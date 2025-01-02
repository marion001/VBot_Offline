<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php';
header('Content-Type: application/json');

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
        $message = 'File: '.basename($filePath).' đã được xóa thành công.';
        if ($fileExtension === 'ppn') {
            $message = 'File .ppn: '.basename($filePath).' đã được xóa thành công.';
            $removed = false;
            foreach (['vi', 'eng'] as $lang) {
                foreach ($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] as $key => $item) {
                    if ($item['file_name'] === $fileName) {
                        unset($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang][$key]);
                        $removed = true;
                    }
                }
                $Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = array_values($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang]);
            }
            file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } elseif ($fileExtension === 'pv') {
			
            $message = 'File .pv: '.basename($filePath).' đã được xóa thành công.';
        }
        echo json_encode([
            'status' => 'success',
            'message' => $message
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Lỗi, không thể xóa file: '.basename($filePath).' vui lòng kiểm tra quyền truy cập.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'File: '.basename($filePath).' không tồn tại.'
    ]);
}
?>
