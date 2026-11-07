#!/usr/bin/env python3
'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import glob
import json
import shutil
import subprocess
import sys
from pathlib import Path

VBOT_ROOT = Path(__file__).resolve().parents[2]
if str(VBOT_ROOT) not in sys.path:
    sys.path.insert(0, str(VBOT_ROOT))

import Internal_IR


def _select_receive_device(argv):
    """Resolve the LIRC node for learning before loading either Python or .so code."""
    if not argv or argv[0] != "learn":
        return None

    device_index = None
    preferred = "/dev/lirc1"
    for index, argument in enumerate(argv[1:], start=1):
        if argument == "--device" and index + 1 < len(argv):
            device_index = index + 1
            preferred = argv[device_index]
            break
        if argument.startswith("--device="):
            device_index = index
            preferred = argument.split("=", 1)[1]
            break

    ir_ctl = shutil.which("ir-ctl")
    if not ir_ctl:
        return "Không tìm thấy ir-ctl; hãy cài gói v4l-utils."

    candidates = [preferred]
    candidates.extend(path for path in sorted(glob.glob("/dev/lirc[0-9]*")) if path not in candidates)
    diagnostics = []
    for device in candidates:
        try:
            result = subprocess.run(
                [ir_ctl, "--features", "-d", device],
                text=True,
                capture_output=True,
                timeout=5,
            )
        except (OSError, subprocess.TimeoutExpired) as error:
            diagnostics.append(f"{device}: {error}")
            continue
        output = (result.stdout or "").lower()
        if result.returncode == 0 and "device can receive raw ir" in output:
            if device_index is None:
                argv.extend(["--device", device])
            elif argv[device_index].startswith("--device="):
                argv[device_index] = f"--device={device}"
            else:
                argv[device_index] = device
            return None
        detail = (result.stderr or result.stdout or "không có capability").strip()
        diagnostics.append(f"{device}: {detail}")

    return "Không tìm thấy thiết bị LIRC hỗ trợ nhận raw IR. Đã kiểm tra: " + "; ".join(diagnostics)

if __name__ == "__main__":
    receive_error = _select_receive_device(sys.argv[1:])
    if receive_error:
        print(json.dumps({"success": False, "message": receive_error}, ensure_ascii=False))
        sys.exit(1)
    sys.exit(Internal_IR.main())
