<?php
require_once __DIR__.'/Api_Helpers.php';
require_once dirname(__DIR__).'/LocalProcess.php';
vbotApiInitialize(['POST']);
include __DIR__.'/../../Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200)))
    vbotApiJsonResponse(['success'=>false,'message'=>'Bạn cần đăng nhập WebUI'],401);
vbotApiVerifyCsrf(true);
session_write_close();
try {
    $action=$_POST['action']??'';
    if (!in_array($action,['list','save','wake','backups','create','read','restore'],true)) throw new InvalidArgumentException('Thao tác không hợp lệ');
    $command=['python3',rtrim($VBot_Offline,'/\\').'/Wake_On_LAN.py',$action];
    $input='';
    if ($action==='save') {
        $input=$_POST['data']??'';
        if (!is_string($input) || strlen($input)>1048576) throw new InvalidArgumentException('Dữ liệu vượt quá 1 MB');
        $revision=$_POST['revision']??'';
        if (!is_string($revision) || !preg_match('/^[a-f0-9]{64}$/D',$revision)) throw new InvalidArgumentException('Hãy tải cấu hình trước khi lưu');
        $command[]='--revision='.$revision;
    } elseif ($action==='wake') {
        $id=$_POST['id']??'';
        if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id)) throw new InvalidArgumentException('ID máy tính không hợp lệ');
        $command[]='--id='.$id;
    }
    if (in_array($action,['create','restore'],true)) {
        $revision=$_POST['revision']??'';
        if (!is_string($revision) || !preg_match('/^[a-f0-9]{64}$/D',$revision)) throw new InvalidArgumentException('Hãy tải lại dữ liệu trước');
        $command[]='--revision='.$revision;
    }
    if (in_array($action,['read','restore'],true)) {
        $name=$_POST['name']??'';
        if (!is_string($name) || ($name!=='' && !preg_match('/^wol_[0-9]{8}_[0-9]{6}_[a-f0-9]{12}\.json$/D',$name))) throw new InvalidArgumentException('Tên bản sao lưu không hợp lệ');
        $command[]='--name='.$name;
    }
    $result=vbotRunLocalProcess($command,$input,5);
    $data=json_decode($result['stdout'],true);
    if (!is_array($data) || !isset($data['success'])) {
        error_log('Wake-on-LAN: '.$result['stderr']);
        throw new RuntimeException('Không nhận được phản hồi từ bộ điều khiển Wake-on-LAN');
    }
    vbotApiJsonResponse($data,$data['success']?200:400);
} catch (Throwable $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);
}
