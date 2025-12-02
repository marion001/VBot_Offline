<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
?>
<?php
if ($Config['contact_info']['user_login']['active']) {
  session_start();
  // Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
  if (
    !isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
  ) {

    // Nếu chưa đăng nhập hoặc đã quá 12 tiếng, hủy session và chuyển hướng đến trang đăng nhập
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
  }
  // Cập nhật lại thời gian đăng nhập để kéo dài thời gian session
  //$_SESSION['user_login']['login_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<style>
        .code-block, .output-block {
            font-family: 'JetBrains Mono', Consolas, monospace;
            font-size: 0.94rem;
        }
        .cmd { color: #28a745; font-weight: 500; }
        .path { color: #dc3545; }
    </style>
<body>
  <!-- ======= Header ======= -->
  <?php
  include 'html_header_bar.php';
  ?>
  
  <!-- End Header -->
  <!-- ======= Sidebar ======= -->
  <?php
  include 'html_sidebar.php';
  ?>
  <!-- End Sidebar-->
  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Hướng dẫn, Hỗ Trợ</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">FAQ</li>
        </ol>
      </nav>
    </div>
    <!-- End Page Title -->
    <section class="section">
      <div class="row">
        <div id="accordion">
          <div class="card">
            <br />
            <!-- 
            <div class="card accordion" id="accordion_button_media_player_source">
            <div class="card-body">
            <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player_source" aria-expanded="false" aria-controls="collapse_button_media_player_source">
            Tets  Drop Down:</h5>
            <div id="collapse_button_media_player_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_media_player_source">
            hihi Vũ Tuyển
            </div>
            </div>
            </div>
            -->
            <div class="card-body">
              <div class="alert alert-success" role="alert">
                Link Tải Xuống IMG: <a href="https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ" target="_blank">https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>
              </div>
              <div class="card accordion" id="accordion_button_mic_tetser">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_mic_tetser" aria-expanded="false" aria-controls="collapse_button_mic_tetser">
                    Cách Cài Đặt Kiểm Tra Mic Và Scan Lấy ID Mic:
                  </h5>
                  <div id="collapse_button_mic_tetser" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_mic_tetser">
                    - Đầu tiên chạy lệnh sau để di chuyển tới thư mục <b>test_device</b>:<br />
                    <b>$:> cd /home/pi/VBot_Offline/resource/test_device</b><br />
                    - Chạy file <b>Scan_Mic.py</b> để tiến hành liệt kê mic và ID<br />
                    <b>$:> python3 Scan_Mic.py</b><br />
                    - Trên giao diện Terminal sẽ hiển thị các ID và Tên Tên Thiết Bị tương ứng<br />
                    - Thay lần lượt ID vừa scan đó vào trong file <b>Test_Mic.py</b> (ở dòng số 12 có giá trị <b>device_index=14</b>, hãy thay số 14 thành ID mic của bạn)<br />
                    - Sau đó chạy file <b>Test_Mic.py</b> để kiểm tra Mic có đúng và hoạt động không:<br />
                    <b>$:> python3 Test_Mic.py</b><br />
                    - File đó sẽ thu âm trong khoảng 6 giây, bạn cần nói vào Mic khi thu âm xong sẽ xuất ra file <b>Test_Microphone.wav</b><br />
                    - Bạn cần mở file đó và nghe xem có âm thanh được thu không hoặc chạy lệnh sau:<br />
                    <b>$:> vlc Test_Microphone.wav</b><br />
                    - Nếu không được bạn cần thử lần lượt các ID được scan và kiểm tra lại driver được cài tương ứng với MIC của bạn chưa<br />
                    - Nếu thành công bạn hãy điền ID Mic đó vào trong cấu hình Config rồi lưu Config lại là được
                    <br /><br />
                    <b>Thay đổi âm lượng Mic/Microphone:</b><br />
                    <b>- B1:</b> Chạy lệnh sau: <b>$:> alsamixer</b> -> nhấn phím <b> Tab hoặc F4</b> tìm tới tên thiết bị (Tùy từng kiểu loại Mic, Driver): ví dụ với Mạch AIO tên là: <b>Captue</b> chỉnh khoảng 35 là trung bình, trong quá trình sử dụng nếu thấy nhạy quá cần chính xuống thấp hơn<br />
                    <b>- B2:</b> Di chuyển tới Tab: <b>Command/Terminal</b> -> <b>VM8960-SoundCard</b> -> <b>Save Alsamixer To VM8960 SoundCard Driver</b><br /><br />
                    <b>+ Hoặc Thao Tác Thay Đổi Âm Lượng Mic Thủ Công:</b><br />
                    <b>- B1:</b> Chạy lệnh sau: <b>$:> alsamixer</b> -> nhấn phím <b> Tab hoặc F4</b> tìm tới tên thiết bị (Tùy từng kiểu loại Mic, Driver): ví dụ với Mạch AIO tên là: <b>Captue</b> chỉnh khoảng 35 là trung bình, trong quá trình sử dụng nếu thấy nhạy quá cần chính xuống thấp hơn
                    <br /><b>- B2:</b> Chạy Lệnh sau để lưu cấu hình alsamixer: <b>$:> sudo alsactl store</b><br />
                    <b>- B3:</b> Sao Lưu Lại Cấu Hình Gốc m8960-soundcard: <b>$:> sudo mv /etc/wm8960-soundcard/wm8960_asound.state /etc/wm8960-soundcard/wm8960_asound_default.state</b><br />
                    <b>- B4:</b>Chạy lệnh sau để sao chép tệp cấu hình alsamixer đã lưu ở <b>B2</b> vào hệ thống driver m8960-soundcard: <b>$:> sudo cp /var/lib/alsa/asound.state /etc/wm8960-soundcard/wm8960_asound.state</b>
                    <br /><br />
                    - Lưu Ý: Nếu sử dụng Mic i2s: <b>INMP441</b> kếp hợp với <b>MAX9857</b> thì bạn cần Flash <b>'IMG VBot I2S'</b> và thiết lập ID MIC Bắt Buộc là: '<b>-1</b>' Nhé
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_media_player_source">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player_source" aria-expanded="false" aria-controls="collapse_button_media_player_source">
                    Hướng Dẫn Cài Đặt, Kiểm Tra Loa, Âm Thanh Đầu Ra:
                  </h5>
                  <div id="collapse_button_media_player_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_media_player_source">
                    - Nếu Là Mạch DAC i2s có thể tham khảo cách cài Driver theo Link Sau:<br />
                    - <a href="https://drive.google.com/drive/folders/1KJIuovEbRGv82uc5FCfi5p0sY1o5W5vU" target="_blank">https://drive.google.com/drive/folders/1KJIuovEbRGv82uc5FCfi5p0sY1o5W5vU</a>
                    <br /><br />
                    - Bạn cần phát 1 file âm thanh bằng VLC, có thể tải lên file âm thanh của bạn như XXX.mp3 vào <b>/home/pi</b> chẳng hạn<br />
                    - Tiếp tới hãy chạy file âm thanh đó bằng lệnh sau:<br />
                    <b>$:> vlc XXX.mp3</b><br />
                    - Nếu có âm thanh được phát ra là xong, không cần cấu hình gì nữa cả<br />
                    - Nếu không có âm thanh được phát ra bạn cần cài cấu hình và sét đầu ra âm thanh mặc định trong <b>alsamixer</b> (tùy mỗi thiết bị mà có cấu hình khác nhau)<br />
                    - Sau đó bạn cần chạy lệnh <b>$:> alsamixer</b> và xác định xem thiết bị đó có tên là gì<br />
                    - Khi đã xác định được tên thiết bị bạn cần điền tên đó vào trong tab <b>Cấu Hình config</b>: <b>Tên thiết bị (alsamixer)</b> (được dùng để sét âm lượng đầu tiên khi chạy chương trình, trong quá trình chạy VBot sẽ chỉ thay đổi âm lượng cửa vlc mà không ảnh hưởng gì tới âm lượng trên hệ thống của bạn)
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_1">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_1" aria-expanded="false">
                    Nâng Cấp Full Dung Lượng Cho Thẻ Nhớ:
                  </h5>
                  <div id="collapse_button_1" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_1">
                    - Đăng nhập vào ssh rồi gõ lệnh sau:<br />
                    $: <b>sudo raspi-config</b><br />
                    - Chọn: <b>(6)Advance Options</b> -> <b>(A1)Expand File System</b> đợi vài giây -> <b>OK</b> -> <b>Fish</b> -> <b>Yes</b> để rebot
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_2">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_2" aria-expanded="false">
                    Thay Đổi Đường Dẫn (Path) Của Apache2:
                  </h5>
                  <div id="collapse_button_2" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_2">
                    <font color=red>Thay Đổi Tự Động:</font><br />
                    Di chuyển tới thư mục sau bằng lệnh:<br />
                    $:> <b>cd /home/pi/VBot_Offline/resource/test_device/</b><br /><br />
                    - Chạy file Change_Path_Apache2.py bằng quyền sudo bằng lệnh:<br />
                    $:> <b>sudo python3 Change_Path_Apache2.py</b><br /><br />
                    - Hệ thống sẽ yêu cầu nhập đường dẫn path trỏ tới WebUI mới, bạn hãy nhập dòng sau và nhấn Enter là xong:<br />
                    <b>/home/pi/VBot_Offline/html</b>
                    <hr />
                    <font color=red>Nếu bạn muốn thay đổi bằng tay</font><br />
                    - Đăng nhập vào ssh rồi gõ lệnh sau:<br /><br />
                    $: <b>sudo nano /etc/apache2/sites-available/000-default.conf</b><br />
                    - Thay dòng: <b>DocumentRoot /home/pi/VBot_Offline/html</b> thành đường dẫn muốn đổi, ví dụ thay thành: <b>DocumentRoot /var/www/html</b>
                    <br />- lưu lại file: <b>Ctrl + x => y => Enter</b><br /><br />
                    - Tiếp theo chạy lệnh:<br />
                    $: <b>sudo nano /etc/apache2/apache2.conf</b><br />
                    - Thay dòng: <b>Directory /home/pi/VBot_Offline/ </b> thành: <b>Directory /var/www/html/</b><br /><br />
                    - Sau đó restart lại appache2 bằng lệnh sau:<br />
                    $: <b>sudo systemctl restart apache2</b>
                  </div>
                </div>
              </div>
            <div class="card accordion" id="accordion_button_Cloudflare_Tunnel">
            <div class="card-body">
            <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_Cloudflare_Tunnel" aria-expanded="false" aria-controls="collapse_button_Cloudflare_Tunnel">
            Truy Cập bằng Domain, Tên Miền: (DNS, NAT & Forwarding - Cloudflare Tunnel)</h5>
            <div id="collapse_button_Cloudflare_Tunnel" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_Cloudflare_Tunnel">
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <h3 class="mb-0">HƯỚNG DẪN CÀI CLOUDFLARED TUNNEL TRÊN RASPBERRY PI ZERO 2W 32BIT (ARM)</h3>
        </div>
		
        <div class="card-body">
			<h5 class="border-bottom border-primary pb-2 mt-4">0. Yêu Cầu: Cần Truy Cập Login SSH Để Thực Thi Các Lệnh</h5>
            <h5 class="border-bottom border-primary pb-2 mt-4">1. Tải và cài đặt Cloudflared Cho RASPBERRY PI ZERO 2W 32 bit (ARM)</h5>
            <div class="bg-dark rounded p-3 code-block text-light">
                <span class="cmd">$</span> wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-armhf.deb<br><br>
                <span class="cmd">$</span> sudo dpkg -i cloudflared-linux-armhf.deb
            </div>
            <small class="text-muted">Output mẫu:</small>
            <div class="bg-black rounded p-3 output-block text-light small">
                Selecting previously unselected package cloudflared...<br>
                Unpacking cloudflared (2025.11.1) ...<br>
                Setting up cloudflared (2025.11.1) ...
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">2. Kiểm tra phiên bản Cloudflare Tunnel </h5>
            <div class="bg-dark rounded p-3 code-block text-light">
                <span class="cmd">$</span> cloudflared --version
            </div>
            <div class="bg-black rounded p-3 output-block text-light small">
                cloudflared version 2025.11.1 (built 2025-11-07-16:59 UTC)
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">3. Đăng nhập Cloudflare (lấy cert)</h5>
            <div class="bg-dark rounded p-3 code-block text-light">
                <span class="cmd">$</span> cloudflared tunnel login
            </div>
            <div class="alert alert-info small mt-2">
                Mở link hiện ra trên trình duyệt → đăng nhập Cloudflare → chọn domain đã được liên kết với cloudflared (ví dụ domain của mình đã liên kết là: vutuyen.com)
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">4. Tạo tunnel (ví dụ tên: vbot_domain_tunnel)</h5>
            <div class="bg-dark rounded p-3 code-block text-light">
                <span class="cmd">$</span> cloudflared tunnel create vbot_domain_tunnel
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">5. Gắn subdomain vào tunnel</h5>
            <div class="bg-dark rounded p-3 code-block text-light">
                <span class="cmd">$</span> cloudflared tunnel route dns vbot_domain_tunnel vbot.vutuyen.com
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">6. Tạo thư mục và file config</h5>
            <div class="bg-dark rounded p-3 code-block text-light mb-2">
                <span class="cmd">$</span> mkdir -p /home/pi/Cloud_Flare<br>
                <span class="cmd">$</span> chmod 0777 /home/pi/Cloud_Flare<br>
                <span class="cmd">$</span> nano /home/pi/Cloud_Flare/config.yml<br/>
            </div>
			<small class="text-muted">Nội Dung File config.yml Bên Dưới, Thay Đổi Giá Trị Cho Phù Hợp:</small>
			<small class="text-muted">Cần thay đổi giá trị của: <b>hostname</b> và <b>credentials-file</b> :</small>
            <div class="bg-secondary rounded p-3 text-light small">
                tunnel: vbot_domain_tunnel<br>
                credentials-file: /home/pi/.cloudflared/000f2d49-5f13-449d-bd50-b1692156562c.json<br><br>
                ingress:<br>
                &nbsp;&nbsp;- hostname: vbot.vutuyen.com<br>
                &nbsp;&nbsp;&nbsp;&nbsp;service: http://localhost:80<br>
                &nbsp;&nbsp;- service: http_status:404
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">7. Chạy thử thủ công</h5>
            <div class="bg-dark rounded p-3 code-block text-light">
                <span class="cmd">$</span> cloudflared tunnel --config /home/pi/Cloud_Flare/config.yml run vbot_domain_tunnel
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">8. Cài đặt chạy tự động (systemd service)</h5>
            <div class="bg-dark rounded p-3 code-block text-light">
                <span class="cmd">$</span> sudo ln -s /home/pi/Cloud_Flare/config.yml /etc/cloudflared/config.yml<br>
                <span class="cmd">$</span> sudo cloudflared service install<br>
                <span class="cmd">$</span> sudo systemctl enable cloudflared<br>
                <span class="cmd">$</span> sudo systemctl start cloudflared
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">9. Quản lý service</h5>
            <div class="row row-cols-1 row-cols-md-2 g-3">
                <div class="col">
                    <div class="bg-dark rounded p-3 code-block text-light small">
                        <span class="cmd">$</span> sudo systemctl stop cloudflared
                    </div>
                    <small class="text-muted">Dừng tạm thời</small>
                </div>
                <div class="col">
                    <div class="bg-dark rounded p-3 code-block text-light small">
                        <span class="cmd">$</span> sudo systemctl disable cloudflared
                    </div>
                    <small class="text-muted">Tắt khởi động cùng hệ thống</small>
                </div>
                <div class="col">
                    <div class="bg-dark rounded p-3 code-block text-light small">
                        <span class="cmd">$</span> systemctl status cloudflared
                    </div>
                    <small>Kiểm tra trạng thái</small>
                </div>
				<!-- 
                <div class="col">
                    <div class="bg-dark rounded p-3 code-block text-light small">
                        <span class="cmd">$</span> sudo cloudflared service install --config /home/pi/Cloud_Flare/config.yml
                    </div>
                    <small>Cài lại service có config</small>
                </div>
				-->
                <div class="col">
                    <div class="bg-dark rounded p-3 code-block text-light small">
                        <span class="cmd">$</span> sudo systemctl enable cloudflared
                    </div>
                    <small>Bật khởi động cùng hệ thống</small>
                </div>
				
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">10. Xóa hoàn toàn service</h5>
            <div class="bg-dark rounded p-3 code-block text-light">
                <span class="cmd">$</span> sudo systemctl stop cloudflared<br>
                <span class="cmd">$</span> sudo systemctl disable cloudflared<br>
                <span class="cmd">$</span> sudo rm /etc/systemd/system/cloudflared.service<br>
                <span class="cmd">$</span> sudo systemctl daemon-reload
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">11. Xem danh sách & thông tin tunnel</h5>
            <div class="bg-dark rounded p-3 code-block text-light mb-2">
                <span class="cmd">$</span> cloudflared tunnel list<br>
                <span class="cmd">$</span> cloudflared tunnel info vbot_domain_tunnel
            </div>
            <h5 class="border-bottom border-primary pb-2 mt-4">12. Xóa tunnel hoàn toàn trên Cloudflare</h5>
            <div class="bg-dark rounded p-3 code-block text-light mb-2">
                <span class="cmd">$</span> cloudflared tunnel list<br>
                <span class="cmd">$</span> cloudflared tunnel delete "tunnel_name"
            </div>
            <div class="alert alert-warning mt-4">
                <strong>Lưu ý quan trọng:</strong> Giữ bí mật file <code class="path">~/.cloudflared/*.json</code> – nếu lộ sẽ bị chiếm quyền điều khiển tunnel!
            </div>
        </div>
    </div>
            </div>
            </div>
            </div>
              <div class="card accordion" id="accordion_button_3">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_3" aria-expanded="false">
                    Tăng Giới Hạn Tải Lên File Trên WebUI:
                  </h5>
                  <div id="collapse_button_3" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_3">
                    - Chạy 2 lệnh sau:<br />
                    $: <b>sudo nano sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 300M/' /etc/php/7.4/apache2/php.ini</b><br />
                    $: <b>sudo sed -i 's/post_max_size = .*/post_max_size = 350M/' /etc/php/7.4/apache2/php.ini</b><br /><br />
                    - Lưu Ý: <b>Bạn cần chỉnh sửa đường dẫn /etc/php/7.4/apache2/php.ini trong câu lệnh cho phù hợp với phiên bản và đường dẫn file php.ini của bạn</b>
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_4">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_4" aria-expanded="false">
                    Đăng Nhập, Đặt Mật Khẩu, Quên Mật Khẩu WebUI:
                  </h5>
                  <div id="collapse_button_4" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_4">
                    - Bật Đăng Nhập Vào WebUI:<br />
                    - Thao Tác: Nhấn vào Avatar trên cùng bên phải chọn: <b>Cá nhân => Cài đặt => Đăng nhập Web UI => (Bật Lên Là Được)</b><br /><br />
                    - Đặt Mật Khẩu Đăng Nhập WebUI:<br />
                    - Thao Tác: Nhấn vào Avatar trên cùng bên phải chọn: <b>Cá nhân => Chỉnh sửa hồ sơ => Mật khẩu Web UI => (Điền Mật Khẩu Của Bạn)</b><br />
                    - Lưu Ý: <b>Bạn cần nhập cả địa chỉ Email để dùng cho trường hợp Quên Mật Khẩu</b><br /><br />
                    <b>- Quên Mật Khẩu và Đổi Mật Khẩu Cũng Nằm Trên Thanh Tác Vụ, Tương Tự Thao Tác Như Trên.</b><br />
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_5">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_5" aria-expanded="false">
                    Nút Nhấn và Hành Động Của Nút:
                  </h5>
                  <div id="collapse_button_5" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_5">

                    <div class="card accordion" id="accordion_button_faq_nut_nhan_thuong">
                      <div class="card-body">
                        <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_faq_nut_nhan_thuong" aria-expanded="false" aria-controls="collapse_button_faq_nut_nhan_thuong">
                          Nút Nhấn Thường (Nhấn Nhả):</h5>
                        <div id="collapse_button_faq_nut_nhan_thuong" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_faq_nut_nhan_thuong">
                          <ul>
                            <li>
                              <b>Nút nhấn up</b>
                              <ul>
                                <li>
                                  Nhấn Nhả:
                                  <ul>
                                    <li>Tăng âm lượng (Tăng theo bước nhấn, được thiết lập trong Config.json)</li>
                                  </ul>
                                </li>
                                <li>
                                  Nhấn Giữ:
                                  <ul>
                                    <li>Tăng âm lượng cao nhất (Giá trị cao nhất được thiết lập trong Config.json)</li>
                                  </ul>
                                </li>
                              </ul>
                            </li>
                            <br />
                            <li>
                              <b>Nút nhấn down</b>
                              <ul>
                                <li>
                                  Nhấn Nhả:
                                  <ul>
                                    <li>Giảm âm lượng (Giảm theo bước nhấn, được thiết lập trong Config.json)</li>
                                  </ul>
                                </li>
                                <li>
                                  Nhấn Giữ:
                                  <ul>
                                    <li>Giảm âm lượng xuống thấp nhất (Giá trị thấp nhất được thiết lập trong Config.json)</li>
                                  </ul>
                                </li>
                              </ul>
                            </li>
                            <br />
                            <li>
                              <b>Nút nhấn mic</b>
                              <ul>
                                <li>
                                  <font color="green">Ở Trạng Thái Chờ Được Đánh Thức:</font>
                                  <ul>
                                    <font color="blue">
                                      <li>
                                        Nhấn Nhả:
                                        <ul>
                                          <li>Bật, Tắt Microphone</li>
                                        </ul>
                                      </li>
                                      <li>
                                        Nhấn Giữ:
                                        <ul>
                                          <li>Bật, Tắt chế độ câu phản hồi</li>
                                        </ul>
                                      </li>
                                    </font>
                                  </ul>
                                </li>
                                <li>
                                  <font color="green">Ở Trạng Thái Đang Phát Nhạc (Media Player):</font>
                                  <ul>
                                    <font color="blue">
                                      <li>
                                        Nhấn Nhả:
                                        <ul>
                                          <li>Tạm Dừng hoặc Tiếp Tục phát nhạc</li>
                                        </ul>
                                      </li>
                                      <li>
                                        Nhấn Giữ:
                                        <ul>
                                          <li>Dừng phát nhạc</li>
                                        </ul>
                                      </li>
                                    </font>
                                  </ul>
                                </li>
                                <li>
                                  <font color="green">Ở Trạng Thái Đang Phát Câu Trả Lời, TTS:</font>
                                  <ul>
                                    <font color="blue">
                                      <li>
                                        Nhấn Nhả + Nhấn Giữ:
                                        <ul>
                                          <li>Dừng Phát Câu Trả Lời, TTS</li>
                                        </ul>
                                      </li>
                                    </font>
                                  </ul>
                                </li>
                              </ul>
                            </li>
                            <br />
                            <li>
                              <b>Nút nhấn Wake Up</b>
                              <ul>
                                <li>
                                  <font color="green">Ở Trạng Thái Chờ Được Đánh Thức:</font>
                                  <ul>
                                    <font color="blue">
                                      <li>
                                        Nhấn Nhả:
                                        <ul>
                                          <li>Đánh Thức Bot, Wake Up</li>
                                        </ul>
                                      </li>
                                      <li>
                                        Nhấn Giữ:
                                        <ul>
                                          <li>Bật, Tắt chế độ hội thoại (hỏi đáp liên tục)</li>
                                        </ul>
                                      </li>
                                    </font>
                                  </ul>
                                </li>
                                <li>
                                  <font color="green">Ở Trạng Thái Đang Phát Nhạc (Media Player):</font>
                                  <ul>
                                    <font color="blue">
                                      <li>
                                        Nhấn Nhả:
                                        <ul>
                                          <li>Tạm Dừng phát nhạc đồng thời đánh thức Bot (Wake Up) để nghe lệnh</li>
                                        </ul>
                                      </li>
                                      <li>
                                        Nhấn Giữ:
                                        <ul>
                                          <li>Dừng Phát Nhạc</li>
                                        </ul>
                                      </li>
                                    </font>
                                  </ul>
                                </li>
                                <li>
                                  <font color="green">Ở Trạng Thái Đang Phát Câu Trả Lời, TTS:</font>
                                  <ul>
                                    <font color="blue">
                                      <li>
                                        Nhấn Nhả + Nhấn Giữ:
                                        <ul>
                                          <li>Dừng Phát Câu Trả Lời, TTS</li>
                                        </ul>
                                      </li>
                                    </font>
                                  </ul>
                                </li>
                              </ul>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>


                    <div class="card accordion" id="accordion_button_faq_nut_nhan_encoder">
                      <div class="card-body">
                        <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_faq_nut_nhan_encoder" aria-expanded="false" aria-controls="collapse_button_faq_nut_nhan_encoder">
                          Nút Nhấn Xoay Encoder:</h5>
                        <div id="collapse_button_faq_nut_nhan_encoder" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_faq_nut_nhan_encoder">
                          Chức năng được lựa chọn trong Phần: Cấu Hình Config -> Cấu Hình Nút Nhấn
                        </div>
                      </div>
                    </div>


                  </div>
                </div>
              </div>

              <div class="card accordion" id="accordion_button_6">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_6" aria-expanded="false">
                    Cấu Hình Auto/Tự Động Chạy VBot Cùng Hệ Thống:
                  </h5>
                  <div id="collapse_button_6" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_6">
                    - Di chuyển tới Tab: <b>Command/Terminal</b><br />
                    - Chọn vào: <b>VBot Auto Run => Cài đặt cấu hình Auto</b> (Hệ thống sẽ tự động tạo và cài đặt file cấu hình VBot để khởi động cùng hệ thống)<br /><br />
                    - Cài đặt xong, tiếp tục chọn vào: <b>VBot Auto Run => Kích Hoạt</b> (Hệ thống sẽ tự động khởi chạy Chương Trình VBot khi thiết bị khởi động xong)<br /><br />
                    - Các tùy chọn điều khiển khác liên quan tới Auto khởi động cũng sẽ nằm trong: <b>Command/Terminal => VBot Auto Run</b>
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_7">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_7" aria-expanded="false">
                    Cấu Hình Auto Kết Nối Wifi hoặc Tạo Điểm Truy Cập AP :
                  </h5>
                  <div id="collapse_button_7" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_7">
                    - Truy Cập Tab: <b>Command/Terminal</b> -> <b>OS Wifi</b>
                    <br />
                    <b>- Restart Auto Wifi Manager:</b> Khởi động lại Services Auto Wifi Manaager đang chạy trên hệ thống<br />
                    <b>- Enable Auto Wifi Manager:</b> Kích Hoạt Services Auto Wifi Manaager trên hệ thống (Mặc định là đã kích hoạt từ trước rồi)<br />
                    <b>- Install Auto Wifi Manager:</b> Chỉ Cài Đặt Auto Wifi Manager Và Tạo Điểm truy Cập AP, Tự động kết nối lại khi mất mạng hoặc hệ thống mạng khởi động sau Vbot<br />
                    <b>- Install Auto Wifi Manager + Đọc IP:</b> Cài Đặt Auto Wifi Manager + Đọc Địa Chỉ IP Ra Loa Và Tạo Điểm truy Cập AP,
                    Tự động kết nối lại khi mất mạng hoặc hệ thống mạng khởi động sau Vbot,
                    Nếu phát hiện địa chỉ IP Local bị thay đổi hoặc mạng wifi trước đo bị thay đổi sẽ tự đọc địa chỉ ip local mới<br />
                    <b>- Logs Auto Wifi Manager:</b> Kiểm Tra Logs Quá Trình Hoạt Động<br />
                    <b>- Status Auto Wifi Manager:</b> Kiểm tra trạng thái xem là: đang hoạt động hay không<br />
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_oled_i2c">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_oled_i2c" aria-expanded="false" aria-controls="collapse_button_oled_i2c">
                    Kết Nối Màn Hình I2C:
                  </h5>
                  <div id="collapse_button_oled_i2c" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_oled_i2c">
                    Loại Màn Hình Đang Được Hỗ Trợ: <b>OLED 128X64 0.96 INCH 1306 giao tiếp I2C</b><br />
                    Sơ Đồ Kết Nối Chân Pin Với GPIO (Loại Giao Tiếp i2c 4 chân Pin):<br />
                    <b>
                      - SDA ==> GPIO2 (Pin 3)<br />
                      - SCL ==> GPIO3 (Pin 5)<br />
                      - VCC ==> 3.3V (Pin 1)<br />
                      - GND ==> GND(Pin 14)<br />
                    </b>
                    Hình Ảnh Sơ Đồ Chân Kết Nối: <a href="https://github.com/user-attachments/assets/655e0b0a-4891-4a6f-aab8-19c5feb139ed" target="_blank"> Xem Ảnh Kết Nối</a><br />
                    Khi kết nối xong các chân cần chạy lệnh sau để kiểm tra xem màn hình đã được nhận diện với địa chỉ 3c chưa:<br />
                    <b>$:> sudo i2cdetect -y 1</b><br />
                    Nếu nhận diện thành công sẽ có địa chỉ 3c như ảnh: <a href="https://github.com/user-attachments/assets/7aa88c10-7763-422d-ac48-1ad31339fe6f" target="_blank">Xem Ảnh</a>
                    <br />
                    Tài Liệu Tham Khảo:<br />
                    - <a href="https://github.com/adafruit/Adafruit_Python_SSD1306" target="_blank">https://github.com/adafruit/Adafruit_Python_SSD1306</a><br />
                    - <a href="https://www.the-diy-life.com/add-an-oled-stats-display-to-raspberry-pi-os-bullseye/" target="_blank">https://www.the-diy-life.com/add-an-oled-stats-display-to-raspberry-pi-os-bullseye/</a>
                    - <a href="https://www.instructables.com/Raspberry-Pi-Monitoring-System-Via-OLED-Display-Mo/" target="_blank">https://www.instructables.com/Raspberry-Pi-Monitoring-System-Via-OLED-Display-Mo/</a>
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_tao_gcloud_driver_json">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_tao_gcloud_driver_json" aria-expanded="false" aria-controls="collapse_button_tao_gcloud_driver_json">
                    Tạo Tệp Json Google Driver Backup:
                  </h5>
                  <div id="collapse_button_tao_gcloud_driver_json" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_tao_gcloud_driver_json">
                    <a href="https://docs.google.com/document/d/1-VTi9MOAgQoR8jZrhN9FlZxjWsq2vDuy/edit?usp=drive_link&ouid=106149318613102395200&rtpof=true&sd=true" target="_blank">Nhấn Để Mở File Hướng Dẫn Tạo Json GDriver</a>
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_add_vbot_assist_hass">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_add_vbot_assist_hass" aria-expanded="false" aria-controls="collapse_button_add_vbot_assist_hass">
                    Liên Kết VBot Vào Hass - Home Assistant:
                  </h5>
                  <div id="collapse_button_add_vbot_assist_hass" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_add_vbot_assist_hass">
                    Truy Cập Tài Liệu: <a href="https://github.com/marion001/VBot_Offline_Custom_Component" target="_blank">https://github.com/marion001/VBot_Offline_Custom_Component</a>
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_train_hotword_snowboy">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_train_hotword_snowboy" aria-expanded="false" aria-controls="collapse_button_train_hotword_snowboy">
                    Hướng Dẫn Train Hotword Snowboy, Cài Thư Viện Snowboy:
                  </h5>
                  <div id="collapse_button_train_hotword_snowboy" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_train_hotword_snowboy">
                    <b>- Cài Thư Viện Snowboy:</b> <br />
                    <b>- Auto Tự Động Cài Đặt:</b> Truy Cập Vào SSH Và Chạy Lệnh Sau Trong Console<br />
                    <b>$:> sudo python3 /home/pi/VBot_Offline/resource/snowboy/Auto_install.py</b><br /><br />
                    <b>- Nếu Cài Đặt Thủ Công:</b>
                    Chạy Lần Lượt Các Lệnh Sau<br />
                    <b>
                      $:> cd ~<br />
                      $:> sudo apt update<br />
                      $:> sudo apt install -y swig<br />
                      $:> sudo apt install -y libatlas-base-dev liblapack-dev libblas-dev libopenblas-dev<br />
                      $:> wget https://www.piwheels.org/simple/scipy/scipy-1.13.1-cp39-cp39-linux_armv7l.whl<br />
                      $:> pip install scipy-1.13.1-cp39-cp39-linux_armv7l.whl<br />
                      $:> git clone https://github.com/seasalt-ai/snowboy.git<br />
                      $:> cd ~/snowboy<br />
                      $:> cd /home/pi/snowboy/swig/Python3<br />
                      $:> make</br>
                      $:> sudo python3 /home/pi/snowboy/setup.py install<br />
                      $:> sudo cp /home/pi/VBot_Offline/resource/snowboy/snowboydetect.py /usr/local/lib/python3.9/dist-packages/snowboy-1.3.0-py3.9.egg/snowboy/<br />
                      $:> sudo cp /home/pi/VBot_Offline/resource/snowboy/_snowboydetect.so /usr/local/lib/python3.9/dist-packages/snowboy-1.3.0-py3.9.egg/snowboy/<br />
                      $:> sudo cp /home/pi/VBot_Offline/resource/snowboy/common.res /usr/local/lib/python3.9/dist-packages/snowboy-1.3.0-py3.9.egg/snowboy/</b>
                    <hr />
                    <b>- Train Hotword:</b> Lần Lượt Các Bước Sau<br />
                    - <b>Lưu Ý: Cần Sử dụng 1 thiết bị khác không chạy Vbot để Train</b><br /><br>
                    B1: <b>Cài Docker trên thiết bị (Cách cài các bạn tham khảo trên google nhé)</b><br /><br />
                    B2: Chạy lệnh sau: <b>$:> sudo docker pull rhasspy/snowboy-seasalt</b><br /><br />
                    B3: Chạy tiếp lệnh sau: <b>$:> docker run -it -p 8899:8000 rhasspy/snowboy-seasalt</b><br /><br />
                    B4: Docker sẽ khởi chạy rhasspy/snowboy-seasalt, hãy kiểm tra xem đã chạy thành công chưa bằng cách truy cập: http://ip:8899<br />
                    nếu cập được vào và hiển thị giao diện để Train là OK<br /><br />
                    B5: Cần Stop, không cho chạy Vbot. Sau đó Truy cập vào SHH với thiết bị chạy VBot rồi chạy lệnh sau:<br />
                    - <b>$:> cd /home/pi/VBot_Offline/resource/test_device</b><br /><br />
                    B6: Mở file <b>Trail_Hotword_Snowboy.py</b> điền địa chỉ ip của thiết bị chạy Docker rhasspy/snowboy-seasalt là dòng số 14 là: <b>server_url = "http://192.168.14.17:8899"</b> thay địa chỉ ip vào, xong lưu lại file<br /><br />
                    B7: Chạy file <b>Trail_Hotword_Snowboy.py</b> bằng lệnh: <b>$:> python3 Trail_Hotword_Snowboy.py</b> nhập tên file hotword cần tạo và nói vào Microphone lần lượt 7 lần<br /><br />
                    - Hoàn tất Train sẽ xuất hiện file <b>.pmdl</b> với tên bạn tạo. Sao chép file đó vào đường dẫn: <b>/home/pi/VBot_Offline/resource/snowboy/hotword</b> hoặc sử dụng Giao Diện để tải lên file<br />
                    Nguồn Tham khảo: <a href="https://github.com/rhasspy/snowboy-seasalt" target="_blank">https://github.com/rhasspy/snowboy-seasalt</a>
                  </div>
                </div>
              </div>

              <div class="card accordion" id="accordion_button_socket_streaming_audio_server">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_socket_streaming_audio_server" aria-expanded="false" aria-controls="collapse_button_socket_streaming_audio_server">
                    Socket Server Streaming Audio:</h5>
                  <div id="collapse_button_socket_streaming_audio_server" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_socket_streaming_audio_server">
                    - Bạn có thể dùng các thiết bị để làm Client như ESP32, Raspberry Pi, Máy Tính, V..v....<br />
                    - Client Cần Đọc Dữ Liệu Âm Thanh Từ Microphone Và Streaming Trực Tiếp Tới Server Chạy VBot<br /><br /><br />
                    <hr />
                    <h5>Nếu Lựa CHọn ESP32, ESP32s3, ESP32 D1 Mini</h5>
                    - Truy Cập Vào Github Sau: <a href="https://github.com/marion001/VBot_Client_Offline" target="_blank">https://github.com/marion001/VBot_Client_Offline</a>
                    Hỗ trợ phát âm thanh .mp3 qua api (Chỉ dùng với cùng lớp mạng nội bộ Local, URL là dạng http, không hỗ trợ https):<br /><br />
                    - Demo CURL phát âm thanh:<br />
                    <pre class="text-danger">
	curl -X POST http://192.168.14.80/play_audio -d "url=http://192.168.14.17/1.mp3"
	</pre>
                    - DEMO CURL dừng phát âm thanh:<br />
                    <pre class="text-danger">
	curl http://192.168.14.80/stop_audio
	</pre>
                    - DEMO CURL Restart ESP:<br />
                    <pre class="text-danger">
	curl -X POST http://192.168.14.80/restart
		</pre>
                    - DEMO CURL Reset Wifi:<br />
                    <pre class="text-danger">
	curl -X POST http://192.168.14.80/resetwifi
	</pre>
                    - DEMO CURL xóa, đặt lại toàn bộ dữ liệu về mặc định:<br />
                    <pre class="text-danger">
	curl -X POST http://192.168.14.80/cleanNVS
	</pre>
                  </div>
                </div>
              </div>

              <div class="card accordion" id="accordion_button_socket_streaming_audio_server">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#OK_webui_external" aria-expanded="false" aria-controls="OK_webui_external">
                    Cho Phép Truy Cập Giao Diện WebUI Bên Ngoài Internet:</h5>
                  <div id="OK_webui_external" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#OK_webui_external">
                    - B1: Cần Bật trong tab: <b>Cấu Hình Config -> Web Interface (Giao Diện) -> Cho Phép Truy Cập Bên Ngoài Internet -> Lưu Cấu Hình</b><br />
                    - B2: Cần kích hoạt lần đầu trong Tab: <b>Command/Terminal -> WebUI External -> Kích Hoạt WebUI Ra Internet</b><br />
                    - B3: Sau đó Reboot lại hệ thống hoặc Restart lại Apache2 để áp dụng thay đổi<br /><br />
                    - Bạn có thể trỏ Tên Miền, Domain, DNS, thông qua Modem, Route, VPN, V..v... về địa chỉ ip Local của thiết bị này<br /><br />
                    - Để đảm bảo an toàn khi truy cập bên ngoài Internet bạn nên kích hoạt mật khẩu đăng nhập WebUI và đổi mật khẩu mặc định: <b>Cá Nhân -> Cài Đặt -> Bật Đăng Nhập WebUI</b>
                  </div>
                </div>
              </div>

              <div class="card accordion" id="accordion_button_socket_streaming_audio_server">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#vbot_on_speaker_guide" aria-expanded="false" aria-controls="vbot_on_speaker_guide">
                    Ra lệnh điều khiển, tương tác giữa các loa chạy VBot trong cùng lớp mạng Lan:</h5>
                  <div id="vbot_on_speaker_guide" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#vbot_on_speaker_guide">
                    - B1: Trong Giao Diện WebUI Cần <b>Quét Để Tìm Kiếm Các Thiết Bị Chạy VBot Trong Cùng Lớp Mạng Lan</b><br />
                    - B2: Đặt tên định danh (là duy nhất) cho Loa của bạn: <b>Cá Nhân -> Chỉnh Sửa Hồ Sơ -> Tên</b> Ví dụ đặt là: (<b>Loa Phòng Ngủ</b> hoặc <b>Phòng Ngủ</b>)<br /><br />
                    - B3: Câu lệnh để từ Loa VBot1 thực thi trên loa VBot2 trong file: <b>Adverbs.json -> on_speaker</b><br /><br />
                    - B4:<br />
                    Ví Dụ Câu Lệnh 1: Phát danh sách nhạc trên loa phòng ngủ<br />
                    Ví Dụ Câu Lệnh 2: Bật/Tắt Mic trên loa phòng ngủ<br />
                    Ví Dụ Câu Lệnh 3: Bật/Tắt chế độ hội thoại ở loa phòng ngủ<br />
                    Ví Dụ Câu Lệnh 4: phát bài hát [Tên Bài Hát] trên loa phòng ngủ<br />
                    Ví Dụ Câu Lệnh 5: [kể truyện, đọc báo, tin tức] [Tên Truyện, Tên Báo, Tin tức] trên loa phòng ngủ<br />
                    Ví Dụ Câu Lệnh 6: [dừng nhạc, tiếp tục, tạm dừng] trên loa phòng ngủ<br />
                  </div>
                </div>
              </div>


            </div>
          </div>
    </section>
  </main>
  <!-- ======= Footer ======= -->
  <?php
  include 'html_footer.php';
  ?>
  <!-- End Footer -->
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <!-- Nghe thử file âm thanh 
      <audio id="audioPlayer" style="display: none;" controls></audio>-->
  <!-- Template Main JS File -->
  <?php
  include 'html_js.php';
  ?>
</body>

</html>