#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git

Script trung gian gọi từ WebUI PHP (html/includes/php_ajax/AI_Knowledge.php)
để lập chỉ mục SQLite FTS5 & Vector Embedding (kb_vectors) cho từng tệp hoặc toàn kho,
đồng thời trích xuất dữ liệu bảng chỉ mục (kb_chunks) và bảng vector (kb_vectors) lên WebUI.
- Giả lập module `Lib` tối giản từ `Config.json` trong `sys.modules["Lib"]` TRƯỚC KHI
  import `AI_Knowledge_Base` (cả bản `.py` lẫn `.so`), tuyệt đối không nạp `Lib.py`
  hay các thư viện phần cứng âm thanh/mic (`pvrecorder`, `alsaaudio`, `GPIO`...).
"""

import base64
import datetime
import json
import os
import sqlite3
import sys
import tarfile
import types

if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8")
    except Exception:
        pass


def _build_lightweight_lib(vbot_root):
    cfg_path = os.path.join(vbot_root, "Config.json")
    cfg = {}
    if os.path.isfile(cfg_path):
        try:
            with open(cfg_path, "r", encoding="utf-8-sig") as f:
                loaded = json.load(f)
                if isinstance(loaded, dict):
                    cfg = loaded
        except Exception:
            cfg = {}

    pipeline_cfg = cfg.get("processing_pipeline") if isinstance(cfg.get("processing_pipeline"), dict) else {}
    gemini_cfg = (
        cfg.get("virtual_assistant", {}).get("google_gemini", {})
        if isinstance(cfg.get("virtual_assistant"), dict)
        else {}
    )
    raw_keys = gemini_cfg.get("api_key", []) if isinstance(gemini_cfg, dict) else []
    if isinstance(raw_keys, str):
        key_list = [k.strip() for k in raw_keys.splitlines() if k.strip()]
    elif isinstance(raw_keys, list):
        key_list = [str(k).strip() for k in raw_keys if str(k).strip()]
    else:
        key_list = []

    groq_cfg = (
        cfg.get("virtual_assistant", {}).get("groq_ai", {})
        if isinstance(cfg.get("virtual_assistant"), dict)
        else {}
    )
    raw_groq = groq_cfg.get("api_key", []) if isinstance(groq_cfg, dict) else []
    if isinstance(raw_groq, str):
        groq_list = [k.strip() for k in raw_groq.splitlines() if k.strip()]
    elif isinstance(raw_groq, list):
        groq_list = [str(k).strip() for k in raw_groq if str(k).strip()]
    else:
        groq_list = []

    ai_engine_dir = os.path.join(vbot_root, "resource", "ai_engine")
    lib_mod = types.ModuleType("Lib")
    lib_mod.vbot_path = vbot_root
    lib_mod.config = cfg
    lib_mod.ai_engine_dir = ai_engine_dir
    lib_mod.ai_knowledge_active = bool(pipeline_cfg.get("ai_knowledge_active", True))
    lib_mod.ai_knowledge_vector_active = bool(pipeline_cfg.get("ai_knowledge_vector_active", True))
    lib_mod.ai_knowledge_dir = os.path.join(ai_engine_dir, "knowledge")
    lib_mod.ai_knowledge_index_db = os.path.join(ai_engine_dir, "knowledge_index.sqlite3")
    lib_mod.ai_knowledge_catalog_json = os.path.join(ai_engine_dir, "knowledge_catalog.json")
    lib_mod.google_gemini_api_key_list = key_list
    lib_mod.google_gemini_api_key = key_list[0] if key_list else ""
    lib_mod.groq_api_key_list = groq_list
    lib_mod.log_exception = lambda *args, **kwargs: None
    lib_mod.show_log = lambda *args, **kwargs: None
    return lib_mod


def _chmod_0777(path):
    try:
        if path and os.path.exists(path):
            os.chmod(path, 0o777)
    except OSError:
        pass


def _create_backup_tar(kb, backup_dir, max_keep=5):
    """Tạo file sao lưu .tar.gz chứa knowledge/, knowledge_catalog.json, knowledge_index.sqlite3 và tự xoay vòng giữ tối đa max_keep file mới nhất."""
    parent_bk = os.path.dirname(os.path.abspath(backup_dir))
    if parent_bk:
        os.makedirs(parent_bk, exist_ok=True)
        _chmod_0777(parent_bk)
    os.makedirs(backup_dir, exist_ok=True)
    _chmod_0777(backup_dir)

    kb_dir = kb.get_knowledge_dir()
    db_path = kb.get_index_db_path()
    cat_path = kb.get_catalog_json_path()

    # Flush SQLite WAL vào file chính trước khi đóng gói để bản sao lưu nhất quán 100%
    if os.path.isfile(db_path):
        try:
            conn = sqlite3.connect(db_path, timeout=5.0)
            try:
                conn.execute("PRAGMA wal_checkpoint(TRUNCATE)")
            finally:
                conn.close()
            _chmod_0777(db_path)
            _chmod_0777(db_path + "-wal")
            _chmod_0777(db_path + "-shm")
        except Exception:
            pass

    ts = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
    out_name = f"VBot_AI_Knowledge_{ts}.tar.gz"
    out_path = os.path.join(backup_dir, out_name)
    files_packed = 0

    try:
        with tarfile.open(out_path, "w:gz") as tf:
            if os.path.isdir(kb_dir):
                for entry in sorted(os.listdir(kb_dir)):
                    if not entry or entry.startswith("."):
                        continue
                    full = os.path.join(kb_dir, entry)
                    if os.path.isfile(full):
                        tf.add(full, arcname="knowledge/" + entry)
                        files_packed += 1
            if os.path.isfile(db_path):
                tf.add(db_path, arcname="knowledge_index.sqlite3")
            if os.path.isfile(cat_path):
                tf.add(cat_path, arcname="knowledge_catalog.json")
        _chmod_0777(out_path)
    except Exception as exc:
        try:
            if os.path.exists(out_path):
                os.remove(out_path)
        except OSError:
            pass
        return {"ok": False, "message": f"Không thể tạo tệp sao lưu .tar.gz: {exc}"}

    # Tự động xóa file cũ nhất nếu vượt quá max_keep (mặc định 5 file)
    deleted_old = []
    try:
        tar_files = []
        for fn in os.listdir(backup_dir):
            if fn.endswith(".tar.gz") or fn.endswith(".tgz"):
                fp = os.path.join(backup_dir, fn)
                if os.path.isfile(fp):
                    tar_files.append((os.path.getmtime(fp), fn, fp))
        tar_files.sort(key=lambda item: item[0], reverse=True)
        for _, old_fn, old_fp in tar_files[int(max_keep):]:
            try:
                os.remove(old_fp)
                deleted_old.append(old_fn)
            except OSError:
                pass
    except OSError:
        pass

    return {
        "ok": True,
        "filename": out_name,
        "size": os.path.getsize(out_path) if os.path.isfile(out_path) else 0,
        "files_packed": files_packed,
        "deleted_old": deleted_old,
    }


def _restore_backup_tar(kb, tar_path):
    """Khôi phục an toàn Kho Tri Thức từ tệp .tar.gz (chống path traversal, đặt quyền 0777 cho mọi file)."""
    if not tar_path or not os.path.isfile(tar_path):
        return {"ok": False, "message": "Không tìm thấy tệp sao lưu .tar.gz cần khôi phục."}

    kb_dir = kb.get_knowledge_dir()
    db_path = kb.get_index_db_path()
    cat_path = kb.get_catalog_json_path()
    ai_engine_parent = os.path.dirname(os.path.abspath(kb_dir))
    if ai_engine_parent:
        os.makedirs(ai_engine_parent, exist_ok=True)
        _chmod_0777(ai_engine_parent)
    os.makedirs(kb_dir, exist_ok=True)
    _chmod_0777(kb_dir)
    allowed_exts = set(getattr(kb, "SUPPORTED_EXTENSIONS", ()))

    restored_docs = 0
    restored_db = False
    restored_cat = False

    try:
        with tarfile.open(tar_path, "r:gz") as tf:
            for member in tf.getmembers():
                if not member.isfile():
                    continue
                mname = str(member.name or "").replace("\\", "/").lstrip("./")
                if not mname or ".." in mname.split("/") or mname.startswith("/"):
                    continue

                if mname == "knowledge_catalog.json":
                    fobj = tf.extractfile(member)
                    if fobj is not None:
                        raw = fobj.read()
                        try:
                            json.loads(raw.decode("utf-8-sig"))
                            with open(cat_path, "wb") as out_f:
                                out_f.write(raw)
                            _chmod_0777(cat_path)
                            restored_cat = True
                        except Exception:
                            pass
                    continue

                if mname == "knowledge_index.sqlite3":
                    fobj = tf.extractfile(member)
                    if fobj is not None:
                        raw = fobj.read()
                        if len(raw) > 100:
                            for suffix in ("-wal", "-shm"):
                                try:
                                    if os.path.exists(db_path + suffix):
                                        os.remove(db_path + suffix)
                                except OSError:
                                    pass
                            with open(db_path, "wb") as out_f:
                                out_f.write(raw)
                            _chmod_0777(db_path)
                            restored_db = True
                    continue

                if mname.startswith("knowledge/"):
                    base_fn = os.path.basename(mname[len("knowledge/"):])
                    if not base_fn or base_fn.startswith("."):
                        continue
                    ext = os.path.splitext(base_fn)[1].lower()
                    if allowed_exts and ext not in allowed_exts:
                        continue
                    fobj = tf.extractfile(member)
                    if fobj is not None:
                        dest = os.path.join(kb_dir, base_fn)
                        with open(dest, "wb") as out_f:
                            out_f.write(fobj.read())
                        _chmod_0777(dest)
                        restored_docs += 1
    except (tarfile.TarError, OSError, EOFError) as exc:
        return {"ok": False, "message": f"Tệp sao lưu .tar.gz bị lỗi hoặc không hợp lệ: {exc}"}

    if restored_docs == 0 and not restored_db and not restored_cat:
        return {"ok": False, "message": "Không tìm thấy dữ liệu Kho Tri Thức hợp lệ bên trong tệp .tar.gz này."}

    return {
        "ok": True,
        "restored_docs": restored_docs,
        "restored_db": restored_db,
        "restored_cat": restored_cat,
    }


def main():
    default_root = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
    mode = "reindex"
    vbot_root = default_root
    target_arg = ""
    extra_payload = {}

    if len(sys.argv) >= 2:
        first = sys.argv[1].strip()
        if first.startswith("b64:"):
            # Payload JSON mã hóa Base64 thuần ASCII từ PHP để bảo toàn 100% tên tệp tiếng Việt / Unicode
            raw_json = base64.b64decode(first[4:]).decode("utf-8")
            payload = json.loads(raw_json)
            if isinstance(payload, dict):
                extra_payload = payload
                mode = str(payload.get("mode") or "reindex").strip()
                if payload.get("root"):
                    vbot_root = os.path.abspath(str(payload["root"]).strip())
                target_arg = str(payload.get("target") or "").strip()
        elif first in ("reindex", "index_file", "inspect", "import_url", "refresh_url", "test_search", "auto_tag"):
            mode = first
            if len(sys.argv) >= 3 and sys.argv[2].strip():
                vbot_root = os.path.abspath(sys.argv[2].strip())
            if len(sys.argv) >= 4:
                target_arg = sys.argv[3].strip()
        else:
            # Tương thích ngược: AI_Knowledge_Indexer.py <vbot_root> [force]
            vbot_root = os.path.abspath(first)
            if len(sys.argv) >= 3:
                target_arg = sys.argv[2].strip()

    if vbot_root not in sys.path:
        sys.path.insert(0, vbot_root)

    # Đăng ký Lib giả lập vào sys.modules để AI_Knowledge_Base (.py hoặc .so) không nạp Lib.py thật
    sys.modules["Lib"] = _build_lightweight_lib(vbot_root)

    import AI_Knowledge_Base as kb

    if mode == "index_file":
        res = kb.index_single_file(target_arg, force=True, build_vectors=True)
        stats_res = kb.get_stats()
        print(json.dumps({"result": res, "stats": stats_res}, ensure_ascii=False))
    elif mode == "inspect":
        data = kb.inspect_index_data(filename=target_arg or None, limit=250)
        print(json.dumps({"inspect": data}, ensure_ascii=False))
    elif mode == "import_url":
        cat_val = str(extra_payload.get("category") or "Web").strip() or "Web"
        title_val = str(extra_payload.get("title") or "").strip() or None
        res = kb.import_url_document(target_arg, title=title_val, category=cat_val, build_vectors=True)
        print(json.dumps({"result": res}, ensure_ascii=False))
    elif mode == "refresh_url":
        res = kb.refresh_url_document(target_arg, build_vectors=True)
        print(json.dumps({"result": res}, ensure_ascii=False))
    elif mode == "test_search":
        cat_val = str(extra_payload.get("category") or "").strip() or None
        hits = kb.search(target_arg, top_k=5, category=cat_val, include_neighbors=True)
        print(json.dumps({"hits": hits}, ensure_ascii=False))
    elif mode == "auto_tag":
        res = kb.auto_tag_document(target_arg)
        print(json.dumps({"result": res}, ensure_ascii=False))
    elif mode == "backup_tar":
        max_keep = int(extra_payload.get("max_keep") or 5)
        res = _create_backup_tar(kb, target_arg, max_keep=max_keep)
        print(json.dumps({"result": res}, ensure_ascii=False))
    elif mode == "restore_tar":
        res = _restore_backup_tar(kb, target_arg)
        print(json.dumps({"result": res}, ensure_ascii=False))
    else:
        force_flag = str(target_arg).lower() in ("1", "true", "yes", "force")
        sync_res = kb.sync_index(force=force_flag, build_vectors=True)
        stats_res = kb.get_stats()
        print(json.dumps({"sync": sync_res, "stats": stats_res}, ensure_ascii=False))


if __name__ == "__main__":
    main()
