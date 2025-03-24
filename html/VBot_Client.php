<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
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
        overflow-x: hidden;
        overflow-y: auto;
        white-space: nowrap;
        cursor: grab;
        user-select: none;
        position: relative;
    }
    #vbot_Client_Scan_devices:active {
        cursor: grabbing;
    }
    #vbot_Client_Scan_devices table {
        min-width: 100%;
        display: inline-block;
    }
    .table-bordered td {
        padding: 5px;
    }
    .header-break {
        white-space: normal;
        word-wrap: break-word;
        max-width: 150px;
    }
    .version, .chip-model {
        white-space: normal;
        word-wrap: break-word;
        max-width: 150px;
    }
    .text-nowrap {
        white-space: nowrap;
    }
    .label-fixed-width {
        display: inline-block;
        width: 175px;
        text-align: right;
    }
    .label-fixed-width-led {
        display: inline-block;
        width: 155px;
        text-align: right;
    }
    .label-fixed-width-button {
        display: inline-block;
        width: 155px;
        text-align: right;
    }
    .label-fixed-width-status {
        display: inline-block;
        width: 120px;
        text-align: right;
    }
    .table-bordered td {
        padding: 5px;
        vertical-align: middle;
    }
    .version-wrap {
        white-space: normal;
        word-wrap: break-word;
        max-width: 150px;
        overflow-wrap: break-word;
    }
    .version-wrap .badge {
        display: block;
        margin-top: 5px;
    }
</style>
<body>
<!-- ======= Header ======= -->
<?php include 'html_header_bar.php'; ?>
<!-- End Header -->

<!-- ======= Sidebar ======= -->
<?php include 'html_sidebar.php'; ?>
<!-- End Sidebar-->

<main id="main" class="main">
    <div class="pagetitle">
        <h1>VBot Client Management <a title="Truy Cập" href="https://github.com/marion001/VBot_Client_Offline" target="_blank"><i class="bi bi-github"></i></a></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
                <li class="breadcrumb-item">VBot Client</li>
				 &nbsp;| Trạng Thái Kích Hoạt VBot Server: <?php echo $Config['api']['streaming_server']['active'] ? '<p class="text-success" title="VBot Server đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="VBot Server không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
            </ol>
        </nav>
    </div><!-- End Page Title -->
    <section class="section"><center>
	<button type="button" class="btn btn-primary" onclick="scan_VBot_Client_Device()"><i class="bi bi-radar"></i> Quét Thiết Bị Client</button>
	<button type="button" class="btn btn-danger" onclick="clearLocalStorage()"><i class="bi bi-x-circle"></i> Xóa Dữ Liệu Quét Trước Đó</button>
	</center>
	<hr/>
        <div class="row">
            <div id="vbot_Client_Scan_devices"></div>
        </div>
    </section>
</main>

<!-- ======= Footer ======= -->
<?php include 'html_footer.php'; ?>
<!-- End Footer -->

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script>
// Quét các thiết bị sử dụng VBot Client trong cùng lớp mạng
function scan_VBot_Client_Device() {
    showMessagePHP("Đang tìm kiếm các thiết bị chạy VBot Client trong mạng", 5);
    loading('show');
    const url = "includes/php_ajax/Scanner.php?VBot_Client_Device_Scaner";
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            loading('hide');
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const data = response.data;
                        if (Array.isArray(data) && data.length > 0) {
                            data.sort(function(a, b) {
                                const aHasData = a.ip_address && a.project_name;
                                const bHasData = b.ip_address && b.project_name;
                                return (aHasData && !bHasData) ? -1 : ((!aHasData && bHasData) ? 1 : 0);
                            });
                            displayDeviceData(data);
                            localStorage.setItem('vbot_client_devices', JSON.stringify(data));
                            showMessagePHP("Đã hoàn tất tìm kiếm các thiết bị VBot Client trong mạng", 5);
                        } else {
                            document.getElementById("vbot_Client_Scan_devices").innerHTML = "Không tìm thấy thiết bị nào.";
                        }
                    } else {
                        showMessagePHP("Đã xảy ra lỗi: " + response.messager);
                    }
                } catch (error) {
                    document.getElementById("vbot_Client_Scan_devices").innerHTML = "Đã xảy ra lỗi khi xử lý dữ liệu: " + xhr.responseText;
                }
            } else {
                document.getElementById("vbot_Client_Scan_devices").innerHTML = "Không thể kết nối tới máy chủ: " + xhr.status;
            }
        }
    };
    xhr.send();
}

// Hàm hiển thị dữ liệu lên bảng
function displayDeviceData(data) {
    let tableHTML = 
        '<table class="table table-bordered border-primary" cellspacing="0" cellpadding="5">' +
            '<thead>' +
                '<tr>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Tên/IP</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Phiên Bản Client</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Chip Model</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Server IP</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Server Port</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Mic/Audio INMP441/MAX98357</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Cấu Hình Led WS2812B</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Cấu Hình Nút Nhấn</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Trạng Thái</th>' +
                    '<th class="header-break" style="text-align: center; vertical-align: middle;">Hành Động</th>' +
                '</tr>' +
            '</thead>' +
            '<tbody>';
    data.forEach((device, index) => {
        tableHTML += 
            '<tr data-device-index="' + index + '">' +
                '<th style="text-align: center; vertical-align: middle;" class="device-ip"><font color=blue><a title="Mở Client trong Tab mới" href="http://' + (device.ip_address || '') + '" target="_blank">' + (device.ip_address || '') + '</a></font><br/><br/><input style="width: 150px;" type="text" placeholder="Đặt Tên Client" class="form-control border-success client-name" value="' + (device.client_name || '') + '"></th>' +
                '<td style="text-align: center; vertical-align: middle;" class="version">' + (device.version || '') + '</td>' +
                '<td style="text-align: center; vertical-align: middle;" class="chip-model">' + (device.chip_model || '') + '</td>' +
                '<td style="text-align: center; vertical-align: middle;"><input style="width: 140px;" type="text" class="form-control border-success server-ip" value="' + (device.server?.vbot_server_ip || '') + '"></td>' +
                '<td style="text-align: center; vertical-align: middle;"><input style="width: 90px;" type="number" class="form-control border-success server-port" value="' + (device.server?.vbot_server_port || '') + '"></td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                    '<table class="table table-bordered mb-0">' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width me-2">SCK/BCLK GPIO:</span><input style="width: 76px;" type="number" step="1" class="form-control border-success gpio-sck" value="' + (device.i2s_config?.gpio_sck_bclk || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width me-2">WS/LRC GPIO:</span><input style="width: 76px;" type="number" step="1" class="form-control border-success gpio-ws" value="' + (device.i2s_config?.gpio_ws_lrc || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width me-2">INMP441=SD GPIO:</span><input style="width: 76px;" type="number" step="1" class="form-control border-success gpio-sd" value="' + (device.i2s_config?.gpio_sd || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width me-2">MAX98357=DIN GPIO:</span><input style="width: 76px;" type="number" step="1" class="form-control border-success gpio-din" value="' + (device.i2s_config?.gpio_din || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width me-2">Mic Gain:</span><input style="width: 76px;" type="number" step="0.1" class="form-control border-success gain-mic" value="' + (device.i2s_config?.gain_mic || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width me-2">Volume Gain:</span><input style="width: 76px;" type="number" step="0.1" class="form-control border-success gain-volume" value="' + (device.i2s_config?.gain_volume || '') + '"></div></td></tr>' +
                    '</table>' +
                '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                    '<table class="table table-bordered mb-0">' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-led me-2">GPIO Pin:</span><input type="number" step="1" class="form-control border-success led-pin" value="' + (device.led_config?.gpio_d0_pin || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-led me-2">Số Led:</span><input type="number" step="1" class="form-control border-success led-pixels" value="' + (device.led_config?.num_pixels || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-led me-2">Độ Sáng:</span><input type="number" step="1" class="form-control border-success led-bright" value="' + (device.led_config?.brightness || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-led me-2">Hiệu Ứng Loading:</span><input type="number" step="1" min="1" max="3" class="form-control border-success led-loading" value="' + (device.led_config?.loading_effect || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-led me-2">Màu Led Think:</span>' +
						'<input style="width: 125px;" type="text" class="form-control border-success led-think" value="' + (device.led_config?.think_color || '') + '">' +
						'<input type="color" class="form-control color-picker ms-2 border-success" value="' + (device.led_config?.think_color ? '#' + device.led_config.think_color : '#000000') + '">' +
					'</div></td></tr>' +
                    '</table>' +
                '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                    '<table class="table table-bordered mb-0">' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-button me-2">WakeUP GPIO:</span><input style="width: 76px;" type="number" step="1" class="form-control border-success button-wake" value="' + (device.button?.gpio_wake_up || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-button me-2">Mic GPIO:</span><input style="width: 76px;" type="number" step="1" class="form-control border-success button-mic" value="' + (device.button?.gpio_mic || '') + '"></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-button me-2">Thời Gian Nhấn (ms):</span><input style="width: 76px;" type="number" step="1" class="form-control border-success button-delay" value="' + (device.button?.debounce_delay || '') + '"></div></td></tr>' +
                    '</table>' +
                '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                    '<table class="table table-bordered mb-0">' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-status me-2">WakeUP Active:</span><input type="checkbox" class="form-check-input wake-active border-success" ' + (device.button?.wakeup_active ? 'checked' : '') + '></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-status me-2">Mic Active:</span><input type="checkbox" class="form-check-input mic-active border-success" ' + (device.button?.mic_active ? 'checked' : '') + '></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-status me-2">Speaker Active:</span><input type="checkbox" class="form-check-input speaker-active border-success" ' + (device.i2s_config?.speaker_active ? 'checked' : '') + '></div></td></tr>' +
                        '<tr><td><div class="d-flex align-items-center"><span class="label-fixed-width-status me-2">Serial Log Active:</span><input type="checkbox" class="form-check-input serial-log border-success" ' + (device.logs_serial_active ? 'checked' : '') + '></div></td></tr>' +
                    '</table>' +
                '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                    '<button type="button" class="btn btn-success save-config-btn">Lưu Cấu Hình</button><br/><br/>' +
					'<button type="button" class="btn btn-warning" onclick="restart_vbot_client(\'' + device.ip_address + '\', \'restart_esp\')">Restart Client</button><br/><br/>' +
					'<button type="button" class="btn btn-info" onclick="restart_vbot_client(\'' + device.ip_address + '\', \'reset_wifi_esp\')">Reset Wifi</button><br/><br/>' +
					'<button type="button" class="btn btn-danger" onclick="restart_vbot_client(\'' + device.ip_address + '\', \'cleanNVS\')">Reset Dữ Liệu</button>' +
                '</td>' +
            '</tr>';
    });
    tableHTML += '</tbody></table>';
    document.getElementById("vbot_Client_Scan_devices").innerHTML = tableHTML;
	// Gắn sự kiện cho các nút và đồng bộ màu sau khi bảng được tạo
    document.querySelectorAll('.save-config-btn').forEach((button) => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const config = {
                ip_address: row.querySelector('.device-ip a').textContent,
                client_name: row.querySelector('.client-name').value,
                server: {
                    vbot_server_ip: row.querySelector('.server-ip').value,
                    vbot_server_port: parseInt(row.querySelector('.server-port').value) || 0
                },
                i2s_config: {
                    gpio_sck_bclk: parseInt(row.querySelector('.gpio-sck').value) || 0,
                    gpio_ws_lrc: parseInt(row.querySelector('.gpio-ws').value) || 0,
                    gpio_sd: parseInt(row.querySelector('.gpio-sd').value) || 0,
                    gpio_din: parseInt(row.querySelector('.gpio-din').value) || 0,
                    gain_mic: parseFloat(row.querySelector('.gain-mic').value) || 0,
                    gain_volume: parseFloat(row.querySelector('.gain-volume').value) || 0,
                    speaker_active: row.querySelector('.speaker-active').checked
                },
                led_config: {
                    gpio_d0_pin: parseInt(row.querySelector('.led-pin').value) || 0,
                    num_pixels: parseInt(row.querySelector('.led-pixels').value) || 0,
                    brightness: parseInt(row.querySelector('.led-bright').value) || 0,
                    loading_effect: parseInt(row.querySelector('.led-loading').value) || 0,
                    think_color: row.querySelector('.led-think').value || ''
                },
                button: {
                    gpio_wake_up: parseInt(row.querySelector('.button-wake').value) || 0,
                    gpio_mic: parseInt(row.querySelector('.button-mic').value) || 0,
                    debounce_delay: parseInt(row.querySelector('.button-delay').value) || 0,
                    wakeup_active: row.querySelector('.wake-active').checked,
                    mic_active: row.querySelector('.mic-active').checked
                },
                logs_serial_active: row.querySelector('.serial-log').checked
            };
            save_vbot_client_config(config);
        });
    });
    // Gọi hàm đồng bộ màu sau khi bảng được hiển thị
    syncColorInputs();
	// Kiểm tra phiên bản sau khi hiển thị bảng
    checkAndDisplayVersionUpdate(data);
    const container = document.getElementById('vbot_Client_Scan_devices');
    let isDragging = false;
    let startX, scrollLeft;
    container.style.overflowX = 'scroll';
    container.style.overflowY = 'auto';
    container.addEventListener('mousedown', (e) => {
        isDragging = true;
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
        container.style.cursor = 'grabbing';
    });
    container.addEventListener('mouseleave', () => {
        isDragging = false;
        container.style.cursor = 'grab';
    });
    container.addEventListener('mouseup', () => {
        isDragging = false;
        container.style.cursor = 'grab';
    });
    container.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const walk = (x - startX) * 2;
        container.scrollLeft = scrollLeft - walk;
    });
}

//Hàm lưu cấu hình
function save_vbot_client_config(config) {
	loading('show');
    const pins = [
        config.i2s_config.gpio_sck_bclk,
        config.i2s_config.gpio_ws_lrc,
        config.i2s_config.gpio_sd,
        config.i2s_config.gpio_din,
        config.led_config.gpio_d0_pin,
        config.button.gpio_mic,
        config.button.gpio_wake_up
    ];
    const pinNames = [
        'SCK/BCLK (I2S)',
        'WS/LRC (I2S)',
        'INMP441=SD (I2S)',
        'MAX98357=DIN (I2S)',
        'LED WS2812B Pin',
        'Mic Button',
        'WakeUP Button'
    ];
    // Kiểm tra trùng lặp GPIO
    let errorMessage = '';
    for (let i = 0; i < pins.length - 1; i++) {
        if (pins[i] === undefined || pins[i] === null || pins[i] === 0) continue;
        for (let j = i + 1; j < pins.length; j++) {
            if (pins[j] === undefined || pins[j] === null || pins[j] === 0) continue;
            if (pins[i] === pins[j]) {
                if (errorMessage !== '') errorMessage += ', ';
                errorMessage += `${pinNames[i]} và ${pinNames[j]} (GPIO: ${pins[i]})`;
            }
        }
    }
    //Nếu có lỗi trùng GPIO, hiển thị thông báo và không gửi yêu cầu
    if (errorMessage !== '') {
		loading('hide');
		show_message('Lỗi: Không thể lưu cấu hình cho ' + config.ip_address + ' do các chân GPIO bị trùng: ' + errorMessage);
        return;
    }
    //Nếu không có lỗi, tiếp tục gửi yêu cầu POST
    const url = 'http://' + config.ip_address + '/config';
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
        '&wakeup_state=' + (config.button.wakeup_active ? 'true' : 'false');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                showMessagePHP('Lưu cấu hình thành công cho ' + config.ip_address, 5);
                fetchConfigAndUpdate(config.ip_address);
				loading('hide');
            } else if (xhr.status === 406) {
				loading('hide');
				show_message('Lỗi: Không thể lưu cấu hình cho ' + config.ip_address + ' do các chân GPIO bị trùng!');
            } else if (xhr.status !== 0) {
				loading('hide');
				show_message('Lưu thất bại cho ' + config.ip_address + ' với mã trạng thái: ' + xhr.status);
            }
        }
    };
    xhr.onerror = function () {
		loading('hide');
        showMessagePHP('Lỗi mạng: Không thể kết nối đến ' + config.ip_address, 5);
    };
    xhr.timeout = 5000;
    xhr.ontimeout = function () {
		loading('hide');
        showMessagePHP('Lỗi: Yêu cầu hết thời gian chờ cho ' + config.ip_address, 5);
    };
    xhr.send(data);
}

// Hàm gọi API để lấy cấu hình mới và cập nhật
function fetchConfigAndUpdate(ip_address) {
	if (!ip_address || typeof ip_address !== 'string' || ip_address.trim() === '') {
        show_message('Lỗi: Địa chỉ IP không hợp lệ hoặc rỗng');
        return;
    }
	loading('show');
    const apiUrl = 'http://' + ip_address + '/VBot_Client_Info';
    const xhr = new XMLHttpRequest();
    xhr.open('GET', apiUrl, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                try {
                    const configData = JSON.parse(xhr.responseText);
                    syncDeviceData(configData, ip_address);
					loading('hide');
                } catch (e) {
					loading('hide');
					show_message('Lỗi: Không thể phân tích dữ liệu cấu hình từ ' + ip_address +'Mã Lỗi: ' +e);
                }
            } else {
				loading('hide');
				show_message('Lỗi: Không thể tải cấu hình từ ' + ip_address + ' (mã trạng thái: ' + xhr.status + ')');
            }
        }
    };
    xhr.onerror = function () {
		loading('hide');
		show_message('Lỗi mạng: Không thể kết nối đến ' + ip_address + ' để tải cấu hình');
    };
	// Timeout ban đầu 2 giây
    xhr.timeout = 2000;
    xhr.ontimeout = function () {
		loading('hide');
        showMessagePHP('ESP đang khởi động lại, thử kết nối lại sau 4 giây cho ' + ip_address, 5);
        setTimeout(() => fetchConfigAndUpdate(ip_address), 2000);
    };
    xhr.send();
}

//Hàm đồng bộ dữ liệu vào localStorage và đẩy lên HTML
function syncDeviceData(configData, ip_address) {
    localStorage.setItem('vbot_config_' + ip_address, JSON.stringify(configData));
    let allDevices = JSON.parse(localStorage.getItem('vbot_client_devices')) || [];
    const deviceIndex = allDevices.findIndex(device => device.ip_address === ip_address);
    if (deviceIndex !== -1) {
        allDevices[deviceIndex] = configData;
    } else {
        allDevices.push(configData);
    }
    localStorage.setItem('vbot_client_devices', JSON.stringify(allDevices));
    displayDeviceData(allDevices);
}

// Hàm gửi yêu cầu reset tới VBot Client
function restart_vbot_client(ip_address, action) {
    if (!ip_address || typeof ip_address !== 'string' || ip_address.trim() === '') {
        show_message('Lỗi: Địa chỉ IP không hợp lệ hoặc rỗng');
        return;
    }
    if (!action || typeof action !== 'string' || action.trim() === '') {
        show_message('Lỗi: thao tác không hợp lệ hoặc rỗng');
        return;
    }
	if (action === 'restart_esp'){
		control_action = 'restart';
	}else if (action === 'reset_wifi_esp'){
		control_action = 'resetwifi';
	}
	else if (action === 'cleanNVS'){
		control_action = 'cleanNVS';
	}
	else{
		show_message('Dữ liệu thao tác không hợp lệ');
		return;
	}
    const url = 'http://' + ip_address + '/'+control_action;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            showMessagePHP('Đã gửi yêu cầu khởi động lại cho ' + ip_address, 5);
        }
    };
    xhr.onerror = function () {
        showMessagePHP('Đã gửi yêu cầu khởi động lại cho ' + ip_address, 5);
    };
    xhr.timeout = 5000;
    xhr.ontimeout = function () {
        showMessagePHP('Lỗi: Hết thời gian chờ khi gửi yêu cầu tới ' + ip_address, 5);
    };
    xhr.send();
}

// Đồng bộ giá trị giữa input text và input color
function syncColorInputs() {
    document.querySelectorAll('.led-think').forEach((textInput, index) => {
        const colorInput = document.querySelectorAll('.color-picker')[index];
        textInput.addEventListener('input', function() {
            const value = textInput.value.trim();
            if (/^[0-9A-Fa-f]{3}$|^[0-9A-Fa-f]{6}$/.test(value)) {
                colorInput.value = '#' + value;
            } else if (value === '') {
                colorInput.value = '#0000FF';
            }
        });
        colorInput.addEventListener('input', function() {
            textInput.value = colorInput.value.replace('#', '');
        });
    });
}

// Hàm lấy thông tin phiên bản từ GitHub
function fetchVersionFromGitHub(callback) {
    const url = 'https://api.github.com/repos/marion001/VBot_Client_Offline/contents/ESP32/bin/Version.json';
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    //xhr.setRequestHeader('Accept', 'application/vnd.github.v3+json');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    const content = atob(response.content);
                    const versionData = JSON.parse(content);
                    callback(versionData);
                } catch (e) {
                    console.error('Lỗi khi phân tích dữ liệu từ GitHub:', e);
                    showMessagePHP('Lỗi: Không thể lấy thông tin phiên bản từ GitHub', 5);
                    callback(null);
                }
            } else {
                showMessagePHP('Lỗi: Không thể kết nối đến GitHub API (mã trạng thái: ' + xhr.status + ')', 5);
                callback(null);
            }
        }
    };

    xhr.onerror = function () {
        showMessagePHP('Lỗi mạng: Không thể kết nối đến GitHub API', 5);
        callback(null);
    };
    xhr.send();
}

//kiểm tra và hiển thị thông báo phiên bản
function checkAndDisplayVersionUpdate(devices) {
    fetchVersionFromGitHub(function(latestVersion) {
        if (!latestVersion) return;
        const latestBuildDate = latestVersion.build_date;
        devices.forEach((device, index) => {
            const currentVersion = device.version || '';
            let versionCell = document.querySelector('tr[data-device-index="' + index + '"] .version');
            if (versionCell) {
                versionCell.classList.add('version-wrap');
                if (currentVersion !== latestBuildDate) {
                    versionCell.innerHTML = currentVersion + ' <span class="version-wrap badge bg-warning text-dark">Có phiên bản mới: <font color=red>' + latestBuildDate + '</font> <a title="Truy Cập" href="https://github.com/marion001/VBot_Client_Offline" target="_blank"><i class="bi bi-github"></i></a></span>';
                } else {
                    versionCell.innerHTML = currentVersion + ' <span class="version-wrap badge bg-success">Đã cập nhật</span>';
                }
            }
        });
    });
}

//Hàm xóa dữ liệu
function clearLocalStorage() {
    localStorage.removeItem('vbot_client_devices');
    localStorage.removeItem('vbot_devices');
    for (let key in localStorage) {
        if (key.startsWith('vbot_config_')) {
            localStorage.removeItem(key);
        }
    }
    showMessagePHP('Đã xóa toàn bộ dữ liệu trong localStorage', 5);
    document.getElementById("vbot_Client_Scan_devices").innerHTML = "<center>Không có dữ liệu lưu trữ</center>";
}

//Kiểm tra và hiển thị dữ liệu từ localStorage khi tải trang
document.addEventListener('DOMContentLoaded', function() {
    const storedData = localStorage.getItem('vbot_client_devices');
    if (storedData) {
        try {
            const data = JSON.parse(storedData);
            if (Array.isArray(data) && data.length > 0) {
                displayDeviceData(data);
            } else {
                document.getElementById("vbot_Client_Scan_devices").innerHTML = "<center>Không có dữ liệu lưu trữ hợp lệ</center>";
            }
        } catch (error) {
            document.getElementById("vbot_Client_Scan_devices").innerHTML = "<center>Lỗi khi tải dữ liệu lưu trữ</center>";
        }
    } else {
        document.getElementById("vbot_Client_Scan_devices").innerHTML = "<center>Không có dữ liệu lưu trữ từ trước</center>";
    }
});
</script>

<?php include 'html_js.php'; ?>

</body>
</html>