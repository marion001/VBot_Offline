'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import sys
import traceback

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
            import Lib
            Lib.Logs_VBot(msg_error)
            Lib.show_log(f"[Start] Lỗi khi khởi động VBot: {e}", color=Lib.Color.RED)
            print(traceback_text, file=sys.stderr, end="")
        except Exception:
            print(msg_error, file=sys.stderr, end="")
        return 1

if __name__ == "__main__":
    sys.exit(main())