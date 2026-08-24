<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

require_once __DIR__.'/Api_Helpers.php';
require_once __DIR__.'/Home_Assistant_Helpers.php';
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

function vbotCheckIsPrivateLanIpv4($ipAddress)
{
    if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    $ip = ip2long($ipAddress);
    return ($ip >= ip2long('10.0.0.0') && $ip <= ip2long('10.255.255.255'))
        || ($ip >= ip2long('172.16.0.0') && $ip <= ip2long('172.31.255.255'))
        || ($ip >= ip2long('192.168.0.0') && $ip <= ip2long('192.168.255.255'));
}

function vbotHassConfiguredUrl($requestedUrl, array $config)
{
    $requestedUrl = rtrim(trim((string)$requestedUrl), '/');
    foreach (['internal_url', 'external_url'] as $key) {
        $configuredUrl = rtrim(trim((string)($config[$key] ?? '')), '/');
        if ($configuredUrl !== '' && hash_equals($configuredUrl, $requestedUrl)) {
            return $configuredUrl;
        }
    }
    return false;
}

//Tets Code Yaml Hass
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yaml_test_control_homeassistant'])) {
    $yamlError = null;
    $parsedYaml = vbotHassParseActionYaml($_POST['yaml_test_control_homeassistant'], $yamlError);
    $actionData = is_array($parsedYaml) ? vbotHassValidateAction($parsedYaml, $yamlError) : null;
    if ($actionData === null) vbotApiJsonResponse(['success' => false, 'message' => $yamlError], 400);
    $action = $actionData['action'];
    $target = $actionData['target'];
    list($domain, $service) = explode('.', $action);
    $data = (array)$actionData['data'];
    $payload = array_merge($data, (array)$target);
    $headers = [
        "Authorization: Bearer " . $Config['home_assistant']['long_token'],
        "Content-Type: application/json"
    ];
    function sendRequest($url, $headers, $payload)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'message' => $error];
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        switch ($statusCode) {
            case 200:
                return ['success' => true, 'message' => 'Thao tác thành công'];
            case 400:
                return ['success' => false, 'message' => 'Lỗi: 400 - Yêu cầu không hợp lệ'];
            case 401:
                return ['success' => false, 'message' => 'Lỗi: 401 - Không được phép'];
            case 404:
                return ['success' => false, 'message' => 'Lỗi: 404 - Không tìm thấy'];
            case 405:
                return ['success' => false, 'message' => 'Lỗi: 405 - Phương pháp không được phép'];
            default:
                return ['success' => false, 'message' => 'Lỗi: ' . $statusCode];
        }
    }
    $internalHassUrl = rtrim((string)$Config['home_assistant']['internal_url'], '/');
    $externalHassUrl = rtrim((string)$Config['home_assistant']['external_url'], '/');
    $response = sendRequest($internalHassUrl . '/api/services/' . $domain . '/' . $service, $headers, $payload);
    if (!$response['success']) {
        $response = sendRequest($externalHassUrl . '/api/services/' . $domain . '/' . $service, $headers, $payload);
    }
    vbotApiJsonResponse($response, $response['success'] ? 200 : 502);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ping_status'])) {
    if (!isset($_POST['ip']) || empty($_POST['ip'])) {
        vbotApiJsonResponse([
            "success" => false,
            "message" => "Không có IP nào được cung cấp",
            "data" => []
        ], 400);
    }
    $ip_input = $_POST['ip'];
    if (is_array($ip_input)) {
        $ip_input = implode(",", $ip_input);
    }
    $ip_input = escapeshellarg($ip_input);
    $CMD = 'python3 '.$Config['web_interface']['path'].'/includes/php_ajax/Ping.py '.$ip_input;
    $result = [
        'success' => false,
        'message' => '',
        'data' => []
    ];
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $result['message'] = "Kết nối SSH không thành công";
        vbotApiJsonResponse($result, 502);
    }
    if (!@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $result['message'] = "Xác thực SSH không thành công";
        vbotApiJsonResponse($result, 502);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $result['message'] = "Không thể thực thi lệnh.";
        vbotApiJsonResponse($result, 502);
    }
    stream_set_blocking($stream, true);
    $output = stream_get_contents($stream);
    fclose($stream);
    if (!$output) {
        $result['message'] = "Phản hồi trống từ Python";
        vbotApiJsonResponse($result, 502);
    }
    $decoded = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $result['message'] = "JSON không hợp lệ từ Python";
        $result['raw'] = $output;
        error_log('Ping script returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse($result, 502);
    }
    vbotApiJsonResponse($decoded);
}

#Kiểm tra trạng thái các thiết bị chạy Vbot Server trong mạng lan
if (isset($_GET['check_status_vbot_server_in_lan'])) {
    $ip = isset($_GET['ip']) ? $_GET['ip'] : '';
    $port = isset($_GET['port']) ? $_GET['port'] : '';
    if (empty($ip) || empty($port)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Thiếu IP hoặc cổng PORT'], 400);
    }
    if (!vbotCheckIsPrivateLanIpv4($ip) || !ctype_digit((string)$port) || (int)$port < 1 || (int)$port > 65535) {
        vbotApiJsonResponse(['success' => false, 'message' => 'IP hoặc cổng PORT không hợp lệ'], 400);
    }
    $url = "http://" . $ip . ":" . $port;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        error_log('VBot server status cURL failed for '.$ip.':'.$port.': '.curl_error($curl));
        curl_close($curl);
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới thiết bị'], 502);
    }
    curl_close($curl);
    $success = false;
    $message = "";
    if ($response) {
        $json_response = json_decode($response, true);
        if (isset($json_response['success']) && $json_response['success'] === true) {
            $success = true;
            $message = "Thiết bị đang trực tuyến";
        } else {
            vbotApiJsonResponse(['success' => false, 'message' => 'Thiết bị ngoại tuyến, hoặc chương trình VBot chưa được khởi chạy'], 502);
        }
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không nhận được phản hồi'], 502);
    }
    vbotApiJsonResponse(['success' => $success, 'message' => $message, 'ip_address' => $ip, 'port_api' => $port]);
}

//Thêm thiết bị chạy Vbot Server thủ công bằng IP
if (isset($_POST['add_ip_vbot_server'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
    if (empty($ip)) {
        vbotApiJsonResponse(['success' => false, 'error' => 'Thiếu địa chỉ IP'], 400);
    }
    if (!vbotCheckIsPrivateLanIpv4($ip)) {
        vbotApiJsonResponse(['success' => false, 'error' => 'Chỉ cho phép địa chỉ IPv4 thuộc mạng LAN riêng'], 400);
    }
    $url = "http://$ip/VBot_API.php";
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET'
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    if (!$response) {
        vbotApiJsonResponse(['success' => false, 'error' => 'Không thể kết nối đến IP'], 502);
    }
    $json = json_decode($response, true);
    if (!isset($json['success']) || $json['success'] !== true) {
        vbotApiJsonResponse(['success' => false, 'error' => 'API thiết bị trả về dữ liệu không hợp lệ'], 502);
    }
    $device = [
        'ip_address' => $json['ip_address'] ?? $ip,
        'port_api' => $json['port_api'] ?? 5002,
        'host_name' => $json['host_name'] ?? '',
        'user_name' => $json['user_name'] ?? ''
    ];
    $json_path = $directory_path . '/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json';
    $dir_path = dirname($json_path);
    if (!is_dir($dir_path)) {
        mkdir($dir_path, 0777, true);
        @chmod($dir_path, 0777);
    }
    if (!file_exists($json_path)) {
        file_put_contents($json_path, "[]", LOCK_EX);
        @chmod($json_path, 0777);
    }
    $devices = [];
    if (file_exists($json_path)) {
        $content = file_get_contents($json_path);
        $devices = json_decode($content, true);
        if (!is_array($devices)) {
            $devices = [];
        }
    }
    $updated = false;
    foreach ($devices as &$d) {
        if ($d['ip_address'] === $device['ip_address']) {
            $d = $device;
            $updated = true;
            break;
        }
    }
    unset($d);
    if (!$updated) {
        $devices[] = $device;
    }
    file_put_contents($json_path, json_encode($devices, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    @chmod($json_path, 0777);
    vbotApiJsonResponse(['success' => true, 'device' => $device]);
}

//Xóa thiết bị chạy Vbot Server thủ công bằng IP
if (isset($_POST['delete_ip_vbot_server'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
    if (empty($ip)) {
        vbotApiJsonResponse(['success' => false, 'error' => 'Thiếu địa chỉ IP'], 400);
    }
    if (!vbotCheckIsPrivateLanIpv4($ip)) {
        vbotApiJsonResponse(['success' => false, 'error' => 'Địa chỉ IPv4 không hợp lệ'], 400);
    }
    $json_path = $directory_path . '/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json';
    $dir_path = dirname($json_path);
    if (!is_dir($dir_path)) {
        mkdir($dir_path, 0777, true);
        @chmod($dir_path, 0777);
    }
    if (!file_exists($json_path)) {
        file_put_contents($json_path, "[]", LOCK_EX);
        @chmod($json_path, 0777);
    }
    $devices = [];
    if (file_exists($json_path)) {
        $content = file_get_contents($json_path);
        $devices = json_decode($content, true);
        if (!is_array($devices)) {
            $devices = [];
        }
    }
    $original_count = count($devices);
    $devices = array_filter($devices, function ($device) use ($ip) {
        return $device['ip_address'] !== $ip;
    });
    $devices = array_values($devices);
    if (count($devices) < $original_count) {
        file_put_contents($json_path, json_encode($devices, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        @chmod($json_path, 0777);
        vbotApiJsonResponse(['success' => true, 'message' => 'Xóa thiết bị thành công', 'ip_address' => $ip]);
    } else {
        vbotApiJsonResponse(['success' => false, 'error' => 'Không tìm thấy dữ liệu IP tương ứng để xóa'], 404);
    }
}

#kiểm tra kết nối tới SSH Server
if (isset($_GET['check_ssh'])) {
    $ssh_host = $_GET['host'];
    $ssh_port = $_GET['port'];
    $ssh_user = $_GET['user'];
    $ssh_pass = $_GET['pass'];
    $response = [
        'success' => false,
        'message' => '',
    ];
    if (empty($ssh_host) || empty($ssh_user) || empty($ssh_pass) || empty($ssh_port)) {
        $response['message'] = 'Vui lòng cung cấp đầy đủ ssh_host, ssh_port, ssh_user và ssh_pass.';
        vbotApiJsonResponse($response, 400);
    }
    if (!function_exists('ssh2_connect')) {
        $response['message'] = 'Tiện ích mở rộng PHP SSH2 chưa được cài đặt: sudo apt-get install php-ssh2';
        vbotApiJsonResponse($response, 500);
    }
    $connection = @ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH, Kiểm tra lại địa chỉ máy chủ hoặc port, hoặc SSH chưa được kích hoạt trên máy chủ';
        vbotApiJsonResponse($response, 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_pass)) {
        $response['message'] = 'Xác thực SSH thất bại, Kiểm tra lại Tên Đăng Nhập hoặc Mật Khẩu';
        vbotApiJsonResponse($response, 401);
    }
    $response['success'] = true;
    $response['message'] = 'Kết nối SSH thành công!';
    ssh2_disconnect($connection);
    gc_collect_cycles();
    vbotApiJsonResponse($response);
}

#Lệnh Command SSH
if (isset($_POST['VBot_CMD'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $Command = isset($_POST['Command']) ? $_POST['Command'] : '';
    if (empty($Command)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không có dữ liệu câu lệnh đầu vào',
            'data' => null
        ], 400);
    }
    $Command_decode = base64_decode($Command);
    $connection = ssh2_connect($ssh_host, $ssh_port);
    $result = [
        'success' => false,
        'message' => '',
        'data' => null,
    ];
    if ($connection) {
        if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            $stream = ssh2_exec($connection, $Command_decode);
            if ($stream) {
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                $result['success'] = true;
                $result['message'] = 'Lệnh: "' . $Command_decode . '" đã được thực thi.';
                $result['data'] = $output;
            } else {
                $result['message'] = 'Không thể thực thi lệnh trên SSH.';
            }
        } else {
            $result['message'] = 'Xác thực SSH không thành công.';
        }
    } else {
        $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
    }
    vbotApiJsonResponse($result, $result['success'] ? 200 : 502);
}

//Kiểm tra phiên bản AirPlay
if (isset($_GET['check_version_airplay'])) {
    $result = [
        'success' => false,
        'current_version' => '',
        'latest_version'  => '',
        'description'     => '',
        'update'          => false,
        'message'         => 'Không có bản cập nhật mới nào'
    ];
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $result['message'] = 'Không thể kết nối SSH.';
        vbotApiJsonResponse($result, 502);
    }
    if (!@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $result['message'] = 'Xác thực SSH thất bại.';
        vbotApiJsonResponse($result, 401);
    }
    $stream = ssh2_exec($connection, "shairport-sync -V");
    stream_set_blocking($stream, true);
    $current = trim(stream_get_contents($stream));
    if (preg_match('/^(VBot_[^-]+-[0-9.]+)/', $current, $match)) {
        $current_version = $match[1];
    } else {
        $current_version = $current;
    }
    $result['current_version'] = $current_version;
    $url = "https://api.github.com/repos/marion001/shairport-sync/contents/Version.json";
    $context = stream_context_create([
        "http" => [
            "header" =>
                "User-Agent: VBot\r\n" .
                "Accept: application/vnd.github+json\r\n"
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $result['message'] = 'Không lấy được dữ liệu từ GitHub.';
        vbotApiJsonResponse($result, 502);
    }
    $github = json_decode($response, true);
    if (!isset($github['content'])) {
        $result['message'] = 'Version.json không hợp lệ.';
        vbotApiJsonResponse($result, 502);
    }
    $json = base64_decode(str_replace("\n", "", $github['content']));
    $version = json_decode($json, true);
    if (!$version) {
        $result['message'] = 'Không đọc được Version.json.';
        vbotApiJsonResponse($result, 502);
    }
    $result['latest_version'] = $version['build_date'];
    $result['description'] = $version['description'];
    $result['update'] = ($current_version !== $version['build_date']);
    $result['success'] = true;
    vbotApiJsonResponse($result);
}

#Chạy Chương trình VBot
if (isset($_POST['start_vbot_service'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $CMD = "systemctl --user start VBot_Offline.service";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    $result = [
        'success' => false,
        'message' => ''
    ];
    if ($connection) {
        if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            $stream = ssh2_exec($connection, $CMD);
            stream_set_blocking($stream, true);
            $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
            $result['success'] = true;
            $result['message'] = 'Dịch vụ VBot đã được khởi chạy thành công.';
        } else {
            $result['message'] = 'Xác thực SSH không thành công.';
        }
    } else {
        $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
    }
    vbotApiJsonResponse($result, $result['success'] ? 200 : 502);
}

#Dừng Chương trình VBot
if (isset($_POST['stop_vbot_service'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $CMD = "systemctl --user stop VBot_Offline.service";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    $result = [
        'success' => false,
        'message' => ''
    ];
    if ($connection) {
        if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            $stream = ssh2_exec($connection, $CMD);
            stream_set_blocking($stream, true);
            $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
            $result['success'] = true;
            $result['message'] = 'Dịch vụ VBot đã được dừng thành công.';
        } else {
            $result['message'] = 'Xác thực SSH không thành công.';
        }
    } else {
        $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
    }
    vbotApiJsonResponse($result, $result['success'] ? 200 : 502);
}

#Khởi động lại Chương trình VBot
if (isset($_POST['restart_vbot_service'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $CMD = "systemctl --user restart VBot_Offline.service";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    $result = [
        'success' => false,
        'message' => ''
    ];
    if ($connection) {
        if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            $stream = ssh2_exec($connection, $CMD);
            stream_set_blocking($stream, true);
            $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
            $result['success'] = true;
            $result['message'] = 'Dịch vụ VBot đã được khởi động lại thành công.';
        } else {
            $result['message'] = 'Xác thực SSH không thành công.';
        }
    } else {
        $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
    }
    vbotApiJsonResponse($result, $result['success'] ? 200 : 502);
}

#Khởi động lại toàn bộ hệ thống
if (isset($_POST['reboot_os'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $CMD = "sudo reboot";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    $result = [
        'success' => false,
        'message' => ''
    ];
    if ($connection) {
        if (ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            #$stream = ssh2_exec($connection, $CMD);
            #stream_set_blocking($stream, true);
            #$output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
			ssh2_exec($connection, $CMD);
            $result['success'] = true;
            $result['message'] = 'Đang khởi động lại toàn bộ hệ thống';
        } else {
            $result['message'] = 'Xác thực SSH không thành công.';
        }
    } else {
        $result['message'] = 'Không thể kết nối tới máy chủ SSH.';
    }
    vbotApiJsonResponse($result, $result['success'] ? 200 : 502);
}

#Kiểm tra kết nối HASS bằng URL/token đã lưu; không đưa token vào query string.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_hass'])) {
    $url = vbotHassConfiguredUrl($_POST['url_hass'] ?? '', $Config['home_assistant']);
    $token = (string)($Config['home_assistant']['long_token'] ?? '');
    if ($url !== false && $token !== '') {
        $ch = curl_init($url . '/api/config');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            error_log('Home Assistant connection check failed: '.$curlError);
            curl_close($ch);
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới Home Assistant'], 502);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode >= 200 && $httpCode < 300) {
                vbotApiJsonResponse(['success' => true, 'message' => 'Kết nối thành công', 'response' => json_decode($response)]);
            } else if ($httpCode === 401) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Kết nối thất bại, Mã token không đúng', 'response' => json_decode($response)], 401);
            } else {
                error_log('Home Assistant check returned HTTP '.$httpCode);
                vbotApiJsonResponse(['success' => false, 'message' => 'Home Assistant trả về lỗi HTTP '.$httpCode], 502);
            }
        }
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'URL/token không hợp lệ hoặc chưa được lưu trong Config'], 400);
    }
}

#Lấy dữ liệu HASS bằng POST + CSRF và chỉ kết nối URL đã lưu trong Config.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_hass_all'])) {
    $url = vbotHassConfiguredUrl($_POST['url_hass'] ?? '', $Config['home_assistant']);
    $token = (string)($Config['home_assistant']['long_token'] ?? '');
    if ($url !== false && $token !== '') {
        $ch = curl_init($url . '/api/states');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            error_log('Home Assistant state download failed: '.$curlError);
            curl_close($ch);
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lấy dữ liệu Home Assistant'], 502);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 200 && $httpCode < 300) {
                $filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
                $existingData = file_exists($filePath_HASS)
                    ? json_decode((string)file_get_contents($filePath_HASS), true)
                    : ['get_hass_all' => []];
                if (!is_array($existingData)) {
                    $existingData = [];
                }
                $existingData['get_hass_all'] = json_decode($response);
                $jsonData = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($jsonData === false || !vbotAtomicWriteFile($filePath_HASS, $jsonData, 'danh sách thiết bị Home Assistant')) {
                    curl_close($ch);
                    vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu dữ liệu Home Assistant'], 500);
                }
                @chmod($filePath_HASS, 0777);
                curl_close($ch);
                vbotApiJsonResponse(['success' => true, 'message' => 'Kết nối thành công', 'response' => json_decode($response)]);
            } else if ($httpCode === 401) {
                curl_close($ch);
                vbotApiJsonResponse(['success' => false, 'message' => 'Kết nối thất bại, Mã token không đúng', 'response' => json_decode($response)], 401);
            } else {
                curl_close($ch);
                error_log('Home Assistant states returned HTTP '.$httpCode);
                vbotApiJsonResponse(['success' => false, 'message' => 'Home Assistant trả về lỗi HTTP '.$httpCode], 502);
            }
        }
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'URL/token không hợp lệ hoặc chưa được lưu trong Config'], 400);
    }
}

#Xóa dữ liệu Hass đã lấy
if (isset($_POST['del_get_hass_all'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $response = [
        'success' => false,
        'message' => 'Đã có lỗi xảy ra.'
    ];
    $filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
    $existingData = file_exists($filePath_HASS)
        ? json_decode((string)file_get_contents($filePath_HASS), true)
        : ['get_hass_all' => []];
    if ($existingData === null) {
        $existingData = [];
    }
    $existingData['get_hass_all'] = [];
    $jsonData = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonData !== false) {
        if (vbotAtomicWriteFile($filePath_HASS, $jsonData, 'xóa danh sách thiết bị Home Assistant')) {
            @chmod($filePath_HASS, 0777);
            $response['success'] = true;
            $response['message'] = 'Dữ Liệu Đồng Bộ trước đó đã được xóa thành công.';
        } else {
            $response['message'] = 'Lỗi: Không thể lưu dữ liệu rỗng vào file.';
        }
    } else {
        $response['message'] = 'Lỗi: Không thể chuyển đổi dữ liệu thành JSON.';
    }
    vbotApiJsonResponse($response, $response['success'] ? 200 : 500);
}

#Kiểm tra key Picovoice
if (isset($_GET['check_key_picovoice'])) {
    $key =  str_replace(' ', '+', @$_GET['key']);
    $lang_code = @$_GET['lang'];
    $response = [
        'success' => false,
        'message' => '',
    ];
    if (empty($_GET['lang']) || empty($_GET['key'])) {
        $response['message'] = 'Vui lòng cung cấp đầy đủ key, lang';
        vbotApiJsonResponse($response, 400);
    }
	if (!in_array($lang_code, ['vi', 'eng', 'customize'], true)) {
		$response['message'] = 'Chỉ hỗ trợ kiểm tra key với ngôn ngữ vi, eng hoặc customize';
		vbotApiJsonResponse($response, 400);
	}
    $lang_path = $VBot_Offline . 'resource/hotword/' . $lang_code;
    if ($lang_code === 'customize') {
        //Script Python sẽ đọc keyword_paths và model_file_path từ Dev_Picovoice.py.
        $modelFilePath = '';
    } else {
        $modelFilePath = $VBot_Offline . 'resource/picovoice/library/' .
            $Config['smart_config']['smart_wakeup']['hotword']['library'][$lang_code]['modelFilePath'];
    }
    $CMD = 'python3 ' .
        escapeshellarg($directory_path . '/includes/php_ajax/Check_Key_Picovoice.py') . ' ' .
        escapeshellarg($key) . ' ' .
        escapeshellarg($lang_path) . ' ' .
        escapeshellarg($modelFilePath);
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        vbotApiJsonResponse($response, 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công.';
        vbotApiJsonResponse($response, 401);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh trên máy chủ SSH.';
        vbotApiJsonResponse($response, 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    $picovoiceResult = json_decode(trim($output), true);
    if (!is_array($picovoiceResult)) {
        error_log('Picovoice key checker returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse(['success' => false, 'message' => 'Trình kiểm tra Picovoice trả về dữ liệu không hợp lệ'], 502);
    }
    vbotApiJsonResponse($picovoiceResult);
}

#Kiểm tra Kết Nối MQTT
if (isset($_GET['check_mqtt'])) {
    if (isset($_GET['host'], $_GET['port'], $_GET['user'], $_GET['pass'])) {
        require('./phpMQTT.php');
        $server = $_GET['host'];
        $port = (int)$_GET['port'];
        $username = $_GET['user'];
        $password = $_GET['pass'];
        if ($server === '' || $port < 1 || $port > 65535) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Máy chủ hoặc cổng MQTT không hợp lệ'], 400);
        }
        $client_id = 'VBot_TEST_CONNECT_MQTT_client_' . uniqid();
        $mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);
        if ($mqtt->connect(true, NULL, $username, $password)) {
            $response = [
                'success' => true,
                'message' => 'Kết nối tới máy chủ MQTT thành công: ' . $server . ':' . $port
            ];
            $mqtt->close();
        } else {
            $response = [
                'success' => false,
                'message' => 'Không thể kết nối tới máy chủ MQTT: ' . $server . ':' . $port . ' hãy kiểm tra lại Cổng Port, Tài Khoản,, Mật Khẩu'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Thiếu thông tin kết nối MQTT, cần nhập đủ thông tin: Máy Chủ MQTT, Cổng PORT, Tài Khoản, Mật Khẩu, Hoặc máy chủ MQTT có lỗi, không hoạt động'
        ];
    }
    vbotApiJsonResponse($response, $response['success'] ? 200 : 502);
}

#Lấy Phiên Bản Picovoice
if (isset($_GET['Picovoice_Version'])) {
    $url = 'https://pypi.org/rss/project/picovoice/releases.xml';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false || $http_code !== 200) {
        error_log('Picovoice RSS request failed: '.curl_error($ch).' (HTTP '.$http_code.')');
        curl_close($ch);
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối tới RSS feed.',
        ], 502);
    } else {
        header('Content-Type: application/xml');
        echo $response;
    }
    curl_close($ch);
    exit();
}

#Chatbox: dùng POST để nội dung trò chuyện không xuất hiện trong URL và access log.
if (isset($_POST['vbot_chatbox'])) {
    vbotApiVerifyCsrf();
    if (!isset($_POST['ip_port']) || !isset($_POST['text'])) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Thiếu một hoặc nhiều tham số: ip:port, text'
        ], 400);
    }
    $ip_port = trim((string)$_POST['ip_port']);
    $text = trim((string)$_POST['text']);
    if ($text === '' || strlen($text) > 10000) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Nội dung Chatbox trống hoặc vượt quá giới hạn cho phép'], 400);
    }
    $chatHost = parse_url($ip_port, PHP_URL_HOST);
    if (
        !filter_var($ip_port, FILTER_VALIDATE_URL)
        || strtolower((string)parse_url($ip_port, PHP_URL_SCHEME)) !== 'http'
        || !vbotCheckIsPrivateLanIpv4($chatHost)
    ) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Địa chỉ Chatbox không hợp lệ hoặc không thuộc mạng LAN riêng'], 400);
    }
    $curl = curl_init();
    $postData = json_encode([
        'type' => 3,
        'data' => 'main_processing',
        'action' => 'chatbot',
        'value' => $text
    ]);
    curl_setopt_array($curl, array(
        CURLOPT_URL => $ip_port,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($curl);
    if ($response === false) {
        $curlError = curl_error($curl);
        $curlErrno = curl_errno($curl);
        curl_close($curl);
        error_log('VBot chatbox cURL failed: '.$curlError.' ('.$curlErrno.')');
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối tới Chatbox',
            'error_code' => $curlErrno
        ], 502);
    }
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpCode !== 200) {
        error_log('VBot chatbox returned HTTP '.$httpCode);
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Yêu cầu thất bại với mã HTTP: ' . $httpCode,
        ], 502);
    }
    $jsonResponse = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('VBot chatbox returned invalid JSON: '.substr(trim($response), 0, 2000));
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Chatbox trả về dữ liệu JSON không hợp lệ'
        ], 502);
    }
    if (!isset($jsonResponse['success']) || !isset($jsonResponse['message'])) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Dữ liệu JSON trả về không đúng định dạng'
        ], 502);
    }
    vbotApiJsonResponse($jsonResponse);
}

//Lấy token zai_did tts_default
if (isset($_GET['get_token_tts_default_zai_did'])) {
    $ch = curl_init(base64_decode('aHR0cHM6Ly9haS56YWxvLnNvbHV0aW9ucw'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log('Zalo token request failed: '.$error);
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối tới dịch vụ lấy token Zalo'
        ], 502);
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    curl_close($ch);
    preg_match_all('/Set-Cookie:\s*(zai_did=[^;]+);.*?Expires=([^;]+);?/i', $header, $matches);
    if (!empty($matches[1]) && !empty($matches[2])) {
        $cookie_value = $matches[1][0];
        $expires_raw = $matches[2][0];
        try {
            $dt = new DateTime($expires_raw, new DateTimeZone('GMT'));
            $dt->modify('-10 days');
            $expires_iso = $dt->format('Y-m-d\TH:i:sP');
            $zai_did_value = explode('=', $cookie_value)[1];
            vbotApiJsonResponse([
                'success' => true,
                'message' => 'Lấy Token zai_did thành công, hãy Lưu Cài Đặt Cấu Hình Config để áp dụng',
                'zai_did' => $zai_did_value,
                'expires_zai_did' => $expires_iso
            ]);
        } catch (Exception $e) {
            error_log('Zalo token expiry parse failed: '.$e->getMessage());
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Không thể xử lý thời hạn token Zalo'
            ], 502);
        }
    } else {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không tìm thấy zai_did hoặc thời gian hết hạn. Vui lòng thử lại'
        ], 502);
    }
}

//Lấy danh sách giọng đọc của google cloud
if (isset($_GET['get_ggcloud_voice_name'])) {
    $voiceScript = rtrim((string)$Config['web_interface']['path'], '/\\') . '/includes/php_ajax/Get_Voice_Name_GCloud.py';
    $CMD = 'python3 ' . escapeshellarg($voiceScript);
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối SSH'
        ], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Xác thực SSH thất bại'
        ], 401);
    }
    $stream = ssh2_exec($connection, $CMD);
    stream_set_blocking($stream, true);
    $output = stream_get_contents(
        ssh2_fetch_stream($stream, SSH2_STREAM_STDIO)
    );
    $voiceData = json_decode(trim($output), true);
    if (!is_array($voiceData)) {
        error_log('Google Cloud voice script returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse(['success' => false, 'message' => 'Danh sách giọng Google Cloud không hợp lệ'], 502);
    }
    vbotApiJsonResponse($voiceData);
}

//Lấy danh sách model trợ lý Google Gemini. API key chỉ nhận qua POST để
//không xuất hiện trong URL, lịch sử trình duyệt hoặc access log của WebUI.
if (isset($_POST['get_model_gemini'])) {
    vbotApiVerifyCsrf(true);
    $apiKey     = trim((string)($_POST['apikey'] ?? ''));
    $versionAPI = (string)($_POST['version_api'] ?? 'v1beta');
    if (empty($apiKey)) {
        vbotApiJsonResponse([
            "success" => false,
            "message" => "Thiếu tham số apikey"
        ], 400);
    }
    if (!in_array($versionAPI, ['v1', 'v1beta'], true)) {
        vbotApiJsonResponse([
            "success" => false,
            "message" => "version_api không hợp lệ (chỉ v1 hoặc v1beta)"
        ], 400);
    }
    $url = "https://generativelanguage.googleapis.com/{$versionAPI}/models?key={$apiKey}";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);
    if ($response === false) {
        error_log('Gemini model request failed: '.$error);
        vbotApiJsonResponse([
            "success" => false,
            "message" => 'Không thể kết nối tới API Gemini'
        ], 502);
    }
    if ($http_code !== 200) {
        error_log('Gemini model API returned HTTP '.$http_code.': '.substr(trim($response), 0, 2000));
        $remoteError = json_decode($response, true);
        $remoteMessage = isset($remoteError['error']['message'])
            ? $remoteError['error']['message']
            : 'API Gemini trả về lỗi HTTP '.$http_code;
        vbotApiJsonResponse(['success' => false, 'message' => $remoteMessage], 502);
    }
    $json = json_decode($response, true);
    if (!isset($json['models']) || !is_array($json['models'])) {
        vbotApiJsonResponse([
            "success" => false,
            "message" => "Dữ liệu Gemini không hợp lệ"
        ], 502);
    }
    $modelList = [];
	foreach ($json['models'] as $model) {
		if (empty($model['name'])) {
			continue;
		}
		$name = preg_replace('#^models/#', '', $model['name']);
		//CHỈ LẤY GEMINI CHAT
		if (strpos($name, 'gemini-') !== 0) {
			continue;
		}
		//loại embedding / image / video / robotics / exp
		if (
			strpos($name, 'embedding') !== false ||
			strpos($name, 'image') !== false ||
			strpos($name, 'video') !== false ||
			strpos($name, 'robotics') !== false ||
			strpos($name, 'exp') !== false
		) {
			continue;
		}
		$modelList[] = $name;
	}
    $modelList = array_values(array_unique($modelList));
    sort($modelList);
	$outputFile = $directory_path . '/includes/other_data/gemini_model_list.json';
	$existingData = [];
	if (file_exists($outputFile)) {
		$existingData = json_decode(file_get_contents($outputFile), true);
		if (!is_array($existingData)) {
			$existingData = [];
		}
	}
	$existingData['gemini_models'] = $modelList;
	$encodedModelList = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if ($encodedModelList === false || !vbotAtomicWriteFile($outputFile, $encodedModelList, 'danh sách Gemini Model')) {
		vbotApiJsonResponse([
			"success" => false,
			"message" => "Không ghi được file JSON"
		], 500);
	}
	@chmod($outputFile, 0777);
    vbotApiJsonResponse([
        "success" => true,
        "count" => count($modelList),
		"message" => "Lấy dữ liệu Model Gemini thành công"
        #"output_file" => $outputFile
    ]);
}

?>
