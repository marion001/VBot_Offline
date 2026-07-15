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
  $_SESSION['user_login']['login_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>

<head>
  <!-- CSS thanh trượt Volume index.php -->
  <style>
    .volume-slider {
      position: relative;
      width: 50px;
      height: 168px;
      background: #ddd;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      top: 15px;
      touch-action: none;
    }

    .volume-bar {
      position: absolute;
      bottom: 0;
      width: 100%;
      background: #3498db;
      border-radius: 10px;
    }

    .volume-percentage {
      position: absolute;
      top: 15%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 16px;
      color: #000000;
      z-index: 1;
    }

    .volume-icon {
      position: absolute;
      font-size: 24px;
      color: #000000;
      bottom: 10px;
      z-index: 1;
    }
  </style>
  <!-- CSS thanh trượt độ sáng đèn led -->
  <style>
    .led_brightness-slider {
      position: relative;
      width: 50px;
      height: 168px;
      background: #ddd;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      top: 15px;
      touch-action: none;
    }

    .led_brightness-bar {
      position: absolute;
      bottom: 0;
      width: 100%;
      background: #3498db;
      border-radius: 10px;
    }

    .led_brightness-percentage {
      position: absolute;
      top: 15%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 16px;
      color: #000000;
      z-index: 1;
    }

    .led_brightness-icon {
      position: absolute;
      font-size: 24px;
      color: #000000;
      bottom: 10px;
      z-index: 1;
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
      <h1>Bảng điều khiển</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">bảng điều khiển</li>
        </ol>
      </nav>
    </div>
    <!-- End Page Title -->
    <section class="section dashboard">
      <div class="row">
        <div class="col-lg-12" id="div_message_error" style="display: none;">
          <div class="alert alert-danger alert-dismissible fade show" id="message_error" role="alert">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        </div>
        <!-- Left side columns -->
        <div class="col-lg-8">
          <div class="row">
            <div class="col-xxl-4 col-md-6">

			<div class="card info-card revenue-card position-relative" style="background-color: #f8f9fa;">
			  <i class="bi bi-info-circle-fill position-absolute top-0 end-0 mt-2 me-2 text-primary" id="weather_info" style="cursor: pointer; font-size: 1.1rem;" onclick="show_message('Dữ liệu thời tiết này được lấy từ openweathermap sử dụng vị trí tọa độ: (Vĩ độ - latitude) và (Kinh độ - longitude) được cấu hình trong: <b>Cá nhân -> <a href=\'Users_Profile.php\'>Chỉnh sửa hồ sơ</a></b>')"></i>

			  <div class="card-body">
				<h5 class="card-title" id="show_city">N/A</h5>
				<font color="green">
				  <div id="show_description"></div>
				</font>
				<div class="d-flex align-items-center">
				  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
					<img id="weather-icon">
				  </div>
				  <div class="ps-3">
					<h6 id="show_weather">N/A</h6>
					<span class="text-muted small pt-2 ps-1">Độ ẩm: </span>
					<span class="text-success small pt-1 fw-bold" id="show_humidity">N/A</span>
					<br />
					<span class="text-muted small pt-2 ps-1">Gió: </span>
					<span class="text-success small pt-1 fw-bold" id="show_windSpeed">N/A</span>
				  </div>
				</div>
			  </div>
			</div>

            </div>
            <!-- End Revenue Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card revenue-card" style="background-color: #f8f9fa;">
                <div class="card-body">
                  <h5 class="card-title" id="show_wifi_name">N/A</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-wifi"></i>
                    </div>
                    <div class="ps-3">
                      <b>
                        <h5 id="show_bit_rate">N/A</h5>
                      </b>
                      <span class="text-muted small pt-2 ps-1">Tần số: </span> <span class="text-success small pt-1 fw-bold" id="show_frequency">N/A</span>
                      <br /><span class="text-muted small pt-2 ps-1">Tx_Power: </span> <span class="text-success small pt-1 fw-bold" id="show_Tx_Power">N/A</span>
                      <br /><span class="text-muted small pt-2 ps-1">Link_Quality: </span> <span class="text-success small pt-1 fw-bold" id="show_Link_Quality">N/A</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Revenue Card -->
            <!-- Revenue Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card revenue-card" style="background-color: #f8f9fa;">
                <div class="card-body">
                  <div class="d-flex justify-content-around">
                    <div class="ps-3" title="Kéo để thay đổi âm lượng của thiết bị">
                      <div class="volume-control">
                        <div class="volume-slider" id="volume-slider">
                          <div class="volume-percentage" id="volume-percentage"></div>
                          <div class="volume-bar" id="volume-bar"></div>
                          <i class="bi bi-volume-up volume-icon" id="volume-icon"></i>
                          <div class="volume-knob" id="volume-knob"></div>
                        </div>
                      </div>
                    </div>
                    <div class="ps-3" title="Kéo để thay đổi độ sáng đèn Led của thiết bị">
                      <div class="led_brightness-control">
                        <div class="led_brightness-slider" id="led_brightness-slider">
                          <div class="led_brightness-percentage" id="led_brightness-percentage"></div>
                          <div class="led_brightness-bar" id="led_brightness-bar"></div>
                          <i class="bi bi-brightness-high led_brightness-icon" id="led_brightness-icon"></i>
                          <div class="led_brightness-knob" id="led_brightness-knob"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Revenue Card -->
            <!-- Reports -->
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="card-title d-flex justify-content-between align-items-center">
                    <label>Trình Phát Đa Phương Tiện - Media Player:</label>
					<span class="d-none" id="ble_active">
						<i class="bi bi-bluetooth text-primary"></i> 
						<span id="bluetooth_status" class="text-success"></span>
					</span>
                  </div>
                  <div id="media-container">
                    <img id="media-cover" src="assets/img/Error_Null_Media_Player.png" alt="Media Cover">
                    <div id="media-info">
                      <p id="media-name">Tên Bài Hát: <font color="blue">N/A</font>
                      </p>
                      <p id="audio-playing">Trạng Thái: <font color="blue">N/A</font>
                      </p>
                      <p id="audio-source">Nguồn Phát: <font color="blue">N/A</font>
                      </p>
                    </div>

                  </div>

                  <div id="waveContainer_song_nhac" style="display: none; justify-content: center; align-items: center;">
                    <canvas id="waveCanvas_songNhac" height="70" style="width: 100%;"></canvas>
                  </div>

                  <div id="progress-container">
                    <input type="range" id="progress-bar" min="0" max="100" value="0" title="Kéo để tua khi đang phát nhạc">
                    <div id="time-info">
                      <font color=red>00:00:00 / 00:00:00</font>
                    </div>
                  </div>
                  <center>
					<button type="button" id="play_Button" name="play_Button" title="Phát nhạc" class="btn btn-success" onclick="control_media('resume')"><i class="bi bi-play-circle"></i></button>
                    <button type="button" id="pause_Button" name="pause_Button" title="Tạm dừng phát nhạc" class="btn btn-warning" onclick="control_media('pause')"><i class="bi bi-pause-circle"></i></button>
                    <button type="button" id="stop_Button" name="stop_Button" title="Dừng phát nhạc" class="btn btn-danger" onclick="control_media('stop')"><i class="bi bi-stop-circle"></i></button>
					
					<button type="button" id="ble_prev_Button" name="ble_prev_Button" title="Bluetooth: Chuyển bài trước đó" class="btn btn-primary" onclick="bluetooth_control('previous')"><i class="bi bi-bluetooth"></i> <i class="bi bi-skip-backward"></i></button>
					<button type="button" id="ble_next_Button" name="ble_next_Button" title="Bluetooth: Chuyển bài kế tiếp" class="btn btn-primary" onclick="bluetooth_control('next')"><i class="bi bi-skip-forward"></i> <i class="bi bi-bluetooth"></i></button>

                  </center>
                  <br/>
                  <center>
				  <button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát trước đó" class="btn btn-success rounded-pill" onclick="playlist_media_control('prev')"><i class="bi bi-music-note-list"></i> <i class="bi bi-skip-backward-fill"></i></button>
                    <button type="button" id="play_Button" name="play_Button" title="Phát nhạc trong Play List" class="btn btn-primary rounded-pill" onclick="playlist_media_control()"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button>
                    <button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát kế tiếp" class="btn btn-success rounded-pill" onclick="playlist_media_control('next')"><i class="bi bi-skip-forward-fill"></i> <i class="bi bi-music-note-list"></i></button>
                  </center>
                </div>
                <hr />
                <div class="card-body">
				<div class="input-group">
				 <span class="input-group-text border-success">Nguồn Nhạc:</span>
                  <select class="form-select border-success" title="Chọn nguồn nhạc để phát hoặc tìm kiếm" id="select_cache_media">
                    <option value="" selected>--- Chọn Nguồn Nhạc ---</option>
					<option value="Link_URL">Nhập URL/Link Nguồn Âm Thanh</option>
                    <option value="Local">Local (Nội bộ)</option>
                    <option value="Youtube">Youtube</option>
                    <option value="ZingMP3">ZingMP3</option>
                    <option value="NhacCuaTui">NhacCuaTui</option>
                    <option value="PlayList_List">PlayList, Danh Sách Phát</option>
                    <option value="PodCast">PodCast</option>
                    <option value="Radio">Đài, Radio</option>
                    <option value="NewsPaper">Báo, Tin Tức</option>
                  </select>
                </div>
                </div>
                <div class="card-body">
                  <!--  <div id="show_list_ZingMP3"></div> -->
                  <div id="NewsPaper_Select" style="display: none;">
                    <?php
                    if (isset($Config['media_player']['news_paper_data']) && is_array($Config['media_player']['news_paper_data'])) {
                      echo '<div class="input-group form-floating mb-3">	<select class="form-select border-success" name="news_paper" id="news_paper">';
                      echo '<option value="">-- Chọn Báo, Tin Tức --</option>';
                      foreach ($Config['media_player']['news_paper_data'] as $newsPaper) {
                        $name = isset($newsPaper['name']) ? htmlspecialchars($newsPaper['name']) : 'Không rõ tên';
                        $link = isset($newsPaper['link']) ? htmlspecialchars($newsPaper['link']) : '#';
                        echo '<option value="' . $link . '" title="Báo: ' . $name . '">' . $name . '</option>';
                      }
                      echo '</select><label for="news_paper">Chọn Trang Báo, Tin Tức:</label><button class="btn btn-success border-success" type="button" onclick="get_data_newspaper()"><i class="bi bi-search"></i></button></div>';
                    } else {
                      echo 'Không tìm thấy dữ liệu báo/tin tức trong tệp JSON.';
                    }
                    ?>
                  </div>
                  <div id="tableContainer"></div>
                </div>
              </div>
            </div>
            <!-- End Reports -->
            <!-- TTS -->
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title d-flex align-items-center">Phát Thông Báo &nbsp;<i class="bi bi-megaphone"></i> &nbsp;<i class="bi bi-question-circle-fill" onclick="show_message('Phát nội dung cần thông báo ra loa')"></i>
                    &nbsp; Tới Thiết Bị:&nbsp;
                    <select class="form-select border-success" style="width: auto;" name="source_text_to_speak_api" id="source_text_to_speak_api">
                      <option value="<?php echo $URL_API_VBOT; ?>" data-full_name_tts_api="<?php echo $Config['contact_info']['full_name']; ?>" selected><?php echo $Config['contact_info']['full_name']; ?> - Mặc Định</option>

                    </select>

                  </h5>
                  <div class="form-floating mb-3">
                    <textarea type="text" class="form-control border-success" style="height: 100px;" name="tts_speaker_notify" id="tts_speaker_notify">
</textarea>
                    <label for="tts_speaker_notify" class="form-label">Nhập nội dung cần thông báo</label>
                    <br />
                    <center>
                      <button type="button" class="btn btn-primary" onclick="tts_speaker_notify_send()" title="Phát nội dung thông báo ra loa"><i class="bi bi-megaphone"></i> Phát</button>
                      <button class="btn btn-danger" title="Xóa toàn bộ nội dung thông báo" onclick="tts_speaker_notify_send('delete_text_tts')" title="Xóa nội dung đã nhập trong nhập liệu thông báo"><i class="bi bi-trash"></i></button>
                      <button class="btn btn-warning" id="download_tts_audio" onclick="showMessagePHP('Không có dữ liệu để tải xuống', 5)" title="Tải xuống tệp âm thanh đã phát thông báo"><i class="bi bi-download"></i></button>
                      <button class="btn btn-success" id="playAudio_tts_audio" onclick="showMessagePHP('Không có dữ liệu để phát', 5)" title="Nghe tệp âm thanh đã phát thông báo trực tiếp trên thiết bị"><i class="bi bi-play"></i></button>
                    </center>
                  </div>
                </div>
              </div>
            </div>
            <!-- End TTS -->
          </div>
        </div>
        <!-- End Left side columns -->
        <!-- Right side columns -->
        <div class="col-lg-4">
          <!-- Chức năng chung -->
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-sliders"></i> Chức Năng Chung:</span></h5>
              <div class="activity">
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" title="Đồng bộ VBot với Web UI" type="checkbox" name="sync_checkbox" id="sync_checkbox" <?php echo $Config['media_player']['media_sync_ui']['active'] ? 'checked' : ''; ?>>
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green"><i class="bi bi-arrow-repeat"></i> Đồng bộ, Sync WebUI <i class="bi bi-question-circle-fill" onclick="show_message('Đồng bộ trạng thái và dữ liệu của Bot với Web UI theo thời gian thực<br/>- Tắt hoặc thiết lập thời gian trễ trong: <b>Cấu hình Config -> Cấu Hình Media Player -> Đồng bộ trạng thái Media với Web UI</b> ')"></i></font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" type="checkbox" id="media_player_active" name="media_player_active" onclick="change_to_another_mode(2, 'media_player_active', this.checked)" <?php echo $Config['media_player']['active'] ? 'checked' : ''; ?>>
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green"><i class="bi bi-disc"></i> Media Player <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt Để kích hoạt sử dụng trình phát nhạc Media Player Khi được tắt sẽ không ra lệnh phát được Bài Hát, PodCast, Radio, v..v...')"></i></font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" type="checkbox" id="wake_up_in_media_player" name="wake_up_in_media_player" onclick="change_to_another_mode(2, 'wake_up_in_media_player', this.checked)" <?php echo $Config['media_player']['wake_up_in_media_player'] ? 'checked' : ''; ?>>
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green">Wake Up in Media Player <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt Để Cho Phép Đánh Thức Khi Đang Phát Media player')"></i></font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" type="checkbox" id="cache_tts" name="cache_tts" onclick="change_to_another_mode(2, 'cache_tts', this.checked)" <?php echo $Config['smart_config']['smart_answer']['cache_tts']['active'] ? 'checked' : ''; ?>>
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green">Cache lại kết quả TTS <i class="bi bi-question-circle-fill" onclick="show_message('Khi được bật hệ thống sẽ sử dụng lại dữ liệu cache, dữ liệu trước đó để sử dụng nhằm làm tăng tốc độ và tối ưu quá trình xử lý dữ liệu')"></i></font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" type="checkbox" name="show_mic_on_off" id="show_mic_on_off" onclick="change_to_another_mode(2, 'mic_on_off', this.checked)">
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
<b id="mic_status">
    <font id="mic_status_text" color="green">
        <i id="mic_icon" class="bi bi-mic"></i>
        Mic, Microphone
        <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt Mic tạm thời')"></i>
    </font>
</b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" type="checkbox" name="show_conversation_mode" id="show_conversation_mode" onclick="change_to_another_mode(2, 'conversation_mode', this.checked)" <?php echo $Config['smart_config']['smart_wakeup']['conversation_mode'] ? 'checked' : ''; ?>>
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green">Chế độ hội thoại <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sẽ hỏi đáp, lắng nghe liên tục mà không cần đánh thức Wake UP lại Bot')"></i></font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" type="checkbox" id="show_wake_up" name="show_wake_up" onclick="change_to_another_mode(2, 'wake_up', this.checked)">
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green"><i class="bi bi-play-circle"></i> Đánh thức, Wake up</font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" type="checkbox" name="show_wakeup_reply" id="show_wakeup_reply" onclick="change_to_another_mode(2, 'wakeup_reply', this.checked)" <?php echo $Config['smart_config']['smart_wakeup']['wakeup_reply']['active'] ? 'checked' : ''; ?>>
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green"> Bật, Tắt Chế độ câu phản hồi <i class="bi bi-question-circle-fill" onclick="show_message('Khi được đánh thức bằng giọng nói, hệ thống sẽ phản hồi lại bằng file âm thanh sau đó tiếp tục nghe lệnh từ người dùng')"></i></font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" type="checkbox" name="multiple_command_active" id="multiple_command_active" onclick="change_to_another_mode(2, 'multiple_command', this.checked)" <?php echo $Config['multiple_command']['active'] ? 'checked' : ''; ?>>
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green"> Bật, Tắt Chế độ xử lý đa câu lệnh <i class="bi bi-question-circle-fill" onclick="show_message('Khi được Bật, sẽ kích hoạt chế độ xử lý nhiều hành động trong 1 câu lệnh, Ví dụ câu lệnh: <br/>- Bật đèn ngủ và tắt đèn phòng khách<br/> - Bật đèn phòng ngủ sau đó phát danh sách nhạc<br/> Từ khóa phân tách nhiều lệnh trong 1 câu: <b>và, sau đó, rồi</b> trong file: <b>Adverbs.json</b>')"></i></font>
                    </b>
                  </div>
                </div>
              </div>
            </div>
			
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-bluetooth"></i> Bluetooth > <a href="FAQ.php" target="_blank"><i class="bi bi-patch-question-fill"></i></a>:</span></h5>
              <div class="activity">
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-danger" disabled type="checkbox" name="bluetooth_active" id="bluetooth_active">
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="red"> Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Tự Động Kiểm Tra Bluetooth Có Được Kích Hoạt Và Tồn Tại Trên Hệ Thống')"></i></font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" title="Bật, Tắt Âm Thanh Bluetooth (Mute, Un-Mute)" type="checkbox" name="bluetooth_mute_unmute" id="bluetooth_mute_unmute" onclick="change_to_another_mode(1, 'bluetooth_mute', this.checked)">
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green"><i class="bi bi-volume-up"></i> Bật, Tắt Âm Thanh <i class="bi bi-question-circle-fill" onclick="show_message('Bật, Tắt Âm Thanh AirPlay (Mute, Un-Mute)')"></i></font>
                    </b>
                  </div>
                </div>

			<div class="activity-item d-flex">
				<div class="activity-content">
					<b class="fw-bold">
						Phiên bản: <span class="text-muted" id="version_bluetooth">N/A</span>
					</b>
				</div>
			</div>
                </div>
			  </div>
			
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-apple"></i> AirPlay > <a href="FAQ.php" target="_blank"><i class="bi bi-patch-question-fill"></i></a>:</span> | <button type="button" style="font-size: 0.75rem;" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="check_version_airplay()" title="Nhấn để kiểm tra phiên bản cập nhật mới">Kiểm tra cập nhật</button></h5>
              <div class="activity">
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-danger" disabled type="checkbox" name="airplay_active" id="airplay_active">
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="red"> Kích Hoạt <i class="bi bi-question-circle-fill" onclick="show_message('Tự Động Kiểm Tra AirPlay Có Được Kích Hoạt Và Tồn Tại Trên Hệ Thống')"></i></font>
                    </b>
                  </div>
                </div>
                <div class="activity-item d-flex">
                  <div class="form-switch">
                    <input class="form-check-input border-success" title="Bật, Tắt Âm Thanh AirPlay (Mute, Un-Mute)" type="checkbox" name="airplay_mute_unmute" id="airplay_mute_unmute" onclick="change_to_another_mode(1, 'airplay_mute', this.checked)">
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="green"><i class="bi bi-volume-up"></i> Bật, Tắt Âm Thanh <i class="bi bi-question-circle-fill" onclick="show_message('Bật, Tắt Âm Thanh AirPlay (Mute, Un-Mute)')"></i></font>
                    </b>
                  </div>
                </div>

			<div class="activity-item d-flex">
				<div class="activity-content">
					<b class="fw-bold">
						Phiên bản: <span class="text-muted" id="version_airplay">N/A</span>
					</b>
				</div>
			</div>

                </div>
			  </div>
          </div>
          <!-- kết thúc chức năng chung -->
          <!-- Chức Năng Khác -->
          <div class="card">
            <div class="card-body pb-0">
              <h5 class="card-title">Chế Độ Khác:</h5>
              <div id="budgetChart" class="echart">
                <ul>
                  <li>
                    <font color="blue">Home Assistant:</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="home_assistant_active" type="checkbox" name="home_assistant_active" id="home_assistant_active" onclick="change_to_another_mode(2, 'home_assistant', this.checked)" <?php if ($Config['home_assistant']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Home Assistant <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để liên kết và điều khiển nhà thông minh')"></i>
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="hass_custom_commands_active" type="checkbox" name="hass_custom_commands_active" id="hass_custom_commands_active" onclick="change_to_another_mode(2, 'hass_custom_active', this.checked)" <?php if ($Config['home_assistant']['custom_commands']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Lệnh Tùy Chỉnh <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt sử dụng lệnh tùy chỉnh (Custom Command) để điều khiển nhà thông minh')"></i>
                        </label>
                      </div>
                    </div>
                  </li>
                  <li>
                    <font color="blue">DEV Customization (Custom Skill):</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="developer_customization_active" type="checkbox" name="developer_customization_active" id="developer_customization_active" onclick="change_to_another_mode(2, 'dev_custom', this.checked)" <?php if ($Config['developer_customization']['active'] === false) echo "disabled"; ?> <?php if ($Config['developer_customization']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Custom Skill <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng chế độ DEV Customization (Custom Skill)')"></i>
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="developer_customization_vbot_processing" type="checkbox" name="developer_customization_vbot_processing" id="developer_customization_vbot_processing" onclick="change_to_another_mode(2, 'dev_custom_vbot', this.checked)" <?php if ($Config['developer_customization']['active'] === false) echo "disabled"; ?><?php if ($Config['developer_customization']['if_custom_skill_can_not_handle']['vbot_processing'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Áp dụng thêm VBot xử lý <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng VBot xử lý khi Custom Skill không thể xử lý')"></i>
                        </label>
                      </div>
                    </div>
                  </li>
                  <li>
                    <font color="blue">Trợ lý ảo <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để sử dụng trợ lý ảo tương ứng')"></i> :</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="default_assistant_active" type="checkbox" name="default_assistant_active" id="default_assistant_active" onclick="change_to_another_mode(2, 'default_assistant', this.checked)" <?php if ($Config['virtual_assistant']['default_assistant']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Default Assistant
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="google_gemini_active" type="checkbox" name="google_gemini_active" id="google_gemini_active" onclick="change_to_another_mode(2, 'google_gemini', this.checked)" <?php if ($Config['virtual_assistant']['google_gemini']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Google Gemini
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="chat_gpt_active" type="checkbox" name="chat_gpt_active" id="chat_gpt_active" onclick="change_to_another_mode(2, 'chat_gpt', this.checked)" <?php if ($Config['virtual_assistant']['chat_gpt']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Chat GPT
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="zalo_assistant_active" type="checkbox" name="zalo_assistant_active" id="zalo_assistant_active" onclick="change_to_another_mode(2, 'zalo_assistant', this.checked)" <?php if ($Config['virtual_assistant']['zalo_assistant']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Zalo AI Assistant
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="dify_ai_active" type="checkbox" name="dify_ai_active" id="dify_ai_active" onclick="change_to_another_mode(2, 'dify_ai', this.checked)" <?php if ($Config['virtual_assistant']['dify_ai']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Dify AI
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="olli_active" type="checkbox" name="olli_active" id="olli_active" onclick="change_to_another_mode(2, 'olli', this.checked)" <?php if ($Config['virtual_assistant']['olli']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Olli AI Assistant
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="xiaozhi_active" type="checkbox" name="xiaozhi_active" id="xiaozhi_active" onclick="change_to_another_mode(2, 'xiaozhi', this.checked)" <?php if ($Config['xiaozhi']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          XiaoZhi AI
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="dev_custom_assistant_active" type="checkbox" name="dev_custom_assistant_active" id="dev_custom_assistant_active" onclick="change_to_another_mode(2, 'dev_custom_assistant', this.checked)" <?php if ($Config['virtual_assistant']['customize_developer_assistant']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          DEV Custom Assistant (Dev_Assistant.py)
                        </label>
                      </div>

                    </div>
                  </li>
                  <li>
                    <font color="blue">Nguồn Nhạc <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để sử dụng nguồn Nhạc, Radio, PodCast tương ứng, khi Bot tìm kiếm dữ liệu')"></i> :</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="music_local_active" type="checkbox" name="music_local_active" id="music_local_active" onclick="change_to_another_mode(2, 'music_local', this.checked)" <?php if ($Config['media_player']['music_local']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Music Local
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="zing_mp3_active" type="checkbox" name="zing_mp3_active" id="zing_mp3_active" onclick="change_to_another_mode(2, 'zing_mp3', this.checked)" <?php if ($Config['media_player']['zing_mp3']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Zing MP3
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="nhaccuatui_active" type="checkbox" name="nhaccuatui_active" id="nhaccuatui_active" onclick="change_to_another_mode(2, 'nhaccuatui', this.checked)" <?php if ($Config['media_player']['nhaccuatui']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          NhacCuaTui - NCT
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="youtube_active" type="checkbox" name="youtube_active" id="youtube_active" onclick="change_to_another_mode(2, 'youtube', this.checked)" <?php if ($Config['media_player']['youtube']['active'] === true) echo "checked"; ?>>
                        <label class="form-check-label">
                          Youtube
                        </label>
                      </div>
                    </div>
                  </li>
                  <li>
                    <font color="blue">Đọc Báo, Tin Tức <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để sử dụng tính năng đọc báo, tin tức trong ngày')"></i> :</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="news_paper_active" type="checkbox" name="news_paper_active" id="news_paper_active" onclick="change_to_another_mode(2, 'news_paper', this.checked)" <?php if ($Config['media_player']['news_paper']['active'] === true) echo "checked"; ?>>
                      </div>
                    </div>
                  </li>

                </ul>
              </div>
            </div>
          </div>
          <!-- Kết Chức năng khác -->
          <!-- Logs hệ thống -->
          <div class="card">
            <div class="card-body pb-0">
              <h5 class="card-title">Logs Hệ Thống <span id="show_log_name_log_display_style"> | N/A </span></h5>
              <div id="budgetChart" class="echart">
                <ul>
                  <li>
                    <font color="blue">Bật, Tắt Logs hệ thống</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="on_off_display_logs" type="checkbox" name="on_off_display_logs" id="on_off_display_logs" onclick="change_og_display_style('change_log', this.checked ? 'on' : 'off', true)" <?php if ($Config['smart_config']['show_log']['active'] === true) echo "checked"; ?>>
                      </div>
                    </div>
                  </li>
                  <li>
                    <font color="blue">Thay đổi chế độ hiển thị Logs <i class="bi bi-question-circle-fill" onclick="show_message('Thay đổi chế độ hiển thị Logs đầu ra trực tiếp và lấy dữ liệu theo thời gian thực')"></i> :</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="console" type="radio" name="select_log_display_style" id="log_display_style_console" onclick="change_og_display_style('change_log', 'console', this.checked)" <?php if ($Config['smart_config']['show_log']['log_display_style'] === "console") echo "checked"; ?>>
                        <label class="form-check-label">
                          Console
                        </label>
                      </div>
                      <div class="form-check">
                        <input <?php if ($Config['api']['active'] === false) echo "disabled"; ?> class="form-check-input border-success" value="api" type="radio" name="select_log_display_style" id="log_display_style_api" onclick="change_og_display_style('change_log', 'api', this.checked)" <?php if ($Config['smart_config']['show_log']['log_display_style'] === "api") echo "checked"; ?>>
                        <label class="form-check-label">
                          API <a href="<?php echo $URL_API_VBOT ?>logs" target="_bank" title="Mở URL Logs API trong tab mới"> <i class="bi bi-box-arrow-up-right"></i></a>
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-danger" disabled value="dev_custom" type="radio" name="select_log_display_style" id="log_display_style_dev_custom" onclick="change_og_display_style('change_log', 'dev_custom', this.checked)" <?php if ($Config['smart_config']['show_log']['log_display_style'] === "dev_custom") echo "checked"; ?>>
                        <label class="form-check-label text-danger">DEV Custom Logs</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input border-success" value="all" type="radio" name="select_log_display_style" id="log_display_style_both" onclick="change_og_display_style('change_log', 'all', this.checked)" <?php if ($Config['smart_config']['show_log']['log_display_style'] === "all") echo "checked"; ?>>
                        <label class="form-check-label">
                          ALL (Tất Cả) <a href="<?php echo $URL_API_VBOT ?>logs" target="_bank" title="Mở URL Logs API trong tab mới"> <i class="bi bi-box-arrow-up-right"></i></a>
                        </label>
                      </div>
                    </div>
                  </li>
                  <li>
                    <font color="blue">Dọn dẹp Logs</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="log_api_del" type="radio" name="delete_log_api" id="delete_log_api" onclick="change_og_display_style('clear_api', 'clear_api', this.checked)">
                        <label class="form-check-label">
                          Xóa Log API
                        </label>
                      </div>
                    </div>
                  </li>
                  <li>
                    <font color="blue">MQTT Broker Logs</font>
                    <div class="form-switch">
                      <div class="form-check">
                        <input class="form-check-input border-success" value="mqtt_show_logs_reconnect" type="checkbox" name="mqtt_show_logs_reconnect" id="mqtt_show_logs_reconnect" onclick="change_og_display_style('mqtt_show_logs_reconnect', this.checked ? 'on' : 'off', true)" <?php if ($Config['mqtt_broker']['mqtt_show_logs_reconnect'] === true) echo "checked"; ?>>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <!-- Kết thúc Logs hệ thống -->
        </div>
        <!-- End Right side columns -->
      </div>
    </section>
  </main>
  <!-- End #main -->
  <!-- ======= Footer ======= -->
  <?php
  include 'html_footer.php';
  ?>
  <!-- End Footer -->
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <?php
  include 'html_js.php';
  ?>
  <script>
    //hàm để hiển thị thông tin vị trí và thời tiết
	async function getLocationAndWeather() {
	  try {

		//const locRes = await fetch('https://ipinfo.io/json');
		//if (!locRes.ok) throw new Error('Lỗi lấy location');
		//const locData = await locRes.json();
		//var locArray = locData.loc.split(',');
		//var lat = locArray[0];
		//var lon = locArray[1];

		var lat = "<?php echo $Config['contact_info']['location']['latitude']; ?>";
		var lon = "<?php echo $Config['contact_info']['location']['longitude']; ?>";

		var weatherUrl = 'https://api.openweathermap.org/data/2.5/weather?lat=' + lat + '&lon=' + lon + '&appid=8473858601dabd3d2cbb24fb50840686&units=metric&lang=vi';
		const weatherRes = await fetch(weatherUrl);
		if (!weatherRes.ok) throw new Error('Lỗi lấy thông tin thời tiết');
		var w = await weatherRes.json();
		var elTemp = document.getElementById('show_weather');
		var elHumidity = document.getElementById('show_humidity');
		var elDesc = document.getElementById('show_description');
		var elWind = document.getElementById('show_windSpeed');
		var elIcon = document.getElementById('weather-icon');
		var elCity = document.getElementById('show_city');
		elTemp.textContent = w.main.temp + '°C';
		elHumidity.textContent = w.main.humidity + '%';
		elDesc.textContent = ' ' + w.weather[0].description;
		elWind.textContent = w.wind.speed + ' m/s';
		elIcon.src = 'https://openweathermap.org/img/w/' + w.weather[0].icon + '.png';
		elCity.innerHTML = w.name + ', <span>' + w.sys.country + '</span>';
	  } catch (err) {
		console.error(err);
		show_message('Không thể lấy thông tin thời tiết');
	  }
	}

    //Cập nhật và hiển thị giá trị led vào thẻ html 
    function updateBrightness(value) {
      const brightnessSlider = document.getElementById('led_brightness-slider');
      const brightnessBar = document.getElementById('led_brightness-bar');
      const brightnessKnob = document.getElementById('led_brightness-knob');
      const brightnessPercentage = document.getElementById('led_brightness-percentage');
      const height = brightnessSlider.clientHeight;
      const percentage = Math.max(0, Math.min(100, value));
      brightnessBar.style.height = percentage + '%';
      brightnessKnob.style.top = (height - (percentage / 100) * height) + 'px';
      brightnessPercentage.textContent = Math.round(percentage) + '%';
    }

    //Cập nhật giá trị volume vào id="volume-slider" html
    function set_Volume_HTML(volume) {
      const volumeSlider = document.getElementById('volume-slider');
      const volumeBar = document.getElementById('volume-bar');
      const volumeKnob = document.getElementById('volume-knob');
      const volumePercentage = document.getElementById('volume-percentage');
      const height = volumeSlider.getBoundingClientRect().height;
      volumeBar.style.height = volume + '%';
      volumeKnob.style.top = (height - (volume / 100) * height) + 'px';
      volumePercentage.textContent = Math.round(volume) + '%';
    }

    //Định dạng thời gian thành HH:MM:SS
    function formatTime_Player(milliseconds) {
      let totalSeconds = Math.floor(milliseconds / 1000);
      let hours = Math.floor(totalSeconds / 3600);
      let minutes = Math.floor((totalSeconds % 3600) / 60);
      let seconds = totalSeconds % 60;
      return hours.toString().padStart(2, '0') + ':' +
        minutes.toString().padStart(2, '0') + ':' +
        seconds.toString().padStart(2, '0');
    }

    //Dùng để tua bài hát
	function sendSetTime_duration(set_duration) {
	  const url = "<?php echo $URL_API_VBOT ?>";
	  const payload = {
		type: 1,
		data: "media_control",
		action: "set_time",
		set_duration: set_duration
	  };
	  fetch(url, {
		method: "POST",
		headers: {"Content-Type": "application/json"},
		body: JSON.stringify(payload)
	  })
	  .then(res => {
		if (!res.ok) {
		  throw new Error("Lỗi HTTP: " + res.status);
		}
		return res.json();
	  })
	  .then(response => {
		if (!response.success) {
		  throw new Error(response.message || "Không rõ lỗi");
		}
		showMessagePHP(response.message, 5);
	  })
	  .catch(err => {
		showMessagePHP("Lỗi Tua Media Player: " + err.message + ". Kiểm tra mạng, API hoặc Bot");
	  });
	}

    //Thay đổi giá trị của biến toàn cục, chế độ hội thoại, chế độ phản hồi, Mic, Wakeup
	function change_to_another_mode(type, dataKey, actionValue) {
	  const url = "<?php echo $URL_API_VBOT ?>";
		let payload;
		if (dataKey === "bluetooth_mute") {
			if (actionValue) {
				payload = {type: type, data: "bluetooth", action: "unmute"};
			} else {
				payload = {type: type, data: "bluetooth", action: "mute"};
			}
		} else {
			payload = {type: type, data: dataKey, action: actionValue};
		}
	  fetch(url, {
		method: "POST",
		headers: {"Content-Type": "application/json"},
		body: JSON.stringify(payload)
	  })
	  .then(res => {
		if (!res.ok) {
		  throw new Error("HTTP: " + res.status);
		}
		return res.json();
	  })
	  .then(response => {
		if (!response.success) {
		  throw new Error(response.message || "Không rõ lỗi");
		}
		if (dataKey === "wake_up") {
			document.getElementById('show_wake_up') && (document.getElementById('show_wake_up').checked = false);
		}
		showMessagePHP(response.message, 5);
	  })
	  .catch(err => {
		show_message("Lỗi Thay Đổi Chế Độ: " + err.message + ". Kiểm tra mạng, API hoặc Bot");
	  });
	}

    //Gửi dữ liệu thay đổi volume tới Bot
	function set_Volume_Data(volume) {
	  const url = "<?php echo $URL_API_VBOT ?>";
	  const payload = {
		type: 2,
		data: "volume",
		action: "setup",
		value: volume
	  };
	  clearTimeout(set_Volume_Data._t);
	  set_Volume_Data._t = setTimeout(() => {
		fetch(url, {
		  method: "POST",
		  headers: {"Content-Type": "application/json"},
		  body: JSON.stringify(payload)
		})
		.then(res => {
		  if (!res.ok) throw new Error("HTTP: " + res.status);
		  return res.json();
		})
		.then(response => {
		  if (!response.success) {
			throw new Error(response.message || "Không rõ lỗi");
		  }
		  set_Volume_HTML(response.volume);
		  showMessagePHP("Âm lượng đã được thay đổi thành: " + response.volume + "%", 5);
		})
		.catch(err => {
		  show_message("Lỗi Thay Đổi Âm Lượng: " + err.message + ". Kiểm tra mạng, API hoặc Bot");
		});
	  }, 150);
	}

	//Kiểm tra phiên bản AirPlay
	function check_version_airplay() {
		loading("show");
		fetch("includes/php_ajax/Check_Connection.php?check_version_airplay", {cache: "no-store"})
		.then(function(response) {
			return response.json();
		})
		.then(function(data) {
			if (!data.success) {
				show_message("AirPlay Lỗi kiểm tra cập nhật:" +data.message);
				return;
			}
			var msg =
				"<center><b>AirPlay Kiểm Tra Phiên Bản Mới</b></center><br/>" +
				"- Phiên bản hiện tại của bạn: <b>" + data.current_version + "</b><br/>" +
				"- Phiên bản đang phát hành: <b>" + data.latest_version + "</b><br/>" +
				"- Nội dung bản đang phát hành: <b>" + data.description + "</b><br/><br/>";
			if (data.update) {
				msg += "<hr/>- AirPlay Có bản cập nhật mới!<br/><br/><a href='/FAQ.php' target='_blank'>- Nhấn vào đây để Cập Nhật, Cài Đặt, Thiết Lập AirPlay</a>";
			} else {
				msg += "- <b>" +data.message+ "</b>";
			}
			show_message(msg);
		})
		.catch(function(error) {
			show_message("AirPlay Không thể kiểm tra phiên bản mới:" +error);
		})
		.finally(function() {
			loading("hide");
		});
	}

    //Thay đổi độ sáng đèn led
	function sendBrightnessData(value) {
	  const url = "<?php echo $URL_API_VBOT ?>";
	  const payload = {
		type: 2,
		data: "led",
		action: "brightness",
		value: value
	  };
	  fetch(url, {
		method: "POST",
		headers: {"Content-Type": "application/json"},
		body: JSON.stringify(payload)
	  })
	  .then(res => {
		if (!res.ok) {
		  throw new Error("HTTP: " + res.status);
		}
		return res.json();
	  })
	  .then(response => {
		if (!response.success) {
		  throw new Error(response.message || "Không rõ lỗi");
		}
		showMessagePHP(response.message, 5);
	  })
	  .catch(err => {
		show_message("Lỗi Thay Đổi Độ Sáng: " + err.message + ". Kiểm tra mạng, API hoặc Bot");
	  });
	}

	//Phát thông báo TTS
	function tts_speaker_notify_send(del_text_input = null) {
	  const textEl = document.getElementById('tts_speaker_notify');
	  const sourceEl = document.getElementById('source_text_to_speak_api');
	  if (del_text_input === "delete_text_tts") {
		textEl.value = '';
		showMessagePHP("Đã xóa nội dung trong nhập liệu thông báo", 5);
		return;
	  }
	  const text = textEl.value?.trim();
	  if (!text) {
		show_message("Hãy nhập nội dung cần phát thông báo");
		return;
	  }
	  loading("show");
	  let url = sourceEl.value;
	  let payload;
	  if (url === 'send_notify_home_assistant') {
		url = '<?php echo $URL_API_VBOT ?>';
		payload = {
		  type: 3,
		  data: "tts",
		  action: "home_assistant",
		  title: "VBot - <?php echo $Config['contact_info']['full_name']; ?>",
		  messenger: text
		};
	  } else {
		payload = {
		  type: 3,
		  data: "tts",
		  action: "notify",
		  value: text
		};
	  }
	  fetch(url, {
		method: "POST",
		headers: {"Content-Type": "application/json"},
		body: JSON.stringify(payload)
	  })
	  .then(res => {
		if (!res.ok) {
		  throw new Error("Lỗi HTTP: " + res.status);
		}
		return res.json();
	  })
	  .then(response => {
		if (!response.success) {
		  throw new Error(response.message || "Không rõ lỗi");
		}
		const msg = response?.text_tts?.trim() ? response.text_tts : response.text_messenger + '. Tới Home Assistant';
		showMessagePHP("Đã phát thông báo: " + msg, 7);
		let audioPath = response.audio_tts;
		if (Array.isArray(audioPath)) {
		  audioPath = audioPath[0];
		} else if (typeof audioPath === "string" && audioPath.startsWith("TTS_Audio")) {
		  audioPath = "<?php echo $VBot_Offline; ?>" + audioPath;
		}
		if (audioPath) {
		  document.getElementById('download_tts_audio') ?.setAttribute('onclick', `downloadFile('${audioPath}')`);
		  document.getElementById('playAudio_tts_audio') ?.setAttribute('onclick', `playAudio('${audioPath}')`);
		}
	  })
	  .catch(err => {
		show_message('Lỗi Phát TTS: ' + err.message + '. Kiểm tra mạng, API, hoặc Bot');
	  })
	  .finally(() => loading("hide"));
	}
  </script>
  
  <script>
//Command bluetooth api
function bluetooth_control(action, value) {
    if (action === "disconnect") {
        if (!confirm("Bạn có chắc chắn muốn ngắt kết nối Bluetooth không?")) {
            return;
        }
    }
	loading('show');
    const payload = {
        type: 1,
        data: 'bluetooth',
        action: action
    };
    if (value !== undefined && value !== null) {
        payload.value = value;
    }
    return fetch('<?php echo $URL_API_VBOT; ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
	.then(function(response) {
		loading('hide');
		if (!response.ok) {
			throw new Error('Lỗi HTTP: ' + response.status);
		}
		return response.json();
	})
	.then(function(data) {
		loading('hide');
		if (data.success) {
			showMessagePHP(data.message, 5);
		} else {
			show_message('Bluetooth Lỗi Control:' + data.message);
		}
	})
    .catch(function(error) {
		loading('hide');
        show_message('Lỗi API Bluetooth: ' +error);
        return {
            success: false,
            message: error.message
        };
    });
}

//Ẩn hiện id ble_active
function renderBluetoothActive(bluetooth) {
    const el = document.getElementById('ble_active');
    if (!el) return;
    if (bluetooth && bluetooth.active === true) {
        el.classList.remove('d-none');
    } else {
        el.classList.add('d-none');
    }
}

//Bật tắt 2 button next và prev tương ứng khi kết nối bluetooth
function setBleButtons(enabled) {
    ["ble_prev_Button", "ble_next_Button", "bluetooth_mute_unmute"].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.disabled = !enabled;
        }
    });
}

//Cập nhật hiển thị danh sách thiết bị bluetooth đang kết nối
let bluetoothSelectOpen = false;
function renderBluetoothStatus(bluetooth) {
	renderBluetoothActive(bluetooth);
    const statusEl = document.getElementById('bluetooth_status');
    if (!statusEl) {
        return;
    }
    const currentSelect = document.getElementById('bluetooth_device_select');

    if (bluetoothSelectOpen || (currentSelect && document.activeElement === currentSelect)) {
        return;
    }
    const devices = Object.values(bluetooth.bluetooth_devices || {});
    if (!bluetooth.is_connected || devices.length === 0) {
        statusEl.innerHTML = '<span class="text-danger">Chưa kết nối</span>';
		setBleButtons(false);
        return;
    }
    if (devices.length === 1) {
        statusEl.innerHTML ='<span class="text-success">' + devices[0].name + '</span> <button type="button" onclick="bluetooth_control(\'disconnect\')" class="btn btn-danger btn-sm py-0 px-2" style="font-size: 0.75rem;" title="Nhấn để ngắt kết nối Bluetooth với thiết bị đang kết nối hiện tại">Ngắt kết nối</button>';
		setBleButtons(true);
        return;
    }
    let html = '<select ' +
				'id="bluetooth_device_select" ' +
				'class="form-select form-select-sm border-success" ' +
				'style="width:auto; min-width:220px;" ' +
				'onfocus="bluetoothSelectOpen=true" ' +
				'onblur="bluetoothSelectOpen=false" ' +
				'onchange="bluetooth_control(\'receive_signal\', this.value)">';

    devices.forEach(function(device) {
        html += '<option value="' + device.path + '"' + (device.path === bluetooth.device_path ? ' selected' : '') + '>' + device.name + '</option>';
    });
    html += '</select>';
    statusEl.innerHTML = html;
	setBleButtons(true);
}
  </script>
  
  <script>

function update_index_data(data){
          if (data.success) {
            //console.log(data);
            document.getElementById('div_message_error').style.display = 'none';
            document.getElementById('show_conversation_mode').checked = data.conversation_mode ? true : false;
            document.getElementById('show_wakeup_reply').checked = data.wakeup_reply ? true : false;
            document.getElementById('multiple_command_active').checked = data.multiple_command_active ? true : false;
			
			//Mic
            //document.getElementById('show_mic_on_off').checked = data.mic_on_off ? true : false;
			const micOn = data.mic_on_off;
			document.getElementById('show_mic_on_off').checked = micOn;
			const micIcon = document.getElementById('mic_icon');
			const micText = document.getElementById('mic_status_text');
			if (micOn) {
				micIcon.className = 'bi bi-mic';
				micText.color = 'green';
			} else {
				micIcon.className = 'bi bi-mic-mute';
				micText.color = 'red';
			}

            document.getElementById('on_off_display_logs').checked = data.log_display_active ? true : false;
            document.getElementById('mqtt_show_logs_reconnect').checked = data.mqtt_show_logs_reconnect ? true : false;
            document.getElementById('cache_tts').checked = data.cache_tts_active ? true : false;
            document.getElementById('media_player_active').checked = data.media_player.media_player_active ? true : false;
            document.getElementById('wake_up_in_media_player').checked = data.media_player.wake_up_in_media_player ? true : false;
            document.getElementById('music_local_active').checked = data.media_player.music_local_active ? true : false;
            document.getElementById('zing_mp3_active').checked = data.media_player.zing_mp3_active ? true : false;
            document.getElementById('nhaccuatui_active').checked = data.media_player.nhaccuatui_active ? true : false;
            document.getElementById('youtube_active').checked = data.media_player.youtube_active ? true : false;
            document.getElementById('news_paper_active').checked = data.news_paper_active ? true : false;
            document.getElementById('home_assistant_active').checked = data.home_assistant_active ? true : false;
            document.getElementById('hass_custom_commands_active').checked = data.hass_custom_commands_active ? true : false;
            document.getElementById('default_assistant_active').checked = data.default_assistant_active ? true : false;
            document.getElementById('google_gemini_active').checked = data.google_gemini_active ? true : false;
            document.getElementById('chat_gpt_active').checked = data.chat_gpt_active ? true : false;
            document.getElementById('zalo_assistant_active').checked = data.zalo_assistant_active ? true : false;
            document.getElementById('dify_ai_active').checked = data.dify_ai_active ? true : false;
            document.getElementById('xiaozhi_active').checked = data.xiaozhi_active ? true : false;
            document.getElementById('olli_active').checked = data.olli_assistant_active ? true : false;
            document.getElementById('dev_custom_assistant_active').checked = data.dev_custom_assistant ? true : false;
            document.getElementById('developer_customization_active').checked = data.dev_custom ? true : false;
            document.getElementById('developer_customization_vbot_processing').checked = data.dev_custom_vbot ? true : false;
            document.getElementById('airplay_mute_unmute').checked = data.media_player.airplay_mute_on_off ? true : false;
            document.getElementById('bluetooth_mute_unmute').checked = data.bluetooth.bluetooth_mute_unmute ? true : false;
            document.getElementById('airplay_active').checked = data.media_player.airplay_active ? true : false;
            document.getElementById('bluetooth_active').checked = data.bluetooth.active ? true : false;
			renderBluetoothStatus(data.bluetooth);
            //Media Player
			document.getElementById('media-name').innerHTML =
				'Tên bài hát: <font color="blue">' +
				(
					data.media_player.airplay_playing === true
						? (
							data.media_player.airplay_song_name &&
							String(data.media_player.airplay_song_name).trim() !== 'N/A'
								? data.media_player.airplay_song_name
								: 'N/A'
						)
						: (
							(() => {
								const devices = data.bluetooth?.bluetooth_devices;
								let btName = null;
								if (devices) {
									for (const k in devices) {
										const d = devices[k];
										if (d.connected && d.playing) {
											btName =
												data.bluetooth.song_name ||
												data.bluetooth.song_artist ||
												data.bluetooth.device_name ||
												d.name ||
												'N/A';
											break;
										}
									}
								}
								return btName ||
									(
										(data.media_player.audio_playing === true || data.media_player.pause_media_flag === true)
											&& data.media_player.media_name &&
											String(data.media_player.media_name).trim() !== 'N/A'
											? data.media_player.media_name
											: 'N/A'
									);
							})()
						)
				) +
				'</font>';

			document.getElementById('audio-playing').innerHTML = 'Trạng Thái: <font color=blue>' + (data.media_player.audio_playing === true || data.bluetooth.playing === true || data.media_player.airplay_playing === true ? 'Đang phát' : (data.media_player.pause_media_flag === true ? 'Đang tạm dừng' : 'Không phát')) + '</font>';
			//Cập nhật nguồn phát nhạc
			document.getElementById('audio-source').innerHTML =
				'Nguồn Phát: <font color=blue>' +
				(
					data.bluetooth?.playing === true
						? ('<i class="bi bi-bluetooth"></i>' + (data.bluetooth.device_name ? ' - ' + data.bluetooth.device_name : ''))
						: (
							data.media_player.airplay_playing === true
								? 'AirPlay'
								: (
									(data.media_player.audio_playing === true || data.media_player.pause_media_flag === true)
										? (
											data.media_player.media_player_source &&
											String(data.media_player.media_player_source).trim() !== 'N/A'
												? data.media_player.media_player_source
												: 'Local Audio'
										)
										: 'N/A'
								)
						)
				) +
				'</font>';

            //Cập nhật ảnh cover bài hát
			document.getElementById('media-cover').src =
			(
				data.bluetooth?.playing === true && data.bluetooth?.is_connected === true
					? 'assets/img/bluetooth_icon.png'
					: (
						data.media_player.airplay_playing === true
							? 'assets/img/AirPlay_Cover.jpg?t=' + Date.now()
							: (
								(data.media_player.audio_playing === true || data.media_player.pause_media_flag === true)
									? (
										data.media_player.media_player_source === 'Local' &&
										(!data.media_player.media_cover ||
										 String(data.media_player.media_cover).trim() === '' ||
										 String(data.media_player.media_cover).trim() === 'N/A')
											? 'assets/img/icon_audio_local.png'
											: (data.media_player.media_cover || 'assets/img/Error_Null_Media_Player.png')
									)
									: 'assets/img/Error_Null_Media_Player.png'
							)
					)
			);
            //Cập nhật giá trị full time
            fullTime = data.media_player.full_time;
            if (data.media_player.audio_playing || data.media_player.airplay_playing || data.bluetooth.playing) {
              updateDisplay_SongNhac(true);
            } else {
              updateDisplay_SongNhac(false);
            }
            //Log thay đổi chế độ đầu ra
            if (data.log_display_style === "console") {
              document.getElementById('log_display_style_console').checked = true;
              rlc_log_display_style = "Console";
            } else if (data.log_display_style === "api") {
              document.getElementById('log_display_style_api').checked = true;
              rlc_log_display_style = "API";
            } else if (data.log_display_style === "all") {
              document.getElementById('log_display_style_both').checked = true;
              rlc_log_display_style = "ALL";
            } else if (data.log_display_style === "dev_custom") {
              document.getElementById('log_display_style_dev_custom').checked = true;
              rlc_log_display_style = "DEV Custom Logs";
            }
            document.getElementById('show_log_name_log_display_style').innerHTML = ' | <font color=green>' + rlc_log_display_style + '</font>';
            if (!isHovering_led_brightness) {
              const brightnessPercentzz = Math.round(Math.max(0, Math.min(255, data.led_brightness)) * 100 / 255);
              updateBrightness(brightnessPercentzz);
            }
            //Cập nhật thanh trượt chỉ khi không đang hover
            if (!isHovering_volume_slide) {
              set_Volume_HTML(data.volume);
            }
            if (!isHovering) {
              //Cập nhật volume khi chuột không trong vùng của nó
              let progressBar = document.getElementById('progress-bar');
              progressBar.max = fullTime;
              progressBar.value = data.media_player.current_duration;
              let timeInfo = document.getElementById('time-info');
              timeInfo.innerHTML = '<font color=blue>' + formatTime_Player(data.media_player.current_duration) + '</font> / ' + formatTime_Player(fullTime);
            }
          } else {
            document.getElementById('div_message_error').style.display = 'block';
            updateDisplay_SongNhac(false);
            //console.log('Lỗi khi lấy dữ liệu', data.message);
          }
}

    //script liên quan tới API GET Media Player
    let isHovering = false;
    let isHovering_volume_slide = false;
    let isHovering_led_brightness = false;
    let fullTime = 0;
    let intervalId;
    //Cập nhật thông tin GET từ API
    function fetchData_all_info() {
      //Kiểm tra nếu checkbox được tích hoặc sync_active là true
      const syncCheckbox = document.getElementById('sync_checkbox');
      var rlc_log_display_style;
      //Không thực hiện fetchData_Media_Player nếu checkbox không được tích
      if (!syncCheckbox.checked) {
        return;
      }
      fetch("<?php echo $URL_API_VBOT ?>?type=1&data=all_info")
        .then(response => {
          if (!response.ok) {
            document.getElementById('div_message_error').style.display = 'block';
            document.getElementById('message_error').innerHTML = 'Không thể kết nối đến API, Vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa, Mã Lỗi: ' + response.status;
          }
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
              throw new Error('Dữ liệu phản hồi không phải JSON');
            });
          }
          return response.json();
        })
        .then(data => {
			update_index_data(data);
        })
        .catch(error => {
          document.getElementById('div_message_error').style.display = 'block';
          document.getElementById('message_error').innerHTML = 'Không thể kết nối đến API, Vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa, ' + error;
          updateDisplay_SongNhac(false);
        });
    }
    // Bắt đầu lấy dữ liệu mỗi giây sử dụng api thường http
    //intervalId = setInterval(fetchData_all_info, <?php echo intval($Config['media_player']['media_sync_ui']['delay_time']); ?> * 1000);

	//Sử dụng cơ chế SSE nhận dữ liệu từ backend VBot
	let sseAllInfo = null;
	let sseAllInfoReconnectTimer = null;
	function stopSSE_all_info() {
		if (sseAllInfoReconnectTimer) {
			clearTimeout(sseAllInfoReconnectTimer);
			sseAllInfoReconnectTimer = null;
		}
		if (sseAllInfo) {
			if (sseAllInfo.readyState !== EventSource.CLOSED) {
				sseAllInfo.close();
			}
			sseAllInfo = null;
		}
	}

	function startSSE_all_info() {
		const syncCheckbox = document.getElementById("sync_checkbox");
		if (!syncCheckbox.checked) {
			stopSSE_all_info();
			return;
		}
		if (sseAllInfoReconnectTimer) {
			clearTimeout(sseAllInfoReconnectTimer);
			sseAllInfoReconnectTimer = null;
		}
		if (sseAllInfo) {
			if (sseAllInfo.readyState !== EventSource.CLOSED) {
				sseAllInfo.close();
			}
			sseAllInfo = null;
		}
		sseAllInfo = new EventSource("<?php echo $URL_API_VBOT ?>?type=1&data=all_info&stream=sse&interval=1");
		sseAllInfo.onopen = function () {
			//console.log("SSE kết nối thành công");
			document.getElementById("div_message_error").style.display = "none";
		};
		sseAllInfo.addEventListener("update", function (event) {
			update_index_data(JSON.parse(event.data));
		});

		sseAllInfo.onerror = function () {
			//console.log("SSE mất kết nối");
			stopSSE_all_info();
			document.getElementById("div_message_error").style.display = "block";
			document.getElementById("message_error").innerHTML = "Không thể kết nối đến API (SSE), vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa.";
			updateDisplay_SongNhac(false);
			//Nếu đã tắt Sync thì không reconnect
			if (!syncCheckbox.checked) {
				return;
			}
			sseAllInfoReconnectTimer = setTimeout(function () {
				const syncCheckbox = document.getElementById("sync_checkbox");
				if (syncCheckbox.checked) {
					startSSE_all_info();
				}
			}, 1000);
		};
	}

	//Khởi động SSE lần đầu
	startSSE_all_info();

	//Theo dõi thay đổi checkbox Sync
	document.getElementById("sync_checkbox").addEventListener("change", function () {
		if (this.checked) {
			startSSE_all_info();
		} else {
			stopSSE_all_info();
		}
	});

    // Ngừng cập nhật thanh trượt khi hover chuột vào
    document.getElementById('progress-bar').addEventListener('mouseover', () => {
      isHovering = true;
    });

    // Tiếp tục cập nhật thanh trượt khi rời chuột
    document.getElementById('progress-bar').addEventListener('mouseout', () => {
      isHovering = false;
    });

    // Lắng nghe sự kiện kéo thanh trượt để hiển thị giá trị khi nhả chuột
    document.getElementById('progress-bar').addEventListener('change', (event) => {
      const progressBar = event.target;
      const currentDuration = progressBar.value;
      //Chạy function để tua thời gian media player
      sendSetTime_duration(currentDuration);
    });

    // Cập nhật giá trị thời gian khi kéo thanh trượt
    document.getElementById('progress-bar').addEventListener('input', (event) => {
      const progressBar = event.target;
      const currentDuration = progressBar.value;
      document.getElementById('time-info').innerHTML = '<font color=green>' + formatTime_Player(currentDuration) + '</font> / ' + formatTime_Player(fullTime);
    });

    //dừng cập nhật volume khi đang hover chuột vào
    document.getElementById('volume-slider').addEventListener('mouseover', () => {
      isHovering_volume_slide = true;
    });

    //tiếp tục cập nhật volume khi đang hover chuột vào
    document.getElementById('volume-slider').addEventListener('mouseout', () => {
      isHovering_volume_slide = false;
    });

    //dừng cập nhật led_brightness-slider khi đang hover chuột vào
    document.getElementById('led_brightness-slider').addEventListener('mouseover', () => {
      isHovering_led_brightness = true;
    });

    //tiếp tục cập nhật led_brightness-slider khi đang hover chuột vào
    document.getElementById('led_brightness-slider').addEventListener('mouseout', () => {
      isHovering_led_brightness = false;
    });

    function updateVolume(e) {
      const volumeSlider = document.getElementById('volume-slider');
      const volumeBar = document.getElementById('volume-bar');
      const volumeKnob = document.getElementById('volume-knob');
      const volumePercentage = document.getElementById('volume-percentage');
      const rect = volumeSlider.getBoundingClientRect();
      const offsetY = e.clientY - rect.top;
      const height = rect.height;
      const percentage = Math.max(0, Math.min(100, ((height - offsetY) / height) * 100));
      volumeBar.style.height = percentage + '%';
      volumeKnob.style.top = (height - (percentage / 100) * height) + 'px';
      volumePercentage.textContent = Math.round(percentage) + '%';
    }
  </script>

  <script>
    //Thay đổi và cập nhật volume khi trượt thanh slide html sự kiện chuột
    function setupVolumeControl() {
      const volumeSlider = document.getElementById('volume-slider');
      const volumeBar = document.getElementById('volume-bar');
      const volumeKnob = document.getElementById('volume-knob');
      const volumePercentage = document.getElementById('volume-percentage');
      let isDragging = false;
      volumeSlider.addEventListener('mousedown', function(e) {
        isDragging = true;
        updateVolume(e);
      });
      document.addEventListener('mousemove', function(e) {
        if (isDragging) {
          updateVolume(e);
        }
      });
      document.addEventListener('mouseup', function() {
        if (isDragging) {
          isDragging = false;
          const volume = parseInt(volumePercentage.textContent.replace('%', ''), 10);
          set_Volume_Data(volume);
        }
      });
      //Khởi tạo với mức âm lượng mặc định
      set_Volume_HTML(<?php echo $Config['smart_config']['speaker']['volume']; ?>);
    }

    //Xử lý led
    function setupBrightnessControl() {
      function updateBrightnessFromEvent(e) {
        const brightnessSlider = document.getElementById('led_brightness-slider');
        const rect = brightnessSlider.getBoundingClientRect();
        const height = rect.height;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        const offsetY = clientY - rect.top;
        const clampedOffsetY = Math.max(0, Math.min(height, offsetY));
        const percentage = Math.round(((height - clampedOffsetY) / height) * 100);
        updateBrightness(percentage);
        return percentage;
      }
      const brightnessSlider = document.getElementById('led_brightness-slider');
      let isDragging = false;
      //Khi nhấn chuột
      brightnessSlider.addEventListener('mousedown', function(e) {
        isDragging = true;
        updateBrightnessFromEvent(e);
      });
      document.addEventListener('mousemove', function(e) {
        if (isDragging) {
          //Chỉ cập nhật UI
          updateBrightnessFromEvent(e);
        }
      });
      //Khi nhả chuột
      document.addEventListener('mouseup', function(e) {
        if (isDragging) {
          isDragging = false;
          const brightnessValue = updateBrightnessFromEvent(e);
          sendBrightnessData(brightnessValue);
        }
      });
      //cập nhật hiển thị giá trị led mặc định lần đầu khi tải trang
      updateBrightness(<?php echo round($Config['smart_config']['led']['brightness'] * 100 / 255); ?>);
    }
  </script>
  <script>
	//Kiểm tra phiên bản Bluetooth hoặc AirPlay
	function check_version(type) {
		fetch("includes/php_ajax/Scanner.php?check_version=" + encodeURIComponent(type), {
			cache: "no-store"
		})
		.then(function(response) {
			if (!response.ok) {
				throw new Error("Lỗi HTTP: " + response.status);
			}
			return response.json();
		})
		.then(function(data) {
			if (data.success) {
				if (type === "bluetooth") {
					document.getElementById("version_bluetooth").textContent = (data.version || "").split("-")[0];
				} else if (type === "airplay") {
					const parts = data.version.split("-");
					document.getElementById("version_airplay").textContent = parts.length >= 2 ? parts.slice(0, 2).join("-") : data.version;
				}
			} else {
				const message = data.message || "";
				if (message.includes("bluealsad") && message.includes("command not found")) {
					return;
				}
				show_message(message);
			}
		})
		.catch(function(error) {
			show_message("Không thể kiểm tra phiên bản: " + type + ", " +error);
		});
	}

    //Lấy thông tin mạng đang kết nối
    function getWifiNetworkInformation() {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', 'includes/php_ajax/Wifi_Act.php?Wifi_Network_Information', true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          if (xhr.status === 200) {
            try {
              var response = JSON.parse(xhr.responseText);
              if (response.success) {
                document.getElementById('show_wifi_name').textContent = response.data.ESSID;
                document.getElementById('show_bit_rate').textContent = response.data.Bit_Rate;
                document.getElementById('show_frequency').textContent = response.data.Frequency;
                document.getElementById('show_Tx_Power').textContent = response.data.Tx_Power;
                document.getElementById('show_Link_Quality').textContent = response.data.Link_Quality;
              } else {
                show_message('Lỗi:' + response.message);
              }
            } catch (e) {
              show_message('Lỗi phân tích JSON 1:' + e);
            }
          } else {
            show_message('Lỗi khi gửi yêu cầu:' + xhr.statusText);
          }
        }
      };
      xhr.onerror = function() {
        show_message('Lỗi khi gửi yêu cầu thông tin wifi');
      };
      xhr.send();
    }
  </script>
  <script>
    //Lắng nghe và thực hiện khi có thay đổi trong Dom khi tải trang xong
	//Touch Kéo Slide volume trên Mobile cảm ứng
    document.addEventListener('DOMContentLoaded', function() {
      const volumeSlider_mb = document.getElementById('volume-slider');
      function handleTouch_volume(e) {
        const touch_vl = e.touches[0];
        updateVolume(touch_vl);
      }
      volumeSlider_mb.addEventListener('touchstart', handleTouch_volume);
      volumeSlider_mb.addEventListener('touchmove', handleTouch_volume);
      volumeSlider_mb.addEventListener('touchend', function(e) {
        const touch_vl = e.changedTouches[0];
        const rect_vl = volumeSlider_mb.getBoundingClientRect();
        const offsetY_vl = touch_vl.clientY - rect_vl.top;
        const height = rect_vl.height;
        const percentage = Math.max(0, Math.min(100, ((height - offsetY_vl) / height) * 100));
        // Cập nhật âm lượng và gửi dữ liệu khi nhả tay ra
        set_Volume_Data(Math.round(percentage));
      });
      //end Touch Kéo Slide volume trên Mobile cảm ứng

      //Touch Kéo Slide độ sáng trên Mobile cảm ứng
      const brightnessSlider_mb = document.getElementById('led_brightness-slider');
      function handleTouch_bright(e) {
        const rect = brightnessSlider_mb.getBoundingClientRect();
        const touch = e.touches[0];
        const offsetY = touch.clientY - rect.top;
        const height = rect.height;
        const clampedOffsetY = Math.max(0, Math.min(height, offsetY));
        const percentage = Math.round(((height - clampedOffsetY) / height) * 100);
        updateBrightness(percentage);
      }
      function handleTouchEnd_bright(e) {
        const rect = brightnessSlider_mb.getBoundingClientRect();
        const touch = e.changedTouches[0];
        const offsetY = touch.clientY - rect.top;
        const height = rect.height;
        const clampedOffsetY = Math.max(0, Math.min(height, offsetY));
        const percentage = Math.round(((height - clampedOffsetY) / height) * 100);
        updateBrightness(percentage);
        sendBrightnessData(percentage);
      }
      brightnessSlider_mb.addEventListener('touchstart', handleTouch_bright);
      brightnessSlider_mb.addEventListener('touchmove', handleTouch_bright);
      brightnessSlider_mb.addEventListener('touchend', handleTouchEnd_bright);
      //End Touch Kéo Slide độ sáng trên Mobile cảm ứng
      //Thay đổi và cập nhật volume khi trượt thanh slide html, click chuột
      setupVolumeControl();
      //Thay đổi và cập nhật độ sáng khi trượt thanh slide html, click chuột
      setupBrightnessControl();
    });
  </script>
  <script>
    //Hiển thị playlist dưới dạng table và tìm kiếm tab index.php
    function loadPlayList() {
      loading('show');
      var tableContainer = document.getElementById('tableContainer');
      var tableHTML =
        '<h5 class="card-title">PlayList, Danh Sách Nhạc: ' +
		' <button type="button" id="play_Button" name="play_Button" title="Phát nhạc trong Play List" class="btn btn-primary btn-sm" onclick="playlist_media_control()"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button>' +
		' <button type="button" class="btn btn-warning btn-sm" title="Tải Xuống Danh Sách Nhạc" onclick="downloadFile(\'<?php echo $HTML_VBot_Offline.'/includes/cache/PlayList.json'; ?>\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-download"></i></button> ' +
		' <button class="btn btn-danger btn-sm" title="Xóa toàn bộ danh sách phát" onclick="deleteFromPlaylist(\'delete_all\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-trash"></i></button> ' +
		'</h5>' +
		'<div class="input-group"><span class="input-group-text border-success">Tải Lên PlayList.json</span><input type="file" class="form-control border-success" id="fileInput_PlayList" accept=".json"><button class="btn btn-primary border-success" type="button" onclick="uploadFile_PlayList(\'index.php\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-upload"></i> Tải Lên</button></div>' +
		'<table class="table table-borderless datatable" id="playlistTable">' +
        '<thead>' +
        '<tr>' +
        '<th scope="col" style="text-align: center; vertical-align: middle;">STT</th>' +
        '<th scope="col" style="text-align: center; vertical-align: middle;">Bài Hát</th>' +
        '<th scope="col" style="text-align: center; vertical-align: middle;">Hành Động</th>' +
        '</tr>' +
        '</thead>' +
        '<tbody id="playlistTableBody">' +
        '<!-- Dữ liệu sẽ được thêm vào đây bởi JavaScript -->' +
        '</tbody>' +
        '</table>';
      tableContainer.innerHTML = tableHTML;
      var table = document.getElementById('playlistTable');
      var tableBody = document.getElementById('playlistTableBody');
      var xhr = new XMLHttpRequest();
      xhr.open('GET', 'includes/php_ajax/Media_Player_Search.php?Cache_PlayList', true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          var data = JSON.parse(xhr.responseText);
          var fileInfo = '';
          // Kiểm tra dữ liệu
          if (data.data && Array.isArray(data.data)) {
            data.data.forEach(function(playlist, index) {
              var description = playlist.description ? (playlist.description.length > 70 ? playlist.description.substring(0, 70) + '...' : playlist.description) : 'N/A';
              // Tạo thông tin cho mỗi playlist dựa trên nguồn
              fileInfo +=
                '<tr>' +
                '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>' +
                '<td>' +
                '<div style="display: flex; align-items: center; margin-bottom: 10px;">' +
                '<div style="flex-shrink: 0; margin-right: 15px;">' +
                '<img src="' + playlist.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;">' +
                '</div>' +
                '<div>' +
                '<p style="margin: 0; font-weight: bold;">Tên bài hát: <font color="green">' + playlist.title + '</font></p>' +
                (playlist.source === 'Youtube' ?
                  '<p style="margin: 0;">Kênh: <font color="green">' + playlist.channelTitle + '</font></p>' +
                  '<p style="margin: 0;">Thời Lượng: <font color="green">' + playlist.duration + '</font></p>' +
                  '<p style="margin: 0;">Mô tả: <font color="green">' + description + '</font></p>' : '') +
                (playlist.source === 'ZingMP3' ?
                  '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color="green">' + playlist.artist + '</font></p>' +
                  '<p style="margin: 0;">Thời Lượng: <font color="green">' + (playlist.duration || 'N/A') + '</font></p>' : '') +
                (playlist.source === 'PodCast' ?
                  '<p style="margin: 0;">Thể Loại: <font color="green">' + description + '</font></p>' : '') +
                (playlist.source === 'Local' ?
                  '<p style="margin: 0;">Kích Thước: <font color="green">' + playlist.duration + '</font></p>' : '') +
                '<p style="margin: 0;">Nguồn Nhạc: <font color="green">' + playlist.source + '</font></p>' +
                '</div>' +
                '</div>' +
                '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                (playlist.source === 'Youtube' ?
                  '<button class="btn btn-success btn-sm" title="Phát: ' + playlist.title + '" onclick="get_Youtube_Link(\'' + playlist.id + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\')"><i class="bi bi-play-circle"></i></button>' +
                  '<a href="https://www.youtube.com/watch?v=' + playlist.id + '" target="_blank">' +
                  '<button class="btn btn-info btn-sm" title="Mở trong tab mới: ' + playlist.title + '"><i class="bi bi-box-arrow-up-right"></i></button>' +
                  '</a>' : '') +
                (playlist.source === 'ZingMP3' ?
                  '<button class="btn btn-success btn-sm" title="Phát: ' + playlist.title + '" onclick="get_ZingMP3_Link(\'' + playlist.id + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'' + playlist.artist + '\')"><i class="bi bi-play-circle"></i></button>' : '') +
                (playlist.source === 'PodCast' ?
                  '<button class="btn btn-success btn-sm" title="Phát: ' + playlist.title + '" onclick="send_Media_Play_API(\'' + playlist.audio + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'PodCast\')"><i class="bi bi-play-circle"></i></button>' +
                  '<a href="' + playlist.audio + '" target="_blank"> ' +
                  '<button class="btn btn-info btn-sm" title="Mở trong tab mới: ' + playlist.title + '"><i class="bi bi-box-arrow-up-right"></i></button>' +
                  '</a>' : '') +
                (playlist.source === 'NhacCuaTui' ?
                  '<button class="btn btn-success btn-sm" title="Phát: ' + playlist.title + '" onclick="send_Media_Play_API(\'' + playlist.audio + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'PodCast\')"><i class="bi bi-play-circle"></i></button>' +
                  '<a href="' + playlist.audio + '" target="_blank"> ' +
                  '<button class="btn btn-info btn-sm" title="Mở trong tab mới: ' + playlist.title + '"><i class="bi bi-box-arrow-up-right"></i></button>' +
                  '</a>' : '') +
                (playlist.source === 'Local' ?
                  ' <button class="btn btn-success btn-sm" title="Phát: ' + playlist.title + '" onclick="send_Media_Play_API(\'' + playlist.audio + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'Local\')"><i class="bi bi-play-circle"></i></button>' : '') +
                ' <button class="btn btn-danger btn-sm" title="Xóa khỏi danh sách phát: ' + playlist.title + '" onclick="deleteFromPlaylist(\'delete_some\', \'' + playlist.ids_list + '\')"><i class="bi bi-trash"></i></button>' +
                '</td>' +
                '</tr>';
            });
            showMessagePHP("Lấy dữ liệu PlayList, danh sách phát thành công", 5);
          } else {
            fileInfo = '<tr><td colspan="3">Không có dữ liệu</td></tr>';
          }
          tableBody.innerHTML = fileInfo;
          try {
            new simpleDatatables.DataTable(table, {
              perPageSelect: [5, 10, 15, ['All', -1]],
              perPage: 5,
              columns: [{
                  select: 0,
                  sortSequence: ['asc', 'desc']
                },
                {
                  select: 1,
                  sortSequence: ['asc', 'desc']
				}
              ]
            });
          } catch (e) {
            show_message('Lỗi khi khởi tạo DataTable: ' + e.message);
          }
          loading('hide');
        } else {
          loading('hide');
          show_message('Lỗi khi lấy dữ liệu: ' + xhr.statusText);
          tableBody.innerHTML = '<tr><td colspan="3">Lỗi khi tải dữ liệu</td></tr>';
        }
      };
      xhr.onerror = function() {
        loading('hide');
        show_message('Lỗi khi thực hiện yêu cầu');
        tableBody.innerHTML = '<tr><td colspan="3">Lỗi khi thực hiện yêu cầu, tải dữ liệu</td></tr>';
      };
      xhr.send();
    }
  </script>
  <script>
	//Select Nguồn nhạc
	const selectEl = document.getElementById('select_cache_media');
	const newsEl = document.getElementById('NewsPaper_Select');
	const tableEl = document.getElementById('tableContainer');
	const actions = {
	  Local: () => media_player_search('Local'),
	  Youtube: cacheYoutube,
	  ZingMP3: cacheZingMP3,
	  NhacCuaTui: cacheNhacCuaTui,
	  PodCast: cachePodCast,
	  Radio: () => media_player_search('Radio'),
	  NewsPaper: cache_NewsPaper,
	  Link_URL: cache_Link_URL,
	  PlayList_List: loadPlayList
	};
	selectEl?.addEventListener('change', () => {
	  const value = selectEl.value;
	  tableEl.style.display = '';
	  newsEl.style.display = (value === 'NewsPaper') ? '' : 'none';
	  actions[value]?.();
	});

    //Phát Thông báo tts tới loa được chọn (điền dữ liệu vào thẻ select)
    function fetchAndPopulateDevices_tts() {
      const selectElement = document.getElementById('source_text_to_speak_api');
      if (!selectElement) {
        return;
      }
      const url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $directory_path . "/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json"; ?>';;
      const xhr = new XMLHttpRequest();
      xhr.open('GET', url, true);
      xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const data = JSON.parse(xhr.responseText);
            if (!data.success) {
              return;
            }
            if (!data.data || !Array.isArray(data.data)) {
              return;
            }
            const serverIp = '<?php echo $serverIp; ?>';
            while (selectElement.options.length > 1) {
              selectElement.remove(1);
            }
            data.data.forEach(device => {
              if (device.ip_address !== serverIp) {
                const option = document.createElement('option');
                option.value = 'http://' + device.ip_address + ':' + device.port_api + '/';
                option.text = device.user_name;
                option.setAttribute('data-full_name_tts_api', device.user_name);
                selectElement.appendChild(option);
              }
            });
            if (<?php echo $Config['home_assistant']['active'] ? 'true' : 'false'; ?>) {
              const option = document.createElement('option');
              option.value = 'send_notify_home_assistant';
              option.text = 'Home Assistant (HASS)';
              option.setAttribute('data-full_name_tts_api', "VBot - <?php echo $Config['contact_info']['full_name']; ?>");
              selectElement.appendChild(option);
            }
          } catch (e) {
            showMessagePHP('Lỗi phát tts: Không thể phân tích JSON - ' + e.message, 5);
          }
        } else {
          showMessagePHP('Lỗi phát tts: lấy dữ liệu các Loa VBot trong cùng lớp mạng: HTTP status ' + xhr.status, 5);
        }
      };
      xhr.onerror = function() {
        showMessagePHP('Lỗi phát tts: khi gửi yêu cầu lấy dữ liệu các loa chạy VBot trong cùng lớp mạng', 5);
      };
      xhr.send();
    }
  </script>
  <script>
    //Hiệu Ứng Sóng Nhạc Khi Phát Media Player
    let currentStatus_SongNHAC = false;
    let previousStatus_SongNHAC = null;
    const canvas_SN = document.getElementById("waveCanvas_songNhac");
    const ctx = canvas_SN.getContext("2d");

    function resizeCanvas_SN() {
      const container = document.getElementById("waveContainer_song_nhac");
      canvas_SN.width = container.clientWidth || window.innerWidth;
      canvas_SN.height = 70;
    }
    window.addEventListener("resize", resizeCanvas_SN);
    resizeCanvas_SN();
    let time_SongNhac = 0;

    function drawWaves() {
      resizeCanvas_SN();
      const width = canvas_SN.width;
      const height = canvas_SN.height;
      ctx.clearRect(0, 0, width, height);
      if (currentStatus_SongNHAC) {
        document.getElementById("waveContainer_song_nhac").style.display = "flex";
        let bassPulse_SN = Math.sin(time_SongNhac * 0.5) * 20 + 20;
        //Sóng 1
        ctx.beginPath();
        const gradient1 = ctx.createLinearGradient(0, 0, 0, height);
        gradient1.addColorStop(0, '#00f7ff');
        gradient1.addColorStop(1, '#8a2be2');
        ctx.strokeStyle = gradient1;
        ctx.lineWidth = 2;
        for (let x = 0; x < width; x++) {
          const amplitude = Math.min(15 + bassPulse_SN, 30);
          const y = height / 2 + Math.sin(x * 0.02 + time_SongNhac) * amplitude * Math.sin(time_SongNhac * 0.3);
          if (x === 0) ctx.moveTo(x, y);
          else ctx.lineTo(x, y);
        }
        ctx.stroke();
        //Sóng 2
        ctx.beginPath();
        const gradient2 = ctx.createLinearGradient(0, 0, 0, height);
        gradient2.addColorStop(0, '#ff00cc');
        gradient2.addColorStop(1, '#ff4500');
        ctx.strokeStyle = gradient2;
        ctx.lineWidth = 2;
        for (let x = 0; x < width; x++) {
          const amplitude = Math.min(10 + bassPulse_SN, 30);
          const y = height / 2 + Math.cos(x * 0.015 + time_SongNhac * 1.2) * amplitude * Math.cos(time_SongNhac * 0.4);
          if (x === 0) ctx.moveTo(x, y);
          else ctx.lineTo(x, y);
        }
        ctx.stroke();
        time_SongNhac += 0.05;
      } else {
        document.getElementById("waveContainer_song_nhac").style.display = "none";
      }
      requestAnimationFrame(drawWaves);
    }

    function updateDisplay_SongNhac(status_SN) {
      if (status_SN === previousStatus_SongNHAC) return;
      previousStatus_SongNHAC = status_SN;
      currentStatus_SongNHAC = status_SN;
    }

    //Khởi động vòng vẽ sóng và list thiết bị dùng cho tts
    window.addEventListener("DOMContentLoaded", () => {
      drawWaves();
      fetchAndPopulateDevices_tts();
    });
    //Bắt sự kiện nhấn Enter khi nhập liệu tìm kiếm bài hát
    document.addEventListener("keypress", function(e) {
      if (e.key === "Enter" && e.target && e.target.id === "song_name_value") {
        e.preventDefault();
        let selectEl = document.getElementById("select_cache_media");
        let source = selectEl ? selectEl.value : "";
        if (source && source !== "") {
          media_player_search(source);
        } else {
          show_message('Vui lòng chọn nguồn nhạc trước khi tìm kiếm!');
          //selectEl.focus();
        }
      }
    });
  </script>
  
  <script>
	document.addEventListener("DOMContentLoaded", function () {
		//Gọi hàm để hiển thị thông tin vị trí và thời tiết
		getLocationAndWeather();

		//Thông tin Wifi
		getWifiNetworkInformation();
		
		//Thông tin phiên bản bluetooth và airplay
		check_version("bluetooth");
		check_version("airplay");
	});
  </script>
</body>

</html>