//thay đổi chế độ sáng tối
const themeToggle = document.getElementById('themeToggle');
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('vbot_theme', theme);
    if (themeToggle) {
        if (theme === 'dark') {
            themeToggle.classList.remove('bi-moon-stars-fill');
            themeToggle.innerHTML = '<i class="bi bi-sun"></i> Chế độ sáng';
        } else {
            themeToggle.classList.remove('bi-sun');
            themeToggle.innerHTML = '<i class="bi bi-moon-stars-fill"></i> Chế độ tối';
        }
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
}

const savedTheme = localStorage.getItem('vbot_theme') || 'light';
setTheme(savedTheme);
if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
}

//Chạy hiển thị thời gian khi trang đã tải xong
window.onload = setInterval(updateTime, 1000);

function updateTime() {
    var d = new Date();
    var hour = d.getHours();
    var min = d.getMinutes();
    var sec = d.getSeconds();
    if (document.getElementById("times")) {
        document.getElementById('times').innerHTML = formatTime(hour) + ':' + formatTime(min) + ':' + formatTime(sec);
    }
    //console.log(formatTime(hour) + ":" + formatTime(min) + ":" + formatTime(sec))
}

//Chuyển đổi thời gian
function formatTime(unit) {
    return unit < 10 ? "0" + unit : unit;
}

// Cập nhật ngày và thứ chỉ một lần khi trang tải
function updateDate() {
    var d = new Date();
    var date = d.getDate();
    var month = d.getMonth();
    var montharr = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
    var year = d.getFullYear();
    var day = d.getDay();
    var dayarr = ["Chủ Nhật", "Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7"];

    if (document.getElementById("days")) {
        document.getElementById("days").innerHTML = dayarr[day];
    }

    if (document.getElementById("dates")) {
        document.getElementById("dates").innerHTML = date + "/" + montharr[month] + "/" + year;
    }
}

//Cập nhật ngày tháng khi trang tải xong
document.addEventListener('DOMContentLoaded', function() {
    updateDate();
    setTimeout(updateDate, 2000);
});

//Hàm để hiển thị hoặc ẩn overlay
function loading(action) {
    const overlay = document.getElementById('loadingOverlay');
    if (action === 'show') {
        overlay.style.display = 'flex';
    } else if (action === 'hide') {
        overlay.style.display = 'none';
    }
}
loading("hide");

//Hiển thị thông báo xác nhận với thông tin chi tiết về backup
function confirmRestore(backup_file_name) {
    var result = confirm(backup_file_name);
    if (result) {
        loading('show');
    }
    return result;
}

//Coppy dữ liệu trong thẻ input
function coppy_value(id_input) {
    var input = document.getElementById(id_input);
    input.select();
    try {
        document.execCommand("copy");
        showMessagePHP("Đã Sao Chép!", 3);
    } catch (err) {
        show_message("Lỗi khi sao chép nội dung. Vui lòng thử lại.");
    }
}

//Mở đường dẫn trong tab mới
function openNewTab(url_link) {
    if (url_link) {
        window.open(url_link, '_blank');
    } else {
        show_message('Không có đường dẫn được cung cấp');
    }
}

//Hàm kiểm tra và thay đổi nút tìm kiếm trong tab Media Player
function checkInput_MediaPlayer() {
    const inputField = document.getElementById('song_name_value');
    const actionButton = document.getElementById('actionButton_Media');
    const inputValue = inputField.value.trim();
    if (inputValue.startsWith('http')) {
        actionButton.innerHTML = '<i class="bi bi-play-circle" title="Phát bằng địa chỉ URL"></i>';
        actionButton.setAttribute('onclick', 'media_player_url()');
    } else {
        actionButton.innerHTML = '<i class="bi bi-search" title="Tìm kiếm"></i>';
        actionButton.setAttribute('onclick', 'media_player_search()');
        if (document.getElementById('select_cache_media')) {
            const selectedValue = document.getElementById('select_cache_media').value;
            if (selectedValue === 'Youtube') {
                actionButton.setAttribute('onclick', 'media_player_search("Youtube")');
            } else if (selectedValue === 'ZingMP3') {
                actionButton.setAttribute('onclick', 'media_player_search("ZingMP3")');
            } else if (selectedValue === 'PodCast') {
                actionButton.setAttribute('onclick', 'media_player_search("PodCast")');
            } else {
                actionButton.setAttribute('onclick', 'media_player_search()');
            }

        }
    }
}

// Hàm trích xuất ID video từ URL YouTube
function extractYouTubeId(url) {
    const youtubeRegex = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|v\/|.+\?v=)|youtu\.be\/)([^"&?\/\s]{11})/;
    const match = url.match(youtubeRegex);
    return match ? match[1] : null;
}

// Hàm xử lý phát URL http từ tab Media Player
function media_player_url() {
    const audioExtensions = ['.mp3', '.wav', '.ogg', '.flac', '.aac', '.m3u8'];
    const inputField = document.getElementById('song_name_value');
    const url = inputField.value.trim();
    link_url = url.toLowerCase();
    const isAudio = audioExtensions.some(function(ext) {
        return link_url.endsWith(ext);
    }) || link_url.startsWith('http');
    if (link_url.startsWith('https://www.youtube.com') || link_url.startsWith('https://youtu.be')) {
        const videoId = extractYouTubeId(url);
        if (videoId) {
            get_Youtube_Link(videoId, null, null);
        } else {
            show_message('URL YouTube không hợp lệ.');
        }
    }
    //Nếu đường link, url có đuôi tệp cuối dùng
    else if (isAudio) {
        var fileName = "";
        var fileExtension = "";
        for (var i = 0; i < audioExtensions.length; i++) {
            if (link_url.endsWith(audioExtensions[i])) {
                fileExtension = audioExtensions[i];
                fileName = link_url.substring(link_url.lastIndexOf('/') + 1, link_url.lastIndexOf(fileExtension));
                break;
            }
        }
        if (fileExtension) {
            startMediaPlayer(url, fileName + '' + fileExtension, 'assets/img/icon_audio_local.png', 'Local');
        } else {
            startMediaPlayer(url, null, 'assets/img/icon_audio_local.png', 'Local');
        }
    } else {
        show_message('URL không hợp lệ hoặc không phải là nguồn nhạc được hỗ trợ.');
    }
}

//Hàm xử lý dữ liệu hiển thị kết quả tìm kiếm ZingMP3
function processZingMP3Data(data_media_ZingMP3) {
    var fileListDiv = document.getElementById('show_list_ZingMP3');
    if (!fileListDiv) {
        fileListDiv = document.getElementById('tableContainer');
    }
    if (!data_media_ZingMP3 || !Array.isArray(data_media_ZingMP3.results) || data_media_ZingMP3.results.length === 0) {
        show_message('<p>Không có dữ liệu bài hát tương ứng với từ khóa trên ZingMP3</p>');
    } else {
        fileListDiv.innerHTML = '';
        if (!document.getElementById("song_name_value")) {
            fileListDiv.innerHTML += '<div class="input-group mb-3">' +
                '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát" title="Nhập tên bài hát cần tìm kiếm" value="">' +
                '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
                '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'ZingMP3\')"><i class="bi bi-search"></i></button>' +
                '<button type="button" class="btn btn-primary border-success" onclick="cacheZingMP3()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
        }
        data_media_ZingMP3.results.forEach(function(zing) {
            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
            fileInfo += '<img src="' + zing.thumb + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + zing.name + '</font></p>';
            fileInfo += '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color=green>' + zing.artist + '</font></p>';
            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (zing.duration || 'N/A') + '</font></p>';
            fileInfo += '<button class="btn btn-success" title="Phát: ' + zing.name + '" onclick="get_ZingMP3_Link(\'' + zing.id + '\', \'' + zing.name + '\', \'' + zing.thumb + '\', \'' + zing.artist + '\')"><i class="bi bi-play-circle"></i></button>';
            fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + zing.name + '" onclick="addToPlaylist(\'' + zing.name + '\', \'' + zing.thumb + '\', \'' + zing.id + '\', \'' + (zing.duration || 'N/A') + '\', null, \'ZingMP3\', \'' + zing.id + '\', null, \'' + zing.artist + '\')"><i class="bi bi-music-note-list"></i></button>';
            fileInfo += ' <button class="btn btn-warning" title="Tải Xuống: ' + zing.name + '" onclick="dowload_ZingMP3_ID(\'' + zing.id + '\', \'' + zing.name + '\')"><i class="bi bi-download"></i></button>';
            fileInfo += ' <button class="btn btn-info" title="Tải Vào Thư Mục Local: ' + zing.name + '" onclick="download_zingMp3_to_local(\'' + zing.id + '\', \'' + zing.name + '\')"><i class="bi bi-save2"></i></button>';
            fileInfo += '</div></div>';
            fileListDiv.innerHTML += fileInfo;
        });
    }
}

//Hàm xử lý dữ liệu hiển thị kết quả tìm kiếm Youtube
function processYoutubeData(data_media_Youtube) {
    if (!data_media_Youtube || !Array.isArray(data_media_Youtube.data) || data_media_Youtube.data.length === 0) {
        show_message('<p>Không có dữ liệu bài hát từ Youtube</p>');
        return;
    }
    var fileListDiv = document.getElementById('show_list_Youtube');
    if (!fileListDiv) {
        fileListDiv = document.getElementById('tableContainer');
    }
    fileListDiv.innerHTML = '';
    if (!document.getElementById("song_name_value")) {
        fileListDiv.innerHTML += '<div class="input-group mb-3">' +
            '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát hoặc nhập url/link Youtube" title="Nhập tên bài hát cần tìm kiếm hoặc nhập url/link Youtube" value="">' +
            '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
            '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'Youtube\')"><i class="bi bi-search"></i></button>' +
            '<button type="button" class="btn btn-primary border-success" onclick="cacheYoutube()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
    }
    data_media_Youtube.data.forEach(function(youtube) {
        var description = youtube.description.length > 70 ? youtube.description.substring(0, 70) + '...' : youtube.description;
        var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
        fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
        fileInfo += '<img src="' + youtube.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
        fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + youtube.title + '</font></p>';
        fileInfo += '<p style="margin: 0;">Kênh: <font color=green>' + (youtube.channelTitle || 'N/A') + '</font></p>';
        fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (youtube.duration || 'N/A') + '</font></p>';
        fileInfo += '<p style="margin: 0;">Mô tả: <font color=green>' + (description || 'N/A') + '</font></p>';
        fileInfo += ' <button class="btn btn-success" title="Phát: ' + youtube.title + '" onclick="get_Youtube_Link(\'' + youtube.id + '\', \'' + youtube.title + '\', \'' + youtube.cover + '\')"><i class="bi bi-play-circle"></i></button>';
        fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + youtube.title + '" onclick="addToPlaylist(\'' + youtube.title + '\', \'' + youtube.cover + '\', \'https://www.youtube.com/watch?v=' + youtube.id + '\', null, \'' + (description || 'N/A') + '\', \'Youtube\', \'' + youtube.id + '\', \'' + (youtube.channelTitle || 'N/A') + '\', null)"><i class="bi bi-music-note-list"></i></button>';
        fileInfo += ' <a href="https://www.youtube.com/watch?v=' + youtube.id + '" target="_bank"><button class="btn btn-info" title="Mở trong tab mới: ' + youtube.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
        fileInfo += '</div></div>';
        fileListDiv.innerHTML += fileInfo;
    });
}

//Xử lý dữ liệu tìm kiếm PodCast
function processPodCastData(data_media_PodCast) {
    if (!data_media_PodCast || !Array.isArray(data_media_PodCast.data) || data_media_PodCast.data.length === 0) {
        show_message('<p>Không có dữ liệu bài hát từ PodCast</p>');
        return;
    }
    var fileListDiv = document.getElementById('show_list_PodCast');
    if (!fileListDiv) {
        fileListDiv = document.getElementById('tableContainer');
    }
    fileListDiv.innerHTML = '';
    if (!document.getElementById("song_name_value")) {
        fileListDiv.innerHTML += '<div class="input-group mb-3">' +
            '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát" title="Nhập tên bài hát cần tìm kiếm" value="">' +
            '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
            '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'PodCast\')"><i class="bi bi-search"></i></button>' +
            '<button type="button" class="btn btn-primary border-success" onclick="cachePodCast()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
    }
    data_media_PodCast.data.forEach(function(podcast) {
        var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
        fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
        fileInfo += '<img src="' + podcast.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
        fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + podcast.title + '</font></p>';
        fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (podcast.duration || 'N/A') + '</font></p>';
        fileInfo += '<p style="margin: 0;">Thể Loại: <font color=green>' + (podcast.description || 'N/A') + '</font></p>';
        fileInfo += '<button class="btn btn-success" title="Phát: ' + podcast.title + '" onclick="startMediaPlayer(\'' + podcast.audio + '\', \'' + podcast.title + '\', \'' + podcast.cover + '\')"><i class="bi bi-play-circle"></i></button>';
        fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + podcast.title + '" onclick="addToPlaylist(\'' + podcast.title + '\', \'' + podcast.cover + '\', \'' + podcast.audio + '\', \'' + (podcast.duration || 'N/A') + '\', \'' + (podcast.description || 'N/A') + '\', \'PodCast\', \'' + podcast.audio + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
        fileInfo += ' <button class="btn btn-warning" title="Tải Xuống: ' + podcast.title + '" onclick="download_AUDIO_URL(\'' + podcast.audio + '\', \'' + podcast.title + '\')"><i class="bi bi-download"></i></button>';
        fileInfo += ' <button class="btn btn-danger" title="Tải Vào Thư Mục Local: ' + podcast.title + '" onclick="download_Link_url_to_local(\'' + podcast.audio + '\', \'' + podcast.title + '\')"><i class="bi bi-save2"></i></button>';
        fileInfo += ' <a href="' + podcast.audio + '" target="_blank"><button class="btn btn-info" title="Mở trong tab mới: ' + podcast.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
        fileInfo += '</div></div>';
        fileListDiv.innerHTML += fileInfo;
    });
}

//Hàm xử lý dữ liệu tìm kiếm nhạc Local
function processLocalData(data_media_local) {
    var fileListDiv = document.getElementById('show_list_media_local');
    if (!fileListDiv) {
        fileListDiv = document.getElementById('tableContainer');
    }
    if (!data_media_local || data_media_local.length === 0) {
        show_message('<p>Không có dữ liệu bài hát Local</p>');
        fileListDiv.innerHTML = '';
        if (!document.getElementById('upload_Music_Local')) {
            fileListDiv.innerHTML = '<form enctype="multipart/form-data" method="POST" action="">' +
                '<div class="input-group">' +
                '<input class="form-control border-success" type="file" id="upload_Music_Local" multiple="">' +
                '<button class="btn btn-success border-success" type="button" onclick="upload_File(\'upload_Music_Local\')">Tải Lên</button>' +
                '<button type="button" class="btn btn-primary border-success" onclick="media_player_search(\'Local\')" title="Tải lại dữ liệu bài hát trong thư mục Local"><i class="bi bi-arrow-repeat"></i></button></div></form>' +
				'<br/><center><p class="text-danger">Không có dữ liệu bài hát nào trong thư mục Nội Bộ (Local)</p></center>';
		}
    } else {
        fileListDiv.innerHTML = '';
        if (!document.getElementById('upload_Music_Local')) {
            fileListDiv.innerHTML = '<form enctype="multipart/form-data" method="POST" action="">' +
                '<div class="input-group">' +
                '<input class="form-control border-success" type="file" id="upload_Music_Local" multiple="">' +
                '<button class="btn btn-success border-success" type="button" onclick="upload_File(\'upload_Music_Local\')">Tải Lên</button>' +
                '<button type="button" class="btn btn-primary border-success" onclick="media_player_search(\'Local\')" title="Tải lại dữ liệu bài hát trong thư mục Local"><i class="bi bi-arrow-repeat"></i></button></div></form>' +
				'<br/><center><button class="btn btn-success" title="Phát toàn bộ bài hát trong thư mục nội bộ Local" onclick="playlist_media_control(\'local\')"><i class="bi bi-play-circle"></i> Phát Toàn Bộ Nhạc Local</button></center>';
        }else{
			fileListDiv.innerHTML = '<br/><center><button class="btn btn-success" title="Phát toàn bộ bài hát trong thư mục nội bộ Local" onclick="playlist_media_control(\'local\')"><i class="bi bi-play-circle"></i> Phát Toàn Bộ Nhạc Local</button></center>';
		}
        data_media_local.forEach(function(file) {
            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
            fileInfo += '<img src="' + file.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: ' + file.name + '</p>';
            fileInfo += '<p style="margin: 0;">Kích thước: ' + file.size + ' MB</p>';
            fileInfo += '<button class="btn btn-success" title="Phát: ' + file.name + '" onclick="startMediaPlayer(\'' + file.full_path + '\', \'' + file.name + '\', \'' + file.cover + '\', \'Local\')"><i class="bi bi-play-circle"></i></button> ';
            fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + file.name + '" onclick="addToPlaylist(\'' + file.name + '\', \'' + file.cover + '\', \'' + file.full_path + '\', \'' + file.size + ' MB\', null, \'Local\', \'' + file.full_path + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
            fileInfo += ' <button class="btn btn-warning" title="Tải Xuống File: ' + file.name + '" onclick="downloadFile(\'' + file.full_path + '\')"><i class="bi bi-download"></i></button>';
            fileInfo += ' <button class="btn btn-danger" title="Xóa File: ' + file.name + '" onclick="deleteFile(\'' + file.full_path + '\', \'media_player_search\')"><i class="bi bi-trash"></i></button>';
            fileInfo += '</div></div>';
            fileListDiv.innerHTML += fileInfo;
            adjustContainerStyle_tableContainer();
        });
    }
}

//gán onclick để sử dụng truyền đối số vào hàm fetchData_NewsPaper lấy dữ liệu báo, tin tức
function get_data_newspaper() {
    var selectedValue = document.getElementById("news_paper").value;
    if (selectedValue) {
        fetchData_NewsPaper(selectedValue);
    } else {
        showMessagePHP("Cần chọn trang Báo, Tin Tức để thực hiện", 3);
    }
}

//Play video hoặc m3u8 HLS
function playHLS(url) {
    loading("show");
    const video = document.getElementById('videoPlayer');
    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(url);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
            loading("hide");
            video.play();
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = url;
        video.addEventListener('loadedmetadata', function() {
            loading("hide");
            video.play();
        });
    }
}

// Hàm thay đổi class giữa modal-lg, modal-xl và modal-fullscreen và cập nhật icon dao diện chatbox
function chatbot_toggleFullScreen() {
    var chatbotSizeSetting = document.getElementById('chatbot_size_setting');
    var chatbotIcon = document.getElementById('chatbot_fullscreen');
    if (chatbotSizeSetting.classList.contains('modal-lg')) {
        chatbotSizeSetting.classList.remove('modal-lg');
        chatbotSizeSetting.classList.add('modal-xl');
    } else if (chatbotSizeSetting.classList.contains('modal-xl')) {
        chatbotSizeSetting.classList.remove('modal-xl');
        chatbotSizeSetting.classList.add('modal-fullscreen');
        chatbotIcon.classList.remove('bi-arrows-fullscreen');
        chatbotIcon.classList.add('bi-fullscreen-exit');
    } else if (chatbotSizeSetting.classList.contains('modal-fullscreen')) {
        chatbotSizeSetting.classList.remove('modal-fullscreen');
        chatbotSizeSetting.classList.add('modal-lg');
        chatbotIcon.classList.remove('bi-fullscreen-exit');
        chatbotIcon.classList.add('bi-arrows-fullscreen');
    }
}

// Hàm thay đổi class giữa modal-lg, modal-xl và modal-fullscreen và cập nhật icon dao diện VBot Scan
function vbotScan_toggleFullScreen() {
    var chatbotSizeSetting = document.getElementById('vbotScan_size_setting');
    var chatbotIcon = document.getElementById('vbotScan_fullscreen');
    if (chatbotSizeSetting.classList.contains('modal-lg')) {
        chatbotSizeSetting.classList.remove('modal-lg');
        chatbotSizeSetting.classList.add('modal-xl');
    } else if (chatbotSizeSetting.classList.contains('modal-xl')) {
        chatbotSizeSetting.classList.remove('modal-xl');
        chatbotSizeSetting.classList.add('modal-fullscreen');
        chatbotIcon.classList.remove('bi-arrows-fullscreen');
        chatbotIcon.classList.add('bi-fullscreen-exit');
    } else if (chatbotSizeSetting.classList.contains('modal-fullscreen')) {
        chatbotSizeSetting.classList.remove('modal-fullscreen');
        chatbotSizeSetting.classList.add('modal-lg');
        chatbotIcon.classList.remove('bi-fullscreen-exit');
        chatbotIcon.classList.add('bi-arrows-fullscreen');
    }
}

function Logs_API_toggleFullScreen() {
    var chatbotSizeSetting = document.getElementById('Show_LogsAPI_size_setting');
    var chatbotIcon = document.getElementById('Show_LogsAPI_fullscreen');
    if (chatbotSizeSetting.classList.contains('modal-lg')) {
        chatbotSizeSetting.classList.remove('modal-lg');
        chatbotSizeSetting.classList.add('modal-xl');
    } else if (chatbotSizeSetting.classList.contains('modal-xl')) {
        chatbotSizeSetting.classList.remove('modal-xl');
        chatbotSizeSetting.classList.add('modal-fullscreen');
        chatbotIcon.classList.remove('bi-arrows-fullscreen');
        chatbotIcon.classList.add('bi-fullscreen-exit');
    } else if (chatbotSizeSetting.classList.contains('modal-fullscreen')) {
        chatbotSizeSetting.classList.remove('modal-fullscreen');
        chatbotSizeSetting.classList.add('modal-lg');
        chatbotIcon.classList.remove('bi-fullscreen-exit');
        chatbotIcon.classList.add('bi-arrows-fullscreen');
    }
}

// Hàm thay đổi class giữa modal-lg, modal-xl và modal-fullscreen và cập nhật icon dao diện iframeModal_toggleFullScreen
function iframeModal_toggleFullScreen() {
    var iframeModal_size_setting = document.getElementById('iframeModal_size_setting');
    var iframeModal_fullscreen = document.getElementById('iframeModal_fullscreen');
    if (iframeModal_size_setting.classList.contains('modal-lg')) {
        iframeModal_size_setting.classList.remove('modal-lg');
        iframeModal_size_setting.classList.add('modal-xl');
    } else if (iframeModal_size_setting.classList.contains('modal-xl')) {
        iframeModal_size_setting.classList.remove('modal-xl');
        iframeModal_size_setting.classList.add('modal-fullscreen');
        iframeModal_fullscreen.classList.remove('bi-arrows-fullscreen');
        iframeModal_fullscreen.classList.add('bi-fullscreen-exit');
    } else if (iframeModal_size_setting.classList.contains('modal-fullscreen')) {
        iframeModal_size_setting.classList.remove('modal-fullscreen');
        iframeModal_size_setting.classList.add('modal-lg');
        iframeModal_fullscreen.classList.remove('bi-fullscreen-exit');
        iframeModal_fullscreen.classList.add('bi-arrows-fullscreen');
    }
}

//Hiển thị thẻ modal iframe
function showIframeModal(ipAddress, source_text_device) {
	loading('show');
    // Kiểm tra mạng nội bộ và không phải HTTPS
    const hostname = location.hostname;
    const isLocalNetwork = (
        hostname === 'localhost' ||
        hostname.startsWith('192.168.') ||
        hostname.startsWith('10.') || /^172\.(1[6-9]|2[0-9]|3[0-1])\./.test(hostname)
    );
    if (ipAddress === hostname) {
		showMessagePHP('Bạn đang ở thiết bị này rồi: ['+source_text_device+' - '+ipAddress+']');
        loading('hide');
        return;
    }
    if (!isLocalNetwork || location.protocol === 'https:') {
        show_message('Chức năng này chỉ hoạt động khi truy cập trong cùng lớp mạng nội bộ');
        loading('hide');
        return;
    }
    const iframeModal_source = document.getElementById('iframeModal_source');
    const iframe = document.getElementById('contentIframe');
    const modalDialog = document.getElementById('iframeModal_size_setting');
    const modalContent = modalDialog.querySelector('.modal-content');
    const modalBody = modalDialog.querySelector('.modal-body');
	iframeModal_source.innerHTML = '<b>Thiết Bị:  ['+source_text_device +'- <a href="http://'+ipAddress+'" target="_blank">'+ ipAddress+'</a>]</b>';
    iframe.src = 'http://' + ipAddress;
    updateIframeSize();
    const modal = new bootstrap.Modal(document.getElementById('iframeModal'));
    modal.show();
    const observer = new MutationObserver(() => updateIframeSize());
    observer.observe(modalDialog, {
        attributes: true,
        attributeFilter: ['class']
    });
    modal._element.addEventListener('hidden.bs.modal', () => observer.disconnect(), {once: true});
	loading('hide');
	showMessagePHP('Đang tương tác với: ['+source_text_device+' - '+ipAddress+']');
}

// Hàm cập nhật kích thước modal-body và iframe
function updateIframeSize() {
    const modalDialog = document.getElementById('iframeModal_size_setting');
    const modalContent = modalDialog.querySelector('.modal-content');
    const modalHeader = modalContent.querySelector('.modal-header');
    const modalBody = modalContent.querySelector('.modal-body');
    const iframe = document.getElementById('contentIframe');
    // Tính chiều cao header động
    const headerHeight = modalHeader ? modalHeader.offsetHeight : 0;
    // Đặt chiều cao modal-body và iframe bằng inline styles
    modalBody.style.height = `calc(100% - ${headerHeight}px)`;
    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = 'none';
    iframe.style.display = 'block';
}

//  hàm cuộn xuống dưới cùng tin nhắn
function scrollToBottom() {
    const chatbox = document.getElementById('chatbox');
    chatbox.scrollTop = chatbox.scrollHeight;
}

// Hàm lấy thời gian hiện tại dưới định dạng dd/mm/yyyy hh:mm:ss
function getCurrentTime() {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    return hours + ':' + minutes + ':' + seconds + ' ' + day + '/' + month + '/' + year;
}

// Hàm lưu tin nhắn vào localStorage
function saveMessage(type, text, name_vbot) {
    const messages = JSON.parse(localStorage.getItem('messages')) || [];
    messages.push({
        type: type,
        text: text,
        time: getCurrentTime() + ' [' + name_vbot + ']'
    });
    localStorage.setItem('messages', JSON.stringify(messages));
}

// Hàm xóa tin nhắn khỏi localStorage và giao diện
function deleteMessage(index) {
    const messages = JSON.parse(localStorage.getItem('messages')) || [];
    messages.splice(index, 1);
    localStorage.setItem('messages', JSON.stringify(messages));
    loadMessages();
}

// Hàm tải tin nhắn từ localStorage
function loadMessages() {
    const chatbox = document.getElementById('chatbox');
    // Nếu không có chatbox thì thoát luôn
    if (!chatbox) return;
    const messages = JSON.parse(localStorage.getItem('messages')) || [];
    chatbox.innerHTML = '';
    messages.forEach(function(message, index) {
        var messageHTML = '<div class="message ' + (message.type === 'user' ? 'user-message' : 'bot-message') + '">' +
            '<span class="delete_message_chatbox" data-index="' + index + '" title="Xóa tin nhắn">x</span>' +
            '<div class="message-time">' + message.time + '</div>';
        if (message.text && /^TTS_Audio.*\.(mp3|ogg|wav)$/i.test(message.text)) {
            var audioExtension = message.text.split('.').pop();
            var fullAudioUrl = 'includes/php_ajax/Show_file_path.php?TTS_Audio=' + encodeURIComponent(message.text);
            messageHTML +=
                '<div class="audio-container">' +
                '    <audio controls>' +
                '        <source src="' + fullAudioUrl + '" type="audio/' + audioExtension + '">' +
                '        Your browser does not support the audio element.' +
                '    </audio>' +
                '</div>';
        } else {
            messageHTML += '<div>' + message.text + '</div>';
        }
        messageHTML += '</div>';
        chatbox.innerHTML += messageHTML;
    });
    scrollToBottom();
    // Gán sự kiện xóa tin nhắn
    document.querySelectorAll('.delete_message_chatbox').forEach(function(button) {
        button.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'), 10);
            deleteMessage(index);
        });
    });
}

// Hàm xóa tất cả tin nhắn từ localStorage và giao diện
function clearMessages() {
    if (!confirm("Bạn có chắc chắn muốn xóa lịch sử chat ?")) {
        return;
    }
    localStorage.removeItem('messages');
    loadMessages();
}

// Hàm xóa tin nhắn khỏi localStorage và giao diện
function deleteMessage(index) {
    const messages = JSON.parse(localStorage.getItem('messages')) || [];
    messages.splice(index, 1);
    localStorage.setItem('messages', JSON.stringify(messages));
    loadMessages();
}

// Hàm để dừng tất cả các phần tử audio đang phát
function stopAllAudio() {
    //console.log("dừng audio");
    var audios = document.querySelectorAll('audio');
    audios.forEach(function(audio) {
        audio.pause();
        audio.currentTime = 0;
    });
}

  //Tải tin nhắn từ localStorage khi trang được tải
  loadMessages();
  //Khi chatbox được hiển thị hoàn toàn
  document.addEventListener('DOMContentLoaded', () => {
  const myModal = document.getElementById('modalDialogScrollable_chatbot');
  if (myModal) {
    myModal.addEventListener('shown.bs.modal', () => {
      scrollToBottom();
    });
  }
});

//Phát âm thanh báo hiệu thu âm hoặc kết thúc
function playSound_default(path_sound) {
    //playSound_default('/assets/sound/default/ding.mp3');
    const audio = new Audio(path_sound);
    audio.play();
    audio.onended = () => {
        //console.log("Âm thanh phát xong. Bắt đầu thu âm...");
        //startRecording();
    };
    audio.onerror = () => {
        showMessagePHP('Không thể phát âm thanh DING', 5)
    };
}

//Hàm thay đổi icon mic dựa trên giao thức URL
function updateMicIcon() {
    const micIcon = document.getElementById('mic_icon_rec');
    if (micIcon) {
        const isHttps = window.location.protocol === 'https:';
        micIcon.className = isHttps ? 'bi bi-mic-fill' : 'bi bi-mic-mute-fill text-warning';
    }
}
updateMicIcon();

//kiểm tra nếu có thẻ id là tableContainer sẽ thay đổi kích thước chiều dọc tùy thuộc vào nội dung
function adjustContainerStyle_tableContainer() {
    var container = document.getElementById('tableContainer');
    if (container) {
        var contentHeight = container.scrollHeight;
        if (contentHeight > 400) {
            container.style.height = '400px';
            container.style.overflowY = 'auto';
        } else {
            container.style.height = 'auto';
            container.style.overflowY = 'hidden';
        }
    }
}

//Nếu nhấn vào nút Nguồn nhạc Local trên tab mediaplayer hoặc ở index
function cacheLocal() {
    var inputElement = document.getElementById("tim_kiem_bai_hat_all");
    if (inputElement) {
        inputElement.style.display = "";
    } else {
        showMessagePHP('Không tìm thấy phần tử với id "tim_kiem_bai_hat_all"', 3);
    }
}

//Nếu nhấn vào nút Nguồn nhạc Local trên tab mediaplayer hoặc ở index
function cacheRadio() {
    var inputElement = document.getElementById("tim_kiem_bai_hat_all");
    if (inputElement) {
        inputElement.style.display = "";
    } else {
        showMessagePHP('Không tìm thấy phần tử với id "tim_kiem_bai_hat_all"', 3);
    }
}

//Nếu nhấn vào nút Báo, Tin tức trên tab mediaplayer hoặc ở index
function cacheNewsPaper() {
    var inputElement = document.getElementById("tim_kiem_bai_hat_all");
    if (inputElement) {
        inputElement.style.display = "none";
    } else {
        showMessagePHP('Không tìm thấy phần tử với id "tim_kiem_bai_hat_all"', 3);
    }
}

// Bluetooth command khi được nhập lệnh thủ công ở thẻ input
function bluetooth_command_at_input() {
    var inputValue = document.getElementById('ble_Command_UI').value.trim();
    if (inputValue === "") {
        return;
    }
    if (!inputValue.startsWith("AT+")) {
        showMessagePHP("Lệnh Không Đúng", 3);
        return;
    }
    bluetooth_control('command', inputValue);
}

// Lấy dữ liệu BLE từ localStorage
function loadBluetoothDevices() {
    var storedData = localStorage.getItem('bluetoothDevices_Vbot');
    if (storedData) {
        var data = JSON.parse(storedData);
        var tableHTML = '<p class="card-title"> Dữ liệu được tìm kiếm trước đó: <i class="bi bi-trash text-danger" title="Xóa dữ liệu Tìm Kiếm" onclick="clearBluetoothDevices()"></i></p><table class="table table-bordered border-primary" cellspacing="0" cellpadding="5"><tr><th style="text-align: center; vertical-align: middle;">Địa Chỉ Mac</th><th style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th><th style="text-align: center; vertical-align: middle;">Hành Động</th></tr>';
        var seenMacAddresses = [];
        data.forEach(function(dataItem) {
            var dataItemContent = dataItem.data;
            if (dataItemContent && dataItemContent.includes("MacAdd") && dataItemContent.includes("Name")) {
                var macAddMatch = dataItemContent.match(/MacAdd:([0-9a-fA-F]+)/);
                var nameMatch = dataItemContent.match(/Name:([^,]+)/);
                if (macAddMatch && nameMatch) {
                    var macAdd = macAddMatch[1];
                    if (!seenMacAddresses.includes(macAdd)) {
                        seenMacAddresses.push(macAdd);
                        tableHTML += "<tr><td style='text-align: center; vertical-align: middle;'>" + macAdd + "</td><td style='text-align: center; vertical-align: middle;'>" + nameMatch[1] + "</td><td style='text-align: center; vertical-align: middle;'><button type='button' class='btn btn-success' onclick=\"bluetooth_control('connect', '" + macAdd + "|" + nameMatch[1] + "')\"><i class='bi bi-arrows-angle-contract'> Kết Nối</i></button></td></tr>";
                    }
                }
            }
        });
        tableHTML += "</table>";
        document.getElementById("Bluetooth_Scan_devices").innerHTML = tableHTML;
    } else {
        document.getElementById("Bluetooth_Scan_devices").innerHTML = "<center><h5 class='card-title text-danger'>Không có danh sách thiết bị Bluetooth nào được tìm thấy</h5></center>";
    }
}

//Bluetooth xóa dữ liệu được lưu trong localStorage
function clearBluetoothDevices() {
    localStorage.removeItem('bluetoothDevices_Vbot');
    document.getElementById("Bluetooth_Scan_devices").innerHTML = "<center><h5 class='card-title text-danger'>Không có danh sách thiết bị Bluetooth nào được tìm thấy</h5></center>";
}

//Tải xuống tệp âm thanh từ url/Link online
function download_AUDIO_URL(url, name_title) {
    if (!url || !name_title) {
        show_message('Lỗi: URL hoặc tên bài hát không được để trống.');
        return;
    }
    loading("show");
    fetch(url)
        .then(response => {
            loading("hide");
            if (!response.ok) {
                throw new Error('Lỗi xảy ra, không thể tải file âm thanh từ ZingMP3');
            }
            return response.blob();
        })
        .then(blob => {
            const blobUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = name_title + '.mp3';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(blobUrl);
            showMessagePHP('Đã tải xuống file âm thanh: ' + name_title + '.mp3', 5);
			loading("hide");
        })
        .catch(error => {
            loading("hide");
            show_message('Lỗi, Không thể tải xuống file âm thanh: ' + error.message);
        });
}