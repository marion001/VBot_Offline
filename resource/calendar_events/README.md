# Lịch ngày quan trọng

VBot đọc `events.json` một lần khi khởi động. Sau khi sửa file, cần restart VBot.

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
