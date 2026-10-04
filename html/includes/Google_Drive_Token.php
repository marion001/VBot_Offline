<?php
// Serialize the whole read/refresh/save cycle, not only the final rename.
function vbotGoogleDriveTokenLock($path)
{
    $handle = @fopen($path . '.oauth.lock', 'c+');
    if ($handle === false) return false;
    $deadline = microtime(true) + 10;
    do {
        if (@flock($handle, LOCK_EX | LOCK_NB)) return $handle;
        usleep(50000);
    } while (microtime(true) < $deadline);
    fclose($handle);
    return false;
}

function vbotGoogleDriveRefreshToken($client, $path, &$accessToken)
{
    $lock = vbotGoogleDriveTokenLock($path);
    if ($lock === false) return ['error' => 'token_lock_failed'];
    try {
        $saved = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
        if (!is_array($saved) || empty($saved['access_token'])) return ['error' => 'missing_saved_token'];
        $client->setAccessToken($saved);
        if (!$client->isAccessTokenExpired()) {
            $accessToken = $saved;
            return $saved;
        }
        if (empty($saved['refresh_token'])) return ['error' => 'missing_refresh_token'];
        $response = $client->fetchAccessTokenWithRefreshToken($saved['refresh_token']);
        if (!is_array($response) || empty($response['access_token'])) {
            return ['error' => is_array($response) ? (string)($response['error'] ?? 'refresh_failed') : 'refresh_failed'];
        }
        if (empty($response['refresh_token'])) unset($response['refresh_token']);
        $merged = array_merge($saved, $response);
        $encoded = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false || !vbotAtomicWriteFile($path, $encoded, 'token Google Drive')) {
            return ['error' => 'token_write_failed'];
        }
        $accessToken = $merged;
        $client->setAccessToken($merged);
        return $merged;
    } catch (Throwable $error) {
        // Never expose a transport exception that may contain OAuth secrets.
        error_log('[Google Drive] Refresh failed (' . get_class($error) . ')');
        return ['error' => 'refresh_transport_failed'];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function vbotGoogleDriveSaveAuthorization($path, array $response)
{
    // An auth-code response without offline credentials must not destroy an
    // existing refresh token, or reuse credentials from a different account.
    if (empty($response['access_token']) || empty($response['refresh_token'])) return false;
    $lock = vbotGoogleDriveTokenLock($path);
    if ($lock === false) return false;
    try {
        $encoded = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded !== false && vbotAtomicWriteFile($path, $encoded, 'token Google Drive');
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function vbotGoogleDrivePreserveCredential($destination)
{
    $path = str_replace('\\', '/', (string)$destination);
    return is_file($destination) && preg_match(
        '#(?:^|/)includes/other_data/Google_Driver_PHP/(?:verify_token|client_secret)\.json(?:\.(?:lock|oauth\.lock))?$#i',
        $path
    ) === 1;
}
