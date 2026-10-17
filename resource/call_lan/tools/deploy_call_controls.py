"""Deploy the call controls patch without restarting VBot or replacing device settings."""
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
backup=Path('/home/pi/VBot_AEC3/integration_backups')/('call_controls_'+uuid.uuid4().hex[:8])
with zipfile.ZipFile(sys.argv[1]) as archive, tempfile.TemporaryDirectory(prefix='call-controls-') as folder:
    manifest=json.loads(archive.read('manifest.json'))
    stage=Path(folder)
    for name,digest in manifest.items():
        relative=Path(name)
        if relative.is_absolute() or '..' in relative.parts or name=='Config.json':
            raise RuntimeError('Invalid patch path')
        data=archive.read(name)
        if hashlib.sha256(data).hexdigest()!=digest:raise RuntimeError('Checksum mismatch')
        target=stage/relative;target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(data)
        if name.endswith('.py'):ast.parse(data,filename=name)
        if name.endswith('.php'):subprocess.run(['php','-l',str(target)],check=True)
        if root not in (root/relative).resolve().parents:raise RuntimeError('Target escapes source root')
    backup.mkdir(parents=True)
    for name in manifest:
        target=root/name
        if target.exists():
            saved=backup/name;saved.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(target,saved)
        target.parent.mkdir(parents=True,exist_ok=True)
        temporary=target.with_name(target.name+'.call-controls-'+uuid.uuid4().hex[:8])
        temporary.write_bytes((stage/name).read_bytes());temporary.chmod(0o777)
        os.replace(temporary,target)
    for name,digest in manifest.items():
        if hashlib.sha256((root/name).read_bytes()).hexdigest()!=digest:raise RuntimeError('Deployment mismatch')
    print('CALL_CONTROLS_UPDATED',len(manifest),'BACKUP',backup)
