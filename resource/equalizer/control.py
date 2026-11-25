"""Cached metadata/control, shared by MQTT and existing SSE. No audio processing."""
import copy
import json
from pathlib import Path
import subprocess
import threading
import time

ROOT = Path(__file__).resolve().parents[2]
_lock = threading.Lock()
_stamp = None
_snapshot = None
_retry_at = 0
_error = ''


def _signature():
    result = []
    for path in (ROOT/'Config.json', ROOT/'resource/equalizer/settings.json'):
        try:
            stat=path.stat();result.append((stat.st_mtime_ns,stat.st_size))
        except FileNotFoundError:
            result.append(None)
    return tuple(result)


def _call(request):
    p=subprocess.run(['php',str(ROOT/'resource/equalizer/mqtt_control.php')],
                     input=json.dumps(request),text=True,encoding='utf-8',capture_output=True,timeout=5)
    try:
        data=json.loads(p.stdout)
    except json.JSONDecodeError as exc:
        detail=(p.stderr or p.stdout or 'PHP EQ CLI error').strip()[:240]
        raise RuntimeError(detail) from exc
    if not data.get('success'):
        raise ValueError(data.get('message','Không cập nhật được EQ'))
    return data


def snapshot():
    global _snapshot,_stamp,_retry_at,_error
    with _lock:
        if time.monotonic()<_retry_at:
            raise RuntimeError(_error)
        stamp=_signature()
        if _snapshot is None or stamp!=_stamp:
            try:
                _snapshot=_call({'action':'snapshot'});_stamp=stamp
                _retry_at=0;_error=''
            except Exception as error:
                _error=str(error);_retry_at=time.monotonic()+5
                raise
        return copy.deepcopy(_snapshot)


def select(preset):
    global _stamp,_snapshot,_retry_at,_error
    if not isinstance(preset,str) or len(preset)>80 or preset=='custom':
        raise ValueError('Chế độ EQ không hợp lệ; Custom chỉ hiển thị trạng thái tùy chỉnh.')
    with _lock:
        _snapshot=_call({'action':'select','id':preset})
        _stamp=None  # Force a subsequent read: a WebUI write may race the CLI.
        _retry_at=0;_error=''
        return copy.deepcopy(_snapshot)


def apply(bands):
    """Apply a partial patch; the PHP file lock protects concurrent band edits."""
    global _stamp, _snapshot, _retry_at, _error
    if not isinstance(bands, dict) or not bands or len(bands)>8:
        raise ValueError('Thông số EQ không hợp lệ')
    with _lock:
        _snapshot=_call({'action':'apply','bands':bands})
        _stamp=None
        _retry_at=0;_error=''
        return copy.deepcopy(_snapshot)
