'''
Code By: Vũ Tuyển
Facebook: https://www.facebook.com/TWFyaW9uMDAx
'''
import socket
import psutil
from gtts import gTTS
import os
import sys
import subprocess

def get_ip_addresses(interface):
    ip_addresses = []
    for iface, addrs in psutil.net_if_addrs().items():
        if iface == interface:
            for addr in addrs:
                if addr.family == socket.AF_INET:
                    ip_addresses.append(addr.address)
    return ip_addresses

interface = 'wlan0'
ip_addresses = get_ip_addresses(interface)
audio_ip = "/tmp/ip_address.mp3"
#if ip_addresses and ip_addresses[0].startswith("192.168"):
if ip_addresses and ip_addresses[0] != "127.0.0.1":
    print(f"Địa chỉ IP của bạn là: {ip_addresses[0]}")
    tts = gTTS(text=f"Địa chỉ IP của bạn là {ip_addresses[0]}", lang='vi')
    tts.save(audio_ip)
    subprocess.run(["sudo", "-u", "pi", "cvlc", "--play-and-exit", "/tmp/ip_address.mp3"], check=True)
    subprocess.run(["sudo", "-u", "pi", "cvlc", "--play-and-exit", "/tmp/ip_address.mp3"], check=True)
else:
    print(f"Không thể lấy địa chỉ IP từ giao diện wifi")
    tts = gTTS(text="Không thể lấy địa chỉ IP từ giao diện wifi", lang='vi')
    tts.save(audio_ip)
    subprocess.run(["sudo", "-u", "pi", "cvlc", "--play-and-exit", "/tmp/ip_address.mp3"], check=True)
sys.exit(0)