#!/bin/sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
if [ "$(id -u)" -ne 0 ]; then
  echo "Chạy bằng sudo: sudo sh resource/dlna_renderer/install.sh" >&2
  exit 1
fi

if ! command -v apt-get >/dev/null 2>&1; then
  echo "Trình cài này yêu cầu Debian/Raspberry Pi OS có apt-get." >&2
  exit 1
fi

systemctl stop vbot-dlna-renderer.service 2>/dev/null || true

apt-get -o DPkg::Lock::Timeout=60 update
DEBIAN_FRONTEND=noninteractive apt-get -o DPkg::Lock::Timeout=60 install -y \
  autoconf automake libtool pkg-config make gcc git \
  libglib2.0-dev libupnp-dev libgstreamer1.0-dev \
  gstreamer1.0-alsa gstreamer1.0-plugins-base \
  gstreamer1.0-plugins-good dbus-bin

BUILD_ROOT=$(mktemp -d "${TMPDIR:-/tmp}/vbot-gmrender-resurrect.XXXXXX")
cleanup() {
  rm -rf "$BUILD_ROOT"
}
trap cleanup EXIT
trap 'exit 1' HUP INT TERM

SOURCE="$BUILD_ROOT/source"
if ! git clone --depth 1 --branch master \
  https://github.com/marion001/gmrender-resurrect.git "$SOURCE"; then
  echo "Không thể tải mã nguồn từ https://github.com/marion001/gmrender-resurrect.git" >&2
  exit 1
fi

if [ ! -f "$SOURCE/configure.ac" ] || [ ! -f "$SOURCE/src/mpris.c" ] \
  || ! grep -q 'upnp_transport_mpris_next' "$SOURCE/src/upnp_transport.c" \
  || ! grep -q 'mpris_init' "$SOURCE/src/main.c" \
  || ! grep -q 'upnp_control_mpris_set_volume_level' "$SOURCE/src/upnp_control.c" \
  || ! grep -q 'VolumeLevel' "$SOURCE/src/mpris.c" \
  || ! grep -q 'SetVolumeLevel' "$SOURCE/src/mpris.c"; then
  echo "Repo GitHub chưa có mã gmrender-resurrect 0.3.1 đã tích hợp MPRIS, Next/Previous và đồng bộ volume DLNA. Hãy push bản đã cập nhật rồi chạy lại." >&2
  exit 1
fi
cd "$SOURCE"
find "$SOURCE" -type f \( -name '*.ac' -o -name '*.am' -o -name '*.in' -o -name '*.sh' \) \
  -exec sed -i 's/\r$//' {} +
sh ./autogen.sh
sh ./configure --prefix=/usr/local --with-gstreamer
make -j"$(getconf _NPROCESSORS_ONLN 2>/dev/null || echo 2)"
make install

install -D -m 0644 "$ROOT/resource/dlna_renderer/vbot-mpris.conf" \
  /etc/dbus-1/system.d/vbot-mpris.conf
if command -v dbus-send >/dev/null 2>&1; then
  dbus-send --system --type=method_call --print-reply \
    --dest=org.freedesktop.DBus / org.freedesktop.DBus.ReloadConfig >/dev/null || true
fi

# Import the module so this works when it is distributed as a Cython .so.
python3 -c "import sys; sys.path.insert(0, sys.argv[sys.argv.index('--root')+1]); import Sync_System_Services as sync; sys.exit(sync.main())" \
  --root "$ROOT" --apply --install --only dlna_renderer

echo "gmrender-resurrect build and installation completed successfully."
