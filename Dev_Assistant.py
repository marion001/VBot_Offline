'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
'''

"""
Phần xử lý dữ liệu các bạn sẽ tự code và xử lý theo ý, sở thích và tùy biến của bạn
Tôi sẽ cung cấp các tài liệu và ví dụ đủ để các bạn xây dựng và phát triển thỏa mãn mày mò, học hỏi

Các dữ liệu và tài nguyên khác có thể tham khảo ở file: Dev_Customization.py
"""

#Thư Viện VBot: Lib
import Lib
"""
Thêm thư viện Lib
"""

import TTS_Processing
"""
thêm Thư viện TTS Speak to Text, Chuyển văn bản thành giọng nói
ví dụ chuyển dữ liệu text, văn bản thành file âm thanh audio

test_tts_to_audio = TTS_Processing.Select_TTS(input_text)
if test_tts_to_audio:
    Lib.show_log(f"dữ liệu âm thanh đã được chuyển đổi từ văn bản: {test_tts_to_audio}", color=Lib.Color.GREEN)
else:
    Lib.show_log(f"Lỗi chuyển đổi dữ liệu văn bản thành âm thanh", color=Lib.Color.RED)
"""


"""
Nếu lấy dữ liệu trong file Config.json

#Ví dụ Lấy và hiển thị dữ liệu trong Config.json:
hien_thi_port_api = Lib.config['api']['port']
print(f"Port API Là: {hien_thi_port_api}")

"""

#thêm thư viện requests dùng cho demo Assistant Gemini
import requests


"""
hàm dev_assistant cần được giữ nguyên, 
Mọi tùy biến và xử lý dữ liệu các bạn dev sẽ code bên trong hàm này
used_for=None cần được giữ nguyên, không được thay đổi
"""
#VÍ DỤ CODE TRỢ LÝ ẢO GOOGLE GEMINI Với Model: gemini-1.5-flash-latest
def dev_assistant(text_input, used_for=None):

    #Hiển thị dữ liệu text truyền vào trợ lý ảo xử lý
    Lib.show_log(f"[DEV Assistant] Dữ liệu truyền vào là: {text_input}", color=Lib.Color.RED)

    #API KEY của google gemini
    api_key_gemini = "AAAAAAAAAAAAAAAAA_BBBBBBBBB"

    url_api = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={api_key_gemini}"

    data = {"contents": [{"parts": [{"text": text_input}]}]}
    headers = {'Content-Type': 'application/json'}

    try:
        response = requests.post(url_api, json=data, headers=headers)
        if response.status_code == 200:
            response_data = response.json()

            #Lấy kết quả trả về của Gemini
            text_gemini = response_data['candidates'][0]['content']['parts'][0]['text'].strip()

            #hiển thị dữ liệu text Gemini trả về
            Lib.show_log(f"[DEV Assistant] Kết quả Gemini: {text_gemini}", color=Lib.Color.GREEN)

            #Chuyển đổi dữ liệu văn bản trả về của Gemini thành âm thanh
            tts_gemini = TTS_Processing.Select_TTS(text_gemini)
            if tts_gemini:

                #Hiển thị dữ liệu đường dẫn tệp âm thanh được chuyển đổi từ text
                Lib.show_log(f"[DEV Assistant] Dữ liệu âm thanh được chuyển đổi thành công: {tts_gemini}", color=Lib.Color.PURPLE)

                #Nếu thành công
                #Trả về dữ liệu cho Chương trình văn bản và đường dẫn audio
                return tts_gemini, text_gemini

            #Nếu chuyển đổi TTS thất bại, trả về Lỗi
            return None, text_gemini
        else:
            Lib.show_log(f"[DEV Assistant] Lỗi: {response.status_code}, {response.text}", color=Lib.Color.RED)

            #Nếu Có Lỗi sử dụng return
            return None, f'[DEV Assistant] Lỗi: {response.text}'

    except Exception as e:
        TEXT_ERROR = f"[DEV Assistant] Có lỗi xảy ra: {e}"
        Lib.show_log(TEXT_ERROR, color=Lib.Color.RED)

        #Nếu Có Lỗi sử dụng return
        return None, TEXT_ERROR

    #Lỗi sử dụng return
    return None, None
    """
    return cuối sẽ trả về 2 dữ liệu, dữ liệu đầu tiên là 'link, url audio', tiếp theo là 'văn bản text'
    Nếu có cả 2 dữ liệu thì trả về cả text và tệp âm thanh, nếu trả về 1 trong 2 thì dữ liệu còn lại sẽ điền là 'None'
    
    Ví dụ chỉ trả về tệp âm thanh: 
        return '/home/pi/VBot_Offline/TTS_Audio/zxcvbn.mp3', None
        
    Hoặc Dữ liệu âm thanh là url:
        return 'http://vutuyen.dev/zxcvbn.mp3', None
        
    Nếu cả 2 giá trị không có dữ liệu hoặc có lỗi xảy ra thì cần trả về 2 giá trị là: 'return None, None'
    """