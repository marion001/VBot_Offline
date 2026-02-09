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
  // Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
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
<?php
include 'html_head.php';
?>
<style>
  .header {
    text-align: center;
    margin-bottom: 20px;
  }

  .message {
    color: #f00;
    font-weight: bold;
  }
</style>

<body>
  <?php
  include 'html_header_bar.php';
  include 'html_sidebar.php';
  ?>
  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Thư Viện Python pip</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item active">lib python pip</li>
        </ol>
      </nav>
    </div>
    <section class="section">
      <div class="row">
        <form method="post">
          <center><button type="submit" class="btn btn-primary rounded-pill" onclick="loading('show')" name="check_versions">Kiểm tra danh sách thư viện python pip Còn Thiếu</button></center>
        </form>
        <?php
        // Hàm đọc và phân tích file thành mảng File Local
        function parsePipFile($filename)
        {
          if (!file_exists($filename)) {
            die("<p class='message'>File <strong>$filename</strong> không tồn tại!</p>");
          }
          $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
          $startIndex = 0;
          foreach ($lines as $index => $line) {
            if (strpos($line, 'Package') !== false && strpos($line, 'Version') !== false) {
              $startIndex = $index + 2;
              break;
            }
          }
          $packages = [];
          for ($i = $startIndex; $i < count($lines); $i++) {
            $parts = preg_split('/\s{2,}/', $lines[$i]);
            if (count($parts) === 2) {
              $packages[trim($parts[0])] = trim($parts[1]);
            }
          }
          return $packages;
        }
        // Hàm đọc và phân tích chuỗi dữ liệu thành mảng URL
        function parsePipString($data)
        {
          // Tách chuỗi thành các dòng
          $lines = explode("\n", $data);
          $startIndex = 0;
          foreach ($lines as $index => $line) {
            // Tìm dòng bắt đầu có tiêu đề
            if (strpos($line, 'Package') !== false && strpos($line, 'Version') !== false) {
              $startIndex = $index + 2; // Bỏ qua tiêu đề và dòng gạch ngang
              break;
            }
          }
          // Khởi tạo mảng lưu gói cài đặt
          $packages = [];
          for ($i = $startIndex; $i < count($lines); $i++) {
            $parts = preg_split('/\s{2,}/', $lines[$i]);
            if (count($parts) === 2) {
              // Lưu gói và phiên bản vào mảng
              $packages[trim($parts[0])] = trim($parts[1]);
            }
          }
          return $packages;
        }
        ?>
        <?php
        if (isset($_POST['check_versions'])) {
          //Chạy lệnh lấy dữu liệu pip của user
          $CMD = "pip list";
          $connection = ssh2_connect($ssh_host, $ssh_port);
          if (!$connection) {
            die("<center><h1><font color='red'>Không thể kết nối tới máy chủ SSH, Hãy Kiểm Tra Lại</font><br/><a href='Lib_pip.php'>Quay Lại</a></h1></center>");
          }
          if (!ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
            die("<center><h1><font color='red'>Xác thực SSH không thành công, Hãy kiểm tra lại thông tin đăng nhập SSH</font> <br/><a href='Lib_pip.php'>Quay Lại</a></h1></center>");
          }
          $stream = ssh2_exec($connection, $CMD);
          stream_set_blocking($stream, true);
          $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
          $output = "$GET_current_USER@$HostName:~ $ $CMD\n";
          $output .= stream_get_contents($stream_out);
          $filePath = $VBot_Offline . 'resource/pip_list_lib_user.txt';
          file_put_contents($filePath, $output);
          #chmod($filePath, 0777);
          //END Chạy lệnh lấy dữ liệu pip của user
          $urlParts = parse_url($Github_Repo_Vbot);
          $pathParts = explode('/', trim($urlParts['path'], '/'));
          $userName = $pathParts[0];
          $repoName = $pathParts[1];
          $mainPackages_git = file_get_contents("https://raw.githubusercontent.com/$userName/$repoName/refs/heads/main/resource/pip_list_lib.txt");
          if ($mainPackages_git === FALSE) {
            $mainPackages = parsePipFile($VBot_Offline . 'resource/pip_list_lib.txt');
          } else {
            $mainPackages = parsePipString($mainPackages_git);
          }
          $userPackages = parsePipFile($VBot_Offline . 'resource/pip_list_lib_user.txt');
          // Kiểm tra thư viện thiếu
          $missingPackages = array_diff_key($mainPackages, $userPackages);
          // Kiểm tra thư viện sai phiên bản
          $wrongVersionPackages = [];
          foreach ($mainPackages as $name => $version) {
            if (isset($userPackages[$name]) && $userPackages[$name] !== $version) {
              $wrongVersionPackages[$name] = [
                'mainVersion' => $version,
                'userVersion' => $userPackages[$name],
              ];
            }
          }
          // Hiển thị thư viện thiếu
          if (!empty($missingPackages)) {
			$manualBuildPackages = [
				'speexdsp-ns' => [
					'url' => 'FAQ.php',
					'note' => 'Thư viện cần build thủ công từ source'
				],
				'snowboy' => [
					'url' => 'FAQ.php',
					'note' => 'Thư viện cần build thủ công từ source'
				],
				// thêm thư viện khác ở đây nếu cần
			];

            echo "<h5 class='card-title text-danger'>Thư Viện Còn Thiếu:</h5>";
            echo "<table class='table table-bordered border-primary'><thead>
            		<tr>
            		<th style='text-align: center; vertical-align: middle;'>Tên Thư Viện</th>
            		<th style='text-align: center; vertical-align: middle;'>Phiên Bản Yêu Cầu (Main)</th>
            		<th style='text-align: center; vertical-align: middle;'>Lệnh Cài Đặt</th>
            		<th style='text-align: center; vertical-align: middle;'>Hành Động</th>
            		</tr>
            		</thead><tbody>";

			foreach ($missingPackages as $name => $version) {
				$isManual = isset($manualBuildPackages[$name]);
				if ($isManual) {
					// Thư viện build thủ công
					$guideUrl = $manualBuildPackages[$name]['url'];
					$installCmd = "<span class='text-warning'>Build thủ công</span>";
					$actionBtn = "<a href='{$guideUrl}' target='_blank' class='btn btn-warning rounded-pill'>Xem hướng dẫn</a>";
				} else {
					//Thư viện cài bằng pip
					$Command_pip = base64_encode("pip install {$name}=={$version}");
					$installCmd = "<font color='blue'>pip install {$name}=={$version}</font>";
					$actionBtn = "
						<button type='button'
								class='btn btn-success rounded-pill'
								onclick='VBot_Command(" . json_encode($Command_pip) . ")'>
							Cài Đặt
						</button>";
				}
				echo "<tr>
					<td style='text-align: center; vertical-align: middle;'>
						{$name}
						<a href='https://pypi.org/project/{$name}/'
						   target='_blank'
						   title='Kiểm tra thư viện: {$name}'>
						   <i class='bi bi-box-arrow-up-right'></i>
						</a>
					</td>
					<td style='text-align: center; vertical-align: middle;'>{$version}</td>
					<td style='text-align: center; vertical-align: middle;'>{$installCmd}</td>
					<td style='text-align: center; vertical-align: middle;'>{$actionBtn}</td>
				</tr>";
			}


            echo "</tbody></table>";
          } else {
            echo "<h4 class='card-title text-success'><center>Tất cả các thư viện python pip cần thiết đều đã được cài đặt</center></h4>";
          }
          // Hiển thị thư viện sai phiên bản
          if (!empty($wrongVersionPackages)) {
            echo "<h5 class='card-title text-danger'>Thư Viện Bị Sai Phiên Bản:</h5>";
            echo "<table class='table table-bordered border-primary'>
            		<thead><tr>
            		<th style='text-align: center; vertical-align: middle;'>Tên Thư Viện</th>
            		<th style='text-align: center; vertical-align: middle;'>Phiên Bản Yêu Cầu (Main)</th>
            		<th style='text-align: center; vertical-align: middle;'>Phiên Bản Hiện Tại Của Bạn (User)</th>
            		<th style='text-align: center; vertical-align: middle;'>Lệnh Cài Đặt</th>
            		<th style='text-align: center; vertical-align: middle;'>Hành Động</th>
            		</tr></thead><tbody>";
            foreach ($wrongVersionPackages as $name => $versions) {
              $Command_pip = base64_encode('pip install ' . $name . '==' . $versions['mainVersion']);
              echo "<tr>
            			<td style='text-align: center; vertical-align: middle;'>$name <a href='https://pypi.org/project/$name/' target='_bank' title='Kiểm tra $name'><i class='bi bi-box-arrow-up-right'></i></a></td>
            			<td style='text-align: center; vertical-align: middle;'><font color=green>{$versions['mainVersion']}</font></td>
            			<td style='text-align: center; vertical-align: middle;'><font color=red>{$versions['userVersion']}</font></td>
            			<td style='text-align: center; vertical-align: middle;'><font color=blue>pip install $name=={$versions['mainVersion']}</font></td>
            			<td style='text-align: center; vertical-align: middle;'><button type='button' class='btn btn-success rounded-pill' onclick='VBot_Command(" . json_encode($Command_pip) . ")'>Cài Đặt</button></td>
            			</tr>";
            }
            echo "</tbody></table>";
          } else {
            echo "<h4 class='card-title text-success'><center>Tất cả các thư viện python pip đều đúng phiên bản</center></h4>";

//$filePath = $VBot_Offline . 'resource/pip_list_lib_user.txt';

$packages = parsePipFile($filePath);

echo '<table class="table table-striped table-hover table-bordered border-primary">
    <tr>
        <th colspan="3" class="text-danger" style="text-align: center; vertical-align: middle;">Danh Sách Thư Viện Python pip</th>
    </tr>
    <tr>
        <th style="text-align: center; vertical-align: middle;">STT</th>
        <th style="text-align: center; vertical-align: middle;">Tên thư viện</th>
        <th style="text-align: center; vertical-align: middle;">Phiên bản hiện tại</th>
    </tr>
';

$stt = 1;
foreach ($packages as $name => $version) {
    echo '
    <tr>
        <td class="lib_pip" style="text-align: center; vertical-align: middle;">'.$stt.'</td>
        <td class="lib_pip" style="text-align: center; vertical-align: middle;">'.$name.'</td>
        <td class="lib_pip" style="text-align: center; vertical-align: middle;">'.$version.'</td>
    </tr>
    ';
    $stt++;
}

echo "</table>";
          }
        }
        ?>
      </div>
    </section>
  </main>
  <!-- ======= Footer ======= -->
  <?php
  include 'html_footer.php';
  ?>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <?php
  include 'html_js.php';
  ?>
</body>

</html>