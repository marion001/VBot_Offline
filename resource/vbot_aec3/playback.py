"""Direct ALSA playback gives AEC the real queued playback delay."""
import ctypes as C
import ctypes.util
import threading
import json
import re
import time
from pathlib import Path


class ALSAPlayback:
    def __init__(self, device, latency_ms, cancelled, rate=44100, channels=2, equalizer=False):
        self.handle = C.c_void_p()
        self.cancelled = cancelled
        self.lock = threading.Lock()
        self.delay_ms = 0
        self.rate, self.frame_bytes = rate, channels * 2
        self.recoveries = self.underruns = 0
        self.route_fallbacks = 0
        self.equalizer = None
        self.local_device = self.device = device
        self.latency_ms, self.channels = latency_ms, channels
        self.route_enabled = bool(equalizer)
        self.route_check_at = self.route_failed_until = 0
        if equalizer and channels == 2:
            from .equalizer import LiveEqualizer
            self.equalizer = LiveEqualizer(rate)
        self.lib = C.CDLL(C.util.find_library('asound') or 'libasound.so.2')
        definitions = {
            'snd_pcm_hw_params_malloc': ([C.POINTER(C.c_void_p)], C.c_int),
            'snd_pcm_hw_params_free': ([C.c_void_p], None),
            'snd_pcm_hw_params_any': ([C.c_void_p, C.c_void_p], C.c_int),
            'snd_pcm_hw_params_set_access': ([C.c_void_p, C.c_void_p, C.c_int], C.c_int),
            'snd_pcm_hw_params_set_format': ([C.c_void_p, C.c_void_p, C.c_int], C.c_int),
            'snd_pcm_hw_params_set_channels': ([C.c_void_p, C.c_void_p, C.c_uint], C.c_int),
            'snd_pcm_hw_params_set_rate': ([C.c_void_p, C.c_void_p, C.c_uint, C.c_int], C.c_int),
            'snd_pcm_hw_params_set_period_size_near': ([C.c_void_p, C.c_void_p, C.POINTER(C.c_ulong), C.POINTER(C.c_int)], C.c_int),
            'snd_pcm_hw_params_set_buffer_size_near': ([C.c_void_p, C.c_void_p, C.POINTER(C.c_ulong)], C.c_int),
            'snd_pcm_hw_params': ([C.c_void_p, C.c_void_p], C.c_int),
            'snd_pcm_sw_params_malloc': ([C.POINTER(C.c_void_p)], C.c_int),
            'snd_pcm_sw_params_free': ([C.c_void_p], None),
            'snd_pcm_sw_params_current': ([C.c_void_p, C.c_void_p], C.c_int),
            'snd_pcm_sw_params_set_start_threshold': ([C.c_void_p, C.c_void_p, C.c_ulong], C.c_int),
            'snd_pcm_sw_params_set_avail_min': ([C.c_void_p, C.c_void_p, C.c_ulong], C.c_int),
            'snd_pcm_sw_params': ([C.c_void_p, C.c_void_p], C.c_int),
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
            self._open_device(device)
            self._refresh_route()
        except Exception:
            self.close()
            raise

    def check(self, result):
        if result < 0:
            raise RuntimeError('ALSA: ' + self.lib.snd_strerror(result).decode(errors='replace'))

    def _open_device(self, device):
        if self.handle:
            self.lib.snd_pcm_drop(self.handle)
            self.lib.snd_pcm_close(self.handle)
            self.handle = C.c_void_p()
        try:
            self.check(self.lib.snd_pcm_open(C.byref(self.handle), device.encode(), 0, 0))
            # Wireless writes have independent scheduling/radio jitter. Do not
            # force the short local-speaker buffer onto the Bluetooth PCM.
            if device.startswith('bluealsa:'):
                self._configure_bluetooth_pcm()
            else:
                self.check(self.lib.snd_pcm_set_params(self.handle, 2, 3, self.channels, self.rate, 1, self.latency_ms * 1000))
        except Exception:
            if self.handle:
                self.lib.snd_pcm_close(self.handle)
                self.handle = C.c_void_p()
            raise
        self.device = device
        self.delay_ms = 0

    def _configure_bluetooth_pcm(self):
        # Keep capacity independent of wake-up cadence: large wireless buffer,
        # short 10 ms periods. set_params() couples periods to buffer / 4.
        hardware, software = C.c_void_p(), C.c_void_p()
        period = C.c_ulong(max(1, self.rate // 100))
        capacity = C.c_ulong(max(period.value * 4, self.rate // 2))
        direction = C.c_int(0)
        try:
            self.check(self.lib.snd_pcm_hw_params_malloc(C.byref(hardware)))
            self.check(self.lib.snd_pcm_hw_params_any(self.handle, hardware))
            self.check(self.lib.snd_pcm_hw_params_set_access(self.handle, hardware, 3))
            self.check(self.lib.snd_pcm_hw_params_set_format(self.handle, hardware, 2))
            self.check(self.lib.snd_pcm_hw_params_set_channels(self.handle, hardware, self.channels))
            self.check(self.lib.snd_pcm_hw_params_set_rate(self.handle, hardware, self.rate, 0))
            self.check(self.lib.snd_pcm_hw_params_set_period_size_near(self.handle, hardware, C.byref(period), C.byref(direction)))
            self.check(self.lib.snd_pcm_hw_params_set_buffer_size_near(self.handle, hardware, C.byref(capacity)))
            self.check(self.lib.snd_pcm_hw_params(self.handle, hardware))
            self.check(self.lib.snd_pcm_sw_params_malloc(C.byref(software)))
            self.check(self.lib.snd_pcm_sw_params_current(self.handle, software))
            self.check(self.lib.snd_pcm_sw_params_set_start_threshold(self.handle, software, max(period.value, capacity.value - period.value)))
            self.check(self.lib.snd_pcm_sw_params_set_avail_min(self.handle, software, period.value))
            self.check(self.lib.snd_pcm_sw_params(self.handle, software))
            self.check(self.lib.snd_pcm_prepare(self.handle))
        finally:
            if software:
                self.lib.snd_pcm_sw_params_free(software)
            if hardware:
                self.lib.snd_pcm_hw_params_free(hardware)

    def _refresh_route(self, force_local=False):
        if not self.route_enabled:
            return
        now = time.monotonic()
        if not force_local and now < self.route_check_at:
            return
        self.route_check_at = now + 1
        target = self.local_device
        if not force_local and now >= self.route_failed_until:
            try:
                route = json.loads(Path('/run/vbot-bt-source.json').read_text())
                mac = route.get('device', '')
                if 0 <= now - route.get('at', 0) < 12 and re.fullmatch(r'(?:[0-9A-F]{2}:){5}[0-9A-F]{2}', mac):
                    target = 'bluealsa:DEV=' + mac + ',PROFILE=a2dp'
            except (OSError, ValueError, TypeError):
                pass
        if target != self.device:
            try:
                self._open_device(target)
                print('[Bluetooth Source] Đầu ra: ' + target, flush=True)
            except Exception as error:
                self.route_failed_until = now + 15
                print('[Bluetooth Source] Trở về loa VBot: ' + str(error), flush=True)
                self._open_device(self.local_device)

    def write(self, pcm, reference):
        with self.lock:
            if self.cancelled.is_set() or not self.handle:
                return
            self._refresh_route()
            if self.equalizer is not None:
                pcm = self.equalizer.process(pcm)
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
                    if self.device != self.local_device:
                        # EPIPE/ESTRPIPE are recoverable XRUN/suspend states,
                        # not proof that the Bluetooth device disconnected.
                        if count in (-32, -86) and retries <= 3 and not self.cancelled.is_set():
                            if self.lib.snd_pcm_recover(self.handle, count, 1) >= 0:
                                continue
                        self.route_fallbacks += 1
                        self.route_failed_until = time.monotonic() + 15
                        self._refresh_route(force_local=True)
                        continue
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
            if getattr(self, 'equalizer', None) is not None:
                self.equalizer.close()
                self.equalizer = None
