#!/bin/bash

# Xóa tất cả các kết nối đã lưu (có thể mất kết nối mạng hiện tại)
for con in $(nmcli -t -f NAME connection show); do
    echo "Xóa kết nối: $con"
    nmcli connection delete "$con"
done

echo "Đã xóa tất cả các kết nối Wi-Fi đã lưu."

# Ghi đè nội dung vào file ip.txt
echo "0.0.0.0" > /home/pi/ip.txt
echo "Đã cập nhật /home/pi/ip.txt thành 0.0.0.0"

# Ghi đè nội dung vào file wifi.txt
echo "VBot_AP_Reset" > /home/pi/wifi.txt
echo "Đã cập nhật /home/pi/wifi.txt thành VBot_AP_Reset"
