# Học nút Bluetooth

Mở **Quản Lý Thiết Bị Bluetooth → Điện thoại, loa và tai nghe**. Ngay dưới mỗi thiết bị là danh sách nút của đúng MAC đó và nút **Học nút**. Thiết bị ngắt kết nối vẫn xem/sửa/xóa nút đã lưu được; cần kết nối lại để học nút mới. Thiết bị hỗ trợ cả Sink và Source có thể xuất hiện ở hai nhóm, cùng hiển thị một cấu hình nút theo MAC.

Nhấn **Học nút** để mở modal, rồi nhấn nút trên thiết bị trong 15 giây. Mã nút nhận được hiện trong input chỉ đọc. Đặt tên, chọn chức năng, bật/tắt **Kích hoạt nút** và lưu trong 3 phút. Có thể sửa tên/chức năng/trạng thái hoặc xóa từng nút. Tắt nút chỉ ngừng hành động đã gán trong VBot, không vô hiệu hóa nút vật lý trên loa. Nút đã lưu trước khi có công tắc vẫn mặc định bật.

Danh sách chức năng dùng chung `ActionRegistry.php` và `Action_Registry.py` với nút/kịch bản hiện có; có cả playlist, radio và chạy kịch bản nhiều bước đã lưu đang bật. Để dùng TTS, Home Assistant hoặc các bước có tham số, tạo kịch bản trong Voice_Routines.php rồi chọn kịch bản đó cho nút.

Dữ liệu riêng nằm tại `resource/bluetooth/button_keys.json`, bản trước lần sửa gần nhất ở `button_keys.json.bak`. Không phát hành dữ liệu cá nhân này. Khi chưa có file, danh sách trống; lưu lần đầu sẽ tạo file.

File `button_keys.json` được tạo với quyền `0777` và chủ sở hữu theo thư mục Bluetooth của dự án. File `.bak` được bỏ qua trong bước chmod khi VBot khởi động.

VBot cần chạy để thực hiện hành động. Sau khi cập nhật lần đầu, khởi động lại VBot; nếu phát hành bản biên dịch thì build lại `Bluetooth.py`. `resource/bluetooth/button_keys.py` giữ nguyên Python. Không cần restart bridge hoặc build thư viện âm thanh.

Chỉ hỗ trợ thiết bị xuất Linux Bluetooth input (ví dụ AVRCP). Nút được loa xử lý nội bộ không thể học. Học một lần nhấn thực tế; không suy đoán thao tác giữ/đúp. Linux giữ nguyên xử lý Bluetooth gốc, nên nút có thể vẫn tác động ở thiết bị hoặc chương trình khác. Trong lượt học, chỉ các hành động Bluetooth được VBot gán mới tạm ngừng.

Định danh bằng MAC trong input khi có; nếu kernel chỉ cung cấp tên, phải khớp duy nhất với một thiết bị Bluetooth đang kết nối. Hai thiết bị trùng tên sẽ không bị gán nhầm. VBot cần quyền đọc `/dev/input/event*` (tài khoản `pi` trên image hiện tại thuộc nhóm `input`). Không chiếm độc quyền input, không thay đổi mic, AEC, EQ hoặc tuyến phát.

Lượt học có khóa chống hai trình duyệt học đồng thời; không lưu khi không nhận được sự kiện. Bỏ sự kiện giữ lặp, chống nhấn trùng trong 350 ms, không xếp hàng nhiều hành động đang chạy. Khi VBot không chạy vẫn học/lưu được qua WebUI; hành động chỉ chạy sau khi VBot khởi động.
