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
      <h1>Logs Service VBot</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item active">Logs Service VBot</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
	    <section class="section">
        <div class="row">
          <div class="card">
            <div class="card-body">
<br/>
<center>
<?php
$logDirectory = $VBot_Offline.'resource/log';
$logFiles = glob($logDirectory . '/*.log');
if (!empty($logFiles)) {
	echo '<div class="input-group">';
    echo '<select class="form-select border-success" name="logFileSelect" id="logFileSelect">';
    echo '<option value="">-- Chọn một file log --</option>';
    foreach ($logFiles as $file) {
        $fileName = basename($file);
        echo '<option value="' . htmlspecialchars($file) . '">' . htmlspecialchars($fileName) . '</option>';
    }
    echo '</select><button class="btn btn-success border-success" type="button" name="select_log_file_red" id="select_log_file_red"><i class="bi bi-eye"></i> Xem</button><button class="btn btn-danger border-success" type="button" onclick="delete_logs()"><i class="bi bi-trash"></i> Xóa Logs</button></div>';
	echo '<br/><textarea class="form-control border-success text-info bg-dark" name="logsOutput" id="logsOutput" rows="17" readonly></textarea>';
} else {
    echo '<p>Chưa Có File Log Nào Được Sinh Ra</p>';
}
?>
</center>
</div>
</div>
</div>
</section>
</main>

  <!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<script>
document.getElementById('select_log_file_red').addEventListener('click', function() {
    var selectedFile = document.getElementById('logFileSelect').value;
    if (selectedFile) {
		var fileName = selectedFile.split('/').pop();
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'includes/php_ajax/Show_file_path.php?read_file_path&file=' + encodeURIComponent(selectedFile), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
					showMessagePHP("Đã tải dữ liệu log", 3);
                    if (response.data.trim() !== "") {
                        document.getElementById('logsOutput').innerHTML = response.data;
                    } else {
                        document.getElementById('logsOutput').innerHTML = 'Không có dữ liệu trong file log: '+fileName;
                    }
                }
            } else {
                document.getElementById('logsOutput').innerHTML = 'Không thể tải dữ liệu từ server.';
            }
        };
        xhr.send();
    } else {
		document.getElementById('logsOutput').innerHTML = 'Vui lòng chọn file Logs để xem';
		show_message('Vui lòng chọn file Logs để xem');
    }
});

//Xóa Logs
function delete_logs() {
  var selectElement = document.getElementById('logFileSelect');
  var selectedValue = selectElement.value;
  if (!selectedValue) {
    show_message('Vui Lòng Chọn File Logs để xóa dữ liệu');
  } else {;
    var xhr = new XMLHttpRequest();
    var url = "includes/php_ajax/Show_file_path.php?empty_the_file&file_path=" + encodeURIComponent(selectedValue);
    xhr.open("GET", url);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var response = JSON.parse(xhr.responseText);
          if (response.success) {
            showMessagePHP(response.message, 3);
          } else {
            showMessagePHP(response.message, 3);
          }
        } catch (e) {
		  show_message('Lỗi khi phân tích JSON!' +e);
        }
      }
    };
    xhr.send();
  }
}



</script>
<?php
include 'html_js.php';
?>

</body>
</html>