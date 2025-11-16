from rpi_ws281x import PixelStrip, Color
import time

# ===== Cấu hình LED =====
LED_COUNT = 24         # Số lượng LED
LED_PIN = 10           # GPIO10 (SPI MOSI)
LED_FREQ_HZ = 800000   # WS2812 yêu cầu 800kHz
LED_DMA = 10
LED_BRIGHTNESS = 250   # Độ sáng (0-255)
LED_INVERT = False
LED_CHANNEL = 0
LED_STRIP = None       # Mặc định GRB

strip = PixelStrip(LED_COUNT, LED_PIN, LED_FREQ_HZ, LED_DMA, LED_INVERT, LED_BRIGHTNESS, LED_CHANNEL)
strip.begin()

def color_wipe(color, wait_ms=400):
    for i in range(strip.numPixels()):
        strip.setPixelColor(i, color)
        strip.show()
        time.sleep(wait_ms / 1000.0)

def rainbow_cycle(wait_ms=20, iterations=1):
    for j in range(256 * iterations):
        for i in range(strip.numPixels()):
            pixel_index = (i * 256 // strip.numPixels()) + j
            strip.setPixelColor(i, wheel(pixel_index & 255))
        strip.show()
        time.sleep(wait_ms / 1000.0)

def wheel(pos):
    if pos < 85:
        return Color(pos * 3, 255 - pos * 3, 0)
    elif pos < 170:
        pos -= 85
        return Color(255 - pos * 3, 0, pos * 3)
    else:
        pos -= 170
        return Color(0, pos * 3, 255 - pos * 3)

# ===== Chạy thử =====
try:
    while True:
        print("Chạy Màu Đỏ")
        color_wipe(Color(255, 0, 0))
        print("Chạy Màu Xanh lá")
        color_wipe(Color(0, 255, 0))
        print("Chạy Màu Xanh dương")
        color_wipe(Color(0, 0, 255))
        print("Chạy Màu Cầu vồng")
        rainbow_cycle()

except KeyboardInterrupt:
    print("Tắt LED...")
    color_wipe(Color(0, 0, 0), 10)
