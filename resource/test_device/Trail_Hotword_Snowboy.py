"""
Code By: Vũ Tuyển
Facebook: https://www.facebook.com/TWFyaW9uMDAx
"""

import wave
import struct
import requests
import os
import glob
from pvrecorder import PvRecorder

# Địa chỉ server để gửi dữ liệu lên Train Hotword
server_url = "http://192.168.14.17:8899"

# ID của Mic Sử dụng thiết bị mặc định thì đặt là "-1"
device_index = -1

# Thời gian ghi âm cho mỗi lần (2 giây)
duration = 2

# Số lần ghi âm yêu cầu tối thiểu là 3
num_recordings = 5

# Xóa các file .wav trước khi ghi âm
for file in glob.glob("*.wav"):
    os.remove(file)

# Nhập tên hotword từ người dùng (bắt buộc nhập)
while True:
    model_name = input("Đặt tên hotword (viết liền không dấu) ví dụ: ok_google hoặc hey_siri: ").strip()
    if model_name:
        break
    print("Bạn phải nhập tên hotword trước khi tiếp tục!")

# Độ dài frame mặc định 512
frame_length = 512

recorder = PvRecorder(device_index=device_index, frame_length=frame_length)
frames_per_second = 16000 // frame_length
total_frames = int(duration * frames_per_second)
recorded_files = []

for i in range(1, num_recordings + 1):
    output_file = f"{i}.wav"
    recorded_files.append(output_file)
    print(f"Bắt đầu ghi âm lần {i}/{num_recordings}, hãy nói vào mic...")
    recorder.start()
    frames = []
    for _ in range(total_frames):
        pcm = recorder.read()
        frames.extend(pcm)
    recorder.stop()
    # Lưu file ghi âm
    with wave.open(output_file, 'wb') as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(16000)
        wf.writeframes(struct.pack('<' + ('h' * len(frames)), *frames))
    print(f"File âm thanh {i}.wav đã được lưu: {os.path.abspath(output_file)}")

recorder.delete()
print("Hoàn tất ghi âm, tiến hành gửi dữ liệu âm thanh lên Server Train...")

#Gửi dữ liệu qua HTTP POST
try:
    files = {"modelName": (None, model_name)}
    # Mở tất cả file và gửi đi
    with requests.Session() as session:
        for i, file in enumerate(recorded_files, start=1):
            files[f"example{i}"] = open(file, "rb")
        response = session.post(f"{server_url}/generate", files=files)
    # Lưu file đầu ra
    output_pmdl = f"{model_name}.pmdl"
    with open(output_pmdl, "wb") as f:
        f.write(response.content)
    print(f"Dữ liệu đã được gửi và tệp `{output_pmdl}` đã được lưu vào: {os.path.abspath(output_pmdl)}")
    #Xóa các file đã thu âm khi hoàn tất chương trình
    for file in glob.glob("*.wav"):
        os.remove(file)
except Exception as e:
    print(f"Lỗi khi gửi dữ liệu: {str(e)}")
