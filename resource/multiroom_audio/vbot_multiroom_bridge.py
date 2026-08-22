#!/usr/bin/env python3

'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import os
import sys
import traceback
from datetime import datetime

#Thư mục gốc VBot:
#/home/pi/VBot_Offline
VBOT_ROOT_PATH = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))

if VBOT_ROOT_PATH not in sys.path:
    sys.path.insert(0, VBOT_ROOT_PATH)

LOG_FILE = f"{VBOT_ROOT_PATH}/resource/log/Vbot_error.log"

def write_error_log(error):
    try:
        os.makedirs(os.path.dirname(LOG_FILE), exist_ok=True)
        current_time = datetime.now().strftime("%H:%M:%S %d-%m-%Y")
        with open(LOG_FILE, "a", encoding="utf-8") as log_file:
            log_file.write(
                "\n"
                "============================================================\n"
                f"[{current_time}] "
                "[VBot Multiroom Bridge] Khởi động thất bại\n"
                f"Lỗi: {error}\n"
                f"{traceback.format_exc()}"
                "============================================================\n"
            )
    except Exception:
        traceback.print_exc(file=sys.stderr)

def main():
    try:
        from VBot_Multiroom_Bridge_Audio import main as bridge_main
        return bridge_main()
    except KeyboardInterrupt:
        return 0
    except Exception as error:
        current_time = datetime.now().strftime("%H:%M:%S %d-%m-%Y")
        print(f"[{current_time}] [VBot Multiroom Bridge] Lỗi: {error}", file=sys.stderr)
        write_error_log(error)
        return 1

if __name__ == "__main__":
    sys.exit(main())