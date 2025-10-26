<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';

$output_notify = ''; // Biến tạm để lưu nội dung
$i_count = 0; // Khai báo biến toàn cục để đếm

// Hàm sử dụng cURL để lấy nội dung từ URL
function fetchContent($url)
{
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  $response = curl_exec($curl);
  $error = curl_error($curl);
  $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  curl_close($curl);
  if ($httpCode !== 200 || $error) {
    return false;
  }
  return $response;
}

function checkPermissions($dir)
{
  global $output_notify, $i_count, $excluded_items_chmod;
  $items = scandir($dir);
  foreach ($items as $item) {
    //if ($item == '.' || $item == '..') continue;
    if (in_array($item, $excluded_items_chmod)) continue;
    $path = $dir . '/' . $item;
    $permissions = substr(sprintf('%o', fileperms($path)), -3);
    // Kiểm tra quyền có phải là 777 hay không
    if ($permissions != '777') {
      $i_count++;
      $output_notify .= '
  			<li>
                <hr class="dropdown-divider">
              </li><li class="notification-item">
                <i class="bi bi-exclamation-circle text-warning"></i>
                <div>
                  <h4>Cấp Quyền Chmod</h4>
                  <p>Một số file, thư mục chưa được cấp quyền</p>
                  <p class="text-danger" onclick="command_php(\'chmod_vbot\', true)">Cấp Quyền</p>
                </div>
              </li>';
    }
    // Nếu là thư mục, đệ quy để kiểm tra các thư mục con
    if (is_dir($path)) {
      checkPermissions($path);
    }
  }
}

$real_directory_path = realpath($directory_path . '/');
$real_base_path = realpath($VBot_Offline);
if ($real_directory_path !== false && strpos($real_directory_path, $real_base_path) === 0) {
  checkPermissions($VBot_Offline);
} else {
  checkPermissions($directory_path . '/');
  checkPermissions($VBot_Offline);
}

?>
<a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Thông báo">
  <i class="bi bi-bell text-success"></i>
  <span id="number_notification" class="badge bg-primary badge-number"><?php if ($i_count != 0) {
                                                                          echo $i_count;
                                                                        } ?></span>
</a>
<!-- End Notification Icon -->
<ul id="notification" class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" style="max-height:400px; overflow-y: auto; width: auto; height: auto;">
  <li class="dropdown-header">
    <font id="number_notification_1" color=red>Bạn có <b><?php echo $i_count; ?></b> thông báo mới.</font>
  </li>
  <?php echo $output_notify; ?>
</ul>
<!-- End Notification Dropdown Items -->