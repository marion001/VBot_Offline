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

    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="assets/vendor/codemirror/codemirror.min.css">
    <link rel="stylesheet" href="assets/vendor/codemirror/dracula.min.css">

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
      <h1>Developers Assistant (Custom Assistant) <i class="bi bi-question-circle-fill" onclick="show_message('Bạn có thể Code File <b>Dev_Assistant.py</b> ở trên giao diện WEB, hoặc có thể truy cập bằng SSH vào Server để Code trực tiếp ở trên file')"></i></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
		  <li class="breadcrumb-item active">Dev_Assistant.py</li>
&nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['developer_customization']['active'] ? '<p class="text-success" title="Developers Assistant đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Developers Assistant không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
        </ol>
      </nav>
    </div>
    <?php
	$file_path = $VBot_Offline.'Dev_Assistant.py';
    if (isset($_POST['save_code'])) {
        // Lưu mã Python từ trình soạn thảo vào file mà không chạy code
        $python_code = $_POST['code'];
        file_put_contents($file_path, $python_code);
        echo '<script>showMessagePHP("Code đã được lưu", 3);</script>';
    }
    ?>

<?php
    if (isset($_POST['run_code'])) {
        // Lưu mã Python từ trình soạn thảo vào file khi chạy code
        $python_code = $_POST['code'];
        file_put_contents($file_path, $python_code);
		echo '<script>showMessagePHP("Chạy Code Thành Công", 2);</script>';
        echo "<div class='output'>";
        // Chạy file Python và lấy đầu ra
        $output = shell_exec("python3 $file_path 2>&1");
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
		</center>
    </form>
</main>


  <!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- CodeMirror JS -->
    <script src="assets/vendor/codemirror/codemirror.min.js"></script>
    <script src="assets/vendor/codemirror/python.min.js"></script>
    <script>
        // Khởi tạo CodeMirror trong thẻ textarea
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