'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

"""
HƯỚNG DẪN SỬ DỤNG
- VBot tự gọi ``await custom_music_async(input_text)`` khi dùng nguồn nhạc tùy chỉnh.
- Giữ nguyên tên hàm. Kết quả phải là ``(audio_url, title, cover_url, source_name)``;
  nếu không tìm thấy hoặc có lỗi, trả ``(None, None, None, None)``.
- ``input_text`` là từ khóa/tên bài hát. Có thể thay URL API và quy tắc chọn chất lượng trong hàm.
- File không có biến toàn cục cấu hình. Thời gian hiện tại và event loop dùng từ ``Lib``.
"""

import aiohttp

import Lib


async def custom_music_async(input_text: str):
    input_text = (input_text or "").strip()
    if not input_text:
        return None, None, None, None
    Lib.show_log(
        f"DEV Custom Music search: {input_text}",
        color=Lib.Color.YELLOW,
    )
    params = {
        "keyword": input_text,
        "correct": "false",
        "timestamp": int(Lib.time.time() * 1000),
    }
    try:
        timeout = aiohttp.ClientTimeout(total=6, sock_connect=5, sock_read=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            async with session.get(
                "https://graph.nhaccuatui.com/api/v3/search/all",
                params=params,
            ) as response:
                response.raise_for_status()
                data = await response.json(content_type=None)

        if not isinstance(data, dict):
            raise ValueError("API trả dữ liệu không phải JSON object")
        result_data = data.get("data")
        if not isinstance(result_data, dict):
            raise ValueError("API thiếu trường data hợp lệ")
        songs = result_data.get("songs", [])
        if not isinstance(songs, list):
            raise ValueError("API trả danh sách bài hát không hợp lệ")
        if not songs:
            Lib.show_log(
                "DEV Custom Music: Không tìm thấy bài hát",
                color=Lib.Color.RED,
            )
            return None, None, None, None

        song = songs[0]
        if not isinstance(song, dict):
            raise ValueError("Thông tin bài hát không hợp lệ")
        streams = song.get("streamURL", [])
        if not isinstance(streams, list):
            streams = []
        selected = next(
            (item for item in streams if isinstance(item, dict) and item.get("type") == "320"),
            None,
        )
        selected = selected or next(
            (item for item in streams if isinstance(item, dict) and item.get("type") == "128"),
            None,
        )
        audio_url = selected.get("stream") if selected else None
        title = f"{song.get('name', '')} - {song.get('artistName', '')}"
        return audio_url, title, song.get("image", ""), "NhacCuaTui"
    except (aiohttp.ClientError, Lib.asyncio.TimeoutError, ValueError, TypeError) as exc:
        Lib.show_log(f"DEV Custom Music error: {exc}", color=Lib.Color.RED)
        return None, None, None, None
