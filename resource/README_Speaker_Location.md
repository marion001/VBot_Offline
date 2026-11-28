# Phòng đặt loa và Home Assistant

Thêm cấu hình vào `Config.json`:

```json
"speaker_location": {
  "active": true,
  "room": "Phòng ngủ"
}
```

`active: false` tắt tính năng, cũng là mặc định khi cấu hình cũ chưa có mục này. `room` nhận tên, bí danh hoặc Area ID của phòng trong Home Assistant; có thể nhập tiếng Việt, tiếng Anh hoặc tên phòng theo ngôn ngữ đang sử dụng. Tên phòng được đối chiếu chính xác sau khi bỏ dấu và chuẩn hóa chữ hoa/thường, không tự đoán phòng gần giống.

Có thể sửa cùng cấu hình này tại **Users_Profile.php → Chỉnh sửa hồ sơ**. Trong **Config.php**, ô tên phòng nằm ở nhóm chế độ khởi chạy và ngôn ngữ hệ thống; công tắc sử dụng phòng đặt loa nằm trong nhóm **cấu hình Home Assistant**. Lưu rồi khởi động lại VBot. Khi bật phải nhập phòng; cấu hình phòng không hợp lệ được giữ nguyên và hiển thị thông báo.

Gán đèn, quạt và các thiết bị vào Area tương ứng trong Home Assistant. VBot dùng Area của thực thể; nếu thực thể chưa có Area riêng thì dùng Area của thiết bị. Thiết bị bị vô hiệu hóa hoặc bị chặn bởi danh sách cho phép/không cho phép không được chọn.

Ví dụ loa đặt tại Phòng ngủ:

- “Bật đèn”: chỉ tìm đèn thuộc Phòng ngủ. Một đèn thì điều khiển; nhiều đèn thì hỏi tên cụ thể, giữ lựa chọn trong phiên hiện tại, cho phép trả lời hoặc hủy trong 30 giây sau lời hỏi.
- Đèn khai báo bằng `switch` được nhận diện theo tên/bí danh có từ khóa đèn hoặc `device_class: light`. Nếu switch chỉ có tên như D1/D2 và chưa rõ chức năng, VBot hỏi chọn đích trước khi điều khiển. Khi đang hỏi lại, có thể trả lời tên, Entity ID (nếu đã bật nguồn tìm kiếm Entity ID) hoặc phòng có duy nhất một đích trong danh sách đã hỏi; nhiều đích trong cùng phòng vẫn cần tên cụ thể. Lệnh “bật đèn” không đạt ngưỡng tên cũng có thể mở phiên chọn các đèn/switch hợp lệ, ngay cả khi tính năng phòng đặt loa đang tắt.
- Phiên hỏi lại giữ toàn bộ đích hợp lệ đã tìm được, không giới hạn số lượng đích. Các giới hạn phòng và quyền điều khiển vẫn áp dụng. Nếu có tối đa 5 đích, VBot đọc tên để lựa chọn. Nếu có hơn 5 đích, VBot chỉ thông báo có nhiều thiết bị phù hợp và yêu cầu nói tên đầy đủ hoặc hủy, không đọc tên ví dụ. Log `[HomeAssistant tìm kiếm]` ghi số thực thể đọc từ máy chủ và số đích tìm kiếm; log `[HomeAssistant hội thoại]` xác nhận phiên đã được mở và hành động đang giữ.
- “Bật đèn phòng khách”: dùng phòng được nói rõ trong câu.
- “Bật đèn bàn”: tên thiết bị cụ thể vẫn đi qua tìm kiếm thiết bị hiện có, không tự thêm phòng.
- Tên hoặc bí danh cụ thể được ưu tiên khi chọn đích trong phòng, không bị loại chỉ vì thực thể là `switch`, `light` hay `fan`. Domain của thực thể được dùng để gửi dịch vụ điều khiển thích hợp; các giới hạn thực thể được phép và khả năng hỗ trợ từng hành động vẫn áp dụng.
- Entity ID được nói rõ không bị đổi sang thiết bị trong phòng đặt loa.
- “Bật tất cả đèn” dùng phòng đặt loa nhưng vẫn cần bật quyền điều khiển nhiều thiết bị trong cấu hình Area của Home Assistant.

Khi không đọc được dữ liệu Area, phòng không tồn tại, tên phòng trùng nhiều Area hoặc không có thiết bị hợp lệ trong phòng, lệnh dùng phòng đặt loa bị chặn và thông báo lý do; không tự chuyển sang đèn ở phòng khác. Không bắt buộc bật riêng `home_assistant.search_sources.area` để dùng phòng đặt loa. Home Assistant và quyền truy cập registry vẫn cần sẵn sàng.

Kịch bản nhiều bước có Entity ID cố định vẫn dùng Entity ID đã lưu. Chế độ chỉ chạy XiaoZhi hoặc mã xử lý người dùng tự viết cần đi qua bộ xử lý Home Assistant của VBot để áp dụng quy tắc này.
