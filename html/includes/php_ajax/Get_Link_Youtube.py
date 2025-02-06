import pytubefix
import sys

def get_link_youtube(video_url):
    try:
        yt = pytubefix.YouTube(f"https://www.youtube.com/watch?v={video_url}")
        audio_stream = yt.streams.get_audio_only()
        audio_url = audio_stream.url
        if audio_url:
            print(audio_url)
            return audio_url
        return None
    except Exception as e:
        #Lib.show_log(f"lỗi khi lấy âm thanh từ video Youtube: {e}", color=Lib.Color.RED)
        return None

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Sử dụng: python3 Get_Link_Youtube.py <video_id>")
    else:
        video_id = sys.argv[1]
        get_link_youtube(video_id)
