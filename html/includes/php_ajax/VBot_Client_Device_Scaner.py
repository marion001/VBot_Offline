import os
import requests
import ipaddress
import subprocess
import re
import json
from concurrent.futures import ThreadPoolExecutor

def read_info_startup(iface_name="wlan0"):
    try:
        ip_result = subprocess.run(['ip', 'addr', 'show', iface_name], capture_output=True, text=True)
        ip_output = ip_result.stdout
        ip_match = re.search(r'inet (\d+\.\d+\.\d+\.\d+)', ip_output)
        return ip_match.group(1) if ip_match else None
    except Exception:
        return None

def ping_ip(ip):
    try:
        response = os.popen(f"ping -c 1 -w 2 {ip}")
        result = response.read()
        return "1 packets transmitted, 1 received" in result
    except Exception:
        return False

def check_device(ip):
    url = f"http://{ip}/VBot_Client_Info"
    try:
        response = requests.get(url, timeout=1)
        if response.status_code == 200:
            data = response.json()
            if data.get('success') is True:
                return data  # Trả về toàn bộ dữ liệu JSON
    except requests.exceptions.RequestException:
        pass
    return None

def process_ip(ip):
    if ping_ip(str(ip)):
        return check_device(str(ip))
    return None

def scan_and_check_devices():
    ip_address = read_info_startup()
    if ip_address is None:
        print(json.dumps({
            "success": False,
            "messager": "Không thể lấy địa chỉ IP hiện tại",
            "data": {}
        }, indent=4))
        return

    ip_network = ipaddress.IPv4Network(f"{ip_address}/24", strict=False)
    found_devices = []

    with ThreadPoolExecutor(max_workers=20) as executor:
        results = list(executor.map(process_ip, ip_network.hosts()))
        found_devices = [result for result in results if result is not None]

    if found_devices:
        print(json.dumps({
            "success": True,
            "messager": "Tìm Kiếm Thiết Bị VBot Client Thành Công",
            "data": found_devices
        }, indent=4, ensure_ascii=False))
    else:
        print(json.dumps({
            "success": False,
            "messager": f"Không tìm thấy thiết bị đang chạy VBot Client nào trong cùng lớp mạng với: {ip_address}",
            "data": {}
        }, indent=4, ensure_ascii=False))

if __name__ == "__main__":
    scan_and_check_devices()