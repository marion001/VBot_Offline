<?php
function vbotEQHelpIcon($text) {
    $action=htmlspecialchars('show_message('.json_encode($text,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).')',ENT_QUOTES,'UTF-8');
    return '<i class="bi bi-question-circle-fill" role="button" tabindex="0" onclick="'.$action.'" onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();this.click();}" aria-label="Giải thích EQ"></i>';
}
function vbotEQBands($input) {
    if (!is_array($input)) throw new InvalidArgumentException('Thông số EQ không hợp lệ');
    $bands=[];
    foreach (['bass_db','low_mid_db','mid_db','high_mid_db','treble_db','air_db','preamp_db','wet_percent'] as $name) {
        $value=$input[$name]??($name==='wet_percent'?100:(in_array($name,['low_mid_db','high_mid_db','air_db'],true)?0:null));
        if ((!is_int($value) && !is_float($value)) || !is_finite((float)$value) || $value<($name==='wet_percent'?0:-12) || $value>($name==='wet_percent'?100:($name==='preamp_db'?0:12))) throw new InvalidArgumentException('Giá trị EQ không hợp lệ: '.$name);
        $bands[$name]=$value;
    }
    return $bands;
}
function vbotEQInitial($legacy=[]) {
    $bands=vbotEQBands(array_merge(['bass_db'=>0,'low_mid_db'=>0,'mid_db'=>0,'high_mid_db'=>0,'treble_db'=>0,'air_db'=>0,'preamp_db'=>0,'wet_percent'=>100],is_array($legacy)?$legacy:[]));
    return ['version'=>1,'boot_id'=>'','selected_preset'=>'flat','default_preset'=>'flat','current'=>$bands,
        'presets'=>[['id'=>'flat','name'=>array_sum(array_map('abs',array_diff_key($bands,['wet_percent'=>true])))==0?'Phẳng':'Cấu hình trước đây','bands'=>$bands]]];
}
function vbotEQIncomingBands($input,$current) {
    if (!is_array($input)) throw new InvalidArgumentException('Thông số EQ không hợp lệ');
    foreach (['low_mid_db','high_mid_db','air_db','wet_percent'] as $name) if (!array_key_exists($name,$input)) $input[$name]=$current[$name]??($name==='wet_percent'?100:0);
    return vbotEQBands($input);
}
function vbotEQNormalize($value) {
    if (!is_array($value) || ($value['version']??null)!==1 || !is_array($value['presets']??null) || count($value['presets'])<1 || count($value['presets'])>50) throw new InvalidArgumentException('File dữ liệu EQ không hợp lệ');
    $presets=[];$ids=[];
    foreach ($value['presets'] as $preset) {
        $id=$preset['id']??'';$name=trim($preset['name']??'');
        if (!is_string($id) || !preg_match('/^[a-z0-9_-]{1,64}$/D',$id) || isset($ids[$id]) || !is_string($name) || !preg_match('/^.{1,80}$/usD',$name) || preg_match('/[\x00-\x1f\x7f]/',$name)) throw new InvalidArgumentException('Tên hoặc ID mẫu EQ không hợp lệ');
        $ids[$id]=true;$presets[]=['id'=>$id,'name'=>$name,'bands'=>vbotEQBands($preset['bands']??null)];
    }
    $selected=$value['selected_preset']??'';
    $builtinSelected=is_string($selected) && in_array($selected,array_column(vbotEQBuiltinModes(),'id'),true);
    if (!isset($ids[$value['default_preset']??'']) || (!isset($ids[$selected]) && !$builtinSelected)) throw new InvalidArgumentException('Mẫu EQ mặc định hoặc đang chọn không tồn tại');
    return ['version'=>1,'boot_id'=>(string)($value['boot_id']??''),'default_preset'=>$value['default_preset'],'selected_preset'=>$value['selected_preset'],
        'current'=>vbotEQBands($value['current']??null),'presets'=>$presets];
}
function vbotEQBootID() {
    return trim((string)@file_get_contents('/proc/sys/kernel/random/boot_id'));
}
function vbotEQRead($path,$legacy=[]) {
    if (!file_exists($path)) $state=vbotEQInitial($legacy);
    else {
        if (filesize($path)>65536) throw new RuntimeException('File EQ vượt giới hạn dung lượng');
        $decoded=json_decode(file_get_contents($path),true);
        $state=!empty($decoded['legacy_import_pending']) && isset($legacy['bass_db'])?vbotEQInitial($legacy):vbotEQNormalize($decoded);
    }
    $boot=vbotEQBootID();
    if ($boot!=='' && $state['boot_id']!==$boot) {
        foreach ($state['presets'] as $preset) if ($preset['id']===$state['default_preset']) {
            $state['current']=$preset['bands'];$state['selected_preset']=$preset['id'];break;
        }
    }
    return $state;
}
function vbotEQBuiltinModes() {
            $modes=['flat'=>[0,0,0,0,0,0],'bass'=>[4,2,0,-1,-1,0],'vocal'=>[-2,-1,0,2,2,0],'clear'=>[-2,-1,0,1,3,2],'warm'=>[3,2,0,-1,-2,-1],'rock'=>[4,2,-1,1,3,2],'night'=>[-3,-2,0,2,1,-2]];
            $names=['flat'=>'Flat — Cân bằng','bass'=>'Bass Boost — Âm trầm','vocal'=>'Vocal — Giọng nói','clear'=>'Clarity — Chi tiết','warm'=>'Warm — Ấm áp','rock'=>'Rock — Sôi động','night'=>'Night — Nghe ban đêm'];
    $result=[];
    foreach ($modes as $id=>$gains) {
        $bands=array_combine(['bass_db','low_mid_db','mid_db','high_mid_db','treble_db','air_db'],$gains);
        $bands['preamp_db']=-3; $bands['wet_percent']=100;
        $result[]=['id'=>'builtin:'.$id,'name'=>$names[$id],'bands'=>$bands];
    }
    return $result;
}
function vbotEQOperation($state,$data) {
    $action=$data['action']??'';
    $index=null;
    foreach ($state['presets'] as $i=>$preset) if ($preset['id']===($data['id']??null)) $index=$i;
    if ($action==='apply') $state['current']=vbotEQIncomingBands($data['bands']??null,$state['current']);
    elseif ($action==='select') {
        if ($index===null) {
            $builtin=null;
            foreach(vbotEQBuiltinModes() as $mode) if($mode['id']===($data['id']??'')) { $builtin=$mode; break; }
            if ($builtin===null) throw new InvalidArgumentException('Mẫu EQ không còn tồn tại');
            $state['selected_preset']=$builtin['id'];$state['current']=$builtin['bands'];
        } else {$state['selected_preset']=$state['presets'][$index]['id'];$state['current']=$state['presets'][$index]['bands'];}
    } elseif ($action==='save') {
        $name=$data['name']??null;
        if (!is_string($name) || !preg_match('/^.{1,80}$/usD',trim($name)) || preg_match('/[\x00-\x1f\x7f]/',$name)) throw new InvalidArgumentException('Nhập tên mẫu EQ hợp lệ, tối đa 80 ký tự tiếng Việt');
        $name=trim($name);
        foreach ($state['presets'] as $i=>$preset) if ($i!==$index && strcasecmp($preset['name'],$name)===0) throw new InvalidArgumentException('Tên mẫu EQ đã tồn tại');
        if (!empty($data['id']) && $index===null) throw new InvalidArgumentException('Mẫu EQ cần sửa không còn tồn tại');
        if ($index===null && count($state['presets'])>=50) throw new InvalidArgumentException('Chỉ lưu tối đa 50 mẫu EQ');
        $preset=['id'=>$index===null?bin2hex(random_bytes(8)):$state['presets'][$index]['id'],'name'=>$name,'bands'=>vbotEQIncomingBands($data['bands']??null,$state['current'])];
        if ($index===null) $state['presets'][]=$preset;else $state['presets'][$index]=$preset;
        $state['selected_preset']=$preset['id'];$state['current']=$preset['bands'];
    } elseif ($action==='default') {
        $builtin=$data['id']??'';
        if (is_string($builtin) && strpos($builtin,'builtin:')===0) {
            $key=substr($builtin,8);
            $modes=[];$names=[];
            foreach(vbotEQBuiltinModes() as $mode) {
                $modeID=substr($mode['id'],8);$names[$modeID]=$mode['name'];
                $modes[$modeID]=array_values(array_intersect_key($mode['bands'],array_flip(['bass_db','low_mid_db','mid_db','high_mid_db','treble_db','air_db'])));
            }
            if (!isset($modes[$key])) throw new InvalidArgumentException('Chế độ EQ không hợp lệ');
            $id='_builtin_'.$key;$index=null;
            foreach ($state['presets'] as $i=>$p) if ($p['id']===$id) $index=$i;
            if ($index===null && count($state['presets'])>=50) throw new InvalidArgumentException('Chỉ lưu tối đa 50 mẫu EQ');
            $bands=array_combine(['bass_db','low_mid_db','mid_db','high_mid_db','treble_db','air_db'],$modes[$key]);
            $bands['preamp_db']=-3;$bands['wet_percent']=100;
            $entry=['id'=>$id,'name'=>$names[$key],'bands'=>$bands];
            if ($index===null) {$state['presets'][]=$entry;$index=count($state['presets'])-1;} else $state['presets'][$index]=$entry;
            $state['selected_preset']=$id;$state['current']=$bands;
        }
        if ($index===null) throw new InvalidArgumentException('Mẫu EQ mặc định không còn tồn tại');
        $state['default_preset']=$state['presets'][$index]['id'];
    } elseif ($action==='delete') {
        if ($index===null) throw new InvalidArgumentException('Mẫu EQ cần xóa không còn tồn tại');
        if (count($state['presets'])===1) throw new InvalidArgumentException('Cần giữ ít nhất một mẫu EQ');
        $id=$state['presets'][$index]['id'];array_splice($state['presets'],$index,1);
        if ($state['default_preset']===$id) $state['default_preset']=$state['presets'][0]['id'];
        if ($state['selected_preset']===$id) {
            foreach ($state['presets'] as $preset) if ($preset['id']===$state['default_preset']) {
                $state['selected_preset']=$preset['id'];$state['current']=$preset['bands'];break;
            }
        }
    } else throw new InvalidArgumentException('Thao tác EQ không hợp lệ');
    $state['boot_id']=vbotEQBootID();
    return vbotEQNormalize($state);
}
