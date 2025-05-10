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
    header('Location: Login.php');
    exit;
}
// Cập nhật lại thời gian đăng nhập để kéo dài thời gian session
//$_SESSION['user_login']['login_time'] = time();
}

  if (isset($_POST['file_path']) && isset($_POST['code'])) {
    $file_path = $_POST['file_path'];
    $json_code = $_POST['code'];
    // Nếu file_path trống thì bỏ qua xử lý
    if (empty($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Không có đường dẫn file, bỏ qua lưu.']);
        return;
    }
    // Kiểm tra JSON có hợp lệ không
    json_decode($json_code);
    if (json_last_error() !== JSON_ERROR_NONE) {
      echo json_encode(["success" => false, "message" => "JSON không hợp lệ."]);
      exit;
    }
    if (file_put_contents($file_path, $json_code) !== false) {
      echo json_encode(["success" => true, "message" => "Đã lưu file thành công: ".basename($file_path)]);
    } else {
      echo json_encode(["success" => false, "message" => "Không thể ghi vào file:". basename($file_path)]);
    }
    exit;
  }
?>

<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
  <!-- Thêm CSS của Prism -->
  <link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css">
  <link rel="stylesheet" href="assets/vendor/codemirror/codemirror.min.css">
  <link rel="stylesheet" href="assets/vendor/codemirror/dracula.min.css">
  <style>
.editor {
  width: 100% !important;
  margin: 10px auto;
  box-sizing: border-box;
}
    .output {
      width: 100%;
      margin-top: 10px;
      padding: 10px;
      max-height: 90%;
      overflow-y: auto;
      border: 1px solid #ddd;
      background: #000;
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
      <h1>Đọc, Chỉnh Sửa File JSON</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item">Chỉnh sửa JSON</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
	    <section class="section">
<div class="row" style="width: 100%;">
  <div style="width: 100%;">
    <select class="form-select border-success" id="file_selector">
      <option value="" selected>Chọn File JSON Cần Sửa, Đọc</option>
      <option value="<?php echo $Config_filePath; ?>">Config.json</option>
      <option value="<?php echo $VBot_Offline.'Action.json'; ?>">Action.json</option>
      <option value="<?php echo $VBot_Offline.'Adverbs.json'; ?>">Adverbs.json</option>
      <option value="<?php echo $VBot_Offline.'Object.json'; ?>">Object.json</option>
      <option value="<?php echo $VBot_Offline.'BackList.json'; ?>">BackList.json</option>
      <option value="<?php echo $VBot_Offline.'stt_token_google_cloud.json'; ?>">stt_token_google_cloud.json</option>
      <option value="<?php echo $VBot_Offline.'tts_token_google_cloud.json'; ?>">tts_token_google_cloud.json</option>
      <option value="<?php echo $VBot_Offline.'resource/hass/Home_Assistant_Custom.json'; ?>">Home_Assistant_Custom.json</option>
      <option value="<?php echo $VBot_Offline.'resource/schedule/Data_Schedule.json'; ?>">Data_Schedule.json</option>
      <option value="<?php echo $VBot_Offline.'Media/News_Paper/News_Paper.json'; ?>">VBot Báo, Tin Tức News_Paper.json</option>
      <option value="<?php echo $directory_path.'/includes/other_data/list_voices_tts_gcloud.json'; ?>">list_voices_tts_gcloud.json</option>
      <option value="<?php echo $directory_path.'/includes/VBot_Client_Data/Data_VBot_Client.json'; ?>">Data_VBot_Client.json</option>
      <option value="<?php echo $directory_path.'/includes/VBot_Server_Data/VBot_Devices_Network.json'; ?>">VBot_Devices_Network.json</option>
      <option value="<?php echo $directory_path.'/includes/Google_Driver_PHP/client_secret.json'; ?>">Google Driver client_secret.json</option>
      <option value="<?php echo $directory_path.'/includes/Google_Driver_PHP/verify_token.json'; ?>">Google Driver verify_token.json</option>
      <option value="<?php echo $directory_path.'/includes/cache/PlayList.json'; ?>">Danh Sách Nhạc PlayList.json</option>
      <option value="<?php echo $directory_path.'/includes/cache/PodCast.json'; ?>">PodCast.json</option>
      <option value="<?php echo $directory_path.'/includes/cache/Youtube.json'; ?>">Youtube.json</option>
      <option value="<?php echo $directory_path.'/includes/cache/ZingMP3.json'; ?>">ZingMP3.json</option>
      <option value="<?php echo $directory_path.'/includes/cache/News_Paper.json'; ?>">WebUI Báo, Tin Tức News_Paper.json</option>
      <option value="<?php echo $VBot_Offline.'Version.json'; ?>">Version.json</option>
    </select>
    <div class="editor" id="editor"></div>
  </div>
</div>
	<center>
	<button class="btn btn-primary rounded-pill" id="save_btn_json_file" title="Lưu dữ liệu"><i class="bi bi-save2"></i> Lưu file</button> 
	<button class="btn btn-warning rounded-pill" title="Tải Xuống" onclick="downloadFile_View_Edit_Json()"><i class="bi bi-download"></i> Tải Xuống</button>
	</center>
</section>
</main>
  <!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->
<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<script src="assets/vendor/codemirror/codemirror.min.js"></script>
<script src="assets/vendor/codemirror/javascript.min.js"></script>
<script src="assets/vendor/prism/prism.min.js"></script>
<script>
  var currentFile = document.getElementById("file_selector").value;
  var editor = CodeMirror(document.getElementById("editor"), {
    mode: "application/json",  // Cú pháp JSON 
    theme: "dracula",          // Theme của CodeMirror
    lineNumbers: true,         // Hiển thị số dòng
    matchBrackets: true,       // Giao diện gợi ý khi chọn dấu ngoặc
    indentUnit: 2,             // Thụt lề 2 ký tự
    tabSize: 2,                // Kích thước tab là 2 ký tự
  });
  // Hàm tải nội dung JSON
  function loadJsonFile(filePath) {
	  loading('show');
  if (!filePath || filePath.trim() === "") {
	editor.setValue("");
	loading('hide');
    return;
  }
  editor.setSize("100%", "70vh");
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "includes/php_ajax/Show_file_path.php?read_file_path&file=" + encodeURIComponent(filePath), true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          try {
            var json = JSON.parse(xhr.responseText);
            editor.setValue(JSON.stringify(json, null, 4));
			loading('hide');
			//showMessagePHP('Đã tải nội dung File JSON', 5);
          } catch (e) {
			  loading('hide');
			show_message('Định dạng JSON lỗi, không hợp lệ:<br/>'+e.message);
          }
        } else {
			loading('hide');
		  show_message('Không thể tải nội dung file JSON');
        }
      }
    };
    xhr.send();
  }
  // Thay đổi file khi chọn
  document.getElementById("file_selector").addEventListener("change", function () {
    currentFile = this.value;
    loadJsonFile(currentFile);
  });
  // Lưu nội dung JSON khi nhấn nút
  document.getElementById("save_btn_json_file").onclick = function () {
	  loading('show');
	  if (!currentFile){
		show_message('Không tìm thấy File JSON được chọn để lưu dữ liệu');
		loading('hide');
		return;
	  }
    var content = editor.getValue();
    try {
		// kiểm tra json hợp lệ trước khi lưu
      var parsed = JSON.parse(content); 
    } catch (e) {
		loading('hide');
	  show_message('Có lỗi trong cấu trúc, cú pháp JSON được chỉnh sửa:<br/><font color=red>' +e.message+'</font>');
      return;
    }
    var xhr = new XMLHttpRequest();
    xhr.open("POST", window.location.href, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    var body = "file_path=" + encodeURIComponent(currentFile) +"&save_code=true&code=" + encodeURIComponent(JSON.stringify(parsed, null, 4));
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success) {
			  loading('hide');
			showMessagePHP(res.message);
          } else {
			  loading('hide');
			show_message(res.message);
          }
        } catch (e) {
			loading('hide');
		  show_message('Lỗi phản hồi từ máy chủ');
        }
      }
    };
    xhr.send(body);
  };

//Tải xuống File Json
function downloadFile_View_Edit_Json() {
  const fileSelector = document.getElementById("file_selector");
  const selectedFile = fileSelector.value;
  if (!selectedFile) {
	show_message('Không có File nào được chọn để tải xuống');
    return;
  }
	downloadFile(selectedFile);
}
  
</script>

  <!-- Template Main JS File -->
<?php
include 'html_js.php';
?>

</body>
</html>