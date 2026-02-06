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

if (isset($_GET['scan_mic'])) {
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/Scan_Mic.py");
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        echo json_encode($response);
        exit();
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công.';
        echo json_encode($response);
        exit();
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh trên máy chủ SSH.';
        echo json_encode($response);
        exit();
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    echo $output;
    exit();
}

if (isset($_GET['scan_alsamixer'])) {
    $CMD = 'amixer';
    $response = [
        'success' => false,
        'message' => '',
        'devices' => []
    ];
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh trên máy chủ SSH.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $controls = stream_get_contents($stream_out);
    // Sử dụng regex để tìm từng điều khiển và các thuộc tính
    preg_match_all("/Simple mixer control '([^']*)',(\d+)[\s\S]*?(?=Simple mixer control|$)/", $controls, $control_blocks, PREG_SET_ORDER);
    $control_data = [];
    foreach ($control_blocks as $block) {
        // Lấy tên điều khiển và id
        $name = $block[1];
        $control_id = (int)$block[2];  // Chuyển đổi id thành số nguyên
        // Tìm các thông số trong block
        preg_match("/Capabilities: ([^\n]*)/", $block[0], $capabilities);
        preg_match("/Playback channels: ([^\n]*)/", $block[0], $playback_channels);
        preg_match("/Capture channels: ([^\n]*)/", $block[0], $capture_channels);
        preg_match("/Limits: ([^\n]*)/", $block[0], $limits);
        // Lấy giá trị cho từng kênh (nếu có)
        $values = [];
        preg_match_all("/(Front Left|Front Right|Mono): ([^\n]*)/", $block[0], $value_matches, PREG_SET_ORDER);
        foreach ($value_matches as $match) {
            $value_info = [
                "channel" => $match[1],
                "details" => trim($match[2])
            ];
            $values[] = $value_info;
        }
        // Đưa các thông số vào một mảng
        $final_output = [
            "id" => $control_id,  // Lưu ID điều khiển
            "name" => $name,  // Sử dụng trực tiếp tên control
            "capabilities" => isset($capabilities[1]) ? trim($capabilities[1]) : null,
            "playback_channels" => isset($playback_channels[1]) ? trim($playback_channels[1]) : null,
            "capture_channels" => isset($capture_channels[1]) ? trim($capture_channels[1]) : null,
            "limits" => isset($limits[1]) ? trim($limits[1]) : null,
            "values" => $values
        ];
        // Thêm vào danh sách điều khiển
        $control_data[] = $final_output;
    }
    // Cập nhật phản hồi
    $response['success'] = true;
    $response['message'] = 'Danh sách điều khiển âm thanh có trong alsamixer';
    $response['devices'] = $control_data;
    // Chuyển đổi sang định dạng JSON và trả về
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

#Scan các thiết bị Chạy VBot trong mạng Lan
if (isset($_GET['VBot_Device_Scaner'])) {
    $json_file_path = "$directory_path/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json";
    $json_dir_path = dirname($json_file_path);
    // Kiểm tra và tạo thư mục nếu chưa tồn tại
    if (!is_dir($json_dir_path)) {
        try {
            mkdir($json_dir_path, 0777, true);
			shell_exec("chmod 0777 " . escapeshellarg($json_dir_path));
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tạo thư mục: ' . $e->getMessage(),
                'data' => []
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
    // Kiểm tra và tạo file JSON nếu chưa tồn tại
    if (!file_exists($json_file_path)) {
        try {
            file_put_contents($json_file_path, json_encode([]));
            #chmod($json_file_path, 0777);
			shell_exec("chmod 0777 " . escapeshellarg($json_file_path));
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tạo file JSON: ' . $e->getMessage(),
                'data' => []
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/VBot_Device_Scaner.py");
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể kết nối tới máy chủ SSH.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Xác thực SSH không thành công.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể thực thi lệnh trên máy chủ SSH.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    stream_set_blocking($stream, true);
    $stdout = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    $output = stream_get_contents($stdout);
    $error_output = stream_get_contents($stderr);
    fclose($stream);
    if (!empty($error_output)) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi từ script Python: ' . $error_output,
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $json_output = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if ($json_output['success']) {
            //Lọc dữ liệu hoàn chỉnh (tất cả trường không được null)
            $complete_data = array_filter($json_output['data'], function ($device) {
                return !is_null($device['ip_address']) &&
                    !is_null($device['port_api']) &&
                    !is_null($device['host_name']) &&
                    !is_null($device['user_name']);
            });
            //Lưu dữ liệu hoàn chỉnh vào file JSON
            if (!empty($complete_data)) {
                try {
                    $existing_data = json_decode(file_get_contents($json_file_path), true);
                    if (!is_array($existing_data)) {
                        //Khởi tạo mảng rỗng nếu file JSON lỗi
                        $existing_data = [];
                    }
                    //Gộp dữ liệu mới, loại bỏ trùng lặp dựa trên ip_address
                    $ip_addresses = array_column($existing_data, 'ip_address');
                    foreach ($complete_data as $new_device) {
                        $index = array_search($new_device['ip_address'], $ip_addresses);
                        if ($index !== false) {
                            //Cập nhật nếu đã tồn tại
                            $existing_data[$index] = $new_device;
                        } else {
                            //Thêm mới nếu chưa có
                            $existing_data[] = $new_device;
                        }
                    }
                    if (!file_put_contents($json_file_path, json_encode(array_values($existing_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
                        throw new Exception('Không thể ghi dữ liệu vào file JSON.');
                    }
                    shell_exec("chmod 0777 " . escapeshellarg($json_file_path));
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Lỗi khi lưu dữ liệu: ' . $e->getMessage(),
                        'data' => []
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }
            $json_output['data'] = json_decode(file_get_contents($json_file_path), true) ?? [];
        }
        echo json_encode($json_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Phản hồi từ script Python không hợp lệ.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

//Xóa dữ liệu đã Scan các thiết bị sử dụng Vbot trong mạng Lan
if (isset($_GET['Clean_VBot_Device_Scaner'])) {
    $json_file_path = "$directory_path/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json";
    if (!file_exists($json_file_path)) {
        echo json_encode([
            'success' => false,
            'message' => 'File JSON không tồn tại.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    try {
        // Ghi mảng rỗng vào file JSON
        if (file_put_contents($json_file_path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
            throw new Exception('Không thể ghi dữ liệu vào file json');
        }
        //chmod($json_file_path, 0777);
		shell_exec("chmod 0777 " . escapeshellarg($json_file_path));
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa dữ liệu thành công',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi xóa dữ liệu: ' . $e->getMessage(),
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

//Scan VBot Client trong Mạng Lan
if (isset($_GET['VBot_Client_Device_Scaner'])) {
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/VBot_Client_Device_Scaner.py");
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể kết nối tới máy chủ SSH.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Xác thực SSH không thành công.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể thực thi lệnh trên máy chủ SSH.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    stream_set_blocking($stream, true);
    $stdout = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    $output = stream_get_contents($stdout);
    $error_output = stream_get_contents($stderr);
    fclose($stream);
    if (!empty($error_output)) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi từ script Python: ' . $error_output,
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $json_output = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($json_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Phản hồi từ script Python không hợp lệ.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

#Xác Thực, Liên Kết Với XiaoZhi
if (isset($_GET['XiaoZhi_Active'])) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'get_device_info') {
        //Lấy thông tin thiết bị
        $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/XiaoZhi_Active.py");
    } elseif ($action === 'signature_hmac') {
        //Tạo chữ ký HMAC cho challenge
        $challenge = isset($_GET['challenge']) ? escapeshellarg($_GET['challenge']) : "''";
        $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/XiaoZhi_Active.py") . " --sign $challenge";
    } else {
        echo json_encode(['success' => false, 'message' => 'Tham số truyền vào không đúng, không hợp lệ']);
        exit();
    }
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        echo json_encode(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH']);
        exit();
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        echo json_encode(['success' => false, 'message' => 'Xác thực SSH không thành công.']);
        exit();
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        echo json_encode(['success' => false, 'message' => 'Không thể thực thi lệnh trên máy chủ SSH.']);
        exit();
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    echo $output;
    exit();
}

//Kiểm tra nếu có dữ liệu POST với showJsonData_Client
if (isset($_POST['showJsonData_Client'])) {
    $ip_address = $_POST['showJsonData_Client'];
    if (empty($ip_address)) {
        echo json_encode([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu showJsonData_Client hoặc giá trị rỗng'
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
    $targetUrl = 'http://' . $ip_address . '/VBot_Client_Info';
    try {
        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Dữ liệu JSON không hợp lệ từ client: ' . $ip_address);
        }
        echo json_encode(array_merge(['success' => true], $data));
    } catch (Exception $e) {
        if (isset($ch)) {
            curl_close($ch);
        }
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
} else if (isset($_POST['xiaozhi'])) {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    if ($action === 'unlink_reset_data') {
        $Config['xiaozhi']['activation_status'] = false;
        $Config['xiaozhi']['device_id'] = null;
        $Config['xiaozhi']['serial_number'] = "";
        $Config['xiaozhi']['hmac_key'] = "";
        $Config['xiaozhi']['device_activation_code'] = "";
        $Config['xiaozhi']['system_options']['client_id'] = "";
        $Config['xiaozhi']['system_options']['device_id'] = "";
        $Config['xiaozhi']['system_options']['network']['firmware']['version'] = "";
        $Config['xiaozhi']['system_options']['network']['firmware']['url'] = "";
        $Config['xiaozhi']['system_options']['network']['websocket_url'] = "";
        $Config['xiaozhi']['system_options']['network']['websocket_access_token'] = "";
        $Config['xiaozhi']['system_options']['network']['mqtt_info']['endpoint'] = "";
        $Config['xiaozhi']['system_options']['network']['mqtt_info']['client_id'] = "";
        $Config['xiaozhi']['system_options']['network']['mqtt_info']['username'] = "";
        $Config['xiaozhi']['system_options']['network']['mqtt_info']['password'] = "";
        $Config['xiaozhi']['system_options']['network']['mqtt_info']['publish_topic'] = "";
        $Config['xiaozhi']['system_options']['network']['mqtt_info']['subscribe_topic'] = "";
        $result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($result_ConfigJson !== false) {
            $messages = "Đã hủy liên kết và Reset lại dữ liệu cấu hình trên thiết bị này thành công, bạn cần truy cập trang chủ của Server để xóa liên kết với thiết bị này";
            $success = true;
        } else {
            $messages = "Lỗi xảy ra khi hủy liên kết và Reset lại dữ liệu cấu hình";
            $success = false;
        }
        echo json_encode([
            'success' => $success,
            'message' => $messages,
            'data' => []
        ]);
        exit;
    } else if ($action === 'active_success_save_data') {
        $json_data = isset($_POST['json_data']) ? $_POST['json_data'] : '';
        $data = json_decode($json_data, true);
        if (empty($data)) {
            echo json_encode([
                'success' => false,
                'message' => 'Không có dữ liệu JSON hợp lệ'
            ]);
            exit;
        }
        $Config['xiaozhi']['activation_status'] = $data['activation_status'];
        $Config['xiaozhi']['device_activation_code'] = $data['activation_code'];
        $Config['xiaozhi']['device_id'] = $data['device_id'];
        $Config['xiaozhi']['hmac_key'] = $data['hmac_signature'];
        $Config['xiaozhi']['serial_number'] = $data['serial_number'];
        $Config['xiaozhi']['system_options']['client_id'] = $data['client_id'];
        $Config['xiaozhi']['system_options']['device_id'] = $data['mac_address'];
        $Config['xiaozhi']['system_options']['network']['websocket_url'] = $data['websocket_url'];
        $Config['xiaozhi']['system_options']['network']['websocket_access_token'] = $data['websocket_token'];
        $Config['xiaozhi']['system_options']['network']['mqtt_info'] = $data['mqtt'];
        $Config['xiaozhi']['system_options']['network']['firmware']['version'] = $data['firmware_version'];
        $result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($result_ConfigJson !== false) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã Kích Hoạt Và Lưu Dữ Liệu Thành Công, Hãy tải lại trang này và Khởi động lại chương trình để áp dụng dữ liệu mới',
                'data' => $json_data
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi xảy ra khi lưu dữ liệu kích hoạt',
                'data' => $json_data
            ]);
        }
        exit;
    } else if ($action === 'activation_status_false') {
        $Config['xiaozhi']['activation_status'] = false;
        $result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($result_ConfigJson !== false) {
            $messages = "Đã yêu cầu liên kết xác thực lại với máy chủ, Chương trình sẽ tự động xác thực lại ở phiên khởi động lần tới. Hoặc nhấn vào đây để: <center><button type='button' class='btn btn-sm btn-success ms-2' onclick='xiaozhi_active_device_info()'><i class='bi bi-link-45deg'></i> Tiến Hành Xác Thực Lại</button></center><br/>";
            $success = true;
        } else {
            $messages = "Lỗi xảy ra khi yêu cầu liên kết xác thực lại với máy chủ";
            $success = false;
        }
        echo json_encode([
            'success' => $success,
            'message' => $messages,
            'data' => []
        ]);
        exit;
    } else if ($action === 'activation_status_true') {
        $Config['xiaozhi']['activation_status'] = true;
        $result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($result_ConfigJson !== false) {
            $messages = "Thay đổi giá trị thành công, thiết bị đã được liên kết với máy chủ Server";
            $success = true;
        } else {
            $messages = "Thay đổi giá trị thất bại, thiết bị đã được liên kết với máy chủ Server";
            $success = false;
        }
        echo json_encode([
            'success' => $success,
            'message' => $messages,
            'data' => []
        ]);
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => "action không hợp lệ hoặc thiếu: {$action}",
            'data' => []
        ]);
        exit;
    }
    exit();
}
?>