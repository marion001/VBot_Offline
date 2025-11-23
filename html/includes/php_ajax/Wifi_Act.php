<?php
#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include '../../Configuration.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');
if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (
        !isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
    ) {
        session_unset();
        session_destroy();
        echo json_encode([
            'success' => false,
            'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (isset($_GET['Delete_Wifi'])) {
    if (isset($_POST['action']) && $_POST['action'] == 'delete_wifi' && isset($_POST['wifiName'])) {
        $wifiName = $_POST['wifiName'];
        $wifiInfo = shell_exec('iwconfig wlan0');
        if (empty($wifiInfo)) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể thực hiện lệnh iwconfig hoặc không có dữ liệu.',
                'data' => null
            ]);
            exit;
        }
        preg_match('/ESSID:"([^"]+)"/', $wifiInfo, $essidMatches);
        $wifiData_ESSID = isset($essidMatches[1]) ? $essidMatches[1] : 'N/A';
        if ($wifiName !== $wifiData_ESSID) {
            $connection = ssh2_connect($ssh_host, $ssh_port);
            if (!$connection) {
                echo json_encode(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH.']);
                exit;
            }
            if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
                echo json_encode(['success' => false, 'message' => 'Xác thực SSH thất bại.']);
                exit;
            }
            $stream = ssh2_exec($connection, "sudo nmcli connection delete '$wifiName'");
            if (!$stream) {
                echo json_encode(['success' => false, 'message' => 'Không thể thực thi lệnh xóa WiFi.']);
                exit;
            }
            stream_set_blocking($stream, true);
            $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
            $result = stream_get_contents($stream_out);
            echo json_encode(['success' => true, 'message' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Wifi ' . $wifiName . ' đang được kết nối, Không cho phép xóa']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa WiFi: Tham số không hợp lệ.']);
    }
    exit();
}

if (isset($_GET['Connect_Wifi'])) {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if (isset($_POST['ssid']) && isset($_POST['password'])) {
            $ssid = $_POST['ssid'];
            $password = $_POST['password'];
            $connection = ssh2_connect($ssh_host, $ssh_port);
            if (!$connection) {
                echo json_encode(['success' => false, 'message' => 'Không thể kết nối tới máy chủ SSH.']);
                exit;
            }
            if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
                echo json_encode(['success' => false, 'message' => 'Xác thực SSH thất bại.']);
                exit;
            }
            if ($action == 'connect_wifi') {
                $command = "sudo nmcli connection up '$ssid'";
            } elseif ($action == 'connect_and_save_wifi') {
                if (!empty($password)) {
                    $command = "sudo nmcli device wifi connect '$ssid' password '$password'";
                } else {
                    $command = "sudo nmcli device wifi connect '$ssid'";
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
                exit;
            }
            $stream = ssh2_exec($connection, $command);
            stream_set_blocking($stream, true);
            $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
            $result = stream_get_contents($stream_out);
            echo json_encode(['success' => true, 'message' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SSID hoặc mật khẩu không được cung cấp.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    }
    exit();
}

//Đặt lại cấu hình Wifi
if (isset($_GET['Reset_Wifi'])) {
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể kết nối đến server SSH.',
            'data' => null
        ]);
        exit;
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Xác thực SSH thất bại.',
            'data' => null
        ]);
        exit;
    }
    $command = 'dos2unix ' . $VBot_Offline . 'resource/wifi_manager/reset_wifi.sh && sudo ' . $VBot_Offline . 'resource/wifi_manager/reset_wifi.sh';
    $stream = ssh2_exec($connection, $command);
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $result = stream_get_contents($stream_out);
    echo json_encode([
        'success' => true,
        'message' => 'Đã gửi lệnh đặt lại toàn bộ cấu hình WiFi, Hãy kiểm tra, kết nối và cấu hình với điểm truy cập Wifi được phát ra là: VBot Assistant',
        'data' => null
    ]);
    exit;
}

#Quét các mạng wifi xung quanh
if (isset($_GET['Scan_Wifi_List'])) {
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể kết nối đến server SSH.',
            'data' => null
        ]);
        exit;
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Xác thực SSH thất bại.',
            'data' => null
        ]);
        exit;
    }
    $stream = ssh2_exec($connection, "sudo nmcli -t -f SSID,BSSID,MODE,CHAN,RATE,SIGNAL,BARS,SECURITY dev wifi");
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $result = stream_get_contents($stream_out);
    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lấy dữ liệu WiFi từ SSH.',
            'data' => null
        ]);
        exit;
    }
    $lines = explode("\n", $result);
    $wifi_data = [];
    foreach ($lines as $line) {
        if (!empty($line)) {
            $line = str_replace('\\', '', $line);
            $parts = explode(':', $line);
            $parts = array_map('htmlspecialchars', $parts);
            $bssidParts = array_slice($parts, 1, 6);
            $bssid = implode(':', $bssidParts);
            $chan = $parts[8];
            $rate = $parts[9];
            $signal = $parts[10];
            $bars = $parts[11];
            $securityy = empty($parts[12]) ? "" : $parts[12];
            $Check_ssid_hidee = empty($parts[0]) ? "wifi_hidden" : $parts[0];
            $Check_ssid_hide = empty($parts[0]) ? "Mạng ẩn" : $parts[0];
            $security = empty($parts[12]) ? "Không mật khẩu" : $parts[12];
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
    echo json_encode([
        'success' => true,
        'message' => 'Quét WiFi thành công.',
        'data' => $wifi_data
    ]);
    exit;
}

if (isset($_GET['Get_Password_Wifi'])) {
    $response = ['success' => false, 'message' => '', 'data' => []];
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
        $response['message'] = "Không thể kết nối đến server SSH.";
        echo json_encode($response);
        exit;
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $response['message'] = "Xác thực SSH thất bại.";
        echo json_encode($response);
        exit;
    }
    $desiredSSID = isset($_GET['ssid']) ? $_GET['ssid'] : '';
    if (empty($desiredSSID)) {
        $response['message'] = "Cần nhập tên Wifi để lấy mật khẩu.";
        echo json_encode($response);
        exit;
    }
    $configFilePath = '/etc/NetworkManager/system-connections/';
    $stream = ssh2_exec($connection, "ls \"$configFilePath\"");
    stream_set_blocking($stream, true);
    $files = explode("\n", trim(stream_get_contents($stream)));
    foreach ($files as $file) {
        if (!empty($file)) {
            $file = trim($file, '"');
            $configFile = $configFilePath . $file;
            $stream = ssh2_exec($connection, "sudo cat \"$configFile\"");
            stream_set_blocking($stream, true);
            $configContent = stream_get_contents($stream);
            preg_match('/ssid=(.*)/', $configContent, $ssidMatches);
            preg_match('/psk=(.*)/', $configContent, $passwordMatches);
            preg_match('/uuid=(.*)/', $configContent, $uuidMatches);
            preg_match('/timestamp=(.*)/', $configContent, $timestampMatches);
            preg_match('/seen-bssids=(.*)/', $configContent, $bssidMatches);
            // Chuyển đổi timestamp sang định dạng ngày giờ
            $formattedTimestamp = !empty($timestampMatches[1]) ? date("H:i:s d-m-Y", $timestampMatches[1]) : null;
            if (!empty($ssidMatches[1]) && strpos($ssidMatches[1], $desiredSSID) !== false) {
                $wifiInfo = [
                    'file' => $file,
                    'ssid' => $ssidMatches[1],
                    'uuid' => !empty($uuidMatches[1]) ? $uuidMatches[1] : null,
                    'timestamp' => $formattedTimestamp,
                    'seen_bssids' => !empty($bssidMatches[1]) ? rtrim($bssidMatches[1], ';') : null,
                    'password' => !empty($passwordMatches[1]) ? $passwordMatches[1] : 'Không có mật khẩu'
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
    echo json_encode($response);
    exit();
}

#Hiển thị các mạng wifi đã kết nối
if (isset($_GET['Show_Wifi_List'])) {
    $result = shell_exec('nmcli -t -f NAME,UUID,DEVICE con show');
    if ($result !== null) {
        $savedWifiInfo = explode("\n", trim($result));
        $savedWifiInfo = array_filter($savedWifiInfo);
        $formattedWifiInfo = array_map(function ($item) {
            $parts = explode(':', $item);
            return [
                "ssid" => $parts[0],
                "uuid" => $parts[1],
                "interface" => $parts[2]
            ];
        }, $savedWifiInfo);
        echo json_encode([
            'success' => true,
            'message' => 'Lấy danh sách WiFi thành công.',
            'data' => $formattedWifiInfo
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể lấy danh sách WiFi.',
            'data' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit();
}

#kiểm tra thông tin mạng wifi đang kết nối
if (isset($_GET['Wifi_Network_Information'])) {
    $wifiInfo = shell_exec('iwconfig wlan0');
    if (empty($wifiInfo)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể thực hiện lệnh iwconfig hoặc không có dữ liệu.',
            'data' => null
        ]);
        exit;
    }
    $wifiData = [];
    // Sử dụng các mẫu chính quy để trích xuất thông tin từ kết quả
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
    // Lưu kết quả vào mảng
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
    echo json_encode([
        'success' => true,
        'message' => 'Dữ liệu đã được lấy thành công.',
        'data' => $wifiData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}
?>