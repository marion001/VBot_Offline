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

#$SSH_CONNECT_ERROR = "<center><h1><font color='red'>Không thể kết nối tới máy chủ SSH, Hãy Kiểm Tra Lại</font><br/><a href='Command.php'>Quay Lại</a></h1></center>";
#$SSH2_AUTH_ERROR = "<center><h1><font color='red'>Xác thực SSH không thành công, Hãy kiểm tra lại thông tin đăng nhập SSH</font> <br/><a href='Command.php'>Quay Lại</a></h1></center>";

//Sử Dụng MEthod POST
$filePath = isset($_POST['filePath']) ? $_POST['filePath'] : '';
if (empty($filePath)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Đường dẫn file không được cung cấp.'
    ]);
    exit();
}
/*
if (strpos($filePath, ".cloudflared") !== false) {
	$fileName = basename($filePath);
    $CMD = "sudo rm " . escapeshellarg($filePath);
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) die($SSH_CONNECT_ERROR);
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) die($SSH2_AUTH_ERROR);
    $stream = ssh2_exec($connection, $CMD);
    if ($stream) {
        stream_set_blocking($stream, true);
        $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $output = stream_get_contents($stream_out);
        fclose($stream_out);
        fclose($stream);
        echo json_encode([
            'status'  => 'success',
            'message' => "File: $fileName đã được xóa qua SSH."
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => "Không thể thực thi lệnh SSH xóa file: $fileName."
        ]);
    }
}
*/
if (file_exists($filePath)) {
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    $fileName = basename($filePath);
    if (unlink($filePath)) {
        $message = 'File: ' . basename($filePath) . ' đã được xóa thành công.';
        #Nếu là file hotword Picovoice
        if ($fileExtension === 'ppn') {
            $message = 'File .ppn: ' . basename($filePath) . ' đã được xóa thành công.';
            foreach (['vi', 'eng'] as $lang) {
                foreach ($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] as $key => $item) {
                    if ($item['file_name'] === $fileName) {
                        unset($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang][$key]);
                    }
                }
                $Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = array_values($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang]);
            }
            file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } elseif ($fileExtension === 'pv') {
            $message = 'File .pv: ' . basename($filePath) . ' đã được xóa thành công.';
        }
        #Nếu là file Hotwor Snowboy
        elseif ($fileExtension === 'pmdl' || $fileExtension === 'umdl') {
            foreach ($Config['smart_config']['smart_wakeup']['hotword']['snowboy'] as $key => $item) {
                if ($item['file_name'] === $fileName) {
                    unset($Config['smart_config']['smart_wakeup']['hotword']['snowboy'][$key]);
                }
            }
            $Config['smart_config']['smart_wakeup']['hotword']['snowboy'] = array_values($Config['smart_config']['smart_wakeup']['hotword']['snowboy']);
            file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        #Nếu là các file âm thanh trong câu phản hồi
        elseif (strpos($filePath, 'sound/wakeup_reply') !== false) {
            foreach ($Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] as $key => $item) {
                if (basename($item['file_name']) === $fileName) {
                    unset($Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'][$key]);
                }
            }
            $Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] = array_values($Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file']);
            file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        echo json_encode([
            'status' => 'success',
            'message' => $message
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Lỗi, không thể xóa file: ' . basename($filePath) . ' vui lòng kiểm tra quyền truy cập.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'File: ' . basename($filePath) . ' không tồn tại.'
    ]);
}
?>