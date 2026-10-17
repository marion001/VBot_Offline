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

Sau khi báo `Shared media routes installed`, đường phát chung là `vbot_aec_media`, bridge đọc `plughw:Loopback,1,1`, và `external_sources` được bật. Không cần build lại thư viện. Không đổi mic hoặc mức âm lượng mixer.

## Tự kiểm tra khi khởi động VBot

Start.py chạy bước kiểm tra trước khi nạp VBot và khởi tạo mic. Nếu đã đủ cấu hình thì bỏ qua, không khởi động lại dịch vụ. Nếu chưa cài thì tự cài bằng `sudo -n`; nếu thiếu quyền, phụ thuộc hoặc có VBot khác đang chạy, sẽ ghi log lỗi và tiếp tục khởi động. Không tự nhập mật khẩu, không tự cài lại thư viện.

Nếu đường ALSA/AirPlay/Bluetooth đã đầy đủ nhưng Config.json chưa đồng bộ, script sao lưu rồi sửa riêng các giá trị đường âm thanh và `external_sources`, không khởi động lại dịch vụ. Nếu phần cấu hình hệ thống còn thiếu, script báo lỗi; dừng VBot, kiểm tra bản sao lưu và khôi phục trước khi cài lại.

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
