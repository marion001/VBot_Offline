# Cài thủ công WebRTC Audio Processing 2.1 và PJSIP 2.17

Hướng dẫn cho Pi Zero 2 W chạy Raspberry Pi OS **32-bit/armhf**, dùng Python
hệ thống, không dùng venv. Đây là hai phiên bản VBot hiện đang dùng, không phải
chỉ dẫn tự chọn phiên bản mới nhất. Chạy lần lượt từng khối và dừng nếu có lỗi.

Toàn bộ nguồn build và thư viện đặt dưới `/home/pi/VBot_AEC3/`; mã VBot có thể
đặt ở nơi khác. Ví dụ dưới đây dùng `/home/pi/VBot_Offline/`.

## 1. Kiểm tra máy và cài công cụ

```bash
uname -m
getconf LONG_BIT
dpkg --print-architecture
python3 --version
cat /etc/os-release
free -h
df -h /home/pi /tmp
```

Các cờ ARM bên dưới dành cho `armv7l`, `32`, `armhf`. Nếu máy là aarch64/64-bit,
không dùng nguyên bộ cờ `-march=armv7-a -mfpu=neon -mfloat-abi=hard` này.
Binding PJSIP được build cho đúng Python đang dùng; máy hiện tại dùng Python 3.9.

Dừng VBot và các bản test đang chiếm mic/loa trước khi build/cài để giảm tải RAM.
Giữ nguyên dịch vụ I2S keepalive đang dùng; hướng dẫn không sửa ALSA/Bluetooth/AirPlay.

```bash
sudo apt-get update
sudo apt-get install -y build-essential git pkg-config cmake ninja-build curl \
  swig python3-dev python3-pip python3-setuptools python3-wheel \
  libasound2-dev libssl-dev uuid-dev alsa-utils binutils unzip
mkdir -p /home/pi/VBot_AEC3/tools/python
mkdir -p /home/pi/VBot_AEC3/webrtc-2.1/build /home/pi/VBot_AEC3/webrtc-2.1/vendor
mkdir -p /home/pi/VBot_AEC3/webrtc-2.1/tmp
mkdir -p /home/pi/VBot_AEC3/pjsip/build /home/pi/VBot_AEC3/pjsip/python
mkdir -p /home/pi/VBot_AEC3/pjsip/tmp
```

Cài Meson riêng vào thư mục công cụ bằng Python hệ thống, **không tạo môi trường env**:

```bash
python3 -m pip install --no-deps --target /home/pi/VBot_AEC3/tools/python 'meson==1.5.2'
export PYTHONPATH=/home/pi/VBot_AEC3/tools/python
python3 -m mesonbuild.mesonmain --version
```

Pi 512 MB chỉ dùng một job. Binding SWIG có thể dùng nhiều swap và biên dịch
rất lâu. Kiểm tra bằng `free -h`, `vmstat 2 5`, `pgrep -a cc1plus`; không chạy
hai lượt build cùng lúc. `TMPDIR` bên dưới đưa file tạm ra thẻ nhớ thay vì tmpfs.

## 2. Tải Abseil và WebRTC 2.1

```bash
cd /home/pi/VBot_AEC3/webrtc-2.1
export TMPDIR=/home/pi/VBot_AEC3/webrtc-2.1/tmp
curl --fail --location --retry 3 \
  https://github.com/abseil/abseil-cpp/archive/refs/tags/20240722.0.tar.gz \
  -o abseil-20240722.0.tar.gz
curl --fail --location --retry 3 \
  https://gstreamer.freedesktop.org/src/mirror/webrtc-audio-processing/webrtc-audio-processing-2.1.tar.gz \
  -o webrtc-audio-processing-2.1.tar.gz
echo 'f50e5ac311a81382da7fa75b97310e4b9006474f9560ac46f54a9967f07d4ae3  abseil-20240722.0.tar.gz' | sha256sum -c -
echo '35e86b986d02ea15f3d04741a1a5a735ba399bc0fac0ee089c39480e35fc3253  webrtc-audio-processing-2.1.tar.gz' | sha256sum -c -
```

Chỉ tiếp tục khi cả hai checksum đều báo `OK`. Các hash là của bộ nguồn đã
build cho hệ thống hiện tại. Abseil 20240722.0 được cố định để tái tạo bộ build
này; nếu đổi sang bản vá khác, cần cập nhật hash và kiểm thử lại thư viện.

```bash
tar -xzf abseil-20240722.0.tar.gz -C build
tar -xzf webrtc-audio-processing-2.1.tar.gz -C build
```

## 3. Build Abseil static/PIC, C++17

```bash
cd /home/pi/VBot_AEC3/webrtc-2.1
cmake -S build/abseil-cpp-20240722.0 -B build/abseil -G Ninja \
  -DCMAKE_INSTALL_PREFIX=/home/pi/VBot_AEC3/webrtc-2.1/vendor \
  -DCMAKE_INSTALL_LIBDIR=lib \
  -DCMAKE_BUILD_TYPE=Release \
  -DCMAKE_CXX_STANDARD=17 \
  -DCMAKE_POSITION_INDEPENDENT_CODE=ON \
  -DCMAKE_CXX_FLAGS="-march=armv7-a -mfpu=neon -mfloat-abi=hard" \
  -DBUILD_SHARED_LIBS=OFF \
  -DABSL_BUILD_TESTING=OFF \
  -DABSL_ENABLE_INSTALL=ON \
  -DABSL_PROPAGATE_CXX_STD=ON
cmake --build build/abseil --parallel 1
cmake --install build/abseil
```

## 4. Build WebRTC Audio Processing 2.1

Mở terminal mới thì cần khai báo lại `PYTHONPATH`, `PKG_CONFIG_PATH`, `TMPDIR`.

```bash
cd /home/pi/VBot_AEC3/webrtc-2.1
export PYTHONPATH=/home/pi/VBot_AEC3/tools/python
export PKG_CONFIG_PATH=/home/pi/VBot_AEC3/webrtc-2.1/vendor/lib/pkgconfig
export TMPDIR=/home/pi/VBot_AEC3/webrtc-2.1/tmp
python3 -m mesonbuild.mesonmain setup build/webrtc build/webrtc-audio-processing-2.1 \
  --prefix=/home/pi/VBot_AEC3/webrtc-2.1/vendor --libdir=lib \
  --buildtype=release --wrap-mode=nofallback \
  -Ddefault_library=shared -Dneon=enabled \
  -Dc_args="-march=armv7-a -mfpu=neon -mfloat-abi=hard" \
  -Dcpp_args="-march=armv7-a -mfpu=neon -mfloat-abi=hard"
python3 -m mesonbuild.mesonmain compile -C build/webrtc -j 1
python3 -m mesonbuild.mesonmain install -C build/webrtc
pkg-config --modversion webrtc-audio-processing-2
pkg-config --variable=prefix webrtc-audio-processing-2
```

Kết quả phải là `2.1` và prefix nằm trong thư mục `webrtc-2.1/vendor`. Nếu build
directory đã được cấu hình, dùng `mesonmain setup --reconfigure ...` với cùng
tham số; không ghi đè bằng prefix của bản 1.3.

## 5. Build wrapper VBot cho AEC3 + NS + AGC

Wrapper là mã riêng của VBot, **phải dùng bản dành cho 2.1**. Hai file nguồn
đi kèm hướng dẫn nằm trong `resource/tools/audio_native/`.

```bash
cp /home/pi/VBot_Offline/resource/tools/audio_native/vbot_aec3_music_2_1.cpp /home/pi/VBot_AEC3/webrtc-2.1/
cp /home/pi/VBot_Offline/resource/tools/audio_native/check_native.py /home/pi/VBot_AEC3/webrtc-2.1/
cd /home/pi/VBot_AEC3/webrtc-2.1
export PKG_CONFIG_PATH=/home/pi/VBot_AEC3/webrtc-2.1/vendor/lib/pkgconfig
c++ -O2 -std=c++17 -fPIC -shared -DWEBRTC_APM_DEBUG_DUMP=0 \
  -DWEBRTC_POSIX -DWEBRTC_LINUX \
  -march=armv7-a -mfpu=neon -mfloat-abi=hard \
  vbot_aec3_music_2_1.cpp \
  -I/home/pi/VBot_AEC3/webrtc-2.1/build/webrtc-audio-processing-2.1/webrtc \
  $(pkg-config --cflags --libs webrtc-audio-processing-2) \
  '-Wl,-rpath,$ORIGIN/vendor/lib' -Wl,-z,defs \
  -o libvbot_aec3_music_2_1.so.new
ldd libvbot_aec3_music_2_1.so.new
python3 check_native.py ./libvbot_aec3_music_2_1.so.new --output native-check-2.1.json
```

Không được có `not found` trong `ldd`; kiểm tra native phải hoàn tất không lỗi.
Nếu đang có thư viện cũ, sao lưu trước khi thay:

```bash
cd /home/pi/VBot_AEC3/webrtc-2.1
if [ -f libvbot_aec3_music_2_1.so ]; then
  cp -p libvbot_aec3_music_2_1.so "libvbot_aec3_music_2_1.so.backup.$(date +%Y%m%d_%H%M%S)"
fi
mv libvbot_aec3_music_2_1.so.new libvbot_aec3_music_2_1.so
```

Thư viện này cần đi cùng `webrtc-2.1/vendor/lib/*.so*`; không chỉ giữ một file
wrapper `.so` rồi xóa vendor. RPATH dùng `$ORIGIN/vendor/lib`, không cần thêm
thư viện riêng vào `/usr/lib` hay sửa `LD_LIBRARY_PATH` toàn hệ thống.

## 6. Tải và build PJSIP 2.17

```bash
cd /home/pi/VBot_AEC3/pjsip
git clone --depth 1 --branch 2.17 https://github.com/pjsip/pjproject.git build/pjproject
cd build/pjproject
export CFLAGS='-O2 -fPIC'
export CXXFLAGS='-O2 -fPIC'
export TMPDIR=/home/pi/VBot_AEC3/pjsip/tmp
./configure --prefix=/home/pi/VBot_AEC3/pjsip/runtime \
  --disable-video --disable-portaudio --enable-libwebrtc-aec3
make dep
make -j1
```

Nếu source đã tồn tại thì không clone đè; kiểm tra tag/commit trước khi build
lại. Cấu hình trên giữ công thức PJSIP đã build cho hai loa hiện tại. ALSA cần
được phát hiện trong bước configure. Không cần `make install`: binding bên
dưới liên kết với các thư viện static trong source tree.

PJSIP có AEC đi kèm trong bộ build, nhưng **VBot tắt AEC của PJMEDIA**
(`ecTailLen=ecOptions=0`). Khi gọi, VBot dùng wrapper 2.1 riêng để xử lý AEC
một lần, rồi NS/AGC chung một lần; không chạy hai bộ lọc chồng lên nhau.

## 7. Build và cài Python binding trực tiếp

```bash
cd /home/pi/VBot_AEC3/pjsip/build/pjproject/pjsip-apps/src/swig/python
export TMPDIR=/home/pi/VBot_AEC3/pjsip/tmp
make PYTHON_EXE=python3
python3 -m pip install . --no-build-isolation --no-deps \
  --target /home/pi/VBot_AEC3/pjsip/python
PYTHONPATH=/home/pi/VBot_AEC3/pjsip/python python3 -c 'import pjsua2; print("PJSUA2 import OK")'
ldd /home/pi/VBot_AEC3/pjsip/python/_pjsua2*.so
```

Đây thường là bước nặng nhất trên Pi 512 MB. Các cảnh báo SWIG Doxygen và
`PyEval_InitThreads` deprecated tự chúng không có nghĩa build thất bại; cần
kiểm tra mã kết thúc, file `.so`, `ldd` và import thực tế.

Nếu SSH có thể mất kết nối, **thay lệnh `make` ở trên** bằng lệnh sau, không
chạy song song với một lượt make khác. Không ghi log, chỉ ghi mã kết thúc:

```bash
cd /home/pi/VBot_AEC3/pjsip/build/pjproject/pjsip-apps/src/swig/python
export TMPDIR=/home/pi/VBot_AEC3/pjsip/tmp
nohup sh -c 'make PYTHON_EXE=python3; rc=$?; printf "%s\n" "$rc" > binding.exit; exit "$rc"' > /dev/null 2>&1 < /dev/null &
```

Đợi tiến trình hoàn tất, `binding.exit` phải là `0`, rồi mới chạy `pip install`
và kiểm tra import. Trước lượt build mới, đổi tên/xóa file trạng thái cũ nếu
có để không nhầm kết quả cũ. Không cần build lại binding trên máy khác nếu
máy đó tương thích với gói runtime theo README cài bản build sẵn.

## 8. Kiểm tra và bật trong VBot

```bash
cd /home/pi/VBot_Offline
python3 - <<'PY'
import json
from Lib_AEC3 import check_webrtc_library
from LAN_Calls import check_dependencies
c=json.load(open('Config.json',encoding='utf-8'))
library='/home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so'
print('WebRTC',check_webrtc_library(library))
c.setdefault('smart_config',{}).setdefault('audio_processing',{}).setdefault('aec3',{})['library']=library
check_dependencies(c.get('lan_calls',{}),c)
print('WebRTC 2.1 + PJSIP OK')
PY
```

Đoạn kiểm tra chỉ đọc cấu hình, không khởi tạo recorder/SIP endpoint và không
ghi Config.json. Trong WebUI, đặt:

- `smart_config.audio_processing.aec3.library`:
  `/home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so`.
- Bật xử lý WebRTC MIC nếu muốn dùng AEC/NS/AGC; chọn AEC output `normal`.
- Bật Call LAN và đặt PJSIP runtime `/home/pi/VBot_AEC3/pjsip`.
- Khai báo danh sách loa hai chiều tại LAN_Calls.php, dùng cùng khóa chung;
  TCP 5010 và SIP UDP 5060 cố định, RTP bắt đầu UDP 40000 theo cấu hình.

Lưu cấu hình rồi chạy `python3 Start.py`. Nếu thư viện thiếu hoặc không tương
thích, VBot ghi log, bỏ qua bộ lọc/Call LAN tương ứng và vẫn tiếp tục chạy.
Việc thư viện nạp được chưa thay thế cho kiểm thử gọi, mic/loa và wake word.

## Cài đường âm thanh chung cho AirPlay, Bluetooth và multiroom

Sau khi cài thư viện, dừng VBot và chạy một lần trên mỗi loa:

```bash
cd /home/pi/VBot_Offline
sudo python3 resource/vbot_aec3/install_media_routes.py \
  --root /home/pi/VBot_Offline --apply
```

Script sao lưu rồi cấu hình ALSA, AirPlay, Bluetooth và bridge sang `vbot_aec_media` / `plughw:Loopback,1,1`, bật `external_sources`. Nếu đã cài thì không chạy lại `--apply`. Start.py mới tự kiểm tra và cài khi còn thiếu; lỗi được ghi log và VBot vẫn tiếp tục chạy. Xem [Cài đường âm thanh chung cho loa](README_INSTALL_SHARED_MEDIA_ROUTES.md).

## Nguồn đối chiếu

- [WebRTC Audio Processing 2.1 — mirror nguồn](https://gstreamer.freedesktop.org/src/mirror/webrtc-audio-processing/)
- [Abseil 20240722.0 — bản nguồn tái tạo bộ build hiện tại](https://github.com/abseil/abseil-cpp/releases/tag/20240722.0)
- [PJSIP 2.17](https://github.com/pjsip/pjproject/releases/tag/2.17)
- [PJSUA2: yêu cầu PIC, SWIG và Python development headers](https://docs.pjsip.org/en/latest/pjsua2/building.html)

Hướng dẫn cài/chuyển thư viện đã build: [README_INSTALL_WEBRTC21_PJSIP_PREBUILT.md](README_INSTALL_WEBRTC21_PJSIP_PREBUILT.md).
ZIP đóng sẵn được phát hành tại `resource/vbot_aec3/vbot_audio_runtime_armhf_py39.zip`;
thư viện sau cài vẫn nằm trong `/home/pi/VBot_AEC3/`.
