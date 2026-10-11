<?php
require_once __DIR__.'/Api_Helpers.php';
require_once dirname(__DIR__).'/VoiceRoutines.php';
vbotApiInitialize(['POST']);
include __DIR__.'/../../Configuration.php';
if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status()!==PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_login'])) vbotApiJsonResponse(['success'=>false,'message'=>'Bạn chưa đăng nhập'],401);
}
vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
$path=rtrim($VBot_Offline,'/\\').'/resource/voice_routines.json';
$lock=@fopen($path.'.lock','c+');
if (!$lock || !flock($lock,LOCK_EX)) vbotApiJsonResponse(['success'=>false,'message'=>'Không khóa được cấu hình kịch bản'],500);
register_shutdown_function(function () use ($lock) { flock($lock,LOCK_UN); fclose($lock); });
try {
    $raw=is_file($path)?file_get_contents($path):'{"version":1,"routines":[]}';
    if (!is_string($raw) || strlen($raw)>1048576) throw new RuntimeException('Không đọc được cấu hình hoặc file vượt quá 1 MB');
    $current=vbotRoutinesValidate(json_decode($raw,true));
    $revision=hash('sha256',$raw);
    $action=$_POST['action']??'';
    if ($action==='list') vbotApiJsonResponse(['success'=>true,'data'=>$current,'revision'=>$revision]);
    if ($action!=='save') vbotApiJsonResponse(['success'=>false,'message'=>'Thao tác không hợp lệ'],400);
    if (!is_string($_POST['revision']??null) || !hash_equals($revision,$_POST['revision']))
        vbotApiJsonResponse(['success'=>false,'message'=>'Cấu hình đã thay đổi ở phiên khác. Hãy sao chép thay đổi của bạn rồi tải lại trang.'],409);
    $input=$_POST['data']??'';
    if (!is_string($input) || strlen($input)>1048576) vbotApiJsonResponse(['success'=>false,'message'=>'Dữ liệu vượt quá 1 MB'],413);
    $data=vbotRoutinesValidate(json_decode($input,true));
    $encoded=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    if (strlen($encoded)>1048576) vbotApiJsonResponse(['success'=>false,'message'=>'Dữ liệu vượt quá 1 MB'],413);
    if (is_file($path) && !vbotRoutineWrite($path.'.bak',$raw))
        throw new RuntimeException('Không sao lưu được cấu hình cũ; chưa lưu thay đổi');
    if (!vbotRoutineWrite($path,$encoded))
        throw new RuntimeException('Không ghi được cấu hình kịch bản');
    vbotApiJsonResponse(['success'=>true,'message'=>'Đã lưu. Câu gọi mới có hiệu lực ngay, không cần khởi động lại.',
        'data'=>$data,'revision'=>hash('sha256',$encoded)]);
} catch (InvalidArgumentException $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);
} catch (Throwable $error) {
    error_log('Voice routines storage: '.$error->getMessage());
    vbotApiJsonResponse(['success'=>false,'message'=>'Không đọc/ghi được cấu hình. File hiện tại được giữ nguyên; kiểm tra quyền truy cập và log VBot.'],500);
}
