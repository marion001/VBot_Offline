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

$responseData = [
    'success' => false,
    'gcloud_notification' => '',
    'message' => '',
    'data' => []
];

if (!$google_cloud_drive_active === true) {
    $responseData['success'] = false;
    $responseData['gcloud_notification'] = "Cloud Backup -> Google Cloud Drive Không được Kích Hoạt Trong Config.json (backup_upgrade->google_cloud_drive->active)";
    $responseData['message'] = "Cloud Backup -> Google Cloud Drive Không được Kích Hoạt Trong Config.json (backup_upgrade->google_cloud_drive->active)";
    vbotApiJsonResponse($responseData, 503);
}

$authConfigPath = '../../includes/other_data/Google_Driver_PHP/client_secret.json';
$tokenPath = '../../includes/other_data/Google_Driver_PHP/verify_token.json';
$base_directory = '/home/' . $GET_current_USER . '/_VBot_Library';
$client_directory = $base_directory . '/google-api-php-client';
$LIB_Google_API_PHP_CLIENT = $client_directory . '/vendor/autoload.php';
$activve_show = true;

//Kiểm tra lại nếu tệp thư viện không tồn tại
if (!file_exists($LIB_Google_API_PHP_CLIENT)) {
    $activve_show = false;
    $responseData['success'] = false;
    $responseData['message'] = "Thư Viện Google Cloud Drive Chưa Được Cấu Hình, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình";
    vbotApiJsonResponse($responseData, 503);
} else {
    require_once $LIB_Google_API_PHP_CLIENT;
    $activve_show = true;
}

use Google\Client;
use Google\Service\Drive;

function convertSize($bytes)
{
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . $sizes[$factor];
}

#Nếu activve_show = true thì sẽ khởi tạo
if ($activve_show === true) {
    $client = new Client();
    $client->setAuthConfig($authConfigPath);
    $client->setAccessType('offline');
    $client->setIncludeGrantedScopes(true);
    $client->addScope(Drive::DRIVE_READONLY);
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        if (json_last_error() === JSON_ERROR_NONE && isset($accessToken['access_token'])) {
            $client->setAccessToken($accessToken);
        }
    } else {
        $responseData['message'] = "Tệp json xác thực không tồn tại: $tokenPath";
        $responseData['gcloud_notification'] = "Tệp json xác thực không tồn tại: $tokenPath";
        vbotApiJsonResponse($responseData, 401);
    }
    //Kiểm tra và làm mới token nếu cần
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            if (isset($newAccessToken['access_token'])) {
                //echo "Làm mới token thành công";
                $accessToken = array_merge($accessToken, $newAccessToken);
                file_put_contents($tokenPath, json_encode($accessToken, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
                @chmod($tokenPath, 0777);
                $responseData['gcloud_notification'] = "Làm mới mã Token thành công";
                $client->setAccessToken($accessToken);
            } else {
                $responseData['message'] = "Token xác thực đã hết hạn và không thể làm mới, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình";
                $responseData['gcloud_notification'] = "Token xác thực đã hết hạn và không thể làm mới, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình";
                vbotApiJsonResponse($responseData, 401);
            }
        } else {
            $responseData['message'] = "Token xác thực đã hết hạn và không thể làm mới, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình dữ liệu";
            $responseData['gcloud_notification'] = "Token xác thực đã hết hạn và không thể làm mới, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình dữ liệu";
            vbotApiJsonResponse($responseData, 401);
        }
    }
}

#Scan thư mục trong GDriver
if (isset($_POST['Scan'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    if (isset($_POST['Folder_Name']) && !empty($_POST['Folder_Name'])) {
        $folderName = trim($_POST['Folder_Name']);
        if (mb_strlen($folderName) > 150) {
            vbotApiJsonResponse(['success' => false, 'message' => 'Tên thư mục Google Drive quá dài'], 400);
        }
    } else {
        $responseData['success'] = false;
        $responseData['message'] = "Cần Nhập Tên Thư Mục Cần Scan";
        vbotApiJsonResponse($responseData, 400);
    }
    $driveService = new Drive($client);
    $response = $driveService->files->listFiles([
        'q' => sprintf("mimeType='application/vnd.google-apps.folder' and name='%s'", str_replace(["\\", "'"], ["\\\\", "\\'"], $folderName)),
        'fields' => 'files(id, name)',
        'pageSize' => 1,
    ]);
    if (count($response->getFiles()) == 0) {
        $responseData['message'] = "Không tìm thấy thư mục: $folderName trên Google Cloud Drive";
    } else {
        $folderId = $response->getFiles()[0]->getId();
        $filesResponse = $driveService->files->listFiles([
            'q' => sprintf("'%s' in parents", $folderId),
            'fields' => 'files(id, name, mimeType, size)',
            'pageSize' => 100, //Điều chỉnh số lượng kết quả cần tìm
        ]);
        if (count($filesResponse->getFiles()) == 0) {
            $responseData['message'] = "Không tìm thấy tệp sao lưu nào trong thư mục: $folderName trên Google Cloud Drive";
        } else {
            $responseData['success'] = true;
            $responseData['message'] = "Danh sách tệp trong thư mục: $folderName trên Google Cloud Drive";
            foreach ($filesResponse->getFiles() as $file) {
                $size = isset($file->size) ? convertSize($file->size) : 'N/A';
                $createdTime = isset($file->createdTime) ? $file->createdTime : 'N/A';
                $formattedTime = $createdTime !== 'N/A' ? date('d-m-Y H:i:s', strtotime($createdTime)) : 'N/A';
                $responseData['data'][] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'mimeType' => $file->getMimeType(),
                    'size' => $size,
                    'created_at' => $formattedTime,
                    'url_share' => 'https://drive.google.com/file/d/' . $file->getId() . '/view?usp=drive_link'
                ];
            }
        }
    }
    vbotApiJsonResponse($responseData, $responseData['success'] ? 200 : 404);
}

//Hàm Xóa file theo id
if (isset($_POST['Delete'])) {
    vbotApiVerifyCsrf(!empty($Config['contact_info']['user_login']['active']));
    if (isset($_POST['id_file']) && !empty($_POST['id_file'])) {
        $id_file = trim($_POST['id_file']);
        if (!preg_match('/^[A-Za-z0-9_-]{10,200}$/', $id_file)) {
            vbotApiJsonResponse(['success' => false, 'message' => 'ID file Google Drive không hợp lệ'], 400);
        }
    } else {
        $responseData['success'] = false;
        $responseData['message'] = "Cần Nhập ID Của Tệp Cần Xóa";
        vbotApiJsonResponse($responseData, 400);
    }
    $driveService = new Drive($client);
    $fileName = $id_file;
    try {
        $file = $driveService->files->get($id_file, ['fields' => 'id, name']);
        if ($file) {
            $fileName = $file->getName();
            $driveService->files->delete($id_file);
            $responseData['success'] = true;
            //$responseData['message'] =  "Tệp $fileName  có ID: $id_file đã được xóa thành công.";
            $responseData['message'] =  "File $fileName đã được xóa thành công.";
            vbotApiJsonResponse($responseData);
        }
    } catch (Exception $e) {
        if ($e->getCode() === 404) {
            $responseData['success'] = false;
            $responseData['message'] = "Tệp có ID: $id_file không tồn tại";
            vbotApiJsonResponse($responseData, 404);
        } else {
            $responseData['success'] = false;
            $responseData['message'] = "Không thể xóa tệp $fileName: " . $e->getMessage();
            error_log('Google Drive delete failed: '.$e->getMessage());
            vbotApiJsonResponse($responseData, 502);
        }
    }
} else {
    $responseData['success'] = false;
    $responseData['message'] = "ID tệp không được cung cấp";
    vbotApiJsonResponse($responseData, 400);
}
?>
