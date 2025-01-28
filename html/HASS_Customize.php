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
      <h1>Lệnh Tùy Chỉnh Home Assistant <i class="bi bi-question-circle-fill" onclick="show_message('- Hỗ trợ code YAML, Có trong: <b>Công cụ nhà phát triển -> Hành Động</b><br/>- Công cụ phát triển hành động cho phép bạn thực hiện bất kỳ hành động nào có trong Home Assistant.')"></i></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">Home Assistant Custom Command</li> 
		   &nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['home_assistant']['custom_commands']['active'] ? '<p class="text-success" title="Home Assistant Custom Command đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Home Assistant Custom Command không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
        </ol>
      </nav>
    </div><!-- End Page Title -->
<form class="row g-3 needs-validation" novalidate method="POST" enctype="multipart/form-data" action="">
<?php
// Đọc dữ liệu JSON
$jsonFilePath = $VBot_Offline . $Config['home_assistant']['custom_commands']['custom_command_file'];

// Mảng lưu thông báo lỗi
$errorMessages = [];
$successMessage = [];

//**Dùng dấu nháy đôi (") cho các giá trị chứa ký tự đặc biệt hoặc không phải chữ/số (vd: dấu cách, ký tự đặc biệt khác).
/*
function arrayToYaml($array, $indent = 0) {
    $yaml = '';
    $indentation = str_repeat(' ', $indent);
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (empty($value)) {
                $yaml .= $indentation . $key . ": {}\n"; // Mảng rỗng
            } else {
                $yaml .= $indentation . $key . ":\n" . arrayToYaml($value, $indent + 2);
            }
        } else {
            // Xử lý giá trị "{}" mà không cần dấu nháy
            if ($value === "{}") {
                $yaml .= $indentation . $key . ": {}\n";
            } elseif (is_numeric($value) || preg_match('/^[a-zA-Z0-9._-]+$/', $value)) {
                // Giá trị đơn giản không cần dấu nháy
                $yaml .= $indentation . $key . ": " . $value . "\n";
            } else {
                //Các giá trị còn lại sử dụng dấu nháy đôi
                $yaml .= $indentation . $key . ": \"" . $value . "\"\n";
            }
        }
    }
    return $yaml;
}
*/
/*

//Hàm tự chuyển YAML (giả lập) thành mảng
function yamlToArray($yaml) {
    $lines = explode("\n", trim($yaml));
    $result = [];
	//Mảng để giữ key cha
    $path = [];
    foreach ($lines as $line) {
		//Bỏ qua dòng trống
        if (trim($line) === "") continue;
        preg_match('/^(\s*)([^:]+):(.*)$/', $line, $matches);
        if (!$matches) continue;
		// Xác định mức độ lùi đầu dòng
        $indent = strlen($matches[1]) / 2;
        $key = trim($matches[2]);
        $value = trim($matches[3]);
        // Loại bỏ dấu ngoặc kép của value
        $value = trim($value, "\"");
        while (count($path) > $indent) {
			// Thoát ra nếu lùi indent
            array_pop($path);
        }
        $parent = &$result;
        foreach ($path as $segment) {
            $parent = &$parent[$segment];
        }
        if ($value === "") {
            $parent[$key] = [];
            $path[] = $key;
        } else {
            $parent[$key] = is_numeric($value) ? (float)$value : $value;
        }
    }
    return $result;
}
*/
#Chuyển đổi Json Sang YAML không dùng thư viện
function arrayToYaml($array, $indent = 0) {
    $yaml = '';
    $indentation = str_repeat(' ', $indent);
    foreach ($array as $key => $value) {
        // Xử lý mảng
        if (is_array($value)) {
            if (empty($value)) {
				// Mảng rỗng
                $yaml .= $indentation . $key . ": {}\n";
            } elseif (isset($value[0]) && !is_array($value[0])) {
                // Nếu là mảng đơn giản, sử dụng dấu "-"
                $yaml .= $indentation . $key . ":\n";
                foreach ($value as $subValue) {
                    $yaml .= $indentation . "  - " . $subValue . "\n";
                }
            } else {
                // Mảng khác (mảng phức tạp)
                $yaml .= $indentation . $key . ":\n" . arrayToYaml($value, $indent + 2);
            }
        } else {
            // Xử lý giá trị đơn
            if ($value === "{}") {
                $yaml .= $indentation . $key . ": {}\n";  // Xử lý rỗng
            } elseif ($value === "" && $key === "entity_id") {
                // Nếu là `entity_id` và có giá trị là rỗng, ghi "{}"
                $yaml .= $indentation . $key . ": {}\n";
            } elseif (is_numeric($value) || preg_match('/^[a-zA-Z0-9._-]+$/', $value)) {
                // Các giá trị đơn giản không cần dấu nháy
                $yaml .= $indentation . $key . ": " . $value . "\n";
            } else {
                // Các giá trị có dấu nháy đôi
                $yaml .= $indentation . $key . ": \"" . $value . "\"\n";
            }
        }
    }
    return $yaml;
}

#Chuyển đổi Yaml Sang Json không dùng thư viện
function yamlToArray($yaml) {
    $lines = explode("\n", trim($yaml));
    $result = [];
    $path = [];
    $lastKey = null;
    foreach ($lines as $line) {
        if (trim($line) === "") continue;
        if (preg_match('/^(\s*)-(.*)$/', $line, $matches)) {
            $indent = strlen($matches[1]) / 2;
            $value = trim($matches[2]);
            $parent = &$result;
            foreach ($path as $segment) {
                $parent = &$parent[$segment];
            }
            if ($lastKey !== null && isset($parent[$lastKey]) && is_array($parent[$lastKey])) {
                $parent[$lastKey][] = $value;
            } else {
                $parent[$lastKey] = [$value];
            }
        } else {
            preg_match('/^(\s*)([^:]+):(.*)$/', $line, $matches);
            if (!$matches) continue;
            $indent = strlen($matches[1]) / 2;
            $key = trim($matches[2]);
            $value = trim($matches[3]);
            $value = trim($value, "\"");
            while (count($path) > $indent) {
                array_pop($path);
            }
            $parent = &$result;
            foreach ($path as $segment) {
                $parent = &$parent[$segment];
            }
            if ($key == 'entity_id') {
                if (strpos($value, "-") === 0) {
                    $parent[$key] = explode("\n", $value);
                } else {
                    $parent[$key] = $value;
                }
            } else {
                if ($value === "") {
                    $parent[$key] = [];
                    $path[] = $key;
                } else {
                    $parent[$key] = is_numeric($value) ? (float)$value : $value;
                }
            }
            $lastKey = $key;
        }
    }
    return $result;
}






// Xử lý dữ liệu khi form được gửi
if (isset($_POST['save_custom_home_assistant'])) {

#Sao Lưu Dữ Liệu Trước
if (isset($Config['backup_upgrade']['custom_home_assistant']['active']) && $Config['backup_upgrade']['custom_home_assistant']['active'] === true) {
// Đường dẫn gốc và đích
$sourceFile = $VBot_Offline . $Config['home_assistant']['custom_commands']['custom_command_file'];
$destinationDir = $directory_path . '/' . $Config['backup_upgrade']['custom_home_assistant']['backup_path'];
$destinationFile = $destinationDir . "/Home_Assistant_Custom_" . date('dmY_His') . ".json";
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
    if (count($jsonFiles) > $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']) {
        foreach (array_slice($jsonFiles, 0, count($jsonFiles) - $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']) as $oldFile) {
            unlink($oldFile);
			$successMessage[] = "Vượt quá số lượng tệp tin Backup là <b>".$Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']."</b>, đã xóa tệp: <b>".basename($oldFile)."</b>";
        }
    }
} else {
    $errorMessages[] = "- Xảy ra Lỗi, Không thể sao lưu tệp: <b>$sourceFile</b>";
}
}
    // Nhận danh sách phần tử bị xóa (dưới dạng mảng ID phần tử)
    $deletedItems = json_decode($_POST['deleted_items'] ?? '[]', true);
    // Duyệt qua dữ liệu mới nhận từ form và xử lý
    $intents = $_POST['intents'] ?? [];
    // Loại bỏ các intent đã bị xóa
    foreach ($deletedItems as $deletedId) {
        // Tạo ID từ name để kiểm tra
        $deletedIndex = str_replace('accordion_button_custom_hass_', '', $deletedId) - 1;
        // Nếu tìm thấy phần tử, loại bỏ nó khỏi mảng intents
        if (isset($intents[$deletedIndex])) {
            unset($intents[$deletedIndex]);
        }
    }
    // Sắp xếp lại chỉ số mảng để giữ tính liên tục sau khi xóa
    $intents = array_values($intents);
    // Chuyển đổi YAML thành mảng và xử lý các dữ liệu khác (YAML, questions, active, v.v.)
    foreach ($intents as $index => $intent) {
        $intents[$index]['data_yaml'] = yamlToArray(trim($intent['data_yaml']));
        $intents[$index]['questions'] = array_filter(array_map('trim', explode("\n", $intent['questions'] ?? '')));
        // Cập nhật các trường khác
        $intents[$index]['name'] = $intent['name'];
        $intents[$index]['reply'] = $intent['reply'] ?? '';
        $intents[$index]['active'] = isset($intent['active']) && $intent['active'] === 'on';
    }
    // Cập nhật dữ liệu sau khi xóa phần tử
    $updatedData = ['intents' => $intents];
    // Ghi lại dữ liệu vào tệp JSON
    if (file_put_contents($jsonFilePath, json_encode($updatedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
        $successMessage[] = "Dữ liệu đã được lưu thành công!";
    } else {
        $errorMessages[] = "Không thể lưu dữ liệu. Vui lòng kiểm tra quyền truy cập tệp tin.";
    }
}

if (isset($_POST['delete_all_custom_home_assistant'])) {

#Sao Lưu Dữ Liệu Trước
if (isset($Config['backup_upgrade']['custom_home_assistant']['active']) && $Config['backup_upgrade']['custom_home_assistant']['active'] === true) {
// Đường dẫn gốc và đích
$sourceFile = $VBot_Offline . $Config['home_assistant']['custom_commands']['custom_command_file'];
$destinationDir = $directory_path . '/' . $Config['backup_upgrade']['custom_home_assistant']['backup_path'];
$destinationFile = $destinationDir . "/Home_Assistant_Custom_" . date('dmY_His') . ".json";
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
    if (count($jsonFiles) > $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']) {
        foreach (array_slice($jsonFiles, 0, count($jsonFiles) - $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']) as $oldFile) {
            unlink($oldFile);
			$successMessage[] = "Vượt quá số lượng tệp tin Backup là <b>".$Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']."</b>, đã xóa tệp: <b>".basename($oldFile)."</b>";
        }
    }
} else {
    $errorMessages[] = "- Xảy ra Lỗi, Không thể sao lưu tệp: <b>$sourceFile</b>";
}
}

// Đường dẫn thư mục
$directory = $VBot_Offline.'resource/hass';

// Kiểm tra thư mục có tồn tại không
if (is_dir($directory)) {
    // Lấy danh sách tất cả các file JSON trong thư mục
    $files = glob($directory . '/*.json');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    $content = json_encode([
        "intents" => []
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	
    file_put_contents($jsonFilePath, $content);
	chmod($jsonFilePath, 0777);
    $successMessage[] = "Toàn bộ dữ liệu cấu hình đã được xóa thành công";
} else {
    $errorMessages[] = "Thư mục không tồn tại: " . $directory;
}


}


//Khôi Phục Dữ liệu bằng tải lên hoặc tệp hệ thống
if (isset($_POST['start_recovery_custom_homeassistant'])) {
$data_recovery_type = $_POST['start_recovery_custom_homeassistant'];
if ($data_recovery_type === "khoi_phuc_tu_tep_tai_len"){
    $uploadOk = 1;
    // Kiểm tra xem tệp có được gửi không
    if (isset($_FILES["fileToUpload_custom_hass_restore"])) {
        //$targetFile = $jsonFilePath;
        $fileName = basename($_FILES["fileToUpload_custom_hass_restore"]["name"]);
        // Kiểm tra xem tệp có phải là .json không
		//if (!preg_match('/\.json$/', $fileName) || !preg_match('/^Home_Assistant_Custom/', $fileName)) {
		if (!preg_match('/\.json$/', $fileName)) {
		$errorMessages[] = "- Chỉ chấp nhận tệp .json, dành cho Home_Assistant_Custom.json";
		$uploadOk = 0;
		}
        // Kiểm tra xem $uploadOk có bằng 0 không
        if ($uploadOk == 0) {
            $errorMessages[] = "- Tệp sao lưu không được tải lên";
        } else {
            // Di chuyển tệp vào thư mục đích
            if (move_uploaded_file($_FILES["fileToUpload_custom_hass_restore"]["tmp_name"], $jsonFilePath)) {
                $successMessage[] = "- Tệp " . htmlspecialchars($fileName) . " đã được tải lên và khôi phục dữ liệu Custom Home Assistant thành công";
            } else {
                $errorMessages[] = "- Có lỗi xảy ra khi tải lên tệp sao lưu của bạn";
            }
        }
    } else {
        $errorMessages[] = "- Không có tệp sao lưu nào được tải lên";
    }
}else if ($data_recovery_type === "khoi_phuc_file_he_thong"){
	$start_recovery_custom_hass = $_POST['backup_custom_hass_json_files'];
if (!empty($start_recovery_custom_hass)) {
if (file_exists($start_recovery_custom_hass)) {
    $command = 'cp ' . escapeshellarg($start_recovery_custom_hass) . ' ' . escapeshellarg($jsonFilePath);
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
}

if (file_exists($jsonFilePath)) {
    $jsonData = file_get_contents($jsonFilePath);
    $data = json_decode($jsonData, true);
// Kiểm tra nếu dữ liệu JSON không hợp lệ hoặc thiếu key "intents"
if (!is_array($data) || !isset($data['intents'])) {
	chmod($jsonFilePath, 0777);
    $data = ['intents' => []];
    file_put_contents($jsonFilePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

$intents = $data['intents'];

if (empty($intents)) {
	 echo '<center><h5 class="card-title"><font color="red">Chưa có tác vụ nào được thiết lập cho Custom Home Assistant:</font></h5></center>';
}
else {

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
<h5 class="card-title"><font color="green">Thiết Lập Lệnh Tùy Chỉnh Home Assistant:</font> <a href="https://github.com/user-attachments/assets/eb92a617-12f6-40c9-9d00-35cdbe5cd0bb" target="_bank">(Ảnh Hướng Dẫn, Demo)</a></h5>

<?php foreach ($intents as $index => $intent): ?>

<div class="card accordion" id="accordion_button_custom_hass_<?= $index + 1 ?>">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_custom_hass_<?= $index + 1 ?>" aria-expanded="false" aria-controls="collapse_button_custom_hass_<?= $index + 1 ?>">
<font color="Fuchsia"><?= htmlspecialchars($intent['name']) ?>, &nbsp;</font> Trạng Thái: &nbsp;<?= !empty($intent['active']) ? ' <font color=green>Bật</font>' : ' <font color=red>Tắt</font>' ?></h5>
<div id="collapse_button_custom_hass_<?= $index + 1 ?>" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_custom_hass_<?= $index + 1 ?>">

<!-- Active trạng thái -->
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt hành động này')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="intents[<?= $index ?>][active]" id="intents[<?= $index ?>][active]" <?= !empty($intent['active']) ? 'checked' : '' ?>>
</div>
</div>
</div>

<!-- Tên Hành ĐỘng -->
<div class="row mb-3">
<label for="intents[<?= $index ?>][name]" class="col-sm-3 col-form-label" title="Đặt Tên Định Danh Cho Hành Động Này">Tên Tác Vụ <i class="bi bi-question-circle-fill" onclick="show_message('Tên Định Danh Để Phân Biệt Với Các Hành Động, Thao Tác Khác')"></i> : </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input required class="form-control border-success" type="text" name="intents[<?= $index ?>][name]" id="intents[<?= $index ?>][name]" title="Đặt Tên Định Danh Cho Hành Động Này" placeholder="<?= htmlspecialchars($intent['name']) ?>" value="<?= htmlspecialchars($intent['name']) ?>">
<div class="invalid-feedback">Cần đặt tên cho hành động này</div>
</div>
</div>
</div>

<!-- Tùy chỉnh câu phản hồi -->
<div class="row mb-3">
<label for="intents[<?= $index ?>][reply]" class="col-sm-3 col-form-label" title="Tùy Chỉnh Câu Phản Hồi Lại Khi Thực Hiện Thao Tác Này">Tùy Chỉnh Câu Phản Hồi <i class="bi bi-question-circle-fill" onclick="show_message('Tùy Chỉnh Câu Phản Hồi Lại Khi Thực Hiện Thao Tác Này, Không Muốn Phản Hồi Lại Theo Ý Của Bạn Thì Để Trống')"></i> : </label>
<div class="col-sm-9">
<input class="form-control border-success" type="text" name="intents[<?= $index ?>][reply]" id="intents[<?= $index ?>][reply]" title="Tùy Chỉnh Câu Phản Hồi Lại Khi Thực Hiện Thao Tác Này" placeholder="<?= htmlspecialchars($intent['reply'] ?? '') ?>" value="<?= htmlspecialchars($intent['reply'] ?? '') ?>">
</div>
</div>

<!-- data_yaml: chuyển đổi mảng thành định dạng YAML -->
<div class="row mb-3">
<label for="intents[<?= $index ?>][data_yaml]" class="col-sm-3 col-form-label">Code YAML <i class="bi bi-question-circle-fill" onclick="show_message('Nội Dung Code YAML Cần Thực Hiện<br/>Nội dung Code YAML này được lấy ở Trong Home Assistant: <b>Công cụ nhà phát triển -> Hành Động -> Công cụ phát triển hành động cho phép bạn thực hiện bất kỳ hành động nào có trong Home Assistant.</b><br/> - Khi bạn thực hiện thành công, sẽ sao chép hết nội dung code trong ô nhập liệu đó vào đây')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<textarea required class="form-control border-success" rows="5" name="intents[<?= $index ?>][data_yaml]" id="intents[<?= $index ?>][data_yaml]">
<?= htmlspecialchars(arrayToYaml($intent['data_yaml'] ?? [])) ?>
</textarea>
<div class="invalid-feedback">Cần nhập nội dung code YAML cho tác vụ này</div>
</div>
<center>
<button type="button" class="btn btn-success rounded-pill" onclick="yaml_test_code_hass('intents[<?= $index ?>][data_yaml]')"><i class="bi bi-align-start"></i> Chạy Thử Code Yaml</button>
</center>
</div>
</div>



<!-- Câu Lệnh Cần Thực Thi -->
<div class="row mb-3">
<label for="intents[<?= $index ?>][questions]" class="col-sm-3 col-form-label">Câu Lệnh <i class="bi bi-question-circle-fill" onclick="show_message('Câu Lệnh Để Điều Khiển, Chạy Hành Động Này, Không Giới Hạn Nhiều Câu Lệnh, Mỗi Câu Lệnh Là 1 Dòng<br/>- Khi bạn nói đúng 1 trong các câu lệnh được thiết lập, thì tác vụ sẽ được chạy')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<textarea required class="form-control border-success" rows="5" name="intents[<?= $index ?>][questions]" id="intents[<?= $index ?>][questions]">
<?= htmlspecialchars(implode("\n", $intent['questions'] ?? [])) ?></textarea>
</textarea>
<div class="invalid-feedback">Cần nhập câu lệnh thực thi cho tác vụ này</div>
</div>
</div>
</div>

<center>
<button type="button" class="btn btn-danger rounded-pill" onclick="removeIntentSection('accordion_button_custom_hass_<?= $index + 1 ?>', '<?= htmlspecialchars($intent['name']) ?>')"><i class="bi bi-trash"></i> Xóa Tác Vụ</button>
</center>

</div>
</div>
</div>
<?php endforeach; ?>

<?php

}
} else {
    #echo("Không tìm thấy tệp JSON cho cấu hình Custom Home Assistant: {$jsonFilePath}");
    $defaultContent = json_encode([
        "intents" => []
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    // Ghi nội dung vào tệp tin
    if (file_put_contents($jsonFilePath, $defaultContent) !== false) {
        chmod($jsonFilePath, 0777);
    }
}

?>

 <!-- Các phần tử mới sẽ được thêm vào đây -->
<div id="accordion-container">
</div>

<center>
<button class="btn btn-primary  rounded-pill" type="submit" name="save_custom_home_assistant"><i class="bi bi-save"></i> Lưu thay đổi</button>
 <button type="button" class="btn btn-success rounded-pill" onclick="addNewSection()">Thêm Mới Tác Vụ</button>
 
 <button type="button" class="btn btn-warning rounded-pill" title="Xem dữ liệu Đã cấu hình Custom Home Assistant" id="openModalBtn_Home_Assistant">
 <i class="bi bi-eye"></i>Xem dữ liệu Cấu Hình</button>
 <button type="button" class="btn btn-info rounded-pill" title="Tải Xuống file: <?php echo $jsonFilePath; ?>" onclick="downloadFile('<?php echo $jsonFilePath; ?>')">
<i class="bi bi-download"></i> Tải Xuống</button>
 <button class="btn btn-danger rounded-pill" type="submit" name="delete_all_custom_home_assistant" onclick="return confirmRestore('Bạn có chắc chắn muốn xóa tất cả dữ liệu cấu hình Custom Home Assistant không')"><i class="bi bi-trash"></i> Xóa Dữ Liệu Cấu hình</button>
</center>

<h5 class="card-title"><font color="green">Khôi Phục Dữ Liệu:</font></h5>
<div class="row mb-3">
    <label class="col-sm-3 col-form-label"><b>Tải Lên Tệp Và Khôi Phục:</b></label>
    <div class="col-sm-9">
        <div class="input-group">
            <input class="form-control border-success" type="file" name="fileToUpload_custom_hass_restore" accept=".json">
            <button class="btn btn-warning border-success" type="submit" name="start_recovery_custom_homeassistant" value="khoi_phuc_tu_tep_tai_len" onclick="return confirmRestore(\'Bạn có chắc chắn muốn tải lên tệp để khôi phục dữ liệu Home_Assistant_Custom.json không?\')">Tải Lên & Khôi Phục</button>
        </div>
    </div>
</div>
 
<div class="row mb-3">
    <label class="col-sm-3 col-form-label"><b>Hoặc Chọn Tệp Khôi Phục:</b></label>
    <div class="col-sm-9">
<?php
$jsonFiles = glob($Config['backup_upgrade']['custom_home_assistant']['backup_path'].'/*.json');
$co_tep_BackUp_customhass = true;
if (empty($jsonFiles)) {
	$co_tep_BackUp_customhass = false;
echo '<select class="form-select border-primary" name="backup_custom_hass_json_files" id="backup_custom_hass_json_files">';
    echo '<option selected value="">Không có tệp khôi phục dữ liệu Config nào</option>';
	echo '</select>';
} else {
	$co_tep_BackUp_customhass = true;
echo '<div class="input-group"><select class="form-select border-primary" name="backup_custom_hass_json_files" id="backup_custom_hass_json_files">';
echo '<option selected value="">Chọn Tệp Khôi Phục Dữ Liệu Custom Home Assistant</option>';
foreach ($jsonFiles as $file) {
    $fileName = basename($file);
    echo '<option value="' . htmlspecialchars($Config['backup_upgrade']['custom_home_assistant']['backup_path'].'/'.$fileName) . '">' . htmlspecialchars($fileName) . '</option>';
}
echo '</select>
<button class="btn btn-warning border-primary" type="submit" name="start_recovery_custom_homeassistant" value="khoi_phuc_file_he_thong">Khôi Phục</button>
<button type="button" class="btn btn-info border-primary" title="Tải Xuống Tệp Sao Lưu Custom Home Assistant" onclick="dowlaod_file_backup_hass_custom(\'get_value_backup_config\')"><i class="bi bi-download"></i></button>
<button type="button" class="btn btn-success border-primary" title="Xem Tệp Sao Lưu Custom Home Assistant" onclick="readJSON_file_path(\'get_value_backup_config\')"><i class="bi bi-eye"></i></button>
<button type="button" class="btn btn-danger border-primary" title="Xóa Tệp Sao Lưu Custom Home Assistant" onclick="delete_file_backup_hass_custom(\'get_value_backup_config\')"><i class="bi bi-trash"></i></button>
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
    <div class="modal fade" id="myModal_Home_Assistant" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
        <div class="modal-dialog" id="modal_dialog_show_Home_Assistant" role="document">
            <div class="modal-content">
                <div class="modal-header">
				<b><font color=blue><div id="name_file_showzz"></div></font></b> 
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
//Mảng chứa danh sách các phần tử đã xóa
let deletedItems = [];
//Để lưu lại phần tử gốc khi xóa
let removedSections = {};

function removeIntentSection(id, name_text) {
    const section = document.getElementById(id);
    if (section) {
        // Thêm ID phần tử vào mảng deletedItems
        deletedItems.push(id);
        // Lưu lại phần tử gốc để khôi phục sau
        removedSections[id] = section.innerHTML;
        // Thay thế nội dung của phần tử bị xóa bằng thông báo và thêm nút "Hủy bỏ"
		section.innerHTML = '<div class="alert alert-danger">' +
			'Đã xóa tác vụ: <b>' + name_text + '</b>, Thay đổi sẽ được lưu khi bạn nhấn "Lưu thay đổi".' +
			'<button type="button" class="btn btn-warning btn-sm rounded-pill" onclick="restoreIntentSection(\'' + id + '\')">Hủy bỏ</button>' +
			'</div>';
        //thêm một input ẩn vào form để gửi danh sách phần tử đã xóa sau đó
        document.querySelector('form').addEventListener('submit', function () {
            const deletedInput = document.createElement('input');
            deletedInput.type = 'hidden';
            deletedInput.name = 'deleted_items';
            deletedInput.value = JSON.stringify(deletedItems);
            this.appendChild(deletedInput);
        });
    }
}

// Hàm phục hồi phần tử
function restoreIntentSection(id) {
    const section = document.getElementById(id);
    if (section && removedSections[id]) {
        section.innerHTML = removedSections[id];
        deletedItems = deletedItems.filter(itemId => itemId !== id);
        delete removedSections[id];
    }
}
</script>

<script>
let sectionCounter = <?= count($intents) + 1; ?>;
// Tạo mới HTML cho các phần tử
function addNewSection() {
    // Tạo ID duy nhất cho mỗi phần tử
    const sectionID = 'section_custom_hass_' + sectionCounter;
    const newSection = 
        '<div class="card" id="' + sectionID + '">' +
            '<div class="card-body">' +
                '<h5 class="card-title">' +
                    '<font color="green">Thêm Mới Tác Vụ:</font>' +
                '</h5>' +

                '<div class="row mb-3">' +
                    '<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message(\'Bật hoặc Tắt để kích hoạt hành động này\')"></i>:</label>' +
                    '<div class="col-sm-9">' +
                        '<div class="form-switch">' +
                            '<input class="form-check-input" type="checkbox" checked name="intents[' + (sectionCounter - 1) + '][active]" id="intents[' + (sectionCounter - 1) + '][active]">' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="row mb-3">' +
                    '<label class="col-sm-3 col-form-label">Tên Tác Vụ <i class="bi bi-question-circle-fill" onclick="show_message(\'Tên Định Danh Để Phân Biệt Với Các Hành Động, Thao Tác Khác\')"></i>:</label>' +
                    '<div class="col-sm-9">' +
                        '<input required type="text" name="intents[' + (sectionCounter - 1) + '][name]" class="form-control border-success" placeholder="Nhập tên tác vụ">' +
                    '<div class="invalid-feedback">Cần đặt tên cho hành động này</div>' +
					'</div>' +
                '</div>' +

                '<div class="row mb-3">' +
                    '<label class="col-sm-3 col-form-label">Tùy Chỉnh Câu Phản Hồi <i class="bi bi-question-circle-fill" onclick="show_message(\'Tùy Chỉnh Câu Phản Hồi Lại Khi Thực Hiện Thao Tác Này, Không Muốn Phản Hồi Lại Theo Ý Của Bạn Thì Để Trống\')"></i>:</label>' +
                    '<div class="col-sm-9">' +
                        '<input type="text" name="intents[' + (sectionCounter - 1) + '][reply]" class="form-control border-success" placeholder="Nhập câu phản hồi tùy chỉnh nếu cần">' +
                    '</div>' +
                '</div>' +

                '<div class="row mb-3">' +
                    '<label class="col-sm-3 col-form-label">Code YAML <i class="bi bi-question-circle-fill" onclick="show_message(\'Nội Dung Code YAML Cần Thực Hiện<br/>Nội dung Code YAML này được lấy ở Trong Home Assistant: <b>Công cụ nhà phát triển -> Hành Động -> Công cụ phát triển hành động cho phép bạn thực hiện bất kỳ hành động nào có trong Home Assistant.</b><br/> - Khi bạn thực hiện thành công, sẽ sao chép hết nội dung code trong ô nhập liệu đó vào đây\')"></i>:</label>' +
                    '<div class="col-sm-9">' +
                        '<textarea required name="intents[' + (sectionCounter - 1) + '][data_yaml]" class="form-control border-success" rows="5" placeholder="Nhập code YAML trong Công cụ nhà phát triển của Home Assistant"></textarea>' +
                    '<div class="invalid-feedback">Cần nhâp Code YAML của Home Assistant</div>' +
					'</div>' +
                '</div>' +

                '<div class="row mb-3">' +
                    '<label class="col-sm-3 col-form-label">Câu Lệnh <i class="bi bi-question-circle-fill" onclick="show_message(\'Câu Lệnh Để Điều Khiển, Chạy Hành Động Này, Không Giới Hạn Nhiều Câu Lệnh, Mỗi Câu Lệnh Là 1 Dòng<br/>- Khi bạn nói đúng 1 trong các câu lệnh được thiết lập, thì tác vụ sẽ được chạy\')"></i>:</label>' +
                    '<div class="col-sm-9">' +
                        '<textarea required name="intents[' + (sectionCounter - 1) + '][questions]" class="form-control border-success" rows="5" placeholder="Nhập câu lệnh cần thực thi tác vụ này"></textarea>' +
                    '<div class="invalid-feedback">Cần nhâp câu lệnh cần thực thi</div>' +
					'</div>' +
                '</div>' +

                '<center>' +
                    '<button type="button" class="btn btn-danger rounded-pill" onclick="removeIntentSection(\'' + sectionID + '\', \'Thêm Mới Tác Vụ\')">' +
                        'Xóa Tác Vụ' +
                    '</button>' +
                '</center>' +
            '</div>' +
        '</div>';
    document.getElementById('accordion-container').insertAdjacentHTML('beforeend', newSection);
    sectionCounter++;
}
</script>

<script>
//Xóa file backup Config
function delete_file_backup_hass_custom(filePath) {
    if (filePath === "get_value_backup_config") {
        var get_value_backup_config = document.getElementById('backup_custom_hass_json_files').value;
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
function dowlaod_file_backup_hass_custom(filePath) {
    if (filePath === "get_value_backup_config") {
        var get_value_backup_config = document.getElementById('backup_custom_hass_json_files').value;
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
        var get_value_backup_config = document.getElementById('backup_custom_hass_json_files').value;
        if (get_value_backup_config === "") {
            showMessagePHP("Không có tệp nào được chọn để xem nội dung");
        } else {
            filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
            read_loadFile(filePath);
			document.getElementById('name_file_showzz').textContent = "Tên File: "+filePath.split('/').pop();
            $('#myModal_Home_Assistant').modal('show');
        }
    } else {
        read_loadFile(filePath);
		document.getElementById('name_file_showzz').textContent = "Tên File: "+filePath.split('/').pop();
        $('#myModal_Home_Assistant').modal('show');
    }
}

//Test điều khiển code yaml
function yaml_test_code_hass(id_texara) {
    yamlInput = yamlToArrayy(document.getElementById(id_texara).value);
    input = JSON.stringify(yamlInput, null, 2);
    try {
        const actionData = JSON.parse(input);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'includes/php_ajax/Check_Connection.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        const data = 'yaml_test_control_homeassistant=' + encodeURIComponent(JSON.stringify(actionData));
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                var response = JSON.parse(xhr.responseText);
                        if (response.success) {
							showMessagePHP(response.message, 3)
                        } else {
                            show_message(response.message, 3);
                        }
            } else {
				show_message('Lỗi yêu cầu: ' + xhr.status);
            }
        };
        xhr.onerror = function () {
			show_message('Lỗi xảy ra khi gửi yêu cầu');
        };
        xhr.send(data);
    } catch (err) {
		show_message('Dữ liệu không hợp lệ' + err);
    }
}

//Chuyển Yaml Sang Json
function yamlToArrayy(yaml) {
    const lines = yaml.trim().split("\n");
    const result = {};
    const path = [];
    let lastKey = null;
    for (let line of lines) {
        if (line.trim() === "") continue;
        let matches;
        if ((matches = line.match(/^(\s*)-(.*)$/))) {
            const indent = matches[1].length / 2;
            const value = matches[2].trim();
            while (path.length > indent) {
                path.pop();
            }
            let parent = result;
            for (const segment of path) {
                parent = parent[segment];
            }
            if (lastKey !== null && Array.isArray(parent[lastKey])) {
                parent[lastKey].push(value);
            } else {
                parent[lastKey] = [value];
            }
        } else if ((matches = line.match(/^(\s*)([^:]+):(.*)$/))) {
            const indent = matches[1].length / 2;
            const key = matches[2].trim();
            let value = matches[3].trim().replace(/^"|"$/g, "");
            while (path.length > indent) {
                path.pop();
            }
            let parent = result;
            for (const segment of path) {
                parent = parent[segment];
            }
            if (key === "entity_id") {
                if (value.startsWith("-")) {
                    parent[key] = value.split("\n");
                } else {
                    parent[key] = value;
                }
            } else {
                if (value === "") {
                    parent[key] = {};
                    path.push(key);
                } else {
                    parent[key] = isNaN(value) ? value : parseFloat(value);
                }
            }
            lastKey = key;
        }
    }
    return result;
}

</script>



<script>
// Hiển thị modal xem nội dung file json Home_Assistant.json
['openModalBtn_Home_Assistant'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function() {
		var file_name_hassJSON = "<?php echo $jsonFilePath; ?>";
        read_loadFile(file_name_hassJSON);
		document.getElementById('name_file_showzz').textContent = "Tên File: "+file_name_hassJSON.split('/').pop();
        $('#myModal_Home_Assistant').modal('show');
    });
});
</script>
<script src="assets/vendor/prism/prism.min.js"></script>
<script src="assets/vendor/prism/prism-json.min.js"></script>
<?php
include 'html_js.php';
?>

</body>
</html>