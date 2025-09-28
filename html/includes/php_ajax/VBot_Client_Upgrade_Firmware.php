<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($Config['contact_info']['user_login']['active']){
  session_start();
  // Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
  if (!isset($_SESSION['user_login']) ||
      (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))) {
      // Nếu chưa đăng nhập hoặc đã quá 12 tiếng, hủy session và chuyển hướng đến trang đăng nhập
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
        echo json_encode(["success" => true, "message" => "bypass_ota_ok"]);
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
// Khôi phục dữ liệu file json
elseif (isset($_POST['client_upload_restore_settings'])) {
    $ip_address = $_POST['client_upload_restore_settings'];
    // Kiểm tra nếu client_upload_restore_settings rỗng hoặc không hợp lệ
    if (empty($ip_address)) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu client_upload_restore_settings hoặc giá trị rỗng'
        ]);
        exit;
    }
    // Kiểm tra định dạng IP (bảo mật)
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        echo json_encode([
            'success' => false,
            'error' => 'Địa chỉ IP không hợp lệ'
        ]);
        exit;
    }
    // Kiểm tra nếu có file config_file
    if (!isset($_FILES['config_file'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu file cấu hình'
        ]);
        exit;
    }
    $file = $_FILES['config_file'];
    // Kiểm tra lỗi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi tải lên file: ' . $file['error']
        ]);
        exit;
    }
    // Kiểm tra định dạng file (chỉ cho phép JSON)
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($fileExtension) !== 'json') {
        echo json_encode([
            'success' => false,
            'error' => 'File phải có định dạng .json'
        ]);
        exit;
    }
    // Tạo URL mục tiêu
    $targetUrl = 'http://' . $ip_address . '/upload_nvs_config';
    try {
        // Khởi tạo cURL
        $ch = curl_init($targetUrl);
        // Tạo dữ liệu multipart cho file
        $postData = [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])
        ];
        // Cấu hình cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Trả về dữ liệu
        curl_setopt($ch, CURLOPT_POST, true); // Sử dụng POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // Gửi file
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Thời gian chờ tối đa (30 giây)
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Theo dõi chuyển hướng
        // Thực thi yêu cầu
        $response = curl_exec($ch);
        // Kiểm tra lỗi cURL
        if ($response === false) {
            throw new Exception('Không thể kết nối tới client: ' . curl_error($ch));
        }
        // Lấy mã trạng thái HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception('Phản hồi từ client không thành công. Mã trạng thái: ' . $httpCode);
        }
        // Đóng cURL
        curl_close($ch);
        // Trả về phản hồi thành công
        echo json_encode([
            'success' => true,
            'message' => 'Tải lên tệp khôi phục cấu hình thành công'
        ]);
    } catch (Exception $e) {
        // Đóng cURL nếu có lỗi
        if (isset($ch)) {
            curl_close($ch);
        }
        // Trả về lỗi
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
// Kiểm tra nếu có dữ liệu POST với client_ctrl_act_vbot
elseif (isset($_POST['client_ctrl_act_vbot'])) {
    $ip_address = $_POST['client_ctrl_act_vbot'];
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Kiểm tra nếu ip_address rỗng hoặc không hợp lệ
    if (empty($ip_address)) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu client_ctrl_act_vbot hoặc giá trị rỗng'
        ]);
        exit;
    }

    // Kiểm tra định dạng IP (bảo mật)
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        echo json_encode([
            'success' => false,
            'error' => 'Địa chỉ IP không hợp lệ'
        ]);
        exit;
    }

    // Kiểm tra nếu action rỗng hoặc không hợp lệ
    if (empty($action)) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu action'
        ]);
        exit;
    }

    // Kiểm tra action hợp lệ
    $valid_actions = ['restart', 'resetwifi', 'cleanNVS'];
    if (!in_array($action, $valid_actions)) {
        echo json_encode([
            'success' => false,
            'error' => 'Hành động không hợp lệ'
        ]);
        exit;
    }

    // Tạo URL mục tiêu
    $targetUrl = 'http://' . $ip_address . '/' . $action;

    try {
        // Khởi tạo cURL
        $ch = curl_init($targetUrl);

        // Cấu hình cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Trả về dữ liệu
        curl_setopt($ch, CURLOPT_POST, true); // Sử dụng POST
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Thời gian chờ 5 giây (đồng bộ với xhr.timeout)
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Theo dõi chuyển hướng

        // Thực thi yêu cầu
        $response = curl_exec($ch);

        // Kiểm tra lỗi cURL
        if ($response === false) {
            throw new Exception('Không thể kết nối tới client: ' . curl_error($ch));
        }

        // Lấy mã trạng thái HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception('Phản hồi từ client không thành công. Mã trạng thái: ' . $httpCode);
        }

        // Đóng cURL
        curl_close($ch);

        // Trả về phản hồi thành công
        echo json_encode([
            'success' => true,
            'message' => 'Gửi yêu cầu ' . $action . ' thành công'
        ]);
    } catch (Exception $e) {
        // Đóng cURL nếu có lỗi
        if (isset($ch)) {
            curl_close($ch);
        }
        // Trả về lỗi
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
// Kiểm tra nếu có dữ liệu POST với client_save_config
elseif (isset($_POST['client_save_config'])) {
    $ip_address = $_POST['client_save_config'];
    if (empty($ip_address)) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu client_save_config hoặc giá trị rỗng'
        ]);
        exit;
    }
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        echo json_encode([
            'success' => false,
            'error' => 'Địa chỉ IP không hợp lệ'
        ]);
        exit;
    }
    $params = [
        // Server / Client
        'clientName' => $_POST['clientName'] ?? '',
        'udp_server' => $_POST['udp_server'] ?? '',
        'udp_server_port' => intval($_POST['udp_server_port'] ?? 0),
        // I2S Config - INMP441
        'i2s_sck' => intval($_POST['i2s_sck'] ?? 0),
        'i2s_ws' => intval($_POST['i2s_ws'] ?? 0),
        'i2s_sd' => intval($_POST['i2s_sd'] ?? 0),
        'gain_mic' => floatval($_POST['gain_mic'] ?? 20),
        'i2s_slot_mask' => $_POST['i2s_slot_mask'] ?? 'left',
        // I2S Config - MAX98357
        'i2s_bclk' => intval($_POST['i2s_bclk'] ?? 0),
        'i2s_lrc' => intval($_POST['i2s_lrc'] ?? 0),
        'i2s_dout' => intval($_POST['i2s_dout'] ?? 0),
        'volume_level' => floatval($_POST['volume_level'] ?? 0),
        // LED Config
        'gpio_ws2812b' => intval($_POST['gpio_ws2812b'] ?? 0),
        'num_pixels' => intval($_POST['num_pixels'] ?? 12),
        'brightness' => intval($_POST['brightness'] ?? 255),
        'loading_effect' => intval($_POST['loading_effect'] ?? 0),
        'LED_THINK_COLOR' => preg_match('/^[0-9A-Fa-f]{6}$/', $_POST['LED_THINK_COLOR'] ?? '') ? $_POST['LED_THINK_COLOR'] : '0000FF',
        // Button Config
        'push_btn_type' => intval($_POST['push_btn_type'] ?? 0),
        'DEBOUNCE_DELAY' => intval($_POST['DEBOUNCE_DELAY'] ?? 100),
        'bt_active_high' => intval($_POST['bt_active_high']) ?? 0,
        'gpio_button_wakeup' => intval($_POST['gpio_button_wakeup'] ?? 0),
        'longPressOption' => intval($_POST['longPressOption'] ?? 0),
        'longPressDuration' => intval($_POST['longPressDuration'] ?? 2000),
        'gpio_button_mic' => intval($_POST['gpio_button_mic'] ?? 0),
        'micLongPressOption' => intval($_POST['micLongPressOption'] ?? 2),
        'micLongPressDuration' => intval($_POST['micLongPressDuration'] ?? 2000),
        'gpio_button_up' => intval($_POST['gpio_button_up'] ?? 0),
        'gpio_button_down' => intval($_POST['gpio_button_down'] ?? 0),
        // Encoder Button
        'button_encd_gpio_clk' => intval($_POST['button_encd_gpio_clk'] ?? 0),
        'button_encd_gpio_dt' => intval($_POST['button_encd_gpio_dt'] ?? 0),
        'button_encd_gpio_sw' => intval($_POST['button_encd_gpio_sw'] ?? 0),
        //'button_encd_pres_opt' => intval($_POST['button_encd_pres_opt'] ?? 2),
		'button_encd_pres_opt' => array_key_exists('button_encd_pres_opt', $_POST) ? intval($_POST['button_encd_pres_opt']) : 2,
        'button_encd_l_pre_opt' => intval($_POST['button_encd_l_pre_opt'] ?? 0),
        'button_encd_long_prs' => intval($_POST['button_encd_long_prs'] ?? 2000)
    ];
    // Chỉ thêm các boolean nếu tồn tại trong $_POST
    if (isset($_POST['ledActive'])) $params['ledActive'] = 'on';
    if (isset($_POST['button_active'])) $params['button_active'] = 'on';
    if (isset($_POST['longPressActive'])) $params['longPressActive'] = 'on';
    if (isset($_POST['micLongPressActive'])) $params['micLongPressActive'] = 'on';
    if (isset($_POST['btn_wake_active'])) $params['btn_wake_active'] = 'on';
    if (isset($_POST['btn_mic_active'])) $params['btn_mic_active'] = 'on';
    if (isset($_POST['btn_up_active'])) $params['btn_up_active'] = 'on';
    if (isset($_POST['btn_down_active'])) $params['btn_down_active'] = 'on';
    if (isset($_POST['logsActive'])) $params['logsActive'] = 'on';
    if (isset($_POST['conversation_mode_active'])) $params['conversation_mode_active'] = 'on';
    if (isset($_POST['sound_on_startup'])) $params['sound_on_startup'] = 'on';
    if (isset($_POST['micActive'])) $params['micActive'] = 'on';
    if (isset($_POST['speakerActive'])) $params['speakerActive'] = 'on';
    // Kiểm tra thông tin bắt buộc
    if (empty($params['clientName']) || empty($params['udp_server'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Thiếu thông tin bắt buộc: clientName hoặc udp_server'
        ]);
        exit;
    }
    // Gửi dữ liệu POST tới client
    $postData = http_build_query($params);
    $targetUrl = 'http://' . $ip_address . '/save';
    try {
        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('Không thể kết nối tới client: ' . curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception('Phản hồi từ client không thành công. Mã trạng thái: ' . $httpCode);
        }
        curl_close($ch);
        echo json_encode([
            'success' => true,
            'message' => 'Lưu cấu hình thành công'
        ]);
    } catch (Exception $e) {
        if (isset($ch)) {
            curl_close($ch);
        }
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Kiểm tra nếu có dữ liệu POST với client_play_audio
elseif (isset($_POST['client_play_audio'])) {
    $ip_address = $_POST['client_play_audio'];
    $audio_url = isset($_POST['url']) ? $_POST['url'] : '';
    if (empty($ip_address)) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu client_play_audio hoặc giá trị rỗng'
        ]);
        exit;
    }
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        echo json_encode([
            'success' => false,
            'error' => 'Địa chỉ IP không hợp lệ'
        ]);
        exit;
    }
    if (empty($audio_url)) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu URL âm thanh'
        ]);
        exit;
    }
    $fileExtension = pathinfo(parse_url($audio_url, PHP_URL_PATH), PATHINFO_EXTENSION);
	/*
    if (strtolower($fileExtension) !== 'mp3') {
        echo json_encode([
            'success' => false,
            'error' => 'URL âm thanh phải có định dạng .mp3'
        ]);
        exit;
    }
	*/
    $targetUrl = 'http://' . $ip_address . '/play_audio';
    $postData = 'url=' . urlencode($audio_url);
    try {
        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('Không thể kết nối tới client: ' . curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception('Phản hồi từ client không thành công. Mã trạng thái: ' . $httpCode);
        }
        curl_close($ch);
        echo json_encode([
            'success' => true,
            'message' => $response ?: 'Phát âm thanh thành công'
        ]);
    } catch (Exception $e) {
        if (isset($ch)) {
            curl_close($ch);
        }
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
// Kiểm tra nếu có dữ liệu POST với client_download_config
elseif (isset($_POST['client_download_config'])) {
    $ip_address = $_POST['client_download_config'];
    if (empty($ip_address)) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu client_download_config hoặc giá trị rỗng'
        ]);
        exit;
    }
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        echo json_encode([
            'success' => false,
            'error' => 'Địa chỉ IP không hợp lệ'
        ]);
        exit;
    }
    $targetUrl = 'http://' . $ip_address . '/VBot_Client_Dowload_Config';
    try {
        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('Không thể kết nối tới client: ' . curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception('Phản hồi từ client không thành công. Mã trạng thái: ' . $httpCode);
        }
        $jsonData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Dữ liệu JSON không hợp lệ từ client');
        }
        curl_close($ch);
        echo json_encode([
            'success' => true,
            'filename' => 'VBot_Client_Config_'.$ip_address.'.json',
            'content' => $response
        ]);
    } catch (Exception $e) {
        if (isset($ch)) {
            curl_close($ch);
        }
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ"]);
    exit;
}
?>
