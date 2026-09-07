"""Dieu phoi cap nhat Program/WebUI tu API va MQTT."""

import shutil
import json
import re
import subprocess
import sys
import threading
import time
from pathlib import Path

try:
    import fcntl
except ImportError:  # pragma: no cover - Windows/development environment
    fcntl = None

import Led
import Lib
from Media_Player import media_player


ROOT = Path(__file__).resolve().parent
RESULT_FILE = ROOT / ".vbot_update_result.json"
PROGRAM_MARKER = ROOT / ".program_upgrade_in_progress"
UPGRADE_LOCK = ROOT / ".vbot_upgrade.lock"
MISSING_RESULT_GRACE_SECONDS = 30
VBOT_SERVICE = "VBot_Offline.service"
UPDATE_TARGETS = {
    "program": {
        "script": ROOT / "Manual_Update_Program.py",
        "sound": ROOT / "resource/sound/default/update_the_vbot_program.mp3",
        "success_sound": ROOT / "resource/sound/default/updated_the_program_successfully.mp3",
        "error_sound": ROOT / "resource/sound/default/vbot_program_update_failed.mp3",
        "label": "chương trình VBot",
    },
    "interface": {
        "script": ROOT / "Manual_Update_WebUI.py",
        "sound": ROOT / "resource/sound/default/update_the_vbot_interface.mp3",
        "success_sound": ROOT / "resource/sound/default/vbot_interface_updated_successfully.mp3",
        "error_sound": ROOT / "resource/sound/default/vbot_interface_update_failed.mp3",
        "label": "giao diện VBot",
    },
}

_lock = threading.RLock()
_state = {
    "running": bool(Lib.update_in_progress),
    "target": "program" if Lib.update_in_progress else None,
    "status": "running" if Lib.update_in_progress else "idle",
    "message": "Chưa có tác vụ cập nhật",
    "started_at": None,
    "finished_at": None,
    "pid": None,
}


def update_snapshot():
    with _lock:
        return dict(_state)


def _set_state(**values):
    with _lock:
        _state.update(values)
        if "running" in values:
            Lib.update_in_progress = bool(values["running"])


def _wait_for_runtime_audio_ready(timeout=120):
    ready_event = getattr(Lib, "vbot_ready_event", None)
    if ready_event is None:
        return True
    if ready_event.wait(timeout=max(0, timeout)):
        return True
    message = "Hết thời gian chờ VBot sẵn sàng để phát âm báo cập nhật"
    Lib.Logs_VBot(f"[Update] {message}")
    Lib.show_log(f"[Update] {message}", color=Lib.Color.YELLOW)
    return False


def _notify_update_result(config, status, message):
    log_message = f"[Update] {message}"
    Lib.Logs_VBot(log_message)
    Lib.show_log(
        log_message,
        color=Lib.Color.GREEN if status == "success" else Lib.Color.RED,
    )
    sound = config["success_sound" if status == "success" else "error_sound"]
    _wait_for_runtime_audio_ready()
    Led.LED("UPDATE")
    if not sound.is_file():
        missing = f"[Update] Không tìm thấy âm báo kết quả: {sound}"
        Lib.Logs_VBot(missing)
        Lib.show_log(missing, color=Lib.Color.YELLOW)
        return False
    if media_player.Play_Sound(str(sound), force=True):
        return True
    failed = f"[Update] Không thể phát âm báo kết quả: {sound}"
    Lib.Logs_VBot(failed)
    Lib.show_log(failed, color=Lib.Color.RED)
    return False


def _valid_service_name(value):
    value = str(value or "").strip()
    if not value or not re.fullmatch(r"[A-Za-z0-9_.@:-]+\.service", value):
        raise ValueError(f"Tên service không hợp lệ: {value!r}")
    return value


def _restart_vbot_after_update(service_name=VBOT_SERVICE):
    """Schedule the service restart only after result logging and audio finish."""
    try:
        service_name = _valid_service_name(service_name)
        message = f"[Update] Đã hoàn tất thông báo kết quả, chuẩn bị restart {service_name}"
        Lib.Logs_VBot(message)
        Lib.show_log(message, color=Lib.Color.YELLOW)
        if sys.platform.startswith("linux") and shutil.which("systemd-run"):
            unit = f"vbot-restart-after-update-{int(time.time())}"
            command = [
                "systemd-run", "--user", "--collect", "--quiet", "--no-block",
                "--on-active=1s", f"--unit={unit}",
                "systemctl", "--user", "restart", service_name,
            ]
            result = subprocess.run(
                command, cwd=str(ROOT), stdin=subprocess.DEVNULL,
                stdout=subprocess.PIPE, stderr=subprocess.STDOUT,
                text=True, timeout=10, check=False,
            )
            if result.returncode:
                detail = (result.stdout or "").strip() or f"mã trả về {result.returncode}"
                raise RuntimeError(f"systemd-run không nhận lịch restart: {detail}")
            accepted = f"[Update] Đã lên lịch restart {service_name} sau 1 giây"
            Lib.Logs_VBot(accepted)
            Lib.show_log(accepted, color=Lib.Color.YELLOW)
            return True
        else:
            command = ["systemctl", "--user", "restart", service_name]
        subprocess.Popen(
            command,
            cwd=str(ROOT),
            stdin=subprocess.DEVNULL,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            start_new_session=True,
        )
        return True
    except Exception as error:
        failed = f"[Update] Không thể restart {service_name}: {error}"
        Lib.log_exception("UpdateManager.restart_after_update", error)
        Lib.Logs_VBot(failed)
        Lib.show_log(failed, color=Lib.Color.RED)
        return False


def _consume_update_result():
    restart_required = False
    try:
        with _lock:
            if not RESULT_FILE.is_file():
                return False
            data = json.loads(RESULT_FILE.read_text(encoding="utf-8-sig"))
            if not isinstance(data, dict) or data.get("status") not in {"success", "error"}:
                return False
            target = str(data.get("target") or "")
            config = UPDATE_TARGETS.get(target)
            if config is None:
                return False
            status = data["status"]
            restart_required = bool(data.get("restart_required", target == "program"))
            service_name = VBOT_SERVICE
            if target == "program" and restart_required:
                service_name = _valid_service_name(data.get("service", VBOT_SERVICE))
            # Chỉ xóa sau khi toàn bộ schema cần thiết đã hợp lệ.
            RESULT_FILE.unlink(missing_ok=True)
        _set_state(
            running=False, target=target, status=status,
            message=str(data.get("message") or f"Cập nhật {config['label']} {status}"),
            started_at=data.get("started_at"), finished_at=data.get("finished_at"), pid=None,
        )
        _notify_update_result(config, status, update_snapshot()["message"])
        Led.LED("MUTE" if not Lib.mic_on_off else "OFF")
        if target == "program" and restart_required:
            _restart_vbot_after_update(service_name)
        return True
    except Exception as error:
        message = f"[Update] Lỗi đọc kết quả cập nhật: {error}"
        Lib.log_exception("UpdateManager.consume_result", error)
        Lib.Logs_VBot(message)
        Lib.show_log(message, color=Lib.Color.RED)
        return False


def _program_updater_active():
    """Return True only while the program updater still owns its upgrade lock."""
    if not PROGRAM_MARKER.is_file():
        return False
    if fcntl is None:
        # Trên môi trường không có flock, marker vẫn là tín hiệu tốt nhất.
        return True
    lock = UPGRADE_LOCK.open("a+")
    try:
        try:
            fcntl.flock(lock.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            return True
        fcntl.flock(lock.fileno(), fcntl.LOCK_UN)
        return False
    finally:
        lock.close()


def _recover_missing_program_result():
    message = "Updater chương trình đã kết thúc nhưng không ghi kết quả cập nhật"
    _set_state(
        running=False, target="program", status="error", message=message,
        finished_at=int(time.time()), pid=None,
    )
    _notify_update_result(UPDATE_TARGETS["program"], "error", message)
    Led.restore_resting_state()
    _restart_vbot_after_update(VBOT_SERVICE)


def _watch_update_result(attempts=None, stop_when_idle=False):
    # Program updater ghi kết quả sau khi service mới đã healthy, vì
    # vậy phiên VBot vừa khởi động phải chờ đến khi updater thực sự kết thúc,
    # không được tự bỏ cuộc sau một timeout cố định khi bước nén backup lâu.
    remaining = None if attempts is None else max(1, int(attempts))
    updater_stopped_at = None
    while remaining is None or remaining > 0:
        if _consume_update_result():
            return
        snapshot = update_snapshot()
        if stop_when_idle and not snapshot["running"]:
            return
        if snapshot["running"] and snapshot["target"] == "program":
            if _program_updater_active():
                updater_stopped_at = None
            elif updater_stopped_at is None:
                updater_stopped_at = time.monotonic()
            elif time.monotonic() - updater_stopped_at >= MISSING_RESULT_GRACE_SECONDS:
                # Kiểm tra lần cuối để khép race giữa lúc updater nhả flock và
                # atomic rename file kết quả.
                if not _consume_update_result():
                    _recover_missing_program_result()
                return
        if remaining is not None:
            remaining -= 1
        time.sleep(1)


def _build_command(target, script):
    python = sys.executable or "python3"
    base = [python, str(script)]
    # Manual updater luôn hoãn restart đến sau khi đã ghi/phát kết quả.
    # Update Manager sẽ nhận file bàn giao và thực hiện restart ở bước cuối.
    # Đặt Program updater trong transient unit riêng để tác vụ tải/copy/backup
    # không phụ thuộc vòng đời process VBot đang phục vụ API/MQTT.
    if sys.platform.startswith("linux") and shutil.which("systemd-run"):
        unit = f"vbot-update-{target}-{int(time.time())}"
        options = ["systemd-run", "--user", "--collect", "--quiet"]
        # Program dùng --no-block; kết quả được watcher nhận rồi mới phát âm
        # báo và lên lịch restart. WebUI không restart nên có thể --wait.
        options.append("--no-block" if target == "program" else "--wait")
        return [*options, f"--unit={unit}", "--working-directory=" + str(ROOT), *base]
    return base


def _run_update(target):
    config = UPDATE_TARGETS[target]
    try:
        Led.LED("UPDATE")
        if config["sound"].is_file():
            media_player.Play_Sound(str(config["sound"]), force=True)
        else:
            Lib.show_log(f"[Update] Không tìm thấy âm báo: {config['sound']}", color=Lib.Color.YELLOW)
        # Play_Sound/callback có thể thay LED, nên khôi phục UPDATE
        # ngay trước khi bắt đầu tiến trình cập nhật.
        Led.LED("UPDATE")
        command = _build_command(target, config["script"])
        process = subprocess.Popen(
            command,
            cwd=str(ROOT),
            stdin=subprocess.DEVNULL,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            start_new_session=True,
        )
        _set_state(pid=process.pid, status="running", message=f"Đang cập nhật {config['label']}")
        return_code = process.wait()
        detached_program_update = target == "program" and "--no-block" in command
        if return_code == 0 and detached_program_update:
            _set_state(status="running", message="Updater chương trình đang chạy trong transient unit độc lập")
            # Nếu updater lỗi trước khi kịp restart VBot, phiên hiện tại vẫn
            # còn sống và phải tự nhận result. Nếu restart thành công, watcher
            # của phiên VBot mới sẽ tiếp quản file kết quả này.
            _watch_update_result(stop_when_idle=True)
            return
        if return_code == 0:
            _set_state(running=False, status="success", finished_at=int(time.time()), message=f"Cập nhật {config['label']} thành công")
        else:
            raise RuntimeError(f"Updater kết thúc với mã {return_code}")
    except Exception as error:
        message = f"[Update] Lỗi cập nhật {config['label']}: {error}"
        Lib.Logs_VBot(message)
        Lib.show_log(message, color=Lib.Color.RED)
        _set_state(running=False, status="error", finished_at=int(time.time()), message=message)
        # Lỗi khởi tạo tiến trình xảy ra trước khi updater có
        # thể ghi result file, nên phát thông báo thất bại ngay.
        if not RESULT_FILE.is_file() and config["error_sound"].is_file():
            Led.LED("UPDATE")
            media_player.Play_Sound(str(config["error_sound"]), force=True)
    finally:
        # Khi Program update thành công service thường đã restart.
        # Nhánh này chủ yếu khôi phục LED cho WebUI/lỗi khởi chạy.
        if not update_snapshot()["running"]:
            _consume_update_result()
            Led.LED("MUTE" if not Lib.mic_on_off else "OFF")


def start_update(target):
    target = str(target or "").strip().lower()
    if target == "webui":
        target = "interface"
    if target not in UPDATE_TARGETS:
        return False, "Loại cập nhật chỉ chấp nhận: program hoặc interface", update_snapshot()
    config = UPDATE_TARGETS[target]
    if not config["script"].is_file():
        return False, f"Không tìm thấy updater: {config['script'].name}", update_snapshot()
    with _lock:
        if _state["running"]:
            return False, f"Đang cập nhật {_state['target']}, không thể chạy thêm tác vụ", dict(_state)
        _state.update({
            "running": True,
            "target": target,
            "status": "starting",
            "message": f"Đang chuẩn bị cập nhật {config['label']}",
            "started_at": int(time.time()),
            "finished_at": None,
            "pid": None,
        })
        Lib.update_in_progress = True
        snapshot = dict(_state)
    threading.Thread(target=_run_update, args=(target,), daemon=True, name=f"vbot-update-{target}").start()
    return True, snapshot["message"], snapshot


threading.Thread(target=_watch_update_result, daemon=True, name="vbot-update-result").start()
