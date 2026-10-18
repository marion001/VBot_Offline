# Cài đường âm thanh chung cho loa

Đây là bước cấu hình ALSA/AirPlay/Bluetooth/bridge, riêng với bước cài thư viện WebRTC 2.1 và PJSIP. Mỗi loa cần cài một lần; cấu hình vẫn có hiệu lực sau reboot.

## Điều kiện

- Đã cài hệ thống âm thanh VBot, Shairport Sync, BlueALSA, Bluetooth agent và dịch vụ multiroom bridge.
- Có snd-aloop (card Loopback). Start.py tự chuẩn bị module này khi khởi động.
- Dừng VBot và các chương trình test âm thanh trước khi cài thủ công.

## Cài thủ công

```bash
cd /home/pi/VBot_Offline
sudo python3 resource/vbot_aec3/install_media_routes.py \
  --root /home/pi/VBot_Offline --apply
```

Script sao lưu trước khi sửa `/etc/asound.conf`, cấu hình AirPlay, BlueALSA, bản Bluetooth agent đang cài và Config.json. Các dịch vụ âm thanh liên quan được khởi động lại; nhạc đang phát có thể bị ngắt. Nếu cài thất bại, script thử khôi phục bản sao lưu.

Sau khi báo `Shared media routes installed`, đường phát chung là `vbot_aec_media`, bridge đọc `plughw:Loopback,1,1`, và `external_sources` được bật. Không cần build lại thư viện. Bước chuẩn bị có thể sửa tham chiếu card ALSA và số kênh WM8960 cũ; mức âm lượng mixer được giữ nguyên.

## Tự kiểm tra khi khởi động VBot

Start.py chạy bước kiểm tra trước khi nạp VBot và khởi tạo mic. Nếu đã đủ cấu hình thì bỏ qua, không khởi động lại dịch vụ. Nếu chưa cài thì tự cài bằng `sudo -n`; nếu thiếu quyền, phụ thuộc hoặc có VBot khác đang chạy, sẽ ghi log lỗi và tiếp tục khởi động. Không tự nhập mật khẩu, không tự cài lại thư viện.

Nếu đường ALSA/AirPlay/Bluetooth đã đầy đủ nhưng Config.json chưa đồng bộ, script sao lưu rồi sửa riêng các giá trị đường âm thanh và `external_sources`, không khởi động lại dịch vụ. Nếu phần cấu hình hệ thống còn thiếu, script báo lỗi; dừng VBot, kiểm tra bản sao lưu và khôi phục trước khi cài lại.

## Sửa ALSA và đường âm thanh sau khi flash hoặc nâng cấp

Nếu `alsamixer` mở nhầm Loopback, WM8960 đổi số card hoặc mất tiếng sau reboot, có thể chạy công cụ sửa riêng mà không cần khởi chạy VBot.

Dừng VBot trước. Nếu chạy bằng `python3 Start.py`, nhấn Ctrl+C trong terminal đang chạy; nếu dùng service thì chạy lệnh dừng dưới đây. Sau đó sửa ALSA:

```bash
systemctl --user stop VBot_Offline.service
cd /home/pi/VBot_Offline
sudo python3 resource/tools/fix_audio_alsa.py
```

Công cụ đọc `/os_image_created.txt`: dòng image VBot có chữ `i2s` là image I2S; không có chữ này là image WM8960. Nó đối chiếu card thực tế trước khi sửa. WM8960 dùng tên cố định `wm8960soundcard`, thu phần cứng 2 kênh và mixer mặc định đúng card, tránh chọn nhầm Loopback khi thứ tự card thay đổi.

Công cụ giữ cấu hình riêng nhận diện được, mức âm lượng mixer và khối đường phát chung `vbot_aec_media`. Với service WM8960 cũ, nó thêm cấu hình bảo vệ để service không xóa ALSA sau reboot. Các dịch vụ âm thanh đang chạy được nạp lại nên nhạc sẽ ngắt tạm thời; công cụ không khởi chạy VBot.

Nếu cần khôi phục mẫu ALSA mặc định đúng image, dùng tùy chọn sau. Tùy chọn này thay phần cấu hình mic/loa tùy chỉnh bằng mẫu, nhưng vẫn giữ khối đường âm thanh chung đã có:

```bash
cd /home/pi/VBot_Offline
sudo python3 resource/tools/fix_audio_alsa.py --restore-default
```

Các bản sao lưu được ghi tại `/home/pi/VBot_AEC3/hardware_backups/`; đường dẫn cụ thể hiện trong kết quả. Thiếu file nhận diện, không thấy đúng card hoặc gặp cấu hình chưa xác định thì công cụ báo lỗi, không tự đoán card để ghi đè.

Cần có đầy đủ các file của bản cập nhật: `resource/tools/fix_audio_alsa.py`, `resource/vbot_aec3/hardware_routes.py` và các mẫu trong `resource/asound_conf/`. Công cụ ưu tiên `default_wm8960_asound_new.conf` hoặc `default_i2s_asound_new.conf` theo image; nếu chưa có thì dùng mẫu tên cũ. Công cụ dùng Python hệ thống, không cần build thêm `.so`.

Hai mẫu mới có sẵn khối media bus. Khi khôi phục, công cụ giữ một khối đã có, không chèn trùng. Khi chưa cài đường chung, bước sửa phần cứng chỉ lấy cấu hình mic/loa; bước cài đường chung bên trên sẽ cấu hình đồng bộ ALSA, AirPlay và Bluetooth.

Sau khi sửa, mở lại `alsamixer` để kiểm tra đúng card và xác minh đường chung:

```bash
cd /home/pi/VBot_Offline
sudo python3 resource/vbot_aec3/install_media_routes.py \
  --root /home/pi/VBot_Offline --ensure
python3 VBot_Multiroom_Bridge_Audio.py status
```

`fix_audio_alsa.py` sửa phần cứng ALSA và giữ/khôi phục khối media bus đã nhận diện; nó không thay thế bước cài mới toàn bộ đường AirPlay/Bluetooth ở trên. Nếu `--ensure` vẫn báo cấu hình hệ thống chưa đầy đủ, kiểm tra bản sao lưu trước khi cài lại; không tự xóa cấu hình đang có.

## Kiểm tra và khôi phục

```bash
cd /home/pi/VBot_Offline
python3 VBot_Multiroom_Bridge_Audio.py status
```

Kiểm tra `capture_device` là `plughw:Loopback,1,1` và không có lỗi passthrough. Thử phát AirPlay/Bluetooth khi VBot chưa chạy, sau đó chạy `python3 Start.py` để thử wake word và multiroom.

Bản sao lưu nằm trong `/home/pi/VBot_AEC3/routing_backups/`. Để khôi phục, dừng VBot rồi dùng đúng đường dẫn bản sao lưu mà lượt cài đã in ra:

```bash
sudo python3 resource/vbot_aec3/install_media_routes.py \
  --root /home/pi/VBot_Offline --restore /home/pi/VBot_AEC3/routing_backups/THU_MUC_SAO_LUU
```

Lần chạy Start.py tiếp theo sẽ kiểm tra và cài lại nếu đường chung còn thiếu.
