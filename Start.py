'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import os
import signal
import sys
import traceback
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime
from pathlib import Path

# asyncio.to_thread() lazily imports ThreadPoolExecutor on first use.  VBot
# starts several background components concurrently, so preload it completely
# on the main thread before importing VBot to avoid a partially initialized
# concurrent.futures.thread module during startup.
_THREAD_POOL_EXECUTOR_CLASS = ThreadPoolExecutor

VBOT_PATH = Path(__file__).resolve().parent

def _write_startup_log(message: str, *, error: bool = False, context: str = 'Start MQTT') -> None:
    text = str(message)
    timestamp = datetime.now().strftime("%H:%M:%S %d-%m-%Y")
    rendered = "\n".join(f"[{timestamp}] - {line}" for line in (text.splitlines() or [""]))
    try:
        print(rendered)
    except UnicodeEncodeError:
        encoding = getattr(sys.stdout, "encoding", None) or "utf-8"
        safe_text = rendered.encode(encoding, errors="replace").decode(encoding, errors="replace")
        print(safe_text)
    if not error:
        return
    try:
        log_file = VBOT_PATH / "resource" / "log" / "Vbot_error.log"
        log_file.parent.mkdir(parents=True, exist_ok=True)
        with log_file.open("a", encoding="utf-8") as handle:
            handle.write(f"[{timestamp}] [{context}] {text}\n")
    except Exception as log_error:
        print(f"[{timestamp}] - [{context}] Không thể ghi file log: {log_error}", file=sys.stderr)


def main() -> int:
    try:
        try:
            from Sync_System_Services import prepare_startup
            prepare_startup(VBOT_PATH)
        except Exception as error:
            _write_startup_log(f'[System Sync] {error}', error=True, context='Start System Sync')
        import VBot
        shutdown_started = False

        def handle_shutdown_signal(signum, _frame):
            nonlocal shutdown_started
            if shutdown_started:
                return
            shutdown_started = True
            signal_name = signal.Signals(signum).name
            _write_startup_log(f"Nhận {signal_name}, đang dọn dẹp trước khi dừng VBot...")
            VBot.cleanup()
            raise SystemExit(0)

        signal.signal(signal.SIGTERM, handle_shutdown_signal)
        signal.signal(signal.SIGINT, handle_shutdown_signal)
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
                f.write(f"\n[{datetime.now().strftime('%H:%M:%S %d-%m-%Y')}]\n {msg_error}\n")
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
