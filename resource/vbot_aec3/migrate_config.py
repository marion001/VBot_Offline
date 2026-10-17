"""Upgrade only mic DSP keys; keep device IDs, routing, media and credentials."""
import copy
import json
from pathlib import Path


def migrate(config):
    result = copy.deepcopy(config)
    smart = result.setdefault('smart_config', {})
    old = smart.pop('aec3', {})
    old.pop('noise_suppression', None)
    old.pop('bypass_legacy_filters', None)
    smart.pop('webrtcvad', None)
    processing = smart.setdefault('audio_processing', {})
    processing.setdefault('active', True)
    processing.setdefault('noise_suppression', 'low')
    processing.setdefault('high_pass_filter', True)
    processing.setdefault('agc', dict(active=True, target_level_dbfs=6,
                                     compression_gain_db=9, limiter=True))
    processing.setdefault('aec3', old)
    for stage in ('hotword', 'speak_to_text'):
        smart.get('smart_wakeup', {}).get(stage, {}).pop('gain', None)
    return result


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument('config')
    args = parser.parse_args()
    path = Path(args.config)
    old = json.loads(path.read_text(encoding='utf-8-sig'))
    updated = migrate(old)
    if updated != old:
        backup = path.with_name(path.name + '.before_webrtc.json')
        if not backup.exists():
            backup.write_bytes(path.read_bytes())
        temporary = path.with_name(path.name + '.webrtc.new')
        temporary.write_text(json.dumps(updated, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')
        temporary.chmod(path.stat().st_mode)
        temporary.replace(path)
    print('WebRTC configuration ready; device and routing settings preserved.')
