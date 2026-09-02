<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
include '../../Configuration.php';
if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (empty($_SESSION['user_login'])) vbotApiJsonResponse(['success'=>false,'message'=>'Bạn chưa đăng nhập'], 401);
}
vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

$ir = $Config['internal_ir'] ?? [];
$jsonPath = $VBot_Offline.($ir['json_file'] ?? 'resource/internal_ir/commands.json');
if (!is_dir(dirname($jsonPath))) @mkdir(dirname($jsonPath), 0777, true);
if (!file_exists($jsonPath)) file_put_contents($jsonPath, "{\n  \"commands\": []\n}", LOCK_EX);

function internalIrRead($path) {
    $data = json_decode((string)@file_get_contents($path), true);
    return is_array($data) ? $data : ['commands'=>[]];
}
function internalIrWrite($path, $data) {
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if ($encoded === false) return false;
    $tmp = $path.'.tmp.'.bin2hex(random_bytes(6));
    if (file_put_contents($tmp, $encoded."\n", LOCK_EX) === false) return false;
    @chmod($tmp, 0777);
    if (!@rename($tmp, $path)) { @unlink($tmp); return false; }
    @chmod($path, 0777);
    return true;
}
function internalIrValidCommand($command) {
    if (!is_array($command) || !isset($command['format'], $command['keys']['command'])) return false;
    $parts = $command['keys']['command'];
    $raw = isset($parts[0]) && is_array($parts[0]) ? $parts[0] : $parts;
    if (!is_array($raw) || count($raw) < 3 || count($raw) > 20000) return false;
    foreach ($raw as $duration) {
        if (!is_int($duration) && !ctype_digit((string)$duration)) return false;
        if ((int)$duration < 1 || (int)$duration > 2000000) return false;
    }
    return true;
}
function internalIrAction($value) {
    $action = trim((string)$value);
    $allowed = [
        'none','wakeup','volume_up','volume_down','volume_max','volume_min',
        'mic_toggle','media_play_pause','media_stop','media_next','media_previous',
        'conversation_toggle','wakeup_reply_toggle','stop_tts','cancel_wakeup',
        'restart_vbot','reboot_os','play_all_local','mute','unmute'
    ];
    if (in_array($action, $allowed, true)) return $action;
    if (preg_match('/^playlist:[A-Za-z0-9_-]{1,80}$/', $action)) return $action;
    return preg_match('/^radio:[a-f0-9]{12}$/', $action) ? $action : null;
}
function internalIrPlaylists($root) {
    $manifest = json_decode((string)@file_get_contents($root.'html/includes/cache/PlayLists.json'), true);
    $result = [];
    foreach (($manifest['playlists'] ?? []) as $item) {
        $id = trim((string)($item['id'] ?? ''));
        $name = trim((string)($item['name'] ?? ''));
        if ($id !== '' && $name !== '' && preg_match('/^[A-Za-z0-9_-]{1,80}$/', $id))
            $result[] = ['id'=>$id, 'name'=>$name];
    }
    return $result;
}
function internalIrRadios($config) {
    $result = [];
    foreach (($config['media_player']['radio_data'] ?? []) as $item) {
        $name = trim((string)($item['name'] ?? ''));
        $link = trim((string)($item['link'] ?? ''));
        if ($name !== '' && filter_var($link, FILTER_VALIDATE_URL)) $result[] = ['id'=>substr(sha1($name."\n".$link), 0, 12), 'name'=>$name];
    }
    return $result;
}
function internalIrRun($command, $VBot_Offline, $ssh_host, $ssh_port, $ssh_user, $ssh_password) {
    // Chạy trực tiếp trên VBot để loại bỏ thời gian kết nối/xác thực SSH.
    $localLines = [];
    $localExitCode = 0;
    exec($command.' 2>&1', $localLines, $localExitCode);
    for ($i=count($localLines)-1; $i>=0; $i--) {
        $localResult = json_decode(trim($localLines[$i]), true);
        if (is_array($localResult)) return $localResult;
    }
    $localDetail = trim(preg_replace('/\s+/', ' ', strip_tags(implode(' ', $localLines))));
    if (strlen($localDetail) > 500) $localDetail = substr($localDetail, 0, 500).'...';
    return ['success'=>false,'message'=>'Trình IR local không trả về JSON'.($localDetail !== '' ? ': '.$localDetail : '')];

}

if (isset($_POST['list'])) {
    vbotApiJsonResponse(['success'=>true,'data'=>internalIrRead($jsonPath),'playlists'=>internalIrPlaylists($VBot_Offline),'radios'=>internalIrRadios($Config),'config'=>[
        'tx_active'=>(bool)($ir['tx_active'] ?? ($ir['active'] ?? false)),
        'rx_active'=>(bool)($ir['rx_active'] ?? ($ir['active'] ?? false)),
        'rx_control_active'=>(bool)($ir['rx_control_active'] ?? false),
        'receive_match_threshold'=>(float)($ir['receive_match_threshold'] ?? 0.72),
        'receive_debounce_ms'=>(int)($ir['receive_debounce_ms'] ?? 450),
        'tx_gpio'=>(int)($ir['tx_gpio'] ?? 17),'rx_gpio'=>(int)($ir['rx_gpio'] ?? 4)
    ]]);
}
if (isset($_POST['learn'])) {
    if (empty($ir['rx_active']) && empty($ir['active'])) vbotApiJsonResponse(['success'=>false,'message'=>'IR thu (RX) đang tắt trong Config.json'], 409);
    $cmd = 'python3 '.escapeshellarg($VBot_Offline.'resource/internal_ir/internal_ir_cli.py').' learn --device '.escapeshellarg($ir['rx_device'] ?? '/dev/lirc1')
         .' --timeout '.max(5, min(60, intval($ir['learn_timeout'] ?? 20))).' --carrier '.intval($ir['carrier'] ?? 38000);
    vbotApiJsonResponse(internalIrRun($cmd,$VBot_Offline,$ssh_host,$ssh_port,$ssh_user,$ssh_password));
}
if (isset($_POST['save'])) {
    $name = trim($_POST['name'] ?? ''); $reply = trim($_POST['reply'] ?? '');
    $action = internalIrAction($_POST['action'] ?? 'none');
    $command = json_decode($_POST['data'] ?? '', true);
    if ($name==='' || mb_strlen($name)>100 || $action === null || !internalIrValidCommand($command))
        vbotApiJsonResponse(['success'=>false,'message'=>'Tên hoặc dữ liệu IR không hợp lệ'], 400);
    $data = internalIrRead($jsonPath); $data['commands'] = $data['commands'] ?? [];
    foreach ($data['commands'] as $item) if (strcasecmp($item['name'] ?? '', $name)===0)
        vbotApiJsonResponse(['success'=>false,'message'=>'Tên lệnh đã tồn tại'], 409);
    $data['commands'][]=['active'=>true,'name'=>$name,'reply'=>$reply,'action'=>$action,'data'=>$command,'created_at'=>date('H:i:s d-m-Y')];
    if (!internalIrWrite($jsonPath,$data)) vbotApiJsonResponse(['success'=>false,'message'=>'Không thể lưu file lệnh'],500);
    vbotApiJsonResponse(['success'=>true,'message'=>'Đã lưu lệnh IR']);
}
if (isset($_POST['bulk_save'])) {
    $commands = json_decode($_POST['commands'] ?? '', true);
    if (!is_array($commands) || count($commands) > 500)
        vbotApiJsonResponse(['success'=>false,'message'=>'Danh sách lệnh IR không hợp lệ'],400);

    $stored = internalIrRead($jsonPath);
    $oldCommands = $stored['commands'] ?? [];
    $validated = [];
    $usedNames = [];
    foreach ($commands as $index=>$item) {
        if (!is_array($item))
            vbotApiJsonResponse(['success'=>false,'message'=>'Dòng '.($index+1).' không hợp lệ'],400);
        $name = trim((string)($item['name'] ?? ''));
        $reply = trim((string)($item['reply'] ?? ''));
        $action = internalIrAction($item['action'] ?? 'none');
        $command = $item['data'] ?? null;
        $nameKey = mb_strtolower($name, 'UTF-8');
        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($reply) > 500 || $action === null || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $name.$reply))
            vbotApiJsonResponse(['success'=>false,'message'=>'Tên hoặc phản hồi ở dòng '.($index+1).' không hợp lệ'],400);
        if (!internalIrValidCommand($command))
            vbotApiJsonResponse(['success'=>false,'message'=>'Mã IR ở dòng '.($index+1).' không hợp lệ'],400);
        if (isset($usedNames[$nameKey]))
            vbotApiJsonResponse(['success'=>false,'message'=>'Tên lệnh bị trùng ở dòng '.($index+1)],409);
        $usedNames[$nameKey] = true;
        $createdAt = trim((string)($item['created_at'] ?? ($oldCommands[$index]['created_at'] ?? '')));
        if ($createdAt === '') $createdAt = date('H:i:s d-m-Y');
        $validated[] = [
            'active'=>!empty($item['active']),
            'name'=>$name,
            'reply'=>$reply,
            'action'=>$action,
            'data'=>$command,
            'created_at'=>$createdAt,
            'updated_at'=>date('H:i:s d-m-Y')
        ];
    }
    $stored['commands'] = $validated;
    if (!internalIrWrite($jsonPath,$stored))
        vbotApiJsonResponse(['success'=>false,'message'=>'Không thể lưu toàn bộ cấu hình IR'],500);
    vbotApiJsonResponse(['success'=>true,'message'=>'Đã lưu toàn bộ cấu hình IR']);
}
if (isset($_POST['edit'])) {
    $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $reply = trim($_POST['reply'] ?? '');
    $action = internalIrAction($_POST['action'] ?? 'none');
    $command = json_decode($_POST['data'] ?? '', true);
    $active = ($_POST['active'] ?? '0') === '1';
    if ($index === false || $name === '' || mb_strlen($name) > 100 || mb_strlen($reply) > 500 || $action === null || !internalIrValidCommand($command))
        vbotApiJsonResponse(['success'=>false,'message'=>'Thông tin hoặc mã IR chỉnh sửa không hợp lệ'],400);
    $stored = internalIrRead($jsonPath); $commands = $stored['commands'] ?? [];
    if (!isset($commands[$index])) vbotApiJsonResponse(['success'=>false,'message'=>'Không tìm thấy lệnh IR'],404);
    foreach ($commands as $i=>$item) if ($i !== $index && strcasecmp($item['name'] ?? '', $name) === 0)
        vbotApiJsonResponse(['success'=>false,'message'=>'Tên lệnh đã tồn tại'],409);
    $commands[$index]['active']=$active;
    $commands[$index]['name']=$name;
    $commands[$index]['reply']=$reply;
    $commands[$index]['action']=$action;
    $commands[$index]['data']=$command;
    $commands[$index]['updated_at']=date('H:i:s d-m-Y');
    $stored['commands']=$commands;
    if (!internalIrWrite($jsonPath,$stored)) vbotApiJsonResponse(['success'=>false,'message'=>'Không thể lưu lệnh đã sửa'],500);
    vbotApiJsonResponse(['success'=>true,'message'=>'Đã cập nhật lệnh IR']);
}
if (isset($_POST['delete'])) {
    $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
    $data = internalIrRead($jsonPath);
    if ($index===false || !isset($data['commands'][$index])) vbotApiJsonResponse(['success'=>false,'message'=>'Không tìm thấy lệnh'],404);
    array_splice($data['commands'],$index,1);
    if (!internalIrWrite($jsonPath,$data)) vbotApiJsonResponse(['success'=>false,'message'=>'Không thể lưu file lệnh'],500);
    vbotApiJsonResponse(['success'=>true,'message'=>'Đã xóa lệnh']);
}
vbotApiJsonResponse(['success'=>false,'message'=>'Yêu cầu không hợp lệ'],400);
?>
