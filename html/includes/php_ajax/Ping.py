#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

#!/usr/bin/env python3
import sys
import json
import subprocess
import platform
import concurrent.futures

#python3 1.py 192.168.14.175 192.168.14.113 192.168.14.8

RETRY_COUNT = 2
TIMEOUT_SEC = 1

def ping_once(ip):
    system = platform.system().lower()
    if system == "windows":
        cmd = ["ping", "-n", "1", "-w", "1000", ip]
    else:
        cmd = ["ping", "-c", "1", "-W", str(TIMEOUT_SEC), ip]
    result = subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    return result.returncode == 0

def ping_with_retry(ip):
    for _ in range(RETRY_COUNT):
        if ping_once(ip):
            return {"success": True, "message": "online"}
    return {"success": False, "message": "offline"}

def parse_ips(args):
    ips = []
    for arg in args:
        if "," in arg:
            ips.extend([ip.strip() for ip in arg.split(",") if ip.strip()])
        else:
            ips.append(arg.strip())
    return ips

if __name__ == "__main__":
    try:
        if len(sys.argv) < 2:
            print(json.dumps({"success": False, "message": "Không có IP nào được cung cấp", "data": {}}))
            sys.exit(1)
        ip_list = parse_ips(sys.argv[1:])
        results = {}
        with concurrent.futures.ThreadPoolExecutor(max_workers=20) as executor:
            future_to_ip = {executor.submit(ping_with_retry, ip): ip for ip in ip_list}
            for future in concurrent.futures.as_completed(future_to_ip):
                ip = future_to_ip[future]
                results[ip] = future.result()
        output = {"success": True, "data": results}
        print(json.dumps(output))
    except Exception as e:
        print(json.dumps({"success": False, "message": str(e), "data": {}}))
        sys.exit(1)