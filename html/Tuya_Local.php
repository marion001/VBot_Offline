<?php
/**
 * Tuya Local device onboarding for VBot.
 *
 * Files are deliberately kept outside the public html directory:
 *   resource/tuya_local/tuya_local_devices.json  - normalized device config
 *   resource/tuya_local/tuya_devices_raw.json    - tuya-local-key output
 *   resource/tuya_local/tinytuya_scan.json       - LAN scan snapshot
 *
 * The local_key is never rendered in the browser.
 */
include 'Configuration.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['vbot_csrf_token']) || !is_string($_SESSION['vbot_csrf_token']) || strlen($_SESSION['vbot_csrf_token']) < 64) {
    $_SESSION['vbot_csrf_token'] = bin2hex(random_bytes(32));
}
if (!empty($Config['contact_info']['user_login']['active'])) {
    if (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200)) {
        session_unset(); session_destroy(); header('Location: Login.php'); exit;
    }
}
// SSE có thể mở lâu trong lúc chờ quét QR. Nhả khóa session sau xác thực
// để tải lại trang và các request WebUI cùng session không bị xếp hàng chờ.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

$base = rtrim($VBot_Offline, '/\\') . '/resource/tuya_local';
$keyTool = $base . '/tuya-local-key';
$normalizedFile = $base . '/tuya_local_devices.json';
$rawFile = $base . '/tuya_devices_raw.json';
$scanFile = $base . '/tinytuya_scan.json';
$tuyaBackupDir = __DIR__ . '/Backup_Upgrade/Backup_Tuya_Local';
$tuyaBackupLimit = 5;
$tuyaBackupParent = dirname($tuyaBackupDir);
function tuya_exec_chmod_0777($path, $recursive = false) {
    if (!is_string($path) || $path === '' || !function_exists('exec')) return false;
    $mode = $recursive ? '-R 0777' : '0777';
    $command = 'chmod ' . $mode . ' -- ' . escapeshellarg($path) . ' 2>&1';
    $output = [];
    $exitCode = 1;
    @exec($command, $output, $exitCode);
    return $exitCode === 0;
}
if (!is_dir($tuyaBackupParent)) @mkdir($tuyaBackupParent, 0777, true);
if (is_dir($tuyaBackupParent)) tuya_exec_chmod_0777($tuyaBackupParent, true);
if (!is_dir($tuyaBackupDir)) @mkdir($tuyaBackupDir, 0777, true);
if (is_dir($tuyaBackupDir)) tuya_exec_chmod_0777($tuyaBackupDir, true);
$runtime = rtrim(sys_get_temp_dir(), '/\\') . '/vbot_tuya_runtime';
if (!is_dir($runtime)) @mkdir($runtime, 0777, true);
if (is_dir($runtime)) tuya_exec_chmod_0777($runtime);
$scanLogFile = $runtime . '/tinytuya_scan.log';
$jobFile = $runtime . '/runtime_state.json';
$logFile = $runtime . '/tuya_devices.log';
$doneFile = $runtime . '/finished.flag';
$exitFile = $runtime . '/exit_code';
$pidFile = $runtime . '/tuya_devices.pid';
$jobMeta = tuya_json_read($jobFile, []);
$qrFile = !empty($jobMeta['qr_file']) ? $jobMeta['qr_file'] : ($runtime . '/tuya-login-qr.png');
if (!is_dir($base)) @mkdir($base, 0777, true);
// PHP (www-data) và các tiến trình Python của VBot cùng cần ghi dữ liệu Tuya.
if (is_dir($base)) {
    $baseMode = @fileperms($base);
    if ($baseMode === false || (($baseMode & 0777) !== 0777)) tuya_exec_chmod_0777($base);
}

function tuya_json_read($file, $fallback = []) {
    if (!is_file($file)) return $fallback;
    $data = json_decode((string)@file_get_contents($file), true);
    return is_array($data) ? $data : $fallback;
}
function tuya_account_id($userCode) { return 'acct_' . substr(hash('sha256', (string)$userCode), 0, 16); }
function tuya_upsert_account(&$config, $userCode) {
    $userCode = trim((string)$userCode);
    if ($userCode === '') return '';
    if (!isset($config['accounts']) || !is_array($config['accounts'])) $config['accounts'] = [];
    $id = tuya_account_id($userCode);
    $config['accounts'][$id] = ['id'=>$id, 'user_code'=>$userCode, 'updated_at'=>date('c')];
    $config['active_account_id'] = $id;
    unset($config['user_code']);
    return $id;
}
function tuya_json_write($file, $data, &$error = null) {
    $error = '';
    // The WebUI and VBot/Python share these JSON files. Ensure the destination
    // is writable by both before opening it; only PHP (the WebUI process) does this.
    $newFile = !is_file($file);
    global $normalizedFile, $tuyaBackupDir, $tuyaBackupLimit;
    if (!$newFile && isset($normalizedFile, $tuyaBackupDir, $tuyaBackupLimit) && $file === $normalizedFile
        && !tuya_backup_file($normalizedFile, $tuyaBackupDir, $tuyaBackupLimit)) {
        $error = 'Không tạo được bản sao lưu trước khi cập nhật cấu hình.';
        return false;
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) { $error = 'Không mã hóa được dữ liệu JSON.'; return false; }
    $directory = dirname($file);
    if (!is_dir($directory)) { $error = 'Thư mục lưu dữ liệu không tồn tại.'; return false; }
    $dirMode = @fileperms($directory);
    if ($dirMode === false || (($dirMode & 0777) !== 0777)) @chmod($directory, 0777);
    $dirMode = @fileperms($directory);
    if ($dirMode === false || (($dirMode & 0777) !== 0777)) {
        $error = 'PHP không thể đặt quyền 0777 cho thư mục dữ liệu.';
        return false;
    }
    if (!$newFile) {
        $currentMode = @fileperms($file);
        if ($currentMode === false || (($currentMode & 0777) !== 0777)) {
            if (!@chmod($file, 0777)) { $error = 'PHP không thể đặt quyền 0777 cho file cấu hình.'; return false; }
        }
    }
    if (!$newFile) {
        $handle = @fopen($file, 'c+');
        if (!$handle) { $error = 'PHP không mở được file cấu hình để ghi.'; return false; }
        if (!@flock($handle, LOCK_EX)) { @fclose($handle); $error = 'Không khóa được file cấu hình để ghi an toàn.'; return false; }
        $ok = @ftruncate($handle, 0) && @rewind($handle);
        $written = $ok ? @fwrite($handle, $json) : false;
        if ($written !== false) @fflush($handle);
        @flock($handle, LOCK_UN); @fclose($handle);
        if ($written === false || $written !== strlen($json)) { $error = 'Ghi file cấu hình không đầy đủ; kiểm tra dung lượng hoặc filesystem chỉ đọc.'; return false; }
        if (!@chmod($file, 0777) || (((@fileperms($file) ?: 0) & 0777) !== 0777)) { $error = 'Đã ghi nhưng không xác nhận được quyền 0777 cho file cấu hình.'; return false; }
        return true;
    }
    $tmp = $file . '.tmp';
    $ok = @file_put_contents($tmp, $json, LOCK_EX);
    if ($ok === false) { $error = 'PHP không tạo/ghi được file tạm trong thư mục dữ liệu.'; return false; }
    if (!@chmod($tmp, 0777)) { @unlink($tmp); $error = 'PHP không đặt được quyền 0777 cho file JSON tạm.'; return false; }
    if (@rename($tmp, $file)) {
        if (!@chmod($file, 0777) || (((@fileperms($file) ?: 0) & 0777) !== 0777)) { $error = 'Đã tạo file nhưng không xác nhận được quyền 0777.'; return false; }
        return true;
    }
    // rename() cannot replace an existing file on some mounted/network filesystems.
    // Keep the same-directory temp file and fall back to a locked in-place write.
    $json = @file_get_contents($tmp);
    if ($json === false) { $error = 'Không đọc lại được file JSON tạm sau khi thay file thất bại.'; return false; }
    if (is_file($file) && !@chmod($file, 0777)) { $error = 'Không thể đặt quyền 0777 cho file đích trước khi ghi.'; return false; }
    $written = @file_put_contents($file, $json, LOCK_EX);
    if ($written === false || $written !== strlen($json)) { $error = 'Không ghi được file đích; filesystem có thể đang chỉ đọc hoặc bị ACL chặn.'; return false; }
    @unlink($tmp);
    @chmod($file, 0777);
    $finalMode = @fileperms($file);
    if ($finalMode === false || (($finalMode & 0777) !== 0777)) { $error = 'Đã ghi nhưng không đặt/ xác nhận được quyền 0777 cho file.'; return false; }
    return true;
}
function tuya_valid_bundle($data) {
    if (!is_array($data) || (int)($data['schema_version'] ?? 1) !== 1 || !isset($data['devices']) || !is_array($data['devices']) || count($data['devices']) > 500) return false;
    $ids = [];
    foreach ($data['devices'] as $device) {
        if (!is_array($device)) return false;
        $id = trim((string)($device['id'] ?? $device['device_id'] ?? ''));
        if ($id === '' || strlen($id) > 128 || !preg_match('/^[A-Za-z0-9_-]+$/', $id) || isset($ids[$id])) return false;
        $ids[$id] = true;
        if (isset($device['tuya']) && !is_array($device['tuya'])) return false;
        if (isset($device['tuya']['dps']) && !is_array($device['tuya']['dps'])) return false;
        foreach (($device['tuya']['dps'] ?? []) as $entry) {
            if (!is_array($entry)) return false;
            if (isset($entry['tts_response']) && !(is_string($entry['tts_response']) && strlen($entry['tts_response']) <= 4000) && !(is_array($entry['tts_response']) && count($entry['tts_response']) <= 2 && array_diff(array_keys($entry['tts_response']), ['on','off']) === [] && (!isset($entry['tts_response']['on']) || (is_string($entry['tts_response']['on']) && strlen($entry['tts_response']['on']) <= 4000)) && (!isset($entry['tts_response']['off']) || (is_string($entry['tts_response']['off']) && strlen($entry['tts_response']['off']) <= 4000)))) return false;
            if (isset($entry['dps_id']) && $entry['dps_id'] !== null && !preg_match('/^\d{1,8}$/', (string)$entry['dps_id'])) return false;
            if (isset($entry['commands']) && !is_array($entry['commands'])) return false;
        }
    }
    if (isset($data['accounts']) && !is_array($data['accounts'])) return false;
    if (isset($data['selected_ids']) && (!is_array($data['selected_ids']) || count($data['selected_ids']) > 500)) return false;
    foreach (($data['selected_ids'] ?? []) as $selectedId) if (!is_string($selectedId) && !is_int($selectedId)) return false;
    return true;
}
function tuya_backup_file($source, $directory, $limit = 5) {
    if (!is_file($source)) return true;
    if (!is_dir($directory) && !@mkdir($directory, 0777, true)) return false;
    if (!tuya_exec_chmod_0777($directory, true)) return false;
    $content = @file_get_contents($source);
    if (!is_string($content)) return false;
    $name = 'Tuya_Local_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.json';
    $path = $directory . DIRECTORY_SEPARATOR . $name;
    $handle = @fopen($path, 'xb');
    if ($handle === false) return false;
    $writeOk = true;
    $offset = 0;
    $length = strlen($content);
    while ($offset < $length) {
        $written = @fwrite($handle, substr($content, $offset));
        if ($written === false || $written === 0) { $writeOk = false; break; }
        $offset += $written;
    }
    if ($writeOk) $writeOk = @fflush($handle);
    @fclose($handle);
    if (!$writeOk || $offset !== $length) { @unlink($path); return false; }
    if (!tuya_exec_chmod_0777($path)) { @unlink($path); return false; }
    clearstatcache(true, $path);
    $backupMode = @fileperms($path);
    if (!is_file($path) || @filesize($path) !== $length || $backupMode === false || (($backupMode & 0777) !== 0777)) {
        @unlink($path);
        return false;
    }
    $files = glob($directory . '/Tuya_Local_*.json') ?: [];
    usort($files, static fn($a, $b) => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));
    foreach (array_slice($files, max(1, (int)$limit)) as $oldFile) @unlink($oldFile);
    return true;
}
function tuya_backup_redirect($status) {
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    $_SESSION['tuya_backup_status'] = (string)$status;
    if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();
    header('Location: Tuya_Local.php');
    exit;
}
function tuya_reply($data, $code = 200) {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function tuya_csrf_ok() {
    $sessionToken = (string)($_SESSION['vbot_csrf_token'] ?? '');
    $requestToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? ''));
    return strlen($sessionToken) >= 64 && $requestToken !== '' && hash_equals($sessionToken, $requestToken);
}
function tuya_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tuya_ssh_background($command) {
    global $ssh_host, $ssh_port, $ssh_user, $ssh_password;
    if (!function_exists('ssh2_connect')) return 'Thư viện SSH2 của PHP chưa được bật.';
    $connection = @ssh2_connect($ssh_host, intval($ssh_port));
    if (!$connection) return 'Không thể kết nối SSH tới ' . $ssh_host . ':' . $ssh_port;
    if (!@ssh2_auth_password($connection, $ssh_user, $ssh_password)) return 'Xác thực SSH không thành công với user ' . $ssh_user;
    $stream = @ssh2_exec($connection, 'setsid sh -c ' . escapeshellarg($command) . ' </dev/null >/dev/null 2>&1 & echo __VBOT_TUYA_STARTED__');
    if (!$stream) return 'Không thể khởi chạy lệnh qua SSH.';
    // Không dùng stream_get_contents(): tiến trình nền có thể giữ channel mở
    // và làm request PHP treo vô hạn. Chỉ đọc ACK ngắn với timeout giới hạn.
    stream_set_blocking($stream, false);
    $ack = '';
    $deadline = microtime(true) + 3.0;
    while (microtime(true) < $deadline && !feof($stream)) {
        $line = fgets($stream);
        if ($line !== false) { $ack .= $line; if (strpos($ack, '__VBOT_TUYA_STARTED__') !== false) break; }
        usleep(50000);
    }
    @fclose($stream);
    if (strpos((string)$ack, '__VBOT_TUYA_STARTED__') === false) return 'SSH không xác nhận đã khởi chạy tiến trình.';
    return true;
}

// Image endpoint keeps the QR PNG outside the web root.
if (($_GET['action'] ?? '') === 'qr') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    if (is_file($qrFile)) { header('Content-Type: image/png'); readfile($qrFile); }
    else http_response_code(404);
    exit;
}

// SSE: giữ một kết nối HTTP duy nhất trong suốt thời gian QR login/Python chạy.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'stream_ticket') {
    if (!tuya_csrf_ok()) tuya_reply(['ok'=>false, 'error'=>'CSRF token không hợp lệ.'], 403);
    $userCode = trim((string)($_POST['user_code'] ?? ''));
    $subnet = trim((string)($_POST['subnet'] ?? '192.168.14.0/24'));
    $relogin = (string)($_POST['relogin'] ?? '1') === '1';
    if ($relogin && !preg_match('/^[A-Za-z0-9]{4,64}$/', $userCode)) tuya_reply(['ok'=>false,'error'=>'Mã người dùng không hợp lệ.'], 400);
    if (!preg_match('/^[0-9.\/,\-]+$/', $subnet)) tuya_reply(['ok'=>false,'error'=>'Dải mạng không hợp lệ.'], 400);
    $ticket = bin2hex(random_bytes(32));
    $ticketPath = $runtime . '/stream-ticket-' . $ticket . '.json';
    $ticketError = '';
    if (!tuya_json_write($ticketPath, ['user_code'=>$userCode,'subnet'=>$subnet,'relogin'=>$relogin,'created_at'=>time()], $ticketError)) {
        tuya_reply(['ok'=>false,'error'=>'Không thể tạo phiên SSE an toàn: ' . $ticketError], 500);
    }
    tuya_reply(['ok'=>true,'ticket'=>$ticket]);
}
if (($_GET['action'] ?? '') === 'stream') {
    set_time_limit(0);
    $ticket = (string)($_GET['ticket'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/D', $ticket)) { http_response_code(400); exit; }
    $ticketPath = $runtime . '/stream-ticket-' . $ticket . '.json';
    $consumedTicketPath = $ticketPath . '.used';
    if (!@rename($ticketPath, $consumedTicketPath)) { http_response_code(410); exit; }
    $ticketData = tuya_json_read($consumedTicketPath, []);
    @unlink($consumedTicketPath);
    if (!is_array($ticketData) || (int)($ticketData['created_at'] ?? 0) < time() - 120) { http_response_code(410); exit; }
    $userCode = trim((string)($ticketData['user_code'] ?? ''));
    $subnet = trim((string)($ticketData['subnet'] ?? '192.168.14.0/24'));
    $relogin = ($ticketData['relogin'] ?? false) === true;
    $scanLockPath = $runtime . '/scan.lock';
    $scanLock = @fopen($scanLockPath, 'c+');
    if (is_resource($scanLock)) { tuya_exec_chmod_0777($scanLockPath); }
    $scanLockAcquired = is_resource($scanLock) && @flock($scanLock, LOCK_EX | LOCK_NB);
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    while (ob_get_level() > 0) @ob_end_flush();
    @ini_set('output_buffering', 'off'); @ini_set('zlib.output_compression', '0');
    // Ép reverse proxy flush SSE ngay, tránh giữ response nhỏ trong buffer.
    echo ': ' . str_repeat(' ', 4096) . "\n\n"; @flush();
    $send = function($payload) { echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"; @flush(); };
    if (!$scanLockAcquired) {
        if (is_resource($scanLock)) @fclose($scanLock);
        $send(['type'=>'error','message'=>'Đang có một phiên quét Tuya khác chạy. Hãy chờ phiên đó kết thúc rồi thử lại.']); exit;
    }
    $activeAccountId = '';
    if ($userCode !== '' && preg_match('/^[A-Za-z0-9]{4,64}$/', $userCode)) {
        $accountConfig = tuya_json_read($normalizedFile, ['schema_version'=>1, 'devices'=>[]]);
        $activeAccountId = tuya_upsert_account($accountConfig, $userCode);
        foreach (($accountConfig['devices'] ?? []) as &$accountDevice) unset($accountDevice['user_code']);
        unset($accountDevice);
        $accountWriteError = '';
        if (!tuya_json_write($normalizedFile, $accountConfig, $accountWriteError)) {
            @flock($scanLock, LOCK_UN); @fclose($scanLock);
            $send(['type'=>'error','message'=>'Không thể lưu mã người dùng Tuya Smart/Smart Life: ' . $accountWriteError]); exit;
        }
    }
    if ($relogin && !preg_match('/^[A-Za-z0-9]{4,64}$/', $userCode)) {
        echo 'data: ' . json_encode(['type'=>'error','message'=>'Mã người dùng không hợp lệ.'], JSON_UNESCAPED_UNICODE) . "\n\n"; @flush(); exit;
    }
    if (!preg_match('/^[0-9.\/,-]+$/', $subnet)) {
        echo 'data: ' . json_encode(['type'=>'error','message'=>'Dải mạng không hợp lệ.'], JSON_UNESCAPED_UNICODE) . "\n\n"; @flush(); exit;
    }
    $remoteRuntime = '/tmp/vbot_tuya_runtime';
    $remotePid = $remoteRuntime . '/tuya_devices.pid';
    $remoteQr = $remoteRuntime . '/tuya-login-qr-' . bin2hex(random_bytes(8)) . '.png';
    $localQrFile = $runtime . '/tuya-login-qr-current.png';
    @unlink($doneFile); @unlink($exitFile); @unlink($logFile); @unlink($scanLogFile);
    tuya_json_write($jobFile, ['started_at'=>date('c'), 'mode'=>$relogin?'qr_login':'refresh', 'subnet'=>$subnet, 'qr_file'=>$localQrFile]);
    $stopRemote = function() use ($remotePid) {
        global $ssh_host, $ssh_port, $ssh_user, $ssh_password;
        $c = @ssh2_connect($ssh_host, intval($ssh_port));
        if ($c && @ssh2_auth_password($c, $ssh_user, $ssh_password)) {
            $s = @ssh2_exec($c, 'if [ -f ' . escapeshellarg($remotePid) . ' ]; then p=$(cat ' . escapeshellarg($remotePid) . '); kill -TERM "$p" 2>/dev/null; rm -f ' . escapeshellarg($remotePid) . '; fi');
            if ($s) { stream_set_blocking($s, false); @fclose($s); }
        }
    };
    register_shutdown_function($stopRemote);
    $send(['type'=>'started','message'=>'Đã kết nối SSH, đang chạy tuya_devices.py dưới user pi...']);
    if (!function_exists('ssh2_connect')) { $send(['type'=>'error','message'=>'Thư viện SSH2 của PHP chưa được bật.']); exit; }
    $connection = @ssh2_connect($ssh_host, intval($ssh_port));
    if (!$connection || !@ssh2_auth_password($connection, $ssh_user, $ssh_password)) { $send(['type'=>'error','message'=>'Không thể xác thực SSH tới ' . $ssh_host]); exit; }
    $remoteLog = $remoteRuntime . '/tuya_devices.log';
    $remoteScanLog = $remoteRuntime . '/tinytuya_scan.log';
    $args = ($relogin ? '--user-code ' . escapeshellarg($userCode) . ' --relogin ' : '') . '--qr-png ' . escapeshellarg($remoteQr) . ' --json --scan ' . escapeshellarg($subnet);
    $watcher = 'set +e; mkdir -p ' . escapeshellarg($remoteRuntime) . '; : > ' . escapeshellarg($remoteLog) . '; rm -f ' . escapeshellarg($remoteQr) . '; cd ' . escapeshellarg($keyTool) . ' || exit 90; ' .
        'python3 -u tuya_devices.py ' . $args . ' >> ' . escapeshellarg($remoteLog) . ' 2>&1 & py=$!; echo "$py" > ' . escapeshellarg($remotePid) . '; offset=0; signature=""; qr_sent=0; last_ping=0; ' .
        'while kill -0 "$py" 2>/dev/null; do sig=$(stat -c "%Y:%s" ' . escapeshellarg($remoteLog) . ' 2>/dev/null); ' .
        'if [ -n "$sig" ] && [ "$sig" != "$signature" ]; then size=${sig#*:}; if [ "$size" -gt "$offset" ]; then printf "__VBOT_LOG_B64__"; tail -c +$((offset+1)) ' . escapeshellarg($remoteLog) . ' | base64 | tr -d "\\n"; printf "\\n"; offset=$size; fi; signature=$sig; fi; ' .
        'if [ "$qr_sent" -eq 0 ] && [ -s ' . escapeshellarg($remoteQr) . ' ]; then printf "__VBOT_QR_B64__"; base64 ' . escapeshellarg($remoteQr) . ' | tr -d "\\n"; printf "\\n"; qr_sent=1; fi; ' .
        'now=$(date +%s); if [ $((now-last_ping)) -ge 2 ]; then printf "__VBOT_HEARTBEAT__\\n"; last_ping=$now; fi; sleep 0.25; done; wait "$py"; rc=$?; ' .
        'python3 -u -m tinytuya scan >> ' . escapeshellarg($remoteScanLog) . ' 2>&1; cat ' . escapeshellarg($remoteScanLog) . ' >> ' . escapeshellarg($remoteLog) . ' 2>/dev/null; ' .
        'sig=$(stat -c "%Y:%s" ' . escapeshellarg($remoteLog) . ' 2>/dev/null); size=${sig#*:}; if [ -n "$size" ] && [ "$size" -gt "$offset" ]; then printf "__VBOT_LOG_B64__"; tail -c +$((offset+1)) ' . escapeshellarg($remoteLog) . ' | base64 | tr -d "\\n"; printf "\\n"; fi; rm -f ' . escapeshellarg($remotePid) . '; printf "__VBOT_DONE__%s\\n" "$rc"; exit "$rc"';
    $stream = @ssh2_exec($connection, 'sh -c ' . escapeshellarg($watcher));
    if (!$stream) { $send(['type'=>'error','message'=>'Không thể khởi chạy Python qua SSH.']); exit; }
    stream_set_blocking($stream, false);
    $send(['type'=>'started','message'=>'Python đã khởi chạy. Đang nhận nhật ký trực tiếp qua SSH và SSE...']);
    $lastHeartbeat = microtime(true); $captured = ''; $protocolBuffer = ''; $exitCode = 1; $finished = false;
    while (!connection_aborted()) {
        $chunk = @fread($stream, 8192);
        if (is_string($chunk) && $chunk !== '') {
            $protocolBuffer .= $chunk;
            while (($newline = strpos($protocolBuffer, "\n")) !== false) {
                $frame = substr($protocolBuffer, 0, $newline); $protocolBuffer = substr($protocolBuffer, $newline + 1);
                if (strpos($frame, '__VBOT_LOG_B64__') === 0) {
                    $delta = base64_decode(substr($frame, strlen('__VBOT_LOG_B64__')), true);
                    if (is_string($delta) && $delta !== '') { $captured .= $delta; $send(['type'=>'output','line'=>$delta]); }
                } elseif (strpos($frame, '__VBOT_QR_B64__') === 0) {
                    $qrData = base64_decode(substr($frame, strlen('__VBOT_QR_B64__')), true);
                    if (is_string($qrData) && $qrData !== '') { @file_put_contents($localQrFile, $qrData); @chmod($localQrFile, 0777); $send(['type'=>'qr','data'=>base64_encode($qrData)]); }
                } elseif (preg_match('/^__VBOT_DONE__(\d+)$/', $frame, $m)) { $exitCode = (int)$m[1]; $finished = true; }
            }
        }
        if ($finished || feof($stream)) break;
        if (microtime(true) - $lastHeartbeat >= 1) { echo ": heartbeat\n\n"; @flush(); $lastHeartbeat = microtime(true); }
        usleep(50000);
    }
    if (connection_aborted()) { $stopRemote(); @fclose($stream); exit; }
    @fclose($stream);
    @file_put_contents($logFile, $captured); @chmod($logFile, 0777); @file_put_contents($exitFile, (string)$exitCode); @chmod($exitFile, 0777); @touch($doneFile); @chmod($doneFile, 0777);
    $raw = null;
    // CLI prints a JSON array after its human-readable summary. Parse candidates
    // from the end because progress/log text may itself contain square brackets.
    if (preg_match_all('/(?m)^\s*\[\s*\n\s*\{.*?^\s*\]\s*$/s', $captured, $jsonMatches) && !empty($jsonMatches[0])) {
        for ($candidateIndex = count($jsonMatches[0]) - 1; $candidateIndex >= 0; $candidateIndex--) {
            $candidate = json_decode(trim($jsonMatches[0][$candidateIndex]), true);
            if (is_array($candidate)) { $raw = $candidate; break; }
        }
    }
    if (!is_array($raw) && preg_match('/Found\s+\d+\s+device\(s\):[^\r\n]*\R(.*)$/s', $captured, $jsonTail)) {
        $candidate = json_decode(trim($jsonTail[1]), true);
        if (is_array($candidate)) $raw = $candidate;
    }
    $savedDevices = [];
    if (is_array($raw)) {
        $writeErrors = [];
        $scan = tuya_json_read($keyTool . '/snapshot.json', []);
        $scanById = [];
        foreach (($scan['devices'] ?? []) as $scanDevice) if (!empty($scanDevice['id'])) $scanById[(string)$scanDevice['id']] = $scanDevice;
        // The network scan snapshot can lag the SSH output; use TinyTuya's
        // live scan log to recover an IP when the cloud device record has "-".
        $scanText = (string)@file_get_contents($remoteScanLog);
        if (preg_match_all('/Address\s*=\s*([0-9.]+)/', $scanText, $addressMatches) && preg_match_all('/Device ID\s*=\s*([A-Za-z0-9_-]+)/', $scanText, $idMatches)) {
            foreach ($idMatches[1] as $scanIndex=>$scanId) if (isset($addressMatches[1][$scanIndex])) $scanById[(string)$scanId] = ['ip'=>$addressMatches[1][$scanIndex]];
        }
        $old = tuya_json_read($normalizedFile, ['schema_version'=>1, 'devices'=>[]]); $oldById = [];
        foreach (($old['devices'] ?? []) as $d) if (!empty($d['id'])) $oldById[(string)$d['id']] = $d;
        foreach ($raw as $d) {
            $id = (string)($d['id'] ?? $d['devId'] ?? ''); if ($id === '') continue;
            $previous = $oldById[$id] ?? []; $lan = $scanById[$id] ?? [];
            $ip = $d['ip'] ?? null; if (!$ip || $ip === '-') $ip = $lan['ip'] ?? null;
            $version = $d['version'] ?? $d['ver'] ?? ($lan['ver'] ?? null);
            $localKey = $d['local_key'] ?? $d['key'] ?? null;
            // Tuya cloud specs use semantic codes (e.g. switch_1), while
            // TinyTuya.set_value() requires the numeric DP id. Resolve that
            // mapping from local_strategy.status_code; never infer it from the
            // code suffix because Tuya codes and DP ids are not guaranteed to match.
            $dpsIdByCode = [];
            foreach (($d['local_strategy'] ?? []) as $dpId=>$strategy) {
                $statusCode = is_array($strategy) ? (string)($strategy['status_code'] ?? '') : '';
                if ($statusCode !== '') $dpsIdByCode[$statusCode] = (string)$dpId;
            }
            $dps = [];
            foreach (['status','function','status_range'] as $group) foreach (($d[$group] ?? []) as $entryKey=>$entry) {
                $key = (string)$entryKey;
                $dps[$key] = $dps[$key] ?? ['dps_id'=>$dpsIdByCode[$key] ?? null,'status'=>null,'function'=>null,'status_range'=>null,'enabled'=>false,'commands'=>['on'=>'','off'=>'']];
                if (isset($dpsIdByCode[$key])) $dps[$key]['dps_id'] = $dpsIdByCode[$key];
                $dps[$key][$group] = $entry;
            }
            $tuyaData = is_array($previous['tuya'] ?? null) ? $previous['tuya'] : [];
            $tuyaData['uuid'] = $d['uuid'] ?? ($tuyaData['uuid'] ?? null);
            $tuyaData['product_id'] = $d['product_id'] ?? $d['productKey'] ?? ($tuyaData['product_id'] ?? null);
            $tuyaData['category'] = $d['category'] ?? ($tuyaData['category'] ?? null);
            foreach (['status','function','status_range','local_strategy'] as $metaField) $tuyaData[$metaField] = $d[$metaField] ?? ($tuyaData[$metaField] ?? []);
            // Existing DPS entries contain VBot-specific phrases and enable flags.
            // Never replace them during a scan; initialize only for a new device.
            if (!isset($tuyaData['dps']) || !is_array($tuyaData['dps']) || !$tuyaData['dps']) $tuyaData['dps'] = $dps;
            foreach ($tuyaData['dps'] as $dpsCode=>&$dpsEntry) {
                if (isset($dpsIdByCode[(string)$dpsCode])) $dpsEntry['dps_id'] = $dpsIdByCode[(string)$dpsCode];
                elseif (!isset($dpsEntry['dps_id']) && ctype_digit((string)$dpsCode)) $dpsEntry['dps_id'] = (string)$dpsCode;
            }
            unset($dpsEntry);
            $network = is_array($previous['network'] ?? null) ? $previous['network'] : [];
            $network['ip'] = $ip; $network['protocol_version'] = $version; $network['online'] = (bool)($d['online'] ?? ($network['online'] ?? false));
            $credentials = is_array($previous['credentials'] ?? null) ? $previous['credentials'] : [];
            $credentials['local_key'] = $localKey;
            $device = $previous;
            $device['id'] = $id; $device['device_id'] = $id;
            $device['account_id'] = $activeAccountId !== '' ? $activeAccountId : (string)($previous['account_id'] ?? '');
            $device['app_name'] = (string)($d['name'] ?? $id);
            $device['display_name'] = $previous['display_name'] ?? ($d['name'] ?? $id);
            $device['enabled'] = $previous['enabled'] ?? false;
            $device['ip'] = $ip; $device['version'] = $version !== null ? (float)$version : null;
            $device['local_key'] = $localKey; $device['network'] = $network; $device['credentials'] = $credentials;
            $device['tuya'] = $tuyaData; $device['commands'] = $previous['commands'] ?? [];
            $savedDevices[] = $device;
        }
        $rawError = ''; $scanError = ''; $normalizedError = ''; $jobError = '';
        $rawSaved = tuya_json_write($rawFile, ['updated_at'=>date('c'),'devices'=>$raw], $rawError);
        $scanSaved = tuya_json_write($scanFile, ['updated_at'=>date('c'),'snapshot'=>$scan], $scanError);
        $oldSelected = $old['selected_ids'] ?? [];
        foreach ($savedDevices as &$savedDevice) unset($savedDevice['user_code']);
        unset($savedDevice);
        $accounts = is_array($old['accounts'] ?? null) ? $old['accounts'] : [];
        unset($old['user_code']);
        $normalized = ['schema_version'=>1,'updated_at'=>date('c'),'source'=>'tuya-local-key + tinytuya','accounts'=>$accounts,'active_account_id'=>$activeAccountId !== '' ? $activeAccountId : (string)($old['active_account_id'] ?? ''),'selected_ids'=>$oldSelected,'devices'=>$savedDevices];
        $normalizedSaved = tuya_json_write($normalizedFile, $normalized, $normalizedError);
        $jobMeta['finalized_at'] = date('c'); $jobSaved = tuya_json_write($jobFile, $jobMeta, $jobError);
        foreach ([['raw JSON',$rawSaved,$rawError],['scan JSON',$scanSaved,$scanError],['cấu hình thiết bị',$normalizedSaved,$normalizedError],['trạng thái tiến trình',$jobSaved,$jobError]] as $writeResult) {
            if (!$writeResult[1]) $writeErrors[] = $writeResult[0] . ': ' . $writeResult[2];
        }
        @chmod($localQrFile, 0777);
        @chmod($logFile, 0777); @chmod($exitFile, 0777); @chmod($doneFile, 0777);
        if (is_resource($scanLock)) { @flock($scanLock, LOCK_UN); @fclose($scanLock); }
        $allSaved = $rawSaved && $scanSaved && $normalizedSaved && $jobSaved;
    } else {
        $allSaved = false;
        $writeErrors = ['Không trích xuất được JSON thiết bị từ kết quả Python.'];
        if (is_resource($scanLock)) { @flock($scanLock, LOCK_UN); @fclose($scanLock); }
    }
    $send(['type'=>'done','exit_code'=>$exitCode,'devices'=>$savedDevices,'saved'=>$allSaved,'save_error'=>implode("\n", $writeErrors)]); exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === 'csrf') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    if (empty($_SESSION['vbot_csrf_token']) || !is_string($_SESSION['vbot_csrf_token']) || strlen($_SESSION['vbot_csrf_token']) < 64) {
        $_SESSION['vbot_csrf_token'] = bin2hex(random_bytes(32));
    }
    tuya_reply(['ok'=>true, 'csrf_token'=>$_SESSION['vbot_csrf_token']]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !tuya_csrf_ok()) tuya_reply(['ok' => false, 'error' => 'CSRF token không hợp lệ.'], 403);

if ($action === 'download' || $action === 'preview') {
    $target = (string)($_GET['target'] ?? 'backup');
    if ($target === 'current' && in_array($action, ['download','preview'], true)) $downloadPath = $normalizedFile;
    else {
        $backupName = basename((string)($_GET['backup'] ?? ''));
        if (!preg_match('/^Tuya_Local_[A-Za-z0-9_-]+\.json$/', $backupName)) { http_response_code(400); exit('Tên bản sao lưu không hợp lệ.'); }
        $downloadPath = $tuyaBackupDir . DIRECTORY_SEPARATOR . $backupName;
    }
    if (!is_file($downloadPath) || !is_readable($downloadPath)) { http_response_code(404); exit('Không tìm thấy file cần tải.'); }
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: ' . ($action === 'preview' ? 'inline' : 'attachment') . '; filename="' . basename($downloadPath) . '"');
    header('Content-Length: ' . (string)filesize($downloadPath));
    header('Cache-Control: no-store');
    readfile($downloadPath);
    exit;
}

if ($action === 'backup_import') {
    if (!isset($_FILES['tuya_import_file']) || ($_FILES['tuya_import_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($_FILES['tuya_import_file']['size'] ?? 0) > 10485760) tuya_backup_redirect('import_invalid');
    $importRaw = @file_get_contents($_FILES['tuya_import_file']['tmp_name']);
    $importData = is_string($importRaw) ? json_decode($importRaw, true) : null;
    if (!tuya_valid_bundle($importData)) tuya_backup_redirect('import_invalid');
    $mode = (string)($_POST['import_mode'] ?? 'merge');
    if (!in_array($mode, ['merge', 'overwrite'], true)) tuya_backup_redirect('import_invalid');
    $current = tuya_json_read($normalizedFile, ['schema_version'=>1, 'devices'=>[], 'accounts'=>[], 'selected_ids'=>[]]);
    if ($mode === 'merge') {
        $mergedById = [];
        foreach (($current['devices'] ?? []) as $device) {
            $id = (string)($device['id'] ?? $device['device_id'] ?? '');
            if ($id !== '') $mergedById[$id] = $device;
        }
        foreach ($importData['devices'] as $device) {
            $id = (string)($device['id'] ?? $device['device_id'] ?? '');
            $mergedById[$id] = $device;
        }
        if (count($mergedById) > 500) tuya_backup_redirect('import_invalid');
        $current['devices'] = array_values($mergedById);
        $current['accounts'] = array_replace(is_array($current['accounts'] ?? null) ? $current['accounts'] : [], is_array($importData['accounts'] ?? null) ? $importData['accounts'] : []);
        $current['selected_ids'] = array_values(array_unique(array_map('strval', array_merge($current['selected_ids'] ?? [], $importData['selected_ids'] ?? []))));
        if (!empty($importData['active_account_id']) && isset($current['accounts'][$importData['active_account_id']])) $current['active_account_id'] = $importData['active_account_id'];
        $importData = $current;
    }
    $importData['schema_version'] = 1;
    $importData['updated_at'] = date('c');
    if (!tuya_json_write($normalizedFile, $importData)) tuya_backup_redirect('backup_error');
    tuya_backup_redirect('imported');
}

if ($action === 'restore_backup') {
    $backupName = basename((string)($_POST['backup_file'] ?? ''));
    $backupPath = $tuyaBackupDir . DIRECTORY_SEPARATOR . $backupName;
    $restoreData = preg_match('/^Tuya_Local_[A-Za-z0-9_-]+\.json$/', $backupName) && is_file($backupPath)
        ? json_decode((string)@file_get_contents($backupPath), true) : null;
    if (!tuya_valid_bundle($restoreData)) tuya_backup_redirect('import_invalid');
    if (!tuya_json_write($normalizedFile, $restoreData)) tuya_backup_redirect('backup_error');
    tuya_backup_redirect('restored');
}

if ($action === 'delete_backup') {
    $backupName = basename((string)($_POST['backup_file'] ?? ''));
    $backupPath = $tuyaBackupDir . DIRECTORY_SEPARATOR . $backupName;
    if (!preg_match('/^Tuya_Local_[A-Za-z0-9_-]+\.json$/', $backupName) || !is_file($backupPath)) tuya_backup_redirect('invalid');
    tuya_backup_redirect(@unlink($backupPath) ? 'backup_deleted' : 'backup_error');
}

if ($action === 'clear_scan') {
    $config = tuya_json_read($normalizedFile, ['schema_version'=>1, 'devices'=>[], 'selected_ids'=>[]]);
    $selected = array_values(array_unique(array_map('strval', $config['selected_ids'] ?? [])));
    $config['devices'] = array_values(array_filter($config['devices'] ?? [], function($device) use ($selected) {
        return in_array((string)($device['id'] ?? $device['device_id'] ?? ''), $selected, true);
    }));
    $config['selected_ids'] = $selected;
    $config['updated_at'] = date('c');
    $savedConfig = tuya_json_write($normalizedFile, $config);
    $savedRaw = tuya_json_write($rawFile, ['updated_at'=>date('c'), 'devices'=>[]]);
    $savedScan = tuya_json_write($scanFile, ['updated_at'=>date('c'), 'snapshot'=>[]]);
    tuya_reply(($savedConfig && $savedRaw && $savedScan) ? ['ok'=>true] : ['ok'=>false, 'error'=>'Không thể xóa hết dữ liệu scan. Kiểm tra quyền ghi các file JSON.'], ($savedConfig && $savedRaw && $savedScan) ? 200 : 500);
}

if ($action === 'delete_device') {
    $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_POST['id'] ?? ''));
    if ($id === '') tuya_reply(['ok'=>false, 'error'=>'ID thiết bị không hợp lệ.'], 400);
    $config = tuya_json_read($normalizedFile, ['schema_version'=>1, 'devices'=>[], 'selected_ids'=>[]]);
    $config['devices'] = array_values(array_filter($config['devices'] ?? [], function($device) use ($id) {
        return (string)($device['id'] ?? $device['device_id'] ?? '') !== $id;
    }));
    $config['selected_ids'] = array_values(array_filter($config['selected_ids'] ?? [], function($selectedId) use ($id) { return (string)$selectedId !== $id; }));
    $config['updated_at'] = date('c');
    $saved = tuya_json_write($normalizedFile, $config);
    tuya_reply($saved ? ['ok'=>true] : ['ok'=>false, 'error'=>'Không thể xóa thiết bị khỏi file cấu hình.'], $saved ? 200 : 500);
}

if ($action === 'start') {
    $userCode = trim((string)($_POST['user_code'] ?? ''));
    $subnet = trim((string)($_POST['subnet'] ?? '192.168.14.0/24'));
    if (!preg_match('/^[A-Za-z0-9]{4,64}$/', $userCode)) tuya_reply(['ok' => false, 'error' => 'Mã người dùng không hợp lệ.']);
    if (!preg_match('/^[0-9.\/,-]+$/', $subnet)) tuya_reply(['ok' => false, 'error' => 'Dải mạng không hợp lệ.']);
    @unlink($doneFile); @unlink($exitFile); @unlink($logFile); @unlink($scanLogFile);
    // Mỗi lần login dùng một file QR mới, tránh cả cache trình duyệt lẫn file cũ.
    $qrFile = $base . '/.tuya-login-qr-' . bin2hex(random_bytes(8)) . '.png';
    @file_put_contents($logFile, 'Đang khởi động tuya-local-key...\n');
    tuya_json_write($jobFile, ['started_at' => date('c'), 'mode' => 'qr_login', 'subnet' => $subnet, 'qr_file' => $qrFile]);
    $cmd = 'cd ' . escapeshellarg($keyTool) . ' && ' .
        'python3 tuya_devices.py --user-code ' . escapeshellarg($userCode) . ' --relogin --qr-png ' . escapeshellarg($qrFile) . ' --json --scan ' . escapeshellarg($subnet) .
        ' > ' . escapeshellarg($logFile) . ' 2>&1; python3 -m tinytuya scan > ' . escapeshellarg($scanLogFile) . ' 2>&1; echo $? > ' . escapeshellarg($exitFile) . '; touch ' . escapeshellarg($doneFile);
    $sshResult = tuya_ssh_background($cmd);
    if ($sshResult !== true) tuya_reply(['ok' => false, 'error' => $sshResult], 500);
    tuya_reply(['ok' => true]);
}

if ($action === 'refresh') {
    $subnet = trim((string)($_POST['subnet'] ?? '192.168.14.0/24'));
    if (!preg_match('/^[0-9.\/,-]+$/', $subnet)) tuya_reply(['ok' => false, 'error' => 'Dải mạng không hợp lệ.']);
    @unlink($doneFile); @unlink($exitFile); @unlink($logFile); @unlink($scanLogFile); @file_put_contents($logFile, 'Đang lấy lại danh sách thiết bị...\n');
    $cmd = 'cd ' . escapeshellarg($keyTool) . ' && python3 tuya_devices.py --json --scan ' . escapeshellarg($subnet) .
        ' > ' . escapeshellarg($logFile) . ' 2>&1; python3 -m tinytuya scan > ' . escapeshellarg($scanLogFile) . ' 2>&1; echo $? > ' . escapeshellarg($exitFile) . '; touch ' . escapeshellarg($doneFile);
    $sshResult = tuya_ssh_background($cmd);
    if ($sshResult !== true) tuya_reply(['ok' => false, 'error' => $sshResult], 500);
    tuya_json_write($jobFile, ['started_at' => date('c'), 'mode' => 'refresh', 'subnet' => $subnet]);
    tuya_reply(['ok' => true]);
}

if ($action === 'save_user_code') {
    $userCode = trim((string)($_POST['user_code'] ?? ''));
    if ($userCode !== '' && !preg_match('/^[A-Za-z0-9]{4,64}$/', $userCode)) tuya_reply(['ok'=>false, 'error'=>'Mã người dùng không hợp lệ.'], 400);
    $config = tuya_json_read($normalizedFile, ['schema_version'=>1, 'devices'=>[]]);
    if (!empty($config['user_code'])) $legacyAccountId = tuya_upsert_account($config, $config['user_code']);
    foreach (($config['devices'] ?? []) as &$device) {
        if (!empty($device['user_code'])) $device['account_id'] = tuya_upsert_account($config, $device['user_code']);
        elseif (empty($device['account_id']) && !empty($legacyAccountId)) $device['account_id'] = $legacyAccountId;
        unset($device['user_code']);
    }
    unset($device);
    $accountId = $userCode !== '' ? tuya_upsert_account($config, $userCode) : (string)($config['active_account_id'] ?? '');
    $saved = tuya_json_write($normalizedFile, $config);
    tuya_reply($saved ? ['ok'=>true, 'account_id'=>$accountId, 'accounts'=>$config['accounts'] ?? []] : ['ok'=>false, 'error'=>'Không thể lưu mã người dùng tài khoản.'], $saved ? 200 : 500);
}

if ($action === 'save') {
    $incoming = json_decode((string)($_POST['devices'] ?? ''), true);
    if (!is_array($incoming)) tuya_reply(['ok' => false, 'error' => 'Dữ liệu thiết bị không hợp lệ.']);
    $existing = tuya_json_read($normalizedFile, ['schema_version' => 1, 'devices' => []]);
    $legacyAccountCode = trim((string)($_POST['account_user_code'] ?? $existing['user_code'] ?? ''));
    if ($legacyAccountCode !== '') $legacyAccountId = tuya_upsert_account($existing, $legacyAccountCode);
        foreach (($existing['devices'] ?? []) as &$existingDevice) {
        $legacyTts = trim((string)($existingDevice['tts_response'] ?? ''));
        if ($legacyTts !== '' && isset($existingDevice['tuya']['dps']) && is_array($existingDevice['tuya']['dps'])) {
            foreach ($existingDevice['tuya']['dps'] as &$legacyEntry) if (empty($legacyEntry['tts_response'])) $legacyEntry['tts_response'] = ['on'=>$legacyTts, 'off'=>$legacyTts];
            unset($legacyEntry);
        }
        unset($existingDevice['tts_response']);
        if (!empty($existingDevice['user_code'])) $existingDevice['account_id'] = tuya_upsert_account($existing, $existingDevice['user_code']);
        elseif (empty($existingDevice['account_id']) && !empty($legacyAccountId)) $existingDevice['account_id'] = $legacyAccountId;
        unset($existingDevice['user_code']);
    }
    unset($existingDevice);
    $accounts = is_array($existing['accounts'] ?? null) ? $existing['accounts'] : [];
    $activeAccountId = (string)($existing['active_account_id'] ?? ($legacyAccountId ?? ''));
    $byId = [];
    foreach (($existing['devices'] ?? []) as $d) if (!empty($d['id'])) $byId[(string)$d['id']] = $d;
    $incomingIds = [];
    $oldSelectedIds = $existing['selected_ids'] ?? array_keys($byId);
    $protected = array_fill_keys($oldSelectedIds, true);
    $selectedIds = [];
    foreach ($incoming as $item) {
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($item['id'] ?? ''));
        if ($id === '') continue;
        $incomingIds[$id] = true;
        foreach (($item['tuya']['dps'] ?? []) as $entry) {
            $tts = $entry['tts_response'] ?? null;
            if ($tts !== null && !(is_string($tts) && strlen($tts) <= 4000) && !(is_array($tts) && count($tts) <= 2 && array_diff(array_keys($tts), ['on','off']) === [] && (!isset($tts['on']) || (is_string($tts['on']) && strlen($tts['on']) <= 4000)) && (!isset($tts['off']) || (is_string($tts['off']) && strlen($tts['off']) <= 4000)))) {
                tuya_reply(['ok'=>false, 'error'=>'Câu phản hồi bật/tắt của từng Entry không hợp lệ hoặc vượt quá 1000 ký tự.'], 400);
            }
        }
        if (empty($item['enabled'])) {
            if (isset($byId[$id])) {
                $freshKey = (string)($item['local_key'] ?? $item['credentials']['local_key'] ?? '');
                if ($freshKey !== '') { $byId[$id]['local_key'] = $freshKey; $byId[$id]['credentials']['local_key'] = $freshKey; }
                $freshIp = $item['ip'] ?? $item['network']['ip'] ?? null;
                if (!empty($freshIp) && $freshIp !== '-') { $byId[$id]['ip'] = $freshIp; $byId[$id]['network']['ip'] = $freshIp; }
                $freshVersion = $item['version'] ?? $item['network']['protocol_version'] ?? null;
                if ($freshVersion !== null) { $byId[$id]['version'] = $freshVersion; $byId[$id]['network']['protocol_version'] = $freshVersion; }
                $freshAccountId = (string)($item['account_id'] ?? '');
                if ($freshAccountId !== '' && isset($accounts[$freshAccountId])) $byId[$id]['account_id'] = $freshAccountId;
                unset($byId[$id]['user_code']);
                $byId[$id]['last_scan_at'] = date('c');
            }
            continue;
        }
        $selectedIds[] = $id;
        if (!isset($byId[$id])) $byId[$id] = $item;
        $byId[$id]['display_name'] = trim((string)($item['display_name'] ?? $byId[$id]['display_name'] ?? ''));
        $byId[$id]['enabled'] = !empty($item['enabled']);
        $freshAccountId = (string)($item['account_id'] ?? '');
        if ($freshAccountId !== '' && isset($accounts[$freshAccountId])) $byId[$id]['account_id'] = $freshAccountId;
        unset($byId[$id]['user_code']);
        $byId[$id]['ip'] = trim((string)($item['ip'] ?? $item['network']['ip'] ?? $byId[$id]['ip'] ?? ''));
        if (!isset($byId[$id]['network']) || !is_array($byId[$id]['network'])) $byId[$id]['network'] = [];
        $byId[$id]['network']['ip'] = $byId[$id]['ip'];
        $byId[$id]['commands'] = is_array($item['commands'] ?? null) ? array_values($item['commands']) : ($byId[$id]['commands'] ?? []);
        if (isset($item['local_key'])) {
            $byId[$id]['local_key'] = (string)$item['local_key'];
            $byId[$id]['credentials']['local_key'] = (string)$item['local_key'];
        } elseif (isset($item['credentials']['local_key'])) {
            $byId[$id]['credentials']['local_key'] = (string)$item['credentials']['local_key'];
            $byId[$id]['local_key'] = (string)$item['credentials']['local_key'];
        }
        foreach (['status', 'function', 'status_range', 'dps'] as $tuyaField) {
            if (isset($item['tuya'][$tuyaField]) && is_array($item['tuya'][$tuyaField])) {
                $byId[$id]['tuya'][$tuyaField] = $item['tuya'][$tuyaField];
            }
        }
        foreach (($byId[$id]['tuya']['dps'] ?? []) as $dpsKey=>$entry) {
            foreach (['on','off'] as $direction) {
                $value = $entry['commands'][$direction] ?? [];
                if (is_string($value)) $value = preg_split('/\r\n|\r|\n/', $value);
                $byId[$id]['tuya']['dps'][$dpsKey]['commands'][$direction] = array_values(array_filter(array_map('trim', is_array($value) ? $value : []), 'strlen'));
            }
        }
        foreach (($byId[$id]['tuya']['dps'] ?? []) as $dpsKey=>$entry) {
            $byId[$id]['tuya']['dps'][$dpsKey]['enabled'] = !empty($entry['enabled']);
        }
    }
    foreach ($incomingIds as $id=>$_) if (isset($byId[$id]) && empty($protected[$id]) && !in_array($id, $selectedIds, true)) unset($byId[$id]);
    $existing['devices'] = array_values($byId); $existing['updated_at'] = date('c'); $existing['selected_ids'] = array_values(array_unique(array_merge(array_intersect($oldSelectedIds, array_keys($byId)), $selectedIds)));
    $existing['accounts'] = $accounts;
    if ($activeAccountId !== '') $existing['active_account_id'] = $activeAccountId;
    unset($existing['user_code']);
    $saveError = '';
    $saved = tuya_json_write($normalizedFile, $existing, $saveError);
    tuya_reply($saved ? ['ok'=>true,'message'=>'Đã lưu cấu hình thiết bị.'] : ['ok'=>false,'error'=>'Không thể lưu cấu hình: ' . ($saveError !== '' ? $saveError : 'lỗi ghi file không xác định.')], $saved ? 200 : 500);
}

// When a CLI job finishes, parse its final JSON array and create the stable schema.
if (is_file($doneFile) && is_file($logFile) && empty($jobMeta['finalized_at'])) {
    $log = (string)@file_get_contents($logFile); $raw = null;
    $start = strrpos($log, "[\n");
    if ($start !== false) $raw = json_decode(substr($log, $start), true);
    if (!is_array($raw)) { $start = strpos($log, '['); if ($start !== false) $raw = json_decode(substr($log, $start), true); }
    if (is_array($raw)) {
        tuya_json_write($rawFile, ['updated_at' => date('c'), 'devices' => $raw]);
        $scan = tuya_json_read($keyTool . '/snapshot.json', []); tuya_json_write($scanFile, ['updated_at' => date('c'), 'snapshot' => $scan]);
        $scanById = [];
        foreach (($scan['devices'] ?? []) as $scanDevice) if (!empty($scanDevice['id'])) $scanById[(string)$scanDevice['id']] = $scanDevice;
        $old = tuya_json_read($normalizedFile, ['schema_version' => 1, 'devices' => []]); $oldById = [];
        foreach ($old['devices'] as $d) if (!empty($d['id'])) $oldById[$d['id']] = $d;
        $devices = [];
        foreach ($raw as $d) {
            $id = (string)($d['id'] ?? $d['devId'] ?? ''); if ($id === '') continue;
            $previous = $oldById[$id] ?? [];
            $lan = $scanById[$id] ?? [];
            $ip = $d['ip'] ?? null;
            if (!$ip || $ip === '-') $ip = $lan['ip'] ?? null;
            $version = $d['version'] ?? $d['ver'] ?? ($lan['ver'] ?? null);
            $localKey = $d['local_key'] ?? $d['key'] ?? null;
            $dpsIdByCode = [];
            foreach (($d['local_strategy'] ?? []) as $dpId=>$strategy) {
                $statusCode = is_array($strategy) ? (string)($strategy['status_code'] ?? '') : '';
                if ($statusCode !== '') $dpsIdByCode[$statusCode] = (string)$dpId;
            }
            $tuya = is_array($previous['tuya'] ?? null) ? $previous['tuya'] : [];
            foreach (['status','function','status_range','local_strategy'] as $field) $tuya[$field] = $d[$field] ?? ($tuya[$field] ?? []);
            $tuya['uuid'] = $d['uuid'] ?? ($tuya['uuid'] ?? null);
            $tuya['product_id'] = $d['product_id'] ?? $d['productKey'] ?? ($tuya['product_id'] ?? null);
            $tuya['category'] = $d['category'] ?? ($tuya['category'] ?? null);
            if (!isset($tuya['dps']) || !is_array($tuya['dps']) || !$tuya['dps']) {
                $tuya['dps'] = [];
                foreach (['status','function','status_range'] as $group) foreach (($d[$group] ?? []) as $code=>$spec) {
                    $code = (string)$code;
                    $tuya['dps'][$code] = $tuya['dps'][$code] ?? ['dps_id'=>$dpsIdByCode[$code] ?? null,'status'=>null,'function'=>null,'status_range'=>null,'enabled'=>false,'commands'=>['on'=>[],'off'=>[]]];
                    $tuya['dps'][$code][$group] = $spec;
                }
            }
            foreach ($tuya['dps'] as $code=>&$entry) {
                if (isset($dpsIdByCode[(string)$code])) $entry['dps_id'] = $dpsIdByCode[(string)$code];
                elseif (!isset($entry['dps_id']) && ctype_digit((string)$code)) $entry['dps_id'] = (string)$code;
            }
            unset($entry);
            $devices[] = ['id'=>$id, 'device_id'=>$id, 'display_name'=>$previous['display_name'] ?? ($d['name'] ?? $id), 'enabled'=>$previous['enabled'] ?? true,
                'ip'=>$ip, 'version'=>$version !== null ? (float)$version : null,
                'local_key'=>$localKey,
                'network'=>['ip'=>$ip, 'protocol_version'=>$version, 'online'=>(bool)($d['online'] ?? false)],
                'credentials'=>['local_key'=>$localKey], 'tuya'=>$tuya, 'commands'=>$previous['commands'] ?? []];
        }
        $normalized = $old;
        $normalized['schema_version'] = 1; $normalized['updated_at'] = date('c'); $normalized['source'] = 'tuya-local-key + tinytuya'; $normalized['devices'] = $devices;
        tuya_json_write($normalizedFile, $normalized);
        $jobMeta['finalized_at'] = date('c');
        tuya_json_write($jobFile, $jobMeta);
    }
}

if ($action === 'status') {
    $job = tuya_json_read($jobFile, []);
    $done = is_file($doneFile) || !empty($job['finalized_at']);
    $exitCode = is_file($exitFile) ? trim((string)@file_get_contents($exitFile)) : null;
    $log = is_file($logFile) ? (string)@file_get_contents($logFile) : '';
    $devices = tuya_json_read($normalizedFile, ['devices' => []]);
    $qrReady = is_file($qrFile) && @filesize($qrFile) > 0;
    $qrData = $qrReady ? base64_encode((string)@file_get_contents($qrFile)) : '';
    tuya_reply(['ok' => true, 'running' => !$done && !empty($job), 'done' => $done, 'job' => $job,
        'qr_ready' => $qrReady, 'qr_mtime' => $qrReady ? @filemtime($qrFile) : 0, 'qr_data' => $qrData,
        'exit_code' => $exitCode, 'log' => substr($log, -4000), 'devices' => $devices['devices'] ?? []]);
}

$config = tuya_json_read($normalizedFile, ['schema_version'=>1, 'devices'=>[]]);
$accountMigrationNeeded = false;
if (!empty($config['user_code'])) {
    $legacyAccountId = tuya_upsert_account($config, $config['user_code']);
    $accountMigrationNeeded = true;
}
foreach (($config['devices'] ?? []) as &$legacyDevice) {
    if (!empty($legacyDevice['user_code'])) {
        $legacyDevice['account_id'] = tuya_upsert_account($config, $legacyDevice['user_code']);
        $accountMigrationNeeded = true;
    } elseif (empty($legacyDevice['account_id']) && !empty($legacyAccountId)) {
        $legacyDevice['account_id'] = $legacyAccountId;
        $accountMigrationNeeded = true;
    }
    if (array_key_exists('user_code', $legacyDevice)) { unset($legacyDevice['user_code']); $accountMigrationNeeded = true; }
}
unset($legacyDevice);
unset($config['user_code']);
if ($accountMigrationNeeded) { $config['updated_at'] = date('c'); tuya_json_write($normalizedFile, $config); }
$config['accounts'] = is_array($config['accounts'] ?? null) ? $config['accounts'] : [];
$activeAccountUserCode = (string)($config['accounts'][$config['active_account_id'] ?? '']['user_code'] ?? '');
if ($activeAccountUserCode === '' && $config['accounts']) {
    $lastAccount = end($config['accounts']);
    $activeAccountUserCode = (string)($lastAccount['user_code'] ?? '');
}
$rawScanCache = tuya_json_read($rawFile, ['devices'=>[]]);
$lanScanCache = tuya_json_read($scanFile, ['snapshot'=>[]]);
$hasTemporaryScanData = !empty($rawScanCache['devices']) || !empty($lanScanCache['snapshot']);
// Configuration.php may close the session on GET requests. Reopen it before
// html_head.php embeds the CSRF token so the page token and POST cookie match.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['vbot_csrf_token']) || !is_string($_SESSION['vbot_csrf_token']) || strlen($_SESSION['vbot_csrf_token']) < 64) {
    $_SESSION['vbot_csrf_token'] = bin2hex(random_bytes(32));
}
$tuyaBackupStatus = (string)($_SESSION['tuya_backup_status'] ?? '');
unset($_SESSION['tuya_backup_status']);
$tuyaBackupFiles = glob($tuyaBackupDir . '/Tuya_Local_*.json') ?: [];
usort($tuyaBackupFiles, static fn($a, $b) => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));
foreach (array_slice($tuyaBackupFiles, $tuyaBackupLimit) as $expiredBackup) @unlink($expiredBackup);
$tuyaBackupFiles = array_slice($tuyaBackupFiles, 0, $tuyaBackupLimit);
$tuyaBackupMessages = [
    'imported'=>'Đã nhập dữ liệu Tuya và tự sao lưu cấu hình trước đó.',
    'restored'=>'Đã khôi phục bản sao lưu; cấu hình trước khi khôi phục cũng đã được sao lưu.',
    'backup_deleted'=>'Đã xóa bản sao lưu Tuya Local.',
    'import_invalid'=>'File JSON không đúng cấu trúc Tuya Local hoặc vượt quá giới hạn 10 MB / 500 thiết bị.',
    'backup_error'=>'Không thể ghi cấu hình hoặc tạo bản sao lưu. Hãy kiểm tra quyền ghi thư mục backup.',
    'invalid'=>'Thao tác bản sao lưu không hợp lệ.',
];
?>
<!DOCTYPE html><html lang="vi"><?php include 'html_head.php'; ?><head><link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css?v=<?php echo $Cache_UI_Ver; ?>"></head><body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main"><div class="pagetitle"><h1>Cấu Hình Tuya Smart/Smart Life -> Local Tuya</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li><li class="breadcrumb-item"> Local Tuya</li></ol></nav></div>
<?php if (isset($tuyaBackupMessages[$tuyaBackupStatus])): ?><div class="alert alert-<?php echo in_array($tuyaBackupStatus, ['imported','restored','backup_deleted'], true) ? 'success' : 'danger'; ?>"><?php echo tuya_h($tuyaBackupMessages[$tuyaBackupStatus]); ?></div><?php endif; ?>
<section class="section"><div class="card"><div class="card-body"><br>
<div class="page-search-toolbar" data-search-items="#tuya-devices > .tuya-device-card, #tuya-active-devices > .tuya-device-card" data-search-watch="#tuya-devices, #tuya-active-devices" data-search-sections=".card.border-secondary, .card.border-primary, .card.border-success, .card.alert-secondary" aria-label="Tìm kiếm và điều hướng cấu hình Tuya"><div class="row g-2 align-items-center"><div class="col-12 col-lg"><div class="input-group"><span class="input-group-text border-primary"><i class="bi bi-search text-primary"></i></span><input type="search" class="form-control border-primary page-search-input" placeholder="Tìm tên thiết bị, DPS, câu lệnh hoặc phản hồi..." autocomplete="off"><button type="button" class="btn btn-outline-secondary page-search-clear" title="Xóa nội dung tìm kiếm"><i class="bi bi-x-lg"></i></button></div></div><div class="col-12 col-lg-auto"><select class="form-select border-primary page-search-navigation" aria-label="Đi tới thiết bị hoặc khu vực"><option value="">Đi tới thiết bị hoặc khu vực...</option></select></div></div><div class="alert alert-info py-2 mt-2 mb-0 page-search-empty" role="status" style="display:none"><i class="bi bi-info-circle"></i> Không tìm thấy thiết bị phù hợp.</div></div>
<div id="tuya-msg"></div>
<section class="card border border-secondary border-2 mt-4"><div class="card-header bg-secondary text-white"><h5 class="mb-0">Quét Dữ Liệu, Liên Kết Thiết Bị Tuya Smart/Smart Life Với VBot <i class="bi bi-question-circle-fill" onclick="show_message('Quy Trình: Nhập Mã người dùng User Code -> Nhấn Quét Mã QR Đăng Nhập -> sẽ hiển thị mã qr người dùng cần mở app Tuya Smart/Smart Life quét mã QR này sau đó đồng ý xác thực trên APP là được')"></i></h5></div><div class="card-body">
<div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label">Tuya Smart/Smart Life User Code</label><input id="tuya-user-code" class="form-control border-success" placeholder="BabC0Zq" autocomplete="off"></div><div class="col-md-4"><label class="form-label">Subnet scan</label><input id="tuya-subnet" class="form-control border-success" value="<?php echo long2ip(ip2long($serverIp) & 0xFFFFFF00) . '/24'; ?>"></div><div class="col-md-4"><button class="btn btn-primary" onclick="tuyaStart()"><i class="bi bi-qr-code-scan"></i> Quét Mã QR Đăng Nhập</button> <!-- <button disabled class="btn btn-secondary" onclick="tuyaRefresh()">Làm mới bằng phiên đăng nhập</button> --></div></div>
</div></section>

<div class="modal fade" id="tuyaRunModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tuya Local — Tiến trình Python</h5><button type="button" class="btn-close" data-bs-dismiss="modal" onclick="loading('hide')"></button></div><div class="modal-body"><div id="tuya-run-state" class="alert alert-info">Đang chuẩn bị...</div><div id="tuya-qr-box" class="text-center" style="display:none"><p>Quét QR bằng Smart Life (+ → Scan), sau đó Confirm login.</p><div class="border rounded p-2 bg-white d-inline-block"><img id="tuya-qr" style="max-width:320px;display:block"></div></div><div class="card mt-3"><div class="card-header py-2"><i class="bi bi-terminal"></i> Log chạy tuya_devices.py</div><div class="card-body p-2"><div id="tuya-log" class="text-start small" style="height:220px;overflow:auto;white-space:pre-wrap;font-family:monospace"></div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="loading('hide')">Đóng</button></div></div></div></div>
 <div class="modal fade" id="tuyaJsonModal" tabindex="-1" aria-labelledby="tuyaJsonModalTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title text-primary" id="tuyaJsonModalTitle">Nội dung JSON</h5><button type="button" class="btn btn-danger" data-bs-dismiss="modal">Đóng</button></div><div class="modal-body"><p id="tuyaJsonModalMessage" class="text-muted"></p><pre class="json rounded border p-3"><code id="tuyaJsonModalCode" class="language-json"></code></pre></div></div></div></div>
 <style>
 .tuya-device-list{display:grid;gap:1rem}
  .tuya-device-card{border:1px solid #dee2e6;border-radius:.75rem;background:#fff;overflow:hidden}
  .tuya-active-device-card.alert{padding:0;background-color:var(--bs-alert-bg);color:var(--bs-alert-color);border-color:var(--bs-alert-border-color)}
  .tuya-active-device-card .tuya-device-card-head{background:transparent;border-bottom-color:var(--bs-alert-border-color)}
 .tuya-device-card.is-configured{border:2px solid #dc3545;background:#fffafa}
 .tuya-device-card-head{padding:.85rem 1rem;background:#f8f9fa;border-bottom:1px solid #dee2e6}
 .tuya-collapse-toggle{width:100%;border:0;text-align:left;color:inherit;cursor:pointer}
 .tuya-collapse-toggle::after{content:'+';font-size:1.35rem;font-weight:600;color:#6c757d;margin-left:1rem}
 .tuya-collapse-toggle[aria-expanded="true"]::after{content:'−'}
 .tuya-device-card-body{padding:1rem}
 .tuya-entry-card{height:100%;padding:.9rem;border:1px solid #dee2e6;border-radius:.65rem;background:#fff}
 .tuya-entry-card textarea{min-height:5.25rem;resize:vertical}
 .tuya-device-meta{font-size:.875rem;color:#6c757d;overflow-wrap:anywhere}
 .tuya-secret-input{min-width:0}
 .tuya-section-hint{max-width:70rem}
 #tuya-devices input:not([type="hidden"]),#tuya-devices textarea,#tuya-active-devices input:not([type="hidden"]),#tuya-active-devices textarea{border-color:var(--bs-success)!important}
 #tuya-devices input[type="checkbox"],#tuya-active-devices input[type="checkbox"]{border-color:var(--bs-success)!important}
 #tuya-devices input:focus,#tuya-devices textarea:focus,#tuya-active-devices input:focus,#tuya-active-devices textarea:focus{border-color:var(--bs-success)!important;box-shadow:0 0 0 .2rem rgba(var(--bs-success-rgb),.2)}
 </style>
 
<section class="card border border-primary border-2 mt-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            Dữ liệu quét tạm trước đó — chọn thiết bị để cấu hình
            <i class="bi bi-question-circle-fill" onclick="show_message('Chọn thiết bị cần thêm, kiểm tra thông tin nhận diện rồi đặt tên và câu lệnh cho từng DPS. Thiết bị đã liên kết sẽ bị khóa cấu hình tại đây.')"></i>
        </h5>
    </div>

    <div class="card-body">
        <br/>
        <div id="tuya-devices" class="tuya-device-list"></div>

        <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
            <button type="button" class="btn btn-success" onclick="tuyaSave()">
                <i class="bi bi-plus-circle"></i> Lưu thiết bị đã chọn
            </button>

            <button type="button" class="btn btn-outline-danger" onclick="clearScanData()">
                <i class="bi bi-trash"></i> Xóa toàn bộ dữ liệu quét tạm
            </button>
        </div>
    </div>
</section>
<section class="card border border-success border-2 mt-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            Thiết bị Tuya Smart/Smart Life đã liên kết với VBot
            <i class="bi bi-question-circle-fill" onclick="show_message('Quản lý thông tin kết nối và cấu hình từng thiết bị DPS. Chỉ các thiết bị DPS được bật mới được VBot dùng, nhập nhiều câu lệnh thì mỗi dòng là một câu.')"></i>
        </h5>
    </div><br/>

    <div class="card-body">
        <div id="tuya-active-devices" class="tuya-device-list"></div>

        <div class="text-center mt-3">
            <button type="button" class="btn btn-success" onclick="saveAllTuyaDevices()">
                <i class="bi bi-save"></i> Lưu toàn bộ thiết bị Tuya Local
            </button>
        </div>
    </div>
</section>
 <section class="card alert alert-secondary mt-4"><div class="card-body pt-3"><h5 class="card-title">Nhập, tải xuống và sao lưu</h5>
   <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
     <input type="hidden" name="action" value="backup_import">
     <div class="col-md-5"><label class="form-label" for="tuya_import_file">Chọn file cấu hình Tuya JSON (tối đa 10 MB)</label><input required accept="application/json,.json" type="file" class="form-control" id="tuya_import_file" name="tuya_import_file"></div>
     <div class="col-md-3"><label class="form-label" for="tuya_import_mode">Cách nhập dữ liệu</label><select class="form-select" id="tuya_import_mode" name="import_mode"><option value="merge">Gộp theo mã thiết bị</option><option value="overwrite">Ghi đè toàn bộ</option></select></div>
      <div class="col-md-4 d-flex gap-2"><button class="btn btn-success" type="submit" onclick="return confirm('Nhập file cấu hình Tuya đã chọn? Cấu hình hiện tại sẽ được sao lưu trước.')"><i class="bi bi-upload"></i> Tải lên</button><button class="btn btn-info" type="button" onclick="readTuyaCurrentJson()"><i class="bi bi-eye"></i> Xem JSON hiện tại</button><a class="btn btn-primary" href="Tuya_Local.php?action=download&amp;target=current"><i class="bi bi-download"></i> Tải xuống</a></div>
     <div class="col-12 small text-muted">Tự động sao lưu trước mỗi lần cập nhật cấu hình và giữ tối đa <b><?php echo $tuyaBackupLimit; ?></b> bản tại <code>html/Backup_Upgrade/Backup_Tuya_Local/</code>.</div>
   </form>
   <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>File sao lưu</th><th>Thời gian</th><th>Dung lượng</th><th>Thao tác</th></tr></thead><tbody>
     <?php if (!$tuyaBackupFiles): ?><tr><td colspan="4" class="text-muted">Chưa có bản sao lưu.</td></tr><?php endif; ?>
      <?php foreach ($tuyaBackupFiles as $backupPath): $backupName=basename($backupPath); ?><tr><td><code><?php echo tuya_h($backupName); ?></code></td><td><?php echo date('d/m/Y H:i:s', filemtime($backupPath) ?: time()); ?></td><td><?php echo number_format((filesize($backupPath) ?: 0)/1024, 1); ?> KB</td><td class="text-nowrap"><button type="button" class="btn btn-sm btn-primary" title="Xem JSON" onclick="readTuyaBackupJson(<?php echo tuya_h(json_encode($backupName, JSON_UNESCAPED_SLASHES)); ?>)"><i class="bi bi-eye"></i></button> <a class="btn btn-sm btn-success" title="Tải xuống" href="Tuya_Local.php?action=download&amp;backup=<?php echo rawurlencode($backupName); ?>"><i class="bi bi-download"></i></a> <form method="post" class="d-inline" onsubmit="return confirm('Khôi phục bản sao lưu này? Cấu hình hiện tại sẽ được sao lưu trước.')"><input type="hidden" name="action" value="restore_backup"><input type="hidden" name="backup_file" value="<?php echo tuya_h($backupName); ?>"><button class="btn btn-sm btn-warning" title="Khôi phục"><i class="bi bi-arrow-counterclockwise"></i></button></form> <form method="post" class="d-inline" onsubmit="return confirm('Xóa vĩnh viễn bản sao lưu này?')"><input type="hidden" name="action" value="delete_backup"><input type="hidden" name="backup_file" value="<?php echo tuya_h($backupName); ?>"><button class="btn btn-sm btn-danger" title="Xóa"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
   </tbody></table></div>
 </div></section>
  </div></div></section></main><?php include 'html_footer.php'; ?><a href="#" class="back-to-top d-flex align-items-center justify-content-center">↑</a><?php include 'html_js.php'; ?>
 <script src="assets/vendor/prism/prism.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
 <script src="assets/vendor/prism/prism-json.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script>
let csrf=window.VBOT_CSRF_TOKEN||document.querySelector('meta[name="vbot-csrf-token"]')?.content||''; let devices=<?php echo json_encode($config['devices']??[], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;let selectedIds=<?php echo json_encode($config['selected_ids']??[], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;let configuredIds=selectedIds.map(String);let scanCleared=<?php echo $hasTemporaryScanData ? 'false' : 'true'; ?>;
let tuyaAccounts=<?php echo json_encode($config['accounts'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;let tuyaActiveAccountId=<?php echo json_encode((string)($config['active_account_id'] ?? ''), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;

document.getElementById('tuya-user-code').previousElementSibling.textContent='Mã người dùng trên APP Tuya Smart/Smart Life (User Code)';
function esc(v){return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}
function msg(t,c='info'){let text=String(t??'');if(c==='danger'||c==='warning'){if(typeof window.show_message==='function')window.show_message(esc(text));else if(typeof window.show_message_text==='function')window.show_message_text(text);return;}if(typeof window.showMessagePHP==='function'){window.showMessagePHP(esc(text),5);return;}document.getElementById('tuya-msg').textContent=text;}
function tuyaEntries(value){if(!value)return[];if(Array.isArray(value))return value.map((v,n)=>[String(n+1),v]);return Object.entries(value);}
function commandText(value){return Array.isArray(value)?value.join('\n'):(value||'');}
function render(){let scanRows=devices.map((d,i)=>{let t=d.tuya||{}, dps=t.dps||{}, configured=selectedIds.includes(String(d.device_id||d.id)), rows=Object.entries(dps).map(([k,entry])=>{let meta=entry.status??entry.function??entry.status_range??{}, type=meta.type??meta.dp_type??'', commands=entry.commands||{on:[],off:[]}, lock=configured?'readonly disabled class="form-control form-control-sm border border-danger"':'class="form-control form-control-sm"';return '<div class="border rounded p-2 mb-2 ' + (configured?'border-danger':'') + '"><div><input ' + (lock) + ' aria-label="Tên dễ nhớ cho DPS ' + (esc(entry.dps_id||k)) + '" placeholder="Tên dễ nhớ, ví dụ: Đèn bàn" value="' + (esc(entry.display_name||'')) + '" data-i="' + (i) + '" data-f="tuya.dps.' + (esc(k)) + '.display_name"><small class="d-block text-muted mt-1">' + (entry.dps_id?'DPS '+esc(entry.dps_id):'Chưa xác định mã DPS') + ' · ' + (esc(k)) + (type?' · '+esc(type):'') + '</small></div><div class="row g-2 mt-1"><div class="col-md-6"><label class="form-label small mb-1">Câu lệnh bật (mỗi dòng một câu lệnh)</label><textarea ' + (lock) + ' rows="3" placeholder="bật đèn 1&#10;đèn 1 bật" data-i="' + (i) + '" data-f="tuya.dps.' + (esc(k)) + '.commands.on">' + (esc(commandText(commands.on))) + '</textarea></div><div class="col-md-6"><label class="form-label small mb-1">Câu lệnh tắt (mỗi dòng một câu lệnh)</label><textarea ' + (lock) + ' rows="3" placeholder="tắt đèn 1&#10;đèn 1 tắt" data-i="' + (i) + '" data-f="tuya.dps.' + (esc(k)) + '.commands.off">' + (esc(commandText(commands.off))) + '</textarea></div></div></div>';}).join('');let key=esc(d.local_key||d.credentials?.local_key||'');return '<tr class="' + (configured?'table-light border-danger':'') + '"><td>' + (configured?'<span class="badge bg-danger">Đã có cấu hình</span><br><input type="checkbox" class="form-check-input mt-2 border border-danger" disabled title="Thiết bị này đã có trong Tuya Local">':'<input type="checkbox" class="form-check-input" data-i="' + (i) + '" data-f="enabled" ' + (selectedIds.includes(String(d.id))?'checked':'') + '>') + '</td><td><input class="form-control ' + (configured?'border-danger':'') + '" data-i="' + (i) + '" data-f="display_name" value="' + (esc(d.display_name||d.id)) + '" ' + (configured?'readonly':'') + '></td><td><code>' + (esc(d.device_id||d.id)) + '</code><br><small>' + (esc(d.ip||d.network?.ip||'Chưa tìm thấy IP')) + ' · v' + (esc(d.version||d.network?.protocol_version||'')) + '</small><div class="input-group input-group-sm mt-1"><input type="password" class="form-control ' + (configured?'border-danger':'') + '" data-i="' + (i) + '" data-f="local_key" value="' + (key) + '" ' + (configured?'readonly':'') + '><span class="input-group-text ' + (configured?'border-danger':'') + '"><input type="checkbox" class="' + (configured?'border-danger':'') + '" title="Hiện local key" ' + (configured?'disabled':'') + ' onclick="this.parentElement.previousElementSibling.type=this.checked?\'text\':\'password\'"></span></div>' + (configured?'<small class="text-danger">Thiết bị đã có cấu hình; thông tin mới được đồng bộ riêng.</small>':'') + '</td><td>' + (rows||'<span class="text-muted">Thiết bị chưa khai báo entries</span>') + '</td></tr>';}).join('');document.getElementById('tuya-devices').innerHTML=scanRows||'<tr><td colspan="4" class="text-center">Chưa có dữ liệu scan. Hãy đăng nhập hoặc làm mới.</td></tr>';let active=devices.filter(d=>selectedIds.includes(String(d.id)));document.getElementById('tuya-active-devices').innerHTML=active.map(d=>{let entries=Object.entries(d.tuya?.dps||{}).map(([k,e])=>{let c=e.commands||{};return '<div class="border rounded p-2 mb-2"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" ' + (e.enabled?'checked':'') + ' data-i="' + (esc(d.id)) + '" data-id="' + (esc(d.id)) + '" data-f="dps.' + (esc(k)) + '.enabled"><label class="form-check-label"><b>' + (esc(e.display_name||'Chưa đặt tên')) + ' <small class="text-muted">(' + (e.dps_id?'DPS '+esc(e.dps_id):'Chưa xác định mã DPS') + ' · ' + (esc(k)) + ')</small></b> — kích hoạt</label></div><input class="form-control form-control-sm mb-1" placeholder="Tên dễ nhớ cho mục này" aria-label="Tên dễ nhớ cho DPS ' + (esc(e.dps_id||k)) + '" value="' + (esc(e.display_name||'')) + '" data-id="' + (esc(d.id)) + '" data-f="dps.' + (esc(k)) + '.display_name"><small class="d-block text-muted mb-2">' + (e.dps_id?'DPS '+esc(e.dps_id):'Chưa xác định mã DPS') + ' · ' + (esc(k)) + '</small><div class="row g-2"><div class="col-md-6"><label class="form-label small">Lệnh bật</label><textarea class="form-control form-control-sm" rows="3" data-id="' + (esc(d.id)) + '" data-f="dps.' + (esc(k)) + '.on">' + (esc(commandText(c.on))) + '</textarea></div><div class="col-md-6"><label class="form-label small">Lệnh tắt</label><textarea class="form-control form-control-sm" rows="3" data-id="' + (esc(d.id)) + '" data-f="dps.' + (esc(k)) + '.off">' + (esc(commandText(c.off))) + '</textarea></div></div></div>';}).join('');let overall=Object.values(d.tuya?.dps||{}).length>0&&Object.values(d.tuya.dps).every(e=>e.enabled);return '<tr><td><label class="form-check"><input class="form-check-input" type="checkbox" ' + (overall?'checked':'') + ' onchange="setDeviceEntriesEnabled(\'' + (esc(d.id)) + '\',this.checked)"><span class="form-check-label">Kích hoạt toàn bộ</span></label></td><td><input class="form-control" data-id="' + (esc(d.id)) + '" data-f="display_name" value="' + (esc(d.display_name||d.id)) + '"></td><td><code>' + (esc(d.device_id||d.id)) + '</code><br><label class="form-label small mt-1 mb-0">IP</label><input class="form-control form-control-sm" data-id="' + (esc(d.id)) + '" data-f="ip" value="' + (esc(d.ip||'')) + '"><label class="form-label small mt-1 mb-0">Local key</label><div class="input-group input-group-sm"><input type="password" class="form-control" data-id="' + (esc(d.id)) + '" data-f="local_key" value="' + (esc(d.local_key||d.credentials?.local_key||'')) + '"><span class="input-group-text"><input type="checkbox" title="Hiện local key" onclick="this.parentElement.previousElementSibling.type=this.checked?\'text\':\'password\'"></span></div></td><td>' + (entries||'Không có entry') + '</td></tr>';}).join('')||'<tr><td colspan="4" class="text-center text-muted">Chưa có thiết bị nào được chọn để điều khiển.</td></tr>';}
const tuyaRenderBase=render;render=function(){tuyaRenderBase();document.querySelectorAll('#tuya-devices tr').forEach((row,i)=>{let d=devices[i],cell=row.cells[1];if(!d||!cell||cell.querySelector('[data-app-name]'))return;let app=document.createElement('div');app.dataset.appName='1';app.className='small text-muted mt-1';app.textContent='Tên trên Smart Life: '+(d.app_name||d.name||'Chưa có');cell.appendChild(app);});document.querySelectorAll('#tuya-active-devices tr').forEach(row=>{if(row.cells.length===4)row.deleteCell(0);else if(row.cells.length===1){row.cells[0].colSpan=3;return;}let idNode=row.querySelector('code');if(!idNode)return;let id=idNode.textContent.trim(),d=devices.find(x=>String(x.id)===id),nameCell=row.cells[0];if(d&&nameCell&&!nameCell.querySelector('[data-app-name]')){let app=document.createElement('div');app.dataset.appName='1';app.className='small text-muted mt-1';app.textContent='Tên trên Smart Life: '+(d.app_name||d.name||'Chưa có');nameCell.appendChild(app);}if(row.querySelector('[data-delete-device]'))return;if(!nameCell)return;let button=document.createElement('button');button.type='button';button.className='btn btn-sm btn-outline-danger mt-2 d-block';button.dataset.deleteDevice='1';button.innerHTML='<i class="bi bi-trash"></i> Xóa liên kết thiết bị';button.addEventListener('click',()=>deleteTuyaDevice(id));nameCell.appendChild(button);});};
async function clearScanData(){if(!window.confirm('Xóa toàn bộ dữ liệu scan tạm? Thiết bị đã cấu hình trong Tuya Local sẽ được giữ lại.'))return;let r=await post('clear_scan');if(!r.ok)return msg(r.error||'Không xóa được dữ liệu scan.','danger');devices=devices.filter(d=>selectedIds.includes(String(d.id)));render();msg('Đã xóa toàn bộ dữ liệu scan tạm.','success');}
async function deleteTuyaDevice(id){if(!window.confirm('Bạn có chắc chắn muốn xóa thiết bị '+id+' khỏi Dữ Liệu Thiết Bị Tuya Local?'))return;let r=await post('delete_device',{id});if(!r.ok)return msg(r.error||'Không xóa được thiết bị.','danger');devices=devices.filter(d=>String(d.id)!==String(id));selectedIds=selectedIds.filter(x=>String(x)!==String(id));configuredIds=configuredIds.filter(x=>String(x)!==String(id));render();msg('Đã xóa thiết bị khỏi Tuya Local.','success');}
function setEntryEnabled(id,key,enabled){let d=devices.find(x=>String(x.id)===String(id));if(!d)return;d.tuya.dps[key].enabled=enabled;render();}
function setDeviceEntriesEnabled(id,enabled){let d=devices.find(x=>String(x.id)===String(id));if(!d)return;Object.values(d.tuya?.dps||{}).forEach(entry=>entry.enabled=enabled);document.querySelectorAll('#tuya-active-devices [data-id="' + (CSS.escape(String(id))) + '"][data-f^="dps."][data-f$=".enabled"]').forEach(e=>e.checked=enabled);}
async function saveAllTuyaDevices(){readEdits();if(!validateEnabledTuyaNames())return;loading('show');try{let r=await post('save',{devices:JSON.stringify(devices)});if(!r.ok){msg(r.error||'Không lưu được cấu hình Tuya Local.','danger');return;}render();msg('Đã lưu toàn bộ thay đổi thiết bị Tuya Local.','success');}catch(e){msg('Lỗi khi lưu cấu hình Tuya Local: '+(e?.message||e),'danger');}finally{loading('hide');}}
function readEdits(){
 document.querySelectorAll('#tuya-devices [data-i]').forEach(e=>{let i=+e.dataset.i,f=e.dataset.f;if(f==='enabled')devices[i][f]=e.checked;else if(f==='local_key'){devices[i].local_key=e.value;devices[i].credentials=devices[i].credentials||{};devices[i].credentials.local_key=e.value;}else if(f.startsWith('tuya.dps.')){let p=f.split('.'),key=p[2];devices[i].tuya=devices[i].tuya||{};devices[i].tuya.dps=devices[i].tuya.dps||{};devices[i].tuya.dps[key]=devices[i].tuya.dps[key]||{commands:{on:[],off:[]},enabled:false};if(p[3]==='display_name')devices[i].tuya.dps[key].display_name=e.value;else if(p[3]==='enabled')devices[i].tuya.dps[key].enabled=e.checked;else{devices[i].tuya.dps[key].commands=devices[i].tuya.dps[key].commands||{on:[],off:[]};devices[i].tuya.dps[key].commands[p[4]]=e.value.split(/\r?\n/).map(x=>x.trim()).filter(Boolean);}}else devices[i][f]=e.value;});
 document.querySelectorAll('#tuya-active-devices [data-id][data-f]').forEach(e=>{let d=devices.find(x=>String(x.id)===String(e.dataset.id));if(!d)return;let f=e.dataset.f;if(f==='display_name')d.display_name=e.value;else if(f==='local_key'){d.local_key=e.value;d.credentials=d.credentials||{};d.credentials.local_key=e.value;}else if(f==='ip'){d.ip=e.value;d.network=d.network||{};d.network.ip=e.value;}else if(f.startsWith('dps.')){let p=f.split('.'),key=p[1],field=p[2];d.tuya=d.tuya||{};d.tuya.dps=d.tuya.dps||{};d.tuya.dps[key]=d.tuya.dps[key]||{commands:{on:[],off:[]},enabled:false};if(field==='enabled')d.tuya.dps[key].enabled=e.checked;else if(field==='display_name')d.tuya.dps[key].display_name=e.value;else if(field==='on'||field==='off')d.tuya.dps[key].commands[field]=e.value.split(/\r?\n/).map(x=>x.trim()).filter(Boolean);}});
 document.querySelectorAll('#tuya-active-devices [data-f="tts_response"]').forEach(e=>{let d=devices.find(x=>String(x.id)===String(e.dataset.id));if(d)d.tts_response=e.value;});
 document.querySelectorAll('#tuya-devices [data-f$=".tts_response.on"], #tuya-devices [data-f$=".tts_response.off"]').forEach(e=>{let p=e.dataset.f.split('.'),d=devices[Number(e.dataset.i)],key=p[2],direction=p[4];if(d?.tuya?.dps?.[key]){d.tuya.dps[key].tts_response=d.tuya.dps[key].tts_response||{};d.tuya.dps[key].tts_response[direction]=e.value;}});
 document.querySelectorAll('#tuya-active-devices [data-f^="dps."][data-f*=".tts_response."]').forEach(e=>{let d=devices.find(x=>String(x.id)===String(e.dataset.id)),p=e.dataset.f.split('.'),key=p[1],direction=p[3];if(d?.tuya?.dps?.[key]){d.tuya.dps[key].tts_response=d.tuya.dps[key].tts_response||{};d.tuya.dps[key].tts_response[direction]=e.value;}});
 }
 function validateEnabledTuyaNames(){
 const missing=[];
 document.querySelectorAll('#tuya-active-devices [data-f^="dps."][data-f$=".enabled"]:checked').forEach(toggle=>{const id=String(toggle.dataset.id),key=toggle.dataset.f.split('.')[1],input=document.querySelector('#tuya-active-devices [data-id="' + (CSS.escape(id)) + '"][data-f="dps.' + (CSS.escape(key)) + '.display_name"]');if(input){input.classList.remove('is-invalid');if(!input.value.trim()){input.classList.add('is-invalid');missing.push(input);}}});
 document.querySelectorAll('#tuya-devices [data-f="enabled"]:checked').forEach(deviceToggle=>{const index=deviceToggle.dataset.i;document.querySelectorAll('#tuya-devices [data-i="' + (CSS.escape(index)) + '"][data-f^="tuya.dps."][data-f$=".enabled"]:checked').forEach(toggle=>{const key=toggle.dataset.f.split('.')[2],input=document.querySelector('#tuya-devices [data-i="' + (CSS.escape(index)) + '"][data-f="tuya.dps.' + (CSS.escape(key)) + '.display_name"]');if(input){input.classList.remove('is-invalid');if(!input.value.trim()){input.classList.add('is-invalid');missing.push(input);}}});});
 if(!missing.length)return true;missing[0].scrollIntoView({behavior:'smooth',block:'center'});missing[0].focus({preventScroll:true});msg('Vui lòng nhập Tên thiết bị cho từng Entries đang bật. Các ô thiếu tên đã được đánh dấu đỏ.','danger');return false;
}
async function refreshCsrfToken(){try{let r=await fetch('Tuya_Local.php?action=csrf&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json'}});if(!r.ok)return false;let x=await r.json();if(!x.csrf_token)return false;csrf=x.csrf_token;window.VBOT_CSRF_TOKEN=csrf;let m=document.querySelector('meta[name="vbot-csrf-token"]');if(m)m.content=csrf;return true;}catch(e){return false;}}
async function post(action,extra={}){let b=new URLSearchParams({action,csrf_token:csrf,...extra});let r=await fetch('Tuya_Local.php',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':csrf,'Accept':'application/json'},body:b});if(r.status===403){if(await refreshCsrfToken()){b.set('csrf_token',csrf);r=await fetch('Tuya_Local.php',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':csrf,'Accept':'application/json'},body:b});}}if(r.status===403){msg('Phiên bảo mật đã hết hạn. Hãy tải lại trang rồi thử lại.','warning');return {ok:false,error:'CSRF token hết hạn'};}return r.json();}
function tuyaStart(){tuyaOpenStream(true);}
function tuyaRefresh(){tuyaOpenStream(false);}
 async function tuyaSave(){readEdits();selectedIds=devices.filter(d=>d.enabled&&!configuredIds.includes(String(d.device_id||d.id))).map(d=>d.id);if(!validateEnabledTuyaNames())return;let r=await post('save',{devices:JSON.stringify(devices)});if(!r.ok)return msg(r.error||'Không lưu được cấu hình.','danger');configuredIds=Array.from(new Set(configuredIds.concat(selectedIds.map(String))));render();msg('Đã lưu. Thiết bị mới đã chuyển xuống Tuya Local; local key/IP mới của thiết bị trùng cũng đã được đồng bộ.','success');}
const tuyaRenderWithMetadata=render;render=function(){tuyaRenderWithMetadata();if(scanCleared)document.getElementById('tuya-devices').innerHTML='<tr><td colspan="4" class="text-center">Chưa có dữ liệu scan. Hãy đăng nhập hoặc làm mới.</td></tr>';};
const tuyaClearScanOriginal=clearScanData;clearScanData=async function(){if(!window.confirm('X\\u00f3a to\\u00e0n b\\u1ed9 d\\u1eef li\\u1ec7u scan t\\u1ea1m? Thi\\u1ebft b\\u1ecb \u0111\u00e3 c\u1ea5u h\u00ecnh v\u1eabn \u0111\u01b0\u1ee3c gi\u1eef trong danh s\u00e1ch b\u00ean d\u01b0\u1edbi.'))return;let r=await post('clear_scan');if(!r.ok)return msg(r.error||'Kh\\u00f4ng x\\u00f3a \u0111\u01b0\u1ee3c d\u1eef li\u1ec7u scan.','danger');scanCleared=true;devices=devices.filter(d=>selectedIds.includes(String(d.id)));render();msg('\u0110\u00e3 x\u00f3a to\u00e0n b\u1ed9 d\u1eef li\u1ec7u scan t\u1ea1m.','success');};
const tuyaStartOriginal=tuyaStart;tuyaStart=function(){scanCleared=false;tuyaStartOriginal();};
const tuyaRefreshOriginal=tuyaRefresh;tuyaRefresh=function(){scanCleared=false;tuyaRefreshOriginal();};
const tuyaRenderWithScanClear=render;render=function(){tuyaRenderWithScanClear();if(scanCleared)document.getElementById('tuya-devices').innerHTML='<tr><td colspan="4" class="text-center">Ch\\u01b0a c\\u00f3 d\\u1eef li\\u1ec7u scan t\\u1ea1m. H\\u00e3y \\u0111\\u0103ng nh\\u1eadp ho\\u1eb7c l\\u00e0m m\\u1edbi.</td></tr>';};
clearScanData=async function(){if(!window.confirm('X\\u00f3a to\\u00e0n b\\u1ed9 d\\u1eef li\\u1ec7u scan t\\u1ea1m? Thi\\u1ebft b\\u1ecb \\u0111\\u00e3 c\\u1ea5u h\\u00ecnh v\\u1eabn \\u0111\\u01b0\\u1ee3c gi\\u1eef trong danh s\\u00e1ch b\\u00ean d\\u01b0\\u1edbi.'))return;let r=await post('clear_scan');if(!r.ok)return msg(r.error||'Kh\\u00f4ng th\\u1ec3 x\\u00f3a d\\u1eef li\\u1ec7u scan t\\u1ea1m.','danger');scanCleared=true;devices=devices.filter(d=>selectedIds.includes(String(d.id)));render();msg('\\u0110\\u00e3 x\\u00f3a to\\u00e0n b\\u1ed9 d\\u1eef li\\u1ec7u scan t\\u1ea1m.','success');};
let currentScanUserCode='';
const tuyaStartWithExisting=tuyaStart;tuyaStart=function(){currentScanUserCode=document.getElementById('tuya-user-code').value.trim();tuyaStartWithExisting();};
const tuyaRenderWithUserCode=render;render=function(){if(currentScanUserCode)devices.forEach(d=>delete d.user_code);tuyaRenderWithUserCode();};
 document.getElementById('tuya-user-code').addEventListener('change',async function(){let code=this.value.trim();if(!code)return;let result=await post('save_user_code',{user_code:code});if(!result.ok)msg(result.error||'Không thể lưu mã người dùng tài khoản.','danger');else{tuyaAccounts=result.accounts||tuyaAccounts;tuyaActiveAccountId=result.account_id||tuyaActiveAccountId;render();msg('Đã lưu mã người dùng Smart Life cho tài khoản.','success');}});
document.getElementById('tuya-subnet').previousElementSibling.textContent='Dải mạng LAN cần quét';
document.querySelector('#tuya-qr-box p').textContent='Mở Smart Life, chọn (+) → Quét mã, sau đó nhấn Xác nhận đăng nhập.';
document.querySelector('.main .alert-info').innerHTML='<b>Quy trình:</b> nhập Mã người dùng Smart Life → tạo mã QR → quét và xác nhận trong ứng dụng → WebUI tự lấy khóa cục bộ và quét địa chỉ IP trong mạng LAN. Khóa cục bộ chỉ được lưu trên máy chủ.';
document.querySelectorAll('.row button').forEach(button=>{if(button.textContent.includes('session'))button.textContent='Làm mới bằng phiên đăng nhập';});
document.querySelector('#tuyaRunModal .card-header').lastChild.textContent=' Nhật ký chạy tuya_devices.py';
document.querySelectorAll('#tuya-devices thead th').forEach(th=>{if(th.textContent.includes('Local key'))th.textContent=th.textContent.replace('Local key','Khóa cục bộ');if(th.textContent==='Entries và câu lệnh bật/tắt')th.textContent='Mục DPS và câu lệnh bật/tắt';});
const scanCardTitle=document.querySelector('.border-primary .card-header h5');if(scanCardTitle)scanCardTitle.textContent='Dữ liệu quét tạm trước đó — chọn thiết bị để cấu hình';
const clearScanButton=document.querySelector('#tuya-form .btn-outline-danger');if(clearScanButton)clearScanButton.innerHTML='<i class="bi bi-trash"></i> Xóa toàn bộ dữ liệu quét tạm';
document.querySelectorAll('#tuya-active-devices label').forEach(label=>{if(label.textContent.trim()==='Local key')label.textContent='Khóa cục bộ (Local Key)';});
const tuyaRenderVietnamese=render;render=function(){tuyaRenderVietnamese();if(scanCleared)document.getElementById('tuya-devices').innerHTML='<tr><td colspan="4" class="text-center">Ch&#432;a c&#243; d&#7919; li&#7879;u scan t&#7841;m. H&#227;y &#273;&#259;ng nh&#7853;p ho&#7863;c l&#224;m m&#7899;i.</td></tr>';};
 const tuyaRenderLabels=render;render=function(){tuyaRenderLabels();document.querySelectorAll('#tuya-active-devices label').forEach(label=>{if(label.textContent.trim()==='Local key')label.textContent='Khóa cục bộ (Local Key)';});document.querySelectorAll('#tuya-devices input[type="checkbox"]').forEach(input=>{if(input.title==='Hiện local key')input.title='Hiện khóa cục bộ';});document.querySelectorAll('#tuya-active-devices input[type="checkbox"]').forEach(input=>{if(input.title==='Hiện local key')input.title='Hiện khóa cục bộ';});};
 const tuyaRenderAccountCodes=render;render=function(){tuyaRenderAccountCodes();document.querySelectorAll('#tuya-active-devices tr').forEach(row=>{let id=row.querySelector('code')?.textContent.trim();let device=devices.find(item=>String(item.id)===String(id));let cell=row.cells[1];if(!device||!cell||cell.querySelector('[data-account-user-code]'))return;let account=tuyaAccounts[String(device.account_id||'')];let label=document.createElement('div');label.className='small text-muted mt-1';label.dataset.accountUserCode='1';label.textContent='Mã người dùng Smart Life: '+(account?.user_code||'Chưa gắn tài khoản');cell.appendChild(label);});};
clearScanData=async function(){if(!window.confirm('X\u00f3a to\u00e0n b\u1ed9 d\u1eef li\u1ec7u scan t\u1ea1m? Thi\u1ebft b\u1ecb \u0111\u00e3 c\u1ea5u h\u00ecnh v\u1eabn \u0111\u01b0\u1ee3c gi\u1eef trong danh s\u00e1ch b\u00ean d\u01b0\u1edbi.'))return;let r=await post('clear_scan');if(!r.ok)return msg(r.error||'Kh\u00f4ng th\u1ec3 x\u00f3a d\u1eef li\u1ec7u scan t\u1ea1m.','danger');scanCleared=true;devices=devices.filter(d=>selectedIds.includes(String(d.id)));render();msg('\u0110\u00e3 x\u00f3a to\u00e0n b\u1ed9 d\u1eef li\u1ec7u scan t\u1ea1m.','success');};
function vietHoaLogTuya(text){return text.replace(/TinyTuya \(Tuya device scanner\)/g,'TinyTuya (công cụ quét thiết bị Tuya)').replace(/You need your Smart Life "user code":/g,'Cần nhập Mã người dùng của tài khoản Smart Life:').replace(/Open the Smart Life app -> Me -> tap the gear icon \(top-right\)/g,'Mở ứng dụng Smart Life → Tôi → chạm biểu tượng bánh răng (góc trên bên phải)').replace(/-> Account and Security -> User Code \(at the bottom\)\./g,'→ Tài khoản và Bảo mật → Mã người dùng (ở cuối trang).').replace(/Enter your Smart Life user code:/g,'Nhập Mã người dùng Smart Life:').replace(/Scan this QR code in the Smart Life app \(\+ -> Scan\),/g,'Quét mã QR này trong ứng dụng Smart Life (+ → Quét mã),').replace(/then tap "Confirm login":/g,'sau đó nhấn "Xác nhận đăng nhập":').replace(/Confirm login/g,'xác nhận đăng nhập').replace(/Waiting for you to confirm in the app\.{2,}/g,'Đang chờ bạn xác nhận trong ứng dụng...').replace(/Logged in\./g,'Đăng nhập thành công.').replace(/\(Saved .*?Open it and scan that if the one above won't scan\.\)/g,'(Đã lưu ảnh QR dự phòng trên máy chủ.)').replace(/Found (\d+) device\(s\): (\d+) online, (\d+) offline/g,'Tìm thấy $1 thiết bị: $2 đang trực tuyến, $3 ngoại tuyến').replace(/Scanning (\d+) address\(es\) on TCP port (\d+)[…\.]{0,3}/g,'Đang quét $1 địa chỉ qua cổng TCP $2...').replace(/Scan completed in ([0-9.]+) seconds/g,'Quét hoàn tất trong $1 giây').replace(/Scan Complete! Found (\d+) devices?\./g,'Đã quét xong! Tìm thấy $1 thiết bị.').replace(/DEVICE KEY required to poll for status/g,'Cần khóa thiết bị để đọc trạng thái').replace(/No Stats for ([0-9.]+):/g,'Không đọc được trạng thái tại $1:').replace(/Unknown v([0-9.]+) Device\s+Product ID/g,'Thiết bị chưa nhận diện (giao thức v$1), mã sản phẩm').replace(/New Broadcast from App at ([0-9.]+)/g,'Thiết bị phát hiện qua ứng dụng tại $1').replace(/Valid Broadcast/g,'Gói tin quảng bá hợp lệ').replace(/Scan completed/g,'Đã quét xong').replace(/Versions:/g,'Phiên bản giao thức:').replace(/Unknown Devices:/g,'Thiết bị chưa nhận diện:').replace(/Scan Complete!/g,'Đã hoàn tất quét!').replace(/Scan completed/g,'Đã hoàn tất quét').replace(/Broadcasted:/g,'Số thiết bị đã phát quảng bá:').replace(/Saving device snapshot data to/g,'Đang lưu bản ghi thiết bị vào').replace(/output trực tiếp/g,'nhật ký trực tiếp');}
const vietHoaLogGoc=vietHoaLogTuya;vietHoaLogTuya=function(text){let translated=vietHoaLogGoc(text),labels={name:'Tên',id:'Mã thiết bị',uuid:'UUID',local_key:'Khóa cục bộ',product_id:'Mã sản phẩm',product_name:'Tên sản phẩm',category:'Danh mục',ip:'Địa chỉ IP',lat:'Vĩ độ',lon:'Kinh độ',time_zone:'Múi giờ',online:'Trực tuyến',sub:'Thiết bị phụ',support_local:'Hỗ trợ điều khiển cục bộ',set_up:'Đã thiết lập',owner_id:'Mã chủ sở hữu',uid:'Mã người dùng',biz_type:'Loại hình',icon:'Biểu tượng',active_time:'Thời điểm hoạt động',create_time:'Thời điểm tạo',update_time:'Thời điểm cập nhật',status:'Trạng thái',function:'Chức năng',status_range:'Phạm vi trạng thái',local_strategy:'Chiến lược cục bộ',Address:'Địa chỉ', 'Device ID':'Mã thiết bị','Local Key':'Khóa cục bộ',Version:'Phiên bản',Type:'Loại thiết bị',MAC:'Địa chỉ MAC'};translated=translated.replace(/(^|\n)(\s*)(name|id|uuid|local_key|product_id|product_name|category|ip|lat|lon|time_zone|online|sub|support_local|set_up|owner_id|uid|biz_type|icon|active_time|create_time|update_time|status|function|status_range|local_strategy|Address|Device ID|Local Key|Version|Type|MAC)\s*:/g,(all,breakLine,indent,key)=>breakLine+indent+(labels[key]||key)+':').replace(/\b(\d+) entries\b/g,'$1 mục').replace(/\b(\d+) address\(es\)/g,'$1 địa chỉ');return translated;};
const vietHoaLogEquals=vietHoaLogTuya;vietHoaLogTuya=function(text){const labels={Address:'Địa chỉ','Device ID':'Mã thiết bị','Local Key':'Khóa cục bộ',Version:'Phiên bản',Type:'Loại thiết bị',MAC:'Địa chỉ MAC'};return vietHoaLogEquals(text).replace(/(^|\n)(\s*)(Address|Device ID|Local Key|Version|Type|MAC)\s*=/g,(all,breakLine,indent,key)=>breakLine+indent+(labels[key]||key)+' =');};
const vietHoaLogLoiGoc=vietHoaLogTuya;vietHoaLogTuya=function(text){return vietHoaLogLoiGoc(text).replace(/Traceback \(most recent call last\):/g,'Thông tin truy vết lỗi Python:').replace(/ModuleNotFoundError: No module named/g,'Thiếu mô-đun Python có tên').replace(/PermissionError:/g,'Lỗi quyền truy cập:').replace(/ConnectionError:/g,'Lỗi kết nối:').replace(/TimeoutError:/g,'Lỗi quá thời gian chờ:').replace(/ValueError:/g,'Giá trị không hợp lệ:').replace(/KeyError:/g,'Thiếu khóa dữ liệu:').replace(/Error:/g,'Lỗi:');};
const tuyaLogBox=document.getElementById('tuya-log');new MutationObserver(()=>{const translated=vietHoaLogTuya(tuyaLogBox.textContent);if(translated!==tuyaLogBox.textContent)tuyaLogBox.textContent=translated;}).observe(tuyaLogBox,{childList:true,characterData:true,subtree:true});
const tuyaRunState=document.getElementById('tuya-run-state');new MutationObserver(()=>{const translated=vietHoaLogTuya(tuyaRunState.textContent);if(translated!==tuyaRunState.textContent)tuyaRunState.textContent=translated;}).observe(tuyaRunState,{childList:true,characterData:true,subtree:true});
   async function readTuyaBackupJson(backupName){
    const name=String(backupName||'');
    if(!/^Tuya_Local_[A-Za-z0-9_-]+\.json$/.test(name)){show_message('Tên file sao lưu Tuya không hợp lệ.');return;}
    const modalElement=document.getElementById('tuyaJsonModal'),title=document.getElementById('tuyaJsonModalTitle'),message=document.getElementById('tuyaJsonModalMessage'),code=document.getElementById('tuyaJsonModalCode');
    title.textContent='Tên file: '+name;message.textContent='Đang tải nội dung JSON...';code.textContent='';
    bootstrap.Modal.getOrCreateInstance(modalElement).show();loading('show');
    try{
     const response=await fetch('Tuya_Local.php?action=preview&backup='+encodeURIComponent(name),{credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json'}});
     if(!response.ok)throw new Error(response.status===404?'Không tìm thấy file sao lưu.':'Không tải được file JSON (HTTP '+response.status+').');
     const data=await response.json();code.textContent=JSON.stringify(data,null,2);message.textContent='Đã tải nội dung bản sao lưu.';
     if(typeof window.vbotHighlightElement==='function')window.vbotHighlightElement(code);
    }catch(error){message.textContent='';show_message(error?.message||'Không đọc được nội dung JSON.');}
    finally{loading('hide');}
   }
   async function readTuyaCurrentJson(){
    const modalElement=document.getElementById('tuyaJsonModal'),title=document.getElementById('tuyaJsonModalTitle'),message=document.getElementById('tuyaJsonModalMessage'),code=document.getElementById('tuyaJsonModalCode');
    title.textContent='Dữ liệu JSON thiết bị Tuya Local hiện tại';message.textContent='Đang tải nội dung JSON...';code.textContent='';
    bootstrap.Modal.getOrCreateInstance(modalElement).show();loading('show');
    try{
     const response=await fetch('Tuya_Local.php?action=preview&target=current',{credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json'}});
     if(!response.ok)throw new Error(response.status===404?'Chưa có file cấu hình hiện tại.':'Không tải được file JSON (HTTP '+response.status+').');
     const data=await response.json();code.textContent=JSON.stringify(data,null,2);message.textContent='Đã tải cấu hình thiết bị hiện tại.';
     if(typeof window.vbotHighlightElement==='function')window.vbotHighlightElement(code);
    }catch(error){message.textContent='';show_message(error?.message||'Không đọc được nội dung JSON hiện tại.');}
    finally{loading('hide');}
   }
function renderTuyaCards(){
 const scanHost=document.getElementById('tuya-devices'),activeHost=document.getElementById('tuya-active-devices');
 const scans=scanCleared?[]:devices;
 scanHost.innerHTML=scans.map((d,i)=>{
  const id=String(d.device_id||d.id||''),configured=selectedIds.map(String).includes(id)||configuredIds.map(String).includes(id),lock=configured?'readonly disabled class="form-control border border-danger"':'class="form-control"';
  const dpEntries=Object.entries(d.tuya?.dps||{}),key=esc(d.local_key||d.credentials?.local_key||'');
  const entries=dpEntries.map(([k,e])=>{const meta=e.status??e.function??e.status_range??{},type=meta.type??meta.dp_type??'',cmd=e.commands||{on:[],off:[]};return '<div class="col-12 col-xl-6"><div class="tuya-entry-card ' + (configured?'border-danger':'') + '"><div class="d-flex align-items-center gap-2 mb-2"><span class="small fw-semibold">Kích Hoạt:</span><div class="form-check form-switch mb-0"><input class="form-check-input mt-1" type="checkbox" role="switch" aria-label="Kích hoạt DPS ' + (esc(e.dps_id||k)) + '" ' + (e.enabled?'checked':'') + ' ' + (configured?'disabled':'') + ' data-i="' + (i) + '" data-f="tuya.dps.' + (esc(k)) + '.enabled"></div></div><label class="form-label mb-1" for="scan-' + (i) + '-' + (esc(k)) + '">Tên thiết bị</label><input id="scan-' + (i) + '-' + (esc(k)) + '" ' + (lock) + ' placeholder="Ví dụ: Đèn ngủ" value="' + (esc(e.display_name||'')) + '" data-i="' + (i) + '" data-f="tuya.dps.' + (esc(k)) + '.display_name" oninput="this.classList.remove(\'is-invalid\')"><div class="tuya-device-meta mt-1 mb-2">' + (e.dps_id?'DPS '+esc(e.dps_id):'Chưa xác định DPS') + ' · ' + (esc(k)) + (type?' · '+esc(type):'') + '</div><div class="small text-danger"><b>Tùy Chỉnh Câu Lệnh Điều Khiển <i class="bi bi-question-circle-fill" onclick="show_message(\'Để trống nếu không sử dụng tùy chỉnh câu lệnh, nếu sử dụng nhiều câu lệnh thì mỗi câu lệnh là 1 dòng\')"></i></b></div><div class="row g-2"><div class="col-md-6"><label class="form-label small mb-1">Câu lệnh bật</label><textarea ' + (lock) + ' rows="3" placeholder="Bật đèn ngủ&#10;Mở đèn ngủ" data-i="' + (i) + '" data-f="tuya.dps.' + (esc(k)) + '.commands.on">' + (esc(commandText(cmd.on))) + '</textarea></div><div class="col-md-6"><label class="form-label small mb-1">Câu lệnh tắt</label><textarea ' + (lock) + ' rows="3" placeholder="Tắt đèn ngủ&#10;Đóng đèn ngủ" data-i="' + (i) + '" data-f="tuya.dps.' + (esc(k)) + '.commands.off">' + (esc(commandText(cmd.off))) + '</textarea></div></div></div></div>';}).join('');
  const scanOpen=devices.some(x=>!scanCleared&&!selectedIds.map(String).includes(String(x.device_id||x.id))&&!configuredIds.map(String).includes(String(x.device_id||x.id)));
  return '<article class="tuya-device-card ' + (configured?'is-configured':'') + '"><button class="tuya-device-card-head tuya-collapse-toggle d-flex flex-wrap align-items-center justify-content-between gap-2 ' + (scanOpen?'':'collapsed') + '" type="button" data-bs-toggle="collapse" data-bs-target="#tuya-scan-card-' + (i) + '" aria-expanded="' + (scanOpen?'true':'false') + '"><span class="d-flex align-items-center gap-2">' + (configured?'<span class="badge text-bg-danger">Đã có cấu hình</span>':'<input type="checkbox" class="form-check-input mt-0" aria-label="Chọn thiết bị ' + (esc(d.app_name||d.name||id)) + '" data-i="' + (i) + '" data-f="enabled" ' + (d.enabled||selectedIds.map(String).includes(String(d.id))?'checked':'') + ' onclick="event.stopPropagation()">') + '<span class="text-start"><strong class="d-block">' + (esc(d.app_name||d.name||'Thiết bị Tuya')) + '</strong><span class="tuya-device-meta">Tên đồng bộ từ Smart Life · ' + (esc(id)) + '</span></span></span><span class="badge ' + (configured?'text-bg-danger':'text-bg-primary') + '">' + (configured?'Đã liên kết':dpEntries.length+' mục DPS') + '</span></button><div class="collapse ' + (scanOpen?'show':'') + '" id="tuya-scan-card-' + (i) + '"><div class="tuya-device-card-body"><div class="row g-3 align-items-end"><div class="col-md-5"><label class="form-label small">Tên hiển thị trong VBot</label><input ' + (lock) + ' value="' + (esc(d.display_name||d.app_name||d.name||id)) + '" data-i="' + (i) + '" data-f="display_name"></div><div class="col-md-4"><div class="small fw-semibold">ID thiết bị</div><code>' + (esc(id)) + '</code><div class="tuya-device-meta mt-1">IP: ' + (esc(d.ip||d.network?.ip||'Chưa tìm thấy')) + ' · Giao thức v' + (esc(d.version||d.network?.protocol_version||'—')) + '</div></div><div class="col-md-3"><label class="form-label small">Local Key</label><div class="input-group"><input type="password" class="form-control tuya-secret-input ' + (configured?'border-danger':'') + '" data-i="' + (i) + '" data-f="local_key" value="' + (key) + '" ' + (configured?'readonly':'') + '><span class="input-group-text"><input type="checkbox" title="Hiện khóa cục bộ" ' + (configured?'disabled':'') + ' onchange="this.closest(\'.input-group\').querySelector(\'input[data-f=local_key]\').type=this.checked?\'text\':\'password\'"></span></div></div></div><div class="d-flex align-items-center justify-content-between mt-4 mb-2"><h6 class="mb-0">Entries và câu lệnh</h6><span class="small text-muted">' + (dpEntries.length) + ' mục · cấu hình bật/tắt riêng</span></div>' + (entries?'<div class="row g-3">' + (entries) + '</div>':'<div class="alert alert-light border mb-0">Thiết bị chưa cung cấp metadata DPS để cấu hình.</div>') + (configured?'<div class="small text-danger mt-3">Thiết bị đã liên kết. Các trường bị khóa để tránh ghi đè cấu hình điều khiển.</div>':'') + '</div></div></article>';
 }).join('')||'<div class="text-center text-muted border rounded p-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>' + (scanCleared?'Chưa có dữ liệu quét tạm. Hãy đăng nhập hoặc quét lại.':'Chưa tìm thấy thiết bị mới để cấu hình.') + '</div>';
 const active=devices.filter(d=>selectedIds.map(String).includes(String(d.id)));
  activeHost.innerHTML=active.map((d,index)=>{
  const did=String(d.id),dps=Object.entries(d.tuya?.dps||{}),allOn=dps.length>0&&dps.every(([,e])=>e.enabled),account=tuyaAccounts[String(d.account_id||'')];
  const entries=dps.map(([k,e])=>{const c=e.commands||{};return '<div class="col-12 col-xl-6"><div class="tuya-entry-card"><div class="d-flex align-items-center gap-2 mb-2"><span class="small fw-semibold">Kích Hoạt:</span><div class="form-check form-switch mb-0"><input class="form-check-input mt-1" type="checkbox" role="switch" aria-label="Kích hoạt DPS ' + (esc(e.dps_id||k)) + '" ' + (e.enabled?'checked':'') + ' data-id="' + (esc(did)) + '" data-f="dps.' + (esc(k)) + '.enabled"></div></div><label class="form-label small mb-1">Tên thiết bị</label><input class="form-control fw-semibold mb-2" placeholder="Ví dụ: Đèn ngủ" aria-label="Tên thiết bị cho DPS ' + (esc(e.dps_id||k)) + '" value="' + (esc(e.display_name||'')) + '" data-id="' + (esc(did)) + '" data-f="dps.' + (esc(k)) + '.display_name" oninput="this.classList.remove(\'is-invalid\')"><div class="tuya-device-meta mb-2">DPS ' + (esc(e.dps_id||'chưa xác định')) + ' <span class="text-muted">· khóa nội bộ ' + (esc(k)) + '</span> · <span class="badge ' + (e.enabled?'text-bg-success':'text-bg-secondary') + '">' + (e.enabled?'Đang bật':'Đang tắt') + '</span></div><hr/><div class="small text-danger"><b>Tùy Chỉnh Câu Lệnh Điều Khiển <i class="bi bi-question-circle-fill" onclick="show_message(\'Để trống nếu không sử dụng tùy chỉnh câu lệnh, nếu sử dụng nhiều câu lệnh thì mỗi câu lệnh là 1 dòng\')"></i></b></div><div class="row g-2"><div class="col-md-6"><label class="form-label small">Câu lệnh bật</label><textarea class="form-control" rows="3" placeholder="Bật đèn ngủ&#10;Mở đèn ngủ" data-id="' + (esc(did)) + '" data-f="dps.' + (esc(k)) + '.on">' + (esc(commandText(c.on))) + '</textarea></div><div class="col-md-6"><label class="form-label small">Câu lệnh tắt</label><textarea class="form-control" rows="3" placeholder="Tắt đèn ngủ&#10;Đóng đèn ngủ" data-id="' + (esc(did)) + '" data-f="dps.' + (esc(k)) + '.off">' + (esc(commandText(c.off))) + '</textarea></div></div></div></div>';}).join('');
  const alertColors=['primary','success','warning','info','secondary','danger'];
  return '<article class="tuya-device-card tuya-active-device-card alert alert-' + (alertColors[index%alertColors.length]) + '"><button class="tuya-device-card-head tuya-collapse-toggle d-flex flex-wrap align-items-center justify-content-between gap-2 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tuya-active-card-' + (esc(did)) + '" aria-expanded="false"><span class="text-start"><strong class="d-block">' + (esc(d.display_name||d.id)) + '</strong><span class="tuya-device-meta">Tuya Smart/Smart Life: ' + (esc(d.app_name||d.name||'Chưa có tên')) + ' · ' + (esc(did)) + '</span></span><span class="badge ' + (allOn?'text-bg-success':'text-bg-secondary') + '">' + (dps.filter(([,e])=>e.enabled).length) + '/' + (dps.length) + ' thiết bị DPS đang bật</span></button><div class="collapse" id="tuya-active-card-' + (esc(did)) + '"><div class="tuya-device-card-body"><div class="row g-3 align-items-end"><div class="col-md-5"><label class="form-label small">Tên hiển thị trong VBot</label><input class="form-control" value="' + (esc(d.display_name||d.id)) + '" data-id="' + (esc(did)) + '" data-f="display_name"></div><div class="col-md-3"><label class="form-label small">Địa chỉ IP</label><input class="form-control" value="' + (esc(d.ip||d.network?.ip||'')) + '" placeholder="192.168.x.x" data-id="' + (esc(did)) + '" data-f="ip"></div><div class="col-md-4"><label class="form-label small">Local Key</label><div class="input-group"><input type="password" class="form-control tuya-secret-input" data-id="' + (esc(did)) + '" data-f="local_key" value="' + (esc(d.local_key||d.credentials?.local_key||'')) + '"><span class="input-group-text"><input type="checkbox" title="Hiện khóa cục bộ" onchange="this.closest(\'.input-group\').querySelector(\'input[data-f=local_key]\').type=this.checked?\'text\':\'password\'"></span></div></div><div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2"><div class="small text-muted">ID: <code>' + (esc(d.device_id||did)) + '</code> | Tuya Smart/Smart Life User Code: <code>' + (esc(account?.user_code||d.user_code||'Chưa gắn')) + '</code></div><div class="d-flex flex-wrap align-items-center gap-3"><label class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" ' + (allOn?'checked':'') + ' onchange="setDeviceEntriesEnabled(\'' + (esc(did)) + '\',this.checked)"><span class="form-check-label">Kích hoạt tất cả thiết bị DPS</span></label><button type="button" class="btn btn-sm btn-danger" onclick="deleteTuyaDevice(\'' + (esc(did)) + '\')"><i class="bi bi-trash"></i> Xóa liên kết thiết bị</button></div></div></div><hr><h6 class="mb-2">Cấu hình từng thiết bị, Entries</h6>' + (entries?'<div class="row g-3">' + (entries) + '</div>':'<div class="alert alert-light border">Thiết bị chưa có Entries để cấu hình.</div>') + '</div></div></article>';
 }).join('')||'<div class="text-center text-muted border rounded p-4"><i class="bi bi-lightbulb fs-3 d-block mb-2"></i>Chưa có thiết bị được liên kết. Chọn thiết bị ở phần quét tạm phía trên.</div>';
}
   async function tuyaOpenStream(relogin){
    const modal=bootstrap.Modal.getOrCreateInstance(document.getElementById('tuyaRunModal'));
    const box=document.getElementById('tuya-qr-box'),img=document.getElementById('tuya-qr');
    const log=document.getElementById('tuya-log'),state=document.getElementById('tuya-run-state');
    loading('show'); modal.show(); box.style.display='none'; img.removeAttribute('src'); log.textContent='';
    state.className='alert alert-info'; state.textContent=relogin?'Đang tạo phiên quét QR mới...':'Đang làm mới dữ liệu và quét IP trong mạng LAN...';
    if(window.tuyaSse){window.tuyaSse.close();window.tuyaSse=null;}
    const ticketResult=await post('stream_ticket',{relogin:relogin?'1':'0',user_code:relogin?document.getElementById('tuya-user-code').value.trim():'',subnet:document.getElementById('tuya-subnet').value.trim()});
    if(!ticketResult.ok){loading('hide');state.className='alert alert-danger';state.textContent=ticketResult.error||'Không tạo được phiên SSE.';msg(state.textContent,'danger');return;}
    const source=new EventSource('Tuya_Local.php?action=stream&ticket='+encodeURIComponent(ticketResult.ticket));
    window.tuyaSse=source;
    source.onmessage=event=>{
     let data;try{data=JSON.parse(event.data);}catch(_){return;}
     if(data.type==='output'){
      const line=typeof vietHoaLogTuya==='function'?vietHoaLogTuya(data.line):data.line;
      if(line.includes('Waiting for you to confirm')){loading('hide');state.textContent='Đang chờ xác nhận đăng nhập trong Tuya Smart/Smart Life...';}
      else if(line.includes('Logged in.')){loading('show');state.textContent='Đăng nhập thành công; đang lấy dữ liệu thiết bị và quét IP LAN...';}
      log.textContent+=line;log.scrollTop=log.scrollHeight;
     }else if(data.type==='qr'&&data.data){loading('hide');box.style.display='block';img.src='data:image/png;base64,'+data.data;state.textContent='Mã QR mới đã tạo. Hãy quét và xác nhận đăng nhập trong Tuya Smart/Smart Life...';}
     else if(data.type==='error'){source.close();loading('hide');state.className='alert alert-danger';state.textContent=data.message||'Có lỗi khi chạy tiến trình Tuya.';msg(state.textContent,'danger');}
     else if(data.type==='done'){
      source.close();loading('hide');if(Array.isArray(data.devices)){devices=data.devices;render();}
      state.className='alert alert-'+(data.saved?'success':'warning');
      state.textContent=data.saved?'Đã lưu đầy đủ dữ liệu JSON scan và cập nhật danh sách thiết bị.':(data.save_error||'Tiến trình kết thúc nhưng chưa lưu đầy đủ dữ liệu scan.');
      if(data.saved)msg('Dữ liệu scan đã lưu. Chọn thiết bị, đặt tên và cấu hình câu lệnh cho từng DPS rồi lưu.','success');
      else msg(state.textContent,'danger');
     }
    };
    source.onerror=()=>{source.close();loading('hide');state.className='alert alert-danger';state.textContent='Mất kết nối SSE tới Pi. Khi tải lại trang hoặc đóng kết nối, tiến trình quét được dừng theo cơ chế hiện tại.';};
   }
   tuyaStart=function(){scanCleared=false;tuyaOpenStream(true);};
   tuyaRefresh=function(){scanCleared=false;tuyaOpenStream(false);};
    render=renderTuyaCards;
   const renderTuyaCardsWithCustomTts=render;
   render=function(){
    renderTuyaCardsWithCustomTts();
    const linked=devices.filter(d=>selectedIds.map(String).includes(String(d.id)));
    document.querySelectorAll('#tuya-active-devices .tuya-device-card').forEach((card,index)=>{
     const device=linked[index],body=card.querySelector('.tuya-device-card-body');
     if(!device||!body||body.querySelector('[data-f="tts_response"]'))return;
     const field=document.createElement('div');field.className='mb-3';
     const label=document.createElement('label');label.className='form-label';label.textContent='Câu phản hồi TTS tùy chỉnh';
     const textarea=document.createElement('textarea');textarea.className='form-control';textarea.rows=2;textarea.maxLength=1000;
     textarea.placeholder='Để trống để dùng câu phản hồi mặc định';textarea.value=device.tts_response||'';
     textarea.dataset.id=String(device.id);textarea.dataset.f='tts_response';
     const help=document.createElement('div');help.className='form-text';help.textContent='Chỉ phát câu này khi điều khiển thiết bị thành công. Để trống để dùng câu mặc định.';
     field.append(label,textarea,help);
     const separator=body.querySelector('hr');if(separator)body.insertBefore(field,separator);else body.appendChild(field);
    });
   };
   render=renderTuyaCards;
   const renderWithEntryTts=render;
   render=function(){
    renderWithEntryTts();
    document.querySelectorAll('#tuya-devices .tuya-entry-card, #tuya-active-devices .tuya-entry-card').forEach(card=>{
     const anchor=card.querySelector('[data-f$=".enabled"]');if(!anchor)return;
     const isScan=anchor.dataset.i!==undefined,parts=anchor.dataset.f.split('.'),key=isScan?parts[2]:parts[1];
     const device=isScan?devices[Number(anchor.dataset.i)]:devices.find(d=>String(d.id)===String(anchor.dataset.id));
      const entry=device?.tuya?.dps?.[key];if(!entry)return;
     const legacy=card.querySelector('[data-f="tts_response"]');if(legacy)legacy.closest('.mb-3')?.remove();
     if(card.querySelector('[data-f*=".tts_response."]'))return;
      const wrap=document.createElement('div');wrap.className='mt-3';
      const heading=document.createElement('div');heading.className='form-label small fw-semibold';heading.textContent='Câu phản hồi TTS tùy chỉnh';wrap.appendChild(heading);
      const columns=document.createElement('div');columns.className='row g-2';
      const responses=entry.tts_response&&typeof entry.tts_response==='object'?entry.tts_response:{};
      [['on','Bật thành công'],['off','Tắt thành công']].forEach(([direction,title])=>{
       const column=document.createElement('div');column.className='col-12 col-md-6';
       const label=document.createElement('label');label.className='form-label small mb-1';label.textContent=title;
       const area=document.createElement('textarea');area.className='form-control';area.rows=3;area.maxLength=1000;area.placeholder='Để trống để dùng phản hồi mặc định';area.value=responses[direction]||(typeof entry.tts_response==='string'?entry.tts_response:device.tts_response||'');
       area.dataset[isScan?'i':'id']=isScan?anchor.dataset.i:String(device.id);
       area.dataset.f=isScan?'tuya.dps.' + (key) + '.tts_response.' + (direction):'dps.' + (key) + '.tts_response.' + (direction);
       column.append(label,area);columns.appendChild(column);
      });
      wrap.appendChild(columns);
      card.appendChild(wrap);
    });
   };
   const renderBeforeFloatingTextareas=render;
   render=function(){
    renderBeforeFloatingTextareas();
    document.querySelectorAll('#tuya-active-devices .tuya-entry-card').forEach(card=>{
     const meta=card.querySelector('.tuya-device-meta.mb-2');
     const activation=card.querySelector('.d-flex.align-items-center.gap-2.mb-2');
     if(meta&&activation)card.insertBefore(meta,activation);
    });
    document.querySelectorAll('#tuya-devices .tuya-entry-card, #tuya-active-devices .tuya-entry-card').forEach(card=>{
     const ttsWrap=[...card.children].find(child=>child.querySelector('.form-label')?.textContent?.trim().includes('TTS'));
     if(ttsWrap){
      const heading=ttsWrap.querySelector('.form-label');
      if(heading){
       heading.className='small fw-semibold text-danger';
       heading.replaceChildren(document.createTextNode('Tùy chỉnh câu phản hồi '));
       const icon=document.createElement('i');icon.className='bi bi-question-circle-fill';
       icon.setAttribute('onclick',"show_message('Tùy chỉnh câu phản hồi theo ý muốn khi thực thi thành công')");
       heading.appendChild(icon);
      }
     }
     card.querySelectorAll('textarea').forEach(area=>{
      if(area.closest('.form-floating'))return;
      const label=area.previousElementSibling;
      if(!label||label.tagName!=='LABEL')return;
      const parent=area.parentElement, floating=document.createElement('div');
      floating.className='form-floating mb-3';
      area.classList.add('form-control');
      if(!area.classList.contains('border-danger'))area.classList.add('border-success');
      area.style.height='120px';
      if(!area.placeholder)area.placeholder=label.textContent.trim()||'Nhập nội dung';
      label.className='';
      parent.insertBefore(floating,label);
      floating.append(area,label);
     });
    });
   };
   render();
</script><script src="assets/js/page-search-navigation.js?v=<?php echo $Cache_UI_Ver; ?>"></script></body></html>

