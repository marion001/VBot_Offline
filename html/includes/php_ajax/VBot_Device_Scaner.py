#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

import subprocess
import re
import json
import requests
import ipaddress
from concurrent.futures import ThreadPoolExecutor

# Kiểm tra và import các thư viện cần thiết
try:
    import nmap
except ImportError:
    print(json.dumps({
        "success": False,
        "messager": "Thư viện 'python-nmap' không được cài đặt. Vui lòng cài đặt bằng 2 lệnh sau: 'pip install python-nmap' và 'sudo apt-get install nmap -y'",
        "data": {}
    }, indent=4))
    exit(1)

# Kiểm tra nmap binary
def check_nmap_installed():
    try:
        subprocess.run(['nmap', '--version'], capture_output=True, text=True, check=True)
        return True
    except (subprocess.CalledProcessError, FileNotFoundError):
        return False

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

# Quét mạng LAN và kiểm tra thiết bị bằng nmap
def scan_and_check_devices():
    if not check_nmap_installed():
        print(json.dumps({
            "success": False,
            "messager": "Chương trình 'nmap' không được cài đặt trên hệ thống. Vui lòng cài đặt bằng lệnh: 'sudo apt-get install nmap'",
            "data": {}
        }, indent=4))
        return

    ip_address = read_info_startup()
    if ip_address is None:
        print(json.dumps({
            "success": False,
            "messager": "Không thể lấy địa chỉ IP hiện tại",
            "data": {}
        }, indent=4))
        return
    
    # Khởi tạo nmap PortScanner
    try:
        nm = nmap.PortScanner()
    except Exception as e:
        print(json.dumps({
            "success": False,
            "messager": f"Lỗi khi khởi tạo nmap: {str(e)}",
            "data": {}
        }, indent=4))
        return

    ip_network = ipaddress.IPv4Network(f"{ip_address}/24", strict=False)
    found_devices = []

    # Quét chỉ các host (bỏ .0 và .255) để khớp với code cũ
    try:
        nm.scan(hosts=str(ip_network), arguments='-sn')  # Quét subnet, không bao gồm .0 và .255
    except Exception as e:
        print(json.dumps({
            "success": False,
            "messager": f"Lỗi khi quét mạng bằng nmap: {str(e)}",
            "data": {}
        }, indent=4))
        return

    active_ips = [host for host in nm.all_hosts() if nm[host].state() == 'up']

    if active_ips:
        with ThreadPoolExecutor(max_workers=20) as executor:
            results = list(executor.map(check_device, active_ips))
            found_devices = [result for result in results if result is not None]

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