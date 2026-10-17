# Rà soát Multiroom và Call LAN — 15/10/2026

## Các điểm đã sửa

1. `resource/vbot_aec3/reference.py`: bridge local im lặng và receiver
   Multiroom có thể cùng gửi tham chiếu trên loa client. Trước đây PID xen
   kẽ bị coi là thay nguồn, gây reset AEC. Bỏ gói toàn zero của PID phụ khi
   PID được chọn vẫn hoạt động trong 300 ms. Vẫn giữ silence của chính nguồn
   đang chọn, vẫn chuyển sang nguồn mới có PCM khác zero và phát hiện gap/rate.
   Nếu nguồn cũ biến mất quá 300 ms, nguồn im lặng mới vẫn được tiếp nhận.
2. `VBot_Multiroom_Bridge_Audio.py`: deadline chờ controller xác nhận capture
   dùng `time.monotonic()`, tránh kéo dài hoặc hết hạn sớm khi đổi giờ hệ thống.
3. `LAN_Calls.py`: đóng runtime đã dừng trước khi tạo lại runtime PJSIP,
   để HTTP socket cũ không giữ cổng 5010 sau khi SIP engine gặp lỗi.

Không thêm AEC/NS/AGC, không tạo recorder hoặc sửa Config.json/Devices.json.
Các sửa đổi ở lượt này nằm trên máy phát triển, chưa triển khai lên loa.

## Các nhánh đã rà soát

- Call LAN dùng PJSIP; AEC PJMEDIA tắt, AEC cuộc gọi xử lý trước bộ NS/AGC
  dùng chung. Khi gọi, AEC media được suspend/giải phóng và khôi phục khi cúp.
- Mic chỉ do một owner đọc. Mute call chặn PCM gửi; kết thúc audio worker
  trước khi trả quyền mic. Dialing/connecting/active chặn wake word.
- Auto accept theo từng peer, delay có thể hủy; thao tác nút gắn call ID,
  callback cũ không áp dụng lên cuộc gọi mới. SIP invite kiểm tra session/IP/token.
- Multiroom receiver áp dụng volume/mute trước sink ALSA, lấy reference sau
  mức volume thực; pause local không dừng cả group. Room idle giải phóng sink.
- Pause/resume do focus không ghi đè pause của người dùng; khi tác vụ hết hạn
  không dừng nhầm nguồn khác. Dọn session cũ có token/event riêng.

## Kiểm chứng

- Nhóm Multiroom/Call LAN/audio focus/reference/optional dependencies:
  **275 passed, 7 skipped**.
- Hai script kiểm tra quyền phát audio và Scheduler: đạt các kịch bản mô phỏng.
- Phân tích cú pháp Python 3.9 cho năm module liên quan: đạt.
- Kiểm tra mới: PID phụ im lặng không giành nguồn; silence của nguồn chính
  vẫn được giữ; runtime Call LAN cũ dừng trước khi mở lại runtime mới.

Toàn bộ hồi quy: **1.754 passed, 33 skipped, 126 subtests passed**, trong
129,23 giây. Các bài bỏ qua không được tính là đã kiểm chứng.

## Giới hạn và bước thử trên loa

Đây là kiểm tra mã và mô phỏng, không có cuộc gọi hoặc nhóm Multiroom thật
được khởi chạy trong lượt rà soát này. Cần thử bản đã triển khai trên hai loa:

1. Nhạc group → wake/STT → trở lại group; loa khác vẫn phát, không phát bù
   packet cũ. Đối chiếu reset_count và foreign_silence trên client.
2. Đang Multiroom/Bluetooth/AirPlay → nhận/gọi Call LAN → cúp; kiểm tra mute
   mic, nói đồng thời và wake word trở lại. Không xử lý NS/AGC hai lần.
3. Từ chối/hết thời gian gọi/mất đầu bên kia → gọi lại; SIP lỗi thì runtime
   dừng, lần khởi tạo tiếp theo phải giải phóng cổng cũ trước.

Lọc PID phụ chỉ giải quyết nguồn im lặng dư thừa. Nếu cố ý trộn đồng thời
hai nguồn PCM khác zero ra loa thì tham chiếu phải là tín hiệu đã trộn;
thay đổi này không triển khai bộ trộn tham chiếu nhiều nguồn độc lập.
