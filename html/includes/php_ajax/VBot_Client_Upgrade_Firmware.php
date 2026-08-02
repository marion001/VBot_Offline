<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

ob_start();
require_once __DIR__.'/Api_Helpers.php';
require_once dirname(__DIR__).'/Client_Data_Helpers.php';
vbotApiInitialize(['POST']);
include '../../Configuration.php';
$startupOutput = ob_get_clean();
if ($startupOutput !== '') {
    error_log('VBot_Client_Upgrade_Firmware startup output suppressed: '.substr(strip_tags($startupOutput), 0, 500));
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

function vbotUpgradeIsLanIp($ip)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    $value = ip2long($ip);
    return ($value >= ip2long('10.0.0.0') && $value <= ip2long('10.255.255.255'))
        || ($value >= ip2long('172.16.0.0') && $value <= ip2long('172.31.255.255'))
        || ($value >= ip2long('192.168.0.0') && $value <= ip2long('192.168.255.255'));
}

function vbotUpgradeIsAllowedUrl($url)
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $parts = parse_url($url);
    $host = strtolower(isset($parts['host']) ? $parts['host'] : '');
    $path = isset($parts['path']) ? $parts['path'] : '';
    $allowedHost = $host === 'github.com'
        || $host === 'raw.githubusercontent.com'
        || substr($host, -22) === '.githubusercontent.com';
    return isset($parts['scheme'])
        && strtolower($parts['scheme']) === 'https'
        && $allowedHost
        && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'bin';
}

function vbotUpgradeRequireValidIp($ip)
{
    if (!vbotUpgradeIsLanIp($ip)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Chỉ cho phép địa chỉ IPv4 thuộc mạng LAN riêng'], 400);
    }
}

$hash_bypass_OTA = "441018525208457705bf09a8ee3c1093";

//[1] Bypass nâng cấp firmware (Gửi yêu cầu đến thiết bị)
if (isset($_POST['bypass_upgrade_firmware']) && !empty($_POST['ip'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $ip = trim($_POST['ip']);
    vbotUpgradeRequireValidIp($ip);
	#link bypass firmware
    $targetUrl = 'http://' . $ip . '/ota/start?mode=fr&hash=' . $hash_bypass_OTA;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log('Firmware bypass FR failed for '.$ip.': '.$error.' (HTTP '.$httpCode.')');
        vbotApiJsonResponse(["success" => false, "message" => "Không thể kết nối tới thiết bị"], 502);
    } else {
        vbotApiJsonResponse(["success" => true, "message" => "bypass_fr_ok"]);
    }
}

//[1] Bypass nâng cấp firmware (Gửi yêu cầu đến thiết bị)
elseif (isset($_POST['bypass_upgrade_littlefs']) && !empty($_POST['ip'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $ip = trim($_POST['ip']);
    vbotUpgradeRequireValidIp($ip);
	#Link Bypass littlefs
    $targetUrl = 'http://' . $ip . '/ota/start?mode=fs&hash=' . $hash_bypass_OTA;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log('Firmware bypass FS failed for '.$ip.': '.$error.' (HTTP '.$httpCode.')');
        vbotApiJsonResponse(["success" => false, "message" => "Không thể kết nối tới thiết bị"], 502);
    } else {
        vbotApiJsonResponse(["success" => true, "message" => "bypass_fs_ok"]);
    }
}

//Nâng Cấp LittleFS / SPIFFS Tự Động
elseif (isset($_POST['start_upgrade_littlefs'], $_POST['ip'], $_POST['url_littlefs']) && !empty($_POST['ip']) && !empty($_POST['url_littlefs'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $ip = trim($_POST['ip']);
    $url_littlefs = trim($_POST['url_littlefs']);
    vbotUpgradeRequireValidIp($ip);
    if (!vbotUpgradeIsAllowedUrl($url_littlefs)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'URL LittleFS phải là HTTPS .bin từ GitHub'], 400);
    }
    $temp_file = tempnam(sys_get_temp_dir(), 'littlefs_');
    if ($temp_file === false) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể tạo file tạm LittleFS'], 500);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_littlefs);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $file_content = curl_exec($ch);
    $httpCodeDownload = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($file_content === false || $httpCodeDownload < 200 || $httpCodeDownload >= 300 || strlen($file_content) > 16777216) {
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }
        error_log('LittleFS download failed: '.($error ?: 'HTTP '.$httpCodeDownload));
        vbotApiJsonResponse([
            "success" => false,
            "message" => "Không thể tải xuống LittleFS"
        ], 502);
    }
    file_put_contents($temp_file, $file_content, LOCK_EX);
    //Upload LittleFS lên Client OTA
    $upload_url = 'http://' . $ip . '/ota/upload';
    $post_data = [
        'file' => new CURLFile(
            $temp_file,
            'application/octet-stream',
            'littlefs.bin'
        )
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upload_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: */*',
        'Origin: http://' . $ip,
        'Referer: http://' . $ip . '/update',
        'User-Agent: Mozilla/5.0'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    @unlink($temp_file);
    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log('LittleFS upload failed for '.$ip_address.': '.$error.' (HTTP '.$httpCode.')');
        vbotApiJsonResponse([
            "success" => false,
            "message" => "Không thể tải LittleFS lên thiết bị"
        ], 502);
    } else {
        vbotApiJsonResponse([
            "success" => true,
            "message" => "Nâng cấp LittleFS thành công"
        ]);
    }
}

//Nâng Cấp Tự ĐỘng firmware
elseif (isset($_POST['start_upgrade_firmware'], $_POST['ip'], $_POST['url_firmware']) && !empty($_POST['ip']) && !empty($_POST['url_firmware'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $ip = trim($_POST['ip']);
    $url_firmware = trim($_POST['url_firmware']);
    vbotUpgradeRequireValidIp($ip);
    if (!vbotUpgradeIsAllowedUrl($url_firmware)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'URL firmware phải là HTTPS .bin từ GitHub'], 400);
    }
    $temp_file = tempnam(sys_get_temp_dir(), 'firmware_');
    if ($temp_file === false) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể tạo file tạm firmware'], 500);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_firmware);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $file_content = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($file_content === false || strlen($file_content) > 16777216) {
        @unlink($temp_file);
        error_log('Firmware download failed: '.$error);
        vbotApiJsonResponse(["success" => false, "message" => "Không thể tải xuống Firmware"], 502);
    }
    file_put_contents($temp_file, $file_content, LOCK_EX);
    $upload_url = 'http://' . $ip . '/ota/upload';
    $firmware_filename = "VBot_Client_FW_" . basename($url_firmware);
    $post_data = ['file' => new CURLFile($temp_file, 'application/octet-stream', $firmware_filename)];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upload_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    @unlink($temp_file);
    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log('Firmware upload failed for '.$ip_address.': '.$error.' (HTTP '.$httpCode.')');
        vbotApiJsonResponse(["success" => false, "message" => "Không thể tải Firmware lên thiết bị"], 502);
    } else {
        vbotApiJsonResponse(["success" => true, "message" => "Nâng cấp Firmware thành công"]);
    }
}

//Nâng Cấp Thủ Công firmware
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['firmware']) && isset($_POST['ip_address'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $ip_address = trim($_POST['ip_address']);
    vbotUpgradeRequireValidIp($ip_address);
    if ($_FILES['firmware']['error'] !== UPLOAD_ERR_OK || $_FILES['firmware']['size'] <= 0 || $_FILES['firmware']['size'] > 16777216) {
        vbotApiJsonResponse(["success" => false, "message" => "File firmware không hợp lệ hoặc vượt quá 16 MB"], 400);
    }
    $originalName = basename($_FILES['firmware']['name']);
    if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'bin') {
        vbotApiJsonResponse(["success" => false, "message" => "Chỉ chấp nhận file .bin"], 400);
    }
    $tmpFile = tempnam(sys_get_temp_dir(), 'vbot_fw_');
    if ($tmpFile === false) {
        vbotApiJsonResponse(["success" => false, "message" => "Không thể tạo file firmware tạm"], 500);
    }
    if (!move_uploaded_file($_FILES['firmware']['tmp_name'], $tmpFile)) {
        @unlink($tmpFile);
        vbotApiJsonResponse(["success" => false, "message" => "Lỗi khi lưu file vào bộ nhớ tạm."], 500);
    }
    $ota_start_url = 'http://' . $ip_address . '/ota/start?mode=fr&hash=' . $hash_bypass_OTA;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ota_start_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($httpCode !== 200) {
        @unlink($tmpFile);
        error_log('OTA bypass failed for '.$ip_address.': '.$error.' (HTTP '.$httpCode.')');
        vbotApiJsonResponse(["success" => false, "message" => "Không thể gửi yêu cầu nâng cấp OTA"], 502);
    }
    $upload_url = 'http://' . $ip_address . '/ota/upload';
    $firmware_filename = "VBot_Client_FW_" . $originalName;
    $post_data = ['file' => new CURLFile($tmpFile, 'application/octet-stream', $firmware_filename)];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upload_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    @unlink($tmpFile);
    if ($httpCode !== 200) {
        error_log('Uploaded firmware transfer failed for '.$ip_address.': '.$error.' (HTTP '.$httpCode.')');
        vbotApiJsonResponse(["success" => false, "message" => "Không thể tải firmware lên thiết bị"], 502);
    } else {
        vbotApiJsonResponse(["success" => true, "message" => "Đã Nâng cấp Firmware"]);
    }
}

//Lưu dữ liệu Client Data
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_data_vbot_client') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $json_file = vbotClientDataFilePath($Config, $directory_path);
    $directory = dirname($json_file);
    if (!is_dir($directory)) {
        if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể tạo thư mục dữ liệu Client'], 500);
        }
        @chmod($directory, 0777);
    }
    if (!file_exists($json_file)) {
        if (@file_put_contents($json_file, '{}', LOCK_EX) === false) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể tạo file JSON dữ liệu Client'], 500);
        }
        @chmod($json_file, 0777);
    }
    if (!is_writable($json_file)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'File dữ liệu Client không có quyền ghi'], 500);
    }
    $data = json_decode(isset($_POST['json_data']) ? $_POST['json_data'] : '', true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Dữ liệu JSON Client không hợp lệ'], 400);
    }
    $encodedData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encodedData === false) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể mã hóa dữ liệu JSON Client'], 400);
    }
    $result = @file_put_contents($json_file, $encodedData, LOCK_EX);
    @chmod($json_file, 0777);
    if ($result !== false) {
        vbotApiJsonResponse(['success' => true, 'message' => 'Dữ liệu Client đã được lưu thành công']);
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể ghi dữ liệu Client vào file'], 500);
    }
}

else {
    vbotApiJsonResponse(["success" => false, "message" => "Yêu cầu không hợp lệ"], 400);
}
?>
