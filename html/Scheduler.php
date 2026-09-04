<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
require_once __DIR__.'/includes/ActionRegistry.php';

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

//Fallback chỉ đọc lịch sử trực tiếp khi tiến trình/API VBot không hoạt động.
if (isset($_GET['scheduler_history_fallback'])) {
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  $limit = filter_var($_GET['limit'] ?? 50, FILTER_VALIDATE_INT, [
    'options' => ['default' => 50, 'min_range' => 1, 'max_range' => 300]
  ]);
  $schedule_data_file = $VBot_Offline . ltrim($Config['schedule']['data_json_file'], '/\\');
  $history_file = dirname($schedule_data_file) . DIRECTORY_SEPARATOR . 'Scheduler_History.json';
  $history = [];
  $message = 'Chưa có dữ liệu lịch sử Scheduler';
  if (is_file($history_file) && is_readable($history_file)) {
    $history_json = file_get_contents($history_file);
    $decoded_history = $history_json !== false ? json_decode($history_json, true) : null;
    if (!is_array($decoded_history)) {
      http_response_code(500);
      echo json_encode(['success' => false, 'data' => [], 'source' => 'file', 'message' => 'Scheduler_History.json không chứa JSON hợp lệ'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      exit;
    }
    $history = array_slice($decoded_history, -$limit);
    $message = 'Đã đọc lịch sử trực tiếp từ Scheduler_History.json';
  }
  echo json_encode(['success' => true, 'data' => $history, 'source' => 'file', 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
$Schedule_Audio_dir = $VBot_Offline . $Config['schedule']['audio_path'];
if (!file_exists($Schedule_Audio_dir)) {
  if (mkdir($Schedule_Audio_dir, 0777, true)) {
    //echo "Đã tạo thư mục: $Schedule_Audio_dir\n";
  }
  shell_exec("chmod 777 " . escapeshellarg($Schedule_Audio_dir));
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

    .scheduler-toolbar {
      position: sticky;
      top: 64px;
      z-index: 900;
      padding: .75rem;
      margin-bottom: 1rem;
      border: 1px solid rgba(13, 110, 253, .22);
      border-radius: .75rem;
      background: rgba(244, 248, 255, .96);
      box-shadow: 0 .25rem .75rem rgba(18, 38, 63, .08);
      backdrop-filter: blur(6px);
    }

    .scheduler-search-hidden {
      display: none !important;
    }

    #scheduler-search-empty {
      display: none;
    }

    @media (max-width: 991.98px) {
      .scheduler-toolbar {
        top: 60px;
      }
    }
  </style>
</head>

<body>
  <?php
  include 'html_header_bar.php';
  include 'html_sidebar.php';
  ?>
  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Lên Lịch: Báo Thức, Lời Nhắc, Thông Báo (Scheduler) <i class="bi bi-question-circle-fill" onclick="show_message('Để Bật hoặc Tắt sử dụng cần thiết lập trong tab <b>Cấu Hình Config</b>')"></i></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">Lên lịch, tác vụ, lời nhắc, thông báo</li>
          &nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['schedule']['active'] ? '<p class="text-success" title="Schedule đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Schedule không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
        </ol>
      </nav>
    </div>
    <form method="POST" class="row g-3 needs-validation" action="" enctype="multipart/form-data" novalidate onsubmit="return validateFormVBot()">
      <?php
      $json_file = $VBot_Offline . $Config['schedule']['data_json_file'];
      //Mảng lưu thông báo lỗi
      $errorMessages = [];
      $successMessage = [];
		function vbotSchedulerSetFullPermissions($path, $label) {
			global $ssh_host, $ssh_port, $ssh_user, $ssh_password;
			clearstatcache(true, $path);
			$currentPermissions = @fileperms($path);
			if ($currentPermissions !== false && (($currentPermissions & 0777) === 0777)) {
				return true;
			}
			if (@chmod($path, 0777)) {
				return true;
			}
			if (
				function_exists('ssh2_connect') &&
				function_exists('ssh2_sftp') &&
				function_exists('ssh2_sftp_chmod')
			) {
				$connection = @ssh2_connect($ssh_host, intval($ssh_port));
				if ($connection && @ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
					$sftp = @ssh2_sftp($connection);
					if ($sftp && @ssh2_sftp_chmod($sftp, $path, 0777)) {
						return true;
					}
				}
			}
			error_log('Scheduler: không thể đặt quyền 0777 cho ' . $label . ': ' . $path);
			return false;
		}
		function vbotSchedulerUploadedAudioIsValid($temporaryFile, $extension) {
			if (!is_uploaded_file($temporaryFile)) return false;
			$allowedMimes = [
				'mp3' => ['audio/mpeg', 'audio/mp3', 'application/octet-stream'],
				'wav' => ['audio/wav', 'audio/x-wav', 'audio/vnd.wave', 'application/octet-stream'],
				'flac' => ['audio/flac', 'audio/x-flac', 'application/octet-stream'],
				'ogg' => ['audio/ogg', 'application/ogg', 'application/octet-stream'],
				'aac' => ['audio/aac', 'audio/x-aac', 'application/octet-stream'],
			];
			$extension = strtolower($extension);
			if (!isset($allowedMimes[$extension])) return false;
			$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
			if ($finfo === false) return true;
			$mime = finfo_file($finfo, $temporaryFile);
			finfo_close($finfo);
			return is_string($mime) && in_array(strtolower($mime), $allowedMimes[$extension], true);
		}

		function generate_audio_select($directories, $field_name, $selected_value = '') {
			if (!is_array($directories)) {
				$directories = [$directories];
			}
			$audioFiles = [];
			foreach ($directories as $directory) {
				if (!is_dir($directory)) {
					echo "<p style='color: red;'>Thư mục $directory không tồn tại.</p>";
					continue;
				}
				// Lọc các tệp âm thanh
				$files = array_filter(scandir($directory), function ($file) use ($directory) {
					return preg_match('/\.(mp3|wav|flac|ogg|aac)$/i', $file) && !is_dir($directory . '/' . $file);
				});
				foreach ($files as $file) {
					$audioFiles[] = $directory . '/' . $file;
				}
			}
			echo '<select class="form-select border-success" name="' . htmlspecialchars($field_name) . '" id="' . htmlspecialchars($field_name) . '">';
			echo '<option value="">Chọn tệp âm thanh</option>';
			foreach ($audioFiles as $audioFile) {
				$selected = ($audioFile == $selected_value) ? 'selected' : '';
				$baseName = basename($audioFile);
				echo '<option value="' . htmlspecialchars($audioFile) . '" ' . $selected . '>' . htmlspecialchars($baseName) . '</option>';
			}
			echo '</select>';
		}

        function render_system_scheduler_options($task_key, $task_data, $label, $time_container_id, $date_field_name = null, $input_prefix = null) {
          $recurrence = $task_data['recurrence'] ?? ['type' => 'legacy'];
          $conditions = $task_data['conditions'] ?? ['mode' => 'always'];
          $type = $recurrence['type'] ?? 'legacy';
          $prefix = $input_prefix ?: ('system_schedule_options[' . $task_key . ']');
          $types = ['legacy'=>'Theo thứ trong tuần','daily'=>'Hằng ngày','daily_months'=>'Hằng ngày (Lựa chọn tháng)','weekdays'=>'Ngày làm việc (Thứ Hai–Thứ Sáu)','weekends'=>'Cuối tuần (Thứ Bảy–Chủ Nhật)','once'=>'Chỉ chạy một lần','monthly'=>'Một ngày mỗi tháng','days_of_month'=>'N ngày trong tháng','interval_days'=>'Chu kỳ N ngày'];
          echo '<div class="row mb-3">';
          echo '<label class="col-sm-3 col-form-label">Chế độ chạy:</label>';
          echo '<div class="col-sm-9">';
          echo '<select class="form-select border-success scheduler-recurrence-type" data-scheduler-index="system-'.htmlspecialchars($task_key).'" data-time-container-id="'.htmlspecialchars($time_container_id).'" name="'.$prefix.'[recurrence][type]">';
          foreach ($types as $value => $text) echo '<option value="'.$value.'" '.($type === $value ? 'selected' : '').'>'.$text.'</option>';
          echo '</select>';
          echo '<div class="input-group mt-2 scheduler-recurrence-fields" data-recurrence-for="weekdays weekends monthly interval_days"><span class="input-group-text border-success">Bắt đầu</span><input type="date" class="form-control border-success" name="'.$prefix.'[recurrence][start_date]" value="'.htmlspecialchars($recurrence['start_date'] ?? '').'"><span class="input-group-text border-success">Kết thúc</span><input type="date" class="form-control border-success" name="'.$prefix.'[recurrence][end_date]" value="'.htmlspecialchars($recurrence['end_date'] ?? '').'"></div>';
          echo '<div class="input-group mt-2 scheduler-recurrence-fields" data-recurrence-for="once"><span class="input-group-text border-success">Ngày thực hiện</span><input type="date" class="form-control border-success" name="'.$prefix.'[recurrence][date]" value="'.htmlspecialchars($recurrence['date'] ?? '').'"></div>';
          echo '<div class="input-group mt-2 scheduler-recurrence-fields" data-recurrence-for="monthly"><span class="input-group-text border-success">Ngày trong tháng</span><input type="number" min="1" max="31" class="form-control border-success" name="'.$prefix.'[recurrence][day]" value="'.intval($recurrence['day'] ?? 1).'"></div>';
          echo '<div class="input-group mt-2 scheduler-recurrence-fields" data-recurrence-for="interval_days"><span class="input-group-text border-success">Lặp mỗi</span><input type="number" min="1" max="365" class="form-control border-success" name="'.$prefix.'[recurrence][interval]" value="'.intval($recurrence['interval'] ?? 1).'"><span class="input-group-text border-success">ngày</span></div>';
          echo '<div class="mt-2 scheduler-recurrence-fields" data-recurrence-for="daily_months"><div class="border rounded p-2">Chọn tháng áp dụng:<br/>';
          $months = $recurrence['months'] ?? range(1, 12); foreach (range(1, 12) as $month) echo '<label class="me-3"><input type="checkbox" class="form-check-input border-success" name="'.$prefix.'[recurrence][months][]" value="'.$month.'" '.(in_array($month, $months) ? 'checked' : '').'> Tháng '.$month.'</label>';
          echo '</div></div><div class="mt-2 scheduler-recurrence-fields" data-recurrence-for="days_of_month"><div class="border rounded p-2">Chọn các ngày trong tháng:<br/>';
          $days = $recurrence['days'] ?? [1]; foreach (range(1, 31) as $day) echo '<label class="me-3"><input type="checkbox" class="form-check-input border-success" name="'.$prefix.'[recurrence][days][]" value="'.$day.'" '.(in_array($day, $days) ? 'checked' : '').'> '.$day.'</label>';
          echo '</div><small class="text-muted">Tháng không có ngày đã chọn (ví dụ ngày 31 trong tháng 2) sẽ tự bỏ qua.</small></div>';
          echo '</div></div>';
          echo '<div class="row mb-3">';
          echo '<label class="col-sm-3 col-form-label">Điều kiện chạy:</label>';
          echo '<div class="col-sm-9">';
          $condition_mode = $conditions['mode'] ?? 'always';
          echo '<select class="form-select border-primary scheduler-condition-mode" data-scheduler-index="system-'.htmlspecialchars($task_key).'" name="'.$prefix.'[conditions][mode]"><option value="always" '.($condition_mode === 'always' ? 'selected' : '').'>Luôn thực thi</option><option value="conditional" '.($condition_mode === 'conditional' ? 'selected' : '').'>Chỉ thực thi khi thỏa mãn điều kiện</option></select>';
          echo '<div class="scheduler-condition-fields mt-2" data-scheduler-index="system-'.htmlspecialchars($task_key).'">';
          echo '<select class="form-select mb-2 border-success" name="'.$prefix.'[conditions][mic_state]"><option value="any">Không phụ thuộc mic</option><option value="on" '.(($conditions['mic_state'] ?? '') === 'on' ? 'selected' : '').'>Chỉ khi mic được bật</option><option value="off" '.(($conditions['mic_state'] ?? '') === 'off' ? 'selected' : '').'>Chỉ khi mic được tắt</option></select>';
          foreach (['only_when_idle'=>'Chỉ khi VBot rảnh','skip_if_media_playing'=>'Bỏ qua khi đang phát Media Player','skip_if_bluetooth_playing'=>'Bỏ qua khi đang phát Bluetooth','skip_if_airplay_playing'=>'Bỏ qua khi đang phát AirPlay'] as $key => $text) echo '<label class="me-3"><input type="checkbox" class="form-check-input border-success" name="'.$prefix.'[conditions]['.$key.']" '.(!empty($conditions[$key]) ? 'checked' : '').'> '.$text.'</label>';
          echo '</div></div></div>';
          echo '<div class="row mb-3 scheduler-legacy-dates" data-scheduler-index="system-'.htmlspecialchars($task_key).'">';
          echo '<label class="col-sm-3 col-form-label">Các thứ trong tuần</label>';
          echo '<div class="col-sm-9"><div class="form-switch">';
          $selected_dates = is_array($task_data['date'] ?? null) ? $task_data['date'] : [];
          $date_input_name = $date_field_name ? htmlspecialchars($date_field_name) : 'dates_' . htmlspecialchars($task_key);
          foreach ($GLOBALS['week_days'] ?? [] as $date => $week_label) {
            echo '<input class="form-check-input border-success" type="checkbox" name="' . $date_input_name . '[]" value="' . htmlspecialchars($date) . '" ' . (in_array($date, $selected_dates) ? 'checked' : '') . '> ';
            echo '<label>' . htmlspecialchars($week_label) . '</label><br />';
          }
          echo '</div></div></div>';
          echo '<div class="row mb-3">';
          echo '<label class="col-sm-3 col-form-label">Thời gian tối đa (giây/s) <i class="bi bi-question-circle-fill" onclick="show_message(\'Là khoảng thời gian lâu nhất mà một tác vụ thông báo được phép phát. Tác vụ được chạy tối đa 60 giây. Khi hết 60 giây mà TTS, file âm thanh hoặc URL media vẫn chưa kết thúc, Scheduler sẽ tự dừng tác vụ và ghi lịch sử trạng thái. Mặc định đặt là 0 => không giới hạn thời gian, chờ tác vụ phát xong.\')"></i>:</label>';
          echo '<div class="col-sm-9"><div class="input-group"><span class="input-group-text">Thời gian</span><input type="number" min="0" max="86400" class="form-control" name="'.$prefix.'[max_duration_seconds]" value="'.intval($task_data['max_duration_seconds'] ?? 0).'"><span class="input-group-text">giây/s</span></div><small class="text-muted">Đơn vị giây, đặt 0 để không giới hạn, chờ tác vụ phát xong.</small></div></div>';
        }
      $directory = dirname($json_file);
      if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
        vbotSchedulerSetFullPermissions($directory, 'thư mục dữ liệu Scheduler');
      }
      if (!file_exists($json_file)) {
        $default_data = [
          'notification_schedule' => []
        ];
        vbotAtomicWriteFile($json_file, json_encode($default_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'khởi tạo dữ liệu Scheduler');
        vbotSchedulerSetFullPermissions($json_file, 'tệp dữ liệu Scheduler');
      }
      $json_data = file_get_contents($json_file);
      $data = json_decode($json_data, true);
      if ($data === null) {
        echo "<center><h1 class='text-danger'>Không thể đọc dữ liệu JSON từ tệp, Vui lòng kiểm tra lại định dạng tệp json: $json_file</h1></center>";
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
          $default_data = ['notification_schedule' => []];
          $default_json = json_encode($default_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          if ($default_json !== false && vbotAtomicWriteFile($json_file, $default_json, 'xóa dữ liệu Scheduler')) {
              vbotSchedulerSetFullPermissions($json_file, 'tệp dữ liệu Scheduler');
              $successMessage[] = "Toàn bộ dữ liệu cấu hình đã được xóa thành công";
              echo '<script>window.location.href = "Scheduler.php";</script>';
              exit;
          } else {
            $errorMessages[] = "Không thể tạo lại tệp dữ liệu Scheduler mặc định.";
          }
        } else {
          $errorMessages[] = "Tệp dữ liệu không tồn tại.";
        }
      }
      if (isset($_POST['Scheduler_Upload_Audio_Submit'])) {
        $file_save_directory = $VBot_Offline . $Config['schedule']['audio_path'];
        // Kiểm tra và tạo thư mục, thiết lập quyền
        /*
          	if (!file_exists($file_save_directory)) {
          		if (!mkdir($file_save_directory, 0777, true)) {
          			$errorMessages[] = 'Không thể tạo thư mục ' . $file_save_directory . '. Vui lòng kiểm tra quyền thư mục.';
          		}
          	}
			if (!vbotSchedulerSetFullPermissions($file_save_directory, 'thư mục âm thanh Scheduler')) {
          		$errorMessages[] = 'Không thể thiết lập quyền cho thư mục ' . $file_save_directory . '. Vui lòng kiểm tra quyền hệ thống.';
          	}
			*/
        // Kiểm tra và tạo thư mục, thiết lập quyền
        if (!file_exists($file_save_directory)) {
          // Tạo thư mục bằng exec thay vì mkdir
          exec("mkdir -p " . escapeshellarg($file_save_directory) . " 2>&1", $output_mkdir, $return_mkdir);
          if ($return_mkdir !== 0) {
            $errorMessages[] = 'Không thể tạo thư mục ' . $file_save_directory . '. Chi tiết: ' . implode("\n", $output_mkdir);
          }
        }

        // Thiết lập quyền bằng exec thay vì chmod
        exec("chmod -R 0777 " . escapeshellarg($file_save_directory) . " 2>&1", $output_chmod, $return_chmod);
        if ($return_chmod !== 0) {
          $errorMessages[] = 'Không thể thiết lập quyền cho thư mục ' . $file_save_directory . '. Chi tiết: ' . implode("\n", $output_chmod);
        }

        $data_recovery_type = $_POST['Scheduler_Upload_Audio_Submit'];
        if ($data_recovery_type === "Scheduler_Upload_Audio") {
          $uploadOk = 1;
          $errorMessages = [];
          $successMessage = [];
          // Kiểm tra xem tệp có được gửi không
          if (isset($_FILES["fileToUpload_Scheduler_Upload_Audio"])) {
            $fileName = basename($_FILES["fileToUpload_Scheduler_Upload_Audio"]["name"]);
            $fileName = preg_replace('/[^\p{L}\p{N} ._()-]/u', '_', $fileName);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileSize = (int)($_FILES["fileToUpload_Scheduler_Upload_Audio"]["size"] ?? 0);
            $fileTargetPath = $file_save_directory . "/" . $fileName; // Đường dẫn đầy đủ
            // Kiểm tra định dạng tệp (chỉ chấp nhận tệp âm thanh như mp3, wav, etc.)
            if ($_FILES["fileToUpload_Scheduler_Upload_Audio"]["error"] !== UPLOAD_ERR_OK) {
              $errorMessages[] = "- Tệp âm thanh tải lên gặp lỗi, mã lỗi: " . intval($_FILES["fileToUpload_Scheduler_Upload_Audio"]["error"]);
              $uploadOk = 0;
            } elseif ($fileSize <= 0 || $fileSize > 104857600) {
              $errorMessages[] = "- Tệp âm thanh không hợp lệ hoặc vượt quá 100 MB";
              $uploadOk = 0;
            } elseif (!preg_match('/\.(mp3|wav|flac|ogg|aac)$/i', $fileName)) {
              $errorMessages[] = "- Chỉ chấp nhận các định dạng tệp âm thanh (mp3, wav, flac, ogg, aac)";
              $uploadOk = 0;
            }
            if ($uploadOk === 1 && !vbotSchedulerUploadedAudioIsValid(
              $_FILES["fileToUpload_Scheduler_Upload_Audio"]["tmp_name"],
              $fileExtension
            )) {
              $errorMessages[] = "- Nội dung tệp không khớp với định dạng âm thanh đã chọn";
              $uploadOk = 0;
            }
            if ($uploadOk == 0) {
              $errorMessages[] = "- Tệp âm thanh không được tải lên.";
            } else {
              // Di chuyển tệp vào thư mục đích
              if (move_uploaded_file($_FILES["fileToUpload_Scheduler_Upload_Audio"]["tmp_name"], $fileTargetPath)) {
                vbotSchedulerSetFullPermissions($fileTargetPath, 'tệp âm thanh Scheduler');
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

	// Khôi Phục Dữ liệu bằng tải lên hoặc tệp hệ thống
	if (isset($_POST['start_recovery_Scheduler'])) {
		$data_recovery_type = $_POST['start_recovery_Scheduler'];
		if ($data_recovery_type === "khoi_phuc_tu_tep_tai_len") {
			$uploadOk = 1;
			if (
				!isset($_FILES["fileToUpload_Scheduler_restore"]) ||
				$_FILES["fileToUpload_Scheduler_restore"]["error"] === UPLOAD_ERR_NO_FILE ||
				empty($_FILES["fileToUpload_Scheduler_restore"]["name"])
			) {
				$errorMessages[] = "- Tệp chưa được chọn để tải lên khôi phục dữ liệu Scheduler";
				$uploadOk = 0;
			}
			if ($uploadOk === 1) {
				$fileName = basename($_FILES["fileToUpload_Scheduler_restore"]["name"]);
				$fileSize = (int)($_FILES["fileToUpload_Scheduler_restore"]["size"] ?? 0);
				if ($_FILES["fileToUpload_Scheduler_restore"]["error"] !== UPLOAD_ERR_OK) {
					$errorMessages[] = "- Tệp Scheduler tải lên gặp lỗi, mã lỗi: " . intval($_FILES["fileToUpload_Scheduler_restore"]["error"]);
					$uploadOk = 0;
				} elseif (!is_uploaded_file($_FILES["fileToUpload_Scheduler_restore"]["tmp_name"]) || $fileSize <= 0 || $fileSize > 10485760) {
					$errorMessages[] = "- Tệp Scheduler không hợp lệ hoặc vượt quá 10 MB";
					$uploadOk = 0;
				} elseif (!preg_match('/\.json$/i', $fileName)) {
					$errorMessages[] = "- Chỉ chấp nhận tệp .json cho Scheduler";
					$uploadOk = 0;
				}
			}
			if ($uploadOk === 1) {
				$jsonContent = file_get_contents($_FILES["fileToUpload_Scheduler_restore"]["tmp_name"]);
				$data = json_decode($jsonContent, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					$errorMessages[] = "- Nội dung tệp JSON không hợp lệ";
					$uploadOk = 0;
				} else {
					$requiredKeys = ['restart_vbot', 'stop_media_player', 'change_led_brightness'];
					foreach ($requiredKeys as $key) {
						if (!isset($data[$key]) || !is_array($data[$key])) {
							$errorMessages[] =
								"- Tệp Scheduler không đúng cấu trúc (thiếu key: {$key})";
							$uploadOk = 0;
							break;
						}
					}
				}
			}
			if ($uploadOk === 1) {
				if (vbotAtomicWriteFile($json_file, $jsonContent, 'khôi phục Scheduler từ tệp tải lên')) {
					$successMessage[] =
						"- Tệp " . htmlspecialchars($fileName) .
						" đã được tải lên và khôi phục dữ liệu Scheduler thành công";
					echo '<script>alert("Đã khôi phục dữ liệu Scheduler từ tệp tải lên thành công");</script>';
					echo '<script>window.location.href = "Scheduler.php";</script>';
				} else {
					$errorMessages[] = "- Có lỗi xảy ra khi tải lên tệp sao lưu Scheduler";
				}
			} else {
				$errorMessages[] = "- Tệp sao lưu Scheduler không hợp lệ, không thể khôi phục";
			}
		}
		else if ($data_recovery_type === "khoi_phuc_file_he_thong") {
			$start_recovery_custom_hass = $_POST['backup_scheduler_json_files'];
			if (!empty($start_recovery_custom_hass)) {
				if (file_exists($start_recovery_custom_hass)) {
					$jsonContent = file_get_contents($start_recovery_custom_hass);
					$data = json_decode($jsonContent, true);
					if (json_last_error() !== JSON_ERROR_NONE || !isset($data['restart_vbot']) || !isset($data['stop_media_player']) || !isset($data['change_led_brightness'])) {
						$errorMessages[] = "- Tệp Scheduler hệ thống không đúng cấu trúc dữ liệu";
					} else {
						if (vbotAtomicWriteFile($json_file, $jsonContent, 'khôi phục Scheduler từ bản sao hệ thống')) {
							$successMessage[] = "Đã khôi phục dữ liệu Scheduler từ tệp sao lưu trên hệ thống thành công";
							echo '<script>alert("Đã khôi phục dữ liệu Scheduler từ tệp sao lưu trên hệ thống thành công");</script>';
							echo '<script>window.location.href = "Scheduler.php";</script>';
						} else {
							$errorMessages[] = "Lỗi xảy ra khi ghi dữ liệu Scheduler đã khôi phục.";
						}
					}
				} else {
					$errorMessages[] = "Lỗi: Tệp " . basename($start_recovery_custom_hass) . " không tồn tại trên hệ thống";
				}
			} else {
				$errorMessages[] = "Không có tệp Scheduler nào được chọn để khôi phục!";
			}
		}
		
	}

      //Xử lý dữ liệu sau khi người dùng gửi form
      if (isset($_POST['save_all_Scheduler'])) {
        #Sao Lưu Dữ Liệu Trước
        if (isset($Config['backup_upgrade']['scheduler']['active']) && $Config['backup_upgrade']['scheduler']['active'] === true) {
          //Đường dẫn gốc và đích
          $sourceFile = $VBot_Offline . $Config['schedule']['data_json_file'];
          $destinationDir = $directory_path . '/' . $Config['backup_upgrade']['scheduler']['backup_path'];
          $destinationFile = $destinationDir . "/Data_Schedule_" . date('dmY_His') . ".json";
          //Kiểm tra xem thư mục đích có tồn tại hay không, nếu không thì tạo mới
          if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0777, true);
            vbotSchedulerSetFullPermissions($destinationDir, 'thư mục backup Scheduler');
            $successMessage[] = "- Tạo thư mục sao lưu thành công: <b>$destinationDir</b>";
          }
          //Sao chép tệp mới
          if (copy($sourceFile, $destinationFile)) {
            vbotSchedulerSetFullPermissions($destinationFile, 'tệp backup Scheduler');
            //$successMessage[] = "Tệp đã được sao chép thành công đến $destinationFile";
            //Lấy danh sách các tệp .json trong thư mục đích, sắp xếp theo thời gian tạo (cũ nhất trước)
            $jsonFiles = glob($destinationDir . "/*.json");
            usort($jsonFiles, function ($a, $b) {
              return filemtime($a) - filemtime($b);
            });
            //Xóa các tệp cũ nhất nếu số lượng tệp vượt quá 5
            if (count($jsonFiles) > $Config['backup_upgrade']['scheduler']['limit_backup_files']) {
              foreach (array_slice($jsonFiles, 0, count($jsonFiles) - $Config['backup_upgrade']['scheduler']['limit_backup_files']) as $oldFile) {
                unlink($oldFile);
                $successMessage[] = "Vượt quá số lượng tệp tin Backup là <b>" . $Config['backup_upgrade']['scheduler']['limit_backup_files'] . "</b>, đã xóa tệp: <b>" . basename($oldFile) . "</b>";
              }
            }
          } else {
            $errorMessages[] = "- Xảy ra Lỗi, Không thể sao lưu tệp: <b>$sourceFile</b>";
          }
        }

        #Lưu dữ liệu Lập Lịch, Thông Báo
        // Khi xóa tác vụ cuối cùng, trình duyệt không gửi notification_schedule.
        // Vì vậy luôn coi trường không tồn tại là một danh sách rỗng để việc xóa được lưu.
        $submitted_schedule = $_POST['notification_schedule'] ?? [];
        $updated_schedule = [];
        if (is_array($submitted_schedule)) {
          foreach ($submitted_schedule as $task) {
            if (!is_array($task)) {
              continue;
            }

            $task['data'] = isset($task['data']) && is_array($task['data']) ? $task['data'] : [];
            $task['active'] = isset($task['active']);
            $task['create_words'] = !empty($task['create_words']) ? $task['create_words'] : 'vbot_interface';
            $task['data']['repeat'] = isset($task['data']['repeat']) && intval($task['data']['repeat']) > 0 ? intval($task['data']['repeat']) : 1;
            $task['data']['message'] = isset($task['data']['message']) ? trim($task['data']['message']) : '';
            $task['data']['audio_file'] = isset($task['data']['audio_file']) ? trim($task['data']['audio_file']) : '';
            $task['data']['max_duration_seconds'] = isset($task['data']['max_duration_seconds'])
              ? max(0, min(86400, intval($task['data']['max_duration_seconds']))) : 0;
            $allowed_recurrence = ['legacy', 'daily', 'daily_months', 'weekdays', 'weekends', 'once', 'monthly', 'days_of_month', 'interval_days'];
            $task['recurrence'] = isset($task['recurrence']) && is_array($task['recurrence']) ? $task['recurrence'] : [];
            $recurrence_type = $task['recurrence']['type'] ?? 'legacy';
            $task['recurrence']['type'] = in_array($recurrence_type, $allowed_recurrence, true) ? $recurrence_type : 'legacy';
            $task['recurrence']['start_date'] = trim($task['recurrence']['start_date'] ?? '');
            $task['recurrence']['end_date'] = trim($task['recurrence']['end_date'] ?? '');
            $task['recurrence']['date'] = trim($task['recurrence']['date'] ?? '');
            $task['recurrence']['day'] = max(1, min(31, intval($task['recurrence']['day'] ?? 1)));
            $task['recurrence']['interval'] = max(1, min(365, intval($task['recurrence']['interval'] ?? 1)));
            $task['recurrence']['months'] = isset($task['recurrence']['months']) && is_array($task['recurrence']['months'])
              ? array_values(array_unique(array_filter(array_map('intval', $task['recurrence']['months']), function ($month) { return $month >= 1 && $month <= 12; })))
              : [];
            $task['recurrence']['days'] = isset($task['recurrence']['days']) && is_array($task['recurrence']['days'])
              ? array_values(array_unique(array_filter(array_map('intval', $task['recurrence']['days']), function ($day) { return $day >= 1 && $day <= 31; })))
              : [];
            if ($task['recurrence']['type'] === 'daily_months' && empty($task['recurrence']['months'])) {
              $task['recurrence']['months'] = range(1, 12);
            }
            if ($task['recurrence']['type'] === 'days_of_month' && empty($task['recurrence']['days'])) {
              $task['recurrence']['days'] = [1];
            }
            $conditions = isset($task['conditions']) && is_array($task['conditions']) ? $task['conditions'] : [];
            $legacy_has_conditions = (($conditions['mic_state'] ?? 'any') !== 'any') ||
              !empty($conditions['only_when_idle']) || !empty($conditions['skip_if_media_playing']) ||
              !empty($conditions['skip_if_bluetooth_playing']) || !empty($conditions['skip_if_airplay_playing']);
            $condition_mode = $conditions['mode'] ?? ($legacy_has_conditions ? 'conditional' : 'always');
            $task['conditions'] = [
              'mode' => ($condition_mode === 'conditional') ? 'conditional' : 'always',
              'mic_state' => in_array(($conditions['mic_state'] ?? 'any'), ['any', 'on', 'off'], true) ? $conditions['mic_state'] : 'any',
              'only_when_idle' => isset($conditions['only_when_idle']),
              'skip_if_media_playing' => isset($conditions['skip_if_media_playing']),
              'skip_if_bluetooth_playing' => isset($conditions['skip_if_bluetooth_playing']),
              'skip_if_airplay_playing' => isset($conditions['skip_if_airplay_playing'])
            ];
            $task['time'] = isset($task['time']) && is_array($task['time'])
              ? array_values(array_filter(array_map('trim', $task['time']), function ($time) {
                  return $time !== '';
                }))
              : [];
            if ($task['recurrence']['type'] === 'once' && count($task['time']) > 1) {
              $task['time'] = array_slice($task['time'], 0, 1);
            }
            $task['date'] = isset($task['date']) && is_array($task['date'])
              ? array_values(array_filter(array_map('trim', $task['date']), function ($date) {
                  return $date !== '';
                }))
              : [];

            //Chỉ lưu tác vụ có đủ dữ liệu bắt buộc.
            if (
              !empty($task['name']) &&
              !empty($task['time']) &&
              (!empty($task['date']) || $task['recurrence']['type'] !== 'legacy') &&
              ($task['data']['message'] !== '' || $task['data']['audio_file'] !== '')
            ) {
              $updated_schedule[] = $task;
            }
          }
        }
        $data['notification_schedule'] = $updated_schedule;

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

        #Lưu dữ liệu thay đổi âm lượng
        $times_change_volume = $_POST['time_change_volume'] ?? [];
        $volumes_change_volume = $_POST['volumes_volume_time'] ?? [];
        $filtered_times = [];
        $filtered_volumes = [];
        // Duyệt từng cặp và lọc nếu cả 2 đều hợp lệ
        foreach ($times_change_volume as $index => $time) {
          $time = trim($time);
          $volume = trim($volumes_change_volume[$index] ?? '');
          if ($time !== '' && $volume !== '' && is_numeric($volume)) {
            $filtered_times[] = $time;
            $filtered_volumes[] = $volume;
          }
        }
        $data['change_volume']['date'] = isset($_POST['dates_change_volume']) ? $_POST['dates_change_volume'] : [];
        $data['change_volume']['active'] = isset($_POST['change_volume_active']) ? true : false;
        $data['change_volume']['time'] = $filtered_times;
        $data['change_volume']['volume_time'] = $filtered_volumes;

        #Lưu dữ liệu thay đổi Độ Sáng LED
        $times_change_led_brightness = $_POST['time_change_brightness'] ?? [];
        $brightness_change_led_brightness = $_POST['brightness_brightnes_time'] ?? [];
        $filtered_times_brightness = [];
        $filtered_brightness = [];
        // Duyệt từng cặp và lọc nếu cả 2 đều hợp lệ
        foreach ($times_change_led_brightness as $index => $time_led_brightness) {
          $time_led_brightness = trim($time_led_brightness);
          $led_brightness = trim($brightness_change_led_brightness[$index] ?? '');
          if ($time_led_brightness !== '' && $led_brightness !== '' && is_numeric($led_brightness)) {
            $filtered_times_brightness[] = $time_led_brightness;
            $filtered_brightness[] = $led_brightness;
          }
        }
        $data['change_led_brightness']['date'] = isset($_POST['dates_changes_brightness']) ? $_POST['dates_changes_brightness'] : [];
        $data['change_led_brightness']['active'] = isset($_POST['change_led_brightness_active']) ? true : false;
        $data['change_led_brightness']['time'] = $filtered_times_brightness;
        $data['change_led_brightness']['brightness_time'] = $filtered_brightness;

		//LƯU LỊCH BẬT / TẮT MIC
		$mic_times   = $_POST['mic_on_off_time']   ?? [];
		$mic_actions = $_POST['mic_on_off_action'] ?? [];
		$filtered_times_mic   = [];
		$filtered_actions_mic = [];
		foreach ($mic_times as $index => $time) {
			$time   = trim($time);
			$action = trim($mic_actions[$index] ?? '');
			if ($time !== '' && preg_match('/^\d{2}:\d{2}$/', $time) && in_array($action, ['on', 'off'], true)) {
				$filtered_times_mic[]   = $time;
				$filtered_actions_mic[] = $action;
			}
		}
		$data['mic_on_off']['active'] = isset($_POST['change_mic_on_off_active']);
		$data['mic_on_off']['date'] = isset($_POST['mic_on_off_date']) ? array_values($_POST['mic_on_off_date']): [];
		$data['mic_on_off']['time']   = $filtered_times_mic;
		$data['mic_on_off']['action'] = $filtered_actions_mic;

        #Lưu dữ liệu dừng phát media Player
        $time_stop_media_player = isset($_POST['time_stop_media_player']) ? $_POST['time_stop_media_player'] : [];
        $data['stop_media_player']['time'] = array_filter($time_stop_media_player);
        $data['stop_media_player']['active'] = isset($_POST['stop_media_player_active']) ? true : false;
        $data['stop_media_player']['date'] = isset($_POST['dates_stop_media_player']) ? $_POST['dates_stop_media_player'] : [];

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

        #Lưu dữ liệu Phát danh sách nhạc: mỗi phần tử là một lịch độc lập.
        $posted_playlist_schedules = isset($_POST['playlist_schedules']) && is_array($_POST['playlist_schedules']) ? $_POST['playlist_schedules'] : [];
        $playlist_schedules = [];
        foreach ($posted_playlist_schedules as $schedule_index => $schedule_option) {
          if (!is_array($schedule_option)) continue;
          $posted_slots = isset($schedule_option['slots']) && is_array($schedule_option['slots']) ? $schedule_option['slots'] : [];
          // Tương thích dữ liệu form của bản ngay trước: nhiều giờ nhưng dùng chung một playlist_id.
          if (!$posted_slots) {
            $fallback_times = isset($schedule_option['time']) && is_array($schedule_option['time']) ? $schedule_option['time'] : [$schedule_option['time'] ?? ''];
            foreach ($fallback_times as $fallback_time) $posted_slots[] = ['time'=>$fallback_time, 'playlist_id'=>$schedule_option['playlist_id'] ?? ''];
          }
          $slots = [];
          foreach ($posted_slots as $slot) {
            if (!is_array($slot)) continue;
            $slot_time = trim((string)($slot['time'] ?? ''));
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $slot_time)) continue;
            $slot_playlist_id = trim((string)($slot['playlist_id'] ?? ''));
            if ($slot_playlist_id !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $slot_playlist_id)) $slot_playlist_id = '';
            $slots[] = ['time'=>$slot_time, 'playlist_id'=>$slot_playlist_id];
          }
          if (!$slots) continue;
          $recurrence = isset($schedule_option['recurrence']) && is_array($schedule_option['recurrence']) ? $schedule_option['recurrence'] : [];
          $allowed_types = ['legacy', 'daily', 'daily_months', 'weekdays', 'weekends', 'once', 'monthly', 'days_of_month', 'interval_days'];
          $recurrence_type = in_array(($recurrence['type'] ?? 'legacy'), $allowed_types, true) ? $recurrence['type'] : 'legacy';
          $months = isset($recurrence['months']) && is_array($recurrence['months']) ? array_values(array_filter(array_unique(array_map('intval', $recurrence['months'])), fn($value) => $value >= 1 && $value <= 12)) : [];
          $month_days = isset($recurrence['days']) && is_array($recurrence['days']) ? array_values(array_filter(array_unique(array_map('intval', $recurrence['days'])), fn($value) => $value >= 1 && $value <= 31)) : [];
          if ($recurrence_type === 'daily_months' && empty($months)) $months = range(1, 12);
          if ($recurrence_type === 'days_of_month' && empty($month_days)) $month_days = [1];
          $condition = isset($schedule_option['conditions']) && is_array($schedule_option['conditions']) ? $schedule_option['conditions'] : [];
          $playlist_schedules[] = [
            'id' => preg_match('/^[a-zA-Z0-9_-]{1,80}$/', (string)($schedule_option['id'] ?? '')) ? (string)$schedule_option['id'] : ('playlist_' . time() . '_' . intval($schedule_index)),
            'active' => isset($schedule_option['active']), 'slots' => $slots,
            'time' => array_column($slots, 'time'), 'playlist_id' => array_column($slots, 'playlist_id'),
            'date' => isset($schedule_option['date']) && is_array($schedule_option['date']) ? array_values(array_intersect($schedule_option['date'], array_keys($week_days))) : [],
            'recurrence' => ['type'=>$recurrence_type, 'start_date'=>trim($recurrence['start_date'] ?? ''), 'end_date'=>trim($recurrence['end_date'] ?? ''), 'date'=>trim($recurrence['date'] ?? ''), 'day'=>max(1,min(31,intval($recurrence['day'] ?? 1))), 'interval'=>max(1,min(365,intval($recurrence['interval'] ?? 1))), 'months'=>$months, 'days'=>$month_days],
            'conditions' => ['mode'=>(($condition['mode'] ?? 'always') === 'conditional' ? 'conditional' : 'always'), 'mic_state'=>in_array(($condition['mic_state'] ?? 'any'), ['any','on','off'], true) ? $condition['mic_state'] : 'any', 'only_when_idle'=>isset($condition['only_when_idle']), 'skip_if_media_playing'=>isset($condition['skip_if_media_playing']), 'skip_if_bluetooth_playing'=>isset($condition['skip_if_bluetooth_playing']), 'skip_if_airplay_playing'=>isset($condition['skip_if_airplay_playing'])],
            'max_duration_seconds' => max(0, min(86400, intval($schedule_option['max_duration_seconds'] ?? 0)))
          ];
        }
        $data['play_play_playlist']['schedules'] = $playlist_schedules;
        $legacy_playlist_times = [];
        $legacy_playlist_ids = [];
        foreach ($playlist_schedules as $playlist_schedule) {
          foreach ($playlist_schedule['slots'] as $playlist_slot) {
            $legacy_playlist_times[] = $playlist_slot['time'];
            $legacy_playlist_ids[] = $playlist_slot['playlist_id'];
          }
        }
        $data['play_play_playlist']['time'] = $legacy_playlist_times;
        $data['play_play_playlist']['playlist_id'] = $legacy_playlist_ids;
        $data['play_play_playlist']['active'] = isset($_POST['play_playlist_active']) ? true : false;

        #Lưu dữ liệu Phát toàn bộ nhạc trong thư mục Local
        $time_player_local = isset($_POST['time_player_local']) ? $_POST['time_player_local'] : [];
        $data['play_all_music_local']['time'] = array_filter($time_player_local);
        $data['play_all_music_local']['active'] = isset($_POST['play_all_local_active']) ? true : false;
        $data['play_all_music_local']['date'] = isset($_POST['dates_play_all_local']) ? $_POST['dates_play_all_local'] : [];

        //Cấu hình Scheduler nâng cao dùng chung cho các tác vụ hệ thống.
        $system_options = $_POST['system_schedule_options'] ?? [];
        $system_task_keys = ['change_volume', 'change_led_brightness', 'mic_on_off', 'play_all_music_local', 'stop_media_player', 'restart_vbot', 'reboot_os'];
        foreach ($system_task_keys as $system_key) {
          $option = isset($system_options[$system_key]) && is_array($system_options[$system_key]) ? $system_options[$system_key] : [];
          $recurrence = isset($option['recurrence']) && is_array($option['recurrence']) ? $option['recurrence'] : [];
          $allowed_types = ['legacy', 'daily', 'daily_months', 'weekdays', 'weekends', 'once', 'monthly', 'days_of_month', 'interval_days'];
          $recurrence_type = in_array(($recurrence['type'] ?? 'legacy'), $allowed_types, true) ? $recurrence['type'] : 'legacy';
          $months = isset($recurrence['months']) && is_array($recurrence['months']) ? array_values(array_filter(array_unique(array_map('intval', $recurrence['months'])), fn($value) => $value >= 1 && $value <= 12)) : [];
          $month_days = isset($recurrence['days']) && is_array($recurrence['days']) ? array_values(array_filter(array_unique(array_map('intval', $recurrence['days'])), fn($value) => $value >= 1 && $value <= 31)) : [];
          if ($recurrence_type === 'daily_months' && empty($months)) $months = range(1, 12);
          if ($recurrence_type === 'days_of_month' && empty($month_days)) $month_days = [1];
          $data[$system_key]['recurrence'] = [
            'type' => $recurrence_type,
            'start_date' => trim($recurrence['start_date'] ?? ''),
            'end_date' => trim($recurrence['end_date'] ?? ''),
            'date' => trim($recurrence['date'] ?? ''),
            'day' => max(1, min(31, intval($recurrence['day'] ?? 1))),
            'interval' => max(1, min(365, intval($recurrence['interval'] ?? 1))),
            'months' => $months,
            'days' => $month_days
          ];
          $condition = isset($option['conditions']) && is_array($option['conditions']) ? $option['conditions'] : [];
          $data[$system_key]['conditions'] = [
            'mode' => (($condition['mode'] ?? 'always') === 'conditional') ? 'conditional' : 'always',
            'mic_state' => in_array(($condition['mic_state'] ?? 'any'), ['any', 'on', 'off'], true) ? $condition['mic_state'] : 'any',
            'only_when_idle' => isset($condition['only_when_idle']),
            'skip_if_media_playing' => isset($condition['skip_if_media_playing']),
            'skip_if_bluetooth_playing' => isset($condition['skip_if_bluetooth_playing']),
            'skip_if_airplay_playing' => isset($condition['skip_if_airplay_playing'])
          ];
          $data[$system_key]['max_duration_seconds'] = max(0, min(86400, intval($option['max_duration_seconds'] ?? 0)));
          if ($recurrence_type === 'once' && count($data[$system_key]['time'] ?? []) > 1) {
            $data[$system_key]['time'] = array_slice(array_values($data[$system_key]['time']), 0, 1);
            foreach (['volume_time', 'brightness_time', 'action'] as $paired_key) {
              if (isset($data[$system_key][$paired_key])) $data[$system_key][$paired_key] = array_slice(array_values($data[$system_key][$paired_key]), 0, 1);
            }
          }
        }

        #Lưu toàn bộ dữ liệu vào file JSON đúng một lần.
        // Lưu các lịch hệ thống độc lập; cấu trúc cũ bên trên vẫn được giữ để tương thích ngược.
        $posted_system_schedules = isset($_POST['system_task_schedules']) && is_array($_POST['system_task_schedules']) ? $_POST['system_task_schedules'] : [];
        $independent_system_task_keys = array_merge($system_task_keys, ['vbot_action', 'send_notify_upgrade_vbot_home_assistant']);
        $grouped_system_schedules = array_fill_keys($independent_system_task_keys, []);
        $migrated_system_task_keys = [];
        foreach ($posted_system_schedules as $schedule_index => $schedule_option) {
          if (!is_array($schedule_option)) continue;
          $task_key = (string)($schedule_option['task'] ?? '');
          $selected_vbot_action = null;
          if (strpos($task_key, 'vbot_action:') === 0) {
            $selected_vbot_action = substr($task_key, strlen('vbot_action:'));
            $task_key = 'vbot_action';
          }
          $source_task_key = (string)($schedule_option['source_task'] ?? '');
          if ($task_key === 'vbot_action' && in_array($source_task_key, ['play_all_music_local','stop_media_player','restart_vbot','reboot_os'], true)) {
            $migrated_system_task_keys[$source_task_key] = true;
          }
          if (!array_key_exists($task_key, $grouped_system_schedules)) continue;
          $slot_time = trim((string)($schedule_option['time'] ?? ''));
          if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $slot_time)) continue;
          $parameter = $selected_vbot_action ?? ($schedule_option['parameter'] ?? null);
          if ($task_key === 'change_volume' || $task_key === 'change_led_brightness') {
            $parameter = max(0, min(100, intval($parameter)));
          } elseif ($task_key === 'mic_on_off') {
            $parameter = in_array($parameter, ['on', 'off'], true) ? $parameter : 'off';
          } elseif ($task_key === 'vbot_action') {
            $parameter = vbotActionRegistryNormalize($Config, $parameter);
          } else {
            $parameter = null;
          }
          $recurrence = isset($schedule_option['recurrence']) && is_array($schedule_option['recurrence']) ? $schedule_option['recurrence'] : [];
          $allowed_types = ['legacy', 'daily', 'daily_months', 'weekdays', 'weekends', 'once', 'monthly', 'days_of_month', 'interval_days'];
          $recurrence_type = in_array(($recurrence['type'] ?? 'legacy'), $allowed_types, true) ? $recurrence['type'] : 'legacy';
          $months = isset($recurrence['months']) && is_array($recurrence['months']) ? array_values(array_filter(array_unique(array_map('intval', $recurrence['months'])), fn($value) => $value >= 1 && $value <= 12)) : [];
          $month_days = isset($recurrence['days']) && is_array($recurrence['days']) ? array_values(array_filter(array_unique(array_map('intval', $recurrence['days'])), fn($value) => $value >= 1 && $value <= 31)) : [];
          if ($recurrence_type === 'daily_months' && !$months) $months = range(1, 12);
          if ($recurrence_type === 'days_of_month' && !$month_days) $month_days = [1];
          $condition = isset($schedule_option['conditions']) && is_array($schedule_option['conditions']) ? $schedule_option['conditions'] : [];
          $grouped_system_schedules[$task_key][] = [
            'id' => preg_match('/^[a-zA-Z0-9_-]{1,80}$/', (string)($schedule_option['id'] ?? '')) ? (string)$schedule_option['id'] : ('system_' . time() . '_' . intval($schedule_index)),
            'active' => isset($schedule_option['active']),
            'slots' => [['time' => $slot_time, 'parameter' => $parameter]],
            'time' => [$slot_time], 'parameter' => $parameter,
            'date' => isset($schedule_option['date']) && is_array($schedule_option['date']) ? array_values(array_intersect($schedule_option['date'], array_keys($week_days))) : [],
            'recurrence' => ['type'=>$recurrence_type, 'start_date'=>trim($recurrence['start_date'] ?? ''), 'end_date'=>trim($recurrence['end_date'] ?? ''), 'date'=>trim($recurrence['date'] ?? ''), 'day'=>max(1,min(31,intval($recurrence['day'] ?? 1))), 'interval'=>max(1,min(365,intval($recurrence['interval'] ?? 1))), 'months'=>$months, 'days'=>$month_days],
            'conditions' => ['mode'=>(($condition['mode'] ?? 'always') === 'conditional' ? 'conditional' : 'always'), 'mic_state'=>in_array(($condition['mic_state'] ?? 'any'), ['any','on','off'], true) ? $condition['mic_state'] : 'any', 'only_when_idle'=>isset($condition['only_when_idle']), 'skip_if_media_playing'=>isset($condition['skip_if_media_playing']), 'skip_if_bluetooth_playing'=>isset($condition['skip_if_bluetooth_playing']), 'skip_if_airplay_playing'=>isset($condition['skip_if_airplay_playing'])],
            'max_duration_seconds' => max(0, min(86400, intval($schedule_option['max_duration_seconds'] ?? 0)))
          ];
        }
        foreach ($grouped_system_schedules as $system_key => $schedules) {
          $data[$system_key]['schedules'] = $schedules;
          if ($schedules) $data[$system_key]['active'] = true;
        }
        foreach (array_keys($migrated_system_task_keys) as $legacy_task_key) {
          $data[$legacy_task_key]['schedules'] = [];
          $data[$legacy_task_key]['active'] = false;
        }
        $removed_system_tasks = isset($_POST['removed_system_schedule_tasks']) && is_array($_POST['removed_system_schedule_tasks']) ? $_POST['removed_system_schedule_tasks'] : [];
        foreach (array_unique($removed_system_tasks) as $removed_system_task) {
          if (array_key_exists($removed_system_task, $grouped_system_schedules) && !$grouped_system_schedules[$removed_system_task]) {
            $data[$removed_system_task]['active'] = false;
          }
        }

        $encoded_schedule = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded_schedule === false) {
          $save_error = "Không thể mã hóa dữ liệu Scheduler: " . json_last_error_msg();
          $errorMessages[] = $save_error;
          error_log($save_error);
        } elseif (!vbotAtomicWriteFile($json_file, $encoded_schedule, 'dữ liệu Scheduler')) {
          $save_error = "Không thể ghi dữ liệu Scheduler vào tệp: " . $json_file;
          $errorMessages[] = $save_error;
          error_log($save_error);
        } else {
          vbotSchedulerSetFullPermissions($json_file, 'tệp dữ liệu Scheduler');
          $successMessage[] = "Dữ liệu đã được lưu thành công.";
        }
      }
      ?>
      <section class="section">
        <div class="scheduler-toolbar" id="scheduler-toolbar" aria-label="Tìm kiếm và điều hướng tác vụ">
          <div class="row g-2 align-items-center">
            <div class="col-12 col-lg">
              <div class="input-group">
                <span class="input-group-text border-primary"><i class="bi bi-search text-primary"></i></span>
                <input type="search" id="scheduler-task-search" class="form-control border-primary"
                  placeholder="Tìm tác vụ, ví dụ: báo thức, âm lượng, Bluetooth..." autocomplete="off">
                <button type="button" id="scheduler-search-clear" class="btn btn-outline-secondary" title="Xóa nội dung tìm kiếm">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>
            <div class="col-12 col-lg-auto">
              <select id="scheduler-quick-navigation" class="form-select border-primary" aria-label="Đi tới tác vụ">
                <option value="">Đi tới tác vụ...</option>
              </select>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2 justify-content-center">
              <button type="submit" name="save_all_Scheduler" class="btn btn-primary rounded-pill flex-grow-1 flex-lg-grow-0">
                <i class="bi bi-save"></i> Lưu Dữ liệu
              </button>
              <button type="button" class="btn btn-success rounded-pill flex-grow-1 flex-lg-grow-0" onclick="addNewTask()">
                <i class="bi bi-plus-circle-dotted"></i> Thêm mới tác vụ thông báo
              </button>
              <button class="btn btn-success rounded-pill flex-grow-1 flex-lg-grow-0" type="button" id="add-system-task-schedule">
                <i class="bi bi-plus-circle"></i> Thêm mới tác vụ hệ thống
              </button>
            </div>
          </div>
          <div id="scheduler-search-empty" class="alert alert-info py-2 mt-2 mb-0" role="status">
            <i class="bi bi-info-circle"></i> Không tìm thấy tác vụ phù hợp.
          </div>
        </div>
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
            echo '<div class="alert alert-success alert-dismissible fade show" id="message_success" role="alert">';
            echo '<ul style="color: green;">';
            foreach ($successMessage as $successMessagegg) {
              echo '<li>' . $successMessagegg . '</li>';
            }
            echo '</ul>';
            echo '</div>';
          }
          ?>
          <div class="card mb-3 border-info">
            <div class="card-body">
              <h5 class="card-title">Theo dõi tiến trình, tác vụ:</h5>
              <button type="button" class="btn btn-danger btn-sm" onclick="stopCurrentSchedulerTask()"><i class="bi bi-stop-circle"></i> Dừng tác vụ đang phát</button>
              <button type="button" class="btn btn-info btn-sm" onclick="loadSchedulerOverview()"><i class="bi bi-arrow-clockwise"></i> Cập nhật</button>
              <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearSchedulerHistory()"><i class="bi bi-trash"></i> Xóa lịch sử</button>
			  <hr/>
              <div class="row mt-3"><div class="col-md-6"><h6><b>Lần chạy tác vụ tiếp theo</b></h6><div id="scheduler-next-runs" class="small text-primary">Đang tải...</div></div>
              <div class="col-md-6"><h6><b>Lịch sử đã thực thi tác vụ</b></h6><div id="scheduler-history" class="small text-success" style="max-height:260px;overflow:auto">Đang tải...</div></div></div>
            </div>
          </div>
          <div id="task-container">
            <?php if (!empty($data['notification_schedule'])) : ?>
              <?php foreach ($data['notification_schedule'] as $index => $notification) : ?>
                <div class="card accordion" id="accordion_button_schedule_<?= $index ?>">
                  <div class="card-body">
                    <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_schedule_<?= $index ?>" aria-expanded="false" aria-controls="collapse_button_schedule_<?= $index ?>">
                      <font color="Fuchsia"><?= htmlspecialchars($notification['name']) ?></font>, &nbsp;</font> Trạng Thái: &nbsp;<?= !empty($notification['active']) ? ' <font color=green>Bật</font>' : ' <font color=red>Tắt</font>' ?>
                    </h5>
                    <div id="collapse_button_schedule_<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_schedule_<?= $index ?>">
                     <div class="alert alert-success" role="alert">  <div id="task-<?= $index ?>">
                        <div class="row mb-3">
                          <label for="active-<?= $index ?>" class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt hành động này')"></i>:</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input type="checkbox" class="form-check-input border-success" id="active-<?= $index ?>" name="notification_schedule[<?= $index ?>][active]" <?= $notification['active'] ? 'checked' : '' ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="name-<?= $index ?>" class="col-sm-3 col-form-label">Tên Tác Vụ <i class="bi bi-question-circle-fill" onclick="show_message('Tên Định Danh Để Phân Biệt Với Các Hành Động, Thao Tác Khác')"></i>
                            <font color="red" size="6" title="Bắt Buộc Nhập">*</font> :
                          </label>
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
                          <label for="message-<?= $index ?>" class="col-sm-3 col-form-label">Nội Dung Thông Báo <i class="bi bi-question-circle-fill" onclick="show_message('Cần nhập nội dung thông báo, nếu không nhập nội dung thì cần phải cấu hình nhập file âm thanh, bắt buộc phải có 1 trong 2 thì mới cho lưu dữ liệu (hệ thống sẽ ưu tiên phát thông báo văn bản, nếu văn bản trống thì sẽ phát âm thanh từ file)<hr/>Hỗ trợ nhập các link Youtube, zingmp3.vn, nhaccuatui.com, và các đường link có đuôi âm thanh như .mp3')"></i>
                            <font color="blue" size="6" title="Có thể nhập, lựa chọn hoặc để trống">*</font> :
                          </label>
                          <div class="col-sm-9">
                            <textarea type="text" rows="3" class="form-control border-success" id="message-<?= $index ?>" name="notification_schedule[<?= $index ?>][data][message]" placeholder="Nhập nội dung thông báo, Hỗ trợ nhập cả các link: Youtube, zingmp3.vn, nhaccuatui.com, các link nhạc có đuôi âm thanh .mp3, Nếu bỏ trống thì cần chọn dữ liệu tệp âm thanh"><?= htmlspecialchars($notification['data']['message']) ?></textarea>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="audio_file-<?= $index ?>" class="col-sm-3 col-form-label">
                            Tệp Âm Thanh (Link,URL/PATH)
                            <i class="bi bi-question-circle-fill" onclick="show_message('Cần nhập thông tin đường dẫn, link, url tới tệp âm thanh, nếu không nhập nội dung thì cần phải cấu hình nhập file âm thanh, bắt buộc phải có 1 trong 2 thì mới cho lưu dữ liệu (hệ thống sẽ ưu tiên phát thông báo văn bản, nếu văn bản trống thì sẽ phát âm thanh từ file)')"></i>
                            <font color="blue" size="6" title="Có thể nhập, lựa chọn hoặc để trống">*</font> :
                          </label>
                          <div class="col-sm-9">
                            <div class="input-group mb-3">
                              <?php
                              // Kiểm tra và gán giá trị mặc định nếu phần tử không tồn tại
                              $audio_file = isset($notification['data']['audio_file']) ? htmlspecialchars($notification['data']['audio_file']) : "";
                              // Gọi hàm để tạo dropdown cho trường âm thanh này
                              generate_audio_select([$VBot_Offline . $Config['schedule']['audio_path'], $VBot_Offline.$Config['media_player']['music_local']['path']], 'notification_schedule[' . $index . '][data][audio_file]', $audio_file);
                              ?>
                              <button class="btn btn-success border-success" onclick="playAudio_Schedule('notification_schedule[<?php echo $index; ?>][data][audio_file]')" type="button"><i class="bi bi-play-circle"></i></button>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="repeat-<?= $index ?>" class="col-sm-3 col-form-label">Số lần lặp lại <i class="bi bi-question-circle-fill" onclick="show_message('Số lần lặp lại phát thông báo khi tác vụ này được kích hoạt, để 2 lần thì đến giờ sẽ thông báo 2 lần liên tiếp')"></i>
                            <font color="red" size="6" title="Bắt Buộc Nhập">*</font> :
                          </label>
                          <div class="col-sm-9">
                            <input required type="number" class="form-control border-success" id="repeat-<?= $index ?>" name="notification_schedule[<?= $index ?>][data][repeat]" value="<?= htmlspecialchars($notification['data']['repeat']) ?>">
                            <div class="invalid-feedback">Cần đặt tên cho hành động này</div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Thời gian tối đa (giây/s)<i class="bi bi-question-circle-fill" onclick="show_message('Là khoảng thời gian lâu nhất mà một tác vụ thông báo được phép phát. Tác vụ được chạy tối đa 60 giây. Khi hết 60 giây mà TTS, file âm thanh hoặc URL media vẫn chưa kết thúc, Scheduler sẽ tự dừng tác vụ và ghi lịch sử trạng thái: 0: không giới hạn, chờ tác vụ phát xong.')"></i>:</label>
                          <div class="col-sm-9">
                            <input type="number" min="0" max="86400" class="form-control border-success" name="notification_schedule[<?= $index ?>][data][max_duration_seconds]" value="<?= intval($notification['data']['max_duration_seconds'] ?? 0) ?>">
                            <small class="text-muted">Đơn vị giây, đặt 0 để không giới hạn, chờ tác vụ phát xong.</small>
                          </div>
                        </div>
                        <?php
                          $recurrence = $notification['recurrence'] ?? ['type' => 'legacy'];
                          $conditions = $notification['conditions'] ?? [];
                          $has_saved_conditions = (($conditions['mic_state'] ?? 'any') !== 'any') || !empty($conditions['only_when_idle']) || !empty($conditions['skip_if_media_playing']) || !empty($conditions['skip_if_bluetooth_playing']) || !empty($conditions['skip_if_airplay_playing']);
                          $condition_mode_ui = $conditions['mode'] ?? ($has_saved_conditions ? 'conditional' : 'always');
                        ?>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Chế độ chạy:</label>
                          <div class="col-sm-9">
                            <select class="form-select border-success scheduler-recurrence-type" data-scheduler-index="<?= $index ?>" name="notification_schedule[<?= $index ?>][recurrence][type]">
                              <?php foreach (['legacy'=>'Theo thứ trong tuần','daily'=>'Hằng ngày','daily_months'=>'Hằng ngày (Lựa chọn tháng)','weekdays'=>'Ngày làm việc (Thứ Hai–Thứ Sáu)','weekends'=>'Cuối tuần (Thứ Bảy–Chủ Nhật)','once'=>'Chỉ một lần','monthly'=>'Một ngày mỗi tháng','days_of_month'=>'N ngày trong tháng'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= (($recurrence['type'] ?? 'legacy') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                              <?php endforeach; ?>
                              <?php if (($recurrence['type'] ?? '') === 'interval_days'): ?><option value="interval_days" selected>Chu kỳ N ngày (lịch cũ)</option><?php endif; ?>
                            </select>
                            <div class="input-group mt-2 scheduler-recurrence-fields" data-recurrence-for="weekdays weekends monthly interval_days">
                              <span class="input-group-text border-success">Bắt đầu</span><input type="date" class="form-control border-success" name="notification_schedule[<?= $index ?>][recurrence][start_date]" value="<?= htmlspecialchars($recurrence['start_date'] ?? '') ?>">
                              <span class="input-group-text border-success">Kết thúc</span><input type="date" class="form-control border-success" name="notification_schedule[<?= $index ?>][recurrence][end_date]" value="<?= htmlspecialchars($recurrence['end_date'] ?? '') ?>">
                            </div>
                            <div class="input-group mt-2 scheduler-recurrence-fields" data-recurrence-for="once"><span class="input-group-text border-success">Ngày thực hiện</span><input type="date" class="form-control border-success" name="notification_schedule[<?= $index ?>][recurrence][date]" value="<?= htmlspecialchars($recurrence['date'] ?? '') ?>"></div>
                            <div class="input-group mt-2 scheduler-recurrence-fields" data-recurrence-for="monthly"><span class="input-group-text border-success">Ngày trong tháng</span><input type="number" min="1" max="31" class="form-control border-success" name="notification_schedule[<?= $index ?>][recurrence][day]" value="<?= intval($recurrence['day'] ?? 1) ?>"></div>
                            <div class="input-group mt-2 scheduler-recurrence-fields" data-recurrence-for="interval_days"><span class="input-group-text">Chu kỳ N ngày</span><input type="number" min="1" max="365" class="form-control" name="notification_schedule[<?= $index ?>][recurrence][interval]" value="<?= intval($recurrence['interval'] ?? 1) ?>"></div>
                            <div class="mt-2 scheduler-recurrence-fields" data-recurrence-for="daily_months">
                              <div class="border rounded p-2"><div class="mb-1">Chọn tháng áp dụng:</div>
                              <?php $selected_months = $recurrence['months'] ?? range(1, 12); foreach (range(1, 12) as $month): ?>
                                <div class="form-check form-check-inline"><input class="form-check-input border-success" type="checkbox" id="recurrence-month-<?= $index ?>-<?= $month ?>" name="notification_schedule[<?= $index ?>][recurrence][months][]" value="<?= $month ?>" <?= in_array($month, $selected_months) ? 'checked' : '' ?>><label class="form-check-label" for="recurrence-month-<?= $index ?>-<?= $month ?>">Tháng <?= $month ?></label></div>
                              <?php endforeach; ?></div>
                            </div>
                            <div class="mt-2 scheduler-recurrence-fields" data-recurrence-for="days_of_month">
                              <div class="border rounded p-2"><div class="mb-1">Chọn các ngày trong tháng:</div>
                              <?php $selected_month_days = $recurrence['days'] ?? [1]; foreach (range(1, 31) as $month_day): ?>
                                <div class="form-check form-check-inline"><input class="form-check-input border-success" type="checkbox" id="recurrence-day-<?= $index ?>-<?= $month_day ?>" name="notification_schedule[<?= $index ?>][recurrence][days][]" value="<?= $month_day ?>" <?= in_array($month_day, $selected_month_days) ? 'checked' : '' ?>><label class="form-check-label" for="recurrence-day-<?= $index ?>-<?= $month_day ?>"><?= $month_day ?></label></div>
                              <?php endforeach; ?></div>
                              <small class="text-muted">Tháng không có ngày đã chọn (ví dụ ngày 31 trong tháng 2) sẽ tự bỏ qua.</small>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Điều kiện chạy:</label>
                          <div class="col-sm-9">
                            <select class="form-select border-primary mb-2 scheduler-condition-mode" data-scheduler-index="<?= $index ?>" name="notification_schedule[<?= $index ?>][conditions][mode]">
                              <option value="always" <?= $condition_mode_ui === 'always' ? 'selected' : '' ?>>Luôn thực thi</option>
                              <option value="conditional" <?= $condition_mode_ui === 'conditional' ? 'selected' : '' ?>>Chỉ thực thi khi thỏa điều kiện</option>
                            </select>
                            <div class="scheduler-condition-fields" data-scheduler-index="<?= $index ?>">
                            <select class="form-select border-success mb-2" name="notification_schedule[<?= $index ?>][conditions][mic_state]">
                              <option value="any" <?= (($conditions['mic_state'] ?? 'any') === 'any') ? 'selected' : '' ?>>Không phụ thuộc microphone</option>
                              <option value="on" <?= (($conditions['mic_state'] ?? '') === 'on') ? 'selected' : '' ?>>Chỉ khi microphone bật</option>
                              <option value="off" <?= (($conditions['mic_state'] ?? '') === 'off') ? 'selected' : '' ?>>Chỉ khi microphone tắt</option>
                            </select>
                            <?php foreach (['only_when_idle'=>'Chỉ khi VBot đang rảnh','skip_if_media_playing'=>'Bỏ qua khi đang phát media','skip_if_bluetooth_playing'=>'Bỏ qua khi Bluetooth đang phát','skip_if_airplay_playing'=>'Bỏ qua khi AirPlay đang phát'] as $key => $label): ?>
                              <div class="form-check"><input class="form-check-input border-success" type="checkbox" name="notification_schedule[<?= $index ?>][conditions][<?= $key ?>]" <?= !empty($conditions[$key]) ? 'checked' : '' ?>><label class="form-check-label"><?= $label ?></label></div>
                            <?php endforeach; ?>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3 scheduler-legacy-dates" data-scheduler-index="<?= $index ?>">
                          <label for="date-<?= $index ?>" class="col-sm-3 col-form-label">
                            Các thứ trong tuần
                            <i class="bi bi-question-circle-fill" onclick="show_message('Chỉ áp dụng khi Chế độ lịch là Theo thứ trong tuần. Có thể chọn nhiều thứ và thêm các ngày ngoại lệ cụ thể, ví dụ: 01/12/2026.')"></i>
                            <font color='red' size='6' title='Bắt Buộc Nhập'>*</font> :
                          </label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <?php
                              //Mảng các ngày đã chọn
                              $selected_days = $notification['date'];
                              //Kiểm tra xem có ngày tháng cụ thể trong dữ liệu hay không
                              $specific_dates = array_filter($selected_days, function ($day) {
                                //Kiểm tra xem giá trị có phải là ngày tháng (dd/mm/yyyy) không
                                return preg_match('/\d{2}\/\d{2}\/\d{4}/', $day);
                              });
                              //Lọc bỏ các ngày tháng cụ thể, chỉ lấy các ngày trong tuần
                              $week_days_selected = array_diff($selected_days, $specific_dates);
                              //Hiển thị checkbox cho các ngày trong tuần
                              foreach ($week_days as $key => $label) {
                                $checked = in_array($key, $week_days_selected) ? 'checked' : '';
                                echo '<input type="checkbox" class="form-check-input border-success" id="date-' . $index . '-' . $key . '" name="notification_schedule[' . $index . '][date][]" value="' . $key . '" ' . $checked . '> ';
                                echo '<label for="date-' . $index . '-' . $key . '">' . $label . '</label><br/>';
                              }
                              //Hiển thị ô input cho các ngày tháng cụ thể (dd/mm/yyyy) và nút xóa
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
                          <label class="col-sm-3 col-form-label">Thời gian (HH:MM) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian theo định dạng giờ, phút phải có dấu : ở giữa định dạng nhập là 24h: từ 00:00 tới 23:59')"></i>
                            <font color="red" size="6" title="Bắt Buộc Nhập">*</font> :
                          </label>
                          <div class="col-sm-9">
                            <div id="time-container-<?= $index ?>">
                              <?php foreach ($notification['time'] as $time_key => $time_value) : ?>
                                <div class="time-input-container input-group mb-3">
                                  <input type="time" step="60" class="form-control border-success time-input" name="notification_schedule[<?= $index ?>][time][]" value="<?= htmlspecialchars($time_value) ?>" id="time-input-<?= $index ?>-<?= $time_key ?>" required>
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
                            <button type="button" class="btn btn-success rounded-pill scheduler-add-time" data-scheduler-index="<?= $index ?>" onclick="addTimeInput(<?= $index ?>)">Thêm thời gian</button>
                          </div>
                        </div>
                        <center>
                          <button class="btn btn-primary rounded-pill" type="button" onclick="run_test_task('<?= htmlspecialchars($notification['name']) ?>', '')"><i class="bi bi-play" title="Chạy Test Tác Vụ Này"></i> Chạy, Test Tác Vụ</button>
                          <button class="btn btn-danger rounded-pill" type="button" onclick="deleteTask(<?= $index ?>)"><i class="bi bi-trash" title="Xóa bỏ tác vụ này"></i> Xóa tác vụ này</button>
                        </center>
                      </div>
                    </div>
                  </div>
                </div>
                </div>
              <?php endforeach; ?>
            <?php else : ?>
              <p class="text-danger">
                <center>
                  <h5>Chưa có tác vụ thông báo, lời nhắc nào được thiết lập</h5>
                </center>
              </p>
            <?php endif; ?>
          </div>
          <hr style="border: 2px solid #0000FF;">
          <div class="alert alert-success text-center" role="alert"><b>Các Tác Vụ Sẵn Có Trên Hệ Thống</b></div>

          <?php
          $independentSystemSchedules = [];
          foreach (['vbot_action','change_volume','change_led_brightness','mic_on_off','play_all_music_local','stop_media_player','restart_vbot','reboot_os','send_notify_upgrade_vbot_home_assistant'] as $systemTaskKey) {
            foreach (($data[$systemTaskKey]['schedules'] ?? []) as $systemSchedule) {
              if (!is_array($systemSchedule)) continue;
              $systemSchedule['task'] = $systemTaskKey;
              $independentSystemSchedules[] = $systemSchedule;
            }
          }
          $systemTaskLabels = [
            'change_volume'=>'Thay đổi âm lượng', 'change_led_brightness'=>'Thay đổi độ sáng LED',
            'mic_on_off'=>'Bật/Tắt microphone',
            'send_notify_upgrade_vbot_home_assistant'=>'Kiểm tra và thông báo cập nhật VBot'
          ];
          $vbotActionOptions = vbotActionRegistryOptions($Config);
          // Hiển thị trực tiếp từng thao tác trong danh sách loại tác vụ. Giá trị
          // vẫn được chuẩn hóa về task=vbot_action + parameter khi lưu JSON.
          $directVbotTaskLabels = [];
          foreach ($vbotActionOptions as $actionValue => $actionLabel) {
            $directVbotTaskLabels['vbot_action:'.$actionValue] = $actionLabel;
          }
          $systemTaskLabels = $directVbotTaskLabels + $systemTaskLabels;
          ?>
          <div class="card border-primary mb-3">
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-calendar2-plus"></i> Quản Lý Các Lịch Tác Vụ Độc Lập</h5>
              <p class="text-muted">Mỗi lịch bên dưới có loại tác vụ, thời gian, chế độ chạy và điều kiện riêng. Dữ liệu lịch cũ vẫn được hỗ trợ.</p>
              <div id="system-task-schedule-changes"></div>
              <div id="system-task-schedules-container">
                <?php foreach ($independentSystemSchedules as $systemScheduleIndex => $systemSchedule):
                  $systemSlots = is_array($systemSchedule['slots'] ?? null) ? $systemSchedule['slots'] : [];
                  $systemSlot = $systemSlots[0] ?? ['time'=>(($systemSchedule['time'][0] ?? '')), 'parameter'=>($systemSchedule['parameter'] ?? '')];
                  $systemTask = $systemSchedule['task'] ?? 'change_volume';
                  $legacyVbotActionMap = ['play_all_music_local'=>'play_all_local','stop_media_player'=>'media_stop','restart_vbot'=>'restart_vbot','reboot_os'=>'reboot_os'];
                  $systemTaskSelectValue = $systemTask === 'vbot_action'
                    ? 'vbot_action:'.((string)($systemSlot['parameter'] ?? $systemSchedule['parameter'] ?? 'none'))
                    : (isset($legacyVbotActionMap[$systemTask]) ? 'vbot_action:'.$legacyVbotActionMap[$systemTask] : $systemTask); ?>
                  <div class="card border-success mb-3 system-task-schedule-card">
                    <div class="card-body">
                      <input type="hidden" name="system_task_schedules[<?= $systemScheduleIndex ?>][id]" value="<?= htmlspecialchars($systemSchedule['id'] ?? ('system_'.$systemScheduleIndex)) ?>">
                      <input type="hidden" name="system_task_schedules[<?= $systemScheduleIndex ?>][source_task]" value="<?= htmlspecialchars($systemTask) ?>">
                      <div class="d-flex justify-content-between align-items-center mb-3"><b>Lịch tác vụ #<?= $systemScheduleIndex + 1 ?></b><button class="btn btn-danger btn-sm system-task-schedule-delete" type="button"><i class="bi bi-trash"></i> Xóa lịch</button></div>
                      <div class="row g-2 mb-3"><div class="col-md-4"><select class="form-select border-primary system-task-kind" name="system_task_schedules[<?= $systemScheduleIndex ?>][task]"><?php foreach ($systemTaskLabels as $key=>$label): ?><option value="<?= htmlspecialchars($key) ?>" <?= $systemTaskSelectValue===$key?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><input class="form-control border-success scheduler-time-24h" type="time" step="60" required name="system_task_schedules[<?= $systemScheduleIndex ?>][time]" value="<?= htmlspecialchars($systemSlot['time'] ?? '') ?>"></div><div class="col-md-3 system-task-parameter-wrap"><input class="form-control border-warning system-task-parameter" name="system_task_schedules[<?= $systemScheduleIndex ?>][parameter]" value="<?= htmlspecialchars((string)($systemSlot['parameter'] ?? '')) ?>"></div><div class="col-md-2"><div class="form-check form-switch pt-2"><input class="form-check-input" type="checkbox" name="system_task_schedules[<?= $systemScheduleIndex ?>][active]" <?= !isset($systemSchedule['active']) || $systemSchedule['active']?'checked':'' ?>><label class="form-check-label">Bật</label></div></div></div>
                      <?php render_system_scheduler_options('independent_'.$systemScheduleIndex, $systemSchedule, 'Tác vụ hệ thống', '', 'system_task_schedules['.$systemScheduleIndex.'][date]', 'system_task_schedules['.$systemScheduleIndex.']'); ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div id="system-task-schedules-empty" class="alert alert-secondary <?= $independentSystemSchedules ? 'd-none' : '' ?>">Chưa có lịch tác vụ độc lập. Nhấn nút bên dưới để tạo lịch mới.</div>
            </div>
          </div>

          <template id="system-task-schedule-template">
            <div class="card border-success mb-3 system-task-schedule-card"><div class="card-body">
              <input type="hidden" name="system_task_schedules[__INDEX__][id]" value="system___STAMP_____INDEX__">
              <div class="d-flex justify-content-between align-items-center mb-3"><b>Lịch tác vụ mới</b><button class="btn btn-danger btn-sm system-task-schedule-delete" type="button"><i class="bi bi-trash"></i> Xóa lịch</button></div>
              <div class="row g-2 mb-3"><div class="col-md-4"><select class="form-select border-primary system-task-kind" name="system_task_schedules[__INDEX__][task]"><?php foreach ($systemTaskLabels as $key=>$label): ?><option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><input class="form-control border-success scheduler-time-24h" type="time" step="60" required name="system_task_schedules[__INDEX__][time]"></div><div class="col-md-3 system-task-parameter-wrap"><input class="form-control border-warning system-task-parameter" name="system_task_schedules[__INDEX__][parameter]" value="50"></div><div class="col-md-2"><div class="form-check form-switch pt-2"><input class="form-check-input" type="checkbox" name="system_task_schedules[__INDEX__][active]" checked><label class="form-check-label">Bật</label></div></div></div>
              <?php render_system_scheduler_options('independent___INDEX__', ['date'=>array_keys($week_days)], 'Tác vụ hệ thống', '', 'system_task_schedules[__INDEX__][date]', 'system_task_schedules[__INDEX__]'); ?>
            </div></div>
          </template>

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
               <div class="alert alert-success" role="alert"> <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">
                    Kích Hoạt
                    <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt hành động này<br/><br/>Yêu Cầu:<br/> - Phải Kích Hoạt Home Assistant<br/>- Phải Kích Hoạt Thông Báo Lời Nhắc')"></i>:
                  </label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input
                        type="checkbox"
                        class="form-check-input border-success"
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
                    Thời Gian Kích Hoạt:
                    <i class="bi bi-question-circle-fill" onclick="show_message('Định dạng thời gian là 24 giờ, (giờ:phút) Ví Dụ: (03:59)')"></i>:
                  </label>
                  <div class="col-sm-9">
                    <input class="form-control border-danger" type="text" name="send_notify_upgrade_vbot_home_assistant_time" id="send_notify_upgrade_vbot_home_assistant_time"
                      placeholder="<?php echo isset($data['send_notify_upgrade_vbot_home_assistant']['time']) ? $data['send_notify_upgrade_vbot_home_assistant']['time'] : '03:01'; ?>"
                      value="<?php echo isset($data['send_notify_upgrade_vbot_home_assistant']['time']) ? $data['send_notify_upgrade_vbot_home_assistant']['time'] : '03:01'; ?>">
                  </div>
                </div>
              </div>
            </div>
          </div>
          </div>

          <div class="card accordion" id="accordion_button_volume_change">
            <div class="card-body">
              <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_volume_change" aria-expanded="false" aria-controls="collapse_button_volume_change">
                <font color="Blue">Lập Lịch Thay Đổi Âm Lượng</font>, Trạng Thái:&nbsp;
                <?php
                echo isset($data['change_volume']['active']) ? ($data['change_volume']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>';
                ?>
              </h5>
              <div id="collapse_button_volume_change" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_volume_change">
              <div class="alert alert-success" role="alert">
			  <?php
                // Kiểm tra dữ liệu change_volume và gán giá trị mặc định nếu không có
                if (!isset($data['change_volume']) || empty($data['change_volume'])) {
                  // Gán giá trị mặc định nếu không có dữ liệu change_volume
                  $data['change_volume'] = [
                    'active' => false,
                    'date' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
                    'time' => ["23:59"],
                    'volume_time' => ["65"]
                  ];
                }
                $change_volume = $data['change_volume'];
                ?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="change_volume_active" id="change_volume_active" value="<?php echo $change_volume['active']; ?>" <?= $change_volume['active'] ? 'checked' : '' ?>>
                    </div>
                  </div>
                </div>
			<?php
			render_system_scheduler_options(
				'change_volume',
				$data['change_volume'] ?? [],
				'Thay đổi âm lượng',
				'time-changes-volumes',
				'dates_change_volume'
			);
			?>
                <!-- Thời gian -->
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Thời Gian Kích Hoạt:</label>
                  <div class="col-sm-9">
                    <div class="time-inputs_display_screen" id="time-changes_volumes">
                      <?php foreach ($change_volume['time'] as $index => $time): ?>
                        <div class="time-input-container input-group mb-3" id="time-change_volume-<?= $index ?>">
                          <input class="form-control border-success" type="time" step="60" name="time_change_volume[]" value="<?= htmlspecialchars($time) ?>">
                          <input class="form-control border-primary" type="number" name="volumes_volume_time[]" value="<?= isset($change_volume['volume_time'][$index]) ? htmlspecialchars($change_volume['volume_time'][$index]) : '' ?>" placeholder="Âm lượng (0-100)" min="0" max="100" style="max-width: 200px;">
						<button class="btn btn-primary" type="button" onclick="run_test_task('change_volume', '<?= isset($change_volume['volume_time'][$index]) ? htmlspecialchars($change_volume['volume_time'][$index]) : '' ?>')"><i class="bi bi-play" title="Chạy Test Tác Vụ Này"></i></button>
                          <button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-change_volume-<?= $index ?>"><i class="bi bi-trash"></i></button>
                        </div>
                      <?php endforeach; ?>
                      <button class="btn btn-success rounded-pill" type="button" id="add-time-change_volume">Thêm thời gian</button>
                    </div>
                  </div>
                </div>
                <hr/>
              </div>
            </div>
          </div>
          </div>

          <div class="card accordion" id="accordion_button_brightness_change">
            <div class="card-body">
              <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_brightness_change" aria-expanded="false" aria-controls="collapse_button_brightness_change">
                <font color="Blue">Lập Lịch Thay Đổi Độ Sáng Đèn LED</font>, Trạng Thái:&nbsp;
                <?php
                echo isset($data['change_led_brightness']['active']) ? ($data['change_led_brightness']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>';
                ?>
              </h5>
              <div id="collapse_button_brightness_change" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_brightness_change">
             <div class="alert alert-success" role="alert">
			 <?php
                //Kiểm tra dữ liệu change_led_brightness và gán giá trị mặc định nếu không có
                if (!isset($data['change_led_brightness']) || empty($data['change_led_brightness'])) {
                  $data['change_led_brightness'] = [
                    'active' => false,
                    'date' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
                    'time' => ["23:59"],
                    'brightness_time' => ["20"]
                  ];
                }
                $change_led_brightness = $data['change_led_brightness'];
                ?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="change_led_brightness_active" id="change_led_brightness_active" value="<?php echo $change_led_brightness['active']; ?>" <?= $change_led_brightness['active'] ? 'checked' : '' ?>>
                    </div>
                  </div>
                </div>

<?php
render_system_scheduler_options(
    'change_led_brightness',
    $data['change_led_brightness'] ?? [],
    'Thay đổi độ sáng LED',
    'time-changes-brightness',
    'dates_changes_brightness'
);
?>

                <!-- Thời gian -->
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Thời Gian Kích Hoạt:</label>
                  <div class="col-sm-9">
                    <div class="time-inputs_display_screen" id="time-changes_brightness">
                      <?php foreach ($change_led_brightness['time'] as $index => $time): ?>
                        <div class="time-input-container input-group mb-3" id="time-change_led_brightness-<?= $index ?>">
                          <input class="form-control border-success" type="time" step="60" name="time_change_brightness[]" value="<?= htmlspecialchars($time) ?>">
                          <input class="form-control border-primary" type="number" name="brightness_brightnes_time[]" value="<?= isset($change_led_brightness['brightness_time'][$index]) ? htmlspecialchars($change_led_brightness['brightness_time'][$index]) : '' ?>" placeholder="Độ sáng từ (0-100)" min="0" max="100" style="max-width: 200px;">
							<button class="btn btn-primary" type="button" onclick="run_test_task('change_led_brightness', '<?= isset($change_led_brightness['brightness_time'][$index]) ? htmlspecialchars($change_led_brightness['brightness_time'][$index]) : '' ?>')"><i class="bi bi-play" title="Chạy Test Tác Vụ Này"></i></button>
                          <button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-change_led_brightness-<?= $index ?>"><i class="bi bi-trash"></i></button>
                        </div>
                      <?php endforeach; ?>
                      <button class="btn btn-success rounded-pill" type="button" id="add-time-change_led_brightness">Thêm thời gian</button>
                    </div>
                  </div>
                </div>
                <hr />
              </div>
            </div>
          </div>
          </div>

		<!-- lên lịch Bật tắt Mic -->
          <div class="card accordion" id="accordion_button_mic_on_off">
            <div class="card-body">
              <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_mic_on_off" aria-expanded="false" aria-controls="collapse_button_mic_on_off">
                <font color="Blue">Lập Lịch Thay Đổi Trạng Thái Bật/Tắt Mic</font>, Trạng Thái:&nbsp;
                <?php
                echo isset($data['mic_on_off']['active']) ? ($data['mic_on_off']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>';
                ?>
              </h5>
              <div id="collapse_button_mic_on_off" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_mic_on_off">
             <div class="alert alert-success" role="alert">
			<?php
			// Dữ liệu mặc định nếu chưa có
			if (!isset($data['mic_on_off']) || empty($data['mic_on_off'])) {
				$data['mic_on_off'] = [
					'active' => false,
					'date'   => ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
					'time'   => ["23:59"],
					'action' => ["off"]];
			}
			$change_mic_on_off = $data['mic_on_off'];
			?>
			<div class="row mb-3">
			  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i>:</label>
			  <div class="col-sm-9">
			  <div class="form-switch">
				<input type="checkbox" class="form-check-input border-success" name="change_mic_on_off_active" value="1" <?= $change_mic_on_off['active'] ? 'checked' : '' ?>>
			  </div>
			  </div>
			</div>

<?php
render_system_scheduler_options(
    'mic_on_off',
    $data['mic_on_off'] ?? [],
    'Bật/Tắt microphone',
    'time-mic_on_off',
    'mic_on_off_date'
);
?>

			<div class="row mb-3">
			  <label class="col-sm-3 col-form-label">Thời gian: Bật/Tắt</label>
			  <div class="col-sm-9">
				<div id="time-mic_on_off">
				  <?php foreach ($change_mic_on_off['time'] as $i => $time): ?>
					<div class="input-group mb-2 time-row" id="mic-row-<?= $i ?>">
					  <input type="time" step="60" class="form-control border-success" name="mic_on_off_time[]" value="<?= htmlspecialchars($time) ?>">
					  <select class="form-select border-success" name="mic_on_off_action[]">
						<option value="on"  <?= ($change_mic_on_off['action'][$i] ?? '') === 'on'  ? 'selected' : '' ?>>Bật Mic</option>
						<option value="off" <?= ($change_mic_on_off['action'][$i] ?? '') === 'off' ? 'selected' : '' ?>>Tắt Mic</option>
					  </select>
					<button class="btn btn-primary" type="button" onclick="run_test_task('mic_on_off', '<?= isset($change_mic_on_off['action'][$i]) ? htmlspecialchars($change_mic_on_off['action'][$i]) : '' ?>')"><i class="bi bi-play" title="Chạy Test Tác Vụ Này"></i></button>
					  <button type="button"class="btn btn-danger border-success" onclick="removeMicRow('mic-row-<?= $i ?>')"><i class="bi bi-trash"></i></button>
					</div>
				  <?php endforeach; ?>
				</div>
				<button type="button" class="btn btn-success rounded-pill" id="add-mic-time">Thêm thời gian</button>
			  </div>
			</div>

              </div>
            </div>
          </div>
          </div>

          <div class="card accordion" id="accordion_button_play_playlist">
            <div class="card-body">
              <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_play_playlist" aria-expanded="false" aria-controls="collapse_button_play_playlist">
                Lập Lịch Phát Danh Sách Nhạc, PlayList:&nbsp;
                <?php
                echo isset($data['play_play_playlist']['active']) ? ($data['play_play_playlist']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>';
                ?>
              </h5>
              <div id="collapse_button_play_playlist" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_play_playlist">
               <div class="alert alert-success" role="alert">
			   <?php
                $schedulerPlaylists = [];
                $schedulerPlaylistManifestPath = __DIR__.'/includes/cache/PlayLists.json';
                if (is_file($schedulerPlaylistManifestPath)) {
                  $schedulerPlaylistManifest = json_decode((string)file_get_contents($schedulerPlaylistManifestPath), true);
                  if (is_array($schedulerPlaylistManifest) && isset($schedulerPlaylistManifest['playlists']) && is_array($schedulerPlaylistManifest['playlists'])) {
                    $schedulerPlaylists = $schedulerPlaylistManifest['playlists'];
                  }
                }
                $schedulerPlaylistIds = array_values(array_filter(array_map(fn($item) => (string)($item['id'] ?? ''), $schedulerPlaylists)));
                //Kiểm tra dữ liệu play_play_playlist và gán giá trị mặc định nếu không có
                if (!isset($data['play_play_playlist']) || empty($data['play_play_playlist'])) {
                  $data['play_play_playlist'] = [
                    'active' => false,
                    'date' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
                    'time' => ["17:30"],
                    'playlist_id' => [""]
                  ];
                }
                $play_play_playlist = $data['play_play_playlist'];
                $playlistScheduleEntries = [];
                if (!empty($play_play_playlist['schedules']) && is_array($play_play_playlist['schedules'])) {
                  $playlistScheduleEntries = $play_play_playlist['schedules'];
                } else {
                  // Tự chuyển cấu trúc cũ (nhiều giờ dùng chung chế độ chạy) sang từng lịch độc lập.
                  $legacyTimes = is_array($play_play_playlist['time'] ?? null) ? $play_play_playlist['time'] : [];
                  $legacyIds = is_array($play_play_playlist['playlist_id'] ?? null) ? $play_play_playlist['playlist_id'] : [];
                  foreach ($legacyTimes as $legacyIndex => $legacyTime) {
                    $playlistScheduleEntries[] = [
                      'id' => 'legacy_' . $legacyIndex,
                      'active' => true,
                      'time' => [$legacyTime],
                      'playlist_id' => $legacyIds[$legacyIndex] ?? '',
                      'date' => $play_play_playlist['date'] ?? array_keys($week_days),
                      'recurrence' => $play_play_playlist['recurrence'] ?? ['type' => 'legacy'],
                      'conditions' => $play_play_playlist['conditions'] ?? ['mode' => 'always'],
                      'max_duration_seconds' => $play_play_playlist['max_duration_seconds'] ?? 0
                    ];
                  }
                }
                if (!$playlistScheduleEntries) {
                  $playlistScheduleEntries[] = ['id'=>'playlist_0', 'active'=>true, 'time'=>['17:30'], 'playlist_id'=>'', 'date'=>array_keys($week_days), 'recurrence'=>['type'=>'legacy'], 'conditions'=>['mode'=>'always'], 'max_duration_seconds'=>0];
                }
                $playlistScheduleEntries = array_values($playlistScheduleEntries);
                ?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="play_playlist_active" id="play_playlist_active" value="<?php echo $play_play_playlist['active']; ?>" <?= $play_play_playlist['active'] ? 'checked' : '' ?>>
                    </div>
                  </div>
                </div>

                <p class="text-muted">Mỗi khung bên dưới là một lịch độc lập: PlayList, thời gian, chế độ chạy và điều kiện có thể khác nhau.</p>
                <div id="playlist-schedules-container">
                  <?php foreach ($playlistScheduleEntries as $index => $playlistSchedule): ?>
                    <?php
                      $scheduleSlots = isset($playlistSchedule['slots']) && is_array($playlistSchedule['slots']) ? $playlistSchedule['slots'] : [];
                      if (!$scheduleSlots) {
                        $scheduleTimes = is_array($playlistSchedule['time'] ?? null) ? $playlistSchedule['time'] : [$playlistSchedule['time'] ?? ''];
                        $schedulePlaylistIds = is_array($playlistSchedule['playlist_id'] ?? null) ? $playlistSchedule['playlist_id'] : [];
                        foreach ($scheduleTimes as $slotIndex => $scheduleTime) {
                          $scheduleSlots[] = ['time'=>$scheduleTime, 'playlist_id'=>$schedulePlaylistIds[$slotIndex] ?? (is_string($playlistSchedule['playlist_id'] ?? null) ? $playlistSchedule['playlist_id'] : '')];
                        }
                      }
                      $scheduleSlots = array_values(array_filter($scheduleSlots, 'is_array'));
                      if (!$scheduleSlots) $scheduleSlots[] = ['time'=>'', 'playlist_id'=>''];
                    ?>
                    <div class="card border-primary mb-3 playlist-schedule-card" data-playlist-schedule-index="<?= intval($index) ?>">
                      <div class="card-body">
                        <input type="hidden" name="playlist_schedules[<?= $index ?>][id]" value="<?= htmlspecialchars($playlistSchedule['id'] ?? ('playlist_'.$index)) ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3"><b>Lịch PlayList #<?= $index + 1 ?></b><button class="btn btn-danger btn-sm playlist-schedule-delete" type="button"><i class="bi bi-trash"></i> Xóa lịch</button></div>
                        <div class="row mb-3"><label class="col-sm-3 col-form-label">Bật lịch này:</label><div class="col-sm-9 form-switch"><input class="form-check-input border-success" type="checkbox" name="playlist_schedules[<?= $index ?>][active]" <?= !isset($playlistSchedule['active']) || $playlistSchedule['active'] ? 'checked' : '' ?>></div></div>
                        <div class="row mb-3"><label class="col-sm-3 col-form-label">Khung giờ và PlayList:</label><div class="col-sm-9"><div class="playlist-schedule-times" id="playlist-schedule-<?= $index ?>"><?php foreach ($scheduleSlots as $slotIndex => $scheduleSlot): $slotPlaylistId = (string)($scheduleSlot['playlist_id'] ?? ''); ?><div class="input-group mb-2 playlist-time-row"><input class="form-control border-success" type="time" step="60" required name="playlist_schedules[<?= $index ?>][slots][<?= $slotIndex ?>][time]" value="<?= htmlspecialchars($scheduleSlot['time'] ?? '') ?>"><select class="form-select border-primary scheduler-playlist-select <?= ($slotPlaylistId !== '' && !in_array($slotPlaylistId, $schedulerPlaylistIds, true)) ? 'is-invalid' : '' ?>" name="playlist_schedules[<?= $index ?>][slots][<?= $slotIndex ?>][playlist_id]"><option value="">PlayList mặc định</option><?php if ($slotPlaylistId !== '' && !in_array($slotPlaylistId, $schedulerPlaylistIds, true)): ?><option value="<?= htmlspecialchars($slotPlaylistId, ENT_QUOTES, 'UTF-8') ?>" selected>PlayList đã bị xóa hoặc không còn tồn tại</option><?php endif; ?><?php foreach ($schedulerPlaylists as $playlist): ?><option value="<?= htmlspecialchars($playlist['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" <?= ($slotPlaylistId === ($playlist['id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($playlist['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select><button class="btn btn-primary scheduler-playlist-test" type="button" title="Chạy thử PlayList của dòng này"><i class="bi bi-play"></i></button><button class="btn btn-outline-danger playlist-time-delete" type="button" title="Xóa khung giờ"><i class="bi bi-trash"></i></button></div><?php endforeach; ?></div><small class="text-muted">Mỗi khung giờ có thể chọn một PlayList khác nhau.</small><br><button class="btn btn-outline-success btn-sm scheduler-add-time playlist-time-add" data-scheduler-index="system-playlist_<?= $index ?>" type="button"><i class="bi bi-plus-circle"></i> Thêm khung giờ và PlayList</button></div></div>
                        <?php render_system_scheduler_options('playlist_'.$index, $playlistSchedule, 'Phát playlist', 'playlist-schedule-'.$index, 'playlist_schedules['.$index.'][date]', 'playlist_schedules['.$index.']'); ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <button class="btn btn-success rounded-pill" type="button" id="add-time-play_play_playlist"><i class="bi bi-plus-circle"></i> Thêm lịch PlayList</button>
				<hr/>
				
              </div>
            </div>
          </div>
          </div>

          <div class="card accordion" id="accordion_button_play_all_local">
            <div class="card-body">
              <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_play_all_local" aria-expanded="false" aria-controls="collapse_button_play_all_local">
                Lập Lịch Phát Toàn Bộ Bài Hát Trong Thẻ Nhớ Nội Bộ, Local: &nbsp;
                <?php
                echo isset($data['play_all_music_local']['active']) ? ($data['play_all_music_local']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>';
                ?>
              </h5>
              <div id="collapse_button_play_all_local" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_play_all_local">
              <div class="alert alert-success" role="alert">
			  <?php
                // Kiểm tra dữ liệu play_all_music_local và gán giá trị mặc định nếu không có
                if (!isset($data['play_all_music_local']) || empty($data['play_all_music_local'])) {
                  $data['play_all_music_local'] = [
                    'active' => false,
                    'date' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
                    'time' => ["17:45"]
                  ];
                }
                $play_all_music_local = $data['play_all_music_local'];
                ?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="play_all_local_active" id="play_all_local_active" value="<?php echo $play_all_music_local['active']; ?>" <?= $play_all_music_local['active'] ? 'checked' : '' ?>>
                    </div>
                  </div>
                </div>
<?php
render_system_scheduler_options(
    'play_all_music_local',
    $data['play_all_music_local'] ?? [],
    'Phát nhạc local',
    'time-on-play_all_music_local',
    'dates_play_all_local'
);
?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Thời Gian Kích Hoạt:</label>
                  <div class="col-sm-9">
                    <div class="time-inputs_player_local" id="time-on-play_all_music_local">
                      <?php foreach ($play_all_music_local['time'] as $index => $time): ?>
                        <div class="time-input-play_all_music_local input-group mb-3" id="time-play_all_music_local-<?= $index ?>">
                          <input class="form-control border-success" type="time" step="60" name="time_player_local[]" value="<?= htmlspecialchars($time) ?>">
						  <button class="btn btn-primary" type="button" onclick="run_test_task('play_all_music_local', '')"><i class="bi bi-play" title="Chạy Test Tác Vụ Này"></i></button>
                          <button class="btn btn-danger border-success" title="Xóa thời gian Bật này" type="button" id="delete-play_all_music_local-<?= $index ?>"><i class="bi bi-trash"></i></button>
                        </div>
                      <?php endforeach; ?>
                      <button class="btn btn-success rounded-pill" type="button" id="add-time-play_all_music_local">Thêm thời gian</button>
                    </div>
                  </div>
                </div>
			<div class="row mb-3">
            <label for="schedule_config_path" class="col-sm-3 col-form-label"><b>Đường Dẫn Thư Mục Music Local:</b></label>
            <div class="col-sm-9">
              <input disabled="" class="form-control border-danger" type="text" name="schedule_config_path" id="schedule_config_path" value="<?php echo $VBot_Offline.$Config['media_player']['music_local']['path'].'/'; ?>">
            </div>
          </div>
              </div>
            </div>
          </div>
          </div>

          <div class="card accordion" id="accordion_button_stop_media_player">
            <div class="card-body">
              <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_stop_media_player" aria-expanded="false" aria-controls="collapse_button_stop_media_player">
                Lập Lịch Dừng Phát Media Player: Nhạc, Báo, Tin Tức, &nbsp; Trạng Thái: &nbsp;
                <?php
                echo isset($data['stop_media_player']['active']) ? ($data['stop_media_player']['active'] ? ' <font color=green> Bật</font>' : ' <font color=red> Tắt</font>') : '<font color=gray> Không xác định</font>';
                ?>
              </h5>
              <div id="collapse_button_stop_media_player" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_stop_media_player">
               <div class="alert alert-success" role="alert">
			   <?php
                // Kiểm tra dữ liệu stop_media_player và gán giá trị mặc định nếu không có
                if (!isset($data['stop_media_player']) || empty($data['stop_media_player'])) {
                  $data['stop_media_player'] = [
                    'active' => false,
                    'date' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
                    'time' => ["03:09"]
                  ];
                }
                $stop_media_player = $data['stop_media_player'];
                ?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="stop_media_player_active" id="stop_media_player_active" value="<?php echo $stop_media_player['active']; ?>" <?= $stop_media_player['active'] ? 'checked' : '' ?>>
                    </div>
                  </div>
                </div>
<?php
render_system_scheduler_options(
    'stop_media_player',
    $data['stop_media_player'] ?? [],
    'Dừng Media Player',
    'time-on-stop_media_player',
    'dates_stop_media_player'
);
?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Thời Gian Kích Hoạt:</label>
                  <div class="col-sm-9">
                    <div class="time-inputs_stop_media_player" id="time-on-stop_media_player">
                      <?php foreach ($stop_media_player['time'] as $index => $time): ?>
                        <div class="time-input-stop_media_player input-group mb-3" id="time-stop_media_player-<?= $index ?>">
                          <input class="form-control border-success" type="time" step="60" name="time_stop_media_player[]" value="<?= htmlspecialchars($time) ?>">
						  <button class="btn btn-primary" type="button" onclick="run_test_task('stop_media_player', '')"><i class="bi bi-play" title="Chạy Test Tác Vụ Này"></i></button>
                          <button class="btn btn-danger border-success" title="Xóa thời gian Bật này" type="button" id="delete-stop_media_player-<?= $index ?>"><i class="bi bi-trash"></i></button>
                        </div>
                      <?php endforeach; ?>
                      <button class="btn btn-success rounded-pill" type="button" id="add-time-stop_media_player">Thêm thời gian</button>
                    </div>
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
               <div class="alert alert-success" role="alert">
			   <?php
                // Kiểm tra dữ liệu restart_vbot và gán giá trị mặc định nếu không có
                if (!isset($data['restart_vbot']) || empty($data['restart_vbot'])) {
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
                      <input class="form-check-input border-success" type="checkbox" name="restart_vbot_service_active" id="restart_vbot_service_active" value="<?php echo $restart_vbot['active']; ?>" <?= $restart_vbot['active'] ? 'checked' : '' ?>>
                    </div>
                  </div>
                </div>
<?php
render_system_scheduler_options(
    'restart_vbot',
    $data['restart_vbot'] ?? [],
    'Restart VBot',
    'time-on-restart_vbot',
    'dates_restart_vbot'
);
?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Thời Gian Kích Hoạt:</label>
                  <div class="col-sm-9">
                    <div class="time-inputs_restart_vbot" id="time-on-restart_vbot">
                      <?php foreach ($restart_vbot['time'] as $index => $time): ?>
                        <div class="time-input-restart_vbot input-group mb-3" id="time-restart_vbot-<?= $index ?>">
                          <input class="form-control border-success" type="time" step="60" name="time_restart_vbot[]" value="<?= htmlspecialchars($time) ?>">
						  <button class="btn btn-primary" type="button" onclick="run_test_task('restart_vbot', '')"><i class="bi bi-play" title="Chạy Test Tác Vụ Này"></i></button>
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
               <div class="alert alert-success" role="alert">
			   <?php
                // Kiểm tra dữ liệu reboot_os và gán giá trị mặc định nếu không có
                if (!isset($data['reboot_os']) || empty($data['reboot_os'])) {
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
                      <input class="form-check-input border-success" type="checkbox" name="reboot_os_active" id="reboot_os_active" value="<?php echo $reboot_os['active']; ?>" <?= $reboot_os['active'] ? 'checked' : '' ?>>
                    </div>
                  </div>
                </div>
<?php
render_system_scheduler_options(
    'reboot_os',
    $data['reboot_os'] ?? [],
    'Reboot hệ điều hành',
    'time-on-reboot_os',
    'dates_reboot_os'
);
?>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Thời Gian Kích Hoạt:</label>
                  <div class="col-sm-9">
                    <div class="time-inputs_reboot_os" id="time-on-reboot_os">
                      <?php foreach ($reboot_os['time'] as $index => $time): ?>
                        <div class="time-input-reboot_os input-group mb-3" id="time-reboot_os-<?= $index ?>">
                          <input class="form-control border-success" type="time" step="60" name="time_reboot_os[]" value="<?= htmlspecialchars($time) ?>">
						  <button class="btn btn-primary" type="button" onclick="run_test_task('reboot_os', '')"><i class="bi bi-play" title="Chạy Test Tác Vụ Này"></i></button>
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
          </div>

<div class="alert alert-primary" role="alert">
Để Bật Tắt Sử Dụng Chức Năng Này Hãy Đi Tới: <b>Cấu Hình Config</b> -> <b>Cài Đặt Lập Lịch, Lời Nhắc, Thông báo, V..v... (Schedule)</b> -> <b>Kích Hoạt</b>
</div>
          <center>
            <button class="btn btn-danger rounded-pill" type="submit" name="delete_all_Scheduler" onclick="return confirmRestore('Bạn có chắc chắn muốn xóa tất cả dữ liệu cấu hình Lời Nhắc, Thông Báo không')">
              <i class="bi bi-trash"></i> Xóa Dữ Liệu Cấu hình</button>
          </center>
          <h5 class="card-title">
            <font color="green">Dữ Liệu Cấu Hình:</font>
          </h5>
          <div class="row mb-3">
            <label for="schedule_data_json_path" class="col-sm-3 col-form-label"><b>Đường Dẫn/Path File Cấu Hình:</b></label>
            <div class="col-sm-9">
			<div class="input-group">
              <input disabled class="form-control border-danger" type="text" name="schedule_data_json_path" id="schedule_data_json_path" value="<?php echo $VBot_Offline . $Config['schedule']['data_json_file']; ?>">
<button type="button" class="btn btn-success border-danger" title="Xem dữ liệu Đã cấu hình Custom Home Assistant" id="openModalBtn_laplich_thongbao"><i class="bi bi-eye"></i></button>
<button type="button" class="btn btn-info border-danger" title="Tải Xuống file: <?php echo $json_file; ?>" onclick="downloadFile('<?php echo $json_file; ?>')"><i class="bi bi-download"></i></button>
            </div>
            </div>
          </div>
          <div class="row mb-3">
            <label for="schedule_audio_path" class="col-sm-3 col-form-label"><b>Đường Dẫn/Path File Âm Thanh:</b></label>
            <div class="col-sm-9">
              <input disabled class="form-control border-danger" type="text" name="schedule_audio_path" id="schedule_audio_path" value="<?php echo $VBot_Offline . $Config['schedule']['audio_path'] . '/'; ?>">
            </div>
          </div>
          <hr />
          <h5 class="card-title">
            <font color="green">Tải Lên Tệp Âm Thanh <i class="bi bi-question-circle-fill" onclick="show_message('Dữ Liệu Tệp/File Âm Thanh Khi Được Tải Lên Sẽ Nằm Ở: <b><?php echo $VBot_Offline . $Config['schedule']['audio_path'] . '/' ?></b>')"></i>:</font>
          </h5>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label"><b>Tải Lên Tệp <i class="bi bi-question-circle-fill" onclick="show_message('Dữ Liệu Tệp/File Âm Thanh Khi Được Tải Lên Sẽ Nằm Ở: <b><?php echo $VBot_Offline . $Config['schedule']['audio_path'] . '/' ?></b>')"></i>:</b></label>
            <div class="col-sm-9">
              <div class="input-group">
                <input class="form-control border-success" type="file" name="fileToUpload_Scheduler_Upload_Audio" accept=".mp3,.wav,.ogg,.aac">
                <button class="btn btn-warning border-success" type="submit" name="Scheduler_Upload_Audio_Submit" value="Scheduler_Upload_Audio" title="Tải Lên File Âm Thanh">Tải Lên</button>
                <button class="btn btn-primary border-success" type="button" onclick="get_audio_schedule()" title="Hiển Thị Danh Sách Tệp Âm Thanh"><i class="bi bi-music-note-list"></i></button>
              </div>
            </div>
          </div>
          <div id="du_lieu_audio_schedule"></div>
          <hr />
          <h5 class="card-title">
            <font color="green">Khôi Phục Dữ Liệu:</font>
          </h5>
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
              $jsonFiles = glob($Config['backup_upgrade']['scheduler']['backup_path'] . '/*.json');
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
                  echo '<option value="' . htmlspecialchars($Config['backup_upgrade']['scheduler']['backup_path'] . '/' . $fileName) . '">' . htmlspecialchars($fileName) . '</option>';
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
          <b>
            <font color=blue>
              <div id="name_file_showzz"></div>
            </font>
          </b>
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
      var xhr = vbotCreateXhr();
      var url = "includes/php_ajax/Media_Player_Search.php?audio_schedule";
      xhr.open("GET", url, true);
      xhr.responseType = "json";
      xhr.send();
      xhr.onload = function() {
        if (xhr.status == 200) {
          var response = xhr.response;
          if (Array.isArray(response)) {
            loading("hide");
            var audioScheduleDiv = document.getElementById("du_lieu_audio_schedule");
            audioScheduleDiv.innerHTML = '';
            var table = document.createElement('table');
            table.classList.add('table', 'table-bordered', 'border-primary');
            var thead = document.createElement('thead');
            var headerRow = document.createElement('tr');
            headerRow.innerHTML = '<th style="text-align: center; vertical-align: middle;">Tên tệp</th><th style="text-align: center; vertical-align: middle;">Kích thước (MB)</th><th style="text-align: center; vertical-align: middle;">Hành Động</th>';
            thead.appendChild(headerRow);
            table.appendChild(thead);
            var tbody = document.createElement('tbody');
            response.forEach(function(audio) {
              var row = document.createElement('tr');
              var audioName = String(audio && audio.name !== undefined ? audio.name : '');
              var audioSize = String(audio && audio.size !== undefined ? audio.size : '0');
              var audioPath = String(audio && audio.full_path !== undefined ? audio.full_path : '');

              var nameCell = document.createElement('td');
              var sizeCell = document.createElement('td');
              var actionCell = document.createElement('td');
              [nameCell, sizeCell, actionCell].forEach(function(cell) {
                cell.style.textAlign = 'center';
                cell.style.verticalAlign = 'middle';
              });
              nameCell.textContent = audioName;
              sizeCell.textContent = audioSize + ' MB';

              function createAudioActionButton(className, title, iconClass, handler) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = className;
                button.title = title;
                button.style.margin = '0 2px';
                var icon = document.createElement('i');
                icon.className = iconClass;
                button.appendChild(icon);
                button.addEventListener('click', handler);
                return button;
              }

              actionCell.appendChild(createAudioActionButton(
                'btn btn-primary', 'Phát tệp âm thanh', 'bi bi-play-circle',
                function() { playAudio(audioPath); }
              ));
              actionCell.appendChild(createAudioActionButton(
                'btn btn-success', 'Tải xuống tệp âm thanh', 'bi bi-download',
                function() { downloadFile(audioPath); }
              ));
              actionCell.appendChild(createAudioActionButton(
                'btn btn-danger', 'Xóa tệp âm thanh', 'bi bi-trash',
                function() { deleteFile(audioPath, 'du_lieu_audio_schedule'); }
              ));

              row.appendChild(nameCell);
              row.appendChild(sizeCell);
              row.appendChild(actionCell);
              tbody.appendChild(row);
            });
            table.appendChild(tbody);
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
          document.getElementById('name_file_showzz').textContent = "Tên File: " + filePath.split('/').pop();
          $('#openModalBtn_lichthongbao').modal('show');
        }
      } else {
        read_loadFile(filePath);
        document.getElementById('name_file_showzz').textContent = "Tên File: " + filePath.split('/').pop();
        $('#openModalBtn_lichthongbao').modal('show');
      }
    }

function loadAudioFiles(selectId) {
  const directories = [
    "<?php echo $VBot_Offline . $Config['schedule']['audio_path'] ?>",
    "<?php echo $VBot_Offline . $Config['media_player']['music_local']['path'] ?>"
  ];
  const selectElem = document.getElementById(selectId);
  if (!selectElem) return;
  selectElem.innerHTML = "<option value=''>Chọn tệp âm thanh</option>";
  directories.forEach(dir => {
    const xhr = vbotCreateXhr();
    const url = "includes/php_ajax/Show_file_path.php?show_all_file&directory_path=" + encodeURIComponent(dir);
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response.success && Array.isArray(response.data)) {
            response.data.forEach(file => {
              let opt = document.createElement("option");
              opt.value = file.path;
              opt.textContent = file.name + " (" + file.size + ")";
              selectElem.appendChild(opt);
            });
          }
        } catch (e) { console.error(e); }
      }
    };
    xhr.send();
  });
}
  </script>
  <script>
    //Hiển thị modal xem nội dung file json Home_Assistant.json
    ['openModalBtn_laplich_thongbao'].forEach(function(id) {
      document.getElementById(id).addEventListener('click', function() {
        var file_name_hassJSON = "<?php echo $json_file; ?>";
        read_loadFile(file_name_hassJSON);
        document.getElementById('name_file_showzz').textContent = "Tên File: " + file_name_hassJSON.split('/').pop();
        $('#openModalBtn_lichthongbao').modal('show');
      });
    });
  </script>
  <script>
    // Trinh duyet tu quyet dinh giao dien cua input type="time" theo locale,
    // vi vay mot so thiet bi se hien SA/CH. Chuyen toan bo o gio sang text
    // co kiem tra HH:mm de giao dien Scheduler luon dung dinh dang 24 gio.
    const schedulerTime24Pattern = /^([01]\d|2[0-3]):([0-5]\d)$/;

    function normalizeSchedulerTime24(value) {
      const raw = String(value || '').trim();
      if (!raw) return '';
      let match = raw.match(/^(\d{1,2}):?(\d{2})$/);
      if (!match) return raw;
      const hour = Number(match[1]);
      const minute = Number(match[2]);
      if (hour > 23 || minute > 59) return raw;
      return String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
    }

    function validateSchedulerTime24(input, normalize = false) {
      if (normalize) input.value = normalizeSchedulerTime24(input.value);
      const valid = !input.value || schedulerTime24Pattern.test(input.value);
      input.setCustomValidity(valid ? '' : 'Vui lòng nhập giờ theo định dạng 24 giờ HH:mm, từ 00:00 đến 23:59.');
      input.classList.toggle('is-invalid', !valid);
      return valid;
    }

    function initializeSchedulerTime24(root = document) {
      const inputs = [];
      if (root.nodeType === Node.ELEMENT_NODE && root.matches('input[type="time"], input.scheduler-time-24h')) inputs.push(root);
      if (root.querySelectorAll) inputs.push(...root.querySelectorAll('input[type="time"], input.scheduler-time-24h'));
      inputs.forEach(input => {
        if (input.dataset.time24Initialized === '1') return;
        input.dataset.time24Initialized = '1';
        input.type = 'text';
        input.classList.add('scheduler-time-24h');
        input.inputMode = 'numeric';
        input.maxLength = 5;
        input.placeholder = 'HH:mm';
        input.autocomplete = 'off';
        input.title = 'Giờ 24h, định dạng HH:mm (00:00–23:59)';
        input.value = normalizeSchedulerTime24(input.value);
        input.addEventListener('input', () => {
          input.value = input.value.replace(/[^0-9:]/g, '').slice(0, 5);
          validateSchedulerTime24(input, false);
        });
        input.addEventListener('blur', () => validateSchedulerTime24(input, true));
        validateSchedulerTime24(input, false);
      });
    }

    initializeSchedulerTime24();
    new MutationObserver(mutations => mutations.forEach(mutation =>
      mutation.addedNodes.forEach(node => initializeSchedulerTime24(node))
    )).observe(document.body, {childList: true, subtree: true});

    let newTaskIndex = <?= count($data['notification_schedule']) ?>;
    let deletedTasks = [];
    function createDateElement(index, specific_date, container) {
      const dateGroupId = index + '-' + specific_date;
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
      container.innerHTML += dateElementHTML;
    }

    // Hàm thêm nút "Thêm ngày" vào cuối container
    function addAddButton(container, index) {
      const id_button_ban_dau = document.getElementById('button_hien_thi_ngay_' + index);
      if (id_button_ban_dau) {
        id_button_ban_dau.remove();
      }
      const existingAddButton = container.querySelector('input[type="button"]');
      if (existingAddButton) {
        existingAddButton.remove();
      }
      container.innerHTML += '<div class="mt-3"><input type="button" class="btn btn-info rounded-pill" value="Thêm ngày cụ thể" onclick="addDateInput(' + index + ')"></div>';
    }

    //Hàm thêm một ngày mới vào container
    function addDateInput(index) {
      const currentDate = new Date();
      const day = ("0" + currentDate.getDate()).slice(-2);
      const month = ("0" + (currentDate.getMonth() + 1)).slice(-2);
      const year = currentDate.getFullYear();
      const formattedDate = day + '/' + month + '/' + year;
      const container = document.querySelector('#date-container-' + index);
      createDateElement(index, formattedDate, container);
      addAddButton(container, index);
    }

    //Hàm xóa ngày khi người dùng nhấn nút xóa
    function removeDateInput(dateGroupId) {
      const dateGroup = document.getElementById('date-group-' + dateGroupId);
      if (dateGroup) {
        dateGroup.remove();
      }
    }

    //Xóa một tác vụ
    function deleteTask(taskIndex) {
      if (confirm("Bạn có chắc muốn xóa tác vụ này?")) {
        const taskDiv = document.getElementById("task-" + taskIndex);
        deletedTasks.push({
          index: taskIndex,
          content: taskDiv.innerHTML
        });
        taskDiv.innerHTML = '<div style="color: red;">Đã xóa tác vụ thứ tự: <b>' + taskIndex + '</b> Chờ lưu thay đổi để áp dụng. <button class="btn btn-warning rounded-pill" type="button" onclick="restoreTask(' + taskIndex + ')">Khôi Phục</button></div>';
      }
    }

    //Phục hồi một tác vụ
    function restoreTask(taskIndex) {
      const task = deletedTasks.find(task => task.index === taskIndex);
      if (task) {
        const taskDiv = document.getElementById("task-" + taskIndex);
        taskDiv.innerHTML = task.content;
        deletedTasks = deletedTasks.filter(task => task.index !== taskIndex);
      }
    }

    function addNewTask() {
      const taskContainer = document.getElementById('task-container');
      let taskHtml =
        "<hr/><div class='card accordion'><div class='card-body'><div id='task-" + newTaskIndex + "'>" +
        "<h5 class='card-title text-danger'>Lập Lịch, Tác Vụ Thông Báo Mới:</h5><div class='alert alert-primary' role='alert'>" +
        "<div class='row mb-3'>" +
        "<label for='active-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Kích hoạt:</label>" +
        "<div class='col-sm-9'>" +
        "<div class='form-switch'>" +
        "<input type='checkbox' class='form-check-input' id='active-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][active]' checked>" +
        "</div>" +
        "</div>" +
        "</div>" +
        "<div class='row mb-3'><label class='col-sm-3 col-form-label'>Thời gian tối đa (giây/s) <i class='bi bi-question-circle-fill' onclick=\"show_message('Là khoảng thời gian lâu nhất mà một tác vụ thông báo được phép phát. Tác vụ được chạy tối đa 60 giây. Khi hết 60 giây mà TTS, file âm thanh hoặc URL media vẫn chưa kết thúc, Scheduler sẽ tự dừng tác vụ và ghi lịch sử trạng thái: 0: không giới hạn, chờ tác vụ phát xong.')\"></i>:</label><div class='col-sm-9'><input type='number' min='0' max='86400' class='form-control border-success' name='notification_schedule[" + newTaskIndex + "][data][max_duration_seconds]' value='0'><small class='text-muted'>Đơn vị giây, đặt 0 để không giới hạn, chờ tác vụ phát xong.</small></div></div>" +
        "<div class='row mb-3'><label class='col-sm-3 col-form-label'>Chế độ chạy:</label><div class='col-sm-9'>" +
        "<select class='form-select border-success scheduler-recurrence-type' data-scheduler-index='" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][recurrence][type]'><option value='legacy'>Theo thứ trong tuần</option><option value='daily'>Hằng ngày</option><option value='daily_months'>Hằng ngày (Lựa chọn tháng)</option><option value='weekdays'>Ngày làm việc (Thứ Hai–Thứ Sáu)</option><option value='weekends'>Cuối tuần (Thứ Bảy–Chủ Nhật)</option><option value='once'>Chỉ một lần</option><option value='monthly'>Một ngày mỗi tháng</option><option value='days_of_month'>N ngày trong tháng</option></select>" +
        "<div class='input-group mt-2 scheduler-recurrence-fields' data-recurrence-for='weekdays weekends monthly'><span class='input-group-text border-success'>Bắt đầu</span><input type='date' class='form-control border-success' name='notification_schedule[" + newTaskIndex + "][recurrence][start_date]'><span class='input-group-text border-success'>Kết thúc</span><input type='date' class='form-control border-success' name='notification_schedule[" + newTaskIndex + "][recurrence][end_date]'></div>" +
        "<div class='input-group mt-2 scheduler-recurrence-fields' data-recurrence-for='once'><span class='input-group-text border-success'>Ngày thực hiện</span><input type='date' class='form-control border-success' name='notification_schedule[" + newTaskIndex + "][recurrence][date]'></div>" +
        "<div class='input-group mt-2 scheduler-recurrence-fields' data-recurrence-for='monthly'><span class='input-group-text border-success'>Ngày trong tháng</span><input type='number' min='1' max='31' value='1' class='form-control border-success' name='notification_schedule[" + newTaskIndex + "][recurrence][day]'></div>" +
        "<div class='mt-2 scheduler-recurrence-fields' data-recurrence-for='daily_months'><div class='border rounded p-2'>Chọn tháng áp dụng:<br>" + Array.from({length: 12}, (_, i) => "<label class='me-3'><input type='checkbox' class='form-check-input border-success' checked name='notification_schedule[" + newTaskIndex + "][recurrence][months][]' value='" + (i + 1) + "'> Tháng " + (i + 1) + "</label>").join('') + "</div></div>" +
        "<div class='mt-2 scheduler-recurrence-fields' data-recurrence-for='days_of_month'><div class='border rounded p-2'>Chọn các ngày trong tháng:<br>" + Array.from({length: 31}, (_, i) => "<label class='me-3'><input type='checkbox' class='form-check-input border-success' " + (i === 0 ? "checked " : "") + "name='notification_schedule[" + newTaskIndex + "][recurrence][days][]' value='" + (i + 1) + "'> " + (i + 1) + "</label>").join('') + "</div><small class='text-muted'>Tháng không có ngày đã chọn (ví dụ ngày 31 trong tháng 2) sẽ tự bỏ qua.</small></div></div></div>" +
        "<div class='row mb-3'><label class='col-sm-3 col-form-label'>Điều kiện chạy:</label><div class='col-sm-9'><select class='form-select border-primary mb-2 scheduler-condition-mode' data-scheduler-index='" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][conditions][mode]'><option value='always' selected>Luôn thực thi</option><option value='conditional'>Chỉ thực thi khi thỏa điều kiện</option></select><div class='scheduler-condition-fields' data-scheduler-index='" + newTaskIndex + "'><select class='form-select mb-2 border-success' name='notification_schedule[" + newTaskIndex + "][conditions][mic_state]'><option value='any'>Không phụ thuộc mic</option><option value='on'>Chỉ khi mic bật</option><option value='off'>Chỉ khi mic tắt</option></select><label><input type='checkbox' class='form-check-input border-success' name='notification_schedule[" + newTaskIndex + "][conditions][only_when_idle]'> Chỉ khi VBot rảnh</label><br><label><input type='checkbox' class='form-check-input border-success' name='notification_schedule[" + newTaskIndex + "][conditions][skip_if_media_playing]'> Bỏ qua khi phát media</label><br><label><input type='checkbox' class='form-check-input border-success' name='notification_schedule[" + newTaskIndex + "][conditions][skip_if_bluetooth_playing]'> Bỏ qua khi Bluetooth phát</label><br><label><input type='checkbox' class='form-check-input border-success' name='notification_schedule[" + newTaskIndex + "][conditions][skip_if_airplay_playing]'> Bỏ qua khi AirPlay phát</label></div></div></div>" +
        "<div class='row mb-3'>" +
        "<label for='name-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Tên tác vụ <font color='red' size='6' title='Bắt Buộc Nhập'>*</font>:</label>" +
        "<div class='col-sm-9'>" +
        "<input required class='form-control border-success' type='text' id='name-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][name]' placeholder='Cần nhập tên tác vụ mới'>" +
        "<div class='invalid-feedback'>Cần đặt tên định danh cho tác vụ này</div>" +
        "</div>" +
        "</div>" +
        "<div class='row mb-3'>" +
        "<label for='message-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Nội Dung Thông Báo <font color='blue' size='6' title='Có thể nhập, lựa chọn hoặc để trống'>*</font>:</label>" +
        "<div class='col-sm-9'>" +
        "<textarea type='text' rows='3' class='form-control border-success' id='message-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][data][message]' placeholder='Cần nhập nội dung thông báo, Hỗ trợ nhập cả các link: Youtube, zingmp3.vn, nhaccuatui.com, các link nhạc có đuôi âm thanh .mp3, Nếu bỏ trống thì cần chọn dữ liệu tệp âm thanh'></textarea>" +
        "</div>" +
        "</div>" +
        "<div class='row mb-3'>" +
        "<label for='audio_file-" + newTaskIndex + "' class='col-sm-3 col-form-label'>" +
        "Tệp Âm Thanh (Link,URL/PATH) " +
        "<i class='bi bi-question-circle-fill' onclick=\"show_message('Cần nhập thông tin đường dẫn hoặc chọn file âm thanh, hệ thống ưu tiên phát text, nếu text trống thì sẽ phát file âm thanh.')\"></i> <font color='blue' size='6' title='Có thể nhập, lựa chọn hoặc để trống'>*</font>:" +
        "</label>" +
        "<div class='col-sm-9'>" +
        "<div class='input-group mb-3'>" +
        "<select class='form-select border-success' id='audio_file-" + newTaskIndex + "' name='notification_schedule[" + newTaskIndex + "][data][audio_file]' class='form-control border-success'>" +
        "<option value=''>Đang tải danh sách...</option>" +
        "</select>" +
        "<button class='btn btn-success border-success' type='button' onclick=\"playAudio_Schedule('notification_schedule[" + newTaskIndex + "][data][audio_file]')\">" +
        "<i class='bi bi-play-circle'></i></button>" +
        "</div>" +
        "</div>" +
        "</div>" +
        "<div class='row mb-3'>" +
        "<label for='repeat-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Số lần lặp lại <font color='red' size='6' title='Bắt Buộc Nhập'>*</font>:</label>" +
        "<div class='col-sm-9'>" +
        "<input required class='form-control border-success' type='number' id='repeat-" + newTaskIndex + "' min='1' step='1' max ='5' name='notification_schedule[" + newTaskIndex + "][data][repeat]' value='1'>" +
        "<div class='invalid-feedback'>Cần điền số lần lặp lại thông báo</div>" +
        "</div>" +
        "</div>" +
        "<div class='row mb-3 scheduler-legacy-dates' data-scheduler-index='" + newTaskIndex + "'>" +
        "<label for='date-" + newTaskIndex + "' class='col-sm-3 col-form-label'>Các thứ trong tuần <font color='red' size='6' title='Bắt Buộc Nhập'>*</font>:</label>" +
        "<div class='col-sm-9'>" +
        "<div class='form-switch'>";
      taskHtml += "<?php foreach (['Monday' => 'Thứ Hai', 'Tuesday' => 'Thứ Ba', 'Wednesday' => 'Thứ Tư', 'Thursday' => 'Thứ Năm', 'Friday' => 'Thứ Sáu', 'Saturday' => 'Thứ Bảy', 'Sunday' => 'Chủ Nhật'] as $key => $label) : ?>" +
        "<input type='checkbox' class='form-check-input' id='date-" + newTaskIndex + "-<?= $key ?>' name='notification_schedule[" + newTaskIndex + "][date][]' value='<?= $key ?>' checked> " +
        " <label for='date-" + newTaskIndex + "-<?= $key ?>'><?= $label ?></label><br>" +
        "<?php endforeach; ?>" +
        "</div>" +
        "<div id='date-container-" + newTaskIndex + "'>" +
        "<button type='button' class='mt-3 btn btn-info rounded-pill' id='button_hien_thi_ngay_" + newTaskIndex + "' onclick='addDateInput(" + newTaskIndex + ")'>Thêm ngày cụ thể</button>" +
        "</div>" +
        "</div></div>" +
        "<div class='row mb-3'>" +
        "<label class='col-sm-3 col-form-label'>Thời gian (HH:MM) <i class='bi bi-question-circle-fill' onclick='show_message(\"Thời gian theo định dạng giờ, phút phải có dấu : ở giữa, định dạng nhập là 24h: từ 00:00 tới 23:59\")'></i> <font color='red' size='6' title='Bắt Buộc Nhập'>*</font> :</label><br>" +
        "<div class='col-sm-9'>" +
        "<div id='time-container-" + newTaskIndex + "'>" +
        "<div class='input-group mb-3'>" +
        "<input required type='time' step='60' class='form-control border-success time-input' name='notification_schedule[" + newTaskIndex + "][time][]'>" +
        "<button type='button' class='btn btn-danger border-success' onclick='removeTimeInput(" + newTaskIndex + ", this)' style='display: none;'><i class='bi bi-trash'></i></button>" +
        "<div class='invalid-feedback'>Cần nhập thời gian thực hiện của tác vụ</div>" +
        "</div>" +
        "</div>" +
        "<button type='button' class='btn btn-primary rounded-pill scheduler-add-time' data-scheduler-index='" + newTaskIndex + "' onclick='addTimeInput(" + newTaskIndex + ")'>Thêm thời gian</button><br><br>" +
        "<center><button type='button' class='btn btn-danger rounded-pill' onclick='deleteTask(" + newTaskIndex + ")'><i class='bi bi-trash'></i> Xóa tác vụ này</button></center>" +
        "</div>" +
        "</div>" +
        "</div>" +
        "</div></div>" +
        "</div>";
      taskContainer.innerHTML += taskHtml;
      initializeSchedulerRecurrence(taskContainer);

      //Sau khi chèn vào DOM -> gọi API để fill dữ liệu
      loadAudioFiles("audio_file-" + newTaskIndex);

      //Tự động cuộn đến task mới và focus input
      const newTaskElem = document.getElementById("task-" + newTaskIndex);
      if (newTaskElem) {
        newTaskElem.scrollIntoView({
          behavior: "smooth",
          block: "center"
        });
        const firstInput = newTaskElem.querySelector("input[type='text']");
        if (firstInput) firstInput.focus();
      }
      newTaskIndex++
      showMessagePHP("Đã Thêm Ô Nhập Liệu Tác Vụ Lập Lịch Mới, Hãy Điền Thông Tin Vào", 6);
    }

    //Xóa input thời gian
    function removeTimeInput(taskIndex, buttonElement) {
      const timeContainer = document.getElementById('time-container-' + taskIndex);
      timeContainer.removeChild(buttonElement.parentElement);
      updateRemoveButtonVisibility(taskIndex);
    }

    //Cập nhật hiển thị nút "Xóa" khi có ít nhất 2 input
    function updateRemoveButtonVisibility(taskIndex) {
      const timeContainer = document.getElementById('time-container-' + taskIndex);
      const timeInputs = timeContainer.getElementsByTagName('div');
      const removeButtons = timeContainer.getElementsByTagName('button');
      for (let i = 0; i < removeButtons.length; i++) {
        removeButtons[i].style.display = timeInputs.length > 1 ? 'inline' : 'none';
      }
    }
    //Gọi updateRemoveButtonVisibility cho mỗi tác vụ khi tải trang
    document.addEventListener("DOMContentLoaded", function() {
      <?php foreach ($data['notification_schedule'] as $index => $notification) : ?>
        updateRemoveButtonVisibility(<?= $index ?>);
      <?php endforeach; ?>
    });

    // Thêm input thời gian mới
    function addTimeInput(taskIndex) {
      const timeContainer = document.getElementById('time-container-' + taskIndex);
      const timeInputHtml =
        "<div class='input-group mb-3'>" +
        "<input required type='time' step='60' class='form-control border-success time-input' name='notification_schedule[" + taskIndex + "][time][]'>" +
        "<button type='button' class='btn btn-danger border-success' onclick='removeTimeInput(" + taskIndex + ", this)' title='Xóa Thời Gian'><i class='bi bi-trash'></i></button>" +
        "</div>";
      timeContainer.innerHTML += timeInputHtml;
      updateRemoveButtonVisibility(taskIndex);
    }
    //Tạo danh sách giờ
    function generateHourSuggestions() {
      const hours = [];
      for (let hour = 0; hour < 24; hour++) {
        const h = hour.toString().padStart(2, '0');
        hours.push(h);
      }
      return hours;
    }

    //Tạo danh sách phút
    function generateMinuteSuggestions() {
      const minutes = [];
      for (let minute = 0; minute < 60; minute += 1) {
        const m = minute.toString().padStart(2, '0');
        minutes.push(m);
      }
      return minutes;
    }

    //Hiển thị danh sách gợi ý giờ
    function showHourSuggestions(input) {
      const container = input.nextElementSibling;
      const hours = generateHourSuggestions();
      container.innerHTML = '';
      hours.forEach(hour => {
        const option = document.createElement('div');
        option.className = 'suggestion-item';
        option.innerText = hour + ' giờ';
        option.onclick = () => {
          input.dataset.selectedHour = hour;
          input.value = hour + ':--';
          showMinuteSuggestions(input);
        };
        container.appendChild(option);
      });
      container.style.display = 'block';
    }

    //Hiển thị danh sách gợi ý phút
    function showMinuteSuggestions(input) {
      const container = input.nextElementSibling;
      const minutes = generateMinuteSuggestions();
      container.innerHTML = '';
      minutes.forEach(minute => {
        const option = document.createElement('div');
        option.className = 'suggestion-item';
        option.innerText = minute + ' phút';
        option.onclick = () => {
          const selectedHour = input.dataset.selectedHour || '00';
          input.value = selectedHour + ':' + minute;
          container.style.display = 'none';
        };
        container.appendChild(option);
      });
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

<!-- Scripts Restart VBot -->
    let time_Restart_VBot = <?= count($restart_vbot['time']) ?>;
    document.getElementById('add-time-restart_vbot').addEventListener('click', function() {
      const timeOnContainer = document.getElementById('time-on-restart_vbot');
      const inputContainerId = 'time_restart_vbot-' + time_Restart_VBot;
      const inputContainer = document.createElement('div');
      inputContainer.id = inputContainerId;
      inputContainer.classList.add('time-input-restart_vbot', 'input-group', 'mb-3');
      inputContainer.innerHTML = '<input class="form-control border-success" type="time" step="60" name="time_restart_vbot[]"><button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-restart_vbot-' + time_Restart_VBot + '"><i class="bi bi-trash"></i></button>';
      timeOnContainer.insertBefore(inputContainer, this);
      document.getElementById('delete-restart_vbot-' + time_Restart_VBot).addEventListener('click', function() {
        document.getElementById(inputContainerId).remove();
      });
      time_Restart_VBot++;
    });
    document.querySelectorAll('.time-inputs_restart_vbot > div > button').forEach(button => {
      button.addEventListener('click', function() {
        const container = button.parentElement;
        container.remove();
      });
    });
  <!--END Scripts Restart VBot -->

 <!-- Scripts stop media Player -->
    let time_Stop_Media_Player = <?= count($stop_media_player['time']) ?>;
    document.getElementById('add-time-stop_media_player').addEventListener('click', function() {
      const timeOnContainer = document.getElementById('time-on-stop_media_player');
      const inputContainerId = 'time_stop_media_player-' + time_Stop_Media_Player;
      const inputContainer = document.createElement('div');
      inputContainer.id = inputContainerId;
      inputContainer.classList.add('time-input-stop_media_player', 'input-group', 'mb-3');
      inputContainer.innerHTML = '<input class="form-control border-success" type="time" step="60" name="time_stop_media_player[]"><button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-stop_media_player-' + time_Stop_Media_Player + '"><i class="bi bi-trash"></i></button>';
      timeOnContainer.insertBefore(inputContainer, this);
      document.getElementById('delete-stop_media_player-' + time_Stop_Media_Player).addEventListener('click', function() {
        document.getElementById(inputContainerId).remove();
      });
      time_Stop_Media_Player++;
    });
    document.querySelectorAll('.time-inputs_stop_media_player > div > button').forEach(button => {
      button.addEventListener('click', function() {
        const container = button.parentElement;
        container.remove();
      });
    });
  <!--END Scripts stop media Player -->

    //Reboot OS
    let time_REboot_OS = <?= count($reboot_os['time']) ?>;
    document.getElementById('add-time-reboot_os').addEventListener('click', function() {
      const timeOnContainer = document.getElementById('time-on-reboot_os');
      const inputContainerId = 'time_reboot_os-' + time_REboot_OS;
      const inputContainer = document.createElement('div');
      inputContainer.id = inputContainerId;
      inputContainer.classList.add('time-input-reboot_os', 'input-group', 'mb-3');
      inputContainer.innerHTML = '<input class="form-control border-success" type="time" step="60" name="time_reboot_os[]"><button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-reboot_os-' + time_REboot_OS + '"><i class="bi bi-trash"></i></button>';
      timeOnContainer.insertBefore(inputContainer, this);
      document.getElementById('delete-reboot_os-' + time_REboot_OS).addEventListener('click', function() {
        document.getElementById(inputContainerId).remove();
      });
      time_REboot_OS++;
    });
    document.querySelectorAll('.time-inputs_reboot_os > div > button').forEach(button => {
      button.addEventListener('click', function() {
        const container = button.parentElement;
        container.remove();
      });
    });

    //Thay Đổi ÂM Lượng
    let timeVolumeCounter = <?= count($change_volume['time']) ?>;
    document.getElementById('add-time-change_volume').addEventListener('click', function() {
      const container = document.getElementById('time-changes_volumes');
      const inputContainerId = 'time-change_volume-' + timeVolumeCounter;
      const inputContainer = document.createElement('div');
      inputContainer.id = inputContainerId;
      inputContainer.classList.add('time-input-container', 'input-group', 'mb-3');
      inputContainer.innerHTML =
        '<input class="form-control border-success" type="time" step="60" name="time_change_volume[]">' +
        '<input class="form-control border-primary" type="number" name="volumes_volume_time[]" placeholder="Âm lượng (0-100)" min="0" max="100" style="max-width: 200px;">' +
        '<button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-change_volume-' + timeVolumeCounter + '">' +
        '<i class="bi bi-trash"></i>' +
        '</button>';
      container.insertBefore(inputContainer, this);
      document.getElementById('delete-change_volume-' + timeVolumeCounter).addEventListener('click', function() {
        document.getElementById(inputContainerId).remove();
      });
      timeVolumeCounter++;
    });

    //Thay đổi độ sáng LED
    let timeBrightnessCounter = <?= count($change_led_brightness['time']) ?>;
    document.getElementById('add-time-change_led_brightness').addEventListener('click', function() {
      const container = document.getElementById('time-changes_brightness');
      const inputContainerId = 'time-change_led_brightness-' + timeBrightnessCounter;
      const inputContainer = document.createElement('div');
      inputContainer.id = inputContainerId;
      inputContainer.classList.add('time-input-container', 'input-group', 'mb-3');
      inputContainer.innerHTML =
        '<input class="form-control border-success" type="time" step="60" name="time_change_brightness[]">' +
        '<input class="form-control border-primary" type="number" name="brightness_brightnes_time[]" placeholder="Độ sáng từ (0-100)" min="0" max="100" style="max-width: 200px;">' +
        '<button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-change_led_brightness-' + timeBrightnessCounter + '">' +
        '<i class="bi bi-trash"></i>' +
        '</button>';
      container.insertBefore(inputContainer, this);
      document.getElementById('delete-change_led_brightness-' + timeBrightnessCounter).addEventListener('click', function() {
        document.getElementById(inputContainerId).remove();
      });
      timeBrightnessCounter++;
    });
 <!-- Scripts Phát danh sách nhạc -->
    let time_Play_Playlist = <?= count($playlistScheduleEntries) ?>;
    const playlistScheduleContainer = document.getElementById('playlist-schedules-container');
    const playlistScheduleTemplate = playlistScheduleContainer.querySelector('.playlist-schedule-card').cloneNode(true);
    function bindPlaylistScheduleCard(card) {
      card.querySelector('.playlist-schedule-delete').addEventListener('click', function() { card.remove(); });
      const timesContainer = card.querySelector('.playlist-schedule-times');
      function bindPlaylistSlot(row) {
        const playlistSelect = row.querySelector('.scheduler-playlist-select');
        playlistSelect.addEventListener('change', function() { playlistSelect.classList.remove('is-invalid'); });
        row.querySelector('.scheduler-playlist-test').addEventListener('click', function() {
          run_test_task('play_play_playlist', playlistSelect.value);
        });
        row.querySelector('.playlist-time-delete').addEventListener('click', function() {
          const rows = timesContainer.querySelectorAll('.playlist-time-row');
          if (rows.length <= 1) {
            show_message('Mỗi lịch PlayList cần có ít nhất một khung giờ.');
            return;
          }
          row.remove();
        });
      }
      timesContainer.querySelectorAll('.playlist-time-row').forEach(bindPlaylistSlot);
      card.querySelector('.playlist-time-add').addEventListener('click', function() {
        const row = timesContainer.querySelector('.playlist-time-row').cloneNode(true);
        row.querySelector('input[type="time"], input.scheduler-time-24h').value = '';
        row.querySelector('.scheduler-playlist-select').value = '';
        row.querySelector('.scheduler-playlist-select').classList.remove('is-invalid');
        const slotKey = 'slot_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
        row.querySelectorAll('[name]').forEach(element => {
          element.name = element.name.replace(/\[slots\]\[[^\]]+\]/, '[slots][' + slotKey + ']');
        });
        timesContainer.appendChild(row);
        bindPlaylistSlot(row);
      });
      initializeSchedulerRecurrence(card);
    }
    playlistScheduleContainer.querySelectorAll('.playlist-schedule-card').forEach(bindPlaylistScheduleCard);
    document.getElementById('add-time-play_play_playlist').addEventListener('click', function() {
      const card = playlistScheduleTemplate.cloneNode(true);
      card.dataset.playlistScheduleIndex = time_Play_Playlist;
      card.querySelector('b').textContent = 'Lịch PlayList #' + (time_Play_Playlist + 1);
      card.querySelectorAll('[name]').forEach(element => {
        element.name = element.name.replace(/playlist_schedules\[\d+\]/g, 'playlist_schedules[' + time_Play_Playlist + ']');
      });
      card.querySelectorAll('[data-scheduler-index]').forEach(element => element.dataset.schedulerIndex = 'system-playlist_' + time_Play_Playlist);
      card.querySelector('.playlist-schedule-times').id = 'playlist-schedule-' + time_Play_Playlist;
      card.querySelector('.scheduler-recurrence-type').dataset.timeContainerId = 'playlist-schedule-' + time_Play_Playlist;
      card.querySelectorAll('input[type="hidden"]').forEach(element => element.value = 'playlist_' + Date.now() + '_' + time_Play_Playlist);
      const clonedTimeRows = card.querySelectorAll('.playlist-time-row');
      clonedTimeRows.forEach((row, rowIndex) => { if (rowIndex > 0) row.remove(); });
      card.querySelector('.playlist-time-row input[type="time"], .playlist-time-row input.scheduler-time-24h').value = '';
      card.querySelector('.playlist-time-row .scheduler-playlist-select').value = '';
      card.querySelector('.playlist-time-row .scheduler-playlist-select').classList.remove('is-invalid');
      card.querySelector('.scheduler-recurrence-type').value = 'legacy';
      card.querySelector('.scheduler-condition-mode').value = 'always';
      card.querySelectorAll('input[type="date"]').forEach(element => element.value = '');
      card.querySelector('input[name$="[active]"]').checked = true;
      card.querySelector('input[name$="[max_duration_seconds]"]').value = 0;
      card.querySelectorAll('.scheduler-legacy-dates input[type="checkbox"]').forEach(element => element.checked = true);
      playlistScheduleContainer.appendChild(card);
      bindPlaylistScheduleCard(card);
      time_Play_Playlist++;
    });
<!--END Scripts Phát danh sách nhạc -->

  <!-- Scripts Phát toàn bộ nhạc có trong thư mục Local -->
    let time_All_Local = <?= count($play_all_music_local['time']) ?>;
    document.getElementById('add-time-play_all_music_local').addEventListener('click', function() {
      const timeOnContainer = document.getElementById('time-on-play_all_music_local');
      const inputContainerId = 'time_player_local-' + time_All_Local;
      const inputContainer = document.createElement('div');
      inputContainer.id = inputContainerId;
      inputContainer.classList.add('time-input-play_all_music_local', 'input-group', 'mb-3');
      inputContainer.innerHTML = '<input class="form-control border-success" type="time" step="60" name="time_player_local[]"><button class="btn btn-danger border-success" title="Xóa thời gian này" type="button" id="delete-play_all_music_local-' + time_All_Local + '"><i class="bi bi-trash"></i></button>';
      timeOnContainer.insertBefore(inputContainer, this);
      document.getElementById('delete-play_all_music_local-' + time_All_Local).addEventListener('click', function() {
        document.getElementById(inputContainerId).remove();
      });
      time_All_Local++;
    });
    document.querySelectorAll('.time-inputs_player_local > div > button').forEach(button => {
      button.addEventListener('click', function() {
        const container = button.parentElement;
        container.remove();
      });
    });
 <!--END Scripts Phát toàn bộ nhạc có trong thư mục Local -->

//Bật Tắt MIC
let micIndex = <?= count($change_mic_on_off['time']) ?>;
document.getElementById('add-mic-time').addEventListener('click', function () {
    const container = document.getElementById('time-mic_on_off');
    const rowId = 'mic-row-' + micIndex;
    const div = document.createElement('div');
    div.className = 'input-group mb-2 time-row';
    div.id = rowId;
	div.innerHTML =
		'<input type="time" step="60" class="form-control border-success" name="mic_on_off_time[]">' +
		'<select class="form-select border-success" name="mic_on_off_action[]">' +
			'<option value="on">Bật</option>' +
			'<option value="off">Tắt</option>' +
		'</select>' +
		'<button type="button" class="btn btn-danger" onclick="removeMicRow(\'' + rowId + '\')">' +
			'<i class="bi bi-trash"></i>' +
		'</button>';
    container.appendChild(div);
    micIndex++;
});
function removeMicRow(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

//Chạy Test tác vụ
function run_test_task(value, parameter = null) {
	loading('show');
    const data = {
        type: 3,
        data: "scheduler",
        value: value
    };
    if (parameter !== null && parameter !== undefined) {
        data.parameter = parameter;
    }
    const xhr = vbotCreateXhr();
    xhr.withCredentials = true;
    xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			try {
				loading('hide');
				const res = JSON.parse(xhr.responseText);
				if (res.success === true) {
					showMessagePHP(res.message || "Thao tác thành công");
				} else {
					show_message(res.message || "Thao tác thất bại");
				}
			} catch (e) {
				loading('hide');
				show_message('Lỗi phản hồi từ Server: '+xhr.responseText);
			}
		}
    };
    xhr.open("POST", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>");
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.send(JSON.stringify(data));
}

async function schedulerApi(value, parameter = null) {
    const payload = {type: 3, data: 'scheduler', value: value};
    if (parameter !== null) payload.parameter = parameter;
    const response = await vbotFetchWithTimeout("<?php echo $Protocol.$serverIp.':'.$Port_API; ?>", {
        method: 'POST', credentials: 'include', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || ('HTTP ' + response.status));
    return result;
}

async function schedulerHistoryFromFile(limit = 50) {
    const response = await vbotFetchWithTimeout(
        'Scheduler.php?scheduler_history_fallback=1&limit=' + encodeURIComponent(limit),
        {method: 'GET', credentials: 'include', cache: 'no-store'}
    );
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || ('HTTP ' + response.status));
    return result;
}

function updateSchedulerRecurrenceFields(select) {
    const scope = select.closest('.playlist-schedule-card') || select.closest('.alert') || select.closest('.card') || document;
    const selectedType = select.value;
    scope.querySelectorAll('.scheduler-recurrence-fields').forEach(group => {
        const visible = (group.dataset.recurrenceFor || '').split(/\s+/).includes(selectedType);
        group.hidden = !visible;
        group.querySelectorAll('input, select').forEach(input => input.disabled = !visible);
    });
    const index = select.dataset.schedulerIndex;
    const legacyGroup = scope.querySelector('.scheduler-legacy-dates[data-scheduler-index="' + index + '"]');
    if (legacyGroup) {
        const visible = selectedType === 'legacy';
        legacyGroup.hidden = !visible;
        legacyGroup.querySelectorAll('input, select, button').forEach(input => input.disabled = !visible);
    }
    let addTimeButton = scope.querySelector('.scheduler-add-time[data-scheduler-index="' + index + '"]');
    const externalTimeContainerId = select.dataset.timeContainerId;
    const externalTimeContainer = externalTimeContainerId ? document.getElementById(externalTimeContainerId) : null;
    if (!addTimeButton && externalTimeContainer && externalTimeContainer.parentElement) {
        addTimeButton = Array.from(externalTimeContainer.parentElement.querySelectorAll('button')).find(button =>
            !externalTimeContainer.contains(button) && String(button.getAttribute('onclick') || '').includes('add')
        );
    }
    const singleTimeOnly = selectedType === 'once' && !scope.classList.contains('playlist-schedule-card');
    if (addTimeButton) {
        addTimeButton.disabled = singleTimeOnly;
        addTimeButton.title = singleTimeOnly ? 'Chế độ Chỉ một lần chỉ sử dụng một thời gian' : 'Thêm thời gian';
    }
    const timeContainer = externalTimeContainer || scope.querySelector('#time-container-' + index);
    if (timeContainer) {
        const timeInputs = Array.from(timeContainer.querySelectorAll('input[type="time"], input.time-input, input.scheduler-time-24h'));
        timeInputs.forEach((input, inputIndex) => {
            const disabledByOnce = singleTimeOnly && inputIndex > 0;
            input.disabled = disabledByOnce;
            const row = input.closest('.input-group');
            if (row) {
                row.classList.toggle('opacity-50', disabledByOnce);
                row.querySelectorAll('button').forEach(button => button.disabled = disabledByOnce);
            }
        });
    }
}

function updateSchedulerConditionFields(select) {
    const scope = select.closest('.playlist-schedule-card') || select.closest('.alert') || select.closest('.card') || document;
    const index = select.dataset.schedulerIndex;
    const fields = scope.querySelector('.scheduler-condition-fields[data-scheduler-index="' + index + '"]');
    if (!fields) return;
    const enabled = select.value === 'conditional';
    fields.classList.toggle('opacity-50', !enabled);
    fields.querySelectorAll('input, select').forEach(input => input.disabled = !enabled);
}

const systemTaskSchedulesContainer = document.getElementById('system-task-schedules-container');
const systemTaskScheduleTemplate = document.getElementById('system-task-schedule-template');
let systemTaskScheduleIndex = <?= count($independentSystemSchedules) ?>;

function updateSystemTaskParameter(card) {
    const task = card.querySelector('.system-task-kind').value;
    const wrap = card.querySelector('.system-task-parameter-wrap');
    const oldField = wrap.querySelector('.system-task-parameter');
    const name = oldField ? oldField.name : '';
    const oldValue = oldField ? oldField.value : '';
    if (task.startsWith('vbot_action:')) {
        const action = task.substring('vbot_action:'.length) || 'none';
        wrap.innerHTML = '<input type="hidden" class="system-task-parameter" name="' + name + '" value=""><div class="form-control bg-light text-muted">Không cần tham số</div>';
        wrap.querySelector('.system-task-parameter').value = action;
    } else if (task === 'mic_on_off') {
        wrap.innerHTML = '<select class="form-select border-warning system-task-parameter" name="' + name + '"><option value="on">Bật Mic</option><option value="off">Tắt Mic</option></select>';
        wrap.querySelector('select').value = (oldValue === 'on' || oldValue === 'off') ? oldValue : 'off';
    } else if (task === 'change_volume' || task === 'change_led_brightness') {
        const label = task === 'change_volume' ? 'Âm lượng (0-100)' : 'Độ sáng (0-100)';
        wrap.innerHTML = '<input type="number" min="0" max="100" class="form-control border-warning system-task-parameter" name="' + name + '" placeholder="' + label + '" value="' + (oldValue || '50') + '">';
    } else {
        wrap.innerHTML = '<input type="hidden" class="system-task-parameter" name="' + name + '" value=""><div class="form-control bg-light text-muted">Không cần tham số</div>';
    }
}

function markSystemTaskScheduleChanged(task) {
    if (!task) return;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'removed_system_schedule_tasks[]';
    input.value = task.startsWith('vbot_action:') ? 'vbot_action' : task;
    document.getElementById('system-task-schedule-changes').appendChild(input);
}

function bindSystemTaskScheduleCard(card) {
    const taskSelect = card.querySelector('.system-task-kind');
    const sourceTaskField = card.querySelector('input[name$="[source_task]"]');
    const sourceTask = sourceTaskField ? sourceTaskField.value : '';
    card.dataset.originalTask = taskSelect.value;
    card.querySelector('.system-task-schedule-delete').addEventListener('click', function () {
        markSystemTaskScheduleChanged(card.dataset.originalTask || taskSelect.value);
        if (sourceTask && sourceTask !== card.dataset.originalTask) markSystemTaskScheduleChanged(sourceTask);
        card.remove();
        document.getElementById('system-task-schedules-empty').classList.toggle('d-none', systemTaskSchedulesContainer.children.length > 0);
    });
    taskSelect.addEventListener('change', function () {
        markSystemTaskScheduleChanged(card.dataset.originalTask);
        if (sourceTask && sourceTask !== card.dataset.originalTask) markSystemTaskScheduleChanged(sourceTask);
        card.dataset.originalTask = taskSelect.value;
        updateSystemTaskParameter(card);
    });
    updateSystemTaskParameter(card);
    initializeSchedulerRecurrence(card);
}

systemTaskSchedulesContainer.querySelectorAll('.system-task-schedule-card').forEach(bindSystemTaskScheduleCard);
function createSystemTaskSchedule(selectedTask = 'change_volume') {
    const stamp = Date.now();
    const html = systemTaskScheduleTemplate.innerHTML
        .replaceAll('__INDEX__', String(systemTaskScheduleIndex))
        .replaceAll('__STAMP__', String(stamp));
    const holder = document.createElement('div');
    holder.innerHTML = html.trim();
    const card = holder.firstElementChild;
    systemTaskSchedulesContainer.appendChild(card);
    card.querySelector('.system-task-kind').value = selectedTask;
    bindSystemTaskScheduleCard(card);
    document.getElementById('system-task-schedules-empty').classList.add('d-none');
    card.scrollIntoView({behavior: 'smooth', block: 'center'});
    systemTaskScheduleIndex++;
}
document.getElementById('add-system-task-schedule').addEventListener('click', function () { createSystemTaskSchedule(); });

// Đặt nút tạo lịch ngay trong từng mục tác vụ để không phải quay lại đầu trang.
const systemTaskAccordionMap = {
    accordion_button_volume_change: 'change_volume',
    accordion_button_brightness_change: 'change_led_brightness',
    accordion_button_mic_on_off: 'mic_on_off',
    accordion_button_play_all_local: 'play_all_music_local',
    accordion_button_stop_media_player: 'stop_media_player',
    accordion_button_restart_vbot_service: 'restart_vbot',
    accordion_button_reboot_os: 'reboot_os',
    accordion_button_send_notify_upgrade_vbot_hass: 'send_notify_upgrade_vbot_home_assistant'
};
Object.entries(systemTaskAccordionMap).forEach(([accordionId, task]) => {
    const accordion = document.getElementById(accordionId);
    const target = accordion ? accordion.querySelector('.alert.alert-success') : null;
    if (!target) return;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-primary btn-sm mb-3';
    button.innerHTML = '<i class="bi bi-calendar2-plus"></i> Tạo lịch độc lập mới cho tác vụ này';
    button.addEventListener('click', function () { createSystemTaskSchedule(task); });
    target.prepend(button);
});

function initializeSchedulerRecurrence(root = document) {
    root.querySelectorAll('.scheduler-recurrence-type').forEach(updateSchedulerRecurrenceFields);
    root.querySelectorAll('.scheduler-condition-mode').forEach(updateSchedulerConditionFields);
}

document.addEventListener('change', event => {
    if (event.target.classList.contains('scheduler-recurrence-type')) updateSchedulerRecurrenceFields(event.target);
    if (event.target.classList.contains('scheduler-condition-mode')) updateSchedulerConditionFields(event.target);
});

document.addEventListener('submit', () => {
    //Giữ lại các lựa chọn điều kiện đang bị khóa khi chế độ là Luôn thực thi.
    document.querySelectorAll('.scheduler-condition-fields input, .scheduler-condition-fields select').forEach(input => input.disabled = false);
});

async function stopCurrentSchedulerTask() {
    try { const result = await schedulerApi('stop_current'); showMessagePHP(result.message); }
    catch (error) { show_message(error.message); }
}

function renderSchedulerRows(elementId, rows, formatter) {
    const container = document.getElementById(elementId);
    container.replaceChildren();
    if (!Array.isArray(rows) || rows.length === 0) { container.textContent = 'Chưa có dữ liệu'; return; }
    rows.forEach(row => { const line = document.createElement('div'); line.className = 'border-bottom py-1'; line.textContent = formatter(row); container.appendChild(line); });
}

function formatSchedulerDateTime(value) {
    const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
    return match ? (match[4] + ':' + match[5] + ' - ' + match[3] + '/' + match[2] + '/' + match[1]) : String(value || '');
}

async function loadSchedulerOverview() {
    const nextRunsContainer = document.getElementById('scheduler-next-runs');
    const historyContainer = document.getElementById('scheduler-history');
    try {
        const nextRuns = await schedulerApi('next_runs', 10);
        renderSchedulerRows('scheduler-next-runs', nextRuns.data, row => formatSchedulerDateTime(row.next_run) + ' — ' + row.name);
    } catch (error) {
        nextRunsContainer.textContent = 'Không thể lấy dữ liệu vì VBot/API đang không hoạt động: ' + error.message;
    }
    try {
        const history = await schedulerApi('history', 50);
        renderSchedulerRows('scheduler-history', [...history.data].reverse(), row => {
            const executionLabel = row.execution_mode === 'manual' ? '[Được chạy kiểm tra thủ công]' : '[Được chạy tự động]';
            return formatSchedulerDateTime(row.finished_at) + ' — ' + row.task + ' ' + executionLabel + ' [' + row.status + ']' + (row.message ? ': ' + row.message : '');
        });
    } catch (apiError) {
        try {
            const history = await schedulerHistoryFromFile(50);
            renderSchedulerRows('scheduler-history', [...history.data].reverse(), row => {
                const executionLabel = row.execution_mode === 'manual' ? '[Được chạy kiểm tra thủ công]' : '[Được chạy tự động]';
                return formatSchedulerDateTime(row.finished_at) + ' — ' + row.task + ' ' + executionLabel + ' [' + row.status + ']' + (row.message ? ': ' + row.message : '');
            });
            historyContainer.insertAdjacentHTML('afterbegin', '<div class="text-danger border-bottom py-1">VBot/API không hoạt động — đang đọc trực tiếp Scheduler_History.json</div>');
        } catch (fileError) {
            historyContainer.textContent = 'Không thể đọc lịch sử từ API hoặc file: ' + fileError.message;
        }
    }
}

async function clearSchedulerHistory() {
    try { await schedulerApi('clear_history'); await loadSchedulerOverview(); }
    catch (error) { show_message(error.message); }
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSchedulerRecurrence();
    loadSchedulerOverview();
});

//Kiểm tra và thông báo lỗi nếu Submit có giá trị input trống
function validateFormVBot() {
    initializeSchedulerTime24();
    let firstInvalidTime = null;
    document.querySelectorAll('input.scheduler-time-24h').forEach(input => {
        if (!input.disabled && !validateSchedulerTime24(input, true) && !firstInvalidTime) firstInvalidTime = input;
    });
    if (firstInvalidTime) {
        show_message('Thời gian không hợp lệ. Vui lòng nhập theo định dạng 24 giờ HH:mm, từ 00:00 đến 23:59.');
        firstInvalidTime.scrollIntoView({behavior: 'smooth', block: 'center'});
        setTimeout(() => firstInvalidTime.focus(), 350);
        return false;
    }
    const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    let firstEmptyInput = null;
    let emptyFields = [];
    requiredInputs.forEach(input => {
        if (input.disabled) return;
        input.classList.remove('empty-field');
        if (!input.value.trim()) {
            input.classList.add('empty-field');
            if (!firstEmptyInput) {
                firstEmptyInput = input;
            }
            const accordionContent = input.closest('.accordion-collapse');
            if (accordionContent) {
                const accordionId = accordionContent.id;
                const accordion = new bootstrap.Collapse(accordionContent, {
                    show: true
                });
                const accordionHeader = document.querySelector('[data-bs-target="#' + accordionId + '"]');
                const sectionName = accordionHeader ? accordionHeader.textContent.trim() : '';
                let fieldName = input.getAttribute('placeholder') || input.getAttribute('name') || input.getAttribute('id') || 'Trường dữ liệu';
                if (sectionName) {
                    fieldName = '<b class="text-success">'+sectionName+'</b> <b class="text-primary">'+fieldName+'</b>';
                }
                emptyFields.push(fieldName);
            } else {
                let fieldName = input.getAttribute('placeholder') || 
                              input.getAttribute('name') ||
                              input.getAttribute('id') ||
                              'Trường dữ liệu';
                const cardHeader = input.closest('.card')?.querySelector('.card-header');
                if (cardHeader) {
                    fieldName = '<b class="text-success">'+cardHeader.textContent.trim()+' </b> <b class="text-primary">'+fieldName+'</b>';
                }
                emptyFields.push(fieldName);
            }
        }
    });
    if (firstEmptyInput) {
        const message = '<br/><center class="text-danger"><b>Vui lòng điền đầy đủ thông tin cho các trường giá trị</b></center><hr><b><center>Các Danh Mục Sau Còn Thiếu Tham Số</center></b><br/>- ' + emptyFields.join('<br>- ');
        show_message(message);
        const accordionContent = firstEmptyInput.closest('.accordion-collapse');
        if (accordionContent) {
            new bootstrap.Collapse(accordionContent, {
                show: true
            });
            setTimeout(() => {
                firstEmptyInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstEmptyInput.focus();
            }, 350);
        } else {
            firstEmptyInput.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            setTimeout(() => firstEmptyInput.focus(), 500);
        }
        return false;
    }
    return true;
}
  </script>

  <script>
    function normalizeSchedulerSearchText(value) {
      return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('vi')
        .trim();
    }

    function initializeSchedulerTaskNavigation() {
      const searchInput = document.getElementById('scheduler-task-search');
      const clearButton = document.getElementById('scheduler-search-clear');
      const navigation = document.getElementById('scheduler-quick-navigation');
      const emptyState = document.getElementById('scheduler-search-empty');
      if (!searchInput || !clearButton || !navigation || !emptyState) {
        return;
      }

      const getAccordions = function() {
        return Array.from(document.querySelectorAll('.card.accordion[id]'));
      };

      const getAccordionLabel = function(accordion, index) {
        const button = accordion.querySelector('.accordion-button');
        const label = button ? button.textContent.replace(/\s+/g, ' ').trim() : '';
        return label || 'Tác vụ ' + (index + 1);
      };

      const rebuildNavigation = function() {
        const currentValue = navigation.value;
        navigation.replaceChildren(new Option('Đi tới tác vụ...', ''));
        getAccordions().forEach(function(accordion, index) {
          navigation.appendChild(new Option(getAccordionLabel(accordion, index), accordion.id));
        });
        if (currentValue && document.getElementById(currentValue)) {
          navigation.value = currentValue;
        }
      };

      const applySearch = function() {
        const query = normalizeSchedulerSearchText(searchInput.value);
        let visibleCount = 0;
        getAccordions().forEach(function(accordion) {
          const searchableText = normalizeSchedulerSearchText(accordion.textContent);
          const visible = query === '' || searchableText.includes(query);
          accordion.classList.toggle('scheduler-search-hidden', !visible);
          if (visible) {
            visibleCount += 1;
          }
        });
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
      };

      const showAccordion = function(accordion) {
        const collapseElement = accordion.querySelector('.accordion-collapse');
        if (!collapseElement) {
          return;
        }
        const BootstrapCollapse = window.bootstrap && window.bootstrap.Collapse;
        if (BootstrapCollapse) {
          let collapseInstance = null;
          if (typeof BootstrapCollapse.getOrCreateInstance === 'function') {
            collapseInstance = BootstrapCollapse.getOrCreateInstance(collapseElement, {toggle: false});
          } else if (typeof BootstrapCollapse.getInstance === 'function') {
            collapseInstance = BootstrapCollapse.getInstance(collapseElement);
          }
          if (!collapseInstance) {
            collapseInstance = new BootstrapCollapse(collapseElement, {toggle: false});
          }
          collapseInstance.show();
          return;
        }
        collapseElement.classList.add('show');
        const toggle = accordion.querySelector('.accordion-button');
        if (toggle) {
          toggle.classList.remove('collapsed');
          toggle.setAttribute('aria-expanded', 'true');
        }
      };

      searchInput.addEventListener('input', applySearch);
      clearButton.addEventListener('click', function() {
        searchInput.value = '';
        applySearch();
        searchInput.focus();
      });
      navigation.addEventListener('change', function() {
        const target = document.getElementById(navigation.value);
        if (target) {
          searchInput.value = '';
          applySearch();
          target.scrollIntoView({behavior: 'smooth', block: 'center'});
          window.setTimeout(function() {
            showAccordion(target);
            const toggle = target.querySelector('.accordion-button');
            if (toggle) {
              toggle.focus({preventScroll: true});
            }
          }, 250);
        }
        navigation.value = '';
      });

      rebuildNavigation();
      applySearch();

      const taskContainer = document.getElementById('task-container');
      if (taskContainer) {
        let rebuildTimer = null;
        new MutationObserver(function() {
          window.clearTimeout(rebuildTimer);
          rebuildTimer = window.setTimeout(function() {
            rebuildNavigation();
            applySearch();
          }, 100);
        }).observe(taskContainer, {childList: true});
      }
    }

    document.addEventListener('DOMContentLoaded', initializeSchedulerTaskNavigation);
  </script>


  <!--END Scripts REBOOT OS SYSTEM -->
  <script src="assets/vendor/prism/prism.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <script src="assets/vendor/prism/prism-json.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <?php
  include 'html_js.php';
  ?>
</body>

</html>
