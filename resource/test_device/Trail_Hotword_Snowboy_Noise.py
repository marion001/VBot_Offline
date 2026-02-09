"""
Code By: Vũ Tuyển
Facebook: https://www.facebook.com/TWFyaW9uMDAx
"""

import wave
import requests
import os
import glob
from pvrecorder import PvRecorder
from speexdsp_ns import NoiseSuppression
from array import array
import webrtcvad



# ================== CẤU HÌNH ==================
server_url = "http://192.168.14.13:8899"
# server_url = "http://snowboy-train-vbot.duckdns.org:8899"

#Kiểm soát gửi dữ liệu âm thanh lên Server
SEND_AUDIO_SERVER = True

#id của Mic
device_index = -1

#Thời gian thu âm mỗi lần
duration = 2

#Tối đa số lượng file thu âm
num_recordings = 3

SAMPLE_RATE = 16000
FRAME_LENGTH = 512
# ==============================================

# ===== WebRTC VAD =====
VAD_MODE = 2              # 0–3 (2 là hợp hotword)
VAD_FRAME_MS = 20
VAD_FRAME_SAMPLES = int(SAMPLE_RATE * VAD_FRAME_MS / 1000)  # 320

VAD_START_FRAMES = 3      # ~60ms
VAD_END_FRAMES = 8        # ~160ms

vad = webrtcvad.Vad(VAD_MODE)

#Xóa các file .wav cũ
for file in glob.glob("*.wav"):
    os.remove(file)

#Nhập tên hotword
while True:
    model_name = input("Đặt tên hotword (viết liền không dấu) ví dụ: ok_google hoặc hey_siri: ").strip()
    if model_name:
        break
    print("Bạn phải nhập tên hotword trước khi tiếp tục!")

# ===== Khởi tạo Recorder + Noise Suppression =====
recorder = PvRecorder(device_index=device_index, frame_length=FRAME_LENGTH)
Noise_STT = NoiseSuppression.create(FRAME_LENGTH, SAMPLE_RATE)
frames_per_second = SAMPLE_RATE // FRAME_LENGTH
total_frames = int(duration * frames_per_second)
recorded_files = []

# ================== GHI ÂM ==================
# ================== GHI ÂM ==================
for i in range(1, num_recordings + 1):
    output_file = f"{i}.wav"
    recorded_files.append(output_file)
    print(f"Bắt đầu ghi âm lần {i}/{num_recordings}, hãy nói vào mic...")
    recorder.start()
    started = False
    speech_count = 0
    silence_count = 0
    with wave.open(output_file, 'wb') as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(SAMPLE_RATE)
        for _ in range(total_frames):
            pcm = recorder.read()                # 512 samples
            pcm_arr = array('h', pcm)
            pcm_bytes = pcm_arr.tobytes()
            #Noise Suppression (512)
            pcm_bytes = Noise_STT.process(pcm_bytes)
            #==== WebRTC VAD (chia 512 -> 320 để check) ====
            is_speech = False
            offset = 0
            while offset + VAD_FRAME_SAMPLES * 2 <= len(pcm_bytes):
                frame = pcm_bytes[offset: offset + VAD_FRAME_SAMPLES * 2]
                offset += VAD_FRAME_SAMPLES * 2
                if vad.is_speech(frame, SAMPLE_RATE):
                    is_speech = True
                    break
            if is_speech:
                speech_count += 1
                silence_count = 0
            else:
                silence_count += 1
                speech_count = 0
            #Chưa bắt đầu nói → bỏ frame
            if not started:
                if speech_count >= VAD_START_FRAMES:
                    started = True
                    wf.writeframes(pcm_bytes)   # ghi NGUYÊN 512
                continue
            #Đã bắt đầu nói → ghi
            wf.writeframes(pcm_bytes)
            #Kết thúc khi im lặng đủ lâu
            if silence_count >= VAD_END_FRAMES:
                break
    recorder.stop()
    print(f"Đã lưu (Noise + WebRTC VAD): {os.path.abspath(output_file)}")
recorder.delete()

# ================== GỬI SERVER ==================
if SEND_AUDIO_SERVER:
    print("Hoàn tất ghi âm, gửi dữ liệu lên Server Train...")
    try:
        files = {"modelName": (None, model_name)}
        with requests.Session() as session:
            for i, file in enumerate(recorded_files, start=1):
                files[f"example{i}"] = open(file, "rb")
            response = session.post(
                f"{server_url}/generate",
                files=files,
                timeout=120
            )
        output_pmdl = f"{model_name}.pmdl"
        with open(output_pmdl, "wb") as f:
            f.write(response.content)
        print(f"Đã tạo model: {os.path.abspath(output_pmdl)}")
        #Xóa file wav sau khi train
        #for file in glob.glob("*.wav"):
            #os.remove(file)
    except Exception as e:
        print(f"Lỗi khi gửi dữ liệu: {str(e)}")
