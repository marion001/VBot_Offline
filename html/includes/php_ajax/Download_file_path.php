<?php
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
  #Facebook: https://www.facebook.com/TWFyaW9uMDAx
  include 'Configuration.php';

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
  ?>
<?php
  $file = $_GET['file'];
  if (filter_var($file, FILTER_VALIDATE_URL)) {
      $ch = curl_init($file);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_NOBODY, false);
      $response = curl_exec($ch);
      $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $header = substr($response, 0, $headerSize);
      $body = substr($response, $headerSize);
  	// Mặc định
      $contentType = 'application/octet-stream';
      $contentLength = strlen($body);
      if (preg_match('/Content-Type:\s*(\S+)/i', $header, $matches)) {
          $contentType = $matches[1];
      }
      // Thiết lập header cho tải xuống
      header('Content-Description: File Transfer');
      header('Content-Type: ' . $contentType);
      header('Content-Disposition: attachment; filename="' . basename($file) . '"');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . $contentLength);
      echo $body;
      curl_close($ch);
      exit;
  } else {
      // Xử lý đường dẫn tệp cục bộ nếu không phải URL
      if (file_exists($file)) {
          $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
          // Kiểm tra xem đuôi file có nằm trong danh sách bị cấm không
          if (in_array($fileExtension, $Restricted_Extensions)) {
              echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền tải xuống file này.']);
              http_response_code(403);
              exit;
          }
          // Thiết lập header cho quá trình tải xuống
          header('Content-Description: File Transfer');
          header('Content-Type: application/octet-stream');
          header('Content-Disposition: attachment; filename="' . basename($file) . '"');
          header('Expires: 0');
          header('Cache-Control: must-revalidate');
          header('Pragma: public');
          header('Content-Length: ' . filesize($file));
          readfile($file);
          exit;
      } else {
          echo json_encode(['status' => 'error', 'message' => 'File không tồn tại.']);
          http_response_code(404);
          exit;
      }
  }
  ?>