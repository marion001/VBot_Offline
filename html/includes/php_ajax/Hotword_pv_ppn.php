<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['GET', 'POST']);
include '../../Configuration.php';

// Preserve UTF-8 file names regardless of the operating system locale.
function vbotUtf8Basename($path)
{
    $path = str_replace('\\', '/', (string) $path);
    $parts = explode('/', $path);
    return end($parts);
}

function vbotHotwordValidFile($fileName, array $extensions, $fileSize, $maxBytes = 5242880)
{
    $baseName = vbotUtf8Basename($fileName);
    return $baseName === $fileName
        && $baseName !== ''
        && mb_strlen($baseName) <= 150
        && !preg_match('/[\x00-\x1F\x7F<>:"|?*\/\\\\]/u', $baseName)
        && in_array(strtolower(pathinfo($baseName, PATHINFO_EXTENSION)), $extensions, true)
        && $fileSize > 0
        && $fileSize <= $maxBytes;
}

function vbotHotwordSaveConfig($configFilePath, array $configData)
{
    $encoded = json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false || file_put_contents($configFilePath, $encoded, LOCK_EX) === false) {
        error_log('Unable to save Hotword configuration: '.json_last_error_msg());
        return false;
    }
    @chmod($configFilePath, 0777);
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
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
        ], 401);
    }
}

#Lấy danh sách hotword, và lib theo tùy chọn lang trong Config.json và hiển thị
if (isset($_GET['hotword'])) {
    $lang_get_HOTWORD = isset($_GET['lang']) ? $_GET['lang'] : '';
    if ($lang_get_HOTWORD === 'vi' || $lang_get_HOTWORD === 'eng') {
        $directory = $VBot_Offline . 'resource/picovoice/library';
        $files = glob($directory . '/*.pv') ?: [];
        $file_list = array_map('basename', $files);
        $porcupineConfig = $Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang_get_HOTWORD];
        $response = ['lang' => $lang_get_HOTWORD, 'config' => $porcupineConfig, 'files_lib_pv' => $file_list, 'path_pv' => $directory . '/', 'path_ppn' => $VBot_Offline . 'resource/hotword/' . $lang_get_HOTWORD . '/', 'config_lib_pv_to_lang' => $Config['smart_config']['smart_wakeup']['hotword']['library'][$lang_get_HOTWORD]['modelFilePath']];
        vbotApiJsonResponse($response);
    } else if ($lang_get_HOTWORD === 'snowboy') {
        $directory = $VBot_Offline . 'resource/snowboy/hotword';
        $files = array_merge(
            glob($directory . '/*.pmdl') ?: [],
            glob($directory . '/*.umdl') ?: []
        );
        $file_list = array_map('basename', $files);
        $porcupineConfig = $Config['smart_config']['smart_wakeup']['hotword']['snowboy'];
        $response = ['lang' => $lang_get_HOTWORD, 'config' => $porcupineConfig, 'files_hotword' => $file_list, 'path_hotword' => $directory . '/'];
        vbotApiJsonResponse($response);
    } else {
        vbotApiJsonResponse(['success' => false, 'lang' => '', 'config' => [], 'files_lib_pv' => [], 'message' => 'Ngôn ngữ Hotword không hợp lệ'], 400);
    }
}

#Lấy danh sách câu phản hồi
if (isset($_GET['get_wakeup_reply'])) {
    if (!isset($Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'])) {
        vbotApiJsonResponse([
            'success' => false,
            'message' => 'Không tìm thấy danh sách file phản hồi.',
            'config' => []
        ], 404);
    }
    $allFiles = $Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'];
    vbotApiJsonResponse([
        'success' => true,
        'message' => 'Lấy danh sách câu phản hồi thành công.',
        'config' => $allFiles
    ]);
}

// Xử lý khi tải lên file ppn và pv xong cập nhật vào Config.json, nếu trùng tên file chỉ tải lên mà không sửa trong config
if (isset($_POST['action_ppn_pv']) && $_POST['action_ppn_pv'] === 'upload_files_ppn_pv') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $uploadDirLibrary = $VBot_Offline . 'resource/picovoice/library/';
    $uploadDirHotword = $VBot_Offline . 'resource/hotword/';
    $lang = isset($_POST['lang_hotword_get']) ? trim($_POST['lang_hotword_get']) : '';
    if (!in_array($lang, ['vi', 'eng'], true)) {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Ngôn ngữ Hotword không hợp lệ'], 400);
    }
    foreach ([$uploadDirLibrary, $uploadDirHotword.$lang.'/'] as $uploadDirectory) {
        if (!is_dir($uploadDirectory) && !@mkdir($uploadDirectory, 0777, true)) {
            vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không thể tạo thư mục tải lên Hotword'], 500);
        }
        @chmod($uploadDirectory, 0777);
    }
    if (file_exists($Config_filePath)) {
        $jsonContent = file_get_contents($Config_filePath);
        $configData = json_decode($jsonContent, true);
        if (!is_array($configData)) {
            vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Config.json không hợp lệ'], 500);
        }
        if (!isset($configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang])) {
            $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = [];
        }
        $existingFiles = array_column($configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang], 'file_name');
        $updatedConfig = $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang];
    } else {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không tìm thấy Config.json'], 404);
    }
    $responseMessages = [];
    if (
        !isset($_FILES['upload_files_ppn_pv']['error'])
        || !is_array($_FILES['upload_files_ppn_pv']['error'])
    ) {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không có file Hotword được tải lên'], 400);
    }
    $hasUploadError = false;
    foreach ($_FILES['upload_files_ppn_pv']['error'] as $key => $error) {
        if ($error == UPLOAD_ERR_OK) {
            $tmpName = $_FILES['upload_files_ppn_pv']['tmp_name'][$key];
            $name = vbotUtf8Basename($_FILES['upload_files_ppn_pv']['name'][$key]);
            $fileSize = (int)$_FILES['upload_files_ppn_pv']['size'][$key];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!is_uploaded_file($tmpName) || !vbotHotwordValidFile($name, ['ppn', 'pv'], $fileSize)) {
                $hasUploadError = true;
                $responseMessages[] = "File Hotword không hợp lệ: $name";
                continue;
            }
            if ($ext == 'pv') {
                $uploadFile = $uploadDirLibrary . $name;
                if (move_uploaded_file($tmpName, $uploadFile)) {
                    @chmod($uploadFile, 0777);
                    $responseMessages[] = "Tệp tin '$name' đã tải lên thành công";
                } else {
                    $hasUploadError = true;
                    $responseMessages[] = "Không thể lưu file '$name'";
                }
            } elseif ($ext == 'ppn') {
                $uploadFile = $uploadDirHotword . $lang . '/' . $name;
                $moveResult = move_uploaded_file($tmpName, $uploadFile);
                if ($moveResult) {
                    @chmod($uploadFile, 0777);
                    if (in_array($name, $existingFiles)) {
                        $responseMessages[] = "Tệp tin: '$name' đã tải lên thành công vào '$uploadFile' nhưng đã tồn tại trong ngôn ngữ '$lang', không cần cập nhật Config.json \n";
                    } else {
                        $updatedConfig[] = ["active" => true, "file_name" => $name, "sensitive" => 0.5];
                        $responseMessages[] = "Tệp tin: '$name' đã tải lên thành công vào '$uploadFile' và thêm vào ngôn ngữ '$lang' trong Config.json \n";
                    }
                } else {
                    $hasUploadError = true;
                    $responseMessages[] = "Không thể tải tập tin lên '$name', hoặc không có full quyền hạn 0777";
                }
            } else {
                $hasUploadError = true;
                $responseMessages[] = "Loại tập tin không được hỗ trợ: $ext.";
            }
        } else {
            $hasUploadError = true;
            $responseMessages[] = "Lỗi tải file lên, cho file $key với mã lỗi: $error.";
        }
    }
    //Cập nhật mảng cấu hình
    if (!empty($updatedConfig)) {
        $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = $updatedConfig;
        if (!vbotHotwordSaveConfig($Config_filePath, $configData)) {
            vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không thể lưu Config.json'], 500);
        }
    }
    vbotApiJsonResponse([
        'status' => $hasUploadError ? 'error' : 'success',
        'success' => !$hasUploadError,
        'messages' => $responseMessages
    ], $hasUploadError ? 400 : 200);
}

#Cập nhật hotword snowboy khi được tải lên
if (isset($_POST['action_hotword_snowboy']) && $_POST['action_hotword_snowboy'] === 'upload_files_hotword_snowboy') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $uploadDirHotword = $VBot_Offline . 'resource/snowboy/hotword/';
    if (file_exists($Config_filePath)) {
        $jsonContent = file_get_contents($Config_filePath);
        $configData = json_decode($jsonContent, true);
        if (!is_array($configData)) {
            vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Config.json không hợp lệ'], 500);
        }
        if (!isset($configData['smart_config']['smart_wakeup']['hotword']['snowboy'])) {
            $configData['smart_config']['smart_wakeup']['hotword']['snowboy'] = [];
        }
        $existingFiles = array_column($configData['smart_config']['smart_wakeup']['hotword']['snowboy'], 'file_name');
        $updatedConfig = $configData['smart_config']['smart_wakeup']['hotword']['snowboy'];
    } else {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không tìm thấy Config.json'], 404);
    }
    if (!is_dir($uploadDirHotword)) {
        if (!@mkdir($uploadDirHotword, 0777, true)) {
            vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không thể tạo thư mục Hotword Snowboy'], 500);
        }
    }
    @chmod($uploadDirHotword, 0777);
    $uploadSuccess = [];
    $uploadErrors = [];
    if (!empty($_FILES['upload_files_hotword_snowboy']['name'][0])) {
        foreach ($_FILES['upload_files_hotword_snowboy']['name'] as $key => $fileName) {
            $fileTmpPath = $_FILES['upload_files_hotword_snowboy']['tmp_name'][$key];
            $fileSize = $_FILES['upload_files_hotword_snowboy']['size'][$key];
            $fileError = $_FILES['upload_files_hotword_snowboy']['error'][$key];
            if ($fileError !== UPLOAD_ERR_OK) {
                $uploadErrors[] = "Lỗi tải file: $fileName (Mã lỗi: $fileError)";
                continue;
            }
            $name = vbotUtf8Basename($fileName);
            if (
                !is_uploaded_file($fileTmpPath)
                || !vbotHotwordValidFile($name, ['pmdl', 'umdl'], (int)$fileSize)
            ) {
                $uploadErrors[] = "File không hợp lệ: $fileName (Chỉ hỗ trợ .pmdl, .umdl)";
                continue;
            }
            $destinationPath = $uploadDirHotword . $name;
            if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                @chmod($destinationPath, 0777);
                $uploadSuccess[] = "Tệp tin '$fileName' đã tải lên thành công vào: '$uploadDirHotword' và được thêm vào Config.json";
                if (!in_array($name, $existingFiles)) {
                    $updatedConfig[] = ["active" => true, "file_name" => $name, "sensitive" => 0.5];
                    //$uploadSuccess[] = "Thêm '$name' vào Config.json";
                }
            } else {
                $uploadErrors[] = "Không thể lưu file: $fileName";
            }
        }
        if (!empty($updatedConfig)) {
            $configData['smart_config']['smart_wakeup']['hotword']['snowboy'] = $updatedConfig;
            if (!vbotHotwordSaveConfig($Config_filePath, $configData)) {
                vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không thể lưu Config.json'], 500);
            }
        }
    } else {
        $uploadErrors[] = "Không có file nào được chọn để tải lên.";
    }
    vbotApiJsonResponse([
        "status" => empty($uploadErrors) ? "success" : "error",
        "success" => empty($uploadErrors),
        "messages" => array_merge($uploadSuccess, $uploadErrors)
    ], empty($uploadErrors) ? 200 : 400);
}

#cập nhật lại hotword picovoice eng và vi trong Config.json tương ứng với tất cả các file .ppn trong 2 thư mục eng và vi
if (isset($_POST['reload_hotword_config'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $directories = [
        $VBot_Offline . "resource/hotword/eng",
        $VBot_Offline . "resource/hotword/vi"
    ];
    $newPorcupineConfig = [
        'vi' => $Config['smart_config']['smart_wakeup']['hotword']['porcupine']['vi'] ?? [],
        'eng' => $Config['smart_config']['smart_wakeup']['hotword']['porcupine']['eng'] ?? []
    ];
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }
        $files = glob($directory . '/*.ppn') ?: [];
        foreach ($files as $file) {
            $parts = explode('/', $file);
            $fileName = end($parts);
            $lang = strpos($directory, 'eng') !== false ? 'eng' : 'vi';
            $exists = false;
            foreach ($newPorcupineConfig[$lang] as $item) {
                if (isset($item['file_name']) && $item['file_name'] === $fileName) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $newPorcupineConfig[$lang][] = ['active' => true, 'file_name' => $fileName, 'sensitive' => 0.5];
            }
        }
    }
    $Config['smart_config']['smart_wakeup']['hotword']['porcupine'] = $newPorcupineConfig;
    if (!vbotHotwordSaveConfig($Config_filePath, $Config)) {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Lỗi khi ghi file cấu hình Hotword tiếng anh và tiếng việt'], 500);
    }
    vbotApiJsonResponse(['status' => 'success', 'success' => true, 'message' => 'Đã ghi cấu hình Config->Hotword tiếng anh và tiếng việt thành công.']);
}

#Cập nhật lại hotword snowboy
if (isset($_POST['reload_hotword_config_snowboy'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $directory = $VBot_Offline . "resource/snowboy/hotword";
    $newSnowboyConfig = [];
    if (is_dir($directory)) {
        $files = array_merge(glob("$directory/*.pmdl") ?: [], glob("$directory/*.umdl") ?: []);
        foreach ($files as $file) {
            $parts = explode('/', $file);
            $fileName = end($parts);
            $newSnowboyConfig[] = [
                'active' => true,
                'file_name' => $fileName,
                'sensitive' => 0.5
            ];
        }
        $Config['smart_config']['smart_wakeup']['hotword']['snowboy'] = $newSnowboyConfig;
        if (!vbotHotwordSaveConfig($Config_filePath, $Config)) {
            vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Lỗi khi ghi file cấu hình Hotword Snowboy.'], 500);
        }
        vbotApiJsonResponse(['status' => 'success', 'success' => true, 'message' => 'Đã cập nhật cấu hình Hotword Snowboy thành công.']);
    } else {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Thư mục hotword không tồn tại.'], 404);
    }
}

//Cập nhật lại Dữ Liệu WakeUP Reply
if (isset($_POST['reload_wakeup_reply'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $relativePath = "resource/sound/wakeup_reply";
    $absolutePath = $VBot_Offline . $relativePath;
    $files = glob($absolutePath . "/*.mp3") ?: [];
    $soundFiles = [];
    foreach ($files as $file) {
        $parts = explode('/', $file);
        $fileName = end($parts);
        $soundFiles[] = [
            "file_name" => $relativePath . "/" . $fileName,
            "active" => true
        ];
    }
    if (!file_exists($Config_filePath)) {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không tìm thấy file cấu hình Config.json'], 404);
    }
    $configContent = file_get_contents($Config_filePath);
    $Config = json_decode($configContent, true);
    if (!is_array($Config)) {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Cấu hình danh sách Câu Phản Hồi WakeUP Reply không hợp lệ.'], 500);
    }
    $Config['smart_config']['smart_wakeup']['wakeup_reply'] = [
        "active" => true,
        "sound_file" => $soundFiles
    ];
    if (!vbotHotwordSaveConfig($Config_filePath, $Config)) {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không thể ghi danh sách Câu Phản Hồi WakeUP Reply vào file cấu hình.'], 500);
    }
    vbotApiJsonResponse(['status' => 'success', 'success' => true, 'message' => 'Đã cập nhật danh sách Câu Phản Hồi WakeUP Reply thành công.']);
}

#Tải lên file Wakeup Reply
if (isset($_POST['wakeup_reply_upload']) && $_POST['wakeup_reply_upload'] === 'upload_files_wakeup_reply') {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $targetDir = $VBot_Offline . 'resource/sound/wakeup_reply/';
    $response = ['status' => 'error', 'messages' => ['Không có file nào được xử lý.']];
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true)) {
        vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không thể tạo thư mục WakeUP Reply'], 500);
    }
    @chmod($targetDir, 0777);
    if (!empty($_FILES['upload_files_wakeup_reply'])) {
        $uploadedFiles = $_FILES['upload_files_wakeup_reply'];
        $successCount = 0;
        $successFiles = [];
        $errorMessages = [];
        for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
            $tmpName = $uploadedFiles['tmp_name'][$i];
            $fileName = vbotUtf8Basename($uploadedFiles['name'][$i]);
            $fileSize = isset($uploadedFiles['size'][$i]) ? (int)$uploadedFiles['size'][$i] : 0;
            $uploadError = isset($uploadedFiles['error'][$i]) ? $uploadedFiles['error'][$i] : UPLOAD_ERR_NO_FILE;
            $fileType = $uploadError === UPLOAD_ERR_OK && is_uploaded_file($tmpName)
                ? mime_content_type($tmpName)
                : false;
            if (
                $uploadError === UPLOAD_ERR_OK
                && is_uploaded_file($tmpName)
                && vbotHotwordValidFile($fileName, ['mp3'], $fileSize, 20971520)
                && in_array($fileType, ['audio/mpeg', 'audio/mp3'], true)
            ) {
                $destination = $targetDir . $fileName;
                if (move_uploaded_file($tmpName, $destination)) {
                    @chmod($destination, 0777);
                    $successCount++;
                    $successFiles[] = $fileName;
                } else {
                    $errorMessages[] = "Không thể lưu file: $fileName";
                }
            } else {
                $errorMessages[] = "$fileName không phải file .mp3 hợp lệ.";
            }
        }
        if ($successCount > 0) {
            $configContent = file_get_contents($Config_filePath);
            $Config = json_decode($configContent, true);
            if (!is_array($Config)) {
                vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Config.json không hợp lệ'], 500);
            }
            $existingFiles = $Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] ?? [];
            $existingFileNames = [];
            foreach ($existingFiles as $item) {
                if (isset($item['file_name'])) {
                    $existingFileNames[] = $item['file_name'];
                }
            }
            foreach ($successFiles as $fileName) {
                $relativePath = "resource/sound/wakeup_reply/" . $fileName;
                if (!in_array($relativePath, $existingFileNames)) {
                    $existingFiles[] = [
                        "file_name" => $relativePath,
                        "active" => true
                    ];
                }
            }
            $Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] = $existingFiles;
            if (!vbotHotwordSaveConfig($Config_filePath, $Config)) {
                vbotApiJsonResponse(['status' => 'error', 'success' => false, 'message' => 'Không thể lưu Config.json'], 500);
            }
            $response['status'] = 'success';
            $response['success'] = true;
            $filesList = implode(", ", $successFiles);
            $response['messages'] = ["Đã tải lên thành công {$successCount} file: {$filesList}."];
            if (!empty($errorMessages)) {
                $response['messages'] = array_merge($response['messages'], $errorMessages);
            }
        } else {
            $response['messages'] = $errorMessages;
        }
    } else {
        $response['messages'] = ['Không có tệp nào được gửi.'];
    }
    if (!isset($response['success'])) {
        $response['success'] = false;
    }
    vbotApiJsonResponse($response, $response['success'] ? 200 : 400);
}

#Lựa chọn file âm thanh dùng cho wakeup reply
if (isset($_POST['use_this_wakeup_reply_sound'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    $sourcePath = vbotApiResolveExistingPath(isset($_POST['file_path']) ? $_POST['file_path'] : '', vbotApiAllowedRoots([$VBot_Offline]), 'file');
    $destFolder = $VBot_Offline . 'resource/sound/wakeup_reply/';
    if ($sourcePath === false || strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) !== 'mp3') {
        vbotApiJsonResponse(["success" => false, "message" => "File không tồn tại"], 404);
    }
    $baseName = basename($sourcePath);
    $destPath = $destFolder . $baseName;
    $relativePath = 'resource/sound/wakeup_reply/' . $baseName;
    if (!is_dir($destFolder) && !@mkdir($destFolder, 0777, true)) {
        vbotApiJsonResponse(["success" => false, "message" => "Không thể tạo thư mục WakeUP Reply"], 500);
    }
    @chmod($destFolder, 0777);
    if (realpath($sourcePath) !== realpath($destPath) && !copy($sourcePath, $destPath)) {
        vbotApiJsonResponse(["success" => false, "message" => "Không thể sao chép file"], 500);
    }
    @chmod($destPath, 0777);
    if (!file_exists($Config_filePath)) {
        vbotApiJsonResponse(["success" => false, "message" => "Không tìm thấy Config.json"], 404);
    }
    $configData = json_decode(file_get_contents($Config_filePath), true);
    if (!is_array($configData)) {
        vbotApiJsonResponse(["success" => false, "message" => "Config.json không hợp lệ"], 500);
    }
    if (!isset($configData['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'])) {
        $configData['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] = [];
    }
    $soundFiles = &$configData['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'];
    $exists = false;
    foreach ($soundFiles as $entry) {
        if (isset($entry['file_name']) && $entry['file_name'] === $relativePath) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $soundFiles[] = [
            "file_name" => $relativePath,
            "active" => true
        ];
        if (!vbotHotwordSaveConfig($Config_filePath, $configData)) {
            vbotApiJsonResponse(["success" => false, "message" => "Không thể lưu Config.json"], 500);
        }
    }
    vbotApiJsonResponse([
        "success" => true,
        "message" => "Đã cập nhật danh sách câu phản hồi wakeup_reply"
    ]);
}
?>
