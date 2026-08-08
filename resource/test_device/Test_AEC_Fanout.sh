#!/usr/bin/env bash

set -u

SOURCE_FILE="${1:-/tmp/vbot-mic-source.wav}"
REFERENCE_FILE="${2:-/tmp/vbot-aec-reference.wav}"
PLAYBACK_LOG="/tmp/vbot-aec-fanout-playback.log"
PLAYBACK_PID=""

cleanup() {
    if [[ -n "${PLAYBACK_PID}" ]] && kill -0 "${PLAYBACK_PID}" 2>/dev/null; then
        kill "${PLAYBACK_PID}" 2>/dev/null || true
        wait "${PLAYBACK_PID}" 2>/dev/null || true
    fi
}

trap cleanup EXIT INT TERM

if [[ ! -f "${SOURCE_FILE}" ]]; then
    echo "[AEC Fanout Test] Không tìm thấy file nguồn: ${SOURCE_FILE}"
    exit 1
fi

if ! aplay -L 2>/dev/null | grep -qx "vbot_aec_fanout_test"; then
    echo "[AEC Fanout Test] Không tìm thấy PCM vbot_aec_fanout_test."
    echo "Hãy chạy Setup_AEC_Fanout_Test.sh trước."
    exit 1
fi

rm -f -- "${REFERENCE_FILE}" "${PLAYBACK_LOG}"

echo "[AEC Fanout Test] Phát đồng thời ra loa và Loopback..."
timeout 7s aplay \
    -D vbot_aec_fanout_test \
    "${SOURCE_FILE}" \
    >"${PLAYBACK_LOG}" 2>&1 &
PLAYBACK_PID=$!

sleep 0.2

echo "[AEC Fanout Test] Thu tín hiệu reference từ hw:VBotAEC,1,0..."
timeout 5s arecord \
    -D hw:VBotAEC,1,0 \
    -f S32_LE \
    -r 48000 \
    -c 2 \
    -d 3 \
    "${REFERENCE_FILE}"
CAPTURE_STATUS=$?

wait "${PLAYBACK_PID}" 2>/dev/null
PLAYBACK_STATUS=$?
PLAYBACK_PID=""

echo
echo "[AEC Fanout Test] Log playback:"
cat "${PLAYBACK_LOG}" 2>/dev/null || true

if [[ ${CAPTURE_STATUS} -ne 0 ]]; then
    echo "[AEC Fanout Test] Thu reference thất bại, mã lỗi: ${CAPTURE_STATUS}"
    exit "${CAPTURE_STATUS}"
fi

if [[ ${PLAYBACK_STATUS} -ne 0 && ${PLAYBACK_STATUS} -ne 124 ]]; then
    echo "[AEC Fanout Test] Playback thất bại, mã lỗi: ${PLAYBACK_STATUS}"
    exit "${PLAYBACK_STATUS}"
fi

if [[ ! -s "${REFERENCE_FILE}" ]]; then
    echo "[AEC Fanout Test] File reference không tồn tại hoặc không có dữ liệu."
    exit 1
fi

echo
echo "[AEC Fanout Test] Đã tạo reference thành công:"
file "${REFERENCE_FILE}" 2>/dev/null || true
ls -lh "${REFERENCE_FILE}"
echo
echo "Nghe reference bằng:"
echo "  aplay -D default \"${REFERENCE_FILE}\""
