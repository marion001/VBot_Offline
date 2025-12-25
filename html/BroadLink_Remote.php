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

// Mảng lưu thông báo
$errorMessages = [];
$successMessage = [];

$broadlink_json_file = $VBot_Offline.$Config['broadlink']['json_file'];
if (!file_exists($broadlink_json_file)) {
    file_put_contents($broadlink_json_file, "{}");
    shell_exec('chmod 0777 ' . escapeshellarg($broadlink_json_file));
}

$json_data = file_get_contents($broadlink_json_file);
$data = json_decode($json_data, true);
$broadlink_devices = $data['devices_remote'] ?? [];

#Backup cấu hình dữ liệu Broadlink
function backupBroadlinkJson($Config, $broadlink_json_file){
	$backup_dir  = $Config['web_interface']['path'].'/'.$Config['broadlink']['backup_path'];
	if (file_exists($broadlink_json_file)) {
		if (!is_dir($backup_dir)) {
			mkdir($backup_dir, 0777, true);
			shell_exec('chmod 0777 ' . escapeshellarg($backup_dir));
		}
		$files = glob($backup_dir . '/broadlink_*.json');
		if (count($files) >= 7) {
			usort($files, function ($a, $b) {
				return filemtime($a) - filemtime($b);
			});
			unlink($files[0]);
		}
		$backup_file = $backup_dir.'/broadlink_'.date('dmY_His').'.json';
		$cmd = 'cp ' . escapeshellarg($broadlink_json_file) . ' ' . escapeshellarg($backup_file);
		shell_exec($cmd);
		shell_exec('chmod 0777 ' . escapeshellarg($backup_file));
	}
}

//Khôi Phục Dữ liệu bằng tải lên hoặc tệp hệ thống
if (isset($_POST['start_recovery_broadlink'])) {
	$data_recovery_type = $_POST['start_recovery_broadlink'];
	if ($data_recovery_type === "khoi_phuc_tu_tep_tai_len") {
		$uploadOk = 1;
		if (
			!isset($_FILES["fileToUpload_broadlink"]) ||
			$_FILES["fileToUpload_broadlink"]["error"] === UPLOAD_ERR_NO_FILE ||
			empty($_FILES["fileToUpload_broadlink"]["name"])
		) {
			$errorMessages[] = "- Tệp chưa được chọn để tải lên khôi phục dữ liệu";
			$uploadOk = 0;
		}
		if ($uploadOk === 1) {
			$fileName = basename($_FILES["fileToUpload_broadlink"]["name"]);
			if (!preg_match('/\.json$/i', $fileName)) {
				$errorMessages[] = "- Chỉ chấp nhận tệp .json, dành cho broadlink.json";
				$uploadOk = 0;
			}
		}
		if ($uploadOk === 1) {
			$jsonContent = file_get_contents($_FILES["fileToUpload_broadlink"]["tmp_name"]);
			$data = json_decode($jsonContent, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$errorMessages[] = "- Nội dung tệp JSON không hợp lệ";
				$uploadOk = 0;
			} else {
				$hasDevices = isset($data['devices_remote']) && is_array($data['devices_remote']);
				$hasCmds    = isset($data['cmd_devices_remote']) && is_array($data['cmd_devices_remote']);
				if (!$hasDevices && !$hasCmds) {
					$errorMessages[] = "- Tệp JSON không đúng dữ liệu Broadlink (thiếu devices_remote hoặc cmd_devices_remote)";
					$uploadOk = 0;
				}
			}
		}
		if ($uploadOk === 1) {
			#backupBroadlinkJson($Config, $broadlink_json_file);
			if (move_uploaded_file(
				$_FILES["fileToUpload_broadlink"]["tmp_name"],
				$broadlink_json_file
			)) {
				$successMessage[] = "- Tệp " . htmlspecialchars($fileName) ." đã được tải lên và khôi phục dữ liệu Broadlink thành công";
			} else {
				$errorMessages[] = "- Có lỗi xảy ra khi tải lên tệp sao lưu của bạn";
			}
		} else {
			$errorMessages[] = "- Tệp sao lưu không hợp lệ, không thể khôi phục";
		}
	}

	else if ($data_recovery_type === "khoi_phuc_file_he_thong") {
		$start_recovery_custom_hass = $_POST['backup_broadlink_json_files'] ?? '';
		if (!empty($start_recovery_custom_hass)) {
			if (file_exists($start_recovery_custom_hass)) {
				#backupBroadlinkJson($Config, $broadlink_json_file);
				$command = 'cp ' . escapeshellarg($start_recovery_custom_hass) . ' ' . escapeshellarg($broadlink_json_file);
				exec($command, $output, $resultCode);
				if ($resultCode === 0) {
					$successMessage[] = "Đã khôi phục dữ liệu từ tệp sao lưu trên hệ thống thành công";
				} else {
					$errorMessages[] = "Lỗi xảy ra khi khôi phục dữ liệu tệp Mã lỗi: " . $resultCode;
				}
			} else {
				$errorMessages[] = "Lỗi: Tệp " . basename($start_recovery_custom_hass) . " không tồn tại trên hệ thống";
			}
		} else {
			$errorMessages[] = "Không có tệp sao lưu nào được chọn để khôi phục!";
		}
	}
}

?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
    <style>
        .scroll-btn {
            position: fixed;
            right: 5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            text-align: center;
            line-height: 40px;
            font-size: 24px;
            z-index: 4;
        }

        .scroll-to-bottom {
            bottom: 15px;
        }

        .scroll-to-top {
            bottom: 60px;
        }
    </style>
    <link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css?v=<?php echo $Cache_UI_Ver; ?>">
    <style>
        #modal_dialog_show_Home_Assistant {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            max-width: calc(100vw - 40px);
        }

        #modal_dialog_show_Home_Assistant .modal-content {
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
    </style>
</head>
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
      <h1>Điều Khiển Thiết Bị IR/RF BroadLink</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item active">BroadLink Remote</li>
		  &nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['broadlink']['remote']['active'] ? '<p class="text-success" title="Developers Customization đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Developers Customization không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
      <div class="row">
<?php
// Hiển thị thông báo lỗi nếu có
if (!empty($errorMessages)) {
	echo '<div class="alert alert-danger alert-dismissible fade show" id="message_error" role="alert">';
	echo '<ul style="color: red;">';
	foreach ($errorMessages as $errorMessage) {
		echo '<li>' . $errorMessage . '</li>';
	}
	echo '</ul>';
	echo '</div>';
}
// Hiển thị thông báo thành công nếu có
if (!empty($successMessage)) {
	echo '<div class="alert alert-success alert-dismissible fade show" id="message_error" role="alert">';
	echo '<ul style="color: green;">';
	foreach ($successMessage as $successMessagegg) {
		echo '<li>' . $successMessagegg . '</li>';
	}
	echo '</ul>';
	echo '</div>';
}
?>
<table id="deviceTable" class="table table-bordered border-primary">
    <thead>
        <tr>
            <th style="text-align: center; vertical-align: middle;" colspan="7">
<button type="button" title="Tìm kiếm thiết bị Broadlink trong mạng nội bộ" class="btn btn-success rounded-pill" onclick="scanBroadlinkDevices()"><i class="bi bi-radar"></i> Quét Thiết Bị BroadLink IR/RF</button>
<button type="button" class="btn btn-danger rounded-pill" onclick="deleteAllDevicesRemote()" title="Xóa Toàn Bộ Thiết Bị Broadlink Remote"><i class="bi bi-trash"></i> Xóa Toàn Bộ Thiết Bị</button>
			</th>
        </tr>
        <tr>
            <th class="text-success" style="text-align: center; vertical-align: middle;" colspan="7">Danh Sách Thiết Bị BroadLink Remote</th>
        </tr>
        <tr>
            <th style="text-align: center; vertical-align: middle;">#</th>
            <th style="text-align: center; vertical-align: middle;">Tên Thân Thiện <i class="bi bi-question-circle-fill" onclick="show_message('Tên Định Danh Cho Thiết Bị Này (Do Bạn Đặt Cho Dễ Nhớ)')"></i></th>
            <th style="text-align: center; vertical-align: middle;">Mã Kiểu Loại</th>
            <th style="text-align: center; vertical-align: middle;">Địa Chỉ IP</th>
            <th style="text-align: center; vertical-align: middle;">Địa Chỉ MAC</th>
            <th style="text-align: center; vertical-align: middle;">Học Lệnh <i class="bi bi-question-circle-fill" onclick="show_message('Cách Học Lệnh IR:<br/> - Khi đèn trên thiết bị Broadlink sáng, vui lòng nhấn nhả nút trên Remote hướng vào thiết bị để học lệnh<hr/>Cách Học Lệnh RF (2 Bước):<br/> - Bước 1: Khi đèn trên thiết bị Broadlink sáng nhấn giữ nút trên remote khoảng 2-3 giây rồi nhả ra. <br/><br/>- Bước 2: Đèn trên thiết bị sẽ tắt rồi sáng lại hãy nhấn nhả nút trên remote 1 lần là xong<hr/>Lưu Ý: Học Lệnh RF đôi chút có thể bị nhiễu tín hiệu vô tuyến nhận mã lệnh sai, Bạn cần Test Lệnh Trước khi Lưu, nếu không đúng lệnh bạn cần thao tác học lại lệnh nhé')"></i></th>
            <th style="text-align: center; vertical-align: middle;">Hành Động</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
<hr/ class="text-primary">
<!-- Bảng Lệnh Đã Học -->
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered border-primary datatable_broadlink" id="cmdTable">
        <thead>
          <tr>
            <th style="text-align: center; vertical-align: middle;" colspan="8">
	<button type="button" class="btn btn-info rounded-pill" onclick="loadLearnedCommandsEditable()"><i class="bi bi-arrow-clockwise"></i> Tải Lại Danh Sách</button>
	<button type="button" class="btn btn-danger rounded-pill" onclick="deleteAllCmdDevicesRemote()" title="Xóa Toàn Bộ Lệnh Đã Học"><i class="bi bi-trash"></i> Xóa Toàn Bộ Lệnh</button></center>
			</th>
          </tr>
		   <tr>
		   <th style="text-align: center; vertical-align: middle;" colspan="8" class="text-success">Danh sách lệnh đã học</th>
		    </tr>
          <tr>
            <th style="text-align: center; vertical-align: middle;">#</th>
            <th style="text-align: center; vertical-align: middle;">Tên Câu Lệnh <i class="bi bi-question-circle-fill" onclick="show_message('Đặt Tên Câu Lệnh Để Thực Thi, Kích Hoạt Mã Lệnh Này')"></i></th>
            <th style="text-align: center; vertical-align: middle;">Câu Phản Hồi <i class="bi bi-question-circle-fill" onclick="show_message('Câu Phản Hồi Này Sẽ Phát Ra Loa Khi Mã Lệnh Được Thực Thi Xong')"></i></th>
            <th style="text-align: center; vertical-align: middle;">Thiết Bị Thực Thi <i class="bi bi-question-circle-fill" onclick="show_message('Thiết bị nào không hỗ trợ lệnh RF sẽ không chọn được')"></i></th>
            <th style="text-align: center; vertical-align: middle;">Kích Hoạt</th>
            <th style="text-align: center; vertical-align: middle;">Kiểu Dữ Liệu</th>
            <th style="text-align: center; vertical-align: middle;">Mã Lệnh</th>
            <th style="text-align: center; vertical-align: middle;">Hành Động</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="7" class="text-center text-muted"><button type="button" class="btn btn-primary" onclick="loadLearnedCommandsEditable()">Nhấn Để Tải Dữ Liệu</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

<h5 class="card-title">
<font color="green">Dữ Liệu Cấu Hình:</font>
</h5>
                    <div class="row mb-3">
                        <label for="broadlink_json_file" class="col-sm-3 col-form-label"><b>Đường Dẫn/Path File Cấu Hình:</b></label>
                        <div class="col-sm-9">
						<div class="input-group">
                            <input disabled class="form-control border-danger" type="text" name="broadlink_json_file" id="broadlink_json_file" value="<?php echo $VBot_Offline . $Config['broadlink']['json_file']; ?>">
<button type="button" class="btn btn-success border-danger" title="Xem dữ liệu Đã cấu hình VBot Broadlink" id="openModalBtn_Home_Assistant"><i class="bi bi-eye"></i></button>
<button type="button" class="btn btn-info border-danger" title="Tải Xuống file: <?php echo $broadlink_json_file; ?>" onclick="downloadFile('<?php echo $broadlink_json_file; ?>')"><i class="bi bi-download"></i></button>
                        </div>
                        </div>
                    </div>
<form class="row g-3 needs-validation" novalidate method="POST" enctype="multipart/form-data" action="">
<h5 class="card-title">
	<font color="green">Khôi Phục Dữ Liệu:</font>
</h5>
<div class="row mb-3">
	<label class="col-sm-3 col-form-label"><b>Tải Lên Tệp Và Khôi Phục:</b></label>
	<div class="col-sm-9">
		<div class="input-group">
			<input class="form-control border-success" type="file" name="fileToUpload_broadlink" accept=".json">
			<button class="btn btn-warning border-success" type="submit" name="start_recovery_broadlink" value="khoi_phuc_tu_tep_tai_len" onclick="return confirmRestore('Bạn có chắc chắn muốn tải lên tệp để khôi phục dữ liệu broadlink.json không?')">Tải Lên & Khôi Phục</button>
		</div>
	</div>
</div>

          <div class="row mb-3">
            <label class="col-sm-3 col-form-label"><b>Hoặc Chọn Tệp Khôi Phục:</b></label>
            <div class="col-sm-9">
              <?php
              $jsonFiles = glob($Config['broadlink']['backup_path'] . '/*.json');
              $co_tep_BackUp_customhass = true;
              if (empty($jsonFiles)) {
                $co_tep_BackUp_customhass = false;
                echo '<select class="form-select border-primary" name="backup_broadlink_json_files" id="backup_broadlink_json_files">';
                echo '<option selected value="">Không có tệp khôi phục dữ liệu Config nào</option>';
                echo '</select>';
              } else {
                $co_tep_BackUp_customhass = true;
                echo '<div class="input-group"><select class="form-select border-primary" name="backup_broadlink_json_files" id="backup_broadlink_json_files">';
                echo '<option selected value="">Chọn Tệp Khôi Phục Dữ Liệu Broadlink</option>';
                foreach ($jsonFiles as $file) {
                  $fileName = basename($file);
                  echo '<option value="' . htmlspecialchars($Config['broadlink']['backup_path'] . '/' . $fileName) . '">' . htmlspecialchars($fileName) . '</option>';
                }
                echo '</select>
                  <button class="btn btn-warning border-primary" type="submit" name="start_recovery_broadlink" value="khoi_phuc_file_he_thong">Khôi Phục</button>
                  <button type="button" class="btn btn-info border-primary" title="Tải Xuống Tệp Sao Lưu Custom Home Assistant" onclick="dowlaod_file_backup_scheduler(\'get_value_backup_config\')"><i class="bi bi-download"></i></button>
                  <button type="button" class="btn btn-success border-primary" title="Xem Tệp Sao Lưu Custom Home Assistant" onclick="readJSON_file_path(\'get_value_backup_config\')"><i class="bi bi-eye"></i></button>
                  <button type="button" class="btn btn-danger border-primary" title="Xóa Tệp Sao Lưu Custom Home Assistant" onclick="delete_file_backup_scheduler(\'get_value_backup_config\')"><i class="bi bi-trash"></i></button>
                  </div>';
              }
              ?>
            </div>
          </div>

</form>
<div class="alert alert-primary" role="alert">
Để Bật Tắt Sử Dụng Chức Năng Này Hãy Đi Tới: <b>Cấu Hình Config</b> -> <b>Liên Kết Broadlink Control, Remote Send IR/RF</b> -> <b>Kích Hoạt</b>
</div>
<!-- Button trigger modal -->
<!--
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal_learn_commands">Mở Modal Học Lệnh Thiết Bị</button>
-->
<!-- Modal Học Lệnh -->
<div class="modal fade" id="exampleModal_learn_commands" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabellearn_commands" aria-hidden="true">

  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabellearn_commands">Học Lệnh Thiết Bị: </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Thông báo trạng thái -->
        <div class="mb-2 text-danger" id="learn_status"><center class="text-danger">Học Lệnh Broadlink</center></div>

<div class="form-floating mb-3">
<textarea id="learned_command_data" class="form-control border-success" rows="6" readonly style="height: 100px;"></textarea>
<label for="learned_command_data">Dữ Liệu Đã Học Lệnh Sẽ Hiển Thị Ở Đây</label>
      </div>
    <!-- JS sẽ đẩy input + select vào đây -->
    <div id="learn_command_extra_fields"></div>
      </div>
      <div class="modal-footer" id="learn_modal_footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Đóng</button>
      </div>
    </div>
  </div>
</div>
<!-- end modal học lệnh -->

    <!-- Modal hiển thị tệp Config.json -->
    <div class="modal fade" id="myModal_Home_Assistant" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
        <div class="modal-dialog" id="modal_dialog_show_Home_Assistant" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <b>
                        <font color=blue>
                            <div id="name_file_showzz"></div>
                        </font>
                    </b>
                    <button type="button" class="close btn btn-danger" data-dismiss="modal_Config" aria-label="Close" onclick="$('#myModal_Home_Assistant').modal('hide');">
                        <i class="bi bi-x-circle-fill"></i> Đóng
                    </button>
                </div>
                <div class="modal-body">
                    <p id="message_LoadConfigJson"></p>
                    <pre id="data" class="json"><code id="code_config" class="language-json"></code></pre>
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
    <script src="assets/vendor/prism/prism.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <script src="assets/vendor/prism/prism-json.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <?php
  include 'html_js.php';
  ?>

<script>
var broadlinkDevices = [];
var currentLearnDeviceMac = null;

//Mở Modal Học Lệnh
function openLearnCommandModal() {bootstrap.Modal.getOrCreateInstance(document.getElementById("exampleModal_learn_commands")).show();
}

//Đóng Modal Học Lệnh
function closeLearnCommandModal() {bootstrap.Modal.getOrCreateInstance(document.getElementById("exampleModal_learn_commands")).hide();
}

//Hiển Thị, Tạo Nút Lưu Lệnh Đã Học
function showSaveLearnCommandButton(ip, mac, devtype, wave_type) {
    const footer = document.getElementById("learn_modal_footer");
    if (!footer) {
		show_message('Lỗi không tìm thấy id: learn_modal_footer');
        return;
    }
    if (document.getElementById("btn_save_learned_command")) return;
    footer.insertAdjacentHTML("afterbegin", 
	'<div class="d-flex gap-2 align-items-center"><button type="button" id="btn_test_learned_command" class="btn btn-primary" onclick="test_cmd_learn(\''+ip+'\', \''+mac+'\', \''+devtype+'\', \''+wave_type+'\')"><i class="bi bi-send-check"></i> Test Lệnh</button>' +
	'<button type="button" id="btn_re_learned_command" class="btn btn-warning" onclick="learn_Command(\''+ip+'\', \''+mac+'\', \''+devtype+'\', \''+wave_type+'\', \'\', \'\')"><i class="bi bi-arrow-counterclockwise"></i> Học Lại</button>'+
	'<button type="button" id="btn_save_learned_command" class="btn btn-success" onclick="saveLearnedCommandToJson(\''+wave_type+'\')"><i class="bi bi-floppy"></i> Lưu lệnh</button></div>');
}

//Thẻ Select Và Input khi học xong lệnh
function showLearnCommandExtraFields() {
    const box = document.getElementById("learn_command_extra_fields");
    if (!box) return;
    if (document.getElementById("learn_command_name")) return;
	box.innerHTML =
		'<div class="form-floating mb-3">' +
			'<input type="text" id="learn_command_name" class="form-control border-success">' +
			'<label for="learn_command_name">Đặt tên câu lệnh này:</label>' +
		'</div>' +
		'<div class="form-floating mb-3">' +
			'<input type="text" id="learn_command_reply" class="form-control border-success">' +
			'<label for="learn_command_reply">Văn Bản, Câu Phản Hồi Tùy Chỉnh</label>' +
		'</div>' +
		'<div class="form-floating mb-3">' +
			'<select id="learn_command_device" class="form-select border-success"></select>' +
			'<label class="form-label">Chọn thiết bị thực thi lệnh:</label>' +
		'</div>';
}

//Scan Thiết Bị Broadlink
function scanBroadlinkDevices() {
	loading('show');
    var url = 'includes/php_ajax/BroadLink.php?scan_broadlink_remote_device';
    showMessagePHP('Đang quét thiết bị Broadlink Remote trong mạng...', 3);
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status !== 200) {
				loading('hide');
                show_message('Lỗi HTTP khi scan: ' + xhr.status);
                return;
            }
            try {
				loading('hide');
                var res = JSON.parse(xhr.responseText);
                if (!res.success) {
                    show_message(res.message || 'Quét thiết bị thất bại');
                    return;
                }
                showMessagePHP(res.message, 5);
                loadBroadlinkDevices();
            } catch (e) {
				loading('hide');
                show_message('Lỗi khi quét thiết bị trong mạng: ' +e+', ' +xhr.responseText);
            }
        }
    };
    xhr.send();
}

//KIỂM TRA THIẾT BỊ CÓ RF HAY KHÔNG
function broadlinkHasRF(devtype) {
    if (!devtype) return false;
    var dt = devtype.toString().toLowerCase();
    var rfTypes = [
        '0x2737', // RM Pro
        '0x279d', // RM Pro+
        '0x27c2', // RM4 Pro
        '0x27bb'  // RM4 Pro (variant)
    ];
    return rfTypes.includes(dt);
}

// TẢI DANH SÁCH THIẾT BỊ BROADLINK
function loadBroadlinkDevices() {
    loading('show');
    var url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $broadlink_json_file; ?>';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        loading('hide');
        if (xhr.status !== 200) {
            show_message('Lỗi HTTP: ' + xhr.status);
            return;
        }
        try {
            var res = JSON.parse(xhr.responseText);
            if (!res.success) {
                show_message('Server trả về lỗi: ' + res.message);
                return;
            }
            if (!res.data || !res.data.devices_remote || res.data.devices_remote.length === 0) {
                var tbodyEmpty = document.querySelector('#deviceTable tbody');
                if (tbodyEmpty) {
                    tbodyEmpty.innerHTML =
                        '<tr><td colspan="9" class="text-center text-danger">' +
                        '<b>Không có dữ liệu thiết bị Broadlink Remote</b></td></tr>';
                }
                return;
            }
            var devices = res.data.devices_remote;
            broadlinkDevices = devices;
            var tbody = document.querySelector('#deviceTable tbody');
            if (!tbody) {
                showMessagePHP('Không tìm thấy #deviceTable tbody', 5);
                return;
            }
            tbody.innerHTML = '';
            for (var i = 0; i < devices.length; i++) {
                var dev = devices[i];
                var tr = document.createElement('tr');
                //CỘT HỌC LỆNH
                var learnButtons =
                    '<button type="button" class="btn btn-primary me-1" ' +
                    'title="Học Lệnh IR từ: ' + dev.friendly_name + '" ' +
                    'onclick="learn_Command(\'' + dev.ip + '\', \'' + dev.mac + '\', \'' + dev.devtype + '\', \'ir\', \'' + dev.friendly_name + '\', \'' + dev.model + '\')">' +
                    '<i class="bi bi-broadcast"></i> IR</button>';
                if (broadlinkHasRF(dev.devtype)) {
                    learnButtons +=
                        '<button type="button" class="btn btn-warning me-1" ' +
                        'title="Học Lệnh RF từ: ' + dev.friendly_name + '" ' +
                        'onclick="learn_Command(\'' + dev.ip + '\', \'' + dev.mac + '\', \'' + dev.devtype + '\', \'rf\', \'' + dev.friendly_name + '\', \'' + dev.model + '\')">' +
                        '<i class="bi bi-broadcast-pin"></i> RF</button>';
                }
                //CỘT HÀNH ĐỘNG
                var actionButtons =
                    '<button type="button" class="btn btn-success me-1" title="Đổi Tên Thiết Bị" onclick="renameDevice(\'' + dev.mac + '\')"><i class="bi bi-pencil-square"></i></button>' +
                    '<button type="button" class="btn btn-danger" ' +
                    'title="Xóa thiết bị: ' + dev.friendly_name + '" ' +
                    'onclick="deleteDeviceByMac(\'' + dev.friendly_name + '\', \'' + dev.mac + '\', \'' + dev.ip + '\', \'' + dev.model + '\')">' +
                    '<i class="bi bi-trash"></i></button>';
                tr.innerHTML =
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td style="text-align: center; vertical-align: middle;">' + dev.friendly_name + '</td>' +
                    '<td style="text-align: center; vertical-align: middle;">' + dev.model + '</td>' +
                    '<td style="text-align: center; vertical-align: middle;">' + dev.ip + '</td>' +
                    '<td style="text-align: center; vertical-align: middle;">' + dev.mac + '</td>' +
                    '<td class="text-center">' + learnButtons + '</td>' +
                    '<td class="text-center">' + actionButtons + '</td>';
                tbody.appendChild(tr);
            }
        } catch (e) {
            show_message('Lỗi giải mã JSON: ' + e + '<br>' + xhr.responseText);
        }
    };
    xhr.send();
}

//Tải dữ liệu các lệnh đã học
function loadLearnedCommandsEditable() {
    loading('show');
    var url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $broadlink_json_file; ?>';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        loading('hide');
        if (xhr.status !== 200) {
            show_message("Lỗi HTTP: " + xhr.status);
            return;
        }
        var res;
        try {
            res = JSON.parse(xhr.responseText);
        } catch (e) {
            show_message("JSON lỗi: " + e);
            return;
        }
        var tbody = document.querySelector('#cmdTable tbody');
        tbody.innerHTML = '';
        if (!res.success || !res.data || !res.data.cmd_devices_remote) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger"><b>Chưa có dữ liệu các mã lệnh đã học</b></td></tr>';
            return;
        }
        var cmds = res.data.cmd_devices_remote;
        if (Object.keys(cmds).length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger"><b>Chưa có dữ liệu các mã lệnh đã học</b></td></tr>';
            return;
        }
        var devices = res.data.devices_remote || [];
        var rowNum = 1;
        for (var mac in cmds) {
            cmds[mac].forEach(function(cmd, index) {
                var tr = document.createElement('tr');
                tr.dataset.cmdMac = mac;
                tr.dataset.cmdIndex = index;
                //Kiểm tra wave
                var hasWaveKey = cmd.hasOwnProperty('wave');
                var waveValue = (hasWaveKey && cmd.wave) ? cmd.wave : null;
                var waveText = (!waveValue || waveValue.trim() === '') ? 'N/A' : waveValue.toUpperCase();
                var waveClass = (!waveValue || waveValue.trim() === '') ? 'text-muted' : waveValue === 'ir' ? 'text-success' : waveValue === 'rf' ? 'text-danger' : '';
                var isRFCommand = hasWaveKey && waveValue === 'rf';
                //select device
                var options = '';
                var macUpper = mac.toUpperCase();
                var deviceExists = devices.some(function(dev) {
                    return dev.mac.toUpperCase() === macUpper;
                });
                if (!deviceExists) {
                    options += '<option value="" selected class="text-danger">⚠ Không có thiết bị tương ứng (Hoặc đã bị xóa)</option>';
                }
                devices.forEach(function(dev) {
                    var isSelected = deviceExists && dev.mac.toUpperCase() === macUpper;
                    var devHasRF = broadlinkHasRF(dev.devtype);
                    var disabledAttr = (isRFCommand && !devHasRF) ? 'disabled' : '';
                    //var rfNote = (isRFCommand && !devHasRF) ? ' ❌ (Không hỗ trợ RF)' : '';
                    var rfNote = (isRFCommand && !devHasRF) ? '' : '';
                    options += '<option value="' + dev.mac + '" ' + 'data-devtype="' + dev.devtype + '" ' + (isSelected ? 'selected' : '') + ' ' + disabledAttr + '>' +dev.friendly_name + ' (' + dev.ip + ' - ' + dev.mac + ')' +rfNote +'</option>';
                });
                //innerHTML
                tr.innerHTML =
                    '<td class="text-center">' + (rowNum++) + '</td>' +
                    '<td><textarea class="form-control border-success cmd_name">' + (cmd.name || '') + '</textarea></td>' +
                    '<td><textarea class="form-control border-success cmd_reply">' + (cmd.reply || '') + '</textarea></td>' +
                    '<td><select class="form-select border-success cmd_device_select">' + options + '</select></td>' +
                    '<td class="text-center">' +
                        '<div class="form-switch">' +
                            '<input type="checkbox" class="cmd_active form-check-input border-success" ' + (cmd.active ? 'checked' : '') + '>' +
                        '</div>' +
                    '</td>' +
                    '<td style="text-align: center;">' +
                        '<b class="' + waveClass + '">' + waveText + '</b>' +
                    '</td>' +
                    '<td><textarea class="form-control border-success cmd_data" rows="2">' + (cmd.data || '') + '</textarea></td>' +
                    '<td style="text-align: center; vertical-align: middle;">' +
                        '<button class="btn btn-success" onclick="saveLearnedCommandRow(this.closest(\'tr\'))"><i class="bi bi-save2"></i> Lưu</button> ' +
                        '<button class="btn btn-primary" onclick="sendLearnedCommandRow(this.closest(\'tr\'))" title="Gửi Lệnh"><i class="bi bi-send-check"></i></button> ' +
                        '<button class="btn btn-danger" onclick="deleteLearnedCommandRow(this.closest(\'tr\'))"><i class="bi bi-trash"></i></button>' +
                    '</td>';
                tbody.appendChild(tr);
            });
        }
    };
    xhr.send();
}

//Xóa Thiết Bị Devices
function deleteDeviceByMac(friendly_name, mac, ip, model) {
    if (!confirm('Bạn có chắc muốn xóa thiết bị: "'+friendly_name+'"\n - Tên Thiết Bị: '+model+'\n - Địa Chỉ MAC: '+mac+'\n - Địa Chỉ IP: '+ip)) return;
	loading('show');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/BroadLink.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status !== 200) {
				loading('hide');
                show_message('HTTP lỗi: ' + xhr.status);
                return;
            }
            try {
				loading('hide');
                var res = JSON.parse(xhr.responseText);
                if (!res.success) {
                    show_message('Xóa thất bại: ' + res.message);
                    return;
                }
                showMessagePHP(res.message, 5);
                loadBroadlinkDevices();
            } catch (e) {
				loading('hide');
                show_message('JSON trả về không hợp lệ: ' +e+ ', ' +xhr.responseText);
            }
        }
    };
    xhr.send('delete_device_broadlink_remote=1'+'&mac=' + encodeURIComponent(mac));
}

//Đổi Tên Thiết Bị
function renameDevice(mac) {
    var newFriendly = prompt('Đổi Tên Thân Thiện\n - Nhập tên mới cho thiết bị:');
    if (!newFriendly) return;
	loading('show');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/BroadLink.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status !== 200) {
				loading('hide');
                show_message('Lỗi HTTP: ' + xhr.status);
                return;
            }
            try {
				loading('hide');
                var res = JSON.parse(xhr.responseText);
                if (!res.success) {
                    showMessagePHP(res.message, 5);
                    return;
                }
                showMessagePHP('Đổi tên thiết bị thành công', 5);
                loadBroadlinkDevices();
            } catch (e) {
				loading('hide');
                show_message('Lỗi xử lý dữ liệu: ' +e +', ' +xhr.responseText);
            }
        }
    };
    var params = 'rename_device_broadlink_remote=1' + '&mac=' + encodeURIComponent(mac) + '&friendly=' + encodeURIComponent(newFriendly);
    xhr.send(params);
}

//Học Lệnh
function learn_Command(ip, mac, devtype, wave_type, friendly_name, model) {
    if (!ip || !mac || !devtype) {
        show_message('Lỗi Xảy Ra, Thiếu tham số thiết bị: ip, mac, devtype');
        return;
    }
	if (friendly_name !== '') {
		if (!confirm('Bạn có chắc muốn học lệnh từ thiết bị: "'+friendly_name+'" không?')) {
			return;
		}
	} else {
		if (!confirm('Bạn có chắc chắn muốn học lại lệnh này không?')) {
			return;
		}
		}
	loading('show');
	if (wave_type === "rf") {
		document.getElementById("learn_status").innerHTML = "<center>Học Lệnh RF (2 Bước)</center><br/>- Bước 1: Khi đèn trên thiết bị Broadlink sáng nhấn giữ nút trên remote khoảng 2-3 giây rồi nhả ra.<br/><br/>- Bước 2: Đèn trên thiết bị sẽ tắt rồi sáng lại hãy nhấn nhả nút trên remote 1 lần là xong<hr/>Lưu Ý: Học Lệnh RF đôi chút có thể bị nhiễu tín hiệu vô tuyến nhận mã lệnh sai, Bạn cần Test Lệnh Trước khi Lưu, nếu không đúng lệnh bạn cần thao tác học lại lệnh nhé";
	} else if (wave_type === "ir") {
		document.getElementById("learn_status").innerHTML = "<center>Học Lệnh IR</center><br/>Khi đèn trên thiết bị Broadlink sáng, vui lòng nhấn nhả nút trên Remote hướng vào thiết bị để học lệnh";
	}
    const nameInput = document.getElementById('learn_command_name');
	const dataTextarea = document.getElementById('learned_command_data');
    if (nameInput) nameInput.value = '';
    if (dataTextarea) dataTextarea.value = '';
	currentLearnDeviceMac = mac;
	openLearnCommandModal();
	document.getElementById("exampleModalLabellearn_commands").textContent = "Học lệnh thiết bị: " + friendly_name;
    showMessagePHP('Đang tiến hành học lệnh trên thiết bị: ' + friendly_name,5);
    const formData = new FormData();
    formData.append("learn_command_broadlink", "1");
    formData.append("ip", ip);
    formData.append("mac", mac);
    formData.append("wave_type", wave_type);
    formData.append("devtype", devtype);
    fetch("includes/php_ajax/BroadLink.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
				loading('hide');
                const textarea = document.getElementById("learned_command_data");
                if (textarea && data.data) {
                    textarea.value = data.data; // IR:xxxx hoặc RF:xxxx
					showLearnCommandExtraFields();
					fillLearnCommandDeviceSelect();
					showSaveLearnCommandButton(ip, mac, devtype, wave_type);
                }
                showMessagePHP(data.message, 5);
            } else {
				closeLearnCommandModal();
				loading('hide');
				show_message("Lỗi: " +data.message);
            }
        } catch (e) {
			closeLearnCommandModal();
			loading('hide');
            show_message("Lỗi dữ liệu trả về từ server: " +text);
        }
    })
    .catch(err => {
		closeLearnCommandModal();
		loading('hide');
        show_message("Lỗi xảy ra: " +err);
    });
}

//Thẻ Select Chọn Device thực thi lệnh
function fillLearnCommandDeviceSelect() {
    const select = document.getElementById("learn_command_device");
    if (!select) return;
    select.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
    if (!broadlinkDevices || broadlinkDevices.length === 0) return;
    broadlinkDevices.forEach(dev => {
        const selected =
            currentLearnDeviceMac &&
            dev.mac === currentLearnDeviceMac ? 'selected' : '';
			select.insertAdjacentHTML("beforeend",
				'<option value="' + dev.mac + '" ' + selected + '>' +
					dev.friendly_name + ' (' + dev.ip + ' - ' + dev.mac + ')' +
				'</option>'
			);
    });
}

//Lưu thông tin khi học xong lệnh
function saveLearnedCommandToJson(wave_type) {
    const name = document.getElementById("learn_command_name")?.value.trim();
    const reply = document.getElementById("learn_command_reply")?.value.trim();
    const mac = document.getElementById("learn_command_device")?.value;
    const data = document.getElementById("learned_command_data")?.value;
    if (!name || !mac || !data) {
        alert("Thiếu Dữ Liệu Để Lưu: Vui lòng nhập đầy đủ thông tin tên lệnh, thiết bị thực thi");
        return;
    }
    const formData = new FormData();
    formData.append("save_learned_command", "1");
    formData.append("command_name", name);
    formData.append("device_mac", mac);
    formData.append("command_data", data);
    formData.append("command_reply", reply);
    formData.append("wave_type", wave_type);
    fetch("includes/php_ajax/BroadLink.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
			loadLearnedCommandsEditable();
            alert(res.message);
			closeLearnCommandModal();
        } else {
            alert("Lỗi: " + res.message);
        }
    })
    .catch(err => {
        alert("Lỗi JS: " + err);
    });
}

//Lưu thông tin chỉnh sửa lệnh
function saveLearnedCommandRow(row) {
    if (!row) return;
    const fd = new FormData();
    fd.append('save_learned_command_edit', '1');
    fd.append('mac_old', row.dataset.cmdMac);
    fd.append('index', row.dataset.cmdIndex);
    fd.append('mac_new', row.querySelector('.cmd_device_select').value);
    fd.append('name', row.querySelector('.cmd_name').value.trim());
    fd.append('reply', row.querySelector('.cmd_reply').value.trim());
    fd.append('data', row.querySelector('.cmd_data').value.trim());
    fd.append('active', row.querySelector('.cmd_active').checked ? '1' : '0');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/BroadLink.php', true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                showMessagePHP(res.message, 5);
                loadLearnedCommandsEditable();
            } else {
                show_message(res.message);
            }
        } catch (e) {
            show_message('Lỗi phản hồi server');
        }
    };
    xhr.send(fd);
}

//Xóa lệnh tương ứng
function deleteLearnedCommandRow(row) {
    if (!row) return;
    if (!confirm('Bạn có chắc muốn xóa lệnh này không?')) return;
    const fd = new FormData();
    fd.append('delete_learned_command', '1');
    fd.append('mac', row.dataset.cmdMac);
    fd.append('index', row.dataset.cmdIndex);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/BroadLink.php', true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                showMessagePHP('Đã xóa lệnh thành công');
                loadLearnedCommandsEditable();
            } else {
                show_message('Lỗi: ' + res.message);
            }
        } catch (e) {
            show_message('Lỗi phản hồi server');
        }
    };
    xhr.send(fd);
}

//Xóa toàn bộ thiết bị device remote
function deleteAllDevicesRemote() {
    if (!confirm("Bạn có chắc muốn xóa toàn bộ thiết bị Broadlink Remote không")) return;
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "includes/php_ajax/BroadLink.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            var res = JSON.parse(xhr.responseText);
            if (res.success){
				loadBroadlinkDevices();
				show_message(res.message);
				}
			else {
				show_message(res.message);
			}
        }
    };
    xhr.send("deleteAllDevicesRemote=1");
}

//XÓa toàn bộ dữ liệu lệnh đã học
function deleteAllCmdDevicesRemote() {
    if (!confirm("Bạn có chắc muốn xóa toàn bộ dữ liệu lệnh đã học không")) return;
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "includes/php_ajax/BroadLink.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            var res = JSON.parse(xhr.responseText);
            if (res.success){
				loadLearnedCommandsEditable();
				show_message(res.message);
			}else{
				show_message(res.message);
			}
        }
    };
    xhr.send("deleteAllCmdDevicesRemote=1");
}

//lấy dữ liệu thực thi send lệnh
function sendLearnedCommandRow(tr) {
    const select = tr.querySelector('.cmd_device_select');
    if (!select || !select.selectedOptions || select.selectedOptions.length === 0) {
        show_message("Không có thiết bị được chọn để thực thi lệnh");
        return;
    }
    const code = tr.querySelector('.cmd_data').value.trim();
    if (!code || code.trim() === '') {
        show_message("Không có dữ liệu lệnh để gửi");
        return;
    }
    const mac = select.value;
    const ip = select.selectedOptions[0].text.match(/(\d+\.\d+\.\d+\.\d+)/)?.[1];
    const devtype = select.selectedOptions[0].dataset.devtype;
    if (!mac || mac === '' || !ip || !devtype) {
        show_message("Thiếu thông tin thiết bị để gửi lệnh");
        return;
    }
	showMessagePHP('Đang tiến hành gửi lệnh Remote từ thiết bị');
    sendBroadlinkCommand(ip, mac, devtype, code);
}

//Test Lệnh Code Khi Học
function test_cmd_learn(ip, mac, devtype, wave_type) {
    const textarea = document.getElementById("learned_command_data");
    const data = textarea.value;
    if (data === null || data === undefined) {
		alert("Dữ liệu lệnh không hợp lệ");
        return;
    }
    const code = data.trim();
    if (code === "") {
		alert("Không có dữ liệu Lệnh, Mã Code");
        return;
    }
    sendBroadlinkCommand(ip, mac, devtype, code);
}

//Gửi lệnh command
function sendBroadlinkCommand(ip, mac, devtype, code) {
    if (!ip || !mac || !devtype || !code) {
        show_message("Thiếu thông tin, dữ liệu để gửi lệnh Broadlink");
        return;
    }
	loading('show');
    devtype = String(devtype).trim();
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "includes/php_ajax/BroadLink.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        var res;
        try {
            res = JSON.parse(xhr.responseText);
        } catch (e) {
			loading('hide');
            show_message("Phản hồi không hợp lệ từ server");
            return;
        }
        if (res.success === true) {
			loading('hide');
			showMessagePHP(res.message || "Đã gửi lệnh thành công", 5);
        } else {
			loading('hide');
            show_message("Lỗi: " + (res.message || "Không xác định"));
        }
    };
    const params =
        "sendBroadlink=1" +
        "&ip=" + encodeURIComponent(ip) +
        "&mac=" + encodeURIComponent(mac) +
        "&devtype=" + encodeURIComponent(devtype) +
        "&code=" + encodeURIComponent(code);
    xhr.send(params);
}

//onclick xem nội dung file json
function readJSON_file_path(filePath) {
  if (filePath === "get_value_backup_config") {
	var get_value_backup_config = document.getElementById('backup_broadlink_json_files').value;
	if (get_value_backup_config === "") {
	  showMessagePHP("Không có tệp nào được chọn để xem nội dung");
	} else {
	  filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
	  read_loadFile(filePath);
	  document.getElementById('name_file_showzz').textContent = "Tên File: " + filePath.split('/').pop();
	  $('#myModal_Home_Assistant').modal('show');
	}
  } else {
	read_loadFile(filePath);
	document.getElementById('name_file_showzz').textContent = "Tên File: " + filePath.split('/').pop();
	$('#myModal_Home_Assistant').modal('show');
  }
}

//Tải xuống file backup Config
function dowlaod_file_backup_scheduler(filePath) {
  if (filePath === "get_value_backup_config") {
	var get_value_backup_config = document.getElementById('backup_broadlink_json_files').value;
	if (get_value_backup_config === "") {
	  showMessagePHP("Không có tệp nào được chọn để tải xuống");
	} else {
	  filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
	  downloadFile(filePath);
	}
  } else {
	showMessagePHP("Không có tệp nào được chọn để tải xuống.");
  }
}

//Xóa file backup Config
function delete_file_backup_scheduler(filePath) {
  if (filePath === "get_value_backup_config") {
	var get_value_backup_config = document.getElementById('backup_broadlink_json_files').value;
	if (get_value_backup_config === "") {
	  showMessagePHP("Không có tệp nào được chọn để tải xuống");
	} else {
	  filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
	  deleteFile(filePath);
	}
  } else {
	showMessagePHP("Không có tệp nào được chọn để tải xuống.");
  }
}

// Hiển thị modal xem nội dung file json Home_Assistant.json
['openModalBtn_Home_Assistant'].forEach(function(id) {
	document.getElementById(id).addEventListener('click', function() {
		var file_name_hassJSON = "<?php echo $broadlink_json_file; ?>";
		read_loadFile(file_name_hassJSON);
		document.getElementById('name_file_showzz').textContent = "Tên File: " + file_name_hassJSON.split('/').pop();
		$('#myModal_Home_Assistant').modal('show');
	});
});

document.addEventListener('DOMContentLoaded', function () {
    loadBroadlinkDevices();
	loadLearnedCommandsEditable();
});
</script>
</body>

</html>