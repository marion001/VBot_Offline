<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
?>
<?php
if ($Config['contact_info']['user_login']['active']){
session_start();
// Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
if (!isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))) {
    // Nếu chưa đăng nhập hoặc đã quá 12 tiếng, hủy session và chuyển hướng đến trang đăng nhập
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
}
// Cập nhật lại thời gian đăng nhập để kéo dài thời gian session
//$_SESSION['user_login']['login_time'] = time();
}
?>

<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>

<body>
<!-- ======= Header ======= -->
<?php
include 'html_header_bar.php'; 
?>
<!-- End Header -->

  <!-- ======= Sidebar ======= -->
<?php
include 'html_sidebar.php';
?>
<!-- End Sidebar-->

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
	<!-- End Page Title -->
	    <section class="section">
        <div class="row">
		<div class="col-lg-12">
		<div class="card">
            <div class="card-body">
			<br/>
		<center>
<div id="wifiInfoResult"></div><br/>
<button id="loadWifiButton" name="loadWifiButton" class="btn btn-primary rounded-pill" onclick="Show_Wifi_List()">Danh Sách Wifi Đã Kết Nối</button>
<button id="scanWifiButton" class="btn btn-secondary rounded-pill" onclick="fetchAndDisplayWifiList()">Quét Mạng Wifi</button>
</center>
<br/>
<div id="hienthiketqua"></div>
		</div>
		</div>
		</div>
		</div>
		</section>
	
</main>


  <!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Template Main JS File -->
<?php
include 'html_js.php';
?>
 
 
<script>
//Hiển thị dang sách wifi đã kết nối
function Show_Wifi_List() {
	loading("show");
    var xhr = new XMLHttpRequest();
    xhr.withCredentials = true;
    xhr.addEventListener("readystatechange", function() {
        if(this.readyState === 4) {
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
                    table += '<td style="text-align: center; vertical-align: middle;"><button onclick="connectWifiOld(\''+wifi.ssid+'\')" class="btn btn-success rounded-pill"><i class="bi bi-arrows-angle-contract"></i> Kết Nối</button></td>';
                    table += '<td style="text-align: center; vertical-align: middle;"><button onclick="getWifiPassword(\''+wifi.ssid+'\')" class="btn btn-primary rounded-pill"><i class="bi bi-info-circle"></i> Mật Khẩu</button></td>';
                    table += '<td style="text-align: center; vertical-align: middle;"><button onclick="deleteWifi(\''+wifi.ssid+'\')" class="btn btn-danger rounded-pill"><i class="bi bi-trash3-fill"></i> Xóa</button></td>';
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
                    var ssidDisplay = wifi.SSID === "Mạng ẩn" 
                        ? "<span style='color:red;'>" + wifi.SSID + "</span>" 
                        : wifi.SSID;
                    tableHTML += 
                        "<tr>" +
                            "<td><center>" + ssidDisplay + "</center></td>" +
                            "<td><center>" + wifi.BSSID + "</center></td>" +
                            "<td><center>" + wifi.Channel + "</center></td>" +
                            "<td><center>" + wifi.Rate + "</center></td>" +
                            "<td><center>" + wifi.Signal + "</center></td>" +
                            "<td><center><font color=green>" + wifi.Bars + "</font></center></td>" +
                            "<td><center>" + wifi.Security + "</center></td>" +
                            '<td><center><button onclick="connectWifiNew(\''+wifi.SSID+'\', \''+wifi.Security+'\')" class="btn btn-success rounded-pill"><i class="bi bi-arrows-angle-contract"></i> Kết Nối</button></center></td>' +
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
    // Nếu security rỗng hoặc null, yêu cầu xác nhận kết nối
    if (security === '' || security === null) {
        var confirmConnect = confirm('Mạng không có mật khẩu. Bạn có chắc chắn muốn kết nối?');
        if (!confirmConnect) {
            //console.log('Kết nối bị hủy');
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
                password = prompt('Nhập mật khẩu cho mạng WiFi '+ssid+' (ít nhất 8 ký tự):');
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
                password = prompt('Nhập mật khẩu cho mạng WiFi '+ssid+' (ít nhất 8 ký tự):');
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
                show_message('<font color=green>Kết nối thành công: ' + response.message +'</font>');
            } else {
                show_message('<font color=red>Kết nối thất bại: ' + response.message+'</font>');
            }
        } else {
            show_message('<font color=red>Có lỗi xảy ra: ' + xhr.statusText+ '</font>');
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
        var confirmConnect_del = confirm('Bạn có chắc chắn muốn xóa mạng wifi: '+ssid);
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
                    show_message('Xóa WiFi '+ssid+' thành công: ' +response.message);
					
                } else {
                    show_message('Xóa WiFi '+ssid+' thất bại: ' +response.message);
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
                    show_message('<font color=green>Kết nối WiFi thành công: ' + response.message+'</font>');
                } else {
                    show_message('<font color=red>Kết nối WiFi thất bại: ' + response.message+'</font>');
                }
            } catch (e) {
                show_message('<font color=red>Lỗi phân tích phản hồi JSON: ' +e+'</font>');
            }
        } else {
            show_message('<font color=red>Có lỗi xảy ra: ' + xhr.statusText+'</font>');
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
							show_message("<b>Tên Wifi:</b> " +wifiInfo.ssid+"<br/><b>Mật Khẩu:</b> <font color=red>" +wifiInfo.password+"</font><br/><b>Địa Chỉ Mac:</b> " +wifiInfo['seen_bssids']+"<br/><b>UUID:</b> " +wifiInfo.uuid+"<br/><b>Timestamp:</b> "+wifiInfo.timestamp);
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
						var tableHTML = "<b>Mạng Wifi Đang Kết Nối: ";
							tableHTML += '<font color=red>'+response.data.ESSID+'</font></b>';
						fileListDiv.innerHTML = tableHTML;
                    } else {
                        show_message('Lỗi:' +response.message);
                    }
                } catch (e) {
                    show_message('Lỗi phân tích JSON:' +e);
                }
            } else {
                show_message('Lỗi khi gửi yêu cầu:' +xhr.statusText);
            }
        }
    };
    xhr.onerror = function() {
        show_message('Lỗi khi gửi yêu cầu.');
    };
    xhr.send();
}

getWifiNetworkInformation();
</script>
</body>
</html>