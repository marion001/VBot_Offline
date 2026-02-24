<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

if ($Config['contact_info']['user_login']['active']) {
  session_start();
  if (
    !isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
  ) {
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
  }
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
<hr/>Hướng Dẫn Flash Chương Trình Vào Thẻ Nhớ: <a href="https://docs.google.com/document/d/1hCE1206JfP0bvoL7BKOAPvLkTM5JrrfS/edit" target="_blank">https://docs.google.com/document/d/1hCE1206JfP0bvoL7BKOAPvLkTM5JrrfS</a>
<hr/>Hướng Dẫn Tháo Lắp Mạch VBot AIO: <a href="https://docs.google.com/document/d/1X_xGqGQt0HfPNDXM6c_joUwHxwr-mGiS/edit" target="_blank">https://docs.google.com/document/d/1X_xGqGQt0HfPNDXM6c_joUwHxwr-mGiS</a>
<hr/>Hướng Dẫn Cấu Hình Ban Đầu: <a href="https://docs.google.com/document/d/1Dc0OvvrF0cLz5gsKXaaSCFvK7AUqY75M/edit" target="_blank">https://docs.google.com/document/d/1Dc0OvvrF0cLz5gsKXaaSCFvK7AUqY75M</a>
              </div>
              <div class="card accordion" id="accordion_button_mic_tetser">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_mic_tetser" aria-expanded="false" aria-controls="collapse_button_mic_tetser">
                    Cách Cài Đặt Kiểm Tra Mic Và Scan Lấy ID Mic:
                  </h5>
                  <div id="collapse_button_mic_tetser" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_mic_tetser">
  <div class="card-body">

    <h5 class="border-bottom border-primary pb-2 mt-3">Bước 1: Di chuyển tới thư mục test_device</h5>
    <div class="code-block">
      <span class="cmd">$</span> cd /home/pi/VBot_Offline/resource/test_device
    </div>

    <h5 class="border-bottom border-primary pb-2 mt-4">Bước 2: Scan danh sách Microphone</h5>
    <div class="code-block">
      <span class="cmd">$</span> python3 Scan_Mic.py
    </div>
    <div class="alert alert-info mt-2 small">
      Terminal sẽ hiển thị danh sách các thiết bị âm thanh kèm <strong>ID</strong> và <strong>tên thiết bị</strong>
    </div>

    <h5 class="border-bottom border-primary pb-2 mt-4">Bước 3: Kiểm tra Mic theo ID</h5>
    <div class="code-block">
      <span class="cmd">$</span> nano Test_Mic.py<br>
      <small class="text-muted"># Tìm dòng device_index = 14 → thay 14 bằng ID mic vừa scan được</small><br><br>
      <span class="cmd">$</span> python3 Test_Mic.py
    </div>

    <div class="alert alert-success mt-3">
      <strong>Thao tác:</strong> Nói to và rõ vào mic trong vòng <strong>6 giây</strong><br>
      → File <code>Test_Microphone.wav</code> sẽ được tạo ra trong thư mục hiện tại
    </div>

    <h5 class="border-bottom border-primary pb-2 mt-4">Bước 4: Nghe lại file thu âm</h5>
    <div class="code-block">
      <span class="cmd">$</span> vlc Test_Microphone.wav
    </div>
    <p>Nếu nghe rõ tiếng nói → Mic đã hoạt động tốt!</p>

    <div class="alert alert-warning">
      <strong>Nếu không nghe được gì:</strong><br>
      → Thử lần lượt các ID khác từ kết quả scan<br>
      → Kiểm tra driver mic đã được cài đúng chưa (AIO, USB, ReSpeaker, v.v.)
    </div>

    <div class="alert alert-success mt-3">
      <strong>Thành công?</strong><br>
      → Vào <strong>WebUI → Cấu hình Config → Microphone → ID Mic</strong><br>
      → Điền đúng ID vừa kiểm tra xong → <strong>Lưu Config</strong>
    </div>

    <!-- PHẦN ĐIỀU CHỈNH ÂM LƯỢNG MIC -->
    <h5 class="border-bottom border-primary pb-2 mt-5">Điều Chỉnh Âm Lượng Microphone</h5>

    <div class="row">
      <div class="col-lg-6">
        <h6 class="text-success fw-bold">Cách 1: Dùng giao diện WebUI (khuyên dùng)</h6>
        <p>→ Vào tab <strong>Command/Terminal</strong><br>
        → Chọn <strong>VM8960-SoundCard</strong> → <strong>Save Alsamixer To VM8960 SoundCard Driver</strong></p>
      </div>
      <div class="col-lg-6">
        <h6 class="text-primary fw-bold">Cách 2: Điều chỉnh thủ công qua Terminal</h6>
        <div class="code-block">
          <span class="cmd">$</span> alsamixer
        </div>
        <ul class="mt-2">
          <li>Nhấn <kbd>F4</kbd> để vào chế độ <strong>Capture</strong></li>
          <li>Tìm cột có tên <strong>Capture</strong> hoặc tên driver mic</li>
          <li>Dùng phím mũi tên ↑↓ để chỉnh mức ~<strong>30–40</strong> (tránh nhạy quá)</li>
        </ul>
      </div>
    </div>

    <h6 class="mt-4 fw-bold">Lưu cấu hình thủ công (nếu dùng driver WM8960/AIO)</h6>
    <div class="code-block">
      <span class="cmd">$</span> sudo alsactl store<br>
      <span class="cmd">$</span> sudo mv /etc/wm8960-soundcard/wm8960_asound.state /etc/wm8960-soundcard/wm8960_asound_default.state<br>
      <span class="cmd">$</span> sudo cp /var/lib/alsa/asound.state /etc/wm8960-soundcard/wm8960_asound.state
    </div>

    <!-- LƯU Ý MIC I2S -->
    <div class="alert alert-danger mt-4 text-center fw-bold">
      <u>ĐẶC BIỆT CHÚ Ý</u><br><br>
      Nếu dùng <strong>Mic I2S INMP441 + MAX9857</strong>:<br>
      → Phải flash đúng <strong>IMG VBot I2S</strong><br>
      → Bắt buộc đặt <strong>ID Mic = -1</strong> trong Config
    </div>

  </div>
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_media_player_source">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player_source" aria-expanded="false" aria-controls="collapse_button_media_player_source">
                    Hướng Dẫn Cài Đặt, Kiểm Tra Loa, Âm Thanh Đầu Ra:
                  </h5>
                  <div id="collapse_button_media_player_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_media_player_source">


            <h1 class="text-center text-primary mb-5">
                <i class="bi bi-volume-up-fill me-2"></i>Hướng dẫn cấu hình âm thanh DAC I2S cho VBot
            </h1>

            <!-- Bước 1 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-1-circle-fill me-2"></i>Bước 1: Cài driver cho mạch DAC I2S
                </div>
                <div class="card-body">
                    <p>Nếu chưa cài driver, tải tại link Google Drive sau:</p>
                    <a href="https://drive.google.com/drive/folders/1KJIuovEbRGv82uc5FCfi5p0sY1o5W5vU" 
                       target="_blank" class="btn btn-success btn-lg">
                        <i class="bi bi-download"></i> Tải driver DAC I2S ngay
                    </a>
                </div>
            </div>

            <!-- Bước 2 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-2-circle-fill me-2"></i>Bước 2: Kiểm tra phát nhạc bằng VLC
                </div>
                <div class="card-body">
                    <p>Copy file nhạc bất kỳ vào thư mục <code>/home/pi</code>:</p>
                    <pre class="bg-dark text-white p-3 rounded"><code>cp /đường_dẫn/nhac_test.mp3 /home/pi/</code></pre>

                    <p>Phát thử bằng lệnh:</p>
                    <pre class="bg-dark text-white p-3 rounded"><code>vlc /home/pi/nhac_test.mp3</code></pre>
                </div>
            </div>

            <!-- Có tiếng → Xong -->
            <div class="card border-success shadow-sm mb-4">
                <div class="card-header bg-success text-white fs-5">
                    <i class="bi bi-check2-all me-2"></i>Nếu CÓ TIẾNG → HOÀN TẤT!
                </div>
                <div class="card-body text-success">
                    <h4 class="fw-bold"><i class="bi bi-emoji-laughing-fill"></i> Không cần làm gì thêm!</h4>
                    <p class="lead">VBot sẽ hoạt động ngay lập tức.</p>
                </div>
            </div>

            <!-- Không có tiếng → Tiếp tục -->
            <div class="card border-danger shadow-sm mb-4">
                <div class="card-header bg-danger text-white fs-5">
                    <i class="bi bi-x-octagon-fill me-2"></i>Nếu KHÔNG có tiếng → Làm tiếp các bước dưới
                </div>
            </div>

            <!-- Bước 3 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-3-circle-fill me-2"></i>Bước 3: Cấu hình alsamixer
                </div>
                <div class="card-body">
                    <pre class="bg-dark text-white p-3 rounded"><code>alsamixer</code></pre>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><kbd>F6</kbd> → Chọn đúng card DAC I2S</li>
                        <li class="list-group-item"><kbd>F4</kbd> → Xem các kênh Playback</li>
                        <li class="list-group-item">Chọn <code>Master</code> hoặc <code>PCM</code> → nhấn <kbd>M</kbd> để bật (<code>OO</code>)</li>
                        <li class="list-group-item">Dùng phím ↑ để tăng âm lượng</li>
                        <li class="list-group-item">Nhấn <kbd>Esc</kbd> để thoát</li>
                    </ul>
                </div>
            </div>

            <!-- Bước 4 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-4-circle-fill me-2"></i>Bước 4: Xác định tên card âm thanh
                </div>
                <div class="card-body">
                    <pre class="bg-dark text-white p-3 rounded mb-2"><code>aplay -l</code></pre>
                    <pre class="bg-dark text-white p-3 rounded"><code>cat /proc/asound/cards</code></pre>

                    <div class="alert alert-info mt-3">
                        <strong>Tên thường gặp:</strong> sndrpihifiberry, IQaudIO, hifiberrydac, Generic, AudioInjecter...
                    </div>
                </div>
            </div>

            <!-- Bước 5 -->
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-5-circle-fill me-2"></i>Bước 5: Điền tên vào VBot
                </div>
                <div class="card-body">
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item">Mở VBot → Tab <strong>Cấu hình</strong></li>
                        <li class="list-group-item">Tìm mục <strong>Tên thiết bị (alsamixer)</strong></li>
                        <li class="list-group-item">Điền đúng tên card vừa tìm được</li>
                        <li class="list-group-item">Nhấn <strong>Lưu</strong></li>
                    </ol>
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-info-circle-fill"></i>
                        Tên này chỉ dùng để set âm lượng lúc khởi động.<br>
                        Khi chạy, VBot <strong>chỉ điều chỉnh âm lượng VLC</strong>, không ảnh hưởng hệ thống.
                    </div>
                </div>
            </div>

                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_1">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_1" aria-expanded="false">
                    Nâng Cấp Full Dung Lượng Cho Thẻ Nhớ:
                  </h5>
                  <div id="collapse_button_1" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_1">

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-terminal-fill me-2"></i> Các bước thực hiện
                </div>
                <div class="card-body">

                    <ol class="list-group list-group-numbered">

                        <li class="list-group-item py-3">
                            <strong>Đăng nhập vào Raspberry Pi qua SSH</strong><br>
                            (hoặc mở Terminal trực tiếp trên Pi)
                        </li>

                        <li class="list-group-item py-3">
                            <strong>Chạy lệnh cấu hình</strong>
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>sudo raspi-config</code></pre>
                        </li>

                        <li class="list-group-item py-3">
                            <div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="badge bg-primary fs-6">1</span>
                                </div>
                                <div class="col-md-11">
                                    Chọn <strong>6 Advanced Options</strong> → nhấn <kbd>Enter</kbd>
                                </div>
                            </div>
                        </li>

                        <li class="list-group-item py-3">
                            <div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="badge bg-primary fs-6">2</span>
                                </div>
                                <div class="col-md-11">
                                    Chọn <strong>A1 Expand Filesystem</strong> → nhấn <kbd>Enter</kbd>
                                </div>
                            </div>
                        </li>

                        <li class="list-group-item py-3">
                            <div class="row align-items-center">
                                <div class="col-md-1 text-center">
                                    <span class="badge bg-success fs-6">3</span>
                                </div>
                                <div class="col-md-11">
                                    Xuất hiện thông báo <strong>“Root partition has been resized...”</strong><br>
                                    → Chọn <strong>OK</strong>
                                </div>
                            </div>
                        </li>

                        <li class="list-group-item py-3">
                            <div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="badge bg-warning fs-6">4</span>
                                </div>
                                <div class="col-md-11">
                                    Nhấn phím <kbd>Tab</kbd> để di chuyển đến <strong>< Finish ></strong> → nhấn <kbd>Enter</kbd>
                                </div>
                            </div>
                        </li>

                        <li class="list-group-item py-3">
                            <div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="badge bg-danger text-white fs-6">5</span>
                                </div>
                                <div class="col-md-11">
                                    Khi hỏi <strong>“Would you like to reboot now?”</strong><br>
                                    → Chọn <strong>< Yes ></strong> và nhấn <kbd>Enter</kbd>
                                </div>
                            </div>
                        </li>

                    </ol>

                    <div class="alert alert-success mt-4">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Xong!</strong> Sau khi khởi động lại, toàn bộ dung lượng thẻ nhớ sẽ được sử dụng.
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Để kiểm tra dung lượng mới, sau khi reboot xong bạn gõ lệnh:
                        <pre class="bg-dark text-white p-2 rounded d-inline ms-2"><code>df -h</code></pre>
                    </div>

                </div>
            </div>

                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_2">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_2" aria-expanded="false">
                    Thay Đổi Đường Dẫn (Path) Của Apache2:
                  </h5>
                  <div id="collapse_button_2" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_2">

            <!-- Cách 1: Tự động (khuyên dùng) -->
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white fs-5">
                    <i class="bi bi-magic me-2"></i> Cách 1 – THAY ĐỔI TỰ ĐỘNG (chỉ 1 lệnh + 1 dòng nhập)
                </div>
                <div class="card-body">

                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item py-3">
                            Di chuyển vào thư mục script:
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>cd /home/pi/VBot_Offline/resource/test_device/</code></pre>
                        </li>
                        <li class="list-group-item py-3">
                            Chạy script thay đổi tự động:
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>sudo python3 Change_Path_Apache2.py</code></pre>
                        </li>
                        <li class="list-group-item py-3">
                            Khi được hỏi, nhập đường dẫn mới rồi nhấn <kbd>Enter</kbd><br>
                            <strong>Ví dụ (đường dẫn mặc định của VBot):</strong>
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>/home/pi/VBot_Offline/html</code></pre>
                        </li>
                    </ol>

                    <div class="alert alert-success mt-4">
                        <i class="bi bi-check2-all me-2"></i>
                        Xong! Script sẽ tự động sửa 2 file cấu hình và restart Apache2 cho bạn.
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- Cách 2: Thay đổi thủ công -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark fs-5">
                    <i class="bi bi-wrench-adjustable-circle me-2"></i> Cách 2 – THAY ĐỔI THỦ CÔNG (nếu muốn tự làm từng bước)
                </div>
                <div class="card-body">

                    <ol class="list-group list-group-numbered">

                        <li class="list-group-item py-3">
                            Mở file cấu hình site:
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>sudo nano /etc/apache2/sites-available/000-default.conf</code></pre>
                            Tìm và sửa dòng:<br>
                            <code>DocumentRoot /home/pi/VBot_Offline/html</code><br>
                            → thành đường dẫn bạn muốn, ví dụ:<br>
                            <code class="text-success">DocumentRoot /var/www/html</code>
                        </li>

                        <li class="list-group-item py-3">
                            Lưu file: <kbd>Ctrl</kbd> + <kbd>X</kbd> → <kbd>Y</kbd> → <kbd>Enter</kbd>
                        </li>

                        <li class="list-group-item py-3">
                            Mở file cấu hình Apache2:
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>sudo nano /etc/apache2/apache2.conf</code></pre>
                            Tìm khối <code>&lt;Directory /home/pi/VBot_Offline/&gt;</code><br>
                            → sửa thành:<br>
                            <code class="text-success">&lt;Directory /var/www/html/&gt;</code><br>
                            (sửa cả 3 dòng bên trong khối nếu có)
                        </li>

                        <li class="list-group-item py-3">
                            Lưu lại: <kbd>Ctrl</kbd> + <kbd>X</kbd> → <kbd>Y</kbd> → <kbd>Enter</kbd>
                        </li>

                        <li class="list-group-item py-3">
                            Restart Apache2:
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>sudo systemctl restart apache2</code></pre>
                        </li>

                    </ol>

                    <div class="alert alert-info mt-4">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Hoàn tất! Truy cập lại địa chỉ IP của Pi để kiểm tra WebUI ở vị trí mới.
                    </div>
                </div>
            </div>

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
                <span class="cmd">$</span> sudo mkdir -p /etc/cloudflared<br>
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
<div class="card shadow-sm">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-rocket-takeoff-fill me-2"></i> Cách nhanh nhất – Chỉ 3 lệnh (khuyên dùng)
                </div>
                <div class="card-body">

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Trước tiên kiểm tra đúng đường dẫn php.ini của bạn:</strong>
                        <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>php -i | grep "Loaded Configuration File"</code></pre>
                        Kết quả sẽ hiện ra đường dẫn chính xác, ví dụ:<br>
                        <code>/etc/php/8.1/apache2/php.ini</code> hoặc <code>/etc/php/7.4/apache2/php.ini</code>
                    </div>

                    <p>Sau khi đã biết đúng đường dẫn, chạy 2 lệnh dưới đây (thay <mark>/đường/dẫn/của/bạn/php.ini</mark> cho phù hợp):</p>

                    <pre class="bg-dark text-white p-3 rounded"><code>sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 300M/' /đường/dẫn/của/bạn/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 350M/' /đường/dẫn/của/bạn/php.ini</code></pre>

                    <p>Và cuối cùng restart Apache2:</p>
                    <pre class="bg-dark text-white p-3 rounded"><code>sudo systemctl restart apache2</code></pre>

                    <div class="alert alert-success mt-4">
                        <i class="bi bi-check2-all me-2"></i>
                        Xong! Giờ bạn có thể upload file lớn tới ~300 MB thoải mái.
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white fs-5">
                    <i class="bi bi-pencil-square me-2"></i> Cách thủ công (nếu thích sửa tay)
                </div>
                <div class="card-body">
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item">Mở file php.ini:
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>sudo nano /đường/dẫn/của/bạn/php.ini</code></pre>
                        </li>
                        <li class="list-group-item">Tìm và sửa 2 dòng:
                            <code>upload_max_filesize = 300M</code><br>
                            <code>post_max_size = 350M</code>
                        </li>
                        <li class="list-group-item">Lưu: <kbd>Ctrl</kbd>+<kbd>X</kbd> → <kbd>Y</kbd> → <kbd>Enter</kbd></li>
                        <li class="list-group-item">Restart Apache2:
                            <pre class="bg-dark text-white p-3 rounded mt-2 mb-0"><code>sudo systemctl restart apache2</code></pre>
                        </li>
                    </ol>
                </div>
            </div>

                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_4">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_4" aria-expanded="false">
                    Đăng Nhập, Đặt Mật Khẩu, Quên Mật Khẩu WebUI:
                  </h5>
                  <div id="collapse_button_4" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_4">
<div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white fs-5">
                    <i class="bi bi-toggle-on me-2"></i> Bật tính năng đăng nhập WebUI
                </div>
                <div class="card-body">
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item py-3">
                            Nhấn vào <strong>Avatar</strong> ở góc trên bên phải
                        </li>
                        <li class="list-group-item py-3">
                            Chọn <strong>Cá nhân</strong> → <strong>Cài đặt</strong>
                        </li>
                        <li class="list-group-item py-3">
                            Tìm mục <strong>Đăng nhập Web UI</strong> → <strong>Bật lên</strong>
                            <div class="text-center mt-3">
                                <i class="bi bi-toggle-on text-success fs-1"></i>
                            </div>
                        </li>
                    </ol>
                    <div class="alert alert-success mt-3">
                        <i class="bi bi-check2-all"></i> Xong! Lần sau truy cập WebUI sẽ yêu cầu đăng nhập.
                    </div>
                </div>
            </div>

            <!-- Đặt mật khẩu -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-key-fill me-2"></i> Đặt mật khẩu đăng nhập WebUI
                </div>
                <div class="card-body">
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item py-3">
                            Nhấn vào <strong>Avatar</strong> góc trên bên phải
                        </li>
                        <li class="list-group-item py-3">
                            Chọn <strong>Cá nhân</strong> → <strong>Chỉnh sửa hồ sơ</strong>
                        </li>
                        <li class="list-group-item py-3">
                            Tìm mục <strong>Mật khẩu Web UI</strong> → nhập mật khẩu mới của bạn
                        </li>
                        <li class="list-group-item py-3">
                            <strong>Bắt buộc</strong> phải nhập <strong>địa chỉ Email</strong> (dùng để lấy lại mật khẩu nếu quên)
                        </li>
                        <li class="list-group-item py-3 text-success fw-bold">
                            Nhấn <strong>Lưu</strong>
                        </li>
                    </ol>
                </div>
            </div>

            <!-- Quên / Đổi mật khẩu -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white fs-5">
                    <i class="bi bi-arrow-repeat me-2"></i> Quên mật khẩu hoặc muốn đổi mật khẩu mới
                </div>
                <div class="card-body">
                    <p class="lead text-center">Chỉ cần làm tương tự như trên:</p>
                    <div class="row text-center">
                        <div class="col-md-6">
                            <div class="card border-primary h-100">
                                <div class="card-body">
                                    <i class="bi bi-shield-exclamation fs-1 text-primary"></i>
                                    <h5 class="mt-3">Quên mật khẩu</h5>
                                    <p>Avatar → Cá nhân → <strong>Quên mật khẩu</strong><br>
                                    Nhập Email đã khai báo → hệ thống sẽ hiển thị mật khẩu</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-success h-100">
                                <div class="card-body">
                                    <i class="bi bi-shield-check fs-1 text-success"></i>
                                    <h5 class="mt-3">Đổi mật khẩu</h5>
                                    <p>Avatar → Cá nhân → <strong>Chỉnh sửa hồ sơ</strong><br>
                                    → Nhập mật khẩu mới → Lưu</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

<!-- Nút UP -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-volume-up-fill me-2"></i> Nút UP (Tăng âm lượng)
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-6">
                            <div class="border rounded p-4 bg-light">
                                <h5 class="text-primary"><i class="bi bi-hand-index"></i> Nhấn – Nhả</h5>
                                <p class="lead fw-bold">Tăng âm lượng từng bước</p>
                                <small class="text-muted">(bước tăng được thiết lập trong Config.json)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-4 bg-light">
                                <h5 class="text-danger"><i class="bi bi-hand-index-fill"></i> Nhấn Giữ</h5>
                                <p class="lead fw-bold">Tăng âm lượng lên tối đa ngay lập tức</p>
                                <small class="text-muted">(giá trị max trong Config.json)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút DOWN -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white fs-5">
                    <i class="bi bi-volume-down-fill me-2"></i> Nút DOWN (Giảm âm lượng)
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-6">
                            <div class="border rounded p-4 bg-light">
                                <h5 class="text-primary"><i class="bi bi-hand-index"></i> Nhấn – Nhả</h5>
                                <p class="lead fw-bold">Giảm âm lượng từng bước</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-4 bg-light">
                                <h5 class="text-danger"><i class="bi bi-hand-index-fill"></i> Nhấn Giữ</h5>
                                <p class="lead fw-bold">Giảm âm lượng xuống thấp nhất ngay</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút MIC -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white fs-5">
                    <i class="bi bi-mic-fill me-2"></i> Nút MIC
                </div>
                <div class="card-body">

                    <div class="accordion" id="accordionMic">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#micStandby">
                                    <strong>Trạng thái chờ được đánh thức</strong>
                                </button>
                            </h2>
                            <div id="micStandby" class="accordion-collapse collapse show" data-bs-parent="#accordionMic">
                                <div class="accordion-body">
                                    <strong>Nhấn – Nhả:</strong> Bật/Tắt Microphone<br>
                                    <strong>Nhấn Giữ:</strong> Bật/Tắt chế độ câu phản hồi (feedback)
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#micMusic">
                                    Đang phát nhạc (Media Player)
                                </button>
                            </h2>
                            <div id="micMusic" class="accordion-collapse collapse" data-bs-parent="#accordionMic">
                                <div class="accordion-body">
                                    <strong>Nhấn – Nhả:</strong> Tạm dừng / Tiếp tục phát nhạc<br>
                                    <strong>Nhấn Giữ:</strong> Dừng phát nhạc hoàn toàn
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#micTTS">
                                    Đang phát câu trả lời (TTS)
                                </button>
                            </h2>
                            <div id="micTTS" class="accordion-collapse collapse" data-bs-parent="#accordionMic">
                                <div class="accordion-body">
                                    <strong>Nhấn Nhả hoặc Nhấn Giữ:</strong> Dừng ngay câu trả lời đang phát
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Nút WAKE UP -->
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-warning text-dark fs-5">
                    <i class="bi bi-chat-dots-fill me-2"></i> Nút WAKE UP (Đánh thức Bot)
                </div>
                <div class="card-body">

                    <div class="accordion" id="accordionWake">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#wakeStandby">
                                    <strong>Trạng thái chờ được đánh thức</strong>
                                </button>
                            </h2>
                            <div id="wakeStandby" class="accordion-collapse collapse show" data-bs-parent="#accordionWake">
                                <div class="accordion-body">
                                    <strong>Nhấn – Nhả:</strong> Đánh thức Bot (Wake Up)<br>
                                    <strong>Nhấn Giữ:</strong> Bật/Tắt chế độ hội thoại liên tục
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#wakeMusic">
                                    Đang phát nhạc
                                </button>
                            </h2>
                            <div id="wakeMusic" class="accordion-collapse collapse" data-bs-parent="#accordionWake">
                                <div class="accordion-body">
                                    <strong>Nhấn – Nhả:</strong> Tạm dừng nhạc + Đánh thức Bot để nghe lệnh<br>
                                    <strong>Nhấn Giữ:</strong> Dừng nhạc hoàn toàn
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#wakeTTS">
                                    Đang phát câu trả lời (TTS)
                                </button>
                            </h2>
                            <div id="wakeTTS" class="accordion-collapse collapse" data-bs-parent="#accordionWake">
                                <div class="accordion-body">
                                    <strong>Nhấn Nhả hoặc Nhấn Giữ:</strong> Dừng ngay câu trả lời
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                  <div class="row mb-3">
                    <b class="text-danger">Sơ Đồ 4 Nút Nhấn Nhả: <a href="https://github.com/user-attachments/assets/8c43d1fd-bf39-47db-a939-052e6540e074" target="_blank">Nhấn Vào Đây Để Xem Sơ Đồ</a></b>
                  </div>
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
<div class="alert alert-primary mt-3">
Ghi Chú: <br/> - Nhấn giữ bất kỳ nút nhấn nào trong khoảng 20 giây rồi nhả ra để Reset Đặt lại toàn bộ cấu hình mạng wifi<br/>
- Nhấn giữ bất kỳ nút nhấn nào trong khoảng 15 giây rồi nhả ra để bật Bluetooth (Khi đã được cấu hình Xem Chi Tiết Bên Dưới: <b>Cài Đặt Cấu Hình Wifi Qua Bluetooth</b>)<br/>
- Nhấn giữ bất kỳ nút nhấn nào trong khoảng 10 giây rồi nhả ra để đọc địa chỉ ip hiện tại
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
                    Cấu Hình Auto Kết Nối Wifi hoặc Tạo Điểm Truy Cập AP:
                  </h5>
                  <div id="collapse_button_7" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_7">
                    - Truy Cập Tab: <b>Command/Terminal</b> -> <b>OS Wifi</b>
                    <br />
                    <b>- Restart Auto Wifi Manager:</b> Khởi động lại Services Auto Wifi Manaager đang chạy trên hệ thống<br />
                    <b>- Enable Auto Wifi Manager:</b> Kích Hoạt Services Auto Wifi Manaager trên hệ thống (Mặc định là đã kích hoạt từ trước rồi)<br />
                    <b>- Install Auto Wifi Manager:</b> Chỉ Cài Đặt Auto Wifi Manager Và Tạo Điểm truy Cập AP, Tự động kết nối lại khi mất mạng hoặc hệ thống mạng khởi động sau VBot<br />
                    <b>- Install Auto Wifi Manager + Đọc IP:</b> Cài Đặt Auto Wifi Manager + Đọc Địa Chỉ IP Ra Loa Và Tạo Điểm truy Cập AP,
                    Tự động kết nối lại khi mất mạng hoặc hệ thống mạng khởi động sau VBot,
                    Nếu phát hiện địa chỉ IP Local bị thay đổi hoặc mạng wifi trước đo bị thay đổi sẽ tự đọc địa chỉ ip local mới<br />
                    <b>- Logs Auto Wifi Manager:</b> Kiểm Tra Logs Quá Trình Hoạt Động<br />
                    <b>- Status Auto Wifi Manager:</b> Kiểm tra trạng thái xem là: đang hoạt động hay không<br />
                  </div>
                </div>
              </div>
            <div class="card accordion" id="accordion_button_wifi_via_ble">
            <div class="card-body">
            <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_wifi_via_ble" aria-expanded="false" aria-controls="collapse_button_wifi_via_ble">
            Cài Đặt Cấu Hình Wifi Qua Bluetooth:</h5>
            <div id="collapse_button_wifi_via_ble" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_wifi_via_ble">

  <div class="alert alert-info">
    <strong>Hướng dẫn:</strong> Cấu hình Bluetooth & cài đặt <b>Rpi-SetWiFi-viaBluetooth</b> trên Raspberry Pi<br/>
<br/>
<b>Hướng Dẫn Sử Dụng APP BTBerryWifi Cấu Hình Wifi Cho Loa VBot: <a href="https://docs.google.com/document/d/1qX4mNQAQWzEbEEpk4AWL95_nlinlArLg/edit" target="_blank">https://docs.google.com/document/d/1qX4mNQAQWzEbEEpk4AWL95_nlinlArLg</a></b>
	
  </div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-terminal"></i> Hướng dẫn cài đặt WiFi qua Bluetooth (Tự động)
    </div>

    <div class="card-body">
        <div class="alert alert-warning">
            <i class="bi bi-shield-lock-fill"></i>
            <b>Yêu cầu bắt buộc:</b>  
            Cần <b>truy cập SSH</b> vào Raspberry Pi để thực hiện các lệnh cài đặt bên dưới.
        </div>

        <p class="mb-3">
            Hệ thống hỗ trợ <b>cấu hình WiFi qua Bluetooth hoàn toàn tự động</b>.  
            Sau khi đăng nhập SSH vào thiết bị, thực hiện các bước sau:
        </p>

        <ol class="mb-4">
            <li class="mb-3">
                <b>Bước 1:</b> Di chuyển đến thư mục cài đặt
                <pre class="bg-dark text-light p-3 rounded mt-2"><code>$:> cd /home/pi/VBot_Offline/resource/set_wifi_via_ble</code></pre>
            </li>

            <li class="mb-3">
                <b>Bước 2:</b> Chạy script cài đặt
                <pre class="bg-dark text-light p-3 rounded mt-2"><code>$:> bash btwifisetInstall.sh</code></pre>
            </li>

            <li class="mb-3">
                <b>Bước 3:</b> Khởi động lại hệ thống sau khi cài đặt thành công
                <pre class="bg-dark text-light p-3 rounded mt-2"><code>$:> sudo reboot</code></pre>
            </li>
        </ol>

        <div class="alert alert-success mb-0">
            <i class="bi bi-check-circle-fill"></i>
            Sau khi khởi động lại, hệ thống sẵn sàng cấu hình WiFi qua Bluetooth.
        </div>
    </div>
</div>



  <!-- BƯỚC 1 -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-terminal"></i> Hướng dẫn cài đặt WiFi qua Bluetooth (Thủ Công)
    </div>
    <div class="card-header bg-primary text-white">
      🔧 Bước 1: Sửa file <code>/etc/apt/sources.list</code>
    </div>
    <div class="card-body">
      <p><b>1. Mở file cấu hình:</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>sudo nano /etc/apt/sources.list</code></pre>
      <p><b>2. Chú thích dòng sau (thêm <code>#</code> phía trước):</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>#deb http://raspbian.raspberrypi.org/raspbian/ bullseye main contrib non-free rpi</code></pre>
      <p><b>3. Thêm dòng mới:</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>deb http://archive.raspbian.org/raspbian bullseye main contrib non-free</code></pre>
      <p class="text-muted">
        Nhấn <kbd>Ctrl</kbd> + <kbd>X</kbd> → <kbd>Y</kbd> → <kbd>Enter</kbd> để lưu
      </p>
      <p><b>4. Áp dụng thay đổi:</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>
sudo apt clean
sudo apt update
      </code></pre>
    </div>
  </div>
  <!-- BƯỚC 2 -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-success text-white">
      📡 Bước 2: Bật & kiểm tra Bluetooth
    </div>
    <div class="card-body">
      <p><b>1. Kích hoạt dịch vụ Bluetooth:</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>
sudo systemctl enable bluetooth.service
sudo systemctl enable hciuart.service
sudo reboot
      </code></pre>
      <span class="badge bg-warning text-dark mb-3">Hệ thống sẽ tự khởi động lại</span>
      <p class="mt-3"><b>2. Kiểm tra Bluetooth đã hoạt động:</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>hciconfig</code></pre>
      <p><b>Kết quả đúng sẽ giống như:</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>
hci0:   Type: Primary  Bus: UART
        UP RUNNING PSCAN ISCAN
      </code></pre>
      <hr>
      <p><b>3. Kiểm tra chi tiết dịch vụ:</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>systemctl status hciuart</code></pre>
      <pre class="bg-dark text-light p-3 rounded"><code>systemctl status bluetooth</code></pre>
    </div>
  </div>
  <!-- BƯỚC 3 -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white">
      📶 Bước 3: Cài Rpi-SetWiFi-viaBluetooth
    </div>
    <div class="card-body">
      <p><b>1. Chạy lệnh cài đặt:</b></p>
      <pre class="bg-dark text-light p-3 rounded"><code>
curl  -L https://raw.githubusercontent.com/marion001/Rpi-SetWiFi-viaBluetooth/main/btwifisetInstall.sh | bash
      </code></pre>
<div class="alert alert-secondary mt-3">
  <b>📌 Trong quá trình cài đặt, chương trình sẽ lần lượt hỏi:</b>
</div>

<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:50%">Câu hỏi</th>
        <th>Giá trị cần nhập</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          Specify <b>btwifiset</b> service install location
        </td>
        <td>
          <span class="badge bg-info text-dark">Nhấn Enter</span>
          <div class="text-muted small">
            (Sử dụng thư mục mặc định)
          </div>
        </td>
      </tr>
      <tr>
        <td>
          Bluetooth password (encryption key)
          <br>
          <span class="text-muted small">
            [Default: VBot-Assistant]
          </span>
        </td>
        <td>
          <code class="text-danger fw-bold">vbot123</code>
        </td>
      </tr>
      <tr>
        <td>
          btwifiset needs your WiFi Country code
        </td>
        <td>
          <code class="fw-bold">vn</code>
        </td>
      </tr>
    </tbody>
  </table>
</div>
<div class="alert alert-success mt-3 mb-0">
  ✔ Sau khi nhập xong các thông tin trên, chương trình sẽ tự động cài đặt
  <b>Rpi-SetWiFi-viaBluetooth</b>
</div><br/>
      <p class="text-success">
        ✔ Sau khi cài đặt hoàn tất, khởi động lại hệ thống bằng lệnh
      </p>
      <pre class="bg-dark text-light p-3 rounded"><code>sudo reboot</code></pre>
    </div>
<!-- APP KẾT NỐI BLUETOOTH -->
<div class="card mb-4 shadow-sm">
  <div class="card-header bg-warning text-dark">
    📱 APP Kết Nối Bluetooth Với Loa VBot
  </div>
  <div class="card-body">
    <p class="text-center">
      Tên ứng dụng:
      <span class="fw-bold text-danger">BTBerryWifi</span>
    </p>
    <div class="row">
      <div class="col-md-6 mb-2">
        <div class="border rounded p-3 h-100">
          <h6 class="text-primary mb-2">🍎 IOS</h6>
          <a href="https://apps.apple.com/us/app/btberrywifi/id1596978011" target="_blank" class="btn btn-outline-primary btn-sm">Mở App Store</a>
          <div class="small text-muted mt-2">
            https://apps.apple.com/us/app/btberrywifi/id1596978011
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-2">
        <div class="border rounded p-3 h-100">
          <h6 class="text-success mb-2">🤖 Android</h6>
          <a href="https://play.google.com/store/apps/details?id=com.bluepieapps.btberrywifi" target="_blank" class="btn btn-outline-success btn-sm">
            Mở Google Play
          </a>
          <div class="small text-muted mt-2">
            https://play.google.com/store/apps/details?id=com.bluepieapps.btberrywifi
          </div>
        </div>
      </div>
    </div>
    <div class="alert alert-info mt-3 mb-0">
      📌 Dùng ứng dụng <b>BTBerryWifi</b> để kết nối Bluetooth và cấu hình WiFi cho <b>Loa VBot</b>
    </div>
  </div>
</div>
<div class="alert alert-warning mt-3">
  ⏱️ <b>Lưu ý:</b> Bluetooth sẽ tự động bật trong khoảng
  <b>15 – 20 phút</b> kể từ khi loa được cấp nguồn,
  sau đó sẽ <b>tự tắt</b>.
  <br>
  <span class="text-muted">
    (Khoảng thời gian này đủ để cấu hình WiFi cho loa)
  </span>
</div>
  </div>
            </div>
            </div>
            </div>
			
			  
              <div class="card accordion" id="accordion_button_oled_i2c">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_oled_i2c" aria-expanded="false" aria-controls="collapse_button_oled_i2c">
                    Kết Nối Màn Hình I2C (<font color=red>Không còn được hỗ trợ</font>):
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
                    - <b>Lưu Ý: Cần Sử dụng 1 thiết bị khác không chạy VBot để Train</b><br /><br>
                    B1: <b>Cài Docker trên thiết bị (Cách cài các bạn tham khảo trên google nhé)</b><br /><br />
                    B2: Chạy lệnh sau: <b>$:> sudo docker pull rhasspy/snowboy-seasalt</b><br /><br />
                    B3: Chạy tiếp lệnh sau: <b>$:> docker run -it -p 8899:8000 rhasspy/snowboy-seasalt</b><br /><br />
                    B4: Docker sẽ khởi chạy rhasspy/snowboy-seasalt, hãy kiểm tra xem đã chạy thành công chưa bằng cách truy cập: http://ip:8899<br />
                    nếu cập được vào và hiển thị giao diện để Train là OK<br /><br />
                    B5: Cần Stop, không cho chạy VBot. Sau đó Truy cập vào SHH với thiết bị chạy VBot rồi chạy lệnh sau:<br />
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

            <div class="card accordion" id="accordion_button_airport">
            <div class="card-body">
            <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_airport" aria-expanded="false" aria-controls="collapse_button_airport">
            Cài Đặt, Thiết Lập AirPlay - shairport-sync:</h5>
            <div id="collapse_button_airport" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_airport">


      <!-- Card chính -->
      <div class="card shadow-lg border-primary mb-4">
        <div class="card-header bg-primary text-white fs-5">
          <i class="bi bi-gear-fill me-2"></i>Cài đặt Shairport
        </div>
        <div class="card-body">

          <h5 class="mt-4 mb-3 text-success">
            Cần truy cập vào giao diện SSH của PI để chạy các lệnh cài đặt bên dưới
          </h5>

          <h5 class="mt-4 mb-3 text-success">
            <i class="bi bi-1-circle-fill me-2"></i>Di chuyển đến thư mục cài đặt
          </h5>
          <div class="terminal">
            <span class="prompt">$:&gt;</span> <b>cd /home/pi/VBot_Offline/resource/airplay</b>
          </div>

          <h5 class="mt-4 mb-3 text-success">
            <i class="bi bi-2-circle-fill me-2"></i>Kiểm tra điều kiện cài đặt
          </h5>
          <div class="terminal">
            <span class="prompt">$:&gt;</span> <b>./pre_check_airplay_on_pi.sh</b>
            <br><span class="code-comment"># Script sẽ báo bạn đủ điều kiện hay thiếu gói nào</span>
          </div>

          <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle-fill me-2"></i>
            Nếu script báo <strong>OK / đủ điều kiện</strong> → tiếp tục bước tiếp theo.<br>
            Nếu thiếu gói → cài bổ sung theo hướng dẫn của script (thường là sudo apt install …).
          </div>

          <h5 class="mt-5 mb-3 text-success">
            <i class="bi bi-3-circle-fill me-2"></i>Chạy lệnh cài đặt chính
          </h5>
          <div class="terminal">
            <span class="prompt">$:&gt;</span> <b>./install_airplay_v3.sh</b>
          </div>

          <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Trong quá trình cài đặt:</strong><br>
            • Làm theo hướng dẫn trên màn hình<br>
            • Có thể mất 5–15 phút tùy tốc độ Pi và mạng<br>
            • Không tắt máy hoặc Ctrl+C giữa chừng
          </div>

          <h5 class="mt-5 mb-3 text-success">
            <i class="bi bi-check2-circle me-2"></i>Sau khi cài xong
          </h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item">→ Khởi động lại Raspberry Pi: <code>sudo reboot</code></li>
            <li class="list-group-item">→ Vào Settings trên iPhone/iPad/Mac → AirPlay → chọn thiết bị mới xuất hiện (thường tên máy Pi hoặc tên bạn đặt)</li>
            <li class="list-group-item">→ Phát nhạc thử → âm thanh phải ra loa ngay</li>
          </ul>

        </div> <!-- card-body -->
      </div> <!-- card -->

      <!-- Card lưu ý cuối -->
      <div class="card shadow border-info">
        <div class="card-header bg-info text-white">
          <i class="bi bi-lightbulb-fill me-2"></i>Lưu ý quan trọng
        </div>
        <div class="card-body">
          <ul class="mb-0">
            <li>Đảm bảo Raspberry Pi kết nối mạng ổn định (WiFi hoặc LAN)</li>
            <li>Nên dùng nguồn 5V/3A trở lên để tránh lỗi âm thanh rè</li>
            <li>Nếu muốn đổi tên thiết bị Airplay → chỉnh trong file config (thường là /etc/shairport-sync.conf)<br/> hoặc di chuyển tới: <b>Command/Terminal</b> => <b>AirPlay</b> => <b>Đổi Tên AirPlay</b></li>
            <li>Chạy <code>sudo systemctl status shairport-sync</code> để kiểm tra dịch vụ có chạy không</li>
          </ul>
        </div>
      </div>
            </div>
            </div>
            </div>


<div class="card accordion" id="accordion_button_zram">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_zram" aria-expanded="false" aria-controls="collapse_button_zram">
Tối Ưu Ram zram-tools:</h5>
<div id="collapse_button_zram" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_zram">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">🚀 Hướng dẫn cài đặt & cấu hình ZRAM, Tối Ưu Ram</h5>
    </div>
    <div class="card-body">
      <!-- Cài đặt -->
      <h6 class="mt-2">
        <span class="badge bg-success">Bước 1</span> Cài đặt ZRAM
      </h6>
      <pre class="bg-dark text-light p-3 rounded">
<code>$:> sudo apt update
$:> sudo apt install zram-tools -y</code>
      </pre>
      <!-- Tự động -->
      <h6 class="mt-4">
        <span class="badge bg-info">Bước 2</span> Cấu hình ZRAM tự động (Nếu cấu hình tự động thì bỏ qua bước 3)
      </h6>
      <pre class="bg-dark text-light p-3 rounded">
<code>$:> sudo sed -i 's/^[[:space:]]*#?[[:space:]]*PERCENT=.*/PERCENT=40/' /etc/default/zramswap
$:> sudo sed -i 's/^[[:space:]]*#?[[:space:]]*ALGO=.*/ALGO=lz4/' /etc/default/zramswap</code>
      </pre>
      <!-- Thủ công -->
      <h6 class="mt-4">
        <span class="badge bg-warning text-dark">Bước 3</span>Hoặc cấu hình ZRAM thủ công (nếu làm thủ công thì bỏ qua bước 2)
      </h6>
      <pre class="bg-dark text-light p-3 rounded">
<code>$:> sudo nano /etc/default/zramswap</code>
      </pre>
      <div class="alert alert-warning">
        ✏️ Sửa <strong>2 tham số sau</strong> (nhớ bỏ dấu <code>#</code> phía trước):
        <pre class="mt-2 mb-0 bg-light text-dark p-2 rounded">
PERCENT=40
ALGO=lz4
        </pre>
      </div>
      <!-- Restart -->
      <h6 class="mt-4">
        <span class="badge bg-secondary">Bước 4</span> Khởi động lại ZRAM và kiểm tra trạng thái đang chạy
      </h6>
      <pre class="bg-dark text-light p-3 rounded">
<code>$:> sudo systemctl restart zramswap</code>
<code>$:> sudo systemctl status zramswap</code>
      </pre>
      <!-- Check -->
      <h6 class="mt-4">
        <span class="badge bg-primary">Bước 5</span> Kiểm tra Priority
      </h6>
      <pre class="bg-dark text-light p-3 rounded">
<code>$:> swapon --show</code>
      </pre>
<pre class="bg-dark text-light p-3 rounded">
<code>pi@VBot-Assistant:~ $ swapon --show
NAME       TYPE        SIZE USED PRIO
/var/swap  file        1.2G   0B   -2
/dev/zram0 partition 189.7M   0B  100</code>
</pre>
      <div class="alert alert-info">
        ℹ️ <strong>ZRAM phải có Priority cao hơn Swap SD (cột PRIO có tham số càng cao sẽ được dùng trước)</strong><br>
        Nếu không, kernel sẽ swap xuống SD trước → ZRAM không được sử dụng.
      </div>
      <!-- Disable swap SD -->
      <h6 class="mt-4">
        <span class="badge bg-danger">Tuỳ chọn</span> Tắt Swap SD (chỉ khi cần, cột PRIO có tham số càng cao sẽ được dùng trước)
      </h6>
      <pre class="bg-dark text-light p-3 rounded">
<code>$:> sudo systemctl stop dphys-swapfile
$:> sudo systemctl disable dphys-swapfile</code>
      </pre>
      <div class="alert alert-danger">
        ⚠️ Chỉ tắt Swap SD khi bạn đã xác nhận ZRAM hoạt động ổn định
      </div>
    </div>
  </div>
</div>
</div>
</div>

<div class="card accordion" id="accordion_button_logs2ram">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_logs2ram" aria-expanded="false" aria-controls="collapse_button_logs2ram">
log2ram Ghi Logs vào Ram, Giảm ghi thẻ nhớ, tăng độ bền, hệ thống chạy ổn định 24/7:</h5>
<div id="collapse_button_logs2ram" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_logs2ram">

    <!-- STEP 2 -->
    <div class="card mb-3">
        <div class="card-header fw-bold">
            1: Xác nhận thư mục đang chứa log
        </div>
        <div class="card-body">
            <pre><code>$:> sudo du -sh /var/hdd.log /var/log</code></pre>
            <p class="text-muted mb-0">
                Nếu log quá lớn (vài trăm MB hoặc GB), cần xóa trước khi bật log2ram.
            </p>
        </div>
    </div>

    <!-- STEP 3 -->
    <div class="card mb-3">
        <div class="card-header fw-bold text-danger">
            2: Xóa toàn bộ log cũ trên thẻ nhớ
        </div>
        <div class="card-body">
            <pre><code>$:> sudo rm -rf /var/hdd.log/*
$:> sudo rm -rf /var/log/journal/*</code></pre>
            <p class="text-warning mb-0">
                ⚠️ Chỉ xóa log, không ảnh hưởng hệ thống.
            </p>
        </div>
    </div>

    <!-- STEP 4 -->
    <div class="card mb-3">
        <div class="card-header fw-bold">
            3: Cài đặt log2ram (chuẩn & gọn)
        </div>
        <div class="card-body">
            <pre><code>$:> sudo apt update
$:> sudo apt install rsync -y
$:> git clone https://github.com/azlux/log2ram.git
$:> cd log2ram
$:> sudo ./install.sh
$:> sudo reboot</code></pre>
        </div>
    </div>

    <!-- STEP 5 -->
    <div class="card mb-3">
        <div class="card-header fw-bold">
            4: Cấu hình log2ram (RẤT QUAN TRỌNG)
        </div>
        <div class="card-body">
            <p>Mở file cấu hình:</p>
            <pre><code>$:> sudo nano /etc/log2ram.conf</code></pre>
            <p class="mt-3 mb-1 fw-bold">Cấu hình khuyến nghị cho Pi Zero 2W:</p>
            <pre><code>SIZE=32M
USE_RSYNC=false
ZL2R=false
COMP_ALG=lz4
LOG_DISK_SIZE=80M</code></pre>
        </div>
    </div>

    <!-- STEP 6 -->
    <div class="card mb-3">
        <div class="card-header fw-bold">
            5: Kiểm tra sau khi reboot
        </div>
        <div class="card-body">
            <pre><code>$:> df -h /var/log
$:> free -h</code></pre>
        </div>
    </div>

    <!-- STEP 7 -->
    <div class="card mb-3">
        <div class="card-header fw-bold text-success">
            6: Gợi ý tối ưu thêm (rất nên làm)
        </div>
        <div class="card-body">
            <p class="fw-bold">Giảm log journald:</p>
            <pre><code>$:> sudo nano /etc/systemd/journald.conf</code></pre>
            <pre><code>Storage=volatile
RuntimeMaxUse=20M
RuntimeKeepFree=10M
SystemMaxUse=20M
SystemKeepFree=50M</code></pre>
            <pre><code>$:> sudo systemctl restart systemd-journald
$:> sudo systemctl restart log2ram</code></pre>
        </div>
    </div>

    <!-- STEP 8 -->
    <div class="card mb-4">
        <div class="card-header fw-bold">
            7: Kiểm tra cuối cùng
        </div>
        <div class="card-body">
            <pre><code>$:> journalctl --disk-usage
$:> df -h /var/log</code></pre>
        </div>
    </div>

</div>
</div>
</div>


<div class="card accordion" id="accordion_button_NoiseSuppression">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_NoiseSuppression" aria-expanded="false" aria-controls="collapse_button_NoiseSuppression">
Cài Đặt NoiseSuppression Giảm Nhiễu Nền Ở Mic Thu Âm (Noise Suppression SpeexDSP):</h5>
<div id="collapse_button_NoiseSuppression" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_NoiseSuppression">

      <div class="d-flex align-items-start mb-4">
        <span class="badge bg-primary rounded-pill fs-5 me-3" style="min-width: 2.5rem; height: 2.5rem; line-height: 2.5rem; padding: 0;">0</span>
        <div class="flex-grow-1">
          <h5 class="mb-3 text-success">Nếu Cài Tự Động</h5>
          <pre class="bg-dark text-white p-3 rounded"><code>$:> sudo python3 -m pip install /home/pi/VBot_Offline/resource/whl_build_file/speexdsp_ns-0.1.2-cp39-cp39-linux_armv7l.whl --force-reinstall</code></pre>
        </div>
      </div>
<hr/>
      <div class="d-flex align-items-start mb-4">
        <span class="badge bg-primary rounded-pill fs-5 me-3" style="min-width: 2.5rem; height: 2.5rem; line-height: 2.5rem; padding: 0;">1</span>
        <div class="flex-grow-1">
          <h5 class="mb-3">Nếu Cài Thủ Công: Clone repository về máy</h5>
          <pre class="bg-dark text-white p-3 rounded"><code>$:> git clone https://github.com/TeaPoly/speexdsp-ns-python.git</code></pre>
        </div>
      </div>
      <div class="d-flex align-items-start mb-4">
        <span class="badge bg-primary rounded-pill fs-5 me-3" style="min-width: 2.5rem; height: 2.5rem; line-height: 2.5rem; padding: 0;">2</span>
        <div class="flex-grow-1">
          <h5 class="mb-3">Di chuyển vào thư mục vừa clone</h5>
          <pre class="bg-dark text-white p-3 rounded"><code>$:> cd speexdsp-ns-python</code></pre>
        </div>
      </div>
      <div class="d-flex align-items-start mb-4">
        <span class="badge bg-primary rounded-pill fs-5 me-3" style="min-width: 2.5rem; height: 2.5rem; line-height: 2.5rem; padding: 0;">3</span>
        <div class="flex-grow-1">
          <h5 class="mb-3">Cài đặt thư viện</h5>
          <pre class="bg-dark text-white p-3 rounded"><code>
$:> sudo apt update
$:> sudo apt install libspeexdsp-dev -y
$:> sudo apt install swig -y
$:> sudo python3 -m pip install .</code></pre>
        </div>
      </div>
      <div class="d-flex align-items-start mb-3">
        <span class="badge bg-primary rounded-pill fs-5 me-3" style="min-width: 2.5rem; height: 2.5rem; line-height: 2.5rem; padding: 0;">4</span>
        <div class="flex-grow-1">
          <h5 class="mb-3">Kiểm tra cài đặt thành công</h5>
          <pre class="bg-dark text-white p-3 rounded"><code>python3 - << 'EOF'
from speexdsp_ns import NoiseSuppression
print("SpeexDSP NS READY")
EOF</code></pre>
          <div class="alert alert-success mt-3">
            <strong>Thành công nếu thấy dòng:</strong><br>
            <code>SpeexDSP NS READY</code>
          </div>
        </div>
      </div>
</div>
</div>
</div>



<div class="card accordion" id="accordion_button_webrtc_noise_gain">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_webrtc_noise_gain" aria-expanded="false" aria-controls="collapse_button_webrtc_noise_gain">
Cài Đặt Webrtc Noise Gain (webrtc_noise_gain) tự động điều chỉnh âm thanh mic thu được khi ở xa hoặc gần mic (nói to hoặc nói nhỏ):</h5>
<div id="collapse_button_webrtc_noise_gain" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_webrtc_noise_gain">
          <!-- Cách 1: Cài tự động (khuyên dùng nếu có file .whl sẵn) -->
          <h5 class="mt-2 mb-3 text-primary">Cách 1: Cài nhanh (tự động) – đã có sẵn file .whl trong VBot</h5>
          <div class="alert alert-info mb-4">
            Dùng khi bạn đã build hoặc tải được file wheel phù hợp (ở đây là phiên bản 1.2.5 cho cp39 armv7l)
          </div>
          <pre class="bg-dark text-light p-3 rounded mb-4"><code>$:> sudo python3 -m pip install /home/pi/VBot_Offline/resource/whl_build_file/webrtc_noise_gain-1.2.5-cp39-cp39-linux_armv7l.whl --force-reinstall</code></pre>
          <!-- Cách 2: Cài thủ công (build từ source) -->
          <h5 class="mt-5 mb-3 text-primary">Cách 2: Cài thủ công (build từ mã nguồn)</h5>
          <div class="accordion" id="installManual">
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                  Bước 1: Cập nhật hệ thống & cài công cụ cần thiết
                </button>
              </h2>
              <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#installManual">
                <div class="accordion-body">
                  <pre class="bg-dark text-light p-3 rounded mb-0"><code>$:> sudo apt update
$:> sudo apt install -y python3 python3-dev python3-pip build-essential</code></pre>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                  Bước 2: Tải source code từ GitHub
                </button>
              </h2>
              <div id="step2" class="accordion-collapse collapse" data-bs-parent="#installManual">
                <div class="accordion-body">
                  <pre class="bg-dark text-light p-3 rounded mb-0"><code>$:> git clone https://github.com/rhasspy/webrtc-noise-gain.git
$:> cd webrtc-noise-gain</code></pre>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                  Bước 3: Cài công cụ build wheel
                </button>
              </h2>
              <div id="step3" class="accordion-collapse collapse" data-bs-parent="#installManual">
                <div class="accordion-body">
                  <pre class="bg-dark text-light p-3 rounded mb-0"><code>$:> sudo python3 -m pip install wheel build</code></pre>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step4">
                  Bước 4: Build wheel
                </button>
              </h2>
              <div id="step4" class="accordion-collapse collapse" data-bs-parent="#installManual">
                <div class="accordion-body">
                  <pre class="bg-dark text-light p-3 rounded mb-0"><code>$:> sudo python3 -m build --wheel</code></pre>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step5">
                  Bước 5: Cài đặt wheel vừa build
                </button>
              </h2>
              <div id="step5" class="accordion-collapse collapse" data-bs-parent="#installManual">
                <div class="accordion-body">
                  <pre class="bg-dark text-light p-3 rounded mb-0"><code>$:> sudo python3 -m pip install dist/*.whl</code></pre>
                </div>
              </div>
            </div>
          </div> <!-- end accordion -->
          <!-- Kiểm tra cài đặt thành công -->
          <h5 class="mt-5 mb-3 text-success">Kiểm tra xem đã cài thành công chưa</h5>
          <pre class="bg-dark text-light p-3 rounded"><code>python3 - <<'EOF'
from webrtc_noise_gain import AudioProcessor
print("OK")
EOF</code></pre>
          <div class="alert alert-success mt-4">
            Nếu thấy dòng <strong>OK</strong> hiện ra → cài đặt thành công!
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