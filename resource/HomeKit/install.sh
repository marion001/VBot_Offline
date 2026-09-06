#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STATE_DIR="${VBOT_HOMEKIT_STATE_DIR:-/home/pi/VBot_Node/HomeKit}"
SERVICE_DIR="${HOME}/.config/systemd/user"
NODE_VERSION="${NODE_VERSION:-22.22.3}"
export PATH="/usr/local/bin:/usr/bin:/bin:${PATH}"

log() {
  echo
  echo "============================================================"
  echo "$1"
  echo "============================================================"
}

die() {
  echo "ERROR: $*" >&2
  exit 1
}

download_file() {
  local url="$1"
  local output="$2"
  if command -v curl >/dev/null 2>&1; then
    curl -fL --retry 3 --connect-timeout 20 "$url" -o "$output"
  elif command -v wget >/dev/null 2>&1; then
    wget -O "$output" "$url"
  else
    die "Cần curl hoặc wget để tải Node.js"
  fi
}

install_node() (
  command -v sudo >/dev/null 2>&1 || die "Không tìm thấy sudo"
  command -v tar >/dev/null 2>&1 || die "Không tìm thấy tar"
  command -v sha256sum >/dev/null 2>&1 || die "Không tìm thấy sha256sum"
  command -v awk >/dev/null 2>&1 || die "Không tìm thấy awk"

  local arch node_arch node_file node_dir node_base_url tmp_dir expected_sha actual_sha
  arch="$(uname -m)"
  case "$arch" in
    armv7l) node_arch="armv7l" ;;
    aarch64|arm64) node_arch="arm64" ;;
    x86_64|amd64) node_arch="x64" ;;
    *) die "Kiến trúc chưa hỗ trợ: $arch" ;;
  esac

  node_file="node-v${NODE_VERSION}-linux-${node_arch}.tar.xz"
  node_dir="node-v${NODE_VERSION}-linux-${node_arch}"
  node_base_url="https://nodejs.org/dist/v${NODE_VERSION}"
  tmp_dir="$(mktemp -d)"
  [[ -n "$tmp_dir" && -d "$tmp_dir" ]] || die "Không tạo được thư mục tạm"

  cleanup_node_tmp() {
    if [[ -n "$tmp_dir" && -d "$tmp_dir" ]]; then
      rm -rf -- "$tmp_dir"
    fi
  }
  trap cleanup_node_tmp EXIT

  log "2/6 - Tải Node.js v${NODE_VERSION} chính thức (${node_arch})"
  download_file "${node_base_url}/${node_file}" "${tmp_dir}/${node_file}"
  download_file "${node_base_url}/SHASUMS256.txt" "${tmp_dir}/SHASUMS256.txt"

  expected_sha="$(awk -v file="$node_file" '$2 == file {print $1}' "${tmp_dir}/SHASUMS256.txt")"
  [[ -n "$expected_sha" ]] || die "Không tìm thấy SHA256 của ${node_file}"
  actual_sha="$(sha256sum "${tmp_dir}/${node_file}" | awk '{print $1}')"
  [[ "$expected_sha" == "$actual_sha" ]] || die "SHA256 Node.js không khớp"

  tar -xJf "${tmp_dir}/${node_file}" -C "$tmp_dir"
  [[ -x "${tmp_dir}/${node_dir}/bin/node" ]] || die "Binary Node.js sau giải nén không hợp lệ"
  [[ "$("${tmp_dir}/${node_dir}/bin/node" --version)" == "v${NODE_VERSION}" ]] || die "Sai phiên bản Node.js trong gói tải về"

  log "3/6 - Cài Node.js v${NODE_VERSION} vào /usr/local"
  sudo cp -a "${tmp_dir}/${node_dir}/." /usr/local/
  hash -r
  [[ -x /usr/local/bin/node ]] || die "Không tìm thấy /usr/local/bin/node sau cài đặt"
  [[ -x /usr/local/bin/npm ]] || die "Không tìm thấy /usr/local/bin/npm sau cài đặt"
  [[ "$(/usr/local/bin/node --version)" == "v${NODE_VERSION}" ]] || die "Xác minh Node.js sau cài đặt thất bại"
)

log "1/6 - Kiểm tra Node.js"
[[ "${EUID}" -ne 0 ]] || die "Không chạy toàn bộ script bằng sudo; hãy chạy ./install.sh để systemd user thuộc tài khoản pi"
current_node=""
local_node=""
if command -v node >/dev/null 2>&1; then
  current_node="$(node --version 2>/dev/null || true)"
fi
if [[ -x /usr/local/bin/node ]]; then
  local_node="$(/usr/local/bin/node --version 2>/dev/null || true)"
fi
echo "Node.js hiện tại : ${current_node:-chưa cài đặt}"
echo "Node /usr/local   : ${local_node:-chưa cài đặt}"
echo "Node.js yêu cầu  : v${NODE_VERSION}"

if [[ "$local_node" != "v${NODE_VERSION}" ]] ||
   [[ ! -x /usr/local/bin/npm ]]; then
  install_node
else
  log "2/6 - Node.js đã đúng phiên bản, bỏ qua cài đặt"
fi

[[ "$(/usr/local/bin/node --version)" == "v${NODE_VERSION}" ]] || die "Node.js chưa đúng v${NODE_VERSION}"
echo "Node : $(/usr/local/bin/node --version)"
echo "npm  : $(/usr/local/bin/npm --version)"

log "4/6 - Chuẩn bị thư mục runtime HomeKit"
mkdir -p "${STATE_DIR}/persist" "$SERVICE_DIR"
VBOT_CONFIG_PATH="/home/pi/VBot_Offline/Config.json"
[[ -f "$VBOT_CONFIG_PATH" ]] || die "Không tìm thấy $VBOT_CONFIG_PATH"
grep -q '"homekit"' "$VBOT_CONFIG_PATH" || die "Config.json chưa có mục homekit"

# Mỗi Raspberry Pi có OTP riêng dù nhiều loa được clone từ cùng OS image.
# Lấy hàng 28 không qua shell eval và ghi JSON nguyên tử bằng Node.js.
command -v vcgencmd >/dev/null 2>&1 || die "Không tìm thấy vcgencmd để đọc Serial phần cứng Raspberry Pi"
command -v awk >/dev/null 2>&1 || die "Không tìm thấy awk để đọc OTP Raspberry Pi"
otp_serial="$(vcgencmd otp_dump | awk -F: '$1 == "28" {gsub(/[[:space:]]/, "", $2); print toupper($2); exit}')"
[[ "$otp_serial" =~ ^[0-9A-F]+$ ]] || die "Không đọc được OTP hợp lệ tại hàng 28 từ vcgencmd otp_dump"
export VBOT_HOMEKIT_OTP_SERIAL="28:${otp_serial}"
export VBOT_HOMEKIT_CONFIG_PATH="$VBOT_CONFIG_PATH"
/usr/local/bin/node -e 'const fs=require("node:fs");const p=process.env.VBOT_HOMEKIT_CONFIG_PATH;const c=JSON.parse(fs.readFileSync(p,"utf8").replace(/^\uFEFF/,""));if(!c.homekit||typeof c.homekit!=="object")throw new Error("Config.json thiếu mục homekit");c.homekit.serial_number=process.env.VBOT_HOMEKIT_OTP_SERIAL;const t=p+".homekit.tmp";fs.writeFileSync(t,JSON.stringify(c,null,4)+"\n",{mode:0o644});fs.renameSync(t,p);'
unset VBOT_HOMEKIT_OTP_SERIAL VBOT_HOMEKIT_CONFIG_PATH
echo "Serial Number HomeKit: 28:${otp_serial} (đã ghi vào Config.json)"
install -m 644 "$SCRIPT_DIR/package.template.json" "${STATE_DIR}/package.json"

log "5/6 - Cài dependency và systemd service do VBot quản lý"
/usr/local/bin/npm --prefix "$STATE_DIR" install --omit=dev
NODE_PATH="${STATE_DIR}/node_modules" /usr/local/bin/node -e '
for (const dependency of ["@homebridge/hap-nodejs", "qrcode"]) {
  try {
    console.log(`${dependency}: ${require.resolve(dependency)}`);
  } catch (error) {
    console.error(`Không tìm thấy dependency HomeKit: ${dependency}`);
    process.exit(1);
  }
}
' || die "Cài dependency HomeKit chưa hoàn tất; kiểm tra kết nối mạng và lỗi npm bên trên"

# Dọn dependency do phiên bản installer cũ từng cài nhầm cạnh mã nguồn.
if [[ -d "$SCRIPT_DIR/node_modules/@homebridge/hap-nodejs" ]]; then
  resolved_script_dir="$(cd "$SCRIPT_DIR" && pwd -P)"
  if [[ "$resolved_script_dir" == "/home/pi/VBot_Offline/resource/HomeKit" ]]; then
    rm -rf -- "$resolved_script_dir/node_modules"
    rm -f -- "$resolved_script_dir/package-lock.json"
    echo "Đã dọn node_modules cũ khỏi thư mục mã nguồn"
  else
    echo "Bỏ qua dọn node_modules cũ vì đường dẫn mã nguồn không đúng đường dẫn VBot chuẩn"
  fi
fi
systemctl --user disable --now vbot-homekit.service 2>/dev/null || true
install -m 644 "$SCRIPT_DIR/vbot-homekit.service" "$SERVICE_DIR/vbot-homekit.service"
systemctl --user daemon-reload

log "6/6 - Kiểm tra cài đặt"
homekit_enable_state="$(systemctl --user is-enabled vbot-homekit.service 2>/dev/null || true)"
[[ "$homekit_enable_state" == "disabled" || "$homekit_enable_state" == "static" ]] ||
  die "vbot-homekit.service chưa được vô hiệu hóa (trạng thái: ${homekit_enable_state:-unknown})"
systemctl --user is-active --quiet vbot-homekit.service &&
  die "vbot-homekit.service vẫn đang chạy sau khi cài đặt"

echo "Đã cài VBot HomeKit. Cấu hình: /home/pi/VBot_Offline/Config.json -> homekit"
echo "Service không tự enable khi boot; vòng đời được quản lý bởi VBot_Offline.service"
echo "Trình cài đặt không khởi động VBot hoặc HomeKit. Hãy chủ động chạy VBot để kiểm tra cơ chế khởi động HomeKit."
echo "Xem log: journalctl --user -u vbot-homekit.service -f"
