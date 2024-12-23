<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';

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

$URL_Address = dirname($Current_URL);
//echo $URL_Address;
?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>

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
        <h1>Trình Phát Đa Phương Tiện</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item active">Trình phát nhạc</li>
&nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['media_player']['active'] ? '<p class="text-success" title="Media Player đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Media Player không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>

            </ol>
        </nav>
    </div>
    <!-- End Page Title -->

    <section class="section">
        <div class="row">
		   <div class="col-lg-12" id="div_message_error" style="display: none;">
		<div class="alert alert-danger alert-dismissible fade show" id="message_error" role="alert">
                
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              </div>
			  
			  
			  
		
            <div class="col-lg-6">

                <div class="card">
                    <div class="card-body">

                        <div class="card-title">
						
						<div class="form-switch">
<input class="form-check-input" type="checkbox" name="sync_checkbox" id="sync_checkbox" <?php echo $Config['media_player']['media_sync_ui']['active'] ? 'checked' : ''; ?>>
<label class="form-check-label" for="sync_checkbox" title="Thiết lập trong: Cấu hình Config->Đồng bộ trạng thái với Web UI"> Đồng Bộ</label>
</div>
						</div>


<div id="media-container">
    <img id="media-cover" src="assets/img/Error_Null_Media_Player.png" alt="Media Cover">
    <div id="media-info">
        <p id="media-name">Tên bài hát: <font color="blue">N/A</font></p>
        <p id="volume">Âm lượng: <font color="blue">N/A</font></p>
        <p id="audio-playing">Trạng thái: <font color="blue">N/A</font></p>
        <p id="audio-source">Nguồn Media: <font color="blue">N/A</font></p>
    </div>
</div>

<div id="progress-container">
    <input type="range" id="progress-bar" min="0" max="100" value="0" title="Kéo để tua khi đang phát nhạc">
    <div id="time-info"><font color=red>00:00:00 / 00:00:00</font></div>
</div>

<h5 class="card-title">Media Control:</h5>
<center>
   <button type="button" id="volumeDOWN_Button" name="volumeDOWN_Button" title="Giảm âm lượng" class="btn btn-primary" onclick="control_volume('down')"><i class="bi bi-volume-down-fill"></i>
                        </button>
						
                        <button type="button" id="play_Button" name="play_Button" title="Phát nhạc" class="btn btn-success" onclick="control_media('resume')"><i class="bi bi-play-circle"></i>
                        </button>
                        <button type="button" id="pause_Button" name="pause_Button" title="Tạm dừng phát nhạc" class="btn btn-warning" onclick="control_media('pause')"><i class="bi bi-pause-circle"></i>
                        </button>
                        <button type="button" id="stop_Button" name="stop_Button" title="Dừng phát nhạc" class="btn btn-danger" onclick="control_media('stop')"><i class="bi bi-stop-circle"></i>
                        </button>

   <button type="button" id="volumeUP_Button" name="volumeUP_Button" title="Tăng âm lượng" class="btn btn-primary" onclick="control_volume('up')"><i class="bi bi-volume-up-fill"></i>
                        </button>
</center><hr/>
<h5 class="card-title">PlayList Control:</h5>
<center><button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát trước đó" class="btn btn-success" onclick="playlist_media_control('prev')"><i class="bi bi-music-note-list"></i> <i class="bi bi-skip-backward-fill"></i></button>
<button type="button" id="play_Button" name="play_Button" title="Phát nhạc trong Play List" class="btn btn-primary" onclick="playlist_media_control()"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button>
<button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát kế tiếp" class="btn btn-success" onclick="playlist_media_control('next')"><i class="bi bi-skip-forward-fill"></i> <i class="bi bi-music-note-list"></i></button></center>
                    </div>
                </div>

            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Nguồn Media: (Nhạc, Đài, Báo, Radio, PodCast)</h5>
                        <!-- Bordered Tabs Justified -->
                        <ul class="nav nav-tabs nav-tabs-bordered d-flex" id="select_source_media_music" role="tablist">
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100 active" id="local-tab" name="Local" data-bs-toggle="tab" data-bs-target="#bordered-justified-local" type="button" role="tab" aria-controls="local" aria-selected="true" onclick="cacheLocal()">Local</button>
                            </li>
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100" id="zing-tab" name="ZingMP3" data-bs-toggle="tab" data-bs-target="#bordered-justified-zing" type="button" role="tab" aria-controls="zing" aria-selected="false" tabindex="-1" onclick="cacheZingMP3()">ZingMP3</button>
                            </li>
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100" id="youtube-tab" name="Youtube" data-bs-toggle="tab" data-bs-target="#bordered-justified-youtube" type="button" role="tab" aria-controls="youtube" aria-selected="false" tabindex="-1" onclick="cacheYoutube()">Youtube</button>
                            </li>
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100" id="podcast-tab" name="PodCast" data-bs-toggle="tab" data-bs-target="#bordered-justified-podcast" type="button" role="tab" aria-controls="podcast" aria-selected="false" tabindex="-1" onclick="cachePodCast()">PodCast</button>
                            </li>
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100" id="radio-tab" name="Radio" data-bs-toggle="tab" data-bs-target="#bordered-justified-radio" type="button" role="tab" aria-controls="radio" aria-selected="false" tabindex="-1" onclick="cacheRadio()">Radio</button>
                            </li>
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100" id="playlist-tab" name="PlayList" data-bs-toggle="tab" data-bs-target="#bordered-justified-playlist" type="button" role="tab" aria-controls="playlist" aria-selected="false" tabindex="-1" onclick="cachePlayList()">PlayList</button>
                            </li>
							
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100" id="newspaper-tab" name="NewsPaper" data-bs-toggle="tab" data-bs-target="#bordered-justified-newspaper" type="button" role="tab" aria-controls="newspaper" aria-selected="false" tabindex="-1" onclick="cache_NewsPaper()">Báo/Tin Tức</button>
                            </li>
							
                        </ul>
                        <br/>
                        <div class="input-group mb-3" id="tim_kiem_bai_hat_all">
                            <input required="" class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Nhập tên bài hát" title="Nhập tên bài hát cần tìm kiếm" value="">
                            <div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>
                            <button id="actionButton_Media" class="btn btn-success border-success" type="button" onclick="media_player_search()"><i class="bi bi-search"></i></button>
                        </div>
                        <div class="tab-content pt-2" id="borderedTabJustifiedContent">
                            <div class="tab-pane fade active show" id="bordered-justified-local" role="tabpanel" aria-labelledby="local-tab">
                                <form enctype="multipart/form-data" method="POST" action="">
                                    <div class="input-group">

                                        <input class="form-control border-success" type="file" id="upload_Music_Local" multiple="">
                                        <!-- Thêm thuộc tính multiple -->
                                        <button class="btn btn-success border-success" type="button" onclick="upload_File('upload_Music_Local')">Tải Lên</button>

                                    </div>
                                </form>

                                <div id="show_list_media_local" style="max-height: 700px; overflow: auto;"></div>
                            </div>


                            <div class="tab-pane fade" id="bordered-justified-zing" role="tabpanel" aria-labelledby="zing-tab">
                                <div id="show_list_ZingMP3" style="max-height: 700px; overflow: auto;"></div>
                            </div>
                            <div class="tab-pane fade" id="bordered-justified-youtube" role="tabpanel" aria-labelledby="youtube-tab">
                               <div id="show_list_Youtube" style="max-height: 700px; overflow: auto;"></div></div>
                            <div class="tab-pane fade" id="bordered-justified-podcast" role="tabpanel" aria-labelledby="podcast-tab">
                                <div id="show_list_PodCast" style="max-height: 700px; overflow: auto;"></div>
                            </div>
                            <div class="tab-pane fade" id="bordered-justified-radio" role="tabpanel" aria-labelledby="radio-tab">
                                <div id="show_list_Radio" style="max-height: 700px; overflow: auto;"></div>
                            </div>
							
                            <div class="tab-pane fade" id="bordered-justified-playlist" role="tabpanel" aria-labelledby="playlist-tab">
                                 <div id="show_list_Playlist2"></div>
                                 <div id="show_list_Playlist" style="max-height: 700px; overflow: auto;"></div>
                            </div>
							
                            <div class="tab-pane fade" id="bordered-justified-newspaper" role="tabpanel" aria-labelledby="newspaper-tab">
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
<div id="show_list_news_paper" style="max-height: 700px; overflow: auto;"></div>
</div>





					</div>
                    </div>
                </div>
            </div>
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

<!-- Nghe thử file âm thanh
<audio id="audioPlayer" style="display: none;" controls></audio>
 -->
  <!-- Template Main JS File -->
<?php
include 'html_js.php';
?>

<script>
// Lắng nghe sự kiện thay đổi trong input tìm kiếm bài hát
document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);


//Hiển thị dữ liệu PlayList
function cachePlayList() {

    var inputElement = document.getElementById("tim_kiem_bai_hat_all");
    if (inputElement) {
        inputElement.style.display = "none";
	}

    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Media_Player_Search.php?Cache_PlayList', true);
    // Khi yêu cầu được hoàn thành
    xhr.onload = function() {
        if (xhr.status === 200) {
            var fileListDiv = document.getElementById('show_list_Playlist');
            try {
				fileListDiv.innerHTML = '<center><button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát trước đó" class="btn btn-success" onclick="playlist_media_control(\'prev\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-skip-backward-fill"></i></button> <button type="button" id="play_Button" name="play_Button" title="Phát nhạc trong Play List" class="btn btn-primary" onclick="playlist_media_control()"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button> <button type="button" id="play_Button" name="play_Button" title="Chuyển bài hát kế tiếp" class="btn btn-success" onclick="playlist_media_control(\'next\')"><i class="bi bi-skip-forward-fill"></i> <i class="bi bi-music-note-list"></i></button></center>';
				fileListDiv.innerHTML += '<br/>Xóa toàn bộ danh sách phát: <button class="btn btn-danger" title="Xóa toàn bộ danh sách phát" onclick="deleteFromPlaylist(\'delete_all\')"><i class="bi bi-trash"></i> Xóa</button>';
                var data = JSON.parse(xhr.responseText);
                if (Array.isArray(data.data) && data.data.length > 0) {
                    // Xử lý và hiển thị từng playlist
                    data.data.forEach(function(playlist) {
                        var description = playlist.description ? playlist.description.length > 70 ? playlist.description.substring(0, 70) + '...' : playlist.description : 'N/A';
                        var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                        // Xử lý theo nguồn
                        if (playlist.source === "Youtube") {
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + playlist.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color="green">' + playlist.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Kênh: <font color="green">' + playlist.channelTitle + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Mô tả: <font color="green">' + description + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Nguồn Nhạc: <font color="green">' + playlist.source + '</font></p>';
                            fileInfo += ' <button class="btn btn-success" title="Phát: ' + playlist.title + '" onclick="get_Youtube_Link(\'' + playlist.id + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\')"><i class="bi bi-play-circle"></i></button>';
							fileInfo += ' <a href="https://www.youtube.com/watch?v='+playlist.id+'" target="_blank"><button class="btn btn-info" title="Mở trong tab mới: ' + playlist.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
							fileInfo += ' <button class="btn btn-danger" title="Xóa khỏi danh sách phát: '+playlist.title+'" onclick="deleteFromPlaylist(\'delete_some\', \''+playlist.ids_list+'\')"><i class="bi bi-trash"></i></button>';
							fileInfo += '</div></div>';

                        } else if (playlist.source === "ZingMP3") {
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + playlist.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color="green">' + playlist.title + '</font></p>';
                            fileInfo += '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color="green">' + playlist.artist + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color="green">' + playlist.duration + '</font></p>';
							fileInfo += '<p style="margin: 0;">Nguồn Nhạc: <font color="green">' + playlist.source + '</font></p>';
                            fileInfo += ' <button class="btn btn-success" title="Phát: ' + playlist.title + '" onclick="get_ZingMP3_Link(\'' + playlist.id + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'' + playlist.artist + '\')"><i class="bi bi-play-circle"></i></button>';
							fileInfo += ' <button class="btn btn-danger" title="Xóa khỏi danh sách phát: '+playlist.title+'" onclick="deleteFromPlaylist(\'delete_some\', \''+playlist.ids_list+'\')"><i class="bi bi-trash"></i></button>';
							fileInfo += '</div></div>';

                        } else if (playlist.source === "PodCast") {
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + playlist.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color="green">' + playlist.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color="green">' + playlist.duration + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thể Loại: <font color="green">' + description + '</font></p>';
							fileInfo += '<p style="margin: 0;">Nguồn Nhạc: <font color="green">' + playlist.source + '</font></p>';
                            fileInfo += '<button class="btn btn-success" title="Phát: ' + playlist.title + '" onclick="send_Media_Play_API(\'' + playlist.audio + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'PodCast\')"><i class="bi bi-play-circle"></i></button>';
							fileInfo += ' <a href="'+playlist.audio+'" target="_blank"><button class="btn btn-info" title="Mở trong tab mới: ' + playlist.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
							fileInfo += ' <button class="btn btn-danger" title="Xóa khỏi danh sách phát: '+playlist.title+'" onclick="deleteFromPlaylist(\'delete_some\', \''+playlist.ids_list+'\')"><i class="bi bi-trash"></i></button>';
							fileInfo += '</div></div>';

                        } else if (playlist.source === "Local") {
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + playlist.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color="green">' + playlist.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Kích Thước: <font color="green">' + playlist.duration + '</font></p>';
							fileInfo += '<p style="margin: 0;">Nguồn Nhạc: <font color="green">' + playlist.source + '</font></p>';
                            fileInfo += ' <button class="btn btn-success" title="Phát: '+playlist.name+'" onclick="send_Media_Play_API(\'' + playlist.audio + '\', \'' + playlist.title + '\', \'' + playlist.cover + '\', \'Local\')"><i class="bi bi-play-circle"></i></button> ';
							fileInfo += ' <button class="btn btn-danger" title="Xóa khỏi danh sách phát: '+playlist.title+'" onclick="deleteFromPlaylist(\'delete_some\', \''+playlist.ids_list+'\')"><i class="bi bi-trash"></i></button>';
							fileInfo += '</div></div>';
                        }
                        fileListDiv.innerHTML += fileInfo;
                    });

                } else {
                    fileListDiv.innerHTML = '<center>Không có dữ liệu ở danh sách phát</center>';
                }
            } catch (e) {
                show_message('Lỗi phân tích dữ liệu Playlist JSON: ' + e.message);
            }
        } else {
            show_message('Không thể tải dữ liệu Playlist. Trạng thái: ' + xhr.status);
        }
    };

    xhr.onerror = function() {
        show_message('Lỗi khi thực hiện yêu cầu lấy dữ liệu Playlist');
    };

    // Gửi yêu cầu
    xhr.send();
}



</script>

<script>
//script liên quan tới API GET Media Player
let isHovering = false;
let fullTime = 0; // lưu trữ toàn bộ thời gian bài nhạc
let intervalId;
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


// Cập nhật thông tin GET từ API
function fetchData_Media_Player() {
    // Kiểm tra nếu checkbox được tích hoặc sync_active là true
    const syncCheckbox = document.getElementById('sync_checkbox');
    // Không thực hiện fetchData_Media_Player nếu checkbox không được tích
    if (!syncCheckbox.checked) {
        return; 
    }

    fetch("<?php echo $Protocol.$serverIp.':'.$Port_API; ?>/?type=1&data=media_player")
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
				document.getElementById('div_message_error').style.display = 'none';
                // Cập nhật các phần tử khác
                document.getElementById('media-name').innerHTML = 'Tên bài hát: <font color="blue">' + (data.media_name ? data.media_name : 'N/A') + '</font>';
                document.getElementById('volume').innerHTML = 'Âm lượng: <font color=blue>' + data.volume+'%</font>';
                document.getElementById('audio-playing').innerHTML = 'Đang phát: <font color=blue>' + (data.audio_playing ? 'Có' : 'Không') + '</font>';
                document.getElementById('audio-source').innerHTML = 'Nguồn Media: <font color=blue>' + data.media_player_source + '</font>';
                // Cập nhật ảnh bìa
                document.getElementById('media-cover').src = data.media_cover ? data.media_cover : 'assets/img/Error_Null_Media_Player.png';

                // Cập nhật giá trị full time
                fullTime = data.full_time;

                // Cập nhật thanh trượt chỉ khi không đang hover
                if (!isHovering) {
                    let progressBar = document.getElementById('progress-bar');
                    progressBar.max = fullTime;
                    progressBar.value = data.current_duration;

                    let timeInfo = document.getElementById('time-info');
                    timeInfo.innerHTML = '<font color=blue>' + formatTime_Player(data.current_duration) + '</font> / ' + formatTime_Player(fullTime);
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
			document.getElementById('message_error').innerHTML = 'Không thể kết nối đến API, Vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa, Mã Lỗi: '+error;
            //console.log('Lỗi kết nối:', error);
            //show_message('Không thể kết nối đến API của VBot, Vui lòng kiểm tra lại kết nối và thử lại.');
        });
}


// Bắt đầu lấy dữ liệu mỗi giây
intervalId = setInterval(fetchData_Media_Player, <?php echo intval($Config['media_player']['media_sync_ui']['delay_time']); ?> * 1000);

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
	document.getElementById('time-info').innerHTML = '<font color=green>' +formatTime_Player(currentDuration) + '</font> / ' + formatTime_Player(fullTime);
});

//Dùng để tua bài hát
function sendSetTime_duration(set_duration) {
    // Tạo đối tượng data với tham số set_duration
    var data = JSON.stringify({
        "type": 1,
        "data": "media_control",
        "action": "set_time",
        "set_duration": set_duration
    });

    // Tạo đối tượng XMLHttpRequest
    var xhr = new XMLHttpRequest();
    //xhr.withCredentials = true;
    xhr.addEventListener("readystatechange", function() {
        if (this.readyState === 4) {
			

			var response = JSON.parse(this.responseText);
				if (response.success){
					showMessagePHP(response.message);
				}else{
					show_message('Lỗi: ' +response.message); 
				}
             
        }
    });
    // Mở kết nối POST và thiết lập tiêu đề Content-Type
    xhr.open("POST", "<?php echo $Protocol.$serverIp.':'.$Port_API; ?>");
    xhr.setRequestHeader("Content-Type", "application/json");
    // Gửi dữ liệu
    xhr.send(data);
}
</script>
</body>

</html>