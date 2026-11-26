'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

"""
HƯỚNG DẪN SỬ DỤNG
- VBot tự gọi ``get_link_youtube(video_url)`` khi trong ``Config.php -> Youtube -> Xử lý Link Youtube``
  chọn ``Dev_Youtube_GetLink.py Xử Lý`` (``Config.json -> media_player -> youtube -> get_link_source = "dev_youtube_getlink"``).
- Giữ nguyên tên hàm ``get_link_youtube(video_url)``.
- Tham số ``video_url``: nhận URL video YouTube hoặc Video ID 11 ký tự.
- Kết quả trả về là tuple 4 phần tử: ``(audio_url, title, thumbnail_url, "Youtube")``.
  Nếu không lấy được link hoặc có lỗi, trả về ``(None, None, None, None)``.
- Người dùng có thể tự chỉnh sửa client của ``pytubefix``, chuyển sang ``yt-dlp`` hoặc gọi API riêng.
"""

import pytubefix

import Lib


def _extract_video_id(url_or_id: str) -> str:
    s = (url_or_id or "").strip()
    if Lib.re.fullmatch(r"[A-Za-z0-9_-]{11}", s):
        return s
    parsed = Lib.urlparse(s)
    if "youtu.be" in parsed.netloc.lower():
        vid = parsed.path.strip("/").split("/")[0]
        if Lib.re.fullmatch(r"[A-Za-z0-9_-]{11}", vid):
            return vid
    qs = Lib.parse_qs(parsed.query)
    if "v" in qs and Lib.re.fullmatch(r"[A-Za-z0-9_-]{11}", qs["v"][0]):
        return qs["v"][0]
    match = Lib.re.search(r"(?:v=|/shorts/|/embed/|/live/)([A-Za-z0-9_-]{11})", s)
    if match:
        return match.group(1)
    return ""


def get_link_youtube(video_url: str):
    """
    Lấy Link/URL stream âm thanh từ video YouTube bằng client mặc định của pytubefix (VISION_OS).
    Trả về: (audio_url, title, thumbnail_url, "Youtube")
    """
    t_start = Lib.time.monotonic()
    video_id = _extract_video_id(video_url)
    if not video_id:
        Lib.show_log(
            f"[Dev_Youtube_GetLink] Video URL/ID không hợp lệ: {video_url}",
            color=Lib.Color.RED,
        )
        return None, None, None, None

    yt_url = f"https://www.youtube.com/watch?v={video_id}"
    try:
        yt = pytubefix.YouTube(yt_url)
        stream = yt.streams.get_by_itag(140) or yt.streams.get_audio_only()
        if not stream or not getattr(stream, "url", None):
            Lib.show_log(
                f"[Dev_Youtube_GetLink] Không tìm thấy luồng âm thanh cho: {yt_url}",
                color=Lib.Color.RED,
            )
            return None, None, None, None

        audio_url = stream.url
        title = (yt.vid_info or {}).get("videoDetails", {}).get("title") or getattr(yt, "title", None) or "Youtube Audio"
        thumbnail_url = f"https://img.youtube.com/vi/{video_id}/maxresdefault.jpg"
        Lib.show_log(
            f"[Dev_Youtube_GetLink] Đã lấy link ({getattr(yt, 'client', 'DEFAULT')}, itag={stream.itag}) sau {Lib.time.monotonic() - t_start:.3f}s: {title}",
            color=Lib.Color.CYAN,
        )
        return audio_url, title, thumbnail_url, "Youtube"
    except Exception as e:
        Lib.log_exception("Dev_Youtube_GetLink.get_link_youtube", e, {"video_url": yt_url})
        Lib.show_log(
            f"[Dev_Youtube_GetLink] Lỗi khi lấy link Youtube: {e}",
            color=Lib.Color.RED,
        )
        return None, None, None, None
