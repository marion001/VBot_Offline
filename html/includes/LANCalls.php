<?php
require_once __DIR__.'/DeviceIdentity.php';
class VBotLANFieldError extends InvalidArgumentException {
    public $field;
    public function __construct(string $field,string $message) {
        $this->field=$field;
        parent::__construct($message);
    }
}
function vbotLANCallsSettingsSubmitted(array $post): bool {
    if (!isset($post['lan_settings_present'])) return false;
    if (isset($post['lan_enabled_control_present'])) return true;
    // Disabled controls are absent from browser POSTs. Older cached pages still
    // send their hidden marker; do not treat it as an editable LAN form.
    foreach (['lan_mode','lan_id','lan_output_device','lan_control_port'] as $field) {
        if (array_key_exists($field,$post)) return true;
    }
    return false;
}
function vbotLANCallsSettings(array $post, array $old, array $buttons=[], $profileName=null, bool $validateDisabled=false): array {
    $settings=$old;
    $settings['control_port']=5010;
    $settings['pjsip']=array_merge($old['pjsip']??[],['sip_port'=>5060]);
    $settings['active']=isset($post['lan_active']);
    if (isset($post['lan_browser_call_present'])) {
        $settings['browser_call']=array_merge(is_array($old['browser_call']??null)?$old['browser_call']:[],
            ['active'=>isset($post['lan_browser_call_active'])]);
    }
    // Disabled LAN calls do not block saving unrelated VBot configuration.
    if (!$settings['active'] && !$validateDisabled) return $settings;
    if (isset($post['lan_accept_controls_present'])) {
        $delay=(string)($post['lan_auto_accept_delay']??'3');
        if (!ctype_digit($delay) || (int)$delay>30) throw new InvalidArgumentException('Thời gian tự nhận phải từ 0 đến 30 giây');
        if ((int)$delay >= (int)($post['lan_ring_timeout']??30)) throw new InvalidArgumentException('Thời gian tự nhận phải nhỏ hơn thời gian chờ cuộc gọi');
        $settings['auto_accept_delay']=(int)$delay;
        $settings['accept_button_active']=isset($post['lan_accept_button_active']);
        $settings['accept_button']=(string)($post['lan_accept_button']??'wakeup');
        $settings['accept_button_press']=(string)($post['lan_accept_button_press']??'short');
        $settings['mic_button_mute']=isset($post['lan_mic_button_mute']);
        if (!in_array($settings['accept_button_press'],['short','hold','double'],true)) throw new InvalidArgumentException('Thao tác nhận cuộc gọi không hợp lệ');
        if ($settings['accept_button_active'] && !isset($buttons[$settings['accept_button']]) && $settings['accept_button']!=='encoder') throw new InvalidArgumentException('Nút nhận cuộc gọi không tồn tại');
        if ($settings['mic_button_mute'] && !empty($post['lan_ptt_button_active']) && ($post['lan_ptt_button']??'')==='mic') throw new InvalidArgumentException('Nút Mic đang dùng bật/tắt mic; hãy chọn nút khác để nhấn giữ nói');
        if ($settings['accept_button_active']) {
            $selected=$settings['accept_button'];
            if ($selected!=='encoder' && empty($buttons[$selected]['active'])) throw new InvalidArgumentException('Hãy bật nút nhận cuộc gọi trong cấu hình Button');
        }
    }
    $pjsip=$settings['pjsip'];
    foreach (['rtp_port'=>40000] as $field=>$default) {
        $raw=(string)($post['lan_pjsip_'.$field]??($pjsip[$field]??$default));
        if (!ctype_digit($raw) || (int)$raw<1024 || (int)$raw>($field==='rtp_port'?65526:65535)) throw new InvalidArgumentException('Cổng PJSIP không hợp lệ');
        $pjsip[$field]=(int)$raw;
    }
    $pjsip['runtime']=$pjsip['runtime']??'/home/pi/VBot_AEC3/pjsip';
    $settings['pjsip']=$pjsip;
    if (isset($post['lan_end_button_present'])) {
        $settings['end_button_active']=isset($post['lan_end_button_active']);
        $settings['end_button']=trim((string)($post['lan_end_button']??''));
        $settings['end_button_press']=(string)($post['lan_end_button_press']??'short');
        if (!in_array($settings['end_button_press'],['short','hold','both'],true)) throw new InvalidArgumentException('Kiểu nhấn nút ngắt cuộc gọi không hợp lệ');
        if (($settings['mic_button_mute']??true) && $settings['end_button']==='mic') $settings['end_button_press']='hold';
        if ($settings['active'] && $settings['end_button_active'] && (!isset($buttons[$settings['end_button']]) || empty($buttons[$settings['end_button']]['active']))) throw new InvalidArgumentException('Chọn nút đang bật để ngắt cuộc gọi');
    }
    if (isset($post['lan_ptt_present'])) {
        $settings['ptt_button_active']=isset($post['lan_ptt_button_active']);
        $settings['ptt_button']=trim((string)($post['lan_ptt_button']??''));
        if ($settings['active'] && $settings['ptt_button_active'] && (!isset($buttons[$settings['ptt_button']]) || empty($buttons[$settings['ptt_button']]['active']))) throw new InvalidArgumentException('Chọn một nút vật lý đang bật trong cấu hình Button để nhấn giữ nói');
    }
    foreach (['ringtone','outgoing_ringtone'] as $ringtoneField) {
        if (!isset($post['lan_'.$ringtoneField])) continue;
        if (!is_string($post['lan_'.$ringtoneField])) throw new InvalidArgumentException('Nhạc chuông không hợp lệ');
        $ringtone=$post['lan_'.$ringtoneField];
        if ($ringtone!=='' && !in_array($ringtone,vbotLANRingtones(),true)) throw new InvalidArgumentException('Nhạc chuông cuộc gọi không tồn tại trong resource/sound/call_ringtone');
        $settings[$ringtoneField]=$ringtone;
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
        if ($key==='') throw new VBotLANFieldError('lan_shared_key','Chưa có khóa chung. Nhập khóa rồi lưu để bật gọi điện LAN.');
        if (strlen($key)<16) throw new VBotLANFieldError('lan_shared_key','Khóa chung quá ngắn: cần ít nhất 16 ký tự.');
        if (strlen($key)>200) throw new VBotLANFieldError('lan_shared_key','Khóa chung quá dài: tối đa 200 ký tự.');
        if (!preg_match('/^[\x21-\x7e]+$/D',$key)) throw new VBotLANFieldError('lan_shared_key','Khóa chung chỉ dùng ký tự ASCII, không có khoảng trắng bên trong hoặc chữ có dấu.');
    }
    if (!in_array($settings['mode'],['full_duplex','push_to_talk'],true)) throw new InvalidArgumentException('Chế độ gọi không hợp lệ');
    if ($settings['active'] && $settings['mode']==='push_to_talk' && !empty($settings['ptt_button_active']) && !empty($settings['end_button_active']) && ($settings['ptt_button']??'')===($settings['end_button']??'')) throw new InvalidArgumentException('Nút nhấn giữ để nói và nút ngắt cuộc gọi phải khác nhau');
    if ($settings['output_device']==='' || strlen($settings['output_device'])>150 || preg_match('/[\x00-\x1f\x7f]/',$settings['output_device'])) throw new InvalidArgumentException('Thiết bị ALSA không hợp lệ');
    foreach (['audio_port','ring_timeout','max_duration'] as $key) {
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
        if (!is_array($peer) || !is_string($peer['id']??null) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$peer['id']) || $peer['id']===$settings['id'] || isset($seen[$peer['id']])) throw new InvalidArgumentException('ID loa đích trùng hoặc không hợp lệ');
        $host=$peer['host']??'';
        if (!filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || !preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/',$host) || isset($hosts[$host])) throw new InvalidArgumentException('Mỗi loa cần IP riêng thuộc mạng LAN');
        if (!is_string($peer['name']??null) || trim($peer['name'])==='' || !preg_match('/^.{1,100}$/usD',$peer['name'])) throw new InvalidArgumentException('Tên loa đích không hợp lệ');
        $peer['control_port']=5010;
        $peer['sip_port']=5060;
        if (array_key_exists('auto_accept',$peer) && !is_bool($peer['auto_accept'])) throw new InvalidArgumentException('Chế độ tự nhận cuộc gọi phải là true/false');
        foreach (['control_port'=>5010,'audio_port'=>5011] as $field=>$default) {
            $port=$peer[$field]??$default;
            if (!is_int($port) || $port<1024 || $port>65535) throw new InvalidArgumentException('Cổng loa đích không hợp lệ');
            $peer[$field]=$port;
        }
        if (array_key_exists('sip_port',$peer) && (!is_int($peer['sip_port']) || $peer['sip_port']<1024 || $peer['sip_port']>65535)) throw new InvalidArgumentException('Cổng SIP loa đích không hợp lệ');
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

function vbotLANPeersForEditor($raw): array {
    if (!is_string($raw) || strlen($raw)>40000) throw new InvalidArgumentException('JSON danh sách loa phải nhỏ hơn 40 KB');
    if (substr($raw,0,3)==="\xEF\xBB\xBF") $raw=substr($raw,3);
    $rows=json_decode($raw,true);
    if (json_last_error()!==JSON_ERROR_NONE || substr(ltrim($raw),0,1)!=='[' || !is_array($rows) || count($rows)>50)
        throw new InvalidArgumentException('Không đọc được mảng JSON; hãy xem JSON và khôi phục từ bản hợp lệ');
    return $rows;
}

function vbotLANPeerFieldErrors(array $rows,string $localId): array {
    $errors=[];$ids=[];$hosts=[];
    foreach ($rows as $i=>$row) {
        if (!is_array($row)) {$errors[$i]['id']='Dòng dữ liệu không phải đối tượng loa; hãy sửa hoặc xóa dòng này';continue;}
        $id=$row['id']??null;$host=$row['host']??null;$name=$row['name']??null;
        if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id)) $errors[$i]['id']='ID cần 1–64 ký tự: chữ, số, _ hoặc -';
        elseif ($id===$localId) $errors[$i]['id']='Đây là ID của chính loa hiện tại; hãy nhập ID loa đích';
        elseif (isset($ids[$id])) {$errors[$i]['id']='ID trùng với dòng khác';$errors[$ids[$id]]['id']='ID trùng với dòng khác';}
        else $ids[$id]=$i;
        if (!is_string($host) || !filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || !preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/',$host)) $errors[$i]['host']='Nhập IPv4 riêng trong mạng LAN';
        elseif (isset($hosts[$host])) {$errors[$i]['host']='IP trùng với dòng khác';$errors[$hosts[$host]]['host']='IP trùng với dòng khác';}
        else $hosts[$host]=$i;
        if (!is_string($name) || trim($name)==='' || !preg_match('/^.{1,100}$/usD',$name)) $errors[$i]['name']='Tên loa cần 1–100 ký tự';
        if (array_key_exists('auto_accept',$row) && !is_bool($row['auto_accept'])) $errors[$i]['auto_accept']='Hãy chọn lại cách nhận cuộc gọi';
    }
    return $errors;
}

function vbotLANValidatedPeers($raw, string $localId): array {
    if (!is_string($raw) || strlen($raw)>40000) throw new InvalidArgumentException('JSON danh sách loa phải nhỏ hơn 40 KB');
    if (substr($raw,0,3)==="\xEF\xBB\xBF") $raw=substr($raw,3);
    if (substr(ltrim($raw),0,1)!=='[') throw new InvalidArgumentException('JSON phải là một mảng danh sách loa');
    $defaults=['id'=>$localId!==''?$localId:'vbot_local','name'=>'Loa VBot','output_device'=>'default','mode'=>'full_duplex','control_port'=>'5010','audio_port'=>'5011','ring_timeout'=>'30','max_duration'=>'3600'];
    $peerPost=['lan_peers'=>$raw,'lan_identity_present'=>'1'];
    foreach ($defaults as $field=>$value) $peerPost['lan_'.$field]=$value;
    $validated=vbotLANCallsSettings($peerPost,[],[],null,true);
    return $validated['peers'];
}

function vbotLANPeersRaw(string $path,array $legacy=[]): string {
    if (is_link($path)) throw new RuntimeException('File danh sách loa không được là liên kết');
    if (!is_file($path)) return json_encode($legacy,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    $file=@fopen($path,'rb');
    if (!$file) throw new RuntimeException('Không đọc được danh sách loa');
    try {$raw=stream_get_contents($file,40001);} finally {fclose($file);}
    if (!is_string($raw) || strlen($raw)>40000) throw new LengthException('JSON vượt quá 40 KB');
    return $raw;
}

function vbotLANPeersLock(string $path,callable $operation) {
    $dir=dirname($path);
    if (is_link($dir) || is_link($path) || is_link($path.'.lock')) throw new RuntimeException('Đường dẫn dữ liệu LAN không được là liên kết');
    if (!is_dir($dir) && !@mkdir($dir,0777,true) && !is_dir($dir)) throw new RuntimeException('Không tạo được thư mục danh sách loa LAN');
    @chmod($dir,0777);
    $lock=@fopen($path.'.lock','c+');
    if (!$lock) throw new RuntimeException('Không mở được khóa dữ liệu LAN');
    @chmod($path.'.lock',0777);
    if (!flock($lock,LOCK_EX)) {fclose($lock);throw new RuntimeException('Không khóa được dữ liệu LAN');}
    try {return $operation();} finally {flock($lock,LOCK_UN);fclose($lock);}
}

function vbotLANPeersEncode(array $peers): string {
    $raw=json_encode($peers,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    if (strlen($raw)>40000) throw new LengthException('JSON sau khi lưu vượt quá 40 KB');
    return $raw;
}

function vbotLANSavePeers(string $path, array $post, string $localId, &$saved,array $legacy=[]): void {
    $peers=vbotLANValidatedPeers($post['lan_peers']??'',$localId);
    $encoded=vbotLANPeersEncode($peers);
    vbotLANPeersLock($path,function()use($path,$post,$encoded,$legacy){
        if (isset($post['revision']) && (!is_string($post['revision']) || !hash_equals(hash('sha256',vbotLANPeersRaw($path,$legacy)),$post['revision'])))
            throw new RuntimeException('Danh sách loa đã thay đổi. Tải lại trang trước khi lưu.',409);
        if (!vbotAtomicWriteFile($path,$encoded,'LAN peer devices',true)) throw new RuntimeException('Không lưu được danh sách loa LAN');
        @chmod($path,0777);
    });
    $saved=$peers;
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
        $peer['sip_port']=5060;
        foreach (['control_port'=>5010,'audio_port'=>5011] as $field=>$default) {
            $port=$device['lan_calls'][$field]??$device['lan_'.$field]??$default;
            if ($field==='control_port') $port=5010;
            $peer[$field]=is_int($port) && $port>=1024 && $port<=65535?$port:$default;
        }
        $peers[]=$peer;$seen[$id]=true;
    }
    return $peers;
}
