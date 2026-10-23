#!/usr/bin/env python3
"""Repair installed BLE startup without replacing private Wi-Fi/crypto logic."""
import argparse
import ast
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time
import uuid

DROP_IN = '''[Unit]
Wants=bluetooth.service
After=dbus.service hciuart.service bluetooth.service
StartLimitIntervalSec=120
StartLimitBurst=4

[Service]
Restart=on-failure
RestartSec=5
'''


def patched_startup(installed, source):
    """Replace only two methods; retain all installed network and crypto code."""
    def methods(text):
        result = {}
        for cls in ast.parse(text).body:
            if isinstance(cls, ast.ClassDef) and cls.name in ('Blue', 'BLEManager'):
                wanted = 'set_adapter' if cls.name == 'Blue' else 'start'
                for node in cls.body:
                    if isinstance(node, ast.FunctionDef) and node.name == wanted:
                        result[(cls.name, wanted)] = node
        return result
    old, new = methods(installed), methods(source)
    required = {('Blue', 'set_adapter'), ('BLEManager', 'start')}
    if set(old) != required or set(new) != required:
        raise RuntimeError('Unsupported BLE source: startup methods not found')
    lines, replacement = installed.splitlines(keepends=True), source.splitlines(keepends=True)
    for key, node in sorted(old.items(), key=lambda item: item[1].lineno, reverse=True):
        revised = new[key]
        lines[node.lineno - 1:node.end_lineno] = replacement[revised.lineno - 1:revised.end_lineno]
    output = ''.join(lines)
    # Startup references this helper; older installed releases do not have it.
    if not any(isinstance(n, ast.ClassDef) and n.name == 'BLEBondRecovery'
               for n in ast.parse(output).body):
        recovery = next(n for n in ast.parse(source).body
                        if isinstance(n, ast.ClassDef) and n.name == 'BLEBondRecovery')
        output = ''.join(replacement[recovery.lineno - 1:recovery.end_lineno]) + '\n\n' + output
    ast.parse(output)
    return output


def write_atomic(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    previous = path.stat() if path.exists() else None
    fd, temporary = tempfile.mkstemp(dir=str(path.parent))
    try:
        with os.fdopen(fd, 'w', encoding='utf-8') as stream:
            stream.write(data)
        os.chmod(temporary, previous.st_mode & 0o777 if previous else 0o644)
        if previous:
            os.chown(temporary, previous.st_uid, previous.st_gid)
        os.replace(temporary, path)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root', type=Path, default=Path(__file__).resolve().parents[2])
    parser.add_argument('--installed-dir', type=Path, default=Path('/usr/local/btwifiset'))
    parser.add_argument('--apply', action='store_true')
    parser.add_argument('--sync-source', action='store_true', help='Copy the complete project source after backing up the installed file')
    parser.add_argument('--restart', action='store_true', help='Restart only btwifiset.service')
    args = parser.parse_args()
    if args.sync_source and args.installed_dir == Path('/usr/local/btwifiset'):
        command = ['python3', '-c', "import sys; sys.path.insert(0, sys.argv[sys.argv.index('--root')+1]); import Sync_System_Services as sync; sys.exit(sync.main())",
                   '--root', str(args.root), '--only', 'ble']
        if args.apply:
            command.append('--apply')
        if args.restart:
            command.append('--install')
        else:
            command.append('--no-restart')
        subprocess.run(command, check=True, timeout=90)
        return
    target = args.installed_dir / 'btwifiset.py'
    source = args.root / 'resource/set_wifi_via_ble/btwifiset.py'
    installed = target.read_text(encoding='utf-8')
    source_text = source.read_text(encoding='utf-8')
    ast.parse(source_text)
    revised = source_text if args.sync_source else patched_startup(installed, source_text)
    override = Path('/etc/systemd/system/btwifiset.service.d/20-vbot-ble-startup.conf')
    changes = revised != installed or not override.exists() or override.read_text() != DROP_IN
    if not args.apply:
        print(json.dumps({'changes_needed': changes, 'target': str(target), 'apply_required': True}))
        return
    if os.geteuid() != 0:
        raise PermissionError('Run with sudo to repair the installed BLE service')
    backup = Path('/home/pi/VBot_AEC3/integration_backups') / ('ble_startup_' + time.strftime('%Y%m%d_%H%M%S') + '_' + uuid.uuid4().hex[:6])
    backup.mkdir(parents=True)
    shutil.copy2(target, backup / 'btwifiset.py')
    for name in ('crypto', 'infopi.json'):
        path = args.installed_dir / name
        if path.is_file():
            shutil.copy2(path, backup / name)
    unit = Path('/etc/systemd/system/btwifiset.service')
    if unit.is_file():
        shutil.copy2(unit, backup / unit.name)
    if override.exists():
        shutil.copy2(override, backup / override.name)
    (backup / 'restore_info.json').write_text(json.dumps({'target': str(target), 'override': str(override), 'override_existed': override.exists()}))
    if revised != installed:
        write_atomic(target, revised)
    write_atomic(override, DROP_IN)
    subprocess.run(['systemctl', 'daemon-reload'], check=True, timeout=15)
    if args.restart:
        subprocess.run(['systemctl', 'restart', 'btwifiset.service'], check=True, timeout=30)
    print(json.dumps({'changed': changes, 'backup': str(backup), 'restarted': args.restart}))


if __name__ == '__main__':
    main()
