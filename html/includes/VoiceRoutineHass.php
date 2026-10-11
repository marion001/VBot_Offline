<?php
require_once __DIR__.'/VoiceRoutines.php';
const VBOT_ROUTINE_HASS_MAX_BYTES = 20971520;

function vbotRoutineHassValidateStates($states): array {
    if (!is_array($states) || count($states)>50000 || $states!==array_values($states))
        throw new InvalidArgumentException('Danh sách thực thể Home Assistant không hợp lệ');
    $seen=[];
    foreach ($states as $entity) {
        if (!is_object($entity) || !is_string($entity->entity_id??null) ||
            !preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/D',$entity->entity_id) ||
            !is_string($entity->state??null) || !is_object($entity->attributes??null) || isset($seen[$entity->entity_id]))
            throw new InvalidArgumentException('Dữ liệu thực thể Home Assistant không hợp lệ');
        $seen[$entity->entity_id]=true;
    }
    return $states;
}
function vbotRoutineHassRead($path) {
    if (!is_file($path)) return (object)['get_hass_all'=>[], 'updated_at'=>null];
    $handle=@fopen($path,'rb');
    if (!$handle) throw new RuntimeException('Không đọc được file dữ liệu Home Assistant');
    try { $raw=stream_get_contents($handle,VBOT_ROUTINE_HASS_MAX_BYTES+1); }
    finally { fclose($handle); }
    if (!is_string($raw) || strlen($raw)>VBOT_ROUTINE_HASS_MAX_BYTES)
        throw new RuntimeException('File dữ liệu Home Assistant vượt quá 20 MB');
    $data=json_decode($raw);
    if (!is_object($data)) throw new InvalidArgumentException('File dữ liệu Home Assistant không hợp lệ');
    vbotRoutineHassValidateStates($data->get_hass_all??[]);
    return $data;
}
function vbotRoutineHassSave($path, array $states) {
    vbotRoutineHassValidateStates($states);
    if (!is_dir(dirname($path)) && !@mkdir(dirname($path),0775,true))
        throw new RuntimeException('Không tạo được thư mục dữ liệu Home Assistant');
    $lock=@fopen($path.'.lock','c+');
    if (!$lock || !flock($lock,LOCK_EX)) {
        if (is_resource($lock)) fclose($lock);
        throw new RuntimeException('Không khóa được dữ liệu Home Assistant');
    }
    try {
        $data=vbotRoutineHassRead($path);
        $data->get_hass_all=$states;
        $data->updated_at=gmdate('c');
        $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || strlen($json)+1>VBOT_ROUTINE_HASS_MAX_BYTES || !vbotRoutineWrite($path,$json."\n"))
            throw new RuntimeException('Không lưu được dữ liệu Home Assistant');
        if (!@chmod($path,0777)) throw new RuntimeException('Đã lưu nhưng không đặt được quyền 0777 cho dữ liệu Home Assistant');
        return $data;
    } finally { flock($lock,LOCK_UN); fclose($lock); }
}
