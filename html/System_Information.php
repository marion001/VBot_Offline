<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';

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


// Chuyển đổi từ chuỗi sang số và đơn vị GB
function convertToGB($value) {
    $number = floatval($value);
    if (strpos($value, 'T') !== false) {
		// Chuyển đổi từ TB sang GB
        return $number * 1024;
    } elseif (strpos($value, 'G') !== false) {
		// Giữ nguyên GB
        return $number;
    } elseif (strpos($value, 'M') !== false) {
		// Chuyển đổi từ MB sang GB
        return $number / 1024;
    } elseif (strpos($value, 'K') !== false) {
		// Chuyển đổi từ KB sang GB
        return $number / (1024 * 1024);
    } else {
		// Trường hợp không xác định hoặc đơn vị rất nhỏ
        return 0;
    }
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
      <h1>Thông tin hệ thống</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">Thông tin OS</li>
        </ol>
      </nav>
    </div>
    <section class="section dashboard">
      <div class="row">
        <div class="col-lg-8">
          <div class="row">

<?php
$diskInfo = shell_exec('df -h');
$lines_disk = explode("\n", trim($diskInfo));
$totalSize = 0;
$totalUsed = 0;
$totalAvail = 0;
foreach ($lines_disk as $index => $line) {
    if ($index === 0) continue;
    $columns = preg_split('/\s+/', $line);
    $sizeGB = convertToGB($columns[1]);
    $usedGB = convertToGB($columns[2]);
    $availGB = convertToGB($columns[3]);
    $totalSize += convertToGB($columns[1]);
    $totalUsed += convertToGB($columns[2]);
    $totalAvail += convertToGB($columns[3]);
}
?>
            <!-- Sales Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card sales-card">
                <div class="card-body">
                  <h5 class="card-title">Bộ nhớ</span></h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-device-hdd-fill"></i>
                    </div>
                    <div class="ps-3">
						<h6><?php echo number_format($totalSize, 1); ?>GB</h6>
                      <span class="text-success small pt-1 fw-bold">Đã dùng: </span> <span class="text-danger small pt-2 ps-1"><?php echo number_format($totalUsed, 1); ?>GB</span>
                      <br/><span class="text-success small pt-1 fw-bold">Còn lại: </span> <span class="text-danger small pt-2 ps-1"><?php echo number_format($totalAvail, 1); ?>GB</span>
                    </div>
                  </div>
                </div>

              </div>
            </div>

<?php
// Hàm chuyển đổi đơn vị từ KB sang GB
function convertKBToGB($kb) {
	// 1 GB = 1048576 KB
    return $kb / 1048576;
}
// Lấy thông tin RAM từ lệnh free
$ramInfo = shell_exec('free -k');
// Chuyển đổi kết quả thành mảng dòng
$lines = explode("\n", trim($ramInfo));
// Phân tích dòng thứ hai, nơi chứa thông tin về RAM
$columns = preg_split('/\s+/', $lines[1]);
// Lấy các giá trị RAM
$totalRAM = convertKBToGB($columns[1]);
$usedRAM = convertKBToGB($columns[2]);
$freeRAM = convertKBToGB($columns[3]);
?>
            <!-- Revenue Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card revenue-card">
                <div class="card-body">
                  <h5 class="card-title">RAM</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-memory"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?php echo number_format($totalRAM, 2); ?>GB</h6>
                      <span class="text-success small pt-1 fw-bold">Đã dùng: </span> <span class="text-danger small pt-2 ps-1"><?php echo number_format($usedRAM, 2); ?>GB</span>
                      <br/><span class="text-success small pt-1 fw-bold">Còn lại: </span> <span class="text-danger small pt-2 ps-1"><?php echo number_format($freeRAM, 2); ?>GB</span>

                    </div>
                  </div>
                </div>

              </div>
            </div>
<?php
// Lấy thông tin tiến trình CPU từ lệnh ps
$processes = shell_exec('ps aux --sort=-%cpu');
// Chia đầu ra thành các dòng
$lines = explode("\n", trim($processes));
// Biến để lưu tổng phần trăm CPU sử dụng
$totalCpuUsage = 0;
// Bỏ qua dòng tiêu đề và tính tổng phần trăm CPU
foreach ($lines as $index => $line) {
    // Bỏ qua dòng tiêu đề
    if ($index == 0) {
        continue;
    }
    // Tách các trường trong dòng
    $fields = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
    // Nếu dòng có đủ số trường
    if (count($fields) >= 11) {
        // Thêm phần trăm CPU vào tổng
        $totalCpuUsage += floatval($fields[2]);
    }
}

// Lấy thông tin CPU từ tệp /proc/cpuinfo
$cpuInfo = shell_exec('cat /proc/cpuinfo');

// Chuyển đổi kết quả thành mảng dòng
$lines = explode("\n", trim($cpuInfo));

$modelName = '';
$cpuCores = 0;
$cpuMHz = 0;

foreach ($lines as $line) {
    if (strpos($line, 'model name') !== false) {
        $modelName = trim(explode(':', $line)[1]);
    }
    if (strpos($line, 'processor') !== false) {
        $cpuCores++;
    }
    if (strpos($line, 'BogoMIPS') !== false) {
        $cpuMHz = trim(explode(':', $line)[1]);
    }
}
// Đọc nhiệt độ từ tập tin hệ thống
$temperature = shell_exec("cat /sys/class/thermal/thermal_zone0/temp");
// Chuyển đổi từ mili độ C sang độ C
$temperatureCelsius = intval($temperature) / 1000;
?>
            <div class="col-xxl-4 col-xl-12">
              <div class="card info-card customers-card">
                <div class="card-body">
                  <h5 class="card-title">CPU</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-cpu-fill"></i>
                    </div>
                    <div class="ps-3">
					<h6><?php echo $totalCpuUsage; ?>%</h6>
                      <span class="text-success small pt-1 fw-bold">Số lõi: </span> <span class="text-danger small pt-2 ps-1"><?php echo $cpuCores; ?></span>
                      <br/><span class="text-success small pt-1 fw-bold">Tần số: </span> <span class="text-danger small pt-2 ps-1"><?php echo $cpuMHz; ?>MHz</span>
                      <br/><span class="text-success small pt-1 fw-bold">Nhiệt độ: </span> <span class="text-danger small pt-2 ps-1"><?php echo $temperatureCelsius; ?>°C</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Thông tin ổ đĩa:</span></h5>
<table class='table table-bordered border-primary'>
<tr><th>Tệp tin hệ thống</th><th>Kích thước</th><th>Đã dùng</th><th>Còn lại</th><th>Sử dụng %</th><th>Gắn trên</th></tr>
<?php
foreach ($lines_disk as $index => $line) {
    if ($index === 0) continue;
    // Phân tách các cột
    $columns = preg_split('/\s+/', $line);
    // Hiển thị chỉ các cột quan trọng
    echo "<tr>";
    echo "<td>{$columns[0]}</td>"; // Filesystem
    echo "<td>{$columns[1]}</td>"; // Size
    echo "<td>{$columns[2]}</td>"; // Used
    echo "<td>{$columns[3]}</td>"; // Avail
    echo "<td>{$columns[4]}</td>"; // Use%
    echo "<td>{$columns[5]}</td>"; // Mounted on
    echo "</tr>";
}
?>
</table>

                </div>

              </div>
            </div>
            <div class="col-12">
              <div class="card recent-sales overflow-auto">

  
                <div class="card-body">
     
              <h5 class="card-title">Thông tin RAM</h5>

<table class='table table-bordered border-primary'>
<tr>
    <th></th> <!-- Ô trống cho nhãn hàng đầu tiên -->
    <th>total</th>
    <th>used</th>
    <th>free</th>
    <th>shared</th>
    <th>buff/cache</th>
    <th>available</th>
</tr>
<?php
// Lấy thông tin RAM từ hệ thống
$ramInfo1 = shell_exec('free -h');
// Chuyển đổi dữ liệu thành mảng các dòng
$ramLines1 = explode("\n", trim($ramInfo1));

// Hiển thị các dòng thông tin RAM (Mem và Swap)
for ($i = 1; $i < count($ramLines1); $i++) {
    echo '<tr>';
    $columns1 = preg_split('/\s+/', $ramLines1[$i]);

    // Hiển thị nhãn (Mem hoặc Swap)
    echo '<td>' . $columns1[0] . '</td>';

    // Hiển thị các giá trị từ cột 2 trở đi
    for ($j = 1; $j < count($columns1); $j++) {
        echo '<td>' . $columns1[$j] . '</td>';
    }

    // Thêm cột trống nếu cần thiết (chỉ cho dòng Swap)
    if ($columns1[0] === "Swap:") {
        echo '<td colspan="3"></td>';
    }

    echo '</tr>';
}
?>
</table>
                </div>
              </div>
            </div>
          </div>
        </div>


<?php
function convertUptimeToVietnamese($uptime) {
    // Xóa từ "up" và cắt chuỗi ở các dấu phẩy
    $uptime = str_replace('up ', '', $uptime);
    $uptimeParts = explode(', ', $uptime);

    $translations = [
        'days' => 'ngày',
        'hours' => 'giờ',
        'minutes' => 'phút'
    ];

    $result = [];

    // Duyệt qua từng phần của thời gian khởi động
    foreach ($uptimeParts as $part) {
        list($value, $unit) = explode(' ', $part);
        if (isset($translations[$unit])) {
            $result[] = "$value " . $translations[$unit];
        }
    }

    return implode(', ', $result);
}

// Lấy thời gian khởi động hệ thống
$uptime = shell_exec('uptime -p');

// Chuyển đổi thời gian khởi động thành tiếng Việt
$uptimeVietnamese = convertUptimeToVietnamese(trim($uptime));
// Lấy thông tin về bo mạch (board)
$boardInfo = shell_exec('cat /proc/device-tree/model');
// Lấy phiên bản PHP
$phpVersion = phpversion();
// Lấy phiên bản Python
$pythonVersion = shell_exec('python3 --version 2>&1');
// Lấy phiên bản Apache
$apacheVersion = shell_exec('apache2 -v | grep "Server version"');

?>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"><?php echo $boardInfo; ?></span></h5>

              <div class="activity">

                <div class="activity-item d-flex">
                  <div class="activite-label text-success">Tên máy chủ:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $HostName; ?>
                  </div>
                </div>
				
                <div class="activity-item d-flex">
                  <div class="activite-label text-success">Tên người dùng:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $GET_current_USER; ?>
                  </div>
                </div>

                <div class="activity-item d-flex">
                  <div class="activite-label text-success">IP máy chủ:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $serverIp; ?>
                  </div>
                </div>
				
                <div class="activity-item d-flex">
                  <div class="activite-label text-success">IP người truy cập:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $userIp; ?>
                  </div>
                </div>

                <div class="activity-item d-flex">
                  <div class="activite-label text-success">Phiên bản PHP:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $phpVersion; ?>
                  </div>
                </div>
				
                <div class="activity-item d-flex">
                  <div class="activite-label text-success">Phiên bản Python:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $pythonVersion; ?>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="activite-label text-success">Phiên bản Apache:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $apacheVersion; ?>
                  </div>
                </div>

                <div class="activity-item d-flex">
                  <div class="activite-label text-success">Thời gian khởi động:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $uptimeVietnamese; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Thông tin CPU</span></h5>

              <div class="activity">


<?php
// Lấy thông tin CPU từ lệnh lscpu
$cpuInfo1 = shell_exec('lscpu');

// Chuyển đổi dữ liệu thành mảng các dòng
$cpuLines1 = explode("\n", trim($cpuInfo1));

// Danh sách các thông tin cần hiển thị
$desiredKeys = [
    'Architecture',
    'Byte Order',
    'CPU(s)',
    'On-line CPU(s) list',
    'Thread(s) per core',
    'Core(s) per socket',
    'Socket(s)',
    'Vendor ID',
    'Model',
    'Model name',
    'Stepping',
    'CPU max MHz',
    'CPU min MHz',
    'BogoMIPS',
    'Flags'
];

foreach ($cpuLines1 as $line) {
    // Chia dòng thành cặp "tên thuộc tính : giá trị"
    $parts = explode(':', $line, 2);
    if (count($parts) == 2) {
        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Kiểm tra xem thuộc tính có trong danh sách cần hiển thị không
        if (in_array($key, $desiredKeys)) {
			echo '<div class="activity-item d-flex">';
            echo '<div class="activite-label text-success">'.$key.':</div>';
            echo '<div class="activity-content text-danger">'.$value.'</div></div>';
        }
    }
}
$cpuSerial = shell_exec('cat /proc/cpuinfo | grep Serial | awk \'{print $3}\'');

?>       
                <div class="activity-item d-flex">
                  <div class="activite-label text-success">Seri CPU:</div>
               
                  <div class="activity-content text-danger">
                   <?php echo $cpuSerial; ?>
                  </div>
                </div>
              </div>
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

<?php
include 'html_js.php';
?>

</body>
</html>