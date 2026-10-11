<?php
require_once __DIR__.'/Api_Helpers.php';
require_once dirname(__DIR__).'/ActionRegistry.php';
vbotApiInitialize(['POST']);
include '../../Configuration.php';
if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (empty($_SESSION['user_login'])) vbotApiJsonResponse(['success'=>false,'message'=>'Bạn chưa đăng nhập'], 401);
}
vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

$ir = $Config['internal_ir'] ?? [];
$jsonPath = $VBot_Offline.($ir['json_file'] ?? 'resource/internal_ir/commands.json');
$irBackupDir = $VBot_Offline.'html/Backup_Upgrade/Backup_Internal_IR';
if (!is_dir(dirname($jsonPath))) @mkdir(dirname($jsonPath), 0777, true);
// Serialize the whole read/modify/write request, not just the final rename.
// Learning does not touch the command file and must not hold this lock.
$internalIrRequestLock = null;
if (!isset($_POST['learn'])) {
    $internalIrRequestLock = @fopen($jsonPath.'.lock', 'c');
    if (!$internalIrRequestLock || !flock($internalIrRequestLock, LOCK_EX))
        vbotApiJsonResponse(['success'=>false, 'message'=>'Không khóa được file lệnh IR'], 500);
    register_shutdown_function(function () use ($internalIrRequestLock) {
        if (is_resource($internalIrRequestLock)) { flock($internalIrRequestLock, LOCK_UN); fclose($internalIrRequestLock); }
    });
    if (!file_exists($jsonPath) && !internalIrWrite($jsonPath, ['commands'=>[]], true))
        vbotApiJsonResponse(['success'=>false, 'message'=>'Không tạo được file lệnh IR'], 500);
}
set_exception_handler(function ($error) {
    if ($error instanceof LengthException)
        vbotApiJsonResponse(['success'=>false, 'message'=>'Tổng dữ liệu lệnh IR vượt quá 20 MB. Hãy giảm số lệnh hoặc độ dài mã raw; file hiện tại được giữ nguyên.'], 413);
    vbotApiJsonResponse(['success'=>false, 'message'=>'Không đọc được danh sách lệnh IR hợp lệ. File hiện tại được giữ nguyên; hãy dùng Sao lưu và khôi phục để phục hồi dữ liệu.'], 500);
});
if (isset($_POST['save']) || isset($_POST['bulk_save']) || isset($_POST['edit']) || isset($_POST['delete'])) {
    if (isset($_POST['revision']) && !hash_equals(hash_file('sha256', $jsonPath), (string)$_POST['revision']))
        vbotApiJsonResponse(['success'=>false, 'message'=>'Danh sách IR đã thay đổi ở phiên khác. Hãy tải lại trang trước khi lưu; thay đổi hiện tại chưa được ghi.'], 409);
}

function internalIrRead($path) {
    $raw = @file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data) || !isset($data['commands']) || !is_array($data['commands']) ||
        $data['commands'] !== array_values($data['commands']))
        throw new RuntimeException('Invalid IR command file');
    foreach ($data['commands'] as $item) {
        if (!is_array($item) || !is_string($item['name'] ?? null) || !is_array($item['data'] ?? null))
            throw new RuntimeException('Invalid IR command entry');
    }
    return $data;
}
function internalIrWrite($path, $data, $alreadyLocked = false) {
    global $internalIrRequestLock, $jsonPath;
    $alreadyLocked = $alreadyLocked || ($path === $jsonPath && is_resource($internalIrRequestLock));
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if ($encoded === false) return false;
    if (strlen($encoded) + 1 > 20971520)
        throw new LengthException('IR data exceeds 20 MB');
    $lock = $alreadyLocked ? null : @fopen($path.'.lock', 'c');
    if (!$alreadyLocked && (!$lock || !flock($lock, LOCK_EX))) { if ($lock) fclose($lock); return false; }
    $tmp = $path.'.tmp.'.bin2hex(random_bytes(6));
    try {
        if (file_put_contents($tmp, $encoded."\n", LOCK_EX) === false) return false;
        @chmod($tmp, 0777);
        if (!@rename($tmp, $path)) return false;
        @chmod($path, 0777);
        return true;
    } finally {
        if (is_file($tmp)) @unlink($tmp);
        if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
    }
}
function internalIrValidCommand($command) {
    if (!is_array($command) || !isset($command['format'], $command['keys']['command'])) return false;
    $parts = $command['keys']['command'];
    if (!is_array($parts) || $parts !== array_values($parts)) return false;
    $raw = isset($parts[0]) && is_array($parts[0]) ? $parts[0] : $parts;
    if (!is_array($raw) || $raw !== array_values($raw) || count($raw) < 3 || count($raw) > 20000) return false;
    $format = $command['format'];
    if (!is_array($format) || ($format['coding'] ?? null) !== 'raw') return false;
    if (isset($format['carrier']) && (!is_int($format['carrier']) || $format['carrier'] < 1 || $format['carrier'] > 1000000)) return false;
    $timebase = $format['timebase'] ?? 1;
    if (!is_int($timebase) || $timebase < 1 || $timebase > 1000000) return false;
    foreach ($raw as $duration) {
        if (!is_int($duration) && !(is_string($duration) && ctype_digit($duration))) return false;
        if ((int)$duration < 1 || (int)$duration > intdiv(2000000, $timebase)) return false;
    }
    return true;
}
function internalIrAction($value, array $config) {
    $action = trim((string)$value);
    if (strpos($action, 'vbot_action:') === 0) $action = substr($action, 12);
    return array_key_exists($action, vbotActionRegistryOptions($config)) ? $action : null;
}
function internalIrValidBackup($data, array $config) {
    if (!is_array($data) || !isset($data['commands']) || !is_array($data['commands']) || count($data['commands']) > 500) return false;
    if ($data['commands'] !== array_values($data['commands'])) return false;
    $names = [];
    foreach ($data['commands'] as $item) {
        if (!is_array($item) || !is_string($item['name'] ?? null) || !is_string($item['reply'] ?? '') || !is_string($item['action'] ?? 'none')) return false;
        $name = trim($item['name']); $reply = $item['reply'] ?? '';
        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($reply) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $name.$reply)) return false;
        if (array_key_exists('active', $item) && !is_bool($item['active'])) return false;
        // Saved playlist/radio references may outlive the corresponding source.
        $action = $item['action'] ?? 'none';
        if (internalIrAction($action, $config) === null && !preg_match('/^(?:playlist|radio):[A-Za-z0-9_-]{1,80}$/', $action)) return false;
        if (!internalIrValidCommand($item['data'] ?? null)) return false;
        $format = $item['data']['format'];
        if (!is_array($format) || ($format['coding'] ?? 'raw') !== 'raw') return false;
        foreach (['carrier', 'timebase'] as $field) {
            if (isset($format[$field]) && (!is_int($format[$field]) || $format[$field] < 1 || $format[$field] > 1000000)) return false;
        }
        $key = mb_strtolower($name, 'UTF-8');
        if (isset($names[$key])) return false;
        $names[$key] = true;
    }
    return true;
}
function internalIrBackupPath($directory, $name) {
    if (!is_string($name) || !preg_match('/^internal_ir_[0-9]{8}_[0-9]{6}_[a-f0-9]{12}\.json$/D', $name)) return false;
    $root = realpath($directory);
    $path = realpath($directory.DIRECTORY_SEPARATOR.$name);
    return $root !== false && $path !== false && dirname($path) === $root && is_file($path) && !is_link($directory.DIRECTORY_SEPARATOR.$name) ? $path : false;
}
function internalIrCreateBackup($source, $directory) {
    $raw = @file_get_contents($source);
    if (!is_string($raw) || strlen($raw) > 20971520) return false;
    if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) return false;
    $name = 'internal_ir_'.date('Ymd_His').'_'.bin2hex(random_bytes(6)).'.json';
    if (!vbotAtomicWriteFile($directory.DIRECTORY_SEPARATOR.$name, $raw, 'internal IR backup')) return false;
    @chmod($directory.DIRECTORY_SEPARATOR.$name, 0777);
    return $name;
}
function internalIrListBackups($directory) {
    $rows = [];
    foreach (glob($directory.'/internal_ir_*.json') ?: [] as $file) {
        $name = basename($file);
        if (internalIrBackupPath($directory, $name) === false) continue;
        $rows[] = ['name'=>$name, 'created_at'=>date('d-m-Y H:i:s', filemtime($file)), 'size'=>filesize($file)];
    }
    usort($rows, fn($a, $b)=>strcmp($b['name'], $a['name']));
    return $rows;
}
function internalIrRestoreBackup($source, $directory, $raw, array $config) {
    global $internalIrRequestLock, $jsonPath;
    if (!is_string($raw) || strlen($raw) > 20971520) return ['success'=>false, 'message'=>'Tệp sao lưu vượt quá 20 MB hoặc không hợp lệ'];
    $data = json_decode($raw, true);
    if (!internalIrValidBackup($data, $config)) return ['success'=>false, 'message'=>'Tệp sao lưu không đúng cấu trúc lệnh IR, có tên trùng hoặc mã/chức năng không hợp lệ'];
    $requestLocked = $source === $jsonPath && is_resource($internalIrRequestLock);
    $lock = $requestLocked ? null : @fopen($source.'.lock', 'c');
    if (!$requestLocked && (!$lock || !flock($lock, LOCK_EX))) { if ($lock) fclose($lock); return ['success'=>false, 'message'=>'Không khóa được file lệnh IR']; }
    try {
        $before = internalIrCreateBackup($source, $directory);
        if ($before === false) return ['success'=>false, 'message'=>'Không thể sao lưu dữ liệu hiện tại; chưa thực hiện khôi phục'];
        if (!internalIrWrite($source, $data, true)) return ['success'=>false, 'message'=>'Không thể ghi dữ liệu khôi phục. Bản sao trước khôi phục: '.$before];
        return ['success'=>true, 'message'=>'Đã khôi phục '.count($data['commands']).' lệnh IR. Đã sao lưu dữ liệu trước khôi phục.', 'before_backup'=>$before];
    } finally { if ($lock) { flock($lock, LOCK_UN); fclose($lock); } }
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

if (isset($_POST['backup_list'])) {
    vbotApiJsonResponse(['success'=>true, 'backups'=>internalIrListBackups($irBackupDir)]);
}
if (isset($_POST['backup_create'])) {
    $name = internalIrCreateBackup($jsonPath, $irBackupDir);
    if ($name === false) vbotApiJsonResponse(['success'=>false, 'message'=>'Không thể tạo bản sao lưu IR'], 500);
    vbotApiJsonResponse(['success'=>true, 'message'=>'Đã tạo bản sao lưu IR', 'name'=>$name]);
}
if (isset($_POST['backup_download'])) {
    $path = internalIrBackupPath($irBackupDir, $_POST['name'] ?? null);
    if ($path === false) vbotApiJsonResponse(['success'=>false, 'message'=>'Không tìm thấy bản sao lưu'], 404);
    if (filesize($path) > 20971520) vbotApiJsonResponse(['success'=>false, 'message'=>'Tệp sao lưu vượt quá 20 MB'], 400);
    $raw = @file_get_contents($path);
    if (!is_string($raw)) vbotApiJsonResponse(['success'=>false, 'message'=>'Không đọc được bản sao lưu'], 500);
    vbotApiJsonResponse(['success'=>true, 'name'=>basename($path), 'content'=>$raw]);
}
if (isset($_POST['backup_restore'])) {
    $path = internalIrBackupPath($irBackupDir, $_POST['name'] ?? null);
    if ($path === false) vbotApiJsonResponse(['success'=>false, 'message'=>'Không tìm thấy bản sao lưu'], 404);
    if (filesize($path) > 20971520) vbotApiJsonResponse(['success'=>false, 'message'=>'Tệp sao lưu vượt quá 20 MB'], 400);
    $result = internalIrRestoreBackup($jsonPath, $irBackupDir, @file_get_contents($path), $Config);
    vbotApiJsonResponse($result, $result['success'] ? 200 : 400);
}
if (isset($_POST['backup_upload'])) {
    $file = $_FILES['backup_file'] ?? [];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '') ||
        ($file['size'] ?? 0) < 1 || ($file['size'] ?? 0) > 20971520 || strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) !== 'json') {
        vbotApiJsonResponse(['success'=>false, 'message'=>'Chọn tệp sao lưu .json hợp lệ, tối đa 20 MB'], 400);
    }
    $result = internalIrRestoreBackup($jsonPath, $irBackupDir, file_get_contents($file['tmp_name']), $Config);
    vbotApiJsonResponse($result, $result['success'] ? 200 : 400);
}
if (isset($_POST['list'])) {
    vbotApiJsonResponse(['success'=>true,'data'=>internalIrRead($jsonPath),'revision'=>hash_file('sha256', $jsonPath),'playlists'=>internalIrPlaylists($VBot_Offline),'radios'=>internalIrRadios($Config),'config'=>[
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
    $action = internalIrAction($_POST['action'] ?? 'none', $Config);
    $command = json_decode($_POST['data'] ?? '', true);
    if ($name==='' || mb_strlen($name)>100 || mb_strlen($reply)>500 ||
        preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $name.$reply) || $action === null || !internalIrValidCommand($command))
        vbotApiJsonResponse(['success'=>false,'message'=>'Tên hoặc dữ liệu IR không hợp lệ'], 400);
    $data = internalIrRead($jsonPath); $data['commands'] = $data['commands'] ?? [];
    if (count($data['commands']) >= 500)
        vbotApiJsonResponse(['success'=>false,'message'=>'Danh sách IR đã đạt giới hạn 500 lệnh. Hãy xóa lệnh không dùng trước khi thêm.'], 400);
    foreach ($data['commands'] as $item) if (mb_strtolower($item['name'] ?? '', 'UTF-8')===mb_strtolower($name, 'UTF-8'))
        vbotApiJsonResponse(['success'=>false,'message'=>'Tên lệnh đã tồn tại'], 409);
    $data['commands'][]=['active'=>true,'name'=>$name,'reply'=>$reply,'action'=>$action,'data'=>$command,'created_at'=>date('H:i:s d-m-Y')];
    if (!internalIrWrite($jsonPath,$data)) vbotApiJsonResponse(['success'=>false,'message'=>'Không thể lưu file lệnh'],500);
    vbotApiJsonResponse(['success'=>true,'message'=>'Đã lưu lệnh IR']);
}
if (isset($_POST['bulk_save'])) {
    $commands = json_decode($_POST['commands'] ?? '', true);
    if (!is_array($commands) || $commands !== array_values($commands) || count($commands) > 500)
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
        $action = internalIrAction($item['action'] ?? 'none', $Config);
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
    $action = internalIrAction($_POST['action'] ?? 'none', $Config);
    $command = json_decode($_POST['data'] ?? '', true);
    $active = ($_POST['active'] ?? '0') === '1';
    if ($index === false || $name === '' || mb_strlen($name) > 100 || mb_strlen($reply) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $name.$reply) || $action === null || !internalIrValidCommand($command))
        vbotApiJsonResponse(['success'=>false,'message'=>'Thông tin hoặc mã IR chỉnh sửa không hợp lệ'],400);
    $stored = internalIrRead($jsonPath); $commands = $stored['commands'] ?? [];
    if (!isset($commands[$index])) vbotApiJsonResponse(['success'=>false,'message'=>'Không tìm thấy lệnh IR'],404);
    foreach ($commands as $i=>$item) if ($i !== $index && mb_strtolower($item['name'] ?? '', 'UTF-8') === mb_strtolower($name, 'UTF-8'))
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
