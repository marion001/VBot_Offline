<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
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

if ($Config['backup_upgrade']['config_json']['active'] === true) {
  $directoryPath_Backup_Config = $Config['backup_upgrade']['config_json']['backup_path'];
  if (!is_dir($directoryPath_Backup_Config)) {
    if (mkdir($directoryPath_Backup_Config, 0777, true)) {
      chmod($directoryPath_Backup_Config, 0777);
    }
  }
}

$read_stt_token_google_cloud = null;

//Khôi phục dữ liệu File Config.json
if (isset($_POST['start_recovery_config_json'])) {
  $data_recovery_type = $_POST['start_recovery_config_json'];
  if ($data_recovery_type === "khoi_phuc_tu_tep_he_thong") {
    $start_recovery_config_json = $_POST['backup_config_json_files'];
    if (!empty($start_recovery_config_json)) {
      if (file_exists($start_recovery_config_json)) {
        $command = 'cp ' . escapeshellarg($start_recovery_config_json) .
          ' ' . escapeshellarg($VBot_Offline .
            'Config.json');
        exec($command, $output, $resultCode);
        if ($resultCode === 0) {
          $messages[] = "Đã khôi phục dữ liệu Config.json từ tệp sao lưu trên hệ thống thành công";
        } else {
          $messages[] = "Lỗi xảy ra khi khôi phục dữ liệu tệp Config.json Mã lỗi: " . $resultCode;
        }
      } else {
        $messages[] = "Lỗi: Tệp " . basename($start_recovery_config_json) .
          " không tồn tại trên hệ thống";
      }
    } else {
      $messages[] = "Không có tệp sao lưu Config nào được chọn để khôi phục!";
    }
  } else if ($data_recovery_type === "khoi_phuc_tu_tep_tai_len") {
    $uploadOk = 1;
    if (isset($_FILES["fileToUpload_configjson_restore"])) {
      $targetFile = $VBot_Offline .
        'Config.json';
      $fileName = basename($_FILES["fileToUpload_configjson_restore"]["name"]);
      if (!preg_match('/\.json$/', $fileName) || !preg_match('/^Config/', $fileName)) {
        $messages[] = "- Chỉ chấp nhận tệp .json, dành cho Config.json";
        $uploadOk = 0;
      }
      if ($uploadOk == 0) {
        $messages[] = "- Tệp sao lưu không được tải lên";
      } else {
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

  if ($Config['backup_upgrade']['config_json']['active'] === true) {
    // Lấy ngày và giờ hiện tại
    $dateTime = new DateTime();
    $newFileName = 'Config_' . $dateTime->format('dmY_His') . '.json';
    $destinationFile_Backup_Config = $directoryPath_Backup_Config . '/' . $newFileName;
    if (copy($Config_filePath, $destinationFile_Backup_Config)) {
      chmod($destinationFile_Backup_Config, 0777);
      $files_ConfigJso_BUP = glob($directoryPath_Backup_Config . '/*.json');
      if (count($files_ConfigJso_BUP) > $Config['backup_upgrade']['config_json']['limit_backup_files']) {
        // Sắp xếp các file theo thời gian tạo (cũ nhất trước)
        usort($files_ConfigJso_BUP, function ($a, $b) {
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
  $Config['web_interface']['external']['active'] = isset($_POST['webui_external']) ? true : false;

  #CẬP NHẬT CÁC GIÁ TRỊ TRONG home_assistant
  $Config['home_assistant']['minimum_threshold'] = floatval($_POST['hass_minimum_threshold']);
  $Config['home_assistant']['lowest_to_display_logs'] = floatval($_POST['hass_lowest_to_display_logs']);
  $Config['home_assistant']['time_out'] = intval($_POST['hass_time_out']);
  $Config['home_assistant']['internal_url'] = $_POST['hass_internal_url'];
  $Config['home_assistant']['external_url'] = $_POST['hass_external_url'];
  $Config['home_assistant']['long_token'] = $_POST['hass_long_token'];
  $Config['home_assistant']['active'] = isset($_POST['hass_active']) ? true : false;
  $Config['home_assistant']['custom_commands']['active'] = isset($_POST['hass_custom_commands_active']) ? true : false;
  $Config['home_assistant']['custom_commands']['minimum_threshold'] = floatval($_POST['hass_custom_commands_threshold']);

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

  #CẬP NHẬT GIÁ TRỊ TRONG Speak To Text (STT)speak_to_text:
  $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] = $_POST['stt_select'];
  $Config['smart_config']['smart_wakeup']['speak_to_text']['duration_recording'] = intval($_POST['duration_recording']);

  #Cập nhật Chế Độ Hội Thoại/Trò Chuyện Liên Tục conversation_mode:
  $Config['smart_config']['smart_wakeup']['conversation_mode'] = isset($_POST['conversation_mode']) ? true : false;

  #Cập nhật chế độ đa lệnh trong 1 câu
  $Config['multiple_command']['active'] = isset($_POST['multiple_command']) ? true : false;
  $Config['multiple_command']['continue_listening_after_commands'] = isset($_POST['continue_listening_after_commands']) ? true : false;

  #CẬP NHẬT CHẾ ĐỘ Hotword Engine Picovoice KEY hotword_engine:
  $Config['smart_config']['smart_wakeup']['hotword_engine']['key'] = $_POST['hotword_engine_key'];
  $Config['smart_config']['smart_wakeup']['hotword']['lang'] = $_POST['select_hotword_lang'];
  #$Config['smart_config']['smart_wakeup']['hotword']['select_wakeup'] = $_POST['hotword_select_wakeup'];
  $Config['smart_config']['smart_wakeup']['hotword']['select_wakeup'] = ($_POST['hotword_select_wakeup'] === "null") ? null : $_POST['hotword_select_wakeup'];
  $Config['smart_config']['smart_wakeup']['hotword']['continue_running_if_hotword_initialization_fails'] = isset($_POST['continue_running_if_hotword_initialization_fails']) ? true : false;

  #CẬP NHẬT CHẾ ĐỘ Text To Speak (TTS) text_to_speak:
  $Config['smart_config']['smart_answer']['cache_tts']['active'] = isset($_POST['cache_tts']) ? true : false;
  $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] = $_POST['tts_select'];
  $Config['smart_config']['smart_answer']['text_to_speak']['directory_tts'] = $_POST['directory_tts'];
  $Config['smart_config']['smart_answer']['text_to_speak']['clean_cache_tts_max_file'] = intval($_POST['clean_cache_tts_max_file']);

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
  $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['quality'] = intval($_POST['tts_default_quality']);
  $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['voice_name'] = intval($_POST['tts_default_voice_name']);
  $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['authentication_zai_did'] = $_POST['authentication_zai_did'];
  $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['expires_zai_did'] = $_POST['expires_zai_did'];

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
  $apiKeys_ZALO_TTS = array_filter(array_map(function ($key_tts_ZL) {
    return str_replace("\r", '', $key_tts_ZL);
  }, $apiKeys_ZALO_TTS), function ($key_tts_ZL) {
    return !empty($key_tts_ZL);
  });
  $apiKeys_ZALO_TTS = array_values($apiKeys_ZALO_TTS);
  $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['api_key'] = $apiKeys_ZALO_TTS;
  $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['speaking_speed'] = floatval($_POST['tts_zalo_speaking_speed']);
  $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] = intval($_POST['tts_zalo_voice_name']);

  #Cập nhật TTS Viettel
  $apiKeys_VIETTEL_TTS = array_map('trim', explode("\n", $_POST['tts_viettel_api_key']));
  $apiKeys_VIETTEL_TTS = array_filter(array_map(function ($key_tts_VT) {
    return str_replace("\r", '', $key_tts_VT);
  }, $apiKeys_VIETTEL_TTS), function ($key_tts_VT) {
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
  $Config['smart_config']['led']['brightness'] = intval(min(100, max(0, intval($_POST['led_brightness']))) * 255 / 100);
  $Config['smart_config']['led']['led_invert'] = isset($_POST['led_invert']) ? true : false;
  $Config['smart_config']['led']['remember_last_brightness'] = isset($_POST['remember_last_brightness']) ? true : false;
  $Config['smart_config']['led']['led_starting_up'] = isset($_POST['led_starting_up']) ? true : false;
  $Config['smart_config']['led']['effect']['led_think'] = $_POST['led_think'];
  $Config['smart_config']['led']['effect']['led_mute'] = $_POST['led_mute'];

  #cẬP NHẬT CẤU HÌNH Ưu tiên nguồn phát/tìm kiếm Media:
  $music_source_priority_1 = isset($_POST['music_source_priority1']) ? $_POST['music_source_priority1'] : '';
  $music_source_priority_2 = isset($_POST['music_source_priority2']) ? $_POST['music_source_priority2'] : '';
  $music_source_priority_3 = isset($_POST['music_source_priority3']) ? $_POST['music_source_priority3'] : '';
  $music_source_priority_4 = isset($_POST['music_source_priority4']) ? $_POST['music_source_priority4'] : '';
  $music_source_priority_5 = isset($_POST['music_source_priority5']) ? $_POST['music_source_priority5'] : '';
  $Config['media_player']['prioritize_music_source'] = [$music_source_priority_1, $music_source_priority_2, $music_source_priority_3, $music_source_priority_4, $music_source_priority_5];

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

  #Cập nhật giá trị đọc truyện, kể truyện
  $Config['media_player']['podcast']['active'] = isset($_POST['podcast_active']) ? true : false;
  $Config['media_player']['podcast']['allows_priority_use_of_virtual_assistants'] = isset($_POST['podcast_virtual_assistants_active']) ? true : false;

  #Cập nhật giá trị Dev custom music
  $Config['media_player']['dev_custom_music']['active'] = isset($_POST['dev_custom_music_active']) ? true : false;

  #cẬP NHẬT GIÁ TRỊ youtube
  $Config['media_player']['youtube']['google_apis_key'] = $_POST['youtube_google_apis_key'];
  $Config['media_player']['youtube']['active'] = isset($_POST['youtube_active']) ? true : false;

  #Cập nhật giá trị Zingmp3 xem có kích hoạt hay không
  $Config['media_player']['zing_mp3']['active'] = isset($_POST['zing_mp3_active']) ? true : false;

  #Cập nhật giá trị nhaccuatui xem có được kích hoạt hay không
  $Config['media_player']['nhaccuatui']['active'] = isset($_POST['nhaccuatui_active']) ? true : false;

  #cẬP NHẬT GIÁ TRỊ Trợ Lý Ảo/Assistant:
  $Config['virtual_assistant']['google_gemini']['api_key'] = $_POST['google_gemini_key'];
  $Config['virtual_assistant']['google_gemini']['model_name'] = $_POST['gemini_models_name'];
  $Config['virtual_assistant']['google_gemini']['api_version'] = $_POST['gemini_api_version'];
  $Config['virtual_assistant']['google_gemini']['active'] = isset($_POST['google_gemini_active']) ? true : false;
  $Config['virtual_assistant']['google_gemini']['time_out'] = intval($_POST['google_gemini_time_out']);

  #Cập nhật giá trị trợ lý ảo Default Assistant
  $Config['virtual_assistant']['default_assistant']['time_out'] = intval($_POST['default_assistant_time_out']);
  $Config['virtual_assistant']['default_assistant']['active'] = isset($_POST['default_assistant_active']) ? true : false;
  $Config['virtual_assistant']['default_assistant']['convert_audio_to_text']['used_for_chatbox'] = isset($_POST['default_assistant_convert_audio_to_text_used_for_chatbox']) ? true : false;
  $Config['virtual_assistant']['default_assistant']['convert_audio_to_text']['used_for_display_and_logs'] = isset($_POST['default_assistant_convert_audio_to_text_used_for_display_and_logs']) ? true : false;

  #Cập nhật trợ lý ảo Olli
  $Config['virtual_assistant']['olli']['active'] = isset($_POST['olli_assistant_active']) ? true : false;
  $Config['virtual_assistant']['olli']['time_out'] = intval($_POST['olli_assistant_time_out']);
  $Config['virtual_assistant']['olli']['voice_name'] = $_POST['olli_assistant_voice_name'];
  $Config['virtual_assistant']['olli']['password'] = $_POST['olli_assistant_password'];
  $Config['virtual_assistant']['olli']['convert_audio_to_text']['used_for_chatbox'] = isset($_POST['olli_convert_audio_to_text_used_for_chatbox']) ? true : false;
  $Config['virtual_assistant']['olli']['convert_audio_to_text']['used_for_display_and_logs'] = isset($_POST['olli_convert_audio_to_text_used_for_display_and_logs']) ? true : false;
  $input_olli_assistant_username = trim($_POST['olli_assistant_username']);
  if (filter_var($input_olli_assistant_username, FILTER_VALIDATE_EMAIL)) {
    $Config['virtual_assistant']['olli']['username'] = $input_olli_assistant_username;
  } elseif (preg_match('/^\d{9,10}$/', $input_olli_assistant_username)) {
    if (strlen($input_olli_assistant_username) == 10 && $input_olli_assistant_username[0] == '0') {
      $input_olli_assistant_username = substr($input_olli_assistant_username, 1);
    }
    $Config['virtual_assistant']['olli']['username'] = '+84' . $input_olli_assistant_username;
  } else {
    $Config['virtual_assistant']['olli']['username'] = trim($_POST['olli_assistant_username']);
  }

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

  #Cập nhật Streaming
  $Config['api']['streaming_server']['active'] = isset($_POST['streming_active']) ? true : false;
  $Config['api']['streaming_server']['connection_protocol'] = $_POST['streaming_server_connection_protocol'];

  #Cập nhật UDP Streaming
  $Config['api']['streaming_server']['protocol']['udp_sock']['port'] = intval($_POST['port_server_udp_streaming_audio']);
  $Config['api']['streaming_server']['protocol']['udp_sock']['maximum_recording_time'] = intval($_POST['udp_maximum_recording_time']);
  $Config['api']['streaming_server']['protocol']['udp_sock']['maximum_client_connected'] = intval($_POST['udp_maximum_client_connected']);
  $Config['api']['streaming_server']['protocol']['udp_sock']['time_remove_inactive_clients'] = intval($_POST['udp_time_remove_inactive_clients']);
  $Config['api']['streaming_server']['protocol']['udp_sock']['source_stt'] = $_POST['udp_source_stt'];
  $Config['api']['streaming_server']['protocol']['udp_sock']['working_mode'] = $_POST['udp_working_mode'];
  $Config['api']['streaming_server']['protocol']['udp_sock']['select_wakeup'] = $_POST['udp_select_wakeup'];
  $Config['api']['streaming_server']['protocol']['udp_sock']['data_client_name'] = $_POST['udp_server_data_client_name'];
  $Config['api']['streaming_server']['protocol']['udp_sock']['client_conversation_mode'] = isset($_POST['udp_sock_client_conversation_mode']) ? true : false;
  $Config['api']['streaming_server']['protocol']['udp_sock']['music_playback_on_client'] = isset($_POST['udp_sock_music_playback_on_client']) ? true : false;

  #cẬP NHẬT Ưu tiên trợ lý ảo prioritize_virtual_assistants:
  $virtual_assistant_priority_1 = isset($_POST['virtual_assistant_priority1']) ? $_POST['virtual_assistant_priority1'] : '';
  $virtual_assistant_priority_2 = isset($_POST['virtual_assistant_priority2']) ? $_POST['virtual_assistant_priority2'] : '';
  $virtual_assistant_priority_3 = isset($_POST['virtual_assistant_priority3']) ? $_POST['virtual_assistant_priority3'] : '';
  $virtual_assistant_priority_4 = isset($_POST['virtual_assistant_priority4']) ? $_POST['virtual_assistant_priority4'] : '';
  $virtual_assistant_priority_5 = isset($_POST['virtual_assistant_priority5']) ? $_POST['virtual_assistant_priority5'] : '';
  $virtual_assistant_priority_6 = isset($_POST['virtual_assistant_priority6']) ? $_POST['virtual_assistant_priority6'] : '';
  $virtual_assistant_priority_7 = isset($_POST['virtual_assistant_priority7']) ? $_POST['virtual_assistant_priority7'] : '';
  $Config['virtual_assistant']['prioritize_virtual_assistants'] = [$virtual_assistant_priority_1, $virtual_assistant_priority_2, $virtual_assistant_priority_3, $virtual_assistant_priority_4, $virtual_assistant_priority_5, $virtual_assistant_priority_6, $virtual_assistant_priority_7];

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

  #Câu Phản Hồi
  $Config['smart_config']['smart_wakeup']['wakeup_reply']['active'] = isset($_POST['wakeup_reply_active']) ? true : false;

  #broadlink
  $Config['broadlink']['remote']['active'] = isset($_POST['broadlink_remote_active']) ? true : false;
  $Config['broadlink']['remote']['minimum_threshold'] = floatval($_POST['broadlink_remote_minimum_threshold']);

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

  #Cập Nhật Nút Nhấn Encoder Rotary
  $Config['smart_config']['button_active']['encoder_rotary']['active'] = isset($_POST['encoder_rotary_active']) ? true : false;
  $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['active'] = isset($_POST['ncoder_rotary_long_press_active']) ? true : false;
  $Config['smart_config']['button_active']['encoder_rotary']['rotating_show_logs'] = isset($_POST['encoder_rotating_show_logs']) ? true : false;
  $Config['smart_config']['button_active']['encoder_rotary']['gpio_clk'] = intval($_POST['encoder_rotary_gpio_clk']);
  $Config['smart_config']['button_active']['encoder_rotary']['gpio_dt'] = intval($_POST['encoder_rotary_gpio_dt']);
  $Config['smart_config']['button_active']['encoder_rotary']['gpio_sw'] = intval($_POST['encoder_rotary_gpio_ws']);
  $Config['smart_config']['button_active']['encoder_rotary']['rotating_step'] = intval($_POST['encoder_rotary_gpio_step']);
  $Config['smart_config']['button_active']['encoder_rotary']['bounce_time_gpio_sw'] = intval($_POST['encoder_rotary_bounce_time_sw']);
  $Config['smart_config']['button_active']['encoder_rotary']['action_gpio_sw'] = $_POST['encoder_rotary_action_gpio_sw'];
  $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['duration'] = intval($_POST['encoder_rotary_long_press_duration']);
  $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] = $_POST['encoder_rotary_long_press_action_gpio_sw'];

  #Cập nhật Custom Skill active
  $Config['developer_customization']['active'] = isset($_POST['developer_customization_active']) ? true : false;
  $Config['developer_customization']['if_custom_skill_can_not_handle']['vbot_processing'] = isset($_POST['developer_customization_vbot_processing']) ? true : false;

  #lệnh điều khiển hệ thống System
  $Config['voice_command_system']['active'] = isset($_POST['voice_command_system_active']) ? true : false;
  $Config['voice_command_system']['minimum_result_threshold'] = floatval($_POST['voice_command_system_threshold']);

  #Cập Nhật STT Google Cloud V2
  $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['recognizer_id'] = $_POST['stt_ggcloud_v2_recognizer_id'];
  $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['time_out'] = intval($_POST['stt_ggcloud_v2_time_out']);
  $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['stt_ggcloud_v2']['model'] = $_POST['stt_ggcloud_v2_model'];

  #Cập nhật lịch, lời nhắc, thông báo
  $Config['schedule']['active'] = isset($_POST['schedule_active']) ? true : false;
  #Cập nhật xử lý lỗi
  $Config['smart_config']['auto_restart_program_error'] = isset($_POST['auto_restart_program_error']) ? true : false;
  $Config['smart_config']['fix_time_sync_error'] = isset($_POST['fix_time_sync_error']) ? true : false;

  #Cập nhật XiaoZhi AI
  $Config['xiaozhi']['active'] = isset($_POST['xiaozhi_ai_active']) ? true : false;
  $Config['xiaozhi']['mcp_system_control'] = isset($_POST['xiaozhi_ai_mcp_system']) ? true : false;
  $Config['xiaozhi']['start_the_protocol'] = $_POST['xiaozhi_start_the_protocol'];
  $Config['xiaozhi']['tts_time_out'] = intval($_POST['xiaozhi_tts_time_out']);
  $Config['xiaozhi']['reconnection_timeout'] = intval($_POST['xiaozhi_reconnection_timeout']);
  $Config['xiaozhi']['tts_stream_silence_time'] = intval($_POST['xiaozhi_tts_stream_silence_time']);
  $Config['xiaozhi']['time_out_output_stream'] = floatval($_POST['xiaozhi_time_out_output_stream']);
  $Config['xiaozhi']['system_options']['network']['ota_version_url'] = rtrim(trim($_POST['xiaozhi_ota_version_url']), '/') . '/';
  #Cập nhật chế độ chạy toàn bộ chương trình
  $Config['launch_source'] = !empty($_POST['launch_source']) ? $_POST['launch_source'] : 'VBot_Assistant';

  ##############################################
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
  // Cập nhật newspaper
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
  if ($_POST['stt_select'] === "stt_ggcloud") {

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
  } else if ($_POST['stt_select'] === "stt_ggcloud_v2") {

    #Cập nhật json stt Google Cloud V2
    $json_data_goolge_cloud_stt = $_POST['stt_ggcloud_v2_json_file_token'];
    json_decode($json_data_goolge_cloud_stt);
    if (json_last_error() === JSON_ERROR_NONE) {
      // Nếu dữ liệu là null hoặc rỗng, thay thế bằng {}
      if (empty($json_data_goolge_cloud_stt) || $json_data_goolge_cloud_stt === null) {
        $json_data_goolge_cloud_stt = '{}';
      }
      $json_data_goolge_cloud_stt = trim($json_data_goolge_cloud_stt);
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

  #Đồng thời Restart VBot nếu được nhấn
  if ($_POST['all_config_save'] === 'and_restart_VBot') {
    $CMD = "systemctl --user restart VBot_Offline.service";
    $connection = ssh2_connect($ssh_host, $ssh_port);
    if ($connection) {
      if (@ssh2_auth_password($connection, $ssh_user, $ssh_password)) {
        $stream = ssh2_exec($connection, $CMD);
        stream_set_blocking($stream, true);
        $output = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
        $messages[] = 'Đang Khởi Động Lại Chương Trình VBot';
      } else {
        $messages[] = 'Xác thực SSH không thành công.';
      }
    } else {
      $messages[] = 'Không thể kết nối tới máy chủ SSH';
    }
  }
}

#########################
#Lưu các giá trị cấu hình chi tiết trong hotword Snowboy
if (isset($_POST['save_hotword_snowboy'])) {
  $updatedConfig = [];
  foreach ($_POST as $key => $value) {
    if (strpos($key, 'snowboy_file_name_') === 0) {
      $index = str_replace('snowboy_file_name_', '', $key);
      $active_key = 'snowboy_active_' . $index;
      $sensitive_key = 'snowboy_sensitive_' . $index;
      $active = isset($_POST[$active_key]) && $_POST[$active_key] === 'on';
      $file_name = isset($_POST[$key]) ? $_POST[$key] : '';
      #$sensitive = isset($_POST[$sensitive_key]) ? floatval($_POST[$sensitive_key]) : 0.5;
	  $sensitive = (isset($_POST[$sensitive_key]) && $_POST[$sensitive_key] !== '') ? floatval($_POST[$sensitive_key]) : 0.5;
      if ($file_name !== '') {
        $updatedConfig[] = [
          "active" => $active,
          "file_name" => $file_name,
          "sensitive" => $sensitive
        ];
      }
    }
  }
  $Config['smart_config']['smart_wakeup']['hotword']['snowboy'] = $updatedConfig;
  file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  $messages[] = 'Cập nhật các giá trị trong Hotword Snowboy thành công';
}

#Lưu các giá trị cấu hình chi tiết trong hotword Picovoice/Procupine
if (isset($_POST['save_hotword_theo_lang'])) {
  $lang = $_POST['lang_hotword_get'];
  $Lib_modelFilePath = $_POST['select_file_lib_pv'];
  $updatedConfig = [];
  if (empty($Lib_modelFilePath)) {
    $messages[] = "Lỗi, Cần chọn file thư viện Hotword .pv";
  } else {
    // Kiểm tra giá trị của biến $lang
    if ($lang !== 'eng' && $lang !== 'vi') {
      $messages[] = "Thất bại, Giá trị ngôn ngữ không có hoặc không phải là 'eng' hay 'vi'";
    } else {
      foreach ($_POST as $key => $value) {
        if (strpos($key, 'file_name_') === 0) {
          $index = str_replace('file_name_', '', $key);
          $active_key = 'active_' . $index;
          $sensitive_key = 'sensitive_' . $index;
          $active = isset($_POST[$active_key]) && $_POST[$active_key] === 'on';
          $file_name = isset($_POST[$key]) ? $_POST[$key] : '';
          #$sensitive = isset($_POST[$sensitive_key]) ? floatval($_POST[$sensitive_key]) : 0.5;
		  $sensitive = (isset($_POST[$sensitive_key]) && $_POST[$sensitive_key] !== '') ? floatval($_POST[$sensitive_key]) : 0.5;
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
      $messages[] = 'Cập nhật các giá trị trong Hotword Picovoice/Procupine thành công';
    }
  }
}

#Lưu các giá trị trong câu phản hồi
if (isset($_POST['save_config_wakeup_reply'])) {
  $newSoundFileList = [];
  // Tìm tất cả các key có định dạng save_wakeup_reply_file_name_#
  foreach ($_POST as $key => $value) {
    if (strpos($key, 'save_wakeup_reply_file_name_') === 0) {
      $index = str_replace('save_wakeup_reply_file_name_', '', $key);
      $fileName = $_POST['save_wakeup_reply_file_name_' . $index];
      $isActive = isset($_POST['save_wakeup_reply_active_' . $index]) ? true : false;
      $newSoundFileList[] = [
        'file_name' => $fileName,
        'active' => $isActive
      ];
    }
  }
  $Config['smart_config']['smart_wakeup']['wakeup_reply']['sound_file'] = $newSoundFileList;
  $Config['smart_config']['smart_wakeup']['wakeup_reply']['active'] = isset($_POST['wakeup_reply_active']) ? true : false;
  file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  $messages[] = 'Cập nhật các giá trị trong Câu Phản Hồi (wakeup_reply) thành công';
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

#Tự Sinh HTML
function input_field(
  $id,
  $label,
  $value = '',
  $disabled = '',
  $type = 'text',
  $step = '',
  $min = '',
  $max = '',
  $help = '',
  $extra_class = 'border-success',
  $button_label = '',
  $button_action = '',
  $button_class = 'btn btn-success border-success',
  $button_type = 'onclick',   	//'action' = onclick, 'link' = mở URL
  $button_target = '_blank'  	//chỉ dùng cho loại link
) {
  //Kiểm tra help có phải HTML hoàn chỉnh
  $is_full_html = preg_match('/^\s*<[^>]+>.*<\/[^>]+>\s*$/s', $help);
  if ($is_full_html) {
    $help_icon = '';
    $help_html = $help;
  } else {
    $safe_help = htmlspecialchars($help, ENT_QUOTES);
    $help_icon = $help ? " <i class='bi bi-question-circle-fill' style='cursor:pointer;' onclick=\"show_message('$safe_help')\"></i>" : '';
    $help_html = '';
  }
  // Xử lý step, min, max
  $step_attr = ($type === 'number' && $step !== '') ? "step='$step'" : '';
  $min_attr  = ($type === 'number' && $min !== '') ? "min='$min'" : '';
  $max_attr  = ($type === 'number' && $max !== '') ? "max='$max'" : '';
  //Nếu có button → input-group
  if (!empty($button_label)) {
    // Loại button là action (onclick JS)
    if ($button_type === 'onclick') {
      $button_html = "<button type='button' class='$button_class' onclick=\"$button_action\">$button_label</button>";
    }
    //Loại button là link (href mở URL)
    elseif ($button_type === 'link') {
      $safe_url = htmlspecialchars($button_action, ENT_QUOTES);
      $button_html = "
                <button type='button' class='$button_class'>
                    <a href='$safe_url' target='$button_target' style='color:white; text-decoration:none;'>$button_label</a>
                </button>";
    }
    $input_html = "
        <div class='input-group mb-3'>
            <input $disabled class='form-control $extra_class' 
                   type='$type' $step_attr $min_attr $max_attr 
                   name='$id' id='$id'
                   value='" . htmlspecialchars($value, ENT_QUOTES) . "'>
            $button_html
        </div>";
  } else {
    //Không có button → input thường
    $input_html = "
        <input $disabled class='form-control $extra_class' 
               type='$type' $step_attr $min_attr $max_attr 
               name='$id' id='$id'
               value='" . htmlspecialchars($value, ENT_QUOTES) . "'>";
  }
  return "
    <div class='row mb-3'>
        <label for='$id' class='col-sm-3 col-form-label'>
            $label $help_html $help_icon:
        </label>
        <div class='col-sm-9'>
            $input_html
        </div>
    </div>";
}

function select_field($id, $label, $options, $selected, $disabled_options = []){
  $html = "
  <div class='row mb-3'>
    <label for='$id' class='col-sm-3 col-form-label'>$label:</label>
    <div class='col-sm-9'>
      <select class='form-select border-success' name='$id' id='$id'>";
  foreach ($options as $value => $text) {
    $sel = ($value === $selected) ? 'selected' : '';
    $dis = (in_array($value, $disabled_options)) ? 'disabled' : '';
    $html .= "<option value='$value' $sel $dis>$text</option>";
  }
  $html .= "</select></div></div>";
  return $html;
}

?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>

<head>
  <link rel="stylesheet" href="assets/vendor/prism/prism-tomorrow.min.css?v=<?php echo $Cache_UI_Ver; ?>">
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
	.empty-field {
		border-color: #dc3545 !important;
		box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.30) !important;
		animation: shake 0.5s linear;
	}

	@keyframes shake {
		0% { transform: translateX(0); }
		25% { transform: translateX(-5px); }
		50% { transform: translateX(5px); }
		75% { transform: translateX(-5px); }
		100% { transform: translateX(0); }
	}

	.accordion-collapse {
		transition: all 0.3s ease-out;
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
    echo "<script>showMessagePHP('$allMessages');</script>";
  }
  include 'html_header_bar.php';
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
      <h1>Cấu hình <font color="red" onclick="readJSON_file_path('<?php echo $Config_filePath; ?>')">Config.json</font>
      </h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active" onclick="readJSON_file_path('<?php echo $Config_filePath; ?>')">Config.json</li>
        </ol>
      </nav>
    </div>
    <form class="row g-3 needs-validation" id="hotwordForm" enctype="multipart/form-data" novalidate method="POST" action="" onsubmit="return validateFormVBot()">
      <section class="section">
        <div class="row">
          <div class="col-lg-12">
            <div class="row mb-3 align-items-center">
              <label for="launch_source" class="col-sm-3 col-form-label fw-semibold text-danger">Chế Độ Khởi Chạy Toàn Bộ Chương Trình <i class="bi bi-question-circle-fill" onclick="show_message('- Chạy VBot Assistant có thể cấu hình sử dụng XiaoZhi AI làm trợ lý ảo ưu tiên<br/>- Chỉ Chạy XiaoZhi AI Xuyên Suốt toàn bộ chương trình sẽ chỉ chạy XiaoZhi, Mọi xử lý cũng đều do XiaoZhi')"></i>:</label>
              <div class="col-sm-9">
                <select class="form-select border-success" name="launch_source" id="launch_source">
                  <option value="VBot_Assistant" <?php if ($Config['launch_source'] === "VBot_Assistant") echo "selected"; ?>>Chạy VBot Assistant (Nên Dùng)</option>
                  <option value="XiaoZhi_AI" <?php if ($Config['launch_source'] === "XiaoZhi_AI") echo "selected"; ?>>Chỉ Chạy XiaoZhi AI</option>
                </select>
              </div>
            </div>
            <div class="card accordion" id="accordion_button_ssh">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_ssh" aria-expanded="false" aria-controls="collapse_button_ssh">
                  Cấu Hình Kết Nối SSH Server <font color="red"> (Bắt Buộc)</font>:
                </h5>
                <div id="collapse_button_ssh" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_ssh">
				<div class="alert alert-success" role="alert">
                  <?php
                  echo input_field('ssh_port', 'Cổng kết nối', $Config['ssh_server']['ssh_port'] ?? 22, 'required', 'number', '1', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-danger', '', '', '', '', '');
                  echo input_field('ssh_username', 'Tài Khoản', $Config['ssh_server']['ssh_username'], 'required', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-danger', '', '', '', '', '');
                  echo input_field('ssh_password', 'Mật khẩu', $Config['ssh_server']['ssh_password'], 'required', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-danger', '', '', '', '', '');
                  ?>
                  <div class="row mb-3">
                    <b class="text-danger">Bạn Muốn Truy Cập Thẳng Vào Thiết Bị Raspberry Pi: <a href="https://bitvise.com/ssh-client-download" target="_blank">Hãy Tải Và Cài Đặt Phần Mềm Truy Cập Bằng SSH Trên Máy Tính Tại Đây</a></b>
                  </div>
                  <center><button type="button" class="btn btn-success rounded-pill" onclick="checkSSHConnection('<?php echo $serverIp; ?>')">Kiểm tra kết nối SSH</button></center>
                </div>
              </div>
            </div>
            </div>
            <div class="card accordion" id="accordion_button_webui_path">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_webui_path" aria-expanded="false" aria-controls="collapse_button_webui_path">
                  Cấu Hình Web Interface (Giao Diện):
                </h5>
                <div id="collapse_button_webui_path" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_webui_path">
                <div class="alert alert-success" role="alert">   <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Hiển Thị Lỗi <i class="bi bi-question-circle-fill" onclick="show_message('Chức Năng Này Chỉ Dành Cho Nhà Phát Triển DEV Debug')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="webui_errors_display" id="webui_errors_display" <?php echo $Config['web_interface']['errors_display'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Cho Phép Truy Cập Bên Ngoài Internet <i class="bi bi-question-circle-fill" onclick="show_message('Cần kích hoạt lần đầu trong Tab: <b>Command/Terminal -> WebUI External -> Kích Hoạt WebUI Ra Internet</b><br/><br/> - Sau đó Reboot lại hệ thống hoặc restart lại Apache2 để áp dụng<br/><br/>- Bạn có thể trỏ Tên Miền, Domain, DNS, thông qua Modem, Route, VPN, V..v... về địa chỉ ip Local của thiết bị này bình thường<br/><br/>- Để đảm bảo an toàn khi truy cập bên ngoài Internet bạn nên kích hoạt mật khẩu đăng nhập WebUI và đổi mật khẩu mặc định: <b>Cá Nhân -> Cài Đặt -> Bật Đăng Nhập WebUI</b>')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="webui_external" id="webui_external" <?php echo $Config['web_interface']['external']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <?php
                  echo input_field('webui_path', 'Path (Đường Dẫn) ', $directory_path, 'required', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', 'Cập Nhật', 'update_webui_link(\'' . $directory_path . '\')', 'btn btn-success border-success', 'onclick', '');
                  ?>
                  <div class="row mb-3">
                    <b class="text-danger">Nếu sử dụng Cloudflared Tunnel Để Gắn Tên Miền/Domain Truy Cập Bên Ngoài Internet: <a href="FAQ.php" target="_blank">hãy nhấn vào đây để xem hướng dẫn</a></b>
                  </div>
                </div>
              </div>
            </div>
            </div>
            <div class="card accordion" id="accordion_button_setting_API">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_API" aria-expanded="false" aria-controls="collapse_button_setting_API">
                  Cấu Hình API VBot Server:
                </h5>
                <div id="collapse_button_setting_API" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_setting_API">
                  <div class="alert alert-success" role="alert"> <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích Hoạt API <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng và giao tiếp với VBot thông qua API')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="api_active" id="api_active" <?php echo $Config['api']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <?php
                  echo input_field('api_port', 'Port API ', htmlspecialchars($Config['api']['port']), 'required', 'number', '1', '', '9999', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', 'Kiểm Tra', "$Protocol$serverIp:$Port_API", 'btn btn-success border-success', 'link', '_blank');
                  ?>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Hiển Thị Log API <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt hiển thị log của API, Chỉ hiển thị khi Debug trực tiếp trên Console, Terminal')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="api_log_active" id="api_log_active" <?php echo $Config['api']['show_log']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <?php
                  echo input_field('max_logs_api', 'Tối Đa Dòng Logs API', $Config['api']['show_log']['max_log'] ?? 30, 'required', 'number', '1', '10', '100', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                  echo select_field(
                    'api_log_active_log_lever',
                    'Mức Độ Hiển Thị Log',
                    [
                      'DEBUG' => 'DEBUG (Các thông tin gỡ lỗi)',
                      'INFO' => 'INFO (Các thông tin)',
                      'WARNING' => 'WARNING (Các cảnh báo lỗi)',
                      'ERROR' => 'ERROR (Lỗi nghiêm trọng)',
                      'CRITICAL' => 'CRITICAL (Lỗi rất nghiêm trọng)'
                    ],
                    $Config['api']['show_log']['log_lever'],
					[]
                  );
                  echo input_field('', 'Danh Sách Dữ Liệu API', "http://$serverIp/API_List.php", 'disabled', 'text', '', '', '', '', 'border-danger', 'Truy Cập', "http://$serverIp/API_List.php", 'btn btn-success border-danger', 'link', '_blank');
                  ?>
                </div>
              </div>
            </div>
            </div>

            <div class="card accordion" id="accordion_button_streaming_server_audio">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_streaming_server_audio" aria-expanded="false" aria-controls="collapse_button_streaming_server_audio">
                  Streming Audio Server <font color=red> (VBot Client, Client - Server) </font><i class="bi bi-question-circle-fill" onclick="show_message('Kiểm Tra Và Test Hãy Truy Cập Vào Trang <b>Hướng Dẫn</b> Hoặc <a href=\'FAQ.php\' target=\'_bank\'>Nhấn Vào Đây</a>')"></i>:</h5>
                <div id="collapse_button_streaming_server_audio" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_streaming_server_audio">
				<div class="alert alert-success" role="alert">
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng thiết bị chạy chương trình VBot làm Server')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="streming_active" id="streming_active" <?php echo $Config['api']['streaming_server']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <?php
                  echo select_field('streaming_server_connection_protocol', 'Kiểu Loại Kết Nối <font color="red" size="6" title="Bắt Buộc Nhập">*</font>', ['udp_sock' => 'Sử dụng ESP32, ESP32 D1 Mini, ESP32S3, ESP32S3 Supper Mini'], $Config['api']['streaming_server']['connection_protocol'], []);
                  ?>

                  <div class="card accordion" id="accordion_button_udp_server_streaming">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed text-danger" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_udp_server_streaming" aria-expanded="false" aria-controls="collapse_button_udp_server_streaming">
                        Cấu hình sử dụng Client: ESP32, ESP32S3, ESP32 D1 Mini:</h5>
                      <div id="collapse_button_udp_server_streaming" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_udp_server_streaming">
						<div class="alert alert-primary" role="alert">
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Flash Firmware:</label>
                          <div class="col-sm-9">
                            <a href="https://github.com/marion001/VBot_Client_Offline" target="_blank"> ESP32, ESP32S3, ESP32 D1 Mini, ESP32S3 Supper Mini <i class="bi bi-github"></i></a>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Chế Độ Hội Thoại <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng chế độ hội thoại, trò chuyện liên tục')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="udp_sock_client_conversation_mode" id="udp_sock_client_conversation_mode" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['client_conversation_mode'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Phát Nhạc Local Trên Client: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để phát nhạc, các bài hát Local trên Client')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="udp_sock_music_playback_on_client" id="udp_sock_music_playback_on_client" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['music_playback_on_client'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <?php
                        echo input_field('port_server_udp_streaming_audio', 'Port Server', $Config['api']['streaming_server']['protocol']['udp_sock']['port'] ?? 5003, 'required', 'number', '1', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                        echo input_field('udp_maximum_recording_time', 'Thời Gian Thu Âm Tối Đa (s)', $Config['api']['streaming_server']['protocol']['udp_sock']['maximum_recording_time'] ?? 5, 'required', 'number', '1', '3', '10', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                        echo input_field('udp_maximum_client_connected', 'Tối Đa Client Kết Nối', $Config['api']['streaming_server']['protocol']['udp_sock']['maximum_client_connected'] ?? 3, 'required', 'number', '1', '1', '10', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                        echo input_field('udp_time_remove_inactive_clients', 'Thời gian dọn dẹp Client (s)', $Config['api']['streaming_server']['protocol']['udp_sock']['time_remove_inactive_clients'] ?? 300, 'required', 'number', '1', '200', '900', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                        echo select_field(
                          'udp_source_stt',
                          'Nguồn xử lý âm thanh STT Cho Client',
                          [
                            'stt_default' => 'STT Mặc Định VBot (Free)',
                            'stt_ggcloud' => 'STT Google Cloud V1 (Nên Dùng)'
                          ],
                          $Config['api']['streaming_server']['protocol']['udp_sock']['source_stt'],
						  []
                        );
                        ?>
                        <div class="row mb-3">
                          <label for="udp_working_mode" class="col-sm-3 col-form-label">Chế Độ Làm Việc <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                          <div class="col-sm-9">
                            <select name="udp_working_mode" id="udp_working_mode" class="form-select border-success" aria-label="Default select example">
                              <option value="main_processing" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['working_mode'] === 'main_processing' ? 'selected' : ''; ?>>main_processing (Loa Server chạy VBot xử lý và thực thi)</option>
                              <option disabled value="null" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['working_mode'] === 'null' ? 'selected' : ''; ?>>null (Chỉ xử lý STT to Text)</option>
                            </select>
                          </div>
                        </div>
                        <?php
                        echo select_field(
                          'udp_select_wakeup',
                          'Nguồn Đánh Thức Hotword Client <font color="red" size="6" title="Bắt Buộc Nhập">*</font>',
                          [
                            'porcupine' => 'Picovoice/Porcupine WakeUp Client (Nên Dùng)',
                            'snowboy' => 'Snowboy WakeUP Client'
                          ],
                          $Config['api']['streaming_server']['protocol']['udp_sock']['select_wakeup'],
						  []
                        );
                        echo input_field('udp_server_data_client_name', 'Tệp Dữ Liệu Client', $Config['api']['streaming_server']['protocol']['udp_sock']['data_client_name'], 'required', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                        echo input_field('udp_server_streaming_audio', 'Server Streaming Audio', $serverIp . ':' . $Port_Server_Streaming_Audio_UDP, 'disabled', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                        echo input_field('udp_server_streaming_audio_local', 'URL Audio Local', htmlspecialchars('http://' . $serverIp . '/assets/sound/'), 'disabled', 'text', '', '', '', '', 'border-danger', 'Truy Cập', htmlspecialchars('http://' . $serverIp . '/assets/sound/'), 'btn btn-success border-danger', 'link', '_blank');
                        echo input_field('client_flash_firmware_url', 'Flash Firmware URL', 'https://github.com/marion001/VBot_Client_Offline', 'disabled', 'text', '', '', '', '', 'border-danger', 'Truy Cập', 'https://github.com/marion001/VBot_Client_Offline', 'btn btn-success border-danger', 'link', '_blank');
                        ?>
                      </div>
                    </div>
                  </div>
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
                 <div class="alert alert-success" role="alert"> <div class="card">
                    <div class="card-body">
                      <h5 class="card-title" title="Âm Lượng (Volume)/Audio Out">Cài Đặt Mic &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Bạn có thể tham khảo hướng dẫn tại đây: <a href=\'FAQ.php\' target=\'_bank\'>Hướng Dẫn</a> <br/> Lưu Ý: Nếu Bạn Sử Dụng Mic I2S: INMP441 kết hợp với MAX98357 Thì Cần Flash IMG (VBot I2S) Và Phải Đặt ID Mic Luôn Luôn Là (-1) Nhé')"></i> &nbsp;:</h5>
                      <div class="alert alert-primary" role="alert">
					  <?php
                      echo input_field('mic_id', 'ID Mic', htmlspecialchars($Config['smart_config']['mic']['id']), 'required', 'number', '', '', '', 'Bạn có thể tham khảo hướng dẫn tại đây: <a href=\'FAQ.php\' target=\'_bank\'>Hướng Dẫn</a> <br/> Lưu Ý: Nếu Bạn Sử Dụng Mic I2S: INMP441 kết hợp với MAX98357 Thì Cần Flash IMG (VBot I2S) Và Phải Đặt ID Mic Luôn Luôn Là (-1) Nhé', 'border-success', 'Tìm Kiếm ID Mic', "scan_audio_devices('scan_mic')", 'btn btn-success border-success', 'onclick', '_blank');
                      ?>
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Auto Scan Mic <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật hệ thống sẽ tìm kiếm và liệt kê các ID và Tên của Microphone có trên hệ thống, và hiển thị ra các đường Logs mỗi khi trương trình được khởi chạy')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="mic_scan_on_boot" id="mic_scan_on_boot" <?php echo $Config['smart_config']['mic']['scan_on_boot'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div id="mic_scanner"></div>
                      </div>
                    </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title" title="Âm Lượng (Volume)/Audio Out">Âm Lượng (Volume)/Audio Out &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('<font color=green>- Trương trình sẽ tương tác và thay đổi âm lượng của trình phát VLC <br/>- Sẽ không can thiệp vào âm lượng trên hệ thống của thiết bị (Trương trình sẽ bị giới hạn mức âm lượng, nếu âm lượng của hệ thống alsamixer đầu ra bị hạn chế hoặc được đặt ở mức thấp)</font><br/>Bạn có thể tham khảo hướng dẫn tại đây: <a href=\'FAQ.php\' target=\'_bank\'> Hướng Dẫn</a>')"></i> &nbsp;:</h5>
                      <div class="alert alert-primary" role="alert">
					  <?php
                      echo input_field('alsamixer_name', 'Tên thiết bị (alsamixer) ', htmlspecialchars($Config['smart_config']['speaker']['system']['alsamixer_name']), '', 'text', '', '', '', 'Tên của thiết bị âm thanh đầu ra trong alsamixer, cần điền đúng tên thiết bị âm thanh đầu ra hiện tại của alsamixer<br/><br/>- nếu không biết đâu là thiết bị âm thanh đầu ra thì bạn có thể phát 1 bài nhạc bằng vlc ví dụ: <b>$: vlc 1.mp3</b> sau đó vào alsamixer bằng lệnh: <b>$: alsamixer</b> thay đổi âm lượng của các thiết bị có trong đó để xác định xem đâu là tên thiết bị đầu ra', 'border-success', 'Tìm Kiếm', "scan_audio_devices('scan_alsamixer')", 'btn btn-success border-success', 'onclick', '_blank');
                      echo input_field('bot_volume', 'Âm lượng', $Config['smart_config']['speaker']['volume'] ?? 50, 'required', 'number', '1', '0', '100', 'Đặt mức âm lượng mặc định khi bắt đầu khởi chạy chương trình', 'border-success', '', '', '', '', '');
                      echo input_field('bot_volume_min', 'Âm lượng thấp nhất', $Config['smart_config']['speaker']['volume_min'] ?? 0, 'required', 'number', '1', '0', '100', 'Mức âm lượng thấp nhất cho phép khi giảm âm lượng, thấp nhất là 0', 'border-success', '', '', '', '', '');
                      echo input_field('bot_volume_max', 'Âm lượng cao nhất', $Config['smart_config']['speaker']['volume_max'] ?? 100, 'required', 'number', '1', '0', '100', 'Mức âm lượng cao nhất khi tăng âm lương, cao nhất là 100', 'border-success', '', '', '', '', '');
                      echo input_field('bot_volume_step', 'Bước âm lượng', $Config['smart_config']['speaker']['volume_step'] ?? 10, 'required', 'number', '1', '0', '100', 'Bước âm lượng thay đổi khi mỗi lần nhấn nút tăng hoặc giảm âm lượng', 'border-danger', '', '', '', '', '');
                      ?>
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Ghi Nhớ Âm Lượng Khi Được Thay Đổi <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật hệ thống sẽ lưu lại giá trị âm lượng vào tệp Config.json mỗi khi được thay đổi trong quá trình Bot hoạt động')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="remember_last_volume" id="remember_last_volume" <?php echo $Config['smart_config']['speaker']['remember_last_volume'] ? 'checked' : ''; ?>>
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
            </div>
            </div>

            <div class="card accordion" id="accordion_button_hotword_engine">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_hotword_engine" aria-expanded="false" aria-controls="collapse_button_hotword_engine">
                  Cấu Hình WakeUP Hotword Engine (Từ Đánh Thức) Picovoice/Snowboy:
                </h5>
                <div id="collapse_button_hotword_engine" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_hotword_engine" style="">
                  <div class="alert alert-success" role="alert"> <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Hotword / Từ Nóng Đánh Thức <i class="bi bi-question-circle-fill" onclick="show_message('Danh sách file thư viện Porcupine: <a href=\'https://github.com/Picovoice/porcupine/tree/master/lib/common\' target=\'_bank\'>Github</a><br/>Mẫu các từ khóa đánh thức: <a href=\'https://github.com/Picovoice/porcupine/tree/master/resources\' target=\'_bank\'>Github</a>')"></i> :</h5>
                      <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                        <label for="" class="col-sm-3 col-form-label">Cho Phép Chạy Chương Trình Khi Lỗi Khởi Tạo Hotword, Wakeup <i class="bi bi-question-circle-fill" onclick="show_message('Cho Phép Chương Trình Tiếp Tục Khởi Chạy Khi Tiến Trình khởi Tạo Từ Đánh Thức Wake Up Gặp Lỗi. (Và sẽ không dùng được Từ nóng Hotword để đánh thức)')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="continue_running_if_hotword_initialization_fails" id="continue_running_if_hotword_initialization_fails" <?php echo $Config['smart_config']['smart_wakeup']['hotword']['continue_running_if_hotword_initialization_fails'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      echo select_field(
                        'hotword_select_wakeup',
                        'Chọn Nguồn Đánh Thức',
                        [
                          'porcupine' => 'Picovoice/Procupine (Nên Dùng)',
                          'snowboy' => 'Snowboy',
                          'null' => 'Không Sử Dụng Hotword'
                        ],
                        $Config['smart_config']['smart_wakeup']['hotword']['select_wakeup'],
						[]
                      );
                      ?>
                      <!-- nếu hotword được chọn là Picovoice Procupine -->
                      <div id="select_show_picovoice_porcupine">
                        <?php
                        echo input_field('hotword_engine_key', 'Picovoice Token Key ', htmlspecialchars($Config['smart_config']['smart_wakeup']['hotword_engine']['key']), '', 'text', '', '', '', 'Đăng ký, lấy key: <a href=\'https://console.picovoice.ai\' target=\'_bank\'>https://console.picovoice.ai</a>', 'border-success', 'Kiểm Tra', 'test_key_Picovoice()', 'btn btn-success border-success', 'onclick', '_blank');
                        ?>
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
                              <th scope="col" colspan="5">
                                <h5 class="card-title">
                                  <center>
                                    <font color=red>Cài đặt nâng cao Hotword:</font>
                                  </center>
                                </h5>
                              </th>
                            </tr>
                            <tr>
                              <th scope="col" colspan="8">
                                <center>
                                  <button type="button" class="btn btn-primary rounded-pill" onclick="loadConfigHotword('vi')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Cài đặt Hotword Tiếng Việt">Tiếng Việt</button>
                                  <button type="button" class="btn btn-primary rounded-pill" onclick="loadConfigHotword('eng')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Cài đặt Hotword Tiếng Anh">Tiếng Anh</button>
                                  <button type="button" class="btn btn-warning rounded-pill" onclick="reload_hotword_config()" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tự động tìm scan các file Hotword có trong thư mục eng và vi để cấu hình trong Config.json">Scan Và Ghi Mới</button>
                                </center>
                                <span id="language_hotwordd" value=""></span>
                            </tr>
                          </thead>
                          <thead id="results_body_hotword1"></thead>
                          <tbody id="results_body_hotword"></tbody>
                        </table>
                      </div>
                      <div id="select_show_snowboy">
                        <center>
                          <button type="button" class="btn btn-primary rounded-pill" onclick="loadConfigHotword('snowboy')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Hiển thị danh sách Hotword Snowboy" aria-describedby="tooltip849515">Danh Sách Hotword</button>
                          <button type="button" class="btn btn-warning rounded-pill" onclick="reload_hotword_config('snowboy')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tự động tìm scan các file Hotword Snowboy có trong thư mục để cấu hình trong Config.json">Scan Và Ghi Mới</button>
                        </center>
                        <br />
                        <div class="row mb-3">
                          <label for="" class="col-sm-3 col-form-label">Cài Thư Viện, Train Hotword:</label>
                          <div class="col-sm-9">
                            <a href="FAQ.php" target="_blank">Nhấn Vào Đây Để Xem Hướng Dẫn</a>
                          </div>
                        </div>
                        <table class="table table-bordered border-primary">
                          <thead id="results_body_hotword_snowboy1"></thead>
                          <tbody id="results_body_hotword_snowboy"></tbody>
                        </table>
                      </div>
                    </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>

            <div class="card accordion" id="accordion_button_setting_stt">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_stt" aria-expanded="false" aria-controls="collapse_button_setting_stt">
                  Chuyển Giọng Nói Thành Văn Bản - Speak To Text (STT) &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi giọng nói thành văn bản để chương trình xử lý dữ liệu')"></i> &nbsp;:
                </h5>
                <div id="collapse_button_setting_stt" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_stt" style="">
               <div class="alert alert-success" role="alert">
			   <?php
                  echo input_field('duration_recording', 'Thời gian lắng nghe tối đa (giây)', $Config['smart_config']['smart_wakeup']['speak_to_text']['duration_recording'] ?? 6, 'required', 'number', '1', '3', '10', 'Thời gian lắng nghe tối đa khi Bot được đánh thức', 'border-success', '', '', '', '', '');
                  ?>
                  <div class="row">
                    <div class="col-lg-6">
                      <div class="card">
                        <div class="card-body">
                          <h5 class="card-title" title="Chuyển giọng nói thành văn bản">Lựa chọn STT (Speak To Text) <font color="red" size="6" title="Bắt Buộc Nhập">*</font> :</h5>
						  <div class="alert alert-primary" role="alert">
                          <?php
                          $GET_stt_select = $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'];
                          if ($GET_stt_select === "stt_default") {
                            $replace_text_stt = "Mặc Định";
                          } else if ($GET_stt_select === "stt_ggcloud") {
                            $replace_text_stt = "Google Cloud V1";
                          } else if ($GET_stt_select === "stt_ggcloud_v2") {
                            $replace_text_stt = "Google Cloud V2";
                          } else {
                            $replace_text_stt = "Không có dữ liệu";
                          }
                          ?>
                          <center>Bạn đang dùng TTS: <font color=red><?php echo $replace_text_stt; ?></font>
                          </center>
                          <div class="col-sm-9">
                            <div class="form-check">
                              <input class="form-check-input border-success" type="radio" name="stt_select" id="stt_default" value="stt_default" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_default' ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="stt_default">STT Mặc Định VBot (Free)</label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input border-success" type="radio" name="stt_select" id="stt_ggcloud" value="stt_ggcloud" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_ggcloud' ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="stt_ggcloud">STT Google Cloud V1 (Authentication.json) <font color=red>Nên sử dụng</font> <i class="bi bi-question-circle-fill" onclick="show_message('Hướng Dẫn Đăng Ký Hãy Xem Ở Hướng Dẫn Sau Trong Thư  Mục <b>Guide</b> -> <b>Tạo STT Google Cloud</b> <br/><br/>-Link: <a href=\'https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ\' target=\'_bank\'>https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>')"></i></label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input border-success" type="radio" name="stt_select" id="stt_ggcloud_v2" value="stt_ggcloud_v2" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_ggcloud_v2' ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="stt_ggcloud_v2">STT Google Cloud V2 (Authentication.json) <i class="bi bi-question-circle-fill" onclick="show_message('Hướng Dẫn Đăng Ký Hãy Xem Ở Hướng Dẫn Sau Trong Thư  Mục <b>Guide</b> -> <b>Tạo STT Google Cloud</b><br/>Lệnh Update Cập Nhật Lib: <b>$:> pip install --upgrade google-cloud-speech</b><br/><br/>-Link: <a href=\'https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ\' target=\'_bank\'>https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>')"></i></label>
                            </div>
                          </div>
                        </div>
                      </div>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="card">
                        <div class="card-body">
                          <h5 class="card-title">Cấu hình STT:</h5>
						  <div class="alert alert-primary" role="alert">
                          <!-- ẩn hiện cấu hình select_stt_ggcloud_html -->
                          <div id="select_stt_ggcloud_html" class="col-12" style="display: none;">
                            <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                              <center>
                                <font color=red>STT Google Cloud V1 (Authentication.json)</font>
                              </center>
                            </h4>
                            <div class="form-floating mb-3">
                              <textarea class="form-control border-success" placeholder="Tệp tin json xác thực" name="stt_ggcloud_json_file_token" id="stt_ggcloud_json_file_token" style="height: 150px;">
<?php echo htmlspecialchars(trim($read_stt_token_google_cloud)); ?>
</textarea>
                              <label for="stt_ggcloud_json_file_token">Tệp tin json xác thực: stt_token_google_cloud.json</label>
                            </div>
                            <div class="form-floating mb-3">
                              <center><button type="button" class="btn btn-success" title="Tải xuống" onclick="downloadFile('<?php echo $VBot_Offline . $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['authentication_json_file']; ?>')"><i class="bi bi-download"></i> Tải Xuống Tệp Json</button></center>
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
                            <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                              <center>
                                <font color=red>STT Google Cloud V2 (Authentication.json)</font>
                              </center>
                            </h4>
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
                              <label for="stt_ggcloud_v2_json_file_token">Tệp tin json xác thực: stt_token_google_cloud.json</label>
                            </div>
                            <div class="form-floating mb-3">
                              <center><button type="button" class="btn btn-success" title="Tải xuống" onclick="downloadFile('<?php echo $VBot_Offline . $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_ggcloud']['authentication_json_file']; ?>')"><i class="bi bi-download"></i> Tải Xuống Tệp Json</button></center>
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
                            <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                              <center>
                                <font color=red>STT Default</font>
                              </center>
                            </h4>
                            Không cần cấu hình
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

            <div class="card accordion" id="accordion_button_setting_tts">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_tts" aria-expanded="false" aria-controls="collapse_button_setting_tts">
                  Chuyển Văn Bản Thành Giọng Nói - Text To Speak (TTS) &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi kết quả từ văn bản thành giọng nói để phát ra loa')"></i> &nbsp;:
                </h5>
                <div id="collapse_button_setting_tts" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_tts" style="">
                 <div class="alert alert-success" role="alert"> <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích hoạt Cache lại TTS:</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="cache_tts" id="cache_tts" <?php echo $Config['smart_config']['smart_answer']['cache_tts']['active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cache_tts"> (Bật hoặc Tắt sử dụng Cache)</label>
                      </div>
                    </div>
                  </div>
                  <?php
                  echo input_field('directory_tts', 'Thư Mục Chứa TTS', $Config['smart_config']['smart_answer']['text_to_speak']['directory_tts'], 'readonly', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                  echo input_field('clean_cache_tts_max_file', 'Dọn Dẹp TTS Nếu Vượt Quá (file)', $Config['smart_config']['smart_answer']['text_to_speak']['clean_cache_tts_max_file'], 'required', 'number', '1', '50', '', 'Tự động dọn dẹp tts nếu số lượng tệp tin vượt quá ngưỡng cho phép', 'border-success', '', '', '', '', '');
                  ?>
                  <div class="row">
                    <div class="col-lg-6">
                      <div class="card">
                        <div class="card-body">
                          <h5 class="card-title" title="Chuyển giọng nói thành văn bản">Lựa chọn TTS (Text To Speak) <font color="red" size="6" title="Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Cần lựa chọn TTS bên dưới để sử dụng hoặc cấu hình cài đặt cho TTS đó')"></i> :</h5>
                          <div class="alert alert-primary" role="alert">
						  <?php
                          $GET_tts_select = $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'];
                          if ($GET_tts_select === "tts_default") {
                            $replace_text_tts = "Mặc Định";
                          } else if ($GET_tts_select === "tts_ggcloud") {
                            $replace_text_tts = "Google Cloud Authentication.json";
                          } else if ($GET_tts_select === "tts_zalo") {
                            $replace_text_tts = "Zalo AI";
                          } else if ($GET_tts_select === "tts_viettel") {
                            $replace_text_tts = "Viettel AI";
                          } else if ($GET_tts_select === "tts_edge") {
                            $replace_text_tts = "Microsoft edge";
                          } else if ($GET_tts_select === "tts_ggcloud_key") {
                            $replace_text_tts = "Google Cloud Key";
                          } else if ($GET_tts_select === "tts_dev_customize") {
                            $replace_text_tts = "TTS DEV Customize";
                          } else {
                            $replace_text_tts = "Không có dữ liệu";
                          }
                          ?>
                          <center>Bạn đang dùng TTS: <font color=red><?php echo $replace_text_tts; ?></font>
                          </center>
                          <?php
                          $tts_list_select = [
                            'tts_default' => [
                              'label' => 'TTS Mặc Định (Free) <font color=red>Đang Bị Lỗi</font>',
                              'help'  => "Với tts_default này sẽ không mất phí với người dùng và vẫn đảm bảo chất lượng cao, ổn định",
                              'disabled' => true,
                            ],
                            'tts_ggcloud' => [
                              'label' => 'TTS Google Cloud (Authentication.json) <font color=green>Khuyến Khích Nên sử dụng</font>',
                              'help'  => "Hướng Dẫn Đăng Ký Hãy Xem Ở Hướng Dẫn Sau Trong Thư Mục <b>Guide</b> -> <b>Tạo STT Google Cloud</b><br/><br/>-Link: <a href='https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ' target='_bank'>https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>",
                            ],
                            'tts_ggcloud_key' => [
                              'label' => 'TTS Google Cloud (Free Key)',
                              'help'  => "Sử dụng Key miễn phí bằng cách lấy thủ công và có thời gian hết hạn: https://www.gstatic.com/cloud-site-ux/text_to_speech/text_to_speech.min.html",
                            ],
                            'tts_zalo' => [
                              'label' => 'TTS Zalo (Api Keys)',
                              'help'  => "Cần sử dụng Api keys của zalo càng nhiều Keys càng tốt, Mỗi Keys một dòng<br/>Key Lỗi hoặc Hết giới hạn dùng miễn phí sẽ tự động chuyển vào file BackList, và sẽ tự động làm mới nội dung BackList vào hôm sau<br/>Trang Chủ: <a href='https://zalo.ai/account/manage-keys' target='_bank'>https://zalo.ai/account/manage-keys</a>",
                            ],
                            'tts_viettel' => [
                              'label' => 'TTS Viettel (Api Keys)',
                              'help'  => "Cần sử dụng Api keys của Viettel càng nhiều Keys càng tốt, Mỗi Keys một dòng<br/>Key Lỗi hoặc Hết giới hạn dùng miễn phí sẽ tự động chuyển vào file BackList, và sẽ tự động làm mới nội dung BackList vào hôm sau<br/>Trang Chủ: <a href='https://viettelai.vn/dashboard/token' target='_bank'>https://viettelai.vn/dashboard/token</a>",
                            ],
                            'tts_edge' => [
                              'label' => 'TTS Microsoft Edge (Free) <font color=green>(Khuyến Khích Nên Sử Dụng)</font>',
                              'help'  => 'TTS Microsoft edge Free',
                            ],
                            'tts_dev_customize' => [
                              'label' => 'TTS DEV Customize: Dev_TTS.py <font color=red>(Người Dùng Tự Code)</font>',
                              'help'  => "Người dùng sẽ tự code, chuyển văn bản thành giọng nói nếu chọn tts này, dữ liệu để các bạn code sẽ nằm trong tệp: <b>Dev_TTS.py</b><br/>- Cần thêm kích hoạt bên dưới để sử dụng vào chương trình",
                              'extra' => [
                                'id' => 'tts_dev_customize_active',
                                'label' => 'Kích Hoạt',
                                'help'  => "Nếu Dùng TTS DEV Custom bạn cần phải kích hoạt để được khởi tạo dữ liệu khi chạy chương trình",
                                'checked' => !empty($Config['smart_config']['smart_answer']['text_to_speak']['tts_dev_customize']['active']),
                              ],
                            ],
                          ];
                          ?>
                          <div class="col-sm-9">
                            <?php foreach ($tts_list_select as $id => $opt): ?>
                              <div class="form-check">
                                <input class="form-check-input border-success" type="radio" name="tts_select" id="<?php echo $id; ?>" value="<?php echo $id; ?>" <?php echo ($Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === $id) ? 'checked' : ''; ?> <?php echo !empty($opt['disabled']) ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $id; ?>">
                                  <?php echo $opt['label']; ?>
                                  <i class="bi bi-question-circle-fill" onclick="show_message('<?php echo addslashes($opt['help']); ?>')"></i>
                                </label>
                                <?php if (!empty($opt['extra'])): ?>
                                  <div class="form-switch mt-1">
                                    <label class="form-label" for="<?php echo $opt['extra']['id']; ?>">
                                      <?php echo $opt['extra']['label']; ?>
                                      <i class="bi bi-question-circle-fill" onclick="show_message('<?php echo addslashes($opt['extra']['help']); ?>')"></i>
                                    </label>
                                    <input class="form-check-input border-success" type="checkbox" name="<?php echo $opt['extra']['id']; ?>" id="<?php echo $opt['extra']['id']; ?>" <?php echo $opt['extra']['checked'] ? 'checked' : ''; ?>>
                                  </div>
                                <?php endif; ?>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="card">
                        <div class="card-body">
                          <h5 class="card-title">Cấu hình TTS:</h5>
						  <div class="alert alert-primary" role="alert">
                          <!-- ẩn hiện cấu hình select_tts_default_html style="display: none;" -->
                          <div id="select_tts_default_html" class="col-12" style="display: none;">
                            <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                              <center>
                                <font color=red>TTS Default</font>
                              </center>
                            </h4>
                            <div class="form-floating mb-3">
                              <select name="tts_default_quality" id="tts_default_quality" class="form-select border-success" aria-label="Default select example">
                                <option value="0" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['quality'] === 0 ? 'selected' : ''; ?>>Tiêu chuẩn</option>
                                <option disabled value="0" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['quality'] === 1 ? 'selected' : ''; ?>>Chất lượng cao</option>
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
                              <input class="form-control border-danger" type="text" name="authentication_zai_did" id="authentication_zai_did" placeholder="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['authentication_zai_did']; ?>" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['authentication_zai_did']; ?>">
                              <label for="authentication_zai_did" class="form-label">Mã Token zai_did:</label>
                            </div>
                            <div class="form-floating mb-3">
                              <input class="form-control border-danger" type="text" name="expires_zai_did" id="expires_zai_did" placeholder="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['expires_zai_did']; ?>" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_default']['expires_zai_did']; ?>">
                              <label for="expires_zai_did" class="form-label">Hết hạn zai_did:</label>
                            </div>
                            <div class="form-floating mb-3">
                              <center><button class="btn btn-success rounded-pill" type="button" onclick="get_token_tts_default_zai_did()">Lấy Token zai_did</button> <i class="bi bi-question-circle-fill" onclick="show_message('zai_did Chỉ dùng cho trợ lý ảo Default Assistant, với chức năng: Chuyển đổi thêm kết quả thành văn bản (text), Hạn sử dụng 1 năm')"></i></center>
                            </div>
                          </div>
                          <!-- ẩn hiện cấu hình select_tts_default_html style="display: none;" -->
                          <div id="select_tts_ggcloud_html" class="col-12" style="display: none;">
                            <h4 class="card-title" title="Chuyển văn bản thành văn bản">
                              <center>
                                <font color=red>TTS Google Cloud</font>
                              </center>
                            </h4>
                            <div class="form-floating mb-3">
                              <select name="tts_ggcloud_language_code" id="tts_ggcloud_language_code" class="form-select border-success" aria-label="Default select example">
                                <option value="vi-VN" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['language_code'] === 'vi-VN' ? 'selected' : ''; ?>>Tiếng Việt</option>
                              </select>
                              <label for="tts_ggcloud_language_code">Ngôn ngữ:</label>
                            </div>
                            <div class="input-group mb-3">
                              <div class="form-floating">
                                <select name="tts_ggcloud_voice_name" id="tts_ggcloud_voice_name" class="form-select border-success" aria-label="Default select example">
                                  <option value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name']; ?>" selected><?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name']; ?></option>
                                </select>
                                <label for="tts_ggcloud_voice_name">Giọng đọc:</label>
                              </div>
                              <button type="button" name="load_list_gcloud_tts" id="load_list_gcloud_tts" class="btn btn-primary" onclick="load_list_GoogleVoices_tts('tts_ggcloud', 'ok')" title="Tải danh sách giọng đọc TTS GCloud"><i class="bi bi-list-ul"></i></button>
                              <button type="button" name="tts_sample_gcloud_play" id="tts_sample_gcloud_play" class="btn btn-success" onclick="play_tts_sample_gcloud('tts_ggcloud_voice_name')"><i class="bi bi-play-circle"></i></button>
                            </div>
                            <div class="form-floating mb-3">
                              <input type="number" min="0.25" step="0.25" max="4.0" class="form-control border-success" name="tts_gcloud_speaking_speed" id="tts_gcloud_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['speaking_speed']; ?>">
                              <label for="tts_gcloud_speaking_speed" class="form-label">Tốc độ đọc:</label>
                            </div>
                            <div class="form-floating mb-3">
                              <textarea class="form-control border-success" placeholder="Tệp tin json xác thực" name="tts_ggcloud_json_file_token" id="tts_ggcloud_json_file_token" style="height: 150px;">
<?php echo htmlspecialchars(trim($read_tts_token_google_cloud)); ?>
</textarea>
                              <label for="tts_ggcloud_json_file_token">Tệp tin json xác thực: tts_token_google_cloud.json</label>
                            </div>
                            <div class="form-floating mb-3">
                              <input disabled type="text" class="form-control border-danger" name="list_voices_tts_gcloud" id="list_voices_tts_gcloud" value="<?php echo $directory_path.'/includes/other_data/list_voices_tts_gcloud.json'; ?>">
                              <label for="list_voices_tts_gcloud" class="form-label">Đường Dẫn Danh Sách Giọng Đọc:</label>
                            </div>
                            <div class="form-floating mb-3">
                              <center><button type="button" class="btn btn-success" title="Tải xuống" onclick="downloadFile('<?php echo $VBot_Offline . $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['authentication_json_file']; ?>')"><i class="bi bi-download"></i> Tải Xuống Tệp Json</button></center>
                            </div>
                          </div>
                          <!-- ẩn hiện cấu hình select_tts_zalo_html style="display: none;" -->
                          <div id="select_tts_zalo_html" class="col-12" style="display: none;">
                            <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                              <center>
                                <font color=red>TTS Zalo AI</font>
                              </center>
                            </h4>
                            <div class="form-floating mb-3">
                              <input type="number" min="0.8" step="0.1" max="1.2" class="form-control border-success" name="tts_zalo_speaking_speed" id="tts_zalo_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['speaking_speed']; ?>">
                              <label for="tts_zalo_speaking_speed" class="form-label">Tốc độ đọc:</label>
                            </div>
                            <div class="form-floating mb-3">
                              <select name="tts_zalo_voice_name" id="tts_zalo_voice_name" class="form-select border-success" aria-label="Default select example">
                                <option value="1" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 1 ? 'selected' : ''; ?>>(Nữ) Miền Nam 1</option>
                                <option value="2" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 2 ? 'selected' : ''; ?>>(Nữ) Miền Bắc 1</option>
                                <option value="3" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 3 ? 'selected' : ''; ?>>(Nam) Miền Nam</option>
                                <option value="4" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 4 ? 'selected' : ''; ?>>(Nam) Miền Bắc</option>
                                <option value="5" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 5 ? 'selected' : ''; ?>>(Nữ) Miền Bắc 2</option>
                                <option value="6" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_zalo']['voice_name'] === 6 ? 'selected' : ''; ?>>(Nữ) Miền Nam 2</option>
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
?></textarea>
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
                            <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                              <center>
                                <font color=red>TTS Viettel AI</font>
                              </center>
                            </h4>
                            <div class="form-floating mb-3">
                              <input type="number" min="0.8" step="0.1" max="1.2" class="form-control border-success" name="tts_viettel_speaking_speed" id="tts_viettel_speaking_speed" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['speaking_speed']; ?>">
                              <label for="tts_viettel_speaking_speed" class="form-label">Tốc độ đọc:</label>
                            </div>
                            <?php
                            $voices_tts_viettel = [
                              'Miền Bắc' => [
                                'hn-quynhanh'   => '(Nữ) Miền Bắc: Quỳnh Anh',
                                'hn-phuongtrang' => '(Nữ) Miền Bắc: Phương Trang',
                                'hn-thaochi'    => '(Nữ) Miền Bắc: Thảo Chi',
                                'hn-thanhha'    => '(Nữ) Miền Bắc: Thanh Hà',
                                'hn-thanhphuong' => '(Nữ) Miền Bắc: Thanh Phương',
                                'hn-thanhtung'  => '(Nam) Miền Bắc: Thanh Tùng',
                                'hn-namkhanh'   => '(Nam) Miền Bắc: Nam Khánh',
                                'hn-tienquan'   => '(Nam) Miền Bắc: Tiến Quân',
                              ],
                              'Miền Trung' => [
                                'hue-maingoc' => '(Nữ) Miền Trung: Mai Ngọc',
                                'hue-baoquoc' => '(Nam) Miền Trung: Bảo Quốc',
                              ],
                              'Miền Nam' => [
                                'hcm-diemmy'   => '(Nữ) Miền Nam: Diễm My',
                                'hcm-phuongly' => '(Nữ) Miền Nam: Phương Ly',
                                'hcm-thuydung' => '(Nữ) Miền Nam: Thùy Dung',
                                'hcm-thuyduyen' => '(Nữ) Miền Nam: Thùy Duyên',
                                'hcm-minhquan' => '(Nam) Miền Nam: Minh Quân',
                              ],
                            ];
                            ?>
                            <div class="form-floating mb-3">
                              <select name="tts_viettel_voice_name" id="tts_viettel_voice_name" class="form-select border-success" aria-label="Default select example">
                                <?php foreach ($voices_tts_viettel as $region => $list): ?>
                                  <optgroup label="<?php echo htmlspecialchars($region); ?>">
                                    <?php foreach ($list as $value => $label): ?>
                                      <option value="<?php echo htmlspecialchars($value); ?>"
                                        <?php echo ($Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['voice_name'] === $value) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                      </option>
                                    <?php endforeach; ?>
                                  </optgroup>
                                <?php endforeach; ?>
                              </select>
                              <label for="tts_viettel_voice_name">Giọng đọc:</label>
                            </div>
                            <div class="form-floating mb-3">
                              <div class="form-switch">
                                <input class="form-check-input border-success" type="checkbox" name="tts_viettel_without_filter" id="tts_viettel_without_filter" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['without_filter'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cache_tts"> <i class="bi bi-question-circle-fill" onclick="show_message('Bật để tăng chất lượng giọng nói nhưng tốc độ sẽ xử lý chậm hơn và ngược lại')"> </i> (Bật, Tắt) Tăng chất lượng giọng nói</label>
                              </div>
                            </div>
                            <div class="form-floating mb-3">
                              <textarea class="form-control border-success" placeholder="Api Keys, Mỗi Keys tương ứng với 1 dòng" name="tts_viettel_api_key" id="tts_viettel_api_key" style="height: 150px;">
<?php
//Hiển thị api Key Viettel theo dòng
$apiKeys_tts_viettel = isset($Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['api_key']) ? $Config['smart_config']['smart_answer']['text_to_speak']['tts_viettel']['api_key'] : [];
$textareaContent_tts_viettel = implode("\n", array_map('trim', $apiKeys_tts_viettel));
echo htmlspecialchars($textareaContent_tts_viettel);
?></textarea>
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
                            <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                              <center>
                                <font color=red>TTS Microsoft Edge Azure</font>
                              </center>
                            </h4>
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
                            <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                              <center>
                                <font color=red>TTS Google Cloud KEY</font>
                              </center>
                            </h4>
                            - Lấy Key Miễn Phí: <i class="bi bi-question-circle-fill" onclick="show_message('Truy Cập: <a href=\'https://www.gstatic.com/cloud-site-ux/text_to_speech/text_to_speech.min.html\' target=\'_bank\'>https://www.gstatic.com/cloud-site-ux/text_to_speech/text_to_speech.min.html</a> <br/>- <b>Nhấn F12</b>, Chuyển Qua Tab: <b>Mạng</b> Lựa Chọn Ngôn Ngữ Tiếng Việt nhập bất kỳ văn bản vào ô rồi nhấn nút: <b>SPEAK IT</b> sau đó <b>Xác Minh Capcha.</b><br/> Nhìn vào Tab vừa nhấn F12 tìm tới giá trị bắt đầu bằng <b>proxy?url=</b> trong toàn bộ giá trị đó tìm tới chỗ: <b>&token=</b> Sau dấu = đó chính mã token hãy sao chép và dán vào ô bên dưới<br/><b>- Lưu Ý: Key Miễn Phí này sẽ có thời gian sử dụng, nếu key hết hạn bạn cần lấy key mới thủ công</b>')"></i>
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
                                  <option value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name']; ?>" selected><?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name']; ?></option>
                                </select>
                                <label for="tts_ggcloud_key_voice_name">Giọng đọc:</label>
                              </div>
                              <button type="button" name="load_list_gcloudkey_tts" id="load_list_gcloudkey_tts" class="btn btn-primary" onclick="load_list_GoogleVoices_tts('tts_ggcloud_key', 'ok')" title="Tải danh sách giọng đọc TTS GCloud"><i class="bi bi-list-ul"></i></button>
                              <button type="button" name="tts_sample_gcloud_play" id="tts_sample_gcloud_play" class="btn btn-success" onclick="play_tts_sample_gcloud('tts_ggcloud_key_voice_name')"><i class="bi bi-play-circle"></i></button>
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
            </div>
            </div>

            <div class="card accordion" id="accordion_button_setting_homeassistant">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_homeassistant" aria-expanded="false" aria-controls="collapse_button_setting_homeassistant">
                  Cấu Hình Kết Nối Tới Home Assistant (HASS):
                </h5>
                <div id="collapse_button_setting_homeassistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_setting_homeassistant">
                 <div class="alert alert-success" role="alert">  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng điều khiển nhà thông minh Home Assistant')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="hass_active" id="hass_active" <?php echo $Config['home_assistant']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>

                  <?php
                  echo input_field('hass_long_token', 'Mã Token (Long Token)', $Config['home_assistant']['long_token'], '', 'text', '', '', '', '', 'border-success', '', '', '', '', '');
                  echo input_field('hass_internal_url', 'URL nội bộ ', htmlspecialchars($Config['home_assistant']['internal_url']), '', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', 'Kiểm Tra', "CheckConnectionHomeAssistant('hass_internal_url')", 'btn btn-success border-success', 'onclick', '_blank');
                  echo input_field('hass_external_url', 'URL bên ngoài ', htmlspecialchars($Config['home_assistant']['external_url']), '', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', 'Kiểm Tra', "CheckConnectionHomeAssistant('hass_external_url')", 'btn btn-success border-success', 'onclick', '_blank');
                  echo input_field('hass_minimum_threshold', 'Ngưỡng kết quả tối thiểu', $Config['home_assistant']['minimum_threshold'] ?? 0.7, 'required', 'number', '0.01', '0.5', '0.9', 'Ngưỡng kết quả cho phép từ <b>0.1 -> 0.9</b> ngưỡng càng cao thì yêu cầu độ chính xác cao khi bot tìm kiếm và lọc thiết bị', 'border-success', '', '', '', '', '');
                  echo input_field('hass_lowest_to_display_logs', 'Ngưỡng tối thiểu hiển thị ra logs', $Config['home_assistant']['lowest_to_display_logs'] ?? 0.39, 'required', 'number', '0.01', '0', '0.45', 'Ngưỡng kết quả tối thiểu để hiển thị các kết quả chưa đạt ngưỡng ra logs chỉ số từ <b>0 -> 0.45</b> là hợp lý, chỉ số hợp lý trong khoảng <b>0.35-0.39</b>, chỉ số này cần phải thấp hơn  chỉ số ngưỡng kết quả tối thiểu bên trên', 'border-danger', '', '', '', '', '');
                  echo input_field('hass_time_out', 'Thời gian chờ tối đa (giây)', $Config['home_assistant']['time_out'] ?? 15, 'required', 'number', '1', '5', '60', 'Ngưỡng kết quả tối thiểu để hiển thị các kết quả chưa đạt ngưỡng ra logs chỉ số từ <b>0 -> 0.45</b> là hợp lý, chỉ số hợp lý trong khoảng <b>0.35-0.39</b>, chỉ số này cần phải thấp hơn  chỉ số ngưỡng kết quả tối thiểu bên trên', 'border-success', '', '', '', '', '');
                  echo input_field('', 'Liên Kết Loa VBot Qua HACS Lên Home Assistant (Hass)', 'https://github.com/marion001/VBot_Offline_Custom_Component', 'disabled', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-danger', 'Truy Cập', "https://github.com/marion001/VBot_Offline_Custom_Component", 'btn btn-success border-danger', 'link', '_blank');
                  ?>
				<div class="alert alert-primary" role="alert">
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Lệnh tùy chỉnh <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng câu lệnh tùy chỉnh (Custom Command) cho điều khiển nhà thông minh Home Assistant<br/>- Thiết lập câu lệnh trong: <b>Thiết Lập Nâng Cao -> Home Assistant Customize Command</b>')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="hass_custom_commands_active" id="hass_custom_commands_active" <?php echo $Config['home_assistant']['custom_commands']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
				  <?php
				echo input_field('hass_custom_commands_threshold', 'Ngưỡng kết quả tối thiểu', $Config['home_assistant']['custom_commands']['minimum_threshold'] ?? 0.85, 'required', 'number', '0.01', '0.5', '0.9', 'Ngưỡng kết quả cho phép từ <b>0.1 -> 1</b> ngưỡng càng cao thì yêu cầu độ chính xác cao khi bot tìm kiếm và lọc thiết bị', 'border-success', '', '', '', '', '');
				  ?>
				</div>
                </div>
                </div>
              </div>
			  
            </div>
            <div class="card accordion" id="accordion_button_mqtt_tuyen">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_mqtt_tuyen" aria-expanded="false" aria-controls="collapse_button_mqtt_tuyen">
                  Cấu Hình Kết Nối MQTT Broker:
                </h5>
                <div id="collapse_button_mqtt_tuyen" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_mqtt_tuyen">
                <div class="alert alert-success" role="alert">   <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt sử dụng giao thức MQTT')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="mqtt_active" id="mqtt_active" <?php echo $Config['mqtt_broker']['mqtt_active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Logs MQTT <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để hiển thị logs khi kết nối, mất kết nối MQTT')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="mqtt_show_logs_reconnect" id="mqtt_show_logs_reconnect" <?php echo $Config['mqtt_broker']['mqtt_show_logs_reconnect'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <?php
                  echo input_field('mqtt_host', 'Máy Chủ MQTT ', htmlspecialchars($Config['mqtt_broker']['mqtt_host']), '', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', 'Kiểm Tra', "checkMQTTConnection()", 'btn btn-success border-success', 'onclick', '_blank');
                  echo input_field('mqtt_port', 'Cổng PORT ', htmlspecialchars($Config['mqtt_broker']['mqtt_port'] ?? 1883), '', 'number', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                  echo input_field('mqtt_username', 'Tài Khoản ', htmlspecialchars($Config['mqtt_broker']['mqtt_username']), '', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                  echo input_field('mqtt_password', 'Mật Khẩu ', htmlspecialchars($Config['mqtt_broker']['mqtt_password']), '', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                  echo input_field('mqtt_client_name', 'Tên Client ', htmlspecialchars($Config['mqtt_broker']['mqtt_client_name']), '', 'text', '', '', '', 'Nếu có nhiều hơn 1 thiết bị trong Mạng, bạn cần thay đổi Tên Client cho khác nhau và là duy nhất, ví dụ: <b>VBot1</b> hoặc <b>VBot2</b><br/>Tên Client này sẽ được gắn với <b>state_topic</b> và <b>command_topic</b> trong cấu hình <b>mqtts.yaml</b><br/><br/>Ví dụ tên Client là <b>Vbot1</b>:<br/><b>- state_topic: \'VBot1/switch/mic_on_off/state\'</b> <br/><b>- command_topic: \'VBot1/switch/mic_on_off/set\'</b>', 'border-success', '', '', '', '', '');
                  echo input_field('mqtt_time_out', 'Thời gian chờ (Time Out) (giây)', htmlspecialchars($Config['mqtt_broker']['mqtt_time_out'] ?? 60), '', 'number', '1', '20', '120', 'Thời gian chờ tối đa trong quá trình kết nối, nếu quá thời gian chờ mà không kết nối được thì sẽ thông báo và hệ thống sẽ tự động kết nối lại cho đến khi thành công', 'border-success', '', '', '', '', '');
                  echo input_field('mqtt_connection_waiting_time', 'Thời gian chờ kết nối lại (giây)', htmlspecialchars($Config['mqtt_broker']['mqtt_connection_waiting_time'] ?? 300), '', 'number', '1', '10', '9999', 'Thời gian chờ để kết nối lại khi bị mỗi lần bị mất kết nối hoặc kết nối không thành công, hệ thống sẽ tự động kết nối lại cho đến khi thành công', 'border-success', '', '', '', '', '');
                  echo select_field('mqtt_qos',
                    'QoS <i class="bi bi-question-circle-fill" onclick="show_message(\'- QoS 0 (At most once): Tin nhắn được gửi một lần duy nhất mà không có sự xác nhận. Điều này có thể dẫn đến việc mất tin nhắn nếu có sự cố kết nối<br/><br/>- QoS 1 (At least once): Tin nhắn được gửi ít nhất một lần và sẽ có xác nhận từ phía người nhận. Điều này đảm bảo rằng tin nhắn sẽ đến nơi, nhưng có thể nhận được tin nhắn trùng lặp.<br/><br/>- QoS 2 (Exactly once): Tin nhắn sẽ được gửi một lần duy nhất, không trùng lặp và không bị mất. Đây là mức độ bảo mật cao nhất, nhưng cũng đòi hỏi nhiều tài nguyên hơn và độ trễ cao hơn\')"></i>',
                    ['0' => '0 (At most once)', '1' => '1 (At least once)', '2' => '2 (Exactly once)'],
                    $Config['mqtt_broker']['mqtt_qos'], []);
                  ?>

                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Retain <i class="bi bi-question-circle-fill" onclick="show_message('- retain=True: Khi bạn gửi một tin nhắn với retain=True, MQTT broker sẽ giữ lại tin nhắn đó và gửi lại cho bất kỳ client nào kết nối vào MQTT đó sau này, ngay cả khi client đó đã không nhận dữ liệu ban đầu.<br/><br/>- retain=False: Tin nhắn sẽ không được lưu trữ. Khi client kết nối vào MQTT, nó sẽ không nhận lại tin nhắn cũ')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="mqtt_retain" id="mqtt_retain" <?php echo $Config['mqtt_broker']['mqtt_retain'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <?php
                  echo input_field('', 'Liên Kết Loa VBot Qua HACS Lên Home Assistant (Hass) ', 'https://github.com/marion001/VBot_Offline_Custom_Component', 'disabled', 'text', '', '', '', 'Liên Kết Loa VBot Lên Home Assist Bằng HACS Custom Component', 'border-danger', 'Truy Cập', 'https://github.com/marion001/VBot_Offline_Custom_Component', 'btn btn-success border-danger', 'link', '_blank');
                  ?>
				  <!--
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label" title="Đặt Tên Client Cho Kết Nối MQTT">Hoặc Tạo File Cấu Hình Thủ Công <i class="bi bi-question-circle-fill" onclick="show_message('Hệ thống sẽ tự động tạo các File cấu hình MQTT theo Tên Client mà bạn đã đặt mà không cần chỉnh sửa thủ công, Sao chép toàn bộ nội dung được tạo vào file cấu hình của bạn là xong')"></i> : </label>
                    <div class="col-sm-9">
                      <div class="input-group mb-3">
                        <button class="btn btn-primary border-success" type="button" title="Hiển thị cấu hình mqtts.yaml để liên kết VBot với Home Assistant" onclick="read_YAML_file_path('mqtts.yaml')">mqtts.yaml</button>
                        <button class="btn btn-success border-success" type="button" title="Hiển thị cấu hình mqtts.yaml để liên kết VBot với Home Assistant" onclick="read_YAML_file_path('scripts.yaml')">scripts.yaml</button>
                        <button class="btn btn-secondary border-success" type="button" title="Hiển thị cấu hình mqtts.yaml để liên kết VBot với Home Assistant" onclick="read_YAML_file_path('input_text.yaml')">input_text.yaml</button>
                        <button class="btn btn-info border-success" type="button" title="Hiển thị cấu hình mqtts.yaml để liên kết VBot với Home Assistant" onclick="read_YAML_file_path('lovelace_entities')">lovelace</button>
                      </div>
                    </div>
                  </div>
				  -->
                </div>
              </div>
            </div>
            </div>
            <div class="card accordion" id="accordion_button_setting_led">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_led" aria-expanded="false" aria-controls="collapse_button_setting_led">
                  Cấu Hình Sử Dụng Đèn Led:
                </h5>
                <div id="collapse_button_setting_led" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_led">
                 <div class="alert alert-success" role="alert"> <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích hoạt đèn Led <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng đèn led trạng thái')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="led_active_on_off" id="led_active_on_off" <?php echo $Config['smart_config']['led']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>

                  <?php
                  echo select_field('led_type_select',
                    'Kiểu loại Led <font color="red" size="6" title="Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message(\'Nếu sử dụng LED dây APA102 thì cần hàn chân <b>SDI (MOSI) -> GPIO10</b> và chân <b>CKI (SCLK) -> GPIO11</b>\')"></i>',
                    [
                      'ws281x' => 'WS281x, SK6812, VBot AIO, Vietbot AIO',
                      'apa102' => 'APA102',
                      'ReSpeaker_Mic_Array_v2.0' => 'ReSpeaker Mic Array v2.0',
                      'dev_custom_led' => 'DEV Custom Led: Dev_Led.py (Người dùng tự code)'
                    ],
                    $Config['smart_config']['led']['led_type'], []);
                  echo input_field('led_gpio', 'LED Pin GPIO', htmlspecialchars($Config['smart_config']['led']['led_gpio'] ?? 10), '', 'number', '1', '0', '60', 'Chân Data của LED sẽ được gán và điều khiển bởi chân GPIO, Mặc định GPIO10 (Không thay đổi được)', 'border-success', '', '', '', '', '_blank');
                  echo input_field('number_led', 'Số lượng LED', htmlspecialchars($Config['smart_config']['led']['number_led'] ?? 24), '', 'number', '1', '0', '100', 'Số lượng đèn LED bạn sử dụng (Mỗi mắt LED sẽ là 1)', 'border-success', '', '', '', '', '_blank');
                  echo input_field('led_brightness', 'Độ sáng đèn LED', htmlspecialchars(intval(($Config['smart_config']['led']['brightness'] ?? 255) * 100 / 255)), '', 'number', '1', '0', '100', 'Số lượng đèn LED bạn sử dụng (Mỗi mắt LED sẽ là 1)', 'border-success', '', '', '', '', '_blank');
                  ?>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Ghi Nhớ Độ Sáng Khi Được Thay Đổi <i class="bi bi-question-circle-fill" onclick="show_message('Khi được Bật sẽ lưu lại giá trị độ sáng của đèn LED khi được thay đổi trong lúc Chương Trình đang hoạt động vào Config.json')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="remember_last_brightness" id="remember_last_brightness" <?php echo $Config['smart_config']['led']['remember_last_brightness'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Đảo ngược đầu LED <i class="bi bi-question-circle-fill" onclick="show_message('Đảo ngược đầu (Bắt Đầu) sáng của đèn LED')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="led_invert" id="led_invert" <?php echo $Config['smart_config']['led']['led_invert'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Đèn LED khi khởi động <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng đèn LED báo trạng thái khi trương trình đang khởi chạy')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="led_starting_up" id="led_starting_up" <?php echo $Config['smart_config']['led']['led_starting_up'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <h5 class="card-title">Hiệu Ứng, Màu Sắc:</h5>
                  <div class="row mb-3">
                    <label for="led_think" class="col-sm-3 col-form-label" title="Màu LED Khi Lắng Nghe">LED Think <font color="red" size="6" title="Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Mã màu dạng Hex tương ứng với chế độ')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="input-group">
                        <input class="form-control border-success" type="text" name="led_think" id="led_think" value="<?php echo $Config['smart_config']['led']['effect']['led_think']; ?>">
                        <input class="form-control-color border-success" type="color" id="color_led_think" onchange="updateColorCode_input('color_led_think', 'led_think')" title="Thay đổi màu LED khi được đánh thức">
                        <button class="btn btn-success border-success" type="button" onclick="test_led('led_think')">Kiểm Tra Màu</button>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="led_mute" class="col-sm-3 col-form-label" title="Màu LED khi Microphone được tắt">LED Mute <font color="red" size="6" title="Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Mã màu dạng Hex tương ứng với chế độ')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="input-group">
                        <input class="form-control border-success" type="text" name="led_mute" id="led_mute" value="<?php echo $Config['smart_config']['led']['effect']['led_mute']; ?>">
                        <input class="form-control-color border-success" type="color" id="color_led_mutex" onchange="updateColorCode_input('color_led_mutex', 'led_mute')" title="Thay đổi màu LED khi Mic bị tắt">
                        <button class="btn btn-success border-success" type="button" onclick="test_led('led_mute')">Kiểm Tra Màu</button>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="led_mute" class="col-sm-3 col-form-label" title="Màu LED khi Microphone được tắt">Hiệu Ứng LED Khác:</label>
                    <div class="col-sm-9">
                      <div class="input-group">
                        <select class="form-select border-success" name="get_test_led_selected" id="get_test_led_selected">
                          <option value="">-- Chọn Hiệu Ứng Kiểm Tra/Test LED --</option>
                          <option value="speak">LED_SPEAK (Hiệu Ứng Khi Nói)</option>
                          <option value="pause">LED_PAUSE (Hiệu Ứng Khi Tạm Dừng Phát)</option>
                          <option value="loading">LED_LOADING (Hiệu Ứng Khi Xử Lý Thông Tin, Dữ Liệu)</option>
                          <option value="startup">LED_STARTUP (Hiệu Ứng Khi Đang Khởi Động)</option>
                          <option value="error">LED_ERROR (Hiệu Ứng Khi Có Lỗi Xảy Ra)</option>
                        </select>
                        <button class="btn btn-success border-success" type="button" onclick="get_test_led()">Kiểm Tra Hiệu Ứng</button>
                      </div>
                    </div>
                  </div>
                  <center><button type="button" class="btn btn-danger rounded-pill" name="led_off" id="led_off" value="led_off" onclick="test_led('led_off')">Dừng Kiểm Tra/Dừng Test LED</button></center>
                </div>
              </div>
            </div>
            </div>

            <div class="card accordion" id="accordion_button_multype_button_config">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_multype_button_config" aria-expanded="false" aria-controls="collapse_button_multype_button_config">
                  Cấu Hình Sử Dụng Nút Nhấn:</h5>
				  
                <div id="collapse_button_multype_button_config" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_multype_button_config">
<div class="alert alert-success" role="alert">
                  <div class="card accordion" id="accordion_button_setting_bton">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_bton" aria-expanded="false" aria-controls="collapse_button_setting_bton">
                        Cấu Hình Nút Nhấn Dạng Thường <font color=red> (Nhấn Nhả)</font>:
                      </h5>
                      <div id="collapse_button_setting_bton" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_bton" style="">
                        <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng nút nhấn hoặc không sử dụng, <a href=\'https://github.com/user-attachments/assets/8c43d1fd-bf39-47db-a939-052e6540e074\' target=\'_blank\'>Xem Sơ Đồ Mạch Nút Nhấn</a>')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="button_active" id="button_active" <?php echo $Config['smart_config']['button_active']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <table class="table table-bordered border-primary">
                          <thead>
                            <tr>
                              <th scope="col" colspan="3">
                                <center>
                                  <font color=red>Cấu Hình Chung</font>
                                </center>
                              </th>
                              <th scope="col" colspan="2">
                                <center>
                                  <font color=red>Nhấn Nhả</font>
                                </center>
                              </th>
                              <th scope="col" colspan="2">
                                <center>
                                  <font color=red>Nhấn Giữ</font>
                                </center>
                              </th>
                            </tr>
                            <tr>
                              <th scope="col">
                                <center>
                                  <font color=blue>Nút Nhấn</font>
                                </center>
                              </th>
                              <th scope="col">
                                <center>
                                  <font color=blue>GPIO</font>
                                </center>
                              </th>
                              <th scope="col">
                                <center>
                                  <font color=blue>Kéo mức thấp</font>
                                </center>
                              </th>
                              <th scope="col">
                                <center>
                                  <font color=blue>Kích hoạt</font>
                                </center>
                              </th>
                              <th scope="col">
                                <center>
                                  <font color=blue>Thời gian nhấn (ms)</font>
                                </center>
                              </th>
                              <th scope="col">
                                <center>
                                  <font color=blue>Kích Hoạt</font>
                                </center>
                              </th>
                              <th scope="col">
                                <center>
                                  <font color=blue>Thời Gian Giữ (s)</font>
                                </center>
                              </th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            foreach ($Config['smart_config']['button'] as $buttonName => $buttonData) {
                              echo '<tr>';
                              echo '<th scope="row" style="text-align: center; vertical-align: middle;"><center>' . $buttonName . ':</center></th>';
                              echo '<td style="text-align: center; vertical-align: middle;"><!-- GPIO --><input required type="number" style="width: 90px;" class="form-control border-success" min="1" step="1" max="30" name="button[' . $buttonName . '][gpio]" value="' . $buttonData['gpio'] . '" placeholder="' . $buttonData['gpio'] . '"></center><div class="invalid-feedback">Cần nhập Chân GPIO cho nút nhấn</div></td>';
                              echo '<td style="text-align: center; vertical-align: middle;"><!-- Pulled High --><div class="form-switch"><input type="checkbox" class="form-check-input border-success" name="button[' . $buttonName . '][pulled_high]"' . ($buttonData['pulled_high'] ? ' checked' : '') . '></div></td>';
                              echo '<td style="text-align: center; vertical-align: middle;"><!-- Active nhấn nhả --> <div class="form-switch"><center><input type="checkbox" class="form-check-input border-success" name="button[' . $buttonName . '][active]"' . ($buttonData['active'] ? ' checked' : '') . '></div></td>';
                              echo '<td><center><!-- bounce_time --><input required type="number" min="20" max="500" step="10" style="width: 100px;" class="form-control border-success" title="" name="button[' . $buttonName . '][bounce_time]" value="' . $buttonData['bounce_time'] . '" ></center><div class="invalid-feedback">Cần nhập Chân GPIO cho nút nhấn</div></td>';
                              echo '<td style="text-align: center; vertical-align: middle;"><!-- Active nhấn giữ --><div class="form-switch"><input type="checkbox" class="form-check-input border-success" name="button[' . $buttonName . '][long_press][active]"' . ($buttonData['long_press']['active'] ? ' checked' : '') . '></div></td>';
                              echo '<td><center><!-- Thời gian Giữ --><input required type="number" min="2" step="1" max="10" style="width: 80px;" class="form-control border-success" title="" name="button[' . $buttonName . '][long_press][duration]" value="' . $buttonData['long_press']['duration'] . '" ></center><div class="invalid-feedback">Cần nhập Chân GPIO cho nút nhấn</div></td>';
                              echo '</tr>';
                            }
                            ?>
                          </tbody>
                        </table>
                  <div class="row mb-3">
                    <b class="text-danger">Sơ Đồ 4 Nút Nhấn Nhả: <a href="https://github.com/user-attachments/assets/8c43d1fd-bf39-47db-a939-052e6540e074" target="_blank">Nhấn Vào Đây Để Xem Sơ Đồ</a></b>
                  </div>
                      </div>
                      </div>
                    </div>
                  </div>

                  <div class="card accordion" id="accordion_button_Encoder_Rotary">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_Encoder_Rotary" aria-expanded="false" aria-controls="collapse_button_Encoder_Rotary">
                        Cấu Hình Nút Nhấn Dạng Xoay <font color=red> (Sử Dụng Encoder Rotary)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Sử Dụng Nút Nhấn Dạng Xoay Encoder, Tương Thích Với Các Module Encoder Có 5 Chân Trên Thị Trường Như:  <b>KY-040 RV09 EC11</b><br/>- Khuyến Nghị Chỉ Nên Kích Hoạt Sử Dụng 1 Trong 2 Kiểu Nút Nhấn Là: <b> Nút Nhấn Dạng Xoay Encoder</b> Hoặc <b>Nút Nhấn Nhả Dạng Thường</b>')"></i>:</h5>
                      <div id="collapse_button_Encoder_Rotary" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_Encoder_Rotary">
                        <div class="alert alert-info" role="alert"> <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng nút nhấn dạng Encoder Rotary hoặc không sử dụng')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="encoder_rotary_active" id="encoder_rotary_active" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>

                        <table class="table table-bordered border-primary">
                          <thead>

                            <tr>
                              <th scope="col" colspan="2" style="text-align: center; vertical-align: middle;">
                                <font color="red">Cấu hình Encoder</font>
                              </th>
                              <th scope="col" colspan="4" style="text-align: center; vertical-align: middle;">
                                <font color="red">Chức Năng Nút Nhấn SW, KEY</font>
                              </th>
                            </tr>

                          </thead>
                          <tbody>
                            <tr>
                              <th scope="row" style="text-align: center; vertical-align: middle;">
                                <font color="blue">CLK, S1 = GPIO:</font>
                              </th>
                              <td><input class="form-control border-success" step="1" min="1" max="35" type="number" name="encoder_rotary_gpio_clk" id="encoder_rotary_gpio_clk" value="<?php echo $Config['smart_config']['button_active']['encoder_rotary']['gpio_clk']; ?>"></td>
                              <th colspan="2" scope="col" style="text-align: center; vertical-align: middle;">
                                <font color="red">Nhấn Nhả</font>
                              </th>
                              <th colspan="2" scope="col" style="text-align: center; vertical-align: middle;">
                                <font color="red">Nhấn Giữ </font>
                              </th>
                            </tr>
                            <tr>
                              <th scope="row" style="text-align: center; vertical-align: middle;">
                                <font color="blue">DT, S2 = GPIO:</font>
                              </th>
                              <td><input class="form-control border-success" step="1" min="1" max="35" type="number" name="encoder_rotary_gpio_dt" id="encoder_rotary_gpio_dt" value="<?php echo $Config['smart_config']['button_active']['encoder_rotary']['gpio_dt']; ?>"></td>
                              <th scope="col" style="text-align: center; vertical-align: middle;">Thời Gian Nhấn (ms):</th>
                              <td><input class="form-control border-success" step="1" min="1" max="1000" type="number" name="encoder_rotary_bounce_time_sw" id="encoder_rotary_bounce_time_sw" value="<?php echo $Config['smart_config']['button_active']['encoder_rotary']['bounce_time_gpio_sw']; ?>"></td>
                              <th scope="col" style="text-align: center; vertical-align: middle;">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng tính năng Nhấn Giữ')"></i>:</th>
                              <td scope="col" style="text-align: center; vertical-align: middle;">
                                <div class="form-switch">
                                  <input class="form-check-input border-success" type="checkbox" name="ncoder_rotary_long_press_active" id="ncoder_rotary_long_press_active" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['active'] ? 'checked' : ''; ?>>
                                </div>
                              </td>
                            </tr>
                            <tr>
                              <th scope="row" style="text-align: center; vertical-align: middle;">
                                <font color="blue">SW, KEY = GPIO:</font>
                              </th>
                              <td><input class="form-control border-success" step="1" min="1" max="35" type="number" name="encoder_rotary_gpio_ws" id="encoder_rotary_gpio_ws" value="<?php echo $Config['smart_config']['button_active']['encoder_rotary']['gpio_sw']; ?>"></td>
                              <th style="text-align: center; vertical-align: middle;">Hành Động:</th>
                              <td>
                                <select name="encoder_rotary_action_gpio_sw" id="encoder_rotary_action_gpio_sw" class="form-select border-success" aria-label="Default select example">
                                  <option value="wakeup" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['action_gpio_sw'] === 'wakeup' ? 'selected' : ''; ?>>Đánh Thức (WakeUP)</option>
                                  <option value="mic" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['action_gpio_sw'] === 'mic' ? 'selected' : ''; ?>>Bật/Tắt Mic</option>
                                </select>
                              </td>
                              <th style="text-align: center; vertical-align: middle;">Thời Gian Giữ (s):</th>
                              <td>
                                <input class="form-control border-success" step="1" min="1" max="10" type="number" name="encoder_rotary_long_press_duration" id="encoder_rotary_long_press_duration" value="<?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['duration']; ?>">
                              </td>
                            </tr>
                            <tr>
                              <th scope="row" style="text-align: center; vertical-align: middle;">
                                <font color="blue">Bước Xoay, Step:</font>
                              </th>
                              <td><input class="form-control border-success" step="1" min="1" max="10" type="number" name="encoder_rotary_gpio_step" id="encoder_rotary_gpio_step" value="<?php echo $Config['smart_config']['button_active']['encoder_rotary']['rotating_step']; ?>"></td>
                              <td colspan="2"></td>

                              <th style="text-align: center; vertical-align: middle;">Hành Động <i class="bi bi-question-circle-fill" onclick="show_message('Các Thao Tác Nâng Cao Khác Khi Nhấn Giữ Bạn Có Thể Tham Khảo Tại Đây: <a href=\'FAQ.php\' target=\'_bank\'>Hướng Dẫn</a>')"></i>:</th>
                              <td>
                                <select name="encoder_rotary_long_press_action_gpio_sw" id="encoder_rotary_long_press_action_gpio_sw" class="form-select border-success" aria-label="Default select example">
                                  <option value="wakeup" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] === 'wakeup' ? 'selected' : ''; ?>>Đánh Thức (WakeUP)</option>
                                  <option value="mic" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] === 'mic' ? 'selected' : ''; ?>>Bật/Tắt Mic</option>
                                  <option value="conversation_mode" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] === 'conversation_mode' ? 'selected' : ''; ?>>Bật/Tắt Chế Độ Hội Thoại</option>
                                  <option value="play_playlist" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] === 'play_playlist' ? 'selected' : ''; ?>>Phát Danh Sách Nhạc</option>
                                  <option value="pause_media" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] === 'pause_media' ? 'selected' : ''; ?>>Tạm Dừng Phát</option>
                                  <option value="stop_media_player" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] === 'stop_media_player' ? 'selected' : ''; ?>>Dừng Phát Nhạc</option>
                                  <option value="restart_vbot" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] === 'restart_vbot' ? 'selected' : ''; ?>>Restart VBot</option>
                                  <option value="reboot_os" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['long_press_gpio_sw']['action_gpio_sw'] === 'reboot_os' ? 'selected' : ''; ?>>Reboot OS</option>
                                </select>
                              </td>
                            </tr>

                            <tr>
                              <th colspan="1" style="text-align: center; vertical-align: middle;">
                                <font color="blue">Hiển Thị Logs Khi Xoay:</font>
                              </th>
                              <td colspan="1" style="text-align: center; vertical-align: middle;">
                                <div class="form-switch">
                                  <input class="form-check-input border-success" type="checkbox" name="encoder_rotating_show_logs" id="encoder_rotating_show_logs" <?php echo $Config['smart_config']['button_active']['encoder_rotary']['rotating_show_logs'] ? 'checked' : ''; ?>>
                                </div>
                              </td>
                              <td colspan="4">
                              </td>
                            </tr>
                          </tbody>
                        </table>

                      </div>
                      </div>
                    </div>
                  </div>

                </div>
                </div>
              </div>
            </div>

            <div class="card accordion" id="accordion_button_Sound_System">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_Sound_System" aria-expanded="false" aria-controls="collapse_button_Sound_System">
                  Âm Thanh Hệ Thống:
                </h5>
                <div id="collapse_button_Sound_System" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_Sound_System" style="">
                <div class="alert alert-success" role="alert"> <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Âm Thanh Khi Khởi Động <i class="bi bi-question-circle-fill" onclick="show_message('Âm thanh thông báo khi chương trình khởi chạy thành công')"></i> :</h5>
                      <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt âm thanh thông báo khi chương trình khởi động')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="sound_welcome_active" id="sound_welcome_active" <?php echo $Config['smart_config']['smart_wakeup']['sound']['welcome']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <label for="sound_welcome_file_path" class="col-sm-3 col-form-label">File âm thanh:</label>
                        <div class="col-sm-9">
                          <div class="input-group">
                            <?php
                            $Audio_welcome_file = $Config['smart_config']['smart_wakeup']['sound']['welcome']['welcome_file'];
                            if ($handle = opendir($VBot_Offline . 'resource/sound/welcome')) {
                              $audio_files = [];
                              while (false !== ($entry = readdir($handle))) {
                                $file_parts = pathinfo($entry);
                                $extension = isset($file_parts['extension']) ? strtolower($file_parts['extension']) : '';
                                if (in_array($extension, $Allowed_Extensions_Audio)) {
                                  $audio_files[] = $entry;
                                }
                              }
                              closedir($handle);
                              echo '<select name="sound_welcome_file_path" id="sound_welcome_file_path" class="form-select border-success">';
                              foreach ($audio_files as $file) {
                                $selected = ($Audio_welcome_file === 'resource/sound/welcome/' . $file) ? ' selected' : '';
                                echo '<option value="' . htmlspecialchars('resource/sound/welcome/' . $file) . '"' . $selected . '>' . htmlspecialchars($file) . '</option>';
                              }
                              echo '</select>';
                            } else {
                              echo "<script>showMessagePHP('Không thể mở thư mục welcome');</script>";
                            }
                            ?>
                            <button class="btn btn-success border-success" id="play_Audio_Welcome" type="button"><i class="bi bi-play-circle"></i></button>
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
                  </div>
                </div>
              </div>
            </div>
            </div>
			
            <div class="card accordion" id="accordion_button_media_player">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player" aria-expanded="false" aria-controls="collapse_button_media_player">
                  Cấu Hình Phát Nhạc, Câu Trả Lời - Media Player:
                </h5>
                <div id="collapse_button_media_player" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_media_player" style="">
                 <div class="alert alert-success" role="alert"> <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Kích hoạt Media Player <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt Để kích hoạt sử dụng trình phát nhạc Media Player<br/>Khi được tắt sẽ không ra lệnh phát được Bài Hát, PodCast, Radio')"></i> :</h5>
                     <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt: </label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="media_player_active" id="media_player_active" <?php echo $Config['media_player']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Cho Phép Đánh Thức Khi Đang Phát Media player <i class="bi bi-question-circle-fill" onclick="show_message('Lưu Ý: Tính năng này được bật có thể gây nhiễu Hotword tự động đánh thức, Nên tắt để tránh nhiễu tạp âm từ loa thu vào MIC')"></i>: </label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="wake_up_in_media_player" id="wake_up_in_media_player" <?php echo $Config['media_player']['wake_up_in_media_player'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                    </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">PlayList (Danh Sách Phát) <i class="bi bi-question-circle-fill"></i> :</h5>
					  <div class="alert alert-primary" role="alert">
                      <?php
                      echo select_field('newspaper_play_mode', 'Nguồn Báo, Tin Tức', ['' => '-- Chọn Chế Độ Phát --', 'random' => 'random (Ngẫu nhiên)', 'sequential' => 'sequential (Tuần tự)'], $Config['media_player']['play_list']['newspaper_play_mode'], []);
                      echo select_field('music_play_mode', 'Nguồn Âm Nhạc', ['' => '-- Chọn Chế Độ Phát --', 'random' => 'random (Ngẫu nhiên)', 'sequential' => 'sequential (Tuần tự)'], $Config['media_player']['play_list']['music_play_mode'], []);
                      ?>
                    </div>
                  </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Đồng bộ trạng thái Media với Web UI <i class="bi bi-question-circle-fill" onclick="show_message('Chế độ đồng bộ này sẽ sử dụng giao tiếp qua API, nếu bạn tắt Kích Hoạt APi thì sẽ không đồng bộ được nhé')"></i> :</h5>
                     <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt tự động đồng bộ khi truy cập vào Web UI')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="media_sync_ui" id="media_sync_ui" <?php echo $Config['media_player']['media_sync_ui']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      echo input_field('media_sync_ui_delay_time', 'Thời gian trễ (giây)', htmlspecialchars($Config['media_player']['media_sync_ui']['delay_time'] ?? 2), 'required', 'number', '1', '1', '10', 'Thời gian mỗi lần đồng bộ (thường sẽ là 1, mỗi 1 giây sẽ tự động đồng bộ 1 lần)', 'border-success', '', '', '', '', '');
                      ?>
                    </div>
                  </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Ưu tiên nguồn phát/tìm kiếm Media <i class="bi bi-question-circle-fill" onclick="show_message('Ưu tiên nguồn tìm kiếm bài hát khi Bot xử lý dữ liệu. (xử lý lần lượt theo thứ tự khi nguồn trước đó không có kết quả)')"></i> :</h5>
                      <div class="alert alert-primary" role="alert"> 
					  <?php
						echo select_field(
							'music_source_priority1',
							'Top 1',
							['' => '-- Chọn Nguồn Phát --', 'music_local' => 'Music Local', 'zing_mp3' => 'ZingMP3', 'nhaccuatui' => 'NhacCuaTui - NCT', 'youtube' => 'Youtube', 'dev_custom_music' => 'DEV Custom Music'],
							isset($Config['media_player']['prioritize_music_source'][0]) ? $Config['media_player']['prioritize_music_source'][0] : '',
							[]
						);
						echo select_field(
							'music_source_priority2',
							'Top 2',
							['' => '-- Chọn Nguồn Phát --', 'music_local' => 'Music Local', 'zing_mp3' => 'ZingMP3', 'nhaccuatui' => 'NhacCuaTui - NCT', 'youtube' => 'Youtube', 'dev_custom_music' => 'DEV Custom Music'],
							isset($Config['media_player']['prioritize_music_source'][1]) ? $Config['media_player']['prioritize_music_source'][1] : '',
							[]
						);
						echo select_field(
							'music_source_priority3',
							'Top 3',
							['' => '-- Chọn Nguồn Phát --', 'music_local' => 'Music Local', 'zing_mp3' => 'ZingMP3', 'nhaccuatui' => 'NhacCuaTui - NCT', 'youtube' => 'Youtube', 'dev_custom_music' => 'DEV Custom Music'],
							isset($Config['media_player']['prioritize_music_source'][2]) ? $Config['media_player']['prioritize_music_source'][2] : '',
							[]
						);
						echo select_field(
							'music_source_priority4',
							'Top 4',
							['' => '-- Chọn Nguồn Phát --', 'music_local' => 'Music Local', 'zing_mp3' => 'ZingMP3', 'nhaccuatui' => 'NhacCuaTui - NCT', 'youtube' => 'Youtube', 'dev_custom_music' => 'DEV Custom Music'],
							isset($Config['media_player']['prioritize_music_source'][3]) ? $Config['media_player']['prioritize_music_source'][3] : '',
							[]
						);
						echo select_field(
							'music_source_priority5',
							'Top 5',
							['' => '-- Chọn Nguồn Phát --', 'music_local' => 'Music Local', 'zing_mp3' => 'ZingMP3', 'nhaccuatui' => 'NhacCuaTui - NCT', 'youtube' => 'Youtube', 'dev_custom_music' => 'DEV Custom Music'],
							isset($Config['media_player']['prioritize_music_source'][4]) ? $Config['media_player']['prioritize_music_source'][4] : '',
							[]
						);
                      ?>
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
                  Nguồn Phát Media Player: Nhạc, Radio, Kể Truyện, PodCast, Đọc Báo Tin tức:
                </h5>
                <div id="collapse_button_media_player_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_media_player_source">
                 <div class="alert alert-success" role="alert">  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Zing MP3:</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc Zing MP3')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="zing_mp3_active" id="zing_mp3_active" <?php echo $Config['media_player']['zing_mp3']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                    </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">NhacCuaTui - NCT:</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc NhacCuaTui - NCT')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="nhaccuatui_active" id="nhaccuatui_active" <?php echo $Config['media_player']['nhaccuatui']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Youtube:</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc Youtube')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="youtube_active" id="youtube_active" <?php echo $Config['media_player']['youtube']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      echo input_field('youtube_google_apis_key', 'Youtube Google Apis Key', htmlspecialchars($Config['media_player']['youtube']['google_apis_key']), 'readonly', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                      ?>
                    </div>
                  </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Music Local:</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc Local')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="music_local_active" id="music_local_active" <?php echo $Config['media_player']['music_local']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      echo input_field('music_local_path', 'Đường dẫn thư mục', htmlspecialchars($Config['media_player']['music_local']['path']), 'readonly', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                      echo input_field('music_local_minimum_threshold', 'Ngưỡng kết quả tối thiểu', htmlspecialchars($Config['media_player']['music_local']['minimum_threshold'] ?? 0.6), 'required', 'number', '0.01', '0.4', '0.9', '', 'border-success', '', '', '', '', '');
                      ?>
                      <?php
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
                  </div>

                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">DEV Custom Music: Dev_Music.py <font color=red>(Người Dùng Tự Code)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Người dùng sẽ tự code bổ sung thêm nguồn cung cấp dữ liệu nhạc, bài hát theo ý muốn, Bạn sẽ cần code ở file: Dev_Music.py')"></i> :</h5>
                      <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc do người dùng tự code, bạn sẽ cần code ở file: Dev_Music.py')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="dev_custom_music_active" id="dev_custom_music_active" <?php echo $Config['media_player']['dev_custom_music']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  </div>

                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Đọc Truyện, Kể Truyện, PodCast:</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng Đọc Truyện')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="podcast_active" id="podcast_active" <?php echo $Config['media_player']['podcast']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Cho phép dùng ưu tiên trợ lý ảo <i class="bi bi-question-circle-fill" onclick="show_message('Khi được Kích hoạt, sẽ sử dụng dữ liệu từ chế độ: Ưu tiên trợ lý ảo')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="podcast_virtual_assistants_active" id="podcast_virtual_assistants_active" <?php echo $Config['media_player']['podcast']['allows_priority_use_of_virtual_assistants'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  </div>

                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Đài, Radio:</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng nguồn phát radio')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="radio_active" id="radio_active" <?php echo $Config['media_player']['radio']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      $radio_data = $Config['media_player']['radio_data'];
                      ?>
                      <table class="table table-bordered border-primary" id="radio-table">
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
							<select class="form-select border-warning" id="radio_select_<?php echo $index; ?>" style="display:none;" onchange="updateRadioLinkName('<?php echo $index; ?>', this)"></select>
						  <div class="input-group mb-3" id="link-group-<?php echo $index; ?>">
							<input type="text" class="form-control border-success" name="radio_link_<?php echo $index; ?>" id="radio_link_<?php echo $index; ?>" value="<?php echo htmlspecialchars($radio['link']); ?>">
							<button type="button" class="btn btn-primary" onclick="showRadioSelect('<?php echo $index; ?>', '<?php echo $HTML_VBot_Offline; ?>')">
							  <i class="bi bi-list-task"></i>
							</button>
						  </div>
						</td>
						  <td>
							<center>
							  <button type="button" class="btn btn-danger" onclick="delete_Dai_bao_Radio('<?php echo $index; ?>', '<?php echo htmlspecialchars($radio['name']); ?>')">
								<i class="bi bi-trash" type="button" title="Xóa đài: <?php echo htmlspecialchars($radio['name']); ?>"></i>
							  </button>
							</center>
						  </td>
						</tr>
						<?php } ?>
                        </tbody>
                      </table>
					  <?php
					  echo input_field('lits_newspaper_radio_radio', 'Đường Dẫn Tệp Dữ Liệu', htmlspecialchars($directory_path.'/includes/other_data/lits_newspaper_radio.json'), 'disabled', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
					  ?>
                      <center> <button type="button" class="btn btn-success rounded-pill" id="add-radio" onclick="addRadio()">Thêm Đài Mới</button></center>
                    </div>
                  </div>
                  </div>

                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">
                        Đọc Báo, Tin Tức
                        <i class="bi bi-question-circle-fill"
                          onclick="show_message('Câu Lệnh: Ví dụ Các link/url báo được hỗ trợ:<br/><br>\
                            - https://podcast.tuoitre.vn<br>\
                            - https://vnexpress.net/podcast<br/>\
                            - https://vnexpress.net/podcast/vnexpress-hom-nay<br/>\
                            - https://vietnamnet.vn/podcast/ban-tin-thoi-su<br/>\
                            - https://baomoi.com/audio/thoi-su-3.epi<br/>\
                            - https://tienphong.vn/podcast/<br/>\
                            ')">
                        </i>:</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng nguồn phát radio')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="news_paper_active" id="news_paper_active" <?php echo $Config['media_player']['news_paper']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      // Get Dữ liệu newspaper từ cấu hình
                      $newspaper_data = $Config['media_player']['news_paper_data'];
                      ?>
                      <table class="table table-bordered border-primary" id="newspaper-table">
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
							<select class="form-select border-warning" id="newspaper_select_<?php echo $index_newspaper; ?>" style="display:none;" onchange="updateNewsPaperLinkName('<?php echo $index_newspaper; ?>', this)"></select>
						  <div class="input-group mb-3" id="newspaper-link-group-<?php echo $index_newspaper; ?>">
							<input type="text" class="form-control border-success" name="newspaper_link_<?php echo $index_newspaper; ?>" id="newspaper_link_<?php echo $index_newspaper; ?>" value="<?php echo htmlspecialchars($newspaper['link']); ?>">
							<button type="button" class="btn btn-primary" onclick="showNewsPaperSelect('<?php echo $index_newspaper; ?>', '<?php echo $HTML_VBot_Offline; ?>')">
							  <i class="bi bi-list-task"></i>
							</button>
						  </div>
						</td>
                              <td>
                                <center>
                                  <button type="button" class="btn btn-danger" onclick="delete_NewsPaper('<?php echo $index_newspaper; ?>', '<?php echo htmlspecialchars($newspaper['name']); ?>')">
                                    <i class="bi bi-trash" type="button" title="Xóa Báo, Tin Tức: <?php echo htmlspecialchars($newspaper['name']); ?>"></i>
                                  </button>
                                </center>
                              </td>
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
					  <?php
					  echo input_field('lits_newspaper_radio_paper', 'Đường Dẫn Tệp Dữ Liệu', htmlspecialchars($directory_path.'/includes/other_data/lits_newspaper_radio.json'), 'disabled', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
					  ?>
                      <center> <button type="button" class="btn btn-success rounded-pill" id="add-newspaper" onclick="addNewsPaper()">Thêm Báo Mới</button></center>
                    </div>
                  </div>
                  </div>
                </div>
              </div>
            </div>
            </div>

            <div class="card accordion" id="accordion_button_virtual_assistant">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_virtual_assistant" aria-expanded="false" aria-controls="collapse_button_virtual_assistant">
                  Cấu Hình Trợ Lý Ảo/Assistant:
                </h5>
                <div id="collapse_button_virtual_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_virtual_assistant" style="">
                  <div class="card">
				  
                    <div class="card-body"><div class="alert alert-success" role="alert">
                      <h5 class="card-title">Ưu tiên trợ lý ảo:</h5>
                      <?php
                      //Get Ưu tiên Nguồn Phát
                      $virtual_assistant_priority = $Config['virtual_assistant']['prioritize_virtual_assistants'];
                      $assistant_options = [
                        "default_assistant" => "Default Assistant",
                        "olli" => "Olli AI Assistant (Khuyến Nghị)",
                        "google_gemini" => "Google Gemini",
                        "chat_gpt" => "Chat GPT",
                        "zalo_assistant" => "Zalo AI Assistant",
                        "dify_ai" => "Dify AI Assistant",
                        "xiaozhi" => "XiaoZhi AI (Khuyến Nghị)",
                        "customize_developer_assistant" => "DEV Custom Assistant: Dev_Assistant.py (Người Dùng Tự Code)"
                      ];
                      for ($i = 0; $i < 5; $i++) {
                        $label = "Top " . ($i + 1);
                        $select_name = "virtual_assistant_priority" . ($i + 1);
                        $selected_value = $virtual_assistant_priority[$i] ?? '';
                        echo '<div class="row mb-3">';
                        echo '<label for="' . $select_name . '" class="col-sm-3 col-form-label">' . $label . ':</label>';
                        echo '<div class="col-sm-9">';
                        echo '<select class="form-select border-success" name="' . $select_name . '" id="' . $select_name . '">';
                        echo '<option value="">-- Chọn Trợ Lý --</option>';
                        foreach ($assistant_options as $value => $label_option) {
                          $selected = ($selected_value === $value) ? "selected" : "";
                          echo '<option value="' . $value . '" ' . $selected . '>' . $label_option . '</option>';
                        }
                        echo '</select>';
                        echo '</div>';
                        echo '</div>';
                      }
                      ?>
                    </div>
                    </div>
                  </div>

                  <div class="card accordion" id="accordion_button_cfg_default_assistant">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cfg_default_assistant" aria-expanded="false" aria-controls="collapse_button_cfg_default_assistant">
                        Cấu Hình Trợ Lý => Default Assistant <i class="bi bi-question-circle-fill" onclick="show_message('Trợ lý ảo mang tên Default Assistant')"></i>:</h5>
                      <div id="collapse_button_cfg_default_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cfg_default_assistant">
					  <div class="alert alert-primary" role="alert">
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Default Assistant<br/>- Phiên ID Chat của trợ lý này sẽ được tạo mới mỗi khi chương trình VBot được khởi động')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="default_assistant_active" id="default_assistant_active" <?php echo $Config['virtual_assistant']['default_assistant']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <?php
                        echo input_field('default_assistant_time_out', 'Thời gian chờ (giây)', htmlspecialchars($Config['virtual_assistant']['default_assistant']['time_out'] ?? 10), 'required', 'number', '1', '5', '90', 'Thời gian chờ phản hồi tối đa (Giây)', 'border-success', '', '', '', '', '');
                        ?>
                        <div class="card-body">
                          <h5 class="card-title">Chuyển đổi thêm kết quả từ âm thanh thành văn bản (text) <i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi này chỉ áp dụng với trợ lý ảo Default Assistant')"></i> :</h5>
                          <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Áp dụng với Chatbot <i class="bi bi-question-circle-fill" onclick="show_message('khi được tắt dữ liệu trả về sẽ là file âm thanh, khi được bật sẽ trả về dữ liệu là file âm thanh và văn bản (text)<br/>Cân nhắc khi được bật thời gian xử lý sẽ lâu hơn')"></i> :</label>
                            <div class="col-sm-9">
                              <div class="form-switch">
                                <input class="form-check-input border-success" type="checkbox" name="default_assistant_convert_audio_to_text_used_for_chatbox" id="default_assistant_convert_audio_to_text_used_for_chatbox" <?php echo $Config['virtual_assistant']['default_assistant']['convert_audio_to_text']['used_for_chatbox'] ? 'checked' : ''; ?>>
                              </div>
                            </div>
                          </div>
                          <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Áp dụng với Hệ thống, Console, Logs, Logs API <i class="bi bi-question-circle-fill" onclick="show_message('khi được tắt dữ liệu trả về sẽ là file âm thanh, khi được bật sẽ trả về dữ liệu là file âm thanh và văn bản (text)<br/>Cân nhắc khi được bật thời gian xử lý sẽ lâu hơn')"></i> :</label>
                            <div class="col-sm-9">
                              <div class="form-switch">
                                <input class="form-check-input border-success" type="checkbox" name="default_assistant_convert_audio_to_text_used_for_display_and_logs" id="default_assistant_convert_audio_to_text_used_for_display_and_logs" <?php echo $Config['virtual_assistant']['default_assistant']['convert_audio_to_text']['used_for_display_and_logs'] ? 'checked' : ''; ?>>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      </div>
                    </div>
                  </div>

                  <div class="card accordion" id="accordion_button_cfg_zaloai_assistant">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cfg_zaloai_assistant" aria-expanded="false" aria-controls="collapse_button_cfg_zaloai_assistant">
                        Cấu Hình Trợ Lý => Zalo AI Assistant:</h5>
                      <div id="collapse_button_cfg_zaloai_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cfg_zaloai_assistant">
                       <div class="alert alert-info" role="alert"> <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Zalo Assistant<br/>- Phiên ID Chat của trợ lý này sẽ được tạo mới mỗi khi chương trình VBot được khởi động')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="zalo_assistant_active" id="zalo_assistant_active" <?php echo $Config['virtual_assistant']['zalo_assistant']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <?php
                        echo input_field('zalo_assistant_time_out', 'Thời gian chờ (giây)', htmlspecialchars($Config['virtual_assistant']['zalo_assistant']['time_out'] ?? 15), 'required', 'number', '1', '5', '30', 'Thời gian chờ phản hồi tối đa (Giây)', 'border-success', '', '', '', '', '');
                        echo input_field('zalo_assistant_set_expiration_time', 'Đặt thời gian hết hạn Token (giây)', htmlspecialchars($Config['virtual_assistant']['zalo_assistant']['set_expiration_time'] ?? 86400), 'required', 'number', '1', '21600', '604800', 'Đặt thời gian hết hạn cho token trung bình đặt 1 ngày tham số tính bằng giây: 86400', 'border-success', '', '', '', '', '');
                        ?>
                      </div>
                    </div>
                    </div>
                  </div>

                  <div class="card accordion" id="accordion_button_cfg_olliai_assistant">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cfg_olliai_assistant" aria-expanded="false" aria-controls="collapse_button_cfg_olliai_assistant">
                        Cấu Hình Trợ Lý => Olli AI Assistant <i class="bi bi-question-circle-fill" onclick="show_message('Bạn cần đăng ký tài khoản Trên APP: Maika để sử dụng<br/>- Có thể dùng địa chỉ Email hoặc SĐT đã được đăng ký để điền vào ô bên dưới')"></i>:</h5>
                      <div id="collapse_button_cfg_olliai_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cfg_olliai_assistant">
                        <div class="alert alert-warning" role="alert"> <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Olli AI Assistant')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="olli_assistant_active" id="olli_assistant_active" <?php echo $Config['virtual_assistant']['olli']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <?php
                        echo input_field('olli_assistant_username', 'Tài Khoản', htmlspecialchars($Config['virtual_assistant']['olli']['username']), '', 'text', '', '', '', 'Tài Khoản Đăng Nhập được tạo Trên APP Maika<br/>- Có thể dùng địa chỉ Email hoặc SĐT đã được đăng ký', 'border-success', '', '', '', '', '');
                        echo input_field('olli_assistant_password', 'Mật Khẩu', htmlspecialchars($Config['virtual_assistant']['olli']['password']), '', 'text', '', '', '', 'Mật Khẩu Đăng Nhập được tạo Trên APP Maika<br/>- Có thể dùng địa chỉ Email hoặc SĐT đã được đăng ký', 'border-success', '', '', '', '', '');
                        ?>
                        <div class="row mb-3"><label class="col-sm-3 col-form-label"></label>
                          <div class="col-sm-9">
                            <center><button class="btn btn-success border-success" type="button" onclick="check_info_login_olli()">Kiểm Tra Kết Nối</button></center>
                          </div>
                        </div>
                        <?php
                        echo select_field('olli_assistant_voice_name', 'Giọng Đọc <font color="red" size="6" title="Bắt Buộc Nhập">*</font>', ['vn_north' => 'Giọng Miền Bắc', 'vn_south' => 'Giọng Miền Nam'], $Config['virtual_assistant']['olli']['voice_name'], []);
                        echo input_field('olli_assistant_time_out', 'Thời gian chờ tối đa (giây)', htmlspecialchars($Config['virtual_assistant']['olli']['time_out'] ?? 10), 'required', 'number', '1', '5', '30', 'Thời gian chờ phản hồi tối đa (Giây)', 'border-success', '', '', '', '', '');
                        ?>
                        <div class="card-body">
                          <h5 class="card-title">Chuyển đổi thêm kết quả từ âm thanh thành văn bản (text) <i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi này chỉ áp dụng với trợ lý ảo Olli AI Assistant')"></i> :</h5>
                          <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Áp dụng với Chatbot <i class="bi bi-question-circle-fill" onclick="show_message('khi được tắt dữ liệu trả về sẽ là file âm thanh, khi được bật sẽ trả về dữ liệu là file âm thanh và văn bản (text)<br/>Cân nhắc khi được bật thời gian xử lý sẽ lâu hơn')"></i> :</label>
                            <div class="col-sm-9">
                              <div class="form-switch">
                                <input class="form-check-input border-success" type="checkbox" name="olli_convert_audio_to_text_used_for_chatbox" id="olli_convert_audio_to_text_used_for_chatbox" <?php echo $Config['virtual_assistant']['olli']['convert_audio_to_text']['used_for_chatbox'] ? 'checked' : ''; ?>>
                              </div>
                            </div>
                          </div>
                          <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Áp dụng với Hệ thống, Console, Logs, Logs API <i class="bi bi-question-circle-fill" onclick="show_message('khi được tắt dữ liệu trả về sẽ là file âm thanh, khi được bật sẽ trả về dữ liệu là file âm thanh và văn bản (text)<br/>Cân nhắc khi được bật thời gian xử lý sẽ lâu hơn')"></i> :</label>
                            <div class="col-sm-9">
                              <div class="form-switch">
                                <input class="form-check-input border-success" type="checkbox" name="olli_convert_audio_to_text_used_for_display_and_logs" id="olli_convert_audio_to_text_used_for_display_and_logs" <?php echo $Config['virtual_assistant']['olli']['convert_audio_to_text']['used_for_display_and_logs'] ? 'checked' : ''; ?>>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    </div>
                  </div>

                  <div class="card accordion" id="accordion_button_cfg_gemini_assistant">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cfg_gemini_assistant" aria-expanded="false" aria-controls="collapse_button_cfg_gemini_assistant">
                        Cấu Hình Trợ Lý => Google Gemini <i class="bi bi-question-circle-fill" onclick="show_message('Lấy Key/Api: <a href=\'https://aistudio.google.com/app/apikey\' target=\'_bank\'>https://aistudio.google.com/app/apikey</a> ')"></i>:</h5>
                      <div id="collapse_button_cfg_gemini_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cfg_gemini_assistant">
                        <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Gemini')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="google_gemini_active" id="google_gemini_active" <?php echo $Config['virtual_assistant']['google_gemini']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <?php
                        echo input_field('google_gemini_key', 'Api Keys', htmlspecialchars($Config['virtual_assistant']['google_gemini']['api_key']), '', 'text', '', '', '', 'Lấy Key API Google Gemini: <a href="https://aistudio.google.com/api-keys" target="_blank">https://aistudio.google.com/api-keys</a>', 'border-success', 'Kiểm Tra', "test_key_Gemini('xin chào')", 'btn btn-success border-success', 'onclick', '_blank');
                        $gemini_model_list_json_file = $HTML_VBot_Offline . '/includes/other_data/gemini_model_list.json';
                        $selected_model = $Config['virtual_assistant']['google_gemini']['model_name'] ?? 'gemini-2.0-flash';
                        $selected_version = $Config['virtual_assistant']['google_gemini']['api_version'] ?? 'v1beta';
                        function renderSelectOrInput_Gemini($gemini_model_list_json_file, $id, $label, $data, $selectedValue, $placeholder = '', $withButton = false)
                        {
                          echo '<div class="row mb-3">';
                          echo '<label for="' . $id . '" class="col-sm-3 col-form-label">' . htmlspecialchars($label) . ':</label>';
                          echo '<div class="col-sm-9">';
                          if (is_array($data) && !empty($data)) {
                            //Select
                            if ($withButton) {
                              echo '<div class="input-group">';
                              echo '<select name="' . $id . '" id="' . $id . '" class="form-select border-danger" aria-label="Default select example">';
                              foreach ($data as $item) {
                                $selected = ($item == $selectedValue) ? ' selected' : '';
                                echo '<option value="' . htmlspecialchars($item) . '"' . $selected . '>' . htmlspecialchars($item) . '</option>';
                              }
                              echo '</select>';
                              echo '<button class="btn btn-primary" type="button" id="btn_' . $id . '" title="' . htmlspecialchars($gemini_model_list_json_file, ENT_QUOTES) . '" onclick="readJSON_file_path(\'' . addslashes($gemini_model_list_json_file) . '\')">Tệp Dữ Liệu Mô Hình</button>';
                              echo '</div>';
                            } else {
                              echo '<select name="' . $id . '" id="' . $id . '" class="form-select border-danger" aria-label="Default select example">';
                              foreach ($data as $item) {
                                $selected = ($item == $selectedValue) ? ' selected' : '';
                                echo '<option value="' . htmlspecialchars($item) . '"' . $selected . '>' . htmlspecialchars($item) . '</option>';
                              }
                              echo '</select>';
                            }
                          } else {
                            //Input text
                            if ($withButton) {
                              echo '<div class="input-group">';
                              echo '<input class="form-control border-danger" type="text" name="' . $id . '" id="' . $id . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($selectedValue) . '">';
                              echo '<button class="btn btn-primary" type="button" id="btn_' . $id . '" title="' . $gemini_model_list_json_file . '">Tệp Dữ Liệu Mô Hình</button>';
                              echo '</div>';
                            } else {
                              echo '<input class="form-control border-danger" type="text" name="' . $id . '" id="' . $id . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($selectedValue) . '">';
                            }
                          }
                          echo '</div></div>';
                        }
                        if (file_exists($gemini_model_list_json_file)) {
                          $data = json_decode(file_get_contents($gemini_model_list_json_file), true);
                          //Chỉ gemini_models_name mới có nút
                          renderSelectOrInput_Gemini($gemini_model_list_json_file, 'gemini_models_name', 'Mô Hình Gemini', $data['gemini_models'] ?? null, $selected_model, $selected_model, true);
                          renderSelectOrInput_Gemini($gemini_model_list_json_file, 'gemini_api_version', 'Phiên Bản API', $data['gemini_api_version'] ?? null, $selected_version, $selected_version);
                        } else {
                          renderSelectOrInput_Gemini($gemini_model_list_json_file, 'gemini_models_name', 'Mô Hình Gemini', null, $selected_model, $selected_model, true);
                          renderSelectOrInput_Gemini($gemini_model_list_json_file, 'gemini_api_version', 'Phiên Bản API', null, $selected_version, $selected_version);
                        }
                        echo input_field('google_gemini_time_out', 'Thời gian chờ (giây)', htmlspecialchars($Config['virtual_assistant']['google_gemini']['time_out'] ?? 25), 'required', 'number', '1', '5', '30', 'Thời gian chờ phản hồi tối đa (Giây)', 'border-success', '', '', '', '', '');
                        echo input_field('gemini_models_path_file', 'Đường dẫn tệp mô hình (Path Model)', htmlspecialchars($gemini_model_list_json_file), 'disabled', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                        ?>
                      </div>
                    </div>
                  </div>
                  </div>

                  <div class="card accordion" id="accordion_button_cfg_chatgpt_assistant">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cfg_chatgpt_assistant" aria-expanded="false" aria-controls="collapse_button_cfg_chatgpt_assistant">
                        Cấu Hình Trợ Lý => Chat GPT:</h5>
                      <div id="collapse_button_cfg_chatgpt_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cfg_chatgpt_assistant">
                        <div class="alert alert-info" role="alert"> <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Chat GPT')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="chat_gpt_active" id="chat_gpt_active" <?php echo $Config['virtual_assistant']['chat_gpt']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <?php
                        echo input_field('chat_gpt_key', 'Api Keys', htmlspecialchars($Config['virtual_assistant']['chat_gpt']['key_chat_gpt']), '', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', 'Kiểm Tra', "test_key_ChatGPT('Chào bạn, bạn tên là gì')", 'btn btn-success border-success', 'onclick', '');
                        echo select_field('chat_gpt_model', 'Model', ['' => '-- Chọn Model --', 'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Khuyến Nghị)', 'gpt-4' => 'GPT-4', 'gpt-4o' => 'GPT-4o', 'gpt-4o-mini' => 'GPT-4o mini', 'gpt-4-turbo' => 'GPT-4 Turbo'], $Config['virtual_assistant']['chat_gpt']['model'], []);
                        echo input_field('chat_gpt_role_system_content', 'Role System Content', htmlspecialchars($Config['virtual_assistant']['chat_gpt']['role_system_content']), '', 'text', '', '', '', 'Thiết lập hành vi mong muốn của Chat GPT trong cuộc trò chuyện, gán GPT như 1 trợ lý, người, vật, v..v...! làm cho trải nghiệm người dùng phù hợp với mục đích cụ thể của bạn.', 'border-success', '', '', '', '', '');
                        echo input_field('chat_gpt_url_api', 'URL API', htmlspecialchars($Config['virtual_assistant']['chat_gpt']['url_api']), '', 'text', '', '', '', '- Hỗ trợ với URL API và API KEY của bên thứ 3<br/><br/>hoặc URL Mặc Định của ChatGPT và Key của ChatGPT: <b>https://api.openai.com/v1/chat/completions</b>', 'border-danger', '', '', '', '', '');
                        echo input_field('chat_gpt_time_out', 'Thời gian chờ (giây)', htmlspecialchars($Config['virtual_assistant']['chat_gpt']['time_out']), '', 'number', '1', '5', '30', 'Thời gian chờ phản hồi tối đa (Giây)', 'border-success', '', '', '', '', '');
                        ?>
                      </div>
                    </div>
                  </div>
                  </div>

                  <div class="card accordion" id="accordion_button_cfg_difyai_assistant">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cfg_difyai_assistant" aria-expanded="false" aria-controls="collapse_button_cfg_difyai_assistant">
                        Cấu Hình Trợ Lý => Difi.ai | <a href="https://cloud.dify.ai" target="_blank">cloud.dify.ai</a>:</h5>
                      <div id="collapse_button_cfg_difyai_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cfg_difyai_assistant">
                        <div class="alert alert-warning" role="alert"> <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng Dify AI ')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="dify_ai_active" id="dify_ai_active" <?php echo $Config['virtual_assistant']['dify_ai']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Giữ Phiên Chat Session <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để lưu trữ ID phiên cho các lần hỏi đáp tiếp theo<br/><br/>- Phiên sẽ được làm mới mỗi khi chương trình VBot được khởi chạy')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="dify_ai_session_chat" id="dify_ai_session_chat" <?php echo $Config['virtual_assistant']['dify_ai']['session_chat_conversation'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <?php
                        echo input_field('dify_ai_key', 'Api Keys', htmlspecialchars($Config['virtual_assistant']['dify_ai']['api_key']), '', 'text', '', '', '', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', 'Kiểm Tra', "test_key_difyAI()", 'btn btn-success border-success', 'onclick', '');
                        echo input_field('dify_ai_user_id', 'User ID', htmlspecialchars($Config['virtual_assistant']['dify_ai']['user_id']), '', 'text', '', '', '', 'Mã định danh người dùng, được sử dụng để xác định danh tính của người dùng cuối để truy xuất và thống kê. Phải được nhà phát triển xác định duy nhất trong ứng dụng, <br/>- Có thể thay thành bất kỳ tên nào bạn muốn', 'border-success', '', '', '', '', '');
                        echo input_field('dify_ai_time_out', 'Thời gian chờ tối đa (giây)', htmlspecialchars($Config['virtual_assistant']['dify_ai']['time_out']), '', 'number', '1', '5', '30', 'Thời gian chờ phản hồi tối đa (Giây)', 'border-success', '', '', '', '', '');
                        ?>
                      </div>
                    </div>
                  </div>
                  </div>

                  <div class="card accordion" id="accordion_button_cfg_devassistant_assistant">
                    <div class="card-body">
                      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cfg_devassistant_assistant" aria-expanded="false" aria-controls="collapse_button_cfg_devassistant_assistant">
                        Cấu Hình Trợ Lý => DEV Assistant: Dev_Assistant.py <font color=red>(Custom Assistant, Người dùng tự code)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng Custom Assistant, Người dùng tự code trợ lý ảo, tùy biến hoặc sử dụng theo nhu cầu riêng ở tệp <b>Dev_Assistant.py</b>, nếu sử dụng hãy kích hoạt và chọn ưu tiên trợ lý ảo này')"></i>:</h5>
                      <div id="collapse_button_cfg_devassistant_assistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cfg_devassistant_assistant">
                        <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng Custom Assistant, Người dùng tự code trợ lý ảo, tùy biến hoặc sử dụng theo nhu cầu riêng ở tệp <b>Dev_Assistant.py</b>, nếu sử dụng hãy kích hoạt và chọn ưu tiên trợ lý ảo này')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="customize_developer_assistant_active" id="customize_developer_assistant_active" <?php echo $Config['virtual_assistant']['customize_developer_assistant']['active'] ? 'checked' : ''; ?>>
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

            <div class="card accordion" id="accordion_button_xiaozhiai">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_xiaozhiai" aria-expanded="false" aria-controls="collapse_button_xiaozhiai">
                  Cấu Hình Bot/Trợ Lý XiaoZhi AI:</h5>
                <div id="collapse_button_xiaozhiai" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_xiaozhiai">
				<div class="alert alert-success" role="alert">
                  <?php
                  echo "<div class='row mb-3'>
					  <label class='col-sm-3 col-form-label'>Kích hoạt 
						<i class='bi bi-question-circle-fill' onclick=\"show_message('Bật hoặc tắt để kích hoạt sử dụng XiaoZhi AI')\"></i> :
					  </label>
					  <div class='col-sm-9'>
						<div class='form-switch'>
						  <input class='form-check-input border-success' type='checkbox' name='xiaozhi_ai_active' id='xiaozhi_ai_active' " . (!empty($Config['xiaozhi']['active']) ? 'checked' : '') . ">
						</div></div></div>";
                  echo "<div class='row mb-3'>
					  <label class='col-sm-3 col-form-label'>MCP VBot System Control 
						<i class='bi bi-question-circle-fill' onclick=\"show_message('Bật hoặc tắt để kích hoạt sử dụng XiaoZhi tương tác ngược với hệ thống và chức năng của VBot thông qua MCP Có Sẵn Của VBot')\"></i> :
					  </label>
					  <div class='col-sm-9'>
						<div class='form-switch'>
						  <input class='form-check-input border-success' type='checkbox' name='xiaozhi_ai_mcp_system' id='xiaozhi_ai_mcp_system' " . (!empty($Config['xiaozhi']['mcp_system_control']) ? 'checked' : '') . ">
						</div></div></div>";
                  echo input_field('xiaozhi_ota_version_url', 'Link/URL OTA Server', $Config['xiaozhi']['system_options']['network']['ota_version_url'] ?? '', '', 'text', '', '', '', "Nhập địa chỉ Link/URL OTA của Server cần kết nối, Ví dụ: https://api.tenclass.net/xiaozhi/ota/<br/>Trang Chủ Liên Kết Thiết Bị: - https://xiaozhi.me/");
                  echo select_field('xiaozhi_start_the_protocol', 'Giao Thức Kết Nối', ['websocket' => 'WebSocket', 'udp' => 'UDP + MQTT (Chưa được hỗ trợ)'], $Config['xiaozhi']['start_the_protocol'] ?? 'websocket', ['udp']);
                  echo input_field('xiaozhi_time_out_output_stream', 'Time Out Audio', $Config['xiaozhi']['time_out_output_stream'] ?? 0.5, '', 'number', '0.1', '', '', 'Nếu Không còn dữ liệu âm thanh Stream trong 1 khoảng thời gian sẽ tự kết thúc TTS', 'border-success', '', '', '', '', '');
                  echo input_field('xiaozhi_tts_time_out', 'Thời Gian Chờ Phản Hồi Tối Đa (Giây)', $Config['xiaozhi']['tts_time_out'] ?? 5, '', 'number', '1', '', '', 'Hết thời gian chờ mà không nhận được dữ liệu phản hồi lại từ Server sẽ đóng phiên kết nối hiện tại', 'border-success', '', '', '', '', '');
                  echo input_field('xiaozhi_reconnection_timeout', 'Thời Gian Chờ Kết Nối Lại Tối Đa (Giây)', $Config['xiaozhi']['reconnection_timeout'] ?? 10, '', 'number', '1', '', '', 'Thời Gian Chờ Kết Nối Lại Tối Đa (giây) khi bị mất kết nối với máy chủ', 'border-success', '', '', '', '', '');
                  echo input_field('xiaozhi_tts_stream_silence_time', 'Ngưỡng im lặng cho phép tối đa TTS (Giây)', $Config['xiaozhi']['tts_stream_silence_time'] ?? 5, '', 'number', '1', '', '', 'là ngưỡng im lặng tối đa mà hệ thống cho phép trong luồng âm thanh TTS — nếu vượt quá thì coi như TTS kết thúc hoặc bị lỗi im lặng', 'border-success', '', '', '', '', '');
                  $status = $Config['xiaozhi']['activation_status'] ?? false;
                  echo "<div class='row mb-3'><label class='col-sm-3 col-form-label'>Trạng Thái Liên Kết 
						<i class='bi bi-question-circle-fill' onclick=\"show_message('Hiển thị trạng thái đã hoặc chưa liên kết với máy chủ')\"></i>:
					  </label><div class='col-sm-9'>";
                  if ($status === true || $status === "true" || $status === 1 || $status === "1") {
                    echo "<span class='text-success fw-bold'><i class='bi bi-check-lg'></i> Đã được liên kết với máy chủ</span>
						<button type='button' class='btn btn-sm btn-danger ms-2' onclick='xiaozhi_unlink_reset_data()'>
						  <i class='bi bi-link-45deg'></i>Hủy Liên Kết Và Đặt Lại Dữ Liệu</button>
						<button type='button' class='btn btn-sm btn-warning ms-2' onclick='xiaozhi_activation_status_false()'>
						  <i class='bi bi-link-45deg'></i>Liên Kết Xác Thực Lại</button>";
                  } else {
                    echo "<span class='text-danger fw-bold'><i class='bi bi-x-circle'></i> Thiết Bị chưa được liên kết với máy chủ</span>
						<button type='button' class='btn btn-sm btn-success ms-2' onclick='xiaozhi_active_device_info()'>
						  <i class='bi bi-link-45deg'></i> Liên kết và lấy mã xác nhận</button>";
                  }
                  echo "</div></div>";
                  echo input_field('xiaozhi_websocket_url', 'WebSocket Link/URL Server', $Config['xiaozhi']['system_options']['network']['websocket_url'] ?? '', 'disabled', 'text', '', '', '', 'Ví dụ: wss://api.tenclass.net/xiaozhi/v1/', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_firmware_version', 'Phiên Bản Firmware', $Config['xiaozhi']['system_options']['network']['firmware']['version'] ?? '', 'disabled', 'text', '', '', '', 'Mặc định sẽ lấy theo Phiên Bản Chương Trình VBot', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_device_id', 'ID Thiết Bị', $Config['xiaozhi']['device_id'] ?? '', 'disabled', 'number', '', '', '', 'Mã ID định danh của thiết bị', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_serial_number', 'Serial Thiết Bị (SN-****)', $Config['xiaozhi']['serial_number'] ?? '', 'disabled', 'text', '', '', '', 'Serial Thiết Bị (SN-****)', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_hmac_key', 'HMAC KEY Signature', $Config['xiaozhi']['hmac_key'] ?? '', 'disabled', 'text', '', '', '', 'HMAC KEY Signature', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_device_activation_code', 'Mã Kích Hoạt Thiết Bị', $Config['xiaozhi']['device_activation_code'] ?? '', 'disabled', 'number', '', '', '', 'Mã Kích Hoạt Thiết Bị Gồm 6 Số Liên Kết Với Server', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_client_id', 'Client ID', $Config['xiaozhi']['system_options']['client_id'] ?? '', 'disabled', 'text', '', '', '', 'Mã Định Danh Client ID Của Thiết Bị Này', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_mac_device_id', 'Địa Chỉ MAC', $Config['xiaozhi']['system_options']['device_id'] ?? '', 'disabled', 'text', '', '', '', 'Địa Chỉ Mac Của Thiết Bị Này', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_websocket_access_token', 'WebSocket Token', $Config['xiaozhi']['system_options']['network']['websocket_access_token'] ?? '', 'disabled', 'text', '', '', '', 'Mặc định là: test-token', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_mqtt_endpoint', 'MQTT Link/URL Server', $Config['xiaozhi']['system_options']['network']['mqtt_info']['endpoint'] ?? '', 'disabled', 'text', '', '', '', 'Máy Chủ MQTT', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_mqtt_client_id', 'MQTT Client ID', $Config['xiaozhi']['system_options']['network']['mqtt_info']['client_id'] ?? '', 'disabled', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_mqtt_username', 'MQTT Tài Khoản', $Config['xiaozhi']['system_options']['network']['mqtt_info']['username'] ?? '', 'disabled', 'text', '', '', '', 'Tài Khoản MQTT', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_mqtt_password', 'MQTT Mật Khẩu', $Config['xiaozhi']['system_options']['network']['mqtt_info']['password'] ?? '', 'disabled', 'text', '', '', '', 'Mật Khẩu MQTT', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_mqtt_publish_topic', 'MQTT Publish Topic', $Config['xiaozhi']['system_options']['network']['mqtt_info']['publish_topic'] ?? '', 'disabled', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                  echo input_field('xiaozhi_mqtt_subscribe_topic', 'MQTT Subscribe Topic', $Config['xiaozhi']['system_options']['network']['mqtt_info']['subscribe_topic'] ?? '', 'disabled', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                  ?>
                </div>
              </div>
            </div>
            </div>

            <div class="card accordion" id="accordion_button_collapse_button_developer_customization">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" title="chế độ tùy chỉnh cho các lập trình viên, DEV" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_developer_customization" aria-expanded="false" aria-controls="collapse_button_developer_customization">
                  DEV Customization: Custom Skill, Dev_Customization.py <font color=red>(Người Dùng Tự Code)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Cơ chế hoạt động:<br/>- Chế độ được kích hoạt, khi được đánh thức Wake UP, chương trình sẽ truyền dữ liệu văn bản được chuyển đổi từ Speak to Text vào File Dev_Customization.py để cho các bạn tự lập trình và xử lý dữ liệu, Khi kết thúc xử lý sẽ cần phải có return để trả về true hoặc false')"></i> :
                </h5>
                <div id="collapse_button_developer_customization" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_developer_customization">
                  <div class="alert alert-success" role="alert"> <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Chế độ DEV: Dev_Customization.py (Custom Skill) <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chế độ để vào và sử dụng chế độ Custom Skill cho các bạn Dev thoải mái xử lý dữ liệu lập trình và tùy biến')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="developer_customization_active" id="developer_customization_active" <?php echo $Config['developer_customization']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Nếu Custom Skill: Dev_Customization.py không thể xử lý:</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Tiếp tục sử dụng VBot xử lý <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng VBot xử lý dữ liệu khi mà Custom Skill không xử lý được')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="developer_customization_vbot_processing" id="developer_customization_vbot_processing" <?php echo $Config['developer_customization']['if_custom_skill_can_not_handle']['vbot_processing'] ? 'checked' : ''; ?>>
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

		  <div class="card accordion" id="accordion_button_broadlink">
		  <div class="card-body">
		  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_broadlink" aria-expanded="false" aria-controls="collapse_button_broadlink">
		  Liên Kết Broadlink Control, Remote Send IR/RF:</h5>
		  <div id="collapse_button_broadlink" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_broadlink">
				<div class="alert alert-success" role="alert">
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để sử dụng Broadlink Control Remote, cần liên kết thiết bị Broadlink Remote với VBot và học lệnh trên giao diện WebUI VBot<br/> Truy Cập: <b>Liên Kết Thiết Bị, Dịch Vụ</b> -> <b>Broadlink Remote</b>')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="broadlink_remote_active" id="broadlink_remote_active" <?php echo $Config['broadlink']['remote']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <?php
				  echo input_field('broadlink_remote_minimum_threshold', 'Ngưỡng kết quả tối thiểu', $Config['broadlink']['remote']['minimum_threshold'] ?? 0.85, 'required', 'number', '0.01', '0.5', '1.0', 'Ngưỡng kết quả cho phép từ <b>0.1 -> 0.9</b> ngưỡng càng cao thì yêu cầu độ chính xác câu lệnh thực thi cao', 'border-success', '', '', '', '', '');
                  echo input_field('schedule_data_json_file', 'Tệp Lưu Trữ Dữ Liệu Cấu Hình ', htmlspecialchars($Config['broadlink']['json_file']), 'readonly', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
				  echo input_field('gcloud_drive_backup_url_ui', 'Trang Cấu Hình Chi tiết', "http://$serverIp/BroadLink_Remote.php", 'disabled', 'text', '', '', '', '', 'border-danger', '<i class="bi bi-arrow-return-right"></i> Đi Tới', "BroadLink_Remote.php", 'btn btn-success border-danger', 'link', '_blank');
                  ?>
                </div>
		  </div>
		  </div>
		  </div>

            <div class="card accordion" id="accordion_button_schedule_lich">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_schedule_lich" aria-expanded="false" aria-controls="collapse_button_schedule_lich">
                  Cài Đặt Lập Lịch, Lời Nhắc, Thông báo, V..v... (Schedule) <i class="bi bi-question-circle-fill" onclick="show_message('Bạn cần di chuyển tới: <b>Thiết Lập Nâng Cao -> Lên Lịch: Lời Nhắc, Thông Báo (Scheduler)</b> để tiến hành thiết lập thông báo.<br/>Ví Dụ Câu Lệnh Ra lệnh nhanh: <br/>- Nhắc tôi uống thuốc sau 10 phút nữa<br/>- Nhắc tôi thức dạy lúc 6 giờ sáng mai<br/>- Nhắc tôi làm việc lúc 13 giờ 40 phút hôm nay nhé')"></i>:
                </h5>
                <div id="collapse_button_schedule_lich" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_schedule_lich">
				<div class="alert alert-success" role="alert">
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để khởi động Phát lời nhắc, Thông báo khi VBot được khởi chạy')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="schedule_active" id="schedule_active" <?php echo $Config['schedule']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <?php
                  echo input_field('schedule_data_json_file', 'Tệp Lưu Trữ Dữ Liệu Cấu Hình:', htmlspecialchars($Config['schedule']['data_json_file']), 'readonly', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                  echo input_field('schedule_audio_path', 'Thư Mục Chứa Tệp Âm Thanh:', htmlspecialchars($Config['schedule']['audio_path']), 'readonly', 'text', '', '', '', '', 'border-danger', '', '', '', '', '');
                  ?>
                  <div class="row mb-3">
                    <b class="text-danger">Yêu Cầu: Cần Nhập Thêm KEY Trợ Lý Gemini Để Có Thể (Hoa Mỹ, Mỹ Miều) Lời Nhắc Khi Lập Lịch</b>
                  </div>
                </div>
              </div>
            </div>
            </div>

            <div class="card accordion" id="accordion_button_sao_luu_cap_nhat">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_sao_luu_cap_nhat" aria-expanded="false" aria-controls="collapse_button_sao_luu_cap_nhat">
                  Cấu Hình Cài Đặt Sao Lưu/Cập Nhật:
                </h5>
                <div id="collapse_button_sao_luu_cap_nhat" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_sao_luu_cap_nhat">
                  <div class="alert alert-success" role="alert"> <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Tự Động Kiểm Tra Bản Cập Nhật <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật sẽ tự động kiểm tra cập nhật mới khi truy cập vào giao diện web ui')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="automatically_check_for_updates" id="automatically_check_for_updates" <?php echo $Config['backup_upgrade']['advanced_settings']['automatically_check_for_updates'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Khởi Động Lại VBot <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, Chương trình sẽ khởi động lại VBot khi quá trình cập nhật Nâng Cấp VBot thành công')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="restart_vbot_upgrade" id="restart_vbot_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['restart_vbot'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Thông Báo Âm Thanh <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, sẽ thông báo bằng âm thanh khi quá trình Cập Nhật, Nâng Cấp Giao Diện Web hoặc chương trình VBot thành công')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="sound_notification_backup_upgrade" id="sound_notification_backup_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['sound_notification'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Tải Lại Giao Diện Web <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, sẽ tải lại giao diện Web khi Nâng Cấp, Cập Nhật web ui thành công')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="refresh_page_ui_backup_upgrade" id="refresh_page_ui_backup_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['refresh_page_ui'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Sao Lưu Config.json</h5>
					  <div class="alert alert-primary" role="alert">
                      <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chức năng sao lưu tệp Config.json mỗi khi lưu hoặc thay đổi cấu hình Config.json')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="backup_config_json_active" id="backup_config_json_active" <?php echo $Config['backup_upgrade']['config_json']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      echo input_field('backup_path_config_json', 'Tên Thư Mục Sao Lưu', htmlspecialchars($Config['backup_upgrade']['config_json']['backup_path']), 'readonly', 'text', '', '', '', 'Tên Thư Mục Sao Lưu Tệp Config.json, Nếu thư mục không tồn tại sẽ tự động được tạo mới', 'border-danger', '', '', '', '', '');
                      echo input_field('limit_backup_files_config_json', 'Giới hạn tệp sao lưu tối đa', htmlspecialchars($Config['backup_upgrade']['config_json']['limit_backup_files'] ?? 5), '', 'number', '1', '1', '20', 'Giới hạn tệp sao lưu tối đa trong thư mục Backup_Config, nếu nhiều hơn giới hạn cho phép sẽ tự động xóa file cũ nhất', 'border-success', '', '', '', '', '');
                      ?>
                    </div>
                  </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Sao Lưu Custom Home Assistant (Home_Assistant_Custom.json)</h5>
                      <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chức năng sao lưu tệp Home_Assistant_Custom.json mỗi khi lưu hoặc thay đổi cấu hình Home_Assistant_Custom.json')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="custom_home_assistant_active" id="custom_home_assistant_active" <?php echo $Config['backup_upgrade']['custom_home_assistant']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      echo input_field('backup_path_custom_home_assistant', 'Tên Thư Mục Sao Lưu', htmlspecialchars($Config['backup_upgrade']['custom_home_assistant']['backup_path']), 'readonly', 'text', '', '', '', 'Tên Thư Mục Sao Lưu Tệp Home_Assistant_Custom.json, Nếu thư mục không tồn tại sẽ tự động được tạo mới', 'border-danger', '', '', '', '', '');
                      echo input_field('limit_backup_custom_home_assistant', 'Giới hạn tệp sao lưu tối đa', htmlspecialchars($Config['backup_upgrade']['custom_home_assistant']['limit_backup_files'] ?? 5), '', 'number', '1', '1', '20', 'Giới hạn tệp sao lưu tối đa trong thư mục Backup_Custom_HomeAssistant, nếu nhiều hơn giới hạn cho phép sẽ tự động xóa file cũ nhất', 'border-success', '', '', '', '', '');
                      ?>
                    </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Sao Lưu Cấu Hình Lời Nhắc, Thông Báo (Scheduler)</h5>
                      <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chức năng sao lưu tệp Data_Schedule.json mỗi khi lưu hoặc thay đổi lưu cấu hình')"></i> :</label>
                        <div class="col-sm-9">
                          <div class="form-switch">
                            <input class="form-check-input border-success" type="checkbox" name="backup_scheduler_active" id="backup_scheduler_active" <?php echo $Config['backup_upgrade']['scheduler']['active'] ? 'checked' : ''; ?>>
                          </div>
                        </div>
                      </div>
                      <?php
                      echo input_field('backup_path_scheduler', 'Tên Thư Mục Sao Lưu', htmlspecialchars($Config['backup_upgrade']['scheduler']['backup_path']), 'readonly', 'text', '', '', '', 'Tên Thư Mục Sao Lưu Tệp Data_Schedule.json, Nếu thư mục không tồn tại sẽ tự động được tạo mới', 'border-danger', '', '', '', '', '');
                      echo input_field('limit_backup_scheduler', 'Giới hạn tệp sao lưu tối đa', htmlspecialchars($Config['backup_upgrade']['scheduler']['limit_backup_files'] ?? 5), '', 'number', '1', '1', '20', 'Giới hạn tệp sao lưu tối đa trong thư mục Backup_Scheduler, nếu nhiều hơn giới hạn cho phép sẽ tự động xóa file cũ nhất', 'border-success', '', '', '', '', '');
                      ?>
                    </div>
                  </div>
                  </div>
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Chương Trình/VBot Program <i class="bi bi-question-circle-fill" onclick="show_message('Cấu hình Cài Đặt khi Sao Lưu và Cập Nhật trương trình')"></i>:</h5>
					  <div class="alert alert-info" role="alert"> 
                      <div class="card accordion" id="accordion_button_cau_hinh_sao_luu_vbot">
                        <div class="card-body">
                          <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cau_hinh_sao_luu_vbot" aria-expanded="false" aria-controls="collapse_button_cau_hinh_sao_luu_vbot">
                            Sao Lưu Chương Trình VBot:
                          </h5>
                          <div id="collapse_button_cau_hinh_sao_luu_vbot" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cau_hinh_sao_luu_vbot">
                           <div class="alert alert-primary" role="alert">  <div class="row mb-3">
                              <label class="col-sm-3 col-form-label">Đồng bộ lên Google Drive: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu sẽ được tải lên Google Drive')"></i> :</label>
                              <div class="col-sm-9">
                                <div class="form-switch">
                                  <input class="form-check-input border-success" type="checkbox" name="backup_vbot_google_drive" id="backup_vbot_google_drive" <?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive'] ? 'checked' : ''; ?>>
                                </div>
                              </div>
                            </div>
                            <?php
                            echo input_field('backup_upgrade_vbot_backup_path', 'Đường dẫn tệp sao lưu:', htmlspecialchars($Config['backup_upgrade']['vbot_program']['backup']['backup_path']), 'readonly', 'text', '', '', '', 'Tên Thư Mục Sao Lưu Tệp. Nếu thư mục không tồn tại sẽ tự động được tạo mới', 'border-danger', '', '', '', '', '');
                            echo input_field('backup_upgrade_vbot_limit_backup_files', 'Giới hạn tệp sao lưu tối đa', htmlspecialchars($Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files'] ?? 5), '', 'number', '1', '1', '20', 'Tối đa số lượng tệp tin sao lưu trên hệ thống, nếu nhiều hơn giới hạn cho phép sẽ tự động xóa file cũ nhất', 'border-success', '', '', '', '', '');
                            ?>
                            <div class="row mb-3">
                              <label for="backup_upgrade_vbot_exclude_files_folder" class="col-sm-3 col-form-label">Bỏ qua file, thư mục không sao lưu <font color="blue" size="6" title="Không Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi thư mục hoặc file sẽ là 1 dòng, nếu là file sẽ cần có đầy đủ đuôi mở rộng của file, ví dụ: <b>123.mp3</b>')"></i> :</label>
                              <div class="col-sm-9">
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
?></textarea>
                              </div>
                            </div>
                            <div class="row mb-3">
                              <label for="backup_upgrade_vbot_exclude_file_format" class="col-sm-3 col-form-label">Bỏ qua định dạng tệp không sao lưu <font color="blue" size="6" title="Không Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi định dạng tệp là 1 dòng, cần có dấu <b>.</b> ở trước định dạng tệp ví dụ: <b>.mp3</b> hoặc <b>.mp4</b>')"></i> :</label>
                              <div class="col-sm-9">
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
?></textarea>
                              </div>
                            </div>
                            <center>
                              <button type="button" name="show_all_file_in_directoryyyy" class="btn btn-success rounded-pill" onclick="show_all_file_in_directory('<?php echo $HTML_VBot_Offline . '/' . $Backup_Dir_Save_VBot; ?>', 'show_all_file_folder_Backup_Program')">Danh Sách Tệp Sao Lưu VBot</button>
                              <br /><br />
                              <div class="limited-height" id="show_all_file_folder_Backup_Program"></div>
                            </center>
                          </div>
                          </div>
                        </div>
                      </div>
                      <div class="card accordion" id="accordion_button_cau_hinh_Cap_nhat_Vbot">
                        <div class="card-body">
                          <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cau_hinh_Cap_nhat_Vbot" aria-expanded="false" aria-controls="collapse_button_cau_hinh_Cap_nhat_Vbot">
                            Cập Nhật Chương Trình VBot:
                          </h5>
                          <div id="collapse_button_cau_hinh_Cap_nhat_Vbot" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cau_hinh_Cap_nhat_Vbot">
                            <div class="alert alert-primary" role="alert"> <div class="row mb-3">
                              <label class="col-sm-3 col-form-label">Tạo Bản Sao Lưu Trước Khi Cập Nhật: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu được tạo ra trước khi cập nhật sẽ được tải lên Google Drive')"></i> :</label>
                              <div class="col-sm-9">
                                <div class="form-switch">
                                  <input class="form-check-input border-success" type="checkbox" name="make_a_backup_before_updating_vbot" id="make_a_backup_before_updating_vbot" <?php echo $Config['backup_upgrade']['vbot_program']['upgrade']['backup_before_updating'] ? 'checked' : ''; ?>>
                                </div>
                              </div>
                            </div>
                            <div class="row mb-3">
                              <label for="vbot_program_upgrade_keep_the_file_folder" class="col-sm-3 col-form-label">Giữ lại Tệp, Thư Mục Không Cập Nhật <font color="blue" size="6" title="Không Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Giữ lại tệp hoặc thư mục không cho phép cập nhật, mỗi tệp hoặc thư mục là 1 dòng, nếu là tệp tin thì cần có đầy đủ tên và đuôi của tệp, ví dụ giữ lại tệp: <b>Config.json</b>, giữ lại thư mục: <b>eng</b>')"></i> :</label>
                              <div class="col-sm-9">
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
?></textarea>
                              </div>
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
                      <h5 class="card-title">Giao diện Web <i class="bi bi-question-circle-fill" onclick="show_message('Cấu hình Cài Đặt khi Sao Lưu và Cập Nhật Giao diện Web')"></i>:</h5>
                <div class="alert alert-info" role="alert">       <div class="card accordion" id="accordion_button_sao_luu_giao_dien">
					  
                        <div class="card-body">
                          <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_sao_luu_giao_dien" aria-expanded="false" aria-controls="collapse_button_sao_luu_giao_dien">
                            Sao Lưu Giao Diện WebUI VBot:
                          </h5>
                          <div id="collapse_button_sao_luu_giao_dien" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_sao_luu_giao_dien">
                          <div class="alert alert-primary" role="alert">  <div class="row mb-3">
                              <label class="col-sm-3 col-form-label">Đồng bộ lên Google Drive: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu sẽ được tải lên Google Drive')"></i> :</label>
                              <div class="col-sm-9">
                                <div class="form-switch">
                                  <input class="form-check-input border-success" type="checkbox" name="backup_web_interface_google_drive" id="backup_web_interface_google_drive" <?php echo $Config['backup_upgrade']['web_interface']['backup']['backup_to_cloud']['google_drive'] ? 'checked' : ''; ?>>
                                </div>
                              </div>
                            </div>
                            <?php
                            echo input_field('backup_web_interface_backup_path', 'Đường dẫn tệp sao lưu:', htmlspecialchars($Config['backup_upgrade']['web_interface']['backup']['backup_path']), 'readonly', 'text', '', '', '', 'Tên Thư Mục Sao Lưu Tệp, Nếu thư mục không tồn tại sẽ tự động được tạo mới', 'border-danger', '', '', '', '', '');
                            echo input_field('backup_web_interface_limit_backup_files', 'Giới hạn tệp sao lưu tối đa', htmlspecialchars($Config['backup_upgrade']['web_interface']['backup']['limit_backup_files'] ?? 5), '', 'number', '1', '1', '20', 'Tối đa số lượng tệp tin sao lưu trên hệ thống, nếu nhiều hơn giới hạn cho phép sẽ tự động xóa file cũ nhất', 'border-success', '', '', '', '', '');
                            ?>
                            <div class="row mb-3">
                              <label for="backup_upgrade_web_interface_exclude_files_folder" class="col-sm-3 col-form-label">Bỏ qua file, thư mục không sao lưu <font color="blue" size="6" title="Không Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi thư mục hoặc file sẽ là 1 dòng, nếu là file sẽ cần có đầy đủ đuôi mở rộng của file, ví dụ: <b>123.mp3</b>')"></i> :</label>
                              <div class="col-sm-9">
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
?></textarea>
                              </div>
                            </div>
                            <div class="row mb-3">
                              <label for="backup_upgrade_web_interface_exclude_file_format" class="col-sm-3 col-form-label">Bỏ qua định dạng tệp không sao lưu <font color="blue" size="6" title="Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi định dạng tệp là 1 dòng, cần có dấu <b>.</b> ở trước định dạng tệp ví dụ: <b>.mp3</b> hoặc <b>.mp4</b>')"></i> :</label>
                              <div class="col-sm-9">
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
?></textarea>
                              </div>
                            </div>
                          </div>
                          </div>
                        </div>
                      </div>
                      <div class="card accordion" id="accordion_button_cap_nhat_giao_dien">
                        <div class="card-body">
                          <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cap_nhat_giao_dien" aria-expanded="false" aria-controls="collapse_button_cap_nhat_giao_dien">
                            Cập Nhật Giao Diện WebUI VBot:
                          </h5>
                          <div id="collapse_button_cap_nhat_giao_dien" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cap_nhat_giao_dien">
                          <div class="alert alert-primary" role="alert">  <div class="row mb-3">
                              <label class="col-sm-3 col-form-label">Tạo Bản Sao Lưu Trước Khi Cập Nhật: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu được tạo ra trước khi cập nhật sẽ được tải lên Google Drive')"></i> :</label>
                              <div class="col-sm-9">
                                <div class="form-switch">
                                  <input class="form-check-input border-success" type="checkbox" name="make_a_backup_before_updating_interface" id="make_a_backup_before_updating_interface" <?php echo $Config['backup_upgrade']['web_interface']['upgrade']['backup_before_updating'] ? 'checked' : ''; ?>>
                                </div>
                              </div>
                            </div>
                            <div class="row mb-3">
                              <label for="vbot_web_interface_upgrade_keep_the_file_folder" class="col-sm-3 col-form-label">Giữ lại Tệp, Thư Mục Không Cập Nhật <font color="blue" size="6" title="Bắt Buộc Nhập">*</font> <i class="bi bi-question-circle-fill" onclick="show_message('Giữ lại tệp hoặc thư mục không cho phép cập nhật, mỗi tệp hoặc thư mục là 1 dòng, nếu là tệp tin thì cần có đầy đủ tên và đuôi của tệp, ví dụ giữ lại tệp: <b>Config.json</b>, giữ lại thư mục: <b>eng</b>')"></i> :</label>
                              <div class="col-sm-9">
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
?></textarea>
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
              </div>
            </div>
            <div class="card accordion" id="accordion_button_Cloud_backup">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_Cloud_Backup" aria-expanded="false" aria-controls="collapse_button_Cloud_Backup">
                  Cấu Hình Tải Lên Bản Sao Lưu Dữ Liệu - Cloud Backup&nbsp;<i class="bi bi-cloud-check"></i>&nbsp;:
                </h5>
                <div id="collapse_button_Cloud_Backup" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_Cloud_Backup">
                 <div class="alert alert-success" role="alert"> <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Google Cloud Drive <i class="bi bi-question-circle-fill" onclick="show_message('Cấu hình thiết lập đồng bộ dữ liệu lên Google Cloud Drive<br/>- Nếu có nhiều thiết bị cần đồng bộ lên Google Cloud Drive thì cần thay đổi tên của 3 thư mục để tránh bị trùng lặp với dữ liệu của thiết bị khác<br/><a href=\'https://docs.google.com/document/d/1-VTi9MOAgQoR8jZrhN9FlZxjWsq2vDuy/edit?usp=drive_link&ouid=106149318613102395200&rtpof=true&sd=true\' target=\'_bank\'><b>Hướng dẫn tạo file json</b></a>')"></i> | <a href="GCloud_Drive.php" title="Truy Cập"> <i class="bi bi-box-arrow-up-right"></i> Truy Cập</a> :</h5>
					<div class="alert alert-primary" role="alert">
                      <?php
                      echo "
						<div class='row mb-3'>
						  <label class='col-sm-3 col-form-label'>
							Kích hoạt 
							<i class='bi bi-question-circle-fill' onclick=\"show_message('Bật hoặc Tắt chức năng sao lưu dữ liệu lên Google Cloud Drive')\"></i> :
						  </label>
						  <div class='col-sm-9'>
							<div class='form-switch'>
							  <input class='form-check-input border-success' type='checkbox' 
									 name='google_cloud_drive_active' id='google_cloud_drive_active' 
									 " . ($Config['backup_upgrade']['google_cloud_drive']['active'] ? 'checked' : '') . ">
							</div>
						  </div>
						</div>";
                      echo input_field('gcloud_drive_backup_folder_name', "Tên Thư Mục Cha Sao Lưu <font color='red' size='6' title='Bắt Buộc Nhập'>*</font>", $Config['backup_upgrade']['google_cloud_drive']['backup_folder_name'], false, 'text', "Tên Thư Mục Sao Lưu Trên Google Cloud Drive (Thư Mục Cha), Nếu thư mục không tồn tại sẽ tự động được tạo mới");
                      echo input_field('gcloud_drive_backup_folder_vbot_name', "Tên Thư Mục Sao Lưu Chương Trình VBot <font color='red' size='6' title='Bắt Buộc Nhập'>*</font>", $Config['backup_upgrade']['google_cloud_drive']['backup_folder_vbot_name'], false, 'text', "Tên Thư Mục Sao Lưu Chương Trình VBot Trên Google Cloud Drive (Thư Mục Con), Nếu thư mục không tồn tại sẽ tự động được tạo mới");
                      echo input_field('gcloud_drive_backup_folder_interface_name', "Tên Thư Mục Sao Lưu Giao Diện VBot <font color='red' size='6' title='Bắt Buộc Nhập'>*</font>", $Config['backup_upgrade']['google_cloud_drive']['backup_folder_interface_name'], false, 'text', "Tên Thư Mục Sao Lưu Giao Diện VBot Trên Google Cloud Drive (Thư Mục Con), Nếu thư mục không tồn tại sẽ tự động được tạo mới");
                      echo select_field(
                        'gcloud_drive_setAccessType',
                        "Kiểu Loại Truy Cập <i class='bi bi-question-circle-fill' onclick=\"show_message('- Để giá trị là offline thì sẽ tự động làm mới lại mã token xác thực khi hết hạn<br/>- Để giá trị là online thì mỗi lần mã token xác thực hết hạn bạn cần lấy lại bằng thao tác thủ công')\"></i>",
                        ['offline' => 'Offline (Tự động làm mới Token) - Mặc Định', 'online' => 'Online (Làm mới Token thủ công)'],
                        $Config['backup_upgrade']['google_cloud_drive']['setAccessType'], []);
                      echo select_field(
                        'gcloud_drive_setPrompt',
                        "Đặt Lời Nhắc Khi Xác Thực 
							 <i class='bi bi-question-circle-fill' 
								onclick=\"show_message('- none: Không hiển thị bất kỳ trang yêu cầu quyền nào từ Google.<br/><br/>Nếu người dùng đã đăng nhập và đã cấp quyền, họ sẽ được chuyển hướng ngay lập tức.<br/><br/>Nếu không có quyền hoặc người dùng chưa đăng nhập, yêu cầu sẽ trả về lỗi.<br/><br/>- consent: Luôn yêu cầu người dùng đồng ý cấp quyền.<br/><br/>- select_account: Hiển thị danh sách tài khoản Google để người dùng chọn.<br/><br/>- consent select_account: Hiển thị cả hộp chọn tài khoản và yêu cầu người dùng xác nhận lại quyền.')\"></i>",
                        [
                          'none' => 'None (Không hiển thị)',
                          'consent' => 'Consent (Yêu cầu đồng ý cấp quyền) - Mặc Định',
                          'select_account' => 'Select Account (Chọn tài khoản muốn dùng)',
                          'consent select_account' => 'Consent Select Account (Chọn và Yêu cầu đồng ý cấp quyền)'
                        ], $Config['backup_upgrade']['google_cloud_drive']['setPrompt'], []);

				echo input_field('gcloud_drive_backup_url_ui', 'Giao Diện Cấu Hình Xác Thực', "http://$serverIp/GCloud_Drive.php", 'disabled', 'text', '', '', '', '', 'border-danger', '<i class="bi bi-arrow-return-right"></i> Đi Tới', "GCloud_Drive.php", 'btn btn-success border-danger', 'link', '_blank');

                      ?>
                    </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>

            <div class="card accordion" id="accordion_button_wakeup_reply_source">
              <div class="card-body">
                <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_wakeup_reply_source" aria-expanded="false" aria-controls="collapse_button_wakeup_reply_source">
                  Chế Độ Câu Phản Hồi (Khi Được Đánh Thức) <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Đánh thức Bot bằng giọng nói thì Bot sẽ phản hồi lại bằng file âm thanh Audio khi lần đầu tiên được đánh thức<br/> Chỉ chấp nhận file âm thanh .mp3')"></i> :</h5>
                <div id="collapse_button_wakeup_reply_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_wakeup_reply_source">
				<div class="alert alert-success" role="alert">
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích hoạt chế độ câu phản hồi <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chế độ câu phản hồi')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="wakeup_reply_active" id="wakeup_reply_active" <?php echo $Config['smart_config']['smart_wakeup']['wakeup_reply']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                  <center>
                    <button type="button" class="btn btn-primary rounded-pill" onclick="loadWakeupReply('<?php echo $VBot_Offline; ?>')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Hiển Thị Danh Sách Câu Phản Hồi">Hiển Thị Danh Sách Câu Phản Hồi</button>
                    <button type="button" class="btn btn-success rounded-pill" onclick="show_create_audio_WakeUP_Reply()" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tạo File Âm Thanh Câu Phản Hồi">Tạo File Âm Thanh</button>
                    <button type="button" class="btn btn-warning rounded-pill" onclick="reload_hotword_config('wakeup_reply')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tự động tìm scan các file âm thanh .mp3 trong thư mục wakeup_reply để cấu hình trong Config.json">Scan Và Ghi Mới</button>
                  </center><br />
                  <table class="table table-bordered border-primary">
                    <tbody>
                      <tr>
                        <td style="text-align: center; vertical-align: middle;">
                          <label for="upload_files_wakeup_reply">
                            <font color="blue">Tải lên file âm thanh Câu Phản Hồi .mp3</font>
                          </label>
                          <div class="input-group">
                            <input class="form-control border-success" type="file" name="upload_files_wakeup_reply[]" id="upload_files_wakeup_reply" accept=".mp3" multiple>
                            <button class="btn btn-primary border-success" type="button" onclick="uploadFilesWakeUP_Reply()"><i class="bi bi-arrow-bar-up"></i> Tải Lên</button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <div style="display: none;" id="displayResults_create_audio_WakeUP_Reply">
                    <div class="input-group mb-3">
                      <span class="input-group-text border-success text-danger" id="basic-addon1">Nguồn Tạo Tệp Âm Thanh:</span>
                      <select id="audio_reply_type" onchange="handleAudioReplyType()" class="form-select border-success">
                        <option selected>Chọn Nguồn TTS....</option>
                        <option value="wakeup_reply_tts_gcloud">TTS Google Cloud (JSON File)</option>
                        <option value="wakeup_reply_tts_zalo">TTS Zalo (Sử Dụng KEY)</option>
                      </select>
                    </div>
                    <div id="tts_audio_reply_options"></div>
                  </div>

                  <div id="displayResults_wakeup_reply"></div>
                </div>
              </div>
            </div>
            </div>

      <div class="card accordion" id="accordion_button_sys_speak_cmd">
      <div class="card-body">
      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_sys_speak_cmd" aria-expanded="false" aria-controls="collapse_button_sys_speak_cmd">
      Lệnh Điều Khiển Hệ Thống SYSTEM <i class="bi bi-question-circle-fill" onclick="show_message('Sử dụng câu lệnh tương ứng để điều khiển 1 số chức năng trong hệ thống SYSTEM mà chương trình cho phép')"></i>:</h5>
      <div id="collapse_button_sys_speak_cmd" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_sys_speak_cmd">
<div class="alert alert-success" role="alert">
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng các lệnh bằng giọng nói để điều khiển, can thiệp vào hệ thống SYSTEM')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="voice_command_system_active" id="voice_command_system_active" <?php echo $Config['voice_command_system']['active'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
				<?php
				echo input_field('voice_command_system_threshold', "Ngưỡng Kết Quả Tối Thiểu <font color='red' size='6' title='Bắt Buộc Nhập'>*</font>", htmlspecialchars($Config['voice_command_system']['minimum_result_threshold'] ?? 0.75), 'required', 'number', '0.01', '0.01', '1.0', 'Ngưỡng so sánh câu lệnh với kết quả tối thiểu', 'border-success', '', '', '', '', '');
				echo input_field('voice_command_file_json', 'Đường Dẫn Tệp Dữ Liệu', htmlspecialchars($VBot_Offline.'resource/SYS_CMD.json'), 'disabled', 'text', '', '', '', '', 'border-danger', '<i class="bi bi-eye"></i> Xem File', 'readJSON_file_path(\'' . $VBot_Offline . '/resource/SYS_CMD.json\')', 'btn btn-success border-danger', 'onclick', '');
				echo input_field('voice_command_url_ui', 'Quản Lý, Cấu Hình Lệnh', "http://$serverIp/Sys_Cmd.php", 'disabled', 'text', '', '', '', '', 'border-danger', '<i class="bi bi-arrow-return-right"></i> Đi Tới', "Sys_Cmd.php", 'btn btn-success border-danger', 'link', '_blank');
				?>
      </div>
      </div>
      </div>
      </div>

            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Chế Độ Hội Thoại/Trò Chuyện Liên Tục <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật Bạn chỉ cần gọi Bot 1 lần, sau khi bot trả lời xong sẽ tự động lắng nghe tiếp và lặp lại (cho tới khi Bạn không còn yêu cầu nào nữa)')"></i> :</h5>
                <div class="alert alert-success" role="alert"> <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt chế độ hội thoại <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chế độ hội thoại')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="conversation_mode" id="conversation_mode" <?php echo $Config['smart_config']['smart_wakeup']['conversation_mode'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>

            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Chế Độ Xử Lý Đa Lệnh Trong 1 Câu Lệnh <i class="bi bi-question-circle-fill" onclick="show_message('Khi được Bật, sẽ kích hoạt chế độ xử lý nhiều hành động trong 1 câu lệnh, Ví dụ câu lệnh: <br/>- Bật đèn ngủ và tắt đèn phòng khách<br/> - Bật đèn phòng ngủ sau đó phát danh sách nhạc<br/> Từ khóa phân tách nhiều lệnh trong 1 câu: <b>và, sau đó, rồi</b> trong file: <b>Adverbs.json</b>')"></i> :</h5>
               <div class="alert alert-success" role="alert">
			   <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Kích hoạt chế độ xử lý đa lệnh <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chế độ đa lệnh trong 1 câu')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="multiple_command" id="multiple_command" <?php echo $Config['multiple_command']['active'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Tiếp tục được đánh thức, lắng nghe khi xử lý xong đa lệnh: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để tự động được đánh thức và lắng nghe câu lệnh tiếp theo, khi xử lý xong đa lệnh trong 1 câu<br/> - Yêu cầu Chế Độ Hội Thoại phải được kích hoạt để sử dụng')"></i> :</label>
                 <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="continue_listening_after_commands" id="continue_listening_after_commands" <?php echo $Config['multiple_command']['continue_listening_after_commands'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
              </div>
              </div>
            </div>

            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Đọc thông tin khi khởi động:</h5>
               <div class="alert alert-success" role="alert"> <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Bật, Tắt đọc thông tin <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt đọc thông tin khi Chương trình khởi động như: Địa chỉ ip của thiết bị, v..v...')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="read_information_startup" id="read_information_startup" <?php echo $Config['smart_config']['read_information_startup']['active'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
                <?php
                echo input_field('read_information_startup_read_number', 'Số lần đọc:', htmlspecialchars($Config['smart_config']['read_information_startup']['read_number'] ?? 2), '', 'number', '1', '1', '5', '<font color="red" size="6" title="Bắt Buộc Nhập">*</font>', 'border-success', '', '', '', '', '');
                ?>
              </div>
            </div>
            </div>
			
            <div class="card">
              <div class="card-body">
                <h5 class="card-title text-danger">Xử Lý Lỗi:</h5>
                <div class="alert alert-success" role="alert"> <div class="row mb-3">
                  <label class="col-sm-3 col-form-label text-danger">Khởi động lại hệ thống khi gặp sự cố hoặc lỗi bất ngờ: <i class="bi bi-question-circle-fill" onclick="show_message('Tự động khởi động lại chương trình VBot khi gặp sự cố hoặc có lỗi xảy ra bất ngờ, Sẽ chỉ hoạt động ở chế độ đang chạy Auto')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="auto_restart_program_error" id="auto_restart_program_error" <?php echo $Config['smart_config']['auto_restart_program_error'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label text-danger">Tự động sửa lỗi đồng bộ, sai thời gian hệ thống: <i class="bi bi-question-circle-fill" onclick="show_message('Tự động sửa lỗi đồng bộ, sai thời gian trên hệ thống OS khi chương trình VBot khởi chạy')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="fix_time_sync_error" id="fix_time_sync_error" <?php echo $Config['smart_config']['fix_time_sync_error'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>
			
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Log Hệ Thống:</h5>
               <div class="alert alert-success" role="alert">  <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Bật, Tắt Logs Hệ Thống <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt log của toàn bộ chương trình khi được chạy')"></i> :</label>
                  <div class="col-sm-9">
                    <div class="form-switch">
                      <input class="form-check-input border-success" type="checkbox" name="log_active" id="log_active" <?php echo $Config['smart_config']['show_log']['active'] ? 'checked' : ''; ?>>
                    </div>
                  </div>
                </div>
                <?php
                echo select_field(
                  'log_display_style',
                  'Kiểu hiển Thị Logs',
                  [
                    'console' => 'console (Hiển thị log ra bảng điều khiển đầu cuối)',
                    'api' => 'api (Hiển thị log ra API, Web UI)',
                    'all' => 'all (Hiển thị log ra tất cả các đường)'
                  ], $Config['smart_config']['show_log']['log_display_style'], []);
                ?>
              </div>
            </div>
            </div>
			
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Khôi Phục Config.json <i class="bi bi-question-circle-fill" onclick="show_message('Khôi Phục Config.json từ tệp sao lưu trên hệ thống')"></i>:</h5>
                <div class="alert alert-success" role="alert"> <div class="row mb-3">
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
                    $jsonFiles = glob($Config['backup_upgrade']['config_json']['backup_path'] . '/*.json');
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
                        echo '<option value="' . htmlspecialchars($Config['backup_upgrade']['config_json']['backup_path'] . '/' . $fileName) . '">' . htmlspecialchars($fileName) . '</option>';
                      }
                      echo '</select>';
                    }

                    if ($co_tep_BackUp_ConfigJson === true) {
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
            </div>
			
            <div class="row mb-3">
              <center>
                <button type="submit" name="all_config_save" value="" class="btn btn-primary rounded-pill"><i class="bi bi-save"></i> Lưu Cài Đặt</button>
                <button type="button" class="btn btn-warning rounded-pill" onclick="readJSON_file_path('<?php echo $Config_filePath; ?>')"><i class="bi bi-eye"></i> Xem Tệp Config</button>
                <button type="button" class="btn btn-success rounded-pill" title="Tải Xuống file: Config.json" onclick="downloadFile('<?php echo $Config_filePath; ?>')"><i class="bi bi-download"></i> Tải Xuống</button>
                <button type="submit" name="all_config_save" value="and_restart_VBot" class="btn btn-danger rounded-pill"><i class="bi bi-save"></i> Lưu Cài Đặt Và Restart VBot</button>
              </center>
              <!-- Modal hiển thị tệp Config.json -->
              <div class="modal fade" id="myModal_Config" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
                <div class="modal-dialog" id="modal_dialog_show_config" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <b>
                        <font color=blue>
                          <div id="name_file_showzz"></div>
                        </font>
                      </b>
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
  </main>
  <!-- End #main -->
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
  <script src="assets/vendor/prism/prism.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <script src="assets/vendor/prism/prism-json.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <script src="assets/vendor/prism/prism-yaml.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <?php
  include 'html_js.php';
  ?>
  <script src="assets/js/Config.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
  <script>
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

    //xem nội dung file json
    function readJSON_file_path(filePath) {
      if (filePath === "get_value_backup_config") {
        //Lấy giá trị value của id: backup_config_json_files
        var get_value_backup_config = document.getElementById('backup_config_json_files').value;
        if (get_value_backup_config === "") {
          showMessagePHP("Không có tệp nào được chọn để xem nội dung");
        } else {
          filePath = "<?php echo $directory_path; ?>/" + get_value_backup_config;
          read_loadFile(filePath);
          document.getElementById('name_file_showzz').textContent = "Tên File: " + filePath.split('/').pop();
          $('#myModal_Config').modal('show');
        }
      } else {
        read_loadFile(filePath);
        document.getElementById('name_file_showzz').textContent = "Tên File: " + filePath.split('/').pop();
        $('#myModal_Config').modal('show');
      }
    }
    //Thêm mới đài Radio
    let radioIndex = <?php echo count($radio_data); ?>;
    const maxRadios = <?php echo $Max_Radios; ?>;
    function addRadio() {
      if (radioIndex < maxRadios) {
        const table = document.getElementById('radio-table').getElementsByTagName('tbody')[0];
        const newRow = document.createElement('tr');
        newRow.id = 'radio-row-'+radioIndex;
        newRow.innerHTML =
          '<td><input type="text" class="form-control border-success" placeholder="Nhập tên đài" name="radio_name_'+radioIndex+'" id="radio_name_'+radioIndex+'"></td>' +
		  '<td><select class="form-select border-warning" id="radio_select_'+radioIndex+'" style="display:none;" onchange="updateRadioLinkName('+radioIndex+', this)"></select>' +
		  '<div class="input-group mb-3" id="link-group-'+radioIndex+'">' +
          '<input type="text" class="form-control border-success" placeholder="Nhập link đài" name="radio_link_'+radioIndex+'" id="radio_link_'+radioIndex+'">' +
		  '<button type="button" class="btn btn-primary" onclick="showRadioSelect('+radioIndex+', \'' + '<?php echo $HTML_VBot_Offline; ?>' + '\')"><i class="bi bi-list-task"></i></button>' +
          '</div></td>' +
          '<td style="text-align: center; vertical-align: middle;"><center>' +
          '<button type="button" class="btn btn-danger" id="delete-radio-'+radioIndex+'" onclick="delete_Dai_bao_Radio('+radioIndex+', null)"><i class="bi bi-trash"></i></button>' +
        '</center></td>';
        table.appendChild(newRow);
        radioIndex++;
      } else {
        show_message("<center>Bạn chỉ có thể thêm tối đa <b>" + maxRadios + "</b> đài radio</center>");
      }
    }

    //Thêm mới Báo, tin tức
    let newspaperIndex = <?php echo count($newspaper_data); ?>;
    const maxNewsPaper = <?php echo $Max_NewsPaper; ?>;
    function addNewsPaper() {
      if (newspaperIndex < maxNewsPaper) {
        const table = document.getElementById('newspaper-table').getElementsByTagName('tbody')[0];
        const newRow = document.createElement('tr');
        newRow.id = 'newspaper-row-' + newspaperIndex;
        newRow.innerHTML =
          '<td><input type="text" class="form-control border-success" placeholder="Nhập tên Báo, Tin Tức" name="newspaper_name_' + newspaperIndex + '" id="newspaper_name_' + newspaperIndex + '"></td>' +
          '<td><select class="form-select border-warning" id="newspaper_select_'+newspaperIndex+'" style="display:none;" onchange="updateNewsPaperLinkName('+newspaperIndex+', this)"></select>' +
		  '<div class="input-group mb-3" id="newspaper-link-group-'+newspaperIndex+'">' +
          '<input type="text" class="form-control border-success" placeholder="Nhập link/url Báo, Tin Tức" name="newspaper_link_' + newspaperIndex + '" id="newspaper_link_' + newspaperIndex + '">' +
		  '<button type="button" class="btn btn-primary" onclick="showNewsPaperSelect('+newspaperIndex+', \'' + '<?php echo $HTML_VBot_Offline; ?>' + '\')"><i class="bi bi-list-task"></i></button>' +
          '</div></td>' +
          '<td style="text-align: center; vertical-align: middle;"><center>' +
          '<button type="button" class="btn btn-danger" id="delete-newspaper-' + newspaperIndex + '" onclick="delete_NewsPaper(' + newspaperIndex + ', null)"><i class="bi bi-trash"></i></button>' +
        '</center></td>';
        table.appendChild(newRow);
        newspaperIndex++;
      } else {
        show_message("<center>Bạn chỉ có thể thêm tối đa <b>" + maxNewsPaper + "</b> kênh báo, tin tức</center>");
      }
    }

    // Cập nhật giá trị của thuộc tính onclick của nút sound_welcome_file_path vào nút nghe thử play_Audio_Welcome
    function updateButton_Audio_Welcome() {
      const selectElement = document.getElementById('sound_welcome_file_path');
      const filePath = '<?php echo $VBot_Offline; ?>' + selectElement.value;
      const button = document.getElementById('play_Audio_Welcome');
      if (button) {
        button.onclick = function() {
          playAudio(filePath);
        };
      }
    }

    //Dành cho Test Led
    function test_led(action) {
      if (<?php echo $Config['smart_config']['led']['active'] ? 'true' : 'false'; ?> === false) {
        show_message("Chế độ sử dụng Led không được kích hoạt");
        return;
      }
      let led_action;
      let led_value = "";
      switch (action) {
        case "speak":
        case "pause":
        case "loading":
        case "startup":
        case "error":
          led_action = action;
          led_value = null;
          break;
        case "led_think":
          led_action = "think";
          led_value = document.getElementById(action)?.value || "";
          break;
        case "led_mute":
          led_action = "mute";
          led_value = document.getElementById(action)?.value || "";
          break;
        case "led_off":
          led_action = "off";
          led_value = document.getElementById(action)?.value || "";
          break;
        default:
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
        show_message("Không thể kết nối tới server. Vui lòng kiểm tra kết nối mạng, Hoặc chương trình chưa được chạy");
        loading("hide");
      };
      xhr.open("POST", "<?php echo $Protocol . $serverIp . ':' . $Port_API; ?>");
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.send(data);
    }

    //Tải danh sách giọng đọc của google tts cloud
    function load_list_GoogleVoices_tts(select_tts_gcloud, loadingg = 'no') {
      if (loadingg === 'ok') loading("show");
      let selectElement_tts_gcloud;
      let currentSelectedVoice_tts_gcloud;
      if (select_tts_gcloud === 'tts_ggcloud') {
        selectElement_tts_gcloud = document.getElementById('tts_ggcloud_voice_name');
        currentSelectedVoice_tts_gcloud = '<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name']; ?>';
      } else if (select_tts_gcloud === 'tts_ggcloud_key') {
        selectElement_tts_gcloud = document.getElementById('tts_ggcloud_key_voice_name');
        currentSelectedVoice_tts_gcloud = '<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name']; ?>';
      } else if (select_tts_gcloud === 'tts_audio_reply') {
        selectElement_tts_gcloud = document.getElementById('tts_audio_reply_voice_name');
        currentSelectedVoice_tts_gcloud = 'vi-VN-Neural2-A';
      }
      if (!selectElement_tts_gcloud) {
        if (loadingg === 'ok') loading("hide");
        showMessagePHP("Không tìm thấy thẻ select tts gcloud được chọn", 5);
        return;
      }
      function loadJSON(url, useRaw = false) {
        const xhr = new XMLHttpRequest();
        xhr.open("GET", url, true);
        if (useRaw) {
          xhr.setRequestHeader("Accept", "application/vnd.github.v3.raw");
        }
        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4) {
            if (xhr.status === 200) {
              try {
                const data = JSON.parse(xhr.responseText);
                const voiceList = data.voice_list_vi_vn;
                selectElement_tts_gcloud.innerHTML = "";
                voiceList.forEach(function(voice) {
                  const option = document.createElement("option");
                  option.value = voice;
                  option.textContent = voice;
                  if (voice === currentSelectedVoice_tts_gcloud) {
                    option.selected = true;
                    option.setAttribute("selected", "");
                  }
                  selectElement_tts_gcloud.appendChild(option);
                });
                if (loadingg === 'ok') {
                  showMessagePHP("Đã tải danh sách giọng đọc TTS Google Cloud", 5);
                  loading("hide");
                }
              } catch (e) {
                if (useRaw) {
                  loadJSON("includes/other_data/list_voices_tts_gcloud.json");
                } else {
                  if (loadingg === 'ok') loading("hide");
                  showMessagePHP("Lỗi phân tích JSON tts GCloud: " + e, 5);
                }
              }
            } else {
              if (useRaw) {
                loadJSON("includes/other_data/list_voices_tts_gcloud.json");
              } else {
                if (loadingg === 'ok') loading("hide");
                showMessagePHP("Không thể tải file JSON tts GCloud. Mã lỗi HTTP: " + xhr.status, 5);
              }
            }
          }
        };
        xhr.send();
      }
      loadJSON("https://api.github.com/repos/marion001/VBot_Offline/contents/html/includes/other_data/list_voices_tts_gcloud.json", true);
    }
    document.addEventListener('DOMContentLoaded', function() {
      updateButton_Audio_Welcome();
      document.getElementById('sound_welcome_file_path').addEventListener('change', updateButton_Audio_Welcome)
      selectHotwordWakeup();
    });
	document.getElementById("hotword_select_wakeup").addEventListener("change", function () {
    selectHotwordWakeup();
	});
  </script>

</body>

</html>