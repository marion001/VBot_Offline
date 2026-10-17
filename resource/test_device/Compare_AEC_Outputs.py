"""Compare linear/normal residuals from ONE AEC state and ONE microphone read."""
import argparse
import array
import json
import math
from pathlib import Path
import runpy
import sys
import time

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--config', required=True)
    parser.add_argument('--music', required=True)
    parser.add_argument('--seconds', type=int, default=30)
    parser.add_argument('--volume', type=int, default=45)
    parser.add_argument('--output', required=True)
    args = parser.parse_args()
    import Lib_AEC3
    Lib_AEC3._load_backend()
    import _vbot_media_aec3.processing as processing
    from _vbot_media_aec3.microphone import MicrophoneProcessor, settings
    config = json.loads(Path(args.config).read_text(encoding='utf-8-sig'))
    value = settings(config)
    library = Lib_AEC3.settings(config)['library']
    post = {name: MicrophoneProcessor(value, library, lambda _: None) for name in ('linear', 'normal')}
    stats = {name: {'energy': 0, 'samples': 0} for name in ('linear', 'normal', 'linear_post', 'normal_post')}
    first_capture = [None]
    base = processing.AEC3

    class MeasuredAEC3(base):
        def __init__(self, noise_suppression='off', library=library, output='linear'):
            # ProcessStream produces normal output; GetLinearAecOutput exposes
            # the paired linear output from exactly the same adaptive state.
            super().__init__(noise_suppression, library, 'linear')

        def process_capture(self, pcm, delay_ms):
            linear = super().process_capture(pcm, delay_ms)
            values = dict(linear=array.array('h', linear), normal=array.array('h', self.normal_output))
            assert len(values['linear']) == len(values['normal'])
            values.update({name+'_post': post[name].process_frame(values[name], 'comparison') for name in ('linear', 'normal')})
            if first_capture[0] is None:
                first_capture[0] = time.monotonic()
            if time.monotonic() - first_capture[0] > 3:
                for name, samples in values.items():
                    stats[name]['energy'] += sum(x*x for x in samples)
                    stats[name]['samples'] += len(samples)
            return linear

    processing.AEC3 = MeasuredAEC3
    try:
        script = ROOT/'resource/test_device/Test_Media_AEC3.py'
        sys.argv = [str(script), '--config', args.config, '--music', args.music,
                    '--seconds', str(args.seconds), '--volume', str(args.volume), '--external']
        runpy.run_path(str(script), run_name='__main__')
    finally:
        processing.AEC3 = base
        for processor in post.values():
            processor.close()
    result = {name: dict(rms=round(math.sqrt(row['energy']/max(1, row['samples'])), 3), samples=row['samples']) for name, row in stats.items()}
    assert all(row['samples'] > 0 for row in stats.values())
    result['normal_vs_linear_db'] = round(20*math.log10(max(.001,result['normal']['rms'])/max(.001,result['linear']['rms'])), 2)
    result['normal_vs_linear_post_db'] = round(20*math.log10(max(.001,result['normal_post']['rms'])/max(.001,result['linear_post']['rms'])), 2)
    result['note'] = 'Same capture/render. Room input is uncontrolled; not a wake-word recognition score.'
    Path(args.output).write_text(json.dumps(result, indent=2)+'\n')
    print(json.dumps(result))


if __name__ == '__main__':
    main()
