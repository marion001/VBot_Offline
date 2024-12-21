#!/usr/bin/env bash

#Đợi 30s cho thiết bị khởi động xong
sleep 30

#lấy tên SSID hiện tại nếu có
OLD_SSID=$(iwgetid -r)

# Lưu địa chỉ IP hiện tại (nếu có)
OLD_IP=$(cat /home/pi/ip.txt 2>/dev/null)

# Lưu tên Wifi hiện tại (nếu có)
OLD_WIFI=$(cat /home/pi/wifi.txt 2>/dev/null)

# Kiểm tra kết nối Wifi
CURRENT_IP=$(hostname -I | awk '{print $1}')

iwgetid -r
if [ $? -eq 0 ]; then
    printf 'Wifi đã kết nối...\n'
    # Nếu IP cũ khác với IP hiện tại, hoặc tên wifi khác với wifi hiện tại
    if [ "$OLD_IP" != "$CURRENT_IP" ] || [ "$OLD_WIFI" != "$OLD_SSID" ]; then
        # Lấy và ghi địa chỉ IP vào file
        echo $CURRENT_IP > /home/pi/ip.txt
		# Lấy và tên wifi vào file
        echo $OLD_SSID > /home/pi/wifi.txt
        sudo -u pi python3 /home/pi/_VBot_IP.py
    fi
    # In ra địa chỉ IP hiện tại
    printf "Địa chỉ IP của bạn là: $CURRENT_IP\n"
    # Khởi động Apache
    sudo systemctl start apache2
    # Kích hoạt và chạy dịch vụ VBot_Offline
    systemctl --user enable --now VBot_Offline.service
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