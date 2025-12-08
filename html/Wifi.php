<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

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
    $raw = shell_exec('nmcli connection show "'.$connectionName.'"');
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
                            <center>
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

<div class="form-floating mb-3">
<input type="text" readonly class="form-control border-danger" title="Tên Mạng Đang kết Nối" name="connected_network_name" id="connected_network_name" value="Địa Chỉ IP Tĩnh Cần Đặt">
<label for="connected_network_name">Tên Mạng Đang Kết Nối:</label>
</div>

<div class="form-floating mb-3">
<input type="text" class="form-control border-success" title="Nhập Địa Chỉ IP Tĩnh Bạn Muốn Đặt" name="dia_chi_ip_tinh" id="dia_chi_ip_tinh" value="Địa Chỉ IP Tĩnh Cần Đặt">
<label for="dia_chi_ip_tinh">Nhập Địa Chỉ IP Tĩnh:</label>
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
        //Reset cấu hình wifi
        function reset_All_Wifi() {
            if (confirm("Bạn có chắc chắn muốn xóa tất cả các kết nối Wi-Fi không?\nHành động này sẽ làm mất kết nối mạng hiện tại!\n\nVà hệ thống sẽ tạo điểm truy cập Wifi mới với tên: 'VBot Assistant' để bạn kết nối và cấu hình")) {
                const xhr = new XMLHttpRequest();
                xhr.open("GET", "includes/php_ajax/Wifi_Act.php?Reset_Wifi=true", true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            const response = JSON.parse(xhr.responseText);
                            show_message(response.message);
                        } else {
                            show_message("Lỗi khi gửi yêu cầu đặt lại cấu hình wifi");
                        }
                    }
                };
                xhr.send();
            }
        }

        //Hiển thị dang sách wifi đã kết nối
        function Show_Wifi_List() {
            loading("show");
            var xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.addEventListener("readystatechange", function() {
                if (this.readyState === 4) {
                    //console.log(this.responseText);
                    var response = JSON.parse(this.responseText);
                    var fileListDiv = document.getElementById('hienthiketqua');
                    fileListDiv.innerHTML = '';
                    // Kiểm tra xem response có thành công và có dữ liệu không
                    if (response.success && Array.isArray(response.data)) {
                        var table = '<table class="table table-bordered border-primary">';
                        table += '<thead><tr><th colspan="6" style="text-align: center; vertical-align: middle;">Danh Sách Wifi Đã Kết Nối</th></tr><tr><th style="text-align: center; vertical-align: middle;">Tên Wifi</th><th style="text-align: center; vertical-align: middle;">UUID</th><th style="text-align: center; vertical-align: middle;">Interface</th><th colspan="3" style="text-align: center; vertical-align: middle;">Hành Động</th></tr></thead>';
                        table += '<tbody>';
                        response.data.forEach(function(wifi) {
                            table += '<tr>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + wifi.ssid + '</td>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + wifi.uuid + '</td>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + wifi.interface + '</td>';
                            table += '<td style="text-align: center; vertical-align: middle;"><button onclick="connectWifiOld(\'' + wifi.ssid + '\')" class="btn btn-success rounded-pill"><i class="bi bi-arrows-angle-contract"></i> Kết Nối</button></td>';
                            table += '<td style="text-align: center; vertical-align: middle;"><button onclick="getWifiPassword(\'' + wifi.ssid + '\')" class="btn btn-primary rounded-pill"><i class="bi bi-info-circle"></i> Mật Khẩu</button></td>';
                            table += '<td style="text-align: center; vertical-align: middle;"><button onclick="deleteWifi(\'' + wifi.ssid + '\')" class="btn btn-danger rounded-pill"><i class="bi bi-trash3-fill"></i> Xóa</button></td>';
                            table += '</tr>';
                        });
                        table += '</tbody></table>';
                        fileListDiv.innerHTML = table;
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
            var xhr = new XMLHttpRequest();
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
                        response.data.forEach(function(wifi) {
                            // Kiểm tra nếu tên WiFi là "Mạng ẩn"
                            var ssidDisplay = wifi.SSID === "Mạng ẩn" ?
                                "<span style='color:red;'>" + wifi.SSID + "</span>" :
                                wifi.SSID;
                            tableHTML +=
                                "<tr>" +
                                "<td><center>" + ssidDisplay + "</center></td>" +
                                "<td><center>" + wifi.BSSID + "</center></td>" +
                                "<td><center>" + wifi.Channel + "</center></td>" +
                                "<td><center>" + wifi.Rate + "</center></td>" +
                                "<td><center>" + wifi.Signal + "</center></td>" +
                                "<td><center><font color=green>" + wifi.Bars + "</font></center></td>" +
                                "<td><center>" + wifi.Security + "</center></td>" +
                                '<td><center><button onclick="connectWifiNew(\'' + wifi.SSID + '\', \'' + wifi.Security + '\')" class="btn btn-success rounded-pill"><i class="bi bi-arrows-angle-contract"></i> Kết Nối</button></center></td>' +
                                "</tr>";
                        });
                        tableHTML +=
                            "</tbody>" +
                            "</table>";
                        fileListDiv.innerHTML = tableHTML;
                    } else {
                        fileListDiv.innerHTML = '<p>Không có dữ liệu WiFi nào được tìm thấy.</p>';
                    }
                } else {
                    show_message('Yêu cầu không thành công với trạng thái: ' + xhr.status);
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
        function connectWifiNew(ssid, security, action) {
            loading("show");
            var password = '';
            var hiddenSSID = '';
            if (security === '' || security === null) {
                var confirmConnect = confirm('Mạng không có mật khẩu. Bạn có chắc chắn muốn kết nối?');
                if (!confirmConnect) {
                    loading("hide");
                    return;
                }
            } else {
                // Nếu tên WiFi có chữ "Mạng ẩn", yêu cầu nhập cả SSID và mật khẩu
                if (ssid.includes('Mạng ẩn')) {
                    do {
                        hiddenSSID = prompt('Nhập tên mạng WiFi bị ẩn:');
                        if (hiddenSSID === null) {
                            //console.log('Nhập SSID bị hủy');
                            loading("hide");
                            return;
                        }
                        if (hiddenSSID.trim().length < 1) {
                            show_message('Tên Wifi phải có ít nhất 1 ký tự. Vui lòng nhập lại.');
                        }
                    } while (hiddenSSID.trim().length < 1);
                    do {
                        password = prompt('Nhập mật khẩu cho mạng WiFi ' + ssid + ' (ít nhất 8 ký tự):');
                        if (password === null) {
                            //console.log('Nhập mật khẩu bị hủy');
                            loading("hide");
                            return;
                        }
                        if (password.trim().length < 8) {
                            show_message('Mật khẩu phải có ít nhất 8 ký tự. Vui lòng nhập lại.');
                        }
                    } while (password.trim().length < 8);
                } else {
                    // Nếu không phải "Mạng ẩn", yêu cầu nhập mật khẩu và kiểm tra độ dài
                    do {
                        password = prompt('Nhập mật khẩu cho mạng WiFi ' + ssid + ' (ít nhất 8 ký tự):');
                        if (password === null) {
                            //console.log('Nhập mật khẩu bị hủy');
                            loading("hide");
                            return;
                        }
                        if (password.trim().length < 8) {
                            show_message('Mật khẩu phải có ít nhất 8 ký tự. Vui lòng nhập lại.');
                        }
                    } while (password.trim().length < 8);
                }
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'includes/php_ajax/Wifi_Act.php?Connect_Wifi', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        show_message('<font color=green>Kết nối thành công: ' + response.message + '</font>');
                    } else {
                        show_message('<font color=red>Kết nối thất bại: ' + response.message + '</font>');
                    }
                } else {
                    show_message('<font color=red>Có lỗi xảy ra: ' + xhr.statusText + '</font>');
                }
                loading("hide");
                getWifiNetworkInformation();
            };
            var data = 'action=connect_and_save_wifi' +
                '&ssid=' + encodeURIComponent(hiddenSSID || ssid) +
                '&password=' + encodeURIComponent(password);
            xhr.send(data);
        }

        //Xóa Wifi
        function deleteWifi(ssid) {
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
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'includes/php_ajax/Wifi_Act.php?Delete_Wifi', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
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
                    show_message('Có lỗi xảy ra:' + xhr.statusText);
                }
                loading("hide");
            };
            xhr.onerror = function() {
                loading("hide");
                show_message('Lỗi yêu cầu mạng.');
            };
            var data = 'action=delete_wifi&wifiName=' + encodeURIComponent(ssid);
            xhr.send(data);
        }

        //Kết nối tới wifi đã lưu trước đó
        function connectWifiOld(ssid) {
            if (!ssid || ssid.trim() === '') {
                show_message('Tên WiFi không hợp lệ.');
                return;
            }
            loading("show");
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'includes/php_ajax/Wifi_Act.php?Connect_Wifi', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
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
                    show_message('<font color=red>Có lỗi xảy ra: ' + xhr.statusText + '</font>');
                }
                loading("hide");
                getWifiNetworkInformation();
            };
            xhr.onerror = function() {
                loading("hide");
                show_message('Lỗi yêu cầu mạng.');
            };
            var data = 'action=connect_wifi&password=""&ssid=' + encodeURIComponent(ssid);
            xhr.send(data);
        }

        //Lấy Mật Khẩu Wifi
        function getWifiPassword(ssid) {
            loading("show");
            const url = "includes/php_ajax/Wifi_Act.php?Get_Password_Wifi&ssid=" + encodeURIComponent(ssid);
            const xhr = new XMLHttpRequest();
            xhr.open("GET", url, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.success) {
                                console.log("Dữ liệu nhận được:", data.data);
                                data.data.forEach(function(wifiInfo) {
                                    show_message("<b>Tên Wifi:</b> " + wifiInfo.ssid + "<br/><b>Mật Khẩu:</b> <font color=red>" + wifiInfo.password + "</font><br/><b>Địa Chỉ Mac:</b> " + wifiInfo['seen_bssids'] + "<br/><b>UUID:</b> " + wifiInfo.uuid + "<br/><b>Timestamp:</b> " + wifiInfo.timestamp);
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
                        show_message("Lỗi khi gửi yêu cầu: " + xhr.status);
                    }
                }
            };
            xhr.send();
        }

        //Lấy thông tin mạng đang kết nối
        function getWifiNetworkInformation() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'includes/php_ajax/Wifi_Act.php?Wifi_Network_Information', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        var fileListDiv = document.getElementById('wifiInfoResult');
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                //console.log('Dữ liệu Wi-Fi:' +xhr.responseText);
								var tableHTML = '<table class="table table-bordered border-primary"><thead>';
								tableHTML += '<tr><th colspan="4" style="text-align: center; vertical-align: middle;">Mạng Wifi Đang Kết Nối: <font color=red>' + response.data.ESSID + '</font></th></tr>';
    tableHTML += '<tr><th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Thông Tin DHCP</th>';
      tableHTML += '<th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">IP Hiện Tại</th>';
      tableHTML += '<th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">DNS</th>';
      tableHTML += '<th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Gateway</th></tr></thead>';
  tableHTML += '<tbody><tr style="text-align: center; vertical-align: middle;"><td class="text-success">'+response.data.DHCP_Mode+'</td><td class="text-success">'+response.data.IP+'</td><td><p class="text-success">'+response.data.DNS+'</p><hr/><p class="text-primary">'+response.data.DNS_Mode+'</p></td><td><p class="text-success">'+response.data.Gateway+'</p><hr/> <p class="text-primary">'+response.data.Gateway_Mode+'</p></td></tr></tbody></table>';
                            fileListDiv.innerHTML = tableHTML;
							document.getElementById('connected_network_name').value = response.data.ESSID;
							document.getElementById('connected_network_name1').value = response.data.ESSID;
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
                        show_message('Lỗi khi gửi yêu cầu:' + xhr.statusText);
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
function set_static_ip() {
    if (!confirm("Bạn có chắc muốn thiết lập IP tĩnh cho thiết bị này không?\n - Hãy Cẩn Thận Vì Rất Có Thể Bạn Sẽ Không Truy Cập Được Vào Thiết Bị Này Khi Cấu Hình IP Tĩnh Sai")) {
        return;
    }
    const ipField = document.getElementById("dia_chi_ip_tinh");
    const gatewayField = document.getElementById("ip_gateway_modem");
    const dns1Field = document.getElementById("set_dns_1");
    const dns2Field = document.getElementById("set_dns_2");
    const connected_network_name = document.getElementById('connected_network_name').value.trim();
    let ip = ipField.value.trim();
    let gateway = gatewayField.value.trim();
    let dns1 = dns1Field.value.trim();
    let dns2 = dns2Field.value.trim();
    if (connected_network_name === '') {
		show_message("Tên mạng đang kết nối không được để trống!");
        return;
    }
    if (!ip || !gateway) {
		show_message("Vui lòng nhập đầy đủ thông tin IP tĩnh, Gateway");
        return;
    }
    if (!dns1 && !dns2) {
        dns1 = "8.8.8.8";
        dns2 = "8.8.4.4";
        dns1Field.value = dns1;
        dns2Field.value = dns2;
    }
    const ipRegex = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
    if (!ipRegex.test(ip)) {
        show_message("Địa chỉ ip không hợp lệ.");
        return;
    }
    if (!ipRegex.test(gateway)) {
		show_message("Gateway không hợp lệ.");
        return;
    }
    if (dns1 && !ipRegex.test(dns1)) {
        show_message("DNS 1 không hợp lệ.");
        return;
    }
    if (dns2 && !ipRegex.test(dns2)) {
		show_message("DNS 2 không hợp lệ.");
        return;
    }
	if (!confirm(
		'Bạn có chắc muốn thiết lập IP tĩnh với các tham số:\n' +
		'Tên mạng: ' + connected_network_name + '\n' +
		'Địa Chỉ IP Tĩnh: ' + ip + '\n' +
		'Gateway Modem Route: ' + gateway + '\n' +
		'DNS 1: ' + dns1 + '\n' +
		'DNS 2: ' + dns2
	)) {
		return;
	}
	loading("show");
    let formData = new FormData();
    formData.append("action", "set_static_ip");
    formData.append("connected_network_name", connected_network_name);
    formData.append("ip", ip);
    formData.append("gateway", gateway);
    formData.append("dns1", dns1);
    formData.append("dns2", dns2);
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "includes/php_ajax/Wifi_Act.php", true);
    xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			loading("hide");
			try {
				var res = JSON.parse(xhr.responseText);
				if (res.success === true) {
					close_ip_modal();
					show_message('<font color=green>'+res.message+'</font><hr/> <button class="btn btn-danger rounded-pill" type="button" onclick="power_action_service(\'reboot_os\',\'Bạn có chắc chắn muốn khởi động lại toàn bộ hệ thống\')">Nhấn Vào Đây Để Khởi Động Lại Hệ Thống, REBOOT OS</button>');
				} else {
					show_message("Lỗi: " + (res.message || "Không xác định"));
				}
			} catch (e) {
				show_message("Lỗi phân tích phản hồi từ server: "+xhr.responseText);
			}
		}
		else {
			show_message("Lỗi xảy ra, không thể gửi yêu cầu");
		}
    };
    xhr.send(formData);
}

//Đặt lại DHCP Động Do Modem Route cấp phát
function set_dhcp_mode() {
	var connected_network_name = document.getElementById("connected_network_name").value.trim();
    if (connected_network_name === "") {
        alert("Không xác định được tên mạng đang kết nối.");
        return;
    }
    if (!confirm(connected_network_name+" Bạn có chắc muốn chuyển sang DHCP (IP động do Modem, Route Cung Cấp)?")) {
        return;
    }
	loading("show");
    var formData = new FormData();
    formData.append("action", "use_dhcp_automatically");
    formData.append("connected_network_name", connected_network_name);
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "includes/php_ajax/Wifi_Act.php", true);
    xhr.onload = function () {
        if (xhr.status === 200) {
			loading("hide");
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
					close_ip_modal();
                    show_message('<font color=green>'+res.message+'</font><hr/> <button class="btn btn-danger rounded-pill" type="button" onclick="power_action_service(\'reboot_os\',\'Bạn có chắc chắn muốn khởi động lại toàn bộ hệ thống\')">Nhấn Vào Đây Để Khởi Động Lại Hệ Thống, REBOOT OS</button>');
                } else {
                    show_message("Lỗi: " + res.message);
                }
            } catch (e) {
				show_message("Lỗi phản hồi từ server: "+e);
            }
        } else {
			loading("hide");
            show_message("Lỗi xảy ra, không thể gửi yêu cầu");
        }
    };
    xhr.send(formData);
}

//Cấu Hình DNS
function set_dns_only() {
    const dns1 = document.getElementById("set_dns_only_1").value.trim();
    const dns2 = document.getElementById("set_dns_only_2").value.trim();
    const connectionName = document.getElementById("connected_network_name").value.trim();
    let d1 = dns1 !== "" ? dns1 : "8.8.8.8";
    let d2 = dns2 !== "" ? dns2 : "8.8.4.4";
    if (!confirm(
        'Bạn có chắc muốn thiết lập DNS cho '+connectionName+':\n' +
        'DNS 1: ' + d1 + '\nDNS 2: ' + d2
    )) {return;}
	loading("show");
    let formData = new FormData();
    formData.append("action", "set_dns_only");
    formData.append("connection_name", connectionName);
    formData.append("dns1", d1);
    formData.append("dns2", d2);
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "includes/php_ajax/Wifi_Act.php", true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            loading("hide");
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.success) {
                    let modal = bootstrap.Modal.getInstance(document.getElementById('exampleModal_dns_only'));
                    if (modal) modal.hide();
					show_message(res.message+'<hr/><button class="btn btn-danger rounded-pill" type="button" onclick="power_action_service(\'reboot_os\',\'Bạn có chắc chắn muốn khởi động lại toàn bộ hệ thống\')">Nhấn Vào Đây Để Khởi Động Lại Hệ Thống, REBOOT OS</button>');
                } else {
					show_message("Lỗi: "+res.message);
                }
            } catch (e) {
				show_message("Lỗi không xác định: "+xhr.responseText);
            }
        }
    };
    xhr.send(formData);
}

//Đặt DNS Mặc Định DHCP MODEM, Route Cấp
function resetDNS_DHCP() {
    if (!confirm("Bạn có chắc muốn dùng DNS mặc định từ DHCP Modem, Route Cấp Phát?")) return;
    loading("show");
    let formData = new FormData();
	const connectionName = document.getElementById("connected_network_name").value.trim();
    formData.append("action", "reset_dns_dhcp");
    formData.append("connection_name", connectionName);
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "includes/php_ajax/Wifi_Act.php", true);
    xhr.onload = function () {
        let resp = {};
        try { resp = JSON.parse(xhr.responseText); } catch (e) {
			loading("hide");
			show_message("Lỗi không xác định: "+xhr.responseText);
		}
        if (resp.success) {
			let modal = bootstrap.Modal.getInstance(document.getElementById('exampleModal_dns_only'));
			if (modal) modal.hide();
			loading("hide");
            show_message(resp.message+'<hr/><button class="btn btn-danger rounded-pill" type="button" onclick="power_action_service(\'reboot_os\',\'Bạn có chắc chắn muốn khởi động lại toàn bộ hệ thống\')">Nhấn Vào Đây Để Khởi Động Lại Hệ Thống, REBOOT OS</button>');
        } else {
			loading("hide");
			show_message("Lỗi: " + resp.message + "\nCMD: " + resp.cmd);
        }
    };
    xhr.send(formData);
}
</script>
</body>

</html>