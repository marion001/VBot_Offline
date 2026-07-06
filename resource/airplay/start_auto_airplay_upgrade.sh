#!/bin/bash
#
# VBot AirPlay - Automatic Shairport Sync Upgrade
# Author: Vũ Tuyển
#

set -euo pipefail

REPO="https://github.com/marion001/shairport-sync"
WORKDIR="$(mktemp -d /tmp/shairport-sync.XXXXXX)"

cleanup() {
    echo ""
    echo "Dọn dẹp thư mục tạm..."
    sudo rm -rf "$WORKDIR"
}

#trap cleanup EXIT

echo "============================================================"
echo "          VBot AirPlay - Upgrade Shairport Sync"
echo "============================================================"
echo

# Kiểm tra sudo
#if ! sudo -v; then
    #echo "Không thể xác thực quyền sudo."
    #exit 1
#fi

echo "[1/11] Dừng dịch vụ Shairport Sync..."
sudo systemctl stop shairport-sync || true

echo
echo "[2/11] Clone source mới: $REPO"
git clone "$REPO" "$WORKDIR"

cd "$WORKDIR"

echo
echo "[3/11] Chuẩn bị source..."
chmod +x verify-gitversion

if command -v dos2unix >/dev/null 2>&1; then
    dos2unix verify-gitversion >/dev/null 2>&1 || true
fi

echo
echo "[4/11] Dọn dẹp build cũ..."
make clean >/dev/null 2>&1 || true

echo
echo "[5/11] Chạy autoreconf..."
autoreconf -fi

echo
echo "[6/11] Configure..."

./configure \
    --with-mqtt-client \
    --sysconfdir=/etc \
    --with-alsa \
    --with-soxr \
    --with-avahi \
    --with-dbus-interface \
    --with-ssl=openssl \
    --with-systemd-startup \
    --with-airplay-2

echo
echo "[7/11] Build..."
make -j"$(nproc)"

echo
echo "[8/11] Install..."
sudo make install

echo
echo "[9/11] Reload systemd..."
sudo systemctl daemon-reload

echo
echo "[10/11] Khởi động lại Shairport Sync..."
sudo systemctl restart shairport-sync

echo
echo "[11/11] Kiểm tra trạng thái..."
sudo systemctl --no-pager --full status shairport-sync

echo
echo "============================================================"
echo "Phiên bản hiện tại:"
echo "============================================================"

shairport-sync -V

echo
echo "============================================================"
echo "NÂNG CẤP SHAIRPORT SYNC THÀNH CÔNG"
echo "============================================================"

cleanup

echo ""
echo "============================================================"
echo "Tự động khởi động lại toàn bộ hệ thống sau 2 giây..."
echo "Lệnh: sudo reboot"
echo "============================================================"

sleep 3

sudo reboot