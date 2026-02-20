'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Email: VBot.Assistant@gmail.com
'''

#Ở File Này, Người Dùng Sẽ Phải Tự Code Xử Lý Dữ Liệu
#Cần vào Web UI chọn "Chế Độ Khởi Chạy Toàn Bộ Chương Trình = Người Dùng Tự Code Xử Lý Dữ Liệu - Dev_Processing.py" Thì Mới Chạy Được File Này Nhé
#Đây chỉ là mẫu Demo, mình viết đơn giản dễ hiểu nhất để mọi người có thể áp dụng được
#Nếu bạn Cần hỗ trợ hãy liên hệ với tác giả

import Assistant
import Lib
import Led
import TTS_Processing
import Def_Processing
from Media_Player import media_player


#Một Số Biến Toàn Cục, Hàm Trong Hệ Thống Dùng Chung
"""
#Biến: Lib.conversation_mode                =>  (Chế Độ Hội Thoại/Trò Chuyện Liên Tục, True = Cho phép tự động Đánh Thức, False Ngược Lại)
#Biến: Lib.conversation_mode_flag           =>  (Khi quay về chờ được đánh thức, nếu là True sẽ tự động Đánh Thức WakeUP Để Thu Âm, False = Ngược Lại. Thường kết hợp với biến: Lib.conversation_mode)
#Biến: Lib.main_vbot_processing             =>  (Gắn Cờ Báo Hệ Thống Xử LÝ Dữ Liệu True = Đang Xử Lý, False Không Xử Lý)
#Biến: status = Lib.active_client           =>  (Streming Audio Server VBot Client, Client - Server, True = Đang bật sử dụng Client như esp32, False tắt tính năng)
#Biến: status = Lib.is_playing_playlist     =>  (True = Đang Được Phát Danh Sách Nhạc, False Ngược Lại)
#Hàm:  text_tts = Lib.tts_string(msg_text)  =>  (tạo tên file âm thanh tts bằng kết quả text được xử lý các ký tự)
#Biến: status = Lib.mic_on_off              =>  (True = Bật Mic, False Ngược lại)
#Hàm:  Lib.restart_vbot()                   =>  (Khởi động lại chương trình VBot khi chạy tự động)
#Hàm:  Lib.reboot_os()                      =>  (Khởi động lại toàn bộ hệ thống)
#Hàm:  text = Lib.read_cpu_temperature()    =>  (Lấy Nhiệt Độ CPU Hiện Tại)
#Hàm:  text = Lib.read_system_uptime()      =>  (Kiểm tra thời gian khởi động)
#Biến: Lib.get_ssid_name                    =>  (Lấy Tên Mạng Wifi Đang Kết Nối)
#Biến: Lib.get_my_ip                        =>  (Lấy Địa Chỉ IP Hiện Tại)
#Biến: Lib.device_mac                       =>  (Lấy Địa Chỉ Mac)
#biến: Lib.device_model                     =>  (Lấy Tên Thiết Bị Board Mạch)
#Hàm: Lib.reset_all_wifi()                  =>  (Đặt lại toàn bộ cấu hình mạng Wifi)
"""

#Điều Khiển Âm Lượng
"""
#Lib.VOLUME(75)
#Lib.VOLUME("UP")
#Lib.VOLUME("DOWN")
#Lib.VOLUME("MAX")
#Lib.VOLUME("MIN")
#Biến: data = Lib.Volume   =>  (Lấy Thông số âm lượng hiện tại)
"""

#Phát Nhạc, File, Dữ Liệu Âm Thanh
"""
#Hàm: media_player.Play_Answer('/home/pi/tts.mp3')                                              =>  (Dùng để phát dữ liệu âm thanh Text To Speak - tts ra loa)
#Hàm: media_player.Play_Media('/home/pi/nhac.mp3')                                              =>  (Dùng để phát nhạc, bài hát, chấp nhận các link, url, stream, radio)
#Hàm: media_player.Play_Sound('/home/pi/sound_local.mp3')                                       =>  (Dùng để phát các file nhạc nội bộ âm báo trong thư mục: resource/sound/)
#Hàm: media_player.Play_Media_List('/home/pi/VBot_Offline/html/includes/cache/PlayList.json')   =>  (Dùng để phát danh sách nhạc với dữ liệu truyền vào là file .json, cho phép dùng cả url file http://cxcxc.com/list.json)

##Gán Giá trị trước khi gọi phát nhạc
#Biến: Lib.audio_media_url = '/home/pi/nhac.mp3'                                                =>  (Dùng để gán dữ liệu nguồn phát âm thanh)
#Biến: Lib.audio_media_title = 'Hoa Cỏ Lau'                                                     =>  (Cần Gán Tên Bài Hát, tiêu đề nhạc, vào Biến Này Trước Khi Phát Nhạc)
#Biến: Lib.audio_media_cover = 'http://1.jpg'                                                   =>  (Cần Gán URL Hình Ảnh Bài Hát trước khi Phát Nhạc, Để Trống nếu không có)
#Biến: Lib.media_player_source = 'Youtube'                                                      =>  (Đặt tên cho nguồn, dữ liệu nhạc từ youtube, hay zingmp3, hay bất kỳ từ đâu)
##Khi Muốn phát nhạc, bạn cần gán đầy đủ tham số cho 4 biến trên
##Sau đó gọi hàm này để bắt đầu phát: media_player.Play_Media('/home/pi/nhac.mp3')


#Hàm: media_player.Pause_Media()                    =>      (Dùng để tạm dừng phát nhạc)
#Hàm: media_player.Stop_Media()                     =>      (Dùng để dừng phát nhạc)
#Hàm: media_player.Stop_Answer()                    =>      (Dùng để dừng phát TTS)
#Hàm: media_player.play_playlist_song('next')       =>      (Dùng để chuyển sang bài kế tiếp khi đang phát Playlist, Danh sách nhạc)
#Hàm: media_player.play_playlist_song('prev')       =>      (Dùng để quay lại bài trước đó khi đang phát Playlist, Danh sách nhạc)
#Hàm: media_player.continue_play()                  =>      (Dùng để tiếp tục phát nhạc khi nhạc đang được tạm dừng từ trước đó)
#Biến: Lib.audio_playing                            =>      (True = Đang phát âm thanh bao gồm nhạc, TTS, False = ngược lại không phát gì cả hoặc đang được tạm dừng nhạc)
#Biến: Lib.Play_Answer_Flag                         =>      (True = Đang phát âm thanh TTS, False = ngược lại)
#Biến: Lib.pause_media_flag                         =>      (True = Nhạc đang được tạm dừng, False = ngược lại)
"""

#AirPlay
"""
status, msg = Lib.shairport_cmd("mute")             =>  (Tắt tiếng mutex AirPlay)
status, msg = Lib.shairport_cmd("unmute")           =>  (Bật tiếng Un-Mutex AirPlay)
status, msg = Lib.shairport_cmd("enable_alsa")      =>  (Bật giao tiếp với hệ thông âm thanh alsa trên hệ thống)
status, msg = Lib.shairport_cmd("disable_alsa")     =>  (Tắt giao tiếp với hệ thông âm thanh alsa trên hệ thống)
status, msg = Lib.shairport_cmd("volume", 75)       =>  (Thay Đổi Âm lượng AirPlay)
status = Lib.shairport_cmd("get_play_state")        =>  (Lấy Trạng Thái Phát Hiện Tại)
status = Lib.shairport_cmd("info_player")           =>  (Lấy thông tin dữ liệu nhạc hiện tại)
status = Lib.shairport_cmd("info_all")              =>  (Lấy toàn bộ dữ liệu nhạc hiện tại)
status = Lib.shairport_sync_player                  =>  (True đang phát AirPlay, False Ngược Lại)
text = Lib.shairport_song_name                      =>  (Tên Bài hát Đang Phát ở AirPlay)
status = Lib.shairport_mute_on_off                  =>  (True đang bật tiếng Un-Mutex, False Đang Được Tắt Tiếng Mutex)
status = Lib.shairport_sync_active                  =>  (True AirPlay đang được Kích Hoạt, False Không được kích hoạt)
"""

#Điều Khiển Đèn LED
"""
#Hàm: Led.LED("OFF")                =>   Tắt LED
#Hàm: Led.LED("PAUSE")              =>   Chạy LED khi nhạc đang được tạm dừng
#Hàm: Led.LED("STARTUP")            =>   Chạy LED khi khởi động trương trình
#Hàm: Led.LED("ERROR")              =>   Chạy LED báo lỗi trong quá trình sử dụng, xử lý dữ liệu
#Hàm: Led.LED("MUTE")               =>   Chạy LED khi Mic, Microphone được tắt
#Hàm: Led.LED("LOADING")            =>   Chạy LED khi hệ thống đang xử lý dữ liệu
#Hàm: Led.LED("THINK")              =>   Chạy LED khi được đánh thức, Lắng nghe âm thanh câu lệnh từ người dùng
#Hàm: Led.LED("SPEAK")              =>   Chạy LED Phát âm thanh TTS, Nhạc => ra loa
#Hàm: Lib.Set_Led_Brightness(255)   =>  (Thiết lập độ sáng đèn led, có giá trị 0 -> 255, tương ứng với 0-100%)
"""

#Kiểm tra trước khi chạy, nếu custom skil được kích hoạt thì sử dụng thêm file Dev_Customization.py
#Giữ nguyên hàm này, không được sửa
if Lib.developer_customization:
    import Dev_Customization

#Kiểm tra xử lý cuối => quay về chờ được đánh thức
#Giữ nguyên hàm này, không được sửa
def dev_finish_processing():
    if Lib.xiaozhi_flag_media_request:  #Nếu có yêu cầu phát nhạc từ xiaozhi (Khi sử dụng Xiaozhi làm trợ lý ảo ưu tiên Top 1)
        Lib.conversation_mode_flag = False
        Lib.xiaozhi_flag_media_request = None
    else:
        Lib.conversation_mode_flag = bool(Lib.conversation_mode)
        if not Lib.conversation_mode:
            media_player.Play_Sound(Lib.Sound_Finish)   #Phát âm thanh nội bộ khi xử lý xong dữ liệu
        Led.LED("OFF")  #Tắt Đèn LED
    #Gắn Cờ False Báo Hệ Thống Đã Xử LÝ Xong Dữ Liệu (True = Đang Xử Lý, False Không Xử Lý)
    Lib.main_vbot_processing = False

#Xử lý cache TTS và phát luôn âm thanh nếu có (Mẫu Demo)
#Giữ nguyên hàm này, không được sửa
def dev_handle_cache_tts(msg_text):
    if not msg_text or not isinstance(msg_text, str):
        return False
    tts_key = Lib.tts_string(msg_text)  #Lib.tts_string(msg_text) tạo tên file âm thanh tts bằng kết quả text được xử lý các ký tự
    result_url = None

    #Nếu Phát TTS ở Loa VBot Server
    if not Lib.active_client:
        #Xử lý và Tìm kiếm file âm thanh tts trước đó khi bật cache và trùng dữ liệu
        tts_data = Def_Processing.cache_results_tts(tts_key) if Lib.cache_tts_active else None
        if not tts_data:
            #Chuyển văn bản thành âm thanh TTS
            tts_data = TTS_Processing.Select_TTS(msg_text)
        Lib.main_vbot_processing = False
        #Phát TTS
        return media_player.Play_Answer(tts_data)

    #Nếu Phát TTS ở Client ESP
    if Lib.cache_tts_active:
        #Xử lý và Tìm kiếm file âm thanh tts khi bật cache
        mp3_file = Def_Processing.cache_results_tts_mp3(tts_key)
        if mp3_file:
            result_url = f"http://{Lib.get_my_ip}/assets/sound/{mp3_file}"

    #Nếu chưa có cache TTS thì tạo mới
    if not result_url:
        #Chuyển văn bản thành âm thanh TTS
        tts_data = TTS_Processing.Select_TTS(msg_text)
        if tts_data:
            #Nếu Ở Client Chuyển Sang MP3 để Phát TTS
            mp3_file = Def_Processing.convert_audio_to_mp3(tts_data)
            if mp3_file:
                result_url = f"http://{Lib.get_my_ip}/assets/sound/{mp3_file}"
    Lib.tts_client_result = result_url  #Trả dữ liệu File âm thanh Về Client để Client phát TTS
    Lib.main_vbot_processing = False    #Gắn Cờ False Báo Hệ Thống Đã Xử LÝ Xong Dữ Liệu (True = Đang Xử Lý, False Không Xử Lý)
    return result_url

#Xử lý các lệnh yêu cầu điều khiển hệ thống
#Giữ nguyên hàm này, không được sửa
async def dev_execute_value_key_sys(command_input: str):
    func = globals().get(command_input)
    if func:
        result = func()
        if result:
            return True
        else:
            return False
    else:
        Lib.show_log(f"[DEV Processing] Hàm '{command_input}' chưa được định nghĩa, bạn cần tự tạo hàm xử lý: 'def {command_input}():' tương ứng trong file này để xử lý dữ liệu", color=Lib.Color.RED)
        return False

#Cần Giữ Nguyên Tên Hàm: async def dev_processing(text_input):
#Mọi Thay Đổi Code cần được xử lý bên trong hàm này nhé
async def dev_processing(text_input):
    print(f"[DEV Processing] Dữ liệu văn bản từ STT truyền vào để xử lý: {text_input}")
    
    #Gắn Cờ Cho Chương Trình Đang Xử Lý Dữ Liệu (True = Đang Xử Lý, False Không Xử Lý)
    Lib.main_vbot_processing = True
    
    #Chạy Led Loading (Hiệu Ứng Đang Xử Lý Dữ Liệu)
    Led.LED("LOADING")
    
    #Chuyển Văn Bản Thành Chữ thường Để Xử Lý Dữ Liệu
    text_input_lower = text_input.lower()


    """
    #Nếu Cần Xử Lý, Cho Phép Sử Dụng: Custom Home Asistant khi được bật
    """
    #Có thể xóa bỏ đoạn này nếu không sử dụng Custom Home Asistant
    if Lib.hass_custom_commands_active:
        #Gọi Custom Home Asistant xử lý
        status, msg_text = Def_Processing.execute_custom_home_assistant(text_input_lower)

        #Nếu Custom Home Asistant xử lý thành công
        if status:
            Lib.show_log(f"[Custom HomeAssistant] {msg_text}", color=Lib.Color.GREEN)
            
            #Gọi kiểm tra và phát tts
            dev_handle_cache_tts(msg_text)
            
            #Kiểm tra xử lý cuối => quay về chờ được đánh thức (Chỉ sử dụng khi muốn quay về chờ được đánh thức, đặt trên dòng return)
            dev_finish_processing()
            
            #Sử dụng return Quay về chờ đánh thức, không chạy tiếp code bên dưới nữa vì đã xử lý xong ở Custom Home Asistant
            return True
    """
    #Kết Thúc Xử lý Custom Home Asistant
    """


    """
    #Nếu Cần Xử lý Broadlink Remote Control khi được bật
    """
    #Nếu Broadlink Remote Control được Kích hoạt
    if Lib.broadlink_remote_active:
        cmd_info = Def_Processing.broadlink_get_command(text_input_lower)
        if cmd_info:
            try:
                device = Def_Processing.get_broadlink_device(cmd_info["mac"], cmd_info["ip"], cmd_info["port"], cmd_info["devtype"])
                device.send_data(Lib.base64.b64decode(cmd_info["data"]))
                print(f"[DEV Customization] Đã gửi lệnh '{cmd_info['command_name']}' tới thiết bị '{cmd_info['device_name']}' ({cmd_info['ip']}) thành công")
                reply = cmd_info.get('command_reply')
                if reply:
                    msg_text = reply
                else:
                    msg_text = f"[DEV Customization] Đã gửi lệnh Remote '{cmd_info['command_name']}' thành công"
                Lib.show_log(f"[DEV Customization] Broadlink Remote {msg_text}", color=Lib.Color.GREEN)
                dev_handle_cache_tts(msg_text)
                dev_finish_processing()
                return True
            except Exception as e:
                Lib.show_log(f"[Broadlink Remote] Lỗi gửi lệnh: {e}", color=Lib.Color.RED)
    """
    #Kết Thúc Xử lý Broadlink Remote Control
    """


    """
    #Nếu Cần Xử Lý Dev Skill, Custom Skill được kích hoạt thì sẽ quăng vào file Dev_Customization.py để xử lý dữ liệu khi được bật
    """
    #Có thể xóa đoạn này nếu không sử dụng
    if Lib.developer_customization:
        Lib.show_log(f"[DEV Customization] Tùy chỉnh nhà phát triển đang xử lý dữ liệu...", color=Lib.Color.GREEN)
        response_dev_skill = Dev_Customization.dev_skill(text_input_lower)
        #Nếu Dev Skill có dữ liệu trả về hoặc là true
        if response_dev_skill:
            if Lib.conversation_mode:   #Nếu Chế Độ Hội Thoại/Trò Chuyện Liên Tục được bật
                Lib.conversation_mode_flag = True   #Đặt giá trị Lib.conversation_mode_flag = True để tự động đánh thức Wake UP
            Led.LED("OFF")
            Lib.main_vbot_processing = False    #Gắn Cờ Cho Chương Trình Đang Xử Lý Dữ Liệu (True = Đang Xử Lý, False Không Xử Lý)

            #Sử dụng return Quay về chờ đánh thức, không chạy tiếp code bên dưới nữa vì đã xử lý xong ở Custom Skill
            return True
        #Nếu dữ liệu trả về là None, False
        else:
            #Nếu Không Cho Phép DEV_Processing xử lý dữ liệu tiếp khi Dev Skill không xử lý được, thì sẽ quay về chờ được đánh thức
            if not Lib.dev_vbot_processing_active:
                if Lib.conversation_mode:   #Nếu Chế Độ Hội Thoại/Trò Chuyện Liên Tục được bật
                    Lib.conversation_mode_flag = True   #Đặt giá trị Lib.conversation_mode_flag = True để tự động đánh thức Wake UP
                Led.LED("OFF")

                #Sử dụng return Quay về chờ đánh thức, không chạy tiếp code bên dưới nữa vì đã xử lý xong ở Custom Skill
                return True

            #Chạy tiếp code bên dưới để xử lý
            else:
                Lib.show_log(f"[DEV Customization] Áp dụng DEV_Processing để xử lý dữ liệu, Chạy tiếp code bên dưới để xử lý", color=Lib.Color.GREEN)
    """
    #Kết Thúc Dev Skill, Custom Skill
    """


    """
    #Nếu Cần Chạy Lệnh Hệ Thống Nếu được bật và trùng câu lệnh
    """
    #Có thể xóa đoạn này nếu không sử dụng
    if Lib.config['voice_command_system']['active']:
        result = Def_Processing.find_best_voice_command_sys(text_input_lower, Lib.voice_cmd_list_sys)
        if result:
            if await dev_execute_value_key_sys(result["value_key"]):
                return True
    """
    #Kết Thúc Xử Lý Lệnh Hệ Thống
    """


    """
    #Bạn Có Thể Xử Lý Dữ Liệu Theo ý Muốn Ở Đây (xử lý điều khiển nhà thông minh, xử lý phát nhạc, gọi các API, V..v....)
    """
    #Ví Dụ phát tts văn bản đó và hiển thị
    dev_handle_cache_tts(text_input_lower)

    #Hiển thị dữ liệu Logs
    Lib.show_log(f"[DEV Processing] Dữ liệu đã được xử lý xong: {text_input_lower}", color=Lib.Color.GREEN)

    #dev_finish_processing()     #Kiểm tra xử lý cuối => quay về chờ được đánh thức (Chỉ sử dụng khi muốn quay về chờ được đánh thức, đặt trên dòng return)
    #return True

    #Nếu return = True (thì cần đặt dev_finish_processing() ở dòng bên trên để xử lý cuối và quay về trạng thái chờ được đánh thức, hoặc được đánh thức tiếp nếu chế độ hội thoại bật)
    #Nếu Không đặt, bỏ return thì code sẽ chạy tiếp bên dưới (ví dụ bên dưới là đang sử dụng trợ lý ảo để xử lý dữ liệu)
    """
    #Kết Thúc Xử Lý Dữ Liệu Theo Ý Muốn
    """




    """
    #Hoặc Xử Lý Cuối Có Thể Dùng Trợ Lý Ảo Để Xử Lý Bên Dưới Đây
    """
    Lib.show_log(f"[DEV Processing] Bắt đầu gọi trợ lý ảo để xử lý dữ liệu", color=Lib.Color.GREEN)
    audio_data = None

    #Gọi trợ lý ảo đã xử lý sẵn cả text và dữ liệu âm thanh trả về 2 giá trị
    #Ưu tiên gọi trợ lý ảo TOP 1 đầu tiên
    Assistant_Audio, Assistant_Text = Assistant.Call(text_input_lower, False)

    #Nếu có dữ liệu văn bản trả về từ trợ lý ảo in ra logs
    if Assistant_Text:
        Lib.show_log(f"[DEV Processing] {Assistant_Text}", color=Lib.Color.GREEN)

    #Kiểm tra nếu có dữ liệu âm thanh trả về từ trợ lý ảo
    elif Assistant_Audio:
        #Gán dữ liệu âm thanh vào biến audio_data
        audio_data = Assistant_Audio
        #Nếu có dữ liệu âm thanh trả về từ trợ lý ảo in ra logs
        Lib.show_log(f"[DEV Processing] Dữ liệu âm thanh trả về từ trợ lý ảo: {Assistant_Audio}", color=Lib.Color.GREEN)

    #Không có dữ liệu trả về từ trợ lý ảo
    else:
        Lib.show_log("[DEV Processing] Assistant văn bản và âm thanh đều không có dữ liệu phản hồi!", color=Lib.Color.RED)

    #Nếu có dữ liệu âm thanh từ trợ lý ảo trả về
    if audio_data:
        #Nếu ở Loa VBot Yêu Cầu
        if not Lib.active_client:
            #Phát âm thanh Kết Quả TTS ở Loa VBot
            media_player.Play_Answer(audio_data)
        
        #Nếu Yêu Cầu Ở Client
        else:
            #Chuyển Sang MP3 để Phát TTS
            tts_client = Def_Processing.convert_audio_to_mp3(audio_data)
            if tts_client:
                #Trả dữ liệu âm thanh về cho loa Client
                Lib.tts_client_result = f"http://{Lib.get_my_ip}/assets/sound/{tts_client}"

    #Kiểm tra xử lý cuối => quay về chờ được đánh thức
    dev_finish_processing()
    return True