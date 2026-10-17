# Lịch ngày quan trọng

VBot đọc `events.json` một lần khi khởi động. Sau khi sửa file, cần restart VBot.

Trong `Calendar_Events.php`, mục **Hành động VBot thực hiện khi đến lịch** có lựa chọn **Phát một bài nhạc Local cụ thể**. Chọn mục này để đọc danh sách bài từ `media_player.music_local.path`, gồm cả thư mục con và các định dạng đã cấu hình. Có thể tìm theo tên tiếng Việt không dấu, chọn bài và tải lại danh sách khi thêm/xóa file.

Lựa chọn được lưu trong `execution` với `action: "media_local"` và `media_path` là đường dẫn chính xác. Sửa sự kiện, xuất/nhập JSON và sao lưu giữ lại bài đã chọn. Khi đến lịch hoặc chạy thủ công, VBot kiểm tra file thuộc thư mục nhạc, định dạng được phép và kết quả khởi động trình phát; lỗi được ghi vào lịch sử sự kiện. Tính năng này hoạt động độc lập với cờ `voice_routines.active`.

- `event_calendar`: loại lịch của ngày, tháng, năm gốc của sự kiện: `solar` hoặc `lunar`.
- `calendar`: loại lịch dùng để chạy sự kiện theo `months` và `days`: `solar` hoặc `lunar`.
- `year`: bắt buộc với sự kiện một lần; sự kiện hằng năm có thể lưu năm tham chiếu hoặc đặt `null`.
- `event_month`, `event_day`: tháng/ngày gốc mang tính thông tin của sự kiện; có thể đặt `null` và không thay đổi lịch chạy.
- `months`, `days`: danh sách các tháng và ngày. VBot tạo các tổ hợp hợp lệ giữa hai danh sách.
- `recurrence`: `once` hoặc `yearly`.
- `leap_month`: với âm lịch, `false` là tháng thường, `true` là tháng nhuận, `null` chấp nhận cả hai.
- `before_days`: các mốc báo trước; `0` là đúng ngày.
- `times`: danh sách giờ kích hoạt dạng 24 giờ `HH:MM`.
- `notification.active`: bật hoặc tắt việc đọc thông báo qua loa.
- `message`: câu tùy chỉnh; để trống để VBot tạo câu theo ngôn ngữ đang dùng.
- `tags`: danh sách tag; trên WebUI nhập mỗi tag trên một dòng.
- `execution.active`: WebUI tự đặt `true` khi chọn hành động và `false` khi chọn `none`.
- `execution.action`: ID hành động trong `resource/action_registry.json`.

Giới hạn 512 sự kiện. `id` phải duy nhất.

Các Event cũ chưa có `event_calendar` sẽ tự dùng giá trị của `calendar` để tương thích. Các khóa cũ `month`, `day`, `time` vẫn được đọc. Khi lưu bằng WebUI, Event được chuẩn hóa sang `months`, `days`, `times`. Ví dụ `months: [1, 6]`, `days: [1, 15]`, `times: ["08:00", "20:30"]` sẽ chạy ngày 1 và 15 của tháng 1 và tháng 6, tại cả hai giờ đã chọn.
# Ngưỡng khớp tên sự kiện

`calendar.events.minimum_threshold` trong Config.json mặc định `0.90`, có thể chỉnh tại Config.php. Ngưỡng áp dụng khi tìm tên/Tags trong câu hỏi giọng nói: `1.00` yêu cầu khớp chính xác sau chuẩn hóa, giá trị thấp hơn cho phép gần khớp. Khởi động lại VBot sau khi đổi cấu hình. Ngưỡng không thay đổi thời điểm lịch tự chạy.

