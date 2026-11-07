<?php
require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['GET']);
include __DIR__.'/../../Configuration.php';

if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time'] > 43200)) {
        vbotApiJsonResponse(['success'=>false, 'message'=>'Bạn cần đăng nhập WebUI'], 401);
    }
}
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Cache-Control: no-store');

require_once dirname(__DIR__).'/ActionRegistry.php';
$all = vbotActionRegistryOptions($Config, true);
$base = vbotActionRegistryOptions($Config, false);
$deviceActions = array_diff_key($all, $base);
vbotApiJsonResponse(['success'=>true, 'actions'=>$deviceActions]);
