"""WebRTC NS + digital AGC, after echo cancellation, at 16 kHz mono."""
import array
import ctypes as C
from collections import deque
import time

DEFAULTS = dict(active=True, noise_suppression='low', high_pass_filter=True,
                agc=dict(active=True, target_level_dbfs=6, compression_gain_db=9,
                         limiter=True))


def settings(config):
    value = dict(DEFAULTS, **config.get('smart_config', {}).get('audio_processing', {}))
    value['agc'] = dict(DEFAULTS['agc'], **value.get('agc', {}))
    for name in ('active', 'high_pass_filter'):
        if type(value[name]) is not bool:
            raise ValueError('WebRTC ' + name + ' must be boolean')
    if value['noise_suppression'] not in ('off', 'low', 'moderate', 'high', 'very_high'):
        raise ValueError('Invalid WebRTC noise_suppression')
    for name in ('active', 'limiter'):
        if type(value['agc'][name]) is not bool:
            raise ValueError('WebRTC AGC ' + name + ' must be boolean')
    for name, maximum in (('target_level_dbfs', 31), ('compression_gain_db', 90)):
        item = value['agc'][name]
        if type(item) is not int or not 0 <= item <= maximum:
            raise ValueError('Invalid WebRTC AGC ' + name)
    return value


class MicrophoneProcessor:
    """A bounded 10 ms FIFO preserves every input sample and caller frame size."""
    def __init__(self, value, library, log=print):
        self.value, self.log = value, log
        self.input = bytearray()
        self.output = bytearray(320)  # constant 10 ms adaptation latency
        self.handle = None
        self.lib = C.CDLL(str(library))
        pcm = C.POINTER(C.c_int16)
        create = self.lib.vbot_mic_create  # old libraries must fail explicitly
        create.argtypes, create.restype = [C.c_int] * 6, C.c_void_p
        self.lib.vbot_aec_capture.argtypes = [C.c_void_p, pcm, pcm, C.c_int]
        self.lib.vbot_aec_capture.restype = C.c_int
        self.lib.vbot_aec_destroy.argtypes, self.lib.vbot_aec_destroy.restype = [C.c_void_p], None
        levels = dict(off=0, low=1, moderate=2, high=3, very_high=4)
        agc = value['agc']
        self.handle = create(levels[value['noise_suppression']], agc['active'],
                             agc['target_level_dbfs'], agc['compression_gain_db'],
                             agc['limiter'], value['high_pass_filter'])
        if not self.handle:
            raise RuntimeError('WebRTC microphone processor initialization failed')
        self.blocks = self.clipped = 0
        self.max_ms = 0.0
        self.over_10ms = 0
        self.times = deque(maxlen=512)
        self.cpu_times = deque(maxlen=512)
        self.cpu_max_ms = self.off_cpu_max_ms = 0.0
        self.off_cpu_over_10ms = 0
        self.last_log = 0
        self.source = None
        self.last_frame = 0
        log('[WebRTC MIC] NS=' + value['noise_suppression'] + ', digital AGC=' + str(agc['active']))

    def process_frame(self, frame, source=None):
        now = time.monotonic()
        # Do not leak a previous wakeword/command tail into another capture owner.
        if source != self.source or now - self.last_frame > .3:
            self.input.clear()
            self.output = bytearray(320)
        self.source, self.last_frame = source, now
        size = len(frame) * 2
        self.input.extend(array.array('h', frame).tobytes())
        while len(self.input) >= 320:
            block = (C.c_int16 * 160).from_buffer_copy(self.input[:320])
            # APM 1.3 leaves dest untouched when every processing module is off.
            # Seed it with input so disabling NS/AGC/HPF never produces silence.
            result = (C.c_int16 * 160).from_buffer_copy(self.input[:320])
            start = time.perf_counter()
            cpu_start = time.thread_time()
            rc = self.lib.vbot_aec_capture(self.handle, block, result, 0)
            cpu_ms = (time.thread_time() - cpu_start) * 1000
            elapsed = (time.perf_counter() - start) * 1000
            if rc:
                raise RuntimeError('WebRTC MIC ProcessStream error ' + str(rc))
            del self.input[:320]
            self.output.extend(bytes(result))
            self.blocks += 1
            self.max_ms = max(self.max_ms, elapsed)
            self.times.append(elapsed)
            self.cpu_times.append(cpu_ms)
            self.cpu_max_ms = max(self.cpu_max_ms, cpu_ms)
            off_cpu = max(0.0, elapsed - cpu_ms)
            self.off_cpu_max_ms = max(self.off_cpu_max_ms, off_cpu)
            self.off_cpu_over_10ms += int(off_cpu > 10)
            self.over_10ms += int(elapsed > 10)
            self.clipped += sum(abs(x) >= 32767 for x in result)
        out = array.array('h')
        out.frombytes(bytes(self.output[:size]))
        del self.output[:size]
        if len(out) != len(frame):
            raise RuntimeError('WebRTC MIC frame size mismatch')
        if now - self.last_log >= 30:
            self.log('[WebRTC MIC] ' + str(self.snapshot()))
            self.last_log = now
        return out

    def snapshot(self):
        recent = sorted(self.times)
        cpu = sorted(self.cpu_times)
        return dict(blocks=self.blocks, max_ms=round(self.max_ms, 3), over_10ms=self.over_10ms,
                    cpu_max_ms=round(self.cpu_max_ms, 3),
                    cpu_recent_p95_ms=round(cpu[int(.95 * (len(cpu)-1))], 3) if cpu else 0,
                    off_cpu_max_ms=round(self.off_cpu_max_ms, 3), off_cpu_over_10ms=self.off_cpu_over_10ms,
                    recent_p95_ms=round(recent[int(.95 * (len(recent)-1))], 3) if recent else 0,
                    clipped_samples=self.clipped, noise_suppression=self.value['noise_suppression'],
                    agc=self.value['agc'], latency_ms=10)

    def close(self):
        if self.handle:
            self.lib.vbot_aec_destroy(self.handle)
            self.handle = None
