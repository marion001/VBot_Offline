import time
import signal
import numpy as np
import snowboydecoder
# Dùng PvRecorder để lấy dữ liệu âm thanh
from pvrecorder import PvRecorder

# Danh sách hotword models
MODEL_PATHS = ["hotword/e_cu.pmdl", "hotword/smart_mirror.umdl"]
SENSITIVITY = [0.5, 0.5]

# Biến điều khiển vòng lặp
running = True

def signal_handler(sig, frame):
    global running
    running = False
    print("\nShutting down...")

# Bắt tín hiệu Ctrl+C để thoát an toàn
signal.signal(signal.SIGINT, signal_handler)

# Khởi tạo detector
detector = snowboydecoder.HotwordDetector(MODEL_PATHS, sensitivity=SENSITIVITY)

# Khởi tạo PvRecorder để ghi âm liên tục
recorder = PvRecorder(device_index=-1, frame_length=512)
recorder.start()

print("Listening for wake words...")

try:
    while running:
        audio_data = np.array(recorder.read(), dtype=np.int16)  # Đọc dữ liệu âm thanh
        score = detector.detector.RunDetection(audio_data.tobytes())  # Kiểm tra wake word

        if score <= 0:
            continue  # Không phát hiện từ đánh thức, tiếp tục

        # Nếu phát hiện từ đánh thức
        print("Wake word detected! Đang xử lý...")
        time.sleep(5)  # Giả lập quá trình xử lý
        print("Quay lại trạng thái lắng nghe...")

except KeyboardInterrupt:
    print("\nStopping...")

# Dừng PvRecorder và giải phóng tài nguyên
recorder.stop()
recorder.delete()
detector.terminate()
