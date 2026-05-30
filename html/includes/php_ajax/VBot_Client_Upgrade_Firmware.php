<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include '../../Configuration.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (
        !isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
    ) {
        session_unset();
        session_destroy();
        echo json_encode([
            'success' => false,
            'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$hash_bypass_OTA = "441018525208457705bf09a8ee3c1093";

//[1] Bypass nâng cấp firmware (Gửi yêu cầu đến thiết bị)
if (isset($_GET['bypass_upgrade_firmware']) && !empty($_GET['ip'])) {
    $ip = $_GET['ip'];
	#link bypass firmware
    $targetUrl = 'http://' . $ip . '/ota/start?mode=fr&hash=' . $hash_bypass_OTA;
    $params = $_GET;
    unset($params['bypass_upgrade_firmware'], $params['ip']);
    if (!empty($params)) {
        $targetUrl .= "?" . http_build_query($params);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Lỗi kết nối tới thiết bị: " . $error]);
    } else {
        http_response_code($httpCode);
        echo json_encode(["success" => true, "message" => "bypass_fr_ok"]);
    }
    exit;
}

//[1] Bypass nâng cấp firmware (Gửi yêu cầu đến thiết bị)
elseif (isset($_GET['bypass_upgrade_littlefs']) && !empty($_GET['ip'])) {
    $ip = $_GET['ip'];
	#Link Bypass littlefs
    $targetUrl = 'http://' . $ip . '/ota/start?mode=fs&hash=' . $hash_bypass_OTA;
    $params = $_GET;
    unset($params['bypass_upgrade_littlefs'], $params['ip']);
    if (!empty($params)) {
        $targetUrl .= "?" . http_build_query($params);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Lỗi kết nối tới thiết bị: " . $error]);
    } else {
        http_response_code($httpCode);
        echo json_encode(["success" => true, "message" => "bypass_fs_ok"]);
    }
    exit;
}

//Nâng Cấp LittleFS / SPIFFS Tự Động
elseif (isset($_GET['start_upgrade_littlefs'], $_GET['ip'], $_GET['url_littlefs']) && !empty($_GET['ip']) && !empty($_GET['url_littlefs'])) {
    $ip = $_GET['ip'];
    $url_littlefs = $_GET['url_littlefs'];
    $temp_file = tempnam(sys_get_temp_dir(), 'littlefs_');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_littlefs);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $file_content = curl_exec($ch);
    $httpCodeDownload = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($file_content === false || $httpCodeDownload < 200 || $httpCodeDownload >= 300) {
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Lỗi tải xuống LittleFS: " . ($error ?: "HTTP " . $httpCodeDownload)
        ]);
        exit;
    }
    file_put_contents($temp_file, $file_content);
    //Upload LittleFS lên Client OTA
    $upload_url = 'http://' . $ip . '/ota/upload';
    $post_data = [
        'file' => new CURLFile(
            $temp_file,
            'application/octet-stream',
            'littlefs.bin'
        )
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upload_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: */*',
        'Origin: http://' . $ip,
        'Referer: http://' . $ip . '/update',
        'User-Agent: Mozilla/5.0'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    unlink($temp_file);
    if ($response === false) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Lỗi tải lên LittleFS: " . $error
        ]);
    } else {
        http_response_code($httpCode);
        echo json_encode([
            "success" => true,
            "message" => "Nâng cấp LittleFS thành công"
        ]);
    }
    exit;
}

//Nâng Cấp Tự ĐỘng firmware
elseif (isset($_GET['start_upgrade_firmware'], $_GET['ip'], $_GET['url_firmware']) && !empty($_GET['ip']) && !empty($_GET['url_firmware'])) {
    $ip = $_GET['ip'];
    $url_firmware = $_GET['url_firmware'];
    $temp_file = tempnam(sys_get_temp_dir(), 'firmware_');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_firmware);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $file_content = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($file_content === false) {
        unlink($temp_file);
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Lỗi tải xuống Firmware: " . $error]);
        exit;
    }
    file_put_contents($temp_file, $file_content);
    $upload_url = 'http://' . $ip . '/ota/upload';
    $firmware_filename = "VBot_Client_FW_" . basename($url_firmware);
    $post_data = ['file' => new CURLFile($temp_file, 'application/octet-stream', $firmware_filename)];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upload_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    unlink($temp_file);
    if ($response === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Lỗi tải lên Firmware: " . $error]);
    } else {
        http_response_code($httpCode);
        echo json_encode(["success" => true, "message" => "Nâng cấp Firmware thành công"]);
    }
    exit;
}

//Nâng Cấp Thủ Công firmware
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['firmware']) && isset($_POST['ip_address'])) {
    $ip_address = $_POST['ip_address'];
    $tmpDir = sys_get_temp_dir();
    $tmpFile = $tmpDir . '/' . basename($_FILES['firmware']['name']);
    if (pathinfo($tmpFile, PATHINFO_EXTENSION) !== 'bin') {
        echo json_encode(["success" => false, "message" => "Chỉ chấp nhận file .bin"]);
        exit;
    }
    if (!move_uploaded_file($_FILES['firmware']['tmp_name'], $tmpFile)) {
        echo json_encode(["success" => false, "message" => "Lỗi khi lưu file vào bộ nhớ tạm."]);
        exit;
    }
    $ota_start_url = 'http://' . $ip_address . '/ota/start?mode=fr&hash=' . $hash_bypass_OTA;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ota_start_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($httpCode !== 200) {
        unlink($tmpFile);
        echo json_encode(["success" => false, "message" => "Lỗi khi bỏ qua xác thực OTA gửi yêu cầu nâng cấp: " . $error]);
        exit;
    }
    $upload_url = 'http://' . $ip_address . '/ota/upload';
    $firmware_filename = "VBot_Client_FW_" . basename($_FILES['firmware']['name']);
    $post_data = ['file' => new CURLFile($tmpFile, 'application/octet-stream', $firmware_filename)];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upload_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    unlink($tmpFile);
    if ($httpCode !== 200) {
        echo json_encode(["success" => false, "message" => "Lỗi tải lên firmware: " . $error]);
    } else {
        echo json_encode(["success" => true, "message" => "Đã Nâng cấp Firmware"]);
    }
    exit;
}

//Lưu dữ liệu Client Data
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_data_vbot_client') {
    $json_file = $directory_path . '/includes/other_data/VBot_Client_Data/' . $Config['api']['streaming_server']['protocol']['udp_sock']['data_client_name'];
    $directory = dirname($json_file);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Không thể tạo thư mục']);
            exit;
        }
		shell_exec('chmod 0777 ' . escapeshellarg($directory));
    }
    if (!file_exists($json_file)) {
        if (file_put_contents($json_file, '{}') === false) {
            echo json_encode(['success' => false, 'message' => 'Không thể tạo file JSON']);
            exit;
        }
		shell_exec('chmod 0777 ' . escapeshellarg($json_file));
    }
    if (!is_writable($json_file)) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền ghi vào file']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data === null) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ']);
        exit;
    }
    $result = file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($result !== false) {
        echo json_encode(['success' => true, 'message' => 'Dữ liệu đã được lưu thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu dữ liệu vào file']);
    }
}

else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ"]);
    exit;
}
?>