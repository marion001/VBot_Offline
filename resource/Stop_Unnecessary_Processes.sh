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
systemctl disable cups.service cups-browsed.service cups.path saned.service saned@.service cups.socket
systemctl stop cups.service cups-browsed.service cups.path saned.service saned@.service cups.socket

# Bluetooth - nếu không dùng
#systemctl disable bluetooth.service hciuart.service
#systemctl stop bluetooth.service hciuart.service

# Modem / 3G/4G - nếu không dùng USB modem
systemctl disable ModemManager.service
systemctl stop ModemManager.service

# VNC - nếu không dùng remote GUI
systemctl disable vncserver-virtuald.service vncserver-x11-serviced.service
systemctl stop vncserver-virtuald.service vncserver-x11-serviced.service

# Test / Demo GPU - không cần thiết
systemctl disable glamor-test.service gldriver-test.service
systemctl stop glamor-test.service gldriver-test.service

# NFS / Network không cần thiết (trừ systemd-networkd)
systemctl disable nfs-common.service rpcbind.service rpc-statd.service rpc-statd-notify.service nfs-client.target
systemctl stop nfs-common.service rpcbind.service rpc-statd.service rpc-statd-notify.service nfs-client.target

# Timer / Cleanup mặc định không cần
systemctl disable man-db.timer
systemctl stop man-db.timer
# Lưu ý: giữ phpsessionclean.timer nếu dùng PHP

# Tắt cron mặc định trên hệ thống
#systemctl disable cron.service
#systemctl stop cron.service
sudo systemctl disable cron.service
sudo systemctl stop cron.service

echo "Đã tắt xong các service không cần thiết."
echo "Pi sẽ khởi động lại ngay bây giờ..."
sudo reboot