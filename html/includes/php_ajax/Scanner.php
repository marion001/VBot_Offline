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

?>
