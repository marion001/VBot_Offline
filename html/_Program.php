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
      session_unset();
      session_destroy();
      header('Location: Login.php');
      exit;
  }
  }

  $messages = [];
  $Version_VBot_Program = null;
  $Version_VBot_Program_filePath = $VBot_Offline.'Version.json';
  
  // Tên thư mục con: Backup_Program trên GCloud
  //$backupFolderName = 'Backup_Program';
  $backupFolderName = $Config['backup_upgrade']['google_cloud_drive']['backup_folder_vbot_name'];
  
  // Các thư mục cần kiểm tra và tạo Download_Path và  Extract_Path
  $directoriessss = [$Download_Path, $Extract_Path];
  
  #Đọc nội dung file Version.json
  if (file_exists($Version_VBot_Program_filePath)) {
      $Version_VBot_Program = json_decode(file_get_contents($Version_VBot_Program_filePath), true);
  
      if (json_last_error() !== JSON_ERROR_NONE) {
          $messages[] = 'Có lỗi xảy ra khi giải mã JSON: ' . json_last_error_msg();
          $Version_VBot_Program = null; // Đặt dữ liệu thành null nếu có lỗi
      }
  } else {
      $messages[] = 'Tệp JSON không tồn tại tại đường dẫn: ' . $Version_VBot_Program_filePath;
      $Version_VBot_Program = null; // Đặt dữ liệu thành null nếu tệp không tồn tại
  }
  
  ?>
<!DOCTYPE html>
<html lang="vi">
  <?php
    include 'html_head.php';
    ?>
  <head>
    <style>
      .limited-height {
      max-height: 350px; 
      overflow-y: auto;
      padding: 10px;
      }
    </style>
    <link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css">
  </head>
  <body>
    <?php
      include 'html_header_bar.php'; 
      include 'html_sidebar.php';

      #Giới hạn file backup
      $Limit_Backup_Files = $Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files'];

      // Hàm để thay thế giá trị trong file json từ file cũ sang file mới và lưu kết quả vào tệp đích
      function replace_values_json_file($configNewPath, $configOldPath) {
      	global $messages;
          $configNewContent = file_get_contents($configNewPath);
          $configOldContent = file_get_contents($configOldPath);
          if ($configNewContent === false || $configOldContent === false) {
              $messages[] = "<font color=red>- Lỗi khi đọc nội dung JSON từ một trong các tệp <b>$configNewData</b> hoặc <b>$configOldData</b> để chuyển dữ liệu</font>";
              return false;
          }
          $configNewData = json_decode($configNewContent, true);
          $configOldData = json_decode($configOldContent, true);
          if ($configNewData === null || $configOldData === null) {
              $messages[] = "<font color=red>- Lỗi khi giải mã nội dung JSON từ một trong các tệp <b>$configNewData</b> hoặc <b>$configOldData</b> để chuyển dữ liệu</font>";
              return false;
          }
          // Mảng lưu trữ các khóa không tồn tại trong config mới
          $missingKeys = [];
          // Mảng lưu trữ các khóa có giá trị giống nhau, không thay đổi
          $unchangedKeys = [];
          // Hàm đệ quy để thay thế giá trị
          function replace_recursive(&$newData, $oldData, &$missingKeys, &$unchangedKeys, $path = '') {
      		global $messages;
              foreach ($oldData as $key => $oldValue) {
      			// Đường dẫn của khóa hiện tại
      			$currentPath = $path ? $path . '=>' . $key : $key;
                  if (array_key_exists($key, $newData)) {
                      if (is_array($oldValue) && is_array($newData[$key])) {
                          // Kiểm tra nếu cả hai mảng chỉ chứa các giá trị
                          if (array_keys($oldValue) === range(0, count($oldValue) - 1) &&
                              array_keys($newData[$key]) === range(0, count($newData[$key]) - 1)) {
                              // Thay thế toàn bộ mảng
                              if ($newData[$key] !== $oldValue) {
                                  $newData[$key] = $oldValue;
                                  #$messages[] = "<font color=green>- Đã thay thế toàn bộ mảng của: <b>'$currentPath'</b></font>";
                              } else {
                                  $unchangedKeys[] = $currentPath;
                              }
                          } else {
                              // Nếu mảng chứa cặp khóa-giá trị, đệ quy để thay thế giá trị bên trong
                              replace_recursive($newData[$key], $oldValue, $missingKeys, $unchangedKeys, $currentPath);
                          }
                      } else {
                          // Thay thế nếu không phải là mảng hoặc có giá trị khác nhau
                          if ($newData[$key] !== $oldValue) {
                              $newData[$key] = $oldValue;
      						$displayValue = is_bool($oldValue) ? ($oldValue ? 'true' : 'false') : ($oldValue === null ? 'null' : $oldValue);
                              #$messages[] = "<font color=green>- Đã thay thế giá trị của: </font> <font color=blue>'$currentPath'</font> thành: <font color=blue>$displayValue</font>";
                          } else {
                              $unchangedKeys[] = $currentPath;
                          }
                      }
                  } else {
                      $missingKeys[] = $currentPath;
                      #$messages[] = "<font color=red>- Khóa '$currentPath' không tồn tại trong dữ liệu mới</font><br/>";
                  }
              }
          }
          // Gọi hàm đệ quy để thay thế các giá trị
          replace_recursive($configNewData, $configOldData, $missingKeys, $unchangedKeys);
          // Hiển thị các khóa không thay đổi
          if (!empty($unchangedKeys)) {
              #$messages[] = "<font color=green>- Các khóa có giá trị không thay đổi:</font> <font color=blue>" . implode(', ', $unchangedKeys) . "</font><br/>";
          }
          // Hiển thị các khóa không tồn tại
          if (!empty($missingKeys)) {
             $messages[] = "<font color=red>- Các khóa không tồn tại trong dữ liệu mới: <b>" . implode(', ', $missingKeys) . "</b></font><br/>";
          }
          // Lưu dữ liệu mới vào tệp JSON
          $writeResult = file_put_contents($configNewPath, json_encode($configNewData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
          if ($writeResult === false) {
              $messages[] = "<font color=green>- Lỗi khi lưu dữ liệu vào tệp <b>'$configNewPath'</b></font><br/>";
              return false;
          }
          $messages[] = "<font color=green>- Các giá trị đã được thay thế và lưu vào tệp: <b>'$configNewPath'</b></font><br/>";
          return true;
      }
      
      #Hàm xóa thư mục và các file, thư mục con bên trong
      function deleteDirectory($dir) {
      	global $messages;
          // Kiểm tra xem thư mục có tồn tại không
          if (!is_dir($dir)) {
      		$messages[] = "<font color=red>Thư mục $dir không tồn tại để xóa dữ liệu</font>";
              return false;
          }
          // Mở thư mục
          $files = scandir($dir);
          foreach ($files as $file) {
              // Bỏ qua các thư mục hiện tại (.) và thư mục cha (..)
              if ($file != '.' && $file != '..') {
      			$filePath = rtrim($dir, '/') . '/' . $file;
                  // Nếu là thư mục, gọi đệ quy
                  if (is_dir($filePath)) {
                      deleteDirectory($filePath);
                  } else {
                      // Xóa tệp
                      unlink($filePath);
                      //$messages[] = "<font color=red>- Đã xóa tệp: </font> <font color=blue>$filePath</font>";
                  }
              }
          }
          // Cuối cùng, xóa thư mục
          rmdir($dir);
          //$messages[] = "<font color=red>- Đã xóa thư mục: </font> <font color=blue>$dir</font>";
          return true;
      }
      
      #Sao chép tệp có lựa chọn giữ lại file hoặc thư mục
      function copyFiles($source, $destination, $keepList = []) {
          global $messages;
          if (!is_dir($source)) {
              $messages[] = "<font color=red>- Thư mục nguồn '$source' không tồn tại</font>";
              return false;
          }

          // Tạo thư mục đích nếu chưa tồn tại
          if (!is_dir($destination)) {
              mkdir($destination, 0777, true);
          }
      
          // Mở thư mục nguồn
          $dir = opendir($source);
          while (($file = readdir($dir)) !== false) {
              // Bỏ qua các thư mục hiện tại (.) và thư mục cha (..)
              if ($file != '.' && $file != '..') {
                  // Đường dẫn đầy đủ của tệp hoặc thư mục
                  $srcPath = rtrim($source, '/') . '/' . $file;
                  $destPath = rtrim($destination, '/') . '/' . $file;
      
                  // Bỏ qua nếu file hoặc thư mục nằm trong danh sách cần giữ lại
                  if (in_array($file, $keepList)) {
                      $messages[] = "<font color=orange>- Bỏ qua tệp/thư mục: </font><font color=blue><b>$file</b></font>";
                      continue;
                  }
      
                  // Nếu là thư mục, gọi đệ quy
                  if (is_dir($srcPath)) {
                      copyFiles($srcPath, $destPath, $keepList);
                  } else {
                      // Sao chép tệp
                      if (copy($srcPath, $destPath)) {
                          $messages[] = "<font color=green>- Đã sao chép tệp: </font><font color=blue><b>" . basename($srcPath) . "</b></font>";
                      } else {
                          $messages[] = "<font color=red>- Không thể sao chép tệp <b>'$srcPath'</b> đến <b>'$destPath'</b></font>";
                      }
                  }
              }
          }
          closedir($dir);
          return true;
      }
      
      #Chỉ sao chép các tệp cần thiết từ thư mục nguồn sang thư mục đích
      //$files = ["Config.json", "Action.json", "Adverbs.json", "Object.json"];
      function copyFilesToDestination($sourceDir, $destinationDir, $files) {
      	global $messages;
          // Tạo thư mục đích nếu chưa tồn tại
          if (!is_dir($destinationDir)) {
              mkdir($destinationDir, 0777, true);
          }
          // Sao chép từng file trong danh sách
          foreach ($files as $file) {
              $sourceFile = $sourceDir . $file;
              $destinationFile = $destinationDir . $file;
              // Kiểm tra nếu tệp tồn tại ở thư mục nguồn
              if (file_exists($sourceFile)) {
                  copy($sourceFile, $destinationFile);
      			// Đặt quyền cho tệp đã sao chép
      			chmod($destinationFile, 0777);
                  $messages[] = "<br/><font color=green>- Đã sao chép <b>$file</b> thành công vào bộ nhớ tạm để chuẩn bị di chuyển dữ liệu</font><br>";
              } else {
                  $messages[] = "<font color=red>- Không tìm thấy tệp <b>$file</b> trong thư mục nguồn: <b>$sourceDir</b></font><br>";
              }
          }
      }
      
      // Hàm xóa thư mục và nội dung bên trong chỉ dùng cho lúc cập nhật, không để Logs
      //Chỉ dùng cho cập nhật
      function deleteDir($dirPath) {
          if (!is_dir($dirPath)) return;
          $files = scandir($dirPath);
          foreach ($files as $file) {
              if ($file == '.' || $file == '..') continue;
              $filePath = $dirPath . "/" . $file;
              if (is_dir($filePath)) {
                  deleteDir($filePath);
              } else {
                  unlink($filePath);
              }
          }
          rmdir($dirPath);
      }
      
      #Xóa nội dungtrong thư mục, không xóa thư mục cha
      function delete_in_Dir($dirPath) {
          if (!is_dir($dirPath)) return;
          $files = scandir($dirPath);
          foreach ($files as $file) {
              if ($file == '.' || $file == '..') continue;
              $filePath = $dirPath . "/" . $file;
              if (is_dir($filePath)) {
                  delete_in_Dir($filePath);
              } else {
                  unlink($filePath);
              }
          }
          //rmdir($dirPath);
      }
      
      #Tạo Thư mục
      function createDirectory($directory) {
      	global $messages;
          if (!is_dir($directory)) {
              if (mkdir($directory, 0777, true)) {
      			chmod($directory, 0777);
                  $messages[] = "<font color=green>- Thư mục <b>'$directory'</b> đã được tạo thành công và quyền truy cập đã được đặt là 0777</font>";
              } else {
                  $messages[] = "<font color=red>- Không thể tạo thư mục <b>'$directory'</b></font>";
              }
          }
      }
      
      
      #Giải nén tệp .tar.gz
      function extractTarGz($tarFilePath, $extractTo) {
      	global $messages;
          if (!file_exists($tarFilePath)) {
      		$messages[] = "<font color=red>- Tệp Sao Lưu: <b>'$tarFilePath'</b> không tồn tại</font>";
              return false;
          }
          // Thực hiện lệnh giải nén
          $command = "tar -xzf " . escapeshellarg($tarFilePath) . " -C " . escapeshellarg($extractTo);
          exec($command, $output, $returnVar);
          // Kiểm tra kết quả
          if ($returnVar === 0) {
              return true;
          } else {
              return false;
          }
      }
      
      #Tải xuống repo git, không dùng lệnh git clone
      function downloadGitRepoAsNamedZip($repoUrl, $destinationDir) {
      	global $messages;
      	$messages[] = "<font color=green>- Đang tiến hành tải xuống bản cập nhật...</font>";
          // Lấy tên repository từ URL
          $repoName = basename(parse_url($repoUrl, PHP_URL_PATH));
          $zipFile = $destinationDir . "/" . $repoName . ".zip";
          $zipUrl = rtrim($repoUrl, '/') . "/archive/refs/heads/main.zip";
          // Tạo thư mục đích nếu chưa tồn tại
          if (!is_dir($destinationDir)) {
              mkdir($destinationDir, 0777, true);
          }
      	
          // Tải tệp ZIP về và lưu với tên mới
          file_put_contents($zipFile, fopen($zipUrl, 'r'));
      	chmod($zipFile, 0777);
          // Giải nén tệp ZIP
          $zip = new ZipArchive;
      	$messages[] = "<font color=green>- Tải xuống thành công, đang tiến hành giải nén dữ liệu...</font>";
          if ($zip->open($zipFile) === TRUE) {
      		//Tên  Thư mục giải nén sẽ có dạng repo-main
              $extractedFolder = $destinationDir . "/" . $repoName . "-main";
              $zip->extractTo($destinationDir);
              $zip->close();
              // Xóa tệp ZIP sau khi giải nén
              unlink($zipFile);
      		
              // Xóa thư mục html bên trong thư mục đã giải nén, nếu tồn tại
              $htmlFolder = $extractedFolder . "/html";
              if (is_dir($htmlFolder)) {
                  deleteDir($htmlFolder);
              }
              chmod($extractedFolder, 0777);
      		//$messages[] = "Giải nén dữ liệu thành công, tiến hành nâng cấp...";
              return $extractedFolder;
          } else {
              $messages[] = "Có Lỗi Xảy Ra, không thể giải nén được giữ liệu đã tải xuống, đã dừng tiến trình";
              return null;
          }
      }
      
      #function Backup Chương trình Vbot
      function backup_data($Exclude_Files_Folder, $Exclude_File_Format){
      
      global $VBot_Offline, $Config, $messages, $HTML_VBot_Offline, $Limit_Backup_Files, $Backup_Dir_Save_VBot, $Version_VBot_Program;
      
      // Kiểm tra nếu thư mục chưa tồn tại
      if (!is_dir($Backup_Dir_Save_VBot)) {
          // Tạo thư mục với quyền 0777
          if (mkdir($Backup_Dir_Save_VBot, 0777, true)) {
              $messages[] = "<font color=blue>- Thư mục đã được tạo: <b>$Backup_Dir_Save_VBot</b></font>";
              chmod($Backup_Dir_Save_VBot, 0777);
          } else {
              $messages[] = "<font color=red>- Lỗi, Không thể tạo thư mục: <b>$Backup_Dir_Save_VBot</b></font>";
      		return null;
          }
      }
      
      //Chuyển dấu / thành dấu - ở file Version.json
      $Version_VBot_Program_releaseDate = str_replace('/', '-', $Version_VBot_Program['releaseDate']);
      $Version_VBot_Program_version = str_replace('/', '-', $Version_VBot_Program['version']);

      # Tên file Backup
      $Backup_File_Name = $Backup_Dir_Save_VBot . '/VBot_Program_' . date('dmY_His') . '_'.$Version_VBot_Program_releaseDate.'_'.$Version_VBot_Program_version.'.tar.gz';
      
      // Tạo lệnh để nén thư mục
      $tarCommand = "tar -czvf " . escapeshellarg($Backup_File_Name) . " -C " . escapeshellarg($VBot_Offline);
      // Thêm các tùy chọn bỏ qua cho từng file trong mảng
      foreach ($Exclude_Files_Folder as $item) {
          $tarCommand .= " --exclude=" . escapeshellarg($item);
      }
      // Thêm các đuôi file cần loại bỏ vào lệnh tar
      foreach ($Exclude_File_Format as $ext) {
          // Tạo một lệnh exclude cho đuôi file
          $tarCommand .= " --exclude=*" . escapeshellarg($ext);
      }
      
      // Thêm tên thư mục cần nén (dùng dấu chấm để nén toàn bộ nội dung thư mục)
      $tarCommand .= " . --warning=all 2>&1";
      // Thực thi lệnh tar
      exec($tarCommand, $output, $returnCode);
      // Kiểm tra kết quả
      if ($returnCode === 0) {
          chmod($Backup_File_Name, 0777);
          $messages[] = "<font color=green>- Tạo bản sao lưu chương trình Vbot thành công:</font> <font color=blue><a title='Tải Xuống file backup: ".basename($Backup_File_Name)."' onclick=\"downloadFile('".$HTML_VBot_Offline."/".$Backup_File_Name."')\">".basename($Backup_File_Name)."</a></font> <a title='Tải Xuống file backup: ".basename($Backup_File_Name)."' onclick=\"downloadFile('".$HTML_VBot_Offline."/".$Backup_File_Name."')\"><font color=green>Tải Xuống</font></a>";
      /*
          // Hiển thị các file và thư mục đã nén
          $messages[] = "<br/>- Các file và thư mục đã được sao lưu và đóng gói vào tệp <b>".basename($Backup_File_Name)."</b>";
          foreach ($output as $line) {
              $messages[] = "<font color=blue>".$line."</font>";
          }
          // Kiểm tra và hiển thị các file và thư mục bị bỏ qua
          $messages[] = "<br/><font color=red><b>- Các file và thư mục không được sao lưu:</b></font>";
          foreach ($Exclude_Files_Folder as $item) {
              $messages[] = "<font color=red>- Thư mục: <b>".$item."</b> không được sao lưu</font>";
          }
          foreach ($Exclude_File_Format as $ext) {
              $messages[] = "<font color=red>- Tệp có phần mở rộng: <b>'$ext'</b> không được sao lưu</font>";
          }
      */
          // Xóa các file cũ nếu số lượng tệp tin sao lưu vượt quá giới hạn
          $Backup_File_Names = glob($Backup_Dir_Save_VBot . '/*.tar.gz');
          $numBackupFiles = count($Backup_File_Names);
          if ($numBackupFiles > $Limit_Backup_Files) {
              // Sắp xếp tệp tin sao lưu theo thời gian tăng dần
              usort($Backup_File_Names, function ($a, $b) {
                  return filemtime($a) - filemtime($b);
              });
              // Xóa các tệp tin cũ nhất cho đến khi số lượng tệp tin sao lưu đạt đến giới hạn
              $filesToDelete = array_slice($Backup_File_Names, 0, $numBackupFiles - $Limit_Backup_Files);
              foreach ($filesToDelete as $file) {
                  unlink($file);
      			$messages[] = "<br/>- Số lượng tệp tin sao lưu trên hệ thống vượt quá giới hạn là: <b>$Limit_Backup_Files</b>, đã xóa file cũ nhất: <font color=red><b>".basename($file)."</b></font>";
              }
          }
      	return $Backup_File_Name;
      } else {
          $messages[] = '<br/></font color=red>- Lỗi khi nén thư mục. Mã lỗi: ' . $returnCode.'</font>';
      	// In chi tiết thông báo lỗi (nếu có)
          $messages[] = $output;
      	return null;
      }
      #End Function
      }
      
      #Biến toàn cục $libPath_exist kiểm tra xem thư viện google Cloud Drive có tồn tại hay không
      $libPath_exist = false;
      if ($google_cloud_drive_active === true) {
      $libPath = '/home/'.$GET_current_USER.'/_VBot_Library/google-api-php-client/vendor/autoload.php';
      // Kiểm tra xem thư viện có tồn tại không
      if (file_exists($libPath)) {
      	#Thêm thư viện
          require $libPath;
      	$libPath_exist = true;
      } else {
      	$libPath_exist = false;
          $messages[] = "<font color=red>Thư viện <b>Google API PHP Client</b> chưa được cấu hình</font>";
          $messages[] = "<br/><font color=red><a href='GCloud_Drive.php'>Nhấn Vào Đây Để Tới Trang  <button class='btn btn-primary rounded-pill' type='button'>Cấu Hình</button></a></font>";
      }
      }
      
      use Google\Client;
      use Google\Service\Drive;
      use Google\Service\Drive\DriveFile; 
      
      #Nếu có thư viện tồn tại là true, kiểm tra tiếp
      if ($libPath_exist === true){
      // Đường dẫn đến file lưu trữ token 
      $authConfigPath = 'includes/other_data/Google_Driver_PHP/client_secret.json';
      // Đường dẫn đến tệp xác thực JSON
      $tokenPath = 'includes/other_data/Google_Driver_PHP/verify_token.json';
      
      // Kiểm tra và lấy token từ tệp
      $accessToken = json_decode(file_get_contents($tokenPath), true);
      
      // Kiểm tra xem token có đầy đủ các trường cần thiết không
      if (!isset($accessToken['access_token'])) {
      	$libPath_exist = false;
      	$messages[] = "<font color=red>Google Cloud Drive chưa được cấu hình Để Lấy Mã Truy Cập Xác Thực Token, hãy nhấn vào nút bên dưới để chuyển sang tab Cấu Hình</font>";
      	$messages[] = '<br/><a href="GCloud_Drive.php"><button type="button" class="btn btn-primary rounded-pill">Cấu Hình Google Cloud Drive</button></a>';
      }else {
      	$libPath_exist = true;
      	// Khởi tạo client
      	$client = new Client();
      	$client->setAuthConfig($authConfigPath);
      	$client->addScope(Drive::DRIVE_FILE);
      	$client->setAccessToken($accessToken);
      // Nếu token đã hết hạn, lấy token mới
      if ($client->isAccessTokenExpired()) {
          if ($client->getRefreshToken()) {
              $token = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
              if (isset($token['access_token'])) {
      		// Cập nhật access token và refresh token vào verify_token.json
      		file_put_contents($tokenPath, json_encode($token, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      		$libPath_exist = true;
      		$messages[] = '<font color=green>- Tự động làm mới và cập nhật Token Google Cloud Drive Thành Công</font>';
      		}else {
      			$libPath_exist = false;
      			$messages[] = '<font color=green>- Xảy ra lỗi, Token Làm Mới Không Tồn Tại Để Xác Thực</font>';
      			$messages[] = '<br/><a href="GCloud_Drive.php"><button type="button" class="btn btn-primary rounded-pill">Cấu Hình Google Cloud Drive</button></a>';
      		}
          } else {
      		$libPath_exist = false;
              // Nếu không có refresh token, yêu cầu người dùng xác thực lại
      		$messages[] = "<font color=red>- Không có Token Làm Mới, Hãy kiểm tra lại cấu hình Google Cloud Drive</font>";
      		$messages[] = '<br/><a href="GCloud_Drive.php"><button type="button" class="btn btn-primary rounded-pill">Cấu Hình Google Cloud Drive</button></a>';
          }
      }
      }
      }
      
      #Sao Lưu chương trình VBot
      if (isset($_POST['Backup_Upgrade_Program'])) {
      	//global $messages;
      // Đặt lại mảng $messages để loại bỏ các thông báo cũ
      $messages = [];
      $Backup_Upgrade_Program = $_POST['Backup_Upgrade_Program'];
      $Exclude_Files_Folder = isset($_POST['exclude_files_folder']) ? $_POST['exclude_files_folder'] : [];
      $Exclude_File_Format = isset($_POST['exclude_file_format']) ? $_POST['exclude_file_format'] : [];
      $Backup_To_Cloud = $_POST['vbot_program_cloud_backup'];
      
      // Kiểm tra và tạo từng thư mục
      foreach ($directoriessss as $directory) {
          createDirectory($directory);
      }
      
      #Cấu hình kết nối SSH:
      $connection = ssh2_connect($ssh_host, $ssh_port);
      ssh2_auth_password($connection, $ssh_user, $ssh_password);
      
      //Kiểm tra xem nút nhấn nào được submit
      if ($Backup_Upgrade_Program === "upload_and_restore"){
      	$messages[] =  "<font color=green>- Đang tiến hành tải lên bản khôi phục dữ liệu</font>";
          $uploadOk = 1;
          // Kiểm tra xem tệp có được gửi không
          if (isset($_FILES["fileToUpload"])) {
              $targetFile = $Download_Path . '/' . basename($_FILES["fileToUpload"]["name"]);
              $fileName = basename($_FILES["fileToUpload"]["name"]);
              // Kiểm tra xem tệp có phải là .tar.gz không
      		if (!preg_match('/\.tar\.gz$/', $fileName) || !preg_match('/^VBot_Program/', $fileName)) {
      		$messages[] = "<font color=red>- Chỉ chấp nhận tệp .tar.gz, dành cho VBot_Program, và được Giao Diện tạo ra bản sao lưu đó</font>";
      		$uploadOk = 0;
      		}
              // Kiểm tra xem $uploadOk có bằng 0 không
              if ($uploadOk == 0) {
      				deleteDirectory($Extract_Path);
      				deleteDirectory($Download_Path);
                  $messages[] = "<font color=red>- Tệp sao lưu không được tải lên</font>";
              } else {
                  // Di chuyển tệp vào thư mục đích
                  if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
                      $messages[] = "<font color=green>- Tệp <b>" . htmlspecialchars($fileName) . "</b> đã được tải lên thành công</font>";
      	// Gọi hàm để giải nén
      	if (extractTarGz($directory_path.'/'.$targetFile, $Extract_Path)) {
      		$Extract_Path_OK = $directory_path.'/'.$Extract_Path.'/';
      		$messages[] = "<font color=green>- Giải nén thành công vào đường dẫn: <b>$Extract_Path/</b> </font><br/>";
      // Gọi hàm để sao chép các tệp
      if (copyFiles($Extract_Path_OK, $VBot_Offline)) {
          $messages[] = "<font color=green><b>- Sao chép toàn bộ tệp và thư mục thành công!</b></font><br/>";
      	deleteDirectory($Extract_Path);
      	deleteDirectory($Download_Path);
      	$messages[] = "<br/><font color=green><b>- Đã hoàn tất khôi phục dữ liệu từ bản sao lưu: ".$fileName."</b></font>";
      	$messages[] = "<br/><font color=green><b>- Bạn cần khởi động lại chương trình Vbot để áp dụng các thay đổi từ bản sao lưu</b></font>";
      } else {
          $messages[] = "<font color=red>- Sao chép tệp thất bại</font>";
      }
      	} else {
          $messages[] = "<font color=red>- Lỗi khi giải nén tệp</font>";
      	}	
                  } else {
                      $messages[] = "<font color=red>- Có lỗi xảy ra khi tải lên tệp sao lưu của bạn</font>";
                  }
              }
          } else {
      			deleteDirectory($Extract_Path);
      			deleteDirectory($Download_Path);
              $messages[] = "<font color=red>- Không có tệp sao lưu nào được tải lên</font>";
          }
      	
      }
      
      elseif ($Backup_Upgrade_Program === "yes_vbot_upgrade" || $Backup_Upgrade_Program === "no_vbot_upgrade") {
      
      #Xử lý dữ liệu nếu nút nhấn là chỉ tạo file backup
      if ($Backup_Upgrade_Program === "no_vbot_upgrade"){
      
      $messages[] =  "- Đang tiến hành sao lưu dữ liệu";
      
      //Sao lưu dữ liệu trước khi cập nhật hoặc sao lưu
      $FileName_Backup_VBot = backup_data($Exclude_Files_Folder, $Exclude_File_Format);
      if (!is_null($FileName_Backup_VBot)) {
      $messages[] = "<font color=green>- Hoàn thành Sao Lưu Chương Trình Vbot Trên Hệ Thống: <b>" .$FileName_Backup_VBot."</b></font>";
      #Liên quan tới Sao lưu lên cloud drive
      #Nếu Kích hoạt sao lưu dữ liệu lên Google Drive
      if ($Backup_To_Cloud === "gdrive"){
      
      if ($google_cloud_drive_active === true){
      
      
      if ($libPath_exist === true) {
      $messages[] = '<br/><font color=blue>- Tiến Hành Sao Lưu Dữ Liệu Lên Google Cloud Drive</font>';
      
      // Khởi tạo client
      $client = new Client();
      $client->setAuthConfig($authConfigPath);
      $client->addScope(Drive::DRIVE_FILE);
      $client->setAccessToken($accessToken);
      // Nếu token đã hết hạn, lấy token mới
      if ($client->isAccessTokenExpired()) {
          if ($client->getRefreshToken()) {
              $token = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
              if (isset($token['access_token'])) {
      		// Cập nhật access token và refresh token vào verify_token.json
      		file_put_contents($tokenPath, json_encode($token, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      		$libPath_exist = true;
      		$messages[] = '<font color=green>- Tự động làm mới và cập nhật Token Google Cloud Drive Thành Công</font>';
      		}else {
      			$libPath_exist = false;
      			$messages[] = '<font color=green>- Xảy ra lỗi, Token Làm Mới Không Tồn Tại Để Xác Thực</font>';
      		}
          } else {
      		$libPath_exist = false;
              // Nếu không có refresh token, yêu cầu người dùng xác thực lại
      		$messages[] = "<font color=red>- Không có Token Làm Mới, Hãy kiểm tra lại cấu hình Google Cloud Drive</font>";
          }
      }
      
      // Xác thực và tạo dịch vụ Drive
      $service = new Drive($client);
      // Tên thư mục cần kiểm tra hoặc tạo
      $folderName = $Config['backup_upgrade']['google_cloud_drive']['backup_folder_name'];
      
      
      //Khởi tạo để cấp quyền cho thư mục nếu được tạo
      $permission = new Google\Service\Drive\Permission();
      $permission->setType('anyone');
      $permission->setRole('reader');
      
      // Kiểm tra thư mục chính có tồn tại không
      $query = "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false";
      $response = $service->files->listFiles(array(
          'q' => $query,
          'fields' => 'files(id, name)'
      ));
      
      if (count($response->files) > 0) {
      	// Lấy ID của thư mục chính
          $folderId = $response->files[0]->id;
          //$messages[] = "Thư mục đã tồn tại với ID: " . $folderId . "\n";
      
          // Kiểm tra thư mục Backup_Program bên trong
          $backupQuery = "mimeType='application/vnd.google-apps.folder' and name='$backupFolderName' and trashed=false and '$folderId' in parents";
          $backupResponse = $service->files->listFiles(array(
              'q' => $backupQuery,
              'fields' => 'files(id, name)'
          ));
      
          if (count($backupResponse->files) > 0) {
              $backupFolderId = $backupResponse->files[0]->id;
              //$messages[] = "Thư mục $backupFolderName đã tồn tại bên trong thư mục $folderName với ID: " . $backupFolderId . ".\n";
          } else {
              // Nếu không tồn tại, tạo thư mục Backup_Program
              $backupFolderMetadata = new DriveFile(array(
                  'name' => $backupFolderName,
                  'mimeType' => 'application/vnd.google-apps.folder', // Định nghĩa loại MIME cho thư mục
                  'parents' => array($folderId) // Đặt thư mục cha
              ));
              $backupFolder = $service->files->create($backupFolderMetadata, array(
                  'fields' => 'id'
              ));
              $backupFolderId = $backupFolder->id;
              $messages[] = "<font color=green>- Thư mục $backupFolderName đã được tạo với ID: " . $backupFolderId . "</font>";
          //Cấp quyền công khai cho thư mục vừa tạo
          $service->permissions->create(
      		// ID của thư mục vừa tạo
              $backupFolderId,
              $permission,
              ['fields' => 'id']
          );
          $messages[] = "<font color=green>- Quyền truy cập công khai đã được cấp cho thư mục <b>$backupFolderName</b></font>";
          }
      } else {
          // Nếu không tồn tại, tạo thư mục chính
          $folderMetadata = new DriveFile(array(
              'name' => $folderName,
              'mimeType' => 'application/vnd.google-apps.folder'
          ));
          $folder = $service->files->create($folderMetadata, array(
              'fields' => 'id'
          ));
          $folderId = $folder->id;
          $messages[] = "<font color=green>- Thư mục <b>".$folderName."</b> đã được tạo với ID: " . $folderId . "</font>";
          //Cấp quyền công khai cho thư mục vừa tạo
          $service->permissions->create(
      		// ID của thư mục vừa tạo
              $folderId,
              $permission,
              ['fields' => 'id']
          );
          $messages[] = "<font color=green>- Quyền truy cập công khai đã được cấp cho thư mục <b>$folderName</b></font>";
          // Tạo thư mục Backup_Program bên trong
          $backupFolderMetadata = new DriveFile(array(
              'name' => $backupFolderName,
              'mimeType' => 'application/vnd.google-apps.folder',
      		// Đặt thư mục cha là thư mục vừa tạo
              'parents' => array($folderId)
          ));
          $backupFolder = $service->files->create($backupFolderMetadata, array(
              'fields' => 'id'
          ));
          $backupFolderId = $backupFolder->id;
          $messages[] = "<br/><font color=green>- Thư mục con: <b>$backupFolderName</b> đã được tạo bên trong thư mục <b>$folderName</b> với ID: " . $backupFolderId . "</font>";
          //Cấp quyền công khai cho thư mục vừa tạo
          $service->permissions->create(
      		// ID của thư mục vừa tạo
              $backupFolderId,
              $permission,
              ['fields' => 'id']
          );
          $messages[] = "<font color=green>- Quyền truy cập công khai đã được cấp cho thư mục <b>$backupFolderName</b></font>";
      }
      
      
      // Kiểm tra số lượng tệp trong thư mục Backup_Program
      $fileQuery = "mimeType != 'application/vnd.google-apps.folder' and '$backupFolderId' in parents and trashed = false";
      $fileResponse = $service->files->listFiles(array(
          'q' => $fileQuery,
          'fields' => 'files(id, name, createdTime)',
      ));
      $fileCount = count($fileResponse->files);
      $messages[] = "<font color=green>- Số tệp hiện tại trên Google Drive <b>$backupFolderName: $fileCount</b></font>";
      
      if ($fileCount >= $Config['backup_upgrade']['google_cloud_drive']['limit_backup_files']) {
          // Nếu có 5 tệp, xóa tệp cũ nhất
      $messages[] = "<br/><font color=red>- Số lượng tệp tin sao lưu trên Google Drive vượt quá: <b>$Limit_Backup_Files</b> file</font>";
          $oldestFile = null;
          foreach ($fileResponse->files as $file) {
              if ($oldestFile === null || strtotime($file->createdTime) < strtotime($oldestFile->createdTime)) {
                  $oldestFile = $file;
              }
          }
          if ($oldestFile) {
              $service->files->delete($oldestFile->id);
              $messages[] = "<font color=red>- Đã xóa tệp cũ nhất: <b>" . $oldestFile->name . " với ID: " . $oldestFile->id . "</b></font>";
          }
      }
      
      // Lấy tên tệp từ đường dẫn
      $fileName = basename($FileName_Backup_VBot);
      $fileMetadata = new DriveFile(array(
          'name' => $fileName,
      	// Đặt thư mục cha là thư mục Backup_Program
          'parents' => array($backupFolderId)
      ));
      // Tải tệp lên
      try {
      	// Đọc nội dung tệp
          $content = file_get_contents($FileName_Backup_VBot); 
          $file = $service->files->create($fileMetadata, array(
              'data' => $content,
              'mimeType' => mime_content_type($FileName_Backup_VBot),
              'uploadType' => 'multipart',
              'fields' => 'id'
          ));
          $messages[] = "<br/><font color=green>- Tệp <b>".$fileName."</b> đã được tải lên với ID: <b>" . $file->id . "</b></font>";
          // Đặt quyền cho tệp để mọi người đều có thể xem
          $permission = new \Google\Service\Drive\Permission(array(
              'role' => 'reader',
              'type' => 'anyone',
          ));
          $service->permissions->create($file->id, $permission);
          $messages[] = "<font color=green>- Quyền công khai đã được thiết lập cho tệp: <b>".$fileName."</b> ai có liên kết cũng có thể xem và tải xuống tệp</font>";
          $messages[] = "<br/><font color=green>- Google Cloud Drive: <a href='https://drive.google.com/file/d/".$file->id."/view?usp=drive_link' target='_bank' title='Xem, Tải xuống file ".$fileName."'><b>Tải Xuống File ".$fileName."</b></a></font>";
      } catch (Exception $e) {
          $messages[] = '<font color=red>- Có lỗi xảy ra khi tải tệp lên: ' . $e->getMessage().'</font>';
      }
      }else {
      	$messages[] =  "<br/><font color=red>- <b>Google Cloud Drive chưa được cấu hình, sẽ không có tệp sao lưu nào được tải lên</b></font>";
      }
      }else{
      	$messages[] = "<font color=red>- <b>Cloud Backup -> Google Cloud Drive Không được Kích Hoạt Trong Config.json (backup_upgrade->google_cloud_drive->active), Sẽ không có file Backup nào được tải lên Google Cloud Drive</font>";
      }
      	}else {
      	$messages[] =  "<font color=red>- <b>Tải dữ liệu Backup Lên Google Cloud Drive không được kích hoạt</b></font>";
      	}
      } else {
          $messages[] =  "<font color=red>- <b>Lỗi: Không thấy tệp sao lưu để tải lên Google Drive</b></font>";
      }
      }
      //Xử lý nếu dữ liệu là nút nhấn cập nhật
      elseif($Backup_Upgrade_Program === "yes_vbot_upgrade"){
      	delete_in_Dir($Download_Path);
      	$vbot_program_cloud_backup_khi_cap_nhat = isset($_POST['vbot_program_cloud_backup_khi_cap_nhat']) ? $_POST['vbot_program_cloud_backup_khi_cap_nhat'] : null;
      	#Các file và thư mục cần bỏ qua không cho cập nhật, ghi đè
      	$Keep_The_File_Folder_POST = isset($_POST['keep_the_file_folder']) ? $_POST['keep_the_file_folder'] : [];
      	$messages[] = "<font color=green><b>- Đang tiến hành cập nhật phiên bản chương trình Vbot mới</b></font><br/>";
      	#lựa chọn có tạo bản sao lưu trước khi cập nhật không
      	$make_a_backup_before_updating = isset($_POST['make_a_backup_before_updating']) ? true : false;
      	
      #Xử lý tải xuống bản cập nhật
      $download_Git_Repo_As_Named_Zip = downloadGitRepoAsNamedZip($Github_Repo_Vbot, $Download_Path);
      
      if (!is_null($download_Git_Repo_As_Named_Zip)) {
      $messages[] = "<font color=green>- Tải dữ liệu và giải nén thành công vào đường dẫn: <b>".$download_Git_Repo_As_Named_Zip."/</b></font><br/>";
      #Bắt đầu sao lưu dữ liệu trước khi ghi đè, xử lý dữ liệu mới
      if ($make_a_backup_before_updating === true){
      $messages[] = "- Đang tiến hành sao lưu dữ liệu trước khi cập nhật...";
      //Sao lưu dữ liệu trước khi cập nhật hoặc sao lưu
      $FileName_Backup_VBot = backup_data($Exclude_Files_Folder, $Exclude_File_Format);
      if (!is_null($FileName_Backup_VBot)) {
      $messages[] = "<font color=green>- Hoàn thành Sao Lưu Chương Trình Vbot Trên Hệ Thống: <b>" .$FileName_Backup_VBot."</b></font>";
      #nếu sao lưu cloud được bật sẽ tải bản sao lưu lên drive
      if ($vbot_program_cloud_backup_khi_cap_nhat === "gdrive"){
      if ($google_cloud_drive_active === true){
      if ($libPath_exist === true) {
      $messages[] = '<br/><font color=blue>- Tiến Hành Sao Lưu Dữ Liệu Lên Google Cloud Drive</font>';
      // Khởi tạo client
      $client = new Client();
      $client->setAuthConfig($authConfigPath);
      $client->addScope(Drive::DRIVE_FILE);
      $client->setAccessToken($accessToken);
      // Nếu token đã hết hạn, lấy token mới
      if ($client->isAccessTokenExpired()) {
          if ($client->getRefreshToken()) {
              $token = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
              if (isset($token['access_token'])) {
      		// Cập nhật access token và refresh token vào verify_token.json
      		file_put_contents($tokenPath, json_encode($token, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      		$libPath_exist = true;
      		$messages[] = '<font color=green>- Tự động làm mới và cập nhật Token Google Cloud Drive Thành Công</font>';
      		}else {
      			$libPath_exist = false;
      			$messages[] = '<font color=green>- Xảy ra lỗi, Token Làm Mới Không Tồn Tại Để Xác Thực</font>';
      		}
          } else {
      		$libPath_exist = false;
              // Nếu không có refresh token, yêu cầu người dùng xác thực lại
      		$messages[] = "<font color=red>- Không có Token Làm Mới, Hãy kiểm tra lại cấu hình Google Cloud Drive</font>";
          }
      }
      // Xác thực và tạo dịch vụ Drive
      $service = new Drive($client);
      // Tên thư mục cần kiểm tra hoặc tạo
      $folderName = $Config['backup_upgrade']['google_cloud_drive']['backup_folder_name'];
      //Khởi tạo để cấp quyền cho thư mục nếu được tạo
      $permission = new Google\Service\Drive\Permission();
      $permission->setType('anyone');
      $permission->setRole('reader');
      // Kiểm tra thư mục chính có tồn tại không
      $query = "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false";
      $response = $service->files->listFiles(array(
          'q' => $query,
          'fields' => 'files(id, name)'
      ));
      if (count($response->files) > 0) {
      	// Lấy ID của thư mục chính
          $folderId = $response->files[0]->id;
          //$messages[] = "Thư mục đã tồn tại với ID: " . $folderId . "\n";
      
          // Kiểm tra thư mục Backup_Program bên trong
          $backupQuery = "mimeType='application/vnd.google-apps.folder' and name='$backupFolderName' and trashed=false and '$folderId' in parents";
          $backupResponse = $service->files->listFiles(array(
              'q' => $backupQuery,
              'fields' => 'files(id, name)'
          ));
          if (count($backupResponse->files) > 0) {
              $backupFolderId = $backupResponse->files[0]->id;
              //$messages[] = "Thư mục $backupFolderName đã tồn tại bên trong thư mục $folderName với ID: " . $backupFolderId . ".\n";
          } else {
              // Nếu không tồn tại, tạo thư mục Backup_Program
              $backupFolderMetadata = new DriveFile(array(
                  'name' => $backupFolderName,
                  'mimeType' => 'application/vnd.google-apps.folder', // Định nghĩa loại MIME cho thư mục
                  'parents' => array($folderId) // Đặt thư mục cha
              ));
              $backupFolder = $service->files->create($backupFolderMetadata, array(
                  'fields' => 'id'
              ));
              $backupFolderId = $backupFolder->id;
              $messages[] = "<font color=green>- Thư mục $backupFolderName đã được tạo với ID: " . $backupFolderId . "</font>";
          //Cấp quyền công khai cho thư mục vừa tạo
          $service->permissions->create(
      		// ID của thư mục vừa tạo
              $backupFolderId,
              $permission,
              ['fields' => 'id']
          );
          $messages[] = "<font color=green>- Quyền truy cập công khai đã được cấp cho thư mục <b>$backupFolderName</b></font>";
          }
      } else {
          // Nếu không tồn tại, tạo thư mục chính
          $folderMetadata = new DriveFile(array(
              'name' => $folderName,
              'mimeType' => 'application/vnd.google-apps.folder' // Định nghĩa loại MIME cho thư mục
          ));
          $folder = $service->files->create($folderMetadata, array(
              'fields' => 'id'
          ));
          $folderId = $folder->id;
          $messages[] = "<font color=green>- Thư mục <b>".$folderName."</b> đã được tạo với ID: " . $folderId . "</font>";
          //Cấp quyền công khai cho thư mục vừa tạo
          $service->permissions->create(
      		// ID của thư mục vừa tạo
              $folderId,
              $permission,
              ['fields' => 'id']
          );
          $messages[] = "<font color=green>- Quyền truy cập công khai đã được cấp cho thư mục <b>$folderName</b></font>";
          // Tạo thư mục Backup_Program bên trong
          $backupFolderMetadata = new DriveFile(array(
              'name' => $backupFolderName,
              'mimeType' => 'application/vnd.google-apps.folder',
      		// Đặt thư mục cha là thư mục vừa tạo
              'parents' => array($folderId)
          ));
          $backupFolder = $service->files->create($backupFolderMetadata, array(
              'fields' => 'id'
          ));
          $backupFolderId = $backupFolder->id;
          $messages[] = "<br/><font color=green>- Thư mục con: <b>$backupFolderName</b> đã được tạo bên trong thư mục <b>$folderName</b> với ID: " . $backupFolderId . "</font>";
          //Cấp quyền công khai cho thư mục vừa tạo
          $service->permissions->create(
      		// ID của thư mục vừa tạo
              $backupFolderId,
              $permission,
              ['fields' => 'id']
          );
          $messages[] = "<font color=green>- Quyền truy cập công khai đã được cấp cho thư mục <b>$backupFolderName</b></font>";
      }
      // Kiểm tra số lượng tệp trong thư mục Backup_Program
      $fileQuery = "mimeType != 'application/vnd.google-apps.folder' and '$backupFolderId' in parents and trashed = false";
      $fileResponse = $service->files->listFiles(array(
          'q' => $fileQuery,
          'fields' => 'files(id, name, createdTime)',
      ));
      $fileCount = count($fileResponse->files);
      $messages[] = "<font color=green>- Số tệp hiện tại trên Google Drive <b>$backupFolderName: $fileCount</b></font>";
      if ($fileCount >= $Config['backup_upgrade']['google_cloud_drive']['limit_backup_files']) {
          // Nếu có 5 tệp, xóa tệp cũ nhất
      $messages[] = "<br/><font color=red>- Số lượng tệp tin sao lưu trên Google Drive vượt quá: <b>$Limit_Backup_Files</b> file</font>";
          $oldestFile = null;
          foreach ($fileResponse->files as $file) {
              if ($oldestFile === null || strtotime($file->createdTime) < strtotime($oldestFile->createdTime)) {
                  $oldestFile = $file;
              }
          }
          if ($oldestFile) {
              $service->files->delete($oldestFile->id);
              $messages[] = "<font color=red>- Đã xóa tệp cũ nhất: <b>" . $oldestFile->name . " với ID: " . $oldestFile->id . "</b></font>";
          }
      }
      // Lấy tên tệp từ đường dẫn
      $fileName = basename($FileName_Backup_VBot);
      $fileMetadata = new DriveFile(array(
          'name' => $fileName,
      	// Đặt thư mục cha là thư mục Backup_Program
          'parents' => array($backupFolderId)
      ));
      // Tải tệp lên
      try {
      	// Đọc nội dung tệp
          $content = file_get_contents($FileName_Backup_VBot); 
          $file = $service->files->create($fileMetadata, array(
              'data' => $content,
              'mimeType' => mime_content_type($FileName_Backup_VBot),
              'uploadType' => 'multipart',
              'fields' => 'id'
          ));
          $messages[] = "<br/><font color=green>- Tệp <b>".$fileName."</b> đã được tải lên với ID: <b>" . $file->id . "</b></font>";
          // Đặt quyền cho tệp để mọi người đều có thể xem
          $permission = new \Google\Service\Drive\Permission(array(
              'role' => 'reader',
              'type' => 'anyone',
          ));
          $service->permissions->create($file->id, $permission);
          $messages[] = "<font color=green>- Quyền công khai đã được thiết lập cho tệp: <b>".$fileName."</b> ai có liên kết cũng có thể xem và tải xuống tệp</font>";
          $messages[] = "<br/><font color=green>- Google Cloud Drive: <a href='https://drive.google.com/file/d/".$file->id."/view?usp=drive_link' target='_bank' title='Xem, Tải xuống file ".$fileName."'><b>Tải Xuống File ".$fileName."</b></a></font>";
      } catch (Exception $e) {
          $messages[] = '<font color=red>- Có lỗi xảy ra khi tải tệp lên: ' . $e->getMessage().'</font>';
      }
      }else {
      	$messages[] =  "<br/><font color=red>- <b>Google Cloud Drive chưa được cấu hình, sẽ không có tệp sao lưu nào được tải lên</b></font>";
      }
      }else{
      	$messages[] = "<font color=red>- <b>Cloud Backup -> Google Cloud Drive</b> Không được Kích Hoạt Trong Config.json <b>(backup_upgrade->google_cloud_drive->active)</b>, Sẽ không có file Backup nào được tải lên Google Cloud Drive</font>";
      }
      	}else {
      	$messages[] =  "<font color=red>- <b>Tải dữ liệu Backup Lên Google Cloud Drive không được kích hoạt</b></font>";
      	}
      }else{
      	$messages[] = "Có lỗi xảy ra trong quá trình tạo bản sao lưu dữ liệu chương trình Vbot";
      }
      }else{
      	$messages[] = "- Sao lưu dữ liệu trước khi cập nhật bị tắt, sẽ không có bản sao lưu nào được tạo ra";
      }
	  
      #tên tập tin để chuyển dữ liệu, nội dung trong tệp tin đó sang tập tin mới ["Config.json", "Action.json"];
      $filename_transfers_data_to_new_file = ["Config.json"];
      copyFilesToDestination($VBot_Offline, $Download_Path.'/', $filename_transfers_data_to_new_file);
      
      $messages[] = "<font color=green><b>- Đang tiến hành cập nhật dữ liệu mới...</b></font>";
      #tiến hành Sao chép ghi đè dữ liệu mới và bỏ qua file được chọn
      if (copyFiles($download_Git_Repo_As_Named_Zip.'/', $VBot_Offline, $Keep_The_File_Folder_POST)) {
          $messages[] = "<font color=green><b>- Đã hoàn tất cập nhật dữ liệu mới</b></font><br/>";
      
      // Đường dẫn đến các tệp JSON
      $config_new_path = $VBot_Offline.'Config.json';
      $config_old_path = $Download_Path.'/Config.json';
      // Gọi hàm để thay thế giá trị Config.json từ cũ sang mới
      replace_values_json_file($config_new_path, $config_old_path);
      
      #Xóa các file, thư mục được tải về
      deleteDirectory($Extract_Path);
      deleteDirectory($Download_Path);
      
      //Kiểm tra và khởi động lại Vbot khi cập nhật thành công
      
      $Auto_Reboot_updated_the_program_successfully = isset($_POST['auto_restart_vbot']) ? true : false;
      if ($Auto_Reboot_updated_the_program_successfully === true){
      	#Lệnh Khởi động lại VBot
      	ssh2_exec($connection, "systemctl --user restart VBot_Offline.service");
      	$messages[] = "<font color=green><br/>- Đã chạy lệnh khởi động lại chương trình Vbot: <b>systemctl --user restart VBot_Offline.service</b></font><br/>";
      }else{
      	$messages[] = "<font color=red><br/>- Chương trình VBot không được lựa chọn để tự động khởi động lại khi cập nhật thành công, Bạn cần khởi động lại thủ công để áp dụng dữ liệu mới</font>";
      }
      
      //Chmod lại các file và thư mục thành 0777
      ssh2_exec($connection, "sudo chmod -R 0777 $VBot_Offline");
      ssh2_exec($connection, "sudo chmod -R 0777 $directory_path");
      
      #Phát âm thanh thông báo nếu cập nhật thành công
      $Sound_updated_the_program_successfully_OK = isset($_POST['sound_updated_the_program_successfully']) ? true : false;
      if ($Sound_updated_the_program_successfully_OK === true){
      $sound_updated_the_program_successfully = $VBot_Offline.$Config['smart_config']['smart_wakeup']['sound']['default']['updated_the_program_successfully'];
      echo "<script>playAudio_upgrade('$sound_updated_the_program_successfully');</script>";
      }
      $messages[] = "<br/><font color=green><b>- Cập nhật dữ liệu hoàn tất</b></font>";
      } else {
          $messages[] = "<font color=red>- Lỗi xảy ra trong quá trình cập nhật dữ liệu mới</font>";
      }
      }else{
      	$messages[] = "<font color=red><b>- Có Lỗi trong quá trình tải xuống và giải nén dữ liệu, đã dừng quá trình cập nhật dữ liệu mới</b>";
      }
      }
      }
      }
      
      //Tải xuống tệp từ google cloud drive
      function downloadFileFromDrive($fileId, $destinationDirectory) {
          global $messages, $client, $tokenPath;
      	// Xác thực và tạo dịch vụ Drive
      	$messages[] = "<font color=green>- Đang tiến hành tải xuống tệp sao lưu có ID là: <b>$fileId</b>";
      // Nếu token đã hết hạn, lấy token mới
      if ($client->isAccessTokenExpired()) {
          if ($client->getRefreshToken()) {
              $token = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
              if (isset($token['access_token'])) {
      		// Cập nhật access token và refresh token vào verify_token.json
      		file_put_contents($tokenPath, json_encode($token, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      		$messages[] = '<font color=green>- Tự động làm mới và cập nhật Token Google Cloud Drive Thành Công</font>';
      		}else {
      			$messages[] = '<font color=green>- Xảy ra lỗi, Token Làm Mới Không Tồn Tại Để Xác Thực</font>';
      			return false;
      		}
          } else {
              // Nếu không có refresh token, yêu cầu người dùng xác thực lại
      		$messages[] = "<font color=red>- Không có Token Làm Mới, Hãy kiểm tra lại cấu hình Google Cloud Drive</font>";
      		return false;
          }
      }
      	$service = new Drive($client);
          try {
              // Lấy thông tin tệp để xác định tên tệp
              $file = $service->files->get($fileId, ['fields' => 'name']);
              $fileName = $file->getName();
              // Đường dẫn đích để lưu tệp với tên mặc định
              $destinationPath = rtrim($destinationDirectory, '/') . '/' . $fileName;
              // Lấy nội dung tệp từ Google Drive
              $content = $service->files->get($fileId, ['alt' => 'media']);
              // Mở tệp đích và lưu nội dung
              $outFile = fopen($destinationPath, 'w');
              while (!$content->getBody()->eof()) {
                  fwrite($outFile, $content->getBody()->read(1024));
              }
              fclose($outFile);
              // Nếu quá trình lưu tệp thành công
              if (file_exists($destinationPath)) {
                  $messages[] = "<font color='green'>- Tải xuống tệp <b>'$fileName'</b> thành công</font>";
      			chmod($destinationPath, 0777);
                  return $destinationPath;
              } else {
                  $messages[] = "<font color='red'>- Không thể lưu tệp '$fileName'.</font>";
                  return false;
              }
          } catch (Exception $e) {
              $messages[] = "<font color='red'>- Lỗi khi tải tệp: " . $e->getMessage() . "</font>";
              return false;
          }
      }
      
      #Khôi phục dữ liệu Vbot từ file bAckup
      // Kiểm tra nếu biến Restore_Backup tồn tại và không rỗng
      if (isset($_POST['Restore_Backup'])) {
      $messages = [];
      
      // Kiểm tra và tạo từng thư mục
      foreach ($directoriessss as $directory) {
          createDirectory($directory);
      }
      
      // Kiểm tra value nếu dữ liệu không rỗng
      if (!empty($_POST['Restore_Backup'])) {
      $data_restore_file = $_POST['Restore_Backup'];
      //Khôi phục dữ liệu trên google cloud nếu data_restore_file bắt đầu bằng link http
      if (strpos($data_restore_file, 'http') === 0) {
      	
      if ($google_cloud_drive_active === true){
      if ($libPath_exist === true) {
      $messages[] = "<font color=green>- Tiến hành khôi phục dữ liệu từ tệp sao lưu trên Google Cloud Drive</font>";
      // Biểu thức chính quy để lấy ID
      if (preg_match('/\/d\/([^\/]+)\/view/', $data_restore_file, $matches)) {
          $fileId = $matches[1];
      // Gọi hàm downloadFileFromDrive
      $downloadedFileName = downloadFileFromDrive($fileId, $Download_Path);
      // Kiểm tra kết quả và xử lý
      if ($downloadedFileName) {
      		$messages[] = "<font color=green>- Tiến hành khôi phục dữ liệu từ tệp sao lưu trên Google Cloud Drive</font>";
      	// Gọi hàm để giải nén
      	if (extractTarGz($downloadedFileName, $Extract_Path)) {
      		$Extract_Path_OK = $directory_path.'/'.$Extract_Path.'/';
      		$messages[] = "<font color=green>- Giải nén thành công vào đường dẫn: <b>$Extract_Path/</b> </font><br/>";
      
      if (copyFiles($Extract_Path_OK, $VBot_Offline)) {
          $messages[] = "<font color=green><b>- Sao chép toàn bộ tệp và thư mục thành công!</b></font><br/>";
      	deleteDirectory($Extract_Path);
      	deleteDirectory($Download_Path);
      	$messages[] = "<br/><font color=green><b>- Đã hoàn tất khôi phục dữ liệu từ bản sao lưu: ".basename($downloadedFileName)."</b></font>";
      	$messages[] = "<br/><font color=green><b>- Bạn cần khởi động lại chương trình Vbot để áp dụng các thay đổi từ bản sao lưu</b></font>";
      } else {
          $messages[] = "<font color=red>- Sao chép tệp thất bại</font>";
      }
      } else {
          $messages[] = "<font color=red>- Lỗi khi giải nén tệp</font>";
      }
      } else {
          $messages[] = "<font color=red>- Lỗi khi tải tệp từ Google Drive có ID: <b>$fileId</b></font>";
      }
      } else {
          $messages[] = "Không tìm thấy ID tệp trong URL $data_restore_file";
      }
      }
      else{
      	$messages[] =  "<br/><font color=red>- <b>Google Cloud Drive chưa được cấu hình, quá trình khôi phục dữ liệu đã được hủy<</b></font>";
      }
      }else{
      $messages[] = "<font color=red>- <b>Cloud Backup -> Google Cloud Drive Không được Kích Hoạt Trong Config.json (backup_upgrade->google_cloud_drive->active), quá trình khôi phục dữ liệu đã được hủy</font>";
      }
      }
      	//Nếu dữ liệu là đường dẫn Local
      	elseif (strpos($data_restore_file, '/home/') === 0) {
      		$messages[] = "<font color=green>- Tiến hành khôi phục dữ liệu từ tệp sao lưu trên hệ thống</font>";
      	// Gọi hàm để giải nén
      	if (extractTarGz($data_restore_file, $Extract_Path)) {
      		$Extract_Path_OK = $directory_path.'/'.$Extract_Path.'/';
      		$messages[] = "<font color=green>- Giải nén thành công vào đường dẫn: <b>$Extract_Path/</b> </font><br/>";
      		
      // Gọi hàm để sao chép các tệp
      if (copyFiles($Extract_Path_OK, $VBot_Offline)) {
          $messages[] = "<font color=green><b>- Sao chép toàn bộ tệp và thư mục thành công!</b></font><br/>";
      	deleteDirectory($Extract_Path);
      	deleteDirectory($Download_Path);
      	$messages[] = "<br/><font color=green><b>- Đã hoàn tất khôi phục dữ liệu từ bản sao lưu: ".basename($data_restore_file)."</b></font>";
      	$messages[] = "<br/><font color=green><b>- Bạn cần khởi động lại chương trình Vbot để áp dụng các thay đổi từ bản sao lưu</b></font>";
      
      } else {
          $messages[] = "<font color=red>- Sao chép tệp thất bại</font>";
      }
      	} else {
          $messages[] = "<font color=red>- Lỗi khi giải nén tệp</font>";
      	}
          } else {
              $messages[] = "<font color=red>- Dữ liệu không bắt đầu bằng 'http' hoặc '/home/'</font>";
          }
      }else{
       $messages[] = "<font color=red>- Dữ liệu Restore_Backup là rỗng.</font>";
      }
      }
        
      
      if (isset($_POST['Check_For_Upgrade'])) {
          // Tách URL thành các phần
          $parsedUrl = parse_url($Github_Repo_Vbot);
          $pathParts = explode('/', trim($parsedUrl['path'], '/'));
      
          // Kiểm tra và gán giá trị
          if (count($pathParts) >= 2) {
              $git_username = $pathParts[0];
              $git_repository = $pathParts[1];
      
              // Đường dẫn tới file local và URL của file trên GitHub
              $localFile = $VBot_Offline . 'Version.json';
              $remoteFileUrl = "https://raw.githubusercontent.com/$git_username/$git_repository/refs/heads/main/Version.json";
      
              // Đọc nội dung file local
              if (file_exists($localFile)) {
                  $localContent = file_get_contents($localFile);
                  $localData = json_decode($localContent, true);
      
                  // Đọc nội dung file từ GitHub sử dụng cURL
                  $remoteContent = fetchContent($remoteFileUrl);
                  if ($remoteContent !== false) {
                      $remoteData = json_decode($remoteContent, true);
                      // Lấy giá trị "releaseDate" từ cả hai file và so sánh
                      if (isset($localData['releaseDate']) && isset($remoteData['releaseDate'])) {
                          if ($localData['releaseDate'] !== $remoteData['releaseDate']) {
                              $messages[] = "<font color=green><b>- Có bản cập nhật chương trình VBot mới:</b></font>";
                              $messages[] = "
      <font color=green><ul>
        <li>Phiên Bản Mới:
          <ul>
            <li>Ngày Phát Hành: <font color=red><b>{$remoteData['releaseDate']}</b></font></li>
            <li>Phiên Bản: <font color=red><b>{$remoteData['version']}</b></font></li>
            <li>Mô Tả: <font color=red><b>{$remoteData['description']}</b></font></li>
      	  <li>Nội Dung Thay Đổi:
      	  <ul>
      	   <li>Tính Năng: <font color=red><b>{$remoteData['changes'][0]['description']}</b></font></li>
      	   <li>Sửa Lỗi: <font color=red><b>{$remoteData['changes'][1]['description']}</b></font></li>
      	   <li>Cải Tiến: <font color=red><b>{$remoteData['changes'][2]['description']}</b></font></li>
      	  </ul>
      	  </li>
          </ul>
        </li>
      </ul></font>
      
      <font color=blue><ul>
        <li>Phiên Bản Hiện Tại:
          <ul>
            <li>Ngày Phát Hành: <b>{$localData['releaseDate']}</b></li>
            <li>Phiên Bản: <b>{$localData['version']}</b></li>
      	  <li>Mô Tả: <b>{$localData['description']}</b></li>
          </ul>
        </li>
      </ul></font>";
      
                              $messages[] = "<font color=green><b>- Hãy cập nhật lên phiên bản mới để được hỗ trợ tốt nhất.</b></font>";
                          } else {
                              $messages[] = "<font color=red><b>- Không có bản cập nhật chương trình mới nào</b></font>";
                              $messages[] = "
      <font color=blue><ul>
        <li>Phiên Bản Hiện Tại:
          <ul>
            <li>Ngày Phát Hành: <b>{$localData['releaseDate']}</b></li>
            <li>Phiên Bản: <b>{$localData['version']}</b></li>
      	  <li>Mô Tả: <b>{$localData['description']}</b></li>
          </ul>
        </li>
      </ul></font>";
                          }
                      } else {
                          $messages[] = "<font color=red>Không tìm thấy trường 'releaseDate' trong một hoặc cả hai file</font>";
                      }
                  } else {
                      $messages[] = "<font color=red>Không thể tải file từ URL: $remoteFileUrl</font>";
                  }
              } else {
                  $messages[] = "<font color=red>Không tìm thấy tệp: $localFile</font>";
              }
          } else {
              $messages[] = "<font color=red>Không thể lấy thông tin username và repository từ URL: $Github_Repo_Vbot</font>";
          }
      }
      
      ?>
    <main id="main" class="main">
      <div class="pagetitle">
        <h1>Quản Lý Trương Trình</h1>
        <nav>
          <ol class="breadcrumb">
            <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Trương Trình</li>
          </ol>
        </nav>
      </div>
      <!-- End Page Title -->
      <section class="section">
        <div class="row">
          <?php
            // Kiểm tra và hiển thị thông báo
            if (!empty($messages)) {
            	
            	echo '<div class="card"><div class="card-body">
            <h5 class="card-title">Thông Báo Tiến Trình:</h5>
            <div class="limited-height">';
            	
                //$allMessages = implode("<br>", array_map('htmlspecialchars', $messages));
                $allMessages = implode("<br>", $messages);
                echo "<p>$allMessages</p>";
            	
            	echo "</div></div></div>";
            }
            ?>
          <form method="POST" action="" enctype="multipart/form-data">
            <div class="card">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Cấu Hình Cập Nhật Vbot:</h5>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Tạo Bản Sao Lưu Trước Khi Cập Nhật:</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="make_a_backup_before_updating" id="make_a_backup_before_updating" <?php if ($Config['backup_upgrade']['vbot_program']['upgrade']['backup_before_updating']) echo 'checked'; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="google_gemini_time_out" class="col-sm-3 col-form-label">Thông Báo Âm Thanh <i class="bi bi-question-circle-fill" onclick="show_message('Thông báo bằng âm thanh khi chương trình được cập nhật thành công')"></i>:</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="sound_updated_the_program_successfully" id="sound_updated_the_program_successfully"  <?php if ($Config['backup_upgrade']['advanced_settings']['sound_notification']) echo 'checked'; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="google_gemini_time_out" class="col-sm-3 col-form-label">Tự động khởi động lại chương trình <i class="bi bi-question-circle-fill" onclick="show_message('Tự động khởi động lại chương trình Vbot khi cập nhật thành công')"></i>:</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="auto_restart_vbot" id="auto_restart_vbot"  <?php if ($Config['backup_upgrade']['advanced_settings']['restart_vbot']) echo 'checked'; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="google_gemini_time_out" class="col-sm-3 col-form-label">Tải Bản Sao Lưu Lên Cloud <i class="bi bi-question-circle-fill" onclick="show_message('Tải bản sao lưu giữ liệu trong quá trình cập nhật lên Cloud')"></i>:</label>
                    <div class="col-sm-9">
                      <div class="input-group mb-3">
                        <input <?php echo $google_cloud_drive_active ? '' : 'disabled'; ?> class="form-check-input border-success" type="checkbox" name="vbot_program_cloud_backup_khi_cap_nhat" id="vbot_program_cloud_backup_khi_cap_nhat" value="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive'] ? 'gdrive' : ''; ?>" <?php if ($Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive']) echo 'checked'; ?>>&nbsp;<label for="vbot_program_cloud_backup_khi_cap_nhat">Google Drive</label>&emsp;&emsp;
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="loai_tru_file_thu_muc" class="col-sm-3 col-form-label">Giữ lại tệp, thư mục <i class="bi bi-question-circle-fill" onclick="show_message('Giữ lại tệp, thư mục không cho cập nhật, ghi đè. <b>Áp dụng cho những tệp, thư mục lưu trữ cấu hình, thông tin Cá Nhân (Có tính chất Riêng Tư)</b><br/><br/>- Thiết lập thêm bớt file và thư mục trong tab: <b>Cấu Hình Config</b>')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="input-group mb-3">
                        <?php
                          foreach ($Config['backup_upgrade']['vbot_program']['upgrade']['keep_file_directory'] as $keep_the_file_folder_tuyen) {
                              echo '<input type="checkbox" class="form-check-input border-success" name="keep_the_file_folder[]" id="' . htmlspecialchars($keep_the_file_folder_tuyen) . '" value="' . htmlspecialchars($keep_the_file_folder_tuyen) . '" checked>&nbsp;<label for="' . htmlspecialchars($keep_the_file_folder_tuyen) . '">' . htmlspecialchars($keep_the_file_folder_tuyen) . '</label>&emsp;&emsp;';
                          }
                          ?>
                      </div>
                    </div>
                  </div>
                  <center>
                    <button type="submit" name="Check_For_Upgrade" class="btn btn-primary rounded-pill" onclick="loading('show')">Kiểm Tra Bản Cập Nhật</button>
                    <button type="submit" name="Backup_Upgrade_Program" value="yes_vbot_upgrade" class="btn btn-success rounded-pill" onclick="return confirmRestore('Bạn có chắc chắn muốn cập nhật phiên bản chương trình Vbot mới?')">Cập Nhật Chương Trình</button>
                  </center>
                </div>
              </div>
              <hr/>
              <div class="card-body">
                <h5 class="card-title">Cấu Hình Sao Lưu Vbot:</h5>
                <div class="row mb-3">
                  <label for="vbot_program_backup_path" class="col-sm-3 col-form-label">Đường dẫn tệp sao lưu:</label>
                  <div class="col-sm-9">
                    <div class="input-group mb-3">
                      <input disabled class="form-control border-danger" type="text" name="vbot_program_backup_path" id="vbot_program_backup_path" placeholder="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_path']; ?>" value="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_path']; ?>">
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="vbot_program_limit_backup_files" class="col-sm-3 col-form-label">Giới hạn tối đa tệp tin sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Cần chỉnh sửa trong <b>Config.json</b> hoặc tab <b>Cấu Hình Config</b>')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="input-group mb-3">
                      <input disabled class="form-control border-danger" type="number" min="2" step="1" max="10" name="vbot_program_limit_backup_files" id="vbot_program_limit_backup_files" placeholder="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files']; ?>" value="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files']; ?>">
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="loai_tru_file_thu_muc" class="col-sm-3 col-form-label">Loại Trừ File/Thư Mục Không Sao Lưu  <i class="bi bi-question-circle-fill" onclick="show_message('thêm hoặc loại bỏ file, thư mục sẽ được cấu hình trong <b>Config.json</b> hoặc chỉnh sửa trong tab <b>Cấu Hình Config</b>')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="input-group mb-3">
                      <?php
                        foreach ($Config['backup_upgrade']['vbot_program']['backup']['exclude_files_folder'] as $exclude_files_folderr) {
                            echo '<input type="checkbox" class="form-check-input border-success" name="exclude_files_folder[]" id="' . htmlspecialchars($exclude_files_folderr) . '" value="' . htmlspecialchars($exclude_files_folderr) . '" checked>&nbsp;<label for="' . htmlspecialchars($exclude_files_folderr) . '">' . htmlspecialchars($exclude_files_folderr) . '</label>&emsp;&emsp;';
                        }
                        ?>
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="loai_tru_dinh_dang_file" class="col-sm-3 col-form-label">Loại Trừ Định Dạng File Không Sao Lưu <i class="bi bi-question-circle-fill" onclick="show_message('thêm hoặc loại bỏ định dạng file sẽ được cấu hình trong <b>Config.json</b> hoặc chỉnh sửa trong tab <b>Cấu Hình Config</b>')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="input-group mb-3">
                      <?php
                        foreach ($Config['backup_upgrade']['vbot_program']['backup']['exclude_file_format'] as $exclude_file_formatt) {
                            echo '<input type="checkbox" class="form-check-input border-success" name="exclude_file_format[]" id="' . htmlspecialchars($exclude_file_formatt) . '" value="' . htmlspecialchars($exclude_file_formatt) . '" checked>&nbsp;<label for="' . htmlspecialchars($exclude_file_formatt) . '">' . htmlspecialchars($exclude_file_formatt) . '</label>&emsp;&emsp;';
                        }
                        ?>
                    </div>
                  </div>
                </div>
                <h5 class="card-title">Tải File Sao Lưu Lên Drive:</h5>
                <div class="row mb-3">
                  <label for="google_gemini_time_out" class="col-sm-3 col-form-label">Nguồn:</label>
                  <div class="col-sm-9">
                    <div class="input-group mb-3">
                      <input <?php echo $google_cloud_drive_active ? '' : 'disabled'; ?> class="form-check-input border-success" type="checkbox" name="vbot_program_cloud_backup" id="vbot_program_cloud_backup" placeholder="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive']; ?>" value="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive'] ? 'gdrive' : ''; ?>" <?php if ($Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive']) echo 'checked'; ?>>&nbsp;<label for="vbot_program_cloud_backup">Google Drive</label>&emsp;&emsp;
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="limit_backup_files_cloud_backup" class="col-sm-3 col-form-label">Tối Đa Tệp Sao Lưu:</label>
                  <div class="col-sm-9">
                    <div class="input-group mb-3">
                      <input disabled class="form-control border-danger" type="number" name="limit_backup_files_cloud_backup" id="limit_backup_files_cloud_backup" placeholder="<?php echo $Config['backup_upgrade']['google_cloud_drive']['limit_backup_files']; ?>" value="<?php echo $Config['backup_upgrade']['google_cloud_drive']['limit_backup_files']; ?>">
                    </div>
                  </div>
                </div>
              </div>
              <hr/>
              <div class="card-body">
                <h5 class="card-title">Khôi Phục Dữ Liệu:</h5>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label"><b>Tải Lên Tệp Khôi Phục:</b></label>
                  <div class="col-sm-9">
                    <div class="input-group">
                      <input class="form-control border-success" type="file" name="fileToUpload" accept=".tar.gz">
                      <button class="btn btn-warning border-success" type="submit" name="Backup_Upgrade_Program" value="upload_and_restore" onclick="return confirmRestore('Bạn có chắc chắn muốn tải lên tệp để khôi phục dữ liệu chương trình Vbot không?')">Khôi Phục Dữ Liệu</button>
                    </div>
                  </div>
                </div>
              </div>
              <hr/>
              <center>
                <button type="submit" name="Backup_Upgrade_Program" value="no_vbot_upgrade" class="btn btn-primary rounded-pill" onclick="return confirmRestore('Bạn có chắc chắn muốn tạo bản sao lưu Vbot với Cấu Hình Sao Lưu bên trên?')">Tạo Bản Sao Lưu VBot</button>
                <button type="button" name="show_all_file_in_directoryyyy" class="btn btn-success rounded-pill" onclick="show_all_file_in_directory('<?php echo $HTML_VBot_Offline . '/' . $Backup_Dir_Save_VBot; ?>', 'Tệp Sao Lưu Chương Trình Trên Hệ Thống', 'show_all_file_folder_Backup_Program')">Tệp Sao Lưu Hệ Thống</button>
                <button type="button" name="show_all_file_in_directory_gcloud" class="btn btn-info rounded-pill" onclick="gcloud_scan('<?php echo $backupFolderName; ?>', 'Tệp Sao Lưu Chương Trình Trên Google Cloud Drive',  'show_all_file_folder_Backup_Program')">Tệp Sao Lưu Google Cloud Drive</button>
                <div class="limited-height" id="show_all_file_folder_Backup_Program"></div>
              </center>
              <!-- Bootstrap Modal -->
              <div class="modal fade" id="responseModal_read_files_in_backup" tabindex="-1" role="dialog" aria-labelledby="responseModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-fullscreen" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="responseModalLabel">Nội dung xem trước tệp tin sao lưu </h5>
                      <button type="button" class="btn btn-danger" onclick="closeModal_read_files_in_backup()"><i class="bi bi-x-circle"></i> Đóng</button>
                    </div>
                    <div class="modal-body">
                      <div class="card-body">
                        <pre id="data" class="json"><code id="modal-body-content" class="language-json"></code></pre>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <center>   <button type="button" class="btn btn-danger" onclick="closeModal_read_files_in_backup()"><i class="bi bi-x-circle"></i> Đóng</button></center>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </section>
    </main>

    <?php
      include 'html_footer.php';
      ?>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script>
      function closeModal_read_files_in_backup() {
          $('#responseModal_read_files_in_backup').modal('hide');
      }
    </script>
    <script src="assets/vendor/prism/prism.min.js"></script>
    <script src="assets/vendor/prism/prism-json.min.js"></script>
    <script src="assets/vendor/prism/prism-yaml.min.js"></script>
    <?php
      include 'html_js.php';
      ?>
  </body>
</html>