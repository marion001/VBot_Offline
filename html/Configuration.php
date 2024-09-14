<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
ini_set('memory_limit', '512M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Lấy đường dẫn đầy đủ tới tệp PHP hiện tại
//$current_file_path = __FILE__;
// Lấy đường dẫn thư mục chứa tệp PHP
$directory_path = dirname(__FILE__);
$HostName = gethostname();
$GET_current_USER = get_current_user();
// Lấy địa chỉ IP của máy chủ
$serverIp = $_SERVER['SERVER_ADDR'];
// Lấy địa chỉ IP của người dùng khi truy cập
$userIp = $_SERVER['REMOTE_ADDR'];
//Đường dẫn ui html
$HTML_VBot_Offline = getcwd();

//đường dẫn path VBot python
$VBot_Offline = "/home/pi/VBot_Offline/";
// Đường dẫn đến tệp JSON
$Config_filePath = $VBot_Offline.'Config.json';

// Danh sách các file, thư mục cần loại trừ không cần scan và chmod 777
$excluded_items_chmod = ['.', '..', '__pycache__', 'Music_Local', 'TTS_Audio', 'robotx.txt'];

// Đọc và giải mã dữ liệu JSON
$Config = null; // Khởi tạo biến để lưu dữ liệu

//biến lưu trữ thông báo php
$messages = [];

// Danh sách các đuôi file không cho phép tải xuống
$Restricted_Extensions = ['html', 'python', 'php', 'so'];

// Danh sách các định dạng hình ảnh hợp lệ
$allowed_image_types = ["jpg", "png", "jpeg", "gif"];


//Tối đa số lượng kênh đài báo radio được cho phép
$Max_Radios = 10;

//Các định dạng file âm thanh cho phép tìm kiếm, tải lên và lựa chọn khi khởi động
$Allowed_Extensions_Audio = ['mp3', 'wav', 'ogg', 'aac'];

// Lấy giao thức (http hoặc https)
$Protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// Lấy tên miền (ví dụ: 192.168.14.113)
$Domain = $_SERVER['HTTP_HOST'];
// Lấy đường dẫn tới file hiện tại (ví dụ: /html/includes/php_ajax/Media_Player_Search.php)
$Path = $_SERVER['REQUEST_URI'];
// Kết hợp thành URL đầy đủ
$Current_URL = $Protocol . $Domain . $Path;


#Đọc nội dung file Config
if (file_exists($Config_filePath)) {
    #$jsonString = file_get_contents($Config_filePath);
    $Config = json_decode(file_get_contents($Config_filePath), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'Có lỗi xảy ra khi giải mã JSON: ' . json_last_error_msg();
        $Config = null; // Đặt dữ liệu thành null nếu có lỗi
    }
} else {
    echo 'Tệp JSON không tồn tại tại đường dẫn: ' . $Config_filePath;
    $Config = null; // Đặt dữ liệu thành null nếu tệp không tồn tại
}

$stt_token_google_cloud = $VBot_Offline.$Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['authentication_json_file'];
$tts_token_google_cloud = $VBot_Offline.$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['authentication_json_file'];
$Backlist_File_Name = $VBot_Offline.$Config['smart_config']['backlist_file_name'];



//Thông tin kết nối SSH
#sudo apt-get install php-ssh2
$ssh_host = $Config['ssh_server']['ssh_host'];
$ssh_port = $Config['ssh_server']['ssh_port'];
$ssh_user = $Config['ssh_server']['ssh_username'];
$ssh_password = $Config['ssh_server']['ssh_password'];


//Cổng port của đường API
$Port_API = $Config['api']['port'];

// Tìm tất cả các tệp có tên bắt đầu bằng 'avata_user'
$files = glob('assets/img/avata_user.*');
// Kiểm tra xem có tệp nào không
if (count($files) > 0) {
    foreach ($files as $file_path) {
        // Lấy tên tệp bao gồm phần mở rộng
        $file_name = basename($file_path);
        // Hiển thị tên tệp
        $Avata_File = "assets/img/".htmlspecialchars($file_name);
    }
} else {
    $Avata_File = "assets/img/no-face.png";
}





?>