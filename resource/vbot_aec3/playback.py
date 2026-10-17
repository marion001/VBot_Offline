"""Direct ALSA playback gives AEC the real queued playback delay."""
import ctypes as C
import ctypes.util
import threading


class ALSAPlayback:
    def __init__(self, device, latency_ms, cancelled, rate=44100, channels=2):
        self.handle = C.c_void_p()
        self.cancelled = cancelled
        self.lock = threading.Lock()
        self.delay_ms = 0
        self.rate, self.frame_bytes = rate, channels * 2
        self.recoveries = self.underruns = 0
        self.lib = C.CDLL(C.util.find_library('asound') or 'libasound.so.2')
        definitions = {
            'snd_pcm_open': ([C.POINTER(C.c_void_p), C.c_char_p, C.c_int, C.c_int], C.c_int),
            'snd_pcm_set_params': ([C.c_void_p, C.c_int, C.c_int, C.c_uint, C.c_uint, C.c_int, C.c_uint], C.c_int),
            'snd_pcm_writei': ([C.c_void_p, C.c_void_p, C.c_ulong], C.c_long),
            'snd_pcm_delay': ([C.c_void_p, C.POINTER(C.c_long)], C.c_int),
            'snd_pcm_recover': ([C.c_void_p, C.c_int, C.c_int], C.c_int),
            'snd_pcm_drop': ([C.c_void_p], C.c_int),
            'snd_pcm_close': ([C.c_void_p], C.c_int),
            'snd_pcm_prepare': ([C.c_void_p], C.c_int),
            'snd_pcm_drain': ([C.c_void_p], C.c_int),
            'snd_strerror': ([C.c_int], C.c_char_p),
        }
        for name, (args, result) in definitions.items():
            function = getattr(self.lib, name)
            function.argtypes, function.restype = args, result
        try:
            self.check(self.lib.snd_pcm_open(C.byref(self.handle), device.encode(), 0, 0))
            self.check(self.lib.snd_pcm_set_params(self.handle, 2, 3, channels, rate, 1, latency_ms * 1000))
        except Exception:
            self.close()
            raise

    def check(self, result):
        if result < 0:
            raise RuntimeError('ALSA: ' + self.lib.snd_strerror(result).decode(errors='replace'))

    def write(self, pcm, reference):
        with self.lock:
            if self.cancelled.is_set() or not self.handle:
                return
            buffer = C.create_string_buffer(pcm)
            offset = 0
            retries = 0
            while offset < len(pcm) and not self.cancelled.is_set():
                count = self.lib.snd_pcm_writei(self.handle, C.byref(buffer, offset), (len(pcm) - offset) // self.frame_bytes)
                if count < 0:
                    self.recoveries += 1
                    if count == -32:
                        self.underruns += 1
                    retries += 1
                    if retries > 3 or self.cancelled.is_set():
                        self.check(count)
                    self.check(self.lib.snd_pcm_recover(self.handle, count, 1))
                    continue
                if not count:
                    raise RuntimeError('ALSA không nhận dữ liệu âm thanh')
                accepted = count * self.frame_bytes
                delay = C.c_long()
                if self.lib.snd_pcm_delay(self.handle, C.byref(delay)) >= 0:
                    self.delay_ms = max(0, round(delay.value * 1000 / self.rate))
                reference(pcm[offset:offset + accepted])
                offset += accepted

    def interrupt(self):
        # A blocking ALSA write is released by drop; close waits for its lock.
        if self.handle:
            self.lib.snd_pcm_drop(self.handle)

    def reset(self):
        with self.lock:
            if self.handle and not self.cancelled.is_set():
                self.check(self.lib.snd_pcm_drop(self.handle))
                self.check(self.lib.snd_pcm_prepare(self.handle))
                self.delay_ms = 0

    def drain(self):
        with self.lock:
            if self.handle and not self.cancelled.is_set():
                self.check(self.lib.snd_pcm_drain(self.handle))
                self.check(self.lib.snd_pcm_prepare(self.handle))
                self.delay_ms = 0

    def close(self):
        self.interrupt()
        with self.lock:
            if self.handle:
                self.lib.snd_pcm_close(self.handle)
                self.handle = C.c_void_p()
