<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include '../../Configuration.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (
        !isset($_SESSION['user_login']) ||
        (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
    ) {
        session_unset();
        session_destroy();
        echo json_encode([
            'success' => false,
            'message' => 'Thao tác bị chặn, chỉ cho phép thực hiện thao tác khi được đăng nhập vào WebUI VBot'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (isset($_GET['upload_Music_Local'])) {
    $targetDirectory = $VBot_Offline . $Config['media_player']['music_local']['path'].'/';
    if (!file_exists($targetDirectory)) {
        mkdir($targetDirectory, 0777, true);
    }
    if (!empty($_FILES['fileUpload']['name'][0])) {
        $messages = [];
        $success = true;
        foreach ($_FILES['fileUpload']['name'] as $index => $fileName) {
            $fileTmpName = $_FILES['fileUpload']['tmp_name'][$index];
            $fileSize = $_FILES['fileUpload']['size'][$index];
            $fileError = $_FILES['fileUpload']['error'][$index];
            $fileType = $_FILES['fileUpload']['type'][$index];
            if ($fileError === UPLOAD_ERR_OK) {
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (in_array($fileExtension, $Allowed_Extensions_Audio)) {
                    $fileName = strtolower($fileName);
                    $filePath = $targetDirectory . basename($fileName);
                    if (move_uploaded_file($fileTmpName, $filePath)) {
						shell_exec('chmod 0777 ' . escapeshellarg($filePath));
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
        echo json_encode(['success' => $success, 'messages' => $messages]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không có file nào được chọn']);
    }
    exit();
}

if (isset($_GET['upload_Sound_Welcome'])) {
    $targetDirectory = $VBot_Offline . 'resource/sound/welcome/';
    if (!file_exists($targetDirectory)) {
        mkdir($targetDirectory, 0777, true);
    }
    if (!empty($_FILES['fileUpload']['name'][0])) {
        $messages = [];
        $success = true;
        foreach ($_FILES['fileUpload']['name'] as $index => $fileName) {
            $fileTmpName = $_FILES['fileUpload']['tmp_name'][$index];
            $fileSize = $_FILES['fileUpload']['size'][$index];
            $fileError = $_FILES['fileUpload']['error'][$index];
            $fileType = $_FILES['fileUpload']['type'][$index];
            if ($fileError === UPLOAD_ERR_OK) {
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (in_array($fileExtension, $Allowed_Extensions_Audio)) {
                    $fileName = strtolower($fileName);
                    $filePath = $targetDirectory . basename($fileName);
                    if (move_uploaded_file($fileTmpName, $filePath)) {
						shell_exec('chmod 0777 ' . escapeshellarg($filePath));
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
        echo json_encode(['success' => $success, 'messages' => $messages]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không có file nào được chọn']);
    }
    exit();
}

#Tải lên hình ảnh avata
if (isset($_GET['upload_avata'])) {
    $response = [
        "success" => false,
        "message" => ""
    ];
    $target_dir = "../../assets/img/";
    $allowed_image_types = ["jpg", "png", "jpeg", "gif"];
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["fileToUpload_avata"])) {
        foreach (glob($target_dir . "avata_user.*") as $file_path) {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $imageFileType = strtolower(pathinfo($_FILES["fileToUpload_avata"]["name"], PATHINFO_EXTENSION));
        $target_file = $target_dir . "avata_user." . $imageFileType;
        $uploadOk = 1;
        $check = getimagesize($_FILES["fileToUpload_avata"]["tmp_name"]);
        if ($check !== false) {
            $response["message"] = "File là hình ảnh - " . $check["mime"] . ".";
        } else {
            $response["message"] = "File không phải là hình ảnh.";
            echo json_encode($response);
            exit();
        }
        if (!in_array($imageFileType, $allowed_image_types)) {
            $response["message"] = "Chỉ cho phép tải lên các tệp hình ảnh JPG, JPEG, PNG & GIF.";
            echo json_encode($response);
            exit();
        }
        if ($uploadOk == 0) {
            $response["message"] = "Xin lỗi, tệp của bạn không được tải lên.";
        } else {
            if (move_uploaded_file($_FILES["fileToUpload_avata"]["tmp_name"], $target_file)) {
				shell_exec('chmod 0777 ' . escapeshellarg($target_file));
                $response["success"] = true;
                $response["message"] = "Tệp đã được tải lên thành công với tên mới: avata_user." . $imageFileType;
            } else {
                $response["message"] = "Xin lỗi, đã xảy ra lỗi khi tải lên tệp của bạn.";
            }
        }
        echo json_encode($response);
        exit();
    }
}
?>