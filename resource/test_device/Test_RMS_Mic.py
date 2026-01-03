'''
Code By: Vũ Tuyển
Facebook: https://www.facebook.com/TWFyaW9uMDAx
'''

import time
import math
from pvrecorder import PvRecorder

def calc_rms(pcm):
    n = len(pcm)
    if n == 0:
        return 0
    s = 0
    for x in pcm:
        s += x * x
    return int((s / n) ** 0.5)

def main():
    recorder = None
    try:
        recorder = PvRecorder(
            device_index=-1,     #ID mic mặc định
            frame_length=512     #~32ms @16kHz
        )
        recorder.start()
        print("🎤 Đang đo RMS từ microphone (Ctrl+C để dừng)\n")
        while True:
            pcm = recorder.read()      #list[int] int16
            rms = calc_rms(pcm)
            print(f"RMS = {rms}")
            time.sleep(0.05)           #giảm spam log
    except KeyboardInterrupt:
        print("\n Dừng đo RMS")
    finally:
        if recorder:
            recorder.stop()
            recorder.delete()

if __name__ == "__main__":
    main()