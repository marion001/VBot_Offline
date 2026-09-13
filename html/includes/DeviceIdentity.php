<?php

if (!function_exists('vbotStableDeviceId')) {
    function vbotStableDeviceId() {
        $interfaces = array_merge(['wlan0', 'eth0'], glob('/sys/class/net/*', GLOB_ONLYDIR) ?: []);
        $checked = [];
        foreach ($interfaces as $interface) {
            $name = basename($interface);
            if ($name === 'lo' || isset($checked[$name])) {
                continue;
            }
            $checked[$name] = true;
            $macFile = '/sys/class/net/' . $name . '/address';
            $mac = is_readable($macFile) ? strtolower(trim((string)file_get_contents($macFile))) : '';
            if (preg_match('/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/', $mac) && $mac !== '00:00:00:00:00:00') {
                return str_replace(':', '', $mac);
            }
        }
        $machineIdFile = '/etc/machine-id';
        $machineId = is_readable($machineIdFile) ? trim((string)file_get_contents($machineIdFile)) : '';
        return $machineId !== '' ? substr(hash('sha256', 'machine-id:' . $machineId), 0, 12) : '';
    }
}
