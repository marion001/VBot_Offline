'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

"""
HƯỚNG DẪN SỬ DỤNG
- VBot tự gọi ``logs_dev(logs_text)`` cho mỗi dòng log khi chế độ log tùy chỉnh được bật.
- Giữ nguyên tên hàm; chỉ chỉnh phần thân để in ra màn hình, ghi tệp hoặc gửi sang máy chủ khác.
- Không gọi lại hàm ghi log chính của VBot từ đây nếu việc đó lại kích hoạt ``logs_dev`` (tránh đệ quy).
- File không có biến toàn cục cấu hình; ``Lib.time`` được dùng để tạo thời gian hiển thị.
"""

#Thư viện Lib của VBot
import Lib

#Giữ nguyên hàm def logs_dev(logs_text)
#Mọi thứ chỉ được sửa đổi code bên trong hàm này
#Các bạn có thể code tùy biến hiển thị logs theo ý muốn như: hiển thị lên màn hình, đẩy logs lên server khác, V..v...

def logs_dev(logs_text):
    current_time = Lib.time.strftime("%H:%M:%S %d-%m-%Y", Lib.time.localtime())
    print(f"DEV Logs: [{current_time}] {logs_text}")
