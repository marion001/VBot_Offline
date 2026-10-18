"""Recognize VBot OS images and repair known legacy ALSA mappings safely."""
import argparse
import datetime
import json
import os
from pathlib import Path
import re
import shutil
import subprocess

BEGIN='# BEGIN VBOT AEC3 MEDIA BUS'
END='# END VBOT AEC3 MEDIA BUS'


def image_type(text):
    if not re.search(r'VBot\s+Assistant\s+OS\s+Image\s+Build:',text,re.I):
        raise ValueError('Không nhận diện được VBot OS image; giữ cấu hình ALSA hiện tại')
    return 'i2s' if 'i2s' in text.lower() else 'wm8960'


def split_bus(text):
    if text.count(BEGIN)!=text.count(END) or text.count(BEGIN)>1:
        raise ValueError('Khối media bus bị thiếu hoặc trùng lặp')
    if BEGIN not in text:return text,''
    start=text.index(BEGIN)
    if text.index(END)<start:raise ValueError('Thứ tự khối media bus không hợp lệ')
    end=text.index(END,start)+len(END)
    return text[:start]+text[end:],text[start:end]


def render(kind,original,template,reset=False):
    base,bus=split_bus(original)
    template_base,_=split_bus(template)
    # The new templates include a media bus. Hardware repair must preserve an
    # existing bus, while the media installer owns first-time bus activation.
    if reset or not base.strip():base=template_base
    if kind=='wm8960':
        def repair(match):
            value=match.group().replace('hw:0,0','hw:CARD=wm8960soundcard,DEV=0')
            if match.group(1)=='array':value=re.sub(r'\bchannels\s+1\b','channels 2',value)
            return value
        base=re.sub(r'(?ms)^pcm\.(dmixed|array)\s*\{.*?^\}',repair,base)
        if 'wm8960soundcard' not in base or 'hw:0,0' in base:
            raise ValueError('Cấu hình ALSA tùy chỉnh chưa xác định; không tự thay card')
        if 'ctl.!default' not in base:
            base+='\nctl.!default {\n    type hw\n    card "wm8960soundcard"\n}\n'
        else:
            base=re.sub(r'(?ms)(^ctl\.!default\s*\{.*?\bcard\s+)(?:"0"|0)(?=\s)',r'\1"wm8960soundcard"',base)
    elif 'wm8960soundcard' in base:
        raise ValueError('Image I2S nhưng ALSA đang chọn WM8960; cần kiểm tra phần cứng')
    return base.rstrip()+'\n'+(('\n'+bus+'\n') if bus else '')


def atomic(path,text):
    temp=path.with_name(path.name+'.hardware-new')
    temp.write_text(text,encoding='utf-8');temp.chmod(0o644);temp.replace(path)


def ensure(root,reset=False):
    marker=Path('/os_image_created.txt')
    kind=image_type(marker.read_text(encoding='utf-8-sig'))
    card='sndrpigooglevoi' if kind=='i2s' else 'wm8960soundcard'
    ids={p.read_text().strip() for p in Path('/proc/asound').glob('card*/id')}
    if card not in ids:raise ValueError('Image '+kind+' nhưng chưa thấy card '+card+'; không sửa ALSA')
    folder=root/'resource/asound_conf'
    template=folder/('default_'+kind+'_asound_new.conf')
    if not template.is_file():template=folder/('default_'+kind+'_asound.conf')
    target=Path('/etc/asound.conf');original=target.read_text(encoding='utf-8-sig') if target.exists() else ''
    updated=render(kind,original,template.read_text(encoding='utf-8-sig'),reset)
    config=json.loads((root/'Config.json').read_text(encoding='utf-8-sig'))
    smart=config.get('smart_config',{});aec=smart.get('audio_processing',{}).get('aec3',smart.get('aec3',{}))
    # Recover a media block erased by the old WM8960 boot service, only when
    # both external service routes still prove this was an installed media bus.
    if kind=='wm8960' and BEGIN not in updated and aec.get('external_sources') is True:
        airplay=Path('/etc/shairport-sync.conf');override=Path('/etc/systemd/system/bluealsa-aplay.service.d/90-vbot-aec3.conf')
        if (airplay.is_file() and re.search(r'output_device\s*=\s*"vbot_aec_media"',airplay.read_text())
                and override.is_file() and '--pcm=vbot_aec_media' in override.read_text()):
            updated+='\n'+BEGIN+'\n'+(root/'resource/vbot_aec3/media_bus.conf').read_text()+END+'\n'
    unit=Path('/lib/systemd/system/wm8960-soundcard.service')
    script=Path('/usr/bin/wm8960-soundcard')
    boot=Path('/boot/firmware/config.txt') if Path('/boot/firmware/config.txt').exists() else Path('/boot/config.txt')
    drop=Path('/etc/systemd/system/wm8960-soundcard.service.d/90-vbot-preserve-config.conf')
    state=Path('/var/lib/alsa/vbot-wm8960.state')
    expected='[Service]\n# WM8960 loaded by boot overlay; preserve ALSA routes.\nExecStart=\nExecStart=/usr/sbin/alsactl -f /var/lib/alsa/vbot-wm8960.state restore wm8960soundcard\n'
    protect=(kind=='wm8960' and unit.exists() and script.exists() and 'rm /etc/asound.conf' in script.read_text()
             and boot.exists() and re.search(r'^\s*dtoverlay=wm8960-soundcard\b',boot.read_text(),re.M))
    service_change=protect and (not drop.exists() or drop.read_text()!=expected or not state.exists())
    if updated==original and not service_change:
        print('AUDIO_HARDWARE_READY image='+kind+' card='+card);return
    backup=Path('/home/pi/VBot_AEC3/hardware_backups')/datetime.datetime.now().strftime('%Y%m%d_%H%M%S_%f');backup.mkdir(parents=True)
    for name,p in [('asound.conf',target),('wm8960-service.conf',drop),('wm8960.state',state)]:
        if p.exists():shutil.copy2(p,backup/name)
    (backup/'paths.json').write_text(json.dumps({'asound_symlink':str(target.readlink()) if target.is_symlink() else None},indent=2))
    if service_change:
        if not state.exists():subprocess.run(['alsactl','-f',str(backup/'mixer_current.state'),'store',card],check=True)
        drop.parent.mkdir(parents=True,exist_ok=True)
        state.parent.mkdir(parents=True,exist_ok=True)
        if not state.exists():shutil.copy2(backup/'mixer_current.state',state)
        atomic(drop,expected)
        subprocess.run(['systemctl','daemon-reload'],check=True)
    if updated!=original:atomic(target,updated)
    print('AUDIO_HARDWARE_REPAIRED image='+kind+' card='+card+' template='+template.name+' backup='+str(backup))


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root',type=Path,required=True)
    parser.add_argument('--restore-default',action='store_true')
    parser.add_argument('--routes-lock-held',action='store_true',help=argparse.SUPPRESS)
    args=parser.parse_args()
    if os.geteuid()!=0:parser.error('Cần chạy với sudo')
    import fcntl
    # The installer already owns the media lock. Standalone WebUI repairs must
    # acquire it too, so the two writers cannot overwrite each other's routes.
    routes_lock=None
    if not args.routes_lock_held:
        routes_lock=open('/run/lock/vbot-media-routes.lock','a')
        try:fcntl.flock(routes_lock,fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:parser.exit(1,'Đường âm thanh đang được cập nhật; vui lòng thử lại sau.\n')
    with open('/run/lock/vbot-audio-hardware.lock','a') as lock:
        fcntl.flock(lock,fcntl.LOCK_EX)
        try:ensure(args.root.resolve(),args.restore_default)
        except (OSError,ValueError,subprocess.CalledProcessError) as error:
            parser.exit(1,str(error)+'\n')
