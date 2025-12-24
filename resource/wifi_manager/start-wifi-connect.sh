#!/usr/bin/env bash

#Đợi cho hệ thống khởi động hoàn tất
sleep 35

#Thời gian chờ để thiết bị kết nối vào AP
TIME_AP=120

#Thời gian chờ trước khi reboot nếu có thiết bị kết nối
TIME_AP_CONNECT=90

#Lưu lại tên wifi lần đầu
OLD_SSID_SAVE=$(iwgetid -r)

SSID="VBot_Assistant"

#Kiểm tra kết nối gateway
check_local_ping() {
    GATEWAY_IP=$(ip route | grep default | awk '{print $3}')
    if [ -z "$GATEWAY_IP" ]; then
        printf "Không tìm thấy GATEWAY_IP!\n"
        return 1
    fi
    ping -c 5 -W 3 "$GATEWAY_IP" > /dev/null 2>&1
    return $?
}

#Phát AP nếu không kết nối Wi-Fi được
start_ap() {
	#systemctl --user disable --now VBot_Offline.service
	#Dừng WebUI Apache2, để tiến hành Tạo Webui Cho AP
	if sudo systemctl is-active --quiet apache2; then
		printf "Đang dừng WebUI Apache2 để cấu hình phát điểm truy cập Wifi.\n"
		for i in {1..3}; do
			printf "Thử dừng Apache2 lần $i...\n"
			if sudo systemctl stop apache2; then
				printf "Đã dừng WebUI Apache2 thành công ở lần $i.\n"
				sleep 3
				break
			else
				printf "Không thể dừng WebUI Apache2 ở lần $i.\n"
				sleep 5
			fi
		done
		#Nếu vẫn đang chạy sau 3 lần thử thì reboot
		if sudo systemctl is-active --quiet apache2; then
			printf "Apache2 vẫn đang chạy sau 3 lần thử. Đang khởi động lại hệ thống!\n"
			sleep 2
			sudo reboot
		fi
	fi
    printf "Tiến hành phát điểm truy cập Wifi: $SSID\n"
    wifi-connect -s "$SSID" -g 192.168.4.1 -d 192.168.4.2,192.168.4.5 &
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
                    printf "Kết nối lại Wifi cũ thành công: %s\n" "$OLD_SSID_SAVE"
                    return
                fi
            else
                printf "Không thể kết nối lại Wifi cũ: %s\n" "$OLD_SSID_SAVE"
            fi
        fi
        #Nếu không có hoặc không kết nối được Wi-Fi cũ, thử các mạng đã lưu
        printf "Thử các Wi-Fi đã lưu trong hệ thống...\n"
        SAVED_NETWORKS=$(nmcli connection show | grep wifi | awk '{print $1}')
        for NET in $SAVED_NETWORKS; do
            printf "Thử kết nối Wifi đã lưu: %s\n" "$NET"
            if nmcli connection up "$NET"; then
                sleep 5
                if iwgetid -r > /dev/null; then
                    printf "Kết nối thành công với Wifi: %s\n" "$NET"
                    return
                fi
            fi
            printf "Không thể kết nối với Wifi: %s\n" "$NET"
        done
        printf "Tất cả mạng Wifi đã lưu đều không kết nối được. Phát lại điểm truy cập Wifi...\n"
    fi
}

#Kiểm tra kết nối mạng
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
				#Lọc IP không phải localhost và không phải hostname, ip phải ở dạng số
				if [ "$CURRENT_IP" != "127.0.0.1" ] && [ "$CURRENT_IP" != "$(hostname)" ] && [ "$CURRENT_IP" != "$(hostname -f)" ]; then
					#Kiểm tra IP chỉ chứa số và dấu chấm
					if echo "$CURRENT_IP" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$'; then
						printf "%s\n" "$CURRENT_IP" > /home/pi/ip.txt
						printf "%s\n" "$OLD_SSID" > /home/pi/wifi.txt
						sudo -u pi python3 /home/pi/_VBot_IP.py
					else
						printf "Địa chỉ IP không hợp lệ: $CURRENT_IP\n"
					fi
				else
					printf "IP localhost hoặc hostname. Không lưu: $CURRENT_IP\n"
				fi
			fi
			#Kiểm tra Apache2 để chạy
			if ! sudo systemctl is-active --quiet apache2; then
				printf "WebUI Apache2 chưa chạy, thử khởi động (tối đa 3 lần)...\n"
				for i in {1..3}; do
					printf "Thử lần $i...\n"
					if sudo systemctl start apache2; then
						printf "Khởi động WebUI Apache2 thành công ở lần $i.\n"
						break
					else
						printf "Không thể khởi động WebUI Apache2 ở lần $i.\n"
						sleep 5
					fi
				done
				#Nếu vẫn không chạy sau 3 lần sẽ reboot
				if ! sudo systemctl is-active --quiet apache2; then
					printf "Apache2 vẫn không chạy sau 3 lần thử. Đang khởi động lại hệ thống...\n"
					sleep 2
					sudo reboot
				fi
			fi
			#systemctl --user enable --now VBot_Offline.service;
            printf "Kiểm tra kết nối mạng thành công, Chờ 90 giây trước khi kiểm tra lại...\n"
            sleep 90
        else
            printf "Mất kết nối mạng nội bộ!\n"
            #systemctl --user disable --now VBot_Offline.service
            start_ap
        fi
    else
        printf "Wi-Fi không được kết nối!\n"
        start_ap
    fi
done
