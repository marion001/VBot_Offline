<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
include '../../Configuration.php';
$allowedFileRoots = vbotApiAllowedRoots([$VBot_Offline, $directory_path]);

// Preserve UTF-8 file names regardless of the operating system locale.
function vbotUtf8Basename($path)
{
    $path = str_replace('\\', '/', (string) $path);
    $parts = explode('/', $path);
    return end($parts);
}

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
vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

$requestedFilePath = isset($_POST['filePath']) ? $_POST['filePath'] : '';

if (empty($requestedFilePath)) {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Đường dẫn file không được cung cấp.'
    ], 400);
}

$filePath = vbotApiResolveExistingPath($requestedFilePath, $allowedFileRoots, 'file');
if ($filePath === false) {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Đường dẫn file không hợp lệ hoặc nằm ngoài thư mục VBot.'
    ], 400);
}

if (file_exists($filePath)) {
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    $fileName = vbotUtf8Basename($filePath);
    if (unlink($filePath)) {
        $message = 'File: ' . vbotUtf8Basename($filePath) . ' đã được xóa thành công.';
        #Nếu là file hotword Picovoice
        if ($fileExtension === 'ppn') {
            $message = 'File .ppn: ' . vbotUtf8Basename($filePath) . ' đã được xóa thành công.';
            foreach (['vi', 'eng'] as $lang) {
                foreach ($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] as $key => $item) {
                    if ($item['file_name'] === $fileName) {
                        unset($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang][$key]);
                    }
                }
                $Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = array_values($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang]);
            }
            file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        } elseif ($fileExtension === 'pv') {
            $message = 'File .pv: ' . vbotUtf8Basename($filePath) . ' đã được xóa thành công.';
        }
        #Nếu là file Hotwor Snowboy
        elseif ($fileExtension === 'pmdl' || $fileExtension === 'umdl') {
            foreach ($Config['smart_config']['smart_wakeup']['hotword']['snowboy'] as $key => $item) {
                if ($item['file_name'] === $fileName) {
                    unset($Config['smart_config']['smart_wakeup']['hotword']['snowboy'][$key]);
                }
            }
            $Config['smart_config']['smart_wakeup']['hotword']['snowboy'] = array_values($Config['smart_config']['smart_wakeup']['hotword']['snowboy']);
            file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
        #Nếu là các file âm thanh trong câu phản hồi
        elseif (strpos($filePath, 'sound/wakeup_reply') !== false) {
            foreach ($Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] as $key => $item) {
                if (vbotUtf8Basename($item['file_name']) === $fileName) {
                    unset($Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'][$key]);
                }
            }
            $Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] = array_values($Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file']);
            file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
        vbotApiJsonResponse([
            'success' => true,
            'status' => 'success',
            'message' => $message
        ]);
    } else {
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Lỗi, không thể xóa file: ' . vbotUtf8Basename($filePath) . ' vui lòng kiểm tra quyền truy cập.'
        ], 500);
    }
} else {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'File: ' . vbotUtf8Basename($filePath) . ' không tồn tại.'
    ], 404);
}
?>
