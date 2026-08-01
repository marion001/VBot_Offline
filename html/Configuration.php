<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Luôn ghi mọi lỗi PHP vào log chung, kể cả lỗi xảy ra khi đang nạp Config.json.
$phpErrorLog = __DIR__ . '/../resource/log/Vbot_error.log';
ini_set('log_errors', 1);
ini_set('error_log', $phpErrorLog);
error_reporting(E_ALL);

#tăng giới hạn bộ nhớ cho PHP
ini_set('memory_limit', '1G');
ini_set('upload_max_filesize', '300M');
ini_set('post_max_size', '300M');

//Thay đổi để trình duyệt tải lại dữ liệu cache js, css đã lưu trước đó
$Cache_UI_Ver = '1.2.3';

//Lấy đường dẫn đầy đủ tới tệp PHP hiện tại
//$current_file_path = __FILE__;

//Lấy đường dẫn thư mục chứa tệp PHP
$directory_path = dirname(__FILE__);

//Lấy HostName
$HostName = gethostname();

//Lấy User Hiện Tại: pi
$GET_current_USER = get_current_user();

//Lấy địa chỉ IP của máy chủ
$serverIp = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';

//Lấy địa chỉ IP của người dùng khi truy cập
$userIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

//Đường dẫn ui html /home/pi/VBot_Offline/html
$HTML_VBot_Offline = __DIR__;

//đường dẫn path VBot python
$VBot_Offline = "/home/pi/VBot_Offline/";

$Backup_dir = $HTML_VBot_Offline . '/Backup_Upgrade/Backup_Config/';

//Đường dẫn đến tệp JSON
$Config_filePath = $VBot_Offline . 'Config.json';

//địa chỉ URL Repo Github, địa chỉ này sẽ dùng cho cập nhật, không được chỉnh sửa
$Github_Repo_Vbot = "https://github.com/marion001/VBot_Offline";

//Danh sách các file, thư mục cần loại trừ không cần scan và chmod 777
$excluded_items_chmod = ['.', '..', '__pycache__', 'Music_Local', 'TTS_Audio', 'robotx.txt'];

//Đọc và giải mã dữ liệu JSON
$Config = null;

//biến lưu trữ thông báo php
$messages = [];
$Configuration_Load_Status = [
    'recovered' => false,
    'backup_file' => null,
    'error' => null
];

//Danh sách các đuôi file không cho phép tải xuống
$Restricted_Extensions = ['html', 'python', 'php', 'so'];

//Danh sách các định dạng hình ảnh hợp lệ
$allowed_image_types = ["jpg", "png", "jpeg", "gif"];

//Tối đa số lượng kênh đài báo radio được cho phép
$Max_Radios = 30;

//Tối đa số lượng trang báo, tin tức
$Max_NewsPaper = 50;

//Các định dạng file âm thanh cho phép tìm kiếm, tải lên và lựa chọn khi khởi động
$Allowed_Extensions_Audio = ['mp3', 'wav', 'ogg', 'aac'];

//Lấy giao thức (http hoặc https)
$serverPort = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
$Protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $serverPort === 443) ? "https://" : "http://";

//Lấy tên miền (ví dụ: 192.168.14.113)
$Domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $serverIp;

//Lấy đường dẫn tới file hiện tại (ví dụ: /html/includes/php_ajax/Media_Player_Search.php)
$Path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

//Kết hợp thành URL đầy đủ
$Current_URL = $Protocol . $Domain . $Path;

//ĐƯờng dẫn thư mục file backup Config
$Backup_dir = $HTML_VBot_Offline . '/Backup_Upgrade/Backup_Config/';

// Kiểm tra file không tồn tại hoặc rỗng
$needCopy = false;
if (!file_exists($Config_filePath)) {
    $needCopy = true;
} else {
    $fileContent = file_get_contents($Config_filePath);
    if (empty(trim($fileContent))) {
        $needCopy = true;
    }
}
if ($needCopy) {
    $backupFiles = glob($Backup_dir . 'Config_*.json');
    if (!empty($backupFiles)) {
        usort($backupFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $latestBackup = $backupFiles[0];
        //echo "Backup được chọn: $latestBackup\n";
        //echo "Sao chép tới: $Config_filePath\n";
        if (file_exists($Config_filePath)) {
            @unlink($Config_filePath);
        }
        $dirPath = dirname($Config_filePath);
        @chmod($dirPath, 0777);
        if (@copy($latestBackup, $Config_filePath)) {
            $Configuration_Load_Status['recovered'] = true;
            $Configuration_Load_Status['backup_file'] = basename($latestBackup);
            @chmod($Config_filePath, 0777);
            $fileContent = file_get_contents($Config_filePath);
        } else {
            //echo "Không thể sao chép file backup vào: $Config_filePath\n";
            $Configuration_Load_Status['error'] = 'Không thể khôi phục Config.json từ bản sao lưu mới nhất.';
            $Config = null;
        }
    } else {
        //echo "Không tìm thấy file backup nào trong: $Backup_dir\n";
        $Configuration_Load_Status['error'] = 'Không tìm thấy bản sao lưu để khôi phục Config.json.';
        $Config = null;
    }
}

// Giải mã JSON nếu file tồn tại và không lỗi
if (!empty($fileContent)) {
    $Config = json_decode($fileContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $Configuration_Load_Status['error'] = 'Lỗi giải mã Config.json: ' . json_last_error_msg();
        $Config = null;
    }
} else {
    $Config = null;
}

if (defined('VBOT_JSON_API') && VBOT_JSON_API === true) {
    // API luôn ghi lỗi vào log nhưng không đưa Warning/Notice/HTML vào phản hồi JSON.
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('html_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', $phpErrorLog);
    error_reporting(E_ALL);
} elseif (isset($Config['web_interface']['errors_display']) && $Config['web_interface']['errors_display'] === true) {
    //Bật hiển thị và ghi toàn bộ lỗi PHP vào log chung của VBot.
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', $phpErrorLog);
    error_reporting(E_ALL);
} else {
    // Chỉ tắt hiển thị trên giao diện; mọi lỗi vẫn luôn được ghi vào Vbot_error.log.
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', $phpErrorLog);
    error_reporting(E_ALL);
}

$stt_token_google_cloud = $VBot_Offline . $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['authentication_json_file'];
$tts_token_google_cloud = $VBot_Offline . $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['authentication_json_file'];
$Backlist_File_Name = $VBot_Offline . $Config['smart_config']['backlist_file_name'];

#ĐƯờng dẫn lưu file backup Vbot
$Backup_Dir_Save_VBot = $Config['backup_upgrade']['vbot_program']['backup']['backup_path'];

#Đường dẫn lưu file backup Web UI
$Backup_Dir_Save_Web = $Config['backup_upgrade']['web_interface']['backup']['backup_path'];

$Download_Path = $Config['backup_upgrade']['download_path'];

$Extract_Path = $Config['backup_upgrade']['extract_path'];

//Thông tin kết nối SSH
#sudo apt-get install php-ssh2
#$ssh_host = $Config['ssh_server']['ssh_host'];
$ssh_host = $serverIp;
$ssh_port = $Config['ssh_server']['ssh_port'];
$ssh_user = $Config['ssh_server']['ssh_username'];
$ssh_password = $Config['ssh_server']['ssh_password'];

if (!function_exists('vbotSetFullPermissions')) {
    function vbotSetFullPermissions($path, $label = 'tệp/thư mục')
    {
        global $ssh_host, $ssh_port, $ssh_user, $ssh_password;
        clearstatcache(true, $path);
        $currentPermissions = @fileperms($path);
        if ($currentPermissions !== false && (($currentPermissions & 0777) === 0777)) {
            return true;
        }
        if (@chmod($path, 0777)) {
            return true;
        }
        if (
            function_exists('ssh2_connect') &&
            function_exists('ssh2_sftp') &&
            function_exists('ssh2_sftp_chmod')
        ) {
            $connection = @ssh2_connect($ssh_host, intval($ssh_port));
            if ($connection && @ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
                $sftp = @ssh2_sftp($connection);
                if ($sftp && @ssh2_sftp_chmod($sftp, $path, 0777)) {
                    return true;
                }
            }
        }
        error_log('Không thể đặt quyền 0777 cho ' . $label . ': ' . $path);
        return false;
    }
}

//Kiểm tra xem google cloud backup có được bật hay không:
$google_cloud_drive_active = $Config['backup_upgrade']['google_cloud_drive']['active'];

//Cổng port của đường API
$Port_API = $Config['api']['port'];
//$Port_Server_Streaming_Audio_UDP = null;
$Port_Server_Streaming_Audio_Socket = $Config['api']['streaming_server']['protocol']['socket']['port'];

$API_AUTH_KEY = $Config['api']['auth']['api_key'];

//Tìm tất cả các tệp có tên bắt đầu bằng 'avata_user'
$files = glob('assets/img/avata_user.*');
//Kiểm tra xem có tệp nào không
if (count($files) > 0) {
    foreach ($files as $file_path) {
        $file_name = basename($file_path);
        $Avata_File = "assets/img/" . htmlspecialchars($file_name);
    }
} else {
    $Avata_File = "assets/img/no-face.png";
}

if ($Config['web_interface']['external']['active'] === true) {
    $URL_API_VBOT = "/vbot_api_external/";
} else {
    $URL_API_VBOT = $Protocol . $serverIp . ':' . $Port_API . '/';
}
?>
