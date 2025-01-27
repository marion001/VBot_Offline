"""
Phần xử lý dữ liệu các bạn sẽ tự code và xử lý theo ý, sở thích và tùy biến của bạn
Tôi sẽ cung cấp các tài liệu và ví dụ đủ để các bạn xây dựng và phát triển thỏa mãn mày mò, học hỏi
Các dữ liệu và tài nguyên khác có thể tham khảo ở file: Dev_Customization.py
"""

import Lib
"""
Thêm thư viện Lib
"""

import requests

#Demo sử dụng Zalo TTS:
#https://ai.zalo.cloud/docs/api/text-to-audio-converter

"""
hàm dev_tts cần được giữ nguyên, 
Mọi tùy biến và xử lý dữ liệu các bạn dev sẽ code bên trong hàm này
"""
def dev_tts(text_input):
    #Lib.show_log(f"[DEV TTS] Dữ liệu truyền vào để chuyển đổi là: {text_input}", color=Lib.Color.GREEN)

    #Đường Dẫn Path Lưu File TTS (Giữ Nguyên dòng output_file_path này)
    output_file_path = Lib.directory_tts+"/"+Lib.tts_string(text_input)+".mp3"

    #Nhập API KEY TTS ZALO
    API_KEY_ZALO = "11111111111111111111111111"

    #Tốc độ đọc TTS
    SPEED_TTS = 0.9
    #Giọng Đọc
    SPEAKER_ID = 4
    payload = f"speaker_id={SPEAKER_ID}&speed={SPEED_TTS}&input={requests.utils.quote(text_input)}"
    headers = {
      'apikey': API_KEY_ZALO,
      'Content-Type': 'application/x-www-form-urlencoded'
    }
    response = requests.request("POST", "https://api.zalo.ai/v1/tts/synthesize", headers=headers, data=payload)
    #Chuyển dữ liệu  trả về thành json
    response_data = response.json()
    #Kiểm tra dữ liệu trả về
    if response_data.get('error_code') == 0:

        #Link/URL TTS Online
        audio_url = response_data['data']['url']
        Lib.show_log(f"[DEV TTS] URL Phát TTS Trực Tiếp: {audio_url}", color=Lib.Color.YELLOW)

        try:
            audio_response = Lib.requests.get(audio_url)
            audio_response.raise_for_status()

            #Nếu muốn lưu lại Tệp âm thanh TTS
            with open(output_file_path, 'wb') as audio_file:
                audio_file.write(audio_response.content)
            Lib.show_log(f"[DEV TTS] File được tải xuống thành công tại: {output_file_path}", color=Lib.Color.GREEN)
            #Trả dữ liệu về cho chương trình phát TTS (đường dẫn path)
            return output_file_path

            #Hoặc có thể trả luôn dữ liệu URL TTS bên trên mà không cần tải xuống Tệp TTS
            #return audio_url

        except Exception as e:
            Lib.show_log(f"[DEV TTS] Lỗi khi tải xuống âm thanh: {e}", color=Lib.Color.RED)
            #Nếu Lỗi Sẽ Trả Về Link TTS Online
            return audio_url
    else:
        Lib.show_log(f"[DEV TTS] Có Lỗi Xảy Ra: {response_data}", color=Lib.Color.RED)
        #Trả về None Nếu Lỗi
        return None
    return None

"""

Dữ liệu trả về cho chương trình sẽ chấp nhận các dạng sau:
- đường dẫn path: /home/pi/VBot_Offline/TTS_Audio/zxcvbn.mp3 (wav, ogg, v..v....)
    return /home/pi/VBot_Offline/TTS_Audio/zxcvbn.mp3

- Url Âm Thanh: https://vutuyen.dev/zxcvbn.mp3 (wav, ogg, v..v....)
    return https://vutuyen.dev/zxcvbn.mp3

- Nhiều dữ liệu âm thanh PATH: ['/home/pi/VBot_Offline/TTS_Audio/1.mp3', '/home/pi/VBot_Offline/TTS_Audio/2.mp3', '/home/pi/VBot_Offline/TTS_Audio/3.mp3']
- Nhiều dữ liệu âm thanh LINK/URL: ['https://vutuyen.dev/1.mp3', 'https://vutuyen.dev/2.mp3', 'https://vutuyen.dev/3.mp3']
    return ['https://vutuyen.dev/1.mp3', 'https://vutuyen.dev/2.mp3', 'https://vutuyen.dev/3.mp3']

"""