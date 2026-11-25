#!/bin/bash
# -------------------------
# Script tắt các service không cần thiết trên Raspberry Pi
# Chạy với quyền sudo
###############################################
# $:> chmod +x Stop_Unnecessary_Processes.sh
# $:> dos2unix Stop_Unnecessary_Processes.sh
# $:> sudo ./Stop_Unnecessary_Processes.sh
# $:> sudo reboot

echo "Đang tắt các .service không cần thiết..."

# In ấn / Scanner (CUPS) - nếu không dùng
systemctl disable --now cups.service cups-browsed.service cups.path saned.service saned@.service cups.socket 2>/dev/null || true

# Bluetooth - nếu không dùng
#systemctl disable --now bluetooth.service hciuart.service 2>/dev/null || true

# Modem / 3G/4G - nếu không dùng USB modem
systemctl disable --now ModemManager.service 2>/dev/null || true

# VNC - nếu không dùng remote GUI
systemctl disable --now vncserver-virtuald.service vncserver-x11-serviced.service 2>/dev/null || true

# Test / Demo GPU - không cần thiết
systemctl disable --now glamor-test.service gldriver-test.service 2>/dev/null || true

# NFS / Network không cần thiết (trừ systemd-networkd)
systemctl disable --now nfs-common.service rpcbind.service rpc-statd.service rpc-statd-notify.service nfs-client.target 2>/dev/null || true

# Timer / Cleanup mặc định không cần
systemctl disable --now man-db.timer 2>/dev/null || true
# Lưu ý: giữ phpsessionclean.timer (PHP) và cron.service (fake-hwclock lưu giờ cho Raspberry Pi không có RTC)
#systemctl disable --now cron.service 2>/dev/null || true

echo "Đã tắt xong các service không cần thiết."
if [ "${1:-}" = "--reboot" ]; then
    echo "Pi sẽ khởi động lại ngay bây giờ..."
    reboot
else
    echo "Chạy thêm tham số '--reboot' hoặc gõ 'sudo reboot' khi bạn sẵn sàng khởi động lại."
fi