'''
Code By: Vũ Tuyển
Facebook: https://www.facebook.com/TWFyaW9uMDAx
'''
from pvrecorder import PvRecorder
def show_micro_devices():
    print(f"Danh Sách các thiết bị Micro:")
    devices = PvRecorder.get_available_devices()
    for index, device in enumerate(devices):
        print(f"ID: {index}, Tên: {device}")
    print(f"Đã tìm kiếm xong các thiết bị Micro:")

show_micro_devices()