#!/usr/bin/env bash

#Đợi 30s cho thiết bị khởi động xong
sleep 30
# Lưu tên SSID hiện tại nếu có
OLD_SSID=$(iwgetid -r)

iwgetid -r
if [ $? -eq 0 ]; then
    sudo systemctl start apache2
    systemctl --user enable --now VBot_Offline.service
    printf 'Wifi đã kết nối...\n'
else
    systemctl --user disable --now VBot_Offline.service
    sudo systemctl stop apache2
    printf 'Bắt đầu phát AP kết nối WiFi...\n'
    wifi-connect -s VBot_Assistant -g 192.168.4.1 -d 192.168.4.2,192.168.4.5 &
    WIFI_CONNECT_PID=$!
    # Chờ 120 giây để xem có thiết bị nào kết nối hay không
    sleep 120
    # Kiểm tra xem có thiết bị nào kết nối vào AP hay không (dùng lệnh arp-scan hoặc ip neigh)
    CONNECTED_DEVICES=$(ip neigh | grep '192.168.4' | grep REACHABLE)
    if [ -z "$CONNECTED_DEVICES" ]; then
        printf 'Không có thiết bị nào được kết nối với AP, Đang tiến hành kết nối với WiFi trước đó...\n'
        # Khôi phục kết nối Wi-Fi cũ nếu có
        if [ -n "$OLD_SSID" ]; then
            nmcli dev wifi connect "$OLD_SSID"
        else
            printf 'Không tìm thấy WiFi trước đó để kết nối, Đang khởi động lại thiết bị...\n'
            sudo reboot
        fi
    else
        printf 'Có thiết bị được kết nối với AP, Khởi động lại sau 60 giây...\n'
        
        #Nếu có thiết bị kết nối vào AP sẽ đợi 60 giây để cấu hình wifi trước khi reboot lại hệ thống
        sleep 90
        sudo reboot
    fi
    # Dừng wifi-connect
    sudo kill $WIFI_CONNECT_PID
fi