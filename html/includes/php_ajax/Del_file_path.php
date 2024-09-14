<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php'; // Đảm bảo đường dẫn chính xác đến Configuration.php

// Đặt tiêu đề để chỉ định nội dung là JSON
header('Content-Type: application/json');

// Lấy đường dẫn file từ POST
$filePath = isset($_POST['filePath']) ? $_POST['filePath'] : '';

// Kiểm tra nếu đường dẫn file có giá trị và file tồn tại
if (empty($filePath)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Đường dẫn file không được cung cấp.'
    ]);
    exit();
}

if (file_exists($filePath)) {
    // Kiểm tra phần mở rộng của tệp
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    $fileName = basename($filePath);


    // Xóa file
    if (unlink($filePath)) {
        $message = 'File: '.basename($filePath).' đã được xóa thành công.';

		#kiểm tra file được xóa có trong config.json hay không, nếu có thì xóa trong config và cập nhật lại
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

            // Lưu cấu hình JSON đã cập nhật
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
