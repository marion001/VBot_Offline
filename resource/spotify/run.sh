#!/bin/sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
CONFIG_PATH="$ROOT/Config.json"
STATE_DIR="$ROOT/resource/spotify/runtime"
mkdir -p "$STATE_DIR"
chmod 0777 "$STATE_DIR" 2>/dev/null || true
rm -f "$STATE_DIR/lockfile"

python3 - "$CONFIG_PATH" "$STATE_DIR/config.yml" <<'PY'
import hashlib
import json
import pathlib
import socket
import sys

config_path = pathlib.Path(sys.argv[1])
out_path = pathlib.Path(sys.argv[2])

cfg = {}
try:
    cfg = json.loads(config_path.read_text(encoding="utf-8-sig"))
except Exception:
    cfg = {}

mp_cfg = cfg.get("media_player", {}) if isinstance(cfg.get("media_player"), dict) else {}
sp_cfg = mp_cfg.get("spotify_connect", {}) if isinstance(mp_cfg.get("spotify_connect"), dict) else {}
mr_cfg = mp_cfg.get("multiroom_audio", {}) if isinstance(mp_cfg.get("multiroom_audio"), dict) else {}

raw_id = ""
for mid_file in ("/etc/machine-id", "/var/lib/dbus/machine-id"):
    try:
        txt = pathlib.Path(mid_file).read_text(encoding="utf-8").strip()
        if txt:
            raw_id = txt
            break
    except OSError:
        continue
if not raw_id:
    raw_id = socket.gethostname()
device_id = hashlib.sha1(("vbot-spotify-" + raw_id).encode("utf-8")).hexdigest()

custom_name = " ".join(str(sp_cfg.get("device_name") or "").split())[:64]
if not custom_name:
    full_name = " ".join(str((cfg.get("contact_info") or {}).get("full_name") or "").split())[:56]
    custom_name = f"VBot {full_name}".strip() if full_name else "VBot Speaker"

bitrate = int(sp_cfg.get("bitrate", 320) or 320)
if bitrate not in (96, 160, 320):
    bitrate = 320

initial_volume = max(0, min(100, int(sp_cfg.get("initial_volume", 80) or 80)))
api_port = int(sp_cfg.get("api_port", 3678) or 3678)
if not (1024 <= api_port <= 65535):
    api_port = 3678

audio_device = str(sp_cfg.get("audio_device") or "").strip()
if not audio_device:
    asound_txt = ""
    try:
        asound_txt = pathlib.Path("/etc/asound.conf").read_text(encoding="utf-8", errors="ignore")
    except OSError:
        pass
    if "pcm.vbot_aec_media" in asound_txt:
        audio_device = "vbot_aec_media"
    else:
        audio_device = str(mr_cfg.get("vlc_loopback_output_device") or "default").strip() or "default"

yaml_lines = [
    'log_level: "warn"',
    f'device_id: "{device_id}"',
    f"device_name: {json.dumps(custom_name, ensure_ascii=False)}",
    'device_type: "speaker"',
    'audio_backend: "alsa"',
    f"audio_device: {json.dumps(audio_device)}",
    'mixer_device: ""',
    "audio_buffer_time: 200000",
    "audio_period_count: 4",
    f"bitrate: {bitrate}",
    "volume_steps: 100",
    f"initial_volume: {initial_volume}",
    "external_volume: false",
    "normalisation_disabled: false",
    "zeroconf_enabled: true",
    'zeroconf_backend: "builtin"',
    "credentials:",
    '  type: "zeroconf"',
    "  zeroconf:",
    "    persist_credentials: true",
    "server:",
    "  enabled: true",
    '  address: "127.0.0.1"',
    f"  port: {api_port}",
    '  image_size: "large"',
]
out_path.write_text("\n".join(yaml_lines) + "\n", encoding="utf-8")
PY

exec /usr/local/bin/go-librespot --config_dir "$STATE_DIR"
