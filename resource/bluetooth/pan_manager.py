"""Bluetooth PAN management for WebUI; no assistant/audio imports."""
import argparse
import datetime
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import importlib.util
import ipaddress
import socket
import struct
import time

PAN_POLICY = Path('/etc/NetworkManager/conf.d/90-vbot-pan.conf')
INSTALL_STATE = Path('/var/lib/vbot-pan/installed.json')
BNEP_MODULE = Path('/etc/modules-load.d/vbot-pan.conf')
PROBE_STATE = Path('/run/vbot-pan-probe.json')
DEVICE_POLICY = Path('/var/lib/vbot-pan/devices.json')
AUTO_STATE = Path('/run/vbot-pan-auto.json')
SYSTEMD_DIR = Path('/etc/systemd/system')
MODES = ('auto', 'on', 'off')


def save_json(path, value):
    path.parent.mkdir(parents=True, exist_ok=True)
    temp = path.with_name(path.name + '.tmp')
    temp.write_text(json.dumps(value, ensure_ascii=False), encoding='utf-8')
    temp.chmod(0o600)
    temp.replace(path)


def device_modes():
    if not DEVICE_POLICY.exists():
        return {}
    data = json.loads(DEVICE_POLICY.read_text(encoding='utf-8'))
    modes = data.get('devices') if isinstance(data, dict) else None
    if not isinstance(modes, dict) or any(
        not re.fullmatch(r'(?:[0-9A-F]{2}:){5}[0-9A-F]{2}', mac) or mode not in MODES
        for mac, mode in modes.items()
    ):
        raise ValueError('Dữ liệu chế độ PAN không hợp lệ: ' + str(DEVICE_POLICY))
    return modes


def profile_mode(mac, modes, profile=None):
    # Only an explicitly saved VBot policy overrides the default Auto VBot.
    return modes.get(mac, 'auto')


def reset_retry(mac):
    try:
        data = json.loads(AUTO_STATE.read_text(encoding='utf-8'))
    except (OSError, ValueError):
        return
    if isinstance(data, dict) and mac in data:
        data.pop(mac)
        save_json(AUTO_STATE, data)


def wifi_station_connected():
    """An active setup AP is not a connection to a Wi-Fi router."""
    for line in run('nmcli', '-e', 'no', '-t', '-f', 'UUID,TYPE,DEVICE', 'connection', 'show', '--active').splitlines():
        parts = line.split(':', 2)
        if len(parts) != 3 or parts[1] not in ('wifi', '802-11-wireless'):
            continue
        uuid, _, interface = parts
        if field(uuid, '802-11-wireless.mode') == 'ap':
            continue
        ipv4 = run('nmcli', '-g', 'IP4.ADDRESS', 'device', 'show', interface)
        ipv6 = run('nmcli', '-g', 'IP6.ADDRESS', 'device', 'show', interface)
        for address in (ipv4 + '\n' + ipv6).splitlines():
            try:
                ip = ipaddress.ip_interface(address).ip
                if not ip.is_link_local and not ip.is_loopback and not ip.is_unspecified:
                    return True
            except ValueError:
                pass
    return False


def install_policy_service():
    """Small periodic oneshot; works even while VBot and Apache are stopped."""
    helper = str(Path(__file__).resolve()).replace('%', '%%').replace('\\', '\\\\').replace('"', '\\"')
    units = {
        'vbot-pan-policy.service': '[Unit]\nDescription=VBot Wi-Fi priority and Bluetooth PAN policy\nAfter=NetworkManager.service bluetooth.service\n\n[Service]\nType=oneshot\nExecStart=/usr/bin/python3 "' + helper + '" reconcile\nTimeoutStartSec=60\n',
        'vbot-pan-policy.timer': '[Unit]\nDescription=Check VBot Bluetooth PAN policy\n\n[Timer]\nOnBootSec=10\nOnUnitInactiveSec=10\nAccuracySec=1\nUnit=vbot-pan-policy.service\n\n[Install]\nWantedBy=timers.target\n',
    }
    changed = False
    for name, text in units.items():
        path = SYSTEMD_DIR / name
        if not path.exists() or path.read_text(encoding='utf-8') != text:
            path.write_text(text, encoding='utf-8')
            path.chmod(0o644)
            changed = True
    if changed:
        run('systemctl', 'daemon-reload')
    run('systemctl', 'enable', '--now', 'vbot-pan-policy.timer')


def ensure_profile(mac, existing):
    if existing:
        return existing
    run('nmcli', 'connection', 'add', 'type', 'bluetooth', 'con-name', 'VBot PAN ' + mac,
        'bluetooth.type', 'panu', 'bluetooth.bdaddr', mac, 'connection.autoconnect', 'no',
        'ipv4.route-metric', '1800', 'ipv6.route-metric', '1800')
    return [p for p in profiles() if p['mac'] == mac]


def reconcile():
    """Only operate on PAN profiles; never disconnect Bluetooth audio or Wi-Fi."""
    if not INSTALL_STATE.exists() or not pan_enabled():
        return {'message': 'PAN chưa cài hoặc đang tắt.'}
    modes = device_modes()
    _, phones = devices()
    items = profiles()
    by_mac = {}
    for item in items:
        by_mac.setdefault(item['mac'], []).append(item)
    duplicates = {mac for mac, rows in by_mac.items() if len(rows) != 1}
    active = set(run('nmcli', '-t', '-f', 'UUID', 'connection', 'show', '--active').splitlines())
    wifi = wifi_station_connected()
    # Remember legacy preferences before turning off NM's independent retries.
    changed = False
    for item in items:
        if item['mac'] not in duplicates and item['mac'] not in modes:
            modes[item['mac']] = profile_mode(item['mac'], modes, item)
            changed = True
    if changed:
        save_json(DEVICE_POLICY, {'version': 1, 'devices': modes})
    for item in items:
        if item.get('autoconnect') and item['mac'] not in duplicates:
            run('nmcli', 'connection', 'modify', 'uuid', item['uuid'], 'connection.autoconnect', 'no',
                'ipv4.route-metric', '1800', 'ipv6.route-metric', '1800')
    connected = {p['mac']: p for p in phones if p.get('bluetooth_connected') and (p.get('nap') or p['mac'] in by_mac)}
    candidates = sorted(mac for mac in connected if mac not in duplicates and profile_mode(mac, modes) == 'auto')
    # A stable selection avoids repeatedly moving the PAN default route.
    active_auto = [mac for mac in candidates if any(p['uuid'] in active for p in by_mac.get(mac, []))]
    auto_target = (active_auto or candidates or [None])[0] if not wifi else None
    try:
        retries = json.loads(AUTO_STATE.read_text(encoding='utf-8'))
        if not isinstance(retries, dict):
            retries = {}
    except (OSError, ValueError):
        retries = {}
    results = []
    attempted = False
    for mac in sorted(set(by_mac) | set(connected)):
        if mac in duplicates:
            continue
        mode = profile_mode(mac, modes)
        rows = by_mac.get(mac, [])
        uuid = rows[0]['uuid'] if rows else None
        is_active = uuid in active if uuid else False
        if mode == 'auto' and is_active and (wifi or mac != auto_target):
            run('nmcli', '--wait', '10', 'connection', 'down', 'uuid', uuid, timeout=15)
            results.append({'mac': mac, 'action': 'disconnected', 'reason': 'wifi' if wifi else 'other_phone'})
            retries.pop(mac, None)
            continue
        wanted = mac in connected and (mode == 'on' or (mode == 'auto' and mac == auto_target))
        # Off prevents automatic connections, but does not tear down a manual one.
        if not wanted or is_active:
            continue
        retry = retries.get(mac, {})
        if not isinstance(retry, dict):
            retry = {}
        if time.monotonic() < retry.get('next', 0):
            continue
        if attempted:
            continue
        attempted = True
        try:
            rows = ensure_profile(mac, rows)
            if len(rows) != 1:
                raise ValueError('Không xác định được hồ sơ PAN duy nhất')
            # Wi-Fi can finish connecting while a slow Bluetooth setup runs.
            if mode == 'auto' and wifi_station_connected():
                continue
            run('nmcli', '--wait', '25', 'connection', 'up', 'uuid', rows[0]['uuid'], timeout=30)
            retries.pop(mac, None)
            results.append({'mac': mac, 'action': 'connected'})
            if mode == 'auto' and wifi_station_connected():
                run('nmcli', '--wait', '10', 'connection', 'down', 'uuid', rows[0]['uuid'], timeout=15)
                results.append({'mac': mac, 'action': 'disconnected', 'reason': 'wifi'})
        except (RuntimeError, ValueError, subprocess.TimeoutExpired) as error:
            failures = min(int(retry.get('failures', 0)) + 1, 4)
            retries[mac] = {'failures': failures, 'next': time.monotonic() + min(30 * 2 ** (failures - 1), 300)}
            results.append({'mac': mac, 'action': 'retry_later', 'error': str(error)[:240]})
    save_json(AUTO_STATE, retries)
    return {'wifi_connected': wifi, 'results': results, 'message': 'Đã đối chiếu chính sách PAN.'}


def pan_route():
    routes = json.loads(run('ip', '-j', '-4', 'route', 'show', 'default'))
    return next((r for r in routes if re.fullmatch(r'bnep[0-9]+', r.get('dev', '')) and r.get('gateway')), None)


def dns_ipv4(interface, server):
    # Bind the DNS query itself to PAN; system DNS may still use Wi-Fi.
    transaction = os.urandom(2)
    query = transaction + struct.pack('!5H', 256, 1, 0, 0, 0) + b'\x07example\x03com\x00\x00\x01\x00\x01'
    with socket.socket(socket.AF_INET, socket.SOCK_DGRAM) as sock:
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_BINDTODEVICE, interface.encode() + b'\0')
        sock.settimeout(4)
        sock.connect((server, 53))
        sock.send(query)
        packet = sock.recv(4096)
    if len(packet) < 12 or packet[:2] != transaction or packet[3] & 15 or not packet[2] & 128:
        raise RuntimeError('DNS không trả lời hợp lệ qua PAN.')
    def skip_name(offset):
        for _ in range(128):
            size = packet[offset]
            if size & 192 == 192:
                return offset + 2
            offset += 1
            if size == 0:
                return offset
            offset += size
        raise RuntimeError('Tên DNS quá dài.')
    offset = 12
    questions, answers = struct.unpack('!2H', packet[4:8])
    for _ in range(questions):
        offset = skip_name(offset) + 4
    for _ in range(answers):
        offset = skip_name(offset)
        kind, klass, _, length = struct.unpack('!HHIH', packet[offset:offset + 10])
        offset += 10
        if kind == 1 and klass == 1 and length == 4:
            return str(ipaddress.IPv4Address(packet[offset:offset + 4]))
        offset += length
    raise RuntimeError('DNS không trả địa chỉ IPv4 cho điểm kiểm tra.')


def probe_pan(route=None, state_path=None):
    route = route if route is not None else pan_route()
    if not route:
        raise RuntimeError('Chưa có gateway PAN. Hãy kết nối và chờ điện thoại cấp IP.')
    interface, gateway = route['dev'], route['gateway']
    ipaddress.IPv4Address(gateway)
    result = {'interface': interface, 'ifindex': socket.if_nametoindex(interface), 'gateway': gateway, 'at': time.time(),
              'gateway_ok': False, 'dns_ok': False, 'https_ok': False}
    # ICMP may be blocked; keep checking DNS/HTTPS even if ping fails.
    try:
        run('ping', '-I', interface, '-c', '1', '-W', '2', gateway, timeout=4)
        result['gateway_ok'] = True
    except Exception:
        pass
    try:
        # Some phones intermittently fail to forward DNS. This fallback is
        # only for the diagnostic and does not alter the system DNS settings.
        try:
            address = dns_ipv4(interface, gateway)
            result['dns_server'] = gateway
        except Exception:
            address = dns_ipv4(interface, '1.1.1.1')
            result['dns_server'] = '1.1.1.1 (dự phòng)'
        result['dns_ok'] = True
        code = run('curl', '-4', '--noproxy', '*', '--interface', interface,
                   '--resolve', 'example.com:443:' + address, '--connect-timeout', '4',
                   '--max-time', '8', '-sS', '-o', '/dev/null', '-w', '%{http_code}',
                   'https://example.com', timeout=10)
        result['https_ok'] = code.isdigit() and 200 <= int(code) < 400
        result['message'] = 'PAN có Internet qua HTTPS.' if result['https_ok'] else 'HTTPS qua PAN chưa thành công (HTTP ' + code + ').'
    except Exception as error:
        result['message'] = ('Không phân giải được DNS qua gateway PAN. Kiểm tra chia sẻ mạng/dữ liệu di động.'
                             if not result['dns_ok'] else 'DNS hoạt động nhưng HTTPS qua PAN chưa thành công: ' + str(error)[:180])
    target = state_path or PROBE_STATE
    temp = target.with_suffix('.tmp')
    temp.write_text(json.dumps(result), encoding='utf-8')
    temp.replace(target)
    return {'probe': result, 'message': result['message']}


def last_probe():
    try:
        result = json.loads(PROBE_STATE.read_text())
        route = pan_route()
        if route and result['interface'] == route['dev'] and result.get('ifindex') == socket.if_nametoindex(route['dev']) and result['gateway'] == route['gateway'] and 0 <= time.time() - result['at'] < 300:
            return result
    except (OSError, ValueError, KeyError, RuntimeError):
        pass
    return None


def installation_info():
    missing = []
    if not shutil.which('nmcli'):
        missing.append('NetworkManager')
    if not shutil.which('bluetoothctl'):
        missing.append('bluez')
    if importlib.util.find_spec('dbus') is None:
        missing.append('python3-dbus')
    if not shutil.which('curl'):
        missing.append('curl')
    if not shutil.which('ping'):
        missing.append('iputils-ping')
    return {'installed': INSTALL_STATE.is_file(), 'missing': missing}


def inspect_setup():
    info = installation_info()
    return {**info, 'enabled': pan_enabled(),
            'network_manager': run('systemctl', 'is-active', 'NetworkManager', check=False),
            'bluetooth': run('systemctl', 'is-active', 'bluetooth', check=False),
            'bnep_loaded': Path('/sys/module/bnep').exists(),
            'policy': str(PAN_POLICY) if PAN_POLICY.exists() else '',
            'wifi_guard': run('systemctl', 'is-active', 'wifi-connect', check=False)}


def install():
    # The existing network manager must remain in charge of Wi-Fi.
    if not shutil.which('nmcli') or run('systemctl', 'is-active', 'NetworkManager', check=False) != 'active':
        raise RuntimeError('Thiết bị chưa dùng NetworkManager. Cần chuyển cấu hình mạng riêng trước; bộ cài không tự thay trình quản lý Wi-Fi.')
    packages = [p for p in installation_info()['missing'] if p in ('bluez', 'python3-dbus', 'curl', 'iputils-ping')]
    if packages:
        run('apt-get', '-o', 'Acquire::Retries=1', '-o', 'Acquire::http::Timeout=20', 'update', timeout=120)
        run('apt-get', '-o', 'DPkg::Lock::Timeout=30', 'install', '-y', '--no-upgrade',
            '--no-install-recommends', *packages, timeout=180)
    run('modprobe', 'bnep')
    module = BNEP_MODULE
    if not module.exists():
        module.parent.mkdir(parents=True, exist_ok=True)
        module.write_text('# Bluetooth PAN\nbnep\n', encoding='utf-8')
    if run('systemctl', 'is-active', 'bluetooth', check=False) != 'active':
        run('systemctl', 'enable', '--now', 'bluetooth')
    ensure_wifi_guard()
    install_policy_service()
    if not PAN_POLICY.exists():
        set_enabled(False)
    INSTALL_STATE.parent.mkdir(parents=True, exist_ok=True)
    INSTALL_STATE.write_text(json.dumps({'version': 2}), encoding='utf-8')
    return {'message': 'Đã cài đặt Bluetooth PAN. Bạn có thể bật/tắt PAN trên trang này.'}


def pan_enabled():
    return not PAN_POLICY.exists() or 'managed=0' not in PAN_POLICY.read_text(encoding='utf-8')


def set_enabled(enabled):
    if enabled:
        ensure_wifi_guard()
        install_policy_service()
    PAN_POLICY.parent.mkdir(parents=True, exist_ok=True)
    previous = PAN_POLICY.read_bytes() if PAN_POLICY.exists() else None
    temp = PAN_POLICY.with_suffix('.tmp')
    temp.write_text('# VBot Bluetooth PAN only; audio is managed by BlueZ/BlueALSA.\n'
                    '[device-vbot-pan]\nmatch-device=type:bt\nmanaged=' + ('1' if enabled else '0') + '\n', encoding='utf-8')
    temp.chmod(0o644)
    temp.replace(PAN_POLICY)
    try:
        run('nmcli', 'general', 'reload', '1')
        # Apply immediately to existing Bluetooth network devices, without
        # restarting NetworkManager or touching the Bluetooth adapter/audio.
        for line in run('nmcli', '-e', 'no', '-t', '-f', 'DEVICE,TYPE', 'device').splitlines():
            device, _, kind = line.rpartition(':')
            if kind == 'bt':
                run('nmcli', 'device', 'set', device, 'managed', 'yes' if enabled else 'no')
    except Exception:
        if previous is None:
            PAN_POLICY.unlink(missing_ok=True)
        else:
            PAN_POLICY.write_bytes(previous)
        run('nmcli', 'general', 'reload', '1', check=False)
        raise
    return {'message': 'Đã bật Bluetooth PAN.' if enabled else 'Đã tắt Bluetooth PAN; Bluetooth âm thanh vẫn hoạt động.'}


def run(*args, check=True, timeout=8):
    p = subprocess.run(list(args), capture_output=True, text=True, timeout=timeout,
                       env={**os.environ, 'LC_ALL': 'C.UTF-8', 'DEBIAN_FRONTEND': 'noninteractive'})
    if check and p.returncode:
        raise RuntimeError((p.stderr or p.stdout).strip()[:800])
    return p.stdout.strip()


def field(uuid, key):
    return run('nmcli', '-e', 'no', '-g', key, 'connection', 'show', 'uuid', uuid)


def profiles():
    result = []
    for line in run('nmcli', '-t', '-f', 'UUID,TYPE', 'connection', 'show').splitlines():
        uuid, _, kind = line.partition(':')
        if kind not in ('bluetooth', '802-15-1'):
            continue
        if field(uuid, 'bluetooth.type') != 'panu':
            continue
        result.append({'uuid': uuid, 'mac': field(uuid, 'bluetooth.bdaddr').upper(),
                       'name': field(uuid, 'connection.id'),
                       'autoconnect': field(uuid, 'connection.autoconnect') == 'yes'})
    return result


def devices():
    import dbus
    bus = dbus.SystemBus()
    objects = dbus.Interface(bus.get_object('org.bluez', '/'),
                             'org.freedesktop.DBus.ObjectManager').GetManagedObjects()
    aliases, phones = [], []
    for interfaces in objects.values():
        adapter = interfaces.get('org.bluez.Adapter1')
        if adapter:
            aliases.append(str(adapter.get('Alias', '')))
        device = interfaces.get('org.bluez.Device1')
        if device and device.get('Paired'):
            uuids = [str(v).lower() for v in device.get('UUIDs', [])]
            phones.append({'mac': str(device['Address']).upper(),
                           'name': str(device.get('Alias', device['Address'])),
                           'bluetooth_connected': bool(device.get('Connected', False)),
                           'nap': '00001116-0000-1000-8000-00805f9b34fb' in uuids})
    return aliases, phones


def status():
    setup = installation_info()
    if setup['missing']:
        return {**setup, 'enabled': False, 'aliases': [], 'phones': [], 'profiles': [], 'addresses': [], 'route': []}
    aliases, phones = devices()
    items = profiles()
    modes = device_modes()
    active = set(run('nmcli', '-t', '-f', 'UUID', 'connection', 'show', '--active').splitlines())
    addresses = json.loads(run('ip', '-j', '-4', 'addr', 'show'))
    pan_ips = [{'interface': i['ifname'], 'ip': a['local']}
               for i in addresses if i['ifname'].startswith('bnep')
               for a in i.get('addr_info', []) if a.get('scope') == 'global']
    for item in items:
        item['connected'] = item['uuid'] in active
        item['mode'] = profile_mode(item['mac'], modes, item)
    for phone in phones:
        phone['mode'] = profile_mode(phone['mac'], modes, next((p for p in items if p['mac'] == phone['mac']), None))
    return {**setup, 'probe': last_probe(), 'enabled': pan_enabled(), 'wifi_connected': wifi_station_connected(), 'aliases': aliases, 'phones': phones, 'profiles': items, 'addresses': pan_ips,
            'route': run('ip', '-4', 'route', 'get', '1.1.1.1', check=False).splitlines()[:1]}


def ensure_wifi_guard():
    root = Path(__file__).resolve().parents[2]
    command = ['python3', '-c', "import sys; sys.path.insert(0, sys.argv[sys.argv.index('--root')+1]); import Sync_System_Services as sync; sys.exit(sync.main())",
               '--root', str(root), '--only', 'wifi', '--apply']
    if run('systemctl', 'is-active', 'wifi-connect', check=False) != 'active':
        command.append('--no-restart')
    run(*command, timeout=90)


def execute(action, mac, enabled, mode=None):
    if action == 'reconcile':
        return reconcile()
    if action == 'probe':
        return probe_pan()
    if action == 'inspect':
        return inspect_setup()
    if action == 'status':
        return status()
    if action == 'install':
        return install()
    if action == 'enable':
        return set_enabled(enabled)
    if action == 'mode' and mode not in MODES:
        raise ValueError('Chế độ PAN không hợp lệ')
    if (action == 'connect' or (action == 'autoconnect' and enabled)) and not pan_enabled():
        raise ValueError('Bluetooth PAN đang tắt. Hãy bật Kích hoạt Bluetooth PAN trước.')
    if not re.fullmatch(r'(?:[0-9A-F]{2}:){5}[0-9A-F]{2}', mac):
        raise ValueError('Địa chỉ Bluetooth không hợp lệ')
    existing = [p for p in profiles() if p['mac'] == mac]
    if len(existing) > 1:
        raise ValueError('Điện thoại có nhiều hồ sơ PAN; hãy kiểm tra hồ sơ trong NetworkManager.')
    if action in ('mode', 'autoconnect'):
        selected_mode = mode if action == 'mode' else ('on' if enabled else 'off')
        _, phones = devices()
        if not existing and not any(p['mac'] == mac and p.get('nap') for p in phones):
            raise ValueError('Hãy ghép đôi điện thoại và bật chia sẻ mạng Bluetooth trước.')
        ensure_wifi_guard()
        install_policy_service()
        existing = ensure_profile(mac, existing)
        if len(existing) != 1:
            raise ValueError('Không xác định được hồ sơ PAN duy nhất')
        modes = device_modes()
        modes[mac] = selected_mode
        run('nmcli', 'connection', 'modify', 'uuid', existing[0]['uuid'],
            'connection.autoconnect', 'no', 'ipv4.route-metric', '1800', 'ipv6.route-metric', '1800')
        save_json(DEVICE_POLICY, {'version': 1, 'devices': modes})
        reset_retry(mac)
        run('systemctl', 'start', '--no-block', 'vbot-pan-policy.service')
        return {'message': 'Đã lưu chế độ PAN; hệ thống sẽ áp dụng trong khoảng 10 giây.'}
    if action == 'connect':
        _, phones = devices()
        if not any(p['mac'] == mac and p['nap'] for p in phones):
            raise ValueError('Hãy ghép đôi điện thoại và bật chia sẻ mạng Bluetooth trước.')
        if profile_mode(mac, device_modes(), existing[0] if existing else None) == 'auto' and wifi_station_connected():
            return {'message': 'Auto VBot đang ưu tiên Wi-Fi. Chọn Bật nếu muốn dùng PAN cả khi có Wi-Fi.'}
        ensure_wifi_guard()
        if not existing:
            run('nmcli', 'connection', 'add', 'type', 'bluetooth', 'con-name', 'VBot PAN ' + mac,
                'bluetooth.type', 'panu', 'bluetooth.bdaddr', mac, 'connection.autoconnect', 'no')
            existing = [p for p in profiles() if p['mac'] == mac]
    if not existing:
        raise ValueError('Chưa có hồ sơ PAN; hãy kết nối lần đầu trước.')
    uuid = existing[0]['uuid']
    if action == 'connect':
        ensure_wifi_guard()
        run('nmcli', 'connection', 'modify', 'uuid', uuid,
            'ipv4.route-metric', '1800', 'ipv6.route-metric', '1800',
            'connection.autoconnect-retries', '3')
    if action == 'connect':
        try:
            run('nmcli', '--wait', '25', 'connection', 'up', 'uuid', uuid, timeout=30)
        except (RuntimeError, subprocess.TimeoutExpired) as error:
            raise RuntimeError('Không kết nối được PAN. Kiểm tra điện thoại ở gần, bật Bluetooth và chia sẻ mạng; mở trang Điểm truy cập cá nhân rồi thử lại. Chi tiết: ' + str(error)[:240]) from error
    elif action == 'disconnect':
        # Explicit disconnect also prevents an immediate automatic reconnect.
        run('nmcli', 'connection', 'modify', 'uuid', uuid, 'connection.autoconnect', 'no')
        modes = device_modes()
        modes[mac] = 'off'
        save_json(DEVICE_POLICY, {'version': 1, 'devices': modes})
        active = run('nmcli', '-t', '-f', 'UUID', 'connection', 'show', '--active').splitlines()
        if uuid in active:
            run('nmcli', '--wait', '10', 'connection', 'down', 'uuid', uuid, timeout=15)
    return {'message': 'Đã ngắt PAN và tắt tự kết nối.' if action == 'disconnect' else 'Đã thực hiện yêu cầu.'}


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('action', choices=['status', 'probe', 'inspect', 'install', 'enable', 'connect', 'disconnect', 'autoconnect', 'mode', 'reconcile'])
    parser.add_argument('--mac', default='')
    parser.add_argument('--enabled', choices=['0', '1'], default='0')
    parser.add_argument('--mode', choices=MODES)
    args = parser.parse_args()
    try:
        # One writer across WebUI requests; reads never hold up a connection attempt.
        if args.action in ('status', 'inspect'):
            data = execute(args.action, '', False)
        else:
            import fcntl
            with open('/run/lock/vbot-pan.lock', 'a') as lock:
                try:
                    fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
                except BlockingIOError:
                    raise RuntimeError('Một thao tác PAN khác đang chạy, hãy thử lại sau.')
                data = execute(args.action, args.mac.upper(), args.enabled == '1', args.mode)
        print(json.dumps({'success': True, **data}, ensure_ascii=False))
    except Exception as error:
        print(json.dumps({'success': False, 'message': str(error)}, ensure_ascii=False))


if __name__ == '__main__':
    main()
