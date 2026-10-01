# Hướng dẫn ngôn ngữ câu lệnh VBot

Thư mục này chứa từ khóa nhận dạng câu lệnh và nội dung phản hồi theo từng ngôn
ngữ của VBot. Mỗi ngôn ngữ sử dụng **một file JSON duy nhất**, đặt trực tiếp trong
`resource/lang_keywords`.

Tài liệu chung của dự án: [VBot Offline README](https://github.com/marion001/VBot_Offline/blob/main/README.md).

## 1. Các file có sẵn

| File | Mục đích |
| --- | --- |
| `vi-VN.json` | Ngôn ngữ mặc định và dữ liệu dự phòng của VBot. |
| `en-US.json` | Bản tiếng Anh đầy đủ, không kế thừa tiếng Việt. |
| `en-US.example.json` | Ví dụ rút gọn; WebUI không đưa file `.example.json` vào danh sách lựa chọn. |

Không sửa hoặc xóa `vi-VN.json` nếu chưa có bản sao lưu hợp lệ. VBot luôn cần file
này để khởi động và làm dữ liệu dự phòng.

## 2. Cách tạo một ngôn ngữ mới

Ví dụ tạo tiếng Pháp:

1. Sao chép `en-US.example.json` thành `fr-FR.json`.
2. Đổi `locale` thành `fr-FR` và `name` thành tên hiển thị mong muốn.
3. Dịch từ khóa, cấu hình thời tiết và các câu trong `responses`.
4. Mở **Config → Ngôn Ngữ Câu Lệnh**, sau đó tải lại trang.
5. Chọn `Français — fr-FR.json`, chọn STT và TTS tiếng Pháp tương ứng.
6. Lưu cấu hình và khởi động lại VBot.

Tên file và giá trị `locale` phải trùng nhau. Nên viết theo định dạng chuẩn:

```text
vi-VN
en-US
fr-FR
de-DE
ja-JP
```

Chỉ chấp nhận mã ngôn ngữ có dạng `xx`, `xx-YY` hoặc tương đương. Không dùng dấu
cách, dấu `/`, `\` hoặc đường dẫn trong tên locale.

## 3. Mẫu file tối thiểu

```json
{
    "schema_version": 1,
    "locale": "fr-FR",
    "name": "Français",
    "inherit_fallback": true,
    "actions": {
        "on": ["allumer", "activer"]
    },
    "objects": {
        "what_time": ["quelle heure", "heure actuelle"]
    },
    "adverbs": {
        "on_speaker": ["sur l'enceinte"]
    },
    "responses": {
        "time": {
            "current": "Il est {hour_24} heures {minute}"
        }
    },
    "values": {
        "multi_command_joiner": "et",
        "language_instruction": "français"
    }
}
```

Mẫu tối thiểu nên để `"inherit_fallback": true`. Các nhóm hoặc khóa chưa khai báo
sẽ được lấy từ `vi-VN.json`, giúp VBot vẫn hoạt động trong khi bản dịch đang được
hoàn thiện.

## 4. Kế thừa dữ liệu tiếng Việt

### Bản dịch đang hoàn thiện

```json
"inherit_fallback": true
```

- Các nhóm và khóa còn thiếu được kế thừa từ `vi-VN.json`.
- WebUI vẫn cho phép lựa chọn và hiển thị trạng thái, ví dụ:
  `Thiếu 5 khóa — kế thừa vi-VN`.
- Một số câu hoặc keyword chưa dịch có thể vẫn xuất hiện bằng tiếng Việt.

### Bản dịch đầy đủ

```json
"inherit_fallback": false
```

- Không trộn keyword hoặc phản hồi tiếng Việt vào ngôn ngữ đang chọn.
- File phải có đầy đủ cấu trúc và khóa tương ứng với `vi-VN.json`.
- WebUI sẽ không cho lựa chọn nếu file còn thiếu khóa.

Nên chỉ chuyển sang `false` sau khi WebUI báo file **Hợp lệ**.

## 5. Cấu trúc dữ liệu

### `schema_version`

Phiên bản cấu trúc file, hiện dùng số nguyên `1`:

```json
"schema_version": 1
```

VBot hiện chỉ hỗ trợ phiên bản `1`. File khai báo phiên bản khác sẽ không được nạp,
tránh trường hợp phiên bản cấu trúc mới bị backend cũ hiểu sai.

### `locale` và `name`

```json
"locale": "en-US",
"name": "English (United States)"
```

- `locale`: phải trùng với tên file.
- `name`: tên ngôn ngữ hiển thị trên WebUI.

### `actions`

Các từ hoặc cụm từ thể hiện hành động:

```json
"actions": {
    "on": ["turn on", "switch on", "enable"],
    "off": ["turn off", "switch off", "disable"],
    "check": ["check", "show", "tell me"]
}
```

Tên nhóm như `on`, `off`, `check` là khóa nội bộ được code sử dụng. Không tự đổi
tên khóa; chỉ dịch hoặc bổ sung các cụm từ trong mảng.

### `objects`

Các đối tượng hoặc loại yêu cầu:

```json
"objects": {
    "what_time": ["what time", "current time"],
    "weather": ["weather", "forecast"],
    "radio": ["radio", "station"]
}
```

### `adverbs`

Các cụm từ bổ nghĩa cho lệnh, ví dụ vị trí thiết bị, phần trăm hoặc cách phát:

```json
"adverbs": {
    "on_speaker": ["on speaker", "on device"],
    "percent": ["percent", "%"],
    "playlist": ["playlist"]
}
```

### `weather`

Cấu hình cách nhận dạng thời tiết và địa điểm bằng các cụm từ dễ đọc:

```json
"weather": {
    "intent": {
        "direct_phrases": ["weather", "forecast"],
        "temperature_phrases": ["temperature"],
        "temperature_excludes": ["cpu"]
    },
    "location_cleanup": {
        "remove_phrases": ["weather", "forecast", "please"],
        "prefixes": ["in", "at"],
        "conjunctions": ["and", "then"],
        "polite_suffixes": ["please", "thanks"]
    },
    "administrative_prefixes": {
        "all": ["city", "province", "district"],
        "district": ["district"],
        "province": ["city", "province"]
    },
    "days": [
        {
            "offset": 0,
            "label": "today",
            "phrases": ["today"],
            "standalone_phrases": []
        },
        {
            "offset": 1,
            "label": "tomorrow",
            "phrases": ["tomorrow"],
            "standalone_phrases": []
        }
    ],
    "default_day": {
        "offset": 0,
        "label": "today"
    },
    "geocoding_language": "en",
    "country_code": "US",
    "location_connector": "in"
}
```

Quy tắc quan trọng:

- Chỉ nhập từ hoặc cụm từ bình thường.
- Không viết regex như `\b`, `(?:...)`, `.*` hoặc lookahead.
- `phrases` dùng cho cụm từ nhận dạng thông thường.
- `standalone_phrases` dùng cho từ ngắn chỉ nên nhận dạng ở vị trí độc lập.
- `offset`: `-1` là hôm qua, `0` là hôm nay, `1` là ngày mai.
- `temperature_excludes` giúp phân biệt nhiệt độ thời tiết với nhiệt độ CPU.

### `responses`

Chứa nội dung VBot trả về cho người dùng, TTS hoặc API. Các nhóm hiện có gồm:

```text
general, api, updates, scheduler, logs, button, mcp, api_media, time, weather, system,
microphone, remote_control, volume, setup, home_assistant,
device_status, media, reminder, calendar
```

Ví dụ:

```json
"responses": {
    "system": {
        "cpu_temperature": "The current CPU temperature is {temperature} degrees Celsius",
        "uptime": "The device has been running for {days} days, {hours} hours, {minutes} minutes, and {seconds} seconds"
    },
    "media": {
        "paused": "Paused playback",
        "stopped": "Stopped playback"
    }
}
```

`responses.weather.codes` sử dụng mã thời tiết Open-Meteo làm khóa. Các khóa số
như `"0"`, `"45"`, `"61"` là hợp lệ và không được đổi thành mô tả chữ.

#### Chuyển đổi log theo từng bước

Các log kỹ thuật cũ không bắt buộc phải chuyển đổi đồng loạt. Log đã được quy
hoạch dùng `Lib.localized_log()` và đặt bản dịch trong `responses.logs`; log chưa
quy hoạch tiếp tục dùng nguyên văn tiếng Việt nên không ảnh hưởng khả năng debug.

Ví dụ:

```json
"logs": {
    "scheduler_cleanup": "[Scheduler] Cleaned notification data after completing task: '{name}'"
}
```

Mỗi khóa log mới phải có trong cả `vi-VN.json` và các file ngôn ngữ đầy đủ như
`en-US.json`. Khi thiếu khóa hoặc template sai placeholder, VBot dùng câu fallback
trong code và không làm dừng chương trình.

### `values`

Các giá trị ngôn ngữ có cấu trúc được nhiều chức năng dùng chung:

```json
"values": {
    "multi_command_joiner": "and",
    "inactive_states": ["off", "closed", "idle"],
    "reminder_time_terms": ["today", "tomorrow", "tonight"],
    "calendar_events": {
        "countdown_terms": ["how long", "how many days"],
        "month_terms": ["this month"],
        "day_number_prefixes": ["day"],
        "month_number_prefixes": ["month"],
        "year_number_prefixes": ["year"],
        "date_order": "mdy",
        "allow_bare_year": true,
        "explicit_date_label": "{month}/{day}/{year}",
        "week_queries": [
            {"week_offset": 0, "label": "this week", "terms": ["this week"]},
            {"week_offset": 1, "label": "next week", "terms": ["next week"]}
        ],
        "range_start_terms": ["between", "from"],
        "range_end_terms": ["and", "to"],
        "next_days_prefixes": ["in the next"],
        "next_days_suffixes": ["days"],
        "range_label": "from {start} to {end}",
        "next_days_label": "in the next {days} days",
        "month_names": [
            {"month": 1, "terms": ["January"]},
            {"month": 10, "terms": ["October"]}
        ],
        "upcoming_terms": ["upcoming", "next event", "read events"],
        "month_label": "this month",
        "month_number_label": "month {month}, {year}",
        "upcoming_label": "upcoming",
        "date_format": "%m/%d/%Y"
    },
    "language_instruction": "English"
}
```

- `multi_command_joiner`: từ nối khi ghép nhiều câu lệnh.
- `inactive_states`: trạng thái được hiểu là không hoạt động.
- `reminder_time_terms`: từ thời gian dùng khi phân tích lời nhắc.
- `calendar_events`: cụm nhận diện truy vấn Events, nhãn phạm vi và định dạng ngày trả về. Các khóa `day_number_prefixes`, `month_number_prefixes` và `year_number_prefixes` lần lượt chứa từ đứng trước số ngày, tháng và năm. `month_names` ánh xạ tháng `1–12` tới các cách gọi. `date_order` nhận `dmy` hoặc `mdy`; `allow_bare_year` cho phép năm không có tiền tố. `week_queries` khai báo tuần hiện tại (`week_offset: 0`) hoặc tuần sau (`1`). Các cặp `range_start_terms`/`range_end_terms` và `next_days_prefixes`/`next_days_suffixes` điều khiển truy vấn khoảng ngày. Mỗi khoảng bị giới hạn tối đa 366 ngày. Các khóa `explicit_date_label`, `range_label`, `next_days_label` định dạng nhãn trả lời. Chỉ nhập cụm từ thông thường, không dùng regex.
- `language_instruction`: tên ngôn ngữ dùng trong chỉ dẫn cho trợ lý ảo.

## 6. Placeholder trong câu phản hồi

Placeholder là tên biến nằm trong dấu ngoặc nhọn:

```json
"cpu_temperature": "CPU temperature: {temperature}°C"
```

Phải giữ đúng tên placeholder của khóa gốc trong `vi-VN.json`. Có thể đổi vị trí
cho phù hợp ngữ pháp, nhưng không được tự dịch tên biến:

```json
"success": "Successfully {action} {name}"
```

Không được đổi `{action}` và `{name}` thành tên biến đã dịch như `{hanh_dong}` hoặc
`{ten}`.

Một số placeholder thường dùng:

| Chức năng | Placeholder |
| --- | --- |
| Thời gian | `{hour_24}`, `{hour_12}`, `{minute}`, `{period}` |
| Thời tiết | `{day_label}`, `{location_name}`, `{condition}`, `{temperature_min}`, `{temperature_max}`, `{humidity_max}`, `{precipitation_max}` |
| Hệ thống | `{temperature}`, `{days}`, `{hours}`, `{minutes}`, `{seconds}` |
| Thiết bị | `{name}`, `{state}`, `{action}`, `{value}`, `{unit}`, `{device}` |
| Lỗi | `{error}` |

Riêng `responses.time.current` có thể dùng định dạng 24 giờ:

```json
"current": "Bây giờ là {hour_24} giờ {minute} phút"
```

hoặc định dạng 12 giờ:

```json
"current": "It is {hour_12}:{minute} {period}"
```

WebUI kiểm tra placeholder trước khi đưa file vào danh sách lựa chọn. Nếu thiếu,
thừa hoặc viết sai tên placeholder, file sẽ được báo không hợp lệ.

Backend Python lặp lại cùng phép kiểm tra khi VBot khởi động. Vì vậy, việc sửa trực
tiếp `Config.json` hoặc bỏ qua WebUI không làm một file sai cấu trúc được nạp vào
chương trình.

## 7. Kiểm tra trên WebUI

Khi tải trang Config, VBot tự quét `resource/lang_keywords/*.json` và hiển thị:

- `[Hợp lệ]`: cấu trúc đầy đủ, kiểu dữ liệu và placeholder đúng.
- `[Thiếu N khóa — kế thừa vi-VN]`: có thể sử dụng vì file đang bật kế thừa.
- Cảnh báo keyword trùng được hiển thị riêng bên dưới danh sách khi một cụm từ nằm
  trong nhiều category của cùng `actions` hoặc cùng `objects`. Việc dùng lại từ
  giữa các section hoặc trong `adverbs` là hợp lệ và không bị cảnh báo.
- `Bỏ qua file không hợp lệ`: file không được đưa vào danh sách, kèm nguyên nhân
  như sai JSON, sai kiểu dữ liệu, sai placeholder hoặc thiếu khóa khi đã tắt kế thừa.

WebUI bỏ qua mọi file kết thúc bằng `.example.json`.

Sau khi tạo hoặc chỉnh sửa file:

1. Tải lại trang Config để quét lại.
2. Kiểm tra trạng thái cạnh tên file.
3. Chọn ngôn ngữ câu lệnh.
4. Chọn STT và TTS cùng ngôn ngữ.
5. Lưu cấu hình.
6. Khởi động lại VBot.

## 8. STT, TTS và log

Ba thành phần cần được phân biệt:

- **Ngôn ngữ câu lệnh**: lấy từ file trong thư mục này.
- **STT**: ngôn ngữ nhận dạng giọng nói, phải chọn tương ứng trong Config.
- **TTS**: giọng đọc phản hồi, cũng phải chọn ngôn ngữ tương ứng.

Nếu chỉ đổi file keyword nhưng STT vẫn là tiếng Việt, câu lệnh tiếng Anh có thể
được nhận dạng sai. Nếu TTS không tương ứng, nội dung đúng nhưng giọng đọc có thể
không tự nhiên.

Log kỹ thuật như `[System]`, `[Bot API]`, MQTT và thông báo lỗi hiện vẫn giữ tiếng
Việt để thuận tiện debug. Nội dung `[BOT]`, TTS và phản hồi dành cho người dùng sẽ
dùng ngôn ngữ đang chọn.

## 9. Hiệu năng và thời điểm áp dụng

- File ngôn ngữ chỉ được đọc và chuẩn hóa **một lần khi VBot khởi động**.
- Dữ liệu được giữ trong RAM, không đọc lại JSON cho mỗi yêu cầu.
- Việc thêm ngôn ngữ không làm phát sinh quá trình quét file liên tục.
- Sau khi sửa JSON, bắt buộc khởi động lại VBot để áp dụng.
- Tải lại trang Config chỉ quét danh sách cho WebUI, không thay đổi tiến trình VBot
  đang chạy.

## 10. Xử lý lỗi thường gặp

### File không xuất hiện trong danh sách

Kiểm tra:

- Tên file có đúng dạng `xx-YY.json` hay không.
- `locale` bên trong có trùng tên file hay không.
- File có phải JSON hợp lệ và mã hóa UTF-8 hay không.
- File có vượt giới hạn kích thước 512 KB hay không.
- Tên file có kết thúc bằng `.example.json` hay không.

### Báo sai placeholder

Mở cùng khóa trong `vi-VN.json`, sau đó giữ nguyên toàn bộ tên biến `{...}`. Chỉ
dịch phần văn bản bên ngoài placeholder.

### Báo thiếu khóa nhưng đã tắt kế thừa

Chọn một trong hai cách:

1. Bổ sung các khóa còn thiếu theo `vi-VN.json`; hoặc
2. Tạm đặt `"inherit_fallback": true` trong thời gian hoàn thiện bản dịch.

### Đã chọn ngôn ngữ nhưng VBot vẫn dùng tiếng Việt

Kiểm tra:

- Đã nhấn lưu Config hay chưa.
- Đã khởi động lại VBot hay chưa.
- Log khởi động có báo đã nạp đúng file ngôn ngữ hay không.
- File có bị lỗi và khiến VBot tự quay về `vi-VN` hay không.
- `language.primary` trong `Config.json` có đúng locale hay không.

Ví dụ log hợp lệ:

```text
[Keyword] Đã nạp file ngôn ngữ en-US.json
[Keyword] Ngôn ngữ câu lệnh đang hoạt động: en-US (resource/lang_keywords/en-US.json)
```

## 11. Nguyên tắc khi cập nhật

- Không đổi tên khóa nội bộ nếu chưa sửa code sử dụng khóa đó.
- Không thêm regex vào danh sách từ khóa thời tiết.
- Không dịch placeholder.
- Không dùng cùng một cụm từ cho quá nhiều hành động khác nhau.
- Ưu tiên cụm từ tự nhiên mà STT của ngôn ngữ đó có thể nhận dạng ổn định.
- Sau mỗi thay đổi, kiểm tra ít nhất: hỏi giờ, thời tiết, CPU, uptime, âm lượng,
  media và một thiết bị Home Assistant.
- Giữ một bản sao lưu trước khi chỉnh sửa file ngôn ngữ đang dùng.
