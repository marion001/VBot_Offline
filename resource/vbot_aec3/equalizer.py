"""Optional native speaker EQ. Never used on microphone or call PCM."""
import ctypes as C
import json
import math
import threading
from pathlib import Path

EQ_DIRECTORY=Path(__file__).resolve().parents[1]/'equalizer'
LIBRARY=str(EQ_DIRECTORY/'libvbot_eq.so')
BAND_KEYS=('bass_db','low_mid_db','mid_db','high_mid_db','treble_db','air_db')
GAIN_KEYS=BAND_KEYS+('preamp_db',)
DEFAULTS=dict(active=False,wet_percent=100,**{name:0 for name in GAIN_KEYS})


def needs_processing(value):
    return value['active'] and (value.get('preamp_db',0)!=0 or
        (value.get('wet_percent',100)>0 and any(value.get(k,0) for k in BAND_KEYS)))

def settings(config):
    value=dict(DEFAULTS,**config.get('media_player',{}).get('equalizer',{}))
    if type(value['active']) is not bool:raise ValueError('EQ active must be boolean')
    for name in GAIN_KEYS:
        x=value[name]
        if type(x) not in (int,float) or not math.isfinite(x) or not (-12 if name=='preamp_db' else -12)<=x<=(0 if name=='preamp_db' else 12):
            raise ValueError('Invalid EQ '+name)
    wet=value['wet_percent']
    if type(wet) not in (int,float) or not math.isfinite(wet) or not 0<=wet<=100:raise ValueError('Invalid EQ wet_percent')
    return value

def effective_bands(document,boot_id):
    presets=document.get('presets',[])
    default=next((p for p in presets if p.get('id')==document.get('default_preset')),None)
    if boot_id and document.get('boot_id')!=boot_id and default is not None:
        return default['bands']
    return document['current']

def current_boot_id():
    path=Path('/proc/sys/kernel/random/boot_id')
    return path.read_text().strip() if path.is_file() else ''

def read_settings(config_path=None,data_path=None):
    path=Path(config_path) if config_path else Path(__file__).resolve().parents[2]/'Config.json'
    config=json.loads(path.read_text(encoding='utf-8-sig'))
    active=config.get('media_player',{}).get('equalizer',{}).get('active',False)
    if active is False:return dict(DEFAULTS)
    data_path=Path(data_path) if data_path else path.parent/'resource/equalizer/settings.json'
    if data_path.exists():
        document=json.loads(data_path.read_text(encoding='utf-8-sig'))
        if document.get('legacy_import_pending') and 'bass_db' in config.get('media_player',{}).get('equalizer',{}):return settings(config)
        if document.get('version')!=1:raise ValueError('Invalid EQ preset file version')
        config={'media_player':{'equalizer':dict(effective_bands(document,current_boot_id()),active=active)}}
    return settings(config)

class Equalizer:
    def __init__(self,value,rate,library=LIBRARY):
        self.handle=None
        self.lib=C.CDLL(str(library))
        self.lib.vbot_eq_band_count.restype=C.c_int
        if self.lib.vbot_eq_band_count()!=6:raise RuntimeError('Build EQ 6-band library first')
        self.lib.vbot_eq_create6.argtypes=[C.c_int]+[C.c_double]*8
        self.lib.vbot_eq_create6.restype=C.c_void_p
        self.lib.vbot_eq_process.argtypes=[C.c_void_p,C.POINTER(C.c_int16),C.c_int]
        self.lib.vbot_eq_process.restype=C.c_int
        self.lib.vbot_eq_destroy.argtypes=[C.c_void_p]
        self.lib.vbot_eq_destroy.restype=None
        self.lib.vbot_eq_mix.argtypes=[C.POINTER(C.c_int16),C.POINTER(C.c_int16)]+[C.c_int]*3
        self.lib.vbot_eq_mix.restype=C.c_int
        self.handle=self.lib.vbot_eq_create6(rate,*(value.get(k,0) for k in GAIN_KEYS),value.get('wet_percent',100))
        if not self.handle:raise RuntimeError('EQ initialization failed')

    def process(self,pcm):
        if len(pcm)%4:raise ValueError('EQ requires S16 stereo frames')
        data=(C.c_int16*(len(pcm)//2)).from_buffer_copy(pcm)
        if self.lib.vbot_eq_process(self.handle,data,len(pcm)//4):raise RuntimeError('EQ processing failed')
        return bytes(data)

    def close(self):
        if self.handle:self.lib.vbot_eq_destroy(self.handle);self.handle=None

def optional_equalizer(rate,log=print,value=None):
    try:
        value=read_settings() if value is None else value
        if not needs_processing(value):return None
        return Equalizer(value,rate)
    except Exception as error:
        log('[EQ] Bỏ qua EQ; tiếp tục phát âm thanh: '+str(error))
        return None


class LiveEqualizer:
    """Watch config off the audio thread; crossfade filter changes over 30 ms."""
    def __init__(self,rate,log=print,config_path=None,library=LIBRARY,data_path=None):
        self.rate,self.log,self.library=rate,log,library
        self.path=Path(config_path) if config_path else Path(__file__).resolve().parents[2]/'Config.json'
        self.data_path=Path(data_path) if data_path else self.path.parent/'resource/equalizer/settings.json'
        self.lock=threading.Lock();self.stopped=threading.Event()
        self.current=self.next=None;self.pending=None;self.transition=False
        self.progress=0;self.total=max(1,round(rate*.03));self.seen=None;self.stamp=None
        try:self._reload()
        except Exception as error:self.log('[EQ] Bỏ qua EQ; tiếp tục phát âm thanh: '+str(error))
        if self.pending is not None:
            self.current=self.pending[1];self.pending=None
        self.worker=threading.Thread(target=self._watch,name='speaker-eq-config',daemon=True)
        self.worker.start()

    def _reload(self):
        stat=self.path.stat();data_stat=self.data_path.stat() if self.data_path.exists() else None
        stamp=(stat.st_mtime_ns,stat.st_size,data_stat.st_mtime_ns if data_stat else None,data_stat.st_size if data_stat else None)
        if stamp==self.stamp:return
        value=read_settings(self.path,self.data_path)
        if value==self.seen:
            self.stamp=stamp
            return
        processor=None
        if needs_processing(value):
            processor=Equalizer(value,self.rate,self.library)
        with self.lock:
            if self.pending is not None and self.pending[1] is not None:self.pending[1].close()
            self.pending=(value,processor)
        self.seen=value
        self.stamp=stamp

    def _watch(self):
        last_error=None
        while not self.stopped.wait(.25):
            try:self._reload();last_error=None
            except Exception as error:
                if str(error)!=last_error:self.log('[EQ] Không cập nhật được thông số; giữ âm thanh hiện tại: '+str(error))
                last_error=str(error)

    def process(self,pcm):
        if not self.transition and self.lock.acquire(blocking=False):
            try:
                if self.pending is not None:
                    self.next=self.pending[1];self.pending=None
                    self.progress=0;self.transition=True
            finally:self.lock.release()
        if not self.transition:return self.current.process(pcm) if self.current else pcm
        old=self.current.process(pcm) if self.current else pcm
        new=self.next.process(pcm) if self.next else pcm
        mixer=self.next or self.current
        if mixer is not None:
            before=(C.c_int16*(len(old)//2)).from_buffer_copy(old)
            after=(C.c_int16*(len(new)//2)).from_buffer_copy(new)
            if mixer.lib.vbot_eq_mix(before,after,len(pcm)//4,self.progress,self.total):raise RuntimeError('EQ crossfade failed')
            new=bytes(after)
        self.progress+=len(pcm)//4
        if self.progress>=self.total:
            if self.current:self.current.close()
            self.current,self.next=self.next,None;self.transition=False
        return new

    def close(self):
        self.stopped.set()
        self.worker.join()
        for processor in (self.current,self.next,self.pending[1] if self.pending else None):
            if processor:processor.close()
        self.current=self.next=None;self.pending=None
