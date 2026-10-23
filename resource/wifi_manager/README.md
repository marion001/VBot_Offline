# Quản lý Wi-Fi

`html/Wifi.php` dùng UUID hồ sơ NetworkManager để đọc IP/DNS và lưu cấu hình. Các thao tác quản trị sử dụng tài khoản SSH trong VBot; không cấp thêm sudo cho Apache.

- Danh sách đã lưu cho phép bật/tắt tự kết nối và đặt ưu tiên -999…999; số lớn hơn được ưu tiên khi tự chọn mạng. Lưu tùy chọn không đổi ngay mạng đang dùng.
- Kết nối mạng mới dùng hộp nhập mật khẩu được che. Mạng ẩn được nhập SSID riêng, hỗ trợ mạng mở hoặc WPA Personal; SSID giữ nguyên dấu cách và giới hạn 32 byte.
- Nút kiểm tra Internet chỉ thử qua Wi-Fi (gateway, DNS và HTTPS). Không dùng Internet qua PAN để kết luận Wi-Fi có mạng.
- Đặt lại Wi-Fi chỉ xóa hồ sơ Wi-Fi theo UUID. Trước khi xóa có sao lưu riêng tư trong `/home/pi/VBot_AEC3/wifi_backups/`; giữ PAN/Ethernet/VPN. File sao lưu có thể chứa mật khẩu, không chia sẻ công khai.

## Đổi IP/DNS không reboot

1. Tải thông tin Wi-Fi đang kết nối để có UUID chính xác.
2. Nhập IP/Gateway/DNS rồi lưu bản nháp. Chưa sửa hồ sơ NetworkManager, nên reboot trước khi áp dụng vẫn giữ mạng cũ. IP có thể kèm prefix (ví dụ `192.168.14.175/24`); nếu bỏ prefix dùng /24. Gateway phải cùng subnet. DHCP nhận IP từ router; DNS mặc định Google (8.8.8.8/8.8.4.4, kèm DNS Google IPv6), hoặc chọn DNS do router cấp. Việc đổi lựa chọn chỉ áp dụng khi lưu DHCP rồi áp dụng kết nối; không tự đổi mạng đang dùng.
3. Nhấn **Áp dụng kết nối đã lưu**. Thao tác được lên lịch sau 3 giây để WebUI trả phản hồi trước khi đường mạng thay đổi.
4. Mở lại trang `Wifi.php` tại IP mới (hoặc tìm IP DHCP trên router), nhấn **Kiểm tra áp dụng** rồi **Xác nhận kết nối hoạt động** trong 120 giây kể từ lúc áp dụng.
5. Nếu không xác nhận hoặc áp dụng lỗi, hệ thống khôi phục các thuộc tính IPv4 trước khi lưu và áp dụng lại kết nối. Bản trạng thái nằm trong `/var/lib/vbot-network/`, chỉ root truy cập. Script Wi-Fi V3 cũng xử lý phiên chưa xác nhận bị gián đoạn bởi reboot.

Trong thời gian thử cấu hình, script Wi-Fi không chuyển sang AP. Không xóa hồ sơ Wi-Fi hoặc chỉnh ngoài WebUI trong khi phiên áp dụng chưa kết thúc. Máy cần NetworkManager, systemd, PHP SSH2 và các file Python trong `resource/wifi_manager`/`resource/bluetooth` đi kèm bản cập nhật. Không cần build thêm module .so ở root.

Đổi IP thật, rollback sau mất kết nối, tạo AP và kết nối mạng ẩn cần thử trên thiết bị có đường truy cập dự phòng trước khi phát hành. Không dùng thao tác xóa/đổi IP thật chỉ để kiểm tra cú pháp.
