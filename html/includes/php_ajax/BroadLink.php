<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include '../../Configuration.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (
        !isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
    ) {
        session_unset();
        session_destroy();
        echo json_encode([
            'success' => false,
            'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$broadlink_json = $VBot_Offline.$Config['broadlink']['json_file'];

if (!file_exists($broadlink_json)) {
    file_put_contents($broadlink_json, "{}");
    shell_exec('chmod 0777 ' . escapeshellarg($broadlink_json));
}

//Xóa device broadlink
if (isset($_POST['delete_device_broadlink_remote']) && !empty($_POST['mac'])) {
    $result = [
        'success' => false,
        'message' => '',
        'data' => []
    ];
    $mac = strtoupper(trim($_POST['mac']));
    if (!file_exists($broadlink_json)) {
        $result['message'] = 'Không tìm thấy file JSON';
        echo json_encode($result);
        exit;
    }
    $json = file_get_contents($broadlink_json);
    $data = json_decode($json, true);
    if (!$data || !isset($data['devices_remote'])) {
        $result['message'] = 'Cấu trúc JSON không hợp lệ';
        echo json_encode($result);
        exit;
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
        echo json_encode($result);
        exit;
    }
    $data['devices_remote'] = array_values($devices);
    file_put_contents($broadlink_json, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $result['success'] = true;
    $result['message'] = 'Đã xóa thiết bị Broadlink có địa chỉ MAC: ' . $mac;
    echo json_encode($result);
    exit;
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
    if (!file_exists($broadlink_json)) {
        $result['message'] = 'File JSON không tồn tại';
        echo json_encode($result);
        exit;
    }
    $json = json_decode(file_get_contents($broadlink_json), true);
    if (!$json || !isset($json['devices_remote'])) {
        $result['message'] = 'Dữ liệu JSON không hợp lệ';
        echo json_encode($result);
        exit;
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
        echo json_encode($result);
        exit;
    }
    file_put_contents($broadlink_json, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $result['success'] = true;
    $result['message'] = 'Đã đổi tên thiết bị thành công';
    echo json_encode($result);
    exit;
}

//Học Lệnh
else if (isset($_POST['learn_command_broadlink'])) {
	$response = [
		"success" => false,
		"message" => ""
	];
    $ip = $_POST['ip'] ?? '';
    $mac = $_POST['mac'] ?? '';
    $devtype = $_POST['devtype'] ?? '';
    if (!$ip || !$mac || !$devtype) {
        $response['message'] = 'Thiếu tham số thiết bị';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    $ip = escapeshellarg($ip);
    $mac = escapeshellarg($mac);
    $devtype = escapeshellarg($devtype);
	$CMD = "python3 "
		 . $VBot_Offline . "resource/broadlink/Broadlink.py learn"
		 . " --ip $ip --mac $mac --devtype $devtype";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh Python';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    echo $output;
    exit();
}

//Lưu lệnh đã học
else if (isset($_POST['save_learned_command'])) {
    if (!file_exists($broadlink_json)) {
        $response['message'] = 'Không tìm thấy file broadlink.json';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $command_name = trim($_POST['command_name'] ?? '');
    $device_mac   = strtoupper(trim($_POST['device_mac'] ?? ''));
    $command_data = trim($_POST['command_data'] ?? '');
    if ($command_name === '' || $device_mac === '' || $command_data === '') {
        $response['message'] = 'Dữ liệu gửi lên không hợp lệ';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $json = file_get_contents($broadlink_json);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $response['message'] = 'File JSON lỗi hoặc không hợp lệ';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!isset($data['cmd_devices_remote'])) {
        $data['cmd_devices_remote'] = [];
    }
    if (!isset($data['cmd_devices_remote'][$device_mac])) {
        $data['cmd_devices_remote'][$device_mac] = [];
    }
    foreach ($data['cmd_devices_remote'][$device_mac] as $cmd) {
        if (strcasecmp($cmd['name'], $command_name) === 0) {
            $response['message'] = 'Lệnh với tên "' . $command_name . '" đã tồn tại trên thiết bị thực thi lệnh này, hãy đổi tên lệnh khác';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    $data['cmd_devices_remote'][$device_mac][] = [
        "active" => true,
        "name" => $command_name,
        "data" => $command_data,
        "created_at" => date('H:i:s d-m-Y')
    ];
    if (file_put_contents($broadlink_json, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        $response['message'] = 'Không thể ghi file JSON';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $response['success'] = true;
    $response['message'] = 'Đã lưu thành công lệnh: '.$command_name;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

//Lưu lệnh khi được chỉnh sửa thông tin
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_learned_command_edit'])) {
    if (!file_exists($broadlink_json)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy file dữ liệu']);
        exit;
    }
    $macOld = strtoupper($_POST['mac_old'] ?? '');
    $macNew = strtoupper($_POST['mac_new'] ?? '');
    $index  = isset($_POST['index']) ? intval($_POST['index']) : -1;
    $name   = trim($_POST['name'] ?? '');
    $data   = trim($_POST['data'] ?? '');
    $active = !empty($_POST['active']);
    if ($macOld === '' || $macNew === '' || $index < 0) {
        echo json_encode(['success' => false, 'message' => 'Thiếu tham số bắt buộc']);
        exit;
    }
    $json = json_decode(file_get_contents($broadlink_json), true);
    if (!isset($json['cmd_devices_remote'])) {
        $json['cmd_devices_remote'] = [];
    }
    $cmds =& $json['cmd_devices_remote'];
    if (!isset($cmds[$macOld][$index])) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy lệnh cần sửa']);
        exit;
    }
    $cmd = $cmds[$macOld][$index];
    $cmd['name'] = $name;
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
    file_put_contents($broadlink_json, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    echo json_encode(['success' => true, 'message' => 'Đã lưu dữ liệu thông tin: "' .$name. '" thành công']);
    exit;
}

//Xóa Lệnh Đã Học
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_learned_command'])) {
    if (!file_exists($broadlink_json)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy file dữ liệu']);
        exit;
    }
    $mac   = strtoupper($_POST['mac'] ?? '');
    $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
    if ($mac === '' || $index < 0) {
        echo json_encode(['success' => false, 'message' => 'Thiếu tham số']);
        exit;
    }
    $json = json_decode(file_get_contents($broadlink_json), true);
    if (!isset($json['cmd_devices_remote'][$mac][$index])) {
        echo json_encode(['success' => false, 'message' => 'Lệnh không tồn tại']);
        exit;
    }
    unset($json['cmd_devices_remote'][$mac][$index]);
    $json['cmd_devices_remote'][$mac] = array_values($json['cmd_devices_remote'][$mac]);
    file_put_contents($broadlink_json, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    echo json_encode(['success' => true]);
    exit;
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
    $data['devices_remote'] = [];
    file_put_contents($broadlink_json, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true, 'message' => 'Đã xóa toàn bộ thiết bị Broadlink Remote' ], JSON_UNESCAPED_UNICODE);
    exit;
}

//Xóa toàn bộ các lệnh đã học
else if (isset($_POST['deleteAllCmdDevicesRemote'])) {
    if (!file_exists($broadlink_json)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy file dữ liệu'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $json = file_get_contents($broadlink_json);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        echo json_encode(['success' => false, 'message' => 'File JSON không hợp lệ'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    //$data['cmd_devices_remote'] = [];
	$data['cmd_devices_remote'] = new stdClass();
    file_put_contents($broadlink_json, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa toàn bộ dữ liệu mã lệnh đã học'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    $ip      = escapeshellarg($ip);
    $mac     = escapeshellarg($mac);
    $devtype = escapeshellarg($devtype);
    $code    = escapeshellarg($code);

    $CMD = "python3 "
         . $VBot_Offline . "resource/broadlink/Broadlink.py send"
         . " --ip $ip --mac $mac --devtype $devtype --code $code";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = 'Không thể kết nối tới máy chủ SSH';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = 'Xác thực SSH không thành công';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    $stream = ssh2_exec($connection, $CMD);
    if (!$stream) {
        $response['message'] = 'Không thể thực thi lệnh Python';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = trim(stream_get_contents($stream_out));
    if ($output === '') {
        $response['message'] = 'Python không trả dữ liệu';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    $py = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Phản hồi Python không hợp lệ';
        $response['debug'] = $output;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    $response['success'] = (bool)($py['success'] ?? false);
    $response['message'] = $py['message'] ?? '';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

else {
	echo json_encode([
		'success' => false,
		'message' => 'Yêu cầu không hợp lệ'
	]);
}
?>