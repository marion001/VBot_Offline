<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
require_once __DIR__.'/includes/php_ajax/Home_Assistant_Helpers.php';
?>
<?php
if ($Config['contact_info']['user_login']['active']) {
    session_start();
    // Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
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
        #modal_dialog_show_Home_Assistant {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            max-width: calc(100vw - 40px);
        }

        #modal_dialog_show_Home_Assistant .modal-content {
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .hass-custom-toolbar {
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

        .hass-custom-search-hidden {
            display: none !important;
        }

        #hass-custom-search-empty {
            display: none;
        }

        @media (max-width: 991.98px) {
            .hass-custom-toolbar {
                top: 60px;
            }
        }
    </style>
</head>

<body>
    <?php
    include 'html_header_bar.php';
    include 'html_sidebar.php';
    ?>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Lệnh Tùy Chỉnh Home Assistant <i class="bi bi-question-circle-fill" onclick="show_message('- Hỗ trợ code YAML, Có trong: <b>Công cụ nhà phát triển -> Hành Động</b><br/>- Công cụ phát triển hành động cho phép bạn thực hiện bất kỳ hành động nào có trong Home Assistant.')"></i></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Home Assistant Custom Command</li>
                    &nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['home_assistant']['custom_commands']['active'] ? '<p class="text-success" title="Home Assistant Custom Command đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Home Assistant Custom Command không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
                </ol>
            </nav>
        </div>
        <div class="hass-custom-toolbar" id="hass-custom-toolbar" aria-label="Tìm kiếm và điều hướng tác vụ Home Assistant">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-lg">
                    <div class="input-group">
                        <span class="input-group-text border-primary"><i class="bi bi-search text-primary"></i></span>
                        <input type="search" id="hass-custom-task-search" class="form-control border-primary"
                            placeholder="Tìm tên tác vụ, câu lệnh, phản hồi hoặc nội dung YAML..." autocomplete="off">
                        <button type="button" id="hass-custom-search-clear" class="btn btn-outline-secondary" title="Xóa nội dung tìm kiếm">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <select id="hass-custom-quick-navigation" class="form-select border-primary" aria-label="Đi tới khu vực Home Assistant">
                        <option value="">Đi tới tác vụ hoặc khu vực...</option>
                        <option value="hass-custom-config-section">Dữ liệu cấu hình</option>
                        <option value="hass-custom-recovery-section">Khôi phục dữ liệu</option>
                    </select>
                </div>
            </div>
            <div id="hass-custom-search-empty" class="alert alert-info py-2 mt-2 mb-0" role="status">
                <i class="bi bi-info-circle"></i> Không tìm thấy tác vụ phù hợp.
            </div>
        </div>
        <form id="hass-custom-form" class="row g-3 needs-validation" novalidate method="POST" enctype="multipart/form-data" action="" onsubmit="return validateFormVBot()">
            <?php
            $jsonFilePath = $VBot_Offline . $Config['home_assistant']['custom_commands']['custom_command_file'];
            // Mảng lưu thông báo
            $errorMessages = [];
            $successMessage = [];

            #Chuyển đổi Json Sang YAML không dùng thư viện
            function vbotYamlEncodeScalar($value)
            {
                if ($value === null) return 'null';
                if (is_bool($value)) return $value ? 'true' : 'false';
                if (is_int($value) || is_float($value)) return (string)$value;
                $text = (string)$value;
                if (preg_match('/^[a-zA-Z0-9._-]+$/', $text)) return $text;
                return json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            function arrayToYaml($array, $indent = 0)
            {
                $array = (array)$array;
                $yaml = '';
                $indentation = str_repeat(' ', $indent);
                foreach ($array as $key => $value) {
                    if (is_object($value)) $value = (array)$value;
                    // Xử lý mảng
                    if (is_array($value)) {
                        if (empty($value)) {
                            // Mảng rỗng
                            $yaml .= $indentation . $key . ": {}\n";
                        } elseif (isset($value[0]) && !is_array($value[0])) {
                            // Nếu là mảng đơn giản, sử dụng dấu "-"
                            $yaml .= $indentation . $key . ":\n";
                            foreach ($value as $subValue) {
                                $yaml .= $indentation . "  - " . vbotYamlEncodeScalar($subValue) . "\n";
                            }
                        } else {
                            // Mảng khác (mảng phức tạp)
                            $yaml .= $indentation . $key . ":\n" . arrayToYaml($value, $indent + 2);
                        }
                    } else {
                        // Xử lý giá trị đơn
                        if ($value === "{}") {
                            $yaml .= $indentation . $key . ": {}\n";  // Xử lý rỗng
                        } elseif ($value === "" && $key === "entity_id") {
                            // Nếu là `entity_id` và có giá trị là rỗng, ghi "{}"
                            $yaml .= $indentation . $key . ": {}\n";
                        } else {
                            $yaml .= $indentation . $key . ": " . vbotYamlEncodeScalar($value) . "\n";
                        }
                    }
                }
                return $yaml;
            }

            // Xử lý dữ liệu khi form được gửi
            if (isset($_POST['save_custom_home_assistant'])) {

                #Sao Lưu Dữ Liệu Trước
                if (isset($Config['backup_upgrade']['custom_home_assistant']['active']) && $Config['backup_upgrade']['custom_home_assistant']['active'] === true) {
                    $sourceFile = $VBot_Offline . $Config['home_assistant']['custom_commands']['custom_command_file'];
                    $destinationDir = $directory_path . '/' . $Config['backup_upgrade']['custom_home_assistant']['backup_path'];
                    $destinationFile = $destinationDir . "/Home_Assistant_Custom_" . date('dmY_His') . ".json";
                    if (!is_dir($destinationDir)) {
                        mkdir($destinationDir, 0777, true);
                    vbotSetFullPermissions($destinationDir, 'thư mục backup Custom HASS');
                        $successMessage[] = "- Tạo thư mục sao lưu thành công: <b>$destinationDir</b>";
                    }
                    if (copy($sourceFile, $destinationFile)) {
                        vbotSetFullPermissions($destinationFile, 'tệp backup Custom HASS');
                        //$successMessage[] = "Tệp đã được sao chép thành công đến $destinationFile";
                        $jsonFiles = glob($destinationDir . "/*.json");
                        usort($jsonFiles, function ($a, $b) {
                            return filemtime($a) - filemtime($b);
                        });
                        // Xóa các tệp cũ nhất nếu số lượng tệp vượt quá 5
                        if (count($jsonFiles) > $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']) {
                            foreach (array_slice($jsonFiles, 0, count($jsonFiles) - $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']) as $oldFile) {
                                unlink($oldFile);
                                $successMessage[] = "Vượt quá số lượng tệp tin Backup là <b>" . $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files'] . "</b>, đã xóa tệp: <b>" . basename($oldFile) . "</b>";
                            }
                        }
                    } else {
                        $errorMessages[] = "- Xảy ra Lỗi, Không thể sao lưu tệp: <b>$sourceFile</b>";
                    }
                }
                $intents = $_POST['intents'] ?? [];
                if (!is_array($intents)) {
                    $intents = [];
                }
                $intents = array_values($intents);
                $intentValidationFailed = false;
                $questionOwners = [];
                // Chuyển đổi YAML thành mảng và xử lý các dữ liệu khác (YAML, questions, active, v.v.)
                foreach ($intents as $index => $intent) {
                    if (!is_array($intent)) {
                        unset($intents[$index]);
                        continue;
                    }
                    $yamlError = null;
                    $parsedYaml = vbotHassParseActionYaml(trim($intent['data_yaml'] ?? ''), $yamlError);
                    $validatedYaml = is_array($parsedYaml) ? vbotHassValidateAction($parsedYaml, $yamlError) : null;
                    $intents[$index]['questions'] = array_filter(array_map('trim', explode("\n", $intent['questions'] ?? '')));
                    $intents[$index]['questions'] = array_values($intents[$index]['questions']);
                    $intents[$index]['name'] = trim($intent['name'] ?? '');
                    $intents[$index]['reply'] = trim($intent['reply'] ?? '');
                    $intents[$index]['active'] = isset($intent['active']) && $intent['active'] === 'on';
                    if ($intents[$index]['name'] === '') {
                        $errorMessages[] = '- Tác vụ số '.($index + 1).' chưa có tên.';
                        $intentValidationFailed = true;
                    }
                    if (!$intents[$index]['questions']) {
                        $errorMessages[] = '- Tác vụ '.htmlspecialchars($intents[$index]['name']).' chưa có câu lệnh.';
                        $intentValidationFailed = true;
                    }
                    foreach ($intents[$index]['questions'] as $question) {
                        $normalizedQuestion = preg_replace('/\s+/u', ' ', trim($question));
                        $normalizedQuestion = function_exists('mb_strtolower') ? mb_strtolower($normalizedQuestion, 'UTF-8') : strtolower($normalizedQuestion);
                        if (isset($questionOwners[$normalizedQuestion])) {
                            $errorMessages[] = '- Câu lệnh bị trùng "'.htmlspecialchars($question).'" giữa tác vụ '.htmlspecialchars($questionOwners[$normalizedQuestion]).' và '.htmlspecialchars($intents[$index]['name']).'.';
                            $intentValidationFailed = true;
                        } else {
                            $questionOwners[$normalizedQuestion] = $intents[$index]['name'];
                        }
                    }
                    if ($validatedYaml === null) {
                        $errorMessages[] = '- YAML của tác vụ '.htmlspecialchars($intents[$index]['name']).' không hợp lệ: '.htmlspecialchars((string)$yamlError);
                        $intentValidationFailed = true;
                    } else {
                        $intents[$index]['data_yaml'] = $validatedYaml;
                    }
                }
                $updatedData = ['intents' => array_values($intents)];
                $encodedData = json_encode($updatedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($intentValidationFailed) {
                    error_log('[Custom Home Assistant] Đã từ chối lưu vì dữ liệu intent/YAML không hợp lệ');
                } elseif ($encodedData === false) {
                    $saveError = "Không thể mã hóa dữ liệu Custom Home Assistant: " . json_last_error_msg();
                    $errorMessages[] = $saveError;
                    error_log($saveError);
                } elseif (!vbotAtomicWriteFile($jsonFilePath, $encodedData, 'Custom Home Assistant')) {
                    $saveError = "Không thể ghi dữ liệu Custom Home Assistant vào tệp: " . $jsonFilePath;
                    $errorMessages[] = $saveError;
                    error_log($saveError);
                } else {
                    if (!vbotSetFullPermissions($jsonFilePath, 'tệp Custom HASS')) {
                        error_log("Không thể đặt quyền 0777 cho tệp Custom Home Assistant: " . $jsonFilePath);
                    }
                    $successMessage[] = "Dữ liệu đã được lưu thành công!";
                }
            }

            if (isset($_POST['delete_all_custom_home_assistant'])) {
                #Sao Lưu Dữ Liệu Trước
                if (isset($Config['backup_upgrade']['custom_home_assistant']['active']) && $Config['backup_upgrade']['custom_home_assistant']['active'] === true) {
                    // Đường dẫn gốc và đích
                    $sourceFile = $VBot_Offline . $Config['home_assistant']['custom_commands']['custom_command_file'];
                    $destinationDir = $directory_path . '/' . $Config['backup_upgrade']['custom_home_assistant']['backup_path'];
                    $destinationFile = $destinationDir . "/Home_Assistant_Custom_" . date('dmY_His') . ".json";
                    // Kiểm tra xem thư mục đích có tồn tại hay không, nếu không thì tạo mới
                    if (!is_dir($destinationDir)) {
                        mkdir($destinationDir, 0777, true);
                    vbotSetFullPermissions($destinationDir, 'thư mục backup Custom HASS');
                        $successMessage[] = "- Tạo thư mục sao lưu thành công: <b>$destinationDir</b>";
                    }
                    if (copy($sourceFile, $destinationFile)) {
                        vbotSetFullPermissions($destinationFile, 'tệp backup Custom HASS');
                        //$successMessage[] = "Tệp đã được sao chép thành công đến $destinationFile";
                        // Lấy danh sách các tệp .json trong thư mục đích, sắp xếp theo thời gian tạo (cũ nhất trước)
                        $jsonFiles = glob($destinationDir . "/*.json");
                        usort($jsonFiles, function ($a, $b) {
                            return filemtime($a) - filemtime($b);
                        });
                        // Xóa các tệp cũ nhất nếu số lượng tệp vượt quá 5
                        if (count($jsonFiles) > $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']) {
                            foreach (array_slice($jsonFiles, 0, count($jsonFiles) - $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']) as $oldFile) {
                                unlink($oldFile);
                                $successMessage[] = "Vượt quá số lượng tệp tin Backup là <b>" . $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files'] . "</b>, đã xóa tệp: <b>" . basename($oldFile) . "</b>";
                            }
                        }
                    } else {
                        $errorMessages[] = "- Xảy ra Lỗi, Không thể sao lưu tệp: <b>$sourceFile</b>";
                    }
                }
                // Chỉ làm rỗng đúng tệp Custom Home Assistant, không xóa các JSON khác trong resource/hass.
                $content = json_encode(["intents" => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($content !== false && vbotAtomicWriteFile($jsonFilePath, $content, 'xóa Custom Home Assistant')) {
                            if (!vbotSetFullPermissions($jsonFilePath, 'tệp Custom HASS khôi phục')) {
                        error_log("Không thể đặt quyền 0777 cho tệp Custom Home Assistant: " . $jsonFilePath);
                    }
                    $successMessage[] = "Toàn bộ dữ liệu cấu hình đã được xóa thành công";
                } else {
                    $deleteError = "Không thể xóa dữ liệu cấu hình Custom Home Assistant: " . $jsonFilePath;
                    $errorMessages[] = $deleteError;
                    error_log($deleteError);
                }
            }

			// Khôi Phục Dữ liệu bằng tải lên hoặc tệp hệ thống
			if (isset($_POST['start_recovery_custom_homeassistant'])) {
				$data_recovery_type = $_POST['start_recovery_custom_homeassistant'];
				if ($data_recovery_type === "khoi_phuc_tu_tep_tai_len") {
					$uploadOk = 1;
					if (
						!isset($_FILES["fileToUpload_custom_hass_restore"]) ||
						$_FILES["fileToUpload_custom_hass_restore"]["error"] === UPLOAD_ERR_NO_FILE ||
						empty($_FILES["fileToUpload_custom_hass_restore"]["name"])
					) {
						$errorMessages[] = "- Tệp chưa được chọn để tải lên khôi phục dữ liệu";
						$uploadOk = 0;
					}
					if ($uploadOk === 1) {
						$fileName = basename($_FILES["fileToUpload_custom_hass_restore"]["name"]);
						$fileSize = (int)($_FILES["fileToUpload_custom_hass_restore"]["size"] ?? 0);
						if ($_FILES["fileToUpload_custom_hass_restore"]["error"] !== UPLOAD_ERR_OK) {
							$errorMessages[] = "- Tệp Home Assistant tải lên gặp lỗi, mã lỗi: " . intval($_FILES["fileToUpload_custom_hass_restore"]["error"]);
							$uploadOk = 0;
						} elseif (!is_uploaded_file($_FILES["fileToUpload_custom_hass_restore"]["tmp_name"]) || $fileSize <= 0 || $fileSize > 10485760) {
							$errorMessages[] = "- Tệp Home Assistant không hợp lệ hoặc vượt quá 10 MB";
							$uploadOk = 0;
						} elseif (!preg_match('/\.json$/i', $fileName)) {
							$errorMessages[] = "- Chỉ chấp nhận tệp .json, dành cho Home_Assistant_Custom.json";
							$uploadOk = 0;
						}
					}
					if ($uploadOk === 1) {
						$jsonContent = file_get_contents($_FILES["fileToUpload_custom_hass_restore"]["tmp_name"]);
						$data = json_decode($jsonContent, true);
						if (json_last_error() !== JSON_ERROR_NONE) {
							$errorMessages[] = "- Nội dung tệp JSON không hợp lệ";
							$uploadOk = 0;
						} else {
							$documentError = null;
							$validatedDocument = vbotHassValidateIntentDocument($data, $documentError);
							if ($validatedDocument === null) {
								$errorMessages[] = "- Tệp JSON không đúng dữ liệu Custom Home Assistant: ".htmlspecialchars((string)$documentError);
								$uploadOk = 0;
							} else {
								$jsonContent = json_encode($validatedDocument, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
							}
						}
					}
					if ($uploadOk === 1) {
                                    if (vbotAtomicWriteFile($jsonFilePath, $jsonContent, 'khôi phục Custom Home Assistant tải lên')) {
                                    if (!vbotSetFullPermissions($jsonFilePath, 'tệp Custom HASS khôi phục')) {
								error_log("Không thể đặt quyền 0777 cho tệp Custom Home Assistant sau khi khôi phục: " . $jsonFilePath);
							}
							$successMessage[] =
								"- Tệp " . htmlspecialchars($fileName) .
								" đã được tải lên và khôi phục dữ liệu Custom Home Assistant thành công";
						} else {
							$errorMessages[] = "- Có lỗi xảy ra khi tải lên tệp sao lưu của bạn";
						}
					} else {
						$errorMessages[] = "- Tệp sao lưu không hợp lệ, không thể khôi phục";
					}
				}
				//KHÔI PHỤC TỪ FILE HỆ THỐNG
				else if ($data_recovery_type === "khoi_phuc_file_he_thong") {
					$selectedBackup = basename($_POST['backup_custom_hass_json_files'] ?? '');
					$backupDirectory = $directory_path . '/' . trim($Config['backup_upgrade']['custom_home_assistant']['backup_path'], '/\\');
					$start_recovery_custom_hass = $backupDirectory . '/' . $selectedBackup;
					if ($selectedBackup !== '') {
						if (is_file($start_recovery_custom_hass)) {
							$jsonContent = file_get_contents($start_recovery_custom_hass);
							$data = json_decode($jsonContent, true);
							$documentError = null;
							$validatedDocument = json_last_error() === JSON_ERROR_NONE ? vbotHassValidateIntentDocument($data, $documentError) : null;
							if ($validatedDocument === null) {
								$errorMessages[] = "- Tệp sao lưu hệ thống không đúng dữ liệu Custom Home Assistant: ".htmlspecialchars((string)$documentError);
							} else {
								$jsonContent = json_encode($validatedDocument, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                if (vbotAtomicWriteFile($jsonFilePath, $jsonContent, 'khôi phục Custom Home Assistant từ backup')) {
									if (!vbotSetFullPermissions($jsonFilePath, 'tệp Custom HASS khôi phục')) {
										error_log("Không thể đặt quyền 0777 cho tệp Custom Home Assistant sau khi khôi phục: " . $jsonFilePath);
									}
									$successMessage[] = "Đã khôi phục dữ liệu Custom Home Assistant từ tệp sao lưu trên hệ thống thành công";
								} else {
									$restoreError = "Không thể sao chép tệp sao lưu Custom Home Assistant: " . $start_recovery_custom_hass;
									$errorMessages[] = $restoreError;
									error_log($restoreError);
								}
							}
						} else {
							$errorMessages[] = "Lỗi: Tệp " . basename($start_recovery_custom_hass) . " không tồn tại trên hệ thống";
						}
					} else {
						$errorMessages[] = "Không có tệp sao lưu Custom Home Assistant nào được chọn để khôi phục!";
					}
				}
			}

            if (file_exists($jsonFilePath)) {
                $jsonData = file_get_contents($jsonFilePath);
                $data = json_decode($jsonData, true);
                $documentError = null;
                $validatedDocument = vbotHassValidateIntentDocument($data, $documentError);
                // Không tự ghi đè file lỗi; giữ file để backup/recovery và chỉ hiển thị danh sách rỗng.
                if ($validatedDocument === null) {
                    error_log("Custom Home Assistant không hợp lệ: " . (string)$documentError);
                    $data = ['intents' => []];
                    $errorMessages[] = '- File Custom Home Assistant không hợp lệ: '.htmlspecialchars((string)$documentError).'. File gốc được giữ nguyên để khôi phục.';
                } else {
                    $data = $validatedDocument;
                }
                $intents = $data['intents'];
                if (empty($intents)) {
                    echo '<center><h5 class="card-title"><font color="red">Chưa có tác vụ nào được thiết lập cho Custom Home Assistant:</font></h5></center>';
                } else {

            ?>
                    <section class="section">
                        <div class="row">
                            <?php
                            // Hiển thị thông báo lỗi nếu có
                            if (!empty($errorMessages)) {
                                echo '<div class="alert alert-danger alert-dismissible fade show" id="message_error" role="alert">';
                                echo '<ul style="color: red;">';
                                foreach ($errorMessages as $errorMessage) {
                                    echo '<li>' . $errorMessage . '</li>';
                                }
                                echo '</ul>';
                                echo '</div>';
                            }
                            // Hiển thị thông báo thành công nếu có
                            if (!empty($successMessage)) {
                                echo '<div class="alert alert-success alert-dismissible fade show" id="message_error" role="alert">';
                                echo '<ul style="color: green;">';
                                foreach ($successMessage as $successMessagegg) {
                                    echo '<li>' . $successMessagegg . '</li>';
                                }
                                echo '</ul>';
                                echo '</div>';
                            }
                            ?>
                            <h5 class="card-title">
                                <font color="green">Thiết Lập Lệnh Tùy Chỉnh Home Assistant:</font> <a href="https://github.com/user-attachments/assets/eb92a617-12f6-40c9-9d00-35cdbe5cd0bb" target="_bank">(Cấu Trúc Code YAML: Ảnh Hướng Dẫn, Demo)</a>
                            </h5>
                            <?php foreach ($intents as $index => $intent): ?>
                                <div class="card accordion hass-custom-task" id="accordion_button_custom_hass_<?= $index + 1 ?>">
                                    <div class="card-body">
                                        <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_custom_hass_<?= $index + 1 ?>" aria-expanded="false" aria-controls="collapse_button_custom_hass_<?= $index + 1 ?>">
                                            <font color="Fuchsia"><?= htmlspecialchars($intent['name']) ?>, &nbsp;</font> Trạng Thái: &nbsp;<?= !empty($intent['active']) ? ' <font color=green>Bật</font>' : ' <font color=red>Tắt</font>' ?>
                                        </h5>
                                        <div id="collapse_button_custom_hass_<?= $index + 1 ?>" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_custom_hass_<?= $index + 1 ?>">
                                            <div class="alert alert-success" role="alert">
											<!-- Active trạng thái -->
                                            <div class="row mb-3">
                                                <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt hành động này')"></i> :</label>
                                                <div class="col-sm-9">
                                                    <div class="form-switch">
                                                        <input class="form-check-input border-success" type="checkbox" name="intents[<?= $index ?>][active]" id="intents[<?= $index ?>][active]" <?= !empty($intent['active']) ? 'checked' : '' ?>>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Tên Hành ĐỘng -->
                                            <div class="row mb-3">
                                                <label for="intents[<?= $index ?>][name]" class="col-sm-3 col-form-label" title="Đặt Tên Định Danh Cho Hành Động Này">Tên Tác Vụ <i class="bi bi-question-circle-fill" onclick="show_message('Tên Định Danh Để Phân Biệt Với Các Hành Động, Thao Tác Khác')"></i> : </label>
                                                <div class="col-sm-9">
                                                    <div class="input-group mb-3">
                                                        <input required class="form-control border-success" type="text" name="intents[<?= $index ?>][name]" id="intents[<?= $index ?>][name]" title="Đặt Tên Định Danh Cho Hành Động Này" placeholder="<?= htmlspecialchars($intent['name']) ?>" value="<?= htmlspecialchars($intent['name']) ?>">
                                                        <div class="invalid-feedback">Cần đặt tên cho hành động này</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Tùy chỉnh câu phản hồi -->
                                            <div class="row mb-3">
                                                <label for="intents[<?= $index ?>][reply]" class="col-sm-3 col-form-label" title="Tùy Chỉnh Câu Phản Hồi Lại Khi Thực Hiện Thao Tác Này">Tùy Chỉnh Câu Phản Hồi <i class="bi bi-question-circle-fill" onclick="show_message('Tùy Chỉnh Câu Phản Hồi Lại Khi Thực Hiện Thao Tác Này, Không Muốn Phản Hồi Lại Theo Ý Của Bạn Thì Để Trống')"></i> : </label>
                                                <div class="col-sm-9">
                                                    <input class="form-control border-success" type="text" name="intents[<?= $index ?>][reply]" id="intents[<?= $index ?>][reply]" title="Tùy Chỉnh Câu Phản Hồi Lại Khi Thực Hiện Thao Tác Này" placeholder="<?= htmlspecialchars($intent['reply'] ?? '') ?>" value="<?= htmlspecialchars($intent['reply'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <!-- data_yaml: chuyển đổi mảng thành định dạng YAML -->
                                            <div class="row mb-3">
                                                <label for="intents[<?= $index ?>][data_yaml]" class="col-sm-3 col-form-label">Code YAML <i class="bi bi-question-circle-fill" onclick="show_message('Nội Dung Code YAML Cần Thực Hiện<br/>Nội dung Code YAML này được lấy ở Trong Home Assistant: <b>Công cụ nhà phát triển -> Hành Động -> Công cụ phát triển hành động cho phép bạn thực hiện bất kỳ hành động nào có trong Home Assistant.</b><br/> - Khi bạn thực hiện thành công, sẽ sao chép hết nội dung code trong ô nhập liệu đó vào đây')"></i> :</label>
                                                <div class="col-sm-9">
                                                    <div class="input-group mb-3">
                                                        <textarea required class="form-control border-success" rows="5" name="intents[<?= $index ?>][data_yaml]" id="intents[<?= $index ?>][data_yaml]">
<?= htmlspecialchars(arrayToYaml($intent['data_yaml'] ?? [])) ?>
</textarea>
                                                        <div class="invalid-feedback">Cần nhập nội dung code YAML cho tác vụ này</div>
                                                    </div>
                                                    <center>
                                                        <button type="button" class="btn btn-success rounded-pill" onclick="yaml_test_code_hass('intents[<?= $index ?>][data_yaml]')"><i class="bi bi-align-start"></i> Test Code Yaml</button>
                                                    </center>
                                                </div>
                                            </div>
                                            <!-- Câu Lệnh Cần Thực Thi -->
                                            <div class="row mb-3">
                                                <label for="intents[<?= $index ?>][questions]" class="col-sm-3 col-form-label">Câu Lệnh <i class="bi bi-question-circle-fill" onclick="show_message('Câu Lệnh Để Điều Khiển, Chạy Hành Động Này, Không Giới Hạn Nhiều Câu Lệnh, Mỗi Câu Lệnh Là 1 Dòng<br/>- Khi bạn nói đúng 1 trong các câu lệnh được thiết lập, thì tác vụ sẽ được chạy')"></i> :</label>
                                                <div class="col-sm-9">
                                                    <div class="input-group mb-3">
                                                        <textarea required class="form-control border-success" rows="5" name="intents[<?= $index ?>][questions]" id="intents[<?= $index ?>][questions]">
<?= htmlspecialchars(implode("\n", $intent['questions'] ?? [])) ?></textarea>
                                                        <div class="invalid-feedback">Cần nhập câu lệnh thực thi cho tác vụ này</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <center>
                                                <button type="button" class="btn btn-danger rounded-pill" onclick='removeIntentSection("accordion_button_custom_hass_<?= $index + 1 ?>", <?= json_encode($intent['name'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>)'><i class="bi bi-trash"></i> Xóa Tác Vụ</button>
                                            </center>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            <?php endforeach; ?>
                    <?php
                }
            } else {
                #echo("Không tìm thấy tệp JSON cho cấu hình Custom Home Assistant: {$jsonFilePath}");
                $defaultContent = json_encode([
                    "intents" => []
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (vbotAtomicWriteFile($jsonFilePath, $defaultContent, 'tạo Custom Home Assistant')) {
                    if (!vbotSetFullPermissions($jsonFilePath, 'tệp Custom HASS')) {
                        error_log("Không thể đặt quyền 0777 cho tệp Custom Home Assistant: " . $jsonFilePath);
                    }
                } else {
                    error_log("Không thể tạo tệp Custom Home Assistant: " . $jsonFilePath);
                }
            }
                    ?>
                    <!-- Các phần tử mới sẽ được thêm vào đây -->
                    <div id="accordion-container"></div>
<div class="alert alert-primary" role="alert">
Để Bật Tắt Sử Dụng Chức Năng Này Hãy Đi Tới: <b>Cấu Hình Config</b> -> <b>Cấu Hình Kết Nối Tới Home Assistant (HASS)</b> -> <b>Lệnh tùy chỉnh</b>
</div>
                    <center>
                        <button class="btn btn-primary  rounded-pill" type="submit" name="save_custom_home_assistant"><i class="bi bi-save"></i> Lưu thay đổi</button>
                        <button type="button" class="btn btn-success rounded-pill" onclick="addNewSection()">Thêm Mới Tác Vụ</button>
                        <button class="btn btn-danger rounded-pill" type="submit" name="delete_all_custom_home_assistant" onclick="return confirmRestore('Bạn có chắc chắn muốn xóa tất cả dữ liệu cấu hình Custom Home Assistant không')"><i class="bi bi-trash"></i> Xóa Dữ Liệu Cấu hình</button>
                    </center>
                    <h5 class="card-title" id="hass-custom-config-section">
                        <font color="green">Dữ Liệu Cấu Hình:</font>
                    </h5>
                    <div class="row mb-3">
                        <label for="custom_home_assistant_config_path" class="col-sm-3 col-form-label"><b>Đường Dẫn/Path File Cấu Hình:</b></label>
                        <div class="col-sm-9">
						<div class="input-group">
                            <input disabled class="form-control border-danger" type="text" name="custom_home_assistant_config_path" id="custom_home_assistant_config_path" value="<?php echo $VBot_Offline . $Config['home_assistant']['custom_commands']['custom_command_file']; ?>">
                        <button type="button" class="btn btn-success border-danger" title="Xem dữ liệu Đã cấu hình Custom Home Assistant" id="openModalBtn_Home_Assistant"><i class="bi bi-eye"></i></button>
                        <button type="button" class="btn btn-info border-danger" title="Tải Xuống file: <?php echo $jsonFilePath; ?>" onclick="downloadFile('<?php echo $jsonFilePath; ?>')"><i class="bi bi-download"></i></button>
						</div>
						</div>
                    </div>
                    <hr />
                    <h5 class="card-title" id="hass-custom-recovery-section">
                        <font color="green">Khôi Phục Dữ Liệu:</font>
                    </h5>
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label"><b>Tải Lên Tệp Và Khôi Phục:</b></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input class="form-control border-success" type="file" name="fileToUpload_custom_hass_restore" accept=".json">
                                <button class="btn btn-warning border-success" type="submit" name="start_recovery_custom_homeassistant" value="khoi_phuc_tu_tep_tai_len" onclick="return confirmRestore('Bạn có chắc chắn muốn tải lên tệp để khôi phục dữ liệu Home_Assistant_Custom.json không?')">Tải Lên & Khôi Phục</button>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label"><b>Hoặc Chọn Tệp Khôi Phục:</b></label>
                        <div class="col-sm-9">
                            <?php
                            $jsonFiles = glob($Config['backup_upgrade']['custom_home_assistant']['backup_path'] . '/*.json');
                            $co_tep_BackUp_customhass = true;
                            if (empty($jsonFiles)) {
                                $co_tep_BackUp_customhass = false;
                                echo '<select class="form-select border-primary" name="backup_custom_hass_json_files" id="backup_custom_hass_json_files">';
                                echo '<option selected value="">Không có tệp khôi phục dữ liệu Config nào</option>';
                                echo '</select>';
                            } else {
                                $co_tep_BackUp_customhass = true;
                                echo '<div class="input-group"><select class="form-select border-primary" name="backup_custom_hass_json_files" id="backup_custom_hass_json_files">';
                                echo '<option selected value="">Chọn Tệp Khôi Phục Dữ Liệu Custom Home Assistant</option>';
                                foreach ($jsonFiles as $file) {
                                    $fileName = basename($file);
                                    echo '<option value="' . htmlspecialchars($Config['backup_upgrade']['custom_home_assistant']['backup_path'] . '/' . $fileName) . '">' . htmlspecialchars($fileName) . '</option>';
                                }
                                echo '</select>
                  <button class="btn btn-warning border-primary" type="submit" name="start_recovery_custom_homeassistant" value="khoi_phuc_file_he_thong">Khôi Phục</button>
                  <button type="button" class="btn btn-info border-primary" title="Tải Xuống Tệp Sao Lưu Custom Home Assistant" onclick="dowlaod_file_backup_hass_custom(\'get_value_backup_config\')"><i class="bi bi-download"></i></button>
                  <button type="button" class="btn btn-success border-primary" title="Xem Tệp Sao Lưu Custom Home Assistant" onclick="readJSON_file_path(\'get_value_backup_config\')"><i class="bi bi-eye"></i></button>
                  <button type="button" class="btn btn-danger border-primary" title="Xóa Tệp Sao Lưu Custom Home Assistant" onclick="delete_file_backup_hass_custom(\'get_value_backup_config\')"><i class="bi bi-trash"></i></button>
                  </div>';
                            }
                            ?>
                        </div>
                    </div>
        </form>
        </div>
        </section>
    </main>
    <!-- Modal hiển thị tệp Config.json -->
    <div class="modal fade" id="myModal_Home_Assistant" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
        <div class="modal-dialog" id="modal_dialog_show_Home_Assistant" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <b>
                        <font color=blue>
                            <div id="name_file_showzz"></div>
                        </font>
                    </b>
                    <button type="button" class="close btn btn-danger" data-dismiss="modal_Config" aria-label="Close" onclick="$('#myModal_Home_Assistant').modal('hide');">
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
    <!-- ======= Footer ======= -->
    <?php
    include 'html_footer.php';
    ?>
    <!-- End Footer -->
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Nghe thử file âm thanh 
      <audio id="audioPlayer" style="display: none;" controls></audio>-->
    <!-- Template Main JS File -->
    <script>
        //Lưu trực tiếp node DOM để khôi phục đúng cả các giá trị người dùng vừa chỉnh sửa.
        const removedSections = {};

        function removeIntentSection(id, name_text) {
            const section = document.getElementById(id);
            if (section) {
                const placeholder = document.createElement('div');
                placeholder.id = id + '_deleted';
                placeholder.className = 'alert alert-danger';
                placeholder.appendChild(document.createTextNode('Đã xóa tác vụ: '));
                const taskName = document.createElement('b');
                taskName.textContent = name_text;
                placeholder.appendChild(taskName);
                placeholder.appendChild(document.createTextNode(', thay đổi sẽ được lưu khi bạn nhấn "Lưu thay đổi". '));
                const restoreButton = document.createElement('button');
                restoreButton.type = 'button';
                restoreButton.className = 'btn btn-warning btn-sm rounded-pill';
                restoreButton.textContent = 'Hủy bỏ';
                restoreButton.addEventListener('click', function() {
                    restoreIntentSection(id);
                });
                placeholder.appendChild(restoreButton);
                removedSections[id] = { section: section, placeholder: placeholder };
                section.replaceWith(placeholder);
            }
        }

        // Hàm phục hồi phần tử
        function restoreIntentSection(id) {
            const removed = removedSections[id];
            if (removed && removed.placeholder.isConnected) {
                removed.placeholder.replaceWith(removed.section);
                delete removedSections[id];
            }
        }
    </script>
    <script>
        let sectionCounter = <?= count($intents) + 1; ?>;
        //Tạo mới HTML cho các phần tử
        function addNewSection() {
            //Tạo ID duy nhất cho mỗi phần tử
            const sectionID = 'section_custom_hass_' + sectionCounter;
            const newSection =
                '<div class="card hass-custom-task" id="' + sectionID + '">' +
                '<div class="card-body">' +
                '<h5 class="card-title text-danger">Thêm Mới Tác Vụ:</h5>' +

                '<div class="alert alert-primary" role="alert"><div class="row mb-3">' +
                '<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message(\'Bật hoặc Tắt để kích hoạt hành động này\')"></i>:</label>' +
                '<div class="col-sm-9">' +
                '<div class="form-switch">' +
                '<input class="form-check-input border-success" type="checkbox" checked name="intents[' + (sectionCounter - 1) + '][active]" id="intents[' + (sectionCounter - 1) + '][active]">' +
                '</div>' +
                '</div>' +
                '</div>' +

                '<div class="row mb-3">' +
                '<label class="col-sm-3 col-form-label">Tên Tác Vụ <i class="bi bi-question-circle-fill" onclick="show_message(\'Tên Định Danh Để Phân Biệt Với Các Hành Động, Thao Tác Khác\')"></i>:</label>' +
                '<div class="col-sm-9">' +
                '<input required type="text" name="intents[' + (sectionCounter - 1) + '][name]" class="form-control border-success" placeholder="Nhập tên tác vụ">' +
                '<div class="invalid-feedback">Cần đặt tên cho hành động này</div>' +
                '</div>' +
                '</div>' +

                '<div class="row mb-3">' +
                '<label class="col-sm-3 col-form-label">Tùy Chỉnh Câu Phản Hồi <i class="bi bi-question-circle-fill" onclick="show_message(\'Tùy Chỉnh Câu Phản Hồi Lại Khi Thực Hiện Thao Tác Này, Không Muốn Phản Hồi Lại Theo Ý Của Bạn Thì Để Trống\')"></i>:</label>' +
                '<div class="col-sm-9">' +
                '<input type="text" name="intents[' + (sectionCounter - 1) + '][reply]" class="form-control border-success" placeholder="Nhập câu phản hồi tùy chỉnh nếu cần">' +
                '</div>' +
                '</div>' +

                '<div class="row mb-3">' +
                '<label class="col-sm-3 col-form-label">Code YAML <i class="bi bi-question-circle-fill" onclick="show_message(\'Nội Dung Code YAML Cần Thực Hiện<br/>Nội dung Code YAML này được lấy ở Trong Home Assistant: <b>Công cụ nhà phát triển -> Hành Động -> Công cụ phát triển hành động cho phép bạn thực hiện bất kỳ hành động nào có trong Home Assistant.</b><br/> - Khi bạn thực hiện thành công, sẽ sao chép hết nội dung code trong ô nhập liệu đó vào đây\')"></i>:</label>' +
                '<div class="col-sm-9">' +
                '<textarea required name="intents[' + (sectionCounter - 1) + '][data_yaml]" class="form-control border-success" rows="5" placeholder="Nhập code YAML trong Công cụ nhà phát triển của Home Assistant"></textarea>' +
                '<div class="invalid-feedback">Cần nhâp Code YAML của Home Assistant</div>' +
                '</div>' +
                '</div>' +

                '<div class="row mb-3">' +
                '<label class="col-sm-3 col-form-label">Câu Lệnh <i class="bi bi-question-circle-fill" onclick="show_message(\'Câu Lệnh Để Điều Khiển, Chạy Hành Động Này, Không Giới Hạn Nhiều Câu Lệnh, Mỗi Câu Lệnh Là 1 Dòng<br/>- Khi bạn nói đúng 1 trong các câu lệnh được thiết lập, thì tác vụ sẽ được chạy\')"></i>:</label>' +
                '<div class="col-sm-9">' +
                '<textarea required name="intents[' + (sectionCounter - 1) + '][questions]" class="form-control border-success" rows="5" placeholder="Nhập câu lệnh cần thực thi tác vụ này"></textarea>' +
                '<div class="invalid-feedback">Cần nhâp câu lệnh cần thực thi</div>' +
                '</div>' +
                '</div>' +

                '<center>' +
                '<button type="button" class="btn btn-danger rounded-pill" onclick="removeIntentSection(\'' + sectionID + '\', \'Thêm Mới Tác Vụ\')">' +
                'Xóa Tác Vụ' +
                '</button>' +
                '</center>' +
                '</div></div>' +
                '</div>';
            document.getElementById('accordion-container').insertAdjacentHTML('beforeend', newSection);
            sectionCounter++;
        }
    </script>
    <script>
        //Xóa file backup Config
        function delete_file_backup_hass_custom(filePath) {
            if (filePath === "get_value_backup_config") {
                var get_value_backup_config = document.getElementById('backup_custom_hass_json_files').value;
                if (get_value_backup_config === "") {
                    showMessagePHP("Không có tệp nào được chọn để tải xuống");
                } else {
                    filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
                    deleteFile(filePath);
                }
            } else {
                showMessagePHP("Không có tệp nào được chọn để tải xuống.");
            }
        }

        //Tải xuống file backup Config
        function dowlaod_file_backup_hass_custom(filePath) {
            if (filePath === "get_value_backup_config") {
                var get_value_backup_config = document.getElementById('backup_custom_hass_json_files').value;
                if (get_value_backup_config === "") {
                    showMessagePHP("Không có tệp nào được chọn để tải xuống");
                } else {
                    filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
                    downloadFile(filePath);
                }
            } else {
                showMessagePHP("Không có tệp nào được chọn để tải xuống.");
            }
        }

        //onclick xem nội dung file json
        function readJSON_file_path(filePath) {
            if (filePath === "get_value_backup_config") {
                var get_value_backup_config = document.getElementById('backup_custom_hass_json_files').value;
                if (get_value_backup_config === "") {
                    showMessagePHP("Không có tệp nào được chọn để xem nội dung");
                } else {
                    filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
                    read_loadFile(filePath);
                    document.getElementById('name_file_showzz').textContent = "Tên File: " + filePath.split('/').pop();
                    $('#myModal_Home_Assistant').modal('show');
                }
            } else {
                read_loadFile(filePath);
                document.getElementById('name_file_showzz').textContent = "Tên File: " + filePath.split('/').pop();
                $('#myModal_Home_Assistant').modal('show');
            }
        }

        //Test điều khiển code yaml
        function yaml_test_code_hass(id_texara) {
            try {
                const yamlInput = document.getElementById(id_texara).value;
                const xhr = vbotCreateXhr();
                xhr.open('POST', 'includes/php_ajax/Check_Connection.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
                const data = 'yaml_test_control_homeassistant=' + encodeURIComponent(yamlInput);
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showMessagePHP(response.message, 3);
                            } else {
                                show_message(response.message || 'Home Assistant từ chối dữ liệu YAML', 3);
                            }
                        } catch (parseError) {
                            console.error('Phản hồi kiểm tra YAML không phải JSON:', xhr.responseText);
                            show_message('Máy chủ trả về dữ liệu không phải JSON khi kiểm tra YAML. Vui lòng xem Vbot_error.log.');
                        }
                    } else {
                        show_message('Lỗi yêu cầu: ' + xhr.status);
                    }
                };
                xhr.onerror = function() {
                    show_message('Lỗi xảy ra khi gửi yêu cầu');
                };
                xhr.send(data);
            } catch (err) {
                show_message('Dữ liệu không hợp lệ' + err);
            }
        }

    </script>
    <script>
        // Hiển thị modal xem nội dung file json Home_Assistant.json
        ['openModalBtn_Home_Assistant'].forEach(function(id) {
            document.getElementById(id).addEventListener('click', function() {
                var file_name_hassJSON = "<?php echo $jsonFilePath; ?>";
                read_loadFile(file_name_hassJSON);
                document.getElementById('name_file_showzz').textContent = "Tên File: " + file_name_hassJSON.split('/').pop();
                $('#myModal_Home_Assistant').modal('show');
            });
        });
    </script>
  <script>
//Kiểm tra và thông báo lỗi nếu Submit có giá trị input trống
function validateFormVBot() {
    const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    let firstEmptyInput = null;
    let emptyFields = [];
    requiredInputs.forEach(input => {
        input.classList.remove('empty-field');
        if (!input.value.trim()) {
            input.classList.add('empty-field');
            if (!firstEmptyInput) {
                firstEmptyInput = input;
            }
            const accordionContent = input.closest('.accordion-collapse');
            if (accordionContent) {
                const accordionId = accordionContent.id;
                const accordion = new bootstrap.Collapse(accordionContent, {
                    show: true
                });
                const accordionHeader = document.querySelector('[data-bs-target="#' + accordionId + '"]');
                const sectionName = accordionHeader ? accordionHeader.textContent.trim() : '';
                let fieldName = input.getAttribute('placeholder') || 
                              input.getAttribute('name') ||
                              input.getAttribute('id') ||
                              'Trường dữ liệu';
                              
                if (sectionName) {
                    fieldName = '<b class="text-success">'+sectionName+'</b> <b class="text-primary">'+fieldName+'</b>';
                }
                emptyFields.push(fieldName);
            } else {
                let fieldName = input.getAttribute('placeholder') || 
                              input.getAttribute('name') ||
                              input.getAttribute('id') ||
                              'Trường dữ liệu';
                const cardHeader = input.closest('.card')?.querySelector('.card-header');
                if (cardHeader) {
                    fieldName = '<b class="text-success">'+cardHeader.textContent.trim()+' </b> <b class="text-primary">'+fieldName+'</b>';
                }
                emptyFields.push(fieldName);
            }
        }
    });
    if (firstEmptyInput) {
        const message = '<br/><center class="text-danger"><b>Vui lòng điền đầy đủ thông tin cho các trường giá trị</b></center><hr><b><center>Các Danh Mục Sau Còn Thiếu Tham Số</center></b><br/>- ' + emptyFields.join('<br>- ');
        show_message(message);
        const accordionContent = firstEmptyInput.closest('.accordion-collapse');
        if (accordionContent) {
            new bootstrap.Collapse(accordionContent, {
                show: true
            });
            setTimeout(() => {
                firstEmptyInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstEmptyInput.focus();
            }, 350);
        } else {
            firstEmptyInput.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            setTimeout(() => firstEmptyInput.focus(), 500);
        }
        return false;
    }
    return true;
}
  </script>
  <script>
    function normalizeHassCustomSearchText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('vi')
            .trim();
    }

    function initializeHassCustomNavigation() {
        const searchInput = document.getElementById('hass-custom-task-search');
        const clearButton = document.getElementById('hass-custom-search-clear');
        const navigation = document.getElementById('hass-custom-quick-navigation');
        const emptyState = document.getElementById('hass-custom-search-empty');
        const form = document.getElementById('hass-custom-form');
        if (!searchInput || !clearButton || !navigation || !emptyState || !form) {
            return;
        }

        const getTasks = function() {
            return Array.from(form.querySelectorAll('.hass-custom-task[id]'));
        };

        const getTaskLabel = function(task, index) {
            const nameInput = task.querySelector('input[name$="[name]"]');
            const accordionButton = task.querySelector('.accordion-button');
            const heading = task.querySelector('.card-title');
            return String(
                (nameInput && nameInput.value) ||
                (accordionButton && accordionButton.textContent) ||
                (heading && heading.textContent) ||
                'Tác vụ ' + (index + 1)
            ).replace(/\s+/g, ' ').trim();
        };

        const getTaskSearchText = function(task) {
            const fieldValues = Array.from(task.querySelectorAll('input, select, textarea')).map(function(field) {
                if (field.type === 'checkbox') {
                    return field.checked ? 'kích hoạt bật active enabled' : 'tắt inactive disabled';
                }
                return field.value || '';
            }).join(' ');
            return normalizeHassCustomSearchText(task.textContent + ' ' + fieldValues);
        };

        const rebuildNavigation = function() {
            navigation.replaceChildren(new Option('Đi tới tác vụ hoặc khu vực...', ''));
            getTasks().forEach(function(task, index) {
                navigation.appendChild(new Option(getTaskLabel(task, index), task.id));
            });
            navigation.appendChild(new Option('Dữ liệu cấu hình', 'hass-custom-config-section'));
            navigation.appendChild(new Option('Khôi phục dữ liệu', 'hass-custom-recovery-section'));
        };

        const applySearch = function() {
            const query = normalizeHassCustomSearchText(searchInput.value);
            let visibleCount = 0;
            getTasks().forEach(function(task) {
                const visible = query === '' || getTaskSearchText(task).includes(query);
                task.classList.toggle('hass-custom-search-hidden', !visible);
                if (visible) {
                    visibleCount += 1;
                }
            });
            emptyState.style.display = query !== '' && visibleCount === 0 ? 'block' : 'none';
        };

        const showTask = function(task) {
            const collapseElement = task.querySelector('.accordion-collapse');
            if (!collapseElement) {
                return;
            }
            const BootstrapCollapse = window.bootstrap && window.bootstrap.Collapse;
            if (BootstrapCollapse) {
                let instance = typeof BootstrapCollapse.getOrCreateInstance === 'function'
                    ? BootstrapCollapse.getOrCreateInstance(collapseElement, {toggle: false})
                    : (typeof BootstrapCollapse.getInstance === 'function' ? BootstrapCollapse.getInstance(collapseElement) : null);
                if (!instance) {
                    instance = new BootstrapCollapse(collapseElement, {toggle: false});
                }
                instance.show();
                return;
            }
            collapseElement.classList.add('show');
            const toggle = task.querySelector('.accordion-button');
            if (toggle) {
                toggle.classList.remove('collapsed');
                toggle.setAttribute('aria-expanded', 'true');
            }
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
                searchInput.value = '';
                applySearch();
                target.scrollIntoView({behavior: 'smooth', block: 'center'});
                window.setTimeout(function() {
                    if (target.classList.contains('hass-custom-task')) {
                        showTask(target);
                    }
                    target.setAttribute('tabindex', '-1');
                    target.focus({preventScroll: true});
                }, 250);
            }
            navigation.value = '';
        });
        form.addEventListener('input', function(event) {
            if (event.target.matches('input, select, textarea')) {
                applySearch();
            }
        });

        let refreshTimer = null;
        new MutationObserver(function() {
            window.clearTimeout(refreshTimer);
            refreshTimer = window.setTimeout(function() {
                rebuildNavigation();
                applySearch();
            }, 100);
        }).observe(form, {childList: true, subtree: true});

        rebuildNavigation();
        applySearch();
    }

    document.addEventListener('DOMContentLoaded', initializeHassCustomNavigation);
  </script>
    <script src="assets/vendor/prism/prism.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <script src="assets/vendor/prism/prism-json.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
    <?php
    include 'html_js.php';
    ?>
</body>

</html>
