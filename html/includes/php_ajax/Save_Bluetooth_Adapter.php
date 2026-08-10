<?php

require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
include __DIR__.'/../../Configuration.php';

if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (
        !isset($_SESSION['user_login'])
        || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200)
    ) {
        session_unset();
        session_destroy();
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Phiên đăng nhập đã hết hạn.'
        ], 401);
    }
}

vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

$adapter = strtolower(trim(isset($_POST['adapter']) ? (string) $_POST['adapter'] : ''));
if (!preg_match('/^hci\d+$/', $adapter)) {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Bluetooth adapter không hợp lệ.'
    ], 400);
}

if (!isset($Config['bluetooth']) || !is_array($Config['bluetooth'])) {
    $Config['bluetooth'] = [];
}
$Config['bluetooth']['adapter'] = $adapter;
$encodedConfig = json_encode(
    $Config,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
if ($encodedConfig === false || !vbotAtomicWriteFile($Config_filePath, $encodedConfig, 'Config.json Bluetooth adapter')) {
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Không thể lưu Bluetooth adapter vào Config.json.'
    ], 500);
}
@chmod($Config_filePath, 0777);

vbotApiJsonResponse([
    'success' => true,
    'status' => 'success',
    'adapter' => $adapter,
    'message' => 'Đã lưu Bluetooth adapter '.$adapter.'. Đang khởi động lại Bluetooth...'
]);

