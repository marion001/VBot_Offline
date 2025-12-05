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

$FILE = $VBot_Offline.'resource/SYS_CMD.json';
$alert_msg = "";
$rows = [];

function alert($type, $msg) {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $msg . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_syscmd"])) {
    if (!file_exists($FILE)) {
        $alert_msg = alert("danger", "<i class='bi bi-x-l'></i> Lỗi File không tồn tại: $FILE");
    } else {
        $json = file_get_contents($FILE);
        $data = json_decode($json, true);
        if (!isset($data["voice_command_system_control"])) {
            $alert_msg = alert("danger", "<i class='bi bi-x-l'></i> Lỗi JSON không đúng định dạng.");
        } else {
            foreach ($data["voice_command_system_control"] as $index => $item) {
                $key = $item["value_key"];
                $data["voice_command_system_control"][$index]["active"] = isset($_POST["active_" . $key]) ? true : false;
                if (isset($_POST["cmd_" . $key])) {
                    $cmd = trim($_POST["cmd_" . $key]);
                    $data["voice_command_system_control"][$index]["command_when_speaking"] = $cmd;
                }
            }
            file_put_contents($FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $alert_msg = alert("success", "<i class='bi bi-check2-circle'></i> <strong>Đã lưu dữ liệu thành công!</strong>");
        }
    }
}

if (!file_exists($FILE)) {
    $alert_msg = alert("danger", "<i class='bi bi-x-l'></i> Lỗi File không tồn tại: $FILE");
} else {
    $json = file_get_contents($FILE);
    $data = json_decode($json, true);
    if (!isset($data["voice_command_system_control"])) {
        $alert_msg = alert("danger", "<i class='bi bi-x-lg'></i> Lỗi JSON không đúng định dạng.");
    } else {
        $rows = $data["voice_command_system_control"];
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
      <h1>Lệnh Điều Khiển Hệ Thống</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item">Câu Lệnh Điều Khiển Hệ Thống SYSTEM</li>
		  &nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['voice_command_system']['active'] ? '<p class="text-success" title="Câu Lệnh Điều Khiển Hệ Thống Đang Được Kích Hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Câu Lệnh Điều Khiển Hệ Thống Không Được Kích Hoạt">&nbsp;Đang Tắt</p>'; ?>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
      <div class="row">
<?php echo $alert_msg; ?>
<form method="post">
    <table class="table table-bordered border-primary">
        <thead>
            <tr>
                <!-- <th>Value Key</th> -->
                <th style="text-align:center">Tên Tác Vụ</th>
                <th style="text-align:center">Câu Lệnh</th>
                <th style="text-align:center">Kích Hoạt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $item): ?>
                <?php $key = htmlspecialchars($item["value_key"]); ?>
                <tr>
                    <!-- <td class="text-danger"><?php //echo $key; ?></td> -->
                    <th style="text-align:center" class="text-success"><?php echo htmlspecialchars($item["name"]); ?></th>
                    <td>
                        <input class="form-control border-success" type="text" name="cmd_<?php echo $key; ?>" class="form-control" value="<?php echo htmlspecialchars($item["command_when_speaking"]); ?>">
                    </td>
                    <td style="text-align:center">
					<div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="active_<?php echo $key; ?>" <?php echo $item["active"] ? "checked" : ""; ?>>
						</div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<div class="row mb-3">
	<label for="custom_home_assistant_config_path" class="col-sm-3 col-form-label"><b>Đường Dẫn/Path File Cấu Hình:</b></label>
	<div class="col-sm-9">
		<input disabled class="form-control border-danger" type="text" name="Sys_CMD_path" id="Sys_CMD_path" value="<?php echo $VBot_Offline.'resource/SYS_CMD.json' ?>">
	</div>
</div>
<div class="alert alert-primary" role="alert">
Để Bật Tắt Sử Dụng Chức Năng Này Hãy Đi Tới: <b>Cấu Hình Config</b> -> <b>Lệnh Điều Khiển Hệ Thống SYSTEM</b> -> <b>Kích Hoạt</b>
</div>
    <center>
    <button type="submit" name="save_syscmd" class="btn btn-primary">
        <i class="bi bi-save"></i> Lưu Cài Đặt
    </button>
	</center>
</form>
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