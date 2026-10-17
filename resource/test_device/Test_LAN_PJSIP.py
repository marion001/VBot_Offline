"""Hardware integration probe, independent of VBot/Porcupine initialization.

Uses production manager, audio lease, AEC and NS/AGC with one real recorder.
No system audio routing or mixer changes. Config/key supplied via private JSON.
"""
import argparse
import array
import ast
import json
import queue
from pathlib import Path
import sys
import threading
from types import SimpleNamespace as NS


def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('--root',required=True)
    parser.add_argument('--config',required=True)
    args=parser.parse_args()
    sys.path.insert(0,args.root)
    config=json.loads(Path(args.config).read_text())
    from pvrecorder import PvRecorder
    import Lib_AEC3
    Lib_AEC3._load_backend()
    from _vbot_media_aec3.microphone import MicrophoneProcessor,settings
    from LAN_Calls import PJSIPCallManager
    ap=config['smart_config']['audio_processing']
    source=config.get('mic_name')
    device=-1
    if source:
        matches=[i for i,n in enumerate(PvRecorder.get_available_devices()) if source.casefold() in n.casefold()]
        if len(matches)!=1:raise RuntimeError('Mic name must match one device')
        device=matches[0]
    recorder=PvRecorder(device_index=device,frame_length=512)
    owner=['hotword']; processing=[False]; processor=[None]; frames=[0]
    mic_event=threading.Event();mic_event.set()
    stopping=threading.Event()
    def change_owner(value):
        owner[0]=value
    def hardware_reader():
        while not stopping.is_set():
            if not mic_event.wait(.1):continue
            lib.read_recorder_frame(enabled_event=mic_event,owner='hotword')
    def log(value,**kwargs):print(value,flush=True)
    def claim(name):
        if processing[0]:return False
        processing[0]=True;return True
    def raw(**kwargs):
        if owner[0]!=kwargs['owner']:return None
        frame=recorder.read();frames[0]+=1;return frame
    def filters(frame):
        if not ap['active']:return frame
        if processor[0] is None:
            processor[0]=MicrophoneProcessor(settings(config),ap['aec3']['library'],log)
        return processor[0].process_frame(frame,'lan_call')
    lib=NS(config=config,main_vbot_processing=False,get_mic_capture_owner=lambda:owner[0],
           set_mic_capture_owner=change_owner,
           current_system_sound=None,Play_Answer_Flag=False,mic_on_off=True,
           recorder=recorder,mic_event=mic_event,try_start_processing=claim,
           finish_processing=lambda **kwargs:processing.__setitem__(0,False),
           audio_manager=NS(request_focus=lambda *a,**k:True,release_focus=lambda *a,**k:True),
           recorder_action=lambda action:getattr(recorder,action)(),
           read_recorder_frame=raw,process_call_microphone_frame=filters,
           clear_audio_capture_buffer=lambda:None,media_aec3=None)
    class RecorderProxy:
        def read(self):
            frame=recorder.read();frames[0]+=1;return frame
        def __getattr__(self,name):return getattr(recorder,name)
    lib.recorder=RecorderProxy()
    lib.recorder_read_lock=threading.RLock()
    lib.recorder_health_lock=threading.Lock()
    lib.recorder_consecutive_errors=0
    lib.mic_processor=None;lib.mic_processor_failed=False
    lib.show_log=log;lib.Color=NS(RED=1)
    def mic_error(error):raise error
    lib._record_recorder_error=mic_error
    source_tree=ast.parse((Path(args.root)/'Lib.py').read_text(encoding='utf-8-sig'))
    names={'read_recorder_frame','recorder_action','process_microphone_frame',
           'process_microphone_filters','process_call_microphone_frame','process_hotword_aec3',
           'close_microphone_processing'}
    nodes=[n for n in source_tree.body if isinstance(n,ast.FunctionDef) and n.name in names]
    if len(nodes)!=len(names):raise RuntimeError('Production mic gateway functions missing')
    exec(compile(ast.Module(body=nodes,type_ignores=[]),'Lib.py','exec'),lib.__dict__)
    sys.modules['Lib']=lib
    manager=PJSIPCallManager(config['lan_calls'],notify=lambda event,state:log(json.dumps({'event':event,'state':state},ensure_ascii=False)))
    try:
        recorder.start()
        reader=threading.Thread(target=hardware_reader,daemon=True);reader.start()
        manager.start();log('PROBE READY')
        for line in sys.stdin:
            action,_,value=line.strip().partition(' ')
            try:
                if action=='quit':break
                if action=='fail-engine':
                    # Exercise exception cleanup (including SWIG wrapper lifetime).
                    manager.jobs.put(('probe_invalid_job',))
                    continue
                if action=='call':manager.dial(value)
                elif action=='accept':manager.accept()
                elif action=='end':manager.end()
                elif action in ('mute','unmute'):manager.mute(action=='mute')
                elif action=='status':
                    log(json.dumps(dict(manager.snapshot(),recorder_instances=1,
                                        recorder_frames=frames[0],owner=owner[0],
                                        ns_agc=lib.mic_processor.snapshot() if lib.mic_processor else None),ensure_ascii=False))
            except Exception as error:log('COMMAND ERROR: '+str(error))
    finally:
        manager.stop()
        stopping.set()
        if 'reader' in locals():reader.join(timeout=3)
        lib.close_microphone_processing()
        recorder.stop();recorder.delete()
        log('PROBE STOPPED owner='+owner[0])


if __name__=='__main__':main()
