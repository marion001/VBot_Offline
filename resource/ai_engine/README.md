# AI Engine (`resource/ai_engine/`)

Thư mục `resource/ai_engine/` lưu trữ toàn bộ dữ liệu trạng thái của chế độ xử lý bằng **AI Function Calling** trong VBot Assistant (bộ đệm lệnh cục bộ, bộ nhớ dài hạn/biệt danh thiết bị, thống kê hiệu năng và nhật ký giọng nói).

---

## 1. Các file dữ liệu trong thư mục

| File | Mục đích | Cơ chế tối ưu thẻ nhớ SD |
| --- | --- | --- |
| `ai_intent_cache.json` | **Bộ Đệm Lệnh AI Cục Bộ (Local Intent Cache):** lưu ánh xạ từ câu nói đã chuẩn hóa sang công cụ (`tool + args`) đã thực thi thành công để tái sử dụng với **0 token (< 50ms)**. | Nạp vào RAM theo `mtime`; chỉ ghi cập nhật bộ đếm `hits` sau mỗi **10 lượt khớp**. Tự chặn lưu các câu nói tắt phụ thuộc ngữ cảnh (*"tắt đi cho tôi"*...) và tự bỏ qua cache nếu người dùng lặp lại y hệt câu lệnh trong 45 giây. |
| `ai_memory.json` | **Bộ nhớ dài hạn & Biệt danh thiết bị (`device_aliases`):** lưu thông tin cá nhân người dùng nhờ nhớ (*"nhớ giúp tôi..."*) và ánh xạ tên gọi tắt sang tên thiết bị Home Assistant thật (*"gọi đèn bàn là đèn học"*). | Cache RAM theo `mtime`; chỉ ghi khi người dùng thêm/sửa/xóa. |
| `ai_telemetry.json` | **Thống kê hiệu năng AI:** lưu số lượt gọi AI, số lượt khớp Cache, token sử dụng (theo ngày và tổng tích lũy), thời gian phản hồi trung bình để hiển thị trên `html/AI_Stats.php`. | Đếm hoàn toàn trong RAM; gom ghi nền theo chu kỳ (mỗi **5 lượt** hoặc **30 giây**) dưới khóa chống xung đột luồng. |
| `voice_journal.json` | **Nhật ký giọng nói (`voice_journal`):** lưu ghi chú nhanh bằng giọng nói kèm thời gian để xem lại bằng giọng nói hoặc trên `html/AI_Stats.php`. | Chỉ ghi khi có ghi chú mới hoặc khi xóa. |
| `knowledge/` + `knowledge_index.sqlite3` + `knowledge_catalog.json` | **Kho Tri Thức AI Cục Bộ — VBot Knowledge v2 (Catalog + Hybrid FTS5 & Vector RRF):** hỗ trợ **27 định dạng** (tài liệu Office/PDF/PDF Scan/EPUB/RTF/HTML/CSV/JSON/XML/YAML, mã nguồn, ảnh `JPG/PNG/WEBP` qua Gemini Vision), nạp & cập nhật từ **URL Website**, và lưu ghi chú bằng **giọng nói**. Công cụ đa năng `search_knowledge` hỗ trợ 5 thao tác: `search`, `list`, `read`, `stats`, `add` + lọc `category`/`file_type`/`source_file`. | Lập chỉ mục tăng dần theo `SHA-256` (cảnh báo trùng nội dung & khử trùng đoạn trích); cắt đoạn theo đề mục (**Heading-Aware**) + ghép đoạn liền kề (**Neighbor Window**); lưu FTS5 BM25 + Vector 256-dim `float32` (`kb_vectors`) ngay trong SQLite (0 Qdrant, 0 RAM nền); tự chuyển sang `web_search` khi tài liệu không có thông tin; sao lưu/khôi phục `.tar.gz` (tối đa 5 bản) và đồng bộ quyền `0777`. |

> **Lưu ý:** Tất cả các file trên đều là dữ liệu người dùng, có thể xóa nội dung về `{}` / `[]` (hoặc bấm nút xóa ngay trên WebUI `Config.php` / `AI_Stats.php`) mà không ảnh hưởng đến chương trình.

---

## 2. Cấu hình trong `Config.json`

Chế độ xử lý AI được cấu hình tại mục `processing_pipeline` (có thể chỉnh trực tiếp trên **WebUI → `Config.php`**):

```json
"processing_pipeline": {
  "mode": "ai",
  "ai_provider": "groq_ai",
  "fallback_to_vbot_on_error": true,
  "web_search_active": true,
  "ai_memory_active": true,
  "ai_knowledge_active": true,
  "ai_knowledge_vector_active": true,
  "intent_cache_active": true,
  "face_display_sse_active": true
}
```

- `mode`: `"ai"` (dùng AI Function Calling) hoặc `"vbot"` (chế độ từ khóa truyền thống).
- `ai_provider`:
  - `"groq_ai"`: Groq AI (tốc độ cao, hỗ trợ nhiều API Key xoay vòng tự động & `BackList.json`).
  - `"google_gemini"`: Google Gemini (tắt thinking budget trên Gemini 2.5 để giảm độ trễ).
  - `"dev_ai_engine"`: Chạy mẫu tùy biến trong `Dev_AI_Engine.py` (giữ dạng `.py` thuần để người dùng tự chỉnh sang OpenAI, OpenRouter, DeepSeek, Ollama LAN...).
- `fallback_to_vbot_on_error`: `true` sẽ tự động chuyển về bộ xử lý VBot nội bộ nếu AI gặp lỗi mạng, timeout hoặc hết hạn mức.
- `ai_intent_cache_active`: bật/tắt Bộ Đệm Lệnh AI Cục Bộ (0 token).
- `face_display_sse_active`: bật/tắt luồng SSE `/ai-face/events` phục vụ màn hình biểu cảm OLED `html/Face.php` (mặc định `false` để tiết kiệm tài nguyên khi không dùng màn hình mặt).

---

## 3. Thứ tự ưu tiên xử lý khi bật chế độ AI

Khi có câu nói đầu vào, VBot đi theo đúng lộ trình ưu tiên tốc độ & tiết kiệm token:

1. **Custom Home Assistant Command (0 token):** khớp trực tiếp lệnh tùy chỉnh trong `resource/hass/Home_Assistant_Custom.json`.
2. **Câu lệnh hệ thống SYSTEM (0 token):** khớp lệnh hệ thống trong `resource/SYS_CMD.json`.
3. **Local Intent Cache (0 token, < 50ms):** tra cứu trong `ai_intent_cache.json` (bỏ qua nếu là câu nói tắt không nêu tên thiết bị hoặc đang trong lượt hỏi làm rõ).
4. **AI Function Calling (`AI_Engine.py` → `AI_Action_Dispatcher.py`):**
   - Đính kèm vị trí phòng loa (`speaker_location`), tối đa 30 thiết bị Home Assistant chính từ `resource/hass/Home_Assistant.json` và ngữ cảnh thiết bị vừa thao tác trong 60 giây.
   - Với các lệnh điều khiển HASS (`on` / `off` / `toggle` / `setup`), tra cứu `entity_id` trực tiếp từ RAM cache `Home_Assistant.json` (< 5ms) thay vì tải lại toàn bộ thực thể qua mạng; tự fallback tải `/api/states` nếu chưa khớp.
   - Nếu AI hỏi làm rõ (câu trả lời kết thúc bằng `?`), VBot tự mở mic im lặng 1 lượt để bạn trả lời tiếp mà không cần gọi lại từ khóa đánh thức.
5. **Fallback VBot nội bộ:** tự động kích hoạt nếu AI không phản hồi và `fallback_to_vbot_on_error = true`.

---

## 4. Các nhóm kỹ năng (Tools) chính & ví dụ câu nói

- **Điều khiển Home Assistant (`control_hass`):**
  - *"Bật đèn ngủ"*, *"Tắt đi cho tôi"* (tự hiểu thiết bị vừa thao tác), *"Bật điều hòa 26 độ"*, *"Hẹn 10 phút nữa tắt quạt"*.
  - Điều khiển hàng loạt theo phòng (`batch_on` / `batch_off`): *"Tắt hết đèn phòng khách"* (tự lọc nhiễu camera & bảo vệ các thiết bị quan trọng như router, wifi, tủ lạnh, server).
  - Kiểm tra thiết bị đang bật (`check_active`): *"Có thiết bị nào đang bật không?"*.
- **Biệt danh thiết bị & Bộ nhớ (`ai_memory`):**
  - *"Gọi đèn bàn là đèn học"*, *"Nhớ giúp tôi mã wifi là..."*.
- **Nhạc & Media (`play_media`, `media_control`):**
  - *"Mở nhạc Sơn Tùng"*, *"Tạm dừng nhạc"*, *"Hẹn 30 phút tắt nhạc"* (giảm âm lượng mượt 10 giây cuối), *"Hết bài này thì dừng"*.
- **Bản tin sáng / Chúc ngủ ngon (`daily_briefing`):**
  - *"Bản tin buổi sáng"* / *"Chúc ngủ ngon"* (tổng hợp giờ, thời tiết, lịch nhắc, tin tức và trạng thái nhà; có thể bật tự động đọc mỗi sáng trong `html/Scheduler.php`).
- **Đếm ngược, Thông báo & Nhật ký (`countdown_timer`, `announce`, `voice_journal`):**
  - *"Đếm ngược 5 phút nấu mì"*, *"Đếm ngược 15 phút tắt bếp"* (chạy song song nhiều bộ đếm).
  - *"Thông báo cả nhà xuống ăn cơm"*.
  - *"Ghi chú giúp tôi chiều nay đi mua đồ"*, *"Đọc lại nhật ký hôm nay"*.
- **Thời tiết, Lịch, Tìm kiếm Web & Hệ thống:**
  - *"Thời tiết Hà Nội ngày mai"*, *"Hôm nay mùng mấy âm lịch?"*, *"Giá vàng hôm nay bao nhiêu?"*, *"Tăng âm lượng lên 70%"*.

---

## 5. Các trang WebUI liên quan

- **`Config.php`:** Bật/tắt chế độ AI, chọn provider (`Groq` / `Gemini` / `Dev_AI_Engine`), nhập nhiều API Key, bật/tắt Kho Tri Thức AI & Tìm kiếm ngữ nghĩa Hybrid Vector, Local Intent Cache và SSE màn hình cảm xúc.
- **`AI_Knowledge.php`:** Quản lý Danh mục tài liệu (Catalog), tải lên 27 định dạng & nạp/cập nhật URL Website, lập chỉ mục từng tệp, xem trực tiếp bảng `kb_chunks` & `kb_vectors`, Search Playground kiểm thử truy vấn, AI tự phân loại & gắn Tags, và Sao lưu/Khôi phục `.tar.gz` tại `html/Backup_Upgrade/Backup_AI_Knowledge` (tối đa 5 bản).
- **`AI_Stats.php`:** Xem thống kê lượt gọi AI / Cache / Token (Hôm nay / Tổng), kiểm tra hạn mức từng API Key Groq trực tiếp, xem/xóa Nhật ký giọng nói.
- **`Command_Test.php`:** Kiểm thử câu lệnh cho **cả 2 chế độ (AI & VBot nội bộ)** — xem trước câu có khớp Local Intent Cache hay Tool/Câu trả lời AI dự kiến mà không cần thực thi thật.
- **`Face.php`:** Màn hình biểu cảm OLED với 21 cảm xúc chuẩn XiaoZhi, mắt chuyển động tự nhiên, miệng nói theo TTS và sóng nhạc khi phát media.
- **`index.php`:** Có nút **Đồng bộ `Home_Assistant.json`** nhanh để cập nhật danh sách thiết bị mới cho AI mà không cần khởi động lại VBot.

---

## 6. Kiểm thử & xử lý sự cố nhanh

- **Chạy test hồi quy AI & module lõi:**
  ```bash
  python3 -m unittest tests/test_core_regression.py
  ```
- **AI không nhận thiết bị HASS mới thêm/đổi tên:** Bấm nút **Đồng bộ `Home_Assistant.json`** trên `index.php` (hoặc nút `Đồng Bộ Home_Assistant.json` trong Home Assistant Custom Component) — AI sẽ tự nạp lại file mới ngay lượt tiếp theo.
- **Lệnh lặp lại bị khớp sai từ cache cũ:** Nhắc lại y hệt câu lệnh đó trong vòng **45 giây** để VBot tự bỏ qua cache và gọi AI phân tích lại, hoặc xóa cache trong `Config.php` / xóa `ai_intent_cache.json`.
