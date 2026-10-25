<?php
include 'Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))) { header('Location: Login.php'); exit; }
if (empty($_SESSION['vbot_csrf_token']) || !is_string($_SESSION['vbot_csrf_token']) || strlen($_SESSION['vbot_csrf_token'])<64) $_SESSION['vbot_csrf_token']=bin2hex(random_bytes(32));
$panCsrf=$_SESSION['vbot_csrf_token'];
?>
<!DOCTYPE html><html lang="vi">
<?php include 'html_head.php'; session_write_close(); ?>
<body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main">
  <div class="pagetitle"><h1>Mạng Internet - Bluetooth PAN - Network</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li><li class="breadcrumb-item active">Bluetooth PAN - Network</li></ol></nav></div>
  <section class="section">
   
    <div class="card"><div class="card-body pt-3">
      <div class="row mb-3"><label class="col-sm-3 col-form-label">Kích hoạt:</label><div class="col-sm-9"><div class="form-switch pt-2"><input class="form-check-input border-success" type="checkbox" id="pan-enabled" disabled></div></div></div>
      <p class="small text-danger">Tắt để ngừng sử dụng mạng internet thông qua Bluetooth, kể cả tự kết nối. Bluetooth phát nhạc vẫn dùng được. Bật lại sẽ cho phép sử dụng PAN theo tùy chọn tự kết nối của từng điện thoại ,Nhấn Lưu thay đổi để áp dụng.</p>
      <center><button type="button" class="btn btn-success" id="pan-save-enabled" disabled><i class="bi bi-save"></i> Lưu thay đổi</button> 
	  <a class="btn btn-primary" href="FAQ.php#faq-bluetooth-pan">Hướng Dẫn Cài Đặt Bluetooth PAN</a>
	   <a class="btn btn-primary" href="Command.php#bluetooth-pan-setup">Cài đặt Bluetooth PAN - Network</a></center>
    </div></div>
    <div class="card"><div class="card-body pt-3">
      <div class="d-flex justify-content-between align-items-center"><h2 class="h5">Trạng thái mạng</h2><button type="button" class="btn btn-primary btn-sm" id="pan-refresh"><i class="bi bi-arrow-clockwise"></i> Làm mới</button></div>
      <div id="pan-notice" class="alert alert-info mt-3" role="status">Đang tải…</div>
      <div class="d-flex flex-wrap gap-2 align-items-center mb-3"><button type="button" class="btn btn-primary btn-sm" id="pan-probe">Kiểm tra Internet qua PAN</button><span class="small text-muted" id="pan-internet">Chưa kiểm tra Internet.</span></div>
      <p class="small text-muted">Nhấn Làm mới để cập nhật trạng thái. Kiểm tra Internet chỉ chạy khi nhấn nút; kết quả được giữ tối đa 5 phút.</p>
      <dl class="row mb-0"><dt class="col-sm-3">Tên Bluetooth của loa</dt><dd class="col-sm-9" id="pan-alias">—</dd><dt class="col-sm-3">Đường mạng ưu tiên</dt><dd class="col-sm-9" id="pan-route">—</dd><dt class="col-sm-3">IP Bluetooth / WebUI</dt><dd class="col-sm-9" id="pan-address">—</dd></dl>
    </div></div>
    <div class="card"><div class="card-body pt-3">
      <h2 class="h5">Điện thoại chia sẻ Internet</h2>
      <p>Bật dữ liệu di động và chia sẻ mạng trên điện thoại. <br/>- iPhone: Điểm truy cập cá nhân → Cho phép người khác kết nối<br/>- Android: Chia sẻ kết nối qua Bluetooth. PAN dùng chung tên Bluetooth và danh sách ghép đôi của loa.</p>
      <p class="small text-muted">Ghép đôi điện thoại trước rồi nhấn Làm mới. Auto VBot ưu tiên Wi-Fi; khi mất Wi-Fi sẽ dùng PAN của điện thoại đang kết nối Bluetooth. AP cấu hình mạng vẫn hoạt động khi chưa có Wi-Fi, kể cả khi PAN có Internet. Không cần chạy VBot.</p>
      <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Thiết Bị / MAC</th><th>Kết nối Bluetooth</th><th>Trạng thái PAN - Internet</th><th>Tự kết nối PAN <i class="bi bi-question-circle" role="button" tabindex="0" aria-label="Giải thích tự kết nối PAN" onclick="show_message('- Auto VBot: ưu tiên Wi-Fi, khi mất Wi-Fi sẽ dùng PAN - Internet của điện thoại đang kết nối Bluetooth, và sẽ tự ngắt kết nối PAN - Internet khi có Wi-Fi kết nối trở lại. AP cấu hình vẫn phát ra khi chưa có, kết nối với mạng Wi-Fi<br/>-  Bật: luôn tự kết nối PAN - Internet khi Bluetooth kết nối tới thiết bị di động<br/>- Tắt: không tự kết nối PAN - Internet, vẫn có thể kết nối thủ công. Điện thoại cần bật chia sẻ mạng, công tắc PAN chung có ưu tiên cao nhất.')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}"></i></th><th>Thao tác</th></tr></thead><tbody id="pan-devices"></tbody></table></div>
      <p class="small text-muted">Ngắt PAN khi đang truy cập qua IP Bluetooth sẽ làm mất kết nối WebUI. Chức năng này không ngắt Bluetooth phát nhạc. Có IP PAN chưa đảm bảo điện thoại cho phép mở WebUI; cần thử trực tiếp.</p>
    </div></div>
  </section>
</main>
<?php include 'html_footer.php'; include 'html_js.php'; ?>
<script>window.vbotPanCsrf=<?= json_encode($panCsrf,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script src="assets/js/bluetooth-pan.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
</body></html>
