<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
setlocale(LC_CTYPE, 'C.UTF-8', 'en_US.UTF-8', 'UTF-8');

if ($Config['contact_info']['user_login']['active']) {
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

function networkInfo($connectionName = null) {
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
    $raw = shell_exec('LANG=C.UTF-8 LC_ALL=C.UTF-8 nmcli connection show '.escapeshellarg($connectionName));
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
	$ipMode = ($method === "manual") ? "Đang dùng IP tĩnh" : "Đang dùng DHCP (IP động)";
	$ipDisplay = !empty($ip) ? $ip : "N/A";                     // IP
	$dnsDisplay = !empty($dnsList) ? implode(", ", $dnsList) : "N/A";  // DNS
	$dnsSource = !empty($dnsList) ? (($ignoreAutoDNS === "yes") ? "DNS thủ công" : "DNS do DHCP cấp") : "N/A"; // nguồn DNS
	$gatewayDisplay = !empty($gateway) ? $gateway : "N/A";     // Gateway
	$gatewaySource = !empty($gateway) ? (($method === "manual") ? "Đặt thủ công" : "DHCP cấp") : "N/A"; // nguồn Gateway
    return [
        "ipMode" => $ipMode,
        "ip" => $ipDisplay,
        "dns" => $dnsDisplay,
        "dnsSource" => $dnsSource,
        "gateway" => $gatewayDisplay,
        "gatewaySource" => $gatewaySource
    ];
}

?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>

<body>
    <?php
    include 'html_header_bar.php';
    include 'html_sidebar.php';
    ?>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Thông tin, cấu hình Wifi</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
                    <li class="breadcrumb-item">Wifi</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body"><br/>
<div id="wifiInfoResult"></div>
<input type="hidden" id="wifi-profile-uuid" value=""><center>
<div class="border rounded p-3 mb-3">
  <div class="d-flex flex-wrap gap-2 mb-2">
    <button type="button" class="btn btn-primary btn-sm" id="wifi-probe">Kiểm tra Internet qua Wi-Fi</button>
    <button type="button" class="btn btn-secondary btn-sm" id="wifi-reload-info">Làm mới thông tin</button>
  </div>
  <p id="wifi-probe-result" class="small mb-2">Chưa kiểm tra Internet. Phép thử chỉ dùng Wi-Fi, không dùng PAN thay thế.</p>
  <hr/>
  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-warning btn-sm" id="wifi-apply" disabled>Áp dụng kết nối đã lưu</button>
    <button type="button" class="btn btn-secondary btn-sm" id="wifi-transaction">Kiểm tra áp dụng</button>
    <button type="button" class="btn btn-success btn-sm" id="wifi-confirm" disabled>Xác nhận kết nối hoạt động</button>
  </div>
  <p class="small mt-2 mb-0" id="wifi-apply-info">Sau khi lưu IP/DNS, nhấn áp dụng ở đây. Nếu không xác nhận áp dụng trong 120 giây, loa sẽ khôi phục lại cấu hình cũ trước đó.</p>
  <div id="wifi-new-address"></div>
</div>
                            
                                <button id="loadWifiButton" name="loadWifiButton" class="btn btn-primary rounded-pill" onclick="Show_Wifi_List()">Danh Sách Wifi Đã Kết Nối</button>
                                <button id="scanWifiButton" class="btn btn-success rounded-pill" onclick="fetchAndDisplayWifiList()">Quét Mạng Wifi</button>
								<button type="button" class="btn btn-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#exampleModal_ipDong">Cấu Hình IP</button>
								<button type="button" class="btn btn-secondary rounded-pill" data-bs-toggle="modal" data-bs-target="#exampleModal_dns_only">Cấu hình DNS</button>
								<button id="resetAllWifi" class="btn btn-danger rounded-pill" onclick="reset_All_Wifi()">Đặt Lại Cấu Hình Wifi</button>
                            </center>
                            <br/>
                            <div id="hienthiketqua"></div>
                        </div>
                    </div>
                </div>
            </div>

<!-- Modal Cấu Hình IP Tĩnh -->
<div class="modal fade" id="exampleModal_ipDong" tabindex="-1" aria-labelledby="exampleModalLabel_ipDong" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel_ipDong">Cấu Hình Địa Chỉ IP Cho Kết Nối Mạng Này</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
<div class="mb-3"><label class="form-label" for="wifi-dhcp-dns">DNS khi dùng DHCP (IP động)</label><select class="form-select" id="wifi-dhcp-dns"><option value="google" selected>Google DNS — 8.8.8.8 / 8.8.4.4 (mặc định)</option><option value="router">DNS do router cấp</option></select><div class="form-text">Lựa chọn này áp dụng khi nhấn Cấu hình Auto DHCP. DNS tùy chỉnh vẫn đặt được ở mục Cấu hình DNS.</div></div>

<div class="form-floating mb-3">
<input type="text" readonly class="form-control border-danger" title="Tên Mạng Đang kết Nối" name="connected_network_name" id="connected_network_name" value="Địa Chỉ IP Tĩnh Cần Đặt">
<label for="connected_network_name">Tên Mạng Đang Kết Nối:</label>
</div>

<div class="form-floating mb-3">
<input type="text" class="form-control border-success" title="Nhập Địa Chỉ IP Tĩnh Bạn Muốn Đặt" name="dia_chi_ip_tinh" id="dia_chi_ip_tinh" value="Địa Chỉ IP Tĩnh Cần Đặt">
<label for="dia_chi_ip_tinh">IP tĩnh, có thể kèm /prefix (ví dụ 192.168.1.10/24):</label>
</div>

<div class="form-floating mb-3">
<input type="text" class="form-control border-success" title="Nhập Địa Chỉ IP Của Gateway, Modem, Route" name="ip_gateway_modem" id="ip_gateway_modem" value="Địa Chỉ IP Của Gateway, Modem, Route">
<label for="ip_gateway_modem">Nhập IP Gateway, Modem, Route:</label>
</div>

<div class="form-floating mb-3">
<input type="text" class="form-control border-success" title="Nhập Địa Chỉ DNS 1 Ví Dụ: 8.8.8.8" name="set_dns_1" id="set_dns_1" value="8.8.8.8">
<label for="set_dns_1">Nhập DNS 1:</label>
</div>

<div class="form-floating mb-3">
<input type="text" class="form-control border-success" title="Nhập Địa Chỉ DNS 2 Ví Dụ: 8.8.4.4" name="set_dns_2" id="set_dns_2" value="8.8.4.4">
<label for="set_dns_2">Nhập DNS 2:</label>
</div>

      </div>
      <div class="modal-footer">
	  <button type="button" class="btn btn-primary rounded-pill" onclick="set_static_ip()">Áp Dụng Cấu Hình IP Tĩnh</button>
	  <button type="button" class="btn btn-info rounded-pill" onclick="set_dhcp_mode()">Cấu Hình Auto DHCP (IP động)</button>
        <button type="button" class="btn btn-danger rounded-pill" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Cấu Hình DNS -->
<div class="modal fade" id="exampleModal_dns_only" tabindex="-1" aria-labelledby="exampleModalLabel_dns_only" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel_dns_only">Cấu Hình DNS Cho Kết Nối Mạng Này</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

<div class="form-floating mb-3">
<input type="text" readonly class="form-control border-danger" title="Tên Mạng Đang kết Nối" name="connected_network_name1" id="connected_network_name1" value="Địa Chỉ IP Tĩnh Cần Đặt">
<label for="connected_network_name1">Tên Mạng Đang Kết Nối:</label>
</div>

<div class="form-floating mb-3">
<input type="text" class="form-control border-success" title="Nhập Địa Chỉ DNS 1 Ví Dụ: 8.8.8.8" name="set_dns_only_1" id="set_dns_only_1" value="8.8.8.8">
<label for="set_dns_only_1">Nhập DNS 1:</label>
</div>

<div class="form-floating mb-3">
<input type="text" class="form-control border-success" title="Nhập Địa Chỉ DNS 2 Ví Dụ: 8.8.4.4" name="set_dns_only_2" id="set_dns_only_2" value="8.8.4.4">
<label for="set_dns_only_2">Nhập DNS 2:</label>
</div>
      </div>
      <div class="modal-footer">
	  <button class="btn btn-primary rounded-pill" onclick="set_dns_only();">Áp Dụng Cấu hình DNS</button>
	  <button class="btn btn-warning rounded-pill" onclick="resetDNS_DHCP()">Dùng DNS mặc định (DHCP Modem, Route Cấp)</button>
        <button type="button" class="btn btn-danger rounded-pill" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Đóng</button>
      </div>
    </div>
  </div>
</div>

        </section>
    </main>
    <?php
    include 'html_footer.php';
    ?>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <?php
    include 'html_js.php';
    ?>
    <script>
        function wifiApiErrorMessage(xhr, fallbackMessage) {
            try {
                const response = JSON.parse(xhr.responseText);
                return response.message || response.error || fallbackMessage;
            } catch (error) {
                return fallbackMessage;
            }
        }

        function wifiEscapeHtml(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(character) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
            });
        }

        //Reset cấu hình wifi
        function reset_All_Wifi() {
            if (confirm("Bạn có chắc chắn muốn xóa tất cả các kết nối Wi-Fi không?\nHành động này sẽ làm mất kết nối mạng hiện tại!\n\nVà hệ thống sẽ tạo điểm truy cập Wifi mới với tên: 'VBot Assistant' để bạn kết nối và cấu hình")) {
                loading("show");
				const xhr = vbotCreateXhr();
                xhr.open("POST", "includes/php_ajax/Wifi_Act.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
                xhr.setRequestHeader("X-CSRF-Token", window.VBOT_CSRF_TOKEN || "");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
							loading("hide");
                            const response = JSON.parse(xhr.responseText);
                            show_message(response.message);
                        } else {
							loading("hide");
                            show_message(wifiApiErrorMessage(xhr, "Lỗi khi gửi yêu cầu đặt lại cấu hình wifi"));
                        }
                    }
                };
                xhr.send("Reset_Wifi=1");
            }
        }
        //Hiển thị dang sách wifi đã kết nối
        function Show_Wifi_List() {
            loading("show");
            var xhr = vbotCreateXhr();
            xhr.withCredentials = true;
            xhr.addEventListener("readystatechange", function() {
                if (this.readyState === 4) {
                    //console.log(this.responseText);
                    var response;
                    try { response = JSON.parse(this.responseText); }
                    catch (error) { loading('hide'); show_message('Không đọc được danh sách Wi-Fi. Hãy thử lại.'); return; }
                    var fileListDiv = document.getElementById('hienthiketqua');
                    fileListDiv.classList.add('table-responsive');
                    fileListDiv.innerHTML = '';
                    // Kiểm tra xem response có thành công và có dữ liệu không
                    if (response.success && Array.isArray(response.data)) {
                        var table = document.createElement('table');
                        table.className = 'table table-bordered border-primary';
                        var thead = document.createElement('thead');
                        var titleRow = document.createElement('tr');
                        var titleCell = document.createElement('th');
                        titleCell.colSpan = 9;
                        titleCell.className = 'text-center align-middle';
                        titleCell.textContent = 'Danh Sách Wifi Đã Kết Nối';
                        titleRow.appendChild(titleCell);
                        thead.appendChild(titleRow);
                        var headerRow = document.createElement('tr');
                        ['Tên hồ sơ', 'UUID', 'Interface', 'Tự kết nối', 'Ưu tiên', 'Lưu', 'Kết Nối', 'Mật Khẩu', 'Xóa'].forEach(function(label) {
                            var th = document.createElement('th');
                            th.className = 'text-center align-middle';
                            th.textContent = label;
                            headerRow.appendChild(th);
                        });
                        thead.appendChild(headerRow);
                        table.appendChild(thead);
                        var tbody = document.createElement('tbody');
                        response.data.forEach(function(wifi, wifiIndex) {
                            var row = document.createElement('tr');
                            [wifi.ssid, wifi.uuid, wifi.interface].forEach(function(value) {
                                var td = document.createElement('td');
                                td.className = 'text-center align-middle';
                                td.textContent = value || '';
                                row.appendChild(td);
                            });
                            const autoCell = row.insertCell();
                            const auto = document.createElement('input'); auto.type='checkbox'; auto.className='form-check-input'; auto.checked=!!wifi.autoconnect; auto.setAttribute('aria-label','Tự kết nối '+wifi.ssid); autoCell.append(auto);
                            const priorityCell = row.insertCell();
                            const priority = document.createElement('input'); priority.type='number'; priority.min='-999'; priority.max='999'; priority.step='1'; priority.value=wifi.priority || 0; priority.className='form-control form-control-sm'; priority.setAttribute('aria-label','Ưu tiên '+wifi.ssid); priority.title='Số lớn hơn được ưu tiên khi tự chọn mạng; không đổi mạng đang kết nối.'; priorityCell.append(priority);
                            const saveCell = row.insertCell();
                            const save = document.createElement('button'); save.type='button'; save.className='btn btn-sm btn-primary'; save.textContent='Lưu';
                            save.addEventListener('click',()=>window.saveWifiPreferences(wifi.uuid,auto.checked,priority.value,save)); saveCell.append(save);
                            [
                                ['Kết Nối', 'btn-success', function() { connectWifiOld(wifi.ssid, wifi.uuid); }],
                                ['Mật Khẩu', 'btn-primary', function() { getWifiPassword(wifi.ssid, wifi.uuid); }],
                                ['Xóa', 'btn-danger', function() { deleteWifi(wifi.ssid, wifi.uuid); }]
                            ].forEach(function(action) {
                                var td = document.createElement('td');
                                td.className = 'text-center align-middle';
                                var button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'btn ' + action[1] + ' rounded-pill';
                                button.textContent = action[0];
                                button.addEventListener('click', action[2]);
                                td.appendChild(button);
                                row.appendChild(td);
                            });
                            tbody.appendChild(row);
                        });
                        table.appendChild(tbody);
                        fileListDiv.appendChild(table);
                        loading("hide");
                    } else {
                        fileListDiv.innerHTML = 'Dữ liệu trả về không hợp lệ.';
                        loading("hide");
                    }
                    loading("hide");
                }
            });
            xhr.open("GET", "includes/php_ajax/Wifi_Act.php?Show_Wifi_List");
            xhr.send();
        }

        //Quét wifi
        function fetchAndDisplayWifiList() {
            loading("show");
            var xhr = vbotCreateXhr();
            xhr.open('GET', 'includes/php_ajax/Wifi_Act.php?Scan_Wifi_List', true);
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var response = JSON.parse(this.responseText);
                    var fileListDiv = document.getElementById('hienthiketqua');
                    fileListDiv.innerHTML = '';
                    if (response.success && Array.isArray(response.data)) {
                        // Tạo bảng để hiển thị dữ liệu
                        var tableHTML =
                            "<table class='table table-bordered border-primary'>" +
                            "<thead>" +
                            "<tr>" +
                            "<th colspan='8'><center><font color='red'>Danh Sách Mạng Wifi Được Tìm Thấy</font></center></th>" +
                            "</tr>" +
                            "<tr>" +
                            "<th scope='col'><center>Tên Mạng Wifi</center></th>" +
                            "<th scope='col'><center>BSSID</center></th>" +
                            "<th scope='col'><center>Kênh</center></th>" +
                            "<th scope='col'><center>RATE</center></th>" +
                            "<th scope='col'><center>Tín Hiệu</center></th>" +
                            "<th scope='col'><center>Cường Độ</center></th>" +
                            "<th scope='col'><center>Bảo Mật</center></th>" +
                            "<th scope='col'><center>Hành Động</center></th>" +
                            "</tr>" +
                            "</thead>" +
                            "<tbody>";
                        response.data.forEach(function(wifi, wifiIndex) {
                            // Kiểm tra nếu tên WiFi là "Mạng ẩn"
                            var ssidDisplay = wifi.SSID === "Mạng ẩn" ?
                                "<span style='color:red;'>" + wifiEscapeHtml(wifi.SSID) + "</span>" :
                                wifiEscapeHtml(wifi.SSID);
                            tableHTML +=
                                "<tr>" +
                                "<td><center>" + ssidDisplay + "</center></td>" +
                                "<td><center>" + wifiEscapeHtml(wifi.BSSID) + "</center></td>" +
                                "<td><center>" + wifiEscapeHtml(wifi.Channel) + "</center></td>" +
                                "<td><center>" + wifiEscapeHtml(wifi.Rate) + "</center></td>" +
                                "<td><center>" + wifiEscapeHtml(wifi.Signal) + "</center></td>" +
                                "<td><center><font color=green>" + wifiEscapeHtml(wifi.Bars) + "</font></center></td>" +
                                "<td><center>" + wifiEscapeHtml(wifi.Security) + "</center></td>" +
                                '<td><center><button type="button" data-wifi-index="' + wifiIndex + '" class="btn btn-success rounded-pill wifi-connect-new"><i class="bi bi-arrows-angle-contract"></i> Kết Nối</button></center></td>' +
                                "</tr>";
                        });
                        tableHTML +=
                            "</tbody>" +
                            "</table>";
                        fileListDiv.innerHTML = tableHTML;
                        fileListDiv.querySelectorAll('.wifi-connect-new').forEach(function(button) {
                            button.addEventListener('click', function() {
                                var wifi = response.data[Number(button.dataset.wifiIndex)];
                                if (wifi) connectWifiNew(wifi.SSID, wifi.Security);
                            });
                        });
                    } else {
                        fileListDiv.innerHTML = '<p>Không có dữ liệu WiFi nào được tìm thấy.</p>';
                    }
                } else {
                    show_message(wifiApiErrorMessage(xhr, 'Yêu cầu không thành công, HTTP ' + xhr.status));
                }
                loading("hide");
            };
            xhr.onerror = function() {
                show_message('Truy vấn thất bại');
                loading("hide");
            };
            xhr.send();
        }

        // Kết nối tới WiFi

        function deleteWifi(ssid, uuid) {
            if (!ssid || ssid.trim() === '') {
                show_message('Tên WiFi không hợp lệ.');
                return;
            }
            var confirmConnect_del = confirm('Bạn có chắc chắn muốn xóa mạng wifi: ' + ssid);
            if (!confirmConnect_del) {
                loading("hide");
                return;
            }
            loading("show");
            var xhr = vbotCreateXhr();
            xhr.open('POST', 'includes/php_ajax/Wifi_Act.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            show_message('Xóa WiFi ' + ssid + ' thành công: ' + response.message);
                        } else {
                            show_message('Xóa WiFi ' + ssid + ' thất bại: ' + response.message);
                        }
                        Show_Wifi_List();
                    } catch (e) {
                        show_message('Lỗi phân tích phản hồi JSON: ' + e);
                    }
                } else {
                    show_message(wifiApiErrorMessage(xhr, 'Có lỗi xảy ra: HTTP ' + xhr.status));
                }
                loading("hide");
            };
            xhr.onerror = function() {
                loading("hide");
                show_message('Lỗi yêu cầu mạng.');
            };
            var data = 'Delete_Wifi=1&action=delete_wifi&wifiName=' + encodeURIComponent(ssid) +
                '&uuid=' + encodeURIComponent(uuid || '');
            xhr.send(data);
        }

        //Kết nối tới wifi đã lưu trước đó
        function connectWifiOld(ssid, uuid) {
            if (!ssid || ssid.trim() === '') {
                show_message('Tên WiFi không hợp lệ.');
                return;
            }
            loading("show");
            var xhr = vbotCreateXhr();
            xhr.open('POST', 'includes/php_ajax/Wifi_Act.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            show_message('<font color=green>Kết nối WiFi thành công: ' + response.message + '</font>');
                        } else {
                            show_message('<font color=red>Kết nối WiFi thất bại: ' + response.message + '</font>');
                        }
                    } catch (e) {
                        show_message('<font color=red>Lỗi phân tích phản hồi JSON: ' + e + '</font>');
                    }
                } else {
                    show_message('<font color=red>' + wifiApiErrorMessage(xhr, 'Có lỗi xảy ra: HTTP ' + xhr.status) + '</font>');
                }
                loading("hide");
                getWifiNetworkInformation();
            };
            xhr.onerror = function() {
                loading("hide");
                show_message('Lỗi yêu cầu mạng.');
            };
            var data = 'Connect_Wifi=1&action=connect_wifi&password=&ssid=' + encodeURIComponent(ssid) +
                '&uuid=' + encodeURIComponent(uuid || '');
            xhr.send(data);
        }

        //Lấy Mật Khẩu Wifi
        function getWifiPassword(ssid, uuid) {
            loading("show");
            const url = "includes/php_ajax/Wifi_Act.php?Get_Password_Wifi=1&ssid=" + encodeURIComponent(ssid) +
                "&uuid=" + encodeURIComponent(uuid || '');
            const xhr = vbotCreateXhr();
            xhr.open("GET", url, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.success) {
                                console.log("Dữ liệu nhận được:", data.data);
                                data.data.forEach(function(wifiInfo) {
                                    show_message("<b>Tên Wifi:</b> " + wifiEscapeHtml(wifiInfo.ssid) + "<br/><b>Mật Khẩu:</b> <font color=red>" + wifiEscapeHtml(wifiInfo.password) + "</font><br/><b>Địa Chỉ Mac:</b> " + wifiEscapeHtml(wifiInfo['seen_bssids']) + "<br/><b>UUID:</b> " + wifiEscapeHtml(wifiInfo.uuid) + "<br/><b>Timestamp:</b> " + wifiEscapeHtml(wifiInfo.timestamp));
                                    loading("hide");
                                });
                            } else {
                                loading("hide");
                                show_message("Có lỗi xảy ra: " + data.message);
                            }
                        } catch (e) {
                            loading("hide");
                            show_message("Lỗi khi phân tích dữ liệu JSON: " + e);
                        }
                    } else {
                        loading("hide");
                        show_message(wifiApiErrorMessage(xhr, "Lỗi khi gửi yêu cầu: HTTP " + xhr.status));
                    }
                }
            };
            xhr.send();
        }

        //Lấy thông tin mạng đang kết nối
        function getWifiNetworkInformation() {
            var xhr = vbotCreateXhr();
            xhr.open('GET', 'includes/php_ajax/Wifi_Act.php?Wifi_Network_Information', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        var fileListDiv = document.getElementById('wifiInfoResult');
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                //console.log('Dữ liệu Wi-Fi:' +xhr.responseText);
								var safeWifiValue = function(value) {
									return vbotEscapeHtml(value == null ? '' : value);
								};
								var tableHTML = '<table class="table table-bordered border-primary"><thead>';
								tableHTML += '<tr><th colspan="4" style="text-align: center; vertical-align: middle;">Mạng Wifi Đang Kết Nối: <font color=red>' + safeWifiValue(response.data.ESSID) + '</font></th></tr>';
    tableHTML += '<tr><th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Thông Tin DHCP</th>';
      tableHTML += '<th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">IP Hiện Tại</th>';
      tableHTML += '<th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">DNS</th>';
      tableHTML += '<th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Gateway</th></tr></thead>';
  tableHTML += '<tbody><tr style="text-align: center; vertical-align: middle;"><td class="text-success">'+safeWifiValue(response.data.DHCP_Mode)+'</td><td class="text-success">'+safeWifiValue(response.data.IP)+'</td><td><p class="text-success">'+safeWifiValue(response.data.DNS)+'</p><hr/><p class="text-primary">'+safeWifiValue(response.data.DNS_Mode)+'</p></td><td><p class="text-success">'+safeWifiValue(response.data.Gateway)+'</p><hr/> <p class="text-primary">'+safeWifiValue(response.data.Gateway_Mode)+'</p></td></tr></tbody></table>';
                            fileListDiv.innerHTML = tableHTML;
							document.getElementById('connected_network_name').value = response.data.ESSID;
							document.getElementById('connected_network_name1').value = response.data.ESSID;
                            document.getElementById('wifi-profile-uuid').value=response.data.uuid || '';
                            if(window.wifiPopulateConfig) window.wifiPopulateConfig(response.data);
                            if(window.wifiLoadTransaction) window.wifiLoadTransaction();
							document.getElementById('dia_chi_ip_tinh').value = response.data.IP.split('/')[0] || '';
							document.getElementById('ip_gateway_modem').value = response.data.Gateway;
							const dnsArray = response.data.DNS.split(',').map(d => d.trim());
							if (dnsArray.length > 0) document.getElementById('set_dns_1').value = dnsArray[0];
							if (dnsArray.length > 1) document.getElementById('set_dns_2').value = dnsArray[1];
                            } else {
                                show_message('Lỗi:' + response.message);
                            }
                        } catch (e) {
                            show_message('Lỗi phân tích JSON:' + e);
                        }
                    } else {
                        show_message(wifiApiErrorMessage(xhr, 'Lỗi khi gửi yêu cầu, HTTP ' + xhr.status));
                    }
                }
            };
            xhr.onerror = function() {
                show_message('Lỗi khi gửi yêu cầu.');
            };
            xhr.send();
        }
        getWifiNetworkInformation();

//Đóng modal
function close_ip_modal() {
    var modalEl = document.getElementById('exampleModal_ipDong');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }
    modal.hide();
}

//Đặt Địa Chỉ IP Tĩnh
</script>
<script src="assets/js/wifi-management.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
</body>

</html>
