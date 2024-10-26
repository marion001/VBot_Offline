<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
$output_notify = ''; // Biến tạm để lưu nội dung
$i_count = 0; // Khai báo biến toàn cục để đếm

function checkPermissions($dir) {
    global $output_notify, $i_count, $excluded_items_chmod; // Khai báo biến toàn cục để lưu nội dung
    $items = scandir($dir); // Mở thư mục để duyệt qua các file và thư mục con
    foreach ($items as $item) {
        //if ($item == '.' || $item == '..') continue;
		if (in_array($item, $excluded_items_chmod)) continue;
        $path = $dir . '/' . $item;
        $permissions = substr(sprintf('%o', fileperms($path)), -3); // Lấy quyền hiện tại
        // Kiểm tra quyền có phải là 777 hay không
        if ($permissions != '777') {
			$i_count++;
            $output_notify .= '<li>
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
// Gọi hàm kiểm tra quyền với thư mục WEbUI
checkPermissions($directory_path.'/');
checkPermissions($VBot_Offline);
?>

<a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Thông báo">
    <i class="bi bi-bell text-success"></i>
    <span class="badge bg-primary badge-number"><?php if ($i_count != 0) { echo $i_count; } ?></span>
</a>
<!-- End Notification Icon -->

<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" style="max-height: 400px; overflow-y: auto; width: auto;">
    <li class="dropdown-header">

        Bạn có <?php echo $i_count; ?> thông báo mới.
       <!-- <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">Xem tất cả</span></a> -->
    </li>
    <?php echo $output_notify; ?>
</ul>
<!-- End Notification Dropdown Items -->