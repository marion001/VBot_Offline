#!/bin/sh
set -eu
# Run as pi. Keep native EQ next to its presets, independently of WebRTC.
eq_source_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
eq_install_dir=$eq_source_dir
mkdir -p "$eq_install_dir"
# The WebUI atomically replaces its preset JSON in this directory.
chmod 0777 "$eq_install_dir"
eq_build_dir=$(mktemp -d "$eq_install_dir/build.XXXXXX")
trap 'rm -f "$eq_build_dir/libvbot_eq.so" "$eq_build_dir/equalizer.cpp"; rmdir "$eq_build_dir"' EXIT
cp "$eq_source_dir/equalizer.cpp" "$eq_build_dir/equalizer.cpp"
c++ -O2 -std=c++17 -fPIC -shared "$eq_build_dir/equalizer.cpp" -o "$eq_build_dir/libvbot_eq.so"
python3 - "$eq_build_dir/libvbot_eq.so" <<'PY'
import ctypes,sys
lib=ctypes.CDLL(sys.argv[1])
lib.vbot_eq_band_count.restype=ctypes.c_int
assert lib.vbot_eq_band_count()==6
lib.vbot_eq_create6.argtypes=[ctypes.c_int]+[ctypes.c_double]*8
lib.vbot_eq_create6.restype=ctypes.c_void_p
lib.vbot_eq_destroy.argtypes=[ctypes.c_void_p]
handle=lib.vbot_eq_create6(48000,0,0,0,0,0,0,0,100)
assert handle
lib.vbot_eq_destroy(handle)
PY
# Validate/migrate presets before replacing a working native library.
python3 "$eq_source_dir/migrate.py"
if [ -f "$eq_install_dir/libvbot_eq.so" ]; then
  cp -p "$eq_install_dir/libvbot_eq.so" "$eq_install_dir/libvbot_eq.so.backup.$(date +%Y%m%d_%H%M%S)"
fi
mv "$eq_build_dir/libvbot_eq.so" "$eq_install_dir/libvbot_eq.so"
mv "$eq_build_dir/equalizer.cpp" "$eq_install_dir/equalizer.cpp"
echo "EQ installed: $eq_install_dir/libvbot_eq.so"
