<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
@set_time_limit(120);
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
    $tmp = $file.'.tmp.'.bin2hex(random_bytes(6));
    if (@file_put_contents($tmp, $json."\n", LOCK_EX) === false) return false;
    @chmod($tmp, 0777);
    if (!@rename($tmp, $file)) { @unlink($tmp); return false; }
    @chmod($file, 0777);
    return true;
}

function cfPublicData($data) {
    $public = $data;
    foreach ($public['profiles'] as &$profile) {
        $profile['api_token_set'] = !empty($profile['api_token']);
        $profile['global_api_key_set'] = !empty($profile['global_api_key']) && !empty($profile['api_email']);
        $profile['auth_type'] = isset($profile['auth_type']) ? (string) $profile['auth_type'] : (!empty($profile['api_token']) ? 'token' : (!empty($profile['global_api_key']) ? 'global' : 'none'));
        unset($profile['api_token']);
        unset($profile['global_api_key']);
    }
    unset($profile);
    return $public;
}

function cfApiRequest($method, $path, $auth) {
    if (!function_exists('curl_init')) cfResponse(['success' => false, 'message' => 'PHP chưa có extension cURL để gọi Cloudflare API.'], 500);
    $curl = curl_init('https://api.cloudflare.com/client/v4'.$path);
    $headers = ['Content-Type: application/json'];
    if (isset($auth['type']) && $auth['type'] === 'global') {
        $headers[] = 'X-Auth-Email: '.$auth['email'];
        $headers[] = 'X-Auth-Key: '.$auth['key'];
    } else {
        $headers[] = 'Authorization: Bearer '.$auth['key'];
    }
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    $json = is_string($body) ? json_decode($body, true) : null;
    if ($error !== '' || !is_array($json) || $status < 200 || $status >= 300 || empty($json['success'])) {
        $message = $error !== '' ? $error : 'Cloudflare API từ chối yêu cầu (HTTP '.$status.').';
        if (is_array($json) && !empty($json['errors'][0]['message'])) $message = (string) $json['errors'][0]['message'];
        return ['ok' => false, 'message' => $message];
    }
    return ['ok' => true, 'data' => $json];
}

function cfFindZone($hostname, $auth) {
    $labels = explode('.', strtolower($hostname));
    for ($offset = 0; $offset < count($labels) - 1; $offset++) {
        $candidate = implode('.', array_slice($labels, $offset));
        $response = cfApiRequest('GET', '/zones?name='.rawurlencode($candidate).'&status=active&per_page=1', $auth);
        if (!$response['ok']) return $response;
        if (!empty($response['data']['result'][0]['id'])) return ['ok' => true, 'zone_id' => (string) $response['data']['result'][0]['id'], 'zone_name' => $candidate];
    }
    return ['ok' => false, 'message' => 'Không tìm thấy Zone Cloudflare chứa hostname '.$hostname.'.'];
}

function cfDeleteTunnelDns($hostname, $auth) {
    $zone = cfFindZone($hostname, $auth);
    if (!$zone['ok']) return $zone;
    $list = cfApiRequest('GET', '/zones/'.rawurlencode($zone['zone_id']).'/dns_records?type=CNAME&name='.rawurlencode($hostname).'&per_page=100', $auth);
    if (!$list['ok']) return $list;
    $deleted = 0;
    foreach (!empty($list['data']['result']) && is_array($list['data']['result']) ? $list['data']['result'] : [] as $record) {
        $content = strtolower(isset($record['content']) ? (string) $record['content'] : '');
        if (!preg_match('/\.cfargotunnel\.com\.?$/', $content)) continue;
        $remove = cfApiRequest('DELETE', '/zones/'.rawurlencode($zone['zone_id']).'/dns_records/'.rawurlencode((string) $record['id']), $auth);
        if (!$remove['ok']) return $remove;
        $deleted++;
    }
    return ['ok' => true, 'deleted' => $deleted, 'zone_name' => $zone['zone_name']];
}

function cfProfileIndex($profiles, $id) {
    foreach ($profiles as $index => $profile) {
        if (isset($profile['id']) && hash_equals((string) $profile['id'], (string) $id)) return $index;
    }
    return -1;
}

function cfRuntimeProfileId($profiles, $runtimeOutput) {
    $runtimeOutput = (string)$runtimeOutput;
    // Named Tunnel có config.yml. Phải đối chiếu tunnel/hostname trước vì URL
    // dịch vụ nội bộ thường giống hồ sơ Quick (ví dụ http://127.0.0.1:80).
    if (preg_match('/(?:^|\R)tunnel:\s*[\'\"]?([^\'\"\s]+)[\'\"]?/i', $runtimeOutput, $match)) {
        $runtimeTunnel = trim($match[1]);
        foreach ($profiles as $profile) {
            if (($profile['mode'] ?? '') === 'domain' && hash_equals((string)($profile['tunnel'] ?? ''), $runtimeTunnel))
                return (string)$profile['id'];
        }
    }
    foreach ($profiles as $profile) {
        if (($profile['mode'] ?? '') !== 'domain') continue;
        $hostname = (string)($profile['hostname'] ?? '');
        if ($hostname !== '' && preg_match('/(?:^|\R)\s*-?\s*hostname:\s*[\'\"]?'.preg_quote($hostname, '/').'[\'\"]?(?:\R|$)/i', $runtimeOutput))
            return (string)$profile['id'];
    }
    // Quick Tunnel chỉ hợp lệ khi chính ExecStart chứa --url; không tìm URL
    // trên toàn output vì URL đó cũng xuất hiện dưới ingress của Named Tunnel.
    if (preg_match('/--- EXEC ---\R(.*?)(?:\R---|$)/s', $runtimeOutput, $execMatch)) {
        $exec = $execMatch[1];
        foreach ($profiles as $profile) {
            $localUrl = (string)($profile['local_url'] ?? '');
            if (($profile['mode'] ?? '') === 'quick' && $localUrl !== ''
                    && preg_match('/--url(?:=|\s+)[\'\"]?'.preg_quote($localUrl, '/').'(?=[\'\"\s;}]|$)/', $exec))
                return (string)$profile['id'];
        }
    }
    return '';
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

function cfCleanProfile($input, $existingId = '', $existingProfile = null) {
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
        'api_token' => is_array($existingProfile) && isset($existingProfile['api_token']) ? (string) $existingProfile['api_token'] : '',
        'api_email' => is_array($existingProfile) && isset($existingProfile['api_email']) ? (string) $existingProfile['api_email'] : '',
        'global_api_key' => is_array($existingProfile) && isset($existingProfile['global_api_key']) ? (string) $existingProfile['global_api_key'] : '',
        'auth_type' => is_array($existingProfile) && isset($existingProfile['auth_type']) ? (string) $existingProfile['auth_type'] : 'none',
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
        $newToken = trim(isset($input['api_token']) ? (string) $input['api_token'] : '');
        $authType = isset($input['auth_type']) ? (string) $input['auth_type'] : $profile['auth_type'];
        if (!in_array($authType, ['none', 'token', 'global'], true)) cfResponse(['success' => false, 'message' => 'Kiểu xác thực Cloudflare không hợp lệ.'], 400);
        $profile['auth_type'] = $authType;
        if ($authType === 'none') {
            $profile['api_token'] = '';
            $profile['api_email'] = '';
            $profile['global_api_key'] = '';
        } elseif ($authType === 'token' && $newToken !== '') {
            if (strlen($newToken) < 20 || strlen($newToken) > 256 || preg_match('/\\s/', $newToken)) cfResponse(['success' => false, 'message' => 'Cloudflare API Token không hợp lệ.'], 400);
            $profile['api_token'] = $newToken;
            $profile['api_email'] = '';
            $profile['global_api_key'] = '';
        } elseif ($authType === 'token' && $profile['api_token'] === '') {
            cfResponse(['success' => false, 'message' => 'Hãy nhập Cloudflare API Token.'], 400);
        } elseif ($authType === 'global') {
            $email = trim(isset($input['api_email']) ? (string) $input['api_email'] : $profile['api_email']);
            $newKey = trim(isset($input['global_api_key']) ? (string) $input['global_api_key'] : '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) cfResponse(['success' => false, 'message' => 'Email tài khoản Cloudflare không hợp lệ.'], 400);
            if ($newKey !== '') {
                if (strlen($newKey) < 20 || strlen($newKey) > 128 || preg_match('/\\s/', $newKey)) cfResponse(['success' => false, 'message' => 'Global API Key không hợp lệ.'], 400);
                $profile['global_api_key'] = $newKey;
            }
            if ($profile['global_api_key'] === '') cfResponse(['success' => false, 'message' => 'Hãy nhập Global API Key.'], 400);
            $profile['api_email'] = $email;
            $profile['api_token'] = '';
        }
    }
    return $profile;
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
$data = cfLoadProfiles($storeFile);

if (!is_file($storeFile) && !cfSaveProfiles($storeDir, $storeFile, $data)) {
    cfResponse(['success' => false, 'message' => 'Không thể khởi tạo kho cấu hình Cloudflare Tunnel.'], 500);
}
if (is_file($storeFile)) @chmod($storeFile, 0777);

if ($action === 'list') cfResponse(['success' => true, 'data' => cfPublicData($data)]);

if ($action === 'save') {
    $raw = isset($_POST['profile']) ? json_decode((string) $_POST['profile'], true) : null;
    if (!is_array($raw)) cfResponse(['success' => false, 'message' => 'Dữ liệu hồ sơ không hợp lệ.'], 400);
    $requestedId = isset($raw['id']) ? (string) $raw['id'] : '';
    $index = $requestedId !== '' ? cfProfileIndex($data['profiles'], $requestedId) : -1;
    if ($requestedId !== '' && $index < 0) cfResponse(['success' => false, 'message' => 'Không tìm thấy hồ sơ cần sửa.'], 404);
    $profile = cfCleanProfile($raw, $requestedId, $index >= 0 ? $data['profiles'][$index] : null);
    if ($index >= 0) $data['profiles'][$index] = $profile; else $data['profiles'][] = $profile;
    if (!cfSaveProfiles($storeDir, $storeFile, $data)) cfResponse(['success' => false, 'message' => 'Không thể lưu dữ liệu Cloudflare Tunnel.'], 500);
    $publicProfile = cfPublicData(['active_profile' => '', 'profiles' => [$profile]])['profiles'][0];
    cfResponse(['success' => true, 'message' => $index >= 0 ? 'Đã cập nhật hồ sơ tunnel.' : 'Đã thêm hồ sơ tunnel.', 'data' => $publicProfile]);
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

if ($action === 'reveal_api_token' || $action === 'reveal_global_key') {
    $id = isset($_POST['id']) ? (string) $_POST['id'] : '';
    $index = cfProfileIndex($data['profiles'], $id);
    if ($index < 0) cfResponse(['success' => false, 'message' => 'Không tìm thấy hồ sơ.'], 404);
    $profile = $data['profiles'][$index];
    $field = $action === 'reveal_api_token' ? 'api_token' : 'global_api_key';
    if (empty($profile[$field])) cfResponse(['success' => false, 'message' => 'Chưa có API được cấu hình.'], 404);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    cfResponse(['success' => true, 'key' => (string) $profile[$field]]);
}

$connection = cfConnect($ssh_host, $ssh_port, $ssh_user, $ssh_password);

if ($action === 'delete_remote') {
    $id = isset($_POST['id']) ? (string) $_POST['id'] : '';
    $index = cfProfileIndex($data['profiles'], $id);
    if ($index < 0) cfResponse(['success' => false, 'message' => 'Không tìm thấy hồ sơ cần xóa.'], 404);
    $profile = $data['profiles'][$index];
    $confirmedName = isset($_POST['confirm_name']) ? (string) $_POST['confirm_name'] : '';
    if (!hash_equals((string) $profile['name'], $confirmedName)) cfResponse(['success' => false, 'message' => 'Tên xác nhận không khớp.'], 400);
    $output = [];
    $runtimeCheck = cfExec($connection, '(systemctl is-active cloudflared.service 2>/dev/null || true); printf "\n--- EXEC ---\n"; (systemctl show cloudflared.service --property=ExecStart --value 2>/dev/null || true); printf "\n--- CONFIG ---\n"; (sudo cat /home/pi/Cloud_Flare/config.yml 2>/dev/null || true)', 10);
    $runtimeActive = preg_match('/(?:^|\R)active(?:\R|$)/', $runtimeCheck['output']) === 1;
    $runtimeProfileId = cfRuntimeProfileId($data['profiles'], $runtimeCheck['output']);
    $runtimeMatchesProfile = $runtimeProfileId !== '' && hash_equals($id, $runtimeProfileId);
    $isActive = $runtimeCheck['ok'] ? ($runtimeActive && $runtimeMatchesProfile) : ($data['active_profile'] === $id);
    $auth = null;
    if ($profile['mode'] === 'domain') {
        $authType = isset($_POST['auth_type']) ? (string) $_POST['auth_type'] : 'saved';
        if ($authType === 'saved') $authType = isset($profile['auth_type']) ? (string) $profile['auth_type'] : (!empty($profile['api_token']) ? 'token' : 'none');
        if ($authType === 'global') {
            $email = trim(isset($_POST['api_email']) && $_POST['api_email'] !== '' ? (string) $_POST['api_email'] : (isset($profile['api_email']) ? (string) $profile['api_email'] : ''));
            $key = trim(isset($_POST['global_api_key']) && $_POST['global_api_key'] !== '' ? (string) $_POST['global_api_key'] : (isset($profile['global_api_key']) ? (string) $profile['global_api_key'] : ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $key === '') cfResponse(['success' => false, 'message' => 'Cần nhập đúng email và Global API Key để xóa DNS.'], 400);
            $auth = ['type' => 'global', 'email' => $email, 'key' => $key];
        } else {
            $key = trim(isset($_POST['api_token']) && $_POST['api_token'] !== '' ? (string) $_POST['api_token'] : (isset($profile['api_token']) ? (string) $profile['api_token'] : ''));
            if ($key === '') cfResponse(['success' => false, 'message' => 'Cần nhập Cloudflare API Token để xóa DNS.'], 400);
            $auth = ['type' => 'token', 'key' => $key];
        }
        if (strlen($auth['key']) < 20 || strlen($auth['key']) > 256 || preg_match('/\s/', $auth['key'])) cfResponse(['success' => false, 'message' => 'Khóa Cloudflare API không hợp lệ.'], 400);
    }
    // Chỉ dừng service sau khi toàn bộ dữ liệu xác thực bắt buộc đã hợp lệ.
    // Nhờ vậy thao tác xóa thiếu/sai API key không làm tunnel đang chạy bị tắt ngoài ý muốn.
    if ($isActive) {
        $stop = cfExec($connection, 'sudo systemctl disable --now cloudflared.service >/dev/null 2>&1 || sudo systemctl stop cloudflared.service');
        if (!$stop['ok']) cfResponse(['success' => false, 'message' => $stop['output'] ?: 'Không thể dừng Cloudflare Tunnel trước khi xóa.'], 500);
        $output[] = 'Đã dừng và vô hiệu hóa service cloudflared.';
    }
    if ($profile['mode'] === 'domain') {
        $dns = cfDeleteTunnelDns($profile['hostname'], $auth);
        if (!$dns['ok']) cfResponse(['success' => false, 'message' => 'Không thể xóa DNS: '.$dns['message']], 502);
        $output[] = $dns['deleted'] > 0 ? 'Đã xóa '.$dns['deleted'].' bản ghi DNS cho '.$profile['hostname'].'.' : 'Không còn bản ghi Tunnel CNAME cho '.$profile['hostname'].'.';

        $findBinary = 'CF_BIN=$(command -v cloudflared 2>/dev/null || true); for CF_PATH in /usr/local/bin/cloudflared /usr/bin/cloudflared /bin/cloudflared; do if [ -z "$CF_BIN" ] && [ -x "$CF_PATH" ]; then CF_BIN="$CF_PATH"; fi; done; [ -n "$CF_BIN" ] && [ -x "$CF_BIN" ]';
        $removeTunnel = cfExec($connection, $findBinary.' && "$CF_BIN" tunnel delete -f '.escapeshellarg($profile['tunnel']), 45);
        if (!$removeTunnel['ok']) cfResponse(['success' => false, 'message' => 'DNS đã được xóa nhưng không thể xóa Named Tunnel: '.($removeTunnel['output'] ?: 'cloudflared trả về lỗi.'), 'output' => implode("\n", $output)], 502);
        $output[] = 'Đã xóa Named Tunnel '.$profile['tunnel'].' trên Cloudflare.';

        $removeCredential = cfExec($connection, 'sudo rm -f -- '.escapeshellarg($profile['credentials_file']));
        if (!$removeCredential['ok']) cfResponse(['success' => false, 'message' => 'Tunnel và DNS đã được xóa nhưng không thể xóa credentials JSON: '.$removeCredential['output'], 'output' => implode("\n", $output)], 500);
        $output[] = 'Đã xóa credentials JSON của tunnel.';
    }
    if ($isActive) {
        $cleanup = cfExec($connection, 'sudo rm -f -- /etc/systemd/system/cloudflared.service /home/pi/Cloud_Flare/config.yml; sudo systemctl daemon-reload');
        if (!$cleanup['ok']) cfResponse(['success' => false, 'message' => 'Đã xóa tài nguyên Cloudflare nhưng không thể dọn cấu hình service cục bộ: '.$cleanup['output'], 'output' => implode("\n", $output)], 500);
        $data['active_profile'] = '';
        $output[] = 'Đã dọn cấu hình service cục bộ.';
    }
    array_splice($data['profiles'], $index, 1);
    if (!cfSaveProfiles($storeDir, $storeFile, $data)) cfResponse(['success' => false, 'message' => 'Đã xóa tài nguyên Cloudflare nhưng không thể xóa hồ sơ VBot.', 'output' => implode("\n", $output)], 500);
    $output[] = 'Đã xóa hồ sơ VBot.';
    cfResponse(['success' => true, 'message' => 'Đã xóa toàn bộ Tunnel, DNS, credentials và hồ sơ VBot.', 'output' => implode("\n", $output)]);
}

if ($action === 'status') {
    $findBinary = 'CF_BIN=$(command -v cloudflared 2>/dev/null || true); for CF_PATH in /usr/local/bin/cloudflared /usr/bin/cloudflared /bin/cloudflared; do if [ -z "$CF_BIN" ] && [ -x "$CF_PATH" ]; then CF_BIN="$CF_PATH"; fi; done';
    $result = cfExec($connection, $findBinary.'; if [ -n "$CF_BIN" ] && [ -x "$CF_BIN" ]; then printf "%s\\n" "$CF_BIN"; "$CF_BIN" --version; else printf "CLOUDFLARED_NOT_INSTALLED"; fi; printf "\\n--- SERVICE ---\\n"; (systemctl is-enabled cloudflared.service 2>/dev/null || true); (systemctl is-active cloudflared.service 2>/dev/null || true); printf "\\n--- EXEC ---\\n"; (systemctl show cloudflared.service --property=ExecStart --value 2>/dev/null || true); printf "\\n--- CONFIG ---\\n"; (sudo cat /home/pi/Cloud_Flare/config.yml 2>/dev/null || true); printf "\\n--- URL ---\\n"; (journalctl -u cloudflared.service -n 80 --no-pager 2>/dev/null | grep -Eo "https://[-a-z0-9]+\\.trycloudflare\\.com" | tail -1 || true)');
    $cloudflaredInstalled = strpos($result['output'], 'CLOUDFLARED_NOT_INSTALLED') === false;
    $serviceIsActive = preg_match('/(?:^|\\R)active(?:\\R|$)/', $result['output']) === 1;
    $publicUrl = '';
    if (preg_match('#https://[-a-z0-9]+\\.trycloudflare\\.com#i', $result['output'], $urlMatch)) {
        $publicUrl = $urlMatch[0];
    }
    if ($serviceIsActive) {
        $matchedProfile = cfRuntimeProfileId($data['profiles'], $result['output']);
        if ($data['active_profile'] !== $matchedProfile) {
            $data['active_profile'] = $matchedProfile;
            cfSaveProfiles($storeDir, $storeFile, $data);
        }
    } elseif (!$serviceIsActive && $data['active_profile'] !== '') {
        $data['active_profile'] = '';
        cfSaveProfiles($storeDir, $storeFile, $data);
    }
    cfResponse([
        'success' => $result['exit_code'] === 0,
        'message' => $cloudflaredInstalled ? ($result['output'] !== '' ? $result['output'] : 'Không lấy được trạng thái cloudflared.') : 'Cloudflared chưa được cài đặt. Hãy mở mục hướng dẫn để cài đặt trước khi kích hoạt tunnel.',
        'data' => cfPublicData($data),
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
        'data' => cfPublicData($data),
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
    $systemCommand .= 'sudo systemctl restart --no-block cloudflared.service'
        .'; CF_READY=0; CF_WAIT=0'
        .'; while [ "$CF_WAIT" -lt 45 ]; do '
        .'if systemctl is-active --quiet cloudflared.service '
        .'&& ! systemctl list-jobs --no-legend 2>/dev/null | grep -q "cloudflared.service"; then sleep 2; '
        .'if systemctl is-active --quiet cloudflared.service '
        .'&& ! systemctl list-jobs --no-legend 2>/dev/null | grep -q "cloudflared.service"; then CF_READY=1; break; fi; fi; '
        .'sleep 1; CF_WAIT=$((CF_WAIT + 1)); done'
        .'; if [ "$CF_READY" -eq 1 ]; then systemctl is-active cloudflared.service; '
        .'else printf "Cloudflared không đạt trạng thái active sau %ss.\n" "$CF_WAIT"; '
        .'systemctl status cloudflared.service --no-pager -l 2>/dev/null || true; '
        .'journalctl -u cloudflared.service -n 30 --no-pager 2>/dev/null || true; false; fi';
    $result = cfExec($connection, $systemCommand, 65);
    // SSH có thể đóng stream chậm dù systemd đã khởi động service thành công.
    // Xác minh trạng thái thực tế trước khi trả lỗi timeout cho WebUI.
    if (!$result['ok'] && $result['exit_code'] === 124) {
        $verification = cfExec($connection, 'systemctl is-active cloudflared.service', 10);
        if ($verification['ok'] && trim($verification['output']) === 'active') {
            $result = ['ok' => true, 'exit_code' => 0, 'output' => 'active (đã xác minh sau khi lệnh SSH hết thời gian chờ)'];
        }
    }
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
