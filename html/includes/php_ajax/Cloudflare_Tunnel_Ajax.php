<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
include __DIR__.'/../../Configuration.php';

if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!isset($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200)) {
        session_unset();
        session_destroy();
        vbotApiJsonResponse(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.'], 401);
    }
    $_SESSION['user_login']['login_time'] = time();
}
vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

$storeDir = dirname(__DIR__, 3).'/resource/cloudflare_tunnel';
$storeFile = $storeDir.'/profiles.json';

function cfResponse($data, $status = 200) {
    vbotApiJsonResponse($data, $status);
}

function cfLoadProfiles($file) {
    if (!is_file($file)) return ['active_profile' => '', 'profiles' => []];
    $data = json_decode((string) @file_get_contents($file), true);
    if (!is_array($data)) return ['active_profile' => '', 'profiles' => []];
    return [
        'active_profile' => isset($data['active_profile']) ? (string) $data['active_profile'] : '',
        'profiles' => isset($data['profiles']) && is_array($data['profiles']) ? array_values($data['profiles']) : []
    ];
}

function cfSaveProfiles($dir, $file, $data) {
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) return false;
    @chmod($dir, 0777);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    $tmp = $file.'.tmp.'.getmypid();
    if (@file_put_contents($tmp, $json."\n", LOCK_EX) === false) return false;
    @chmod($tmp, 0777);
    if (!@rename($tmp, $file)) { @unlink($tmp); return false; }
    @chmod($file, 0777);
    return true;
}

function cfProfileIndex($profiles, $id) {
    foreach ($profiles as $index => $profile) {
        if (isset($profile['id']) && hash_equals((string) $profile['id'], (string) $id)) return $index;
    }
    return -1;
}

function cfConnect($host, $port, $user, $password) {
    if (!function_exists('ssh2_connect')) cfResponse(['success' => false, 'message' => 'PHP chưa có extension SSH2.'], 500);
    $connection = @ssh2_connect($host, $port);
    if (!$connection || !@ssh2_auth_password($connection, $user, $password)) {
        cfResponse(['success' => false, 'message' => 'Không thể xác thực SSH tới hệ thống.'], 502);
    }
    return $connection;
}

function cfExec($connection, $command, $timeout = 25) {
    $marker = '__VBOT_CF_EXIT__';
    $wrapped = 'sh -lc '.escapeshellarg($command.' 2>&1; code=$?; printf "\\n'.$marker.'%s" "$code"');
    $stream = @ssh2_exec($connection, $wrapped);
    if (!$stream) return ['ok' => false, 'exit_code' => -1, 'output' => 'Không thể thực thi lệnh SSH.'];
    stream_set_blocking($stream, false);
    $output = '';
    $started = microtime(true);
    while (!feof($stream)) {
        $chunk = fread($stream, 8192);
        if ($chunk !== false && $chunk !== '') $output .= $chunk;
        if (microtime(true) - $started > $timeout) { fclose($stream); return ['ok' => false, 'exit_code' => 124, 'output' => 'Lệnh quá thời gian cho phép.']; }
        usleep(20000);
    }
    fclose($stream);
    $exitCode = -1;
    if (preg_match('/\\n'.$marker.'(\\d+)\\s*$/', $output, $match)) {
        $exitCode = (int) $match[1];
        $output = preg_replace('/\\n'.$marker.'\\d+\\s*$/', '', $output);
    }
    return ['ok' => $exitCode === 0, 'exit_code' => $exitCode, 'output' => trim($output)];
}

function cfWriteRemoteFile($connection, $path, $content) {
    $encoded = base64_encode($content);
    return cfExec($connection, 'printf %s '.escapeshellarg($encoded).' | base64 -d | sudo tee '.escapeshellarg($path).' >/dev/null && sudo chmod 644 '.escapeshellarg($path));
}

function cfSystemdArgument($value) {
    $value = str_replace(['\\', '"', '%'], ['\\\\', '\\"', '%%'], (string) $value);
    return '"'.$value.'"';
}

function cfCleanProfile($input, $existingId = '') {
    $name = trim(isset($input['name']) ? (string) $input['name'] : '');
    $mode = isset($input['mode']) ? (string) $input['mode'] : '';
    $localUrl = trim(isset($input['local_url']) ? (string) $input['local_url'] : 'http://127.0.0.1:80');
    $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
    if ($name === '' || $nameLength > 80) cfResponse(['success' => false, 'message' => 'Tên hồ sơ phải từ 1 đến 80 ký tự.'], 400);
    if (!in_array($mode, ['quick', 'domain'], true)) cfResponse(['success' => false, 'message' => 'Chế độ tunnel không hợp lệ.'], 400);
    if (!filter_var($localUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $localUrl)) cfResponse(['success' => false, 'message' => 'URL dịch vụ nội bộ không hợp lệ.'], 400);
    $profile = [
        'id' => $existingId !== '' ? $existingId : bin2hex(random_bytes(8)),
        'name' => $name,
        'mode' => $mode,
        'local_url' => $localUrl,
        'hostname' => '',
        'tunnel' => '',
        'credentials_file' => '',
        'auto_start' => !empty($input['auto_start']),
        'updated_at' => date('c')
    ];
    if ($mode === 'domain') {
        $profile['hostname'] = strtolower(trim(isset($input['hostname']) ? (string) $input['hostname'] : ''));
        $profile['tunnel'] = trim(isset($input['tunnel']) ? (string) $input['tunnel'] : '');
        $profile['credentials_file'] = trim(isset($input['credentials_file']) ? (string) $input['credentials_file'] : '');
        if (!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,63}$/', $profile['hostname'])) cfResponse(['success' => false, 'message' => 'Domain/hostname không hợp lệ.'], 400);
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,127}$/', $profile['tunnel'])) cfResponse(['success' => false, 'message' => 'Tên hoặc UUID tunnel không hợp lệ.'], 400);
        if (!preg_match('#^/[-A-Za-z0-9_./]+\\.json$#', $profile['credentials_file']) || strpos($profile['credentials_file'], '..') !== false) cfResponse(['success' => false, 'message' => 'Đường dẫn credentials JSON không hợp lệ.'], 400);
    }
    return $profile;
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
$data = cfLoadProfiles($storeFile);

if (!is_file($storeFile) && !cfSaveProfiles($storeDir, $storeFile, $data)) {
    cfResponse(['success' => false, 'message' => 'Không thể khởi tạo kho cấu hình Cloudflare Tunnel.'], 500);
}

if ($action === 'list') cfResponse(['success' => true, 'data' => $data]);

if ($action === 'save') {
    $raw = isset($_POST['profile']) ? json_decode((string) $_POST['profile'], true) : null;
    if (!is_array($raw)) cfResponse(['success' => false, 'message' => 'Dữ liệu hồ sơ không hợp lệ.'], 400);
    $requestedId = isset($raw['id']) ? (string) $raw['id'] : '';
    $index = $requestedId !== '' ? cfProfileIndex($data['profiles'], $requestedId) : -1;
    if ($requestedId !== '' && $index < 0) cfResponse(['success' => false, 'message' => 'Không tìm thấy hồ sơ cần sửa.'], 404);
    $profile = cfCleanProfile($raw, $requestedId);
    if ($index >= 0) $data['profiles'][$index] = $profile; else $data['profiles'][] = $profile;
    if (!cfSaveProfiles($storeDir, $storeFile, $data)) cfResponse(['success' => false, 'message' => 'Không thể lưu dữ liệu Cloudflare Tunnel.'], 500);
    cfResponse(['success' => true, 'message' => $index >= 0 ? 'Đã cập nhật hồ sơ tunnel.' : 'Đã thêm hồ sơ tunnel.', 'data' => $profile]);
}

if ($action === 'delete') {
    $id = isset($_POST['id']) ? (string) $_POST['id'] : '';
    $index = cfProfileIndex($data['profiles'], $id);
    if ($index < 0) cfResponse(['success' => false, 'message' => 'Không tìm thấy hồ sơ.'], 404);
    if ($data['active_profile'] === $id) cfResponse(['success' => false, 'message' => 'Hãy dừng tunnel đang hoạt động trước khi xóa hồ sơ.'], 409);
    array_splice($data['profiles'], $index, 1);
    if (!cfSaveProfiles($storeDir, $storeFile, $data)) cfResponse(['success' => false, 'message' => 'Không thể xóa hồ sơ.'], 500);
    cfResponse(['success' => true, 'message' => 'Đã xóa hồ sơ cục bộ. Tunnel và DNS trên Cloudflare không bị xóa.']);
}

$connection = cfConnect($ssh_host, $ssh_port, $ssh_user, $ssh_password);

if ($action === 'status') {
    $findBinary = 'CF_BIN=$(command -v cloudflared 2>/dev/null || true); for CF_PATH in /usr/local/bin/cloudflared /usr/bin/cloudflared /bin/cloudflared; do if [ -z "$CF_BIN" ] && [ -x "$CF_PATH" ]; then CF_BIN="$CF_PATH"; fi; done';
    $result = cfExec($connection, $findBinary.'; if [ -n "$CF_BIN" ] && [ -x "$CF_BIN" ]; then printf "%s\\n" "$CF_BIN"; "$CF_BIN" --version; else printf "CLOUDFLARED_NOT_INSTALLED"; fi; printf "\\n--- SERVICE ---\\n"; (systemctl is-enabled cloudflared.service 2>/dev/null || true); (systemctl is-active cloudflared.service 2>/dev/null || true); printf "\\n--- URL ---\\n"; (journalctl -u cloudflared.service -n 80 --no-pager 2>/dev/null | grep -Eo "https://[-a-z0-9]+\\.trycloudflare\\.com" | tail -1 || true)');
    $cloudflaredInstalled = strpos($result['output'], 'CLOUDFLARED_NOT_INSTALLED') === false;
    $serviceIsActive = preg_match('/(?:^|\\R)active(?:\\R|$)/', $result['output']) === 1;
    $publicUrl = '';
    if (preg_match('#https://[-a-z0-9]+\\.trycloudflare\\.com#i', $result['output'], $urlMatch)) {
        $publicUrl = $urlMatch[0];
    }
    if ($serviceIsActive && $data['active_profile'] === '' && count($data['profiles']) === 1) {
        $data['active_profile'] = (string) $data['profiles'][0]['id'];
        cfSaveProfiles($storeDir, $storeFile, $data);
    } elseif (!$serviceIsActive && $data['active_profile'] !== '') {
        $data['active_profile'] = '';
        cfSaveProfiles($storeDir, $storeFile, $data);
    }
    cfResponse([
        'success' => $result['exit_code'] === 0,
        'message' => $cloudflaredInstalled ? ($result['output'] !== '' ? $result['output'] : 'Không lấy được trạng thái cloudflared.') : 'Cloudflared chưa được cài đặt. Hãy mở mục hướng dẫn để cài đặt trước khi kích hoạt tunnel.',
        'data' => $data,
        'runtime' => ['installed' => $cloudflaredInstalled, 'active' => $serviceIsActive, 'public_url' => $publicUrl]
    ], $result['exit_code'] === 0 ? 200 : 500);
}

$id = isset($_POST['id']) ? (string) $_POST['id'] : '';
$index = cfProfileIndex($data['profiles'], $id);
if ($index < 0 && in_array($action, ['activate', 'check'], true)) cfResponse(['success' => false, 'message' => 'Không tìm thấy hồ sơ tunnel.'], 404);

if ($action === 'check') {
    $profile = $data['profiles'][$index];
    $findBinary = 'CF_BIN=$(command -v cloudflared 2>/dev/null || true); for CF_PATH in /usr/local/bin/cloudflared /usr/bin/cloudflared /bin/cloudflared; do if [ -z "$CF_BIN" ] && [ -x "$CF_PATH" ]; then CF_BIN="$CF_PATH"; fi; done; [ -n "$CF_BIN" ] && [ -x "$CF_BIN" ]';
    $command = $findBinary.' && "$CF_BIN" --version';
    if ($profile['mode'] === 'domain') {
        $command .= ' && test -r '.escapeshellarg($profile['credentials_file']).' && "$CF_BIN" tunnel info '.escapeshellarg($profile['tunnel']);
    } else {
        $command .= ' && printf "\\n--- SERVICE ---\\n"'
            .' && (systemctl is-enabled cloudflared.service 2>/dev/null || true)'
            .' && (systemctl is-active cloudflared.service 2>/dev/null || true)'
            .' && printf "\\n--- EXEC ---\\n"'
            .' && (systemctl show cloudflared.service --property=ExecStart --value 2>/dev/null || true)'
            .' && printf "\\n--- URL ---\\n"'
            .' && (journalctl -u cloudflared.service -n 80 --no-pager 2>/dev/null | grep -Eo "https://[-a-z0-9]+\\.trycloudflare\\.com" | tail -1 || true)';
    }
    $result = cfExec($connection, $command, 35);
    $serviceIsActive = preg_match('/(?:^|\\R)active(?:\\R|$)/', $result['output']) === 1;
    $serviceMatchesProfile = strpos($result['output'], (string) $profile['local_url']) !== false;
    $publicUrl = '';
    if (preg_match('#https://[-a-z0-9]+\\.trycloudflare\\.com#i', $result['output'], $urlMatch)) $publicUrl = $urlMatch[0];
    if ($profile['mode'] === 'quick' && $serviceIsActive && $serviceMatchesProfile) {
        $data['active_profile'] = $id;
        cfSaveProfiles($storeDir, $storeFile, $data);
    }
    cfResponse([
        'success' => $result['ok'],
        'message' => $result['output'] ?: 'Kiểm tra hoàn tất.',
        'data' => $data,
        'runtime' => ['active' => $serviceIsActive, 'public_url' => $publicUrl]
    ], $result['ok'] ? 200 : 500);
}

if ($action === 'activate') {
    $profile = $data['profiles'][$index];
    $service = "[Unit]\nDescription=VBot Cloudflare Tunnel\nAfter=network-online.target\nWants=network-online.target\n\n[Service]\nType=simple\n";
    if ($profile['mode'] === 'quick') {
        $service .= 'ExecStart=/usr/bin/env cloudflared tunnel --no-autoupdate --url '.cfSystemdArgument($profile['local_url'])."\n";
    } else {
        $yamlQuote = function ($value) { return "'".str_replace("'", "''", $value)."'"; };
        $yaml = 'tunnel: '.$yamlQuote($profile['tunnel'])."\ncredentials-file: ".$yamlQuote($profile['credentials_file'])."\ningress:\n  - hostname: ".$yamlQuote($profile['hostname'])."\n    service: ".$yamlQuote($profile['local_url'])."\n  - service: http_status:404\n";
        $writeConfig = cfExec($connection, 'sudo mkdir -p /home/pi/Cloud_Flare');
        if (!$writeConfig['ok']) cfResponse(['success' => false, 'message' => $writeConfig['output']], 500);
        $writeConfig = cfWriteRemoteFile($connection, '/home/pi/Cloud_Flare/config.yml', $yaml);
        if (!$writeConfig['ok']) cfResponse(['success' => false, 'message' => $writeConfig['output']], 500);
        $service .= "ExecStart=/usr/bin/env cloudflared tunnel --no-autoupdate --config /home/pi/Cloud_Flare/config.yml run\n";
    }
    $service .= "Restart=on-failure\nRestartSec=5\nTimeoutStartSec=20\nTimeoutStopSec=10\nKillMode=mixed\nFinalKillSignal=SIGKILL\n\n[Install]\nWantedBy=multi-user.target\n";
    $writeService = cfWriteRemoteFile($connection, '/etc/systemd/system/cloudflared.service', $service);
    if (!$writeService['ok']) cfResponse(['success' => false, 'message' => $writeService['output']], 500);
    $systemCommand = 'sudo systemctl daemon-reload && ';
    $systemCommand .= $profile['auto_start'] ? 'sudo systemctl enable cloudflared.service && ' : 'sudo systemctl disable cloudflared.service >/dev/null 2>&1 || true; ';
    $systemCommand .= 'timeout 25s sudo systemctl restart cloudflared.service && sleep 2 && systemctl is-active cloudflared.service';
    $result = cfExec($connection, $systemCommand, 40);
    if (!$result['ok']) cfResponse(['success' => false, 'message' => $result['output'] ?: 'Không thể khởi động cloudflared.'], 500);
    $data['active_profile'] = $id;
    cfSaveProfiles($storeDir, $storeFile, $data);
    cfResponse(['success' => true, 'message' => 'Đã kích hoạt hồ sơ '.$profile['name'].'.', 'output' => $result['output']]);
}

if ($action === 'stop') {
    $result = cfExec($connection, 'sudo systemctl stop cloudflared.service');
    if ($result['ok']) { $data['active_profile'] = ''; cfSaveProfiles($storeDir, $storeFile, $data); }
    cfResponse(['success' => $result['ok'], 'message' => $result['ok'] ? 'Đã dừng Cloudflare Tunnel.' : ($result['output'] ?: 'Không thể dừng Cloudflare Tunnel.')], $result['ok'] ? 200 : 500);
}

cfResponse(['success' => false, 'message' => 'Thao tác không được hỗ trợ.'], 400);
