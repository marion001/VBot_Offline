<?php
#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Email: VBot.Assistant@gmail.com

require_once __DIR__ . '/Api_Helpers.php';
@setlocale(LC_ALL, 'C.UTF-8', 'en_US.UTF-8', 'vi_VN.UTF-8');
vbotApiInitialize(['GET', 'POST']);
include '../../Configuration.php';

if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (
        !isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
    ) {
        session_unset();
        session_destroy();
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Thao tác bị chặn, vui lòng đăng nhập WebUI VBot.'
        ], 401);
    }
}

$mb = 1024 * 1024;
$fileSizeLimits = [
    // Văn bản, dữ liệu cấu trúc, web & mã nguồn (5MB)
    'txt'  => 5 * $mb,
    'md'   => 5 * $mb,
    'rtf'  => 5 * $mb,
    'json' => 5 * $mb,
    'xml'  => 5 * $mb,
    'yaml' => 5 * $mb,
    'yml'  => 5 * $mb,
    'csv'  => 5 * $mb,
    'html' => 5 * $mb,
    'htm'  => 5 * $mb,
    'py'   => 5 * $mb,
    'js'   => 5 * $mb,
    'c'    => 5 * $mb,
    'cpp'  => 5 * $mb,
    'h'    => 5 * $mb,
    // Tài liệu Office & bảng tính (10MB)
    'docx' => 10 * $mb,
    'odt'  => 10 * $mb,
    'xls'  => 10 * $mb,
    'xlsx' => 10 * $mb,
    // Trình chiếu (15MB)
    'ppt'  => 15 * $mb,
    'pptx' => 15 * $mb,
    // Sách điện tử & PDF (20MB)
    'pdf'  => 20 * $mb,
    'epub' => 20 * $mb,
    // Hình ảnh OCR / Gemini Vision (10MB)
    'jpg'  => 10 * $mb,
    'jpeg' => 10 * $mb,
    'png'  => 10 * $mb,
    'webp' => 10 * $mb,
];
$allowedExtensions = array_keys($fileSizeLimits);
$editableExtensions = ['txt', 'md', 'json', 'xml', 'yaml', 'yml', 'csv', 'html', 'htm', 'py', 'js', 'c', 'cpp', 'h'];

$aiEngineDir = rtrim((string)$VBot_Offline, '/\\') . '/resource/ai_engine';
$knowledgeDir = $aiEngineDir . '/knowledge';
$catalogJsonPath = $aiEngineDir . '/knowledge_catalog.json';
$htmlRootDir = realpath(__DIR__ . '/../../') ?: dirname(dirname(__DIR__));
$backupDir = rtrim($htmlRootDir, '/\\') . '/Backup_Upgrade/Backup_AI_Knowledge';
$maxBackupFiles = 5;

if (!is_dir($aiEngineDir)) {
    @mkdir($aiEngineDir, 0777, true);
    @chmod($aiEngineDir, 0777);
}
if (!is_dir($knowledgeDir)) {
    @mkdir($knowledgeDir, 0777, true);
    @chmod($knowledgeDir, 0777);
}
$backupParentDir = rtrim($htmlRootDir, '/\\') . '/Backup_Upgrade';
if (!is_dir($backupParentDir)) {
    @mkdir($backupParentDir, 0777, true);
}
@chmod($backupParentDir, 0777);
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0777, true);
}
@chmod($backupDir, 0777);
$realBackupDir = realpath($backupDir) ?: $backupDir;
$realKnowledgeDir = realpath($knowledgeDir);
if ($realKnowledgeDir === false || !is_dir($realKnowledgeDir)) {
    vbotApiJsonResponse([
        'success' => false,
        'message' => 'Không thể truy cập hoặc tạo thư mục kho tri thức: ' . $knowledgeDir
    ], 500);
}

function vbotKbSanitizeFilename($rawName, array $allowedExtensions, $defaultExt = 'txt')
{
    $name = trim((string)$rawName);
    $name = str_replace(["\0", '/', '\\'], '', $name);
    $name = preg_replace('/\.\.+/', '.', $name);
    $name = ltrim($name, '. ');
    $name = preg_replace('/[^\p{L}\p{N}._\- ]+/u', '_', $name);
    $name = preg_replace('/\s+/', '_', trim($name));
    if ($name === '' || $name === '.' || $name === '..') {
        return null;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext === '') {
        $name .= '.' . $defaultExt;
        $ext = $defaultExt;
    }
    if (!in_array($ext, $allowedExtensions, true)) {
        return null;
    }
    if (mb_strlen($name, 'UTF-8') > 140) {
        $base = mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 110, 'UTF-8');
        $name = $base . '.' . $ext;
    }
    return $name;
}

function vbotKbFormatBytes($bytes)
{
    $bytes = (int)$bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / (1024 * 1024), 2) . ' MB';
}

function vbotKbDefaultTitle($filename)
{
    $stem = pathinfo((string)$filename, PATHINFO_FILENAME);
    $stem = preg_replace('/[_\-]+/', ' ', $stem);
    $stem = trim(preg_replace('/\s+/', ' ', (string)$stem));
    return $stem !== '' ? $stem : (string)$filename;
}

function vbotKbReadCatalog($catalogPath)
{
    if (!is_file($catalogPath)) {
        return ['version' => 2, 'updated_at' => 0, 'documents' => []];
    }
    $raw = @file_get_contents($catalogPath);
    if ($raw === false || trim($raw) === '') {
        return ['version' => 2, 'updated_at' => 0, 'documents' => []];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['version' => 2, 'updated_at' => 0, 'documents' => []];
    }
    if (!isset($decoded['documents']) || !is_array($decoded['documents'])) {
        $decoded['documents'] = [];
    }
    return $decoded;
}

function vbotKbWriteCatalog($catalogPath, array $data)
{
    $data['version'] = 2;
    $data['updated_at'] = time();
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }
    $ok = @file_put_contents($catalogPath, $json, LOCK_EX) !== false;
    if ($ok) {
        @chmod($catalogPath, 0777);
    }
    return $ok;
}

function vbotKbParseTags($rawTags)
{
    if (is_array($rawTags)) {
        $parts = $rawTags;
    } else {
        $parts = preg_split('/[,;\n]+/', (string)$rawTags);
    }
    $clean = [];
    $seen = [];
    if (is_array($parts)) {
        foreach ($parts as $p) {
            $t = trim((string)$p);
            if ($t === '') {
                continue;
            }
            if (mb_strlen($t, 'UTF-8') > 40) {
                $t = mb_substr($t, 0, 40, 'UTF-8');
            }
            $k = mb_strtolower($t, 'UTF-8');
            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $clean[] = $t;
            }
            if (count($clean) >= 12) {
                break;
            }
        }
    }
    return $clean;
}

function vbotKbRunPythonIndexer($vbotRoot, $mode = 'reindex', $targetArg = '', array $extra = [])
{
    if (!function_exists('proc_open')) {
        return ['ok' => false, 'reason' => 'proc_open bị tắt trên PHP'];
    }
    $rootReal = realpath((string)$vbotRoot);
    if ($rootReal === false || !is_dir($rootReal)) {
        return ['ok' => false, 'reason' => 'Không xác định được thư mục gốc VBot'];
    }
    $indexerScript = __DIR__ . DIRECTORY_SEPARATOR . 'AI_Knowledge_Indexer.py';
    if (!is_file($indexerScript)) {
        return ['ok' => false, 'reason' => 'Không tìm thấy file trung gian AI_Knowledge_Indexer.py'];
    }
    @chmod($indexerScript, 0777);

    $candidates = [];
    if (DIRECTORY_SEPARATOR === '\\') {
        $candidates = ['python', 'python3'];
    } else {
        $candidates = ['/home/pi/VBot_Offline/venv/bin/python', '/usr/bin/python3', 'python3', 'python'];
    }

    // Mã hóa tham số dạng JSON + Base64 thuần ASCII để tránh PHP escapeshellarg() làm mất dấu tiếng Việt khi locale=C
    $payloadArr = array_merge($extra, [
        'mode' => (string)$mode,
        'root' => $rootReal,
        'target' => (string)$targetArg,
    ]);
    $payloadJson = json_encode($payloadArr, JSON_UNESCAPED_UNICODE);
    $b64Arg = 'b64:' . base64_encode($payloadJson !== false ? $payloadJson : '{}');

    $lastErr = 'Không tìm thấy trình thông dịch Python';
    foreach ($candidates as $bin) {
        if (strpos($bin, '/') === 0 && !is_executable($bin)) {
            continue;
        }
        $cmd = escapeshellcmd($bin)
            . ' ' . escapeshellarg($indexerScript)
            . ' ' . escapeshellarg($b64Arg);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes, $rootReal);
        if (!is_resource($proc)) {
            continue;
        }
        @fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + 45.0;
        $realExitCode = null;
        while (true) {
            $status = proc_get_status($proc);
            $stdout .= (string)@stream_get_contents($pipes[1]);
            $stderr .= (string)@stream_get_contents($pipes[2]);
            if (!$status['running']) {
                // PHP chỉ trả exit code thật ở lần proc_get_status() đầu tiên sau khi tiến trình kết thúc;
                // proc_close() gọi sau đó sẽ luôn trả -1.
                $realExitCode = (int)$status['exitcode'];
                break;
            }
            if (microtime(true) > $deadline) {
                @proc_terminate($proc);
                @fclose($pipes[1]);
                @fclose($pipes[2]);
                @proc_close($proc);
                return ['ok' => false, 'reason' => 'Quá thời gian chờ lập chỉ mục (45 giây). VBot sẽ tiếp tục tự động lập chỉ mục khi bạn hỏi.'];
            }
            usleep(100000);
        }
        $stdout .= (string)@stream_get_contents($pipes[1]);
        $stderr .= (string)@stream_get_contents($pipes[2]);
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        $closeCode = proc_close($proc);
        $exitCode = ($realExitCode !== null && $realExitCode !== -1) ? $realExitCode : $closeCode;

        // Ưu tiên kết quả JSON hợp lệ trên stdout (không phụ thuộc exit code do PHP trả về)
        $lines = array_values(array_filter(array_map('trim', explode("\n", trim($stdout)))));
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $parsed = json_decode($lines[$i], true);
            if (is_array($parsed)) {
                return ['ok' => true, 'data' => $parsed];
            }
        }
        $lastErr = trim($stderr) !== ''
            ? substr(trim($stderr), -400)
            : ('Exit code ' . $exitCode . (trim($stdout) !== '' ? ' | stdout: ' . substr(trim($stdout), 0, 200) : ''));
    }
    return ['ok' => false, 'reason' => $lastErr];
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? 'list'));

if ($method === 'GET' && $action === 'list') {
    $catalog = vbotKbReadCatalog($catalogJsonPath);
    $catDocs = isset($catalog['documents']) && is_array($catalog['documents']) ? $catalog['documents'] : [];

    $items = [];
    $categoriesSet = [];
    $totalChunks = 0;
    $totalVectors = 0;
    $entries = @scandir($realKnowledgeDir);
    if (is_array($entries)) {
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry[0] === '.') {
                continue;
            }
            $full = $realKnowledgeDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($full)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }
            $size = (int)@filesize($full);
            $mtime = (int)@filemtime($full);
            $meta = isset($catDocs[$entry]) && is_array($catDocs[$entry]) ? $catDocs[$entry] : [];

            $category = trim((string)($meta['category'] ?? ''));
            if ($category === '') {
                $category = 'Tài liệu';
            }
            $categoriesSet[$category] = ($categoriesSet[$category] ?? 0) + 1;

            $chunkCount = isset($meta['chunk_count']) ? (int)$meta['chunk_count'] : -1;
            $vectorCount = isset($meta['vector_count']) ? (int)$meta['vector_count'] : 0;
            $status = trim((string)($meta['status'] ?? ''));
            if ($status === '') {
                $status = ($chunkCount > 0) ? 'indexed' : (($chunkCount === 0) ? 'empty' : 'pending');
            }
            if ($chunkCount > 0) {
                $totalChunks += $chunkCount;
            }
            if ($vectorCount > 0) {
                $totalVectors += $vectorCount;
            }
            $tags = isset($meta['tags']) && is_array($meta['tags']) ? $meta['tags'] : [];

            $items[] = [
                'filename' => $entry,
                'document_id' => (string)($meta['document_id'] ?? ''),
                'title' => trim((string)($meta['title'] ?? '')) ?: vbotKbDefaultTitle($entry),
                'category' => $category,
                'tags' => $tags,
                'summary' => (string)($meta['summary'] ?? ''),
                'source_url' => (string)($meta['source_url'] ?? ''),
                'hit_count' => (int)($meta['hit_count'] ?? 0),
                'duplicate_of' => (string)($meta['duplicate_of'] ?? ''),
                'ext' => $ext,
                'size' => $size,
                'size_human' => vbotKbFormatBytes($size),
                'updated_at' => $mtime > 0 ? date('d/m/Y H:i', $mtime) : '',
                'editable' => in_array($ext, $editableExtensions, true),
                'chunk_count' => $chunkCount,
                'vector_count' => $vectorCount,
                'status' => $status,
            ];
        }
    }
    usort($items, function ($a, $b) {
        return strcasecmp($a['filename'], $b['filename']);
    });
    $totalSize = 0;
    foreach ($items as $it) {
        $totalSize += (int)$it['size'];
    }
    ksort($categoriesSet);
    vbotApiJsonResponse([
        'success' => true,
        'directory' => $realKnowledgeDir,
        'count' => count($items),
        'total_size' => $totalSize,
        'total_size_human' => vbotKbFormatBytes($totalSize),
        'total_chunks' => $totalChunks,
        'total_vectors' => $totalVectors,
        'categories' => array_keys($categoriesSet),
        'files' => $items,
    ]);
}

if ($method === 'GET' && $action === 'download') {
    $safeName = vbotKbSanitizeFilename($_GET['file'] ?? '', $allowedExtensions, 'txt');
    if ($safeName === null) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp không hợp lệ.'], 400);
    }
    $target = $realKnowledgeDir . DIRECTORY_SEPARATOR . $safeName;
    if (!is_file($target) || dirname(realpath($target)) !== $realKnowledgeDir) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy tệp tri thức.'], 404);
    }
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . rawurlencode($safeName) . '"; filename*=UTF-8\'\'' . rawurlencode($safeName));
    header('Content-Length: ' . filesize($target));
    readfile($target);
    exit;
}

if ($method === 'GET' && $action === 'read') {
    $safeName = vbotKbSanitizeFilename($_GET['file'] ?? '', $editableExtensions, 'txt');
    if ($safeName === null) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp không hợp lệ hoặc định dạng không hỗ trợ chỉnh sửa trực tiếp.'], 400);
    }
    $target = $realKnowledgeDir . DIRECTORY_SEPARATOR . $safeName;
    if (!is_file($target) || dirname(realpath($target)) !== $realKnowledgeDir) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy tệp tri thức.'], 404);
    }
    $content = @file_get_contents($target);
    if ($content === false) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không đọc được nội dung tệp.'], 500);
    }
    vbotApiJsonResponse([
        'success' => true,
        'filename' => $safeName,
        'content' => $content,
    ]);
}

if ($method === 'GET' && $action === 'inspect') {
    $rawFile = trim((string)($_GET['file'] ?? ''));
    $safeName = '';
    if ($rawFile !== '') {
        $checked = vbotKbSanitizeFilename($rawFile, $allowedExtensions, 'txt');
        if ($checked === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp không hợp lệ.'], 400);
        }
        $safeName = $checked;
    }
    $res = vbotKbRunPythonIndexer($VBot_Offline, 'inspect', $safeName);
    if (!$res['ok']) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể truy vấn dữ liệu chỉ mục & vector (' . $res['reason'] . ').',
        ], 200);
    }
    vbotApiJsonResponse([
        'success' => true,
        'inspect' => $res['data']['inspect'] ?? [],
    ]);
}

if ($method === 'GET' && $action === 'test_search') {
    $q = trim((string)($_GET['q'] ?? ''));
    $cat = trim((string)($_GET['category'] ?? ''));
    if ($q === '') {
        vbotApiJsonResponse(['success' => false, 'message' => 'Vui lòng nhập câu hỏi hoặc từ khóa cần kiểm thử.'], 400);
    }
    $res = vbotKbRunPythonIndexer($VBot_Offline, 'test_search', $q, ['category' => $cat]);
    if (!$res['ok']) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể chạy kiểm thử tìm kiếm (' . $res['reason'] . ').',
        ], 200);
    }
    vbotApiJsonResponse([
        'success' => true,
        'query' => $q,
        'hits' => $res['data']['hits'] ?? [],
    ]);
}

function vbotKbSanitizeBackupName($rawName)
{
    $name = basename(trim((string)$rawName));
    if ($name === '' || strpos($name, '..') !== false) {
        return null;
    }
    if (!preg_match('/^[A-Za-z0-9._\-]+\.(?:tar\.gz|tgz)$/i', $name)) {
        return null;
    }
    return $name;
}

if ($method === 'GET' && $action === 'list_backups') {
    $backups = [];
    if (is_dir($realBackupDir)) {
        $entries = @scandir($realBackupDir);
        if (is_array($entries)) {
            foreach ($entries as $entry) {
                $safe = vbotKbSanitizeBackupName($entry);
                if ($safe === null) {
                    continue;
                }
                $full = $realBackupDir . DIRECTORY_SEPARATOR . $safe;
                if (!is_file($full)) {
                    continue;
                }
                $sz = (int)@filesize($full);
                $mt = (int)@filemtime($full);
                $backups[] = [
                    'filename' => $safe,
                    'size' => $sz,
                    'size_human' => vbotKbFormatBytes($sz),
                    'mtime' => $mt,
                    'created_at' => $mt > 0 ? date('d/m/Y H:i:s', $mt) : '',
                ];
            }
        }
    }
    usort($backups, function ($a, $b) {
        return $b['mtime'] <=> $a['mtime'];
    });
    vbotApiJsonResponse([
        'success' => true,
        'directory' => $realBackupDir,
        'max_keep' => $maxBackupFiles,
        'count' => count($backups),
        'backups' => $backups,
    ]);
}

if ($method === 'GET' && $action === 'download_backup') {
    $safe = vbotKbSanitizeBackupName($_GET['file'] ?? '');
    if ($safe === null) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp sao lưu không hợp lệ.'], 400);
    }
    $target = $realBackupDir . DIRECTORY_SEPARATOR . $safe;
    if (!is_file($target) || dirname(realpath($target)) !== $realBackupDir) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy tệp sao lưu.'], 404);
    }
    header('Content-Description: File Transfer');
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . rawurlencode($safe) . '"');
    header('Content-Length: ' . filesize($target));
    readfile($target);
    exit;
}

if ($method === 'POST') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

    if ($action === 'save') {
        $safeName = vbotKbSanitizeFilename($_POST['filename'] ?? '', $editableExtensions, 'txt');
        $content = (string)($_POST['content'] ?? '');
        if ($safeName === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tài liệu không hợp lệ (chỉnh sửa trực tiếp hỗ trợ: txt, md, json, xml, yaml, csv, html, py, js, c, cpp, h).'], 400);
        }
        if (trim($content) === '') {
            vbotApiJsonResponse(['success' => false, 'message' => 'Nội dung tri thức không được để trống.'], 400);
        }
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $limitForExt = (int)($fileSizeLimits[$ext] ?? (5 * $mb));
        if (strlen($content) > $limitForExt) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Nội dung tệp .' . $ext . ' vượt quá giới hạn ' . vbotKbFormatBytes($limitForExt) . '.'], 413);
        }
        if ($ext === 'json') {
            json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Nội dung JSON không hợp lệ: ' . json_last_error_msg()], 400);
            }
        }
        $target = $realKnowledgeDir . DIRECTORY_SEPARATOR . $safeName;
        $written = @file_put_contents($target, $content, LOCK_EX);
        if ($written === false) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể ghi tệp vào thư mục kho tri thức.'], 500);
        }
        @chmod($target, 0777);
        $catalog = vbotKbReadCatalog($catalogJsonPath);
        if (isset($catalog['documents'][$safeName]) && is_array($catalog['documents'][$safeName])) {
            $catalog['documents'][$safeName]['status'] = 'pending';
            vbotKbWriteCatalog($catalogJsonPath, $catalog);
        }
        vbotApiJsonResponse([
            'success' => true,
            'filename' => $safeName,
            'message' => 'Đã lưu tài liệu tri thức "' . $safeName . '" thành công! Bạn có thể bấm nút lập chỉ mục (biểu tượng CPU) ở dòng tài liệu hoặc hỏi trực tiếp VBot.',
        ]);
    }

    if ($action === 'upload') {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Chưa chọn tệp để tải lên.'], 400);
        }
        $f = $_FILES['file'];
        $uploadErr = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadErr !== UPLOAD_ERR_OK) {
            if ($uploadErr === UPLOAD_ERR_INI_SIZE || $uploadErr === UPLOAD_ERR_FORM_SIZE) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Tệp vượt quá giới hạn upload_max_filesize của PHP (' . ini_get('upload_max_filesize') . ').'], 413);
            }
            vbotApiJsonResponse(['success' => false, 'message' => 'Tải tệp thất bại (mã lỗi: ' . $uploadErr . ').'], 400);
        }
        $safeName = vbotKbSanitizeFilename($f['name'] ?? '', $allowedExtensions, 'txt');
        if ($safeName === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Định dạng không hỗ trợ. Chấp nhận: PDF, DOCX, ODT, XLS, XLSX, PPT, PPTX, MD, TXT, RTF, CSV, JSON, XML, YAML, HTML, EPUB, PY, JS, C, CPP, H.'], 400);
        }
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $limitForExt = (int)($fileSizeLimits[$ext] ?? (5 * $mb));
        $fileSize = (int)($f['size'] ?? 0);
        if ($fileSize <= 0 || $fileSize > $limitForExt) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Tệp .' . $ext . ' phải có dung lượng lớn hơn 0 và tối đa ' . vbotKbFormatBytes($limitForExt) . '.',
            ], 413);
        }
        $target = $realKnowledgeDir . DIRECTORY_SEPARATOR . $safeName;
        if (!@move_uploaded_file($f['tmp_name'], $target)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu tệp tải lên vào thư mục tri thức.'], 500);
        }
        @chmod($target, 0777);
        $uploadedSha = @hash_file('sha256', $target) ?: '';
        $dupFile = '';
        $catalog = vbotKbReadCatalog($catalogJsonPath);
        if ($uploadedSha !== '' && isset($catalog['documents']) && is_array($catalog['documents'])) {
            foreach ($catalog['documents'] as $otherFn => $otherMeta) {
                if ($otherFn !== $safeName && is_array($otherMeta) && !empty($otherMeta['sha256']) && $otherMeta['sha256'] === $uploadedSha) {
                    $dupFile = (string)$otherFn;
                    break;
                }
            }
        }
        if (isset($catalog['documents'][$safeName]) && is_array($catalog['documents'][$safeName])) {
            $catalog['documents'][$safeName]['status'] = 'pending';
            vbotKbWriteCatalog($catalogJsonPath, $catalog);
        }
        $msg = 'Đã tải lên tài liệu "' . $safeName . '" thành công!';
        if ($dupFile !== '') {
            $msg .= ' (Cảnh báo: Nội dung trùng 100% với tệp "' . $dupFile . '" đã có trong kho).';
        } else {
            $msg .= ' Bấm nút lập chỉ mục (biểu tượng CPU) ở dòng tài liệu hoặc hỏi trực tiếp VBot.';
        }
        vbotApiJsonResponse([
            'success' => true,
            'filename' => $safeName,
            'duplicate_of' => $dupFile,
            'message' => $msg,
        ]);
    }

    if ($action === 'update_meta') {
        $safeName = vbotKbSanitizeFilename($_POST['filename'] ?? '', $allowedExtensions, 'txt');
        if ($safeName === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp không hợp lệ.'], 400);
        }
        $target = $realKnowledgeDir . DIRECTORY_SEPARATOR . $safeName;
        if (!is_file($target) || dirname(realpath($target)) !== $realKnowledgeDir) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy tệp tri thức.'], 404);
        }
        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '') {
            $title = vbotKbDefaultTitle($safeName);
        }
        if (mb_strlen($title, 'UTF-8') > 160) {
            $title = mb_substr($title, 0, 160, 'UTF-8');
        }
        $category = trim((string)($_POST['category'] ?? ''));
        if ($category === '') {
            $category = 'Tài liệu';
        }
        if (mb_strlen($category, 'UTF-8') > 60) {
            $category = mb_substr($category, 0, 60, 'UTF-8');
        }
        $tags = vbotKbParseTags($_POST['tags'] ?? '');
        $summary = trim((string)($_POST['summary'] ?? ''));
        if (mb_strlen($summary, 'UTF-8') > 500) {
            $summary = mb_substr($summary, 0, 500, 'UTF-8');
        }

        $catalog = vbotKbReadCatalog($catalogJsonPath);
        $entry = isset($catalog['documents'][$safeName]) && is_array($catalog['documents'][$safeName])
            ? $catalog['documents'][$safeName]
            : ['filename' => $safeName];
        $entry['filename'] = $safeName;
        $entry['title'] = $title;
        $entry['category'] = $category;
        $entry['tags'] = $tags;
        $entry['summary'] = $summary;
        $catalog['documents'][$safeName] = $entry;

        if (!vbotKbWriteCatalog($catalogJsonPath, $catalog)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu metadata vào knowledge_catalog.json.'], 500);
        }
        vbotApiJsonResponse([
            'success' => true,
            'message' => 'Đã cập nhật danh mục & metadata cho "' . $safeName . '".',
            'entry' => $entry,
        ]);
    }

    if ($action === 'index_file') {
        $safeName = vbotKbSanitizeFilename($_POST['filename'] ?? '', $allowedExtensions, 'txt');
        if ($safeName === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp không hợp lệ.'], 400);
        }
        $target = $realKnowledgeDir . DIRECTORY_SEPARATOR . $safeName;
        if (!is_file($target) || dirname(realpath($target)) !== $realKnowledgeDir) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy tệp tri thức.'], 404);
        }
        $res = vbotKbRunPythonIndexer($VBot_Offline, 'index_file', $safeName);
        if (!$res['ok']) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Chưa thể lập chỉ mục tệp "' . $safeName . '" (' . $res['reason'] . ').',
            ], 200);
        }
        $resultData = $res['data']['result'] ?? [];
        if (empty($resultData['ok'])) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => (string)($resultData['message'] ?? 'Lập chỉ mục thất bại.'),
            ], 200);
        }
        $fileEntry = $resultData['file'] ?? [];
        $cCnt = (int)($fileEntry['chunk_count'] ?? 0);
        $vCnt = (int)($fileEntry['vector_count'] ?? 0);
        $msg = sprintf(
            'Đã lập chỉ mục tệp "%s": %d đoạn FTS5, %d vector ngữ nghĩa (kb_vectors).',
            $safeName,
            $cCnt,
            $vCnt
        );
        vbotApiJsonResponse([
            'success' => true,
            'message' => $msg,
            'file' => $fileEntry,
            'stats' => $res['data']['stats'] ?? [],
        ]);
    }

    if ($action === 'import_url') {
        $url = trim((string)($_POST['url'] ?? ''));
        $cat = trim((string)($_POST['category'] ?? 'Web'));
        if ($url === '') {
            vbotApiJsonResponse(['success' => false, 'message' => 'Vui lòng nhập đường dẫn URL (http:// hoặc https://).'], 400);
        }
        $res = vbotKbRunPythonIndexer($VBot_Offline, 'import_url', $url, ['category' => $cat]);
        if (!$res['ok']) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Không thể nạp trang web (' . $res['reason'] . ').',
            ], 200);
        }
        $resultData = $res['data']['result'] ?? [];
        if (empty($resultData['ok'])) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => (string)($resultData['message'] ?? 'Nạp URL thất bại.'),
            ], 200);
        }
        $fn = (string)($resultData['filename'] ?? '');
        $fInfo = $resultData['file'] ?? [];
        vbotApiJsonResponse([
            'success' => true,
            'filename' => $fn,
            'message' => sprintf(
                'Đã nạp trang web thành "%s" (%d đoạn FTS5, %d vector).',
                $fn,
                (int)($fInfo['chunk_count'] ?? 0),
                (int)($fInfo['vector_count'] ?? 0)
            ),
        ]);
    }

    if ($action === 'refresh_url') {
        $safeName = vbotKbSanitizeFilename($_POST['filename'] ?? '', $allowedExtensions, 'md');
        if ($safeName === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp không hợp lệ.'], 400);
        }
        $res = vbotKbRunPythonIndexer($VBot_Offline, 'refresh_url', $safeName);
        if (!$res['ok']) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Không thể cập nhật lại từ URL (' . $res['reason'] . ').',
            ], 200);
        }
        $resultData = $res['data']['result'] ?? [];
        if (empty($resultData['ok'])) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => (string)($resultData['message'] ?? 'Cập nhật URL thất bại.'),
            ], 200);
        }
        $fInfo = $resultData['file'] ?? [];
        vbotApiJsonResponse([
            'success' => true,
            'message' => sprintf(
                'Đã cập nhật nội dung mới nhất từ URL cho "%s" (%d đoạn FTS5, %d vector).',
                $safeName,
                (int)($fInfo['chunk_count'] ?? 0),
                (int)($fInfo['vector_count'] ?? 0)
            ),
        ]);
    }

    if ($action === 'create_backup') {
        $backupParentDir = dirname($realBackupDir);
        if (!is_dir($backupParentDir)) {
            @mkdir($backupParentDir, 0777, true);
        }
        @chmod($backupParentDir, 0777);
        if (!is_dir($realBackupDir)) {
            @mkdir($realBackupDir, 0777, true);
        }
        @chmod($realBackupDir, 0777);
        $res = vbotKbRunPythonIndexer($VBot_Offline, 'backup_tar', $realBackupDir, ['max_keep' => $maxBackupFiles]);
        if (!$res['ok']) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Tạo bản sao lưu .tar.gz thất bại (' . $res['reason'] . ').',
            ], 200);
        }
        $resultData = $res['data']['result'] ?? [];
        if (empty($resultData['ok'])) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => (string)($resultData['message'] ?? 'Không tạo được bản sao lưu .tar.gz.'),
            ], 200);
        }
        $fn = (string)($resultData['filename'] ?? '');
        $packed = (int)($resultData['files_packed'] ?? 0);
        $deletedOld = isset($resultData['deleted_old']) && is_array($resultData['deleted_old']) ? $resultData['deleted_old'] : [];
        $msg = 'Đã tạo bản sao lưu "' . $fn . '" (' . $packed . ' tài liệu + chỉ mục & vector).';
        if (count($deletedOld) > 0) {
            $msg .= ' Đã tự động xóa bản cũ nhất: ' . implode(', ', $deletedOld) . ' (giới hạn tối đa ' . $maxBackupFiles . ' bản).';
        }
        vbotApiJsonResponse([
            'success' => true,
            'filename' => $fn,
            'message' => $msg,
        ]);
    }

    if ($action === 'restore_backup') {
        $safe = vbotKbSanitizeBackupName($_POST['filename'] ?? '');
        if ($safe === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp sao lưu không hợp lệ.'], 400);
        }
        $target = $realBackupDir . DIRECTORY_SEPARATOR . $safe;
        if (!is_file($target) || dirname(realpath($target)) !== $realBackupDir) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy tệp sao lưu "' . $safe . '".'], 404);
        }
        $res = vbotKbRunPythonIndexer($VBot_Offline, 'restore_tar', $target);
        if (!$res['ok']) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Khôi phục từ bản sao lưu thất bại (' . $res['reason'] . ').',
            ], 200);
        }
        $resultData = $res['data']['result'] ?? [];
        if (empty($resultData['ok'])) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => (string)($resultData['message'] ?? 'Khôi phục thất bại.'),
            ], 200);
        }
        vbotApiJsonResponse([
            'success' => true,
            'message' => sprintf(
                'Đã khôi phục từ "%s": %d tài liệu%s%s (quyền 0777)!',
                $safe,
                (int)($resultData['restored_docs'] ?? 0),
                !empty($resultData['restored_db']) ? ' + chỉ mục FTS5 & bảng vector (kb_vectors)' : '',
                !empty($resultData['restored_cat']) ? ' + danh mục metadata' : ''
            ),
        ]);
    }

    if ($action === 'upload_restore_backup') {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Chưa chọn tệp sao lưu .tar.gz để tải lên.'], 400);
        }
        $f = $_FILES['file'];
        if ((int)($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tải tệp sao lưu .tar.gz thất bại.'], 400);
        }
        if ((int)($f['size'] ?? 0) > 150 * $mb) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tệp sao lưu .tar.gz vượt quá giới hạn 150MB.'], 413);
        }
        $origName = strtolower((string)($f['name'] ?? ''));
        if (!preg_match('/\.(?:tar\.gz|tgz)$/', $origName)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Chỉ chấp nhận tệp sao lưu định dạng .tar.gz hoặc .tgz.'], 400);
        }
        $tmpTar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vbot_kb_restore_' . uniqid('', true) . '.tar.gz';
        if (!@move_uploaded_file($f['tmp_name'], $tmpTar)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể lưu tệp tạm để giải nén khôi phục.'], 500);
        }
        $res = vbotKbRunPythonIndexer($VBot_Offline, 'restore_tar', $tmpTar);
        @unlink($tmpTar);
        if (!$res['ok']) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Khôi phục từ tệp tải lên thất bại (' . $res['reason'] . ').',
            ], 200);
        }
        $resultData = $res['data']['result'] ?? [];
        if (empty($resultData['ok'])) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => (string)($resultData['message'] ?? 'Khôi phục thất bại.'),
            ], 200);
        }
        vbotApiJsonResponse([
            'success' => true,
            'message' => sprintf(
                'Đã khôi phục thành công từ tệp tải lên: %d tài liệu%s%s (quyền 0777)!',
                (int)($resultData['restored_docs'] ?? 0),
                !empty($resultData['restored_db']) ? ' + chỉ mục FTS5 & bảng vector (kb_vectors)' : '',
                !empty($resultData['restored_cat']) ? ' + danh mục metadata' : ''
            ),
        ]);
    }

    if ($action === 'delete_backup') {
        $safe = vbotKbSanitizeBackupName($_POST['filename'] ?? '');
        if ($safe === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp sao lưu không hợp lệ.'], 400);
        }
        $target = $realBackupDir . DIRECTORY_SEPARATOR . $safe;
        if (!is_file($target) || dirname(realpath($target)) !== $realBackupDir) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy tệp sao lưu.'], 404);
        }
        if (!@unlink($target)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể xóa tệp sao lưu.'], 500);
        }
        vbotApiJsonResponse([
            'success' => true,
            'message' => 'Đã xóa bản sao lưu "' . $safe . '".',
        ]);
    }

    if ($action === 'auto_tag') {
        $safeName = vbotKbSanitizeFilename($_POST['filename'] ?? '', $allowedExtensions, 'txt');
        if ($safeName === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp không hợp lệ.'], 400);
        }
        $target = $realKnowledgeDir . DIRECTORY_SEPARATOR . $safeName;
        if (!is_file($target) || dirname(realpath($target)) !== $realKnowledgeDir) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy tệp tri thức.'], 404);
        }
        $res = vbotKbRunPythonIndexer($VBot_Offline, 'auto_tag', $safeName);
        if (!$res['ok']) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'AI tự phân loại thất bại (' . $res['reason'] . ').',
            ], 200);
        }
        $resultData = $res['data']['result'] ?? [];
        if (empty($resultData['ok'])) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => (string)($resultData['message'] ?? 'Không phân tích được metadata bằng AI.'),
            ], 200);
        }
        vbotApiJsonResponse([
            'success' => true,
            'message' => 'AI đã tự động tạo Tiêu đề, Danh mục, Tags & Tóm tắt cho "' . $safeName . '"!',
            'entry' => $resultData['entry'] ?? [],
        ]);
    }

    if ($action === 'reindex') {
        $force = !empty($_POST['force']) && (string)$_POST['force'] !== '0';
        $res = vbotKbRunPythonIndexer($VBot_Offline, 'reindex', $force ? '1' : '0');
        if (!$res['ok']) {
            vbotApiJsonResponse([
                'success' => false,
                'message' => 'Chưa thể chạy lập chỉ mục trực tiếp từ PHP (' . $res['reason'] . '). VBot Python vẫn sẽ tự động lập chỉ mục ở lần hỏi kế tiếp.',
            ], 200);
        }
        $sync = $res['data']['sync'] ?? [];
        $stats = $res['data']['stats'] ?? [];
        $indexedFiles = (int)($sync['indexed'] ?? 0);
        $totalFiles = (int)($stats['total_files'] ?? 0);
        $unchangedFiles = max(0, $totalFiles - $indexedFiles);
        $msg = sprintf(
            'Đã đồng bộ kho tri thức: %d tệp mới/cập nhật, %d tệp giữ nguyên, %d vector ngữ nghĩa vừa tạo (Tổng: %d tệp, %d đoạn).',
            $indexedFiles,
            $unchangedFiles,
            (int)($sync['vectors_built'] ?? 0),
            $totalFiles,
            (int)($stats['total_chunks'] ?? 0)
        );
        vbotApiJsonResponse([
            'success' => true,
            'message' => $msg,
            'sync' => $sync,
            'stats' => $stats,
        ]);
    }

    if ($action === 'delete') {
        $safeName = vbotKbSanitizeFilename($_POST['filename'] ?? '', $allowedExtensions, 'txt');
        if ($safeName === null) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên tệp không hợp lệ.'], 400);
        }
        $target = $realKnowledgeDir . DIRECTORY_SEPARATOR . $safeName;
        if (!is_file($target) || dirname(realpath($target)) !== $realKnowledgeDir) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tệp không tồn tại.'], 404);
        }
        if (!@unlink($target)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Không thể xóa tệp tri thức.'], 500);
        }
        $catalog = vbotKbReadCatalog($catalogJsonPath);
        if (isset($catalog['documents'][$safeName])) {
            unset($catalog['documents'][$safeName]);
            vbotKbWriteCatalog($catalogJsonPath, $catalog);
        }
        vbotApiJsonResponse([
            'success' => true,
            'message' => 'Đã xóa tài liệu "' . $safeName . '".',
        ]);
    }
}

vbotApiJsonResponse(['success' => false, 'message' => 'Hành động không hợp lệ.'], 400);
