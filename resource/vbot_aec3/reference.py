"""Bounded local speaker-reference IPC; stale/lost PCM resets the AEC history."""
from collections import deque
import os
from pathlib import Path
import socket
import struct
import threading
import time

HEADER = struct.Struct('!dIIIII')  # monotonic time, rate, delay, XRUNs, PID, sequence


def socket_path():
    directory = Path('/tmp') / ('vbot-aec3-' + str(os.getuid()))
    directory.mkdir(mode=0o700, exist_ok=True)
    info = directory.lstat()
    if directory.is_symlink() or info.st_uid != os.getuid() or info.st_mode & 0o077:
        raise RuntimeError('Unsafe AEC reference directory')
    return str(directory / 'render.sock')


class ReferenceSender:
    def __init__(self, rate, playback):
        self.rate, self.playback = rate, playback
        self.sock = socket.socket(socket.AF_UNIX, socket.SOCK_DGRAM)
        self.sock.setblocking(False)
        self.path = socket_path()
        self.sequence = 0

    def send(self, pcm):
        # Split oversized ALSA writes into <=10 ms datagrams.
        size = self.rate // 100 * 4
        for offset in range(0, len(pcm), size):
            header = HEADER.pack(time.monotonic(), self.rate, self.playback.delay_ms,
                                 self.playback.underruns, os.getpid(), self.sequence)
            self.sequence = (self.sequence + 1) & 0xffffffff
            try:
                self.sock.sendto(header + pcm[offset:offset + size], self.path)
            except OSError:
                pass  # Wake consumer absent/full never stalls speaker playback.

    def close(self):
        self.sock.close()


class ReferenceListener:
    def __init__(self, callback, error, manual=False, max_batch_ms=100):
        self.callback, self.error = callback, error
        self.stopped = threading.Event()
        self.sock = socket.socket(socket.AF_UNIX, socket.SOCK_DGRAM)
        self.path = socket_path()
        # Refuse a live owner; only remove a stale socket from a previous crash.
        probe = socket.socket(socket.AF_UNIX, socket.SOCK_DGRAM)
        try:
            probe.connect(self.path)
        except (FileNotFoundError, ConnectionRefusedError):
            Path(self.path).unlink(missing_ok=True)
        else:
            probe.close()
            self.sock.close()
            raise RuntimeError('AEC reference listener already running')
        finally:
            probe.close()
        self.condition = threading.Condition()
        self.pending = deque()
        self.serial = 0
        self.previous = None
        self.capture_active = True
        self.max_batch_ms = max(10, min(100, int(max_batch_ms)))
        self.receive_timing = deque(maxlen=512)
        self.pump_timing = deque(maxlen=256)
        self.diagnostics = deque(maxlen=16)
        self.last_drop = None
        self._last_diagnostic = 0
        self.stats = dict(received=0, stale=0, overflow=0, callback_busy=0, foreign_silence=0)
        self.sock.bind(self.path)
        self.sock.settimeout(.1)
        self.thread = threading.Thread(target=self._run, name='aec-render-reference', daemon=True)
        self.worker = None if manual else threading.Thread(target=self._deliver, name='aec-reference-dsp', daemon=True)
        if self.worker is not None:
            self.worker.start()
        self.thread.start()

    def _run(self):
        # Never wait for the DSP/microphone lock on the socket reader.
        selected_pid = None
        last_selected = 0
        while not self.stopped.is_set():
            try:
                packet = self.sock.recv(8192)
                if len(packet) <= HEADER.size:
                    continue
                timestamp, rate, delay, xruns, pid, seq = HEADER.unpack_from(packet)
                age = time.monotonic() - timestamp
                pcm = packet[HEADER.size:]
                if age < 0 or age > .15 or rate not in (44100, 48000) or len(pcm) % 4:
                    self.stats['stale'] += 1
                    self.last_drop = dict(reason='socket_stale_or_invalid', age_ms=round(age*1000, 3), pid=pid, seq=seq, rate=rate)
                    continue
                # A client can have both a silent local bridge and a playing
                # Multiroom receiver. Foreign silence is not a source takeover.
                now = time.monotonic()
                if selected_pid is not None and pid != selected_pid and now - last_selected < .3 and not any(pcm):
                    self.stats['foreign_silence'] = self.stats.get('foreign_silence', 0) + 1
                    continue
                selected_pid, last_selected = pid, now
                with self.condition:
                    self.stats['received'] += 1
                    if not getattr(self, 'capture_active', True):
                        self.stats['idle_discarded'] = self.stats.get('idle_discarded', 0) + 1
                        continue
                    if hasattr(self, 'receive_timing'):
                        self.receive_timing.append((now, timestamp, len(pcm)/(rate*4)*1000, pid))
                    self.serial += 1
                    self.pending.append((self.serial, timestamp, rate, delay, xruns, pid, seq, pcm))
                    self._trim()
                    self.condition.notify()
            except socket.timeout:
                continue
            except Exception as exc:
                self._error(exc)
                break

    def _trim(self):
        # Bound by PCM duration, including partial packets, rather than RAM alone.
        duration = sum(len(p[7]) / (p[2] * 4) for p in self.pending)
        self.queue_peak_ms = max(getattr(self,'queue_peak_ms',0),duration*1000)
        while self.pending and duration > .100001:
            p = self.pending.popleft()
            duration -= len(p[7]) / (p[2] * 4)
            self.stats['overflow'] += 1
            self.last_drop = dict(reason='queue_overflow', pid=p[5], seq=p[6])

    def _deliver(self):
        while not self.stopped.is_set():
            with self.condition:
                self.condition.wait_for(lambda: self.pending or self.stopped.is_set(), timeout=.1)
            self.pump()

    def set_capture_active(self, active):
        """Drain the socket while idle without retaining obsolete render PCM."""
        active = bool(active)
        with self.condition:
            if active == getattr(self, 'capture_active', True):
                return False
            self.capture_active = active
            self.generation = getattr(self, 'generation', 0) + 1
            self.stats['idle_discarded'] = self.stats.get('idle_discarded', 0) + len(self.pending)
            self.pending.clear()
            self.previous = None
            self._last_delivered_timestamp = time.monotonic()
            self.condition.notify_all()
            return True

    def timing_snapshot(self):
        with self.condition:
            received=list(self.receive_timing)
            pumped=list(self.pump_timing)
            queued=sum(len(p[7])/(p[2]*4)*1000 for p in self.pending)
        def summary(values):
            values=sorted(values)
            return dict(max=round(values[-1],3),p95=round(values[min(len(values)-1,int((len(values)-1)*.95))],3)) if values else None
        gaps=[(b[0]-a[0])*1000 for a,b in zip(received,received[1:])]
        send_gaps=[(b[1]-a[1])*1000 for a,b in zip(received,received[1:]) if a[3]==b[3]]
        burst=peak_burst=0
        for index,row in enumerate(received):
            burst=burst+row[2] if index and (row[0]-received[index-1][0])*1000<=2 and row[3]==received[index-1][3] else row[2]
            peak_burst=max(peak_burst,burst)
        return dict(window_packets=len(received),receive_gap_ms=summary(gaps),send_gap_ms=summary(send_gaps),
                    receive_burst_pcm_ms=round(peak_burst,3),queue_pcm_ms=round(queued,3),
                    queue_peak_pcm_ms=round(getattr(self,'queue_peak_ms',0),3),
                    pump_gap_ms=summary([p[0] for p in pumped]),batch_pcm_ms=summary([p[1] for p in pumped]),
                    callback_wall_ms=summary([p[2] for p in pumped]),callback_cpu_ms=summary([p[3] for p in pumped]),
                    max_batch_ms=getattr(self,'max_batch_ms',100))

    def pump(self):
        # Manual mode is drained by the mic owner immediately before capture.
        if self.stopped.is_set():
            return
        with self.condition:
            now = time.monotonic()
            while self.pending and now - self.pending[0][1] > .15:
                dropped = self.pending.popleft()
                self.stats['stale'] += 1
                self.last_drop = dict(reason='queue_stale', age_ms=round((now-dropped[1])*1000, 3), pid=dropped[5], seq=dropped[6])
                self.previous = None
            if not self.pending:
                return
            pump_started=time.monotonic()
            pump_gap_ms=(pump_started-getattr(self,'_last_pump_started',pump_started))*1000
            self._last_pump_started=pump_started
            batch = [self.pending[0]]
            batch_ms=len(batch[0][7])/(batch[0][2]*4)*1000
            for p in list(self.pending)[1:]:
                last = batch[-1]
                if p[2] != last[2] or p[5] != last[5] or p[6] != ((last[6] + 1) & 0xffffffff):
                    break
                duration_ms=len(p[7])/(p[2]*4)*1000
                if batch_ms+duration_ms>getattr(self,'max_batch_ms',100)+.001:
                    break
                batch.append(p)
                batch_ms+=duration_ms
            queued_packets = len(self.pending)
            generation = getattr(self, 'generation', 0)
            # Transfer ownership BEFORE DSP work. Counting an in-flight batch
            # against the pending limit makes the socket reader discard fresh
            # packets while that batch is already being consumed.
            for _ in batch:
                self.pending.popleft()
        first, last = batch[0], batch[-1]
        age = max(0, time.monotonic() - last[1])
        discontinuity = self.previous != (first[5], (first[6] - 1) & 0xffffffff, first[2])
        if discontinuity and hasattr(self, 'diagnostics'):
            previous = self.previous
            reason = ('initial_or_stale_history' if previous is None else
                      'sender_changed' if previous[0] != first[5] else
                      'rate_changed' if previous[2] != first[2] else 'sequence_gap')
            detail = dict(event='reference_gap', reason=reason, pid=first[5], seq=first[6], rate=first[2],
                          previous=previous, packet_age_ms=round(age*1000,3),
                          oldest_age_ms=round(max(0,time.monotonic()-first[1])*1000,3),
                          delivery_gap_ms=round((first[1]-getattr(self,'_last_delivered_timestamp',first[1]))*1000,3),
                          queue_packets=queued_packets, batch_packets=len(batch),
                          batch_pcm_ms=round(batch_ms,3),pump_gap_ms=round(pump_gap_ms,3),
                          last_drop=getattr(self,'last_drop',None), counters=dict(self.stats), at_mono=round(time.monotonic(),3))
            self.last_discontinuity = detail
            if time.monotonic()-self._last_diagnostic >= 1:
                self.diagnostics.append(detail)
                self._last_diagnostic = time.monotonic()
        try:
            callback_started = time.monotonic()
            callback_cpu_started=time.thread_time()
            accepted = self.callback(b''.join(p[7] for p in batch), first[2],
                max(0, last[3] - round(age * 1000)), last[4], discontinuity)
            self.last_callback_ms = round((time.monotonic()-callback_started)*1000,3)
            if hasattr(self,'pump_timing'):
                self.pump_timing.append((pump_gap_ms,batch_ms,self.last_callback_ms,(time.thread_time()-callback_cpu_started)*1000))
            if accepted is False:
                self.stats['callback_busy'] += 1
                with self.condition:
                    if (getattr(self, 'capture_active', True) and not self.stopped.is_set()
                            and generation == getattr(self, 'generation', 0)):
                        self.pending.extendleft(reversed(batch))
                        self._trim()
                self.stopped.wait(.002)
                return
            with self.condition:
                if generation == getattr(self, 'generation', 0):
                    self.previous = last[5], last[6], last[2]
                    self._last_delivered_timestamp = last[1]
        except Exception as exc:
            self._error(exc)

    def _error(self, exc):
        if not self.stopped.is_set():
            self.stopped.set()
            with self.condition:
                self.condition.notify_all()
            self.error(exc)

    def close(self):
        self.stopped.set()
        with self.condition:
            self.condition.notify_all()
        for thread in (self.thread, self.worker):
            if thread is not None and thread is not threading.current_thread():
                thread.join(timeout=1)
        self.sock.close()
        Path(self.path).unlink(missing_ok=True)
        if any(t is not None and t.is_alive() and t is not threading.current_thread() for t in (self.thread, self.worker)):
            raise RuntimeError('AEC reference worker did not stop')
