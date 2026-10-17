# Rà soát trước khi build và phát hành — 15/10/2026

## Phạm vi

Rà soát các nhánh mới về WebRTC 2.1, PJSIP/Call LAN, dùng chung mic,
quyền phát âm thanh, nhạc chuông, cấu hình và sao lưu WebUI, Scheduler,
Wake-on-LAN, Bluetooth, AirPlay và Multiroom. Các kiểm tra tự động chạy
trên máy phát triển; không khởi chạy lại VBot hoặc thay đổi cấu hình hai loa.

## Các lỗi đã sửa trong lượt này

- Luồng SIP gặp lỗi chưa đánh dấu dịch vụ đã dừng. Hiện tại đặt cờ dừng,
  trạng thái không còn báo khả dụng và từ chối gọi mới qua engine đã chết.
- Khi trả lại AEC phát nhạc bị lỗi, bước trả quyền mic có thể bị bỏ qua.
  Đã dùng `finally` để vẫn thực hiện giải phóng quyền mic và audio focus.
- Công cụ cài native dùng tên file tạm cố định. Đã chuyển sang file tạm
  tạo độc quyền, ghi và đồng bộ trước khi thay thế, dọn khi thất bại;
  từ chối lock symlink và vị trí sao lưu ngoài thư mục thư viện.
- Kiểm tra kích thước manifest ZIP trước khi giải nén và từ chối thành phần
  không phải file thường. Ghi danh sách file đã cài trong phạm vi rollback.
- Bài kiểm tra chẩn đoán TTS còn phụ thuộc chữ ký `Play_Sound` cũ. Đã đổi
  sang tìm phương thức qua AST, kiểm tra đúng toàn bộ thân phương thức.

Có kiểm tra hồi quy cho lỗi SIP, giải phóng mic khi khôi phục media thất bại,
file tạm và rollback khi sao chép bị ngắt. Không sửa Config.json/Devices.json,
không thay gain hoặc cấu hình ALSA.

## Kiểm tra đã thực hiện

- Chuyển mã Cython 3.3.0 cho 57 module Python gốc: không lỗi. Sau sửa đổi
  đã chuyển mã lại LAN_PJSIP.py. Đây là bước sinh mã C, chưa phải biên dịch
  và chạy extension ARM trên Python 3.9 của loa.
- Phân tích cú pháp tương thích Python 3.9 cho 72 file gốc và module audio,
  Call LAN, Wake-on-LAN, công cụ: đạt.
- PHP 7.4 lint toàn bộ 103 file trong html: đạt.
- Cú pháp sáu file JavaScript liên quan Call LAN, nhạc chuông, Wake-on-LAN,
  kịch bản và sao lưu: đạt. Sáu bộ kiểm tra hành vi WebUI trong V8 đạt,
  tổng cộng 60 tình huống.
- Sáu script kiểm tra API, quyền phát audio, Bluetooth, vòng đời dịch vụ,
  cleanup, Scheduler và các nhánh lỗi gốc: đạt.
- Bộ ZIP native đã cập nhật helper mới, manifest và SHA-256. Đối chiếu lại
  checksum cả 13 file; không thay binary WebRTC/PJSIP trong gói này.

Pytest cuối cùng: **1.746 passed, 33 skipped, 126 subtests passed**, chạy
trong 121,35 giây. Các bài bỏ qua không được tính là đã kiểm chứng.

## Trước khi đóng gói bản .so

1. Build trên đúng kiến trúc và Python của loa. Kiểm tra artifact mới đã
   thay thế extension cũ cùng tên: Python có thể ưu tiên `.so` hơn `.py`.
   Riêng thay đổi runtime lần này nằm ở `LAN_PJSIP.py`.
2. Giữ module gốc chưa được chọn build dưới dạng `.py`. Danh sách trong
   `Build_SO/2.py` hiện chỉ chọn một số module, không tự build toàn bộ VBot.
3. Giữ `resource/vbot_aec3/`, `resource/call_lan/` và các tài nguyên WebUI,
   âm thanh cần thiết. Thư viện native và vendor vẫn ở `/home/pi/VBot_AEC3/`.
   Chỉ sao chép wrapper `.so` mà thiếu vendor shared libraries là chưa đủ.
4. Không đưa cấu hình, khóa, ID, danh sách loa riêng của máy phát triển
   đè lên thiết bị người dùng. Công cụ cài native không cài thay các route
   ALSA hay dịch vụ audio của thiết bị mới.

## Kiểm tra thực tế còn cần trước khi phát hành

- Chạy bản `.so` cuối cùng trên cả .175 và .132; gọi hai chiều, nói đồng
  thời, từ chối, hết thời gian, mất kết nối, gọi lại và cúp máy bằng nút.
- Kiểm tra auto accept theo từng loa, delay và các kiểu nhấn nút; mute
  trong cuộc gọi không truyền mic và kết thúc trả mic về wake word.
- Nhạc chuông đến/đi lặp hết thời gian chờ và dừng ngay khi nhận/cúp máy;
  giọng nói nhận cuộc gọi không bị tiếng chuông giữ quyền phát.
- Chạy nhạc local, Bluetooth, AirPlay và Multiroom; wake word, STT, tiếp
  tục phát và đổi nguồn. Theo dõi clipping, underrun, queue và CPU/RAM.
- Thử thiếu thư viện trên bản sao cấu hình/thư mục thử riêng: VBot vẫn
  chạy, ghi rõ lý do bỏ qua bộ lọc hoặc Call LAN.
- Thử lưu cấu hình, tải/xem JSON, sao lưu và khôi phục bằng trình duyệt.

Kiểm tra mô phỏng không xác nhận được chất lượng âm thanh, độ ổn định Wi-Fi,
GPIO hoặc ABI thực tế của bản `.so` sắp build. Không kết luận đã đủ điều kiện
phát hành chỉ dựa trên các kiểm tra ở máy phát triển.
