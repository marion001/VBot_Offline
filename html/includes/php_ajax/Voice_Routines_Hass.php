<?php
require_once __DIR__.'/Api_Helpers.php';
require_once dirname(__DIR__).'/VoiceRoutineHass.php';
vbotApiInitialize(['GET','POST']);
include __DIR__.'/../../Configuration.php';
if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status()!==PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))
        vbotApiJsonResponse(['success'=>false,'message'=>'Bạn cần đăng nhập WebUI'],401);
}
if ($_SERVER['REQUEST_METHOD']==='POST') vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
header('Cache-Control: no-store');
$path=rtrim($VBot_Offline,'/\\').'/resource/hass/Home_Assistant.json';
try {
    if ($_SERVER['REQUEST_METHOD']==='GET') {
        $data=vbotRoutineHassRead($path);
        vbotApiJsonResponse(['success'=>true,'data'=>['get_hass_all'=>$data->get_hass_all??[],
            'updated_at'=>$data->updated_at??null], 'exists'=>is_file($path)]);
    }
    if (($_POST['action']??'')!=='refresh') vbotApiJsonResponse(['success'=>false,'message'=>'Thao tác không hợp lệ'],400);
    if (!function_exists('curl_init')) throw new RuntimeException('PHP chưa được cài phần mở rộng cURL');
    $hass=$Config['home_assistant']??[];
    $token=$hass['long_token']??'';
    $urls=[];
    foreach (['internal_url','external_url'] as $key) {
        $url=rtrim(trim((string)($hass[$key]??'')),'/');
        $parts=parse_url($url);
        if ($url!=='' && is_array($parts) && in_array($parts['scheme']??'', ['http','https'],true) &&
            !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) &&
            !isset($parts['query']) && !isset($parts['fragment'])) $urls[]=$url;
    }
    if (!is_string($token) || trim($token)==='' || !$urls)
        vbotApiJsonResponse(['success'=>false,'message'=>'Hãy lưu URL và Long Token Home Assistant trong phần cấu hình trước.'],400);
    $states=null;
    foreach (array_unique($urls) as $url) {
        $response=''; $tooLarge=false;
        $ch=curl_init($url.'/api/states');
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
        curl_setopt($ch,CURLOPT_TIMEOUT,25);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
        curl_setopt($ch,CURLOPT_HTTPHEADER,['Authorization: Bearer '.$token,'Accept: application/json']);
        curl_setopt($ch,CURLOPT_WRITEFUNCTION,function ($handle,$chunk) use (&$response,&$tooLarge) {
            if (strlen($response)+strlen($chunk)>VBOT_ROUTINE_HASS_MAX_BYTES) { $tooLarge=true; return 0; }
            $response.=$chunk; return strlen($chunk);
        });
        $ok=curl_exec($ch);
        $status=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($tooLarge) vbotApiJsonResponse(['success'=>false,'message'=>'Dữ liệu Home Assistant vượt quá 20 MB; bản đã lưu được giữ nguyên.'],413);
        if ($status===401 || $status===403)
            vbotApiJsonResponse(['success'=>false,'message'=>'Home Assistant từ chối truy cập. Kiểm tra Long Token; bản đã lưu được giữ nguyên.'],502);
        if ($ok===false || $status<200 || $status>=300) continue;
        $states=vbotRoutineHassValidateStates(json_decode($response));
        break;
    }
    if ($states===null) vbotApiJsonResponse(['success'=>false,'message'=>'Không kết nối được Home Assistant; bản đã lưu được giữ nguyên.'],502);
    $data=vbotRoutineHassSave($path,$states);
    vbotApiJsonResponse(['success'=>true,'message'=>'Đã tải và lưu '.count($states).' thực thể Home Assistant.',
        'data'=>['get_hass_all'=>$data->get_hass_all,'updated_at'=>$data->updated_at], 'exists'=>true]);
} catch (InvalidArgumentException $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage().'; bản đã lưu được giữ nguyên.'],502);
} catch (Throwable $error) {
    error_log('Voice routines Home Assistant cache: '.$error->getMessage());
    vbotApiJsonResponse(['success'=>false,'message'=>'Không tải/đọc/lưu được dữ liệu Home Assistant. Kiểm tra cấu hình, quyền thư mục và log VBot.'],500);
}
