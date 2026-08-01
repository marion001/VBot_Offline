<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['GET', 'POST']);
include '../../Configuration.php';

if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (
        !isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
    ) {
        session_unset();
        session_destroy();
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
        ], 401);
    }
}

if (isset($_GET['scan_mic'])) {
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/Scan_Mic.py");
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH'], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Xác thực SSH không thành công.'], 502);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh trên máy chủ SSH.'], 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    $decodedOutput = json_decode($output, true);
    if (!is_array($decodedOutput)) {
        error_log('Scan microphone returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse(['success' => false, 'message' => 'Trình quét microphone trả về dữ liệu không hợp lệ'], 502);
    }
    vbotApiJsonResponse($decodedOutput);
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
        vbotApiJsonResponse($response, 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công.';
        vbotApiJsonResponse($response, 502);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh trên máy chủ SSH.';
        vbotApiJsonResponse($response, 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $controls = stream_get_contents($stream_out);
    preg_match_all("/Simple mixer control '([^']*)',(\d+)[\s\S]*?(?=Simple mixer control|$)/", $controls, $control_blocks, PREG_SET_ORDER);
    $control_data = [];
    foreach ($control_blocks as $block) {
        $name = $block[1];
        $control_id = (int)$block[2];
        preg_match("/Capabilities: ([^\n]*)/", $block[0], $capabilities);
        preg_match("/Playback channels: ([^\n]*)/", $block[0], $playback_channels);
        preg_match("/Capture channels: ([^\n]*)/", $block[0], $capture_channels);
        preg_match("/Limits: ([^\n]*)/", $block[0], $limits);
        $values = [];
        preg_match_all("/(Front Left|Front Right|Mono): ([^\n]*)/", $block[0], $value_matches, PREG_SET_ORDER);
        foreach ($value_matches as $match) {
            $value_info = [
                "channel" => $match[1],
                "details" => trim($match[2])
            ];
            $values[] = $value_info;
        }
        $final_output = [
            "id" => $control_id,
            "name" => $name,
            "capabilities" => isset($capabilities[1]) ? trim($capabilities[1]) : null,
            "playback_channels" => isset($playback_channels[1]) ? trim($playback_channels[1]) : null,
            "capture_channels" => isset($capture_channels[1]) ? trim($capture_channels[1]) : null,
            "limits" => isset($limits[1]) ? trim($limits[1]) : null,
            "values" => $values
        ];
        $control_data[] = $final_output;
    }
    $response['success'] = true;
    $response['message'] = 'Danh sách điều khiển âm thanh có trong alsamixer';
    $response['devices'] = $control_data;
    vbotApiJsonResponse($response);
}

#Scan các thiết bị Chạy VBot trong mạng Lan
if (isset($_POST['VBot_Device_Scaner'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $json_file_path = "$directory_path/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json";
    $json_dir_path = dirname($json_file_path);
    if (!is_dir($json_dir_path)) {
        try {
            mkdir($json_dir_path, 0777, true);
            @chmod($json_dir_path, 0777);
        } catch (Exception $e) {
            error_log('VBot scanner directory creation failed: '.$e->getMessage());
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Không thể tạo thư mục lưu dữ liệu quét.',
                'data' => []
            ], 500);
        }
    }
    if (!file_exists($json_file_path)) {
        try {
            file_put_contents($json_file_path, json_encode([]), LOCK_EX);
            @chmod($json_file_path, 0777);
        } catch (Exception $e) {
            error_log('VBot scanner JSON creation failed: '.$e->getMessage());
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Không thể tạo file dữ liệu quét.',
                'data' => []
            ], 500);
        }
    }
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/VBot_Device_Scaner.py");
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối tới máy chủ SSH.',
            'data' => []
        ], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Xác thực SSH không thành công.',
            'data' => []
        ], 502);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể thực thi lệnh trên máy chủ SSH.',
            'data' => []
        ], 502);
    }
    stream_set_blocking($stream, true);
    $stdout = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    $output = stream_get_contents($stdout);
    $error_output = stream_get_contents($stderr);
    fclose($stream);
    if (!empty($error_output)) {
        error_log('VBot device scanner Python error: '.trim($error_output));
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Trình quét thiết bị VBot gặp lỗi.',
            'data' => []
        ], 502);
    }
    $json_output = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (!empty($json_output['success'])) {
            $scannerData = isset($json_output['data']) && is_array($json_output['data'])
                ? $json_output['data']
                : [];
            $complete_data = array_filter($scannerData, function ($device) {
                return is_array($device)
                    && !empty($device['ip_address'])
                    && isset($device['port_api'])
                    && isset($device['host_name'])
                    && isset($device['user_name']);
            });
            if (!empty($complete_data)) {
                try {
                    $existing_data = json_decode(file_get_contents($json_file_path), true);
                    if (!is_array($existing_data)) {
                        $existing_data = [];
                    }
                    $ip_addresses = array_column($existing_data, 'ip_address');
                    foreach ($complete_data as $new_device) {
                        $index = array_search($new_device['ip_address'], $ip_addresses);
                        if ($index !== false) {
                            $existing_data[$index] = $new_device;
                        } else {
                            $existing_data[] = $new_device;
                        }
                    }
                    if (!file_put_contents($json_file_path, json_encode(array_values($existing_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX)) {
                        throw new Exception('Không thể ghi dữ liệu vào file JSON.');
                    }
                    @chmod($json_file_path, 0777);
                } catch (Exception $e) {
                    error_log('VBot scanner data save failed: '.$e->getMessage());
                    vbotApiJsonResponse([
                        'success' => false,
                        'message' => 'Không thể lưu dữ liệu thiết bị đã quét.',
                        'data' => []
                    ], 500);
                }
            }
            $json_output['data'] = json_decode(file_get_contents($json_file_path), true) ?? [];
        }
        vbotApiJsonResponse($json_output);
    } else {
        error_log('VBot device scanner returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Phản hồi từ script Python không hợp lệ.',
            'data' => []
        ], 502);
    }
}

//Xóa dữ liệu đã Scan các thiết bị sử dụng Vbot trong mạng Lan
if (isset($_POST['Clean_VBot_Device_Scaner'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $json_file_path = "$directory_path/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json";
    if (!file_exists($json_file_path)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'File JSON không tồn tại.',
            'data' => []
        ], 404);
    }
    try {
        if (file_put_contents($json_file_path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
            throw new Exception('Không thể ghi dữ liệu vào file json');
        }
        @chmod($json_file_path, 0777);
        vbotApiJsonResponse([
            'success' => true,
            'message' => 'Đã xóa dữ liệu thành công',
            'data' => []
        ]);
    } catch (Exception $e) {
        error_log('VBot scanner cleanup failed: '.$e->getMessage());
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể xóa dữ liệu đã quét.',
            'data' => []
        ], 500);
    }
}

//Scan VBot Client trong Mạng Lan
if (isset($_GET['VBot_Client_Device_Scaner'])) {
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/VBot_Client_Device_Scaner.py");
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối tới máy chủ SSH.',
            'data' => []
        ], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Xác thực SSH không thành công.',
            'data' => []
        ], 502);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể thực thi lệnh trên máy chủ SSH.',
            'data' => []
        ], 502);
    }
    stream_set_blocking($stream, true);
    $stdout = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    $output = stream_get_contents($stdout);
    $error_output = stream_get_contents($stderr);
    fclose($stream);
    if (!empty($error_output)) {
        error_log('VBot client scanner Python error: '.trim($error_output));
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Trình quét VBot Client gặp lỗi.',
            'data' => []
        ], 502);
    }
    $json_output = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        vbotApiJsonResponse($json_output);
    } else {
        error_log('VBot client scanner returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Phản hồi từ script Python không hợp lệ.',
            'data' => []
        ], 502);
    }
}

//Kiểm tra phiên bản Bluetooth hoặc AirPlay
if (isset($_GET['check_version'])) {
    $type = strtolower(trim($_GET['check_version']));
    switch ($type) {
        case "bluetooth":
            $CMD = "bluealsad -V";
            break;
        case "airplay":
            $CMD = "shairport-sync --version";
            break;
        default:
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Tham số không hợp lệ. Chỉ hỗ trợ bluetooth hoặc airplay.',
                'data' => []
            ], 400);
    }
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối tới máy chủ SSH.',
            'data' => []
        ], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Xác thực SSH không thành công.',
            'data' => []
        ], 502);
    }
    $stream = ssh2_exec($connection, escapeshellcmd($CMD));
    if (!$stream) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể thực thi lệnh trên máy chủ SSH.',
            'data' => []
        ], 502);
    }
    stream_set_blocking($stream, true);
    $stdout = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    $output = trim(stream_get_contents($stdout));
    $error_output = trim(stream_get_contents($stderr));
    fclose($stdout);
    fclose($stderr);
    fclose($stream);
    if (!empty($error_output)) {
        error_log('Version scanner command failed: '.$error_output);
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể lấy thông tin phiên bản '.$type.'.',
            'data' => []
        ], 502);
    }
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Lấy phiên bản ' . $type . ' thành công.',
        'version' => $output
    ]);
}

#Xác Thực, Liên Kết Với XiaoZhi
if (isset($_GET['XiaoZhi_Active'])) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'get_device_info') {
        $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/XiaoZhi_Active.py");
    } elseif ($action === 'signature_hmac') {
        $challenge = isset($_GET['challenge']) ? escapeshellarg($_GET['challenge']) : "''";
        $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/XiaoZhi_Active.py") . " --sign $challenge";
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'Tham số truyền vào không đúng, không hợp lệ'], 400);
    }
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH'], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Xác thực SSH không thành công.'], 502);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh trên máy chủ SSH.'], 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    $decodedOutput = json_decode($output, true);
    if (!is_array($decodedOutput)) {
        error_log('XiaoZhi activation script returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse(['success' => false, 'message' => 'Chương trình xác thực XiaoZhi trả về dữ liệu không hợp lệ'], 502);
    }
    vbotApiJsonResponse($decodedOutput);
}

function vbotIsPrivateLanIpv4($ipAddress)
{
    if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    $ip = ip2long($ipAddress);
    $ranges = [
        [ip2long('10.0.0.0'), ip2long('10.255.255.255')],
        [ip2long('172.16.0.0'), ip2long('172.31.255.255')],
        [ip2long('192.168.0.0'), ip2long('192.168.255.255')]
    ];
    foreach ($ranges as $range) {
        if ($ip >= $range[0] && $ip <= $range[1]) {
            return true;
        }
    }
    return false;
}

// Kiểm tra nếu có dữ liệu POST với showJsonData_Client
if (isset($_POST['showJsonData_Client'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $ip_address = $_POST['showJsonData_Client'];
    if (empty($ip_address)) {
        vbotApiJsonResponse([
            'success' => false,
            'error' => 'Yêu cầu không hợp lệ, thiếu showJsonData_Client hoặc giá trị rỗng'
        ], 400);
    }
    if (!vbotIsPrivateLanIpv4($ip_address)) {
        vbotApiJsonResponse([
            'success' => false,
            'error' => 'Chỉ cho phép địa chỉ IPv4 thuộc mạng LAN riêng'
        ], 400);
    }
    $urls = [
        'http://' . $ip_address . '/VBot_Client_Info',
        'http://' . $ip_address . ':8081/VBot_Client_Info'
    ];
    $lastError = '';
    foreach ($urls as $targetUrl) {
        try {
            $ch = curl_init($targetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            $response = curl_exec($ch);
            if ($response === false) {
                throw new Exception(curl_error($ch));
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                throw new Exception('HTTP ' . $httpCode);
            }
            curl_close($ch);
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON không hợp lệ');
            }
            vbotApiJsonResponse(array_merge([
                'success' => true,
                'source_url' => $targetUrl
            ], $data));
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            if (isset($ch)) {
                curl_close($ch);
            }
        }
    }
    error_log('VBot Client info connection failed for '.$ip_address.': '.$lastError);
    vbotApiJsonResponse([
        'success' => false,
        'error' => 'Không thể kết nối tới client trên cả port 80 và 8081.'
    ], 502);
}

else if (isset($_POST['xiaozhi'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
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
        $result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if ($result_ConfigJson !== false) {
            $messages = "Đã hủy liên kết và Reset lại dữ liệu cấu hình trên thiết bị này thành công, bạn cần truy cập trang chủ của Server để xóa liên kết với thiết bị này";
            $success = true;
        } else {
            $messages = "Lỗi xảy ra khi hủy liên kết và Reset lại dữ liệu cấu hình";
            $success = false;
        }
        vbotApiJsonResponse([
            'success' => $success,
            'message' => $messages,
            'data' => []
        ], $success ? 200 : 500);
    } else if ($action === 'active_success_save_data') {
        $json_data = isset($_POST['json_data']) ? $_POST['json_data'] : '';
        $data = json_decode($json_data, true);
        if (empty($data)) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Không có dữ liệu JSON hợp lệ'
            ], 400);
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
        $result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if ($result_ConfigJson !== false) {
            vbotApiJsonResponse([
                'success' => true,
                'message' => 'Đã Kích Hoạt Và Lưu Dữ Liệu Thành Công, Hãy tải lại trang này và Khởi động lại chương trình để áp dụng dữ liệu mới',
                'data' => $json_data
            ]);
        } else {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Lỗi xảy ra khi lưu dữ liệu kích hoạt',
                'data' => $json_data
            ], 500);
        }
    } else if ($action === 'activation_status_false') {
        $Config['xiaozhi']['activation_status'] = false;
        $result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if ($result_ConfigJson !== false) {
            $messages = "Đã yêu cầu liên kết xác thực lại với máy chủ, Chương trình sẽ tự động xác thực lại ở phiên khởi động lần tới. Hoặc nhấn vào đây để: <center><button type='button' class='btn btn-sm btn-success ms-2' onclick='xiaozhi_active_device_info()'><i class='bi bi-link-45deg'></i> Tiến Hành Xác Thực Lại</button></center><br/>";
            $success = true;
        } else {
            $messages = "Lỗi xảy ra khi yêu cầu liên kết xác thực lại với máy chủ";
            $success = false;
        }
        vbotApiJsonResponse([
            'success' => $success,
            'message' => $messages,
            'data' => []
        ], $success ? 200 : 500);
    } else if ($action === 'activation_status_true') {
        $Config['xiaozhi']['activation_status'] = true;
        $result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if ($result_ConfigJson !== false) {
            $messages = "Thay đổi giá trị thành công, thiết bị đã được liên kết với máy chủ Server";
            $success = true;
        } else {
            $messages = "Thay đổi giá trị thất bại, thiết bị đã được liên kết với máy chủ Server";
            $success = false;
        }
        vbotApiJsonResponse([
            'success' => $success,
            'message' => $messages,
            'data' => []
        ], $success ? 200 : 500);
    } else {
        vbotApiJsonResponse([
            'success' => false,
            'message' => "action không hợp lệ hoặc thiếu: {$action}",
            'data' => []
        ], 400);
    }
}
?>
