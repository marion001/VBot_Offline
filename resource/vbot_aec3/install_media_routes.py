"""Install/restore the shared Bluetooth/AirPlay media route on an idle Pi.

Run sudo python3 ... --root /home/pi/VBot_Offline --apply.
Restoration: sudo python3 ... --root ... --restore /home/pi/VBot_AEC3/...backup...
Does not start VBot or change microphone/mixer settings.
"""
import argparse
import datetime
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys

BEGIN = '# BEGIN VBOT AEC3 MEDIA BUS'
END = '# END VBOT AEC3 MEDIA BUS'
BASE_SERVICES = ['bluealsa-aplay.service', 'shairport-sync.service', 'vbot-multiroom-bridge.service']
SERVICES = BASE_SERVICES + ['vbot-bluetooth-agent.service']


def configure_shairport(text):
    # Strip comments without changing indices; braces in comments are not syntax.
    token = re.compile(r'"(?:\\.|[^"\\])*"|//[^\n]*|/\*.*?\*/', re.S)
    masked = token.sub(lambda m: m.group() if m.group().startswith('"') else
                       ''.join('\n' if x == '\n' else ' ' for x in m.group()), text)
    matches = list(re.finditer(r'^\s*alsa\s*=\s*\{', masked, re.M))
    if len(matches) != 1:
        raise ValueError('Expected exactly one ALSA section in shairport config')
    start = matches[0].end()
    depth, end = 1, start
    in_string, escape = False, False
    for end in range(start, len(masked)):
        c = masked[end]
        if escape:
            escape = False
        elif in_string and c == '\\':
            escape = True
        elif c == '"':
            in_string = not in_string
        elif not in_string:
            depth += (c == '{') - (c == '}')
            if depth == 0:
                break
    if depth:
        raise ValueError('Unclosed Shairport ALSA section')
    body = text[start:end]
    # Force a stable bus format and software volume (already default on this Pi).
    body = re.sub(r'^\s*(output_device|output_rate|output_format|mixer_device|mixer_control_name|mixer_control_index)\s*=.*?;[^\n]*',
                  '', body, flags=re.M)
    body += '\n    output_device = "vbot_aec_media";\n    output_rate = 48000;\n    output_format = "S16";\n'
    return text[:start] + body + text[end:]


def run(*args):
    return subprocess.run(args, check=True, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)


def atomic_write(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    temp = path.with_name(path.name + '.aec-new')
    temp.write_bytes(data)
    if path.exists():
        info = path.stat()
        os.chmod(temp, info.st_mode)
        os.chown(temp, info.st_uid, info.st_gid)
    os.replace(temp, path)


def restore(backup):
    manifest = json.loads((backup / 'manifest.json').read_text())
    managed = list(manifest.get('services', BASE_SERVICES))
    # Older backups predate agent synchronization; restart a running agent so
    # its child reopens the PCM selected by the restored configuration.
    legacy_agent = ('vbot-bluetooth-agent.service' not in managed and
                    subprocess.run(['systemctl', 'is-active', '--quiet', 'vbot-bluetooth-agent.service']).returncode == 0)
    if legacy_agent:
        managed.append('vbot-bluetooth-agent.service')
    for service in managed:
        subprocess.run(['systemctl', 'stop', service], check=False)
    for item in manifest['files']:
        target = Path(item['target'])
        if item['existed']:
            shutil.copy2(backup / item['saved'], target)
            os.chown(target, item['uid'], item['gid'])
        else:
            target.unlink(missing_ok=True)
    run('systemctl', 'daemon-reload')
    for service in manifest['running']:
        run('systemctl', 'start', service)
    if legacy_agent:
        run('systemctl', 'start', 'vbot-bluetooth-agent.service')
    print('Restored routes from', backup)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root', required=True, type=Path)
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument('--apply', action='store_true')
    group.add_argument('--ensure', action='store_true', help='Install only when missing; leave a complete installation untouched')
    group.add_argument('--restore', type=Path)
    parser.add_argument('--startup-pid', type=int, default=0)
    args = parser.parse_args()
    if os.geteuid() != 0:
        parser.error('Run with sudo; this writes ALSA and service configuration')
    root = args.root.resolve()
    import fcntl
    lock = open('/run/lock/vbot-media-routes.lock', 'a')
    fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
    config_path = root / 'Config.json'
    cfg = json.loads(config_path.read_text())
    if args.ensure or args.apply:
        asound_text = Path('/etc/asound.conf').read_text() if Path('/etc/asound.conf').exists() else ''
        smart = cfg.get('smart_config', {})
        aec = smart.get('audio_processing', {}).get('aec3', smart.get('aec3', {}))
        media = cfg.get('media_player', {}).get('multiroom_audio', {})
        airplay = Path('/etc/shairport-sync.conf')
        override = Path('/etc/systemd/system/bluealsa-aplay.service.d/90-vbot-aec3.conf')
        complete = (BEGIN in asound_text and END in asound_text
                    and 'pcm.vbot_aec_media' in asound_text
                    and airplay.is_file()
                    and re.search(r'output_device\s*=\s*"vbot_aec_media"', airplay.read_text())
                    and override.is_file() and '--pcm=vbot_aec_media' in override.read_text())
        if complete:
            if (aec.get('external_sources') is not True
                    or media.get('vlc_loopback_output_device') != 'vbot_aec_media'
                    or media.get('vlc_loopback_capture_device') != 'plughw:Loopback,1,1'):
                backup = Path('/home/pi/VBot_AEC3/routing_backups') / datetime.datetime.now().strftime('%Y%m%d_%H%M%S_%f_config')
                backup.mkdir(parents=True)
                shutil.copy2(config_path, backup / 'Config.json')
                if 'audio_processing' in smart:
                    smart['audio_processing'].setdefault('aec3', {})['external_sources'] = True
                else:
                    smart.setdefault('aec3', {})['external_sources'] = True
                cfg.setdefault('smart_config', smart)
                cfg.setdefault('media_player', {}).setdefault('multiroom_audio', {}).update(
                    vlc_loopback_output_device='vbot_aec_media',
                    vlc_loopback_capture_device='plughw:Loopback,1,1')
                atomic_write(config_path, (json.dumps(cfg, ensure_ascii=False, indent=4) + '\n').encode())
                print('SHARED_MEDIA_CONFIG_REPAIRED BACKUP', backup)
            print('SHARED_MEDIA_ROUTES_READY - Đường âm thanh chung đã được cài đặt và bật cấu hình từ trước; không cần cài lại.')
            return
    # A startup invocation may ignore only its own not-yet-initialized launcher.
    ancestors = set()
    parent = os.getpid()
    for _ in range(12):
        try:
            parent = int(re.search(r'^PPid:\s+(\d+)', Path('/proc', str(parent), 'status').read_text(), re.M).group(1))
        except (OSError, AttributeError):
            break
        ancestors.add(parent)
        if parent <= 1:
            break
    if args.startup_pid and args.startup_pid not in ancestors:
        raise RuntimeError('startup-pid must identify the calling launcher')
    for proc in Path('/proc').glob('[0-9]*'):
        try:
            argv = (proc / 'cmdline').read_bytes().split(b'\0')
            if int(proc.name) == args.startup_pid:
                continue
            if any(Path(x.decode()).name in ('Start.py', 'VBot.py') for x in argv if x) and (proc / 'cwd').resolve() == root:
                raise RuntimeError('Stop VBot before changing media routes')
        except (OSError, UnicodeError):
            continue
    if args.restore:
        restore(args.restore.resolve())
        return
    if not any(p.read_text().strip() == 'Loopback' for p in Path('/proc/asound').glob('card*/id')):
        raise RuntimeError('snd-aloop Loopback card is required')
    config_path = root / 'Config.json'
    cfg = json.loads(config_path.read_text())
    smart = cfg.setdefault('smart_config', {})
    aec = smart.get('audio_processing', {}).get('aec3', smart.get('aec3', {}))
    if aec.get('external_sources'):
        raise RuntimeError('Cấu hình đã bật đường âm thanh chung nhưng thiết lập hệ thống chưa đầy đủ. Cần kiểm tra hoặc khôi phục từ bản sao lưu trước khi cài lại.')
    prefix = Path('/home/pi/VBot_AEC3')
    backup = prefix / 'routing_backups' / datetime.datetime.now().strftime('%Y%m%d_%H%M%S')
    bus = (Path(__file__).with_name('media_bus.conf')).read_text()
    asound = Path('/etc/asound.conf')
    original = asound.read_text() if asound.exists() else ''
    if BEGIN in original or END in original:
        raise RuntimeError('Existing media bus block needs restoration first')
    shairport = Path('/etc/shairport-sync.conf')
    override = Path('/etc/systemd/system/bluealsa-aplay.service.d/90-vbot-aec3.conf')
    if 'audio_processing' in smart:
        smart['audio_processing'].setdefault('aec3', {})['external_sources'] = True
    else:
        smart.setdefault('aec3', {})['external_sources'] = True
    cfg.setdefault('media_player', {}).setdefault('multiroom_audio', {}).update(
        vlc_loopback_output_device='vbot_aec_media', vlc_loopback_capture_device='plughw:Loopback,1,1')
    changes = {asound: (original + '\n' + BEGIN + '\n' + bus + END + '\n').encode(),
               shairport: configure_shairport(shairport.read_text()).encode(),
               override: b'[Service]\nExecStart=\nExecStart=/usr/bin/bluealsa-aplay -S --volume=software --pcm=vbot_aec_media --pcm-buffer-time=200000 --pcm-period-time=50000\n',
               config_path: (json.dumps(cfg, ensure_ascii=False, indent=4) + '\n').encode()}
    # The service executes a separate installed copy, not the project source.
    agent_source = root / 'resource/bluetooth/bluetooth_agent.py'
    installed_agent = Path('/usr/local/bin/bluetooth_agent.py')
    if not agent_source.is_file() or not installed_agent.is_file():
        raise RuntimeError('Install the VBot Bluetooth agent before shared media routing')
    compile(agent_source.read_text(encoding='utf-8-sig'), str(agent_source), 'exec')
    changes[installed_agent] = agent_source.read_bytes()
    manifest = dict(files=[], running=[], services=SERVICES)
    backup.mkdir(parents=True)
    for service in SERVICES:
        if subprocess.run(['systemctl', 'is-active', '--quiet', service]).returncode == 0:
            manifest['running'].append(service)
    for index, target in enumerate(changes):
        item = dict(target=str(target), existed=target.exists(), saved=str(index))
        if target.exists():
            info = target.stat()
            item.update(uid=info.st_uid, gid=info.st_gid)
            shutil.copy2(target, backup / str(index))
        manifest['files'].append(item)
    (backup / 'manifest.json').write_text(json.dumps(manifest, indent=2))
    try:
        for service in SERVICES:
            subprocess.run(['systemctl', 'stop', service], check=False)
        for path, data in changes.items():
            atomic_write(path, data)
        run('aplay', '-L')
        run('systemctl', 'daemon-reload')
        # The bridge is needed even when no Bluetooth/AirPlay client is connected.
        for service in ['vbot-multiroom-bridge.service', 'bluealsa-aplay.service', 'shairport-sync.service', 'vbot-bluetooth-agent.service']:
            run('systemctl', 'start', service)
            run('systemctl', 'is-active', '--quiet', service)
    except Exception:
        restore(backup)
        raise
    print('Shared media routes installed. Backup:', backup)
    print('Restart VBot after testing; no microphone or mixer settings changed.')


if __name__ == '__main__':
    main()
