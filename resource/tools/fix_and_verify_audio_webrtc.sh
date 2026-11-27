#!/usr/bin/env bash
# ==============================================================================
# VBot Assistant - Cong cu tu dong sua loi, toi uu va kiem tra toan dien
# WebRTC Audio Processing 2.1 + AEC3 + ALSA theo OS Image (I2S / WM8960)
#
# Phan biet tu dong qua /os_image_created.txt:
#   - Co chu "i2s" (vd: VBot Assistant OS Image Build: 03/11/2026-i2s) -> I2S
#   - Khong co "i2s" (vd: VBot Assistant OS Image Build: 03/11/2026)    -> WM8960
#
# Cach dung tren SSH:
#   sudo bash /home/pi/VBot_Offline/resource/tools/fix_and_verify_audio_webrtc.sh
#   sudo bash /home/pi/VBot_Offline/resource/tools/fix_and_verify_audio_webrtc.sh --check-only
#   sudo bash /home/pi/VBot_Offline/resource/tools/fix_and_verify_audio_webrtc.sh --keep-mic-gain
# ==============================================================================
set -uo pipefail

VBOT_ROOT="/home/pi/VBot_Offline"
AEC3_ROOT="/home/pi/VBot_AEC3"
OS_MARKER_FILE="/os_image_created.txt"
CHECK_ONLY=0
KEEP_MIC_GAIN=0
SKIP_CONFIG_TUNE=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --root)
      VBOT_ROOT="$2"
      shift 2
      ;;
    --check-only)
      CHECK_ONLY=1
      shift
      ;;
    --keep-mic-gain)
      KEEP_MIC_GAIN=1
      shift
      ;;
    --skip-config-tune)
      SKIP_CONFIG_TUNE=1
      shift
      ;;
    -h|--help)
      echo "Cach dung: sudo bash $0 [--root /home/pi/VBot_Offline] [--check-only] [--keep-mic-gain] [--skip-config-tune]"
      exit 0
      ;;
    *)
      echo "[ERR] Tham so khong hop le: $1" >&2
      exit 2
      ;;
  esac
done

if [[ $EUID -ne 0 && $CHECK_ONLY -eq 0 ]]; then
  echo "[INFO] Dang nang quyen sudo..."
  exec sudo -E bash "$0" "$@"
fi

PI_USER="${SUDO_USER:-pi}"
if ! id "$PI_USER" >/dev/null 2>&1; then
  PI_USER="pi"
fi
PI_UID="$(id -u "$PI_USER" 2>/dev/null || echo 1000)"
PI_GID="$(id -g "$PI_USER" 2>/dev/null || echo 1000)"

C_RED='\033[0;31m'
C_GRN='\033[0;32m'
C_YLW='\033[1;33m'
C_BLU='\033[0;36m'
C_RST='\033[0m'

PASS_CNT=0
WARN_CNT=0
FAIL_CNT=0

ok()   { PASS_CNT=$((PASS_CNT + 1)); printf "${C_GRN}[OK]${C_RST}   %s\n" "$*"; }
warn() { WARN_CNT=$((WARN_CNT + 1)); printf "${C_YLW}[WARN]${C_RST} %s\n" "$*"; }
err()  { FAIL_CNT=$((FAIL_CNT + 1)); printf "${C_RED}[ERR]${C_RST}  %s\n" "$*"; }
info() { printf "${C_BLU}[INFO]${C_RST} %s\n" "$*"; }
step() { printf "\n${C_BLU}==================== %s ====================${C_RST}\n" "$*"; }

# Goi Sync_System_Services tuong thich ca khi la file .py hoac da build Cython .so
run_sync_services() {
  python3 -c "import sys; sys.path.insert(0, sys.argv[sys.argv.index('--root')+1]); import Sync_System_Services as sync; sys.exit(sync.main())" --root "$VBOT_ROOT" "$@"
}

# ------------------------------------------------------------------------------
# 1. Nhan dien OS Image (I2S vs WM8960)
# ------------------------------------------------------------------------------
step "1. NHAN DIEN OS IMAGE VA PHAN CUNG"

if [[ ! -f "$OS_MARKER_FILE" ]]; then
  err "Khong tim thay $OS_MARKER_FILE! Khong the nhan dien loai mach (I2S / WM8960)."
  exit 1
fi

OS_MARKER_RAW="$(tr -d '\r' < "$OS_MARKER_FILE" | head -n 1)"
if ! grep -qiE "VBot[[:space:]]+Assistant[[:space:]]+OS[[:space:]]+Image[[:space:]]+Build:" <<< "$OS_MARKER_RAW"; then
  err "Noi dung $OS_MARKER_FILE khong dung dinh dang VBot OS Image: '$OS_MARKER_RAW'"
  exit 1
fi

if grep -qi "i2s" <<< "$OS_MARKER_RAW"; then
  BOARD_TYPE="i2s"
  BOARD_LABEL="VBot AIO I2S (2x INMP441 + MAX98357)"
  EXPECTED_CARD="sndrpigooglevoi"
  OTHER_CARD="wm8960soundcard"
else
  BOARD_TYPE="wm8960"
  BOARD_LABEL="VBot AIO WM8960"
  EXPECTED_CARD="wm8960soundcard"
  OTHER_CARD="sndrpigooglevoi"
fi

info "OS Image Marker : $OS_MARKER_RAW"
info "Phan loai mach  : $BOARD_TYPE ($BOARD_LABEL)"
info "Card ALSA chuan : $EXPECTED_CARD"
info "Thu muc VBot    : $VBOT_ROOT"

if [[ ! -d "$VBOT_ROOT" || ! -f "$VBOT_ROOT/Config.json" ]]; then
  err "Khong tim thay thu muc VBot hoac Config.json tai: $VBOT_ROOT"
  exit 1
fi

# Kiem tra card phan cung thuc te tren kernel
CARDS_LIST="$(cat /proc/asound/card*/id 2>/dev/null || true)"
if ! grep -qx "$EXPECTED_CARD" <<< "$CARDS_LIST"; then
  err "Image la '$BOARD_TYPE' nhung khong thay card '$EXPECTED_CARD' trong /proc/asound! Danh sach card hien tai: $(tr '\n' ' ' <<< "$CARDS_LIST")"
  if [[ $CHECK_ONLY -eq 0 ]]; then
    echo "Hay kiem tra lai ket noi phan cung /boot/config.txt hoac reboot thiet bi truoc khi sua."
    exit 1
  fi
fi

# ------------------------------------------------------------------------------
# 2. Thuc thi sua loi & dong bo (bo qua neu --check-only)
# ------------------------------------------------------------------------------
VBOT_WAS_ACTIVE=0
if [[ $CHECK_ONLY -eq 0 ]]; then
  step "2. SAO LUU VA DONG BO CAU HINH THEO MACH ($BOARD_TYPE)"

  BACKUP_DIR="$AEC3_ROOT/fix_script_backups/$(date +%Y%m%d_%H%M%S)"
  mkdir -p "$BACKUP_DIR"
  for f in /etc/asound.conf /etc/shairport-sync.conf \
           /etc/systemd/system/i2s_dac_keepalive.service \
           /etc/systemd/system/bluealsa-aplay.service.d/90-vbot-aec3.conf \
           /var/lib/alsa/asound.state "$VBOT_ROOT/Config.json"; do
    if [[ -f "$f" ]]; then
      cp -a "$f" "$BACKUP_DIR/$(echo "$f" | sed 's|^/||; s|/|__|g')"
    fi
  done
  chown -R "$PI_UID:$PI_GID" "$AEC3_ROOT/fix_script_backups" 2>/dev/null || true
  info "Da sao luu cau hinh truoc khi sua tai: $BACKUP_DIR"

  # Tam dung VBot_Offline neu dang chay de giai phong mic/loa va cai .so an toan
  if sudo -u "$PI_USER" XDG_RUNTIME_DIR="/run/user/$PI_UID" systemctl --user is-active --quiet VBot_Offline.service 2>/dev/null; then
    VBOT_WAS_ACTIVE=1
    info "Tam dung VBot_Offline.service..."
    sudo -u "$PI_USER" XDG_RUNTIME_DIR="/run/user/$PI_UID" systemctl --user stop VBot_Offline.service || true
    sleep 1
  fi

  # Kiem tra xem con tien trinh Start.py / VBot.py chay tay khong (ke ca duong dan tuong doi khi build .so)
  MANUAL_PIDS="$(python3 - "$VBOT_ROOT" << 'PY' || true
import os, sys
from pathlib import Path
root = Path(sys.argv[1]).resolve()
my_pid = os.getpid()
pids = []
for proc in Path('/proc').glob('[0-9]*'):
    try:
        pid = int(proc.name)
        if pid == my_pid:
            continue
        argv = [x.decode('utf-8', 'replace') for x in (proc / 'cmdline').read_bytes().split(b'\0') if x]
        if any(Path(x).name in ('Start.py', 'VBot.py') for x in argv[1:]):
            try:
                cwd = (proc / 'cwd').resolve()
            except OSError:
                cwd = None
            if cwd == root or any(str(root) in x for x in argv[1:]):
                pids.append(str(pid))
    except (OSError, ValueError):
        continue
print(' '.join(pids))
PY
)"
  if [[ -n "$MANUAL_PIDS" ]]; then
    info "Tam dung tien trinh VBot chay thu cong (PID: $MANUAL_PIDS)..."
    kill -TERM $MANUAL_PIDS 2>/dev/null || true
    sleep 1.5
  fi

  # Don file giao dien cu Media_Player.php neu con sot tu OS image cu sau khi cap nhat
  if [[ -f "$VBOT_ROOT/html/Media_Player.php" ]]; then
    cp -a "$VBOT_ROOT/html/Media_Player.php" "$BACKUP_DIR/html__Media_Player.php" 2>/dev/null || true
    rm -f "$VBOT_ROOT/html/Media_Player.php"
    info "Da don file cu khong con su dung: html/Media_Player.php"
  fi

  # 2.1. Dam bao module snd-aloop (Loopback)
  info "[2.1] Kiem tra module kernel snd-aloop (Loopback)..."
  if [[ -f "$VBOT_ROOT/resource/vbot-loopback.conf" ]]; then
    install -m 0644 "$VBOT_ROOT/resource/vbot-loopback.conf" /etc/modules-load.d/vbot-loopback.conf
  fi
  if [[ ! -d /sys/module/snd_aloop ]]; then
    modprobe snd-aloop || true
    sleep 0.5
  fi

  # 2.2. Kiem tra va cai goi native WebRTC 2.1 + PJSIP tu ZIP di kem neu thieu/cu
  info "[2.2] Kiem tra thu vien native WebRTC 2.1 + AEC3 + PJSIP..."
  RUNTIME_ZIP="$VBOT_ROOT/resource/vbot_aec3/vbot_audio_runtime_armhf_py39.zip"
  RUNTIME_BUNDLE_PY="$VBOT_ROOT/resource/tools/audio_native/runtime_bundle.py"
  mkdir -p "$AEC3_ROOT/tmp"
  chown -R "$PI_UID:$PI_GID" "$AEC3_ROOT"

  NEED_NATIVE_INSTALL=0
  if [[ -f "$RUNTIME_ZIP" && -f "$RUNTIME_BUNDLE_PY" ]]; then
    if ! python3 - "$RUNTIME_ZIP" "$AEC3_ROOT" << 'PY'
import ctypes, hashlib, json, sys, zipfile
from pathlib import Path

zip_path, aec_root = Path(sys.argv[1]), Path(sys.argv[2])
so_path = aec_root / 'webrtc-2.1/libvbot_aec3_music_2_1.so'
if not so_path.is_file():
    sys.exit(1)
with zipfile.ZipFile(zip_path) as zf:
    manifest = json.loads(zf.read('runtime-manifest.json'))
for rel, expected_sha in manifest.get('files', {}).items():
    if rel == 'tools/runtime_bundle.py':
        continue
    target = aec_root / rel
    if not target.is_file() or hashlib.sha256(target.read_bytes()).hexdigest() != expected_sha:
        sys.exit(1)
lib = ctypes.CDLL(str(so_path))
lib.vbot_apm_version.restype = ctypes.c_char_p
assert lib.vbot_apm_version() == b'2.1' and lib.vbot_aec_abi() == 1
for fn in ('vbot_mic_create', 'vbot_aec_create', 'vbot_aec_get_linear'):
    getattr(lib, fn)
sys.exit(0)
PY
    then
      NEED_NATIVE_INSTALL=1
    fi
  fi

  if [[ $NEED_NATIVE_INSTALL -eq 1 ]]; then
    info "Phat hien thu vien native chua cai hoac la ban cu -> Tien hanh cai tu goi ZIP co san..."
    if python3 "$RUNTIME_BUNDLE_PY" check-platform "$RUNTIME_ZIP" >/dev/null 2>&1; then
      REQ_PKGS="$(python3 "$RUNTIME_BUNDLE_PY" packages "$RUNTIME_ZIP" 2>/dev/null || true)"
      MISSING_PKGS=""
      for pkg in $REQ_PKGS; do
        base_pkg="${pkg%%:*}"
        if ! dpkg -s "$base_pkg" >/dev/null 2>&1; then
          MISSING_PKGS="$MISSING_PKGS $pkg"
        fi
      done
      if [[ -n "$MISSING_PKGS" ]]; then
        info "Cai dat cac goi thu vien he thong con thieu:$MISSING_PKGS"
        apt-get update -qq && apt-get install -y $MISSING_PKGS
      fi
      sudo -u "$PI_USER" TMPDIR="$AEC3_ROOT/tmp" python3 "$RUNTIME_BUNDLE_PY" install "$RUNTIME_ZIP" --root "$AEC3_ROOT"
    else
      warn "Goi ZIP prebuilt khong tuong thich nen tang OS/Python hien tai; giu nguyen $AEC3_ROOT (can build thu cong neu thieu)."
    fi
  else
    info "Thu vien WebRTC 2.1 + PJSIP tai $AEC3_ROOT da khop 100% voi ban chuan."
  fi

  # 2.3. Sua ALSA va dich vu phan cung rieng theo tung loai mach
  info "[2.3] Dong bo ALSA (/etc/asound.conf) va dich vu am thanh cho mach $BOARD_TYPE..."
  if [[ "$BOARD_TYPE" == "i2s" ]]; then
    # Khoi phuc mau ALSA I2S chuan (dmixer 48000 Hz S32_LE), van giu nguyen vbot_aec_media neu da co
    python3 "$VBOT_ROOT/resource/vbot_aec3/hardware_routes.py" --root "$VBOT_ROOT" --restore-default

    # Cap nhat i2s_dac_keepalive.service (48000 Hz S32_LE, Nice=0, tiet kiem ~10% CPU)
    cat > /etc/systemd/system/i2s_dac_keepalive.service << 'KA_EOF'
[Unit]
Description=VBot Assistant - Keep I2S DAC awake to prevent pop noise
After=sound.target

[Service]
Type=simple
ExecStart=/usr/bin/aplay -q -D dmixer -t raw -r 48000 -f S32_LE -c 2 --period-size=2048 --buffer-size=8192 /dev/zero
Restart=always
RestartSec=1
Nice=0

[Install]
WantedBy=multi-user.target
KA_EOF
    chmod 0644 /etc/systemd/system/i2s_dac_keepalive.service
    for ka_tpl in "$VBOT_ROOT/resource/i2s/i2s_dac_keepalive.service" "$VBOT_ROOT/resource/i2s/i2s_dac_keepalive.service_aec_new"; do
      if [[ -f "$ka_tpl" ]]; then
        cp /etc/systemd/system/i2s_dac_keepalive.service "$ka_tpl"
        chown "$PI_UID:$PI_GID" "$ka_tpl" 2>/dev/null || true
      fi
    done
    systemctl daemon-reload
    systemctl enable i2s_dac_keepalive.service >/dev/null 2>&1 || true
    systemctl restart i2s_dac_keepalive.service
  else
    # WM8960: Khoi phuc mau ALSA WM8960 chuan (period_size=1024, buffer_size=8192, rate=48000, channels=2,
    # ten card co dinh wm8960soundcard, va khoa wm8960-soundcard.service khong xoa asound.conf)
    python3 "$VBOT_ROOT/resource/vbot_aec3/hardware_routes.py" --root "$VBOT_ROOT" --restore-default
    if [[ -f /etc/systemd/system/wm8960-soundcard.service.d/90-vbot-preserve-config.conf ]]; then
      systemctl daemon-reload
      systemctl restart wm8960-soundcard.service || true
    fi
  fi

  # 2.4. Cai hoac xac minh duong am thanh chung vbot_aec_media (AirPlay / Bluetooth / Multiroom / DLNA / Spotify)
  info "[2.4] Kiem tra va dong bo duong am thanh chung vbot_aec_media..."
  ROUTES_COMPLETE=0
  if grep -q "# BEGIN VBOT AEC3 MEDIA BUS" /etc/asound.conf 2>/dev/null \
     && grep -q 'pcm.vbot_aec_media' /etc/asound.conf 2>/dev/null \
     && [[ -f /etc/shairport-sync.conf ]] \
     && grep -Eq 'output_device[[:space:]]*=[[:space:]]*"vbot_aec_media"' /etc/shairport-sync.conf \
     && [[ -f /etc/systemd/system/bluealsa-aplay.service.d/90-vbot-aec3.conf ]] \
     && grep -q -- '--pcm=vbot_aec_media' /etc/systemd/system/bluealsa-aplay.service.d/90-vbot-aec3.conf; then
    ROUTES_COMPLETE=1
  fi

  if [[ $ROUTES_COMPLETE -eq 1 ]]; then
    python3 "$VBOT_ROOT/resource/vbot_aec3/install_media_routes.py" --root "$VBOT_ROOT" --ensure
  else
    # Dam bao bluetooth_agent va multiroom_bridge da duoc dong bo truoc khi install_media_routes --apply
    run_sync_services --only bluetooth_agent --apply --install >/dev/null 2>&1 || true
    run_sync_services --only multiroom_bridge --apply --install >/dev/null 2>&1 || true
    # Neu he thong chua cai du media bus nhung Config.json mau da bat san external_sources=true
    # hoac /etc/asound.conf co khoi bus do dang, dua ve trang thai sach truoc khi goi --apply.
    python3 - "$VBOT_ROOT/Config.json" << 'PY'
import json, sys
from pathlib import Path
p = Path(sys.argv[1])
cfg = json.loads(p.read_text(encoding='utf-8-sig'))
smart = cfg.setdefault('smart_config', {})
if 'audio_processing' in smart:
    smart['audio_processing'].setdefault('aec3', {})['external_sources'] = False
if 'aec3' in smart and isinstance(smart['aec3'], dict):
    smart['aec3']['external_sources'] = False
p.write_text(json.dumps(cfg, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')
asound = Path('/etc/asound.conf')
if asound.is_file():
    txt = asound.read_text(encoding='utf-8', errors='surrogateescape')
    b_tag, e_tag = '# BEGIN VBOT AEC3 MEDIA BUS', '# END VBOT AEC3 MEDIA BUS'
    while b_tag in txt and e_tag in txt and txt.index(b_tag) < txt.index(e_tag):
        s = txt.index(b_tag)
        e = txt.index(e_tag, s) + len(e_tag)
        txt = (txt[:s].rstrip() + '\n' + txt[e:].lstrip()).rstrip() + '\n'
    asound.write_text(txt, encoding='utf-8', errors='surrogateescape')
PY
    python3 "$VBOT_ROOT/resource/vbot_aec3/install_media_routes.py" --root "$VBOT_ROOT" --apply
  fi

  # 2.5. Can chinh gain Mixer phan cung/softvol theo tung loai mach (chong clipping ADC)
  if [[ "$BOARD_TYPE" == "i2s" ]]; then
    info "[2.5] Kiem tra muc softvol VBot_DAC_Microphone tren I2S..."
    if ! amixer -c "$EXPECTED_CARD" cget name='VBot_DAC_Microphone' >/dev/null 2>&1; then
      timeout 2 arecord -q -D default -f S16_LE -r 16000 -c 1 -d 1 /dev/null >/dev/null 2>&1 || true
    fi
    CUR_MIC_VAL="$(amixer -c "$EXPECTED_CARD" cget name='VBot_DAC_Microphone' 2>/dev/null | awk -F= '/^[[:space:]]*: values=/{split($2,a,","); print a[1]}' || true)"
    if [[ -n "$CUR_MIC_VAL" ]]; then
      if [[ $KEEP_MIC_GAIN -eq 1 ]]; then
        info "Giu nguyen VBot_DAC_Microphone=$CUR_MIC_VAL theo co --keep-mic-gain."
      elif [[ "$CUR_MIC_VAL" -gt 170 || "$CUR_MIC_VAL" -lt 110 ]]; then
        info "Dieu chinh VBot_DAC_Microphone tu $CUR_MIC_VAL -> 150,150 (~59%, +16.4 dB, chong clipping +30dB)..."
        amixer -c "$EXPECTED_CARD" cset name='VBot_DAC_Microphone' 150,150 >/dev/null
        alsactl store
      else
        info "VBot_DAC_Microphone hien tai ($CUR_MIC_VAL) nam trong vung an toan (110..170), luu alsactl store..."
        alsactl store
      fi
    fi
  else
    info "[2.5] Can chinh gain analog WM8960 (Input Boost=2 [+20dB], Capture=35 [+9dB], ADC HPF=on, ALC=Off, Speaker=121 [0dB])..."
    if [[ $KEEP_MIC_GAIN -eq 1 ]]; then
      info "Giu nguyen mixer WM8960 theo co --keep-mic-gain."
    else
      amixer -c "$EXPECTED_CARD" sset 'ALC Function' 'Off' >/dev/null 2>&1 || true
      amixer -c "$EXPECTED_CARD" sset 'ADC High Pass Filter' on >/dev/null 2>&1 || true
      amixer -c "$EXPECTED_CARD" sset 'Left Input Boost Mixer LINPUT1' 2 >/dev/null 2>&1 || true
      amixer -c "$EXPECTED_CARD" sset 'Right Input Boost Mixer RINPUT1' 2 >/dev/null 2>&1 || true
      CUR_WM_CAP="$(amixer -c "$EXPECTED_CARD" sget 'Capture' 2>/dev/null | awk '/Front Left: Capture/{print $4; exit}' || true)"
      if [[ -z "$CUR_WM_CAP" || "$CUR_WM_CAP" -gt 38 || "$CUR_WM_CAP" -lt 28 ]]; then
        amixer -c "$EXPECTED_CARD" sset 'Capture' 35,35 cap >/dev/null 2>&1 || true
      fi
      CUR_WM_SPK="$(amixer -c "$EXPECTED_CARD" sget 'Speaker' 2>/dev/null | awk '/Front Left: Playback/{print $4; exit}' || true)"
      if [[ -n "$CUR_WM_SPK" && "$CUR_WM_SPK" -gt 121 ]]; then
        amixer -c "$EXPECTED_CARD" sset 'Speaker' 121,121 >/dev/null 2>&1 || true
      fi
      mkdir -p /var/lib/alsa
      alsactl -f /var/lib/alsa/vbot-wm8960.state store "$EXPECTED_CARD" >/dev/null 2>&1 || true
      alsactl store >/dev/null 2>&1 || true
    fi
  fi

  # 2.6. Dong bo Config.json (nang cap schema cu + dien thong so toi uu theo mach)
  if [[ $SKIP_CONFIG_TUNE -eq 0 ]]; then
    info "[2.6] Nang cap schema va cap nhat thong so WebRTC MIC + AEC3 trong Config.json ($BOARD_TYPE)..."
    python3 - "$VBOT_ROOT" "$BOARD_TYPE" << 'PY'
import importlib.util, json, sys
from pathlib import Path

root = Path(sys.argv[1])
board = sys.argv[2]
cfg_path = root / 'Config.json'

spec = importlib.util.spec_from_file_location('migrate_config', root / 'resource/vbot_aec3/migrate_config.py')
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)

old_cfg = json.loads(cfg_path.read_text(encoding='utf-8-sig'))
cfg = mod.migrate(old_cfg)

ap = cfg.setdefault('smart_config', {}).setdefault('audio_processing', {})
ap['active'] = True
ap['high_pass_filter'] = True
ap['noise_suppression'] = 'low'
ap['agc'] = {
    'active': True,
    'target_level_dbfs': 6,
    'compression_gain_db': 9,
    'limiter': True,
}
aec = ap.setdefault('aec3', {})
aec['active'] = True
aec['library'] = '/home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so'
aec['output'] = 'linear'
aec['playback_device'] = aec.get('playback_device') or 'default'
aec['playback_latency_ms'] = 100
aec['capture_delay_ms'] = -16 if board == 'i2s' else 8
aec['render_clock_ppm'] = 0
aec['capture_gain'] = 1
aec['wake_gain'] = 1
aec['external_sources'] = True

mp = cfg.setdefault('media_player', {})
mp['wake_up_in_media_player'] = True
mr = mp.setdefault('multiroom_audio', {})
mr['vlc_loopback_output_device'] = 'vbot_aec_media'
mr['vlc_loopback_capture_device'] = 'plughw:Loopback,1,1'

lan_pjsip = cfg.setdefault('lan_calls', {}).setdefault('pjsip', {})
lan_pjsip['runtime'] = lan_pjsip.get('runtime') or '/home/pi/VBot_AEC3/pjsip'
lan_pjsip['sip_port'] = 5060
lan_pjsip.setdefault('rtp_port', 40000)
lan_pjsip.setdefault('codec', 'G722/16000/1')
lan_pjsip['capture_gain'] = 1
lan_pjsip.setdefault('output_gain', 0.5)
lan_pjsip['capture_delay_ms'] = 0 if board == 'i2s' else 24
lan_pjsip['render_clock_ppm'] = 0
lan_pjsip.setdefault('playback_latency_ms', 100)

tmp = cfg_path.with_suffix('.json.fix_tmp')
tmp.write_text(json.dumps(cfg, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')
tmp.chmod(cfg_path.stat().st_mode)
tmp.replace(cfg_path)
PY
    chown "$PI_UID:$PI_GID" "$VBOT_ROOT/Config.json" 2>/dev/null || true
    chmod 0777 "$VBOT_ROOT/Config.json" 2>/dev/null || true
  fi

  # 2.7. Dong bo cac system services cua VBot va khoi dong lai cac audio daemon
  info "[2.7] Dong bo dich vu he thong VBot va nap lai cac dich vu am thanh..."
  run_sync_services --apply --startup >/dev/null || true
  for svc in vbot-multiroom-bridge.service bluealsa-aplay.service shairport-sync.service vbot-bluetooth-agent.service; do
    if systemctl is-active --quiet "$svc"; then
      systemctl try-restart "$svc" || true
    fi
  done
  sleep 1.5
fi

# ------------------------------------------------------------------------------
# 3. Kiem tra doi chieu toan dien (Thua / Thieu / Sai)
# ------------------------------------------------------------------------------
step "3. KIEM TRA DOI CHIEU TOAN DIEN (THUA / THIEU / SAI)"

# --- [A] Card am thanh & khong lan cau hinh cheo giua I2S va WM8960 ---
CARDS_NOW="$(cat /proc/asound/card*/id 2>/dev/null || true)"
if grep -qx "$EXPECTED_CARD" <<< "$CARDS_NOW"; then
  ok "Card phan cung '$EXPECTED_CARD' da san sang tren he thong."
else
  err "Thieu card phan cung '$EXPECTED_CARD' trong /proc/asound/cards!"
fi

if grep -qx "Loopback" <<< "$CARDS_NOW" && [[ -f /etc/modules-load.d/vbot-loopback.conf ]]; then
  ok "Card 'Loopback' (snd-aloop) va /etc/modules-load.d/vbot-loopback.conf day du."
else
  err "Thieu card 'Loopback' hoac thieu /etc/modules-load.d/vbot-loopback.conf!"
fi

if grep -qx "$OTHER_CARD" <<< "$CARDS_NOW"; then
  warn "Phat hien card '$OTHER_CARD' cung xuat hien tren image '$BOARD_TYPE' (kiem tra lai /boot/config.txt)."
fi

# --- [B] Kiem tra chi tiet /etc/asound.conf (thieu, trung lap, sai thong so) ---
if [[ ! -f /etc/asound.conf ]]; then
  err "Thieu file /etc/asound.conf!"
else
  ASOUND_CHECK="$(python3 - "$BOARD_TYPE" << 'PY'
import re, sys
from pathlib import Path

board = sys.argv[1]
txt = Path('/etc/asound.conf').read_text(encoding='utf-8', errors='replace')
begin_n = txt.count('# BEGIN VBOT AEC3 MEDIA BUS')
end_n = txt.count('# END VBOT AEC3 MEDIA BUS')

if begin_n == 1 and end_n == 1:
    print("OK|Khoi '# BEGIN/END VBOT AEC3 MEDIA BUS' dung 1 ban duy nhat (khong thieu, khong trung lap).")
elif begin_n == 0 and end_n == 0:
    print("ERR|Thieu khoi '# BEGIN VBOT AEC3 MEDIA BUS' trong /etc/asound.conf!")
else:
    print(f"ERR|Khoi VBOT AEC3 MEDIA BUS bi trung lap hoac lech cap (BEGIN={begin_n}, END={end_n})!")

if 'pcm.vbot_aec_media' in txt and 'hw:Loopback,0,1' in txt and 'rate 48000' in txt:
    print("OK|Thiet bi pcm.vbot_aec_media -> hw:Loopback,0,1 (48000 Hz) chinh xac.")
else:
    print("ERR|Cau hinh pcm.vbot_aec_media hoac hw:Loopback,0,1 bi sai/thieu trong /etc/asound.conf!")

if board == 'i2s':
    if 'wm8960soundcard' in txt:
        print("ERR|Thua/sai cau hinh: Image I2S nhung /etc/asound.conf lai chua 'wm8960soundcard'!")
    else:
        print("OK|Khong bi lan cau hinh wm8960soundcard trong /etc/asound.conf.")
    m = re.search(r'(?ms)^pcm\.dmixer\s*\{.*?^\}', txt)
    if not m:
        print("ERR|Thieu khoi pcm.dmixer trong /etc/asound.conf (I2S)!")
    else:
        blk = m.group(0)
        if 'rate 48000' in blk and 'format S32_LE' in blk and 'rate 44100' not in blk:
            print("OK|I2S pcm.dmixer chay dung rate 48000 Hz + format S32_LE (khong con rate 44100 cu).")
        else:
            print("ERR|I2S pcm.dmixer chua dat dung 'rate 48000' + 'format S32_LE' (hoac con sot rate 44100)!")
    if 'VBot_DAC_Microphone' in txt and 'VBot_DAC_Speaker' in txt:
        print("OK|Day du control softvol VBot_DAC_Speaker va VBot_DAC_Microphone.")
    else:
        print("ERR|Thieu VBot_DAC_Speaker hoac VBot_DAC_Microphone trong /etc/asound.conf!")
else:
    if 'sndrpigooglevoi' in txt:
        print("ERR|Thua/sai cau hinh: Image WM8960 nhung /etc/asound.conf lai chua 'sndrpigooglevoi'!")
    else:
        print("OK|Khong bi lan cau hinh sndrpigooglevoi trong /etc/asound.conf.")
    if 'hw:0,0' in txt:
        print("ERR|Con sot tham chieu cung 'hw:0,0' trong /etc/asound.conf (de loi khi Loopback doi thu tu card)!")
    elif 'hw:CARD=wm8960soundcard,DEV=0' in txt:
        print("OK|WM8960 su dung ten card co dinh 'hw:CARD=wm8960soundcard,DEV=0'.")
    else:
        print("ERR|Thieu 'hw:CARD=wm8960soundcard,DEV=0' trong /etc/asound.conf!")
    m_dm = re.search(r'(?ms)^pcm\.dmixed\s*\{.*?^\}', txt)
    m_arr = re.search(r'(?ms)^pcm\.array\s*\{.*?^\}', txt)
    if (m_dm and m_arr
            and 'period_size 1024' in m_dm.group(0) and 'buffer_size 8192' in m_dm.group(0) and 'rate 48000' in m_dm.group(0)
            and 'period_size 1024' in m_arr.group(0) and 'buffer_size 8192' in m_arr.group(0) and 'rate 48000' in m_arr.group(0)
            and re.search(r'\bchannels\s+2\b', m_arr.group(0))):
        print("OK|WM8960 pcm.dmixed & pcm.array chay dung 48000 Hz, channels 2, period_size 1024 / buffer_size 8192 (khong tran queue AEC3).")
    else:
        print("ERR|WM8960 pcm.dmixed hoac pcm.array thieu period_size 1024 / buffer_size 8192 / rate 48000 / channels 2!")
PY
)"
  while IFS='|' read -r lvl msg; do
    [[ -z "$lvl" ]] && continue
    case "$lvl" in
      OK) ok "$msg" ;;
      WARN) warn "$msg" ;;
      ERR) err "$msg" ;;
    esac
  done <<< "$ASOUND_CHECK"
fi

# --- [C] Kiem tra thu vien Native WebRTC 2.1 + AEC3 + PJSIP ---
SO_FILE="$AEC3_ROOT/webrtc-2.1/libvbot_aec3_music_2_1.so"
if [[ ! -f "$SO_FILE" ]]; then
  err "Thieu thu vien AEC3 native: $SO_FILE"
else
  LDD_OUT="$(ldd "$SO_FILE" 2>&1 || true)"
  if grep -q "not found" <<< "$LDD_OUT"; then
    err "ldd $SO_FILE bao thieu thu vien lien ket: $LDD_OUT"
  else
    ok "ldd $SO_FILE day du tat ca shared libraries."
  fi

  NATIVE_TEST="$(PYTHONPATH="$AEC3_ROOT/pjsip/python:$VBOT_ROOT" python3 - "$SO_FILE" "$VBOT_ROOT/resource/tools/audio_native/check_native.py" << 'PY' 2>&1 || echo "ERR|Chay kiem thu native that bai"
import importlib.util, sys
from pathlib import Path

so_path = Path(sys.argv[1])
chk_py = Path(sys.argv[2])
spec = importlib.util.spec_from_file_location('check_native', chk_py)
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)
rep = mod.check(so_path)
aec = rep['aec']
print(f"OK|DSP WebRTC 2.1 ABI=1 hoat dong tot (synthetic ERLE={aec['synthetic_erle_db']} dB, p95={aec['p95_ms']} ms, all_off_bit_exact={rep['all_off_bit_exact']}).")
try:
    import pjsua2
    print("OK|PJSIP Python binding (pjsua2) import thanh cong.")
except Exception as e:
    print(f"WARN|Khong import duoc pjsua2 (Call LAN): {e}")
PY
)"
  while IFS='|' read -r lvl msg; do
    [[ -z "$lvl" ]] && continue
    case "$lvl" in
      OK) ok "$msg" ;;
      WARN) warn "$msg" ;;
      ERR) err "$msg" ;;
      *) err "$lvl $msg" ;;
    esac
  done <<< "$NATIVE_TEST"
fi

# --- [D] Kiem tra AirPlay, BlueALSA va dich vu theo mach ---
if [[ -f /etc/shairport-sync.conf ]]; then
  SH_CHECK="$(python3 - << 'PY'
import re
from pathlib import Path
txt = Path('/etc/shairport-sync.conf').read_text(encoding='utf-8', errors='replace')
token = re.compile(r'"(?:\\.|[^"\\])*"|//[^\n]*|/\*.*?\*/', re.S)
masked = token.sub(lambda m: m.group() if m.group().startswith('"') else ''.join('\n' if x == '\n' else ' ' for x in m.group()), txt)
matches = list(re.finditer(r'^\s*alsa\s*=\s*\{', masked, re.M))
if len(matches) != 1:
    print(f"ERR|File /etc/shairport-sync.conf co {len(matches)} khoi alsa (can dung 1 khoi)!")
else:
    s = matches[0].end()
    depth = 1
    in_s = esc = False
    for e in range(s, len(masked)):
        c = masked[e]
        if esc: esc = False
        elif in_s and c == '\\': esc = True
        elif c == '"': in_s = not in_s
        elif not in_s:
            depth += (c == '{') - (c == '}')
            if depth == 0: break
    body = masked[s:e]
    devs = re.findall(r'output_device\s*=\s*"([^"]+)"', body)
    rates = re.findall(r'output_rate\s*=\s*([^;]+);', body)
    mixers = re.findall(r'mixer_control_name\s*=', body)
    if devs == ['vbot_aec_media'] and [r.strip() for r in rates] == ['48000'] and not mixers:
        print("OK|/etc/shairport-sync.conf (alsa): output_device=vbot_aec_media, output_rate=48000, khong trung lap, khong du mixer_control_name.")
    else:
        print(f"ERR|/etc/shairport-sync.conf (alsa) chua chuan: output_device={devs}, output_rate={rates}, extra_mixer={len(mixers)}")
PY
)"
  IFS='|' read -r lvl msg <<< "$SH_CHECK"
  [[ "$lvl" == "OK" ]] && ok "$msg" || err "$msg"
else
  warn "Khong tim thay /etc/shairport-sync.conf (AirPlay chua cai)."
fi

BLUE_OVR="/etc/systemd/system/bluealsa-aplay.service.d/90-vbot-aec3.conf"
if [[ -f "$BLUE_OVR" ]] && grep -q -- '--pcm=vbot_aec_media' "$BLUE_OVR"; then
  ok "BlueALSA override ($BLUE_OVR) da tro dung --pcm=vbot_aec_media."
else
  err "Thieu hoac sai BlueALSA override tai $BLUE_OVR!"
fi

if [[ "$BOARD_TYPE" == "i2s" ]]; then
  KA_UNIT="/etc/systemd/system/i2s_dac_keepalive.service"
  if [[ -f "$KA_UNIT" ]] && grep -q "48000" "$KA_UNIT" && grep -q "S32_LE" "$KA_UNIT" && ! grep -q "44100" "$KA_UNIT" && ! grep -q "Nice=-19" "$KA_UNIT"; then
    if systemctl is-active --quiet i2s_dac_keepalive.service; then
      ok "i2s_dac_keepalive.service dang chay chuan (48000 Hz, S32_LE, Nice=0)."
    else
      err "i2s_dac_keepalive.service da cap nhat file nhung KHONG active!"
    fi
  else
    err "i2s_dac_keepalive.service chua duoc cap nhat sang 48000 Hz S32_LE Nice=0 (van con 44100 hoac Nice=-19)!"
  fi

  if systemctl is-active --quiet wm8960-soundcard.service 2>/dev/null; then
    err "Thua dich vu: wm8960-soundcard.service dang active tren image I2S!"
  fi

  MIC_VAL_NOW="$(amixer -c "$EXPECTED_CARD" cget name='VBot_DAC_Microphone' 2>/dev/null | awk -F= '/^[[:space:]]*: values=/{split($2,a,","); print a[1]}' || true)"
  if [[ -z "$MIC_VAL_NOW" ]]; then
    warn "Chua doc duoc VBot_DAC_Microphone (control softvol se duoc tao ngay khi mo mic lan dau)."
  elif [[ "$MIC_VAL_NOW" -ge 110 && "$MIC_VAL_NOW" -le 175 ]]; then
    ok "Mixer I2S VBot_DAC_Microphone=$MIC_VAL_NOW/255 nam trong vung toi uu chong clipping (khuyen nghi ~150)."
  else
    warn "Mixer I2S VBot_DAC_Microphone=$MIC_VAL_NOW/255 nam ngoai vung khuyen nghi 110..175 (>175 de bi clipping +30dB tren INMP441)!"
  fi
else
  if systemctl is-active --quiet i2s_dac_keepalive.service 2>/dev/null; then
    err "Thua dich vu: i2s_dac_keepalive.service dang active tren image WM8960!"
  fi
  if [[ -f /lib/systemd/system/wm8960-soundcard.service ]] && grep -q "rm /etc/asound.conf" /usr/bin/wm8960-soundcard 2>/dev/null; then
    if [[ -f /etc/systemd/system/wm8960-soundcard.service.d/90-vbot-preserve-config.conf && -f /var/lib/alsa/vbot-wm8960.state ]]; then
      ok "WM8960 co day du drop-in bao ve /etc/asound.conf va file luu mixer /var/lib/alsa/vbot-wm8960.state."
    else
      err "WM8960 thieu 90-vbot-preserve-config.conf hoac vbot-wm8960.state (reboot se mat /etc/asound.conf)!"
    fi
  fi
  WM_BST_L="$(amixer -c "$EXPECTED_CARD" sget 'Left Input Boost Mixer LINPUT1' 2>/dev/null | awk '/Mono:/{print $2; exit}' || true)"
  WM_CAP_L="$(amixer -c "$EXPECTED_CARD" sget 'Capture' 2>/dev/null | awk '/Front Left: Capture/{print $4; exit}' || true)"
  WM_HPF="$(amixer -c "$EXPECTED_CARD" sget 'ADC High Pass Filter' 2>/dev/null | grep -o '\[on\]\|\[off\]' | head -n 1 || true)"
  WM_ALC="$(amixer -c "$EXPECTED_CARD" sget 'ALC Function' 2>/dev/null | awk -F"'" '/Item0:/{print $2; exit}' || true)"
  if [[ "$WM_BST_L" == "2" && -n "$WM_CAP_L" && "$WM_CAP_L" -ge 28 && "$WM_CAP_L" -le 38 && "$WM_HPF" == "[on]" && "$WM_ALC" == "Off" ]]; then
    ok "Mixer WM8960 toi uu chong clipping (Boost=2 [+20dB], Capture=$WM_CAP_L, ADC HPF=[on], ALC=Off)."
  else
    warn "Mixer WM8960 hien tai (Boost=$WM_BST_L, Capture=$WM_CAP_L, HPF=$WM_HPF, ALC=$WM_ALC) chua o muc toi uu (Boost=2, Capture=35, HPF=[on], ALC=Off)."
  fi
fi

if systemctl is-active --quiet vbot-multiroom-bridge.service; then
  ok "vbot-multiroom-bridge.service dang hoat dong."
else
  err "vbot-multiroom-bridge.service KHONG hoat dong!"
fi

# --- [E] Kiem tra Config.json, khoa cu du thua va ma nguon VBot ---
CFG_AUDIT="$(python3 - "$VBOT_ROOT" "$BOARD_TYPE" << 'PY'
import json, sys
from pathlib import Path

root = Path(sys.argv[1])
board = sys.argv[2]
sys.path.insert(0, str(root))

cfg = json.loads((root / 'Config.json').read_text(encoding='utf-8-sig'))
smart = cfg.get('smart_config', {})

# Kiem tra khoa cu du thua
leftovers = []
if 'webrtcvad' in smart:
    leftovers.append('smart_config.webrtcvad')
if 'aec3' in smart:
    leftovers.append('smart_config.aec3 (legacy root)')
for stg in ('hotword', 'speak_to_text'):
    if 'gain' in smart.get('smart_wakeup', {}).get(stg, {}):
        leftovers.append(f'smart_wakeup.{stg}.gain')
ap = smart.get('audio_processing', {})
aec = ap.get('aec3', {})
for k in ('noise_suppression', 'bypass_legacy_filters'):
    if k in aec:
        leftovers.append(f'audio_processing.aec3.{k}')

if leftovers:
    print("ERR|Config.json con sot khoa cu du thua: " + ", ".join(leftovers))
else:
    print("OK|Config.json sach se, khong con khoa cu du thua (webrtcvad / legacy aec3 / software gain cu).")

import Lib_AEC3
Lib_AEC3._load_backend()
from _vbot_media_aec3.microphone import settings as mic_settings

try:
    s_aec = Lib_AEC3.settings(cfg)
    s_mic = mic_settings(cfg)
    print(f"OK|Hop le Lib_AEC3.settings & microphone.settings (output={s_aec['output']}, delay={s_aec['capture_delay_ms']}ms, ppm={s_aec['render_clock_ppm']}, NS={s_mic['noise_suppression']}, AGC={s_mic['agc']['target_level_dbfs']}/{s_mic['agc']['compression_gain_db']}).")
except Exception as e:
    print(f"ERR|Loi xac thuc cau hinh Python Lib_AEC3/microphone: {e}")
    sys.exit(0)

if board == 'i2s':
    if s_aec['render_clock_ppm'] != 0:
        print(f"WARN|I2S render_clock_ppm dang la {s_aec['render_clock_ppm']} (khuyen nghi 0 ppm vi chung clock I2S phan cung).")
    else:
        print("OK|I2S render_clock_ppm = 0 ppm (chinh xac).")
    if s_aec['output'] != 'linear':
        print(f"WARN|I2S aec3.output dang la '{s_aec['output']}' (khuyen nghi 'linear' de Porcupine bat nhay tu danh thuc 'E cu').")
    else:
        print("OK|I2S aec3.output = 'linear' (toi uu cho Porcupine).")
    if not (-30 <= s_aec['capture_delay_ms'] <= 5):
        print(f"WARN|I2S capture_delay_ms dang la {s_aec['capture_delay_ms']} ms (khuyen nghi -16 ms).")
    else:
        print(f"OK|I2S capture_delay_ms = {s_aec['capture_delay_ms']} ms (nam trong vung toi uu).")
else:
    if s_aec['render_clock_ppm'] == 0:
        print("OK|WM8960 render_clock_ppm = 0 ppm (chinh xac).")
    else:
        print(f"WARN|WM8960 render_clock_ppm dang la {s_aec['render_clock_ppm']} (khuyen nghi 0 ppm).")
    if s_aec['output'] == 'linear':
        print("OK|WM8960 aec3.output = 'linear' (toi uu cho Porcupine).")
    else:
        print(f"WARN|WM8960 aec3.output dang la '{s_aec['output']}' (khuyen nghi 'linear' de Porcupine bat nhay tu danh thuc 'E cu').")
    if -5 <= s_aec['capture_delay_ms'] <= 25:
        print(f"OK|WM8960 capture_delay_ms = {s_aec['capture_delay_ms']} ms (nam trong vung toi uu ~8 ms).")
    else:
        print(f"WARN|WM8960 capture_delay_ms dang la {s_aec['capture_delay_ms']} ms (khuyen nghi 8 ms).")

mr = cfg.get('media_player', {}).get('multiroom_audio', {})
if (s_aec['external_sources'] is True
        and mr.get('vlc_loopback_output_device') == 'vbot_aec_media'
        and mr.get('vlc_loopback_capture_device') == 'plughw:Loopback,1,1'):
    print("OK|Config.json da ket noi dung vbot_aec_media & plughw:Loopback,1,1 (external_sources=true).")
else:
    print("ERR|Config.json chua dong bo external_sources=true hoac vlc_loopback_output/capture_device!")

if cfg.get('media_player', {}).get('wake_up_in_media_player') is True:
    print("OK|media_player.wake_up_in_media_player = true (cho phep goi wakeword khi phat nhac).")
else:
    print("WARN|media_player.wake_up_in_media_player dang la false (VBot se bo qua wakeword khi dang phat nhac).")

try:
    import LAN_Calls
    s_pj = LAN_Calls.options(cfg.get('lan_calls', {}))
    exp_cd = 0 if board == 'i2s' else 24
    lo_cd, hi_cd = (-15, 15) if board == 'i2s' else (10, 35)
    if s_pj['render_clock_ppm'] != 0:
        print(f"WARN|lan_calls.pjsip.render_clock_ppm dang la {s_pj['render_clock_ppm']} (khuyen nghi 0 ppm).")
    elif not (lo_cd <= s_pj['capture_delay_ms'] <= hi_cd):
        print(f"WARN|lan_calls.pjsip.capture_delay_ms dang la {s_pj['capture_delay_ms']} ms (khuyen nghi {exp_cd} ms cho {board}).")
    elif s_pj['capture_gain'] > 2:
        print(f"WARN|lan_calls.pjsip.capture_gain dang la {s_pj['capture_gain']} (khuyen nghi 1 khi da bat WebRTC AGC).")
    else:
        print(f"OK|lan_calls.pjsip chuan ({board}: delay={s_pj['capture_delay_ms']}ms, ppm={s_pj['render_clock_ppm']}, gain={s_pj['capture_gain']}, codec={s_pj['codec']}).")
except Exception as e:
    print(f"ERR|Loi xac thuc cau hinh lan_calls.pjsip: {e}")
PY
)"
while IFS='|' read -r lvl msg; do
  [[ -z "$lvl" ]] && continue
  case "$lvl" in
    OK) ok "$msg" ;;
    WARN) warn "$msg" ;;
    ERR) err "$msg" ;;
    *) err "$lvl $msg" ;;
  esac
done <<< "$CFG_AUDIT"

if command -v php >/dev/null 2>&1; then
  if php -l "$VBOT_ROOT/html/includes/AEC3Settings.php" >/dev/null 2>&1 && php -l "$VBOT_ROOT/html/Config.php" >/dev/null 2>&1; then
    ok "Cu phap PHP WebUI (AEC3Settings.php & Config.php) hop le."
  else
    err "Loi cu phap PHP trong html/includes/AEC3Settings.php hoac html/Config.php!"
  fi
fi

# Khoi dong lai VBot_Offline.service neu truoc do dang chay
if [[ $VBOT_WAS_ACTIVE -eq 1 ]]; then
  info "Khoi dong lai VBot_Offline.service..."
  sudo -u "$PI_USER" XDG_RUNTIME_DIR="/run/user/$PI_UID" systemctl --user start VBot_Offline.service || warn "Khong the tu khoi dong lai VBot_Offline.service."
fi

# ------------------------------------------------------------------------------
# 4. Tong ket ket qua
# ------------------------------------------------------------------------------
step "4. TONG KET KET QUA ($BOARD_TYPE)"
printf "Mach nhan dien : %s (%s)\n" "$BOARD_TYPE" "$BOARD_LABEL"
printf "Ket qua kiem tra: ${C_GRN}%d OK${C_RST} | ${C_YLW}%d CANH BAO${C_RST} | ${C_RED}%d LOI${C_RST}\n" "$PASS_CNT" "$WARN_CNT" "$FAIL_CNT"

if [[ $FAIL_CNT -gt 0 ]]; then
  printf "${C_RED}=> CHUA DAT: Co %d muc loi can xu ly o tren!${C_RST}\n" "$FAIL_CNT"
  exit 1
else
  printf "${C_GRN}=> HOAN TAT: Tat ca cac muc he thong, thu vien .so, ALSA va Config deu CHINH XAC!${C_RST}\n"
  exit 0
fi
