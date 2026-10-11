<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['GET']);
include __DIR__.'/../../Configuration.php';
if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status()!==PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))
        vbotApiJsonResponse(['success'=>false,'message'=>'Bạn cần đăng nhập WebUI'],401);
}
if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
header('Cache-Control: no-store');
try {
    $settings=$Config['media_player']['music_local']??[];
    $configured=$settings['path']??'';
    if (!is_string($configured) || trim($configured)==='') throw new RuntimeException('Chưa cấu hình thư mục nhạc Local');
    $absolute=preg_match('~^(?:[A-Za-z]:[\\\\/]|[\\\\/])~',$configured);
    $root=realpath($absolute?$configured:rtrim($VBot_Offline,'/\\').'/'.$configured);
    if ($root===false || !is_dir($root) || !is_readable($root)) throw new RuntimeException('Không đọc được thư mục nhạc Local');
    $formats=array_map(function ($item) { return ltrim(strtolower((string)$item),'.'); },$settings['allowed_formats']??[]);
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
    $files=[];
    foreach ($iterator as $file) {
        if (!$file->isFile() || !in_array(strtolower($file->getExtension()),$formats,true)) continue;
        $path=$file->getRealPath();
        if ($path===false || strpos($path,$root.DIRECTORY_SEPARATOR)!==0 || !in_array(strtolower(pathinfo($path,PATHINFO_EXTENSION)),$formats,true)) continue;
        $files[]=['path'=>$path,'name'=>substr($path,strlen($root)+1)];
        if (count($files)>10000) throw new RuntimeException('Thư mục vượt quá 10000 bài nhạc; hãy dùng thư mục nhỏ hơn');
    }
    usort($files,function ($a,$b) { return strnatcasecmp($a['name'],$b['name']); });
    vbotApiJsonResponse(['success'=>true,'files'=>$files,'message'=>'Đã đọc '.count($files).' bài nhạc Local']);
} catch (Throwable $error) {
    error_log('Voice routines local music: '.$error->getMessage());
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage()],400);
}
