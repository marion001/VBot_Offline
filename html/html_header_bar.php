<?php
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
  #Facebook: https://www.facebook.com/TWFyaW9uMDAx
  include 'Configuration.php';
  ?>
<head>
  <title>VBot Assistant - <?php echo $Config['contact_info']['full_name'] ?></title>
  <!-- css ChatBot -->
  <link href="assets/css/chatbot_head_bar.css" rel="stylesheet">
  <style>
    #vbot_Scan_devices {
    max-height: 400px;
    overflow-y: auto;
    word-wrap: break-word;
    }
  </style>
</head>
<header id="header" class="header fixed-top d-flex align-items-center">
  <div class="d-flex align-items-center justify-content-between">
    <a href="index.php" onclick="loading('show')" class="logo d-flex align-items-center">
    <img src="assets/img/logo.png" alt="">
    <span class="d-none d-lg-block">VBot Assistant</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div>
  <!-- End Logo -->
  <nav id="vbot_header_bar" class="header-nav ms-auto">
    <ul class="d-flex align-items-center">
      <div id="container_time" class="border-success pe-3">
        <div id="day-date-container_time">
          <div id="days"></div>
          ,&nbsp;
          <div id="dates"></div>
        </div>
        <font color="red">
          <div id="times"></div>
        </font>
      </div>
      <li class="nav-item dropdown">
        <?php
          include 'Notify.php';
          ?>
      </li>
      <!-- End Notification Nav -->
      <li class="nav-item nav-icon" title="Tìm kiếm các thiết bị chạy VBot trong cùng lớp mạng">
        <i class="bi bi-radar text-success" type="button" data-bs-toggle="modal" data-bs-target="#modalDialogScrollable_VBot_Scan_Devicde" onclick="get_vbotScanDevices()"></i>
      </li>
      <div class="modal fade" id="modalDialogScrollable_VBot_Scan_Devicde" tabindex="-1" data-bs-backdrop="false" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" id="vbotScan_size_setting">
          <div class="modal-content">
            <div id="welcome-message" class="welcome-message">
              Tìm Kiếm Các Thiết Bị Chạy VBot Trong Cùng Lớp Mạng
              <div class="icon-group_chatbot">
                <i class="bi bi-trash text-danger pe-3" title="Xóa Dữ Liệu Tìm Kiếm" onclick="clearAllDevices_vbotScanDevices()"></i>
                <i class="bi bi-arrow-repeat refresh-btn pe-3 text-success" title="Kiểm tra, tải lại trạng thái hoạt động của các thiết bị chạy VBot" onclick="check_Device_Status_VBot_Server()"></i>
                <i class="bi bi-arrows-fullscreen pe-3" id="vbotScan_fullscreen" onclick="vbotScan_toggleFullScreen()" title="Phóng to, thu nhỏ giao diện"></i>
                <i class="bi bi-x-lg text-danger" data-bs-dismiss="modal" title="Đóng"></i>
              </div>
            </div>
            <br/>
            <button type="button" class="btn btn-warning" onclick="scan_VBot_Device()"><i class="bi bi-radar"></i> Quét Thiết Bị</button>
			<br/>
            <div class="input-group mb-3">
  <span class="input-group-text border-success">Thêm Thủ Công:</span>
  <input type="text" id="add_ip_vbot_server" class="form-control border-success" placeholder="Nhập địa chỉ ip thiết bị loa VBot">
<button type="button" id="btn_add_ip_vbot_server" name="btn_add_ip_vbot_server" title="Thêm thiết bị VBot thủ công" class="btn btn-primary border-success" onclick="add_IP_VBot_Server()">Thêm</button>
</div>

            <div id="vbot_Scan_devices"></div>
          </div>
        </div>
      </div>
      <!-- Bluetooth -->
      <?php
        if (isset($Config['bluetooth']['active']) && $Config['bluetooth']['active'] == true) {
        
        echo '<a class="nav-item nav-icon" data-bs-toggle="modal" data-bs-target="#modalDialogScrollable_Bluetooth_Scan_Devicde" onclick="loadBluetoothDevices()">
                   <i class="bi bi-bluetooth text-success"></i>
                  </a>';
        }
        ?>
      <div class="modal fade" id="modalDialogScrollable_Bluetooth_Scan_Devicde" tabindex="-1" data-bs-backdrop="false" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" id="vbotBluetooth_size_setting">
          <div class="modal-content" id="BLT_modal-content_Style" style="height: 50%; overflow-y: auto;">
            <div id="welcome-message" class="welcome-message">
              <div class="input-group mb-3" title="Bật/tắt nguồn Module Bluetooth">
                <i class="bi bi-bluetooth text-success"></i> Bluetooth Power:&nbsp;
                <div class="form-switch">
                  <input class="form-check-input" type="checkbox" id="bluetooth_power" name="bluetooth_power" onclick="bluetooth_control('power', this.checked)">
                </div>
                <i class="bi bi-activity text-success" title="Kiểm tra trạng thái kết nối Bluetooth" onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_connect_devices']; ?>')"></i>
              </div>
              </center>
              <div class="icon-group_chatbot">
                <i class="bi bi-arrows-fullscreen pe-3" id="vbotBluetooth_fullscreen" onclick="vbotBluetooth_toggleFullScreen()" title="Phóng to, thu nhỏ giao diện"></i>
                <i class="bi bi-x-lg text-danger" data-bs-dismiss="modal" title="Đóng"></i>
              </div>
            </div>
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
              <div class="container-fluid">
                <div class="input-group">
                  <button type="button" title="Tìm kiếm các thiết bị Bluetooth" class="btn btn-primary" onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['pair']; ?>')"><i class="bi bi-search"></i> Tìm Kiếm</button>
                  <button type="button" title="Khởi động lại Module Bluetooth" class="btn btn-danger" onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['reset']; ?>')"><i class="bi bi-bootstrap-reboot"></i> Restart</button>
                  <button type="button" title="Xóa dữ liệu đã lưu trong bộ nhớ Memory" class="btn btn-info" onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['clear_memory']; ?>')"><i class="bi bi-recycle"></i> Clear Memory</button>
                  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown_ble_cmd" aria-controls="navbarNavDarkDropdown_ble_cmd" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                  <div class="collapse navbar-collapse" id="navbarNavDarkDropdown_ble_cmd">
                    <ul class="navbar-nav">
                      <li class="nav-item dropdown">
                        <button class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-gear-wide-connected"></i> Nâng Cao</button>
                        <ul class="dropdown-menu dropdown-menu-dark" style="max-height: 200px; overflow-y: auto;">
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['version']; ?>')" title="Kiểm tra phiên bản Module Bluetooth"><a class="dropdown-item text-danger">Kiểm Tra Phiên Bản Module Bluetooth</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_connect_devices']; ?>')" title="Kiểm tra trạng thái kết nối Bluetooth với thiết bị khác"><a class="dropdown-item text-danger">Kiểm Tra Trạng Thái Kết Nối Bluetooth</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_mode']; ?>')" title="Kiểm tra Module Bluetooth đang ở chế độ Thu hay Phát"><a class="dropdown-item text-danger">Kiểm Tra Chế Độ Hiện Tại Của Module</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['reset']; ?>')" title="Khởi động lại Module Bluetooth"><a class="dropdown-item text-danger">Khởi Động Lại Module Bluetooth</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['dis_connect_devices']; ?>')" title="Ngắt kết nối hiện tại"><a class="dropdown-item text-danger">Ngắt Kết Nối Hiện Tại</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_volume']; ?>')" title="Kiểm tra âm lượng của Module Bluetooth"><a class="dropdown-item text-danger">Kiểm Tra Âm Lượng</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_channel']; ?>')" title="Kiểm tra nguồn âm thanh hiện tại của Module Bluetooth"><a class="dropdown-item text-danger">Kiểm Tra Nguồn Âm Thanh</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_baud']; ?>')" title="Kiểm tra tốc độ truyền giao tiếp UART BAUD Rate"><a class="dropdown-item text-danger">Kiểm Tra Baud Rate</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_memory']; ?>')"><a class="dropdown-item text-danger" title="Kiểm tra dữ liệu của thiết bị kết nối được lưu trong bộ nhớ Memory">Kiểm Tra Dữ Liệu Memory</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_name']; ?>')"><a class="dropdown-item text-danger" title="Kiểm tra tên của Module Bluetooth">Kiểm Tra Tên Module Bluetooth</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['check_mac_address']; ?>')"><a class="dropdown-item text-danger" title="Kiểm tra địa chỉ Mac của Module Bluetooth">Kiểm Tra Địa Chỉ Mac Module Bluetooth</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['clear_memory']; ?>')"><a class="dropdown-item text-danger" title="Dọn dẹp dữ liệu được lưu trong bộ nhớ Memory">Xóa Dữ Liệu Trong Memory</a></li>
                          <li onclick="bluetooth_control('command', '<?php echo $BLE_CMD['cmd']['power_off']; ?>')"><a class="dropdown-item text-danger" title="Tắt Module Power OFF">Tắt Module Power OFF</a></li>
                        </ul>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="input-group mb-3">
                <span class="input-group-text border-success" id="basic-addon1"><i class="bi bi-terminal-fill"></i></span>
                <input type="text" title="Nhập lệnh AT+" class="form-control border-success" name="ble_Command_UI" ID="ble_Command_UI" placeholder="AT+">
                <button title="Gửi tập lệnh AT+" class="btn btn-success border-success" onclick="bluetooth_command_at_input()" type="button">Command</button>
              </div>
            </nav>
            <div id="Bluetooth_Scan_devices"></div>
          </div>
        </div>
      </div>
      <!-- END Bluetooth -->
      <!-- Chatbot Biểu tượng mở chatbox -->
      <li class="nav-item nav-icon">
        <i class="bi bi-chat-dots text-primary" type="button" class="btn btn-primary" title="Mở ChatBot" data-bs-toggle="modal" data-bs-target="#modalDialogScrollable_chatbot"></i>
      </li>
      <div class="modal fade" id="modalDialogScrollable_chatbot" tabindex="-1" data-bs-backdrop="false" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" id="chatbot_size_setting">
          <div class="modal-content">
            <div id="welcome-message" class="welcome-message">
              VBot-ChatBox
              <div class="icon-group_chatbot">
                <i class="bi bi-arrow-repeat pe-3" onclick="loadMessages()" title="Tải lại Chatbox"></i>
                <i class="bi bi-arrows-fullscreen pe-3" id="chatbot_fullscreen" onclick="chatbot_toggleFullScreen()" title="Phóng to, thu nhỏ giao diện chatbox"></i>
                <i class="bi bi-x-lg" data-bs-dismiss="modal" title="Đóng ChatBox"></i>
              </div>
            </div>
            <div class="modal-body">
              <div id="chatbox_wrapper">
                <div id="chatbox"></div>
              </div>
            </div>
            <div class="modal-footer">
              <div class="input-group mb-3">
                <button class="btn btn-info border-success" onclick="Recording_STT('start', '6')"><i class="bi bi-mic"></i></button>
                <input type="text" class="form-control border-success" id="user_input_chatbox" placeholder="Nhập tin nhắn...">
                <button id="send_button_chatbox" class="btn btn-primary border-success" title="Gửi tin nhắn"><i class="bi bi-send"></i>
                </button>
                <button id="re-load_button_chatbox" class="btn btn-info border-success" onclick="loadMessages()" title="Tải lại Chatbox"><i class="bi bi-arrow-repeat"></i>
                </button>
                <button id="clear_button_chatbox" class="btn btn-warning border-success" onclick="clearMessages()" title="Xóa lịch sử Chat"><i class="bi bi-trash"></i>
                </button>
                <button type="button" class="btn btn-danger border-success" data-bs-dismiss="modal" title="Đóng ChatBox"><i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- end Chatbot --> 
      <!-- restart vbot -->	
      <li class="nav-item dropdown pe-3">
        <a class="nav-item nav-icon" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-power text-danger"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow POWER_CONTROL">
          <form method="POST"	action="">
            <li>
              <a class="dropdown-item d-flex align-items-center" onclick="power_action_service('start_vbot_service','Khởi chạy chương trình VBot')">
              <i class="bi bi-align-start text-success"></i>
              <span class="text-primary">Start VBot</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" onclick="power_action_service('stop_vbot_service','Bạn có chắc chắn muốn dừng chương trình VBot')">
              <i class="bi bi-stop-btn text-danger"></i>
              <span class="text-primary">Stop VBot</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" onclick="power_action_service('restart_vbot_service','Bạn có chắc chắn muốn khởi động lại chương trình VBot')">
              <i class="bi bi-arrow-repeat text-warning" title="Khởi động lại chương trình VBot"></i>
              <span class="text-primary">Restart VBot</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" onclick="power_action_service('reboot_os','Bạn có chắc chắn muốn khởi động lại toàn bộ hệ thống')">
              <i class="bi bi-bootstrap-reboot text-primary"></i>
              <span class="text-danger">Reboot OS</span>
              </a>
            </li>
          </form>
        </ul>
      </li>
      <!-- end restart vbot -->		
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
        <img src="<?php echo $Avata_File; ?>" alt="Profile" class="rounded-circle">
        <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $Config['contact_info']['full_name']; ?></span>
        </a><!-- End Profile Iamge Icon -->
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6><?php echo $Config['contact_info']['full_name']; ?></h6>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center" onclick="loading('show')" href="Users_Profile.php">
            <i class="bi bi-person"></i>
            <span>Cá nhân</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <!--
            <button id="themeToggle" class="btn btn-primary"><i class="bi bi-moon"></i></button>
            -->
          <li>
            <a class="dropdown-item d-flex align-items-center" id="themeToggle">
            <span>Chế độ tối</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center" href="FAQ.php">
            <i class="bi bi-question-circle"></i>
            <span>Hướng Dẫn</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <?php
            if ($Config['contact_info']['user_login']['active']){
            	echo '<li>
                      <a class="dropdown-item d-flex align-items-center" onclick="loading(\'show\')" href="Login.php?logout">
                        <font color=red><i class="bi bi-box-arrow-right"></i>
                        <span>Đăng xuất</span></font>
                      </a>
                    </li>';
            }
            ?>
        </ul>
        <!-- End Profile Dropdown Items -->
      </li>
      <!-- End Profile Nav -->
    </ul>
  </nav>
  <!-- End Icons Navigation -->
  <script>
    //Khởi động lại chương trình VBot html_header_bar.php
    function power_action_service(action_name, mess_name) {
        if (!confirm(mess_name)) {
            return;
        }
        loading('show');
        var xhr = new XMLHttpRequest();
        var url = "includes/php_ajax/Check_Connection.php?" + action_name;
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                loading('hide');
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showMessagePHP(response.message);
                        } else {
                            show_message("Lỗi: " + response.message);
                        }
                    } catch (e) {
                        show_message("<b>Lỗi định dạng phản hồi từ máy chủ:</b><br/>"+xhr.responseText);
                    }
                } else {
                    show_message("Yêu cầu thất bại với mã trạng thái: " + xhr.status);
                }
            }
        };
        xhr.open("GET", url, true);
        xhr.send();
    }
    
    
  </script>
</header>