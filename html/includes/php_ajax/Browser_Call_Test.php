<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
$VBot_Config_Read_Only=true;
include __DIR__.'/../../Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200)))
    vbotApiJsonResponse(['success'=>false,'message'=>'Bạn cần đăng nhập WebUI'],401);
if (!is_string($_POST['csrf']??null) || empty($_SESSION['browser_call_csrf']) || !hash_equals($_SESSION['browser_call_csrf'],$_POST['csrf']))
    vbotApiJsonResponse(['success'=>false,'message'=>'Phiên đã hết hạn; tải lại trang'],403);
session_write_close();
header('Cache-Control: no-store');
try {
    $payload=$_POST['payload']??'';
    if (!is_string($payload) || strlen($payload)>12000) throw new RuntimeException('Dữ liệu không hợp lệ');
    $data=json_decode($payload,true);
    if (!is_array($data) || !in_array($data['action']??'', ['start','exchange','end'],true)) throw new RuntimeException('Thao tác không hợp lệ');
    if ($data['action']==='start') {
        $agent=substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,512);
        $device='Trình duyệt WebUI';
        foreach (['iPhone','iPad','Android','Windows','Macintosh','Linux'] as $label) {
            if (stripos($agent,$label)!==false) {$device='Trình duyệt trên '.$label;break;}
        }
        $data['device_name']=$device;
        $data['client_ip']=(string)($_SERVER['REMOTE_ADDR']??'không rõ');
    }
    $port=(int)$Port_API;
    if ($port<1 || $port>65535) throw new RuntimeException('Cổng API không hợp lệ');
    $headers=['Content-Type: application/json'];
    if (!empty($Config['api']['auth']['active'])) $headers[]='VBot-API-Key: '.($Config['api']['auth']['api_key']??'');
    $curl=curl_init('http://127.0.0.1:'.$port.'/browser-call-test');
    curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($data),CURLOPT_HTTPHEADER=>$headers,
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>($data['action']==='start'?25:4),CURLOPT_PROXY=>'',CURLOPT_FOLLOWLOCATION=>false]);
    $body=curl_exec($curl);
    $status=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);
    $result=is_string($body)?json_decode($body,true):null;
    if (!is_array($result) || !isset($result['success'])) throw new RuntimeException('Không kết nối được VBot; hãy chạy Start.py và bật gọi từ trình duyệt trong cấu hình gọi LAN');
    vbotApiJsonResponse($result,$status>=200 && $status<=599?$status:502);
} catch (Throwable $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],502);
}
