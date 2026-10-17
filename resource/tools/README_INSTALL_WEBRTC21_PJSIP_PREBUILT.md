# Cài WebRTC 2.1 và PJSIP từ ZIP đi kèm VBot

Bản phát hành VBot đã có sẵn hai file trong `resource/vbot_aec3/`:

- [vbot_audio_runtime_armhf_py39.zip](../vbot_aec3/vbot_audio_runtime_armhf_py39.zip)
- [vbot_audio_runtime_armhf_py39.zip.sha256](../vbot_aec3/vbot_audio_runtime_armhf_py39.zip.sha256)

**Không cần tạo ZIP, chuyển ZIP từ loa khác hoặc build Python binding.**
Các lệnh bên dưới chạy qua SSH ngay trên loa cần cài, bằng user `pi`,
dùng Python hệ thống, không dùng venv.

Gói đi kèm dành cho **ARMv7/armhf 32-bit, Python 3.9, Raspbian 11**.
Có sẵn ZIP không có nghĩa là dùng được trên mọi hệ điều hành/Python:
hãy kiểm tra tương thích trước khi cài. Thư viện sẽ được cài tại
`/home/pi/VBot_AEC3/`, còn mã VBot có thể nằm ở thư mục khác.
Nếu VBot không nằm tại `/home/pi/VBot_Offline/`, thay đường dẫn tương ứng
trong các lệnh bên dưới.

Đây là gói thư viện WebRTC/PJSIP; vẫn cần các phụ thuộc và cấu hình phần cứng
âm thanh của VBot như PvRecorder, VLC, python-vlc và ALSA.

## 1. Kiểm tra ZIP và khả năng tương thích

```bash
cd /home/pi/VBot_Offline/resource/vbot_aec3
sudo apt-get update
sha256sum -c vbot_audio_runtime_armhf_py39.zip.sha256
```

Checksum phải báo `OK`. Công cụ cài cũng có sẵn trong bản VBot:

```bash
python3 /home/pi/VBot_Offline/resource/tools/audio_native/runtime_bundle.py inspect /home/pi/VBot_Offline/resource/vbot_aec3/vbot_audio_runtime_armhf_py39.zip
python3 /home/pi/VBot_Offline/resource/tools/audio_native/runtime_bundle.py check-platform /home/pi/VBot_Offline/resource/vbot_aec3/vbot_audio_runtime_armhf_py39.zip
```

Phải có `PLATFORM_COMPATIBLE`. Nếu báo `Incompatible`, dùng image OS/Python
phù hợp hoặc làm theo hướng dẫn build thủ công. Đổi tên file `_pjsua2*.so`
không khắc phục được khác biệt Python ABI hay 32/64-bit.

## 2. Cài các package runtime hệ thống

Không cần compiler, SWIG, Meson hay Abseil development files trên máy đích.
Vẫn cần các shared library hệ thống mà extension native liên kết tới.
Công cụ lấy danh sách package từ `ldd` và `dpkg-query` trên máy nguồn:

```bash
python3 /home/pi/VBot_Offline/resource/tools/audio_native/runtime_bundle.py packages /home/pi/VBot_Offline/resource/vbot_aec3/vbot_audio_runtime_armhf_py39.zip
sudo apt-get install -y $(python3 /home/pi/VBot_Offline/resource/tools/audio_native/runtime_bundle.py packages /home/pi/VBot_Offline/resource/vbot_aec3/vbot_audio_runtime_armhf_py39.zip)
```

Danh sách thường có libc, libstdc++, ALSA, OpenSSL và UUID, tùy bộ build.
Nếu apt không tìm được package, kiểm tra image OS/repository; không chép thư
viện hệ thống từ loa nguồn vào `/usr/lib` hoặc đổi tên SONAME để chữa lỗi.
Manifest không chứa file thư viện hệ thống của máy nguồn.

## 3. Kiểm tra gói trước khi cài

```bash
sudo mkdir -p /home/pi/VBot_AEC3/tmp
sudo chown -R pi:pi /home/pi/VBot_AEC3
export TMPDIR=/home/pi/VBot_AEC3/tmp
python3 /home/pi/VBot_Offline/resource/tools/audio_native/runtime_bundle.py verify /home/pi/VBot_Offline/resource/vbot_aec3/vbot_audio_runtime_armhf_py39.zip
```

Công cụ kiểm tra đường dẫn ZIP, kích thước, checksum từng file và môi trường,
rồi thử nạp WebRTC/PJSUA2 trong thư mục tạm. Nếu thiếu `.so` hệ thống hoặc có
`GLIBC_* not found`, import lỗi, sai ABI/phiên bản thì không tiếp tục cài.

## 4. Cài thư viện vào đường dẫn cố định

Dừng VBot và các chương trình test gọi trên loa mới. Công cụ sẽ từ chối cài
nếu còn tiến trình `Start.py`, `VBot.py` hoặc launcher test được nhận diện.

```bash
python3 /home/pi/VBot_Offline/resource/tools/audio_native/runtime_bundle.py install \
  /home/pi/VBot_Offline/resource/vbot_aec3/vbot_audio_runtime_armhf_py39.zip --root /home/pi/VBot_AEC3
```

Kết quả phải có `INSTALLED /home/pi/VBot_AEC3 BACKUP ...`.

- Chỉ cài các file runtime có trong manifest, không xóa source/build cũ.
- File bị thay thế được sao lưu trong
  `/home/pi/VBot_AEC3/runtime_backups/<thời-gian>_<mã>/`.
- Nếu lỗi trong bước ghi hoặc kiểm tra sau cài, công cụ khôi phục các file
  đã thay trong lượt đó; các thư mục rỗng tạo thêm có thể còn lại.
- Không sửa Config.json, Devices.json, ALSA, Bluetooth, AirPlay hoặc systemd.

Kiểm tra trực tiếp sau cài:

```bash
PYTHONPATH=/home/pi/VBot_AEC3/pjsip/python python3 -c 'import pjsua2; print("PJSUA2 import OK")'
ldd /home/pi/VBot_AEC3/pjsip/python/_pjsua2*.so
ldd /home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so
```

Không strip thêm file `.so` trước khi giữ bản sao lưu và kiểm tra import.
Bản đã strip hợp lệ có thể đóng gói dùng lại bình thường.

## 5. Cài đường âm thanh chung cho AirPlay, Bluetooth và multiroom

Dừng VBot rồi chạy một lần trên mỗi loa:

```bash
systemctl --user stop VBot_Offline.service
cd /home/pi/VBot_Offline
sudo python3 resource/vbot_aec3/install_media_routes.py \
  --root /home/pi/VBot_Offline --apply
```

Đây là bước riêng với cài ZIP thư viện: có sửa ALSA, dịch vụ âm thanh và đường Loopback trong Config.json, kèm sao lưu. Nếu đã cài thì không chạy lại `--apply`. Start.py mới tự kiểm tra và cài khi còn thiếu; nếu lỗi sẽ ghi log và tiếp tục. Xem [Cài đường âm thanh chung cho loa](README_INSTALL_SHARED_MEDIA_ROUTES.md).

## 6. Bật tính năng trên thiết bị mới

Cài mã VBot cùng các phụ thuộc Python/phần cứng như quy trình VBot hiện tại.
Trên WebUI thiết bị mới:

1. Bật WebRTC MIC nếu cần, đường dẫn wrapper:
   `/home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so`; AEC output `normal`.
2. Bật Call LAN, PJSIP runtime `/home/pi/VBot_AEC3/pjsip`.
3. Giữ ID mDNS riêng của từng loa; nhập cùng khóa chung và khai báo danh sách
   loa hai chiều. Không sao chép nguyên cấu hình/ID máy nguồn sang máy đích.
4. TCP điều khiển 5010/SIP UDP 5060 cố định, RTP bắt đầu 40000 theo cấu hình.
5. Chọn mic, thiết bị phát và các nút phù hợp; lưu rồi khởi động lại VBot.

```bash
cd /home/pi/VBot_Offline
python3 Start.py
```

Thử gọi hai chiều, tắt/bật mic, nhận/kết thúc và wake word khi phát nhạc.
Kiểm tra import chỉ chứng minh bộ native nạp được, chưa chứng minh thiết bị
âm thanh, mạng hoặc AEC đã được căn chỉnh đúng cho loa mới.

Nếu đổi Python major.minor, kiến trúc hoặc image OS, tạo bộ build mới theo
[README_INSTALL_WEBRTC21_PJSIP_MANUAL.md](README_INSTALL_WEBRTC21_PJSIP_MANUAL.md).
