# Thử gọi từ trình duyệt tới chính loa VBot

Bản thử riêng, chưa phải chức năng phát hành. Không cần cài thêm thư viện,
không đổi AirPlay/Bluetooth và không build lại PJSIP/AEC3.

## Cơ chế

Trình duyệt dùng getUserMedia và AudioWorklet để trao đổi PCM mono S16_LE
16 kHz qua HTTPS theo khối 100 ms. PHP proxy tới API loopback của VBot, không
ghi âm xuống đĩa. Một yêu cầu gửi và nhận âm thanh, không gửi song song;
hàng đợi giới hạn để không tích lũy trễ vô hạn.

Đây là **HTTPS PCM thử nghiệm**, chưa phải SIP/WebRTC RTP trên trình duyệt.
Chi phí khoảng 10 yêu cầu PHP/giây. Phải đo độ trễ và tải CPU thực tế trên Pi
Zero 2W trước khi chọn transport cho bản chính thức.

Phía loa dùng lại LAN_Calls.CallAudio: PvRecorder hiện có, một AEC cuộc gọi và
NS/AGC chung sau AEC. Không mở endpoint PJSIP mới. echoCancellation,
noiseSuppression, autoGainControl trên trình duyệt xử lý mic điện thoại/máy
tính, là mic khác với mic loa nên không lọc trùng.

Một cuộc gọi trình duyệt hoặc LAN được chiếm loa tại một thời điểm. Chặn wake
word khi gọi. Nhấn gọi trên WebUI sẽ mở mic loa ngay; bản thử chưa có chuông
và xác nhận ở phía loa. Dùng đăng nhập WebUI, CSRF, token phiên âm thanh riêng
và API chỉ nhận qua loopback. Không mở cổng mới.

## Thử thực tế

1. Trong Config.php, bật gọi LAN và “Cho phép gọi từ trình duyệt tới loa này”,
   lưu cấu hình. Các khóa tương ứng trong Config.json:

```json
"lan_calls": {
    "active": true,
    "browser_call": {"active": true}
}
```

Đây là phần cấu hình minh họa; giữ nguyên các khóa gọi LAN khác khi chỉnh.
2. Mở WebUI bằng **HTTPS với chứng chỉ được trình duyệt tin cậy**. HTTP qua IP
   LAN không được cấp mic. Chỉ thử trong cùng LAN, không công khai trang thử.
3. Dừng phiên VBot cũ rồi chạy thủ công:

```bash
cd /home/pi/VBot_Offline
python3 Start.py
```

4. Vào **Gọi điện LAN → Thử gọi từ trình duyệt tới loa này**, hoặc mở
   `LAN_Calls.php#browserCallSection`. Nhấn gọi và cấp quyền microphone.
5. Dùng tai nghe khi điện thoại/máy tính ở gần loa. Thử nói hai chiều, tắt/bật
   mic trình duyệt, kết thúc và kiểm tra wake word trở lại.
6. Xem **Thông số kiểm thử**: thời gian request cần dưới 100 ms để giữ nhịp.
   Thử đóng tab/mất mạng, sau đó kiểm tra gọi LAN vẫn hoạt động.

Loa tự kết thúc khi thiếu audio quá 10 giây, có lỗi âm thanh, runtime LAN dừng
hoặc cuộc gọi quá 15 phút. Đóng tab cũng gửi lệnh kết thúc. Nếu dọn mic bị lỗi,
giữ quyền mic để tránh hai luồng đọc đồng thời; xem log và khởi động lại VBot.

Chạy `python3 Start.py` bình thường. Tắt `lan_calls.browser_call.active` rồi
khởi động lại VBot để tắt gọi trình duyệt; gọi LAN giữa các loa vẫn dùng cấu hình
riêng. Không sử dụng biến môi trường để bật chức năng này nữa.

## Kiểm tra phương án WebRTC

Đã kiểm tra loa .175 Python 3.9.2 ARMv7: chưa có aiortc/PyAV. Kiểm tra tải wheel
bằng pip không tìm được PyAV 14 phù hợp aiortc 1.13.0 trong PyPI/piwheels.
Chưa cài hoặc nâng cấp thư viện của VBot. ZIP runtime PJSIP/AEC3 không chứa
aiortc. Bản thử dùng PCM HTTPS để không phải đổi bộ phụ thuộc hiện tại.

Nguồn: [getUserMedia](https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia),
[AudioWorklet](https://developer.mozilla.org/en-US/docs/Web/API/AudioWorklet),
[aiortc 1.13.0](https://github.com/aiortc/aiortc/blob/1.13.0/pyproject.toml).
