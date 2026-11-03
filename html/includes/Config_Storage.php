<?php
// Read/merge/recover while holding the same .lock used by Python writers.
function vbotConfigReadObject($path, &$content)
{
    $content = @file_get_contents($path);
    if (!is_string($content) || substr(ltrim($content), 0, 1) !== '{') return null;
    $decoded = json_decode($content, true);
    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) && $decoded !== [] ? $decoded : null;
}

function vbotConfigLoadRecover($path, $backupDirectory, &$status, $readOnly=false)
{
    $current = vbotConfigReadObject($path, $content);
    if ($current !== null) return $current;
    if ($readOnly) {
        $backups=glob(rtrim($backupDirectory,'/\\').'/Config_*.json')?:[];
        usort($backups,static function($a,$b){return @filemtime($b)<=>@filemtime($a);});
        array_unshift($backups,$path.'.before-lan-save.json',$path.'.bak');
        foreach ($backups as $backup) {
            if (!is_file($backup) || is_link($backup)) continue;
            $candidate=vbotConfigReadObject($backup,$content);
            if ($candidate!==null) return $candidate;
        }
        $status['error']='Không đọc được cấu hình; trang này không được phép khôi phục Config.json.';
        return null;
    }
    $lock = @fopen($path.'.lock', 'c+');
    if (is_resource($lock)) @chmod($path.'.lock',0777);
    if ($lock === false || !@flock($lock, LOCK_EX)) {
        if (is_resource($lock)) fclose($lock);
        $status['error'] = 'Không thể khóa Config.json để khôi phục.';
        return null;
    }
    try {
        // A runtime writer may have repaired the primary before we acquired the lock.
        $current = vbotConfigReadObject($path, $content);
        if ($current !== null) return $current;
        $backups = glob(rtrim($backupDirectory, '/\\').'/Config_*.json') ?: [];
        usort($backups, static function ($a, $b) { return @filemtime($b) <=> @filemtime($a); });
        array_unshift($backups, $path.'.bak');
        array_unshift($backups, $path.'.before-lan-save.json');
        foreach ($backups as $backup) {
            if (!is_file($backup) || is_link($backup)) continue;
            $candidate = vbotConfigReadObject($backup, $content);
            if ($candidate === null) continue;
            if (!vbotAtomicWriteFile($path, $content, 'Config.json recovery', true)) {
                $status['error'] = 'Không thể ghi nguyên tử Config.json khi khôi phục.';
                return null;
            }
            @chmod($path, 0777);
            $status['recovered'] = true;
            $status['backup_file'] = basename($backup);
            return $candidate;
        }
        $status['error'] = 'Không tìm thấy bản sao lưu Config.json có JSON dạng object hợp lệ.';
        return null;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function vbotConfigIsMap(array $value)
{
    return $value !== [] && array_keys($value) !== range(0, count($value) - 1);
}

function vbotConfigMergeChanges($baseline, $proposed, $current)
{
    if ($proposed === $baseline) return $current;
    if (!is_array($baseline) || !is_array($proposed) || !is_array($current)
        || !vbotConfigIsMap($baseline) || !vbotConfigIsMap($proposed) || !vbotConfigIsMap($current)) {
        return $proposed;
    }
    foreach ($baseline as $key => $_value) {
        if (!array_key_exists($key, $proposed)) unset($current[$key]);
    }
    foreach ($proposed as $key => $value) {
        if (array_key_exists($key, $baseline) && $value === $baseline[$key]) continue;
        $current[$key] = array_key_exists($key, $baseline) && array_key_exists($key, $current)
            ? vbotConfigMergeChanges($baseline[$key], $value, $current[$key]) : $value;
    }
    return $current;
}

function vbotConfigWriteChanges($path, array $baseline, array $proposed, &$saved)
{
    if ($baseline === [] || $proposed === []) {
        error_log('[PHP Config ERROR] Refusing to save empty Config.json');
        return false;
    }
    $lockPath = $path.'.lock';
    if (function_exists('vbotConfigPrepareLockFile') && !vbotConfigPrepareLockFile($lockPath, 'Config.json')) {
        error_log('[PHP Config ERROR] Lock Config.json không ghi được dù đã thử chuẩn bị quyền; path='.$lockPath
            .' | kiểm tra owner/quyền file bằng: ls -l '.escapeshellarg($lockPath));
        return false;
    }
    $lock = @fopen($lockPath, 'c+');
    if (is_resource($lock)) @chmod($path.'.lock',0777);
    if ($lock === false || !@flock($lock, LOCK_EX)) {
        $last = error_get_last();
        error_log('[PHP Config ERROR] Không khóa được Config.json để lưu: '.($last['message'] ?? $lockPath)
            .' | php_user='.get_current_user().' | effective_uid='.(function_exists('posix_geteuid') ? posix_geteuid() : 'không khả dụng')
            .' | path='.$lockPath);
        if (is_resource($lock)) fclose($lock);
        return false;
    }
    try {
        $current = vbotConfigReadObject($path, $content);
        if ($current === null) {
            error_log('[PHP Config ERROR] Không thể lưu Config.json: file hiện tại không đọc được hoặc JSON không hợp lệ: '.$path);
            return false;
        }
        $merged = vbotConfigMergeChanges($baseline, $proposed, $current);
        // A settings form must never remove an existing root section.
        foreach (['web_interface','smart_config','api','media_player','contact_info','voice_command_system'] as $section) {
            if (isset($current[$section]) && is_array($current[$section]) && (!isset($merged[$section]) || !is_array($merged[$section]))) {
                error_log('[PHP Config ERROR] Không thể lưu Config.json vì dữ liệu đề xuất làm mất section bắt buộc: '.$section);
                return false;
            }
        }
        $encoded = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            error_log('[PHP Config ERROR] Không thể mã hóa Config.json sau khi gộp thay đổi: '.json_last_error_msg());
            return false;
        }
        if (!vbotAtomicWriteFile($path, $encoded, 'Config.json merged', true)) {
            $last = error_get_last();
            error_log('[PHP Config ERROR] Ghi nguyên tử Config.json thất bại: '.($last['message'] ?? $path));
            return false;
        }
        $saved = $merged;
        return true;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
