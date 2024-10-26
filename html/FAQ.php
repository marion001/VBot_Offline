
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
- Đăng nhập vào ssh rồi gõ lệnh sau:<br/><br/>
$: <b>sudo nano /etc/apache2/sites-available/000-default.conf</b><br/>
- Thay dòng: <b>DocumentRoot /home/pi/vietbot_offline/html</b> thành đường dẫn muốn đổi, ví dụ thay thành: <b>DocumentRoot /var/www/html</b>
<br/>- lưu lại file: <b>Ctrl + x => y => Enter</b><br/><br/>
- Tiếp theo chạy lệnh:<br/>
$: <b>sudo nano /etc/apache2/apache2.conf</b><br/>
- Thay dòng: <b>Directory /home/pi/vietbot_offline/ </b> thành: <b>Directory /var/www/html/</b><br/><br/>
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
      <li>Nhấn Nhả:
        <ul>
          <li>Bật, Tắt Microphone (chỉ có tác dụng khi đang ở chế độ chờ được đánh thức)</li>
        </ul>
        <ul>
          <li class="text-danger">Khi Đang Phát Nhạc (Nhấn sẽ Stop/Dừng Phát Nhạc)</li>
        </ul>
      </li>
      <li>Nhấn Giữ:
        <ul>
          <li>Bật, Tắt chế độ Câu Phản Hồi (chỉ có tác dụng khi đang ở chế độ chờ được đánh thức)</li>
        </ul>
      </li>
    </ul>
  </li>
  <br/>
  <li><b>Nút nhấn wakeup</b>
    <ul>
      <li>Nhấn Nhả:
        <ul>
          <li>Đánh thức Bot, Wake up (chỉ có tác dụng khi đang ở chế độ chờ được đánh thức)</li>
        </ul>
        <ul>
          <li class="text-danger">Khi Đang Phát Nhạc (Nhấn sẽ tạm Dừng Phát Nhạc và đồng thời kích hoạt Đánh thức bot/Wake UP để nghe lệnh)</li>
        </ul>
      </li>
      <li>Nhấn Giữ:
        <ul>
          <li>Bật, Tắt chế độ Hội Thoại (chỉ có tác dụng khi đang ở chế độ chờ được đánh thức)</li>
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

-dfgdfgdfg

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