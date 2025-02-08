<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include '../../Configuration.php';

#Lấy danh sách hotword, và lib theo tùy chọn lang trong Config.json và hiển thị
if (isset($_GET['hotword'])){
    // Lấy giá trị ngôn ngữ từ GET
    $lang_get_HOTWORD = isset($_GET['lang']) ? $_GET['lang'] : '';
    if ($lang_get_HOTWORD === 'vi' || $lang_get_HOTWORD === 'eng'){
		$directory = $VBot_Offline . 'resource/picovoice/library';
        $files = glob($directory . '/*.pv');
        $file_list = array_map('basename', $files);
        $porcupineConfig = $Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang_get_HOTWORD];
        $response = ['lang' => $lang_get_HOTWORD, 'config' => $porcupineConfig, 'files_lib_pv' => $file_list, 'path_pv' => $directory . '/', 'path_ppn' => $VBot_Offline . 'resource/hotword/' . $lang_get_HOTWORD . '/', 'config_lib_pv_to_lang' => $Config['smart_config']['smart_wakeup']['hotword']['library'][$lang_get_HOTWORD]['modelFilePath']];
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    else if ($lang_get_HOTWORD === 'snowboy'){
		$directory = $VBot_Offline . 'resource/snowboy/hotword';
        $files = array_merge(
			glob($directory . '/*.pmdl'),
			glob($directory . '/*.umdl')
		);
        $file_list = array_map('basename', $files);
        $porcupineConfig = $Config['smart_config']['smart_wakeup']['hotword']['snowboy'];
        $response = ['lang' => $lang_get_HOTWORD, 'config' => $porcupineConfig, 'files_hotword' => $file_list, 'path_hotword' => $directory . '/'];
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    else{
        // Trả về đối tượng rỗng nếu ngôn ngữ không hợp lệ
        echo json_encode(['lang' => '', 'config' => [], 'files_lib_pv' => [], 'config_lib_pv_to_lang' => $Config['smart_config']['smart_wakeup']['hotword']['library'][$lang_get_HOTWORD]['modelFilePath']]);
    }
    exit();
}

// Xử lý khi tải lên file ppn và pv xong cập nhật vào Config.json, nếu trùng tên file chỉ tải lên mà không sửa trong config
if (isset($_POST['action_ppn_pv']) && $_POST['action_ppn_pv'] === 'upload_files_ppn_pv'){
    $uploadDirLibrary = $VBot_Offline . 'resource/picovoice/library/';
    $uploadDirHotword = $VBot_Offline . 'resource/hotword/';
    $lang = $_POST['lang_hotword_get'];
    if (file_exists($Config_filePath)){
        $jsonContent = file_get_contents($Config_filePath);
        $configData = json_decode($jsonContent, true);
        if (!isset($configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang])){
            $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = [];
        }
        $existingFiles = array_column($configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang], 'file_name');
        $updatedConfig = $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang];
    }
    else{
        $configData = ['smart_config' => ['smart_wakeup' => ['hotword' => ['porcupine' => ['vi' => [], 'eng' => []]]]]];
        $existingFiles = [];
    }
    $responseMessages = [];
    foreach ($_FILES['upload_files_ppn_pv']['error'] as $key => $error){
        if ($error == UPLOAD_ERR_OK){
            $tmpName = $_FILES['upload_files_ppn_pv']['tmp_name'][$key];
            $name = basename($_FILES['upload_files_ppn_pv']['name'][$key]);
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if ($ext == 'pv'){
                $uploadFile = $uploadDirLibrary . $name;
                move_uploaded_file($tmpName, $uploadFile);
                $responseMessages[] = "Tệp tin '$name' đã tải lên thành công vào: '$uploadFile'";
            }
            elseif ($ext == 'ppn'){
                $uploadFile = $uploadDirHotword . $lang . '/' . $name;
                $moveResult = move_uploaded_file($tmpName, $uploadFile);
                if ($moveResult){
                    chmod($uploadFile, 0777);
                    if (in_array($name, $existingFiles)){
                        // Nếu tên file đã tồn tại thì không cần cập nhật cấu hình
                        $responseMessages[] = "Tệp tin: '$name' đã tải lên thành công vào '$uploadFile' nhưng đã tồn tại trong ngôn ngữ '$lang', không cần cập nhật Config.json \n";
                    }
                    else{
                        // Thêm thông tin file mới vào mảng cấu hình
                        $updatedConfig[] = ["active" => true, "file_name" => $name, "sensitive" => 0.5];
                        $responseMessages[] = "Tệp tin: '$name' đã tải lên thành công vào '$uploadFile' và thêm vào ngôn ngữ '$lang' trong Config.json \n";
                    }
                }
                else{
                    $responseMessages[] = "Không thể tải tập tin lên '$name', hoặc không có full quyền hạn 0777";
                }
            }
            else{
                $responseMessages[] = "Loại tập tin không được hỗ trợ: $ext.";
            }
        }
        else{
            $responseMessages[] = "Lỗi tải file lên, cho file $key với mã lỗi: $error.";
        }
    }
    // Cập nhật mảng cấu hình
    if (!empty($updatedConfig)){
        $configData['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = $updatedConfig;
        file_put_contents($Config_filePath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    echo json_encode(['status' => 'success', 'messages' => $responseMessages]);
    exit;
}

#Cập nhật hotword snowboy khi được tải lên
if (isset($_POST['action_hotword_snowboy']) && $_POST['action_hotword_snowboy'] === 'upload_files_hotword_snowboy') {
    $uploadDirHotword = $VBot_Offline . 'resource/snowboy/hotword/';
    # Lấy dữ liệu từ Config.json
    if (file_exists($Config_filePath)) {
        $jsonContent = file_get_contents($Config_filePath);
        $configData = json_decode($jsonContent, true);
        if (!isset($configData['smart_config']['smart_wakeup']['hotword']['snowboy'])) {
            $configData['smart_config']['smart_wakeup']['hotword']['snowboy'] = [];
        }
        $existingFiles = array_column($configData['smart_config']['smart_wakeup']['hotword']['snowboy'], 'file_name');
        $updatedConfig = $configData['smart_config']['smart_wakeup']['hotword']['snowboy'];
    } else {
        $configData = ['smart_config' => ['smart_wakeup' => ['hotword' => ['snowboy' => []]]]];
        $existingFiles = [];
    }
    // Kiểm tra thư mục lưu trữ tồn tại chưa, nếu chưa thì tạo mới
    if (!is_dir($uploadDirHotword)) {
        mkdir($uploadDirHotword, 0777, true);
    }
    $uploadSuccess = [];
    $uploadErrors = [];
    // Kiểm tra nếu có file được tải lên
    if (!empty($_FILES['upload_files_hotword_snowboy']['name'][0])) {
        foreach ($_FILES['upload_files_hotword_snowboy']['name'] as $key => $fileName) {
            $fileTmpPath = $_FILES['upload_files_hotword_snowboy']['tmp_name'][$key];
            $fileSize = $_FILES['upload_files_hotword_snowboy']['size'][$key];
            $fileError = $_FILES['upload_files_hotword_snowboy']['error'][$key];
            // Kiểm tra lỗi khi tải lên
            if ($fileError !== UPLOAD_ERR_OK) {
                $uploadErrors[] = "Lỗi tải file: $fileName (Mã lỗi: $fileError)";
                continue;
            }
            // Kiểm tra định dạng file hợp lệ
            $allowedExtensions = ['pmdl', 'umdl'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                $uploadErrors[] = "File không hợp lệ: $fileName (Chỉ hỗ trợ .pmdl, .umdl)";
                continue;
            }
            // Kiểm tra dung lượng file (giới hạn 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $uploadErrors[] = "File quá lớn: $fileName (Tối đa 5MB)";
                continue;
            }
            $name = basename($fileName);
            $destinationPath = $uploadDirHotword . $name;
            // Di chuyển file vào thư mục
            if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                chmod($destinationPath, 0777);
				$uploadSuccess[] = "Tệp tin '$fileName' đã tải lên thành công vào: '$uploadDirHotword' và được thêm vào Config.json";
                // Kiểm tra file có tồn tại trong Config.json chưa
                if (!in_array($name, $existingFiles)) {
                    // Thêm thông tin file mới vào cấu hình
                    $updatedConfig[] = ["active" => true, "file_name" => $name, "sensitive" => 0.5];
                    //$uploadSuccess[] = "Thêm '$name' vào Config.json";
                }
            } else {
                $uploadErrors[] = "Không thể lưu file: $fileName";
            }
        }
        // Cập nhật Config.json
        if (!empty($updatedConfig)) {
            $configData['smart_config']['smart_wakeup']['hotword']['snowboy'] = $updatedConfig;
            file_put_contents($Config_filePath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    } else {
        $uploadErrors[] = "Không có file nào được chọn để tải lên.";
    }
    // Trả về phản hồi JSON
    header('Content-Type: application/json');
    echo json_encode([
        "status" => empty($uploadErrors) ? "success" : "error",
        "messages" => array_merge($uploadSuccess, $uploadErrors)
    ]);
    exit;
}

#cập nhật lại hotword picovoice eng và vi trong Config.json tương ứng với tất cả các file .ppn trong 2 thư mục eng và vi
if (isset($_GET['reload_hotword_config'])){
    #$directories = ['/home/pi/VBot_Offline/resource/hotword/eng', '/home/pi/VBot_Offline/resource/hotword/vi'];
    $directories = [
        $VBot_Offline . "resource/hotword/eng",
        $VBot_Offline . "resource/hotword/vi"
    ];
    // Khởi tạo cấu hình mặc định
    $newPorcupineConfig = [
		'vi' => $config['smart_config']['smart_wakeup']['hotword']['porcupine']['vi'] ?? [],
		'eng' => $config['smart_config']['smart_wakeup']['hotword']['porcupine']['eng'] ?? []
		];
	foreach ($directories as $directory){
        if (!is_dir($directory)){
            continue;
        }
        $files = glob($directory . '/*.ppn');
        foreach ($files as $file){
            $parts = explode('/', $file);
            $fileName = end($parts);
            #echo $fileName;
            $lang = strpos($directory, 'eng') !== false ? 'eng' : 'vi';
            $exists = false;
            foreach ($newPorcupineConfig[$lang] as $item){
                if ($item['file_name'] === $fileName){
                    $exists = true;
                    break;
                }
            }
            if (!$exists){
                $newPorcupineConfig[$lang][] = ['active' => true, 'file_name' => $fileName, 'sensitive' => 0.5];
            }
        }
    }
    $Config['smart_config']['smart_wakeup']['hotword']['porcupine'] = $newPorcupineConfig;
    if (file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))){
        echo json_encode(['status' => 'success', 'message' => 'Đã ghi cấu hình Config->Hotword tiếng anh và tiếng việt thành công.']);
    }
    else{
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi ghi file cấu hình Hotword tiếng anh và tiếng việt']);
    }
    exit;
}

#Cập nhật lại hotword snowboy
if (isset($_GET['reload_hotword_config_snowboy'])) {
    $directory = $VBot_Offline . "resource/snowboy/hotword";
    // Khởi tạo cấu hình mới từ dữ liệu có sẵn trong Config
    $newSnowboyConfig = $Config['smart_config']['smart_wakeup']['hotword']['snowboy'] ?? [];
    if (is_dir($directory)) {
        // Lấy danh sách file .pmdl và .umdl
        $files = array_merge(glob("$directory/*.pmdl"), glob("$directory/*.umdl"));
        foreach ($files as $file) {
            $parts = explode('/', $file);
            $fileName = end($parts);
            $exists = false;
            // Kiểm tra xem file đã tồn tại trong config chưa
            foreach ($newSnowboyConfig as $item) {
                if ($item['file_name'] === $fileName) {
                    $exists = true;
                    break;
                }
            }
            // Nếu chưa tồn tại, thêm vào config
            if (!$exists) {
                $newSnowboyConfig[] = ['active' => true, 'file_name' => $fileName, 'sensitive' => 0.5];
            }
        }
    }
    $Config['smart_config']['smart_wakeup']['hotword']['snowboy'] = $newSnowboyConfig;
    if (file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
        echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật cấu hình Hotword Snowboy thành công.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi ghi file cấu hình Hotword Snowboy.']);
    }
    exit;
}

?>
