<?php
function vbotWakeOnLANActive(array $config, string $legacyPath): bool {
    $active=$config['wake_on_lan']['active']??null;
    if (is_bool($active)) return $active;
    if (is_file($legacyPath) && filesize($legacyPath)<=1048576) {
        $legacy=json_decode((string)file_get_contents($legacyPath),true);
        if (is_array($legacy) && is_bool($legacy['active']??null)) return $legacy['active'];
    }
    return true;
}
