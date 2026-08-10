#!/usr/bin/env python3

import glob
import json
import os
import re
import shlex
import subprocess
import sys

try:
    sys.stdout.reconfigure(encoding="utf-8")
except (AttributeError, OSError):
    pass

def run(command):
    try:
        result = subprocess.run(command, capture_output=True, text=True, timeout=5, check=False)
        return result.stdout.strip()
    except Exception:
        return ""

def read_text(path):
    try:
        with open(path, "r", encoding="utf-8") as handle:
            return handle.read().strip()
    except OSError:
        return ""

def get_property(adapter, property_name):
    output = run([
        "busctl", "get-property", "org.bluez", f"/org/bluez/{adapter}",
        "org.bluez.Adapter1", property_name,
    ])
    if output.startswith("b "):
        return output.split(None, 1)[1].lower() == "true"
    return None

#Giải mã chuỗi byte UTF-8 dạng bát phân mà busctl dùng
def decode_busctl_string(value):
    def decode_octets(match):
        octets = re.findall(r"\\([0-7]{3})", match.group(0))
        try:
            return bytes(int(octet, 8) for octet in octets).decode("utf-8")
        except (ValueError, UnicodeDecodeError):
            return match.group(0)
    return re.sub(r"(?:\\[0-7]{3})+", decode_octets, value)

def get_string_property(adapter, property_name):
    output = run([
        "busctl", "get-property", "org.bluez", f"/org/bluez/{adapter}",
        "org.bluez.Adapter1", property_name,
    ])
    try:
        values = shlex.split(output)
        return decode_busctl_string(values[1].strip()) if len(values) > 1 and values[0] == "s" else ""
    except ValueError:
        return ""

def main():
    controller_names = {}
    default_addresses = set()
    for line in run(["bluetoothctl", "list"]).splitlines():
        match = re.match(r"^Controller\s+([0-9A-Fa-f:]{17})\s+(.+?)(?:\s+\[default\])?$", line.strip())
        if not match:
            continue
        address, name = match.groups()
        if line.rstrip().endswith("[default]"):
            default_addresses.add(address.upper())
            name = re.sub(r"\s+\[default\]$", "", name)
        controller_names[address.upper()] = name.strip()

    devices = []
    for path in sorted(glob.glob("/sys/class/bluetooth/hci*")):
        adapter = os.path.basename(path)
        if not re.fullmatch(r"hci\d+", adapter):
            continue
        address = read_text(os.path.join(path, "address")).upper()
        if not re.fullmatch(r"[0-9A-F]{2}(?::[0-9A-F]{2}){5}", address):
            address = get_string_property(adapter, "Address").upper()
        controller_name = controller_names.get(address) or get_string_property(adapter, "Alias") or "Bluetooth Controller"
        devices.append({
            "adapter": adapter,
            "address": address,
            "name": controller_name,
            "powered": get_property(adapter, "Powered"),
            "pairable": get_property(adapter, "Pairable"),
            "discoverable": get_property(adapter, "Discoverable"),
            "default": address in default_addresses,
        })

    print(json.dumps({
        "success": bool(devices),
        "message": f"Tìm thấy {len(devices)} Bluetooth adapter" if devices else "Không tìm thấy Bluetooth adapter nào",
        "devices": devices,
    }, ensure_ascii=False))


if __name__ == "__main__":
    main()
