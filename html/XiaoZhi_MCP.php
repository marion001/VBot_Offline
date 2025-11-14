<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';

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

#Tải File
function download_file($url, $saveDir) {
    if (!is_dir($saveDir)) {
        mkdir($saveDir, 0777, true);
    }
    $fileName = basename(parse_url($url, PHP_URL_PATH));
    $savePath = rtrim($saveDir, "/") . "/" . $fileName;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($data === false || !empty($error)) {
        echo "<p style='color:red;'>Lỗi tải file từ <b>$url</b>: $error</p>";
        return false;
    }
    if (file_put_contents($savePath, $data) !== false) {
        exec("chmod 0777 " . escapeshellarg($savePath));
        //echo "<p style='color:green;'>Đã tải và lưu file tại: $savePath</p>";
        return true;
    } else {
        echo "<p style='color:red;'>Không thể ghi file vào: $savePath</p>";
        return false;
    }
}

//Đường dẫn file JSON
$mcp_json_file = $VBot_Offline.'resource/xiaozhi/xiaozhi_tools.json';
if (!file_exists($mcp_json_file)) {
    echo ("<h1><p style='color:red;'>Không tìm thấy file JSON: $mcp_json_file</p></h1>");
	download_file('https://raw.githubusercontent.com/marion001/VBot_Offline/refs/heads/main/resource/xiaozhi/xiaozhi_tools.json', $VBot_Offline.'resource/xiaozhi/');
}


//Lưu bật tắt từng MCP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mcp'])) {
    if (file_exists($mcp_json_file)) {
        $json_data = file_get_contents($mcp_json_file);
        $data = json_decode($json_data, true);
        if (isset($data['tools'])) {
            foreach ($data['tools'] as &$tool) {
                $name = $tool['name'];
                //Nếu checkbox được tick → active = true, ngược lại false
                $tool['active'] = isset($_POST[$name]) ? true : false;
            }
            unset($tool);
            file_put_contents($mcp_json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $messages[] = "Đã Lưu Dữ Liệu Thành Công";
        } else {
            $messages[] = "Lỗi Xảy Ra, Dữ Liệu Json Không Hợp Lệ";
        }
    } else {
		$messages[] = "Lỗi Xảy Ra, Không tìm thấy file JSON: $mcp_json_file";
    }
}
// Đọc nội dung JSON
$mcp_json_data = file_get_contents($mcp_json_file);
$MCP_data_json = json_decode($mcp_json_data, true);
?>

<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
    <style>
        .scroll-btn {
            position: fixed;
            right: 5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            text-align: center;
            line-height: 40px;
            font-size: 24px;
            z-index: 4;
        }

        .scroll-to-bottom {
            bottom: 15px;
        }

        .scroll-to-top {
            bottom: 60px;
        }
    </style>
    <link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css?v=<?php echo $Cache_UI_Ver; ?>">
    <style>
        #modal_dialog_show_XiaoZhi_MCP {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            max-width: calc(100vw - 40px);
        }

        #modal_dialog_show_XiaoZhi_MCP .modal-content {
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
    </style>
</head>
<body>
  <?php
  //Hiển thị thông báo php
  if (!empty($messages)) {
    $safeMessages = array_map(function ($msg) {
      return htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    }, $messages);
    $allMessages = implode("\\n", $safeMessages);
    echo "<script>showMessagePHP('$allMessages', 5);</script>";
  }
  include 'html_header_bar.php';
  include 'html_sidebar.php';
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Máy Chủ XiaoZhi MCP</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item">XiaoZhi MCP Server</li>
          &nbsp;| Trạng Thái Kích Hoạt MCP: <?php echo $Config['home_assistant']['custom_commands']['active'] ? '<p class="text-success" title="Home Assistant Custom Command đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Home Assistant Custom Command không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
          &nbsp;| Trợ Lý XiaoZhi AI: <?php echo $Config['home_assistant']['custom_commands']['active'] ? '<p class="text-success" title="Home Assistant Custom Command đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Home Assistant Custom Command không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
        </ol>
      </nav>
    </div>
    <section class="section">
    <div class="row">

<!-- <div class="col-lg-6"> -->
<form method="post" action="">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">MCP Nội Bộ Hệ Thống System VBot Control <i class="bi bi-question-circle-fill" onclick="show_message('Máy chủ, Server khi được kết nối sẽ có thể tương tác được với hệ thống VBot')"></i></h5>
<?php
if (!$MCP_data_json || !isset($MCP_data_json['tools'])) {
    die("<center><p style='color:red;'>Dữ liệu JSON không hợp lệ hoặc thiếu trường 'tools': $mcp_json_file</p></center>");
}
echo '<table class="table table-bordered border-primary">';
echo '<thead>
        <tr>
          <th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">STT</th>
          <th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Tên Tools MCP</th>
          <th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Trạng Thái Kích Hoạt</th>
          <th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Mô Tả</th>
        </tr>
      </thead>
      <tbody>';
$stt_mcp = 1;
foreach ($MCP_data_json['tools'] as $tool) {
    $name = htmlspecialchars($tool['name']);
    $description = htmlspecialchars($tool['description'] ?? '');
    $active = !empty($tool['active']) ? 'checked' : '';
    echo "<tr>
            <th scope='row' style='text-align: center; vertical-align: middle;'>{$stt_mcp}</th>
            <td style='vertical-align: middle;' class='text-success'><b>{$name}</b></td>
            <td style='text-align: center; vertical-align: middle;'>
              <div class='form-switch'>
                <input class='form-check-input border-success' type='checkbox' name='{$name}' id='{$name}' {$active}>
              </div>
            </td>
            <td style='vertical-align: middle;' class='text-primary'>{$description}</td>
          </tr>";
    $stt_mcp++;
}

echo '</tbody></table>';
?>
<div class="row mb-3">
            <label for="file_xiaozhi_tools" class="col-sm-3 col-form-label"><b>Đường Dẫn/Path File Cấu Hình:</b></label>
            <div class="col-sm-9">
              <input disabled="" class="form-control border-danger" type="text" name="file_xiaozhi_tools" id="file_xiaozhi_tools" value="<?php echo $mcp_json_file; ?>">
            </div>
          </div>
<div class="row mb-3">
 <center><button type="submit" class="btn btn-primary rounded-pill" name="save_mcp"> <i class="bi bi-save"></i> Lưu thay đổi</button>
 <button type="button" class="btn btn-success rounded-pill" name="save_mcp" onclick="downloadFile('<?php echo $mcp_json_file; ?>')"><i class="bi bi-download"></i> Tải Xuống</button>
<button type="button" class="btn btn-warning rounded-pill" title="Xem dữ liệu json MCP" id="openModalBtn_XiaoZhi_MCP"><i class="bi bi-eye"></i>Xem dữ liệu Cấu Hình</button>
</center>
            </div>
            </div>
          </div></form>
<!-- </div> -->
<!--
        <div class="col-lg-6">
          </div>
-->
      </div>
    </section>
  </main>
    <!-- Modal hiển thị tệp Config.json -->
    <div class="modal fade" id="myModal_XiaoZhi_MCP" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
        <div class="modal-dialog" id="modal_dialog_show_XiaoZhi_MCP" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <b>
                        <font color=blue>
                            <div id="name_file_showzz"></div>
                        </font>
                    </b>
                    <button type="button" class="close btn btn-danger" data-dismiss="modal_Config" aria-label="Close" onclick="$('#myModal_XiaoZhi_MCP').modal('hide');">
                        <i class="bi bi-x-circle-fill"></i> Đóng
                    </button>
                </div>
                <div class="modal-body">
                    <p id="message_LoadConfigJson"></p>
                    <pre id="data" class="json"><code id="code_config" class="language-json"></code></pre>
                </div>
            </div>
        </div>
    </div>
  <?php
  include 'html_footer.php';
  ?>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script>
        // Hiển thị modal xem nội dung file json Home_Assistant.json
        ['openModalBtn_XiaoZhi_MCP'].forEach(function(id) {
            document.getElementById(id).addEventListener('click', function() {
                var file_name_hassJSON = "<?php echo $mcp_json_file; ?>";
                read_loadFile(file_name_hassJSON);
                document.getElementById('name_file_showzz').textContent = "Tên File: " + file_name_hassJSON.split('/').pop();
                $('#myModal_XiaoZhi_MCP').modal('show');
            });
        });
    </script>
    <script src="assets/vendor/prism/prism.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <script src="assets/vendor/prism/prism-json.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <?php
  include 'html_js.php';
  ?>

</body>

</html>