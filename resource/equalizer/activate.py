"""Prepare the installed EQ runtime when enabled from Config.php.

Run through the speaker's existing SSH/sudo connection. Never stop the bridge
when EQ is disabled: AirPlay/Bluetooth still depend on it.
"""
import argparse
import ctypes
import os
from pathlib import Path
import subprocess
import sys


def run(*args):
    subprocess.run(list(args),check=True,timeout=45)


def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('--root',type=Path,required=True)
    args=parser.parse_args()
    if os.geteuid()!=0:parser.error('Requires sudo')
    root=args.root.resolve()
    library=root/'resource/equalizer/libvbot_eq.so'
    if not library.is_file():raise RuntimeError('EQ library is not installed: '+str(library))
    # Check compatibility before changing routing or starting services.
    lib=ctypes.CDLL(str(library))
    lib.vbot_eq_band_count.restype=ctypes.c_int
    if lib.vbot_eq_band_count()!=6 or not hasattr(lib,'vbot_eq_create6'):
        raise RuntimeError('EQ library must support six bands and Wet/Dry')
    unit=Path('/etc/systemd/system/vbot-multiroom-bridge.service')
    if not unit.exists():
        source=root/'resource/multiroom_audio/vbot-multiroom-bridge.service'
        text=source.read_text().replace('/home/pi/VBot_Offline',str(root))
        if any(c in str(root) for c in '\n\r\"%') or ' ' in str(root):
            raise RuntimeError('Unsupported service installation path')
        unit.write_text(text);unit.chmod(0o644)
        run('systemctl','daemon-reload')
    run(sys.executable,str(root/'resource/vbot_aec3/install_media_routes.py'),'--root',str(root),'--ensure')
    # Idempotent: does not restart a running bridge or interrupt its audio.
    run('systemctl','enable','--now','vbot-multiroom-bridge.service')
    run('systemctl','is-active','--quiet','vbot-multiroom-bridge.service')
    print('EQ_RUNTIME_READY')


if __name__=='__main__':main()
