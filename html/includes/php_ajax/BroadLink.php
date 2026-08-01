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

function vbotBroadlinkIsLanIp($ip)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    $value = ip2long($ip);
    return ($value >= ip2long('10.0.0.0') && $value <= ip2long('10.255.255.255'))
        || ($value >= ip2long('172.16.0.0') && $value <= ip2long('172.31.255.255'))
        || ($value >= ip2long('192.168.0.0') && $value <= ip2long('192.168.255.255'));
}

function vbotBroadlinkIsMac($mac)
{
    return preg_match('/^(?:[0-9A-F]{2}[:-]?){5}[0-9A-F]{2}$/i', $mac) === 1;
}

function vbotBroadlinkValidLabel($value, $maxLength = 100)
{
    return is_string($value)
        && trim($value) !== ''
        && mb_strlen($value) <= $maxLength
        && !preg_match('/[\x00-\x1F\x7F]/', $value);
}

function vbotBroadlinkSaveJson($filePath, array $data)
{
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false || file_put_contents($filePath, $encoded, LOCK_EX) === false) {
        error_log('Unable to save BroadLink JSON: '.$filePath.' | '.json_last_error_msg());
        return false;
    }
    @chmod($filePath, 0777);
    return true;
}

$broadlink_json = $VBot_Offline.$Config['broadlink']['json_file'];

if (!file_exists($broadlink_json)) {
    $broadlinkDirectory = dirname($broadlink_json);
    if (!is_dir($broadlinkDirectory) && !@mkdir($broadlinkDirectory, 0777, true)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể tạo thư mục dữ liệu BroadLink'], 500);
    }
    @chmod($broadlinkDirectory, 0777);
    if (file_put_contents($broadlink_json, "{}", LOCK_EX) === false) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể tạo file dữ liệu BroadLink'], 500);
    }
    @chmod($broadlink_json, 0777);
}

#Backup cấu hình dữ liệu Broadlink
function backupBroadlinkJson($Config, $broadlink_json){
	$backup_dir  = $Config['web_interface']['path'].'/'.$Config['broadlink']['backup_path'];
	if (file_exists($broadlink_json)) {
		if (!is_dir($backup_dir)) {
			mkdir($backup_dir, 0777, true);
			@chmod($backup_dir, 0777);
		}
		$files = glob($backup_dir . '/broadlink_*.json');
		if (count($files) >= 7) {
			usort($files, function ($a, $b) {
				return filemtime($a) - filemtime($b);
			});
			unlink($files[0]);
		}
		$backup_file = $backup_dir.'/broadlink_'.date('dmY_His').'.json';
		if (copy($broadlink_json, $backup_file)) {
            @chmod($backup_file, 0777);
        }
	}
}

//Xóa device broadlink
if (isset($_POST['delete_device_broadlink_remote']) && !empty($_POST['mac'])) {
    $result = [
        'success' => false,
        'message' => '',
        'data' => []
    ];
    $mac = strtoupper(trim($_POST['mac']));
    if (!vbotBroadlinkIsMac($mac)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Địa chỉ MAC không hợp lệ'], 400);
    }
    if (!file_exists($broadlink_json)) {
        $result['message'] = 'Không tìm thấy file JSON';
        vbotApiJsonResponse($result, 404);
    }
    $json = file_get_contents($broadlink_json);
    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['devices_remote']) || !is_array($data['devices_remote'])) {
        $result['message'] = 'Cấu trúc JSON không hợp lệ';
        vbotApiJsonResponse($result, 500);
    }
    $devices = $data['devices_remote'];
    $found = false;
    foreach ($devices as $k => $dev) {
        if (isset($dev['mac']) && strtoupper($dev['mac']) === $mac) {
            unset($devices[$k]);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $result['message'] = 'Không tìm thấy device với MAC này';
        vbotApiJsonResponse($result, 404);
    }
	backupBroadlinkJson($Config, $broadlink_json);
    $data['devices_remote'] = array_values($devices);
    if (!vbotBroadlinkSaveJson($broadlink_json, $data)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu dữ liệu sau khi xóa thiết bị'], 500);
    }
    $result['success'] = true;
    $result['message'] = 'Đã xóa thiết bị Broadlink có địa chỉ MAC: ' . $mac;
    vbotApiJsonResponse($result);
}

#Quét thiết bị Broadlink Remote Trong Lan
else if (isset($_POST['scan_broadlink_remote_device'])) {
    $CMD = 'python3 ' . escapeshellarg($VBot_Offline.'resource/broadlink/Broadlink.py') . ' scan';
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH'], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công.';
        vbotApiJsonResponse(['success' => false, 'message' => 'Xác thực SSH không thành công.'], 401);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh trên máy chủ SSH.';
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh trên máy chủ SSH.'], 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
	backupBroadlinkJson($Config, $broadlink_json);
    $scanResult = json_decode(trim($output), true);
    if (!is_array($scanResult)) {
        error_log('BroadLink scanner returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse(['success' => false, 'message' => 'Trình quét BroadLink trả về dữ liệu không hợp lệ'], 502);
    }
    vbotApiJsonResponse($scanResult);
}

#Đổi Tên friendly_name
else if (isset($_POST['rename_device_broadlink_remote']) && !empty($_POST['mac']) && isset($_POST['friendly'])) {
	$result = [
		'success' => false,
		'message' => '',
		'data' => []
	];
    $mac = strtoupper(trim($_POST['mac']));
    $newFriendly = trim($_POST['friendly']);
    if (!vbotBroadlinkIsMac($mac) || !vbotBroadlinkValidLabel($newFriendly)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'MAC hoặc tên thiết bị không hợp lệ'], 400);
    }
    if (!file_exists($broadlink_json)) {
        $result['message'] = 'File JSON không tồn tại';
        vbotApiJsonResponse($result, 404);
    }
    $json = json_decode(file_get_contents($broadlink_json), true);
    if (!is_array($json) || !isset($json['devices_remote']) || !is_array($json['devices_remote'])) {
        $result['message'] = 'Dữ liệu JSON không hợp lệ';
        vbotApiJsonResponse($result, 500);
    }
    $found = false;
    foreach ($json['devices_remote'] as &$device) {
        if (strtoupper($device['mac']) === $mac) {
            $device['friendly_name'] = $newFriendly;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $result['message'] = 'Không tìm thấy thiết bị theo MAC';
        vbotApiJsonResponse($result, 404);
    }
	backupBroadlinkJson($Config, $broadlink_json);
    if (!vbotBroadlinkSaveJson($broadlink_json, $json)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu tên thiết bị BroadLink'], 500);
    }
    $result['success'] = true;
    $result['message'] = 'Đã đổi tên thiết bị thành công';
    vbotApiJsonResponse($result);
}

//Học Lệnh
else if (isset($_POST['learn_command_broadlink'])) {
	$response = [
		"success" => false,
		"message" => ""
	];
    $ip = $_POST['ip'] ?? '';
    $mac = $_POST['mac'] ?? '';
    $wave_type = $_POST['wave_type'] ?? 'ir';
    $devtype = $_POST['devtype'] ?? '';
    if (!$ip || !$mac || !$devtype) {
        $response['message'] = 'Thiếu tham số thiết bị';
        vbotApiJsonResponse($response, 400);
    }
    if (!vbotBroadlinkIsLanIp($ip) || !vbotBroadlinkIsMac($mac) || !in_array($wave_type, ['ir', 'rf'], true) || !preg_match('/^(?:0x)?[0-9a-f]{1,8}$/i', $devtype)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Thông tin thiết bị BroadLink không hợp lệ'], 400);
    }
    $ip = escapeshellarg($ip);
    $mac = escapeshellarg($mac);
    $devtype = escapeshellarg($devtype);
	$wave_type = escapeshellarg($wave_type);
	$CMD = "python3 "
		 . escapeshellarg($VBot_Offline . "resource/broadlink/Broadlink.py") . " learn"
		 . " --ip $ip --mac $mac --devtype $devtype --wavetype $wave_type";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        vbotApiJsonResponse($response, 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công';
        vbotApiJsonResponse($response, 401);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh Python';
        vbotApiJsonResponse($response, 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    $learnResult = json_decode(trim($output), true);
    if (!is_array($learnResult)) {
        error_log('BroadLink learner returned invalid JSON: '.substr(trim($output), 0, 2000));
        vbotApiJsonResponse(['success' => false, 'message' => 'Trình học lệnh BroadLink trả về dữ liệu không hợp lệ'], 502);
    }
    vbotApiJsonResponse($learnResult);
}

//Lưu lệnh đã học
else if (isset($_POST['save_learned_command'])) {
    $response = ['success' => false, 'message' => ''];
    if (!file_exists($broadlink_json)) {
        $response['message'] = 'Không tìm thấy file broadlink.json';
        vbotApiJsonResponse($response, 404);
    }
    $command_name = trim($_POST['command_name'] ?? '');
    $device_mac   = strtoupper(trim($_POST['device_mac'] ?? ''));
    $command_data = trim($_POST['command_data'] ?? '');
    $command_reply = trim($_POST['command_reply'] ?? '');
    $wave_type = trim($_POST['wave_type'] ?? '');
    if ($command_name === '' || $device_mac === '' || $command_data === '') {
        $response['message'] = 'Dữ liệu gửi lên không hợp lệ';
        vbotApiJsonResponse($response, 400);
    }
    if (
        !vbotBroadlinkIsMac($device_mac)
        || !vbotBroadlinkValidLabel($command_name)
        || mb_strlen($command_reply) > 500
        || preg_match('/[\x00-\x1F\x7F]/', $command_reply)
        || strlen($command_data) > 65536
        || preg_match('/[\x00-\x1F\x7F]/', $command_data)
        || !in_array($wave_type, ['ir', 'rf'], true)
    ) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Dữ liệu lệnh BroadLink không hợp lệ'], 400);
    }
    $json = file_get_contents($broadlink_json);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $response['message'] = 'File JSON lỗi hoặc không hợp lệ';
        vbotApiJsonResponse($response, 500);
    }
    if (!isset($data['cmd_devices_remote']) || !is_array($data['cmd_devices_remote'])) {
        $data['cmd_devices_remote'] = [];
    }
    if (!isset($data['cmd_devices_remote'][$device_mac]) || !is_array($data['cmd_devices_remote'][$device_mac])) {
        $data['cmd_devices_remote'][$device_mac] = [];
    }
    foreach ($data['cmd_devices_remote'][$device_mac] as $cmd) {
        if (isset($cmd['name']) && strcasecmp($cmd['name'], $command_name) === 0) {
            $response['message'] = 'Lệnh với tên này đã tồn tại trên thiết bị, hãy đổi tên lệnh khác';
            vbotApiJsonResponse($response, 409);
        }
    }
    $data['cmd_devices_remote'][$device_mac][] = [
        "active" => true,
        "name" => $command_name,
        "reply" => $command_reply,
        "wave" => $wave_type,
        "data" => $command_data,
        "created_at" => date('H:i:s d-m-Y')
    ];
	backupBroadlinkJson($Config, $broadlink_json);
    if (!vbotBroadlinkSaveJson($broadlink_json, $data)) {
        $response['message'] = 'Không thể ghi file JSON';
        vbotApiJsonResponse($response, 500);
    }
    $response['success'] = true;
    $response['message'] = 'Đã lưu thành công lệnh: '.htmlspecialchars($command_name, ENT_QUOTES, 'UTF-8');
    vbotApiJsonResponse($response);
}

//Lưu lệnh khi được chỉnh sửa thông tin
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_learned_command_edit'])) {
    if (!file_exists($broadlink_json)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy file dữ liệu'], 404);
    }
    $macOld = strtoupper($_POST['mac_old'] ?? '');
    $macNew = strtoupper($_POST['mac_new'] ?? '');
    $index  = isset($_POST['index']) ? intval($_POST['index']) : -1;
    $name   = trim($_POST['name'] ?? '');
    $reply   = trim($_POST['reply'] ?? '');
    $data   = trim($_POST['data'] ?? '');
    $active = !empty($_POST['active']);
    if (
        !vbotBroadlinkIsMac($macOld)
        || !vbotBroadlinkIsMac($macNew)
        || $index < 0
        || !vbotBroadlinkValidLabel($name)
        || mb_strlen($reply) > 500
        || preg_match('/[\x00-\x1F\x7F]/', $reply)
        || strlen($data) > 65536
        || preg_match('/[\x00-\x1F\x7F]/', $data)
    ) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Thiếu tham số bắt buộc hoặc dữ liệu không hợp lệ'], 400);
    }
    $json = json_decode(file_get_contents($broadlink_json), true);
    if (!is_array($json)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'File dữ liệu BroadLink không hợp lệ'], 500);
    }
    if (!isset($json['cmd_devices_remote']) || !is_array($json['cmd_devices_remote'])) {
        $json['cmd_devices_remote'] = [];
    }
    $cmds =& $json['cmd_devices_remote'];
    if (!isset($cmds[$macOld][$index])) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy lệnh cần sửa'], 404);
    }
    $cmd = $cmds[$macOld][$index];
    $cmd['name'] = $name;
    $cmd['reply'] = $reply;
    $cmd['data'] = $data;
    $cmd['active'] = $active;
    $cmd['created_at'] = $cmd['created_at'] ?? date('Y-m-d H:i:s');
    //Nếu đổi thiết bị thực thi
    if ($macOld !== $macNew) {
        unset($cmds[$macOld][$index]);
        $cmds[$macOld] = array_values($cmds[$macOld]);
        if (!isset($cmds[$macNew])) {
            $cmds[$macNew] = [];
        }
        $cmds[$macNew][] = $cmd;
    } else {
        $cmds[$macOld][$index] = $cmd;
    }
	backupBroadlinkJson($Config, $broadlink_json);
    if (!vbotBroadlinkSaveJson($broadlink_json, $json)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu lệnh BroadLink đã chỉnh sửa'], 500);
    }
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Đã lưu dữ liệu thông tin: "' .htmlspecialchars($name, ENT_QUOTES, 'UTF-8'). '" thành công'
    ]);
}

//Xóa Lệnh Đã Học
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_learned_command'])) {
    if (!file_exists($broadlink_json)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy file dữ liệu'], 404);
    }
    $mac   = strtoupper($_POST['mac'] ?? '');
    $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
    if (!vbotBroadlinkIsMac($mac) || $index < 0) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Thiếu tham số'], 400);
    }
    $json = json_decode(file_get_contents($broadlink_json), true);
    if (!isset($json['cmd_devices_remote'][$mac][$index])) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Lệnh không tồn tại'], 404);
    }
	backupBroadlinkJson($Config, $broadlink_json);
    unset($json['cmd_devices_remote'][$mac][$index]);
    $json['cmd_devices_remote'][$mac] = array_values($json['cmd_devices_remote'][$mac]);
    if (!vbotBroadlinkSaveJson($broadlink_json, $json)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu dữ liệu sau khi xóa lệnh'], 500);
    }
    vbotApiJsonResponse(['success' => true]);
}

//Xóa toàn bộ device remote
else if (isset($_POST['deleteAllDevicesRemote'])) {
    $data = [];
    if (file_exists($broadlink_json)) {
        $data = json_decode(file_get_contents($broadlink_json), true);
        if (!is_array($data)) {
            $data = [];
        }
    }
	backupBroadlinkJson($Config, $broadlink_json);
    $data['devices_remote'] = [];
    if (!vbotBroadlinkSaveJson($broadlink_json, $data)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu dữ liệu sau khi xóa thiết bị'], 500);
    }
    vbotApiJsonResponse(['success' => true, 'message' => 'Đã xóa toàn bộ thiết bị Broadlink Remote']);
}

//Xóa toàn bộ các lệnh đã học
else if (isset($_POST['deleteAllCmdDevicesRemote'])) {
    if (!file_exists($broadlink_json)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy file dữ liệu'], 404);
    }
    $json = file_get_contents($broadlink_json);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'File JSON không hợp lệ'], 500);
    }
	backupBroadlinkJson($Config, $broadlink_json);
	$data['cmd_devices_remote'] = new stdClass();
    if (!vbotBroadlinkSaveJson($broadlink_json, $data)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu dữ liệu sau khi xóa các lệnh'], 500);
    }
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Đã xóa toàn bộ dữ liệu mã lệnh đã học'
    ]);
}

//Gửi Test Lệnh
else if (isset($_POST['sendBroadlink']) && isset($_POST['ip'], $_POST['mac'], $_POST['devtype'], $_POST['code'])) {
    $response = [
        "success" => false,
        "message" => ""
    ];
    $ip      = $_POST['ip'] ?? '';
    $mac     = $_POST['mac'] ?? '';
    $devtype = $_POST['devtype'] ?? '';
    $code    = $_POST['code'] ?? '';
    if (!$ip || !$mac || !$devtype || !$code) {
        $response['message'] = 'Thiếu tham số gửi lệnh';
        vbotApiJsonResponse($response, 400);
    }
    if (!vbotBroadlinkIsLanIp($ip) || !vbotBroadlinkIsMac($mac) || !preg_match('/^(?:0x)?[0-9a-f]{1,8}$/i', $devtype) || strlen($code) > 65536 || preg_match('/[\\x00-\\x1F\\x7F]/', $code)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Dữ liệu gửi lệnh BroadLink không hợp lệ'], 400);
    }
    $ip      = escapeshellarg($ip);
    $mac     = escapeshellarg($mac);
    $devtype = escapeshellarg($devtype);
    $code    = escapeshellarg($code);
    $CMD = "python3 "
         . escapeshellarg($VBot_Offline . "resource/broadlink/Broadlink.py") . " send"
         . " --ip $ip --mac $mac --devtype $devtype --code $code";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        vbotApiJsonResponse($response, 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công';
        vbotApiJsonResponse($response, 401);
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh Python';
        vbotApiJsonResponse($response, 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = trim(stream_get_contents($stream_out));
    if ($output === '') {
        $response['message'] = 'Python không trả dữ liệu';
        vbotApiJsonResponse($response, 502);
    }
    $py = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Phản hồi Python không hợp lệ';
        error_log('BroadLink sender returned invalid JSON: '.substr($output, 0, 2000));
        vbotApiJsonResponse($response, 502);
    }
    $response['success'] = (bool)($py['success'] ?? false);
    $response['message'] = $py['message'] ?? '';
    vbotApiJsonResponse($response, $response['success'] ? 200 : 502);
}

else {
	vbotApiJsonResponse([
		'success' => false,
		'message' => 'Yêu cầu không hợp lệ'
	], 400);
}
?>
