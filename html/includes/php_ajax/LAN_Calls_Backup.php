<?php
require_once __DIR__.'/Api_Helpers.php';
require_once dirname(__DIR__).'/LANCallBackups.php';
vbotApiInitialize(['POST']);
$VBot_Config_Read_Only=true;
include __DIR__.'/../../Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200)))
    vbotApiJsonResponse(['success'=>false,'message'=>'Bạn cần đăng nhập WebUI'],401);
if (!is_string($_POST['csrf']??null) || empty($_SESSION['lan_calls_csrf']) || !hash_equals($_SESSION['lan_calls_csrf'],$_POST['csrf']))
    vbotApiJsonResponse(['success'=>false,'message'=>'Phiên đã hết hạn; tải lại trang'],403);
session_write_close();
header('Cache-Control: no-store');
try {
    $root=dirname(__DIR__,3);
    $settings=$Config['lan_calls']??[];
    $localId=($settings['use_mdns_id']??true)?vbotStableDeviceId():($settings['id']??'');
    $localId=$localId!==''?$localId:($settings['id']??'');
    $result=vbotLANBackupOperation($root.'/resource/call_lan/Devices.json',$root.'/html/Backup_Upgrade/Backup_LAN_Calls',$localId,$_POST,$settings['peers']??[]);
    vbotApiJsonResponse(array_merge(['success'=>true],$result));
} catch (LengthException $error) {vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],413);}
catch (InvalidArgumentException $error) {vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);}
catch (Throwable $error) {
    error_log('LAN calls backup: '.$error->getMessage());
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getCode()===409?$error->getMessage():'Không đọc/ghi được dữ liệu LAN; kiểm tra quyền và log VBot.'],$error->getCode()===409?409:500);
}
