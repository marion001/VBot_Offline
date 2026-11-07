<?php
require_once __DIR__.'/ActionRegistry.php';

function vbotSchedulerActionOptions(array $config): array {
    $options = vbotActionRegistryOptions($config, true);
    $path = dirname(__DIR__, 2).'/resource/voice_routines/voice_routines.json';
    if (!is_file($path)) $path = dirname(__DIR__, 2).'/resource/voice_routines.json';
    $data = is_file($path) && filesize($path) <= 1048576 ? json_decode((string)file_get_contents($path), true) : [];
    foreach (is_array($data['routines'] ?? null) ? $data['routines'] : [] as $routine) {
        if (!is_array($routine)) continue;
        $id = $routine['id'] ?? '';
        if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $id)) {
            $options['routine:'.$id] = 'Chạy kịch bản: '.($routine['name'] ?? $id).(($routine['enabled'] ?? false) ? '' : ' (đang tắt)');
        }
    }
    $wolPath=dirname(__DIR__, 2).'/resource/wake_on_lan/devices.json';
    $wol=is_file($wolPath)?json_decode((string)@file_get_contents($wolPath),true):[];
    $legacyPath=dirname(__DIR__, 2).'/Config.json';
    $mainConfig=is_file($legacyPath)?json_decode((string)@file_get_contents($legacyPath),true):[];
    $wolActive=$config['wake_on_lan']['active']??$mainConfig['wake_on_lan']['active']??$wol['active']??false;
    foreach (is_array($wol['devices']??null)?$wol['devices']:[] as $device) {
        if (!is_array($device) || !is_string($device['id']??null) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$device['id'])) continue;
        $enabled=$wolActive && ($device['enabled']??false);
        $options['wol:'.$device['id']]='Bật máy tính (Wake-on-LAN): '.($device['name']??$device['id']).($enabled?'':' (đang tắt)');
    }
    return $options;
}

function vbotSchedulerNormalizeAction(array $config, $action): string {
    $value = trim((string)$action);
    if (strpos($value, 'vbot_action:') === 0) $value = substr($value, 12);
    // Preserve a removed routine's ID so a save never silently replaces it.
    if (preg_match('/^(?:(?:routine|wol):[A-Za-z0-9_-]{1,64}|playlist:[A-Za-z0-9_-]{1,64}|radio:[0-9a-f]{12}|ir:[0-9a-f]{40}|tuya:[A-Za-z0-9_-]{1,260}:(?:on|off)|hass:[A-Za-z0-9_-]{1,220})$/D', $value)) return $value;
    return vbotActionRegistryNormalize($config, $value);
}
