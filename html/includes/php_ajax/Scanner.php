<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php';
header('Content-Type: application/json');

if (isset($_GET['scan_mic'])) {
    // Câu lệnh gọi Python script
    $CMD = escapeshellcmd("python3 $directory_path/includes/php_ajax/Scan_Mic.py");
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

if (isset($_GET['scan_alsamixer'])) {

    // Câu lệnh gọi amixer
    $CMD = 'amixer';

    // Khởi tạo phản hồi
    $response = [
        'success' => false,
        'message' => '',
        'devices' => []
    ];

    // Kết nối SSH
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Xác thực SSH
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Thực thi câu lệnh và lấy kết quả
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
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}
?>
