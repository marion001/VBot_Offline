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

//Sử Dụng MEthod POST
$filePath = isset($_POST['filePath']) ? $_POST['filePath'] : '';
if (empty($filePath)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Đường dẫn file không được cung cấp.'
    ]);
    exit();
}

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