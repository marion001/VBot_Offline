<?php
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
  #Facebook: https://www.facebook.com/TWFyaW9uMDAx
  
include '../../Configuration.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');
if ($Config['contact_info']['user_login']['active']){
  session_start();
  // Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
  if (!isset($_SESSION['user_login']) ||
      (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))) {
      // Nếu chưa đăng nhập hoặc đã quá 12 tiếng, hủy session và chuyển hướng đến trang đăng nhập
      session_unset();
      session_destroy();
      echo json_encode([
          'success' => false,
          'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      exit;
  }
}

  #Lấy danh sách hotword, và lib theo tùy chọn lang trong Config.json và hiển thị
  if (isset($_GET['hotword'])){
      // Lấy giá trị ngôn ngữ từ GET
      $lang_get_HOTWORD = isset($_GET['lang']) ? $_GET['lang'] : '';
      if ($lang_get_HOTWORD === 'vi' || $lang_get_HOTWORD === 'eng'){
  		$directory = $VBot_Offline . 'resource/picovoice/library';
          $files = glob($directory . '/*.pv');
          $file_list = array_map('basename', $files);
          $porcupineConfig = $Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang_get_HOTWORD];
          $response = ['lang' => $lang_get_HOTWORD, 'config' => $porcupineConfig, 'files_lib_pv' => $file_list, 'path_pv' => $directory . '/', 'path_ppn' => $VBot_Offline . 'resource/hotword/' . $lang_get_HOTWORD . '/', 'config_lib_pv_to_lang' => $Config['smart_config']['smart_wakeup']['hotword']['library'][$lang_get_HOTWORD]['modelFilePath']];
          echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      else if ($lang_get_HOTWORD === 'snowboy'){
  		$directory = $VBot_Offline . 'resource/snowboy/hotword';
          $files = array_merge(
  			glob($directory . '/*.pmdl'),
  			glob($directory . '/*.umdl')
  		);
          $file_list = array_map('basename', $files);
          $porcupineConfig = $Config['smart_config']['smart_wakeup']['hotword']['snowboy'];
          $response = ['lang' => $lang_get_HOTWORD, 'config' => $porcupineConfig, 'files_hotword' => $file_list, 'path_hotword' => $directory . '/'];
          echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      else{
          // Trả về đối tượng rỗng nếu ngôn ngữ không hợp lệ
          echo json_encode(['lang' => '', 'config' => [], 'files_lib_pv' => [], 'config_lib_pv_to_lang' => $Config['smart_config']['smart_wakeup']['hotword']['library'][$lang_get_HOTWORD]['modelFilePath']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      exit();
  }

  #Lấy danh sách câu phản hồi
if (isset($_GET['get_wakeup_reply'])) {
    if (!isset($Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy danh sách file phản hồi.',
            'config' => []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    $allFiles = $Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'];
    echo json_encode([
        'success' => true,
        'message' => 'Lấy danh sách câu phản hồi thành công.',
        'config' => $allFiles
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}


  // Xử lý khi tải lên file ppn và pv xong cập nhật vào Config.json, nếu trùng tên file chỉ tải lên mà không sửa trong config
  if (isset($_POST['action_ppn_pv']) && $_POST['action_ppn_pv'] === 'upload_files_ppn_pv'){
      $uploadDirLibrary = $VBot_Offline . 'resource/picovoice/library/';
      $uploadDirHotword = $VBot_Offline . 'resource/hotword/';
      $lang = $_POST['lang_hotword_get'];
      if (file_exists($Config_filePath)){
          $jsonContent = file_get_contents($Config_filePath);
          $configData = json_decode($jsonContent, true);
          if (!isset($configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang])){
              $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = [];
          }
          $existingFiles = array_column($configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang], 'file_name');
          $updatedConfig = $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang];
      }
      else{
          $configData = ['smart_config' => ['smart_wakeup' => ['hotword' => ['porcupine' => ['vi' => [], 'eng' => []]]]]];
          $existingFiles = [];
      }
      $responseMessages = [];
      foreach ($_FILES['upload_files_ppn_pv']['error'] as $key => $error){
          if ($error == UPLOAD_ERR_OK){
              $tmpName = $_FILES['upload_files_ppn_pv']['tmp_name'][$key];
              $name = basename($_FILES['upload_files_ppn_pv']['name'][$key]);
              $ext = pathinfo($name, PATHINFO_EXTENSION);
              if ($ext == 'pv'){
                  $uploadFile = $uploadDirLibrary . $name;
                  move_uploaded_file($tmpName, $uploadFile);
                  $responseMessages[] = "Tệp tin '$name' đã tải lên thành công vào: '$uploadFile'";
              }
              elseif ($ext == 'ppn'){
                  $uploadFile = $uploadDirHotword . $lang . '/' . $name;
                  $moveResult = move_uploaded_file($tmpName, $uploadFile);
                  if ($moveResult){
                      chmod($uploadFile, 0777);
                      if (in_array($name, $existingFiles)){
                          // Nếu tên file đã tồn tại thì không cần cập nhật cấu hình
                          $responseMessages[] = "Tệp tin: '$name' đã tải lên thành công vào '$uploadFile' nhưng đã tồn tại trong ngôn ngữ '$lang', không cần cập nhật Config.json \n";
                      }
                      else{
                          // Thêm thông tin file mới vào mảng cấu hình
                          $updatedConfig[] = ["active" => true, "file_name" => $name, "sensitive" => 0.5];
                          $responseMessages[] = "Tệp tin: '$name' đã tải lên thành công vào '$uploadFile' và thêm vào ngôn ngữ '$lang' trong Config.json \n";
                      }
                  }
                  else{
                      $responseMessages[] = "Không thể tải tập tin lên '$name', hoặc không có full quyền hạn 0777";
                  }
              }
              else{
                  $responseMessages[] = "Loại tập tin không được hỗ trợ: $ext.";
              }
          }
          else{
              $responseMessages[] = "Lỗi tải file lên, cho file $key với mã lỗi: $error.";
          }
      }
      // Cập nhật mảng cấu hình
      if (!empty($updatedConfig)){
          $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = $updatedConfig;
          file_put_contents($Config_filePath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      }
      echo json_encode(['status' => 'success', 'messages' => $responseMessages], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      exit;
  }
  
  #Cập nhật hotword snowboy khi được tải lên
  if (isset($_POST['action_hotword_snowboy']) && $_POST['action_hotword_snowboy'] === 'upload_files_hotword_snowboy') {
      $uploadDirHotword = $VBot_Offline . 'resource/snowboy/hotword/';
      # Lấy dữ liệu từ Config.json
      if (file_exists($Config_filePath)) {
          $jsonContent = file_get_contents($Config_filePath);
          $configData = json_decode($jsonContent, true);
          if (!isset($configData['smart_config']['smart_wakeup']['hotword']['snowboy'])) {
              $configData['smart_config']['smart_wakeup']['hotword']['snowboy'] = [];
          }
          $existingFiles = array_column($configData['smart_config']['smart_wakeup']['hotword']['snowboy'], 'file_name');
          $updatedConfig = $configData['smart_config']['smart_wakeup']['hotword']['snowboy'];
      } else {
          $configData = ['smart_config' => ['smart_wakeup' => ['hotword' => ['snowboy' => []]]]];
          $existingFiles = [];
      }
      // Kiểm tra thư mục lưu trữ tồn tại chưa, nếu chưa thì tạo mới
      if (!is_dir($uploadDirHotword)) {
          mkdir($uploadDirHotword, 0777, true);
      }
      $uploadSuccess = [];
      $uploadErrors = [];
      // Kiểm tra nếu có file được tải lên
      if (!empty($_FILES['upload_files_hotword_snowboy']['name'][0])) {
          foreach ($_FILES['upload_files_hotword_snowboy']['name'] as $key => $fileName) {
              $fileTmpPath = $_FILES['upload_files_hotword_snowboy']['tmp_name'][$key];
              $fileSize = $_FILES['upload_files_hotword_snowboy']['size'][$key];
              $fileError = $_FILES['upload_files_hotword_snowboy']['error'][$key];
              // Kiểm tra lỗi khi tải lên
              if ($fileError !== UPLOAD_ERR_OK) {
                  $uploadErrors[] = "Lỗi tải file: $fileName (Mã lỗi: $fileError)";
                  continue;
              }
              // Kiểm tra định dạng file hợp lệ
              $allowedExtensions = ['pmdl', 'umdl'];
              $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
              if (!in_array($fileExtension, $allowedExtensions)) {
                  $uploadErrors[] = "File không hợp lệ: $fileName (Chỉ hỗ trợ .pmdl, .umdl)";
                  continue;
              }
              // Kiểm tra dung lượng file (giới hạn 5MB)
              if ($fileSize > 5 * 1024 * 1024) {
                  $uploadErrors[] = "File quá lớn: $fileName (Tối đa 5MB)";
                  continue;
              }
              $name = basename($fileName);
              $destinationPath = $uploadDirHotword . $name;
              // Di chuyển file vào thư mục
              if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                  chmod($destinationPath, 0777);
  				$uploadSuccess[] = "Tệp tin '$fileName' đã tải lên thành công vào: '$uploadDirHotword' và được thêm vào Config.json";
                  // Kiểm tra file có tồn tại trong Config.json chưa
                  if (!in_array($name, $existingFiles)) {
                      // Thêm thông tin file mới vào cấu hình
                      $updatedConfig[] = ["active" => true, "file_name" => $name, "sensitive" => 0.5];
                      //$uploadSuccess[] = "Thêm '$name' vào Config.json";
                  }
              } else {
                  $uploadErrors[] = "Không thể lưu file: $fileName";
              }
          }
          // Cập nhật Config.json
          if (!empty($updatedConfig)) {
              $configData['smart_config']['smart_wakeup']['hotword']['snowboy'] = $updatedConfig;
              file_put_contents($Config_filePath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
          }
      } else {
          $uploadErrors[] = "Không có file nào được chọn để tải lên.";
      }
      // Trả về phản hồi JSON
      header('Content-Type: application/json');
      echo json_encode([
          "status" => empty($uploadErrors) ? "success" : "error",
          "messages" => array_merge($uploadSuccess, $uploadErrors)
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      exit;
  }
  
  #cập nhật lại hotword picovoice eng và vi trong Config.json tương ứng với tất cả các file .ppn trong 2 thư mục eng và vi
  if (isset($_GET['reload_hotword_config'])){
      #$directories = ['/home/pi/VBot_Offline/resource/hotword/eng', '/home/pi/VBot_Offline/resource/hotword/vi'];
      $directories = [
          $VBot_Offline . "resource/hotword/eng",
          $VBot_Offline . "resource/hotword/vi"
      ];
      // Khởi tạo cấu hình mặc định
      $newPorcupineConfig = [
  		'vi' => $config['smart_config']['smart_wakeup']['hotword']['porcupine']['vi'] ?? [],
  		'eng' => $config['smart_config']['smart_wakeup']['hotword']['porcupine']['eng'] ?? []
  		];
  	foreach ($directories as $directory){
          if (!is_dir($directory)){
              continue;
          }
          $files = glob($directory . '/*.ppn');
          foreach ($files as $file){
              $parts = explode('/', $file);
              $fileName = end($parts);
              #echo $fileName;
              $lang = strpos($directory, 'eng') !== false ? 'eng' : 'vi';
              $exists = false;
              foreach ($newPorcupineConfig[$lang] as $item){
                  if ($item['file_name'] === $fileName){
                      $exists = true;
                      break;
                  }
              }
              if (!$exists){
                  $newPorcupineConfig[$lang][] = ['active' => true, 'file_name' => $fileName, 'sensitive' => 0.5];
              }
          }
      }
      $Config['smart_config']['smart_wakeup']['hotword']['porcupine'] = $newPorcupineConfig;
      if (file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))){
          echo json_encode(['status' => 'success', 'message' => 'Đã ghi cấu hình Config->Hotword tiếng anh và tiếng việt thành công.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      else{
          echo json_encode(['status' => 'error', 'message' => 'Lỗi khi ghi file cấu hình Hotword tiếng anh và tiếng việt'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      exit;
  }

#Cập nhật lại hotword snowboy
if (isset($_GET['reload_hotword_config_snowboy'])) {
    $directory = $VBot_Offline . "resource/snowboy/hotword";
    $newSnowboyConfig = [];
    if (is_dir($directory)) {
        // Lấy danh sách file .pmdl và .umdl
        $files = array_merge(glob("$directory/*.pmdl"), glob("$directory/*.umdl"));
        foreach ($files as $file) {
            $parts = explode('/', $file);
            $fileName = end($parts);
            $newSnowboyConfig[] = [
                'active' => true,
                'file_name' => $fileName,
                'sensitive' => 0.5
            ];
        }
        $Config['smart_config']['smart_wakeup']['hotword']['snowboy'] = $newSnowboyConfig;
        if (file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
            echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật cấu hình Hotword Snowboy thành công.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi khi ghi file cấu hình Hotword Snowboy.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Thư mục hotword không tồn tại.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

//Cập nhật lại Dữ Liệu WakeUP Reply
if (isset($_GET['reload_wakeup_reply'])) {
    $relativePath = "resource/sound/wakeup_reply";
    $absolutePath = $VBot_Offline . $relativePath;
    // Tìm tất cả các file mp3 trong thư mục
    $files = glob($absolutePath . "/*.mp3");
	//$files = array_merge(glob($absolutePath . "/*.mp3"), glob($absolutePath . "/*.wav"));
    $soundFiles = [];
    foreach ($files as $file) {
		$parts = explode('/', $file);
		$fileName = end($parts);
        $soundFiles[] = [
            "file_name" => $relativePath . "/" . $fileName,
			"active" => true
        ];
    }
    if (!file_exists($Config_filePath)) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy file cấu hình Config.json']);
        exit;
    }
    $configContent = file_get_contents($Config_filePath);
    $Config = json_decode($configContent, true);
    if (!is_array($Config)) {
        echo json_encode(['status' => 'error', 'message' => 'Cấu hình danh sách Câu Phản Hồi WakeUP Reply không hợp lệ.']);
        exit;
    }
    $Config['smart_config']['smart_wakeup']['wakeup_reply'] = [
        "active" => true,
        "sound_file" => $soundFiles
    ];
    if (file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
        echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật danh sách Câu Phản Hồi WakeUP Reply thành công.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không thể ghi danh sách Câu Phản Hồi WakeUP Reply vào file cấu hình.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

#Tải lên file Wakeup Reply
if (isset($_POST['wakeup_reply_upload']) && $_POST['wakeup_reply_upload'] === 'upload_files_wakeup_reply') {
    $targetDir = $VBot_Offline . 'resource/sound/wakeup_reply/';
    $response = ['status' => 'error', 'messages' => ['Không có file nào được xử lý.']];

    if (!empty($_FILES['upload_files_wakeup_reply'])) {
        $uploadedFiles = $_FILES['upload_files_wakeup_reply'];
        $successCount = 0;
        $successFiles = [];
        $errorMessages = [];
        for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
            $tmpName = $uploadedFiles['tmp_name'][$i];
            // Lấy tên file dùng explode với dấu '/'
            $nameParts = explode('/', $uploadedFiles['name'][$i]);
            $fileName = end($nameParts);
            $fileType = mime_content_type($tmpName);
            if ($fileType === "audio/mpeg" || pathinfo($fileName, PATHINFO_EXTENSION) === 'mp3') {
                $destination = $targetDir . $fileName;
                if (move_uploaded_file($tmpName, $destination)) {
                    $successCount++;
                    $successFiles[] = $fileName;  // Lưu tên file thành công
                } else {
                    $errorMessages[] = "Không thể lưu file: $fileName";
                }
            } else {
                $errorMessages[] = "$fileName không phải file .mp3 hợp lệ.";
            }
        }
		if ($successCount > 0) {
			// Đọc config hiện tại (nếu chưa đọc trước đó)
			$configContent = file_get_contents($Config_filePath);
			$Config = json_decode($configContent, true);
			// Mảng hiện tại trong config (có thể chưa tồn tại)
			$existingFiles = $Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] ?? [];
			// Lấy danh sách tên file hiện có để kiểm tra trùng
			$existingFileNames = [];
			foreach ($existingFiles as $item) {
				if (isset($item['file_name'])) {
					$existingFileNames[] = $item['file_name'];
				}
			}
			// Duyệt từng file upload thành công, thêm mới nếu chưa có
			foreach ($successFiles as $fileName) {
				$relativePath = "resource/sound/wakeup_reply/" . $fileName;
				if (!in_array($relativePath, $existingFileNames)) {
					$existingFiles[] = [
						"file_name" => $relativePath,
						"active" => true
					];
				}
			}
			$Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] = $existingFiles;
			file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			$response['status'] = 'success';
			$filesList = implode(", ", $successFiles);
			$response['messages'] = ["Đã tải lên thành công {$successCount} file: {$filesList}."];
			if (!empty($errorMessages)) {
				$response['messages'] = array_merge($response['messages'], $errorMessages);
			}
		}else {
            $response['messages'] = $errorMessages;
        }
    } else {
        $response['messages'] = ['Không có tệp nào được gửi.'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}


  ?>