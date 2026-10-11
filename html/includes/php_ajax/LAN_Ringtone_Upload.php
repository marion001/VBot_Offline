<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
$VBot_Config_Read_Only=true;
require __DIR__.'/../../Configuration.php';
require_once __DIR__.'/../LANCalls.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))) {
    vbotApiJsonResponse(['success'=>false,'message'=>'Cần đăng nhập WebUI để tải nhạc chuông'],401);
}
vbotApiVerifyCsrf(true);
try {
    $file=$_FILES['ringtone']??null;
    if (!is_array($file) || ($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new InvalidArgumentException('Không nhận được file âm thanh hoặc tải lên thất bại');
    if (!is_uploaded_file($file['tmp_name']??'')) throw new InvalidArgumentException('File tải lên không hợp lệ');
    $name=vbotLANValidateRingtoneFile($file['tmp_name'],(string)$file['name']);
    $dir=dirname(__DIR__,3).'/resource/sound/call_ringtone';
    if (!is_dir($dir) && !@mkdir($dir,0777,true) && !is_dir($dir)) throw new RuntimeException('Không tạo được thư mục nhạc chuông');
    @chmod($dir,0777);
    if (file_exists($dir.'/'.$name)) $name=pathinfo($name,PATHINFO_FILENAME).'_'.bin2hex(random_bytes(6)).'.'.pathinfo($name,PATHINFO_EXTENSION);
    $temp=tempnam($dir,'.ringtone-');
    if ($temp===false) throw new RuntimeException('Không tạo được file tạm');
    try {
        if (!move_uploaded_file($file['tmp_name'],$temp) || !rename($temp,$dir.'/'.$name)) throw new RuntimeException('Không lưu được nhạc chuông');
        @chmod($dir.'/'.$name,0777);
    } finally { if (is_file($temp)) @unlink($temp); }
    vbotApiJsonResponse(['success'=>true,'name'=>$name,'files'=>vbotLANRingtones(),'message'=>'Đã tải nhạc chuông. Lưu cài đặt và khởi động lại VBot để sử dụng.']);
} catch (Throwable $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);
}
