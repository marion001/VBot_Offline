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
  
  if (isset($_GET['upload_Music_Local'])) {
  	// Định nghĩa các định dạng file được phép tải lên
  	//$allowedExtensions = ['mp3', 'wav', 'flac'];
      // Thư mục lưu trữ file tải lên
      $targetDirectory = $VBot_Offline . 'Media/Music_Local/';
      // Kiểm tra nếu thư mục không tồn tại thì tạo mới
      if (!file_exists($targetDirectory)) {
          mkdir($targetDirectory, 0777, true);
      }
  
      // Kiểm tra xem có file nào được tải lên không
      if (!empty($_FILES['fileUpload']['name'][0])) {
          $messages = [];
          $success = true;
  
          foreach ($_FILES['fileUpload']['name'] as $index => $fileName) {
              $fileTmpName = $_FILES['fileUpload']['tmp_name'][$index];
              $fileSize = $_FILES['fileUpload']['size'][$index];
              $fileError = $_FILES['fileUpload']['error'][$index];
              $fileType = $_FILES['fileUpload']['type'][$index];
  
              // Xử lý lỗi file
              if ($fileError === UPLOAD_ERR_OK) {
                  $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                  if (in_array($fileExtension, $Allowed_Extensions_Audio)) {
                      // Xử lý tên file
                      //$fileName = preg_replace('/[^a-zA-Z0-9.]/', '', strtolower($fileName));
                      $fileName = strtolower($fileName);
                      $filePath = $targetDirectory . basename($fileName);
  
                      // Di chuyển file vào thư mục
                      if (move_uploaded_file($fileTmpName, $filePath)) {
  						chmod($filePath, 0777);
                          $messages[] = 'Tải lên thành công: ' . $fileName;
                      } else {
                          $success = false;
                          $messages[] = 'Không thể di chuyển file: ' . $fileName;
                      }
                  } else {
                      $success = false;
                      $messages[] = 'Định dạng file không hợp lệ: ' . $fileName;
                  }
              } else {
                  $success = false;
                  $messages[] = 'Lỗi tải lên file: ' . $fileName;
              }
          }
  
          echo json_encode(['success' => $success, 'messages' => $messages]);
      } else {
          echo json_encode(['success' => false, 'message' => 'Không có file nào được chọn']);
      }
      exit();
  }
  
  
  
  if (isset($_GET['upload_Sound_Welcome'])) {
  	// Định nghĩa các định dạng file được phép tải lên
  	//$allowedExtensions = ['mp3', 'wav', 'flac'];
      // Thư mục lưu trữ file tải lên
      $targetDirectory = $VBot_Offline . 'resource/sound/welcome/';
      // Kiểm tra nếu thư mục không tồn tại thì tạo mới
      if (!file_exists($targetDirectory)) {
          mkdir($targetDirectory, 0777, true);
      }
  
      // Kiểm tra xem có file nào được tải lên không
      if (!empty($_FILES['fileUpload']['name'][0])) {
          $messages = [];
          $success = true;
  
          foreach ($_FILES['fileUpload']['name'] as $index => $fileName) {
              $fileTmpName = $_FILES['fileUpload']['tmp_name'][$index];
              $fileSize = $_FILES['fileUpload']['size'][$index];
              $fileError = $_FILES['fileUpload']['error'][$index];
              $fileType = $_FILES['fileUpload']['type'][$index];
  
              // Xử lý lỗi file
              if ($fileError === UPLOAD_ERR_OK) {
                  $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                  if (in_array($fileExtension, $Allowed_Extensions_Audio)) {
                      // Xử lý tên file
                      //$fileName = preg_replace('/[^a-zA-Z0-9.]/', '', strtolower($fileName));
                      $fileName = strtolower($fileName);
                      $filePath = $targetDirectory . basename($fileName);
  
                      // Di chuyển file vào thư mục
                      if (move_uploaded_file($fileTmpName, $filePath)) {
  						chmod($filePath, 0777);
                          $messages[] = 'Tải lên thành công: ' . $fileName;
                      } else {
                          $success = false;
                          $messages[] = 'Không thể di chuyển file: ' . $fileName;
                      }
                  } else {
                      $success = false;
                      $messages[] = 'Định dạng file không hợp lệ: ' . $fileName;
                  }
              } else {
                  $success = false;
                  $messages[] = 'Lỗi tải lên file: ' . $fileName;
              }
          }
  
          echo json_encode(['success' => $success, 'messages' => $messages]);
      } else {
          echo json_encode(['success' => false, 'message' => 'Không có file nào được chọn']);
      }
      exit();
  }
  
  #Tải lên hình ảnh avata
  
  // Kiểm tra xem yêu cầu có chứa tham số 'upload_avata' không
  if (isset($_GET['upload_avata'])) {
      // Khởi tạo phản hồi mặc định và đường dẫn thư mục lưu trữ
      $response = [
          "success" => false,
          "message" => ""
      ];
      
      // Đường dẫn thư mục lưu trữ
      $target_dir = "../../assets/img/";
      
      // Danh sách các định dạng hình ảnh hợp lệ
      $allowed_image_types = ["jpg", "png", "jpeg", "gif"];
  
      if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["fileToUpload_avata"])) {
          // Xóa tất cả các tệp có tên là 'avata_user' bất kể định dạng
          foreach (glob($target_dir . "avata_user.*") as $file_path) {
              if (file_exists($file_path)) {
                  unlink($file_path); // Xóa tệp
              }
          }
  
          // Lấy thông tin tệp từ form upload
          $imageFileType = strtolower(pathinfo($_FILES["fileToUpload_avata"]["name"], PATHINFO_EXTENSION));
  
          // Đổi tên tệp thành 'avata_user' với định dạng giữ nguyên
          $target_file = $target_dir . "avata_user." . $imageFileType;
          $uploadOk = 1;
  
          // Kiểm tra xem tệp có phải là hình ảnh không
          $check = getimagesize($_FILES["fileToUpload_avata"]["tmp_name"]);
          if ($check !== false) {
              $response["message"] = "File là hình ảnh - " . $check["mime"] . ".";
          } else {
              $response["message"] = "File không phải là hình ảnh.";
              echo json_encode($response);
              exit();
          }
  
          // Kiểm tra định dạng tệp
          if (!in_array($imageFileType, $allowed_image_types)) {
              $response["message"] = "Chỉ cho phép tải lên các tệp hình ảnh JPG, JPEG, PNG & GIF.";
              echo json_encode($response);
              exit();
          }
  
          // Kiểm tra nếu $uploadOk bằng 0 thì dừng quá trình tải lên
          if ($uploadOk == 0) {
              $response["message"] = "Xin lỗi, tệp của bạn không được tải lên.";
          } else {
              if (move_uploaded_file($_FILES["fileToUpload_avata"]["tmp_name"], $target_file)) {
                  // Thay đổi quyền của tệp vừa tải lên
                  chmod($target_file, 0777); // Thay đổi quyền thành 0777
  
                  $response["success"] = true;
                  $response["message"] = "Tệp đã được tải lên thành công với tên mới: avata_user." . $imageFileType;
              } else {
                  $response["message"] = "Xin lỗi, đã xảy ra lỗi khi tải lên tệp của bạn.";
              }
          }
  
          // Trả về phản hồi dưới dạng JSON
          echo json_encode($response);
          exit();
      }
  }
  ?>