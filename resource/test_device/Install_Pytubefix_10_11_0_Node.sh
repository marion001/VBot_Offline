#!/bin/bash
# Auto install pytubefix 10.11.0 + Node.js system binary + nodejs_wheel shim
# Không cài/upgrade aiohttp.
#
# Usage:
#   chmod +x Install_Pytubefix_10_11_0_Node.sh
#   ./Install_Pytubefix_10_11_0_Node.sh

set -euo pipefail

PYTHON_BIN="${PYTHON_BIN:-python3}"
PYTUBEFIX_VERSION="10.11.0"
NODE_VERSION="${NODE_VERSION:-22.22.3}"

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

command -v "$PYTHON_BIN" >/dev/null 2>&1 || die "Không tìm thấy $PYTHON_BIN"
command -v sudo >/dev/null 2>&1 || die "Không tìm thấy sudo"
command -v tar >/dev/null 2>&1 || die "Không tìm thấy tar"
command -v sha256sum >/dev/null 2>&1 || die "Không tìm thấy sha256sum"

ARCH="$(uname -m)"

case "$ARCH" in
    armv7l)
        NODE_ARCH="armv7l"
        ;;
    aarch64|arm64)
        NODE_ARCH="arm64"
        ;;
    x86_64|amd64)
        NODE_ARCH="x64"
        ;;
    *)
        die "Kiến trúc chưa hỗ trợ: $ARCH"
        ;;
esac

NODE_FILE="node-v${NODE_VERSION}-linux-${NODE_ARCH}.tar.xz"
NODE_DIR="node-v${NODE_VERSION}-linux-${NODE_ARCH}"
NODE_BASE_URL="https://nodejs.org/dist/v${NODE_VERSION}"
TMP_DIR="$(mktemp -d)"

cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

download_file() {
    local url="$1"
    local output="$2"

    if command -v curl >/dev/null 2>&1; then
        curl -fL --retry 3 --connect-timeout 20 "$url" -o "$output"
    elif command -v wget >/dev/null 2>&1; then
        wget -O "$output" "$url"
    else
        die "Cần curl hoặc wget để tải file"
    fi
}

log "1/6 - Kiểm tra hệ thống"

echo "Architecture      : $ARCH"
echo "Python            : $("$PYTHON_BIN" --version 2>&1)"
echo "pytubefix         : $PYTUBEFIX_VERSION"
echo "Node.js           : $NODE_VERSION"
echo "Node package      : $NODE_FILE"

# Chỉ kiểm tra aiohttp đã có, không cài hay upgrade.
if ! "$PYTHON_BIN" -c "import aiohttp; print('aiohttp           : ' + aiohttp.__version__)" 2>/dev/null; then
    die "Không tìm thấy aiohttp. Script này không tự cài aiohttp."
fi

log "2/6 - Cài đúng pytubefix==10.11.0"

# --no-deps để không kéo nodejs-wheel-binaries và không đụng aiohttp.
"$PYTHON_BIN" -m pip install \
    --user \
    --upgrade \
    --no-deps \
    "pytubefix==${PYTUBEFIX_VERSION}"

log "3/6 - Tải Node.js binary chính thức"

cd "$TMP_DIR"

download_file \
    "${NODE_BASE_URL}/${NODE_FILE}" \
    "$NODE_FILE"

download_file \
    "${NODE_BASE_URL}/SHASUMS256.txt" \
    "SHASUMS256.txt"

EXPECTED_SHA="$(
    awk -v file="$NODE_FILE" '$2 == file {print $1}' SHASUMS256.txt
)"

[ -n "$EXPECTED_SHA" ] || die "Không tìm thấy SHA256 của $NODE_FILE"

ACTUAL_SHA="$(sha256sum "$NODE_FILE" | awk '{print $1}')"

echo "Expected SHA256: $EXPECTED_SHA"
echo "Actual SHA256  : $ACTUAL_SHA"

[ "$EXPECTED_SHA" = "$ACTUAL_SHA" ] || die "SHA256 không khớp"

log "4/6 - Cài Node.js vào /usr/local"

tar -xJf "$NODE_FILE"

[ -x "${NODE_DIR}/bin/node" ] || die "Không tìm thấy Node sau khi giải nén"

# Test binary trước khi cài hệ thống.
"${NODE_DIR}/bin/node" --version

sudo cp -a "${NODE_DIR}/." /usr/local/

[ -x /usr/local/bin/node ] || die "/usr/local/bin/node không tồn tại"

echo "Node              : $(/usr/local/bin/node --version)"

if [ -x /usr/local/bin/npm ]; then
    echo -n "npm               : "
    PATH="/usr/local/bin:$PATH" /usr/local/bin/npm --version
fi

log "5/6 - Tạo nodejs_wheel shim ở mức hệ thống"

PY_VERSION="$(
    "$PYTHON_BIN" -c \
    'import sys; print("{}.{}".format(sys.version_info.major, sys.version_info.minor))'
)"

SYSTEM_SITE="/usr/local/lib/python${PY_VERSION}/dist-packages"
SHIM_DIR="${SYSTEM_SITE}/nodejs_wheel"

sudo mkdir -p "$SHIM_DIR"

sudo tee "${SHIM_DIR}/__init__.py" >/dev/null <<'PYEOF'
from . import executable
PYEOF

sudo tee "${SHIM_DIR}/executable.py" >/dev/null <<'PYEOF'
# Compatibility shim for pytubefix.
# Node.js được cài trực tiếp tại /usr/local.

ROOT_DIR = "/usr/local"
PYEOF

log "6/6 - Kiểm tra"

"$PYTHON_BIN" - <<'PYEOF'
import os
import aiohttp
import pytubefix
import nodejs_wheel.executable

print("aiohttp           :", aiohttp.__version__)
print("pytubefix         :", getattr(pytubefix, "__version__", "unknown"))
print("nodejs_wheel root :", nodejs_wheel.executable.ROOT_DIR)

node_path = os.path.join(
    nodejs_wheel.executable.ROOT_DIR,
    "bin",
    "node"
)

print("Node path         :", node_path)

if not os.path.isfile(node_path):
    raise SystemExit("Không tìm thấy Node tại: " + node_path)

if not os.access(node_path, os.X_OK):
    raise SystemExit("Node không có quyền execute: " + node_path)

if getattr(pytubefix, "__version__", None) != "10.11.0":
    raise SystemExit(
        "Sai phiên bản pytubefix: "
        + str(getattr(pytubefix, "__version__", None))
    )

print("Kiểm tra hoàn tất: OK")
PYEOF

echo
echo "============================================================"
echo "CÀI ĐẶT HOÀN TẤT"
echo "============================================================"
echo "pytubefix : 10.11.0"
echo "Node.js   : $(/usr/local/bin/node --version)"
echo "Shim      : ${SHIM_DIR}/executable.py"
