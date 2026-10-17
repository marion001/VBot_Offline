# Lập lịch cho kịch bản nhiều bước

1. Tạo và lưu kịch bản ở trang **Kịch bản nhiều bước**, bật kịch bản đó.
2. Mở **Scheduler.php**, phần **Các tác vụ sẵn có trên hệ thống**, thêm lịch.
3. Trong danh sách tác vụ chọn **Chạy kịch bản: [tên]**, đặt giờ, lịch lặp,
   điều kiện nếu cần, bật lịch và lưu.

Lịch dùng ID kịch bản nên đổi tên vẫn chạy đúng. Mỗi lượt đọc bản kịch bản
đã lưu mới nhất; không cần gọi bằng giọng nói. VBot và Scheduler phải đang chạy,
và tính năng kịch bản phải được bật trong cấu hình VBot.

Các bước, điều kiện, dừng khi lỗi, giới hạn thời gian và hủy chạy dùng chung
bộ thực thi kịch bản hiện có. Không chạy hai kịch bản đồng thời. Kịch bản đang
tắt hoặc đã bị xóa sẽ báo thất bại thay vì chạy thao tác khác.
Lịch sử Scheduler ghi kết quả tác vụ; lịch sử kịch bản ghi từng bước.
