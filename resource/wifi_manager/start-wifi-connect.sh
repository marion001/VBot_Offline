#!/usr/bin/env bash
# VBOT_PAN_WIFI_RECOVERY_V5
export LC_ALL=C.UTF-8
sleep 5
# Recheck saved Wi-Fi while AP has no clients; this is NOT an AP lifetime.
TIME_AP=60
SSID="VBot_Assistant"
FAILED_WIFI_UUID=
FAILED_WIFI_UNTIL=0
APACHE_STOPPED=0
WIFI_LOGGED=0

uptime_seconds() {
    awk '{printf "%d\n", $1}' /proc/uptime
}

nmcli_query() {
    timeout 5 nmcli "$@"
}

check_local_ping() {
    ip -4 route show default | awk '
        { gateway=""; iface="";
          for(i=1;i<=NF;i++) {
            if($i=="via") gateway=$(i+1);
            if($i=="dev") iface=$(i+1);
          }
          if(gateway!="" && iface!="") print iface, gateway;
        }' | (while read -r GATEWAY_IF GATEWAY_IP; do
        if ping -I "$GATEWAY_IF" -c 2 -W 3 "$GATEWAY_IP" >/dev/null 2>&1; then exit 0; fi
    done
    exit 1)
}

pan_has_address() {
    # Diagnostic only: PAN must not suppress the Wi-Fi setup AP.
    ip -4 route show default | awk '{for(i=1;i<=NF;i++) if($i=="dev" && $(i+1) ~ /^bnep[0-9]+$/) found=1} END {exit !found}'
}

wifi_station_connected() {
    nmcli_query -e no -t -f UUID,TYPE,DEVICE connection show --active | (
        while IFS=: read -r uuid kind device; do
            [ "$kind" = wifi ] || [ "$kind" = 802-11-wireless ] || continue
            [ "$device" = wlan0 ] || continue
            mode=$(nmcli_query -g 802-11-wireless.mode connection show uuid "$uuid") || continue
            [ "$mode" = ap ] && continue
            if ip -4 -o addr show dev wlan0 scope global | grep -q ' inet '; then exit 0; fi
        done
        exit 1
    )
}

retry_saved_wifi() {
    wifi_station_connected && return 0
    uuid=$(find_saved_wifi) || return 1
    try_saved_uuid "$uuid"
}

find_saved_wifi() {
    timeout 6 nmcli --wait 5 device wifi rescan ifname wlan0 >/dev/null 2>&1 || true
    available=$(nmcli_query -e no -t -f SSID device wifi list ifname wlan0 --rescan no) || return 1
    deadline=$(($(uptime_seconds) + 10))
    for uuid in $(nmcli_query -t -f UUID,TYPE connection show | awk -F: '$2=="wifi" || $2=="802-11-wireless" {print $1}'); do
        [ "$(uptime_seconds)" -lt "$deadline" ] || break
        if [ "$uuid" = "$FAILED_WIFI_UUID" ] && [ "$(uptime_seconds)" -lt "$FAILED_WIFI_UNTIL" ]; then continue; fi
        auto=$(nmcli_query -g connection.autoconnect connection show uuid "$uuid")
        [ "$auto" = yes ] || continue
        mode=$(nmcli_query -g 802-11-wireless.mode connection show uuid "$uuid") || continue
        [ "$mode" = ap ] && continue
        name=$(nmcli_query -e no -g 802-11-wireless.ssid connection show uuid "$uuid")
        [ -n "$name" ] || continue
        printf '%s\n' "$available" | grep -Fxq -- "$name" || continue
        printf '%s\n' "$uuid"
        return 0
    done
    return 1
}

try_saved_uuid() {
    [ -n "$1" ] || return 1
    printf 'Thử một hồ sơ Wi-Fi đã lưu (tối đa 12 giây): %s\n' "$1"
    if timeout 15 nmcli --wait 12 connection up uuid "$1" ifname wlan0 >/dev/null 2>&1 && wifi_station_connected; then
        FAILED_WIFI_UUID=
        return 0
    fi
    FAILED_WIFI_UUID=$1
    FAILED_WIFI_UNTIL=$(($(uptime_seconds) + 300))
    return 1
}

ap_has_clients() {
    # If client information is unavailable, keep the portal open.
    command -v iw >/dev/null 2>&1 || return 0
    clients=$(timeout 3 iw dev wlan0 station dump 2>/dev/null) || return 0
    printf '%s\n' "$clients" | grep -q '^Station '
}

setup_ap_active() {
    nmcli_query -e no -t -f UUID,TYPE,DEVICE connection show --active | (
        while IFS=: read -r uuid kind device; do
            [ "$device" = wlan0 ] || continue
            [ "$kind" = wifi ] || [ "$kind" = 802-11-wireless ] || continue
            mode=$(nmcli_query -g 802-11-wireless.mode connection show uuid "$uuid")
            name=$(nmcli_query -e no -g 802-11-wireless.ssid connection show uuid "$uuid")
            if [ "$mode" = ap ] && [ "$name" = "$SSID" ]; then exit 0; fi
        done
        exit 1
    )
}

#Phát AP khi không có Wi-Fi router, kể cả khi PAN có Internet; không reboot.
cleanup_ap() {
    if [ -n "${WIFI_CONNECT_PID:-}" ]; then
        kill "$WIFI_CONNECT_PID" 2>/dev/null || true
        grace=0
        while kill -0 "$WIFI_CONNECT_PID" 2>/dev/null && [ "$grace" -lt 5 ]; do
            sleep 1
            grace=$((grace + 1))
        done
        if kill -0 "$WIFI_CONNECT_PID" 2>/dev/null; then
            printf 'Portal chưa thoát; dừng tiến trình AP sau 5 giây chờ.\n'
            pkill -TERM -P "$WIFI_CONNECT_PID" 2>/dev/null || true
            kill -KILL "$WIFI_CONNECT_PID" 2>/dev/null || true
        fi
        wait "$WIFI_CONNECT_PID" 2>/dev/null || true
        WIFI_CONNECT_PID=
        for uuid in $(nmcli_query -t -f UUID,TYPE,DEVICE connection show --active | awk -F: '($2=="wifi" || $2=="802-11-wireless") && $3=="wlan0" {print $1}'); do
            mode=$(nmcli_query -g 802-11-wireless.mode connection show uuid "$uuid")
            name=$(nmcli_query -e no -g 802-11-wireless.ssid connection show uuid "$uuid")
            if [ "$mode" = ap ] && [ "$name" = "$SSID" ]; then
                timeout 6 nmcli --wait 5 connection down uuid "$uuid" >/dev/null 2>&1 || true
            fi
        done
    fi
    if [ "$APACHE_STOPPED" = 1 ]; then
        timeout 10 systemctl start apache2 || true
        APACHE_STOPPED=0
    fi
}
trap cleanup_ap EXIT
trap 'exit 0' TERM INT

start_ap() {
    if wifi_station_connected; then return; fi
    if ! command -v wifi-connect >/dev/null 2>&1; then
        printf 'Thiếu chương trình wifi-connect; chưa thể phát AP.\n'
        sleep 10
        return
    fi
    APACHE_STOPPED=1
    if ! timeout 10 systemctl stop apache2; then
        printf 'Không dừng được Apache2; thử lại sau, không reboot.\n'
        cleanup_ap
        sleep 5
        return
    fi
    printf 'Tiến hành phát điểm truy cập Wifi: %s\n' "$SSID"
    if [ -d /usr/local/share/wifi-connect/ui ]; then
        wifi-connect -i wlan0 -u /usr/local/share/wifi-connect/ui -s "$SSID" -g 192.168.4.1 -d 192.168.4.2,192.168.4.5 &
    else
        wifi-connect -i wlan0 -s "$SSID" -g 192.168.4.1 -d 192.168.4.2,192.168.4.5 &
    fi
    WIFI_CONNECT_PID=$!
    count=0
    seen_ap=0
    missing_ap=0
    started=$(uptime_seconds)
    while kill -0 "$WIFI_CONNECT_PID" 2>/dev/null; do
        sleep 5
        count=$((count + 5))
        if wifi_station_connected; then
            systemctl start --no-block vbot-pan-policy.service >/dev/null 2>&1 || true
            break
        fi
        if [ "$seen_ap" = 0 ]; then
            if setup_ap_active; then
                seen_ap=1
                printf 'AP đã hoạt động: %s — mở http://192.168.4.1 để cấu hình.\n' "$SSID"
            elif [ "$(uptime_seconds)" -ge "$((started + 45))" ]; then
                printf 'Portal chưa phát AP sau 45 giây; khởi động lại riêng portal.\n'
                break
            fi
        elif setup_ap_active; then
            missing_ap=0
        else
            missing_ap=$((missing_ap + 5))
            if [ "$missing_ap" -ge 60 ]; then
                printf 'Tiến trình portal còn chạy nhưng AP đã mất 60 giây; thử tạo AP lại.\n'
                break
            fi
        fi
        if [ "$count" -ge "$TIME_AP" ]; then
            count=0
            if ! ap_has_clients; then
                candidate=$(find_saved_wifi) || candidate=
                if [ -n "$candidate" ]; then
                    cleanup_ap
                    try_saved_uuid "$candidate" || true
                    break
                fi
            fi
        fi
    done
    cleanup_ap
    sleep 3
}

while true; do
    # Do not start a setup AP during a timed trial of a new IP/DNS config.
    if python3 -c 'import glob,json,time,subprocess,pathlib; pending=False
boot=pathlib.Path("/proc/sys/kernel/random/boot_id").read_text().strip()
for path in glob.glob("/var/lib/vbot-network/*.json"):
 try:
  data=json.load(open(path))
  if data.get("stage") not in ("scheduled","applying","awaiting_confirm"): continue
  expired=time.time()>=data.get("deadline",0)+45 or data.get("boot_id")!=boot
  helper=data.get("helper","")
  if expired and pathlib.Path(helper).is_file() and helper.endswith("/resource/wifi_manager/network_manager.py"):
   result=subprocess.run(["python3",helper,"rollback","--uuid="+pathlib.Path(path).stem,"--token="+data.get("token","")],timeout=75)
   if result.returncode: print("Khôi phục thử cấu hình thất bại; cho phép AP dự phòng.")
  elif not expired: pending=True
 except subprocess.TimeoutExpired: print("Khôi phục thử cấu hình hết thời gian; cho phép AP dự phòng.")
 except (OSError,ValueError): pass
raise SystemExit(0 if pending else 1)' ; then
        printf 'Đang thử cấu hình Wi-Fi mới; chưa chuyển sang AP.\n'
        sleep 10
        continue
    fi
    retry_saved_wifi || true
    if wifi_station_connected; then
        CURRENT_IP=$(ip -4 -o addr show dev wlan0 scope global | awk '{split($4,a,"/"); print a[1]; exit}')
        CURRENT_SSID=$(iwgetid -r)
        OLD_IP=$(cat /home/pi/ip.txt 2>/dev/null)
        OLD_WIFI=$(cat /home/pi/wifi.txt 2>/dev/null)
        if [ -n "$CURRENT_SSID" ] && [ -n "$CURRENT_IP" ] && { [ "$OLD_IP" != "$CURRENT_IP" ] || [ "$OLD_WIFI" != "$CURRENT_SSID" ]; }; then
            printf '%s\n' "$CURRENT_IP" >/home/pi/ip.txt
            printf '%s\n' "$CURRENT_SSID" >/home/pi/wifi.txt
            sudo -u pi python3 /home/pi/_VBot_IP.py
        fi
        if ! systemctl is-active --quiet apache2; then
            systemctl start apache2 || printf 'Không khởi động được Apache2; không reboot.\n'
        fi
        systemctl start --no-block vbot-pan-policy.service >/dev/null 2>&1 || true
        if [ "$WIFI_LOGGED" = 0 ]; then
            printf 'Wi-Fi router đang kết nối; kiểm tra mỗi 10 giây. Auto VBot sẽ ưu tiên Wi-Fi.\n'
            WIFI_LOGGED=1
        fi
        sleep 10
    else
        WIFI_LOGGED=0
        printf 'Chưa có Wi-Fi; phát AP cấu hình và giữ Bluetooth/PAN hoạt động.\n'
        start_ap
    fi
done
