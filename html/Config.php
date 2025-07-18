<?php
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
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
  #$Config['smart_config']['led']['brightness'] = intval($_POST['led_brightness']);
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
  
  #Cập nhật giá trị đọc truyện, kể truyện 
  $Config['media_player']['podcast']['active'] = isset($_POST['podcast_active']) ? true : false;
  $Config['media_player']['podcast']['allows_priority_use_of_virtual_assistants'] = isset($_POST['podcast_virtual_assistants_active']) ? true : false;
  
  #cẬP NHẬT GIÁ TRỊ youtube
  $Config['media_player']['youtube']['google_apis_key'] = $_POST['youtube_google_apis_key'];
  $Config['media_player']['youtube']['active'] = isset($_POST['youtube_active']) ? true : false;
  
  #Cập nhật giá trị Zingmp3 xem có kích hoạt hay không
  $Config['media_player']['zing_mp3']['active'] = isset($_POST['zing_mp3_active']) ? true : false;
  
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

  #Cập nhật Streaming HTTP POST
  $Config['api']['streaming_server']['protocol']['http_post']['port'] = intval($_POST['port_server_streaming_audio']);
  $Config['api']['streaming_server']['protocol']['http_post']['working_mode'] = $_POST['streaming_server_working_mode'];
  $Config['api']['streaming_server']['protocol']['http_post']['source_stt'] = $_POST['streaming_server_source_stt'];
  $Config['api']['streaming_server']['protocol']['http_post']['max_stream_audio_empty'] = intval($_POST['max_stream_audio_empty']);

  #Cập nhật Streaming Socket
  $Config['api']['streaming_server']['protocol']['socket']['port'] = intval($_POST['port_server_socket_streaming_audio']);
  $Config['api']['streaming_server']['protocol']['socket']['maximum_recording_time'] = intval($_POST['socket_maximum_recording_time']);
  $Config['api']['streaming_server']['protocol']['socket']['maximum_client_connected'] = intval($_POST['socket_maximum_client_connected']);
  $Config['api']['streaming_server']['protocol']['socket']['source_stt'] = $_POST['streaming_server_source_stt_socket'];
  $Config['api']['streaming_server']['protocol']['socket']['working_mode'] = $_POST['streaming_server_working_mode_socket'];
  $Config['api']['streaming_server']['protocol']['socket']['select_wakeup'] = $_POST['streaming_server_select_wakeup_socket'];
  
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
  
  #Đồng thời Restart VBot nếu được nhấn
  if ($_POST['all_config_save'] === 'and_restart_VBot'){
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
      </div>
      <!-- End Page Title -->
      <form class="row g-3 needs-validation" id="hotwordForm" enctype="multipart/form-data" novalidate method="POST" action="">
        <section class="section">
          <div class="row">
            <div class="col-lg-12">
              <div class="card accordion" id="accordion_button_ssh">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_ssh" aria-expanded="false" aria-controls="collapse_button_ssh">
                    Cấu Hình Kết Nối SSH Server <font color="red"> (Bắt Buộc)</font>:
                  </h5>
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
                    Cấu Hình Web Interface (Giao Diện): 
                  </h5>
                  <div id="collapse_button_webui_path" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_webui_path">
                    <div class="row mb-3">
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
                    Cấu Hình API VBot Server:
                  </h5>
                  <div id="collapse_button_setting_API" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_setting_API">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích Hoạt API <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng và giao tiếp với VBot thông qua API')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="api_active" id="api_active" <?php echo $Config['api']['active'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="api_port" class="col-sm-3 col-form-label">Port API:</label>
                      <div class="col-sm-9">
                        <div class="input-group mb-3">
                          <input required type="number" class="form-control border-success" name="api_port" id="api_port" max="9999" placeholder="<?php echo htmlspecialchars($Config['api']['port']) ?>" value="<?php echo htmlspecialchars($Config['api']['port']) ?>">
                          <div class="invalid-feedback">Cần nhập cổng Port dành cho API!</div>
                          <button class="btn btn-success border-success" type="button" title="<?php echo $Protocol.$serverIp.':'.$Port_API; ?>"><a title="<?php echo $Protocol.$serverIp.':'.$Port_API; ?>" style="text-decoration: none; color: inherit;" href="<?php echo $Protocol.$serverIp.':'.$Port_API; ?>" target="_blank">Kiểm Tra</a></button>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Hiển Thị Log API <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt hiển thị log của API, Chỉ hiển thị khi Debug trực tiếp trên Console, Terminal')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="api_log_active" id="api_log_active" <?php echo $Config['api']['show_log']['active'] ? 'checked' : ''; ?>>
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
					
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Danh Sách Dữ Liệu API:</label>
                      <div class="col-sm-9">
                        <div class="input-group mb-3">
                          <input disabled class="form-control border-danger" type="text" value="http://<?php echo $serverIp; ?>/API_List.php">
                          <button class="btn btn-success border-danger" type="button"><a style="color: white;" href="/API_List.php" target="_blank">Truy Cập</a></button>
                        </div>
                      </div>
                    </div>
					
                  </div>
                </div>
              </div>

      <div class="card accordion" id="accordion_button_streaming_server_audio">
      <div class="card-body">
      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_streaming_server_audio" aria-expanded="false" aria-controls="collapse_button_streaming_server_audio">
      Streming Audio Server <font color=red> (VBot Client, Client - Server) </font><i class="bi bi-question-circle-fill" onclick="show_message('Kiểm Tra Và Test Hãy Truy Cập Vào Trang <b>Hướng Dẫn</b> Hoặc <a href=\'FAQ.php\' target=\'_bank\'>Nhấn Vào Đây</a>')"></i>:</h5>
      <div id="collapse_button_streaming_server_audio" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_streaming_server_audio">

                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng thiết bị chạy chương trình VBot làm Server')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="streming_active" id="streming_active" <?php echo $Config['api']['streaming_server']['active'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="streaming_server_connection_protocol" class="col-sm-3 col-form-label">Kiểu Loại Kết Nối:</label>
                      <div class="col-sm-9">
                        <select name="streaming_server_connection_protocol" id="streaming_server_connection_protocol" class="form-select border-success" aria-label="Default select example">
                          <option value="udp_sock" <?php echo $Config['api']['streaming_server']['connection_protocol'] === 'udp_sock' ? 'selected' : ''; ?>>Sử dụng ESP32, ESP32 D1 Mini, ESP32S3, ESP32S3 Supper Mini</option>
						  <option value="socket" <?php echo $Config['api']['streaming_server']['connection_protocol'] === 'socket' ? 'selected' : ''; ?>>Socket</option>
                          <option value="http_post" <?php echo $Config['api']['streaming_server']['connection_protocol'] === 'http_post' ? 'selected' : ''; ?>>HTTP POST</option>
						</select>
                      </div>
                    </div>

			  <div class="card accordion" id="accordion_button_udp_server_streaming">
			  <div class="card-body">
			  <h5 class="card-title accordion-button collapsed text-danger" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_udp_server_streaming" aria-expanded="false" aria-controls="collapse_button_udp_server_streaming">
			  Cấu hình nếu sử dụng: ESP32, ESP32S3, ESP32 D1 Mini, Raspberry Pi:</h5>
			  <div id="collapse_button_udp_server_streaming" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_udp_server_streaming">

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
					<div class="row mb-3">
                      <label for="port_server_udp_streaming_audio" class="col-sm-3 col-form-label">Port Server:</label>
                      <div class="col-sm-9">
                          <input required type="number" class="form-control border-success" name="port_server_udp_streaming_audio" id="port_server_udp_streaming_audio" max="9999" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['udp_sock']['port']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['udp_sock']['port']) ?>">
                          <div class="invalid-feedback">Cần nhập cổng Port dành cho Server Streaming Audio!</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_maximum_recording_time" class="col-sm-3 col-form-label">Thời Gian Thu Âm Tối Đa (s):</label>
                      <div class="col-sm-9">
                        <input required type="number" class="form-control border-success" name="udp_maximum_recording_time" id="udp_maximum_recording_time" max="10" step="1" min="1" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['udp_sock']['maximum_recording_time']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['udp_sock']['maximum_recording_time']) ?>">
                        <div class="invalid-feedback">Cần nhập thời gian thu âm tối đa khi được đánh thức</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_maximum_client_connected" class="col-sm-3 col-form-label">Tối Đa Client Kết Nối:</label>
                      <div class="col-sm-9">
                        <input required type="number" class="form-control border-success" name="udp_maximum_client_connected" id="udp_maximum_client_connected" max="20" step="1" min="1" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['udp_sock']['maximum_client_connected']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['udp_sock']['maximum_client_connected']) ?>">
                        <div class="invalid-feedback">Cần nhập Tối Đa Số Lượng Client Cho Phép Kết Nối</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_time_remove_inactive_clients" class="col-sm-3 col-form-label">Thời gian dọn dẹp Client (s):</label>
                      <div class="col-sm-9">
                        <input required type="number" class="form-control border-success" name="udp_time_remove_inactive_clients" id="udp_time_remove_inactive_clients" step="1" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['udp_sock']['time_remove_inactive_clients']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['udp_sock']['time_remove_inactive_clients']) ?>">
                        <div class="invalid-feedback">Cần nhập thời gian dọn dẹp các Client không hoạt động trong một khoảng thời gian</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_source_stt" class="col-sm-3 col-form-label">Nguồn xử lý âm thanh STT Cho Client:</label>
                      <div class="col-sm-9">
                        <select name="udp_source_stt" id="udp_source_stt" class="form-select border-success" aria-label="Default select example">
                          <option value="stt_default" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['source_stt'] === 'stt_default' ? 'selected' : ''; ?>>STT Mặc Định VBot (Free)</option>
                          <option value="stt_ggcloud" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['source_stt'] === 'stt_ggcloud' ? 'selected' : ''; ?>>STT Google Cloud V1</option>
						</select>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_working_mode" class="col-sm-3 col-form-label">Chế Độ Làm Việc:</label>
                      <div class="col-sm-9">
                        <select name="udp_working_mode" id="udp_working_mode" class="form-select border-success" aria-label="Default select example">
                          <option value="main_processing" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['working_mode'] === 'main_processing' ? 'selected' : ''; ?>>main_processing (Loa Server chạy VBot xử lý và thực thi)</option>
                          <option disabled value="null" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['working_mode'] === 'null' ? 'selected' : ''; ?>>null (Chỉ xử lý STT to Text)</option>
						</select>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_select_wakeup" class="col-sm-3 col-form-label">Nguồn Đánh Thức Hotword Client:</label>
                      <div class="col-sm-9">
                        <select name="udp_select_wakeup" id="udp_select_wakeup" class="form-select border-success" aria-label="Default select example">
                          <option value="porcupine" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['select_wakeup'] === 'porcupine' ? 'selected' : ''; ?>>Picovoice/Porcupine (WakeUp Client)</option>
                          <option value="snowboy" <?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['select_wakeup'] === 'snowboy' ? 'selected' : ''; ?>>Snowboy (WakeUP Client)</option>
						</select>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_server_data_client_name" class="col-sm-3 col-form-label">Tệp Dữ Liệu Client:</label>
                      <div class="col-sm-9">
                        <input readonly type="text" class="form-control border-danger" name="udp_server_data_client_name" id="udp_server_data_client_name" placeholder="<?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['data_client_name']; ?>" value="<?php echo $Config['api']['streaming_server']['protocol']['udp_sock']['data_client_name']; ?>">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_server_streaming_audio" class="col-sm-3 col-form-label">Server Streaming Audio:</label>
                      <div class="col-sm-9">
                        <input readonly type="text" class="form-control border-danger" name="udp_server_streaming_audio" id="udp_server_streaming_audio" placeholder="<?php echo htmlspecialchars($serverIp.':'.$Port_Server_Streaming_Audio_UDP); ?>" value="<?php echo htmlspecialchars($serverIp.':'.$Port_Server_Streaming_Audio_UDP); ?>">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="udp_server_streaming_audio_local" class="col-sm-3 col-form-label">URL Audio Local:</label>
                      <div class="col-sm-9">
						<div class="input-group mb-3">
                        <input readonly type="text" class="form-control border-danger" name="udp_server_streaming_audio_local" id="udp_server_streaming_audio_local" value="<?php echo htmlspecialchars('http://'.$serverIp.'/assets/sound/'); ?>">
						<button class="btn btn-success border-danger" type="button"><a style="color: white;" href="<?php echo htmlspecialchars('http://'.$serverIp.'/assets/sound/'); ?>" target="_blank">Truy Cập</a></button>
					  </div>
                      </div>
                    </div>

					<div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Flash Firmware URL:</label>
                      <div class="col-sm-9">
                        <div class="input-group mb-3">
                          <input disabled="" class="form-control border-danger" type="text" placeholder="https://github.com/marion001/VBot_Client_Offline" title="https://github.com/marion001/VBot_Client_Offline" value="https://github.com/marion001/VBot_Client_Offline">
                          <button class="btn btn-success border-danger" type="button"><a style="color: white;" href="https://github.com/marion001/VBot_Client_Offline" target="_blank">Truy Cập</a></button>
                        </div>
                      </div>
                    </div>

			  </div>
			  </div>
			  </div>

				  <div class="card accordion" id="accordion_button_socket_server_socket">
				  <div class="card-body">
				  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_socket_server_socket" aria-expanded="false" aria-controls="collapse_button_socket_server_socket">
				  Chế Độ Kết Nối Socket <font color=red> (Đang Phát Triển) </font> <i class="bi bi-question-circle-fill" onclick="show_message('Kiểm Tra Và Test Hãy Truy Cập Vào Trang <b>Hướng Dẫn</b> Hoặc <a href=\'FAQ.php\' target=\'_bank\'>Nhấn Vào Đây</a>')"></i>:</h5>
				  <div id="collapse_button_socket_server_socket" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_socket_server_socket">

					<div class="row mb-3">
                      <label for="port_server_socket_streaming_audio" class="col-sm-3 col-form-label">Port Server Socket:</label>
                      <div class="col-sm-9">
                          <input required type="number" class="form-control border-danger" name="port_server_socket_streaming_audio" id="port_server_socket_streaming_audio" max="9999" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['socket']['port']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['socket']['port']) ?>">
                          <div class="invalid-feedback">Cần nhập cổng Port dành cho Server Streaming Audio Socket!</div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="socket_maximum_recording_time" class="col-sm-3 col-form-label">Thời Gian Thu Âm Tối Đa:</label>
                      <div class="col-sm-9">
                        <input required type="number" class="form-control border-danger" name="socket_maximum_recording_time" id="socket_maximum_recording_time" max="10" step="1" min="1" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['socket']['maximum_recording_time']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['socket']['maximum_recording_time']) ?>">
                        <div class="invalid-feedback">Cần nhập thời gian thu âm tối đa khi được đánh thức</div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="socket_maximum_client_connected" class="col-sm-3 col-form-label">Tối Đa Client Kết Nối:</label>
                      <div class="col-sm-9">
                        <input required type="number" class="form-control border-danger" name="socket_maximum_client_connected" id="socket_maximum_client_connected" max="20" step="1" min="1" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['socket']['maximum_client_connected']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['socket']['maximum_client_connected']) ?>">
                        <div class="invalid-feedback">Cần nhập Tối Đa Số Lượng Client Cho Phép Kết Nối</div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="streaming_server_source_stt_socket" class="col-sm-3 col-form-label">Nguồn xử lý âm thanh STT Cho Client:</label>
                      <div class="col-sm-9">
                        <select name="streaming_server_source_stt_socket" id="streaming_server_source_stt_socket" class="form-select border-danger" aria-label="Default select example">
                          <option value="stt_default" <?php echo $Config['api']['streaming_server']['protocol']['socket']['source_stt'] === 'stt_default' ? 'selected' : ''; ?>>STT Mặc Định VBot (Free)</option>
						</select>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="streaming_server_working_mode_socket" class="col-sm-3 col-form-label">Chế Độ Làm Việc:</label>
                      <div class="col-sm-9">
                        <select name="streaming_server_working_mode_socket" id="streaming_server_working_mode_socket" class="form-select border-danger" aria-label="Default select example">
                          <option value="main_processing" <?php echo $Config['api']['streaming_server']['protocol']['socket']['working_mode'] === 'main_processing' ? 'selected' : ''; ?>>main_processing (Loa Server chạy VBot xử lý và thực thi)</option>
                          <option disabled value="null" <?php echo $Config['api']['streaming_server']['protocol']['socket']['working_mode'] === 'null' ? 'selected' : ''; ?>>null (Chỉ xử lý STT to Text)</option>
						</select>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="streaming_server_select_wakeup_socket" class="col-sm-3 col-form-label">Nguồn Đánh Thức Hotword Client:</label>
                      <div class="col-sm-9">
                        <select name="streaming_server_select_wakeup_socket" id="streaming_server_select_wakeup_socket" class="form-select border-danger" aria-label="Default select example">
                          <option value="porcupine" <?php echo $Config['api']['streaming_server']['protocol']['socket']['select_wakeup'] === 'porcupine' ? 'selected' : ''; ?>>Picovoice/Porcupine (WakeUp Client)</option>
                          <option value="snowboy" <?php echo $Config['api']['streaming_server']['protocol']['socket']['select_wakeup'] === 'snowboy' ? 'selected' : ''; ?>>Snowboy (WakeUP Client)</option>
						</select>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="url_server_streaming_audio_socket" class="col-sm-3 col-form-label">URL Server Socket Streaming Audio:</label>
                      <div class="col-sm-9">
						<div class="input-group mb-3">
                        <input readonly type="text" class="form-control border-danger" name="url_server_streaming_audio_socket" id="url_server_streaming_audio_socket" placeholder="<?php echo htmlspecialchars('ws://'.$serverIp.':'.$Port_Server_Streaming_Audio_Socket); ?>" value="<?php echo htmlspecialchars('ws://'.$serverIp.':'.$Port_Server_Streaming_Audio_Socket); ?>">
						<button class="btn btn-success border-danger" type="button" title="Kiểm tra kết nối Socket Server Streaming" onclick="connectWebSocketAndSendID()">Kiểm Tra</button>
					  </div>
                      </div>
                    </div>

				  </div>
				  </div>
				  </div>

				  <div class="card accordion" id="accordion_button_socket_server_http">
				  <div class="card-body">
				  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_socket_server_http" aria-expanded="false" aria-controls="collapse_button_socket_server_http">
				  Chế Độ Kết Nối HTTP POST <i class="bi bi-question-circle-fill" onclick="show_message('Chưa có hướng dẫn ở chế độ này')"></i>  <font color=red>(Đang Phát Triển)</font></h5>
				  <div id="collapse_button_socket_server_http" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_socket_server_http">

					<div class="row mb-3">
                      <label for="api_port" class="col-sm-3 col-form-label">Port Server HTTP:</label>
                      <div class="col-sm-9">
                        <div class="input-group mb-3">
                          <input required type="number" class="form-control border-danger" name="port_server_streaming_audio" id="port_server_streaming_audio" max="9999" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['http_post']['port']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['http_post']['port']) ?>">
                          <div class="invalid-feedback">Cần nhập cổng Port dành cho Server Streaming Audio HTTP POST!</div>
                          <button class="btn btn-success border-danger" type="button" title="<?php echo $Protocol.$serverIp.':'.$Port_Server_Streaming_Audio.'/vbot/stream_audio_server'; ?>"><a title="<?php echo $Protocol.$serverIp.':'.$Port_Server_Streaming_Audio.'/vbot/stream_audio_server'; ?>" style="text-decoration: none; color: inherit;" href="<?php echo $Protocol.$serverIp.':'.$Port_Server_Streaming_Audio.'/vbot/stream_audio_server'; ?>" target="_blank">Kiểm Tra</a></button>
                        </div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="streaming_server_working_mode" class="col-sm-3 col-form-label">Chế Độ Làm Việc:</label>
                      <div class="col-sm-9">
                        <select name="streaming_server_working_mode" id="streaming_server_working_mode" class="form-select border-danger" aria-label="Default select example">
                          <option value="main_processing" <?php echo $Config['api']['streaming_server']['protocol']['http_post']['working_mode'] === 'main_processing' ? 'selected' : ''; ?>>main_processing (Loa Server chạy VBot xử lý và thực thi)</option>
                          <option value="null" <?php echo $Config['api']['streaming_server']['protocol']['http_post']['working_mode'] === 'null' ? 'selected' : ''; ?>>null (Chỉ xử lý STT to Text)</option>
						</select>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="streaming_server_source_stt" class="col-sm-3 col-form-label">Nguồn xử lý âm thanh STT Từ Client:</label>
                      <div class="col-sm-9">
                        <select name="streaming_server_source_stt" id="streaming_server_source_stt" class="form-select border-danger" aria-label="Default select example">
                          <option value="stt_default" <?php echo $Config['api']['streaming_server']['protocol']['http_post']['source_stt'] === 'stt_default' ? 'selected' : ''; ?>>STT Mặc Định VBot (Free)</option>
						</select>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="max_stream_audio_empty" class="col-sm-3 col-form-label">Thời Gian Chờ Tối Đa:</label>
                      <div class="col-sm-9">
                        <input required type="number" class="form-control border-danger" name="max_stream_audio_empty" id="max_stream_audio_empty" max="20" step="1" min="1" placeholder="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['http_post']['max_stream_audio_empty']) ?>" value="<?php echo htmlspecialchars($Config['api']['streaming_server']['protocol']['http_post']['max_stream_audio_empty']) ?>">
                        <div class="invalid-feedback">Cần nhập thời gian chờ tối đa, mặc định 5</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="url_server_streaming_audio" class="col-sm-3 col-form-label">URL Server HTTP POST Streaming Audio:</label>
                      <div class="col-sm-9">
                        <input readonly type="text" class="form-control border-danger" name="url_server_streaming_audio" id="url_server_streaming_audio" placeholder="<?php echo htmlspecialchars($Protocol.$serverIp.':'.$Port_Server_Streaming_Audio.'/vbot/stream_audio_server'); ?>" value="<?php echo htmlspecialchars($Protocol.$serverIp.':'.$Port_Server_Streaming_Audio.'/vbot/stream_audio_server'); ?>">
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
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title" title="Âm Lượng (Volume)/Audio Out">Cài Đặt Mic &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Bạn có thể tham khảo hướng dẫn tại đây: <a href=\'FAQ.php\' target=\'_bank\'>Hướng Dẫn</a>')"></i> &nbsp;:</h5>
                        <div class="row mb-3">
                          <label for="mic_id" class="col-sm-3 col-form-label">ID Mic <i class="bi bi-question-circle-fill" onclick="show_message('Bạn có thể tham khảo hướng dẫn tại đây: <a href=\'FAQ.php\' target=\'_bank\'>Hướng Dẫn</a>')"></i>:</label>
                          <div class="col-sm-9">
                            <div class="input-group mb-3">
                              <input required class="form-control border-success" type="number" name="mic_id" id="mic_id" placeholder="<?php echo $Config['smart_config']['mic']['id']; ?>" value="<?php echo $Config['smart_config']['mic']['id']; ?>">
                              <div class="invalid-feedback">Cần nhập ID của Mic!</div>
                              <button class="btn btn-success border-success" type="button" onclick="scan_audio_devices('scan_mic')">Tìm Kiếm ID Mic</button>
                            </div>
                          </div>
                        </div>
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
              <div class="card accordion" id="accordion_button_hotword_engine">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_hotword_engine" aria-expanded="false" aria-controls="collapse_button_hotword_engine">
                    Cấu Hình WakeUP Hotword Engine (Từ Đánh Thức) Picovoice/Snowboy:
                  </h5>
                  <div id="collapse_button_hotword_engine" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_hotword_engine" style="">
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Hotword / Từ Nóng Đánh Thức <i class="bi bi-question-circle-fill" onclick="show_message('Danh sách file thư viện Porcupine: <a href=\'https://github.com/Picovoice/porcupine/tree/master/lib/common\' target=\'_bank\'>Github</a><br/>Mẫu các từ khóa đánh thức: <a href=\'https://github.com/Picovoice/porcupine/tree/master/resources\' target=\'_bank\'>Github</a>')"></i> :</h5>
                        <div class="row mb-3">
                          <label for="continue_running_if_hotword_initialization_fails" class="col-sm-3 col-form-label">Cho Phép Chạy Chương Trình Khi Lỗi Khởi Tạo Hotword, Wakeup: <i class="bi bi-question-circle-fill" onclick="show_message('Cho Phép Chương Trình Tiếp Tục Khởi Chạy Khi Tiến Trình khởi Tạo Từ Đánh Thức Wake Up Gặp Lỗi. (Và sẽ không dùng được Từ nóng Hotword để đánh thức)')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="continue_running_if_hotword_initialization_fails" id="continue_running_if_hotword_initialization_fails" <?php echo $Config['smart_config']['smart_wakeup']['hotword']['continue_running_if_hotword_initialization_fails'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="hotword_select_wakeup" class="col-sm-3 col-form-label">Chọn Nguồn Đánh Thức:</label>
                          <div class="col-sm-9">
                            <select name="hotword_select_wakeup" id="hotword_select_wakeup" class="form-select border-success" aria-label="Default select example" onchange="selectHotwordWakeup()">
                              <option value="porcupine" <?php echo $Config['smart_config']['smart_wakeup']['hotword']['select_wakeup'] === 'porcupine' ? 'selected' : ''; ?>>Picovoice/Procupine</option>
                              <option value="snowboy" <?php echo $Config['smart_config']['smart_wakeup']['hotword']['select_wakeup'] === 'snowboy' ? 'selected' : ''; ?>>Snowboy</option>
                              <option value="null" <?php echo $Config['smart_config']['smart_wakeup']['hotword']['select_wakeup'] === null ? 'selected' : ''; ?>>Không Sử Dụng Hotword</option>
                            </select>
                          </div>
                        </div>
                        <!-- nếu hotword được chọn là Picovoice Procupine -->
                        <div id="select_show_picovoice_porcupine">
                          <div class="row mb-3">
                            <label for="hotword_engine_key" class="col-sm-3 col-form-label">Picovoice Token Key: <i class="bi bi-question-circle-fill" onclick="show_message('Đăng ký, lấy key: <a href=\'https://console.picovoice.ai\' target=\'_bank\'>https://console.picovoice.ai</a>')"></i></label>
                            <div class="col-sm-9">
                              <div class="input-group mb-3">
                                <input required class="form-control border-success" type="text" name="hotword_engine_key" id="hotword_engine_key" placeholder="<?php echo $Config['smart_config']['smart_wakeup']['hotword_engine']['key']; ?>" value="<?php echo $Config['smart_config']['smart_wakeup']['hotword_engine']['key']; ?>">
                                <div class="invalid-feedback">Cần nhập key Picovoice để gọi Hotword!</div>
                                <button class="btn btn-success border-success" type="button" onclick="test_key_Picovoice()">Kiểm Tra</button>
                              </div>
                            </div>
                          </div>
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
                                    <center><font color=red>Cài đặt nâng cao Hotword:</font></center>
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
                          <br/>
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
              <div class="card accordion" id="accordion_button_setting_stt">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_stt" aria-expanded="false" aria-controls="collapse_button_setting_stt">
                    Chuyển Giọng Nói Thành Văn Bản - Speak To Text (STT) &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi giọng nói thành văn bản để chương trình xử lý dữ liệu')"></i> &nbsp;:
                  </h5>
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
                                <input class="form-check-input border-success" type="radio" name="stt_select" id="stt_default" value="stt_default" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_default' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="stt_default">STT Mặc Định VBot (Free)</label>
                              </div>
                              <div class="form-check">
                                <input class="form-check-input border-success" type="radio" name="stt_select" id="stt_ggcloud" value="stt_ggcloud" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_ggcloud' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="stt_ggcloud">STT Google Cloud V1 (Authentication.json) <i class="bi bi-question-circle-fill" onclick="show_message('Hướng Dẫn Đăng Ký Hãy Xem Ở Hướng Dẫn Sau Trong Thư  Mục <b>Guide</b> -> <b>Tạo STT Google Cloud</b> <br/><br/>-Link: <a href=\'https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ\' target=\'_bank\'>https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>')"></i></label>
                              </div>
                              <div class="form-check">
                                <input class="form-check-input border-success" type="radio" name="stt_select" id="stt_ggcloud_v2" value="stt_ggcloud_v2" <?php echo $Config['smart_config']['smart_wakeup']['speak_to_text']['stt_select'] === 'stt_ggcloud_v2' ? 'checked' : ''; ?>>
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
                              <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                                <center><font color=red>STT Google Cloud V1 (Authentication.json)</font></center>
                              </h4>
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
                              <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                                <center><font color=red>STT Google Cloud V2 (Authentication.json)</font></center>
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
                              <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                                <center><font color=red>STT Default</font></center>
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
              <div class="card accordion" id="accordion_button_setting_tts">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_tts" aria-expanded="false" aria-controls="collapse_button_setting_tts">
                    Chuyển Văn Bản Thành Giọng Nói - Text To Speak (TTS) &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Chuyển đổi kết quả từ văn bản thành giọng nói để phát ra loa')"></i> &nbsp;: 
                  </h5>
                  <div id="collapse_button_setting_tts" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_tts" style="">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích hoạt Cache lại TTS:</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="cache_tts" id="cache_tts" <?php echo $Config['smart_config']['smart_answer']['cache_tts']['active'] ? 'checked' : ''; ?>>
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
                    <div class="row mb-3">
                      <label for="clean_cache_tts_max_file" class="col-sm-3 col-form-label" title="Tự động dọn dẹp tts nếu số lượng tệp tin vượt quá ngưỡng cho phép">Dọn Dẹp TTS Nếu Vượt Quá (file) <i class="bi bi-question-circle-fill" onclick="show_message('Tự động dọn dẹp tts nếu số lượng tệp tin vượt quá ngưỡng cho phép')"></i> :</label>
                      <div class="col-sm-9">
                        <input required class="form-control border-danger" step="1" min="0" max="3000" type="number" name="clean_cache_tts_max_file" id="clean_cache_tts_max_file" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Mức âm lượng sẽ tăng lên cao nhất" placeholder="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['clean_cache_tts_max_file']; ?>" value="<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['clean_cache_tts_max_file']; ?>">
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
                                <input class="form-check-input border-success" type="radio" name="tts_select" id="tts_default" value="tts_default" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_default' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tts_default">TTS Mặc Định (Free) <i class="bi bi-question-circle-fill" onclick="show_message('Với tts_default này sẽ không mất phí với người dùng và vẫn đảm bảo chất lượng cao, ổn định')"></i></label>
                              </div>
                              <div class="form-check">
                                <input class="form-check-input border-success" type="radio" name="tts_select" id="tts_ggcloud" value="tts_ggcloud" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_ggcloud' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tts_ggcloud">TTS Google Cloud (Authentication.json) <i class="bi bi-question-circle-fill" onclick="show_message('Hướng Dẫn Đăng Ký Hãy Xem Ở Hướng Dẫn Sau Trong Thư  Mục <b>Guide</b> -> <b>Tạo STT Google Cloud</b><br/><br/>-Link: <a href=\'https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ\' target=\'_bank\'>https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ</a>')"></i></label>
                              </div>
                              <div class="form-check">
                                <input class="form-check-input border-success" type="radio" name="tts_select" id="tts_ggcloud_key" value="tts_ggcloud_key" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_ggcloud_key' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tts_ggcloud_key">TTS Google Cloud (Key) hoặc (Free Key) <i class="bi bi-question-circle-fill" onclick="show_message('Cần sử dụng Key của Google Cloud để xác thực hoặc sử dụng Key miễn phí bằng cách lấy thủ công và có thời gian hết hạn')"></i></label>
                              </div>
                              <div class="form-check">
                                <input class="form-check-input border-success" type="radio" name="tts_select" id="tts_zalo" value="tts_zalo" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_zalo' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tts_zalo">TTS Zalo (Api Keys) <i class="bi bi-question-circle-fill" onclick="show_message('Cần sử dụng Api keys của zalo càng nhiều Keys càng tốt, Mỗi Keys một dòng<br/>Key Lỗi hoặc Hết giới hạn dùng miễn phí sẽ tự động chuyển vào file BackList, và sẽ tự động làm mới nội dung BackList vào hôm sau<br/>Trang Chủ: <a href=\'https://zalo.ai/account/manage-keys\' target=\'_bank\'>https://zalo.ai/account/manage-keys</a>')"></i></label>
                              </div>
                              <div class="form-check">
                                <input class="form-check-input border-success" type="radio" name="tts_select" id="tts_viettel" value="tts_viettel" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_viettel' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tts_viettel">TTS Viettel (Api Keys) <i class="bi bi-question-circle-fill" onclick="show_message('Cần sử dụng Api keys của Viettel càng nhiều Keys càng tốt, Mỗi Keys một dòng<br/>Key Lỗi hoặc Hết giới hạn dùng miễn phí sẽ tự động chuyển vào file BackList, và sẽ tự động làm mới nội dung BackList vào hôm sau<br/>Trang Chủ: <a href=\'https://viettelai.vn/dashboard/token\' target=\'_bank\'>https://viettelai.vn/dashboard/token</a>')"></i></label>
                              </div>
                              <div class="form-check" >
                                <input  class="form-check-input border-success" type="radio" name="tts_select" id="tts_edge" value="tts_edge" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_edge' ? 'checked' : ''; ?>>
                                <label  class="form-check-label" for="tts_edge">TTS Microsoft Edge (Free) <i class="bi bi-question-circle-fill" onclick="show_message('TTS Microsoft edge Free')"></i></label>
                              </div>
                              <div class="form-check" >
                                <input  class="form-check-input border-success" type="radio" name="tts_select" id="tts_dev_customize" value="tts_dev_customize" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_select'] === 'tts_dev_customize' ? 'checked' : ''; ?>>
                                <label  class="form-check-label" for="tts_dev_customize">TTS DEV Customize: Dev_TTS.py <font color=red>(Người Dùng Tự Code)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Người dùng sẽ tự code, chuyển văn bản thành giọng nói nếu chọn tts này, dữ liệu để các bạn code sẽ nằm trong tệp: <b>Dev_TTS.py</b><br/>- Cần thêm kích hoạt bên dưới để sử dụng vào chương trình')"></i></label>
                                <div class="form-switch">
                                  <label class="form-label" for="tts_dev_customize_active">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Nếu Dùng TTS DEV Custom bạn cần phải kích hoạt để được khởi tạo dữ liệu khi chạy chương trình')"></i></label>
                                  <input class="form-check-input border-success" type="checkbox" name="tts_dev_customize_active" id="tts_dev_customize_active" <?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_dev_customize']['active'] ? 'checked' : ''; ?>>
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
                              <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                                <center><font color=red>TTS Default</font></center>
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
                                <center><font color=red>TTS Google Cloud</font></center>
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
                                <label for="tts_ggcloud_json_file_token">Tệp tin json xác thực:</label>
                              </div>
                              <div class="form-floating mb-3">
                                <center><button type="button" class="btn btn-success" title="Tải xuống" onclick="downloadFile('<?php echo $VBot_Offline.$Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['authentication_json_file']; ?>')"><i class="bi bi-download"></i> Tải Xuống Tệp Json</button></center>
                              </div>
                            </div>
                            <!-- ẩn hiện cấu hình select_tts_zalo_html style="display: none;" -->
                            <div id="select_tts_zalo_html" class="col-12" style="display: none;">
                              <h4 class="card-title" title="Chuyển giọng nói thành văn bản">
                                <center><font color=red>TTS Zalo AI</font></center>
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
                                <center><font color=red>TTS Viettel AI</font></center>
                              </h4>
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
                                <center><font color=red>TTS Microsoft Edge Azure</font></center>
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
                                <center><font color=red>TTS Google Cloud KEY</font></center>
                              </h4>
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
              <div class="card accordion" id="accordion_button_setting_homeassistant">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_homeassistant" aria-expanded="false" aria-controls="collapse_button_setting_homeassistant">
                    Cấu Hình Kết Nối Tới Home Assistant (HASS):
                  </h5>
                  <div id="collapse_button_setting_homeassistant" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_setting_homeassistant">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng điều khiển nhà thông minh Home Assistant')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="hass_active" id="hass_active" <?php echo $Config['home_assistant']['active'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Lệnh tùy chỉnh <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng câu lệnh tùy chỉnh (Custom Command) cho điều khiển nhà thông minh Home Assistant<br/>- Thiết lập câu lệnh trong: <b>Thiết Lập Nâng Cao -> Home Assistant Customize Command</b>')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="hass_custom_commands_active" id="hass_custom_commands_active" <?php echo $Config['home_assistant']['custom_commands']['active'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="hass_long_token" class="col-sm-3 col-form-label" title="Mã token của nhà thông minh Home Assistant">Mã Token (Long Token):</label>
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
					
                    <div class="row mb-3">
                       <label class="col-sm-3 col-form-label" title="Liên Kết Loa VBot Với Home Assist (Hass)">Liên Kết Loa VBot Qua HACS Lên Home Assistant (Hass) <i class="bi bi-question-circle-fill" onclick="show_message('Liên Kết Loa VBot Lên Home Assist Bằng HACS Custom Component')"></i> : </label>
                      <div class="col-sm-9">
						<div class="input-group mb-3">
                          <input disabled="" class="form-control border-danger" type="text" placeholder="https://github.com/marion001/VBot-Assistant-MQTT-HASS.git" title="https://github.com/marion001/VBot-Assistant-MQTT-HASS.git" value="https://github.com/marion001/VBot-Assistant-MQTT-HASS.git">
                          <button class="btn btn-success border-danger" type="button"><a style="color: white;" href="https://github.com/marion001/VBot-Assistant-MQTT-HASS.git" target="_blank">Truy Cập</a></button>
                        </div>
                      </div>
                    </div>
					
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Tích hợp vào Assist (Tác Nhân):</label>
                      <div class="col-sm-9">
                        <div class="input-group mb-3">
                          <input disabled class="form-control border-danger" type="text" placeholder="https://github.com/marion001/VBot-Assist-Conversation" title="https://github.com/marion001/VBot-Assist-Conversation" value="https://github.com/marion001/VBot-Assist-Conversation">
                          <button class="btn btn-success border-danger" type="button"><a style="color: white;" href="https://github.com/marion001/VBot-Assist-Conversation" target="_blank">Truy Cập</a></button>
                        </div>
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
                    <div class="row mb-3">
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
                          <input class="form-check-input border-success" type="checkbox" name="mqtt_retain" id="mqtt_retain" <?php echo $Config['mqtt_broker']['mqtt_retain'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                       <label class="col-sm-3 col-form-label" title="Liên Kết Loa VBot Với Home Assist (Hass)">Liên Kết Loa VBot Qua HACS Lên Home Assistant (Hass) <i class="bi bi-question-circle-fill" onclick="show_message('Liên Kết Loa VBot Lên Home Assist Bằng HACS Custom Component')"></i> : </label>
                      <div class="col-sm-9">
						<div class="input-group mb-3">
                          <input disabled="" class="form-control border-danger" type="text" placeholder="https://github.com/marion001/VBot-Assistant-MQTT-HASS.git" title="https://github.com/marion001/VBot-Assistant-MQTT-HASS.git" value="https://github.com/marion001/VBot-Assistant-MQTT-HASS.git">
                          <button class="btn btn-success border-danger" type="button"><a style="color: white;" href="https://github.com/marion001/VBot-Assistant-MQTT-HASS.git" target="_blank">Truy Cập</a></button>
                        </div>
                      </div>
                    </div>
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
                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_setting_led">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_led" aria-expanded="false" aria-controls="collapse_button_setting_led">
                    Cấu Hình Sử Dụng Đèn Led:
                  </h5>
                  <div id="collapse_button_setting_led" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_led">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích hoạt đèn Led <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng đèn led trạng thái')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="led_active_on_off" id="led_active_on_off" <?php echo $Config['smart_config']['led']['active'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="led_type_select" class="col-sm-3 col-form-label">Kiểu loại Led <i class="bi bi-question-circle-fill" onclick="show_message('Nếu sử dụng LED dây APA102 thì cần hàn chân <b>SDI (MOSI) -> GPIO10</b> và chân <b>CKI (SCLK) -> GPIO11</b>')"></i>:</label>
                      <div class="col-sm-9">
                        <select name="led_type_select" id="led_type_select" class="form-select border-success" aria-label="Default select example">
                          <option value="ws281x" <?php echo $Config['smart_config']['led']['led_type'] === 'ws281x' ? 'selected' : ''; ?>>WS281x, SK6812, VBot AIO, Vietbot AIO</option>
                          <option value="apa102" <?php echo $Config['smart_config']['led']['led_type'] === 'apa102' ? 'selected' : ''; ?>>APA102</option>
                          <option value="ReSpeaker_Mic_Array_v2.0" <?php echo $Config['smart_config']['led']['led_type'] === 'ReSpeaker_Mic_Array_v2.0' ? 'selected' : ''; ?>>ReSpeaker Mic Array v2.0</option>
                          <option value="dev_custom_led" <?php echo $Config['smart_config']['led']['led_type'] === 'dev_custom_led' ? 'selected' : ''; ?>>DEV Custom Led: Dev_Led.py (Người dùng tự code)</option>
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
                      <label for="led_brightness" class="col-sm-3 col-form-label" title="Độ sáng của đèn Led">Độ sáng đèn LED: <i class="bi bi-question-circle-fill" onclick="show_message('Độ sáng của Led sẽ từ 0 -> 100%')"></i> :</label>
                      <div class="col-sm-9">
                        <input class="form-control border-success" step="0" min="1" max="100" type="number" name="led_brightness" id="led_brightness" value="<?php echo intval($Config['smart_config']['led']['brightness'] * 100 / 255); ?>">
                      </div>
                    </div>
					
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Ghi Nhớ Độ Sáng Khi Được Thay Đổi <i class="bi bi-question-circle-fill" onclick="show_message('Khi được Bật sẽ lưu lại giá trị độ sáng của đèn led khi được thay đổi trong lúc Chương Trình đang hoạt động vào Config.json')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="remember_last_brightness" id="remember_last_brightness" <?php echo $Config['smart_config']['led']['remember_last_brightness'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
					
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Đảo ngược đầu LED <i class="bi bi-question-circle-fill" onclick="show_message('Đảo ngược đầu (Bắt Đầu) sáng của đèn led')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="led_invert" id="led_invert" <?php echo $Config['smart_config']['led']['led_invert'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Đèn LED khi khởi động <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng đèn led báo trạng thái khi trương trình đang khởi chạy')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="led_starting_up" id="led_starting_up" <?php echo $Config['smart_config']['led']['led_starting_up'] ? 'checked' : ''; ?>>
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
			  


      <div class="card accordion" id="accordion_button_multype_button_config">
      <div class="card-body">
      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_multype_button_config" aria-expanded="false" aria-controls="collapse_button_multype_button_config">
      Cấu Hình Sử Dụng Nút Nhấn:</h5>
      <div id="collapse_button_multype_button_config" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_multype_button_config">
      
			  
			  
              <div class="card accordion" id="accordion_button_setting_bton">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting_bton" aria-expanded="false" aria-controls="collapse_button_setting_bton">
                    Cấu Hình Nút Nhấn Dạng Thường <font color=red> (Nhấn Nhả)</font>:
                  </h5>
                  <div id="collapse_button_setting_bton" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting_bton" style="">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng nút nhấn hoặc không sử dụng')"></i> :</label>
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
                            <center><font color=red>Cấu Hình Chung</font></center>
                          </th>
                          <th scope="col" colspan="2">
                            <center><font color=red>Nhấn Nhả</font></center>
                          </th>
                          <th scope="col" colspan="2">
                            <center><font color=red>Nhấn Giữ</font></center>
                          </th>
                        </tr>
                        <tr>
                          <th scope="col">
                            <center><font color=blue>Nút Nhấn</font></center>
                          </th>
                          <th scope="col">
                            <center><font color=blue>GPIO</font></center>
                          </th>
                          <th scope="col">
                            <center><font color=blue>Kéo mức thấp</font></center>
                          </th>
                          <th scope="col">
                            <center><font color=blue>Kích hoạt</font></center>
                          </th>
                          <th scope="col">
                            <center><font color=blue>Thời gian nhấn (ms)</font></center>
                          </th>
                          <th scope="col">
                            <center><font color=blue>Kích Hoạt</font></center>
                          </th>
                          <th scope="col">
                            <center><font color=blue>Thời Gian Giữ (s)</font></center>
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
                  </div>
                </div>
              </div>

      <div class="card accordion" id="accordion_button_Encoder_Rotary">
      <div class="card-body">
      <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_Encoder_Rotary" aria-expanded="false" aria-controls="collapse_button_Encoder_Rotary">
      Cấu Hình Nút Nhấn Dạng Xoay <font color=red> (Sử Dụng Encoder Rotary)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Sử Dụng Nút Nhấn Dạng Xoay Encoder, Tương Thích Với Các Module Encoder Có 5 Chân Trên Thị Trường Như:  <b>KY-040 RV09 EC11</b><br/>- Khuyến Nghị Chỉ Nên Kích Hoạt Sử Dụng 1 Trong 2 Kiểu Nút Nhấn Là: <b> Nút Nhấn Dạng Xoay Encoder</b> Hoặc <b>Nút Nhấn Nhả Dạng Thường</b>')"></i>:</h5>
      <div id="collapse_button_Encoder_Rotary" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_Encoder_Rotary">
                    <div class="row mb-3">
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
      <th scope="col" colspan="2" style="text-align: center; vertical-align: middle;"><font color="red">Cấu hình Encoder</font></th>
	  <th scope="col" colspan="4" style="text-align: center; vertical-align: middle;"><font color="red">Chức Năng Nút Nhấn SW, KEY</font></th>
	   </tr>

  </thead>
  <tbody>
    <tr>
      <th scope="row" style="text-align: center; vertical-align: middle;"><font color="blue">CLK, S1 = GPIO:</font></th>
		<td><input class="form-control border-success" step="1" min="1" max="35" type="number" name="encoder_rotary_gpio_clk" id="encoder_rotary_gpio_clk" value="<?php echo $Config['smart_config']['button_active']['encoder_rotary']['gpio_clk']; ?>"></td>
<th colspan="2" scope="col" style="text-align: center; vertical-align: middle;"><font color="red">Nhấn Nhả</font></th>
<th colspan="2" scope="col" style="text-align: center; vertical-align: middle;"><font color="red">Nhấn Giữ </font></th>
	</tr>
    <tr>
      <th scope="row" style="text-align: center; vertical-align: middle;"><font color="blue">DT, S2 = GPIO:</font></th>
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
      <th scope="row" style="text-align: center; vertical-align: middle;"><font color="blue">SW, KEY = GPIO:</font></th>
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
<th scope="row" style="text-align: center; vertical-align: middle;"><font color="blue">Bước Xoay, Step:</font></th>
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
<th colspan="1" style="text-align: center; vertical-align: middle;"><font color="blue">Hiển Thị Logs Khi Xoay:</font></th>
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
	  
			  
              <div class="card accordion" id="accordion_button_lcd_oled">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_lcd_oled" aria-expanded="false" aria-controls="collapse_button_lcd_oled">
                    Màn Hình LCD OLED  &nbsp; <i class="bi bi-question-circle-fill" onclick="show_message('Tài Liệu:<br/>- <a href=\'https://github.com/adafruit/Adafruit_Python_SSD1306\' target=\'_bank\'>https://github.com/adafruit/Adafruit_Python_SSD1306</a> <br/>- <a href=\'https://www.the-diy-life.com/add-an-oled-stats-display-to-raspberry-pi-os-bullseye/\' target=\'_bank\'>https://www.the-diy-life.com/add-an-oled-stats-display-to-raspberry-pi-os-bullseye/</a>')"></i>  &nbsp;:
                  </h5>
                  <div id="collapse_button_lcd_oled" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_lcd_oled" style="">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt hiển thị sử dụng màn hình LCD OLED')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="display_screen_active" id="display_screen_active" <?php echo $Config['display_screen']['active'] ? 'checked' : ''; ?> title="Nhấn để Bật hoặc Tắt sử dụng màn hình">
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Loại Màn Hình Kết Nối :</label>
                      <div class="col-sm-9">
                        <div class="input-group">
                          <select name="display_screen_connection_type" id="display_screen_connection_type" class="form-select border-success">
                            <option value="lcd_i2c" <?php echo $Config['display_screen']['connection_type'] === 'lcd_i2c' ? 'selected' : ''; ?>>Kết Nối I2C</option>
                            <option value="lcd_spi" <?php echo $Config['display_screen']['connection_type'] === 'lcd_spi' ? 'selected' : ''; ?> disabled>Kết Nối SPI (Chức Năng Đang Phát Triển)</option>
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
                                if ($handle_font = opendir($VBot_Offline.'resource/screen_disp/font')) {
                                    $font_file = [];
                                    while (false !== ($entry_font = readdir($handle_font))) {
                                        $file_parts = pathinfo($entry_font);
                                        $extension_font = isset($file_parts['extension']) ? strtolower($file_parts['extension']) : '';
                                        if (in_array($extension_font, ['ttf', 'otf'])) {
                                            $font_file[] = $entry_font;
                                        }
                                    }
                                    closedir($handle_font);
                                    echo '<select name="lcd_i2c_font_ttf" id="lcd_i2c_font_ttf" class="form-select border-success">';
                                    foreach ($font_file as $file_font) {
                                		 $selected_font = ($LCD_I2C_Font_TTF === 'resource/screen_disp/font/'.$file_font) ? ' selected' : ''; 
                                	echo '<option value="' . htmlspecialchars('resource/screen_disp/font/'.$file_font) . '"' . $selected_font . '>' . htmlspecialchars($file_font) . '</option>';
                                    }
                                    echo '</select>';
                                } else {
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
                        Chức Năng Đang Phát Triển
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
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Âm Thanh Khi Khởi Động <i class="bi bi-question-circle-fill" onclick="show_message('Âm thanh thông báo khi chương trình khởi chạy thành công')"></i> :</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt âm thanh thông báo khi chương trình khởi động')"></i> :</label>
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
                                if ($handle = opendir($VBot_Offline.'resource/sound/welcome')) {
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
                                		 $selected = ($Audio_welcome_file === 'resource/sound/welcome/'.$file) ? ' selected' : ''; 
                                	echo '<option value="' . htmlspecialchars('resource/sound/welcome/'.$file) . '"' . $selected . '>' . htmlspecialchars($file) . '</option>';
                                    }
                                    echo '</select>';
                                } else {
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

					<!--
                    <div class="card accordion" id="accordion_button_setting">
                      <div class="card-body">
                        <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_setting" aria-expanded="false" aria-controls="collapse_button_setting">
                          Âm Thanh Khác/Mặc Định:
                        </h5>
                        <div id="collapse_button_setting" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_setting" style="">
                          <?php
                           /* foreach ($Config['smart_config']['smart_wakeup']['sound']['default'] as $key => $value) {
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
							*/
                            ?>
                        </div>
                      </div>
                    </div>
					-->

                  </div>
                </div>
              </div>
              <div class="card accordion" id="accordion_button_media_player">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_media_player" aria-expanded="false" aria-controls="collapse_button_media_player">
                    Cấu Hình Phát Nhạc - Media Player:
                  </h5>
                  <div id="collapse_button_media_player" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordion_button_media_player" style="">
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Kích hoạt Media Player <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt Để kích hoạt sử dụng trình phát nhạc Media Player<br/>Khi được tắt sẽ không ra lệnh phát được Bài Hát, PodCast, Radio')"></i> :</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt: </label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="media_player_active" id="media_player_active" <?php echo $Config['media_player']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Cho Phép Đánh Thức Khi Đang Phát Media player: </label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="wake_up_in_media_player" id="wake_up_in_media_player" <?php echo $Config['media_player']['wake_up_in_media_player'] ? 'checked' : ''; ?>>
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
                              <input class="form-check-input border-success" type="checkbox" name="media_sync_ui" id="media_sync_ui" <?php echo $Config['media_player']['media_sync_ui']['active'] ? 'checked' : ''; ?>>
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
                    Nguồn Phát Media Player: Nhạc, Radio, Kể Truyện, PodCast, Đọc Báo Tin tức:
                  </h5>
                  <div id="collapse_button_media_player_source" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_media_player_source">
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Zing MP3:</h5>
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
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Youtube:</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng nguồn phát nhạc Youtube')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="youtube_active" id="youtube_active" <?php echo $Config['media_player']['youtube']['active'] ? 'checked' : ''; ?>>
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
                              <input class="form-check-input border-success" type="checkbox" name="music_local_active" id="music_local_active" <?php echo $Config['media_player']['music_local']['active'] ? 'checked' : ''; ?>>
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
                        <h5 class="card-title">Đọc Truyện, Kể Truyện, PodCast:</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng Đọc Truyện')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="podcast_active" id="podcast_active" <?php echo $Config['media_player']['podcast']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Cho phép dùng ưu tiên trợ lý ảo: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được Kích hoạt, sẽ sử dụng dữ liệu từ chế độ: Ưu tiên trợ lý ảo')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="podcast_virtual_assistants_active" id="podcast_virtual_assistants_active" <?php echo $Config['media_player']['podcast']['allows_priority_use_of_virtual_assistants'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
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
                              <input class="form-check-input border-success" type="checkbox" name="radio_active" id="radio_active" <?php echo $Config['media_player']['radio']['active'] ? 'checked' : ''; ?>>
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
                              <th scope="col">
                                <center><font color="red">Tên Đài</font></center>
                              </th>
                              <th scope="col">
                                <center><font color="red">Link Đài</font></center>
                              </th>
                              <th scope="col">
                                <center><font color="red">Hành Động</font></center>
                              </th>
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
                        <center> <button type="button" class="btn btn-success rounded-pill" id="add-radio" onclick="addRadio()">Thêm Đài Mới</button></center>
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
                          </i> 
                          :
                        </h5>
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
                        <table class="table table-bordered border-primary"  id="newspaper-table">
                          <thead>
                            <tr>
                              <th scope="col">
                                <center><font color="red">Tên Báo</font></center>
                              </th>
                              <th scope="col">
                                <center><font color="red">Link Báo</font></center>
                              </th>
                              <th scope="col">
                                <center><font color="red">Hành Động</font></center>
                              </th>
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
                        <center> <button type="button" class="btn btn-success rounded-pill" id="add-newspaper" onclick="addNewsPaper()">Thêm Báo Mới</button></center>
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
                      <div class="card-body">
                        <h5 class="card-title">Ưu tiên trợ lý ảo:</h5>
                        <?php
                          //Get Ưu tiên Nguồn Phát
                          $virtual_assistant_priority = $Config['virtual_assistant']['prioritize_virtual_assistants'];
						$assistant_options = [
							"default_assistant" => "Default Assistant",
							"olli" => "Olli AI Assistant",
							"google_gemini" => "Google Gemini",
							"chat_gpt" => "Chat GPT",
							"zalo_assistant" => "Zalo AI Assistant",
							"dify_ai" => "Dify AI Assistant",
							"customize_developer_assistant" => "DEV Custom Assistant: Dev_Assistant.py (Người Dùng Tự Code)"
						];
						for ($i = 0; $i < 7; $i++) {
							$label = "Top " . ($i + 1);
							$select_name = "virtual_assistant_priority" . ($i + 1);
							$selected_value = $virtual_assistant_priority[$i] ?? '';
							echo '<div class="row mb-3">';
							echo '  <label for="' . $select_name . '" class="col-sm-3 col-form-label">' . $label . ':</label>';
							echo '  <div class="col-sm-9">';
							echo '    <select class="form-select border-success" name="' . $select_name . '" id="' . $select_name . '">';
							echo '      <option value="">-- Chọn Trợ Lý --</option>';
							foreach ($assistant_options as $value => $label_option) {
								$selected = ($selected_value === $value) ? "selected" : "";
								echo '      <option value="' . $value . '" ' . $selected . '>' . $label_option . '</option>';
							}
							echo '    </select>';
							echo '  </div>';
							echo '</div>';
						}
						?>
                      </div>
                    </div>
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Trợ Lý Default Assistant <i class="bi bi-question-circle-fill" onclick="show_message('Trợ lý ảo mang tên Default Assistant')"></i> :</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Default Assistant<br/>- Phiên ID Chat của trợ lý này sẽ được tạo mới mỗi khi chương trình VBot được khởi động')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="default_assistant_active" id="default_assistant_active" <?php echo $Config['virtual_assistant']['default_assistant']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="default_assistant_time_out" class="col-sm-3 col-form-label">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa (Giây)')"></i> :</label>
                          <div class="col-sm-9">
                              <input  class="form-control border-success" type="number" min="5" step="1" max="90" name="default_assistant_time_out" id="default_assistant_time_out" placeholder="<?php echo $Config['virtual_assistant']['default_assistant']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['default_assistant']['time_out']; ?>">
                          </div>
                        </div>
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
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Trợ Lý Zalo AI Assistant:</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Zalo Assistant<br/>- Phiên ID Chat của trợ lý này sẽ được tạo mới mỗi khi chương trình VBot được khởi động')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="zalo_assistant_active" id="zalo_assistant_active" <?php echo $Config['virtual_assistant']['zalo_assistant']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="zalo_assistant_time_out" class="col-sm-3 col-form-label">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa (Giây)')"></i> :</label>
                          <div class="col-sm-9">
                              <input  class="form-control border-success" type="number" min="5" step="1" max="30" name="zalo_assistant_time_out" id="zalo_assistant_time_out" placeholder="<?php echo $Config['virtual_assistant']['zalo_assistant']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['zalo_assistant']['time_out']; ?>">
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="zalo_assistant_set_expiration_time" class="col-sm-3 col-form-label">Đặt thời gian hết hạn Token (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Đặt thời gian hết hạn cho token trung bình đặt 1 ngày tham số tính bằng giây: 86400')"></i> :</label>
                          <div class="col-sm-9">
                              <input  class="form-control border-success" type="number" min="21600" max="604800" step="1" name="zalo_assistant_set_expiration_time" id="zalo_assistant_set_expiration_time" placeholder="<?php echo $Config['virtual_assistant']['zalo_assistant']['set_expiration_time']; ?>" value="<?php echo $Config['virtual_assistant']['zalo_assistant']['set_expiration_time']; ?>">
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Trợ Lý Olli AI Assistant <i class="bi bi-question-circle-fill" onclick="show_message('Bạn cần đăng ký tài khoản Trên APP: Maika để sử dụng<br/>- Có thể dùng địa chỉ Email hoặc SĐT đã được đăng ký để điền vào ô bên dưới')"></i>:</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Olli AI Assistant')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="olli_assistant_active" id="olli_assistant_active" <?php echo $Config['virtual_assistant']['olli']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="olli_assistant_username" class="col-sm-3 col-form-label">Tài Khoản <i class="bi bi-question-circle-fill" onclick="show_message('Tài Khoản Đăng Nhập được tạo Trên APP Maika<br/>- Có thể dùng địa chỉ Email hoặc SĐT đã được đăng ký')"></i> :</label>
                          <div class="col-sm-9">
                              <input  class="form-control border-success" type="text" name="olli_assistant_username" id="olli_assistant_username" placeholder="Tài Khoản Đăng Nhập Của Bạn, Email hoặc Số Điện Thoại" value="<?php echo $Config['virtual_assistant']['olli']['username']; ?>">
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="olli_assistant_password" class="col-sm-3 col-form-label">Mật Khẩu <i class="bi bi-question-circle-fill" onclick="show_message('Mật Khẩu Đăng Nhập được tạo Trên APP Maika<br/>- Có thể dùng địa chỉ Email hoặc SĐT đã được đăng ký')"></i> :</label>
                          <div class="col-sm-9">
                              <input  class="form-control border-success" type="text" name="olli_assistant_password" id="olli_assistant_password" placeholder="Mật Khẩu Đăng Nhập Của Bạn" value="<?php echo $Config['virtual_assistant']['olli']['password']; ?>">
                          <br/><center><button class="btn btn-success border-success" type="button"  onclick="check_info_login_olli()">Kiểm Tra Kết Nối</button>
						  </center>
						  </div>

                        </div>
                        <div class="row mb-3">
                          <label for="olli_assistant_voice_name" class="col-sm-3 col-form-label">Giọng Đọc:</label>
							<div class="col-sm-9">
                            <select class="form-select border-success" name="olli_assistant_voice_name" id="olli_assistant_voice_name">
                              <option value="vn_north" <?php if ($Config['virtual_assistant']['olli']['voice_name'] === "vn_north") echo "selected"; ?>>Giọng Miền Bắc</option>
                              <option value="vn_south" <?php if ($Config['virtual_assistant']['olli']['voice_name'] === "vn_south") echo "selected"; ?>>Giọng Miền Nam</option>
                            </select>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="olli_assistant_time_out" class="col-sm-3 col-form-label">Thời gian chờ (giây) <i class="bi bi-question-circle-fill" onclick="show_message('Thời gian chờ phản hồi tối đa (Giây)')"></i> :</label>
                          <div class="col-sm-9">
                              <input  class="form-control border-success" type="number" min="5" step="1" max="30" name="olli_assistant_time_out" id="olli_assistant_time_out" placeholder="<?php echo $Config['virtual_assistant']['olli']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['olli']['time_out']; ?>">
                          </div>
                        </div>
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

                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Trợ Lý Google Gemini <i class="bi bi-question-circle-fill" onclick="show_message('Lấy Key/Api: <a href=\'https://aistudio.google.com/app/apikey\' target=\'_bank\'>https://aistudio.google.com/app/apikey</a> ')"></i> :</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Gemini')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="google_gemini_active" id="google_gemini_active" <?php echo $Config['virtual_assistant']['google_gemini']['active'] ? 'checked' : ''; ?>>
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
<?php
$gemini_model_list_json_file = $HTML_VBot_Offline.'/includes/other_data/gemini_model_list.json';
if (file_exists($gemini_model_list_json_file)) {
    $data = json_decode(file_get_contents($gemini_model_list_json_file), true);
    $selected_model = $Config['virtual_assistant']['google_gemini']['model_name'] ?? 'gemini-2.0-flash';
    $selected_version = $Config['virtual_assistant']['google_gemini']['api_version'] ?? 'v1beta';
    // Thẻ select model
    if (isset($data["gemini_models"]) && is_array($data["gemini_models"])) {
        echo '<div class="row mb-3"><label for="gemini_models_name" class="col-sm-3 col-form-label">Mô Hình Gemini:</label>';
        echo '<div class="col-sm-9"><select name="gemini_models_name" id="gemini_models_name" class="form-select border-danger" aria-label="Default select example">';
        foreach ($data["gemini_models"] as $model) {
            $selected = ($model == $selected_model) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($model) . '"' . $selected . '>' . htmlspecialchars($model) . '</option>';
        }
        echo '</select></div></div>';
    } else {
		echo '<div class="row mb-3"><label for="gemini_models_name" class="col-sm-3 col-form-label">Mô Hình Gemini:</label>';
        echo '<div class="col-sm-9">';
		echo '<input class="form-control border-danger" type="text" name="gemini_models_name" id="gemini_models_name" placeholder="'.$Config['virtual_assistant']['google_gemini']['model_name'].'" value="'.$Config['virtual_assistant']['google_gemini']['model_name'].'">';
		echo '</div></div>';
    }
    // Thẻ select gemini_api_version
    if (isset($data["gemini_api_version"]) && is_array($data["gemini_api_version"])) {
        echo '<div class="row mb-3"><label for="gemini_api_version" class="col-sm-3 col-form-label">Phiên Bản API:</label>';
        echo '<div class="col-sm-9"><select name="gemini_api_version" id="gemini_api_version" class="form-select border-danger" aria-label="Default select example">';
        foreach ($data["gemini_api_version"] as $version) {
            $selected = ($version == $selected_version) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($version) . '"' . $selected . '>' . htmlspecialchars($version) . '</option>';
        }
        echo '</select></div></div>';
    } else {
		echo '<div class="row mb-3"><label for="gemini_api_version" class="col-sm-3 col-form-label">Phiên Bản API:</label>';
        echo '<div class="col-sm-9">';
		echo '<input class="form-control border-danger" type="text" name="gemini_api_version" id="gemini_api_version" placeholder="'.$Config['virtual_assistant']['google_gemini']['api_version'].'" value="'.$Config['virtual_assistant']['google_gemini']['api_version'].'">';
		echo '</div></div>';
    }
} else {
		#echo '<script>error_notify("Tệp Json không tồn tại hoặc Tệp bị lỗi: '.$gemini_model_list_json_file.'");</script>';
		echo '<div class="row mb-3"><label for="gemini_models_name" class="col-sm-3 col-form-label">Mô Hình Gemini:</label>';
        echo '<div class="col-sm-9">';
		echo '<input class="form-control border-danger" type="text" name="gemini_models_name" id="gemini_models_name" placeholder="'.$Config['virtual_assistant']['google_gemini']['model_name'].'" value="'.$Config['virtual_assistant']['google_gemini']['model_name'].'">';
		echo '</div></div>';
		echo '<div class="row mb-3"><label for="gemini_api_version" class="col-sm-3 col-form-label">Mô Hình Gemini:</label>';
        echo '<div class="col-sm-9">';
		echo '<input class="form-control border-danger" type="text" name="gemini_api_version" id="gemini_api_version" placeholder="'.$Config['virtual_assistant']['google_gemini']['api_version'].'" value="'.$Config['virtual_assistant']['google_gemini']['api_version'].'">';
		echo '</div></div>';
}
?>
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
                        <h5 class="card-title">Trợ Lý Chat GPT:</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng trợ lý ảo Chat GPT')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="chat_gpt_active" id="chat_gpt_active" <?php echo $Config['virtual_assistant']['chat_gpt']['active'] ? 'checked' : ''; ?>>
                            </div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label for="chat_gpt_key" class="col-sm-3 col-form-label">Api Keys:</label>
                          <div class="col-sm-9">
                            <div class="input-group mb-3">
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
                        <h5 class="card-title">Trợ Lý Difi.ai | <a href="https://cloud.dify.ai" target="_blank">cloud.dify.ai</a>:</h5>
                        <div class="row mb-3">
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
                        <div class="row mb-3">
                          <label for="dify_ai_key" class="col-sm-3 col-form-label">Api Keys:</label>
                          <div class="col-sm-9">
                            <div class="input-group mb-3">
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
                              <input  class="form-control border-success" type="number" min="5" step="1" max="30" name="dify_ai_time_out" id="dify_ai_time_out" placeholder="<?php echo $Config['virtual_assistant']['dify_ai']['time_out']; ?>" value="<?php echo $Config['virtual_assistant']['dify_ai']['time_out']; ?>">
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">
                          Trợ Lý DEV Assistant: Dev_Assistant.py <font color=red>(Custom Assistant, Người dùng tự code)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để kích hoạt sử dụng Custom Assistant, Người dùng tự code trợ lý ảo, tùy biến hoặc sử dụng theo nhu cầu riêng ở tệp <b>Dev_Assistant.py</b>, nếu sử dụng hãy kích hoạt và chọn ưu tiên trợ lý ảo này')"></i>:
                        </h5>
                        <div class="row mb-3">
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
              <div class="card accordion" id="accordion_button_collapse_button_developer_customization">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" title="chế độ tùy chỉnh cho các lập trình viên, DEV" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_developer_customization" aria-expanded="false" aria-controls="collapse_button_developer_customization">
                    DEV Customization: Custom Skill, Dev_Customization.py <font color=red>(Người Dùng Tự Code)</font> <i class="bi bi-question-circle-fill" onclick="show_message('Cơ chế hoạt động:<br/>- Chế độ được kích hoạt, khi được đánh thức Wake UP, chương trình sẽ truyền dữ liệu văn bản được chuyển đổi từ Speak to Text vào File Dev_Customization.py để cho các bạn tự lập trình và xử lý dữ liệu, Khi kết thúc xử lý sẽ cần phải có return để trả về true hoặc false')"></i> :
                  </h5>
                  <div id="collapse_button_developer_customization" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_developer_customization">
                    <div class="row mb-3">
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
              <div class="card accordion" id="accordion_button_schedule_lich">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_schedule_lich" aria-expanded="false" aria-controls="collapse_button_schedule_lich">
                    Cài Đặt Lập Lịch, Lời Nhắc, Thông báo, V..v... (Schedule) <i class="bi bi-question-circle-fill" onclick="show_message('Bạn cần di chuyển tới: <b>Thiết Lập Nâng Cao -> Lên Lịch: Lời Nhắc, Thông Báo (Scheduler)</b> để tiến hành thiết lập thông báo')"></i>:
                  </h5>
                  <div id="collapse_button_schedule_lich" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_schedule_lich">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để khởi động Phát lời nhắc, Thông báo khi VBot được khởi chạy')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="schedule_active" id="schedule_active" <?php echo $Config['schedule']['active'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="schedule_data_json_file" class="col-sm-3 col-form-label">Tệp Lưu Trữ Dữ Liệu Cấu Hình:</label>
                      <div class="col-sm-9">
                        <input readonly class="form-control border-danger" type="text" name="schedule_data_json_file" id="schedule_data_json_file" placeholder="<?php echo $Config['schedule']['data_json_file']; ?>" value="<?php echo $Config['schedule']['data_json_file']; ?>">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="schedule_audio_path" class="col-sm-3 col-form-label">Thư Mục Chứa Tệp Âm Thanh:</label>
                      <div class="col-sm-9">
                        <input readonly class="form-control border-danger" type="text" name="schedule_audio_path" id="schedule_audio_path" placeholder="<?php echo $Config['schedule']['audio_path']; ?>" value="<?php echo $Config['schedule']['audio_path']; ?>">
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
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Tự Động Kiểm Tra Bản Cập Nhật <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật sẽ tự động kiểm tra cập nhật mới khi truy cập vào giao diện web ui')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="automatically_check_for_updates" id="automatically_check_for_updates" <?php echo $Config['backup_upgrade']['advanced_settings']['automatically_check_for_updates'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Khởi Động Lại VBot: <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, Chương trình sẽ khởi động lại VBot khi quá trình cập nhật Nâng Cấp VBot thành công')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="restart_vbot_upgrade" id="restart_vbot_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['restart_vbot'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Thông Báo Âm Thanh: <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, sẽ thông báo bằng âm thanh khi quá trình Cập Nhật, Nâng Cấp Giao Diện Web hoặc chương trình VBot thành công')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="sound_notification_backup_upgrade" id="sound_notification_backup_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['sound_notification'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Tải Lại Giao Diện Web: <i class="bi bi-question-circle-fill" onclick="show_message('Nếu được bật, sẽ tải lại giao diện Web khi Nâng Cấp, Cập Nhật web ui thành công')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input border-success" type="checkbox" name="refresh_page_ui_backup_upgrade" id="refresh_page_ui_backup_upgrade" <?php echo $Config['backup_upgrade']['advanced_settings']['refresh_page_ui'] ? 'checked' : ''; ?>>
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
                              <input class="form-check-input border-success" type="checkbox" name="backup_config_json_active" id="backup_config_json_active" <?php echo $Config['backup_upgrade']['config_json']['active'] ? 'checked' : ''; ?>>
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
                              <input class="form-check-input border-success" type="checkbox" name="custom_home_assistant_active" id="custom_home_assistant_active" <?php echo $Config['backup_upgrade']['custom_home_assistant']['active'] ? 'checked' : ''; ?>>
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
                              <input class="form-check-input border-success" type="checkbox" name="backup_scheduler_active" id="backup_scheduler_active" <?php echo $Config['backup_upgrade']['scheduler']['active'] ? 'checked' : ''; ?>>
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
                              Sao Lưu Chương Trình VBot:
                            </h5>
                            <div id="collapse_button_cau_hinh_sao_luu_vbot" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cau_hinh_sao_luu_vbot">
                              <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Đồng bộ lên Google Drive: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu sẽ được tải lên Google Drive')"></i> :</label>
                                <div class="col-sm-9">
                                  <div class="form-switch">
                                    <input class="form-check-input border-success" type="checkbox" name="backup_vbot_google_drive" id="backup_vbot_google_drive" <?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_to_cloud']['google_drive'] ? 'checked' : ''; ?>>
                                  </div>
                                </div>
                              </div>
                              <div class="row mb-3">
                                <label for="backup_upgrade_vbot_backup_path" class="col-sm-3 col-form-label">Đường dẫn tệp sao lưu:</label>
                                <div class="col-sm-9">
                                    <input readonly class="form-control border-danger" type="text" name="backup_upgrade_vbot_backup_path" id="backup_upgrade_vbot_backup_path" placeholder="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_path']; ?>" value="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['backup_path']; ?>">
                                </div>
                              </div>
                              <div class="row mb-3">
                                <label for="backup_upgrade_vbot_limit_backup_files" class="col-sm-3 col-form-label">Tệp sao lưu tối đa <i class="bi bi-question-circle-fill" onclick="show_message('Tối đa số lượng tệp tin sao lưu trên hệ thống')"></i> :</label>
                                <div class="col-sm-9">
                                    <input  class="form-control border-success" type="number" min="2" step="1" max="10" name="backup_upgrade_vbot_limit_backup_files" id="backup_upgrade_vbot_limit_backup_files" placeholder="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files']; ?>" value="<?php echo $Config['backup_upgrade']['vbot_program']['backup']['limit_backup_files']; ?>">
                                </div>
                              </div>
                              <div class="row mb-3">
                                <label for="backup_upgrade_vbot_exclude_files_folder" class="col-sm-3 col-form-label">Bỏ qua file, thư mục không sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi thư mục hoặc file sẽ là 1 dòng, nếu là file sẽ cần có đầy đủ đuôi mở rộng của file, ví dụ: <b>123.mp3</b>')"></i> :</label>
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
                                <label for="backup_upgrade_vbot_exclude_file_format" class="col-sm-3 col-form-label">Bỏ qua định dạng tệp không sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi định dạng tệp là 1 dòng, cần có dấu <b>.</b> ở trước định dạng tệp ví dụ: <b>.mp3</b> hoặc <b>.mp4</b>')"></i> :</label>
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
                                <br/><br/>
                                <div class="limited-height" id="show_all_file_folder_Backup_Program"></div>
                              </center>
                            </div>
                          </div>
                        </div>
                        <div class="card accordion" id="accordion_button_cau_hinh_Cap_nhat_Vbot">
                          <div class="card-body">
                            <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cau_hinh_Cap_nhat_Vbot" aria-expanded="false" aria-controls="collapse_button_cau_hinh_Cap_nhat_Vbot">
                              Cập Nhật Chương Trình VBot:
                            </h5>
                            <div id="collapse_button_cau_hinh_Cap_nhat_Vbot" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cau_hinh_Cap_nhat_Vbot">
                              <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Tạo Bản Sao Lưu Trước Khi Cập Nhật: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu được tạo ra trước khi cập nhật sẽ được tải lên Google Drive')"></i> :</label>
                                <div class="col-sm-9">
                                  <div class="form-switch">
                                    <input class="form-check-input border-success" type="checkbox" name="make_a_backup_before_updating_vbot" id="make_a_backup_before_updating_vbot" <?php echo $Config['backup_upgrade']['vbot_program']['upgrade']['backup_before_updating'] ? 'checked' : ''; ?>>
                                  </div>
                                </div>
                              </div>
                              <div class="row mb-3">
                                <label for="vbot_program_upgrade_keep_the_file_folder" class="col-sm-3 col-form-label">Giữ lại Tệp, Thư Mục Không Cập Nhật <i class="bi bi-question-circle-fill" onclick="show_message('Giữ lại tệp hoặc thư mục không cho phép cập nhật, mỗi tệp hoặc thư mục là 1 dòng, nếu là tệp tin thì cần có đầy đủ tên và đuôi của tệp, ví dụ giữ lại tệp: <b>Config.json</b>, giữ lại thư mục: <b>eng</b>')"></i> :</label>
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
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Giao diện Web <i class="bi bi-question-circle-fill" onclick="show_message('Cấu hình Cài Đặt khi Sao Lưu và Cập Nhật Giao diện Web')"></i> :</h5>
                        <div class="card accordion" id="accordion_button_sao_luu_giao_dien">
                          <div class="card-body">
                            <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_sao_luu_giao_dien" aria-expanded="false" aria-controls="collapse_button_sao_luu_giao_dien">
                              Sao Lưu Giao Diện WebUI VBot:
                            </h5>
                            <div id="collapse_button_sao_luu_giao_dien" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_sao_luu_giao_dien">
                              <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Đồng bộ lên Google Drive: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu sẽ được tải lên Google Drive')"></i> :</label>
                                <div class="col-sm-9">
                                  <div class="form-switch">
                                    <input class="form-check-input border-success" type="checkbox" name="backup_web_interface_google_drive" id="backup_web_interface_google_drive" <?php echo $Config['backup_upgrade']['web_interface']['backup']['backup_to_cloud']['google_drive'] ? 'checked' : ''; ?>>
                                  </div>
                                </div>
                              </div>
                              <div class="row mb-3">
                                <label for="backup_web_interface_backup_path" class="col-sm-3 col-form-label">Đường dẫn tệp sao lưu:</label>
                                <div class="col-sm-9">
                                    <input readonly class="form-control border-danger" type="text" name="backup_web_interface_backup_path" id="backup_web_interface_backup_path" placeholder="<?php echo $Config['backup_upgrade']['web_interface']['backup']['backup_path']; ?>" value="<?php echo $Config['backup_upgrade']['web_interface']['backup']['backup_path']; ?>">
                                </div>
                              </div>
                              <div class="row mb-3">
                                <label for="backup_web_interface_limit_backup_files" class="col-sm-3 col-form-label">Tệp sao lưu tối đa <i class="bi bi-question-circle-fill" onclick="show_message('Tối đa số lượng tệp tin sao lưu trên hệ thống')"></i> :</label>
                                <div class="col-sm-9">
                                    <input  class="form-control border-success" type="number" min="2" step="1" max="10" name="backup_web_interface_limit_backup_files" id="backup_web_interface_limit_backup_files" placeholder="<?php echo $Config['backup_upgrade']['web_interface']['backup']['limit_backup_files']; ?>" value="<?php echo $Config['backup_upgrade']['web_interface']['backup']['limit_backup_files']; ?>">
                                </div>
                              </div>
                              <div class="row mb-3">
                                <label for="backup_upgrade_web_interface_exclude_files_folder" class="col-sm-3 col-form-label">Bỏ qua file, thư mục không sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi thư mục hoặc file sẽ là 1 dòng, nếu là file sẽ cần có đầy đủ đuôi mở rộng của file, ví dụ: <b>123.mp3</b>')"></i> :</label>
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
                                <label for="backup_upgrade_web_interface_exclude_file_format" class="col-sm-3 col-form-label">Bỏ qua định dạng tệp không sao lưu <i class="bi bi-question-circle-fill" onclick="show_message('Mỗi định dạng tệp là 1 dòng, cần có dấu <b>.</b> ở trước định dạng tệp ví dụ: <b>.mp3</b> hoặc <b>.mp4</b>')"></i> :</label>
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
                        <div class="card accordion" id="accordion_button_cap_nhat_giao_dien">
                          <div class="card-body">
                            <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_cap_nhat_giao_dien" aria-expanded="false" aria-controls="collapse_button_cap_nhat_giao_dien">
                              Cập Nhật Giao Diện WebUI VBot:
                            </h5>
                            <div id="collapse_button_cap_nhat_giao_dien" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_cap_nhat_giao_dien">
                              <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Tạo Bản Sao Lưu Trước Khi Cập Nhật: <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật, Bản sao lưu được tạo ra trước khi cập nhật sẽ được tải lên Google Drive')"></i> :</label>
                                <div class="col-sm-9">
                                  <div class="form-switch">
                                    <input class="form-check-input border-success" type="checkbox" name="make_a_backup_before_updating_interface" id="make_a_backup_before_updating_interface" <?php echo $Config['backup_upgrade']['web_interface']['upgrade']['backup_before_updating'] ? 'checked' : ''; ?>>
                                  </div>
                                </div>
                              </div>
                              <div class="row mb-3">
                                <label for="vbot_web_interface_upgrade_keep_the_file_folder" class="col-sm-3 col-form-label">Giữ lại Tệp, Thư Mục Không Cập Nhật <i class="bi bi-question-circle-fill" onclick="show_message('Giữ lại tệp hoặc thư mục không cho phép cập nhật, mỗi tệp hoặc thư mục là 1 dòng, nếu là tệp tin thì cần có đầy đủ tên và đuôi của tệp, ví dụ giữ lại tệp: <b>Config.json</b>, giữ lại thư mục: <b>eng</b>')"></i> :</label>
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
              <div class="card accordion" id="accordion_button_Cloud_backup">
                <div class="card-body">
                  <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_Cloud_Backup" aria-expanded="false" aria-controls="collapse_button_Cloud_Backup">
                    Cấu Hình Tải Lên Bản Sao Lưu Dữ Liệu - Cloud Backup&nbsp;<i class="bi bi-cloud-check"></i>&nbsp;:
                  </h5>
                  <div id="collapse_button_Cloud_Backup" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_Cloud_Backup">
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Google Cloud Drive <i class="bi bi-question-circle-fill" onclick="show_message('Cấu hình thiết lập đồng bộ dữ liệu lên Google Cloud Drive<br/>- Nếu có nhiều thiết bị cần đồng bộ lên Google Cloud Drive thì cần thay đổi tên của 3 thư mục để tránh bị trùng lặp với dữ liệu của thiết bị khác<br/><a href=\'https://docs.google.com/document/d/1-VTi9MOAgQoR8jZrhN9FlZxjWsq2vDuy/edit?usp=drive_link&ouid=106149318613102395200&rtpof=true&sd=true\' target=\'_bank\'><b>Hướng dẫn tạo file json</b></a>')"></i> | <a href="GCloud_Drive.php" title="Truy Cập"> <i class="bi bi-box-arrow-up-right"></i> Truy Cập</a> :</h5>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Kích hoạt: <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chức năng sao lưu dữ liệu lê Google Cloud Drive')"></i> :</label>
                          <div class="col-sm-9">
                            <div class="form-switch">
                              <input class="form-check-input border-success" type="checkbox" name="google_cloud_drive_active" id="google_cloud_drive_active" <?php echo $Config['backup_upgrade']['google_cloud_drive']['active'] ? 'checked' : ''; ?>>
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
                          <label class="col-sm-3 col-form-label">Tên Thư Mục Sao Lưu Chương Trình VBot <i class="bi bi-question-circle-fill" onclick="show_message('Tên Thư Mục Sao Lưu Chương Trình VBot Trên Google Cloud Drive (Thư Mục Con), Nếu thư mục không tồn tại sẽ tự động được tạo mới')"></i> : </label>
                          <div class="col-sm-9">
                            <input required class="form-control border-success" type="text" name="gcloud_drive_backup_folder_vbot_name" id="gcloud_drive_backup_folder_vbot_name" value="<?php echo $Config['backup_upgrade']['google_cloud_drive']['backup_folder_vbot_name']; ?>">
                            <div class="invalid-feedback">Cần nhập Tên Thư Mục Sao Lưu trên Google Drive</div>
                          </div>
                        </div>
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Tên Thư Mục Sao Lưu Giao Diện VBot <i class="bi bi-question-circle-fill" onclick="show_message('Tên Thư Mục Sao Lưu Giao Diện VBot Trên Google Cloud Drive (Thư Mục Con), Nếu thư mục không tồn tại sẽ tự động được tạo mới')"></i> : </label>
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
                    Bluetooth <i class="bi bi-bluetooth"></i> <font color=red>(Chức năng chưa được phát triển)</font>:
                  </h5>
                  <div id="collapse_button_bluetooth_uart_vbot" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_bluetooth_uart_vbot">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Kích hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để Khởi Tạo Bluetooth')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input readonly class="form-check-input border-danger" type="checkbox" name="bluetooth_active" id="bluetooth_active" <?php echo $Config['bluetooth']['active'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Hiển thị Logs Bluetooth <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để hiển thị logs Bluetooth')"></i> :</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input readonly class="form-check-input border-danger" type="checkbox" name="bluetooth_show_logs" id="bluetooth_show_logs" <?php echo $Config['bluetooth']['show_logs'] ? 'checked' : ''; ?>>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="bluetooth_gpio_power" class="col-sm-3 col-form-label">GPIO Power:</label>
                      <div class="col-sm-9">
                          <input readonly class="form-control border-danger" type="number" step="1" min="1" max="30" name="bluetooth_gpio_power" id="bluetooth_gpio_power" placeholder="<?php echo $Config['bluetooth']['gpio_power']; ?>" value="<?php echo $Config['bluetooth']['gpio_power']; ?>">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="bluetooth_baud_rate" class="col-sm-3 col-form-label">Baud Rate:</label>
                      <div class="col-sm-9">
                          <input readonly class="form-control border-danger" type="number" name="bluetooth_baud_rate" id="bluetooth_baud_rate" placeholder="<?php echo $Config['bluetooth']['baud_rate']; ?>" value="<?php echo $Config['bluetooth']['baud_rate']; ?>">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="bluetooth_serial_port" class="col-sm-3 col-form-label">Serial Port:</label>
                      <div class="col-sm-9">
                          <input readonly class="form-control border-danger" type="text"  name="bluetooth_serial_port" id="bluetooth_serial_port" placeholder="<?php echo $Config['bluetooth']['serial_port']; ?>" value="<?php echo $Config['bluetooth']['serial_port']; ?>">
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

                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích hoạt chế độ câu phản hồi <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chế độ câu phản hồi')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="wakeup_reply_active" id="wakeup_reply_active" <?php echo $Config['smart_config']['smart_wakeup']['wakeup_reply']['active'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
				  <center>
<button type="button" class="btn btn-primary rounded-pill" onclick="loadWakeupReply()" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Hiển Thị Danh Sách Câu Phản Hồi">Hiển Thị Danh Sách Câu Phản Hồi</button>
<button type="button" class="btn btn-success rounded-pill" onclick="show_create_audio_WakeUP_Reply()" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tạo File Âm Thanh Câu Phản Hồi">Tạo File Âm Thanh</button>
<button type="button" class="btn btn-warning rounded-pill" onclick="reload_hotword_config('wakeup_reply')" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Tự động tìm scan các file âm thanh .mp3 trong thư mục wakeup_reply để cấu hình trong Config.json">Scan Và Ghi Mới</button>
</center><br/><table class="table table-bordered border-primary">
<tbody>
<tr>
<td style="text-align: center; vertical-align: middle;">
<label for="upload_files_wakeup_reply"><font color="blue">Tải lên file âm thanh Câu Phản Hồi .mp3</font></label>
<div class="input-group">
  <input class="form-control border-success" type="file" name="upload_files_wakeup_reply[]" id="upload_files_wakeup_reply" accept=".mp3" multiple>
  <button class="btn btn-primary border-success" type="button" onclick="uploadFilesWakeUP_Reply()">Tải Lên</button>
</div>
</td></tr></tbody></table>

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
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Chế Độ Hội Thoại/Trò Chuyện Liên Tục <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật Bạn chỉ cần gọi Bot 1 lần, sau khi bot trả lời xong sẽ tự động lắng nghe tiếp và lặp lại (cho tới khi Bạn không còn yêu cầu nào nữa)')"></i> :</h5>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Kích hoạt chế độ hội thoại <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt chế độ hội thoại')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="conversation_mode" id="conversation_mode" <?php echo $Config['smart_config']['smart_wakeup']['conversation_mode'] ? 'checked' : ''; ?>>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Chế Độ Xử Lý Đa Lệnh Trong 1 Câu Lệnh <i class="bi bi-question-circle-fill" onclick="show_message('Khi được Bật, sẽ kích hoạt chế độ xử lý nhiều hành động trong 1 câu lệnh, Ví dụ câu lệnh: <br/>- Bật đèn ngủ và tắt đèn phòng khách<br/> - Bật đèn phòng ngủ sau đó phát danh sách nhạc<br/> Từ khóa phân tách nhiều lệnh trong 1 câu: <b>và, sau đó, rồi</b> trong file: <b>Adverbs.json</b>')"></i> :</h5>
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

              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Đọc thông tin khi khởi động:</h5>
                  <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Bật, Tắt đọc thông tin <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt đọc thông tin khi Chương trình khởi động như: Địa chỉ ip của thiết bị, v..v...')"></i> :</label>
                    <div class="col-sm-9">
                      <div class="form-switch">
                        <input class="form-check-input border-success" type="checkbox" name="read_information_startup" id="read_information_startup" <?php echo $Config['smart_config']['read_information_startup']['active'] ? 'checked' : ''; ?>>
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
                        <input class="form-check-input border-success" type="checkbox" name="auto_restart_program_error" id="auto_restart_program_error" <?php echo $Config['smart_config']['auto_restart_program_error'] ? 'checked' : ''; ?>>
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
                        <input class="form-check-input border-success" type="checkbox" name="log_active" id="log_active" <?php echo $Config['smart_config']['show_log']['active'] ? 'checked' : ''; ?>>
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
    <script src="assets/vendor/prism/prism.min.js"></script>
    <script src="assets/vendor/prism/prism-json.min.js"></script>
    <script src="assets/vendor/prism/prism-yaml.min.js"></script>
    <?php
      include 'html_js.php';
      ?>
	<script src="assets/js/Config.js"></script>
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
                  // Kích hoạt Prism.js để lên màu
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
      			document.getElementById('name_file_showzz').textContent = "Tên File: "+filePath.split('/').pop();
                  $('#myModal_Config').modal('show');
              }
          } else {
              read_loadFile(filePath);
      		document.getElementById('name_file_showzz').textContent = "Tên File: "+filePath.split('/').pop();
              $('#myModal_Config').modal('show');
          }
      }

      //Hiển thị list hotword khi được scan
      function loadConfigHotword(lang) {
          const xhr = new XMLHttpRequest();
          xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?hotword&lang=' + lang, true);
          xhr.onload = function() {
              if (xhr.status === 200) {
                  const data = JSON.parse(xhr.responseText);
      			if (lang === "vi" || lang === "eng") {
      				displayResults_Hotword_dataa(data);
      			}else if (lang === "snowboy") {
      				displayResults_Hotword_Snowboy(data);
      			}
              }
          };
          xhr.send();
      }

// Hiển thị list danh sách câu phản hồi
function loadWakeupReply() {
    const xhr = new XMLHttpRequest();
	const pathVBot_PY = '<?php echo $VBot_Offline; ?>';
    xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?get_wakeup_reply', true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    //showMessagePHP('Lấy Dữ Liệu Câu Phản Hồi Thành Công');
                    const data = response.config;
                    const container = document.getElementById('displayResults_wakeup_reply');
					if (!data || !Array.isArray(data) || data.length === 0) {
						container.innerHTML = '<div class="alert alert-warning text-center mt-3 text-danger">Dữ liệu Rỗng, Không có dữ liệu câu phản hồi nào</div>';
						return;
					}
                    let html = 
                        '<br/><table class="table table-bordered border-primary">' +
                            '<thead>' +
                                '<tr>' +
                                    '<th class="text-danger" style="text-align: center;" colspan="4">Cài Đặt Câu Phản Hồi</th>' +
                                '</tr>' +
                                '<tr>' +
                                    '<th style="text-align: center;">STT</th>' +
                                    '<th style="text-align: center;">Kích Hoạt</th>' +
                                    '<th style="text-align: center;">Đường Dẫn File</th>' +
                                    '<th style="text-align: center;">Hành Động</th>' +
                                '</tr>' +
                            '</thead>' +
                            '<tbody>';
                    data.forEach((item, index) => {
                        html += 
                            '<tr>' +
                                '<td style="text-align: center;">' + (index + 1) + '</td>' +
                                '<td style="text-align: center;"><div class="form-switch">' +
                                    '<input class="form-check-input border-success" type="checkbox" name="save_wakeup_reply_active_' + index + '" ' + (item.active ? 'checked' : '') + '>' +
                                '</div></td>' +
                                '<td>' +
                                    '<input readonly class="form-control border-danger" type="text" name="save_wakeup_reply_file_name_' + index + '" value="' + item.file_name + '">' +
                                '</td>' +
                                '<td style="text-align: center;">' +
								'<button type="button" title="Nghe thử: ' + item.file_name + '" class="btn btn-primary" onclick="playAudio(\''+ pathVBot_PY + item.file_name + '\')"><i class="bi bi-play-circle"></i></button> ' +
                                ' <button type="button" class="btn btn-success" onclick="downloadFile(\''+ pathVBot_PY + item.file_name + '\')" title="Tải Xuống File: ' + item.file_name + '"><i class="bi bi-download"></i></button> ' +
                                ' <button type="button" class="btn btn-danger" title="Xóa file: ' + item.file_name + '" onclick="deleteFile(\''+ pathVBot_PY + item.file_name + '\', \'wakeup_reply\')"><i class="bi bi-trash"></i></button>' +
								'</td>' +
                            '</tr>';
                    });
                    html += 
                        '<tr><td colspan="4"><center><button class="btn btn-success rounded-pill" type="submit" name="save_config_wakeup_reply" title="Lưu cài đặt câu phản hồi">Lưu Cài Đặt Câu Phản Hồi</button></center></td></tr>' +
                        '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    show_message('Không thành công: ' + response.message);
                }
            } catch (err) {
                show_message('Lỗi khi phân tích JSON: ' + err);
            }
        } else {
            show_message('Lỗi HTTP: ' + xhr.status);
        }
    };
    xhr.onerror = function () {
        show_message('Lỗi khi gửi yêu cầu.');
    };
    xhr.send();
}

//Tải Lên File âm thanh wakeup_reply
function uploadFilesWakeUP_Reply() {
    const input = document.getElementById('upload_files_wakeup_reply');
    const files = input.files;
    if (files.length === 0) {
        show_message('Vui lòng chọn ít nhất một file âm thanh .mp3 để tải lên.');
        return;
    }
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('upload_files_wakeup_reply[]', files[i]);
    }
    formData.append('wakeup_reply_upload', 'upload_files_wakeup_reply');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/Hotword_pv_ppn.php', true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                const res = JSON.parse(xhr.responseText);
                let messageHtml = "";
                if (Array.isArray(res.messages)) {
                    messageHtml = res.messages.join("\n");
                } else if (res.message) {
                    messageHtml = res.message;
                }
                show_message(messageHtml);
				input.value = '';
				loadWakeupReply();
            } catch (e) {
                show_message('Lỗi khi phân tích phản hồi từ máy chủ.');
            }
        } else {
            show_message('Lỗi khi gửi yêu cầu tải lên.');
        }
    };
    xhr.send(formData);
}

      //Tải lên file ppv và pv dùng cho Picovoice/Procupine
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

      //Tải lên file hotword Snowboy
      function uploadFilesHotwordSnowboy() {
          const formData = new FormData();
          const files = document.getElementById('upload_files_hotword_snowboy').files;
          const lang = 'snowboy'
          if (files.length === 0) {
              show_message('Vui lòng chọn ít nhất một file để tải lên.');
              return;
          }
          for (let i = 0; i < files.length; i++) {
              formData.append('upload_files_hotword_snowboy[]', files[i]);
      		//console.log(files);
          }
          formData.append('action_hotword_snowboy', 'upload_files_hotword_snowboy');
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
          if (!confirm("Bạn có chắc chắn muốn cập nhật mới dữ liệu")) {
              return;
          }
          var xhr = new XMLHttpRequest();
      	if (langg === "No") {
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
      				if (element_data_lang_shows) {
      					var value_lang = element_data_lang_shows.getAttribute('value');
      					if (value_lang === "vi") {
      						loadConfigHotword("vi");
      					} else if (value_lang === "eng") {
      						loadConfigHotword("eng");
      					}
      				}
      			} else {
      				show_message("<center>Có lỗi xảy ra khi ghi mới dữ liệu Hotword tiếng anh và tiếng việt</center>");
      			}
      		};
      	}
      	else if (langg === 'snowboy'){
      		xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?reload_hotword_config_snowboy');
      		xhr.onload = function() {
      			if (xhr.status === 200) {
      				var response = JSON.parse(xhr.responseText);
      				if (response.status === 'success') {
      					show_message("<center>" + response.message + "</center>");
      				} else {
      					show_message("<center>" + response.message + "</center>");
      				}
      				loadConfigHotword("snowboy");
      			} else {
      				show_message("<center>Có lỗi xảy ra khi ghi mới dữ liệu Hotword tiếng anh và tiếng việt</center>");
      			}
      		};
      	}
      	else if (langg === 'wakeup_reply'){
      		xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?reload_wakeup_reply');
      		xhr.onload = function() {
      			if (xhr.status === 200) {
      				var response = JSON.parse(xhr.responseText);
      				if (response.status === 'success') {
      					show_message("<center>" + response.message + "</center>");
      				} else {
      					show_message("<center>" + response.message + "</center>");
      				}
      				loadWakeupReply();
      			} else {
      				show_message("<center>Có lỗi xảy ra khi ghi mới dữ liệu Câu Phản Hồi Wakeup Reply</center>");
      			}
      		};
      	}
          xhr.send();
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
                              '<br/><b>- URL nội bộ:</b> <a href="' + response.response.internal_url + '" target="_blank">' + response.response.internal_url + '</a><br/><b>- URL bên ngoài:</b> <a href="' + response.response.external_url + '" target="_blank">' + response.response.external_url + '</a>');
                      } else {
                          show_message('<center><font color=red><b>Thất bại</b></font></center><br/>' + response.message)
                      }
                  } else {
					  show_message('<center><font color=red><b>Thất bại</b></font></center><br/>' + xhr.statusText)
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

      //Dành cho Test Led 
      function test_led(action) {
          if ( <?php echo $Config['smart_config']['led']['active'] ? 'true' : 'false'; ?> === false) {
              show_message("Chế độ sử dụng Led không được kích hoạt");
              return;
          }
          const led_value = document.getElementById(action).value;
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
                              for (var i = 0; i < pathParts.length; i++) {
                                  var part = pathParts[i].trim();
                                  currentData = currentData[part];
                              }
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
          const inputField = document.getElementById('webui_path');
          if (inputField) {
      		path_web_ui_html = "<?php echo $directory_path; ?>";
              inputField.value = path_web_ui_html
      		showMessagePHP("Đã cập nhật đường dẫn path Web UI: "+path_web_ui_html)
          } else {
      		show_message('Không tìm thấy input có id "webui_path".');
          }
      }

//Lựa chọn file âm thanh dùng cho wakeup reply
function use_this_wakeup_reply_sound(filePath) {
	if (!filePath) {
        show_message('Không có dữ liệu file âm thanh');
        return;
    }
	loading('show');
  const encodedFilePath = encodeURIComponent(filePath);
  const url = 'includes/php_ajax/Hotword_pv_ppn.php?use_this_wakeup_reply_sound&file_path=' + encodedFilePath;
  const xhr = new XMLHttpRequest();
  xhr.open("GET", url, true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response.success) {
			  loadWakeupReply();
            showMessagePHP(response.message || "Đã sử dụng file âm thanh làm wakeup reply!", 5);
			loading('hide');
          } else {
			  loading('hide');
            show_message(response.message || "Thao tác thất bại!");
          }
        } catch (err) {
			loading('hide');
          show_message("Lỗi xử lý phản hồi server!");
        }
      } else {
		  loading('hide');
        show_message("Lỗi kết nối đến server!");
      }
    }
  };
  xhr.send();
}

//Tạo file âm thanh tts gcloud
function createAudio_Wakeup_reply(source_tts) {
	loading('show');
  const text = document.getElementById("tts_audio_reply_input_text").value.trim();
  if (!text) {
	  loading('hide');
    showMessagePHP("Vui lòng nhập nội dung để tạo âm thanh", 5);
    return;
  }
  let url_tts = "";

  if (source_tts === 'tts_ggcloud'){
  const speed = document.getElementById("create_tts_ggcloud_WakeUP_Reply_speed").value;
  const voiceName = document.getElementById("tts_audio_reply_voice_name").value;
  const encodedText = encodeURIComponent(text);
  const encodedVoice = encodeURIComponent(voiceName);
  url_tts = "includes/php_ajax/TTS_Audio_Create.php?create_tts_audio&source_tts=tts_ggcloud&language_code=vi-VN&speaking_rate="+speed+"&voice_name="+encodedVoice+"&text="+encodedText;
  }

	else if (source_tts === 'tts_zalo') {
		const speakerId = document.getElementById("create_tts_zalo_WakeUP_Reply_voice_name").value;
		const speakerSpeed = document.getElementById("create_tts_zalo_WakeUP_Reply_speed").value;
		const encodedText = encodeURIComponent(text);
		url_tts = "includes/php_ajax/TTS_Audio_Create.php"
			+ "?create_tts_audio"
			+ "&source_tts=tts_zalo"
			+ "&speaker_id=" + speakerId
			+ "&speaker_speed=" + speakerSpeed
			+ "&encode_type=1&text=" + encodedText;
	}

  const xhr = new XMLHttpRequest();
  xhr.open("GET", url_tts, true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          const data = JSON.parse(xhr.responseText);
			if (data.success) {
			  showMessagePHP(data.message || "Tạo file âm thanh thành công", 5);
			  const filePathInput = document.getElementById("tts_audio_reply_output_path");
			  const showww_reply_output_path = document.getElementById("showww_tts_audio_reply_output_path");
				const playButton = document.getElementById("btn_play_audio_reply_out_p");
				const downloadButton = document.getElementById("btn_download_audio_reply_out_p");
				const adddButton = document.getElementById("add_use_this_wakeup_reply_sound");
				 
			  if (filePathInput && showww_reply_output_path) {
				loading('hide');
				filePathInput.value = data.file_path || "";
				showww_reply_output_path.style.display = "flex";
				playButton.setAttribute("onclick", "playAudio('" + data.file_path + "')");
				downloadButton.setAttribute("onclick", "downloadFile('" + data.file_path + "')");
				adddButton.setAttribute("onclick", "use_this_wakeup_reply_sound('" + data.file_path + "')");
			  }
			}else {
				loading('hide');
            showMessagePHP("Lỗi khi tạo file âm thanh: " + (data.message || "Không rõ lỗi"), 5);
          }
        } catch (e) {
			loading('hide');
          showMessagePHP("Lỗi Phản Hồi Từ Server, Vui Lòng Thử Lại: " + e.message, 5);
        }
      } else {
		  loading('hide');
        showMessagePHP("Không thể gửi yêu cầu tạo âm thanh. Mã lỗi: " + xhr.status, 5);
      }
    }
  };
  xhr.send();
}

//Lấy token zai_did tts_default
function get_token_tts_default_zai_did() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Check_Connection.php?get_token_tts_default_zai_did', true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const zaiDid = response.zai_did;
                    const expires = response.expires_zai_did;
                    document.getElementById('authentication_zai_did').value = zaiDid;
                    document.getElementById('expires_zai_did').value = expires;
					showMessagePHP(response.message, 5);
                } else {
					show_message(response.message);
                }
            } catch (e) {
				show_message(e);
            }
        }
    };
    xhr.send();
}

//Tải danh sách giọng đọc của google tts cloud
function load_list_GoogleVoices_tts(select_tts_gcloud, loadingg='no') {
	if (loadingg === 'ok') loading("show");
	let selectElement_tts_gcloud;
	let currentSelectedVoice_tts_gcloud;
	if (select_tts_gcloud === 'tts_ggcloud') {
		selectElement_tts_gcloud = document.getElementById('tts_ggcloud_voice_name');
		currentSelectedVoice_tts_gcloud = '<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud']['voice_name']; ?>';
	} else if (select_tts_gcloud === 'tts_ggcloud_key') {
		selectElement_tts_gcloud = document.getElementById('tts_ggcloud_key_voice_name');
		currentSelectedVoice_tts_gcloud = '<?php echo $Config['smart_config']['smart_answer']['text_to_speak']['tts_ggcloud_key']['voice_name']; ?>';
	}else if (select_tts_gcloud === 'tts_audio_reply') {
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
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					try {
						const data = JSON.parse(xhr.responseText);
						const voiceList = data.voice_list_vi_vn;
						selectElement_tts_gcloud.innerHTML = "";
						voiceList.forEach(function (voice) {
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
	// Gọi GitHub trước, nếu lỗi sẽ tự động fallback
	loadJSON("https://api.github.com/repos/marion001/VBot_Offline/contents/html/includes/other_data/list_voices_tts_gcloud.json", true);
}
      // Đặt sự kiện khi DOM đã được tải hoàn toàn
      document.addEventListener('DOMContentLoaded', function() {
      	//cập nhật giá tị khi select thay đổi vào  play_Audio_Welcome
          updateButton_Audio_Welcome();
          document.getElementById('sound_welcome_file_path').addEventListener('change', updateButton_Audio_Welcome)
      	// Gọi hàm để cập nhật trạng thái ban đầu lựa chọn nguồn hotword đánh thức
          selectHotwordWakeup();
      });

    </script>
  </body>
</html>