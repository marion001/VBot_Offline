'use strict';

    //Quét các thiết bị sử dụng VBot trong cùng lớp mạng
    function scan_VBot_Device() {
        loading('show');
        showMessagePHP('Đang tìm kiếm các thiết bị chạy VBot trong cùng lớp mạng Lan', 12);
        const url = "includes/php_ajax/Scanner.php";
        const xhr = new XMLHttpRequest();
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
        xhr.setRequestHeader("X-CSRF-Token", window.VBOT_CSRF_TOKEN || "");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                loading('hide');
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const data = response.data;
                            if (Array.isArray(data) && data.length > 0) {
                                data.sort(function(a, b) {
                                    const aHasData = a.ip_address && a.port_api && a.host_name && a.user_name;
                                    const bHasData = b.ip_address && b.port_api && b.host_name && b.user_name;
                                    if (aHasData && !bHasData) {
                                        return -1;
                                    } else if (!aHasData && bHasData) {
                                        return 1;
                                    }
                                    return 0;
                                });
                                let tableHTML =
                                    '<table class="table table-bordered border-primary" cellspacing="0" cellpadding="5">' +
                                    '<thead>' +
                                    '<tr>' +
                                    '<th id="th_device_name" style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th>' +
                                    '<th id="th_ip_address" style="text-align: center; vertical-align: middle;">Địa Chỉ IP</th>' +
                                    '<th id="th_port_api" style="text-align: center; vertical-align: middle;">Port API</th>' +
                                    '<th id="th_host_name" style="text-align: center; vertical-align: middle;">HostName</th>' +
                                    '<th id="th_host_name" style="text-align: center; vertical-align: middle;">Hành Động</th>' +
                                    '</tr>' +
                                    '</thead>' +
                                    '<tbody>';
                                data.forEach((device, index) => {
                                    const rowId = 'device_row_' + index;
                                    tableHTML +=
                                        '<tr id="' + rowId + '">' +
                                        '<td id="' + rowId + '_name" style="text-align: center; vertical-align: middle;"><b><p class="text-success">' + (device.user_name || '') + '</p></b></td>' +
                                        '<td id="' + rowId + '_ip" style="text-align: center; vertical-align: middle;"><b><a class="text-danger" href="http://' + (device.ip_address || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.ip_address || '') + '</a></b></td>' +
                                        '<td id="' + rowId + '_port" style="text-align: center; vertical-align: middle;"><b><a class="text-success" href="http://' + (device.ip_address || '') + ':' + (device.port_api || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.port_api || '') + '</a></b></td>' +
                                        '<td id="' + rowId + '_host" style="text-align: center; vertical-align: middle;"><b>' + (device.host_name || '') + '</b></td>' +
                                        '<td id="' + rowId + '_action" style="text-align: center; vertical-align: middle;">' +
                                        '<button class="btn btn-danger" title="Xóa ' + (device.ip_address || '') + '" onclick="delete_IP_VBot_Server(\'' + (device.ip_address || '') + '\')"><i class="bi bi-trash"></i></button>' +
                                        ' <button class="btn btn-primary" title="WebUI ' + (device.ip_address || '') + '" onclick="showIframeModal(\'' + (device.ip_address || '') + '\', \'' + (device.user_name || '') + '\')"><i class="bi bi-gear-wide-connected"></i></button>' +
                                        '</td>' +
                                        '</tr>';
                                });
                                tableHTML +=
                                    '</tbody>' +
                                    '</table>';
                                document.getElementById("vbot_Scan_devices").innerHTML = tableHTML;
                                check_Device_Status_VBot_Server();
                                fetchAndPopulateDevices_chatbot();
                            } else {
                                document.getElementById("vbot_Scan_devices").innerHTML = "Không tìm thấy thiết bị nào.";
                            }
                        } else {
                            show_message("Đã xảy ra lỗi: " + (response.message || response.error || "Không rõ lỗi"));
                        }
                    } catch (error) {
                        document.getElementById("vbot_Scan_devices").innerHTML = "Đã xảy ra lỗi khi xử lý dữ liệu: " + xhr.responseText;
                    }
                } else {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        document.getElementById("vbot_Scan_devices").innerHTML =
                            response.message || response.error || ("Không thể kết nối tới máy chủ: " + xhr.status);
                    } catch (error) {
                        document.getElementById("vbot_Scan_devices").innerHTML = "Không thể kết nối tới máy chủ: " + xhr.status;
                    }
                }
            }
        };
        xhr.send("VBot_Device_Scaner=1");
    }

    //Kiểm tra trực tuyến các thiết bị Vot server trong mạng lan
    function check_Device_Status_VBot_Server(chatbox_click = 'off') {
        const table = document.getElementById("vbot_Scan_devices").querySelector("table");
        if (!table) {
            return;
        }
        const rows = table.querySelectorAll("tbody tr");
        rows.forEach((row, index) => {
            const ipCell = row.querySelector('#device_row_' + index + '_ip a');
            const portCell = row.querySelector('#device_row_' + index + '_port a');
            if (!ipCell || !portCell) {
                return;
            }
            const ip = ipCell.textContent;
            const port = portCell.textContent;
            const xhr = new XMLHttpRequest();
            const url = 'includes/php_ajax/Check_Connection.php?check_status_vbot_server_in_lan=true&ip=' + encodeURIComponent(ip) + '&port=' + encodeURIComponent(port);
            xhr.open('GET', url, true);
            xhr.onload = function() {
                let isOnline = false;
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        isOnline = response.success === true;
                    } catch (error) {
                        isOnline = false;
                    }
                }
                const td = row.querySelector('#device_row_' + index + '_name');
                const deviceName = td.querySelector('p') ? td.querySelector('p').textContent : '';
                const statusDot = isOnline ?
                    '<span style="color: green; font-size: 30px;" title="Thiết bị đang trực tuyến">●</span>' :
                    '<span style="color: red; font-size: 30px;" title="Thiết bị đang ngoại tuyến">●</span>';
                if (td) {
                    td.innerHTML = '<b>' + statusDot + ' <p class="text-success">' + deviceName + '</p></b>';
                }
                if (isOnline) {
                    showMessagePHP('<font color="green">Thiết bị: ' + ip + ' đang <b>trực tuyến</b></font>', 7);
                } else {
                    showMessagePHP('<font color="red">Thiết bị: ' + ip + ' đang <b>ngoại tuyến</b></font>', 7);
                }
            };
            xhr.onerror = function() {
                const td = row.querySelector('#device_row_' + index + '_name');
                const deviceName = td.querySelector('p') ? td.querySelector('p').textContent : '';
                const statusDot = '<span style="color: red; font-size: 30px;" title="Thiết bị đang ngoại tuyến">●</span>';
                if (td) {
                    td.innerHTML = '<b>' + statusDot + ' <p class="text-success">' + deviceName + '</p></b>';
                }
                showMessagePHP('<font color="red">Thiết bị: ' + ip + ' đang <b>ngoại tuyến</b></font>', 7);
            };
            xhr.send();
        });
    }

    //Lấy dữ liệu Các thiết bị chạy VBot trong mạng lan đã được Scan
    function get_vbotScanDevices() {
        loading('show');
        const url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=' + window.webuiVbotClientConfig.devicesFilePath + '';
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const jsonData = JSON.parse(xhr.responseText);
                    if (jsonData.success && Array.isArray(jsonData.data) && jsonData.data.length > 0) {
                        let tableHTML =
                            '<p class="card-title">Dữ liệu được tìm kiếm trước đó:</p>' +
                            '<table class="table table-bordered border-primary" cellspacing="0" cellpadding="5">' +
                            '<thead>' +
                            '<tr>' +
                            '<th id="th_device_name" style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th>' +
                            '<th id="th_ip_address" style="text-align: center; vertical-align: middle;">Địa Chỉ IP</th>' +
                            '<th id="th_port_api" style="text-align: center; vertical-align: middle;">Port API</th>' +
                            '<th id="th_host_name" style="text-align: center; vertical-align: middle;">HostName</th>' +
                            '<th id="th_host_name" style="text-align: center; vertical-align: middle;">Hành Động</th>' +
                            '</tr>' +
                            '</thead>' +
                            '<tbody>';
                        jsonData.data.forEach((device, index) => {
                            const rowId = 'device_row_' + index;
                            tableHTML +=
                                '<tr id="' + rowId + '">' +
                                '<td id="' + rowId + '_name" style="text-align: center; vertical-align: middle;"><b><p class="text-success">' + (device.user_name || '') + '</p></b></td>' +
                                '<td id="' + rowId + '_ip" style="text-align: center; vertical-align: middle;"><b><a class="text-danger" href="http://' + (device.ip_address || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.ip_address || '') + '</a></b></td>' +
                                '<td id="' + rowId + '_port" style="text-align: center; vertical-align: middle;"><b><a class="text-success" href="http://' + (device.ip_address || '') + ':' + (device.port_api || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.port_api || '') + '</a></b></td>' +
                                '<td id="' + rowId + '_host" style="text-align: center; vertical-align: middle;"><b>' + (device.host_name || '') + '</b></td>' +
                                '<td id="' + rowId + '_action" style="text-align: center; vertical-align: middle;">' +
                                '<button class="btn btn-danger" title="Xóa ' + (device.ip_address || '') + '" onclick="delete_IP_VBot_Server(\'' + (device.ip_address || '') + '\')"><i class="bi bi-trash"></i></button>' +
                                ' <button class="btn btn-primary" title="WebUI ' + (device.ip_address || '') + '" onclick="showIframeModal(\'' + (device.ip_address || '') + '\', \'' + (device.user_name || '') + '\')"><i class="bi bi-gear-wide-connected"></i></button>' +
                                '</td>' +
                                '</tr>';
                        });
                        tableHTML +=
                            '</tbody>' +
                            '</table>';
                        document.getElementById("vbot_Scan_devices").innerHTML = tableHTML;
                        check_Device_Status_VBot_Server();
                        loading('hide');
                    } else {
                        loading('hide');
                        document.getElementById("vbot_Scan_devices").innerHTML = "<center><h5 class='text-danger'>Không có thiết bị nào, nhấn vào QUÉT THIẾT BỊ để tìm kiếm</h5></center>";
                    }
                } catch (error) {
                    loading('hide');
                    document.getElementById("vbot_Scan_devices").innerHTML = "<center><h5 class='text-danger'>Lỗi khi phân tích dữ liệu: " + error.message + "</h5></center>";
                }
            } else {
                loading('hide');
                document.getElementById("vbot_Scan_devices").innerHTML = "<center><h5 class='text-danger'>Lỗi khi tải dữ liệu: Mã trạng thái " + xhr.status + "</h5></center>";
            }
        };
        xhr.onerror = function() {
            loading('hide');
            document.getElementById("vbot_Scan_devices").innerHTML = "<center><h5 class='text-danger'>Lỗi mạng khi tải dữ liệu</h5></center>";
        };
        xhr.send();
    }

    //Xóa dữ liệu đã lưu Các thiết bị chạy VBot Server Trong Lan
    function clearAllDevices_vbotScanDevices() {
        const url = 'includes/php_ajax/Scanner.php';
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
        xhr.setRequestHeader("X-CSRF-Token", window.VBOT_CSRF_TOKEN || "");
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    showMessagePHP(response.message, 3);
                    if (response.success) {
                        get_vbotScanDevices();
                    }
                } catch (error) {
                    showMessagePHP('Lỗi khi phân tích dữ liệu: ' + error.message, 3);
                }
            } else {
                showMessagePHP('Lỗi khi xóa dữ liệu: Mã trạng thái ' + xhr.status, 3);
            }
        };
        xhr.onerror = function() {
            showMessagePHP('Lỗi mạng khi xóa dữ liệu', 3);
        };
        xhr.send("Clean_VBot_Device_Scaner=1");
    }

    //Thêm thiết bị chạy VBot Server thủ công trong mạng Lan
    function add_IP_VBot_Server() {
        loading('show');
        const input = document.getElementById('add_ip_vbot_server');
        if (!input) {
            loading('hide');
            show_message('Không tìm thấy thẻ input có id: add_ip_vbot_server');
            return;
        }
        const ip = input.value.trim();
        const validIP = /^(http:\/\/)?192\.168\.\d{1,3}\.\d{1,3}$/.test(ip);
        if (!validIP) {
            loading('hide');
            show_message('Địa chỉ IP không hợp lệ. Chỉ chấp nhận địa chỉ bắt đầu bằng 192.168 hoặc http://192.168');
            return;
        }
        const cleanIP = ip.replace(/^http:\/\//, '');
        const url = 'includes/php_ajax/Check_Connection.php';
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        loading('hide');
                        showMessagePHP('Thêm thiết bị thành công: ' + response.device.ip_address, 5);
                        get_vbotScanDevices();
                    } else {
                        loading('hide');
                        showMessagePHP('Thêm thiết bị thất bại: ' + response.error, 5);
                    }
                } catch (e) {
                    loading('hide');
                    showMessagePHP('Thêm thiết bị thất bại: ' + e, 5);
                }
            } else {
                loading('hide');
                showMessagePHP('Không thể kết nối đến máy chủ. Mã lỗi: ' + xhr.status, 5);
            }
        };
        xhr.onerror = function() {
            loading('hide');
            showMessagePHP('Lỗi khi gửi yêu cầu', 5);
        };
        xhr.send('add_ip_vbot_server=1&ip=' + encodeURIComponent(cleanIP));
    }

    //Xóa ip VBot Server đã scan được
    function delete_IP_VBot_Server(ip) {
        loading('show');
        if (!ip || !ip.startsWith('192.168')) {
            alert("Địa chỉ IP không hợp lệ. IP phải bắt đầu bằng '192.168'.");
            loading('hide');
            return;
        }
        const url = '/includes/php_ajax/Check_Connection.php';
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    loading('hide');
                    showMessagePHP("<font color=green>Xóa thiết bị với IP " + ip + " thành công</font>", 7);
                    get_vbotScanDevices();
                } else {
                    loading('hide');
                    showMessagePHP("Lỗi xóa thiết bị: " + response.error);
                }
            } else {
                loading('hide');
                show_message("Lỗi kết nối: " + xhr.status);
            }
        };
        xhr.onerror = function() {
            loading('hide');
            showMessagePHP("Lỗi khi gửi yêu cầu", 5);
        };
        xhr.send('delete_ip_vbot_server=1&ip=' + encodeURIComponent(ip));
    }
