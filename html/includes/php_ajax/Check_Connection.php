<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php';
header('Content-Type: application/json');

#kiểm tra kết nối tới SSH Server
if (isset($_GET['check_ssh'])) {

    // Lấy các tham số từ URL
    $ssh_host = $_GET['host']; // Địa chỉ IP hoặc tên miền của máy chủ
    $ssh_port = $_GET['port']; // Cổng SSH (mặc định là 22)
    $ssh_user = $_GET['user']; // Tên đăng nhập
    $ssh_pass = $_GET['pass']; // Mật khẩu

    $response = [
        'success' => false,
        'message' => '',
    ];

    // Kiểm tra xem tất cả các tham số đều được cung cấp
    if (empty($ssh_host) || empty($ssh_user) || empty($ssh_pass) || empty($ssh_port)) {
        $response['message'] = 'Vui lòng cung cấp đầy đủ ssh_host, ssh_port, ssh_user và ssh_pass.';
        echo json_encode($response);
        exit();
    }

    // Kiểm tra xem PHP đã có phần mở rộng SSH2 chưa
    if (!function_exists('ssh2_connect')) {
        $response['message'] = 'Tiện ích mở rộng PHP SSH2 chưa được cài đặt: sudo apt-get install php-ssh2';
        echo json_encode($response);
        exit();
    }

    // Kết nối tới máy chủ SSH
    $connection = @ssh2_connect($ssh_host, $ssh_port);

    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH, Kiểm tra lại địa chỉ máy chủ hoặc port, hoặc SSH chưa được kích hoạt trên máy chủ';
        echo json_encode($response);
        exit();
    }

    // Xác thực bằng tên đăng nhập và mật khẩu
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_pass)) {
        $response['message'] = 'Xác thực SSH thất bại, Kiểm tra lại Tên Đăng Nhập hoặc Mật Khẩu';
        echo json_encode($response);
        exit();
    }

    // Nếu thành công
    $response['success'] = true;
    $response['message'] = 'Kết nối SSH thành công!';
    echo json_encode($response);

    // Đóng kết nối SSH
    ssh2_disconnect($connection);
	gc_collect_cycles(); // Giải phóng bộ nhớ

    exit();
}

if (isset($_GET['start_vbot_service'])) {
    $CMD = "systemctl --user start VBot_Offline.service";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    // Khởi tạo biến để lưu kết quả
    $result = [
        'success' => false,
        'message' => ''
    ];
    if ($connection) {
        if (ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            $stream = ssh2_exec($connection, $CMD);
            stream_set_blocking($stream, true);
            $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
            
            // Nếu lệnh thành công
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

if (isset($_GET['stop_vbot_service'])) {
    $CMD = "systemctl --user stop VBot_Offline.service";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    // Khởi tạo biến để lưu kết quả
    $result = [
        'success' => false,
        'message' => ''
    ];
    if ($connection) {
        if (ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            $stream = ssh2_exec($connection, $CMD);
            stream_set_blocking($stream, true);
            $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
            
            // Nếu lệnh thành công
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

if (isset($_GET['restart_vbot_service'])) {
    $CMD = "systemctl --user restart VBot_Offline.service";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    // Khởi tạo biến để lưu kết quả
    $result = [
        'success' => false,
        'message' => ''
    ];
    if ($connection) {
        if (ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            $stream = ssh2_exec($connection, $CMD);
            stream_set_blocking($stream, true);
            $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
            
            // Nếu lệnh thành công
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

if (isset($_GET['reboot_os'])) {
    $CMD = "sudo reboot";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    // Khởi tạo biến để lưu kết quả
    $result = [
        'success' => false,
        'message' => ''
    ];
    if ($connection) {
        if (ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            $stream = ssh2_exec($connection, $CMD);
            stream_set_blocking($stream, true);
            $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
            
            // Nếu lệnh thành công
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

if (isset($_GET['check_hass']))
{
    // Lấy URL và token từ GET dữ liệu
    $url = isset($_GET['url_hass']) ? $_GET['url_hass'] : '';
    $token = isset($_GET['token_hass']) ? $_GET['token_hass'] : '';

    // Kiểm tra nếu URL không rỗng
    if (!empty($url))
    {
        // Khởi tạo cURL
        $ch = curl_init($url . '/api/config');
        // Cấu hình cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        // Thực hiện cURL
        $response = curl_exec($ch);
        // Kiểm tra lỗi cURL
        if (curl_errno($ch))
        {
            $curlError = curl_error($ch);
            // Kiểm tra lỗi kết nối cụ thể
            if (strpos($curlError, 'Failed to connect') !== false)
            {
                $message = 'Không thể kết nối, Kiểm tra lại URL: ' . $curlError;
            }
            else
            {
                $message = 'Xảy ra lỗi khi tiến hành kiểm tra: ' . $curlError;
            }
            echo json_encode(['success' => false, 'message' => $message]);
        }
        else
        {
            // Kiểm tra mã trạng thái HTTP
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 200 && $httpCode < 300)
            {
                echo json_encode(['success' => true, 'message' => 'Kết nối thành công', 'response' => json_decode($response) ]);
            }
            else if ($httpCode = 401 && $httpCode = 200)
            {
                echo json_encode(['success' => false, 'message' => 'Kết nối thất bại, Mã token không đúng', 'response' => json_decode($response) ]);
            }
            else
            {
                echo json_encode(['success' => false, 'response' => json_decode($response) , 'message' => 'HTTP Error: ' . $httpCode]);
            }
        }
        // Đóng cURL
        curl_close($ch);
    }
    else
    {
        echo json_encode(['success' => false, 'message' => 'URL không hợp lệ']);
    }
    exit();
}


if (isset($_GET['get_hass_all']))
{
    // Lấy URL và token từ GET dữ liệu
    $url = isset($_GET['url_hass']) ? $_GET['url_hass'] : '';
    $token = isset($_GET['token_hass']) ? $_GET['token_hass'] : '';
    // Kiểm tra nếu URL không rỗng
    if (!empty($url))
    {
        // Khởi tạo cURL
        $ch = curl_init($url . '/api/states');
        // Cấu hình cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        // Thực hiện cURL
        $response = curl_exec($ch);
        // Kiểm tra lỗi cURL
        if (curl_errno($ch))
        {
            $curlError = curl_error($ch);
            // Kiểm tra lỗi kết nối cụ thể
            if (strpos($curlError, 'Failed to connect') !== false)
            {
                $message = 'Không thể kết nối, Kiểm tra lại URL: ' . $curlError;
            }
            else
            {
                $message = 'Xảy ra lỗi khi tiến hành kiểm tra: ' . $curlError;
            }
            echo json_encode(['success' => false, 'message' => $message]);
        }
        else
        {
            // Kiểm tra mã trạng thái HTTP
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 200 && $httpCode < 300)
            {
                echo json_encode(['success' => true, 'message' => 'Kết nối thành công', 'response' => json_decode($response) ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				$filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
	// Kiểm tra nếu file không tồn tại
if (!file_exists($filePath_HASS)) {
    // Tạo file rỗng nếu không tồn tại
    file_put_contents($filePath_HASS, json_encode(['get_hass_all' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    // Chmod 0777 cho file
    chmod($filePath_HASS, 0777);
}

				// Cập nhật dữ liệu vào 'get_hass_all'
				$existingData['get_hass_all'] = json_decode($response); // Gán dữ liệu từ $response
				// Chuyển đổi dữ liệu thành JSON
				$jsonData = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				// Lưu dữ liệu vào file Home_Assistant.json
				file_put_contents($filePath_HASS, $jsonData);
			}
            else if ($httpCode = 401 && $httpCode = 200)
            {
                echo json_encode(['success' => false, 'message' => 'Kết nối thất bại, Mã token không đúng', 'response' => json_decode($response) ]);
            }
            else
            {
                echo json_encode(['success' => false, 'response' => json_decode($response) , 'message' => 'HTTP Error: ' . $httpCode]);
            }
        }
        // Đóng cURL
        curl_close($ch);
    }
    else
    {
        echo json_encode(['success' => false, 'message' => 'URL không hợp lệ']);
    }
    exit();
}


if (isset($_GET['del_get_hass_all'])) {

$response = [
    'success' => false,
    'message' => 'Đã có lỗi xảy ra.'
];
    $filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
    
    // Kiểm tra nếu file không tồn tại
    if (!file_exists($filePath_HASS)) {
        // Tạo file rỗng nếu không tồn tại
        file_put_contents($filePath_HASS, json_encode(['get_hass_all' => []], JSON_PRETTY_PRINT));
        chmod($filePath_HASS, 0777);
    }
    
    // Đọc dữ liệu hiện tại từ file
    $existingData = json_decode(file_get_contents($filePath_HASS), true);
    if ($existingData === null) {
        // Nếu dữ liệu hiện tại không đọc được, khởi tạo mảng rỗng
        $existingData = [];
    }

    // Xóa dữ liệu get_hass_all
    $existingData['get_hass_all'] = [];

    // Chuyển đổi dữ liệu thành JSON
    $jsonData = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Kiểm tra nếu json_encode thành công
    if ($jsonData !== false) {
        // Lưu dữ liệu vào file Home_Assistant.json
        if (file_put_contents($filePath_HASS, $jsonData)) {
            $response['success'] = true;
            $response['message'] = 'Dữ Liệu Đồng Bộ trước đó đã được xóa thành công.';
        } else {
            $response['message'] = 'Lỗi: Không thể lưu dữ liệu rỗng vào file.';
        }
    } else {
        $response['message'] = 'Lỗi: Không thể chuyển đổi dữ liệu thành JSON.';
    }
	// Trả về phản hồi dưới dạng JSON
echo json_encode($response);
}



if (isset($_GET['check_key_picovoice'])) {
    // Lấy các tham số từ URL
    $key =  str_replace(' ', '+', @$_GET['key']);
    $lang = $VBot_Offline.'resource/hotword/'.@$_GET['lang'];


    $response = [
        'success' => false,
        'message' => '',
    ];
	
    // Kiểm tra xem tất cả các tham số đều được cung cấp
    if (empty($_GET['lang']) || empty($_GET['key'])) {
        $response['message'] = 'Vui lòng cung cấp đầy đủ key, lang';
        echo json_encode($response);
        exit();
    }
	$modelFilePath = $VBot_Offline.'resource/picovoice/library/'.$Config['smart_config']['smart_wakeup']['hotword']['library'][$_GET['lang']]['modelFilePath'];
	
    // Câu lệnh gọi Python script
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/Check_Key_Picovoice.py $key $lang $modelFilePath");
    //$CMD = escapeshellcmd("ls");

    // Kết nối SSH
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

    // Thực thi câu lệnh và lấy kết quả
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh trên máy chủ SSH.';
        echo json_encode($response);
        exit();
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);

    // Hiển thị kết quả
    echo $output;
    
	exit();
}

if (isset($_GET['check_mqtt'])) {
    // Kiểm tra xem các tham số có được cung cấp trong URL hay không
    if (isset($_GET['host'], $_GET['port'], $_GET['user'], $_GET['pass'])) {
        require('./phpMQTT.php');
        $server = $_GET['host'];
        $port = $_GET['port'];
        $username = $_GET['user'];
        $password = $_GET['pass'];

        // Tạo client ID ngẫu nhiên
        $client_id = 'VBot_TEST_CONNECT_MQTT_client_' . uniqid();

        // Khởi tạo kết nối MQTT
        $mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

        // Kiểm tra kết nối MQTT
        if ($mqtt->connect(true, NULL, $username, $password)) {
            // Trả về JSON khi kết nối thành công
            $response = [
                'success' => true,
                'message' => 'Kết nối tới máy chủ MQTT thành công: '.$server.':'.$port
            ];
            $mqtt->close(); // Ngắt kết nối sau khi hoàn tất
        } else {
            // Trả về JSON khi kết nối không thành công
            $response = [
                'success' => false,
                'message' => 'Không thể kết nối tới máy chủ MQTT: '.$server.':'.$port.' hãy kiểm tra lại Cổng Port, Tài Khoản,, Mật Khẩu'
            ];
        }
    } else {
        // Trả về JSON khi thiếu tham số kết nối
        $response = [
            'success' => false,
            'message' => 'Thiếu thông tin kết nối MQTT, cần nhập đủ thông tin: Máy Chủ MQTT, Cổng PORT, Tài Khoản, Mật Khẩu, Hoặc máy chủ MQTT có lỗi, không hoạt động'
        ];
    }

    // Trả về kết quả dưới dạng JSON
    echo json_encode($response);
    exit();
}
?>
