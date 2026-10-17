<?php
require_once __DIR__.'/LANCalls.php';

function vbotLANBackupDirectory(string $directory): void {
    if (is_link($directory)) throw new RuntimeException('Thư mục sao lưu không được là liên kết');
    if (!is_dir($directory) && !@mkdir($directory,0777,true) && !is_dir($directory)) throw new RuntimeException('Không tạo được thư mục sao lưu');
    @chmod($directory,0777);
}

function vbotLANBackupPath(string $directory,$name) {
    if (!is_string($name) || !preg_match('/^lan_calls_[0-9]{8}_[0-9]{6}_[a-f0-9]{12}\.json$/D',$name)) return false;
    $candidate=$directory.'/'.$name;$root=realpath($directory);$file=realpath($candidate);
    if ($root===false || $file===false || dirname($file)!==$root || !is_file($file) || is_link($candidate) || is_link($directory)) return false;
    return $file;
}

function vbotLANBackupList(string $directory): array {
    $rows=[];
    foreach (glob($directory.'/lan_calls_*.json')?:[] as $file) {
        $name=basename($file);
        if (vbotLANBackupPath($directory,$name)!==false) $rows[]=['name'=>$name,'created_at'=>date('d/m/Y H:i:s',filemtime($file)),'size'=>filesize($file)];
    }
    usort($rows,function($a,$b){return strcmp($b['name'],$a['name']);});
    return $rows;
}

function vbotLANBackupCreate(string $directory,string $raw): string {
    vbotLANBackupDirectory($directory);
    $name='lan_calls_'.date('Ymd_His').'_'.bin2hex(random_bytes(6)).'.json';
    if (!vbotAtomicWriteFile($directory.'/'.$name,$raw,'LAN backup',true)) throw new RuntimeException('Không ghi được bản sao lưu');
    @chmod($directory.'/'.$name,0777);
    $others=array_values(array_filter(vbotLANBackupList($directory),function($item)use($name){return $item['name']!==$name;}));
    foreach (array_slice($others,9) as $item) {
        $file=vbotLANBackupPath($directory,$item['name']);if ($file!==false) @unlink($file);
    }
    return $name;
}

function vbotLANBackupOperation(string $path,string $directory,string $localId,array $request,array $legacy=[]): array {
    return vbotLANPeersLock($path,function()use($path,$directory,$localId,$request,$legacy){
        $raw=vbotLANPeersRaw($path,$legacy);$revision=hash('sha256',$raw);$action=$request['action']??'';
        if ($action==='list') {
            try {vbotLANValidatedPeers($raw,$localId);$valid=true;} catch (Throwable $error) {$valid=false;}
            return ['revision'=>$revision,'current_valid'=>$valid,'backups'=>vbotLANBackupList($directory)];
        }
        if ($action==='read') {
            $name=$request['name']??'';
            if ($name==='') return ['revision'=>$revision,'raw'=>$raw,'filename'=>'Devices.json'];
            $file=vbotLANBackupPath($directory,$name);
            if ($file===false) throw new InvalidArgumentException('Bản sao lưu không tồn tại');
            return ['revision'=>$revision,'raw'=>vbotLANPeersRaw($file),'filename'=>$name];
        }
        if (!in_array($action,['create','import','restore'],true)) throw new InvalidArgumentException('Thao tác không hợp lệ');
        if (!is_string($request['revision']??null) || !hash_equals($revision,$request['revision']))
            throw new RuntimeException('Danh sách loa đã thay đổi. Tải lại danh sách sao lưu hoặc trang trước khi tiếp tục.',409);
        if ($action==='create') {
            $name=vbotLANBackupCreate($directory,$raw);
            return ['revision'=>$revision,'backups'=>vbotLANBackupList($directory),'message'=>'Đã tạo bản sao lưu: '.$name];
        }
        if ($action==='restore') {
            $file=vbotLANBackupPath($directory,$request['name']??null);
            if ($file===false) throw new InvalidArgumentException('Bản sao lưu không tồn tại');
            $incoming=vbotLANPeersRaw($file);$mode='replace';
        } else {$incoming=$request['data']??null;$mode=$request['mode']??'merge';}
        $peers=vbotLANValidatedPeers($incoming,$localId);
        if ($mode==='merge') {
            $byId=[];
            foreach (vbotLANValidatedPeers($raw,$localId) as $peer) $byId[$peer['id']]=$peer;
            foreach ($peers as $peer) $byId[$peer['id']]=$peer;
            $peers=vbotLANValidatedPeers(json_encode(array_values($byId),JSON_UNESCAPED_UNICODE),$localId);
        } elseif ($mode!=='replace') throw new InvalidArgumentException('Chế độ nhập không hợp lệ');
        $encoded=vbotLANPeersEncode($peers);
        $before=vbotLANBackupCreate($directory,$raw);
        if (!vbotAtomicWriteFile($path,$encoded,'LAN restore',true)) throw new RuntimeException('Không ghi được dữ liệu; bản trước thay đổi: '.$before);
        @chmod($path,0777);
        return ['data'=>$peers,'revision'=>hash('sha256',$encoded),'before_backup'=>$before,'backups'=>vbotLANBackupList($directory),
            'message'=>'Đã nhập/khôi phục danh sách loa. Khởi động lại VBot để áp dụng.'];
    });
}
