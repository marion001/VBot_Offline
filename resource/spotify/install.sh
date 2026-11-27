#!/bin/sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
if [ "$(id -u)" -ne 0 ]; then
  echo "Chạy bằng sudo: sudo sh resource/spotify/install.sh" >&2
  exit 1
fi

if ! command -v apt-get >/dev/null 2>&1; then
  echo "Trình cài này yêu cầu Debian/Raspberry Pi OS có apt-get." >&2
  exit 1
fi

systemctl stop vbot-spotify-connect.service 2>/dev/null || true

apt-get -o DPkg::Lock::Timeout=60 update
ASOUND_PKG="libasound2"
if apt-cache show libasound2t64 >/dev/null 2>&1; then
  ASOUND_PKG="libasound2t64"
fi
FLAC_PKG=""
for candidate in libflac12t64 libflac12 libflac8; do
  if apt-cache show "$candidate" >/dev/null 2>&1; then
    FLAC_PKG="$candidate"
    break
  fi
done

DEBIAN_FRONTEND=noninteractive apt-get -o DPkg::Lock::Timeout=60 install -y \
  ca-certificates curl tar "$ASOUND_PKG" libogg0 libvorbis0a libvorbisfile3 libmpg123-0 $FLAC_PKG

ARCH=$(uname -m)
DPKG_ARCH=$(dpkg --print-architecture 2>/dev/null || echo "")
if [ "$DPKG_ARCH" = "arm64" ] || [ "$ARCH" = "aarch64" ]; then
  ASSET_NAME="go-librespot_linux_arm64.tar.gz"
elif [ "$DPKG_ARCH" = "armhf" ] || [ "$ARCH" = "armv7l" ] || [ "$ARCH" = "armv6l" ]; then
  ASSET_NAME="go-librespot_linux_armv6_rpi.tar.gz"
elif [ "$DPKG_ARCH" = "amd64" ] || [ "$ARCH" = "x86_64" ]; then
  ASSET_NAME="go-librespot_linux_x86_64.tar.gz"
else
  echo "Kiến trúc CPU không được hỗ trợ tự động: uname=$ARCH dpkg=$DPKG_ARCH" >&2
  exit 1
fi

TMP_DIR=$(mktemp -d "${TMPDIR:-/tmp}/vbot-spotify-install.XXXXXX")
cleanup() {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT
trap 'exit 1' HUP INT TERM

FALLBACK_TAG="v0.10.7"
DOWNLOAD_URL=$(python3 - "$ASSET_NAME" "$FALLBACK_TAG" <<'PY'
import json
import sys
import urllib.request

asset_name = sys.argv[1]
fallback_tag = sys.argv[2]
url = f"https://github.com/devgianlu/go-librespot/releases/download/{fallback_tag}/{asset_name}"
try:
    req = urllib.request.Request(
        "https://api.github.com/repos/devgianlu/go-librespot/releases/latest",
        headers={"User-Agent": "VBot-Spotify-Installer"},
    )
    with urllib.request.urlopen(req, timeout=10) as resp:
        rel = json.loads(resp.read().decode("utf-8"))
    for item in rel.get("assets", []):
        if item.get("name") == asset_name and item.get("browser_download_url"):
            url = item["browser_download_url"]
            break
except Exception:
    pass
print(url)
PY
)

echo "Đang tải go-librespot ($ASSET_NAME) từ: $DOWNLOAD_URL"
curl -fL --connect-timeout 15 --max-time 180 "$DOWNLOAD_URL" -o "$TMP_DIR/$ASSET_NAME"
tar -xzf "$TMP_DIR/$ASSET_NAME" -C "$TMP_DIR"

if [ ! -f "$TMP_DIR/go-librespot" ]; then
  echo "Gói tải về không chứa tệp thực thi go-librespot." >&2
  exit 1
fi

install -D -m 0755 "$TMP_DIR/go-librespot" /usr/local/bin/go-librespot
mkdir -p "$ROOT/resource/spotify/runtime"
chmod 0777 "$ROOT/resource/spotify/runtime"

# Đồng bộ systemd service qua Sync_System_Services (hỗ trợ cả bản .py lẫn Cython .so)
python3 -c "import sys; sys.path.insert(0, sys.argv[sys.argv.index('--root')+1]); import Sync_System_Services as sync; sys.exit(sync.main())" \
  --root "$ROOT" --apply --install --only spotify_connect

echo "Cài đặt VBot Spotify Connect (go-librespot) hoàn tất thành công."
