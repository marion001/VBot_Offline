# IR nội bộ Raspberry Pi cho VBot

Tính năng này cho phép VBot học và phát lệnh hồng ngoại bằng module IR kết nối trực tiếp với GPIO của Raspberry Pi. Hệ thống sử dụng kernel LIRC và lệnh `ir-ctl`, không sử dụng `pigpio`.

## 1. Yêu cầu phần cứng

- Raspberry Pi đang chạy VBot Offline.
- Module phát IR (TX), nên dùng transistor hoặc mạch khuếch đại để điều khiển LED IR.
- Module thu IR (RX) tương thích mức logic 3.3 V.
- Các module và Raspberry Pi phải nối chung GND.

Cấu hình GPIO mặc định:

| Chức năng | GPIO BCM | Chân vật lý |
| --- | ---: | ---: |
| Phát IR (TX) | GPIO17 | Pin 11 |
| Thu IR (RX) | GPIO4 | Pin 7 |

Lưu ý:

- Không sử dụng GPIO10 vì VBot đang dùng chân này để điều khiển LED.
- Không sử dụng GPIO16 khi dùng Google Voice HAT vì đây là chân điều khiển amplifier.
- GPIO phát và GPIO thu không được trùng nhau.
- Không cấp tín hiệu 5 V trực tiếp vào GPIO của Raspberry Pi.

## 2. Kích hoạt và chọn GPIO trên WebUI

Mở WebUI VBot, vào:

```text
Cấu hình → IR Nội Bộ Raspberry Pi
```

Tại đây có thể cấu hình độc lập:

- **Kích hoạt IR phát (TX):** cho phép WebUI và VBot phát lệnh IR.
- **Kích hoạt IR thu (RX):** cho phép học lệnh từ remote.
- **Dùng IR thu điều khiển loa:** chạy luồng nhận nền và thực hiện chức năng được gán cho mã remote. Công tắc luôn hiển thị nhưng bị vô hiệu hóa khi IR thu (RX) chưa được bật.
- **Tạm khóa RX sau khi phát:** thời gian bỏ qua tín hiệu thu sau TX để mắt thu không nhận lại mã do chính VBot vừa phát; mặc định 500 ms, khuyến nghị 300–700 ms.
- **GPIO phát IR (BCM):** GPIO kết nối với module phát.
- **GPIO thu IR (BCM):** GPIO kết nối với module thu.

Có thể bật riêng TX, riêng RX hoặc bật đồng thời cả hai. Sau khi thay đổi, nhấn nút lưu cấu hình trước khi chạy bộ cài.

Thông số của luồng điều khiển nền:

- **Ngưỡng khớp mã IR:** từ `0.40` đến `0.99`, mặc định `0.92`. Mã tốt nhất phải đạt tối thiểu giá trị này mới được thực thi.
- **Khoảng cách phân biệt mã:** từ `0.00` đến `0.30`, mặc định `0.06`. Điểm của mã tốt nhất phải cao hơn mã đứng thứ hai ít nhất mức này; nhờ vậy các nút có header hoặc khung IR gần giống nhau không bị nhận nhầm.
- **Số xung tối thiểu:** từ `7` đến `500`, mặc định `20`. Tín hiệu ngắn, frame bị thu thiếu hoặc nhiễu vụn sẽ bị bỏ qua.
- **Chống lặp remote:** từ `100` đến `3000` ms, mặc định `450` ms. Tăng giá trị nếu một lần nhấn bị thực thi nhiều lần.

Khi một tín hiệu gần đạt nhưng chưa đủ an toàn, VBot chỉ hiển thị chẩn đoán trên console và không ghi thông báo từ chối này vào file log, ví dụ:

```text
[Internal IR RX] Bỏ qua tín hiệu: hai mã quá giống nhau, điểm tốt nhất 0.914, mã thứ hai 0.898, khoảng cách 0.016/0.060, số xung 67
```

Sau khi thay đổi các thông số nhận dạng, cần khởi động lại VBot để luồng RX nền đọc cấu hình mới.

Các giá trị tương ứng được lưu trong `Config.json`:

```json
"internal_ir": {
  "tx_active": true,
  "rx_active": true,
  "rx_control_active": true,
  "backend": "ir_ctl",
  "tx_gpio": 17,
  "rx_gpio": 4,
  "tx_device": "/dev/lirc0",
  "rx_device": "/dev/lirc1",
  "receive_match_threshold": 0.92,
  "receive_match_margin": 0.06,
  "receive_minimum_pulses": 20,
  "receive_debounce_ms": 450,
  "tx_rx_guard_ms": 500
}
```

## 3. Cài đặt và thiết lập LIRC

Đăng nhập SSH vào Raspberry Pi bằng user `pi`, sau đó chạy lần lượt:

```bash
cd /home/pi/VBot_Offline/resource/internal_ir
chmod 0777 install_internal_ir.sh
./install_internal_ir.sh
```

Script sẽ tự động:

- Xin quyền `sudo` khi cần.
- Cài `v4l-utils` để cung cấp lệnh `ir-ctl`.
- Đọc GPIO TX/RX từ `Config.json`.
- Kiểm tra GPIO không trùng và không sử dụng chân đã bị VBot chiếm dụng.
- Cấu hình `gpio-ir` và `gpio-ir-tx` trong file cấu hình boot.
- Cấp quyền truy cập thiết bị LIRC cho user VBot và WebUI.

Sau khi script chạy hoàn tất, bắt buộc khởi động lại hệ thống:

```bash
sudo reboot
```

## 4. Kiểm tra thiết bị sau khi reboot

Kiểm tra các thiết bị LIRC:

```bash
ls -l /dev/lirc*
```

Khi bật cả TX và RX, thông thường hệ thống có:

```text
/dev/lirc0  thiết bị phát IR
/dev/lirc1  thiết bị thu IR
```

Kiểm tra tính năng từng thiết bị:

```bash
ir-ctl --features -d /dev/lirc0
ir-ctl --features -d /dev/lirc1
```

Thiết bị TX cần hiển thị khả năng `Device can send raw IR`. Thiết bị RX cần hiển thị khả năng `Device can receive raw IR`.

## 5. Học và quản lý lệnh bằng WebUI

Mở trang:

```text
Internal_IR.php
```

Quy trình học lệnh:

1. Bật **IR thu (RX)** trong cấu hình.
2. Nhập tên lệnh và câu phản hồi nếu cần.
3. Nhấn **Học lệnh**.
4. Hướng remote vào module thu IR và nhấn nút cần học.
5. Kiểm tra mã IR JSON xuất hiện trên giao diện.
6. Nhấn **Thử phát** để kiểm tra.
7. Nhấn **Thêm lệnh** để lưu.

Trong bảng lệnh có thể:

- Bật hoặc tắt từng câu lệnh bằng công tắc **Kích hoạt**.
- Sửa trực tiếp tên lệnh.
- Sửa trực tiếp câu phản hồi.
- Chọn chức năng tự động khi module thu nhận đúng mã IR: đánh thức VBot, điều khiển âm lượng, microphone, chế độ hội thoại, media hoặc phát một Playlist cụ thể.
- Sửa trực tiếp mã IR JSON.
- Nhấn **Gửi** để phát thử mã của dòng hiện tại.
- Nhấn **Xóa** để xóa lệnh.
- Nhấn **Lưu toàn bộ cấu hình** để lưu tất cả thay đổi.

Khi IR thu (RX) được bật, VBot chạy một luồng nhận nền độc lập. Luồng này so khớp mã remote với các lệnh đang kích hoạt và thực thi lựa chọn trong cột **Chức năng khi nhận**. Chức năng học trên WebUI sẽ tạm chiếm thiết bị RX; luồng nền tự dừng đọc trong thời gian học và tiếp tục sau khi học xong. Khi phát IR, VBot cũng khóa RX trong toàn bộ thời gian TX và giữ thêm khoảng bảo vệ đã cấu hình, áp dụng cho cả lệnh giọng nói lẫn nút phát trên WebUI.

Các chức năng có thể gán gồm:

- Đánh thức VBot.
- Tăng, giảm, đặt âm lượng lớn nhất hoặc nhỏ nhất.
- Bật/tắt microphone và chế độ hội thoại.
- Bật/tắt chế độ câu phản hồi khi wakeup.
- Dừng câu trả lời TTS đang phát.
- Hủy quá trình wakeup/thu âm hiện tại.
- Phát/tạm dừng, dừng, chuyển bài tiếp theo hoặc bài trước.
- Tắt tiếng loa (Mute) hoặc mở tiếng loa (Unmute).
- Phát toàn bộ thư viện nhạc Local.
- Phát trực tiếp một Playlist đang có trong kho Playlist của WebUI. Danh sách lựa chọn được đọc tự động từ `html/includes/cache/PlayLists.json`, vì vậy Playlist mới sẽ xuất hiện sau khi tải lại trang IR.
- Phát một kênh Radio cụ thể đang có trong `Config.json`.
- Khởi động lại service VBot hoặc reboot Raspberry Pi.

## 6. Dùng hành động VBot trong Scheduler

Trang `Scheduler.php` sử dụng chung bộ ánh xạ hành động với IR Remote. Trong mục **Các Tác Vụ Sẵn Có Trên Hệ Thống**, từng hành động được hiển thị trực tiếp trong danh sách loại tác vụ; không cần chọn một lớp trung gian.

Các Playlist và Radio được nạp động giống trang IR. Scheduler gọi chung `Button.trigger_internal_ir_action()`, vì vậy hành vi của lệnh chạy theo lịch và lệnh nhận từ remote được giữ đồng nhất.

Các lịch cũ dùng `play_all_music_local`, `stop_media_player`, `restart_vbot` hoặc `reboot_os` vẫn được đọc. Khi người dùng lưu lại Scheduler, các lịch này được chuyển sang cấu trúc `vbot_action` mới và cấu hình cũ được vô hiệu hóa để tránh một hành động chạy hai lần.

## 7. Hỗ trợ Cloudflare Tunnel

WebUI quản lý IR nội bộ có thể được truy cập từ bên ngoài mạng LAN thông qua trang [Cloudflare Tunnel](../../html/Cloudflare_Tunnel.php). Tính năng này chỉ chuyển tiếp WebUI qua HTTPS; thiết bị LIRC và GPIO vẫn được điều khiển cục bộ trên Raspberry Pi, không được công khai trực tiếp ra Internet.

Để sử dụng:

1. Vào **Cấu Hình Config → Cấu Hình Web Interface/Cloudflare Tunnel (Giao Diện)** và bật **Cho Phép Truy Cập Bên Ngoài Internet**.
2. Mở trang **Cloudflare Tunnel**, tạo hoặc kích hoạt hồ sơ Quick Tunnel/Domain riêng trỏ tới WebUI VBot.
3. Bật xác thực đăng nhập WebUI trước khi cho phép truy cập từ Internet.
4. Sau khi Tunnel hoạt động, mở `Internal_IR.php` qua domain Cloudflare để học, quản lý hoặc phát lệnh IR từ xa.

Cloudflare Tunnel không thay thế bước cài đặt `install_internal_ir.sh`, cấu hình GPIO hoặc quyền truy cập `/dev/lirc*`. Các thành phần này vẫn phải được thiết lập trực tiếp trên Raspberry Pi.

Dữ liệu lệnh được lưu tại:

```text
resource/internal_ir/commands.json
```

## 8. Sử dụng bằng dòng lệnh

File CLI trung gian là:

```text
resource/internal_ir/internal_ir_cli.py
```

Xem các lệnh được hỗ trợ:

```bash
cd /home/pi/VBot_Offline
python3 resource/internal_ir/internal_ir_cli.py --help
```

Kiểm tra TX và RX:

```bash
python3 resource/internal_ir/internal_ir_cli.py status \
  --tx-device /dev/lirc0 \
  --rx-device /dev/lirc1
```

Học một lệnh IR trong tối đa 30 giây:

```bash
python3 resource/internal_ir/internal_ir_cli.py learn \
  --device /dev/lirc1 \
  --timeout 30 \
  --carrier 38000
```

Kết quả được trả về dưới dạng JSON trên một dòng. Trường `data` chứa mã IR có thể lưu vào `commands.json` hoặc gửi lại qua WebUI.

Lệnh phát yêu cầu tham số `--data` là JSON IR được mã hóa URL-safe Base64. WebUI tự thực hiện bước mã hóa và gọi lệnh này, vì vậy nên sử dụng nút **Gửi** hoặc **Thử phát** trên WebUI để tránh sai dữ liệu.

## 9. Sử dụng module `Internal_IR.py` hoặc `.so`

`internal_ir_cli.py` không chứa phần xử lý IR. File này thêm thư mục gốc VBot vào `sys.path`, sau đó thực hiện:

```python
import Internal_IR
```

Do đó hệ thống hỗ trợ cả hai dạng:

```text
/home/pi/VBot_Offline/Internal_IR.py
/home/pi/VBot_Offline/Internal_IR.<ABI>.so
```

Nếu biên dịch thành `.so`:

- Tên module bắt buộc phải là `Internal_IR`.
- Phải biên dịch đúng kiến trúc Raspberry Pi và đúng phiên bản Python đang chạy.
- Module phải cung cấp các hàm `initialize()`, `learn()`, `send()`, `status()`, `receive_once()`, `match_command()` và `main()`.
- Chỉ xóa hoặc đổi tên `Internal_IR.py` sau khi đã kiểm tra import `.so` thành công.

Kiểm tra module đang được import từ đâu:

```bash
cd /home/pi/VBot_Offline
python3 -c "import Internal_IR; print(Internal_IR.__file__)"
```

Kiểm tra các hàm bắt buộc:

```bash
python3 -c "import Internal_IR; print(all(callable(getattr(Internal_IR, name, None)) for name in ('initialize','learn','send','status','receive_once','match_command','main')))"
```

Kết quả mong đợi là `True`.

## 10. Xử lý lỗi thường gặp

### Bấm nút remote nhưng bị nhận nhầm hoặc bị từ chối

- Giữ cấu hình khuyến nghị ban đầu: ngưỡng `0.92`, khoảng cách phân biệt `0.06`, tối thiểu `20` xung.
- Nếu bấm đúng nhưng thường xuyên bị từ chối, thử giảm ngưỡng xuống `0.90` hoặc khoảng cách xuống `0.04–0.05`.
- Nếu vẫn bị nhận nhầm, học lại các nút trong cùng điều kiện khoảng cách và hướng remote, đồng thời tránh giữ nút quá lâu.
- Dòng `Bỏ qua tín hiệu` trên console cho biết điểm tốt nhất, điểm mã thứ hai và khoảng cách thực tế để hiệu chỉnh.

### Không có `/dev/lirc0` hoặc `/dev/lirc1`

Chạy lại `install_internal_ir.sh`, sau đó reboot. Kiểm tra các dòng `dtoverlay=gpio-ir` và `dtoverlay=gpio-ir-tx` trong `/boot/config.txt` hoặc `/boot/firmware/config.txt`.

### Hết thời gian chờ tín hiệu IR

- Kiểm tra đã bật IR thu (RX).
- Kiểm tra đúng GPIO và dây tín hiệu module thu.
- Kiểm tra GND chung.
- Hướng remote gần module thu và giữ nút trong khoảng một giây.

### Không phát được IR

- Kiểm tra đã bật IR phát (TX).
- Kiểm tra `/dev/lirc0` có khả năng phát raw IR.
- Kiểm tra transistor, LED IR, điện trở và nguồn cấp của module phát.
- Dùng camera điện thoại để quan sát LED IR khi phát thử.

### WebUI không có quyền truy cập LIRC

Kiểm tra user `www-data` thuộc group `video`:

```bash
groups www-data
```

Nếu chưa có, chạy lại bộ cài và reboot hệ thống.
