'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''


"""
HƯỚNG DẪN SỬ DỤNG

1. Chọn ``calendar.source`` là ``dev_calendar`` trong Config.json hoặc WebUI.
2. Khởi động lại chương trình VBot.
3. Có thể hỏi: lịch dương hôm nay, lịch âm ngày mai, lịch âm ngày kia,
   lịch âm 20/09/2026 hoặc lịch âm ngày 20 tháng 9 năm 2026.

``calendar_type`` nhận ``solar`` hoặc ``lunar``.
``calendar_data`` chứa label, weekday, iso_date, solar và lunar. Hàm phải trả
về ``(audio, text)``. Để audio rỗng nếu muốn VBot tự tạo TTS từ text. Nếu hàm
lỗi hoặc trả về hai giá trị rỗng, VBot sẽ tự dùng kết quả lịch hệ thống.
"""

import Lib


# Các tùy chọn mẫu để thay đổi nội dung mà không sửa phần xử lý chính.
INCLUDE_SOLAR_DATE_IN_LUNAR_RESULT = True
INCLUDE_WEEKDAY_IN_LUNAR_RESULT = True

# Có thể đặt đường dẫn MP3 riêng cho từng loại lịch. Để trống để dùng TTS.
CUSTOM_AUDIO = {
    "solar": "",
    "lunar": "",
}


def _day_label(calendar_data):
    """Trả về nhãn tự nhiên cho ngày tương đối hoặc ngày cụ thể."""
    label = str(calendar_data.get("label") or "hôm nay").strip()
    return "" if label == "ngày được hỏi" else label.capitalize()


def _solar_text(calendar_data):
    solar = calendar_data["solar"]
    return f"ngày {solar['day']} tháng {solar['month']} năm {solar['year']}"


def _lunar_text(calendar_data):
    lunar = calendar_data["lunar"]
    day_prefix = "mùng" if int(lunar["day"]) <= 10 else "ngày"
    leap_text = " nhuận" if lunar.get("leap") else ""
    return (
        f"{day_prefix} {lunar['day']} tháng {lunar['month']}{leap_text} "
        f"năm {lunar['year_name']}"
    )


def format_solar_result(calendar_data):
    """Tùy chỉnh câu trả lời dương lịch tại đây."""
    label = _day_label(calendar_data)
    weekday = calendar_data["weekday"]
    solar_text = _solar_text(calendar_data)
    if label:
        return f"{label} là {weekday}, {solar_text} dương lịch."
    return f"{weekday.capitalize()}, {solar_text} dương lịch."


def format_lunar_result(calendar_data):
    """Tùy chỉnh câu trả lời âm lịch tại đây."""
    label = _day_label(calendar_data)
    solar_text = _solar_text(calendar_data)
    lunar_text = _lunar_text(calendar_data)

    if INCLUDE_SOLAR_DATE_IN_LUNAR_RESULT:
        subject = f"{label}, {solar_text}" if label else solar_text.capitalize()
        if INCLUDE_WEEKDAY_IN_LUNAR_RESULT:
            subject += f", {calendar_data['weekday']}"
        return f"{subject} dương lịch là {lunar_text} âm lịch."

    subject = label or "Ngày được hỏi"
    if INCLUDE_WEEKDAY_IN_LUNAR_RESULT:
        subject += f", {calendar_data['weekday']}"
    return f"{subject} là {lunar_text} âm lịch."


async def custom_calendar(
    text_input,
    text_input_handle,
    calendar_type,
    calendar_data,
):
    """Điểm vào bắt buộc của tính năng lịch tùy chỉnh."""
    Lib.show_log(
        f"[Dev_Calendar] Câu lệnh: {text_input} | "
        f"Loại lịch: {calendar_type} | Ngày: {calendar_data.get('iso_date')}",
        color=Lib.Color.WHITE,
    )

    if calendar_type == "solar":
        response_text = format_solar_result(calendar_data)
    elif calendar_type == "lunar":
        response_text = format_lunar_result(calendar_data)
    else:
        return "", ""

    response_audio = str(CUSTOM_AUDIO.get(calendar_type) or "").strip()
    return response_audio, response_text
