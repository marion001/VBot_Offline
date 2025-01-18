<!--
Code By: Vũ Tuyển
Designed by: BootstrapMade
Facebook: https://www.facebook.com/TWFyaW9uMDAx
-->
<head>
  <meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">



  <!-- <title>VBot Assistant</title> -->
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">
  
  

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 20 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
  <style>
          .overlay_loading {
            display: none; /* Ẩn overlay theo mặc định */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Nền tối */
            z-index: 9999;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 1.5rem;
        }
  </style>
    <style>
        /* CSS hiển thời gian */
        #container_time {
			background:#f1f1f1;
			border:2px solid #999;
			border-radius:10px;
			padding: 2px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        #day-date-container_time {
            display: flex;
            justify-content: center;
    
        }
        /* Ẩn phần ngày tháng, thời gian khi trên giao diện mobile */
        @media (max-width: 768px) {

            #container_time {
                display: none;
            }
			
    #notification {
        max-width: 100%;
		width: auto;
    }
	    #number_notification_1 {
        max-width: 100%;
        width: auto;
    }
			
        }
    </style>
<!-- Css hiển thị thông báo cho code php -->
    <style>
        #toast {
            visibility: hidden;
            position: fixed;
            bottom: 10px;
            right:55px;
            background: #333;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        #toast button {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            position: absolute;
            top: 5px;
            right: 5px;
        }
        #toastMessage {
            margin-right: 20px; /* Tạo khoảng trống cho nút đóng */
        }
    </style>
<!-- Css hiển thị media player -->
    <style>

        #media-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        #media-cover {
            width: 150px;
            height: 150px;
            border-radius: 10px;
        }
        #progress-container {
            width: 100%;
        }
        #progress-bar {
            width: 100%;
        }
        #time-info {
            text-align: right;
            margin-top: 5px;
        }
		
    </style>
	
	<!--script Hiển thị thông báo Mesage php -->
    <script>
        function showMessagePHP(message, timeout=15) {
            var toast = document.getElementById('toast');
            var toastMessage = document.getElementById('toastMessage');
            toastMessage.innerText = message;
            toast.style.visibility = 'visible';
            
            // Ẩn thông báo sau 30 giây
            setTimeout(function() {
                toast.style.visibility = 'hidden';
            }, timeout * 1000);
        }

        function hideToast() {
            document.getElementById('toast').style.visibility = 'hidden';
        }
		
		//Hiển thị và đóng thông báo Message
function show_message(message) {
    document.querySelector('#notificationModal .modal-body').innerHTML = message;
    $('#notificationModal').modal('show');
}

function close_message() {
    $('#notificationModal').modal('hide');
}




//xử lý Nghe thử các file âm thanh
function playAudio_upgrade(filePath) {
    //loading("show");
    function getMimeType(extension) {
        const mimeTypes = {
            'mp3': 'audio/mpeg',
            'wav': 'audio/wav',
            'ogg': 'audio/ogg',
            'aac': 'audio/aac',
            'flac': 'audio/flac',
            // Thêm các định dạng tệp âm thanh khác nếu cần
        };

        return mimeTypes[extension.toLowerCase()] || 'application/octet-stream';
    }
    const audioPlayer = document.getElementById('audioPlayer');
    // Kiểm tra nếu filePath bắt đầu bằng 'http'
    if (filePath.startsWith('http')) {
        //loading("hide");

        // Kiểm tra nếu filePath là '.m3u8'
        if (filePath.endsWith('.m3u8')) {
            //Chạy playHLS nếu là m3u8
            playHLS(filePath);
        } else {
            audioPlayer.src = filePath;
            audioPlayer.load();
            audioPlayer.play().catch(function(error) {
                show_message('Lỗi khi phát âm thanh: ' + error.message);
            });
        }
        return; // Kết thúc hàm nếu đã phát trực tiếp
    }

    const xhr = new XMLHttpRequest();

    // Gửi yêu cầu GET tới server để lấy tệp âm thanh dưới dạng base64
    xhr.open('GET', 'includes/php_ajax/Show_file_path.php?audio_b64&path=' + encodeURIComponent(filePath), true);
    xhr.responseType = 'text';
    xhr.onload = function() {
        //loading("hide");
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                const base64Audio = response.data.base64Content;
                const mimeType = getMimeType(response.data.fileExtension);
                audioPlayer.src = 'data:' + mimeType + ';base64,' + base64Audio;
                audioPlayer.load();
                audioPlayer.play();
            } else {
                show_message('Lỗi khi tìm nạp âm thanh: ' + response.error);
            }
        }
    };

    xhr.onerror = function() {
        //loading("hide");
        show_message('Yêu cầu phát âm thanh không thành công');
    };

    xhr.send();
}

    </script>
	

</head>
<div id="toast"><span id="toastMessage"></span><button onclick="hideToast()">×</button></div>

<!-- Thông báo Mesage html_head.php -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
			<button type="button" class="btn btn-danger" onclick="close_message()" title="Tắt thông báo"><i class="bi bi-x-circle-fill"></i></button>
 
            <div class="modal-body">
			
                <!-- Nội dung thông báo ở đây sẽ được cập nhật bởi JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger rounded-pill" onclick="close_message()">Đóng</button>
            </div>
        </div>
    </div>
</div>
<!--Kết Thúc Thông báo Mesage -->