# EQ loa 6 dải

## File và dữ liệu

- `settings.json`: thông số đang nghe, danh sách mẫu theo tên, mẫu đang chọn và mẫu mặc định.
- `libvbot_eq.so`: thư viện EQ native build trên Pi; đặt ngay trong thư mục này.
- `equalizer.cpp`, `build_eq.sh`: nguồn và lệnh build EQ.
- `Config.json` chỉ giữ `media_player.equalizer.active` để bật/tắt sử dụng EQ.

Không đưa dữ liệu preset cá nhân vào bản phát hành; `settings.json` mẫu đi kèm chỉ có EQ phẳng. Khi cập nhật thiết bị đã có dữ liệu, giữ file `settings.json` riêng của loa. Công cụ cập nhật mặc định giữ JSON người dùng đã tồn tại.

## Cài hoặc nâng cấp

```bash
cd /home/pi/VBot_Offline
sh resource/equalizer/build_eq.sh
```

Không cần build lại WebRTC/PJSIP. Script build EQ tại `resource/equalizer/libvbot_eq.so`, chuẩn bị quyền ghi dữ liệu cho WebUI và chuyển các giá trị EQ cũ từ Config.json sang file riêng nếu cần. Không ghi đè danh sách mẫu đã lưu. Cấu hình cũ được sao lưu trong `/home/pi/VBot_AEC3/eq_config_backups/` khi chuyển đổi.

Nếu chưa có đường âm thanh chung:

```bash
systemctl --user stop VBot_Offline.service
sudo python3 resource/vbot_aec3/install_media_routes.py --root /home/pi/VBot_Offline --ensure
```

Dừng VBot chạy thủ công bằng Ctrl+C trước khi cài routing. Sau khi nâng cấp code hoặc thư viện, khởi động lại đường phát một lần:

```bash
sudo systemctl restart vbot-multiroom-bridge.service
```

Nếu đang chạy VBot, dừng và chạy lại để các luồng âm thanh của VBot nạp code mới. Các lần chỉnh thông số sau đó không cần restart.

## Chỉnh EQ và lưu mẫu

Mở trang chủ `index.php`, mục **EQ loa — 6 dải** ngay dưới Media Player và trên Phát Thông Báo. `Config.php` giữ công tắc bật/tắt cùng nút dẫn tới giao diện này:

1. Bật EQ. Kéo thanh hoặc dùng nút +/− theo bước 0,5 dB; có thể nhập số trực tiếp. Thông số tự lưu và áp dụng sau khoảng 0,5–1 giây, chưa ghi đè mẫu. Đồ thị chỉ thể hiện mức chỉnh của từng dải, không phải phổ âm thanh đo được.
2. Nhập tên rồi nhấn **Lưu mẫu mới**. Tối đa 50 mẫu, tên tối đa 80 ký tự.
3. Chọn mẫu trong select để áp dụng ngay. Sửa thanh kéo hoặc tên rồi **Cập nhật mẫu** để ghi đè mẫu đã chọn.
4. **Xóa mẫu** xóa mẫu đang chọn; phải giữ ít nhất một mẫu. Nếu xóa mẫu mặc định/đang dùng, hệ thống chọn mẫu còn lại phù hợp.
5. **Đặt làm mặc định** chọn mẫu sẽ được áp dụng sau reboot loa. Trong phiên hiện tại, vẫn có thể nghe mẫu khác hoặc chỉnh tay. Khởi động lại riêng VBot không làm mất lựa chọn trong cùng phiên boot.

**Đưa EQ về phẳng** đưa các giá trị hiện tại về 0 dB, không xóa hay ghi đè mẫu đã lưu. **Áp dụng ngay** dùng để gửi lại khi có lỗi mạng. Không cần nút Lưu toàn bộ Config.php khi chỉnh trực tiếp.

WebUI ghi file trực tiếp qua PHP, có xác thực và CSRF; không gọi API hay phụ thuộc tiến trình VBot. Khi VBot dừng, dịch vụ `vbot-multiroom-bridge.service` vẫn phát AirPlay/Bluetooth và đọc EQ mới. Không cần bật microphone/AEC để dùng EQ.

## Đường tín hiệu

`PCM → EQ từng loa → ALSA → phần PCM ALSA đã nhận → tham chiếu AEC`.

# EQ 6 dải và Wet/Dry

Các dải: 100, 250, 630, 1.600, 4.000 và 10.000 Hz. Gain ±12 dB, bước 0,5 dB.

Mức áp dụng EQ (Wet/Dry) nằm dưới Preamp. Mặc định 100%; 0% là âm gốc, 100% là âm qua EQ. Preamp áp dụng sau khi trộn. Nhánh EQ vẫn tự giảm gain theo các dải tăng để giữ khoảng dự phòng. Vì vậy thay đổi Wet/Dry có thể thay đổi cả âm sắc và âm lượng.

Wet/Dry được lưu cùng từng mẫu trong settings.json; mẫu cũ bổ sung 100%, dải Air bổ sung 0 dB. Các gain cũ được giữ theo vị trí dải nhưng tần số đã thay đổi, nên cần nghe và chỉnh lại mẫu cũ. Công cụ migrate sao lưu dữ liệu trước khi chuyển đổi.

Build bằng `sh resource/equalizer/build_eq.sh`. Sau cập nhật, dừng VBot, khởi động lại bridge rồi chạy VBot lại một lần. Những lần chỉnh sau áp dụng trực tiếp, không cần khởi động lại.


Thông số được đọc mỗi 250 ms ngoài luồng PCM; chuyển bộ lọc bằng crossfade 30 ms. Từng loa Multiroom xử lý riêng ở đầu ra, không xử lý EQ tại luồng truyền nhóm. Gọi LAN và mic bỏ qua EQ. Thiếu/lỗi thư viện giữ đường âm thanh hiện tại và ghi log; EQ không sửa được âm thanh đã clipping trước đó hoặc méo vật lý của loa.

Gói ZIP WebRTC/PJSIP chưa bao gồm thư viện EQ. Cần test nguồn VLC, AirPlay, Bluetooth, Multiroom và wake word trên chính bản build trước phát hành.
