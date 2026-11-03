#!/usr/bin/env bash
set -Eeuo pipefail
log(){ printf '[VBot IR] %s\n' "$*"; }
fail(){ printf '[VBot IR] LỖI: %s\n' "$*" >&2; exit 1; }

if [[ ${EUID} -ne 0 ]]; then
  command -v sudo >/dev/null || fail "Không tìm thấy sudo"
  log "Tự động nâng quyền bằng sudo..."; exec sudo bash "$0" "$@"
fi

apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y v4l-utils

# pigpio waveform gây xung đột audio trên VBot; LIRC kernel thay thế hoàn toàn.
systemctl disable --now pigpiod 2>/dev/null || true
pkill -x pigpiod 2>/dev/null || true

CONFIG_FILE="/boot/firmware/config.txt"
[[ -f ${CONFIG_FILE} ]] || CONFIG_FILE="/boot/config.txt"
[[ -f ${CONFIG_FILE} ]] || fail "Không tìm thấy config.txt của Raspberry Pi"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
VBOT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
VBOT_CONFIG="${VBOT_ROOT}/Config.json"
[[ -f ${VBOT_CONFIG} ]] || fail "Không tìm thấy ${VBOT_CONFIG}"
TX_GPIO="$(python3 -c 'import json,sys; print(int(json.load(open(sys.argv[1], encoding="utf-8"))["internal_ir"]["tx_gpio"]))' "$VBOT_CONFIG")"
RX_GPIO="$(python3 -c 'import json,sys; print(int(json.load(open(sys.argv[1], encoding="utf-8"))["internal_ir"]["rx_gpio"]))' "$VBOT_CONFIG")"
[[ ${TX_GPIO} =~ ^([0-9]|1[0-9]|2[0-7])$ ]] || fail "GPIO TX không hợp lệ: ${TX_GPIO}"
[[ ${RX_GPIO} =~ ^([0-9]|1[0-9]|2[0-7])$ ]] || fail "GPIO RX không hợp lệ: ${RX_GPIO}"
[[ ${TX_GPIO} != "${RX_GPIO}" ]] || fail "GPIO TX và RX không được trùng nhau"
[[ ${TX_GPIO} != "10" && ${RX_GPIO} != "10" ]] || fail "GPIO10 đang được VBot sử dụng để điều khiển LED"
[[ ${TX_GPIO} != "16" && ${RX_GPIO} != "16" ]] || fail "GPIO16 đang được Google Voice HAT sử dụng để bật amplifier"

add_overlay(){
  local line="$1"
  grep -Fxq "$line" "$CONFIG_FILE" || printf '%s\n' "$line" >> "$CONFIG_FILE"
}
RX_LINE="dtoverlay=gpio-ir,gpio_pin=${RX_GPIO}"
RX_LINE_COUNT="$(grep -Fxc "$RX_LINE" "$CONFIG_FILE" || true)"
TX_LINE_COUNT="$(grep -Ec '^dtoverlay=gpio-ir-tx,gpio_pin=[0-9][0-9]*$' "$CONFIG_FILE" || true)"
BOOT_CONFIG_CHANGED=0
if [[ ${RX_LINE_COUNT} != "1" || ${TX_LINE_COUNT} != "0" ]]; then BOOT_CONFIG_CHANGED=1; fi
sed -i '/^dtoverlay=gpio-ir,gpio_pin=[0-9][0-9]*$/d' "$CONFIG_FILE"
sed -i '/^dtoverlay=gpio-ir-tx,gpio_pin=[0-9][0-9]*$/d' "$CONFIG_FILE"
add_overlay "$RX_LINE"
# TX is loaded/unloaded by VBot at runtime so GPIO17 can be handed to the
# ReSpeaker button without editing config.txt for every configuration change.

# Cho user gọi script được quyền truy cập /dev/lircX sau lần đăng nhập kế tiếp.
TARGET_USER="${SUDO_USER:-pi}"
if id "$TARGET_USER" >/dev/null 2>&1; then usermod -aG video "$TARGET_USER"; fi
if id www-data >/dev/null 2>&1; then usermod -aG video www-data; fi
install -d -m 0775 "${VBOT_ROOT}/resource/internal_ir"
touch "${VBOT_ROOT}/resource/internal_ir/commands.json"
if id www-data >/dev/null 2>&1; then
  chgrp www-data "${VBOT_ROOT}/resource/internal_ir" "${VBOT_ROOT}/resource/internal_ir/commands.json"
  chmod 0775 "${VBOT_ROOT}/resource/internal_ir"
  chmod 0664 "${VBOT_ROOT}/resource/internal_ir/commands.json"
fi

log "Đã cài LIRC/ir-ctl và cấu hình RX GPIO${RX_GPIO}; TX GPIO${TX_GPIO} sẽ do VBot quản lý động."
if [[ ${BOOT_CONFIG_CHANGED} -eq 1 ]]; then
  log "Đã cập nhật overlay boot trong ${CONFIG_FILE}; cần reboot một lần để áp dụng RX và gỡ TX tĩnh cũ."
else
  log "Overlay boot đã đúng; không cần reboot."
fi
log "VBot sẽ tự nạp/gỡ gpio-ir-tx theo Config.json bằng sudo không hỏi mật khẩu; đổi cấu hình sau đó chỉ cần restart VBot."
