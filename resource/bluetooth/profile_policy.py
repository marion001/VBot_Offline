"""Shared A2DP profile configuration; leave BlueZ pairing and PAN independent."""
import json
from pathlib import Path
import threading

_lock = threading.Lock()
_cache = {}


def read_roles(path=None):
    path = Path(path) if path else Path(__file__).resolve().parents[2] / 'Config.json'
    try:
        info = path.stat()
        signature = (info.st_mtime_ns, info.st_size, info.st_ino)
        with _lock:
            cached = _cache.get(str(path))
            if cached and cached[0] == signature:
                return dict(cached[1])
            data = json.loads(path.read_text(encoding='utf-8-sig')).get('bluetooth', {})
            value = {role: data.get('a2dp_'+role+'_active', True) is True for role in ('sink', 'source')}
            _cache[str(path)] = (signature, value)
            return dict(value)
    except (OSError, ValueError, TypeError, AttributeError):
        with _lock:
            previous = _cache.get(str(path))
            if previous:
                return dict(previous[1])
        return {'sink': True, 'source': True}
