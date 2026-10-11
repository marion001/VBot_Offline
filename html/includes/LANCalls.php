<?php
require_once __DIR__.'/DeviceIdentity.php';
function vbotLANCallsSettings(array $post, array $old, array $buttons=[], $profileName=null): array {
    $settings=$old;
    $settings['active']=isset($post['lan_active']);
    if (isset($post['lan_end_button_present'])) {
        $settings['end_button_active']=isset($post['lan_end_button_active']);
        $settings['end_button']=trim((string)($post['lan_end_button']??''));
        $settings['end_button_press']=(string)($post['lan_end_button_press']??'short');
        if (!in_array($settings['end_button_press'],['short','hold','both'],true)) throw new InvalidArgumentException('Kiểu nhấn nút ngắt cuộc gọi không hợp lệ');
        if ($settings['active'] && $settings['end_button_active'] && (!isset($buttons[$settings['end_button']]) || empty($buttons[$settings['end_button']]['active']))) throw new InvalidArgumentException('Chọn nút đang bật để ngắt cuộc gọi');
    }
    if (isset($post['lan_ptt_present'])) {
        $settings['ptt_button_active']=isset($post['lan_ptt_button_active']);
        $settings['ptt_button']=trim((string)($post['lan_ptt_button']??''));
        if ($settings['active'] && $settings['ptt_button_active'] && (!isset($buttons[$settings['ptt_button']]) || empty($buttons[$settings['ptt_button']]['active']))) throw new InvalidArgumentException('Chọn một nút vật lý đang bật trong cấu hình Button để nhấn giữ nói');
    }
    if (isset($post['lan_ringtone'])) {
        $ringtone=(string)$post['lan_ringtone'];
        if ($ringtone!=='' && !in_array($ringtone,vbotLANRingtones(),true)) throw new InvalidArgumentException('Nhạc chuông cuộc gọi không tồn tại trong resource/sound/call_ringtone');
        $settings['ringtone']=$ringtone;
    }
    foreach (['id','name','output_device','mode'] as $key) $settings[$key]=trim((string)($post['lan_'.$key]??''));
    if ($profileName!==null) $settings['name']=trim((string)$profileName)!==''?trim((string)$profileName):'Loa VBot';
    $settings['use_mdns_id']=isset($post['lan_identity_present'])?isset($post['lan_use_mdns_id']):($old['use_mdns_id']??true);
    if ($settings['use_mdns_id']) {
        $identity=vbotStableDeviceId();
        if ($identity!=='') $settings['id']=$identity;
    }
    $newKey=trim((string)($post['lan_shared_key']??''));
    if ($newKey!=='') $settings['shared_key']=$newKey;
    else $settings['shared_key']=trim((string)($settings['shared_key']??''));
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$settings['id']) || !preg_match('/^.{1,100}$/usD',$settings['name'])) throw new InvalidArgumentException('ID hoặc tên loa không hợp lệ');
    if ($settings['active']) {
        $key=$settings['shared_key'];
        if ($key==='') throw new InvalidArgumentException('Chưa có khóa chung. Nhập khóa rồi lưu để bật gọi điện LAN.');
        if (strlen($key)<16) throw new InvalidArgumentException('Khóa chung quá ngắn: cần ít nhất 16 ký tự.');
        if (strlen($key)>200) throw new InvalidArgumentException('Khóa chung quá dài: tối đa 200 ký tự.');
        if (!preg_match('/^[\x21-\x7e]+$/D',$key)) throw new InvalidArgumentException('Khóa chung chỉ dùng ký tự ASCII, không có khoảng trắng bên trong hoặc chữ có dấu.');
    }
    if (!in_array($settings['mode'],['full_duplex','push_to_talk'],true)) throw new InvalidArgumentException('Chế độ gọi không hợp lệ');
    if ($settings['active'] && $settings['mode']==='push_to_talk' && !empty($settings['ptt_button_active']) && !empty($settings['end_button_active']) && ($settings['ptt_button']??'')===($settings['end_button']??'')) throw new InvalidArgumentException('Nút nhấn giữ để nói và nút ngắt cuộc gọi phải khác nhau');
    if ($settings['output_device']==='' || strlen($settings['output_device'])>150 || preg_match('/[\x00-\x1f\x7f]/',$settings['output_device'])) throw new InvalidArgumentException('Thiết bị ALSA không hợp lệ');
    foreach (['control_port','audio_port','ring_timeout','max_duration'] as $key) {
        $raw=(string)($post['lan_'.$key]??'');
        $port=strpos($key,'port')!==false;
        if (!ctype_digit($raw) || (int)$raw<($port?1024:10) || (int)$raw>($port?65535:7200)) throw new InvalidArgumentException('Cổng hoặc thời gian chờ không hợp lệ');
        $settings[$key]=(int)$raw;
    }
    $raw=$post['lan_peers']??json_encode($old['peers']??[]);
    if (!is_string($raw) || strlen($raw)>40000) throw new InvalidArgumentException('Danh sách loa quá lớn');
    $peers=json_decode($raw,true);
    if (!is_array($peers) || array_values($peers)!==$peers || count($peers)>50) throw new InvalidArgumentException('Danh sách loa không hợp lệ');
    $seen=[];$hosts=[];
    foreach ($peers as &$peer) {
        if (!is_array($peer) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$peer['id']??'') || $peer['id']===$settings['id'] || isset($seen[$peer['id']])) throw new InvalidArgumentException('ID loa đích trùng hoặc không hợp lệ');
        $host=$peer['host']??'';
        if (!filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || !preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/',$host) || isset($hosts[$host])) throw new InvalidArgumentException('Mỗi loa cần IP riêng thuộc mạng LAN');
        if (!is_string($peer['name']??null) || trim($peer['name'])==='' || !preg_match('/^.{1,100}$/usD',$peer['name'])) throw new InvalidArgumentException('Tên loa đích không hợp lệ');
        foreach (['control_port'=>5010,'audio_port'=>5011] as $field=>$default) {
            $port=$peer[$field]??$default;
            if (!is_int($port) || $port<1024 || $port>65535) throw new InvalidArgumentException('Cổng loa đích không hợp lệ');
            $peer[$field]=$port;
        }
        $seen[$peer['id']]=true;$hosts[$host]=true;
    }
    unset($peer);
    $settings['peers']=$peers;
    // The profile is the single source of the local speaker name.
    if ($profileName!==null) unset($settings['name']);
    return $settings;
}

function vbotLANEscape($value): string { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }

function vbotLANRingtones(): array {
    $names=[];
    foreach (glob(dirname(__DIR__,2).'/resource/sound/call_ringtone/*')?:[] as $file) {
        if (is_file($file) && preg_match('/\.(mp3|wav|ogg|flac|m4a)$/i',basename($file))) $names[]=basename($file);
    }
    natcasesort($names);return array_values($names);
}

function vbotLANValidateRingtoneFile(string $path, string $originalName): string {
    $size=@filesize($path);
    if ($size===false || $size<=0 || $size>20*1024*1024) throw new InvalidArgumentException('Nhạc chuông phải có dữ liệu và không vượt quá 20 MB');
    $name=basename(str_replace('\\','/',$originalName));
    if (strlen($name)>180 || preg_match('/[\x00-\x1f\x7f]/',$name) || !preg_match('/\.(mp3|wav|ogg|flac|m4a)$/i',$name)) throw new InvalidArgumentException('Chỉ nhận file MP3, WAV, OGG, FLAC hoặc M4A');
    if (!class_exists('finfo')) throw new RuntimeException('PHP cần bật extension fileinfo để kiểm tra âm thanh');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (strpos((string)$mime,'audio/')!==0 && !in_array($mime,['application/ogg','video/mp4'],true)) throw new InvalidArgumentException('Nội dung file không được nhận diện là âm thanh');
    return $name;
}

function vbotLANReadPeers(string $path, array $legacy=[]): array {
    if (!is_file($path)) return $legacy;
    $raw=(string)file_get_contents($path);
    $peers=json_decode($raw,true);
    if (substr(ltrim($raw),0,1)!=='[' || !is_array($peers) || array_values($peers)!==$peers || count($peers)>50) throw new RuntimeException('File danh sách loa LAN không hợp lệ');
    return $peers;
}

function vbotLANSavePeers(string $path, array $post, string $localId, &$saved): void {
    $defaults=['id'=>$localId!==''?$localId:'vbot_local','name'=>'Loa VBot','output_device'=>'default','mode'=>'full_duplex','control_port'=>'5010','audio_port'=>'5011','ring_timeout'=>'30','max_duration'=>'3600'];
    $peerPost=['lan_peers'=>$post['lan_peers']??'','lan_identity_present'=>'1'];
    foreach ($defaults as $field=>$value) $peerPost['lan_'.$field]=$value;
    $validated=vbotLANCallsSettings($peerPost,[]);
    $encoded=json_encode($validated['peers'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $dir=dirname($path);
    if (!is_dir($dir) && !@mkdir($dir,0777,true) && !is_dir($dir)) throw new RuntimeException('Không tạo được thư mục danh sách loa LAN');
    @chmod($dir,0777);
    if ($encoded===false || !vbotAtomicWriteFile($path,$encoded,'LAN peer devices')) throw new RuntimeException('Không lưu được danh sách loa LAN');
    @chmod($path,0777);
    $saved=$validated['peers'];
}

function vbotLANDiscoveredPeers(string $path, string $localId): array {
    $devices=is_readable($path)?json_decode((string)file_get_contents($path),true):[];
    if (!is_array($devices)) return [];
    $peers=[];$seen=[];
    foreach ($devices as $device) {
        if (!is_array($device) || ($device['device_type']??'')!=='vbot_server') continue;
        $id=$device['device_id']??'';$host=$device['ip_address']??'';
        if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id) || $id===$localId || isset($seen[$id])) continue;
        if (!is_string($host) || !filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || !preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/',$host)) continue;
        $name=is_string($device['user_name']??null)?trim($device['user_name']):'';
        $peer=['id'=>$id,'name'=>$name!==''?$name:$id,'host'=>$host];
        foreach (['control_port'=>5010,'audio_port'=>5011] as $field=>$default) {
            $port=$device['lan_calls'][$field]??$device['lan_'.$field]??$default;
            $peer[$field]=is_int($port) && $port>=1024 && $port<=65535?$port:$default;
        }
        $peers[]=$peer;$seen[$id]=true;
    }
    return $peers;
}
