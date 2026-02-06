<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

#tăng giới hạn bộ nhớ cho PHP
ini_set('memory_limit', '1G');
ini_set('upload_max_filesize', '300M');
ini_set('post_max_size', '300M');

//Thay đổi để trình duyệt tải lại dữ liệu cache js, css đã lưu trước đó
$Cache_UI_Ver = '1.1.8';

//Lấy đường dẫn đầy đủ tới tệp PHP hiện tại
//$current_file_path = __FILE__;

//Lấy đường dẫn thư mục chứa tệp PHP
$directory_path = dirname(__FILE__);

//Lấy HostName
$HostName = gethostname();

//Lấy User Hiện Tại: pi
$GET_current_USER = get_current_user();

//Lấy địa chỉ IP của máy chủ
$serverIp = $_SERVER['SERVER_ADDR'];

//Lấy địa chỉ IP của người dùng khi truy cập
$userIp = $_SERVER['REMOTE_ADDR'];

//Đường dẫn ui html /home/pi/VBot_Offline/html
$HTML_VBot_Offline = getcwd();

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
$Protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

//Lấy tên miền (ví dụ: 192.168.14.113)
$Domain = $_SERVER['HTTP_HOST'];

//Lấy đường dẫn tới file hiện tại (ví dụ: /html/includes/php_ajax/Media_Player_Search.php)
$Path = $_SERVER['REQUEST_URI'];

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
            exec("rm -f " . escapeshellarg($Config_filePath));
        }
        $dirPath = dirname($Config_filePath);
        exec("chmod 0777 " . escapeshellarg($dirPath));
        $cmd = "cp " . escapeshellarg($latestBackup) . " " . escapeshellarg($Config_filePath);
        exec($cmd, $output, $return_var);
        if ($return_var === 0) {
            //echo "Đã sao chép Config từ backup.\n";
            echo '<script>
				alert("- Lỗi Dữ Liệu Tệp Cấu Hình Config.json\n\n- Đã Khôi Phục Dữ Liệu Từ Tệp Sao Lưu Mới Nhất: ' . basename($latestBackup) . '");
				</script>';
            exec("chmod 0777 " . escapeshellarg($Config_filePath));
            $fileContent = file_get_contents($Config_filePath);
        } else {
            //echo "Không thể sao chép file backup vào: $Config_filePath\n";
            $Config = null;
        }
    } else {
        //echo "Không tìm thấy file backup nào trong: $Backup_dir\n";
        $Config = null;
    }
}

// Giải mã JSON nếu file tồn tại và không lỗi
if (!empty($fileContent)) {
    $Config = json_decode($fileContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'Có lỗi xảy ra khi giải mã Config.json: ' . json_last_error_msg();
        $Config = null;
    }
} else {
    $Config = null;
}

if (isset($Config['web_interface']['errors_display']) && $Config['web_interface']['errors_display'] === true) {
    //Bật Logs PHP
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    //Tắt Logs PHP
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
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

//Kiểm tra xem google cloud backup có được bật hay không:
$google_cloud_drive_active = $Config['backup_upgrade']['google_cloud_drive']['active'];

//Cổng port của đường API
$Port_API = $Config['api']['port'];
$Port_Server_Streaming_Audio_UDP = $Config['api']['streaming_server']['protocol']['udp_sock']['port'];

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