#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx

import requests
import ipaddress
import json
import subprocess
import re
from concurrent.futures import ThreadPoolExecutor

try:
    import nmap
except ImportError:
    print(json.dumps({
        "success": False,
        "messager": "Thư viện 'python-nmap' không được cài đặt. Vui lòng cài đặt bằng 2 lệnh sau: 'pip install python-nmap' và 'sudo apt-get install nmap'",
        "data": {}
    }, indent=4))
    exit(1)

def read_info_startup(iface_name="wlan0"):
    """Lấy địa chỉ IP của interface mạng"""
    try:
        ip_result = subprocess.run(['ip', 'addr', 'show', iface_name], capture_output=True, text=True)
        ip_output = ip_result.stdout
        ip_match = re.search(r'inet (\d+\.\d+\.\d+\.\d+)', ip_output)
        return ip_match.group(1) if ip_match else None
    except Exception:
        return None

def check_device(ip):
    """Kiểm tra xem thiết bị có chạy VBot Client không"""
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

def scan_network(ip_address):
    """Quét mạng bằng nmap và kiểm tra thiết bị VBot Client"""
    nm = nmap.PortScanner()
    # Quét subnet /24 với ping scan (-sn) để tìm các host đang hoạt động
    network = ipaddress.IPv4Network(f"{ip_address}/24", strict=False)
    nm.scan(hosts=str(network), arguments='-sn')  # -sn: Ping scan, không quét port
    
    active_ips = [host for host in nm.all_hosts() if nm[host].state() == 'up']
    found_devices = []

    if active_ips:
        with ThreadPoolExecutor(max_workers=20) as executor:
            results = executor.map(check_device, active_ips)
            found_devices = [result for result in results if result is not None]

    return found_devices

def scan_and_check_devices():
    """Hàm chính để quét và kiểm tra thiết bị"""
    ip_address = read_info_startup()
    if ip_address is None:
        print(json.dumps({
            "success": False,
            "messager": "Không thể lấy địa chỉ IP hiện tại",
            "data": {}
        }, indent=4))
        return

    found_devices = scan_network(ip_address)

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