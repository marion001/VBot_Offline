"""Exercise VBot's new libVLC/ALSA AEC adapter without importing VBot/Lib.

Read Config.json but never modify it; switches are enabled only in this probe.
"""
import argparse
import array
import json
from pathlib import Path
import sys
import time
import wave

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
from Lib_AEC3 import MediaAEC3, external_media_device


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--config', required=True)
    parser.add_argument('--music')
    parser.add_argument('--listen-only', action='store_true', help='Measure Bluetooth/AirPlay; do not create a music player')
    parser.add_argument('--seconds', type=int, default=30)
    parser.add_argument('--volume', type=int, default=30)
    parser.add_argument('--exercise-controls', action='store_true')
    parser.add_argument('--external', action='store_true', help='Read Multiroom ALSA speaker reference')
    parser.add_argument('--record-dir', help='Save raw and processed mic for comparison')
    parser.add_argument('--capture-gain', type=float)
    parser.add_argument('--mic-id', type=int)
    args = parser.parse_args()
    if not args.listen_only and not args.music:
        parser.error('--music is required unless --listen-only')
    if args.listen_only and (not args.external or args.exercise_controls):
        parser.error('--listen-only requires --external and cannot exercise VLC controls')
    import vlc
    from pvrecorder import PvRecorder
    cfg = json.loads(Path(args.config).read_text(encoding='utf-8'))
    smart = cfg.setdefault('smart_config', {})
    aec = smart.get('audio_processing', {}).setdefault('aec3', {}) if 'audio_processing' in smart else smart.setdefault('aec3', {})
    aec['active'] = True
    cfg.setdefault('media_player', {})['wake_up_in_media_player'] = True
    if args.capture_gain is not None:
        aec['capture_gain'] = args.capture_gain
    bridge = recorder = instance = player = mic_processor = None
    files = []
    frames = processed = 0
    try:
        bridge = MediaAEC3(cfg, external=args.external)
        from Lib_AEC3 import _load_backend, settings as aec_settings
        _load_backend()
        from _vbot_media_aec3.microphone import settings as mic_settings, MicrophoneProcessor
        mic_options = mic_settings(cfg)
        if mic_options['active']:
            mic_processor = MicrophoneProcessor(mic_options, aec_settings(cfg)['library'])
        device = (external_media_device(cfg) or cfg['media_player'].get('multiroom_audio', {}).get(
            'vlc_loopback_output_device', 'plughw:Loopback,0,0')) if args.external else 'default'
        if not args.listen_only:
            instance = vlc.Instance('--aout=alsa', '--alsa-audio-device=' + device, '--no-video', '--quiet', '--intf=dummy', '--file-caching=300')
            player = instance.media_player_new()
            if not args.external:
                bridge.attach(vlc, player)
        if args.record_dir:
            directory = Path(args.record_dir)
            directory.mkdir(parents=True, exist_ok=True)
            for name in ('raw', 'clean'):
                f = wave.open(str(directory / (name + '.wav')), 'wb')
                f.setparams((1, 2, 16000, 0, 'NONE', 'not compressed'))
                files.append(f)
        mic_id = cfg['smart_config']['mic']['id'] if args.mic_id is None else args.mic_id
        recorder = PvRecorder(device_index=mic_id, frame_length=512)
        recorder.start()
        if player:
            player.set_media(instance.media_new(args.music))
            player.audio_set_volume(args.volume)
            player.play()
            time.sleep(.2)
            player.audio_set_volume(args.volume)
        deadline = time.monotonic() + args.seconds
        started = time.monotonic()
        actions = set()
        report = time.monotonic() + 5
        while time.monotonic() < deadline:
            elapsed = time.monotonic() - started
            if args.exercise_controls:
                for label, at, action in [('pause', 8, lambda: player.set_pause(1)),
                                          ('resume', 10, lambda: player.set_pause(0)),
                                          ('volume', 16, lambda: player.audio_set_volume(20))]:
                    if elapsed >= at and label not in actions:
                        actions.add(label)
                        action()
                        print('control:', label, flush=True)
            raw = recorder.read()
            frame = bridge.process_frame(raw)
            is_aec = int(getattr(frame, 'aec3_processed', False))
            if frame is not None and mic_processor:
                frame = mic_processor.process_frame(frame, 'hotword')
            if files:
                files[0].writeframesraw(array.array('h', raw).tobytes())
                if frame is not None:
                    files[1].writeframesraw(array.array('h', frame).tobytes())
            frames += 1
            processed += is_aec
            if bridge.failed:
                raise RuntimeError(bridge.failed)
            if time.monotonic() > report:
                print(json.dumps(dict(frames=frames, processed=processed, aec=bridge.processor.snapshot(),
                    alsa_delay_ms=bridge.player.delay_ms, underruns=bridge.player.underruns,
                    capture_clipped=bridge.capture_clipped_samples, wake_clipped=bridge.wake_clipped_samples,
                    aec_saturated=bridge.aec_saturated_samples)), flush=True)
                report = time.monotonic() + 5
        if not processed:
            raise RuntimeError('No AEC-processed microphone frames; check VLC callbacks')
        if mic_processor:
            print('WebRTC MIC:', json.dumps(mic_processor.snapshot()), flush=True)
        print('libVLC AEC integration probe OK', flush=True)
    finally:
        for f in files:
            f.close()
        if bridge:
            bridge.cancelled.set()
            if bridge.player:
                bridge.player.interrupt()
        if player:
            player.stop()
            player.release()
        if recorder:
            recorder.stop()
            recorder.delete()
        if bridge:
            bridge.close()
        if mic_processor:
            mic_processor.close()
        if instance:
            instance.release()


if __name__ == '__main__':
    main()
