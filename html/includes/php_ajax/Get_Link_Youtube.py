import sys
import yt_dlp

def get_link_youtube(video_id):
    # Tạo URL đầy đủ từ ID video
    video_url = f"https://www.youtube.com/watch?v={video_id}"
    ydl_opts = {
        'format': 'bestaudio',   # Chọn âm thanh tốt nhất
        'quiet': True,           # Tắt thông báo
        'noplaylist': True,      # Không tải danh sách phát
        'extract_flat': True,    # Tăng tốc bằng cách không tải thêm thông tin không cần thiết
    }
    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        try:
            info_dict = ydl.extract_info(video_url, download=False)
            # Lấy link audio URL
            #print(info_dict)
            if info_dict.get('url'):
                link_player = info_dict.get('url')
                print(link_player)
                #print(info_dict.get('fulltitle'))
                #print(info_dict.get('release_date'))
                return link_player
        except Exception as e:
            print(f"Lỗi: {e}")
        return None

if __name__ == "__main__":
    # Kiểm tra tham số CLI
    if len(sys.argv) != 2:
        print("Sử dụng: python3 Get_Link_Youtube.py <video_id>")
    else:
        video_id = sys.argv[1]
        get_link_youtube(video_id)
