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



    if (isset($_POST['notification_schedule'])) {
        $updated_schedule = [];

foreach ($_POST['notification_schedule'] as $task) {
	$task['active'] = isset($task['active']) ? (bool)$task['active'] : false;
	$task['data']['repeat'] = isset($task['data']['repeat']) && intval($task['data']['repeat']) > 0 ? intval($task['data']['repeat']) : 1;
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
        $successMessage[] = "Dữ liệu đã được lưu thành công.";
    }
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
<label for="message-<?= $index ?>" class="col-sm-3 col-form-label">Nội Dung Thông Báo <i class="bi bi-question-circle-fill" onclick="show_message('Cần nhập nội dung thông báo, nếu không nhập nội dung thì cần phải cấu hình nhập file âm thanh, bắt buộc phải có 1 trong 2 thì mới cho lưu dữ liệu (hệ thống sẽ ưu tiên phát thông báo văn bản, nếu văn bản trống thì sẽ phát âm thanh từ file)')"></i>:</label>
<div class="col-sm-9">
<textarea type="text" rows="3" class="form-control border-success" id="message-<?= $index ?>" name="notification_schedule[<?= $index ?>][data][message]"><?= htmlspecialchars($notification['data']['message']) ?></textarea>
</div>
</div>

<div class="row mb-3">
<label for="audio_file-<?= $index ?>" class="col-sm-3 col-form-label">Tệp Âm Thanh (Link,URL/PATH) <i class="bi bi-question-circle-fill" onclick="show_message('Cần nhập thông tin đường dẫn, link, url tới tệp âm thanh, nếu không nhập nội dung thì cần phải cấu hình nhập file âm thanh, bắt buộc phải có 1 trong 2 thì mới cho lưu dữ liệu (hệ thống sẽ ưu tiên phát thông báo văn bản, nếu văn bản trống thì sẽ phát âm thanh từ file)')"></i>:</label>
<div class="col-sm-9">
<input type="text" class="form-control border-success" id="audio_file-<?= $index ?>" name="notification_schedule[<?= $index ?>][data][audio_file]" placeholder="Hoặc đường dẫn Path, Link, Url đến âm thanh" value="<?= htmlspecialchars($notification['data']['audio_file']) ?>">
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

            // Lấy danh sách các ngày đã chọn từ JSON
            $selected_days = $notification['date']; // Mảng các ngày đã chọn

            // Kiểm tra xem có ngày tháng cụ thể trong dữ liệu hay không
            $specific_dates = array_filter($selected_days, function($day) {
                return preg_match('/\d{2}\/\d{2}\/\d{4}/', $day); // Kiểm tra xem giá trị có phải là ngày tháng (dd/mm/yyyy) không
            });
            $week_days_selected = array_diff($selected_days, $specific_dates); // Lọc bỏ các ngày tháng cụ thể, chỉ lấy các ngày trong tuần

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

<!-- Nghe thử file âm thanh 
<audio id="audioPlayer" style="display: none;" controls></audio>-->

  <!-- Template Main JS File -->
  
  
<script>
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

// Hàm Thêm Ngày
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
    // Đảm bảo nút "Thêm ngày" luôn nằm ở cuối cùng của container
    addAddButton(container, index); // Thêm nút "Thêm ngày" vào cuối container sau khi thêm ngày mới
}


// Hàm xóa ngày khi người dùng nhấn nút xóa
function removeDateInput(dateGroupId) {
    const dateGroup = document.getElementById('date-group-' + dateGroupId);
    if (dateGroup) {
        dateGroup.remove(); // Xóa thẻ div chứa input và nút xóa
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
        taskDiv.innerHTML = task.content;  // Phục hồi nội dung cũ
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


			"<div class='row mb-3'>" +
			"<label for='audio_file-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Tệp Âm Thanh (Link,URL/PATH):</label>" +
			"<div class='col-sm-9'>" +
			"<input class='form-control border-success' type='text' id='audio_file-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][data][audio_file]' placeholder='Hoặc đường dẫn Path, Link, Url đến âm thanh'>" +
			"</div>" +
			"</div>" +


			"<div class='row mb-3'>" +
			"<label for='repeat-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Số lần lặp lại:</label>" +
			"<div class='col-sm-9'>" +
			"<input required class='form-control border-success' type='number' id='repeat-" + newTaskIndex + "' min='1' step='1' max ='5' name='notification_schedule[" + newTaskIndex + "][data][repeat]' value='1'>" +
			"<div class='invalid-feedback'>Cần điền số lần lặp lại thông báo</div>" +
			"</div>" +
			"</div>" +

			"<div class='row mb-3'>" +
			"<label for='active-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Kích hoạt:</label>" +
			"<div class='col-sm-9'>" +
			"<div class='form-switch'>" +
			"<input type='checkbox' class='form-check-input' id='active-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][active]' checked>" +
			"</div>" +
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
        for (let minute = 0; minute < 60; minute += 1) { // Tăng mỗi 5 phút
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
            option.innerText = `${hour} giờ`;
            option.onclick = () => {
                input.dataset.selectedHour = hour; // Lưu giờ được chọn tạm thời
                input.value = `${hour}:--`; // Chỉ cập nhật giờ, giữ chỗ cho phút
                showMinuteSuggestions(input); // Chuyển sang chọn phút
            };
            container.appendChild(option);
        });
        container.style.display = 'block'; // Hiển thị danh sách
    }

    // Hiển thị danh sách gợi ý phút
    function showMinuteSuggestions(input) {
        const container = input.nextElementSibling;
        const minutes = generateMinuteSuggestions();
        container.innerHTML = ''; // Xóa gợi ý cũ
        minutes.forEach(minute => {
            const option = document.createElement('div');
            option.className = 'suggestion-item';
            option.innerText = `${minute} phút`;
            option.onclick = () => {
                const selectedHour = input.dataset.selectedHour || '00';
                input.value = `${selectedHour}:${minute}`; // Cập nhật giờ và phút đầy đủ
                container.style.display = 'none'; // Ẩn danh sách sau khi chọn phút
            };
            container.appendChild(option);
        });
        container.style.display = 'block'; // Hiển thị danh sách
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


  <script src="assets/vendor/prism/prism.min.js"></script>
<script src="assets/vendor/prism/prism-json.min.js"></script>
<?php
include 'html_js.php';
?>

</body>
</html>