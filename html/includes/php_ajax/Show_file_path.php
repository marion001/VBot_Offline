<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php';

header('Content-Type: application/json');

function encodeFileToBase64($filePath)
{
    if (file_exists($filePath))
    {
        // Đọc nội dung tệp
        $fileContent = file_get_contents($filePath);

        // Mã hóa nội dung tệp thành base64
        $base64Content = base64_encode($fileContent);

        // Lấy phần mở rộng của tệp
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Tạo mảng chứa dữ liệu tệp và nội dung đã mã hóa
        $response = ['success' => true, 'data' => ['fileName' => basename($filePath) , 'base64Content' => $base64Content, 'fileExtension' => $fileExtension]];

        // Trả về dữ liệu JSON
        return json_encode($response);
    }
    else
    {
        // Trả về thông báo lỗi nếu tệp không tồn tại
        return json_encode(['success' => false, 'error' => 'File not found']);
    }
}

if (isset($_GET['scan_Music_Local']))
{
    // Đường dẫn tới thư mục cần tìm kiếm
    $directory = $VBot_Offline . 'Media/Music_Local';
    // Tạo biểu thức tìm kiếm từ mảng đuôi file
    $searchPattern = $directory . '/*{' . implode(',', $Allowed_Extensions_Audio) . '}';

    // Tìm kiếm các file với các đuôi mở rộng cho phép
    $allFiles = glob($searchPattern, GLOB_BRACE);
    // Trả về kết quả dưới dạng JSON
    header('Content-Type: application/json');
    echo json_encode($allFiles);
	exit();
}

if (isset($_GET['scan_Audio_Startup']))
{
    // Đường dẫn tới thư mục cần tìm kiếm
    $directory = $VBot_Offline . 'resource/sound/welcome';
    // Tạo biểu thức tìm kiếm từ mảng đuôi file
    $searchPattern = $directory . '/*{' . implode(',', $Allowed_Extensions_Audio) . '}';

    // Tìm kiếm các file với các đuôi mở rộng cho phép
    $allFiles = glob($searchPattern, GLOB_BRACE);
    // Trả về kết quả dưới dạng JSON
    header('Content-Type: application/json');
    echo json_encode($allFiles);
	exit();
}

if (isset($_GET['audio_b64']))
{
    $filePath = isset($_GET['path']) ? $_GET['path'] : '';
    // Gọi hàm và hiển thị kết quả
    echo encodeFileToBase64($filePath);
    exit();
}

if (isset($_GET['TTS_Audio'])) {
    $file = $_GET['TTS_Audio'];

    // Đường dẫn tới thư mục chứa các tệp âm thanh
    //$baseDir = $VBot_Offline.'TTS_Audio/';

    // Xây dựng đường dẫn đầy đủ tới tệp âm thanh
    $filePath = $VBot_Offline . $file;

    // Kiểm tra xem tệp có tồn tại không
    if (file_exists($filePath)) {
        // Xác định loại tệp âm thanh
        $fileInfo = pathinfo($filePath);
        $fileExtension = strtolower($fileInfo['extension']);
        $mimeType = 'audio/' . $fileExtension;

        // Gửi tiêu đề và nội dung của tệp âm thanh
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
    } else {
        http_response_code(404);
        echo 'Tệp không tồn tại.';
    }
	exit();
} 
if (isset($_GET['data_backlist'])) {
	
// Khởi tạo mảng lưu trữ thông báo và trạng thái
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Kiểm tra xem tệp có tồn tại không
if (file_exists($Backlist_File_Name)) {
    // Đọc nội dung của tệp vào biến
    $fileContents = file_get_contents($Backlist_File_Name);
    // Kiểm tra nếu tệp đọc thành công
    if ($fileContents !== false) {
        // Đặt dữ liệu vào mảng response và cập nhật success thành true
        $response['success'] = true;
        $response['message'] = 'Tải dữ liệu thành công.';
		// Giải mã nội dung JSON
        $response['data'] = json_decode($fileContents, true); 
    } else {
        // Thông báo lỗi nếu không thể đọc tệp
        $response['message'] = 'Lỗi: Không thể đọc nội dung của file ' . $Backlist_File_Name . '.';
    }
} else {
    // Thông báo lỗi nếu tệp không tồn tại
    $response['message'] = 'Lỗi: File ' . $Backlist_File_Name . ' không tồn tại.';
}
// Trả về dữ liệu JSON
echo json_encode($response);
exit();
}


//dùng cho delete_data_backlist Hàm để xóa giá trị từ đường dẫn bằng cách thay thế nó với giá trị được chỉ định
function updateValueByPath(&$data, $path, $newValue) {
    $keys = explode('->', $path); // Phân tách đường dẫn
    $lastKey = array_pop($keys);   // Lấy khóa cuối cùng
    $array = &$data;

    foreach ($keys as $key) {
        if (!isset($array[$key])) {
            return false; // Không tìm thấy khóa
        }
        $array = &$array[$key];
    }

    // Thay thế giá trị nếu tồn tại
    $array[$lastKey] = $newValue; // Đặt giá trị mới
    return true;
}

// Kiểm tra và xử lý xóa giá trị nếu tham số 'delete_data_backlist' và 'path' được truyền
if (isset($_GET['delete_data_backlist']) && isset($_GET['path'])) {
	
#your_script.php?delete_data_backlist=1&path=backlist->tts_zalo->backlist_limit&value_type=null
	
// Khởi tạo mảng lưu trữ thông báo và trạng thái
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];
	
    $path_to_update = $_GET['path']; // Lấy đường dẫn cần cập nhật
    $value_type = isset($_GET['value_type']) ? $_GET['value_type'] : null; // Lấy loại giá trị

    // Chuyển đổi giá trị loại
    if ($value_type === 'null') {
        $newValue = null;
    } elseif ($value_type === '{}') {
        $newValue = []; // Mảng trống
    } elseif ($value_type === '[]') {
        $newValue = []; // Mảng trống
    } else {
        $newValue = $_GET['value_type'] ?? null; // Nếu không có value_type, lấy giá trị từ tham số 'value'
    }

    // Đọc nội dung của tệp vào biến
    if (file_exists($Backlist_File_Name)) {
        $fileContents = file_get_contents($Backlist_File_Name);
        // Kiểm tra nếu tệp đọc thành công
        if ($fileContents !== false) {
            // Giải mã nội dung JSON
            $data = json_decode($fileContents, true);

            // Cập nhật nội dung nếu giải mã thành công
            if ($data !== null) {
                // Cập nhật giá trị từ đường dẫn
                if (updateValueByPath($data, $path_to_update, $newValue)) {
                    // Lưu lại tệp JSON với nội dung đã được cập nhật
                    if (file_put_contents($Backlist_File_Name, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
                        $response['success'] = true;
                        $response['message'] = 'Giá trị đã được cập nhật thành công.';
                        $response['data'] = $data;
                    } else {
                        $response['message'] = 'Lỗi: Không thể lưu nội dung vào file ' . $Backlist_File_Name . '.';
                    }
                } else {
                    $response['message'] = 'Lỗi: Đường dẫn không hợp lệ hoặc giá trị không tồn tại.';
                }
            } else {
                $response['message'] = 'Lỗi: Dữ liệu JSON không hợp lệ trong file ' . $Backlist_File_Name . '.';
            }
        } else {
            $response['message'] = 'Lỗi: Không thể đọc nội dung của file ' . $Backlist_File_Name . '.';
        }
    } else {
        $response['message'] = 'Lỗi: File ' . $Backlist_File_Name . ' không tồn tại.';
    }
    echo json_encode($response);
    exit();
}

// hiển thị toàn bộ dữ liệu trong file backlist.json
if (isset($_GET['data_backlist'])) {
    // Đọc nội dung của tệp vào biến
    if (file_exists($Backlist_File_Name)) {
        $fileContents = file_get_contents($Backlist_File_Name);

        // Kiểm tra nếu tệp đọc thành công
        if ($fileContents !== false) {
            // Đặt dữ liệu vào mảng response và cập nhật success thành true
            $response['success'] = true;
            $response['message'] = 'Tải dữ liệu thành công.';
            // Giải mã nội dung JSON
            $response['data'] = json_decode($fileContents, true);
        } else {
            $response['message'] = 'Lỗi: Không thể đọc nội dung của file ' . $Backlist_File_Name . '.';
        }
    } else {
        $response['message'] = 'Lỗi: File ' . $Backlist_File_Name . ' không tồn tại.';
    }
    // Trả về dữ liệu JSON
    echo json_encode($response);
    exit();
}
?>
