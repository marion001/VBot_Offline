# Cloudflare Tunnel cho VBot WebUI

Tính năng Cloudflare Tunnel cho phép truy cập WebUI và API ngoài của VBot qua HTTPS mà không cần mở port trực tiếp trên router. Trang quản lý hỗ trợ cả **Quick Tunnel** và **Named Tunnel dùng domain riêng**.

Trang WebUI:

```text
Cloudflare_Tunnel.php
```

## 1. Tính năng được hỗ trợ

- Tạo và quản lý nhiều hồ sơ Tunnel.
- Quick Tunnel với domain ngẫu nhiên `trycloudflare.com`, không cần tài khoản Cloudflare.
- Named Tunnel với domain/hostname riêng.
- Bật một hồ sơ, chuyển đổi hồ sơ đang chạy hoặc dừng Tunnel.
- Tự khởi động Tunnel cùng hệ thống bằng service `cloudflared.service`.
- Kiểm tra vị trí file thực thi, phiên bản cloudflared, trạng thái service, cấu hình và URL công khai.
- Hiển thị URL WebUI và URL API VBot tương ứng.
- Tìm kiếm hồ sơ theo tên, domain hoặc URL nội bộ.
- Lưu API Token hoặc Global API Key theo từng hồ sơ.
- Xóa riêng hồ sơ VBot hoặc xóa toàn bộ Tunnel, DNS, credentials và cấu hình cục bộ.
- Tự nhận diện đúng hồ sơ Domain đang chạy theo UUID Tunnel/hostname, tránh nhầm với Quick Tunnel có cùng URL nội bộ.

## 2. Điều kiện sử dụng

Trong WebUI, vào:

```text
Cấu Hình Config → Cấu Hình Web Interface/Cloudflare Tunnel (Giao Diện)
```

Bật:

```text
Cho Phép Truy Cập Bên Ngoài Internet
```

Khuyến nghị bật đăng nhập WebUI và sử dụng mật khẩu đủ mạnh trước khi công khai địa chỉ Tunnel.

Hệ thống cần có:

- Kết nối Internet.
- `cloudflared` đã được cài đặt.
- PHP extension SSH2 để WebUI điều khiển hệ thống qua SSH.
- PHP extension cURL nếu sử dụng Cloudflare API để xóa DNS.
- Tài khoản VBot SSH có quyền chạy các lệnh `sudo` cần thiết.

## 3. Cài đặt cloudflared

Đăng nhập SSH vào Raspberry Pi và chạy:

```bash
wget -O /tmp/cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-armhf.deb
sudo dpkg -i /tmp/cloudflared.deb
```

Tùy chọn `-O /tmp/cloudflared.deb` luôn ghi nội dung tải về vào đúng tên file. Nếu file cũ đã tồn tại trong `/tmp`, `wget` sẽ ghi đè file đó.

Kiểm tra sau khi cài:

```bash
command -v cloudflared
cloudflared --version
```

> Gói `armhf` phù hợp với hệ điều hành ARM 32-bit. Nếu Raspberry Pi đang chạy hệ điều hành 64-bit, cần dùng gói cloudflared đúng kiến trúc tương ứng.

## 4. Sử dụng Quick Tunnel

Quick Tunnel phù hợp để thử nghiệm nhanh:

1. Mở `Cloudflare_Tunnel.php`.
2. Nhấn **Thêm Tunnel**.
3. Nhập tên hồ sơ.
4. Chọn **Quick Tunnel**.
5. Giữ URL dịch vụ nội bộ mặc định:

   ```text
   http://127.0.0.1:80
   ```

6. Lưu hồ sơ.
7. Bật công tắc của hồ sơ và nhấn **Áp dụng thay đổi**.
8. Chờ service khởi động, sau đó nhấn **Làm mới** hoặc **Kiểm tra hệ thống** để lấy URL `trycloudflare.com`.

URL Quick Tunnel có thể thay đổi sau khi service hoặc Raspberry Pi khởi động lại. Không nên dùng Quick Tunnel làm địa chỉ cố định lâu dài.

## 5. Sử dụng Domain riêng

### 5.1. Chuẩn bị Named Tunnel

Đăng nhập Cloudflare một lần:

```bash
cloudflared tunnel login
```

Nếu `/home/pi/.cloudflared/cert.pem` đã tồn tại và còn hợp lệ thì có thể bỏ qua bước đăng nhập.

Tạo Tunnel:

```bash
cloudflared tunnel create vbot_domain_tunnel
```

Gắn hostname vào Tunnel:

```bash
cloudflared tunnel route dns vbot_domain_tunnel vbot.example.com
```

Tìm file credentials:

```bash
ls -1 /home/pi/.cloudflared/*.json
```

### 5.2. Tạo hồ sơ trên WebUI

Trong cửa sổ **Thêm Cloudflare Tunnel**, chọn **Domain riêng** và nhập:

- Tên hồ sơ.
- URL nội bộ, thông thường là `http://127.0.0.1:80`.
- Domain/hostname, ví dụ `vbot.example.com`.
- Tên hoặc UUID Tunnel.
- Đường dẫn tuyệt đối tới credentials JSON, ví dụ `/home/pi/.cloudflared/UUID.json`.
- Tùy chọn tự khởi động cùng hệ thống.

Sau khi lưu, bật hồ sơ và nhấn **Áp dụng thay đổi**. WebUI sẽ tạo:

```text
/home/pi/Cloud_Flare/config.yml
/etc/systemd/system/cloudflared.service
```

Service Domain sử dụng cấu hình ingress gồm hostname đã chọn, URL dịch vụ nội bộ và fallback `http_status:404`.

## 6. Xác thực Cloudflare API

Thông tin API không bắt buộc để chạy Tunnel. Nó được dùng để tìm Zone và xóa bản ghi DNS khi người dùng chọn xóa toàn bộ. Named Tunnel được xóa bằng lệnh `cloudflared tunnel delete`, sử dụng chứng thực `cert.pem` đã tạo lúc `cloudflared tunnel login`.

### Global API Key

Đây là lựa chọn được WebUI khuyến nghị vì thiết lập nhanh. Cần nhập:

- Email tài khoản Cloudflare.
- Global API Key.

Global API Key có quyền rộng trên tài khoản, vì vậy cần bảo vệ file hồ sơ và tài khoản WebUI.

### API Token

Nếu muốn giới hạn quyền, tạo Custom API Token có tối thiểu:

```text
Zone / Zone / Read
Zone / DNS / Edit
```

Giới hạn token vào đúng Zone chứa hostname VBot nếu có thể.

Nút hình con mắt cho phép xem/ẩn API Token hoặc Global API Key đã lưu. Nếu hồ sơ chưa có khóa, WebUI sẽ hiển thị thông báo thay vì trả dữ liệu rỗng.

## 7. Áp dụng, kiểm tra và dừng Tunnel

- Công tắc trong từng dòng chỉ chọn hồ sơ dự kiến sử dụng.
- Nhấn **Áp dụng thay đổi** để thực sự ghi service và khởi động Tunnel.
- Chỉ một hồ sơ được áp dụng tại một thời điểm.
- Nhấn **Kiểm tra hệ thống** để xem trạng thái thực tế của cloudflared và systemd.
- Nhấn **Dừng Tunnel** để dừng service hiện tại.
- Nếu bật **Tự khởi động cùng hệ thống**, service được enable trong systemd.

Kiểm tra thủ công qua SSH:

```bash
sudo systemctl status cloudflared.service --no-pager -l
sudo systemctl is-enabled cloudflared.service
sudo systemctl is-active cloudflared.service
sudo journalctl -u cloudflared.service -n 80 --no-pager
```

## 8. Xóa hồ sơ và tài nguyên Cloudflare

WebUI cung cấp hai lựa chọn:

### Chỉ xóa hồ sơ VBot

- Chỉ xóa dữ liệu hồ sơ trong WebUI.
- Không xóa Tunnel, DNS hoặc credentials trên Cloudflare/Raspberry Pi.

### Xóa toàn bộ Tunnel + DNS + credentials

- Dừng và vô hiệu hóa service nếu hồ sơ đang chạy.
- Xóa bản ghi CNAME Tunnel của hostname bằng Cloudflare API.
- Xóa Named Tunnel bằng cloudflared.
- Xóa credentials JSON của Tunnel.
- Dọn `config.yml` và service cục bộ.
- Xóa hồ sơ VBot.

Thao tác này không thể hoàn tác và yêu cầu nhập lại đúng tên hồ sơ. `cert.pem` không bị xóa vì có thể đang được dùng chung cho các Tunnel khác.

Nếu hồ sơ chưa lưu thông tin API, WebUI sẽ yêu cầu nhập API Token hoặc Global API Key khi chọn xóa toàn bộ.

## 9. Dữ liệu hồ sơ và bảo mật

Dữ liệu được lưu tại:

```text
resource/cloudflare_tunnel/profiles.json
```

Nếu thư mục hoặc file chưa tồn tại, WebUI sẽ tự tạo thư mục và tạo file khi lưu lần đầu. Thư mục và file được đặt quyền `0777` theo cơ chế tương thích hiện tại của VBot.

`profiles.json` có thể chứa API Token hoặc Global API Key ở dạng dữ liệu cục bộ. Vì vậy:

- Nếu `profiles.json` bị mất hoặc bị thay bằng kho rỗng, khi mở trang quản lý WebUI,
  hệ thống sẽ đọc `cloudflared.service` và `/home/pi/Cloud_Flare/config.yml` để tự
  tái tạo hồ sơ Quick/Named Tunnel đang cấu hình. API Token hoặc Global API Key
  không tồn tại trong runtime nên phải nhập lại nếu cần thao tác xóa DNS từ WebUI.

- Không tải file này lên Git hoặc chia sẻ công khai.
- Không đưa nội dung file vào ảnh chụp/log hỗ trợ.
- `.gitignore` trong thư mục đã loại trừ `profiles.json` và các file tạm `profiles.json.tmp.*`.
- Có thể xóa `profiles.json` trước khi phát hành mã nguồn; WebUI sẽ tự tạo lại khi cần.

WebUI không trả khóa bí mật trong API danh sách hồ sơ. Khóa chỉ được trả khi người dùng đã đăng nhập, có CSRF hợp lệ và chủ động nhấn nút xem khóa.

## 10. Xử lý lỗi thường gặp

### Cloudflared chưa được cài đặt

Chạy lại các lệnh trong mục cài đặt, sau đó nhấn **Kiểm tra hệ thống**.

### Service báo đang chạy nhưng hồ sơ hiển thị đang tắt

Nhấn **Làm mới** hoặc **Kiểm tra hệ thống**. Hệ thống nhận diện hồ sơ Domain theo UUID Tunnel/hostname trong `config.yml`; Quick Tunnel được nhận diện theo tham số `--url` trong `ExecStart`.

Kiểm tra thêm:

```bash
sudo systemctl show cloudflared.service --property=ExecStart --value
sudo cat /home/pi/Cloud_Flare/config.yml
```

### Lệnh quá thời gian cho phép

Việc khởi động Tunnel có thể mất thời gian do mạng hoặc Cloudflare. WebUI sẽ kiểm tra lại trạng thái service sau khi kết nối SSH chậm. Nếu vẫn lỗi, kiểm tra:

```bash
sudo systemctl status cloudflared.service --no-pager -l
sudo journalctl -u cloudflared.service -n 80 --no-pager
```

### Domain không truy cập được

- Kiểm tra hostname đã được route tới đúng Tunnel.
- Kiểm tra credentials JSON tồn tại và đọc được.
- Kiểm tra UUID trong hồ sơ khớp với file credentials và `config.yml`.
- Kiểm tra dịch vụ nội bộ hoạt động tại `http://127.0.0.1:80`.
- Kiểm tra **Cho Phép Truy Cập Bên Ngoài Internet** đã được bật trong Config.php.

### Không xóa được DNS

- Với API Token, kiểm tra quyền `Zone / Zone / Read` và `Zone / DNS / Edit`.
- Với Global API Key, kiểm tra đúng email tài khoản và khóa.
- Kiểm tra hostname thuộc một Zone đang hoạt động trong tài khoản Cloudflare.
- PHP cần có extension cURL để gọi Cloudflare API.

### WebUI báo lỗi SSH hoặc không ghi được service

- Kiểm tra cấu hình SSH của VBot.
- Kiểm tra PHP đã có extension SSH2.
- Kiểm tra user SSH có thể dùng `sudo`.
- Kiểm tra quyền ghi `/home/pi/Cloud_Flare` và `/etc/systemd/system`.
