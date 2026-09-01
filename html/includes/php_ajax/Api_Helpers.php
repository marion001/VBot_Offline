<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

function vbotApiJsonResponse(array $payload, $statusCode = 200)
{
    if (isset($GLOBALS['vbot_api_buffer_level'])) {
        $suppressedOutput = '';
        while (ob_get_level() >= $GLOBALS['vbot_api_buffer_level']) {
            $bufferContent = ob_get_contents();
            if ($bufferContent !== false && $bufferContent !== '') {
                $suppressedOutput = $bufferContent.$suppressedOutput;
            }
            ob_end_clean();
        }
        unset($GLOBALS['vbot_api_buffer_level']);
        if ($suppressedOutput !== '') {
            $logOutput = preg_replace('/\s+/', ' ', strip_tags($suppressedOutput));
            if ($logOutput === null) {
                $logOutput = $suppressedOutput;
            }
            error_log(
                'Suppressed API output: '
                .substr($logOutput, 0, 4000)
                .(strlen($logOutput) > 4000 ? ' [truncated]' : '')
            );
        }
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($json === false) {
        http_response_code(500);
        $json = '{"success":false,"status":"error","message":"Không thể mã hóa phản hồi JSON."}';
        error_log('JSON encode failed: '.json_last_error_msg());
    }
    echo $json;
    exit;
}

function vbotApiInitialize($allowedMethods = ['GET', 'POST'], $bufferOutput = true)
{
    if (!defined('VBOT_JSON_API')) {
        define('VBOT_JSON_API', true);
    }
    // Keep HTML-formatted PHP warnings out of JSON API responses.
    // Errors are still written to the configured PHP error log.
    ini_set('display_errors', '0');
    ini_set('html_errors', '0');
    if ($bufferOutput && !isset($GLOBALS['vbot_api_buffer_level'])) {
        ob_start();
        $GLOBALS['vbot_api_buffer_level'] = ob_get_level();
        set_exception_handler(function ($exception) {
            error_log('Unhandled API exception: '.$exception);
            vbotApiJsonResponse([
                'success' => false,
                'status' => 'error',
                'message' => 'Máy chủ gặp lỗi khi xử lý yêu cầu.'
            ], 500);
        });
        register_shutdown_function(function () {
            $error = error_get_last();
            if (
                $error !== null
                && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)
            ) {
                error_log(
                    'Fatal API error: '.$error['message']
                    .' in '.$error['file'].':'.$error['line']
                );
                vbotApiJsonResponse([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Máy chủ gặp lỗi nghiêm trọng khi xử lý yêu cầu.'
                ], 500);
            }

            if (!isset($GLOBALS['vbot_api_buffer_level'])) {
                return;
            }
            $isJsonResponse = false;
            foreach (headers_list() as $headerLine) {
                if (stripos($headerLine, 'Content-Type:') === 0) {
                    $isJsonResponse = stripos($headerLine, 'application/json') !== false;
                }
            }
            if (!$isJsonResponse) {
                return;
            }
            $responseBody = ob_get_contents();
            if ($responseBody === false || trim($responseBody) === '') {
                return;
            }
            json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log(
                    'Invalid buffered API JSON suppressed: '
                    .json_last_error_msg()
                    .' | '.substr(preg_replace('/\s+/', ' ', strip_tags($responseBody)), 0, 4000)
                );
                vbotApiJsonResponse([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Máy chủ tạo phản hồi JSON không hợp lệ.'
                ], 500);
            }
        });
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    if (!in_array($method, $allowedMethods, true)) {
        header('Allow: '.implode(', ', $allowedMethods));
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Phương thức HTTP không được hỗ trợ.'
        ], 405);
    }
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

function vbotApiAllowedRoots(array $candidateRoots)
{
    $roots = [];
    foreach ($candidateRoots as $root) {
        if (!is_string($root) || $root === '') {
            continue;
        }
        $resolved = realpath($root);
        if ($resolved !== false && is_dir($resolved)) {
            $roots[] = rtrim($resolved, DIRECTORY_SEPARATOR);
        }
    }
    return array_values(array_unique($roots));
}

function vbotApiPathIsInside($resolvedPath, array $allowedRoots)
{
    foreach ($allowedRoots as $root) {
        if (
            $resolvedPath === $root
            || strpos($resolvedPath, $root.DIRECTORY_SEPARATOR) === 0
        ) {
            return true;
        }
    }
    return false;
}

function vbotApiResolveExistingPath($path, array $allowedRoots, $expectedType = null)
{
    if (!is_string($path) || $path === '' || strpos($path, "\0") !== false) {
        return false;
    }
    $resolved = realpath($path);
    if ($resolved === false || !vbotApiPathIsInside($resolved, $allowedRoots)) {
        return false;
    }
    if ($expectedType === 'file' && !is_file($resolved)) {
        return false;
    }
    if ($expectedType === 'directory' && !is_dir($resolved)) {
        return false;
    }
    return $resolved;
}

function vbotApiIsSensitiveFilePath($path)
{
    $normalized = strtolower(str_replace('\\', '/', (string) $path));
    $baseName = basename($normalized);
    if (in_array($baseName, [
        'config.json', 'config.json.bak', 'config_new.json', 'config_old.json',
        'client_secret.json', 'verify_token.json', 'profiles.json',
        'stt_token_google_cloud.json', 'tts_token_google_cloud.json',
    ], true)) {
        return true;
    }
    return strpos($normalized, '/backup_config/') !== false
        || strpos($normalized, '/google_driver_php/') !== false
        || strpos($normalized, '/.cloudflared/') !== false
        || preg_match('/(?:^|\/)(?:stt|tts)[_-]token[^\/]*\.json$/i', $normalized) === 1;
}

function vbotApiAnonymousReadablePath($resolvedPath, array $anonymousRoots, $vbotRoot)
{
    $versionFiles = [
        realpath($vbotRoot.'Version.json'),
        realpath($vbotRoot.'html/Version.json'),
    ];
    if (in_array($resolvedPath, array_filter($versionFiles), true)) {
        return true;
    }
    return vbotApiPathIsInside($resolvedPath, $anonymousRoots);
}

function vbotApiCanExposeFile($resolvedPath, $loginActive, array $anonymousRoots, $vbotRoot, $securityEnabled = true)
{
    if ($resolvedPath === false) {
        return false;
    }
    if (!$securityEnabled) {
        return true;
    }
    if (vbotApiIsSensitiveFilePath($resolvedPath)) {
        return false;
    }
    return $loginActive || vbotApiAnonymousReadablePath($resolvedPath, $anonymousRoots, $vbotRoot);
}

function vbotApiVerifyCsrf($required = true)
{
    if (!$required) {
        return true;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sessionToken = isset($_SESSION['vbot_csrf_token'])
        ? $_SESSION['vbot_csrf_token']
        : '';
    $requestToken = isset($_SERVER['HTTP_X_CSRF_TOKEN'])
        ? $_SERVER['HTTP_X_CSRF_TOKEN']
        : (isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
    if (
        !is_string($sessionToken)
        || $sessionToken === ''
        || !is_string($requestToken)
        || !hash_equals($sessionToken, $requestToken)
    ) {
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'CSRF token không hợp lệ hoặc đã hết hạn.'
        ], 403);
    }
    return true;
}
