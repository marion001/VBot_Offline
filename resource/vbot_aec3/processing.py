"""Native AEC3, exact 10 ms reframing, no silent unprocessed fallback."""
import ctypes as C
import array
from collections import deque
import math
from pathlib import Path
import random
import statistics
import threading
import time

ROOT = Path(__file__).resolve().parent
FRAME_BYTES = 320


def validate_aec(value):
    if not isinstance(value, dict) or type(value.get('active', False)) is not bool:
        raise ValueError('test_audio.aec.active phải là true hoặc false')
    result = dict(value)
    result.setdefault('active', False)
    level = result.setdefault('noise_suppression', 'off')
    if not isinstance(level, str) or level not in ('off', 'low', 'moderate', 'high', 'very_high'):
        raise ValueError('test_audio.aec.noise_suppression phải là off, low, moderate, high hoặc very_high')
    for name, default, low, high in (('capture_delay_ms', 32, 0, 500),
                                   ('playback_latency_ms', 100, 20, 200),
                                   ('render_clock_ppm', 0, -3000, 3000)):
        item = result.setdefault(name, default)
        if type(item) is not int or not low <= item <= high:
            unit = 'ppm' if name == 'render_clock_ppm' else 'ms'
            raise ValueError('test_audio.aec.' + name + f' phải từ {low} đến {high} {unit}')
    return result


class ReferenceClock:
    """Match render reference to capture clock without changing the speaker PCM."""
    def __init__(self, ppm=0):
        if type(ppm) is not int or not -3000 <= ppm <= 3000:
            raise ValueError('render_clock_ppm phải từ -3000 đến 3000')
        self.ppm = ppm
        self.step = 1 / (1 + ppm / 1000000)
        self.pending = array.array('h')
        self.position = 0.0

    def feed(self, pcm):
        if len(pcm) % 2:
            raise ValueError('PCM phải là S16_LE')
        if not self.ppm:
            return pcm
        self.pending.extend(array.array('h', pcm))
        output = array.array('h')
        while self.position + 1 < len(self.pending):
            index = int(self.position)
            fraction = self.position - index
            value = self.pending[index] + fraction * (self.pending[index+1] - self.pending[index])
            output.append(round(value))
            self.position += self.step
        consumed = min(int(self.position), max(0, len(self.pending) - 1))
        if consumed:
            del self.pending[:consumed]
            self.position -= consumed
        return output.tobytes()


class AEC3:
    def __init__(self, noise_suppression='off', library='/home/pi/VBot_AEC3/libvbot_aec3.so', output='normal'):
        levels = {'off': 0, 'low': 1, 'moderate': 2, 'high': 3, 'very_high': 4}
        if not isinstance(noise_suppression, str) or noise_suppression not in levels:
            raise ValueError('Mức giảm nhiễu không hợp lệ')
        self.noise_suppression = noise_suppression
        self.lock = threading.Lock()
        self.handle = None
        self.render_pending = bytearray()
        self.capture_pending = bytearray()
        self.normal_output = b''
        self.capture_blocks = self.render_blocks = 0
        self.capture_max_ms = 0.0
        self.capture_times_ms = deque(maxlen=512)
        self.capture_over_budget = 0
        if output not in ('normal', 'linear'):
            raise ValueError('AEC output must be normal or linear')
        self.output = output
        try:
            self.lib = C.CDLL(str(library))
        except OSError as error:
            raise RuntimeError('Không nạp được AEC3 tại ' + str(library) + '. Kiểm tra thư viện và phụ thuộc: ' + str(error)) from error
        self.backend = 'WebRTC Audio Processing 1.3 (AEC3)'
        version = getattr(self.lib, 'vbot_apm_version', None)
        if version is not None:
            version.argtypes, version.restype = [], C.c_char_p
            value = version()
            if value:
                self.backend = 'WebRTC Audio Processing ' + value.decode('ascii') + ' (AEC3)'
        pcm = C.POINTER(C.c_int16)
        for name, args, result in (
            ('vbot_aec_abi', [], C.c_int),
            ('vbot_aec_create', [], C.c_void_p),
            ('vbot_aec_render', [C.c_void_p, pcm], C.c_int),
            ('vbot_aec_capture', [C.c_void_p, pcm, pcm, C.c_int], C.c_int),
            ('vbot_aec_destroy', [C.c_void_p], None)):
            function = getattr(self.lib, name)
            function.argtypes, function.restype = args, result
        if self.lib.vbot_aec_abi() != 1:
            raise RuntimeError('ABI AEC3 không đúng; build lại /home/pi/VBot_AEC3/libvbot_aec3.so từ vbot_aec3.cpp mới')
        self.handle = self.lib.vbot_aec_create()
        if not self.handle:
            raise RuntimeError('WebRTC không khởi tạo được AEC3')
        if output == 'linear':
            try:
                self.linear = self.lib.vbot_aec_get_linear
                self.linear.argtypes = [C.c_void_p, pcm]
                self.linear.restype = C.c_int
            except AttributeError:
                self.close()
                raise RuntimeError('Linear output requires the separate music diagnostic library')
        if noise_suppression != 'off':
            try:
                setter = self.lib.vbot_aec_set_noise_suppression
                setter.argtypes = [C.c_void_p, C.c_int]
                setter.restype = C.c_int
                if setter(self.handle, levels[noise_suppression]):
                    raise RuntimeError('WebRTC không bật được giảm nhiễu')
            except AttributeError as error:
                self.close()
                raise RuntimeError('Thư viện AEC3 cũ chưa hỗ trợ giảm nhiễu; build lại libvbot_aec3.so từ vbot_aec3.cpp mới') from error
            except Exception:
                self.close()
                raise

    def feed_render(self, pcm):
        if len(pcm) % 2:
            raise ValueError('PCM phải là S16_LE')
        with self.lock:
            if not self.handle:
                raise RuntimeError('AEC3 đã đóng')
            self.render_pending.extend(pcm)
            while len(self.render_pending) >= FRAME_BYTES:
                frame = (C.c_int16 * 160).from_buffer_copy(self.render_pending[:FRAME_BYTES])
                del self.render_pending[:FRAME_BYTES]
                if self.lib.vbot_aec_render(self.handle, frame):
                    raise RuntimeError('AEC3 lỗi xử lý tín hiệu phát')
                self.render_blocks += 1

    def process_capture(self, pcm, delay_ms):
        if len(pcm) % 2:
            raise ValueError('PCM phải là S16_LE')
        output = bytearray()
        normal_output = bytearray()
        with self.lock:
            if not self.handle:
                raise RuntimeError('AEC3 đã đóng')
            self.capture_pending.extend(pcm)
            while len(self.capture_pending) >= FRAME_BYTES:
                frame = (C.c_int16 * 160).from_buffer_copy(self.capture_pending[:FRAME_BYTES])
                del self.capture_pending[:FRAME_BYTES]
                cleaned = (C.c_int16 * 160)()
                start = time.perf_counter()
                rc = self.lib.vbot_aec_capture(self.handle, frame, cleaned, max(0, min(500, int(delay_ms))))
                if rc:
                    raise RuntimeError(f'AEC3 lỗi xử lý microphone: {rc}')
                normal_output.extend(bytes(cleaned))
                if self.output == 'linear':
                    rc = self.linear(self.handle, cleaned)
                    if rc:
                        raise RuntimeError(f'AEC3 linear output failed: {rc}')
                elapsed_ms = (time.perf_counter() - start) * 1000
                self.capture_max_ms = max(self.capture_max_ms, elapsed_ms)
                self.capture_times_ms.append(elapsed_ms)
                if elapsed_ms > 10:
                    self.capture_over_budget += 1
                output.extend(bytes(cleaned))
                self.capture_blocks += 1
        self.normal_output = bytes(normal_output)
        return bytes(output)

    def snapshot(self):
        with self.lock:
            times = sorted(self.capture_times_ms)
            return dict(backend=self.backend, capture_blocks=self.capture_blocks,
                        render_blocks=self.render_blocks, capture_max_ms=round(self.capture_max_ms, 3),
                        capture_recent_mean_ms=round(statistics.mean(times), 3) if times else 0,
                        capture_recent_p95_ms=round(times[math.ceil(len(times)*.95)-1], 3) if times else 0,
                        capture_over_10ms=self.capture_over_budget,
                        noise_suppression=self.noise_suppression, output=self.output)

    def close(self):
        with self.lock:
            if self.handle:
                self.lib.vbot_aec_destroy(self.handle)
                self.handle = None


def benchmark():
    """Real native synthetic echo test, not a hardware/acoustic guarantee."""
    rng = random.Random(1234)
    processor = AEC3()
    echo_history = [0] * 800  # 50 ms echo delay
    raw_energy = clean_energy = 0
    elapsed = []
    try:
        for block in range(1500):
            far = [rng.randint(-8000, 8000) for _ in range(160)]
            echo_history.extend(far)
            near = [round(.5 * echo_history.pop(0)) for _ in range(160)]
            source = bytes((C.c_int16 * 160)(*far))
            mic = bytes((C.c_int16 * 160)(*near))
            start = time.perf_counter()
            processor.feed_render(source)
            cleaned = processor.process_capture(mic, 50)
            elapsed.append((time.perf_counter() - start) * 1000)
            if block >= 1000:
                output = (C.c_int16 * 160).from_buffer_copy(cleaned)
                raw_energy += sum(x*x for x in near)
                clean_energy += sum(x*x for x in output)
        reduction = 10 * math.log10((raw_energy + 1) / (clean_energy + 1))
        ordered = sorted(elapsed)
        report = dict(backend=processor.backend, synthetic_echo_reduction_db=round(reduction, 2),
                      frame_budget_ms=10, mean_ms=round(statistics.mean(elapsed), 3),
                      p95_ms=round(ordered[int(len(ordered)*.95)], 3), max_ms=round(max(elapsed), 3))
        if reduction < 10 or report['p95_ms'] >= 10:
            raise RuntimeError('AEC3 chưa đạt kiểm tra khử vọng/tốc độ: ' + str(report))
        # A muted output could "pass" an echo-only test. Check local speech alone too.
        processor.close()
        processor = AEC3()
        local_energy = preserved_energy = 0
        for block in range(300):
            local = [round(3000 * math.sin(2*math.pi*350*(block*160+i)/16000)) for i in range(160)]
            processor.feed_render(b'\0' * FRAME_BYTES)
            output = (C.c_int16 * 160).from_buffer_copy(
                processor.process_capture(bytes((C.c_int16 * 160)(*local)), 0))
            if block >= 100:
                local_energy += sum(x*x for x in local)
                preserved_energy += sum(x*x for x in output)
        preserved_db = 10 * math.log10((preserved_energy+1)/(local_energy+1))
        report['local_voice_gain_db'] = round(preserved_db, 2)
        if preserved_db < -6:
            raise RuntimeError('AEC3 làm mất tín hiệu nói gần: ' + str(report))
        return report
    finally:
        processor.close()
