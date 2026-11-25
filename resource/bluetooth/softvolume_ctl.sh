#!/bin/bash
# Code By: Vũ Tuyển
# Script hỗ trợ bật/tắt SoftVolume cho BlueALSA

PATH_PCM=$1
ACTION=$2

if [ -z "$PATH_PCM" ] || [ -z "$ACTION" ]; then
    echo "Sử dụng: $0 <bluealsa_path> <on|off>"
    exit 1
fi

if [ "$ACTION" == "on" ]; then
    VALUE="true"
elif [ "$ACTION" == "off" ]; then
    VALUE="false"
else
    echo "Hành động không hợp lệ: $ACTION"
    exit 1
fi

# Cơ chế thử lại để đợi dịch vụ BlueALSA và PCM Interface sẵn sàng
MAX_RETRIES=15
RETRY_COUNT=0
SUCCESS=false

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    # Thử thực thi lệnh busctl set-property
    if busctl --timeout=2s set-property org.bluealsa "$PATH_PCM" org.bluealsa.PCM1 SoftVolume b "$VALUE" 2>/dev/null; then
        echo "Thành công: SoftVolume $ACTION cho $PATH_PCM (Thử lần $((RETRY_COUNT+1)))"

        VERIFY=$(busctl --timeout=2s get-property org.bluealsa "$PATH_PCM" org.bluealsa.PCM1 SoftVolume 2>/dev/null)
        if [[ "$VERIFY" != *"$VALUE"* ]]; then
            sleep 0.2
            VERIFY=$(busctl --timeout=2s get-property org.bluealsa "$PATH_PCM" org.bluealsa.PCM1 SoftVolume 2>/dev/null)
        fi
        if [[ "$VERIFY" == *"$VALUE"* ]]; then
            echo "Xác nhận kiểm tra: SoftVolume hiện tại là $VALUE ($VERIFY)"
            SUCCESS=true
        else
            echo "Cảnh báo: Kiểm tra lại thấy giá trị không khớp ($VERIFY). Có thể cần thêm thời gian."
            SUCCESS=true # Vẫn coi là thành công vì lệnh SET đã được chấp nhận
        fi
        break
    fi

    RETRY_COUNT=$((RETRY_COUNT+1))
    sleep 0.3
done

if [ "$SUCCESS" = true ]; then
    exit 0
else
    # Lần cuối không redirect để Python bắt được lỗi nếu vẫn thất bại
    busctl --timeout=2s set-property org.bluealsa "$PATH_PCM" org.bluealsa.PCM1 SoftVolume b "$VALUE"
    exit 1
fi
