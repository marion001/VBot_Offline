"""Deploy the reviewed audio/Scheduler/WoL bundle; preserve device settings."""
import argparse
import ast
import ctypes
import fcntl
import hashlib
import json
import os
from pathlib import Path
import shutil
import stat
import subprocess
import tempfile
import uuid
import zipfile


def write(path, content, mode=0o777):
    path.parent.mkdir(parents=True, exist_ok=True)
    fd, temporary = tempfile.mkstemp(prefix='.vbot-update-', dir=str(path.parent))
    try:
        with os.fdopen(fd, 'wb') as handle:
            handle.write(content)
            handle.flush()
            os.fsync(handle.fileno())
        os.chmod(temporary, mode)
        os.replace(temporary, path)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('archive', type=Path)
    parser.add_argument('--root', type=Path, default=Path('/home/pi/VBot_Offline'))
    parser.add_argument('--activate-aec21', action='store_true')
    args = parser.parse_args()
    root = args.root.resolve()
    if not (root/'Config.json').is_file():
        raise RuntimeError('Không tìm thấy Config.json của VBot')
    running = subprocess.run(['pgrep', '-f', r'python[0-9.]*[[:space:]]+([^[:space:]]*/)?(Start|VBot)\.py([[:space:]]|$)'], capture_output=True, text=True)
    if running.returncode != 1:
        raise RuntimeError('Cần dừng VBot trước khi cập nhật các module âm thanh')
    library = Path('/home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so')
    if args.activate_aec21:
        lib = ctypes.CDLL(str(library))
        lib.vbot_apm_version.restype = ctypes.c_char_p
        if lib.vbot_apm_version() != b'2.1':
            raise RuntimeError('Ứng viên native không phải bản 2.1')
    with zipfile.ZipFile(args.archive) as archive, tempfile.TemporaryDirectory(prefix='vbot-review-') as stage_name:
        stage = Path(stage_name)
        manifest = json.loads(archive.read('update_manifest.json'))
        payload = {}
        for name, digest in manifest['files'].items():
            relative = Path(name)
            if relative.is_absolute() or '..' in relative.parts or name == 'Config.json' or '__pycache__' in relative.parts:
                raise RuntimeError('Đường dẫn cập nhật không hợp lệ')
            target = root/relative
            if root not in target.resolve().parents:
                raise RuntimeError('Tệp cập nhật nằm ngoài thư mục VBot')
            content = archive.read(name)
            if hashlib.sha256(content).hexdigest() != digest:
                raise RuntimeError('Checksum sai: '+name)
            staged = stage/relative
            staged.parent.mkdir(parents=True, exist_ok=True)
            staged.write_bytes(content)
            if name.endswith('.py'):
                ast.parse(content, filename=name)
            elif name.endswith('.php'):
                subprocess.run(['php', '-l', str(staged)], check=True, stdout=subprocess.DEVNULL)
            payload[name] = content
        backup = Path('/home/pi/VBot_AEC3/integration_backups')/('20261014_audio_scheduler_wol_'+uuid.uuid4().hex[:8])
        backup.mkdir(parents=True)
        changed = []
        with (root/'Config.json.lock').open('a+b') as lock:
            fcntl.flock(lock, fcntl.LOCK_EX)
            old_config = (root/'Config.json').read_bytes()
            write(backup/'Config.json', old_config, 0o600)
            try:
                for name, content in payload.items():
                    target = root/name
                    if name == 'resource/wake_on_lan/devices.json' and target.exists():
                        continue
                    previous = target.read_bytes() if target.exists() else None
                    mode = stat.S_IMODE(target.stat().st_mode) if target.exists() else 0o777
                    if previous is not None:
                        write(backup/name, previous, mode)
                    changed.append((name, previous, mode))
                    write(target, content)
                (root/'resource/wake_on_lan').chmod(0o777)
                if args.activate_aec21:
                    config = json.loads(old_config.decode('utf-8-sig'))
                    config['smart_config']['audio_processing']['aec3']['library'] = str(library)
                    write(root/'Config.json', (json.dumps(config, ensure_ascii=False, indent=4)+'\n').encode('utf-8'), stat.S_IMODE((root/'Config.json').stat().st_mode))
            except BaseException:
                for name, previous, mode in reversed(changed):
                    target = root/name
                    if previous is None:
                        target.unlink(missing_ok=True)
                    else:
                        write(target, previous, mode)
                write(root/'Config.json', old_config, stat.S_IMODE((root/'Config.json').stat().st_mode))
                raise
        result = dict(backup=str(backup), files=[row[0] for row in changed], aec21=args.activate_aec21)
        write(backup/'deployment.json', json.dumps(result, indent=2).encode('utf-8'), 0o600)
        print(json.dumps(result))


if __name__ == '__main__':
    main()
