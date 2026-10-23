"""Wi-Fi profile configuration and timed rollback, independent of VBot runtime."""
import argparse
import importlib.util
import ipaddress
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import time
import uuid as uuid_module

STATE = Path('/var/lib/vbot-network')
FIELDS = ('ipv4.method', 'ipv4.addresses', 'ipv4.gateway', 'ipv4.dns', 'ipv4.ignore-auto-dns', 'ipv6.dns', 'ipv6.ignore-auto-dns')


def run(*args, check=True, timeout=12):
    p = subprocess.run(list(args), capture_output=True, text=True, timeout=timeout,
                       env={**os.environ, 'LC_ALL': 'C.UTF-8'})
    if check and p.returncode:
        raise RuntimeError((p.stderr or p.stdout).strip()[:500])
    return p.stdout.strip()


def value(uuid, key):
    return run('nmcli', '-e', 'no', '-g', key, 'connection', 'show', 'uuid', uuid)


def validate_profile(uuid):
    if not re.fullmatch(r'[0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}', uuid):
        raise ValueError('UUID Wi-Fi không hợp lệ')
    if value(uuid, 'connection.type') not in ('802-11-wireless', 'wifi'):
        raise ValueError('Hồ sơ không phải Wi-Fi; không thay đổi PAN/Ethernet/VPN.')


def state_path(uuid):
    return STATE / (uuid + '.json')


def load(uuid):
    path = state_path(uuid)
    return json.loads(path.read_text()) if path.exists() else {}


def store(uuid, data):
    STATE.mkdir(parents=True, exist_ok=True, mode=0o700)
    tmp = state_path(uuid).with_suffix('.tmp')
    tmp.write_text(json.dumps(data), encoding='utf-8')
    tmp.chmod(0o600)
    tmp.replace(state_path(uuid))


def boot_id():
    path = Path('/proc/sys/kernel/random/boot_id')
    return path.read_text().strip() if path.exists() else ''


def modify(uuid, values):
    args = ['nmcli', 'connection', 'modify', 'uuid', uuid]
    for key, setting in values.items():
        args.extend([key, setting])
    run(*args)


def ipv4(address):
    return str(ipaddress.IPv4Address(address))


def settings(action, data):
    if action == 'set_static_ip':
        address = str(data.get('ip', ''))
        iface = ipaddress.IPv4Interface(address if '/' in address else address + '/24')
        gateway = ipaddress.IPv4Address(data.get('gateway', ''))
        if not 1 <= iface.network.prefixlen <= 30 or iface.ip.is_loopback or iface.ip.is_multicast or gateway == iface.ip or gateway not in iface.network or gateway in (iface.network.network_address, iface.network.broadcast_address) or iface.ip in (iface.network.network_address, iface.network.broadcast_address):
            raise ValueError('IP/Gateway không cùng subnet hoặc IP là địa chỉ mạng/broadcast.')
        return {'ipv4.method': 'manual', 'ipv4.addresses': str(iface), 'ipv4.gateway': str(gateway),
                'ipv4.dns': dns(data), 'ipv4.ignore-auto-dns': 'yes', 'ipv6.dns': '', 'ipv6.ignore-auto-dns': 'yes'}
    if action == 'use_dhcp_automatically':
        source = data.get('dns_source', 'google')
        if source not in ('google', 'router'):
            raise ValueError('Nguồn DNS không hợp lệ')
        return {'ipv4.method': 'auto', 'ipv4.addresses': '', 'ipv4.gateway': '',
                'ipv4.dns': '8.8.8.8,8.8.4.4' if source == 'google' else '',
                'ipv4.ignore-auto-dns': 'yes' if source == 'google' else 'no',
                'ipv6.dns': '2001:4860:4860::8888,2001:4860:4860::8844' if source == 'google' else '',
                'ipv6.ignore-auto-dns': 'yes' if source == 'google' else 'no'}
    if action == 'set_dns_only':
        return {'ipv4.dns': dns(data), 'ipv4.ignore-auto-dns': 'yes', 'ipv6.dns': '', 'ipv6.ignore-auto-dns': 'yes'}
    if action == 'reset_dns_dhcp':
        return {'ipv4.dns': '', 'ipv4.ignore-auto-dns': 'no', 'ipv6.dns': '', 'ipv6.ignore-auto-dns': 'no'}
    raise ValueError('Thao tác cấu hình không hợp lệ')


def dns(data):
    addresses = [ipv4(str(data.get('dns1', '')))]
    if data.get('dns2'):
        addresses.append(ipv4(str(data['dns2'])))
    return ','.join(addresses)


def apply_connection(uuid):
    device = value(uuid, 'GENERAL.DEVICES')
    if device and re.fullmatch(r'[A-Za-z0-9_.-]+', device):
        try:
            run('nmcli', '--wait', '15', 'device', 'reapply', device, timeout=20)
            return
        except RuntimeError:
            pass
    run('nmcli', '--wait', '25', 'connection', 'up', 'uuid', uuid, timeout=30)


def execute(action, uuid, data):
    validate_profile(uuid)
    previous = load(uuid)
    pending = previous.get('stage') in ('scheduled', 'applying', 'awaiting_confirm')
    if action in ('apply-worker', 'rollback', 'confirm'):
        if not data.get('token') or data['token'] != previous.get('token'):
            raise ValueError('Phiên áp dụng Wi-Fi không hợp lệ.')
    if action == 'transaction':
        result = {k: previous.get(k) for k in ('stage', 'token', 'deadline', 'error')}
        result['new_ip'] = previous.get('proposed', {}).get('ipv4.addresses', '').split('/')[0]
        return {'transaction': result}
    if action in ('connect_wifi', 'delete_wifi'):
        if pending:
            raise ValueError('Hãy xác nhận hoặc chờ khôi phục kết nối trước.')
        if action == 'delete_wifi':
            active = run('nmcli', '-t', '-f', 'UUID', 'connection', 'show', '--active').splitlines()
            if uuid in active:
                raise ValueError('Wi-Fi đang kết nối; không cho phép xóa.')
            run('nmcli', 'connection', 'delete', 'uuid', uuid)
            state_path(uuid).unlink(missing_ok=True)
            return {'message': 'Đã xóa hồ sơ Wi-Fi.'}
        run('nmcli', '--wait', '25', 'connection', 'up', 'uuid', uuid, timeout=30)
        return {'message': 'Đã kết nối hồ sơ Wi-Fi.'}
    if action == 'preferences':
        if pending:
            raise ValueError('Hãy xác nhận hoặc chờ khôi phục kết nối trước.')
        auto = data.get('autoconnect')
        priority = data.get('priority')
        if auto not in ('yes', 'no') or not re.fullmatch(r'-?[0-9]{1,3}', str(priority)) or not -999 <= int(priority) <= 999:
            raise ValueError('Tự kết nối/ưu tiên Wi-Fi không hợp lệ (-999 đến 999).')
        modify(uuid, {'connection.autoconnect': auto, 'connection.autoconnect-priority': str(int(priority))})
        return {'message': 'Đã lưu tự kết nối và mức ưu tiên Wi-Fi.'}
    if action in ('set_static_ip', 'use_dhcp_automatically', 'set_dns_only', 'reset_dns_dhcp'):
        if pending:
            raise ValueError('Hãy xác nhận hoặc chờ khôi phục kết nối trước.')
        new = settings(action, data)
        baseline = previous.get('baseline') if previous.get('stage') == 'saved' else {k: value(uuid, k) for k in FIELDS}
        # Saving a draft must not silently change the next boot's connection.
        proposed = previous.get('proposed', {}) if previous.get('stage') == 'saved' else {}
        proposed.update(new)
        store(uuid, {'baseline': baseline, 'proposed': proposed, 'stage': 'saved'})
        return {'message': 'Đã lưu cấu hình. Nhấn Áp dụng kết nối; không cần reboot.', 'new_ip': new.get('ipv4.addresses', '').split('/')[0]}
    if action == 'apply':
        if previous.get('stage') != 'saved':
            raise ValueError('Hãy lưu cấu hình trước khi áp dụng.')
        if not previous.get('proposed'):
            raise ValueError('Hãy lưu lại cấu hình trước khi áp dụng bản mới.')
        probe_module().ensure_wifi_guard()
        token = uuid_module.uuid4().hex
        previous.update(stage='scheduled', token=token, deadline=time.time() + 120,
                        boot_id=boot_id(), helper=str(Path(__file__).resolve()))
        store(uuid, previous)
        args = [sys.executable, str(Path(__file__).resolve())]
        try:
            run('systemd-run', '--quiet', '--unit=vbot-wifi-rollback-' + token, '--on-active=120',
                *args, 'rollback', '--uuid=' + uuid, '--token=' + token)
            run('systemd-run', '--quiet', '--unit=vbot-wifi-apply-' + token, '--on-active=3',
                *args, 'apply-worker', '--uuid=' + uuid, '--token=' + token)
        except Exception:
            previous['stage'] = 'saved'
            store(uuid, previous)
            raise
        return {'message': 'Sẽ áp dụng sau 3 giây. Mở lại trang và xác nhận trong 120 giây; nếu không, cấu hình cũ tự khôi phục.', 'token': token}
    if action == 'confirm':
        if previous.get('stage') != 'awaiting_confirm' or time.time() >= previous.get('deadline', 0):
            raise ValueError('Chưa áp dụng xong hoặc phiên đã hết hạn; hãy kiểm tra lại.')
        store(uuid, {'stage': 'confirmed'})
        run('systemctl', 'stop', 'vbot-wifi-rollback-' + data['token'] + '.timer', check=False)
        return {'message': 'Đã xác nhận và giữ cấu hình Wi-Fi mới.'}
    if action == 'rollback':
        if not pending:
            return {'message': 'Không cần khôi phục.'}
        modify(uuid, previous['baseline'])
        apply_connection(uuid)
        previous['stage'] = 'rolled_back'
        store(uuid, previous)
        return {'message': 'Đã khôi phục cấu hình Wi-Fi trước khi áp dụng.'}
    if action == 'apply-worker':
        if previous.get('stage') != 'scheduled':
            return {}
        previous['stage'] = 'applying'
        store(uuid, previous)
        try:
            modify(uuid, previous['proposed'])
            apply_connection(uuid)
            previous['stage'] = 'awaiting_confirm'
        except Exception as error:
            modify(uuid, previous['baseline'])
            apply_connection(uuid)
            previous.update(stage='rolled_back', error=str(error))
        store(uuid, previous)
        return {}
    raise ValueError('Thao tác không hợp lệ')


def probe_module():
    path = Path(__file__).resolve().parents[1] / 'bluetooth/pan_manager.py'
    spec = importlib.util.spec_from_file_location('vbot_network_probe', path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def probe_wifi():
    # Reuse the bound-interface diagnostic; PAN and Wi-Fi keep separate caches.
    module = probe_module()
    wifi = {line.split(':')[0] for line in run('nmcli', '-t', '-f', 'DEVICE,TYPE', 'device').splitlines() if line.endswith(':wifi')}
    routes = json.loads(run('ip', '-j', '-4', 'route', 'show', 'default'))
    route = next((r for r in routes if r.get('dev') in wifi and r.get('gateway')), None)
    if not route:
        raise ValueError('Wi-Fi chưa có gateway; không dùng PAN thay cho phép thử này.')
    result = module.probe_pan(route, STATE / 'wifi-probe.json')
    result['message'] = result['message'].replace('PAN', 'Wi-Fi')
    result['probe']['message'] = result['probe']['message'].replace('PAN', 'Wi-Fi')
    return result


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('action', choices=['connect_wifi', 'delete_wifi', 'set_static_ip', 'use_dhcp_automatically', 'set_dns_only', 'reset_dns_dhcp', 'preferences', 'apply', 'apply-worker', 'rollback', 'confirm', 'transaction', 'probe'])
    parser.add_argument('--uuid', default='')
    parser.add_argument('--token', default='')
    args = parser.parse_args()
    try:
        import fcntl
        STATE.mkdir(parents=True, exist_ok=True, mode=0o700)
        with open(STATE / 'lock', 'a') as lock:
            try:
                fcntl.flock(lock, fcntl.LOCK_EX if args.action in ('rollback', 'apply-worker') else fcntl.LOCK_EX | fcntl.LOCK_NB)
            except BlockingIOError:
                raise RuntimeError('Một thao tác mạng đang chạy; hãy thử lại sau.')
            data = json.loads(sys.stdin.read(8192) or '{}') if args.action not in ('apply-worker', 'rollback') else {}
            if not isinstance(data, dict):
                raise ValueError('Dữ liệu không hợp lệ')
            data['token'] = args.token or data.get('token', '')
            result = probe_wifi() if args.action == 'probe' else execute(args.action, args.uuid, data)
        print(json.dumps({'success': True, **result}, ensure_ascii=False))
    except Exception as error:
        print(json.dumps({'success': False, 'message': str(error)}, ensure_ascii=False))
        raise SystemExit(1)


if __name__ == '__main__':
    main()
