"""
Custom Led
"""

#Code Mẫu sử dụng Led WS2812
#Các Hàm hiệu ứng def cần được giữ nguyên, code sửa đổi hiệu ứng thay đổi bên trong hàm đó
#Các Loại Led Khác cũng tự tự và cần thêm thư viện tương ứng

#Thư Viện VBot: Lib
import Lib

import time

#Thư Viện LED WS281x
from rpi_ws281x import PixelStrip, Color

"""
Cấu hình LED
"""
#Số lượng LED WS2812 (Lấy trong Config.json)
LED_COUNT = Lib.config['smart_config']['led']['number_led']

#Chân GPIO dùng để điều khiển LED Sử dụng GPIO10 (Lấy trong Config.json)
LED_PIN = Lib.config['smart_config']['led']['led_gpio']

#Tần số PWM 800kHz cho WS2812
LED_FREQ_HZ = 800000

#DMA channel để phát tín hiệu
LED_DMA = 10

#Đảo ngược chiều led True hoặc False (Lấy trong Config.json)
LED_INVERT = Lib.config['smart_config']['led']['led_invert']

#Độ sáng của LED (0-255)
LED_BRIGHTNESS = Lib.led_brightness
#LED_BRIGHTNESS = Lib.config['smart_config']['led']['brightness']

# Khởi tạo dải LED
strip = PixelStrip(LED_COUNT, LED_PIN, LED_FREQ_HZ, LED_DMA, LED_INVERT, LED_BRIGHTNESS)
strip.begin()

#Tắt các led khi khởi tạo thành công
for i in range(strip.numPixels()):
    strip.setPixelColor(i, Color(0, 0, 0))
strip.show()


"""
Các Hiệu Ứng Của LED
"""
#Tắt LED
#chỉ thay đổi code bên trong hàm "def LED_OFF()"
def LED_OFF():
    global Lib
    #Khóa Luồng, Đảm bảo chỉ có 1 hiệu ứng led được chạy
    with Lib.led_effect_lock:
        Lib.led_effect_active = False
        for i in range(strip.numPixels()):
            strip.setPixelColor(i, Color(0, 0, 0))
        strip.show()


#Hiệu ứng LED_LOADING Xử Lý Dữ Liệu
#chỉ thay đổi code bên trong hàm "def LED_LOADING()"
def LED_LOADING():
    global Lib
    Lib.led_effect_active = True
    num_pixels = strip.numPixels()
    #Số LED sáng cùng lúc trên mỗi nửa
    num_dots = 3
    #Độ mờ sáng dần của LED phía sau
    fade_step = 85
    #Màu xanh lá cây
    BASE_COLOR = Color(0, 255, 0)
    #Sử dụng vòng while để kiểm tra trạng thái LED cần sử dụng trong toàn bộ chương trình
    while Lib.led_effect_active:
        if Lib.current_led_effect != "LOADING":
            break
        strip.setBrightness(LED_BRIGHTNESS)
        #Chia số lượng led thành 2 nửa
        for j in range(num_pixels // 2):
            if Lib.current_led_effect != "LOADING":
                break
            #Đặt toàn bộ LED Tắt
            for i in range(num_pixels):
                strip.setPixelColor(i, Color(0, 0, 0))
            for t in range(num_dots):
                #Nửa đầu quay thuận
                pos1 = (j - t) % (num_pixels // 2)  
                #Nửa sau quay thuận từ giữa dải LED
                pos2 = ((num_pixels // 2) + (j - t)) % num_pixels
                #Giảm sáng dần
                brightness = max(0, 255 - (t * fade_step))
                # LED nửa đầu
                strip.setPixelColor(pos1, BASE_COLOR)
                # LED nửa sau
                strip.setPixelColor(pos2, BASE_COLOR)
            strip.show()
            #Tốc độ quay hiệu ứng
            time.sleep(0.08)
            if not Lib.led_effect_active:
                break
#KẾT THÚC LED LOADING



#Led LED_THINK khi được đánh thức wake up, Lắng nghe Câu Lệnh
#chỉ thay đổi code bên trong hàm "def LED_THINK()"
def LED_THINK(color_hex=Lib.config['smart_config']['led']['effect']['led_think']):
    global Lib
    Lib.led_effect_active = True
    num_pixels = strip.numPixels()
    #Chuyển đổi màu từ HEX trong Config.json sang số nguyên
    base_color = int('0x' + color_hex, 16)
    #Sử dụng vòng while để kiểm tra trạng thái LED cần sử dụng trong toàn bộ chương trình
    while Lib.led_effect_active:
        if Lib.current_led_effect != "THINK":
            break
        # Hiệu ứng sáng dần
        for brightness in range(0, 256, 5):  
            strip.setBrightness(brightness)
            for i in range(num_pixels):
                strip.setPixelColor(i, base_color)
            strip.show()
            #Điều chỉnh tốc sáng
            time.sleep(0.02)
        # Hiệu ứng mờ dần
        for brightness in range(255, -1, -5):  
            strip.setBrightness(brightness)
            for i in range(num_pixels):
                strip.setPixelColor(i, base_color)
            strip.show()
            #Điều chỉnh tốc sáng
            time.sleep(0.02)  
        if not Lib.led_effect_active:
            break
#KẾT THÚC LED THINK



#Tắt Mic LED_MUTE
#chỉ thay đổi code bên trong hàm "def LED_MUTE()"
def LED_MUTE(color_hex=Lib.config['smart_config']['led']['effect']['led_mute']):
    global Lib
    Lib.led_effect_active = True
    #Sử dụng vòng while để kiểm tra trạng thái LED cần sử dụng trong toàn bộ chương trình
    while Lib.led_effect_active:
        if Lib.current_led_effect != "MUTE":
            break
        strip.setBrightness(LED_BRIGHTNESS)
        for i in range(strip.numPixels()):
            if Lib.current_led_effect != "MUTE":
                break
            strip.setPixelColor(i, int('0x' + color_hex, 16))
        strip.show()
        Lib.time.sleep(2)
        if not Lib.led_effect_active:
            break
#KẾT THÚC LED MUTE



#Led báo lỗi LED_ERROR
#chỉ thay đổi code bên trong hàm "def LED_ERROR()"
def LED_ERROR():
    global Lib
    Lib.led_effect_active = True
    #Sử dụng vòng while để kiểm tra trạng thái LED cần sử dụng trong toàn bộ chương trình
    while Lib.led_effect_active:
        if Lib.current_led_effect != "ERROR":
            break
        strip.setBrightness(LED_BRIGHTNESS)
        for i in range(strip.numPixels()):
            strip.setPixelColor(i, Color(255, 0, 0))
            strip.show()
            Lib.time.sleep(0.05)
        for _ in range(5):
            for i in range(strip.numPixels()):
                strip.setPixelColor(i, Color(0, 0, 0))
            strip.show()
            Lib.time.sleep(0.5)
            for i in range(strip.numPixels()):
                strip.setPixelColor(i, Color(255, 0, 0))
            strip.show()
            Lib.time.sleep(0.5)
            if not Lib.led_effect_active:
                break
#KẾT THÚC LED_ERROR 



#Led khi tạm dừng phát LED_PAUSE
#chỉ thay đổi code bên trong hàm "def LED_PAUSE()"
def LED_PAUSE():
    global Lib
    Lib.led_effect_active = True
    def set_strip_color_brightness(hue, brightness):
        color = hsv_to_rgb(hue / 360.0, 1.0, 1.0)
        r, g, b = color
        for i in range(strip.numPixels()):
            strip.setPixelColor(i, Color(r * brightness // 255, g * brightness // 255, b * brightness // 255))
        strip.show()
    def hsv_to_rgb(h, s, v):
        if s == 0.0: return int(v * 255), int(v * 255), int(v * 255)
        i = int(h * 6.0)
        f = (h * 6.0) - i
        p = v * (1.0 - s)
        q = v * (1.0 - s * f)
        t = v * (1.0 - s * (1.0 - f))
        i = i % 6
        if i == 0: r, g, b = v, t, p
        if i == 1: r, g, b = q, v, p
        if i == 2: r, g, b = p, v, t
        if i == 3: r, g, b = p, q, v
        if i == 4: r, g, b = t, p, v
        if i == 5: r, g, b = v, p, q
        return int(r * 255), int(g * 255), int(b * 255)
    #Sử dụng vòng while để kiểm tra trạng thái LED cần sử dụng trong toàn bộ chương trình
    while Lib.led_effect_active:
        if Lib.current_led_effect != "PAUSE":
            break
        strip.setBrightness(LED_BRIGHTNESS)
        hue = Lib.random.randint(0, 360)
        for brightness in range(0, 256, 5):
            if Lib.current_led_effect != "PAUSE":
                break
            set_strip_color_brightness(hue, brightness)
            Lib.time.sleep(0.09)
            if not Lib.led_effect_active:
                break
        for brightness in range(255, -1, -5):
            if Lib.current_led_effect != "PAUSE":
                break
            set_strip_color_brightness(hue, brightness)
            Lib.time.sleep(0.09)
            if not Lib.led_effect_active:
                break
#KẾT THÚC LED_PAUSE



#Led Speak TTS, trạng thái led khi phát kết quả, dữ liệu, âm nhạc
#Chỉ thay đổi code bên trong hàm "def LED_SPEAK()"
def LED_SPEAK():
    global Lib
    Lib.led_effect_active = True
    num_pixels = strip.numPixels()
    #Số LED sáng cùng lúc trên mỗi nửa
    num_dots = 3
    #Độ mờ dần của LED phía sau
    fade_step = 85
    #Màu Xanh lá Cây
    BASE_COLOR = Color(0, 255, 0)
    #Sử dụng vòng while để kiểm tra trạng thái LED cần sử dụng trong toàn bộ chương trình
    while Lib.led_effect_active:
        if Lib.current_led_effect != "SPEAK":
            break
        strip.setBrightness(LED_BRIGHTNESS)
        #Sử Dụng reversed QUAY NGƯỢC LẠI hiệu ứng LOADING
        for j in reversed(range(num_pixels // 2)):
            if Lib.current_led_effect != "SPEAK":
                break
            # Tắt toàn bộ LED
            for i in range(num_pixels):
                strip.setPixelColor(i, Color(0, 0, 0))
            for t in range(num_dots):
                # Nửa đầu quay NGƯỢC
                pos1 = (j + t) % (num_pixels // 2)
                # Nửa sau quay NGƯỢC từ giữa dải LED
                pos2 = ((num_pixels // 2) + (j + t)) % num_pixels  
                # Giảm sáng dần
                brightness = max(0, 255 - (t * fade_step))
                # LED nửa đầu
                strip.setPixelColor(pos1, BASE_COLOR)
                # LED nửa sau
                strip.setPixelColor(pos2, BASE_COLOR)
            strip.show()
            #Tốc độ quay hiệu ứng
            time.sleep(0.13)
            if not Lib.led_effect_active:
                break
#KẾT THÚC LED_SPEAK



#Led khi khởi động chương trình VBot
#Chỉ thay đổi code bên trong hàm "def LED_STARTUP()"
def LED_STARTUP():
    global Lib
    Lib.led_effect_active = True
    num_pixels = strip.numPixels()
    #Danh Sách Màu
    colors = [
        Color(255, 0, 255),
        Color(0, 255, 255),
        Color(255, 0, 0),
        Color(0, 255, 0),
        Color(0, 0, 255),
        Color(255, 255, 0),
        Color(255, 255, 255)
    ]
    #Sử dụng vòng while để kiểm tra trạng thái LED cần sử dụng trong toàn bộ chương trình
    while Lib.led_effect_active and Lib.current_led_effect == "STARTUP":
        if Lib.current_led_effect != "STARTUP":
            break
        for color in colors:
            if Lib.current_led_effect != "STARTUP":
                break
            #Chia số lượng đèn LED Làm 2 nửa
            for i in range(num_pixels // 2):
                #Nửa Phải
                strip.setPixelColor((num_pixels // 2) + i, color)
                #Nửa Trái
                strip.setPixelColor((num_pixels // 2) - i, color)
                strip.show()
                time.sleep(0.09)
            time.sleep(0.1)
            #Mờ dần khi chuyển sang màu mới
            for brightness in range(255, 0, -25):
                dimmed_color = Color(
                    #Đỏ
                    (color >> 16) * brightness // 255,
                    #Xanh Lá
                    ((color >> 8) & 0xFF) * brightness // 255,
                    #Xanh Dương
                    (color & 0xFF) * brightness // 255
                )
                for i in range(num_pixels):
                    strip.setPixelColor(i, dimmed_color)
                strip.show()
                time.sleep(0.05)
            if not Lib.led_effect_active:
                break
#KẾT THÚC LED_STARTUP



#Led khi âm lượng được thay đổi volume_change sẽ là giá trị âm lượng được truyền vào hàm có giá trị từ 0 tới 100
def LED_VOLUME(volume_change):
    def wheel(pos):
        if pos < 85:
            return Color(pos * 3, 255 - pos * 3, 0)
        elif pos < 170:
            pos -= 85
            return Color(255 - pos * 3, 0, pos * 3)
        else:
            pos -= 170
            return Color(0, pos * 3, 255 - pos * 3)
    leds_on = int((volume_change / Lib.volume_Max) * LED_COUNT)
    for i in range(leds_on):
        strip.setPixelColor(i, wheel((i * 256 // LED_COUNT) & 255))
    for i in range(leds_on, LED_COUNT):
        strip.setPixelColor(i, Color(0, 0, 0))
    strip.show()
    Lib.time.sleep(0.4)
    for i in range(LED_COUNT):
        strip.setPixelColor(i, Color(0, 0, 0))
    strip.show()
    return
#KẾT THÚC LED_VOLUME


"""
#Kết Thúc Các Hiệu Ứng Của LED
"""


#Chạy thử: $:> python3 Dev_Led.py
"""
try:
    #Ví dụ hiệu ứng LED STARTUP
    Lib.led_effect_active = True
    Lib.current_led_effect = "STARTUP"
    LED_STARTUP()
except KeyboardInterrupt:
    LED_OFF()
"""