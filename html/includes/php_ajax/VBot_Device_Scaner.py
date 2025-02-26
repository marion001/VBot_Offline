#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx

import os
import requests
import ipaddress
import subprocess
import re
import json
from concurrent.futures import ThreadPoolExecutor

# Lấy địa chỉ IP của máy tính hiện tại
def read_info_startup(iface_name="wlan0"):
    try:
        ip_result = subprocess.run(['ip', 'addr', 'show', iface_name], capture_output=True, text=True)
        ip_output = ip_result.stdout
        ip_match = re.search(r'inet (\d+\.\d+\.\d+\.\d+)', ip_output)
        ip_address = ip_match.group(1) if ip_match else None
        return ip_address
    except Exception:
        return None

# Hàm kiểm tra kết nối bằng ping
def ping_ip(ip):
    try:
        response = os.popen(f"ping -c 1 -w 2 {ip}")
        result = response.read()
        if "1 packets transmitted, 1 received" in result:
            return True
        return False
    except Exception:
        return False

# Hàm kiểm tra dữ liệu từ API và lưu thông tin khi thành công
def check_device(ip):
    url = f"http://{ip}/VBot_API.php"
    try:
        response = requests.get(url, timeout=1)
        if response.status_code == 200:
            data = response.json()
            if data.get('success') is True:
                return {
                    "ip_address": data['ip_address'],
                    "port_api": data['port_api'],
                    "host_name": data['host_name'],
                    "user_name": data['user_name']
                }
        else:
            return {
                "ip_address": ip,
                "port_api": None,
                "host_name": None,
                "user_name": None
                }
    except requests.exceptions.RequestException:
        pass
    return None

# Hàm xử lý cho mỗi IP (kiểm tra ping và API)
def process_ip(ip):
    if ping_ip(str(ip)):
        return check_device(str(ip))
    return None

# Quét mạng LAN và kiểm tra thiết bị
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
    found_devices = []  # Danh sách thiết bị tìm thấy

    # Sử dụng ThreadPoolExecutor để tăng tốc độ kiểm tra các IP
    with ThreadPoolExecutor(max_workers=20) as executor:
        results = list(executor.map(process_ip, ip_network.hosts()))
        # Lọc ra các thiết bị hợp lệ (không phải None)
        found_devices = [result for result in results if result is not None]

    # Xử lý kết quả sau khi quét xong
    if found_devices:
        print(json.dumps({
            "success": True,
            "messager": "Tìm Kiếm Thiết Bị VBot Thành Công",
            "data": found_devices
        }, indent=4))
    else:
        print(json.dumps({
            "success": False,
            "messager": f"Không tìm thấy thiết bị đang chạy VBot nào trong cùng lớp mạng với: {ip_address}",
            "data": {}
        }, indent=4))

# Chạy chương trình
if __name__ == "__main__":
    scan_and_check_devices()
