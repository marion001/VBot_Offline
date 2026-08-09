<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

date_default_timezone_set('Asia/Ho_Chi_Minh');

//Khóa liên tiến trình dùng chung với Lib_System.py và ghi file bằng rename nguyên tử.
if (!function_exists('vbotAtomicWriteFile')) {
function vbotAtomicWriteFile($filePath, $content, $label = 'file')
{
    if (!is_string($filePath) || $filePath === '' || !is_string($content)) {
        error_log('[PHP FILE ERROR] Dữ liệu ghi không hợp lệ: '.$label);
        return false;
    }
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        error_log('[PHP FILE ERROR] Thư mục không tồn tại: '.$directory);
        return false;
    }
    $lockHandle = @fopen($filePath.'.lock', 'c+');
    if ($lockHandle === false || !@flock($lockHandle, LOCK_EX)) {
        if (is_resource($lockHandle)) fclose($lockHandle);
        error_log('[PHP FILE ERROR] Không thể khóa file: '.$filePath);
        return false;
    }
    $tempPath = @tempnam($directory, '.vbot-json-');
    $success = false;
    try {
        if ($tempPath === false) throw new RuntimeException('Không thể tạo file tạm');
        $handle = @fopen($tempPath, 'wb');
        if ($handle === false) throw new RuntimeException('Không thể mở file tạm');
        $length = strlen($content);
        $written = 0;
        while ($written < $length) {
            $count = fwrite($handle, substr($content, $written));
            if ($count === false || $count === 0) {
                fclose($handle);
                throw new RuntimeException('Không thể ghi đủ nội dung');
            }
            $written += $count;
        }
        fflush($handle);
        if (function_exists('fsync')) @fsync($handle);
        fclose($handle);
        if (!@rename($tempPath, $filePath)) throw new RuntimeException('Không thể thay thế file đích');
        $success = true;
    } catch (Throwable $error) {
        error_log('[PHP FILE ERROR] '.$label.': '.$error->getMessage());
    } finally {
        if (!$success && is_string($tempPath) && is_file($tempPath)) @unlink($tempPath);
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
    return $success;
}
}

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
$Cache_UI_Ver = '1.2.4';

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
$Restricted_Extensions = ['html', 'python', 'php', 'so', 'json'];

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

//CSRF độc lập với đăng nhập: WebUI không yêu cầu mật khẩu vẫn phải có session/token hợp lệ.
$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
$requestScript = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
$loginActive = !empty($Config['contact_info']['user_login']['active']);
$csrfProtectedPost = $requestMethod === 'POST' && $requestScript !== 'Login.php';
$needsAnonymousCsrfSession = !$loginActive && $requestMethod !== '' && $requestScript !== 'Login.php';

if ($csrfProtectedPost || $needsAnonymousCsrfSession) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    if (
        !isset($_SESSION['vbot_csrf_token'])
        || !is_string($_SESSION['vbot_csrf_token'])
        || strlen($_SESSION['vbot_csrf_token']) < 64
    ) {
        $_SESSION['vbot_csrf_token'] = bin2hex(random_bytes(32));
    }
}

if ($csrfProtectedPost) {
    $sessionToken = isset($_SESSION['vbot_csrf_token']) ? $_SESSION['vbot_csrf_token'] : '';
    $requestToken = isset($_SERVER['HTTP_X_CSRF_TOKEN'])
        ? $_SERVER['HTTP_X_CSRF_TOKEN']
        : (isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($requestToken) || !hash_equals($sessionToken, $requestToken)) {
        error_log('[PHP SECURITY] Đã chặn POST thiếu hoặc sai CSRF token: '.$requestScript);
        http_response_code(403);
        if (defined('VBOT_JSON_API') && VBOT_JSON_API === true) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'status' => 'error', 'message' => 'CSRF token không hợp lệ hoặc đã hết hạn.']);
        } else {
            echo 'Yêu cầu bị từ chối: CSRF token không hợp lệ hoặc đã hết hạn.';
        }
        exit;
    }
    //Khi login bật, các trang cũ sẽ tự mở lại session để kiểm tra đăng nhập.
    //Khi login tắt, giữ session mở để html_head.php xuất token cho giao diện.
    if ($loginActive) session_write_close();
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

// Ghi lỗi nâng cấp theo một định dạng chung. error_log đã được trỏ tới
// resource/log/Vbot_error.log ở đầu tệp này.
if (!function_exists('vbotUpgradeReportError')) {
    function vbotUpgradeReportError(&$messages, $component, $step, $detail)
    {
        $component = strtoupper((string) $component);
        $step = trim((string) $step);
        $detail = trim((string) $detail);
        $logMessage = '[UPGRADE ' . $component . ' ERROR] [' . $step . '] ' . $detail;
        error_log($logMessage);
        $messages[] = "<font color=red><b>- Lỗi tại bước " . htmlspecialchars($step, ENT_QUOTES, 'UTF-8') . ":</b> "
            . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . "</font><br/>";
        return false;
    }
}

if (!function_exists('vbotUpgradeValidatePackage')) {
    function vbotUpgradeValidatePackage($root, array $requiredPaths, &$messages, $component)
    {
        $root = rtrim($root, '/\\');
        if (!is_dir($root)) {
            return vbotUpgradeReportError($messages, $component, 'kiểm tra gói', 'Thư mục gói cập nhật không tồn tại: ' . $root);
        }
        foreach ($requiredPaths as $relativePath) {
            $path = $root . '/' . ltrim($relativePath, '/\\');
            if (!is_file($path) || !is_readable($path) || filesize($path) <= 0) {
                return vbotUpgradeReportError($messages, $component, 'kiểm tra gói', 'Thiếu hoặc không đọc được tệp bắt buộc: ' . $relativePath);
            }
        }
        return true;
    }
}

if (!function_exists('vbotUpgradeValidateAlternativeFiles')) {
    function vbotUpgradeValidateAlternativeFiles($root, array $requiredGroups, &$messages, $component)
    {
        $root = rtrim($root, '/\\');
        foreach ($requiredGroups as $label => $patterns) {
            $found = false;
            foreach ((array) $patterns as $pattern) {
                foreach ((array) glob($root . '/' . ltrim($pattern, '/\\'), GLOB_NOSORT) as $path) {
                    if (is_file($path) && is_readable($path) && filesize($path) > 0) {
                        $found = true;
                        break 2;
                    }
                }
            }
            if (!$found) {
                return vbotUpgradeReportError(
                    $messages,
                    $component,
                    'kiểm tra gói',
                    'Thiếu mô-đun bắt buộc ' . $label . ' (chấp nhận: ' . implode(', ', (array) $patterns) . ')'
                );
            }
        }
        return true;
    }
}

if (!function_exists('vbotUpgradeValidateJson')) {
    function vbotUpgradeValidateJson($path, &$messages, $component, $label)
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            return vbotUpgradeReportError($messages, $component, 'kiểm tra JSON', 'Không thể đọc ' . $label);
        }
        json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return vbotUpgradeReportError($messages, $component, 'kiểm tra JSON', $label . ' không hợp lệ: ' . json_last_error_msg());
        }
        return true;
    }
}

if (!function_exists('vbotUpgradeLintPhpTree')) {
    function vbotUpgradeFindPhpCli()
    {
        $candidates = [];
        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
            $candidates[] = PHP_BINARY;
        }
        if (defined('PHP_BINDIR') && is_string(PHP_BINDIR) && PHP_BINDIR !== '') {
            $candidates[] = rtrim(PHP_BINDIR, '/\\') . '/php';
        }
        $candidates[] = '/usr/bin/php';
        $candidates[] = '/usr/local/bin/php';

        if (DIRECTORY_SEPARATOR === '/') {
            $commandOutput = [];
            $commandCode = 1;
            @exec('command -v php 2>/dev/null', $commandOutput, $commandCode);
            if ($commandCode === 0 && !empty($commandOutput[0])) {
                $candidates[] = trim($commandOutput[0]);
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            $resolved = @realpath($candidate);
            if ($resolved === false || !is_file($resolved) || !is_executable($resolved)) continue;
            $binaryName = basename($resolved);
            if (preg_match('/^php(?:[0-9]+(?:\.[0-9]+)*)?(?:\.exe)?$/i', $binaryName)) {
                return $resolved;
            }
        }
        return null;
    }

    function vbotUpgradeLintPhpTree($root, &$messages, $component)
    {
        if (!is_dir($root)) {
            return vbotUpgradeReportError($messages, $component, 'kiểm tra PHP', 'Thư mục PHP không tồn tại: ' . $root);
        }
        $phpCli = vbotUpgradeFindPhpCli();
        if ($phpCli === null) {
            return vbotUpgradeReportError(
                $messages,
                $component,
                'kiểm tra môi trường PHP',
                'Không tìm thấy PHP CLI có quyền thực thi (thường là /usr/bin/php)'
            );
        }
        $checked = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if (!$item->isFile() || strtolower($item->getExtension()) !== 'php') continue;
            $output = [];
            $exitCode = 0;
            exec(escapeshellarg($phpCli) . ' -l ' . escapeshellarg($item->getPathname()) . ' 2>&1', $output, $exitCode);
            if ($exitCode !== 0) {
                return vbotUpgradeReportError(
                    $messages,
                    $component,
                    'kiểm tra cú pháp PHP',
                    $item->getFilename() . ': ' . implode(' ', $output)
                );
            }
            $checked++;
        }
        if ($checked === 0) {
            return vbotUpgradeReportError($messages, $component, 'kiểm tra PHP', 'Không tìm thấy tệp PHP trong gói giao diện');
        }
        $messages[] = "<font color=green>- Đã kiểm tra cú pháp <b>$checked</b> tệp PHP.</font><br/>";
        return true;
    }
}

if (!function_exists('vbotUpgradeTransactionalCopy')) {
    function vbotUpgradeTransactionalCopy($source, $destination, array $keepList, $rollbackRoot, &$messages, $component)
    {
        $source = rtrim($source, '/\\');
        $destination = rtrim($destination, '/\\');
        $rollbackRoot = rtrim($rollbackRoot, '/\\');
        if (!is_dir($source)) {
            return vbotUpgradeReportError($messages, $component, 'sao chép', 'Thư mục nguồn không tồn tại: ' . $source);
        }
        if ((!is_dir($destination) && !mkdir($destination, 0777, true)) ||
            (!is_dir($rollbackRoot) && !mkdir($rollbackRoot, 0777, true))) {
            return vbotUpgradeReportError($messages, $component, 'chuẩn bị rollback', 'Không thể tạo thư mục đích hoặc rollback');
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $item) {
            if (!$item->isFile() || in_array($item->getFilename(), $keepList, true)) continue;
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
            if ($relative === '' || strpos($relative, '../') !== false) {
                return vbotUpgradeReportError($messages, $component, 'kiểm tra đường dẫn', 'Đường dẫn không an toàn: ' . $relative);
            }
            $files[] = [$item->getPathname(), $relative];
        }
        if (empty($files)) {
            return vbotUpgradeReportError($messages, $component, 'kiểm tra gói', 'Không tìm thấy tệp nào để cập nhật');
        }

        $processed = [];
        $manifest = [];
        foreach ($files as [$srcPath, $relative]) {
            $destPath = $destination . '/' . $relative;
            $backupPath = $rollbackRoot . '/' . $relative;
            $existed = is_file($destPath);
            if ($existed) {
                if (!is_dir(dirname($backupPath)) && !mkdir(dirname($backupPath), 0777, true)) {
                    $failure = 'Không thể tạo thư mục rollback cho: ' . $relative;
                    break;
                }
                if (!copy($destPath, $backupPath)) {
                    $failure = 'Không thể sao lưu tệp hiện tại để rollback: ' . $relative;
                    break;
                }
            }
            if (!is_dir(dirname($destPath)) && !mkdir(dirname($destPath), 0777, true)) {
                $failure = 'Không thể tạo thư mục đích cho: ' . $relative;
                break;
            }
            // Đăng ký rollback trước khi copy vì copy() có thể tạo/ghi dở tệp đích.
            $processed[] = [$destPath, $backupPath, $existed];
            $manifest[] = ['relative' => $relative, 'existed' => $existed];
            if (!copy($srcPath, $destPath)) {
                $failure = 'Không thể sao chép tệp: ' . $relative;
                break;
            }
            clearstatcache(true, $destPath);
            $sourceHash = @hash_file('sha256', $srcPath);
            $destinationHash = @hash_file('sha256', $destPath);
            if ($sourceHash === false || !hash_equals($sourceHash, (string) $destinationHash)) {
                $failure = 'SHA-256 không khớp sau khi sao chép: ' . $relative;
                break;
            }
            @chmod($destPath, 0777);
        }

        if (isset($failure)) {
            foreach (array_reverse($processed) as [$destPath, $backupPath, $existed]) {
                if ($existed && is_file($backupPath)) {
                    @copy($backupPath, $destPath);
                } elseif (!$existed && is_file($destPath)) {
                    @unlink($destPath);
                }
            }
            return vbotUpgradeReportError($messages, $component, 'sao chép/rollback', $failure . '. Đã rollback các tệp đã thay đổi.');
        }
        if (file_put_contents(
            $rollbackRoot . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        ) === false) {
            foreach (array_reverse($processed) as [$destPath, $backupPath, $existed]) {
                if ($existed && is_file($backupPath)) @copy($backupPath, $destPath);
                elseif (!$existed && is_file($destPath)) @unlink($destPath);
            }
            return vbotUpgradeReportError($messages, $component, 'tạo manifest', 'Không thể lưu manifest rollback; đã phục hồi dữ liệu cũ');
        }
        $messages[] = "<font color=green>- Đã sao chép và xác minh SHA-256 <b>" . count($processed) . "</b> tệp.</font><br/>";
        return true;
    }
}

if (!function_exists('vbotUpgradeRollbackTransaction')) {
    function vbotUpgradeRollbackTransaction($rollbackRoot, $destination, &$messages, $component)
    {
        $rollbackRoot = rtrim($rollbackRoot, '/\\');
        $destination = rtrim($destination, '/\\');
        $manifestPath = $rollbackRoot . '/manifest.json';
        $manifest = json_decode((string) @file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            return vbotUpgradeReportError($messages, $component, 'rollback', 'Không đọc được manifest rollback');
        }
        $rollbackOk = true;
        foreach (array_reverse($manifest) as $entry) {
            $relative = $entry['relative'] ?? '';
            if ($relative === '' || strpos($relative, '../') !== false) {
                $rollbackOk = false;
                continue;
            }
            $destPath = $destination . '/' . $relative;
            $backupPath = $rollbackRoot . '/' . $relative;
            if (!empty($entry['existed'])) {
                if (!is_file($backupPath) || !@copy($backupPath, $destPath)) $rollbackOk = false;
            } elseif (is_file($destPath) && !@unlink($destPath)) {
                $rollbackOk = false;
            }
        }
        if (!$rollbackOk) {
            return vbotUpgradeReportError($messages, $component, 'rollback', 'Rollback không hoàn tất; cần khôi phục từ bản sao lưu hệ thống');
        }
        error_log('[UPGRADE ' . strtoupper($component) . ' WARNING] Đã rollback toàn bộ tệp cập nhật');
        $messages[] = "<font color=orange><b>- Đã rollback toàn bộ tệp về phiên bản trước.</b></font><br/>";
        return true;
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
