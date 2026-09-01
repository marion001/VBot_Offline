# Tailscale Funnel WebUI

Trang `html/Tailscale_Funnel.php` quản lý Funnel/Serve đang được lưu bởi daemon
Tailscale trên chính thiết bị VBot. Trang không lưu auth key, không chạy
`tailscale down`, không logout và không gỡ gói Tailscale.

Các thao tác chính:

- Bật hoặc áp dụng Funnel nền cho một URL HTTP loopback.
- Chỉ dừng Funnel tại cổng HTTPS đã chọn bằng lệnh `funnel ... off`.
- Xóa toàn bộ cấu hình Funnel và Serve bằng hai lệnh `reset`, có xác nhận bắt buộc.
- Xem phiên bản, trạng thái `tailscaled`, đăng nhập, Funnel, Serve, DNS và IP.

Tailscale Funnel chỉ hỗ trợ cổng HTTPS công khai `443`, `8443` hoặc `10000`.
