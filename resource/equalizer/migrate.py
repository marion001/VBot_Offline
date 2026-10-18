"""Move legacy EQ gains out of Config.json without replacing saved presets."""
import datetime
import fcntl
import json
import math
import os
from pathlib import Path
import shutil
import tempfile

def write(path,value):
    with tempfile.NamedTemporaryFile(mode='w',encoding='utf-8',dir=path.parent,prefix='.eq-',delete=False) as stream:
        temporary=Path(stream.name)
        try:
            json.dump(value,stream,ensure_ascii=False,indent=4);stream.write('\n');stream.flush();os.fsync(stream.fileno())
            os.chmod(temporary,path.stat().st_mode&0o777 if path.exists() else 0o666)
            os.replace(temporary,path)
        finally:
            temporary.unlink(missing_ok=True)

def main():
    folder=Path(__file__).resolve().parent;root=folder.parents[1]
    config=root/'Config.json';data=folder/'settings.json'
    # Match PHP writer order: presets, then Config.
    with (folder/'settings.json.lock').open('a') as data_lock, (root/'Config.json.lock').open('a') as config_lock:
        for handle in (data_lock,config_lock):
            try:os.fchmod(handle.fileno(),0o666)
            except PermissionError:pass  # Existing WebUI-owned lock is already writable.
        fcntl.flock(data_lock,fcntl.LOCK_EX);fcntl.flock(config_lock,fcntl.LOCK_EX)
        c=json.loads(config.read_text(encoding='utf-8-sig'));legacy=c.get('media_player',{}).get('equalizer',{})
        state=json.loads(data.read_text(encoding='utf-8-sig')) if data.exists() else None
        if state is None or state.get('legacy_import_pending'):
            bands={name:legacy.get(name,0) for name in ('bass_db','low_mid_db','mid_db','high_mid_db','treble_db','preamp_db')}
            for key,value in bands.items():
                if type(value) not in (int,float) or not math.isfinite(value) or not (-12 if key=='preamp_db' else -12)<=value<=(0 if key=='preamp_db' else 12):raise ValueError('Invalid legacy EQ '+key)
            name='Cấu hình trước đây' if any(bands.values()) else 'Phẳng'
            state=dict(version=1,boot_id='',selected_preset='flat',default_preset='flat',current=bands,presets=[dict(id='flat',name=name,bands=bands)])
            write(data,state)
        previous=json.loads(data.read_text(encoding='utf-8-sig'))
        for bands in [state['current']]+[preset['bands'] for preset in state['presets']]:
            bands.setdefault('low_mid_db',0);bands.setdefault('high_mid_db',0);bands.setdefault('air_db',0);bands.setdefault('wet_percent',100)
            for key in ('bass_db','low_mid_db','mid_db','high_mid_db','treble_db','air_db','preamp_db','wet_percent'):
                value=bands[key]
                minimum,maximum=(0,100) if key=='wet_percent' else (-12,0 if key=='preamp_db' else 12)
                if type(value) not in (int,float) or not math.isfinite(value) or not minimum<=value<=maximum:raise ValueError('Invalid saved EQ '+key)
        if state!=previous:
            backup=Path('/home/pi/VBot_AEC3/eq_config_backups')/datetime.datetime.now().strftime('%Y%m%d_%H%M%S_%f')
            backup.mkdir(parents=True);shutil.copy2(data,backup/'settings.json');write(data,state)
        minimal={'active':legacy.get('active',False)}
        if legacy!=minimal:
            backup=Path('/home/pi/VBot_AEC3/eq_config_backups')/datetime.datetime.now().strftime('%Y%m%d_%H%M%S_%f')
            backup.mkdir(parents=True);shutil.copy2(config,backup/'Config.json')
            c.setdefault('media_player',{})['equalizer']=minimal;write(config,c)
    print('EQ presets ready; Config.json contains only the EQ switch')

if __name__=='__main__':main()
