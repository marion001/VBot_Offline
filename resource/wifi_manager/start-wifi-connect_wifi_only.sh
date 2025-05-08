#!/usr/bin/env bash

# Đợi 35s cho thiết bị khởi động xong
sleep 35

# Thời gian chờ để kiểm tra thiết bị kết nối vào AP
TIME_AP=120

# Thời gian chờ trước khi reboot nếu có thiết bị kết nối vào AP
TIME_AP_CONNECT=90

# Lấy tên SSID hiện tại nếu có
OLD_SSID=$(iwgetid -r)

# Lưu địa chỉ IP hiện tại (nếu có)
OLD_IP=$(cat /home/pi/ip.txt 2>/dev/null)

# Lưu tên WiFi hiện tại (nếu có)
OLD_WIFI=$(cat /home/pi/wifi.txt 2>/dev/null)

# Kiểm tra kết nối WiFi
CURRENT_IP=$(hostname -I | awk '{print $1}')

# Lấy địa chỉ IP của gateway
GATEWAY_IP=$(ip route | grep default | awk '{print $3}')

# Hàm kiểm tra ping tới IP local (gateway)
check_local_ping() {
    if [ -z "$GATEWAY_IP" ]; then
        printf "Không tìm thấy GATEWAY_IP!\n"
        return 1
    fi
    ping -c 5 -W 3 "$GATEWAY_IP" > /dev/null 2>&1
    return $?
}

iwgetid -r
#Nếu có wifi đang kết nối
if [ $? -eq 0 ]; then
    #printf 'WiFi đã kết nối...\n'
    #Kiểm tra ping tới gateway nếu thành công
    if check_local_ping; then
        #printf 'Kết nối mạng nội bộ hoạt động (ping tới %s thành công)!\n' "$GATEWAY_IP"
        #Nghỉ (90 giây) trước khi tiếp tục lặp lại
        #sleep 90
	#Nếu ping thất bại
    else
        printf 'Mất kết nối mạng nội bộ (ping tới %s không thành công)!\n' "$GATEWAY_IP"
        # Xử lý khi không ping được tới gateway
        systemctl --user disable --now VBot_Offline.service
        sudo systemctl stop apache2
        printf 'Bắt đầu phát AP kết nối WiFi...\n'
        wifi-connect -s VBot_Assistant -g 192.168.4.1 -d 192.168.4.2,192.168.4.5 &
        WIFI_CONNECT_PID=$!
        # Chờ TIME_AP giây để xem có thiết bị nào kết nối hay không
        sleep "$TIME_AP"
        # Kiểm tra xem có thiết bị nào kết nối vào AP hay không
        CONNECTED_DEVICES=$(ip neigh | grep '192.168.4' | grep REACHABLE)
        if [ -z "$CONNECTED_DEVICES" ]; then
            printf 'Không có thiết bị nào được kết nối với AP, Đang tiến hành kết nối với WiFi trước đó...\n'
            # Khôi phục kết nối Wi-Fi cũ nếu có
            if [ -n "$OLD_SSID" ]; then
                nmcli dev wifi connect "$OLD_SSID"
            else
                printf 'Không tìm thấy WiFi trước đó để kết nối, Đang khởi động lại thiết bị...\n'
				#sleep 3
                sudo reboot
            fi
        else
            printf 'Có thiết bị được kết nối với AP, Khởi động lại sau %s giây...\n' "$TIME_AP_CONNECT"
            # Nếu có thiết bị kết nối vào AP, đợi TIME_AP_CONNECT giây trước khi reboot
            sleep "$TIME_AP_CONNECT"
            sudo reboot
        fi
        # Dừng wifi-connect
        sudo kill "$WIFI_CONNECT_PID"
    fi
#Nếu không có wifi đang kết nối
else
    printf 'WiFi không kết nối...\n'
    systemctl --user disable --now VBot_Offline.service
    sudo systemctl stop apache2
    printf 'Bắt đầu phát AP kết nối WiFi...\n'
    wifi-connect -s VBot_Assistant -g 192.168.4.1 -d 192.168.4.2,192.168.4.5 &
    WIFI_CONNECT_PID=$!
    # Chờ TIME_AP giây để xem có thiết bị nào kết nối hay không
    sleep "$TIME_AP"
    # Kiểm tra xem có thiết bị nào kết nối vào AP hay không
    CONNECTED_DEVICES=$(ip neigh | grep '192.168.4' | grep REACHABLE)
    if [ -z "$CONNECTED_DEVICES" ]; then
        printf 'Không có thiết bị nào được kết nối với AP, Đang tiến hành kết nối với WiFi trước đó...\n'
        # Khôi phục kết nối Wi-Fi cũ nếu có
        if [ -n "$OLD_SSID" ]; then
            nmcli dev wifi connect "$OLD_SSID"
        else
            printf 'Không tìm thấy WiFi trước đó để kết nối, Đang khởi động lại thiết bị...\n'
			#sleep 3
            sudo reboot
        fi
    else
        printf 'Có thiết bị được kết nối với AP, Khởi động lại sau %s giây...\n' "$TIME_AP_CONNECT"
        # Nếu có thiết bị kết nối vào AP, đợi TIME_AP_CONNECT giây trước khi reboot
        sleep "$TIME_AP_CONNECT"
        sudo reboot
    fi
    # Dừng wifi-connect
    sudo kill "$WIFI_CONNECT_PID"
fi