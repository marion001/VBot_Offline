"""Multiroom speaker sink: paced ALSA writes plus post-volume AEC reference."""
import argparse
from pathlib import Path
import sys
import threading

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
from Lib_AEC3 import _load_backend


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--device', required=True)
    parser.add_argument('--rate', type=int, required=True, choices=(44100, 48000))
    parser.add_argument('--channels', type=int, required=True, choices=(2,))
    parser.add_argument('--latency', type=int, default=100)
    args = parser.parse_args()
    _, _, Playback = _load_backend()
    from _vbot_media_aec3.reference import ReferenceSender
    playback = Playback(args.device, args.latency, threading.Event(), args.rate, args.channels)
    sender = ReferenceSender(args.rate, playback)
    pending = bytearray()
    try:
        while True:
            pcm = sys.stdin.buffer.read1(args.rate // 100 * 4)
            if not pcm:
                break
            pending.extend(pcm)
            size = len(pending) // 4 * 4
            if size:
                playback.write(bytes(pending[:size]), sender.send)
                del pending[:size]
        playback.drain()
    finally:
        sender.close()
        playback.close()


if __name__ == '__main__':
    main()
