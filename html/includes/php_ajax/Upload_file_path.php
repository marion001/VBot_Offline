<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

require_once __DIR__.'/Api_Helpers.php';
vbotApiInitialize(['POST']);
include '../../Configuration.php';
$allowedFileRoots = vbotApiAllowedRoots([$VBot_Offline, $directory_path]);

function vbotUploadSafeName($fileName)
{
    $fileName = basename(str_replace('\\', '/', (string)$fileName));
    $fileName = preg_replace('/[\x00-\x1F\x7F]/', '', $fileName);
    $fileName = preg_replace('/[^\p{L}\p{N} ._()-]/u', '_', $fileName);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $stem = pathinfo($fileName, PATHINFO_FILENAME);
    $stem = trim(preg_replace('/[.]+/', '_', (string)$stem), " .");
    if ($stem === '') {
        return '';
    }
    return $extension !== '' ? $stem.'.'.$extension : $stem;
}

function vbotUploadWithinLimit(array $file, $maxBytes)
{
    return isset($file['size'])
        && is_numeric($file['size'])
        && (int)$file['size'] > 0
        && (int)$file['size'] <= $maxBytes;
}

function vbotUploadEnsureDirectory($directory, array $allowedRoots)
{
    if (!is_dir($directory)) {
        $parentCandidate = dirname(rtrim($directory, '/\\'));
        while (!is_dir($parentCandidate) && dirname($parentCandidate) !== $parentCandidate) {
            $parentCandidate = dirname($parentCandidate);
        }
        $parent = realpath($parentCandidate);
        if ($parent === false || !vbotApiPathIsInside($parent, $allowedRoots)) {
            return false;
        }
        if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
            return false;
        }
    }
    $resolved = vbotApiResolveExistingPath($directory, $allowedRoots, 'directory');
    if ($resolved !== false) {
        @chmod($resolved, 0777);
    }
    return $resolved;
}

function vbotUploadAudioMimeIsAllowed($temporaryFile, $extension)
{
    $allowedMimes = [
        'mp3' => ['audio/mpeg', 'audio/mp3', 'application/octet-stream'],
        'wav' => ['audio/wav', 'audio/x-wav', 'audio/vnd.wave', 'application/octet-stream'],
        'ogg' => ['audio/ogg', 'application/ogg', 'application/octet-stream'],
        'aac' => ['audio/aac', 'audio/x-aac', 'application/octet-stream']
    ];
    if (!isset($allowedMimes[$extension]) || !is_uploaded_file($temporaryFile)) {
        return false;
    }
    if (!function_exists('finfo_open')) {
        return true;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return false;
    }
    $mime = finfo_file($finfo, $temporaryFile);
    finfo_close($finfo);
    if (!in_array($mime, $allowedMimes[$extension], true)) {
        return false;
    }
    $handle = @fopen($temporaryFile, 'rb');
    if ($handle === false) {
        return false;
    }
    $header = fread($handle, 12);
    fclose($handle);
    if ($extension === 'mp3') {
        return substr($header, 0, 3) === 'ID3'
            || (strlen($header) >= 2 && ord($header[0]) === 0xFF && (ord($header[1]) & 0xE0) === 0xE0);
    }
    if ($extension === 'wav') {
        return substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WAVE';
    }
    if ($extension === 'ogg') {
        return substr($header, 0, 4) === 'OggS';
    }
    if ($extension === 'aac') {
        return strlen($header) >= 2 && ord($header[0]) === 0xFF && in_array(ord($header[1]) & 0xF6, [0xF0, 0xF2], true);
    }
    return false;
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
vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));

if (isset($_POST['upload_Music_Local'])) {
    $targetDirectory = $VBot_Offline . $Config['media_player']['music_local']['path'].'/';
    $targetDirectory = vbotUploadEnsureDirectory($targetDirectory, $allowedFileRoots);
    if ($targetDirectory === false) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Thư mục upload không hợp lệ.'], 500);
    }
    $targetDirectory .= DIRECTORY_SEPARATOR;
    if (!empty($_FILES['fileUpload']['name'][0])) {
        $messages = [];
        $success = true;
        foreach ($_FILES['fileUpload']['name'] as $index => $fileName) {
            $fileName = vbotUploadSafeName($fileName);
            $fileTmpName = $_FILES['fileUpload']['tmp_name'][$index];
            $fileSize = $_FILES['fileUpload']['size'][$index];
            $fileError = $_FILES['fileUpload']['error'][$index];
            if ($fileError === UPLOAD_ERR_OK) {
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($fileName === '' || !vbotUploadWithinLimit(['size' => $fileSize], 104857600)) {
                    $success = false;
                    $messages[] = 'Tên file rỗng hoặc dung lượng vượt quá 100 MB.';
                } elseif (
                    in_array($fileExtension, $Allowed_Extensions_Audio, true)
                    && vbotUploadAudioMimeIsAllowed($fileTmpName, $fileExtension)
                ) {
                    $fileName = strtolower($fileName);
                    $filePath = $targetDirectory.$fileName;
                    if (move_uploaded_file($fileTmpName, $filePath)) {
                        @chmod($filePath, 0777);
                        $messages[] = 'Tải lên thành công: ' . $fileName;
                    } else {
                        $success = false;
                        $messages[] = 'Không thể di chuyển file: ' . $fileName;
                    }
                } else {
                    $success = false;
                    $messages[] = 'Định dạng file không hợp lệ: ' . $fileName;
                }
            } else {
                $success = false;
                $messages[] = 'Lỗi tải lên file: ' . $fileName;
            }
        }
        vbotApiJsonResponse(['success' => $success, 'messages' => $messages], $success ? 200 : 500);
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không có file nào được chọn'], 400);
    }
}

if (isset($_POST['upload_Sound_Welcome'])) {
    $targetDirectory = $VBot_Offline . 'resource/sound/welcome/';
    $targetDirectory = vbotUploadEnsureDirectory($targetDirectory, $allowedFileRoots);
    if ($targetDirectory === false) {
        vbotApiJsonResponse(['success' => false, 'message' => 'Thư mục upload không hợp lệ.'], 500);
    }
    $targetDirectory .= DIRECTORY_SEPARATOR;
    if (!empty($_FILES['fileUpload']['name'][0])) {
        $messages = [];
        $success = true;
        foreach ($_FILES['fileUpload']['name'] as $index => $fileName) {
            $fileName = vbotUploadSafeName($fileName);
            $fileTmpName = $_FILES['fileUpload']['tmp_name'][$index];
            $fileSize = $_FILES['fileUpload']['size'][$index];
            $fileError = $_FILES['fileUpload']['error'][$index];
            if ($fileError === UPLOAD_ERR_OK) {
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($fileName === '' || !vbotUploadWithinLimit(['size' => $fileSize], 20971520)) {
                    $success = false;
                    $messages[] = 'Tên file rỗng hoặc dung lượng vượt quá 20 MB.';
                } elseif (
                    in_array($fileExtension, $Allowed_Extensions_Audio, true)
                    && vbotUploadAudioMimeIsAllowed($fileTmpName, $fileExtension)
                ) {
                    $fileName = strtolower($fileName);
                    $filePath = $targetDirectory.$fileName;
                    if (move_uploaded_file($fileTmpName, $filePath)) {
                        @chmod($filePath, 0777);
                        $messages[] = 'Tải lên thành công: ' . $fileName;
                    } else {
                        $success = false;
                        $messages[] = 'Không thể di chuyển file: ' . $fileName;
                    }
                } else {
                    $success = false;
                    $messages[] = 'Định dạng file không hợp lệ: ' . $fileName;
                }
            } else {
                $success = false;
                $messages[] = 'Lỗi tải lên file: ' . $fileName;
            }
        }
        vbotApiJsonResponse(['success' => $success, 'messages' => $messages], $success ? 200 : 500);
    } else {
        vbotApiJsonResponse(['success' => false, 'message' => 'Không có file nào được chọn'], 400);
    }
}

#Tải lên hình ảnh avata
if (isset($_POST['upload_avata'])) {
    $response = [
        "success" => false,
        "message" => ""
    ];
    $target_dir = vbotUploadEnsureDirectory($directory_path.'/assets/img', $allowedFileRoots);
    $allowedImageMimes = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif']
    ];
    if ($target_dir !== false && isset($_FILES["fileToUpload_avata"])) {
        $uploadedImage = $_FILES["fileToUpload_avata"];
        $imageFileType = strtolower(pathinfo($uploadedImage["name"], PATHINFO_EXTENSION));
        if (
            $uploadedImage['error'] !== UPLOAD_ERR_OK
            || !vbotUploadWithinLimit($uploadedImage, 5242880)
            || !isset($allowedImageMimes[$imageFileType])
        ) {
            $response["message"] = "Ảnh không hợp lệ hoặc dung lượng vượt quá 5 MB.";
            vbotApiJsonResponse($response, 400);
        }
        $check = @getimagesize($uploadedImage["tmp_name"]);
        if ($check === false || !in_array($check['mime'], $allowedImageMimes[$imageFileType], true)) {
            $response["message"] = "Nội dung hoặc định dạng hình ảnh không hợp lệ.";
            vbotApiJsonResponse($response, 400);
        }
        $target_file = $target_dir.DIRECTORY_SEPARATOR."avata_user.".$imageFileType;
        if (move_uploaded_file($uploadedImage["tmp_name"], $target_file)) {
            foreach (glob($target_dir.DIRECTORY_SEPARATOR."avata_user.*") ?: [] as $oldAvatar) {
                if ($oldAvatar !== $target_file && is_file($oldAvatar)) {
                    @unlink($oldAvatar);
                }
            }
            @chmod($target_file, 0777);
            $response["success"] = true;
            $response["message"] = "Tệp đã được tải lên thành công với tên mới: avata_user.".$imageFileType;
        } else {
            $response["message"] = "Xin lỗi, đã xảy ra lỗi khi tải lên tệp của bạn.";
        }
        vbotApiJsonResponse($response, $response['success'] ? 200 : 500);
    }
    $response["message"] = "Thiếu file ảnh hoặc thư mục lưu avatar không hợp lệ.";
    vbotApiJsonResponse($response, 400);
}

#Tải lên file json PlayList
if (isset($_POST['json_file_playlist'])) {
    $response = [
        "success" => false,
        "message" => ""
    ];
    $targetDirectory = vbotApiResolveExistingPath(
        $VBot_Offline."html/includes/cache",
        $allowedFileRoots,
        'directory'
    );
    $target_file = $targetDirectory !== false
        ? $targetDirectory.DIRECTORY_SEPARATOR."PlayList.json"
        : false;
    if (!isset($_FILES["select_json_file_playlist"])) {
        $response["message"] = "Không có file!";
    }
    elseif (
        $_FILES["select_json_file_playlist"]["error"] !== UPLOAD_ERR_OK
        || !vbotUploadWithinLimit($_FILES["select_json_file_playlist"], 5242880)
        || strtolower(pathinfo($_FILES["select_json_file_playlist"]["name"], PATHINFO_EXTENSION)) !== 'json'
    ) {
        $response["message"] = "Lỗi tải lên file!";
    }
    elseif ($target_file === false) {
        $response["message"] = "Thư mục lưu PlayList không hợp lệ!";
    }
    else {
        $content = @file_get_contents($_FILES["select_json_file_playlist"]["tmp_name"]);
        $playlistData = is_string($content) ? json_decode($content, true) : null;
        if (
            json_last_error() !== JSON_ERROR_NONE
            || !is_array($playlistData)
            || !isset($playlistData['data'])
            || !is_array($playlistData['data'])
            || count($playlistData['data']) > 5000
        ) {
            $response["message"] = "File JSON không hợp lệ!";
        }
	elseif (
        ($normalizedContent = json_encode($playlistData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false
        && @file_put_contents($target_file, $normalizedContent, LOCK_EX) !== false
    ) {
		$original_name = vbotUploadSafeName($_FILES["select_json_file_playlist"]["name"]);
        @chmod($target_file, 0777);
		$response["success"] = true;
		$response["message"] = "Tải lên thành công: " . $original_name . " -> PlayList.json";
	}
        else {
            $response["message"] = "Không thể lưu file!";
        }
    }
    vbotApiJsonResponse($response, $response['success'] ? 200 : 500);
}
?>
