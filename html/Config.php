<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
?>
<?php
if ($Config['contact_info']['user_login']['active']){
session_start();
// Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
if (!isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))) {
    // Nếu chưa đăng nhập hoặc đã quá 12 tiếng, hủy session và chuyển hướng đến trang đăng nhập
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
}
// Cập nhật lại thời gian đăng nhập để kéo dài thời gian session
//$_SESSION['user_login']['login_time'] = time();
}
?>
<?php

if ($Config['backup_upgrade']['config_json']['active'] === true){
$directoryPath_Backup_Config = $Config['backup_upgrade']['config_json']['backup_path'];
//Kiểm tra xem thư mục Backup_Config có tồn tại hay không
if (!is_dir($directoryPath_Backup_Config)) {
    if (mkdir($directoryPath_Backup_Config, 0777, true)) {
		chmod($directoryPath_Backup_Config, 0777);
} 
}
}
$read_stt_token_google_cloud = null;

if (isset($_POST['start_recovery_config_json'])) {

$data_recovery_type = $_POST['start_recovery_config_json'];

if ($data_recovery_type === "khoi_phuc_tu_tep_he_thong"){
$start_recovery_config_json = $_POST['backup_config_json_files'];
if (!empty($start_recovery_config_json)) {
if (file_exists($start_recovery_config_json)) {
    $command = 'cp ' . escapeshellarg($start_recovery_config_json) . ' ' . escapeshellarg($VBot_Offline.'Config.json');
    exec($command, $output, $resultCode);
    if ($resultCode === 0) {
        $messages[] = "Đã khôi phục dữ liệu Config.json từ tệp sao lưu trên hệ thống thành công";
    } else {
        $messages[] = "Lỗi xảy ra khi khôi phục dữ liệu tệp Config.json Mã lỗi: " . $resultCode;
    }
} else {
    $messages[] = "Lỗi: Tệp ".basename($start_recovery_config_json)." không tồn tại trên hệ thống";
}
    } else {
        $messages[] = "Không có tệp sao lưu Config nào được chọn để khôi phục!";
    }
}else if ($data_recovery_type === "khoi_phuc_tu_tep_tai_len"){
    $uploadOk = 1;
    // Kiểm tra xem tệp có được gửi không
    if (isset($_FILES["fileToUpload_configjson_restore"])) {
        $targetFile = $VBot_Offline.'Config.json';
        $fileName = basename($_FILES["fileToUpload_configjson_restore"]["name"]);
        // Kiểm tra xem tệp có phải là .json không
		if (!preg_match('/\.json$/', $fileName) || !preg_match('/^Config/', $fileName)) {
		$messages[] = "- Chỉ chấp nhận tệp .json, dành cho Config.json";
		$uploadOk = 0;
		}
        // Kiểm tra xem $uploadOk có bằng 0 không
        if ($uploadOk == 0) {
            $messages[] = "- Tệp sao lưu không được tải lên";
        } else {
            // Di chuyển tệp vào thư mục đích
            if (move_uploaded_file($_FILES["fileToUpload_configjson_restore"]["tmp_name"], $targetFile)) {
                $messages[] = "- Tệp " . htmlspecialchars($fileName) . " đã được tải lên và khôi phục thành công";
            } else {
                $messages[] = "- Có lỗi xảy ra khi tải lên tệp sao lưu của bạn";
            }
        }
    } else {
        $messages[] = "- Không có tệp sao lưu nào được tải lên";
    }
}
}

#Lưu lại các giá trị Config.json
if (isset($_POST['all_config_save'])) {

if ($Config['backup_upgrade']['config_json']['active'] === true){

// Lấy ngày và giờ hiện tại
$dateTime = new DateTime();
$newFileName = 'Config_' . $dateTime->format('dmY_His') . '.json';
$destinationFile_Backup_Config = $directoryPath_Backup_Config.'/'.$newFileName;
if (copy($Config_filePath, $destinationFile_Backup_Config)) {
    chmod($destinationFile_Backup_Config, 0777);
    $files_ConfigJso_BUP = glob($directoryPath_Backup_Config.'/*.json');
    if (count($files_ConfigJso_BUP) > $Config['backup_upgrade']['config_json']['limit_backup_files']) {
        // Sắp xếp các file theo thời gian tạo (cũ nhất trước)
        usort($files_ConfigJso_BUP, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        // Xóa file cũ nhất
        $oldestFile_configBaup = array_shift($files_ConfigJso_BUP);
        if (unlink($oldestFile_configBaup)) {
           // echo "Đã xóa file cũ nhất: $oldestFile_configBaup\n";
        }
    }
}
}

#CẬP NHẬT CÁC GIÁ TRỊ TRONG mic
$Config['smart_config']['mic']['id'] = intval($_POST['mic_id']);
$Config['smart_config']['mic']['scan_on_boot'] = isset($_POST['mic_scan_on_boot']) ? true : false;

#CẬP NHẬT CÁC GIÁ TRỊ TRONG API
$Config['api']['active'] = isset($_POST['api_active']) ? true : false;
$Config['api']['port'] = intval($_POST['api_port']);
$Config['api']['show_log']['max_log'] = intval($_POST['max_logs_api']);
$Config['api']['show_log']['active'] = isset($_POST['api_log_active']) ? true : false;
$Config['api']['show_log']['log_lever'] = $_POST['api_log_active_log_lever'] ? true : false;

#Cập nhật giá trị đường dẫn path web ui
$Config['web_interface']['path'] = isset($_POST['webui_path']) ? $_POST['webui_path'] : $directory_path;
$Config['web_interface']['errors_display'] = isset($_POST['webui_errors_display']) ? true : false;


#CẬP NHẬT CÁC GIÁ TRỊ TRONG home_assistant
$Config['home_assistant']['minimum_threshold'] = floatval($_POST['hass_minimum_threshold']);
$Config['home_assistant']['lowest_to_display_logs'] = floatval($_POST['hass_lowest_to_display_logs']);
$Config['home_assistant']['time_out'] = intval($_POST['hass_time_out']);
$Config['home_assistant']['internal_url'] = $_POST['hass_internal_url'];
$Config['home_assistant']['external_url'] = $_POST['hass_external_url'];
$Config['home_assistant']['long_token'] = $_POST['hass_long_token'];
$Config['home_assistant']['active'] = isset($_POST['hass_active']) ? true : false;
$Config['home_assistant']['custom_commands']['active'] = isset($_POST['hass_custom_commands_active']) ? true : false;


#Cập nhật các giá trị trong MQTT Broker
$Config['mqtt_broker']['mqtt_active'] = isset($_POST['mqtt_active']) ? true : false;
$Config['mqtt_broker']['mqtt_show_logs_reconnect'] = isset($_POST['mqtt_show_logs_reconnect']) ? true : false;
$Config['mqtt_broker']['mqtt_retain'] = isset($_POST['mqtt_retain']) ? true : false;
$Config['mqtt_broker']['mqtt_host'] = $_POST['mqtt_host'];
$Config['mqtt_broker']['mqtt_port'] = intval($_POST['mqtt_port']);
$Config['mqtt_broker']['mqtt_time_out'] = intval($_POST['mqtt_time_out']);
$Config['mqtt_broker']['mqtt_connection_waiting_time'] = intval($_POST['mqtt_connection_waiting_time']);
$Config['mqtt_broker']['mqtt_qos'] = intval($_POST['mqtt_qos']);
$Config['mqtt_broker']['mqtt_username'] = $_POST['mqtt_username'];
$Config['mqtt_broker']['mqtt_password'] = $_POST['mqtt_password'];
$Config['mqtt_broker']['mqtt_client_name'] = $_POST['mqtt_client_name'];

#CẬP NHẬT CÁC GIÁ TRỊ TRONG LOG HỆ THỐNG show_log
$Config['smart_config']['show_log']['active'] = isset($_POST['log_active']) ? true : false;
$Config['smart_config']['show_log']['log_display_style'] = $_POST['log_display_style'];

#Cập nhật bật, tắt thông tin khi chương trình khởi động thành công
$Config['smart_config']['read_information_startup']['active'] = isset($_POST['read_information_startup']) ? true : false;
$Config['smart_config']['read_information_startup']['read_number'] = intval($_POST['read_information_startup_read_number']);

#CẬP NHẬT CÁC GIÁ TRỊ TRONG Thiết Bị Đầu Ra/Âm Lượng (Volume):
$Config['smart_config']['speaker']['system']['alsamixer_name'] = $_POST['alsamixer_name'];
$Config['smart_config']['speaker']['volume'] = intval($_POST['bot_volume']);
$Config['smart_config']['speaker']['volume_min'] = intval($_POST['bot_volume_min']);
$Config['smart_config']['speaker']['volume_max'] = intval($_POST['bot_volume_max']);
$Config['smart_config']['speaker']['volume_step'] = intval($_POST['bot_volume_step']);
$Config['smart_config']['speaker']['remember_last_volume'] = isset($_POST['remember_last_volume']) ? true : false;

#Cập Nhật GIá Trị Màn Hình LCD OLED
$Config['display_screen']['active'] = isset($_POST['display_screen_active']) ? true : false;
$Config['display_screen']['connection_type'] = $_POST['display_screen_connection_type'];

#Cập nhật giá trị màn hình kết nối i2c
$Config['display_screen']['lcd_i2c']['screen_type'] = $_POST['lcd_i2c_screen_type'];
$Config['display_screen']['lcd_i2c']['font_size'] = intval($_POST['lcd_i2c_font_size']);
$Config['display_screen']['lcd_i2c']['font_ttf'] = $_POST['lcd_i2c_font_ttf'];

#CẬP NHẬT GIÁ TRỊ TRONG Speak To Text (STT)speak_to_text:
$Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] = $_POST['stt_select'];
$Config['smart_config']['smart_wakeup']['speak_to_text']['duration_recording'] = intval($_POST['duration_recording']);
$Config['smart_config']['smart_wakeup']['speak_to_text']['silence_duration'] = intval($_POST['silence_duration']);
$Config['smart_config']['smart_wakeup']['speak_to_text']['single_utterance_time'] = floatval($_POST['single_utterance_time']);
$Config['smart_config']['smart_wakeup']['speak_to_text']['min_amplitude_threshold'] = intval($_POST['min_amplitude_threshold']);

#Cập nhật Chế Độ Hội Thoại/Trò Chuyện Liên Tục conversation_mode:
$Config['smart_config']['smart_wakeup']['conversation_mode'] = isset($_POST['conversation_mode']) ? true : false;

#CẬP NHẬT CHẾ ĐỘ Hotword Engine Picovoice KEY hotword_engine:
$Config['smart_config']['smart_wakeup']['hotword_engine']['key'] = $_POST['hotword_engine_key'];
$Config['smart_config']['smart_wakeup']['hotword']['lang'] = $_POST['select_hotword_lang'];

#CẬP NHẬT CHẾ ĐỘ Text To Speak (TTS) text_to_speak:
$Config['smart_config']['smart_answer']['cache_tts']['active'] = isset($_POST['cache_tts']) ? true : false;
$Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] = $_POST['tts_select'];
$Config['smart_config']['smart_answer']['text_to_speak']['directory_tts'] = $_POST['directory_tts'];

#Cập Nhật TTS Dev Custom
$Config['smart_config']['smart_answer']['text_to_speak']['tts_dev_customize']['active'] = isset($_POST['tts_dev_customize_active']) ? true : false;

#CẬP NHẬT GIÁ TRỊ TRONG tts GOOGEL CLOUD
$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['language_code'] = $_POST['tts_ggcloud_language_code'];
$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] = $_POST['tts_ggcloud_voice_name'];
$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['speaking_speed'] = floatval($_POST['tts_gcloud_speaking_speed']);

#CẬP NHẬT GIÁ TRỊ TRONG tts Google CLOUD token key
$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['token_key'] = $_POST['tts_ggcloud_key_token'];
$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['language_code'] = $_POST['tts_ggcloud_key_language_code'];
$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] = $_POST['tts_ggcloud_key_voice_name'];
$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['speaking_speed'] = floatval($_POST['tts_ggcloud_key_speaking_speed']);

#cập nhật giá trị trong tts tts_default
$Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['speaking_speed'] = floatval($_POST['tts_default_speaking_speed']);
$Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['quality'] = intval($_POST['tts_default_voice_name']);
$Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['voice_name'] = intval($_POST['tts_default_voice_name']);
$Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['authentication_zai_sid'] = $_POST['authentication_zai_sid'];
$Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['expires_zai_sid'] = $_POST['expires_zai_sid'];

#cập nhật giá trị trong tts tts_edge
$Config['smart_config']['smart_answer']['text_to_speak']['tts_edge']['speaking_speed'] = floatval($_POST['tts_edge_speaking_speed']);
$Config['smart_config']['smart_answer']['text_to_speak']['tts_edge']['voice_name'] = $_POST['tts_edge_voice_name'];
$Config['smart_config']['smart_answer']['text_to_speak']['tts_edge']['language_code'] = $_POST['tts_edge_language_code'];

#Cập nhật giá trị trong Google Cloud Drive
$Config['backup_upgrade']['google_cloud_drive']['active'] = isset($_POST['google_cloud_drive_active']) ? true : false;
$Config['backup_upgrade']['google_cloud_drive']['backup_folder_name'] = $_POST['gcloud_drive_backup_folder_name'];
$Config['backup_upgrade']['google_cloud_drive']['backup_folder_vbot_name'] = $_POST['gcloud_drive_backup_folder_vbot_name'];
$Config['backup_upgrade']['google_cloud_drive']['backup_folder_interface_name'] = $_POST['gcloud_drive_backup_folder_interface_name'];
$Config['backup_upgrade']['google_cloud_drive']['setAccessType'] = $_POST['gcloud_drive_setAccessType'];
$Config['backup_upgrade']['google_cloud_drive']['setPrompt'] = $_POST['gcloud_drive_setPrompt'];

#Cập nhật tts zalo
$apiKeys_ZALO_TTS = array_map('trim', explode("\n", $_POST['tts_zalo_api_key']));
$apiKeys_ZALO_TTS = array_filter(array_map(function($key_tts_ZL) {
    return str_replace("\r", '', $key_tts_ZL);
}, $apiKeys_ZALO_TTS), function($key_tts_ZL) {
	// Loại bỏ các chuỗi trống
     return !empty($key_tts_ZL);
});
$apiKeys_ZALO_TTS = array_values($apiKeys_ZALO_TTS);
$Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['api_key'] = $apiKeys_ZALO_TTS;
$Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['speaking_speed'] = floatval($_POST['tts_zalo_speaking_speed']);
$Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] = intval($_POST['tts_zalo_voice_name']);

#Cập nhật TTS Viettel
$apiKeys_VIETTEL_TTS = array_map('trim', explode("\n", $_POST['tts_viettel_api_key']));
$apiKeys_VIETTEL_TTS = array_filter(array_map(function($key_tts_VT) {
    return str_replace("\r", '', $key_tts_VT);
}, $apiKeys_VIETTEL_TTS), function($key_tts_VT) {
	// Loại bỏ các chuỗi trống
     return !empty($key_tts_VT);
});
$apiKeys_VIETTEL_TTS = array_values($apiKeys_VIETTEL_TTS);
$Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['api_key'] = $apiKeys_VIETTEL_TTS;
$Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['speaking_speed'] = floatval($_POST['tts_viettel_speaking_speed']);
$Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] = $_POST['tts_viettel_voice_name'];
$Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['without_filter'] = isset($_POST['tts_viettel_without_filter']) ? true : false;

#Cập nhật cấu hình SSH Server:
#$Config['ssh_server']['ssh_host'] = $_POST['ssh_host'];
$Config['ssh_server']['ssh_port'] = intval($_POST['ssh_port']);
$Config['ssh_server']['ssh_username'] = $_POST['ssh_username'];
$Config['ssh_server']['ssh_password'] = $_POST['ssh_password'];

#CẬP NHẬT cẤU hình BUTTON NÚT NHẤN
$Config['smart_config']['button_active']['active'] = isset($_POST['button_active']) ? true : false;
foreach ($_POST['button'] as $buttonName => $buttonData) {
	$Config['smart_config']['button'][$buttonName]['gpio'] = intval($buttonData['gpio']);
	$Config['smart_config']['button'][$buttonName]['pulled_high'] = isset($buttonData['pulled_high']) ? (bool)$buttonData['pulled_high'] : false;
	$Config['smart_config']['button'][$buttonName]['active'] = isset($buttonData['active']) ? (bool)$buttonData['active'] : false;
	$Config['smart_config']['button'][$buttonName]['bounce_time'] = intval($buttonData['bounce_time']);
	$Config['smart_config']['button'][$buttonName]['long_press']['active'] = isset($buttonData['long_press']['active']) ? (bool)$buttonData['long_press']['active'] : false;
	$Config['smart_config']['button'][$buttonName]['long_press']['duration'] = intval($buttonData['long_press']['duration']);
}

#CẬP NHẬT CẤU HÌNH ÂM THANH HỆ THỐNG
$Config['smart_config']['smart_wakeup']['sound']['welcome']['active'] = isset($_POST['sound_welcome_active']) ? true : false;
$Config['smart_config']['smart_wakeup']['sound']['welcome']['welcome_file'] = $_POST['sound_welcome_file_path'];

#cập nhật cấu hình đèn LED
$Config['smart_config']['led']['active'] = isset($_POST['led_active_on_off']) ? true : false;
$Config['smart_config']['led']['led_gpio'] = intval($_POST['led_gpio']);
$Config['smart_config']['led']['led_type'] = $_POST['led_type_select'];
$Config['smart_config']['led']['led_gpio'] = intval($_POST['led_gpio']);
$Config['smart_config']['led']['number_led'] = intval($_POST['number_led']);
$Config['smart_config']['led']['brightness'] = intval($_POST['led_brightness']);
$Config['smart_config']['led']['led_invert'] = isset($_POST['led_invert']) ? true : false;
$Config['smart_config']['led']['led_starting_up'] = isset($_POST['led_starting_up']) ? true : false;
$Config['smart_config']['led']['effect']['led_think'] = $_POST['led_think'];
$Config['smart_config']['led']['effect']['led_mute'] = $_POST['led_mute'];

#cẬP NHẬT CẤU HÌNH Ưu tiên nguồn phát/tìm kiếm Media:
$music_source_priority_1 = isset($_POST['music_source_priority1']) ? $_POST['music_source_priority1'] : '';
$music_source_priority_2 = isset($_POST['music_source_priority2']) ? $_POST['music_source_priority2'] : '';
$music_source_priority_3 = isset($_POST['music_source_priority3']) ? $_POST['music_source_priority3'] : '';
$Config['media_player']['prioritize_music_source'] = [$music_source_priority_1, $music_source_priority_2, $music_source_priority_3];

#Cập nhật cấu hình PlayList
$Config['media_player']['play_list']['newspaper_play_mode'] = isset($_POST['newspaper_play_mode']) ? $_POST['newspaper_play_mode'] : 'sequential';
$Config['media_player']['play_list']['music_play_mode'] = isset($_POST['music_play_mode']) ? $_POST['music_play_mode'] : 'random';

#cập nhật đồng bộ hóa media với web ui
$Config['media_player']['media_sync_ui']['active'] = isset($_POST['media_sync_ui']) ? true : false;
$Config['media_player']['media_sync_ui']['delay_time'] = intval($_POST['media_sync_ui_delay_time']);

#Cập Nhật Bật hoặc tắt Sử dụng Media Player
$Config['media_player']['active'] = isset($_POST['media_player_active']) ? true : false;
$Config['media_player']['wake_up_in_media_player'] = isset($_POST['wake_up_in_media_player']) ? true : false;

#CẬP NHẬT CÁC GIÁ TRỊ TRONG music_local
$allowed_formats_str = $_POST['music_local_allowed_formats'];
$Config['media_player']['music_local']['path'] = $_POST['music_local_path'];
$Config['media_player']['music_local']['active'] = isset($_POST['music_local_active']) ? true : false;
$Config['media_player']['music_local']['minimum_threshold'] = floatval($_POST['music_local_minimum_threshold']);
$Config['media_player']['music_local']['allowed_formats'] = array_map('trim', explode(',', $allowed_formats_str));

#cẬP NHẬT GIÁ TRỊ youtube
$Config['media_player']['youtube']['google_apis_key'] = $_POST['youtube_google_apis_key'];
$Config['media_player']['youtube']['active'] = isset($_POST['youtube_active']) ? true : false;

#Cập nhật giá trị Zingmp3 xem có kích hoạt hay không
$Config['media_player']['zing_mp3']['active'] = isset($_POST['zing_mp3_active']) ? true : false;

#cẬP NHẬT GIÁ TRỊ Trợ Lý Ảo/Assistant:
$Config['virtual_assistant']['google_gemini']['api_key'] = $_POST['google_gemini_key'];
$Config['virtual_assistant']['google_gemini']['active'] = isset($_POST['google_gemini_active']) ? true : false;
$Config['virtual_assistant']['google_gemini']['time_out'] = intval($_POST['google_gemini_time_out']);

#Cập nhật giá trị trợ lý ảo Default Assistant
$Config['virtual_assistant']['default_assistant']['time_out'] = intval($_POST['default_assistant_time_out']);
$Config['virtual_assistant']['default_assistant']['active'] = isset($_POST['default_assistant_active']) ? true : false;
$Config['virtual_assistant']['default_assistant']['convert_audio_to_text']['used_for_chatbox'] = isset($_POST['default_assistant_convert_audio_to_text_used_for_chatbox']) ? true : false;
$Config['virtual_assistant']['default_assistant']['convert_audio_to_text']['used_for_display_and_logs'] = isset($_POST['default_assistant_convert_audio_to_text_used_for_display_and_logs']) ? true : false;

#Cập nhật giá trị trợ lý ảo chatgpt
$Config['virtual_assistant']['chat_gpt']['key_chat_gpt'] = $_POST['chat_gpt_key'];
$Config['virtual_assistant']['chat_gpt']['role_system_content'] = $_POST['chat_gpt_role_system_content'];
$Config['virtual_assistant']['chat_gpt']['url_api'] = $_POST['chat_gpt_url_api'];
$Config['virtual_assistant']['chat_gpt']['model'] = !empty($_POST['chat_gpt_model']) ? $_POST['chat_gpt_model'] : 'gpt-3.5-turbo';
$Config['virtual_assistant']['chat_gpt']['active'] = isset($_POST['chat_gpt_active']) ? true : false;
$Config['virtual_assistant']['chat_gpt']['time_out'] = intval($_POST['chat_gpt_time_out']);

#Cập nhật trợ lý ảo zalo_assistant
$Config['virtual_assistant']['zalo_assistant']['active'] = isset($_POST['zalo_assistant_active']) ? true : false;
$Config['virtual_assistant']['zalo_assistant']['time_out'] = intval($_POST['zalo_assistant_time_out']);
$Config['virtual_assistant']['zalo_assistant']['set_expiration_time'] = intval($_POST['zalo_assistant_set_expiration_time']);

#Cập nhật trợ lý ảo Difi AI
$Config['virtual_assistant']['dify_ai']['active'] = isset($_POST['dify_ai_active']) ? true : false;
$Config['virtual_assistant']['dify_ai']['session_chat_conversation'] = isset($_POST['dify_ai_session_chat']) ? true : false;
$Config['virtual_assistant']['dify_ai']['time_out'] = intval($_POST['dify_ai_time_out']);
$Config['virtual_assistant']['dify_ai']['api_key'] = $_POST['dify_ai_key'];
$Config['virtual_assistant']['dify_ai']['user_id'] = $_POST['dify_ai_user_id'];

#Cập nhật trợ lý ảo custom assistant customize_developer_assistant_active
$Config['virtual_assistant']['customize_developer_assistant']['active'] = isset($_POST['customize_developer_assistant_active']) ? true : false;

#Cập nhật Bluetooth
$Config['bluetooth']['active'] = isset($_POST['bluetooth_active']) ? true : false;
$Config['bluetooth']['show_logs'] = isset($_POST['bluetooth_show_logs']) ? true : false;
$Config['bluetooth']['gpio_power'] = intval($_POST['bluetooth_gpio_power']);
$Config['bluetooth']['baud_rate'] = intval($_POST['bluetooth_baud_rate']);
$Config['bluetooth']['serial_port'] = $_POST['bluetooth_serial_port'];

#cẬP NHẬT Ưu tiên trợ lý ảo prioritize_virtual_assistants:
$virtual_assistant_priority_1 = isset($_POST['virtual_assistant_priority1']) ? $_POST['virtual_assistant_priority1'] : '';
$virtual_assistant_priority_2 = isset($_POST['virtual_assistant_priority2']) ? $_POST['virtual_assistant_priority2'] : '';
$virtual_assistant_priority_3 = isset($_POST['virtual_assistant_priority3']) ? $_POST['virtual_assistant_priority3'] : '';
$virtual_assistant_priority_4 = isset($_POST['virtual_assistant_priority4']) ? $_POST['virtual_assistant_priority4'] : '';
$virtual_assistant_priority_5 = isset($_POST['virtual_assistant_priority5']) ? $_POST['virtual_assistant_priority5'] : '';
$virtual_assistant_priority_6 = isset($_POST['virtual_assistant_priority6']) ? $_POST['virtual_assistant_priority6'] : '';
$Config['virtual_assistant']['prioritize_virtual_assistants'] = [$virtual_assistant_priority_1, $virtual_assistant_priority_2, $virtual_assistant_priority_3, $virtual_assistant_priority_4, $virtual_assistant_priority_5, $virtual_assistant_priority_6];

#Cập nhật sao lưu trương trình VBot
$Config['backup_upgrade']['advanced_settings']['restart_vbot'] = isset($_POST['restart_vbot_upgrade']) ? true : false;
$Config['backup_upgrade']['advanced_settings']['automatically_check_for_updates'] = isset($_POST['automatically_check_for_updates']) ? true : false;
$Config['backup_upgrade']['advanced_settings']['sound_notification'] = isset($_POST['sound_notification_backup_upgrade']) ? true : false;
$Config['backup_upgrade']['advanced_settings']['refresh_page_ui'] = isset($_POST['refresh_page_ui_backup_upgrade']) ? true : false;

#Cập nhật sao lưu Vbot cài đặt Config.json
$Config['backup_upgrade']['config_json']['active'] = isset($_POST['backup_config_json_active']) ? true : false;
$Config['backup_upgrade']['config_json']['limit_backup_files'] = intval($_POST['limit_backup_files_config_json']);
$Config['backup_upgrade']['config_json']['backup_path'] = $_POST['backup_path_config_json'];
$Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive'] = isset($_POST['backup_vbot_google_drive']) ? true : false;
$Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files'] = intval($_POST['backup_upgrade_vbot_limit_backup_files']);

#Cập nhật sao lưu Custom Home Assistant
$Config['backup_upgrade']['custom_home_assistant']['active'] = isset($_POST['custom_home_assistant_active']) ? true : false;
$Config['backup_upgrade']['custom_home_assistant']['limit_backup_files'] = intval($_POST['limit_backup_custom_home_assistant']);
$Config['backup_upgrade']['custom_home_assistant']['backup_path'] = $_POST['backup_path_custom_home_assistant'];

#Cập nhật sao lưu lời nhắc, thông báo Scheduler
$Config['backup_upgrade']['scheduler']['active'] = isset($_POST['backup_scheduler_active']) ? true : false;
$Config['backup_upgrade']['scheduler']['limit_backup_files'] = intval($_POST['limit_backup_scheduler']);
$Config['backup_upgrade']['scheduler']['backup_path'] = $_POST['backup_path_scheduler'];

#Cập nhật  Chương trình Vbot
$Config['backup_upgrade']['vbot_program']['upgrade']['backup_before_updating'] = isset($_POST['make_a_backup_before_updating_vbot']) ? true : false;

#Cập nhật giao diện vbot
$Config['backup_upgrade']['web_interface']['upgrade']['backup_before_updating'] = isset($_POST['make_a_backup_before_updating_interface']) ? true : false;

#Cập nhật bỏ qua file, thư mục không cần sao lưu trương trình vbot
$Backup_Upgrade_VBot_Exclude_Files_Folder = $_POST['backup_upgrade_vbot_exclude_files_folder'];
$exclude_FilesFolder_Vbot_backup_upgrade = array_filter(array_map('trim', explode("\n", $Backup_Upgrade_VBot_Exclude_Files_Folder)));
#Lưu dữ liệu
$Config['backup_upgrade']['vbot_program']['backup']['exclude_files_folder'] = $exclude_FilesFolder_Vbot_backup_upgrade;

#Cập nhật bỏ qua định dạng tệp tin không cần sao lưu trương trình Vbot
$Backup_Upgrade_VBot_exclude_file_Format = $_POST['backup_upgrade_vbot_exclude_file_format'];
$excludefile_Format_Vbot_backup_upgrade = array_filter(array_map('trim', explode("\n", $Backup_Upgrade_VBot_exclude_file_Format)));
#Lưu dữ liệu
$Config['backup_upgrade']['vbot_program']['backup']['exclude_file_format'] = $excludefile_Format_Vbot_backup_upgrade;

#Cập nhật: giữ lại tệp, thư mục không cho cập nhật chương trình vbot
$vbot_program_upgrade_keep_the_file_folder = $_POST['vbot_program_upgrade_keep_the_file_folder'];
$vbot_program_upgrade_keep_the_file_folder_tuyen = array_filter(array_map('trim', explode("\n", $vbot_program_upgrade_keep_the_file_folder)));
#Lưu dữ liệu
$Config['backup_upgrade']['vbot_program']['upgrade']['keep_file_directory'] = $vbot_program_upgrade_keep_the_file_folder_tuyen;

#Cập nhật: giữ lại tệp, thư mục không cho cập nhật Giao diện vbot
$vbot_web_interface_upgrade_keep_the_file_folder = $_POST['vbot_web_interface_upgrade_keep_the_file_folder'];
$vbot_web_interface_upgrade_keep_the_file_folder_tuyen = array_filter(array_map('trim', explode("\n", $vbot_web_interface_upgrade_keep_the_file_folder)));
#Lưu dữ liệu
$Config['backup_upgrade']['web_interface']['upgrade']['keep_file_directory'] = $vbot_web_interface_upgrade_keep_the_file_folder_tuyen;

#Cập nhật sao lưu Giao diện Web UI
$Config['backup_upgrade']['web_interface']['backup']['backup_to_cloud']['google_drive'] = isset($_POST['backup_web_interface_google_drive']) ? true : false;
$Config['backup_upgrade']['web_interface']['backup']['limit_backup_files'] = intval($_POST['backup_web_interface_limit_backup_files']);

#Cập nhật bỏ qua file, thư mục không cần sao lưu Giao diện vbot
$Backup_Web_Interface_Exclude_Files_Folder = $_POST['backup_upgrade_web_interface_exclude_files_folder'];
$exclude_FilesFolder_Web_Interface_backup_upgrade = array_filter(array_map('trim', explode("\n", $Backup_Web_Interface_Exclude_Files_Folder)));
#Lưu dữ liệu
$Config['backup_upgrade']['web_interface']['backup']['exclude_files_folder'] = $exclude_FilesFolder_Web_Interface_backup_upgrade;

#Cập nhật bỏ qua định dạng tệp tin không cần sao lưu giao diện Vbot
$Backup_Upgrade_web_interface_exclude_file_Format = $_POST['backup_upgrade_web_interface_exclude_file_format'];
$excludefile_Format_web_interface_backup_upgrade = array_filter(array_map('trim', explode("\n", $Backup_Upgrade_web_interface_exclude_file_Format)));
#Lưu dữ liệu
$Config['backup_upgrade']['web_interface']['backup']['exclude_file_format'] = $excludefile_Format_web_interface_backup_upgrade;

#Cập nhật bật tắt Kích hoạt radio
$Config['media_player']['radio']['active'] = isset($_POST['radio_active']) ? true : false;

#Cập nhật bật tắt kích hoạt đọc báo, tin tức
$Config['media_player']['news_paper']['active'] = isset($_POST['news_paper_active']) ? true : false;

#Cập nhật Custom Skill active
$Config['developer_customization']['active'] = isset($_POST['developer_customization_active']) ? true : false;
$Config['developer_customization']['if_custom_skill_can_not_handle']['vbot_processing'] = isset($_POST['developer_customization_vbot_processing']) ? true : false;

#Cập Nhật STT Google Cloud V2
$Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['recognizer_id'] = $_POST['stt_ggcloud_v2_recognizer_id'];
$Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['time_out'] = intval($_POST['stt_ggcloud_v2_time_out']);
$Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['model'] = $_POST['stt_ggcloud_v2_model'];

#Cập nhật lịch, lời nhắc, thông báo
$Config['schedule']['active'] = isset($_POST['schedule_active']) ? true : false;
#Cập nhật xử lý lỗi
$Config['smart_config']['auto_restart_program_error'] = isset($_POST['auto_restart_program_error']) ? true : false;


##############################################
// Khởi tạo mảng radio_data đã cập nhật
// Cập nhật radio_data từ POST
$updated_radio_data = [];
foreach ($_POST as $key => $value) {
    if (strpos($key, 'radio_name_') === 0) {
        $index = str_replace('radio_name_', '', $key);
        $link_key = 'radio_link_' . $index;
        // Kiểm tra cả tên đài và link đài đều có dữ liệu
        if (isset($_POST[$link_key]) && !empty(trim($_POST[$key])) && !empty(trim($_POST[$link_key]))) {
            $updated_radio_data[] = [
                "name" => $_POST[$key],
                "link" => $_POST[$link_key]
            ];
        }
    }
}
// Lưu dữ liệu radio đã cập nhật vào cấu hình
$Config['media_player']['radio_data'] = $updated_radio_data;
##########################################

// Khởi tạo mảng newspaper đã cập nhật
// Cập nhật newspaper từ POST
$updated_news_paper_data = [];
foreach ($_POST as $key_newspaper => $value) {
    if (strpos($key_newspaper, 'newspaper_name_') === 0) {
        $index = str_replace('newspaper_name_', '', $key_newspaper);
        $link_key_newspaper = 'newspaper_link_' . $index;
        // Kiểm tra cả tên đài và link đài đều có dữ liệu
        if (isset($_POST[$link_key_newspaper]) && !empty(trim($_POST[$key_newspaper])) && !empty(trim($_POST[$link_key_newspaper]))) {
            $updated_news_paper_data[] = [
                "name" => $_POST[$key_newspaper],
                "link" => $_POST[$link_key_newspaper]
            ];
        }
    }
}
// Lưu dữ liệu radio đã cập nhật vào cấu hình
$Config['media_player']['news_paper_data'] = $updated_news_paper_data;

#Cập Nhật File JSon STT Google Cloud
if ($_POST['stt_select'] === "stt_ggcloud"){

#Cập nhật json stt Google Cloud V1
$json_data_goolge_cloud_stt = $_POST['stt_ggcloud_json_file_token'];
json_decode($json_data_goolge_cloud_stt);
if (json_last_error() === JSON_ERROR_NONE) {
    // Nếu dữ liệu là null hoặc rỗng, thay thế bằng {}
    if (empty($json_data_goolge_cloud_stt) || $json_data_goolge_cloud_stt === null) {
        $json_data_goolge_cloud_stt = '{}';
    }
    // Xóa bỏ các khoảng trắng không mong muốn ở đầu và cuối chuỗi
    $json_data_goolge_cloud_stt = trim($json_data_goolge_cloud_stt);
    // Lưu dữ liệu vào file
    file_put_contents($stt_token_google_cloud, $json_data_goolge_cloud_stt);
    //$messages[] = 'Dữ liệu stt_token_google_cloud đã được lưu vào file thành công.';
} else {
    $messages[] = 'Lỗi: Dữ liệu stt_token_google_cloud không phải là JSON hợp lệ.';
}

}else if ($_POST['stt_select'] === "stt_ggcloud_v2"){


#Cập nhật json stt Google Cloud V2
$json_data_goolge_cloud_stt = $_POST['stt_ggcloud_v2_json_file_token'];
json_decode($json_data_goolge_cloud_stt);
if (json_last_error() === JSON_ERROR_NONE) {
    // Nếu dữ liệu là null hoặc rỗng, thay thế bằng {}
    if (empty($json_data_goolge_cloud_stt) || $json_data_goolge_cloud_stt === null) {
        $json_data_goolge_cloud_stt = '{}';
    }
    // Xóa bỏ các khoảng trắng không mong muốn ở đầu và cuối chuỗi
    $json_data_goolge_cloud_stt = trim($json_data_goolge_cloud_stt);
    // Lưu dữ liệu vào file
    file_put_contents($stt_token_google_cloud, $json_data_goolge_cloud_stt);
    //$messages[] = 'Dữ liệu stt_token_google_cloud đã được lưu vào file thành công.';
} else {
    $messages[] = 'Lỗi: Dữ liệu stt_token_google_cloud không phải là JSON hợp lệ.';
}
}


#Cập nhật tts Google Cloud
$json_data_goolge_cloud_tts = $_POST['tts_ggcloud_json_file_token'];
json_decode($json_data_goolge_cloud_tts);
if (json_last_error() === JSON_ERROR_NONE) {
    // Nếu dữ liệu là null hoặc rỗng, thay thế bằng {}
    if (empty($json_data_goolge_cloud_tts) || $json_data_goolge_cloud_tts === null) {
        $json_data_goolge_cloud_tts = '{}';
    }
    // Xóa bỏ các khoảng trắng không mong muốn ở đầu và cuối chuỗi
    $json_data_goolge_cloud_tts = trim($json_data_goolge_cloud_tts);
    // Lưu dữ liệu vào file
    file_put_contents($tts_token_google_cloud, $json_data_goolge_cloud_tts);
    //$messages[] = 'Dữ liệu tts_token_google_cloud đã được lưu vào file thành công.';
} else {
    $messages[] = 'Lỗi: Dữ liệu tts_token_google_cloud không phải là JSON hợp lệ.';
}	
$result_ConfigJson = file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

if ($result_ConfigJson !== false) {
    $messages[] = "Cấu hình đã được lưu thành công!";
} else {
    $messages[] = "Đã xảy ra lỗi khi lưu cấu hình";
}
}
#########################

#Lưu các giá trị cấu hình chi tiết trong hotword
if (isset($_POST['save_hotword_theo_lang'])) {
    $lang = $_POST['lang_hotword_get'];
    $Lib_modelFilePath = $_POST['select_file_lib_pv'];
    $updatedConfig = [];
if (empty($Lib_modelFilePath)) {
	$messages[] = "Lỗi, Cần chọn file thư viện Hotword .pv";
}else{
// Kiểm tra giá trị của biến $lang
if ($lang !== 'eng' && $lang !== 'vi') {
    $messages[] = "Thất bại, Giá trị ngôn ngữ không có hoặc không phải là 'eng' hay 'vi'";
}else{
	    foreach ($_POST as $key => $value) {
        if (strpos($key, 'file_name_') === 0) {
            $index = str_replace('file_name_', '', $key);
            $active_key = 'active_' . $index;
            $sensitive_key = 'sensitive_' . $index;
            $active = isset($_POST[$active_key]) && $_POST[$active_key] === 'on';
            $file_name = isset($_POST[$key]) ? $_POST[$key] : '';
            $sensitive = isset($_POST[$sensitive_key]) ? floatval($_POST[$sensitive_key]) : 0.5;
            if ($file_name !== '') {
                $updatedConfig[] = [
                    "active" => $active,
                    "file_name" => $file_name,
                    "sensitive" => $sensitive
                ];
            }
        }
    }
    $Config['smart_config']['smart_wakeup']['hotword']['porcupine'][$lang] = $updatedConfig;
    $Config['smart_config']['smart_wakeup']['hotword']['library'][$lang]['modelFilePath'] = $Lib_modelFilePath;
    file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $messages[] = 'Cập nhật các giá trị trong Hotword thành công';
}
}
}

#Đọc File stt và tts google cloud
 if (file_exists($stt_token_google_cloud)) {
		$read_stt_token_google_cloud = file_get_contents($stt_token_google_cloud);
    } else {
		$read_stt_token_google_cloud = '';
		$messages[] = 'Lỗi: File read_stt_token_google_cloud không tồn tại.';
    }
 if (file_exists($tts_token_google_cloud)) {
		$read_tts_token_google_cloud = file_get_contents($tts_token_google_cloud);
		
    } else {
		$read_tts_token_google_cloud = '';
		$messages[] = 'Lỗi: File read_stt_token_google_cloud không tồn tại.';
    }
?>

<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
<!-- <link href="assets/vendor/prism/prism.min.css" rel="stylesheet"> -->
<link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css">
     <style>
        #modal_dialog_show_config {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            max-width: calc(100vw - 40px);
        }
        #modal_dialog_show_config .modal-content {
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
    </style>
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
			z-index: 2; 
        }
        .scroll-to-bottom {
            bottom: 15px;
        }
        .scroll-to-top {
            bottom: 60px;
        }
    </style>
	
	
 </head>
<body>
    <?php
	//Hiển thị thông báo php
    if (!empty($messages)) {
        $safeMessages = array_map(function($msg) {
            return htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        }, $messages);
        $allMessages = implode("\\n", $safeMessages);
        echo "<script>showMessagePHP('$allMessages');</script>";
    }
    ?>
<!--end Hiển thị thông báo Mesage php -->
<!-- ======= Header ======= -->
<?php
include 'html_header_bar.php'; 
?>
<!-- End Header -->
  <!-- ======= Sidebar ======= -->
<?php
include 'html_sidebar.php';
?>
<!-- End Sidebar-->

<!-- 
<div class="card accordion" id="accordion_button_media_player_source">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player_source" aria-expanded="false" aria-controls="collapse_button_media_player_source">
Tets  Drop Down:</h5>
<div id="collapse_button_media_player_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_media_player_source">
hihi Vũ Tuyển
</div>
</div>
</div>
-->

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Cấu hình <font color="red" onclick="readJSON_file_path('<?php echo $Config_filePath; ?>')">Config.json</font></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active" onclick="readJSON_file_path('<?php echo $Config_filePath; ?>')">Config.json</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
<form class="row g-3 needs-validation" id="hotwordForm" enctype="multipart/form-data" novalidate method="POST" action="">
    <section class="section">
      <div class="row">
        <div class="col-lg-12">
		  <div class="card accordion" id="accordion_button_ssh">
		<div class="card-body">
			  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_ssh" aria-expanded="false" aria-controls="collapse_button_ssh">
                 Cấu Hình Kết Nối SSH Server <font color="red"> (Bắt Buộc)</font>:</h5>
				 <div id="collapse_button_ssh" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_ssh">
                <div class="row mb-3">
                  <label for="ssh_port" class="col-sm-3 col-form-label">Cổng kết nối:</label>
                  <div class="col-sm-9">
                      <input required class="form-control border-success" type="number" name="ssh_port" id="ssh_port" placeholder="<?php echo $Config['ssh_server']['ssh_port']; ?>" value="<?php echo $Config['ssh_server']['ssh_port']; ?>">
                 <div class="invalid-feedback">Cần nhập cổng kết nối tới máy chủ SSH</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="ssh_username" class="col-sm-3 col-form-label">Tên đăng nhập:</label>
                  <div class="col-sm-9">
                      <input required class="form-control border-success" type="text" name="ssh_username" id="ssh_username" placeholder="<?php echo $Config['ssh_server']['ssh_username']; ?>" value="<?php echo $Config['ssh_server']['ssh_username']; ?>">
                 <div class="invalid-feedback">Cần nhập tên đăng nhập của máy chủ SSH</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="ssh_password" class="col-sm-3 col-form-label">Mật khẩu:</label>
                  <div class="col-sm-9">
                      <input required class="form-control border-success" type="text" name="ssh_password" id="ssh_password" placeholder="<?php echo $Config['ssh_server']['ssh_password']; ?>" value="<?php echo $Config['ssh_server']['ssh_password']; ?>">
                 <div class="invalid-feedback">Cần nhập mật khẩu của máy chủ SSH</div>
                  </div>
                </div>
				<center><button type="button" class="btn btn-success rounded-pill" onclick="checkSSHConnection()">Kiểm tra kết nối SSH</button></center>
                </div>
                </div>
                </div>

<div class="card accordion" id="accordion_button_webui_path">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_webui_path" aria-expanded="false" aria-controls="collapse_button_webui_path">
Web Interface (Giao Diện): </h5>
<div id="collapse_button_webui_path" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_webui_path">

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Hiển Thị Lỗi <i class="bi bi-question-circle-fill" onclick="show_message('Chức Năng Này Chỉ Dành Cho Nhà Phát Triển DEV Debug')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="webui_errors_display" id="webui_errors_display" <?php echo $Config['web_interface']['errors_display'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="webui_path" class="col-sm-3 col-form-label">Path (Đường Dẫn):</label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input required type="text" class="form-control border-success" name="webui_path" id="webui_path" placeholder="<?php echo htmlspecialchars($directory_path) ?>" value="<?php echo htmlspecialchars($directory_path) ?>">
<div class="invalid-feedback">Cần nhập đường dẫn path hiện tại của giao diện Web UI</div>
<button class="btn btn-success border-success" type="button" title="<?php echo $directory_path; ?>" onclick="update_webui_link()">Cập Nhật</button>
</div>
</div>
</div>

</div>
</div>
</div>


<div class="card accordion" id="accordion_button_setting_API">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_API" aria-expanded="false" aria-controls="collapse_button_setting_API">
Cấu Hình API:</h5>
<div id="collapse_button_setting_API" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_setting_API">
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích Hoạt API <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng và giao tiếp với VBot thông qua API')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="api_active" id="api_active" <?php echo $Config['api']['active'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>
				
                <div class="row mb-3">
                  <label for="api_port" class="col-sm-3 col-form-label">Port API:</label>
                  <div class="col-sm-9">
				   <div class="input-group mb-3">
                    <input required type="number" class="form-control border-success" name="api_port" id="api_port" max="9999" placeholder="<?php echo htmlspecialchars($Config['api']['port']) ?>" value="<?php echo htmlspecialchars($Config['api']['port']) ?>">
					<div class="invalid-feedback">Cần nhập cổng Port dành cho API!</div>
					<button class="btn btn-success border-success" type="button" title="<?php echo $Protocol.$serverIp.':'.$Port_API; ?>"><a title="<?php echo $Protocol.$serverIp.':'.$Port_API; ?>" style="text-decoration: none; color: inherit;" href="<?php echo $Protocol.$serverIp.':'.$Port_API; ?>" target="_bank">Kiểm Tra</a></button>
				  </div>
				  </div>
                </div>
				
		
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Hiển Thị Log API <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt hiển thị log của API, Chỉ hiển thị khi Debug trực tiếp trên Console, Terminal')"></i> :</label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="api_log_active" id="api_log_active" <?php echo $Config['api']['show_log']['active'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="max_logs_api" class="col-sm-3 col-form-label">Tối Đa Dòng Logs API:</label>
                  <div class="col-sm-9">
                    <input required type="number" class="form-control border-success" name="max_logs_api" id="max_logs_api" max="70" step="1" min="10" placeholder="<?php echo htmlspecialchars($Config['api']['show_log']['max_log']) ?>" value="<?php echo htmlspecialchars($Config['api']['show_log']['max_log']) ?>">
					<div class="invalid-feedback">Cần nhập tối đa dòng logs được hiển thị khi đọc qua đường API</div>
				  </div>
                </div>

                <div class="row mb-3">
                  <label for="api_log_active_log_lever" class="col-sm-3 col-form-label">Mức Độ Hiển Thị Log:</label>
                  <div class="col-sm-9">
                   <select name="api_log_active_log_lever" id="api_log_active_log_lever" class="form-select border-success" aria-label="Default select example">
                      <option value="DEBUG" <?php echo $Config['api']['show_log']['log_lever'] === 'DEBUG' ? 'selected' : ''; ?>>DEBUG (Các thông tin gỡ lỗi)</option>
                      <option value="INFO" <?php echo $Config['api']['show_log']['log_lever'] === 'INFO' ? 'selected' : ''; ?>>INFO (Các thông tin)</option>
                      <option value="WARNING" <?php echo $Config['api']['show_log']['log_lever'] === 'WARNING' ? 'selected' : ''; ?>>WARNING (Các cảnh báo lỗi)</option>
                      <option value="ERROR" <?php echo $Config['api']['show_log']['log_lever'] === 'ERROR' ? 'selected' : ''; ?>>ERROR (Lỗi nghiêm trọng)</option>
                      <option value="CRITICAL" <?php echo $Config['api']['show_log']['log_lever'] === 'CRITICAL' ? 'selected' : ''; ?>>CRITICAL (Lỗi rất nghiêm trọng)</option>
                    </select>
                  </div>
                </div>
</div>
</div>
</div>


<div class="card accordion" id="accordion_button_volume_setting">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_volume_setting" aria-expanded="false" aria-controls="collapse_button_volume_setting">
Cấu Hình Âm Thanh Volume/Mic:
</h5>
<div id="collapse_button_volume_setting" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_volume_setting">

<div class="card">
<div class="card-body">
<h5 class="card-title" title="Âm Lượng (Volume)/Audio Out">Cài Đặt Mic &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Bạn có thể tham khảo hướng dẫn tại đây: <a href=\'FAQ.php\' target=\'_bank\'>Hướng Dẫn</a>')"></i> &nbsp;:</h5>

                <div class="row mb-3">
                  <label for="mic_id" class="col-sm-3 col-form-label">ID Mic <i class="bi bi-question-circle-fill" onclick="show_message('Bạn có thể tham khảo hướng dẫn tại đây: <a href=\'FAQ.php\' target=\'_bank\'>Hướng Dẫn</a>')"></i>:</label>
                  <div class="col-sm-9">
				  <div class="input-group mb-3">
                      <input required class="form-control border-success" type="number" name="mic_id" id="mic_id" placeholder="<?php echo $Config['smart_config']['mic']['id']; ?>" value="<?php echo $Config['smart_config']['mic']['id']; ?>">
                 	     
				 <div class="invalid-feedback">Cần nhập ID của Mic!</div>
				  <button class="btn btn-success border-success" type="button" onclick="scan_audio_devices('scan_mic')">Tìm Kiếm</button>
  
                  </div>
                </div>
                </div>
				
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Auto Scan Mic <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật hệ thống sẽ tìm kiếm và liệt kê các ID và Tên của Microphone có trên hệ thống, và hiển thị ra các đường Logs mỗi khi trương trình được khởi chạy')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="mic_scan_on_boot" id="mic_scan_on_boot" <?php echo $Config['smart_config']['mic']['scan_on_boot'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>
				<div class="row mb-3">
				 <div id="mic_scanner"></div>
				</div>
</div></div>
<div class="card">
<div class="card-body">
<h5 class="card-title" title="Âm Lượng (Volume)/Audio Out">Âm Lượng (Volume)/Audio Out &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('<font color=green>- Trương trình sẽ tương tác và thay đổi âm lượng của trình phát VLC <br/>- Sẽ không can thiệp vào âm lượng trên hệ thống của thiết bị (Trương trình sẽ bị giới hạn mức âm lượng, nếu âm lượng của hệ thống alsamixer đầu ra bị hạn chế hoặc được đặt ở mức thấp)</font><br/>Bạn có thể tham khảo hướng dẫn tại đây: <a href=\'FAQ.php\' target=\'_bank\'> Hướng Dẫn</a>')"></i> &nbsp;:</h5>

                <div class="row mb-3">
                  <label for="alsamixer_name" class="col-sm-3 col-form-label" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tên thiết bị âm thanh đầu ra của hệ thống có trong alsamixer">Tên thiết bị (alsamixer) <i class="bi bi-question-circle-fill" onclick="show_message('Tên của thiết bị âm thanh đầu ra trong alsamixer, cần điền đúng tên thiết bị âm thanh đầu ra hiện tại của alsamixer<br/><br/>- nếu không biết đâu là thiết bị âm thanh đầu ra thì bạn có thể phát 1 bài nhạc bằng vlc ví dụ: <b>$: vlc 1.mp3</b> sau đó vào alsamixer bằng lệnh: <b>$: alsamixer</b> thay đổi âm lượng của các thiết bị có trong đó để xác định xem đâu là tên thiết bị đầu ra')"></i>:</label>
                  <div class="col-sm-9">
				  <div class="input-group mb-3">
                      <input class="form-control border-success" type="text" name="alsamixer_name" id="alsamixer_name" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tên thiết bị âm thanh đầu ra của hệ thống có trong alsamixer" placeholder="<?php echo $Config['smart_config']['speaker']['system']['alsamixer_name']; ?>" value="<?php echo $Config['smart_config']['speaker']['system']['alsamixer_name']; ?>">
                    <button class="btn btn-success border-success" type="button" onclick="scan_audio_devices('scan_alsamixer')">Tìm Kiếm</button>
					</div>
					</div>
                  </div>
                <div class="row mb-3">
                  <label for="bot_volume" class="col-sm-3 col-form-label" title="Âm lượng khi chạy lần đầu tiên">Âm lượng <i class="bi bi-question-circle-fill" onclick="show_message('Đặt mức âm lượng mặc định khi bắt đầu khởi chạy chương trình')"></i> :</label>
                  <div class="col-sm-9">
				  
                      <input required class="form-control border-success" step="1" min="0" max="100" type="number" name="bot_volume" id="bot_volume" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Âm lượng khi chạy lần đầu tiên" placeholder="<?php echo $Config['smart_config']['speaker']['volume']; ?>" value="<?php echo $Config['smart_config']['speaker']['volume']; ?>">
					<div class="invalid-feedback">Cần nhập âm lượng khi khởi động!</div>
					</div>
                  </div>
				  
                <div class="row mb-3">
                  <label for="bot_volume_min" class="col-sm-3 col-form-label" title="Mức âm lượng sẽ giảm xuống thấp nhất">Âm lượng thấp nhất <i class="bi bi-question-circle-fill" onclick="show_message('Mức âm lượng thấp nhất cho phép khi giảm âm lượng, thấp nhất là 0')"></i> :</label>
                  <div class="col-sm-9">
                      <input required class="form-control border-success" step="1" min="0" max="100" type="number" name="bot_volume_min" id="bot_volume_min" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Mức âm lượng sẽ giảm xuống thấp nhất" placeholder="<?php echo $Config['smart_config']['speaker']['volume_min']; ?>" value="<?php echo $Config['smart_config']['speaker']['volume_min']; ?>">
                    <div class="invalid-feedback">Cần nhập âm lượng hạ xuống thấp nhất khi Bot thay đổi!</div>
					</div>
                  </div>
				  
                <div class="row mb-3">
                  <label for="bot_volume_max" class="col-sm-3 col-form-label" title="Mức âm lượng sẽ tăng lên cao nhất">Âm lượng cao nhất <i class="bi bi-question-circle-fill" onclick="show_message('Mức âm lượng cao nhất khi tăng âm lương, cao nhất là 100')"></i> :</label>
                  <div class="col-sm-9">
                      <input required class="form-control border-success" step="1" min="0" max="100" type="number" name="bot_volume_max" id="bot_volume_max" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Mức âm lượng sẽ tăng lên cao nhất" placeholder="<?php echo $Config['smart_config']['speaker']['volume_max']; ?>" value="<?php echo $Config['smart_config']['speaker']['volume_max']; ?>">
                    <div class="invalid-feedback">Cần nhập âm lượng tối đa khi Bot thay đổi!</div>
					</div>
                  </div>
				  
                <div class="row mb-3">
                  <label for="bot_volume_step" class="col-sm-3 col-form-label" title="Bước âm lượng khi được thay đổi">Bước âm lượng <i class="bi bi-question-circle-fill" onclick="show_message('Bước âm lượng thay đổi khi mỗi lần tăng hoặc giảm âm lượng')"></i> :</label>
                  <div class="col-sm-9">
                      <input required class="form-control border-success" step="1" min="0" max="100" type="number" name="bot_volume_step" id="bot_volume_step" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Bước âm lượng khi được thay đổi" placeholder="<?php echo $Config['smart_config']['speaker']['volume_step']; ?>" value="<?php echo $Config['smart_config']['speaker']['volume_step']; ?>">
                    <div class="invalid-feedback">Cần nhập âm lượng tối đa khi Bot thay đổi!</div>
					</div>
                  </div>

                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Ghi Nhớ Âm Lượng <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật hệ thống sẽ lưu lại giá trị âm lượng vào tệp Config.json mỗi khi được thay đổi trong quá trình Bot hoạt động')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="remember_last_volume" id="remember_last_volume" <?php echo $Config['smart_config']['speaker']['remember_last_volume'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>

				<div class="row mb-3">
				 <div id="alsamixer_scan"></div>
				</div>
</div>
</div>
</div>
</div>
</div>


<div class="card accordion" id="accordion_button_hotword_engine">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_hotword_engine" aria-expanded="false" aria-controls="collapse_button_hotword_engine">
Cấu Hình Hotword Engine/Picovoice :</h5>
           
<div id="collapse_button_hotword_engine" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_hotword_engine" style="">

<div class="card">
<div class="card-body">
<h5 class="card-title" title="Key Picovoice">Picovoice <i class="bi bi-question-circle-fill" onclick="show_message('Đăng ký, lấy key: <a href=\'https://console.picovoice.ai\' target=\'_bank\'>https://console.picovoice.ai</a>')"></i> :</h5>
         <div class="row mb-3">
                  <label for="hotword_engine_key" class="col-sm-3 col-form-label">Token Key:</label>
                  <div class="col-sm-9">
				  <div class="input-group mb-3">
                      <input required class="form-control border-success" type="text" name="hotword_engine_key" id="hotword_engine_key" placeholder="<?php echo $Config['smart_config']['smart_wakeup']['hotword_engine']['key']; ?>" value="<?php echo $Config['smart_config']['smart_wakeup']['hotword_engine']['key']; ?>">
                 <div class="invalid-feedback">Cần nhập key Picovoice để gọi Hotword!</div>
				  <button class="btn btn-success border-success" type="button" onclick="test_key_Picovoice()">Kiểm Tra</button>
				
                  </div>
                  </div>
                </div>
            </div>
          </div>
		  
<div class="card">
<div class="card-body">
<h5 class="card-title">Hotword <i class="bi bi-question-circle-fill" onclick="show_message('Danh sách file thư viện Porcupine: <a href=\'https://github.com/Picovoice/porcupine/tree/master/lib/common\' target=\'_bank\'>Github</a><br/>Mẫu các từ khóa đánh thức: <a href=\'https://github.com/Picovoice/porcupine/tree/master/resources\' target=\'_bank\'>Github</a>')"></i> :</h5>

     
     <div class="form-floating mb-3">			
<select name="select_hotword_lang" id="select_hotword_lang" class="form-select border-success" aria-label="Default select example">
<option value="vi" <?php echo $Config['smart_config']['smart_wakeup']['hotword']['lang'] === 'vi' ? 'selected' : ''; ?>>Tiếng việt</option>
<option value="eng" <?php echo $Config['smart_config']['smart_wakeup']['hotword']['lang'] === 'eng' ? 'selected' : ''; ?>>Tiếng anh</option>
</select>
<label for="select_hotword_lang">Chọn ngôn ngữ để gọi, đánh thức Bot:</label>
</div>
			
<table class="table table-bordered border-primary">
                <thead>
                  <tr>
                    <th scope="col" colspan="5">  <h5 class="card-title"><center><font color=red>Cài đặt nâng cao Hotword:</font></center></h5></th>
                  </tr>
				   <tr>
                    <th scope="col" colspan="8"><center>
					<button type="button" class="btn btn-primary rounded-pill" onclick="loadConfigHotword('vi')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Cài đặt Hotword Tiếng Việt">Tiếng Việt</button>
					<button type="button" class="btn btn-primary rounded-pill" onclick="loadConfigHotword('eng')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Cài đặt Hotword Tiếng Anh">Tiếng Anh</button>
					<button type="button" class="btn btn-warning rounded-pill" onclick="reload_hotword_config()" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tự động tìm scan các file Hotword có trong thư mục eng và vi để cấu hình trong Config.json">Scan Và Ghi Mới</button>
				
					</center>
					<span id="language_hotwordd" value=""></span>
					</tr>
					</thead> 
                
                  <thead id="results_body_hotword1">
                  </thead>
				   
                <tbody id="results_body_hotword">
               

                </tbody>
              </table>

            </div>
          </div>
							
</div>
</div>
</div>





				
				
<div class="card accordion" id="accordion_button_setting_stt">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_stt" aria-expanded="false" aria-controls="collapse_button_setting_stt">
Speak To Text (STT) &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi giọng nói thành văn bản để chương trình xử lý dữ liệu')"></i> &nbsp;:</h5>
           
<div id="collapse_button_setting_stt" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_stt" style="">

			  <div class="row mb-3">
                  <label for="duration_recording" class="col-sm-3 col-form-label" title="Thời gian thu âm tối đa">Thời gian lắng nghe tối đa (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian lắng nghe tối đa khi Bot được đánh thức')"></i> :</label>
                  <div class="col-sm-9">
                      <input class="form-control border-success" type="number" step="1" min="4" max="10" name="duration_recording" id="duration_recording" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Thời gian thu âm tối đa" placeholder="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['duration_recording']; ?>" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['duration_recording']; ?>">
                    </div>
                  </div>
                <div class="row mb-3">
                  <label for="silence_duration" class="col-sm-3 col-form-label" title="Thời gian im lặng tối đa khi thu âm">Thời gian im lặng tối đa (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian im lặng tối đa, khi phát hiện im lặng trong quá trình đang lắng nghe thì sẽ dừng lắng nghe, (tham số phải nhỏ hơn thời gian lắng nghe tối đa)')"></i> :</label>
                  <div class="col-sm-9">
                      <input class="form-control border-success" step="1" min="1" max="10" type="number" name="silence_duration" id="silence_duration" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Thời gian im lặng tối đa khi thu âm" placeholder="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['silence_duration']; ?>" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['silence_duration']; ?>">
                    </div>
                  </div>

                <div class="row mb-3">
                  <label for="single_utterance_time" class="col-sm-3 col-form-label" title="Thời gian im lặng tối đa khi thu âm">Thời gian tối đa dừng câu, từ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian im lặng tối đa ngắt câu từ được coi là xong (single_utterance)')"></i> :</label>
                  <div class="col-sm-9">
                      <input class="form-control border-success" step="0.1" min="0.1" max="6" type="number" name="single_utterance_time" id="single_utterance_time" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Thời gian im lặng tối đa ngắt câu từ được coi là xong (single_utterance)" placeholder="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['single_utterance_time']; ?>" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['single_utterance_time']; ?>">
                    </div>
                  </div>


                <div class="row mb-3">
                  <label for="min_amplitude_threshold" class="col-sm-3 col-form-label" title="Mức âm lượng sẽ giảm xuống thấp nhất">Ngưỡng biên độ tối thiểu (RMS) <i class="bi bi-question-circle-fill" onclick="show_message('Ngưỡng biên độ để đánh giá đang được im lặng khi lắng nghe, (biên độ càng cao thì cần âm thanh môi trường lớn và ngược lại)')"></i> :</label>
                  <div class="col-sm-9">
                      <input class="form-control border-success" step="10" min="310" max="2000" type="number" name="min_amplitude_threshold" id="min_amplitude_threshold" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Ngưỡng biên độ tối thiểu để nhận biết là có âm thanh" placeholder="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['min_amplitude_threshold']; ?>" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['min_amplitude_threshold']; ?>">
                    </div>
                  </div>


				  
			 <div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title" title="Chuyển giọng nói thành văn bản">Lựa chọn STT (Speak To Text):</h5>
			  <?php 
			  $GET_stt_select = $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select']; 
			  if ($GET_stt_select === "stt_default"){
				  $replace_text_stt = "Mặc Định";
			  }else if ($GET_stt_select === "stt_ggcloud"){
				  $replace_text_stt = "Google Cloud V1";
			  }else if ($GET_stt_select === "stt_ggcloud_v2"){
				  $replace_text_stt = "Google Cloud V2";
			  }else{
				  $replace_text_stt = "Không có dữ liệu";
			  }
			  ?>
			  <center>Bạn đang dùng TTS: <font color=red><?php echo $replace_text_stt; ?></font></center>
				<div class="col-sm-9">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="stt_select" id="stt_default" value="stt_default" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_default' ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="stt_default">STT Mặc Định VBot (Free)</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="stt_select" id="stt_ggcloud" value="stt_ggcloud" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_ggcloud' ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="stt_ggcloud">STT Google Cloud V1 (Authentication.json) <i class="bi bi-question-circle-fill" onclick="show_message('Hướng Dẫn Đăng Ký Hãy Xem Ở Hướng Dẫn Sau Trong Thư  Mục <b>Guide</b> -> <b>Tạo STT Google Cloud</b> <br/><br/>-Link: <a href=\'https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ\' target=\'_bank\'>https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>')"></i></label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="stt_select" id="stt_ggcloud_v2" value="stt_ggcloud_v2" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_ggcloud_v2' ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="stt_ggcloud_v2">STT Google Cloud V2 (Authentication.json) <i class="bi bi-question-circle-fill" onclick="show_message('Hướng Dẫn Đăng Ký Hãy Xem Ở Hướng Dẫn Sau Trong Thư  Mục <b>Guide</b> -> <b>Tạo STT Google Cloud</b><br/>Lệnh Update Cập Nhật Lib: <b>$:> pip install --upgrade google-cloud-speech</b><br/><br/>-Link: <a href=\'https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ\' target=\'_bank\'>https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>')"></i></label>
                    </div>
                  </div>
            </div>
          </div>

        </div>

        <div class="col-lg-6">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Cấu hình STT:</h5>
            
<!-- ẩn hiện cấu hình select_stt_ggcloud_html -->
<div id="select_stt_ggcloud_html" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển giọng nói thành văn bản"><center><font color=red>STT Google Cloud V1 (Authentication.json)</font></center></h4>

<div class="form-floating mb-3">
<textarea class="form-control border-success" placeholder="Tệp tin json xác thực" name="stt_ggcloud_json_file_token" id="stt_ggcloud_json_file_token" style="height: 150px;">
<?php echo htmlspecialchars(trim($read_stt_token_google_cloud)); ?>
</textarea>
<label for="stt_ggcloud_json_file_token">Tệp tin json xác thực:</label>
</div>
<div class="form-floating mb-3">
<center><button type="button" class="btn btn-success" title="Tải xuống" onclick="downloadFile('<?php echo $VBot_Offline.$Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['authentication_json_file']; ?>')"><i class="bi bi-download"></i> Tải Xuống Tệp Json</button></center>
</div>
                    <div class="form-floating mb-3">
                      <input readonly type="text" class="form-control border-danger" name="stt_ggcloud_language_code" id="stt_ggcloud_language_code" placeholder="vi-VN" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['language_code']; ?>">
                      <label for="stt_ggcloud_language_code">Ngôn Ngữ:</label>
                    </div>

                    <div class="form-floating mb-3">
                      <input readonly type="number" class="form-control border-danger" min="0" step="1" name="stt_ggcloud_rate" id="stt_ggcloud_rate" placeholder="16000" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['rate']; ?>">
                      <label for="stt_ggcloud_rate">Sample Rate:</label>
                    </div>
					
                    <div class="form-floating mb-3">
                      <input readonly type="number" class="form-control border-danger" name="stt_ggcloud_channels" id="stt_ggcloud_channels" placeholder="1" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['channels']; ?>">
                      <label for="stt_ggcloud_channels">Số Lượng Kênh (Channels):</label>
                    </div>
					
                    <div class="form-floating mb-3">
                      <input readonly type="number" class="form-control border-danger" name="stt_ggcloud_chunk" id="stt_ggcloud_chunk" placeholder="1024" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['chunk']; ?>">
                      <label for="stt_ggcloud_chunk">Chunk:</label>
                    </div>

                  </div>


<div id="select_stt_ggcloud_v2_html" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển giọng nói thành văn bản"><center><font color=red>STT Google Cloud V2 (Authentication.json)</font></center></h4>

                    <div class="form-floating mb-3">
                      <input required type="text" class="form-control border-success" name="stt_ggcloud_v2_recognizer_id" id="stt_ggcloud_v2_recognizer_id" placeholder="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['recognizer_id']; ?>" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['recognizer_id']; ?>">
                      <label for="stt_ggcloud_rate">Recognizer ID (Điền ID của bạn vào đây nhé):</label>
                    </div>

                    <div class="form-floating mb-3">
                      <input required type="number" class="form-control border-success" min="0" step="1" max="120" name="stt_ggcloud_v2_time_out" id="stt_ggcloud_v2_time_out" placeholder="60" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['time_out']; ?>">
                      <label for="stt_ggcloud_rate">Thời gian chờ tối đa (giây):</label>
                    </div>

<div class="form-floating mb-3">
<textarea class="form-control border-success" placeholder="Tệp tin json xác thực" name="stt_ggcloud_v2_json_file_token" id="stt_ggcloud_v2_json_file_token" style="height: 150px;">
<?php echo htmlspecialchars(trim($read_stt_token_google_cloud)); ?>
</textarea>
<label for="stt_ggcloud_v2_json_file_token">Tệp tin json xác thực:</label>
</div>

<div class="form-floating mb-3">
<center><button type="button" class="btn btn-success" title="Tải xuống" onclick="downloadFile('<?php echo $VBot_Offline.$Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['authentication_json_file']; ?>')"><i class="bi bi-download"></i> Tải Xuống Tệp Json</button></center>
</div>

                    <div class="form-floating mb-3">
                      <input readonly type="text" class="form-control border-danger" name="stt_ggcloud_language_code" id="stt_ggcloud_language_code" placeholder="vi-VN" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['language_code']; ?>">
                      <label for="stt_ggcloud_language_code">Ngôn Ngữ:</label>
                    </div>

                    <div class="form-floating mb-3">
                      <input readonly type="text" class="form-control border-danger" name="stt_ggcloud_v2_model" id="stt_ggcloud_v2_model" placeholder="short" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['model']; ?>">
                      <label for="stt_ggcloud_rate">Model:</label>
                    </div>

                    <div class="form-floating mb-3">
                      <input readonly type="number" class="form-control border-danger" min="0" step="1" name="stt_ggcloud_rate" id="stt_ggcloud_rate" placeholder="16000" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['rate']; ?>">
                      <label for="stt_ggcloud_rate">Sample Rate:</label>
                    </div>
					
                    <div class="form-floating mb-3">
                      <input readonly type="number" class="form-control border-danger" name="stt_ggcloud_channels" id="stt_ggcloud_channels" placeholder="1" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['channels']; ?>">
                      <label for="stt_ggcloud_channels">Số Lượng Kênh (Channels):</label>
                    </div>
					
                    <div class="form-floating mb-3">
                      <input readonly type="number" class="form-control border-danger" name="stt_ggcloud_chunk" id="stt_ggcloud_chunk" placeholder="1024" value="<?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['chunk']; ?>">
                      <label for="stt_ggcloud_chunk">Chunk:</label>
                    </div>

                  </div>

<!-- ẩn hiện cấu hình select_stt_default_html -->
<div id="select_stt_default_html" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển giọng nói thành văn bản"><center><font color=red>STT Default</font></center></h4>
Không cần cấu hình</div>
          </div>
        </div>
      </div>
</div>
</div>
</div>
</div>

<div class="card accordion" id="accordion_button_setting_tts">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_tts" aria-expanded="false" aria-controls="collapse_button_setting_tts">
Text To Speak (TTS) &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi kết quả từ văn bản thành giọng nói để phát ra loa')"></i> &nbsp;: </h5>
<div id="collapse_button_setting_tts" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_tts" style="">

			  <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt Cache lại TTS:</label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="cache_tts" id="cache_tts" <?php echo $Config['smart_config']['smart_answer']['cache_tts']['active'] ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="cache_tts"> (Bật hoặc Tắt sử dụng Cache)</label>
                    </div>
                  </div>
                </div>
				
                <div class="row mb-3">
                  <label for="directory_tts" class="col-sm-3 col-form-label">Thư Mục Chứa TTS:</label>
                  <div class="col-sm-9">
                      <input readonly class="form-control border-danger" type="text" name="directory_tts" id="directory_tts" placeholder="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['directory_tts']; ?>" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['directory_tts']; ?>">
                  </div>
                </div>
				
<div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title" title="Chuyển giọng nói thành văn bản">Lựa chọn TTS (Text To Speak) <i class="bi bi-question-circle-fill" onclick="show_message('Cần lựa chọn TTS bên dưới để sử dụng hoặc cấu hình cài đặt cho TTS đó')"></i> :</h5>
			  <?php 
			  $GET_tts_select = $Config['smart_config']['smart_answer']['text_to_speak']['tts_select']; 
			  if ($GET_tts_select === "tts_default"){
				  $replace_text_tts = "Mặc Định";
			  }else if ($GET_tts_select === "tts_ggcloud"){
				  $replace_text_tts = "Google Cloud Authentication.json";
			  }else if ($GET_tts_select === "tts_zalo"){
				  $replace_text_tts = "Zalo AI";
			  }else if ($GET_tts_select === "tts_viettel"){
				  $replace_text_tts = "Viettel AI";
			  }else if ($GET_tts_select === "tts_edge"){
				  $replace_text_tts = "Microsoft edge";
			  }else if ($GET_tts_select === "tts_ggcloud_key"){
				  $replace_text_tts = "Google Cloud Key";
			  }else if ($GET_tts_select === "tts_dev_customize"){
				  $replace_text_tts = "TTS DEV Customize";
			  }else{
				  $replace_text_tts = "Không có dữ liệu";
			  }
			  ?>
			  <center>Bạn đang dùng TTS: <font color=red><?php echo $replace_text_tts; ?></font></center>
				<div class="col-sm-9">
				
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tts_select" id="tts_default" value="tts_default" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_default' ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="tts_default">TTS Mặc Định (Free) <i class="bi bi-question-circle-fill" onclick="show_message('Với tts_default này sẽ không mất phí với người dùng và vẫn đảm bảo chất lượng cao, ổn định')"></i></label>
                    </div>
					
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tts_select" id="tts_ggcloud" value="tts_ggcloud" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_ggcloud' ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="tts_ggcloud">TTS Google Cloud (Authentication.json) <i class="bi bi-question-circle-fill" onclick="show_message('Hướng Dẫn Đăng Ký Hãy Xem Ở Hướng Dẫn Sau Trong Thư  Mục <b>Guide</b> -> <b>Tạo STT Google Cloud</b><br/><br/>-Link: <a href=\'https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ\' target=\'_bank\'>https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>')"></i></label>
                    </div>
					
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tts_select" id="tts_ggcloud_key" value="tts_ggcloud_key" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_ggcloud_key' ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="tts_ggcloud_key">TTS Google Cloud (Key) hoặc (Free Key) <i class="bi bi-question-circle-fill" onclick="show_message('Cần sử dụng Key của Google Cloud để xác thực hoặc sử dụng Key miễn phí bằng cách lấy thủ công và có thời gian hết hạn')"></i></label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tts_select" id="tts_zalo" value="tts_zalo" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_zalo' ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="tts_zalo">TTS Zalo (Api Keys) <i class="bi bi-question-circle-fill" onclick="show_message('Cần sử dụng Api keys của zalo càng nhiều Keys càng tốt, Mỗi Keys một dòng<br/>Key Lỗi hoặc Hết giới hạn dùng miễn phí sẽ tự động chuyển vào file BackList, và sẽ tự động làm mới nội dung BackList vào hôm sau<br/>Trang Chủ: <a href=\'https://zalo.ai/account/manage-keys\' target=\'_bank\'>https://zalo.ai/account/manage-keys</a>')"></i></label>
                    </div>
					
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="tts_select" id="tts_viettel" value="tts_viettel" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_viettel' ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="tts_viettel">TTS Viettel (Api Keys) <i class="bi bi-question-circle-fill" onclick="show_message('Cần sử dụng Api keys của Viettel càng nhiều Keys càng tốt, Mỗi Keys một dòng<br/>Key Lỗi hoặc Hết giới hạn dùng miễn phí sẽ tự động chuyển vào file BackList, và sẽ tự động làm mới nội dung BackList vào hôm sau<br/>Trang Chủ: <a href=\'https://viettelai.vn/dashboard/token\' target=\'_bank\'>https://viettelai.vn/dashboard/token</a>')"></i></label>
                    </div>

                    <div class="form-check" >
                      <input  class="form-check-input" type="radio" name="tts_select" id="tts_edge" value="tts_edge" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_edge' ? 'checked' : ''; ?>>
                      <label  class="form-check-label" for="tts_edge">TTS Microsoft Edge (Free) <i class="bi bi-question-circle-fill" onclick="show_message('TTS Microsoft edge Free')"></i></label>
                    </div>

                    <div class="form-check" >
                      <input  class="form-check-input" type="radio" name="tts_select" id="tts_dev_customize" value="tts_dev_customize" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_dev_customize' ? 'checked' : ''; ?>>
                      <label  class="form-check-label" for="tts_dev_customize">TTS DEV Customize <font color=red>(Người Dùng Tự Code)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Người dùng sẽ tự code, chuyển văn bản thành giọng nói nếu chọn tts này, dữ liệu để các bạn code sẽ nằm trong tệp: <b>Dev_TTS.py</b><br/>- Cần thêm kích hoạt bên dưới để sử dụng vào chương trình')"></i></label>
						<div class="form-switch">
						<label class="form-label" for="tts_dev_customize_active">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Nếu Dùng TTS DEV Custom bạn cần phải kích hoạt để được khởi tạo dữ liệu khi chạy chương trình')"></i></label>
						<input class="form-check-input" type="checkbox" name="tts_dev_customize_active" id="tts_dev_customize_active" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_dev_customize']['active'] ? 'checked' : ''; ?>>
						</div>


					</div>
                  </div>

            </div>
          </div>

        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Cấu hình TTS:</h5>
<!-- ẩn hiện cấu hình select_tts_default_html style="display: none;" -->
<div id="select_tts_default_html" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển giọng nói thành văn bản"><center><font color=red>TTS Default</font></center></h4>

<div class="form-floating mb-3">			
<select name="tts_default_quality" id="tts_default_quality" class="form-select border-success" aria-label="Default select example">
<option value="0" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['quality'] === 0 ? 'selected' : ''; ?>>Tiêu chuẩn</option>
<option value="1" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['quality'] === 1 ? 'selected' : ''; ?>>Chất lượng cao</option>
</select>
<label for="tts_default_quality">Chất lượng giọng đọc:</label>
</div>

<div class="form-floating mb-3">  
<input type="number" min="0.8" step="0.1" max="1.2" class="form-control border-success" name="tts_default_speaking_speed" id="tts_default_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['speaking_speed']; ?>">
 <label for="tts_default_speaking_speed" class="form-label">Tốc độ đọc:</label>	
</div>

<div class="form-floating mb-3">			
<select name="tts_default_voice_name" id="tts_default_voice_name" class="form-select border-success" aria-label="Default select example">
<option value="1" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['voice_name'] === 1 ? 'selected' : ''; ?>>Giọng Miền Nam (Nữ)</option>
<option value="3" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['voice_name'] === 3 ? 'selected' : ''; ?>>Giọng Miền Nam (Nam)</option>
<option value="2" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['voice_name'] === 2 ? 'selected' : ''; ?>>Giọng Miền Bắc (Nữ)</option>
<option value="4" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['voice_name'] === 4 ? 'selected' : ''; ?>>Giọng Miền Bắc (Nam)</option>
<option value="6" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['voice_name'] === 6 ? 'selected' : ''; ?>>Giọng Miền Trung (Nữ)</option>
<option value="8" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['voice_name'] === 8 ? 'selected' : ''; ?>>Giọng Miền Trung (Nam)</option>
</select>
<label for="tts_default_voice_name">Giọng đọc:</label>
</div>

<div class="form-floating mb-3">
<input class="form-control border-danger" type="text" name="authentication_zai_sid" id="authentication_zai_sid" placeholder="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['authentication_zai_sid']; ?>" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['authentication_zai_sid']; ?>">
<label for="authentication_zai_sid" class="form-label">Mã Token zai_sid:</label>
</div>
<div class="form-floating mb-3">
<input class="form-control border-danger" type="text" name="expires_zai_sid" id="expires_zai_sid" placeholder="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['expires_zai_sid']; ?>" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['expires_zai_sid']; ?>">
<label for="expires_zai_sid" class="form-label">Hết hạn zai_sid:</label>
</div>
<div class="form-floating mb-3">
<center><button class="btn btn-primary rounded-pill" type="button" onclick="">Get Token zai_sid</button> <i class="bi bi-question-circle-fill" onclick="show_message('zai_sid Chỉ dùng cho trợ lý ảo Default Assistant, với chức năng: Chuyển đổi thêm kết quả thành văn bản (text), Hạn sử dụng 1 năm')"></i></center>
</div>
</div>
<!-- ẩn hiện cấu hình select_tts_default_html style="display: none;" -->

<!-- ẩn hiện cấu hình select_stt_ggcloud_html style="display: none;" -->
<div id="select_tts_ggcloud_html" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển văn bản thành văn bản"><center><font color=red>TTS Google Cloud</font></center></h4>
<div class="form-floating mb-3">			
<select name="tts_ggcloud_language_code" id="tts_ggcloud_language_code" class="form-select border-success" aria-label="Default select example">
<option value="vi-VN" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['language_code'] === 'vi-VN' ? 'selected' : ''; ?>>Tiếng Việt</option>
</select><label for="tts_ggcloud_language_code">Ngôn ngữ:</label></div>
<div class="input-group mb-3">
<div class="form-floating">
<select name="tts_ggcloud_voice_name" id="tts_ggcloud_voice_name" class="form-select border-success" aria-label="Default select example">
<option value="vi-VN-Neural2-A" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Neural2-A' ? 'selected' : ''; ?>>vi-VN-Neural2-A FEMALE</option>
<option value="vi-VN-Neural2-D" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Neural2-D' ? 'selected' : ''; ?>>vi-VN-Neural2-D MALE</option>
<option value="vi-VN-Standard-A" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Standard-A' ? 'selected' : ''; ?>>vi-VN-Standard-A FEMALE</option>
<option value="vi-VN-Standard-B" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Standard-B' ? 'selected' : ''; ?>>vi-VN-Standard-B MALE</option>
<option value="vi-VN-Standard-C" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Standard-C' ? 'selected' : ''; ?>>vi-VN-Standard-C FEMALE</option>
<option value="vi-VN-Standard-D" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Standard-D' ? 'selected' : ''; ?>>vi-VN-Standard-D MALE</option>
<option value="vi-VN-Wavenet-A" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Wavenet-A' ? 'selected' : ''; ?>>vi-VN-Wavenet-A FEMALE</option>
<option value="vi-VN-Wavenet-B" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Wavenet-B' ? 'selected' : ''; ?>>vi-VN-Wavenet-B MALE</option>
<option value="vi-VN-Wavenet-C" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Wavenet-C' ? 'selected' : ''; ?>>vi-VN-Wavenet-C FEMALE</option>
<option value="vi-VN-Wavenet-D" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name'] === 'vi-VN-Wavenet-D' ? 'selected' : ''; ?>>vi-VN-Wavenet-D MALE</option>
</select> 
<label for="tts_ggcloud_voice_name">Giọng đọc:</label>
</div>
<button type="button" name="tts_sample_gcloud_play" id="tts_sample_gcloud_play" class="btn btn-success" onclick="play_tts_sample_gcloud()"><i class="bi bi-play-circle"></i></button>
</div>


<div class="form-floating mb-3">  
<input type="number" min="0.25" step="0.25" max="4.0" class="form-control border-success" name="tts_gcloud_speaking_speed" id="tts_gcloud_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['speaking_speed']; ?>">
 <label for="tts_gcloud_speaking_speed" class="form-label">Tốc độ đọc:</label>	
</div>
<div class="form-floating mb-3">
<textarea class="form-control border-success" placeholder="Tệp tin json xác thực" name="tts_ggcloud_json_file_token" id="tts_ggcloud_json_file_token" style="height: 150px;">
<?php echo htmlspecialchars(trim($read_tts_token_google_cloud)); ?>
</textarea>
<label for="tts_ggcloud_json_file_token">Tệp tin json xác thực:</label>
</div>

<div class="form-floating mb-3">
<center><button type="button" class="btn btn-success" title="Tải xuống" onclick="downloadFile('<?php echo $VBot_Offline.$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['authentication_json_file']; ?>')"><i class="bi bi-download"></i> Tải Xuống Tệp Json</button></center>
</div>


</div>


<!-- ẩn hiện cấu hình select_tts_zalo_html style="display: none;" -->
<div id="select_tts_zalo_html" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển giọng nói thành văn bản"><center><font color=red>TTS Zalo AI</font></center></h4>


<div class="form-floating mb-3">  
<input type="number" min="0.8" step="0.1" max="1.2" class="form-control border-success" name="tts_zalo_speaking_speed" id="tts_zalo_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['speaking_speed']; ?>">
 <label for="tts_zalo_speaking_speed" class="form-label">Tốc độ đọc:</label>	
</div>

<div class="form-floating mb-3">			
<select name="tts_zalo_voice_name" id="tts_zalo_voice_name" class="form-select border-success" aria-label="Default select example">
<option value="1" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 1 ? 'selected' : ''; ?>>(Nữ) Miền Nam</option>
<option value="3" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 3 ? 'selected' : ''; ?>>(Nam) Miền Nam</option>
<option value="2" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 2 ? 'selected' : ''; ?>>(Nữ) Miền Bắc</option>
<option value="4" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 4 ? 'selected' : ''; ?>>(Nam) Miền Bắc</option>
</select>
<label for="tts_zalo_voice_name">Giọng đọc:</label>
</div>



<div class="form-floating mb-3">
<textarea class="form-control border-success" placeholder="Api Keys, Mỗi Keys tương ứng với 1 dòng" name="tts_zalo_api_key" id="tts_zalo_api_key" style="height: 150px;">
<?php
//Hiển thị api Key zalo theo dòng
$apiKeys_tts_zalo = isset($Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['api_key']) ? $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['api_key'] : [];
$textareaContent_tts_zalo = implode("\n", array_map('trim', $apiKeys_tts_zalo));
echo htmlspecialchars($textareaContent_tts_zalo);
?>
</textarea>
<label for="tts_zalo_api_key">Api Keys (Mỗi Keys 1 dòng):</label>
</div>

<div class="form-floating mb-3">
<textarea readonly class="form-control border-danger" name="tts_zalo_backlist_content" id="tts_zalo_backlist_content" style="height: 150px;">
</textarea>
<label for="tts_zalo_api_key">Nội Dung Keys BackList Zalo | <?php echo $Config['smart_config']['backlist_file_name']; ?>:</label>
</div>
<center>
<button type="button" class="btn btn-warning rounded-pill" onclick="changeBacklistValue('backlist->tts_zalo->backlist_limit', '[]')" title="Làm mới nội dung tts_zalo trong Backlist"><i class="bi bi-recycle"></i> Clear BackList</button>
<button type="button" class="btn btn-primary rounded-pill" onclick="getBacklistData('backlist->tts_zalo', 'tts_zalo_backlist_content')" title="Làm mới nội dung tts_zalo trong Backlist"><i class="bi bi-arrow-repeat"></i> Re-Load BackList</button>
</center>
</div>
<!-- ẩn hiện cấu hình select_tts_zalo_html style="display: none;" -->




<!-- ẩn hiện cấu hình select_tts_viettel_html style="display: none;" -->
<div id="select_tts_viettel_html" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển giọng nói thành văn bản"><center><font color=red>TTS Viettel AI</font></center></h4>


<div class="form-floating mb-3">  
<input type="number" min="0.8" step="0.1" max="1.2" class="form-control border-success" name="tts_viettel_speaking_speed" id="tts_viettel_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['speaking_speed']; ?>">
 <label for="tts_viettel_speaking_speed" class="form-label">Tốc độ đọc:</label>	
</div>

<div class="form-floating mb-3">			
<select name="tts_viettel_voice_name" id="tts_viettel_voice_name" class="form-select border-success" aria-label="Default select example">
<option value="hn-quynhanh" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hn-quynhanh' ? 'selected' : ''; ?>>(Nữ) Miền Bắc: Quỳnh Anh</option>
<option value="hn-phuongtrang" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hn-phuongtrang' ? 'selected' : ''; ?>>(Nữ) Miền Bắc: Phương Trang</option>
<option value="hn-thaochi" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hn-thaochi' ? 'selected' : ''; ?>>(Nữ) Miền Bắc: Thảo Chi</option>
<option value="hn-thanhha" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hn-thanhha' ? 'selected' : ''; ?>>(Nữ) Miền Bắc: Thanh Hà</option>
<option value="hn-thanhphuong" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hn-thanhphuong' ? 'selected' : ''; ?>>(Nữ) Miền Bắc: Thanh Phương</option>
<option value="hn-thanhtung" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hn-thanhtung' ? 'selected' : ''; ?>>(Nam) Miền Bắc: Thanh Tùng</option>
<option value="hn-namkhanh" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hn-namkhanh' ? 'selected' : ''; ?>>(Nam) Miền Bắc: Nam Khánh</option>
<option value="hn-tienquan" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hn-tienquan' ? 'selected' : ''; ?>>(Nam) Miền Bắc: Tiến Quân</option>
<option value="hue-maingoc" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hue-maingoc' ? 'selected' : ''; ?>>(Nữ) Miền Trung: Mai Ngọc</option>
<option value="hue-baoquoc" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hue-baoquoc' ? 'selected' : ''; ?>>(Nam) Miền Trung: Bảo Quốc</option>
<option value="hcm-diemmy" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hcm-diemmy' ? 'selected' : ''; ?>>(Nữ) Miền Nam: Diễm My</option>
<option value="hcm-phuongly" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hcm-phuongly' ? 'selected' : ''; ?>>(Nữ) Miền Nam: Phương Ly</option>
<option value="hcm-thuydung" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hcm-thuydung' ? 'selected' : ''; ?>>(Nữ) Miền Nam: Thùy Dung</option>
<option value="hcm-thuyduyen" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hcm-thuyduyen' ? 'selected' : ''; ?>>(Nữ) Miền Nam: Thùy Duyên</option>
<option value="hcm-minhquan" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === 'hcm-minhquan' ? 'selected' : ''; ?>>(Nam) Miền Nam: Minh Quân</option>
</select>
<label for="tts_viettel_voice_name">Giọng đọc:</label>
</div>

<div class="form-floating mb-3">
<div class="form-switch">
 <input class="form-check-input" type="checkbox" name="tts_viettel_without_filter" id="tts_viettel_without_filter" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['without_filter'] ? 'checked' : ''; ?>>
<label class="form-check-label" for="cache_tts"> <i class="bi bi-question-circle-fill" onclick="show_message('Bật để tăng chất lượng giọng nói nhưng tốc độ sẽ xử lý chậm hơn và ngược lại')"> </i> (Bật, Tắt) Tăng chất lượng giọng nói</label>
</div>            
          
</div>            


<div class="form-floating mb-3">
<textarea class="form-control border-success" placeholder="Api Keys, Mỗi Keys tương ứng với 1 dòng" name="tts_viettel_api_key" id="tts_viettel_api_key" style="height: 150px;">
<?php
//Hiển thị api Key Viettel theo dòng
$apiKeys_tts_viettel = isset($Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['api_key']) 
    ? $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['api_key'] 
    : [];
$textareaContent_tts_viettel = implode("\n", array_map('trim', $apiKeys_tts_viettel));
echo htmlspecialchars($textareaContent_tts_viettel);
?>
</textarea>
<label for="tts_viettel_api_key">Api Keys (Mỗi Keys 1 dòng):</label>
</div>

<div class="form-floating mb-3">
<textarea readonly class="form-control border-danger" name="tts_viettel_backlist_content" id="tts_viettel_backlist_content" style="height: 150px;">
</textarea>
<label for="tts_viettel_api_key">Nội Dung Keys BackList Viettel | <?php echo $Config['smart_config']['backlist_file_name']; ?>:</label>
</div>
<center>
<button type="button" class="btn btn-warning rounded-pill" onclick="changeBacklistValue('backlist->tts_viettel->backlist_limit', '[]')" title="Làm mới nội dung tts_viettel trong Backlist"><i class="bi bi-recycle"></i> Clear BackList</button>
<button type="button" class="btn btn-primary rounded-pill" onclick="getBacklistData('backlist->tts_viettel', 'tts_viettel_backlist_content')" title="Làm mới nội dung tts_viettel trong Backlist"><i class="bi bi-arrow-repeat"></i> Re-Load BackList</button>
</center>
</div>
<!-- ẩn hiện cấu hình select_tts_viettel_html style="display: none;" -->


<!-- ẩn hiện cấu hình select_tts_edge_html style="display: none;" -->
<div id="select_tts_edge_html" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển giọng nói thành văn bản"><center><font color=red>TTS Microsoft Edge Azure</font></center></h4>



<div class="form-floating mb-3">  
<input type="number" min="0.1" step="0.1" max="1.9" class="form-control border-success" name="tts_edge_speaking_speed" id="tts_edge_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_edge']['speaking_speed']; ?>">
 <label for="tts_edge_speaking_speed" class="form-label">Tốc độ đọc:</label>	
</div>

<div class="form-floating mb-3">			
<select name="tts_edge_voice_name" id="tts_edge_voice_name" class="form-select border-success" aria-label="Default select example">
<option value="vi-VN-HoaiMyNeural" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_edge']['voice_name'] === 'vi-VN-HoaiMyNeural' ? 'selected' : ''; ?>>Giọng Nữ, vi-VN-HoaiMyNeural</option>
<option value="vi-VN-NamMinhNeural" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_edge']['voice_name'] === 'vi-VN-NamMinhNeural' ? 'selected' : ''; ?>>Giọng Nam, vi-VN-NamMinhNeural</option>
</select>
<label for="tts_edge_voice_name">Giọng đọc:</label>
</div>

<div class="form-floating mb-3">  
<input type="text" class="form-control border-danger" name="tts_edge_language_code" id="tts_edge_language_code" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_edge']['language_code']; ?>">
 <label for="tts_edge_language_code" class="form-label">Ngôn Ngữ:</label>	
</div>

</div>
<!-- ẩn hiện cấu hình select_tts_edge_html style="display: none;" -->




<!-- ẩn hiện cấu hình select_tts_ggcloud_key style="display: none;" -->
<div id="select_tts_ggcloud_key" class="col-12" style="display: none;">
<h4 class="card-title" title="Chuyển giọng nói thành văn bản"><center><font color=red>TTS Google Cloud KEY</font></center></h4>

- Lấy Key Miễn Phí: <i class="bi bi-question-circle-fill" onclick="show_message('Truy Cập: <a href=\'https://www.gstatic.com/cloud-site-ux/text_to_speech/text_to_speech.min.html\' target=\'_bank\'>https://www.gstatic.com/cloud-site-ux/text_to_speech/text_to_speech.min.html</a> <br/>- <b>Nhấn F12</b>, Chuyển Qua Tab: <b>Mạng</b> Lựa Chọn Ngôn Ngữ Tiếng Việt nhập bất kỳ văn bản vào ô rồi nhấn nút: <b>SPEAK IT</b> sau đó <b>Xác Minh Capcha.</b><br/> Nhìn vào Tab vừa nhấn F12 tìm tới giá trị bắt đầu bằng <b>proxy?url=</b> trong toàn bộ giá trị đó tìm tới chỗ: <b>&token=</b> Sau dấu = đó chính mã token hãy sao chép và dán vào ô bên dưới<br/><b>- Lưu Ý: Key Miễn Phí này sẽ có thời gian sử dụng, nếu key hết hạn bạn cần lấy key mới thủ công</b>')"></i>
<br/>- Key Mất Phí: <i class="bi bi-question-circle-fill" onclick="show_message('Key này sẽ nằm trong Project <a href=\'https://console.cloud.google.com\' target=\'_bank\'>https://console.cloud.google.com/</a> chọn vào dự án của bạn, và được kích hoạt API: Cloud Text To Text API (Hoặc có thể tạo mới Project để lấy key, như dùng tệp .json)')"></i>
<div class="form-floating mb-3">  
<input type="text" class="form-control border-success" name="tts_ggcloud_key_token" id="tts_ggcloud_key_token" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['token_key']; ?>">
 <label for="tts_ggcloud_key_token" class="form-label">Token Key:</label>	
</div>

<div class="form-floating mb-3">  
<input type="number" min="0.25" step="0.25" max="4.0" class="form-control border-success" name="tts_ggcloud_key_speaking_speed" id="tts_ggcloud_key_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['speaking_speed']; ?>">
 <label for="tts_ggcloud_key_speaking_speed" class="form-label">Tốc độ đọc:</label>	
</div>


<div class="input-group mb-3">
<div class="form-floating">
<select name="tts_ggcloud_key_voice_name" id="tts_ggcloud_key_voice_name" class="form-select border-success" aria-label="Default select example">
<option value="vi-VN-Neural2-A" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Neural2-A' ? 'selected' : ''; ?>>vi-VN-Neural2-A FEMALE</option>
<option value="vi-VN-Neural2-D" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Neural2-D' ? 'selected' : ''; ?>>vi-VN-Neural2-D MALE</option>
<option value="vi-VN-Standard-A" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Standard-A' ? 'selected' : ''; ?>>vi-VN-Standard-A FEMALE</option>
<option value="vi-VN-Standard-B" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Standard-B' ? 'selected' : ''; ?>>vi-VN-Standard-B MALE</option>
<option value="vi-VN-Standard-C" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Standard-C' ? 'selected' : ''; ?>>vi-VN-Standard-C FEMALE</option>
<option value="vi-VN-Standard-D" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Standard-D' ? 'selected' : ''; ?>>vi-VN-Standard-D MALE</option>
<option value="vi-VN-Wavenet-A" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Wavenet-A' ? 'selected' : ''; ?>>vi-VN-Wavenet-A FEMALE</option>
<option value="vi-VN-Wavenet-B" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Wavenet-B' ? 'selected' : ''; ?>>vi-VN-Wavenet-B MALE</option>
<option value="vi-VN-Wavenet-C" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Wavenet-C' ? 'selected' : ''; ?>>vi-VN-Wavenet-C FEMALE</option>
<option value="vi-VN-Wavenet-D" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name'] === 'vi-VN-Wavenet-D' ? 'selected' : ''; ?>>vi-VN-Wavenet-D MALE</option>
</select> 
<label for="tts_ggcloud_key_voice_name">Giọng đọc:</label>
</div>
<button type="button" name="tts_sample_gcloud_play" id="tts_sample_gcloud_play" class="btn btn-success" onclick="play_tts_sample_gcloud()"><i class="bi bi-play-circle"></i></button>
</div>



<div class="form-floating mb-3">  
<input type="text" class="form-control border-danger" name="tts_ggcloud_key_language_code" id="tts_ggcloud_key_language_code" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['language_code']; ?>">
 <label for="tts_ggcloud_key_language_code" class="form-label">Ngôn Ngữ:</label>	
</div>


</div>
<!-- ẩn hiện cấu hình select_tts_ggcloud_key style="display: none;" -->


</div>
</div>
        </div>
      </div>
      </div>
      </div>
				
                </div>


<div class="card accordion" id="accordion_button_setting_homeassistant">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_homeassistant" aria-expanded="false" aria-controls="collapse_button_setting_homeassistant">
Cấu Hình Home Assistant:</h5>
<div id="collapse_button_setting_homeassistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_setting_homeassistant">
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng điều khiển nhà thông minh Home Assistant')"></i> :</label>
                  <div class="col-sm-9">
				  
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="hass_active" id="hass_active" <?php echo $Config['home_assistant']['active'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>


                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Lệnh tùy chỉnh <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng câu lệnh tùy chỉnh (Custom Command) cho điều khiển nhà thông minh Home Assistant<br/>- Thiết lập câu lệnh trong: <b>Thiết Lập Nâng Cao -> Home Assistant Customize Command</b>')"></i> :</label>
                  <div class="col-sm-9">
				  
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="hass_custom_commands_active" id="hass_custom_commands_active" <?php echo $Config['home_assistant']['custom_commands']['active'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>



			        <div class="row mb-3">
                  <label for="hass_long_token" class="col-sm-3 col-form-label" title="Mã token của nhà thông minh Home Assistant">Mã Token:</label>
                 <div class="col-sm-9">
                      <input required class="form-control border-success" type="text" name="hass_long_token" id="hass_long_token" title="Mã token của nhà thông minh Home Assistant" placeholder="<?php echo htmlspecialchars($Config['home_assistant']['long_token']) ?>" value="<?php echo htmlspecialchars($Config['home_assistant']['long_token']) ?>">
						<div class="invalid-feedback">Cần nhập mã Token của nhà thông minh!</div>
					</div>
                </div>
                <div class="row mb-3">
                  <label for="hass_internal_url" class="col-sm-3 col-form-label" title="Địa chỉ url nội bộ">URL nội bộ:</label>
                 <div class="col-sm-9">
				 <div class="input-group mb-3">
                      <input required class="form-control border-success" type="text" name="hass_internal_url" id="hass_internal_url" placeholder="<?php echo htmlspecialchars($Config['home_assistant']['internal_url']) ?>" title="Địa chỉ url nội bộ" value="<?php echo htmlspecialchars($Config['home_assistant']['internal_url']) ?>">
						<div class="invalid-feedback">Cần nhập URL nội bộ của nhà thông minh!</div>
						
    <button class="btn btn-success border-success" type="button" onclick="CheckConnectionHomeAssistant('hass_internal_url')">Kiểm Tra</button>
  
					</div>
					</div>
                </div>
                <div class="row mb-3">
                  <label for="hass_external_url" class="col-sm-3 col-form-label" title="Địa chỉ url bên ngoài">URL bên ngoài:</label>
                 <div class="col-sm-9">
				 <div class="input-group mb-3">
                      <input class="form-control border-success" type="text" name="hass_external_url" id="hass_external_url" title="Địa chỉ url bên ngoài" placeholder="<?php echo htmlspecialchars($Config['home_assistant']['external_url']) ?>" value="<?php echo htmlspecialchars($Config['home_assistant']['external_url']) ?>">
				      <button class="btn btn-success border-success" type="button" onclick="CheckConnectionHomeAssistant('hass_external_url')">Kiểm Tra</button>
  
					</div>
				   </div>
                </div>
          
                <div class="row mb-3">
                  <label for="hass_minimum_threshold" class="col-sm-3 col-form-label" title="Ngưỡng tối thiểu để tìm kiếm và so sánh thiết bị của bạn với từ khóa">Ngưỡng kết quả tối thiểu <i class="bi bi-question-circle-fill" onclick="show_message('Ngưỡng kết quả cho phép từ 0.1 đến 0.9 ngưỡng càng cao thì yêu cầu độ chính xác cao khi bot tìm kiếm và lọc thiết bị')"></i> :</label>
                 <div class="col-sm-9">
                      <input required class="form-control border-success" type="number" step="0.1" min="0.5" max="0.9" name="hass_minimum_threshold" id="hass_minimum_threshold" title="Ngưỡng tối thiểu để tìm kiếm và so sánh thiết bị của bạn với từ khóa" placeholder="<?php echo htmlspecialchars($Config['home_assistant']['minimum_threshold']) ?>" value="<?php echo htmlspecialchars($Config['home_assistant']['minimum_threshold']) ?>">
						<div class="invalid-feedback">Cần nhập ngưỡng tối thiểu để so sánh tên thiết bị với yêu cầu của bạn!</div>
					</div>
                </div>
				
                <div class="row mb-3">
                  <label for="hass_lowest_to_display_logs" class="col-sm-3 col-form-label" title="Ngưỡng tối thiểu để tìm kiếm và so sánh thiết bị của bạn với từ khóa">Ngưỡng tối thiểu hiển thị ra logs <i class="bi bi-question-circle-fill" onclick="show_message('Ngưỡng kết quả tối thiểu để hiển thị các kết quả chưa đạt ngưỡng ra logs chỉ số từ <b>0 -> 0.45</b> là hợp lý, chỉ số hợp lý trong khoảng 0.35-0.39, chỉ số này cần phải thấp hơn  chỉ số <b>ngưỡng kết quả tối thiểu</b> bên trên')"></i> :</label>
                 <div class="col-sm-9">
                      <input required class="form-control border-danger" type="number" step="0.01" min="0" max="0.45" name="hass_lowest_to_display_logs" id="hass_lowest_to_display_logs" title="Ngưỡng tối thiểu để tìm kiếm và so sánh thiết bị của bạn với từ khóa" placeholder="<?php echo htmlspecialchars($Config['home_assistant']['lowest_to_display_logs']) ?>" value="<?php echo htmlspecialchars($Config['home_assistant']['lowest_to_display_logs']) ?>">
						<div class="invalid-feedback">Cần nhập ngưỡng tối thiểu để hiển thị kết quả dưới ngưỡng ra logs!</div>
					</div>
                </div>
				
                <div class="row mb-3">
                  <label for="hass_time_out" class="col-sm-3 col-form-label" title="Thời gian chờ phản hồi tối đa">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa khi truy vấn và xử lý dữ liệu')"></i> :</label>
                 <div class="col-sm-9">
                      <input required class="form-control border-success" type="number" step="1" min="5" max="60" name="hass_time_out" id="hass_time_out" title="Thời gian chờ phản hồi tối đa" placeholder="<?php echo htmlspecialchars($Config['home_assistant']['time_out']) ?>" value="<?php echo htmlspecialchars($Config['home_assistant']['time_out']) ?>">
                    <div class="invalid-feedback">Cần nhập thời gian tối đa chờ phản hồi!</div>
					</div>
                </div>
</div>
</div>
</div>


<div class="card accordion" id="accordion_button_mqtt_tuyen">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_mqtt_tuyen" aria-expanded="false" aria-controls="collapse_button_mqtt_tuyen">
Cấu Hình MQTT Broker:</h5>
<div id="collapse_button_mqtt_tuyen" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_mqtt_tuyen">


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt sử dụng giao thức MQTT')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="mqtt_active" id="mqtt_active" <?php echo $Config['mqtt_broker']['mqtt_active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Logs MQTT <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để hiển thị logs khi kết nối, mất kết nối MQTT')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="mqtt_show_logs_reconnect" id="mqtt_show_logs_reconnect" <?php echo $Config['mqtt_broker']['mqtt_show_logs_reconnect'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="mqtt_host" class="col-sm-3 col-form-label" title="MQTT Host, máy chủ của MQTT cần kết nối tới">Máy Chủ MQTT: </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input class="form-control border-success" type="text" name="mqtt_host" id="mqtt_host" title="Địa chỉ Link/Url/Host của MQTT Broker" placeholder="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_host']) ?>" value="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_host']) ?>">
<button class="btn btn-success border-success" type="button" title="Kiểm tra kết nối tới MQTT" onclick="checkMQTTConnection()">Kiểm Tra</button>

</div>
</div>
</div>

<div class="row mb-3">
<label for="mqtt_port" class="col-sm-3 col-form-label" title="Thời gian chờ phản hồi tối đa">Cổng PORT: </label>
<div class="col-sm-9">
<input class="form-control border-success" type="number" name="mqtt_port" id="mqtt_port" title="Cổng Port của MQTT Broker" placeholder="1883" value="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_port']) ?>">
</div>
</div>

<div class="row mb-3">
<label for="mqtt_username" class="col-sm-3 col-form-label" title="Tài Khoản Kết Nối MQTT">Tài Khoản: </label>
<div class="col-sm-9">
<input class="form-control border-success" type="text" name="mqtt_username" id="mqtt_username" title="Tài khoản kết nối MQTT" placeholder="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_username']) ?>" value="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_username']) ?>">
</div>
</div>

<div class="row mb-3">
<label for="mqtt_password" class="col-sm-3 col-form-label" title="Mật Khẩu Kết Nối MQTT">Mật Khẩu: </label>
<div class="col-sm-9">
<input class="form-control border-success" type="text" name="mqtt_password" id="mqtt_password" title="Mật khẩu kết nối MQTT" placeholder="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_password']) ?>" value="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_password']) ?>">
</div>
</div>

<div class="row mb-3">
<label for="mqtt_client_name" class="col-sm-3 col-form-label" title="Đặt Tên Client Cho Kết Nối MQTT">Tên Client <i class="bi bi-question-circle-fill" onclick="show_message('Nếu có nhiều hơn 1 thiết bị trong Mạng, bạn cần thay đổi Tên Client cho khác nhau và là duy nhất, ví dụ: <b>VBot1</b> hoặc <b>VBot2</b><br/>Tên Client này sẽ được gắn với <b>state_topic</b> và <b>command_topic</b> trong cấu hình <b>mqtts.yaml</b><br/><br/>Ví dụ tên Client là <b>Vbot1</b>:<br/><b>- state_topic: \'VBot1/switch/mic_on_off/state\'</b> <br/><b>- command_topic: \'VBot1/switch/mic_on_off/set\'</b>')"></i> : </label>
<div class="col-sm-9">
<input class="form-control border-success" type="text" name="mqtt_client_name" id="mqtt_client_name" title="Đặt Tên Client Cho Kết Nối MQTT" placeholder="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_client_name']) ?>" value="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_client_name']) ?>">
</div>
</div>

<div class="row mb-3">
<label for="mqtt_time_out" class="col-sm-3 col-form-label" title="Thời gian chờ (Time Out) (giây)">Thời gian chờ (Time Out) (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ tối đa trong quá trình kết nối, nếu quá thời gian chờ mà không kết nối được thì sẽ thông báo và hệ thống sẽ tự động kết nối lại cho đến khi thành công')"></i>: </label>
<div class="col-sm-9">
<input class="form-control border-success" type="number" min="20" max="120" step="1" name="mqtt_time_out" id="mqtt_time_out" title="Thời gian chờ (Time Out) (giây)" placeholder="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_time_out']) ?>" value="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_time_out']) ?>">
</div>
</div>

<div class="row mb-3">
<label for="mqtt_connection_waiting_time" class="col-sm-3 col-form-label" title="Thời gian chờ kết nối lại (giây)">Thời gian chờ kết nối lại (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ để kết nối lại khi bị mỗi lần bị mất kết nối hoặc kết nối không thành công, hệ thống sẽ tự động kết nối lại cho đến khi thành công')"></i>: </label>
<div class="col-sm-9">
<input class="form-control border-success" type="number" min="10" max="9999" step="1" name="mqtt_connection_waiting_time" id="mqtt_connection_waiting_time" title="Thời gian chờ kết nối lại (giây)" placeholder="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_connection_waiting_time']) ?>" value="<?php echo htmlspecialchars($Config['mqtt_broker']['mqtt_connection_waiting_time']) ?>">
</div>
</div>


<div class="row mb-3">
<label class="col-sm-3 col-form-label">QoS <i class="bi bi-question-circle-fill" onclick="show_message('- QoS 0 (At most once): Tin nhắn được gửi một lần duy nhất mà không có sự xác nhận. Điều này có thể dẫn đến việc mất tin nhắn nếu có sự cố kết nối<br/><br/>- QoS 1 (At least once): Tin nhắn được gửi ít nhất một lần và sẽ có xác nhận từ phía người nhận. Điều này đảm bảo rằng tin nhắn sẽ đến nơi, nhưng có thể nhận được tin nhắn trùng lặp.<br/><br/>- QoS 2 (Exactly once): Tin nhắn sẽ được gửi một lần duy nhất, không trùng lặp và không bị mất. Đây là mức độ bảo mật cao nhất, nhưng cũng đòi hỏi nhiều tài nguyên hơn và độ trễ cao hơn')"></i> :</label>
<div class="col-sm-9">
<div class="input-group">
<select name="mqtt_qos" id="mqtt_qos" class="form-select border-success">
<option value="0" <?php echo $Config['mqtt_broker']['mqtt_qos'] === 0 ? 'selected' : ''; ?>>0</option>
<option value="1" <?php echo $Config['mqtt_broker']['mqtt_qos'] === 1 ? 'selected' : ''; ?>>1</option>
<option value="2" <?php echo $Config['mqtt_broker']['mqtt_qos'] === 2 ? 'selected' : ''; ?>>2</option>
</select>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Retain <i class="bi bi-question-circle-fill" onclick="show_message('- retain=True: Khi bạn gửi một tin nhắn với retain=True, MQTT broker sẽ giữ lại tin nhắn đó và gửi lại cho bất kỳ client nào kết nối vào MQTT đó sau này, ngay cả khi client đó đã không nhận dữ liệu ban đầu.<br/><br/>- retain=False: Tin nhắn sẽ không được lưu trữ. Khi client kết nối vào MQTT, nó sẽ không nhận lại tin nhắn cũ')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="mqtt_retain" id="mqtt_retain" <?php echo $Config['mqtt_broker']['mqtt_retain'] ? 'checked' : ''; ?>>
</div>
</div>
</div>


<div class="row mb-3">
<label class="col-sm-3 col-form-label" title="Đặt Tên Client Cho Kết Nối MQTT">Tạo File Cấu Hình <i class="bi bi-question-circle-fill" onclick="show_message('Hệ thống sẽ tự động tạo các File cấu hình MQTT theo Tên Client mà bạn đã đặt mà không cần chỉnh sửa thủ công, Sao chép toàn bộ nội dung được tạo vào file cấu hình của bạn là xong')"></i> : </label>
<div class="col-sm-9">
<div class="input-group mb-3">
<button class="btn btn-primary border-success" type="button" title="Hiển thị cấu hình mqtts.yaml để liên kết VBot với Home Assistant" onclick="read_YAML_file_path('mqtts.yaml')">mqtts.yaml</button>
<button class="btn btn-success border-success" type="button" title="Hiển thị cấu hình mqtts.yaml để liên kết VBot với Home Assistant" onclick="read_YAML_file_path('scripts.yaml')">scripts.yaml</button>
<button class="btn btn-secondary border-success" type="button" title="Hiển thị cấu hình mqtts.yaml để liên kết VBot với Home Assistant" onclick="read_YAML_file_path('input_text.yaml')">input_text.yaml</button>
<button class="btn btn-info border-success" type="button" title="Hiển thị cấu hình mqtts.yaml để liên kết VBot với Home Assistant" onclick="read_YAML_file_path('lovelace_entities')">lovelace</button>
</div>
</div>
</div>

</div>
</div>
</div>



                <div class="card accordion" id="accordion_button_setting_led">
               <div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_led" aria-expanded="false" aria-controls="collapse_button_setting_led">
                 Cấu Hình Đèn Led:</h5>
                  <div id="collapse_button_setting_led" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_led">


<div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt đèn Led <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng đèn led trạng thái')"></i> :</label>
                  <div class="col-sm-9">
				  
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="led_active_on_off" id="led_active_on_off" <?php echo $Config['smart_config']['led']['active'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>

<div class="row mb-3">
                  <label for="led_type_select" class="col-sm-3 col-form-label">Kiểu loại Led:</label>
                  <div class="col-sm-9">
                   <select name="led_type_select" id="led_type_select" class="form-select border-success" aria-label="Default select example">
                      <option value="ws281x" <?php echo $Config['smart_config']['led']['led_type'] === 'ws281x' ? 'selected' : ''; ?>>ws281x</option>
                      <option value="apa102" <?php echo $Config['smart_config']['led']['led_type'] === 'apa102' ? 'selected' : ''; ?>>apa102</option>
                      <option value="ReSpeaker_Mic_Array_v2.0" <?php echo $Config['smart_config']['led']['led_type'] === 'ReSpeaker_Mic_Array_v2.0' ? 'selected' : ''; ?>>ReSpeaker_Mic_Array_v2.0</option>
					  
					</select>
                  </div>
                </div>


<div class="row mb-3">
                  <label for="led_gpio" class="col-sm-3 col-form-label" title="Chân GPIO để điều khiển đèn LED">Led Pin GPIO: <i class="bi bi-question-circle-fill" onclick="show_message('Chân Data của led sẽ được gán và điều khiển bởi chân GPIO')"></i> :</label>
                  <div class="col-sm-9">
                      <input class="form-control border-success" step="1" min="1" max="30" type="number" name="led_gpio" id="led_gpio" value="<?php echo $Config['smart_config']['led']['led_gpio']; ?>">
                    </div>
                  </div>



				

<div class="row mb-3">
                  <label for="number_led" class="col-sm-3 col-form-label" title="Số lượng đèn Led cần sử dụng">Số lượng LED: <i class="bi bi-question-circle-fill" onclick="show_message('Số lượng đèn Led bạn sử dụng (Mỗi mắt led sẽ là 1)')"></i> :</label>
                  <div class="col-sm-9">
                      <input class="form-control border-success" step="1" min="1" max="150" type="number" name="number_led" id="number_led" value="<?php echo $Config['smart_config']['led']['number_led']; ?>">
                    </div>
                  </div>
				
<div class="row mb-3">
                  <label for="led_brightness" class="col-sm-3 col-form-label" title="Độ sáng của đèn Led">Độ sáng đèn LED: <i class="bi bi-question-circle-fill" onclick="show_message('Độ sáng của Led sẽ từ 0 đến 255, tương ứng với 0 -> 100%')"></i> :</label>
                  <div class="col-sm-9">
                      <input class="form-control border-success" step="0" min="1" max="255" type="number" name="led_brightness" id="led_brightness" value="<?php echo $Config['smart_config']['led']['brightness']; ?>">
                    </div>
                  </div>
				  
<div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Đảo ngược đầu LED <i class="bi bi-question-circle-fill" onclick="show_message('Đảo ngược đầu (Bắt Đầu) sáng của đèn led')"></i> :</label>
                  <div class="col-sm-9">
				  
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="led_invert" id="led_invert" <?php echo $Config['smart_config']['led']['led_invert'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
<div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Đèn LED khi khởi động <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng đèn led báo trạng thái khi trương trình đang khởi chạy')"></i> :</label>
                  <div class="col-sm-9">

					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="led_starting_up" id="led_starting_up" <?php echo $Config['smart_config']['led']['led_starting_up'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
<h5 class="card-title">Hiệu Ứng, Màu Sắc:</h5>

<div class="row mb-3">
                  <label for="led_think" class="col-sm-3 col-form-label" title="Màu Led Khi Lắng Nghe">Led Think: <i class="bi bi-question-circle-fill" onclick="show_message('Mã màu dạng Hex tương ứng với chế độ')"></i> :</label>
                  <div class="col-sm-9">
				  <div class="input-group">
                      <input class="form-control border-success" type="text" name="led_think" id="led_think" value="<?php echo $Config['smart_config']['led']['effect']['led_think']; ?>">
					<input class="form-control-color border-success" type="color" id="color_led_think" onchange="updateColorCode_input('color_led_think', 'led_think')" title="Thay đổi màu Led khi được đánh thức">                   
				   <button class="btn btn-success border-success" type="button" onclick="test_led('led_think')">Test Led</button>
				   </div>
				   </div>
                  </div>

<div class="row mb-3">
                  <label for="led_mute" class="col-sm-3 col-form-label" title="Màu Led khi Microphone được tắt">Led Mute: <i class="bi bi-question-circle-fill" onclick="show_message('Mã màu dạng Hex tương ứng với chế độ')"></i> :</label>
                  <div class="col-sm-9">
				  <div class="input-group">
                      <input class="form-control border-success" type="text" name="led_mute" id="led_mute" value="<?php echo $Config['smart_config']['led']['effect']['led_mute']; ?>">
					<input class="form-control-color border-success" type="color" id="color_led_mutex" onchange="updateColorCode_input('color_led_mutex', 'led_mute')" title="Thay đổi màu LED khi Mic bị tắt">
				   <button class="btn btn-success border-success" type="button" onclick="test_led('led_mute')">Test Led</button>
				   </div>
				   </div>
                  </div>
<center><button type="button" class="btn btn-danger rounded-pill" name="led_off" id="led_off" value="led_off" onclick="test_led('led_off')">Dừng Test LED</button></center>
</div>
</div>
</div>



             
              
                <div class="card accordion" id="accordion_button_setting_bton">
               <div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_bton" aria-expanded="false" aria-controls="collapse_button_setting_bton">
                 Cấu Hình Nút Nhấn:</h5>
                  <div id="collapse_button_setting_bton" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_bton" style="">
	
<div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng nút nhấn hoặc không sử dụng')"></i> :</label>
                  <div class="col-sm-9">
				  
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="button_active" id="button_active" <?php echo $Config['smart_config']['button_active']['active'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
				
			  
<table class="table table-bordered border-primary">
                <thead>
					<tr>
					 <th scope="col" colspan="3"><center><font color=red>Cấu Hình Chung</font></center></th>
					 <th scope="col" colspan="2"><center><font color=red>Nhấn Nhả</font></center></th>
					 <th scope="col" colspan="2"><center><font color=red>Nhấn Giữ</font></center></th>
					</tr>
                  <tr>
                    <th scope="col"><center><font color=blue>Nút Nhấn</font></center></th>
                    <th scope="col"><center><font color=blue>GPIO</font></center></th>
					<th scope="col"><center><font color=blue>Kéo mức thấp</font></center></th>
                    <th scope="col"><center><font color=blue>Kích hoạt</font></center></th>
                    
                    <th scope="col"><center><font color=blue>Thời gian nhấn (ms)</font></center></th>
                    <th scope="col"><center><font color=blue>Kích Hoạt</font></center></th>
                    <th scope="col"><center><font color=blue>Thời Gian Giữ (s)</font></center></th>
                  </tr>
                </thead>
                <tbody>
<?php
    foreach ($Config['smart_config']['button'] as $buttonName => $buttonData) {
		echo '<tr>';
        echo '<th scope="row" style="text-align: center; vertical-align: middle;"><center>' . $buttonName . ':</center></th>';
        echo '<td style="text-align: center; vertical-align: middle;"><!-- GPIO --><input required type="number" style="width: 90px;" class="form-control border-success" min="1" step="1" max="30" name="button[' . $buttonName . '][gpio]" value="' . $buttonData['gpio'] . '" placeholder="' . $buttonData['gpio'] . '"></center><div class="invalid-feedback">Cần nhập Chân GPIO cho nút nhấn</div></td>';
		echo '<td style="text-align: center; vertical-align: middle;"><!-- Pulled High --><div class="form-switch"><input type="checkbox" class="form-check-input" name="button[' . $buttonName . '][pulled_high]"' . ($buttonData['pulled_high'] ? ' checked' : '') . '></div></td>';
		
		echo '<td style="text-align: center; vertical-align: middle;"><!-- Active nhấn nhả --> <div class="form-switch"><center><input type="checkbox" class="form-check-input" name="button[' . $buttonName . '][active]"' . ($buttonData['active'] ? ' checked' : '') . '></div></td>';

		echo '<td><center><!-- bounce_time --><input required type="number" min="20" max="500" step="10" style="width: 100px;" class="form-control border-success" title="" name="button[' . $buttonName . '][bounce_time]" value="' . $buttonData['bounce_time'] . '" ></center><div class="invalid-feedback">Cần nhập Chân GPIO cho nút nhấn</div></td>';
		
		echo '<td style="text-align: center; vertical-align: middle;"><!-- Active nhấn giữ --><div class="form-switch"><input type="checkbox" class="form-check-input" name="button[' . $buttonName . '][long_press][active]"' . ($buttonData['long_press']['active'] ? ' checked' : '') . '></div></td>';
		echo '<td><center><!-- Thời gian Giữ --><input required type="number" min="2" step="1" max="10" style="width: 80px;" class="form-control border-success" title="" name="button[' . $buttonName . '][long_press][duration]" value="' . $buttonData['long_press']['duration'] . '" ></center><div class="invalid-feedback">Cần nhập Chân GPIO cho nút nhấn</div></td>';
		
		echo '</tr>';
	}
?>
</tbody></table>
</div>
</div>
</div>




<div class="card accordion" id="accordion_button_lcd_oled">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_lcd_oled" aria-expanded="false" aria-controls="collapse_button_lcd_oled">
Màn Hình LCD OLED  &nbsp; <i class="bi bi-question-circle-fill" onclick="show_message('Tài Liệu:<br/>- <a href=\'https://github.com/adafruit/Adafruit_Python_SSD1306\' target=\'_bank\'>https://github.com/adafruit/Adafruit_Python_SSD1306</a> <br/>- <a href=\'https://www.the-diy-life.com/add-an-oled-stats-display-to-raspberry-pi-os-bullseye/\' target=\'_bank\'>https://www.the-diy-life.com/add-an-oled-stats-display-to-raspberry-pi-os-bullseye/</a>')"></i>  &nbsp;:</h5>
<div id="collapse_button_lcd_oled" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_lcd_oled" style="">
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt hiển thị sử dụng màn hình LCD OLED')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="display_screen_active" id="display_screen_active" <?php echo $Config['display_screen']['active'] ? 'checked' : ''; ?> title="Nhấn để Bật hoặc Tắt sử dụng màn hình">
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Loại Màn Hình Kết Nối :</label>
<div class="col-sm-9">
<div class="input-group">
<select name="display_screen_connection_type" id="display_screen_connection_type" class="form-select border-success">
<option value="lcd_i2c" <?php echo $Config['display_screen']['connection_type'] === 'lcd_i2c' ? 'selected' : ''; ?>>Kết Nối I2C</option>
<option value="lcd_spi" <?php echo $Config['display_screen']['connection_type'] === 'lcd_spi' ? 'selected' : ''; ?>>Kết Nối SPI</option>
</select>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Văn Bản Đầu <i class="bi bi-question-circle-fill" onclick="show_message('Chỉ Cho phép hiển thị văn bản và không có dấu, không có ký tự đặc biệt')"></i> :</label>
<div class="col-sm-9">
<div class="input-group">
<input class="form-control border-success" type="text" name="display_screen_text_display_center" id="display_screen_text_display_center" placeholder="<?php echo $Config['display_screen']['text_display_center']; ?>" value="<?php echo $Config['display_screen']['text_display_center']; ?>">
</div>
</div>
</div>


<div class="card">
<div class="card-body">
<h5 class="card-title">Cấu Hình Màn I2C <i class="bi bi-question-circle-fill" onclick="show_message('Sơ Đồ Kết Nối Chân Pin Với GPIO (Loại Giao Tiếp i2c 4 chân Pin):<br/><b>- SDA ==> GPIO2 (Pin 3)<br/>- SCL ==> GPIO3 (Pin 5)<br/>- VCC ==> 3.3V (Pin 1)<br/>- GND ==> GND(Pin 14)</b>')"></i>:</h5>
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Mã Màn Hình I2C:</label>
<div class="col-sm-9">
<div class="input-group">
<select name="lcd_i2c_screen_type" id="lcd_i2c_screen_type" class="form-select border-success">
<option value="SSD1306_128_64" <?php echo $Config['display_screen']['lcd_i2c']['screen_type'] === 'SSD1306_128_64' ? 'selected' : ''; ?>>SSD1306 128x64 LCD OLED 0.96inch 4pin</option>
<option value="SSD1306_128_32" <?php echo $Config['display_screen']['lcd_i2c']['screen_type'] === 'SSD1306_128_32' ? 'selected' : ''; ?>>SSD1306 128x32 LCD OLED 0.91inch 4pin</option>
<option value="SSD1306_96_16" <?php echo $Config['display_screen']['lcd_i2c']['screen_type'] === 'SSD1306_96_16' ? 'selected' : ''; ?>>SSD1306 96x16 LCD OLED 0.691inch 4pin</option>
</select>

</div>
</div>
</div>

<div class="row mb-3">
<label for="lcd_i2c_font_size" class="col-sm-3 col-form-label">Cỡ Chữ:</label>
<div class="col-sm-9">
<div class="input-group">
<input class="form-control border-success" type="number" min="5" max="25" step="1" name="lcd_i2c_font_size" id="lcd_i2c_font_size" placeholder="<?php echo $Config['display_screen']['lcd_i2c']['font_size']; ?>" value="<?php echo $Config['display_screen']['lcd_i2c']['font_size']; ?>">
</div>
</div>
</div>
<div class="row mb-3">
<label for="lcd_i2c_font_ttf" class="col-sm-3 col-form-label">Phông Chữ:</label>
<div class="col-sm-9">
<div class="input-group">
<?php
// so sánh file đánh dấu checked
$LCD_I2C_Font_TTF = $Config['display_screen']['lcd_i2c']['font_ttf'];

// Mở thư mục
if ($handle_font = opendir($VBot_Offline.'resource/screen_disp/font')) {
    // Khởi tạo mảng lưu trữ các tệp âm thanh
    $font_file = [];

    // Đọc các tệp trong thư mục
    while (false !== ($entry_font = readdir($handle_font))) {
        // Lấy phần mở rộng của tệp
        $file_parts = pathinfo($entry_font);
        $extension_font = isset($file_parts['extension']) ? strtolower($file_parts['extension']) : '';

        // Kiểm tra xem tệp có phải là tệp âm thanh hợp lệ không
        if (in_array($extension_font, ['ttf', 'otf'])) {
            $font_file[] = $entry_font;
        }
    }
    // Đóng thư mục
    closedir($handle_font);
    // Hiển thị các tệp âm thanh dưới dạng thẻ <select>
    echo '<select name="lcd_i2c_font_ttf" id="lcd_i2c_font_ttf" class="form-select border-success">';
    foreach ($font_file as $file_font) {
		 $selected_font = ($LCD_I2C_Font_TTF === 'resource/screen_disp/font/'.$file_font) ? ' selected' : ''; 
	echo '<option value="' . htmlspecialchars('resource/screen_disp/font/'.$file_font) . '"' . $selected_font . '>' . htmlspecialchars($file_font) . '</option>';
    }
    echo '</select>';
} else {
    //echo 'Không thể mở thư mục welcome';
	echo "<script>showMessagePHP('Không thể mở thư mục resource/screen_disp/font');</script>";
}
?>
</div>
</div>
</div>
</div>
</div>

<div class="card">
<div class="card-body">
<h5 class="card-title">Cấu Hình Màn SPI:</h5>

Chưa DEV


</div>
</div>

</div>
</div>
</div>





<div class="card accordion" id="accordion_button_Sound_System">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_Sound_System" aria-expanded="false" aria-controls="collapse_button_Sound_System">
Âm Thanh Hệ Thống:</h5>
<div id="collapse_button_Sound_System" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_Sound_System" style="">

<div class="card">
<div class="card-body">
<h5 class="card-title">Âm Thanh Khi Khởi Động <i class="bi bi-question-circle-fill" onclick="show_message('Âm thanh thông báo khi chương trình khởi chạy thành công')"></i> :</h5>

				                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt âm thanh thông báo khi chương trình khởi động')"></i> :</label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="sound_welcome_active" id="sound_welcome_active" <?php echo $Config['smart_config']['smart_wakeup']['sound']['welcome']['active'] ? 'checked' : ''; ?>>

                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="sound_welcome_file_path" class="col-sm-3 col-form-label">File âm thanh:</label>
                  <div class="col-sm-9">
				     <div class="input-group">
				  
				  
<?php
// so sánh file đánh dấu checked
$Audio_welcome_file = $Config['smart_config']['smart_wakeup']['sound']['welcome']['welcome_file'];

// Mở thư mục
if ($handle = opendir($VBot_Offline.'resource/sound/welcome')) {
    // Khởi tạo mảng lưu trữ các tệp âm thanh
    $audio_files = [];

    // Đọc các tệp trong thư mục
    while (false !== ($entry = readdir($handle))) {
        // Lấy phần mở rộng của tệp
        $file_parts = pathinfo($entry);
        $extension = isset($file_parts['extension']) ? strtolower($file_parts['extension']) : '';

        // Kiểm tra xem tệp có phải là tệp âm thanh hợp lệ không
        if (in_array($extension, $Allowed_Extensions_Audio)) {
            $audio_files[] = $entry;
        }
    }
    // Đóng thư mục
    closedir($handle);
    // Hiển thị các tệp âm thanh dưới dạng thẻ <select>
    echo '<select name="sound_welcome_file_path" id="sound_welcome_file_path" class="form-select border-success">';
    foreach ($audio_files as $file) {
		 $selected = ($Audio_welcome_file === 'resource/sound/welcome/'.$file) ? ' selected' : ''; 
	echo '<option value="' . htmlspecialchars('resource/sound/welcome/'.$file) . '"' . $selected . '>' . htmlspecialchars($file) . '</option>';
    }
    echo '</select>';
} else {
    //echo 'Không thể mở thư mục welcome';
	echo "<script>showMessagePHP('Không thể mở thư mục welcome');</script>";
}
?>
<button class="btn btn-success border-success"  id="play_Audio_Welcome" type="button"><i class="bi bi-play-circle"></i></button>
				  </div>
				  </div>
                </div>
				
				
<div class="row mb-3">
    <label class="col-sm-3 col-form-label">Tải lên file âm thanh khởi động:</label>
    <div class="col-sm-9">
        <div class="input-group">
            <input class="form-control border-success" type="file" id="upload_Sound_Welcome" multiple> <!-- Thêm thuộc tính multiple -->
			<button class="btn btn-success border-success" type="button" onclick="upload_File('upload_Sound_Welcome')">Tải Lên</button>
			<button type="button" name="list_music_startup" id="list_music_startup" class="btn btn-warning border-success" title="Hiển thị danh sách bài hát trên hệ thống Local" onclick="list_audio_show_path('scan_Audio_Startup')">Danh Sách File Âm Thanh</button>
        </div>
		
    </div>
</div>
<div class="row mb-3">
    <table id="show_mp3_sound_welcome" class="table table-bordered border-primary">
        <thead>
	
        </thead>
        <tbody>
        </tbody>
    </table>
                </div>
            </div>
          </div>
		  
		  
		  
		  

                <div class="card accordion" id="accordion_button_setting">
               <div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting" aria-expanded="false" aria-controls="collapse_button_setting">
                 Âm Thanh Khác/Mặc Định:</h5>
                  <div id="collapse_button_setting" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting" style="">
<?php
foreach ($Config['smart_config']['smart_wakeup']['sound']['default'] as $key => $value) {
    echo "
    <div class='row mb-3'>
        <label for='sound_{$key}' class='col-sm-3 col-form-label'>{$key}:</label>
        <div class='col-sm-9'>
            <div class='input-group'>
                <input readonly class='form-control border-danger' type='text' name='sound_{$key}_file_path' id='sound_{$key}_file_path' placeholder='{$value}' value='{$value}'>
                <button class='btn btn-success border-danger' onclick=\"playAudio('{$VBot_Offline}{$value}')\" type='button'><i class='bi bi-play-circle'></i></button>
            </div>
        </div>
    </div>";
}
?>
</div>
</div>
</div>

</div>
</div>
</div>



<div class="card accordion" id="accordion_button_media_player">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player" aria-expanded="false" aria-controls="collapse_button_media_player">
Cấu Hình Media Player:</h5>
<div id="collapse_button_media_player" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_media_player" style="">


	
<div class="card">
<div class="card-body">
<h5 class="card-title">Kích hoạt Media Player <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt Để kích hoạt sử dụng trình phát nhạc Media Player<br/>Khi được tắt sẽ không ra lệnh phát được Bài Hát, PodCast, Radio')"></i> :</h5>

<div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt: </label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="media_player_active" id="media_player_active" <?php echo $Config['media_player']['active'] ? 'checked' : ''; ?>>
                    
                    </div>
                  </div>
</div>

<div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Cho Phép Đánh Thức Khi Đang Phát Media player: </label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="wake_up_in_media_player" id="wake_up_in_media_player" <?php echo $Config['media_player']['wake_up_in_media_player'] ? 'checked' : ''; ?>>
                    
                    </div>
                  </div>
</div>

</div>
</div>


<div class="card">
<div class="card-body">
<h5 class="card-title">PlayList (Danh Sách Phát) <i class="bi bi-question-circle-fill"></i> :</h5>

<div class="row mb-3">
    <label for="newspaper_play_mode" class="col-sm-3 col-form-label">Nguồn Báo, Tin Tức:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="newspaper_play_mode" id="newspaper_play_mode">
            <option value="">-- Chọn Chế Độ Phát --</option>
            <option value="random" <?php if ($Config['media_player']['play_list']['newspaper_play_mode'] === "random") echo "selected"; ?>>random (Ngẫu nhiên)</option>
            <option value="sequential" <?php if ($Config['media_player']['play_list']['newspaper_play_mode'] === "sequential") echo "selected"; ?>>sequential (Tuần tự)</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <label for="music_play_mode" class="col-sm-3 col-form-label">Nguồn Âm Nhạc:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="music_play_mode" id="music_play_mode">
            <option value="">-- Chọn Chế Độ Phát --</option>
            <option value="random" <?php if ($Config['media_player']['play_list']['music_play_mode'] === "random") echo "selected"; ?>>random (Ngẫu nhiên)</option>
            <option value="sequential" <?php if ($Config['media_player']['play_list']['music_play_mode'] === "sequential") echo "selected"; ?>>sequential (Tuần tự)</option>
        </select>
    </div>
</div>
</div>
</div>

<div class="card">
<div class="card-body">
<h5 class="card-title">Đồng bộ trạng thái Media với Web UI <i class="bi bi-question-circle-fill" onclick="show_message('Chế độ đồng bộ này sẽ sử dụng giao tiếp qua API, nếu bạn tắt Kích Hoạt APi thì sẽ không đồng bộ được nhé')"></i> :</h5>


<div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt tự động đồng bộ khi truy cập vào Web UI')"></i> :</label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="media_sync_ui" id="media_sync_ui" <?php echo $Config['media_player']['media_sync_ui']['active'] ? 'checked' : ''; ?>>
                    
                    </div>
                  </div>
</div>

<div class="row mb-3">
<label for="media_sync_ui_delay_time" class="col-sm-3 col-form-label">Thời gian trễ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian mỗi lần đồng bộ (thường sẽ là 1, mỗi 1 giây sẽ tự động đồng bộ 1 lần)')"></i> : </label>
<div class="col-sm-9">
<input required class="form-control border-success" type="number" min="1" max="5" step="1" name="media_sync_ui_delay_time" id="media_sync_ui_delay_time" placeholder="<?php echo $Config['media_player']['media_sync_ui']['delay_time']; ?>" value="<?php echo $Config['media_player']['media_sync_ui']['delay_time']; ?>">
<div class="invalid-feedback">Cần nhập ngưỡng kết quả tối thiểu khi tìm kiếm bài hát</div>
</div>
</div>

            </div>
          </div>


	
<div class="card">
<div class="card-body">
<h5 class="card-title">Ưu tiên nguồn phát/tìm kiếm Media <i class="bi bi-question-circle-fill" onclick="show_message('Ưu tiên nguồn tìm kiếm bài hát khi Bot xử lý dữ liệu. (xử lý lần lượt theo thứ tự khi nguồn trước đó không có kết quả)')"></i> :</h5>
<?php
	//Get Ưu tiên Nguồn Phát
	$music_source_priority = $Config['media_player']['prioritize_music_source'];
?>
<div class="row mb-3">
    <label for="music_source_priority1" class="col-sm-3 col-form-label">Top 1:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="music_source_priority1" id="music_source_priority1">
            <option value="">-- Chọn Nguồn Phát --</option>
            <option value="music_local" <?php if ($music_source_priority[0] === "music_local") echo "selected"; ?>>Music Local</option>
            <option value="zing_mp3" <?php if ($music_source_priority[0] === "zing_mp3") echo "selected"; ?>>ZingMP3</option>
            <option value="youtube" <?php if ($music_source_priority[0] === "youtube") echo "selected"; ?>>Youtube</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <label for="music_source_priority2" class="col-sm-3 col-form-label">Top 2:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="music_source_priority2" id="music_source_priority2">
            <option value="">-- Chọn Nguồn Phát --</option>
            <option value="music_local" <?php if ($music_source_priority[1] === "music_local") echo "selected"; ?>>Music Local</option>
            <option value="zing_mp3" <?php if ($music_source_priority[1] === "zing_mp3") echo "selected"; ?>>ZingMP3</option>
            <option value="youtube" <?php if ($music_source_priority[1] === "youtube") echo "selected"; ?>>Youtube</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <label for="music_source_priority3" class="col-sm-3 col-form-label">Top 3:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="music_source_priority3" id="music_source_priority3">
            <option value="">-- Chọn Nguồn Phát --</option>
            <option value="music_local" <?php if ($music_source_priority[2] === "music_local") echo "selected"; ?>>Music Local</option>
            <option value="zing_mp3" <?php if ($music_source_priority[2] === "zing_mp3") echo "selected"; ?>>ZingMP3</option>
            <option value="youtube" <?php if ($music_source_priority[2] === "youtube") echo "selected"; ?>>Youtube</option>
        </select>
    </div>
</div>
  

            </div>
          </div>





</div>
</div>
</div>







<div class="card accordion" id="accordion_button_media_player_source">
		<div class="card-body">
			  
			  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player_source" aria-expanded="false" aria-controls="collapse_button_media_player_source">
Nguồn Phát Media Player: Nhạc, Radio, PodCast, Đọc Báo Tin tức:</h5>
				 
				 <div id="collapse_button_media_player_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_media_player_source">
	
<div class="card">
<div class="card-body">
<h5 class="card-title">Zing MP3:</h5>
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc Zing MP3')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="zing_mp3_active" id="zing_mp3_active" <?php echo $Config['media_player']['zing_mp3']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>
 </div>
</div>

<div class="card">
<div class="card-body">
<h5 class="card-title">Youtube:</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc Youtube')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="youtube_active" id="youtube_active" <?php echo $Config['media_player']['youtube']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="youtube_google_apis_key" class="col-sm-3 col-form-label">Youtube Google Apis Key:</label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input readonly class="form-control border-danger" type="text" name="youtube_google_apis_key" id="youtube_google_apis_key" placeholder="<?php echo $Config['media_player']['youtube']['google_apis_key']; ?>" value="<?php echo $Config['media_player']['youtube']['google_apis_key']; ?>">
<button class="btn btn-success border-success" type="button">Kiểm Tra</button>
</div>
</div>
</div>
 </div>
</div>

<div class="card">
<div class="card-body">
<h5 class="card-title">Music Local:</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc Local')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="music_local_active" id="music_local_active" <?php echo $Config['media_player']['music_local']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="music_local_path" class="col-sm-3 col-form-label">Đường dẫn thư mục:</label>
<div class="col-sm-9">
<input readonly class="form-control border-danger" type="text" name="music_local_path" id="music_local_path" placeholder="<?php echo $Config['media_player']['music_local']['path']; ?>" value="<?php echo $Config['media_player']['music_local']['path']; ?>">
</div>
</div>
<div class="row mb-3">
<label for="music_local_minimum_threshold" class="col-sm-3 col-form-label">Ngưỡng kết quả tối thiểu:</label>
<div class="col-sm-9">
<input required class="form-control border-success" type="number" min="0.4" max="0.9" step="0.1" name="music_local_minimum_threshold" id="music_local_minimum_threshold" placeholder="<?php echo $Config['media_player']['music_local']['minimum_threshold']; ?>" value="<?php echo $Config['media_player']['music_local']['minimum_threshold']; ?>">
<div class="invalid-feedback">Cần nhập ngưỡng kết quả tối thiểu khi tìm kiếm bài hát</div>
</div>
</div>

<?php
    // Get Định dạng được phép từ cấu hình
    // Chuyển mảng thành chuỗi, mỗi phần tử cách nhau bởi dấu phẩy
    $allowed_formats_str = implode(", ", $Config['media_player']['music_local']['allowed_formats']);
?>
<div class="row mb-3">
    <label for="music_local_allowed_formats" class="col-sm-3 col-form-label" title="Định dạng âm thanh cho phép Bot tìm kiếm">Định dạng âm thanh được phép: </label>
    <div class="col-sm-9">
        <input required type="text" class="form-control border-success" name="music_local_allowed_formats" id="music_local_allowed_formats" value="<?php echo htmlspecialchars($allowed_formats_str); ?>">
<div class="invalid-feedback">Cần nhập các dạng đuôi tệp âm thanh để cho phép tìm kiếm</div>
	</div>
</div>

<div class="row mb-3">
    <label class="col-sm-3 col-form-label">Tải lên bài hát:</label>
    <div class="col-sm-9">
        <div class="input-group">
            <input class="form-control border-success" type="file" id="upload_Music_Local" multiple> <!-- Thêm thuộc tính multiple -->
            <button class="btn btn-success border-success" type="button" onclick="upload_File('upload_Music_Local')">Tải Lên</button>
<button type="button" name="list_music_local" id="list_music_local" class="btn btn-warning border-success" title="Hiển thị danh sách bài hát trên hệ thống Local" onclick="list_audio_show_path('scan_Music_Local')">Danh Sách Bài Hát</button>

        </div>
    </div>
</div>
<div class="row mb-3">
    <table id="show_mp3_music_local" class="table table-bordered border-primary">
        <thead>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
</div>
 </div>
<div class="card">
<div class="card-body">
<h5 class="card-title">Đài, Radio:</h5>
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng nguồn phát radio')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="radio_active" id="radio_active" <?php echo $Config['media_player']['radio']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<?php
    // Get Dữ liệu radio từ cấu hình
    $radio_data = $Config['media_player']['radio_data'];
?>
    <table class="table table-bordered border-primary"  id="radio-table">
        <thead>
            <tr>
                <th scope="col"><center><font color="red">Tên Đài</font></center></th>
                <th scope="col"><center><font color="red">Link Đài</font></center></th>
                <th scope="col"><center><font color="red">Hành Động</font></center></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($radio_data as $index => $radio) { ?>
                 <tr id="radio-row-<?php echo $index; ?>">
                    <td>
                        <input type="text" class="form-control border-success" name="radio_name_<?php echo $index; ?>" id="radio_name_<?php echo $index; ?>" value="<?php echo htmlspecialchars($radio['name']); ?>">
                    </td>
                    <td>
                        <input type="text" class="form-control border-success" name="radio_link_<?php echo $index; ?>" id="radio_link_<?php echo $index; ?>" value="<?php echo htmlspecialchars($radio['link']); ?>">
                    </td>
<td><center>
<button type="button" class="btn btn-danger" onclick="delete_Dai_bao_Radio('<?php echo $index; ?>', '<?php echo htmlspecialchars($radio['name']); ?>')">
    <i class="bi bi-trash" type="button" title="Xóa đài: <?php echo htmlspecialchars($radio['name']); ?>"></i>
</button>
</center></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
	 <center> <button type="button" class="btn btn-success rounded-pill" id="add-radio" onclick="addRadio()">Thêm Đài Mới</button></center>
	 
</div>
</div>

 
<div class="card">
<div class="card-body">
<h5 class="card-title">
    Đọc Báo, Tin Tức 
    <i class="bi bi-question-circle-fill" 
       onclick="show_message('Ví dụ Các link/url báo được hỗ trợ:<br/><br>\
 - https://podcast.tuoitre.vn<br>\
 - https://thanhnien.vn/thoi-su.htm<br>\
 - https://vnexpress.net/podcast<br/>\
 - https://vnexpress.net/podcast/vnexpress-hom-nay<br/>\
 - https://vietnamnet.vn/podcast/ban-tin-thoi-su<br/>\
 - https://baomoi.com/audio/thoi-su-3.epi<br/>\
 - https://tienphong.vn/podcast/<br/>\
')">
    </i> 
    :
</h5>


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng nguồn phát radio')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="news_paper_active" id="news_paper_active" <?php echo $Config['media_player']['news_paper']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>




<?php
    // Get Dữ liệu newspaper từ cấu hình
    $newspaper_data = $Config['media_player']['news_paper_data'];
?>
    <table class="table table-bordered border-primary"  id="newspaper-table">
        <thead>
            <tr>
                <th scope="col"><center><font color="red">Tên Báo</font></center></th>
                <th scope="col"><center><font color="red">Link Báo</font></center></th>
                <th scope="col"><center><font color="red">Hành Động</font></center></th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($newspaper_data as $index_newspaper => $newspaper) { ?>
<tr id="newspaper-row-<?php echo $index_newspaper; ?>">
<td>
<input type="text" class="form-control border-success" name="newspaper_name_<?php echo $index_newspaper; ?>" id="newspaper_name_<?php echo $index_newspaper; ?>" value="<?php echo htmlspecialchars($newspaper['name']); ?>">
</td>
<td>
<input type="text" class="form-control border-success" name="newspaper_link_<?php echo $index_newspaper; ?>" id="newspaper_link_<?php echo $index_newspaper; ?>" value="<?php echo htmlspecialchars($newspaper['link']); ?>">
</td>
<td><center>
<button type="button" class="btn btn-danger" onclick="delete_NewsPaper('<?php echo $index_newspaper; ?>', '<?php echo htmlspecialchars($newspaper['name']); ?>')">
    <i class="bi bi-trash" type="button" title="Xóa Báo, Tin Tức: <?php echo htmlspecialchars($newspaper['name']); ?>"></i>
</button>
</center></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
	 <center> <button type="button" class="btn btn-success rounded-pill" id="add-newspaper" onclick="addNewsPaper()">Thêm Báo Mới</button></center>


</div>
</div>



                </div>
                </div>
                </div>




				


<div class="card accordion" id="accordion_button_virtual_assistant">
		<div class="card-body">
			  
			  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_virtual_assistant" aria-expanded="false" aria-controls="collapse_button_virtual_assistant">
                 Trợ Lý Ảo/Assistant:</h5>
				 
				 <div id="collapse_button_virtual_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_virtual_assistant" style="">
	
		
<div class="card">
<div class="card-body">
<h5 class="card-title">Ưu tiên trợ lý ảo:</h5>
<?php
	//Get Ưu tiên Nguồn Phát
	$virtual_assistant_priority = $Config['virtual_assistant']['prioritize_virtual_assistants'];
?>
<div class="row mb-3">
    <label for="virtual_assistant_priority1" class="col-sm-3 col-form-label">Top 1:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="virtual_assistant_priority1" id="virtual_assistant_priority1">
            <option value="">-- Chọn Trợ Lý --</option>
            <option value="default_assistant" <?php if ($virtual_assistant_priority[0] === "default_assistant") echo "selected"; ?>>Default Assistant</option>
            <option value="google_gemini" <?php if ($virtual_assistant_priority[0] === "google_gemini") echo "selected"; ?>>Google Gemini</option>
            <option value="chat_gpt" <?php if ($virtual_assistant_priority[0] === "chat_gpt") echo "selected"; ?>>Chat GPT</option>
			<option value="zalo_assistant" <?php if ($virtual_assistant_priority[0] === "zalo_assistant") echo "selected"; ?>>Zalo AI Assistant</option>
			<option value="dify_ai" <?php if ($virtual_assistant_priority[0] === "dify_ai") echo "selected"; ?>>Dify AI Assistant</option>
			<option value="customize_developer_assistant" <?php if ($virtual_assistant_priority[0] === "customize_developer_assistant") echo "selected"; ?>>DEV Custom Assistant (Người Dùng Tự Code)</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <label for="virtual_assistant_priority2" class="col-sm-3 col-form-label">Top 2:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="virtual_assistant_priority2" id="virtual_assistant_priority2">
            <option value="">-- Chọn Trợ Lý --</option>
            <option value="default_assistant" <?php if ($virtual_assistant_priority[1] === "default_assistant") echo "selected"; ?>>Default Assistant</option>
            <option value="google_gemini" <?php if ($virtual_assistant_priority[1] === "google_gemini") echo "selected"; ?>>Google Gemini</option>
            <option value="chat_gpt" <?php if ($virtual_assistant_priority[1] === "chat_gpt") echo "selected"; ?>>Chat GPT</option>
			<option value="zalo_assistant" <?php if ($virtual_assistant_priority[1] === "zalo_assistant") echo "selected"; ?>>Zalo AI Assistant</option>
			<option value="dify_ai" <?php if ($virtual_assistant_priority[1] === "dify_ai") echo "selected"; ?>>Dify AI Assistant</option>
			<option value="customize_developer_assistant" <?php if ($virtual_assistant_priority[1] === "customize_developer_assistant") echo "selected"; ?>>DEV Custom Assistant (Người Dùng Tự Code)</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <label for="virtual_assistant_priority3" class="col-sm-3 col-form-label">Top 3:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="virtual_assistant_priority3" id="virtual_assistant_priority3">
            <option value="">-- Chọn Trợ Lý --</option>
            <option value="default_assistant" <?php if ($virtual_assistant_priority[2] === "default_assistant") echo "selected"; ?>>Default Assistant</option>
            <option value="google_gemini" <?php if ($virtual_assistant_priority[2] === "google_gemini") echo "selected"; ?>>Google Gemini</option>
            <option value="chat_gpt" <?php if ($virtual_assistant_priority[2] === "chat_gpt") echo "selected"; ?>>Chat GPT</option>
			<option value="zalo_assistant" <?php if ($virtual_assistant_priority[2] === "zalo_assistant") echo "selected"; ?>>Zalo AI Assistant</option>
			<option value="dify_ai" <?php if ($virtual_assistant_priority[2] === "dify_ai") echo "selected"; ?>>Dify AI Assistant</option>
			<option value="customize_developer_assistant" <?php if ($virtual_assistant_priority[2] === "customize_developer_assistant") echo "selected"; ?>>DEV Custom Assistant (Người Dùng Tự Code)</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <label for="virtual_assistant_priority4" class="col-sm-3 col-form-label">Top 4:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="virtual_assistant_priority4" id="virtual_assistant_priority4">
            <option value="">-- Chọn Trợ Lý --</option>
            <option value="default_assistant" <?php if ($virtual_assistant_priority[3] === "default_assistant") echo "selected"; ?>>Default Assistant</option>
            <option value="google_gemini" <?php if ($virtual_assistant_priority[3] === "google_gemini") echo "selected"; ?>>Google Gemini</option>
            <option value="chat_gpt" <?php if ($virtual_assistant_priority[3] === "chat_gpt") echo "selected"; ?>>Chat GPT</option>
            <option value="zalo_assistant" <?php if ($virtual_assistant_priority[3] === "zalo_assistant") echo "selected"; ?>>Zalo AI Assistant</option>
			<option value="dify_ai" <?php if ($virtual_assistant_priority[3] === "dify_ai") echo "selected"; ?>>Dify AI Assistant</option>
			<option value="customize_developer_assistant" <?php if ($virtual_assistant_priority[3] === "customize_developer_assistant") echo "selected"; ?>>DEV Custom Assistant (Người Dùng Tự Code)</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <label for="virtual_assistant_priority5" class="col-sm-3 col-form-label">Top 5:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="virtual_assistant_priority5" id="virtual_assistant_priority5">
            <option value="">-- Chọn Trợ Lý --</option>
            <option value="default_assistant" <?php if ($virtual_assistant_priority[4] === "default_assistant") echo "selected"; ?>>Default Assistant</option>
            <option value="google_gemini" <?php if ($virtual_assistant_priority[4] === "google_gemini") echo "selected"; ?>>Google Gemini</option>
            <option value="chat_gpt" <?php if ($virtual_assistant_priority[4] === "chat_gpt") echo "selected"; ?>>Chat GPT</option>
            <option value="zalo_assistant" <?php if ($virtual_assistant_priority[4] === "zalo_assistant") echo "selected"; ?>>Zalo AI Assistant</option>
			<option value="dify_ai" <?php if ($virtual_assistant_priority[4] === "dify_ai") echo "selected"; ?>>Dify AI Assistant</option>
			<option value="customize_developer_assistant" <?php if ($virtual_assistant_priority[4] === "customize_developer_assistant") echo "selected"; ?>>DEV Custom Assistant (Người Dùng Tự Code)</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <label for="virtual_assistant_priority6" class="col-sm-3 col-form-label">Top 6:</label>
    <div class="col-sm-9">
        <select class="form-select border-success" name="virtual_assistant_priority6" id="virtual_assistant_priority6">
            <option value="">-- Chọn Trợ Lý --</option>
            <option value="default_assistant" <?php if ($virtual_assistant_priority[5] === "default_assistant") echo "selected"; ?>>Default Assistant</option>
            <option value="google_gemini" <?php if ($virtual_assistant_priority[5] === "google_gemini") echo "selected"; ?>>Google Gemini</option>
            <option value="chat_gpt" <?php if ($virtual_assistant_priority[5] === "chat_gpt") echo "selected"; ?>>Chat GPT</option>
            <option value="zalo_assistant" <?php if ($virtual_assistant_priority[5] === "zalo_assistant") echo "selected"; ?>>Zalo AI Assistant</option>
			<option value="dify_ai" <?php if ($virtual_assistant_priority[5] === "dify_ai") echo "selected"; ?>>Dify AI Assistant</option>
			<option value="customize_developer_assistant" <?php if ($virtual_assistant_priority[5] === "customize_developer_assistant") echo "selected"; ?>>DEV Custom Assistant (Người Dùng Tự Code)</option>
        </select>
    </div>
</div>

            </div>
          </div>
		  

<div class="card">
<div class="card-body">
<h5 class="card-title">Default Assistant <i class="bi bi-question-circle-fill" onclick="show_message('Trợ lý ảo mang tên Default Assistant')"></i> :</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Default Assistant')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="default_assistant_active" id="default_assistant_active" <?php echo $Config['virtual_assistant']['default_assistant']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="default_assistant_time_out" class="col-sm-3 col-form-label">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa (Giây)')"></i> :</label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input  class="form-control border-success" type="number" min="5" step="1" max="90" name="default_assistant_time_out" id="default_assistant_time_out" placeholder="<?php echo $Config['virtual_assistant']['default_assistant']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['default_assistant']['time_out']; ?>">
</div>
</div>
</div>


<div class="card-body">
<h5 class="card-title">Chuyển đổi thêm kết quả từ âm thanh thành văn bản (text) <i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi này chỉ áp dụng với trợ lý ảo Default Assistant')"></i> :</h5>


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Áp dụng với Chatbot <i class="bi bi-question-circle-fill" onclick="show_message('khi được tắt dữ liệu trả về sẽ là file âm thanh, khi được bật sẽ trả về dữ liệu là file âm thanh và văn bản (text)<br/>Cân nhắc khi được bật thời gian xử lý sẽ lâu hơn')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="default_assistant_convert_audio_to_text_used_for_chatbox" id="default_assistant_convert_audio_to_text_used_for_chatbox" <?php echo $Config['virtual_assistant']['default_assistant']['convert_audio_to_text']['used_for_chatbox'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Áp dụng với Hệ thống, Console, Logs, Logs API <i class="bi bi-question-circle-fill" onclick="show_message('khi được tắt dữ liệu trả về sẽ là file âm thanh, khi được bật sẽ trả về dữ liệu là file âm thanh và văn bản (text)<br/>Cân nhắc khi được bật thời gian xử lý sẽ lâu hơn')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="default_assistant_convert_audio_to_text_used_for_display_and_logs" id="default_assistant_convert_audio_to_text_used_for_display_and_logs" <?php echo $Config['virtual_assistant']['default_assistant']['convert_audio_to_text']['used_for_display_and_logs'] ? 'checked' : ''; ?>>
</div>
</div>
</div>
</div>
</div>
</div>

<div class="card">
<div class="card-body">
<h5 class="card-title">Google Gemini <i class="bi bi-question-circle-fill" onclick="show_message('Lấy Key/Api: <a href=\'https://aistudio.google.com/app/apikey\' target=\'_bank\'>https://aistudio.google.com/app/apikey</a> ')"></i> :</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Gemini')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="google_gemini_active" id="google_gemini_active" <?php echo $Config['virtual_assistant']['google_gemini']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="google_gemini_key" class="col-sm-3 col-form-label">Api Keys:</label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input  class="form-control border-success" type="text" name="google_gemini_key" id="google_gemini_key" placeholder="<?php echo $Config['virtual_assistant']['google_gemini']['api_key']; ?>" value="<?php echo $Config['virtual_assistant']['google_gemini']['api_key']; ?>">
<button class="btn btn-success border-success" type="button" onclick="test_key_Gemini('xin chào')">Kiểm Tra</button>
</div>
</div>
</div>

<div class="row mb-3">
<label for="google_gemini_time_out" class="col-sm-3 col-form-label">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa (Giây)')"></i> :</label>
<div class="col-sm-9">
<input  class="form-control border-success" type="number" min="5" step="1" max="30" name="google_gemini_time_out" id="google_gemini_time_out" placeholder="<?php echo $Config['virtual_assistant']['google_gemini']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['google_gemini']['time_out']; ?>">
</div>
</div>
</div>
</div>

<div class="card">
<div class="card-body">
<h5 class="card-title">Chat GPT:</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Chat GPT')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="chat_gpt_active" id="chat_gpt_active" <?php echo $Config['virtual_assistant']['chat_gpt']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="chat_gpt_key" class="col-sm-3 col-form-label">Api Keys:</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input  class="form-control border-success" type="text" name="chat_gpt_key" id="chat_gpt_key" placeholder="<?php echo $Config['virtual_assistant']['chat_gpt']['key_chat_gpt']; ?>" value="<?php echo $Config['virtual_assistant']['chat_gpt']['key_chat_gpt']; ?>">
<button class="btn btn-success border-success" type="button"  onclick="test_key_ChatGPT('Chào bạn, bạn tên là gì')">Kiểm Tra</button>
</div>
</div>
</div>

<div class="row mb-3">
<label for="chat_gpt_model" class="col-sm-3 col-form-label">Model:</label>
<div class="col-sm-9">
<select class="form-select border-success" name="chat_gpt_model" id="chat_gpt_model">
<option value="">-- Chọn Model --</option>
<option value="gpt-3.5-turbo" <?php if ($Config['virtual_assistant']['chat_gpt']['model'] === "gpt-3.5-turbo") echo "selected"; ?>>GPT-3.5 Turbo (Khuyến Nghị)</option>
<option value="gpt-4" <?php if ($Config['virtual_assistant']['chat_gpt']['model'] === "gpt-4") echo "selected"; ?>>GPT-4</option>
<option value="gpt-4o" <?php if ($Config['virtual_assistant']['chat_gpt']['model'] === "gpt-4o") echo "selected"; ?>>GPT-4o</option>
<option value="gpt-4o-mini" <?php if ($Config['virtual_assistant']['chat_gpt']['model'] === "gpt-4o-mini") echo "selected"; ?>>GPT-4o mini</option>
<option value="gpt-4-turbo" <?php if ($Config['virtual_assistant']['chat_gpt']['model'] === "gpt-4-turbo") echo "selected"; ?>>GPT-4 Turbo</option>
</select>
</div>
</div>

<div class="row mb-3">
<label for="chat_gpt_role_system_content" class="col-sm-3 col-form-label">Role System Content <i class="bi bi-question-circle-fill" onclick="show_message('Thiết lập hành vi mong muốn của Chat GPT trong cuộc trò chuyện, gán GPT như 1 trợ lý, người, vật, v..v...! làm cho trải nghiệm người dùng phù hợp với mục đích cụ thể của bạn.')"></i>:</label>
<div class="col-sm-9">
<input  class="form-control border-success" type="text" name="chat_gpt_role_system_content" id="chat_gpt_role_system_content" placeholder="<?php echo $Config['virtual_assistant']['chat_gpt']['role_system_content']; ?>" value="<?php echo $Config['virtual_assistant']['chat_gpt']['role_system_content']; ?>">
</div>
</div>

<div class="row mb-3">
<label for="chat_gpt_url_api" class="col-sm-3 col-form-label">URL API <i class="bi bi-question-circle-fill" onclick="show_message('- Hỗ trợ với URL API và API KEY của bên thứ 3<br/><br/>hoặc URL Mặc Định của ChatGPT và Key của ChatGPT: <b>https://api.openai.com/v1/chat/completions</b>')"></i>:</label>
<div class="col-sm-9">
<input  class="form-control border-danger" type="text" name="chat_gpt_url_api" id="chat_gpt_url_api" placeholder="https://api.openai.com/v1/chat/completions" value="<?php echo $Config['virtual_assistant']['chat_gpt']['url_api']; ?>">
</div>
</div>


<div class="row mb-3">
<label for="chat_gpt_time_out" class="col-sm-3 col-form-label">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa (Giây)')"></i> :</label>
<div class="col-sm-9">
<input  class="form-control border-success" type="number" min="5" step="1" max="30" name="chat_gpt_time_out" id="chat_gpt_time_out" placeholder="<?php echo $Config['virtual_assistant']['chat_gpt']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['chat_gpt']['time_out']; ?>">
</div>
</div>

</div>
</div>

<div class="card">
<div class="card-body">
<h5 class="card-title">Zalo AI Assistant:</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Zalo Assistant')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="zalo_assistant_active" id="zalo_assistant_active" <?php echo $Config['virtual_assistant']['zalo_assistant']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="zalo_assistant_time_out" class="col-sm-3 col-form-label">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa (Giây)')"></i> :</label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input  class="form-control border-success" type="number" min="5" step="1" max="30" name="zalo_assistant_time_out" id="zalo_assistant_time_out" placeholder="<?php echo $Config['virtual_assistant']['zalo_assistant']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['zalo_assistant']['time_out']; ?>">
</div>
</div>
</div>

<div class="row mb-3">
<label for="zalo_assistant_set_expiration_time" class="col-sm-3 col-form-label">Đặt thời gian hết hạn Token (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Đặt thời gian hết hạn cho token trung bình đặt 1 ngày tham số tính bằng giây: 86400')"></i> :</label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input  class="form-control border-success" type="number" min="21600" max="604800" step="1" name="zalo_assistant_set_expiration_time" id="zalo_assistant_set_expiration_time" placeholder="<?php echo $Config['virtual_assistant']['zalo_assistant']['set_expiration_time']; ?>" value="<?php echo $Config['virtual_assistant']['zalo_assistant']['set_expiration_time']; ?>">
</div>
</div>
</div>

</div>
</div>



<div class="card">
<div class="card-body">
<h5 class="card-title">Difi.ai | <a href="https://cloud.dify.ai" target="_bank">cloud.dify.ai</a>:</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng Dify AI ')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="dify_ai_active" id="dify_ai_active" <?php echo $Config['virtual_assistant']['dify_ai']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Giữ Phiên Chat Session <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để lưu trữ ID phiên cho các lần hỏi đáp tiếp theo<br/><br/>- Phiên sẽ được làm mới mỗi khi chương trình VBot được khởi chạy')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="dify_ai_session_chat" id="dify_ai_session_chat" <?php echo $Config['virtual_assistant']['dify_ai']['session_chat_conversation'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="dify_ai_key" class="col-sm-3 col-form-label">Api Keys:</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input  class="form-control border-success" type="text" name="dify_ai_key" id="dify_ai_key" placeholder="<?php echo $Config['virtual_assistant']['dify_ai']['api_key']; ?>" value="<?php echo $Config['virtual_assistant']['dify_ai']['api_key']; ?>">
<button class="btn btn-success border-success" type="button" onclick="test_key_difyAI()">Kiểm Tra</button>
</div>
</div>
</div>

<div class="row mb-3">
<label for="dify_ai_user_id" class="col-sm-3 col-form-label">User ID <i class="bi bi-question-circle-fill" onclick="show_message('Mã định danh người dùng, được sử dụng để xác định danh tính của người dùng cuối để truy xuất và thống kê. Phải được nhà phát triển xác định duy nhất trong ứng dụng, <br/>- Có thể thay thành bất kỳ tên nào bạn muốn')"></i>:</label>
<div class="col-sm-9">
<input  class="form-control border-danger" type="text" name="dify_ai_user_id" id="dify_ai_user_id" placeholder="<?php echo $Config['virtual_assistant']['dify_ai']['user_id']; ?>" value="<?php echo $Config['virtual_assistant']['dify_ai']['user_id']; ?>">
</div>
</div>

<div class="row mb-3">
<label for="dify_ai_time_out" class="col-sm-3 col-form-label">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa (Giây)')"></i> :</label>
<div class="col-sm-9">
<div class="input-group mb-3">
<input  class="form-control border-success" type="number" min="5" step="1" max="30" name="dify_ai_time_out" id="dify_ai_time_out" placeholder="<?php echo $Config['virtual_assistant']['dify_ai']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['dify_ai']['time_out']; ?>">
</div>
</div>
</div>
</div>
</div>


<div class="card">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_custom_assistant" aria-expanded="false" aria-controls="collapse_button_custom_assistant">
DEV Assistant (Custom Assistant) <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng Custom Assistant, Người dùng tự code trợ lý ảo, tùy biến hoặc sử dụng theo nhu cầu riêng, nếu sử dụng hãy kích hoạt và chọn ưu tiên trợ lý ảo này')"></i>:</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng Custom Assistant, Người dùng tự code trợ lý ảo, tùy biến hoặc sử dụng theo nhu cầu riêng, nếu sử dụng hãy kích hoạt và chọn ưu tiên trợ lý ảo này')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="customize_developer_assistant_active" id="customize_developer_assistant_active" <?php echo $Config['virtual_assistant']['customize_developer_assistant']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>


</div>
</div>



                </div>
                </div>
                </div>


<div class="card accordion" id="accordion_button_collapse_button_developer_customization">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" title="chế độ tùy chỉnh cho các lập trình viên, DEV" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_developer_customization" aria-expanded="false" aria-controls="collapse_button_developer_customization">
DEV Customization (Custom Skill) <i class="bi bi-question-circle-fill" onclick="show_message('Cơ chế hoạt động:<br/>- Chế độ được kích hoạt, khi được đánh thức Wake UP, chương trình sẽ truyền dữ liệu văn bản được chuyển đổi từ Speak to Text vào File Dev_Customization.py để cho các bạn tự lập trình và xử lý dữ liệu, Khi kết thúc xử lý sẽ cần phải có return để trả về true hoặc false')"></i> :</h5>
<div id="collapse_button_developer_customization" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_developer_customization">
             
			 <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Chế độ DEV (Custom Skill) <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chế độ để vào và sử dụng chế độ Custom Skill cho các bạn Dev thoải mái xử lý dữ liệu lập trình và tùy biến')"></i> :</label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="developer_customization_active" id="developer_customization_active" <?php echo $Config['developer_customization']['active'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>

<div class="card">
<div class="card-body">
 <h5 class="card-title">Nếu Custom Skill không thể xử lý:</h5>
			 <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Sử dụng Vbot xử lý <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng Vbot xử lý dữ liệu khi mà Custom Skill không xử lý được')"></i> :</label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="developer_customization_vbot_processing" id="developer_customization_vbot_processing" <?php echo $Config['developer_customization']['if_custom_skill_can_not_handle']['vbot_processing'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
                </div>
                </div>
				
</div>
</div>
</div>


<div class="card accordion" id="accordion_button_schedule_lich">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_schedule_lich" aria-expanded="false" aria-controls="collapse_button_schedule_lich">
Cài Đặt Lời Nhắc, Thông báo (Schedule) <i class="bi bi-question-circle-fill" onclick="show_message('Bạn cần di chuyển tới: <b>Thiết Lập Nâng Cao -> Lên Lịch: Lời Nhắc, Thông Báo (Scheduler)</b> để tiến hành thiết lập thông báo')"></i>:</h5>
<div id="collapse_button_schedule_lich" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_schedule_lich">


			 <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để khởi động Phát lời nhắc, Thông báo khi Vbot được khởi chạy')"></i> :</label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="schedule_active" id="schedule_active" <?php echo $Config['schedule']['active'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>

<div class="row mb-3">
<label for="schedule_data_json_file" class="col-sm-3 col-form-label">Tệp Dữ Liệu Cấu Hình:</label>
<div class="col-sm-9">
<input readonly class="form-control border-danger" type="text" name="schedule_data_json_file" id="schedule_data_json_file" placeholder="<?php echo $Config['schedule']['data_json_file']; ?>" value="<?php echo $Config['schedule']['data_json_file']; ?>">

</div>
</div>

</div>
</div>
</div>



<div class="card accordion" id="accordion_button_sao_luu_cap_nhat">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_sao_luu_cap_nhat" aria-expanded="false" aria-controls="collapse_button_sao_luu_cap_nhat">
Sao Lưu/Cập Nhật:</h5>
<div id="collapse_button_sao_luu_cap_nhat" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_sao_luu_cap_nhat">

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tự Động Kiểm Tra Bản Cập Nhật <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật sẽ tự động kiểm tra cập nhật mới khi truy cập vào giao diện web ui')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="automatically_check_for_updates" id="automatically_check_for_updates" <?php echo $Config['backup_upgrade']['advanced_settings']['automatically_check_for_updates'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Khởi Động Lại VBot: <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, Chương trình sẽ khởi động lại Vbot khi quá trình cập nhật Nâng Cấp Vbot thành công')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="restart_vbot_upgrade" id="restart_vbot_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['restart_vbot'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Thông Báo Âm Thanh: <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, sẽ thông báo bằng âm thanh khi quá trình Cập Nhật, Nâng Cấp Giao Diện Web hoặc chương trình Vbot thành công')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="sound_notification_backup_upgrade" id="sound_notification_backup_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['sound_notification'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tải Lại Giao Diện Web: <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, sẽ tải lại giao diện Web khi Nâng Cấp, Cập Nhật web ui thành công')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="refresh_page_ui_backup_upgrade" id="refresh_page_ui_backup_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['refresh_page_ui'] ? 'checked' : ''; ?>>
</div>
</div>
</div>


<div class="card">
<div class="card-body">
<h5 class="card-title">Sao Lưu Config.json</h5>
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chức năng sao lưu tệp Config.json mỗi khi lưu hoặc thay đổi cấu hình Config.json')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="backup_config_json_active" id="backup_config_json_active" <?php echo $Config['backup_upgrade']['config_json']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tên Thư Mục Sao Lưu <i class="bi bi-question-circle-fill" onclick="show_message('Tên Thư Mục Sao Lưu Tệp Config.json, Nếu thư mục không tồn tại sẽ tự động được tạo mới')"></i> : </label>
<div class="col-sm-9">
<input readonly class="form-control border-danger" type="text" name="backup_path_config_json" id="backup_path_config_json" value="<?php echo $Config['backup_upgrade']['config_json']['backup_path']; ?>">
<div class="invalid-feedback">Cần nhập Tên Thư Mục Sao Lưu Config.json</div>
</div>
</div>


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Giới hạn tệp sao lưu tối đa <i class="bi bi-question-circle-fill" onclick="show_message('Giới hạn tệp sao lưu tối đa trong thư mục Backup_Config, nếu nhiều hơn giới hạn cho phép sẽ tự động xóa file cũ nhất')"></i> : </label>
<div class="col-sm-9">
<div class="form-switch">
<input required class="form-control border-success" type="number" min="1" step="1" max="20" name="limit_backup_files_config_json" id="limit_backup_files_config_json" value="<?php echo $Config['backup_upgrade']['config_json']['limit_backup_files']; ?>">
<div class="invalid-feedback">Nhập giới hạn tệp sao lưu tối đa</div>
</div>
</div>
</div>


</div>
</div>


<div class="card">
<div class="card-body">
<h5 class="card-title">Sao Lưu Custom Home Assistant (Home_Assistant_Custom.json)</h5>
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chức năng sao lưu tệp Home_Assistant_Custom.json mỗi khi lưu hoặc thay đổi cấu hình Home_Assistant_Custom.json')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="custom_home_assistant_active" id="custom_home_assistant_active" <?php echo $Config['backup_upgrade']['custom_home_assistant']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tên Thư Mục Sao Lưu <i class="bi bi-question-circle-fill" onclick="show_message('Tên Thư Mục Sao Lưu Tệp Home_Assistant_Custom.json, Nếu thư mục không tồn tại sẽ tự động được tạo mới')"></i> : </label>
<div class="col-sm-9">
<input readonly class="form-control border-danger" type="text" name="backup_path_custom_home_assistant" id="backup_path_custom_home_assistant" value="<?php echo $Config['backup_upgrade']['custom_home_assistant']['backup_path']; ?>">
<div class="invalid-feedback">Cần nhập Tên Thư Mục Sao Lưu Config.json</div>
</div>
</div>


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Giới hạn tệp sao lưu tối đa <i class="bi bi-question-circle-fill" onclick="show_message('Giới hạn tệp sao lưu tối đa trong thư mục Backup_Custom_HomeAssistant, nếu nhiều hơn giới hạn cho phép sẽ tự động xóa file cũ nhất')"></i> : </label>
<div class="col-sm-9">
<div class="form-switch">
<input required class="form-control border-success" type="number" min="1" step="1" max="20" name="limit_backup_custom_home_assistant" id="limit_backup_custom_home_assistant" value="<?php echo $Config['backup_upgrade']['custom_home_assistant']['limit_backup_files']; ?>">
<div class="invalid-feedback">Nhập giới hạn tệp sao lưu tối đa</div>
</div>
</div>
</div>


</div>
</div>


<div class="card">
<div class="card-body">
<h5 class="card-title">Sao Lưu Cấu Hình Lời Nhắc, Thông Báo (Scheduler)</h5>
<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chức năng sao lưu tệp Data_Schedule.json mỗi khi lưu hoặc thay đổi lưu cấu hình')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="backup_scheduler_active" id="backup_scheduler_active" <?php echo $Config['backup_upgrade']['scheduler']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tên Thư Mục Sao Lưu <i class="bi bi-question-circle-fill" onclick="show_message('Tên Thư Mục Sao Lưu Tệp Data_Schedule.json, Nếu thư mục không tồn tại sẽ tự động được tạo mới')"></i> : </label>
<div class="col-sm-9">
<input readonly class="form-control border-danger" type="text" name="backup_path_scheduler" id="backup_path_scheduler" value="<?php echo $Config['backup_upgrade']['scheduler']['backup_path']; ?>">
<div class="invalid-feedback">Cần nhập Tên Thư Mục Sao Lưu Config.json</div>
</div>
</div>


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Giới hạn tệp sao lưu tối đa <i class="bi bi-question-circle-fill" onclick="show_message('Giới hạn tệp sao lưu tối đa trong thư mục Backup_Scheduler, nếu nhiều hơn giới hạn cho phép sẽ tự động xóa file cũ nhất')"></i> : </label>
<div class="col-sm-9">
<div class="form-switch">
<input required class="form-control border-success" type="number" min="1" step="1" max="20" name="limit_backup_scheduler" id="limit_backup_scheduler" value="<?php echo $Config['backup_upgrade']['scheduler']['limit_backup_files']; ?>">
<div class="invalid-feedback">Nhập giới hạn tệp sao lưu tối đa</div>
</div>
</div>
</div>


</div>
</div>


<div class="card">
<div class="card-body">

<h5 class="card-title">VBot Program<i class="bi bi-question-circle-fill" onclick="show_message('Cấu hình Cài Đặt khi Sao Lưu và Cập Nhật trương trình')"></i> :</h5>
<div class="card accordion" id="accordion_button_cau_hinh_sao_luu_vbot">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cau_hinh_sao_luu_vbot" aria-expanded="false" aria-controls="collapse_button_cau_hinh_sao_luu_vbot">
Sao Lưu VBot:</h5>
<div id="collapse_button_cau_hinh_sao_luu_vbot" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cau_hinh_sao_luu_vbot">

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Đồng bộ lên Google Drive: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu sẽ được tải lên Google Drive')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="backup_vbot_google_drive" id="backup_vbot_google_drive" <?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="backup_upgrade_vbot_backup_path" class="col-sm-3 col-form-label">Đường dẫn tệp sao lưu:</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input readonly class="form-control border-danger" type="text" name="backup_upgrade_vbot_backup_path" id="backup_upgrade_vbot_backup_path" placeholder="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_path']; ?>" value="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_path']; ?>">
</div>
</div>
</div>

<div class="row mb-3">
<label for="backup_upgrade_vbot_limit_backup_files" class="col-sm-3 col-form-label">Tệp sao lưu tối đa <i class="bi bi-question-circle-fill" onclick="show_message('Tối đa số lượng tệp tin sao lưu trên hệ thống')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input  class="form-control border-success" type="number" min="2" step="1" max="10" name="backup_upgrade_vbot_limit_backup_files" id="backup_upgrade_vbot_limit_backup_files" placeholder="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files']; ?>" value="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files']; ?>">
</div>
</div>
</div>

<div class="row mb-3">
<label for="backup_upgrade_vbot_exclude_files_folder" class="col-sm-3 col-form-label">Bỏ qua file, thư mục không sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi thư mục hoặc file sẽ là 1 dòng, nếu là file sẽ cần có đầy đủ đuôi mở rộng của file, ví dụ: <b>123.mp3</b>')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<textarea class="form-control border-success" rows="5" name="backup_upgrade_vbot_exclude_files_folder" id="backup_upgrade_vbot_exclude_files_folder">
<?php
$excludeFilesFolder_Vbot_backup_upgrade = isset($Config['backup_upgrade']['vbot_program']['backup']['exclude_files_folder']) ? $Config['backup_upgrade']['vbot_program']['backup']['exclude_files_folder'] : [];
    if (!empty($excludeFilesFolder_Vbot_backup_upgrade)) {
        foreach ($excludeFilesFolder_Vbot_backup_upgrade as $index_exclude_files_folder => $item_exclude_files_folder) {
            echo htmlspecialchars($item_exclude_files_folder);
            // Không thêm xuống dòng cuối cùng sau phần tử cuối
            if ($index_exclude_files_folder < count($excludeFilesFolder_Vbot_backup_upgrade) - 1) {
                echo "\n";
            }
        }
    }
?>
</textarea>
</div>
</div>
</div>


<div class="row mb-3">
<label for="backup_upgrade_vbot_exclude_file_format" class="col-sm-3 col-form-label">Bỏ qua định dạng tệp không sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi định dạng tệp là 1 dòng, cần có dấu <b>.</b> ở trước định dạng tệp ví dụ: <b>.mp3</b> hoặc <b>.mp4</b>')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<textarea class="form-control border-success" rows="5" name="backup_upgrade_vbot_exclude_file_format" id="backup_upgrade_vbot_exclude_file_format">
<?php
$backup_upgrade_vbot_exclude_file = isset($Config['backup_upgrade']['vbot_program']['backup']['exclude_file_format']) ? $Config['backup_upgrade']['vbot_program']['backup']['exclude_file_format'] : [];
    if (!empty($backup_upgrade_vbot_exclude_file)) {
        foreach ($backup_upgrade_vbot_exclude_file as $index_exclude_file_format => $item_index_exclude_file_format) {
            echo htmlspecialchars($item_index_exclude_file_format);
            // Không thêm xuống dòng cuối cùng sau phần tử cuối
            if ($index_exclude_file_format < count($backup_upgrade_vbot_exclude_file) - 1) {
                echo "\n";
            }
        }
    }
?>
</textarea>
</div>
</div>
</div>
<center>
<button type="button" name="show_all_file_in_directoryyyy" class="btn btn-success rounded-pill" onclick="show_all_file_in_directory('<?php echo $HTML_VBot_Offline . '/' . $Backup_Dir_Save_VBot; ?>', 'show_all_file_folder_Backup_Program')">Danh Sách Tệp Sao Lưu Vbot</button>
<br/><br/>
<div class="limited-height" id="show_all_file_folder_Backup_Program"></div>
</center>
</div>
</div>
</div>




<div class="card accordion" id="accordion_button_cau_hinh_Cap_nhat_Vbot">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cau_hinh_Cap_nhat_Vbot" aria-expanded="false" aria-controls="collapse_button_cau_hinh_Cap_nhat_Vbot">
Cập Nhật VBot:</h5>
<div id="collapse_button_cau_hinh_Cap_nhat_Vbot" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cau_hinh_Cap_nhat_Vbot">

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tạo Bản Sao Lưu Trước Khi Cập Nhật: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu được tạo ra trước khi cập nhật sẽ được tải lên Google Drive')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="make_a_backup_before_updating_vbot" id="make_a_backup_before_updating_vbot" <?php echo $Config['backup_upgrade']['vbot_program']['upgrade']['backup_before_updating'] ? 'checked' : ''; ?>>
</div>
</div>
</div>



<div class="row mb-3">
<label for="vbot_program_upgrade_keep_the_file_folder" class="col-sm-3 col-form-label">Giữ lại Tệp, Thư Mục Không Cập Nhật <i class="bi bi-question-circle-fill" onclick="show_message('Giữ lại tệp hoặc thư mục không cho phép cập nhật, mỗi tệp hoặc thư mục là 1 dòng, nếu là tệp tin thì cần có đầy đủ tên và đuôi của tệp, ví dụ giữ lại tệp: <b>Config.json</b>, giữ lại thư mục: <b>eng</b>')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<textarea class="form-control border-success" rows="5" name="vbot_program_upgrade_keep_the_file_folder" id="vbot_program_upgrade_keep_the_file_folder">
<?php
$excludeFilesFolder_Vbot_upgrade = isset($Config['backup_upgrade']['vbot_program']['upgrade']['keep_file_directory']) ? $Config['backup_upgrade']['vbot_program']['upgrade']['keep_file_directory'] : [];
    if (!empty($excludeFilesFolder_Vbot_upgrade)) {
        foreach ($excludeFilesFolder_Vbot_upgrade as $index_exclude_files_folder_Vbot_UPGRADE => $item_exclude_files_folder_VBot_upgrade) {
            echo htmlspecialchars($item_exclude_files_folder_VBot_upgrade);
            // Không thêm xuống dòng cuối cùng sau phần tử cuối
            if ($index_exclude_files_folder_Vbot_UPGRADE < count($excludeFilesFolder_Vbot_upgrade) - 1) {
                echo "\n";
            }
        }
    }
?>
</textarea>
</div>
</div>
</div>


</div>
</div>
</div>


</div>
</div>



<div class="card">
<div class="card-body">
<h5 class="card-title">Giao diện Web <i class="bi bi-question-circle-fill" onclick="show_message('Cấu hình Cài Đặt khi Sao Lưu và Cập Nhật Giao diện Web')"></i> :</h5>

<div class="card accordion" id="accordion_button_sao_luu_giao_dien">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_sao_luu_giao_dien" aria-expanded="false" aria-controls="collapse_button_sao_luu_giao_dien">
Sao Lưu Giao Diện:</h5>
<div id="collapse_button_sao_luu_giao_dien" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_sao_luu_giao_dien">

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Đồng bộ lên Google Drive: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu sẽ được tải lên Google Drive')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="backup_web_interface_google_drive" id="backup_web_interface_google_drive" <?php echo $Config['backup_upgrade']['web_interface']['backup']['backup_to_cloud']['google_drive'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="backup_web_interface_backup_path" class="col-sm-3 col-form-label">Đường dẫn tệp sao lưu:</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input readonly class="form-control border-danger" type="text" name="backup_web_interface_backup_path" id="backup_web_interface_backup_path" placeholder="<?php echo $Config['backup_upgrade']['web_interface']['backup']['backup_path']; ?>" value="<?php echo $Config['backup_upgrade']['web_interface']['backup']['backup_path']; ?>">
</div>
</div>
</div>

<div class="row mb-3">
<label for="backup_web_interface_limit_backup_files" class="col-sm-3 col-form-label">Tệp sao lưu tối đa <i class="bi bi-question-circle-fill" onclick="show_message('Tối đa số lượng tệp tin sao lưu trên hệ thống')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input  class="form-control border-success" type="number" min="2" step="1" max="10" name="backup_web_interface_limit_backup_files" id="backup_web_interface_limit_backup_files" placeholder="<?php echo $Config['backup_upgrade']['web_interface']['backup']['limit_backup_files']; ?>" value="<?php echo $Config['backup_upgrade']['web_interface']['backup']['limit_backup_files']; ?>">
</div>
</div>
</div>

<div class="row mb-3">
<label for="backup_upgrade_web_interface_exclude_files_folder" class="col-sm-3 col-form-label">Bỏ qua file, thư mục không sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi thư mục hoặc file sẽ là 1 dòng, nếu là file sẽ cần có đầy đủ đuôi mở rộng của file, ví dụ: <b>123.mp3</b>')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<textarea class="form-control border-success" rows="5" name="backup_upgrade_web_interface_exclude_files_folder" id="backup_upgrade_web_interface_exclude_files_folder">
<?php
$excludeFilesFolder_Vbot_web_interface = isset($Config['backup_upgrade']['web_interface']['backup']['exclude_files_folder']) ? $Config['backup_upgrade']['web_interface']['backup']['exclude_files_folder'] : [];
    if (!empty($excludeFilesFolder_Vbot_web_interface)) {
        foreach ($excludeFilesFolder_Vbot_web_interface as $index_exclude_files_folder_web_interface => $item_exclude_files_folder_web_interface) {
            echo htmlspecialchars($item_exclude_files_folder_web_interface);
            // Không thêm xuống dòng cuối cùng sau phần tử cuối
            if ($index_exclude_files_folder_web_interface < count($excludeFilesFolder_Vbot_web_interface) - 1) {
                echo "\n";
            }
        }
    }
?>
</textarea>
</div>
</div>
</div>

<div class="row mb-3">
<label for="backup_upgrade_web_interface_exclude_file_format" class="col-sm-3 col-form-label">Bỏ qua định dạng tệp không sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi định dạng tệp là 1 dòng, cần có dấu <b>.</b> ở trước định dạng tệp ví dụ: <b>.mp3</b> hoặc <b>.mp4</b>')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<textarea class="form-control border-success" rows="5" name="backup_upgrade_web_interface_exclude_file_format" id="backup_upgrade_web_interface_exclude_file_format">
<?php
$backup_upgrade_web_interface_exclude_file = isset($Config['backup_upgrade']['web_interface']['backup']['exclude_file_format']) ? $Config['backup_upgrade']['web_interface']['backup']['exclude_file_format'] : [];
    if (!empty($backup_upgrade_web_interface_exclude_file)) {
        foreach ($backup_upgrade_web_interface_exclude_file as $index_exclude_file_format_web_interface => $exclude_file_format_web_interface) {
            echo htmlspecialchars($exclude_file_format_web_interface);
            // Không thêm xuống dòng cuối cùng sau phần tử cuối
            if ($index_exclude_file_format_web_interface < count($backup_upgrade_web_interface_exclude_file) - 1) {
                echo "\n";
            }
        }
    }
?>
</textarea>
</div>
</div>
</div>
</div>
</div>
</div>



<div class="card accordion" id="accordion_button_cap_nhat_giao_dien">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cap_nhat_giao_dien" aria-expanded="false" aria-controls="collapse_button_cap_nhat_giao_dien">
Cập Nhật Giao Diện:</h5>
<div id="collapse_button_cap_nhat_giao_dien" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cap_nhat_giao_dien">


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tạo Bản Sao Lưu Trước Khi Cập Nhật: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu được tạo ra trước khi cập nhật sẽ được tải lên Google Drive')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="make_a_backup_before_updating_interface" id="make_a_backup_before_updating_interface" <?php echo $Config['backup_upgrade']['web_interface']['upgrade']['backup_before_updating'] ? 'checked' : ''; ?>>
</div>
</div>
</div>



<div class="row mb-3">
<label for="vbot_web_interface_upgrade_keep_the_file_folder" class="col-sm-3 col-form-label">Giữ lại Tệp, Thư Mục Không Cập Nhật <i class="bi bi-question-circle-fill" onclick="show_message('Giữ lại tệp hoặc thư mục không cho phép cập nhật, mỗi tệp hoặc thư mục là 1 dòng, nếu là tệp tin thì cần có đầy đủ tên và đuôi của tệp, ví dụ giữ lại tệp: <b>Config.json</b>, giữ lại thư mục: <b>eng</b>')"></i> :</label>
<div class="col-sm-9"><div class="input-group mb-3">
<textarea class="form-control border-success" rows="5" name="vbot_web_interface_upgrade_keep_the_file_folder" id="vbot_web_interface_upgrade_keep_the_file_folder">
<?php
$excludeFilesFolder_web_interface_upgrade = isset($Config['backup_upgrade']['web_interface']['upgrade']['keep_file_directory']) ? $Config['backup_upgrade']['web_interface']['upgrade']['keep_file_directory'] : [];
    if (!empty($excludeFilesFolder_web_interface_upgrade)) {
        foreach ($excludeFilesFolder_web_interface_upgrade as $index_exclude_files_folder_web_interface_UPGRADE => $item_exclude_files_folder_web_interface_upgrade) {
            echo htmlspecialchars($item_exclude_files_folder_web_interface_upgrade);
            // Không thêm xuống dòng cuối cùng sau phần tử cuối
            if ($index_exclude_files_folder_web_interface_UPGRADE < count($excludeFilesFolder_web_interface_upgrade) - 1) {
                echo "\n";
            }
        }
    }
?>
</textarea>
</div>
</div>
</div>

</div>
</div>
</div>
				
</div>
</div>


</div>
</div>
</div>


<div class="card accordion" id="accordion_button_Cloud_backup">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_Cloud_Backup" aria-expanded="false" aria-controls="collapse_button_Cloud_Backup">
Cloud Backup&nbsp;<i class="bi bi-cloud-check"></i>&nbsp;:</h5>
<div id="collapse_button_Cloud_Backup" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_Cloud_Backup">

<div class="card">
<div class="card-body">
<h5 class="card-title">Google Cloud Drive <i class="bi bi-question-circle-fill" onclick="show_message('Cấu hình thiết lập đồng bộ dữ liệu lên Google Cloud Drive<br/>- Nếu có nhiều thiết bị cần đồng bộ lên Google Cloud Drive thì cần thay đổi tên của 3 thư mục để tránh bị trùng lặp với dữ liệu của thiết bị khác<br/><a href=\'https://docs.google.com/document/d/1-VTi9MOAgQoR8jZrhN9FlZxjWsq2vDuy/edit?usp=drive_link&ouid=106149318613102395200&rtpof=true&sd=true\' target=\'_bank\'><b>Hướng dẫn tạo file json</b></a>')"></i> | <a href="GCloud_Drive.php" title="Truy Cập"> <i class="bi bi-box-arrow-up-right"></i> Truy Cập</a> :</h5>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chức năng sao lưu dữ liệu lê Google Cloud Drive')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="google_cloud_drive_active" id="google_cloud_drive_active" <?php echo $Config['backup_upgrade']['google_cloud_drive']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tên Thư Mục Cha Sao Lưu <i class="bi bi-question-circle-fill" onclick="show_message('Tên Thư Mục Sao Lưu Trên Google Cloud Drive (Thư Mục Cha), Nếu thư mục không tồn tại sẽ tự động được tạo mới')"></i> : </label>
<div class="col-sm-9">
<input required class="form-control border-success" type="text" name="gcloud_drive_backup_folder_name" id="gcloud_drive_backup_folder_name" value="<?php echo $Config['backup_upgrade']['google_cloud_drive']['backup_folder_name']; ?>">
<div class="invalid-feedback">Cần nhập Tên Thư Mục Sao Lưu trên Google Drive</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tên Thư Mục Sao Lưu Chương Trình Vbot <i class="bi bi-question-circle-fill" onclick="show_message('Tên Thư Mục Sao Lưu Chương Trình Vbot Trên Google Cloud Drive (Thư Mục Con), Nếu thư mục không tồn tại sẽ tự động được tạo mới')"></i> : </label>
<div class="col-sm-9">
<input required class="form-control border-success" type="text" name="gcloud_drive_backup_folder_vbot_name" id="gcloud_drive_backup_folder_vbot_name" value="<?php echo $Config['backup_upgrade']['google_cloud_drive']['backup_folder_vbot_name']; ?>">
<div class="invalid-feedback">Cần nhập Tên Thư Mục Sao Lưu trên Google Drive</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Tên Thư Mục Sao Lưu Giao Diện Vbot <i class="bi bi-question-circle-fill" onclick="show_message('Tên Thư Mục Sao Lưu Giao Diện Vbot Trên Google Cloud Drive (Thư Mục Con), Nếu thư mục không tồn tại sẽ tự động được tạo mới')"></i> : </label>
<div class="col-sm-9">
<input required class="form-control border-success" type="text" name="gcloud_drive_backup_folder_interface_name" id="gcloud_drive_backup_folder_interface_name" value="<?php echo $Config['backup_upgrade']['google_cloud_drive']['backup_folder_interface_name']; ?>">
<div class="invalid-feedback">Cần nhập Tên Thư Mục Sao Lưu trên Google Drive</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kiểu Loại Truy Cập <i class="bi bi-question-circle-fill" onclick="show_message('- Để giá trị là offline thì sẽ tự động làm mới lại mã token xác thực khi hết hạn<br/>- Để giá trị là online thì mỗi lần mã token xác thực hết hạn bạn cần lấy lại bằng thao tác thủ công')"></i> : </label>
<div class="col-sm-9">
<select class="form-select border-success" name="gcloud_drive_setAccessType" id="gcloud_drive_setAccessType">
<option value="offline" <?php if ($Config['backup_upgrade']['google_cloud_drive']['setAccessType'] === "offline") echo "selected"; ?>>Offline (Tự động làm mới Token)</option>
<option value="online" <?php if ($Config['backup_upgrade']['google_cloud_drive']['setAccessType'] === "online") echo "selected"; ?>>Online (Làm mới Token thủ công)</option>
</select>
</div>
</div>


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Đặt Lời Nhắc Khi Xác Thực 
    <i class="bi bi-question-circle-fill" 
       onclick="show_message('- none: Không hiển thị bất kỳ trang yêu cầu quyền nào từ Google. ' + 
       'Nếu người dùng đã đăng nhập và đã cấp quyền, họ sẽ được chuyển hướng ngay lập tức. ' + 
       'Nếu không có quyền hoặc người dùng chưa đăng nhập, yêu cầu sẽ trả về lỗi.<br/><br/> ' + 
       '- consent: Luôn yêu cầu người dùng đồng ý cấp quyền, ngay cả khi họ đã cấp quyền trước đó. ' + 
       'Điều này hữu ích khi bạn muốn người dùng xác nhận lại các quyền đã cấp.<br/><br/> ' + 
       '- select_account: Hiển thị một danh sách các tài khoản Google để người dùng chọn tài khoản muốn sử dụng ' + 
       'nếu họ đã đăng nhập nhiều tài khoản.<br/><br/> ' + 
       '- consent select_account: Hiển thị cả hộp chọn tài khoản và yêu cầu người dùng xác nhận lại quyền truy cập.')">
    </i> : 
</label>
<div class="col-sm-9">
<select class="form-select border-success" name="gcloud_drive_setPrompt" id="gcloud_drive_setPrompt">
<option value="none" <?php if ($Config['backup_upgrade']['google_cloud_drive']['setPrompt'] === "none") echo "selected"; ?>>None (Không hiển thị)</option>
<option value="consent" <?php if ($Config['backup_upgrade']['google_cloud_drive']['setPrompt'] === "consent") echo "selected"; ?>>Consent (Yêu cầu đồng ý cấp quyền)</option>
<option value="select_account" <?php if ($Config['backup_upgrade']['google_cloud_drive']['setPrompt'] === "select_account") echo "selected"; ?>>Select Account (Chọn tài khoản muốn dùng)</option>
<option value="consent select_account" <?php if ($Config['backup_upgrade']['google_cloud_drive']['setPrompt'] === "consent select_account") echo "selected"; ?>>Consent Select Account (Chọn và Yêu cầu đồng ý cấp quyền)</option>
</select>

</div>
</div>

</div>
</div>

</div>
</div>
</div>



<div class="card accordion" id="accordion_button_bluetooth_uart_vbot">
<div class="card-body">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_bluetooth_uart_vbot" aria-expanded="false" aria-controls="collapse_button_bluetooth_uart_vbot">
Bluetooth <i class="bi bi-bluetooth"></i> <font color=red>(Chức năng chưa được phát triển)</font>:</h5>
<div id="collapse_button_bluetooth_uart_vbot" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_bluetooth_uart_vbot">


<div class="row mb-3">
<label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để Khởi Tạo Bluetooth')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="bluetooth_active" id="bluetooth_active" <?php echo $Config['bluetooth']['active'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label">Hiển thị Logs Bluetooth <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để hiển thị logs Bluetooth')"></i> :</label>
<div class="col-sm-9">
<div class="form-switch">
<input class="form-check-input" type="checkbox" name="bluetooth_show_logs" id="bluetooth_show_logs" <?php echo $Config['bluetooth']['show_logs'] ? 'checked' : ''; ?>>
</div>
</div>
</div>

<div class="row mb-3">
<label for="bluetooth_gpio_power" class="col-sm-3 col-form-label">GPIO Power:</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input readonly class="form-control border-danger" type="number" step="1" min="1" max="30" name="bluetooth_gpio_power" id="bluetooth_gpio_power" placeholder="<?php echo $Config['bluetooth']['gpio_power']; ?>" value="<?php echo $Config['bluetooth']['gpio_power']; ?>">
</div>
</div>
</div>

<div class="row mb-3">
<label for="bluetooth_baud_rate" class="col-sm-3 col-form-label">Baud Rate:</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input readonly class="form-control border-danger" type="number" name="bluetooth_baud_rate" id="bluetooth_baud_rate" placeholder="<?php echo $Config['bluetooth']['baud_rate']; ?>" value="<?php echo $Config['bluetooth']['baud_rate']; ?>">
</div>
</div>
</div>

<div class="row mb-3">
<label for="bluetooth_serial_port" class="col-sm-3 col-form-label">Serial Port:</label>
<div class="col-sm-9"><div class="input-group mb-3">
<input readonly class="form-control border-danger" type="text"  name="bluetooth_serial_port" id="bluetooth_serial_port" placeholder="<?php echo $Config['bluetooth']['serial_port']; ?>" value="<?php echo $Config['bluetooth']['serial_port']; ?>">
</div>
</div>
</div>

</div>
</div>
</div>

<div class="card">
<div class="card-body">
              <h5 class="card-title">Chế Độ Hội Thoại/Trò Chuyện Liên Tục <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật Bạn chỉ cần gọi Bot 1 lần, sau khi bot trả lời xong sẽ tự động lắng nghe tiếp và lặp lại (cho tới khi Bạn không còn yêu cầu nào nữa)')"></i> :</h5>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt chế độ hội thoại <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chế độ hội thoại')"></i> :</label>
                  <div class="col-sm-9">
				  
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="conversation_mode" id="conversation_mode" <?php echo $Config['smart_config']['smart_wakeup']['conversation_mode'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
                </div>
                </div>

<div class="card">
			<div class="card-body">
              <h5 class="card-title">Đọc thông tin khi khởi động:</h5>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Bật, Tắt đọc thông tin <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt đọc thông tin khi Chương trình khởi động như: Địa chỉ ip của thiết bị, v..v...')"></i> :</label>
                  <div class="col-sm-9">

					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="read_information_startup" id="read_information_startup" <?php echo $Config['smart_config']['read_information_startup']['active'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Số lần đọc: </label>
                  <div class="col-sm-9">

					<div class="form-switch">
                      <input required class="form-control border-success" type="number" min="1" step="1" max="5" name="read_information_startup_read_number" id="read_information_startup_read_number" value="<?php echo $Config['smart_config']['read_information_startup']['read_number']; ?>">
                      <div class="invalid-feedback">Cần nhập số lần đọc thông tin khi khởi động lần đầu tiên</div>
                    </div>
                  </div>
                </div>
                </div>
				
                </div>

<div class="card">
			<div class="card-body">
              <h5 class="card-title">Xử Lý Lỗi:</h5>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Khởi động lại hệ thống khi gặp sự cố hoặc lỗi bất ngờ: <i class="bi bi-question-circle-fill" onclick="show_message('Tự động khởi động lại chương trình VBot khi gặp sự cố hoặc có lỗi xảy ra bất ngờ, Sẽ chỉ hoạt động ở chế độ đang chạy Auto')"></i> :</label>
                  <div class="col-sm-9">
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="auto_restart_program_error" id="auto_restart_program_error" <?php echo $Config['smart_config']['auto_restart_program_error'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>
                </div>
                </div>

			<div class="card">
			<div class="card-body">
              <h5 class="card-title">Log Hệ Thống:</h5>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Bật, Tắt logs hệ thống <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt log của toàn bộ chương trình khi được chạy')"></i> :</label>
                  <div class="col-sm-9">
				  
					<div class="form-switch">
                      <input class="form-check-input" type="checkbox" name="log_active" id="log_active" <?php echo $Config['smart_config']['show_log']['active'] ? 'checked' : ''; ?>>
                      
                    </div>
                  </div>
                </div>
				
				<div class="row mb-3">
                  <label for="log_display_style" class="col-sm-3 col-form-label">Kiểu hiển thị Logs:</label>
                  <div class="col-sm-9">
                   <select name="log_display_style" id="log_display_style" class="form-select border-success" aria-label="Default select example">
                      <option value="console" <?php echo $Config['smart_config']['show_log']['log_display_style'] === 'console' ? 'selected' : ''; ?>>console (Hiển thị log ra bảng điều khiển đầu cuối)</option>
                      <option value="api" <?php echo $Config['smart_config']['show_log']['log_display_style'] === 'api' ? 'selected' : ''; ?>>api (Hiển thị log ra API, Web UI)</option>
                      <option value="display_screen" <?php echo $Config['smart_config']['show_log']['log_display_style'] === 'display_screen' ? 'selected' : ''; ?>>display_screen (Hiển thị log ra màn hình)</option>
                      <option value="all" <?php echo $Config['smart_config']['show_log']['log_display_style'] === 'all' ? 'selected' : ''; ?>>all (Hiển thị log ra tất cả các đường)</option>
					</select>
                  </div>
                </div>
                </div>
                </div>


<div class="card">
<div class="card-body">
<h5 class="card-title">Khôi Phục Config.json <i class="bi bi-question-circle-fill" onclick="show_message('Khôi Phục Config.json từ tệp sao lưu trên hệ thống')"></i>:</h5>

<div class="row mb-3">
    <label class="col-sm-3 col-form-label"><b>Tải Lên Tệp Và Khôi Phục:</b></label>
    <div class="col-sm-9">
        <div class="input-group">
            <input class="form-control border-success" type="file" name="fileToUpload_configjson_restore" accept=".json">
            <button class="btn btn-warning border-success" type="submit" name="start_recovery_config_json" value="khoi_phuc_tu_tep_tai_len" onclick="return confirmRestore('Bạn có chắc chắn muốn tải lên tệp để khôi phục dữ liệu Config.json không?')"><i class="bi bi-upload"></i> Tải Lên & Khôi Phục</button>
        </div>
    </div>
</div>

<div class="row mb-3">
<label class="col-sm-3 col-form-label text-primary"><b>Hoặc Chọn Tệp Khôi Phục:</b></label>
<div class="col-sm-9">

<?php
$jsonFiles = glob($Config['backup_upgrade']['config_json']['backup_path'].'/*.json');
$co_tep_BackUp_ConfigJson = true;
if (empty($jsonFiles)) {
	$co_tep_BackUp_ConfigJson = false;
echo '<select class="form-select border-primary" name="backup_config_json_files" id="backup_config_json_files">';
    echo '<option selected value="">Không có tệp khôi phục dữ liệu Config nào</option>';
	echo '</select>';
} else {
	$co_tep_BackUp_ConfigJson = true;
echo '<div class="input-group"><select class="form-select border-primary" name="backup_config_json_files" id="backup_config_json_files">';
echo '<option selected value="">Chọn Tệp Khôi Phục Dữ Liệu Config</option>';
foreach ($jsonFiles as $file) {
    $fileName = basename($file);
    echo '<option value="' . htmlspecialchars($Config['backup_upgrade']['config_json']['backup_path'].'/'.$fileName) . '">' . htmlspecialchars($fileName) . '</option>';
}
echo '</select>';
}

if ($co_tep_BackUp_ConfigJson === true){
echo '
<button class="btn btn-primary border-primary" name="start_recovery_config_json" value="khoi_phuc_tu_tep_he_thong" type="submit" onclick="return confirmRestore(\'Bạn có chắc chắn muốn khôi phục dữ liệu Config từ tệp sao lưu trên hệ thống\')">Khôi Phục</button>
<button type="button" class="btn btn-info border-primary" onclick="readJSON_file_path(\'get_value_backup_config\')"><i class="bi bi-eye"></i></button>
<button type="button" class="btn btn-success border-primary" title="Tải Xuống Tệp Sao Lưu Config" onclick="dowlaod_file_backup_json_config(\'get_value_backup_config\')"><i class="bi bi-download"></i></button>
<button type="button" class="btn btn-danger border-primary" title="Xóa Tệp Sao Lưu Config" onclick="delete_file_backup_json_config(\'get_value_backup_config\')"><i class="bi bi-trash"></i></button>
</div>';
}

?>
</div>
</div>

</div>
</div>

<div class="row mb-3">
<center>
<button type="submit" name="all_config_save" class="btn btn-primary rounded-pill"><i class="bi bi-save"></i> Lưu Cài Đặt</button>
<button type="button" class="btn btn-warning rounded-pill" onclick="readJSON_file_path('<?php echo $Config_filePath; ?>')"><i class="bi bi-eye"></i> Xem Tệp Config</button>
<button type="button" class="btn btn-success rounded-pill" title="Tải Xuống file: Config.json" onclick="downloadFile('<?php echo $Config_filePath; ?>')"><i class="bi bi-download"></i> Tải Xuống</button>
</center>

    <!-- Modal hiển thị tệp Config.json -->
    <div class="modal fade" id="myModal_Config" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
        <div class="modal-dialog" id="modal_dialog_show_config" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <b><font color=blue><div id="name_file_showzz"></div></font></b> 
				<button type="button" class="close btn btn-danger" data-dismiss="modal_Config" aria-label="Close" onclick="$('#myModal_Config').modal('hide');">
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
                
                </div>
          </div>
        </div>
      </div>
    </section>
    </form>
  </main><!-- End #main -->

<!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->




     <!-- Nút cuộn lên -->
    <a href="#" title="Cuộn lên trên cùng" class="scroll-btn scroll-to-top d-flex align-items-center justify-content-center" onclick="scrollToTop(event)">
        <i class="bi bi-arrow-up-short"></i>
    </a>
   <!-- Nút cuộn xuống -->
    <a href="#" title="Cuộn xuống dưới cùng" class="scroll-btn scroll-to-bottom d-flex align-items-center justify-content-center" onclick="scrollToBottom(event)">
        <i class="bi bi-arrow-down-short"></i>
    </a>

  <!-- Template Main JS File -->
<script src="assets/vendor/prism/prism.min.js"></script>
<script src="assets/vendor/prism/prism-json.min.js"></script>
<script src="assets/vendor/prism/prism-yaml.min.js"></script>

<?php
include 'html_js.php';
?>

<script>
// Hàm để cuộn lên đầu trang
function scrollToTop(event) {
    event.preventDefault();
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Hàm để cuộn xuống cuối trang
function scrollToBottom(event) {
    event.preventDefault();
    window.scrollTo({
        top: document.body.scrollHeight,
        behavior: 'smooth'
    });
}

//Xóa file backup Config
function delete_file_backup_json_config(filePath) {
    if (filePath === "get_value_backup_config") {
        var get_value_backup_config = document.getElementById('backup_config_json_files').value;
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
function dowlaod_file_backup_json_config(filePath) {
    if (filePath === "get_value_backup_config") {
        var get_value_backup_config = document.getElementById('backup_config_json_files').value;
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

//Kiểm tra kết nối MQTT
function checkMQTTConnection() {
	loading('show');
    var host = document.getElementById('mqtt_host').value;
    var port = document.getElementById('mqtt_port').value;
    var user = document.getElementById('mqtt_username').value;
    var pass = document.getElementById('mqtt_password').value;
    var url = 'includes/php_ajax/Check_Connection.php?check_mqtt&host=' + host + '&port=' + port + '&user=' + user + '&pass=' + pass;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
			loading('hide');
            try {
                var response = JSON.parse(xhr.responseText);
				show_message(response.message)
            } catch (e) {
				show_message('Lỗi khi phân tích cú pháp JSON: ' +e)
            }
        } else {
			loading('hide');
			show_message('Yêu cầu thất bại. Mã lỗi: '+xhr.status)
        }
    };
    xhr.onerror = function() {
		loading('hide');
		show_message('Yêu cầu bị lỗi.')
    };
    xhr.send();
}

//Đọc nội dung các file yaml MQTT
function read_YAML_file_path(fileName) {
	loading('show');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Show_file_path.php?yaml=' + fileName, true);
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
			loading('hide');
            var codeElement = document.getElementById('code_config');
            // Hiển thị nội dung YAML
            codeElement.textContent = xhr.responseText.trim();
			// Áp dụng lớp cú pháp YAML
            codeElement.className = 'language-yaml';
            // Kích hoạt Prism.js để làm nổi bật cú pháp
            Prism.highlightElement(codeElement);
            showMessagePHP("Lấy Dữ Liệu: " + fileName + " thành công", 3)
            document.getElementById('name_file_showzz').textContent = "Tên File: " + fileName.split('/').pop();
            $('#myModal_Config').modal('show');
        } else {
			loading('hide');
            show_message('Thất bại, Mã lỗi:' + xhr.status)
        }
    };
    xhr.onerror = function() {
		loading('hide');
        show_message('Lỗi xảy ra, Truy vấn thất bại')
    };
    xhr.send();
}

//onclick xem nội dung file json
function readJSON_file_path(filePath) {
    if (filePath === "get_value_backup_config") {
		//Lấy giá trị value của id: backup_config_json_files
        var get_value_backup_config = document.getElementById('backup_config_json_files').value;
        if (get_value_backup_config === "") {
            showMessagePHP("Không có tệp nào được chọn để xem nội dung");
        } else {
            filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
            read_loadFile(filePath);
			document.getElementById('name_file_showzz').textContent = "Tên File: "+filePath.split('/').pop();
            $('#myModal_Config').modal('show');
        }
    } else {
        read_loadFile(filePath);
		document.getElementById('name_file_showzz').textContent = "Tên File: "+filePath.split('/').pop();
        $('#myModal_Config').modal('show');
    }
}

//ẩn hiện Cấu hình STT: khi lựa chọn radio Lựa chọn STT (Speak To Text):
document.querySelectorAll('input[name="stt_select"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const div_select_stt_ggcloud_html = document.getElementById('select_stt_ggcloud_html');
        const div_select_stt_default_html = document.getElementById('select_stt_default_html');
        const div_select_stt_ggcloud_v2_html = document.getElementById('select_stt_ggcloud_v2_html');
        if (document.getElementById('stt_ggcloud').checked) {
            div_select_stt_ggcloud_html.style.display = 'block'; // Hiển thị div
        } else {
            div_select_stt_ggcloud_html.style.display = 'none'; // Ẩn div
        }
        if (document.getElementById('stt_default').checked) {
            div_select_stt_default_html.style.display = 'block';
        } else {
            div_select_stt_default_html.style.display = 'none';
        }
        if (document.getElementById('stt_ggcloud_v2').checked) {
            div_select_stt_ggcloud_v2_html.style.display = 'block';
        } else {
            div_select_stt_ggcloud_v2_html.style.display = 'none';
        }
    });
});

//ẩn hiện cấu hình tts khi được lựa chọn
document.querySelectorAll('input[name="tts_select"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const div_select_tts_ggcloud_html = document.getElementById('select_tts_ggcloud_html');
        const div_select_tts_default_html = document.getElementById('select_tts_default_html');
        const div_select_tts_edge_html = document.getElementById('select_tts_edge_html');
        const div_select_tts_ggcloud_key = document.getElementById('select_tts_ggcloud_key');
        const div_select_tts_zalo_html = document.getElementById('select_tts_zalo_html');
        const div_select_tts_viettel_html = document.getElementById('select_tts_viettel_html');
        if (document.getElementById('tts_ggcloud').checked) {
            div_select_tts_ggcloud_html.style.display = 'block'; // Hiển thị div
        } else {
            div_select_tts_ggcloud_html.style.display = 'none'; // Ẩn div
        }
        if (document.getElementById('tts_default').checked) {
            div_select_tts_default_html.style.display = 'block';
			document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_default_html.style.display = 'none';
        }
        if (document.getElementById('tts_zalo').checked) {
            getBacklistData('backlist->tts_zalo', 'tts_zalo_backlist_content');
            div_select_tts_zalo_html.style.display = 'block';
			document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_zalo_html.style.display = 'none';
        }
        if (document.getElementById('tts_viettel').checked) {
            getBacklistData('backlist->tts_viettel', 'tts_viettel_backlist_content');
            div_select_tts_viettel_html.style.display = 'block';
			document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_viettel_html.style.display = 'none';
        }
        if (document.getElementById('tts_edge').checked) {
            div_select_tts_edge_html.style.display = 'block';
			document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_edge_html.style.display = 'none';
        }
        if (document.getElementById('tts_ggcloud_key').checked) {
            div_select_tts_ggcloud_key.style.display = 'block';
			document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_ggcloud_key.style.display = 'none';
        }
		if (document.getElementById('tts_dev_customize').checked) {
			document.getElementById("tts_dev_customize_active").checked = true;
        }
    });
});

//Hiển thị list hotword khi được scan
function loadConfigHotword(lang) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?hotword&lang=' + lang, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            displayResults_Hotword_dataa(data);
        }
    };
    xhr.send();
}

    function displayResults_Hotword_dataa(data) {
        const resultsDiv = document.getElementById('results_body_hotword');
        const resultsDiv1 = document.getElementById('results_body_hotword1');
        // Xóa nội dung hiện tại
        resultsDiv.innerHTML = '';
        resultsDiv1.innerHTML = '';
        // Hiển thị ngôn ngữ đang được truy vấn
        let reponse_lang = data.lang === "vi" ? "Tiếng Việt" : "Tiếng Anh";
        const langDiv = document.getElementById('language_hotwordd');
        langDiv.innerHTML = '<strong>- Ngôn ngữ: </strong> <font color="red" id="data_lang_shows" value=' + data.lang + '>' + reponse_lang + '</font><br/>- File Thư Viện Đang Dùng: <font color="red">' + data.config_lib_pv_to_lang + '</font>';
        const fileList = data.files_lib_pv;
        // Tạo thẻ <select> một lần với tất cả các tùy chọn
        var selectHtml = '<tr><td colspan="4"><div class="form-floating mb-3"><select required class="form-select" id="select_file_lib_pv" name="select_file_lib_pv">';
        selectHtml += '<option value="">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</option>';
        fileList.forEach(function(file) {
            var isSelected_lib = (file === data.config_lib_pv_to_lang) ? ' selected' : '';
            selectHtml += '<option value="' + file + '"' + isSelected_lib + '>' + file + '</option>';
        });
        selectHtml += '</select><div class="invalid-feedback">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</div> <label for="select_file_lib_pv">Chọn file thư viện Hotword: ' + reponse_lang + '</label></div></td>';
        selectHtml += '<td style="text-align: center; vertical-align: middle;"><center><button type="button" class="btn btn-danger" id="deleteFilePV" title="Xóa file: "><i class="bi bi-trash"></i></button>  <button type="button" class="btn btn-success" id="downloadFilePV" title="Tải xuống file: "><i class="bi bi-download"></i></button> </center></td></tr>';

        if (Array.isArray(data.config) && data.config.length > 0) {
            let i_up = 0;
            let tableContent = '';
            data.config.forEach((item, index) => {
                i_up++;
                tableContent +=
                    '<tr>' +
                    '<td style="text-align: center; vertical-align: middle;">' + i_up + '</td>' +
                    '<td style="text-align: center; vertical-align: middle;"><div  class="form-switch"><input class="form-check-input" type="checkbox" name="active_' + index + '" ' + (item.active ? 'checked' : '') + '></div></td>' +
                    '<td><input readonly class="form-control" type="text" name="file_name_' + index + '" value="' + item.file_name + '"></td>' +
                    '<td><input class="form-control" type="number" name="sensitive_' + index + '" value="' + item.sensitive + '" step="0.1" min="0.1" max="1.0"></td>' +
                    '<td style="text-align: center; vertical-align: middle;"><center><button type="button" class="btn btn-danger" title="Xóa file: ' + item.file_name + '" onclick="deleteFile(\'' + data.path_ppn + item.file_name + '\', \'' + data.lang + '\')"><i class="bi bi-trash"></i></button> ' +
                    '  <button type="button" class="btn btn-success" title="Tải xuống file: ' + item.file_name + '"  onclick="downloadFile(\'' + data.path_ppn + item.file_name + '\')"><i class="bi bi-download"></i></button></center></td>' +
                    '</tr>';
            });
            // Thêm nút tải lên và nút lưu vào thẻ <tr>
            tableContent +=
                '<tr>' +
                '<td colspan="5" style="text-align: center;">' +
                '<input type="hidden" name="lang_hotword_get" id="lang_hotword_get" value="' + data.lang + '">' +
                '<label for="upload_files"><font color=blue>Tải lên file hotword .ppn hoặc file thư viện .pv cho <b>'+reponse_lang+'</b></font></label>' +
                '<div class="input-group">' +
                '<input class="form-control" type="file" name="upload_files_ppn_pv[]" id="upload_files_ppn_pv" accept=".ppn,.pv" multiple>' +
                ' <button class="btn btn-primary"  type="button" onclick="uploadFilesHotwordPPNandPV()">Tải Lên</button>' +
                '</div>' +
                '<br/><button type="submit" name="save_hotword_theo_lang" class="btn btn-success rounded-pill" title="Lưu cài đặt hotword">Lưu Cài Đặt Hotword</button>' +
                '</td>' +
                '</tr>';
            resultsDiv1.innerHTML +=
                selectHtml +
                '<tr><th><center>STT</center></th>' +
                '<th><center>Kích Hoạt</center></th>' +
                '<th><center>File Hotword</center></th>' +
                '<th><center>Độ Nhạy</center></th>' +
                '<th><center>Hành Động</center></th></tr>';
            resultsDiv.innerHTML = tableContent;
        } 
		//Nếu không có dữ liệu hotword trong COnfig.json là rỗng: []
		else {
            let i_up = 0;
            let tableContent = '';
			var selectHtml = '<tr><td colspan="4"><div class="form-floating mb-3"><select required class="form-select" id="select_file_lib_pv" name="select_file_lib_pv">';
			selectHtml += '<option value="">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</option>';
			fileList.forEach(function(file) {
				var isSelected_lib = (file === data.config_lib_pv_to_lang) ? ' selected' : '';
				selectHtml += '<option value="' + file + '"' + isSelected_lib + '>' + file + '</option>';
			});
			selectHtml += '</select><div class="invalid-feedback">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</div> <label for="select_file_lib_pv">Chọn file thư viện Hotword: ' + reponse_lang + '</label></div></td>';
			selectHtml += '<td style="text-align: center; vertical-align: middle;"><center><button type="button" class="btn btn-danger" id="deleteFilePV" title="Xóa file: "><i class="bi bi-trash"></i></button>  <button type="button" class="btn btn-success" id="downloadFilePV" title="Tải xuống file: "><i class="bi bi-download"></i></button> </center></td></tr>';
            resultsDiv1.innerHTML = selectHtml;
			tableContent += '<tr><td colspan="5" class="text-danger"><center>Không có dữ liệu Hotword nào được cấu hình với ngôn ngữ '+reponse_lang+'</center></td></tr>';
            tableContent +=
                '<tr>' +
                '<td colspan="5" style="text-align: center;">' +
                '<input type="hidden" name="lang_hotword_get" id="lang_hotword_get" value="' + data.lang + '">' +
                '<label for="upload_files"><font color=blue>Tải lên file hotword .ppn hoặc file thư viện .pv cho <b>'+reponse_lang+'</b></font></label>' +
                '<div class="input-group">' +
                '<input class="form-control" type="file" name="upload_files_ppn_pv[]" id="upload_files_ppn_pv" accept=".ppn,.pv" multiple>' +
                ' <button class="btn btn-primary"  type="button" onclick="uploadFilesHotwordPPNandPV()">Tải Lên</button>' +
                '</div>' +
                '<br/><button type="submit" name="save_hotword_theo_lang" class="btn btn-success rounded-pill" title="Lưu cài đặt hotword">Lưu Cài Đặt Hotword</button>' +
                '</td>' +
                '</tr>';
			resultsDiv.innerHTML = tableContent;
        }
        // Lấy giá trị mặc định của thẻ select khi tải trang
        var selectedValuee = document.getElementById('select_file_lib_pv').value;
        var deleteIconn = document.getElementById('deleteFilePV');
        var downloadIcon = document.getElementById('downloadFilePV');
        // Thiết lập onclick xóa file trong thẻ select với giá trị mặc định
        deleteIconn.onclick = function() {
            if (selectedValuee) {
                deleteFile(data.path_pv + selectedValuee);
                // Tải lại dữ liệu hotword ở Config.json
                loadConfigHotword(data.lang);
            } else {
                show_message('Cần chọn file thư viện .pv trước khi xóa');
            }
        };
        // Thiết lập onclick tải xuống file
        downloadIcon.onclick = function() {
            if (selectedValuee) {
                downloadFile(data.path_pv + selectedValuee);
            } else {
                show_message('Cần chọn file thư viện .pv trước khi tải xuống');
            }
        };
        // Lắng nghe sự kiện thay đổi trên thẻ select pv để xóa file
        document.getElementById('select_file_lib_pv').addEventListener('change', function() {
            var selectedValue = this.value;
            var deleteIcon = document.getElementById('deleteFilePV');
            var downloadIcon = document.getElementById('downloadFilePV');
            deleteIcon.onclick = function() {
                if (selectedValue) {
                    deleteFile(data.path_pv + selectedValue);
                    // Tải lại dữ liệu hotword ở Config.json
                    loadConfigHotword(data.lang);
                } else {
                    show_message('Cần chọn file thư viện .pv trước khi xóa');
                }
            };
            downloadIcon.onclick = function() {
                if (selectedValue) {
                    downloadFile(data.path_pv + selectedValue);
                } else {
                    show_message('Cần chọn file thư viện .pv trước khi tải xuống');
                }
            };
            // Cập nhật title của icon
            deleteIcon.title = 'Xóa file: ' + selectedValue;
            downloadIcon.title = 'Tải xuống file: ' + selectedValue;
        });
    }

//Tải lên file ppv và pv
function uploadFilesHotwordPPNandPV() {
    const formData = new FormData();
    const files = document.getElementById('upload_files_ppn_pv').files;
    const lang = document.getElementById('lang_hotword_get').value;
    if (files.length === 0) {
        show_message('Vui lòng chọn ít nhất một file để tải lên.');
        return;
    }
    for (let i = 0; i < files.length; i++) {
        formData.append('upload_files_ppn_pv[]', files[i]);
    }
    formData.append('lang_hotword_get', lang);
    formData.append('action_ppn_pv', 'upload_files_ppn_pv');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/Hotword_pv_ppn.php');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                let messages = [];
                if (response.status === 'success') {
                    // Tổng hợp tất cả thông báo
                    messages.push(response.messages + '<br/>');
                } else {
                    messages.push('Trạng thái phản hồi không mong đợi: ' + response.status);
                }
                //Tải lại dữ liệu hotword ở Config.json
                loadConfigHotword(lang)
                show_message(messages.join('<br/>'));
            } catch (e) {
                show_message('Không thể phân tích phản hồi json: ' + e.message);
            }
        } else {
            show_message("<center>Tải file lên thất bại</center>");
        }
    };
    xhr.send(formData);
}

//Cập nhật các file trong eng và vi để Làm mới lại cấu hình Hotword trong Config.json, 
function reload_hotword_config(langg = "No") {
    if (!confirm("Bạn có chắc chắn muốn cập nhật lại dữ liệu Hotword trong Config.json bao gồm cả tiếng anh và tiếng việt?")) {
        return;
    }
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?reload_hotword_config');
    xhr.onload = function() {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                show_message("<center>" + response.message + "</center>");
            } else {
                show_message("<center>" + response.message + "</center>");
            }
            var element_data_lang_shows = document.getElementById('data_lang_shows');
            //Tải lại dữ liệu hotword ở Config.json theo lang nếu có giá trị
            // Kiểm tra nếu phần tử tồn tại
            if (element_data_lang_shows) {
                // Lấy giá trị của thuộc tính 'value'
                var value_lang = element_data_lang_shows.getAttribute('value');
                // Thực hiện các hành động cần thiết với giá trị
                console.log('Giá trị của phần tử với ID "data_lang_shows" là: ' + value_lang);
                if (value_lang === "vi") {
                    loadConfigHotword("vi")
                } else if (value_lang === "eng") {
                    loadConfigHotword("eng")
                }
            }
        } else {
            show_message("<center>Có lỗi xảy ra khi ghi mới dữ liệu Hotword tiếng anh và tiếng việt</center>");
        }
    };
    xhr.send();
}

//Xóa thẻ input đài báo radio
function delete_Dai_bao_Radio(index_id, name_dai_radio) {
    if (name_dai_radio !== null) {
        if (!confirm('Bạn có chắc chắn muốn xóa đài "' + name_dai_radio + '" này không?')) {
            return;
        }
    }
    var row = document.getElementById('radio-row-' + index_id);
    if (row) {
        row.remove();
    }
}

//Thêm mới đài Radio
let radioIndex = <?php echo count($radio_data); ?> ;
const maxRadios = <?php echo $Max_Radios; ?> ;
function addRadio() {
    if (radioIndex < maxRadios) {
        const table = document.getElementById('radio-table').getElementsByTagName('tbody')[0];
        const newRow = document.createElement('tr');
        newRow.id = 'radio-row-' + radioIndex;
        newRow.innerHTML =
            '<td>' +
            '<input type="text" class="form-control border-success" placeholder="Nhập tên đài" name="radio_name_' + radioIndex + '" id="radio_name_' + radioIndex + '">' +
            '</td>' +
            '<td>' +
            '<input type="text" class="form-control border-success" placeholder="Nhập link đài" name="radio_link_' + radioIndex + '" id="radio_link_' + radioIndex + '">' +
            '</td>' +
            '<td style="text-align: center; vertical-align: middle;"><center>' +
            '<button type="button" class="btn btn-danger" id="delete-radio-' + radioIndex + '" onclick="delete_Dai_bao_Radio(' + radioIndex + ', null)"><i class="bi bi-trash"></i></button>'
        '</center></td>';
        table.appendChild(newRow);
        radioIndex++;
    } else {
        show_message("<center>Bạn chỉ có thể thêm tối đa <b>" + maxRadios + "</b> đài radio</center>");
    }
}

//Thêm mới Báo, tin tức
let newspaperIndex = <?php echo count($newspaper_data); ?> ;
const maxNewsPaper = <?php echo $Max_NewsPaper; ?> ;
function addNewsPaper() {
    if (newspaperIndex < maxNewsPaper) {
        const table = document.getElementById('newspaper-table').getElementsByTagName('tbody')[0];
        const newRow = document.createElement('tr');
        newRow.id = 'newspaper-row-' + newspaperIndex;
        newRow.innerHTML =
            '<td>' +
            '<input type="text" class="form-control border-success" placeholder="Nhập tên Báo, Tin Tức" name="newspaper_name_' + newspaperIndex + '" id="newspaper_name_' + newspaperIndex + '">' +
            '</td>' +
            '<td>' +
            '<input type="text" class="form-control border-success" placeholder="Nhập link/url Báo, Tin Tức" name="newspaper_link_' + newspaperIndex + '" id="newspaper_link_' + newspaperIndex + '">' +
            '</td>' +
            '<td style="text-align: center; vertical-align: middle;"><center>' +
            '<button type="button" class="btn btn-danger" id="delete-newspaper-' + newspaperIndex + '" onclick="delete_NewsPaper(' + newspaperIndex + ', null)"><i class="bi bi-trash"></i></button>'
        '</center></td>';
        table.appendChild(newRow);
        newspaperIndex++;
    } else {
        show_message("<center>Bạn chỉ có thể thêm tối đa <b>" + maxNewsPaper + "</b> kênh báo, tin tức</center>");
    }
}

//Xóa thẻ input báo, tin tức
function delete_NewsPaper(index_id, name_newspaper) {
    if (name_newspaper !== null) {
        if (!confirm('Bạn có chắc chắn muốn xóa kênh báo: "' + name_newspaper + '" này không?')) {
            return;
        }
    }
    var row = document.getElementById('newspaper-row-' + index_id);
    if (row) {
        row.remove();
    }
}

//Kiểm tra kết nối Home Assistant
function CheckConnectionHomeAssistant(inputId) {
    loading("show");
    var url_hasss = document.getElementById(inputId).value;
    var token_hasss = document.getElementById('hass_long_token').value;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Check_Connection.php?check_hass&url_hass=' + encodeURIComponent(url_hasss) + '&token_hass=' + encodeURIComponent(token_hasss), true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            loading("hide");
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    show_message('<center><font color=green><b>' + response.message + '</b></font></center><br/><b>- Tên Nhà:</b> ' + response.response.location_name +
                        '<br/><b>- Kinh độ:</b> ' + response.response.longitude + '<br/><b>- Vĩ độ:</b> ' + response.response.latitude + '<br/><b>- Múi giờ:</b> ' + response.response.time_zone +
                        '<br/><b>- Quốc gia:</b> ' + response.response.country + '<br/><b>- Ngôn ngữ:</b> ' + response.response.language +
                        '<br/><b>- Phiên bản Home Assistant:</b> ' + response.response.version + '<br/><b>- Trạng thái hoạt động:</b> ' + response.response.state +
                        '<br/><b>- URL nội bộ:</b> <a href="' + response.response.internal_url + '" target="_bank">' + response.response.internal_url + '</a><br/><b>- URL bên ngoài:</b> <a href="' + response.response.external_url + '" target="_bank">' + response.response.external_url + '</a>');
                } else {
                    show_message('<center><font color=red><b>Thất bại</b></font></center><br/>' + response.message)
                }
            } else {
                console.log('Lỗi kết nối: ' + xhr.statusText);
            }
        }
    };
    xhr.send();
}

//Kiểm tra Kết Nối SSH
function checkSSHConnection() {
	loading("show");
    var sshHost = "<?php echo $serverIp; ?>";
    var sshPort = document.getElementById('ssh_port').value;
    var sshUser = document.getElementById('ssh_username').value;
    var sshPass = document.getElementById('ssh_password').value;
    var xhr = new XMLHttpRequest();
    var url = 'includes/php_ajax/Check_Connection.php?check_ssh' +
              '&host=' + encodeURIComponent(sshHost) +
              '&port=' + encodeURIComponent(sshPort) +
              '&user=' + encodeURIComponent(sshUser) +
              '&pass=' + encodeURIComponent(sshPass);
    xhr.open('GET', url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
			loading("hide");
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        show_message(response.message);
                    } else {
                        show_message("Lỗi: " + response.message);
                    }
                } catch (e) {
                    show_message("Lỗi phân tích cú pháp phản hồi: " + e.message);
                }
            } else {
                show_message("Lỗi kết nối: trạng thái HTTP " + xhr.status);
            }
        }
    };
    xhr.send();
}



function test_key_difyAI() {
	loading('show');
    var dify_ai_key = document.getElementById('dify_ai_key').value;
    const url = 'https://api.dify.ai/v1/chat-messages';
    const headers = {
        'Authorization': 'Bearer ' + dify_ai_key,
        'Content-Type': 'application/json',
    };
    const data = {
        "inputs": {},
        "query": "Chào bạn",
        "response_mode": "blocking",
        "user": "VBot_Assistant_TestKey",
    };
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    Object.keys(headers).forEach(key => {
        xhr.setRequestHeader(key, headers[key]);
    });
    xhr.onload = function () {
        try {
            if (xhr.status === 200) {
                try {
                    const responseData = JSON.parse(xhr.responseText);
                    const answerDifyAI = responseData.answer || null;
                    if (answerDifyAI) {
                        loading('hide');
						show_message("<font color='green'><center>Kiểm tra thành công</center></font><br/>-Dữ liệu trả về: "+answerDifyAI);
                    } else {
						loading('hide');
                        show_message("Không có dữ liệu phản hồi từ Dify AI");
                    }
                } catch (e) {
					loading('hide');
                    show_message("[Dify AI] Lỗi phân tích dữ liệu JSON: " + e);
                }
            } else {
				loading('hide');
                show_message("[Dify AI] Lỗi HTTP (" + xhr.status + "): " + xhr.statusText);
            }
        } catch (e) {
			loading('hide');
            show_message("[Dify AI] Lỗi xử lý phản hồi: " + e);
        }
    };
    xhr.onerror = function () {
		loading('hide');
        show_message("[Dify AI] Lỗi kết nối HTTP, vui lòng kiểm tra lại kết nối mạng.");
    };
    try {
        xhr.send(JSON.stringify(data));
    } catch (e) {
		loading('hide');
        show_message("[Dify AI] Lỗi gửi yêu cầu HTTP: " + e);
    }
}



// Test key ChatGPT
function test_key_ChatGPT(text) {
	loading("show");
    var apiKey = document.getElementById('chat_gpt_key').value;
    var url_API = document.getElementById('chat_gpt_url_api').value;
    //const url_API = "https://api.openai.com/v1/chat/completions";
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url_API, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Authorization", 'Bearer ' + apiKey);
    const body = JSON.stringify({
        model: "gpt-3.5-turbo",
        messages: [
            { role: "system", content: "Bạn là một trợ lý thông minh" },
            { role: "user", content: text }
        ]
    });
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
				loading("hide");
                const response = JSON.parse(xhr.responseText);
                const reply = response.choices[0].message.content;
				show_message('<center>Kiểm Tra API KEY Thành Công</center><br/>Phản hồi: <font color=green>' + reply + '</font>');
            } else {
				loading("hide");
				show_message('Lỗi xảy ra:<br/><font color=red>' + xhr.responseText + '</font>');
            }
        }
    };
    xhr.send(body);
}

    //Tets, kiểm tra Key Gemini
    function test_key_Gemini(text) {
        loading("show");
        var apiKey = document.getElementById('google_gemini_key').value;
        var url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' + apiKey;
        var payload = {
            contents: [{
                parts: [{
                    text: text
                }]
            }]
        };
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                loading("hide");
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    var candidates = response.candidates;
                    if (candidates.length > 0) {
                        var contentText = candidates[0].content.parts[0].text;
                        show_message('<center>Kiểm Tra API KEY Thành Công</center><br/>Phản hồi: <font color=green>' + contentText + '</font>');
                    }
                } else {
                    var response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        show_message('Lỗi xảy ra: <font color=red>' + response.error.message + '</font>');
                    } else {
                        show_message('Có Lỗi Xảy Ra: ' + xhr.status);
                    }
                }
            }
        };
        xhr.send(JSON.stringify(payload));
    }
    //tách lấy tên file và đuôi từ đường dẫn path
    function getFileNameFromPath(filePath) {
        // Tách đường dẫn bằng dấu '/'
        var parts = filePath.split('/');
        // Lấy phần cuối cùng của mảng, đó là tên tệp với phần mở rộng
        var fileNameWithExtension = parts.pop();
        return fileNameWithExtension;
    }

    //Hiển thị các bài hát trong thư mục Local
    function list_audio_show_path(id_path_music) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'includes/php_ajax/Show_file_path.php?' + encodeURIComponent(id_path_music), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                //console.log(xhr.responseText)
                var data = JSON.parse(xhr.responseText);
                if (id_path_music === "scan_Music_Local") {
                    var tableBody = document.getElementById('show_mp3_music_local').getElementsByTagName('tbody')[0];
                    tableBody.innerHTML = '';
                    var tableHead = document.querySelector('#show_mp3_music_local thead');
                    tableHead.innerHTML =
                        '<tr>' +
                        '<th colspan="3"><center>Danh Sách Bài Hát Có Trong Thư Mục Music_Local</center></th>' +
                        '</tr>' +
                        '<tr>' +
                        '<th><center>STT</center></th>' +
                        '<th><center>Tên File</center></th>' +
                        '<th><center>Hành Động</center></th>' +
                        '</tr>';
                    data.forEach(function(file, index) {
                        var fileName = getFileNameFromPath(file);
                        var rowContent =
                            '<tr>' +
                            '<td style="text-align: center; vertical-align: middle;"><center>' + (index + 1) + '</center></td>' +
                            '<td><input readonly class="form-control border-primary" type="text" name="file_name_music_local' + index + '" value="' + fileName + '"></td>' +
                            '<td style="text-align: center; vertical-align: middle;"><center>' +
                            '<button type="button" class="btn btn-danger" title="Xóa file: ' + fileName + '" onclick="deleteFile(\'' + file + '\', \'scan_Music_Local\')"><i class="bi bi-trash"></i></button>' +
                            ' <button type="button" class="btn btn-success" title="Tải Xuống file: ' + fileName + '" onclick="downloadFile(\'' + file + '\')"><i class="bi bi-download"></i></button>' +
                            '</center></td>' +
                            '</tr>';
                        tableBody.insertAdjacentHTML('beforeend', rowContent);
                    });
                }else if (id_path_music === "scan_Audio_Startup") {
                    var tableBody = document.getElementById('show_mp3_sound_welcome').getElementsByTagName('tbody')[0];
                    tableBody.innerHTML = ''; 
                    var tableHead = document.querySelector('#show_mp3_sound_welcome thead');
                    tableHead.innerHTML =
                        '<tr>' +
                        '<th colspan="3"><center>Danh Sách Âm Thanh Có Trong Thư Mục Welcome</center></th>' +
                        '</tr>' +
                        '<tr>' +
                        '<th><center>STT</center></th>' +
                        '<th><center>Tên File</center></th>' +
                        '<th><center>Hành Động</center></th>' +
                        '</tr>';
                    data.forEach(function(file, index) {
                        var fileName = getFileNameFromPath(file);
                        var rowContent =
                            '<tr>' +
                            '<td style="text-align: center; vertical-align: middle;"><center>' + (index + 1) + '</center></td>' +
                            '<td><input readonly class="form-control border-primary" type="text" name="file_name_music_local' + index + '" value="' + fileName + '"></td>' +
                            '<td style="text-align: center; vertical-align: middle;"><center>' +
                            '<button type="button" class="btn btn-danger" title="Xóa file: ' + fileName + '" onclick="deleteFile(\'' + file + '\', \'scan_Audio_Startup\')"><i class="bi bi-trash"></i></button>' +
                            ' <button type="button" class="btn btn-success" title="Tải Xuống file: ' + fileName + '" onclick="downloadFile(\'' + file + '\')"><i class="bi bi-download"></i></button>' +
                            '</center></td>' +
                            '</tr>';
                        tableBody.insertAdjacentHTML('beforeend', rowContent);
                    });
				}
            }
        };
        xhr.send();
    }

    // Cập nhật giá trị của thuộc tính onclick của nút sound_welcome_file_path vào nút nghe thử play_Audio_Welcome
    function updateButton_Audio_Welcome() {
        const selectElement = document.getElementById('sound_welcome_file_path');
        const filePath = '<?php echo $VBot_Offline; ?>'+selectElement.value;
        const button = document.getElementById('play_Audio_Welcome');
        if (button) {
            button.onclick = function() {
                playAudio(filePath);
            };
        }
    }
    // Đặt sự kiện khi DOM đã được tải hoàn toàn và cập nhật giá tị khi select thay đổi vào  play_Audio_Welcome
    document.addEventListener('DOMContentLoaded', function() {
        updateButton_Audio_Welcome(); // Cập nhật thuộc tính onclick khi DOM tải xong
        document.getElementById('sound_welcome_file_path').addEventListener('change', updateButton_Audio_Welcome); // Cập nhật khi giá trị thay đổi
    });

//Check key picovoice
function test_key_Picovoice() {
	loading("show");
    var token = document.getElementById('hotword_engine_key').value;
    var lang = document.getElementById('select_hotword_lang').value;
    var xhr = new XMLHttpRequest();
    var url = 'includes/php_ajax/Check_Connection.php?check_key_picovoice&key='+token+'&lang='+lang;
    xhr.open('GET', url);
    xhr.responseType = 'json';
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
			loading("hide");
            var data = xhr.response;
            if (data.success) {
                show_message('<font color=green><center>' +data.message+'</center><br/>- Ngôn ngữ kiểm tra: <b>'+data.language_name+'</b><br/>- File Hotword kiểm tra ngẫu nhiên trong thư mục '+data.lang+': <b>'+data.hotword_random_test+'</b><br/>- File thư viện Procupine: <b>'+data.model_file_path+'</b></font>');
            } else {
                show_message('<font color=red><center>Lỗi</center> ' +data.message+'</font>');
            }
        } else {
			loading("hide");
            show_message('Lỗi: ' +xhr.statusText);
        }
    };
    xhr.onerror = function() {
		loading("hide");
        show_message('Yêu cầu thất bại');
    };
    xhr.send();
}

//scan_audio_devices('scan_Mic')
//scan mic hoặc audio out
function scan_audio_devices(device_name) {
    loading("show");
    var xhr = new XMLHttpRequest();
    var url = 'includes/php_ajax/Scanner.php?'+device_name;
    xhr.open('GET', url, true);
    xhr.responseType = 'json';
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
			loading("hide");
            var data = xhr.response;
            if (data && data.success) {
                //console.log(data.message);
                //console.log(data.devices);
			if (device_name === "scan_mic"){
				var container = document.getElementById('mic_scanner');
                var tableHTML = '<table class="table table-bordered border-primary">';
                tableHTML += '<thead><tr><th colspan="3" style="text-align: center; vertical-align: middle;"><font color=green>'+data.message+'</font></th></tr><tr><th style="text-align: center; vertical-align: middle;">ID Mic</th><th style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th><th style="text-align: center; vertical-align: middle;">Hành Động</th></tr></thead>';
                tableHTML += '<tbody>';
                data.devices.forEach(function(device) {
                    tableHTML += '<tr><td style="text-align: center; vertical-align: middle;">' + device.ID + '</td><td style="vertical-align: middle;">' + (device.Tên || '') + '</td><td style="text-align: center; vertical-align: middle;"><button type="button" class="btn btn-primary rounded-pill" onclick="selectDevice_MIC(' + device.ID + ')">Chọn</button></td></td></tr>';
                });
                tableHTML += '</tbody></table>';
                if (container) {
					showMessagePHP(data.message, 4);
                    container.innerHTML = tableHTML;
                } else {
                    show_message('Không tìm thấy thẻ div với id: '+container);
                }
			}
			//Hiển thị thông tin khi scan_alsamixer 
			else if (device_name === "scan_alsamixer"){
				var container = document.getElementById('alsamixer_scan');
                var tableHTML = '<table class="table table-bordered border-primary">';
                tableHTML += '<thead><tr><th colspan="6" style="text-align: center; vertical-align: middle;"><font color=green>'+data.message+'</font></th></tr><tr><th style="text-align: center; vertical-align: middle;">ID Speaker</th><th style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th><th style="text-align: center; vertical-align: middle;">Khả Năng</th><th style="text-align: center; vertical-align: middle;">Kênh Phát</th><th style="text-align: center; vertical-align: middle;">Thông Số</th><th style="text-align: center; vertical-align: middle;">Hành Động</th></tr></thead>';
                tableHTML += '<tbody>';
                data.devices.forEach(function(device) {
                    tableHTML += '<tr><td style="text-align: center; vertical-align: middle;">' + device.id + '</td><td style="vertical-align: middle;">' + (device.name || '') + '</td><td style="vertical-align: middle;">' + (device.capabilities || '') + '</td><td style="vertical-align: middle;">' + (device.playback_channels || '') + '</td><td style="vertical-align: middle;">' + (device.values.length > 0 ? device.values.map(value => (value.channel || '') + ' ' + (value.details || '')).join('<br>') : '') + '</td><td style="text-align: center; vertical-align: middle;"><button type="button" class="btn btn-primary rounded-pill" onclick="selectDevice_Alsamixer(\''+device.name+'\')">Chọn</button></td></td></tr>';
                });
                tableHTML += '</tbody></table>';
                // Đẩy nội dung bảng vào thẻ div
                if (container) {
					showMessagePHP(data.message, 4);
                    container.innerHTML = tableHTML;
                } else {
                    show_message('Không tìm thấy thẻ div với id: '+container);
                }
			}
            } else if (data) {
                show_message('Lỗi: ' + data.message);
            } else {
                show_message('Lỗi không xác định. Vui lòng thử lại sau.');
            }
        } else {
			loading("hide");
            show_message('Lỗi: ' + xhr.statusText);
        }
    };
    xhr.onerror = function() {
        loading("hide");
        show_message('Yêu cầu thất bại. Vui lòng kiểm tra kết nối mạng.');
    };
    xhr.send();
}

//Chọn Mic để đẩy vào value của thẻ input Mic
function selectDevice_MIC(id) {
    var micInput = document.getElementById('mic_id');
    if (micInput) {
        micInput.value = id;
		showMessagePHP('Đã chọn Mic có id là: '+id, 3);
    } else {
        show_message('Không tìm thấy thẻ input với id "mic_id".', 3);
    }
}

//Chọn Speaker để đẩy vào value của thẻ Tên thiết bị (alsamixer):
function selectDevice_Alsamixer(name) {
    var alsamixerInput = document.getElementById('alsamixer_name');
    if (alsamixerInput) {
        alsamixerInput.value = name;
		showMessagePHP('Đã chọn thiết bị trong alsamixer có tên là là: '+name, 3);
    } else {
        show_message('Không tìm thấy thẻ input với id "alsamixer_name".', 3);
    }
}

//Play nghe thử âm thanh tts mẫu của google CLOUD
function play_tts_sample_gcloud(){
	loading('show');
    const selectElement = document.getElementById('tts_ggcloud_voice_name');
    if (selectElement) {
    if (selectElement.value) {
       playAudio('https://cloud.google.com/static/text-to-speech/docs/audio/'+selectElement.value+'.wav');
    }else{
		show_message('Cần chọn 1 giọng đọc để nghe thử');
	}
    }
	else{
		showMessagePHP('Không tìm thấy dữu liệu thẻ select với id=tts_ggcloud_voice_name', 3);
	}
	loading('hide');
}

</script>
<script>
    //Cập nhật bảng mã màu vào thẻ input
    // Thiết lập giá trị ban đầu cho các thẻ input khi tải trang
    window.onload = function() {
        // Thiết lập màu cho thẻ đầu tiên
        setColorPickerValue('color_led_think', 'led_think');
        // Thiết lập màu cho thẻ thứ hai
        setColorPickerValue('color_led_mutex', 'led_mute');
    };
    // Hàm thiết lập màu cho các thẻ colorPicker dựa trên giá trị colorCodeInput
    function setColorPickerValue(colorPickerId, colorCodeInputId) {
        const initialColor = document.getElementById(colorCodeInputId).value;
        document.getElementById(colorPickerId).value = '#' + initialColor;
    }
    // Hàm cập nhật mã màu vào thẻ input
    function updateColorCode_input(colorPickerId, colorCodeInputId) {
        // Lấy mã màu từ input color và bỏ dấu '#' ở đầu
        const selectedColor = document.getElementById(colorPickerId).value.substring(1);
        // Cập nhật giá trị của thẻ input với mã màu đã chọn (không có '#')
        document.getElementById(colorCodeInputId).value = selectedColor;
    }
</script>
	
<script>
//Dành cho Test Led 
function test_led(action) {
    if ( <?php echo $Config['smart_config']['led']['active'] ? 'true' : 'false'; ?> === false) {
        show_message("Chế độ sử dụng Led không được kích hoạt");
        return;
    }
    const led_value = document.getElementById(action).value;
    //console.log(led_value);
    //console.log(action);
    let led_action;
    if (action === "led_think") {
        led_action = "think";
    } else if (action === "led_mute") {
        led_action = "mute";
    } else if (action === "led_off") {
        led_action = "off";
    } else {
        show_message("Tham số truyền vào không hợp lệ");
        return;
    }
    loading("show");
    const data = JSON.stringify({
        "type": 2,
        "data": "led",
        "action": led_action,
        "value": led_value
    });
    const xhr = new XMLHttpRequest();
    xhr.withCredentials = true;
    xhr.addEventListener("readystatechange", function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    //show_message(response.message || "Yêu cầu đã được gửi thành công.");
                    showMessagePHP(response.message, 3);
                } else {
                    show_message("Yêu cầu không thành công: " + (response.message || "Lỗi không xác định."));
                }
                loading("hide");
            } else {
                show_message("Có lỗi xảy ra trong quá trình gửi yêu cầu.");
                loading("hide");
            }
        }
    });
    xhr.onerror = function() {
        show_message("Không thể kết nối tới server. Vui lòng kiểm tra kết nối mạng.");
        loading("hide");
    };
    xhr.open("POST", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>");
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.send(data);
}

//Thay đổi giá trị value của BackList.json theo đường dẫn chỉ định 
function changeBacklistValue(path_json, value_type) {
    var url = "includes/php_ajax/Show_file_path.php";
    var params = "delete_data_backlist=1&path=" + path_json + "&value_type=" + value_type;
    var xhr = new XMLHttpRequest();
    xhr.addEventListener("readystatechange", function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        showMessagePHP(response.message, 3);
                        if (path_json === "backlist->tts_zalo->backlist_limit") {
                            getBacklistData('backlist->tts_zalo', 'tts_zalo_backlist_content');
                        }
                        else if (path_json === "backlist->tts_viettel->backlist_limit") {
                            getBacklistData('backlist->tts_viettel', 'tts_viettel_backlist_content');
                        }

                    } else {;
                        show_message("Có lỗi xảy ra khi cập nhật backlist: " + response.message);
                    }
                } catch (e) {
                    show_message("Lỗi phân tích cú pháp JSON: " + e);
                }
            } else {
                show_message("Có lỗi xảy ra khi gửi yêu cầu. Mã lỗi: " + this.status);
            }
        }
    });
    xhr.onerror = function() {
        show_message("Lỗi mạng hoặc không thể kết nối với máy chủ.");
    };
    xhr.open("GET", url + "?" + params, true);
    xhr.send();
}

//Hiển thị dữ liệu BackList.json
function getBacklistData(dataPath, textareaId) {
    var url = "includes/php_ajax/Show_file_path.php?data_backlist";
    var xhr = new XMLHttpRequest();
    xhr.addEventListener("readystatechange", function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        var data = response.data;
                        var pathParts = dataPath.split('->');
                        var currentData = data;
                        // Duyệt qua các phần của path để lấy dữ liệu
                        for (var i = 0; i < pathParts.length; i++) {
                            var part = pathParts[i].trim();
                            currentData = currentData[part];
                        }
                        // Cập nhật nội dung của thẻ textarea
                        var textarea = document.getElementById(textareaId);
                        if (textarea) {
                            textarea.value = JSON.stringify(currentData, null, 4);
                        } else {
                            show_message("Không tìm thấy thẻ textarea với ID: " + textareaId);
                        }
                        showMessagePHP("Dữ liệu đã được tải thành công.", 3);
                    } else {
                        show_message("Có lỗi xảy ra: " + response.message);
                    }
                } catch (e) {
                    show_message("Có lỗi xảy ra khi xử lý phản hồi từ máy chủ: " + e);
                }
            } else {
                show_message("Có lỗi xảy ra khi gửi yêu cầu. Mã lỗi: " + this.status);
            }
        }
    });
    xhr.onerror = function() {
        show_message("Lỗi mạng hoặc không thể kết nối với máy chủ.");
    };
    xhr.open("GET", url, true);
    xhr.send();
}

//Cập nhật đường dẫn path web ui vào thẻ input
function update_webui_link() {
    // Gán giá trị vào input có id là "webui_path"
    const inputField = document.getElementById('webui_path');
    if (inputField) {
		path_web_ui_html = "<?php echo $directory_path; ?>";
        inputField.value = path_web_ui_html
		showMessagePHP("Đã cập nhật đường dẫn path Web UI: "+path_web_ui_html)
    } else {
		show_message('Không tìm thấy input có id "webui_path".');
    }
}

</script>

	
</body>

</html>
