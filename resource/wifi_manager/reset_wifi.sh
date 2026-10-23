#!/bin/bash
set -euo pipefail
install -d -m 700 /var/lib/vbot-network
exec 9>/var/lib/vbot-network/lock
flock -n 9 || { echo 'Một thao tác mạng đang chạy; hãy thử lại sau.' >&2; exit 1; }
if python3 -c 'import glob,json
for p in glob.glob("/var/lib/vbot-network/*.json"):
 try:
  if json.load(open(p)).get("stage") in ("scheduled","applying","awaiting_confirm"): raise SystemExit(0)
 except (OSError,ValueError): pass
raise SystemExit(1)' ; then
    echo 'Đang thử cấu hình mạng; hãy xác nhận hoặc chờ khôi phục trước khi đặt lại Wi-Fi.' >&2
    exit 1
fi

# Sao lưu riêng tư trước khi xóa; file hồ sơ có thể chứa mật khẩu Wi-Fi.
umask 077
backup="/home/pi/VBot_AEC3/wifi_backups/$(date +%Y%m%d_%H%M%S)"
install -d -m 700 "$backup"
cp -a /etc/NetworkManager/system-connections "$backup/"
printf 'Đã sao lưu hồ sơ mạng tại: %s\n' "$backup"

# Chỉ xóa Wi-Fi theo UUID; giữ PAN, Ethernet, VPN và tên có dấu cách.
uuids=$(nmcli -t -f UUID,TYPE connection show | awk -F: '$2=="wifi" || $2=="802-11-wireless" {print $1}')
for uuid in $uuids; do
    nmcli connection delete uuid "$uuid" || exit 1
done

echo "Đã xóa tất cả các kết nối Wi-Fi đã lưu."

# Ghi đè nội dung vào file ip.txt
echo "0.0.0.0" > /home/pi/ip.txt
echo "Đã cập nhật /home/pi/ip.txt thành 0.0.0.0"

# Ghi đè nội dung vào file wifi.txt
echo "VBot_AP_Reset" > /home/pi/wifi.txt
echo "Đã cập nhật /home/pi/wifi.txt thành VBot_AP_Reset"
