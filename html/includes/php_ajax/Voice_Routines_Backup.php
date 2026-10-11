<?php
require_once __DIR__.'/Api_Helpers.php';
require_once dirname(__DIR__).'/VoiceRoutineBackups.php';
vbotApiInitialize(['POST']);
include __DIR__.'/../../Configuration.php';
if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status()!==PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))
        vbotApiJsonResponse(['success'=>false,'message'=>'Bạn cần đăng nhập WebUI'],401);
}
vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
header('Cache-Control: no-store');
$path=rtrim($VBot_Offline,'/\\').'/resource/voice_routines.json';
$directory=rtrim($VBot_Offline,'/\\').'/resource/voice_routines_backups';
$lock=@fopen($path.'.lock','c+');
if (!$lock || !flock($lock,LOCK_EX)) vbotApiJsonResponse(['success'=>false,'message'=>'Không khóa được dữ liệu kịch bản'],500);
register_shutdown_function(function()use($lock){flock($lock,LOCK_UN);fclose($lock);});
try {
    $raw=vbotRoutineBackupRaw($path); $revision=hash('sha256',$raw);
    $action=$_POST['action']??'';
    if ($action==='list') {
        try { vbotRoutineBackupDecode($raw); $valid=true; } catch (InvalidArgumentException $error) { $valid=false; }
        vbotApiJsonResponse(['success'=>true,'revision'=>$revision,'current_valid'=>$valid,'backups'=>vbotRoutineBackupList($directory)]);
    }
    if ($action==='read') {
        $name=$_POST['name']??'';
        if ($name==='') { $content=$raw; $filename='voice_routines.json'; }
        else {
            $file=vbotRoutineBackupPath($directory,$name);
            if ($file===false) vbotApiJsonResponse(['success'=>false,'message'=>'Bản sao lưu không tồn tại'],404);
            $content=vbotRoutineBackupRaw($file);$filename=$name;
        }
        vbotApiJsonResponse(['success'=>true,'raw'=>$content,'filename'=>$filename,'revision'=>$revision]);
    }
    if (!in_array($action,['create','import','restore'],true)) vbotApiJsonResponse(['success'=>false,'message'=>'Thao tác không hợp lệ'],400);
    if (!is_string($_POST['revision']??null) || !hash_equals($revision,$_POST['revision']))
        vbotApiJsonResponse(['success'=>false,'message'=>'Cấu hình đã thay đổi. Tải lại danh sách sao lưu hoặc trang trước khi tiếp tục.'],409);
    if ($action==='create') {
        $name=vbotRoutineBackupCreate($directory,$raw);
        vbotApiJsonResponse(['success'=>true,'message'=>'Đã tạo bản sao lưu: '.$name,'revision'=>$revision,'backups'=>vbotRoutineBackupList($directory)]);
    }
    if ($action==='restore') {
        $file=vbotRoutineBackupPath($directory,$_POST['name']??null);
        if ($file===false) vbotApiJsonResponse(['success'=>false,'message'=>'Bản sao lưu không tồn tại'],404);
        $incoming=vbotRoutineBackupRaw($file);$mode='replace';
    } else { $incoming=$_POST['data']??null;$mode=$_POST['mode']??'merge'; }
    $result=vbotRoutineBackupReplace($path,$directory,$raw,$incoming,$mode);
    vbotApiJsonResponse(array_merge($result,['success'=>true,'message'=>'Đã nhập/khôi phục cấu hình. Bản trước thay đổi đã được sao lưu; kịch bản mới có hiệu lực ở lần gọi tiếp theo.', 'backups'=>vbotRoutineBackupList($directory)]));
} catch (LengthException $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],413);
} catch (InvalidArgumentException $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);
} catch (Throwable $error) {
    error_log('Voice routine backup: '.$error->getMessage());
    vbotApiJsonResponse(['success'=>false,'message'=>'Không đọc/ghi được dữ liệu sao lưu. Kiểm tra quyền truy cập và log VBot.'],500);
}
