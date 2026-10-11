# Kịch bản nhiều bước bằng giọng nói

Trang quản lý: **WebUI → Tác Vụ Lịch, Calendar → Kịch bản giọng nói nhiều bước** (`Voice_Routines.php`).

Sau khi đưa mã mới lên thiết bị, khởi động lại VBot một lần để nạp module và API mới. Những lần sửa kịch bản tiếp theo chỉ cần lưu trên WebUI, không cần khởi động lại.

Trong `Config.json`, cấu hình bật/tắt toàn bộ tính năng khi khởi động:

```json
"voice_routines": {
  "active": true,
  "minimum_threshold": 0.90
}
```

`true` bật tính năng; `false` bỏ qua định tuyến kịch bản bằng giọng nói và chặn chạy kịch bản qua API. Có thể thay đổi tại **Config.php → Kịch bản nhiều bước bằng giọng nói**. Cờ này được đọc lúc VBot khởi động, nên đổi cờ cần khởi động lại; sửa file kịch bản không thay đổi cờ trong phiên đang chạy. Cấu hình cũ chưa có mục này mặc định bật để giữ hành vi trước khi nâng cấp. Khi tắt, WebUI vẫn cho phép chỉnh và lưu kịch bản để sử dụng sau.

## Cách dùng

1. Thêm kịch bản, đặt tên và nhập câu gọi, mỗi dòng một câu.
2. Thêm các bước rồi dùng nút ↑/↓ để sắp xếp thứ tự.
3. Chọn **Bật kịch bản** và **Lưu toàn bộ**.
4. Nói một câu gọi đã cấu hình, hoặc “chạy kịch bản” / “kích hoạt kịch bản” + tên.
5. Có thể bấm **Chạy thử bản đã lưu** và xem kết quả từng bước ngay trên trang.

Ví dụ kịch bản **Đi ngủ**, câu gọi **Tôi đi ngủ**:

| Bước | Loại | Cấu hình |
|---|---|---|
| 1 | Home Assistant | `light.phong_khach`, tắt |
| 2 | Thao tác loa | Dừng media |
| 3 | Đặt âm lượng | 25% |
| 4 | Chờ | 1 giây |
| 5 | Đọc thông báo | Chúc bạn ngủ ngon |

Thay Entity ID bằng thực thể có thật trong nhà bạn. Kịch bản mẫu **Đi ngủ** trong file mặc định chưa bật và không chứa thiết bị Home Assistant; hãy chỉnh trước khi sử dụng.

### Tìm và tải thực thể Home Assistant

Ở bước **Home Assistant**, ô Entity ID có nút **Tải dữ liệu Home Assistant**. Nút này dùng URL nội bộ (dự phòng URL ngoài) và Long Token đã lưu trong Config để tải toàn bộ danh sách `/api/states`, giữ cả trạng thái, thuộc tính và thông tin thời gian, rồi lưu vào `resource/hass/Home_Assistant.json` dưới khóa `get_hass_all`. Dữ liệu khác trong file được giữ nguyên.

Khi mở `Voice_Routines.php`, JavaScript đọc file đã lưu một lần qua endpoint cùng WebUI có kiểm tra đăng nhập. Danh sách được giữ trong bộ nhớ trang để tìm theo tên thân thiện hoặc Entity ID, hỗ trợ nhập tên tiếng Việt không dấu. Chọn một kết quả sẽ điền Entity ID tương ứng và cập nhật thao tác cho loại thiết bị. Có thể dùng phím lên/xuống và Enter để chọn, hoặc nhập Entity ID trực tiếp dù chưa tải danh sách.

Các loại thực thể chưa hỗ trợ điều khiển trong kịch bản vẫn được lưu đầy đủ và xuất hiện trong tìm kiếm với chú thích, nhưng không chọn được. Bản dữ liệu có thời gian cập nhật; nhấn nút tải để làm mới. Nếu kết nối, token hoặc dữ liệu trả về lỗi, file và danh sách đang có được giữ nguyên. Tải dữ liệu không lưu hoặc thay đổi các kịch bản đang chỉnh sửa.

### Phát một bài Local hoặc link media

Chọn loại bước **Thao tác loa / phát playlist**, sau đó chọn một trong các thao tác:

- **Phát một bài nhạc Local cụ thể:** trang tự đọc thư mục `media_player.music_local.path` đã cấu hình, bao gồm thư mục con và chỉ liệt kê định dạng trong `allowed_formats`. Nhập tên để tìm bài, chọn bài trong danh sách; nút **Tải lại danh sách nhạc** cập nhật khi thêm/xóa file. Bước lưu chính xác đường dẫn bài đã chọn, không tìm kiếm gần đúng khi chạy. File bị xóa, nằm ngoài thư mục nhạc hoặc không thuộc định dạng đã cấu hình sẽ báo lỗi.
- **Phát từ URL / link trực tiếp / stream:** nhập URL HTTP/HTTPS của audio, stream hoặc link YouTube, Zing MP3, NhacCuaTui vào cùng một ô. VBot tự nhận diện nguồn và lấy link phát mới ở mỗi lần chạy thay vì lưu link CDN tạm thời. Không yêu cầu URL phải có đuôi `.mp3`. Khả năng phát phụ thuộc nguồn, quyền truy cập nội dung và bộ xử lý media hiện có. Các kịch bản cũ dùng lựa chọn riêng cho từng nền tảng vẫn chạy được và hiển thị dưới lựa chọn chung này.

Các bước này có ô **Tên hiển thị** không bắt buộc. Nếu để trống, dùng tên file hoặc tên bài lấy được từ nguồn. Chỉ thay thế media/playlist hiện tại sau khi xác định được nguồn mới. Bước báo thành công khi trình phát xác nhận đã bắt đầu phát, rồi chuyển tới bước tiếp theo; không chờ hết bài. Thêm bước **Chờ** nếu muốn giữ nhạc trước khi thực hiện thao tác sau.

## Các bước được hỗ trợ

- **Home Assistant:** bật/tắt đèn, quạt, switch, input_boolean; đặt độ sáng/tốc độ; điều hòa và nhiệt độ; rèm và vị trí; kích hoạt script/scene. Tiếp tục sử dụng danh sách thiết bị được phép/chặn và kiểm tra kết quả của backend hiện có. Khi chưa xác nhận được trạng thái, bước được ghi là lỗi; không tự gửi lại lệnh.
- **Thao tác loa:** phát/tiếp tục, tạm dừng, dừng, bài kế/trước, mute/unmute, microphone, lặp/ngẫu nhiên, nhạc local, playlist và radio đã cấu hình.
- **Đặt âm lượng:** 0–100%.
- **Đặt độ sáng LED ở loa:** 0–100%, chuyển thành thang 0–255 qua `Lib.Set_Led_Brightness`, áp dụng lên hiệu ứng LED hiện tại và đồng bộ trạng thái theo cơ chế có sẵn. Đèn LED phải được bật trong cấu hình loa. Việc nhớ độ sáng qua khởi động lại tuân theo `smart_config.led.remember_last_brightness`.
- **Đọc thông báo:** tối đa 500 ký tự, sử dụng TTS và chính sách đầu ra của phiên hiện tại. Loa chủ đọc từng bước; phiên text-only trả văn bản và không phát trên loa. Với client WebSocket, hợp đồng phản hồi hiện tại chỉ trả kết quả TTS cuối của yêu cầu, không stream riêng từng thông báo trung gian.
- **Chờ:** 0–120 giây mỗi bước, tổng thời gian chờ tối đa 300 giây.

Câu gọi được so sánh trên toàn bộ câu, bỏ qua chữ hoa/thường, khoảng trắng dư và dấu `.?!` cuối câu. `voice_routines.minimum_threshold` mặc định `0.90`, có thể chỉnh tại Config.php từ `0.10` đến `1.00`; `1.00` yêu cầu khớp chính xác sau chuẩn hóa. Khởi động lại VBot sau khi đổi cấu hình. Nếu hai kịch bản gần khớp có điểm chênh lệch dưới `0.03`, VBot không tự chọn; câu khớp chính xác được ưu tiên. Khi bật xử lý nhiều lệnh, câu gọi kịch bản vẫn được giữ nguyên trước khi tách lệnh.

### Điều kiện thời gian

Mỗi kịch bản có thể chọn **Không sử dụng điều kiện**, **Chỉ chạy trong khung giờ**, hoặc **Từ ngày đến ngày, trong khung giờ**. Điều kiện giới hạn quyền chạy khi nhận câu gọi hoặc yêu cầu chạy thử/API; không tự lên lịch chạy.

Giờ nhập theo định dạng 24 giờ `HH:MM`, ngày theo `YYYY-MM-DD`, dùng giờ hệ thống của VBot. Hai giờ phải khác nhau. Khung `22:00–06:00` cho phép chạy qua nửa đêm. Bao gồm phút bắt đầu/kết thúc và ngày đầu/cuối; với khoảng ngày, ngày hiện tại phải nằm trong khoảng đã chọn kể cả khung qua nửa đêm.

Điều kiện được kiểm tra trước từng bước. Khi hết thời gian cho phép, kịch bản dừng và báo bị chặn; thao tác đã gửi hoặc bước chờ đang chạy hoàn tất trước lần kiểm tra tiếp theo. Kịch bản cũ không có trường `condition` mặc định không giới hạn.

```json
"condition": {
  "mode": "date_time",
  "start_date": "2026-10-08",
  "end_date": "2026-10-31",
  "start_time": "18:00",
  "end_time": "23:00"
}
```

Chế độ `time` chỉ cần `start_time` và `end_time`; chế độ `none` không cần ngày/giờ. Lưu, nhập và khôi phục bản sao lưu đều kiểm tra định dạng điều kiện.

## Thực thi và lưu trữ

Các bước chạy lần lượt và chờ kết quả của bước trước. Mặc định dừng khi lỗi; bỏ chọn **Dừng khi một bước lỗi** để tiếp tục các bước sau, nhưng kết quả toàn kịch bản vẫn báo có lỗi. Không tự thử lại thao tác thiết bị.

Chỉ một kịch bản chạy tại một thời điểm. Mỗi bước thao tác có giới hạn 60 giây; toàn lượt chạy tối đa 10 phút. Nút **Dừng kịch bản đang chạy**, hủy request hoặc một request giọng nói mới thay thế request hiện tại sẽ ngăn các bước còn lại. Thao tác đã thực hiện không được hoàn tác; thao tác đang gửi hoặc chạy trong thread có thể vẫn hoàn tất.

Dữ liệu lưu riêng tại `resource/voice_routines.json`, gồm `version: 1` và danh sách `routines`. File tối đa 1 MB, tối đa 50 kịch bản, 10 câu gọi và 30 bước mỗi kịch bản. Runtime nạp lại file ở mỗi lần gọi; một lượt đang chạy giữ bản cấu hình tại thời điểm bắt đầu.

WebUI kiểm tra dữ liệu, khóa file khi lưu, ghi bằng thay thế nguyên tử và giữ bản cũ tại `resource/voice_routines.json.bak`. Mã revision ngăn hai tab ghi đè nhau. Nút **Xuất bản đang sửa** giúp giữ thay đổi trước khi tải lại. Khi file cấu hình hỏng, trang từ chối ghi đè; có thể phục hồi file `.bak` sau khi kiểm tra nội dung. Cơ chế cập nhật chương trình hiện có giữ lại JSON người dùng, trừ khi bạn chủ động chọn thay thế file.

### Nhập, tải xuống, xem JSON, sao lưu và khôi phục

Khu vực cuối `Voice_Routines.php` có các thao tác:

- **Xem JSON đã lưu / Tải xuống JSON đã lưu:** dùng nội dung trên thiết bị. Nút **Xuất bản đang sửa** phía trên dùng dữ liệu đang chỉnh trên trang.
- **Tạo bản sao lưu:** tạo tệp riêng trong `resource/voice_routines_backups`, giữ tối đa 10 bản; danh sách cho phép xem JSON, tải xuống hoặc khôi phục một bản.
- **Tải lên tệp JSON / Dán JSON:** nhập theo ID (bản nhập thay các ID trùng) hoặc thay thế toàn bộ để khôi phục. File tối đa 1 MB, phải đúng schema và không có câu gọi/tên trùng sau khi gộp.

Nhập/khôi phục tự sao lưu nguyên trạng file hiện tại trước khi thay thế, kiểm tra revision và ghi nguyên tử. File không hợp lệ hoặc thao tác lỗi không thay thế cấu hình đang dùng. Có thể phục hồi file JSON bị hỏng bằng chế độ thay thế hoặc một bản sao hợp lệ; nguyên trạng file hỏng cũng được giữ trong bản sao trước thao tác. Các thay đổi chưa lưu trên trang chỉ bị bỏ sau khi người dùng xác nhận và nhập/khôi phục thành công. Mọi thao tác dùng đăng nhập và CSRF của WebUI.

Sao lưu chỉ chứa danh sách kịch bản, không thay đổi `voice_routines.active` trong `Config.json`. Sau khi nhập/khôi phục, cấu hình mới có hiệu lực ở lần gọi tiếp theo; lượt đang chạy vẫn dùng bản đã nạp lúc bắt đầu.

## API chạy và trạng thái

Các route dùng cơ chế xác thực API hiện có (`VBot-API-Key` nếu bật):

- `GET /voice-routines`: trạng thái lượt chạy gần nhất trong bộ nhớ.
- `POST /voice-routines` với `{"action":"run","id":"good_night"}`: chạy kịch bản đã lưu và đang bật, qua pipeline xử lý giọng nói/văn bản chung; trả kết quả sau khi chạy xong.
- `POST /voice-routines` với `{"action":"cancel"}`: yêu cầu dừng lượt đang chạy.

Trang PHP lưu cấu hình qua `includes/php_ajax/Voice_Routines_Ajax.php`, áp dụng đăng nhập WebUI và CSRF như các trang quản lý hiện có.
