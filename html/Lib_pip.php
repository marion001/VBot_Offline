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
    <style>

        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .message {
            color: #f00;
            font-weight: bold;
        }
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
      <h1>Thư Viện Python pip</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item active">lib python pip</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
	    <section class="section">
        <div class="row">
		
    <form method="post">
        <center><button type="submit" class="btn btn-primary rounded-pill" onclick="loading('show')" name="check_versions">Kiểm tra danh sách thư viện python pip</button></center>
    </form>


    <?php
    // Hàm đọc và phân tích file thành mảng File Local
    function parsePipFile($filename) {
        if (!file_exists($filename)) {
            die("<p class='message'>File <strong>$filename</strong> không tồn tại!</p>");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $startIndex = 0;
        foreach ($lines as $index => $line) {
            if (strpos($line, 'Package') !== false && strpos($line, 'Version') !== false) {
                $startIndex = $index + 2; // Bỏ qua tiêu đề và dòng gạch ngang
                break;
            }
        }

        $packages = [];
        for ($i = $startIndex; $i < count($lines); $i++) {
            $parts = preg_split('/\s{2,}/', $lines[$i]);
            if (count($parts) === 2) {
                $packages[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $packages;
    }


// Hàm đọc và phân tích chuỗi dữ liệu thành mảng URL
function parsePipString($data) {
    // Tách chuỗi thành các dòng
    $lines = explode("\n", $data);
    $startIndex = 0;
    foreach ($lines as $index => $line) {
        // Tìm dòng bắt đầu có tiêu đề
        if (strpos($line, 'Package') !== false && strpos($line, 'Version') !== false) {
            $startIndex = $index + 2; // Bỏ qua tiêu đề và dòng gạch ngang
            break;
        }
    }

    // Khởi tạo mảng lưu gói cài đặt
    $packages = [];
    for ($i = $startIndex; $i < count($lines); $i++) {
        $parts = preg_split('/\s{2,}/', $lines[$i]); // Tách theo khoảng trắng có 2 hoặc nhiều hơn
        if (count($parts) === 2) { 
            // Lưu gói và phiên bản vào mảng
            $packages[trim($parts[0])] = trim($parts[1]);
        }
    }

    return $packages;
}
    ?>
	
<?php
if (isset($_POST['check_versions'])) {

//Chạy lệnh lấy dữu liệu pip của user
$CMD = "pip list";
$connection = ssh2_connect($ssh_host, $ssh_port);
if (!$connection) {
    die("<center><h1><font color='red'>Không thể kết nối tới máy chủ SSH, Hãy Kiểm Tra Lại</font><br/><a href='Lib_pip.php'>Quay Lại</a></h1></center>");
}
if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
    die("<center><h1><font color='red'>Xác thực SSH không thành công, Hãy kiểm tra lại thông tin đăng nhập SSH</font> <br/><a href='Lib_pip.php'>Quay Lại</a></h1></center>");
}
$stream = ssh2_exec($connection, $CMD);
stream_set_blocking($stream, true);
$stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
$output = "$GET_current_USER@$HostName:~ $ $CMD\n";
$output .= stream_get_contents($stream_out);
$filePath = $VBot_Offline.'resource/pip_list_lib_user.txt';
file_put_contents($filePath, $output);
#chmod($filePath, 0777);
//END Chạy lệnh lấy dữ liệu pip của user
	
$urlParts = parse_url($Github_Repo_Vbot);
$pathParts = explode('/', trim($urlParts['path'], '/'));
$userName = $pathParts[0];
$repoName = $pathParts[1];
$mainPackages_git = file_get_contents("https://raw.githubusercontent.com/$userName/$repoName/refs/heads/main/resource/pip_list_lib.txt");
if ($mainPackages_git === FALSE) {
    $mainPackages = parsePipFile($VBot_Offline.'resource/pip_list_lib.txt');
} else {
    $mainPackages = parsePipString($mainPackages_git);
}

	
    $userPackages = parsePipFile($VBot_Offline.'resource/pip_list_lib_user.txt');
    // Kiểm tra thư viện thiếu
    $missingPackages = array_diff_key($mainPackages, $userPackages);
    // Kiểm tra thư viện sai phiên bản
    $wrongVersionPackages = [];
    foreach ($mainPackages as $name => $version) {
        if (isset($userPackages[$name]) && $userPackages[$name] !== $version) {
            $wrongVersionPackages[$name] = [
                'mainVersion' => $version,
                'userVersion' => $userPackages[$name],
            ];
        }
    }
    // Hiển thị thư viện thiếu
    if (!empty($missingPackages)) {
		echo "<h5 class='card-title text-danger'>Thư Viện Còn Thiếu:</h5>";
        echo "<table class='table table-bordered border-primary'><thead>
		<tr>
		<th style='text-align: center; vertical-align: middle;'>Tên Thư Viện</th>
		<th style='text-align: center; vertical-align: middle;'>Phiên Bản Yêu Cầu (Main)</th>
		<th style='text-align: center; vertical-align: middle;'>Lệnh Cài Đặt</th>
		<th style='text-align: center; vertical-align: middle;'>Hành Động</th>
		</tr>
		</thead><tbody>";
        foreach ($missingPackages as $name => $version) {
			$Command_pip = base64_encode('pip install '.$name.'=='.$version);
            echo "<tr><td style='text-align: center; vertical-align: middle;'>$name <a href='https://pypi.org/project/$name/' target='_bank' title='Kiểm tra thư viện: $name'><i class='bi bi-box-arrow-up-right'></i></a></td>
			<td style='text-align: center; vertical-align: middle;'>$version</td>
			<td style='text-align: center; vertical-align: middle;'><font color=blue>pip install $name==$version</font></td>
			<td style='text-align: center; vertical-align: middle;'><button type='button' class='btn btn-success rounded-pill' onclick='VBot_Command(" . json_encode($Command_pip) . ")'>Cài Đặt</button></td>
			</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<h5 class='card-title'><center><font color=green>Tất cả các thư viện python pip cần thiết đều đã được cài đặt</font></center></h5>";
    }

    // Hiển thị thư viện sai phiên bản
    if (!empty($wrongVersionPackages)) {
		echo "<h5 class='card-title text-danger'>Thư Viện Bị Sai Phiên Bản:</h5>";
        echo "<table class='table table-bordered border-primary'>
		<thead><tr>
		<th style='text-align: center; vertical-align: middle;'>Tên Thư Viện</th>
		<th style='text-align: center; vertical-align: middle;'>Phiên Bản Yêu Cầu (Main)</th>
		<th style='text-align: center; vertical-align: middle;'>Phiên Bản Hiện Tại Của Bạn (User)</th>
		<th style='text-align: center; vertical-align: middle;'>Lệnh Cài Đặt</th>
		<th style='text-align: center; vertical-align: middle;'>Hành Động</th>
		</tr></thead><tbody>";
        foreach ($wrongVersionPackages as $name => $versions) {
			$Command_pip = base64_encode('pip install '.$name.'=='.$versions['mainVersion']);
            echo "<tr>
			<td style='text-align: center; vertical-align: middle;'>$name <a href='https://pypi.org/project/$name/' target='_bank' title='Kiểm tra $name'><i class='bi bi-box-arrow-up-right'></i></a></td>
			<td style='text-align: center; vertical-align: middle;'><font color=green>{$versions['mainVersion']}</font></td>
			<td style='text-align: center; vertical-align: middle;'><font color=red>{$versions['userVersion']}</font></td>
			<td style='text-align: center; vertical-align: middle;'><font color=blue>pip install $name=={$versions['mainVersion']}</font></td>
			<td style='text-align: center; vertical-align: middle;'><button type='button' class='btn btn-success rounded-pill' onclick='VBot_Command(" . json_encode($Command_pip) . ")'>Cài Đặt</button></td>
			</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<h5 class='card-title'><center><font color=green>Tất cả các thư viện python pip đều đúng phiên bản</font></center></h5>";
    }
}

?>
	


 
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