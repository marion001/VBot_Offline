<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include '../../Configuration.php';
//Cấu hình tiêu đề CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($Config['contact_info']['user_login']['active']) {
  session_start();
  if (
    !isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
  ) {
	if (isset($_GET['file']) && !empty($_GET['file'])) {
		$requested_file = $_GET['file'];
		if ($requested_file === $VBot_Offline.'Version.json' || $requested_file === $VBot_Offline.'html/Version.json') {
			$file_path = $requested_file;
			$response = [
				'success' => true,
				'message' => 'Đọc file thành công',
				'data' => file_get_contents($requested_file)
			];
			echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		} else{
		session_unset();
		session_destroy();
		echo json_encode([
		  'success' => false,
		  'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	  }
	}
	else{
		session_unset();
		session_destroy();
		echo json_encode([
		  'success' => false,
		  'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	  }
  }
}

//Hàm để quy đổi kích thước file
function formatSize($size)
{
  if ($size >= 1073741824) {
    return round($size / 1073741824, 2) . ' GB';
  } elseif ($size >= 1048576) {
    return round($size / 1048576, 2) . ' MB';
  } elseif ($size >= 1024) {
    return round($size / 1024, 2) . ' KB';
  } else {
    return $size . ' bytes';
  }
}

//Hàm đệ quy để tìm tất cả các file trong thư mục
function get_all_file_directory($dir)
{
  $files = [];
  $items = scandir($dir);

  foreach ($items as $item) {
    // Bỏ qua các thư mục . và ..
    if ($item === '.' || $item === '..') {
      continue;
    }
    $path = $dir . '/' . $item;
    if (is_dir($path)) {
      $files = array_merge($files, get_all_file_directory($path));
    } else {
      $files[] = [
        'name' => $item,
        'path' => $path,
        'size' => formatSize(filesize($path)),
        'created_at' => date("d-m-Y H:i:s", filectime($path)),
      ];
    }
  }
  return $files;
}

function encodeFileToBase64($filePath)
{
  if (file_exists($filePath)) {
    $fileContent = file_get_contents($filePath);
    $base64Content = base64_encode($fileContent);
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    $response = ['success' => true, 'data' => ['fileName' => basename($filePath), 'base64Content' => $base64Content, 'fileExtension' => $fileExtension]];
    return json_encode($response);
  } else {
    return json_encode(['success' => false, 'error' => 'File not found']);
  }
}

//Tìm Kiếm Âm Thanh Trong Thư Mục Music_Local
if (isset($_GET['scan_Music_Local'])) {
  $directory = $VBot_Offline . 'Media/Music_Local';
  $searchPattern = $directory . '/*{' . implode(',', $Allowed_Extensions_Audio) . '}';
  $allFiles = glob($searchPattern, GLOB_BRACE);
  echo json_encode($allFiles);
  exit();
}

//TÌm Kiếm Âm Thanh Trong Thư Mục welcome
if (isset($_GET['scan_Audio_Startup'])) {
  $directory = $VBot_Offline . 'resource/sound/welcome';
  $searchPattern = $directory . '/*{' . implode(',', $Allowed_Extensions_Audio) . '}';
  $allFiles = glob($searchPattern, GLOB_BRACE);
  echo json_encode($allFiles);
  exit();
}

//Chuyển Âm Thanh Thành Base64
if (isset($_GET['audio_b64'])) {
  $filePath = isset($_GET['path']) ? $_GET['path'] : '';
  echo encodeFileToBase64($filePath);
  exit();
}

//Tìm Kiếm Âm Thanh Trong Thư Mục TTS_Audio
if (isset($_GET['TTS_Audio'])) {
  $file = $_GET['TTS_Audio'];
  $filePath = $VBot_Offline . $file;
  if (file_exists($filePath)) {
    $fileInfo = pathinfo($filePath);
    $fileExtension = strtolower($fileInfo['extension']);
    $mimeType = 'audio/' . $fileExtension;
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
  } else {
    http_response_code(404);
    echo 'Tệp không tồn tại.';
  }
  exit();
}

if (isset($_GET['empty_the_file'])) {
  $file_path = $_GET['file_path'];
  $response = [];
  if (file_exists($file_path)) {
    file_put_contents($file_path, '');
    $response['success'] = true;
    $response['message'] = "Đã Xóa Dữ Liệu File: " . basename($file_path) . " Thành Công";
  } else {
    $response['success'] = false;
    $response['message'] = "File: " . basename($file_path) . " Không Tồn Tại Để Xóa Dữ Liệu";
  }
  echo json_encode($response);
}

//dùng cho delete_data_backlist Hàm để xóa giá trị từ đường dẫn bằng cách thay thế nó với giá trị được chỉ định
function updateValueByPath(&$data, $path, $newValue)
{
  $keys = explode('->', $path);
  $lastKey = array_pop($keys);
  $array = &$data;
  foreach ($keys as $key) {
    if (!isset($array[$key])) {
      return false;
    }
    $array = &$array[$key];
  }
  $array[$lastKey] = $newValue;
  return true;
}

// Kiểm tra và xử lý xóa giá trị nếu tham số 'delete_data_backlist' và 'path' được truyền
if (isset($_GET['delete_data_backlist']) && isset($_GET['path'])) {
  $response = [
    'success' => false,
    'message' => '',
    'data' => null
  ];
  $path_to_update = $_GET['path'];
  $value_type = isset($_GET['value_type']) ? $_GET['value_type'] : null;
  if ($value_type === 'null') {
    $newValue = null;
  } elseif ($value_type === '{}') {
    $newValue = [];
  } elseif ($value_type === '[]') {
    $newValue = [];
  } else {
    $newValue = $_GET['value_type'] ?? null;
  }
  if (file_exists($Backlist_File_Name)) {
    $fileContents = file_get_contents($Backlist_File_Name);
    if ($fileContents !== false) {
      $data = json_decode($fileContents, true);
      if ($data !== null) {
        if (updateValueByPath($data, $path_to_update, $newValue)) {
          if (file_put_contents($Backlist_File_Name, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
            $response['success'] = true;
            $response['message'] = 'Giá trị đã được cập nhật thành công.';
            $response['data'] = $data;
          } else {
            $response['message'] = 'Lỗi: Không thể lưu nội dung vào file ' . $Backlist_File_Name . '.';
          }
        } else {
          $response['message'] = 'Lỗi: Đường dẫn không hợp lệ hoặc giá trị không tồn tại.';
        }
      } else {
        $response['message'] = 'Lỗi: Dữ liệu JSON không hợp lệ trong file ' . $Backlist_File_Name . '.';
      }
    } else {
      $response['message'] = 'Lỗi: Không thể đọc nội dung của file ' . $Backlist_File_Name . '.';
    }
  } else {
    $response['message'] = 'Lỗi: File ' . $Backlist_File_Name . ' không tồn tại.';
  }
  echo json_encode($response);
  exit();
}

// hiển thị toàn bộ dữ liệu trong file backlist.json
if (isset($_GET['data_backlist'])) {
  if (file_exists($Backlist_File_Name)) {
    $fileContents = file_get_contents($Backlist_File_Name);
    if ($fileContents !== false) {
      $response['success'] = true;
      $response['message'] = 'Tải dữ liệu thành công.';
      $response['data'] = json_decode($fileContents, true);
    } else {
      $response['message'] = 'Lỗi: Không thể đọc nội dung của file ' . $Backlist_File_Name . '.';
    }
  } else {
    $response['message'] = 'Lỗi: File ' . $Backlist_File_Name . ' không tồn tại.';
  }
  echo json_encode($response);
  exit();
}

if (isset($_GET['read_file_path']) && isset($_GET['file']) && !empty($_GET['file'])) {
  $response = [
    'success' => false,
    'message' => '',
    'data' => null
  ];
  $file_path = $_GET['file'];
  if (file_exists($file_path) && is_readable($file_path)) {
    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
    if ($file_extension === 'json' || $file_extension === 'txt' || $file_extension === 'log' || $file_extension === 'logs') {
      $content = file_get_contents($file_path);
      $response['success'] = true;
      $response['message'] = 'Tệp đã được đọc thành công.';
      if ($file_extension === 'json') {
        $response['data'] = json_decode($content, true);
        header('Content-Type: application/json');
      } else {
        $response['data'] = $content;
        header('Content-Type: text/plain');
        $response['data'] = nl2br(htmlspecialchars($content));
      }
    } else {
      $response['message'] = 'Loại tệp không được phép.';
    }
  } else {
    $response['message'] = 'Tệp không tồn tại hoặc không có quyền đọc.';
  }
  echo json_encode($response);
  exit();
}

//Hiển thị toàn bộ file trong thư mục
if (isset($_GET['show_all_file'])) {
  $directory = $_GET['directory_path'];
  if (!is_dir($directory)) {
    echo json_encode([
      'success' => false,
      'message' => "Thư mục $directory không tồn tại.",
      'data' => []
    ]);
    exit;
  }
  $fileList = get_all_file_directory($directory);
  if (empty($fileList)) {
    echo json_encode([
      'success' => false,
      'message' => 'Không có tệp nào trong thư mục.',
      'data' => []
    ]);
    exit;
  }
  echo json_encode([
    'success' => true,
    'message' => 'Danh sách file đã được tìm thấy.',
    'data' => $fileList
  ]);
  exit();
}

//Xem cấu trúc tệp backup tar.gz
if (isset($_GET['read_file_backup']) && isset($_GET['file']) && !empty($_GET['file'])) {
  $filePath = escapeshellarg($_GET['file']);
  $command = "tar -tzf $filePath";
  $output = shell_exec($command);
  if ($output) {
    $fileList = explode("\n", trim($output));
    $response = [
      "success" => true,
      "message" => "Đọc nội dung tệp thành công.",
      "data" => $fileList
    ];
  } else {
    $response = [
      "success" => false,
      "message" => "Không thể đọc nội dung của tệp .tar.gz.",
      "data" => []
    ];
  }
  header('Content-Type: application/json');
  echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit();
}

# Xem nội dung file bên trong cấu trúc tệp .tar.gz
if (isset($_GET['read_files_in_backup']) && isset($_GET['file_path']) && !empty($_GET['file_path']) && isset($_GET['file_name']) && !empty($_GET['file_name'])) {
  $file_path = $_GET['file_path'];
  $file_name = $_GET['file_name'];
  if (file_exists($file_path)) {
    $command = "tar -O -xzf " . escapeshellarg($file_path) . " " . escapeshellarg($file_name);
    $file_content = shell_exec($command);
    if ($file_content !== null) {
      if (substr($file_name, -5) === '.json') {
        $decoded_data = json_decode($file_content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $response = [
            "success" => true,
            "message" => "Đọc nội dung tệp thành công.",
            "data" => $decoded_data
          ];
        } else {
          $response = [
            "success" => false,
            "message" => "Nội dung tệp JSON không hợp lệ."
          ];
        }
      } else {
        $response = [
          "success" => true,
          "message" => "Đọc nội dung tệp thành công.",
          "data" => $file_content
        ];
      }
    } else {
      $response = [
        "success" => false,
        "message" => "Không thể đọc nội dung tệp."
      ];
    }
  } else {
    $response = [
      "success" => false,
      "message" => "Tệp không tồn tại."
    ];
  }
  echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
?>