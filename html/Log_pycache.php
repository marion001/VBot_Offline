<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
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
// Đường dẫn tới thư mục
$directory = $VBot_Offline."__pycache__";
// Xóa tất cả các tệp trong thư mục nếu yêu cầu POST 'delete' được gửi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_all_file'])) {
    $files = scandir($directory);
    $files = array_diff($files, array('.', '..'));
    foreach ($files as $file) {
        $filePath = $directory . '/' . $file;
        if (is_file($filePath)) {
            unlink($filePath); // Xóa file
        }
    }
}

// Hàm chuyển đổi kích thước file sang KB, MB, GB
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
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
      <h1>Logs __pycache__</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
      
          <li class="breadcrumb-item active">__pycache__</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Bảng dữ liệu Logs __pycache__</h5>
     
              <!-- Table with stripped rows -->
			  <?php

				



if (is_dir($directory)) {


echo '<table class="table datatable">
<thead>
<tr>
<th>STT</th>
<th>Tên</th>
<th>Thời Gian Tạo</th>
<th>Kích Thước</th>
<th>Hành Động</th>
</tr>
</thead>
<tbody>';
// Mở thư mục và đọc các file
$files = scandir($directory);
$i = 0;
$size_all = 0;
// Lọc bỏ các thư mục đặc biệt '.' và '..'
$files = array_diff($files, array('.', '..'));
        // Hiển thị từng tệp dưới dạng dòng trong bảng
        foreach ($files as $file) {
			$filePath = $directory . '/' . $file;
			 // Lấy kích thước file
            $size = formatSizeUnits(filesize($filePath));
            // Lấy thời gian tạo file và định dạng lại
            $date = date("d-m-Y H:i:s", filemtime($filePath));
			$i++;
			$size_all += filesize($filePath);
            echo "<tr>
					<td>$i</td>
                    <td>$file</td>
                    <td>$date</td>
                    <td>$size</td>
                    <td>
					<button type=\"button\" class=\"btn btn-danger\" title=\"Xóa file: $file\" onclick=\"deleteFile('$filePath')\"><i class=\"bi bi-trash\"></i></button>
					<button type=\"button\" class=\"btn btn-success\" title=\"Tải xuống file: $file\" onclick=\"downloadFile('$filePath')\"><i class=\"bi bi-download\"></i></button>
					</td>
                  </tr>";
        }
echo '</tbody>
      </table>
      <form method="post" action="">
          <center>
              <button type="submit" onclick="loading(\'show\')" name="delete_all_file" class="btn btn-danger rounded-pill">
                  Xóa tất cả dữ liệu: ' . $i . ' Tệp, ' . formatSizeUnits($size_all) . '
              </button>
          </center>
      </form>';

} else {
    echo "- Không có dữ liệu cho Logs <b>__pycache__</b>";
}
?>







            </div>
          </div>

        </div>
      </div>
    </section>

  </main><!-- End #main -->
<!-- End #main -->
<!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- Nghe thử file âm thanh -->
<!-- <audio id="audioPlayer" style="display: none;" controls></audio> -->

  <!-- Template Main JS File -->
<?php
include 'html_js.php';
?>


</body>

</html>
