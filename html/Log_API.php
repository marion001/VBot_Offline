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
      <h1>Logs Hệ Thống (API)</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">Logs API</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
	    <section class="section">
        <div class="row">
		
	<div class="col-lg-12">

          <div class="card">
            <div class="card-body">
<br/>
<center>
<button type="button" class="btn btn-primary">
<input class="form-check-input" title="Bật để hiển thị Logs" type="checkbox" name="fetchLogsCheckbox" id="fetchLogsCheckbox">
<label class="form-check-label" for="fetchLogsCheckbox" title="Thiết lập trong: Cấu hình Config->Đồng bộ trạng thái với Web UI"> Hiển thị Logs</label>
</button>
<button type="button" class="btn btn-danger" onclick="change_og_display_style('clear_api', 'clear_api', 'false')"><i class="bi bi-trash"></i> Xóa logs</button>
</center>
<div class="form-group">
<br/>
    <textarea class="form-control border-success text-info bg-dark" id="logsOutput" rows="17" readonly></textarea>
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

<!-- Nghe thử file âm thanh -->
<audio id="audioPlayer" style="display: none;" controls></audio>

  <!-- Template Main JS File -->
<?php
include 'html_js.php';
?>
    <script>
        const checkbox = document.getElementById('fetchLogsCheckbox');
        const logsOutput = document.getElementById('logsOutput');
        let intervalId;
		function fetchLogs() {
			const xhr = new XMLHttpRequest();
			xhr.withCredentials = true;
			xhr.addEventListener("readystatechange", function () {
				if (this.readyState === 4) {
					try {
						if (this.status === 0) {
							logsOutput.value = "Không thể kết nối đến VBot vui lòng kiểm tra lại, hoặc chế độ API không được bật khi khởi chạy chương trình";
							return;
						}
						if (this.status !== 200) {
							logsOutput.value = "Lỗi từ máy chủ: HTTP " +this.status;
							return;
						}
						if (this.responseText.trim() === "") {
							logsOutput.value = "Phản hồi từ server bị trống.";
							return;
						}
						const response = JSON.parse(this.responseText);
						if (response.success) {
							const logs = response.data.map(item => item.logs_message).join('\n');
							logsOutput.value = logs;
							logsOutput.scrollTop = logsOutput.scrollHeight;
						} else {
							logsOutput.value = 'Lỗi: ' + response.message;
						}
					} catch (e) {
						logsOutput.value = 'Lỗi khi phân tích dữ liệu: ' + e.message;
					}
				}
			});
			xhr.onerror = function () {
				logsOutput.value = "Không thể kết nối đến máy chủ. Kiểm tra kết nối mạng hoặc xem máy chủ có đang chạy hay không, hoặc chế độ API không được bật khi khởi chạy chương trình";
			};
			xhr.open("GET", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>/logs");
			xhr.send();
		}
        checkbox.addEventListener('change', function() {
            if (this.checked) {
				showMessagePHP("Đang hiển thị Logs trên web", 3);
                intervalId = setInterval(fetchLogs, 1000);
            } else {
                clearInterval(intervalId);
            }
        });
    </script>

</body>
</html>