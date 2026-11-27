# VBot Assistant 🇻🇳

VBot Assistant là hệ thống loa thông minh tiếng Việt chạy trên Raspberry Pi, được tối ưu cho Raspberry Pi Zero 2W. Dự án thuần Việt dùng để điều khiển nhà thông minh trong mạng nội bộ gia đình (Local), kết hợp nhận diện từ khóa đánh thức (Picovoice, Snowboy), xử lý âm thanh số WebRTC 2.1 (khử vọng AEC3 khi phát nhạc, giảm nhiễu NS, cân bằng âm lượng AGC), Speech-to-Text (STT), xử lý lệnh, trợ lý AI đa mô hình & thị giác máy tính (Vision), Kho Tri Thức AI Cục Bộ, Text-to-Speech (TTS), phát media (Local, Spotify Connect, AirPlay, Bluetooth, DLNA, Multiroom), gọi điện nội bộ LAN (PJSIP), Apple HomeKit, Home Assistant, Local Tuya, Wake-on-LAN, MQTT, WebSocket client và giao diện quản trị WebUI.

### VBot HomeKit Bridge

VBot có thể liên kết trực tiếp với **Apple HomeKit** để xuất loa, media, Remote iOS,
microphone, trợ lý AI, playlist, radio, điều khiển hệ thống và Multiroom lên ứng dụng
**Nhà** trên iPhone/iPad. Lệnh điều khiển được gửi vào REST API VBot, còn trạng thái
được cập nhật hai chiều theo thời gian thực qua SSE `all_info`.

Bridge hỗ trợ các kiểu phụ kiện `speaker`, `television`, `switches` và `hybrid`;
phím trong giao diện **ĐK Từ Xa (Remote)** của iOS có thể gán chức năng riêng theo
sáu trạng thái hoạt động của VBot. HomeKit chạy và dừng theo vòng đời VBot, sử dụng
OTP phần cứng Raspberry Pi làm danh tính ổn định và giữ dữ liệu pairing bên ngoài
thư mục mã nguồn.

Xem [hướng dẫn HomeKit trong README này](#liên-kết-apple-homekit) hoặc
[tài liệu HomeKit chi tiết](resource/HomeKit/README.md).

## Mục lục
- Tài Liệu OS IMG Liên Quan Tới VBot: [Google Drive - VBot Images](https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ)
- [Cài VBot Client Cho ESP, PhicommR1](https://github.com/marion001/VBot_Client_Offline)
- [Liên Kết Dữ Liệu VBot Vào Home Assistant (HASS), Assist Qua Custom Component](https://github.com/marion001/VBot_Offline_Custom_Component)
- [Tính năng chính](#tính-năng-chính)
- [Phần cứng và nền tảng](#phần-cứng-và-nền-tảng)
- [Kiến trúc chương trình](#kiến-trúc-chương-trình)
- [Luồng xử lý giọng nói](#luồng-xử-lý-giọng-nói)
- [Audio, AirPlay, Bluetooth, DLNA và Spotify Connect](#audio-airplay-bluetooth-dlna-và-spotify-connect)
- [Spotify Connect](#spotify-connect)
- [DLNA Audio Renderer](#dlna-audio-renderer)
- [WebRTC 2.1, Khử vọng AEC3 và Gọi nội bộ LAN (PJSIP)](#webrtc-21-khử-vọng-aec3-và-gọi-nội-bộ-lan-pjsip)
- [Multiroom Audio](#multiroom-audio)
- [Liên kết Apple HomeKit](#liên-kết-apple-homekit)
- [WebSocket Streaming](#websocket-streaming)
- [REST API, SSE và MQTT](#rest-api-sse-và-mqtt)
- [Cập nhật chương trình và WebUI](#cập-nhật-chương-trình-và-webui)
- [Runtime Diagnostics và Watchdog](#runtime-diagnostics-và-watchdog)
- [WebUI](#webui)
- [Cài đặt và khởi chạy](#cài-đặt-và-khởi-chạy)
- [Cấu hình](#cấu-hình)
- [Kiểm thử](#kiểm-thử)
- [Xử lý sự cố](#xử-lý-sự-cố)
- [Phát triển và mở rộng](#phát-triển-và-mở-rộng)
- [Cập nhật thủ công qua SSH](#cập-nhật-thủ-công-qua-ssh)
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

- Trợ lý mặc định của VBot (từ khóa nội bộ theo locale `resource/lang_keywords/<locale>.json`, mặc định `vi-VN.json`).
- **Chế độ xử lý AI Function Calling (`processing_pipeline.mode = "ai"`):**
  - Hỗ trợ **Groq AI**, **Google Gemini**, **OpenAI-Compatible (`openai_compat`: Ollama LAN / LM Studio / vLLM / OpenRouter / Cerebras)**, **ChatGPT**, **XiaoZhi** và `Dev_AI_Engine.py`.
  - **Tự động xoay vòng nhiều API Key (Round-Robin) + Dự phòng chéo (`Groq ⇄ Gemini ⇄ Ollama LAN`):** phân biệt lỗi giới hạn theo phút (**TPM/RPM 429** — chỉ tạm nghỉ vài giây trong RAM, không khóa oan cả ngày) với hết hạn mức ngày (**TPD/RPD** hoặc **401** — đưa vào `BackList.json`).
  - **Đa lệnh AI thông minh (Multi-Tool 3 pha):** thực thi song song các lệnh điều khiển/tra cứu độc lập (`Phase 1`), tự lan truyền động từ chung (*"Kiểm tra đèn phòng ngủ và đèn phòng khách"*), đọc xong xác nhận rồi mới mở nhạc (`Phase 2`) và đặt hẹn giờ tắt nhạc sau cùng (`Phase 3`); tự hỏi lại khi nhiều thiết bị đánh số trùng tiền tố (*Đèn Phòng Khách 1/2/3*).
  - **VBot Vision (`AI_Vision.py` — Camera Home Assistant & USB/Pi Camera):** chụp và phân tích ảnh trực tiếp 100% trên RAM (**Zero-disk**, không ghi thẻ nhớ SD, tự nén ảnh 4K bằng Pillow/`ffmpeg`), hỗ trợ chọn **Mô hình Xử lý Ảnh (`vision_model_name`)** riêng cho từng trợ lý (Groq Llama 4 Scout / Google Gemini) và dự phòng chéo.
  - **Kho Tri Thức AI Cục Bộ — VBot Knowledge v2 (`AI_Knowledge_Base.py`):** hỗ trợ 27 định dạng + OCR ảnh/PDF Scan + URL Website + ghi chú giọng nói, tìm kiếm **Hybrid (SQLite FTS5 BM25 + Vector 256 chiều RRF)**, tích hợp **Vector Cục Bộ 100% Offline (`local-subword-256`, `< 2ms` trên Pi Zero 2W)** tự dự phòng khi không có Gemini Key hoặc mất Internet.
  - **Phát giọng nói theo từng câu (Chunked TTS Pipeline):** câu trả lời dài được phát ngay câu đầu trong khi tạo nền câu kế tiếp, giảm rõ rệt độ trễ chờ.
- **Bộ Đệm Lệnh AI Cục Bộ (`Local Intent Cache`):** khớp lệnh lặp lại tức thì với **0 token (< 50ms)**, tự bỏ qua cache khi nhắc lại trong 45 giây và tự chặn lưu nhầm câu nói tắt phụ thuộc ngữ cảnh.
- **Ngữ cảnh hội thoại thông minh:** nhớ thiết bị/camera vừa thao tác để hiểu câu nói tắt (*"Bật đèn ngủ"* → *"Tắt đi cho tôi"*), tự mở mic 1 lượt khi AI hỏi làm rõ (`?`).
- **Màn hình Biểu cảm (`html/Face.php`) & Client Độc lập (`html/VBot_Face_Client.html`):**
  - `Face.php`: 21 biểu cảm hoạt họa, đĩa xoay ảnh bìa bài hát + 4 nút điều khiển nhanh (`⏮ ⏯ ⏭ ■`), thời tiết OpenWeatherMap, đồng hồ đếm ngược / hẹn giờ tắt nhạc, phụ đề khớp cả micro & ChatBox, Wake Lock, Chế độ Ngủ Đêm (tự động `22:30–06:00`) và OLED Pixel Shift chống lưu ảnh.
  - `VBot_Face_Client.html`: 1 file HTML/JS độc lập kết hợp giao diện Face + kết nối WebSocket Micro/Loa cho điện thoại cũ, máy tính bảng hoặc PC trong mạng LAN (mở trực tiếp hoặc tải xuống 1 chạm từ `Client_Testing.php`).
- Home Assistant Assist và câu lệnh Home Assistant tùy chỉnh.
- Các điểm mở rộng `Dev_*.py` dành cho logic cá nhân (người dùng/lập trình viên có thể tự tùy biến mà không cần build lại `.so`).

### Media và kết nối âm thanh

- Nhạc cục bộ và trình quản lý đa Playlist.
- Zing MP3, NhacCuaTui, YouTube, radio, podcast và tin tức, tùy nguồn còn hoạt động và được bật.
- **Spotify Connect (`go-librespot`):** phát nhạc trực tiếp từ ứng dụng Spotify trên điện thoại/máy tính tới VBot, đồng bộ thời gian thực tên bài, nghệ sĩ, album, ảnh bìa, thời lượng, vị trí và âm lượng hai chiều; điều khiển Play/Pause/Next/Prev/Stop từ WebUI, nút vật lý, giọng nói, REST API, MQTT, HomeKit và Home Assistant.
- AirPlay thông qua Shairport Sync.
- Bluetooth Audio Sink (nhận nhạc từ điện thoại) và Bluetooth Audio Source (xuất âm thanh VBot ra loa/tai nghe Bluetooth ngoài).
- DLNA Audio Renderer để nhận nhạc từ ứng dụng điều khiển DLNA/UPnP.
- **Bộ cân bằng âm thanh 6 dải (Native Equalizer `libvbot_eq.so`):** chỉnh trực tiếp Bass, Low-Mid, Mid, High-Mid, Treble, Air, Preamp và các preset âm nhạc ngay trên `Audio_Equalizer.php`.
- **Xử lý Microphone WebRTC 2.1 & Khử vọng AEC3:** đánh thức loa nhạy bằng từ khóa ngay cả khi loa đang phát nhạc lớn (Local, Multiroom, AirPlay, Bluetooth, Spotify Connect).
- Tạm dừng, tiếp tục đúng vị trí và dừng media nội bộ.
- Điều khiển âm lượng, mute/unmute và đồng bộ trạng thái lên WebUI/API.
- Hiển thị nguồn đang phát qua `media_player_source` (`Local`, `Spotify`, `AirPlay`, `Bluetooth`, `DLNA`, `Multiroom`).

### Nhà thông minh

- Home Assistant REST/WebSocket tùy cấu hình.
- MQTT command/state topic.
- Broadlink IR/RF và hồng ngoại tích hợp trên mạch (`Internal_IR`).
- Custom command cho thiết bị, script và automation.
- **Local Tuya:** Liên kết thiết bị từ ứng dụng **Tuya Smart** và **Smart Life** với VBot; điều khiển trực tiếp trong mạng LAN nội bộ bằng Local Tuya, không cần gửi lệnh điều khiển qua đám mây. Cấu hình thiết bị, DPS và câu lệnh tại `html/Tuya_Local.php`.
- **Wake-on-LAN (`Wake_On_LAN`):** bật máy tính PC, NAS hoặc TV hỗ trợ Magic Packet trong mạng nội bộ bằng giọng nói (AI / từ khóa), giao diện `html/Wake_On_LAN.php` hoặc API.
- Tích hợp tham khảo:
  - [VBot Offline Custom Component (v1.17.0+)](https://github.com/marion001/VBot_Offline_Custom_Component)

### Hệ thống và tiện ích

- Kịch bản nhiều bước bằng giọng nói, cấu hình trên trang WebUI riêng `Voice_Routines.php`: Home Assistant, thao tác loa, âm lượng, thông báo và chờ. Xem [hướng dẫn kịch bản](resource/voice_routines/README_Voice_Routines.md).
- Phòng đặt loa: lệnh như “bật đèn” tự tìm thiết bị Home Assistant trong phòng đã cấu hình, hỏi lại theo phiên nếu có nhiều thiết bị. Bật/tắt và sửa phòng tại Config hoặc hồ sơ người dùng. Xem [hướng dẫn phòng đặt loa](resource/README_Speaker_Location.md).
- Scheduler cho thông báo, báo thức, phát media, thay đổi âm lượng, LED và microphone.
- Nút nhấn và rotary encoder.
- LED báo trạng thái chờ, nghe, xử lý, phát âm thanh, mute, pause, cập nhật, lỗi và thay đổi âm lượng.
- Zeroconf/mDNS để phát hiện thiết bị trong LAN.
- WebUI cấu hình, xem log, quản lý client và cập nhật chương trình.
- Backup cấu hình khi người dùng lưu từ WebUI.
- Runtime diagnostics và watchdog nhẹ cho các luồng quan trọng.

### Các tính năng mới nổi bật

- **AI Engine & Bộ kỹ năng thông minh (`AI_Engine`, `AI_Skill_Registry`, `AI_Action_Dispatcher`):**
  - Điều khiển Home Assistant đơn lẻ & hàng loạt theo phòng (`batch_on` / `batch_off` có lọc nhiễu camera và bảo vệ thiết bị quan trọng như router, tủ lạnh, server), đặt tên gọi tắt thiết bị (Device Alias), hỏi thiết bị đang bật.
  - Bản tin buổi sáng / chúc ngủ ngon (`daily_briefing`, hỗ trợ hẹn giờ tự động mỗi sáng trong `Scheduler.php`), Nhật ký giọng nói (`voice_journal`), Nhiều bộ đếm ngược song song (`countdown_timer`), Phát thông báo loa (`announce`), Hẹn giờ tắt nhạc giảm âm lượng mượt (`Sleep Timer` 10s fade-out), Dừng sau bài hiện tại, Tìm kiếm web tóm tắt.
  - **Local Intent Cache (0 token):** giảm tối đa chi phí/hạn mức API AI và phản hồi tức thì cho các lệnh quen thuộc.
- **Kho Tri Thức AI Cục Bộ — VBot Knowledge v2 (`AI_Knowledge_Base.py` + `html/AI_Knowledge.php`):**
  - Hỗ trợ **27 định dạng** (PDF, PDF Scan, DOCX, ODT, XLS/XLSX, PPT/PPTX, EPUB, HTML, RTF, MD, TXT, CSV, JSON/XML/YAML, mã nguồn, ảnh JPG/PNG/WEBP qua Gemini Vision), nạp & cập nhật từ **URL Website**, và lưu ghi chú tri thức bằng **giọng nói**.
  - **Tìm kiếm Hybrid 3 lớp (0 Qdrant, 0 RAM nền):** Kết hợp Knowledge Catalog + SQLite FTS5 BM25 + Vector Semantic 256 chiều (`kb_vectors` trong SQLite) hợp nhất **RRF**, cắt đoạn theo đề mục (**Heading-Aware Chunking**), ghép đoạn liền kề (**Neighbor Window**), hỏi nối tiếp nhiều lượt, tự chuyển sang **Web Search** khi không có trong tài liệu, khử trùng lặp `SHA-256` và AI tự phân loại & gắn Tags.
  - Trang WebUI `html/AI_Knowledge.php` hỗ trợ lập chỉ mục từng tệp, xem trực tiếp bảng `kb_chunks` & `kb_vectors`, **Search Playground** kiểm thử truy vấn và **Sao lưu / Khôi phục `.tar.gz`** tại `html/Backup_Upgrade/Backup_AI_Knowledge` (tối đa 5 bản tự xóa bản cũ nhất, đồng bộ quyền `0777`).
- **Màn hình biểu cảm OLED (`html/Face.php`) & 21 cảm xúc chuẩn XiaoZhi:** giao diện mắt SVG chuyển động tự nhiên (tự liếc mắt + parallax theo trỏ chuột/chạm), 21 mã cảm xúc + trạng thái nghe/suy nghĩ/nói, hiệu ứng miệng khi nói, sóng nhạc 5 cột khi phát media, tự cuộn phụ đề TTS dài kiểu teleprompter, menu điều khiển nguồn (Start/Stop/Restart/Reboot) và cảnh báo mất kết nối tự động; đồng bộ thời gian thực qua SSE `/ai-face/events` cho cả chế độ AI và VBot từ khóa.
- **Bảng thống kê AI (`html/AI_Stats.php`):** theo dõi lượt gọi AI, tỷ lệ khớp Local Intent Cache, token tiêu thụ (Hôm nay / Tổng), kiểm tra hạn mức trực tiếp từng API Key Groq và quản lý Nhật ký giọng nói.
- **Đồng bộ & tăng tốc Home Assistant cho hệ thống nhiều thực thể:** tự đồng bộ `Home_Assistant.json` khi khởi chạy, nút đồng bộ nhanh ngay trên Dashboard `index.php` và trong Home Assistant Custom Component (`SYNC_HASS_JSON`, có khóa chống chạy chồng luồng); tra cứu `entity_id` trực tiếp từ RAM cache (< 5ms) kết hợp gộp `/api/states` 4 giây.
- **Tối ưu hiệu năng & độ bền 24/7 trên Raspberry Pi:** tổng hợp TTS câu dài song song (`Edge TTS` / `Default TTS`), cache RAM theo `mtime_ns` cho `BackList.json` & `Home_Assistant.json`, gom ghi thẻ nhớ SD định kỳ cho telemetry/cache, làm nóng kết nối HTTPS nền khi khởi động, kiểm soát `timeout` toàn bộ tiến trình con và tách nhỏ module lõi để build `.so` nhanh hơn.
- **Xử lý âm thanh số WebRTC 2.1 (AEC3 + NS + Digital AGC) & Tối ưu riêng cho mạch I2S / WM8960:**
  - Khử vọng âm học tuyến tính (`linear` AEC3) giúp Porcupine bắt nhạy từ khóa đánh thức khi đang phát nhạc qua tuyến âm thanh chung `vbot_aec_media` (`48 kHz`), kết hợp lọc thông cao (HPF), giảm nhiễu nền (`NS`) và cân bằng giọng nói tự động (`Digital AGC + Limiter`).
  - Nút **Đặt lại mặc định các thông số** trên `Config.php` tự nhận diện `/os_image_created.txt` (mạch **VBot AIO I2S** hoặc **VBot AIO WM8960**) để điền thông số chuẩn đã đo kiểm thực tế; đi kèm script tự động hóa 1 lệnh `resource/tools/fix_and_verify_audio_webrtc.sh` tự cấu hình ALSA, mixer, service và kiểm tra 24 hạng mục.
- **Gọi điện nội bộ LAN (PJSIP `G.722 16 kHz`) & Gọi trực tiếp từ trình duyệt WebUI (`WebUI_Call`):**
  - Đàm thoại hai chiều băng rộng giữa các loa VBot trong mạng LAN qua SIP/RTP (PJSUA2) hoặc gọi thẳng từ điện thoại/máy tính mở WebUI qua HTTPS tới loa; dùng chung một microphone `PvRecorder`, khử vọng AEC3 cuộc gọi và bộ lọc NS/AGC chung không xử lý chồng lặp; hỗ trợ tự nhận cuộc gọi, nhạc chuông tùy chỉnh và chế độ nhấn giữ để nói (PTT). Cấu hình tại `Config.php` và `LAN_Calls.php`. Xem [hướng dẫn gọi điện LAN](resource/README_LAN_Calls.md).
- **Spotify Connect (`Spotify_Connect` + `go-librespot`):** biến VBot thành loa Spotify Connect trong mạng nội bộ, tự động đồng bộ ảnh bìa, thông tin bài hát, thanh tiến trình, hiệu ứng LED/Face và điều khiển xuyên suốt từ ứng dụng Spotify, WebUI Dashboard, nút cứng, giọng nói, MQTT, HomeKit và Home Assistant Custom Component.
- **Wake-on-LAN & Bộ cân bằng âm thanh 6 dải (Equalizer):** đánh thức máy tính/NAS bằng giọng nói hoặc giao diện `Wake_On_LAN.php`; tinh chỉnh chất âm loa thời gian thực bằng thư viện C `libvbot_eq.so` tại `Audio_Equalizer.php`.
- **DLNA Audio Renderer:** nhận luồng nhạc UPnP/DLNA, đồng bộ trạng thái phát, metadata, thời lượng, vị trí và âm lượng với VBot qua D-Bus/MPRIS; điều khiển từ WebUI, REST API và MQTT.
- **Multiroom Audio:** một VBot làm coordinator phát đồng bộ tới nhiều loa VBot trong LAN; hỗ trợ nhóm loa, metadata, volume tổng và volume từng luồng.
- **Apple HomeKit:** đưa loa, Remote iOS, media, trợ lý, playlist, radio, hệ thống và Multiroom vào ứng dụng Nhà qua VBot HomeKit Bridge; trạng thái được đồng bộ bằng SSE và lệnh điều khiển gọi trực tiếp API VBot.
- **Trạng thái media thống nhất & Trình điều khiển tích hợp trên Dashboard:** Local Media, Spotify Connect, AirPlay, Bluetooth, DLNA và Multiroom cùng đi qua state machine dùng chung và điều khiển trực tiếp ngay trên trang chủ `index.php`.
- **Playlist mở rộng:** quản lý nhiều playlist có ID/tên riêng, chế độ phát, next/previous, loop và xuất trạng thái qua API/MQTT.
- **XiaoZhi MCP:** hỗ trợ công cụ điều khiển hệ thống, Home Assistant, playlist, lịch nhắc và plugin MCP do người dùng tự viết.
- **Runtime Diagnostics:** quan sát thread, queue, audio source, client streaming, MQTT, SSE và lịch sử phục hồi watchdog ngay trên WebUI.
- **Cập nhật an toàn:** chung một bộ điều phối cho WebUI, REST API, MQTT và cập nhật thủ công; có khóa chống chạy trùng, rollback, bảo vệ dữ liệu người dùng và bàn giao kết quả qua restart.
- **LED cập nhật riêng:** dùng hiệu ứng `UPDATE` trong toàn bộ thời gian nâng cấp; `Dev_Led.py` chưa định nghĩa `LED_UPDATE` sẽ tự dùng `LED_STARTUP` làm dự phòng.
- **Thông báo cập nhật đúng thứ tự:** ghi log và phát hết âm báo thành công/thất bại trước khi restart service ở bước cuối.
- **Truy cập từ xa có kiểm soát:** WebUI có trang quản lý Cloudflare Tunnel và Tailscale Funnel; REST API hỗ trợ API key.

## Phần cứng và nền tảng

### Thiết bị khuyến nghị

- Raspberry Pi Zero 2W. (ISO IMG Được Build Trên Phần Cứng Này)
- Thẻ nhớ chất lượng tốt và nguồn điện ổn định tối thiểu 5V-2A.
- ReSpeaker 2-Mics Pi HAT, Mạch VBot AIO, Mạch Vietbot AIO hoặc microphone I2S tương thích.
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

## Kiến trúc chương trình

Các thành phần chính:

| File | Vai trò |
|---|---|
| `Start.py` | Điểm khởi chạy chương trình. |
| `VBot.cpython-39-arm-linux-gnueabihf.so` | Vòng đời chính, hotword, microphone và cleanup. |
| `Lib.cpython-39-arm-linux-gnueabihf.so` | Cấu hình, trạng thái dùng chung, helper hệ thống, DBus và hàng đợi. |
| `Assistant.cpython-39-arm-linux-gnueabihf.so` | Điều phối trợ lý, STT/TTS và câu trả lời. |
| `Data_Processing.cpython-39-arm-linux-gnueabihf.so` | Phân tích nội dung và định tuyến lệnh. |
| `Def_Processing.cpython-39-arm-linux-gnueabihf.so` | Các hàm xử lý chức năng hệ thống/trợ lý. |
| `STT_Processing.cpython-39-arm-linux-gnueabihf.so` | Chọn và gọi nguồn Speech-to-Text. |
| `TTS_Processing.cpython-39-arm-linux-gnueabihf.so` | Chọn và gọi nguồn Text-to-Speech. |
| `Media_Player.cpython-39-arm-linux-gnueabihf.so` | Media player nội bộ và vòng đời phát nhạc. |
| `Api.cpython-39-arm-linux-gnueabihf.so` | REST API, SSE, MQTT, AirPlay DBus, diagnostics và watchdog. |
| `Api_HTTP.cpython-39-arm-linux-gnueabihf.so`, `Api_MQTT.cpython-39-arm-linux-gnueabihf.so` | Các handler HTTP/MQTT được tách khỏi lớp điều phối API chính. |
| `Streaming.cpython-39-arm-linux-gnueabihf.so` | WebSocket server, session client và streaming STT. |
| `Bluetooth.cpython-39-arm-linux-gnueabihf.so` | Theo dõi BlueZ, metadata và trạng thái Bluetooth. |
| `Spotify_Connect.cpython-39-arm-linux-gnueabihf.so` | Tích hợp `go-librespot` (WebSocket sự kiện + HTTP API điều khiển), đồng bộ metadata và âm lượng Spotify Connect. |
| `DLNA_Renderer.cpython-39-arm-linux-gnueabihf.so` | Tích hợp UPnP/DLNA renderer `gmediarender` qua D-Bus/MPRIS. |
| `Lib_AEC3.cpython-39-arm-linux-gnueabihf.so` | Quản lý khử vọng âm học WebRTC 2.1 AEC3, tham chiếu `vbot_aec_media` và xử lý tín hiệu microphone. |
| `LAN_Calls.cpython-39-arm-linux-gnueabihf.so`, `WebUI_Call.cpython-39-arm-linux-gnueabihf.so` | Gọi điện nội bộ hai chiều giữa các loa VBot qua PJSIP (`G.722 16 kHz`) và gọi từ trình duyệt WebUI qua HTTPS. |
| `Wake_On_LAN.cpython-39-arm-linux-gnueabihf.so`, `Local_Tuya.cpython-39-arm-linux-gnueabihf.so`, `Internal_IR.cpython-39-arm-linux-gnueabihf.so` | Đánh thức thiết bị qua Magic Packet LAN, điều khiển thiết bị Tuya cục bộ và phát/học hồng ngoại nội bộ. |
| `Sync_System_Services.cpython-39-arm-linux-gnueabihf.so` | Tự động kiểm tra và đồng bộ cấu hình các systemd service phụ trợ (`shairport-sync`, `bluealsa-aplay`, `gmediarender`, `vbot-spotify-connect`, `vbot-multiroom-bridge`). |
| `Button.cpython-39-arm-linux-gnueabihf.so` | Nút nhấn và rotary encoder. |
| `Led.cpython-39-arm-linux-gnueabihf.so` | Hiệu ứng LED và khôi phục trạng thái nghỉ. |
| `Scheduler.cpython-39-arm-linux-gnueabihf.so` | Lịch thông báo và tác vụ định kỳ. |
| `Update_Manager.cpython-39-arm-linux-gnueabihf.so`, `Update_Backup.cpython-39-arm-linux-gnueabihf.so`, `Update_Paths.cpython-39-arm-linux-gnueabihf.so` | Điều phối cập nhật từ WebUI, REST API và MQTT; quản lý sao lưu, đường dẫn, LED, âm báo, log và restart. |
| `Manual_Update_Program.py` | Cập nhật/rollback chương trình qua SSH và bàn giao kết quả cho Update Manager (giữ dạng `.py`). |
| `Manual_Update_WebUI.py` | Cập nhật/rollback riêng cây giao diện WebUI (giữ dạng `.py`). |
| `VBot_Multiroom_*.cpython-39-arm-linux-gnueabihf.so` | Bridge, receiver, coordinator, group và cấu hình Multiroom Audio. |
| `resource/HomeKit/` | VBot HomeKit Bridge, registry phụ kiện, trình cài Node.js và systemd user service. |
| `AI_Engine.cpython-39-arm-linux-gnueabihf.so`, `AI_Skill_Registry.cpython-39-arm-linux-gnueabihf.so`, `AI_Action_Dispatcher.cpython-39-arm-linux-gnueabihf.so`, `AI_Knowledge_Base.cpython-39-arm-linux-gnueabihf.so`, `AI_Vision.cpython-39-arm-linux-gnueabihf.so` | Bộ xử lý AI Function Calling (Groq/Gemini/OpenAI-Compat), Thị giác máy tính Zero-disk (`AI_Vision`), Local Intent Cache, Telemetry, Kho Tri Thức AI Cục Bộ (Hybrid FTS5 + Vector RRF) và thực thi công cụ thông minh. |
| `Dev_AI_Engine.py` | Điểm mở rộng AI tùy biến cho người dùng (giữ dạng `.py` không biên dịch `.so`). |
| `Lib_System.cpython-39-arm-linux-gnueabihf.so`, `Lib_Audio.cpython-39-arm-linux-gnueabihf.so`, `Lib_AirPlay.cpython-39-arm-linux-gnueabihf.so`, `Lib_Bluetooth.cpython-39-arm-linux-gnueabihf.so`, `Lib_Multiroom.cpython-39-arm-linux-gnueabihf.so`, `Lib_Picovoice_Utils.cpython-39-arm-linux-gnueabihf.so`, `Lib_State_Audio.cpython-39-arm-linux-gnueabihf.so`, `Lib_Device_Runtime.cpython-39-arm-linux-gnueabihf.so` | Các lớp chức năng được tách khỏi facade `Lib` để tối ưu tốc độ build `.so` và bảo trì. |
| `Data_Processing_HASS.cpython-39-arm-linux-gnueabihf.so`, `Data_Processing_Media_Info.cpython-39-arm-linux-gnueabihf.so`, `Def_Processing_HASS.cpython-39-arm-linux-gnueabihf.so`, `Def_Processing_Media.cpython-39-arm-linux-gnueabihf.so`, `Def_Processing_Weather.cpython-39-arm-linux-gnueabihf.so` | Các module con được tách từ `Data_Processing` và `Def_Processing` theo mô hình Facade. |
| `html/` | WebUI PHP, JavaScript, CSS, script trung gian `includes/php_ajax/AI_Knowledge_Indexer.py` và tài nguyên giao diện. |
| `resource/` | Âm thanh, service, script cài đặt và dữ liệu tích hợp. |

### Nguyên tắc xử lý bất đồng bộ

- I/O mạng trong STT, TTS, API và WebSocket ưu tiên async.
- Tác vụ blocking được đẩy sang thread khi cần.
- Lệnh hệ thống, GPIO, DBus hoặc thư viện phần cứng không bắt buộc chuyển sang async nếu bản thân thư viện là đồng bộ.
- Các hàng đợi có giới hạn để tránh tăng RAM không kiểm soát trên Pi Zero 2W.
- Shutdown hủy task, đóng loop và dừng thread theo thứ tự để tránh tự khởi động lại dịch vụ.

## Luồng xử lý giọng nói

```text
Chờ từ khóa đánh thức (Porcupine / Snowboy)
        │
        ▼
Phát âm báo + cập nhật LED + Face.php (listening)
        │
        ▼
Thu âm ───── nhấn giữ nút ────► hủy thu âm, về trạng thái chờ
        │
        ▼
STT (nhận dạng giọng nói) → Face.php (thinking)
        │
        ├── [Chế độ AI: processing_pipeline.mode = "ai"]
        │       ├── 1. Lệnh HASS Custom & Lệnh hệ thống SYS_CMD (0 token)
        │       ├── 2. Local Intent Cache - ai_intent_cache.json (0 token, < 50ms)
        │       └── 3. AI Function Calling (Groq / Gemini / Dev_AI_Engine)
        │               ├── Điều khiển Nhà thông minh (HASS đơn/hàng loạt theo phòng, Tuya, Alias)
        │               ├── Kho Tri Thức AI (search_knowledge: Catalog + Hybrid FTS5 & Vector RRF
        │               │     + Neighbor Window → tự chuyển web_search nếu tài liệu không có)
        │               ├── Tìm kiếm Internet thời gian thực (web_search)
        │               ├── Media, Bản tin, Nhật ký, Bộ nhớ dài hạn, Đếm ngược, Thông báo
        │               └── Tự dự phòng sang VBot từ khóa nếu AI lỗi (fallback_to_vbot_on_error)
        │
        └── [Chế độ VBot từ khóa: processing_pipeline.mode = "vbot"]
                ├── Kịch bản giọng nói (Voice Routines)
                ├── Home Assistant / Local Tuya / MQTT / Broadlink
                ├── Media / Radio / Podcast / Tin tức
                ├── Trợ lý AI & Tác vụ hệ thống
        │
        ▼
TTS (tổng hợp song song câu dài) + LED + Face.php (21 cảm xúc + phụ đề tự cuộn)
        │
        └── tự mở mic tiếp nếu AI hỏi làm rõ (?) hoặc bật chế độ hội thoại (conversation_mode)
```

## Audio, AirPlay, Bluetooth, DLNA và Spotify Connect

VBot có nhiều nguồn cùng sử dụng loa. Trạng thái được xuất qua API để xác định nguồn hiện tại:

- `Local`: media player nội bộ từ các nguồn nhạc, giải trí tích hợp sẵn.
- `Spotify`: luồng nhạc đang phát từ ứng dụng Spotify qua Spotify Connect (`go-librespot`).
- `AirPlay`: AirPlay2 (Tương thích với hệ sinh thái Apple) đang phát.
- `Bluetooth`: thiết bị Bluetooth (Kết nối phát nhạc từ điện thoại ra loa VBot thông qua Bluetooth) đang phát.
- `DLNA`: luồng nhạc đang được gửi tới renderer DLNA của VBot.
- `Multiroom`: loa đang phát đồng bộ trong nhóm Multiroom Audio.
- `N/A`: không có nguồn media xác định.

### Spotify Connect

VBot tích hợp **Spotify Connect** thông qua daemon nhẹ `go-librespot` (chạy nền bằng systemd `vbot-spotify-connect.service`), cho phép chọn phát nhạc trực tiếp lên loa VBot từ ứng dụng Spotify trên điện thoại, máy tính bảng hoặc PC trong cùng mạng.

- Tên hiển thị mặc định lấy theo hồ sơ loa VBot (hoặc đặt riêng tại `media_player.spotify_connect.device_name`), hỗ trợ chọn chất lượng luồng `96` / `160` / `320 kbps` và âm lượng khởi tạo.
- Đồng bộ thời gian thực qua WebSocket nội bộ (`127.0.0.1:3678/events`): trạng thái phát/tạm dừng, tên bài hát, nghệ sĩ, album, ảnh bìa, thời lượng, vị trí phát và thay đổi âm lượng hai chiều.
- Hỗ trợ điều khiển Play, Pause, Resume, Stop, Next, Previous, Seek và Volume trực tiếp từ Dashboard WebUI, nút nhấn vật lý, câu lệnh giọng nói, REST API, MQTT, Apple HomeKit và Home Assistant Custom Component.
- Tự động xuất âm thanh vào tuyến chung `vbot_aec_media` khi hệ thống bật khử vọng AEC3 / Multiroom Bridge (vẫn gọi được từ khóa đánh thức khi đang nghe nhạc Spotify).

Cài đặt lần đầu trên Raspberry Pi:

```bash
sudo bash /home/pi/VBot_Offline/resource/spotify/install.sh
```

Sau khi cài đặt, vào **WebUI → Config.php → Nguồn Nhạc / Media Player → Spotify Connect**, bật kích hoạt, lưu cài đặt và khởi động lại VBot.

### DLNA Audio Renderer

VBot có thể hoạt động như một **UPnP/DLNA audio renderer** để nhận nhạc từ ứng dụng điều khiển trong cùng mạng. Renderer `gmediarender` phát âm thanh vào sink ALSA dùng chung của VBot, giúp luồng DLNA đi qua tuyến âm thanh hệ thống hiện có.

- Tên thiết bị được quảng bá theo dạng `VBot {full_name}` lấy từ `contact_info.full_name` trong `Config.json`.
- Trạng thái phát, tiêu đề, nghệ sĩ, album, ảnh bìa (nếu ứng dụng gửi), thời lượng, vị trí phát, âm lượng và địa chỉ ứng dụng gửi được đồng bộ qua D-Bus/MPRIS.
- Có thể điều khiển phát, tạm dừng, tiếp tục, dừng, tua, âm lượng và mute qua WebUI, REST API hoặc MQTT. Next/Previous khả dụng tùy URI kế tiếp hoặc lịch sử mà ứng dụng gửi nhạc cung cấp.
- Khi được bật, DLNA xuất hiện như một nguồn media của VBot; trạng thái media và âm thanh được phối hợp với các luồng phát khác.
- Cài renderer trên Raspberry Pi OS/Debian bằng `sudo sh resource/dlna_renderer/install.sh`, bật **DLNA Audio Renderer** trong WebUI rồi khởi động lại VBot.

Chi tiết cài đặt và các lệnh D-Bus xem tại [`resource/dlna_renderer/README.md`](resource/dlna_renderer/README.md).

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

## WebRTC 2.1, Khử vọng AEC3 và Gọi nội bộ LAN (PJSIP)

VBot hỗ trợ bộ xử lý âm thanh số gốc C++ **WebRTC Audio Processing 2.1** (khử vọng âm học `AEC3`, giảm nhiễu nền `NS`, lọc thông cao `HPF`, cân bằng giọng nói tự động `Digital AGC + Limiter`) kết hợp thư viện **PJSIP (PJSUA2)** cho đàm thoại nội bộ hai chiều băng rộng (`G.722 16 kHz`), được cài đặt độc lập tại `/home/pi/VBot_AEC3/`.

### Cách 1 (Khuyên dùng): Thiết lập và kiểm tra tự động trọn gói bằng 1 lệnh

Trên hai bản OS Image chính thức của VBot (**VBot AIO I2S** và **VBot AIO WM8960**), sau khi cập nhật chương trình VBot lên bản mới nhất, chỉ cần chạy duy nhất một lệnh qua SSH:

```bash
sudo bash /home/pi/VBot_Offline/resource/tools/fix_and_verify_audio_webrtc.sh
```

Script tự động thực hiện toàn bộ quy trình và kiểm tra đối chiếu 24 hạng mục:
- Tự giải nén và cài gói runtime WebRTC 2.1 + PJSUA2 đã biên dịch sẵn (`vbot_audio_runtime_armhf_py39.zip`) vào `/home/pi/VBot_AEC3` nếu thiết bị chưa cài.
- Nhận diện chính xác loại mạch từ `/os_image_created.txt`, chuẩn hóa `/etc/asound.conf` ở `48000 Hz` (`S32_LE` + `i2s_dac_keepalive` tối ưu CPU cho mạch **I2S**; `period_size = 1024` chống tràn hàng đợi tham chiếu + bảo vệ cấu hình khi khởi động cho mạch **WM8960**).
- Cân chỉnh chuẩn mức gain phần cứng trong `alsamixer` để triệt tiêu hiện tượng vỡ tiếng (clipping) trước khi vào AEC3.
- Thiết lập tuyến âm thanh chung `vbot_aec_media` (`snd-aloop`) cho VLC, Multiroom, AirPlay, Bluetooth và Spotify Connect.
- Tự động điền bộ thông số tối ưu theo từng mạch vào `Config.json` cho cả **WebRTC MIC + AEC3** lẫn **Gọi nội bộ LAN (`lan_calls.pjsip`)**.
- Chế độ chỉ kiểm tra (không ghi thay đổi): `bash /home/pi/VBot_Offline/resource/tools/fix_and_verify_audio_webrtc.sh --check-only`.

### Cách 2: Cài đặt thủ công hoặc tự biên dịch từ mã nguồn

- [Cài từ ZIP đã build sẵn](resource/tools/README_INSTALL_WEBRTC21_PJSIP_PREBUILT.md), dành cho thiết bị ARMv7 (`armhf`), Python 3.9, Raspberry Pi OS Bullseye tương thích.
- [Build thủ công từng bước từ mã nguồn](resource/tools/README_INSTALL_WEBRTC21_PJSIP_MANUAL.md), khi đổi phiên bản Python, kiến trúc CPU hoặc bản phân phối HĐH.
- [Hướng dẫn cài đường âm thanh chung `vbot_aec_media`](resource/tools/README_INSTALL_SHARED_MEDIA_ROUTES.md).

Trên giao diện `Config.php`, cả hai khu vực **Xử lý âm thanh MIC — WebRTC Audio Processing** và **Gọi Điện Trong Mạng Nội Bộ (Call Local Lan)** đều có nút **Đặt lại mặc định các thông số** giúp tự động nhận diện mạch (**I2S** hoặc **WM8960**) để khôi phục ngay bộ thông số chuẩn bất kỳ lúc nào. Tài liệu chi tiết cũng được tích hợp sẵn trong WebUI tại `FAQ.php` → **Cài WebRTC 2.1 và PJSIP**.

## Multiroom Audio

Multiroom cho phép một loa VBot làm **coordinator** và phân phối audio tới các loa thành viên trong cùng mạng LAN. Kiến trúc gồm bridge điều phối nguồn, receiver trên từng loa, controller và kho cấu hình nhóm.

Các khả năng chính:

- Tạo, đổi tên và xóa nhóm loa.
- Thêm hoặc loại bỏ thành viên trong nhóm.
- Chuyển giữa phát Local và Multiroom.
- Start, stop, pause, continue và điều khiển volume từ API/MQTT.
- Đồng bộ title, artist, album, cover, URL, duration và vị trí phát.
- Phân biệt volume tổng của coordinator với volume luồng tại receiver.
- Tự phát hiện VBot trong LAN qua mDNS/Zeroconf.
- Khôi phục nguồn Local và trạng thái LED khi phiên Multiroom kết thúc hoặc mất kết nối.
- Không đánh dấu VBot sẵn sàng nhận stream cho đến khi chuỗi khởi động và âm báo startup hoàn tất.

Trạng thái Multiroom được đưa vào payload media dùng chung và endpoint diagnostics. Khi Multiroom đang phát, LED và API không được báo nhầm thành Local Media.

## Liên kết Apple HomeKit

VBot có thể xuất các chức năng của loa lên ứng dụng **Nhà (Apple Home)** thông qua
**VBot HomeKit Bridge**, xây dựng bằng HAP-NodeJS. Bridge chạy hoàn toàn trong mạng
nội bộ và liên kết với chương trình VBot qua hai luồng:

- REST API VBot nhận lệnh điều khiển như volume, mute/unmute, phát/tạm dừng,
  wakeup, microphone, chế độ hội thoại, playlist, radio và Multiroom.
- SSE `all_info` đẩy trạng thái hiện tại trở lại HomeKit mà không cần polling liên tục.

Người dùng có thể chọn kiểu phụ kiện chính `speaker`, `television`, `switches` hoặc
`hybrid`. Chế độ `hybrid` cung cấp đồng thời loa, các tile điều khiển tương thích rộng
và giao diện **ĐK Từ Xa (Remote)** của iOS. Các phím Remote có thể được gán action
riêng theo sáu trạng thái: Playlist, chờ, đang phát, đang tạm dừng, TTS và thu âm.
Phím Volume +/− trong cấu hình tương ứng với hai phím âm lượng vật lý cạnh iPhone
khi Remote đang mở.

HomeKit dùng OTP hàng `28:` từ `vcgencmd otp_dump` làm danh tính phần cứng ổn định,
tránh trùng accessory khi nhiều loa được tạo từ cùng một image hệ điều hành. Dữ liệu
pairing được giữ tại `/home/pi/VBot_Node/HomeKit/persist`; không xóa thư mục này khi
cập nhật nếu muốn giữ nguyên liên kết với Apple Home.

### Cài đặt HomeKit lần đầu

```bash
cd /home/pi/VBot_Offline/resource/HomeKit
chmod +x install.sh
./install.sh
```

Trình cài đặt kiểm tra và cài đúng Node.js, đặt dependency tại
`/home/pi/VBot_Node/HomeKit/node_modules`, cài `vbot-homekit.service` và không tự chạy
VBot. Sau đó mở WebUI, vào **Config → Liên Kết, Thêm Loa VBot Vào HomeKit**, bật
HomeKit, chọn kiểu phụ kiện, lưu cấu hình rồi khởi động lại VBot. Bridge chỉ được chạy
sau khi API VBot sẵn sàng và sẽ dừng theo vòng đời VBot.

Mã QR ghép đôi được tạo tại:

```text
/home/pi/VBot_Node/HomeKit/HomeKit_Pairing_QR.svg
```

Có thể mở mã QR ngay trong WebUI rồi chọn **Nhà → + → Thêm phụ kiện** trên iPhone.
Thay đổi cấu hình không cần chạy lại `install.sh` hoặc pair lại; chỉ cần lưu và khởi
động lại VBot. Bridge không theo dõi file và không tự restart giữa lúc VBot đang chạy,
nhằm giữ kết nối ổn định và tránh dồn lệnh điều khiển.

Tài liệu chi tiết, kiểm tra và xử lý sự cố nằm tại
[`resource/HomeKit/README.md`](resource/HomeKit/README.md) và mục HomeKit trong
[`html/FAQ.php`](html/FAQ.php).

## Tra cứu âm lịch và dương lịch

VBot có thể lấy ngày hiện tại từ đồng hồ hệ thống, nhận các mốc `hôm nay`,
`ngày mai`, `ngày kia`, `hôm qua` hoặc ngày cụ thể dạng `20/09/2026` và
`ngày 20 tháng 9 năm 2026`. Khi hỏi âm lịch, ngày dương được chuyển sang âm
lịch Việt Nam theo múi giờ UTC+7, bao gồm tháng nhuận và tên năm Can Chi.

Chọn nguồn xử lý trong `Config.json`:

```json
"calendar": {
    "source": "system"
}
```

Các giá trị hỗ trợ:

- `system`: VBot tự xử lý lịch âm và dương, không cần Internet.
- `virtual_assistant_priority`: ưu tiên trợ lý ảo; nếu trợ lý không phản hồi thì
  tự động dùng lịch hệ thống.
- `dev_calendar`: gọi `Dev_Calendar.py`; nếu file tùy chỉnh lỗi hoặc không trả
  kết quả thì tự động dùng lịch hệ thống.

Hàm `custom_calendar()` trong `Dev_Calendar.py` nhận câu gốc, câu đã chuẩn hóa,
loại lịch và dữ liệu ngày đã quy đổi; hàm phải trả về `(audio, text)`. Sau khi
đổi cấu hình nguồn, hãy khởi động lại chương trình VBot để nạp đúng module.


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

- [`README_Socket.md`](README_Socket.md)

## REST API, SSE và MQTT

### REST API

Cổng API được cấu hình tại:

```json
{
  "api": {
    "port": 5002
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
- Snapshot media gồm nguồn phát, metadata, timeline và trạng thái playlist.
- Topic runtime cho danh sách playlist và trạng thái Multiroom.
- Điều khiển playlist theo ID, next/previous và điều khiển nhóm Multiroom.
- Kích hoạt cập nhật `program` hoặc `interface` qua cùng `Update_Manager` như REST API.

## Cập nhật chương trình và WebUI

VBot dùng chung `Update_Manager.py` cho yêu cầu cập nhật từ WebUI, REST API và MQTT. Trình cập nhật thủ công qua SSH cũng sử dụng cùng định dạng file kết quả để hành vi giữa các đường cập nhật nhất quán.

### Vòng đời cập nhật chương trình

```text
Nhận yêu cầu cập nhật
        │
        ▼
Khóa nâng cấp + bật LED UPDATE
        │
        ▼
Tải và kiểm tra gói → backup → merge Config → cài đặt
        │
        ├── lỗi: rollback dữ liệu
        │
        ▼
Ghi kết quả thành công/thất bại vào log
        │
        ▼
Phát hết âm báo kết quả → khôi phục LED
        │
        ▼
Restart VBot_Offline.service ở bước cuối
```

Các đặc điểm an toàn:

- Không cho hai tác vụ cập nhật chương trình chạy đồng thời.
- Marker và `flock` giúp nhận biết updater thật sự còn hoạt động sau khi bàn giao giữa các process.
- Không dùng timeout cố định 180 giây trong lúc chờ đóng gói backup lớn.
- Tự phục hồi nếu updater kết thúc nhưng không ghi được file kết quả.
- Xác thực `status`, `target` và tên service trước khi xóa file kết quả.
- Kiểm tra mã trả về khi yêu cầu systemd lên lịch restart.
- `--check-only` không ghi kết quả, không phát âm báo và không restart.
- `--no-restart` vẫn ghi/phát kết quả nhưng giữ service hiện tại.

Trong lúc cập nhật, VBot dùng hiệu ứng LED `UPDATE`. Với LED tùy chỉnh, có thể thêm:

```python
def LED_UPDATE():
    # Hiệu ứng cập nhật tùy chỉnh
    ...
```

Nếu `Dev_Led.py` không có hàm này, hệ thống tự dùng `LED_STARTUP` và ghi cảnh báo thay vì làm hỏng tác vụ cập nhật.

## Runtime Diagnostics và Watchdog

### API diagnostics

Endpoint:

```http
GET /runtime/diagnostics
```

Ví dụ:

```bash
curl http://127.0.0.1:5002/runtime/diagnostics
```

Nếu bật API authentication:

```bash
curl -H 'VBot-API-Key: YOUR_API_KEY' \
  http://127.0.0.1:5002/runtime/diagnostics
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

- Dashboard (`index.php`): điều khiển nhanh chế độ, trình phát Media đa nguồn tích hợp trực tiếp (Local, Playlist, YouTube, ZingMP3, Podcast, Radio, Tin tức, Spotify Connect, AirPlay, Bluetooth, DLNA, Multiroom), âm lượng và nút **Đồng bộ `Home_Assistant.json`**.
- Cấu hình `Config.json` (`Config.php`): bao gồm cấu hình AI/Vision, WebRTC Audio Processing & AEC3, Spotify Connect, Gọi nội bộ LAN/PJSIP (kèm nút tự nhận diện mạch đặt lại thông số chuẩn).
- Kho Tri Thức AI Cục Bộ (`AI_Knowledge.php`): tải lên 27 định dạng tài liệu/ảnh/URL, quản lý chỉ mục Hybrid FTS5 + Vector, Search Playground và sao lưu/khôi phục.
- Gọi điện nội bộ LAN & Gọi từ trình duyệt (`LAN_Calls.php`): quay số giữa các loa VBot, gọi thử trực tiếp từ trình duyệt WebUI qua HTTPS, quét thiết bị LAN và sao lưu danh bạ loa.
- Kịch bản giọng nói (`Voice_Routines.php`), Local Tuya (`Tuya_Local.php`), Wake-on-LAN (`Wake_On_LAN.php`) và Bộ cân bằng âm thanh 6 dải (`Audio_Equalizer.php`).
- Command/Terminal.
- Kiểm tra câu lệnh (`Command_Test.php`): hỗ trợ **cả 2 chế độ (AI & VBot nội bộ)** — xem nhánh xử lý AI, Local Intent Cache (0 token), xem trước Tool/Câu trả lời AI dự kiến hoặc keyword/đích HASS, và tùy chọn thực thi thật.
- Thống kê AI & Hạn mức (`AI_Stats.php`): theo dõi số lượt gọi, tỷ lệ khớp Cache, token tiêu thụ, kiểm tra hạn mức API Key Groq và Nhật ký giọng nói.
- Màn hình cảm xúc OLED (`Face.php`) & Client độc lập (`VBot_Face_Client.html`): hiển thị khuôn mặt 21 cảm xúc chuẩn XiaoZhi, đĩa nhạc xoay, thời tiết, đồng hồ đếm ngược, trạng thái nghe/nói và sóng nhạc theo thời gian thực.
- REST API.
- Scheduler.
- Log VBot, TTS và API.
- Quản lý VBot Client/WebSocket.
- Thông tin hệ thống.
- Chẩn đoán Runtime.
- Backup và nâng cấp.
- Multiroom, playlist và trạng thái media đang phát.
- Cloudflare Tunnel và Tailscale Funnel.
- Quản lý XiaoZhi MCP, mô tả công cụ và plugin tùy chỉnh.
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

1. Flash Ghi image bằng Raspberry Pi Imager hoặc Balena Etcher.
2. Khởi động Raspberry Pi và cấu hình Wi-Fi (Có thể cấu hình bằng kết nối wifi phát ra từ pi hoặc thông qua bluetooth với APP tên là: BTBerryWifi).
3. Khi kết nối Wifi thành công sau khoảng 1-2 phút loa sẽ đọc địa chỉ ip, truy cập WebUI bằng địa chỉ IP của thiết bị.
4. Kiểm tra microphone, loa, LED và nút nhấn.
5. Cập nhật thông tin cá nhân, API key và token dịch vụ trong WebUI.
6. Khởi động lại VBot (Restart vbot) sau khi mỗi lần thay đổi cấu hình để áp dụng dữ liệu

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

### Chạy thủ công VBot

Dừng service đang chạy tự động trước để tránh hai tiến trình cùng giữ microphone/loa:

```bash
systemctl --user stop VBot_Offline.service
cd /home/pi/VBot_Offline
python3 Start.py
```

Nhấn `Ctrl+C` để dừng chạy thủ công. Sau khi kiểm tra xong và khôi phục lại chạy tự động:

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

- `processing_pipeline`: chọn chế độ xử lý (`vbot` hoặc `ai`), nhà cung cấp AI chính, Vision Camera, Kho Tri Thức AI, bộ nhớ và Intent Cache.
- `contact_info`: thông tin người dùng và đăng nhập WebUI.
- `smart_config`: wake word, STT, TTS, microphone, xử lý âm thanh WebRTC 2.1 & AEC3 (`audio_processing`), LED, nút và loa.
- `virtual_assistant`: các trợ lý AI (Groq, Gemini, OpenAI-Compatible/Ollama, ChatGPT, Dify, Olli, Zalo...).
- `media_player`: nguồn nhạc nội bộ, Spotify Connect (`spotify_connect`), AirPlay, DLNA, Multiroom và hành vi phát media.
- `lan_calls` & `wake_on_lan`: gọi nội bộ LAN qua PJSIP, gọi từ trình duyệt WebUI và đánh thức thiết bị qua mạng LAN.
- `homekit` & `xiaozhi`: liên kết Apple HomeKit Bridge và giao thức/MCP XiaoZhi.
- `api`: REST API, auth và streaming server.
- `mqtt_broker`: broker, client name, QoS và retain.
- `web_interface`: đường dẫn WebUI, bảo mật truy cập tệp và external access.
- `backup_upgrade`: backup và nâng cấp.

### Dữ liệu nhạy cảm

Các file cấu hình/token có thể chứa:

- API key.
- MQTT username/password.
- Home Assistant token.
- STT/TTS credential.
- Thông tin mạng và người dùng.

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
python -m unittest tests.test_core_regression
python -m unittest tests.test_audio_concurrency_fixes tests.test_command_queue_backpressure
python -m unittest tests.test_runtime_diagnostics
python -m unittest tests.test_runtime_watchdog
python -m unittest tests.test_airplay_metadata
```

### Trên Raspberry Pi

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
curl -v http://127.0.0.1:5002/runtime/diagnostics
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
| `Dev_Led_Effects.py` | Hiệu ứng màu LED thủ công có thể tùy chỉnh; file Python thuần, không build `.so`. |
| `Dev_Music.py` | Nguồn media tùy chỉnh. |
| `Dev_Processing.py` | Logic xử lý văn bản riêng. |
| `Dev_Weather.py` | Nguồn thời tiết riêng. |
| `Dev_Logs.py` | Định tuyến log riêng. |
| `Dev_Picovoice.py` | Tùy chỉnh wake word/Picovoice. (Có thể sử dụng Picovoice không cần KEY)|

### Thêm hiệu ứng màu LED

`Dev_Led_Effects.py` chạy trực tiếp bằng Python và không được đưa vào danh sách build `.so`. Mỗi nhóm phần cứng gom các hàm hiệu ứng con và danh sách tên/nhãn vào một hàm tổng: `ws281x`, `apa102`, `respeaker_mic_array`, `custom`. Thêm hiệu ứng bằng cách viết hàm con trong đúng hàm tổng rồi thêm mục vào từ điển `effects` ở cuối hàm. API, MQTT và Home Assistant chỉ nhận hiệu ứng của loại LED đang cấu hình.

Ví dụ thêm hiệu ứng nhịp chậm bên trong hàm tổng `ws281x()`:

```python
def night_pulse(state, now, led_count):
    level = 0.15 + 0.85 * (0.5 + 0.5 * math.sin(now * 2 * math.pi / 8))
    scale = state["brightness"] / 255 * level
    color = tuple(round(channel * scale) for channel in state["rgb_color"])
    for index in range(led_count):
        set_pixel(index, color_factory(*color))
    strip.show()


effects["night_pulse"] = ("Night Pulse", night_pulse)
```

Các hàm con dùng trực tiếp driver của hàm tổng đang chứa chúng. `state` có `rgb_color` (ba kênh 0–255), `brightness` (0–255) và `effect`; `now` là thời gian đơn điệu tính bằng giây. Sau khi sửa file, khởi động lại VBot để nạp thay đổi. Không cần sửa `Led.py` hay build `.so` chỉ để thêm hiệu ứng màu.

Với phần cứng `dev_custom_led`, có thể thêm `LED_MANUAL_RENDER(frames)` trong `Dev_Led.py`; VBot sẽ chuyển danh sách pixel RGB đã tạo ở trên cho hàm này. Nếu chưa có hàm đó, VBot tiếp tục dùng `LED_MANUAL(state)` như trước.

### MCP Plugins cho trợ lý XiaoZhi

VBot hỗ trợ mở rộng công cụ MCP cho XiaoZhi mà không cần sửa trực tiếp
`VBot_XiaoAI.cpython-39-arm-linux-gnueabihf`. Mỗi thư mục trong `resource/xiaozhi/mcp_plugins` tương
ứng với một MCP tool độc lập:

```text
resource/xiaozhi/mcp_plugins/
└── ten_plugin/
    ├── plugin.json
    └── handler.py
```

- `plugin.json` khai báo trạng thái, tên tool, mô tả, `inputSchema`,
  entrypoint, hàm xử lý và timeout.
- `handler.py` nhận `arguments` từ XiaoZhi, xử lý dữ liệu hoặc điều khiển
  VBot rồi trả kết quả MCP.
- Handler có thể là hàm đồng bộ hoặc `async`.
- VBot kiểm tra arguments, giới hạn thời gian chạy và chuẩn hóa kết quả
  trước khi gửi lại server XiaoZhi.
- Plugin chỉ được nạp một lần khi VBot khởi động.

Có thể bật/tắt từng plugin tại WebUI:

```text
html/XiaoZhi_MCP.php → MCP Plugins do người dùng tự viết
```

Sau khi thêm hoặc bật/tắt plugin, cần khởi động lại VBot để áp dụng. Nút
**Hướng Dẫn MCP Plugin** trên WebUI hiển thị cấu trúc và ví dụ đầy đủ.

Dự án có sẵn ba plugin mẫu, mặc định đều tắt:

| Plugin | Công dụng |
|---|---|
| `self.vbot.echo` | Đọc lại nội dung để kiểm tra luồng MCP. |
| `self.vbot.system_status` | Trả thông tin mạng, IP, âm lượng và uptime của VBot. |
| `self.vbot.wikipedia_search` | Tìm kiếm kiến thức trên Wikipedia tiếng Việt. |

Tài liệu chi tiết: [`resource/xiaozhi/mcp_plugins.readme`](resource/xiaozhi/mcp_plugins.readme).

Nguyên tắc khi đóng góp hoặc mở rộng:

- Không thực hiện network I/O blocking trực tiếp trong event loop.
- Đặt timeout cho STT, TTS và HTTP request.
- Không tạo queue không giới hạn trên thiết bị RAM thấp.
- Không dùng biến global mới nếu trạng thái đã có nơi quản lý phù hợp.
- Mọi luồng phải có cơ chế stop và cleanup.
- Không để watchdog khởi động lại dịch vụ trong lúc shutdown.
- LED tạm thời phải khôi phục trạng thái nghỉ sau khi hoàn tất.
- Thêm test hồi quy cho lỗi đã sửa.

## Cập nhật thủ công qua SSH

Hai công cụ `Manual_Update_Program.py` và `Manual_Update_WebUI.py` dùng để cập nhật hoặc
rollback VBot khi WebUI không hoạt động. Đăng nhập SSH bằng tài khoản chạy VBot (thường là
`pi`), không thêm `sudo` trước lệnh Python:

```bash
ssh pi@DIA_CHI_IP_VBOT
cd /home/pi/VBot_Offline
python3 Manual_Update_Program.py --help
python3 Manual_Update_WebUI.py --help
```

### Cập nhật chương trình

Chạy không có `--apply` sẽ cập nhật ngay, merge giá trị `Config.json` cũ vào mẫu Config mới,
tạo rollback, ghi log, phát âm báo kết quả rồi mới restart `VBot_Offline.service` ở bước cuối:

```bash
python3 Manual_Update_Program.py
```

Chỉ tải và kiểm tra gói, không ghi dữ liệu:

```bash
python3 Manual_Update_Program.py --check-only
```

Cập nhật nhưng chưa restart service:

```bash
python3 Manual_Update_Program.py --no-restart
```

### Cập nhật WebUI

```bash
# Cập nhật ngay
python3 Manual_Update_WebUI.py

# Chỉ tải, kiểm tra ZIP và lint PHP
python3 Manual_Update_WebUI.py --check-only
```

Cập nhật WebUI không restart service VBot. Sau khi hoàn tất, dùng `Ctrl+F5` để tải lại giao diện.

### Repository, nhánh hoặc ZIP local

```bash
python3 Manual_Update_Program.py --repo https://github.com/USER/REPOSITORY --branch main
python3 Manual_Update_WebUI.py --repo https://github.com/USER/REPOSITORY --branch main

python3 Manual_Update_Program.py --zip /home/pi/VBot_Offline-main.zip
python3 Manual_Update_WebUI.py --zip /home/pi/VBot_Offline-main.zip
```

### Bảo vệ JSON và dữ liệu người dùng

Các JSON đang tồn tại được giữ lại mặc định, gồm `BackList.json`, token STT/TTS,
`Home_Assistant.json`, `Home_Assistant_Custom.json` và những JSON dữ liệu khác. JSON lõi như
`Version.json`, `resource/lang_keywords/vi-VN.json` và một số JSON hệ thống vẫn được
cập nhật. Chỉ ghi đè một JSON dữ liệu khi thực sự cần:

```bash
python3 Manual_Update_Program.py --replace-json Home_Assistant.json
python3 Manual_Update_WebUI.py --replace-json PlayList.json
```

WebUI giữ `html/includes/other_data` mặc định. Có thể giữ thêm thư mục tùy chỉnh:

```bash
python3 Manual_Update_WebUI.py --keep includes/other_data --keep includes/cache
```

### Backup rollback

Rollback thủ công được đóng gói theo định dạng:

```text
VBot_Program_DDMMYYYY_HHMMSS_releaseDate_version.tar.gz
VBot_Interface_DDMMYYYY_HHMMSS_releaseDate_version.tar.gz
```

Ví dụ: `VBot_Program_07082026_221844_05-08-2026_1.2.0.tar.gz`.

Backup Python có `created.json`, `Config.json` (đối với chương trình) và thư mục `files/` chứa
các file cũ. Backup đầy đủ do WebUI tạo có dữ liệu trực tiếp tại gốc archive. Hai công cụ tự
nhận diện và xử lý đúng cả hai cấu trúc.

Khôi phục bản chương trình mới nhất:

```bash
python3 Manual_Update_Program.py --rollback
```

Công cụ tìm `VBot_Program_*.tar.gz` mới nhất trong:

```text
/home/pi/VBot_Offline/Backup_Upgrade/Manual_Program
/home/pi/VBot_Offline/html/Backup_Upgrade/Backup_Program
```

Hoặc chỉ định một file:

```bash
python3 Manual_Update_Program.py --rollback /home/pi/VBot_Offline/Backup_Upgrade/Manual_Program/VBot_Program_09082026_131844_05-08-2026_1.2.0.tar.gz
```

Khôi phục WebUI mới nhất hoặc chỉ định file:

```bash
python3 Manual_Update_WebUI.py --rollback
python3 Manual_Update_WebUI.py --rollback /home/pi/VBot_Offline/Backup_Upgrade/Manual_WebUI/VBot_Interface_09082026_131844_05-08-2026_1.2.0.tar.gz
```

Backup WebUI được tìm trong:

```text
/home/pi/VBot_Offline/Backup_Upgrade/Manual_WebUI
/home/pi/VBot_Offline/html/Backup_Upgrade/Backup_Interface
```

File được chỉ định phải nằm trực tiếp trong một thư mục hợp lệ, đúng tiền tố và đuôi `.tar.gz`.
Archive được kiểm tra chống path traversal, symlink, hardlink và file thiết bị trước khi giải nén.
Rollback chương trình không thay đổi `html`; rollback WebUI chỉ khôi phục cây `html`.

### Quyền file, dừng giữa chừng và log

- Trình cập nhật chương trình chmod `0777` phần chương trình nhưng bỏ qua `TTS_Audio`, `Media`,
  toàn bộ `html`, `*.lock` và symlink.
- Trình cập nhật WebUI chỉ chmod `html`, bỏ qua `html/Backup_Upgrade`, `*.lock` và symlink.
- Khi nhấn `Ctrl+C` sau lúc bắt đầu ghi, công cụ rollback dữ liệu đã thay đổi, khôi phục Config,
  xóa marker và thoát với mã `130`. Không nhấn `Ctrl+C` lần hai trong khi đang rollback.
- Tất cả tiến trình được in ra console. Lỗi phát sinh và kết quả cuối cùng (thành công hoặc thất bại)
  được ghi vào `resource/log/Vbot_error.log`.
- Nếu VBot đang chạy, updater thủ công bàn giao kết quả cho `Update_Manager`. Nếu không có watcher
  nhận kết quả trong thời gian chờ, updater tự phát âm báo bằng trình phát hệ thống rồi restart.

```bash
tail -f /home/pi/VBot_Offline/resource/log/Vbot_error.log
systemctl --user status VBot_Offline.service --no-pager
```

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

## Sơ Đồ Đi Dây
<img width="1863" height="1494" alt="Image" src="https://github.com/user-attachments/assets/fa6e6391-126a-4a12-b450-365e3f1b3cf8" />

## WebUI VBot
<img width="2688" height="1360" alt="Image" src="https://github.com/user-attachments/assets/fc2ac10b-00d0-4b20-9c63-2367c3a101d0" />

## Home Assistant Custom Component (HASS)
<img width="2688" height="1360" alt="Image" src="https://github.com/user-attachments/assets/b917e0a2-e6ff-452c-9823-14d04e664791" />
