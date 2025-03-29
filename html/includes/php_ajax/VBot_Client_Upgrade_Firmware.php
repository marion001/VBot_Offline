<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$hash_bypass_OTA = "441018525208457705bf09a8ee3c1093";

// [1] Bypass nâng cấp firmware (Gửi yêu cầu đến thiết bị)
if (isset($_GET['bypass_upgrade_firmware']) && !empty($_GET['ip'])) {
    $ip = $_GET['ip'];
    $targetUrl = 'http://'.$ip.'/ota/start?mode=fr&hash='.$hash_bypass_OTA;
    // Thêm các tham số khác (nếu có)
    $params = $_GET;
    unset($params['bypass_upgrade_firmware'], $params['ip']);
    if (!empty($params)) {
        $targetUrl .= "?" . http_build_query($params);
    }
    // Gửi request qua cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    // Trả về JSON
    if ($response === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Lỗi kết nối tới thiết bị: " . $error]);
    } else {
        http_response_code($httpCode);
        echo json_encode(["success" => true, "message" => "Thiết bị đang nâng cấp firmware"]);
    }
    exit;
}

//Nâng Cấp Tự ĐỘng
elseif (isset($_GET['start_upgrade_firmware'], $_GET['ip'], $_GET['url_firmware']) && !empty($_GET['ip']) && !empty($_GET['url_firmware'])) {
    $ip = $_GET['ip'];
    $url_firmware = $_GET['url_firmware'];
    $temp_file = tempnam(sys_get_temp_dir(), 'firmware_');
    // Tải file firmware về máy chủ
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
    // Lưu file vào bộ nhớ tạm
    file_put_contents($temp_file, $file_content);
    // Gửi firmware tới thiết bị
    $upload_url = 'http://'.$ip.'/ota/upload';
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
    unlink($temp_file); // Xóa file tạm
    if ($response === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Lỗi tải lên Firmware: " . $error]);
    } else {
        http_response_code($httpCode);
        echo json_encode(["success" => true, "message" => "Nâng cấp Firmware thành công"]);
    }
    exit;
}

//Nâng Cấp Thủ Công
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
    // Gửi yêu cầu bắt đầu nâng cấp firmware
    $ota_start_url = 'http://'.$ip_address.'/ota/start?mode=fr&hash='.$hash_bypass_OTA;
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
    // Gửi firmware tới thiết bị
    $upload_url = 'http://'.$ip_address.'/ota/upload';
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
    // Đường dẫn file JSON trên server
    $json_file = $directory_path.'/includes/other_data/VBot_Client_Data/'.$Config['api']['streaming_server']['protocol']['udp_sock']['data_client_name'];
    $directory = dirname($json_file);
    // Kiểm tra và tạo thư mục nếu chưa tồn tại
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Không thể tạo thư mục']);
            exit;
        }
        chmod($directory, 0777);
    }
    // Kiểm tra và tạo file nếu chưa tồn tại
    if (!file_exists($json_file)) {
        if (file_put_contents($json_file, '{}') === false) {
            echo json_encode(['success' => false, 'message' => 'Không thể tạo file JSON']);
            exit;
        }
        chmod($json_file, 0777);
    }
    // Kiểm tra quyền ghi file
    if (!is_writable($json_file)) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền ghi vào file']);
        exit;
    }
    // Nhận dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data === null) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ']);
        exit;
    }
    // Lưu dữ liệu vào file
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
