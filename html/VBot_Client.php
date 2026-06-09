<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
?>

<?php
if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (
        !isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
    ) {
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
<link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css?v=<?php echo $Cache_UI_Ver; ?>">
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

    .fullscreen-toggle,
    .refresh-btn {
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
            <br />
            <div class="input-group mb-3">
                <span class="input-group-text border-success" id="basic-addon1">Thêm Client Thủ Công</span>
                <input type="text" class="form-control border-success" name="add_client_manual_ip" id="add_client_manual_ip" placeholder="Nhập địa chỉ IP Của Client, VD: 192.168.1.199">
                <button type="button" class="btn btn-primary border-success" onclick="bat_dau_them_client_thu_cong()">Thêm</button>
            </div>
            <hr />
            <div class="row">
                <div id="vbot_Client_Scan_devices"></div>
            </div>
<div class="alert alert-primary" role="alert">
Để Bật Tắt Sử Dụng Chức Năng Này Hãy Đi Tới: <b>Cấu Hình Config</b> -> <b>Streming Audio Server (VBot Client, Client - Server)</b> -> <b>Kích Hoạt</b>
</div>
        </section>
    </main>

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
        //Hàm lưu dữ liệu Client vào Server
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

        //Hàm đọc dữ liệu từ server
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
            xhr.onreadystatechange = function() {
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
                            } else {
                                show_message('Lỗi: <font color="red">' + response.messager + '</font>');
                            }
                        } catch (error) {
                            document.getElementById('vbot_Client_Scan_devices').innerHTML = 'Lỗi xử lý dữ liệu.';
                        }
                    }
                }
            };
            xhr.send();
        }

		//Hiển thị thông in các thiết bị VBot client
        function displayDeviceData(data) {
            if (!Array.isArray(data)) {
                document.getElementById('vbot_Client_Scan_devices').innerHTML = '<center>Dữ liệu không hợp lệ</center>';
                return;
            }
            // Hàm so sánh để sắp xếp IP từ nhỏ đến lớn
            function compareIPs(a, b) {
			const ipA = (a.ip_address || a.ip || '').split('.').map(Number);
			const ipB = (b.ip_address || b.ip || '').split('.').map(Number);
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
                const versionUrl = device.VBOT_CHECK_API_VERSION || device.update.version_url;
                tableHTML +=
                    '<tr>' +
                    '<td style="text-align: center; vertical-align: middle;"><span class="status-dot" id="status-' + index + '"></span>' + (device.client_name || '') + '</td>' +

					'<td style="text-align: center; vertical-align: middle;">' +
					'<a href="' +
					'http://' + (device.ip_address || device.ip || '') +
					(((device.chip?.manufacturer || '').toLowerCase() === 'phicomm') ? ':8081' : '') +
					'" target="_blank">' +
					(device.ip_address || device.ip || '') +
					//(((device.chip?.manufacturer || '').toLowerCase() === 'phicomm') ? ':8081' : '') +
					' <i class="bi bi-box-arrow-up-right"></i></a></td>' +

					'<td style="text-align: center; vertical-align: middle;"><a href="http://' + (device.server.vbot_server_ip || device.server.host) + '" target="_blank">' + (device.server.vbot_server_ip || device.server.socket) + ' <i class="bi bi-box-arrow-up-right"></i></a></td>' +
                    '<td style="text-align: center; vertical-align: middle;" id="device-version-' + index + '">' + (device.version || '') + '</td>' +
                    '<td style="text-align: center; vertical-align: middle;">' + (device.chip_model || device.chip.model) + ' ' + (device.chip_suffix || '') + '</td>' +
                    '<td style="text-align: center; vertical-align: middle;">' +
                    '<button class="btn btn-info ping-btn" data-ip="' + (device.ip_address || device.ip || '') + '" data-index="' + index + '" title="Kiểm tra trạng thái"><i class="bi bi-wifi"></i></button> ' +
                    '<button type="button" class="btn btn-warning" onclick="showJsonData(\'' + (device.ip_address || device.ip || '') + '\')" title="Xem dữ liệu cấu hình json"><i class="bi bi-filetype-json"></i></button> ' +
                    '<button class="btn btn-danger delete-btn" data-ip="' + (device.ip_address || device.ip || '') + '" title="Xóa Client: ' + (device.ip_address || device.ip || '') + '"><i class="bi bi-trash"></i></button> ' +
                    '</td>' +
                    '</tr>';
                //Check version
                fetch(versionUrl)
                    .then(response => response.json())
					.then(versionData => {
						let latestVersion = '';
						if (versionData.content) {
							// GitHub API trả Base64
							const decodedContent = atob(versionData.content);
							latestVersion = JSON.parse(decodedContent).build_date;
						} else {
							// JSON trực tiếp
							latestVersion = versionData.build_date;
						}
						const currentVersion = device.version || '';
						let versionHTML = currentVersion;
						if (latestVersion && !compareVersions(currentVersion, latestVersion)) {
							versionHTML +=
								' <span class="update-link" title="Có phiên bản mới: ' + latestVersion + '">Có phiên bản mới: ' + latestVersion + '</span>' +
								' <br/><button type="button" class="btn btn-primary update-link_upgrade" onclick="start_upgrade_firmware(\'' + (device.ip_address || device.ip || '') + '\', \'' + (device.firmware_url || device.update.bin_url || '') + '\')"> Nâng Cấp <i class="bi bi-arrow-up-circle"></i></button>';
						} else {
							versionHTML += ' <span class="latest-version" title="Phiên bản mới nhất"> Đã cập nhật</span>';
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
                pingDevice(device.ip_address || device.ip || '', index);
            });
            tableHTML += '</tbody></table>';
            document.getElementById('vbot_Client_Scan_devices').innerHTML = tableHTML;

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

        //Tải lại dữ liệu cho Client riêng lẻ
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
                                const deviceIndex = devices.findIndex(d => (d.ip_address || d.ip) === ip_address);
                                if (deviceIndex !== -1) {
                                    devices[deviceIndex] = updatedDevice;
                                } else {
                                    devices.push(updatedDevice);
                                }
                                saveToServer(devices);
                                displayDeviceData(devices);
                                showMessagePHP('Đã tải lại dữ liệu cho ' + ip_address, 5);
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

		//Xóa dữ liệu đã tìm kiếm trước đó
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

		//đổi tên file .bin để nâng cấp littlefs
		function get_littlefs_url(url_firmware) {
			return url_firmware.replace(/\/[^\/]+\.bin$/i, '/littlefs.bin');
		}

		//kiểm tra URL littlefs.bin có tồn tại
		function check_file_exists(url, callback) {
			var xhr = new XMLHttpRequest();
			xhr.open('HEAD', url, true);
			xhr.onload = function() {
				callback(xhr.status >= 200 && xhr.status < 300);
			};
			xhr.onerror = function() {
				callback(false);
			};
			xhr.send();
		}

		//Delay sleep
		function sleep(ms) {
			return new Promise(resolve => setTimeout(resolve, ms));
		}

        //Tiến hành cập nhật Firmware tự động
        function start_upgrade_firmware(ip_address, url_firmware) {
            var confirmUpgrade = confirm("Bạn có chắc chắn muốn nâng cấp Firmware mới nhất không?");
            if (!confirmUpgrade) {
                showMessagePHP("Đã hủy nâng cấp Firmware", 5);
                return;
            }

		// Nếu file cập nhật là APK thì hướng dẫn cập nhật qua ADB
		if ((url_firmware || '').toLowerCase().split('?')[0].endsWith('.apk')) {
			show_message(
				'<div style="text-align:left; line-height:1.6;">' +
					'<b>Có phiên bản mới.</b><br><br>' +
					'<a href="https://github.com/marion001/VBot_Client_Offline/tree/main/SOCKET_CONNECTION/Phicomm_R1" target="_blank">Hướng Dẫn Cập Nhật Đầy Đủ -> Github</a><br><br>' +
					'<b>Tải công cụ ADB:</b><br>' +
					'<a href="https://github.com/marion001/VBot_Client_Offline/blob/main/SOCKET_CONNECTION/Phicomm_R1/adb_tools.zip" target="_blank">adb_tools.zip</a><br><br>' +
					'<b>Hướng dẫn cập nhật qua ADB:</b><br>' +
					'Bật ADB trên loa Phicomm R1 (Đã được bật sẵn).<br><br>' +
					'<b>Kết nối tới thiết bị:</b><br>' +
					'<code>adb connect ' + ip_address + ':5555</code><br><br>' +
					'<b>Tải xuống file .apk mới tại đây:</b><br>' +
					'<b>APK:</b> <a href="' + url_firmware + '" target="_blank">PhicommR1_VBotClient.apk</a><br><br>' +
					'<b>Đẩy APK vào thiết bị (điền đầy đủ đường dẫn file .apk đã tải xuống):</b><br>' +
					'<code>adb -s ' + ip_address + ':5555 push PhicommR1_VBotClient.apk /data/local/tmp/vbot.apk</code><br><br>' +
					'<b>Cài đặt, cập nhật ứng dụng app:</b><br>' +
					'<code>adb -s ' + ip_address + ':5555 shell pm install -r /data/local/tmp/vbot.apk</code><br><br>' +
					'<b>Xóa file .apk tạm trước đó:</b><br>' +
					'<code>adb -s ' + ip_address + ':5555 shell rm /data/local/tmp/vbot.apk</code><br><br>' +
					'<b>Khởi động lại ứng dụng, App:</b><br>' +
					'<code>adb -s ' + ip_address + ':5555 shell am start -n com.vbot_client.phicommr1/.MainActivity</code><br><br>' +
					'<b>Lưu ý:</b><br>' +
					'Thay <code>&lt;ip&gt;</code> bằng địa chỉ IP của loa Phicomm R1 tương ứng của bạn.<br>' +
					'Đảm bảo các thiết bị kết nối cùng một mạng LAN.<br>' +
					'Nếu lỗi kết nối, hãy khởi động lại ADB hoặc khởi động lại loa.' +
				'</div>'
			);
			return;
		}

            loading('show');
            //Kiểm tra định dạng IP (ví dụ: 192.168.x.x)
            var ipRegex = /^192\.168\.\d{1,3}\.\d{1,3}$/;
            if (!ipRegex.test(ip_address)) {
                loading('hide');
                show_message("Định dạng địa chỉ IP không hợp lệ. Phải là dạng: 192.168.x.x");
                return;
            }
            //Kiểm tra URL firmware phải bắt đầu bằng http/https và kết thúc bằng .bin
            var urlRegex = /^https?:\/\/.*\.bin$/i;
            if (!urlRegex.test(url_firmware)) {
                loading('hide');
                show_message("URL của Firmware cũ không hợp lệ<br/>Phải bắt đầu bằng http:// hoặc https:// và kết thúc bằng .bin.<br/>Cần được nâng cấp thủ công");
                return;
            }
            showMessagePHP("Bắt đầu nâng cấp Firmware", 5);
            //Thiết lập timeout cho toàn bộ quá trình (4 phút)
            var totalTimeout = 240000;
            var startTime = Date.now();
            //Timeout cho bypass_upgrade_firmware (90 giây)
            var bypassTimeout = 90000;
            var bypassTimer = setTimeout(function() {
                loading('hide');
                show_message("Gián Đoạn, hết thời gian chờ nhận phản hồi bỏ qua xác thực OTA khi nâng cấp Firmware, Bạn cần nâng cấp thủ công");
            }, bypassTimeout);
            bypass_upgrade_firmware(ip_address, function(result) {
                //Hủy timeout của bypass nếu hoàn thành
                clearTimeout(bypassTimer);
                //Kiểm tra thời gian đã trôi qua
                var elapsedTime = Date.now() - startTime;
                if (elapsedTime >= totalTimeout) {
                    loading('hide');
                    show_message("Gián Đoạn, hết thời gian chờ nhận phản hồi khi nâng cấp Firmware, Bạn cần nâng cấp thủ công");
                    return;
                }
                if (result.toLowerCase().includes("bypass_fr_ok")) {
                    showMessagePHP("Bỏ qua xác thực OTA thành công, đang tiến hành cập nhật Firmware....", 5);
                    var xhr = new XMLHttpRequest();
                    var url = 'includes/php_ajax/VBot_Client_Upgrade_Firmware.php?start_upgrade_firmware&ip=' + encodeURIComponent(ip_address) + '&url_firmware=' + encodeURIComponent(url_firmware);
                    xhr.open('GET', url, true);
                    //Timeout cho XMLHttpRequest (còn lại từ 180 giây)
                    var remainingTime = totalTimeout - elapsedTime;
                    var xhrTimeout = setTimeout(function() {
                        xhr.abort();
                        loading('hide');
                        show_message("Gián Đoạn, hết thời gian chờ nhận phản hồi khi nâng cấp Firmware, Bạn cần nâng cấp thủ công");
                    }, remainingTime);
                    xhr.onload = function() {
                        //Hủy timeout nếu request hoàn thành
                        clearTimeout(xhrTimeout);
                        if (xhr.status >= 200 && xhr.status < 300) {
                            var data = JSON.parse(xhr.responseText);
							if (data.success) {
								showMessagePHP("Thành Công Firmware: " + data.message, 5);
								showMessagePHP("Đang chờ thiết bị khởi động lại...", 5);
								//Đợi 10 giây chờ client reboot
								sleep(10000).then(function() {
									showMessagePHP("Thiết bị đã khởi động lại, tiếp tục nâng cấp giao diện cấu hình trong bộ nhớ LittleFS...", 5);
									var url_littlefs = get_littlefs_url(url_firmware);
									check_file_exists(url_littlefs, function(exists) {
										if (!exists) {
											loading('hide');
											showMessagePHP("Đã nâng cấp Firmware thành công (không có littlefs.bin)", 5);
											loading_data_client(ip_address, 5000);
											return;
										}
										//Nâng cấp littlefs Nếu có
										bypass_upgrade_littlefs(ip_address, function(resultLittlefs) {
											if (resultLittlefs.toLowerCase().includes("bypass_fs_ok")) {
												var xhrLittlefs = new XMLHttpRequest();
												var urlLittlefs =
													'includes/php_ajax/VBot_Client_Upgrade_Firmware.php?start_upgrade_littlefs'
													+ '&ip=' + encodeURIComponent(ip_address)
													+ '&url_littlefs=' + encodeURIComponent(url_littlefs);
												xhrLittlefs.open('GET', urlLittlefs, true);
												xhrLittlefs.onload = function() {
													loading('hide');
													if (xhrLittlefs.status >= 200 && xhrLittlefs.status < 300) {
														var dataLittlefs = JSON.parse(xhrLittlefs.responseText);
														if (dataLittlefs.success) {
															showMessagePHP("Đã nâng cấp Firmware và giao diện cấu hình trong LittleFS thành công", 5);
															loading_data_client(ip_address, 5000);
														} else {
															show_message("Nâng cấp Firmware thành công, nhưng nâng cấp LittleFS thất bại: " + dataLittlefs.message);
														}
													} else {
														show_message("Nâng cấp Firmware thành công, nhưng nâng cấp giao diện trong LittleFS lỗi HTTP: " + xhrLittlefs.status);
													}
												};
												xhrLittlefs.onerror = function() {
													loading('hide');
													show_message("Nâng cấp Firmware thành công nhưng lỗi mạng khi nâng cấp giao diện trong LittleFS");
												};
												xhrLittlefs.send();
											} else {
												loading('hide');
												show_message("Nâng cấp Firmware thành công nhưng bypass LittleFS thất bại: " + resultLittlefs);
											}
										});
									});
								});
							}
							else {
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
                    xhr.onerror = function() {
                        //Hủy timeout nếu có lỗi mạng
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

        //Hàm bypass firmware (giữ nguyên)
        function bypass_upgrade_firmware(ip_address, callback) {
            var xhr = new XMLHttpRequest();
            var url = 'includes/php_ajax/VBot_Client_Upgrade_Firmware.php?bypass_upgrade_firmware&ip=' + encodeURIComponent(ip_address);
            xhr.open('GET', url, true);
            xhr.onload = function() {
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
            xhr.onerror = function() {
                show_message("Lỗi mạng khi bỏ qua xác thực OTA");
                callback('Error');
            };
            xhr.send();
        }

        //Hàm bypass LittleFS (giữ nguyên)
        function bypass_upgrade_littlefs(ip_address, callback) {
            var xhr = new XMLHttpRequest();
            var url = 'includes/php_ajax/VBot_Client_Upgrade_Firmware.php?bypass_upgrade_littlefs&ip=' + encodeURIComponent(ip_address);
            xhr.open('GET', url, true);
            xhr.onload = function() {
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
            xhr.onerror = function() {
                show_message("Lỗi mạng khi bỏ qua xác thực OTA");
                callback('Error');
            };
            xhr.send();
        }

        //Nâng cấp FW Thủ Công, chọn file .bin
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
            //Thiết lập tổng timeout 3 phút (180 giây)
            var timeoutDuration = 180000;
            var timeoutId = setTimeout(function() {
                xhr.abort();
                loading('hide');
                showMessagePHP("Gián Đoạn, hết thời gian chờ nhận phản hồi khi nâng cấp Firmware, Bạn cần nâng cấp thủ công", 5);
            }, timeoutDuration);
            xhr.onload = function() {
                clearTimeout(timeoutId);
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 && response.success) {
                        loading('hide');
                        showMessagePHP("Thành công: " + response.message, 5);
                        loading_data_client(ip_address, 5000);
                    } else {
                        loading('hide');
                        showMessagePHP("Lỗi: " + response.message, 5);
                    }
                } catch (e) {
                    loading('hide');
                    showMessagePHP("Lỗi phân tích phản hồi dữ liệu từ Server", 5);
                }
            };
            xhr.onerror = function() {
                clearTimeout(timeoutId);
                loading('hide');
                showMessagePHP("Lỗi kết nối trong quá trình nâng cấp firmware", 5);
            };
            xhr.send(formData);
        }

        //Thêm Client thủ công bằng IP
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
                    let ip_address = client.ip_address || client.ip;
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
            if (!confirm('Bạn có chắc chắn muốn xóa Client với IP: ' + ip)) {
                return;
            }
            loading('show');
            loadFromServer()
                .then(devices => {
                    const updatedDevices = devices.filter(device => device.ip_address || device.ip || '' !== ip);
                    return saveToServer(updatedDevices)
                        .then(result => {
                            if (result.success) {
                                displayDeviceData(updatedDevices);
                                showMessagePHP('Đã xóa Client ' + ip + ' thành công', 5);
                            } else {
                                showMessagePHP('Lỗi khi xóa Client ' + ip + ': ' + result.message, 5);
                            }
                            loading('hide');
                        });
                })
                .catch(error => {
                    loading('hide');
                    showMessagePHP('Lỗi khi xử lý xóa Client ' + ip + ': ' + error, 5);
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

        //Hiển thị dữ liệu khi trang được tải
        document.addEventListener('DOMContentLoaded', function() {
            loading('show');
            loadFromServer()
                .then(devices => {
                    loading('hide');
                    if (Array.isArray(devices) && devices.length > 0) {
                        displayDeviceData(devices);
                        showMessagePHP('Đã tải dữ liệu VBot Client được lưu từ Server', 5);
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
    <script src="assets/vendor/prism/prism.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <script src="assets/vendor/prism/prism-json.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <?php include 'html_js.php'; ?>
</body>

</html>