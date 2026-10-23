#!/usr/bin/env python3
"""Learn Linux Bluetooth input keys; share VBot's action registry, no audio changes."""
import argparse
import json
import os
from pathlib import Path
import re
import select
import struct
import tempfile
import threading
import time

ROOT = Path(__file__).resolve().parents[2]
STATE = ROOT / 'resource/bluetooth/button_keys.json'
LEARNING = Path('/run/vbot-bluetooth-key-learning.json')
EVENT = struct.Struct('@llHHi')


def atomic(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    name = None
    try:
        with tempfile.NamedTemporaryFile(mode='w', encoding='utf-8', dir=str(path.parent), delete=False) as stream:
            name = stream.name
            json.dump(data, stream, ensure_ascii=False)
            stream.flush()
            os.fsync(stream.fileno())
        os.chmod(name, 0o777 if path == STATE else 0o644)
        if hasattr(os, 'geteuid') and os.geteuid() == 0:
            owner = path.parent.stat()
            os.chown(name, owner.st_uid, owner.st_gid)
        os.replace(name, path)
        name = None
    finally:
        if name:
            os.unlink(name)


def load():
    if not STATE.exists():
        return {'version': 1, 'buttons': []}
    data = json.loads(STATE.read_text(encoding='utf-8'))
    if not isinstance(data, dict) or not isinstance(data.get('buttons'), list):
        raise ValueError('Dữ liệu nút Bluetooth không hợp lệ')
    seen = set()
    if len(data['buttons']) > 256:
        raise ValueError('Quá nhiều nút Bluetooth')
    for button in data['buttons']:
        if (not isinstance(button, dict) or not isinstance(button.get('mac'), str)
                or not re.fullmatch(r'(?:[0-9A-F]{2}:){5}[0-9A-F]{2}', button['mac'])
                or type(button.get('code')) is not int or not 1 <= button['code'] <= 767
                or not isinstance(button.get('name'), str) or not 1 <= len(button['name']) <= 80
                or type(button.get('enabled', True)) is not bool
                or not isinstance(button.get('action'), str) or len(button['action']) > 150):
            raise ValueError('Thông tin nút Bluetooth không hợp lệ')
        key = (button['mac'], button['code'])
        if key in seen:
            raise ValueError('Trùng nút Bluetooth')
        seen.add(key)
    return data


def validate_action(action):
    if not isinstance(action, str) or len(action) > 150:
        raise ValueError('Chức năng không hợp lệ')
    registry = json.loads((ROOT / 'resource/action_registry.json').read_text(encoding='utf-8-sig'))
    if action in {item['id'] for item in registry['actions']}:
        return
    if re.fullmatch(r'playlist:[A-Za-z0-9_-]{1,64}|radio:[0-9a-f]{12}', action):
        return
    if action.startswith('routine:'):
        routines = json.loads((ROOT / 'resource/voice_routines/voice_routines.json').read_text(encoding='utf-8-sig'))
        if any(item.get('id') == action[8:] for item in routines.get('routines', [])):
            return
    raise ValueError('Chức năng hoặc kịch bản không còn tồn tại')


def connected():
    import dbus
    bus = dbus.SystemBus()
    objects = dbus.Interface(bus.get_object('org.bluez', '/'), 'org.freedesktop.DBus.ObjectManager').GetManagedObjects(timeout=5)
    return [dict(mac=str(p['Address']).upper(), name=str(p.get('Alias') or p.get('Name') or 'Thiết bị Bluetooth'), names={str(p.get('Alias', '')), str(p.get('Name', ''))})
            for interfaces in objects.values() for p in [interfaces.get('org.bluez.Device1', {})]
            if p.get('Connected') and p.get('Address')]


def inputs(devices):
    """Use unique Bluetooth identity only; never attribute an ambiguous name to a MAC."""
    result = {}
    for path in Path('/sys/class/input').glob('event*'):
        try:
            device = path / 'device'
            if (device / 'id/bustype').read_text().strip() != '0005':
                continue
            name = (device / 'name').read_text().strip()
            uniq = (device / 'uniq').read_text().strip().upper()
            matches = [d for d in devices if (uniq == d['mac'] if uniq else
                       name in d['names'] or name.removesuffix(' (AVRCP)') in d['names'])]
            if len(matches) == 1:
                result[Path('/dev/input') / path.name] = matches[0]['mac']
        except OSError:
            continue
    return result


def learning():
    try:
        return json.loads(LEARNING.read_text())['until'] > time.monotonic()
    except (OSError, ValueError, KeyError, TypeError):
        return False


def learned_tokens():
    try:
        data = json.loads(LEARNING.read_text())
        tokens = data.get('tokens', []) + ([data['learned']] if isinstance(data.get('learned'), dict) else [])
        return [token for token in tokens if isinstance(token, dict) and
                isinstance(token.get('until'), (int, float)) and token['until'] > time.monotonic()][-256:]
    except (OSError, ValueError, TypeError, AttributeError):
        return []


def learn(mac):
    import fcntl
    with open('/run/lock/vbot-bluetooth-key-learning.lock', 'a') as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise ValueError('Đang có một lượt học nút khác')
        paths = [path for path, address in inputs(connected()).items() if address == mac]
        if not paths:
            raise ValueError('Chưa thấy input Bluetooth riêng của thiết bị; hãy kết nối lại. Thiết bị có thể không gửi nút về Pi.')
        handles = []
        tokens = learned_tokens()
        completed = False
        try:
            atomic(LEARNING, {'until': time.monotonic() + 17, 'mac': mac, 'tokens': tokens})
            for path in paths:
                handles.append(open(path, 'rb', buffering=0))
            deadline = time.monotonic() + 15
            while time.monotonic() < deadline:
                ready, _, _ = select.select(handles, [], [], max(0, min(0.25, deadline-time.monotonic())))
                for handle in ready:
                    raw = handle.read(EVENT.size)
                    if len(raw) != EVENT.size:
                        raise ValueError('Thiết bị đã ngắt kết nối')
                    _, _, kind, code, value = EVENT.unpack(raw)
                    if kind == 1 and value == 1:
                        # A learned key is the physical press, not an inferred hold/double press.
                        token = {'mac': mac, 'code': code, 'until': time.monotonic()+180}
                        tokens = [old for old in tokens if (old.get('mac'), old.get('code')) != (mac, code)]
                        atomic(LEARNING, {'until': time.monotonic()+0.6, 'mac': mac, 'tokens': (tokens+[token])[-256:]})
                        completed = True
                        return {'mac': mac, 'code': code}
            raise ValueError('Hết 15 giây nhưng không nhận được nút. Chưa lưu thay đổi.')
        finally:
            for handle in handles:
                handle.close()
            if not completed:
                # End failed learning without invalidating other browser windows.
                try:
                    atomic(LEARNING, {'until': 0, 'tokens': tokens})
                except (OSError, ValueError):
                    pass


def save(mac, code, name, action, remove=False, enabled=True, previous_code=0):
    if not re.fullmatch(r'(?:[0-9A-F]{2}:){5}[0-9A-F]{2}', mac) or not 1 <= code <= 767:
        raise ValueError('Định danh nút không hợp lệ')
    data = load()
    old = next((b for b in data['buttons'] if b['mac'] == mac and b['code'] == code), None)
    if not remove:
        if not isinstance(name, str) or not 1 <= len(name.strip()) <= 80:
            raise ValueError('Tên nút phải có từ 1 đến 80 ký tự')
        validate_action(action)
        if old is None:
            if not any(token.get('mac') == mac and token.get('code') == code for token in learned_tokens()):
                raise ValueError('Hãy học nút trước khi lưu (kết quả học có hiệu lực 3 phút)')
    if previous_code and not any(b['mac'] == mac and b['code'] == previous_code for b in data['buttons']):
        raise ValueError('Nút cũ đã thay đổi hoặc bị xóa; hãy tải lại danh sách')
    buttons = [b for b in data['buttons'] if not (b['mac'] == mac and b['code'] in (code, previous_code))]
    if not remove:
        if len(buttons) >= 256:
            raise ValueError('Đã đạt giới hạn 256 nút')
        buttons.append({'mac': mac, 'code': code, 'name': name.strip(), 'action': action, 'enabled': enabled})
    if STATE.exists():
        atomic(STATE.with_suffix('.json.bak'), data)
    atomic(STATE, {'version': 1, 'buttons': buttons})
    return {'buttons': buttons}


def find_binding(bindings, key):
    return next((b for b in bindings if b.get('enabled', True) and (b['mac'], b['code']) == key), None)


class Monitor:
    def __init__(self, execute, log):
        self.execute, self.log = execute, log
        self.stop_event = threading.Event()
        self.thread = threading.Thread(target=self.run, name='BluetoothKeys', daemon=True)
        self.action_lock = threading.Lock()

    def dispatch(self, binding):
        identity = binding.get('device_name', 'Thiết bị Bluetooth')+' ('+binding['mac']+')'
        try:
            if self.stop_event.is_set():
                return
            self.log('[Bluetooth Key] '+identity+' | Nút '+binding['name']+' → '+binding['action'])
            if self.execute(binding['action']) is False:
                self.log('[Bluetooth Key] '+identity+' | Không thực hiện được nút '+binding['name'])
        except Exception as error:
            self.log('[Bluetooth Key] '+identity+' | Nút '+binding['name']+': '+str(error))
        finally:
            self.action_lock.release()

    def run(self):
        handles, mapping, last, refreshed, names, owners = {}, {}, {}, 0, {}, {}
        try:
            while not self.stop_event.is_set():
                if time.monotonic()-refreshed > 2:
                    refreshed = time.monotonic()
                    try:
                        bindings = load()['buttons']
                        wanted = {b['mac'] for b in bindings if b.get('enabled', True)}
                        devices = connected() if wanted else []
                        names = {device['mac']: device['name'] for device in devices}
                        mapping = {p: mac for p, mac in inputs(devices).items() if mac in wanted}
                        for path in list(handles):
                            if path not in mapping or owners.get(path) != mapping[path]:
                                handles.pop(path).close()
                        for path in mapping:
                            if path not in handles:
                                handles[path] = open(path, 'rb', buffering=0)
                                owners[path] = mapping[path]
                    except Exception as error:
                        bindings, mapping = [], {}
                        for handle in handles.values():
                            handle.close()
                        handles.clear()
                        self.log('[Bluetooth Key] '+str(error))
                        self.stop_event.wait(5)
                if not handles:
                    self.stop_event.wait(0.25)
                    continue
                try:
                    ready, _, _ = select.select(list(handles.values()), [], [], 0.25)
                except OSError:
                    for handle in handles.values():
                        handle.close()
                    handles.clear()
                    continue
                for handle in ready:
                    path = next(p for p, stream in handles.items() if stream is handle)
                    try:
                        raw = handle.read(EVENT.size)
                        if len(raw) != EVENT.size:
                            raise OSError('Bluetooth input disconnected')
                        sec, usec, kind, code, value = EVENT.unpack(raw)
                    except OSError:
                        handles.pop(path).close()
                        continue
                    key = (mapping.get(path), code)
                    now = time.monotonic()
                    if self.stop_event.is_set() or kind != 1 or value != 1 or learning():
                        continue
                    # Discard queued presses from a learning window / busy action.
                    if time.time()-(sec+usec/1000000) > 0.5 or now-last.get(key, 0) < 0.35:
                        continue
                    last[key] = now
                    binding = find_binding(bindings, key)
                    if binding and self.action_lock.acquire(blocking=False):
                        binding = {**binding, 'device_name': names.get(binding['mac'], 'Thiết bị Bluetooth')}
                        threading.Thread(target=self.dispatch, args=(binding,), name='BluetoothKeyAction', daemon=True).start()
        finally:
            for handle in handles.values():
                handle.close()

    def start(self):
        self.thread.start()

    def stop(self):
        self.stop_event.set()
        if self.thread.ident is not None:
            self.thread.join(timeout=2)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('operation', choices=['list', 'learn', 'save', 'delete'])
    parser.add_argument('--mac', default='')
    parser.add_argument('--code', type=int, default=0)
    parser.add_argument('--name', default='')
    parser.add_argument('--action', default='none')
    parser.add_argument('--enabled', choices=['0', '1'], default='1')
    parser.add_argument('--previous-code', type=int, default=0)
    args = parser.parse_args()
    try:
        if args.operation == 'list':
            data = load()
        elif args.operation == 'learn':
            data = learn(args.mac.upper())
        else:
            import fcntl
            with open('/run/lock/vbot-bluetooth-key-save.lock', 'a') as lock:
                fcntl.flock(lock, fcntl.LOCK_EX)
                data = save(args.mac.upper(), args.code, args.name, args.action, args.operation == 'delete', args.enabled == '1', args.previous_code)
        print(json.dumps({'success': True, **data}, ensure_ascii=False))
    except Exception as error:
        print(json.dumps({'success': False, 'message': str(error)}, ensure_ascii=False))
        return 1
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
