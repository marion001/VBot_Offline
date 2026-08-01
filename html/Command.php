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

// Khởi tạo biến để lưu output
$output = '';

//Mọi thao tác giao diện đã được chuyển sang Command_Ajax.php với CSRF và JSON.
//Chỉ giữ POST trực tiếp cho ô Terminal chạy lệnh tùy ý trên chính trang này.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['commandd'])) {
  $legacyAction = '';
  foreach (array_keys($_POST) as $postKey) {
    if (!in_array($postKey, ['csrf_token', 'commandnd'], true)) {
      $legacyAction = (string) $postKey;
      break;
    }
  }
  error_log('Blocked legacy Command.php POST action: '.($legacyAction !== '' ? $legacyAction : '[unknown]'));
  $_POST = [];
  http_response_code(410);
  $output = 'Thao tác POST cũ đã bị vô hiệu. Vui lòng tải lại trang để sử dụng cơ chế AJAX mới.';
}

$SSH_CONNECT_ERROR = "<center><h1><font color='red'>Không thể kết nối tới máy chủ SSH, Hãy Kiểm Tra Lại</font><br/><a href='Command.php'>Quay Lại</a></h1></center>";
$SSH2_AUTH_ERROR = "<center><h1><font color='red'>Xác thực SSH không thành công, Hãy kiểm tra lại thông tin đăng nhập SSH</font> <br/><a href='Command.php'>Quay Lại</a></h1></center>";
//Command
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commandd'])) {
  $commandnd = @$_POST['commandnd'];
  if (empty($commandnd)) {
    $output .= "$GET_current_USER@$HostName:$ ~> Hãy Nhập Lệnh Cần Thực Thi";
  } else {
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {
      die($SSH_CONNECT_ERROR);
    }
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
      die($SSH2_AUTH_ERROR);
    }
    $stream = ssh2_exec($connection, $commandnd);
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $output = "$GET_current_USER@$HostName:~ $ $commandnd\n";
    $output .=  stream_get_contents($stream_out);
  }
}




























//Cài Đặt Hành Động Với LCD













#Kết Thúc Cài Đặt Hành Động Với LCD



































































































//check_version_picovoice_porcupine
























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
      <h1>Dòng lệnh/Đầu cuối</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">Command/Terminal</li>
          <li class="breadcrumb-item active"><?php echo "<font color='green'>" . @trim(file_get_contents('/os_image_created.txt')) . "</font>" ?: "<font color='red'>VBot Assistant OS Image Build: N/A</font>"; ?></li>
        </ol>
      </nav>
    </div>
    <!-- End Page Title -->
    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <form method="POST" action="">
                <br />
                <div class="row g-3 d-flex justify-content-center">
                  <div class="col-auto d-flex flex-wrap justify-content-center gap-2">
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-danger dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                         <i class="bi bi-robot"></i> VBot Auto</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <li><button class="dropdown-item text-danger" name="auto_start" type="button" title="Chạy lại trương trình" onclick="command_php('auto_start')">Chạy</button></li>
                          <li><button class="dropdown-item text-danger" name="auto_restart" type="button" title="Tạm dừng trương trình đang chạy" onclick="command_php('auto_restart')">Khởi động lại</button></li>
                          <li><button class="dropdown-item text-danger" name="auto_stop" type="button" title="Tạm dừng trương trình đang chạy" onclick="command_php('auto_stop')">Dừng</button></li>
                          <li><button class="dropdown-item text-danger" name="auto_status" type="button" title="Tạm dừng trương trình đang chạy" onclick="command_php('auto_status')">Trạng thái</button></li>
                          <li><button class="dropdown-item text-danger" name="auto_enable" type="button" title="Tự động chạy trương trình khi hệ thống khởi động" onclick="command_php('auto_enable')">Kích hoạt</button></li>
                          <li><button class="dropdown-item text-danger" name="auto_disable" type="button" title="Vô hiệu hóa trương trình, không cho tự động chạy" onclick="command_php('auto_disable')">Vô hiệu</button></li>
                          <li><button class="dropdown-item text-danger" name="config_auto" type="button" title="Vô hiệu hóa trương trình, không cho tự động chạy" onclick="command_php('config_auto')">Cài đặt cấu hình Auto</button></li>
                        </ul>
                      </div>
                    </div>
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-warning dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                         <i class="bi bi-wifi"></i> OS Wifi</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <li>
                            <button class="dropdown-item text-danger" name="restart_auto_wifi" type="button" title="Khởi động lại Services Auto Wifi Manaager" onclick="command_php('restart_auto_wifi')">Restart Auto Wifi Manager</button>
                          </li>
                          <button class="dropdown-item text-danger" name="enable_auto_wifi" type="button" title="Kích Hoạt Services Auto Wifi Manaager" onclick="command_php('enable_auto_wifi')">Enable Auto Wifi Manager</button></li>
                          <button class="dropdown-item text-danger" name="auto_wifi_manager_only" type="button" title="Chỉ Cài Đặt Auto Wifi Manager Và Tạo Điểm truy Cập AP" onclick="command_php('auto_wifi_manager_only')">Chỉ Install Auto Wifi Manager</button></li>
                          <button class="dropdown-item text-danger" name="auto_wifi_manager_and_speaker_ip" type="button" title="Cài Đặt Auto Wifi Manager Và Đọc Địa Chỉ IP Khi Mà IP Hoặc Wifi Bị Thay Đổi" onclick="command_php('auto_wifi_manager_and_speaker_ip')">Install Auto Wifi Manager + Đọc IP</button></li>
                          <button class="dropdown-item text-danger" name="logs_auto_wifi" type="button" title="Xem Logs Auto Wifi Manaager" onclick="command_php('logs_auto_wifi')">Logs Auto Wifi Manager</button></li>
                          <button class="dropdown-item text-danger" name="status_auto_wifi" type="button" title="Kiêm tra trạng thái Auto Wifi Manaager" onclick="command_php('status_auto_wifi')">Status Auto Wifi Manager</button></li>
                        </ul>
                      </div>
                    </div>
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false" title="Cấu Hình WebUI Ra Internet">
                         <i class="bi bi-browser-safari"></i> WebUI External</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <li><button class="dropdown-item text-danger" name="enabled_vbot_api_external" type="button" title="Cấu Hình WebUI Ra Internet" onclick="command_php('enabled_vbot_api_external')">Kích Hoạt WebUI Ra Internet</button></li>
                          <li><button class="dropdown-item text-danger" name="disable_vbot_api_external" type="button" title="Cấu Hình WebUI Ra Internet" onclick="command_php('disable_vbot_api_external')">Vô Hiệu WebUI Ra Internet</button></li>
                          <li><button onclick="command_php('apache_restart')" class="dropdown-item text-danger" type="button" title="Restart Apache2">Restart WebUI Apache2</button></li>

                        </ul>
                      </div>
                    </div>

                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-dark dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                         <i class="bi bi-gear"></i> Hệ Thống</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <li><button onclick="command_php('apache_restart')" class="dropdown-item text-danger" type="button" title="Khởi động lại apache2">Restart Apache2</button></li>
                          <li><button onclick="command_php('logs_apache2')" class="dropdown-item text-danger" type="button" title="Xem 500 dòng lỗi Apache2 gần nhất">Logs Apache2</button></li>
                          <li><button onclick="command_php('restart_alsa')" class="dropdown-item text-danger" type="button" title="Khởi động lại Alsa">Restart Alsa-Restore</button></li>
                          <li><button onclick="command_php('reboot_os')" class="dropdown-item text-danger" type="button" title="Khởi động lại hệ thống">Reboot OS</button></li>
                          <li><button onclick="command_php('chmod_vbot')" class="dropdown-item text-danger" type="button" title="Chmod VBot và UI HTML thành 0777">Chmod 0777</button></li>
                          <li><button onclick="command_php('owner_vbot')" class="dropdown-item text-danger" type="button" title="Thay đổi quyền sở hữu các file thành của người dùng SSH">Owner Change</button></li>
                          <li><button onclick="command_php('fix_asound_airplay')" class="dropdown-item text-danger" type="button" title="Khôi Phục lại dữ liệu cấu hình file /etc/asound.conf">Khôi Phục /etc/asound.conf</button></li>
                          <li><button onclick="command_php('reload_services')" class="dropdown-item text-danger" type="button" title="Re-load lại các Services">Re-load Services</button></li>
                          <li><button onclick="command_php('ifconfig_os')" class="dropdown-item text-danger" type="button" title="Kiểm tra thông tin mạng">Thông tin mạng</button></li>
                          <li><button onclick="command_php('lscpu_os')" class="dropdown-item text-danger" type="button" title="Kiểm tra thông CPU">Thông tin CPU</button></li>
                          <li><button onclick="command_php('hostnamectl_os')" class="dropdown-item text-danger" type="button" title="Kiểm tra thông tin hệ điều hành">Thông tin OS</button></li>
                          <li><button onclick="command_php('kiem_tra_bo_nho')" class="dropdown-item text-danger" type="button" title="Kiểm tra thông tin dung lượng lưu trữ">Thông tin dung lượng</button></li>
                          <li><button onclick="command_php('kiem_tra_dung_luong')" class="dropdown-item text-danger" type="button" title="Kiểm tra thông tin bộ nhớ RAM">Thông tin bộ nhớ</button></li>
                          <li><button onclick="command_php('serial_getty_ttyS0_start')" class="dropdown-item text-danger" type="button" title="Bắt đầu một phiên đăng nhập (login shell) qua cổng UART">Start serial-getty@ttyS0.service</button></li>
                          <li><button onclick="command_php('serial_getty_ttyS0_stop')" class="dropdown-item text-danger" type="button" title="Dừng phiên đăng nhập (login shell) qua cổng UART">Stop serial-getty@ttyS0.service</button></li>
                          <li><button onclick="command_php('serial_getty_ttyS0_disable')" class="dropdown-item text-danger" type="button" title="Vô hiệu một phiên đăng nhập (login shell) qua cổng UART (Start UP)">Disable serial-getty@ttyS0.service</button></li>
                          <li><button onclick="command_php('serial_getty_ttyS0_enable')" class="dropdown-item text-danger" type="button" title="Kích hoạt một phiên đăng nhập (login shell) qua cổng UART (Start UP)">Enable serial-getty@ttyS0.service</button></li>
                          <li><button onclick="command_php('list_systemctl_enabled')" class="dropdown-item text-danger" type="button" title="Các dịch vụ đang khởi động cùng hệ thống">Systemctl List Enable</button></li>
                          <li><button onclick="command_php('sudo_alsactl_store')" class="dropdown-item text-danger" type="button" title="Lưu cấu hình âm thanh alsamixer">sudo alsactl store</button></li>
                          <li><button onclick="command_php('Stop_Service_Unnecessary_Processes')" class="dropdown-item text-danger" type="button" title="Tắt các tiến trình service không cần thiết trên hệ thống">Tắt các tiến trình Service không cần thiết</button></li>
                          <li><button onclick="command_php('os_image_created')" class="dropdown-item text-danger" type="button" title="Kiểm tra phiên bản OS IMG">Phiên bản OS IMG</button></li>
                        </ul>
                      </div>
                    </div>
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                         <i class="bi bi-list-check"></i> Thư Viện</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <li><button onclick="command_php('pip_show_all_lib')" class="dropdown-item text-danger" type="button" title="Liệt kê các thư viện đã cài bằng pip">pip show all lib</button></li>
                          <li><button onclick="command_php('pvporcupine_info')" class="dropdown-item text-danger" type="button" title="Kiểm tra thông tin thư viện pvporcupine">Thông tin pvporcupine</button></li>
                          <li><button onclick="command_php('picovoice_info')" class="dropdown-item text-danger" type="button" title="Kiểm tra thông tin thư viện picovoice">Thông tin picovoice</button></li>
                        </ul>
                      </div>
                    </div>
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                         <i class="bi bi-pci-card-sound"></i> ALSA SoundCard
                        </button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <li><button onclick="command_php('alsamixer_soundcard_start')" class="dropdown-item text-danger" type="button" title="alsamixer_soundcard_start">ALSA SoundCard Start</button></li>
                          <li><button onclick="command_php('alsamixer_soundcard_stop')" class="dropdown-item text-danger" type="button" title="alsamixer_soundcard_stop">ALSA SoundCard Stop</button></li>
                          <li><button onclick="command_php('alsamixer_soundcard_disable')" class="dropdown-item text-danger" type="button" title="alsamixer_soundcard_disable">ALSA SoundCard Disable</button></li>
                          <li><button onclick="command_php('alsamixer_soundcard_enable')" class="dropdown-item text-danger" type="button" title="alsamixer_soundcard_enable">ALSA SoundCard Enable</button></li>
                          <li><button onclick="command_php('alsamixer_soundcard_status')" class="dropdown-item text-danger" type="button" title="alsamixer_soundcard_status">ALSA SoundCard Status</button></li>
                          <li><button class="dropdown-item text-danger" name="save_asound_to_alsamixer" type="button" title="save_asound_to_alsamixer" onclick="command_php('save_asound_to_alsamixer')">Save Alsamixer SoundCard</button></li>
                          <li><button class="dropdown-item text-danger" name="alsamixer_asound_to_alsamixer" type="button" title="alsamixer_asound_to_alsamixer" onclick="command_php('alsamixer_asound_to_alsamixer')">Restore WM8960 ALSA SoundCard Driver Default</button></li>
                        </ul>
                      </div>
                    </div>
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                         <i class="bi bi-cloud-fill"></i> Cloudflare Tunnel</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <li><button class="dropdown-item text-danger" name="cloudflared_tunnel_start" type="button" title="Chạy " onclick="command_php('cloudflared_tunnel_start')">Chạy</button></li>
                          <li><button class="dropdown-item text-danger" name="cloudflared_tunnel_stop" type="button" title="Dừng Chạy Tạm thời" onclick="command_php('cloudflared_tunnel_stop')">Dừng</button></li>
                          <li><button class="dropdown-item text-danger" name="cloudflared_tunnel_disable" type="button" title="Dừng Chạy Cloudflare Tunnel Khi pi Khởi Động" onclick="command_php('cloudflared_tunnel_disable')">Vô Hiệu</button></li>
                          <li><button class="dropdown-item text-danger" name="cloudflared_tunnel_enable" type="button" title="Cho Phép Chạy Cloudflare Tunnel Khi pi Khởi Động" onclick="command_php('cloudflared_tunnel_enable')">Kích hoạt</button></li>
                          <li><button class="dropdown-item text-danger" name="cloudflared_tunnel_status" type="button" title="Kiểm Tra Trạng Thái Cloudflare Tunnel" onclick="command_php('cloudflared_tunnel_status')">Kiểm Tra Trạng Thái</button></li>
                          <li><button class="dropdown-item text-danger" name="cloudflared_tunnel_list" type="button" title="Xem Danh Sách Tunnel List" onclick="command_php('cloudflared_tunnel_list')">Xem Danh Sách Tunnel List</button></li>
                          <li><a href="FAQ.php"><button onclick="loading('show')" class="dropdown-item text-danger" type="button" title="Xem Hướng Dẫn">Hướng Dẫn</button></a></li>
                        </ul>
                      </div>
                    </div>
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-warning dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false" title="Cấu Hình Wifi Thông Qua Bluetooth">
                         <i class="bi bi-bluetooth"></i> Set Wifi Via BLE</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <button class="dropdown-item text-danger" name="start_btwifiset" type="button" title="Chạy Services Auto btwifiset" onclick="command_php('start_btwifiset')">Start btwifiset</button></li>
                          <button class="dropdown-item text-danger" name="stop_btwifiset" type="button" title="Dừng Services Auto btwifiset" onclick="command_php('stop_btwifiset')">Stop btwifiset</button></li>
						  <button class="dropdown-item text-danger" name="restart_btwifiset" type="button" title="Khởi động lại Services Auto Wifi Manaager" onclick="command_php('restart_btwifiset')">Restart Auto btwifiset</button>
                          <button class="dropdown-item text-danger" name="enable_btwifiset" type="button" title="Kích Hoạt Services Auto btwifiset" onclick="command_php('enable_btwifiset')">Enable Auto btwifiset</button></li>
                          <button class="dropdown-item text-danger" name="disabled_btwifiset" type="button" title="Vô Hiệu Services Auto btwifiset" onclick="command_php('disabled_btwifiset')">Disabled Auto btwifiset</button></li>
                          <button class="dropdown-item text-danger" name="logs_btwifiset" type="button" title="Xem Logs Auto btwifiset" onclick="command_php('logs_btwifiset')">Logs Auto btwifiset</button></li>
                          <button class="dropdown-item text-danger" name="status_btwifiset" type="button" title="Kiểm tra trạng thái btwifiset" onclick="command_php('status_btwifiset')">Status Auto btwifiset</button></li>
                          <button class="dropdown-item text-danger" name="pass_crypto_btwifiset" type="button" title="Xem Mật Khẩu Mã Hóa Tín Hiệu btwifiset" onclick="command_php('pass_crypto_btwifiset')">Password Crypto btwifiset</button></li>
                          <button class="dropdown-item text-danger" name="update_btwifiset_py" type="button" title="Cập nhật mới file btwifiset.py từ resource" onclick="command_php('update_btwifiset_py')">UPDATE btwifiset.py</button></li>
                        </ul>
                      </div>
                    </div>
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false" title="Cấu Hình AirPlay tương thích với Iphone, Ipad">
                          <i class="bi bi-apple"></i> AirPlay</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                          <button class="dropdown-item text-danger" name="rename_airplay" type="button" onclick="rename_airplayyy()" title="Đổi tên thiết bị AirPlay">Đổi Tên AirPlay</button></li>
                          <button class="dropdown-item text-danger" name="start_airplay" type="button" title="Chạy Services Auto AirPlay" onclick="command_php('start_airplay')">Start AirPlay</button></li>
                          <button class="dropdown-item text-danger" name="stop_airplay" type="button" title="Dừng Services Auto AirPlay" onclick="command_php('stop_airplay')">Stop AirPlay</button></li>
						  <button class="dropdown-item text-danger" name="restart_airplay" type="button" title="Khởi động lại Services AirPlay" onclick="command_php('restart_airplay')">Restart Auto AirPlay</button>
                          <button class="dropdown-item text-danger" name="enable_airplay" type="button" title="Kích Hoạt Services Auto AirPlay" onclick="command_php('enable_airplay')">Enable Auto AirPlay</button></li>
                          <button class="dropdown-item text-danger" name="disabled_airplay" type="button" title="Vô Hiệu Services Auto AirPlay" onclick="command_php('disabled_airplay')">Disabled Auto AirPlay</button></li>
                          <button class="dropdown-item text-danger" name="logs_airplay" type="button" title="Xem Logs Auto AirPlay" onclick="command_php('logs_airplay')">Logs Auto AirPlay</button></li>
                          <button class="dropdown-item text-danger" name="status_airplay" type="button" title="Kiểm tra trạng thái AirPlay" onclick="command_php('status_airplay')">Status Auto AirPlay</button></li>
                          <button class="dropdown-item text-danger" name="version_airplay" type="button" title="Kiểm tra phiên bản AirPlay" onclick="command_php('version_airplay')">Phiên Bản AirPlay</button></li>
                          <button class="dropdown-item text-danger" name="fix_airplay_services" type="button" title="Tự động sửa lỗi AirPlay khi lỗi bị treo không tự động chạy lại" onclick="command_php('fix_airplay_services')">Fix shairport-sync.service AirPlay</button></li>
                          <button onclick="command_php('fix_asound_airplay')" class="dropdown-item text-danger" type="button" title="Tự động sửa lỗi âm thanh ở /etc/asound.conf khi sử dụng mạch: WM8960 hoặc I2S">Fix /etc/asound.conf AirPlay</button></li>
                        </ul>
                      </div>
                    </div>
                    <div class="btn-group">
                      <div class="dropdown">
                        <button class="btn btn-warning dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false" title="Bluetooth Audio">
                         <i class="bi bi-bluetooth"></i> Bluetooth Audio</button>
                        <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">

						  <button class="dropdown-item text-danger" name="start_vbot_bluetooth_agent" type="button" title="Khởi động lại vbot-bluetooth-agent.service" onclick="command_php('start_vbot_bluetooth_agent')">vbot-bluetooth-agent Start</button>
						  <button class="dropdown-item text-danger" name="stop_vbot_bluetooth_agent" type="button" title="Khởi động lại vbot-bluetooth-agent.service" onclick="command_php('stop_vbot_bluetooth_agent')">vbot-bluetooth-agent Stop</button>
						  <button class="dropdown-item text-danger" name="restart_vbot_bluetooth_agent" type="button" title="Khởi động lại vbot-bluetooth-agent.service" onclick="command_php('restart_vbot_bluetooth_agent')">vbot-bluetooth-agent Restart</button>
                          <button class="dropdown-item text-danger" name="enable_vbot_bluetooth_agent" type="button" title="Kích Hoạt Services Auto vbot-bluetooth-agent.service" onclick="command_php('enable_vbot_bluetooth_agent')">vbot-bluetooth-agent Enable Auto</button></li>
                          <button class="dropdown-item text-danger" name="disabled_vbot_bluetooth_agent" type="button" title="Vô Hiệu Services Auto vbot-bluetooth-agent.service" onclick="command_php('disabled_vbot_bluetooth_agent')">vbot-bluetooth-agent Disabled Auto</button></li>
                          <button class="dropdown-item text-danger" name="status_vbot_bluetooth_agent" type="button" title="Kiểm tra trạng thái vbot-bluetooth-agent.service" onclick="command_php('status_vbot_bluetooth_agent')">vbot-bluetooth-agent Status Auto</button></li>
						  <button class="dropdown-item text-danger" name="logs_vbot_bluetooth_agent" type="button" title="Xem Logs Auto vbot-bluetooth-agent.service" onclick="command_php('logs_vbot_bluetooth_agent')">vbot-bluetooth-agent Logs Auto</button></li>

                          <button class="dropdown-item text-danger" name="start_bluealsa" type="button" title="Chạy bluealsa.service" onclick="command_php('start_bluealsa')">bluealsa Start</button></li>
                          <button class="dropdown-item text-danger" name="stop_bluealsa" type="button" title="Dừng bluealsa.service" onclick="command_php('stop_bluealsa')">bluealsa Stop</button></li>
						  <button class="dropdown-item text-danger" name="restart_bluealsa" type="button" title="Khởi động lại bluealsa.service" onclick="command_php('restart_bluealsa')">bluealsa Restart</button>
                          <button class="dropdown-item text-danger" name="enable_bluealsa" type="button" title="Kích Hoạt Services Auto bluealsa.service" onclick="command_php('enable_bluealsa')">bluealsa Enable Auto</button></li>
                          <button class="dropdown-item text-danger" name="disabled_bluealsa" type="button" title="Vô Hiệu Services Auto bluealsa.service" onclick="command_php('disabled_bluealsa')">bluealsa Disabled Auto</button></li>
                          <button class="dropdown-item text-danger" name="status_bluealsa" type="button" title="Kiểm tra trạng thái bluealsa.service" onclick="command_php('status_bluealsa')">bluealsa Status Auto</button></li>
						  <button class="dropdown-item text-danger" name="logs_bluealsa" type="button" title="Xem Logs Auto bluealsa.service" onclick="command_php('logs_bluealsa')">bluealsa Logs Auto</button></li>



						  <button class="dropdown-item text-danger" name="install_bluetooth_agent_py" type="button" title="Cài đặt bluetooth_agent.py" onclick="command_php('install_bluetooth_agent_py')">install bluetooth_agent.py</button></li>
						  <button class="dropdown-item text-danger" name="install_bthelper" type="button" title="Cài đặt bthelper" onclick="command_php('install_bthelper')">install bthelper</button></li>
						  <button class="dropdown-item text-danger" name="install_bluealsa" type="button" title="Cài đặt bluealsa.service" onclick="command_php('install_bluealsa')">install bluealsa.service</button></li>
						  <button class="dropdown-item text-danger" name="install_bluetooth_agent_service" type="button" title="Cài đặt vbot-bluetooth-agent.service" onclick="command_php('install_bluetooth_agent_service')">install vbot-bluetooth-agent.service</button></li>
						  <button class="dropdown-item text-danger" name="install_bluetooth_config_main" type="button" title="Cài đặt cấu hình config cho bluetooth" onclick="command_php('install_bluetooth_config_main')">install config bluetooth main.conf</button></li>
                        </ul>
                      </div>
                    </div>
              </form>
            </div>
          </div>
          <hr />
          <form method="POST" action="">
            <div class="row g-3 d-flex justify-content-center">
              <div class="col-auto d-flex flex-wrap justify-content-center gap-2">
                <div class="input-group">
                  <span class="input-group-text text-success">Nâng/Hạ Cấp Picovoice</span>
                  <select class="btn btn-success dropdown-toggle" data-toggle="dropdown" id="versions_picovoice_install" name="versions_picovoice_install">
                    <option value="" selected>Đang Lấy Dữ Liệu...</option>
                  </select>
                </div>
              </div>
              <div class="col-auto d-flex flex-wrap justify-content-center gap-2">
                <div class="input-group-append">
                  <button class="btn btn-danger" name="install_picovoice" title="Cài đặt Picovoice" type="button" onclick="command_php('install_picovoice')">Cài Đặt Picovoice</button>
                  <button type="button" name='check_version_picovoice_porcupine' class='btn btn-primary' title='Kiểm tra phiên bản Picovoice và Porcupine' onclick="command_php('check_version_picovoice_porcupine')">Kiểm tra phiên bản</button>
                </div>
              </div>
            </div>
            <br />
            <div class="row g-3 d-flex justify-content-center">
              <div class="col-auto d-flex flex-wrap justify-content-center gap-2">
                <div class="input-group">
                  <span class="input-group-text text-success">Thư Viện Porcupine (.pv)</span>
                  <select class="btn btn-success dropdown-toggle" data-toggle="dropdown" id="versions_porcupine_install" name="versions_porcupine_install">
                    <option value="" selected>Đang Lấy Dữ Liệu...</option>
                  </select>
                </div>
              </div>
              <div class="col-auto d-flex flex-wrap justify-content-center gap-2">
                <div class="input-group-append">
                  <button class="btn btn-danger" name="install_porcupine" title="Cài đặt Porcupine" type="button" onclick="command_php('install_porcupine')">Cài Đặt Porcupine</button>
                </div>
              </div>
            </div>
          </form>
          <hr />
		  
<form method="POST" action="">
<div id="rename_airplay-box" style="display: none;">
<div class="input-group mb-3">
<span class="input-group-text border-success" id="basic-addon1">Tên AirPlay:</span>
<input type="text" class="form-control border-success" id="airplay_name_change" name="airplay_name_change" placeholder="Nhập Tên AirPlay cần thay đổi" aria-label="Username" aria-describedby="basic-addon1">
<button type="button" name="submit_rename_airplay" class="btn btn-success border-success" onclick="command_php('submit_rename_airplay')"><i class="bi bi-save"></i> Lưu</button>
</div>
</div>
</form>
          <form method="POST" action="">
            <div class="row g-3 d-flex justify-content-center">

              <div class="col-auto d-flex flex-wrap justify-content-center gap-2">
                <div class="btn-group">
                  <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false" title="Thiết Lập Múi Giờ, Thời Gian Cho Hệ Thống">
                      Thời Gian, Múi Giờ
                    </button>
                    <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                      <li><button class="dropdown-item text-danger" name="list_time_zones" type="button" id="list_time_zones" title="Hiển Thị Danh Sách Múi Giờ" onclick="command_php('list_time_zones')">Danh Sách Múi Giờ</button></li>
                      <li><button class="dropdown-item text-danger" name="check_time_zones" type="button" id="check_time_zones" title="Kiểm Tra Múi Giờ Hiện Tại Trên Hệ Thống" onclick="command_php('check_time_zones')">Kiểm Tra Múi Giờ Hệ Thống</button></li>
                      <li><button class="dropdown-item text-danger" name="fix_time_zones" type="button" id="fix_time_zones" title="Sửa Lỗi Đồng Bộ, Sai Thời Gian Hệ Thống" onclick="command_php('fix_time_zones')">Sửa Lỗi Đồng Bộ, Sai Thời Gian</button></li>
                    </ul>
                  </div>
                </div>

              </div>
              <?php
              $currentTimezone = date_default_timezone_get();
              $timezoneOptions = timezone_identifiers_list();
              ?>
              <div class="col-auto">
                <div class="input-group">
                  <select class="form-select border-primary" name="show_lits_timezone" id="show_lits_timezone">
                    <?php foreach ($timezoneOptions as $timezoneOption) { ?>
                      <option value="<?php echo htmlspecialchars($timezoneOption, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $timezoneOption === $currentTimezone ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($timezoneOption, ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php } ?>
                  </select>
                  <button class="btn btn-success border-primary" name="set_time_zones" id="set_time_zones"
                    type="button" onclick="command_php('set_time_zones')">Thiết Lập Múi Giờ</button>
                </div>
              </div>
            </div>
          </form>


          <hr />
          <form method="POST" action="">
            <div class="input-group mb-3">
              <span class="input-group-text border-success" id="basic-addon1"><i class="bi bi-terminal-fill" onclick="show_message('Nhập các lệnh Linux cần thực thi, hệ thống sẽ sử dụng thông tin ssh của bạn để thực hiện lệnh như 1 user bình thường')"></i></span>
              <input type="text" class="form-control border-success" name="commandnd" placeholder="Nhập dòng lệnh cần thực hiện">
              <button class="btn btn-success border-success" onclick="loading('show')" name="commandd" type="submit">Command</button>
            </div>
            <div class="form-group">
              <textarea class="form-control border-success text-info bg-dark" id="textarea_log_command" rows="14"><?php echo $output; ?></textarea>
            </div>
          </form>
        </div>
      </div>
      </div>
      </div>
    </section>
  </main>
  <!-- End #main -->
  <!-- ======= Footer ======= -->
  <?php
  include 'html_footer.php';
  ?>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <?php
  include 'html_js.php';
  ?>
  <script>
    function get_picovoice_version() {
      const xhr = new XMLHttpRequest();
      xhr.open('GET', 'includes/php_ajax/Check_Connection.php?Picovoice_Version');
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
          const picovoiceDropdown = document.getElementById('versions_picovoice_install');
          const porcupineDropdown = document.getElementById('versions_porcupine_install');
          if (xhr.status === 200) {
            const xmlContent = xhr.responseText;
            // Tìm vị trí của các thẻ <item>
            let startPos = xmlContent.indexOf('<item>');
            let endPos = xmlContent.indexOf('</item>');
            // Mảng lưu các phiên bản
            const versions = [];
            // Lặp qua từng mục và thêm thông tin vào mảng
            while (startPos !== -1 && endPos !== -1) {
              const itemXml = xmlContent.substring(startPos, endPos + '</item>'.length);
              // Trích xuất tiêu đề (<title>)
              const titleMatch = itemXml.match(/<title>(.*?)<\/title>/);
              if (titleMatch && titleMatch[1]) {
                // Thêm phiên bản vào mảng
                versions.push(titleMatch[1]);
              }
              // Chuyển sang mục tiếp theo
              startPos = xmlContent.indexOf('<item>', endPos);
              endPos = xmlContent.indexOf('</item>', startPos);
            }
            // Xóa tất cả các option cũ trong dropdowns
            picovoiceDropdown.innerHTML = '';
            porcupineDropdown.innerHTML = '';
            // Thêm tùy chọn mặc định vào dropdown Picovoice
            const defaultPicovoiceOption = document.createElement('option');
            defaultPicovoiceOption.value = '';
            defaultPicovoiceOption.textContent = 'Chọn phiên bản';
            picovoiceDropdown.appendChild(defaultPicovoiceOption);
            // Tạo mảng lưu trữ các 3 ký tự đầu tiên của phiên bản Porcupine
            const porcupineVersions = new Set();
            // Thêm tùy chọn mặc định vào dropdown Porcupine (Chọn phiên bản Porcupine)
            const defaultPorcupineOption = document.createElement('option');
            defaultPorcupineOption.value = '';
            defaultPorcupineOption.textContent = 'Chọn phiên bản';
            porcupineDropdown.appendChild(defaultPorcupineOption);
            // Thêm các phiên bản vào dropdown Picovoice
            if (versions.length > 0) {
              versions.forEach(version => {
                const picovoiceOption = document.createElement('option');
                picovoiceOption.value = version;
                picovoiceOption.textContent = `Picovoice: ${version}`;
                picovoiceDropdown.appendChild(picovoiceOption);
                // Lấy 3 ký tự đầu tiên của phiên bản
                const versionPrefix = version.substring(0, 3);
                // Nếu 3 ký tự đầu tiên chưa được thêm vào mảng Set, thì thêm vào dropdown Porcupine
                if (!porcupineVersions.has(versionPrefix)) {
                  porcupineVersions.add(versionPrefix);
                  const porcupineOption = document.createElement('option');
                  porcupineOption.value = versionPrefix;
                  porcupineOption.textContent = `Porcupine: ${versionPrefix}`;
                  porcupineDropdown.appendChild(porcupineOption);
                }
              });
            } else {
              // Nếu không có phiên bản nào, hiển thị option mặc định
              const option = document.createElement('option');
              option.value = '';
              option.textContent = 'Phiên bản: -----';
              picovoiceDropdown.appendChild(option);
            }
          } else {
            showMessagePHP('Lỗi HTTP:' + xhr.status);
            // Hiển thị lỗi trong dropdown
            const errorOption = document.createElement('option');
            errorOption.value = '';
            errorOption.textContent = 'Không thể tải dữ liệu.';
            picovoiceDropdown.appendChild(errorOption);
            porcupineDropdown.appendChild(errorOption);
          }
        }
      };
      xhr.send();
    }


function rename_airplayyy() {
    const box = document.getElementById("rename_airplay-box");

    if (box.style.display === "none" || box.style.display === "") {
        box.style.display = "block";
    } else {
        box.style.display = "none";
    }
}


    //lấy dữ liệu phiên bản picovoice khi trang được tải toàn bộ
    window.onload = function() {
      get_picovoice_version();
    };
  </script>
</body>

</html>
