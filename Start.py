'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import sys

def main() -> int:
    try:
        import VBot
        VBot.main()
        return 0
    except KeyboardInterrupt:
        return 0
    except Exception as e:
        try:
            import Lib
            Msg_ERROR = f"[Start] Lỗi khi khởi động VBot: {e}"
            Lib.Logs_VBot(Msg_ERROR)
            Lib.show_log(Msg_ERROR, color=Lib.Color.RED)
        except Exception:
            print(f"[Start] Lỗi khi khởi động VBot: {e}", file=sys.stderr)
        return 1

if __name__ == "__main__":
    sys.exit(main())