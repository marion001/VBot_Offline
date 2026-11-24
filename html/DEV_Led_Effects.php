<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
?>
<?php
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
?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>

<head>
  <!-- CodeMirror CSS -->
  <link rel="stylesheet" href="assets/vendor/codemirror/codemirror.min.css?v=<?php echo $Cache_UI_Ver; ?>">
  <link rel="stylesheet" href="assets/vendor/codemirror/dracula.min.css?v=<?php echo $Cache_UI_Ver; ?>">
  <style>
    .editor {
      width: 100%;
      margin-top: 0px;
    }

    .CodeMirror {
      border-radius: 10px;
      height: 450px;
      font-family: 'Courier New', Courier, monospace;
    }

    .output {
      width: 100%;
      margin-top: 0;
      padding: 10px;
      max-height: 200px;
      overflow-y: auto;
      border: 1px solid #ddd;
      background: #000000;
      color: #00ff00;
      box-sizing: border-box;
      border-radius: 10px;
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
      <h1>Developers LED Effects (Hiệu Ứng LED Tùy Chỉnh) <i class="bi bi-question-circle-fill" onclick="show_message('Bạn có thể Code File <b>Dev_Led_Effects.py</b> ở trên giao diện WEB, hoặc truy cập bằng SSH vào Server để tự viết thêm các hiệu ứng LED tùy chỉnh cho VBot')"></i></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item active">Dev_Led_Effects.py</li>
          &nbsp;| Trạng Thái LED:
          <?php
          if (!empty($Config['smart_config']['led']['active'])) {
            echo '<p class="text-success" title="Hệ thống LED đang được bật">&nbsp;Đang Bật</p>';
          } else {
            echo '<p class="text-danger" title="Hệ thống LED đang tắt">&nbsp;Đang Tắt</p>';
          }
          ?>
        </ol>
      </nav>
    </div>
    <?php
    $file_path = $VBot_Offline . 'Dev_Led_Effects.py';
    if (isset($_POST['save_code'])) {
      $python_code = $_POST['code'];
      file_put_contents($file_path, $python_code, LOCK_EX);
      echo '<script>showMessagePHP("Code đã được lưu", 3);</script>';
    }
    ?>
    <?php
    if (isset($_POST['run_code'])) {
      $python_code = $_POST['code'];
      file_put_contents($file_path, $python_code, LOCK_EX);
      echo '<script>showMessagePHP("Chạy Code Thành Công", 2);</script>';
      echo "<div class='output'>";
      $output = shell_exec("python3 " . escapeshellarg($file_path) . " 2>&1");
      echo "<pre>" . htmlspecialchars($output) . "</pre>";
      echo "</div><hr/>";
    }
    ?>
    <form method="POST" class="editor">
      <textarea name="code" id="code-editor" style="display:none;"><?php
        if (file_exists($file_path)) {
          echo htmlspecialchars(file_get_contents($file_path));
        } else {
          echo "File $file_path không tồn tại.";
        }
      ?></textarea>
      <br>
      <center>
        <button disabled type="submit" name="run_code" class="btn btn-success rounded-pill" title="Run Code" onclick="loading('show')">Chạy Code</button>
        <button type="submit" name="save_code" class="btn btn-primary rounded-pill" title="Lưu Code" onclick="loading('show')">Lưu Code</button>
      </center><br/>
      <div class="alert alert-primary" role="alert">
        Sau khi viết và lưu hiệu ứng LED mới trong <b>Dev_Led_Effects.py</b>, hãy <b>Khởi động lại VBot</b> để chọn hiệu ứng mới tại trang <b>Trang Chủ (index.php)</b> hoặc <b>Cấu Hình Config</b>.
      </div>
    </form>
  </main>
  <!-- ======= Footer ======= -->
  <?php
  include 'html_footer.php';
  ?>
  <!-- End Footer -->
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <!-- CodeMirror JS -->
  <script src="assets/vendor/codemirror/codemirror.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <script src="assets/vendor/codemirror/python.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <script>
    var editor = CodeMirror.fromTextArea(document.getElementById("code-editor"), {
      mode: "python",
      theme: "dracula",
      lineNumbers: true,
      indentUnit: 4,
      tabSize: 4,
      matchBrackets: true
    });
  </script>
  <!-- Template Main JS File -->
  <?php
  include 'html_js.php';
  ?>
</body>

</html>
