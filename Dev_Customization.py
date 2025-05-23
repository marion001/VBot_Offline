'''
Code By: Vũ Tuyển
Designed by: BootstrapMade
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
'''

"""
Phần xử lý dữ liệu các bạn sẽ tự code và xử lý theo ý, sở thích và tùy biến của bạn
Tôi sẽ cung cấp các tài liệu và ví dụ đủ để các bạn xây dựng và phát triển thỏa mãn mày mò, học hỏi
"""

#Thư Viện VBot: Lib
import Lib
"""
Thêm thư viện Lib
"""

import Led
"""
#thêm thư viện Đèn LED
#sử dụng thư viện Led: Led.LED("OFF"), Led.LED("SPEAK") có các tham số: SPEAK, THINK, LOADING, MUTE, STARTUP, PAUSE, OFF, SOCKET_ERROR, OFF
#Hiện tại chỉ hỗ trợ led WS281x nhé
"""


import TTS_Processing
"""
thêm Thư viện TTS Speak to Text, Chuyển văn bản thành giọng nói
"""


from Media_Player import media_player
"""
#Thư viện Media Player để phát âm thanh từ file, url, audio, stream
#các tham số sử dụng Media Player: 
#media_player.Play_Answer("1.mp3") = dùng để phát câu trả lời được chuyển đổi từ tts, text to speak

#media_player.Play_Sound(Lib.Sound_Finish) =  dùng để phát các âm thanh như ding, dong, tút, tút, các file âm thanh mặc định có sẵn
#Các tham số sử dụng cho Play_Sound như: Lib.Sound_Finish, Lib.Sound_Start, Lib.Sound_Volume_Change

#media_player.Play_Media("http://fdsf.vn/thuyen_quyen.mp3") = dùng để phát nhạc, podcast, audio, radio, stream (có tích hợp các lệnh để điều khiển: play, pause, stop, continue)
#Các tham số để điều khiển Play_Media: media_player.Pause_Media(), media_player.Stop_Media(), media_player.continue_play()
#Tham số để Tua khi đang phát Play_Media:  media_player.media_player.set_time(124587) 124587 là ví dụ về thời gian cần tua, dưới dạng số nguyên dương của vlc

#Các tham số khác khi sử dụng media_player: Lib.audio_media_title, Lib.audio_media_url, Lib.media_player_source, Lib.audio_media_cover
"""


import Assistant
"""
#Thư viện sử dụng trợ lý ảo
#ví dụ sử dụng trợ lý ảo: Sử Dụng Trợ Lý Ảo Theo Chế ĐỘ Ưu Tiên lần Lượt: Assistant.Call(input_text, True)
#Assistant.Call(input_text, True) có đối số thứ 2 là True hoặc False để chuyển đổi dữ liệu âm thanh sang văn bản Text, True để chuyển đổi, False không chuyển đổi

Assistant_Audio, Assistant_Text = Assistant.Call(input_text, True)
print(f"Assistant Audio: {Assistant_Audio}")
print(f"Assistant Text: {Assistant_Text}")


#Gọi Trực Tiếp Tới 1 Trợ Lý Ảo Nào Đó: Các Trợ Lý Ảo: gemini_assistant, gpt_assistant, default_assistant, zalo_assistant
#Có đối số thứ 2 là True hoặc False để chuyển đổi dữ liệu âm thanh sang văn bản Text, True để chuyển đổi, False không chuyển đổi
#Ví Dụ Gọi Trợ Lý ảo gemini_assistant:
Assistant_Audio, Assistant_Text = Assistant.gemini_assistant(input_text, True)
print(f"Assistant Audio: {Assistant_Audio}")
print(f"Assistant Text: {Assistant_Text}")

"""



"""
Cần giữ nguyên hàm, function: def dev_skill(input_text):
Mọi tùy biến và xử lý dữ liệu các bạn dev sẽ code bên trong hàm "def dev_skill(input_text):"
biến input_text = dữ liệu text được chuyển đổi Speak to text
"""
def dev_skill(input_text):

    
    Lib.show_log(f"Dữ liệu STT truyền vào CUstom Skill: {input_text}", color=Lib.Color.GREEN)


    """
    hiển thị trực tiếp print với mã màu:
    các tham số mã màu cho print: RED_COLOR, RESET_COLOR, GREEN_COLOR, YELLOW_COLOR
    """
    print(f"{Lib.GREEN_COLOR} Đây là văn bản text truyền vào: {input_text} {Lib.RESET_COLOR}")

    #Ví dụ Lấy và hiển thị dữ liệu trong Config.json:
    hien_thi_port_api = Lib.config['api']['port']
    Lib.show_log(f"ví dụ hiển thị cổng port của APi là: {hien_thi_port_api}", color=Lib.Color.GREEN)

    """
    Sử dụng hiển thị show_log và màu Sắc
    color=Lib.Color.GREEN sẽ có các tham số màu: PURPLE, CYAN, DARKCYAN, BLUE, GREEN, YELLOW, RED, BOLD, UNDERLINE, END, WHITE
    """
    Lib.show_log(f"[DEV Customization] Tùy chỉnh nhà phát triển đang xử lý dữ liệu...", color=Lib.Color.GREEN)
    
    

  


    #Ví dụ sử dụng trợ lý ảo
    """
    
    Assistant_Audio, Assistant_Text = Assistant.Call(input_text, False)
    if Assistant_Text:
        Lib.show_log(f"[BOT]: {Assistant_Text}", color=Lib.Color.GREEN)
    else:
        if Assistant_Audio:
            Lib.show_log(f"[BOT]: dữ liệu âm thanh {Assistant_Audio}", color=Lib.Color.GREEN)
        else:
            Lib.show_log(f"Văn bản và âm thanh đều None không có dữ liệu phản hồi", color=Lib.Color.RED)
    if Assistant_Audio:
        if media_player.Play_Answer(Assistant_Audio):
            pass
            
    """

    
    
    
    
    #ví dụ chuyển dữ liệu text, văn bản thành file âm thanh audio
    """
    
    test_tts_to_audio = TTS_Processing.Select_TTS(input_text)
    if test_tts_to_audio:
        Lib.show_log(f"dữ liệu âm thanh đã được chuyển đổi từ văn bản: {test_tts_to_audio}", color=Lib.Color.GREEN)
    else:
        Lib.show_log(f"Lỗi chuyển đổi dữ liệu văn bản thành âm thanh", color=Lib.Color.RED)
    
    """
    
    
    
    """
    Ví dụ Phát âm thanh từ file audio
    Sử dụng pass nếu không muốn thực thi trong điều kiện if,
    hoặc có thể sử dụng trực tiếp à không cần điều kiện if: media_player.Play_Answer(test_tts_to_audio):
    """
    #Play_Answer dùng để phát câu trả lời
    """
    if media_player.Play_Answer(test_tts_to_audio):
        pass
        
    #ví dụ phát nhạc:
    Lib.audio_media_url = "http://vutuyen.dev/1.mp3" #link bài hát, hoặc link stream, radio, v..v...
    Lib.audio_media_title = "thuyền quyên" #tên của hài hát
    Lib.audio_media_cover = "http://vutuyen.dev/1.png" #ảnh của bài nhạc
    Lib.media_player_source = "youtube" #Điền gì cũng được
    #Tiến hành phát nhạc
    media_player.Play_Media(Lib.audio_media_url)
    
    #Sử dụng các lệnh điều khiển Media Player:
    #Tạm dừng phát nhạc
    media_player.Pause_Media()
    #Tiếp tục phát nhạc đã tạm dừng trước đó
    media_player.continue_play()
    #Stop, dừng phát nhạc
    media_player.Stop_Media(), 

    """


    """
    Ví dụ các tham số dùng để điều khiển âm lượng Volume
    """
    #Sét âm lượng 90%
    #Lib.VOLUME(90)
    
    #Lib.VOLUME("UP")
    #Lib.VOLUME("DOWN")
    #Lib.VOLUME("MAX")
    #Lib.VOLUME("MIN")



    
    """
    Nếu bạn để return là True thì Chương trình sẽ coi như 
    Dev_Customization.py xử lý xong và quay về trạng thái chờ được đánh thức
    
    Nếu bạn để return là False hoặc None thì trương trình sẽ có lựa chọn trong
    Config.json: developer_customization->if_custom_skill_can_not_handle->active->true để dùng Vbot xử lý dữ liệu
    còn nếu developer_customization->if_custom_skill_can_not_handle->active->false 
    thì sẽ quay về trạng thái chờ đánh thức mà không dùng vbot xử lý
    """
    """
    return False -> thì sẽ sử dụng tiếp Vbot để xử lý dữ liệu
    return True -> quay về trạng thái chời được đánh thức
    """
    Lib.show_log(f"[DEV Customization] return False => Vbot sẽ xử lý tiếp dữ liệu", color=Lib.Color.GREEN)
    return False
    #Dữ liệu trả lại về cho trương trình bắt buộc là return True hoặc False = None
    