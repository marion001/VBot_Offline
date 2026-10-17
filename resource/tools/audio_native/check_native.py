"""Offline ABI/PCM/NS/AGC/AEC probe; does not open microphone or speaker."""
import argparse
import ctypes as C
import json
import math
import random
import statistics
import time
from pathlib import Path


def bind(path):
    lib = C.CDLL(str(path))
    pcm = C.POINTER(C.c_int16)
    for name, args, result in (
        ('vbot_aec_abi', [], C.c_int),
        ('vbot_aec_create', [], C.c_void_p),
        ('vbot_mic_create', [C.c_int] * 6, C.c_void_p),
        ('vbot_aec_capture', [C.c_void_p, pcm, pcm, C.c_int], C.c_int),
        ('vbot_aec_render', [C.c_void_p, pcm], C.c_int),
        ('vbot_aec_get_linear', [C.c_void_p, pcm], C.c_int),
        ('vbot_aec_destroy', [C.c_void_p], None),
    ):
        fn = getattr(lib, name)
        fn.argtypes, fn.restype = args, result
    assert lib.vbot_aec_abi() == 1
    return lib


def times(values):
    ordered = sorted(values)
    return dict(mean_ms=round(statistics.mean(values), 3),
                p95_ms=round(ordered[int(.95 * (len(ordered)-1))], 3),
                max_ms=round(max(values), 3), over_10ms=sum(x > 10 for x in values))


def check(path):
    lib = bind(path)
    block = C.c_int16 * 160
    # Output must be written when every filter is off, even with a poisoned dest.
    handle = lib.vbot_mic_create(0, 0, 6, 9, 1, 0)
    assert handle, 'all-off initialization failed'
    try:
        for i in range(50):
            source = block(*(int(1500 * math.sin((i*160+j)*.08)) for j in range(160)))
            output = block(*([12345] * 160))
            assert lib.vbot_aec_capture(handle, source, output, 0) == 0
            assert list(source) == list(output), 'all-off PCM is not bit exact'
    finally:
        lib.vbot_aec_destroy(handle)
    modes = []
    for ns in range(5):
        for agc in (0, 1):
            handle = lib.vbot_mic_create(ns, agc, 6, 9, 1, 1)
            assert handle, 'NS/AGC initialization failed'
            elapsed, energy, clipped = [], 0, 0
            rng = random.Random(47)
            try:
                for i in range(300):
                    source = block(*(int(1800*math.sin((i*160+j)*.13)) + rng.randint(-200, 200) for j in range(160)))
                    output = block()
                    start = time.perf_counter()
                    assert lib.vbot_aec_capture(handle, source, output, 0) == 0
                    elapsed.append((time.perf_counter()-start)*1000)
                    energy += sum(x*x for x in output)
                    clipped += sum(abs(x) >= 32767 for x in output)
                assert energy > 0, 'NS/AGC output is silent'
                assert clipped == 0, 'unexpected clipping at low input level'
                modes.append(dict(ns=ns, agc=agc, clipped=clipped, **times(elapsed)))
            finally:
                lib.vbot_aec_destroy(handle)
    # Deterministic echo only. A linear output mistakenly interpreted as S16
    # instead of normalized float will collapse to +/-1, failing early RMS.
    handle = lib.vbot_aec_create()
    assert handle, 'AEC initialization failed'
    rng = random.Random(91)
    render = [rng.randint(-5000, 5000) for _ in range(160 * 2000)]
    early_energy, raw_energy, linear_energy, elapsed = 0, 0, 0, []
    try:
        for i in range(2000):
            offset = i * 160
            reference = block(*render[offset:offset+160])
            echo = [int(.45 * render[k-512]) if k >= 512 else 0 for k in range(offset, offset+160)]
            source, output, linear = block(*echo), block(), block()
            start = time.perf_counter()
            assert lib.vbot_aec_render(handle, reference) == 0
            assert lib.vbot_aec_capture(handle, source, output, 32) == 0
            assert lib.vbot_aec_get_linear(handle, linear) == 0
            elapsed.append((time.perf_counter()-start)*1000)
            if 10 <= i < 50:
                early_energy += sum(x*x for x in linear)
            if i >= 1500:
                raw_energy += sum(x*x for x in source)
                linear_energy += sum(x*x for x in linear)
        early_rms = math.sqrt(early_energy / (40 * 160))
        assert early_rms > 10, 'linear PCM scale incorrect or unexpectedly silent'
        erle = 10 * math.log10(raw_energy / max(1, linear_energy))
        assert erle > 6, 'synthetic echo did not converge'
        aec = dict(early_linear_rms=round(early_rms, 2),
                   synthetic_erle_db=round(erle, 2), **times(elapsed))
    finally:
        lib.vbot_aec_destroy(handle)
    handle = lib.vbot_aec_create()
    assert handle, 'local-speech AEC initialization failed'
    raw_energy, linear_energy, normal_energy = 0, 0, 0
    try:
        for i in range(200):
            source = block(*(int(3000*math.sin(2*math.pi*440*(i*160+j)/16000)) for j in range(160)))
            output, linear = block(), block()
            assert lib.vbot_aec_render(handle, block()) == 0
            assert lib.vbot_aec_capture(handle, source, output, 32) == 0
            assert lib.vbot_aec_get_linear(handle, linear) == 0
            if i >= 100:
                raw_energy += sum(x*x for x in source)
                linear_energy += sum(x*x for x in linear)
                normal_energy += sum(x*x for x in output)
        ratio = math.sqrt(linear_energy / raw_energy)
        assert .1 < ratio < 1.5, 'local signal muted or incorrectly amplified'
        aec['local_signal_rms_ratio'] = round(ratio, 3)
        normal_ratio = math.sqrt(normal_energy / raw_energy)
        assert .1 < normal_ratio < 1.5, 'normal AEC output muted local signal'
        aec['normal_local_signal_rms_ratio'] = round(normal_ratio, 3)
    finally:
        lib.vbot_aec_destroy(handle)
    return dict(library=str(path), all_off_bit_exact=True, modes=modes, aec=aec)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('libraries', nargs='+')
    parser.add_argument('--output', required=True)
    args = parser.parse_args()
    results = [check(Path(p).resolve()) for p in args.libraries]
    Path(args.output).write_text(json.dumps(results, indent=2) + '\n')
    print(json.dumps(results, indent=2))
