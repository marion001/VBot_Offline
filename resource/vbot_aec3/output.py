"""Multiroom speaker sink: paced ALSA writes plus post-volume AEC reference."""
import argparse
from pathlib import Path
import sys
import threading
import json
import os
import time
import re
import stat

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
from Lib_AEC3 import _load_backend


def cleanup_stale_diagnostics(directory):
    """Only remove our regular diagnostic files whose owning PID is gone."""
    for path in directory.glob('output-*'):
        match = re.fullmatch(r'output-([1-9][0-9]*)\.(json|tmp)', path.name)
        if not match:
            continue
        try:
            info = path.lstat()
            if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid():
                continue
            try:
                os.kill(int(match[1]), 0)
            except ProcessLookupError:
                path.unlink(missing_ok=True)
            except PermissionError:
                pass  # A live process owned by someone else is not stale.
        except OSError:
            pass


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--device', required=True)
    parser.add_argument('--rate', type=int, required=True, choices=(44100, 48000))
    parser.add_argument('--channels', type=int, required=True, choices=(2,))
    parser.add_argument('--latency', type=int, default=100)
    args = parser.parse_args()
    _, _, Playback = _load_backend()
    from _vbot_media_aec3.reference import ReferenceSender
    playback = Playback(args.device, args.latency, threading.Event(), args.rate, args.channels, equalizer=True)
    sender = ReferenceSender(args.rate, playback)
    pending = bytearray()
    status_path = Path(sender.path).parent / ('output-' + str(os.getpid()) + '.json')
    cleanup_stale_diagnostics(status_path.parent)
    last_report = 0
    read_max_ms = write_max_ms = 0
    try:
        while True:
            before_read = time.monotonic()
            pcm = sys.stdin.buffer.read1(args.rate // 100 * 4)
            read_max_ms = max(read_max_ms, (time.monotonic() - before_read) * 1000)
            if not pcm:
                break
            pending.extend(pcm)
            size = len(pending) // 4 * 4
            if size:
                before_write = time.monotonic()
                playback.write(bytes(pending[:size]), sender.send)
                write_max_ms = max(write_max_ms, (time.monotonic() - before_write) * 1000)
                del pending[:size]
                now = time.monotonic()
                if now - last_report >= 5 and playback.route_enabled:
                    try:
                        temporary = status_path.with_suffix('.tmp')
                        temporary.write_text(json.dumps(dict(at=now, pid=os.getpid(), device=playback.device,
                            delay_ms=playback.delay_ms, underruns=playback.underruns,
                            recoveries=playback.recoveries, fallbacks=playback.route_fallbacks,
                            read_max_ms=round(read_max_ms, 2), write_max_ms=round(write_max_ms, 2))))
                        temporary.replace(status_path)
                    except OSError:
                        pass
                    last_report = now
                    read_max_ms = write_max_ms = 0
        playback.drain()
    finally:
        try:
            status_path.unlink(missing_ok=True)
            status_path.with_suffix('.tmp').unlink(missing_ok=True)
        finally:
            try:
                sender.close()
            finally:
                playback.close()


if __name__ == '__main__':
    main()
