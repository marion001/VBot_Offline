"""Hiệu ứng LED VBot, được gom riêng theo từng loại phần cứng.

HƯỚNG DẪN
=========
File này chạy bằng Python thường, không đưa vào danh sách build .so. Sửa file
rồi khởi động lại VBot để nạp thay đổi. Các hiệu ứng báo loa/micro trong Led.py
không bị ảnh hưởng.

Mỗi loại đèn chỉ có một hàm tổng: ``ws281x()``, ``apa102()``,
``respeaker_mic_array()`` hoặc ``custom()``. Các hàm hiệu ứng con và danh sách
tên/nhãn của chúng được khai báo ngay bên trong hàm tổng tương ứng.

THÊM HIỆU ỨNG
=============
1. Mở đúng hàm tổng theo phần cứng bạn dùng, ví dụ ``ws281x()`` cho WS2812.
2. Thêm một hàm con trong đúng hàm tổng. Ví dụ trong ``ws281x()``, các hàm
   con dùng trực tiếp được ``strip``, ``color_factory`` và ``set_pixel``:

       def nhap_nhay(state, now, led_count):
           mau = _selected_color(state)
           if math.sin(now * 2 * math.pi) < 0:
               mau = (0, 0, 0)
           for index in range(led_count):
               set_pixel(index, color_factory(*mau))
           strip.show()

   Trong ``apa102()``, hàm con dùng ``device.set_pixel(...)`` rồi gọi
   ``device.show()``. Trong ``respeaker_mic_array()``, hàm con dùng
   ``write(6, data)`` với 4 giá trị cho mỗi LED. Trong ``custom()``, hàm con gọi
   ``render(danh_sach_mau_rgb)`` để chuyển màu đến ``Dev_Led.py``.

3. Thêm tên và nhãn của hàm vào từ điển ``effects`` bên trong hàm tổng:

       effects["nhap_nhay"] = ("Nhấp Nháy", nhap_nhay)

   Tên bên trái (``nhap_nhay``) là giá trị dùng trong API/MQTT. Tên bên phải là
   nội dung hiển thị trong Home Assistant. Chỉ thêm hiệu ứng vào đúng loại LED.
4. Khởi động lại VBot. API/MQTT và Home Assistant sẽ hiển thị danh sách hiệu
   ứng của loại LED đang cấu hình.

THAM SỐ CỦA HÀM HIỆU ỨNG CON
============================
* ``state["rgb_color"]``: màu chọn được, ba kênh RGB từ 0 đến 255.
* ``state["brightness"]``: độ sáng từ 0 đến 255.
* ``state["effect"]``: tên kỹ thuật của hiệu ứng hiện tại.
* ``now``: thời gian đơn điệu tính bằng giây, dùng để tạo chuyển động.
* ``led_count``: số bóng LED được cấu hình.

Các hàm hiệu ứng chạy liên tục khi đèn bật (khoảng 20 lần/giây). Nên tính màu
theo ``now`` và tránh vòng lặp chờ dài bên trong hiệu ứng.

MẪU GỌI API
===========
Ví dụ bật WS2812 với hiệu ứng cầu vồng:

    {"type": 2, "data": "led", "action": "turn_on",
     "effect": "rainbow", "rgb_color": [255, 120, 35], "brightness": 170}

Tên hiệu ứng được kiểm tra theo loại LED đang cấu hình; cùng một tên có thể có
logic riêng ở WS2812, APA102, ReSpeaker USB và LED tùy chỉnh.
"""

from __future__ import annotations

import colorsys
import json
import math
import os
import time


def normalize_backend(led_type):
    """Đổi led_type trong Config.json thành tên backend của file này."""
    value = str(led_type or "").strip().lower()
    if value in {"ws281x", "ws2812", "ws2811", "ws2815"}:
        return "ws281x"
    if value in {"apa102", "respeaker_2_mics_pi_hat_v1.0"}:
        return "apa102"
    if value == "respeaker_mic_array_v2.0":
        return "respeaker_mic_array"
    if value == "dev_custom_led":
        return "custom"
    return value


def _brightness(state):
    try:
        return max(0.0, min(255.0, float(state.get("brightness", 255)))) / 255.0
    except (TypeError, ValueError):
        return 1.0


def _rgb(color, scale=1.0):
    return tuple(max(0, min(255, round(int(channel) * scale))) for channel in color)


def _wheel(position):
    return tuple(round(channel * 255) for channel in colorsys.hsv_to_rgb(position % 1.0, 1.0, 1.0))


def _selected_color(state, scale=1.0):
    return _rgb(state.get("rgb_color", (255, 180, 100)), _brightness(state) * scale)


def _candle_color(state, now):
    flicker = (
        0.68
        + 0.16 * math.sin(now * 7.3)
        + 0.10 * math.sin(now * 13.1 + 1.7)
        + 0.06 * math.sin(now * 19.7 + 0.4)
    )
    selected = state.get("rgb_color", (255, 180, 100))
    warm = tuple(round(channel * ratio) for channel, ratio in zip(selected, (1.0, 0.48, 0.12)))
    return _rgb(warm, _brightness(state) * max(0.25, min(1.0, flicker)))


def _dispatch(effects, state, now, led_count, backend):
    """Tra cứu tên hiệu ứng; state=None chỉ yêu cầu lấy danh sách, không ghi LED."""
    if state is None:
        return {name: item[0] for name, item in effects.items()}
    if now is None:
        now = time.monotonic()
    effect_name = str(state.get("effect") or "solid").strip().lower().replace(" ", "_")
    item = effects.get(effect_name)
    if item is None:
        raise ValueError("Hiệu ứng không có trong nhóm " + backend + ": " + effect_name)
    item[1](state, now, max(0, int(led_count)))
    return True


_EFFECT_OPTIONS_CACHE = {}


def effect_options(led_type):
    """Lấy tên kỹ thuật và nhãn của loại LED mà không khởi tạo phần cứng."""
    backend = normalize_backend(led_type)
    if backend in _EFFECT_OPTIONS_CACHE:
        return dict(_EFFECT_OPTIONS_CACHE[backend])
    if backend == "ws281x":
        options = ws281x(None, None, None, None)
    elif backend == "apa102":
        options = apa102(None, None)
    elif backend == "respeaker_mic_array":
        options = respeaker_mic_array(None, 0, None)
    elif backend == "custom":
        options = custom(None, 0, None)
    else:
        options = {}
    _EFFECT_OPTIONS_CACHE[backend] = dict(options)
    return dict(options)


def effect_names(led_type):
    """Lấy các tên API/MQTT hợp lệ cho loại LED đang cấu hình."""
    return list(effect_options(led_type))


def effect_registry():
    """Trả về danh sách hiệu ứng theo từng adapter để WebUI dùng chung."""
    return {
        "ws281x": effect_options("ws281x"),
        "apa102": effect_options("apa102"),
        "respeaker_mic_array": effect_options("respeaker_mic_array"),
        "custom": effect_options("custom"),
    }


def _publish_effect_registry():
    """Cập nhật danh sách hiệu ứng cho các menu thao tác của WebUI."""
    root = os.path.dirname(os.path.abspath(__file__))
    path = os.path.join(root, "resource", "led_effects_registry.json")
    temporary_path = path + ".tmp"
    try:
        encoded = json.dumps(effect_registry(), ensure_ascii=False, indent=2, sort_keys=True)
        try:
            with open(path, "r", encoding="utf-8") as handle:
                if handle.read() == encoded:
                    return
        except OSError:
            pass
        with open(temporary_path, "w", encoding="utf-8", newline="\n") as handle:
            handle.write(encoded)
        os.replace(temporary_path, path)
    except (OSError, TypeError, ValueError):
        # LED rendering must remain available even if the WebUI metadata file
        # cannot be updated on a read-only installation.
        return


# ===========================================================================
# WS281x / WS2812 / WS2811 / WS2815
# ===========================================================================

def ws281x(strip, color_factory, set_pixel, state, now=None):
    """Hiệu ứng cho thư viện rpi_ws281x. Hàm con dùng strip, Color, set_pixel."""
    def solid(state, now, led_count):
        color = _selected_color(state)
        for index in range(led_count):
            set_pixel(index, color_factory(*color))
        strip.show()

    def breathe(state, now, led_count):
        pulse = 0.12 + 0.88 * (0.5 + 0.5 * math.sin(now * 2.0 * math.pi / 4.0))
        color = _selected_color(state, pulse)
        for index in range(led_count):
            set_pixel(index, color_factory(*color))
        strip.show()

    def color_loop(state, now, led_count):
        color = _rgb(_wheel(now / 10.0), _brightness(state))
        for index in range(led_count):
            set_pixel(index, color_factory(*color))
        strip.show()

    def night_pulse(state, now, led_count):
        pulse = 0.16 + 0.34 * (0.5 + 0.5 * math.sin(now * 2.0 * math.pi / 9.0))
        selected = state.get("rgb_color", (255, 180, 100))
        amber = tuple(round(a * 0.35 + b * 0.65) for a, b in zip(selected, (255, 115, 28)))
        color = _rgb(amber, _brightness(state) * pulse)
        for index in range(led_count):
            set_pixel(index, color_factory(*color))
        strip.show()

    def rainbow(state, now, led_count):
        brightness = _brightness(state)
        for index in range(led_count):
            hue = now * 0.08 + index / max(1, led_count)
            set_pixel(index, color_factory(*_rgb(_wheel(hue), brightness)))
        strip.show()

    def color_wipe(state, now, led_count):
        color = _selected_color(state)
        lit_count = min(led_count, int((now % 3.0) / 3.0 * (led_count + 1)))
        for index in range(led_count):
            pixel = color if index < lit_count else (0, 0, 0)
            set_pixel(index, color_factory(*pixel))
        strip.show()

    def theater_chase(state, now, led_count):
        color = _selected_color(state)
        step = int(now * 5.0) % 3
        for index in range(led_count):
            pixel = color if (index + step) % 3 == 0 else (0, 0, 0)
            set_pixel(index, color_factory(*pixel))
        strip.show()

    def candle(state, now, led_count):
        color = _candle_color(state, now)
        for index in range(led_count):
            set_pixel(index, color_factory(*color))
        strip.show()

    effects = {
        "solid": ("Solid", solid),
        "breathe": ("Breathe", breathe),
        "color_loop": ("Color Loop", color_loop),
        "night_pulse": ("Night Pulse", night_pulse),
        "rainbow": ("Rainbow", rainbow),
        "color_wipe": ("Color Wipe", color_wipe),
        "theater_chase": ("Theater Chase", theater_chase),
        "candle": ("Candle", candle),
    }
    if state is None:
        return _dispatch(effects, None, now, 0, "ws281x")
    strip.setBrightness(255)
    return _dispatch(effects, state, now, strip.numPixels(), "ws281x")


# ===========================================================================
# APA102, gồm cả ReSpeaker 2-Mics Pi HAT
# ===========================================================================

def apa102(device, state, now=None):
    """Hiệu ứng cho APA102. Hàm con ghi pixel bằng device.set_pixel()."""
    def solid(state, now, led_count):
        color = _selected_color(state)
        for index in range(led_count):
            device.set_pixel(index, *color)
        device.show()

    def breathe(state, now, led_count):
        pulse = 0.12 + 0.88 * (0.5 + 0.5 * math.sin(now * 2.0 * math.pi / 4.0))
        color = _selected_color(state, pulse)
        for index in range(led_count):
            device.set_pixel(index, *color)
        device.show()

    def color_loop(state, now, led_count):
        color = _rgb(_wheel(now / 10.0), _brightness(state))
        for index in range(led_count):
            device.set_pixel(index, *color)
        device.show()

    def night_pulse(state, now, led_count):
        pulse = 0.16 + 0.34 * (0.5 + 0.5 * math.sin(now * 2.0 * math.pi / 9.0))
        selected = state.get("rgb_color", (255, 180, 100))
        amber = tuple(round(a * 0.35 + b * 0.65) for a, b in zip(selected, (255, 115, 28)))
        color = _rgb(amber, _brightness(state) * pulse)
        for index in range(led_count):
            device.set_pixel(index, *color)
        device.show()

    def rainbow(state, now, led_count):
        brightness = _brightness(state)
        for index in range(led_count):
            hue = now * 0.08 + index / max(1, led_count)
            device.set_pixel(index, *_rgb(_wheel(hue), brightness))
        device.show()

    def theater_chase(state, now, led_count):
        color = _selected_color(state)
        step = int(now * 5.0) % 3
        for index in range(led_count):
            device.set_pixel(index, *(color if (index + step) % 3 == 0 else (0, 0, 0)))
        device.show()

    def candle(state, now, led_count):
        color = _candle_color(state, now)
        for index in range(led_count):
            device.set_pixel(index, *color)
        device.show()

    effects = {
        "solid": ("Solid", solid),
        "breathe": ("Breathe", breathe),
        "color_loop": ("Color Loop", color_loop),
        "night_pulse": ("Night Pulse", night_pulse),
        "rainbow": ("Rainbow", rainbow),
        "theater_chase": ("Theater Chase", theater_chase),
        "candle": ("Candle", candle),
    }
    if state is None:
        return _dispatch(effects, None, now, 0, "apa102")
    return _dispatch(effects, state, now, device.num_led, "apa102")


# ===========================================================================
# Vòng LED USB ReSpeaker Mic Array v2.0
# ===========================================================================

def respeaker_mic_array(write, led_count, state, now=None):
    """Hiệu ứng cho ReSpeaker USB; mỗi LED gửi 4 byte qua write(6, data)."""
    def send_uniform(color, count):
        write(6, [channel for _ in range(count) for channel in (*color, 0)])

    def solid(state, now, count):
        send_uniform(_selected_color(state), count)

    def breathe(state, now, count):
        pulse = 0.12 + 0.88 * (0.5 + 0.5 * math.sin(now * 2.0 * math.pi / 4.0))
        send_uniform(_selected_color(state, pulse), count)

    def color_loop(state, now, count):
        send_uniform(_rgb(_wheel(now / 10.0), _brightness(state)), count)

    def night_pulse(state, now, count):
        pulse = 0.16 + 0.34 * (0.5 + 0.5 * math.sin(now * 2.0 * math.pi / 9.0))
        selected = state.get("rgb_color", (255, 180, 100))
        amber = tuple(round(a * 0.35 + b * 0.65) for a, b in zip(selected, (255, 115, 28)))
        send_uniform(_rgb(amber, _brightness(state) * pulse), count)

    def rainbow(state, now, count):
        brightness = _brightness(state)
        data = []
        for index in range(count):
            color = _rgb(_wheel(now * 0.08 + index / max(1, count)), brightness)
            data.extend((*color, 0))
        write(6, data)

    def candle(state, now, count):
        send_uniform(_candle_color(state, now), count)

    effects = {
        "solid": ("Solid", solid),
        "breathe": ("Breathe", breathe),
        "color_loop": ("Color Loop", color_loop),
        "night_pulse": ("Night Pulse", night_pulse),
        "rainbow": ("Rainbow", rainbow),
        "candle": ("Candle", candle),
    }
    if state is None:
        return _dispatch(effects, None, now, 0, "respeaker_mic_array")
    return _dispatch(effects, state, now, led_count, "respeaker_mic_array")


# ===========================================================================
# LED tự viết trong Dev_Led.py; render là LED_MANUAL_RENDER(frames)
# ===========================================================================

def custom(render, led_count, state, now=None):
    """Hiệu ứng LED tùy chỉnh; các hàm con gửi danh sách RGB đến render()."""
    def solid(state, now, count):
        color = _selected_color(state)
        render([color] * count)

    def breathe(state, now, count):
        pulse = 0.12 + 0.88 * (0.5 + 0.5 * math.sin(now * 2.0 * math.pi / 4.0))
        color = _selected_color(state, pulse)
        render([color] * count)

    def color_loop(state, now, count):
        color = _rgb(_wheel(now / 10.0), _brightness(state))
        render([color] * count)

    def night_pulse(state, now, count):
        pulse = 0.16 + 0.34 * (0.5 + 0.5 * math.sin(now * 2.0 * math.pi / 9.0))
        selected = state.get("rgb_color", (255, 180, 100))
        amber = tuple(round(a * 0.35 + b * 0.65) for a, b in zip(selected, (255, 115, 28)))
        render([_rgb(amber, _brightness(state) * pulse)] * count)

    def rainbow(state, now, count):
        brightness = _brightness(state)
        render([
            _rgb(_wheel(now * 0.08 + index / max(1, count)), brightness)
            for index in range(count)
        ])

    effects = {
        "solid": ("Solid", solid),
        "breathe": ("Breathe", breathe),
        "color_loop": ("Color Loop", color_loop),
        "night_pulse": ("Night Pulse", night_pulse),
        "rainbow": ("Rainbow", rainbow),
    }
    if state is None:
        return _dispatch(effects, None, now, 0, "custom")
    return _dispatch(effects, state, now, led_count, "custom")


# Chạy sau khi đã định nghĩa tất cả adapter để effect_registry() tra cứu được.
_publish_effect_registry()
