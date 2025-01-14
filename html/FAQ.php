
<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
?>
<?php
if ($Config['contact_info']['user_login']['active']){
session_start();
// Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
if (!isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))) {
    
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
    </div><!-- End Page Title -->
	    <section class="section">
        <div class="row">
		<div id="accordion">
  <div class="card">
  <br/>
<div class="card-body">

<div class="alert alert-success" role="alert">
Link Tải Xuống IMG: <a href="https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ" target="_bank">https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>
</div>


<div class="card accordion" id="accordion_button_mic_tetser">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_mic_tetser" aria-expanded="false" aria-controls="collapse_button_mic_tetser">
Cách Cài Đặt Kiểm Tra Mic Và Scan Lấy ID Mic:</h5>
<div id="collapse_button_mic_tetser" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_mic_tetser">
- Đầu tiên chạy lệnh sau để di chuyển tới thư mục <b>test_device</b>:<br/>
<b>$:> cd /home/pi/VBot_Offline/resource/test_device</b><br/>
- Chạy file <b>Scan_Mic.py</b> để tiến hành liệt kê mic và ID<br/>
<b>$:> python3 Scan_Mic.py</b><br/>
- Trên giao diện Terminal sẽ hiển thị các ID và Tên Tên Thiết Bị tương ứng<br/>
- Thay lần lượt ID vừa scan đó vào trong file <b>Test_Mic.py</b> (ở dòng số 12 có giá trị <b>device_index=14</b>, hãy thay số 14 thành ID mic của bạn)<br/>
- Sau đó chạy file <b>Test_Mic.py</b> để kiểm tra Mic có đúng và hoạt động không:<br/>
<b>$:> python3 Test_Mic.py</b><br/>
- File đó sẽ thu âm trong khoảng 6 giây, bạn cần nói vào Mic khi thu âm xong sẽ xuất ra file <b>Test_Microphone.wav</b><br/>
- Bạn cần mở file đó và nghe xem có âm thanh được thu không hoặc chạy lệnh sau:<br/>
<b>$:> vlc Test_Microphone.wav</b><br/>
- Nếu không được bạn cần thử lần lượt các ID được scan và kiểm tra lại driver được cài tương ứng với MIC của bạn chưa<br/>
- Nếu thành công bạn hãy điền ID Mic đó vào trong cấu hình Config rồi lưu Config lại là được
</div>
</div>
</div>

<div class="card accordion" id="accordion_button_media_player_source">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player_source" aria-expanded="false" aria-controls="collapse_button_media_player_source">
Hướng Dẫn Cài Đặt, Kiểm Tra Loa, Âm Thanh Đầu Ra:</h5>
<div id="collapse_button_media_player_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_media_player_source">
- Nếu Là Mạch DAC i2s có thể tham khảo cách cài Driver theo Link Sau:<br/>
- <a href="https://drive.google.com/drive/folders/1KJIuovEbRGv82uc5FCfi5p0sY1o5W5vU" target="_bank">https://drive.google.com/drive/folders/1KJIuovEbRGv82uc5FCfi5p0sY1o5W5vU</a>
<br/><br/>
- Bạn cần phát 1 file âm thanh bằng VLC, có thể tải lên file âm thanh của bạn như XXX.mp3 vào <b>/home/pi</b> chẳng hạn<br/>
- Tiếp tới hãy chạy file âm thanh đó bằng lệnh sau:<br/>
<b>$:> vlc XXX.mp3</b><br/>
- Nếu có âm thanh được phát ra là xong, không cần cấu hình gì nữa cả<br/>
- Nếu không có âm thanh được phát ra bạn cần cài cấu hình và sét đầu ra âm thanh mặc định trong  <b>alsamixer</b> (tùy mỗi thiết bị mà có cấu hình khác nhau)<br/>
- Sau đó bạn cần chạy lệnh <b>$:> alsamixer</b> và xác định xem thiết bị đó có tên là gì<br/>
- Khi đã xác định được tên thiết bị bạn cần điền tên đó vào trong tab <b>Cấu Hình config</b>: <b>Tên thiết bị (alsamixer)</b> (được dùng để sét âm lượng đầu tiên khi chạy chương trình, trong quá trình chạy VBot sẽ chỉ thay đổi âm lượng cửa vlc mà không ảnh hưởng gì tới âm lượng trên hệ thống của bạn)
</div>
</div>
</div>

<div class="card accordion" id="accordion_button_1">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_1" aria-expanded="false">
Nâng Cấp Full Dung Lượng Cho Thẻ Nhớ:
</h5><div id="collapse_button_1" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_1">

- Đăng nhập vào ssh rồi gõ lệnh sau:<br/>
$: <b>sudo raspi-config</b><br/>
- Chọn: <b>(6)Advance Options</b> -> <b>(A1)Expand File System</b> đợi vài giây -> <b>OK</b> -> <b>Fish</b> -> <b>Yes</b> để rebot

</div>
</div>
</div>

<div class="card accordion" id="accordion_button_2">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_2" aria-expanded="false">
Thay Đổi Đường Dẫn (Path) Của Apache2:
</h5><div id="collapse_button_2" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_2">
<font color=red>Thay Đổi Tự Động:</font><br/>
Di chuyển tới thư mục sau bằng lệnh:<br/>
$:> <b>cd /home/pi/VBot_Offline/resource/test_device/</b><br/><br/>

- Chạy file Change_Path_Apache2.py bằng quyền sudo bằng lệnh:<br/>
$:> <b>sudo python3 Change_Path_Apache2.py</b><br/><br/>

- Hệ thống sẽ yêu cầu nhập đường dẫn path trỏ tới WebUI mới, bạn hãy nhập dòng sau và nhấn Enter là xong:<br/>
<b>/home/pi/VBot_Offline/html</b>
<hr/>

<font color=red>Nếu bạn muốn thay đổi bằng tay</font><br/>
- Đăng nhập vào ssh rồi gõ lệnh sau:<br/><br/>
$: <b>sudo nano /etc/apache2/sites-available/000-default.conf</b><br/>
- Thay dòng: <b>DocumentRoot /home/pi/VBot_Offline/html</b> thành đường dẫn muốn đổi, ví dụ thay thành: <b>DocumentRoot /var/www/html</b>
<br/>- lưu lại file: <b>Ctrl + x => y => Enter</b><br/><br/>
- Tiếp theo chạy lệnh:<br/>
$: <b>sudo nano /etc/apache2/apache2.conf</b><br/>
- Thay dòng: <b>Directory /home/pi/VBot_Offline/ </b> thành: <b>Directory /var/www/html/</b><br/><br/>
- Sau đó restart lại appache2 bằng lệnh sau:<br/>
$: <b>sudo systemctl restart apache2</b>
</div>
</div>
</div>

<div class="card accordion" id="accordion_button_3">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_3" aria-expanded="false">
Tăng Giới Hạn Tải Lên File Trên WebUI:
</h5><div id="collapse_button_3" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_3">
- Chạy 2 lệnh sau:<br/>
$: <b>sudo nano sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 300M/' /etc/php/7.4/apache2/php.ini</b><br/>
$: <b>sudo sed -i 's/post_max_size = .*/post_max_size = 350M/' /etc/php/7.4/apache2/php.ini</b><br/><br/>
- Lưu Ý: <b>Bạn cần chỉnh sửa đường dẫn /etc/php/7.4/apache2/php.ini trong câu lệnh cho phù hợp với phiên bản và đường dẫn file php.ini của bạn</b>
</div>
</div>
</div>

<div class="card accordion" id="accordion_button_4">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_4" aria-expanded="false">
Đăng Nhập, Đặt Mật Khẩu, Quên Mật Khẩu WebUI:
</h5><div id="collapse_button_4" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_4">
- Bật Đăng Nhập Vào WebUI:<br/>
- Thao Tác: Nhấn vào Avatar trên cùng bên phải chọn: <b>Cá nhân => Cài đặt => Đăng nhập Web UI => (Bật Lên Là Được)</b><br/><br/>
- Đặt Mật Khẩu Đăng Nhập WebUI:<br/>
- Thao Tác: Nhấn vào Avatar trên cùng bên phải chọn: <b>Cá nhân => Chỉnh sửa hồ sơ => Mật khẩu Web UI => (Điền Mật Khẩu Của Bạn)</b><br/>
- Lưu Ý: <b>Bạn cần nhập cả địa chỉ Email để dùng cho trường hợp Quên Mật Khẩu</b><br/><br/>
<b>- Quên Mật Khẩu và Đổi Mật Khẩu Cũng Nằm Trên Thanh Tác Vụ, Tương Tự Thao Tác Như Trên.</b><br/>
</div>
</div>
</div>

<div class="card accordion" id="accordion_button_5">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_5" aria-expanded="false">
Nút Nhấn và Hành Động Của Nút:
</h5><div id="collapse_button_5" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_5">
<ul>
  <li><b>Nút nhấn up</b>
    <ul>
      <li>Nhấn Nhả:
        <ul>
          <li>Tăng âm lượng (Tăng theo bước nhấn, được thiết lập trong Config.json)</li>
        </ul>
      </li>
      <li>Nhấn Giữ:
        <ul>
          <li>Tăng âm lượng cao nhất (Giá trị cao nhất được thiết lập trong Config.json)</li>
        </ul>
      </li>
    </ul>
  </li>
  <br/>
  <li><b>Nút nhấn down</b>
    <ul>
      <li>Nhấn Nhả:
        <ul>
          <li>Giảm âm lượng (Giảm theo bước nhấn, được thiết lập trong Config.json)</li>
        </ul>
      </li>
      <li>Nhấn Giữ:
        <ul>
          <li>Giảm âm lượng xuống thấp nhất (Giá trị thấp nhất được thiết lập trong Config.json)</li>
        </ul>
      </li>
    </ul>
  </li>
  <br/>
  
<li><b>Nút nhấn mic</b>
    <ul>
        <li><font color="green">Ở Trạng Thái Chờ Được Đánh Thức:</font>
            <ul><font color="blue">
                <li>Nhấn Nhả:
                    <ul>
                        <li>Bật, Tắt Microphone</li>
                    </ul>
                </li>
                <li>Nhấn Giữ:
                    <ul>
                        <li>Bật, Tắt chế độ câu phản hồi</li>
                    </ul>
                </li></font>
            </ul>
        </li>
        <li><font color="green">Ở Trạng Thái Đang Phát Nhạc (Media Player):</font>
            <ul><font color="blue">
                <li>Nhấn Nhả:
                    <ul>
                        <li>Tạm Dừng hoặc Tiếp Tục phát nhạc</li>
                    </ul>
                </li>
                <li>Nhấn Giữ:
                    <ul>
                        <li>Dừng phát nhạc</li>
                    </ul>
                </li></font>
            </ul>
        </li>
    </ul>
</li>


<br/>

<li><b>Nút nhấn Wake Up</b>
    <ul>
        <li><font color="green">Ở Trạng Thái Chờ Được Đánh Thức:</font>
            <ul><font color="blue">
                <li>Nhấn Nhả:
                    <ul>
                        <li>Đánh Thức Bot, Wake Up</li>
                    </ul>
                </li>
                <li>Nhấn Giữ:
                    <ul>
                        <li>Bật, Tắt chế độ hội thoại (hỏi đáp liên tục)</li>
                    </ul>
                </li></font>
            </ul>
        </li>
        <li><font color="green">Ở Trạng Thái Đang Phát Nhạc (Media Player):</font>
            <ul><font color="blue">
                <li>Nhấn Nhả:
                    <ul>
                        <li>Tạm Dừng phát nhạc đồng thời đánh thức Bot (Wake Up) để nghe lệnh</li>
                    </ul>
                </li>
                <li>Nhấn Giữ:
                    <ul>
                        <li>Dừng Phát Nhạc</li>
                    </ul>
                </li></font>
            </ul>
        </li>
    </ul>
</li>
  
</ul>
</div>
</div>
</div>
	
<div class="card accordion" id="accordion_button_6">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_6" aria-expanded="false">
Cấu Hình Auto/Tự Động Chạy VBot Cùng Hệ Thống:
</h5><div id="collapse_button_6" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_6">

- Di chuyển tới Tab: <b>Command/Terminal</b><br/>
- Chọn vào: <b>VBot Auto Run => Cài đặt cấu hình Auto</b> (Hệ thống sẽ tự động tạo và cài đặt file cấu hình VBot để khởi động cùng hệ thống)<br/><br/>
- Cài đặt xong, tiếp tục chọn vào: <b>VBot Auto Run => Kích Hoạt</b> (Hệ thống sẽ tự động khởi chạy Chương Trình VBot khi thiết bị khởi động xong)<br/><br/>

- Các tùy chọn điều khiển khác liên quan tới Auto khởi động cũng sẽ nằm trong: <b>Command/Terminal => VBot Auto Run</b>
</div>
</div>
</div>
	
<div class="card accordion" id="accordion_button_7">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_7" aria-expanded="false">
Cấu Hình Auto Kết Nối Wifi hoặc Tạo Điểm Truy Cập AP :
</h5><div id="collapse_button_7" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_7">
- Truy Cập Tab: <b>Command/Terminal</b> -> <b>OS Wifi</b>
<br/>
<b>- Restart Auto Wifi Manager:</b> Khởi động lại Services Auto Wifi Manaager đang chạy trên hệ thống<br/>
<b>- Enable Auto Wifi Manager:</b> Kích Hoạt Services Auto Wifi Manaager trên hệ thống (Mặc định là đã kích hoạt từ trước rồi)<br/>
<b>- Install Auto Wifi Manager:</b> Chỉ Cài Đặt Auto Wifi Manager Và Tạo Điểm truy Cập AP, Tự động kết nối lại khi mất mạng hoặc hệ thống mạng khởi động sau Vbot<br/>
<b>- Install Auto Wifi Manager + Đọc IP:</b> Cài Đặt Auto Wifi Manager + Đọc Địa Chỉ IP Ra Loa Và Tạo Điểm truy Cập AP,
	Tự động kết nối lại khi mất mạng hoặc hệ thống mạng khởi động sau Vbot,
	Nếu phát hiện địa chỉ IP Local bị thay đổi hoặc mạng wifi trước đo bị thay đổi sẽ tự đọc địa chỉ ip local mới<br/>
<b>- Logs Auto Wifi Manager:</b> Kiểm Tra Logs Quá Trình Hoạt Động<br/>
<b>- Status Auto Wifi Manager:</b> Kiểm tra trạng thái xem là: đang hoạt động hay không<br/>
</div>
</div>
</div>


<div class="card accordion" id="accordion_button_oled_i2c">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_oled_i2c" aria-expanded="false" aria-controls="collapse_button_oled_i2c">
Kết Nối Màn Hình I2C:</h5>
<div id="collapse_button_oled_i2c" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_oled_i2c">
Loại Màn Hình Đang Được Hỗ Trợ: <b>OLED 128X64 0.96 INCH 1306 giao tiếp I2C</b><br/>
Sơ Đồ Kết Nối Chân Pin Với GPIO (Loại Giao Tiếp i2c 4 chân Pin):<br/>
<b>
- SDA ==> GPIO2 (Pin 3)<br/>
- SCL ==> GPIO3 (Pin 5)<br/>
- VCC ==> 3.3V (Pin 1)<br/>
- GND ==> GND(Pin 14)<br/>
</b>
Hình Ảnh Sơ Đồ Chân Kết Nối: <a href="https://github.com/user-attachments/assets/655e0b0a-4891-4a6f-aab8-19c5feb139ed" target="_bank"> Xem Ảnh Kết Nối</a><br/>
Khi kết nối xong các chân cần chạy lệnh sau để kiểm tra xem màn hình đã được nhận diện với địa chỉ 3c chưa:<br/>
<b>$:> sudo i2cdetect -y 1</b><br/>
Nếu nhận diện thành công sẽ có địa chỉ 3c như ảnh: <a href="https://github.com/user-attachments/assets/7aa88c10-7763-422d-ac48-1ad31339fe6f" target="_bank">Xem Ảnh</a>

<br/>
Tài Liệu Tham Khảo:<br/>
- <a href="https://github.com/adafruit/Adafruit_Python_SSD1306" target="_bank">https://github.com/adafruit/Adafruit_Python_SSD1306</a><br/>
- <a href="https://www.the-diy-life.com/add-an-oled-stats-display-to-raspberry-pi-os-bullseye/" target="_bank">https://www.the-diy-life.com/add-an-oled-stats-display-to-raspberry-pi-os-bullseye/</a>
- <a href="https://www.instructables.com/Raspberry-Pi-Monitoring-System-Via-OLED-Display-Mo/" target="_bank">https://www.instructables.com/Raspberry-Pi-Monitoring-System-Via-OLED-Display-Mo/</a>

</div>
</div>
</div>

<div class="card accordion" id="accordion_button_tao_gcloud_driver_json">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_tao_gcloud_driver_json" aria-expanded="false" aria-controls="collapse_button_tao_gcloud_driver_json">
Tạo Tệp Json Google Driver Backup:</h5>
<div id="collapse_button_tao_gcloud_driver_json" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_tao_gcloud_driver_json">
<a href="https://docs.google.com/document/d/1-VTi9MOAgQoR8jZrhN9FlZxjWsq2vDuy/edit?usp=drive_link&ouid=106149318613102395200&rtpof=true&sd=true" target="_bank">Nhấn Để Mở File Hướng Dẫn Tạo Json GDriver</a>
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