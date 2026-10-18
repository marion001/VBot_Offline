<?php
// Use the same configured SSH account as other system actions in Config.php.
function vbotEQEnsureRuntime($configPath,$host,$port,$user,$password) {
    if (!function_exists('ssh2_connect')) throw new RuntimeException('PHP chưa có extension SSH2');
    $connection=@ssh2_connect($host,(int)$port);
    if (!$connection || !@ssh2_auth_password($connection,$user,$password)) throw new RuntimeException('Không kết nối được SSH của loa');
    $root=dirname(realpath($configPath)?:$configPath);
    $command='timeout 55s sudo -n /usr/bin/python3 '.escapeshellarg($root.'/resource/equalizer/activate.py').' --root '.escapeshellarg($root).' 2>&1';
    $stream=@ssh2_exec($connection,$command);
    if (!is_resource($stream)) throw new RuntimeException('Không chạy được bước chuẩn bị EQ');
    stream_set_blocking($stream,true);stream_set_timeout($stream,60);
    $output=stream_get_contents($stream);$meta=stream_get_meta_data($stream);fclose($stream);
    if (!empty($meta['timed_out']) || strpos((string)$output,'EQ_RUNTIME_READY')===false) {
        error_log('[EQ Runtime] '.(string)$output);
        throw new RuntimeException('Chưa chuẩn bị được EQ. Kiểm tra thư viện EQ, kết nối SSH và quyền sudo của loa; xem log hệ thống.');
    }
}
