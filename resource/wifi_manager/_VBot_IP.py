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

def _valid_audio_file(path):
    try:
        return os.path.isfile(path) and os.path.getsize(path) > 64
    except OSError:
        return False

def play_mp3(path):
    cmd = ["cvlc", "--intf", "dummy", "--no-video", "--play-and-exit", path]
    if hasattr(os, "geteuid") and os.geteuid() == 0:
        cmd = ["sudo", "-u", PLAYER_USER] + cmd
    try:
        subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, check=False, timeout=20)
    except (OSError, subprocess.TimeoutExpired) as err:
        print("Không thể phát âm thanh IP:", err)

def build_ip_mp3(ip):
    uid_tag = str(os.geteuid()) if hasattr(os, "geteuid") else "u"
    safe_ip = ip.replace('.', '_')
    output_mp3 = f"{TMP_DIR}/ip_{uid_tag}_{safe_ip}.mp3"
    tts_file = f"{TMP_DIR}/ip_gtts_{uid_tag}_{safe_ip}.mp3"
    list_file = f"{TMP_DIR}/ip_{uid_tag}_{safe_ip}.txt"
    if _valid_audio_file(output_mp3):
        return output_mp3
    if _valid_audio_file(tts_file):
        return tts_file
    try:
        files = [SOUND_MAP["zero"]]
        for c in ip:
            if c not in SOUND_MAP:
                raise ValueError(f"Ký tự không hợp lệ: {c}")
            files.append(SOUND_MAP[c])
        with open(list_file, "w", encoding="utf-8") as f:
            for name in files:
                f.write(f"file '{os.path.join(SOUND_DIR, name)}'\n")
        tmp_out = output_mp3 + ".tmp"
        result = subprocess.run(
            ["ffmpeg", "-y", "-f", "concat", "-safe", "0", "-i", list_file, "-c:a", "mp3", "-ab", "128k", tmp_out],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.PIPE,
            timeout=15,
        )
        try:
            os.remove(list_file)
        except OSError:
            pass
        if result.returncode == 0 and _valid_audio_file(tmp_out):
            os.replace(tmp_out, output_mp3)
            return output_mp3
        try:
            os.remove(tmp_out)
        except OSError:
            pass
        raise RuntimeError(result.stderr.decode(errors="ignore"))
    except Exception as e:
        print("Ghép MP3 Offline lỗi, dùng gTTS:", e)
        try:
            tmp_tts = tts_file + ".tmp"
            tts = gTTS(text=f"Địa chỉ IP của bạn là {ip}", lang='vi', timeout=8)
            tts.save(tmp_tts)
            if _valid_audio_file(tmp_tts):
                os.replace(tmp_tts, tts_file)
                return tts_file
        except Exception:
            pass
        return None

def main():
    ips = get_ip_addresses(INTERFACE)
    if not ips or ips[0] == "127.0.0.1":
        print("Không thể lấy địa chỉ IP")
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