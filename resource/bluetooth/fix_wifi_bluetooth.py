#!/usr/bin/env python3
"""Backed-up Zero 2 W coexistence fix. Never reload Wi-Fi from WebUI."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import time

FIRMWARE = Path('/lib/firmware/brcm')
BACKUPS = Path('/home/pi/VBot_AEC3/bluetooth_coexistence_backups')
MODEL = Path('/proc/device-tree/model')
VALUES = {'btc_mode': '1', 'btc_params8': '0x4e20', 'btc_params1': '0x7530'}
NAMES = {'1': 'brcmfmac43436s-sdio', '2': 'brcmfmac43436-sdio'}


def classify(model, logs):
    if 'Raspberry Pi Zero 2 W' not in model:
        return None
    matches = re.findall(r'using brcm/(brcmfmac43436s?-sdio) for chip BCM43430/([12])', logs)
    if not matches:
        return None
    name, revision = matches[-1]
    return name if NAMES[revision] == name else None


def detect():
    model = MODEL.read_text().rstrip('\x00') if MODEL.exists() else ''
    logs = ''
    for command in (['dmesg'], ['journalctl', '-k', '-b', '--no-pager']):
        result = subprocess.run(command, capture_output=True, text=True, timeout=15)
        if result.returncode == 0:
            logs += '\n' + result.stdout
        if classify(model, logs):
            break
    name = classify(model, logs)
    path = FIRMWARE / (name + '.raspberrypi,model-zero-2-w.txt') if name else None
    if path and not path.exists():
        path = FIRMWARE / (name + '.txt')
    supported = bool(path and path.is_file())
    return {'model': model, 'supported': supported, 'firmware': str(path) if supported else '',
            'message': 'Đã nhận diện Zero 2 W và chip BCM43430 phù hợp.' if supported else
            'Bỏ qua: chưa xác định được đúng model/chip/firmware hỗ trợ; không sửa cấu hình.'}


def configured(data):
    found = {}
    counts = {}
    for line in data.splitlines():
        match = re.match(r'^\s*(btc_mode|btc_params8|btc_params1)\s*=\s*([^#\s]+)', line)
        if match:
            found[match[1]] = match[2]
            counts[match[1]] = counts.get(match[1], 0) + 1
    return found == VALUES and all(count == 1 for count in counts.values())


def snapshot(label):
    BACKUPS.mkdir(parents=True, exist_ok=True)
    backup = BACKUPS / (label + '_' + time.strftime('%Y%m%d_%H%M%S') + '_' + str(os.getpid()) + '_' + str(time.monotonic_ns()))
    backup.mkdir(mode=0o700)
    return backup


def replace(path, data, mode):
    info = path.stat()
    fd, temporary = tempfile.mkstemp(dir=str(path.parent))
    try:
        with os.fdopen(fd, 'wb') as stream:
            stream.write(data)
            stream.flush()
            os.fsync(stream.fileno())
        os.chmod(temporary, mode)
        if hasattr(os, 'chown'):
            os.chown(temporary, info.st_uid, info.st_gid)
        os.replace(temporary, path)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def apply():
    info = detect()
    if not info['supported']:
        return info
    alias = Path(info['firmware'])
    target = alias.resolve(strict=True)
    if target.parent != FIRMWARE.resolve() or target.name not in {name + '.txt' for name in NAMES.values()}:
        raise RuntimeError('Đường firmware không hợp lệ; không sửa')
    old = target.read_bytes()
    if configured(old.decode()):
        return dict(info, changed=False, message='Cấu hình Wi-Fi/Bluetooth đã phù hợp; không ghi lại firmware. Nếu vừa cài trước đó, reboot để nạp cấu hình.')
    backup = snapshot('coexistence')
    shutil.copy2(target, backup / 'firmware.txt')
    manifest = {'version': 1, 'target': target.name, 'model': info['model'], 'sha256': hashlib.sha256(old).hexdigest(), 'mode': target.stat().st_mode & 0o777}
    (backup / 'manifest.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding='utf-8')
    lines = [line for line in old.decode().splitlines() if not re.match(r'^\s*(btc_mode|btc_params8|btc_params1)\s*=', line)]
    new = ('\n'.join(lines) + '\n\n# VBot Wi-Fi/Bluetooth coexistence\n' + ''.join(key + '=' + value + '\n' for key, value in VALUES.items())).encode()
    try:
        replace(target, new, manifest['mode'])
        if target.read_bytes() != new:
            raise RuntimeError('Không xác minh được firmware sau ghi')
    except Exception:
        replace(target, old, manifest['mode'])
        raise
    return dict(info, changed=True, backup=str(backup), reboot_required=True,
                message='Đã sao lưu và sửa Wi-Fi/Bluetooth. Cần reboot để áp dụng; chưa ngắt Wi-Fi. Backup: ' + str(backup))


def restore(value):
    backup = Path(value).resolve(strict=True)
    if backup.parent != BACKUPS.resolve() or backup.is_symlink():
        raise ValueError('Chỉ khôi phục từ thư mục backup của bộ sửa này')
    for name in ('manifest.json', 'firmware.txt'):
        if (backup / name).is_symlink():
            raise ValueError('Backup không được là symlink')
    manifest = json.loads((backup / 'manifest.json').read_text(encoding='utf-8'))
    if manifest.get('target') not in {name + '.txt' for name in NAMES.values()}:
        raise ValueError('Manifest firmware không hợp lệ')
    detected = detect()
    if not detected['supported'] or Path(detected['firmware']).resolve().name != manifest['target']:
        raise ValueError('Backup không khớp chip/firmware của thiết bị hiện tại')
    data = (backup / 'firmware.txt').read_bytes()
    if hashlib.sha256(data).hexdigest() != manifest.get('sha256'):
        raise ValueError('Checksum backup không khớp; không khôi phục')
    target = (FIRMWARE / manifest['target']).resolve(strict=True)
    if target.parent != FIRMWARE.resolve():
        raise ValueError('Đường firmware không hợp lệ')
    undo = snapshot('before_restore')
    shutil.copy2(target, undo / 'firmware.txt')
    undo_manifest = dict(manifest, sha256=hashlib.sha256(target.read_bytes()).hexdigest(), mode=target.stat().st_mode & 0o777)
    (undo / 'manifest.json').write_text(json.dumps(undo_manifest, ensure_ascii=False, indent=2), encoding='utf-8')
    replace(target, data, int(manifest['mode']))
    return {'message': 'Đã khôi phục firmware. Cần reboot để áp dụng. Bản trước khôi phục: ' + str(undo), 'reboot_required': True}


def backup_bluetooth():
    backup = snapshot('bluetooth_defaults')
    for name in ['/usr/local/bin/bluetooth_agent.py', '/usr/bin/bthelper', '/etc/systemd/system/bluealsa.service',
                 '/etc/systemd/system/vbot-bluetooth-agent.service', '/etc/systemd/system/bluealsa-aplay.service',
                 '/etc/bluetooth/main.conf', '/etc/systemd/system/bluealsa.service.d']:
        source = Path(name)
        if source.exists():
            destination = backup / name.lstrip('/')
            destination.parent.mkdir(parents=True, exist_ok=True)
            if source.is_dir():
                shutil.copytree(source, destination, symlinks=True)
            else:
                shutil.copy2(source, destination)
    states = {}
    for unit in ('bluealsa.service', 'vbot-bluetooth-agent.service', 'bluealsa-aplay.service'):
        states[unit] = {state: subprocess.run(['systemctl', state, unit], capture_output=True, text=True, timeout=5).stdout.strip()
                        for state in ('is-active', 'is-enabled')}
    (backup / 'service_states.json').write_text(json.dumps(states, indent=2), encoding='utf-8')
    return {'message': 'Đã sao lưu cấu hình Bluetooth trước khi sửa: ' + str(backup), 'backup': str(backup)}


def main():
    parser = argparse.ArgumentParser()
    actions = parser.add_mutually_exclusive_group()
    actions.add_argument('--check', action='store_true')
    actions.add_argument('--apply', action='store_true')
    actions.add_argument('--restore', metavar='BACKUP')
    actions.add_argument('--backup-bluetooth', action='store_true')
    args = parser.parse_args()
    try:
        if not (args.apply or args.restore or args.backup_bluetooth):
            result = detect()
            if result['supported']:
                result['configured'] = configured(Path(result['firmware']).read_text())
        else:
            if os.geteuid() != 0:
                raise RuntimeError('Chạy bằng sudo để sao lưu và sửa firmware')
            import fcntl
            with open('/run/lock/vbot-wifi-bluetooth.lock', 'a') as lock:
                fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
                result = restore(args.restore) if args.restore else backup_bluetooth() if args.backup_bluetooth else apply()
        print(json.dumps({'success': True, **result}, ensure_ascii=False))
        return 0
    except Exception as error:
        print(json.dumps({'success': False, 'message': str(error)}, ensure_ascii=False))
        return 1


if __name__ == '__main__':
    raise SystemExit(main())
