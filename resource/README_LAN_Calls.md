# Gọi điện nội bộ giữa loa VBot Python

Mỗi loa chạy VBot Python, có microphone và thiết bị phát ALSA. Loa liên lạc trực tiếp trong LAN; không cần Internet, dịch vụ cloud, ESP32 hoặc Android. Trang quản lý: **Gọi điện LAN** (`LAN_Calls.php`), cũng có liên kết trong Config.php.

## Cấu hình hai loa

Trong **Config.php → Cấu hình gọi nội bộ**, bật gọi LAN, cấu hình ID/tên loa, khóa chung, cổng, thiết bị ALSA và chế độ microphone. Bấm **Lưu Cài Đặt Và Restart VBot** để ghi `lan_calls` vào Config.json và áp dụng. Khóa chung hiển thị dạng text; khi lưu lỗi, trang giữ các giá trị vừa nhập để sửa.

Trang **LAN_Calls.php** chỉ quản lý danh sách loa được phép kết nối và các nút gọi/nhận/từ chối/kết thúc. Danh sách được lưu riêng tại `resource/call_lan/Devices.json`, quyền 0777. Trang này không ghi hoặc tự khôi phục Config.json. Khi chưa có file riêng, đọc danh sách cũ từ Config.json để tương thích; lần lưu đầu tạo file riêng. Khởi động lại VBot để nạp danh sách mới.

Ví dụ loa phòng ngủ `192.168.1.10`, loa phòng khách `192.168.1.11`:

```json
"lan_calls": {
  "active": true,
  "use_mdns_id": true,
  "id": "aabbccddeeff",
  "name": "phòng ngủ",
  "shared_key": "THAY_BANG_KHOA_CHUNG_IT_NHAT_16_KY_TU",
  "control_port": 5010,
  "audio_port": 5011,
  "output_device": "default",
  "mode": "full_duplex",
  "ring_timeout": 30,
  "max_duration": 3600,
  "peers": [
    {"id": "112233445566", "name": "phòng khách", "host": "192.168.1.11", "control_port": 5010, "audio_port": 5011}
  ]
}
```

Mặc định `use_mdns_id: true`: VBot lấy ID mDNS ổn định từ phần cứng, dùng chung `hardware_id` với Multiroom; không cần tạo ID riêng cho gọi điện. Ô ID trên WebUI hiển thị ID tự động. Hai ID trong ví dụ chỉ minh họa: thay bằng ID thực tế hiển thị trên từng loa. ID MQTT cũ không phải ID chuẩn để khai báo peer. `id` trong Config được ghi khi lưu WebUI, nhưng runtime vẫn lấy ID phần cứng khi bật chế độ tự động; `use_mdns_id: false` giữ cách nhập ID thủ công để tương thích cấu hình cũ.

Ở loa phòng khách, đặt `name` thành `phòng khách`; khai báo peer có ID mDNS của loa phòng ngủ, tên `phòng ngủ`, IP `192.168.1.10`. Dùng cùng `shared_key` trên hai loa (16–200 ký tự ASCII không có khoảng trắng). Không sử dụng nguyên khóa ví dụ. Mỗi loa phải khai báo loa còn lại; IP phải thuộc mạng IPv4 riêng `10.x`, `172.16–31.x`, `192.168.x`. ID không được trùng, IP nên được giữ cố định tại router. Phiên bản này tái sử dụng ID mDNS nhưng vẫn dùng IP đã khai báo để truyền âm thanh, chưa tự dò hoặc cập nhật IP peer qua mDNS.

Lưu cấu hình và khởi động lại VBot trên cả hai loa. Mặc định tính năng tắt. Không cần mở cổng trên Internet. Nếu máy có firewall nội bộ, cho phép TCP 5010 và UDP 5011 từ IP loa đã cấu hình. Cổng API/WebUI hiện có vẫn dùng cho điều khiển trang; cần bật API VBot để dùng các nút gọi/nhận/kết thúc trên WebUI. Nhận diện câu gọi vẫn dùng nguồn STT đã cấu hình của VBot; chỉ đường truyền cuộc trò chuyện là độc lập với STT và Internet.

`output_device` là thiết bị ALSA dùng bởi `aplay`; mặc định `default`. Microphone tái sử dụng PvRecorder đang được cấu hình của VBot, mono 16 kHz. Không mở một microphone thứ hai.

## Sử dụng

1. Ở loa phòng ngủ, nói **“gọi loa phòng khách”** hoặc chọn loa rồi bấm **Gọi loa** trên WebUI.
2. Loa phòng khách phát nhạc chuông đã chọn trong Config.php và chạy LED **CALL_RINGING** (nháy đôi xanh dương). Nhấn ngắn nút **WakeUp** để nhận ngay, không mở thu âm/STT; cũng có thể nói **“nhận cuộc gọi”**, **“từ chối cuộc gọi”**, hoặc dùng nút WebUI. Khi kết nối/trò chuyện, chuyển sang **CALL_ACTIVE** (xanh lá sáng/tối chậm). Kết thúc hoặc hết thời gian chờ sẽ trả LED về trạng thái thông thường.
3. Khi nhận, hai loa truyền PCM trực tiếp cho nhau, không gửi nội dung trò chuyện qua STT/TTS. Microphone được dành riêng cho cuộc gọi; trợ lý/hotword không xử lý giọng nói trong lúc trò chuyện.
4. Trong khi gọi, dùng WebUI hoặc nút vật lý để kết thúc. Có thể gán action **Gọi LAN: Nhận hoặc kết thúc cuộc gọi** (`lan_call_toggle`) hoặc **Gọi LAN: Kết thúc / từ chối** (`lan_call_end`) cho nút trong cấu hình hiện có. Nút WakeUp thông thường không tự kết thúc cuộc gọi nếu chưa gán action này.

Nhấn ngắn nút **MIC** khi đang kết nối hoặc trò chuyện sẽ kết thúc cuộc gọi. Ngoài cuộc gọi, MIC vẫn thực hiện chức năng thông thường.

Có thể tùy chỉnh trong Config.php: bật **Dùng nút vật lý ngắt cuộc gọi**, chọn nút và chọn **Nhấn nhả**, **Nhấn giữ** hoặc **Cả nhấn nhả và nhấn giữ**. Mặc định MIC / nhấn nhả. Nhấn giữ ngắt ngay khi đạt thời gian giữ cấu hình của nút (mặc định 2 giây), không cần bật thao tác nhấn giữ thông thường của nút. Khi dùng nhấn giữ để nói, chọn một nút khác để ngắt. Tắt tùy chọn ngắt cuộc gọi sẽ trả thao tác nút về chức năng thông thường.

Trong Config.php, bên dưới select nhạc chuông có ô chọn file và nút **Tải lên**. File âm thanh được lưu vào `resource/sound/call_ringtone/`, cập nhật select và chọn file mới ngay. Hỗ trợ MP3/WAV/OGG/FLAC/M4A, tối đa 20 MB; file trùng tên được đổi tên để giữ bản cũ. Bấm lưu cài đặt và khởi động lại để áp dụng. PHP cần extension fileinfo để kiểm tra nội dung âm thanh.
5. Sau khi kết thúc, microphone trở lại chế độ hotword. AudioManager giữ quyền âm thanh của cuộc gọi và khôi phục các nguồn nó đã tạm dừng/mute; nhạc do người dùng tạm dừng trước đó không tự phát lại.

Nếu hai loa gọi nhau cùng lúc hoặc loa đang có cuộc gọi, yêu cầu mới bị từ chối. Không tự nhận cuộc gọi. Hết thời gian đổ chuông, mất luồng UDP khoảng 20 giây, lỗi microphone/ALSA hoặc vượt thời lượng tối đa sẽ ngắt cuộc gọi và trả lại tài nguyên.

Có hai chế độ:

- `full_duplex`: hai bên nói đồng thời; WebUI có nút bật/tắt microphone cuộc gọi.
- `push_to_talk`: microphone cuộc gọi mặc định tắt; nhấn giữ **Nhấn giữ để nói** trên WebUI. Thả tay hoặc mất tín hiệu giữ nút sẽ tắt truyền microphone, có giới hạn gia hạn tối đa 2 giây.

Trong Config.php có thể bật **Dùng nút vật lý nhấn giữ để nói trong cuộc gọi** và chọn một nút đã cấu hình trong Button. Nút phải đang bật. Khi cuộc gọi đang hoạt động ở chế độ `push_to_talk`, giữ nút truyền microphone, thả nút ngừng truyền; thao tác này ưu tiên hơn nhấn ngắn/nhấn giữ/nhấn đúp thông thường. Trong các trạng thái khác nút hoạt động như cấu hình cũ. Nếu chọn MIC để nói, kết thúc bằng WebUI. Cấu hình dùng `lan_calls.ptt_button_active` và `lan_calls.ptt_button`; lưu và khởi động lại VBot để áp dụng.

Phiên bản này chưa có bộ khử vọng AEC bằng phần mềm. Tùy microphone, độ lớn âm lượng và vị trí hai loa, chế độ nói đồng thời có thể bị vọng. Nên kiểm tra `push_to_talk` trước nếu phần cứng chưa có chống vọng; đây là hạn chế âm thanh thực tế, không thể xác nhận bằng kiểm thử máy phát triển.

## Tùy chỉnh câu gọi theo ngôn ngữ

Tên peer có thể dùng ngôn ngữ tùy ý. Mặc định có câu tiếng Việt và tiếng Anh. Có thể bổ sung trong `lan_calls`:

```json
"voice_commands": {
  "call": ["gọi loa", "call speaker"],
  "accept": ["nhận cuộc gọi", "accept call"],
  "reject": ["từ chối cuộc gọi", "reject call"],
  "end": ["kết thúc cuộc gọi", "end call"]
}
```

Câu gọi chỉ khớp tên/ID loa chính xác sau chuẩn hóa, không tự chọn loa có tên gần giống. Lệnh kết thúc bằng giọng nói chỉ dùng khi microphone chưa chuyển sang luồng trò chuyện; trong cuộc gọi đang hoạt động hãy dùng WebUI hoặc nút đã gán.

Tên loa được so khớp sau khi bỏ tiền tố “Loa”/“Speaker”: “gọi loa phòng ngủ” khớp “Loa Phòng Ngủ”. Khi yêu cầu đọc lại tên, VBot tiếp tục thu lượt tiếp theo trong phiên wakeup, không dùng thời hạn 90 giây. Lượt trả lời khớp tên/ID sẽ gọi; nếu là yêu cầu khác, hủy chọn loa và chuyển yêu cầu sang xử lý thông thường. Phiên kết thúc, hủy hoặc không có lời nói sẽ xóa trạng thái chọn loa.

Cũng hỗ trợ “gọi điện tới loa phòng ngủ”, “gọi điện đến loa phòng khách”, “gọi điện cho loa phòng ngủ”, “gọi tới loa phòng khách” và “gọi phòng ngủ”. LED WS281x, APA102 và ReSpeaker dùng hai hiệu ứng mới; nếu dùng Dev_Led.py tùy biến, bổ sung LED_CALL_RINGING và LED_CALL_ACTIVE trong file đó.

Câu gọi/nhận/từ chối/ngắt/hủy được lấy từ `values.lan_calls` trong file ngôn ngữ đang chọn. vi-VN dùng các câu tiếng Việt; en-US hỗ trợ “call the speaker in bedroom”, “call speaker bedroom”, “answer the call”, “decline the call”, “hang up”, “cancel call”. Tên loa vẫn là tên người dùng cấu hình, không tự dịch tên phòng. Câu gọi được xử lý trước khi tách đa lệnh; nếu dịch vụ LAN chưa chạy, VBot thông báo lỗi tại chỗ, không chuyển câu gọi sang trợ lý ảo. Câu tùy biến trong Config được bổ sung vào các câu mặc định của ngôn ngữ đang chọn.

Phản hồi giọng nói gọi LAN và kịch bản dùng `responses.lan_calls` / `responses.voice_routines` qua Lib_System.py. Lỗi kỹ thuật vẫn được ghi đầy đủ bằng tiếng Việt trong log; TTS dùng thông báo theo ngôn ngữ đã chọn và không đọc nguyên lỗi tiếng Việt khi đang dùng tiếng Anh. Log, trạng thái WebUI và nhãn PHP vẫn giữ nguyên tiếng Việt. Câu gọi kịch bản mặc định và câu hủy/chọn số Home Assistant nằm trong `values.voice_routines` / `values.hass_clarification`. Nội dung thông báo do người dùng nhập và tên thiết bị/kịch bản không tự dịch.

## API và kiểm tra thực tế

GET `/lan-calls` trả trạng thái và danh sách loa (không trả khóa chung). POST cùng đường dẫn nhận `action: call` với `peer`, `accept`, `reject`, `end`, hoặc `mute` với `muted: true/false`. API sử dụng cơ chế xác thực hiện có của VBot và giới hạn địa chỉ gọi nội bộ.

Trang WebUI nhận trạng thái qua SSE `/lan-calls/events`: một kết nối liên tục, đẩy trạng thái khi thay đổi và heartbeat khoảng 10 giây; không gọi GET định kỳ. Khi ẩn tab hoặc rời trang, WebUI đóng luồng; mở lại tab sẽ lấy snapshot mới qua SSE. Nếu mất kết nối, tự thử lại sau 3 giây. Fetch streaming giữ header xác thực `VBot-API-Key`, không đưa khóa vào URL.

Kênh điều khiển riêng `/signal` trên TCP 5010 chỉ chấp nhận peer/IP đã khai báo và khóa chung. PCM trên UDP có HMAC theo phiên và số thứ tự, loại gói giả mạo/lặp/sai phiên; bộ đệm giới hạn để tránh tích lũy độ trễ. Âm thanh và signaling chưa được mã hóa; phạm vi sử dụng là LAN tin cậy, không chuyển tiếp các cổng này ra ngoài.

Trình tự thử trên hai loa: gọi bằng WebUI → nhận → nghe hai chiều → mute → kết thúc → kiểm tra hotword/media → thử từ chối và không trả lời → thử ngắt mạng loa đích → thử gọi bằng giọng nói. Kiểm tra đúng thiết bị ALSA và âm lượng nhỏ trước khi tăng dần. Xem log `[Gọi LAN]` khi lỗi.
