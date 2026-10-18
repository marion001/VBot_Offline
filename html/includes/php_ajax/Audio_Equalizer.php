<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['GET','POST']);
$VBot_Config_Read_Only=true;
require __DIR__.'/../../Configuration.php';
require_once __DIR__.'/../EqualizerStorage.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))) {
    vbotApiJsonResponse(['success'=>false,'message'=>'Cần đăng nhập WebUI để chỉnh EQ'],401);
}
if ($_SERVER['REQUEST_METHOD']==='POST') vbotApiVerifyCsrf(true);
session_write_close();
$root=dirname(__DIR__,3);$path=$root.'/resource/equalizer/settings.json';$lock=null;
try {
    $legacy=$Config['media_player']['equalizer']??[];
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $body=file_get_contents('php://input',false,null,0,4097);
        $data=json_decode($body,true);
        if (strlen($body)>4096 || !is_array($data)) throw new InvalidArgumentException('Dữ liệu EQ không hợp lệ');
        $lock=@fopen($path.'.lock','c+');
        if (is_resource($lock)) @chmod($path.'.lock',0777);
        if ($lock===false || !flock($lock,LOCK_EX)) throw new RuntimeException('Không khóa được dữ liệu EQ');
        $state=vbotEQRead($path,$legacy);
        if (($data['action']??'')==='toggle') {
            if (!isset($data['active']) || !is_bool($data['active'])) throw new InvalidArgumentException('Trạng thái EQ không hợp lệ');
            if ($data['active'] && !is_file($root.'/resource/equalizer/libvbot_eq.so')) throw new RuntimeException('Chưa cài EQ. Chạy sh resource/equalizer/build_eq.sh trên loa.');
            $proposed=$Config;$proposed['media_player']['equalizer']=['active'=>$data['active']];
            if (!vbotConfigWriteChanges($root.'/Config.json',$Config,$proposed,$saved)) throw new RuntimeException('Không lưu được công tắc EQ');
            @chmod($root.'/Config.json',0777);$Config=$saved;
        } else $state=vbotEQOperation($state,$data);
        $encoded=json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if ($encoded===false || !vbotAtomicWriteFile($path,$encoded."\n",'EQ presets',true)) throw new RuntimeException('Không lưu được dữ liệu EQ');
        @chmod($path,0777);
    } else $state=vbotEQRead($path,$legacy);
    if (is_resource($lock)) {flock($lock,LOCK_UN);fclose($lock);$lock=null;}
    vbotApiJsonResponse(['success'=>true,'active'=>!empty($Config['media_player']['equalizer']['active']),'state'=>$state,
        'message'=>'Đã lưu EQ']);
} catch (Throwable $error) {
    if (is_resource($lock)) {flock($lock,LOCK_UN);fclose($lock);}
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);
}
