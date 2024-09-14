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

<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
<!-- css ChatBot -->
<style>
        html, body {
            height: 100%;
            margin: 0;
        }

        .container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }



        #chatbox_wrapper {
            flex: 1;
            overflow: hidden;
           /* border: 1px solid #ccc; 
            padding: 7px; */
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        #chatbox {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 10px;
        }

        .message {
            position: relative;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            word-wrap: break-word;
            background-color: #f5f5f5;
        }

        .bot-message {
            background-color: #e0f7fa;
        }

        .user-message {
            background-color: #c8e6c9;
            text-align: right;
        }

        .message-time {
            font-size: 0.8em;
            color: #888;
            margin-bottom: 5px;
        }

        .typing-indicator {
            font-style: italic;
            color: gray;
            margin-top: 10px;
        }

        .input-group {
            margin-top: 10px;
        }

        .delete_message_chatbox {
            position: absolute;
            top: -5px;
            right: 10px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            visibility: hidden;
        }

        .message:hover .delete_message_chatbox {
            visibility: visible;
        }

        .message:hover {
            background-color: #e2e2e2;
        }
</style>
</head>
<body>
<!-- ======= Header ======= -->
<?php
// include 'html_header_bar.php'; 
?>
<!-- End Header -->

  <!-- ======= Sidebar ======= -->
<?php
include 'html_sidebar.php';
?>
<!-- End Sidebar-->

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Thông tin cá nhân</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Người dùng</li>
          <li class="breadcrumb-item active">Hồ sơ</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
	    <section class="section">
        <div class="row">

     
            </div>
                <div id="chatbox_wrapper">
                    <div id="chatbox"></div>
                </div>
                <div class="input-group mb-3">
                    <input type="text" class="form-control border-success" id="user_input_chatbox" placeholder="Nhập tin nhắn...">
                      <button id="send_button_chatbox" class="btn btn-primary border-success" title="Gửi dữ liệu"><i class="bi bi-send"></i>
                    </button>
					                <button class="btn btn-success"><i class="bi bi-arrow-repeat" onclick="loadMessages()" title="Tải lại Chatbox"></i>
                </button>
					<button id="clear_button_chatbox" class="btn btn-warning" onclick="clearMessages()" title="Xóa lịch sử Chat"><i class="bi bi-trash"></i>
                </button>

                </div>
           
		</div>
		</section>
	
</main>


  <!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->
    <script>
        function scrollToBottom() {
            const chatbox = document.getElementById('chatbox');
            console.log('Scroll Top:', chatbox.scrollTop);
            console.log('Scroll Height:', chatbox.scrollHeight);
            chatbox.scrollTop = chatbox.scrollHeight;
        }

        function addMessage(message) {
            const chatbox = document.getElementById('chatbox');
            chatbox.innerHTML += '<div class="message user-message"><div class="message-content"><b>' + new Date().toLocaleTimeString() + '</b> ' + message + '</div></div>';
            setTimeout(scrollToBottom, 100); // Đảm bảo DOM đã cập nhật
        }

        document.addEventListener('DOMContentLoaded', scrollToBottom);
    </script>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- Nghe thử file âm thanh 
<audio id="audioPlayer" style="display: none;" controls></audio>-->

  <!-- Template Main JS File -->
<?php
include 'html_js.php';
?>

</body>
</html>