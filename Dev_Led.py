"""
Custom Led
Demo sử dụng Led WS2812
"""
import Lib
import time
from rpi_ws281x import PixelStrip, Color

"""
Cấu hình LED
"""
#Số lượng LED WS2812
LED_COUNT = Lib.config['smart_config']['led']['number_led']

#Chân GPIO dùng để điều khiển LED Sử dụng GPIO10
LED_PIN = Lib.config['smart_config']['led']['led_gpio']

#Tần số PWM 800kHz cho WS2812
LED_FREQ_HZ = 800000

#DMA channel để phát tín hiệu
LED_DMA = 10

#Đảo ngược chiều led True hoặc False
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
    wait_ms = 1
    def wheel(pos):
        if pos < 0 or pos > 255:
            return Color(0, 0, 0)
        if pos < 85:
            return Color(255 - pos * 3, pos * 3, 0)
        elif pos < 170:
            pos -= 85
            return Color(0, 255 - pos * 3, pos * 3)
        else:
            pos -= 170
            return Color(pos * 3, 0, 255 - pos * 3)
    while Lib.led_effect_active and Lib.current_led_effect == "LOADING":
        strip.setBrightness(LED_BRIGHTNESS)
        for j in range(256):
            for i in range(strip.numPixels()):
                pixel_index = (i * 256 // strip.numPixels()) + j
                strip.setPixelColor(i, wheel(pixel_index & 255))
            strip.show()
            Lib.time.sleep(wait_ms / 1500.0)
            if not Lib.led_effect_active:
                break


#Led LED_THINK khi được đánh thức wake up, Lắng nghe Câu Lệnh
#chỉ thay đổi code bên trong hàm "def LED_THINK()"
def LED_THINK(color_hex=Lib.config['smart_config']['led']['effect']['led_think']):
    global Lib
    Lib.led_effect_active = True
    if Lib.led_effect_active and Lib.current_led_effect == "THINK":
        strip.setBrightness(LED_BRIGHTNESS)
        for i in range(strip.numPixels()):
            strip.setPixelColor(i, int('0x' + color_hex, 16))
        strip.show()


#Tắt Mic LED_MUTE
#chỉ thay đổi code bên trong hàm "def LED_MUTE()"
def LED_MUTE(color_hex=Lib.config['smart_config']['led']['effect']['led_mute']):
    global Lib
    Lib.led_effect_active = True
    while Lib.led_effect_active and Lib.current_led_effect == "MUTE":
        strip.setBrightness(LED_BRIGHTNESS)
        for i in range(strip.numPixels()):
            strip.setPixelColor(i, int('0x' + color_hex, 16))
        strip.show()
        Lib.time.sleep(2)
        if not Lib.led_effect_active:
            break


#Led báo lỗi LED_ERROR
#chỉ thay đổi code bên trong hàm "def LED_ERROR()"
def LED_ERROR():
    global Lib
    Lib.led_effect_active = True
    while Lib.led_effect_active and Lib.current_led_effect == "ERROR":
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
    while Lib.led_effect_active and Lib.current_led_effect == "PAUSE":
        strip.setBrightness(LED_BRIGHTNESS)
        hue = Lib.random.randint(0, 360)
        for brightness in range(0, 256, 5):
            set_strip_color_brightness(hue, brightness)
            Lib.time.sleep(0.09)
            if not Lib.led_effect_active:
                break
        for brightness in range(255, -1, -5):
            set_strip_color_brightness(hue, brightness)
            Lib.time.sleep(0.09)
            if not Lib.led_effect_active:
                break

#Led Speak TTS, trạng thái led khi phát kết quả, dữ liệu, âm nhạc
#Chỉ thay đổi code bên trong hàm "def LED_SPEAK()"
def LED_SPEAK():
    global Lib
    Lib.led_effect_active = True
    wait_ms = 10
    def wheel(pos):
        if pos < 0 or pos > 255:
            return Color(0, 0, 0)
        if pos < 85:
            return Color(255 - pos * 3, pos * 3, 0)
        elif pos < 170:
            pos -= 85
            return Color(0, 255 - pos * 3, pos * 3)
        else:
            pos -= 170
            return Color(pos * 3, 0, 255 - pos * 3)
    while Lib.led_effect_active and Lib.current_led_effect == "SPEAK":
        strip.setBrightness(Lib.led_brightness)
        for j in range(256):
            for i in range(strip.numPixels()):
                pixel_index = (i * 256 // strip.numPixels()) + j
                strip.setPixelColor(i, wheel(pixel_index & 255))
            strip.show()
            Lib.time.sleep(wait_ms / 1000.0)
            if not Lib.led_effect_active:
                break

#Led khi khởi động chương trình VBot
#Chỉ thay đổi code bên trong hàm "def LED_STARTUP()"
def LED_STARTUP():
    global Lib
    Lib.led_effect_active = True
    colors = [
        Color(255, 0, 255),  # Màu tím
        Color(0, 255, 255),  # Màu cyan
        Color(222, 128, 128),   # Màu teal
        Color(150, 128, 128),   # Màu teal
        Color(110, 128, 128),   # Màu teal
        Color(0, 255, 0),   # Màu xanh lá cây
        Color(0, 0, 255),   # Màu xanh dương
        Color(255, 255, 0), # Màu vàng
        Color(255, 255, 255), # Màu trắng
        Color(128, 0, 128),  # Màu màu dâm
        Color(255, 165, 0),  # Màu cam
        Color(0, 128, 128),   # Màu teal
        Color(255, 0, 0)   # Màu đỏ
        ]
    while Lib.led_effect_active and Lib.current_led_effect == "STARTUP":
        for color in colors:
            for i in range(strip.numPixels()):
                strip.setPixelColor(i, color)
                strip.show()
                Lib.time.sleep(0.05)
                if not Lib.led_effect_active:
                    break
            Lib.time.sleep(0.05)
            if not Lib.led_effect_active:
                break

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
"""
#Kết Thúc Các Hiệu Ứng Của LED
"""
#Chạy thử: $:> python3 Dev_Led.py
"""
try:
    #Ví dụ hiệu ứng LED Volume
    LED_VOLUME(55)
except KeyboardInterrupt:
    LED_OFF()
"""