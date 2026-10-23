<?php
// Local CLI only. Reuse exactly the same preset normalization/operation as WebUI.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once dirname(__DIR__,2).'/html/includes/EqualizerStorage.php';
$root=dirname(__DIR__,2);$path=__DIR__.'/settings.json';$lock=null;$temporary=null;
try {
    $input=stream_get_contents(STDIN,4097);
    $request=json_decode($input,true);
    if (strlen($input)>4096 || !is_array($request)) throw new InvalidArgumentException('Dữ liệu EQ không hợp lệ');
    $action=$request['action']??'snapshot';
    if (!in_array($action,['snapshot','select','apply'],true)) throw new InvalidArgumentException('Thao tác EQ không hợp lệ');
    $config=json_decode(file_get_contents($root.'/Config.json'),true);
    if (!is_array($config)) throw new RuntimeException('Không đọc được Config.json');
    if ($action!=='snapshot') {
        $lock=fopen($path.'.lock','c+');
        if (!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Không khóa được dữ liệu EQ');
        @chmod($path.'.lock',0777);
    }
    $state=vbotEQRead($path,$config['media_player']['equalizer']??[]);
    if ($action!=='snapshot') {
        if ($action==='select') {
            $id=$request['id']??'';
            if (!is_string($id)) throw new InvalidArgumentException('ID EQ không hợp lệ');
            $state=vbotEQOperation($state,['action'=>'select','id'=>$id]);
        } else {
            $patch=$request['bands']??null;
            if (!is_array($patch) || !$patch || count($patch)>8) throw new InvalidArgumentException('Thông số EQ không hợp lệ');
            foreach($patch as $key=>$value) if(!array_key_exists($key,$state['current']) || (!is_int($value) && !is_float($value))) throw new InvalidArgumentException('Dải EQ không hợp lệ');
            // Merge inside the same file lock: changing one band cannot undo another.
            $state=vbotEQOperation($state,['action'=>'apply','bands'=>array_merge($state['current'],$patch)]);
        }
        $temporary=tempnam(__DIR__,'.eq-mqtt-');
        $encoded=json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if ($temporary===false || $encoded===false || file_put_contents($temporary,$encoded."\n")===false || !chmod($temporary,0777) || !rename($temporary,$path)) throw new RuntimeException('Không lưu được EQ');
        $temporary=null;
    }
    $modes=vbotEQBuiltinModes();$options=$modes;$current='custom';
    foreach ($state['presets'] as $preset) {
        if (strpos($preset['id'],'_builtin_')!==0) $options[]=['id'=>$preset['id'],'name'=>$preset['name'],'bands'=>$preset['bands']];
        if ($preset['id']===$state['selected_preset'] && $preset['bands']==$state['current']) $current=strpos($preset['id'],'_builtin_')===0?'builtin:'.substr($preset['id'],9):$preset['id'];
    }
    if ($current==='custom') foreach($modes as $mode) if($mode['bands']==$state['current']) { $current=$mode['id']; break; }
    // Custom is a read-only description; selecting it is a no-op in HA.
    $options[]=['id'=>'custom','name'=>'Custom — Tùy chỉnh'];
    $revision=hash('sha256',json_encode([$state,!empty($config['media_player']['equalizer']['active'])]));
    echo json_encode(['success'=>true,'revision'=>$revision,'active'=>!empty($config['media_player']['equalizer']['active']),
        'current_id'=>$current,'default_id'=>$state['default_preset'],'presets'=>$options,'state'=>$state],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch(Throwable $error) {
    echo json_encode(['success'=>false,'message'=>$error->getMessage()],JSON_UNESCAPED_UNICODE);
} finally {
    if (is_string($temporary) && file_exists($temporary)) unlink($temporary);
    if (is_resource($lock)) {flock($lock,LOCK_UN);fclose($lock);}
}
