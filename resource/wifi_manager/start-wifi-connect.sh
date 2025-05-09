#!/usr/bin/env bash

# Đợi cho hệ thống khởi động hoàn tất
sleep 35

# Thời gian chờ để thiết bị kết nối vào AP
TIME_AP=120

# Thời gian chờ trước khi reboot nếu có thiết bị kết nối
TIME_AP_CONNECT=90

# Lưu lại tên wifi lần đầu
OLD_SSID_SAVE=$(iwgetid -r)

# Kiểm tra kết nối gateway
check_local_ping() {
    GATEWAY_IP=$(ip route | grep default | awk '{print $3}')
    if [ -z "$GATEWAY_IP" ]; then
        printf "Không tìm thấy GATEWAY_IP!\n"
        return 1
    fi
    ping -c 5 -W 3 "$GATEWAY_IP" > /dev/null 2>&1
    return $?
}

# Phát AP nếu không kết nối Wi-Fi được
start_ap() {
    printf "Tiến hành phát điểm truy cập Wifi: VBot_Assistant\n"
    wifi-connect -s VBot_Assistant -g 192.168.4.1 -d 192.168.4.2,192.168.4.5 &
    WIFI_CONNECT_PID=$!
    sleep "$TIME_AP"
    CONNECTED_DEVICES=$(ip neigh | grep '192.168.4' | grep REACHABLE)
    sudo kill "$WIFI_CONNECT_PID" 2>/dev/null
    if [ -n "$CONNECTED_DEVICES" ]; then
        printf "Đã có thiết bị kết nối. Đợi %d giây rồi reboot...\n" "$TIME_AP_CONNECT"
        sleep "$TIME_AP_CONNECT"
        sudo reboot
    else
        printf "Chưa có thiết bị nào kết nối vào điểm truy cập Wifi.\n"
        printf "Thử kết nối lại Wifi cũ hoặc các Wifi đã lưu...\n"
        nmcli radio wifi off
        sleep 2
        nmcli radio wifi on
        sleep 3
        nmcli device wifi rescan
        sleep 5
        if [ -n "$OLD_SSID_SAVE" ]; then
            printf "Thử kết nối lại Wi-Fi trước đó: %s\n" "$OLD_SSID_SAVE"
            if nmcli dev wifi connect "$OLD_SSID_SAVE"; then
                sleep 5
                if iwgetid -r > /dev/null; then
                    printf "Kết nối lại Wi-Fi cũ thành công: %s\n" "$OLD_SSID_SAVE"
                    return
                fi
            else
                printf "Không thể kết nối lại Wi-Fi cũ: %s\n" "$OLD_SSID_SAVE"
            fi
        fi
        # Nếu không có hoặc không kết nối được Wi-Fi cũ, thử các mạng đã lưu
        printf "Thử các Wi-Fi đã lưu trong hệ thống...\n"
        SAVED_NETWORKS=$(nmcli connection show | grep wifi | awk '{print $1}')
        for NET in $SAVED_NETWORKS; do
            printf "Thử kết nối Wi-Fi đã lưu: %s\n" "$NET"
            if nmcli connection up "$NET"; then
                sleep 5
                if iwgetid -r > /dev/null; then
                    printf "Kết nối thành công với: %s\n" "$NET"
                    return
                fi
            fi
            printf "Không thể kết nối với: %s\n" "$NET"
        done
        printf "Tất cả mạng Wi-Fi đã lưu đều không kết nối được. Phát lại AP...\n"
    fi
}

# Kiểm tra kết nối mạng
while true; do
    #Lấy lại IP và Wi-Fi mỗi vòng lặp
    OLD_SSID=$(iwgetid -r)
    OLD_IP=$(cat /home/pi/ip.txt 2>/dev/null)
    OLD_WIFI=$(cat /home/pi/wifi.txt 2>/dev/null)
    CURRENT_IP=$(hostname -I | awk '{print $1}')
    if iwgetid -r > /dev/null; then
        if check_local_ping; then
            #Nếu IP hoặc Wi-Fi thay đổi, lưu lại và gọi Python
            if [ "$OLD_IP" != "$CURRENT_IP" ] || [ "$OLD_WIFI" != "$OLD_SSID" ]; then
                printf "%s\n" "$CURRENT_IP" > /home/pi/ip.txt
                printf "%s\n" "$OLD_SSID" > /home/pi/wifi.txt
                sudo -u pi python3 /home/pi/_VBot_IP.py
            fi
            sudo systemctl start apache2
            systemctl --user enable --now VBot_Offline.service
            printf "Kiểm tra kết nối mạng thành công, Chờ 90 giây trước khi kiểm tra lại...\n"
            sleep 90
        else
            printf "Mất kết nối mạng nội bộ!\n"
            systemctl --user disable --now VBot_Offline.service
            sudo systemctl stop apache2
            start_ap
        fi
    else
        printf "Wi-Fi không được kết nối!\n"
        systemctl --user disable --now VBot_Offline.service
        sudo systemctl stop apache2
        start_ap
    fi
done
