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
    if (!is_string($action) || !in_array($action,['input_connection_limit','connection_limit','key_list','key_learn','key_save','key_delete','status','scan','install','pair','connect','select','disconnect','device_disconnect','sink_connect','sink_disconnect','forget','local','autoconnect'],true)) throw new InvalidArgumentException('Thao tác không hợp lệ');
    $command=['sudo','-n','python3',rtrim($VBot_Offline,'/\\').'/resource/bluetooth/source_manager.py',$action];
    if (strpos($action,'key_')===0) {
        $command=['sudo','-n','python3',rtrim($VBot_Offline,'/\\').'/resource/bluetooth/button_keys.py',substr($action,4)];
        if ($action!=='key_list') {
            $mac=$_POST['mac']??'';
            if (!is_string($mac) || !preg_match('/^(?:[0-9a-f]{2}:){5}[0-9a-f]{2}$/iD',$mac)) throw new InvalidArgumentException('MAC Bluetooth không hợp lệ');
            $command[]='--mac='.$mac;
        }
        if (in_array($action,['key_save','key_delete'],true)) {
            $code=$_POST['code']??'';
            if (!is_string($code) || !ctype_digit($code) || (int)$code<1 || (int)$code>767) throw new InvalidArgumentException('Mã nút không hợp lệ');
            $command[]='--code='.$code;
        }
        if ($action==='key_save') {
            $name=$_POST['name']??''; $function=$_POST['function']??'';
            if (!is_string($name) || trim($name)==='' || strlen($name)>320 || !is_string($function) || strlen($function)>150) throw new InvalidArgumentException('Tên nút hoặc chức năng không hợp lệ');
            $command[]='--name='.$name;
            $command[]='--action='.$function;
            $enabled=$_POST['enabled']??'1';
            if (!is_string($enabled) || !in_array($enabled,['0','1'],true)) throw new InvalidArgumentException('Trạng thái kích hoạt nút không hợp lệ');
            $command[]='--enabled='.$enabled;
            $previousCode=$_POST['previous_code']??'0';
            if (!is_string($previousCode) || !ctype_digit($previousCode) || (int)$previousCode>767) throw new InvalidArgumentException('Mã nút cũ không hợp lệ');
            $command[]='--previous-code='.$previousCode;
        }
    } elseif (in_array($action,['connection_limit','input_connection_limit'],true)) {
        $maximum=$_POST['max_connections']??'';
        if (!is_string($maximum) || !preg_match('/^[1-7]$/D',$maximum)) throw new InvalidArgumentException('Giới hạn kết nối phải từ 1 đến 7');
        $command[]='--max-connections='.$maximum;
    } elseif (!in_array($action,['status','scan','install','local'],true)) {
        $mac=$_POST['mac']??''; $enabled=$_POST['enabled']??'0';
        if (!is_string($mac) || !preg_match('/^(?:[0-9a-f]{2}:){5}[0-9a-f]{2}$/iD',$mac)) throw new InvalidArgumentException('MAC Bluetooth không hợp lệ');
        if (!is_string($enabled) || !in_array($enabled,['0','1'],true)) throw new InvalidArgumentException('Giá trị tự kết nối không hợp lệ');
        $command[]='--mac='.$mac;
        $command[]='--enabled='.$enabled;
    }
    // Use the same configured SSH account as Wifi.php; Apache needs no sudo grant.
    if (!function_exists('ssh2_connect')) throw new RuntimeException('Thiếu PHP SSH2');
    $connection=@ssh2_connect($ssh_host,(int)$ssh_port);
    if (!$connection || !@ssh2_auth_password($connection,$ssh_user,$ssh_password)) throw new RuntimeException('Không xác thực được SSH cấu hình trong VBot.');
    $stream=@ssh2_exec($connection,implode(' ',array_map('escapeshellarg',$command)));
    if (!$stream) throw new RuntimeException('Không mở được kênh SSH để quản lý A2DP Source.');
    $errors=ssh2_fetch_stream($stream,SSH2_STREAM_STDERR);
    stream_set_blocking($stream,false); stream_set_blocking($errors,false);
    $timeout=$action==='install'?330:45;
    set_time_limit($timeout+10);
    $output=''; $stderr=''; $deadline=microtime(true)+$timeout;
    try {
        do {
            $output.=stream_get_contents($stream); $stderr.=stream_get_contents($errors);
            if (strlen($output)+strlen($stderr)>2097152) throw new RuntimeException('Phản hồi A2DP Source vượt giới hạn.');
            if (feof($stream) && feof($errors)) break;
            if (microtime(true)>$deadline) throw new RuntimeException('Hết thời gian chờ A2DP Source; hãy làm mới trạng thái.');
            usleep(20000);
        } while (true);
    } finally { fclose($errors); fclose($stream); }
    $data=json_decode($output,true);
    if (!is_array($data) || !isset($data['success'])) {
        error_log('Bluetooth Source: '.$stderr);
        throw new RuntimeException('Không chạy được bộ quản lý A2DP Source. Kiểm tra NetworkManager, python3-dbus và quyền sudo của tài khoản SSH.');
    }
    if ($action==='key_list' && !empty($data['success'])) {
        require_once __DIR__.'/../ActionRegistry.php';
        $data['actions']=vbotActionRegistryOptions($Config);
        $routinePath=rtrim($VBot_Offline,'/\\').'/resource/voice_routines/voice_routines.json';
        $routines=is_file($routinePath)?json_decode((string)file_get_contents($routinePath),true):[];
        foreach (($routines['routines']??[]) as $routine) {
            if (!empty($routine['enabled']) && is_string($routine['id']??null) && is_string($routine['name']??null))
                $data['actions']['routine:'.$routine['id']]='Kịch bản: '.$routine['name'];
        }
    }
    vbotApiJsonResponse($data,$data['success']?200:400);
} catch (Throwable $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);
}
