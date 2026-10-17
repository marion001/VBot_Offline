"""Apply the reviewed mic update on a Pi; no system audio routing changes."""
import datetime
import importlib.util
import json
import os
from pathlib import Path
import py_compile
import shutil
import subprocess
import zipfile

def main():
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument('--root', required=True)
    parser.add_argument('--archive', required=True)
    args = parser.parse_args()
    root = Path(args.root).resolve()
    for process in Path('/proc').glob('[0-9]*'):
        try:
            cmd = (process / 'cmdline').read_bytes().split(b'\0')
            if any(Path(x.decode()).name in ('Start.py', 'VBot.py') for x in cmd if x):
                if (process / 'cwd').resolve() == root:
                    raise RuntimeError('Stop VBot before applying the microphone update')
        except (OSError, UnicodeError):
            continue
    backup = Path('/home/pi/VBot_AEC3/integration_backups') / datetime.datetime.now().strftime('%Y%m%d_%H%M%S_webrtc_mic')
    stage = backup / 'stage'
    stage.mkdir(parents=True)
    with zipfile.ZipFile(args.archive) as archive:
        for member in archive.namelist():
            target = (stage / member).resolve()
            if not target.is_relative_to(stage.resolve()):
                raise ValueError('Unsafe archive member')
        archive.extractall(stage)
    files = [p for p in stage.rglob('*') if p.is_file() and p.suffix in ('.py', '.php', '.md', '.cpp')]
    for path in files:
        if path.suffix == '.py':
            py_compile.compile(str(path), doraise=True)
        elif path.suffix == '.php':
            subprocess.run(['php', '-l', str(path)], check=True)
    library = Path('/home/pi/VBot_AEC3/music_test/libvbot_aec3_music.so')
    native = library.with_name('libvbot_aec3_unified.so.new')
    if not native.is_file():
        raise RuntimeError('Build and validate the new native library first')
    spec = importlib.util.spec_from_file_location('mic_migration', stage / 'resource/vbot_aec3/migrate_config.py')
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    config_path = root / 'Config.json'
    config = module.migrate(json.loads(config_path.read_text(encoding='utf-8')))
    manifest = []
    try:
        for path in files:
            relative = path.relative_to(stage)
            target = root / relative
            saved = backup / 'files' / relative
            existed = target.exists()
            saved.parent.mkdir(parents=True, exist_ok=True)
            if existed:
                shutil.copy2(target, saved)
            manifest.append(dict(target=str(target), saved=str(saved), existed=existed))
        for target in (config_path, library):
            saved = backup / target.name
            shutil.copy2(target, saved)
            manifest.append(dict(target=str(target), saved=str(saved), existed=True))
        (backup / 'manifest.json').write_text(json.dumps(manifest, indent=2))
        for path in files:
            target = root / path.relative_to(stage)
            target.parent.mkdir(parents=True, exist_ok=True)
            temp = target.with_name(target.name + '.webrtc.new')
            shutil.copy2(path, temp)
            temp.replace(target)
        temp = config_path.with_name('Config.json.webrtc.new')
        temp.write_text(json.dumps(config, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')
        temp.chmod(config_path.stat().st_mode)
        temp.replace(config_path)
        temp = library.with_name(library.name + '.webrtc.new')
        shutil.copy2(native, temp)
        temp.replace(library)
    except Exception:
        for item in reversed(manifest):
            target = Path(item['target'])
            if item['existed']:
                shutil.copy2(item['saved'], target)
            elif target.exists():
                target.unlink()
        raise
    print('Applied WebRTC MIC update. Backup:', backup)
    print('Mic ID, wake-in-media setting, LAN calls and system ALSA routes preserved.')

if __name__ == '__main__':
    main()
