<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
include __DIR__.'/../../Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200)))
    vbotApiJsonResponse(['success'=>false,'message'=>'Bạn cần đăng nhập WebUI'],401);
vbotApiVerifyCsrf(true);
session_write_close();
try {
    $action=$_POST['action']??'';
    if (!is_string($action) || !in_array($action,['status','probe','inspect','install','enable','connect','disconnect','autoconnect','mode'],true)) throw new InvalidArgumentException('Thao tác không hợp lệ');
    $command=['sudo','-n','python3',rtrim($VBot_Offline,'/\\').'/resource/bluetooth/pan_manager.py',$action];
    if (!in_array($action,['status','probe','inspect','install'],true)) {
        $mac=$_POST['mac']??''; $enabled=$_POST['enabled']??'0';
        if ($action!=='enable' && (!is_string($mac) || !preg_match('/^(?:[0-9a-f]{2}:){5}[0-9a-f]{2}$/iD',$mac))) throw new InvalidArgumentException('MAC Bluetooth không hợp lệ');
        if (!is_string($enabled) || !in_array($enabled,['0','1'],true)) throw new InvalidArgumentException('Giá trị tự kết nối không hợp lệ');
        if ($action!=='enable') $command[]='--mac='.$mac;
        $command[]='--enabled='.$enabled;
        if ($action==='mode') {
            $mode=$_POST['mode']??'';
            if (!is_string($mode) || !in_array($mode,['auto','on','off'],true)) throw new InvalidArgumentException('Chế độ PAN không hợp lệ');
            $command[]='--mode='.$mode;
        }
    }
    // Use the same configured SSH account as Wifi.php; Apache needs no sudo grant.
    if (!function_exists('ssh2_connect')) throw new RuntimeException('Thiếu PHP SSH2');
    $connection=@ssh2_connect($ssh_host,(int)$ssh_port);
    if (!$connection || !@ssh2_auth_password($connection,$ssh_user,$ssh_password)) throw new RuntimeException('Không xác thực được SSH cấu hình trong VBot.');
    $stream=@ssh2_exec($connection,implode(' ',array_map('escapeshellarg',$command)));
    if (!$stream) throw new RuntimeException('Không mở được kênh SSH để quản lý PAN.');
    $errors=ssh2_fetch_stream($stream,SSH2_STREAM_STDERR);
    stream_set_blocking($stream,false); stream_set_blocking($errors,false);
    $timeout=$action==='install'?330:45;
    set_time_limit($timeout+10);
    $output=''; $stderr=''; $deadline=microtime(true)+$timeout;
    try {
        do {
            $output.=stream_get_contents($stream); $stderr.=stream_get_contents($errors);
            if (strlen($output)+strlen($stderr)>2097152) throw new RuntimeException('Phản hồi PAN vượt giới hạn.');
            if (feof($stream) && feof($errors)) break;
            if (microtime(true)>$deadline) throw new RuntimeException('Hết thời gian chờ PAN; hãy làm mới trạng thái.');
            usleep(20000);
        } while (true);
    } finally { fclose($errors); fclose($stream); }
    $data=json_decode($output,true);
    if (!is_array($data) || !isset($data['success'])) {
        error_log('Bluetooth PAN: '.$stderr);
        throw new RuntimeException('Không chạy được bộ quản lý PAN. Kiểm tra NetworkManager, python3-dbus và quyền sudo của tài khoản SSH.');
    }
    vbotApiJsonResponse($data,$data['success']?200:400);
} catch (Throwable $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);
}
