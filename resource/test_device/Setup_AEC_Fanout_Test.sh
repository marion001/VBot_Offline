#!/usr/bin/env bash

set -u

ASOUND_FILE="/etc/asound.conf"
BACKUP_FILE="/etc/asound.conf.before-aec-fanout-test"
BEGIN_MARKER="# ==== VBOT AEC FANOUT TEST BEGIN ===="
END_MARKER="# ==== VBOT AEC FANOUT TEST END ===="
TEMP_FILE="$(mktemp /tmp/vbot-asound-aec.XXXXXX)"

cleanup() {
    rm -f -- "${TEMP_FILE}"
}

trap cleanup EXIT INT TERM

if [[ ! -f "${ASOUND_FILE}" ]]; then
    echo "[AEC Fanout] Không tìm thấy ${ASOUND_FILE}."
    exit 1
fi

if ! command -v aplay >/dev/null 2>&1; then
    echo "[AEC Fanout] Không tìm thấy aplay. Hãy cài gói alsa-utils."
    exit 1
fi

if ! aplay -l 2>/dev/null | grep -q "VBotAEC"; then
    echo "[AEC Fanout] Không tìm thấy card VBotAEC."
    echo "Hãy tải snd-aloop trước khi chạy script."
    exit 1
fi

if ! aplay -L 2>/dev/null | grep -qx "softvol"; then
    echo "[AEC Fanout] Không tìm thấy PCM softvol trong cấu hình ALSA hiện tại."
    exit 1
fi

if [[ ! -f "${BACKUP_FILE}" ]]; then
    sudo cp -a -- "${ASOUND_FILE}" "${BACKUP_FILE}"
    echo "[AEC Fanout] Đã sao lưu: ${BACKUP_FILE}"
else
    echo "[AEC Fanout] Giữ nguyên bản sao lưu đã có: ${BACKUP_FILE}"
fi

#Loại bỏ block thử nghiệm cũ để script có thể chạy lại an toàn.
awk -v begin="${BEGIN_MARKER}" -v end="${END_MARKER}" '
    $0 == begin { skipping = 1; next }
    $0 == end { skipping = 0; next }
    !skipping { print }
' "${ASOUND_FILE}" >"${TEMP_FILE}"

cat >>"${TEMP_FILE}" <<'EOF'

# ==== VBOT AEC FANOUT TEST BEGIN ====
#Đầu phát Loopback dùng làm tín hiệu loa tham chiếu.
pcm.vbot_aec_reference_hw {
    type hw
    card VBotAEC
    device 0
    subdevice 0
}

#Cho phép nhiều nguồn âm thanh cùng ghi vào nhánh reference.
pcm.vbot_aec_reference_dmixer {
    type dmix
    ipc_key 1025
    ipc_perm 0666
    slave {
        pcm "vbot_aec_reference_hw"
        period_time 0
        period_size 1024
        buffer_size 8192
        rate 48000
        format S32_LE
        channels 2
    }
}

#Ghép hai đầu ra stereo thành một PCM bốn kênh:
#kênh 0-1 đi tới loa, kênh 2-3 đi tới Loopback reference.
pcm.vbot_aec_fanout_multi {
    type multi

    slaves.speaker.pcm "softvol"
    slaves.speaker.channels 2

    slaves.reference.pcm "vbot_aec_reference_dmixer"
    slaves.reference.channels 2

    bindings.0.slave speaker
    bindings.0.channel 0
    bindings.1.slave speaker
    bindings.1.channel 1
    bindings.2.slave reference
    bindings.2.channel 0
    bindings.3.slave reference
    bindings.3.channel 1
}

#Nhân đôi stereo input tới cả loa và Loopback.
pcm.vbot_aec_fanout_test {
    type route
    slave {
        pcm "vbot_aec_fanout_multi"
        channels 4
    }
    ttable.0.0 1
    ttable.1.1 1
    ttable.0.2 1
    ttable.1.3 1
}
# ==== VBOT AEC FANOUT TEST END ====
EOF

sudo cp -- "${TEMP_FILE}" "${ASOUND_FILE}"

if ! ALSA_LIST="$(aplay -L 2>&1)"; then
    echo "[AEC Fanout] Cấu hình ALSA không hợp lệ:"
    echo "${ALSA_LIST}"
    sudo cp -a -- "${BACKUP_FILE}" "${ASOUND_FILE}"
    echo "[AEC Fanout] Đã tự động khôi phục ${ASOUND_FILE}."
    exit 1
fi

if ! grep -qx "vbot_aec_fanout_test" <<<"${ALSA_LIST}"; then
    echo "[AEC Fanout] ALSA không tạo được PCM vbot_aec_fanout_test."
    sudo cp -a -- "${BACKUP_FILE}" "${ASOUND_FILE}"
    echo "[AEC Fanout] Đã tự động khôi phục ${ASOUND_FILE}."
    exit 1
fi

echo "[AEC Fanout] Đã tạo PCM thử nghiệm: vbot_aec_fanout_test"
echo "[AEC Fanout] Thiết bị default và đường microphone chưa bị thay đổi."
echo
echo "Khôi phục thủ công nếu cần:"
echo "  sudo cp -a \"${BACKUP_FILE}\" \"${ASOUND_FILE}\""
