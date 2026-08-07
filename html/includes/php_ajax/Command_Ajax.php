<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

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
            'message' => 'Phiên đăng nhập đã hết hạn, bạn cần tải lại trang để quay về trang đăng nhập.'
        ], 401);
    }
}

vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
$allowedActions = [
    'chmod_vbot', 'owner_vbot', 'apache_restart', 'restart_alsa', 'reload_services',
    'serial_getty_ttyS0_start', 'serial_getty_ttyS0_stop',
    'serial_getty_ttyS0_enable', 'serial_getty_ttyS0_disable',
    'reboot_os',
    'fix_asound_airplay',
    'ifconfig_os', 'lscpu_os', 'hostnamectl_os',
    'kiem_tra_bo_nho', 'kiem_tra_dung_luong',
    'logs_apache2', 'list_systemctl_enabled', 'os_image_created',
    'sudo_alsactl_store', 'Stop_Service_Unnecessary_Processes',
    'pip_show_all_lib', 'pvporcupine_info', 'picovoice_info',
    'alsamixer_soundcard_start', 'alsamixer_soundcard_stop',
    'alsamixer_soundcard_enable', 'alsamixer_soundcard_disable',
    'alsamixer_soundcard_status',
    'auto_start', 'auto_stop', 'auto_restart', 'auto_enable', 'auto_disable', 'auto_status',
    'restart_auto_wifi', 'enable_auto_wifi', 'logs_auto_wifi', 'status_auto_wifi',
    'start_btwifiset', 'stop_btwifiset', 'restart_btwifiset', 'enable_btwifiset', 'disabled_btwifiset',
    'logs_btwifiset', 'status_btwifiset', 'pass_crypto_btwifiset',
    'start_vbot_bluetooth_agent', 'stop_vbot_bluetooth_agent', 'restart_vbot_bluetooth_agent',
    'enable_vbot_bluetooth_agent', 'disabled_vbot_bluetooth_agent',
    'status_vbot_bluetooth_agent', 'logs_vbot_bluetooth_agent',
    'start_bluealsa', 'stop_bluealsa', 'restart_bluealsa', 'enable_bluealsa', 'disabled_bluealsa',
    'status_bluealsa', 'logs_bluealsa',
    'start_airplay', 'stop_airplay', 'restart_airplay', 'enable_airplay', 'disabled_airplay',
    'logs_airplay', 'status_airplay', 'version_airplay',
    'cloudflared_tunnel_start', 'cloudflared_tunnel_stop', 'cloudflared_tunnel_enable',
    'cloudflared_tunnel_disable', 'cloudflared_tunnel_status', 'cloudflared_tunnel_list',
    'save_asound_to_alsamixer', 'alsamixer_asound_to_alsamixer', 'update_btwifiset_py',
    'fix_airplay_services', 'install_bluetooth_agent_py', 'install_bthelper', 'install_bluealsa',
    'install_bluetooth_agent_service', 'install_bluetooth_config_main',
    'check_version_picovoice_porcupine', 'list_time_zones', 'check_time_zones', 'fix_time_zones',
    'config_auto', 'auto_wifi_manager_only', 'auto_wifi_manager_and_speaker_ip',
    'enabled_vbot_api_external', 'disable_vbot_api_external',
    'install_picovoice', 'install_porcupine', 'set_time_zones',
    'submit_rename_airplay',
];
if (!in_array($action, $allowedActions, true)) {
    error_log('Command AJAX rejected unsupported action: '.$action);
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Thao tác không được hỗ trợ.'
    ], 400);
}

$confirmationActions = [
    'reboot_os', 'fix_asound_airplay', 'Stop_Service_Unnecessary_Processes',
    'alsamixer_asound_to_alsamixer', 'update_btwifiset_py', 'fix_airplay_services',
    'install_bluetooth_agent_py', 'install_bthelper', 'install_bluealsa',
    'install_bluetooth_agent_service', 'install_bluetooth_config_main', 'fix_time_zones',
    'config_auto', 'auto_wifi_manager_only', 'auto_wifi_manager_and_speaker_ip',
    'enabled_vbot_api_external', 'disable_vbot_api_external', 'install_picovoice', 'install_porcupine',
];
if (in_array($action, $confirmationActions, true)
    && (!isset($_POST['confirmed']) || $_POST['confirmed'] !== '1')) {
    error_log('Command AJAX '.$action.' rejected: confirmation is missing');
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Thao tác hệ thống chưa được xác nhận.'
    ], 400);
}

if (!function_exists('ssh2_connect')) {
    error_log('Command AJAX '.$action.' failed: PHP SSH2 extension is unavailable');
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'PHP chưa có extension SSH2.'
    ], 500);
}

$connection = @ssh2_connect($ssh_host, $ssh_port);
if (!$connection) {
    error_log('Command AJAX '.$action.' failed: cannot connect to SSH server '.$ssh_host.':'.$ssh_port);
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Không thể kết nối tới máy chủ SSH.'
    ], 502);
}

if (!@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
    error_log('Command AJAX '.$action.' failed: SSH authentication failed for user '.$ssh_user);
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Xác thực SSH không thành công.'
    ], 502);
}

if ($action === 'owner_vbot' && !preg_match('/^[a-z_][a-z0-9_-]*[$]?$/i', (string) $ssh_user)) {
    error_log('Command AJAX owner_vbot failed: invalid SSH username');
    vbotApiJsonResponse([
        'success' => false,
        'status' => 'error',
        'message' => 'Tên người dùng SSH không hợp lệ.'
    ], 500);
}

$commands = [];
$responseOutput = '';
$commandLog = '';
$outputActions = [
    'ifconfig_os', 'lscpu_os', 'hostnamectl_os', 'kiem_tra_bo_nho', 'kiem_tra_dung_luong',
    'logs_apache2', 'list_systemctl_enabled', 'os_image_created',
    'pip_show_all_lib', 'pvporcupine_info', 'picovoice_info',
    'alsamixer_soundcard_status',
    'auto_status', 'logs_auto_wifi', 'status_auto_wifi',
    'logs_btwifiset', 'status_btwifiset', 'pass_crypto_btwifiset',
    'status_vbot_bluetooth_agent', 'logs_vbot_bluetooth_agent',
    'status_bluealsa', 'logs_bluealsa', 'logs_airplay', 'status_airplay', 'version_airplay',
    'cloudflared_tunnel_status', 'cloudflared_tunnel_list',
    'check_version_picovoice_porcupine', 'list_time_zones', 'check_time_zones',
];
$successMessages = [
    'chmod_vbot' => 'Đã cấp quyền 0777 cho VBot và WebUI.',
    'owner_vbot' => 'Đã chuyển chủ sở hữu VBot và WebUI sang tài khoản '.$ssh_user.'.',
    'apache_restart' => 'Đã lên lịch khởi động lại Apache2.',
    'restart_alsa' => 'Đã khởi động lại dịch vụ ALSA.',
    'reload_services' => 'Đã tải lại cấu hình systemd.',
    'serial_getty_ttyS0_start' => 'Đã khởi động serial-getty@ttyS0.',
    'serial_getty_ttyS0_stop' => 'Đã dừng serial-getty@ttyS0.',
    'serial_getty_ttyS0_enable' => 'Đã bật serial-getty@ttyS0 khởi động cùng hệ thống.',
    'serial_getty_ttyS0_disable' => 'Đã tắt serial-getty@ttyS0 khỏi khởi động cùng hệ thống.',
    'reboot_os' => 'Đã lên lịch khởi động lại hệ thống.',
    'fix_asound_airplay' => 'Đã khôi phục và xác minh /etc/asound.conf thành công.',
    'ifconfig_os' => 'Thông tin mạng',
    'lscpu_os' => 'Thông tin CPU',
    'hostnamectl_os' => 'Thông tin hệ điều hành',
    'kiem_tra_bo_nho' => 'Thông tin dung lượng lưu trữ',
    'kiem_tra_dung_luong' => 'Thông tin bộ nhớ RAM',
    'logs_apache2' => '500 dòng lỗi Apache2 gần nhất',
    'list_systemctl_enabled' => 'Các service khởi động cùng hệ thống',
    'os_image_created' => 'Phiên bản OS Image',
    'sudo_alsactl_store' => 'Đã lưu trạng thái ALSA hiện tại.',
    'Stop_Service_Unnecessary_Processes' => 'Đã lên lịch tắt các service không cần thiết và khởi động lại hệ thống.',
    'pip_show_all_lib' => 'Danh sách thư viện Python đã cài đặt',
    'pvporcupine_info' => 'Thông tin thư viện pvporcupine',
    'picovoice_info' => 'Thông tin thư viện picovoice',
    'alsamixer_soundcard_start' => 'Đã khởi động WM8960 SoundCard.',
    'alsamixer_soundcard_stop' => 'Đã dừng WM8960 SoundCard.',
    'alsamixer_soundcard_enable' => 'Đã bật WM8960 SoundCard khởi động cùng hệ thống.',
    'alsamixer_soundcard_disable' => 'Đã tắt WM8960 SoundCard khỏi khởi động cùng hệ thống.',
    'alsamixer_soundcard_status' => 'Trạng thái WM8960 SoundCard',
    'auto_start' => 'Đã chạy VBot Offline.', 'auto_stop' => 'Đã dừng VBot Offline.',
    'auto_restart' => 'Đã khởi động lại VBot Offline.', 'auto_enable' => 'Đã bật tự khởi động VBot Offline.',
    'auto_disable' => 'Đã tắt tự khởi động VBot Offline.', 'auto_status' => 'Trạng thái VBot Offline',
    'restart_auto_wifi' => 'Đã khởi động lại Wi-Fi Manager.', 'enable_auto_wifi' => 'Đã bật tự khởi động Wi-Fi Manager.',
    'logs_auto_wifi' => 'Logs Wi-Fi Manager', 'status_auto_wifi' => 'Trạng thái Wi-Fi Manager',
    'start_btwifiset' => 'Đã chạy btwifiset.', 'stop_btwifiset' => 'Đã dừng btwifiset.',
    'restart_btwifiset' => 'Đã khởi động lại btwifiset.', 'enable_btwifiset' => 'Đã bật tự khởi động btwifiset.',
    'disabled_btwifiset' => 'Đã tắt tự khởi động btwifiset.', 'logs_btwifiset' => 'Logs btwifiset',
    'status_btwifiset' => 'Trạng thái btwifiset', 'pass_crypto_btwifiset' => 'Mật khẩu mã hóa btwifiset',
    'start_vbot_bluetooth_agent' => 'Đã chạy Bluetooth Agent.', 'stop_vbot_bluetooth_agent' => 'Đã dừng Bluetooth Agent.',
    'restart_vbot_bluetooth_agent' => 'Đã khởi động lại Bluetooth Agent.',
    'enable_vbot_bluetooth_agent' => 'Đã bật tự khởi động Bluetooth Agent.',
    'disabled_vbot_bluetooth_agent' => 'Đã tắt tự khởi động Bluetooth Agent.',
    'status_vbot_bluetooth_agent' => 'Trạng thái Bluetooth Agent', 'logs_vbot_bluetooth_agent' => 'Logs Bluetooth Agent',
    'start_bluealsa' => 'Đã chạy BlueALSA.', 'stop_bluealsa' => 'Đã dừng BlueALSA.',
    'restart_bluealsa' => 'Đã khởi động lại BlueALSA.', 'enable_bluealsa' => 'Đã bật tự khởi động BlueALSA.',
    'disabled_bluealsa' => 'Đã tắt tự khởi động BlueALSA.', 'status_bluealsa' => 'Trạng thái BlueALSA',
    'logs_bluealsa' => 'Logs BlueALSA',
    'start_airplay' => 'Đã chạy AirPlay.', 'stop_airplay' => 'Đã dừng AirPlay.',
    'restart_airplay' => 'Đã khởi động lại AirPlay.', 'enable_airplay' => 'Đã bật tự khởi động AirPlay.',
    'disabled_airplay' => 'Đã tắt tự khởi động AirPlay.', 'logs_airplay' => 'Logs AirPlay',
    'status_airplay' => 'Trạng thái AirPlay', 'version_airplay' => 'Phiên bản AirPlay',
    'cloudflared_tunnel_start' => 'Đã chạy Cloudflare Tunnel.', 'cloudflared_tunnel_stop' => 'Đã dừng Cloudflare Tunnel.',
    'cloudflared_tunnel_enable' => 'Đã bật tự khởi động Cloudflare Tunnel.',
    'cloudflared_tunnel_disable' => 'Đã tắt tự khởi động Cloudflare Tunnel.',
    'cloudflared_tunnel_status' => 'Trạng thái Cloudflare Tunnel', 'cloudflared_tunnel_list' => 'Danh sách Cloudflare Tunnel',
    'save_asound_to_alsamixer' => 'Đã lưu cấu hình ALSA và WM8960.',
    'alsamixer_asound_to_alsamixer' => 'Đã khôi phục cấu hình WM8960 mặc định.',
    'update_btwifiset_py' => 'Đã cập nhật btwifiset.py.', 'fix_airplay_services' => 'Đã khôi phục service AirPlay.',
    'install_bluetooth_agent_py' => 'Đã cài bluetooth_agent.py.', 'install_bthelper' => 'Đã cài bthelper.',
    'install_bluealsa' => 'Đã cài service BlueALSA.',
    'install_bluetooth_agent_service' => 'Đã cài service Bluetooth Agent.',
    'install_bluetooth_config_main' => 'Đã chạy trình cài đặt cấu hình Bluetooth.',
    'check_version_picovoice_porcupine' => 'Phiên bản Picovoice và Porcupine',
    'list_time_zones' => 'Danh sách múi giờ', 'check_time_zones' => 'Thông tin múi giờ hiện tại',
    'fix_time_zones' => 'Đã khôi phục cấu hình đồng bộ thời gian.',
    'config_auto' => 'Đã cài cấu hình tự khởi động VBot.',
    'auto_wifi_manager_only' => 'Đã cài Wi-Fi Manager.',
    'auto_wifi_manager_and_speaker_ip' => 'Đã cài Wi-Fi Manager và chức năng đọc địa chỉ IP.',
    'enabled_vbot_api_external' => 'Đã bật cấu hình WebUI External. Hãy restart Apache2 để áp dụng.',
    'disable_vbot_api_external' => 'Đã tắt cấu hình WebUI External. Hãy restart Apache2 để áp dụng.',
    'install_picovoice' => 'Đã cài đặt thư viện Picovoice.',
    'install_porcupine' => 'Đã cài đặt model Porcupine.',
    'set_time_zones' => 'Đã thiết lập múi giờ hệ thống.',
    'submit_rename_airplay' => 'Đã đổi tên AirPlay.',
];

if ($action === 'chmod_vbot' || $action === 'owner_vbot') {
    $targets = array_values(array_unique([
        rtrim($VBot_Offline, '/'),
        rtrim($HTML_VBot_Offline, '/'),
    ]));
    foreach ($targets as $target) {
        if ($target === '') {
            error_log('Command AJAX '.$action.' failed: empty target path');
            vbotApiJsonResponse([
                'success' => false,
                'status' => 'error',
                'message' => 'Đường dẫn thao tác quyền file không hợp lệ.'
            ], 500);
        }
        if ($action === 'chmod_vbot') {
            $systemCommand = 'sudo chmod -R 0777 -- '.escapeshellarg($target);
        } else {
            $owner = escapeshellarg($ssh_user.':'.$ssh_user);
            $systemCommand = 'sudo chown -R '.$owner.' -- '.escapeshellarg($target);
        }
        $commands[] = ['command' => $systemCommand, 'label' => $target];
    }
} elseif ($action === 'fix_asound_airplay') {
    $imageInfoPath = '/os_image_created.txt';
    if (!is_file($imageInfoPath)) {
        error_log('Command AJAX fix_asound_airplay failed: missing '.$imageInfoPath);
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Không tìm thấy file nhận diện OS: '.$imageInfoPath
        ], 500);
    }
    $imageInfo = @file_get_contents($imageInfoPath);
    if ($imageInfo === false) {
        error_log('Command AJAX fix_asound_airplay failed: cannot read '.$imageInfoPath);
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Không thể đọc file nhận diện OS.'
        ], 500);
    }
    $configName = stripos($imageInfo, 'i2s') !== false
        ? 'default_i2s_asound.conf'
        : 'default_wm8960_asound.conf';
    $source = rtrim($VBot_Offline, '/').'/resource/asound_conf/'.$configName;
    if (!is_file($source)) {
        error_log('Command AJAX fix_asound_airplay failed: missing source '.$source);
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Không tìm thấy file cấu hình âm thanh nguồn: '.$configName
        ], 500);
    }

    $target = '/etc/asound.conf';
    $backup = '/etc/asound.conf.vbot-backup-'.str_replace('.', '-', uniqid('', true));
    $sourceArg = escapeshellarg($source);
    $targetArg = escapeshellarg($target);
    $backupArg = escapeshellarg($backup);
    $restoreCommand = '(had_backup=0; '
        .'if sudo test -e '.$targetArg.'; then sudo cp -a -- '.$targetArg.' '.$backupArg.' || exit 1; had_backup=1; fi; '
        .'result=0; sudo cp -- '.$sourceArg.' '.$targetArg
        .' && sudo cmp -s -- '.$sourceArg.' '.$targetArg
        .' && sudo systemctl restart alsa-state.service || result=$?; '
        .'if [ "$result" -ne 0 ]; then '
        .'if [ "$had_backup" -eq 1 ]; then sudo cp -a -- '.$backupArg.' '.$targetArg.'; else sudo rm -f -- '.$targetArg.'; fi; '
        .'sudo systemctl restart alsa-state.service >/dev/null 2>&1; fi; '
        .'sudo rm -f -- '.$backupArg.'; exit "$result")';
    $commands[] = ['command' => $restoreCommand, 'label' => $target];
} elseif ($action === 'Stop_Service_Unnecessary_Processes') {
    $scriptPath = rtrim($VBot_Offline, '/').'/resource/Stop_Unnecessary_Processes.sh';
    if (!is_file($scriptPath)) {
        error_log('Command AJAX Stop_Service_Unnecessary_Processes failed: missing '.$scriptPath);
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Không tìm thấy script tắt service không cần thiết.'
        ], 500);
    }
    $scriptArg = escapeshellarg($scriptPath);
    $jobSuffix = str_replace('.', '-', uniqid('', true));
    $commands[] = [
        'command' => 'sudo dos2unix -- '.$scriptArg
            .' && sudo systemd-run --quiet --on-active=3s --unit=vbot-stop-unnecessary-'.$jobSuffix
            .' /bin/bash '.$scriptArg,
        'label' => $scriptPath,
    ];
} elseif ($action === 'config_auto') {
    if (!preg_match('/^[a-z_][a-z0-9_-]*[$]?$/i', (string) $ssh_user)) {
        vbotApiJsonResponse(['success' => false, 'status' => 'error', 'message' => 'Tên người dùng SSH không hợp lệ.'], 500);
    }
    $serviceContent = "[Unit]\nDescription=VBot_Offline\n\n[Service]\n"
        .'ExecStart=/usr/bin/python3.9 '.rtrim($VBot_Offline, '/')."/Start.py\n"
        .'WorkingDirectory='.rtrim($VBot_Offline, '/')."/\nRestart=always\n\n[Install]\nWantedBy=default.target\n";
    $source = rtrim($VBot_Offline, '/').'/resource/VBot_Offline.service';
    $userDir = '/home/'.$ssh_user.'/.config/systemd/user';
    $commands[] = [
        'command' => 'printf %s '.escapeshellarg($serviceContent).' | sudo tee '.escapeshellarg($source).' >/dev/null'
            .' && sudo chmod 0777 '.escapeshellarg($source)
            .' && mkdir -p '.escapeshellarg($userDir.'/default.target.wants')
            .' && cp -- '.escapeshellarg($source).' '.escapeshellarg($userDir.'/VBot_Offline.service')
            .' && ln -sfn '.escapeshellarg($userDir.'/VBot_Offline.service').' '.escapeshellarg($userDir.'/default.target.wants/VBot_Offline.service')
            .' && systemctl --user daemon-reload',
        'label' => $source,
    ];
} elseif ($action === 'auto_wifi_manager_only' || $action === 'auto_wifi_manager_and_speaker_ip') {
    $resourceRoot = rtrim($VBot_Offline, '/').'/resource/wifi_manager';
    $wifiScript = $action === 'auto_wifi_manager_only'
        ? $resourceRoot.'/start-wifi-connect_wifi_only.sh'
        : $resourceRoot.'/start-wifi-connect.sh';
    $wifiService = $resourceRoot.'/wifi-connect.service';
    $systemCommand = 'test -f '.escapeshellarg($wifiScript).' && test -f '.escapeshellarg($wifiService)
        .' && cp -- '.escapeshellarg($wifiScript).' /home/pi/start-wifi-connect.sh'
        .' && sudo cp -- '.escapeshellarg($wifiService).' /etc/systemd/system/wifi-connect.service';
    if ($action === 'auto_wifi_manager_and_speaker_ip') {
        $ipScript = $resourceRoot.'/_VBot_IP.py';
        $systemCommand .= ' && test -f '.escapeshellarg($ipScript).' && sudo cp -- '.escapeshellarg($ipScript).' /home/pi/_VBot_IP.py';
    }
    $systemCommand .= ' && dos2unix /home/pi/start-wifi-connect.sh && sudo systemctl daemon-reload'
        .' && sudo systemctl enable wifi-connect.service && sudo systemctl restart wifi-connect.service';
    $commands[] = ['command' => $systemCommand, 'label' => $action];
} elseif ($action === 'enabled_vbot_api_external' || $action === 'disable_vbot_api_external') {
    $apacheConfig = '/etc/apache2/sites-available/000-default.conf';
    $configContent = @file_get_contents($apacheConfig);
    if ($configContent === false || !preg_match('/<VirtualHost\s*\*:80\s*>\s*(.*?)\s*<\/VirtualHost>/s', $configContent, $matches)) {
        error_log('Command AJAX '.$action.' failed: cannot parse '.$apacheConfig);
        vbotApiJsonResponse(['success' => false, 'status' => 'error', 'message' => 'Không thể đọc khối VirtualHost *:80.'], 500);
    }
    $vhostContent = preg_replace('/^\s*#?\s*ProxyPass(?:Reverse)?\s+\/vbot_api_external\/.*$/m', '', $matches[1]);
    if ($action === 'enabled_vbot_api_external') {
        $vhostContent = rtrim($vhostContent)."\n    ProxyPass /vbot_api_external/ http://localhost:".$Port_API."/"
            ."\n    ProxyPassReverse /vbot_api_external/ http://localhost:".$Port_API."/\n";
    }
    $newConfig = preg_replace(
        '/<VirtualHost\s*\*:80\s*>\s*.*?\s*<\/VirtualHost>/s',
        "<VirtualHost *:80>\n".$vhostContent."\n</VirtualHost>",
        $configContent,
        1
    );
    $tempConfig = '/tmp/vbot-apache-'.str_replace('.', '-', uniqid('', true)).'.conf';
    if (@file_put_contents($tempConfig, $newConfig) === false) {
        vbotApiJsonResponse(['success' => false, 'status' => 'error', 'message' => 'Không thể tạo file cấu hình Apache tạm.'], 500);
    }
    $backupConfig = $apacheConfig.'.vbot-backup';
    $systemCommand = 'sudo cp -a -- '.escapeshellarg($apacheConfig).' '.escapeshellarg($backupConfig)
        .' && sudo cp -- '.escapeshellarg($tempConfig).' '.escapeshellarg($apacheConfig)
        .' && sudo a2enmod proxy proxy_http && sudo apache2ctl configtest'
        .' || { result=$?; sudo cp -a -- '.escapeshellarg($backupConfig).' '.escapeshellarg($apacheConfig).'; exit "$result"; }';
    $commands[] = [
        'command' => '(('.$systemCommand.'); result=$?; rm -f -- '.escapeshellarg($tempConfig).'; exit "$result")',
        'label' => $apacheConfig,
    ];
} elseif ($action === 'install_picovoice' || $action === 'install_porcupine') {
    $parameterName = $action === 'install_picovoice' ? 'version_picovoice' : 'version_porcupine';
    $version = isset($_POST[$parameterName]) ? trim((string) $_POST[$parameterName]) : '';
    if (!preg_match('/^\d+(?:\.\d+){1,3}$/', $version)) {
        vbotApiJsonResponse(['success' => false, 'status' => 'error', 'message' => 'Phiên bản được chọn không hợp lệ.'], 400);
    }
    if ($action === 'install_picovoice') {
        $commands[] = ['command' => 'pip install '.escapeshellarg('picovoice=='.$version), 'label' => 'picovoice '.$version];
    } else {
        $destination = rtrim($VBot_Offline, '/').'/resource/picovoice/library';
        $url = 'https://github.com/Picovoice/porcupine/archive/refs/tags/v'.$version.'.zip';
        $archiveRoot = 'porcupine-'.$version.'/lib/common/';
        $command = 'tmp_file=$(mktemp) && curl -fL -- '.escapeshellarg($url).' -o "$tmp_file"'
            .' && mkdir -p '.escapeshellarg($destination)
            .' && unzip -p "$tmp_file" '.escapeshellarg($archiveRoot.'porcupine_params.pv').' > '.escapeshellarg($destination.'/porcupine_params.pv')
            .' && unzip -p "$tmp_file" '.escapeshellarg($archiveRoot.'porcupine_params_vn.pv').' > '.escapeshellarg($destination.'/porcupine_params_vn.pv')
            .' && chmod 0777 '.escapeshellarg($destination.'/porcupine_params.pv').' '.escapeshellarg($destination.'/porcupine_params_vn.pv')
            .'; result=$?; rm -f "$tmp_file"; exit "$result"';
        $commands[] = ['command' => '('.$command.')', 'label' => 'porcupine '.$version];
    }
} elseif ($action === 'set_time_zones') {
    $timezoneValue = isset($_POST['timezone']) ? trim((string) $_POST['timezone']) : '';
    if (!in_array($timezoneValue, timezone_identifiers_list(), true)) {
        vbotApiJsonResponse(['success' => false, 'status' => 'error', 'message' => 'Múi giờ không hợp lệ.'], 400);
    }
    $commands[] = ['command' => 'sudo timedatectl set-timezone '.escapeshellarg($timezoneValue), 'label' => $timezoneValue];
} elseif ($action === 'submit_rename_airplay') {
    $airplayName = isset($_POST['airplay_name']) ? trim((string) $_POST['airplay_name']) : '';
    $airplayName = preg_replace('/[\/\\\'"`;|&<>\r\n]/u', '', $airplayName);
    $airplayName = preg_replace('/\s+/u', ' ', trim((string) $airplayName));
    $airplayNameLength = function_exists('mb_strlen') ? mb_strlen($airplayName, 'UTF-8') : strlen($airplayName);
    if ($airplayName === '' || $airplayNameLength > 80) {
        vbotApiJsonResponse(['success' => false, 'status' => 'error', 'message' => 'Tên AirPlay không hợp lệ hoặc dài quá 80 ký tự.'], 400);
    }
    $sedName = str_replace(['\\', '&', '|'], ['\\\\', '\\&', '\\|'], $airplayName);
    $sedExpression = 's|^[[:space:]]*\(//[[:space:]]*\)\?name[[:space:]]*=.*|        name = "'.$sedName.'";|g';
    $commands[] = [
        'command' => 'sudo sed -i '.escapeshellarg($sedExpression).' /etc/shairport-sync.conf'
            .' && sudo systemctl restart shairport-sync.service',
        'label' => 'AirPlay: '.$airplayName,
    ];
} else {
    $jobSuffix = str_replace('.', '-', uniqid('', true));
    $serviceCommands = [
        //Chạy trễ để request JSON hiện tại kịp trả về trước khi Apache dừng.
        'apache_restart' => 'sudo systemd-run --quiet --on-active=2s --unit=vbot-apache-restart-'.$jobSuffix.' /bin/systemctl restart apache2.service',
        'restart_alsa' => 'sudo systemctl restart alsa-state.service',
        'reload_services' => 'sudo systemctl daemon-reload',
        'serial_getty_ttyS0_start' => 'sudo systemctl start serial-getty@ttyS0.service',
        'serial_getty_ttyS0_stop' => 'sudo systemctl stop serial-getty@ttyS0.service',
        'serial_getty_ttyS0_enable' => 'sudo systemctl enable serial-getty@ttyS0.service',
        'serial_getty_ttyS0_disable' => 'sudo systemctl disable serial-getty@ttyS0.service',
        //Chạy trễ để phản hồi JSON hoàn tất trước khi hệ thống ngắt kết nối.
        'reboot_os' => 'sudo systemd-run --quiet --on-active=3s --unit=vbot-system-reboot-'.$jobSuffix.' /bin/systemctl reboot',
        'ifconfig_os' => 'ifconfig',
        'lscpu_os' => 'lscpu',
        'hostnamectl_os' => 'hostnamectl',
        'kiem_tra_bo_nho' => 'df -hm',
        'kiem_tra_dung_luong' => 'free -mh',
        'logs_apache2' => 'tail -n 500 -- /var/log/apache2/error.log',
        'list_systemctl_enabled' => 'systemctl list-unit-files --state=enabled --no-pager --no-legend',
        'os_image_created' => "if [ -f /os_image_created.txt ]; then cat /os_image_created.txt; else printf 'Không lấy được thông tin phiên bản OS IMG'; fi",
        'sudo_alsactl_store' => 'sudo alsactl store',
        'pip_show_all_lib' => 'pip list',
        'pvporcupine_info' => 'pip show pvporcupine',
        'picovoice_info' => 'pip show picovoice',
        'alsamixer_soundcard_start' => 'sudo systemctl start wm8960-soundcard.service',
        'alsamixer_soundcard_stop' => 'sudo systemctl stop wm8960-soundcard.service',
        'alsamixer_soundcard_enable' => 'sudo systemctl enable /usr/src/wm8960-soundcard-1.0/wm8960-soundcard.service',
        'alsamixer_soundcard_disable' => 'sudo systemctl disable wm8960-soundcard.service',
        //systemctl status trả mã 3 khi service đang dừng; đây vẫn là dữ liệu hợp lệ để hiển thị.
        'alsamixer_soundcard_status' => '(sudo systemctl status wm8960-soundcard.service --no-pager --full; status=$?; '
            .'if [ "$status" -eq 4 ]; then exit 4; fi; exit 0)',
        'auto_start' => 'systemctl --user start VBot_Offline.service',
        'auto_stop' => 'systemctl --user stop VBot_Offline.service',
        'auto_restart' => 'systemctl --user restart VBot_Offline.service',
        'auto_enable' => 'systemctl --user enable VBot_Offline.service',
        'auto_disable' => 'systemctl --user disable VBot_Offline.service',
        'auto_status' => '(systemctl --user status VBot_Offline.service --no-pager --full; status=$?; if [ "$status" -eq 4 ]; then exit 4; fi; exit 0)',
        'restart_auto_wifi' => 'sudo systemctl restart wifi-connect.service',
        'enable_auto_wifi' => 'sudo systemctl enable wifi-connect.service',
        'logs_auto_wifi' => 'journalctl -u wifi-connect.service -n 500 --no-pager',
        'status_auto_wifi' => '(sudo systemctl status wifi-connect.service --no-pager --full; status=$?; if [ "$status" -eq 4 ]; then exit 4; fi; exit 0)',
        'start_btwifiset' => 'sudo systemctl start btwifiset.service', 'stop_btwifiset' => 'sudo systemctl stop btwifiset.service',
        'restart_btwifiset' => 'sudo systemctl restart btwifiset.service', 'enable_btwifiset' => 'sudo systemctl enable btwifiset.service',
        'disabled_btwifiset' => 'sudo systemctl disable btwifiset.service',
        'logs_btwifiset' => 'journalctl -u btwifiset.service -n 500 --no-pager',
        'status_btwifiset' => '(sudo systemctl status btwifiset.service --no-pager --full; status=$?; if [ "$status" -eq 4 ]; then exit 4; fi; exit 0)',
        'pass_crypto_btwifiset' => 'cat /usr/local/btwifiset/crypto',
        'start_vbot_bluetooth_agent' => 'sudo systemctl start vbot-bluetooth-agent.service',
        'stop_vbot_bluetooth_agent' => 'sudo systemctl stop vbot-bluetooth-agent.service',
        'restart_vbot_bluetooth_agent' => 'sudo systemctl restart vbot-bluetooth-agent.service',
        'enable_vbot_bluetooth_agent' => 'sudo systemctl enable vbot-bluetooth-agent.service',
        'disabled_vbot_bluetooth_agent' => 'sudo systemctl disable vbot-bluetooth-agent.service',
        'logs_vbot_bluetooth_agent' => 'journalctl -u vbot-bluetooth-agent.service -n 500 --no-pager',
        'status_vbot_bluetooth_agent' => '(sudo systemctl status vbot-bluetooth-agent.service --no-pager --full; status=$?; if [ "$status" -eq 4 ]; then exit 4; fi; exit 0)',
        'start_bluealsa' => 'sudo systemctl start bluealsa.service', 'stop_bluealsa' => 'sudo systemctl stop bluealsa.service',
        'restart_bluealsa' => 'sudo systemctl restart bluealsa.service', 'enable_bluealsa' => 'sudo systemctl enable bluealsa.service',
        'disabled_bluealsa' => 'sudo systemctl disable bluealsa.service',
        'logs_bluealsa' => 'journalctl -u bluealsa.service -n 500 --no-pager',
        'status_bluealsa' => '(sudo systemctl status bluealsa.service --no-pager --full; status=$?; if [ "$status" -eq 4 ]; then exit 4; fi; exit 0)',
        'start_airplay' => 'sudo systemctl start shairport-sync.service', 'stop_airplay' => 'sudo systemctl stop shairport-sync.service',
        'restart_airplay' => 'sudo systemctl restart shairport-sync.service', 'enable_airplay' => 'sudo systemctl enable shairport-sync.service',
        'disabled_airplay' => 'sudo systemctl disable shairport-sync.service',
        'logs_airplay' => 'journalctl -u shairport-sync.service -n 500 --no-pager',
        'status_airplay' => '(sudo systemctl status shairport-sync.service --no-pager --full; status=$?; if [ "$status" -eq 4 ]; then exit 4; fi; exit 0)',
        'version_airplay' => 'shairport-sync -V',
        'cloudflared_tunnel_start' => 'sudo systemctl start cloudflared.service',
        'cloudflared_tunnel_stop' => 'sudo systemctl stop cloudflared.service',
        'cloudflared_tunnel_enable' => 'sudo systemctl enable cloudflared.service',
        'cloudflared_tunnel_disable' => 'sudo systemctl disable cloudflared.service',
        'cloudflared_tunnel_status' => '(systemctl status cloudflared.service --no-pager --full; status=$?; if [ "$status" -eq 4 ]; then exit 4; fi; exit 0)',
        'cloudflared_tunnel_list' => 'cloudflared tunnel list',
        'save_asound_to_alsamixer' => 'sudo alsactl store && sudo cp -- /var/lib/alsa/asound.state /etc/wm8960-soundcard/wm8960_asound.state',
        'alsamixer_asound_to_alsamixer' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/wm8960_asound_default.state')
            .' && sudo cp -- '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/wm8960_asound_default.state').' /etc/wm8960-soundcard/wm8960_asound.state',
        'update_btwifiset_py' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/set_wifi_via_ble/btwifiset.py')
            .' && sudo cp -- '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/set_wifi_via_ble/btwifiset.py').' /usr/local/btwifiset/btwifiset.py',
        'fix_airplay_services' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/airplay/shairport-sync.service')
            .' && sudo cp -- '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/airplay/shairport-sync.service').' /lib/systemd/system/shairport-sync.service'
            .' && sudo systemctl daemon-reload && sudo systemctl restart shairport-sync.service',
        'install_bluetooth_agent_py' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/bluetooth_agent.py')
            .' && sudo cp -- '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/bluetooth_agent.py').' /usr/local/bin/bluetooth_agent.py'
            .' && sudo chmod 0777 /usr/local/bin/bluetooth_agent.py',
        'install_bthelper' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/bthelper')
            .' && sudo cp -- '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/bthelper').' /usr/bin/bthelper',
        'install_bluealsa' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/bluealsa.service')
            .' && sudo cp -- '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/bluealsa.service').' /etc/systemd/system/bluealsa.service'
            .' && sudo systemctl daemon-reload && sudo systemctl enable bluealsa.service && sudo systemctl start bluealsa.service',
        'install_bluetooth_agent_service' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/vbot-bluetooth-agent.service')
            .' && sudo cp -- '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/vbot-bluetooth-agent.service').' /etc/systemd/system/vbot-bluetooth-agent.service'
            .' && sudo systemctl daemon-reload && sudo systemctl enable vbot-bluetooth-agent.service && sudo systemctl start vbot-bluetooth-agent.service',
        'install_bluetooth_config_main' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/install_config_bluetooth.sh')
            .' && sudo bash '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/bluetooth/install_config_bluetooth.sh'),
        'check_version_picovoice_porcupine' => 'pip show picovoice pvporcupine',
        'list_time_zones' => 'timedatectl list-timezones', 'check_time_zones' => 'timedatectl',
        'fix_time_zones' => 'sudo test -f '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/timesyncd.conf')
            .' && sudo cp -- '.escapeshellarg(rtrim($VBot_Offline, '/').'/resource/timesyncd.conf').' /etc/systemd/timesyncd.conf'
            .' && sudo systemctl restart systemd-timesyncd.service && sudo timedatectl set-ntp true',
    ];
    $commands[] = ['command' => $serviceCommands[$action], 'label' => $action];
}

foreach ($commands as $commandItem) {
    $systemCommand = $commandItem['command'];
    $commandLabel = $commandItem['label'];
    $commandLog .= ($commandLog === '' ? '' : "\n\n").$GET_current_USER.'@'.$HostName.':~ $ '.$systemCommand;
    $command = $systemCommand." 2>&1; printf '\\n__VBOT_EXIT__%s' $?";
    $stream = @ssh2_exec($connection, $command);
    if (!$stream) {
        error_log('Command AJAX '.$action.' failed: cannot execute '.$commandLabel);
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Không thể thực thi thao tác hệ thống.'
        ], 500);
    }

    stream_set_blocking($stream, true);
    $commandOutput = stream_get_contents($stream);
    fclose($stream);
    if (!preg_match('/__VBOT_EXIT__(\d+)\s*$/', (string) $commandOutput, $exitMatch)) {
        error_log('Command AJAX '.$action.' failed: missing exit status for '.$commandLabel.' | '.trim((string) $commandOutput));
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Không xác định được kết quả thao tác hệ thống.'
        ], 500);
    }

    $exitCode = (int) $exitMatch[1];
    $cleanOutput = trim(preg_replace('/\n?__VBOT_EXIT__\d+\s*$/', '', (string) $commandOutput));
    $commandLog .= "\n".($cleanOutput !== '' ? $cleanOutput : '[Khong co du lieu tra ve]');
    if ($exitCode !== 0) {
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Thao tac that bai: '.($cleanOutput !== '' ? $cleanOutput : 'lenh tra ve ma '.$exitCode),
            'command_log' => $commandLog,
            'data' => $commandLog,
        ], 500);
        error_log('Command AJAX '.$action.' failed for '.$commandLabel.' (exit '.$exitCode.'): '.$cleanOutput);
        vbotApiJsonResponse([
            'success' => false,
            'status' => 'error',
            'message' => 'Thao tác thất bại: '.($cleanOutput !== '' ? $cleanOutput : 'lệnh trả về mã '.$exitCode)
        ], 500);
    }
    if (in_array($action, $outputActions, true)) {
        $responseOutput .= ($responseOutput === '' ? '' : "\n").$cleanOutput;
        if (strlen($responseOutput) > 200000) {
            $responseOutput = "[Đầu dữ liệu đã được lược bớt vì vượt quá giới hạn 200 KB]\n\n"
                .substr($responseOutput, -200000);
        }
    }
}

$responsePayload = [
    'success' => true,
    'status' => 'success',
    'message' => $successMessages[$action],
    'command_log' => $commandLog
];
if (in_array($action, $outputActions, true)) {
    $responsePayload['display'] = 'modal';
    $responsePayload['data'] = $commandLog;
}
vbotApiJsonResponse($responsePayload);
