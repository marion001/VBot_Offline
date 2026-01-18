#!/bin/bash

# Quick pre-installation check script
# Run this before the main installer to verify your system is ready

echo "╔═════════════════════════════════════════════════════╗"
echo "║   Kiểm tra trước khi cài đặt AirPlay 2              ║"
echo "╚═════════════════════════════════════════════════════╝"
echo

CHECKS_PASSED=0
CHECKS_FAILED=0
WARNINGS=0

check_pass() {
    echo "✓ $1"
    ((CHECKS_PASSED++))
}

check_fail() {
    echo "✗ $1"
    ((CHECKS_FAILED++))
}

check_warn() {
    echo "⚠ $1"
    ((WARNINGS++))
}

echo "Đang tiến hành kiểm tra hệ thống..."
echo

# Check 1: Not running as root
if [ "$EUID" -eq 0 ]; then
    check_fail "Đang chạy với quyền root - vui lòng chạy với tư cách người dùng thông thường."
else
    check_pass "Đang chạy với tư cách người dùng thông thường"
fi

# Check 2: Can sudo
if sudo -n true 2>/dev/null || sudo true 2>/dev/null; then
    check_pass "Có thể truy cập Sudo"
else
    check_fail "Không thể sử dụng sudo"
fi

# Check 3: Internet
TEST_HOSTS=("8.8.8.8" "1.1.1.1" "github.com")
INTERNET_OK=0
for host in "${TEST_HOSTS[@]}"; do
    # Try twice with longer timeout for slow Pi Zero networks
    ping -c 2 -W 5 -i 0.5 "$host" >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        INTERNET_OK=1
        break
    fi
    sleep 1
done

if [ $INTERNET_OK -eq 1 ]; then
    check_pass "Kết nối internet hoạt động"
else
    check_warn "Kiểm tra kết nối internet không cho kết quả rõ ràng (có thể do mạng chậm)"
    echo "  Xác minh thủ công: ping -c 3 8.8.8.8"
fi

# Check 4: Disk space
SPACE=$(df / | tail -1 | awk '{print $4}')
if [ "$SPACE" -gt 1000000 ]; then
    check_pass "Dung lượng ổ đĩa: $((SPACE / 1024)) MB có sẵn"
else
    check_fail "Không đủ dung lượng ổ đĩa: $((SPACE / 1024)) MB (Cần 1000+ MB)"
fi

# Check 5: Memory
MEM=$(free -m | awk '/^Mem:/{print $7}')
if [ "$MEM" -gt 100 ]; then
    check_pass "Bộ nhớ khả dụng: $MEM MB"
else
    check_warn "Bộ nhớ thấp: $MEM MB (có thể chậm)"
fi

# Check 6: Audio devices
echo
echo "Đã tìm thấy thiết bị âm thanh:"
if aplay -l 2>/dev/null | grep -q "card"; then
    aplay -l 2>/dev/null | grep "^card" | while read line; do
        echo "  → $line"
    done
    check_pass "Đã phát hiện thiết bị âm thanh"
else
    check_fail "Không tìm thấy thiết bị âm thanh nào."
fi

# Check 7: USB DAC
echo
USB_DAC=$(aplay -l 2>/dev/null | grep "^card" | grep -iv "bcm2835\|Headphones\|vc4-hdmi" || true)
if [ -n "$USB_DAC" ]; then
    echo "Thiết bị âm thanh ngoài:"
    echo "$USB_DAC" | while read line; do
        echo "  → $line"
    done
    check_pass "Đã tìm thấy USB DAC hoặc thiết bị âm thanh ngoài"
else
    check_warn "Không phát hiện thấy DAC USB (chỉ có âm thanh tích hợp)"
    echo "  Kết nối DAC USB để có chất lượng tốt hơn."
fi

# Check 8: Pi model
echo
if [ -f /proc/device-tree/model ]; then
    PI_MODEL=$(tr -d '\0' < /proc/device-tree/model)
    echo "Thiết bị: $PI_MODEL"
    if echo "$PI_MODEL" | grep -qE "Pi Zero W|Pi 1"; then
        check_warn "Mẫu Raspberry Pi cũ - có thể không hoạt động tốt với AirPlay 2."
    else
        check_pass "Mẫu Pi tương thích"
    fi
else
    check_warn "Không phải là Raspberry Pi"
fi

# Check 9: Wi-Fi
echo
if ip link show wlan0 &>/dev/null; then
    WIFI_STATE=$(ip link show wlan0 | grep -o "state [A-Z]*" | awk '{print $2}')
    if [ "$WIFI_STATE" = "UP" ]; then
        check_pass "Giao diện Wi-Fi đang hoạt động"
    else
        check_warn "Giao diện Wi-Fi bị tắt"
    fi
else
    check_warn "Không tìm thấy giao diện Wi-Fi (wlan0)"
fi

# Summary
echo
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Bản tóm tắt:"
echo "  ✓ Vượt Qua:   $CHECKS_PASSED"
echo "  ✗ Thất Bại:   $CHECKS_FAILED"
echo "  ⚠ Cảnh Báo: $WARNINGS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo

if [ $CHECKS_FAILED -eq 0 ]; then
    echo "✅ Hệ thống đã sẵn sàng để cài đặt!"
    echo
    echo "Để cài đặt, hãy chạy lệnh sau:"
    echo "  bash install_airplay_v3.sh"
    exit 0
else
    echo "❌ Vui lòng sửa các lỗi kiểm tra trước khi cài đặt."
    exit 1
fi
