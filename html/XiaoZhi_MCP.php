<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
require_once __DIR__.'/includes/php_ajax/Api_Helpers.php';

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
$mcp_playlist_manifest_file = $VBot_Offline.'html/includes/cache/PlayLists.json';
$mcp_managed_playlists = [];
$mcp_active_playlist_id = '';
if (is_file($mcp_playlist_manifest_file)) {
    $mcp_playlist_manifest = json_decode((string) file_get_contents($mcp_playlist_manifest_file), true);
    if (is_array($mcp_playlist_manifest)) {
        $mcp_active_playlist_id = (string) ($mcp_playlist_manifest['active_id'] ?? '');
        foreach (($mcp_playlist_manifest['playlists'] ?? []) as $playlist) {
            if (!is_array($playlist)) continue;
            $playlist_id = trim((string) ($playlist['id'] ?? ''));
            $playlist_name = trim((string) ($playlist['name'] ?? ''));
            $playlist_file = trim((string) ($playlist['file'] ?? ''));
            if ($playlist_id === '' || $playlist_name === '' || basename($playlist_file) !== $playlist_file) continue;
            if (!is_file($VBot_Offline.'html/includes/cache/playlists/'.$playlist_file)) continue;
            $mcp_managed_playlists[] = [
                'id' => $playlist_id,
                'name' => $playlist_name,
                'active' => $playlist_id === $mcp_active_playlist_id,
            ];
        }
    }
}
$mcp_plugins_readme = is_file($mcp_plugins_readme_file)
    ? file_get_contents($mcp_plugins_readme_file)
    : "Không tìm thấy file hướng dẫn: ".$mcp_plugins_readme_file;
if (!is_dir($mcp_plugins_dir)) {
    mkdir($mcp_plugins_dir, 0777, true);
}
$protected_mcp_tool_names = [
    'self.vbot.echo',
    'self.vbot.system_status',
    'self.vbot.wikipedia_search',
];
$protected_mcp_directories = ['sample_echo', 'system_status', 'wikipedia_search'];

function delete_mcp_plugin_directory($directory_path) {
    if (is_link($directory_path) || is_file($directory_path)) {
        return @unlink($directory_path);
    }
    if (!is_dir($directory_path)) {
        return false;
    }
    $items = scandir($directory_path);
    if ($items === false) {
        return false;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        if (!delete_mcp_plugin_directory($directory_path.DIRECTORY_SEPARATOR.$item)) {
            return false;
        }
    }
    return @rmdir($directory_path);
}

//Tạo MCP plugin mới từ WebUI.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_mcp_plugin'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $directory_name = trim((string) ($_POST['mcp_create_directory'] ?? ''));
    $tool_name = trim((string) ($_POST['mcp_create_tool_name'] ?? ''));
    $description = trim((string) ($_POST['mcp_create_description'] ?? ''));
    $parameter_name = trim((string) ($_POST['mcp_create_parameter_name'] ?? ''));
    $parameter_description = trim((string) ($_POST['mcp_create_parameter_description'] ?? ''));
    $parameter_required = ($_POST['mcp_create_parameter_required'] ?? '') === '1';
    $timeout = filter_var(
        $_POST['mcp_create_timeout'] ?? 12,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 60]]
    );

    if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/', $directory_name)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Tên MCP phải bắt đầu bằng chữ và chỉ chứa chữ, số, dấu gạch ngang hoặc gạch dưới.'
        ], 400);
    }
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_.-]{1,127}$/', $tool_name)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Tool name không hợp lệ.'
        ], 400);
    }
    if ($description === '' || strlen($description) > 500) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Mô tả MCP không được để trống và tối đa 500 ký tự.'
        ], 400);
    }
    if ($parameter_name !== '' && !preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $parameter_name)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Tên tham số không hợp lệ.'
        ], 400);
    }
    if ($parameter_name !== '' && ($parameter_description === '' || strlen($parameter_description) > 300)) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Hãy nhập mô tả tham số, tối đa 300 ký tự.'
        ], 400);
    }
    if ($timeout === false) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Timeout phải từ 1 đến 60 giây.'], 400);
    }

    $plugin_directory = $mcp_plugins_dir.'/'.$directory_name;
    if (file_exists($plugin_directory)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Tên MCP này đã tồn tại.'], 409);
    }
    foreach (glob($mcp_plugins_dir.'/*/plugin.json') ?: [] as $manifest_path) {
        $existing_manifest = json_decode((string) @file_get_contents($manifest_path), true);
        if (is_array($existing_manifest) && ($existing_manifest['tool']['name'] ?? '') === $tool_name) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tool name này đã được sử dụng.'], 409);
        }
    }

    $python_file_name = 'handler.py';
    $properties = (object) [];
    $required = [];
    if ($parameter_name !== '') {
        $properties = [
            $parameter_name => [
                'type' => 'string',
                'description' => $parameter_description
            ]
        ];
        if ($parameter_required) {
            $required[] = $parameter_name;
        }
    }
    $manifest = [
        'active' => false,
        'entrypoint' => $python_file_name,
        'function' => 'handle',
        'timeout' => $timeout,
        'tool' => [
            'name' => $tool_name,
            'description' => $description,
            'inputSchema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required
            ]
        ]
    ];
    $manifest_content = json_encode(
        $manifest,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $python_tool_name = json_encode($tool_name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $python_content = "\"\"\"MCP plugin được tạo từ VBot WebUI.\"\"\"\n\n"
        ."def handle(arguments, context):\n"
        ."    \"\"\"Xử lý arguments do XiaoZhi gửi tới.\"\"\"\n"
        ."    context[\"log\"](\n"
        ."        f\"[MCP Plugin] Đang xử lý {$tool_name}: {arguments}\",\n"
        ."        color=context[\"Lib\"].Color.CYAN,\n"
        ."    )\n"
        ."    return {\n"
        ."        \"content\": [{\"type\": \"text\", \"text\": f\"Đã xử lý dữ liệu: {arguments}\"}],\n"
        ."        \"structuredContent\": {\"tool\": {$python_tool_name}, \"arguments\": arguments},\n"
        ."    }\n";

    if ($manifest_content === false || !@mkdir($plugin_directory, 0777, true)) {
        error_log('Unable to create MCP plugin directory: '.$plugin_directory);
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể tạo thư mục MCP plugin.'], 500);
    }
    $manifest_path = $plugin_directory.'/plugin.json';
    $python_path = $plugin_directory.'/'.$python_file_name;
    if (
        file_put_contents($manifest_path, $manifest_content."\n", LOCK_EX) === false
        || file_put_contents($python_path, $python_content, LOCK_EX) === false
    ) {
        @unlink($manifest_path);
        @unlink($python_path);
        @rmdir($plugin_directory);
        error_log('Unable to write MCP plugin files: '.$plugin_directory);
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể ghi file MCP plugin.'], 500);
    }
    @chmod($plugin_directory, 0777);
    @chmod($manifest_path, 0777);
    @chmod($python_path, 0777);
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Đã tạo MCP plugin: '.$directory_name,
        'directory' => $directory_name,
        'files' => [$python_file_name, 'plugin.json']
    ]);
}

//Xóa MCP plugin do người dùng tạo; khóa cứng các plugin mẫu mặc định.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_mcp_plugin'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $directory_name = trim((string) ($_POST['mcp_delete_directory'] ?? ''));
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/', $directory_name)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Tên thư mục MCP không hợp lệ.'], 400);
    }
    if (in_array($directory_name, $protected_mcp_directories, true)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không được xóa MCP mặc định.'], 403);
    }

    $plugins_root_path = realpath($mcp_plugins_dir);
    $plugin_directory = realpath($mcp_plugins_dir.'/'.$directory_name);
    if (
        $plugins_root_path === false
        || $plugin_directory === false
        || dirname($plugin_directory) !== $plugins_root_path
        || !is_dir($plugin_directory)
        || is_link($plugin_directory)
    ) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy MCP plugin cần xóa.'], 404);
    }
    $manifest_path = $plugin_directory.'/plugin.json';
    $manifest = is_file($manifest_path)
        ? json_decode((string) file_get_contents($manifest_path), true)
        : null;
    $tool_name = is_array($manifest) ? ($manifest['tool']['name'] ?? '') : '';
    if (in_array($tool_name, $protected_mcp_tool_names, true)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không được xóa MCP mặc định: '.$tool_name], 403);
    }
    if (!is_array($manifest) || $tool_name === '') {
        vbotApiJsonResponse(['success' => false, 'message' => 'plugin.json không hợp lệ; từ chối xóa để bảo vệ dữ liệu.'], 400);
    }

    if (!delete_mcp_plugin_directory($plugin_directory)) {
        error_log('Unable to delete MCP plugin directory: '.$plugin_directory);
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể xóa hoàn toàn MCP plugin.'], 500);
    }
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Đã xóa MCP plugin: '.$directory_name
    ]);
}

function resolve_mcp_plugin_file($plugins_root, $plugin_directory_name, $plugin_file_name) {
    $allowed_extension = strtolower(pathinfo($plugin_file_name, PATHINFO_EXTENSION));
    if (
        !preg_match('/^[A-Za-z0-9_-]+$/', $plugin_directory_name)
        || basename($plugin_file_name) !== $plugin_file_name
        || !preg_match('/^[A-Za-z0-9_.-]+\.(py|json)$/i', $plugin_file_name)
        || !in_array($allowed_extension, ['py', 'json'], true)
    ) {
        return false;
    }

    $plugins_root_path = realpath($plugins_root);
    $plugin_directory_path = realpath($plugins_root.'/'.$plugin_directory_name);
    $plugin_file_path = realpath($plugins_root.'/'.$plugin_directory_name.'/'.$plugin_file_name);
    if (
        $plugins_root_path === false
        || $plugin_directory_path === false
        || $plugin_file_path === false
        || dirname($plugin_file_path) !== $plugin_directory_path
        || strpos($plugin_directory_path, $plugins_root_path.DIRECTORY_SEPARATOR) !== 0
        || !is_file($plugin_file_path)
    ) {
        return false;
    }
    return $plugin_file_path;
}

if (!file_exists($mcp_json_file)) {
    echo ("<h1><p style='color:red;'>Không tìm thấy file JSON: $mcp_json_file</p></h1>");
	download_file('https://raw.githubusercontent.com/marion001/VBot_Offline/refs/heads/main/resource/xiaozhi/xiaozhi_tools.json', $VBot_Offline.'resource/xiaozhi/');
}

//Chỉ tải nội dung file khi người dùng mở trình soạn thảo.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_mcp_plugin_file'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $plugin_directory_name = $_POST['mcp_plugin_directory'] ?? '';
    $plugin_file_name = $_POST['mcp_plugin_file'] ?? '';
    $plugin_file_path = resolve_mcp_plugin_file(
        $mcp_plugins_dir,
        $plugin_directory_name,
        $plugin_file_name
    );
    $response = [
        'success' => false,
        'message' => 'Không tìm thấy file MCP plugin cần đọc'
    ];
    if ($plugin_file_path !== false) {
        $plugin_file_content = file_get_contents($plugin_file_path);
        if ($plugin_file_content !== false) {
            $response = [
                'success' => true,
                'message' => 'Đã tải file',
                'content' => $plugin_file_content
            ];
        } else {
            $response['message'] = 'Không thể đọc nội dung file MCP plugin';
        }
    }
    vbotApiJsonResponse($response, $response['success'] ? 200 : 404);
}

//Lưu nội dung file Python/JSON của MCP plugin từ trình soạn thảo modal.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mcp_plugin_file'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $plugin_directory_name = $_POST['mcp_plugin_directory'] ?? '';
    $plugin_file_name = $_POST['mcp_plugin_file'] ?? '';
    $plugin_file_content = $_POST['mcp_plugin_file_content'] ?? '';
    $allowed_extension = strtolower(pathinfo($plugin_file_name, PATHINFO_EXTENSION));
    $plugin_file_save_success = false;
    $plugin_file_save_message = '';

    $plugin_file_path = resolve_mcp_plugin_file(
        $mcp_plugins_dir,
        $plugin_directory_name,
        $plugin_file_name
    );
    if ($plugin_file_path === false) {
        $plugin_file_save_message = "Tên plugin, tên file hoặc đường dẫn không hợp lệ";
    } elseif (
            $allowed_extension === 'json'
            && json_decode($plugin_file_content, true) === null
            && json_last_error() !== JSON_ERROR_NONE
        ) {
            $plugin_file_save_message = "JSON không hợp lệ: ".json_last_error_msg();
        } else {
            $backup_file_path = $plugin_file_path.'.bak';
            if (!@copy($plugin_file_path, $backup_file_path)) {
                $plugin_file_save_message = "Không thể tạo bản sao lưu nên file chưa được ghi";
            } elseif (atomic_write_mcp_json($plugin_file_path, $plugin_file_content)) {
                $plugin_file_save_success = true;
                $plugin_file_save_message = "Đã lưu file MCP plugin: "
                    .$plugin_directory_name.'/'.$plugin_file_name
                    ." (đã tạo bản sao .bak)";
            } else {
                $plugin_file_save_message = "Không thể lưu file MCP plugin: ".$plugin_directory_name.'/'.$plugin_file_name;
            }
        }

    if (isset($_POST['mcp_plugin_ajax'])) {
        vbotApiJsonResponse(
            ['success' => $plugin_file_save_success, 'message' => $plugin_file_save_message],
            $plugin_file_save_success ? 200 : 400
        );
    }
    $messages[] = $plugin_file_save_message;
}

//Chỉnh sửa mô tả MCP hệ thống trực tiếp trong xiaozhi_tools.json.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_builtin_mcp_description'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $tool_name = trim((string) ($_POST['builtin_mcp_tool_name'] ?? ''));
    $description = trim((string) ($_POST['builtin_mcp_description'] ?? ''));
    $description_length = function_exists('mb_strlen') ? mb_strlen($description, 'UTF-8') : strlen($description);
    if ($tool_name === '' || $description === '' || $description_length > 4000) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Tên MCP hoặc mô tả không hợp lệ; mô tả phải từ 1 đến 4000 ký tự.'
        ], 400);
    }
    $tools_data = json_decode((string) @file_get_contents($mcp_json_file), true);
    if (!is_array($tools_data) || !isset($tools_data['tools']) || !is_array($tools_data['tools'])) {
        vbotApiJsonResponse(['success' => false, 'message' => 'xiaozhi_tools.json không hợp lệ.'], 500);
    }
    $updated = false;
    foreach ($tools_data['tools'] as &$tool) {
        if (is_array($tool) && isset($tool['name']) && hash_equals((string) $tool['name'], $tool_name)) {
            $tool['description'] = $description;
            normalize_mcp_empty_properties($tool);
            $updated = true;
            break;
        }
    }
    unset($tool);
    if (!$updated) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không tìm thấy MCP hệ thống: '.$tool_name], 404);
    }
    $json_output = json_encode(
        $tools_data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $backup_file = $mcp_json_file.'.bak';
    if ($json_output === false || !@copy($mcp_json_file, $backup_file)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể tạo bản sao xiaozhi_tools.json.bak nên chưa lưu mô tả.'], 500);
    }
    if (!atomic_write_mcp_json($mcp_json_file, $json_output)) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không thể ghi mô tả vào xiaozhi_tools.json.'], 500);
    }
    vbotApiJsonResponse([
        'success' => true,
        'tool_name' => $tool_name,
        'description' => $description,
        'message' => 'Đã cập nhật mô tả MCP: '.$tool_name.' (đã tạo bản sao .bak)'
    ]);
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
    $plugin_manifest['_files'] = [];
    $plugin_files = array_merge(
        glob($plugin_directory.'/*.py') ?: [],
        glob($plugin_directory.'/*.json') ?: []
    );
    sort($plugin_files, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($plugin_files as $plugin_file) {
        if (is_file($plugin_file)) {
            $plugin_manifest['_files'][basename($plugin_file)] = null;
        }
    }
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
    <link rel="stylesheet" href="assets/vendor/codemirror/codemirror.min.css?v=<?php echo $Cache_UI_Ver; ?>">
    <link rel="stylesheet" href="assets/vendor/codemirror/dracula.min.css?v=<?php echo $Cache_UI_Ver; ?>">
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
        #modal_dialog_mcp_plugin_editor {
            max-width: calc(100vw - 40px);
            width: 1100px;
        }
        #modal_dialog_mcp_plugin_editor .CodeMirror {
            height: calc(100vh - 250px);
            min-height: 420px;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
        }

        .mcp-toolbar {
            position: sticky;
            top: 64px;
            z-index: 900;
            padding: .75rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(13, 110, 253, .22);
            border-radius: .75rem;
            background: rgba(244, 248, 255, .96);
            box-shadow: 0 .25rem .75rem rgba(18, 38, 63, .08);
            backdrop-filter: blur(6px);
        }

        .mcp-search-hidden {
            display: none !important;
        }

        #mcp-search-empty {
            display: none;
        }

        @media (max-width: 991.98px) {
            .mcp-toolbar {
                top: 60px;
            }
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
    <div class="mcp-toolbar" id="mcp-toolbar" aria-label="Tìm kiếm và điều hướng MCP">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-lg">
          <div class="input-group">
            <span class="input-group-text border-primary"><i class="bi bi-search text-primary"></i></span>
            <input type="search" id="mcp-tool-search" class="form-control border-primary"
              placeholder="Tìm tên MCP, plugin, mô tả hoặc trạng thái..." autocomplete="off">
            <button type="button" id="mcp-search-clear" class="btn btn-outline-secondary" title="Xóa nội dung tìm kiếm">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
        <div class="col-12 col-lg-auto">
          <select id="mcp-quick-navigation" class="form-select border-primary" aria-label="Đi tới khu vực MCP">
            <option value="">Đi tới khu vực...</option>
            <option value="mcp-builtin-section">MCP nội bộ VBot</option>
            <option value="mcp-custom-section">MCP Plugin người dùng</option>
            <option value="mcp-actions-section">Lưu và quản lý cấu hình</option>
          </select>
        </div>
      </div>
      <div id="mcp-search-empty" class="alert alert-info py-2 mt-2 mb-0" role="status">
        <i class="bi bi-info-circle"></i> Không tìm thấy MCP phù hợp.
      </div>
    </div>
    <div class="row">

<!-- <div class="col-lg-6"> -->
<form method="post" action="" id="mcp-config-form">
          <div class="card" id="mcp-config-section">
            <div class="card-body">
              <h5 class="card-title" id="mcp-builtin-section">MCP Nội Bộ Hệ Thống System VBot Control <i class="bi bi-question-circle-fill" onclick="show_message('Máy chủ, Server khi được kết nối sẽ có thể tương tác được với hệ thống VBot')"></i></h5>
<?php
if (!$MCP_data_json || !isset($MCP_data_json['tools'])) {
    die("<center><p style='color:red;'>Dữ liệu JSON không hợp lệ hoặc thiếu trường 'tools': $mcp_json_file</p></center>");
}
echo '<div class="table-responsive"><table class="table table-bordered border-primary" id="mcp-builtin-table">';
echo '<thead>
        <tr>
          <th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">STT</th>
          <th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Tên MCP Plugin/Tools</th>
          <th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Trạng Thái Kích Hoạt</th>
          <th scope="col" class="text-danger" style="text-align: center; vertical-align: middle;">Mô Tả</th>
        </tr>
      </thead>
      <tbody>';
$stt_mcp = 1;
foreach ($MCP_data_json['tools'] as $tool) {
    $name = htmlspecialchars($tool['name'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($tool['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $active = isset($enabled_mcp_tools[$tool['name']]) ? 'checked' : '';
    $playlist_choices_html = '';
    if (($tool['name'] ?? '') === '_VBot_PlaylistPlayer') {
        if ($mcp_managed_playlists) {
            $playlist_badges = [];
            foreach ($mcp_managed_playlists as $playlist) {
                $playlist_label = htmlspecialchars($playlist['name'], ENT_QUOTES, 'UTF-8');
                $badge_class = $playlist['active'] ? 'bg-success' : 'bg-secondary';
                $default_text = $playlist['active'] ? ' <i class="bi bi-star-fill"></i> Mặc định' : '';
                $playlist_badges[] = '<span class="badge '.$badge_class.' me-1 mb-1">'
                    .$playlist_label.$default_text.'</span>';
            }
            $playlist_choices_html = '<div class="mt-2 p-2 border rounded bg-light">'
                .'<small class="text-dark"><b><i class="bi bi-music-note-list"></i> PlayList XiaoZhi có thể chọn:</b></small><br>'
                .implode('', $playlist_badges)
                .'<div class="small text-muted mt-1">Người dùng có thể yêu cầu phát đúng tên PlayList; nếu không nêu tên sẽ dùng PlayList mặc định.</div>'
                .'</div>';
        } else {
            $playlist_choices_html = '<div class="alert alert-warning py-1 px-2 mt-2 mb-0 small">Chưa có PlayList hợp lệ để XiaoZhi lựa chọn.</div>';
        }
    }
    echo "<tr>
            <th scope='row' style='text-align: center; vertical-align: middle;'>{$stt_mcp}</th>
            <td style='vertical-align: middle;' class='text-success'><b>{$name}</b></td>
            <td style='text-align: center; vertical-align: middle;'>
              <div class='form-switch'>
                <input class='form-check-input border-success' type='checkbox' name='{$name}' id='{$name}' {$active}>
              </div>
            </td>
            <td style='vertical-align: middle;' class='text-primary'>
              <span class='builtin-mcp-description-text'>{$description}</span>
              <button type='button' class='btn btn-sm btn-outline-success ms-2 builtin-mcp-description-edit'
                      title='Chỉnh sửa mô tả MCP' data-tool='{$name}' data-description='{$description}'>
                <i class='bi bi-pencil-square'></i>
              </button>
              {$playlist_choices_html}
            </td>
          </tr>";
    $stt_mcp++;
}

echo '</tbody></table></div>';

echo '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">';
echo '<h5 class="card-title mb-0" id="mcp-custom-section">MCP Plugins do người dùng tự viết</h5>';
echo '<button type="button" class="btn btn-success rounded-pill" id="open_create_mcp_plugin_modal">'
    .'<i class="bi bi-plus-circle"></i> Tạo MCP mới</button>';
echo '</div><hr>';
if (empty($MCP_plugins)) {
    echo '<div class="alert alert-secondary">Chưa có MCP plugin trong thư mục resource/xiaozhi/mcp_plugins.</div>';
} else {
    echo '<div class="table-responsive"><table class="table table-bordered border-success" id="mcp-custom-table">';
    echo '<thead>
            <tr>
              <th class="text-danger" style="text-align: center; vertical-align: middle;">STT</th>
              <th class="text-danger" style="text-align: center; vertical-align: middle;">Tên MCP Plugin/Tools</th>
              <th class="text-danger" style="text-align: center; vertical-align: middle;">Trạng Thái Kích Hoạt</th>
              <th class="text-danger" style="text-align: center; vertical-align: middle;">File Plugin</th>
              <th class="text-danger" style="text-align: center; vertical-align: middle;">Mô Tả</th>
              <th class="text-danger" style="text-align: center; vertical-align: middle;">Thao Tác</th>
            </tr>
          </thead><tbody>';
    $plugin_index = 1;
    foreach ($MCP_plugins as $plugin) {
        $raw_directory = $plugin['_directory'];
        $raw_tool_name = $plugin['tool']['name'];
        $directory = htmlspecialchars($raw_directory, ENT_QUOTES, 'UTF-8');
        $tool_name = htmlspecialchars($raw_tool_name, ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($plugin['tool']['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $checked = !empty($plugin['active']) ? 'checked' : '';
        $field_name = 'mcp_plugin__'.$directory;
        $file_buttons = '';
        foreach ($plugin['_files'] as $plugin_file_name => $plugin_file_content) {
            $safe_file_name = htmlspecialchars($plugin_file_name, ENT_QUOTES, 'UTF-8');
            $file_icon = strtolower(pathinfo($plugin_file_name, PATHINFO_EXTENSION)) === 'py'
                ? 'bi-filetype-py'
                : 'bi-filetype-json';
            $file_buttons .= "<button type='button' class='btn btn-sm btn-outline-primary m-1 mcp-plugin-file-btn'
                data-plugin='{$directory}' data-file='{$safe_file_name}'>
                <i class='bi {$file_icon}'></i> {$safe_file_name}
              </button>";
        }
        if ($file_buttons === '') {
            $file_buttons = '<small class="text-muted">Không có file .py/.json</small>';
        }
        $is_protected_plugin = in_array($raw_tool_name, $protected_mcp_tool_names, true)
            || in_array($raw_directory, $protected_mcp_directories, true);
        $delete_button = $is_protected_plugin
            ? '<span class="badge bg-danger"><i class="bi bi-lock-fill"></i> MCP mặc định</span>'
            : "<button type='button' class='btn btn-sm btn-outline-danger mcp-plugin-delete-btn'"
                ." data-plugin='{$directory}' data-tool='{$tool_name}'>"
                ."<i class='bi bi-trash'></i> Xóa MCP</button>";
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
                <td style='text-align:center;vertical-align:middle;'>{$file_buttons}</td>
                <td style='vertical-align:middle;' class='text-primary'>{$description}</td>
                <td style='text-align:center;vertical-align:middle;'>{$delete_button}</td>
              </tr>";
        $plugin_index++;
    }
    echo '</tbody></table></div>';
}
?>
<div class="row mb-3" id="mcp-actions-section">
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
    <div class="modal fade" id="myModal_MCP_Plugin_Editor" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" id="modal_dialog_mcp_plugin_editor" role="document">
            <div class="modal-content">
                <form method="post" action="" id="mcp_plugin_editor_form">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary">
                            <i class="bi bi-pencil-square"></i>
                            Chỉnh sửa: <span id="mcp_plugin_editor_title"></span>
                        </h5>
                        <button type="button" class="close btn btn-danger" onclick="$('#myModal_MCP_Plugin_Editor').modal('hide');">
                            <i class="bi bi-x-circle-fill"></i> Đóng
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="mcp_plugin_directory" id="mcp_plugin_editor_directory">
                        <input type="hidden" name="mcp_plugin_file" id="mcp_plugin_editor_file">
                        <textarea name="mcp_plugin_file_content" id="mcp_plugin_file_content"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="save_mcp_plugin_file" id="mcp_plugin_editor_save" class="btn btn-primary rounded-pill">
                            <i class="bi bi-save"></i> Lưu File
                        </button>
                        <button type="button" class="btn btn-secondary rounded-pill" onclick="$('#myModal_MCP_Plugin_Editor').modal('hide');">
                            <i class="bi bi-x-circle"></i> Đóng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="myModal_Builtin_MCP_Description" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="builtin_mcp_description_form">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary"><i class="bi bi-pencil-square"></i> Chỉnh sửa mô tả: <span id="builtin_mcp_description_title"></span></h5>
                        <button type="button" class="close btn btn-danger" onclick="$('#myModal_Builtin_MCP_Description').modal('hide');"><i class="bi bi-x-circle-fill"></i> Đóng</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="builtin_mcp_tool_name" id="builtin_mcp_tool_name">
                        <textarea class="form-control border-primary" rows="10" maxlength="4000" required
                                  name="builtin_mcp_description" id="builtin_mcp_description"></textarea>
                        <div class="form-text">Mô tả này được gửi tới server XiaoZhi để hướng dẫn khi nào và cách gọi MCP. Tối đa 4000 ký tự.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="builtin_mcp_description_save" class="btn btn-primary rounded-pill"><i class="bi bi-save"></i> Lưu mô tả</button>
                        <button type="button" class="btn btn-secondary rounded-pill" onclick="$('#myModal_Builtin_MCP_Description').modal('hide');">Đóng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="myModal_Create_MCP_Plugin" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="create_mcp_plugin_form">
                    <div class="modal-header">
                        <h5 class="modal-title text-success">
                            <i class="bi bi-plus-circle"></i> Tạo MCP Plugin mới
                        </h5>
                        <button type="button" class="close btn btn-danger" onclick="$('#myModal_Create_MCP_Plugin').modal('hide');">
                            <i class="bi bi-x-circle-fill"></i> Đóng
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            WebUI sẽ tạo thư mục MCP với hai file chuẩn <b>handler.py</b> và <b>plugin.json</b>.
                            Plugin mới mặc định chưa kích hoạt.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="mcp_create_directory">Tên MCP/thư mục</label>
                                <input class="form-control border-success" id="mcp_create_directory"
                                    name="mcp_create_directory" required maxlength="64"
                                    pattern="[A-Za-z][A-Za-z0-9_-]*" placeholder="vi_du: weather_local">
                                <div class="form-text">File Python luôn được tạo với tên: <b>handler.py</b></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mcp_create_tool_name">Tool name</label>
                                <input class="form-control border-success" id="mcp_create_tool_name"
                                    name="mcp_create_tool_name" required maxlength="128"
                                    placeholder="self.vbot.weather_local">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="mcp_create_description">Diễn giải MCP</label>
                                <textarea class="form-control border-primary" id="mcp_create_description"
                                    name="mcp_create_description" required maxlength="500" rows="3"
                                    placeholder="Mô tả rõ MCP này dùng để làm gì để XiaoZhi lựa chọn đúng tool"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mcp_create_parameter_name">Tên input/tham số</label>
                                <input class="form-control" id="mcp_create_parameter_name"
                                    name="mcp_create_parameter_name" maxlength="64"
                                    pattern="[A-Za-z_][A-Za-z0-9_]*" placeholder="ví dụ: query">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mcp_create_parameter_description">Diễn giải input</label>
                                <input class="form-control" id="mcp_create_parameter_description"
                                    name="mcp_create_parameter_description" maxlength="300"
                                    placeholder="Nội dung người dùng cần cung cấp">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="mcp_create_parameter_required" name="mcp_create_parameter_required" checked>
                                    <label class="form-check-label" for="mcp_create_parameter_required">Input bắt buộc</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mcp_create_timeout">Timeout (giây)</label>
                                <input class="form-control" type="number" id="mcp_create_timeout"
                                    name="mcp_create_timeout" min="1" max="60" value="12" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success rounded-pill" id="create_mcp_plugin_submit">
                            <i class="bi bi-folder-plus"></i> Tạo MCP
                        </button>
                        <button type="button" class="btn btn-secondary rounded-pill" onclick="$('#myModal_Create_MCP_Plugin').modal('hide');">
                            Đóng
                        </button>
                    </div>
                </form>
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
    <script src="assets/vendor/codemirror/codemirror.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <script src="assets/vendor/codemirror/python.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <script src="assets/vendor/codemirror/javascript.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <script>
        var mcpPluginFiles = <?php
            $plugin_editor_files = [];
            foreach ($MCP_plugins as $plugin) {
                $plugin_editor_files[$plugin['_directory']] = $plugin['_files'];
            }
            echo json_encode(
                $plugin_editor_files,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
            );
        ?>;
        var mcpPluginEditor = CodeMirror.fromTextArea(
            document.getElementById('mcp_plugin_file_content'),
            {
                mode: 'python',
                theme: 'dracula',
                lineNumbers: true,
                indentUnit: 4,
                tabSize: 4,
                lineWrapping: false
            }
        );
        var mcpPluginSavedContent = '';
        var mcpPluginContentLoaded = false;
        //Luôn gọi thẳng PHP WebUI; không phụ thuộc URL hiện tại hoặc API VBot Python.
        var mcpPluginPhpEndpoint = 'XiaoZhi_MCP.php';
        var currentBuiltinMcpDescriptionButton = null;

        document.querySelectorAll('.builtin-mcp-description-edit').forEach(function(button) {
            button.addEventListener('click', function() {
                currentBuiltinMcpDescriptionButton = this;
                document.getElementById('builtin_mcp_tool_name').value = this.dataset.tool || '';
                document.getElementById('builtin_mcp_description_title').textContent = this.dataset.tool || '';
                document.getElementById('builtin_mcp_description').value = this.dataset.description || '';
                $('#myModal_Builtin_MCP_Description').modal('show');
                setTimeout(function() {
                    document.getElementById('builtin_mcp_description').focus();
                }, 250);
            });
        });

        document.getElementById('builtin_mcp_description_form').addEventListener('submit', function(event) {
            event.preventDefault();
            if (!this.reportValidity()) return;
            var saveButton = document.getElementById('builtin_mcp_description_save');
            var originalButtonHtml = saveButton.innerHTML;
            var formData = new FormData(this);
            formData.append('save_builtin_mcp_description', '1');
            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';
            fetch(mcpPluginPhpEndpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''}
            })
            .then(function(response) {
                return response.text().then(function(responseText) {
                    var result;
                    try { result = JSON.parse(responseText); }
                    catch (error) { throw new Error('PHP trả về dữ liệu không phải JSON (HTTP ' + response.status + ')'); }
                    if (!response.ok || !result.success) throw new Error(result.message || ('HTTP ' + response.status));
                    return result;
                });
            })
            .then(function(result) {
                if (currentBuiltinMcpDescriptionButton) {
                    currentBuiltinMcpDescriptionButton.dataset.description = result.description;
                    var descriptionElement = currentBuiltinMcpDescriptionButton.parentElement.querySelector('.builtin-mcp-description-text');
                    if (descriptionElement) descriptionElement.textContent = result.description;
                }
                $('#myModal_Builtin_MCP_Description').modal('hide');
                showMessagePHP(result.message, 3);
            })
            .catch(function(error) {
                showMessagePHP('Lỗi lưu mô tả MCP: ' + error.message, 5);
            })
            .finally(function() {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonHtml;
            });
        });

        var createMcpDirectoryInput = document.getElementById('mcp_create_directory');
        var createMcpToolNameInput = document.getElementById('mcp_create_tool_name');
        var createMcpToolNameEdited = false;

        document.getElementById('open_create_mcp_plugin_modal').addEventListener('click', function() {
            $('#myModal_Create_MCP_Plugin').modal('show');
            setTimeout(function() {
                createMcpDirectoryInput.focus();
            }, 250);
        });

        createMcpToolNameInput.addEventListener('input', function() {
            createMcpToolNameEdited = this.value.trim() !== '';
        });

        createMcpDirectoryInput.addEventListener('input', function() {
            var directoryName = this.value.trim();
            if (!createMcpToolNameEdited) {
                createMcpToolNameInput.value = directoryName
                    ? 'self.vbot.' + directoryName.replace(/-/g, '_')
                    : '';
            }
        });

        document.getElementById('create_mcp_plugin_form').addEventListener('submit', function(event) {
            event.preventDefault();
            if (!this.reportValidity()) {
                return;
            }
            var form = this;
            var submitButton = document.getElementById('create_mcp_plugin_submit');
            var originalButtonHtml = submitButton.innerHTML;
            var formData = new FormData(form);
            formData.append('create_mcp_plugin', '1');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo...';

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''}
            })
            .then(function(response) {
                return response.text().then(function(responseText) {
                    var result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (error) {
                        throw new Error('Máy chủ trả về dữ liệu không phải JSON (HTTP ' + response.status + ')');
                    }
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || ('HTTP ' + response.status));
                    }
                    return result;
                });
            })
            .then(function(result) {
                showMessagePHP(result.message, 3);
                $('#myModal_Create_MCP_Plugin').modal('hide');
                window.setTimeout(function() {
                    window.location.reload();
                }, 600);
            })
            .catch(function(error) {
                showMessagePHP('Lỗi tạo MCP: ' + error.message, 5);
            })
            .finally(function() {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonHtml;
            });
        });

        document.querySelectorAll('.mcp-plugin-delete-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                var pluginDirectory = this.getAttribute('data-plugin');
                var toolName = this.getAttribute('data-tool');
                if (!window.confirm(
                    'Bạn có chắc chắn muốn xóa MCP "' + toolName + '"?\n'
                    + 'Toàn bộ thư mục ' + pluginDirectory + ' và các file bên trong sẽ bị xóa.'
                )) {
                    return;
                }
                var deleteButton = this;
                var originalButtonHtml = deleteButton.innerHTML;
                var deleteData = new FormData();
                deleteData.append('delete_mcp_plugin', '1');
                deleteData.append('mcp_delete_directory', pluginDirectory);
                deleteButton.disabled = true;
                deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xóa...';

                fetch(window.location.href, {
                    method: 'POST',
                    body: deleteData,
                    credentials: 'same-origin',
                    headers: {'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''}
                })
                .then(function(response) {
                    return response.text().then(function(responseText) {
                        var result;
                        try {
                            result = JSON.parse(responseText);
                        } catch (error) {
                            throw new Error('Máy chủ trả về dữ liệu không phải JSON (HTTP ' + response.status + ')');
                        }
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || ('HTTP ' + response.status));
                        }
                        return result;
                    });
                })
                .then(function(result) {
                    showMessagePHP(result.message, 3);
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 500);
                })
                .catch(function(error) {
                    deleteButton.disabled = false;
                    deleteButton.innerHTML = originalButtonHtml;
                    showMessagePHP('Lỗi xóa MCP: ' + error.message, 5);
                });
            });
        });

        function mcpPluginEditorHasUnsavedChanges() {
            return mcpPluginContentLoaded && mcpPluginEditor.getValue() !== mcpPluginSavedContent;
        }

        document.querySelectorAll('.mcp-plugin-file-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                var plugin = this.getAttribute('data-plugin');
                var file = this.getAttribute('data-file');
                var currentPlugin = document.getElementById('mcp_plugin_editor_directory').value;
                var currentFile = document.getElementById('mcp_plugin_editor_file').value;
                if (
                    (currentPlugin !== plugin || currentFile !== file)
                    && mcpPluginEditorHasUnsavedChanges()
                    && !window.confirm('Nội dung hiện tại chưa được lưu. Bạn có chắc muốn mở file khác?')
                ) {
                    return;
                }
                if (!mcpPluginFiles[plugin] || !(file in mcpPluginFiles[plugin])) {
                    show_message('Không thể đọc nội dung file plugin');
                    return;
                }
                document.getElementById('mcp_plugin_editor_directory').value = plugin;
                document.getElementById('mcp_plugin_editor_file').value = file;
                document.getElementById('mcp_plugin_editor_title').textContent = plugin + '/' + file;
                mcpPluginEditor.setOption('mode', file.toLowerCase().endsWith('.json') ? {name: 'javascript', json: true} : 'python');
                $('#myModal_MCP_Plugin_Editor').modal('show');

                if (typeof mcpPluginFiles[plugin][file] === 'string') {
                    mcpPluginEditor.setValue(mcpPluginFiles[plugin][file]);
                    mcpPluginSavedContent = mcpPluginFiles[plugin][file];
                    mcpPluginContentLoaded = true;
                    mcpPluginEditor.clearHistory();
                    setTimeout(function() {
                        mcpPluginEditor.refresh();
                        mcpPluginEditor.focus();
                    }, 250);
                    return;
                }

                var selectedButton = this;
                var originalButtonHtml = selectedButton.innerHTML;
                var loadData = new FormData();
                loadData.append('load_mcp_plugin_file', '1');
                loadData.append('mcp_plugin_directory', plugin);
                loadData.append('mcp_plugin_file', file);
                selectedButton.disabled = true;
                mcpPluginContentLoaded = false;
                mcpPluginEditor.setValue('Đang tải nội dung file...');
                fetch(mcpPluginPhpEndpoint, {
                    method: 'POST',
                    body: loadData,
                    credentials: 'same-origin',
                    headers: {'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''}
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function(result) {
                    if (!result.success) {
                        throw new Error(result.message || 'Không thể đọc file');
                    }
                    mcpPluginFiles[plugin][file] = result.content;
                    if (
                        document.getElementById('mcp_plugin_editor_directory').value === plugin
                        && document.getElementById('mcp_plugin_editor_file').value === file
                    ) {
                        mcpPluginEditor.setValue(result.content);
                        mcpPluginSavedContent = result.content;
                        mcpPluginContentLoaded = true;
                        mcpPluginEditor.clearHistory();
                    }
                })
                .catch(function(error) {
                    if (
                        document.getElementById('mcp_plugin_editor_directory').value === plugin
                        && document.getElementById('mcp_plugin_editor_file').value === file
                    ) {
                        mcpPluginEditor.setValue('');
                        mcpPluginSavedContent = '';
                        mcpPluginContentLoaded = false;
                    }
                    showMessagePHP('Lỗi đọc file: ' + error.message, 5);
                })
                .finally(function() {
                    selectedButton.disabled = false;
                    selectedButton.innerHTML = originalButtonHtml;
                    mcpPluginEditor.refresh();
                    mcpPluginEditor.focus();
                });
                setTimeout(function() {
                    mcpPluginEditor.refresh();
                }, 250);
            });
        });

        document.getElementById('mcp_plugin_editor_form').addEventListener('submit', function(event) {
            event.preventDefault();
            mcpPluginEditor.save();

            var form = this;
            var saveButton = document.getElementById('mcp_plugin_editor_save');
            var originalButtonHtml = saveButton.innerHTML;
            var formData = new FormData(form);
            formData.append('save_mcp_plugin_file', '1');
            formData.append('mcp_plugin_ajax', '1');
            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';

            fetch(mcpPluginPhpEndpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''}
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function(result) {
                if (!result.success) {
                    throw new Error(result.message || 'Không thể lưu file');
                }
                var plugin = document.getElementById('mcp_plugin_editor_directory').value;
                var file = document.getElementById('mcp_plugin_editor_file').value;
                mcpPluginFiles[plugin][file] = mcpPluginEditor.getValue();
                mcpPluginSavedContent = mcpPluginEditor.getValue();
                mcpPluginContentLoaded = true;
                mcpPluginEditor.clearHistory();
                showMessagePHP(result.message, 3);
            })
            .catch(function(error) {
                showMessagePHP('Lỗi lưu file: ' + error.message, 5);
            })
            .finally(function() {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonHtml;
                mcpPluginEditor.focus();
            });
        });

        $('#myModal_MCP_Plugin_Editor').on('hide.bs.modal', function(event) {
            if (
                mcpPluginEditorHasUnsavedChanges()
                && !window.confirm('Nội dung chưa được lưu. Bạn có chắc muốn đóng trình soạn thảo?')
            ) {
                event.preventDefault();
            }
        });

        window.addEventListener('beforeunload', function(event) {
            if (!mcpPluginEditorHasUnsavedChanges()) {
                return;
            }
            event.preventDefault();
            event.returnValue = '';
        });
    </script>
    <script>
        function normalizeMcpSearchText(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase('vi')
                .trim();
        }

        function initializeMcpPageNavigation() {
            const searchInput = document.getElementById('mcp-tool-search');
            const clearButton = document.getElementById('mcp-search-clear');
            const navigation = document.getElementById('mcp-quick-navigation');
            const emptyState = document.getElementById('mcp-search-empty');
            if (!searchInput || !clearButton || !navigation || !emptyState) {
                return;
            }

            const getRows = function() {
                return Array.from(document.querySelectorAll('#mcp-builtin-table tbody tr, #mcp-custom-table tbody tr'));
            };

            const getRowSearchText = function(row) {
                const fieldState = Array.from(row.querySelectorAll('input, select, textarea')).map(function(field) {
                    if (field.type === 'checkbox') {
                        return field.checked ? 'kích hoạt bật active enabled' : 'tắt inactive disabled';
                    }
                    return field.value || '';
                }).join(' ');
                return normalizeMcpSearchText(row.textContent + ' ' + fieldState);
            };

            const applySearch = function() {
                const query = normalizeMcpSearchText(searchInput.value);
                let visibleCount = 0;
                getRows().forEach(function(row) {
                    const visible = query === '' || getRowSearchText(row).includes(query);
                    row.classList.toggle('mcp-search-hidden', !visible);
                    if (visible) {
                        visibleCount += 1;
                    }
                });
                emptyState.style.display = query !== '' && visibleCount === 0 ? 'block' : 'none';
            };

            searchInput.addEventListener('input', applySearch);
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                applySearch();
                searchInput.focus();
            });
            navigation.addEventListener('change', function() {
                const target = document.getElementById(navigation.value);
                if (target) {
                    target.scrollIntoView({behavior: 'smooth', block: 'center'});
                    window.setTimeout(function() {
                        target.setAttribute('tabindex', '-1');
                        target.focus({preventScroll: true});
                    }, 250);
                }
                navigation.value = '';
            });

            document.getElementById('mcp-config-form')?.addEventListener('change', function(event) {
                if (event.target.matches('input, select, textarea')) {
                    applySearch();
                }
            });
            applySearch();
        }

        document.addEventListener('DOMContentLoaded', initializeMcpPageNavigation);
    </script>
  <?php
  include 'html_js.php';
  ?>

</body>

</html>
