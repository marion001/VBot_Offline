<?php
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
  #Facebook: https://www.facebook.com/TWFyaW9uMDAx
  
  include '../../Configuration.php';
  header('Content-Type: application/json');

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

  //Sử Dụng MEthod POST
  $filePath = isset($_POST['filePath']) ? $_POST['filePath'] : '';
  if (empty($filePath)) {
      echo json_encode([
          'status' => 'error',
          'message' => 'Đường dẫn file không được cung cấp.'
      ]);
      exit();
  }
  
  if (file_exists($filePath)) {
      $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
      $fileName = basename($filePath);
      if (unlink($filePath)) {
          $message = 'File: '.basename($filePath).' đã được xóa thành công.';
          if ($fileExtension === 'ppn') {
              $message = 'File .ppn: '.basename($filePath).' đã được xóa thành công.';
              $removed = false;
              foreach (['vi', 'eng'] as $lang) {
                  foreach ($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] as $key => $item) {
                      if ($item['file_name'] === $fileName) {
                          unset($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang][$key]);
                          $removed = true;
                      }
                  }
                  $Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = array_values($Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang]);
              }
              file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
          } elseif ($fileExtension === 'pv') {
              $message = 'File .pv: '.basename($filePath).' đã được xóa thành công.';
          }elseif ($fileExtension === 'pmdl' || $fileExtension === 'umdl') {
  			$removed = false;
                  foreach ($Config['smart_config']['smart_wakeup']['hotword']['snowboy'] as $key => $item) {
                      if ($item['file_name'] === $fileName) {
                          unset($Config['smart_config']['smart_wakeup']['hotword']['snowboy'][$key]);
                          $removed = true;
                      }
                  }
                  $Config['smart_config']['smart_wakeup']['hotword']['snowboy'] = array_values($Config['smart_config']['smart_wakeup']['hotword']['snowboy']);
  			file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  		}
          echo json_encode([
              'status' => 'success',
              'message' => $message
          ]);
      } else {
          echo json_encode([
              'status' => 'error',
              'message' => 'Lỗi, không thể xóa file: '.basename($filePath).' vui lòng kiểm tra quyền truy cập.'
          ]);
      }
  } else {
      echo json_encode([
          'status' => 'error',
          'message' => 'File: '.basename($filePath).' không tồn tại.'
      ]);
  }
  ?>