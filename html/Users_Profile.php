<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
?>
<?php
if ($Config['contact_info']['user_login']['active']) {
  session_start();
  // Kiểm tra xem người dùng đã đăng nhập chưa và thời gian đăng nhập
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
?>
<?php
// Chuyển đổi danh sách định dạng hình ảnh thành chuỗi cho thuộc tính accept
$accept_types = implode(", ", array_map(function ($type) {
  return ".{$type}";
}, $allowed_image_types));

if (isset($_POST['save_change_info_name'])) {


  $jsonFilePath = $VBot_Offline . 'resource/VietNam_Localtion.json';
  $jsonData = file_get_contents($jsonFilePath);
  $data = json_decode($jsonData, true);
  // Lấy danh sách các tỉnh và quận từ mảng data
  $provinces = isset($data['province']) ? $data['province'] : [];
  $districts = isset($data['district']) ? $data['district'] : [];
  // Lấy ID tỉnh/quận từ POST
  $provinceId = $_POST['province_name'];
  $districtId = $_POST['district_name'];
  // Tìm kiếm tên tỉnh từ ID
  $selectedProvinceName = '';
  foreach ($provinces as $province) {
    if ($province['idProvince'] == $provinceId) {
      $selectedProvinceName = $province['name'];
      break;
    }
  }
  // Tìm tên quận từ ID
  $selectedDistrictName = '';
  foreach ($districts as $district) {
    if ($district['idDistrict'] == $districtId) {
      $selectedDistrictName = $district['name'];
      break;
    }
  }

  // Lưu lại tên tỉnh, quận và ID vào mảng $Config
  $Config['contact_info']['address']['province'] = $selectedProvinceName;
  $Config['contact_info']['address']['district'] = $selectedDistrictName;
  $Config['contact_info']['address']['id_province'] = $provinceId;
  $Config['contact_info']['address']['id_district'] = $districtId;

  #CẬP NHẬT Thông tin người dùng
  $Config['contact_info']['full_name'] = $_POST['full_name'];
  $Config['contact_info']['location']['latitude'] = floatval($_POST['latitude_name']);
  $Config['contact_info']['location']['longitude'] = floatval($_POST['longitude_name']);
  $Config['contact_info']['email'] = $_POST['email_name'];
  $Config['contact_info']['user_login']['user_password'] = $_POST['webui_password'];

  // Lưu cấu hình $Config vào file JSON
  file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

if (isset($_POST['save_change_user_login'])) {
  $Config['contact_info']['user_login']['active'] = isset($_POST['user_login_active']) ? true : false;
  $Config['contact_info']['user_login']['login_attempts'] = intval($_POST['login_attempts']);
  $Config['contact_info']['user_login']['login_lock_time'] = intval($_POST['login_lock_time']);
  // Lưu cấu hình $Config vào file JSON
  file_put_contents($Config_filePath, json_encode($Config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
?>
<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>

<body>
  <?php
  include 'html_header_bar.php';
  include 'html_sidebar.php';
  ?>
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
    </div>
    <section class="section profile">
      <div class="row">
        <div class="col-xl-4">
          <div class="card">
            <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
              <img src="<?php echo $Avata_File; ?>" alt="Profile" class="rounded-circle">
              <h2><?php echo $Config['contact_info']['full_name']; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-xl-8">
          <div class="card">
            <div class="card-body pt-3">
              <!-- Bordered Tabs -->
              <ul class="nav nav-tabs nav-tabs-bordered">
                <li class="nav-item">
                  <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">Tổng quan</button>
                </li>
                <li class="nav-item">
                  <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">Chỉnh sửa hồ sơ</button>
                </li>
                <li class="nav-item">
                  <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Cài đặt</button>
                </li>
                <li class="nav-item">
                  <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Đổi mật khẩu WebUI</button>
                </li>
                <li class="nav-item">
                  <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-forgot-password">Quên mật khẩu WebUI</button>
                </li>
              </ul>
              <div class="tab-content pt-2">
                <div class="tab-pane fade profile-overview active show" id="profile-overview" role="tabpanel">
                  <h5 class="card-title">Chi tiết hồ sơ</h5>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label ">Tên:</div>
                    <div class="col-lg-9 col-md-8"><?php echo $Config['contact_info']['full_name']; ?></div>
                  </div>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Địa Chỉ:</div>
                    <div class="col-lg-9 col-md-8"><?php echo $Config['contact_info']['address']['district'] . ", " . $Config['contact_info']['address']['province']; ?></div>
                  </div>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Vị Trí:</div>
                    <div class="col-lg-9 col-md-8">Kinh độ: <?php echo $Config['contact_info']['location']['longitude']; ?>, Vĩ Độ: <?php echo $Config['contact_info']['location']['latitude']; ?></div>
                  </div>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Email:</div>
                    <div class="col-lg-9 col-md-8"><?php echo $Config['contact_info']['email']; ?></div>
                  </div>
                </div>
                <div class="tab-pane fade profile-edit pt-3" id="profile-edit" role="tabpanel">
                  <!-- Profile Edit Form -->
                  <form class="row g-3 needs-validation" enctype="multipart/form-data" novalidate method="POST" action="" onsubmit="return validateForm_pass()">
                    <div class="row mb-3">
                      <label class="col-md-4 col-lg-3 col-form-label">Ảnh hồ sơ cá nhân:</label>
                      <div class="col-md-8 col-lg-9">
                        <img src="<?php echo $Avata_File; ?>" alt="Profile">
                        <div class="pt-2">
                          <div class="input-group">
                            <input class="form-control border-success" type="file" id="avataa_fileToUpload" accept="<?php echo $accept_types; ?>">
                            <button class="btn btn-success border-success" type="button" onclick="fileToUpload_avata()">Tải Lên</button>
                            <button type="button" name="remove_avata" id="remove_avata" class="btn btn-danger border-success" onclick="deleteFile('../../<?php echo $Avata_File; ?>')" title="Xóa Avata"><i class="bi bi-trash"></i></button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="full_name" class="col-md-4 col-lg-3 col-form-label">Tên <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                      <div class="col-md-8 col-lg-9">
                        <input required name="full_name" type="text" class="form-control border-success" id="full_name" value="<?php echo $Config['contact_info']['full_name']; ?>">
                        <div class="invalid-feedback">Vui Lòng Nhập Tên!</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-md-4 col-lg-3 col-form-label">Địa Chỉ <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                      <div class="col-md-8 col-lg-9">
                        <div class="input-group mb-3 border-success">
                          <div class="input-group-prepend">
                            <span class="input-group-text border-success" for="province_name">Tỉnh: </span>
                          </div>
                          <select required id="city-province" name="province_name" class="form-select border-success">
                            <option value="">-- Chọn Tỉnh/Thành Phố --</option>
                          </select>
                          <div class="invalid-feedback">Vui lòng chọn Tỉnh, Thành Phố của bạn</div>
                        </div>
                        <div class="input-group mb-3 border-success">
                          <div class="input-group-prepend">
                            <span class="input-group-text border-success" for="district_name">Huyện: </span>
                          </div>
                          <select required id="district-town" name="district_name" class="form-select border-success">
                            <option value="0">-- Chọn Quận/Huyện --</option>
                          </select>
                          <div class="invalid-feedback">Vui lòng chọn Quận, Huyện, Thị Xã của bạn</div>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label class="col-md-4 col-lg-3 col-form-label">Vị Trí <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                      <div class="col-md-8 col-lg-9">
                        <div class="input-group mb-3 border-success">
                          <div class="input-group-prepend">
                            <span class="input-group-text border-success">Kinh Độ: </span>
                          </div>
                          <input type="text" id="longitude_name" name="longitude_name" class="form-control border-success" placeholder="<?php echo $Config['contact_info']['location']['longitude']; ?>" value="<?php echo $Config['contact_info']['location']['longitude']; ?>">
                        </div>
                        <div class="input-group mb-3 border-success">
                          <div class="input-group-prepend">
                            <span class="input-group-text border-success">Vĩ Độ: </span>
                          </div>
                          <input type="text" name="latitude_name" id="latitude_name" class="form-control border-success" placeholder="<?php echo $Config['contact_info']['location']['latitude']; ?>" value="<?php echo $Config['contact_info']['location']['latitude']; ?>">
                        </div>
                        <center>
                          <button type="button" class="btn btn-info" onclick="getLocationData()">Lấy Vị Trí Hiện Tại</button>
                        </center>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="email_name" class="col-md-4 col-lg-3 col-form-label">Email <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                      <div class="col-md-8 col-lg-9">
                        <input required name="email_name" type="text" class="form-control border-success" id="email_name" placeholder="<?php echo $Config['contact_info']['email']; ?>" value="<?php echo $Config['contact_info']['email']; ?>">
                        <div class="invalid-feedback">Vui Lòng Nhập Email (Dùng để tìm lại mật khẩu và 1 số chức năng khác) !</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="webui_password" class="col-md-4 col-lg-3 col-form-label">Mật khẩu Web UI <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                      <div class="col-md-8 col-lg-9">
                        <input required name="webui_password" type="text" class="form-control border-success" id="webui_password" placeholder="<?php echo $Config['contact_info']['user_login']['user_password']; ?>" value="<?php echo $Config['contact_info']['user_login']['user_password']; ?>">
                        <div class="invalid-feedback">Vui Lòng Nhập Mật Khảu Đăng Nhập Web UI (Dùng để đăng nhập khi bạn bật đăng nhập trên web ui) !</div>
                      </div>
                    </div>
                    <div class="text-center">
                      <button type="submit" name="save_change_info_name" class="btn btn-primary rounded-pill">Lưu Hồ Sơ</button>
                    </div>
                  </form>
                  <!-- End Profile Edit Form -->
                </div>
                <div class="tab-pane fade pt-3" id="profile-settings" role="tabpanel">
                  <form class="row g-3 needs-validation" enctype="multipart/form-data" novalidate method="POST" action="">
                    <!-- Settings Form -->
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Đăng nhập Web UI:</label>
                      <div class="col-sm-9">
                        <div class="form-switch">
                          <input class="form-check-input" type="checkbox" name="user_login_active" id="user_login_active" <?php echo $Config['contact_info']['user_login']['active'] ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="user_login_active"> Bật để kích hoạt sử dụng mật khẩu đăng nhập vào giao diện WebUI VBot</label>
                        </div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="login_attempts" class="col-md-4 col-lg-3 col-form-label">Số Lần Đăng Nhập Sai Tối Đa <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                      <div class="col-md-8 col-lg-9">
                        <input required name="login_attempts" type="number" min="3" class="form-control border-success" id="login_attempts" placeholder="<?php echo $Config['contact_info']['user_login']['login_attempts']; ?>" value="<?php echo $Config['contact_info']['user_login']['login_attempts']; ?>">
                        <div class="invalid-feedback">Vui Lòng Nhập Số Lần Đăng Nhập Vào WebUI Khi Nhập Sai Mật Khẩu</div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="login_lock_time" class="col-md-4 col-lg-3 col-form-label">Thời Gian Chờ Đăng Nhập Sai Tối Đa (s/Giây) <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                      <div class="col-md-8 col-lg-9">
                        <input required name="login_lock_time" type="number" min="3" class="form-control border-success" id="login_lock_time" placeholder="<?php echo $Config['contact_info']['user_login']['login_lock_time']; ?>" value="<?php echo $Config['contact_info']['user_login']['login_lock_time']; ?>">
                        <div class="invalid-feedback">Vui Lòng Nhập Thời Gian Chờ Tối Đa Khi Nhập Sai Mật Khẩu</div>
                      </div>
                    </div>

                    <hr />
                    <div class="text-center">
                      <button type="submit" name="save_change_user_login" class="btn btn-primary rounded-pill">Lưu Cài Đặt</button>
                    </div>
                    <!-- End settings Form -->
                  </form>
                </div>
                <div class="tab-pane fade pt-3" id="profile-change-password" role="tabpanel">
                  <!-- Change Password Form -->
                  <div class="row mb-3">
                    <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Mật Khẩu Cũ <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                    <div class="col-md-8 col-lg-9">
                      <input required name="currentPassword" type="password" class="form-control border-success" id="currentPassword">
                      <div class="valid-feedback">Cần nhập mật khẩu cũ!</div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="newPassword" class="col-md-4 col-lg-3 col-form-label">Mật Khẩu Mới <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                    <div class="col-md-8 col-lg-9">
                      <input required name="newPassword" type="password" class="form-control border-success" id="newPassword">
                      <div class="valid-feedback">Cần nhập mật khẩu mới!</div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="renewPassword" class="col-md-4 col-lg-3 col-form-label">Nhập Lại Mật Khẩu Mới <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                    <div class="col-md-8 col-lg-9">
                      <input required name="renewPassword" type="password" class="form-control border-success" id="renewPassword">
                      <div class="valid-feedback">Cần nhập lại mật khẩu mới!</div>
                    </div>
                  </div>
                  <div class="text-center">
                    <button type="button" onclick="changePassword()" class="btn btn-primary">Đổi Mật Khẩu</button>
                  </div>
                </div>
                <div class="tab-pane fade pt-3" id="profile-forgot-password" role="tabpanel">
                  <!-- Change Password Form -->
                  <div class="row mb-3">
                    <label for="forgotPassword_email" class="col-md-4 col-lg-3 col-form-label">Nhập Email <font color="red" size="6" title="Bắt Buộc Nhập">*</font>:</label>
                    <div class="col-md-8 col-lg-9">
                      <input required name="forgotPassword_email" type="text" class="form-control border-success" id="forgotPassword_email" value="<?php echo $Config['contact_info']['email']; ?>">
                      <div class="valid-feedback">Cần nhập địa chỉ email để lấy lại mật khẩu</div>
                    </div>
                  </div>
                  <div class="text-center">
                    <button type="button" onclick="forgotPassword()" class="btn btn-primary">Lấy Mật Khẩu</button>
                  </div>
                </div>
              </div>
              <!-- End Bordered Tabs -->
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
  <?php
  include 'html_footer.php';
  ?>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <?php
  include 'html_js.php';
  ?>
  <script>
    //Kiểm tra nhật mật khẩu webui nếu chứa khoảng trống
    function validateForm_pass() {
      loading('show');
      const webui_password = document.getElementById('webui_password');
      const email_name = document.getElementById('email_name');
      const longitude_name = document.getElementById('longitude_name');
      const latitude_name = document.getElementById('latitude_name');
      if (/\s/.test(webui_password.value)) {
        show_message('Mật khẩu đăng nhập WebUI không được phép chứa khoảng trống hoặc dấu cách!');
        loading('hide');
        return false;
      }
      if (webui_password.value.trim() === '') {
        show_message('Cần nhập mật khẩu đăng nhập WebUI');
        loading('hide');
        return false;
      }

      if (/\s/.test(email_name.value)) {
        show_message('Địa chỉ Email không được phép chứa khoảng trống hoặc dấu cách!');
        loading('hide');
        return false;
      }
      if (email_name.value.trim() === '') {
        show_message('Cần nhập địa chỉ Email của bạn khi quên mật khẩu WebUI');
        loading('hide');
        return false;
      }

      if (/\s/.test(longitude_name.value)) {
        show_message('Kinh độ không được phép chứa khoảng trống hoặc dấu cách!');
        loading('hide');
        return false;
      }
      if (/\s/.test(latitude_name.value)) {
        show_message('Vĩ độ không được phép chứa khoảng trống hoặc dấu cách!');
        loading('hide');
        return false;
      }
      loading('hide');
      return true;
    }

    //Tải lên avata
    function fileToUpload_avata() {
      loading('show');
      var fileInput = document.getElementById('avataa_fileToUpload');
      if (fileInput.files.length === 0) {
        show_message('Chưa chọn tệp nào.');
        loading('hide');
        return;
      }
      var formData = new FormData();
      formData.append('fileToUpload_avata', fileInput.files[0]);
      formData.append('upload_avata', 'true');
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'includes/php_ajax/Upload_file_path.php?upload_avata', true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            var response = JSON.parse(xhr.responseText);
            console.log(response);
            if (response.success) {
              loading('hide');
              show_message(response.message + "<br/>Hãy tải lại trang để áp dụng");
            } else {
              loading('hide');
              show_message(response.message);
            }
          } catch (e) {
            loading('hide');
            show_message('Lỗi khi xử lý phản hồi từ máy chủ: ' + e);
          }
        } else {
          loading('hide');
          show_message('Yêu cầu bị lỗi với mã trạng thái: ' + xhr.status);
        }
      };
      xhr.onerror = function() {
        loading('hide');
        show_message('Yêu cầu thất bại');
      };
      xhr.send(formData);
    }

    //Thay đổi mật khẩu web UI
    function changePassword() {
      loading('show');
      var currentPassword = document.getElementById("currentPassword").value;
      var newPassword = document.getElementById("newPassword").value;
      var renewPassword = document.getElementById("renewPassword").value;

      if (/\s/.test(currentPassword)) {
        show_message('Mật khẩu cũ không được phép chứa khoảng trống hoặc dấu cách!');
        loading('hide');
        return false;
      }
      if (/\s/.test(newPassword)) {
        show_message('Mật khẩu mới không được phép chứa khoảng trống hoặc dấu cách!');
        loading('hide');
        return false;
      }
      if (/\s/.test(renewPassword)) {
        show_message('Nhập lại mật khẩu mới không được phép chứa khoảng trống hoặc dấu cách!');
        loading('hide');
        return false;
      }
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "Login.php?change_password&currentPassword=" + encodeURIComponent(currentPassword) + "&newpassword=" + encodeURIComponent(newPassword) + "&renewpassword=" + encodeURIComponent(renewPassword), true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var response = JSON.parse(xhr.responseText);
          if (response.success) {
            loading('hide');
            show_message(response.message);
          } else {
            loading('hide');
            show_message("Lỗi: " + response.message);
          }
        }
      };
      xhr.send();
    }

    //Lựa chọn địa danh
    const apiURL = 'includes/php_ajax/Show_file_path.php?read_file_path&file=' + '<?php echo $VBot_Offline; ?>' + 'resource/VietNam_Localtion.json';
    $(document).ready(function() {
      $.get(apiURL, function(response) {
        if (response.success && response.data) {
          const data = response.data;
          //console.log(data);
          const $provinceSelect = $('#city-province');
          const provinces = data.province || [];
          provinces.sort((a, b) => a.name.localeCompare(b.name));
          //dropdown tỉnh
          provinces.forEach(province => {
            const isSelectedProvince = (province.idProvince == '<?php echo $Config['contact_info']['address']['id_province']; ?>') ? 'selected' : '';
            $provinceSelect.append('<option value="' + province.idProvince + '" ' + isSelectedProvince + '>' + province.name + '</option>');
          });
          //Kiểm tra nếu đã có tỉnh được chọn thì load quận tương ứng
          const selectedProvinceId = '<?php echo $Config['contact_info']['address']['id_province']; ?>';
          loadDistricts(data.district || [], selectedProvinceId);
          // Thêm sự kiện cho tỉnh được chọn
          $provinceSelect.on('change', function() {
            const idProvince = $(this).val();
            loadDistricts(data.district || [], idProvince);
          });
        } else {
          showMessagePHP('Dữ liệu không hợp lệ: ' + (response.message || 'Không có thông tin chi tiết.'), 5);
        }
      }).fail(function(error) {
        showMessagePHP('Lỗi khi tải dữ liệu thông tin địa chỉ: ' + error, 5);
      });
      // Load các quận/huyện cho tỉnh đã chọn
      function loadDistricts(districtList, idProvince) {
        const $districtSelect = $('#district-town');
        $districtSelect.empty().append('<option value="">-- Chọn Quận/Huyện --</option>');
        // Lọc và sắp xếp các quận/huyện theo tỉnh
        const filteredDistricts = districtList
          .filter(d => d.idProvince == idProvince)
          .sort((a, b) => a.name.localeCompare(b.name));
        //Thêm các option cho quận, và thêm thuộc tính selected nếu quận đã chọn
        filteredDistricts.forEach(district => {
          const isSelectedDistrict = (district.idDistrict == '<?php echo $Config['contact_info']['address']['id_district']; ?>') ? 'selected' : '';
          $districtSelect.append('<option value="' + district.idDistrict + '" ' + isSelectedDistrict + '>' + district.name + '</option>');
        });
      }
    });

    //Lấy vị trí hiện tại
    function getLocationData() {
      loading('show');
      $.ajax({
        url: 'https://ipinfo.io/json',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          loading('hide');
          const loc = data.loc.split(',');
          const latitude = loc[0];
          const longitude = loc[1];
          $('#latitude_name').val(latitude);
          $('#longitude_name').val(longitude);
          showMessagePHP('Đã cập nhật dữ liệu vị trí hiện tại, Vị trí được phát hiện: ' + data.city + ', ' + data.region, 10);
        },
        error: function(xhr, status, error) {
          loading('hide');
          show_message('Không thể lấy thông tin vị trí: ' + error);
        }
      });
    }
  </script>
</body>

</html>