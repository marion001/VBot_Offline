<?php
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
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
<?php
  // Khởi tạo biến để lưu output
  $output = '';
  
  $SSH_CONNECT_ERROR = "<center><h1><font color='red'>Không thể kết nối tới máy chủ SSH, Hãy Kiểm Tra Lại</font><br/><a href='Command.php'>Quay Lại</a></h1></center>";
  $SSH2_AUTH_ERROR = "<center><h1><font color='red'>Xác thực SSH không thành công, Hãy kiểm tra lại thông tin đăng nhập SSH</font> <br/><a href='Command.php'>Quay Lại</a></h1></center>";
  function picovoice_version($noi_dung_tep, $ten_lop, $ten_phuong_thuc) {
      try {
          $dong = explode("\n", $noi_dung_tep);
          $trong_lop = $noi_dung_lop = $trong_phuong_thuc = $noi_dung_phuong_thuc = $gia_tri_return = false;
          foreach ($dong as $line) {
              $noi_dung_lop .= $line;
              if (strpos($line, "class {$ten_lop}(") !== false) {
                  $trong_lop = true;
              }
              if ($trong_lop && strpos($line, "def {$ten_phuong_thuc}(") !== false) {
                  $trong_phuong_thuc = true;
              }
              if ($trong_phuong_thuc) {
                  $noi_dung_phuong_thuc .= $line;
                  if (strpos($line, 'return ') !== false) {
                      $gia_tri_return = trim(trim(str_replace("'", "", explode('return ', $line)[1])));
                      break;
                  }
              }
          }
          return $gia_tri_return;
      } catch (Exception $e) {
          return "Lỗi xử lý tệp.";
      }
  }
  function porcupine_version($file_path, $skip_count = 9) {
      try {
          $file = fopen($file_path, 'r');
          // Đọc và bỏ qua 9 ký tự đầu
          fread($file, $skip_count);
          // Đọc 15 ký tự tiếp theo
          $next_14_characters = fread($file, 5);
          fclose($file);
          return $next_14_characters;
      } catch (Exception $e) {
          return "File không tồn tại";
      }
  }
  
  //Command
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commandd'])) {
  	$commandnd = @$_POST['commandnd'];
  	if (empty($commandnd)) {
              $output .= "$GET_current_USER@$HostName:$ ~> Hãy Nhập Lệnh Cần Thực Thi";
          }
  else {
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $commandnd);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $commandnd\n";
  $output .=  stream_get_contents($stream_out);
  }
  }
  
  if (isset($_POST['save_asound_to_alsamixer'])) {
      $CMD = "sudo alsactl store";
      $CMD1 = "sudo cp /var/lib/alsa/asound.state /etc/wm8960-soundcard/wm8960_asound.state";    
      $connection = ssh2_connect($ssh_host, $ssh_port);
      if (!$connection) { die($SSH_CONNECT_ERROR); }
      if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) { die($SSH2_AUTH_ERROR); }
      $stream = ssh2_exec($connection, $CMD);
      stream_set_blocking($stream, true);
      $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
      sleep(1);
      $stream1 = ssh2_exec($connection, $CMD1);
      stream_set_blocking($stream1, true);
      $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO);
      $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
      $output .= stream_get_contents($stream_out);
      $output .= "\n$GET_current_USER@$HostName:~ $ $CMD1\n";
      $output .= stream_get_contents($stream_out1);
  }
  
  if (isset($_POST['alsamixer_asound_to_alsamixer'])) {
  $CMD = 'sudo cp '.$VBot_Offline.'resource/wm8960_asound_default.state /etc/wm8960-soundcard/wm8960_asound.state';
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['sudo_alsactl_store'])) {
  $CMD = "sudo alsactl store";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }

  if (isset($_POST['logs_apache2'])) {
  $CMD = "cat /var/log/apache2/error.log";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }

  if (isset($_POST['list_systemctl_enabled'])) {
  $CMD = "sudo systemctl list-unit-files --state=enabled";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['alsamixer_soundcard_stop'])) {
  $CMD = "sudo systemctl stop wm8960-soundcard.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['alsamixer_soundcard_start'])) {
  $CMD = "sudo systemctl start wm8960-soundcard.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['alsamixer_soundcard_disable'])) {
  $CMD = "sudo systemctl disable wm8960-soundcard.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['alsamixer_soundcard_status'])) {
  $CMD = "sudo systemctl status wm8960-soundcard.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['alsamixer_soundcard_enable'])) {
  $CMD = "sudo systemctl enable /usr/src/wm8960-soundcard-1.0/wm8960-soundcard.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['auto_start'])) {
  $CMD = "systemctl --user start VBot_Offline.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['auto_stop'])) {
  $CMD = "systemctl --user stop VBot_Offline.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['auto_enable'])) {
  $CMD = "systemctl --user enable VBot_Offline.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['auto_disable'])) {
  $CMD = "systemctl --user disable VBot_Offline.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['auto_status'])) {
  $CMD = "systemctl --user status VBot_Offline.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['auto_restart'])) {
  $CMD = "systemctl --user restart VBot_Offline.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }


if (isset($_POST['enabled_vbot_api_external'])) {
    $proxyConfig = <<<EOT
    ProxyPass /vbot_api_external/ http://localhost:{$Port_API}/
    ProxyPassReverse /vbot_api_external/ http://localhost:{$Port_API}/
EOT;

    // Thực hiện SSH kết nối
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {die($SSH_CONNECT_ERROR);}
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
    // Đọc file cấu hình
    $cmd_read = "cat /etc/apache2/sites-available/000-default.conf";
    $stream = ssh2_exec($connection, $cmd_read);
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $conf_content = stream_get_contents($stream_out);
    fclose($stream);
    // Ghi log lệnh đọc file
    $output = "$GET_current_USER@$HostName:~ $ $cmd_read\n";
    // Tìm khối <VirtualHost *:80>
    if (!preg_match('/<VirtualHost\s*\*:80\s*>\s*(.*?)\s*<\/VirtualHost>/s', $conf_content, $matches)) {
        die("Không tìm thấy khối <VirtualHost *:80> trong file cấu hình Apache2");
    }
    $virtual_host_content = $matches[1]; // Nội dung bên trong <VirtualHost *:80>
    $virtual_host_full = $matches[0]; // Toàn bộ khối <VirtualHost *:80> ... </VirtualHost>
    // Kiểm tra sự tồn tại của ProxyPass và ProxyPassReverse (bao gồm cả dòng bị bình luận)
    $has_proxy_pass = preg_match('/^\s*(#)?\s*ProxyPass \/vbot_api_external\//m', $virtual_host_content, $proxy_pass_match);
    $has_proxy_pass_reverse = preg_match('/^\s*(#)?\s*ProxyPassReverse \/vbot_api_external\//m', $virtual_host_content, $proxy_pass_reverse_match);
    // Xử lý nội dung trong khối VirtualHost
    if ($has_proxy_pass || $has_proxy_pass_reverse) {
        // Thay thế hoặc uncomment các dòng hiện có
        $new_vhost_content = $virtual_host_content;
        if ($has_proxy_pass) {
            // Nếu dòng ProxyPass tồn tại (có hoặc không có #), thay thế hoặc uncomment
            $new_vhost_content = preg_replace(
                '/^\s*#?\s*ProxyPass \/vbot_api_external\/.*?$/m',
                '    ProxyPass /vbot_api_external/ http://localhost:'.$Port_API.'/',
                $new_vhost_content
            );
        } else {
            // Nếu không có ProxyPass, thêm mới vào cuối khối
            $new_vhost_content = rtrim($new_vhost_content) . "\n    ProxyPass /vbot_api_external/ http://localhost:".$Port_API."/";
        }
        if ($has_proxy_pass_reverse) {
            // Nếu dòng ProxyPassReverse tồn tại (có hoặc không có #), thay thế hoặc uncomment
            $new_vhost_content = preg_replace(
                '/^\s*#?\s*ProxyPassReverse \/vbot_api_external\/.*?$/m',
                '    ProxyPassReverse /vbot_api_external/ http://localhost:'.$Port_API.'/',
                $new_vhost_content
            );
        } else {
            // Nếu không có ProxyPassReverse, thêm mới vào cuối khối
            $new_vhost_content = rtrim($new_vhost_content) . "\n    ProxyPassReverse /vbot_api_external/ http://localhost:".$Port_API."/";
        }
    } else {
        // Nếu không có cả hai, thêm cả hai vào cuối khối
        $new_vhost_content = rtrim($virtual_host_content) . "\n" . $proxyConfig;
    }
    // Cập nhật toàn bộ nội dung file với khối VirtualHost đã chỉnh sửa
    $new_conf = preg_replace(
        '/<VirtualHost\s*\*:80\s*>\s*.*?\s*<\/VirtualHost>/s',
        "<VirtualHost *:80>\n$new_vhost_content\n</VirtualHost>",
        $conf_content
    );
    // Tạo file tạm trên server
    $remote_temp_file = '/tmp/apache_conf_temp.conf';
    $cmd_touch = "touch $remote_temp_file";
    $stream_touch = ssh2_exec($connection, $cmd_touch);
    stream_set_blocking($stream_touch, true);
    $stream_touch_out = ssh2_fetch_stream($stream_touch, SSH2_STREAM_STDIO);
    $result_touch = stream_get_contents($stream_touch_out);
    fclose($stream_touch);
    $output .= "$GET_current_USER@$HostName:~ $ $cmd_touch\n";
    // Ghi nội dung mới vào file tạm
    $cmd_write = "echo " . escapeshellarg($new_conf) . " > $remote_temp_file";
    $stream_write = ssh2_exec($connection, $cmd_write);
    stream_set_blocking($stream_write, true);
    $stream_write_out = ssh2_fetch_stream($stream_write, SSH2_STREAM_STDIO);
    $result_write = stream_get_contents($stream_write_out);
    fclose($stream_write);
    // Sao chép file tạm vào vị trí cấu hình
    $cmd_replace = "sudo cp $remote_temp_file /etc/apache2/sites-available/000-default.conf";
    $stream_replace = ssh2_exec($connection, $cmd_replace);
    stream_set_blocking($stream_replace, true);
    $stream_replace_out = ssh2_fetch_stream($stream_replace, SSH2_STREAM_STDIO);
    $result_replace = stream_get_contents($stream_replace_out);
    fclose($stream_replace);
    // Kích hoạt Modules proxy và proxy_http
    $cmd_proxy = "sudo a2enmod proxy";
    $cmd_proxy_http = "sudo a2enmod proxy_http";
    $stream_proxy = ssh2_exec($connection, $cmd_proxy);
    $stream_proxy_http = ssh2_exec($connection, $cmd_proxy_http);
    stream_set_blocking($stream_proxy, true);
    stream_set_blocking($stream_proxy_http, true);
    $stream_proxy_out = ssh2_fetch_stream($stream_proxy, SSH2_STREAM_STDIO);
    $stream_proxy_http_out = ssh2_fetch_stream($stream_proxy_http, SSH2_STREAM_STDIO);
    $result_proxy = stream_get_contents($stream_proxy_out);
    $result_proxy_http = stream_get_contents($stream_proxy_http_out);
    fclose($stream_proxy);
    fclose($stream_proxy_http);
    // Ghi log các lệnh
    $output .= "$GET_current_USER@$HostName:~ $ $cmd_replace\n";
    $output .= "$GET_current_USER@$HostName:~ $ $cmd_proxy\n";
    $output .= $result_proxy . "\n";
    $output .= "$GET_current_USER@$HostName:~ $ $cmd_proxy_http\n";
    $output .= $result_proxy_http . "\n";
	$output .= "Đã thiết lập cấu hình WebUI ra Internet thành công, Vui lòng Restart lại Apache2 hoặc Reboot lại hệ thống để áp dụng";
}

if (isset($_POST['disable_vbot_api_external'])) {
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if (!$connection) {die($SSH_CONNECT_ERROR);}
    if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
    // Đọc file cấu hình
    $cmd_read = "cat /etc/apache2/sites-available/000-default.conf";
    $stream = ssh2_exec($connection, $cmd_read);
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $conf_content = stream_get_contents($stream_out);
    fclose($stream);
    // Ghi log lệnh đọc file
    $output = "pi@VBot-Assistant:~ $ $cmd_read\n";
    // Tìm khối <VirtualHost *:80>
    if (!preg_match('/<VirtualHost\s*\*:80\s*>\s*(.*?)\s*<\/VirtualHost>/s', $conf_content, $matches)) {
        die("Không tìm thấy khối <VirtualHost *:80> trong file cấu hình Apache2");
    }
	// Nội dung bên trong <VirtualHost *:80>
    $virtual_host_content = $matches[1];
    // Xóa tất cả các dòng ProxyPass và ProxyPassReverse (có hoặc không có #)
    $new_vhost_content = preg_replace('/^\s*#?\s*ProxyPass\s+\/vbot_api_external\/.*$/m', '', $virtual_host_content);
    $new_vhost_content = preg_replace('/^\s*#?\s*ProxyPassReverse\s+\/vbot_api_external\/.*$/m', '',$new_vhost_content);
    // Cập nhật toàn bộ nội dung file với khối VirtualHost đã chỉnh sửa
    $new_conf = preg_replace('/<VirtualHost\s*\*:80\s*>\s*.*?\s*<\/VirtualHost>/s', "<VirtualHost *:80>\n$new_vhost_content\n</VirtualHost>",$conf_content);
    // Tạo file tạm trên server
    $remote_temp_file = '/tmp/apache_conf_temp.conf';
    $cmd_touch = "touch $remote_temp_file";
    $stream_touch = ssh2_exec($connection, $cmd_touch);
    stream_set_blocking($stream_touch, true);
    $stream_touch_out = ssh2_fetch_stream($stream_touch, SSH2_STREAM_STDIO);
    $result_touch = stream_get_contents($stream_touch_out);
    fclose($stream_touch);
    // Ghi log lệnh touch
    $output .= "pi@VBot-Assistant:~ $ $cmd_touch\n";
    // Ghi nội dung mới vào file tạm
    $cmd_write = "echo " . escapeshellarg($new_conf) . " > $remote_temp_file";
    $stream_write = ssh2_exec($connection, $cmd_write);
    stream_set_blocking($stream_write, true);
    $stream_write_out = ssh2_fetch_stream($stream_write, SSH2_STREAM_STDIO);
    $result_write = stream_get_contents($stream_write_out);
    fclose($stream_write);
    // Sao chép file tạm vào vị trí cấu hình
    $cmd_replace = "sudo cp $remote_temp_file /etc/apache2/sites-available/000-default.conf";
    $stream_replace = ssh2_exec($connection, $cmd_replace);
    stream_set_blocking($stream_replace, true);
    $stream_replace_out = ssh2_fetch_stream($stream_replace, SSH2_STREAM_STDIO);
    $result_replace = stream_get_contents($stream_replace_out);
    fclose($stream_replace);
    // Ghi log các lệnh
    $output .= "pi@VBot-Assistant:~ $ $cmd_replace\n";
    $output .= $result_replace . "\n";
    $output .= "Đã vô hiệu cấu hình WebUI ra Internet thành công, Vui lòng Restart lại Apache2 hoặc Reboot lại hệ thống để áp dụng";
}

  if (isset($_POST['auto_wifi_manager_only'])) {
  $file_auto_wifi_manager_only = $VBot_Offline.'resource/wifi_manager/start-wifi-connect_wifi_only.sh';
  $file_auto_service = $VBot_Offline.'resource/wifi_manager/wifi-connect.service';
  $CMD = "cp $file_auto_wifi_manager_only /home/pi/start-wifi-connect.sh";
  $CMD3 = "sudo cp $file_auto_service /etc/systemd/system/wifi-connect.service";
  $CMD2 = "dos2unix /home/pi/start-wifi-connect.sh";
  $CMD4 = "sudo systemctl daemon-reload";
  $CMD5 = "sudo systemctl enable wifi-connect.service";
  $CMD1 = "sudo systemctl restart wifi-connect.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  $stream3 = ssh2_exec($connection, $CMD3);
  $stream2 = ssh2_exec($connection, $CMD2);
  $stream4 = ssh2_exec($connection, $CMD4);
  $stream5 = ssh2_exec($connection, $CMD5);
  $stream1 = ssh2_exec($connection, $CMD1);
  stream_set_blocking($stream, true);
  stream_set_blocking($stream3, true);
  stream_set_blocking($stream2, true);
  stream_set_blocking($stream4, true);
  stream_set_blocking($stream5, true);
  stream_set_blocking($stream1, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $stream_out3 = ssh2_fetch_stream($stream3, SSH2_STREAM_STDIO);
  $stream_out2 = ssh2_fetch_stream($stream2, SSH2_STREAM_STDIO);
  $stream_out4 = ssh2_fetch_stream($stream4, SSH2_STREAM_STDIO);
  $stream_out5 = ssh2_fetch_stream($stream5, SSH2_STREAM_STDIO);
  $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $> Chỉ Cài Đặt Auto Wifi Manager Không Đọc Địa Chỉ IP\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD3\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD2\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD4\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD5\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD1\n";
  $output .=  stream_get_contents($stream_out);
  $output .=  stream_get_contents($stream_out3);
  $output .=  stream_get_contents($stream_out2);
  $output .=  stream_get_contents($stream_out4);
  $output .=  stream_get_contents($stream_out5);
  $output .=  stream_get_contents($stream_out1);
  }
  
  if (isset($_POST['auto_wifi_manager_and_speaker_ip'])) {
  $file_auto_wifi_manager_only = $VBot_Offline.'resource/wifi_manager/start-wifi-connect.sh';
  $file_auto_service = $VBot_Offline.'resource/wifi_manager/wifi-connect.service';
  $file_python_ip = $VBot_Offline.'resource/wifi_manager/_VBot_IP.py';
  $CMD = "cp $file_auto_wifi_manager_only /home/pi/start-wifi-connect.sh";
  $CMD3 = "sudo cp $file_auto_service /etc/systemd/system/wifi-connect.service";
  $CMD5 = "sudo cp $file_python_ip /home/pi/_VBot_IP.py";
  $CMD2 = "dos2unix /home/pi/start-wifi-connect.sh";
  $CMD4 = "sudo systemctl daemon-reload";
  $CMD6 = "sudo systemctl enable wifi-connect.service";
  $CMD1 = "sudo systemctl restart wifi-connect.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  $stream3 = ssh2_exec($connection, $CMD3);
  $stream5 = ssh2_exec($connection, $CMD5);
  $stream2 = ssh2_exec($connection, $CMD2);
  $stream4 = ssh2_exec($connection, $CMD4);
  $stream6 = ssh2_exec($connection, $CMD6);
  $stream1 = ssh2_exec($connection, $CMD1);
  stream_set_blocking($stream, true);
  stream_set_blocking($stream3, true);
  stream_set_blocking($stream5, true);
  stream_set_blocking($stream2, true);
  stream_set_blocking($stream4, true);
  stream_set_blocking($stream6, true);
  stream_set_blocking($stream1, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $stream_out3 = ssh2_fetch_stream($stream3, SSH2_STREAM_STDIO);
  $stream_out5 = ssh2_fetch_stream($stream5, SSH2_STREAM_STDIO);
  $stream_out2 = ssh2_fetch_stream($stream2, SSH2_STREAM_STDIO);
  $stream_out4 = ssh2_fetch_stream($stream4, SSH2_STREAM_STDIO);
  $stream_out6 = ssh2_fetch_stream($stream6, SSH2_STREAM_STDIO);
  $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $> Cài Đặt Auto Wifi Manager Và Tự Động Đọc Địa Chỉ IP\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD3\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD5\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD2\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD4\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD6\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD1\n";
  $output .=  stream_get_contents($stream_out);
  $output .=  stream_get_contents($stream_out3);
  $output .=  stream_get_contents($stream_out5);
  $output .=  stream_get_contents($stream_out2);
  $output .=  stream_get_contents($stream_out4);
  $output .=  stream_get_contents($stream_out6);
  $output .=  stream_get_contents($stream_out1);
  }
  
  if (isset($_POST['config_auto'])) {
  // Đường dẫn đến file service
  $serviceFilePath = "{$VBot_Offline}resource/VBot_Offline.service";
  
  // Nội dung của file service với biến
  $serviceContent = <<<EOD
  [Unit]
  Description=VBot_Offline
  
  [Service]
  # Khởi chạy ứng dụng Python VBot_Offline
  ExecStart=/usr/bin/python3.9 {$VBot_Offline}Start.py
  WorkingDirectory=$VBot_Offline
  
  # Ghi log ra các file log sau khi ứng dụng khởi chạy
  #StandardOutput=append:{$VBot_Offline}resource/log/service_log.log
  #StandardError=append:{$VBot_Offline}resource/log/service_error.log
  
  # Tự động khởi động lại service nếu bị lỗi
  Restart=always
  
  [Install]
  WantedBy=default.target
  EOD;
  
  // Tạo hoặc ghi đè file service
  file_put_contents($serviceFilePath, $serviceContent);
  $CMD1 = "cp {$VBot_Offline}resource/VBot_Offline.service /home/$ssh_user/.config/systemd/user/VBot_Offline.service";
  $CMD2 = "sudo chmod 0777 {$VBot_Offline}resource/VBot_Offline.service";
  $CMD3 = "ln -s /home/$ssh_user/.config/systemd/user/VBot_Offline.service /home/$ssh_user/.config/systemd/user/default.target.wants/VBot_Offline.service";
  $CMD4 = "sudo systemctl daemon-reload";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream1 = ssh2_exec($connection, $CMD1);
  $stream2 = ssh2_exec($connection, $CMD2);
  $stream3 = ssh2_exec($connection, $CMD3);
  $stream4 = ssh2_exec($connection, $CMD4);
  stream_set_blocking($stream1, true); 
  stream_set_blocking($stream2, true); 
  stream_set_blocking($stream3, true); 
  stream_set_blocking($stream4, true); 
  $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO); 
  $stream_out2 = ssh2_fetch_stream($stream2, SSH2_STREAM_STDIO); 
  $stream_out3 = ssh2_fetch_stream($stream3, SSH2_STREAM_STDIO); 
  $stream_out4 = ssh2_fetch_stream($stream4, SSH2_STREAM_STDIO); 
  $output = "$GET_current_USER@$HostName:~ $ \n$serviceContent\n\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD1\n";
  $output .= stream_get_contents($stream_out1);
  $output .= "$GET_current_USER@$HostName:~ $ $CMD2\n";
  $output .= stream_get_contents($stream_out2);
  $output .= "$GET_current_USER@$HostName:~ $ $CMD3\n";
  $output .= stream_get_contents($stream_out3);
  $output .= "$GET_current_USER@$HostName:~ $ $CMD4\n";
  $output .= stream_get_contents($stream_out4);
  }
  
  
  //Cài Đặt Hành Động Với LCD
  if (isset($_POST['lcd_auto_start'])) {
  $CMD = "systemctl --user start VBot_LCD_OLED.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['lcd_auto_stop'])) {
  $CMD = "systemctl --user stop VBot_LCD_OLED.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['lcd_auto_enable'])) {
  $CMD = "systemctl --user enable VBot_LCD_OLED.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['lcd_auto_disable'])) {
  $CMD = "systemctl --user disable VBot_LCD_OLED.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['lcd_auto_status'])) {
  $CMD = "systemctl --user status VBot_LCD_OLED.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['lcd_auto_restart'])) {
  $CMD = "systemctl --user restart VBot_LCD_OLED.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['lcd_config_auto'])) {
  // Đường dẫn đến file service
  $serviceFilePath = "{$VBot_Offline}resource/VBot_LCD_OLED.service";
  
  // Nội dung của file service với biến
  $serviceContent = <<<EOD
  [Unit]
  Description=VBot_LCD_OLED
  
  [Service]
  ExecStart=/usr/bin/python3.9 {$VBot_Offline}resource/screen_disp/Run.py
  Restart=always
  
  [Install]
  WantedBy=default.target
  EOD;
  
  // Tạo hoặc ghi đè file service
  file_put_contents($serviceFilePath, $serviceContent);
  $CMD1 = "cp {$VBot_Offline}resource/VBot_LCD_OLED.service /home/$ssh_user/.config/systemd/user/VBot_LCD_OLED.service";
  $CMD2 = "sudo chmod 0777 {$VBot_Offline}resource/VBot_LCD_OLED.service";
  $CMD3 = "ln -s /home/$ssh_user/.config/systemd/user/VBot_LCD_OLED.service /home/$ssh_user/.config/systemd/user/default.target.wants/VBot_LCD_OLED.service";
  $CMD4 = "sudo systemctl daemon-reload";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream1 = ssh2_exec($connection, $CMD1);
  $stream2 = ssh2_exec($connection, $CMD2);
  $stream3 = ssh2_exec($connection, $CMD3);
  $stream4 = ssh2_exec($connection, $CMD4);
  stream_set_blocking($stream1, true); 
  stream_set_blocking($stream2, true); 
  stream_set_blocking($stream3, true); 
  stream_set_blocking($stream4, true); 
  $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO); 
  $stream_out2 = ssh2_fetch_stream($stream2, SSH2_STREAM_STDIO); 
  $stream_out3 = ssh2_fetch_stream($stream3, SSH2_STREAM_STDIO); 
  $stream_out4 = ssh2_fetch_stream($stream4, SSH2_STREAM_STDIO); 
  $output = "$GET_current_USER@$HostName:~ $ $CMD1\n";
  $output .= stream_get_contents($stream_out1);
  $output .= "$GET_current_USER@$HostName:~ $ $CMD2\n";
  $output .= stream_get_contents($stream_out2);
  $output .= "$GET_current_USER@$HostName:~ $ $CMD3\n";
  $output .= stream_get_contents($stream_out3);
  $output .= "$GET_current_USER@$HostName:~ $ $CMD4\n";
  $output .= stream_get_contents($stream_out4);
  }
  #Kết Thúc Cài Đặt Hành Động Với LCD
  
  if (isset($_POST['apache_restart'])) {
  $CMD = "sudo systemctl restart apache2.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['restart_alsa'])) {
  #$CMD = "sudo systemctl restart alsa-restore";
  $CMD = "sudo systemctl restart alsa-state";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['serial_getty_ttyS0_stop'])) {
  $CMD = "sudo systemctl stop serial-getty@ttyS0.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['serial_getty_ttyS0_start'])) {
  $CMD = "sudo systemctl start serial-getty@ttyS0.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['serial_getty_ttyS0_disable'])) {
  $CMD = "sudo systemctl disable serial-getty@ttyS0.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['serial_getty_ttyS0_enable'])) {
  $CMD = "sudo systemctl enable serial-getty@ttyS0.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['reboot_os'])) {
  $CMD = "sudo reboot";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['reload_services'])) {
  $CMD = "sudo systemctl daemon-reload";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['restart_auto_wifi'])) {
  $CMD = "sudo systemctl restart wifi-connect.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['enable_auto_wifi'])) {
  $CMD = "sudo systemctl enable wifi-connect.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['logs_auto_wifi'])) {
  $CMD = "journalctl -u wifi-connect.service -e";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['status_auto_wifi'])) {
  $CMD = "sudo systemctl status wifi-connect.service";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['chmod_vbot'])) {
  $CMD1 = "sudo chmod -R 0777 $VBot_Offline";
  $CMD2 = "sudo chmod -R 0777 $HTML_VBot_Offline";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream1 = ssh2_exec($connection, $CMD1);
  $stream2 = ssh2_exec($connection, $CMD2);
  stream_set_blocking($stream1, true); 
  stream_set_blocking($stream2, true); 
  $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO); 
  $stream_out2 = ssh2_fetch_stream($stream2, SSH2_STREAM_STDIO); 
  $output = "$GET_current_USER@$HostName:~ $ $CMD1\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD2\n";
  $output .= stream_get_contents($stream_out1); 
  $output .= stream_get_contents($stream_out2); 
  }
  
  if (isset($_POST['owner_vbot'])) {
  $CMD1 = "sudo chown -R $GET_current_USER:$GET_current_USER $VBot_Offline";
  $CMD2 = "sudo chown -R $GET_current_USER:$GET_current_USER $HTML_VBot_Offline";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream1 = ssh2_exec($connection, $CMD1);
  $stream2 = ssh2_exec($connection, $CMD2);
  stream_set_blocking($stream1, true); 
  stream_set_blocking($stream2, true); 
  $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO); 
  $stream_out2 = ssh2_fetch_stream($stream2, SSH2_STREAM_STDIO); 
  $output = "$GET_current_USER@$HostName:~ $ $CMD1\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD2\n";
  $output .= stream_get_contents($stream_out1); 
  $output .= stream_get_contents($stream_out2); 
  }
  
  if (isset($_POST['ifconfig_os'])) {
  $CMD = "ifconfig";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['lscpu_os'])) {
  $CMD = "lscpu";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['hostnamectl_os'])) {
  $CMD = "hostnamectl";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['kiem_tra_bo_nho'])) {
  $CMD = "df -hm";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['kiem_tra_dung_luong'])) {
  $CMD = "free -mh";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['pvporcupine_info'])) {
  $CMD = "pip show pvporcupine";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['picovoice_info'])) {
  $CMD = "pip show picovoice";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['pip_show_all_lib'])) {
  $CMD = "pip list";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['os_image_created'])) {
      $CMD_CHECK = "[ -f /os_image_created.txt ] && echo 'EXIST' || echo 'NOT_EXIST'";
      $connection = ssh2_connect($ssh_host, $ssh_port);
      if (!$connection) { die($SSH_CONNECT_ERROR); }
      if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) { die($SSH2_AUTH_ERROR); }
      $stream = ssh2_exec($connection, $CMD_CHECK);
      stream_set_blocking($stream, true);
      $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
      $check_output = trim(stream_get_contents($stream_out));
      if ($check_output === "EXIST") {
          $CMD = "cat /os_image_created.txt";
          $stream = ssh2_exec($connection, $CMD);
          stream_set_blocking($stream, true);
          $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
          #$output = "$GET_current_USER@$HostName:~ $ $CMD\n";
          $output = stream_get_contents($stream_out);
      } else {
          $output = "Không lấy được thông tin phiên bản OS IMG";
      }
      #echo nl2br(htmlspecialchars($output));
  }
  
  
  //check_version_picovoice_porcupine
  if (isset($_POST['check_version_picovoice_porcupine'])) {
	  
/*
  $remotePath = "/home/$ssh_user/.local/lib/python3.9/site-packages/";
  $pattern = '/^pvporcupine-(\d+\.\d+\.\d+)\.dist-info$/m';
  // Thực hiện lệnh ls để lấy danh sách thư mục
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, "ls $remotePath");
  stream_set_blocking($stream, true);
  $outputhh = stream_get_contents($stream);
  fclose($stream);
  $output .= "$GET_current_USER@$HostName:~ssh$:\n";
  // Kiểm tra xem có thư mục nào khớp với biểu thức chính quy không
  if (preg_match($pattern, $outputhh, $matches)) {
      $foundVersion = $matches[1];
      $output .= "Phiên bản Picovoice: $foundVersion\n";
  } else {
      //echo "Không tìm thấy thư mục pvporcupine-X.X.X.dist-info.";
  $path_picovoice = "/home/$ssh_user/.local/lib/python3.9/site-packages/picovoice/_picovoice.py";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, "cat $path_picovoice");
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output =  stream_get_contents($stream_out);
  //echo $output;
  $text_picovoice_version = picovoice_version($output, 'Picovoice', 'version');
  $firstThreeCharspicovoice_version = substr($text_picovoice_version, 0, 3);
  $output .= "Phiên bản Picovoice: $text_picovoice_version\n";
  }
  */

  $CMD = "pip show picovoice pvporcupine";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);

  //Kiểm tra phiên bản porcupine hiện tại
   if ($Config['smart_config']['smart_wakeup']['hotword']['lang'] == 'vi') {
      $porcupine_check = $Config['smart_config']['smart_wakeup']['hotword']['library']['vi']['modelFilePath'];
  } elseif ($Config['smart_config']['smart_wakeup']['hotword']['lang'] == 'eng') {
      $porcupine_check = $Config['smart_config']['smart_wakeup']['hotword']['library']['eng']['modelFilePath'];
  }
  
  $file_path = $VBot_Offline.'resource/picovoice/library/'.$porcupine_check;
  $text_porcupine_version = porcupine_version($file_path);
  $output .= "\nPhiên bản thư viện Porcupine: $text_porcupine_version";
  }
  
  
  if (isset($_POST['install_picovoice'])) {
  $versions_picovoice_install = $_POST['versions_picovoice_install'];
  if (empty($versions_picovoice_install)) {
      $output = "Picovoice:> Hãy chọn phiên bản picovoice cần cài đặt\n";
  } else {
  $CMD = "pip install picovoice==$versions_picovoice_install";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true); 
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO); 
  $output = "$GET_current_USER@$HostName:~$ pip install picovoice==$versions_picovoice_install\n";
  $output .= stream_get_contents($stream_out);
  }
  }
  
  if (isset($_POST['install_porcupine'])) {
  $destinationPath = $VBot_Offline.'resource/picovoice/library';
  $versions_porcupine_install = $_POST['versions_porcupine_install'];
  if (empty($versions_porcupine_install)) {
      $output .= "Porcupine:> Hãy chọn phiên bản Porcupine cần cài đặt\n";
  } else {
  $fileUrl = 'https://github.com/Picovoice/porcupine/archive/refs/tags/v'.$versions_porcupine_install.'.zip';
  $fileContent = file_get_contents($fileUrl);
  $filename = basename($fileUrl);
  $destinationFile = $destinationPath . '/' . $filename;
  file_put_contents($destinationFile, $fileContent);
  chmod($destinationFile, 0777);
  $output .= "Porcupine:> Phiên bản thư viện Porcupine (.pv) được cài đặt là: $versions_porcupine_install\n";
  $fileNameZip = 'porcupine-'.$versions_porcupine_install.'/lib/common';
  $zipFilePath = $destinationPath.'/v'.$versions_porcupine_install.'.zip';
  $zip = new ZipArchive;
  if ($zip->open($zipFilePath) === TRUE) {
      $fileNamesToCopy = ["$fileNameZip/porcupine_params.pv", "$fileNameZip/porcupine_params_vn.pv"];
      foreach ($fileNamesToCopy as $fileNameInZip) {
          // Kiểm tra xem file có tồn tại trong ZIP hay không
          $index = $zip->locateName($fileNameInZip);
          if ($index !== false) {
              // Đọc nội dung của file từ ZIP
              $fileContent = $zip->getFromIndex($index);
              // Đường dẫn đến thư mục đích
              $destinationFilee = $destinationPath . '/' . basename($fileNameInZip);
              // Ghi nội dung của file vào thư mục đích
              file_put_contents($destinationFilee, $fileContent);
              //$output .= 'Porcupine:> File '.basename($fileNameInZip).' đã được đưa vào thư mục lib có chứa tệp .pv | ';
          } else {
              $output .= 'Porcupine:> File '.basename($fileNameInZip). 'không tồn tại | ';
          }
      }
      $zip->close();
  	shell_exec('rm ' . escapeshellarg($zipFilePath));
  	$output .= 'Porcupine:> HÃY CHỌN LẠI NGÔN NGỮ HOTWORD VÀ LƯU CẤU HÌNH SAU ĐÓ KHỞI ĐỘNG LẠI VBot ĐỂ ÁP DỤNG.';
  } else {
      $output .= 'Porcupine:> Lỗi không thể mở file thư viện Porcupine: v'.$versions_porcupine_install.'.zip \n';
  }
  }
  }

  if (isset($_POST['set_time_zones'])) {
	$timezones_value = $_POST['show_lits_timezone'];
  $CMD = "sudo timedatectl set-timezone $timezones_value";
  $CMD1 = "timedatectl";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  sleep(3);
  $stream1 = ssh2_exec($connection, $CMD1);
  stream_set_blocking($stream1, true);
  $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD1\n";
  $output .=  stream_get_contents($stream_out);
  $output .=  stream_get_contents($stream_out1);
  }

  if (isset($_POST['check_time_zones'])) {
  $CMD = "timedatectl";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .=  stream_get_contents($stream_out);
  }
  
  if (isset($_POST['fix_time_zones'])) {
	$timezones_value = $_POST['show_lits_timezone'];
  $CMD = 'sudo cp '.$VBot_Offline.'resource/timesyncd.conf /etc/systemd/timesyncd.conf';
  $CMD1 = "sudo systemctl restart systemd-timesyncd && sudo timedatectl set-ntp true && sudo timedatectl timesync-status && timedatectl";
  $connection = ssh2_connect($ssh_host, $ssh_port);
  if (!$connection) {die($SSH_CONNECT_ERROR);}
  if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {die($SSH2_AUTH_ERROR);}
  $stream = ssh2_exec($connection, $CMD);
  stream_set_blocking($stream, true);
  $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
  $stream1 = ssh2_exec($connection, $CMD1);
  stream_set_blocking($stream1, true);
  $stream_out1 = ssh2_fetch_stream($stream1, SSH2_STREAM_STDIO);
  $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
  $output .= "$GET_current_USER@$HostName:~ $ $CMD1\n";
  $output .=  stream_get_contents($stream_out);
  $output .=  stream_get_contents($stream_out1);
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
        <h1>Dòng lệnh/Đầu cuối</h1>
        <nav>
          <ol class="breadcrumb">
            <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Command/Terminal</li>
            <li class="breadcrumb-item active"><?php echo "<font color='green'>".@trim(file_get_contents('/os_image_created.txt'))."</font>" ?: "<font color='red'>VBot Assistant OS Image Build: N/A</font>"; ?></li>
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
                  <br/>
                  <div class="row g-3 d-flex justify-content-center">
                    <div class="col-auto">
                      <div class="btn-group">
                        <div class="dropdown">
                          <button class="btn btn-danger dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                          VBot Auto
                          </button>
                          <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="auto_start" type="submit" title="Chạy lại trương trình">Chạy</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="auto_restart" type="submit" title="Tạm dừng trương trình đang chạy">Khởi động lại</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="auto_stop" type="submit" title="Tạm dừng trương trình đang chạy">Dừng</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="auto_status" type="submit" title="Tạm dừng trương trình đang chạy">Trạng thái</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="auto_enable" type="submit" title="Tự động chạy trương trình khi hệ thống khởi động">Kích hoạt</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="auto_disable" type="submit" title="Vô hiệu hóa trương trình, không cho tự động chạy">Vô hiệu</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="config_auto" type="submit" title="Vô hiệu hóa trương trình, không cho tự động chạy">Cài đặt cấu hình Auto</button></li>
                          </ul>
                        </div>
                      </div>
                      <div class="btn-group">
                        <div class="dropdown">
                          <button class="btn btn-warning dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                          OS Wifi
                          </button>
                          <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                            <li>
                              <button onclick="loading('show')" class="dropdown-item text-danger" name="restart_auto_wifi" type="submit" title="Khởi động lại Services Auto Wifi Manaager">Restart Auto Wifi Manager</button>
                            </li>
                            <button onclick="loading('show')" class="dropdown-item text-danger" name="enable_auto_wifi" type="submit" title="Kích Hoạt Services Auto Wifi Manaager">Enable Auto Wifi Manager</button></li>
                            <button onclick="loading('show')" class="dropdown-item text-danger" name="auto_wifi_manager_only" type="submit" title="Chỉ Cài Đặt Auto Wifi Manager Và Tạo Điểm truy Cập AP">Chỉ Install Auto Wifi Manager</button></li>
                            <button onclick="loading('show')" class="dropdown-item text-danger" name="auto_wifi_manager_and_speaker_ip" type="submit" title="Cài Đặt Auto Wifi Manager Và Đọc Địa Chỉ IP Khi Mà IP Hoặc Wifi Bị Thay Đổi">Install Auto Wifi Manager + Đọc IP</button></li>
                            <button onclick="loading('show')" class="dropdown-item text-danger" name="logs_auto_wifi" type="submit" title="Xem Logs Auto Wifi Manaager">Logs Auto Wifi Manager</button></li>
                            <button onclick="loading('show')" class="dropdown-item text-danger" name="status_auto_wifi" type="submit" title="Kiêm tra trạng thái Auto Wifi Manaager">Status Auto Wifi Manager</button></li>
                          </ul>
                        </div>
                      </div>
						<div class="btn-group">
                        <div class="dropdown">
                          <button class="btn btn-primary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false" title="Cấu Hình WebUI Ra Internet">
                          WebUI External
                          </button>
                          <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="enabled_vbot_api_external" type="submit" title="Cấu Hình WebUI Ra Internet">Kích Hoạt WebUI Ra Internet</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="disable_vbot_api_external" type="submit" title="Cấu Hình WebUI Ra Internet">Vô Hiệu WebUI Ra Internet</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="apache_restart" type="submit" title="Restart Apache2">Restart WebUI Apache2</button></li>

                          </ul>
                        </div>
                        </div>

                      <div class="btn-group" disabled>
                        <div class="dropdown">
                          <button class="btn btn-info dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                          LCD OLED Auto
                          </button>
                          <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="lcd_auto_start" type="submit" title="Chạy lại trương trình">Chạy</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="lcd_auto_restart" type="submit" title="Tạm dừng trương trình đang chạy">Khởi động lại</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="lcd_auto_stop" type="submit" title="Tạm dừng trương trình đang chạy">Dừng</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="lcd_auto_status" type="submit" title="Tạm dừng trương trình đang chạy">Trạng thái</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="lcd_auto_enable" type="submit" title="Tự động chạy trương trình khi hệ thống khởi động">Kích hoạt</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="lcd_auto_disable" type="submit" title="Vô hiệu hóa trương trình, không cho tự động chạy">Vô hiệu</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="lcd_config_auto" type="submit" title="Tự Động Cài Đặt Các File Cấu Hình LCD OLED để tự động chạy cùng hệ thống">Install Auto Start LCD on Boot</button></li>
                          </ul>
                        </div>
                      </div>
                      <div class="btn-group">
                        <div class="dropdown">
                          <button class="btn btn-dark dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                          Hệ Thống
                          </button>
                          <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="apache_restart" type="submit" title="Khởi động lại apache2">Restart Apache2</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="logs_apache2" type="submit" title="Khởi động lại apache2">Logs Apache2</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="restart_alsa" type="submit" title="Khởi động lại Alsa">Restart Alsa-Restore</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="reboot_os" type="submit" title="Khởi động lại hệ thống">Reboot OS</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="chmod_vbot" type="submit" title="Chmod VBot và UI HTML thành 0777">Chmod 0777</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="owner_vbot" type="submit" title="Thay đổi quyền sở hữu các file thành của người dùng SSH">Owner Change</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="reload_services" type="submit" title="Re-load lại các Services">Re-load Services</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="ifconfig_os" type="submit" title="Kiểm tra thông tin mạng">Thông tin mạng</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="lscpu_os" type="submit" title="Kiểm tra thông CPU">Thông tin CPU</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="hostnamectl_os" type="submit" title="Kiểm tra thông tin hệ điều hành">Thông tin OS</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="kiem_tra_bo_nho" type="submit" title="Kiểm tra thông tin bộ nhớ">Thông tin bộ nhớ</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="kiem_tra_dung_luong" type="submit" title="Kiểm tra thông tin dung lượng">Thông tin dung lượng</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="serial_getty_ttyS0_start" type="submit" title="Bắt đầu một phiên đăng nhập (login shell) qua cổng UART">Start serial-getty@ttyS0.service</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="serial_getty_ttyS0_stop" type="submit" title="Dừng phiên đăng nhập (login shell) qua cổng UART">Stop serial-getty@ttyS0.service</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="serial_getty_ttyS0_disable" type="submit" title="Vô hiệu một phiên đăng nhập (login shell) qua cổng UART (Start UP)">Disable serial-getty@ttyS0.service</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="serial_getty_ttyS0_enable" type="submit" title="Kích hoạt một phiên đăng nhập (login shell) qua cổng UART (Start UP)">Enable serial-getty@ttyS0.service</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="list_systemctl_enabled" type="submit" title="Các dịch vụ đang khởi động cùng hệ thống">Systemctl List Enable</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="sudo_alsactl_store" type="submit" title="Lưu cấu hình âm thanh alsamixer">sudo alsactl store</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="os_image_created" type="submit" title="Kiểm tra phiên bản OS IMG">Phiên bản OS IMG</button></li>
                          </ul>
                        </div>
                      </div>
                      <div class="btn-group">
                        <div class="dropdown">
                          <button class="btn btn-success dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                          Thư Viện
                          </button>
                          <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="pip_show_all_lib" type="submit" title="Liệt kê các thư viện đã cài bằng pip">pip show all lib</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="pvporcupine_info" type="submit" title="Kiểm tra thông tin thư viện pvporcupine">Thông tin pvporcupine</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="picovoice_info" type="submit" title="Kiểm tra thông tin thư viện picovoice">Thông tin picovoice</button></li>
                          </ul>
                        </div>
                      </div>
                      <div class="btn-group">
                        <div class="dropdown">
                          <button class="btn btn-secondary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                          ALSA SoundCard
                          </button>
                          <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="alsamixer_soundcard_start" type="submit" title="alsamixer_soundcard_start">ALSA SoundCard Start</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="alsamixer_soundcard_stop" type="submit" title="alsamixer_soundcard_stop">ALSA SoundCard Stop</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="alsamixer_soundcard_disable" type="submit" title="alsamixer_soundcard_disable">ALSA SoundCard Disable</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="alsamixer_soundcard_enable" type="submit" title="alsamixer_soundcard_enable">ALSA SoundCard Enable</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="alsamixer_soundcard_status" type="submit" title="alsamixer_soundcard_status">ALSA SoundCard Status</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="save_asound_to_alsamixer" type="submit" title="save_asound_to_alsamixer">Save Alsamixer SoundCard</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="alsamixer_asound_to_alsamixer" type="submit" title="alsamixer_asound_to_alsamixer">Restore ALSA SoundCard Driver Default</button></li>
                          </ul>
                        </div>
                      </div>
                </form>
                <!-- 
                  <div class="btn-group">
                  <form method="POST" action="">
                  <button class="btn btn-primary rounded-pill" name="setting_apache2" type="button" title="Cấu hình apache2">Cấu hình apache2</button><br/>
                  <div class="input-group mb-3">
                  <input required="" class="form-control border-success" type="text" name="setting_apache2_path" id="setting_apache2_path" placeholder="/home/pi/VBot_Offline/html" title="Ví dụ: /home/pi/VBot_Offline/html">
                  <div class="invalid-feedback">Cần nhập đường dẫn path cần cấu hình apache2</div>
                  <button class="btn btn-success border-success" type="submit">Cấu hình</button>
                  </div>
                  </form>  
                  </div>
                  -->
                </div>
                </div>
                <hr/>
                <form method="POST" action="">
                  <div class="row g-3 d-flex justify-content-center">
                    <div class="col-auto">
                      <div class="input-group">
                        <span class="input-group-text text-success">Nâng/Hạ Cấp Picovoice</span>
                        <select class="btn btn-success dropdown-toggle" data-toggle="dropdown" id="versions_picovoice_install" name="versions_picovoice_install">
                          <option value="" selected>Đang Lấy Dữ Liệu...</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-auto">
                      <div class="input-group-append">
                        <button class="btn btn-danger" onclick="loading('show')" name="install_picovoice" title="Cài đặt Picovoice" type="submit">Cài Đặt Picovoice</button>
                        <button type='submit' onclick="loading('show')" name='check_version_picovoice_porcupine' class='btn btn-primary' title='Kiểm tra phiên bản Picovoice và Porcupine'>Kiểm tra phiên bản</button>
                      </div>
                    </div>
                  </div>
                  <br/>
                  <div class="row g-3 d-flex justify-content-center">
                    <div class="col-auto">
                      <div class="input-group">
                        <span class="input-group-text text-success">Thư Viện Porcupine (.pv)</span>
                        <select class="btn btn-success dropdown-toggle" data-toggle="dropdown" id="versions_porcupine_install" name="versions_porcupine_install">
                          <option value="" selected>Đang Lấy Dữ Liệu...</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-auto">
                      <div class="input-group-append">
                        <button class="btn btn-danger" onclick="loading('show')" name="install_porcupine" title="Cài đặt Porcupine" type="submit">Cài Đặt Porcupine</button>
                      </div>
                    </div>
                  </div>
                </form>
				<hr/>
				
                <form method="POST" action="">
                  <div class="row g-3 d-flex justify-content-center">
				  
                    <div class="col-auto">
                      <div class="btn-group">
                        <div class="dropdown">
                          <button class="btn btn-secondary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false" title="Thiết Lập Múi Giờ, Thời Gian Cho Hệ Thống">
                          Thời Gian, Múi Giờ
                          </button>
                          <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="list_time_zones" type="submit" id="list_time_zones" title="Hiển Thị Danh Sách Múi Giờ">Danh Sách Múi Giờ</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="check_time_zones" type="submit" id="check_time_zones" title="Kiểm Tra Múi Giờ Hiện Tại Trên Hệ Thống">Kiểm Tra Múi Giờ Hệ Thống</button></li>
                            <li><button onclick="loading('show')" class="dropdown-item text-danger" name="fix_time_zones" type="submit" id="fix_time_zones" title="Sửa Lỗi Đồng Bộ, Sai Thời Gian Hệ Thống">Sửa Lỗi Đồng Bộ, Sai Thời Gian</button></li>
                          </ul>
                        </div>
                      </div>

                  </div>
				<?php
				if (isset($_POST['list_time_zones'])) {
				$listtimezone = shell_exec('timedatectl list-timezones');
				$timezones = explode("\n", trim($listtimezone));
				echo '<br/><br/><div class="input-group mb-3"><span class="input-group-text text-success">Chọn Múi Giờ:</span><select name="show_lits_timezone" id="show_lits_timezone" class="form-select border-success">';
				foreach ($timezones as $tz) {
					if (!empty($tz)) {
						$selected = ($tz === "Asia/Ho_Chi_Minh") ? ' selected' : '';
						echo '<option value="' . htmlspecialchars($tz) . '"' . $selected . '>' . htmlspecialchars($tz) . '</option>';
					}
				}
				echo '</select><button class="btn btn-success border-primary" name="set_time_zones" id="set_time_zones" type="submit" onclick="loading(\'show\')">Thiết Lập Múi Giờ</button></div>';
				  }
				?>
                  </div>
                </form>
				
				
                <hr/>
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
          xhr.onreadystatechange = function () {
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
      				showMessagePHP('Lỗi HTTP:' +xhr.status);
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
      //lấy dữ liệu phiên bản picovoice khi trang được tải toàn bộ
      window.onload = function() {
      	get_picovoice_version();
      };
      
    </script>
  </body>
</html>