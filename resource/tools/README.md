# Công cụ triển khai dùng chung

Các file tại đây chỉ chạy thủ công khi triển khai cập nhật, không được VBot
import hoặc gọi trong quá trình hoạt động bình thường. Có thể bỏ thư mục này
khỏi bản cài chạy thực tế nếu không cần công cụ triển khai.

- [Cài WebRTC 2.1 và PJSIP thủ công từng bước](README_INSTALL_WEBRTC21_PJSIP_MANUAL.md).
- [Đóng ZIP và cài bản đã build lên loa mới](README_INSTALL_WEBRTC21_PJSIP_PREBUILT.md).
- `audio_native/`: wrapper 2.1, bài kiểm tra native và công cụ đóng/cài runtime.
- ZIP native đã build và checksum được phát hành trong `../vbot_aec3/`.

`deploy_audio_scheduler_wol.py` kiểm tra checksum và cú pháp của gói cập nhật,
sao lưu file trước khi thay thế, giữ cấu hình thiết bị và hỗ trợ kích hoạt
đường dẫn WebRTC 2.1 bằng tùy chọn `--activate-aec21`. Công cụ yêu cầu dừng
VBot trước khi chạy. File trước đây nằm trực tiếp trong `resource/`.

Không chạy lại với gói cập nhật cũ sau khi đã chỉnh sửa/nâng cấp mã nguồn,
vì gói đó có thể thay thế các file bằng phiên bản cũ.
