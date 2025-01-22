<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
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



<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
<style>

.time-input-container {
    position: relative; 
}

/* Danh sách gợi ý */
.suggestions-list {
    position: absolute;
    top: 100%; 
    left: 0;
    background-color: white;
    border: 1px solid #ccc;
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
    white-space: nowrap;  
    width: 100%;        
    margin-top: 2px;      
    padding: 0;
    border-radius: 4px;
}

.suggestion-item {
    padding: 8px 10px;
    cursor: pointer;
    border-bottom: 1px solid #ddd;
}

.suggestion-item:last-child {
    border-bottom: none; 
}

.suggestion-item:hover {
    background-color: #f0f0f0;
}


</style>
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

<!-- <link href="assets/vendor/prism/prism.min.css" rel="stylesheet"> -->
<link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css">
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
      <h1>Lên Lịch: Lời Nhắc, Thông Báo (Scheduler) <i class="bi bi-question-circle-fill" onclick="show_message('Để Bật hoặc Tắt sử dụng cần thiết lập trong tab <b>Cấu Hình Config</b>')"></i></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">Lên lịch, tác vụ, lời nhắc, thông báo</li>
		  
		   &nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['schedule']['active'] ? '<p class="text-success" title="Schedule đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Schedule không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
       
		  
        </ol>
      </nav>
    </div><!-- End Page Title -->
	
<form method="POST" class="row g-3 needs-validation" action="" enctype="multipart/form-data" novalidate>

<?php

$json_file = $VBot_Offline.$Config['schedule']['data_json_file'];
// Mảng lưu thông báo lỗi
$errorMessages = [];
$successMessage = [];


function generate_audio_select($directory, $field_name, $selected_value = '') {
    // Kiểm tra thư mục có tồn tại không
    if (!is_dir($directory)) {
        echo "<p style='color: red;'>Thư mục không tồn tại.</p>";
        return;
    }
    // Lọc các tệp âm thanh (mp3, wav, flac, ogg, aac)
    $audioFiles = array_filter(scandir($directory), function($file) use ($directory) {
        // Kiểm tra định dạng tệp
        return preg_match('/\.(mp3|wav|flac|ogg|aac)$/i', $file) && !is_dir($directory . '/' . $file);
    });
    // Tạo phần HTML cho thẻ <select>
    echo '<select class="form-control border-success" name="' . htmlspecialchars($field_name) . '" id="' . htmlspecialchars($field_name) . '">';
    echo '<option value="">Chọn tệp âm thanh</option>';
    
    // Tạo các thẻ <option> cho từng tệp âm thanh
    foreach ($audioFiles as $audioFile) {
        $selected = ($directory.'/'.$audioFile == $selected_value) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($directory.'/'.$audioFile) . '" ' . $selected . '>' . htmlspecialchars($audioFile) . '</option>';
    }
    echo '</select>';
}


$directory = dirname($json_file);
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
	chmod($directory, 0777);
}


if (!file_exists($json_file)) {
    $default_data = [
        'notification_schedule' => []
    ];
    file_put_contents($json_file, json_encode($default_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    chmod($json_file, 0777);
}


$json_data = file_get_contents($json_file);
$data = json_decode($json_data, true);


if ($data === null) {
    echo "Không thể đọc dữ liệu JSON từ tệp, Vui lòng kiểm tra lại định dạng tệp json";
    exit();
}

// Các ngày trong tuần
$week_days = [
	"Monday" => "Thứ Hai",
	"Tuesday" => "Thứ Ba",
	"Wednesday" => "Thứ Tư",
	"Thursday" => "Thứ Năm",
	"Friday" => "Thứ Sáu",
	"Saturday" => "Thứ Bảy",
	"Sunday" => "Chủ Nhật"
];

if (isset($_POST['delete_all_Scheduler'])) {
    if (file_exists($json_file)) {
        if (unlink($json_file)) {
            $default_data = [
                'notification_schedule' => []
            ];
            if (file_put_contents($json_file, json_encode($default_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
                chmod($json_file, 0777);
                $successMessage[] = "Toàn bộ dữ liệu cấu hình đã được xóa thành công";
                // Tải lại trang bằng header
                echo '<script>window.location.href = "Scheduler.php";</script>';
                exit;
            } else {
                $errorMessage[] = "Không thể tạo lại tệp dữ liệu mặc định.";
            }
        } else {
            $errorMessage[] = "Không thể xóa tệp dữ liệu cũ.";
        }
    } else {
        $errorMessage[] = "Tệp dữ liệu không tồn tại.";
    }
				
}


if (isset($_POST['Scheduler_Upload_Audio_Submit'])) {
	
    $file_save_directory = $VBot_Offline.$Config['schedule']['audio_path'];

	// Kiểm tra và tạo thư mục, thiết lập quyền
	if (!file_exists($file_save_directory)) {
		if (!mkdir($file_save_directory, 0777, true)) {
			$errorMessages[] = 'Không thể tạo thư mục ' . $file_save_directory . '. Vui lòng kiểm tra quyền thư mục.';
		}
	}

	if (!chmod($file_save_directory, 0777)) {
		$errorMessages[] = 'Không thể thiết lập quyền cho thư mục ' . $file_save_directory . '. Vui lòng kiểm tra quyền hệ thống.';
	}

    $data_recovery_type = $_POST['Scheduler_Upload_Audio_Submit'];
    if ($data_recovery_type === "Scheduler_Upload_Audio") {
        $uploadOk = 1;
        $errorMessages = [];
        $successMessage = [];

        // Kiểm tra xem tệp có được gửi không
        if (isset($_FILES["fileToUpload_Scheduler_Upload_Audio"])) {
            $fileName = basename($_FILES["fileToUpload_Scheduler_Upload_Audio"]["name"]);
            $fileTargetPath = $file_save_directory . "/" . $fileName; // Đường dẫn đầy đủ

            // Kiểm tra định dạng tệp (chỉ chấp nhận tệp âm thanh như mp3, wav, etc.)
            if (!preg_match('/\.(mp3|wav|flac|ogg|aac)$/i', $fileName)) {
                $errorMessages[] = "- Chỉ chấp nhận các định dạng tệp âm thanh (mp3, wav, flac, ogg, aac)";
                $uploadOk = 0;
            }

            if ($uploadOk == 0) {
                $errorMessages[] = "- Tệp âm thanh không được tải lên.";
            } else {
                // Di chuyển tệp vào thư mục đích
                if (move_uploaded_file($_FILES["fileToUpload_Scheduler_Upload_Audio"]["tmp_name"], $fileTargetPath)) {
					chmod($fileTargetPath, 0777);
                    $successMessage[] = "- Tệp \"" . htmlspecialchars($fileName) . "\" đã được tải lên và lưu trữ thành công.";
                } else {
                    $errorMessages[] = "- Có lỗi xảy ra khi tải lên tệp của bạn. Vui lòng thử lại.";
                }
            }
        } else {
            $errorMessages[] = "- Không có tệp nào được chọn để tải lên.";
        }

    }
}


//Khôi Phục Dữ liệu bằng tải lên hoặc tệp hệ thống
if (isset($_POST['start_recovery_Scheduler'])) {
$data_recovery_type = $_POST['start_recovery_Scheduler'];
if ($data_recovery_type === "khoi_phuc_tu_tep_tai_len"){
    $uploadOk = 1;
    // Kiểm tra xem tệp có được gửi không
    if (isset($_FILES["fileToUpload_Scheduler_restore"])) {
        $fileName = basename($_FILES["fileToUpload_Scheduler_restore"]["name"]);
		if (!preg_match('/\.json$/', $fileName)) {
		$errorMessages[] = "- Chỉ chấp nhận tệp .json, dành cho Home_Assistant_Custom.json";
		$uploadOk = 0;
		}
        if ($uploadOk == 0) {
            $errorMessages[] = "- Tệp sao lưu không được tải lên";
        } else {
            // Di chuyển tệp vào thư mục đích
            if (move_uploaded_file($_FILES["fileToUpload_Scheduler_restore"]["tmp_name"], $json_file)) {
                $successMessage[] = "- Tệp " . htmlspecialchars($fileName) . " đã được tải lên và khôi phục dữ liệu Custom Home Assistant thành công";
            } else {
                $errorMessages[] = "- Có lỗi xảy ra khi tải lên tệp sao lưu của bạn";
            }
        }
    } else {
        $errorMessages[] = "- Không có tệp sao lưu nào được tải lên";
    }
}

else if ($data_recovery_type === "khoi_phuc_file_he_thong"){
	$start_recovery_custom_hass = $_POST['backup_scheduler_json_files'];
if (!empty($start_recovery_custom_hass)) {
if (file_exists($start_recovery_custom_hass)) {
    $command = 'cp ' . escapeshellarg($start_recovery_custom_hass) . ' ' . escapeshellarg($json_file);
    exec($command, $output, $resultCode);
    if ($resultCode === 0) {
        $successMessage[] = "Đã khôi phục dữ liệu Custom Home Assistant từ tệp sao lưu trên hệ thống thành công";
    } else {
        $errorMessages[] = "Lỗi xảy ra khi khôi phục dữ liệu tệp Custom Home Assistant Mã lỗi: " . $resultCode;
    }
} else {
    $errorMessages[] = "Lỗi: Tệp ".basename($start_recovery_custom_hass)." không tồn tại trên hệ thống";
}
    } else {
        $errorMessages[] = "Không có tệp sao lưu Custom Home Assistant nào được chọn để khôi phục!";
    }
}
echo '<script>window.location.href = "Scheduler.php";</script>';
}


// Xử lý dữ liệu sau khi người dùng gửi form
if (isset($_POST['save_all_Scheduler'])) {
	
#Sao Lưu Dữ Liệu Trước
if (isset($Config['backup_upgrade']['scheduler']['active']) && $Config['backup_upgrade']['scheduler']['active'] === true) {
// Đường dẫn gốc và đích
$sourceFile = $VBot_Offline . $Config['schedule']['data_json_file'];
$destinationDir = $directory_path . '/' . $Config['backup_upgrade']['scheduler']['backup_path'];
$destinationFile = $destinationDir . "/Data_Schedule_" . date('dmY_His') . ".json";
// Kiểm tra xem thư mục đích có tồn tại hay không, nếu không thì tạo mới
if (!is_dir($destinationDir)) {
    mkdir($destinationDir, 0777, true);
    chmod($destinationDir, 0777);
    $successMessage[] = "- Tạo thư mục sao lưu thành công: <b>$destinationDir</b>";
}
// Sao chép tệp mới
if (copy($sourceFile, $destinationFile)) {
    chmod($destinationFile, 0777);
    //$successMessage[] = "Tệp đã được sao chép thành công đến $destinationFile";
    // Lấy danh sách các tệp .json trong thư mục đích, sắp xếp theo thời gian tạo (cũ nhất trước)
    $jsonFiles = glob($destinationDir . "/*.json");
    usort($jsonFiles, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    // Xóa các tệp cũ nhất nếu số lượng tệp vượt quá 5
    if (count($jsonFiles) > $Config['backup_upgrade']['scheduler']['limit_backup_files']) {
        foreach (array_slice($jsonFiles, 0, count($jsonFiles) - $Config['backup_upgrade']['scheduler']['limit_backup_files']) as $oldFile) {
            unlink($oldFile);
			$successMessage[] = "Vượt quá số lượng tệp tin Backup là <b>".$Config['backup_upgrade']['scheduler']['limit_backup_files']."</b>, đã xóa tệp: <b>".basename($oldFile)."</b>";
        }
    }
} else {
    $errorMessages[] = "- Xảy ra Lỗi, Không thể sao lưu tệp: <b>$sourceFile</b>";
}
}

#Lưu dữ liệu Lập Lịch, Thông Báo
    if (isset($_POST['notification_schedule'])) {
        $updated_schedule = [];
		foreach ($_POST['notification_schedule'] as $task) {
			$task['active'] = isset($task['active']) ? (bool)$task['active'] : false;
			
			$task['create_words'] = isset($task['create_words']) && !empty($task['create_words']) ? $task['create_words'] : 'vbot_interface';

			$task['data']['repeat'] = isset($task['data']['repeat']) && intval($task['data']['repeat']) > 0 ? intval($task['data']['repeat']) : 1;
			
			$task['data']['audio_file'] = isset($task['data']['audio_file']) && !empty($task['data']['audio_file']) ? $task['data']['audio_file'] : "";

			// Kiểm tra các điều kiện cơ bản của task
			if (
				!empty($task['name']) &&
				isset($task['time']) && is_array($task['time']) && count($task['time']) > 0 && // Kiểm tra time có mảng không
				!empty($task['date']) &&
				(!empty($task['data']['message']) || !empty($task['data']['audio_file']))
				) {
				// Lọc các giá trị thời gian hợp lệ (không phải chuỗi trống)
				$task['time'] = array_filter($task['time'], function($time) {
					return !empty($time);
				});
				// Nếu danh sách time sau khi lọc vẫn có ít nhất một giá trị
				if (!empty($task['time'])) {
					$updated_schedule[] = $task;
				}
			}
		}
        $data['notification_schedule'] = $updated_schedule;
        file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        #$successMessage[] = "Dữ liệu đã được lưu thành công.";
    }

#Lưu dữ liệu Thông Báo Cập Nhật Home Assistant
$data['send_notify_upgrade_vbot_home_assistant']['active'] = isset($_POST['send_notify_upgrade_vbot_home_assistant_active']) ? true : false;
$data['send_notify_upgrade_vbot_home_assistant']['time'] = isset($_POST['send_notify_upgrade_vbot_home_assistant_time']) ? $_POST['send_notify_upgrade_vbot_home_assistant_time'] : '03:01';

#Lưu dữ liệu Bật tắt màn hình
$time_on_display_screen = isset($_POST['time_on_display_screen']) ? $_POST['time_on_display_screen'] : [];
$time_off_display_screen = isset($_POST['time_off_display_screen']) ? $_POST['time_off_display_screen'] : [];
$data['display_screen']['active'] = isset($_POST['display_screen_active']) ? true : false;
$data['display_screen']['date'] = isset($_POST['dates_display_screen']) ? $_POST['dates_display_screen'] : [];
$data['display_screen']['time_on'] = array_filter($time_on_display_screen);
$data['display_screen']['time_off'] = array_filter($time_off_display_screen);

#Lưu dữ liệu Restart VBot
$time_restart_vbot = isset($_POST['time_restart_vbot']) ? $_POST['time_restart_vbot'] : [];
$data['restart_vbot']['time'] = array_filter($time_restart_vbot);
$data['restart_vbot']['active'] = isset($_POST['restart_vbot_service_active']) ? true : false;
$data['restart_vbot']['date'] = isset($_POST['dates_restart_vbot']) ? $_POST['dates_restart_vbot'] : [];

#Lưu dữ liệu Reboot OS
$time_reboot_os = isset($_POST['time_reboot_os']) ? $_POST['time_reboot_os'] : [];
$data['reboot_os']['time'] = array_filter($time_reboot_os);
$data['reboot_os']['active'] = isset($_POST['reboot_os_active']) ? true : false;
$data['reboot_os']['date'] = isset($_POST['dates_reboot_os']) ? $_POST['dates_reboot_os'] : [];


#lưu toàn bộ dữ liệu vào file Json
file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$successMessage[] = "Dữ liệu đã được lưu thành công.";
}
?>
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
		
    
<div id="task-container">
<?php if (!empty($data['notification_schedule'])) : ?>

    <?php foreach ($data['notification_schedule'] as $index => $notification) : ?>



<div class="card accordion" id="accordion_button_schedule_<?= $index ?>">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_schedule_<?= $index ?>" aria-expanded="false" aria-controls="collapse_button_schedule_<?= $index ?>">
<font color="Fuchsia"><?= htmlspecialchars($notification['name']) ?></font>, &nbsp;</font> Trạng Thái: &nbsp;<?= !empty($notification['active']) ? ' <font color=green>Bật</font>' : ' <font color=red>Tắt</font>' ?></h5>
<div id="collapse_button_schedule_<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_schedule_<?= $index ?>">



<div id="task-<?= $index ?>">
		

<div class="row mb-3">
<label for="active-<?= $index ?>" class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt hành động này')"></i>:</label>
<div class="col-sm-9">
<div class="form-switch">
<input type="checkbox" class="form-check-input" id="active-<?= $index ?>" name="notification_schedule[<?= $index ?>][active]" <?= $notification['active'] ? 'checked' : '' ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="name-<?= $index ?>" class="col-sm-3 col-form-label">Tên Tác Vụ <i class="bi bi-question-circle-fill" onclick="show_message('Tên Định Danh Để Phân Biệt Với Các Hành Động, Thao Tác Khác')"></i>:</label>
<div class="col-sm-9">
<input required class="form-control border-success" type="text" id="name-<?= $index ?>" name="notification_schedule[<?= $index ?>][name]" placeholder="<?= htmlspecialchars($notification['name']) ?>" value="<?= htmlspecialchars($notification['name']) ?>" title="Đặt Tên Định Danh Cho Lịch, Tác Vụ Này">
<div class="invalid-feedback">Cần đặt tên định danh cho tác vụ thông báo này</div>
</div>
</div>

<div class="row mb-3">
<label for="create_words-<?= $index ?>" class="col-sm-3 col-form-label">Nguồn tạo:</label>
<div class="col-sm-9">
<input readonly class="form-control border-danger" type="text" id="create_words-<?= $index ?>" name="notification_schedule[<?= $index ?>][create_words]" placeholder="<?= htmlspecialchars($notification['create_words'] ?? 'vbot_interface') ?>" value="<?= htmlspecialchars($notification['create_words'] ?? 'vbot_interface') ?>" title="Nguồn tạo tác vụ này">
</div>
</div>


<div class="row mb-3">
<label for="message-<?= $index ?>" class="col-sm-3 col-form-label">Nội Dung Thông Báo <i class="bi bi-question-circle-fill" onclick="show_message('Cần nhập nội dung thông báo, nếu không nhập nội dung thì cần phải cấu hình nhập file âm thanh, bắt buộc phải có 1 trong 2 thì mới cho lưu dữ liệu (hệ thống sẽ ưu tiên phát thông báo văn bản, nếu văn bản trống thì sẽ phát âm thanh từ file)')"></i>:</label>
<div class="col-sm-9">
<textarea type="text" rows="3" class="form-control border-success" id="message-<?= $index ?>" name="notification_schedule[<?= $index ?>][data][message]"><?= htmlspecialchars($notification['data']['message']) ?></textarea>
</div>
</div>

<div class="row mb-3">
    <label for="audio_file-<?= $index ?>" class="col-sm-3 col-form-label">
        Tệp Âm Thanh (Link,URL/PATH) 
        <i class="bi bi-question-circle-fill" onclick="show_message('Cần nhập thông tin đường dẫn, link, url tới tệp âm thanh, nếu không nhập nội dung thì cần phải cấu hình nhập file âm thanh, bắt buộc phải có 1 trong 2 thì mới cho lưu dữ liệu (hệ thống sẽ ưu tiên phát thông báo văn bản, nếu văn bản trống thì sẽ phát âm thanh từ file)')"></i>:
    </label>
    <div class="col-sm-9">
	<div class="input-group mb-3">
        <?php 
		
			// Kiểm tra và gán giá trị mặc định nếu phần tử không tồn tại
			$audio_file = isset($notification['data']['audio_file']) ? htmlspecialchars($notification['data']['audio_file']) : "";
            // Gọi hàm để tạo dropdown cho trường âm thanh này
            generate_audio_select($VBot_Offline.$Config['schedule']['audio_path'], 'notification_schedule[' . $index . '][data][audio_file]', $audio_file);
       
	   ?>
		<button class="btn btn-success border-success" onclick="playAudio_Schedule('notification_schedule[<?php echo $index; ?>][data][audio_file]')" type="button"><i class="bi bi-play-circle"></i></button>
    </div>
    </div>
</div>


<div class="row mb-3">
<label for="repeat-<?= $index ?>" class="col-sm-3 col-form-label">Số lần lặp lại <i class="bi bi-question-circle-fill" onclick="show_message('Số lần lặp lại phát thông báo khi tác vụ này được kích hoạt, để 2 lần thì đến giờ sẽ thông báo 2 lần liên tiếp')"></i>:</label>
<div class="col-sm-9">
<input required type="number" class="form-control border-success" id="repeat-<?= $index ?>" name="notification_schedule[<?= $index ?>][data][repeat]" value="<?= htmlspecialchars($notification['data']['repeat']) ?>">
<div class="invalid-feedback">Cần đặt tên cho hành động này</div>
</div>
</div>


<div class="row mb-3">
    <label for="date-<?= $index ?>" class="col-sm-3 col-form-label">
        Chọn các ngày trong tuần hoặc thiết lập ngày cụ thể (có thể thiết lập được cả 2 loại dữ liệu cùng lúc)
        <i class="bi bi-question-circle-fill" onclick="show_message('Chọn ít nhất một trong các ngày trong tuần hoặc nhập ngày cụ thể vào ô dưới, định dạng ngày sẽ phải phân cách bởi dấu / ví dụ: <b>01/12/20244</b>')"></i>:
    </label>
    <div class="col-sm-9">
        <div class="form-switch">
            <?php
            // Mảng các ngày đã chọn
            $selected_days = $notification['date']; 
            // Kiểm tra xem có ngày tháng cụ thể trong dữ liệu hay không
            $specific_dates = array_filter($selected_days, function($day) {
				// Kiểm tra xem giá trị có phải là ngày tháng (dd/mm/yyyy) không
                return preg_match('/\d{2}\/\d{2}\/\d{4}/', $day);
            });
			// Lọc bỏ các ngày tháng cụ thể, chỉ lấy các ngày trong tuần
            $week_days_selected = array_diff($selected_days, $specific_dates);
            // Hiển thị checkbox cho các ngày trong tuần
            foreach ($week_days as $key => $label) {
                $checked = in_array($key, $week_days_selected) ? 'checked' : '';
                echo '<input type="checkbox" class="form-check-input" id="date-' . $index . '-' . $key . '" name="notification_schedule[' . $index . '][date][]" value="' . $key . '" ' . $checked . '> ';
                echo '<label for="date-' . $index . '-' . $key . '">' . $label . '</label><br/>';
            }
            // Hiển thị ô input cho các ngày tháng cụ thể (dd/mm/yyyy) và nút xóa
            foreach ($specific_dates as $specific_date) {
                echo '<div class="mt-3 input-group" id="date-group-' . $index . '-' . $specific_date . '">';
                echo '<input type="text" class="form-control border-success" name="notification_schedule[' . $index . '][date][]" id="date-specific-' . $index . '-' . $specific_date . '" value="' . $specific_date . '" placeholder="Nhập ngày tháng (dd/mm/yyyy)" required>';
                echo '<button type="button" class="btn btn-danger border-success" onclick="removeDateInput(\'' . $index . '-' . $specific_date . '\')" title="Xóa Ngày: ' . $specific_date . '"><i class="bi bi-trash"></i></button>';
                echo '</div>';
            }
            ?>
<div id="date-container-<?= $index ?>">
    <button type="button" class="mt-3 btn btn-info rounded-pill" id="button_hien_thi_ngay_<?= $index ?>" onclick="addDateInput(<?= $index ?>)">Thêm ngày cụ thể</button>
</div>
        </div>
    </div>
</div>


<div class="row mb-3">
    <label class="col-sm-3 col-form-label">Thời gian (HH:MM) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian theo định dạng giờ, phút phải có dấu : ở giữa định dạng nhập là 24h: từ 00:00 tới 23:59')"></i>:</label>
    <div class="col-sm-9">
        <div id="time-container-<?= $index ?>">
            <?php foreach ($notification['time'] as $time_key => $time_value) : ?>
                <div class="time-input-container input-group mb-3">
                    <input type="text" class="form-control border-success time-input" 
                           name="notification_schedule[<?= $index ?>][time][]" 
                           value="<?= htmlspecialchars($time_value) ?>" 
                           id="time-input-<?= $index ?>-<?= $time_key ?>" 
                           placeholder="Chọn giờ và phút" 
                           onclick="showHourSuggestions(this)" 
                           autocomplete="off">
                    <div class="suggestions-list" id="suggestions-list-<?= $index ?>-<?= $time_key ?>" style="display: none;">
                        <!-- Các gợi ý sẽ được thêm vào đây -->
                    </div>
                    <button type="button" class="btn btn-danger border-success" 
                            onclick="removeTimeInput(<?= $index ?>, this)" title="Xóa Thời Gian">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
<button type="button" class="btn btn-success rounded-pill" onclick="addTimeInput(<?= $index ?>)">Thêm thời gian</button>
</div>
</div>

<center>
<button class="btn btn-danger rounded-pill" type="button" onclick="deleteTask(<?= $index ?>)"><i class="bi bi-trash" title="Xóa bỏ tác vụ này"></i> Xóa tác vụ này</button>
</center>
</div>


</div>
</div>
</div>
<?php endforeach; ?>
<?php else : ?>
<p class="text-danger"><center><h5>Chưa có tác vụ thông báo, lời nhắc nào được thiết lập</h5></center></p>
<?php endif; ?>
</div>

<hr style="border: 2px solid #0000FF;">

<div class="card accordion" id="accordion_button_send_notify_upgrade_vbot_hass">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_send_notify_upgrade_vbot_hass" aria-expanded="false" aria-controls="collapse_button_send_notify_upgrade_vbot_hass">
<font color="Purple">Kiểm Tra Và Thông Báo Cập Nhật VBot Tới Home Assistant,</font>&nbsp; Trạng Thái: &nbsp;
<?php 
echo isset($data['send_notify_upgrade_vbot_home_assistant']['active']) 
    ? ($data['send_notify_upgrade_vbot_home_assistant']['active'] 
        ? ' <font color=green> Bật</font>' 
        : ' <font color=red> Tắt</font>') 
    : '<font color=gray> Không xác định</font>'; 
?>
</h5>
<div id="collapse_button_send_notify_upgrade_vbot_hass" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_send_notify_upgrade_vbot_hass">

<div class="row mb-3">
    <label class="col-sm-3 col-form-label">
        Kích Hoạt 
        <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt hành động này<br/><br/>Yêu Cầu:<br/> - Phải Kích Hoạt Home Assistant<br/>- Phải Kích Hoạt Thông Báo Lời Nhắc')"></i>:
    </label>
    <div class="col-sm-9">
        <div class="form-switch">
            <input 
                type="checkbox" 
                class="form-check-input" 
                id="send_notify_upgrade_vbot_home_assistant_active" 
                name="send_notify_upgrade_vbot_home_assistant_active" 
                <?php echo (isset($data['send_notify_upgrade_vbot_home_assistant']['active']) 
                            ? $data['send_notify_upgrade_vbot_home_assistant']['active'] 
                            : true) ? 'checked' : ''; ?>>
        </div>
    </div>
</div>


<div class="row mb-3">
    <label for="send_notify_upgrade_vbot_home_assistant_time" class="col-sm-3 col-form-label">
        Thời Gian 
        <i class="bi bi-question-circle-fill" onclick="show_message('Định dạng thời gian là 24 giờ, (giờ:phút) Ví Dụ: (03:59)')"></i>:
    </label>
    <div class="col-sm-9">
        <input 
            class="form-control border-danger" 
            type="text" 
            name="send_notify_upgrade_vbot_home_assistant_time" 
            id="send_notify_upgrade_vbot_home_assistant_time" 
            placeholder="<?php echo isset($data['send_notify_upgrade_vbot_home_assistant']['time']) ? $data['send_notify_upgrade_vbot_home_assistant']['time'] : '03:01'; ?>" 
            value="<?php echo isset($data['send_notify_upgrade_vbot_home_assistant']['time']) ? $data['send_notify_upgrade_vbot_home_assistant']['time'] : '03:01'; ?>">
    </div>
</div>
</div>
</div>
</div>


<div class="card accordion" id="accordion_button_display_screen_time">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_display_screen_time" aria-expanded="false" aria-controls="collapse_button_display_screen_time">
<font color="Blue">Lập Lịch, Bật Tắt Hiển Thị Dữ Liệu Màn Hình</font>, Trạng Thái:&nbsp;
<?php 
echo isset($data['display_screen']['active']) ? ($data['display_screen']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>'; 
?></h5>
<div id="collapse_button_display_screen_time" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_display_screen_time">

<?php
// Kiểm tra dữ liệu display_screen và gán giá trị mặc định nếu không có
if (!isset($data['display_screen']) || empty($data['display_screen'])) {
    // Gán giá trị mặc định nếu không có dữ liệu display_screen
    $data['display_screen'] = [
        'active' => false,
        'date' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        'time_on' => ["05:01"],
        'time_off' => ["23:59"]
    ];
} 
$display_screen = $data['display_screen'];
?>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="display_screen_active" id="display_screen_active" value="<?php echo $display_screen['active']; ?>" <?= $display_screen['active'] ? 'checked' : '' ?>>
</div>
</div>
</div>

<!-- Checkbox ngày -->
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Các Ngày Trong Tuần <i class="bi bi-question-circle-fill" onclick="show_message('Chọn Các Ngày Trong Tuần Để Áp Dụng Bật, Tắt Sử Dụng Màn Hình')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<?php foreach ($week_days as $date => $label): ?>
<input class="form-check-input" type="checkbox" name="dates_display_screen[]" value="<?= htmlspecialchars($date) ?>" <?= in_array($date, $display_screen['date']) ? 'checked' : '' ?>>
<label><?= htmlspecialchars($label) ?></label>
<br/>
<?php endforeach; ?>
</div>
</div>
</div>

<!-- Thời gian bật -->
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Thời Gian Bật:</label>
<div class="col-sm-9">
<div class="time-inputs_display_screen" id="time-on-container">
<?php foreach ($display_screen['time_on'] as $index => $time_on): ?>
<div class="time-input-container input-group mb-3" id="time-on_display_screen-<?= $index ?>">
<input class="form-control border-success" type="text" name="time_on_display_screen[]" value="<?= htmlspecialchars($time_on) ?>" placeholder="HH:mm">
<button class="btn btn-danger border-success" title="Xóa thời gian Bật này" type="button" id="delete-on_display_screen-<?= $index ?>"><i class="bi bi-trash"></i></button>
</div>
<?php endforeach; ?>
<button class="btn btn-success rounded-pill" type="button" id="add-time-on_display_screen">Thêm thời gian bật</button>
</div>
</div>
</div>

<hr/>


<!-- Thời gian tắt -->
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Thời Gian Tắt:</label>
<div class="col-sm-9">

<div class="time-inputs_display_screen" id="time-off-container">
<?php foreach ($display_screen['time_off'] as $index => $time_off): ?>
<div class="time-input-container input-group mb-3" id="time-off_display_screen-<?= $index ?>">
<input class="form-control border-success" type="text" name="time_off_display_screen[]" value="<?= htmlspecialchars($time_off) ?>" placeholder="HH:mm">
<button class="btn btn-danger border-success" title="Xóa thời gian Tắt này" type="button" id="delete-off_display_screen-<?= $index ?>"><i class="bi bi-trash"></i></button>
</div>
<?php endforeach; ?>
<button class="btn btn-warning rounded-pill" type="button" id="add-time-off_display_screen">Thêm thời gian tắt</button>
</div>

</div>
</div>

</div>
</div>
</div>


<div class="card accordion" id="accordion_button_restart_vbot_service">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_restart_vbot_service" aria-expanded="false" aria-controls="collapse_button_restart_vbot_service">
Lập Lịch Auto Restart VBot, &nbsp; Trạng Thái: &nbsp;
<?php 
echo isset($data['restart_vbot']['active']) ? ($data['restart_vbot']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>'; 
?>
</h5>

<div id="collapse_button_restart_vbot_service" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_restart_vbot_service">

<?php
// Kiểm tra dữ liệu restart_vbot và gán giá trị mặc định nếu không có
if (!isset($data['restart_vbot']) || empty($data['restart_vbot'])) {
    // Gán giá trị mặc định nếu không có dữ liệu restart_vbot
    $data['restart_vbot'] = [
        'active' => false,
        'date' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        'time' => ["03:03"]
    ];
}
$restart_vbot = $data['restart_vbot'];
?>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="restart_vbot_service_active" id="restart_vbot_service_active" value="<?php echo $restart_vbot['active']; ?>" <?= $restart_vbot['active'] ? 'checked' : '' ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Các Ngày Trong Tuần <i class="bi bi-question-circle-fill" onclick="show_message('Chọn Các Ngày Trong Tuần Để Sử Dụng Khởi Động Lại Chương Trình VBot')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<?php foreach ($week_days as $date => $label): ?>
<input class="form-check-input" type="checkbox" name="dates_restart_vbot[]" value="<?= htmlspecialchars($date) ?>" <?= in_array($date, $restart_vbot['date']) ? 'checked' : '' ?>>
<label><?= htmlspecialchars($label) ?></label>
<br/>
<?php endforeach; ?>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Thời Gian:</label>
<div class="col-sm-9">
<div class="time-inputs_restart_vbot" id="time-on-restart_vbot">
<?php foreach ($restart_vbot['time'] as $index => $time): ?>
<div class="time-input-restart_vbot input-group mb-3" id="time-restart_vbot-<?= $index ?>">
<input class="form-control border-success" type="text" name="time_restart_vbot[]" value="<?= htmlspecialchars($time) ?>" placeholder="HH:mm">
<button class="btn btn-danger border-success" title="Xóa thời gian Bật này" type="button" id="delete-restart_vbot-<?= $index ?>"><i class="bi bi-trash"></i></button>
</div>
<?php endforeach; ?>
<button class="btn btn-success rounded-pill" type="button" id="add-time-restart_vbot">Thêm thời gian</button>
</div>
</div>
</div>


</div>
</div>
</div>


<div class="card accordion" id="accordion_button_reboot_os">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_reboot_os" aria-expanded="false" aria-controls="collapse_button_reboot_os">
Lập Lịch Auto Reboot OS SYSTEM, &nbsp; Trạng Thái: &nbsp;
<?php 
echo isset($data['reboot_os']['active']) ? ($data['reboot_os']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>'; 
?>
</h5>
<div id="collapse_button_reboot_os" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_reboot_os">
<?php
// Kiểm tra dữ liệu reboot_os và gán giá trị mặc định nếu không có
if (!isset($data['reboot_os']) || empty($data['reboot_os'])) {
    // Gán giá trị mặc định nếu không có dữ liệu reboot_os
    $data['reboot_os'] = [
        'active' => false,
        'date' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        'time' => ["03:05"]
    ];
}
$reboot_os = $data['reboot_os'];
?>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="reboot_os_active" id="reboot_os_active" value="<?php echo $reboot_os['active']; ?>" <?= $reboot_os['active'] ? 'checked' : '' ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Các Ngày Trong Tuần <i class="bi bi-question-circle-fill" onclick="show_message('Chọn Các Ngày Trong Tuần Để Sử Dụng Khởi Động Lại OS SYSTEM')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<?php foreach ($week_days as $date => $label): ?>
<input class="form-check-input" type="checkbox" name="dates_reboot_os[]" value="<?= htmlspecialchars($date) ?>" <?= in_array($date, $reboot_os['date']) ? 'checked' : '' ?>>
<label><?= htmlspecialchars($label) ?></label>
<br/>
<?php endforeach; ?>
</div>
</div>
</div>


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Thời Gian:</label>
<div class="col-sm-9">
<div class="time-inputs_reboot_os" id="time-on-reboot_os">
<?php foreach ($reboot_os['time'] as $index => $time): ?>
<div class="time-input-reboot_os input-group mb-3" id="time-reboot_os-<?= $index ?>">
<input class="form-control border-success" type="text" name="time_reboot_os[]" value="<?= htmlspecialchars($time) ?>" placeholder="HH:mm">
<button class="btn btn-danger border-success" title="Xóa thời gian Bật này" type="button" id="delete-reboot_os-<?= $index ?>"><i class="bi bi-trash"></i></button>
</div>
<?php endforeach; ?>
<button class="btn btn-success rounded-pill" type="button" id="add-time-reboot_os">Thêm thời gian</button>
</div>
</div>
</div>


</div>
</div>
</div>


<center>
<button type="submit" name="save_all_Scheduler" class="btn btn-primary rounded-pill"><i class="bi bi-save"></i> Lưu Dữ liệu</button>
<button type="button" class="btn btn-success rounded-pill" onclick="addNewTask()">Thêm mới tác vụ</button>
<button type="button" class="btn btn-info rounded-pill" title="Tải Xuống file: <?php echo $json_file; ?>" onclick="downloadFile('<?php echo $json_file; ?>')">
<i class="bi bi-download"></i> Tải Xuống</button>
<button type="button" class="btn btn-warning rounded-pill" title="Xem dữ liệu Đã cấu hình Custom Home Assistant" id="openModalBtn_laplich_thongbao">
 <i class="bi bi-eye"></i>Xem dữ liệu Cấu Hình</button>

<button class="btn btn-danger rounded-pill" type="submit" name="delete_all_Scheduler" onclick="return confirmRestore('Bạn có chắc chắn muốn xóa tất cả dữ liệu cấu hình Lời Nhắc, Thông Báo không')">
<i class="bi bi-trash"></i> Xóa Dữ Liệu Cấu hình</button>
		</center>
		
<h5 class="card-title"><font color="green">Tải Lên Tệp Âm Thanh:</font></h5>
<div class="row mb-3">
    <label class="col-sm-3 col-form-label"><b>Tải Lên Tệp:</b></label>
    <div class="col-sm-9">
        <div class="input-group">
            <input class="form-control border-success" type="file" name="fileToUpload_Scheduler_Upload_Audio" accept=".mp3,.wav,.ogg,.aac">
            <button class="btn btn-warning border-success" type="submit" name="Scheduler_Upload_Audio_Submit" value="Scheduler_Upload_Audio">Tải Lên</button>
			<button class="btn btn-primary border-success" type="button" onclick="get_audio_schedule()"><i class="bi bi-music-note-list"></i></button>
        </div>
    </div>
</div>


<div id="du_lieu_audio_schedule"></div>

<hr/>
<h5 class="card-title"><font color="green">Khôi Phục Dữ Liệu:</font></h5>
<div class="row mb-3">
    <label class="col-sm-3 col-form-label"><b>Tải Lên Tệp Và Khôi Phục:</b></label>
    <div class="col-sm-9">
        <div class="input-group">
            <input class="form-control border-success" type="file" name="fileToUpload_Scheduler_restore" accept=".json">
            <button class="btn btn-warning border-success" type="submit" name="start_recovery_Scheduler" value="khoi_phuc_tu_tep_tai_len" onclick="return confirmRestore(\'Bạn có chắc chắn muốn tải lên tệp để khôi phục dữ liệu Home_Assistant_Custom.json không?\')">Tải Lên & Khôi Phục</button>
        </div>
    </div>
</div>

<div class="row mb-3">
    <label class="col-sm-3 col-form-label"><b>Hoặc Chọn Tệp Khôi Phục:</b></label>
    <div class="col-sm-9">
<?php
$jsonFiles = glob($Config['backup_upgrade']['scheduler']['backup_path'].'/*.json');
$co_tep_BackUp_customhass = true;
if (empty($jsonFiles)) {
	$co_tep_BackUp_customhass = false;
echo '<select class="form-select border-primary" name="backup_scheduler_json_files" id="backup_scheduler_json_files">';
    echo '<option selected value="">Không có tệp khôi phục dữ liệu Config nào</option>';
	echo '</select>';
} else {
	$co_tep_BackUp_customhass = true;
echo '<div class="input-group"><select class="form-select border-primary" name="backup_scheduler_json_files" id="backup_scheduler_json_files">';
echo '<option selected value="">Chọn Tệp Khôi Phục Dữ Liệu Lời Nhắc Thông Báo</option>';
foreach ($jsonFiles as $file) {
    $fileName = basename($file);
    echo '<option value="' . htmlspecialchars($Config['backup_upgrade']['scheduler']['backup_path'].'/'.$fileName) . '">' . htmlspecialchars($fileName) . '</option>';
}
echo '</select>
<button class="btn btn-warning border-primary" type="submit" name="start_recovery_Scheduler" value="khoi_phuc_file_he_thong">Khôi Phục</button>
<button type="button" class="btn btn-info border-primary" title="Tải Xuống Tệp Sao Lưu Custom Home Assistant" onclick="dowlaod_file_backup_scheduler(\'get_value_backup_config\')"><i class="bi bi-download"></i></button>
<button type="button" class="btn btn-success border-primary" title="Xem Tệp Sao Lưu Custom Home Assistant" onclick="readJSON_file_path(\'get_value_backup_config\')"><i class="bi bi-eye"></i></button>
<button type="button" class="btn btn-danger border-primary" title="Xóa Tệp Sao Lưu Custom Home Assistant" onclick="delete_file_backup_scheduler(\'get_value_backup_config\')"><i class="bi bi-trash"></i></button>
</div>';
}


?>
</div>
</div>

 </form>
		</div>
		</section>
	
</main>

    <!-- Modal hiển thị tệp Config.json -->
    <div class="modal fade" id="openModalBtn_lichthongbao" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
        <div class="modal-dialog" id="modal_dialog_show_Home_Assistant" role="document">
            <div class="modal-content">
                <div class="modal-header">
				<b><font color=blue><div id="name_file_showzz"></div></font></b> 
                    <button type="button" class="close btn btn-danger" data-dismiss="modal_Config" aria-label="Close" onclick="$('#openModalBtn_lichthongbao').modal('hide');">
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
  <!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>


<script>
function playAudio_Schedule(id_select_DOM) {
    var element = document.getElementById(id_select_DOM);
    if (element) {
        // Kiểm tra phần tử có thuộc tính 'value' hay không
        if ('value' in element) {
            playAudio(element.value);
        } else {
            show_message("Không lấy được giá trị value của thẻ '" + id_select_DOM + "'");
            return null;
        }
    } else {
		show_message("Không tìm thấy dữ liệu thẻ với ID: '" + id_select_DOM + "'");
        return null;
    }
}

function get_audio_schedule() {
	loading("show");
    var xhr = new XMLHttpRequest();
    var url = "includes/php_ajax/Media_Player_Search.php?audio_schedule";
    xhr.open("GET", url, true);
    xhr.responseType = "json";
    xhr.send();
    xhr.onload = function() {
        if (xhr.status == 200) {
            var response = xhr.response;
            if (Array.isArray(response)) {
				loading("hide");
                // Lấy phần tử <div> với id "du_lieu_audio_schedule"
                var audioScheduleDiv = document.getElementById("du_lieu_audio_schedule");
                
                // Làm trống nội dung thẻ div trước khi thêm dữ liệu mới
                audioScheduleDiv.innerHTML = '';

                // Tạo bảng HTML để hiển thị tên và kích thước tệp
                var table = document.createElement('table');
				// Thêm lớp CSS để làm đẹp bảng class="table table-bordered border-primary"
                table.classList.add('table', 'table-bordered', 'border-primary');

                // Tạo tiêu đề bảng
                var thead = document.createElement('thead');
                var headerRow = document.createElement('tr');
                headerRow.innerHTML = '<th style="text-align: center; vertical-align: middle;">Tên tệp</th><th style="text-align: center; vertical-align: middle;">Kích thước (MB)</th><th style="text-align: center; vertical-align: middle;">Hành Động</th>';
                thead.appendChild(headerRow);
                table.appendChild(thead);

                var tbody = document.createElement('tbody');
                response.forEach(function(audio) {
                    var row = document.createElement('tr');
				row.innerHTML = '<td style="text-align: center; vertical-align: middle;">' + audio.name + '</td><td style="text-align: center; vertical-align: middle;">' + audio.size + ' MB</td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                ' <button type="button" class="btn btn-primary" onclick="playAudio(\'' + audio.full_path + '\')"><i class="bi bi-play-circle"></i></button>' +
                ' <button type="button" class="btn btn-success" onclick="downloadFile(\'' + audio.full_path + '\')"><i class="bi bi-download"></i></button>' +
                ' <button type="button" class="btn btn-danger" onclick="deleteFile(\'' + audio.full_path + '\', \'du_lieu_audio_schedule\')"><i class="bi bi-trash"></i></button>' +
                '</td>';
                    tbody.appendChild(row);
                });
                // Thêm phần thân bảng vào bảng
                table.appendChild(tbody);
                // Thêm bảng vào trong <div id="du_lieu_audio_schedule">
                audioScheduleDiv.appendChild(table);
            } else {
				loading("hide");
                console.error("Dữ liệu không phải là mảng.");
            }
        } else {
			loading("hide");
            console.error("Yêu cầu không thành công: " + xhr.status);
        }
    };
    xhr.onerror = function() {
		loading("hide");
        console.error("Lỗi trong quá trình gửi yêu cầu.");
    };
}


//Xóa file backup Config
function delete_file_backup_scheduler(filePath) {
    if (filePath === "get_value_backup_config") {
        var get_value_backup_config = document.getElementById('backup_scheduler_json_files').value;
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

//Tải xuống file backup Config
function dowlaod_file_backup_scheduler(filePath) {
    if (filePath === "get_value_backup_config") {
        var get_value_backup_config = document.getElementById('backup_scheduler_json_files').value;
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

//onclick xem nội dung file json
function readJSON_file_path(filePath) {
    if (filePath === "get_value_backup_config") {
        var get_value_backup_config = document.getElementById('backup_scheduler_json_files').value;
        if (get_value_backup_config === "") {
            showMessagePHP("Không có tệp nào được chọn để xem nội dung");
        } else {
            filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
            read_loadFile(filePath);
			document.getElementById('name_file_showzz').textContent = "Tên File: "+filePath.split('/').pop();
            $('#openModalBtn_lichthongbao').modal('show');
        }
    } else {
        read_loadFile(filePath);
		document.getElementById('name_file_showzz').textContent = "Tên File: "+filePath.split('/').pop();
        $('#openModalBtn_lichthongbao').modal('show');
    }
}
</script>
  
<script>
// Hiển thị modal xem nội dung file json Home_Assistant.json
['openModalBtn_laplich_thongbao'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function() {
		var file_name_hassJSON = "<?php echo $json_file; ?>";
        read_loadFile(file_name_hassJSON);
		document.getElementById('name_file_showzz').textContent = "Tên File: "+file_name_hassJSON.split('/').pop();
        $('#openModalBtn_lichthongbao').modal('show');
    });
});
</script>
<script>
// Chỉ số bắt đầu cho tác vụ mới
let newTaskIndex = <?= count($data['notification_schedule']) ?>; 
// Biến lưu trữ các tác vụ đã xóa
let deletedTasks = [];

// Hàm tải lại dữ liệu vào DOM (tải lại các ngày có sẵn từ server hoặc từ mảng hiện tại)
function createDateElement(index, specific_date, container) {
	// Tạo ID đặc biệt cho mỗi ngày
    const dateGroupId = index + '-' + specific_date;
    // Tạo HTML chứa input và nút xóa, sử dụng innerHTML
	const dateElementHTML = 
		'<div class="mt-3 input-group" id="date-group-' + dateGroupId + '">' +
			'<input type="text" ' +
				'class="form-control border-success" ' +
				'name="notification_schedule[' + index + '][date][]" ' +
				'value="' + specific_date + '" ' +
				'placeholder="Nhập ngày tháng (dd/mm/yyyy)" ' +
				'>' +
			'<button type="button" ' +
				'class="btn btn-danger border-success" ' +
				'title="Xóa Ngày: ' + specific_date + '" ' +
				'onclick="removeDateInput(\'' + dateGroupId + '\')">' +
				'<i class="bi bi-trash"></i>' +
			'</button>' +
		'</div>';
    // Thêm HTML vào container
    container.innerHTML += dateElementHTML;
}

// Hàm thêm nút "Thêm ngày" vào cuối container
function addAddButton(container, index) {
	// Xóa thẻ div chứa các phần tử bên trong
    const id_button_ban_dau = document.getElementById('button_hien_thi_ngay_' + index);
    if (id_button_ban_dau) {
        id_button_ban_dau.remove();
    }
    // Xóa tất cả các nút "Thêm ngày" cũ trước khi thêm nút mới
    const existingAddButton = container.querySelector('input[type="button"]');
    if (existingAddButton) {
        existingAddButton.remove();
    }
    // Thêm nút "Thêm ngày" mới vào cuối cùng của container
    container.innerHTML += '<div class="mt-3"><input type="button" class="btn btn-info rounded-pill" value="Thêm ngày cụ thể" onclick="addDateInput(' + index + ')"></div>';
}

// Hàm thêm một ngày mới vào container
function addDateInput(index) {
    const currentDate = new Date();
    const day = ("0" + currentDate.getDate()).slice(-2);
    const month = ("0" + (currentDate.getMonth() + 1)).slice(-2);
    const year = currentDate.getFullYear();
    const formattedDate = day+'/'+month+'/'+year;
    const container = document.querySelector('#date-container-' + index);
    // Tạo và thêm phần tử ngày vào container
    createDateElement(index, formattedDate, container);
	// Thêm nút "Thêm ngày" vào cuối container sau khi thêm ngày mới
    addAddButton(container, index);
}

// Hàm xóa ngày khi người dùng nhấn nút xóa
function removeDateInput(dateGroupId) {
    const dateGroup = document.getElementById('date-group-' + dateGroupId);
    if (dateGroup) {
		// Xóa thẻ div chứa input và nút xóa
        dateGroup.remove();
    }
}

// Xóa một tác vụ
function deleteTask(taskIndex) {
    if (confirm("Bạn có chắc muốn xóa tác vụ này?")) {
        const taskDiv = document.getElementById("task-" + taskIndex);
        // Lưu tác vụ vào mảng deletedTasks
        deletedTasks.push({
            index: taskIndex,
			// Lưu nội dung của tác vụ để phục hồi
            content: taskDiv.innerHTML
        });
        // Xóa nội dung hiện tại và hiển thị thông báo
        taskDiv.innerHTML = '<div style="color: red;">Đã xóa tác vụ thứ tự: <b>' + taskIndex + '</b> Chờ lưu thay đổi để áp dụng. <button class="btn btn-warning rounded-pill" type="button" onclick="restoreTask(' + taskIndex + ')">Khôi Phục</button></div>';
    }
}

// Phục hồi một tác vụ
function restoreTask(taskIndex) {
    // Tìm tác vụ đã xóa từ mảng deletedTasks
    const task = deletedTasks.find(task => task.index === taskIndex);
    if (task) {
        // Tạo lại thẻ div cho tác vụ đã xóa và thay thế nội dung
        const taskDiv = document.getElementById("task-" + taskIndex);
		// Phục hồi nội dung cũ
        taskDiv.innerHTML = task.content;
        // Xóa tác vụ khỏi mảng deletedTasks
        deletedTasks = deletedTasks.filter(task => task.index !== taskIndex);
    }
}

function addNewTask() {
    const taskContainer = document.getElementById('task-container');
    let taskHtml = 
        "<hr/><div class='card accordion'><div class='card-body'><div id='task-" + newTaskIndex + "'>" +
            "<h5 class='card-title'>Lập Lịch, Tác Vụ Thông Báo Mới:</h5>" +

			"<div class='row mb-3'>" +
			"<label for='active-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Kích hoạt:</label>" +
			"<div class='col-sm-9'>" +
			"<div class='form-switch'>" +
			"<input type='checkbox' class='form-check-input' id='active-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][active]' checked>" +
			"</div>" +
			"</div>" +
			"</div>" +

			"<div class='row mb-3'>" +
			"<label for='name-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Tên tác vụ:</label>" +
			"<div class='col-sm-9'>" +
			"<input required class='form-control border-success' type='text' id='name-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][name]' placeholder='Cần nhập tên tác vụ mới'>" +
			"<div class='invalid-feedback'>Cần đặt tên định danh cho tác vụ này</div>" +
			"</div>" +
			"</div>" +

			"<div class='row mb-3'>" +
			"<label for='message-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Nội Dung Thông Báo:</label>" +
			"<div class='col-sm-9'>" +
			"<textarea type='text' rows='3' class='form-control border-success' id='message-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][data][message]' placeholder='Cần nhập nội dung thông báo'></textarea>" +
			"</div>" +
			"</div>" +

/*
			"<div class='row mb-3'>" +
			"<label for='audio_file-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Tệp Âm Thanh (Link,URL/PATH):</label>" +
			"<div class='col-sm-9'>" +
			"<input class='form-control border-success' type='text' id='audio_file-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][data][audio_file]' placeholder='Hoặc đường dẫn Path, Link, Url đến âm thanh'>" +
			"</div>" +
			"</div>" +
*/

			"<div class='row mb-3'>" +
			"<label for='repeat-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Số lần lặp lại:</label>" +
			"<div class='col-sm-9'>" +
			"<input required class='form-control border-success' type='number' id='repeat-" + newTaskIndex + "' min='1' step='1' max ='5' name='notification_schedule[" + newTaskIndex + "][data][repeat]' value='1'>" +
			"<div class='invalid-feedback'>Cần điền số lần lặp lại thông báo</div>" +
			"</div>" +
			"</div>" +

			"<div class='row mb-3'>" +
            "<label for='date-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Chọn các ngày trong tuần:</label>" +
			"<div class='col-sm-9'>" +
			"<div class='form-switch'>";
			taskHtml += "<?php foreach (['Monday' => 'Thứ Hai', 'Tuesday' => 'Thứ Ba', 'Wednesday' => 'Thứ Tư', 'Thursday' => 'Thứ Năm', 'Friday' => 'Thứ Sáu', 'Saturday' => 'Thứ Bảy', 'Sunday' => 'Chủ Nhật'] as $key => $label) : ?>" +
                    "<input type='checkbox' class='form-check-input' id='date-" + newTaskIndex + "-<?= $key ?>' name='notification_schedule[" + newTaskIndex + "][date][]' value='<?= $key ?>' checked> " +
                    " <label for='date-" + newTaskIndex + "-<?= $key ?>'><?= $label ?></label><br>" +
                 "<?php endforeach; ?>" +
				 "</div>" +
				 "<div id='date-container-"+newTaskIndex+"'>" +
				 "<button type='button' class='mt-3 btn btn-info rounded-pill' id='button_hien_thi_ngay_"+newTaskIndex+"' onclick='addDateInput("+newTaskIndex+")'>Thêm ngày cụ thể</button>" +
				 "</div>" +
				 "</div></div>" +

			"<div class='row mb-3'>" +
            "<label class='col-sm-3 col-form-label'>Thời gian (HH:MM) <i class='bi bi-question-circle-fill' onclick='show_message(\"Thời gian theo định dạng giờ, phút phải có dấu : ở giữa, định dạng nhập là 24h: từ 00:00 tới 23:59\")'></i>:</label><br>" +
			"<div class='col-sm-9'>" +
            "<div id='time-container-" + newTaskIndex + "'>" +
                "<div class='input-group mb-3'>" +
                    "<input required min='00:00' max='23:59' class='form-control border-success' name='notification_schedule[" + newTaskIndex + "][time][]' placeholder='HH:MM'>" +
                    "<button type='button' class='btn btn-danger border-success' onclick='removeTimeInput(" + newTaskIndex + ", this)' style='display: none;'><i class='bi bi-trash'></i></button>" +
                "<div class='invalid-feedback'>Cần nhập thời gian thực hiện của tác vụ</div>" +
				"</div>" +
            "</div>" +
            "<button type='button' class='btn btn-primary rounded-pill' onclick='addTimeInput(" + newTaskIndex + ")'>Thêm thời gian</button><br><br>" +
            "<center><button type='button' class='btn btn-danger rounded-pill' onclick='deleteTask(" + newTaskIndex + ")'><i class='bi bi-trash'></i> Xóa tác vụ này</button></center>" +
			"</div>" +
			"</div>" +
			"</div>" +
			"</div>" +
        "</div>";
    taskContainer.innerHTML += taskHtml;
	// Tăng index để tạo tác vụ mới tiếp theo
    newTaskIndex++
	showMessagePHP("Đã Thêm Ô Nhập Liệu Tác Vụ Lập Lịch Mới, Hãy Điền Thông Tin Vào", 6);
}

// Xóa input thời gian
function removeTimeInput(taskIndex, buttonElement) {
    const timeContainer = document.getElementById('time-container-' + taskIndex);
    timeContainer.removeChild(buttonElement.parentElement);
    // Cập nhật hiển thị nút "Xóa" sau khi xóa một input
    updateRemoveButtonVisibility(taskIndex);
}

// Cập nhật hiển thị nút "Xóa" khi có ít nhất 2 input
function updateRemoveButtonVisibility(taskIndex) {
    const timeContainer = document.getElementById('time-container-' + taskIndex);
    const timeInputs = timeContainer.getElementsByTagName('div');
    const removeButtons = timeContainer.getElementsByTagName('button');
    // Nếu chỉ còn một input thời gian, ẩn nút "Xóa", nếu có hơn một input thì hiển thị
    for (let i = 0; i < removeButtons.length; i++) {
        removeButtons[i].style.display = timeInputs.length > 1 ? 'inline' : 'none';
    }
}
// Gọi updateRemoveButtonVisibility cho mỗi tác vụ khi tải trang
document.addEventListener("DOMContentLoaded", function() {
    <?php foreach ($data['notification_schedule'] as $index => $notification) : ?>
        updateRemoveButtonVisibility(<?= $index ?>);
    <?php endforeach; ?>
});
</script>

<script>
// Thêm input thời gian mới
function addTimeInput(taskIndex) {
    const timeContainer = document.getElementById('time-container-' + taskIndex);
    const timeInputHtml = 
        "<div class='input-group mb-3'>" +
            "<input type='text' class='form-control border-success' name='notification_schedule[" + taskIndex + "][time][]' placeholder='HH:MM'>" +
            "<button type='button' class='btn btn-danger border-success' onclick='removeTimeInput(" + taskIndex + ", this)' title='Xóa Thời Gian'><i class='bi bi-trash'></i></button>" +
        "</div>";
    timeContainer.innerHTML += timeInputHtml;
    // Cập nhật hiển thị nút "Xóa" khi có ít nhất 2 input
    updateRemoveButtonVisibility(taskIndex);
}
    // Tạo danh sách giờ
    function generateHourSuggestions() {
        const hours = [];
        for (let hour = 0; hour < 24; hour++) {
            const h = hour.toString().padStart(2, '0');
            hours.push(h);
        }
        return hours;
    }
    // Tạo danh sách phút
    function generateMinuteSuggestions() {
        const minutes = [];
		// Tăng mỗi 1 phút
        for (let minute = 0; minute < 60; minute += 1) {
            const m = minute.toString().padStart(2, '0');
            minutes.push(m);
        }
        return minutes;
    }
    // Hiển thị danh sách gợi ý giờ
    function showHourSuggestions(input) {
        const container = input.nextElementSibling;
        const hours = generateHourSuggestions();
        container.innerHTML = ''; // Xóa gợi ý cũ
        hours.forEach(hour => {
            const option = document.createElement('div');
            option.className = 'suggestion-item';
            option.innerText = hour + ' giờ';
            option.onclick = () => {
				// Lưu giờ được chọn tạm thời
                input.dataset.selectedHour = hour;
				// Chỉ cập nhật giờ, giữ chỗ cho phút
                input.value = hour + ':--';
				// Chuyển sang chọn phút
                showMinuteSuggestions(input);
            };
            container.appendChild(option);
        });
		// Hiển thị danh sách
        container.style.display = 'block';
    }
    // Hiển thị danh sách gợi ý phút
    function showMinuteSuggestions(input) {
        const container = input.nextElementSibling;
        const minutes = generateMinuteSuggestions();
        container.innerHTML = ''; // Xóa gợi ý cũ
        minutes.forEach(minute => {
            const option = document.createElement('div');
            option.className = 'suggestion-item';
            option.innerText = minute + ' phút';
            option.onclick = () => {
                const selectedHour = input.dataset.selectedHour || '00';
                input.value = selectedHour + ':' + minute;
				// Ẩn danh sách sau khi chọn phút
                container.style.display = 'none';
            };
            container.appendChild(option);
        });
		// Hiển thị danh sách
        container.style.display = 'block';
    }
    // Ẩn danh sách gợi ý khi nhấp ra ngoài
    document.addEventListener('click', function(event) {
        const isInput = event.target.classList.contains('time-input');
        const isSuggestion = event.target.classList.contains('suggestion-item');
        if (!isInput && !isSuggestion) {
            document.querySelectorAll('.suggestions-list').forEach(list => {
                list.style.display = 'none';
            });
        }
    });
</script>

<!-- Scripts lập Lịch Màn Hình -->
<script>
    let timeOnCounter_display_screen = <?= count($display_screen['time_on']) ?>;
    let timeOffCounter_display_screen = <?= count($display_screen['time_off']) ?>;
    // Thêm input cho Time On
    document.getElementById('add-time-on_display_screen').addEventListener('click', function() {
        const timeOnContainer = document.getElementById('time-on-container');
        // Tạo id cho input mới và container
        const inputContainerId = 'time-on_display_screen-' + timeOnCounter_display_screen;
        // Sử dụng innerHTML để tạo input và button xóa
        const inputContainer = document.createElement('div');
        inputContainer.id = inputContainerId;
		inputContainer.classList.add('time-input-container', 'input-group', 'mb-3');
        inputContainer.innerHTML = '<input class="form-control border-success" type="text" name="time_on_display_screen[]" placeholder="HH:mm (Thời gian bật)"><button class="btn btn-danger border-success" title="Xóa thời gian Bật này" type="button" id="delete-on_display_screen-' + timeOnCounter_display_screen + '"><i class="bi bi-trash"></i></button>';
        // Thêm container vào trong DOM
        timeOnContainer.insertBefore(inputContainer, this);
        // Gắn sự kiện xóa khi nhấn Delete
        document.getElementById('delete-on_display_screen-' + timeOnCounter_display_screen).addEventListener('click', function() {
            document.getElementById(inputContainerId).remove();
        });
        timeOnCounter_display_screen++;
    });

    // Thêm input cho Time Off
    document.getElementById('add-time-off_display_screen').addEventListener('click', function() {
        const timeOffContainer = document.getElementById('time-off-container');
        // Tạo id cho input mới và container
        const inputContainerId = 'time-off_display_screen-' + timeOffCounter_display_screen;
        // Sử dụng innerHTML để tạo input và button xóa
        const inputContainer = document.createElement('div');
        inputContainer.id = inputContainerId;
		inputContainer.classList.add('time-input-container', 'input-group', 'mb-3');
        inputContainer.innerHTML = '<input class="form-control border-success" type="text" name="time_off_display_screen[]" placeholder="HH:mm (Thời gian tắt)"><button class="btn btn-danger border-success" title="Xóa thời gian Tắt này" type="button" id="delete-off_display_screen-' + timeOffCounter_display_screen + '"><i class="bi bi-trash"></i></button>';
        // Thêm container vào trong DOM
        timeOffContainer.insertBefore(inputContainer, this);
        // Gắn sự kiện xóa khi nhấn Delete
        document.getElementById('delete-off_display_screen-' + timeOffCounter_display_screen).addEventListener('click', function() {
            document.getElementById(inputContainerId).remove();
        });
        timeOffCounter_display_screen++;
    });
    // Gắn sự kiện xóa ban đầu cho các button đã có
    document.querySelectorAll('.time-inputs_display_screen > div > button').forEach(button => {
        button.addEventListener('click', function() {
            const container = button.parentElement;
            container.remove();
        });
    });
</script>
<!--END Scripts lập Lịch Màn Hình -->

<!-- Scripts Restart VBot -->
<script>
    let time_Restart_VBot = <?= count($restart_vbot['time']) ?>;
    // Thêm input cho Time On
    document.getElementById('add-time-restart_vbot').addEventListener('click', function() {
        const timeOnContainer = document.getElementById('time-on-restart_vbot');
        // Tạo id cho input mới và container
        const inputContainerId = 'time_restart_vbot-' + time_Restart_VBot;
        // Sử dụng innerHTML để tạo input và button xóa
        const inputContainer = document.createElement('div');
        inputContainer.id = inputContainerId;
		inputContainer.classList.add('time-input-restart_vbot', 'input-group', 'mb-3');
        inputContainer.innerHTML = '<input class="form-control border-success" type="text" name="time_restart_vbot[]" placeholder="HH:mm (Thời gian)"><button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-restart_vbot-' + time_Restart_VBot + '"><i class="bi bi-trash"></i></button>';
        // Thêm container vào trong DOM
        timeOnContainer.insertBefore(inputContainer, this);
        // Gắn sự kiện xóa khi nhấn Delete
        document.getElementById('delete-restart_vbot-' + time_Restart_VBot).addEventListener('click', function() {
            document.getElementById(inputContainerId).remove();
        });
        time_Restart_VBot++;
    });
    // Gắn sự kiện xóa ban đầu cho các button đã có
    document.querySelectorAll('.time-inputs_restart_vbot > div > button').forEach(button => {
        button.addEventListener('click', function() {
            const container = button.parentElement;
            container.remove();
        });
    });
</script>
<!--END Scripts Restart VBot -->


<!-- Scripts REBOOT OS SYSTEM -->
<script>
    let time_REboot_OS = <?= count($reboot_os['time']) ?>;
    // Thêm input cho Time On
    document.getElementById('add-time-reboot_os').addEventListener('click', function() {
        const timeOnContainer = document.getElementById('time-on-reboot_os');
        // Tạo id cho input mới và container
        const inputContainerId = 'time_reboot_os-' + time_REboot_OS;
        // Sử dụng innerHTML để tạo input và button xóa
        const inputContainer = document.createElement('div');
        inputContainer.id = inputContainerId;
		inputContainer.classList.add('time-input-reboot_os', 'input-group', 'mb-3');
        inputContainer.innerHTML = '<input class="form-control border-success" type="text" name="time_reboot_os[]" placeholder="HH:mm (Thời gian)"><button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-reboot_os-' + time_REboot_OS + '"><i class="bi bi-trash"></i></button>';
        // Thêm container vào trong DOM
        timeOnContainer.insertBefore(inputContainer, this);
        // Gắn sự kiện xóa khi nhấn Delete
        document.getElementById('delete-reboot_os-' + time_REboot_OS).addEventListener('click', function() {
            document.getElementById(inputContainerId).remove();
        });
        time_REboot_OS++;
    });
    // Gắn sự kiện xóa ban đầu cho các button đã có
    document.querySelectorAll('.time-inputs_reboot_os > div > button').forEach(button => {
        button.addEventListener('click', function() {
            const container = button.parentElement;
            container.remove();
        });
    });
</script>
<!--END Scripts REBOOT OS SYSTEM -->

  <script src="assets/vendor/prism/prism.min.js"></script>
<script src="assets/vendor/prism/prism-json.min.js"></script>
<?php
include 'html_js.php';
?>

</body>
</html>