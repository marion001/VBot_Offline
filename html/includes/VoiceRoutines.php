<?php
// Schema validation is mirrored by Voice_Routines.validate at execution time.
function vbotRoutineActions(array $config): array {
    require_once __DIR__.'/ActionRegistry.php';
    $allowed = array_flip(['media_play','media_pause','media_stop','media_next','media_previous',
        'mute','unmute','play_all_local','mic_on','mic_off','repeat_toggle','shuffle_toggle']);
    $actions = array_filter(vbotActionRegistryOptions($config), function ($key) use ($allowed) {
        return isset($allowed[$key]) || preg_match('/^(playlist:[A-Za-z0-9_-]{1,64}|radio:[0-9a-f]{12})$/D', $key);
    }, ARRAY_FILTER_USE_KEY);
    return $actions + ['play_local_file'=>'Phát một bài nhạc Local cụ thể',
        'play_url'=>'Phát từ URL / link trực tiếp / stream'];
}
function vbotRoutineMediaUrl($value): string {
    $url=vbotRoutineText($value,'Link media',8192);
    $parts=parse_url($url);
    if ($url!==$value || !is_array($parts) || !in_array(strtolower($parts['scheme']??''),['http','https'],true) ||
        empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || preg_match('/[\x00-\x20\x7f]/',$url))
        throw new InvalidArgumentException('Link media phải là URL HTTP/HTTPS hợp lệ, không chứa tài khoản hoặc khoảng trắng');
    return $url;
}
function vbotRoutineHassActions(): array {
    return ['light'=>['turn_on','turn_off','turn_setup'], 'fan'=>['turn_on','turn_off','turn_setup'],
        'switch'=>['turn_on','turn_off'], 'input_boolean'=>['turn_on','turn_off'],
        'climate'=>['turn_on','turn_off','turn_setup','turn_cool','turn_auto','turn_heat','turn_dry','turn_fan_only'],
        'cover'=>['turn_open','turn_close','turn_stop','turn_setup'], 'script'=>['turn_on'], 'scene'=>['turn_on']];
}
function vbotRoutineNormalize($text): string {
    if (class_exists('Normalizer')) $text = Normalizer::normalize($text, Normalizer::FORM_C);
    $upper = preg_split('//u', 'ÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴĐ', -1, PREG_SPLIT_NO_EMPTY);
    $lower = preg_split('//u', 'àáảãạăằắẳẵặâầấẩẫậèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ', -1, PREG_SPLIT_NO_EMPTY);
    // PHP 7 strtolower is locale-sensitive and can corrupt UTF-8 bytes.
    $text = strtr(strtr($text, array_combine($upper, $lower)), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    return trim(rtrim(trim(preg_replace('/\s+/u', ' ', $text)), '.!?'));
}
function vbotRoutineText($value, $label, $limit=200): string {
    if (!is_string($value) || trim($value)==='' || preg_match_all('/./us', $value) > $limit)
        throw new InvalidArgumentException($label.' phải có từ 1 đến '.$limit.' ký tự');
    return trim($value);
}
function vbotRoutineNumber($value, $min, $max, $label) {
    if ((!is_int($value) && !is_float($value)) || !is_finite((float)$value) || $value<$min || $value>$max)
        throw new InvalidArgumentException($label.' phải từ '.$min.' đến '.$max);
    return $value;
}
function vbotRoutineList($value): bool {
    return is_array($value) && $value===array_values($value);
}
function vbotRoutineCondition($condition): void {
    if ($condition===null) return;
    if (!is_array($condition) || !in_array($condition['mode']??null,['none','time','date_time'],true)) throw new InvalidArgumentException('Loại điều kiện kịch bản không hợp lệ');
    if ($condition['mode']==='none') return;
    foreach (['start_time','end_time'] as $field) {
        if (!is_string($condition[$field]??null) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$condition[$field])) throw new InvalidArgumentException('Giờ điều kiện phải đúng định dạng 24 giờ HH:MM');
    }
    if ($condition['start_time']===$condition['end_time']) throw new InvalidArgumentException('Giờ bắt đầu và kết thúc phải khác nhau');
    if ($condition['mode']==='date_time') {
        foreach (['start_date','end_date'] as $field) {
            $value=$condition[$field]??null;
            if (!is_string($value) || !preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D',$value,$parts) || !checkdate((int)$parts[2],(int)$parts[3],(int)$parts[1])) throw new InvalidArgumentException('Ngày điều kiện không hợp lệ, cần YYYY-MM-DD');
        }
        if (strcmp($condition['start_date'],$condition['end_date'])>0) throw new InvalidArgumentException('Ngày bắt đầu phải trước hoặc bằng ngày kết thúc');
    }
}
function vbotRoutineWrite($path, $content): bool {
    // Caller holds the main config lock for both backup and replacement.
    // Set permissions BEFORE rename so the Python service can always read it.
    $temporary=@tempnam(dirname($path),'.vbot-routine-');
    if ($temporary===false) return false;
    $handle=null;
    try {
        if (realpath(dirname($temporary))!==realpath(dirname($path))) return false;
        $handle=@fopen($temporary,'wb');
        if (!$handle) return false;
        $offset=0; $length=strlen($content);
        while ($offset<$length) {
            $written=fwrite($handle,substr($content,$offset));
            if ($written===false || $written===0) return false;
            $offset+=$written;
        }
        if (!fflush($handle)) return false;
        if (function_exists('fsync') && !fsync($handle)) return false;
        fclose($handle); $handle=null;
        if (!@chmod($temporary,0644)) return false;
        return @rename($temporary,$path);
    } finally {
        if (is_resource($handle)) fclose($handle);
        if (is_file($temporary)) @unlink($temporary);
    }
}
function vbotRoutinesValidate($data): array {
    if (!is_array($data) || ($data['version']??null)!==1 || !vbotRoutineList($data['routines']??null) || count($data['routines'])>50)
        throw new InvalidArgumentException('Cấu hình phải có version=1 và tối đa 50 kịch bản');
    $ids=[]; $names=[]; $triggers=[]; $routes=[]; $hass=vbotRoutineHassActions();
    $actions=['media_play','media_pause','media_stop','media_next','media_previous','mute','unmute',
        'play_all_local','mic_on','mic_off','repeat_toggle','shuffle_toggle','play_local_file',
        'play_url','play_youtube','play_zingmp3','play_nhaccuatui'];
    foreach ($data['routines'] as $item) {
        if (!is_array($item)) throw new InvalidArgumentException('Kịch bản không hợp lệ');
        $id=vbotRoutineText($item['id']??null,'ID',64);
        $name=vbotRoutineNormalize(vbotRoutineText($item['name']??null,'Tên',100));
        if ($name==='') throw new InvalidArgumentException('Tên kịch bản không được chỉ có dấu câu');
        if ($id!==$item['id'] || !preg_match('/^[A-Za-z0-9_-]+$/D',$id) || isset($ids[$id]) || isset($names[$name]))
            throw new InvalidArgumentException('ID và tên kịch bản phải duy nhất');
        $ids[$id]=true; $names[$name]=true;
        foreach (['enabled','stop_on_error'] as $field)
            if (!is_bool($item[$field]??null)) throw new InvalidArgumentException($field.' phải là true/false');
        vbotRoutineCondition($item['condition']??null);
        if (!vbotRoutineList($item['triggers']??null) || count($item['triggers'])<1 || count($item['triggers'])>10)
            throw new InvalidArgumentException('Mỗi kịch bản cần 1 đến 10 câu gọi');
        $keys=[];
        foreach ($item['triggers'] as $alias) {
            $key=vbotRoutineNormalize(vbotRoutineText($alias,'Câu gọi'));
            if ($key==='') throw new InvalidArgumentException('Câu gọi không được chỉ có dấu câu');
            if (isset($triggers[$key])) throw new InvalidArgumentException('Câu gọi phải duy nhất');
            $triggers[$key]=true; $keys[]=$key;
        }
        $keys[]='chạy kịch bản '.$name; $keys[]='kích hoạt kịch bản '.$name;
        foreach (['run routine','run the routine','activate routine','activate the routine'] as $prefix) $keys[]=$prefix.' '.$name;
        foreach ($keys as $key) {
            if (isset($routes[$key]) && $routes[$key]!==$id) throw new InvalidArgumentException('Câu gọi trùng với tên kịch bản khác');
            $routes[$key]=$id;
        }
        if (!vbotRoutineList($item['steps']??null) || count($item['steps'])<1 || count($item['steps'])>30)
            throw new InvalidArgumentException('Mỗi kịch bản cần 1 đến 30 bước');
        $wait=0;
        foreach ($item['steps'] as $step) {
            if (!is_array($step)) throw new InvalidArgumentException('Bước không hợp lệ');
            switch ($step['type']??'') {
                case 'wait': $wait+=vbotRoutineNumber($step['seconds']??null,0,120,'Thời gian chờ'); break;
                case 'volume': vbotRoutineNumber($step['value']??null,0,100,'Âm lượng'); break;
                case 'led_brightness': vbotRoutineNumber($step['value']??null,0,100,'Độ sáng LED'); break;
                case 'speak': vbotRoutineText($step['text']??null,'Thông báo',500); break;
                case 'action':
                    $action=vbotRoutineText($step['action']??null,'Thao tác',100);
                    if ($action!==$step['action'] || (!in_array($action,$actions,true) && !preg_match('/^(playlist:[A-Za-z0-9_-]{1,64}|radio:[0-9a-f]{12})$/D',$action)))
                        throw new InvalidArgumentException('Thao tác loa không được hỗ trợ');
                    if (in_array($action,['play_url','play_youtube','play_zingmp3','play_nhaccuatui'],true)) vbotRoutineMediaUrl($step['url']??null);
                    if ($action==='play_local_file') {
                        $path=vbotRoutineText($step['path']??null,'Bài nhạc Local',2048);
                        if ($path!==$step['path'] || strpos($path,"\0")!==false || strpos($path,'://')!==false)
                            throw new InvalidArgumentException('Đường dẫn bài nhạc Local không hợp lệ');
                    }
                    if (in_array($action,['play_local_file','play_url','play_youtube','play_zingmp3','play_nhaccuatui'],true)) {
                        $title=array_key_exists('title',$step)?$step['title']:'';
                        if (!is_string($title) || preg_match_all('/./us',$title)>200 || strpos($title,"\0")!==false)
                            throw new InvalidArgumentException('Tên media phải là văn bản tối đa 200 ký tự');
                    }
                    break;
                case 'home_assistant':
                    $entity=vbotRoutineText($step['entity_id']??null,'Entity ID',150);
                    $domain=explode('.',$entity)[0]; $action=$step['action']??'';
                    if ($entity!==$step['entity_id'] || !preg_match('/^[a-z_]+\.[a-z0-9_]+$/D',$entity) || !in_array($action,$hass[$domain]??[],true))
                        throw new InvalidArgumentException('Entity ID hoặc thao tác Home Assistant không hợp lệ');
                    if ($action==='turn_setup') vbotRoutineNumber($step['value']??null,$domain==='climate'?5:0,$domain==='climate'?40:100,'Giá trị thiết bị');
                    elseif (isset($step['value'])) throw new InvalidArgumentException('Chỉ bước đặt giá trị mới nhận giá trị thiết bị');
                    break;
                default: throw new InvalidArgumentException('Loại bước không được hỗ trợ');
            }
        }
        if ($wait>300) throw new InvalidArgumentException('Tổng thời gian chờ tối đa 300 giây');
    }
    return $data;
}
