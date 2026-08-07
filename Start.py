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
from pathlib import Path

VBOT_PATH = Path(__file__).resolve().parent

def main() -> int:
    try:
        import VBot
        VBot.main()
        return 0
    except KeyboardInterrupt:
        return 0
    except Exception as e:
        traceback_text = traceback.format_exc()
        msg_error = f"[Start] Lỗi khi khởi động VBot: {e}\n{traceback_text}"
        try:
            log_file = VBOT_PATH / "resource" / "log" / "Vbot_error.log"
            os.makedirs(os.path.dirname(log_file), exist_ok=True)
            with open(log_file, "a", encoding="utf-8") as f:
                f.write(
                    f"\n[{datetime.now().strftime('%H:%M:%S %d-%m-%Y')}]\n"
                    f"{msg_error}\n"
                )
        except Exception as log_error:
            print(f"[Start] Không thể ghi file log: {log_error}", file=sys.stderr)
        try:
            import Lib
            Lib.show_log(f"[Start] Lỗi khi khởi động VBot: {e}", color=Lib.Color.RED)
        except Exception:
            print(f"[Start] Lỗi khi khởi động VBot: {e}", file=sys.stderr)
        print(traceback_text, file=sys.stderr, end="")
        return 1

if __name__ == "__main__":
    sys.exit(main())