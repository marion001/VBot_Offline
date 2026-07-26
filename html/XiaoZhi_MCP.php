<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

//Giữ properties={} là JSON object khi PHP mã hóa lại associative array.
function normalize_mcp_empty_properties(&$tool) {
    if (
        isset($tool['inputSchema'])
        && is_array($tool['inputSchema'])
        && array_key_exists('properties', $tool['inputSchema'])
        && is_array($tool['inputSchema']['properties'])
        && count($tool['inputSchema']['properties']) === 0
    ) {
        $tool['inputSchema']['properties'] = (object)[];
    }
}

function atomic_write_mcp_json($file_path, $json_content) {
    $directory = dirname($file_path);
    $temp_file = tempnam($directory, '.mcp_tmp_');
    if ($temp_file === false) {
        return false;
    }
    $written = file_put_contents($temp_file, $json_content, LOCK_EX);
    if ($written === false) {
        @unlink($temp_file);
        return false;
    }
    if (file_exists($file_path)) {
        $permissions = @fileperms($file_path);
        if ($permissions !== false) {
            @chmod($temp_file, $permissions & 0777);
        }
    }
    if (!@rename($temp_file, $file_path)) {
        @unlink($temp_file);
        return false;
    }
    return true;
}

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
$mcp_plugins_dir = $VBot_Offline.'resource/xiaozhi/mcp_plugins';
$mcp_plugins_readme_file = $VBot_Offline.'resource/xiaozhi/mcp_plugins.readme';
$mcp_plugins_readme = is_file($mcp_plugins_readme_file)
    ? file_get_contents($mcp_plugins_readme_file)
    : "Không tìm thấy file hướng dẫn: ".$mcp_plugins_readme_file;
if (!is_dir($mcp_plugins_dir)) {
    mkdir($mcp_plugins_dir, 0775, true);
}
if (!file_exists($mcp_json_file)) {
    echo ("<h1><p style='color:red;'>Không tìm thấy file JSON: $mcp_json_file</p></h1>");
	download_file('https://raw.githubusercontent.com/marion001/VBot_Offline/refs/heads/main/resource/xiaozhi/xiaozhi_tools.json', $VBot_Offline.'resource/xiaozhi/');
}


//Lưu bật tắt từng MCP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mcp'])) {
    if (file_exists($mcp_json_file)) {
        $json_data = file_get_contents($mcp_json_file);
        $data = json_decode($json_data, true);
        if (isset($data['tools']) && is_array($data['tools'])) {
            $enabled_tools = [];
            foreach ($data['tools'] as &$tool) {
                if (!is_array($tool) || empty($tool['name']) || !is_string($tool['name'])) {
                    continue;
                }
                $name = $tool['name'];
                if (isset($_POST[$name])) {
                    $enabled_tools[] = $name;
                }
                //Dọn trường active của cấu trúc cũ khỏi định nghĩa MCP.
                unset($tool['active']);
            }
            unset($tool);
            $data['enabledTools'] = $enabled_tools;
            foreach ($data['tools'] as &$tool) {
                normalize_mcp_empty_properties($tool);
            }
            unset($tool);
            $json_output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json_output !== false && atomic_write_mcp_json($mcp_json_file, $json_output)) {
                $messages[] = "Đã Lưu Dữ Liệu Thành Công";
            } else {
                $messages[] = "Lỗi Xảy Ra, Không Thể Ghi Dữ Liệu JSON";
            }
        } else {
            $messages[] = "Lỗi Xảy Ra, Dữ Liệu Json Không Hợp Lệ";
        }
    } else {
		$messages[] = "Lỗi Xảy Ra, Không tìm thấy file JSON: $mcp_json_file";
    }

    //Lưu trạng thái từng MCP plugin; mỗi thư mục chứa một plugin.json.
    $plugin_directories = glob($mcp_plugins_dir.'/*', GLOB_ONLYDIR) ?: [];
    foreach ($plugin_directories as $plugin_directory) {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', basename($plugin_directory))) {
            continue;
        }
        $plugin_manifest_file = $plugin_directory.'/plugin.json';
        if (!is_file($plugin_manifest_file)) {
            continue;
        }
        $plugin_manifest = json_decode(file_get_contents($plugin_manifest_file), true);
        if (!is_array($plugin_manifest) || !isset($plugin_manifest['tool']['name'])) {
            $messages[] = "Plugin không hợp lệ: ".basename($plugin_directory);
            continue;
        }
        $plugin_field = 'mcp_plugin__'.basename($plugin_directory);
        $plugin_manifest['active'] = isset($_POST[$plugin_field]);
        normalize_mcp_empty_properties($plugin_manifest['tool']);
        $plugin_json_output = json_encode(
            $plugin_manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (
            $plugin_json_output === false
            || !atomic_write_mcp_json($plugin_manifest_file, $plugin_json_output)
        ) {
            $messages[] = "Không thể lưu MCP plugin: ".basename($plugin_directory);
        }
    }
}
// Đọc nội dung JSON
$mcp_json_data = file_get_contents($mcp_json_file);
$MCP_data_json = json_decode($mcp_json_data, true);
$enabled_mcp_tools = [];
if (is_array($MCP_data_json)) {
    if (isset($MCP_data_json['enabledTools']) && is_array($MCP_data_json['enabledTools'])) {
        $enabled_mcp_tools = array_fill_keys($MCP_data_json['enabledTools'], true);
    } elseif (isset($MCP_data_json['tools']) && is_array($MCP_data_json['tools'])) {
        //Tương thích file cũ trong lần mở giao diện đầu tiên.
        foreach ($MCP_data_json['tools'] as $legacy_tool) {
            if (!empty($legacy_tool['active']) && !empty($legacy_tool['name'])) {
                $enabled_mcp_tools[$legacy_tool['name']] = true;
            }
        }
    }
}

$MCP_plugins = [];
$plugin_directories = glob($mcp_plugins_dir.'/*', GLOB_ONLYDIR) ?: [];
sort($plugin_directories, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($plugin_directories as $plugin_directory) {
    if (!preg_match('/^[A-Za-z0-9_-]+$/', basename($plugin_directory))) {
        continue;
    }
    $plugin_manifest_file = $plugin_directory.'/plugin.json';
    if (!is_file($plugin_manifest_file)) {
        continue;
    }
    $plugin_manifest = json_decode(file_get_contents($plugin_manifest_file), true);
    if (!is_array($plugin_manifest) || !isset($plugin_manifest['tool']['name'])) {
        continue;
    }
    $plugin_manifest['_directory'] = basename($plugin_directory);
    $MCP_plugins[] = $plugin_manifest;
}
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
          &nbsp;| Trạng Thái Kích Hoạt MCP: <?php echo $Config['xiaozhi']['mcp_system_control'] ? '<p class="text-success" title="XiaoZhi MCP đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="XiaoZhi MCP không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
          &nbsp;| Trợ Lý XiaoZhi AI: <?php echo $Config['xiaozhi']['active'] ? '<p class="text-success" title="Trợ lý XiaoZhi đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Trợ lý XiaoZhi không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
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
    $active = isset($enabled_mcp_tools[$tool['name']]) ? 'checked' : '';
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

echo '<h5 class="card-title">MCP Plugins do người dùng tự viết</h5>';
if (empty($MCP_plugins)) {
    echo '<div class="alert alert-secondary">Chưa có MCP plugin trong thư mục resource/xiaozhi/mcp_plugins.</div>';
} else {
    echo '<table class="table table-bordered border-success">';
    echo '<thead>
            <tr>
              <th class="text-danger" style="text-align:center;">STT</th>
              <th class="text-danger">Tên Plugin / Tool</th>
              <th class="text-danger" style="text-align:center;">Kích Hoạt</th>
              <th class="text-danger">Mô Tả</th>
            </tr>
          </thead><tbody>';
    $plugin_index = 1;
    foreach ($MCP_plugins as $plugin) {
        $directory = htmlspecialchars($plugin['_directory'], ENT_QUOTES, 'UTF-8');
        $tool_name = htmlspecialchars($plugin['tool']['name'], ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($plugin['tool']['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $checked = !empty($plugin['active']) ? 'checked' : '';
        $field_name = 'mcp_plugin__'.$directory;
        echo "<tr>
                <th style='text-align:center;vertical-align:middle;'>{$plugin_index}</th>
                <td style='vertical-align:middle;'>
                  <b class='text-success'>{$tool_name}</b><br>
                  <small class='text-muted'>{$directory}</small>
                </td>
                <td style='text-align:center;vertical-align:middle;'>
                  <div class='form-switch'>
                    <input class='form-check-input border-success' type='checkbox'
                           name='{$field_name}' id='{$field_name}' {$checked}>
                  </div>
                </td>
                <td style='vertical-align:middle;' class='text-primary'>{$description}</td>
              </tr>";
        $plugin_index++;
    }
    echo '</tbody></table>';
}
?>
<div class="row mb-3">
            <label for="file_xiaozhi_tools" class="col-sm-3 col-form-label"><b>Đường Dẫn/Path File Cấu Hình:</b></label>
            <div class="col-sm-9">
              <input disabled="" class="form-control border-danger" type="text" name="file_xiaozhi_tools" id="file_xiaozhi_tools" value="<?php echo $mcp_json_file; ?>">
            </div>
          </div>

<div class="alert alert-primary" role="alert">
Để Bật Tắt Sử Dụng Chức Năng Này Hãy Đi Tới: <b>Cấu Hình Config</b> -> <b>Cấu Hình Bot/Trợ Lý XiaoZhi AI</b> -> <b>Kích Hoạt</b>
</div>

<div class="row mb-3">
 <center><button type="submit" class="btn btn-primary rounded-pill" name="save_mcp"> <i class="bi bi-save"></i> Lưu thay đổi</button>
 <button type="button" class="btn btn-success rounded-pill" name="save_mcp" onclick="downloadFile('<?php echo $mcp_json_file; ?>')"><i class="bi bi-download"></i> Tải Xuống</button>
<button type="button" class="btn btn-warning rounded-pill" title="Xem dữ liệu json MCP" id="openModalBtn_XiaoZhi_MCP"><i class="bi bi-eye"></i>Xem dữ liệu Cấu Hình</button>
<button type="button" class="btn btn-info rounded-pill" title="Hướng dẫn tạo và sử dụng MCP Plugin" onclick="$('#myModal_XiaoZhi_MCP_Guide').modal('show');"><i class="bi bi-book"></i> Hướng Dẫn MCP Plugin</button>
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
    <div class="modal fade" id="myModal_XiaoZhi_MCP_Guide" tabindex="-1" role="dialog" aria-labelledby="modalLabel_XiaoZhi_MCP_Guide" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="modalLabel_XiaoZhi_MCP_Guide">
                        <i class="bi bi-book"></i> Hướng Dẫn Tạo Và Xử Lý MCP Plugin
                    </h5>
                    <button type="button" class="close btn btn-danger" aria-label="Close" onclick="$('#myModal_XiaoZhi_MCP_Guide').modal('hide');">
                        <i class="bi bi-x-circle-fill"></i> Đóng
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        Mỗi thư mục trong <b>resource/xiaozhi/mcp_plugins</b> tương ứng với một MCP tool.
                        Sau khi thêm hoặc bật/tắt plugin, hãy khởi động lại VBot để áp dụng.
                    </div>
                    <pre class="p-3 border rounded bg-light" style="white-space: pre-wrap; word-break: break-word; max-height: 70vh; overflow-y: auto;"><code><?php echo htmlspecialchars($mcp_plugins_readme, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="$('#myModal_XiaoZhi_MCP_Guide').modal('hide');">
                        <i class="bi bi-x-circle"></i> Đóng
                    </button>
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
