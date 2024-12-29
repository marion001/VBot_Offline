'''
Code By: Vũ Tuyển
Facebook: https://www.facebook.com/TWFyaW9uMDAx
'''

import wave
import numpy as np
import struct
from pvrecorder import PvRecorder

#ID của Mic
device_index = 14  #Sử dụng thiết bị mặc định thì đặt là: "-1"

#Thời gian ghi âm (6 giây)
duration = 6

#Tên File âm thanh
output_file = "Test_Microphone.wav"  

# Độ dài frame
frame_length = 512

recorder = PvRecorder(device_index=device_index, frame_length=frame_length)
recorder.start()

frames_per_second = 16000 // frame_length

total_frames = int(duration * frames_per_second)

print(f"Đang ghi âm trong {duration} giây, hãy nói vào thiết bị Mic của bạn trong 6 giây")

frames = []

for _ in range(total_frames):
    pcm = recorder.read()
    frames.extend(pcm)

recorder.stop()

with wave.open(output_file, 'wb') as wf:
    wf.setnchannels(1)
    wf.setsampwidth(2)
    wf.setframerate(16000)
    wf.writeframes(struct.pack('<' + ('h' * len(frames)), *frames))

print(f"File âm thanh đã được lưu tại: {output_file}, hãy mở file lên để nghe, kiểm tra xem mic có hoạt động không")
