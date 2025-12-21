'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import socket
import psutil
import os
import sys
import subprocess
from gtts import gTTS

INTERFACE = 'wlan0'
SOUND_DIR = "/home/pi/VBot_Offline/resource/sound/number"
TMP_DIR = "/tmp"
PLAYER_USER = "pi"

SOUND_MAP = {"zero": "zero.mp3", ".": "dot.mp3", "0": "0.mp3", "1": "1.mp3", "2": "2.mp3", "3": "3.mp3", "4": "4.mp3", "5": "5.mp3", "6": "6.mp3", "7": "7.mp3", "8": "8.mp3", "9": "9.mp3",}

def get_ip_addresses(interface):
    ips = []
    for iface, addrs in psutil.net_if_addrs().items():
        if iface == interface:
            for addr in addrs:
                if addr.family == socket.AF_INET:
                    ips.append(addr.address)
    return ips

def play_mp3(path):
    subprocess.run(["sudo", "-u", "pi", "cvlc", "--play-and-exit", path], check=True)

def build_ip_mp3(ip):
    output_mp3 = f"{TMP_DIR}/ip_{ip.replace('.', '_')}.mp3"
    tts_file = f"{TMP_DIR}/ip_gtts_{ip.replace('.', '_')}.mp3"
    list_file = f"{TMP_DIR}/ip_{ip.replace('.', '_')}.txt"
    if os.path.exists(output_mp3):
        return output_mp3
    if os.path.exists(tts_file):
        return tts_file
    try:
        files = [SOUND_MAP["zero"]]
        for c in ip:
            if c not in SOUND_MAP:
                raise ValueError(f"Ký tự không hợp lệ: {c}")
            files.append(SOUND_MAP[c])
        with open(list_file, "w") as f:
            for name in files:
                f.write(f"file '{os.path.join(SOUND_DIR, name)}'\n")
        result = subprocess.run(["ffmpeg", "-y", "-f", "concat", "-safe", "0", "-i", list_file, "-c:a", "mp3", "-ab", "128k", output_mp3], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
        if result.returncode == 0 and os.path.exists(output_mp3):
            return output_mp3
        raise RuntimeError(result.stderr.decode())
    except Exception as e:
        print("Ghép MP3 Offline lỗi, dùng gTTS:", e)
        try:
            tts_file = f"{TMP_DIR}/ip_gtts_{ip.replace('.', '_')}.mp3"
            tts = gTTS(text=f"Địa chỉ IP của bạn là {ip}", lang='vi')
            tts.save(tts_file)
            return tts_file
        except Exception:
            return None

def main():
    ips = get_ip_addresses(INTERFACE)
    if not ips or ips[0] == "127.0.0.1":
        print("Không thể lấy địa chỉ IP")
        try:
            tts = gTTS(text="Không thể lấy địa chỉ IP từ giao diện wifi", lang='vi')
            err_mp3 = f"{TMP_DIR}/ip_error.mp3"
            tts.save(err_mp3)
            play_mp3(err_mp3)
        except Exception:
            pass
        return
    ip = ips[0]
    print(f"Địa chỉ IP của bạn là: {ip}")
    mp3 = build_ip_mp3(ip)
    if mp3:
        play_mp3(mp3)
        play_mp3(mp3)
    else:
        print("Không thể phát âm thanh IP")
if __name__ == "__main__":
    main()
    sys.exit(0)