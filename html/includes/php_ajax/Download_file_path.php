<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
?>
<?php
// Lấy tham số file từ URL
$file = $_GET['file'];

// Kiểm tra nếu $file là URL
if (filter_var($file, FILTER_VALIDATE_URL)) {
    // Khởi tạo cURL
    $ch = curl_init($file);

    // Thiết lập cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true); // Bao gồm header trong output
    curl_setopt($ch, CURLOPT_NOBODY, false); // Bao gồm nội dung trong output

    // Thực hiện cURL
    $response = curl_exec($ch);

    // Phân tích header và body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    // Xác định loại MIME và kích thước nội dung từ header
    $contentType = 'application/octet-stream'; // Mặc định
    $contentLength = strlen($body);

    if (preg_match('/Content-Type:\s*(\S+)/i', $header, $matches)) {
        $contentType = $matches[1];
    }

    // Thiết lập header cho tải xuống
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $contentLength);

    // Đọc và xuất nội dung tệp
    echo $body;

    // Đóng cURL
    curl_close($ch);
    exit;
} else {
    // Xử lý đường dẫn tệp cục bộ nếu không phải URL
    if (file_exists($file)) {
        // Lấy thông tin tệp
        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
        
        // Kiểm tra xem đuôi file có nằm trong danh sách bị cấm không
        if (in_array($fileExtension, $Restricted_Extensions)) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền tải xuống file này.']);
            http_response_code(403); // Trả về mã lỗi 403 Forbidden
            exit;
        }

        // Thiết lập header cho quá trình tải xuống
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));

        // Đọc file và xuất ra
        readfile($file);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File không tồn tại.']);
        http_response_code(404); // Trả về mã lỗi 404 Not Found
        exit;
    }
}
?>
