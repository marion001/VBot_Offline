#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

Device info + HMAC signature utility
- Xuất thông tin: mac_address, hostname, device_model, machine_id, serial_number, hmac_key
- Sinh HMAC signature cho challenge: --sign "challenge_string"
"""

import json
import hashlib
import hmac
import socket
import sys
from pathlib import Path
import psutil
import platform
import uuid

try:
    import machineid
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "missing module: pip install machineid"
    }, ensure_ascii=False))
    sys.exit(1)

#lấy Địa Chỉ IP Local
def get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"

#Lấy địa chỉ MAC (chuẩn hóa dạng xx:xx:xx:xx:xx:xx)
def get_mac_address() -> str:
    try:
        mac = next(
            (
                addr.address.replace("-", ":").lower()
                for iface, addrs in psutil.net_if_addrs().items()
                for addr in addrs
                if getattr(addr, "family", None) == socket.AF_PACKET
                and addr.address
                and addr.address != "00:00:00:00:00:00"
            ),
            "00:00:00:00:00:00",
        )
        return mac
    except Exception as e:
        return "00:00:00:00:00:00"

#Đọc file Version.json và trả về giá trị releaseDate.
def get_release_date(file_path="VBot_Offline/Version.json"):
    try:
        with open(file_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        release_date = data.get("releaseDate")
        if release_date:
            return release_date
        else:
            return "N/A"
    except FileNotFoundError:
        return "N/A"
    except json.JSONDecodeError:
        return "N/A"

#Tạo Serial thiết bị từ địa chỉ MAC
def generate_serial_number(mac_address: str) -> str:
    mac_clean = mac_address.replace(":", "")
    short_hash = hashlib.md5(mac_clean.encode()).hexdigest()[:8].upper()
    return f"SN-{short_hash}-{mac_clean}"

#Tạo UUID Định Danh Thiết Bị
def get_machine_id() -> str:
    try:
        raw_id = machineid.id()
        machine_uuid = str(uuid.UUID(hex=raw_id))
    except Exception:
        machine_uuid = str(uuid.uuid4())
    return machine_uuid

#Tạo KEY HMAC từ hostname, mac_address, machine_id
def generate_hmac_key(hostname: str, mac_address: str, machine_id: str) -> str:
    fingerprint = "||".join([hostname, mac_address, machine_id])
    return hashlib.sha256(fingerprint.encode()).hexdigest()

#Lấy tên thiết bị hoặc board mạch
def get_device_model() -> str:
    model_path = Path("/proc/device-tree/model")
    if model_path.is_file():
        try:
            device_name = model_path.read_text().strip("\x00").strip()
        except Exception:
            device_name = "VBot_XiaoZhi"
    else:
        device_name = "VBot_XiaoZhi"
    return device_name or f"{platform.system()} {platform.machine()}"

#Sinh signature HMAC-SHA256
def sign_challenge(hmac_key: str, challenge: str) -> str:
    return hmac.new(hmac_key.encode(), challenge.encode(), hashlib.sha256).hexdigest()

def main():
    try:
        mac = get_mac_address()
        get_ip_address = get_local_ip()
        hostname = socket.gethostname()
        machine_id = get_machine_id()
        model = get_device_model()
        serial = generate_serial_number(mac)
        hmac_key = generate_hmac_key(hostname, mac, machine_id)
        version_program = get_release_date()

        #Nếu có --sign "challenge"
        if len(sys.argv) >= 3 and sys.argv[1] == "--sign":
            challenge = sys.argv[2]
            signature = sign_challenge(hmac_key, challenge)
            print(json.dumps({
                "success": True,
                "mode": "signature_hmac",
                "challenge": challenge,
                "signature": signature,
                "serial_number": serial,
                "mac_address": mac,
                "machine_id": machine_id
            }, ensure_ascii=False, indent=4))
            return

        #Ngược lại: trả thông tin thiết bị
        data = {
            "success": True,
            "mode": "get_device_info",
            "ip_address": get_ip_address,
            "mac_address": mac,
            "hostname": hostname,
            "device_model": model,
            "machine_id": machine_id,
            "serial_number": serial,
            "hmac_key": hmac_key,
            "version_program": version_program
        }
        print(json.dumps(data, ensure_ascii=False, indent=4))

    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e)
        }, ensure_ascii=False))

if __name__ == "__main__":
    main()
