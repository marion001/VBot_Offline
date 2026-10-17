# Rà soát gọi trình duyệt và cấu hình — 15/10/2026

## Thay đổi

- Bỏ cờ môi trường. Chỉ bật khi `lan_calls.active` và
  `lan_calls.browser_call.active` cùng là boolean `true`.
- Checkbox trong Config.php; giữ tùy chọn cũ nếu POST từ trang cache chưa có
  trường mới. Có thể tắt riêng gọi trình duyệt mà vẫn giữ gọi LAN.
- API truyền cấu hình đang chạy vào bộ điều khiển; khi tắt không tạo runtime.
- Watchdog kết thúc khi cấu hình đang chạy bị tắt, runtime LAN dừng, mất audio
  10 giây, lỗi thiết bị hoặc quá 15 phút.
- Trình duyệt kết thúc khi mic bị thu hồi hoặc ngừng cấp PCM quá 5 giây.
- Trạng thái gọi trình duyệt được công bố qua kênh cập nhật LAN; nút gọi loa
  khác không hiện khả dụng trong lúc mic đang dành cho trình duyệt.
- Giới hạn payload proxy 12 KB, timeout theo thao tác; không retry PCM để tránh
  phát lặp hoặc tăng trễ khi mất mạng.

## Các đường đã rà soát

- Browser và LAN dùng chung khóa chiếm mic; gọi đến/đi LAN bị từ chối khi đang
  giữ mic cho trình duyệt. Không tạo PvRecorder thứ hai.
- Dùng CallAudio: một AEC cuộc gọi, NS/AGC dùng chung sau AEC; không thêm bộ lọc
  vào transport HTTP. Mic trình duyệt là thiết bị độc lập với mic trên loa.
- Dọn luồng audio trước khi trả mic; nếu dọn thất bại giữ reservation để tránh
  hai luồng đọc đồng thời. Không tự khởi động lại dịch vụ khi xảy ra lỗi.
- Mute vật lý không được vượt qua bởi dữ liệu TX đã đệm.
- CSRF chung + CSRF phiên gọi, cookie same-origin, token riêng và số thứ tự
  PCM được kiểm tra. API chỉ nhận qua loopback. Không ghi PCM xuống đĩa.
- Hàng đợi capture trình duyệt tối đa hai khối, playback worklet giới hạn,
  FIFO loa giới hạn; một yêu cầu audio đang chờ tại mỗi thời điểm.
- Không đổi tuyến phát AirPlay/Bluetooth/Multiroom hoặc thư viện native.

## Xác minh

- Bộ kiểm tra toàn dự án: 1.784 passed, 33 skipped, 126 subtests.
- Sau bổ sung trạng thái browser_active: chạy lại nhóm gọi trình duyệt, LAN,
  conversation và điều khiển: 98 passed.
- PHP lint các trang/endpoint sửa; kiểm tra cú pháp JavaScript và mô phỏng
  AudioWorklet 48 kHz → 16 kHz, playback, giới hạn hàng đợi.
- Kiểm tra riêng cấu hình chỉ bật bằng boolean, cờ môi trường cũ không bật được.

Đây không phải cam kết không còn lỗi. Cần chạy thử lâu trên phần cứng, trình
duyệt và HTTPS thực tế sau khi khởi động lại VBot. Transport vẫn là PCM HTTPS
100 ms, khoảng 10 yêu cầu/giây; không gọi là WebRTC RTP và chưa đo tải CPU/RAM
của phiên âm thanh thực tế trong lần rà soát này.
