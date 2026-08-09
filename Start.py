'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import os
import importlib
import json
import subprocess
import sys
import traceback
from datetime import datetime
from pathlib import Path

VBOT_PATH = Path(__file__).resolve().parent
PAHO_MQTT_REQUIRED_VERSION = "1.6.1"

def _write_startup_log(message: str, *, error: bool = False) -> None:
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
            handle.write(f"[{timestamp}] [Start MQTT] {text}\n")
    except Exception as log_error:
        print(f"[{timestamp}] - [Start MQTT] Không thể ghi file log: {log_error}", file=sys.stderr)

def _mqtt_is_enabled() -> bool:
    for config_name in ("Config.json", "Config.json.bak"):
        config_path = VBOT_PATH / config_name
        try:
            with config_path.open("r", encoding="utf-8-sig") as handle:
                config = json.load(handle)
            mqtt_config = config.get("mqtt_broker", {})
            if isinstance(mqtt_config, dict):
                return mqtt_config.get("mqtt_active") is True
        except (OSError, ValueError, TypeError):
            continue
    return False

def check_paho_mqtt_version() -> bool:
    try:
        from importlib.metadata import PackageNotFoundError, version
    except ImportError:
        from importlib_metadata import PackageNotFoundError, version
    try:
        from packaging.version import Version
    except ImportError:
        try:
            from pip._vendor.packaging.version import Version
        except ImportError as error:
            _write_startup_log(f"Không thể kiểm tra phiên bản paho-mqtt vì thiếu packaging: {error}", error=True)
            return False
    try:
        current_version = version("paho-mqtt")
        #_write_startup_log(f"Phiên bản paho-mqtt hiện tại: {current_version}")
        if Version(current_version) >= Version(PAHO_MQTT_REQUIRED_VERSION):
            try:
                importlib.import_module("paho.mqtt.client")
                #_write_startup_log(f"paho-mqtt {current_version} đã đạt yêu cầu (>= {PAHO_MQTT_REQUIRED_VERSION}), không cần cập nhật.")
                return True
            except Exception as error:
                _write_startup_log(f"paho-mqtt {current_version} có metadata nhưng không import được: {error}. Đang cài đặt lại...", error=True)
        _write_startup_log(f"paho-mqtt {current_version} thấp hơn {PAHO_MQTT_REQUIRED_VERSION}. Đang nâng cấp...")
    except PackageNotFoundError:
        _write_startup_log(f"Chưa cài đặt paho-mqtt. Đang cài phiên bản {PAHO_MQTT_REQUIRED_VERSION}...")
    except Exception as error:
        _write_startup_log(f"Lỗi khi đọc phiên bản paho-mqtt: {error}", error=True)
        return False
    try:
        result = subprocess.run(
            [
                sys.executable,
                "-m",
                "pip",
                "install",
                "--user",
                "--upgrade",
                f"paho-mqtt=={PAHO_MQTT_REQUIRED_VERSION}",
            ],
            check=True,
            capture_output=True,
            text=True,
            timeout=180,
        )
        installed_version = version("paho-mqtt")
        if Version(installed_version) < Version(PAHO_MQTT_REQUIRED_VERSION):
            raise RuntimeError(f"pip hoàn tất nhưng phiên bản nhận được là {installed_version}")
        importlib.invalidate_caches()
        importlib.import_module("paho.mqtt.client")
        _write_startup_log(f"Đã cài đặt paho-mqtt thành công: {installed_version}")
        if result.stdout.strip():
            _write_startup_log(result.stdout.strip())
        return True
    except Exception as error:
        detail = getattr(error, "stderr", None)
        suffix = f" | {str(detail).strip()}" if detail else ""
        _write_startup_log(f"Lỗi khi cài đặt paho-mqtt: {error}{suffix}", error=True)
        return False

def main() -> int:
    try:
        if _mqtt_is_enabled() and not check_paho_mqtt_version():
            _write_startup_log("Không thể bảo đảm phiên bản paho-mqtt yêu cầu, VBot vẫn tiếp tục khởi động.", error=True)
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
