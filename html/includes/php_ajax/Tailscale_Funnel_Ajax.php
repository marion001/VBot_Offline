<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
@set_time_limit(120);
include __DIR__.'/../../Configuration.php';

if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!isset($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200)) {
        session_unset(); session_destroy();
        vbotApiJsonResponse(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.'], 401);
    }
    $_SESSION['user_login']['login_time'] = time();
}
vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

function tsResponse($data, $status = 200) { vbotApiJsonResponse($data, $status); }

function tsConnect($host, $port, $user, $password) {
    if (!function_exists('ssh2_connect')) tsResponse(['success' => false, 'message' => 'PHP chưa có extension SSH2.'], 500);
    $connection = @ssh2_connect($host, $port);
    if (!$connection || !@ssh2_auth_password($connection, $user, $password)) {
        tsResponse(['success' => false, 'message' => 'Không thể xác thực SSH tới hệ thống.'], 502);
    }
    return $connection;
}

function tsExec($connection, $command, $timeout = 30) {
    $marker = '__VBOT_TS_EXIT__';
    $wrapped = 'sh -lc '.escapeshellarg($command.' 2>&1; code=$?; printf "\n'.$marker.'%s" "$code"');
    $stream = @ssh2_exec($connection, $wrapped);
    if (!$stream) return ['ok' => false, 'exit_code' => -1, 'output' => 'Không thể thực thi lệnh SSH.'];
    stream_set_blocking($stream, false);
    $output = '';
    $started = microtime(true);
    while (!feof($stream)) {
        $chunk = fread($stream, 8192);
        if ($chunk !== false && $chunk !== '') $output .= $chunk;
        if (microtime(true) - $started > $timeout) {
            fclose($stream);
            return ['ok' => false, 'exit_code' => 124, 'output' => 'Lệnh quá thời gian cho phép.'];
        }
        usleep(20000);
    }
    fclose($stream);
    $exitCode = -1;
    if (preg_match('/\n'.$marker.'(\d+)\s*$/', $output, $match)) {
        $exitCode = (int)$match[1];
        $output = preg_replace('/\n'.$marker.'\d+\s*$/', '', $output);
    }
    return ['ok' => $exitCode === 0, 'exit_code' => $exitCode, 'output' => trim((string)$output)];
}

function tsBinaryCommand() {
    return 'TS_BIN=$(command -v tailscale 2>/dev/null || true); '
        .'for TS_PATH in /usr/local/bin/tailscale /usr/bin/tailscale /bin/tailscale; do '
        .'if [ -z "$TS_BIN" ] && [ -x "$TS_PATH" ]; then TS_BIN="$TS_PATH"; fi; done';
}

function tsRuntimeSection($output, $name) {
    $pattern = '/(?:^|\R)--- '.preg_quote($name, '/').' ---\R(.*?)(?=\R--- [A-Z ]+ ---|$)/s';
    return preg_match($pattern, (string)$output, $match) ? trim($match[1]) : '';
}

function tsFindAccountProfile($statusJson, $userId) {
    if (!is_array($statusJson) || $userId === '') return null;
    $users = isset($statusJson['User']) && is_array($statusJson['User']) ? $statusJson['User'] : [];
    foreach ($users as $key => $candidate) {
        if (!is_array($candidate)) continue;
        $candidateId = isset($candidate['ID']) ? (string)$candidate['ID'] : '';
        if ((string)$key === (string)$userId || ($candidateId !== '' && $candidateId === (string)$userId)) return $candidate;
    }
    return null;
}

function tsRuntimeData($output) {
    $statusJson = json_decode(tsRuntimeSection($output, 'TAILSCALE JSON'), true);
    $backendState = is_array($statusJson) && isset($statusJson['BackendState']) ? (string)$statusJson['BackendState'] : '';
    $dnsName = '';
    $ipv4 = '';
    $accountLogin = '';
    $accountDisplayName = '';
    if (is_array($statusJson) && isset($statusJson['Self']) && is_array($statusJson['Self'])) {
        $dnsName = rtrim(isset($statusJson['Self']['DNSName']) ? (string)$statusJson['Self']['DNSName'] : '', '.');
        foreach (isset($statusJson['Self']['TailscaleIPs']) && is_array($statusJson['Self']['TailscaleIPs']) ? $statusJson['Self']['TailscaleIPs'] : [] as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { $ipv4 = (string)$ip; break; }
        }
        $userId = isset($statusJson['Self']['UserID']) ? (string)$statusJson['Self']['UserID'] : '';
        $user = tsFindAccountProfile($statusJson, $userId);
        if (is_array($user)) {
            $accountLogin = trim(isset($user['LoginName']) ? (string)$user['LoginName'] : '');
            $accountDisplayName = trim(isset($user['DisplayName']) ? (string)$user['DisplayName'] : '');
        }
    }
    if ($accountLogin === '') {
        $plainStatus = tsRuntimeSection($output, 'TAILSCALE STATUS');
        foreach (preg_split('/\R/', $plainStatus) ?: [] as $line) {
            if (!preg_match('/^\s*(?:100\.|fd7a:)[^\s]*\s+\S+\s+(\S+)\s+\S+/', $line, $match)) continue;
            if ($match[1] !== '-' && strpos($match[1], 'tag:') !== 0) { $accountLogin = (string)$match[1]; break; }
        }
    }
    $funnelStatus = tsRuntimeSection($output, 'FUNNEL STATUS');
    $serveStatus = tsRuntimeSection($output, 'SERVE STATUS');
    $publicUrl = '';
    if (preg_match('#https://[-a-z0-9.]+\.ts\.net(?::\d+)?/?#i', $funnelStatus, $urlMatch)) $publicUrl = $urlMatch[0];
    $emptyMarkers = ['No serve config', 'No Funnel', 'No configuration'];
    $funnelActive = $funnelStatus !== '';
    $serveActive = $serveStatus !== '';
    foreach ($emptyMarkers as $marker) {
        if (stripos($funnelStatus, $marker) !== false) $funnelActive = false;
        if (stripos($serveStatus, $marker) !== false) $serveActive = false;
    }
    return [
        'installed' => strpos((string)$output, 'TAILSCALE_NOT_INSTALLED') === false,
        'daemon_enabled' => preg_match('/(?:^|\R)enabled(?:\R|$)/', tsRuntimeSection($output, 'DAEMON')) === 1,
        'daemon_active' => preg_match('/(?:^|\R)active(?:\R|$)/', tsRuntimeSection($output, 'DAEMON')) === 1,
        'logged_in' => strcasecmp($backendState, 'Running') === 0,
        'backend_state' => $backendState,
        'dns_name' => $dnsName,
        'ipv4' => $ipv4,
        'account_login' => $accountLogin,
        'account_display_name' => $accountDisplayName,
        'funnel_active' => $funnelActive,
        'serve_active' => $serveActive,
        'public_url' => $publicUrl
    ];
}

function tsValidateTarget($value) {
    $value = trim((string)$value);
    if (!filter_var($value, FILTER_VALIDATE_URL)) return '';
    $parts = parse_url($value);
    if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'http') return '';
    $host = strtolower((string)($parts['host'] ?? ''));
    if (!in_array($host, ['127.0.0.1', 'localhost'], true)) return '';
    if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) return '';
    $port = isset($parts['port']) ? (int)$parts['port'] : 80;
    if ($port < 1 || $port > 65535) return '';
    return $value;
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
$connection = tsConnect($ssh_host, $ssh_port, $ssh_user, $ssh_password);
$binary = tsBinaryCommand();

if ($action === 'status') {
    $command = $binary
        .'; if [ -n "$TS_BIN" ] && [ -x "$TS_BIN" ]; then "$TS_BIN" version; else printf "TAILSCALE_NOT_INSTALLED"; fi'
        .'; printf "\n--- DAEMON ---\n"; (systemctl is-enabled tailscaled.service 2>/dev/null || true); (systemctl is-active tailscaled.service 2>/dev/null || true)'
        .'; printf "\n--- TAILSCALE STATUS ---\n"; (sudo "$TS_BIN" status 2>/dev/null || true)'
        .'; printf "\n--- TAILSCALE JSON ---\n"; (sudo "$TS_BIN" status --json 2>/dev/null || true)'
        .'; printf "\n--- FUNNEL STATUS ---\n"; (sudo "$TS_BIN" funnel status 2>/dev/null || true)'
        .'; printf "\n--- FUNNEL JSON ---\n"; (sudo "$TS_BIN" funnel status --json 2>/dev/null || true)'
        .'; printf "\n--- SERVE STATUS ---\n"; (sudo "$TS_BIN" serve status 2>/dev/null || true)'
        .'; printf "\n--- SERVE JSON ---\n"; (sudo "$TS_BIN" serve status --json 2>/dev/null || true)';
    $result = tsExec($connection, $command, 35);
    $runtime = tsRuntimeData($result['output']);
    tsResponse(['success' => true, 'message' => $result['output'] !== '' ? $result['output'] : 'Không lấy được trạng thái Tailscale.', 'runtime' => $runtime]);
}

if ($action === 'start_funnel') {
    $target = tsValidateTarget($_POST['target'] ?? '');
    $httpsPort = isset($_POST['https_port']) ? (int)$_POST['https_port'] : 443;
    if ($target === '') tsResponse(['success' => false, 'message' => 'Đích Funnel phải là URL HTTP cục bộ 127.0.0.1 hoặc localhost.'], 400);
    if (!in_array($httpsPort, [443, 8443, 10000], true)) tsResponse(['success' => false, 'message' => 'Cổng HTTPS Funnel chỉ được phép là 443, 8443 hoặc 10000.'], 400);
    $command = $binary.'; [ -n "$TS_BIN" ] && [ -x "$TS_BIN" ] || { printf "Tailscale chưa được cài đặt"; exit 127; }; sudo "$TS_BIN" funnel --bg --yes --https='.$httpsPort.' '.escapeshellarg($target);
    $result = tsExec($connection, $command, 55);
    if (!$result['ok']) {
        error_log('[TAILSCALE FUNNEL ERROR] Không thể bật Funnel: '.$result['output']);
        tsResponse(['success' => false, 'message' => $result['output'] ?: 'Không thể bật Tailscale Funnel.'], 500);
    }
    error_log('[TAILSCALE FUNNEL INFO] Đã bật Funnel HTTPS '.$httpsPort.' tới '.$target.'.');
    tsResponse(['success' => true, 'message' => $result['output'] ?: 'Đã bật Tailscale Funnel.']);
}

if ($action === 'set_autostart') {
    $enabledValue = isset($_POST['enabled']) ? (string)$_POST['enabled'] : '';
    if (!in_array($enabledValue, ['true', 'false'], true)) tsResponse(['success' => false, 'message' => 'Trạng thái tự khởi động không hợp lệ.'], 400);
    $enable = $enabledValue === 'true';
    $serviceAction = $enable ? 'enable' : 'disable';
    $command = 'sudo systemctl '.$serviceAction.' tailscaled.service';
    $result = tsExec($connection, $command, 25);
    if (!$result['ok']) {
        error_log('[TAILSCALE SERVICE ERROR] Không thể '.$serviceAction.' tailscaled.service: '.$result['output']);
        tsResponse(['success' => false, 'message' => $result['output'] ?: 'Không thể thay đổi tự khởi động tailscaled.'], 500);
    }
    $message = $enable ? 'Đã bật tailscaled tự khởi động cùng hệ thống.' : 'Đã tắt tailscaled tự khởi động cùng hệ thống; kết nối hiện tại vẫn được giữ nguyên.';
    error_log('[TAILSCALE SERVICE INFO] '.$message);
    tsResponse(['success' => true, 'message' => $message, 'enabled' => $enable]);
}

if ($action === 'service_control') {
    $operation = isset($_POST['operation']) ? (string)$_POST['operation'] : '';
    if (!in_array($operation, ['start', 'stop'], true)) tsResponse(['success' => false, 'message' => 'Thao tác service không hợp lệ.'], 400);
    $command = $operation === 'start'
        ? 'sudo systemctl enable --now tailscaled.service'
        : 'sudo systemctl stop tailscaled.service';
    $result = tsExec($connection, $command, 30);
    if (!$result['ok']) {
        error_log('[TAILSCALE SERVICE ERROR] Không thể '.$operation.' tailscaled.service: '.$result['output']);
        tsResponse(['success' => false, 'message' => $result['output'] ?: 'Không thể điều khiển tailscaled.service.'], 500);
    }
    $message = $operation === 'start'
        ? 'Đã bật và cho phép tailscaled tự khởi động cùng hệ thống. Nếu chưa đăng nhập, hãy chạy tailscale up.'
        : 'Đã dừng tailscaled. Phần mềm và cấu hình vẫn được giữ lại.';
    error_log('[TAILSCALE SERVICE INFO] '.$message);
    tsResponse(['success' => true, 'message' => $message]);
}

if ($action === 'logout') {
    $confirmation = isset($_POST['confirmation']) ? trim((string)$_POST['confirmation']) : '';
    if (!hash_equals('DANG XUAT TAILSCALE', $confirmation)) tsResponse(['success' => false, 'message' => 'Nội dung xác nhận đăng xuất không đúng.'], 400);
    $command = $binary.'; [ -n "$TS_BIN" ] && [ -x "$TS_BIN" ] || exit 127; sudo "$TS_BIN" logout';
    $result = tsExec($connection, $command, 35);
    if (!$result['ok']) {
        error_log('[TAILSCALE LOGOUT ERROR] Không thể đăng xuất thiết bị: '.$result['output']);
        tsResponse(['success' => false, 'message' => $result['output'] ?: 'Không thể đăng xuất khỏi Tailscale.'], 500);
    }
    error_log('[TAILSCALE LOGOUT INFO] Thiết bị đã đăng xuất khỏi tailnet.');
    tsResponse(['success' => true, 'message' => 'Đã đăng xuất thiết bị khỏi Tailscale. Muốn kết nối lại, hãy chạy tailscale up và đăng nhập lại.']);
}

if ($action === 'uninstall') {
    $confirmation = isset($_POST['confirmation']) ? trim((string)$_POST['confirmation']) : '';
    if (!hash_equals('GO TAILSCALE', $confirmation)) tsResponse(['success' => false, 'message' => 'Nội dung xác nhận gỡ Tailscale không đúng.'], 400);
    $removeState = isset($_POST['remove_state']) && (string)$_POST['remove_state'] === 'true';
    $command = $binary
        .'; if [ -n "$TS_BIN" ] && [ -x "$TS_BIN" ]; then sudo "$TS_BIN" funnel reset >/dev/null 2>&1 || true; sudo "$TS_BIN" serve reset >/dev/null 2>&1 || true; sudo "$TS_BIN" logout >/dev/null 2>&1 || true; fi'
        .'; sudo systemctl disable --now tailscaled.service >/dev/null 2>&1 || true'
        .'; sudo apt-get remove --purge -y tailscale tailscale-archive-keyring'
        .'; sudo rm -f -- /etc/apt/sources.list.d/tailscale.list /usr/share/keyrings/tailscale-archive-keyring.gpg'
        .($removeState ? '; sudo rm -rf -- /var/lib/tailscale' : '')
        .'; sudo apt-get update';
    $result = tsExec($connection, $command, 115);
    if (!$result['ok']) {
        error_log('[TAILSCALE UNINSTALL ERROR] Gỡ Tailscale chưa hoàn tất: '.$result['output']);
        tsResponse(['success' => false, 'message' => $result['output'] ?: 'Không thể gỡ hoàn toàn Tailscale.'], 500);
    }
    $message = 'Đã gỡ Tailscale, gói keyring và repository.'.($removeState ? ' Đã xóa cả /var/lib/tailscale.' : ' Dữ liệu /var/lib/tailscale được giữ lại.');
    error_log('[TAILSCALE UNINSTALL INFO] '.$message);
    tsResponse(['success' => true, 'message' => $message, 'output' => $result['output']]);
}

if ($action === 'stop_funnel') {
    $httpsPort = isset($_POST['https_port']) ? (int)$_POST['https_port'] : 443;
    if (!in_array($httpsPort, [443, 8443, 10000], true)) tsResponse(['success' => false, 'message' => 'Cổng HTTPS Funnel không hợp lệ.'], 400);
    $command = $binary.'; [ -n "$TS_BIN" ] && [ -x "$TS_BIN" ] || exit 127; sudo "$TS_BIN" funnel --yes --https='.$httpsPort.' off';
    $result = tsExec($connection, $command, 35);
    if (!$result['ok']) {
        error_log('[TAILSCALE FUNNEL ERROR] Không thể dừng Funnel: '.$result['output']);
        tsResponse(['success' => false, 'message' => $result['output'] ?: 'Không thể dừng Funnel.'], 500);
    }
    error_log('[TAILSCALE FUNNEL INFO] Đã dừng riêng Funnel HTTPS '.$httpsPort.'; không xóa Serve.');
    tsResponse(['success' => true, 'message' => $result['output'] ?: 'Đã dừng riêng Tailscale Funnel; cấu hình Serve không bị xóa.']);
}

if ($action === 'reset_all') {
    $confirmation = isset($_POST['confirmation']) ? trim((string)$_POST['confirmation']) : '';
    if (!hash_equals('XOA TAT CAU HINH', $confirmation)) tsResponse(['success' => false, 'message' => 'Nội dung xác nhận không đúng.'], 400);
    $command = $binary.'; [ -n "$TS_BIN" ] && [ -x "$TS_BIN" ] || exit 127; sudo "$TS_BIN" funnel reset && sudo "$TS_BIN" serve reset';
    $result = tsExec($connection, $command, 40);
    if (!$result['ok']) {
        error_log('[TAILSCALE FUNNEL ERROR] Không thể xóa toàn bộ Funnel/Serve: '.$result['output']);
        tsResponse(['success' => false, 'message' => $result['output'] ?: 'Không thể xóa toàn bộ cấu hình Funnel/Serve.'], 500);
    }
    error_log('[TAILSCALE FUNNEL INFO] Đã xóa toàn bộ cấu hình Funnel và Serve; giữ nguyên đăng nhập Tailscale.');
    tsResponse(['success' => true, 'message' => 'Đã xóa toàn bộ cấu hình Funnel và Serve. Tailscale vẫn đăng nhập và tailscaled vẫn chạy.', 'output' => $result['output']]);
}

tsResponse(['success' => false, 'message' => 'Thao tác không hợp lệ.'], 400);
