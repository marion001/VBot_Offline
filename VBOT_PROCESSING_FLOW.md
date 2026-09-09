# Sơ đồ xử lý và luồng hoạt động hoàn chỉnh của VBot

Tài liệu này mô tả kiến trúc đang được triển khai trong mã nguồn VBot. Mục tiêu là giúp người dùng, người viết `Dev_*.py` và người bảo trì biết dữ liệu đi qua đâu, trạng thái nào sở hữu microphone/audio, lúc nào phải kết thúc xử lý và cơ chế cập nhật–rollback hoạt động thế nào.

## 1. Bản đồ tổng thể

```mermaid
flowchart TD
    BOOT[Start.py] --> CFG[Lib.py: nạp Config.json và trạng thái dùng chung]
    CFG --> CORE[VBot.py: khởi tạo runtime]
    CORE --> HW[Mic / Wake word / LED / Media Player]
    CORE --> NET[HTTP API / MQTT / WebSocket Streaming]
    CORE --> BG[Scheduler / Multiroom / Home Assistant / XiaoZhi]

    HW --> INPUT{Nguồn yêu cầu}
    NET --> INPUT
    BG --> INPUT

    INPUT -->|Wake word + mic local| STT[STT_Processing.Select_STT]
    INPUT -->|PCM từ client| SSTT[Streaming STT]
    INPUT -->|Text API/MQTT| TEXT[Văn bản đầu vào]
    STT --> TEXT
    SSTT --> TEXT

    TEXT --> MODE{Chế độ xử lý}
    MODE -->|Mặc định| DP[Data_Processing.processing]
    MODE -->|Dev toàn phần| DEVP[Dev_Processing.dev_processing]
    MODE -->|Chatbot client| CHAT[Data_Processing.chatbot_async]
    MODE -->|STT → TTS| DIRECT[Trả trực tiếp transcript/TTS]

    DP --> ROUTE{Bộ định tuyến ý định}
    ROUTE --> DEVSKILL[Dev_Customization.dev_skill]
    ROUTE --> LOCAL[Lệnh hệ thống / âm lượng / media]
    ROUTE --> HASS[Home Assistant]
    ROUTE --> INFO[Giờ / lịch / thời tiết / tin tức]
    ROUTE --> AI[Assistant.Call_async]

    DEVP --> OUTPUT
    DEVSKILL --> OUTPUT
    LOCAL --> OUTPUT
    HASS --> OUTPUT
    INFO --> OUTPUT
    AI --> OUTPUT{Kiểu kết quả}
    CHAT --> OUTPUT
    DIRECT --> OUTPUT

    OUTPUT -->|Văn bản| TTS[TTS_Processing.Select_TTS_async]
    OUTPUT -->|Audio trả lời| ANSWER[Media Player: Play Answer]
    OUTPUT -->|Nhạc/stream| MEDIA[Media Player: Play Media]
    TTS --> ANSWER
    ANSWER --> FINISH[finish_processing theo owner]
    MEDIA --> FINISH
    FINISH --> CONV{Conversation mode?}
    CONV -->|Có| STT
    CONV -->|Không| IDLE[LED OFF / chờ wake word]
```

## 2. Khởi động hệ thống

```mermaid
sequenceDiagram
    participant OS as systemd/OS
    participant S as Start.py
    participant L as Lib.py
    participant V as VBot.py
    participant M as Media_Player.py
    participant N as API/MQTT/Streaming

    OS->>S: Khởi chạy chương trình
    S->>S: Kiểm tra Python và phụ thuộc bắt buộc
    S->>L: Import cấu hình/runtime
    L->>L: Đọc Config.json, tạo lock và state dùng chung
    S->>V: Chạy entrypoint chính
    V->>M: Khởi tạo MediaPlayer worker
    V->>N: Khởi tạo HTTP, MQTT và Streaming theo cấu hình
    V->>V: Khởi tạo mic, wake word, LED, scheduler và tích hợp
    V-->>OS: Runtime chuyển sang trạng thái chờ
```

Thành phần chính:

| Thành phần | Trách nhiệm |
|---|---|
| `Start.py` | Điểm vào, kiểm tra môi trường và khởi động VBot. |
| `Lib.py` | Nạp `Config.json`, cung cấp state, lock, logging, microphone ownership và tiện ích dùng chung. |
| `VBot.py` | Điều phối vòng đời, wake word, thu âm và gọi pipeline xử lý. |
| `Media_Player.py` | Phát âm báo, câu trả lời TTS, nhạc/stream và serialize điều khiển media. |
| `Api_HTTP.py` | Nhận lệnh WebUI/API, trả trạng thái và kích hoạt Update Manager. |
| `Api_MQTT.py` | Nhận/phát trạng thái và lệnh MQTT. |
| `Streaming.py` | Quản lý client, PCM, STT và chế độ xử lý của từng phiên. |

Nếu một dependency tùy chọn không có, module tương ứng phải bị vô hiệu hóa có kiểm soát; lỗi không được làm hỏng toàn bộ pipeline nếu chức năng đó không được bật.

## 3. Nguồn đầu vào

### 3.1 Wake word và microphone local

```mermaid
stateDiagram-v2
    [*] --> Idle
    Idle --> WakeDetected: Porcupine/Snowboy phát hiện từ khóa
    WakeDetected --> ClaimMic: xin owner microphone
    ClaimMic --> Listening: owner hợp lệ
    ClaimMic --> Idle: owner đang thuộc pipeline khác
    Listening --> Recognizing: chuyển frame PCM sang STT
    Recognizing --> Processing: có transcript cuối
    Recognizing --> Idle: timeout/lỗi/hủy
    Processing --> Speaking: có câu trả lời
    Speaking --> Listening: conversation_mode
    Speaking --> Idle: hoàn tất bình thường
```

Quy tắc quan trọng:

- Mọi frame mic đi qua cổng đọc chung trong `Lib`, không đọc native recorder đồng thời từ nhiều luồng.
- Một phiên phải chụp/giữ đúng owner. Phiên cũ không được giải phóng owner của phiên mới.
- Khi wake-up bị hủy, task STT và iterator mạng phải được đóng, không để worker tiếp tục đọc mic.
- LED phản ánh trạng thái (`THINK`, `SPEAK`, `ERROR`, `MUTE`, `OFF`) nhưng không phải nguồn sự thật của processing state.

### 3.2 Client Streaming

```mermaid
flowchart LR
    C[ESP/WebSocket Client] --> SESSION[Streaming session + generation]
    SESSION --> QUEUE[Audio queue có giới hạn]
    QUEUE --> BACKEND{STT backend}
    BACKEND -->|Mặc định| CORE[STT hệ thống]
    BACKEND -->|Dev| DEV[Dev_STT.dev_stt_streaming]
    CORE --> WM{working_mode}
    DEV --> WM
    WM -->|main_processing| DP[Data_Processing.processing]
    WM -->|chatbot| CB[chatbot_async]
    WM -->|stt_to_tts| TT[TTS trực tiếp]
    DP --> RESP[Audio/text về client]
    CB --> RESP
    TT --> RESP
```

Mỗi lần client reconnect có generation mới. Kết quả từ worker của generation cũ phải bị bỏ để không phát câu trả lời sai phiên.

### 3.3 HTTP API và MQTT

- Yêu cầu text có thể đi thẳng vào xử lý mà không chiếm mic local.
- API điều khiển media/âm lượng phải đi qua facade/queue tương ứng để tránh đua trạng thái.
- API cập nhật chỉ gửi yêu cầu cho `Update_Manager`; tiến trình phục vụ không tự ghi đè code đang chạy.
- MQTT dùng registry/topic đã cấu hình; phản hồi trạng thái phải phản ánh state runtime sau khi thao tác hoàn tất.

## 4. STT – chuyển âm thanh thành văn bản

Điểm điều phối chính là `STT_Processing.Select_STT()`.

```mermaid
flowchart TD
    A[Select_STT] --> B[Chọn backend từ Config]
    B --> D[Default/WebSocket]
    B --> G1[Google Cloud V1]
    B --> G2[Google Cloud V2]
    B --> DEV[Dev_STT.dev_stt]
    D --> R{Kết quả}
    G1 --> R
    G2 --> R
    DEV --> R
    R -->|Transcript hợp lệ| OK[Chuẩn hóa và ghi outcome]
    R -->|Đóng WS bình thường| EMPTY[Không coi là lỗi nghiêm trọng]
    R -->|Timeout/lỗi| ERR[Log + âm báo lỗi theo cấu hình]
    OK --> P[Data Processing]
    EMPTY --> IDLE[Giải phóng owner]
    ERR --> IDLE
```

Hợp đồng backend Dev:

- `Dev_STT.dev_stt()` trả `str` hoặc `None`.
- `Dev_STT.dev_stt_streaming(pcm_audio, sample_rate, client_id)` không đọc mic local; chỉ xử lý PCM được truyền vào.
- Transcript cuối của mic local được gán vào `Lib.stt_transcript` để tương thích pipeline.
- Request blocking của SDK phải chạy ngoài event loop.

## 5. Bộ xử lý câu lệnh

`Data_Processing.processing(text_input)` là cổng chính. Hàm bọc quản lý owner và gọi `_processing_impl`.

Thứ tự khái quát:

1. Kiểm tra input, chuẩn hóa chữ thường và từ khóa.
2. Nhận processing ownership.
3. Xử lý multi-command nếu câu chứa nhiều lệnh.
4. Thử các lệnh ưu tiên: trạng thái, mic, âm lượng, hệ thống và điều khiển media.
5. Thử tích hợp Home Assistant và các action đã cấu hình.
6. Thử thời tiết, lịch, nhắc việc, radio, podcast, báo và nguồn nhạc.
7. Nếu bật Custom Skill, gọi `Dev_Customization.dev_skill`.
8. Nếu chưa có handler nhận yêu cầu, chuyển sang trợ lý ảo.
9. Phát kết quả và giải phóng đúng owner.

### Ý nghĩa kết quả của Custom Skill

| Kết quả | Ý nghĩa |
|---|---|
| `True` | Skill đã xử lý xong; VBot không chạy các handler phía sau. |
| `False` hoặc `None` | Skill bỏ qua; pipeline mặc định tiếp tục. |
| Exception | Được ghi log; pipeline cố gắng tiếp tục theo nhánh dự phòng. |

`Dev_Customization.py` không được tự ý giải phóng owner không thuộc về nó. Với media metadata, dùng `Lib.vbot_state.set_media_metadata(...)` thay vì cập nhật từng biến rời rạc.

## 6. Chế độ Dev Processing toàn phần

Khi WebUI chọn chế độ người dùng tự xử lý, `Data_Processing` chuyển sang `Dev_Processing.dev_processing`.

```mermaid
flowchart TD
    I[Text input] --> CLAIM[claim owner dev_processing]
    CLAIM --> CUSTOM[Logic trong Dev_Processing]
    CUSTOM --> SKILL[Dev_Customization tùy chọn]
    CUSTOM --> CMD[Lệnh hệ thống/media]
    CUSTOM --> ASSIST[Assistant tùy chọn]
    SKILL --> DONE
    CMD --> DONE
    ASSIST --> DONE
    DONE[dev_finish_processing_async] --> RELEASE[finish_processing owner=dev_processing]
    RELEASE --> IDLE[Chờ hoặc hội thoại tiếp]
```

`_DEV_PROCESSING_OWNER` là định danh nội bộ. Không đổi nếu chưa sửa đồng bộ cơ chế ownership. Mọi đường return đã xử lý phải hoàn tất state; tránh gán trực tiếp một cờ Boolean cũ rồi bỏ lock/owner lại phía sau.

## 7. Assistant và TTS

### Assistant

`Assistant.Call_async` chọn backend theo độ ưu tiên và cấu hình. Backend Dev được gọi qua `Dev_Assistant.dev_assistant(text_input, used_for)`.

Hợp đồng trả về là `(audio, text)`:

- Có `audio`: có thể phát trực tiếp.
- Có `text` nhưng chưa có audio: chuyển qua TTS.
- Cả hai `None`: backend thất bại/bỏ qua, pipeline dùng fallback nếu có.

### TTS

```mermaid
flowchart LR
    TEXT[Văn bản] --> KEY[Chuẩn hóa cache key]
    KEY --> CACHE{Cache hợp lệ?}
    CACHE -->|Có| AUDIO[Audio local]
    CACHE -->|Không| SELECT[Select_TTS_async]
    SELECT --> BACKEND{Backend}
    BACKEND --> EDGE[Edge]
    BACKEND --> GCLOUD[Google]
    BACKEND --> ZALO[Zalo]
    BACKEND --> DEV[Dev_TTS.dev_tts]
    EDGE --> SAVE[Ghi file nguyên tử]
    GCLOUD --> SAVE
    ZALO --> SAVE
    DEV --> SAVE
    SAVE --> AUDIO
    AUDIO --> PLAY[play_answer_async]
```

Cache chỉ coi file hoàn chỉnh là kết quả; file tạm/đang ghi không được phát. Backend thất bại phải trả `None`, không trả đường dẫn giả. API key/credentials không được ghi vào log hoặc commit công khai.

## 8. Media Player và quyền sở hữu audio

VBot phân biệt ba nhóm phát:

| Nhóm | API tiêu biểu | Dùng cho |
|---|---|---|
| Sound | `play_sound_async` | Ding/dong, thông báo hệ thống, trạng thái cập nhật. |
| Answer | `play_answer_async` | Câu trả lời TTS, thường phát một lần. |
| Media | `play_media_async` | Nhạc, radio, podcast, playlist, stream dài. |

Điều khiển play/pause/continue/stop được serialize để hai nguồn không cùng thay đổi VLC. State media (URL, title, cover, source, vị trí, pause/mute) phải cập nhật qua state/facade dùng chung để WebUI, MQTT, Multiroom và XiaoZhi nhìn thấy cùng một trạng thái.

Khi một nguồn mới chiếm audio:

1. Xác định nguồn hiện tại.
2. Dừng/tạm dừng nguồn cũ theo chính sách takeover.
3. Cập nhật metadata và generation.
4. Bắt đầu nguồn mới.
5. Chỉ callback đúng generation mới được thay đổi trạng thái cuối.

## 9. LED

```mermaid
stateDiagram-v2
    [*] --> STARTUP
    STARTUP --> OFF
    OFF --> THINK: wake/input
    THINK --> SPEAK: có câu trả lời
    THINK --> ERROR: xử lý thất bại
    SPEAK --> OFF: hoàn tất
    OFF --> MUTE: tắt mic
    MUTE --> OFF: bật mic
    OFF --> PAUSE: media pause
    OFF --> UPDATE: cập nhật
    UPDATE --> OFF: cập nhật hoàn tất
```

`Dev_Led.py` phải giữ các hàm `LED_OFF`, `LED_LOADING`, `LED_THINK`, `LED_MUTE`, `LED_ERROR`, `LED_PAUSE`, `LED_SPEAK`, `LED_STARTUP`, `LED_UPDATE`, `LED_VOLUME`. Hiệu ứng lặp phải thường xuyên kiểm tra `Lib.current_led_effect` và `Lib.led_effect_active`.

## 10. Hoàn tất processing và conversation mode

Đây là phần dễ gây treo nhất.

```mermaid
flowchart TD
    HANDLER[Handler đang chạy] --> OWN{Owner còn đúng?}
    OWN -->|Không| DROP[Không được clear state của phiên mới]
    OWN -->|Có| RESPONSE[Hoàn tất audio/kết quả]
    RESPONSE --> RELEASE[Lib.finish_processing owner=...]
    RELEASE --> CONV{conversation_mode}
    CONV -->|True| FLAG[conversation_mode_flag=True]
    FLAG --> LISTEN[Thu câu kế tiếp]
    CONV -->|False| LED[LED OFF]
    LED --> IDLE[Chờ wake word]
```

Không dùng cleanup cưỡng bức trừ shutdown/recovery được thiết kế rõ. Một task timeout hoặc bị cancel vẫn phải chạy `finally` để nhả tài nguyên của chính nó.

## 11. Home Assistant, XiaoZhi, Multiroom và Scheduler

- **Home Assistant:** handler phân tích entity/action, gọi API, phát phản hồi hoặc fallback; lỗi mạng không được giữ processing owner.
- **XiaoZhi:** có pipeline và media takeover riêng; yêu cầu MCP được kiểm tra schema trước khi thực thi.
- **Multiroom:** metadata và trạng thái pause/mute được đồng bộ; receiver phải dừng nhanh khi nguồn cục bộ chiếm audio.
- **Scheduler:** lịch/nhắc việc chạy nền; khi phát âm thanh vẫn phải tuân thủ audio ownership và trạng thái runtime.

## 12. Cập nhật chương trình và WebUI

```mermaid
flowchart TD
    UI[Người dùng xác nhận trên WebUI] --> OPT[Đọc keep/exclude/backup/restart/sound]
    OPT --> BACKUP{Backup trước cập nhật?}
    BACKUP -->|Có| LOCAL[Tạo backup local + hậu kiểm archive]
    LOCAL --> CLOUD{Checkbox Google Drive?}
    CLOUD -->|Có| UPLOAD[PHP thử upload + áp dụng quyền chia sẻ]
    UPLOAD -->|Lỗi| WARN[Cảnh báo; giữ backup local]
    UPLOAD -->|Thành công| HANDOFF
    WARN --> HANDOFF[Bàn giao Update Manager]
    BACKUP -->|Không| HANDOFF
    HANDOFF --> SOUND[Phát hết âm báo bắt đầu]
    SOUND --> PY[Manual_Update_Program.py / WebUI.py]
    PY --> DOWNLOAD[Tải/nhận ZIP và kiểm tra]
    DOWNLOAD --> LOCK[Upgrade lock + marker]
    LOCK --> STOP[Dừng service khi cần]
    STOP --> TX[Copy transaction + merge Config]
    TX --> PERM[Áp dụng quyền 0777 theo phạm vi]
    PERM --> HEALTH[Restart/health check]
    HEALTH -->|OK| RESULT[Ghi result, âm báo thành công]
    HEALTH -->|Lỗi| ROLLBACK[Rollback code + Config]
    ROLLBACK --> RESULT2[Ghi result lỗi + phục hồi service]
```

Các nguyên tắc:

- Chạy trực tiếp `Manual_Update_Program.py`/`Manual_Update_WebUI.py` không tải Cloud.
- Upload Google Drive do yêu cầu WebUI kích hoạt; lỗi Cloud là cảnh báo và không hủy cập nhật nếu backup local đã thành công.
- Danh sách giữ lại và loại trừ được truyền rõ; Python không tự suy đoán khi WebUI đã cung cấp danh sách.
- `Config.json` được merge có kiểm soát; dữ liệu người dùng không bị thay bằng template mới.
- Rollback giao dịch luôn được tạo trước khi thay file.
- PHP giữ cơ chế đặt `0777`; updater Python cũng áp dụng quyền theo phạm vi đã quy định.

## 13. Backup Google Drive

```mermaid
flowchart LR
    B[Backup .tar.gz local] --> AUTH[OAuth access token]
    AUTH --> EXP{Hết hạn?}
    EXP -->|Có| REFRESH[Refresh + merge token cũ/mới]
    EXP -->|Không| FOLDER
    REFRESH --> FOLDER[Tìm/tạo thư mục cha và con]
    FOLDER --> FILE[Upload file]
    FILE --> SHARE{sharing_permission}
    SHARE -->|private| PRIVATE[Gỡ quyền anyone]
    SHARE -->|anyone_with_link| PUBLIC[anyone/reader]
    PRIVATE --> PRUNE[Giữ đúng giới hạn backup]
    PUBLIC --> PRUNE
```

Không ghi trực tiếp refresh response thay token cũ vì Google có thể không trả lại `refresh_token`. Luôn merge rồi ghi nguyên tử. Chế độ mặc định là `private`.

## 14. Luồng lỗi và phục hồi

| Điểm lỗi | Phản ứng mong đợi |
|---|---|
| Mic owner không hợp lệ | Dừng phiên hiện tại, không đọc mic. |
| STT timeout/lỗi | Cancel worker/stream, ghi outcome, nhả owner, phát báo lỗi nếu bật. |
| Handler exception | Ghi log có context, chạy fallback hoặc kết thúc an toàn. |
| TTS lỗi | Không phát file dở; backend/fallback khác có thể tiếp tục. |
| Media callback cũ | Bỏ qua nếu generation không còn hợp lệ. |
| Client disconnect | Hủy task phiên, bỏ queue và nhả owner đúng client. |
| Google Drive lỗi | Giữ backup local, cảnh báo; cập nhật WebUI vẫn tiếp tục. |
| Gói cập nhật không hợp lệ | Không thay file hiện tại. |
| Copy/restart/health check lỗi | Rollback code và `Config.json`, phục hồi service. |
| Shutdown | Ngừng nhận việc mới, cancel task, dừng worker/media và giải phóng tài nguyên. |

## 15. Hợp đồng của các file Dev

| File | Điểm vào bắt buộc | Kết quả |
|---|---|---|
| `Dev_Assistant.py` | `async dev_assistant(text_input, used_for=None)` | `(audio, text)` |
| `Dev_Customization.py` | `async dev_skill(input_text)` | `True` nếu đã xử lý, `False/None` nếu bỏ qua |
| `Dev_Led.py` | Các hàm `LED_*` | Không yêu cầu giá trị trả về |
| `Dev_Logs.py` | `logs_dev(logs_text)` | Không gọi ngược logger gây đệ quy |
| `Dev_Music.py` | `async custom_music_async(input_text)` | `(url, title, cover, source)` |
| `Dev_Picovoice.py` | `keyword_paths`, `sensitivities`, `model_file_path` | Biến cấu hình module |
| `Dev_Processing.py` | `async dev_processing(text_input)` | Trạng thái đã xử lý |
| `Dev_STT.py` | `dev_stt`, `dev_stt_streaming` | Transcript hoặc `None` |
| `Dev_TTS.py` | `async dev_tts(text_input)` | Đường dẫn audio/URL hoặc `None` |
| `Dev_Weather.py` | `async custom_weather(text_input, text_input_handle)` | `(audio, text)` |

## 16. Checklist khi thêm một tính năng

1. Xác định nguồn gọi và coroutine/thread đang chạy.
2. Xác định có cần processing, mic hoặc audio ownership không.
3. Đặt timeout cho mạng và subprocess.
4. Không block event loop bằng SDK/hàm I/O đồng bộ.
5. Chuẩn hóa input và kiểm tra schema response.
6. Không log API key, token, mật khẩu hoặc nội dung credentials.
7. Dùng API state/media dùng chung thay vì gán nhiều biến rời rạc.
8. Bảo đảm mọi nhánh return/error/cancel đều cleanup đúng owner.
9. Không phát file tạm hoặc dữ liệu chưa hoàn chỉnh.
10. Thêm kiểm thử success, timeout, cancel, reconnect và exception.

## 17. Cách đọc log để lần theo một yêu cầu

Theo dõi theo thứ tự:

```text
Nguồn input/client
→ owner/generation
→ backend STT + outcome
→ Data_Processing/Dev_Processing handler
→ Assistant/TTS/Media
→ finish_processing(owner)
→ trạng thái LED/conversation mode
```

Với cập nhật:

```text
WebUI request
→ backup local
→ Google Drive (nếu chọn)
→ Update Manager state
→ updater result file
→ âm báo
→ restart/health/rollback
```

Khi điều tra lỗi, luôn ghép timestamp, owner, client ID, generation và backend; không chỉ nhìn dòng exception cuối cùng.

## 18. Dòng thời gian chi tiết của một câu lệnh local

```mermaid
sequenceDiagram
    participant H as Hotword loop
    participant L as Lib state/ownership
    participant S as STT Processing
    participant D as Data Processing
    participant A as Assistant/Handler
    participant T as TTS Processing
    participant M as Media Player

    H->>L: Kiểm tra mic, media policy và processing state
    H->>L: Chuyển mic owner từ hotword sang local_stt
    H->>M: Phát Sound_Start
    H->>S: Select_STT()
    S->>L: Đọc frame theo owner và recording generation
    alt Nhận dạng thành công
        S-->>H: transcript cuối
        H->>D: process_request(text, source=server)
        D->>L: try_start_processing(owner=data_processing)
        D->>A: Router handler/assistant
        alt Handler trả audio
            A-->>D: audio path/URL
        else Handler trả text
            A-->>D: response text
            D->>T: Select_TTS_async(text)
            T-->>D: audio hoàn chỉnh
        end
        D->>M: play_answer_async(audio)
        M-->>D: hoàn tất/cancel/lỗi
        D->>L: finish_processing(owner=data_processing)
        D->>L: đặt conversation flag nếu cần
    else Timeout/cancel/lỗi
        S->>S: Dừng request/iterator/task
        S->>L: Ghi STT outcome
    end
    H->>L: Finalize session và trả mic owner về hotword
```

Điểm kiểm tra khi pipeline bị treo:

- `mic_capture_owner` có trả về `hotword` hay không.
- `processing_owner` có còn thuộc một task đã kết thúc hay không.
- `recording_generation` có bị phiên cũ sử dụng không.
- Cờ phát Answer/Media và VLC callback có kết thúc đúng generation không.
- `conversation_mode_flag` có đang chủ động tạo lượt nghe kế tiếp không.

## 19. Luồng shutdown có kiểm soát

```mermaid
flowchart TD
    SIG[SIGTERM / SIGINT / KeyboardInterrupt] --> GUARD[Chỉ cho phép bắt đầu shutdown một lần]
    GUARD --> STOPNEW[Ngừng nhận phiên và công việc mới]
    STOPNEW --> WATCH[Ngừng watchdog để tránh tự phục hồi dịch vụ]
    WATCH --> INPUT[Ngừng button, encoder, IR, MQTT, API, scheduler, streaming]
    INPUT --> CLIENT[Đóng client/socket/XiaoZhi]
    CLIENT --> AUDIO[Ngừng audio command queue và Media Player]
    AUDIO --> INTEGRATION[Ngừng Multiroom, AirPlay, Bluetooth, HomeKit]
    INTEGRATION --> LED[Ngừng LED và worker còn lại]
    LED --> LOOP[Cancel async task, shutdown async generators và đóng event loop]
    LOOP --> EXIT[Thoát tiến trình]
```

Thứ tự shutdown có chủ đích: watchdog phải dừng trước các dịch vụ mà nó có thể phục hồi; nguồn input dừng trước audio; Media Player chỉ được force-clear mọi owner trong shutdown toàn ứng dụng.

## 20. Các bất biến kiến trúc cần giữ

| Bất biến | Lý do |
|---|---|
| Chỉ một owner xử lý chính tại một thời điểm | Tránh hai câu trả lời và hai thay đổi state cạnh tranh. |
| Chỉ pipeline sở hữu mic được đọc recorder | Tránh native read đồng thời và PCM bị chia sai phiên. |
| Generation cũ không được commit kết quả | Reconnect/cancel không làm sống lại phiên cũ. |
| Callback media phải được serialize | Play/pause/stop không đua nhau trên VLC. |
| File cache chỉ được công bố sau ghi nguyên tử | Không phát MP3/JSON chưa hoàn chỉnh. |
| Refresh token cũ phải được giữ khi refresh | Google thường chỉ trả access token mới. |
| Backup local độc lập với Cloud | Mất mạng không được làm mất khả năng cập nhật/rollback. |
| Updater không ghi code trong process đang phục vụ | Tránh import/module và Config ở trạng thái nửa cũ nửa mới. |
| Mọi I/O ngoài đều có timeout/cancel | Không giữ owner vô thời hạn. |
| Mọi nhánh kết thúc đều cleanup đúng phạm vi | Không để mic, LED, queue hoặc service bị mắc trạng thái. |
