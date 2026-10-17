#!/bin/bash
#
# VBot AirPlay - Automatic Shairport Sync and NQPTP Upgrade
# Author: Vũ Tuyển
#

set -euo pipefail

REPO="https://github.com/marion001/shairport-sync"
NQPTP_REPO="https://github.com/mikebrady/nqptp.git"
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

echo "[1/13] Dừng dịch vụ Shairport Sync..."
sudo systemctl stop shairport-sync || true

echo
echo "[2/13] Clone source mới: $REPO"
git clone "$REPO" "$WORKDIR"

cd "$WORKDIR"

echo
echo "[3/13] Chuẩn bị source..."
chmod +x verify-gitversion

if command -v dos2unix >/dev/null 2>&1; then
    dos2unix verify-gitversion >/dev/null 2>&1 || true
fi

echo
echo "[4/13] Dọn dẹp build cũ..."
make clean >/dev/null 2>&1 || true

echo
echo "[5/13] Chạy autoreconf..."
autoreconf -fi

echo
echo "[6/13] Configure..."

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
echo "[7/13] Build..."
make -j"$(nproc)"

echo
echo "[8/13] Clone và build NQPTP mới nhất..."
git clone --depth 1 "$NQPTP_REPO" "$WORKDIR/nqptp"
cd "$WORKDIR/nqptp"
autoreconf -fi
./configure --with-systemd-startup
make -j"$(nproc)"

echo
echo "[9/13] Cài đặt và khởi động lại NQPTP..."
sudo systemctl stop nqptp || true
sudo make install
sudo systemctl daemon-reload
sudo systemctl enable nqptp
sudo systemctl restart nqptp
# Cho NQPTP thời gian khởi tạo shared memory trước khi chạy Shairport Sync.
sleep 3
sudo systemctl is-active --quiet nqptp
/usr/local/bin/nqptp -V
cd "$WORKDIR"

echo
echo "[10/13] Install..."
sudo make install

echo
echo "[11/13] Reload systemd..."
sudo systemctl daemon-reload

echo
echo "[12/13] Khởi động lại Shairport Sync..."
sudo systemctl restart shairport-sync

echo
echo "[13/13] Kiểm tra trạng thái..."
sleep 3
sudo systemctl is-active --quiet nqptp
sudo systemctl is-active --quiet shairport-sync
sudo systemctl --no-pager --full status nqptp shairport-sync

echo
echo "============================================================"
echo "Phiên bản hiện tại:"
echo "============================================================"

shairport-sync -V

echo
echo "============================================================"
echo "NÂNG CẤP NQPTP VÀ SHAIRPORT SYNC THÀNH CÔNG"
echo "============================================================"

cleanup

echo ""
echo "============================================================"
echo "Tự động khởi động lại toàn bộ hệ thống sau 3 giây..."
echo "Lệnh: sudo reboot"
echo "============================================================"

sleep 3

sudo reboot
