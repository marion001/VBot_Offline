<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
include 'Configuration.php';
?>

<?php
if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (!isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))) {
        session_unset();
        session_destroy();
        header('Location: Login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<?php include 'html_head.php'; ?>

<style>
    #vbot_Client_Scan_devices {
        width: 100%;
        overflow-x: auto;
    }
    .modal-xl {
        max-width: 90%;
    }
    .modal-lg {
        max-width: 70%;
    }
    .fullscreen-toggle, .refresh-btn {
        cursor: pointer;
        margin-right: 10px;
    }
    .latest-version {
        background-color: green;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
    }
    .update-link {
        background-color: #ff9800;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
        display: inline-block;
        word-break: break-word;
        white-space: normal;
        text-align: center;
    }
    .update-link_upgrade {
        background-color: #8e30c9;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
        display: inline-block;
        word-break: break-word;
        white-space: normal;
        text-align: center;
    }
	.status-dot {
		display: inline-block;
		width: 12px;
		height: 12px;
		border-radius: 50%;
		margin-right: 5px;
		vertical-align: middle;
	}
	.online {
		background-color: #28a745;
	}
	.offline {
		background-color: #dc3545;
	}
	
</style>
<body>
<?php include 'html_header_bar.php'; ?>
<?php include 'html_sidebar.php'; ?>
<main id="main" class="main">
    <div class="pagetitle">
        <h1>VBot Client Management <a title="Truy Cập" href="https://github.com/marion001/VBot_Client_Offline" target="_blank"><i class="bi bi-github"></i></a></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
                <li class="breadcrumb-item">VBot Client</li>
                 | Trạng Thái Kích Hoạt VBot Server: <?php echo $Config['api']['streaming_server']['active'] ? '<p class="text-success" title="VBot Server đang được kích hoạt"> Đang Bật</p>' : '<p class="text-danger" title="VBot Server không được kích hoạt"> Đang Tắt</p>'; ?>
            </ol>
        </nav>
    </div>

    <section class="section">
        <center>
            <button type="button" class="btn btn-primary" onclick="scan_VBot_Client_Device()"><i class="bi bi-radar"></i> Quét Thiết Bị Client</button>
            <button class="btn btn-success" onclick="reloadClients()" title="Tải lại toàn bộ dữ liệu Client hiện có"> Tải lại Dữ Liệu Client</button>
            <button type="button" class="btn btn-danger" onclick="clearServerData()"><i class="bi bi-x-circle"></i> Xóa Dữ Liệu Quét Trước Đó</button>
        </center>
        <br/>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text border-success" id="basic-addon1">Thêm Client Thủ Công</span>
            </div>
            <input type="text" class="form-control border-success" name="add_client_manual_ip" id="add_client_manual_ip" placeholder="Nhập địa chỉ IP Của Client">
            <button type="button" class="btn btn-primary border-success" onclick="bat_dau_them_client_thu_cong()">Thêm</button>
        </div>
        <hr/>
        <div class="row">
            <div id="vbot_Client_Scan_devices"></div>
        </div>
    </section>
</main>

<!-- Modal -->
<div class="modal fade" id="clientConfigModal" tabindex="-1" aria-labelledby="clientConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center w-100">
                <h5 class="modal-title" id="clientConfigModalLabel">Cấu Hình Client</h5>
                <div class="d-flex align-items-center">
                    <i class="bi bi-arrow-repeat refresh-btn pe-3 text-success" title="Tải lại dữ liệu Client"></i>
                    <i class="bi bi-arrows-fullscreen fullscreen-toggle pe-3 text-primary" title="Phóng to/Thu nhỏ"></i>
                    <i class="bi bi-x-lg text-danger" title="Đóng" data-bs-dismiss="modal"></i>
                </div>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Nội dung sẽ được thêm bằng JavaScript -->
            </div>
            <hr/>
            <div class="modal-footer d-flex justify-content-center">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Đóng</button>
                <button type="button" class="btn btn-success" id="saveConfigBtn"><i class="bi bi-save"></i> Lưu Cài Đặt</button>
            </div>
        </div>
    </div>
</div>

<?php include 'html_footer.php'; ?>
<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script>
// Hàm lưu dữ liệu Client vào Server
function saveToServer(data) {
    return fetch('includes/php_ajax/VBot_Client_Upgrade_Firmware.php?action=save_data_vbot_client', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (!result.success) {
            show_message('Lỗi khi lưu dữ liệu: ' + result.message);
        } else {
            showMessagePHP(result.message, 5);
        }
        return result;
    })
    .catch(error => {
        show_message('Lỗi kết nối khi lưu dữ liệu: ' + error);
    });
}

// Hàm đọc dữ liệu từ server
function loadFromServer() {
    const url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $directory_path; ?>/includes/other_data/VBot_Client_Data/Data_VBot_Client.json';
    return fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Không thể tải dữ liệu');
            }
            return response.json();
        })
		// Truy cập vào "data", trả về mảng rỗng nếu không có
        .then(response => response.data || [])
        .catch(error => {
            showMessagePHP('Không thể tải dữ liệu từ server', 5);
            return [];
        });
}

//So sánh phiên bản cập nhật
function compareVersions(currentVersion, latestVersion) {
    if (!currentVersion || !latestVersion) return false;
    // So sánh trực tiếp hai chuỗi
    return currentVersion === latestVersion;
}

function scan_VBot_Client_Device() {
    showMessagePHP('Đang tìm kiếm các thiết bị chạy VBot Client trong mạng', 5);
    loading('show');
    const url = 'includes/php_ajax/Scanner.php?VBot_Client_Device_Scaner';
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            loading('hide');
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const data = response.data;
                        if (Array.isArray(data) && data.length > 0) {
                            displayDeviceData(data);
                            saveToServer(data);
                        } else {
                            document.getElementById('vbot_Client_Scan_devices').innerHTML = 'Không tìm thấy thiết bị VBot Client nào';
                        }
                    }else{
						show_message('Lỗi: <font color="red">' +response.messager+'</font>');
					}
                } catch (error) {
                    document.getElementById('vbot_Client_Scan_devices').innerHTML = 'Lỗi xử lý dữ liệu.';
                }
            }
        }
    };
    xhr.send();
}

function displayDeviceData(data) {
    if (!Array.isArray(data)) {
        document.getElementById('vbot_Client_Scan_devices').innerHTML = '<center>Dữ liệu không hợp lệ</center>';
        return;
    }
    // Hàm so sánh để sắp xếp IP từ nhỏ đến lớn
    function compareIPs(a, b) {
        const ipA = a.ip_address.split('.').map(Number);
        const ipB = b.ip_address.split('.').map(Number);
        for (let i = 0; i < 4; i++) {
            if (ipA[i] !== ipB[i]) {
                return ipA[i] - ipB[i];
            }
        }
        return 0;
    }
    // Sắp xếp mảng data theo IP
    data.sort(compareIPs);
    let tableHTML = 
        '<table class="table table-bordered border-primary">' +
        '<thead>' +
        '<tr>' +
        '<th style="text-align: center; vertical-align: middle;">Tên Client</th>' +
        '<th style="text-align: center; vertical-align: middle;">Địa Chỉ IP</th>' +
        '<th style="text-align: center; vertical-align: middle;">Phiên Bản Firmware</th>' +
        '<th style="text-align: center; vertical-align: middle;">Chip Model</th>' +
        '<th style="text-align: center; vertical-align: middle;">Hành Động</th>' +
        '</tr>' +
        '</thead>' +
        '<tbody>';
    data.forEach(function(device, index) {
        const isS3 = (device.client_name || '').toLowerCase().includes('s3');
        const versionUrl = isS3 
            ? 'https://api.github.com/repos/marion001/VBot_Client_Offline/contents/ESP32S3/bin/Version.json' 
            : 'https://api.github.com/repos/marion001/VBot_Client_Offline/contents/ESP32/bin/Version.json';
        tableHTML += 
            '<tr>' +
            '<td style="text-align: center; vertical-align: middle;"><span class="status-dot" id="status-' + index + '"></span>' + (device.client_name || '') + '</td>' +
            '<td style="text-align: center; vertical-align: middle;"><a href="http://' + (device.ip_address || '') + '" target="_blank">' + (device.ip_address || '') + ' <i class="bi bi-box-arrow-up-right"></i></a></td>' +
            '<td style="text-align: center; vertical-align: middle;" id="device-version-' + index + '">' + (device.version || '') + '</td>' +
            '<td style="text-align: center; vertical-align: middle;">' + (device.chip_model || '') + '</td>' +
            '<td style="text-align: center; vertical-align: middle;">' +
            '<button class="btn btn-success config-btn" data-ip="' + (device.ip_address || '') + '" data-bs-toggle="modal" data-bs-target="#clientConfigModal">Cấu Hình</button> ' +
            '<button class="btn btn-info ping-btn" data-ip="' + (device.ip_address || '') + '" data-index="' + index + '" title="Kiểm tra trạng thái"><i class="bi bi-wifi"></i></button> ' +
            '<button class="btn btn-danger delete-btn" data-ip="' + (device.ip_address || '') + '" title="Xóa Client: ' + (device.ip_address || '') + '"><i class="bi bi-trash"></i></button> ' +
            '</td>' +
            '</tr>';
        // Check version
        fetch(versionUrl)
            .then(response => response.json())
            .then(versionData => {
                const base64Content = versionData.content;
                const decodedContent = atob(base64Content);
                const latestVersion = JSON.parse(decodedContent).build_date;
                const currentVersion = device.version || '';
                let versionHTML = currentVersion;
                if (latestVersion && !compareVersions(currentVersion, latestVersion)) {
                    versionHTML += ' <span class="update-link" title="Phiên bản mới nhất" title="Có phiên bản mới: ' + latestVersion + '">Có phiên bản mới: '+latestVersion+'</span>' +
                                    ' <br/><button type="button" class="btn btn-primary update-link_upgrade" onclick="start_upgrade_firmware(\'' + (device.ip_address || '') + '\', \'' + (device.firmware_url || '') + '\')">Nâng Cấp <i class="bi bi-arrow-up-circle"></i></button>';
                } else {
                    versionHTML += ' <span class="latest-version" title="Phiên bản mới nhất">Đã cập nhật</span>';
                }
                document.querySelector('#device-version-' + index).innerHTML = versionHTML;
            })
			.catch(error => {
				// Kiểm tra lỗi rate limit từ GitHub
				if (error.message && error.message.includes("API rate limit exceeded")) {
					showMessagePHP('Đã vượt quá giới hạn API GitHub. Vui lòng thử lại sau 1h', 5);
					//console.log('Rate limit error:', error);
				} else {
					// Xử lý các lỗi khác
					showMessagePHP('Lỗi kiểm tra phiên bản mới từ GitHub: ' + error.message, 5);
					//console.log('Fetch error:', error);
				}
				document.querySelector('#device-version-' + index).innerHTML = device.version || '';
			});
        // Ping device initially
        pingDevice(device.ip_address, index);
    });
    tableHTML += '</tbody></table>';
    document.getElementById('vbot_Client_Scan_devices').innerHTML = tableHTML;
    // Gắn sự kiện cho các nút "Cấu Hình"
    document.querySelectorAll('.config-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const ip = this.getAttribute('data-ip');
            loadFromServer().then(devices => {
                const device = devices.find(d => d.ip_address === ip);
                if (device) {
                    showConfigModal(device);
                } else {
                    showMessagePHP('Không tìm thấy thiết bị với IP: ' + ip, 5);
                }
            });
        });
    });
    // Gắn sự kiện cho các nút "Xóa"
    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const ip = this.getAttribute('data-ip');
            deleteClient(ip);
        });
    });
    // Gắn sự kiện cho các nút "Ping"
    document.querySelectorAll('.ping-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const ip = this.getAttribute('data-ip');
            const index = this.getAttribute('data-index');
            pingDevice(ip, index, true); // Tham số true để hiển thị thông báo
        });
    });
}

function showConfigModal(device) {
    document.getElementById('clientConfigModalLabel').innerHTML = '<font color=red>Cấu Hình Client: ' + device.ip_address + ' - ' + device.client_name+ '</font><hr/>';
    let modalContent = 
        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;"><font color="red">Thông Tin Client</font></th></tr></thead>' +
        '<tbody>' +
        '<tr><th>Tên Thiết Bị (Tối đa 25 ký tự):</th><td><input type="text" class="form-control client-name border-success" value="' + (device.client_name || '') + '"></td></tr>' +
        '<tr><th>Địa Chỉ IP:</th><td><a href="http://' + (device.ip_address || '') + '" target="_blank">' + (device.ip_address || '') + ' <i class="bi bi-box-arrow-up-right"></i></a></td></tr>' +
        '<tr><th>Phiên Bản VBot Client (Build Date):</th><td class="text-danger">' + (device.version || '') + '</td></tr>' +
        '<tr><th>Chip Model:</th><td>' + (device.chip_model || '') + '</td></tr>' +
        '<tr><th>Firmware URL:</th><td><a href="' + (device.firmware_url || '') + '" target="_blank">' + (device.firmware_url || '') + '</a></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;"><font color="red">Cấu Hình Kết Nối Tới Server VBot</font></th></tr></thead>' +
        '<tbody>' +
        '<tr><th>Địa Chỉ IP VBot Server:</th><td><input type="text" class="form-control server-ip border-success" value="' + (device.server?.vbot_server_ip || '') + '"></td></tr>' +
        '<tr><th>VBot Server Socket Port:</th><td><input type="number" class="form-control server-port border-success" value="' + (device.server?.vbot_server_port || '') + '"></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;"><font color="red">Cấu Hình I2S (Mic/Audio INMP441/MAX98357)</font></th></tr></thead>' +
        '<tbody>' +
        '<tr><th>INMP441=SCK / MAX98357=BCLK GPIO Pin:</th><td><input type="number" class="form-control gpio-sck border-success" value="' + (device.i2s_config?.gpio_sck_bclk || '') + '"></td></tr>' +
        '<tr><th>INMP441=WS / MAX98357=LRC GPIO Pin:</th><td><input type="number" class="form-control gpio-ws border-success" value="' + (device.i2s_config?.gpio_ws_lrc || '') + '"></td></tr>' +
        '<tr><th>INMP441=SD GPIO Pin:</th><td><input type="number" class="form-control gpio-sd border-success" value="' + (device.i2s_config?.gpio_sd || '') + '"></td></tr>' +
        '<tr><th>MAX98357=DIN GPIO Pin:</th><td><input type="number" class="form-control gpio-din border-success" value="' + (device.i2s_config?.gpio_din || '') + '"></td></tr>' +
        '<tr><th>Khuếch Đại Mic Gain (5.0->50.0):</th><td><input type="number" step="0.1" class="form-control gain-mic border-success" value="' + (device.i2s_config?.gain_mic || '') + '"></td></tr>' +
        '<tr><th>Khuếch Đại Volume Gain:</th><td><input type="number" step="0.1" class="form-control gain-volume border-success" value="' + (device.i2s_config?.gain_volume || '') + '"></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;"><font color="red">Cấu Hình LED WS2812B</font></th></tr></thead>' +
        '<tbody>' +
        '<tr><th>LED WS2812B GPIO Pin:</th><td><input type="number" class="form-control led-pin border-success" value="' + (device.led_config?.gpio_d0_pin || '') + '"></td></tr>' +
        '<tr><th>Số Lượng LED (Nên Sử Dụng Số Chẵn):</th><td><input type="number" class="form-control led-pixels border-success" value="' + (device.led_config?.num_pixels || '') + '"></td></tr>' +
        '<tr><th>Độ Sáng LED (0-255):</th><td><input type="number" class="form-control led-bright border-success" value="' + (device.led_config?.brightness || '') + '"></td></tr>' +
        '<tr><th>Hiệu Ứng LED Loading (Xử Lý) 1-3:</th><td><input type="number" min="1" max="3" class="form-control led-loading border-success" value="' + (device.led_config?.loading_effect || '') + '"></td></tr>' +
        '<tr><th>Màu LED Think/WakeUP (Hex):</th><td><div class="input-group mb-3"><input type="text" class="form-control led-think border-success" value="' + (device.led_config?.think_color || '') + '"><input type="color" class="form-control-color color-picker border-success" value="' + (device.led_config?.think_color ? '#' + device.led_config.think_color : '#000000') + '"></div></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;"><font color="red">Cấu Hình Nút Nhấn</font></th></tr></thead>' +
        '<tbody>' +
        '<tr><th>Nút Nhấn Mic (Bật/Tắt Sử Dụng WakeUP Bằng Giọng Nói) GPIO Pin:</th><td><input type="number" class="form-control button-mic border-success" value="' + (device.button?.gpio_mic || '') + '"></td></tr>' +
        '<tr><th>Nút Nhấn WakeUp (Đánh Thức Bằng Nút Nhấn) GPIO Pin:</th><td><input type="number" class="form-control button-wake border-success" value="' + (device.button?.gpio_wake_up || '') + '"></td></tr>' +
        '<tr><th>Thời Gian Nhấn (ms):</th><td><input type="number" class="form-control button-delay border414-success" value="' + (device.button?.debounce_delay || '') + '"></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;"><font color="red">Thiết Lập Tùy Chọn Khác</font></th></tr></thead>' +
        '<tbody>' +
        '<tr><th>Hiển Thị Logs ESP Qua Cổng Serial:</th><td><div class="form-switch"><input type="checkbox" class="form-check-input serial-log border-success" ' + (device.logs_serial_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Bật,Tắt Sử Dụng Mic Để WakeUP Bằng Giọng Nói (Tương Tự Nút Nhấn Mic):</th><td><div class="form-switch"><input type="checkbox" class="form-check-input mic-active border-success" ' + (device.button?.mic_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Kích Hoạt Sử Dụng Loa (Ding/Dong):</th><td><div class="form-switch"><input type="checkbox" class="form-check-input speaker-active border-success" ' + (device.i2s_config?.speaker_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Kích Hoạt Sử Dụng Nút Nhấn WakeUp:</th><td><div class="form-switch"><input type="checkbox" class="form-check-input wake-active border-success" ' + (device.button?.wakeup_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Chế Độ Hội Thoại (Trò Chuyện Liên Tục):</th><td><div class="form-switch"><input type="checkbox" class="form-check-input conversation-active border-success" ' + (device.conversation_active ? 'checked' : '') + '></div></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;"><font color="red">Sao Lưu / Khôi Phục, Nâng Cấp Firmware OTA</font></th></tr></thead>' +
        '<tbody>' +
        '<tr><th style="text-align: center; vertical-align: middle;">Nâng Cấp Firmware Tự Động OTA:</th>' +
        '<td style="text-align: center; vertical-align: middle;">' +
        '<button type="button" class="btn btn-success" onclick="start_upgrade_firmware(\'' + (device.ip_address || '') + '\', \'' + (device.firmware_url || '') + '\')"><i class="bi bi-box-arrow-in-up"></i> Nâng Cấp Firmware Tự Động</button>' +
        '</td></tr>' +
        '<tr><th style="text-align: center; vertical-align: middle;">Nâng Cấp Firmware Thủ Công <a href="https://github.com/marion001/VBot_Client_Offline" target="_blank"><i class="bi bi-github"></i></a>:</th>' +
        '<td style="text-align: center; vertical-align: middle;"><div class="input-group mb-3"><input class="form-control client-name border-success" type="file" id="manual_upgrade_firmware" name="manual_upgrade_firmware" accept=".bin">' +
        '<button type="button" class="btn btn-success" onclick="start_upgrade_firmware_manual(\'' + (device.ip_address || '') + '\')"><i class="bi bi-box-arrow-in-up"></i> Nâng Cấp</button>' +
        '</div></td></tr>' +
        '<tr><th style="text-align: center; vertical-align: middle;">Khôi Phục Cấu Hình Cài Đặt (.json):</th>' +
        '<td style="text-align: center; vertical-align: middle;"><div class="input-group mb-3"><input class="form-control client-name border-success" type="file" id="configFile_restore" accept=".json">' +
        '<button type="button" onclick="upload_restore_settings(\'' + (device.ip_address || '') + '\')" class="btn btn-success"><i class="bi bi-filetype-json"></i> Khôi Phục Cấu Hình</button>' +
        '<a href="http://' + (device.ip_address || '') + '/VBot_Client_Dowload_Config" target="_blank"><button type="button" class="btn btn-primary"><i class="bi bi-download"></i> Tải Xuống Tệp Cấu Hình</button></a>' +
        '</div></td></tr>' +
        '</tbody></table><hr/>' +

        '<center>' +
            '<button type="button" class="btn btn-warning" onclick="ctrl_act_vbot_client(\'' + (device.ip_address || '') + '\', \'restart_esp\')"><i class="bi bi-arrow-counterclockwise"></i> Restart ESP</button>' +
            ' <button type="button" class="btn btn-info" onclick="ctrl_act_vbot_client(\'' + (device.ip_address || '') + '\', \'reset_wifi_esp\')"><i class="bi bi-wifi-off"></i> Reset Cấu Hình Wifi</button>' +
            ' <button type="button" class="btn btn-danger" onclick="ctrl_act_vbot_client(\'' + (device.ip_address || '') + '\', \'cleanNVS\')"> <i class="bi bi-recycle"></i> Reset Toàn Bộ Dữ Liệu</button>' +
        '</center>';

    document.getElementById('modalContent').innerHTML = modalContent;
    const textInput = document.querySelector('.led-think');
    const colorInput = document.querySelector('.color-picker');
    textInput.addEventListener('input', function() {
        if (/^[0-9A-Fa-f]{6}$/.test(this.value)) colorInput.value = '#' + this.value;
    });
    colorInput.addEventListener('input', function() {
        textInput.value = this.value.replace('#', '');
    });
    document.getElementById('saveConfigBtn').onclick = function() {
        saveConfig(device.ip_address);
    };
    const modalDialog = document.querySelector('#clientConfigModal .modal-dialog');
    const fullscreenToggle = document.querySelector('.fullscreen-toggle');
    let currentSizeIndex = 0;
    const sizes = ['modal-xl', 'modal-fullscreen', 'modal-lg'];
    function updateFullscreenIcon() {
        if (sizes[currentSizeIndex] === 'modal-fullscreen') {
            fullscreenToggle.classList.remove('bi-arrows-fullscreen');
            fullscreenToggle.classList.add('bi-fullscreen-exit');
            fullscreenToggle.title = 'Thu nhỏ';
        } else {
            fullscreenToggle.classList.remove('bi-fullscreen-exit');
            fullscreenToggle.classList.add('bi-arrows-fullscreen');
            fullscreenToggle.title = 'Phóng to/Thu nhỏ';
        }
    }
    fullscreenToggle.onclick = function() {
        modalDialog.classList.remove(sizes[currentSizeIndex]);
        currentSizeIndex = (currentSizeIndex + 1) % sizes.length;
        modalDialog.classList.add(sizes[currentSizeIndex]);
        updateFullscreenIcon();
    };
    updateFullscreenIcon();
    const refreshBtn = document.querySelector('.refresh-btn');
    refreshBtn.onclick = function() {
        loading_data_client(device.ip_address, 1000);
    };
}

// Tải lại dữ liệu cho Client riêng lẻ
function loading_data_client(ip_address, time_out = 1000) {
    loading('show');
    setTimeout(function() {
        const infoUrl = 'http://' + ip_address + '/VBot_Client_Info';
        const xhr = new XMLHttpRequest();
        xhr.open('GET', infoUrl, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            loading('hide');
                            loadFromServer().then(devices => {
                                const updatedDevice = response;
                                const deviceIndex = devices.findIndex(d => d.ip_address === ip_address);
                                if (deviceIndex !== -1) {
                                    devices[deviceIndex] = updatedDevice;
                                } else {
                                    devices.push(updatedDevice);
                                }
                                saveToServer(devices);
                                displayDeviceData(devices);
                                showMessagePHP('Đã tải lại dữ liệu cho ' + ip_address, 5);
                                // Kiểm tra xem modal có đang mở và thuộc về IP này không
                                const modal = document.getElementById('clientConfigModal');
                                const isModalOpen = modal.classList.contains('show');
                                const modalIp = modal.querySelector('#clientConfigModalLabel font')?.textContent?.includes(ip_address);
                                if (isModalOpen && modalIp) {
                                    showConfigModal(updatedDevice);
                                }
                            });
                        } else {
                            loading('hide');
                            showMessagePHP('Tải lại dữ liệu thất bại', 5);
                        }
                    } catch (error) {
                        loading('hide');
                        showMessagePHP('Lỗi xử lý dữ liệu từ API: ' + error.message, 5);
                    }
                } else {
                    loading('hide');
                    showMessagePHP('Không thể kết nối tới client: ' + ip_address, 5);
                }
            }
        };
        xhr.send();
    }, time_out);
}

// Lưu cấu hình Config theo ip client
function saveConfig(ip_address) {
    loading('show');
    const config = {
        ip_address: ip_address,
        client_name: document.querySelector('.client-name').value,
        server: {
            vbot_server_ip: document.querySelector('.server-ip').value,
            vbot_server_port: parseInt(document.querySelector('.server-port').value) || 0
        },
        i2s_config: {
            gpio_sck_bclk: parseInt(document.querySelector('.gpio-sck').value) || 0,
            gpio_ws_lrc: parseInt(document.querySelector('.gpio-ws').value) || 0,
            gpio_sd: parseInt(document.querySelector('.gpio-sd').value) || 0,
            gpio_din: parseInt(document.querySelector('.gpio-din').value) || 0,
            gain_mic: parseFloat(document.querySelector('.gain-mic').value) || 0,
            gain_volume: parseFloat(document.querySelector('.gain-volume').value) || 0,
            speaker_active: document.querySelector('.speaker-active').checked
        },
        led_config: {
            gpio_d0_pin: parseInt(document.querySelector('.led-pin').value) || 0,
            num_pixels: parseInt(document.querySelector('.led-pixels').value) || 0,
            brightness: parseInt(document.querySelector('.led-bright').value) || 0,
            loading_effect: parseInt(document.querySelector('.led-loading').value) || 0,
            think_color: document.querySelector('.led-think').value
        },
        button: {
            gpio_wake_up: parseInt(document.querySelector('.button-wake').value) || 0,
            gpio_mic: parseInt(document.querySelector('.button-mic').value) || 0,
            debounce_delay: parseInt(document.querySelector('.button-delay').value) || 0,
            wakeup_active: document.querySelector('.wake-active').checked,
            mic_active: document.querySelector('.mic-active').checked
        },
        logs_serial_active: document.querySelector('.serial-log').checked,
        conversation_active: document.querySelector('.conversation-active').checked
    };
    const url = 'http://' + ip_address + '/config';
    const data = 'client_name=' + encodeURIComponent(config.client_name) +
        '&udp_server=' + config.server.vbot_server_ip +
        '&udp_port=' + config.server.vbot_server_port +
        '&i2s_sck=' + config.i2s_config.gpio_sck_bclk +
        '&i2s_ws=' + config.i2s_config.gpio_ws_lrc +
        '&i2s_sd=' + config.i2s_config.gpio_sd +
        '&i2s_dout=' + config.i2s_config.gpio_din +
        '&gain_mic=' + config.i2s_config.gain_mic +
        '&gain_volume=' + config.i2s_config.gain_volume +
        '&led_pin=' + config.led_config.gpio_d0_pin +
        '&num_pixels=' + config.led_config.num_pixels +
        '&brightness=' + config.led_config.brightness +
        '&loading_effect=' + config.led_config.loading_effect +
        '&led_think_color=' + config.led_config.think_color +
        '&gpio_mic_pin=' + config.button.gpio_mic +
        '&gpio_led_pin=' + config.button.gpio_wake_up +
        '&debounce_delay=' + config.button.debounce_delay +
        '&logs_state=' + (config.logs_serial_active ? 'true' : 'false') +
        '&mic_state=' + (config.button.mic_active ? 'true' : 'false') +
        '&speaker_state=' + (config.i2s_config.speaker_active ? 'true' : 'false') +
        '&wakeup_state=' + (config.button.wakeup_active ? 'true' : 'false') +
        '&conv_mode=' + (config.conversation_active ? 'true' : 'false');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                loading('hide');
                showMessagePHP('Lưu cấu hình thành công cho ' + ip_address + '|' + config.client_name, 5);
                const infoUrl = 'http://' + ip_address + '/VBot_Client_Info';
                const infoXhr = new XMLHttpRequest();
                infoXhr.open('GET', infoUrl, true);
                infoXhr.onreadystatechange = function () {
                    if (infoXhr.readyState === 4 && infoXhr.status === 200) {
                        try {
                            const response = JSON.parse(infoXhr.responseText);
                            if (response.success) {
                                loadFromServer().then(devices => {
                                    const updatedDevice = response;
                                    const deviceIndex = devices.findIndex(d => d.ip_address === ip_address);
                                    if (deviceIndex !== -1) {
                                        devices[deviceIndex] = updatedDevice;
                                    } else {
                                        devices.push(updatedDevice);
                                    }
                                    saveToServer(devices);
                                    displayDeviceData(devices);
                                    showConfigModal(updatedDevice);
                                });
                            } else {
                                showMessagePHP('Lấy thông tin client thất bại', 5);
                            }
                        } catch (error) {
                            showMessagePHP('Lỗi xử lý dữ liệu từ API client', 5);
                        }
                    }
                };
                infoXhr.send();
            } else {
                loading('hide');
                showMessagePHP('Lưu cấu hình thất bại cho ' + ip_address, 5);
            }
        }
    };
    xhr.send(data);
}

function clearServerData() {
    var confirmUpgrade = confirm("Bạn có chắc chắn muốn xóa dữ liệu được tìm kiếm trước đó");
    if (!confirmUpgrade) {
        return;
    }
    loading('show');
    saveToServer([]).then(() => {
        showMessagePHP('Đã xóa dữ liệu được tìm kiếm trước đó', 5);
        document.getElementById('vbot_Client_Scan_devices').innerHTML = '<center>Không có dữ liệu lưu trữ</center>';
        loading('hide');
    });
}

// Hàm gửi yêu cầu reset tới VBot Client
function ctrl_act_vbot_client(ip_address, action) {
    loading('show');
    if (!ip_address || typeof ip_address !== 'string' || ip_address.trim() === '') {
        show_message('Địa chỉ IP không hợp lệ hoặc rỗng');
        loading('hide');
        return;
    }
    if (!action || typeof action !== 'string' || action.trim() === '') {
        show_message('Lỗi, dữ liệu không hợp lệ hoặc rỗng');
        loading('hide');
        return;
    }
    if (action === 'restart_esp') {
        control_action = 'restart';
    } else if (action === 'reset_wifi_esp') {
        control_action = 'resetwifi';
    } else if (action === 'cleanNVS') {
        control_action = 'cleanNVS';
    } else {
        loading('hide');
        show_message('Dữ liệu thao tác không hợp lệ');
        return;
    }
    const url = 'http://' + ip_address + '/' + control_action;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            loading('hide');
            showMessagePHP('Đã gửi yêu cầu khởi động lại cho: ' + ip_address, 5);
        }
    };
    xhr.onerror = function () {
        loading('hide');
        showMessagePHP('Đã gửi yêu cầu khởi động lại cho: ' + ip_address, 5);
    };
    xhr.timeout = 5000;
    xhr.ontimeout = function () {
        loading('hide');
        showMessagePHP('Đã gửi yêu cầu khởi động lại cho: ' + ip_address, 5);
    };
    xhr.send();
}

// Tiến hành cập nhật Firmware tự động
function start_upgrade_firmware(ip_address, url_firmware) {
    var confirmUpgrade = confirm("Bạn có chắc chắn muốn nâng cấp Firmware mới nhất không?");
    if (!confirmUpgrade) { 
        showMessagePHP("Đã hủy nâng cấp Firmware", 5);
        return;
    }
    loading('show');
    // Kiểm tra định dạng IP (ví dụ: 192.168.x.x)
    var ipRegex = /^192\.168\.\d{1,3}\.\d{1,3}$/;
    if (!ipRegex.test(ip_address)) {
        loading('hide');
        show_message("Định dạng địa chỉ IP không hợp lệ. Phải là dạng: 192.168.x.x");
        return;
    }
    // Kiểm tra URL firmware phải bắt đầu bằng http/https và kết thúc bằng .bin
    var urlRegex = /^https?:\/\/.*\.bin$/i;
    if (!urlRegex.test(url_firmware)) {
        loading('hide');
        show_message("URL của Firmware cũ không hợp lệ<br/>Phải bắt đầu bằng http:// hoặc https:// và kết thúc bằng .bin.<br/>Cần được nâng cấp thủ công");
        return;
    }
    showMessagePHP("Bắt đầu nâng cấp Firmware", 5);
    // Thiết lập timeout cho toàn bộ quá trình (180 giây)
    var totalTimeout = 180000;
    var startTime = Date.now();
    // Timeout cho bypass_upgrade_firmware (30 giây)
    var bypassTimeout = 30000;
    var bypassTimer = setTimeout(function() {
        loading('hide');
        show_message("Gián Đoạn, hết thời gian chờ nhận phản hồi bỏ qua xác thực OTA khi nâng cấp Firmware, Bạn cần nâng cấp thủ công");
    }, bypassTimeout);
    bypass_upgrade_firmware(ip_address, function(result) {
		// Hủy timeout của bypass nếu hoàn thành
        clearTimeout(bypassTimer);
        // Kiểm tra thời gian đã trôi qua
        var elapsedTime = Date.now() - startTime;
        if (elapsedTime >= totalTimeout) {
            loading('hide');
            show_message("Gián Đoạn, hết thời gian chờ nhận phản hồi khi nâng cấp Firmware, Bạn cần nâng cấp thủ công");
            return;
        }
        if (result.toLowerCase().includes("bypass_ota_ok")) {
            showMessagePHP("Bỏ qua xác thực OTA thành công, đang tiến hành cập nhật Firmware....", 5);
            var xhr = new XMLHttpRequest();
            var url = 'includes/php_ajax/VBot_Client_Upgrade_Firmware.php?start_upgrade_firmware&ip=' + encodeURIComponent(ip_address) + '&url_firmware=' + encodeURIComponent(url_firmware);
            xhr.open('GET', url, true);
            // Timeout cho XMLHttpRequest (còn lại từ 180 giây)
            var remainingTime = totalTimeout - elapsedTime;
            var xhrTimeout = setTimeout(function() {
                xhr.abort();
                loading('hide');
                show_message("Gián Đoạn, hết thời gian chờ nhận phản hồi khi nâng cấp Firmware, Bạn cần nâng cấp thủ công");
            }, remainingTime);
            xhr.onload = function () {
				//Hủy timeout nếu request hoàn thành
                clearTimeout(xhrTimeout);
                if (xhr.status >= 200 && xhr.status < 300) {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        loading('hide');
                        showMessagePHP("Thành Công: " + data.message, 5);
                        loading_data_client(ip_address, 3000);
                    } else {
                        loading('hide');
                        show_message("Thất Bại: " + data.message);
                    }
                } else {
                    loading('hide');
                    try {
                        var errorData = JSON.parse(xhr.responseText);
                        show_message("Tải lên không thành công với trạng thái: " + xhr.status + " - " + errorData.message);
                    } catch (e) {
                        show_message("Tải lên không thành công với trạng thái: " + xhr.status + " - Không thể phân tích phản hồi từ server");
                    }
                }
            };
            xhr.onerror = function () {
				// Hủy timeout nếu có lỗi mạng
                clearTimeout(xhrTimeout);
                loading('hide');
                show_message("Lỗi mạng trong quá trình cập nhật Firmware");
            };
            xhr.send();
        } else {
            loading('hide');
            show_message('-Lỗi xảy ra trong quá trình bỏ qua xác thực OTA<br/>- Thiết Bị Không Trực Tuyến: ' + result);
        }
    });
}

// Hàm bypass firmware (giữ nguyên)
function bypass_upgrade_firmware(ip_address, callback) {
    var xhr = new XMLHttpRequest();
    var url = 'includes/php_ajax/VBot_Client_Upgrade_Firmware.php?bypass_upgrade_firmware&ip=' + encodeURIComponent(ip_address);
    xhr.open('GET', url, true);
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
				// Trả về "Thiết bị đang nâng cấp firmware"
                callback(data.message);
            } else {
                show_message("Bỏ qua xác thực OTA thất bại: " + data.message);
                callback('Error');
            }
        } else {
            show_message("Yêu cầu bỏ qua xác thực OTA không thành công với trạng thái: " + xhr.status + " " + xhr.statusText);
            callback('Error');
        }
    };
    xhr.onerror = function () {
        show_message("Lỗi mạng khi bỏ qua xác thực OTA");
        callback('Error');
    };
    xhr.send();
}

// Nâng cấp FW Thủ Công, chọn file .bin
function start_upgrade_firmware_manual(ip_address) {
    var fileInput = document.getElementById("manual_upgrade_firmware");
    if (fileInput.files.length === 0) {
        show_message("Vui lòng chọn file .bin để nâng cấp firmware!");
        return;
    }
    var file = fileInput.files[0];
    var formData = new FormData();
    formData.append("firmware", file);
    formData.append("ip_address", ip_address);
    var confirmUpgrade = confirm("Bạn có chắc chắn muốn nâng cấp thủ công từ Firmware này?");
    if (!confirmUpgrade) {
        showMessagePHP("Đã hủy nâng cấp.", 3);
        return;
    }
    loading('show');
    showMessagePHP("Đang gửi Firmware .bin tới Client để nâng cấp Thủ Công...", 5);
    var xhr = new XMLHttpRequest();
    xhr.open("POST", 'includes/php_ajax/VBot_Client_Upgrade_Firmware.php', true);
    // Thiết lập timeout 3 phút (180 giây)
    var timeoutDuration = 180000;
    var timeoutId = setTimeout(function() {
        xhr.abort();
        loading('hide');
        showMessagePHP("Gián Đoạn, hết thời gian chờ nhận phản hồi khi nâng cấp Firmware, Bạn cần nâng cấp thủ công", 5);
    }, timeoutDuration);

    xhr.onload = function () {
        clearTimeout(timeoutId);
        try {
            var response = JSON.parse(xhr.responseText);
            if (xhr.status === 200 && response.success) {
                loading('hide');
                showMessagePHP("Thành công: " + response.message, 5);
                loading_data_client(ip_address, 3000);
            } else {
                loading('hide');
                showMessagePHP("Lỗi: " + response.message, 5);
            }
        } catch (e) {
            loading('hide');
            showMessagePHP("Lỗi phân tích phản hồi dữ liệu từ Server", 5);
        }
    };
    xhr.onerror = function () {
        clearTimeout(timeoutId);
        loading('hide');
        showMessagePHP("Lỗi kết nối trong quá trình nâng cấp firmware", 5);
    };
    xhr.send(formData);
}

// Khôi phục file cấu hình cài đặt
function upload_restore_settings(ip_address) {
    loading('show');
    const fileInput = document.getElementById('configFile_restore');
    const file = fileInput.files[0];
    if (!file) {
        show_message('Vui lòng chọn tệp .json cần khôi phục cấu hình!');
        loading('hide');
        return;
    }
    const formData = new FormData();
    formData.append("file", file);
    fetch('http://' + ip_address + '/upload_nvs_config', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                loading('hide');
                showMessagePHP("Đã tải lên tệp khôi phục cấu hình thành công!", 5);
            } else {
                loading('hide');
                show_message("Tải lên tệp khôi phục cấu hình thất bại!");
            }
        })
        .catch(error => {
            loading('hide');
            showMessagePHP("Đã thao tác tải lên tệp .json khôi phục cấu hình", 5);
        });
    loading_data_client(ip_address, 2000);
}

// Thêm Client thủ công bằng IP
function bat_dau_them_client_thu_cong() {
    var inputElement = document.getElementById("add_client_manual_ip");
    var input_value = inputElement.value.trim();
    input_value = input_value.replace(/^https?:\/\//, '');
    input_value = input_value.split('/')[0];
    var ip_pattern = /^(\d{1,3}\.){3}\d{1,3}$/;
    if (!ip_pattern.test(input_value)) {
        show_message("Địa chỉ IP không hợp lệ! Vui lòng nhập lại.");
        return;
    }
    loading_data_client(input_value);
    inputElement.value = "";
}

// Tải lại các client có trong bộ nhớ server
function reloadClients() {
    loadFromServer().then(clients => {
        if (!clients || clients.length === 0) {
            show_message("Không có Client nào trong dữ liệu.");
            return;
        }
        loading('show');
        clients.forEach((client, index) => {
            let ip_address = client.ip_address;
            setTimeout(() => {
                loading_data_client(ip_address, 1000);
            }, index * 2000);
        });
        loading('hide');
        showMessagePHP("Đã tải mới lại tất cả Client");
    });
}

//Check Online, Offline
async function pingDevice(ip, index, showNotification = false) {
    loading('show');
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);
        
        const response = await fetch('http://' + ip + '/VBot_Client_Info', { 
            mode: 'no-cors',
            signal: controller.signal
        });
        clearTimeout(timeoutId);
        
        const statusDot = document.getElementById('status-' + index);
        if (statusDot) {
            statusDot.classList.remove('offline');
            statusDot.classList.add('online');
            statusDot.title = 'Trực Tuyến';
        }
        //console.log(ip + ' is online');
        if (showNotification) {
            showMessagePHP('<p class="text-success"><b>Thiết bị ' + ip + ' đang Trực Tuyến</b></p>', 3);
        }
        loading('hide');
    } catch (error) {
        const statusDot = document.getElementById('status-' + index);
        if (statusDot) {
            statusDot.classList.remove('online');
            statusDot.classList.add('offline');
            statusDot.title = 'Ngoại Tuyến';
        }
        //console.log(ip + ' is offline');
        if (showNotification) {
            showMessagePHP('<p class="text-danger"><b>Thiết bị ' + ip + ' đang Ngoại Tuyến</b></p>', 3);
        }
        loading('hide');
    }
}

//Xóa Client Theo IP
function deleteClient(ip) {
    if (!confirm('Bạn có chắc chắn muốn xóa Client với IP: '+ip)) {
        return;
    }
    loading('show');
    loadFromServer()
        .then(devices => {
            const updatedDevices = devices.filter(device => device.ip_address !== ip);
            return saveToServer(updatedDevices)
                .then(result => {
                    if (result.success) {
                        displayDeviceData(updatedDevices);
                        showMessagePHP('Đã xóa Client '+ip+' thành công', 5);
                    } else {
                        showMessagePHP('Lỗi khi xóa Client '+ip+': '+result.message, 5);
                    }
                    loading('hide');
                });
        })
        .catch(error => {
            loading('hide');
            showMessagePHP('Lỗi khi xử lý xóa Client '+ip+': '+error, 5);
        });
}

//Hiển thị dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    loading('show');
    loadFromServer()
        .then(devices => {
            loading('hide');
            if (Array.isArray(devices) && devices.length > 0) {
                displayDeviceData(devices);
                showMessagePHP('Đã tải dữ liệu Vbot Client được lưu từ Server', 5);
            } else {
                document.getElementById('vbot_Client_Scan_devices').innerHTML = '<center>Không có dữ liệu thiết bị nào được lưu trữ trên server.</center>';
            }
        })
        .catch(error => {
            loading('hide');
            document.getElementById('vbot_Client_Scan_devices').innerHTML = '<center>Lỗi khi tải dữ liệu từ server: ' + error.message + '</center>';
        });
});
</script>

<?php include 'html_js.php'; ?>
</body>
</html>