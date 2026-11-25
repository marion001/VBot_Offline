Danh sách loa gọi nội bộ được lưu trong `Devices.json` tại thư mục này.

Đây là thư mục chung cho Call LAN/PJSIP của dự án. `Devices.json` được VBot
đọc khi khởi động; `tools/` chỉ chứa công cụ triển khai và schema tham khảo,
không chạy trong luồng gọi điện. Các công cụ trước đây ở `resource/lan_pjsip/`
đã được chuyển vào `tools/`. Thư viện native vẫn ở `/home/pi/VBot_AEC3/`.

Hướng dẫn cài thư viện:
[build thủ công WebRTC 2.1/PJSIP](../tools/README_INSTALL_WEBRTC21_PJSIP_MANUAL.md)
hoặc [cài gói runtime đã build](../tools/README_INSTALL_WEBRTC21_PJSIP_PREBUILT.md).

WebUI LAN_Calls.php hỗ trợ xem/tải xuống JSON đã lưu, tạo và khôi phục bản
sao lưu, tải lên hoặc dán JSON, gộp theo ID hoặc thay thế toàn bộ danh sách.
Chỉ quản lý danh sách loa, bao gồm `auto_accept`; không xuất khóa chung hoặc
thay đổi Config.json. Trước khi nhập/khôi phục tự sao lưu dữ liệu hiện tại,
giữ 10 bản tại `html/Backup_Upgrade/Backup_LAN_Calls`. JSON nhập tối đa 40 KB
và 50 loa; cổng TCP 5010/SIP UDP 5060 được giữ cố định. ID/IP trùng, ID chính
loa này, IP ngoài LAN hoặc `auto_accept` không phải boolean sẽ bị từ chối.

LAN_Calls.php chỉ ghi file này, không ghi Config.json. Thư mục và file JSON được cấp quyền 0777 khi lưu. Dữ liệu là một mảng các loa gồm id, name, host, control_port, sip_port và auto_accept. Trường audio_port được giữ để tương thích dữ liệu cũ; PJSIP không dùng cổng PCM cũ.

Nếu chưa có Devices.json, WebUI và Python đọc danh sách cũ từ Config.json để tương thích. Khi lưu danh sách lần đầu, file riêng được tạo; từ đó file này là nguồn dữ liệu chính, kể cả khi danh sách rỗng. Khởi động lại VBot sau khi lưu để nạp danh sách mới.
