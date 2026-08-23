<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
  ]);
  session_start();
}
if (empty($_SESSION['vbot_csrf_token']) || !is_string($_SESSION['vbot_csrf_token'])) {
  $_SESSION['vbot_csrf_token'] = bin2hex(random_bytes(32));
}

function verifyLoginCsrfToken()
{
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
  return is_string($token)
    && isset($_SESSION['vbot_csrf_token'])
    && is_string($_SESSION['vbot_csrf_token'])
    && hash_equals($_SESSION['vbot_csrf_token'], $token);
}

function rejectInvalidLoginCsrf()
{
  if (verifyLoginCsrfToken()) return;
  if (function_exists('Logs')) Logs('Đã chặn yêu cầu Login.php thiếu hoặc sai CSRF token');
  http_response_code(403);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc phiên CSRF đã hết hạn.']);
  exit;
}

$filePath_Data = 'includes/other_data/WebUI_Login_Security/Login_Data.json';
$dirPath_Data  = dirname($filePath_Data);
$logDir  = $VBot_Offline . 'resource/log';
$logFile = $logDir . "/Vbot_error.log";
if (!file_exists($logDir)) {
  mkdir($logDir, 0777, true);
}
@chmod($logDir, 0777);
if (!file_exists($logFile)) {
  file_put_contents($logFile, "");
}
@chmod($logFile, 0777);
function Logs($message)
{
  global $logFile;
  $current_time = date("H:i:s d-m-Y");
  $line = "[" . $current_time . "][VBot WebUI] " . $message . "\n";
  file_put_contents($logFile, $line, FILE_APPEND);
}

function vbotLoginClientKey()
{
  $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : 'unknown';
  return hash('sha256', $remoteAddress !== '' ? $remoteAddress : 'unknown');
}

function vbotNormalizeLoginData($data, $clientKey)
{
  if (!is_array($data)) $data = [];
  if (!isset($data['attempts_by_client']) || !is_array($data['attempts_by_client'])) {
    $legacyFails = max(0, (int)($data['number_of_failed_logins'] ?? 0));
    $legacyTime = isset($data['last_failed_login_time']) ? (int)$data['last_failed_login_time'] : null;
    $data = ['attempts_by_client' => []];
    if ($legacyFails > 0) {
      $data['attempts_by_client'][$clientKey] = [
        'number_of_failed_logins' => $legacyFails,
        'last_failed_login_time' => $legacyTime,
      ];
    }
  }
  return $data;
}

function vbotUpdateLoginData($filePath, $clientKey, callable $callback)
{
  $handle = @fopen($filePath, 'c+');
  if ($handle === false || !@flock($handle, LOCK_EX)) {
    if (is_resource($handle)) fclose($handle);
    throw new RuntimeException('Không thể khóa dữ liệu bảo mật đăng nhập');
  }
  try {
    rewind($handle);
    $raw = stream_get_contents($handle);
    $data = vbotNormalizeLoginData($raw !== '' ? json_decode($raw, true) : [], $clientKey);
    $result = $callback($data);
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) throw new RuntimeException('Không thể mã hóa dữ liệu bảo mật đăng nhập');
    rewind($handle);
    if (!ftruncate($handle, 0) || fwrite($handle, $encoded) === false) {
      throw new RuntimeException('Không thể ghi dữ liệu bảo mật đăng nhập');
    }
    fflush($handle);
    if (function_exists('fsync')) @fsync($handle);
    return ['data' => $data, 'result' => $result];
  } finally {
    flock($handle, LOCK_UN);
    fclose($handle);
  }
}

function vbotLoginAttemptState(array $data, $clientKey, $maxAttempts, $lockSeconds, $now = null)
{
  $now = $now ?? time();
  $entry = $data['attempts_by_client'][$clientKey] ?? [];
  $fails = max(0, (int)($entry['number_of_failed_logins'] ?? 0));
  $lastFailed = (int)($entry['last_failed_login_time'] ?? 0);
  $unlockTime = ($fails >= $maxAttempts && $lastFailed > 0) ? $lastFailed + $lockSeconds : 0;
  return [
    'fails' => $fails,
    'locked' => $unlockTime > $now,
    'remaining_seconds' => max(0, $unlockTime - $now),
  ];
}

if (!is_dir($dirPath_Data)) {
  mkdir($dirPath_Data, 0777, true);
}
@chmod($dirPath_Data, 0777);

if (!file_exists($filePath_Data)) {
  $defaultData = [
    "attempts_by_client" => []
  ];
  file_put_contents($filePath_Data, json_encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
@chmod($filePath_Data, 0777);

$loginClientKey = vbotLoginClientKey();
$maxLoginAttempts = max(1, (int)($Config['contact_info']['user_login']['login_attempts'] ?? 5));
$loginLockSeconds = max(1, (int)($Config['contact_info']['user_login']['login_lock_time'] ?? 900));
$loginReadResult = vbotUpdateLoginData($filePath_Data, $loginClientKey, function (&$data) {
  return null;
});
$Login_Data = $loginReadResult['data'];
$error1 = '';
$error = '';
if (isset($_POST['reset_limit_login'])) {
  rejectInvalidLoginCsrf();
  $inputEmail = trim($_POST['email'] ?? '');
  if (strcasecmp($inputEmail, $Config['contact_info']['email']) === 0) {
    if (file_exists($filePath_Data)) {
      $resetResult = vbotUpdateLoginData($filePath_Data, $loginClientKey, function (&$data) {
        $data['attempts_by_client'] = [];
        return true;
      });
      $Login_Data = $resetResult['data'];
      $Msg_ERROR = "✅ Đã reset giới hạn đăng nhập WebUI";
      $error .= $Msg_ERROR . '<br/><br/>';
      Logs($Msg_ERROR);
    }
  } else {
    $Msg_ERROR = '❌ Email không trùng khớp, không thể Reset thời gian chờ đăng nhập WebUI';
    $error1 = "<font color='red' size='5'>$Msg_ERROR</font>";
    Logs($Msg_ERROR);
  }
}

$loginState = vbotLoginAttemptState($Login_Data, $loginClientKey, $maxLoginAttempts, $loginLockSeconds);
if ($loginState['locked']) {
    $error .= "<br/><center><h1><font color='red'>VBot Assistant Đăng Nhập Thất Bại</font><br/><br/>Vượt quá số lần đăng nhập cho phép! Hãy thử lại sau <font color='red'>" . $loginState['remaining_seconds'] . "</font> giây<br/><br/><a href='Login.php'>Tải Lại Trang</a></h1>";
    echo $error;
    echo '<hr/><h2><font color=red>Reset Giới Hạn Thời Gian Chờ</font></h2><br/><form method="POST">
			<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['vbot_csrf_token'], ENT_QUOTES, 'UTF-8') . '">
			Nhập Email: <input type="text" name="email" placeholder="Nhập Email Của Bạn">
			<button type="submit" name="reset_limit_login">Reset Giới Hạn Đăng Nhập</button>
			</form><br/>';
    echo $error1;
    echo '</center>';
    exit();
}

#Quên Mật Khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
  rejectInvalidLoginCsrf();
  $my_email = $_POST['mail'] ?? '';
  if (!empty($my_email)) {
    if ($my_email === $Config['contact_info']['email']) {
      // Hiển thị mật khẩu hoặc gửi liên kết đặt lại mật khẩu
      $response = [
        "success" => true,
        "message" => $Config['contact_info']['user_login']['user_password']
      ];
    } else {
      $response = [
        "success" => false,
        "message" => "Email không khớp!"
      ];
    }
  } else {
    $response = [
      "success" => false,
      "message" => "Vui lòng nhập email!"
    ];
  }
  header('Content-Type: application/json');
  echo json_encode($response);
  exit();
}
//Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
  rejectInvalidLoginCsrf();
  header('Content-Type: application/json');
  if (empty($_SESSION['user_login']['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn!']);
    exit();
  }
  $currentPassword = $_POST['currentPassword'] ?? '';
  $newPassword = $_POST['newpassword'] ?? '';
  $renewPassword = $_POST['renewpassword'] ?? '';
  // Kiểm tra xem tất cả các tham số có giá trị không
  if (!empty($currentPassword) && !empty($newPassword) && !empty($renewPassword)) {
    // Kiểm tra xem mật khẩu cũ có khớp với mật khẩu hiện tại không
    if ($currentPassword === $Config['contact_info']['user_login']['user_password']) {
      // Kiểm tra độ dài mật khẩu mới
      if (strlen($newPassword) >= 6 && strlen($newPassword) <= 32) {
        // Kiểm tra xem mật khẩu mới và nhập lại mật khẩu mới có khớp nhau không
        if ($newPassword === $renewPassword) {
          //Tiến hành cập nhật mật khẩu mới
          $Config['contact_info']['user_login']['user_password'] = $renewPassword;
          $encodedConfig = json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          if ($encodedConfig === false || !vbotAtomicWriteFile($Config_filePath, $encodedConfig, 'Config.json')) {
            echo json_encode(['success' => false, 'message' => 'Không thể ghi mật khẩu mới vào Config.json!']);
            exit();
          }
          $response = [
            "success" => true,
            "message" => "Mật khẩu đã được thay đổi thành công!"
          ];
        } else {
          $response = [
            "success" => false,
            "message" => "Mật khẩu mới và xác nhận mật khẩu không khớp!"
          ];
        }
      } else {
        $response = [
          "success" => false,
          "message" => "Mật khẩu mới phải từ 6 đến 32 ký tự!"
        ];
      }
    } else {
      $response = [
        "success" => false,
        "message" => "Mật khẩu cũ không đúng!"
      ];
    }
  } else {
    $response = [
      "success" => false,
      "message" => "Vui lòng nhập đầy đủ thông tin!"
    ];
  }
  echo json_encode($response);
  exit();
}

//Đăng xuất
if (isset($_GET['logout'])) {
  unset($_SESSION['user_login']);
  header('Location: Login.php');
  exit;
}

if ($Config['contact_info']['user_login']['active']) {
  // Kiểm tra xem người dùng đã đăng nhập chưa
  if (!empty($_SESSION['user_login']['logged_in'])) {
    // Nếu đã đăng nhập, chuyển hướng đến trang index
    header('Location: index.php');
    exit;
  } elseif (isset($_SESSION['user_login'])) {
    unset($_SESSION['user_login']);
  }
} else {
  header('Location: index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token_password'])) {
  if (!verifyLoginCsrfToken()) {
    $Msg_ERROR = "Yêu cầu đăng nhập không hợp lệ!";
    $error .= $Msg_ERROR;
    Logs($Msg_ERROR);
  } else {
    $stored_hash = hash('sha256', $Config['contact_info']['user_login']['user_password']);
    $password_user = (string) $_POST['token_password'];
    $passwordMatches = hash_equals($stored_hash, $password_user);
    $attemptUpdate = vbotUpdateLoginData(
      $filePath_Data,
      $loginClientKey,
      function (&$data) use ($loginClientKey, $maxLoginAttempts, $loginLockSeconds, $passwordMatches) {
        $now = time();
        $state = vbotLoginAttemptState($data, $loginClientKey, $maxLoginAttempts, $loginLockSeconds, $now);
        if ($state['locked']) {
          return ['status' => 'locked', 'remaining_seconds' => $state['remaining_seconds']];
        }
        if ($state['fails'] >= $maxLoginAttempts) {
          unset($data['attempts_by_client'][$loginClientKey]);
          $state['fails'] = 0;
        }
        if ($passwordMatches) {
          unset($data['attempts_by_client'][$loginClientKey]);
          return ['status' => 'success'];
        }
        $currentFails = $state['fails'] + 1;
        $data['attempts_by_client'][$loginClientKey] = [
          'number_of_failed_logins' => $currentFails,
          'last_failed_login_time' => $currentFails >= $maxLoginAttempts ? $now : null,
        ];
        return [
          'status' => $currentFails >= $maxLoginAttempts ? 'locked' : 'failed',
          'fails' => $currentFails,
          'remaining_seconds' => $currentFails >= $maxLoginAttempts ? $loginLockSeconds : 0,
        ];
      }
    );
    $Login_Data = $attemptUpdate['data'];
    $attemptResult = $attemptUpdate['result'];
    if (($attemptResult['status'] ?? '') === 'success') {
      session_regenerate_id(true);
      // Đăng nhập thành công
      $_SESSION['user_login'] = [
        'logged_in' => true,
        'login_time' => time()
      ];
      header('Location: index.php');
      exit;
    } elseif (($attemptResult['status'] ?? '') === 'locked') {
      $waitSeconds = max(1, (int)($attemptResult['remaining_seconds'] ?? $loginLockSeconds));
      $error .= "❌ Đã vượt quá {$maxLoginAttempts} lần đăng nhập. Vui lòng thử lại sau <b>{$waitSeconds}</b> giây.";
    } else {
      $error .= "❌ Sai mật khẩu!";
      $currentFails = (int)($attemptResult['fails'] ?? 0);
      $remaining = $maxLoginAttempts - $currentFails;
      if ($remaining > 0 && $remaining <= 3) {
        $error .= "<br/>Bạn còn <b>{$remaining}</b> lần đăng nhập";
      }
      //Logs("Sai Mật Khẩu");
    }
  }
}
?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<script src="assets/js/crypto-js.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>

<body>
  <!-- Loading Mesage-->
  <div id="loadingOverlay" class="overlay_loading">
    <div class="spinner-border spinner-border-sm" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status">
      <span class="sr-only">________</span>
    </div>
    <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
  </div>
  <!--Kết thúc Loading Mesage-->
  <!-- Thông báo Mesage html_slidebar.php -->
  <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <button type="button" class="btn btn-danger" onclick="close_message()" title="Tắt thông báo"><i class="bi bi-x-circle-fill"></i></button>
        <div class="modal-body">
          <!-- Nội dung thông báo ở đây sẽ được cập nhật bởi JavaScript -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger rounded-pill" onclick="close_message()">Đóng</button>
        </div>
      </div>
    </div>
  </div>
  <!--Kết Thúc Thông báo Mesage -->
  <main>
    <div class="container">
      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
              <a href=""><img src="assets/img/logo.png" alt="Logo" class="logo-img mb-3" style="width: 250px; height: 250px; object-fit: contain;"></a>
              <!-- Logo -->
              <div class="d-flex justify-content-center py-4">
                <a href="" class="logo d-flex flex-column align-items-center w-auto">
                  <span class="d-block d-lg-none">VBot Assistant</span> <!-- Hiển thị trên mobile -->
                  <span class="d-none d-lg-block">VBot Assistant</span> <!-- Hiển thị trên desktop -->
                </a>
              </div>
              <!-- End Logo -->
              <div class="card">
                <div class="card-body">
                  <!-- Default Tabs -->
                  <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                      <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Đăng Nhập</button>
                    </li>
                    <li class="nav-item" role="presentation">
                      <button class="nav-link" id="quenmatkhau-tab" data-bs-toggle="tab" data-bs-target="#quenmatkhau" type="button" role="tab" aria-controls="quenmatkhau" aria-selected="false" tabindex="-1">Quên Mật Khẩu</button>
                    </li>
                  </ul>
                  <div class="tab-content pt-2">
                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                      <div class="pt-4 pb-2">
                        <h3 class="card-title text-center pb-0 fs-4 text-danger"><?php echo $Config['contact_info']['full_name']; ?></h5>
                          <h5 class="card-title text-center pb-0 fs-4">Đăng Nhập Hệ Thống</h5>
                      </div>
                      <?php if (isset($error)): ?>
                        <p style="color: red;"><?php echo $error; ?></p>
                      <?php endif; ?>
                      <form class="row g-3 needs-validation" novalidate method="POST" onsubmit="return VBot_Hash();" action="">
                        <div class="col-12">
                          <label for="salt_password" class="form-label">Mật khẩu:</label>
                          <input type="password" name="salt_password" class="form-control border-success" placeholder="Nhập mật khẩu đăng nhập" id="salt_password" required>
                          <input type="hidden" name="token_password" class="form-control border-success" id="token_password">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['vbot_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                          <div class="invalid-feedback">Vui lòng nhập mật khẩu của bạn!</div>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                          <button onclick="loading('show')" class="btn btn-primary rounded-pill" type="submit">Đăng nhập</button>
                        </div>
                      </form>
                    </div>
                    <div class="tab-pane fade" id="quenmatkhau" role="tabpanel" aria-labelledby="quenmatkhau-tab">
                      <div class="col-12">
                        <h5 class="card-title text-center pb-0 fs-4">Quên Mật Khẩu</h5>
                      </div>
                      <div class="col-12">
                        <label for="forgotPassword_email" class="form-label">Nhập Email</label>
                        <input type="text" placeholder="Nhập Email để lấy lại mật khẩu" name="forgotPassword_email" class="form-control border-success" id="forgotPassword_email" required>
                      </div>
                      <div class="d-grid gap-2 mt-3">
                        <button type="button" onclick="forgotPassword()" class="btn btn-primary rounded-pill">Lấy Mật Khẩu</button>
                      </div>
                    </div>
                  </div>
                  <!-- End Default Tabs -->
                </div>
              </div>
  <div class="copyright">
	 <p>@Email: <a href="mailto:vbot.assistant@gmail.com" target="_blank">VBot.Assistant@gmail.com</a></p>
  </div>
              <div class="credits">
                Code by <a href="https://www.facebook.com/TWFyaW9uMDAx" target="_bank">(<i class="bi bi-facebook"></i> Vũ Tuyển)</a>, <a href="https://www.facebook.com/groups/1148385343358824" target="_bank"><i class="bi bi-facebook"></i> Group Facebook</a>, <a href="https://github.com/marion001/VBot_Offline" target="_bank"><i class="bi bi-github"></i> GitHub</a>, <a href="https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ" target="_bank"><i class="bi bi-folder2-open"></i> VBot IMG</a>, Designed by: <a href="https://bootstrapmade.com/" target="_bank">BootstrapMade</a>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>
  <!-- End #main -->
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <p style="display: none;" id="loading_show"></p>

  <script>
    function VBot_Hash() {
      const password = document.getElementById("salt_password").value;
      const hashed = CryptoJS.SHA256(password).toString();
      document.getElementById("token_password").value = hashed;
      document.getElementById("salt_password").value = "21a6b5db2e8d0be9699defb1d3468d92d9e71b4db3d3f8cdca1645234e5e3cbf";
      return true;
    }
  </script>

  <?php
  include 'html_js.php';
  ?>
</body>

</html>
