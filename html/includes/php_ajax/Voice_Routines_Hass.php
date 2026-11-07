<?php
require_once __DIR__.'/Api_Helpers.php';
require_once dirname(__DIR__).'/VoiceRoutineHass.php';
vbotApiInitialize(['GET','POST']);
include __DIR__.'/../../Configuration.php';
if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status()!==PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))
        vbotApiJsonResponse(['success'=>false,'message'=>'B?n c?n dang nh?p WebUI'],401);
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
    if (($_POST['action']??'')!=='refresh') vbotApiJsonResponse(['success'=>false,'message'=>html_entity_decode('Thao t&#225;c kh&#244;ng h&#7907;p l&#7879;',ENT_QUOTES,'UTF-8')],400);
    if (!function_exists('curl_init')) throw new RuntimeException(html_entity_decode('PHP ch&#432;a &#273;&#432;&#7907;c c&#224;i ph&#7847;n m&#7903; r&#7897;ng cURL',ENT_QUOTES,'UTF-8'));
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
        vbotApiJsonResponse(['success'=>false,'message'=>html_entity_decode('H&#227;y l&#432;u URL v&#224; Long Token Home Assistant trong ph&#7847;n c&#7845;u h&#236;nh tr&#432;&#7899;c.',ENT_QUOTES,'UTF-8')],400);
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
        if ($tooLarge) vbotApiJsonResponse(['success'=>false,'message'=>html_entity_decode('D&#7919; li&#7879;u Home Assistant v&#432;&#7907;t qu&#225; 20 MB; b&#7843;n &#273;&#227; l&#432;u &#273;&#432;&#7907;c gi&#7919; nguy&#234;n.',ENT_QUOTES,'UTF-8')],413);
        if ($status===401 || $status===403) vbotApiJsonResponse(['success'=>false,'message'=>html_entity_decode('Home Assistant t&#7915; ch&#7889;i truy c&#7853;p. Ki&#7875;m tra Long Token; b&#7843;n &#273;&#227; l&#432;u &#273;&#432;&#7907;c gi&#7919; nguy&#234;n.',ENT_QUOTES,'UTF-8')],502);
        if ($ok===false || $status<200 || $status>=300) continue;
        if ($ok===false || $status<200 || $status>=300) continue;
        $states=vbotRoutineHassValidateStates(json_decode($response));
        break;
    }
    if ($states===null) vbotApiJsonResponse(['success'=>false,'message'=>html_entity_decode('Kh&#244;ng k&#7871;t n&#7889;i &#273;&#432;&#7907;c Home Assistant; b&#7843;n &#273;&#227; l&#432;u &#273;&#432;&#7907;c gi&#7919; nguy&#234;n.',ENT_QUOTES,'UTF-8')],502);
    $data=vbotRoutineHassSave($path,$states);
    vbotApiJsonResponse(['success'=>true,'message'=>html_entity_decode('&#272;&#227; t&#7843;i v&#224; l&#432;u ',ENT_QUOTES,'UTF-8').count($states).html_entity_decode(' th&#7921;c th&#7875; Home Assistant.',ENT_QUOTES,'UTF-8'),
        'data'=>['get_hass_all'=>$data->get_hass_all,'updated_at'=>$data->updated_at], 'exists'=>true]);
} catch (InvalidArgumentException $error) {
    vbotApiJsonResponse(['success'=>false,'message'=>$error->getMessage().html_entity_decode('; b&#7843;n &#273;&#227; l&#432;u &#273;&#432;&#7907;c gi&#7919; nguy&#234;n.',ENT_QUOTES,'UTF-8')],502);
} catch (Throwable $error) {
    error_log('Voice routines Home Assistant cache: '.$error->getMessage());
    vbotApiJsonResponse(['success'=>false,'message'=>html_entity_decode('Kh&#244;ng t&#7843;i/&#273;&#7885;c/l&#432;u &#273;&#432;&#7907;c d&#7919; li&#7879;u Home Assistant. Ki&#7875;m tra c&#7845;u h&#236;nh, quy&#7873;n th&#432; m&#7909;c v&#224; log VBot.',ENT_QUOTES,'UTF-8')],500);
}
