# VBot HomeKit

Bridge này công bố một `VBot HomeKit Bridge` và các accessory logic bên dưới trong
Apple Home. Loa dùng `SmartSpeaker`; các nhóm Media, Giọng nói, Playlist, Radio và
Hệ thống dùng service chuẩn để điều khiển API VBot hiện hữu. Shairport Sync tiếp tục
phụ trách AirPlay/AirPlay 2.

Mã bridge và cấu hình `homekit` nằm trong `/home/pi/VBot_Offline`; `node_modules`,
`package.json`, `package-lock.json` và pairing được đặt tại
`/home/pi/VBot_Node/HomeKit`. Service nạp dependency qua biến `NODE_PATH` và đọc
chung `/home/pi/VBot_Offline/Config.json`.

`homekit.accessory_type` chọn cách công bố phần media chính: `speaker` dùng
`SmartSpeaker`, `television` dùng giao diện Remote/Television, `switches` chỉ dùng
các nút tương thích rộng, và `hybrid` công bố đồng thời Speaker + Remote. Các nhóm
Playlist, Radio, Multiroom, Voice, Trợ lý và Trạng thái không bị mất khi đổi kiểu.

## Yêu cầu

- Raspberry Pi và iPhone cùng LAN, mDNS/UDP 5353 không bị chặn.
- VBot API đang chạy (mặc định `127.0.0.1:5002`).
- Script tự kiểm tra và cài Node.js `22.22.3` chính thức cùng npm nếu máy chưa
  có hoặc đang dùng phiên bản khác. Gói tải về được xác minh bằng SHA256 từ
  `nodejs.org`; hỗ trợ ARMv7, ARM64 và x64.

## Cài đặt

```bash
cd /home/pi/VBot_Offline/resource/HomeKit
chmod +x install.sh
./install.sh
```

Trình cài đặt chỉ cài Node.js/dependency, chép unit và để `vbot-homekit.service` ở
trạng thái `disabled/inactive`. Nó không khởi động hoặc restart VBot. Khi cần kiểm tra,
hãy chủ động chạy `systemctl --user start VBot_Offline.service`; VBot sẽ khởi chạy
HomeKit sau khi API sẵn sàng nếu `homekit.active=true`.

Có thể chỉ định một bản Node 22 khác khi cần:

```bash
NODE_VERSION=22.22.3 ./install.sh
```

Bridge đọc trực tiếp mục `homekit` cùng `api.port`, `api.auth.active` và
`api.auth.api_key` từ `/home/pi/VBot_Offline/Config.json`, vì vậy không còn file
`/home/pi/VBot_Node/HomeKit/config.json`. Các giá trị runtime liên quan được lấy như sau:

- Tên HomeKit lấy từ `contact_info.full_name`.
- Firmware lấy từ trường `version` trong `/home/pi/VBot_Offline/Version.json`.
- SSE luôn dùng `/?type=1&data=all_info&stream=sse&interval=1` để đồng bộ cả
  media, micro, wakeup và chế độ hội thoại.
- Pairing luôn lưu tại `/home/pi/VBot_Node/HomeKit/persist`.

Sau khi thay đổi cấu hình, hãy lưu rồi khởi động lại VBot để Bridge được dừng và
khởi chạy lại theo đúng vòng đời chung. Không cần chạy lại `install.sh` và không
cần ghép đôi lại. Có thể xem log Node tại `resource/log/service_log.log`; lệnh
`journalctl --user -u vbot-homekit.service -f` chỉ có dữ liệu khi tiến trình thật
sự được systemd user service quản lý.

Sau khi cài đặt, `vbot-homekit.service` không được enable độc lập. Khi VBot khởi động,
nó đọc `homekit.active`: chỉ start HomeKit sau khi API sẵn sàng; khi VBot dừng hoặc
restart, HomeKit cũng dừng theo nhờ lifecycle cleanup và `PartOf=VBot_Offline.service`.
Có thể bật/tắt và sửa thông số HomeKit trực tiếp trong trang `Config.php`, sau đó chọn
lưu và khởi động lại VBot.

Sau khi bridge chạy, mã QR được tạo tại
`/home/pi/VBot_Node/HomeKit/HomeKit_Pairing_QR.svg`. Trong trang cấu hình, bấm
**Mã QR** cạnh ô Pairing rồi mở app Nhà: **+ → Thêm phụ kiện** và quét mã; vẫn có
thể nhập PIN thủ công. Cảnh báo phụ kiện chưa chứng nhận là bình thường đối với
phụ kiện HAP tự xây dựng.

Bridge chỉ cần ghép đôi một lần. Sau khi ghép, Apple Home nhận các accessory con:
`VBot Assistant`, `VBot Media`, `VBot Giọng nói`, `VBot Playlist`, `VBot Radio`,
`VBot Trợ lý AI`, `VBot Trạng thái` và `VBot Hệ thống`. Nhóm Radio không được quảng
bá nếu chưa có đài nào trong Config.json.

Các switch trạng thái thật được đồng bộ hai chiều qua SSE gồm câu phản hồi Wakeup,
đánh thức khi phát media, nhiều câu lệnh, tiếp tục nghe, cache TTS, Media Player và
các trợ lý ChatGPT, Gemini, Xiaozhi, mặc định, Olli, Zalo, Dify, trợ lý tùy chỉnh.
Accessory Trạng thái có cảm biến trực tuyến và cảm biến VBot đang hoạt động.

Các tích hợp mở rộng được khai báo tại `resource/HomeKit/homekit_registry.json`.
Registry hiện dựng tự động slider độ sáng LED; switch cho nguồn Local, Podcast,
Radio, YouTube, Zing MP3, NhacCuaTui; và cảm biến trạng thái AirPlay, Bluetooth,
Multiroom. Engine hỗ trợ `switch`, `brightness` và `occupancy`; mỗi ID/subtype phải
ổn định để giữ nguyên IID HomeKit qua các lần khởi động.

Bridge không tự theo dõi hoặc restart khi `Config.json`, Action Registry, HomeKit
Registry, Playlist hay cấu hình Multiroom thay đổi. Dữ liệu topology mới được nạp
ở lần VBot/HomeKit khởi động kế tiếp, nhằm tránh gián đoạn kết nối và việc iOS gửi
dồn các thao tác trong thời gian bridge khởi động lại.

Nhóm trạng thái còn có cảm biến chờ Wakeup, thu âm, nhận dạng STT, trợ lý đang xử
lý, phát TTS, phát media, MQTT được kích hoạt/đang kết nối và vai trò Multiroom
Coordinator/Client. Tất cả dùng chung snapshot SSE, không tạo polling riêng.

## Dữ liệu bền vững

Pairing được lưu tại `/home/pi/VBot_Node/HomeKit/persist`. Không xóa thư mục
`/home/pi/VBot_Node/HomeKit` khi cập nhật VBot. `username: auto` và Serial Number
dùng OTP phần cứng Raspberry Pi từ hàng `28:` của `vcgencmd otp_dump`, tránh
trùng ID giữa nhiều loa dùng chung OS image và `machine-id`. MAC phần cứng được
dùng làm fallback nếu thiết bị không hỗ trợ `vcgencmd`. Nếu đổi Serial hoặc
`username`, có thể phải xóa phụ kiện cũ trong Apple Home và pair lại.

## Kiểm tra

```bash
/usr/local/bin/node --test /home/pi/VBot_Offline/resource/HomeKit/test/*.test.js
systemctl --user status vbot-homekit.service --no-pager
avahi-browse -rt _hap._tcp
```

Bridge dùng SSE `all_info` hiện có làm kênh đồng bộ chính nên thay đổi được
đẩy sang HomeKit ngay khi snapshot đổi. `GET` chỉ được gọi một lần như phương án
dự phòng khi SSE ngắt và đang chờ kết nối lại. Giá trị trùng lặp không được đẩy
lại sang HomeKit, nhờ đó tránh vòng lặp Volume/Mute.

Do Apple Home có thể chỉ đọc `SmartSpeaker` DIY và hiện `Controls Unavailable`,
bridge công bố thêm các service điều khiển chuẩn trong cùng phụ kiện: **Phát**,
**Tắt tiếng**, **Âm lượng**, **Đánh thức**, **Micro** và **Hội thoại**. Tile
SmartSpeaker chính vẫn cung cấp trạng thái; thanh Brightness của service Âm lượng
tương ứng trực tiếp với 0–100% volume. Đánh thức là công tắc dạng nút nhấn và tự
trở về Off sau khi gọi API.

Tên các control không lặp lại tên loa: `Phát / Tạm dừng`, `Tắt tiếng`, `Âm
lượng`, `Đánh thức VBot`, `Micro` và `Chế độ hội thoại`.

`Đánh Thức VBot` được công bố thành một accessory/tile độc lập, không nằm trong
nhóm Giọng nói, Media hoặc Hệ thống. Đây là nút momentary: gọi API wakeup ngay
khi bật rồi tự trở về Off.

Ngoài các control trạng thái trên, bridge tự đọc `resource/action_registry.json` và tạo
nút nhấn cho toàn bộ VBot action. Danh sách bao gồm điều khiển media, âm lượng, mic,
hội thoại, câu phản hồi, đọc giờ/ngày/IP, cập nhật, restart và reboot. Playlist được đọc
từ `html/includes/cache/PlayLists.json`; radio được đọc từ `media_player.radio_data`
trong `Config.json`. Mỗi nút gọi API `type=2`, `data=vbot_action`, sau đó tự trở về Off.
Vì danh sách được đọc lúc bridge khởi động, sau khi thêm playlist/radio hãy khởi động lại VBot.

Giao diện Remote iOS có thể phát nhiều mã HAP cho cùng vùng điều hướng. Bridge
gom `ARROW_LEFT`/`PREVIOUS_TRACK`/`REWIND` vào cấu hình **Phím Trái** và gom
`ARROW_RIGHT`/`NEXT_TRACK`/`FAST_FORWARD` vào **Phím Phải**. Hai phím Volume trên
WebUI là phím âm lượng vật lý cạnh iPhone khi Remote đang mở. Phím Mute/Unmute
cũng hỗ trợ đủ sáu trạng thái. Trong một chuỗi bấm liên tiếp, trạng thái được giữ
ổn định để âm báo ngắn không làm action kế tiếp bị chuyển nhầm sang cấu hình TTS.
