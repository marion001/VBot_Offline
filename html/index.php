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
$_SESSION['user_login']['login_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="vi">
<!--
Code By: Vũ Tuyển
Designed by: BootstrapMade
Facebook: https://www.facebook.com/TWFyaW9uMDAx
-->
<?php
include 'html_head.php';
?>
<head>
<!-- CSS thanh trượt Volume index.php -->
    <style>

        .volume-slider {
            position: relative;
            width: 50px;
            height: 150px;
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
            height: 150px;
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


  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Bảng điều khiển</h1>
	  

	  
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">bảng điều khiển</li>
        </ol>
      </nav>

    </div><!-- End Page Title -->

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
              <div class="card info-card revenue-card" style="background-color: #f8f9fa;">
                <div class="card-body">
                  <h5 class="card-title" id="show_city">N/A</h5>
 <font color=green><div id="show_description"></div></font>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                     <img id="weather-icon"></img>
                    </div>
                    <div class="ps-3">
                      <h6 id="show_weather">N/A</h6>
                     <span class="text-muted small pt-2 ps-1">Độ ẩm: </span>  <span class="text-success small pt-1 fw-bold" id="show_humidity">N/A</span>
                     <br/><span class="text-muted small pt-2 ps-1">Gió: </span>  <span class="text-success small pt-1 fw-bold" id="show_windSpeed">N/A</span>

                    </div>
				
                  </div>
				
                </div>

              </div>
            </div><!-- End Revenue Card -->

            <div class="col-xxl-4 col-md-6">
              <div class="card info-card revenue-card" style="background-color: #f8f9fa;">
                <div class="card-body">
                  <h5 class="card-title" id="show_wifi_name">N/A</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                     <i class="bi bi-wifi"></i>
                    </div>
                    <div class="ps-3">
                      <b><h5 id="show_bit_rate">N/A</h5></b>
                     <span class="text-muted small pt-2 ps-1">Tần số: </span>  <span class="text-success small pt-1 fw-bold" id="show_frequency">N/A</span>
                     <br/><span class="text-muted small pt-2 ps-1">Tx_Power: </span>  <span class="text-success small pt-1 fw-bold" id="show_Tx_Power">N/A</span>
                     <br/><span class="text-muted small pt-2 ps-1">Link_Quality: </span>  <span class="text-success small pt-1 fw-bold" id="show_Link_Quality">N/A</span>

                    </div>
				
                  </div>
				
                </div>

              </div>
            </div><!-- End Revenue Card -->
            <!-- Revenue Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card revenue-card" style="background-color: #f8f9fa;">
                <div class="card-body">
                  

                  <div class="d-flex justify-content-around">
                   
					
					  <div class="ps-3" title="Kéo để thay đổi âm lượng của thiết bị">
					<font color="blue">Âm lượng</font>
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

<font color="blue">Độ sáng</font>
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
            </div><!-- End Revenue Card -->





 


            <!-- Reports -->
            <div class="col-12">
              <div class="card">


                <div class="card-body">
                 
                        <div class="card-title">
<label>Trình Phát Đa Phương Tiện:</label>

</div>


<div id="media-container">
    <img id="media-cover" src="assets/img/Error_Null_Media_Player.png" alt="Media Cover">
    <div id="media-info">
        <p id="media-name">Tên bài hát: <font color="blue">N/A</font></p>
        <p id="audio-playing">Trạng thái: <font color="blue">N/A</font></p>
        <p id="audio-source">Nguồn Media: <font color="blue">N/A</font></p>
    </div>
</div>

<div id="progress-container">
    <input type="range" id="progress-bar" min="0" max="100" value="0" title="Kéo để tua khi đang phát nhạc">
    <div id="time-info"><font color=red>00:00:00 / 00:00:00</font></div>
</div>


<center>
<!--
   <button type="button" id="volumeDOWN_Button" name="volumeDOWN_Button" title="Giảm âm lượng" class="btn btn-primary" onclick="control_volume('down')"><i class="bi bi-volume-down-fill"></i>
                        </button>
-->
                        <button type="button" id="play_Button" name="play_Button" title="Phát nhạc" class="btn btn-success" onclick="control_media('resume')"><i class="bi bi-play-circle"></i>
                        </button>
                        <button type="button" id="pause_Button" name="pause_Button" title="Tạm dừng phát nhạc" class="btn btn-warning" onclick="control_media('pause')"><i class="bi bi-pause-circle"></i>
                        </button>
                        <button type="button" id="stop_Button" name="stop_Button" title="Dừng phát nhạc" class="btn btn-danger" onclick="control_media('stop')"><i class="bi bi-stop-circle"></i>
                        </button>
						<button type="button" class="btn btn-primary" title="Hiển thị PlayList, Danh sách phát" onclick="loadPlayList()">
						<i class="bi bi-music-note-list"></i>
						</button>
						
<!--
   <button type="button" id="volumeUP_Button" name="volumeUP_Button" title="Tăng âm lượng" class="btn btn-primary" onclick="control_volume('up')"><i class="bi bi-volume-up-fill"></i>
</button>
-->
</center><br/>
 <center><button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát trước đó" class="btn btn-success" onclick="playlist_media_control('prev')"><i class="bi bi-music-note-list"></i> <i class="bi bi-skip-backward-fill"></i></button>
<button type="button" id="play_Button" name="play_Button" title="Phát nhạc trong Play List" class="btn btn-primary" onclick="playlist_media_control()"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button>
<button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát kế tiếp" class="btn btn-success" onclick="playlist_media_control('next')"><i class="bi bi-skip-forward-fill"></i> <i class="bi bi-music-note-list"></i></button></center>
                </div>
				<hr/>
<div class="card-body">	
<select class="form-select border-success" title="Chọn nguồn nhạc để phát hoặc tìm kiếm" id="select_cache_media">
  <option value="" selected>Chọn nguồn nhạc</option>
  <option value="Local">Local (Nội bộ)</option>
  <option value="Youtube">Youtube</option>
  <option value="ZingMP3">ZingMP3</option>
  <option value="PodCast">PodCast</option>
  <option value="Radio">Đài, Radio</option>
  <option value="NewsPaper">Báo, Tin Tức</option>
</select>
</div>
     <div class="card-body">
           <!--  <div id="show_list_ZingMP3"></div> -->
            <div id="NewsPaper_Select" style="display: none;">
			

<?php

// Kiểm tra nếu mảng news_paper_data tồn tại
if (isset($Config['media_player']['news_paper_data']) && is_array($Config['media_player']['news_paper_data'])) {
    // Bắt đầu thẻ <select>
    echo '<div class="input-group form-floating mb-3">	<select class="form-select border-success" name="news_paper" id="news_paper">';
    echo '<option value="">-- Chọn Báo, Tin Tức --</option>';
    foreach ($Config['media_player']['news_paper_data'] as $newsPaper) {
        // Kiểm tra và lấy các trường `name` và `link`
        $name = isset($newsPaper['name']) ? htmlspecialchars($newsPaper['name']) : 'Không rõ tên';
        $link = isset($newsPaper['link']) ? htmlspecialchars($newsPaper['link']) : '#';
        echo '<option value="'.$link.'" title="Báo: '.$name.'">'.$name.'</option>';
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
            </div><!-- End Reports -->



            <!-- TTS -->
            <div class="col-12">
              <div class="card">


                <div class="card-body">
                 

              <h5 class="card-title">Phát Thông Báo <i class="bi bi-megaphone"></i> <i class="bi bi-question-circle-fill" onclick="show_message('Phát nội dung cần thông báo ra loa')"></i> <span> | Text to Speak</span></h5>
		
			<div class="form-floating mb-3">  
<textarea type="text" class="form-control border-success" style="height: 100px;"  name="tts_speaker_notify" id="tts_speaker_notify">
</textarea>
 <label for="tts_speaker_notify" class="form-label">Nhập nội dung cần thông báo</label>	
 <br/>
 <center>
 <button type="button" class="btn btn-primary" onclick="tts_speaker_notify_send()" title="Phát nội dung thông báo ra loa"><i class="bi bi-megaphone"></i> Phát</button>
 <button class="btn btn-danger" title="Xóa toàn bộ nội dung thông báo" onclick="tts_speaker_notify_send('delete_text_tts')" title="Xóa nội dung đã nhập trong nhập liệu thông báo"><i class="bi bi-trash"></i></button>
 <button class="btn btn-warning" id="download_tts_audio" onclick="showMessagePHP('Không có dữ liệu để tải xuống', 5)" title="Tải xuống tệp âm thanh đã phát thông báo"><i class="bi bi-download"></i></button>
 <button class="btn btn-success" id="playAudio_tts_audio" onclick="showMessagePHP('Không có dữ liệu để phát', 5)" title="Nghe tệp âm thanh đã phát thông báo trực tiếp trên thiết bị"><i class="bi bi-play"></i></button>
  </center>
</div>
                </div>
              </div>
            </div><!-- End TTS -->
          </div>
        </div><!-- End Left side columns -->

        <!-- Right side columns -->
        <div class="col-lg-4">

          <!-- Chức năng chung -->
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Chức Năng Chung:</span></h5>

              <div class="activity">


<div class="activity-item d-flex">
	  	  <div class="form-switch">
<input class="form-check-input" title="Đồng bộ VBot với Web UI" type="checkbox" name="sync_checkbox" id="sync_checkbox" <?php echo $Config['media_player']['media_sync_ui']['active'] ? 'checked' : ''; ?>>
</div>
<i class="bi bi-dash-lg"></i>
    <div class="activity-content">
<b><font color="green">Đồng bộ, Sync <i class="bi bi-question-circle-fill" onclick="show_message('Đồng bộ trạng thái và dữ liệu của Bot với Web UI theo thời gian thực<br/>- Tắt hoặc thiết lập thời gian trễ trong: <b>Cấu hình Config -> Cấu Hình Media Player -> Đồng bộ trạng thái Media với Web UI</b> ')"></i></font></b>
</div>
</div>

<!--
<div class="activity-item d-flex">
    <div class="form-switch">
        <input class="form-check-input" type="checkbox" id="bluetooth_power" name="bluetooth_power" onclick="bluetooth_control('power', this.checked)">
    </div>
    <i class="bi bi-dash-lg"></i>
    <div class="activity-content">
   <b><font color="green">Bluetooth Power <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt nguồn Bluetooth')"></i></font></b>
    </div>
</div>
-->
<div class="activity-item d-flex">
    <div class="form-switch">
        <input class="form-check-input" type="checkbox" id="media_player_active" name="media_player_active" onclick="change_to_another_mode('media_player_active', this.checked)" <?php echo $Config['media_player']['active'] ? 'checked' : ''; ?>>
    </div>
    <i class="bi bi-dash-lg"></i>
    <div class="activity-content">
   <b><font color="green">Media Player <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt Để kích hoạt sử dụng trình phát nhạc Media Player Khi được tắt sẽ không ra lệnh phát được Bài Hát, PodCast, Radio, v..v...')"></i></font></b>
    </div>
</div>

<div class="activity-item d-flex">
    <div class="form-switch">
        <input class="form-check-input" type="checkbox" id="wake_up_in_media_player" name="wake_up_in_media_player" onclick="change_to_another_mode('wake_up_in_media_player', this.checked)" <?php echo $Config['media_player']['wake_up_in_media_player'] ? 'checked' : ''; ?>>
    </div>
    <i class="bi bi-dash-lg"></i>
    <div class="activity-content">
   <b><font color="green">Wake Up in Media Player <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt Để Cho Phép Đánh Thức Khi Đang Phát Media player')"></i></font></b>
    </div>
</div>

<div class="activity-item d-flex">
    <div class="form-switch">
        <input class="form-check-input" type="checkbox" id="cache_tts" name="cache_tts" onclick="change_to_another_mode('cache_tts', this.checked)" <?php echo $Config['smart_config']['smart_answer']['cache_tts']['active'] ? 'checked' : ''; ?>>
    </div>
    <i class="bi bi-dash-lg"></i>
    <div class="activity-content">
   <b><font color="green">Cache lại kết quả TTS</font></b>
    </div>
</div>

<div class="activity-item d-flex">
    <div class="form-switch">
        <input class="form-check-input" type="checkbox" name="show_mic_on_off" id="show_mic_on_off" onclick="change_to_another_mode('mic_on_off', this.checked)">
    </div>
    <i class="bi bi-dash-lg"></i>
    <div class="activity-content">
        <b><font color="green">Mic, Microphone</font></b>
    </div>
</div>

<div class="activity-item d-flex">
    <div class="form-switch">
        <input class="form-check-input" type="checkbox" name="show_conversation_mode" id="show_conversation_mode" onclick="change_to_another_mode('conversation_mode', this.checked)" <?php echo $Config['smart_config']['smart_wakeup']['conversation_mode'] ? 'checked' : ''; ?>>
    </div>
    <i class="bi bi-dash-lg"></i>
    <div class="activity-content">
        <b><font color="green">Chế độ hội thoại <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sẽ hỏi đáp, lắng nghe liên tục mà không cần đánh thức Wake UP lại Bot')"></i></font></b>
    </div>
</div>

<div class="activity-item d-flex">
    <div class="form-switch">
        <input class="form-check-input" type="checkbox" id="show_wake_up" name="show_wake_up" onclick="change_to_another_mode('wake_up', this.checked)">
    </div>
    <i class="bi bi-dash-lg"></i>
    <div class="activity-content">
   <b><font color="green">Đánh thức, Wake up</font></b>
    </div>
</div>

<div class="activity-item d-flex">
    <div class="form-switch">
        <input disabled class="form-check-input" type="checkbox" name="show_wakeup_reply" id="show_wakeup_reply" onclick="change_to_another_mode('wakeup_reply', this.checked)" <?php echo $Config['smart_config']['smart_wakeup']['wakeup_reply']['active'] ? 'checked' : ''; ?>>
    </div>
    <i class="bi bi-dash-lg"></i>
    <div class="activity-content">
        <i class="bi bi-ban"></i><b class="text-decoration-line-through"><font color="green" disabled> Bật, Tắt Chế độ câu phản hồi</font></b>
		
    </div>
</div>


              </div>

            </div>
          </div><!-- kết thúc chức năng chung -->




          <!-- Chức Năng Khác -->
          <div class="card">
            <div class="card-body pb-0">
              <h5 class="card-title">Chế Độ Khác:</h5>

              <div id="budgetChart" class="echart">
<ul>
 

 
 
  <li>  <font color="blue">Home Assistant:</font>
   
  <div class="form-switch">
 
<div class="form-check">
  <input class="form-check-input" value="home_assistant_active" type="checkbox" name="home_assistant_active" id="home_assistant_active" onclick="change_to_another_mode('home_assistant', this.checked)" <?php if ($Config['home_assistant']['active'] === true) echo "checked"; ?>>
 <label class="form-check-label">
    Home Assistant <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để liên kết và điều khiển nhà thông minh')"></i>
  </label>
</div>

<div class="form-check">
 <input class="form-check-input" value="hass_custom_commands_active" type="checkbox" name="hass_custom_commands_active" id="hass_custom_commands_active" onclick="change_to_another_mode('hass_custom_active', this.checked)" <?php if ($Config['home_assistant']['custom_commands']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Lệnh Tùy Chỉnh <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để kích hoạt sử dụng lệnh tùy chỉnh (Custom Command) để điều khiển nhà thông minh')"></i>
  </label>
</div>



</div>
  </li>
  
  
  <li>  <font color="blue">DEV Customization (Custom Skill):</font>
   
  <div class="form-switch">
 
<div class="form-check">
  <input class="form-check-input" value="developer_customization_active" type="checkbox" name="developer_customization_active" id="developer_customization_active" onclick="change_to_another_mode('dev_custom', this.checked)" <?php if ($Config['developer_customization']['active'] === false) echo "disabled"; ?> <?php if ($Config['developer_customization']['active'] === true) echo "checked"; ?>>
 <label class="form-check-label">
    Custom Skill <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt sử dụng chế độ DEV Customization (Custom Skill)')"></i>
  </label>
</div>

<div class="form-check">
 <input class="form-check-input" value="developer_customization_vbot_processing" type="checkbox" name="developer_customization_vbot_processing" id="developer_customization_vbot_processing" onclick="change_to_another_mode('dev_custom_vbot', this.checked)" <?php if ($Config['developer_customization']['active'] === false) echo "disabled"; ?><?php if ($Config['developer_customization']['if_custom_skill_can_not_handle']['vbot_processing'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Áp dụng thêm Vbot xử lý <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc Tắt để sử dụng Vbot xử lý khi Custom Skill không thể xử lý')"></i>
  </label>
</div>



</div>
  </li>
  
  
 
 
  <li><font color="blue">Trợ lý ảo <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để sử dụng trợ lý ảo tương ứng')"></i> :</font>
  <div class="form-switch">
 
<div class="form-check">
 <input class="form-check-input" value="default_assistant_active" type="checkbox" name="default_assistant_active" id="default_assistant_active" onclick="change_to_another_mode('default_assistant', this.checked)" <?php if ($Config['virtual_assistant']['default_assistant']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Default Assistant
  </label>
</div>

<div class="form-check">
 <input class="form-check-input" value="google_gemini_active" type="checkbox" name="google_gemini_active" id="google_gemini_active" onclick="change_to_another_mode('google_gemini', this.checked)" <?php if ($Config['virtual_assistant']['google_gemini']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Google Gemini
  </label>
</div>

<div class="form-check">
 <input class="form-check-input" value="chat_gpt_active" type="checkbox" name="chat_gpt_active" id="chat_gpt_active" onclick="change_to_another_mode('chat_gpt', this.checked)" <?php if ($Config['virtual_assistant']['chat_gpt']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Chat GPT
  </label>
</div>

<div class="form-check">
 <input class="form-check-input" value="zalo_assistant_active" type="checkbox" name="zalo_assistant_active" id="zalo_assistant_active" onclick="change_to_another_mode('zalo_assistant', this.checked)" <?php if ($Config['virtual_assistant']['zalo_assistant']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Zalo AI Assistant
  </label>
</div>

<div class="form-check">
 <input class="form-check-input" value="dify_ai_active" type="checkbox" name="dify_ai_active" id="dify_ai_active" onclick="change_to_another_mode('dify_ai', this.checked)" <?php if ($Config['virtual_assistant']['dify_ai']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Dify AI
  </label>
</div>

</div>
  </li>
  
 
  <li><font color="blue">Nguồn Nhạc <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để sử dụng nguồn Nhạc, Radio, PodCast tương ứng, khi Bot tìm kiếm dữ liệu')"></i> :</font>
  <div class="form-switch">
 
<div class="form-check">
 <input class="form-check-input" value="music_local_active" type="checkbox" name="music_local_active" id="music_local_active" onclick="change_to_another_mode('music_local', this.checked)" <?php if ($Config['media_player']['music_local']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Music Local
  </label>
</div>

<div class="form-check">
 <input class="form-check-input" value="zing_mp3_active" type="checkbox" name="zing_mp3_active" id="zing_mp3_active" onclick="change_to_another_mode('zing_mp3', this.checked)" <?php if ($Config['media_player']['zing_mp3']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Zing MP3
  </label>
</div>

<div class="form-check">
 <input class="form-check-input" value="youtube_active" type="checkbox" name="youtube_active" id="youtube_active" onclick="change_to_another_mode('youtube', this.checked)" <?php if ($Config['media_player']['youtube']['active'] === true) echo "checked"; ?>>
  <label class="form-check-label">
    Youtube
  </label>
</div>


</div>
  </li>

 
<li><font color="blue">Đọc Báo, Tin Tức <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để sử dụng tính năng đọc báo, tin tức trong ngày')"></i> :</font>
  <div class="form-switch">
<div class="form-check">
 <input class="form-check-input" value="news_paper_active" type="checkbox" name="news_paper_active" id="news_paper_active" onclick="change_to_another_mode('news_paper', this.checked)" <?php if ($Config['media_player']['news_paper']['active'] === true) echo "checked"; ?>>
</div>
</div>
</li>


<li><font color="blue">Màn Hình (Display Screen) <i class="bi bi-question-circle-fill" onclick="show_message('Bật hoặc tắt để sử dụng, hiển thị dữ liệu lên màn hình')"></i> :</font>
  <div class="form-switch">
<div class="form-check">
 <input class="form-check-input" value="display_screen_active" type="checkbox" name="display_screen_active" id="display_screen_active" onclick="change_to_another_mode('display_screen', this.checked)" <?php if ($Config['display_screen']['active'] === true) echo "checked"; ?>>
</div>
</div>
</li>

</ul>
</div>
            </div>
          </div><!-- Kết Chức năng khác -->
		  
		  


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
  <input class="form-check-input" value="on_off_display_logs" type="checkbox" name="on_off_display_logs" id="on_off_display_logs" onclick="change_og_display_style('change_log', this.checked ? 'on' : 'off', true)" <?php if ($Config['smart_config']['show_log']['active'] === true) echo "checked"; ?>>
 
<!-- <label class="form-check-label">
    Xóa Log API
  </label>
  -->
</div>
</div>
  </li>
 
 
 
  <li><font color="blue">Thay đổi chế độ hiển thị Logs <i class="bi bi-question-circle-fill" onclick="show_message('Thay đổi chế độ hiển thị Logs đầu ra trực tiếp và lấy dữ liệu theo thời gian thực')"></i> :</font>
  <div class="form-switch">
 
      <div class="form-check">
  <input class="form-check-input" value="console" type="radio" name="select_log_display_style" id="log_display_style_console" onclick="change_og_display_style('change_log', 'console', this.checked)" <?php if ($Config['smart_config']['show_log']['log_display_style'] === "console") echo "checked"; ?>>
  <label class="form-check-label">
    Console
  </label>
</div>

      <div class="form-check">
  <input <?php if ($Config['display_screen']['active'] === false) echo "disabled"; ?> class="form-check-input" value="display_screen" type="radio" name="select_log_display_style" id="log_display_style_oled_display" onclick="change_og_display_style('change_log', 'display_screen', this.checked)" <?php if ($Config['smart_config']['show_log']['log_display_style'] === "display_screen") echo "checked"; ?>>
  <label class="form-check-label">
    Màn Hình
  </label>
</div>

      <div class="form-check">
  <input <?php if ($Config['api']['active'] === false) echo "disabled"; ?> class="form-check-input" value="api" type="radio" name="select_log_display_style" id="log_display_style_api" onclick="change_og_display_style('change_log', 'api', this.checked)" <?php if ($Config['smart_config']['show_log']['log_display_style'] === "api") echo "checked"; ?>>
  <label class="form-check-label">
    API <a href="<?php echo $Protocol.$serverIp.':'.$Port_API; ?>/logs" target="_bank" title="Mở URL Logs API trong tab mới"> <i class="bi bi-box-arrow-up-right"></i></a>
  </label>
</div>
     <div class="form-check">
  <input class="form-check-input" value="all" type="radio" name="select_log_display_style" id="log_display_style_both" onclick="change_og_display_style('change_log', 'all', this.checked)" <?php if ($Config['smart_config']['show_log']['log_display_style'] === "all") echo "checked"; ?>>
  <label class="form-check-label">
    ALL (Tất Cả) <a href="<?php echo $Protocol.$serverIp.':'.$Port_API; ?>/logs" target="_bank" title="Mở URL Logs API trong tab mới"> <i class="bi bi-box-arrow-up-right"></i></a>
  </label>
</div>

</div>
  </li>
  <li>
  <font color="blue">Dọn dẹp Logs</font>
    <div class="form-switch">
       <div class="form-check">
  <input class="form-check-input" value="log_api_del" type="radio" name="delete_log_api" id="delete_log_api" onclick="change_og_display_style('clear_api', 'clear_api', this.checked)">
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
  <input class="form-check-input" value="mqtt_show_logs_reconnect" type="checkbox" name="mqtt_show_logs_reconnect" id="mqtt_show_logs_reconnect" onclick="change_og_display_style('mqtt_show_logs_reconnect', this.checked ? 'on' : 'off', true)" <?php if ($Config['mqtt_broker']['mqtt_show_logs_reconnect'] === true) echo "checked"; ?>>

</div>
</div>
  </li>

</ul>
</div>
            </div>
          </div><!-- Kết thúc Logs hệ thống -->



        </div><!-- End Right side columns -->

      </div>
    </section>

  </main><!-- End #main -->

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
    function getLocationAndWeather() {
            var xhr = new XMLHttpRequest();
            xhr.addEventListener('readystatechange', function() {
                if (this.readyState === 4) {
                    // Chuyển đổi dữ liệu JSON thành đối tượng JavaScript
                    var data = JSON.parse(this.responseText);
                    // Lấy các giá trị cần thiết từ dữ liệu IP
                    var city = data.city;
                    var loc = data.loc;
                    var country = data.country;
                    // Tách giá trị lat và lon từ loc
                    var locArray = loc.split(',');
                    var lat = locArray[0];
                    var lon = locArray[1];
                    // Khởi tạo một đối tượng XMLHttpRequest để lấy thông tin thời tiết
                    var xhrWeather = new XMLHttpRequest();
                    xhrWeather.addEventListener('readystatechange', function() {
                        if (this.readyState === 4) {
                            // Chuyển đổi dữ liệu thời tiết JSON thành đối tượng JavaScript
                            var weatherData = JSON.parse(this.responseText);
                            var icon = 'https://openweathermap.org/img/w/' + weatherData.weather[0].icon + '.png';
                            document.getElementById('show_weather').textContent = weatherData.main.temp + '°C';
                            document.getElementById('show_humidity').textContent = weatherData.main.humidity + '%';
                            document.getElementById('show_description').textContent = ' ' + weatherData.weather[0].description;
                            document.getElementById('show_windSpeed').textContent = weatherData.wind.speed + ' m/s';
                            document.getElementById('weather-icon').src = icon;
                            document.getElementById('show_city').innerHTML = city + ', <span>' + weatherData.sys.country + '</span>';
                        }
                    });

                    var weatherUrl = 'https://api.openweathermap.org/data/2.5/weather?lat=' + lat + '&lon=' + lon + '&appid=8473858601dabd3d2cbb24fb50840686&units=metric&lang=vi';
                    xhrWeather.open('GET', weatherUrl);
                    // Gửi request để lấy thời tiết
                    xhrWeather.send();
                }
            });
            xhr.open('GET', 'https://ipinfo.io/json');
            // Gửi request để lấy thông tin IP
            xhr.send();
        }
        // Gọi hàm để hiển thị thông tin vị trí và thời tiết
    getLocationAndWeather();

    //Cập nhật và hiển thị giá trị led vào thẻ html 
    function updateBrightness(value) {
        const brightnessSlider = document.getElementById('led_brightness-slider');
        const brightnessBar = document.getElementById('led_brightness-bar');
        const brightnessKnob = document.getElementById('led_brightness-knob');
        const brightnessPercentage = document.getElementById('led_brightness-percentage');
        const height = brightnessSlider.clientHeight;
        const percentage = Math.max(0, Math.min(100, (value / 255) * 100));
        brightnessBar.style.height = percentage + '%';
        brightnessKnob.style.top = (height - (percentage / 100) * height) + 'px';
        brightnessPercentage.textContent = Math.round((value / 255) * 100) + '%';
    }

    //Cập nhật giá trị volume vào id="volume-slider" html
    function set_Volume_HTML(volume) {
        // Lấy các phần tử từ ID
        const volumeSlider = document.getElementById('volume-slider');
        const volumeBar = document.getElementById('volume-bar');
        const volumeKnob = document.getElementById('volume-knob');
        const volumePercentage = document.getElementById('volume-percentage');
        const height = volumeSlider.getBoundingClientRect().height;
        volumeBar.style.height = volume + '%';
        volumeKnob.style.top = (height - (volume / 100) * height) + 'px';
        volumePercentage.textContent = Math.round(volume) + '%';
    }

    // Định dạng thời gian thành HH:MM:SS
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
        var data = JSON.stringify({
            "type": 1,
            "data": "media_control",
            "action": "set_time",
            "set_duration": set_duration
        });

        var xhr = new XMLHttpRequest();
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                try {
                    if (this.status === 0) {
                        showMessagePHP('Lỗi: Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API, và Bot đã hoạt động chưa');
                        return;
                    } else if (this.status !== 200) {
                        showMessagePHP('Lỗi: Mã trạng thái HTTP ' + this.status);
                        return;
                    }
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        showMessagePHP(response.message, 5);
                    } else {
                        showMessagePHP('Lỗi: ' + response.message);
                    }
                } catch (error) {
                    showMessagePHP('Đã xảy ra lỗi trong quá trình xử lý: ' + error.message);
                }
            }
        });
        xhr.open("POST", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(data);
    }

    //Thay đổi giá trị của biến toàn cục, chế độ hội thoại, chế độ phản hồi, Mic, Wakeup
    function change_to_another_mode(dataKey, actionValue) {
        var data = JSON.stringify({
            "type": 2,
            "data": dataKey,
            "action": actionValue // true hoặc false tùy vào giá trị truyền vào
        });

        var xhr = new XMLHttpRequest();
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                try {
                    if (this.status === 0) {
                        show_message('Lỗi: Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API, và Bot đã hoạt động chưa');
                        return;
                    } else if (this.status !== 200) {
                        show_message('Lỗi: Mã trạng thái HTTP ' + this.status);
                        return;
                    }
                    // Nếu dataKey là "wake_up", bỏ chọn checkbox
                    if (dataKey === "wake_up") {
                        document.getElementById('show_wake_up').checked = false;
                    }
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        showMessagePHP(response.message, 5);
                    } else {
                        show_message('Lỗi: ' + response.message);
                    }
                } catch (error) {
                    show_message('Đã xảy ra lỗi trong quá trình xử lý: ' + error.message);
                }
            }
        });

        xhr.open("POST", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(data);
    }

    //Gửi dữ liệu thay đổi volume tới Bot
    function set_Volume_Data(volume) {
            var data = JSON.stringify({
                "type": 2,
                "data": "volume",
                "action": "setup",
                "value": volume
            });

            var xhr = new XMLHttpRequest();
            xhr.addEventListener("readystatechange", function() {
                if (this.readyState === 4) {
                    try {
                        if (this.status === 0) {
                            show_message('Lỗi: Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API, và Bot đã hoạt động chưa');
                            return;
                        } else if (this.status !== 200) {
                            show_message('Lỗi: Mã trạng thái HTTP ' + this.status);
                            return;
                        }
                        var response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Cập nhật lại thanh trượt với giá trị từ phản hồi
                            set_Volume_HTML(response.volume);
                            showMessagePHP("Âm lượng đã được thay đổi thành: " + response.volume + "%", 5);
                        } else {
                            show_message('Lỗi: ' + response.message);
                        }
                    } catch (error) {
                        show_message('Đã xảy ra lỗi trong quá trình xử lý: ' + error.message);
                    }
                }
            });

            xhr.open("POST", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>");
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.send(data);
        }
        //Thay đổi độ sáng đèn led
    function sendBrightnessData(value) {
        var data = JSON.stringify({
            "type": 2,
            "data": "led",
			"action": "brightness",
            "value": value
        });
        var xhr = new XMLHttpRequest();

        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                try {
                    if (this.status === 0) {
                        show_message('Lỗi: Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API, và Bot đã hoạt động chưa');
                        return;
                    } else if (this.status !== 200) {
                        show_message('Lỗi: Mã trạng thái HTTP ' + this.status);
                        return;
                    }
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        showMessagePHP(response.message, 5);
                    } else {
                        show_message('Lỗi: ' + response.message);
                    }
                } catch (error) {
                    show_message('Đã xảy ra lỗi trong quá trình xử lý: ' + error.message);
                }
            }
        });
        xhr.open("POST", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(data);
    }

    //Phát TTS notify phát thông báo
    function tts_speaker_notify_send(del_text_input=null) {
		var text_input = document.getElementById('tts_speaker_notify');
		
    // Kiểm tra xem có cần xóa nội dung của textarea không
    if (del_text_input === "delete_text_tts"){
        text_input.value = ''; // Xóa nội dung trong textarea
        showMessagePHP("Đã xóa nội dung trong nhập liệu thông báo", 5);
        return;
    }
		
		//var text_input = document.getElementById('tts_speaker_notify').value;
			if (!text_input.value){
				show_message("Hãy nhập nội dung cần phát thông báo");
				return;
			}
			loading("show")
            var data = JSON.stringify({
                "type": 3,
                "data": "tts",
                "action": "notify",
                "value": text_input.value
            });

            var xhr = new XMLHttpRequest();
            xhr.addEventListener("readystatechange", function() {
                if (this.readyState === 4) {
                    try {
                        if (this.status === 0) {
							loading("hide")
                            show_message('Lỗi: Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API, và Bot đã hoạt động chưa');
                            return;
                        } else if (this.status !== 200) {
							loading("hide")
                            show_message('Lỗi: Mã trạng thái HTTP ' + this.status);
                            return;
                        }
                        var response = JSON.parse(this.responseText);
                        if (response.success) {
							loading("hide")
                            showMessagePHP("Đã phát thông báo: " + response.text_tts, 7);
							// Cập nhật onclick của nút download với đường dẫn tệp âm thanh
 
                    var audioPath_tts = response.audio_tts;
                    
                    // Kiểm tra nếu audioPath bắt đầu bằng 'TTS_Audio'
                    if (audioPath_tts.startsWith('TTS_Audio')) {
                        audioPath_tts = '<?php echo $VBot_Offline; ?>'+audioPath_tts;
                    }

                    // Cập nhật onclick của nút download với đường dẫn tệp âm thanh
                    var downloadButton = document.getElementById('download_tts_audio');
                    var playAudio_tts_audio = document.getElementById('playAudio_tts_audio');
                    downloadButton.setAttribute('onclick', "downloadFile('" + audioPath_tts + "')");
                    playAudio_tts_audio.setAttribute('onclick', "playAudio('" + audioPath_tts + "')");
                
                        } else {
							loading("hide")
                            show_message('Lỗi: ' + response.message);
                        }
						
                    } catch (error) {
						loading("hide")
                        show_message('Đã xảy ra lỗi trong quá trình xử lý: ' + error.message);
                    }
                }
            });

            xhr.open("POST", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>");
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.send(data);
        }
</script>

<script>
    //script liên quan tới API GET Media Player
    let isHovering = false;
    let isHovering_volume_slide = false;
    let isHovering_led_brightness = false;
    let fullTime = 0; // lưu trữ toàn bộ thời gian bài nhạc
    let intervalId;
    // Cập nhật thông tin GET từ API
    function fetchData_all_info() {
        // Kiểm tra nếu checkbox được tích hoặc sync_active là true
        const syncCheckbox = document.getElementById('sync_checkbox');
		var rlc_log_display_style;
        // Không thực hiện fetchData_Media_Player nếu checkbox không được tích
        if (!syncCheckbox.checked) {
            return;
        }
        fetch("<?php echo $Protocol.$serverIp.':'.$Port_API; ?>/?type=1&data=all_info")
            .then(response => {
                if (!response.ok) {
                    document.getElementById('div_message_error').style.display = 'block';
                    document.getElementById('message_error').innerHTML = 'Không thể kết nối đến API, Vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa, Mã Lỗi: ' + response.status;
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    //console.log(data);
                    document.getElementById('div_message_error').style.display = 'none';
                    // Cập nhật các phần tử khác
                    document.getElementById('show_conversation_mode').checked = data.conversation_mode ? true : false;
                    document.getElementById('show_wakeup_reply').checked = data.wakeup_reply ? true : false;
                    document.getElementById('show_mic_on_off').checked = data.mic_on_off ? true : false;
                    document.getElementById('on_off_display_logs').checked = data.log_display_active ? true : false;
                    document.getElementById('mqtt_show_logs_reconnect').checked = data.mqtt_show_logs_reconnect ? true : false;
                    document.getElementById('cache_tts').checked = data.cache_tts_active ? true : false;
                    document.getElementById('media_player_active').checked = data.media_player.media_player_active ? true : false;
                    document.getElementById('bluetooth_power').checked = data.bluetooth_power ? true : false;
                    document.getElementById('wake_up_in_media_player').checked = data.media_player.wake_up_in_media_player ? true : false;
                    document.getElementById('music_local_active').checked = data.media_player.music_local_active ? true : false;
                    document.getElementById('zing_mp3_active').checked = data.media_player.zing_mp3_active ? true : false;
                    document.getElementById('youtube_active').checked = data.media_player.youtube_active ? true : false;
                    document.getElementById('news_paper_active').checked = data.news_paper_active ? true : false;
                    document.getElementById('display_screen_active').checked = data.display_screen_active ? true : false;
                    document.getElementById('home_assistant_active').checked = data.home_assistant_active ? true : false;
                    document.getElementById('hass_custom_commands_active').checked = data.hass_custom_commands_active ? true : false;
                    document.getElementById('default_assistant_active').checked = data.default_assistant_active ? true : false;
                    document.getElementById('google_gemini_active').checked = data.google_gemini_active ? true : false;
                    document.getElementById('chat_gpt_active').checked = data.chat_gpt_active ? true : false;
                    document.getElementById('zalo_assistant_active').checked = data.zalo_assistant_active ? true : false;
                    document.getElementById('dify_ai_active').checked = data.dify_ai_active ? true : false;
                    document.getElementById('developer_customization_active').checked = data.dev_custom ? true : false;
                    document.getElementById('developer_customization_vbot_processing').checked = data.dev_custom_vbot ? true : false;
                    //document.getElementById('show_wake_up').checked = false;
                    //Media Player
                    document.getElementById('media-name').innerHTML = 'Tên bài hát: <font color="blue">' + (data.media_player.media_name ? data.media_player.media_name : 'N/A') + '</font>';
                    document.getElementById('audio-playing').innerHTML = 'Đang phát: <font color=blue>' + (data.media_player.audio_playing ? 'Có' : 'Không') + '</font>';
                    document.getElementById('audio-source').innerHTML = 'Nguồn Media: <font color=blue>' + data.media_player.media_player_source + '</font>';
                    // Cập nhật ảnh cover bài hát
                    document.getElementById('media-cover').src = data.media_player.media_cover ? data.media_player.media_cover : 'assets/img/Error_Null_Media_Player.png';
                    // Cập nhật giá trị full time
                    fullTime = data.media_player.full_time;
					//Log thay đổi chế độ đầu ra
					if (data.log_display_style === "console"){
						document.getElementById('log_display_style_console').checked = true;
						rlc_log_display_style = "Console";
					}else if (data.log_display_style === "display_screen"){
						document.getElementById('log_display_style_oled_display').checked = true;
						rlc_log_display_style = "Display Screen";
					}else if (data.log_display_style === "api"){
						document.getElementById('log_display_style_api').checked = true;
						rlc_log_display_style = "API";
					}else if (data.log_display_style === "all"){
						document.getElementById('log_display_style_both').checked = true;
						rlc_log_display_style = "ALL";
					}
					document.getElementById('show_log_name_log_display_style').innerHTML = ' | <font color=green>'+rlc_log_display_style+'</font>';
                    if (!isHovering_led_brightness) {
                        updateBrightness(data.led_brightness);
                    }

                    // Cập nhật thanh trượt chỉ khi không đang hover
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
                    // Hiển thị thông báo lỗi khi lấy dữ liệu không thành công
                    console.log('Lỗi khi lấy dữ liệu', data.message);
                }
            })
            .catch(error => {
                // Hiển thị thẻ #div_message_error
                document.getElementById('div_message_error').style.display = 'block';
                document.getElementById('message_error').innerHTML = 'Không thể kết nối đến API, Vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa, Mã Lỗi: ' + error;
            });
    }

    // Bắt đầu lấy dữ liệu mỗi giây
    intervalId = setInterval(fetchData_all_info, <?php echo intval($Config['media_player']['media_sync_ui']['delay_time']); ?> * 1000);

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

<!-- Xử lý thanh trượt Volume -->
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
            const offsetY = e.clientY - rect.top;
            const height = rect.height;
            const percentage = Math.max(0, Math.min(100, ((height - offsetY) / height) * 100));
            const brightnessValue = Math.round((percentage / 100) * 255);
            updateBrightness(brightnessValue);
            return brightnessValue;
        }
        const brightnessSlider = document.getElementById('led_brightness-slider');
        let isDragging = false;
        brightnessSlider.addEventListener('mousedown', function(e) {
            isDragging = true;
            const brightnessValue = updateBrightnessFromEvent(e);
            sendBrightnessData(brightnessValue);
        });
        document.addEventListener('mousemove', function(e) {
            if (isDragging) {
                updateBrightnessFromEvent(e);
            }
        });
        document.addEventListener('mouseup', function(e) {
            if (isDragging) {
                isDragging = false;
                const brightnessValue = updateBrightnessFromEvent(e);
                sendBrightnessData(brightnessValue);
            }
        });
        //cập nhật hiển thị giá trị led mặc định lần đầu khi tải trang
        updateBrightness(<?php echo $Config['smart_config']['led']['brightness']; ?>);
    }
</script>

<script>
    //Lấy thông tin mạng đang kết nối
    function getWifiNetworkInformation() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'includes/php_ajax/Wifi_Act.php?Wifi_Network_Information', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
					//console.log(xhr.responseText);
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            //console.log('Dữ liệu Wi-Fi:' +xhr.responseText);
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
    getWifiNetworkInformation();
</script>
<script>
    //Lắng nghe và thực hiện khi có thay đổi trong Dom khi tải trang xong
    document.addEventListener('DOMContentLoaded', function() {

        //Touch Kéo Slide volume trên Mobile cảm ứng
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
            const percentage = Math.max(0, Math.min(100, ((height - offsetY) / height) * 100));
            const value = Math.round((percentage / 100) * 255);
            updateBrightness(value);
        }

        function handleTouchEnd_bright(e) {
            const rect = brightnessSlider_mb.getBoundingClientRect();
            const touch = e.changedTouches[0];
            const offsetY = touch.clientY - rect.top;
            const height = rect.height;
            const percentage = Math.max(0, Math.min(100, ((height - offsetY) / height) * 100));
            const value = Math.round((percentage / 100) * 255);
            // Cập nhật giao diện và gửi dữ liệu khi nhả tay ra
            updateBrightness(value);
            sendBrightnessData(value);
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
            //'<center><button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát trước đó" class="btn btn-success" onclick="playlist_media_control(\'prev\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-skip-backward-fill"></i></button> <button type="button" id="play_Button" name="play_Button" title="Phát nhạc trong Play List" class="btn btn-primary" onclick="playlist_media_control()"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button> <button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát kế tiếp" class="btn btn-success" onclick="playlist_media_control(\'next\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-skip-forward-fill"></i></button></center>' +
			'<h5 class="card-title">PlayList, Danh Sách Phát  <span>| Media Player</span></h5><h5>Xóa toàn bộ bài hát trong PlayList <button class="btn btn-danger" title="Xóa toàn bộ danh sách phát" onclick="deleteFromPlaylist(\'delete_all\')"><i class="bi bi-trash"></i> Xóa</button></h5><table class="table table-borderless datatable" id="playlistTable">' +
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
                                '<p style="margin: 0;">Mô tả: <font color="green">' + description + '</font></p>' : '') +
                            (playlist.source === 'ZingMP3' ?
                                '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color="green">' + playlist.artist + '</font></p>' +
                                '<p style="margin: 0;">Thời Lượng: <font color="green">' + playlist.duration + '</font></p>' : '') +
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
                                '<button class="btn btn-success" title="Phát: ' + playlist.title + '" onclick="get_Youtube_Link(\'' + playlist.id + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\')"><i class="bi bi-play-circle"></i></button>' +
                                '<a href="https://www.youtube.com/watch?v=' + playlist.id + '" target="_blank">' +
                                '<button class="btn btn-info" title="Mở trong tab mới: ' + playlist.title + '"><i class="bi bi-box-arrow-up-right"></i></button>' +
                                '</a>' : '') +
                            (playlist.source === 'ZingMP3' ?
                                '<button class="btn btn-success" title="Phát: ' + playlist.title + '" onclick="get_ZingMP3_Link(\'' + playlist.id + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'' + playlist.artist + '\')"><i class="bi bi-play-circle"></i></button>' : '') +
                            (playlist.source === 'PodCast' ?
                                '<button class="btn btn-success" title="Phát: ' + playlist.title + '" onclick="send_Media_Play_API(\'' + playlist.audio + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'PodCast\')"><i class="bi bi-play-circle"></i></button>' +
                                '<a href="' + playlist.audio + '" target="_blank">' +
                                '<button class="btn btn-info" title="Mở trong tab mới: ' + playlist.title + '"><i class="bi bi-box-arrow-up-right"></i></button>' +
                                '</a>' : '') +
                            (playlist.source === 'Local' ?
                                ' <button class="btn btn-success" title="Phát: ' + playlist.title + '" onclick="send_Media_Play_API(\'' + playlist.audio + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'Local\')"><i class="bi bi-play-circle"></i></button>' : '') +
                            ' <button class="btn btn-danger" title="Xóa khỏi danh sách phát: ' + playlist.title + '" onclick="deleteFromPlaylist(\'delete_some\', \'' + playlist.ids_list + '\')"><i class="bi bi-trash"></i></button>' +
                            '</td>' +
                            '</tr>';
                    });
					showMessagePHP("Lấy dữ liệu PlayList, danh sách phát thành công", 5);
                } else {
                    fileInfo = '<tr><td colspan="3">Không có dữ liệu</td></tr>';
                }
                tableBody.innerHTML = fileInfo;
                // Khởi tạo DataTable
                try {
                    new simpleDatatables.DataTable(table, {
						// Tùy chọn phân trang
                        perPageSelect: [5, 10, 15, ['All', -1]],
						// Thiết lập số lượng trang mặc định là 5
                        perPage: 5,
                        columns: [{
                                select: 0,
                                sortSequence: ['asc', 'desc']
                            }, // Sắp xếp cột thứ 1 (STT)
                            {
                                select: 1,
                                sortSequence: ['asc', 'desc']
                            } // Sắp xếp cột thứ 2 (Bài Hát)
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
//Kiểm tra thẻ select_cache_media nếu được chọn giá trị
var selectElement_select_cache_media = document.getElementById('select_cache_media');
// Thêm sự kiện lắng nghe thay đổi giá trị
selectElement_select_cache_media.addEventListener('change', function() {
    // Lấy giá trị được chọn
    var selectedValue_cache_media = selectElement_select_cache_media.value;
    if (selectedValue_cache_media === "Local"){
		document.getElementById('NewsPaper_Select').style.display = 'none';
		document.getElementById('tableContainer').style.display = '';
		media_player_search('Local');
	}else if (selectedValue_cache_media === "Youtube"){
		document.getElementById('NewsPaper_Select').style.display = 'none';
		document.getElementById('tableContainer').style.display = '';
		cacheYoutube();
	}else if (selectedValue_cache_media === "ZingMP3"){
		document.getElementById('NewsPaper_Select').style.display = 'none';
		document.getElementById('tableContainer').style.display = '';
		cacheZingMP3();
	}else if (selectedValue_cache_media === "PodCast"){
		document.getElementById('NewsPaper_Select').style.display = 'none';
		document.getElementById('tableContainer').style.display = '';
		cachePodCast()
	}else if (selectedValue_cache_media === "Radio"){
		document.getElementById('NewsPaper_Select').style.display = 'none';
		document.getElementById('tableContainer').style.display = '';
		media_player_search('Radio');
	}else if (selectedValue_cache_media === "NewsPaper"){
		document.getElementById('NewsPaper_Select').style.display = '';
		document.getElementById('tableContainer').style.display = '';
		cache_NewsPaper()
		
	}
});

</script>


</body>


</html>