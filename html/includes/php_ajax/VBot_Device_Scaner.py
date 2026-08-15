import ipaddress
import json
import re
import subprocess
from concurrent.futures import ThreadPoolExecutor, as_completed

import requests


NMAP_TIMEOUT_SECONDS = 30
HTTP_TIMEOUT_SECONDS = 1.5
MAX_SCAN_ADDRESSES = 4096
MAX_WORKERS = 32


def emit(success, message, data=None):
    print(json.dumps({
        "success": bool(success),
        "message": str(message),
        "data": data if isinstance(data, list) else [],
    }, ensure_ascii=False))


def get_primary_network():
    """Return the network used by the default IPv4 route."""
    route_result = subprocess.run(
        ["ip", "-j", "-4", "route", "get", "1.1.1.1"],
        capture_output=True, text=True, check=True, timeout=5,
    )
    routes = json.loads(route_result.stdout or "[]")
    if not routes:
        raise RuntimeError("Không tìm thấy route IPv4 mặc định")
    interface = routes[0].get("dev")
    source_ip = routes[0].get("prefsrc") or routes[0].get("src")
    if not interface:
        raise RuntimeError("Không xác định được interface mạng mặc định")

    address_result = subprocess.run(
        ["ip", "-j", "-4", "addr", "show", "dev", interface],
        capture_output=True, text=True, check=True, timeout=5,
    )
    interfaces = json.loads(address_result.stdout or "[]")
    addresses = [
        item for info in interfaces for item in info.get("addr_info", [])
        if item.get("family") == "inet" and item.get("scope") == "global"
    ]
    selected = next((item for item in addresses if item.get("local") == source_ip), None)
    selected = selected or (addresses[0] if addresses else None)
    if not selected:
        raise RuntimeError(f"Interface {interface} không có địa chỉ IPv4 LAN")

    source_ip = selected["local"]
    prefix_length = int(selected.get("prefixlen", 24))
    network = ipaddress.IPv4Network(f"{source_ip}/{prefix_length}", strict=False)
    # Không để một cấu hình /8 hoặc /16 vô tình tạo lượt quét quá lớn từ WebUI.
    if network.num_addresses > MAX_SCAN_ADDRESSES:
        network = ipaddress.IPv4Network(f"{source_ip}/24", strict=False)
    return interface, source_ip, network


def scan_active_ips(network):
    result = subprocess.run(
        [
            "nmap", "-sn", "-n", "--max-retries", "1",
            "--host-timeout", "2s", "-oG", "-", str(network),
        ],
        capture_output=True, text=True, check=False,
        timeout=NMAP_TIMEOUT_SECONDS,
    )
    if result.returncode != 0:
        detail = (result.stderr or result.stdout or "nmap thất bại").strip()
        raise RuntimeError(detail[:500])
    active_ips = []
    for line in result.stdout.splitlines():
        match = re.match(r"Host:\s+(\d+\.\d+\.\d+\.\d+).*Status:\s+Up", line)
        if match:
            active_ips.append(match.group(1))
    return active_ips


def check_device(ip):
    try:
        response = requests.get(
            f"http://{ip}/VBot_API.php",
            timeout=HTTP_TIMEOUT_SECONDS,
            allow_redirects=False,
            headers={"Accept": "application/json"},
        )
        if response.status_code != 200:
            return None
        payload = response.json()
        if not isinstance(payload, dict) or payload.get("success") is not True:
            return None
        port_api = int(payload.get("port_api"))
        if not 1 <= port_api <= 65535:
            return None
        host_name = str(payload.get("host_name") or "").strip()[:255]
        user_name = str(payload.get("user_name") or "").strip()[:255]
        if not host_name or not user_name:
            return None
        # IP nguồn quét là dữ liệu đáng tin cậy hơn trường do thiết bị trả về.
        return {
            "ip_address": ip,
            "port_api": port_api,
            "host_name": host_name,
            "user_name": user_name,
        }
    except (requests.RequestException, ValueError, TypeError, KeyError):
        return None


def scan_and_check_devices():
    try:
        interface, source_ip, network = get_primary_network()
        active_ips = scan_active_ips(network)
        found_devices = []
        with ThreadPoolExecutor(max_workers=min(MAX_WORKERS, max(1, len(active_ips)))) as executor:
            futures = {executor.submit(check_device, ip): ip for ip in active_ips}
            for future in as_completed(futures):
                try:
                    device = future.result()
                except Exception:
                    device = None
                if device:
                    found_devices.append(device)
        found_devices.sort(key=lambda item: ipaddress.IPv4Address(item["ip_address"]))
        message = (
            f"Tìm thấy {len(found_devices)} thiết bị VBot qua {interface} ({network})"
            if found_devices else
            f"Không tìm thấy thiết bị VBot qua {interface} ({source_ip}, mạng {network})"
        )
        # Quét hoàn tất vẫn là success; data rỗng không phải lỗi thực thi.
        emit(True, message, found_devices)
    except FileNotFoundError as error:
        missing = getattr(error, "filename", None) or "ip/nmap"
        emit(False, f"Thiếu chương trình hệ thống: {missing}")
    except subprocess.TimeoutExpired:
        emit(False, f"Quá thời gian quét mạng ({NMAP_TIMEOUT_SECONDS} giây)")
    except Exception as error:
        emit(False, f"Không thể quét thiết bị VBot: {error}")


if __name__ == "__main__":
    scan_and_check_devices()
