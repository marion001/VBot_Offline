'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

#Thư Viện VBot: Lib
import Lib

#Màu Thông Báo Logs (color=Lib.Color.YELLOW) có thể sử dụng: (PURPLE, CYAN, DARKCYAN, BLUE, GREEN, YELLOW, RED, BOLD, UNDERLINE, END, WHITE)

#Code mẫu Lấy Dữ Liệu Âm Nhạc Từ Nguồn: NhacCuaTui

#Tên hàm:  " def custom_music(input_text: str) " không được thay đổi để phù hợp tương thích với hệ thống
def custom_music(input_text: str):

    Lib.show_log(f"Vào DEV Custom Music với từ khóa tìm kiếm là: {input_text}", color=Lib.Color.YELLOW)

    #Tạo URL API tìm kiếm của NhacCuaTui với từ khóa cần tìm.
    #timestamp được tính theo milliseconds để API hợp lệ.
    url_api_nhaccuatui = f"https://graph.nhaccuatui.com/api/v3/search/all?keyword={input_text}&correct=false&timestamp={int(Lib.time.time() * 1000)}"

    try:
        #Gửi request GET đến API, timeout 5 giây tránh bị treo.
        response = Lib.requests.get(url_api_nhaccuatui, timeout=5)

        #Nếu status != 200 thì raise lỗi ngay để nhảy vào except.
        response.raise_for_status()

        #Parse nội dung JSON trả về từ API.
        json_data = response.json()

        #Lấy danh sách bài hát trong JSON.
        #Nếu thiếu "data" hoặc "songs", trả về list rỗng.
        songs = json_data.get("data", {}).get("songs", [])

        #Lấy bài hát đầu tiên (kết quả ưu tiên nhất).
        first_song = songs[0] if songs else None

        #Nếu không có bài hát nào thì báo lỗi và trả None.
        if not first_song:
            Lib.show_log("DEV Custom Music: Không Tìm Thấy Dữ Liệu Bài Hát", color=Lib.Color.RED)
            return None, None, None, None

        #Lấy thông tin cơ bản của bài hát.
        name = first_song.get("name", "")              # Tên bài hát
        image = first_song.get("image", "")            # Ảnh cover
        artistName = first_song.get("artistName", "")  # Tên nghệ sĩ

        #Danh sách các luồng stream nhạc (320kbps, 128kbps...)
        streams = first_song.get("streamURL", [])

        #Tìm link stream chất lượng 320kbps nếu có
        stream_320 = next((s for s in streams if s.get("type") == "320"), None)

        #Tìm link stream chất lượng 128kbps nếu không có 320
        stream_128 = next((s for s in streams if s.get("type") == "128"), None)

        #Ưu tiên chọn stream chất lượng cao nhất có sẵn
        if stream_320:
            url_audio = stream_320.get("stream")
        elif stream_128:
            url_audio = stream_128.get("stream")
        else:
            url_audio = None  #trả Về None Nếu Không có link stream nào hợp lệ

        #print(f"URL Âm Thanh: {url_audio}")
        #print(f"Tên Bài Hát: {name}")
        #print(f"Tên Nghệ Sĩ: {artistName}")
        #print(f"URL Hình Ảnh: {image}")

        #Trả về return dữ liệu chuẩn theo format của hệ thống VBot MediaPlayer:
        #1: URL audio
        #2: Tiêu đề bài hát: "Tên bài - Ca sĩ"
        #3: Ảnh cover
        #4: Tên nguồn phát (source)
        return url_audio, f"{name} - {artistName}", image, "NhacCuaTui"

    except Exception as e:
        #Nếu bất kỳ lỗi nào xảy ra (timeout, mạng, parse json...)
        Lib.show_log(f"Lỗi DEV Custom Music: {e}", color=Lib.Color.RED)
        return None, None, None, None