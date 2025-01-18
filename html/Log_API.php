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
<div class="form-control border-success text-info bg-dark" id="logsOutput" style="height: 500px; overflow-y: auto; white-space: pre-wrap;"></div>
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
    // Hàm gửi yêu cầu và cập nhật nội dung logs
    function fetchLogs() {
        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const logs = response.data.map(item => formatLogMessage(item.logs_message)).join('');
                        logsOutput.innerHTML = logs;
                    } else {
                        logsOutput.innerHTML = '<span style="color: red;">Lỗi: ' + response.message + '</span>';;
                    }
                } catch (e) {
                    logsOutput.innerHTML = '<span style="color: red;">Lỗi khi phân tích dữ liệu: ' + e.message + '</span>';
                }
				// Cuộn xuống dưới cùng
                logsOutput.scrollTop = logsOutput.scrollHeight;
            }
        };
        xhr.open("GET", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>/logs");
        xhr.send();
    }

    //Màu cho log messages
    function formatLogMessage(message) {
	const logStyles = [
		{ keyword: '[BOT] Đang thu âm', style: 'color: rgb(255, 105, 97);' },
		{ keyword: '[BOT]', style: 'color: rgb(255, 214, 10);' },
		{ keyword: '[HUMAN]', style: 'color: rgb(0, 255, 0);' },
		{ keyword: 'Đang chờ được đánh thức.', style: 'color: rgb(0, 255, 0);' },
		{ keyword: 'dữ liệu âm thanh', style: 'color: rgb(144, 238, 144);' },
		{ keyword: 'Không có giọng nói được truyền vào', style: 'color: rgb(221, 160, 221);' },
		{ keyword: 'Đã được đánh thức.', style: 'color: rgb(255, 182, 193);' },
		{ keyword: 'Đang phát', style: 'color: rgb(255, 165, 0);' },
		{ keyword: '[Custom skills', style: 'color: rgb(64, 224, 208);' },
		{ keyword: 'ERROR', style: 'color: rgb(255, 69, 58);' },
		{ keyword: 'WARNING', style: 'color: rgb(255, 140, 0);' },
		{ keyword: 'SUCCESS', style: 'color: rgb(50, 205, 50);' },
	];
        const style = logStyles.find(log => message.includes(log.keyword))?.style || 'color: white;';
        return '<div style="' + style + '">' + message + '</div>';
    }
    // Xử lý khi trạng thái checkbox thay đổi
    checkbox.addEventListener('change', function () {
        if (this.checked) {
            showMessagePHP("Đang hiển thị Logs trên web", 5);
            intervalId = setInterval(fetchLogs, 1000);
        } else {
            clearInterval(intervalId);
        }
    });
</script>

</body>
</html>