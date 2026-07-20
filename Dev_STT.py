'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import Lib
import time
from Media_Player import media_player

#Mẫu Code Demo Sử Dụng STT Chuyển đổi giọng nói thành văn bản của Google Cloud V1

#Hướng Dẫn Tạo File Json STT GCloud:
#https://drive.google.com/drive/folders/1VLNVp75t62yYOuqt6x4z-7pwlFk2fKY2

#Thư viện STT GCloud V1
from google.cloud import speech_v1p1beta1 as speech

Lib.os.environ["GRPC_POLL_STRATEGY"] = "epoll1"

#Thời gian thu âm STT tối đa
maximum_recording_time = Lib.config["smart_config"]["smart_wakeup"]["speak_to_text"]["duration_recording"]

dev_stt_interim_results = Lib.config["smart_config"]["smart_wakeup"]["speak_to_text"]["interim_results"]

#Tham số cấu hình nhân Gain, khuếch đại âm thanh thu từ Mic
STT_GAIN_VALUE_FLOAT = float(Lib.config['smart_config']['smart_wakeup']['speak_to_text']['gain']['value_float'])

#Mỗi giây ghi lại 16.000 giá trị âm thanh (16 kHz)
RATE = 16000

#Mỗi lần đọc sẽ lấy 1024 mẫu âm thanh
#CHUNK = 1024

#Ngôn Ngữ Giọng Nói STT
Language_Code = 'vi-VN'

#Đường dẫn tới File JSON Gcloud (bạn có thể tạo file khác ngang hàng để sử dụng)
Lib.os.environ["GOOGLE_APPLICATION_CREDENTIALS"] = 'stt_token_google_cloud.json'

#Khởi tạo Client STT Google Cloud V1
client = speech.SpeechClient()

Lib.show_log("- [DEV STT] Đã Khởi Tạo: Google Cloud Speak To Text V1", color=Lib.Color.GREEN)

#Ví Dụ Sử Dụng STT Google Cloud V1
async def dev_stt():
    def audio_generator():
        last_audio_time = time.time()
        start_time = time.time()
        agc_buffer = bytearray()
        while True:
            if time.time() - start_time >= maximum_recording_time:
                Lib.show_log(f'[DEV STT] Đã dừng thu âm sau: {maximum_recording_time} giây', color=Lib.Color.YELLOW)
                break

            #Mọi dữ liệu mic phải đi qua cổng đọc chung để tránh native read đồng thời.
            audio_frame = Lib.read_recorder_frame(owner="dev_stt")
            if audio_frame is None:
                break

            if any(audio_frame):
                last_audio_time = time.time()

                #Chuyển dữ liệu audio dạng số nguyên (list/int array) thành bytes PCM 16-bit
                pcm_bytes = Lib.array.array('h', audio_frame).tobytes()

                #Nhân Khuếch Đại Gain Mic
                if Lib.stt_gain_active:
                    pcm_bytes = Lib.audioop.mul(pcm_bytes, 2, STT_GAIN_VALUE_FLOAT)

                #Nếu bật lọc nhiễu âm thanh đầu vào Mic: Noise + AGC 
                if Lib.Noise_Auto_Gain:

                    #Thêm dữ liệu PCM mới (pcm_bytes) vào bộ đệm agc_buffer
                    agc_buffer.extend(pcm_bytes)
                    
                    #Khi buffer có đủ 320 bytes thì xử lý
                    while len(agc_buffer) >= 320:  #10ms @16kHz

                        #Lấy 320 bytes đầu tiên (1 frame 10ms) để xử lý
                        chunk = agc_buffer[:320]
                        
                        #Xóa phần đã lấy khỏi buffer để chuẩn bị cho frame tiếp theo
                        del agc_buffer[:320]

                        #Đưa frame 10ms vào bộ xử lý AGC
                        result = Lib.Noise_Auto_Gain.Process10ms(bytes(chunk))
                        
                        #Gửi frame audio đã xử lý vào Google Streaming STT
                        yield speech.StreamingRecognizeRequest(audio_content=result.audio)

                #Nếu không bật tự động cân bằng âm thanh AGC thì dùng Speex Noise (Lọc Nhiễu Nền)
                elif Lib.Noise_STT:
                    #Gửi frame audio đã xử lý vào Google Streaming STT
                    yield speech.StreamingRecognizeRequest(audio_content=Lib.Noise_STT.process(pcm_bytes))

                #Nếu không bật lọc nhiễu âm thanh đầu vào Mic => gửi trực tiếp dữ liệu âm thanh tới stt gcloud
                else:
                    #Gửi frame audio vào Google Streaming STT
                    yield speech.StreamingRecognizeRequest(audio_content=pcm_bytes)
            else:
                #Dừng nếu im lặng quá lâu
                if time.time() - last_audio_time > 2.0:  #2 giây im lặng
                    print("Đã dừng thu âm sau 2 giây không có âm thanh")
                    break
    try:
        Lib.show_log(f"[DEV STT] Đang thu âm.....", color=Lib.Color.PURPLE)
        config = speech.RecognitionConfig(
            encoding=speech.RecognitionConfig.AudioEncoding.LINEAR16,
            sample_rate_hertz=RATE,
            language_code=Language_Code,
            enable_automatic_punctuation=True,
        )
        streaming_config = speech.StreamingRecognitionConfig(
            config=config,
            interim_results=dev_stt_interim_results,   #Hiển Thị Kết quả trung gian (True là bật, False là tắt) (bật True chỉ dùng để Debug)
            single_utterance=True   #Kích hoạt chế độ ngắt tự động khi phát hiện dừng nói (True = Bật, False = Tắt)
        )
        requests = audio_generator()
        # Google trả về lazy iterator; ép duyệt iterator trong worker thread để
        # việc chờ Mic/Network không khóa event loop.
        responses = await Lib.asyncio.to_thread(
            lambda: list(client.streaming_recognize(streaming_config, requests))
        )
        for response in responses:
            for result in response.results:

                #Kết quả dữ liệu văn bản từ âm thanh STT
                if result.is_final:
                    #print(f"Kết quả cuối cùng: {result.alternatives[0].transcript}")
                    Lib.show_log(f"Kết quả cuối cùng: {result.alternatives[0].transcript}", color=Lib.Color.GREEN)

                    #Biến: Lib.stt_transcript sẽ lưu giá trị chuyển đổi được từ STT sang Text để hệ thống xử lý
                    #Bắt buộc phải gán giá trị cuối được chuyển đổi từ STT vào biến: Lib.stt_transcript
                    Lib.stt_transcript = result.alternatives[0].transcript

                    return result.alternatives[0].transcript

                #Nếu kết quả trung gian bên trên được đặt là True: interim_results = True
                else:
                    #print(f"Kết quả STT trung gian tạm thời: {result.alternatives[0].transcript}")
                    Lib.show_log(f"[STT-Logs] {result.alternatives[0].transcript}", color=Lib.Color.CYAN)
    #Xử lý Lỗi
    except Exception as e:
        Msg_ERROR = f"[DEV STT] Đã xảy ra lỗi: {e}"

        #Lưu Logs Vào File nếu Có Lỗi Xảy Ra
        Lib.Logs_VBot(Msg_ERROR)
        
        #Hiển Thị Logs lỗi ra Nguồn Được Chọn
        Lib.show_log(Msg_ERROR, color=Lib.Color.RED)
        
        #Thông Báo Âm Thanh Khi Lỗi STT
        await media_player.play_sound_async(
            Lib.config['smart_config']['smart_wakeup']['sound']['default']['stt_to_text_error']
        )
        return None
    return None
