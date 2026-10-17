# Bật máy tính bằng Wake-on-LAN

Mở **Wake-on-LAN — Bật máy tính** trong menu WebUI, chọn tab **Cấu hình máy tính**.
Thêm tên, MAC card Ethernet, broadcast (thường `255.255.255.255` hoặc broadcast
của mạng LAN) và cổng UDP (mặc định 9), rồi lưu.

Ví dụ tên **máy tính phòng làm việc**: nói **bật máy tính phòng làm việc**.
Tên gọi khác **desktop**: nói **bật desktop**. Tên gọi phải khớp toàn câu;
câu phủ định hoặc có thời gian chờ không tự gửi gói tin.

Tab **Điều khiển** có nút bật từng máy. Nút dùng cấu hình đã lưu; không cần
VBot đang chạy để gửi từ web. Điều khiển giọng nói cần VBot đang chạy.
Cấu hình riêng ở `resource/wake_on_lan/devices.json`, có hiệu lực ngay khi lưu.
Không cần thư viện Python ngoài hoặc môi trường ảo.

Máy tính phải hỗ trợ và bật Wake-on-LAN trong BIOS/UEFI, cấu hình card mạng
cho phép đánh thức bằng Magic Packet. Trạng thái tắt máy, Fast Startup,
driver và kết nối Wi-Fi có thể ảnh hưởng; nên thử Ethernet trước.
Thông báo gửi thành công chưa xác nhận máy đã bật.

Gói tin theo cấu trúc Magic Packet: 6 byte FF và MAC lặp 16 lần.
Nguồn: https://wiki.wireshark.org/WakeOnLan
