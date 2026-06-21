'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

#Thư Viện VBot: Lib
import Lib
import aiohttp
    
#Màu Thông Báo Logs (color=Lib.Color.YELLOW) có thể sử dụng: (PURPLE, CYAN, DARKCYAN, BLUE, GREEN, YELLOW, RED, BOLD, UNDERLINE, END, WHITE)

#Tên hàm:  " async def custom_weather(text_input: str, text_input_handle: str) " không được thay đổi để phù hợp tương thích với hệ thống
async def custom_weather(text_input, text_input_handle):

    #mẫu Demo lấy dữ liệu thời tiết, để phân loại thời tiết ngày mai, ngày kia các bạn cần tự code xử lý dữ liệu text_input_lower hoặc text_input_handle

    Lib.show_log(f"[Dev_Weather] văn bản text_input: {text_input}", color=Lib.Color.WHITE)
    Lib.show_log(f"[Dev_Weather] văn bản text_input_handle: {text_input_handle}", color=Lib.Color.WHITE)

    try:

        WEATHER_CODE = {
            0: "trời quang",
            1: "trời chủ yếu quang",
            2: "trời có mây rải rác",
            3: "trời nhiều mây",
            45: "có sương mù",
            48: "có sương mù đóng băng",
            51: "mưa phùn nhẹ",
            53: "mưa phùn vừa",
            55: "mưa phùn dày",
            56: "mưa phùn đóng băng nhẹ",
            57: "mưa phùn đóng băng dày",
            61: "mưa nhỏ",
            63: "mưa vừa",
            65: "mưa to",
            66: "mưa đóng băng nhẹ",
            67: "mưa đóng băng nặng",
            71: "tuyết rơi nhẹ",
            73: "tuyết rơi vừa",
            75: "tuyết rơi dày",
            77: "có hạt tuyết",
            80: "mưa rào nhẹ",
            81: "mưa rào vừa",
            82: "mưa rào rất mạnh",
            85: "mưa tuyết nhẹ",
            86: "mưa tuyết nặng",
            95: "có giông",
            96: "có dông kèm mưa đá nhẹ",
            99: "có dông kèm mưa đá nặng",
        }


        #Mặc định Demo Hà Nội
        latitude = 21.0285
        longitude = 105.8542
        location_name = "Hà Nội"

        url = "https://api.open-meteo.com/v1/forecast"

        params = {
            "latitude": latitude,
            "longitude": longitude,
            "current": "temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,wind_direction_10m",
            "timezone": "Asia/Ho_Chi_Minh",
        }

        async with aiohttp.ClientSession(timeout=aiohttp.ClientTimeout(total=10)) as session:
            async with session.get(url, params=params) as response:
                if response.status != 200:
                    return "", f"Lỗi lấy thời tiết: status {response.status}"
                data = await response.json()

        current = data.get("current") or {}

        temperature = current.get("temperature_2m")
        humidity = current.get("relative_humidity_2m")
        weather_code = current.get("weather_code")

        weather_desc = WEATHER_CODE.get(
            weather_code,
            f"mã thời tiết không xác định {weather_code}"
        )

        weather_text = (
            f"Thời tiết hiện tại ở {location_name}: {weather_desc}. "
            f"Nhiệt độ {temperature} độ C, "
            f"độ ẩm {humidity} phần trăm. "
        )

        return "", weather_text

    except Exception as e:
        return "", f"Lỗi lấy thời tiết từ Open-Meteo: {e}"
