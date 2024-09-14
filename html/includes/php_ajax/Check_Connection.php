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

?>
