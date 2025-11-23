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
    echo json_encode($responseData);
    exit();
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
    echo json_encode($responseData);
    exit();
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
    //Khởi tạo Google Client
    $client = new Client();
    $client->setAuthConfig($authConfigPath);
    $client->setAccessType('offline');
    $client->setIncludeGrantedScopes(true);
    $client->addScope(Drive::DRIVE_READONLY);

    //Tải token từ file nếu đã tồn tại
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        //Kiểm tra xem token có hợp lệ hay không
        if (json_last_error() === JSON_ERROR_NONE && isset($accessToken['access_token'])) {
            $client->setAccessToken($accessToken);
        }
    } else {
        $responseData['message'] = "Tệp json xác thực không tồn tại: $tokenPath";
        $responseData['gcloud_notification'] = "Tệp json xác thực không tồn tại: $tokenPath";
        echo json_encode($responseData);
        exit();
    }

    //Kiểm tra và làm mới token nếu cần
    if ($client->isAccessTokenExpired()) {
        //Kiểm tra xem có Refresh Token hay không
        if ($client->getRefreshToken()) {
            //Nếu đã có Refresh Token, cố gắng làm mới Access Token
            $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            //Kiểm tra xem có access token mới không
            if (isset($newAccessToken['access_token'])) {
                //echo "Làm mới token thành công";
                // Cập nhật token mới vào biến accessToken
                $accessToken = array_merge($accessToken, $newAccessToken);
                //Lưu token mới vào tệp
                file_put_contents($tokenPath, json_encode($accessToken, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $responseData['gcloud_notification'] = "Làm mới mã Token thành công";
                //Thiết lập token mới cho client
                $client->setAccessToken($accessToken);
            } else {
                $responseData['message'] = "Token xác thực đã hết hạn và không thể làm mới, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình";
                $responseData['gcloud_notification'] = "Token xác thực đã hết hạn và không thể làm mới, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình";
                echo json_encode($responseData);
                exit();
            }
        } else {
            $responseData['message'] = "Token xác thực đã hết hạn và không thể làm mới, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình dữ liệu";
            $responseData['gcloud_notification'] = "Token xác thực đã hết hạn và không thể làm mới, cần truy cập: Sao Lưu Cloud->Google Drive để cấu hình dữ liệu";
            echo json_encode($responseData);
            exit();
        }
    }
}

if (isset($_GET['Scan'])) {
    //Kiểm tra folderName
    if (isset($_GET['Folder_Name']) && !empty($_GET['Folder_Name'])) {
        //Lấy tên thư mục từ tham số
        $folderName = $_GET['Folder_Name'];
    } else {
        $responseData['success'] = false;
        $responseData['message'] = "Cần Nhập Tên Thư Mục Cần Scan";
        echo json_encode($responseData);
        exit();
    }
    //Khởi tạo Google Drive Service
    $driveService = new Drive($client);
    //Tìm kiếm thư mục với tên được cung cấp
    $response = $driveService->files->listFiles([
        'q' => sprintf("mimeType='application/vnd.google-apps.folder' and name='%s'", $folderName),
        'fields' => 'files(id, name)',
        'pageSize' => 1, //Tìm một thư mục
    ]);
    if (count($response->getFiles()) == 0) {
        $responseData['message'] = "Không tìm thấy thư mục: $folderName trên Google Cloud Drive";
    } else {
        //Lấy ID của thư mục
        $folderId = $response->getFiles()[0]->getId();
        //Tìm tất cả các tệp bên trong thư mục bằng ID của thư mục cha
        $filesResponse = $driveService->files->listFiles([
            'q' => sprintf("'%s' in parents", $folderId),
            'fields' => 'files(id, name, mimeType, size)',
            'pageSize' => 100, //Điều chỉnh số lượng kết quả cần tìm
        ]);
        //Kiểm tra và hiển thị danh sách tệp tìm thấy trong thư mục
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
    echo json_encode($responseData);
    exit();
}

//Hàm Xóa file theo id
if (isset($_GET['Delete'])) {
    //Kiểm tra biến folderName
    if (isset($_GET['id_file']) && !empty($_GET['id_file'])) {
        //Lấy tên thư mục từ tham số
        $id_file = $_GET['id_file'];
    } else {
        //Nếu không có dữ liệu, xử lý lỗi
        $responseData['success'] = false;
        $responseData['message'] = "Cần Nhập ID Của Tệp Cần Xóa";
        echo json_encode($responseData);
        exit();
    }
    //Khởi tạo Drive
    $driveService = new Drive($client);
    //Kiểm tra tệp có tồn tại trước khi xóa
    try {
        $file = $driveService->files->get($id_file, ['fields' => 'id, name']);
        if ($file) {
            $fileName = $file->getName();
            $driveService->files->delete($id_file);
            $responseData['success'] = true;
            //$responseData['message'] =  "Tệp $fileName  có ID: $id_file đã được xóa thành công.";
            $responseData['message'] =  "File $fileName đã được xóa thành công.";
            echo json_encode($responseData);
        }
    } catch (Exception $e) {
        if ($e->getCode() === 404) {
            $responseData['success'] = false;
            $responseData['message'] = "Tệp có ID: $id_file không tồn tại";
            echo json_encode($responseData);
        } else {
            $responseData['success'] = false;
            $responseData['message'] = "Không thể xóa tệp $fileName: " . $e->getMessage();
            echo json_encode($responseData);
        }
    }
} else {
    $responseData['success'] = false;
    $responseData['message'] = "ID tệp không được cung cấp";
    echo json_encode($responseData);
}

/*
  else{
      // Nếu không có dữ liệu, xử lý lỗi
      $responseData['success'] = false;
      $responseData['message'] = "Cần Nhập Đầy Đủ Tham Số Cần Truy Vấn";
      echo json_encode($responseData);
      exit();
  	
  }
  */
?>