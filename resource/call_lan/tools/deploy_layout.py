"""Install LAN WebUI files and consolidate legacy deployment tools, preserving peers."""
import ast
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import uuid
import zipfile

root=Path('/home/pi/VBot_Offline')
backup=Path('/home/pi/VBot_AEC3/integration_backups')/('lan_layout_'+uuid.uuid4().hex[:8])
legacy=root/'resource/lan_pjsip'
tools=root/'resource/call_lan/tools'
if root.resolve()!=root or legacy.is_symlink() or tools.is_symlink():raise RuntimeError('Unexpected project path')
peers=root/'resource/call_lan/Devices.json'
peer_bytes=peers.read_bytes() if peers.exists() else None
config_bytes=(root/'Config.json').read_bytes()
with zipfile.ZipFile(sys.argv[1]) as archive,tempfile.TemporaryDirectory(prefix='lan-layout-') as folder:
    manifest=json.loads(archive.read('manifest.json'));stage=Path(folder)
    for name,digest in manifest.items():
        relative=Path(name)
        if relative.is_absolute() or '..' in relative.parts or name in ('Config.json','resource/call_lan/Devices.json'):
            raise RuntimeError('Patch must not include device settings')
        if root not in (root/relative).resolve().parents:raise RuntimeError('Target escapes project')
        data=archive.read(name)
        if hashlib.sha256(data).hexdigest()!=digest:raise RuntimeError('Checksum mismatch')
        target=stage/relative;target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(data)
        if name.endswith('.py'):ast.parse(data,filename=name)
        if name.endswith('.php'):subprocess.run(['php','-l',str(target)],check=True)
    backup.mkdir(parents=True)
    if legacy.exists():
        if legacy.resolve()!=legacy:raise RuntimeError('Legacy directory escapes project')
        for source in legacy.rglob('*'):
            if source.is_symlink():raise RuntimeError('Unexpected symlink in legacy tools')
            if not source.is_file() or '__pycache__' in source.parts or source.suffix in ('.pyc','.pyo'):continue
            target=tools/source.relative_to(legacy)
            if root not in target.resolve().parents:raise RuntimeError('Tools path escapes project')
            if not target.exists() and str(target.relative_to(root)) not in manifest:
                target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(source,target)
    for name in manifest:
        target=root/name
        if target.exists():
            saved=backup/name;saved.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(target,saved)
        target.parent.mkdir(parents=True,exist_ok=True)
        temporary=target.with_name(target.name+'.lan-layout-'+uuid.uuid4().hex[:8])
        temporary.write_bytes((stage/name).read_bytes());temporary.chmod(0o777);os.replace(temporary,target)
    for name,digest in manifest.items():
        if hashlib.sha256((root/name).read_bytes()).hexdigest()!=digest:raise RuntimeError('Installed checksum mismatch')
    if legacy.exists():shutil.move(str(legacy),str(backup/'legacy_lan_pjsip'))
    if (peers.read_bytes() if peers.exists() else None)!=peer_bytes or (root/'Config.json').read_bytes()!=config_bytes:
        raise RuntimeError('Device data changed during deployment; verify current settings')
    print('LAN_LAYOUT_UPDATED',len(manifest),'PEERS_AND_CONFIG_PRESERVED','BACKUP',backup)
