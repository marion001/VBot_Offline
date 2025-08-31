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
 <link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css">
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
	.client_music_player {
		max-height: 200px;
		overflow-y: auto;
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

<div class="input-group mb-3">
                <span class="input-group-text border-danger" id="basic-addon2">Hướng Dẫn:</span>
            <input type="text" disabled class="form-control border-danger" name="guide_client" id="guide_client" value="https://github.com/marion001/VBot_Client_Offline">
            <button class="btn btn-success border-success" type="button"><a style="color: white;" href="https://github.com/marion001/VBot_Client_Offline" target="_blank">Truy Cập</a></button>
        </div>

    <section class="section">
        <center>
            <button type="button" class="btn btn-primary" onclick="scan_VBot_Client_Device()"><i class="bi bi-radar"></i> Quét Thiết Bị Client</button>
            <button class="btn btn-success" onclick="reloadClients()" title="Tải lại toàn bộ dữ liệu Client hiện có"> Tải lại Dữ Liệu Client</button>
            <button type="button" class="btn btn-danger" onclick="clearServerData()"><i class="bi bi-x-circle"></i> Xóa Dữ Liệu Quét Trước Đó</button>
        </center>
        <br/>
        <div class="input-group mb-3">
                <span class="input-group-text border-success" id="basic-addon1">Thêm Client Thủ Công</span>
            <input type="text" class="form-control border-success" name="add_client_manual_ip" id="add_client_manual_ip" placeholder="Nhập địa chỉ IP Của Client, VD: 192.168.1.199">
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

<!-- Modal hiển thị JSON -->
<div class="modal fade" id="jsonDisplayModal" tabindex="-1" aria-labelledby="jsonDisplayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center w-100">
                <h5 class="modal-title" id="jsonDisplayModalLabel"> Dữ liệu cấu hình từ Client</h5>
				<div class="d-flex align-items-center">
                <i class="bi bi-x-lg text-danger" title="Đóng" data-bs-dismiss="modal"></i>
				</div>
            </div>
            <div class="modal-body">
                <pre id="jsonContent" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
            </div>
            <div class="modal-footer d-flex justify-content-center">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Đóng</button>
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
        '<th style="text-align: center; vertical-align: middle;">Client Đang Kết Nối Tới Server</th>' +
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
            '<td style="text-align: center; vertical-align: middle;"><a href="http://' + (device.server.vbot_server_ip || '') + '" target="_blank">' + (device.server.vbot_server_ip || '') + ' <i class="bi bi-box-arrow-up-right"></i></a></td>' +
            '<td style="text-align: center; vertical-align: middle;" id="device-version-' + index + '">' + (device.version || '') + '</td>' +
            '<td style="text-align: center; vertical-align: middle;">' + (device.chip_model || '') + '</td>' +
            '<td style="text-align: center; vertical-align: middle;">' +
            '<button class="btn btn-success config-btn" data-ip="' + (device.ip_address || '') + '" data-bs-toggle="modal" data-bs-target="#clientConfigModal" title="Cấu hình"><i class="bi bi-gear-wide-connected"></i></button> ' +
            '<button class="btn btn-info ping-btn" data-ip="' + (device.ip_address || '') + '" data-index="' + index + '" title="Kiểm tra trạng thái"><i class="bi bi-wifi"></i></button> ' +
            '<button type="button" class="btn btn-warning" onclick="showJsonData(\'' + (device.ip_address || '') + '\')" title="Xem dữ liệu cấu hình json"><i class="bi bi-filetype-json"></i></button> ' +
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
            pingDevice(ip, index, true);
        });
    });
}

//Hiển thị bảng khi nhấn vào cài đặt Client
function showConfigModal(device) {
    document.getElementById('clientConfigModalLabel').innerHTML = '<font color=red>Cấu Hình Client: ' + device.ip_address + ' - ' + device.client_name+ '</font><hr/>';
    let modalContent = 
        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;" class="text-danger">Thông Tin Client</th></tr></thead>' +
        '<tbody>' +
        '<tr><th>Tên Thiết Bị (Tối đa 25 ký tự):</th><td><input type="text" class="form-control client_name border-success" value="' + (device.client_name || '') + '"></td></tr>' +
        '<tr><th>Địa Chỉ IP:</th><td><a href="http://' + (device.ip_address || '') + '" target="_blank">' + (device.ip_address || '') + ' <i class="bi bi-box-arrow-up-right"></i></a></td></tr>' +
        '<tr><th>Phiên Bản VBot Client (Build Date):</th><td class="text-danger">' + (device.version || '') + '</td></tr>' +
        '<tr><th>Chip Model:</th><td>' + (device.chip_model || '') + '</td></tr>' +
        '<tr><th>Firmware URL:</th><td><a href="' + (device.firmware_url || '') + '" target="_blank">' + (device.firmware_url || '') + '</a></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;" class="text-danger">Cấu Hình Kết Nối Tới Server VBot</th></tr></thead>' +
        '<tbody>' +
        '<tr><th>Địa Chỉ IP VBot Server:</th><td><input type="text" class="form-control vbot_server_ip border-success" value="' + (device.server?.vbot_server_ip || '') + '"></td></tr>' +
        '<tr><th>VBot Server Socket Port:</th><td><input type="number" class="form-control vbot_server_port border-success" value="' + (device.server?.vbot_server_port || '') + '"></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;" class="text-danger">I2S Cấu hình GPIO cho Mic INMP441 và Audio MAX98357</th></tr></thead>' +
        '<tbody>' +
        '<tr><th>INMP441=SCK / MAX98357=BCLK GPIO Pin:</th><td><input type="number" class="form-control i2s_sck_bclk_gpio border-success" value="' + (device.i2s_config?.i2s_sck_bclk_gpio || '') + '"></td></tr>' +
        '<tr><th>INMP441=WS / MAX98357=LRC GPIO Pin:</th><td><input type="number" class="form-control i2s_ws_lrc_gpio border-success" value="' + (device.i2s_config?.i2s_ws_lrc_gpio || '') + '"></td></tr>' +
        '<tr><th>INMP441=SD GPIO Pin:</th><td><input type="number" class="form-control i2s_sd_gpio border-success" value="' + (device.i2s_config?.i2s_sd_gpio || '') + '"></td></tr>' +
        '<tr><th>MAX98357=DIN GPIO Pin:</th><td><input type="number" class="form-control i2s_din_gpio border-success" value="' + (device.i2s_config?.i2s_din_gpio || '') + '"></td></tr>' +
        '<tr><th>Khuếch Đại Mic Gain (5.0->50.0):</th><td><input type="number" step="0.1" class="form-control i2s_gain_mic border-success" value="' + (device.i2s_config?.i2s_gain_mic || '') + '"></td></tr>' +
        '<tr><th>Chọn Kênh Mic:</th><td>' +
		'<select class="form-select i2s_mic_channel border-success">' +
		'<option value="left" ' + (device.i2s_config?.i2s_mic_channel === 'left' ? 'selected' : '') + '>Mic Trái (Left: L/R -> GND)</option>' +
		'<option value="right" ' + (device.i2s_config?.i2s_mic_channel === 'right' ? 'selected' : '') + '>Mic Phải (Right L/R -> 3.3v)</option>' +
		'<option value="both" ' + (device.i2s_config?.i2s_mic_channel === 'both' ? 'selected' : '') + '>Cả 2 Mic</option>' +
		'</select>' +
		'</td></tr>' +
		'<tr><th>Âm Lượng Loa (%):</th><td><input type="number" step="1" min="0" max="100" class="form-control i2s_speaker_volume border-success" value="' + (device.i2s_config?.i2s_speaker_volume || '') + '"></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;" class="text-danger">Cấu Hình Sử Dụng Đèn LED WS2812B</th></tr></thead>' +
        '<tbody>' +
		'<tr><th>Kích Hoạt Sử Dụng LED:</th><td style="text-align: center; vertical-align: middle;"><div class="form-switch">' +
		'<input type="checkbox" class="form-check-input led_active border-success" ' + (device.led_config.led_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>LED WS2812B GPIO Pin:</th><td><input type="number" class="form-control led_gpio border-success" value="' + (device.led_config?.led_gpio || '') + '"></td></tr>' +
        '<tr><th>Số Lượng LED (Nên Sử Dụng Số Chẵn):</th><td><input type="number" class="form-control led_number border-success" value="' + (device.led_config?.led_number || '') + '"></td></tr>' +
        '<tr><th>Độ Sáng LED (0-255):</th><td><input type="number" class="form-control led_brightness border-success" value="' + (device.led_config?.led_brightness || '') + '"></td></tr>' +
        '<tr><th>Hiệu Ứng LED Loading (Xử Lý) 1-3:</th><td>' +
		'<select class="form-select led_loading_effect border-success">' +
		'<option value="1" ' + (device.led_config?.led_loading_effect === 1 ? 'selected' : '') + '>Hiệu Ứng 1</option>' +
		'<option value="2" ' + (device.led_config?.led_loading_effect === 2 ? 'selected' : '') + '>Hiệu Ứng 2</option>' +
		'<option value="3" ' + (device.led_config?.led_loading_effect === 3 ? 'selected' : '') + '>Hiệu Ứng 3</option>' +
		'</select>' +
		'</td></tr>' +
        '<tr><th>Màu LED Think/WakeUP (Hex):</th><td><div class="input-group mb-3"><input type="text" class="form-control led_think_color border-success" value="' + (device.led_config?.led_think_color || '') + '"><input type="color" class="form-control-color color-picker border-success" value="' + (device.led_config?.led_think_color ? '#' + device.led_config.led_think_color : '#000000') + '"></div></td></tr>' +
        '</tbody></table>' +

		'<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="3" style="text-align: center; vertical-align: middle;" class="text-danger">Cấu Hình Sử Dụng Nút Nhấn (Button)</th></tr></thead>' +
        '<tbody>' +
		'<tr><th>Kích Hoạt Sử Dụng Nút Nhấn:</th><td colspan="2" style="text-align: center; vertical-align: middle;"><div class="form-switch">' +
		'<input type="checkbox" class="form-check-input button_active border-success" ' + (device.button.button_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Nút Nhấn Mic (Bật/Tắt Sử Dụng WakeUP Bằng Giọng Nói) GPIO Pin:</th><td colspan="2" style="text-align: center; vertical-align: middle;"><input type="number" class="form-control button_mic_gpio border-success" value="' + (device.button?.button_mic_gpio || '') + '"></td></tr>' +
        '<tr><th>Nút Nhấn WakeUp (Đánh Thức Bằng Nút Nhấn) GPIO Pin:</th><td colspan="2" style="text-align: center; vertical-align: middle;"><input type="number" class="form-control button_wakeup_gpio border-success" value="' + (device.button?.button_wakeup_gpio || '') + '"></td></tr>' +
        '<tr><th>Thời Gian Nhấn (ms) 1000=1s:</th><td colspan="2" style="text-align: center; vertical-align: middle;"><input type="number" class="form-control button_debounce_delay border-success" value="' + (device.button?.button_debounce_delay || '') + '"></td></tr>' +
        '<tr><th colspan="3" style="text-align: center; vertical-align: middle;" class="text-danger">Cấu Hình Nhấn Giữ Nút</th></tr>' +
		'<tr><th style="text-align: center; vertical-align: middle;" class="text-danger">Chức Năng/Cấu Hình Nhấn Giữ</th><th style="text-align: center; vertical-align: middle;" class="text-danger">Cấu Hình Nhấn Giữ Nút WakeUp</th>' +
		'<th style="text-align: center; vertical-align: middle;" class="text-danger">Cấu Hình Nhấn Giữ Nút MIC</th></tr>' +
        '<tr><th>Kích Hoạt Nhấn Giữ:</th>' +
            '<td style="text-align: center; vertical-align: middle;"><div class="form-switch"><input type="checkbox" class="form-check-input button_wakeup_long_press_active border-success" ' + (device.button?.button_wakeup_long_press_active ? 'checked' : '') + '></div></td>' +
            '<td style="text-align: center; vertical-align: middle;"><div class="form-switch"><input type="checkbox" class="form-check-input button_mic_long_press_active border-success" ' + (device.button?.button_mic_long_press_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Hành Động Thực Hiện Nhấn Giữ:</th>' +
            '<td style="text-align: center; vertical-align: middle;"><select class="form-select button_wakeup_long_press_action border-success">' +
                '<option value="0" ' + (device.button?.button_wakeup_long_press_action === 0 ? 'selected' : '') + '>Bật/Tắt Mic</option>' +
                '<option value="1" ' + (device.button?.button_wakeup_long_press_action === 1 ? 'selected' : '') + '>Khởi Động Lại</option>' +
                '<option value="2" ' + (device.button?.button_wakeup_long_press_action === 2 ? 'selected' : '') + '>Dừng Phát Âm Thanh</option>' +
            '</select></td>' +
            '<td style="text-align: center; vertical-align: middle;"><select class="form-select button_mic_long_press_action border-success">' +
                '<option value="0" ' + (device.button?.button_mic_long_press_action === 0 ? 'selected' : '') + '>Bật/Tắt Mic</option>' +
                '<option value="1" ' + (device.button?.button_mic_long_press_action === 1 ? 'selected' : '') + '>Khởi Động Lại</option>' +
                '<option value="2" ' + (device.button?.button_mic_long_press_action === 2 ? 'selected' : '') + '>Dừng Phát Âm Thanh</option>' +
            '</select></td></tr>' +
        '<tr><th>Thời Gian Giữ Nút (ms) 1000=1s:</th>' +
            '<td style="text-align: center; vertical-align: middle;"><input type="number" class="form-control button_wakeup_long_press_time border-success" value="' + (device.button?.button_wakeup_long_press_time || '') + '"></td>' +
            '<td style="text-align: center; vertical-align: middle;"><input type="number" class="form-control button_mic_long_press_time border-success" value="' + (device.button?.button_mic_long_press_time || '') + '"></td></tr>' +
        '</tbody></table>' +

        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;" class="text-danger">Thiết Lập Tùy Chọn Khác</th></tr></thead>' +
        '<tbody>' +
        '<tr><th>Hiển Thị Logs Cổng Serial:</th><td style="text-align: center; vertical-align: middle;"><div class="form-switch"><input type="checkbox" class="form-check-input logs_serial_active border-success" ' + (device.other_settings.logs_serial_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Kích Hoạt Chế độ hội thoại:</th><td style="text-align: center; vertical-align: middle;"><div class="form-switch"><input type="checkbox" class="form-check-input conversation_active border-success" ' + (device.other_settings.conversation_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Âm Thanh Khi Khởi Động:</th><td style="text-align: center; vertical-align: middle;"><div class="form-switch"><input type="checkbox" class="form-check-input sound_startup border-success" ' + (device.other_settings.sound_startup ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Kích Hoạt Mic (Mic Mute On/Off):</th><td style="text-align: center; vertical-align: middle;"><div class="form-switch"><input type="checkbox" class="form-check-input mic_mutex_active border-success" ' + (device.other_settings.mic_mutex_active ? 'checked' : '') + '></div></td></tr>' +
        '<tr><th>Kích Hoạt Sử Dụng Loa:</th><td style="text-align: center; vertical-align: middle;"><div class="form-switch"><input type="checkbox" class="form-check-input speaker_active border-success" ' + (device.other_settings.speaker_active ? 'checked' : '') + '></div></td></tr>' +
        '</tbody></table>' +

		'<table class="config-table table table-bordered border-primary"><tbody><tr><th colspan="2" class="text-danger"><center>Music Player URL Local http .mp3</center></th></tr>' +
		'<tr><th>Play Audio Link/URL <i class="bi bi-question-circle-fill" onclick="show_message(\'Chỉ hỗ trợ tệp âm thanh .mp3 và là URL Local trong mạng nội bộ, ví dụ URL: http://192.168.14.194/1.mp3\')"></i>:</th><td><div class="input-group mb-3"><input class="form-control border-success" type="text" id="audioUrl" placeholder="Nhập Link/URL Local âm thanh .mp3">' +
		'<button type="button" title="Phát âm thanh từ Link/URL Local" class="btn btn-success border-success" onclick="playAudioFromUrl(\'' + (device.ip_address || '') + '\', \'\')"><i class="bi bi-play-circle"></i></button></div></td></tr>' +
		'</tbody></table>' +
		'<div class="client_music_player" id="client_music_player"></div>' +
        '<table class="config-table table table-bordered border-primary">' +
        '<thead><tr><th colspan="2" style="text-align: center; vertical-align: middle;" class="text-danger">Sao Lưu / Khôi Phục, Nâng Cấp Firmware OTA</th></tr></thead>' +
        '<tbody>' +
        '<tr><th>Nâng Cấp Firmware Tự Động OTA:</th>' +
        '<td style="text-align: center; vertical-align: middle;">' +
        '<button type="button" class="btn btn-success" onclick="start_upgrade_firmware(\'' + (device.ip_address || '') + '\', \'' + (device.firmware_url || '') + '\')"><i class="bi bi-box-arrow-in-up"></i> Nâng Cấp Firmware Tự Động</button>' +
        '</td></tr>' +
        '<tr><th>Nâng Cấp Firmware Thủ Công <a href="https://github.com/marion001/VBot_Client_Offline" target="_blank"><i class="bi bi-github"></i></a>:</th>' +
        '<td style="text-align: center; vertical-align: middle;"><div class="input-group mb-3"><input class="form-control client_name border-success" type="file" id="manual_upgrade_firmware" name="manual_upgrade_firmware" accept=".bin">' +
        '<button type="button" class="btn btn-success" onclick="start_upgrade_firmware_manual(\'' + (device.ip_address || '') + '\')"><i class="bi bi-box-arrow-in-up"></i> Nâng Cấp</button>' +
        '</div></td></tr>' +
        '<tr><th>Khôi Phục Cấu Hình Cài Đặt (.json):</th>' +
        '<td style="text-align: center; vertical-align: middle;"><div class="input-group mb-3"><input class="form-control client_name border-success" type="file" id="configFile_restore" accept=".json">' +
        '<button type="button" onclick="upload_restore_settings(\'' + (device.ip_address || '') + '\')" class="btn btn-success"><i class="bi bi-filetype-json"></i> Khôi Phục Cấu Hình</button>' +
		'<button type="button" class="btn btn-primary" onclick="downloadConfig(\'' + (device.ip_address || '') + '\')"><i class="bi bi-download"></i> Tải Xuống Tệp Cấu Hình .json</button>' +
        '</div></td></tr>' +
        '</tbody></table><hr/>' +

        '<center>' +
            '<button type="button" class="btn btn-warning" onclick="ctrl_act_vbot_client(\'' + (device.ip_address || '') + '\', \'restart_esp\')"><i class="bi bi-arrow-counterclockwise"></i> Restart ESP</button>' +
            ' <button type="button" class="btn btn-info" onclick="ctrl_act_vbot_client(\'' + (device.ip_address || '') + '\', \'reset_wifi_esp\')"><i class="bi bi-wifi-off"></i> Reset Cấu Hình Wifi</button>' +
            ' <button type="button" class="btn btn-danger" onclick="ctrl_act_vbot_client(\'' + (device.ip_address || '') + '\', \'cleanNVS\')"> <i class="bi bi-recycle"></i> Reset Toàn Bộ Dữ Liệu</button>' +
        '</center>';

    document.getElementById('modalContent').innerHTML = modalContent;
    const textInput = document.querySelector('.led_think_color');
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
	//lấy dữ liệu âm thanh local đẩy lên bảng Client
	fetchMusicList(device.ip_address);
}

//Đẩy dữ liệu âm thanh Local vào Client
function fetchMusicList(ip_address) {
    var url = 'includes/php_ajax/Media_Player_Search.php?Local';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.responseType = 'json';
    xhr.onload = function () {
        if (xhr.status === 200) {
            var data = xhr.response;
            var container = document.getElementById('client_music_player');
            var html = '<table class="config-table table table-bordered border-primary">' +
                       '<thead>' +
                       '<tr>' +
                       '<th class="text-danger"><center>STT</center></th>' +
                       '<th class="text-danger"><center>Danh Sách Bài Hát Có Trong Nguồn Nhạc Local</center></th>' +
                       '<th class="text-danger"><center>Hành Động</center></th>' +
                       '</tr>' +
                       '</thead>' +
                       '<tbody>';
            for (var i = 0; i < data.length; i++) {
                var item = data[i];
                html += '<tr>' +
                        '<th><center>' + (i + 1) + '</center></th>' +
                        '<td>' + item.name + '</td>' +
                        '<td><center><button title="Phát bài hát: ' + item.name + '" class="btn btn-success" onclick="playAudioFromUrl(\'' + ip_address + '\', \'http://<?php echo $serverIp;?>/assets/sound/Music_Local/'+item.name+'\')"><i class="bi bi-play-circle"></i></button> ' +
						' <button class="btn btn-warning" title="Tải Xuống: ' + item.name + '" onclick="downloadFile(\'' + item.full_path + '\')"><i class="bi bi-download"></i></button>' +
						'</center></td>' +
                        '</tr>';
            }
            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            console.error('Lỗi khi lấy dữ liệu:', xhr.statusText);
        }
    };
    xhr.onerror = function () {
        console.error('Yêu cầu thất bại.');
    };
    xhr.send();
}

// Tải lại dữ liệu cho Client riêng lẻ
function loading_data_client(ip_address, time_out = 10000) {
    loading('show');
    setTimeout(function() {
        const phpUrl = '/includes/php_ajax/Scanner.php';
        const formData = new FormData();
        formData.append('showJsonData_Client', ip_address);
        fetch(phpUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Không thể kết nối tới server PHP');
                }
                return response.json();
            })
            .then(response => {
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
                        const modal = document.getElementById('clientConfigModal');
                        const isModalOpen = modal.classList.contains('show');
                        const modalIp = modal.querySelector('#clientConfigModalLabel font')?.textContent?.includes(ip_address);
                        if (isModalOpen && modalIp) {
                            showConfigModal(updatedDevice);
                        }
                    });
                } else {
                    throw new Error(response.error || 'Tải lại dữ liệu thất bại');
                }
            })
            .catch(error => {
                loading('hide');
                showMessagePHP('Lỗi: ' + error.message, 5);
            });
    }, time_out);
}

//Lưu cấu hình Config theo từng Client
function saveConfig(ip_address) {
    loading('show');
    const config = {
        ip_address: ip_address,
        client_name: document.querySelector('.client_name').value,
        server: {
            vbot_server_ip: document.querySelector('.vbot_server_ip').value,
            vbot_server_port: parseInt(document.querySelector('.vbot_server_port').value) || 0
        },
        i2s_config: {
            i2s_sck_bclk_gpio: parseInt(document.querySelector('.i2s_sck_bclk_gpio').value) || 0,
            i2s_ws_lrc_gpio: parseInt(document.querySelector('.i2s_ws_lrc_gpio').value) || 0,
            i2s_sd_gpio: parseInt(document.querySelector('.i2s_sd_gpio').value) || 0,
            i2s_din_gpio: parseInt(document.querySelector('.i2s_din_gpio').value) || 0,
            i2s_gain_mic: parseFloat(document.querySelector('.i2s_gain_mic').value) || 0,
            i2s_mic_channel: document.querySelector('.i2s_mic_channel').value || 'left',
            i2s_speaker_volume: parseFloat(document.querySelector('.i2s_speaker_volume').value) || 0
        },
        led_config: {
            led_active: document.querySelector('.led_active').checked,
            led_gpio: parseInt(document.querySelector('.led_gpio').value) || 0,
            led_number: parseInt(document.querySelector('.led_number').value) || 0,
            led_brightness: parseInt(document.querySelector('.led_brightness').value) || 0,
            led_loading_effect: parseInt(document.querySelector('.led_loading_effect').value) || 0,
            led_think_color: document.querySelector('.led_think_color').value
        },
        button: {
            button_active: document.querySelector('.button_active').checked,
            button_wakeup_gpio: parseInt(document.querySelector('.button_wakeup_gpio').value) || 0,
            button_mic_gpio: parseInt(document.querySelector('.button_mic_gpio').value) || 0,
            button_debounce_delay: parseInt(document.querySelector('.button_debounce_delay').value) || 200,
            button_wakeup_long_press_active: document.querySelector('.button_wakeup_long_press_active').checked,
            button_mic_long_press_active: document.querySelector('.button_mic_long_press_active').checked,
            button_wakeup_long_press_action: parseInt(document.querySelector('.button_wakeup_long_press_action').value) || 0,
            button_mic_long_press_action: parseInt(document.querySelector('.button_mic_long_press_action').value) || 0,
            button_wakeup_long_press_time: parseInt(document.querySelector('.button_wakeup_long_press_time').value) || 200,
            button_mic_long_press_time: parseInt(document.querySelector('.button_mic_long_press_time').value) || 2000
        },
        other_settings: {
            logs_serial_active: document.querySelector('.logs_serial_active').checked,
            conversation_active: document.querySelector('.conversation_active').checked,
            sound_startup: document.querySelector('.sound_startup').checked,
            mic_mutex_active: document.querySelector('.mic_mutex_active').checked,
            speaker_active: document.querySelector('.speaker_active').checked
        }
    };

    // Kiểm tra dữ liệu đầu vào
    if (!config.client_name || !config.server.vbot_server_ip) {
        loading('hide');
        showMessagePHP('Vui lòng điền đầy đủ thông tin bắt buộc', 5);
        return;
    }

    // Kiểm tra định dạng màu HEX
    const hexColorPattern = /^[0-9A-Fa-f]{6}$/;
    if (!hexColorPattern.test(config.led_config.led_think_color)) {
        config.led_config.led_think_color = '0000ff';
    }

    // Log để debug giá trị long press action
    //console.log('WakeUp Long Press Action:', config.button.button_wakeup_long_press_action);
    //console.log('MIC Long Press Action:', config.button.button_mic_long_press_action);

    //dữ liệu gửi
    const formData = new FormData();
    formData.append('client_save_config', ip_address);
    formData.append('clientName', config.client_name);
    formData.append('udp_server', config.server.vbot_server_ip);
    formData.append('udp_server_port', config.server.vbot_server_port);
    formData.append('i2s_sck_bclk', config.i2s_config.i2s_sck_bclk_gpio);
    formData.append('i2s_ws_lrc', config.i2s_config.i2s_ws_lrc_gpio);
    formData.append('i2s_dout', config.i2s_config.i2s_din_gpio);
    formData.append('i2s_sd', config.i2s_config.i2s_sd_gpio);
    formData.append('gain_mic', config.i2s_config.i2s_gain_mic);
    formData.append('i2s_slot_mask', config.i2s_config.i2s_mic_channel);
    formData.append('volume_level', config.i2s_config.i2s_speaker_volume);
    formData.append('gpio_ws2812b', config.led_config.led_gpio);
    formData.append('num_pixels', config.led_config.led_number);
    formData.append('brightness', config.led_config.led_brightness);
    formData.append('loading_effect', config.led_config.led_loading_effect);
    formData.append('LED_THINK_COLOR', config.led_config.led_think_color);
    formData.append('gpio_button_mic', config.button.button_mic_gpio);
    formData.append('gpio_button_wakeup', config.button.button_wakeup_gpio);
    formData.append('DEBOUNCE_DELAY', config.button.button_debounce_delay);
    formData.append('longPressOption', config.button.button_wakeup_long_press_action);
    formData.append('micLongPressOption', config.button.button_mic_long_press_action);
    formData.append('longPressDuration', config.button.button_wakeup_long_press_time);
    formData.append('micLongPressDuration', config.button.button_mic_long_press_time);

    // Chỉ thêm các tham số boolean nếu giá trị là true
    if (config.led_config.led_active) formData.append('ledActive', 'on');
    if (config.button.button_active) formData.append('button_active', 'on');
    if (config.button.button_wakeup_long_press_active) formData.append('longPressActive', 'on');
    if (config.button.button_mic_long_press_active) formData.append('micLongPressActive', 'on');
    if (config.other_settings.logs_serial_active) formData.append('logsActive', 'on');
    if (config.other_settings.conversation_active) formData.append('conversation_mode_active', 'on');
    if (config.other_settings.sound_startup) formData.append('sound_on_startup', 'on');
    if (config.other_settings.mic_mutex_active) formData.append('micActive', 'on');
    if (config.other_settings.speaker_active) formData.append('speakerActive', 'on');

    fetch('/includes/php_ajax/VBot_Client_Upgrade_Firmware.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Không thể kết nối tới server PHP');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Lưu cấu hình thất bại cho ' + ip_address);
            }
            const infoFormData = new FormData();
            infoFormData.append('showJsonData_Client', ip_address);
            return fetch('/includes/php_ajax/Scanner.php', {
                method: 'POST',
                body: infoFormData
            })
                .then(infoResponse => {
                    if (!infoResponse.ok) {
                        throw new Error('Không thể kết nối tới server PHP để lấy thông tin client');
                    }
                    return infoResponse.json();
                })
                .then(infoData => {
                    loading('hide');
                    if (infoData.success) {
                        showMessagePHP('Lưu cấu hình thành công cho ' + ip_address + '|' + config.client_name, 5);
                        loadFromServer().then(devices => {
                            const updatedDevice = infoData;
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
                        throw new Error(infoData.error || 'Lấy thông tin client thất bại');
                    }
                });
        })
        .catch(error => {
            loading('hide');
            showMessagePHP('Lỗi: ' + error.message, 5);
        });
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

//Phát âm thanh bài hát
function playAudioFromUrl(ip_address, link_url) {
    // Kiểm tra nếu link_url không có dữ liệu thì lấy dữ liệu từ input
    const audioUrl = link_url || document.getElementById('audioUrl').value;
    if (!audioUrl) {
        show_message('Vui lòng nhập URL âm thanh .mp3');
        return;
    }
    loading('show');
    const formData = new FormData();
    formData.append('client_play_audio', ip_address);
    formData.append('url', audioUrl);
    fetch('/includes/php_ajax/VBot_Client_Upgrade_Firmware.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Không thể kết nối tới server PHP');
            }
            return response.json();
        })
        .then(data => {
            loading('hide');
            if (data.success) {
                showMessagePHP(data.message || 'Phát âm thanh thành công', 5);
            } else {
                throw new Error(data.error || 'Lỗi khi phát âm thanh');
            }
        })
        .catch(error => {
            loading('hide');
            show_message('Lỗi: ' + error.message);
        });
}


// Hàm gửi yêu cầu control tới VBot Client
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
    let control_action;
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
    const formData = new FormData();
    formData.append('client_ctrl_act_vbot', ip_address);
    formData.append('action', control_action);
    fetch('/includes/php_ajax/VBot_Client_Upgrade_Firmware.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Không thể kết nối tới server PHP');
            }
            return response.json();
        })
        .then(data => {
            loading('hide');
            if (data.success) {
                showMessagePHP('Đã gửi yêu cầu ' + control_action + ' cho: ' + ip_address, 5);
            } else {
                throw new Error(data.error || 'Gửi yêu cầu thất bại');
            }
        })
        .catch(error => {
            loading('hide');
            showMessagePHP('Lỗi: ' + error.message, 5);
        });
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
    formData.append('client_upload_restore_settings', ip_address);
    formData.append('config_file', file);
    fetch('/includes/php_ajax/VBot_Client_Upgrade_Firmware.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Không thể kết nối tới server PHP');
            }
            return response.json();
        })
        .then(data => {
            loading('hide');
            if (data.success) {
                showMessagePHP("Đã tải lên tệp khôi phục cấu hình thành công!", 5);
                loading_data_client(ip_address, 2000);
            } else {
                throw new Error(data.error || 'Tải lên tệp khôi phục cấu hình thất bại!');
            }
        })
        .catch(error => {
            loading('hide');
            showMessagePHP('Lỗi: ' + error.message, 5);
        });
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
        const phpUrl = '/includes/php_ajax/Scanner.php';
        const formData = new FormData();
        formData.append('showJsonData_Client', ip);
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);
        const response = await fetch(phpUrl, {
            method: 'POST',
            body: formData,
            signal: controller.signal
        });
        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error('Không thể kết nối tới server PHP');
        }
        const data = await response.json();
        const statusDot = document.getElementById('status-' + index);
        if (data.success) {
            if (statusDot) {
                statusDot.classList.remove('offline');
                statusDot.classList.add('online');
                statusDot.title = 'Trực Tuyến';
            }
            if (showNotification) {
                showMessagePHP('<p class="text-success"><b>Thiết bị ' + ip + ' đang Trực Tuyến</b></p>', 3);
            }
        } else {
            throw new Error(data.error || 'Thiết bị ngoại tuyến');
        }
        loading('hide');
    } catch (error) {
        const statusDot = document.getElementById('status-' + index);
        if (statusDot) {
            statusDot.classList.remove('online');
            statusDot.classList.add('offline');
            statusDot.title = 'Ngoại Tuyến';
        }
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

//Hàm hiển thị dữ liệu JSON trong modal
function showJsonData(ip_address) {
    loading('show');
    const phpUrl = '/includes/php_ajax/Scanner.php';
    const formData = new FormData();
    formData.append('showJsonData_Client', ip_address);
    fetch(phpUrl, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Không thể kết nối tới server PHP');
            }
            return response.json();
        })
        .then(data => {
            loading('hide');
            if (!data.success) {
                throw new Error(data.error || 'Không thể lấy dữ liệu từ client');
            }
            const formattedJson = JSON.stringify(data, null, 2);
            document.getElementById('jsonContent').innerHTML = '<code class="language-json">' + formattedJson + '</code>';
            Prism.highlightAllUnder(document.getElementById('jsonContent'));
            const jsonModal = new bootstrap.Modal(document.getElementById('jsonDisplayModal'));
            jsonModal.show();
        })
        .catch(error => {
            loading('hide');
            showMessagePHP('Lỗi khi lấy dữ liệu JSON: ' + error.message, 5);
            document.getElementById('jsonContent').innerHTML = '<code class="language-json">Không thể tải dữ liệu JSON.</code>';
            Prism.highlightAllUnder(document.getElementById('jsonContent'));
            const jsonModal = new bootstrap.Modal(document.getElementById('jsonDisplayModal'));
            jsonModal.show();
        });
}

function downloadConfig(ip_address) {
    loading('show'); // Hiển thị trạng thái đang tải

    // Kiểm tra ip_address
    if (!ip_address || typeof ip_address !== 'string' || ip_address.trim() === '') {
        show_message('Địa chỉ IP không hợp lệ hoặc rỗng');
        loading('hide');
        return;
    }

    const formData = new FormData();
    formData.append('client_download_config', ip_address);

    fetch('/includes/php_ajax/VBot_Client_Upgrade_Firmware.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Không thể kết nối tới server PHP');
            }
            return response.json();
        })
        .then(data => {
            loading('hide');
            if (data.success) {
                const blob = new Blob([data.content], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = data.filename || 'vbot_config_client.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                showMessagePHP('Tải xuống tệp cấu hình thành công', 5);
            } else {
                throw new Error(data.error || 'Tải xuống tệp cấu hình thất bại');
            }
        })
        .catch(error => {
            loading('hide');
            show_message('Lỗi: ' + error.message);
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
<script src="assets/vendor/prism/prism.min.js"></script>
<script src="assets/vendor/prism/prism-json.min.js"></script>
<?php include 'html_js.php'; ?>
</body>
</html>