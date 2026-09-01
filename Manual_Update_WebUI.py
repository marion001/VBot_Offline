#!/usr/bin/env python3
'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

"""Cập nhật thủ công giao diện VBot khi chính WebUI bị lỗi."""

#Cập nhật $:> python3 Manual_Update_WebUI.py

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
HTML_ROOT = ROOT / "html"
ERROR_LOG = ROOT / "resource/log/Vbot_error.log"
UPGRADE_LOCK = ROOT / ".vbot_upgrade.lock"
UPDATE_RESULT = ROOT / ".vbot_update_result.json"

for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8", errors="replace")
    except (AttributeError, OSError):
        pass

def log(message, error=False):
    level = "ERROR" if error else "INFO"
    line = f"[{dt.datetime.now():%H:%M:%S %d-%m-%Y}] [MANUAL WEBUI {level}] {message}"
    print(line)
    if error:
        ERROR_LOG.parent.mkdir(parents=True, exist_ok=True)
        with ERROR_LOG.open("a", encoding="utf-8") as handle:
            handle.write(line + "\n")

def read_json_object(path):
    with Path(path).open("r", encoding="utf-8-sig") as handle:
        value = json.load(handle)
    if not isinstance(value, dict):
        raise ValueError(f"{path} không chứa JSON object")
    return value

def backup_version_metadata(path):
    try:
        data = read_json_object(path)
    except (OSError, ValueError, json.JSONDecodeError):
        data = {}
    clean = lambda value, fallback: re.sub(r"[^0-9A-Za-z._-]", "-", str(value or fallback))
    return clean(data.get("releaseDate"), "unknown-date"), clean(data.get("version"), "unknown-version")

def backup_limit(config_path, section):
    try:
        config = read_json_object(config_path)
        limit = int(config["backup_upgrade"][section]["backup"]["limit_backup_files"])
        return limit if limit >= 1 else 5
    except (OSError, KeyError, TypeError, ValueError, json.JSONDecodeError):
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
            log(f"Đã xóa backup WebUI cũ: {item.name}")
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


def atomic_json_write(path, value):
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    fd, temp_name = tempfile.mkstemp(prefix=".update-result-", dir=str(path.parent))
    try:
        with os.fdopen(fd, "w", encoding="utf-8") as handle:
            json.dump(value, handle, ensure_ascii=False, indent=2)
            handle.flush()
            os.fsync(handle.fileno())
        os.replace(temp_name, path)
    finally:
        if os.path.exists(temp_name):
            os.unlink(temp_name)

def apply_project_permissions(root):
    root = Path(root)
    excluded_roots = {root / "Backup_Upgrade"}
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
        f"Đã chmod 0777 cho {changed} file/thư mục trong {root}, "
        f"{already_correct} lỗi chmod được bỏ qua vì quyền đã là 0777; "
        f"đã loại trừ Backup_Upgrade, liên kết mềm và {skipped_locks} file *.lock"
    )
    if permission_errors:
        log(
            f"Có {len(permission_errors)} file/thư mục không thể chmod; "
            "bản cập nhật vẫn tiếp tục vì đây chỉ là lỗi metadata quyền",
            error=True,
        )
        for detail in permission_errors:
            log(f"Không thể chmod 0777: {detail}", error=True)

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

def restore_full_tree(source, destination):
    restored = 0
    for item in source.rglob("*"):
        if item.is_file():
            atomic_copy_file(item, destination / item.relative_to(source))
            restored += 1
    if restored == 0:
        raise RuntimeError("Backup không chứa tệp nào có thể khôi phục")
    return restored

def rollback_latest(_args):
    search_paths = (
        ROOT / "Backup_Upgrade/Manual_WebUI",
        ROOT / "html/Backup_Upgrade/Backup_Interface",
    )
    archive = requested_backup(_args.rollback, search_paths, "VBot_Interface")
    log(f"Đã chọn bản sao lưu WebUI mới nhất: {archive}")
    with tempfile.TemporaryDirectory(prefix="vbot-manual-webui-rollback-") as temp:
        extracted = safe_extract_tar(archive, Path(temp) / "extract")
        if (extracted / "created.json").is_file():
            log("Đã nhận diện backup rollback thủ công, đang khôi phục các tệp thay đổi...")
            rollback_transaction(extracted)
        else:
            log("Đã nhận diện backup đầy đủ từ WebUI, đang khôi phục giao diện...")
            restored = restore_full_tree(extracted, HTML_ROOT)
            log(f"Đã khôi phục {restored} tệp WebUI từ backup đầy đủ")
        apply_project_permissions(HTML_ROOT)
    log(f"Rollback WebUI thành công từ: {archive.name}")

def php_cli():
    candidates = [shutil.which("php"), "/usr/bin/php", "/usr/local/bin/php"]
    for candidate in candidates:
        if not candidate:
            continue
        path = Path(candidate)
        if path.is_file() and os.access(path, os.X_OK):
            return str(path.resolve())
    raise RuntimeError("Không tìm thấy PHP CLI có quyền thực thi")

def validate_package(repo_root):
    source = repo_root / "html"
    log("Đang kiểm tra các tệp WebUI bắt buộc...")
    for name in ("Version.json", "Configuration.php", "_Dashboard.php", "_Program.php"):
        path = source / name
        if not path.is_file() or path.stat().st_size == 0:
            raise RuntimeError(f"Thiếu tệp giao diện bắt buộc: html/{name}")
    with (source / "Version.json").open("r", encoding="utf-8-sig") as handle:
        if not isinstance(json.load(handle), dict):
            raise RuntimeError("html/Version.json không hợp lệ")
    binary = php_cli()
    log(f"Đã tìm thấy PHP CLI: {binary}")
    checked = 0
    log("Đang kiểm tra cú pháp toàn bộ tệp PHP...")
    for path in source.rglob("*.php"):
        result = subprocess.run(
            [binary, "-l", str(path)],
            stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True,
        )
        if result.returncode:
            raise RuntimeError(f"PHP lỗi tại {path.relative_to(source)}: {result.stdout.strip()}")
        checked += 1
    if checked == 0:
        raise RuntimeError("Không tìm thấy tệp PHP trong gói WebUI")
    return source, checked

def should_keep(relative, keep_paths):
    value = relative.as_posix().strip("/")
    return any(value == keep or value.startswith(keep + "/") for keep in keep_paths)

def copy_transaction(source, rollback, keep_paths, replace_json):
    files_root = rollback / "files"
    created = []
    preserved_json = []
    created_file = rollback / "created.json"
    created_file.write_text("[]", encoding="utf-8")
    for item in source.rglob("*"):
        if not item.is_file():
            continue
        relative = item.relative_to(source)
        if should_keep(relative, keep_paths):
            continue
        destination = HTML_ROOT / relative
        relative_text = relative.as_posix()
        preserve_existing_json = (
            destination.exists()
            and relative.suffix.lower() == ".json"
            and relative_text != "Version.json"
            and not relative_text.startswith("assets/vendor/")
            and relative_text not in replace_json
            and relative.name not in replace_json
        )
        if preserve_existing_json:
            preserved_json.append(relative_text)
            continue
        if destination.exists():
            backup = files_root / relative
            atomic_copy_file(destination, backup)
        else:
            created.append(relative.as_posix())
            created_file.write_text(json.dumps(created, indent=2), encoding="utf-8")
        atomic_copy_file(item, destination)
    log(f"Đã sao chép WebUI mới; có {len(created)} tệp mới được tạo")
    if preserved_json:
        log(f"Đã giữ lại {len(preserved_json)} tệp JSON dữ liệu WebUI:")
        for relative in preserved_json:
            print(f"  - html/{relative}")

def rollback_transaction(rollback):
    log("Đang khôi phục WebUI từ rollback...")
    created_file = rollback / "created.json"
    if created_file.is_file():
        for relative in json.loads(created_file.read_text(encoding="utf-8")):
            target = HTML_ROOT / relative
            if target.is_file() or target.is_symlink():
                target.unlink()
    files_root = rollback / "files"
    if files_root.is_dir():
        for backup in files_root.rglob("*"):
            if backup.is_file():
                destination = HTML_ROOT / backup.relative_to(files_root)
                atomic_copy_file(backup, destination)
    log("Đã khôi phục WebUI cũ")

def _update(args):
    if args.rollback is not None:
        rollback_latest(args)
        return
    stamp = dt.datetime.now().strftime("%Y%m%d_%H%M%S")
    rollback = ROOT / "Backup_Upgrade/Manual_WebUI" / stamp
    keep_paths = {value.strip("/\\") for value in args.keep if value.strip("/\\")}
    replace_json = {
        str(value).replace("\\", "/").strip("/")
        for value in args.replace_json
        if str(value).strip("/\\")
    }
    with tempfile.TemporaryDirectory(prefix="vbot-manual-webui-") as temp:
        work = Path(temp)
        repo_root = safe_extract(acquire_zip(args, work), work / "extract")
        source, checked = validate_package(repo_root)
        log(f"Gói WebUI hợp lệ, đã lint {checked} tệp PHP")
        if not args.apply:
            log("Đã hoàn tất chế độ --check-only, không có dữ liệu nào được cập nhật")
            return
        old_release_date, old_version = backup_version_metadata(HTML_ROOT / "Version.json")
        manual_backup_limit = backup_limit(ROOT / "Config.json", "web_interface")
        log(f"Giới hạn backup WebUI thủ công: {manual_backup_limit} tệp")
        rollback.mkdir(parents=True, exist_ok=False)
        log(f"Đã tạo vùng rollback: {rollback}")
        try:
            log("Đang sao chép các tệp WebUI mới...")
            copy_transaction(source, rollback, keep_paths, replace_json)
            log("Đang áp dụng quyền 0777 cho WebUI, bỏ qua html/Backup_Upgrade và *.lock...")
            apply_project_permissions(HTML_ROOT)
            try:
                rollback_archive = package_rollback(
                    rollback, "VBot_Interface", old_release_date, old_version, manual_backup_limit
                )
            except Exception as package_error:
                rollback_archive = rollback
                log(f"Không thể nén rollback, thư mục backup được giữ nguyên: {package_error}", error=True)
            log(f"Cập nhật WebUI thành công. Backup rollback: {rollback_archive}")
        except (Exception, KeyboardInterrupt) as update_error:
            reason = "Người dùng đã dừng bằng Ctrl+C" if isinstance(update_error, KeyboardInterrupt) else str(update_error)
            log(f"Cập nhật WebUI bị dừng/lỗi: {reason}. Đang rollback", error=True)
            try:
                rollback_transaction(rollback)
            except Exception as rollback_error:
                log(f"Rollback WebUI phát sinh lỗi: {rollback_error}", error=True)
                raise RuntimeError(
                    f"{reason} | Rollback WebUI chưa hoàn chỉnh: {rollback_error}"
                ) from update_error
            try:
                package_rollback(
                    rollback, "VBot_Interface", old_release_date, old_version, manual_backup_limit
                )
            except Exception as package_error:
                log(f"Không thể nén rollback, thư mục backup được giữ nguyên: {package_error}", error=True)
            raise


@contextlib.contextmanager
def upgrade_guard():
    """Không cho Program và WebUI ghi file cùng một lúc."""
    lock = UPGRADE_LOCK.open("a+")
    try:
        if fcntl is not None:
            try:
                fcntl.flock(lock.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
            except BlockingIOError as error:
                raise RuntimeError("Một tiến trình cập nhật khác đang hoạt động") from error
        yield
    finally:
        if fcntl is not None:
            fcntl.flock(lock.fileno(), fcntl.LOCK_UN)
        lock.close()


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
    parser.add_argument(
        "--keep", action="append", default=["includes/other_data"],
        help="Đường dẫn tương đối trong html cần giữ lại; có thể dùng nhiều lần",
    )
    parser.add_argument(
        "--replace-json", action="append", default=[],
        help="Cho phép thay thế một JSON hiện có; dùng đường dẫn tương đối trong html",
    )
    parser.set_defaults(apply=True)
    parser.add_argument(
        "--check-only", dest="apply", action="store_false",
        help="Chỉ tải và kiểm tra gói, không cập nhật; mặc định công cụ cập nhật ngay",
    )
    parser.add_argument(
        "--rollback", nargs="?", const="latest", default=None, metavar="FILE",
        help="Khôi phục FILE backup, nếu không truyền FILE sẽ dùng bản .tar.gz mới nhất",
    )
    args = parser.parse_args()
    started_at = int(time.time())
    try:
        update(args)
        result = {"target": "interface", "status": "success", "message": "Cập nhật giao diện VBot thành công", "started_at": started_at, "finished_at": int(time.time())}
        atomic_json_write(UPDATE_RESULT, result)
        return 0
    except KeyboardInterrupt:
        log("Đã nhận Ctrl+C, dữ liệu tạm của quá trình cập nhật đã được dọn dẹp", error=True)
        return 130
    except Exception as error:
        log(f"Lỗi cập nhật WebUI: {error}", error=True)
        result = {"target": "interface", "status": "error", "message": f"Cập nhật giao diện VBot thất bại: {error}", "started_at": started_at, "finished_at": int(time.time())}
        atomic_json_write(UPDATE_RESULT, result)
        return 1

if __name__ == "__main__":
    sys.exit(main())
