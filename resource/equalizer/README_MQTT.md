# EQ qua Home Assistant và MQTT

Cập nhật thư mục `custom_components/vbot_assistant` từ `VBot_Offline_Custom_Component` vào Home Assistant, khởi động lại Home Assistant và VBot. Loa cần bật kết nối MQTT; EQ cần được bật trong Config.php để nghe hiệu ứng.

Thiết bị VBot có select **Chế độ âm thanh - EQ**, gồm các chế độ có sẵn và mẫu người dùng đã lưu. Chọn chế độ sẽ dùng ngay cấu hình EQ chung. Không thay đổi mẫu mặc định sau reboot hoặc công tắc bật/tắt EQ. `Custom` chỉ mô tả cấu hình đang chỉnh tay, không phải mẫu có thể khôi phục.

- Lệnh: `<client_id>/script/equalizer_control/set`, JSON `{"action":"select","id":"builtin:rock"}`, QoS 1, không retain.
- Chỉnh dải: cùng topic, JSON `{"action":"apply","bands":{"bass_db":4.5}}`. Chỉ gửi các dải cần chỉnh; backend hợp nhất dưới khóa file. Home Assistant có 6 number tương ứng, Preamp và Wet/Dry.
- Kết quả: `<client_id>/script/equalizer_control/state`.
- Trạng thái: `<client_id>/equalizer/state`, retained; chứa danh sách mẫu và chế độ đã áp dụng.

WebUI index.php dùng kết nối SSE hiện có để nhận thay đổi từ Home Assistant. Khi chỉnh trên WebUI, VBot phát trạng thái MQTT mới để Home Assistant cập nhật. Không mở thêm SSE, không chạy thêm bộ lọc EQ và không cần build lại thư viện EQ native.

Khi VBot dừng, chỉnh EQ trực tiếp ở WebUI vẫn hoạt động qua bridge; điều khiển MQTT cần VBot đang chạy. Các module root cần build lại nếu phát hành dạng .so: `Api_HTTP.py`, `Api_MQTT.py`, `VBot_MQTT.py`. Giữ các file hỗ trợ trong `resource/equalizer` và PHP/JavaScript trong bản phát hành.
