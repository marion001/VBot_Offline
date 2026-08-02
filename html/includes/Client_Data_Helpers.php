<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

if (!function_exists('vbotClientDataFileNameFromConfig')) {
    function vbotClientDataFileNameFromConfig(array $config)
    {
        $streaming = isset($config['api']['streaming_server']) && is_array($config['api']['streaming_server'])
            ? $config['api']['streaming_server']
            : [];
        $protocols = isset($streaming['protocol']) && is_array($streaming['protocol'])
            ? $streaming['protocol']
            : [];
        $selectedProtocol = isset($streaming['connection_protocol'])
            ? trim((string)$streaming['connection_protocol'])
            : '';
        $candidates = array_values(array_unique(array_filter([
            $selectedProtocol,
            'socket',
            'udp_sock'
        ])));

        foreach ($candidates as $protocolName) {
            if (
                isset($protocols[$protocolName])
                && is_array($protocols[$protocolName])
                && !empty($protocols[$protocolName]['data_client_name'])
            ) {
                $fileName = basename(str_replace('\\', '/', (string)$protocols[$protocolName]['data_client_name']));
                if (
                    preg_match('/^[A-Za-z0-9._-]{1,120}$/', $fileName)
                    && strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'json'
                ) {
                    return $fileName;
                }
            }
        }

        return 'Data_VBot_Client.json';
    }
}

if (!function_exists('vbotClientDataFilePath')) {
    function vbotClientDataFilePath(array $config, $htmlDirectory)
    {
        return rtrim($htmlDirectory, '/\\')
            . '/includes/other_data/VBot_Client_Data/'
            . vbotClientDataFileNameFromConfig($config);
    }
}
