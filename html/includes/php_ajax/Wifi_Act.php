<?php
#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['GET', 'POST']);
include '../../Configuration.php';
putenv("LANG=C.UTF-8");
putenv("LC_ALL=C.UTF-8");
setlocale(LC_CTYPE, 'C.UTF-8', 'en_US.UTF-8', 'UTF-8');
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

function vbotWifiValidName($value)
{
    return is_string($value)
        && $value !== ''
        && strlen($value) <= 100
        && !preg_match('/[\x00-\x1F\x7F]/', $value);
}

function vbotWifiValidUuid($value)
{
    return is_string($value)
        && preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $value);
}

function vbotNmcliSplitTerseLine($line)
{
    $parts = preg_split('/(?<!\\\\):/', rtrim($line, "\r\n"));
    return array_map(function ($part) {
        return str_replace(['\\:', '\\\\'], [':', '\\'], $part);
    }, $parts ?: []);
}

//Kiểm tra thông tin mạng
function networkInfo($connectionName = null) {
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    if (empty($connectionName)) {
        return [
            "ipMode" => "N/A",
            "ip" => "N/A",
            "dns" => "N/A",
            "dnsSource" => "N/A",
            "gateway" => "N/A",
            "gatewaySource" => "N/A"
        ];
    }
    $raw = shell_exec('nmcli connection show '.escapeshellarg($connectionName));
    $method = "";
    $ip = "";
    $gateway = "";
    $dnsList = [];
    $ignoreAutoDNS = "";
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if (strpos($line, "ipv4.method:") === 0) {
            $method = trim(substr($line, strlen("ipv4.method:")));
        }
        if (strpos($line, "IP4.ADDRESS[1]:") === 0) {
            $ip = trim(substr($line, strlen("IP4.ADDRESS[1]:")));
        }
        if (strpos($line, "IP4.GATEWAY:") === 0) {
            $gateway = trim(substr($line, strlen("IP4.GATEWAY:")));
        }
        if (strpos($line, "IP4.DNS[") === 0) {
            $dns = trim(substr($line, strpos($line, ":") + 1));
            if (!empty($dns)) $dnsList[] = $dns;
        }
        if (strpos($line, "ipv4.ignore-auto-dns:") === 0) {
            $ignoreAutoDNS = trim(substr($line, strlen("ipv4.ignore-auto-dns:")));
        }
    }
	$ipMode = ($method === "manual") ? "Đang Dùng IP Tĩnh" : "IP Động Được DHCP Modem, Route Cấp Phát";
	$ipDisplay = !empty($ip) ? $ip : "N/A";                     // IP
	$dnsDisplay = !empty($dnsList) ? implode(", ", $dnsList) : "N/A";  // DNS
	$dnsSource = !empty($dnsList) ? (($ignoreAutoDNS === "yes") ? "DNS Được Cấu Hình Thủ Công" : "DNS Được Route, Modem Cấp Phát") : "N/A"; // nguồn DNS
	$gatewayDisplay = !empty($gateway) ? $gateway : "N/A";     // Gateway
	$gatewaySource = !empty($gateway) ? (($method === "manual") ? "Gateway Được Cấu Hình Thủ Công" : "Gateway Được Route, Modem Cấp Phát") : "N/A"; // nguồn Gateway
    return [
        "ipMode" => $ipMode,
        "ip" => $ipDisplay,
        "dns" => $dnsDisplay,
        "dnsSource" => $dnsSource,
        "gateway" => $gatewayDisplay,
        "gatewaySource" => $gatewaySource
    ];
}

//Xóa Wifi
if (isset($_POST['Delete_Wifi'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    if (isset($_POST['action']) && $_POST['action'] == 'delete_wifi' && isset($_POST['wifiName'])) {
        $wifiName = trim($_POST['wifiName']);
        $wifiUuid = trim($_POST['uuid'] ?? '');
        if (!vbotWifiValidName($wifiName) || !vbotWifiValidUuid($wifiUuid)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên hoặc UUID kết nối WiFi không hợp lệ.'], 400);
        }
        $safeWifiName = htmlspecialchars($wifiName, ENT_QUOTES, 'UTF-8');
        $activeUuids = (string) shell_exec('LANG=C.UTF-8 LC_ALL=C.UTF-8 nmcli -t -f UUID connection show --active');
        $isActive = in_array(strtolower($wifiUuid), array_map('strtolower', array_filter(array_map('trim', explode("\n", $activeUuids)))), true);
        if (!$isActive) {
            $connection = ssh2_connect($ssh_host, $ssh_port);
            if (!$connection) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH.'], 502);
            }
            if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Xác thực SSH thất bại.'], 401);
            }
            $stream = ssh2_exec($connection, 'sudo nmcli connection delete uuid ' . escapeshellarg($wifiUuid));
            if (!$stream) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh xóa WiFi.'], 502);
            }
            stream_set_blocking($stream, true);
            $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
            $stream_err = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
            $result = stream_get_contents($stream_out);
            $resultError = trim(stream_get_contents($stream_err));
            if ($resultError !== '') {
                error_log('Delete WiFi failed: '.$resultError);
                vbotApiJsonResponse(['success' => false, 'message' => 'Không thể xóa kết nối WiFi.'], 502);
            }
            vbotApiJsonResponse(['success' => true, 'message' => htmlspecialchars(trim($result), ENT_QUOTES, 'UTF-8')]);
        } else {
            vbotApiJsonResponse(['success' => false, 'message' => 'Wifi ' . $safeWifiName . ' đang được kết nối, Không cho phép xóa'], 409);
        }
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'Lỗi khi xóa WiFi: Tham số không hợp lệ.'], 400);
    }
}

//Kết Nối Wifi
if (isset($_POST['Connect_Wifi'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if (isset($_POST['ssid']) && isset($_POST['password'])) {
                $ssid = trim($_POST['ssid']);
                $wifiUuid = trim($_POST['uuid'] ?? '');
            $password = $_POST['password'];
            if (!vbotWifiValidName($ssid) || !is_string($password) || strlen($password) > 63 || preg_match('/[\x00-\x1F\x7F]/', $password)) {
                vbotApiJsonResponse(['success' => false, 'message' => 'SSID hoặc mật khẩu WiFi không hợp lệ.'], 400);
            }
            $connection = ssh2_connect($ssh_host, $ssh_port);
            if (!$connection) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH.'], 502);
            }
            if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Xác thực SSH thất bại.'], 401);
            }
            if ($action == 'connect_wifi') {
                if (!vbotWifiValidUuid($wifiUuid)) {
                    vbotApiJsonResponse(['success' => false, 'message' => 'UUID kết nối WiFi không hợp lệ.'], 400);
                }
                $command = 'sudo nmcli connection up uuid ' . escapeshellarg($wifiUuid);
            } elseif ($action == 'connect_and_save_wifi') {
                if (!empty($password)) {
                    $command = 'sudo nmcli device wifi connect ' . escapeshellarg($ssid) . ' password ' . escapeshellarg($password);
                } else {
                    $command = 'sudo nmcli device wifi connect ' . escapeshellarg($ssid);
                }
            } else {
                vbotApiJsonResponse(['success' => false, 'message' => 'Hành động không hợp lệ.'], 400);
            }
            $stream = ssh2_exec($connection, $command);
            if (!$stream) {
                vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh kết nối WiFi.'], 502);
            }
            stream_set_blocking($stream, true);
            $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
            $stream_err = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
            $result = stream_get_contents($stream_out);
            $resultError = trim(stream_get_contents($stream_err));
            if ($resultError !== '') {
                error_log('Connect WiFi failed: '.$resultError);
                vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới WiFi đã chọn.'], 502);
            }
            vbotApiJsonResponse(['success' => true, 'message' => htmlspecialchars(trim($result), ENT_QUOTES, 'UTF-8')]);
        } else {
            vbotApiJsonResponse(['success' => false, 'message' => 'SSID hoặc mật khẩu không được cung cấp.'], 400);
        }
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'Yêu cầu không hợp lệ.'], 400);
    }
}

//Đặt lại cấu hình Wifi
if (isset($_POST['Reset_Wifi'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối đến server SSH.',
            'data' => null
        ], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Xác thực SSH thất bại.',
            'data' => null
        ], 401);
    }
    $resetWifiScript = $VBot_Offline . 'resource/wifi_manager/reset_wifi.sh';
    $command = 'dos2unix ' . escapeshellarg($resetWifiScript) . ' && sudo ' . escapeshellarg($resetWifiScript);
    $stream = ssh2_exec($connection, $command);
    if (!$stream) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi chương trình đặt lại WiFi.', 'data' => null], 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $stream_err = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    $result = stream_get_contents($stream_out);
    $resultError = trim(stream_get_contents($stream_err));
    if ($resultError !== '') {
        error_log('Reset WiFi failed: '.$resultError);
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể đặt lại cấu hình WiFi.', 'data' => null], 502);
    }
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Đã gửi lệnh đặt lại toàn bộ cấu hình WiFi, Hãy kiểm tra, kết nối và cấu hình với điểm truy cập Wifi được phát ra là: VBot Assistant',
        'data' => null
    ]);
}

#Quét các mạng wifi xung quanh
if (isset($_GET['Scan_Wifi_List'])) {
	putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể kết nối đến server SSH.',
            'data' => null
        ], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Xác thực SSH thất bại.',
            'data' => null
        ], 401);
    }
    $stream = ssh2_exec($connection, "sudo nmcli --escape yes -t -f SSID,BSSID,MODE,CHAN,RATE,SIGNAL,BARS,SECURITY dev wifi");
    if (!$stream) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh quét WiFi.', 'data' => null], 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $result = stream_get_contents($stream_out);
    if (!$result) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Lỗi khi lấy dữ liệu WiFi từ SSH.',
            'data' => null
        ], 502);
    }
    $lines = explode("\n", $result);
    $wifi_data = [];
    foreach ($lines as $line) {
        if (!empty($line)) {
            $parts = vbotNmcliSplitTerseLine($line);
            if (count($parts) < 8) continue;
            $bssid = $parts[1];
            $chan = $parts[3];
            $rate = $parts[4];
            $signal = $parts[5];
            $bars = $parts[6];
            $securityy = empty($parts[7]) ? "" : $parts[7];
            $Check_ssid_hidee = empty($parts[0]) ? "wifi_hidden" : $parts[0];
            $Check_ssid_hide = empty($parts[0]) ? "Mạng ẩn" : $parts[0];
            $security = empty($parts[7]) ? "Không mật khẩu" : $parts[7];
            $wifi_data[] = [
                'SSID' => $Check_ssid_hide,
                'BSSID' => $bssid,
                'Channel' => $chan,
                'Rate' => $rate,
                'Signal' => $signal,
                'Bars' => $bars,
                'Security' => $security
            ];
        }
    }
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Quét WiFi thành công.',
        'data' => $wifi_data
    ]);
}

#Lấy Mật Khẩu Wifi Đã Kết Nối
if (isset($_GET['Get_Password_Wifi'])) {
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    $response = ['success' => false, 'message' => '', 'data' => []];
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = "Không thể kết nối đến server SSH.";
        vbotApiJsonResponse($response, 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = "Xác thực SSH thất bại.";
        vbotApiJsonResponse($response, 401);
    }
    $desiredSSID = isset($_GET['ssid']) ? trim($_GET['ssid']) : '';
    $desiredUuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : '';
    if (!vbotWifiValidName($desiredSSID) || !vbotWifiValidUuid($desiredUuid)) {
        $response['message'] = "Tên hoặc UUID WiFi không hợp lệ.";
        vbotApiJsonResponse($response, 400);
    }
    $configFilePath = '/etc/NetworkManager/system-connections/';
    $stream = ssh2_exec($connection, "ls \"$configFilePath\"");
    stream_set_blocking($stream, true);
    $files = explode("\n", trim(stream_get_contents($stream)));
    foreach ($files as $file) {
        if (!empty($file)) {
            $file = trim($file, '"');
            $configFile = $configFilePath . $file;
            $stream = ssh2_exec($connection, 'sudo cat '.escapeshellarg($configFile));
            if (!$stream) {
                continue;
            }
            stream_set_blocking($stream, true);
            $configContent = stream_get_contents($stream);
            preg_match('/^ssid=(.*)$/m', $configContent, $ssidMatches);
            preg_match('/^psk=(.*)$/m', $configContent, $passwordMatches);
            preg_match('/^uuid=(.*)$/m', $configContent, $uuidMatches);
            preg_match('/^timestamp=(.*)$/m', $configContent, $timestampMatches);
            preg_match('/^seen-bssids=(.*)$/m', $configContent, $bssidMatches);
            $formattedTimestamp = !empty($timestampMatches[1]) ? date("H:i:s d-m-Y", $timestampMatches[1]) : null;
            if (!empty($uuidMatches[1]) && strcasecmp(trim($uuidMatches[1]), $desiredUuid) === 0) {
                $wifiInfo = [
                    'file' => $file,
                    'ssid' => trim($ssidMatches[1]),
                    'uuid' => !empty($uuidMatches[1]) ? trim($uuidMatches[1]) : null,
                    'timestamp' => $formattedTimestamp,
                    'seen_bssids' => !empty($bssidMatches[1]) ? rtrim(trim($bssidMatches[1]), ';') : null,
                    'password' => !empty($passwordMatches[1]) ? trim($passwordMatches[1]) : 'Không có mật khẩu'
                ];
                $response['data'][] = $wifiInfo;
            }
        }
    }
    if (!empty($response['data'])) {
        $response['success'] = true;
        $response['message'] = "Tìm thấy thông tin WiFi.";
    } else {
        $response['message'] = "Không tìm thấy WiFi phù hợp.";
    }
    vbotApiJsonResponse($response, $response['success'] ? 200 : 404);
}

#Hiển thị các mạng wifi đã kết nối
if (isset($_GET['Show_Wifi_List'])) {
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    $result = shell_exec("LANG=C.UTF-8 LC_ALL=C.UTF-8 nmcli --escape yes -t -f NAME,UUID,TYPE,DEVICE con show");
    if ($result !== null) {
        //Nếu chuỗi không phải UTF-8 thì chuyển sang UTF-8
        if (!mb_check_encoding($result, "UTF-8")) {
            $result = mb_convert_encoding($result, "UTF-8");
        }
        $savedWifiInfo = array_filter(explode("\n", trim($result)));
        $formattedWifiInfo = array_values(array_filter(array_map(function ($item) {
            $parts = vbotNmcliSplitTerseLine($item);
            $uuid = $parts[1] ?? '';
            $type = $parts[2] ?? '';
            if (!vbotWifiValidUuid($uuid) || !in_array($type, ['802-11-wireless', 'wifi'], true)) return null;
            return [
                "ssid"      => $parts[0] ?? "",
                "uuid"      => $uuid,
                "interface" => $parts[3] ?? ""
            ];
        }, $savedWifiInfo)));
        vbotApiJsonResponse([
            "success" => true,
            "message" => "Lấy danh sách WiFi thành công.",
            "data"    => $formattedWifiInfo
        ]);
    } else {

        vbotApiJsonResponse([
            "success" => false,
            "message" => "Không thể lấy danh sách WiFi.",
            "data"    => []
        ], 502);
    }
}

#kiểm tra thông tin mạng wifi đang kết nối
if (isset($_GET['Wifi_Network_Information'])) {
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
	#$wifiInfo = shell_exec('LANG=C.UTF-8 iwconfig wlan0');
	$wifiInfo = shell_exec('LANG=C.UTF-8 iwconfig wlan0 2>&1');
    #$wifiInfo = shell_exec('iwconfig wlan0');
	#$wifiInfo = iconv('ISO-8859-1', 'UTF-8//IGNORE', $wifiInfo); //Nếu tên wifi có dấu tiếng việt
    if (empty($wifiInfo)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không thể thực hiện lệnh iwconfig hoặc không có dữ liệu.',
            'data' => null
        ], 502);
    }
    $wifiData = [];
    preg_match('/ESSID:"([^"]+)"/', $wifiInfo, $essidMatches);
    preg_match('/Frequency:([\d\.]+)\sGHz/', $wifiInfo, $frequencyMatches);
    preg_match('/Access Point: ([0-9A-Fa-f:]{17})/', $wifiInfo, $accessPointMatches);
    preg_match('/Bit Rate=([\d.]+) Mb\/s/', $wifiInfo, $bitRateMatches);
    preg_match('/Tx-Power=([\d.]+) dBm/', $wifiInfo, $txPowerMatches);
    preg_match('/Retry short limit:(\d+)/', $wifiInfo, $retryLimitMatches);
    preg_match('/RTS thr:(\S+)/', $wifiInfo, $rtsThrMatches);
    preg_match('/Fragment thr:(\S+)/', $wifiInfo, $fragThrMatches);
    preg_match('/Power Management:(\S+)/', $wifiInfo, $powerMgmtMatches);
    preg_match('/Link Quality=(\d+\/\d+)/', $wifiInfo, $linkQualityMatches);
    preg_match('/Signal level=(-?\d+) dBm/', $wifiInfo, $signalLevelMatches);
    preg_match('/Rx invalid nwid:(\d+)/', $wifiInfo, $rxInvalidNwidMatches);
    preg_match('/Rx invalid crypt:(\d+)/', $wifiInfo, $rxInvalidCryptMatches);
    preg_match('/Rx invalid frag:(\d+)/', $wifiInfo, $rxInvalidFragMatches);
    preg_match('/Tx excessive retries:(\d+)/', $wifiInfo, $txExcessiveRetriesMatches);
    preg_match('/Invalid misc:(\d+)/', $wifiInfo, $invalidMiscMatches);
    preg_match('/Missed beacon:(\d+)/', $wifiInfo, $missedBeaconMatches);
    //Lưu kết quả vào mảng
    $wifiData['ESSID'] = isset($essidMatches[1]) ? $essidMatches[1] : 'N/A';
    $wifiData['Frequency'] = isset($frequencyMatches[1]) ? $frequencyMatches[1] . ' GHz' : 'N/A';
    $wifiData['Access_Point'] = isset($accessPointMatches[1]) ? $accessPointMatches[1] : 'N/A';
    $wifiData['Bit_Rate'] = isset($bitRateMatches[1]) ? $bitRateMatches[1] . ' Mb/s' : 'N/A';
    $wifiData['Tx_Power'] = isset($txPowerMatches[1]) ? $txPowerMatches[1] . ' dBm' : 'N/A';
    $wifiData['Retry_Short_Limit'] = isset($retryLimitMatches[1]) ? $retryLimitMatches[1] : 'N/A';
    $wifiData['RTS_Threshold'] = isset($rtsThrMatches[1]) ? $rtsThrMatches[1] : 'N/A';
    $wifiData['Fragment_Threshold'] = isset($fragThrMatches[1]) ? $fragThrMatches[1] : 'N/A';
    $wifiData['Power_Management'] = isset($powerMgmtMatches[1]) ? $powerMgmtMatches[1] : 'N/A';
    $wifiData['Link_Quality'] = isset($linkQualityMatches[1]) ? $linkQualityMatches[1] : 'N/A';
    $wifiData['Signal_Level'] = isset($signalLevelMatches[1]) ? $signalLevelMatches[1] . ' dBm' : 'N/A';
    $wifiData['Rx_Invalid_Nwid'] = isset($rxInvalidNwidMatches[1]) ? $rxInvalidNwidMatches[1] : 'N/A';
    $wifiData['Rx_Invalid_Crypt'] = isset($rxInvalidCryptMatches[1]) ? $rxInvalidCryptMatches[1] : 'N/A';
    $wifiData['Rx_Invalid_Frag'] = isset($rxInvalidFragMatches[1]) ? $rxInvalidFragMatches[1] : 'N/A';
    $wifiData['Tx_Excessive_Retries'] = isset($txExcessiveRetriesMatches[1]) ? $txExcessiveRetriesMatches[1] : 'N/A';
    $wifiData['Invalid_Misc'] = isset($invalidMiscMatches[1]) ? $invalidMiscMatches[1] : 'N/A';
    $wifiData['Missed_Beacon'] = isset($missedBeaconMatches[1]) ? $missedBeaconMatches[1] : 'N/A';
	$net = networkInfo(isset($essidMatches[1]) ? $essidMatches[1] : null);
	$wifiData['DHCP_Mode'] = $net['ipMode'];
	$wifiData['IP'] = $net['ip'];
	$wifiData['DNS'] =  $net['dns'];
	$wifiData['DNS_Mode'] =  $net['dnsSource'];
	$wifiData['Gateway'] = $net['gateway'];
	$wifiData['Gateway_Mode'] = $net['gatewaySource'];
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Dữ liệu đã được lấy thành công.',
        'data' => $wifiData
    ]);
}

#Đặt IP Tĩnh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_static_ip') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    $connectionName = $_POST['connected_network_name'] ?? '';
    $ip = $_POST['ip'] ?? '';
    $gateway = $_POST['gateway'] ?? '';
    $dns1 = $_POST['dns1'] ?? '8.8.8.8';
    $dns2 = $_POST['dns2'] ?? '8.8.4.4';
    if (!vbotWifiValidName($connectionName) || $ip === '' || $gateway === '') {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Thiếu tham số: Connection Name, IP hoặc Gateway'
        ], 400);
    }
    if (strpos($ip, '/') !== false) {$ip = explode('/', $ip)[0];}
    if (
        !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        || !filter_var($gateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        || !filter_var($dns1, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        || ($dns2 !== '' && !filter_var($dns2, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
    ) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Địa chỉ IP, Gateway hoặc DNS không hợp lệ'], 400);
    }
    $ip_cidr = $ip . "/24";
    $safeConnectionName = htmlspecialchars($connectionName, ENT_QUOTES, 'UTF-8');
    $dnsString = $dns1;
    if ($dns2 !== '') {$dnsString .= "," . $dns2;}
	$fullCmd = 'sudo nmcli connection modify ' . escapeshellarg($connectionName) . ' '
			 . 'ipv4.addresses ' . escapeshellarg($ip_cidr) . ' '
			 . 'ipv4.gateway ' . escapeshellarg($gateway) . ' '
			 . 'ipv4.dns ' . escapeshellarg($dnsString) . ' '
			 . 'ipv4.method manual';
	$connection = ssh2_connect($ssh_host, $ssh_port);
	if (!$connection) {
		vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH.'], 502);
	}
	if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
		vbotApiJsonResponse(['success' => false, 'message' => 'Xác thực SSH thất bại.'], 401);
	}
	$stream = ssh2_exec($connection, $fullCmd);
	if (!$stream) {
		vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh nmcli.'], 502);
	}
	stream_set_blocking($stream, true);
	$stdout = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
	$stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
	$result_out = stream_get_contents($stdout);
	$result_err = stream_get_contents($stderr);
	fclose($stdout);
	fclose($stderr);
	if (!empty($result_err)) {
		error_log('Set static IP failed: '.trim($result_err));
		vbotApiJsonResponse([
			'success' => false,
			'message' => 'Không thể áp dụng IP tĩnh.'
		], 502);
	}
	vbotApiJsonResponse([
		'success' => true,
		'message' => 'Thiết Lập IP Tĩnh Thành Công cho: <b>'.$safeConnectionName.'</b>, Bạn Hãy REBOOT - Khởi Động lại Thiết Bị Để Được Áp Dụng',
		'output' => trim($result_out)
	]);
}

//Đặt lại IP Động
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'use_dhcp_automatically') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    $connectionName = $_POST['connected_network_name'] ?? '';
    if (!vbotWifiValidName($connectionName)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Thiếu tên mạng WiFi (connectionName)'
        ], 400);
    }
    $safeConnectionName = htmlspecialchars($connectionName, ENT_QUOTES, 'UTF-8');
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối SSH'], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Xác thực SSH thất bại'], 401);
    }
    $fullCmd =
        'sudo nmcli connection modify ' . escapeshellarg($connectionName) . ' ' .
        'ipv4.method auto ' .
        'ipv4.addresses "" ' .
        'ipv4.gateway "" ' .
        'ipv4.dns "8.8.8.8 8.8.4.4" ' .
        'ipv4.ignore-auto-dns yes';
    $stream = ssh2_exec($connection, $fullCmd);
    if (!$stream) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh để chuyển sang DHCP ip động'], 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    vbotApiJsonResponse([
        'success' => true,
        'message' => "Đã chuyển sang DHCP (IP động) thành công cho: <b>$safeConnectionName</b>, Bạn Hãy REBOOT - Khởi Động lại Thiết Bị Để Được Áp Dụng",
        'output' => trim($output)
    ]);
}

#Cấu Hình DNS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_dns_only') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    $connectionName = $_POST['connection_name'] ?? '';
    $dns1 = $_POST['dns1'] ?? '8.8.8.8';
    $dns2 = $_POST['dns2'] ?? '8.8.4.4';
    if (!vbotWifiValidName($connectionName)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Thiếu Connection Name'], 400);
    }
    $safeConnectionName = htmlspecialchars($connectionName, ENT_QUOTES, 'UTF-8');
    if (
        !filter_var($dns1, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        || !filter_var($dns2, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
    ) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Địa chỉ DNS không hợp lệ'], 400);
    }
    $dnsString = $dns1 . ' ' . $dns2;
    $fullCmd =
        'sudo nmcli connection modify ' . escapeshellarg($connectionName) . ' ' .
        'ipv4.dns ' . escapeshellarg($dnsString) . ' ' .
        'ipv4.ignore-auto-dns yes';
    $conn = ssh2_connect($ssh_host, $ssh_port);
    if (!$conn) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối SSH'], 502);
    }
    if (!ssh2_auth_password($conn, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Sai Tài Khoản hoặc Mật Khẩu Đăng Nhập SSH'], 401);
    }
    $stream = ssh2_exec($conn, $fullCmd);
    if (!$stream) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh cấu hình DNS'], 502);
    }
    stream_set_blocking($stream, true);
    $result = stream_get_contents($stream);
    vbotApiJsonResponse([
        'success' => true,
        'message' => "Đã thiết lập cấu hình DNS: $dnsString cho: <b>$safeConnectionName</b><br/>Hãy REBOOT, Khởi Động Lại Hệ Thống Để Áp Dụng DNS Mới",
        'output' => trim($result)
    ]);
}

#Đặt Lại DNS SẼ DO DHCP CUNG CẤP DNS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_dns_dhcp') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    putenv("LANG=C.UTF-8");
    putenv("LC_ALL=C.UTF-8");
    $connectionName = $_POST['connection_name'] ?? '';
    if (!vbotWifiValidName($connectionName)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Thiếu tên kết nối WiFi'
        ], 400);
    }
    $safeConnectionName = htmlspecialchars($connectionName, ENT_QUOTES, 'UTF-8');
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể kết nối SSH'], 502);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Xác thực SSH thất bại'], 401);
    }
    $fullCmd = 'sudo nmcli connection modify ' . escapeshellarg($connectionName) . ' '
             . 'ipv4.dns "" '
             . 'ipv4.ignore-auto-dns no';
    $stream = ssh2_exec($connection, $fullCmd);
    if (!$stream) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể thực thi lệnh cấu hình DNS mặc định do DHCP Modem, Route cấp phát'], 502);
    }
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = stream_get_contents($stream_out);
    vbotApiJsonResponse([
        'success' => true,
        'message' => "Đã chuyển về dùng DNS mặc định từ DHCP Modem, Route cấp phát cho: <b>$safeConnectionName</b><br/>Hãy REBOOT, Khởi Động Lại Hệ Thống Để Áp Dụng DNS Mới",
        'response' => trim($output)
    ]);
}
?>
