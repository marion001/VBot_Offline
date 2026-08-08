#!/usr/bin/env python3

import glob
import json
import os
import re
import subprocess
import sys

try:
    sys.stdout.reconfigure(encoding="utf-8")
except (AttributeError, OSError):
    pass


def run(command):
    try:
        result = subprocess.run(
            command,
            capture_output=True,
            text=True,
            timeout=5,
            check=False,
        )
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
        devices.append({
            "adapter": adapter,
            "address": address,
            "name": controller_names.get(address, "Bluetooth Controller"),
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
