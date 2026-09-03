#!/usr/bin/env python3
'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

"""Cập nhật thủ công chương trình VBot khi WebUI không sử dụng được."""

#Cập nhật $:> python3 Manual_Update_Program.py

import argparse
import contextlib
import datetime as dt
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import stat
import subprocess
import sys
import tarfile
import tempfile
import time
import urllib.request
import zipfile

try:
    import fcntl
except ImportError:
    fcntl = None

ROOT = Path(__file__).resolve().parent
ERROR_LOG = ROOT / "resource/log/Vbot_error.log"
MARKER = ROOT / ".program_upgrade_in_progress"
UPGRADE_LOCK = ROOT / ".vbot_upgrade.lock"
UPDATE_RESULT = ROOT / ".vbot_update_result.json"
SUCCESS_SOUND = ROOT / "resource/sound/default/vbot_program_updated_successfully.mp3"
ERROR_SOUND = ROOT / "resource/sound/default/vbot_program_update_failed.mp3"
CORE_UPDATE_JSON = {
    "Version.json",
    "Action.json",
    "Adverbs.json",
    "Object.json",
    "resource/SYS_CMD.json",
    "resource/VietNam_Localtion.json",
    "resource/API_VBot_OFFLINE.postman_collection.json",
}

for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8", errors="replace")
    except (AttributeError, OSError):
        pass

def log(message, error=False):
    level = "ERROR" if error else "INFO"
    line = f"[{dt.datetime.now():%H:%M:%S %d-%m-%Y}] [MANUAL PROGRAM {level}] {message}"
    print(line)
    if error:
        ERROR_LOG.parent.mkdir(parents=True, exist_ok=True)
        with ERROR_LOG.open("a", encoding="utf-8") as handle:
            handle.write(line + "\n")

def log_result(message, error=False):
    """Persist the final outcome; ordinary progress remains console-only."""
    level = "ERROR" if error else "INFO"
    line = f"[{dt.datetime.now():%H:%M:%S %d-%m-%Y}] [MANUAL PROGRAM {level}] {message}"
    print(line)
    ERROR_LOG.parent.mkdir(parents=True, exist_ok=True)
    with ERROR_LOG.open("a", encoding="utf-8") as handle:
        handle.write(line + "\n")

def play_result_sound(path):
    path = Path(path)
    if not path.is_file():
        log_result(f"Không tìm thấy âm báo kết quả: {path}", error=True)
        return False
    candidates = (
        ("mpg123", ["mpg123", "-q", str(path)]),
        ("cvlc", ["cvlc", "--play-and-exit", "--quiet", str(path)]),
        ("ffplay", ["ffplay", "-nodisp", "-autoexit", "-loglevel", "quiet", str(path)]),
    )
    for executable, command in candidates:
        if not shutil.which(executable):
            continue
        try:
            result = subprocess.run(command, check=False, timeout=60)
            if result.returncode == 0:
                return True
        except (OSError, subprocess.SubprocessError) as error:
            log(f"Trình phát {executable} không phát được âm báo: {error}", error=True)
    log_result("Không thể phát âm báo kết quả bằng mpg123, cvlc hoặc ffplay", error=True)
    return False

def finish_without_update_manager(result, restart_required, service_name="VBot_Offline.service"):
    """Fallback for SSH/manual runs when no active VBot watcher accepts the result."""
    if not restart_required:
        return
    deadline = time.monotonic() + 10
    while UPDATE_RESULT.exists() and time.monotonic() < deadline:
        time.sleep(0.25)
    if not UPDATE_RESULT.exists():
        log("Update Manager đã nhận kết quả và sẽ restart service sau âm báo")
        return
    # Tránh service mới đọc lại cùng kết quả và restart lần thứ hai.
    UPDATE_RESULT.unlink(missing_ok=True)
    status = result.get("status")
    message = str(result.get("message") or "Không có nội dung kết quả cập nhật")
    log_result(f"[Update] {message}", error=status != "success")
    play_result_sound(SUCCESS_SOUND if status == "success" else ERROR_SOUND)
    log_result(f"[Update] Đã hoàn tất âm báo, tiến hành restart {service_name}")
    restarted = service(["restart", service_name])
    if restarted.returncode:
        raise RuntimeError("Không thể restart service sau thông báo kết quả: " + restarted.stdout.strip())

def read_json(path):
    with Path(path).open("r", encoding="utf-8-sig") as handle:
        value = json.load(handle)
    if not isinstance(value, dict):
        raise ValueError(f"{path} không chứa JSON object")
    return value

def backup_version_metadata(path):
    try:
        data = read_json(path)
    except (OSError, ValueError, json.JSONDecodeError):
        data = {}
    clean = lambda value, fallback: re.sub(r"[^0-9A-Za-z._-]", "-", str(value or fallback))
    return clean(data.get("releaseDate"), "unknown-date"), clean(data.get("version"), "unknown-version")

def backup_limit(config, section):
    try:
        limit = int(config["backup_upgrade"][section]["backup"]["limit_backup_files"])
        return limit if limit >= 1 else 5
    except (KeyError, TypeError, ValueError):
        return 5

def prune_backups(directory, prefix, limit, keep=None):
    limit = max(1, limit)
    backups = sorted(
        (item for item in directory.glob(f"{prefix}_*.tar.gz") if item.is_file() and not item.is_symlink()),
        key=lambda item: (item.stat().st_mtime_ns, item.name),
    )
    removed = 0
    excess = max(0, len(backups) - limit)
    for item in backups:
        if removed >= excess:
            break
        if keep is not None and item == keep:
            continue
        try:
            item.unlink()
            removed += 1
            log(f"Đã xóa backup chương trình cũ: {item.name}")
        except OSError as error:
            log(f"Không thể xóa backup cũ {item}: {error}", error=True)
    log(f"Đã kiểm tra giới hạn backup: giữ tối đa {limit} tệp, đã xóa {removed} tệp cũ")

def package_rollback(rollback, prefix, release_date, version, limit=5):
    if not rollback.is_dir():
        raise RuntimeError(f"Không tìm thấy thư mục rollback để nén: {rollback}")
    name = f"{prefix}_{dt.datetime.now():%d%m%Y_%H%M%S}_{release_date}_{version}.tar.gz"
    archive = rollback.parent / name
    log(f"Đang tiến hành nén bản sao lưu thành tệp tar.gz: {archive.name}")
    fd, temp_name = tempfile.mkstemp(prefix=".manual-backup-", suffix=".tar.gz", dir=str(rollback.parent))
    os.close(fd)
    try:
        with tarfile.open(temp_name, "w:gz") as package:
            package.add(rollback, arcname=".", recursive=True)
        with tarfile.open(temp_name, "r:gz") as package:
            if not package.getmembers():
                raise RuntimeError("Tệp backup vừa nén không chứa dữ liệu")
        os.replace(temp_name, archive)
        os.chmod(archive, 0o777)
        shutil.rmtree(rollback)
    finally:
        if os.path.exists(temp_name):
            os.unlink(temp_name)
    log(f"Đã nén rollback thành: {archive}")
    prune_backups(archive.parent, prefix, limit, keep=archive)
    return archive

def merge_config(template, old):
    result = dict(template)
    for key, old_value in old.items():
        if key not in result:
            continue
        if isinstance(old_value, dict) and isinstance(result[key], dict):
            result[key] = merge_config(result[key], old_value)
        else:
            result[key] = old_value
    return result

def download_with_progress(url, destination, timeout):
    request = urllib.request.Request(url, headers={"User-Agent": "VBot-Manual-Updater"})
    with urllib.request.urlopen(request, timeout=timeout) as response, destination.open("wb") as out:
        total_header = response.headers.get("Content-Length")
        total = int(total_header) if total_header and total_header.isdigit() else 0
        downloaded = 0
        started = time.monotonic()
        last_display = 0.0
        while True:
            chunk = response.read(64 * 1024)
            if not chunk:
                break
            out.write(chunk)
            downloaded += len(chunk)
            now = time.monotonic()
            if now - last_display < 0.2:
                continue
            elapsed = max(now - started, 0.001)
            speed = downloaded / elapsed / (1024 * 1024)
            current_mb = downloaded / (1024 * 1024)
            if total > 0:
                total_mb = total / (1024 * 1024)
                percent = min(100.0, downloaded * 100 / total)
                status = f"Đang tải: {current_mb:.2f}/{total_mb:.2f} MB ({percent:5.1f}%) | {speed:.2f} MB/s"
            else:
                status = f"Đang tải: {current_mb:.2f} MB | {speed:.2f} MB/s"
            sys.stdout.write("\r" + status.ljust(90))
            sys.stdout.flush()
            last_display = now
        out.flush()
        os.fsync(out.fileno())
    elapsed = max(time.monotonic() - started, 0.001)
    speed = downloaded / elapsed / (1024 * 1024)
    current_mb = downloaded / (1024 * 1024)
    if total > 0:
        final_status = (
            f"Đang tải: {current_mb:.2f}/{total / (1024 * 1024):.2f} MB "
            f"(100.0%) | {speed:.2f} MB/s"
        )
    else:
        final_status = f"Đã tải: {current_mb:.2f} MB | {speed:.2f} MB/s"
    sys.stdout.write("\r" + final_status.ljust(90) + "\n")
    sys.stdout.flush()
    log(f"Tải xuống hoàn tất: {downloaded / (1024 * 1024):.2f} MB trong {elapsed:.1f} giây")

def atomic_json_write(path, value):
    path = Path(path)
    lock_path = Path(str(path) + ".lock")
    lock_path.parent.mkdir(parents=True, exist_ok=True)
    with lock_path.open("a+") as lock:
        if fcntl is not None:
            fcntl.flock(lock.fileno(), fcntl.LOCK_EX)
        fd, temp_name = tempfile.mkstemp(prefix=".manual-update-", dir=str(path.parent))
        try:
            with os.fdopen(fd, "w", encoding="utf-8") as handle:
                json.dump(value, handle, ensure_ascii=False, indent=4)
                handle.flush()
                os.fsync(handle.fileno())
            os.replace(temp_name, path)
        finally:
            if os.path.exists(temp_name):
                os.unlink(temp_name)
            if fcntl is not None:
                fcntl.flock(lock.fileno(), fcntl.LOCK_UN)

def atomic_copy_file(source, destination):
    source = Path(source)
    destination = Path(destination)
    destination.parent.mkdir(parents=True, exist_ok=True)
    fd, temp_name = tempfile.mkstemp(prefix=".manual-copy-", dir=str(destination.parent))
    try:
        with source.open("rb") as src, os.fdopen(fd, "wb") as out:
            shutil.copyfileobj(src, out, length=1024 * 1024)
            out.flush()
            os.fsync(out.fileno())
        try:
            os.chmod(temp_name, stat.S_IMODE(source.stat().st_mode))
        except OSError:
            pass
        os.replace(temp_name, destination)
    except Exception as error:
        raise OSError(f"Không thể sao chép {source} -> {destination}: {error}") from error
    finally:
        if os.path.exists(temp_name):
            os.unlink(temp_name)

def apply_project_permissions(root):
    root = Path(root)
    excluded_roots = {root / "TTS_Audio", root / "Media", root / "html"}
    changed = 0
    already_correct = 0
    skipped_locks = 0
    permission_errors = []

    def chmod_path(path):
        nonlocal changed, already_correct
        try:
            os.chmod(path, 0o777)
            changed += 1
        except OSError as error:
            try:
                if stat.S_IMODE(os.stat(path, follow_symlinks=False).st_mode) == 0o777:
                    already_correct += 1
                    return
            except OSError:
                pass
            permission_errors.append(f"{path}: {error}")

    chmod_path(root)
    for current, directories, files in os.walk(root, topdown=True, followlinks=False):
        current_path = Path(current)
        kept_directories = []
        for name in directories:
            path = current_path / name
            if path in excluded_roots or path.is_symlink():
                continue
            chmod_path(path)
            kept_directories.append(name)
        directories[:] = kept_directories
        for name in files:
            path = current_path / name
            if path.is_symlink() or name.endswith(".lock"):
                if name.endswith(".lock"):
                    skipped_locks += 1
                continue
            chmod_path(path)
    log(
        f"Đã chmod 0777 cho {changed} file/thư mục trong {root}; "
        f"{already_correct} lỗi chmod được bỏ qua vì quyền đã là 0777; "
        f"đã loại trừ TTS_Audio, Media, toàn bộ html, liên kết mềm và {skipped_locks} file *.lock"
    )
    if permission_errors:
        log(f"Có {len(permission_errors)} file/thư mục không thể chmod, bản cập nhật vẫn tiếp tục vì đây chỉ là lỗi metadata quyền", error=True)
        for detail in permission_errors:
            log(f"Không thể chmod 0777: {detail}", error=True)

def acquire_zip(args, work):
    archive = work / "update.zip"
    if args.zip:
        log(f"Đang sử dụng gói ZIP local: {args.zip}")
        atomic_copy_file(Path(args.zip).expanduser().resolve(), archive)
    else:
        url = f"{args.repo.rstrip('/')}/archive/refs/heads/{args.branch}.zip"
        log(f"Đang tải: {url}")
        download_with_progress(url, archive, args.timeout)
    if archive.stat().st_size < 1024:
        raise RuntimeError("Gói ZIP rỗng hoặc quá nhỏ")
    log(f"Đã kiểm tra kích thước ZIP: {archive.stat().st_size / (1024 * 1024):.2f} MB")
    return archive

def safe_extract(archive, target):
    log("Đang kiểm tra an toàn và giải nén gói cập nhật...")
    with zipfile.ZipFile(archive) as package:
        for info in package.infolist():
            name = PurePosixPath(info.filename.replace("\\", "/"))
            mode = (info.external_attr >> 16) & 0o170000
            if name.is_absolute() or ".." in name.parts or mode == stat.S_IFLNK:
                raise RuntimeError(f"ZIP chứa đường dẫn/liên kết không an toàn: {info.filename}")
        package.extractall(target)
    roots = [item for item in target.iterdir() if item.is_dir()]
    if len(roots) != 1:
        raise RuntimeError("Không xác định được thư mục gốc duy nhất trong ZIP")
    log(f"Giải nén thành công: {roots[0].name}")
    return roots[0]

def safe_extract_tar(archive, target):
    log(f"Đang kiểm tra an toàn tệp rollback: {archive.name}")
    target.mkdir(parents=True, exist_ok=True)
    with tarfile.open(archive, "r:gz") as package:
        members = package.getmembers()
        if not members:
            raise RuntimeError("Tệp rollback tar.gz không chứa dữ liệu")
        for member in members:
            name = PurePosixPath(member.name.replace("\\", "/"))
            if name.is_absolute() or ".." in name.parts or member.issym() or member.islnk() or member.isdev():
                raise RuntimeError(f"Tệp rollback chứa mục không an toàn: {member.name}")
        package.extractall(target, members=members)
    log("Đã kiểm tra và giải nén tệp rollback an toàn")
    return target

def newest_backup(paths, prefix):
    candidates = [
        item
        for directory in paths
        if directory.is_dir()
        for item in directory.glob(f"{prefix}_*.tar.gz")
        if item.is_file()
    ]
    if not candidates:
        locations = ", ".join(str(path) for path in paths)
        raise RuntimeError(f"Không tìm thấy backup {prefix}_*.tar.gz trong: {locations}")
    return max(candidates, key=lambda item: (item.stat().st_mtime_ns, item.name))

def requested_backup(value, paths, prefix):
    if value == "latest":
        return newest_backup(paths, prefix)
    requested = Path(value).expanduser()
    if requested.is_symlink():
        raise RuntimeError(f"Không chấp nhận file backup là liên kết mềm: {requested}")
    try:
        archive = requested.resolve(strict=True)
    except OSError as error:
        raise RuntimeError(f"File backup được chỉ định không tồn tại/không đọc được: {requested}") from error
    allowed_directories = {path.resolve() for path in paths}
    if archive.parent not in allowed_directories:
        raise RuntimeError("File backup phải nằm trực tiếp trong một thư mục backup được cho phép")
    if not archive.is_file() or not archive.name.startswith(prefix + "_") or not archive.name.endswith(".tar.gz"):
        raise RuntimeError(f"Tên file backup không hợp lệ, yêu cầu {prefix}_*.tar.gz")
    return archive

def restore_full_tree(source, destination, skip_html=False):
    restored = 0
    for item in source.rglob("*"):
        if not item.is_file():
            continue
        relative = item.relative_to(source)
        if skip_html and relative.parts and relative.parts[0] == "html":
            continue
        target = destination / relative
        if relative.as_posix() == "Config.json":
            atomic_json_write(target, read_json(item))
        else:
            atomic_copy_file(item, target)
        restored += 1
    if restored == 0:
        raise RuntimeError("Backup không chứa tệp nào có thể khôi phục")
    return restored

def rollback_latest(args):
    search_paths = (
        ROOT / "Backup_Upgrade/Manual_Program",
        ROOT / "html/Backup_Upgrade/Backup_Program",
    )
    archive = requested_backup(args.rollback, search_paths, "VBot_Program")
    log(f"Đã chọn bản sao lưu chương trình mới nhất: {archive}")
    with tempfile.TemporaryDirectory(prefix="vbot-manual-program-rollback-") as temp:
        extracted = safe_extract_tar(archive, Path(temp) / "extract")
        manual_format = (extracted / "created.json").is_file()
        if manual_format:
            log("Đã nhận diện backup rollback thủ công; đang khôi phục các tệp thay đổi...")
            rollback_transaction(extracted)
            config_backup = extracted / "Config.json"
            if config_backup.is_file():
                atomic_json_write(ROOT / "Config.json", read_json(config_backup))
        else:
            log("Đã nhận diện backup đầy đủ từ WebUI; đang khôi phục chương trình...")
            restored = restore_full_tree(extracted, ROOT, skip_html=True)
            log(f"Đã khôi phục {restored} tệp chương trình từ backup đầy đủ")
        apply_project_permissions(ROOT)
    log(f"Đang restart service sau rollback: {args.service}")
    restarted = service(["restart", args.service])
    if restarted.returncode:
        raise RuntimeError("Không thể restart service sau rollback: " + restarted.stdout.strip())
    time.sleep(3)
    if service(["is-active", "--quiet", args.service]).returncode:
        raise RuntimeError("Service không active sau rollback")
    log(f"Rollback chương trình thành công từ: {archive.name}")

def validate_package(source):
    log("Đang kiểm tra các tệp chương trình bắt buộc...")
    for name in ("Config.json", "Version.json", "Start.py"):
        path = source / name
        if not path.is_file() or path.stat().st_size == 0:
            raise RuntimeError(f"Thiếu tệp bắt buộc: {name}")
    for label, patterns in {
        "Lib": ("Lib.py", "Lib.cpython-*.so"),
        "VBot": ("VBot.py", "VBot.cpython-*.so"),
    }.items():
        if not any(path.is_file() and path.stat().st_size > 0 for pattern in patterns for path in source.glob(pattern)):
            raise RuntimeError(f"Thiếu module {label}: {', '.join(patterns)}")
    read_json(source / "Config.json")
    read_json(source / "Version.json")
    log("Config.json và Version.json trong gói cập nhật hợp lệ")
    if any(path.is_file() for path in source.rglob("*.py")):
        log("Đang kiểm tra cú pháp Python trong gói cập nhật...")
        lint_cache = source.parent / "python_lint_cache"
        environment = os.environ.copy()
        environment["PYTHONPYCACHEPREFIX"] = str(lint_cache)
        result = subprocess.run(
            [sys.executable, "-m", "compileall", "-q", str(source)],
            stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True,
            env=environment,
        )
        if result.returncode:
            raise RuntimeError("Cú pháp Python không hợp lệ: " + result.stdout.strip())
        log("Cú pháp Python trong gói cập nhật hợp lệ")

def matches_keep_entry(relative, keep_entries):
    value = relative.as_posix().strip("/")
    return any(
        value == entry or value.startswith(entry + "/") or relative.name == entry
        for entry in keep_entries
    )

def copy_transaction(source, rollback, keep_entries, replace_json):
    files_root = rollback / "files"
    created = []
    preserved = []
    created_file = rollback / "created.json"
    created_file.write_text("[]", encoding="utf-8")
    for item in source.rglob("*"):
        if not item.is_file():
            continue
        relative = item.relative_to(source)
        if relative.parts[0] == "html" or relative.as_posix() in {"Config.json", "Config.json.bak"}:
            continue
        destination = ROOT / relative
        relative_text = relative.as_posix()
        preserve_existing_json = (
            destination.exists()
            and relative.suffix.lower() == ".json"
            and relative_text not in CORE_UPDATE_JSON
            and relative_text not in replace_json
            and relative.name not in replace_json
        )
        if matches_keep_entry(relative, keep_entries) or preserve_existing_json:
            preserved.append(relative_text)
            continue
        if destination.exists():
            backup = files_root / relative
            atomic_copy_file(destination, backup)
        else:
            created.append(relative.as_posix())
            created_file.write_text(json.dumps(created, indent=2), encoding="utf-8")
        atomic_copy_file(item, destination)
    log(f"Đã sao chép chương trình mới; có {len(created)} tệp mới được tạo")
    if preserved:
        log(f"Đã giữ lại {len(preserved)} tệp/thư mục dữ liệu người dùng:")
        for relative in preserved:
            print(f"  - {relative}")

def rollback_transaction(rollback):
    log("Đang khôi phục các tệp chương trình từ rollback...")
    created_file = rollback / "created.json"
    if created_file.is_file():
        for relative in json.loads(created_file.read_text(encoding="utf-8")):
            target = ROOT / relative
            if target.is_file() or target.is_symlink():
                target.unlink()
    files_root = rollback / "files"
    if files_root.is_dir():
        for backup in files_root.rglob("*"):
            if backup.is_file():
                destination = ROOT / backup.relative_to(files_root)
                atomic_copy_file(backup, destination)
    log("Đã khôi phục các tệp chương trình cũ")

def service(command):
    return subprocess.run(["systemctl", "--user", *command], stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)

@contextlib.contextmanager
def upgrade_guard():
    """Giữ khóa nâng cấp thật; tự phục hồi marker nếu lần chạy trước bị dừng."""
    lock = UPGRADE_LOCK.open("a+")
    try:
        if fcntl is not None:
            try:
                fcntl.flock(lock.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
            except BlockingIOError as error:
                raise RuntimeError("Một tiến trình cập nhật khác đang hoạt động") from error
        # Đã lấy được flock nên marker hiện có chắc chắn là cờ bị sót.
        if MARKER.exists():
            MARKER.unlink()
            log(f"Đã xóa marker cập nhật bị sót: {MARKER}")
        MARKER.write_text(dt.datetime.now().isoformat() + "\n", encoding="utf-8")
        log("Đã bật khóa nâng cấp dùng chung và marker bảo vệ Config.json")
        yield
    finally:
        MARKER.unlink(missing_ok=True)
        if fcntl is not None:
            fcntl.flock(lock.fileno(), fcntl.LOCK_UN)
        lock.close()


def _update(args):
    if args.rollback is not None:
        rollback_latest(args)
        return
    stamp = dt.datetime.now().strftime("%Y%m%d_%H%M%S")
    rollback = ROOT / "Backup_Upgrade/Manual_Program" / stamp
    with tempfile.TemporaryDirectory(prefix="vbot-manual-program-") as temp:
        work = Path(temp)
        source = safe_extract(acquire_zip(args, work), work / "extract")
        validate_package(source)
        log(f"Gói cập nhật hợp lệ: {source.name}")
        if not args.apply:
            log("Đã hoàn tất chế độ --check-only; không có dữ liệu nào được cập nhật")
            return
        old_config = read_json(ROOT / "Config.json")
        old_release_date, old_version = backup_version_metadata(ROOT / "Version.json")
        manual_backup_limit = backup_limit(old_config, "vbot_program")
        log(f"Giới hạn backup chương trình thủ công: {manual_backup_limit} tệp")
        log("Đã đọc Config.json hiện tại")
        merged_config = merge_config(read_json(source / "Config.json"), old_config)
        log("Đã merge giá trị Config cũ vào mẫu Config mới")
        configured_keep = (
            old_config.get("backup_upgrade", {})
            .get("vbot_program", {})
            .get("upgrade", {})
            .get("keep_file_directory", [])
        )
        if not isinstance(configured_keep, (list, tuple, set)):
            log("keep_file_directory không phải danh sách; bỏ qua cấu hình giữ tệp này")
            configured_keep = []
        keep_entries = {
            str(value).replace("\\", "/").strip("/")
            for value in configured_keep
            if isinstance(value, str) and value.strip("/\\")
        }
        replace_json = {
            str(value).replace("\\", "/").strip("/")
            for value in args.replace_json
            if str(value).strip("/\\")
        }
        log(f"Đã nạp {len(keep_entries)} mục cần giữ từ Config.json, các JSON người dùng hiện có sẽ không bị ghi đè")
        rollback.mkdir(parents=True, exist_ok=False)
        atomic_copy_file(ROOT / "Config.json", rollback / "Config.json")
        log(f"Đã tạo vùng rollback: {rollback}")
        try:
            log("Đang sao chép các tệp chương trình mới...")
            copy_transaction(source, rollback, keep_entries, replace_json)
            atomic_copy_file(rollback / "Config.json", ROOT / "Config.json.bak")
            atomic_json_write(ROOT / "Config.json", merged_config)
            log("Đã cài Config.json mới và tạo Config.json.bak")
            log("Đang áp dụng quyền 0777 cho phần chương trình, bỏ qua toàn bộ html và *.lock...")
            apply_project_permissions(ROOT)
            if not args.no_restart:
                log(f"Đang restart service: {args.service}")
                restarted = service(["restart", args.service])
                if restarted.returncode:
                    raise RuntimeError("Không thể restart service: " + restarted.stdout.strip())
                log("Đã gửi lệnh restart; chờ service khởi động ổn định trong 3 giây...")
                time.sleep(3)
                healthy = service(["is-active", "--quiet", args.service])
                if healthy.returncode:
                    raise RuntimeError("Service không active sau cập nhật: " + healthy.stdout.strip())
                log(f"Service {args.service} đang active")
            else:
                log("Đã bỏ qua restart service theo tùy chọn --no-restart")
            try:
                rollback_archive = package_rollback(
                    rollback, "VBot_Program", old_release_date, old_version, manual_backup_limit
                )
            except Exception as package_error:
                rollback_archive = rollback
                log(f"Không thể nén rollback, thư mục backup được giữ nguyên: {package_error}", error=True)
            log(f"Cập nhật chương trình thành công. Backup rollback: {rollback_archive}")
        except (Exception, KeyboardInterrupt) as update_error:
            reason = "Người dùng đã dừng bằng Ctrl+C" if isinstance(update_error, KeyboardInterrupt) else str(update_error)
            log(f"Cập nhật bị dừng/lỗi: {reason}. Đang rollback chương trình và Config.json", error=True)
            rollback_errors = []
            try:
                rollback_transaction(rollback)
            except Exception as rollback_error:
                rollback_errors.append(f"mã nguồn: {rollback_error}")
                log(f"Rollback mã nguồn phát sinh lỗi: {rollback_error}", error=True)
            try:
                atomic_json_write(ROOT / "Config.json", read_json(rollback / "Config.json"))
                log("Đã khôi phục Config.json cũ")
            except Exception as config_error:
                rollback_errors.append(f"Config.json: {config_error}")
                log(f"Rollback Config.json phát sinh lỗi: {config_error}", error=True)
            if not args.no_restart:
                restart_old = service(["restart", args.service])
                if restart_old.returncode:
                    rollback_errors.append(f"restart bản cũ: {restart_old.stdout.strip()}")
                    log(f"Không thể restart phiên bản cũ: {restart_old.stdout.strip()}", error=True)
            try:
                package_rollback(
                    rollback, "VBot_Program", old_release_date, old_version, manual_backup_limit
                )
            except Exception as package_error:
                rollback_errors.append(f"nén rollback: {package_error}")
                log(f"Không thể nén rollback; thư mục backup được giữ nguyên: {package_error}", error=True)
            detail = "; ".join(rollback_errors)
            if detail:
                raise RuntimeError(f"{reason} | Rollback chưa hoàn chỉnh: {detail}") from update_error
            raise


def update(args):
    if not args.apply and args.rollback is None:
        return _update(args)
    with upgrade_guard():
        return _update(args)

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--repo", default="https://github.com/marion001/VBot_Offline")
    parser.add_argument("--branch", default="main")
    parser.add_argument("--zip", help="Dùng gói ZIP local thay vì tải GitHub")
    parser.add_argument("--timeout", type=int, default=60)
    parser.add_argument("--service", default="VBot_Offline.service")
    parser.add_argument(
        "--replace-json", action="append", default=[],
        help="Cho phép thay thế một JSON hiện có; dùng đường dẫn tương đối và có thể lặp lại",
    )
    parser.set_defaults(apply=True)
    parser.add_argument(
        "--check-only", dest="apply", action="store_false",
        help="Chỉ tải và kiểm tra gói, không cập nhật; mặc định công cụ cập nhật ngay",
    )
    parser.add_argument("--no-restart", action="store_true")
    parser.add_argument(
        "--rollback", nargs="?", const="latest", default=None, metavar="FILE",
        help="Khôi phục FILE backup; nếu không truyền FILE sẽ dùng bản .tar.gz mới nhất",
    )
    args = parser.parse_args()
    check_only = not args.apply and args.rollback is None
    restart_required = not args.no_restart
    # Mọi đường cập nhật đều hoãn restart: kết quả, log và âm báo phải hoàn
    # tất trước. Update Manager hoặc fallback bên dưới sẽ restart cuối cùng.
    args.no_restart = True
    started_at = int(time.time())
    try:
        update(args)
        if check_only:
            log("Kiểm tra gói cập nhật hoàn tất; không ghi kết quả, không phát âm báo và không restart service")
            return 0
        result = {"target": "program", "status": "success", "message": "Cập nhật chương trình VBot thành công", "started_at": started_at, "finished_at": int(time.time()), "restart_required": restart_required, "service": args.service}
        atomic_json_write(UPDATE_RESULT, result)
        finish_without_update_manager(result, restart_required, args.service)
        return 0
    except KeyboardInterrupt:
        log("Đã nhận Ctrl+C; dữ liệu tạm và marker cập nhật đã được dọn dẹp", error=True)
        return 130
    except Exception as error:
        log(f"Lỗi cập nhật chương trình: {error}", error=True)
        result = {"target": "program", "status": "error", "message": f"Cập nhật chương trình VBot thất bại: {error}", "started_at": started_at, "finished_at": int(time.time()), "restart_required": restart_required, "service": args.service}
        atomic_json_write(UPDATE_RESULT, result)
        try:
            finish_without_update_manager(result, restart_required, args.service)
        except Exception as finish_error:
            log(f"Lỗi hoàn tất cập nhật: {finish_error}", error=True)
        return 1

if __name__ == "__main__":
    sys.exit(main())
