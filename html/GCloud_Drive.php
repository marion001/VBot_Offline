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
<?php


if ($google_cloud_drive_active === true){

// Khởi tạo biến tạm để lưu trữ thông báo
$notifications = [];

$activve_show = true;
// Hàm định dạng dung lượng (byte) thành đơn vị dễ đọc hơn
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Hàm kiểm tra và tạo thư mục nếu chưa tồn tại
function createDirectoryIfNotExists($filePath) {
	global $notifications;
    $directoryPath = dirname($filePath);
    if (!is_dir($directoryPath)) {
        if (mkdir($directoryPath, 0777, true)) {
            //$notifications[] = "Đã tạo thư mục: $directoryPath\n";
        } else {
            $notifications[] = "Không thể tạo thư mục: $directoryPath\n";
            //exit;
        }
    }
}

// Hàm kiểm tra và tạo file nếu chưa tồn tại, và thiết lập quyền chmod
function createFileIfNotExists($filePath, $initialContent = '{}') {
	global $notifications;
	// Nội dung mặc định là '{}'
    if (!file_exists($filePath)) {
        if (file_put_contents($filePath, $initialContent)) {
            //$notifications[] = "Đã tạo file: $filePath với nội dung mặc định\n";
            
            // Thiết lập quyền chmod 777 cho file
            if (chmod($filePath, 0777)) {
                //$notifications[] = "Đã thiết lập quyền 777 cho file: $filePath\n";
            } else {
                $notifications[] =  "Không thể thiết lập quyền 777 cho file: $filePath\n";
            }
        } else {
            $notifications[] = "Không thể tạo file: $filePath\n";
            //exit; 
        }
    }
}




// Đường dẫn đến file lưu trữ token 
$authConfigPath = 'includes/other_data/Google_Driver_PHP/client_secret.json';

// Đường dẫn đến tệp xác thực JSON
$tokenPath = 'includes/other_data/Google_Driver_PHP/verify_token.json';


$base_directory = '/home/' . $GET_current_USER . '/_VBot_Library';
$client_directory = $base_directory . '/google-api-php-client';
$LIB_Google_API_PHP_CLIENT = $client_directory . '/vendor/autoload.php';

/*
if (isset($_GET['code'])) {
	echo $_GET['code'];
    //header('Location: https://redirectmeto.com/http://192.168.14.113/html/GCloud_Drive.php');
}
*/

if (isset($_POST['del_all_data_gcloud_drive'])) {
	
// Xóa file client_secret.json
if (file_exists($authConfigPath)) {
    if (unlink($authConfigPath)) {
        $notifications[] = "File <strong>" . htmlspecialchars(basename($authConfigPath)) . "</strong> đã được xóa thành công.<br/>";
    } else {
        $notifications[] =  "Không thể xóa file <strong>" . htmlspecialchars(basename($authConfigPath)) . "</strong>.<br/>";
    }
} else {
    $notifications[] = "File <strong>" . htmlspecialchars(basename($authConfigPath)) . "</strong> không tồn tại.<br/>";
}

// Xóa file verify_token.json
if (file_exists($tokenPath)) {
    if (unlink($tokenPath)) {
        $notifications[] =  "File <strong>" . htmlspecialchars(basename($tokenPath)) . "</strong> đã được xóa thành công.<br/>";
    } else {
        $notifications[] =  "Không thể xóa file <strong>" . htmlspecialchars(basename($tokenPath)) . "</strong>.<br/>";
    }
} else {
    $notifications[] = "File <strong>" . htmlspecialchars(basename($tokenPath)) . "</strong> không tồn tại.<br/>";
}

echo '<script type="text/javascript">window.location.href = window.location.href;</script>';
}




#$data_read_authConfigPath = json_decode($read_authConfigPath, true);


// Kiểm tra và tạo thư mục và file cho tokenPath
createDirectoryIfNotExists($tokenPath);
createFileIfNotExists($tokenPath);

// Kiểm tra và tạo thư mục và file cho authConfigPath
createDirectoryIfNotExists($authConfigPath);
// Nội dung mặc định là '{}'
#createFileIfNotExists($authConfigPath);

}
?>


<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
<script type="text/javascript">
    function startCountdown(timeLeft) {
        var countdownElement = document.getElementById("countdown");
        
        var countdownTimer = setInterval(function() {
            timeLeft--;
            //countdownElement.textContent = timeLeft;
            countdownElement.innerHTML = "<font color=red size=5>"+timeLeft+"</font>";
            if (timeLeft <= 0) {
                clearInterval(countdownTimer); // Dừng bộ đếm khi hết thời gian
                //window.location.reload(); // Tải lại trang
				window.location.href = "GCloud_Drive.php"; 
            }
        }, 1000); // Cập nhật mỗi 1 giây
    }


function reload_page() {
	 //window.location.reload();
	  window.location.href = "GCloud_Drive.php"; 
}
    // Gọi hàm với thời gian đếm ngược là 5 giây
    //startCountdown(5);
</script>
</head>
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


<?php
#Biến toàn cục kiểm tra xem thư viện có tồn tại hay không
$libPath_exist = false;
if ($google_cloud_drive_active === true){
// Các trường cần kiểm tra
$requiredFields = [
    'client_id' => '',
    'project_id' => '',
    'auth_uri' => '',
    'token_uri' => '',
    'auth_provider_x509_cert_url' => '',
    'client_secret' => '',
    'redirect_uris' => []
];

// Kiểm tra xem file có tồn tại không
if (!file_exists($authConfigPath)) {
    // Nếu file không tồn tại, tạo mới với các trường cần thiết
    $data = ['installed' => $requiredFields];
    file_put_contents($authConfigPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    
    // Thiết lập quyền truy cập 0777
    chmod($authConfigPath, 0777);
    //$notifications[] = "File JSON đã được tạo với các trường cần thiết và quyền truy cập đã được thiết lập thành 0777.";

    // Đọc và hiển thị nội dung file JSON vừa tạo
    $jsonContent = file_get_contents($authConfigPath);
    //$notifications[] = "Nội dung file JSON: <pre>" . htmlspecialchars($jsonContent) . "</pre>";

    // Kiểm tra xem có trường nào thiếu giá trị không
    $emptyFields = [];
    foreach ($requiredFields as $field => $defaultValue) {
        if (empty($data['installed'][$field])) {
            $emptyFields[] = $field;
        }
    }
    // Thông báo nếu có trường không có giá trị
    if (!empty($emptyFields)) {
		
		$activve_show = false;
		
        //$notifications[] = "1 Các trường sau đây không có giá trị: " . implode(', ', $emptyFields) . ".";
		$notifications[] = "<center><font color=red>
		Lỗi: Không có dữ liệu json Xác thực với Google Cloud OAuth 2.0 Client IDs<br/><br/>
		Cần cập nhật lại dữ liệu <b>Thông Tin Xác Thực: Google Cloud OAuth 2.0 Client IDs</b> của tệp json: client_secret ở bên dưới</font></center>";
	}
} else {
    // Đọc nội dung của file JSON
    $jsonContent = file_get_contents($authConfigPath);

    // Giải mã nội dung JSON thành mảng PHP
    $data = json_decode($jsonContent, true);

    // Kiểm tra xem dữ liệu đã được giải mã thành công không
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Lỗi giải mã JSON: " . json_last_error_msg());
    }

    // Kiểm tra xem các trường có trong dữ liệu hay không
    $missingFields = [];
    foreach ($requiredFields as $field => $defaultValue) {
        if (!isset($data['installed'][$field])) {
            $missingFields[] = $field;
            // Thiết lập giá trị mặc định cho trường thiếu
            $data['installed'][$field] = $defaultValue;
        }
    }

    // Nếu có trường thiếu, cập nhật file JSON
    if (!empty($missingFields)) {
        file_put_contents($authConfigPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $notifications[] = "Các trường thiếu đã được thêm vào file JSON: " . implode(', ', $missingFields) . ".<br/>";
    }

    // Kiểm tra xem tất cả các trường đã có giá trị hay chưa
    $emptyFields = [];
    foreach ($requiredFields as $field => $defaultValue) {
        if (empty($data['installed'][$field])) {
            $emptyFields[] = $field;
        }
    }

    // Thông báo nếu có trường không có giá trị
    if (!empty($emptyFields)) {
		$activve_show = false;
        //$notifications[] = "2 Các trường sau đây không có giá trị: " . implode(', ', $emptyFields) . ".";
		$notifications[] = "<center><font color=red>
		Lỗi: Không có dữ liệu json Xác thực với Google Cloud OAuth 2.0 Client IDs<br/><br/>
		Cần cập nhật lại dữ liệu <b>Thông Tin Xác Thực: Google Cloud OAuth 2.0 Client IDs</b> của tệp json: client_secret ở bên dưới</font></center>";
    } 
}

// Thiết lập quyền truy cập 0777
chmod($authConfigPath, 0777);


 

// Đọc file client_secret hiện tại
$read_authConfigPath = file_get_contents($authConfigPath);
$data_authConfigPath = json_decode($read_authConfigPath, true);
}
?>


  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Google Cloud Drive | <a href="https://docs.google.com/document/d/1-VTi9MOAgQoR8jZrhN9FlZxjWsq2vDuy/edit?usp=drive_link&ouid=106149318613102395200&rtpof=true&sd=true" target="_bank">Hướng Dẫn Tạo Json</a></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item">Google Drive</li>
&nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['backup_upgrade']['google_cloud_drive']['active'] ? '<p class="text-success" title="Google Drive đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Google Drive không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>

        </ol>
      </nav>
    </div><!-- End Page Title -->
	    <section class="section">
        <div class="row">

<?php


if ($google_cloud_drive_active === true){

// Hiển thị thông báo
foreach ($notifications as $notification) {
    echo $notification . "<br/>";
}


#$LIB_Google_API_PHP_CLIENT = '/home/'.$GET_current_USER.'/_VBot_Library/google-api-php-client/vendor/autoload.php';




if (isset($_POST['cau_hinh_cai_dat_thu_vien'])) {
	$connection = ssh2_connect($ssh_host, $ssh_port);
    ssh2_auth_password($connection, $ssh_user, $ssh_password);
	$composer_json = $HTML_VBot_Offline.'/includes/other_data/Google_Driver_PHP/composer.json';
	$composer_lock = $HTML_VBot_Offline.'/includes/other_data/Google_Driver_PHP/composer.lock';
	ssh2_exec($connection, "cp  $composer_json $client_directory");
    ssh2_exec($connection, "cp $composer_lock $client_directory");
	ssh2_exec($connection, "chmod -R 0777 $base_directory");
	ssh2_exec($connection, "cd $client_directory/ && composer update");
}

// Kiểm tra sự tồn tại của thư mục
if (!file_exists($client_directory)) {
    // Kết nối SSH
    $connection = ssh2_connect($ssh_host, $ssh_port);
    ssh2_auth_password($connection, $ssh_user, $ssh_password);

    // Tạo thư mục nếu không tồn tại
    if (!file_exists($base_directory)) {
		// Tạo thư mục _VBot_Library
        ssh2_exec($connection, "mkdir -p $base_directory");
		// Thay đổi quyền truy cập
        ssh2_exec($connection, "chmod 0777 $base_directory"); 
    }
    // Tạo thư mục google-api-php-client
    ssh2_exec($connection, "mkdir -p $client_directory");
	// Thay đổi quyền truy cập
    ssh2_exec($connection, "chmod 0777 $client_directory"); 
}



// Kiểm tra lại nếu tệp thư viện không tồn tại
if (!file_exists($LIB_Google_API_PHP_CLIENT)) {
	$libPath_exist = false;
    echo "<p class='text-danger'>Thư viện <b>google-api-php-client</b> chưa được cấu hình theo đường dẫn: <b>$LIB_Google_API_PHP_CLIENT</b> hãy nhấn nút <b>Cấu Hình</b> bên dưới và chờ đợi để cấu hình được hoàn thành</p>";
	echo '<br/><br/><center><form method="POST" action="">
	<button class="btn btn-success border-success rounded-pill" type="submit" name="cau_hinh_cai_dat_thu_vien" onclick="loading(\'show\')">Cấu Hình</button>
	</form>
	</center>';
}else {
$libPath_exist = true;
require_once $LIB_Google_API_PHP_CLIENT;
}
}else {
echo "<font color=red>- Cấu hình Config -> Cloud Backup -> Google Cloud Drive Không được Kích Hoạt Trong Config.json (backup_upgrade->google_cloud_drive->active)</font>";
echo "<font color=red>- Bạn Cần Kích Hoạt để Cấu Hình Được Hiển Thị Tại Đây</font>";
		}
		
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;



if ($libPath_exist === true){

//if ($google_cloud_drive_active === true){


// Khởi tạo Google Client
$client = new Client();

if ($activve_show === true){
$client->setAuthConfig($authConfigPath);
// URI chuyển hướng khi xác thực có thể truyền GEt để tự động lấy mã xác thực
//$client->setRedirectUri('http://localhost'); 
$client->setRedirectUri($data_authConfigPath['installed']['redirect_uris'][0]); 
$client->addScope(Drive::DRIVE_FILE);
// Yêu cầu Refresh Token khi hết hạn
#$client->setAccessType('offline'); 
$client->setAccessType($Config['backup_upgrade']['google_cloud_drive']['setAccessType']); 
// Buộc Google yêu cầu người dùng đồng ý lại
//$client->setPrompt('consent'); 
$client->setPrompt($Config['backup_upgrade']['google_cloud_drive']['setPrompt']); 
}

#Lưu dữ liệu secret_json vào file json
if (isset($_POST['save_client_secret_json'])) {
    // Lấy nội dung từ textarea
    $new_client_secret_json = json_decode(trim($_POST['client_secret_json']));
        $pretty_json = json_encode($new_client_secret_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Lưu dữ liệu vào file
        file_put_contents($authConfigPath, $pretty_json);
		echo '<script type="text/javascript">
            window.location.href = window.location.href;
          </script>';
		  exit();
}
?>

		
<?php

echo '<div style="text-align: right;">Google API Client Version: <font color=red><b>' . $client::LIBVER.'</b></font></div><br/><br/>';

?>

<div class="card accordion" id="accordion_button_client_secret_json">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_client_secret_json" aria-expanded="false" aria-controls="collapse_button_client_secret_json">
Thông Tin Xác Thực: Google Cloud OAuth 2.0 Client IDs, Type->Desktop  </h5>
<div id="collapse_button_client_secret_json" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_client_secret_json">


<div class="card-body">
<form method="POST" action="">

<div class="row mb-3">
<label for="client_secret_json">Dữ liệu tệp json OAuth Client, client_secret.json <i class="bi bi-question-circle-fill" onclick="show_message('Cần tạo cấu hình xác thực json với kiểu loại là Desktop:<br/><b>Credentials -> + Create credentials -> OAuth client ID -> Appllication type -> Desktop app</b>')"></i> : </label>
<textarea type="text" class="form-control border-success" name="client_secret_json" id="client_secret_json" rows="5">
<?php 
echo htmlspecialchars(trim($read_authConfigPath));
?>
</textarea>
</div>
<center><button class="btn btn-primary border-success rounded-pill" type="submit" name="save_client_secret_json" onclick="loading('show')">Lưu Dữ Liệu JSON</button></center>

</form>
</div>
</div>
</div>
</div>


<?php
if ($activve_show === true){
?>
<div class="card">
<div class="card-body">
<h5 class="card-title">Google Driver Verify Token</h5>

<?php
#Lấy Mã token từ mã xác thực
if (isset($_POST['submit_authorization_code'])) {
$authCode = $_POST['authorization_code'];

if (empty($authCode)) {
    // Nếu có dữ liệu, thực hiện một số hành động
    echo "<center><h5><p class='text-danger'>Cần nhập mã ủy quyền để xác thực</p></h5></center>";
}else {
// Kiểm tra xem chuỗi có chứa "http" hoặc "https"
if (strpos($authCode, 'http') !== false || strpos($authCode, 'https') !== false) {
    // Phân tích URL
    $url_components = parse_url($authCode);
    
    // Tách các tham số trong chuỗi truy vấn
    parse_str($url_components['query'], $params);

    // Kiểm tra và lấy giá trị của 'code'
    if (isset($params['code'])) {
        $authCode_check = $params['code'];
    } else {
		// Nếu không có tham số 'code', giữ nguyên authCode
        $authCode_check = $authCode; 
    }
} else {
	// Nếu không có http/https, giữ nguyên authCode
    $authCode_check = $authCode; 
}

	
// Lấy Access Token từ mã xác thực
$accessToken = $client->fetchAccessTokenWithAuthCode($authCode_check);
// Kiểm tra và lưu token vào file JSON
if (!empty($accessToken['access_token'])) {
    // Lưu token vào file
    file_put_contents($tokenPath, json_encode($accessToken, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "<center>Xác thực thành công, Token đã được lưu trữ<br/><br/>";
	echo "<p class='card-title text-success'>Trang sẽ được tải lại sau <span id='countdown'><font color=red size=5>5</font></span> giây.";
	echo "<script>startCountdown(5);</script>"; 
	echo '<br/><br/><button type="button" class="btn btn-info" onclick="reload_page()">Tải Lại</button></center>';
    #echo $accessToken;
} 
else {
    echo "<center><p class='card-title text-danger'>Không thể lấy Access Token. Vui lòng kiểm tra mã xác thực ủy quyền không đúng</p><br/><br/>";
	 echo '<button type="button" class="btn btn-info" onclick="reload_page()">Tải Lại</button></center>';
}
}
}


// Tải token từ file nếu đã tồn tại
if (file_exists($tokenPath)) {
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    // Kiểm tra xem token có hợp lệ hay không
    if (json_last_error() === JSON_ERROR_NONE && isset($accessToken['access_token'])) {
        $client->setAccessToken($accessToken);
    }
}

// Nếu chưa có Access Token, yêu cầu người dùng xác thực
if ($client->isAccessTokenExpired()) {
if ($client->getRefreshToken()) {
    // Nếu đã có Refresh Token, cố gắng làm mới Access Token
    $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    // Kiểm tra xem token mới có tồn tại không
    if (isset($newAccessToken['access_token'])) {
        // Lưu token mới vào file
        file_put_contents($tokenPath, json_encode($newAccessToken, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo "<center><p class='card-title text-success'>Token đã được tự động làm mới thành công</p></center>";
        echo "<center><p class='card-title text-success'>Trang sẽ được tải lại sau <span id='countdown'><font color='red' size='5'>5</font></span> giây.</p></center>";
        echo "<script>startCountdown(5);</script>"; 
        echo '<button type="button" class="btn btn-info" onclick="reload_page()">Tải Lại</button>';
    } else{
		echo "<p class='text-danger'>- Xảy ra Lỗi, Token không tồn tại khi được làm mới, Cần Phải Cấu Hình Lại Mã Ủy Quyền<br/><br/>";
		echo "Thông tin trả về:<br/>" .json_encode($newAccessToken, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."</p>";
        $authUrl = $client->createAuthUrl();
		
		echo '<div class="row mb-3">
                  <label for="auth_Url" class="col-sm-3 col-form-label">Truy Cập URL Sau Để Lấy Mã Xác Thực: </label>
                  <div class="col-sm-9">
				   <div class="input-group mb-3">
                    <input readonly type="text" class="form-control border-success" name="auth_Url" id="auth_Url" placeholder="'.urldecode($authUrl).'" value="'.urldecode($authUrl).'">
					<button class="btn btn-primary border-success" type="button" onclick="coppy_value(\'auth_Url\')"><i class="bi bi-copy"></i> Sao Chép</button>
					<button type="button" class="btn btn-success" onclick="openNewTab(\''.$authUrl.'\')"><i class="bi bi-box-arrow-up-right"></i> Đi Tới</button>
				  </div>
				  </div>
                </div>';
		echo '<form method="POST" action=""><div class="row mb-3">
                  <label for="authorization_code" class="col-sm-3 col-form-label">Nhập Mã Ủy Quyền: </label>
                  <div class="col-sm-9">
				   <div class="input-group mb-3">
                    <input type="text" class="form-control border-success" name="authorization_code" id="authorization_code" placeholder="4/0AVG7fiQKI7QXXXXXXXXX2wUYcrsLxG0V-og7oqsXXXXXXXXX41knwNnqqkMT-XXXXXXXXX">
				<button type="submit" name="submit_authorization_code" class="btn btn-success" onclick="loading(\'show\')">Xác Thực</button>
				  </div>
				  </div>
                </div></form>';
	}
}
 else {
		echo "<center><p class='card-title text-danger'>Cần Cấu Hình Để Lấy Mã Truy Cập Xác Thực Token</p></center>";
        $authUrl = $client->createAuthUrl();
		echo '<div class="row mb-3">
                  <label for="auth_Url" class="col-sm-3 col-form-label">Truy Cập URL Sau Để Lấy Mã Xác Thực: </label>
                  <div class="col-sm-9">
				   <div class="input-group mb-3">
                    <input readonly type="text" class="form-control border-success" name="auth_Url" id="auth_Url" placeholder="'.urldecode($authUrl).'" value="'.urldecode($authUrl).'">
					<button class="btn btn-primary border-success" type="button" onclick="coppy_value(\'auth_Url\')"><i class="bi bi-copy"></i> Sao Chép</button>
					<button type="button" class="btn btn-success" onclick="openNewTab(\''.$authUrl.'\')"><i class="bi bi-box-arrow-up-right"></i> Đi Tới</button>
				  </div>
				  </div>
                </div>';
				
		echo '<form method="POST" action=""><div class="row mb-3">
                  <label for="authorization_code" class="col-sm-3 col-form-label">Nhập Mã Ủy Quyền: </label>
                  <div class="col-sm-9">
				   <div class="input-group mb-3">
                    <input type="text" class="form-control border-success" name="authorization_code" id="authorization_code" placeholder="4/0AVG7fiQKI7QXXXXXXXXX2wUYcrsLxG0V-og7oqsXXXXXXXXX41knwNnqqkMT-XXXXXXXXX">
				<button type="submit" name="submit_authorization_code" class="btn btn-success" onclick="loading(\'show\')">Xác Thực</button>
				  </div>
				  </div>
                </div></form>';
		
        //exit;
    }
}
else{
	

    // Khởi tạo Google Drive Service
    $driveService = new Drive($client);
    // Lấy thông tin về Google Drive của người dùng
    $about = $driveService->about->get(['fields' => 'user, storageQuota']);
    // Hiển thị thông tin
    $user = $about->getUser();
    $storageQuota = $about->getStorageQuota();

echo "<center><p class='card-title text-success'>Token Hợp Lệ</p></center>";

echo '<div class="row mb-3">
<label for="get_display_name" class="col-sm-3 col-form-label" title="Tên Người Dùng">Tên Người Dùng: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<b class="text-danger">'.$user->getDisplayName().'</b>
</div>
</div>
</div>';

echo '<div class="row mb-3">
<label for="get_display_name" class="col-sm-3 col-form-label" title="Địa Chỉ Gmail">Địa Chỉ Gmail: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<b class="text-danger">'.$user->getEmailAddress().'</b>
</div>
</div>
</div>';

echo '<div class="row mb-3">
<label for="get_display_name" class="col-sm-3 col-form-label" title="Dung Lượng Đã Dùng">Dung Lượng Đã Dùng: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<b class="text-danger">'.formatBytes($storageQuota->getUsage()).'</b>
</div>
</div>
</div>';

echo '<div class="row mb-3">
<label for="get_display_name" class="col-sm-3 col-form-label" title="Tổng Dung Lượng">Tổng Dung Lượng: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<b class="text-danger">'.formatBytes($storageQuota->getLimit()).'</b>
</div>
</div>
</div>';

echo '<div class="row mb-3">
<label for="access_token_verify" class="col-sm-3 col-form-label" title="Verify Token">Mã Token: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input disabled type="text" class="form-control border-danger" name="access_token_verify" id="access_token_verify" placeholder="'.$accessToken['access_token'].'" value="'.$accessToken['access_token'].'">
</div>
</div>
</div>';
echo '<div class="row mb-3">
<label for="refresh_token_verify" class="col-sm-3 col-form-label" title="Refresh Token">Mã làm mới Token: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input disabled type="text" class="form-control border-danger" name="refresh_token_verify" id="refresh_token_verify" placeholder="'.$accessToken['refresh_token'].'" value="'.$accessToken['refresh_token'].'">
</div>
</div>
</div>';

$expiration_time = date('d-m-Y H:i:s', $accessToken['created'] + $accessToken['expires_in']);
echo '<div class="row mb-3">
<label for="expires_in_verify" class="col-sm-3 col-form-label">Thời gian hết hạn: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input disabled type="text" class="form-control border-danger" name="expires_in_verify" id="expires_in_verify" placeholder="'.$expiration_time.'" value="'.$expiration_time.'">
</div>
</div>
</div>';
echo '<div class="row mb-3">
<label for="scope_verify" class="col-sm-3 col-form-label">Quyền, Phạm Vi: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input disabled type="text" class="form-control border-danger" name="scope_verify" id="scope_verify" placeholder="'.$accessToken['scope'].'" value="'.$accessToken['scope'].'">
</div>
</div>
</div>';
echo '<div class="row mb-3">
<label for="token_type_verify" class="col-sm-3 col-form-label">Loại Token: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input disabled type="text" class="form-control border-danger" name="token_type_verify" id="token_type_verify" placeholder="'.$accessToken['token_type'].'" value="'.$accessToken['token_type'].'">
</div>
</div>
</div>';

$readable_time = date('d-m-Y H:i:s', $accessToken['created']);
echo '<div class="row mb-3">
<label for="created_verify" class="col-sm-3 col-form-label">Thời gian tạo: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input disabled type="text" class="form-control border-danger" name="created_verify" id="created_verify" placeholder="'.$readable_time.'" value="'.$readable_time.'">
</div>
</div>
</div>';
}

/*
// Lưu Access Token và Refresh Token vào file
file_put_contents($tokenPath, json_encode($client->getAccessToken()));

// Khởi tạo service Drive
$service = new Drive($client);

// Định nghĩa metadata cho file
$fileMetadata = new DriveFile([
    'name' => '1.mp3', // Đặt tên file sẽ xuất hiện trên Google Drive
]);

// Đường dẫn đến file muốn tải lên
$filePath = '1.mp3'; // Thay đường dẫn này bằng file thực tế trên máy chủ

// Đọc nội dung file
$content = file_get_contents($filePath);

// Thực hiện upload file lên Google Drive
try {
    $file = $service->files->create($fileMetadata, [
        'data' => $content,
        'mimeType' => mime_content_type($filePath), // Tự động phát hiện loại MIME của file
        'uploadType' => 'multipart',
        'fields' => 'id'
    ]);

    // In ra File ID sau khi tải lên thành công
    printf("File ID: %s\n", $file->id);
} catch (Exception $e) {
    echo 'Lỗi khi tải lên file: ' . $e->getMessage();
}
*/
?>

<form method="post" onsubmit="return confirmDelete();">
<center><button class="btn btn-danger border-danger rounded-pill" type="submit" name="del_all_data_gcloud_drive">Xóa toàn bộ Dữ Liệu</button></center>
</form>
		</div>
		</div>
		<?php
		}
/*	
	}else {
			
			echo "<font color=red>- Cấu hình Config -> Cloud Backup -> Google Cloud Drive Không được Kích Hoạt Trong Config.json (backup_upgrade->google_cloud_drive->active)</font>";
			echo "<font color=red>- Bạn Cần Kích Hoạt để Cấu Hình Được Hiển Thị Tại Đây</font>";
		}
		*/
		
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
<script type="text/javascript">
        function confirmDelete() {
            return confirm("Bạn có chắc chắn muốn xóa toàn bộ dữ liệu không, thao tác này sẽ xóa cả dữ liệu tệp cấu hình xác thực JSON  Google Cloud OAuth 2.0 Client IDs");
       // loading('show');
		}
      </script>
  <!-- Template Main JS File -->
<?php
include 'html_js.php';
?>

</body>
</html>