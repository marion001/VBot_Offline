'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

'''
Hệ thống OS sử dụng phiên bản 3.0 trong dải được hỗ trợ từ:
    - Phiên bản Picovoice tương thích từ 3.0 -> 3.0.x
    - Phiên bản Pvporcupine/Porcupine tương thích từ 3.0 -> 3.0.x
Bạn hãy sử dụng từ khóa hotword và tệp model thư viện tương ứng với phiên bản trong dải bên trên

Key Picovoice sẽ được sử dụng trong file Config.json
    - smart_config -> smart_wakeup -> hotword_engine -> key

keyword_paths sử dụng bao nhiêu từ khóa thì sensitivities cần cấu hình theo tương ứng
Các tên biến như: keyword_paths, sensitivities, model_file_path bắt buộc phải giữ nguyên, không được thay đổi tên

Demo dưới đây sử dụng ngôn ngữ trung quốc sử dụng từ đánh thức wakeup: Nǐ hǎo = Xin Chào
Bạn có thể sử dụng nhiều hơn 1 từ đánh thức wakeup
'''

#Liệt kê các từ khóa đánh thức
keyword_paths = [
    #'/home/pi/VBot_Offline/resource/hotword/customize/咖啡_raspberry-pi.ppn',    #Kāfēi  = Cà Phê
    #'/home/pi/VBot_Offline/resource/hotword/customize/水饺_raspberry-pi.ppn',    #Shuǐjiǎo   = Bánh Bao
    #'/home/pi/VBot_Offline/resource/hotword/customize/豪猪_raspberry-pi.ppn',    #Háozhū = Con Nhím
    '/home/pi/VBot_Offline/resource/hotword/customize/你好_raspberry-pi.ppn'      #Nǐ hǎo = Xin Chào
]

#Độ nhạy các từ khóa Hotword
sensitivities = [
    #0.5,
    #0.5,
    #0.5,
    0.5
]

#Tệp Model thư viện tương ứng với mô hình ngôn ngữ của bạn
model_file_path = '/home/pi/VBot_Offline/resource/picovoice/library/porcupine_params_zh.pv'