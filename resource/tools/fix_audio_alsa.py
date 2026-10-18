"""Run the VBot ALSA hardware repair manually, without starting VBot.

    sudo python3 resource/tools/fix_audio_alsa.py
    sudo python3 resource/tools/fix_audio_alsa.py --root /home/pi/VBot_Offline

Uses the image detector and templates shipped alongside this script. Preserves
user mixer levels and the shared media bus; backups are printed by the repair.
"""
import argparse
from pathlib import Path
import os
import subprocess
import sys


def vbot_running(root, proc=Path('/proc')):
    for process in proc.glob('[0-9]*'):
        try:
            args=(process/'cmdline').read_bytes().split(b'\0')
            if (process/'cwd').resolve()!=root:continue
            if any(Path(arg.decode()).name in ('Start.py','VBot.py') for arg in args if arg):return True
        except (OSError,UnicodeError):continue
    return False


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root',type=Path,default=Path(__file__).resolve().parents[2])
    parser.add_argument('--restore-default',action='store_true',help='Khôi phục mẫu mặc định đúng image, vẫn giữ media bus')
    args=parser.parse_args();root=args.root.resolve()
    if not sys.platform.startswith('linux') or os.geteuid()!=0:
        parser.error('Chạy trên loa bằng sudo python3')
    helper=root/'resource/vbot_aec3/hardware_routes.py'
    if not helper.is_file() or not (root/'Config.json').is_file():
        parser.error('Thiếu hardware_routes.py hoặc Config.json. Hãy đặt --root đúng thư mục VBot.')
    if vbot_running(root):
        parser.error('Hãy dừng VBot trước khi sửa ALSA; công cụ không tự dừng chương trình.')
    command=[sys.executable,str(helper),'--root',str(root)]
    if args.restore_default:command.append('--restore-default')
    subprocess.run(command,check=True)
    # Restart only the protected WM unit; never invoke the old destructive script.
    drop=Path('/etc/systemd/system/wm8960-soundcard.service.d/90-vbot-preserve-config.conf')
    if drop.is_file() and 'restore wm8960soundcard' in drop.read_text():
        subprocess.run(['systemctl','restart','wm8960-soundcard.service'],check=True)
    # Existing audio handles must reopen the repaired default PCM. Leave stopped
    # services stopped and do not restart Bluetooth or the whole VBot application.
    for name in ('vbot-multiroom-bridge.service','bluealsa-aplay.service','vbot-bluetooth-agent.service','shairport-sync.service'):
        if subprocess.run(['systemctl','is-active','--quiet',name]).returncode==0:
            subprocess.run(['systemctl','try-restart',name],check=True)
    subprocess.run(['amixer','info'],check=True)
    print('Đã kiểm tra/sửa ALSA và nạp lại các dịch vụ âm thanh đang chạy. Không khởi chạy VBot.')
    return 0


if __name__=='__main__':
    try:sys.exit(main())
    except (OSError,subprocess.CalledProcessError) as error:
        print('Không hoàn tất sửa ALSA: '+str(error),file=sys.stderr)
        sys.exit(1)
