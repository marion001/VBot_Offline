<?php
if (!defined('VBOT_WIFI_PROFILE_CONTEXT')) { http_response_code(404); exit; }
// Included after the standard Wifi_Act authentication and helper definitions.
$wifiProfileActions=['connect_wifi','delete_wifi','set_static_ip','use_dhcp_automatically','set_dns_only','reset_dns_dhcp','preferences','apply','confirm','transaction','probe'];
if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($_POST['action']??'', $wifiProfileActions, true)) {
    vbotApiVerifyCsrf(true);
    if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
    try {
        $action=$_POST['action']; $uuid=$_POST['uuid']??'';
        if ($action!=='probe' && !vbotWifiValidUuid($uuid)) throw new InvalidArgumentException('Chưa xác định được UUID Wi-Fi. Hãy tải lại thông tin mạng.');
        $data=[];
        foreach (['ip','gateway','dns1','dns2','dns_source','autoconnect','priority','token'] as $key) {
            if (isset($_POST[$key])) {
                if (!is_string($_POST[$key]) || strlen($_POST[$key])>256) throw new InvalidArgumentException('Dữ liệu cấu hình không hợp lệ');
                $data[$key]=$_POST[$key];
            }
        }
        $command=['sudo','-n','python3',rtrim($VBot_Offline,'/\\').'/resource/wifi_manager/network_manager.py',$action,'--uuid='.$uuid];
        $connection=@ssh2_connect($ssh_host,(int)$ssh_port);
        if (!$connection || !@ssh2_auth_password($connection,$ssh_user,$ssh_password)) throw new RuntimeException('Không xác thực được SSH');
        $stream=@ssh2_exec($connection,'printf %s '.escapeshellarg(json_encode((object)$data)).' | '.implode(' ',array_map('escapeshellarg',$command)));
        if (!$stream) throw new RuntimeException('Không mở được kênh SSH');
        $errors=ssh2_fetch_stream($stream,SSH2_STREAM_STDERR);
        stream_set_blocking($stream,false); stream_set_blocking($errors,false);
        $stdout=''; $stderr=''; $deadline=microtime(true)+40;
        try {
            do {
                $stdout.=stream_get_contents($stream); $stderr.=stream_get_contents($errors);
                if (strlen($stdout)+strlen($stderr)>1048576) throw new RuntimeException('Phản hồi mạng quá lớn');
                if (feof($stream) && feof($errors)) break;
                if (microtime(true)>$deadline) throw new RuntimeException('Hết thời gian chờ; hãy kiểm tra lại trạng thái mạng.');
                usleep(20000);
            } while (true);
        } finally { fclose($errors); fclose($stream); }
        $result=json_decode($stdout,true);
        if (!is_array($result) || !isset($result['success'])) {
            error_log('Wifi profile: '.$stderr);
            throw new RuntimeException('Không nhận được kết quả từ bộ quản lý Wi-Fi');
        }
        vbotApiJsonResponse($result,$result['success']?200:400);
    } catch (Throwable $error) { vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400); }
}
