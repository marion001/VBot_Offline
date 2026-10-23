<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['GET']);
include __DIR__.'/../../Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))) {
    session_write_close();
    vbotApiJsonResponse(['success'=>false,'message'=>'Bạn cần đăng nhập WebUI'],401);
}
session_write_close();
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store');
header('X-Accel-Buffering: no');
while (ob_get_level()>0) ob_end_flush();
set_time_limit(35);
echo "retry: 3000\n\n";
flush();
$deadline=microtime(true)+25;
$previous=null;
do {
    if (connection_aborted()) break;
    $raw=@file_get_contents('/run/vbot-bt-source-status.json');
    $data=is_string($raw)?json_decode($raw,true):null;
    if (is_array($data) && is_array($data['devices']??null) && is_array($data['settings']??null)) {
        $age=hrtime(true)/1000000000-(float)($data['at']??0);
        if ($age<0 || $age>20) {
            $data['installed']=false;
            $data['route']=[];
            foreach ($data['devices'] as &$device) {
                foreach (['connected','output_connected','input_connected','output_running','input_running'] as $key) $device[$key]=false;
            }
            unset($device);
        }
        unset($data['at'],$data['route']['at']);
        $data['success']=true;
        $payload=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
        $fingerprint=hash('sha256',$payload);
        if ($fingerprint!==$previous) {
            echo "event: update\ndata: ".$payload."\n\n";
            $previous=$fingerprint;
        } else echo ": heartbeat\n\n";
    } else echo ": waiting for Bluetooth Source\n\n";
    flush();
    usleep(1000000);
} while (microtime(true)<$deadline);
