<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';

#Quên Mật Khẩu
if (isset($_GET['forgot_password'])) {
    $my_email = $_GET['mail'];
    if (!empty($my_email)) {
        if ($my_email === $Config['contact_info']['email']) {
            // Hiển thị mật khẩu hoặc gửi liên kết đặt lại mật khẩu
            $response = [
                "success" => true,
                "message" => $Config['contact_info']['user_login']['user_password']
            ];
            // Ví dụ: Gửi email chứa liên kết đặt lại mật khẩu
            // sendResetLink($my_email);
        } else {
            $response = [
                "success" => false,
                "message" => "Email không khớp!"
            ];
        }
    } else {
        $response = [
            "success" => false,
            "message" => "Vui lòng nhập email!"
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($response);
	exit();
}
//Đổi mật khẩu
if (isset($_GET['change_password'])) {
	header('Content-Type: application/json');
    $currentPassword = $_GET['currentPassword'];
    $newPassword = $_GET['newpassword'];
    $renewPassword = $_GET['renewpassword'];
    // Kiểm tra xem tất cả các tham số có giá trị không
    if (!empty($currentPassword) && !empty($newPassword) && !empty($renewPassword)) {
        // Kiểm tra xem mật khẩu cũ có khớp với mật khẩu hiện tại không
        if ($currentPassword === $Config['contact_info']['user_login']['user_password']) {
            // Kiểm tra độ dài mật khẩu mới
            if (strlen($newPassword) >= 6 && strlen($newPassword) <= 32) {
                // Kiểm tra xem mật khẩu mới và nhập lại mật khẩu mới có khớp nhau không
                if ($newPassword === $renewPassword) {
                    //Tiến hành cập nhật mật khẩu mới
					$Config['contact_info']['user_login']['user_password'] = $renewPassword;

					file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                    $response = [
                        "success" => true,
                        "message" => "Mật khẩu đã được thay đổi thành công!"
                    ];
                } else {
                    $response = [
                        "success" => false,
                        "message" => "Mật khẩu mới và xác nhận mật khẩu không khớp!"
                    ];
                }
            } else {
                $response = [
                    "success" => false,
                    "message" => "Mật khẩu mới phải từ 6 đến 32 ký tự!"
                ];
            }
        } else {
            $response = [
                "success" => false,
                "message" => "Mật khẩu cũ không đúng!"
            ];
        }
    } else {
        $response = [
            "success" => false,
            "message" => "Vui lòng nhập đầy đủ thông tin!"
        ];
    }
echo json_encode($response);
exit();
}

session_start();
//Đăng xuất
if (isset($_GET['logout'])) {
unset($_SESSION['user_login']);
header('Location: Login.php');
exit;
}


if ($Config['contact_info']['user_login']['active']){
// Kiểm tra xem người dùng đã đăng nhập chưa
if (isset($_SESSION['user_login'])) {
    // Nếu đã đăng nhập, chuyển hướng đến trang index
    header('Location: index.php');
    exit;
}
}else{
    header('Location: index.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_user = $_POST['yourPassword'];

    // Kiểm tra mật khẩu trực tiếp
	// Thay 'password' bằng mật khẩu thực tế của bạn
    if ($password_user === $Config['contact_info']['user_login']['user_password']) {
        $_SESSION['user_login'] = [
			// Lưu mật khẩu vào session để kiểm tra khi đăng xuất
            //'password' => $password_user, 
            'login_time' => time()
        ];
        header('Location: index.php');
        exit;
    } else {
        $error = "Sai mật khẩu!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>

<body>
<!-- Loading Mesage-->
    <div id="loadingOverlay" class="overlay_loading">
	<div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
<div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status">
  <span class="sr-only">________</span>
</div>
<div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
</div>
    </div>
<!--Kết thúc Loading Mesage-->
<!-- Thông báo Mesage html_slidebar.php -->
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
  <main>
    <div class="container">
      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
              <div class="d-flex justify-content-center py-4">
                <a href="index.php" class="logo d-flex align-items-center w-auto">
                  <img src="assets/img/logo.png" alt="">
                  <span class="d-none d-lg-block">VBot Assistant</span>
                </a>
              </div><!-- End Logo -->



<div class="card">
            <div class="card-body">

              <!-- Default Tabs -->
			  
              <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Đăng Nhập</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="quenmatkhau-tab" data-bs-toggle="tab" data-bs-target="#quenmatkhau" type="button" role="tab" aria-controls="quenmatkhau" aria-selected="false" tabindex="-1">Quên Mật Khẩu</button>
                </li>
              </ul>
              <div class="tab-content pt-2">
                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
  
                  <div class="pt-4 pb-2">
                    <h5 class="card-title text-center pb-0 fs-4">Đăng Nhập Hệ Thống</h5>
                  </div>
				    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
                  <form class="row g-3 needs-validation" novalidate method="POST" action="">

                    <div class="col-12">
                      <label for="yourPassword" class="form-label">Mật khẩu</label>
                      <input type="password" name="yourPassword" class="form-control border-success" id="yourPassword" required>
                      <div class="invalid-feedback">Vui lòng nhập mật khẩu của bạn!</div>
                    </div>

                  
                    <div class="d-grid gap-2 mt-3">
                      <button onclick="loading('show')" class="btn btn-primary rounded-pill" type="submit">Đăng nhập</button>
                    </div>
			
                  </form>

             
           
				</div>
                <div class="tab-pane fade" id="quenmatkhau" role="tabpanel" aria-labelledby="quenmatkhau-tab">
				
				
				
				             <div class="col-12">
                    <h5 class="card-title text-center pb-0 fs-4">Quên Mật Khẩu</h5>
                  </div>
				       <div class="col-12">
                      <label for="forgotPassword_email" class="form-label">Nhập Email</label>
                      <input type="text" placeholder="Nhập Email để lấy lại mật khẩu" name="forgotPassword_email" class="form-control border-success" id="forgotPassword_email" required>
                    
                    </div>
					<div class="d-grid gap-2 mt-3">
                      <button type="button" onclick="forgotPassword()" class="btn btn-primary rounded-pill">Lấy Mật Khẩu</button>
                    </div>
			  </div>
         
              </div><!-- End Default Tabs -->

            </div>
          </div>







              <div class="credits">
                <!-- All the links in the footer should remain intact. -->
                <!-- You can delete the links only if you purchased the pro version. -->
                <!-- Licensing information: https://bootstrapmade.com/license/ -->
                <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
                Code by <a href="https://www.facebook.com/TWFyaW9uMDAx" target="_bank">(<i class="bi bi-facebook"></i> Vũ Tuyển)</a> <a href="https://github.com/marion001/VBot_Offline" target="_bank"><i class="bi bi-github"></i> GitHub</a> <a href="https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ" target="_bank"><i class="bi bi-folder2-open"></i> VBot IMG</a>, Designed by: <a href="https://bootstrapmade.com/" target="_bank">BootstrapMade</a>
              </div>

            </div>
          </div>
        </div>

      </section>

    </div>
  </main><!-- End #main -->


  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<p style="display: none;" id="loading_show"></p>
<!-- Nghe thử file âm thanh -->
<!-- <audio id="audioPlayer" style="display: none;" controls></audio> -->

  <!-- Template Main JS File -->
<?php
include 'html_js.php';
?>

</body>

</html>