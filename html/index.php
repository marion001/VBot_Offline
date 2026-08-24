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

    #tableContainer:not(:empty) {
      width: 100%;
      max-width: 100%;
      margin-top: 12px;
      padding: 16px;
      border: 1px solid rgba(13, 110, 253, 0.28);
      border-radius: 12px;
      background: rgba(248, 249, 250, 0.82);
      box-shadow: 0 2px 10px rgba(33, 37, 41, 0.06);
    }

    .playlist-control-panel {
      padding: 12px;
      margin-bottom: 10px;
      border: 1px solid #dee2e6;
      border-radius: 10px;
      background: #fff;
    }

    .playlist-control-panel-primary {
      border-color: rgba(13, 110, 253, 0.35);
      background: rgba(13, 110, 253, 0.035);
    }

    .playlist-control-panel-info {
      border-color: rgba(13, 202, 240, 0.42);
      background: rgba(13, 202, 240, 0.04);
    }

    .playlist-control-panel-success {
      border-color: rgba(25, 135, 84, 0.35);
      background: rgba(25, 135, 84, 0.035);
    }

    .playlist-control-label {
      display: block;
      margin-bottom: 8px;
      color: #495057;
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.025em;
    }

    .playlist-action-cell > .btn,
    .playlist-action-cell > a {
      display: inline-block;
      margin: 4px;
      vertical-align: middle;
    }

    .playlist-action-cell > a > .btn {
      margin: 0;
    }

    /* Giữ khoảng cách rõ ràng khi các nút Playlist tự xuống dòng. */
    #tableContainer .playlist-control-panel .btn-group.flex-wrap {
      gap: 8px;
    }

    #tableContainer .playlist-control-panel .btn-group.flex-wrap > .btn {
      margin: 0;
      border-radius: 0.375rem !important;
    }

    @media (max-width: 991.98px) {
      #tableContainer .playlist-control-panel .btn {
        margin: 4px;
        border-radius: 0.375rem !important;
      }

      #tableContainer .playlist-control-panel .btn-group.flex-wrap {
        padding: 2px 0;
      }

      #tableContainer .playlist-control-panel .input-group {
        align-items: center;
        row-gap: 6px;
      }
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
                    <?php if (!empty($Config['media_player']['multiroom_audio']['active'])): ?>
                    <div class="ms-2 d-flex flex-column align-items-start gap-1">
                      <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#multiroomModal" onclick="multiroomOpen()"><i class="bi bi-speaker"></i>Điều Khiển Multiroom Audio</button>
                      <p id="media-multiroom-connection" class="mb-0 small"><span class="badge bg-danger text-white"><i class="bi bi-speaker"></i>Multiroom Audio: Không kết nối</span></p>
                    </div>
                    <?php endif; ?>
                    <label>Trình Phát Đa Phương Tiện - Media Player</label>
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
                  <div class="d-flex flex-column align-items-center gap-2">
                    <div class="d-flex flex-row align-items-center justify-content-center gap-2" aria-label="Các nút điều khiển media">
                      <div class="btn-group" role="group" aria-label="Điều khiển phát nhạc">
                        <button type="button" id="play_Button" title="Phát nhạc" class="btn btn-success" onclick="control_media('resume')"><i class="bi bi-play-circle"></i></button>
                        <button type="button" id="pause_Button" title="Tạm dừng phát nhạc" class="btn btn-warning" onclick="control_media('pause')"><i class="bi bi-pause-circle"></i></button>
                        <button type="button" id="stop_Button" title="Dừng phát nhạc" class="btn btn-danger" onclick="control_media('stop')"><i class="bi bi-stop-circle"></i></button>
                      </div>
                      <div class="btn-group" role="group" aria-label="Chuyển bài hát">
                        <button type="button" id="media_prev_Button" title="Chuyển bài trước theo nguồn đang phát" class="btn btn-outline-primary" onclick="control_media('previous')"><i class="bi bi-skip-backward-fill"></i></button>
                        <button type="button" id="media_next_Button" title="Chuyển bài kế tiếp theo nguồn đang phát" class="btn btn-outline-primary" onclick="control_media('next')"><i class="bi bi-skip-forward-fill"></i></button>
                      </div>
                    </div>
                    <button type="button" id="playlist_play_Button" title="Phát Playlist mặc định" class="btn btn-primary btn-sm rounded-pill" onclick="playlist_media_control()"><i class="bi bi-music-note-list"></i> Phát Playlist mặc định</button>
                  </div>
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
			  
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-speaker"></i> Multiroom Audio > <i class="bi bi-patch-question-fill" onclick="show_message('Phát âm thanh đa vùng trên các loa chạy VBot trong cùng lớp mạng nội bộ, Lan Local')"></i>:</span> </h5>
              <div class="activity">
                <div class="activity-item d-flex">
                  <div class="form-switch">
					<input class="form-check-input border-danger"disabled type="checkbox" name="multiroom_audio_active" id="multiroom_audio_active" <?php echo $Config['media_player']['multiroom_audio']['active'] ? 'checked' : ''; ?>>
                  </div>
                  <i class="bi bi-dash-lg"></i>
                  <div class="activity-content">
                    <b>
                      <font color="red"> Kích Hoạt </font>
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
              <div id="systemModeOptions" class="echart">
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
              <div id="systemLogsOptions" class="echart">
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
  <div class="modal fade" id="multiroomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">

<div class="modal-header d-flex align-items-center">
  <h5 class="modal-title mb-0">
    <i class="bi bi-speaker"></i> Multiroom Audio (Âm thanh Đa Vùng)
  </h5>

  <div id="mr-room-connection" class="d-flex align-items-center ms-2">
    <i class="bi bi-broadcast me-1"></i>
    <span>Multiroom không kết nối</span>
  </div>

  <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
</div>

      <div class="modal-body">
        
        <div id="multiroom-ui-message" class="alert d-none"></div>
        <div class="row g-3">
          <!-- Phần Quản Lý Nhóm Loa -->
          <div class="col-lg-5">
            <!-- Card Quét Loa -->
            <div class="card border-secondary mb-3"><div class="card-body"><h6 class="card-title"><i class="bi bi-search"></i> Quét Thiết Bị Trong Mạng Lan</h6>
              <button class="btn btn-secondary btn-sm w-100 mb-2" onclick="multiroomRefresh(true, true)"><i class="bi bi-search"></i> Quét Lại Thiết Bị</button>
              <div id="mr-devices" class="small border rounded p-2" style="max-height:200px;overflow-y:auto;background:#f8f9fa;"></div>
            </div></div>

            <!-- Card Quản Lý Nhóm -->
            <div class="card border-primary"><div class="card-body"><h6 class="card-title"><i class="bi bi-collection"></i> Quản Lý Nhóm Loa</h6>
              <div id="mr-group-vbot-required" class="alert alert-warning py-2">Chỉ có thể quản lý nhóm loa khi chương trình VBot đang hoạt động.</div>
              <div class="alert alert-info py-2 small"><i class="bi bi-info-circle"></i> Khuyến nghị mỗi nhóm nên có dưới 8 thiết bị để duy trì kết nối và đồng bộ âm thanh ổn định.</div>
              <!-- Chọn Group -->
              <label class="form-label mb-2"><small>Chọn Nhóm Loa Hiện Có:</small></label>
              <select id="mr-group-select" class="form-select mb-3 border-success" onchange="multiroomSelectGroup()"></select>

              <div class="border-top pt-3 mt-3">
                <div class="d-flex gap-2 flex-wrap">
                  <button id="mr-group-create" class="btn btn-outline-success btn-sm flex-grow-1" onclick="multiroomGroupMode('create')" disabled><i class="bi bi-plus-circle"></i> Tạo Nhóm</button>
                  <button id="mr-group-edit" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="multiroomGroupMode('edit')" disabled><i class="bi bi-pencil"></i> Sửa</button>
                  <button id="mr-group-delete" class="btn btn-outline-danger btn-sm flex-grow-1" onclick="multiroomGroupMode('delete')" disabled><i class="bi bi-trash"></i> Xóa</button>
                </div>

                <div id="mr-group-editor" class="border rounded p-3 mt-3 d-none bg-light">
                  <h6 id="mr-group-editor-title" class="mb-3 text-center"></h6>
                  <input type="hidden" id="mr-group-id">
                  <label class="form-label"><small>Tên nhóm:</small></label>
                  <input id="mr-group-name" class="form-control mb-3 border-success" placeholder="Nhập tên nhóm loa cần tạo" title="Tên nhóm loa">
                  <label class="form-label"><small id="mr-group-members-label">Chọn loa tham gia nhóm:</small></label>
                  <div id="mr-group-members" class="border rounded p-2 mb-3 bg-white" style="max-height:220px;overflow-y:auto;"></div>
                  <div id="mr-group-delete-warning" class="alert alert-danger py-2 d-none">Nhóm sẽ bị xóa khỏi cấu hình. Các loa và dịch vụ Multiroom không bị gỡ.</div>
                  <div class="d-flex gap-2">
                    <button id="mr-group-confirm" class="btn btn-primary btn-sm flex-grow-1" onclick="multiroomGroupConfirm()" disabled><i class="bi bi-check-circle"></i> Xác Nhận</button>
                    <button class="btn btn-secondary btn-sm" onclick="multiroomGroupMode('cancel')"><i class="bi bi-x-circle"></i> Hủy</button>
                  </div>
                </div>
              </div>
            </div></div>
          </div>

          <!-- Phần Điều Khiển Phiên Phát -->
          <div class="col-lg-7">
            <!-- Card Bắt Đầu Phát -->
            <div class="card border-success mb-3"><div class="card-body"><h6 class="card-title"><i class="bi bi-play-circle"></i> Lựa Chọn Nhóm Để Phát Âm Thanh</h6>
              <div class="input-group mb-2">
                <select id="mr-session-group-select" class="form-select border-success" title="Chọn nhóm loa để phát âm thanh đa vùng"></select>
                <button class="btn btn-success border-success" onclick="multiroomSession('start')" title="Phát nhóm đã chọn"><i class="bi bi-play-fill"></i> Kết Nối</button>
              </div>
              <button class="btn btn-danger w-100 btn-sm" onclick="multiroomSession('local')" title="Dừng phát group, chuyển về loa local"><i class="bi bi-stop-circle"></i> Dừng kết nối & Phát Về Loa Chủ</button>
            </div></div>

            <!-- Card Quản Lý Loa Trong Phiên Phát -->
            <div id="mr-active-session-card" class="card border-warning mb-3"><div class="card-body"><h6 class="card-title"><i class="bi bi-sliders"></i> Quản Lý Loa Đang Phát Trong Nhóm</h6>
              <div id="mr-no-active-session" class="alert alert-secondary mb-0">Không có dữ liệu hiển thị do chưa kết nối với các nhóm loa để phát âm thanh đa vùng.</div>
              <div id="mr-active-session-controls" class="d-none">
                <div id="mr-session" class="small alert alert-info mb-2"></div>
                <div class="border rounded p-2 mb-3 bg-light">
                  <div class="d-flex justify-content-between"><small><b>Âm lượng tổng các loa VBot đang kết nối</b></small><small id="mr-master-volume-value" class="text-primary">0%</small></div>
                  <input type="range" id="mr-master-volume" class="form-range" min="0" max="100" value="0"
                    title="Thay đổi Lib.Volume đồng thời trên toàn bộ loa trong phiên"
                    onpointerdown="multiroomMasterVolumeDragging=true"
                    onpointercancel="multiroomMasterVolumeDragging=false"
                    oninput="multiroomMasterVolumePreview(this.value)"
                    onchange="multiroomMasterVolumeCommit(this.value)">
                  <small class="text-success">Đây là Volume tổng của các loa VBot, khi được thay đổi ở đây toàn bộ âm lượng tổng của các loa VBot có trong nhóm này đều có giá trị giống nhau</small>
                </div>
                <div id="mr-speakers" class="row g-2 mb-3" style="max-height:300px;overflow-y:auto;"></div>
                <button class="btn btn-primary w-100 btn-sm" onclick="multiroomApplySpeakerChanges()" title="Áp dụng những thay đổi được chọn"><i class="bi bi-check-circle"></i> Áp Dụng Thay Đổi</button>
              </div>
            </div></div>

          </div>
        </div>
      </div><div class="modal-footer"><button class="btn btn-danger" data-bs-dismiss="modal">Đóng</button></div>
    </div></div>
  </div>

  <div class="modal fade" id="playlistAddTargetModal" tabindex="-1" aria-labelledby="playlistAddTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="playlistAddTargetModalLabel"><i class="bi bi-music-note-list"></i> Thêm Bài Hát Vào Playlist</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-primary py-2" id="playlist-add-song-title">Đang chọn bài hát...</div>
          <label for="playlist-add-target-select" class="form-label fw-bold">Chọn playlist nhận bài hát:</label>
          <select id="playlist-add-target-select" class="form-select border-success mb-3"></select>
          <button type="button" id="playlist-add-new-toggle" class="btn btn-outline-success btn-sm" onclick="playlistToggleCreateNew(true)">
            <i class="bi bi-plus-circle"></i> Thêm Vào Playlist Mới
          </button>
          <div id="playlist-add-new-fields" class="mt-3 d-none">
            <label for="playlist-add-new-name" class="form-label fw-bold">Tên playlist mới:</label>
            <div class="input-group">
              <input type="text" maxlength="80" id="playlist-add-new-name" class="form-control border-success" placeholder="Ví dụ: Nhạc thư giãn">
              <button type="button" class="btn btn-outline-secondary" onclick="playlistToggleCreateNew(false)">Dùng Playlist Có Sẵn</button>
            </div>
          </div>
          <div id="playlist-add-target-message" class="alert d-none mt-3 mb-0"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="button" id="playlist-add-confirm" class="btn btn-success" onclick="confirmAddToPlaylist()">
            <i class="bi bi-plus-circle-fill"></i> Xác Nhận Thêm
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="playlistManagerDialog" tabindex="-1" aria-labelledby="playlistManagerDialogLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="playlistManagerDialogLabel"><i class="bi bi-music-note-list"></i> Quản Lý PlayList</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <div id="playlist-manager-dialog-description" class="alert alert-info py-2"></div>
          <div id="playlist-manager-name-group">
            <label for="playlist-manager-name-input" class="form-label fw-bold" id="playlist-manager-name-label">Tên PlayList:</label>
            <input type="text" maxlength="80" class="form-control border-primary" id="playlist-manager-name-input" autocomplete="off" onkeydown="if(event.key==='Enter'){event.preventDefault();playlistManagerDialogSubmit();}">
            <div class="invalid-feedback" id="playlist-manager-name-error">Tên PlayList phải từ 1 đến 80 ký tự.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="button" class="btn btn-primary" id="playlist-manager-dialog-confirm" onclick="playlistManagerDialogSubmit()"><i class="bi bi-check-circle"></i> Xác Nhận</button>
        </div>
      </div>
    </div>
  </div>
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
    const multiroomApiUrl = <?php echo json_encode(rtrim($URL_API_VBOT, '/') . '/multiroom'); ?>;
    let multiroomSnapshot = {};
    let multiroomPendingSpeakerChanges = {}; // Track checkbox changes
    let multiroomEventSource = null;
    let multiroomSSEReconnectTimer = null;
    let multiroomSSEWasDisconnected = false;
    let multiroomDiscoveryTimer = null;
    const multiroomDeviceLastSeen = {};
    const multiroomDeviceOfflineAfterMs = 45000;
    const multiroomVolumeHovered = new Set();
    const multiroomVolumeDragging = new Set();
    const multiroomVolumeDrafts = {};
    let multiroomLoadingDepth = 0;
    let multiroomGroupEditorMode = null;
    let multiroomAPIConnected = false;
    let multiroomMasterVolumeDragging = false;
    let multiroomDisabledNoticeShown = false;
    const multiroomConnectionError = 'Không thể kết nối đến API (SSE), vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa.';
    const multiroomGroupRequiresVBot = 'Chỉ có thể quản lý nhóm loa khi chương trình VBot đang hoạt động.';
    const multiroomDisabledMessage = 'Multiroom Audio không được kích hoạt khi chương trình VBot khởi động, để kích hoạt hãy vào: Cấu hình Config -> Media Player -> Multiroom Audio để kích hoạt và khởi động lại chương trình VBot để áp dụng.';

    function setMultiroomAPIConnected(connected) {
      multiroomAPIConnected = connected === true;
      ['mr-group-create', 'mr-group-edit', 'mr-group-delete', 'mr-group-confirm'].forEach(id => {
        const button = document.getElementById(id);
        if(button) button.disabled = !multiroomAPIConnected;
      });
      const warning = document.getElementById('mr-group-vbot-required');
      if(warning) warning.classList.toggle('d-none', multiroomAPIConnected);
      if(!multiroomAPIConnected) updateMultiroomConnectionIndicators(false);
    }

    function updateMultiroomConnectionIndicators(connected) {
      const isConnected = connected === true;
      const text = isConnected ? 'Đang kết nối' : 'Không kết nối';
      const modalStatus = document.getElementById('mr-room-connection');
      if(modalStatus) {
		modalStatus.innerHTML = '<i class="bi bi-broadcast me-1"></i> ' + text;
		modalStatus.className = 'd-flex align-items-center ms-2 ' + (isConnected ? 'text-success' : 'text-secondary');
      }
      const mediaStatus = document.getElementById('media-multiroom-connection');
      if(mediaStatus) {
        mediaStatus.innerHTML = '<span class="text-white badge ' +
          (isConnected ? 'bg-success' : 'bg-secondary') + '"><i class="bi bi-speaker"></i>Multiroom Audio: ' + text + '</span>';
      }
    }

    function multiroomLoadingStart() {
      multiroomLoadingDepth += 1;
      if(multiroomLoadingDepth === 1) loading("show");
    }

    function multiroomLoadingEnd() {
      multiroomLoadingDepth = Math.max(0, multiroomLoadingDepth - 1);
      if(multiroomLoadingDepth === 0) loading("hide");
    }

    function applyMultiroomSnapshot(nextSnapshot, discoveryCompleted=false) {
      nextSnapshot = nextSnapshot || {};
      const knownDevices = Array.isArray(multiroomSnapshot.devices) ? multiroomSnapshot.devices : [];
      const incomingDevices = Array.isArray(nextSnapshot.devices) ? nextSnapshot.devices : [];
      const now = Date.now();
      const mergedDevices = new Map(knownDevices.map(device => [String(device.id||'').toLowerCase(), {...device}]));
      incomingDevices.forEach(device => {
        const id = String(device.id||'').toLowerCase();
        if(!id) return;
        mergedDevices.set(id, {...(mergedDevices.get(id)||{}), ...device});
        if(discoveryCompleted) multiroomDeviceLastSeen[id] = now;
      });
      const runtimeSpeakers = (nextSnapshot.controller?.speakers || []);
      runtimeSpeakers.forEach(speaker => {
        const id = String(speaker.id||'').toLowerCase();
        if(!id) return;
        if(speaker.online === true) multiroomDeviceLastSeen[id] = now;
        if(!mergedDevices.has(id)) mergedDevices.set(id, {...speaker});
      });
      nextSnapshot.devices = Array.from(mergedDevices.entries()).map(([id, device]) => {
        const runtime = runtimeSpeakers.find(speaker => String(speaker.id||'').toLowerCase() === id);
        const lastSeen = Number(multiroomDeviceLastSeen[id] || 0);
        const online = runtime?.online === false
          ? false
          : (runtime?.online === true || (lastSeen > 0 && now - lastSeen <= multiroomDeviceOfflineAfterMs));
        return {...device, online:online, last_seen_ms:lastSeen || null};
      });
      multiroomSnapshot = nextSnapshot;
      setMultiroomAPIConnected(true);
      renderMultiroom();
      if(nextSnapshot.enabled === false && !multiroomDisabledNoticeShown) {
        multiroomDisabledNoticeShown = true;
        const message = document.getElementById('multiroom-ui-message');
        if(message) {
          message.textContent = multiroomDisabledMessage;
          message.className = 'alert alert-warning';
        }
      }
    }

    function stopMultiroomSSE() {
      if(multiroomSSEReconnectTimer) {
        clearTimeout(multiroomSSEReconnectTimer);
        multiroomSSEReconnectTimer = null;
      }
      if(multiroomEventSource) {
        multiroomEventSource.close();
        multiroomEventSource = null;
      }
    }

    function startMultiroomSSE() {
      stopMultiroomSSE();
      multiroomEventSource = new EventSource(multiroomApiUrl + '/events?interval=1');
      multiroomEventSource.addEventListener('update', event => {
        try {
          const data = JSON.parse(event.data);
          if(data.success && data.multiroom) applyMultiroomSnapshot(data.multiroom, false);
        } catch(error) {
          mrMessage('Dữ liệu SSE Multiroom không hợp lệ: ' + error.message, true);
        }
      });
      multiroomEventSource.addEventListener('error_status', event => {
        try { mrMessage(JSON.parse(event.data).message || 'Lỗi cập nhật Multiroom', true); }
        catch(_error) { mrMessage('Lỗi cập nhật Multiroom', true); }
      });
      multiroomEventSource.onopen = function () {
        setMultiroomAPIConnected(true);
        const message = document.getElementById('multiroom-ui-message');
        if(message && message.textContent === multiroomConnectionError) message.className = 'alert d-none';
        if(multiroomSSEWasDisconnected) {
          multiroomSSEWasDisconnected = false;
          // API vua song lai: quet mDNS va lay lai toan bo snapshot, khong chi
          // runtime SSE (runtime SSE co chu dich khong quet LAN lien tuc).
          multiroomRefresh(true);
        }
      };
      multiroomEventSource.onerror = function () {
        setMultiroomAPIConnected(false);
        mrMessage(multiroomConnectionError, true);
        multiroomSSEWasDisconnected = true;
        if(multiroomEventSource) {
          multiroomEventSource.close();
          multiroomEventSource = null;
        }
        if(!multiroomSSEReconnectTimer) {
          multiroomSSEReconnectTimer = setTimeout(function () {
            multiroomSSEReconnectTimer = null;
            if(document.getElementById('multiroomModal')?.classList.contains('show')) startMultiroomSSE();
          }, 1000);
        }
      };
    }

    function stopMultiroomDiscoveryMonitor() {
      if(multiroomDiscoveryTimer) {
        clearInterval(multiroomDiscoveryTimer);
        multiroomDiscoveryTimer = null;
      }
    }

    function startMultiroomDiscoveryMonitor() {
      stopMultiroomDiscoveryMonitor();
      multiroomDiscoveryTimer = setInterval(function () {
        if(document.getElementById('multiroomModal')?.classList.contains('show')) {
          multiroomRefresh(true, false, true);
        }
      }, 20000);
    }
    
    function mrMessage(text, error=false) { 
      const el=document.getElementById('multiroom-ui-message'); 
      if(!el)return; 
      el.textContent=text; 
      el.className='alert '+(error?'alert-danger':'alert-success'); 
    }

    function multiroomSuccess(text, timeout=5) {
      mrMessage(text, false);
      if(typeof showMessagePHP === 'function') showMessagePHP(text, timeout);
    }
    
    async function multiroomRequest(payload) {
      const response=await vbotFetchWithTimeout(multiroomApiUrl,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)},30000)
        .catch(() => { setMultiroomAPIConnected(false); throw new Error(multiroomConnectionError); });
      const data=await multiroomReadJson(response);
      if(!response.ok||!data.success) throw new Error(data.message||data.result?.error||'Thao tác Multiroom không thành công'); 
      setMultiroomAPIConnected(true);
      return data.result;
    }

    async function multiroomReadJson(response) {
      const contentType = String(response.headers.get('content-type') || '').toLowerCase();
      if(!contentType.includes('application/json')) {
        setMultiroomAPIConnected(false);
        throw new Error(multiroomConnectionError);
      }
      try {
        return await response.json();
      } catch(_error) {
        setMultiroomAPIConnected(false);
        throw new Error(multiroomConnectionError);
      }
    }
    
    function multiroomSelectGroup() { 
      const g=(multiroomSnapshot.groups||[]).find(x=>x.id===document.getElementById('mr-group-select').value); 
      if(!g)return;
      if(multiroomGroupEditorMode==='edit' || multiroomGroupEditorMode==='delete') {
        multiroomGroupMode(multiroomGroupEditorMode);
      }
    }

    function multiroomGroupMode(mode) {
      const editor = document.getElementById('mr-group-editor');
      if(mode === 'cancel') {
        multiroomGroupEditorMode = null;
        editor.classList.add('d-none');
        return;
      }
      if(!multiroomAPIConnected) {
        mrMessage(multiroomGroupRequiresVBot, true);
        return;
      }
      const selected = (multiroomSnapshot.groups||[]).find(
        group => group.id === document.getElementById('mr-group-select').value
      );
      if(mode !== 'create' && !selected) {
        mrMessage('Vui lòng chọn một nhóm để thao tác', true);
        return;
      }
      multiroomGroupEditorMode = mode;
      editor.classList.remove('d-none');
      const nameInput = document.getElementById('mr-group-name');
      const confirm = document.getElementById('mr-group-confirm');
      const warning = document.getElementById('mr-group-delete-warning');
      const deleting = mode === 'delete';
      document.getElementById('mr-group-editor-title').textContent =
        mode === 'create' ? 'Tạo Nhóm Loa Mới' : mode === 'edit' ? 'Sửa Nhóm Loa' : 'Xóa Nhóm Loa';
      document.getElementById('mr-group-id').value = selected?.id || '';
      nameInput.value = selected?.name || '';
      nameInput.placeholder = mode === 'create' ? 'Nhập tên nhóm cần tạo' : 'Tên nhóm loa';
      nameInput.disabled = deleting;
      confirm.className = 'btn btn-sm flex-grow-1 ' + (deleting ? 'btn-danger' : mode === 'create' ? 'btn-success' : 'btn-primary');
      confirm.innerHTML = deleting
        ? '<i class="bi bi-trash"></i> Xác Nhận Xóa'
        : '<i class="bi bi-check-circle"></i> ' + (mode === 'create' ? 'Tạo Nhóm' : 'Lưu Thay Đổi');
      warning.classList.toggle('d-none', !deleting);
      document.getElementById('mr-group-members-label').textContent = deleting
        ? 'Các loa hiện có trong nhóm:' : 'Chọn loa tham gia nhóm:';
      renderGroupMembersCheckbox(selected?.members || [], deleting);
      if(mode === 'create') nameInput.focus();
    }

    function multiroomGroupIdFromName(name) {
      return String(name||'').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase().replace(/đ/g, 'd').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    }

    function multiroomGroupConfirm() {
      if(!multiroomAPIConnected) {
        mrMessage(multiroomGroupRequiresVBot, true);
        return;
      }
      if(multiroomGroupEditorMode === 'delete') return multiroomGroupDelete();
      if(multiroomGroupEditorMode === 'create' || multiroomGroupEditorMode === 'edit') {
        return multiroomGroupSave(multiroomGroupEditorMode);
      }
    }
    
    function renderGroupMembersCheckbox(selectedMembers=[], disabled=false) {
      const container = document.getElementById('mr-group-members');
      const availableDevices = [...(multiroomSnapshot.devices||[])];
      const knownIds = new Set(availableDevices.map(device => device.id));
      selectedMembers.forEach(id => {
        if(!knownIds.has(id)) availableDevices.push({id:id, name:id, online:false});
      });
      container.innerHTML = availableDevices.map(d => {
        const isSelected = selectedMembers.includes(d.id);
        const status = d.online===false ? '<span class="text-danger">offline</span>' : '<span class="text-success">online</span>';
		return '<div class="form-check">' +
		  '<input class="form-check-input mr-device-checkbox border-success" type="checkbox" id="device_' + d.id + '" data-device-id="' + d.id + '" ' +
			(isSelected ? 'checked' : '') + ' ' +
			(disabled ? 'disabled' : '') +
		  '>' +
		  '<label class="form-check-label" for="device_' + d.id + '">' +
			'<i class="bi bi-speaker"></i> ' + (d.name || d.id || 'N/A') + ' - ' + d.id + ' ' + status +
		  '</label>' +
		'</div>';
      }).join('') || '<div class="text-muted">Không tìm thấy loa.</div>';
    }
    
    function getSelectedGroupMembers() {
      const checkboxes = document.querySelectorAll('#mr-group-members .mr-device-checkbox:checked');
      return Array.from(checkboxes).map(cb => cb.dataset.deviceId);
    }
    
    async function multiroomGroupSave(mode) { 
      multiroomLoadingStart();
      try { 
        const name=document.getElementById('mr-group-name').value.trim();
        if(!name) throw new Error('Tên nhóm không được để trống');
        const groupId = mode==='create'
          ? multiroomGroupIdFromName(name)
          : document.getElementById('mr-group-id').value;
        if(!groupId) throw new Error('Không thể tạo mã nhóm từ tên đã nhập');
        const members=getSelectedGroupMembers();
        const p={
          action:mode==='create'?'group_create':'group_update',
          group_id:groupId,
          name:name,
          members:members,
          replace:true
        }; 
        await multiroomRequest(p);
        multiroomGroupMode('cancel');
        multiroomSuccess(mode==='create'?'Đã tạo nhóm loa':'Đã cập nhật tên và thành viên nhóm loa'); 
      } catch(e){
        mrMessage(e.message,true);
      } finally { multiroomLoadingEnd(); }
    }
    
    async function multiroomGroupDelete() { 
      multiroomLoadingStart();
      try { 
        await multiroomRequest({action:'group_delete',group_id:document.getElementById('mr-group-id').value}); 
        multiroomGroupMode('cancel');
        multiroomSuccess('Đã xóa nhóm loa'); 
      } catch(e){
        mrMessage(e.message,true);
      } finally { multiroomLoadingEnd(); }
    }
    
    async function multiroomSession(action) { 
      multiroomLoadingStart();
      try { 
        const p={action:action}; 
        if(action==='start') p.group_id=document.getElementById('mr-session-group-select').value; 
        await multiroomRequest(p); 
        multiroomSuccess(action==='start'?'Đã kết nối âm thanh đa vùng':'Đã dừng Multiroom và chuyển về loa local'); 
      } catch(e){
        mrMessage(e.message,true);
      } finally { multiroomLoadingEnd(); }
    }
    
    function toggleSessionSpeaker(speakerId, isChecked) {
      multiroomPendingSpeakerChanges[speakerId] = isChecked;
    }
    
    async function multiroomApplySpeakerChanges() {
      multiroomLoadingStart();
      try {
        const controller = multiroomSnapshot.controller || {};
        const currentSpeakers = (controller.speakers || []).map(s => s.id);
        const toAdd = [];
        const toRemove = [];
        
        // Determine which speakers to add/remove
        Object.entries(multiroomPendingSpeakerChanges).forEach(([id, shouldBePresent]) => {
          if(shouldBePresent && !currentSpeakers.includes(id)) {
            toAdd.push(id);
          } else if(!shouldBePresent && currentSpeakers.includes(id)) {
            toRemove.push(id);
          }
        });
        
        if(toAdd.length === 0 && toRemove.length === 0) {
          multiroomSuccess('Không có thay đổi nào cần áp dụng');
        } else {
          const desiredSpeakerIds = currentSpeakers
            .filter(id => !toRemove.includes(id))
            .concat(toAdd.filter(id => !currentSpeakers.includes(id)));
          await multiroomRequest({action:'sync_speakers', speaker_ids:desiredSpeakerIds});
          multiroomSuccess('Đã áp dụng thay đổi loa');
        }
        multiroomPendingSpeakerChanges = {};
      } catch(e){
        mrMessage(e.message,true);
      } finally { multiroomLoadingEnd(); }
    }
    
    async function multiroomSpeakerSetVolume(id, volume) { 
      try { 
        await multiroomRequest({action:'set_volume', speaker_ids:[id], volume:Number(volume)}); 
      } catch(e){
        mrMessage(e.message,true);
      } 
    }

    function multiroomMasterVolumePreview(value) {
      multiroomMasterVolumeDragging = true;
      const text = document.getElementById('mr-master-volume-value');
      if(text) text.textContent = Number(value) + '%';
    }

    async function multiroomMasterVolumeCommit(value) {
      multiroomLoadingStart();
      try {
        const result = await multiroomRequest({action:'set_master_volume', volume:Number(value)});
        const applied = Number(result?.group_master_volume ?? value);
        multiroomSuccess('Đã đặt âm lượng tổng của toàn bộ loa thành ' + applied + '%');
      } catch(e) {
        mrMessage(e.message, true);
      } finally {
        multiroomMasterVolumeDragging = false;
        multiroomLoadingEnd();
      }
    }

    function multiroomVolumeHover(id, active) {
      if(active) multiroomVolumeHovered.add(id);
      else multiroomVolumeHovered.delete(id);
      if(!active && !multiroomVolumeDragging.has(id)) renderMultiroom();
    }

    function multiroomVolumeBegin(id, value) {
      multiroomVolumeDragging.add(id);
      multiroomVolumeDrafts[id] = Number(value);
    }

    function multiroomVolumePreview(id, value) {
      const normalized = Math.max(0, Math.min(100, Number(value) || 0));
      multiroomVolumeDrafts[id] = normalized;
      const text = document.getElementById('mr-volume-value-' + id);
      if(text) text.textContent = normalized + '%';
    }

    async function multiroomVolumeCommit(id, value) {
      const normalized = Math.max(0, Math.min(100, Number(value) || 0));
      multiroomVolumeDrafts[id] = normalized;
      try {
        await multiroomSpeakerSetVolume(id, normalized);
      } finally {
        multiroomVolumeDragging.delete(id);
        delete multiroomVolumeDrafts[id];
        if(!multiroomVolumeHovered.has(id)) renderMultiroom();
      }
    }

    function multiroomVolumeCancel(id) {
      multiroomVolumeDragging.delete(id);
      delete multiroomVolumeDrafts[id];
      if(!multiroomVolumeHovered.has(id)) renderMultiroom();
    }
    
    async function multiroomSpeakerSetMute(id, isMuted) { 
      multiroomLoadingStart();
      try { 
        await multiroomRequest({action:'set_mute', speaker_ids:[id], muted:isMuted});
        multiroomSuccess(isMuted?'Đã tắt tiếng loa':'Đã bật tiếng loa'); 
      } catch(e){
        mrMessage(e.message,true);
      } finally { multiroomLoadingEnd(); }
    }
    
    function renderMultiroom() {
      // Render group select
      const select=document.getElementById('mr-group-select'); 
      if(!select)return; 
      const old=select.value; 
      select.replaceChildren(); 
      (multiroomSnapshot.groups||[]).forEach(g=>select.add(new Option(g.name+' ('+g.member_count+' loa)',g.id))); 
      if(old && (multiroomSnapshot.groups||[]).some(g=>g.id===old)) select.value=old; 
      if(!multiroomGroupEditorMode) multiroomSelectGroup();
      
      // Render session group select
      const sessionSelect=document.getElementById('mr-session-group-select');
      if(sessionSelect) {
        const oldSession=sessionSelect.value;
        sessionSelect.replaceChildren();
        (multiroomSnapshot.groups||[]).forEach(g=>sessionSelect.add(new Option(g.name+' ('+g.member_count+' loa)',g.id)));
        if(oldSession)sessionSelect.value=oldSession;
      }
      
      // Render devices list
      const devices=document.getElementById('mr-devices'); 
      const runtimeSpeakerMap = Object.fromEntries(((multiroomSnapshot.controller||{}).speakers||[]).map(s => [s.id, s]));
      devices.replaceChildren();
      const multiroomDevices = multiroomSnapshot.devices || [];
      multiroomDevices.forEach(d=>{
        const online = runtimeSpeakerMap[d.id]?.online ?? d.online;
        const item = document.createElement('div');
        item.className = 'py-1';
        const icon = document.createElement('i');
        icon.className = 'bi bi-speaker';
        const status = document.createElement('span');
        status.className = online === false ? 'text-danger' : 'text-success';
        status.title = online === false ? 'Offline' : 'Online';
        status.textContent = '●';
        item.appendChild(icon);
        item.appendChild(document.createTextNode(' ' + String(d.name || d.id || 'N/A') + ' - ' + String(d.id || '') + ' '));
        item.appendChild(status);
        devices.appendChild(item);
      });
      if (multiroomDevices.length === 0) devices.textContent = 'Không tìm thấy loa.';
      
      // Render session info
      const c=multiroomSnapshot.controller||{}, b=multiroomSnapshot.bridge||{}, r=multiroomSnapshot.receiver||{}; 
      const activeSessionStates = ['starting', 'playing', 'paused', 'receiving'];
      const multiroomSessionActive = activeSessionStates.includes(String(c.state||'').toLowerCase())
        || b.mode === 'multiroom'
        || r.running === true;
      updateMultiroomConnectionIndicators(multiroomSessionActive);
      document.getElementById('mr-no-active-session')?.classList.toggle('d-none', multiroomSessionActive);
      document.getElementById('mr-active-session-controls')?.classList.toggle('d-none', !multiroomSessionActive);
      if(!multiroomSessionActive) {
        multiroomPendingSpeakerChanges = {};
        multiroomVolumeHovered.clear();
        multiroomVolumeDragging.clear();
      }
      document.getElementById('mr-session').textContent='Phiên phát: '+(c.state||'idle')+' | Nhóm: '+(c.group_id||'N/A')+' | '+((c.speakers||[]).length)+' loa'; 
      if(!multiroomMasterVolumeDragging) {
        const masterVolume = Number(c.group_master_volume ?? r.master_volume ?? 0);
        const masterSlider = document.getElementById('mr-master-volume');
        const masterText = document.getElementById('mr-master-volume-value');
        if(masterSlider) masterSlider.value = masterVolume;
        if(masterText) masterText.textContent = masterVolume + '%';
      }

      // Coordinator la VBot dang giu nguon PCM va phat packet cho ca group.
      // Uu tien controller smart; tren loa client dung route cua receiver.
      const normalizeMrHost = value => String(value||'').trim().toLowerCase().replace(/\.$/, '');
      const coordinatorHost = c.coordinator_host || r.coordinator_host || r.route_coordinator_host || '';
      const normalizedCoordinatorHost = normalizeMrHost(coordinatorHost);
      const localDeviceId = String(r.id||'').trim().toLowerCase();
      const localDeviceHost = normalizeMrHost(r.host||'');
      
      // Render speakers with checkboxes - hiển thị tất cả devices, không chỉ current speakers
      const speakersContainer = document.getElementById('mr-speakers');
      if(!multiroomSessionActive) {
        speakersContainer.replaceChildren();
        return;
      }
      // SSE van cap nhat snapshot phia sau, nhung khong thay DOM slider khi
      // con tro dang hover/drag de thumb khong bi giat ve gia tri server cu.
      if(multiroomVolumeHovered.size || multiroomVolumeDragging.size) return;
      const currentSpeakerIds = (c.speakers||[]).map(s => s.id);
      const speakersMap = Object.fromEntries((c.speakers||[]).map(s => [s.id, s]));
      
      const allDevices = multiroomSnapshot.devices||[];
      speakersContainer.innerHTML = allDevices.map(d => {
        const rawSpeakerId = String(d.id || '');
        const safeSpeakerId = vbotEscapeHtml(rawSpeakerId);
        const encodedSpeakerId = vbotEncodeInlineValue(rawSpeakerId);
        const safeSpeakerName = vbotEscapeHtml(d.name || d.id || 'Loa');
        const currentSpeaker = speakersMap[d.id];
        const isInCurrentSession = currentSpeakerIds.includes(d.id);
        const isCoordinator = normalizedCoordinatorHost && normalizeMrHost(d.host) === normalizedCoordinatorHost;
        
        // Xác định trạng thái checkbox: nếu có pending change, dùng nó; không thì dùng trạng thái hiện tại
        let isChecked;
        if(isCoordinator) {
          // Loa chủ chính là nguồn PCM nên luôn thuộc phiên phát. Không cho
          // WebUI gửi yêu cầu ngắt chính coordinator khỏi phiên của nó.
          isChecked = true;
          delete multiroomPendingSpeakerChanges[d.id];
        } else if(multiroomPendingSpeakerChanges.hasOwnProperty(d.id)) {
          isChecked = multiroomPendingSpeakerChanges[d.id]; // Dùng pending state
        } else {
          isChecked = isInCurrentSession; // Dùng current state
        }
        
        const volume = currentSpeaker?.volume ?? 100;
        const isMuted = currentSpeaker?.muted ?? false;
        const effectiveOnline = currentSpeaker?.online ?? d.online;
        const statusClass = effectiveOnline === false ? 'text-danger' : 'text-success';
        const statusText = effectiveOnline === false ? 'offline' : 'online';
        const bgColor = isChecked ? '#e8f5e9' : (isInCurrentSession ? '#fff3e0' : '#f5f5f5');
        const isCurrentDevice = (localDeviceId && String(d.id||'').trim().toLowerCase() === localDeviceId)
          || (localDeviceHost && normalizeMrHost(d.host) === localDeviceHost);
        
		return '<div class="col-md-6"><div class="border rounded p-3" style="background:' + bgColor + ';">' +
		  '<div class="form-check mb-2">' +
			'<input class="form-check-input border-success" type="checkbox" id="speaker_' + safeSpeakerId + '" ' + (isChecked ? 'checked' : '') +
			  (isCoordinator ? ' disabled title="Loa chủ luôn phát âm thanh trong phiên"' : '') +
			  " onchange=\"toggleSessionSpeaker(decodeURIComponent('" + encodedSpeakerId + "'), this.checked)\">" +
			'<label class="form-check-label fw-bold d-block"' +
			  (isCoordinator ? ' style="opacity:1;color:inherit;"' : '') + '>' +
			  '<span class="d-block"><i class="bi bi-speaker"></i> ' + safeSpeakerName +
				' <span class="' + statusClass + '" title="' + statusText + '">●</span>' +
			  '</span>' +
			  '<span class="d-flex align-items-center gap-1 mt-1 flex-wrap">' +
				(isCoordinator
				  ? '<span class="badge bg-primary"><i class="bi bi-broadcast"></i> Nguồn Phát</span>'
				  : (isInCurrentSession
					  ? '<span class="badge bg-secondary"><i class="bi bi-speaker"></i> Nguồn Nhận</span>'
					  : '')) +
				(isCurrentDevice
				  ? '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Loa hiện tại</span>'
				  : '') +
			  '</span>' +
			'</label>' +
		  '</div>' +

		  '<div class="mb-2">' +
			'<small class="text-muted">Volume PCM: <b id="mr-volume-value-' + safeSpeakerId + '" class="text-primary">' +
			  volume + '%</b> ' +
			  (isMuted ? '<span class="badge bg-danger">Mute</span>' : '') +
			'</small><br>' +

			'<input title="Kéo để thay đổi âm lượng PCM của từng loa trong nhóm" type="range" min="0" max="100" value="' + volume + '" class="form-range form-range-sm" ' +
			  "onpointerenter=\"multiroomVolumeHover(decodeURIComponent('" + encodedSpeakerId + "'), true)\" " +
			  "onpointerleave=\"multiroomVolumeHover(decodeURIComponent('" + encodedSpeakerId + "'), false)\" " +
			  "onfocus=\"multiroomVolumeHover(decodeURIComponent('" + encodedSpeakerId + "'), true)\" " +
			  "onblur=\"multiroomVolumeHover(decodeURIComponent('" + encodedSpeakerId + "'), false)\" " +
			  "onpointerdown=\"multiroomVolumeBegin(decodeURIComponent('" + encodedSpeakerId + "'), this.value)\" " +
			  "onpointercancel=\"multiroomVolumeCancel(decodeURIComponent('" + encodedSpeakerId + "'))\" " +
			  "oninput=\"multiroomVolumePreview(decodeURIComponent('" + encodedSpeakerId + "'), this.value)\" " +
			  "onchange=\"multiroomVolumeCommit(decodeURIComponent('" + encodedSpeakerId + "'), this.value)\" " +
			  (isInCurrentSession ? '' : 'disabled') +
			'>' +
		  '</div>' +

		  '<button class="btn ' + (isMuted ? 'btn-success' : 'btn-warning') + ' btn-sm me-1" ' +
			"onclick=\"multiroomSpeakerSetMute(decodeURIComponent('" + encodedSpeakerId + "'), " + (!isMuted) + ")\" " +
			(isInCurrentSession ? '' : 'disabled') +
		  '>' +
			'<i class="bi ' + (isMuted ? 'bi-volume-mute' : 'bi-volume-down') + '"></i> ' +
			(isMuted ? 'Bật' : 'Tắt') + ' Âm' +
		  '</button>' +

		  (isChecked && !isInCurrentSession
			? '<small class="text-success d-block mt-2"><i class="bi bi-plus-circle-fill"></i> Thêm vào phiên đang phát</small>'
			: '') +

		  (!isChecked && isInCurrentSession
			? '<small class="text-danger d-block mt-2"><i class="bi bi-x-circle-fill"></i> Ngắt khỏi phiên đang phát</small>'
			: '') +

		'</div></div>';
      }).join('')||'<div class="text-muted col-12">Không có loa nào.</div>';
    }
    
    async function multiroomRefresh(discover=false, notifySuccess=false, background=false) { 
      if(!background) multiroomLoadingStart();
      try { 
        // Refresh nhanh khong quet mDNS; giu lai danh sach da discovery.
        const response=await vbotFetchWithTimeout(multiroomApiUrl+(discover?'?discover=true':''),{cache:'no-store'},30000)
          .catch(() => { setMultiroomAPIConnected(false); throw new Error(multiroomConnectionError); }); 
        const data=await multiroomReadJson(response);
        if(!response.ok||!data.success)throw new Error(data.message||'Không lấy được trạng thái'); 
        applyMultiroomSnapshot(data.multiroom||{}, discover);
        if(notifySuccess) multiroomSuccess(discover?'Đã quét và cập nhật danh sách loa':'Đã cập nhật trạng thái Multiroom');
      } catch(e){
        setMultiroomAPIConnected(false);
        mrMessage(e.message,true);
      } finally { if(!background) multiroomLoadingEnd(); }
    }
    
    function multiroomOpen(){ 
      document.getElementById('multiroom-ui-message').className='alert d-none'; 
      multiroomDisabledNoticeShown = false;
      multiroomPendingSpeakerChanges = {}; // Reset changes when opening modal
      multiroomSSEWasDisconnected = false;
      setMultiroomAPIConnected(false);
      multiroomRefresh(true);
      startMultiroomSSE();
      startMultiroomDiscoveryMonitor();
    }

    document.getElementById('multiroomModal')?.addEventListener('hidden.bs.modal', function () {
      stopMultiroomSSE();
      stopMultiroomDiscoveryMonitor();
    });
    
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
		const weatherRes = await vbotFetchWithTimeout(weatherUrl, {}, 30000);
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
		elCity.replaceChildren();
		elCity.appendChild(document.createTextNode(String(w.name || '') + ', '));
		var countrySpan = document.createElement('span');
		countrySpan.textContent = String((w.sys && w.sys.country) || '');
		elCity.appendChild(countrySpan);
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
	  vbotFetchWithTimeout(url, {
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
	  vbotFetchWithTimeout(url, {
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
		vbotFetchWithTimeout(url, {
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
		vbotFetchWithTimeout("includes/php_ajax/Check_Connection.php?check_version_airplay", {cache: "no-store"}, 30000)
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
	  vbotFetchWithTimeout(url, {
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
	  vbotFetchWithTimeout(url, {
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
    return vbotFetchWithTimeout('<?php echo $URL_API_VBOT; ?>', {
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

//Nút tắt/mở tiếng Bluetooth chỉ hoạt động khi có thiết bị kết nối.
//Previous/Next là nút dùng chung nên được backend quyết định theo nguồn đang phát.
function setBleButtons(enabled) {
    ["bluetooth_mute_unmute"].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.disabled = !enabled;
        }
    });
}

//Cập nhật hiển thị danh sách thiết bị bluetooth đang kết nối
let bluetoothSelectOpen = false;
function getBluetoothAdapterName(device) {
    if (device && device.adapter) {
        return String(device.adapter);
    }
    const path = String((device && device.path) || '');
    const match = path.match(/\/(hci\d+)(?:\/|$)/i);
    return match ? match[1].toLowerCase() : 'N/A';
}

function formatBluetoothDeviceLabel(device) {
    return String((device && device.name) || 'Unknown Device') + ' (' + getBluetoothAdapterName(device) + ')';
}

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
		statusEl.replaceChildren();
		const disconnected = document.createElement('span');
		disconnected.className = 'text-danger';
		disconnected.textContent = 'Chưa kết nối';
		statusEl.appendChild(disconnected);
		setBleButtons(false);
        return;
    }
    if (devices.length === 1) {
		statusEl.replaceChildren();
		const deviceLabel = document.createElement('span');
		deviceLabel.className = 'text-success';
		deviceLabel.textContent = formatBluetoothDeviceLabel(devices[0]);
		const disconnectButton = document.createElement('button');
		disconnectButton.type = 'button';
		disconnectButton.className = 'btn btn-danger btn-sm py-0 px-2 ms-1';
		disconnectButton.style.fontSize = '0.75rem';
		disconnectButton.title = 'Nhấn để ngắt kết nối Bluetooth với thiết bị đang kết nối hiện tại';
		disconnectButton.textContent = 'Ngắt kết nối';
		disconnectButton.addEventListener('click', function() { bluetooth_control('disconnect'); });
		statusEl.appendChild(deviceLabel);
		statusEl.appendChild(disconnectButton);
		setBleButtons(true);
        return;
    }
	statusEl.replaceChildren();
	const deviceSelect = document.createElement('select');
	deviceSelect.id = 'bluetooth_device_select';
	deviceSelect.className = 'form-select form-select-sm border-success';
	deviceSelect.style.width = 'auto';
	deviceSelect.style.minWidth = '220px';
	deviceSelect.addEventListener('focus', function() { bluetoothSelectOpen = true; });
	deviceSelect.addEventListener('blur', function() { bluetoothSelectOpen = false; });
	deviceSelect.addEventListener('change', function() { bluetooth_control('receive_signal', this.value); });
    devices.forEach(function(device) {
		const option = document.createElement('option');
		option.value = String(device.path || '');
		option.textContent = formatBluetoothDeviceLabel(device);
		option.selected = device.path === bluetooth.device_path;
		deviceSelect.appendChild(option);
    });
	statusEl.appendChild(deviceSelect);
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
			const media = data.media_player || {};
			const audioOutput = data.audio_output || {};
			const mediaSourceKind = media.source_kind || (
				media.airplay_playing === true ? 'airplay' :
				data.bluetooth?.playing === true ? 'bluetooth' :
				(media.audio_playing === true || media.pause_media_flag === true) ? 'local_media' : 'idle'
			);
			const mediaPlaybackState = media.playback_state || (
				media.pause_media_flag === true ? 'paused' :
				(media.audio_playing === true || media.airplay_playing === true || data.bluetooth?.playing === true)
					? 'playing' : 'idle'
			);
			const mediaIsPlaying = mediaPlaybackState === 'playing';
			const mediaIsPaused = mediaPlaybackState === 'paused';
			const multiroomActive = media.multiroom_active === true || mediaSourceKind === 'multiroom';
			updateMultiroomConnectionIndicators(multiroomActive);
			const multiroomCoordinator = media.multiroom_coordinator === true;
			const showMediaProgress = true;
			canSeekCurrentMedia = (!multiroomActive || multiroomCoordinator) && (mediaIsPlaying || mediaIsPaused);
			const progressContainer = document.getElementById('progress-container');
			progressContainer.style.display = showMediaProgress ? '' : 'none';
			document.getElementById('progress-bar').disabled = !canSeekCurrentMedia;
            //Media Player
			document.getElementById('media-name').innerHTML =
				'Tên bài hát: <font color="blue">' +
				(
					mediaSourceKind === 'airplay'
						? (
							media.airplay_song_name &&
							String(media.airplay_song_name).trim() !== 'N/A'
								? media.airplay_song_name
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
								return (mediaSourceKind === 'bluetooth' ? btName : null) ||
									(
										(mediaIsPlaying || mediaIsPaused)
											&& media.media_name &&
											String(media.media_name).trim() !== 'N/A'
											? media.media_name
											: 'N/A'
									);
							})()
						)
				) +
				'</font>';

			document.getElementById('audio-playing').innerHTML = 'Trạng Thái: <font color=blue>' + (mediaIsPlaying ? (multiroomActive ? 'Đang phát Multiroom' : 'Đang phát') : (mediaIsPaused ? (multiroomActive ? 'Multiroom đang tạm dừng' : 'Đang tạm dừng') : 'Không phát')) + '</font>';
			//Cập nhật nguồn phát nhạc
			document.getElementById('audio-source').innerHTML =
				'Nguồn Phát: <font color=blue>' +
				(
					mediaSourceKind === 'bluetooth'
						? ('<i class="bi bi-bluetooth"></i>' + (data.bluetooth.device_name ? ' - ' + data.bluetooth.device_name : ''))
						: (
							mediaSourceKind === 'airplay'
								? 'AirPlay'
								: (
									(mediaIsPlaying || mediaIsPaused)
										? (
											media.media_player_source &&
											String(media.media_player_source).trim() !== 'N/A'
												? media.media_player_source
												: 'Local Audio'
										  )
										: 'N/A'
								)
						)
				) +
				'</font>';
			if (media.playlist_active && media.playlist_name) {
				document.getElementById('audio-source').innerHTML =
					'Nguồn Phát: <font color="blue">PlayList - ' + playlistEscapeHtml(media.playlist_name) + '</font>';
			}
			playlistMarkPlaying(media.playlist_active ? media.playlist_id : null, media.playlist_active ? media.media_name : null);
			if (multiroomActive) {
				const coordinatorName = String(media.multiroom_coordinator_name || '').trim();
				document.getElementById('audio-source').innerHTML =
					'Nguồn Phát: <font color="blue">Multiroom' +
					(coordinatorName ? ' - ' + coordinatorName : '') + '</font>';
			}

            //Cập nhật ảnh cover bài hát
			document.getElementById('media-cover').src =
			(
				mediaSourceKind === 'bluetooth' && data.bluetooth?.is_connected === true
					? 'assets/img/bluetooth_icon.png'
					: (
						mediaSourceKind === 'airplay'
							? 'assets/img/AirPlay_Cover.jpg?t=' + Date.now()
							: (
								(mediaIsPlaying || mediaIsPaused)
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
            // Bluetooth cung cấp tổng thời lượng qua metadata AVRCP Track.Duration;
            // media nội bộ tiếp tục dùng thời lượng do VLC trả về.
            const bluetoothDuration = Number(data.bluetooth?.song_duration) || 0;
            const displayedCurrentDuration = mediaSourceKind === 'bluetooth'
              ? 0
              : (Number(data.media_player.current_duration) || 0);
            fullTime = mediaSourceKind === 'bluetooth'
              ? bluetoothDuration
              : (Number(data.media_player.full_time) || 0);
            if (mediaIsPlaying || mediaIsPaused) {
              updateDisplay_SongNhac(true, mediaIsPaused);
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
              set_Volume_HTML(audioOutput.volume ?? data.volume);
            }
            if (!isHovering) {
              //Cập nhật volume khi chuột không trong vùng của nó
              let progressBar = document.getElementById('progress-bar');
              progressBar.max = fullTime;
              progressBar.value = displayedCurrentDuration;
              let timeInfo = document.getElementById('time-info');
              timeInfo.innerHTML = '<font color=blue>' + formatTime_Player(displayedCurrentDuration) + '</font> / ' + formatTime_Player(fullTime);
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
    let canSeekCurrentMedia = false;
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
      vbotFetchWithTimeout("<?php echo $URL_API_VBOT ?>?type=1&data=all_info")
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
      if (!canSeekCurrentMedia) return;
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
		vbotFetchWithTimeout("includes/php_ajax/Scanner.php?check_version=" + encodeURIComponent(type), {
			cache: "no-store"
		})
		.then(async function(response) {
			const data = await response.json();
			if (!response.ok) {
				throw new Error(data.message || ("Lỗi HTTP: " + response.status));
			}
			return data;
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
				if ((message.includes("bluealsad") || message.includes("shairport")) && message.includes("command not found")) {
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
      var xhr = vbotCreateXhr();
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
              show_message('Lỗi phân tích JSON:' + e);
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
    let selectedPlaylistId = null;
    let playlistManagerCache = {playlists:[], active_id:null};
    let playlistRenderedItems = [];
    let playlistOrderDirty = false;
    let playlistPage = 1;
    let playlistPageSize = 10;
    let playlistOrderSaveTimer = null;
    let playlistRuntimeId = null;
    let playlistRuntimeTitle = null;

    function playlistEscapeHtml(value) {
      const element = document.createElement('div');
      element.textContent = String(value ?? '');
      return element.innerHTML;
    }

    function playlistSafeHttpUrl(value) {
      try {
        const url = new URL(String(value || ''), window.location.href);
        return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
      } catch(error) {
        return '';
      }
    }

    function playlistDurationSeconds(value) {
      const text = String(value || '').trim();
      if(/^\d+$/.test(text)) return Number(text);
      const parts = text.split(':').map(Number);
      if(parts.length >= 2 && parts.every(Number.isFinite)) return parts.reduce((total, part) => total * 60 + part, 0);
      const iso = text.match(/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/i);
      return iso ? Number(iso[1]||0)*3600 + Number(iso[2]||0)*60 + Number(iso[3]||0) : 0;
    }

    function playlistFormatDuration(seconds) {
      seconds = Math.max(0, Math.floor(Number(seconds)||0));
      return [Math.floor(seconds/3600), Math.floor((seconds%3600)/60), seconds%60].map(value => String(value).padStart(2,'0')).join(':');
    }

    function playlistRenderStats(items) {
      const sources = {};
      let duration = 0;
      items.forEach(item => {
        const source = String(item.source || 'Không rõ');
        sources[source] = (sources[source] || 0) + 1;
        duration += playlistDurationSeconds(item.duration);
      });
      const element = document.getElementById('playlist-stats');
      if(element) element.textContent = items.length + ' bài • ' + playlistFormatDuration(duration) + ' • ' + Object.entries(sources).map(([source,count]) => source + ': ' + count).join(' | ');
    }

    function playlistRefreshOrderButtons() {
      const rows = Array.from(document.querySelectorAll('#playlistTableBody tr[data-playlist-item-id]'));
      rows.forEach((row, index) => {
        const up = row.querySelector('.playlist-order-up');
        const down = row.querySelector('.playlist-order-down');
        if(up) up.disabled = index === 0;
        if(down) down.disabled = index === rows.length - 1;
      });
    }

    function playlistPlayEntry(button) {
      const item = playlistRenderedItems[Number(button.dataset.entryIndex)];
      if(!item) return show_message('Không tìm thấy dữ liệu bài hát');
      const source = String(item.source || '');
      if(source === 'Youtube') return play_Youtube_Link(item.id, item.title, item.cover);
      if(source === 'ZingMP3') return get_ZingMP3_Link(item.id, item.title, item.cover, item.artist);
      if(source === 'Local') return send_Media_Play_API(item.audio, item.title, item.cover, 'Local');
      return send_Media_Play_API(item.audio, item.title, item.cover, source || 'PlayList');
    }

    function playlistMarkPlaying(playlistId, title) {
      playlistRuntimeId = playlistId || null;
      playlistRuntimeTitle = title || null;
      document.querySelectorAll('#playlistTableBody tr[data-entry-index]').forEach(row => {
        const item = playlistRenderedItems[Number(row.dataset.entryIndex)];
        const active = Boolean(playlistId && playlistId === selectedPlaylistId && title && String(item?.title || item?.name || '') === String(title));
        row.classList.toggle('table-success', active);
        let badge = row.querySelector('.playlist-playing-badge');
        if(active && !badge) {
          badge = document.createElement('span');
          badge.className = 'playlist-playing-badge badge bg-success ms-2';
          badge.textContent = 'Đang phát';
          row.querySelector('td:nth-child(2) div > div:last-child')?.prepend(badge);
        } else if(!active && badge) badge.remove();
      });
    }

    function playlistDeleteEntry(button) {
      const item = playlistRenderedItems[Number(button.dataset.entryIndex)];
      if(item?.ids_list) deleteFromPlaylist('delete_some', String(item.ids_list));
    }

    async function loadPlayList() {
      loading('show');
      try {
        const response = await vbotFetchWithTimeout('includes/php_ajax/Media_Player_Search.php?Playlist_Manager=1', {cache:'no-store'}, 30000);
        const manager = await response.json();
        if(!response.ok || manager.success === false) throw new Error(manager.message || 'Không tải được danh sách PlayList');
        const exists = (manager.playlists||[]).some(item => item.id === selectedPlaylistId);
        selectedPlaylistId = exists ? selectedPlaylistId : manager.active_id;
        renderPlayList(manager);
      } catch(error) {
        loading('hide');
        show_message('Lỗi quản lý PlayList: ' + error.message);
      }
    }

    let playlistManagerDialogAction = null;

    function playlistCurrentMeta() {
      return (playlistManagerCache.playlists||[]).find(item => item.id === selectedPlaylistId) || {};
    }

    function playlistOpenDialog(action) {
      const meta = playlistCurrentMeta();
      const modalElement = document.getElementById('playlistManagerDialog');
      const input = document.getElementById('playlist-manager-name-input');
      const inputGroup = document.getElementById('playlist-manager-name-group');
      const description = document.getElementById('playlist-manager-dialog-description');
      const title = document.getElementById('playlistManagerDialogLabel');
      const confirmButton = document.getElementById('playlist-manager-dialog-confirm');
      playlistManagerDialogAction = action;
      input.classList.remove('is-invalid');
      input.value = '';
      const destructiveAction = action === 'delete' || action === 'delete_all' || action === 'delete_selected';
      inputGroup.classList.toggle('d-none', destructiveAction);
      confirmButton.className = 'btn ' + (destructiveAction ? 'btn-danger' : 'btn-primary');

      if(action === 'create') {
        title.innerHTML = '<i class="bi bi-plus-circle"></i> Tạo PlayList Mới';
        description.textContent = 'Nhập tên cho PlayList cần tạo. Việc tạo mới không thay đổi PlayList mặc định.';
        input.placeholder = 'Ví dụ: Nhạc thư giãn';
      } else if(action === 'clone') {
        title.innerHTML = '<i class="bi bi-copy"></i> Nhân Bản PlayList';
        description.textContent = 'Tạo một bản sao độc lập từ PlayList “' + (meta.name || 'N/A') + '”.';
        input.value = (meta.name || 'PlayList') + ' - Bản sao';
      } else if(action === 'rename') {
        title.innerHTML = '<i class="bi bi-pencil"></i> Đổi Tên PlayList';
        description.textContent = 'Nhập tên mới cho PlayList: ' + (meta.name || 'N/A');
        input.value = meta.name || '';
      } else if(action === 'delete_all') {
        title.innerHTML = '<i class="bi bi-trash"></i> Xóa Toàn Bộ Bài Hát';
        description.textContent = 'Bạn có chắc chắn muốn xóa toàn bộ bài hát trong PlayList “' + (meta.name || 'N/A') + '”? PlayList vẫn được giữ lại.';
      } else if(action === 'delete_selected') {
        const selectedCount = playlistSelectedItemIds().length;
        title.innerHTML = '<i class="bi bi-trash"></i> Xóa Các Bài Đã Chọn';
        description.textContent = 'Bạn có chắc chắn muốn xóa ' + selectedCount + ' bài đã chọn khỏi PlayList “' + (meta.name || 'N/A') + '”?';
      } else {
        title.innerHTML = '<i class="bi bi-trash"></i> Xóa PlayList';
        description.textContent = 'Bạn có chắc chắn muốn xóa PlayList “' + (meta.name || 'N/A') + '” và toàn bộ bài hát bên trong?';
      }

      const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      modal.show();
      if(action === 'create' || action === 'rename' || action === 'clone') {
        modalElement.addEventListener('shown.bs.modal', () => { input.focus(); input.select(); }, {once:true});
      }
    }

    function playlistManagerAction(action) {
      if(action === 'select') return playlistManagerRequest(action, '');
      playlistOpenDialog(action);
    }

    async function playlistManagerDialogSubmit() {
      const action = playlistManagerDialogAction;
      const input = document.getElementById('playlist-manager-name-input');
      const name = input.value.trim();
      if((action === 'create' || action === 'rename' || action === 'clone') && (!name || name.length > 80)) {
        input.classList.add('is-invalid');
        input.focus();
        return;
      }
      if(action === 'delete_all') {
        bootstrap.Modal.getInstance(document.getElementById('playlistManagerDialog'))?.hide();
        deleteFromPlaylist('delete_all', '', true);
        return;
      }
      if(action === 'delete_selected') {
        const selectedIds = playlistSelectedItemIds();
        if(!selectedIds.length) {
          bootstrap.Modal.getInstance(document.getElementById('playlistManagerDialog'))?.hide();
          show_message('Không còn bài hát nào được chọn');
          return;
        }
        bootstrap.Modal.getInstance(document.getElementById('playlistManagerDialog'))?.hide();
        deleteFromPlaylist('delete_some', selectedIds.join(','), true);
        return;
      }
      await playlistManagerRequest(action, name);
    }

    async function playlistManagerRequest(action, name='') {
      loading('show');
      try {
        const body = new URLSearchParams({playlist_manager_action:action, playlist_id:selectedPlaylistId||'', source_playlist_id:selectedPlaylistId||'', playlist_name:name.trim()});
        const response = await vbotFetchWithTimeout('includes/php_ajax/Media_Player_Search.php', {
          method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':window.VBOT_CSRF_TOKEN||''}, body:body
        });
        const data = await response.json();
        if(!response.ok || !data.success) throw new Error(data.message || 'Thao tác PlayList thất bại');
        if(action === 'create' || action === 'clone' || action === 'select') selectedPlaylistId = data.playlist_id || data.active_id;
        if(action === 'delete') selectedPlaylistId = data.active_id;
        bootstrap.Modal.getInstance(document.getElementById('playlistManagerDialog'))?.hide();
        showMessagePHP(data.message, 5);
        await loadPlayList();
      } catch(error) {
        loading('hide');
        show_message(error.message);
      }
    }

    function selectManagedPlaylist(id) {
      selectedPlaylistId = id;
      loadPlayList();
    }

    function downloadSelectedPlaylist() {
      if(!selectedPlaylistId) return;
      downloadPlaylistExport('includes/php_ajax/Media_Player_Search.php?Cache_PlayList=1&playlist_id=' + encodeURIComponent(selectedPlaylistId));
    }

    function downloadPlaylistExport(url) {
      const link = document.createElement('a');
      link.href = url + (url.includes('?') ? '&' : '?') + '_download=' + Date.now();
      link.download = '';
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      link.remove();
    }

    function downloadAllPlaylists() {
      downloadPlaylistExport('includes/php_ajax/Media_Player_Search.php?Playlist_Backup=1');
    }

    function playlistSelectedItemIds() {
      return Array.from(document.querySelectorAll('.playlist-item-check:checked')).map(item => item.value);
    }

    function playlistDeleteSelected() {
      const ids = playlistSelectedItemIds();
      if(!ids.length) return show_message('Hãy chọn ít nhất một bài hát cần xóa');
      playlistOpenDialog('delete_selected');
    }

    function playlistToggleAll(checked) {
      document.querySelectorAll('.playlist-item-check').forEach(item => {
        if(item.closest('tr')?.style.display !== 'none') item.checked = checked;
      });
      playlistUpdateSelectionState();
    }

    function playlistUpdateSelectionState() {
      const selected = playlistSelectedItemIds().length;
      const total = document.querySelectorAll('.playlist-item-check').length;
      const label = document.getElementById('playlist-selected-count');
      if(label) label.textContent = 'Đã chọn: ' + selected + ' / ' + total + ' bài';
      ['playlist-copy-selected','playlist-move-selected','playlist-delete-selected'].forEach(id => {
        const button = document.getElementById(id);
        if(button) button.disabled = selected === 0 || ((id !== 'playlist-delete-selected') && !(playlistManagerCache.playlists||[]).some(item => item.id !== selectedPlaylistId));
      });
    }

    function playlistApplyFilterPage(resetPage=false) {
      if(resetPage) playlistPage = 1;
      const query = String(document.getElementById('playlist-filter')?.value || '').trim().toLocaleLowerCase('vi');
      playlistPageSize = Number(document.getElementById('playlist-page-size')?.value || 10);
      const rows = Array.from(document.querySelectorAll('#playlistTableBody tr[data-playlist-item-id]'));
      const matched = rows.filter(row => !query || String(row.dataset.playlistSearch || '').includes(query));
      const pages = Math.max(1, Math.ceil(matched.length / playlistPageSize));
      playlistPage = Math.min(Math.max(1, playlistPage), pages);
      rows.forEach(row => { row.style.display = 'none'; });
      matched.slice((playlistPage - 1) * playlistPageSize, playlistPage * playlistPageSize).forEach(row => { row.style.display = ''; });
      const pageInfo = document.getElementById('playlist-page-info');
      if(pageInfo) pageInfo.textContent = 'Trang ' + playlistPage + '/' + pages + ' • ' + matched.length + ' bài';
      const prev = document.getElementById('playlist-page-prev');
      const next = document.getElementById('playlist-page-next');
      if(prev) prev.disabled = playlistPage <= 1;
      if(next) next.disabled = playlistPage >= pages;
      playlistUpdateSelectionState();
    }

    function playlistChangePage(delta) {
      playlistPage += delta;
      playlistApplyFilterPage(false);
    }

    async function playlistItemsAction(action) {
      const ids = playlistSelectedItemIds();
      if(!ids.length) return show_message('Hãy chọn ít nhất một bài hát');
      const targets = (playlistManagerCache.playlists||[]).filter(item => item.id !== selectedPlaylistId);
      if(!targets.length) return show_message('Chưa có PlayList đích khác');
      const targetId = document.getElementById('playlist-bulk-target')?.value;
      const target = targets.find(item => item.id === targetId);
      if(!target) return;
      await playlistPostItems({playlist_items_action:action, playlist_id:selectedPlaylistId, target_playlist_id:target.id, item_ids:JSON.stringify(ids)});
    }

    async function playlistSaveSettings() {
      const mode = document.getElementById('playlist-play-mode')?.value || 'random';
      const loop = document.getElementById('playlist-loop-mode')?.checked ? 'true' : 'false';
      await playlistPostItems({playlist_items_action:'settings', playlist_id:selectedPlaylistId, play_mode:mode, loop:loop});
    }

    async function playlistPostItems(values, reload=true) {
      loading('show');
      try {
        const response = await vbotFetchWithTimeout('includes/php_ajax/Media_Player_Search.php', {
          method:'POST',
          headers:{
            'Content-Type':'application/x-www-form-urlencoded',
            'X-CSRF-Token':window.VBOT_CSRF_TOKEN||''
          },
          body:new URLSearchParams(values)
        });
        const data = await response.json();
        if(!response.ok || !data.success) throw new Error(data.message || 'Thao tác PlayList thất bại');
        showMessagePHP(data.message, 4);
        if(reload) await loadPlayList();
      } catch(error) {
        loading('hide');
        show_message(error.message);
      }
    }

    function playlistMoveRow(button, direction) {
      const row = button.closest('tr[data-playlist-item-id]');
      if(!row) return;
      const sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
      if(!sibling || !sibling.matches('tr[data-playlist-item-id]')) return;
      if(direction < 0) row.parentNode.insertBefore(row, sibling);
      else row.parentNode.insertBefore(sibling, row);
      document.querySelectorAll('#playlistTableBody tr[data-playlist-item-id]').forEach((item, index) => {
        const number = item.querySelector('.playlist-order-number');
        if(number) number.textContent = String(index + 1);
      });
      playlistRefreshOrderButtons();
      row.classList.add('table-warning');
      playlistOrderDirty = true;
      const saveButton = document.getElementById('playlist-save-order');
      if(saveButton) {
        saveButton.classList.remove('btn-outline-primary');
        saveButton.classList.add('btn-warning');
        saveButton.innerHTML = '<i class="bi bi-list-ol"></i> Lưu Thứ Tự (*)';
      }
      setTimeout(() => row.classList.remove('table-warning'), 350);
      clearTimeout(playlistOrderSaveTimer);
      playlistOrderSaveTimer = setTimeout(() => playlistSaveOrder(), 900);
    }

    async function playlistSaveOrder() {
      const ids = Array.from(document.querySelectorAll('#playlistTableBody tr[data-playlist-item-id]')).map(row => row.dataset.playlistItemId);
      await playlistPostItems({playlist_items_action:'reorder', playlist_id:selectedPlaylistId, item_ids:JSON.stringify(ids)});
      playlistOrderDirty = false;
    }

    function renderPlayList(manager) {
      playlistManagerCache = manager;
      const selectedMeta = (manager.playlists||[]).find(item => item.id === selectedPlaylistId) || {};
      const bulkTargetOptions = (manager.playlists||[]).filter(item => item.id !== selectedPlaylistId).map(item => '<option value="' + playlistEscapeHtml(item.id) + '">' + playlistEscapeHtml(item.name) + '</option>').join('');
      const playlistOptions = (manager.playlists||[]).map(item =>
        '<option value="' + playlistEscapeHtml(item.id) + '" ' + (item.id===selectedPlaylistId?'selected':'') + '>' +
        playlistEscapeHtml(item.name) + ' (' + Number(item.item_count||0) + ' bài)' + (item.active?' • mặc định':'') + '</option>'
      ).join('');
      var tableContainer = document.getElementById('tableContainer');
      var tableHTML =
		'<h5 class="card-title">PlayList, Danh Sách Nhạc</h5><div id="playlist-stats" class="alert alert-secondary py-2 small">Đang tính thống kê...</div>' +
		'<div class="playlist-control-panel playlist-control-panel-primary"><span class="playlist-control-label"><i class="bi bi-collection-play"></i> Chọn Và Sử Dụng PlayList <span class="badge bg-primary ms-1">' + Number((manager.playlists||[]).length) + ' PlayList</span></span><div class="input-group"><span class="input-group-text border-primary"><i class="bi bi-collection-play"></i></span>' +
		'<select id="playlist-manager-select" class="form-select border-primary" onchange="selectManagedPlaylist(this.value)">' + playlistOptions + '</select>' +
		'<button type="button" title="Phát PlayList đang chọn" class="btn btn-success" onclick="playlist_media_control(\'managed\')" ' + (Number(selectedMeta.item_count||0)===0?'disabled':'') + '><i class="bi bi-play-fill"></i> Phát</button>' +
		'<button type="button" class="btn btn-warning" title="Tải xuống PlayList đang chọn" onclick="downloadSelectedPlaylist()"><i class="bi bi-download"></i> Tải Xuống</button></div></div>' +
		'<div class="playlist-control-panel"><span class="playlist-control-label"><i class="bi bi-tools"></i> Quản Lý PlayList</span><div class="btn-group flex-wrap" role="group" aria-label="Quản lý PlayList">' +
		'<button class="btn btn-success" type="button" onclick="playlistManagerAction(\'create\')"><i class="bi bi-plus-circle"></i> Tạo</button>' +
		'<button class="btn btn-outline-success" type="button" onclick="playlistManagerAction(\'clone\')"><i class="bi bi-copy"></i> Nhân Bản</button>' +
		'<button class="btn btn-warning" type="button" onclick="playlistManagerAction(\'select\')" ' + (selectedPlaylistId===manager.active_id?'disabled':'') + '><i class="bi bi-star-fill"></i> Đặt Mặc Định</button>' +
		'<button class="btn btn-primary" type="button" onclick="playlistManagerAction(\'rename\')"><i class="bi bi-pencil"></i> Đổi Tên</button>' +
		'<button class="btn btn-outline-danger" type="button" onclick="playlistOpenDialog(\'delete_all\')" ' + (Number(selectedMeta.item_count||0)===0?'disabled':'') + '><i class="bi bi-eraser"></i> Xóa Toàn Bộ Bài</button>' +
		'<button class="btn btn-danger" type="button" onclick="playlistManagerAction(\'delete\')" ' + ((manager.playlists||[]).length<=1?'disabled':'') + '><i class="bi bi-trash"></i> Xóa PlayList</button><button class="btn btn-dark" type="button" onclick="downloadAllPlaylists()"><i class="bi bi-archive"></i> Sao Lưu Tất Cả</button></div></div>' +
		'<div class="playlist-control-panel playlist-control-panel-info"><span class="playlist-control-label"><i class="bi bi-shuffle"></i> Chế Độ Và Thứ Tự Phát</span><div class="row g-2 align-items-center"><div class="col-md-4"><label class="form-label mb-0">Thứ tự phát</label><select id="playlist-play-mode" class="form-select border-info"><option value="sequential" ' + (selectedMeta.play_mode==='sequential'?'selected':'') + '>Tuần tự</option><option value="random" ' + (selectedMeta.play_mode==='random'?'selected':'') + '>Ngẫu nhiên</option><option value="repeat_one" ' + (selectedMeta.play_mode==='repeat_one'?'selected':'') + '>Lặp một bài</option></select></div>' +
		'<div class="col-md-3 pt-md-4"><div class="form-check form-switch"><input id="playlist-loop-mode" class="form-check-input" type="checkbox" ' + (selectedMeta.loop?'checked':'') + '><label class="form-check-label" for="playlist-loop-mode">Lặp danh sách</label></div></div>' +
		'<div class="col-md-5 pt-md-4"><button class="btn btn-info btn-sm" onclick="playlistSaveSettings()"><i class="bi bi-save"></i> Lưu Chế Độ Phát</button> <button id="playlist-save-order" class="btn btn-outline-primary btn-sm" onclick="playlistSaveOrder()"><i class="bi bi-list-ol"></i> Lưu Thứ Tự</button></div></div></div>' +
		'<div class="playlist-control-panel playlist-control-panel-success"><span class="playlist-control-label"><i class="bi bi-check2-square"></i> Thao Tác Các Bài Đã Chọn <span id="playlist-selected-count" class="badge bg-secondary ms-1">Đã chọn: 0 / ' + Number(selectedMeta.item_count||0) + ' bài</span></span><div class="input-group input-group-sm"><span class="input-group-text">PlayList đích</span><select id="playlist-bulk-target" class="form-select" ' + (bulkTargetOptions?'':'disabled') + '>' + (bulkTargetOptions || '<option>Chưa có PlayList khác</option>') + '</select><button id="playlist-copy-selected" class="btn btn-outline-success" onclick="playlistItemsAction(\'copy\')" disabled><i class="bi bi-copy"></i> Sao Chép Bài Đã Chọn</button><button id="playlist-move-selected" class="btn btn-outline-warning" onclick="playlistItemsAction(\'move\')" disabled><i class="bi bi-arrow-left-right"></i> Di Chuyển Bài Đã Chọn</button><button id="playlist-delete-selected" class="btn btn-danger" onclick="playlistDeleteSelected()" disabled><i class="bi bi-trash"></i> Xóa Bài Đã Chọn</button></div><div class="text-muted small mt-2"><i class="bi bi-arrow-up-down"></i> Dùng nút mũi tên trên từng bài để đổi vị trí; hệ thống sẽ tự lưu sau khi thay đổi.</div></div>' +
		'<div class="playlist-control-panel playlist-control-panel-success"><span class="playlist-control-label"><i class="bi bi-file-earmark-arrow-up"></i> Tải Lên và Nhập Vào Dữ LIệu PlayList</span><div class="row g-2"><div class="col-12 col-md"><select id="playlist-import-mode" class="form-select border-success" onchange="document.getElementById(\'playlist-import-new-name-column\').classList.toggle(\'d-none\',this.value!==\'new\')"><option value="overwrite">Ghi đè PlayList hiện tại</option><option value="merge">Gộp và bỏ bài trùng</option><option value="new">Tạo thành PlayList mới</option></select></div><div id="playlist-import-new-name-column" class="col-12 col-md d-none"><input id="playlist-import-new-name" maxlength="80" class="form-control border-success" placeholder="Tên PlayList mới"></div><div class="col-12"><div class="input-group"><input type="file" class="form-control border-success" id="fileInput_PlayList" accept=".json"><button class="btn btn-primary border-success" type="button" onclick="uploadFile_PlayList(\'index.php\')"><i class="bi bi-upload"></i> Tải lên và nhập</button></div></div></div></div>' +
		'<div class="row g-2 align-items-center my-3"><div class="col-md-6"><div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-search"></i></span><input id="playlist-filter" class="form-control" placeholder="Tìm tên bài, nghệ sĩ hoặc nguồn..." oninput="playlistApplyFilterPage(true)"></div></div><div class="col-md-2"><select id="playlist-page-size" class="form-select form-select-sm" onchange="playlistApplyFilterPage(true)"><option value="5">5 bài/trang</option><option value="10" selected>10 bài/trang</option><option value="20">20 bài/trang</option><option value="50">50 bài/trang</option></select></div><div class="col-md-4 text-md-end"><button id="playlist-page-prev" class="btn btn-outline-secondary btn-sm" onclick="playlistChangePage(-1)"><i class="bi bi-chevron-left"></i></button> <span id="playlist-page-info" class="small mx-2">Trang 1/1</span><button id="playlist-page-next" class="btn btn-outline-secondary btn-sm" onclick="playlistChangePage(1)"><i class="bi bi-chevron-right"></i></button></div></div>' +
		'<table class="table table-borderless" id="playlistTable">' +
        '<thead>' +
        '<tr>' +
        '<th scope="col" style="text-align: center; vertical-align: middle;"><input class="form-check-input" type="checkbox" title="Chọn tất cả" onchange="playlistToggleAll(this.checked)"> STT</th>' +
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
      var xhr = vbotCreateXhr();
      xhr.open('GET', 'includes/php_ajax/Media_Player_Search.php?Cache_PlayList=1&playlist_id=' + encodeURIComponent(selectedPlaylistId||''), true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          var data = JSON.parse(xhr.responseText);
          var fileInfo = '';
          // Kiểm tra dữ liệu
          if (data.data && Array.isArray(data.data)) {
            playlistRenderedItems = data.data.slice();
            playlistOrderDirty = false;
            data.data.forEach(function(playlist, index) {
              var description = playlist.description ? (playlist.description.length > 70 ? playlist.description.substring(0, 70) + '...' : playlist.description) : 'N/A';
              const safeCover = playlistSafeHttpUrl(playlist.cover) || 'assets/img/Error_Null_Media_Player.png';
              const externalUrl = playlist.source === 'Youtube'
                ? ('https://www.youtube.com/watch?v=' + encodeURIComponent(String(playlist.id || '')))
                : playlistSafeHttpUrl(playlist.audio);
              // Tạo thông tin cho mỗi playlist dựa trên nguồn
              fileInfo +=
                '<tr data-playlist-item-id="' + playlistEscapeHtml(playlist.ids_list) + '" data-entry-index="' + index + '" data-playlist-search="' + playlistEscapeHtml([playlist.title, playlist.artist, playlist.channelTitle, playlist.source].filter(Boolean).join(' ').toLocaleLowerCase('vi')) + '">' +
                '<td style="text-align: center; vertical-align: middle;"><input class="form-check-input playlist-item-check" type="checkbox" onchange="playlistUpdateSelectionState()" value="' + playlistEscapeHtml(playlist.ids_list) + '"> <span class="playlist-order-number">' + (index + 1) + '</span><div class="btn-group btn-group-sm ms-1" role="group" aria-label="Đổi vị trí bài hát"><button type="button" class="btn btn-outline-primary playlist-order-up" title="Chuyển lên" onclick="playlistMoveRow(this,-1)" ' + (index===0?'disabled':'') + '><i class="bi bi-arrow-up"></i></button><button type="button" class="btn btn-outline-primary playlist-order-down" title="Chuyển xuống" onclick="playlistMoveRow(this,1)" ' + (index===data.data.length-1?'disabled':'') + '><i class="bi bi-arrow-down"></i></button></div></td>' +
                '<td>' +
                '<div style="display: flex; align-items: center; margin-bottom: 10px;">' +
                '<div style="flex-shrink: 0; margin-right: 15px;">' +
                '<img src="' + playlistEscapeHtml(safeCover) + '" alt="Ảnh bìa" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;">' +
                '</div>' +
                '<div>' +
                '<p style="margin: 0; font-weight: bold;">Tên bài hát: <font color="green">' + playlistEscapeHtml(playlist.title || 'N/A') + '</font></p>' +
                (playlist.source === 'Youtube' ?
                  '<p style="margin: 0;">Kênh: <font color="green">' + playlistEscapeHtml(playlist.channelTitle || 'N/A') + '</font></p>' +
                  '<p style="margin: 0;">Thời Lượng: <font color="green">' + playlistEscapeHtml(playlist.duration || 'N/A') + '</font></p>' +
                  '<p style="margin: 0;">Mô tả: <font color="green">' + playlistEscapeHtml(description) + '</font></p>' : '') +
                (playlist.source === 'ZingMP3' ?
                  '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color="green">' + playlistEscapeHtml(playlist.artist || 'N/A') + '</font></p>' +
                  '<p style="margin: 0;">Thời Lượng: <font color="green">' + playlistEscapeHtml(playlist.duration || 'N/A') + '</font></p>' : '') +
                (playlist.source === 'PodCast' ?
                  '<p style="margin: 0;">Thể Loại: <font color="green">' + playlistEscapeHtml(description) + '</font></p>' : '') +
                (playlist.source === 'Local' ?
                  '<p style="margin: 0;">Kích Thước: <font color="green">' + playlistEscapeHtml(playlist.duration || 'N/A') + '</font></p>' : '') +
                '<p style="margin: 0;">Nguồn Nhạc: <font color="green">' + playlistEscapeHtml(playlist.source || 'N/A') + '</font></p>' +
                '</div>' +
                '</div>' +
                '</td>' +
                '<td class="playlist-action-cell" style="text-align: center; vertical-align: middle;">' +
                '<button class="btn btn-success btn-sm" data-entry-index="' + index + '" title="Phát bài hát" onclick="playlistPlayEntry(this)"><i class="bi bi-play-circle"></i></button>' +
                (externalUrl ? '<a href="' + playlistEscapeHtml(externalUrl) + '" target="_blank" rel="noopener noreferrer"><button type="button" class="btn btn-info btn-sm" title="Mở trong tab mới"><i class="bi bi-box-arrow-up-right"></i></button></a>' : '') +
                '<button class="btn btn-danger btn-sm" data-entry-index="' + index + '" title="Xóa khỏi danh sách phát" onclick="playlistDeleteEntry(this)"><i class="bi bi-trash"></i></button>' +
                '</td>' +
                '</tr>';
            });
            showMessagePHP("Lấy dữ liệu PlayList, danh sách phát thành công", 5);
          } else {
            fileInfo = '<tr><td colspan="3">Không có dữ liệu</td></tr>';
          }
          tableBody.innerHTML = fileInfo;
          playlistRenderStats(playlistRenderedItems);
          playlistMarkPlaying(playlistRuntimeId, playlistRuntimeTitle);
          playlistApplyFilterPage(true);
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
      const xhr = vbotCreateXhr();
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
    let isPaused_SongNHAC = false;
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
        if (!isPaused_SongNHAC) {
          time_SongNhac += 0.05;
        }
      } else {
        document.getElementById("waveContainer_song_nhac").style.display = "none";
      }
      requestAnimationFrame(drawWaves);
    }

    function updateDisplay_SongNhac(status_SN, paused_SN = false) {
      const nextStatus = `${status_SN}:${paused_SN}`;
      if (nextStatus === previousStatus_SongNHAC) return;
      previousStatus_SongNHAC = nextStatus;
      currentStatus_SongNHAC = status_SN;
      isPaused_SongNHAC = status_SN && paused_SN;
      // Khi tải trang trong lúc media đã pause, dựng một khung sóng rõ ràng
      // thay vì giữ pha 0 (hai đường sóng gần như phẳng).
      if (isPaused_SongNHAC && time_SongNhac === 0) {
        time_SongNhac = 1.25;
      }
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
