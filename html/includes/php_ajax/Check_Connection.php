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

//Tets Code Yaml Hass
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yaml_test_control_homeassistant'])) {
    $actionData = json_decode($_POST['yaml_test_control_homeassistant'], true);
    if (!$actionData || empty($actionData['action']) || empty($actionData['target']['entity_id'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu "action" hoặc "entity_id" trong dữ liệu']);
        exit;
    }
    $action = $actionData['action'];
    $target = $actionData['target'];
    $entity_id = $target['entity_id'];
    list($domain, $service) = explode('.', $action);
    $data = is_array($actionData['data'] ?? null) ? $actionData['data'] : [];
    if (is_string($entity_id)) {
        $payload = array_merge(['entity_id' => [$entity_id]], $data);
    } elseif (is_array($entity_id)) {
        $payload = array_merge(['entity_id' => $entity_id], $data);
    } else {
        $payload = array_merge(['entity_id' => []], $data);
    }
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
    $response = sendRequest($Config['home_assistant']['internal_url'] . '/api/services/' . $domain . '/' . $service, $headers, $payload);
    if (!$response['success']) {
        $response = sendRequest($Config['home_assistant']['external_url'] . '/api/services/' . $domain . '/' . $service, $headers, $payload);
    }
    echo json_encode($response);
    exit;
}

#Kiểm tra trạng thái các thiết bị chạy Vbot Server trong mạng lan
if (isset($_GET['check_status_vbot_server_in_lan'])) {
    $ip = isset($_GET['ip']) ? $_GET['ip'] : '';
    $port = isset($_GET['port']) ? $_GET['port'] : '';
    if (empty($ip) || empty($port)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu IP hoặc cổng PORT']);
        exit;
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
        echo json_encode(['success' => false, 'message' => 'Lỗi cURL: ' . curl_error($curl)]);
        curl_close($curl);
        exit;
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
            echo json_encode(['success' => false, 'message' => 'Thiết bị ngoại tuyến, hoặc chương trình VBot chưa được khởi chạy']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Không nhận được phản hồi']);
        exit;
    }
    echo json_encode(['success' => $success, 'message' => $message, 'ip_address' => $ip, 'port_api' => $port]);
    exit;
}

//Thêm thiết bị chạy Vbot Server thủ công bằng IP
if (isset($_GET['add_ip_vbot_server'])) {
    $ip = isset($_GET['ip']) ? trim($_GET['ip']) : '';
    if (empty($ip)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu địa chỉ IP']);
        exit;
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
        echo json_encode(['success' => false, 'error' => 'Không thể kết nối đến IP']);
        exit;
    }
    $json = json_decode($response, true);
    if (!isset($json['success']) || $json['success'] !== true) {
        echo json_encode(['success' => false, 'error' => 'API trả về success = false']);
        exit;
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
		shell_exec('chmod 0777 ' . escapeshellarg($dir_path));
    }
    if (!file_exists($json_path)) {
        file_put_contents($json_path, "[]");
		shell_exec('chmod 0777 ' . escapeshellarg($json_path));
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
    file_put_contents($json_path, json_encode($devices, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo json_encode(['success' => true, 'device' => $device]);
    exit;
}

//Xóa thiết bị chạy Vbot Server thủ công bằng IP
if (isset($_GET['delete_ip_vbot_server'])) {
    $ip = isset($_GET['ip']) ? trim($_GET['ip']) : '';
    if (empty($ip)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu địa chỉ IP']);
        exit;
    }
    $json_path = $directory_path . '/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json';
    $dir_path = dirname($json_path);
    if (!is_dir($dir_path)) {
        mkdir($dir_path, 0777, true);
		shell_exec('chmod 0777 ' . escapeshellarg($dir_path));
    }
    if (!file_exists($json_path)) {
        file_put_contents($json_path, "[]");
		shell_exec('chmod 0777 ' . escapeshellarg($json_path));
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
        file_put_contents($json_path, json_encode($devices, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo json_encode(['success' => true, 'message' => 'Xóa thiết bị thành công', 'ip_address' => $ip]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy dữ liệu IP tương ứng để xóa']);
    }
    exit;
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
        echo json_encode($response);
        exit();
    }
    if (!function_exists('ssh2_connect')) {
        $response['message'] = 'Tiện ích mở rộng PHP SSH2 chưa được cài đặt: sudo apt-get install php-ssh2';
        echo json_encode($response);
        exit();
    }
    $connection = @ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH, Kiểm tra lại địa chỉ máy chủ hoặc port, hoặc SSH chưa được kích hoạt trên máy chủ';
        echo json_encode($response);
        exit();
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_pass)) {
        $response['message'] = 'Xác thực SSH thất bại, Kiểm tra lại Tên Đăng Nhập hoặc Mật Khẩu';
        echo json_encode($response);
        exit();
    }
    $response['success'] = true;
    $response['message'] = 'Kết nối SSH thành công!';
    echo json_encode($response);
    ssh2_disconnect($connection);
    gc_collect_cycles();
    exit();
}

#Lệnh Command SSH
if (isset($_GET['VBot_CMD'])) {
    $Command = $_GET['Command'] ?? '';
    if (empty($Command)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không có dữ liệu câu lệnh đầu vào',
            'data' => null
        ]);
        exit();
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
    echo json_encode($result);
    exit();
}

#Chạy Chương trình VBot
if (isset($_GET['start_vbot_service'])) {
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
    echo json_encode($result);
    exit;
}

#Dừng Chương trình VBot
if (isset($_GET['stop_vbot_service'])) {
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
    echo json_encode($result);
    exit;
}

#Khởi động lại Chương trình VBot
if (isset($_GET['restart_vbot_service'])) {
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
    echo json_encode($result);
    exit;
}

#Khởi động lại toàn bộ hệ thống
if (isset($_GET['reboot_os'])) {
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
    echo json_encode($result);
    exit;
}

#kiểm tra Kết Nối Hass
if (isset($_GET['check_hass'])) {
    $url = isset($_GET['url_hass']) ? $_GET['url_hass'] : '';
    $token = isset($_GET['token_hass']) ? $_GET['token_hass'] : '';
    if (!empty($url)) {
        $ch = curl_init($url . '/api/config');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            if (strpos($curlError, 'Failed to connect') !== false) {
                $message = 'Không thể kết nối, Kiểm tra lại URL: ' . $curlError;
            } else {
                $message = 'Xảy ra lỗi khi tiến hành kiểm tra: ' . $curlError;
            }
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode(['success' => true, 'message' => 'Kết nối thành công', 'response' => json_decode($response)]);
            } else if ($httpCode = 401 && $httpCode = 200) {
                echo json_encode(['success' => false, 'message' => 'Kết nối thất bại, Mã token không đúng', 'response' => json_decode($response)]);
            } else {
                echo json_encode(['success' => false, 'response' => json_decode($response), 'message' => 'HTTP Error: ' . $httpCode]);
            }
        }
        curl_close($ch);
    } else {
        echo json_encode(['success' => false, 'message' => 'URL không hợp lệ']);
    }
    exit();
}

#Lấy dữ liệu Hass
if (isset($_GET['get_hass_all'])) {
    $url = isset($_GET['url_hass']) ? $_GET['url_hass'] : '';
    $token = isset($_GET['token_hass']) ? $_GET['token_hass'] : '';
    if (!empty($url)) {
        $ch = curl_init($url . '/api/states');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            if (strpos($curlError, 'Failed to connect') !== false) {
                $message = 'Không thể kết nối, Kiểm tra lại URL: ' . $curlError;
            } else {
                $message = 'Xảy ra lỗi khi tiến hành kiểm tra: ' . $curlError;
            }
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode(['success' => true, 'message' => 'Kết nối thành công', 'response' => json_decode($response)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
                if (!file_exists($filePath_HASS)) {
                    file_put_contents($filePath_HASS, json_encode(['get_hass_all' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
					shell_exec('chmod 0777 ' . escapeshellarg($filePath_HASS));
                }
                $existingData['get_hass_all'] = json_decode($response);
                $jsonData = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                file_put_contents($filePath_HASS, $jsonData);
            } else if ($httpCode = 401 && $httpCode = 200) {
                echo json_encode(['success' => false, 'message' => 'Kết nối thất bại, Mã token không đúng', 'response' => json_decode($response)]);
            } else {
                echo json_encode(['success' => false, 'response' => json_decode($response), 'message' => 'HTTP Error: ' . $httpCode]);
            }
        }
        curl_close($ch);
    } else {
        echo json_encode(['success' => false, 'message' => 'URL không hợp lệ']);
    }
    exit();
}

#Xóa dữ liệu Hass đã lấy
if (isset($_GET['del_get_hass_all'])) {
    $response = [
        'success' => false,
        'message' => 'Đã có lỗi xảy ra.'
    ];
    $filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
    if (!file_exists($filePath_HASS)) {
        file_put_contents($filePath_HASS, json_encode(['get_hass_all' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		shell_exec('chmod 0777 ' . escapeshellarg($filePath_HASS));
    }
    $existingData = json_decode(file_get_contents($filePath_HASS), true);
    if ($existingData === null) {
        $existingData = [];
    }
    $existingData['get_hass_all'] = [];
    $jsonData = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonData !== false) {
        if (file_put_contents($filePath_HASS, $jsonData)) {
            $response['success'] = true;
            $response['message'] = 'Dữ Liệu Đồng Bộ trước đó đã được xóa thành công.';
        } else {
            $response['message'] = 'Lỗi: Không thể lưu dữ liệu rỗng vào file.';
        }
    } else {
        $response['message'] = 'Lỗi: Không thể chuyển đổi dữ liệu thành JSON.';
    }
    echo json_encode($response);
}

#Kiểm tra key Picovoice
if (isset($_GET['check_key_picovoice'])) {
    $key =  str_replace(' ', '+', @$_GET['key']);
    $lang = $VBot_Offline . 'resource/hotword/' . @$_GET['lang'];
    $response = [
        'success' => false,
        'message' => '',
    ];
    if (empty($_GET['lang']) || empty($_GET['key'])) {
        $response['message'] = 'Vui lòng cung cấp đầy đủ key, lang';
        echo json_encode($response);
        exit();
    }
    $modelFilePath = $VBot_Offline . 'resource/picovoice/library/' . $Config['smart_config']['smart_wakeup']['hotword']['library'][$_GET['lang']]['modelFilePath'];
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/Check_Key_Picovoice.py $key $lang $modelFilePath");
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

#Kiểm tra Kết Nối MQTT
if (isset($_GET['check_mqtt'])) {
    if (isset($_GET['host'], $_GET['port'], $_GET['user'], $_GET['pass'])) {
        require('./phpMQTT.php');
        $server = $_GET['host'];
        $port = $_GET['port'];
        $username = $_GET['user'];
        $password = $_GET['pass'];
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
    echo json_encode($response);
    exit();
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false || $http_code !== 200) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Không thể kết nối tới RSS feed.',
            'error' => curl_error($ch)
        ]);
    } else {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/xml');
        echo $response;
    }
    curl_close($ch);
    exit();
}

#Chatbox Check_Connection.php?vbot_chatbox&ip=192.168.14.113&port=5002&text=tên%20bạn%20là%20gì
if (isset($_GET['vbot_chatbox'])) {
    if (!isset($_GET['ip_port']) || !isset($_GET['text'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu một hoặc nhiều tham số: ip:port, text'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $ip_port = $_GET['ip_port'];
    $text = $_GET['text'];
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
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
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
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi cURL: ' . $curlError,
            'error_code' => $curlErrno
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpCode !== 200) {
        echo json_encode([
            'success' => false,
            'message' => 'Yêu cầu thất bại với mã HTTP: ' . $httpCode,
            'response' => $response
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $jsonResponse = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi phân tích JSON trả về: ' . json_last_error_msg(),
            'response' => $response
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if (!isset($jsonResponse['success']) || !isset($jsonResponse['message'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu JSON trả về không đúng định dạng',
            'response' => $jsonResponse
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    echo json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

//Lấy token zai_did tts_default
if (isset($_GET['get_token_tts_default_zai_did'])) {
    $ch = curl_init(base64_decode('aHR0cHM6Ly9haS56YWxvLmNsb3VkLw=='));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        echo json_encode([
            'success' => false,
            'message' => "Lỗi cURL, Vui lòng thử lại: $error"
        ]);
        exit();
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
            echo json_encode([
                'success' => true,
                'message' => 'Lấy Token zai_did thành công, hãy Lưu Cài Đặt Cấu Hình Config để áp dụng',
                'zai_did' => $zai_did_value,
                'expires_zai_did' => $expires_iso
            ], JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi xử lý thời gian: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy zai_did hoặc thời gian hết hạn. Vui lòng thử lại'
        ]);
    }
    exit();
}

//Lấy danh sách giọng đọc của google cloud
if (isset($_GET['get_ggcloud_voice_name'])) {
    $CMD = 'python3 ' .$Config['web_interface']['path'].'/includes/php_ajax/Get_Voice_Name_GCloud.py';
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể kết nối SSH'
        ]);
        exit;
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Xác thực SSH thất bại'
        ]);
        exit;
    }
    $stream = ssh2_exec($connection, $CMD);
    stream_set_blocking($stream, true);
    $output = stream_get_contents(
        ssh2_fetch_stream($stream, SSH2_STREAM_STDIO)
    );
    echo trim($output);
    exit;
}

//Lấy danh sách model trợ lý google gemini
if (isset($_GET['get_model_gemini'])) {
    $apiKey     = $_GET['apikey'] ?? '';
    $versionAPI = $_GET['version_api'] ?? 'v1beta'; // mặc định
    if (empty($apiKey)) {
        echo json_encode([
            "success" => false,
            "message" => "Thiếu tham số apikey"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!in_array($versionAPI, ['v1', 'v1beta'], true)) {
        echo json_encode([
            "success" => false,
            "message" => "version_api không hợp lệ (chỉ v1 hoặc v1beta)"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $url = "https://generativelanguage.googleapis.com/{$versionAPI}/models?key={$apiKey}";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_TIMEOUT => 15,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);
    if ($response === false) {
        echo json_encode([
            "success" => false,
            "message" => $error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($http_code !== 200) {
        echo $response;
        exit;
    }
    $json = json_decode($response, true);
    if (!isset($json['models']) || !is_array($json['models'])) {
        echo json_encode([
            "success" => false,
            "message" => "Dữ liệu Gemini không hợp lệ"
        ], JSON_UNESCAPED_UNICODE);
        exit;
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
	$outputFile = "/home/pi/VBot_Offline/html/includes/other_data/gemini_model_list.json";
	$existingData = [];
	if (file_exists($outputFile)) {
		$existingData = json_decode(file_get_contents($outputFile), true);
		if (!is_array($existingData)) {
			$existingData = [];
		}
	}
	$existingData['gemini_models'] = $modelList;
	if (file_put_contents($outputFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
		echo json_encode([
			"success" => false,
			"message" => "Không ghi được file JSON"
		], JSON_UNESCAPED_UNICODE);
		exit;
	}
    echo json_encode([
        "success" => true,
        "count" => count($modelList),
		"message" => "Lấy dữ liệu Model Gemini thành công"
        #"output_file" => $outputFile
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

?>