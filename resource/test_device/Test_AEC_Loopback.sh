#!/usr/bin/env bash

set -u

SOURCE_FILE="${1:-/tmp/vbot-mic-source.wav}"
OUTPUT_FILE="${2:-/tmp/vbot-loopback.wav}"
PLAYBACK_LOG="/tmp/vbot-aplay-loop.log"
PLAYBACK_DEVICE="hw:VBotAEC,0,0"
CAPTURE_DEVICE="hw:VBotAEC,1,0"
PLAYBACK_PID=""

cleanup() {
    if [[ -n "${PLAYBACK_PID}" ]] && kill -0 "${PLAYBACK_PID}" 2>/dev/null; then
        kill "${PLAYBACK_PID}" 2>/dev/null || true
        wait "${PLAYBACK_PID}" 2>/dev/null || true
    fi
}

trap cleanup EXIT INT TERM

if ! command -v aplay >/dev/null 2>&1 || ! command -v arecord >/dev/null 2>&1; then
    echo "[AEC Test] Không tìm thấy aplay/arecord. Hãy cài gói alsa-utils."
    exit 1
fi

if [[ ! -f "${SOURCE_FILE}" ]]; then
    echo "[AEC Test] Không tìm thấy file nguồn: ${SOURCE_FILE}"
    echo "Hãy thu file nguồn trước hoặc truyền đường dẫn WAV vào tham số thứ nhất."
    exit 1
fi

if ! aplay -l 2>/dev/null | grep -q "VBotAEC"; then
    echo "[AEC Test] Đang tải module snd-aloop..."
    if ! sudo modprobe snd-aloop index=2 id=VBotAEC pcm_substreams=1 pcm_notify=0; then
        echo "[AEC Test] Không thể tải snd-aloop."
        exit 1
    fi
fi

if ! aplay -l 2>/dev/null | grep -q "VBotAEC"; then
    echo "[AEC Test] Không tìm thấy card VBotAEC sau khi tải snd-aloop."
    exit 1
fi

rm -f -- "${OUTPUT_FILE}" "${PLAYBACK_LOG}"

echo "[AEC Test] File nguồn : ${SOURCE_FILE}"
echo "[AEC Test] File kết quả: ${OUTPUT_FILE}"
echo "[AEC Test] Bắt đầu phát PCM vào ${PLAYBACK_DEVICE}..."

timeout 7s aplay \
    -D "${PLAYBACK_DEVICE}" \
    "${SOURCE_FILE}" \
    >"${PLAYBACK_LOG}" 2>&1 &
PLAYBACK_PID=$!

sleep 0.2

echo "[AEC Test] Bắt đầu thu PCM từ ${CAPTURE_DEVICE}..."
timeout 5s arecord \
    -D "${CAPTURE_DEVICE}" \
    -f S32_LE \
    -r 48000 \
    -c 2 \
    -d 3 \
    "${OUTPUT_FILE}"
CAPTURE_STATUS=$?

wait "${PLAYBACK_PID}" 2>/dev/null
PLAYBACK_STATUS=$?
PLAYBACK_PID=""

if [[ -s "${PLAYBACK_LOG}" ]]; then
    echo
    echo "[AEC Test] Log playback:"
    cat "${PLAYBACK_LOG}"
fi

if [[ ${CAPTURE_STATUS} -ne 0 ]]; then
    echo "[AEC Test] Thu Loopback thất bại, mã lỗi: ${CAPTURE_STATUS}"
    exit "${CAPTURE_STATUS}"
fi

if [[ ${PLAYBACK_STATUS} -ne 0 && ${PLAYBACK_STATUS} -ne 124 ]]; then
    echo "[AEC Test] Playback thất bại, mã lỗi: ${PLAYBACK_STATUS}"
    exit "${PLAYBACK_STATUS}"
fi

if [[ ! -s "${OUTPUT_FILE}" ]]; then
    echo "[AEC Test] File kết quả không tồn tại hoặc không có dữ liệu."
    exit 1
fi

echo
echo "[AEC Test] Loopback đã tạo file thành công:"
file "${OUTPUT_FILE}" 2>/dev/null || true
ls -lh "${OUTPUT_FILE}"
echo
echo "Nghe thử bằng lệnh:"
echo "  aplay -D default \"${OUTPUT_FILE}\""
