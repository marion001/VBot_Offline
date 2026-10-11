<?php
require_once __DIR__.'/VoiceRoutines.php';

function vbotRoutineBackupDirectory($directory): void {
    if (is_link($directory)) throw new RuntimeException('Thư mục sao lưu không được là liên kết');
    if (!is_dir($directory) && !@mkdir($directory,0777,true) && !is_dir($directory))
        throw new RuntimeException('Không tạo được thư mục sao lưu');
    if (!vbotRoutineDirectoryPermissions($directory)) throw new RuntimeException('Thư mục sao lưu không có quyền đọc/ghi: '.$directory);
}

function vbotRoutineBackupMigrate($source,$directory): void {
    vbotRoutineBackupDirectory($directory);
    if (!is_dir($source) || is_link($source)) return;
    foreach (vbotRoutineBackupList($source) as $item) {
        $file=vbotRoutineBackupPath($source,$item['name']);
        if ($file===false) continue;
        $target=$directory.DIRECTORY_SEPARATOR.$item['name'];
        if (file_exists($target) || is_link($target)) {
            if (is_link($target) || !is_file($target) || hash_file('sha256',$file)!==hash_file('sha256',$target))
                throw new RuntimeException('Tên bản sao lưu trùng với dữ liệu khác: '.$item['name']);
            if (!@unlink($file)) throw new RuntimeException('Không dọn được bản sao lưu cũ');
        } elseif (!@rename($file,$target)) {
            throw new RuntimeException('Không chuyển được bản sao lưu sang thư mục mới');
        }
    }
    // Remove only an empty legacy directory; keep unexpected files untouched.
    @rmdir($source);
}

function vbotRoutineBackupRaw($path): string {
    if (!is_file($path)) return '{"version":1,"routines":[]}';
    $handle=@fopen($path,'rb');
    if (!$handle) throw new RuntimeException('Không đọc được file kịch bản');
    try { $raw=stream_get_contents($handle,1048577); } finally { fclose($handle); }
    if (!is_string($raw) || strlen($raw)>1048576) throw new LengthException('File vượt quá 1 MB');
    return $raw;
}
function vbotRoutineBackupDecode($raw): array {
    if (!is_string($raw) || strlen($raw)>1048576) throw new LengthException('Dữ liệu vượt quá 1 MB');
    if (substr($raw,0,3)==="\xEF\xBB\xBF") $raw=substr($raw,3);
    return vbotRoutinesValidate(json_decode($raw,true));
}
function vbotRoutineBackupPath($directory,$name) {
    if (!is_string($name) || !preg_match('/^voice_routines_[0-9]{8}_[0-9]{6}_[a-f0-9]{12}\.json$/D',$name)) return false;
    $root=realpath($directory); $file=realpath($directory.DIRECTORY_SEPARATOR.$name);
    if ($root===false || $file===false || dirname($file)!==$root || !is_file($file) || is_link($directory.DIRECTORY_SEPARATOR.$name)) return false;
    return $file;
}
function vbotRoutineBackupList($directory): array {
    $rows=[];
    foreach (glob($directory.'/voice_routines_*.json')?:[] as $file) {
        $name=basename($file);
        if (vbotRoutineBackupPath($directory,$name)===false) continue;
        $rows[]=['name'=>$name,'created_at'=>date('d/m/Y H:i:s',filemtime($file)), 'size'=>filesize($file)];
    }
    usort($rows,function($a,$b){return strcmp($b['name'],$a['name']);});
    return $rows;
}
function vbotRoutineBackupCreate($directory,$raw): string {
    vbotRoutineBackupDirectory($directory);
    $name='voice_routines_'.date('Ymd_His').'_'.bin2hex(random_bytes(6)).'.json';
    if (!vbotRoutineWrite($directory.'/'.$name,$raw,0777)) throw new RuntimeException('Không tạo được bản sao lưu hoặc cấp quyền 0777');
    // Keep the new snapshot even when multiple archives share one timestamp.
    $others=array_values(array_filter(vbotRoutineBackupList($directory),function($item)use($name){return $item['name']!==$name;}));
    foreach(array_slice($others,9) as $item) {
        $file=vbotRoutineBackupPath($directory,$item['name']);
        if ($file!==false) @unlink($file);
    }
    return $name;
}
function vbotRoutineBackupReplace($path,$directory,$raw,$incoming,$mode): array {
    $data=vbotRoutineBackupDecode($incoming);
    if ($mode==='merge') {
        $current=vbotRoutineBackupDecode($raw);
        $byId=[];
        foreach($current['routines'] as $item) $byId[$item['id']]=$item;
        foreach($data['routines'] as $item) $byId[$item['id']]=$item;
        $data=vbotRoutinesValidate(['version'=>1,'routines'=>array_values($byId)]);
    } elseif ($mode!=='replace') throw new InvalidArgumentException('Chế độ nhập không hợp lệ');
    $encoded=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    if (strlen($encoded)>1048576) throw new LengthException('Dữ liệu sau khi nhập vượt quá 1 MB');
    $before=vbotRoutineBackupCreate($directory,$raw);
    if (is_file($path) && !vbotRoutineWrite($path.'.bak',$raw)) throw new RuntimeException('Không lưu được bản dự phòng; chưa thay thế dữ liệu');
    if (!vbotRoutineWrite($path,$encoded)) throw new RuntimeException('Không ghi được dữ liệu; bản trước thay đổi: '.$before);
    return ['data'=>$data,'revision'=>hash('sha256',$encoded),'before_backup'=>$before];
}
