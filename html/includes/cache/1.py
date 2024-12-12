import vlc
import time
import json
import random  
# Thêm thư viện random để phát ngẫu nhiên

class MediaPlayer:
    def __init__(self, play_mode='sequential'):
        self.instance = vlc.Instance('--aout=alsa')
        self.media_player = self.instance.media_player_new()
        self.play_mode = play_mode  # Lưu chế độ phát nhạc (ngẫu nhiên hoặc lần lượt)
        self.played_songs = []  # Biến lưu trữ các bài hát đã phát

    def Play_Media(self, url):
        try:
            # Nếu media player đang phát sẽ dừng sau đó phát cái mới
            if self.media_player.is_playing():
                self.media_player.stop()

            # Thiết lập media mới
            media = self.instance.media_new(url)
            self.media_player.set_media(media)
            self.media_player.audio_set_volume(50)
            self.media_player.play()

            # Chờ nhạc bắt đầu phát
            start_time = time.time()
            while time.time() - start_time < 5:  # Đợi tối đa 5 giây
                state = self.media_player.get_state()
                if state == vlc.State.Playing:
                    print(f"Đang phát: {url}")
                    break
                time.sleep(0.1)

            # Đợi nhạc phát đến khi kết thúc
            while True:
                state = self.media_player.get_state()
                if state in [vlc.State.Ended, vlc.State.Stopped, vlc.State.Error]:
                    print(f"Đã dừng: {url}")
                    break
                time.sleep(0.1)

        except Exception as e:
            print(f"Lỗi khi phát nhạc: {e}")
            return False

    def get_zingmp3_link(self, song_id):
        """
        Lấy link ZingMp3 theo song_id (ở đây cần có API hoặc logic xử lý thực tế)
        """
        # Giả sử trả về link mp3 từ ZingMp3
        zingmp3_url = f"http://zingmp3.vn/link/{song_id}.mp3"
        print(f"Đang lấy link ZingMp3: {zingmp3_url}")
        return zingmp3_url

    def get_youtube_link(self, youtube_id):
        """
        Lấy link Youtube từ youtube_id (ở đây cần có API hoặc logic xử lý thực tế)
        """
        # Giả sử trả về link mp3 từ YouTube
        youtube_url = f"http://youtube.com/audio/{youtube_id}.mp3"
        print(f"Đang lấy link YouTube: {youtube_url}")
        return youtube_url

    def play_list_player(self, playlist_file):
        try:
            # Đọc danh sách nhạc từ PlayList.json
            with open(playlist_file, 'r') as file:
                data = json.load(file)
                playlist = data.get("data", [])
                
            if not playlist:
                print("Danh sách nhạc trống.")
                return

            # Nếu chế độ phát là ngẫu nhiên, trộn danh sách bài hát
            if self.play_mode == 'random':
                random.shuffle(playlist)
                print("Đang phát nhạc ngẫu nhiên.")

            # Phát tuần tự các bài nhạc trong danh sách
            while playlist:
                if self.play_mode == 'random' and len(self.played_songs) < len(playlist):
                    # Nếu phát ngẫu nhiên, chọn bài chưa phát
                    song = random.choice([s for s in playlist if s not in self.played_songs])
                else:
                    # Nếu phát lần lượt, lấy bài đầu tiên
                    song = playlist.pop(0)

                audio_file = song.get('audio')
                source = song.get('source')

                # Nếu bài hát chưa phát, phát nó
                if song not in self.played_songs:
                    if source == "Local":
                        # Nếu là Local, phát luôn
                        self.Play_Media(audio_file)
                    elif source == "ZingMp3":
                        # Nếu là ZingMp3, lấy link rồi phát
                        song_id = song.get('ids_list')  # Lấy song_id từ 'ids_list'
                        zing_url = self.get_zingmp3_link(song_id)
                        self.Play_Media(zing_url)
                    elif source == "Youtube":
                        # Nếu là Youtube, lấy link rồi phát
                        youtube_id = song.get('ids_list')  # Lấy youtube_id từ 'ids_list'
                        youtube_url = self.get_youtube_link(youtube_id)
                        self.Play_Media(youtube_url)
                    else:
                        print(f"Không hỗ trợ nguồn: {source}")

                    # Sau khi phát xong, thêm bài hát vào danh sách đã phát
                    self.played_songs.append(song)

            print("Danh sách nhạc đã phát xong hoặc bị dừng.")

        except Exception as e:
            print(f"Lỗi khi đọc hoặc phát danh sách nhạc: {e}")

# Khởi tạo đối tượng và phát danh sách nhạc từ PlayList.json
play_mode = 'random'  # Hoặc 'sequential' nếu muốn phát theo thứ tự
player = MediaPlayer(play_mode)
player.play_list_player("PlayList.json")
