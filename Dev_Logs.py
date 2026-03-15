'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

#Thư viện Lib của VBot
import Lib

#Giữ nguyên hàm def logs_dev(logs_text)
#Mọi thứ chỉ được sửa đổi code bên trong hàm này
#Các bạn có thể code tùy biến hiển thị logs theo ý muốn như: hiển thị lên màn hình, đẩy logs lên server khác, V..v...

def logs_dev(logs_text):
    current_time = Lib.time.strftime("%H:%M:%S %d-%m-%Y", Lib.time.localtime())
    print(f"DEV Logs: [{current_time}] {logs_text}")