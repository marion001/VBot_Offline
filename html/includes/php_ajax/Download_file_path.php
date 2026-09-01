<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['GET'], false);
include '../../Configuration.php';
$allowedFileRoots = vbotApiAllowedRoots([$VBot_Offline, $directory_path]);
$loginActive = !empty($Config['contact_info']['user_login']['active']);
$fileAccessSecurity = !array_key_exists('file_access_security', $Config['web_interface'])
    || !empty($Config['web_interface']['file_access_security']);
$anonymousDownloadRoots = vbotApiAllowedRoots([
    $VBot_Offline.'resource/log',
    $VBot_Offline.'resource/schedule',
    $VBot_Offline.'resource/broadlink',
    $VBot_Offline.'resource/internal_ir',
    $VBot_Offline.'Media',
    $VBot_Offline.'TTS_Audio',
    $directory_path.'/includes/cache',
    $directory_path.'/includes/other_data',
]);

// Preserve UTF-8 file names regardless of the operating system locale.
function vbotUtf8Basename($path)
{
    $path = str_replace('\\', '/', (string) $path);
    $parts = explode('/', $path);
    return end($parts);
}

function vbotSendDownloadFilename($fileName)
{
    // Prevent control characters from injecting additional HTTP headers.
    $fileName = preg_replace('/[\x00-\x1F\x7F]/', '', (string) $fileName);
    $asciiFallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);
    if ($asciiFallback === '' || $asciiFallback === null) {
        $asciiFallback = 'download';
    }

    header(
        'Content-Disposition: attachment; filename="' . $asciiFallback .
        '"; filename*=UTF-8\'\'' . rawurlencode($fileName)
    );
}

if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (
        !isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
    ) {
        session_unset();
        session_destroy();
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
        ], 401);
    }
}

$requestedFile = isset($_GET['file']) ? $_GET['file'] : '';
if (filter_var($requestedFile, FILTER_VALIDATE_URL)) {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Endpoint này chỉ cho phép tải file nội bộ VBot.'
    ], 400);
}

$file = vbotApiResolveExistingPath($requestedFile, $allowedFileRoots, 'file');
if ($file === false || !is_readable($file)) {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'File không tồn tại hoặc nằm ngoài thư mục VBot.'
    ], 404);
}

if (!vbotApiCanExposeFile($file, $loginActive, $anonymousDownloadRoots, $VBot_Offline, $fileAccessSecurity)) {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'File chứa dữ liệu bảo mật hoặc không thuộc vùng được phép tải xuống.'
    ], 403);
}

$fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$restrictedExtensions = array_values(array_diff(
    array_map('strtolower', $Restricted_Extensions),
    ['json']
));
if (in_array($fileExtension, $restrictedExtensions, true)) {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Bạn không có quyền tải xuống file này.'
    ], 403);
}

if (isset($_GET['check'])) {
    vbotApiJsonResponse([
        'success' => true,
        'status' => 'success',
        'message' => 'Tệp sẵn sàng để tải xuống.',
        'file_name' => vbotUtf8Basename($file),
        'file_size' => filesize($file)
    ]);
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
vbotSendDownloadFilename(vbotUtf8Basename($file));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: '.filesize($file));
readfile($file);
exit;
?>
