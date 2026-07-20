# VBot Assistant

VBot Assistant là hệ thống loa thông minh tiếng Việt chạy trên Raspberry Pi, được tối ưu cho Raspberry Pi Zero 2W. Dự án kết hợp nhận diện từ khóa đánh thức, Speech-to-Text (STT), xử lý lệnh, trợ lý AI, Text-to-Speech (TTS), phát media, Home Assistant, MQTT, AirPlay, Bluetooth, WebSocket client và giao diện quản trị WebUI.

Repository này chứa chương trình Python, WebUI PHP, tài nguyên âm thanh, cấu hình dịch vụ và bộ kiểm thử chạy được trên Windows trước khi triển khai thực tế lên Raspberry Pi.

> VBot tương tác trực tiếp với microphone, ALSA, GPIO, LED, DBus, Bluetooth và Shairport Sync. Kiểm thử trên Windows chỉ xác nhận logic và cú pháp; các chức năng phần cứng vẫn phải được kiểm tra trên Raspberry Pi.

## Mục lục

- [Tính năng chính](#tính-năng-chính)
- [Phần cứng và nền tảng](#phần-cứng-và-nền-tảng)
- [Kiến trúc chương trình](#kiến-trúc-chương-trình)
- [Luồng xử lý giọng nói](#luồng-xử-lý-giọng-nói)
- [Audio, AirPlay và Bluetooth](#audio-airplay-và-bluetooth)
- [WebSocket Streaming](#websocket-streaming)
- [REST API, SSE và MQTT](#rest-api-sse-và-mqtt)
- [Runtime Diagnostics và Watchdog](#runtime-diagnostics-và-watchdog)
- [WebUI](#webui)
- [Cài đặt và khởi chạy](#cài-đặt-và-khởi-chạy)
- [Cấu hình](#cấu-hình)
- [Kiểm thử](#kiểm-thử)
- [Xử lý sự cố](#xử-lý-sự-cố)
- [Phát triển và mở rộng](#phát-triển-và-mở-rộng)
- [Sao lưu và an toàn dữ liệu](#sao-lưu-và-an-toàn-dữ-liệu)
- [Liên hệ](#liên-hệ)

## Tính năng chính

### Trợ lý giọng nói tiếng Việt

- Nhận diện từ khóa đánh thức bằng Porcupine hoặc Snowboy, tùy cấu hình.
- Thu âm và nhận dạng giọng nói qua nhiều nguồn STT.
- Tổng hợp giọng nói qua nhiều nguồn TTS.
- Chế độ hội thoại liên tục.
- Cho phép hủy phiên thu âm bằng nút WakeUp hoặc nút microphone.
- Phát âm báo khi bắt đầu, kết thúc hoặc hủy thu âm.
- Hỗ trợ xử lý nhiều lệnh trong một câu.

### Trợ lý AI và xử lý lệnh

- Trợ lý mặc định của VBot.
- Google Gemini, ChatGPT, XiaoZhi và các nguồn trợ lý được bật trong `Config.json`.
- Home Assistant Assist và câu lệnh Home Assistant tùy chỉnh.
- Các điểm mở rộng `Dev_*.py` dành cho logic cá nhân.
- Phân tích từ khóa thông qua `Action.json`, `Adverbs.json` và `Object.json`.

### Media và kết nối âm thanh

- Nhạc cục bộ.
- Zing MP3, NhacCuaTui, YouTube, radio, podcast và tin tức, tùy nguồn còn hoạt động và được bật.
- AirPlay thông qua Shairport Sync.
- Bluetooth Audio Sink.
- Tạm dừng, tiếp tục đúng vị trí và dừng media nội bộ.
- Điều khiển âm lượng, mute/unmute và đồng bộ trạng thái lên WebUI/API.
- Hiển thị nguồn đang phát qua `media_player_source`.

### Nhà thông minh

- Home Assistant REST/WebSocket tùy cấu hình.
- MQTT command/state topic.
- Broadlink IR/RF.
- Custom command cho thiết bị, script và automation.
- Tích hợp tham khảo:
  - [VBot Offline Custom Component](https://github.com/marion001/VBot_Offline_Custom_Component)
  - [VBot Assist Conversation](https://github.com/marion001/VBot-Assist-Conversation)

### Hệ thống và tiện ích

- Scheduler cho thông báo, báo thức, phát media, thay đổi âm lượng, LED và microphone.
- Nút nhấn và rotary encoder.
- LED báo trạng thái chờ, nghe, xử lý, phát âm thanh, mute, pause, lỗi và thay đổi âm lượng.
- Zeroconf/mDNS để phát hiện thiết bị trong LAN.
- WebUI cấu hình, xem log, quản lý client và cập nhật chương trình.
- Backup cấu hình khi người dùng lưu từ WebUI.
- Runtime diagnostics và watchdog nhẹ cho các luồng quan trọng.

## Phần cứng và nền tảng

### Thiết bị khuyến nghị

- Raspberry Pi Zero 2W.
- Thẻ nhớ chất lượng tốt và nguồn điện ổn định.
- ReSpeaker 2-Mics Pi HAT, VBot AIO, Vietbot AIO hoặc microphone I2S tương thích.
- WM8960, MAX98357 hoặc thiết bị audio ALSA tương thích.
- LED WS2812B/APA102 theo cấu hình phần cứng.
- Nút WakeUp, nút microphone, nút âm lượng hoặc rotary encoder nếu sử dụng.

### Hệ điều hành

Dự án được thiết kế để chạy trên Linux/Raspberry Pi OS với các dịch vụ hệ thống liên quan đến:

- ALSA/audio output.
- DBus system bus.
- Bluetooth/BlueZ/BlueALSA.
- Shairport Sync cho AirPlay.
- Apache/PHP cho WebUI.
- MQTT broker nếu bật MQTT.

Windows chỉ được dùng cho kiểm thử logic Python và kiểm tra hồi quy, không mô phỏng đầy đủ phần cứng Raspberry Pi.

## Kiến trúc chương trình

Các thành phần chính:

| File | Vai trò |
|---|---|
| `Start.py` | Điểm khởi chạy chương trình. |
| `VBot.py` | Vòng đời chính, hotword, microphone và cleanup. |
| `Lib.py` | Cấu hình, trạng thái dùng chung, helper hệ thống, DBus và hàng đợi. |
| `Assistant.py` | Điều phối trợ lý, STT/TTS và câu trả lời. |
| `Data_Processing.py` | Phân tích nội dung và định tuyến lệnh. |
| `Def_Processing.py` | Các hàm xử lý chức năng hệ thống/trợ lý. |
| `STT_Processing.py` | Chọn và gọi nguồn Speech-to-Text. |
| `TTS_Processing.py` | Chọn và gọi nguồn Text-to-Speech. |
| `Media_Player.py` | Media player nội bộ và vòng đời phát nhạc. |
| `Api.py` | REST API, SSE, MQTT, AirPlay DBus, diagnostics và watchdog. |
| `Streaming.py` | WebSocket server, session client và streaming STT. |
| `Bluetooth.py` | Theo dõi BlueZ, metadata và trạng thái Bluetooth. |
| `Button.py` | Nút nhấn và rotary encoder. |
| `Led.py` | Hiệu ứng LED và khôi phục trạng thái nghỉ. |
| `Scheduler.py` | Lịch thông báo và tác vụ định kỳ. |
| `html/` | WebUI PHP, JavaScript, CSS và tài nguyên giao diện. |
| `resource/` | Âm thanh, service, script cài đặt và dữ liệu tích hợp. |
| `tests/` | Bộ kiểm thử logic và hồi quy. |

### Nguyên tắc xử lý bất đồng bộ

- I/O mạng trong STT, TTS, API và WebSocket ưu tiên async.
- Tác vụ blocking được đẩy sang thread khi cần.
- Lệnh hệ thống, GPIO, DBus hoặc thư viện phần cứng không bắt buộc chuyển sang async nếu bản thân thư viện là đồng bộ.
- Các hàng đợi có giới hạn để tránh tăng RAM không kiểm soát trên Pi Zero 2W.
- Shutdown hủy task, đóng loop và dừng thread theo thứ tự để tránh tự khởi động lại dịch vụ.

## Luồng xử lý giọng nói

```text
Chờ từ khóa đánh thức
        │
        ▼
Phát âm báo + cập nhật LED
        │
        ▼
Thu âm ───── nhấn giữ nút ────► hủy thu âm, phát dong.mp3, về trạng thái chờ
        │
        ▼
STT → phân tích câu lệnh
        │
        ├── Home Assistant / MQTT / Broadlink
        ├── Media / radio / podcast
        ├── Trợ lý AI
        └── Tác vụ hệ thống
        │
        ▼
TTS / phản hồi / LED
        │
        └── tiếp tục nghe nếu bật conversation mode
```

## Audio, AirPlay và Bluetooth

VBot có nhiều nguồn cùng sử dụng loa. Trạng thái được xuất qua API để xác định nguồn hiện tại:

- `Local`: media player nội bộ.
- `AirPlay`: Shairport Sync đang phát.
- `Bluetooth`: thiết bị Bluetooth đang phát.
- `N/A`: không có nguồn media xác định.

### Media nội bộ

- Pause giữ nguyên thời điểm đang phát.
- Continue tiếp tục từ vị trí pause thay vì tạo lại bài hát.
- Stop kết thúc phiên media, không gọi thêm luồng stop TTS nếu không cần thiết.
- WakeUp/TTS có thể tạm dừng media và khôi phục theo trạng thái trước đó.

### AirPlay

AirPlay sử dụng Shairport Sync và DBus interface:

```text
org.gnome.ShairportSync
/org/gnome/ShairportSync
org.gnome.ShairportSync.RemoteControl
```

Tên bài hát được lấy từ thuộc tính `Metadata`, thường qua khóa `xesam:title`. Một số ứng dụng/iPhone chỉ gửi audio mà không gửi metadata. Trong trường hợp:

```text
PlayerState = "Playing"
Metadata = a{sv} 0
```

DBus vẫn hoạt động nhưng Shairport không có tên bài để cung cấp; VBot không thể tự suy ra tên bài chính xác.

Kiểm tra trực tiếp:

```bash
busctl --system get-property \
  org.gnome.ShairportSync \
  /org/gnome/ShairportSync \
  org.gnome.ShairportSync.RemoteControl \
  PlayerState

busctl --system get-property \
  org.gnome.ShairportSync \
  /org/gnome/ShairportSync \
  org.gnome.ShairportSync.RemoteControl \
  Metadata
```

### Bluetooth

`Bluetooth.py` theo dõi BlueZ qua DBus để cập nhật:

- Trạng thái kết nối.
- Trạng thái playing/paused/stopped.
- Tên thiết bị.
- Tên bài, nghệ sĩ, album và thời lượng nếu thiết bị nguồn cung cấp.
- `media_player_source = "Bluetooth"` khi Bluetooth đang phát.

Agent Bluetooth nằm tại:

```text
resource/bluetooth/bluetooth_agent.py
```

## WebSocket Streaming

Streaming chỉ sử dụng WebSocket; logic UDP legacy không còn được sử dụng.

Các trách nhiệm chính của `Streaming.py`:

- Quản lý session theo client.
- Giới hạn số client đồng thời.
- Nhận frame PCM qua WebSocket.
- Giới hạn kích thước queue audio.
- Hủy session cũ khi client kết nối lại.
- Timeout STT và đóng session lỗi.
- Trả transcript, nội dung phản hồi hoặc audio PCM cho client.
- Theo dõi số frame bị bỏ để chẩn đoán client gửi nhanh hơn khả năng xử lý.

Xem thêm:

- [`README_Socket_Client.md`](README_Socket_Client.md)

## REST API, SSE và MQTT

### REST API

Cổng API được cấu hình tại:

```json
{
  "api": {
    "port": 5000
  }
}
```

Giá trị thực tế phải đọc từ `Config.json` của thiết bị.

API có thể bật xác thực bằng header:

```http
VBot-API-Key: YOUR_API_KEY
```

Không đưa API key lên Git, log công khai hoặc JavaScript phía trình duyệt.

Danh sách thao tác REST đầy đủ được hiển thị trong tab **Giao Tiếp API (API REST)** của WebUI.

### Server-Sent Events

API trạng thái hỗ trợ SSE để WebUI nhận dữ liệu thay đổi mà không tạo nhiều request đồng thời. Client SSE có queue giới hạn và heartbeat để phát hiện kết nối đã đóng.

### Audio proxy

`/audio_proxy` chỉ chấp nhận URL HTTP/HTTPS công khai. Endpoint chặn:

- Loopback như `127.0.0.1` và `::1`.
- Địa chỉ private trong LAN.
- Link-local, multicast và các địa chỉ không phải global.
- URL có username/password nhúng trong địa chỉ.

Đường dẫn audio cục bộ của chính VBot không cần đi qua audio proxy.

### MQTT

MQTT dùng command/state topic để tích hợp Home Assistant hoặc Node-RED. Hệ thống gồm:

- Paho network loop.
- Processing worker.
- Publish worker.
- Queue giới hạn để tránh chiếm RAM khi broker mất kết nối.
- Cơ chế reconnect và đăng ký lại topic.

Không commit tài khoản hoặc mật khẩu broker thật vào repository công khai.

## Runtime Diagnostics và Watchdog

### API diagnostics

Endpoint:

```http
GET /runtime/diagnostics
```

Ví dụ:

```bash
curl http://127.0.0.1:5000/runtime/diagnostics
```

Nếu bật API authentication:

```bash
curl -H 'VBot-API-Key: YOUR_API_KEY' \
  http://127.0.0.1:5000/runtime/diagnostics
```

Thông tin trả về gồm:

- Audio nội bộ, AirPlay và Bluetooth.
- Nguồn media hiện tại.
- LED, độ sáng và microphone.
- MQTT, WebSocket và SSE.
- Số WebSocket client, client đang thu âm và frame bị bỏ.
- Kích thước queue MQTT, AirPlay và Bluetooth.
- Danh sách thread quan trọng.
- Trạng thái watchdog, số lần kiểm tra, số lần phục hồi và lỗi gần nhất.

Diagnostics chỉ đọc dữ liệu trong bộ nhớ, không chạy `systemctl`, không ping STT/TTS và không tạo network health request.

### WebUI diagnostics

Mở tab:

```text
Chẩn đoán Runtime
```

hoặc truy cập:

```text
http://IP_CUA_VBOT/Runtime_Diagnostics.php
```

Trang tự cập nhật mỗi 5 giây, có thể tắt auto-refresh và dừng polling khi tab trình duyệt bị ẩn.

### Watchdog

Watchdog giám sát:

- API async runtime.
- WebSocket server.
- AirPlay DBus listener.
- Bluetooth monitor.
- MQTT Paho network loop.
- MQTT processing/publish worker.

Cơ chế mặc định:

- Kiểm tra mỗi 30 giây.
- Chỉ phục hồi khi thread thực sự chết.
- Cooldown 60 giây giữa các lần phục hồi.
- Tối đa ba lần phục hồi liên tiếp.
- Không dùng request mạng định kỳ.
- Dừng trước các dịch vụ khác trong quá trình shutdown.

## WebUI

WebUI nằm trong thư mục `html/` và chạy qua Apache/PHP trên Raspberry Pi.

Các khu vực chính:

- Dashboard.
- Cấu hình `Config.json`.
- Command/Terminal.
- REST API.
- Scheduler.
- Log VBot, TTS và API.
- Quản lý VBot Client/WebSocket.
- Thông tin hệ thống.
- Chẩn đoán Runtime.
- Backup và nâng cấp.
- DEV Customize.

Nếu giao diện chưa nhận JavaScript/CSS mới, tải lại bằng `Ctrl + F5` hoặc xóa cache trình duyệt.

## Cài đặt và khởi chạy

### Cách khuyến nghị: sử dụng image VBot

Image dựng sẵn cho Raspberry Pi Zero 2W được cung cấp tại:

[Google Drive - VBot Images](https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ)

Chọn image theo phần cứng audio:

- Image mặc định: phù hợp WM8960, ReSpeaker hoặc VBot AIO tương ứng.
- Image có nhãn I2S: phù hợp INMP441 kết hợp DAC I2S như MAX98357 hoặc phần cứng tương đương.
- Phần cứng khác cần driver ALSA và mapping card/device phù hợp.

Quy trình chung:

1. Ghi image bằng Raspberry Pi Imager hoặc Balena Etcher.
2. Khởi động Raspberry Pi và cấu hình Wi-Fi.
3. Truy cập WebUI bằng địa chỉ IP của thiết bị.
4. Kiểm tra microphone, loa, LED và nút nhấn.
5. Cập nhật thông tin cá nhân, API key và token dịch vụ trong WebUI.
6. Khởi động lại VBot sau khi thay đổi cấu hình quan trọng.

### Chạy bằng service

```bash
systemctl --user status VBot_Offline.service
systemctl --user start VBot_Offline.service
systemctl --user stop VBot_Offline.service
systemctl --user restart VBot_Offline.service
```

Xem log service:

```bash
journalctl --user -u VBot_Offline.service -f
```

### Chạy thủ công

Dừng service trước để tránh hai tiến trình cùng giữ microphone/loa:

```bash
systemctl --user stop VBot_Offline.service
cd /home/pi/VBot_Offline
python3 Start.py
```

Nhấn `Ctrl+C` để dừng. Sau khi kiểm tra xong:

```bash
systemctl --user start VBot_Offline.service
```

> Đường dẫn `/home/pi/VBot_Offline` là đường dẫn phổ biến của image VBot. Nếu bạn cài ở vị trí khác, hãy dùng đường dẫn thực tế.

## Cấu hình

File cấu hình chính:

```text
Config.json
```

Nên chỉnh cấu hình qua WebUI để được kiểm tra dữ liệu và tự động tạo backup.

Các nhóm quan trọng:

- `contact_info`: thông tin người dùng và đăng nhập WebUI.
- `smart_config`: wake word, STT, TTS, microphone, LED, nút và loa.
- `virtual_assistant`: các trợ lý AI.
- `media_player`: nguồn nhạc và hành vi phát media.
- `api`: REST API, auth và streaming server.
- `mqtt_broker`: broker, client name, QoS và retain.
- `web_interface`: đường dẫn WebUI và external access.
- `backup_upgrade`: backup và nâng cấp.

### Dữ liệu nhạy cảm

Các file cấu hình/token có thể chứa:

- API key.
- MQTT username/password.
- Home Assistant token.
- STT/TTS credential.
- Thông tin mạng và người dùng.

Không đăng công khai các file này. Khi chia sẻ log hoặc báo lỗi, hãy che token, mật khẩu và địa chỉ nhạy cảm.

## Kiểm thử

### Trên Windows

Kiểm tra cú pháp các file chính:

```powershell
python -m py_compile Api.py Bluetooth.py Streaming.py VBot.py
```

Chạy toàn bộ test:

```powershell
python -m unittest discover -s tests
```

Chạy một nhóm test:

```powershell
python -m unittest tests.test_runtime_diagnostics
python -m unittest tests.test_runtime_watchdog
python -m unittest tests.test_airplay_metadata
```

### Trên Raspberry Pi

Sau khi test logic trên Windows, kiểm tra thực tế:

1. Wake word và thu âm.
2. Hủy thu âm bằng nút.
3. STT/TTS thực tế.
4. Pause/continue/stop media.
5. LED sau khi thay đổi âm lượng và mute microphone.
6. AirPlay/Bluetooth connect, play, pause và disconnect.
7. WebSocket client kết nối lại.
8. MQTT reconnect.
9. `/runtime/diagnostics` và tab Chẩn đoán Runtime.
10. Shutdown/restart service không còn thread giữ tài nguyên.

## Xử lý sự cố

### API diagnostics không truy cập được

```bash
curl -v http://127.0.0.1:5000/runtime/diagnostics
```

Nếu bật auth, thêm `VBot-API-Key`. Kiểm tra cổng thực tế trong `Config.json` và trạng thái service.

### AirPlay phát được nhưng không có tên bài

```bash
busctl --system get-property \
  org.gnome.ShairportSync \
  /org/gnome/ShairportSync \
  org.gnome.ShairportSync.RemoteControl \
  Metadata
```

Nếu kết quả là `a{sv} 0`, ứng dụng nguồn không gửi metadata hoặc Shairport chưa nhận metadata. Thử Apple Music/Spotify và chuyển sang bài khác. Xem thêm:

```bash
shairport-sync -V
sudo journalctl -u shairport-sync -f
mosquitto_sub -h localhost -t 'shairport/vbot/#' -v
```

### Bluetooth không cập nhật trạng thái

```bash
systemctl status bluetooth
bluetoothctl show
bluetoothctl devices Connected
```

Kiểm tra tab Runtime xem `bluetooth`, thread `VBotBluetooth` và lỗi watchdog gần nhất.

### Continue phát lại từ đầu

- Xác nhận media đang ở trạng thái pause, không phải stop.
- Kiểm tra `pause_media_flag`, `current_duration` và nguồn media trong diagnostics.
- Không tạo một media player mới khi chỉ cần resume player hiện tại.

### LED không trở về trạng thái microphone mute

- Kiểm tra `led.effect` và `led.mic_enabled` trong diagnostics.
- Hiệu ứng âm lượng chỉ là trạng thái tạm thời.
- Sau timeout, LED phải gọi cơ chế khôi phục trạng thái nghỉ thay vì đặt cứng `OFF`.

### WebSocket client bị mất frame

Kiểm tra:

```json
connections.websocket.dropped_frames
```

Nếu tăng liên tục, client đang gửi nhanh hơn server xử lý hoặc queue quá nhỏ. Không tăng queue quá lớn trên Pi Zero 2W; ưu tiên điều chỉnh tốc độ/frame phía client.

### Watchdog báo `restart_limit`

Điều này có nghĩa một dịch vụ đã chết và phục hồi thất bại ba lần liên tiếp. Xem:

- `watchdog.targets.<name>.last_error`
- Log VBot.
- Log systemd/DBus/Bluetooth tương ứng.

Watchdog không thay thế việc sửa nguyên nhân gốc.

## Phát triển và mở rộng

Các file mở rộng:

| File | Mục đích |
|---|---|
| `Dev_Customization.py` | Custom skill và hành động riêng. |
| `Dev_Assistant.py` | Tích hợp trợ lý AI riêng. |
| `Dev_STT.py` | Nguồn STT tùy chỉnh. |
| `Dev_TTS.py` | Nguồn TTS tùy chỉnh. |
| `Dev_Led.py` | Hiệu ứng LED tùy chỉnh. |
| `Dev_Music.py` | Nguồn media tùy chỉnh. |
| `Dev_Processing.py` | Logic xử lý văn bản riêng. |
| `Dev_Weather.py` | Nguồn thời tiết riêng. |
| `Dev_Logs.py` | Định tuyến log riêng. |
| `Dev_Picovoice.py` | Tùy chỉnh wake word/Picovoice. |

Nguyên tắc khi đóng góp hoặc mở rộng:

- Không thực hiện network I/O blocking trực tiếp trong event loop.
- Đặt timeout cho STT, TTS và HTTP request.
- Không tạo queue không giới hạn trên thiết bị RAM thấp.
- Không dùng biến global mới nếu trạng thái đã có nơi quản lý phù hợp.
- Mọi luồng phải có cơ chế stop và cleanup.
- Không để watchdog khởi động lại dịch vụ trong lúc shutdown.
- LED tạm thời phải khôi phục trạng thái nghỉ sau khi hoàn tất.
- Thêm test hồi quy cho lỗi đã sửa.
- Không đưa UDP streaming legacy trở lại; streaming client hiện dùng WebSocket.

## Sao lưu và an toàn dữ liệu

Khi người dùng lưu cấu hình từ WebUI, backup được tạo tại:

```text
html/Backup_Upgrade/Backup_Config/
```

Khuyến nghị:

- Giữ ít nhất một bản backup hoạt động ổn định trước khi nâng cấp.
- Không xóa backup mới nhất khi chưa kiểm tra `Config.json` mới.
- Không commit backup chứa credential vào repository công khai.
- Kiểm tra quyền file theo yêu cầu của image VBot trước khi chạy service.
- Thử restore trên bản sao trước khi thay đổi hệ thống đang hoạt động.

## Liên hệ

- Tác giả: **Vũ Tuyển — VBot Assistant**
- Email: [VBot.Assistant@gmail.com](mailto:VBot.Assistant@gmail.com)
- Facebook: [Vũ Tuyển](https://www.facebook.com/TWFyaW9uMDAx)
- Cộng đồng: [VBot Assistant Facebook Group](https://www.facebook.com/groups/1148385343358824)
- GitHub: [marion001/VBot_Offline](https://github.com/marion001/VBot_Offline)

---

VBot Assistant hướng tới một nền tảng loa thông minh tiếng Việt có thể tự triển khai, tùy biến và tích hợp sâu với hệ sinh thái nhà thông minh.

<img width="2688" height="1360" alt="Image" src="https://github.com/user-attachments/assets/fc2ac10b-00d0-4b20-9c63-2367c3a101d0" />