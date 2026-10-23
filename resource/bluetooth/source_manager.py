#!/usr/bin/env python3
"""A2DP output management, independent of the assistant and PAN."""
import argparse
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import time
import threading
import signal
import importlib.util

STATE = Path('/var/lib/vbot-bt-source/settings.json')
ROUTE = Path('/run/vbot-bt-source.json')
RUNTIME = Path('/run/vbot-bt-source-status.json')
SINK = '0000110b-0000-1000-8000-00805f9b34fb'
SOURCE = '0000110a-0000-1000-8000-00805f9b34fb'
SERVICE = 'vbot-bt-source.service'
_profile_policy = None


def profile_roles():
    global _profile_policy
    if _profile_policy is None:
        spec = importlib.util.spec_from_file_location('vbot_profile_policy', Path(__file__).with_name('profile_policy.py'))
        _profile_policy = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(_profile_policy)
    return _profile_policy.read_roles()


def atomic(path, value):
    path.parent.mkdir(parents=True, exist_ok=True)
    name = None
    try:
        with tempfile.NamedTemporaryFile(mode='w', encoding='utf-8', dir=str(path.parent), delete=False) as file:
            name = file.name
            json.dump(value, file, ensure_ascii=False)
        os.chmod(name, 0o644)
        os.replace(name, path)
        name = None
    finally:
        if name:
            os.unlink(name)


def settings():
    try:
        value = json.loads(STATE.read_text(encoding='utf-8'))
        mac = value.get('selected', '')
        if mac and not re.fullmatch(r'(?:[0-9A-F]{2}:){5}[0-9A-F]{2}', mac):
            raise ValueError('Invalid selected device')
        return {'selected': mac, 'autoconnect': value.get('autoconnect') is True}
    except FileNotFoundError:
        return {'selected': '', 'autoconnect': False}


def connection_limit():
    try:
        value = json.loads(STATE.with_name('connection_limits.json').read_text())['max_output_connections']
    except (FileNotFoundError, KeyError):
        return 5
    if type(value) is not int or not 1 <= value <= 7:
        raise ValueError('Giới hạn kết nối loa/tai nghe phải từ 1 đến 7')
    return value


def input_connection_limit():
    try:
        value = json.loads(STATE.with_name('connection_limits.json').read_text())['max_input_connections']
        if type(value) is int and 1 <= value <= 7:
            return value
    except (OSError, ValueError, KeyError, TypeError, AttributeError):
        pass
    return 2


def input_selection():
    try:
        value = json.loads(STATE.with_name('input.json').read_text()).get('selected', '')
        return value if isinstance(value, str) and re.fullmatch(r'(?:[0-9A-F]{2}:){5}[0-9A-F]{2}', value) else ''
    except (OSError, ValueError, TypeError, AttributeError):
        return ''


def select_input(dbus, bus, rows, mac):
    target = next((d for d in rows if d['mac'] == mac), None)
    if not target or not target.get('paired') or not target.get('connected') or not target.get('audio_source'):
        raise ValueError('Điện thoại chưa kết nối hoặc không hỗ trợ phát nhạc vào VBot')
    selected = dbus.Interface(bus.get_object('org.bluez', target['path']), 'org.bluez.Device1')
    opened = not target.get('input_connected')
    disconnected = []
    try:
        if opened:
            try:
                selected.ConnectProfile(SOURCE, timeout=8)
            except Exception as error:
                if getattr(error, 'get_dbus_name', lambda: '')() not in ('org.bluez.Error.AlreadyConnected', 'org.bluez.Error.AlreadyExists'):
                    raise
        for other in rows:
            if other['mac'] == mac or not other.get('input_connected'):
                continue
            obj = bus.get_object('org.bluez', other['path'])
            try:
                dbus.Interface(obj, 'org.bluez.MediaControl1').Pause(timeout=2)
            except Exception:
                pass
            dbus.Interface(obj, 'org.bluez.Device1').DisconnectProfile(SOURCE, timeout=3)
            disconnected.append(other)
        atomic(STATE.with_name('input.json'), {'selected': mac})
    except Exception:
        for other in disconnected:
            try:
                dbus.Interface(bus.get_object('org.bluez', other['path']), 'org.bluez.Device1').ConnectProfile(SOURCE, timeout=3)
            except Exception:
                pass
        if opened:
            try:
                selected.DisconnectProfile(SOURCE, timeout=3)
            except Exception:
                pass
        raise


def output_connections(rows):
    return [row for row in rows if row.get('audio_sink') and row.get('connected')]


def enforce_connection_limit(dbus, bus, rows, maximum, selected, remembered):
    connected = output_connections(rows)
    order = {mac: index for index, mac in enumerate(remembered)}
    connected.sort(key=lambda row: (row['mac'] != selected, row['mac'] not in order, order.get(row['mac'], 0), row['mac']))
    keep = connected[:maximum]
    for row in connected[maximum:]:
        dbus.Interface(bus.get_object('org.bluez', row['path']), 'org.bluez.Device1').Disconnect(timeout=10)
        print('[Bluetooth Source] Đạt giới hạn '+str(maximum)+' loa/tai nghe; ngắt '+row['name']+' ('+row['mac']+')', flush=True)
    return [row['mac'] for row in keep]


def bluez():
    import dbus
    bus = dbus.SystemBus()
    objects = dbus.Interface(bus.get_object('org.bluez', '/'), 'org.freedesktop.DBus.ObjectManager').GetManagedObjects(timeout=5)
    try:
        audio = dbus.Interface(bus.get_object('org.bluealsa', '/org/bluealsa'), 'org.freedesktop.DBus.ObjectManager').GetManagedObjects(timeout=5)
        objects.update(audio)
    except dbus.exceptions.DBusException:
        # No audio service means no usable A2DP output, even if PAN is up.
        pass
    return dbus, bus, objects


def device_rows(objects):
    result = []
    outputs = set()
    inputs = set()
    playing_outputs = set()
    playing_inputs = set()
    for interfaces in objects.values():
        pcm = interfaces.get('org.bluealsa.PCM1', {})
        device = str(pcm.get('Device', ''))
        if pcm.get('Transport') == 'A2DP-source':
            outputs.add(device)
            if pcm.get('Running'):
                playing_outputs.add(device)
        elif pcm.get('Transport') == 'A2DP-sink':
            inputs.add(device)
            if pcm.get('Running'):
                playing_inputs.add(device)
    for path, interfaces in objects.items():
        d = interfaces.get('org.bluez.Device1')
        if d:
            uuids = [str(value).lower() for value in d.get('UUIDs', [])]
            result.append({'mac': str(d['Address']).upper(), 'name': str(d.get('Alias') or d.get('Name') or d['Address']),
                           'paired': bool(d.get('Paired')), 'trusted': bool(d.get('Trusted')),
                           'connected': bool(d.get('Connected')), 'audio_sink': SINK in uuids,
                           'audio_source': SOURCE in uuids,
                           'output_connected': str(path) in outputs and bool(d.get('Connected')),
                           'input_connected': str(path) in inputs and bool(d.get('Connected')),
                           'output_running': str(path) in playing_outputs and bool(d.get('Connected')),
                           'input_running': str(path) in playing_inputs and bool(d.get('Connected')),
                           'rssi': int(d['RSSI']) if 'RSSI' in d else None, 'path': str(path)})
    return sorted(result, key=lambda value: (not value['paired'], value['name'].casefold()))


def status():
    _, _, objects = bluez()
    adapters = [str(i['org.bluez.Adapter1'].get('Alias', '')) for i in objects.values() if 'org.bluez.Adapter1' in i]
    try:
        route = json.loads(ROUTE.read_text())
        if time.monotonic() - route.get('at', 0) > 12:
            route = {}
    except (OSError, ValueError, TypeError):
        route = {}
    installed = subprocess.run(['systemctl', 'is-active', '--quiet', SERVICE], timeout=5).returncode == 0
    rows = device_rows(objects)
    config, maximum = settings(), connection_limit()
    result = {'settings': config, 'devices': rows, 'roles': profile_roles(), 'max_output_connections': maximum, 'output_connections': len(output_connections(rows)), 'adapter': ', '.join(adapters),
              'installed': installed, 'route': route, 'input_selected': input_selection(), 'max_input_connections': input_connection_limit()}
    return result


def install(fix_coexistence=True):
    help_result = subprocess.run(['bluealsad', '--help'], capture_output=True, text=True, timeout=5, check=True)
    if 'a2dp-source' not in help_result.stdout:
        raise RuntimeError('BlueALSA hiện tại chưa hỗ trợ A2DP Source')
    # A separate drop-in keeps the original service file recoverable.
    directory = Path('/etc/systemd/system/bluealsa.service.d')
    directory.mkdir(parents=True, exist_ok=True)
    override = directory / '90-vbot-source.conf'
    original = subprocess.run(['systemctl', 'show', '-p', 'ExecStart', '--value', 'bluealsa.service'], capture_output=True, text=True, timeout=5, check=True).stdout
    # Retain all existing flags, codec options and adapter selections.
    match = re.search(r'argv\[\]=(.*?)(?: ;|$)', original)
    if not match or '\n' in match[1]:
        raise RuntimeError('Không xác định được lệnh BlueALSA hiện tại')
    command = match[1].strip()
    if 'a2dp-source' not in command:
        command += ' -p a2dp-source'
    if 'a2dp-sink' not in command:
        command += ' -p a2dp-sink'
    root = Path(__file__).resolve().parents[2]
    agent = root / 'resource/bluetooth/bluetooth_agent.py'
    target_agent = Path('/usr/local/bin/bluetooth_agent.py')
    target_service = Path('/etc/systemd/system/' + SERVICE)
    # Read every input and snapshot every target before changing the system.
    agent_data = agent.read_bytes()
    targets = (override, target_agent, target_service)
    snapshots = {path: (path.read_bytes(), path.stat().st_mode & 0o777) if path.exists() else None for path in targets}
    states = {}
    for unit in ('bluealsa.service', 'vbot-bluetooth-agent.service', SERVICE):
        states[unit] = {
            'active': subprocess.run(['systemctl', 'is-active', '--quiet', unit], timeout=5).returncode == 0,
            'enabled': subprocess.run(['systemctl', 'is-enabled', '--quiet', unit], timeout=5).returncode == 0,
        }
    backup = Path('/home/pi/VBot_AEC3/bluetooth_source_backups') / (time.strftime('%Y%m%d_%H%M%S') + '_' + str(os.getpid()))
    backup.mkdir(parents=True, exist_ok=True)
    for path, snapshot in snapshots.items():
        if snapshot is not None:
            (backup / path.name).write_bytes(snapshot[0])
    atomic(backup / 'services.json', states)
    service = '[Unit]\nDescription=VBot Bluetooth A2DP output policy\nAfter=bluetooth.service bluealsa.service\nWants=bluealsa.service\n[Service]\nExecStart=/usr/bin/python3 "' + str(Path(__file__).resolve()) + '" watch\nRestart=on-failure\nRestartSec=5\n[Install]\nWantedBy=multi-user.target\n'
    try:
        override.write_text('[Service]\nExecStart=\nExecStart=' + command + '\n', encoding='utf-8')
        target_agent.write_bytes(agent_data)
        target_service.write_text(service, encoding='utf-8')
        subprocess.run(['systemctl', 'daemon-reload'], check=True, timeout=15)
        subprocess.run(['systemctl', 'restart', 'bluealsa.service', 'vbot-bluetooth-agent.service'], check=True, timeout=35)
        subprocess.run(['systemctl', 'enable', '--now', SERVICE], check=True, timeout=15)
        # enable --now does not reload code in an already running worker.
        subprocess.run(['systemctl', 'restart', SERVICE], check=True, timeout=15)
    except Exception as error:
        failures = []
        def restore_call(command, timeout=35):
            try:
                subprocess.run(command, check=True, timeout=timeout)
            except Exception as restore_error:
                failures.append(str(restore_error))
        restore_call(['systemctl', 'stop', SERVICE])
        restore_call(['systemctl', 'disable', SERVICE])
        for path, snapshot in snapshots.items():
            try:
                if snapshot is None:
                    path.unlink(missing_ok=True)
                else:
                    path.write_bytes(snapshot[0])
                    path.chmod(snapshot[1])
            except OSError as restore_error:
                failures.append(str(restore_error))
        restore_call(['systemctl', 'daemon-reload'])
        for unit, state in states.items():
            if unit == SERVICE and snapshots[target_service] is None and not state['active'] and not state['enabled']:
                continue  # Newly created unit was already stopped and removed.
            if unit == SERVICE and state['enabled']:
                restore_call(['systemctl', 'enable', unit])
            restore_call(['systemctl', 'restart' if state['active'] else 'stop', unit])
        if failures:
            raise RuntimeError(str(error) + '; rollback incomplete: ' + '; '.join(failures) + '; backup: ' + str(backup)) from error
        raise
    message = 'Đã bật A2DP Source và giữ A2DP Sink. Sao lưu: ' + str(backup)
    if fix_coexistence:
        try:
            result = subprocess.run([sys.executable, str(root / 'resource/bluetooth/fix_wifi_bluetooth.py'), '--apply'], capture_output=True, text=True, timeout=40)
            details = json.loads(result.stdout)
            message += '\n[Wi-Fi/Bluetooth] ' + details.get('message', '')
            if result.returncode != 0:
                message += ' (Source đã cài; bước sửa firmware chưa hoàn tất.)'
        except Exception as error:
            message += '\n[Wi-Fi/Bluetooth] Chưa hoàn tất: ' + str(error)
    return {'message': message}


def update_route(config, rows):
    if not profile_roles()['source']:
        config = dict(config, selected='')
    row = next((d for d in rows if d['mac'] == config['selected']), None)
    device = row['mac'] if row and row.get('output_connected') and row['audio_sink'] else ''
    route = {'device': device, 'name': row['name'] if row else '', 'at': time.monotonic()}
    atomic(ROUTE, route)
    return route


def publish_runtime(config, rows, route, maximum, adapter='', installed=True):
    atomic(RUNTIME, {'settings': config, 'devices': rows, 'route': route, 'adapter': adapter,
                     'installed': installed, 'roles': profile_roles(), 'input_selected': input_selection(), 'max_input_connections': input_connection_limit(), 'max_output_connections': maximum, 'at': time.monotonic()})


def publish_status(value):
    # Only a command holding the policy lock may replace the shared snapshot.
    if isinstance(value, dict) and all(key in value for key in ('settings', 'devices', 'route', 'max_output_connections')):
        publish_runtime(value['settings'], value['devices'], value['route'], value['max_output_connections'], value.get('adapter', ''), value.get('installed', True))
    return value


def watch_step(state):
    import fcntl
    # Route writes, cap enforcement and reconnect must share the same lock as
    # route-changing commands. Never apply a snapshot obtained before a switch.
    with open('/run/lock/vbot-bt-source.lock', 'a') as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            return False
        config = settings()
        dbus, bus, objects = bluez()
        rows = device_rows(objects)
        roles = profile_roles()
        preferred_input = input_selection()
        if preferred_input and not any(d['mac'] == preferred_input and d.get('connected') and d.get('audio_source') for d in rows):
            atomic(STATE.with_name('input.json'), {'selected': ''})
            preferred_input = ''
        if not roles['source']:
            # Return audio locally before potentially slow remote disconnects.
            update_route(dict(config, selected=''), rows)
        failures = {}
        for role, prefix, uuid in (('source', 'output', SINK), ('sink', 'input', SOURCE)):
            if roles[role] and not (role == 'sink' and preferred_input):
                continue
            for device in rows:
                if role == 'sink' and roles[role] and device['mac'] == preferred_input:
                    continue
                if not device.get(prefix+'_connected'):
                    continue
                try:
                    dbus.Interface(bus.get_object('org.bluez', device['path']), 'org.bluez.Device1').DisconnectProfile(uuid, timeout=3)
                    device[prefix+'_connected'] = device[prefix+'_running'] = False
                except Exception as error:
                    # One stale device must not starve the route heartbeat for
                    # an unrelated, active speaker or break the whole snapshot.
                    key = role+':'+device['mac']
                    failures[key] = str(error)
                    if state.get('profile_errors', {}).get(key) != str(error):
                        print('[Bluetooth Source] Disable '+key+': '+str(error), flush=True)
        state['profile_errors'] = failures
        maximum = connection_limit()
        state['remembered'] = enforce_connection_limit(dbus, bus, rows, maximum, config['selected'], state.get('remembered', []))
        row = next((d for d in rows if d['mac'] == config['selected']), None)
        route = update_route(config if roles['source'] else dict(config, selected=''), rows)
        adapter = ', '.join(str(i['org.bluez.Adapter1'].get('Alias', '')) for i in objects.values() if 'org.bluez.Adapter1' in i)
        publish_runtime(config, rows, route, maximum, adapter)
        state['cached'] = (config, rows, maximum, adapter)
        if config != state.get('previous'):
            state['next_attempt'] = 0
            state['previous'] = config
        has_room = row and (row.get('connected') or len(output_connections(rows)) < maximum)
        if roles['source'] and has_room and config['autoconnect'] and row['paired'] and row['audio_sink'] and not row.get('output_connected') and time.monotonic() >= state.get('next_attempt', 0):
            state['next_attempt'] = time.monotonic() + 30
            dbus.Interface(bus.get_object('org.bluez', row['path']), 'org.bluez.Device1').ConnectProfile(SINK, timeout=8)
        return True


def watch():
    state = {}
    last_error = None
    pending, stopped = threading.Event(), threading.Event()
    loop = None
    bus = None
    receivers = []
    try:
        from dbus.mainloop.glib import DBusGMainLoop
        from gi.repository import GLib
        import dbus
        DBusGMainLoop(set_as_default=True)
        bus = dbus.SystemBus()
        def changed(interface, properties, *args, **kwargs):
            if str(interface) in ('org.bluez.Device1', 'org.bluez.Adapter1', 'org.bluez.MediaTransport1', 'org.bluealsa.PCM1') and set(properties).intersection({'Connected','Paired','Alias','Name','UUIDs','Powered','State','Volume','Running','Transport'}):
                pending.set()
        def interfaces(*args, **kwargs):
            pending.set()
        def owner(name, old, new):
            if str(name) in ('org.bluez', 'org.bluealsa'):
                pending.set()
        subscriptions = [(handler, dict(options, bus_name=service))
                         for service in ('org.bluez', 'org.bluealsa')
                         for handler, options in [(changed, {'dbus_interface':'org.freedesktop.DBus.Properties','signal_name':'PropertiesChanged'}),
                                                  (interfaces, {'dbus_interface':'org.freedesktop.DBus.ObjectManager','signal_name':'InterfacesAdded'}),
                                                  (interfaces, {'dbus_interface':'org.freedesktop.DBus.ObjectManager','signal_name':'InterfacesRemoved'})]]
        subscriptions.append((owner, {'dbus_interface':'org.freedesktop.DBus','signal_name':'NameOwnerChanged'}))
        for handler, options in subscriptions:
            bus.add_signal_receiver(handler, **options)
            receivers.append((handler, options))
        loop = GLib.MainLoop()
        threading.Thread(target=loop.run, name='BluetoothSourceDBus', daemon=True).start()
        print('[Bluetooth Source] DBus events enabled; reconciliation every 15s', flush=True)
    except Exception as error:
        print('[Bluetooth Source] DBus event fallback: '+str(error), flush=True)
    def stop(*args):
        stopped.set(); pending.set()
    signal.signal(signal.SIGTERM, stop)
    signal.signal(signal.SIGINT, stop)
    previous_files = None
    reconciled = heartbeat = 0
    pending.set()
    try:
        while not stopped.is_set():
            pending.wait(1)
            now = time.monotonic()
            files = []
            for path in (STATE, STATE.with_name('input.json'), STATE.with_name('connection_limits.json'), Path(__file__).resolve().parents[2]/'Config.json'):
                try:
                    info = path.stat(); files.append((info.st_mtime_ns, info.st_size, info.st_ino))
                except OSError:
                    files.append(None)
            dirty = files != previous_files
            previous_files = files
            try:
                if stopped.is_set():
                    break
                if pending.is_set() or dirty or now-reconciled >= 15:
                    if pending.is_set():
                        stopped.wait(.1)  # Coalesce a burst from one profile transition.
                    pending.clear()
                    if watch_step(state):
                        reconciled = heartbeat = time.monotonic()
                    else:
                        pending.set()
                        stopped.wait(.5)
                elif now-heartbeat >= 3 and state.get('cached'):
                    # Keep the output watchdog alive without querying DBus.
                    import fcntl
                    with open('/run/lock/vbot-bt-source.lock', 'a') as lock:
                        try:
                            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
                        except BlockingIOError:
                            continue
                        config, rows, maximum, adapter = state['cached']
                        if settings() == config:
                            route = update_route(config if profile_roles()['source'] else dict(config, selected=''), rows)
                            publish_runtime(config, rows, route, maximum, adapter)
                        else:
                            pending.set()
                        heartbeat = now
                last_error = None
            except Exception as error:
                state.pop('cached', None)
                if str(error) != last_error:
                    print('[Bluetooth Source] ' + str(error), flush=True)
                last_error = str(error)
                reconciled = time.monotonic()
                stopped.wait(1)
    finally:
        if loop: loop.quit()
        if bus:
            for handler, options in receivers:
                bus.remove_signal_receiver(handler, **options)


def execute(action, mac='', auto=False, fix_coexistence=True, max_connections=None):
    if action == 'install':
        return install(fix_coexistence=fix_coexistence)
    if action == 'watch':
        return watch()
    if action == 'status':
        return status()
    roles = profile_roles()
    if action in ('connect', 'select') and not roles['source']:
        raise ValueError('A2DP Source đã tắt trong cấu hình')
    if action in ('sink_connect', 'input_select') and not roles['sink']:
        raise ValueError('A2DP Sink đã tắt trong cấu hình')
    dbus, bus, objects = bluez()
    if action == 'input_select':
        select_input(dbus, bus, device_rows(objects), mac)
        result = publish_status(status())
        result['input_selected'] = mac
        return result
    if action == 'scan':
        path = next((p for p, i in objects.items() if 'org.bluez.Adapter1' in i), None)
        if not path:
            raise RuntimeError('Không có bộ điều khiển Bluetooth')
        adapter = dbus.Interface(bus.get_object('org.bluez', path), 'org.bluez.Adapter1')
        # A2DP uses BR/EDR. Avoid spending this scan on unrelated BLE adverts.
        adapter.SetDiscoveryFilter({'Transport': 'bredr'}, timeout=5)
        started = False
        try:
            adapter.StartDiscovery(timeout=5)
            started = True
            time.sleep(12)
        finally:
            try:
                if started:
                    adapter.StopDiscovery(timeout=5)
            finally:
                adapter.SetDiscoveryFilter({}, timeout=5)
        # Discovery can take longer than the route watchdog's deadline. It
        # owns a separate lock, so only the policy worker publishes snapshots.
        return status()
    config = settings()
    if action in ('connection_limit', 'input_connection_limit'):
        if max_connections is None:
            max_connections = 2 if action == 'input_connection_limit' else 5
        if type(max_connections) is not int or not 1 <= max_connections <= 7:
            raise ValueError('Giới hạn kết nối Bluetooth phải từ 1 đến 7')
        rows = device_rows(objects)
        is_input = action == 'input_connection_limit'
        current = sum(bool(row.get('connected') and row.get('audio_source')) for row in rows) if is_input else len(output_connections(rows))
        if max_connections < current:
            raise ValueError('Đang có '+str(current)+(' điện thoại' if is_input else ' loa/tai nghe')+' kết nối. Hãy ngắt bớt trước khi giảm giới hạn để tránh dừng nhạc.')
        target = STATE.with_name('connection_limits.json')
        limits = {}
        if target.exists():
            limits = json.loads(target.read_text())
            if not isinstance(limits, dict):
                raise ValueError('Cấu hình giới hạn Bluetooth không hợp lệ')
            atomic(target.with_suffix('.json.bak'), limits)
        limits['max_input_connections' if is_input else 'max_output_connections'] = max_connections
        atomic(target, limits)
        result = status()
        result['message'] = 'Đã lưu giới hạn '+str(max_connections)+(' điện thoại' if is_input else ' loa/tai nghe')+' kết nối đồng thời.'
        return publish_status(result)
    if action == 'local':
        config.update(selected='', autoconnect=False)
    else:
        if not re.fullmatch(r'(?:[0-9A-F]{2}:){5}[0-9A-F]{2}', mac):
            raise ValueError('MAC Bluetooth không hợp lệ')
        row = next((r for r in device_rows(objects) if r['mac'] == mac), None)
        if not row:
            raise ValueError('Không tìm thấy thiết bị; hãy quét lại')
        obj = bus.get_object('org.bluez', row['path'])
        device = dbus.Interface(obj, 'org.bluez.Device1')
        props = dbus.Interface(obj, 'org.freedesktop.DBus.Properties')
        if action == 'sink_connect' and row.get('audio_source') and not row.get('connected'):
            current = sum(bool(item.get('connected') and item.get('audio_source')) for item in device_rows(objects))
            if current >= input_connection_limit():
                raise ValueError('Đã đạt giới hạn '+str(input_connection_limit())+' điện thoại kết nối. Hãy ngắt một điện thoại trước.')
        if (action in ('connect', 'select') or (action == 'pair' and not row['paired'])) and row['audio_sink'] and not row['connected']:
            if len(output_connections(device_rows(objects))) >= connection_limit():
                raise ValueError('Đã đạt giới hạn '+str(connection_limit())+' loa/tai nghe kết nối. Ngắt một thiết bị trước khi kết nối thiết bị mới.')
        if action == 'pair':
            if not row['paired']:
                # Sink agent hides pairing while a phone is connected. Pairing
                # with bondable off can succeed temporarily without saving keys.
                adapter_path = row['path'].split('/dev_', 1)[0]
                adapter_props = dbus.Interface(bus.get_object('org.bluez', adapter_path), 'org.freedesktop.DBus.Properties')
                was_pairable = bool(adapter_props.Get('org.bluez.Adapter1', 'Pairable', timeout=5))
                try:
                    if not was_pairable:
                        adapter_props.Set('org.bluez.Adapter1', 'Pairable', dbus.Boolean(True), timeout=5)
                    device.Pair(timeout=30)
                finally:
                    if not was_pairable:
                        adapter_props.Set('org.bluez.Adapter1', 'Pairable', dbus.Boolean(False), timeout=5)
            # Pair() completing and Trusted=True do not prove keys were retained.
            deadline = time.monotonic() + 2
            while not bool(props.Get('org.bluez.Device1', 'Paired', timeout=5)):
                if time.monotonic() >= deadline:
                    raise RuntimeError('Thiết bị chưa xác nhận ghép đôi (Paired=false). Bật chế độ ghép đôi trên loa/tai nghe rồi thử lại; Trusted không đồng nghĩa đã ghép đôi.')
                time.sleep(0.2)
            props.Set('org.bluez.Device1', 'Trusted', dbus.Boolean(True), timeout=5)
            result = status()
            paired = next((item for item in result.get('devices', []) if item['mac'] == mac), None)
            if not paired or not paired.get('paired'):
                raise RuntimeError('Ghép đôi chưa được giữ lại trên BlueZ. Hãy bật chế độ ghép đôi rồi thử lại.')
            result['message'] = 'Đã xác nhận ghép đôi với '+paired['name']+'. Có thể chọn kết nối âm thanh.'
            # Pairing must never save the pre-pair route/settings over a
            # selection changed while the Bluetooth confirmation was pending.
            return result
        elif action in ('sink_connect', 'sink_disconnect'):
            # The remote phone is the Source; VBot receives as the Sink.
            # Disconnect only audio, preserving an active PAN connection.
            if not row['audio_source'] or config['selected'] == mac:
                raise ValueError('Thiết bị không phải nguồn nhạc đầu vào khả dụng')
            if not row['paired']:
                raise ValueError('Hãy ghép đôi điện thoại trước khi kết nối')
            if action == 'sink_connect':
                props.Set('org.bluez.Device1', 'Trusted', dbus.Boolean(True), timeout=5)
                try:
                    device.ConnectProfile(SOURCE, timeout=15)
                except dbus.exceptions.DBusException as error:
                    if error.get_dbus_name() not in ('org.bluez.Error.AlreadyConnected', 'org.bluez.Error.AlreadyExists'):
                        raise
            else:
                device.DisconnectProfile(SOURCE, timeout=10)
        elif action in ('connect', 'select'):
            if not row['audio_sink']:
                raise ValueError('Thiết bị không công bố hỗ trợ loa/tai nghe A2DP Sink')
            if not row['paired']:
                raise ValueError('Hãy ghép đôi trước khi kết nối')
            # Remember intent first, so the Sink agent will not mistake this
            # connection for an incoming phone and reject it.
            previous_config = dict(config)
            reconnect = config['autoconnect'] if action == 'select' and config['selected'] == mac else auto
            config.update(selected=mac, autoconnect=reconnect)
            atomic(STATE, config)
            try:
                props.Set('org.bluez.Device1', 'Trusted', dbus.Boolean(True), timeout=5)
                try:
                    device.ConnectProfile(SINK, timeout=15)
                except dbus.exceptions.DBusException as error:
                    if error.get_dbus_name() not in ('org.bluez.Error.AlreadyConnected', 'org.bluez.Error.AlreadyExists'):
                        raise
            except Exception:
                atomic(STATE, previous_config)
                _, _, fresh = bluez()
                update_route(previous_config, device_rows(fresh))
                raise
        elif action in ('disconnect', 'device_disconnect'):
            if config['selected'] == mac:
                config.update(selected='', autoconnect=False)
                atomic(STATE, config)
                atomic(ROUTE, {'device': '', 'at': time.monotonic()})
            if row['connected']:
                if action == 'device_disconnect':
                    device.Disconnect(timeout=10)
                else:
                    device.DisconnectProfile(SINK, timeout=10)
        elif action == 'forget':
            if config['selected'] == mac:
                config.update(selected='', autoconnect=False)
                atomic(STATE, config)
                atomic(ROUTE, {'device': '', 'at': time.monotonic()})
            adapter = row['path'].split('/dev_', 1)[0]
            dbus.Interface(bus.get_object('org.bluez', adapter), 'org.bluez.Adapter1').RemoveDevice(row['path'], timeout=10)
        elif action == 'autoconnect':
            if config['selected'] != mac:
                raise ValueError('Chọn thiết bị làm đầu ra trước')
            config['autoconnect'] = auto
        else:
            raise ValueError('Thao tác không hợp lệ')
    atomic(STATE, config)
    _, _, fresh = bluez()
    update_route(config, device_rows(fresh))
    result = status()
    return publish_status(result)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('action', choices=['input_connection_limit', 'connection_limit', 'status', 'install', 'scan', 'pair', 'connect', 'select', 'disconnect', 'device_disconnect', 'sink_connect', 'sink_disconnect', 'input_select', 'forget', 'local', 'autoconnect', 'watch'])
    parser.add_argument('--max-connections', type=int, default=None)
    parser.add_argument('--mac', default='')
    parser.add_argument('--enabled', choices=['0', '1'], default='0')
    parser.add_argument('--skip-coexistence', action='store_true', help='Install Source without changing Wi-Fi firmware')
    args = parser.parse_args()
    try:
        if args.action in ('status', 'watch'):
            data = execute(args.action)
        else:
            import fcntl
            lock_path = '/run/lock/vbot-bt-discovery.lock' if args.action in ('scan', 'pair') else '/run/lock/vbot-bt-source.lock'
            with open(lock_path, 'a') as lock:
                try:
                    fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
                except BlockingIOError:
                    raise RuntimeError('Một thao tác Bluetooth Source đang chạy; hãy thử lại sau')
                data = execute(args.action, args.mac.upper(), args.enabled == '1', fix_coexistence=not args.skip_coexistence, max_connections=args.max_connections if args.max_connections is not None else (2 if args.action == 'input_connection_limit' else 5))
        print(json.dumps({'success': True, **(data or {})}, ensure_ascii=False))
    except Exception as error:
        print(json.dumps({'success': False, 'message': str(error)}, ensure_ascii=False))
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
