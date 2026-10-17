"""Replace a compiled VBot tree with source while retaining the whole old tree."""
import argparse
import ast
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import uuid
import zipfile


def merge_missing(old, defaults):
    result=dict(old)
    for key,value in defaults.items():
        if key not in result:result[key]=value
        elif isinstance(result[key],dict) and isinstance(value,dict):
            result[key]=merge_missing(result[key],value)
    return result


def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('archive',type=Path)
    parser.add_argument('--schema',type=Path,required=True)
    parser.add_argument('--root',type=Path,default=Path('/home/pi/VBot_Offline'))
    args=parser.parse_args()
    root=args.root.absolute()
    if root!=Path('/home/pi/VBot_Offline') or root.is_symlink():
        raise RuntimeError('Unexpected source replacement target')
    if not (root/'Config.json').is_file():raise RuntimeError('Missing device Config.json')
    if subprocess.run(['pgrep','-f',r'python[0-9.]*[[:space:]]+([^[:space:]]*/)?(Start|VBot)\.py([[:space:]]|$)'],capture_output=True).returncode!=1:
        raise RuntimeError('Stop VBot before source replacement')
    stamp='20261014_'+uuid.uuid4().hex[:8]
    stage=root.with_name('VBot_Offline_source_stage_'+stamp)
    backup=root.with_name('VBot_Offline_so_backup_'+stamp)
    if stage.exists() or backup.exists():raise RuntimeError('Backup/stage collision')
    def ignore(folder,names):
        return [n for n in names if n=='__pycache__' or n.endswith(('.pyc','.pyo'))
                or (Path(folder)==root and n.endswith('.so'))]
    shutil.copytree(root,stage,ignore=ignore)
    with zipfile.ZipFile(args.archive) as z:
        manifest=json.loads(z.read('update_manifest.json'))
        for name,digest in manifest['files'].items():
            relative=Path(name)
            if relative.is_absolute() or '..' in relative.parts or name=='Config.json':
                raise RuntimeError('Invalid source path')
            data=z.read(name)
            if hashlib.sha256(data).hexdigest()!=digest:raise RuntimeError('Invalid checksum: '+name)
            if name.endswith('.py') and '/' not in name:ast.parse(data,filename=name)
            target=stage/relative
            if stage not in target.resolve().parents:raise RuntimeError('Source symlink escapes staging')
            target.parent.mkdir(parents=True,exist_ok=True)
            target.write_bytes(data);target.chmod(0o777)
    config=json.loads((root/'Config.json').read_text(encoding='utf-8-sig'))
    config=merge_missing(config,json.loads(args.schema.read_text(encoding='utf-8')))
    spec=importlib.util.spec_from_file_location('_mic_migration',stage/'resource/vbot_aec3/migrate_config.py')
    migration=importlib.util.module_from_spec(spec);spec.loader.exec_module(migration)
    config=migration.migrate(config)
    ap=config['smart_config']['audio_processing']
    ap.update(active=True,noise_suppression='low',high_pass_filter=True)
    ap['agc']=dict(active=True,target_level_dbfs=6,compression_gain_db=9,limiter=True)
    ap['aec3'].update(active=True,library='/home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so',
                     output='normal',capture_gain=.3,wake_gain=1,render_clock_ppm=980,
                     capture_delay_ms=32,playback_latency_ms=100,playback_device='default',external_sources=False)
    # The shared media bus has not been installed on this speaker; local VLC
    # uses direct callbacks. No /etc/asound.conf or system service changes here.
    (stage/'Config.json').write_text(json.dumps(config,ensure_ascii=False,indent=4)+'\n',encoding='utf-8')
    (stage/'Config.json').chmod((root/'Config.json').stat().st_mode & 0o777)
    for file in ('html/Config.php','html/includes/LANCalls.php'):
        subprocess.run(['php','-l',str(stage/file)],check=True,stdout=subprocess.DEVNULL)
    root.rename(backup)
    try:stage.rename(root)
    except Exception:
        backup.rename(root);raise
    print(json.dumps({'root':str(root),'backup':str(backup),'root_compiled_modules':len(list(root.glob('*.so'))),
                      'source_files':len(list(root.glob('*.py')))},ensure_ascii=False))


if __name__=='__main__':main()
