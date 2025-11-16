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
  <link href="assets/css/chatbot_head_bar.css?v=<?php echo $Cache_UI_Ver; ?>" rel="stylesheet">
  <style>
    #vbot_Scan_devices {
      max-height: 400px;
      overflow-y: auto;
      word-wrap: break-word;
    }

    #Recording_STT_mic_animation {
      display: none;
      height: 30px;
      width: 120px;
      margin: 10px auto;
      justify-content: center;
      align-items: center;
    }

    .waveform {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
      width: 100%;
    }

    .wave-bar {
      width: 4px;
      height: 10px;
      background-color: #28a745;
      margin: 0 2px;
      animation: wave 0.5s infinite ease-in-out;
    }

    .wave-bar:nth-child(2) {
      animation-delay: 0.1s;
    }

    .wave-bar:nth-child(3) {
      animation-delay: 0.2s;
    }

    .wave-bar:nth-child(4) {
      animation-delay: 0.3s;
    }

    .wave-bar:nth-child(5) {
      animation-delay: 0.4s;
    }

    .wave-bar:nth-child(6) {
      animation-delay: 0.5s;
    }

    .wave-bar:nth-child(7) {
      animation-delay: 0.6s;
    }

    .wave-bar:nth-child(8) {
      animation-delay: 0.7s;
    }

    .wave-bar:nth-child(9) {
      animation-delay: 0.8s;
    }

    .wave-bar:nth-child(10) {
      animation-delay: 0.9s;
    }

    @keyframes wave {
      0%,
      100% {
        height: 10px;
      }
      50% {
        height: 30px;
      }
    }

    .modal-footer {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    #contentIframe {
      width: 100 %;
      height: 100 %;
      border: none;
      display: block;
    }

    #iframeModal_header {
      background-color: #bcffcd;
      color: #0b7beb;
    }

	#searchResults {
		max-height: 300px;
		overflow-y: auto;
		max-width: 600px;
		width: 100%;
		padding: 0;
	}

	#searchResults .dropdown-item {
		white-space: normal;
		word-wrap: break-word;
		border-bottom: 1px solid rgba(0,0,0,0.1);
		padding: 5px 10px;
	}

	#searchResults .dropdown-item:last-child {
		border-bottom: none;
	}

	#searchResults .dropdown-item:hover {
		background-color: #fff9c4;
		color: #000;
		cursor: pointer;
	}

	.highlight {
		background-color: #c8e6c9 !important; /* xanh nhạt */
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
<!-- Tìm kiếm -->
  <div class="search-bar">
<div class="input-group flex-nowrap" title="Tìm kiếm nội dung trong trang">
  <input class="form-control border-success" type="text" id="searchInput" placeholder="Nhập nội dung tìm kiếm">
  <span class="input-group-text border-success" id="addon-wrapping"><i class="bi bi-search text-success"></i></span>
  </div>
 <ul class="dropdown-menu w-100 border-success" id="searchResults"></ul>
</div>
<!--END  Tìm kiếm -->
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
      <!-- Scan VBot trong cùng Lớp Mạng-->
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
              <input type="text" id="add_ip_vbot_server" class="form-control border-success" placeholder="Nhập địa chỉ ip thiết bị loa VBot, VD: 192.168.1.107">
              <button type="button" id="btn_add_ip_vbot_server" name="btn_add_ip_vbot_server" title="Thêm thiết bị VBot thủ công" class="btn btn-primary border-success" onclick="add_IP_VBot_Server()">Thêm</button>
            </div>
            <div id="vbot_Scan_devices"></div>
          </div>
        </div>
      </div>

      <!-- Scan Show Logs API-->
      <li class="nav-item nav-icon" title="Hiển Thị Dữ Liệu Logs VBot Assistant Theo Thời Gian Thực">
        <i class="bi bi-journal-text text-info" type="button" data-bs-toggle="modal" data-bs-target="#modalDialogScrollable_Show_Logs_API"></i>
      </li>
      <div class="modal fade" id="modalDialogScrollable_Show_Logs_API" tabindex="-1" data-bs-backdrop="false" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" id="Show_LogsAPI_size_setting">
          <div class="modal-content bg-secondary">
            <div id="welcome-message" class="welcome-message">
              Hiển Thị Dữ Liệu Logs VBot Assistant Theo Thời Gian Thực
              <div class="icon-group_chatbot">
                <i class="bi bi-arrows-fullscreen pe-3" id="Show_LogsAPI_fullscreen" onclick="Logs_API_toggleFullScreen()" title="Phóng to, thu nhỏ giao diện"></i>
                <i class="bi bi-x-lg text-danger" data-bs-dismiss="modal" id="Close_Logs_Head" title="Đóng"></i>
              </div>
            </div><br/>
            <center>
              <button type="button" class="btn btn-primary">
                <input class="form-check-input" title="Bật để hiển thị Logs" type="checkbox" id="fetchLogsCheckbox_Head">
                <label class="form-check-label" for="fetchLogsCheckbox_Head">Hiển thị Logs</label>
              </button>
              <button type="button" class="btn btn-danger" onclick="change_og_display_style('clear_api', 'clear_api', 'false')"><i class="bi bi-trash"></i> Xóa logs</button>
            </center>
            <div class="form-group">
              <br>
              <div class="form-control border-success text-info bg-dark" id="logsOutput_Head" style="height: 500px; overflow-y: auto; white-space: pre-wrap;"></div>
            </div>

          </div>
        </div>
      </div>
      <!--END Scan Show Logs API-->

      <!-- Modal iframe -->
      <div class="modal fade" id="iframeModal" tabindex="-1" data-bs-backdrop="false" aria-labelledby="iframeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable" id="iframeModal_size_setting">
          <div class="modal-content">
            <div class="modal-header" id="iframeModal_header">
              <div id="iframeModal_source">Vũ Tuyển - Comback Soon</div>
              <i class="bi bi-arrows-fullscreen pe-3 text-success ms-auto" id="iframeModal_fullscreen" onclick="iframeModal_toggleFullScreen()" title="Phóng to, thu nhỏ giao diện"></i>
              <i class="bi bi-x-lg text-danger" data-bs-dismiss="modal" title="Đóng"></i>
            </div>
            <div class="modal-body">
              <iframe id="contentIframe"></iframe>
            </div>
          </div>
        </div>
      </div>

      <!-- Chatbot Biểu tượng mở chatbox -->
      <li class="nav-item nav-icon">
        <i class="bi bi-chat-dots text-primary" type="button" onclick="fetchAndPopulateDevices_chatbot()" class="btn btn-primary" title="Mở ChatBot" data-bs-toggle="modal" data-bs-target="#modalDialogScrollable_chatbot"></i>
      </li>
      <div class="modal fade" id="modalDialogScrollable_chatbot" tabindex="-1" data-bs-backdrop="false" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" id="chatbot_size_setting">
          <div class="modal-content">

            <div id="welcome-message" class="welcome-message">
              VBot ChatBox:
              <select class="form-select border-success" name="source_chatbot_api" id="source_chatbot_api">
                <option value="<?php echo $URL_API_VBOT; ?>" data-full_name_chatbot_api="<?php echo $Config['contact_info']['full_name']; ?>" selected><?php echo $Config['contact_info']['full_name']; ?> - Mặc Định</option>
              </select>
              &nbsp;<div class="icon-group_chatbot">
                <i class="bi bi-arrow-repeat pe-3 text-success" onclick="scan_VBot_Device()" title="Tìm kiếm các thiết bị chạy VBot trong cùng lớp mạng Lan"></i>
                <i class="bi bi-arrows-fullscreen pe-3" id="chatbot_fullscreen" onclick="chatbot_toggleFullScreen()" title="Phóng to, thu nhỏ giao diện chatbox"></i>
                <i class="bi bi-x-lg text-danger" data-bs-dismiss="modal" title="Đóng ChatBox"></i>
              </div>
            </div>

            <div class="modal-body">
              <div id="chatbox_wrapper">
                <div id="chatbox"></div>
              </div>
            </div>
            <div class="modal-footer">

              <div id="Recording_STT_mic_animation">
                <div class="waveform">
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                  <div class="wave-bar"></div>
                </div>
              </div>
              <div class="input-group mb-3">
                <button class="btn btn-success border-success" onclick="Recording_STT()" title="Nhấn để kích hoạt và  nói từ Microphone"><i id="mic_icon_rec" class="bi bi-mic-fill"></i></button>
                <button class="btn btn-danger border-success" onclick="Recording_STT('stop')" title="Nhấn để dừng Microphone"><i class="bi bi-stop-circle-fill"></i></button>
                <input type="text" class="form-control border-success" id="user_input_chatbox" placeholder="Nhập tin nhắn...">
                <button id="send_button_chatbox" class="btn btn-primary border-success" title="Gửi tin nhắn"><i class="bi bi-send"></i>
                </button>
                <button id="re-load_button_chatbox" class="btn btn-info border-success" onclick="loadMessages()" title="Tải lại Chatbox"><i class="bi bi-arrow-repeat"></i>
                </button>
                <button id="clear_button_chatbox" class="btn btn-warning border-success" onclick="clearMessages()" title="Xóa lịch sử Chat"><i class="bi bi-trash"></i>
                </button>
                <!-- <button type="button" class="btn btn-danger border-success" data-bs-dismiss="modal" title="Đóng ChatBox"><i class="bi bi-x-lg"></i></button> -->
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- end Chatbot -->
      <!-- restart vbot -->
      <li class="nav-item dropdown pe-3">
        <a class="nav-item nav-icon" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-power text-danger" title="Lựa chọn hành động"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow POWER_CONTROL">
          <form method="POST" action="">
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
          if ($Config['contact_info']['user_login']['active']) {
            echo '<li>
                      <a class="dropdown-item d-flex align-items-center" onclick="loading(\'show\')" href="Login.php?logout">
                        <font color=red><i class="bi bi-box-arrow-right"></i>
                        <span>Đăng xuất</span></font>
                      </a>
                    </li>';
          }
          ?>
        </ul>
      </li>
    </ul>
  </nav>
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
              show_message("<b>Lỗi định dạng phản hồi từ máy chủ:</b><br/>" + xhr.responseText);
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