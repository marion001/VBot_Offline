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
<?php
//ĐỌc Nội Dung File custom_command_file
$Hass_Custom_Json = $VBot_Offline . $Config['home_assistant']['custom_commands']['custom_command_file'];
// Kiểm tra nếu file không tồn tại
if (!file_exists($Hass_Custom_Json)) {
    // Tạo file rỗng nếu không tồn tại
    file_put_contents($Hass_Custom_Json, json_encode(['intents' => []], JSON_PRETTY_PRINT));
    // Chmod 0777 cho file
    chmod($Hass_Custom_Json, 0777);
}

// Khởi tạo biến $hassData_all là mảng rỗng để tránh lỗi undefined variable
$hassData_all = [];
$filePath_HASS = $VBot_Offline . 'resource/hass/Home_Assistant.json';
// Kiểm tra nếu file không tồn tại
if (!file_exists($filePath_HASS)) {
    // Tạo file rỗng nếu không tồn tại
    file_put_contents($filePath_HASS, json_encode(['get_hass_all' => []], JSON_PRETTY_PRINT));
    
    // Chmod 0777 cho file
    chmod($filePath_HASS, 0777);
}
$data = json_decode(file_get_contents($filePath_HASS), true);
// Lấy dữ liệu từ get_hass_all
$hassData_all = $data['get_hass_all'] ?? [];

?>
<?php

// Mảng lưu thông báo lỗi
$errorMessages = [];
// Mảng để lưu những intent hợp lệ
$valid_intents = [];
// Kiểm tra xem có dữ liệu POST không để lưu lại thay đổi
#if ($_SERVER['REQUEST_METHOD'] === 'POST') {
if (isset($_POST['save_custom_home_assistant'])) {
    // Lấy dữ liệu từ form
    $intents = isset($_POST['intents']) ? $_POST['intents'] : null;
    // Kiểm tra nếu intents tồn tại và là mảng
    if (is_array($intents)) {
        // Khởi tạo mảng cho intents hợp lệ
        $valid_intents = [];
        // Khởi tạo mảng cho thông báo lỗi
        $errorMessages = [];
        // Kiểm tra từng intent trước khi lưu
        foreach ($intents as $index => $intent) {
            $name = trim($intent['name']);
            $entityid = trim($intent['entityid']);
            $action = trim($intent['action']);
            $friendly_name = trim($intent['friendly_name']);
            $questions = trim($intent['questions']);
            // Kiểm tra nếu có trường nào bị thiếu
            if (empty($entityid) || empty($action) || empty($questions)) {
                // Thêm thông báo lỗi cho intent có lỗi
                $errorMessages[] = "Giá trị tùy chỉnh thứ <b>" . ($index + 1) . "</b> không được lưu vì điền không đủ thông tin như: Entity ID, Hành Động hoặc Câu Lệnh Thực Thi.";
            } else {
                // Nếu đủ dữ liệu, tách các câu hỏi thành mảng và thêm vào mảng hợp lệ
                $intent['questions'] = array_map('trim', explode("\n", $questions));
                $valid_intents[] = $intent; // Lưu intent hợp lệ
            }
        }
    } else {
        // Nếu intents không hợp lệ hoặc không tồn tại, khởi tạo mảng trống
        $valid_intents = [];
        $errorMessages[] = "Không có các Tác Vụ tùy chỉnh nào cho Home Assistant được lưu";
    }

    // Đọc nội dung file JSON hiện tại
    $fileData = json_decode(file_get_contents($Hass_Custom_Json), true);
    
    // Cập nhật mảng intents trong dữ liệu file
    $fileData['intents'] = $valid_intents; // Cập nhật dù là mảng rỗng
    
    // Ghi lại toàn bộ nội dung vào file JSON (lưu mảng intents mới, dù là mảng rỗng)
    file_put_contents($Hass_Custom_Json, json_encode($fileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Thông báo thành công hoặc lỗi
    if (!empty($valid_intents)) {
        $successMessage = "Lưu dữ liệu thành công!";
    } else {
        $successMessage = "Không có các Tác Vụ tùy chỉnh nào cho Home Assistant được lưu";
    }
}


// Đọc file JSON hiện tại
$json_data_custom = file_get_contents($Hass_Custom_Json);
$intents = json_decode($json_data_custom, true);
?>


<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<head>
   <style>
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
			z-index: 4; 
        }

        .scroll-to-bottom {
            bottom: 15px;
        }

        .scroll-to-top {
            bottom: 60px;
        }

    </style>
<style>
#suggestions {
    position: absolute;
    border: 1px solid #ccc;
    background: #e9e9e9;
    z-index: 3;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
}

#suggestions div {
    padding: 10px;
    cursor: pointer;
}

#suggestions div:hover {
    background-color: #e1a4a4;
}
</style>
<link href="assets/vendor/prism/prism.min.css" rel="stylesheet">
     <style>
        #modal_dialog_show_Home_Assistant {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            max-width: calc(100vw - 40px);
        }

        #modal_dialog_show_Home_Assistant .modal-content {
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
    </style>
<script>
	
let data_home_assistant = [];

data_home_assistant = <?php echo json_encode($hassData_all); ?>;

	
// Hàm thêm intent mới
function addNewIntent() {
    const container = document.getElementById('intents-container');
    const newIndex = document.querySelectorAll('.intent').length; // Số lượng hiện tại của intent

    // Tạo thẻ div mới chứa các trường input
    const newIntent = 
        '<div class="intent" id="intent_' + newIndex + '" style="margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;">' +
		
			'<div class="input-group mb-3">' +
            '<span class="input-group-text border-success" for="name_' + newIndex + '">Tên Tác Vụ&nbsp;<i class="bi bi-question-circle-fill" onclick="show_message(\'Tên này chỉ là để cho bạn dễ nhận diện và hình dung, thích đặt tên là gì thì đặt nhé\')"></i> &nbsp;:</span>' +
            '<input class="form-control border-success" placeholder="Tên định danh để phân biệt với các câu lệnh, cấu hình khác" type="text" name="intents[' + newIndex + '][name]" id="name_' + newIndex + '">' +
			'</div>' +


			'<div class="input-group mb-3">' +
            '<span class="input-group-text border-danger" for="friendly_name_' + newIndex + '">Friendly Name&nbsp;<i class="bi bi-question-circle-fill" onclick="show_message(\'Không cần nhập gì ở đây, hệ thống sẽ tự động điền thông tin\')"></i>&nbsp;:</span>' +
            '<input readonly class="form-control border-danger" placeholder="Không cần điền dữ liệu ở đây" type="text" name="intents[' + newIndex + '][friendly_name]" id="friendly_name_' + newIndex + '">' +
			'</div>' +


			'<div class="input-group mb-3">' +
            '<span class="input-group-text border-success" for="entityid_' + newIndex + '">Entity ID&nbsp;<i class="bi bi-question-circle-fill" onclick="show_message(\'Cần nhập đúng Entity ID thiết bị trong Home Assistant của bạn\')"></i>&nbsp;:</span>' +
            '<input class="form-control border-success" placeholder="Điền dữ liệu hoặc Nhập tên để tìm kiếm" type="text" name="intents[' + newIndex + '][entityid]" id="entityid_' + newIndex + '">' +
			'</div>' +

			'<div class="input-group mb-3">' +
            '<span class="input-group-text border-success" for="action_' + newIndex + '">Hành Động:</span>' +
			'<select class="form-select border-success" name="intents[' + newIndex + '][action]" id="action_' + newIndex + '">' +
			'<option value="">-- Chọn Hành Động Cần Thực Hiện --</option>' +
			'<option value="turn_on">Bật (turn_on)</option>' +
			'<option value="turn_off">Tắt (turn_off)</option>' +
			'</select>' +
			'</div>' +

            '<label for="questions_' + newIndex + '">Câu Lệnh Thực Thi <i class="bi bi-question-circle-fill" onclick="show_message(\'Nếu nhiều hơn 1 câu lệnh thì sẽ cần xuống dòng, Mỗi câu lệnh tương ứng với 1 dòng\')"></i> :</label><br>' +
            '<textarea class="form-control border-success" placeholder="Nhập câu lệnh cần gán để điều khiển thiết bị, nếu nhiều hơn 1 câu lệnh thì cần xuống dòng, mỗi câu lệnh là 1 dòng" name="intents[' + newIndex + '][questions]" id="questions_' + newIndex + '" rows="5"></textarea><br>' +

            '<center><button class="btn btn-danger rounded-pill" type="button" onclick="removeIntent(' + newIndex + ')"><i class="bi bi-trash" type="button"></i> Xóa</button><center>' +
        '</div>';

    // Thêm intent mới vào container
    container.insertAdjacentHTML('beforeend', newIntent);
	
// Gọi lại hàm để gán sự kiện
initializeSearchInputs();
}


// Hàm xóa intent và hiển thị thông báo trước khi xóa hẳn
function removeIntent(index, intent_name) {
	
    if (!confirm("Bạn có chắc chắn muốn xóa tác vụ: '" + intent_name + "' này không?")) {
        return;
    }
	
    const intentDiv = document.getElementById('intent_' + index);
    if (intentDiv) {
		showMessagePHP("Đã xóa tạm: " +intent_name, 3)
        intentDiv.innerHTML = '<h5 class="card-title"><font color=red>Sẽ thực thi xóa: '+intent_name+',</font> đang chờ (Lưu Thay Đổi) để áp dụng</h5>';
    } else {
		show_message("Không tìm thấy intent với index: " +index);
    }
}

    </script>
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
      <h1>Lệnh Tùy Chỉnh Home Assistant <i class="bi bi-question-circle-fill" onclick="show_message('- Áp dụng được với switch, script, automation, v..v...<br/><br/>- Tệp Json cấu hình nằm tại đường dẫn path: <b><?php echo $Config['home_assistant']['custom_commands']['custom_command_file']; ?></b>')"></i></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item">Người dùng</li>
          <li class="breadcrumb-item active">Home Assistant Custom Command</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
	    <section class="section">
        <div class="row">


<!-- Form để chỉnh sửa các giá trị trong file JSON -->
<?php
// Hiển thị thông báo lỗi nếu có
if (!empty($errorMessages)) {
	echo '<div class="alert alert-danger alert-dismissible fade show" id="message_error" role="alert">';
    echo '<ul style="color: red;">';
    foreach ($errorMessages as $errorMessage) {
        echo '<li>' . $errorMessage . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

// Hiển thị thông báo thành công nếu có
if (!empty($successMessage)) {
echo '<script type="text/javascript">';
echo 'showMessagePHP("' . addslashes($successMessage) . '", 3);';
echo '</script>';
}

echo '<center>
<button type="button" class="btn btn-success rounded-pill" title="Đồng bộ dữ liệu từ Home Assistant" onclick="get_hass_all()">
    <i class="bi bi-arrow-repeat"></i> Đồng Bộ và Lưu Dữ Liệu
</button>

<button type="button" class="btn btn-danger rounded-pill" title="Xóa dữ liệu Đã Đồng Bộ từ Home Assistant" onclick="del_get_hass_all()">
    <i class="bi bi-trash"></i> Xóa dữ liệu Đã Đồng Bộ
</button>

<button type="button" class="btn btn-warning rounded-pill" title="Xem dữ liệu Đã Đồng Bộ từ Home Assistant" id="openModalBtn_Home_Assistant">
    <i class="bi bi-trash"></i> Xem dữ liệu Cấu Hình Json
</button>
</center><br/><br/>';

// Form để chỉnh sửa các giá trị trong file JSON
echo '<form method="POST">';
echo '<div id="intents-container">';

if (!empty($intents['intents'])) {


    foreach ($intents['intents'] as $index => $intent) {
		
$action_value = htmlspecialchars($intent['action']);
echo '<div class="card accordion" id="accordion_button_HASS_Custom_'.$index.'">

<div class="card-body">
<div class="intent" id="intent_' . $index . '">
<h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_button_HASS_Custom_'.$index.'" aria-expanded="false" aria-controls="collapse_button_HASS_Custom_'.$index.'">
' . htmlspecialchars($intent['name']) . ' (' . ($action_value === "turn_on" ? "<font color=green>Bật</font>" : "<font color=red>Tắt</font>") . '):</h5>
<div id="collapse_button_HASS_Custom_'.$index.'" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#collapse_button_HASS_Custom_'.$index.'">


<div class="input-group mb-3">
    <span class="input-group-text border-success" for="name_' . $index . '">Tên Tác Vụ&nbsp;<i class="bi bi-question-circle-fill" onclick="show_message(\'Tên này chỉ là để cho bạn dễ nhận diện và hình dung, thích đặt tên là gì thì đặt nhé\')"></i> &nbsp;:</span>
<input type="text" class="form-control border-success" name="intents[' . $index . '][name]" value="' . htmlspecialchars($intent['name']) . '" id="name_' . $index . '">
</div>


<div class="input-group mb-3">
    <span class="input-group-text border-danger" for="friendly_name_' . $index . '">Friendly Name&nbsp;<i class="bi bi-question-circle-fill" onclick="show_message(\'Không cần nhập gì ở đây, hệ thống sẽ tự động điền thông tin\')"></i>&nbsp;:</span>
<input readonly type="text" class="form-control border-danger" name="intents[' . $index . '][friendly_name]" value="' . htmlspecialchars($intent['friendly_name']) . '" id="friendly_name_' . $index . '">
</div>


<div class="input-group mb-3">
    <span class="input-group-text border-success" for="entityid_' . $index . '">Entity ID&nbsp;<i class="bi bi-question-circle-fill" onclick="show_message(\'Cần nhập đúng Entity ID thiết bị trong Home Assistant của bạn\')"></i>&nbsp;:</span>
<input type="text" class="form-control border-success" name="intents[' . $index . '][entityid]" value="' . htmlspecialchars($intent['entityid']) . '" id="entityid_' . $index . '">
</div>


<div class="input-group mb-3">
    <span class="input-group-text border-success" for="action_' . $index . '">Hành Động:</span>
  <select class="form-select border-success" name="intents[' . $index . '][action]" id="action_' . $index . '">
      <option value="">-- Chọn Hành Động Cần Thực Hiện --</option>
      <option value="turn_on" ' . ($action_value === "turn_on" ? "selected" : "") . '>Bật (turn_on)</option>
      <option value="turn_off" ' . ($action_value === "turn_off" ? "selected" : "") . '>Tắt (turn_off)</option>
  </select>
</div>


<label for="questions_' . $index . '">Câu Lệnh Thực Thi <i class="bi bi-question-circle-fill" onclick="show_message(\'Nếu nhiều hơn 1 câu lệnh thì sẽ cần xuống dòng, Mỗi câu lệnh tương ứng với 1 dòng\')"></i> :</label><br>
<textarea class="form-control border-success" name="intents[' . $index . '][questions]" id="questions_' . $index . '" rows="5">
'.htmlspecialchars(implode("\n", $intent['questions'])).'
</textarea><br>


<center>
<button type="button" class="btn btn-danger rounded-pill" title="Xóa dữ liệu: ' . htmlspecialchars($intent['name']) . '" onclick="removeIntent(' . $index . ', \'' . htmlspecialchars($intent['name']) . '\')">
    <i class="bi bi-trash" type="button"></i> Xóa Dữ Liệu Này
</button>
</center>

</div>
</div>
</div>
</div>';
}
} 
else {
    echo '<br/><center><p><font color=red size=5>Câu lệnh tùy chỉnh điều khiển Home Assistant chưa được thiết lập</font></p><center><br/>';
}
echo '</div>';
?>

<?php
// Nút tạo mới, gọi hàm addNewIntent()
echo '<center><button type="submit" name="save_custom_home_assistant" class="btn btn-primary rounded-pill">Lưu thay đổi</button>
<button type="button" class="btn btn-success rounded-pill" onclick="addNewIntent()">Thêm lệnh tùy chỉnh</button></center>';
echo '</form>';
?>
</div>
</div>
</section>
</main>

    <!-- Modal hiển thị tệp Config.json -->
    <div class="modal fade" id="myModal_Home_Assistant" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
        <div class="modal-dialog" id="modal_dialog_show_Home_Assistant" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close btn btn-danger" data-dismiss="modal_Config" aria-label="Close" onclick="$('#myModal_Home_Assistant').modal('hide');">
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

<div id="suggestions" style="display: none;"></div>
<div class="suggestions" id="suggestions"></div>

<!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>

<script>

// Hiển thị modal xem nội dung file json Home_Assistant.json
['openModalBtn_Home_Assistant'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function() {
        read_loadFile('<?php echo $Hass_Custom_Json; ?>');
        $('#myModal_Home_Assistant').modal('show');
    });
});

//Xóa toàn bộ Dữ liệu đã đồng bộ từ Home Assistant
function del_get_hass_all() {
    if (!confirm("Bạn có chắc chắn muốn xóa dữ liệu Home Assistant đã đồng bộ không")) {
        return;
    }
	loading('show');
    // Khởi tạo một đối tượng XMLHttpRequest
    var xhr = new XMLHttpRequest();
    // Đường dẫn URL cho yêu cầu GET
    var url = "includes/php_ajax/Check_Connection.php?del_get_hass_all";
    // Xử lý phản hồi từ server khi request được hoàn thành
    xhr.onreadystatechange = function() {
        // Kiểm tra trạng thái request đã hoàn thành và trả về 200 OK
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                // Phản hồi thành công, xử lý kết quả
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
					loading('hide');
                    showMessagePHP(response.message, 3);
                } else {
					loading('hide');
                    show_message("Lỗi: " + response.message);
                }
            } else {
				loading('hide');
                show_message.log("Yêu cầu thất bại với mã trạng thái: " + xhr.status);
            }
        }
    };
    // Mở kết nối với phương thức GET và URL
    xhr.open("GET", url, true);
    // Gửi yêu cầu đến server
    xhr.send();
}


//lấy và làm mới dữ liệu Home Assistant lưu vào tệp Home_Assistant.json
function get_hass_all() {
	loading('show');
    var xhr = new XMLHttpRequest();
    var token_hasss = "<?php echo $Config['home_assistant']['long_token']; ?>";
    var url_hasss = [
        "<?php echo $Config['home_assistant']['internal_url']; ?>",
        "<?php echo $Config['home_assistant']['external_url']; ?>"
    ];
    // Biến để theo dõi chỉ số URL hiện tại
    var currentUrlIndex = 0;
    function sendRequest() {
        // Định nghĩa URL
        var url = 'includes/php_ajax/Check_Connection.php?get_hass_all&url_hass=' + encodeURIComponent(url_hasss[currentUrlIndex]) + '&token_hass=' + encodeURIComponent(token_hasss);
        xhr.open("GET", url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
					loading('hide');
					var response = JSON.parse(xhr.responseText);
                    //console.log(response)
                    if (response.success) {
						showMessagePHP("Đã đồng bộ và lưu dữ liệu từ Home Assistant Thành Công", 5)
						data_home_assistant = response.response
					}
					else{
						show_message('<center><font color=red><b>Lấy dữ liệu Thất Bại</b></font></center><br/>' + response.message)
					}
                } else {
					showMessagePHP("Lỗi khi gửi yêu cầu:" +xhr.status);
                    // Chuyển sang URL thứ hai nếu hiện tại không thành công
                    if (currentUrlIndex === 0) {
						// Chuyển tới URL thứ hai
                        currentUrlIndex++; 
						// Gửi yêu cầu tới URL thứ hai
                        sendRequest(); 
                    } else {
						loading('hide');
						show_message("Tất cả URL đều không thành công.");
                    }
                }
            }
        };
        xhr.send();
    }
    sendRequest();
}

</script>

<script>
// Dữ liệu từ PHP
let searchInputs = document.querySelectorAll('[id^="entityid_"]');
// Hàm để tìm kiếm và hiển thị gợi ý
function searchAndSuggest(inputElement, newIndex) {
    const query = inputElement.value.toLowerCase();
    const suggestionsBox = document.getElementById('suggestions');
	// Xóa gợi ý cũ
    suggestionsBox.innerHTML = '';
	// Ẩn gợi ý nếu không có
    suggestionsBox.style.display = 'none';
    // Đặt vị trí của khung gợi ý ngay bên dưới ô input
    const rect = inputElement.getBoundingClientRect();
    suggestionsBox.style.top = rect.bottom + window.scrollY + 'px';
    suggestionsBox.style.left = rect.left + window.scrollX + 'px';
	// Đặt chiều rộng giống ô input
    suggestionsBox.style.width = rect.width + 'px';
    if (query) {
        const filteredData = data_home_assistant.filter(item => {
            const entityIdMatch = item.entity_id && item.entity_id.toLowerCase().includes(query);
            const friendlyNameMatch = item.attributes && item.attributes.friendly_name && item.attributes.friendly_name.toLowerCase().includes(query);
            return entityIdMatch || friendlyNameMatch;
        });
        // Hiển thị gợi ý nếu có kết quả
        if (filteredData.length) {
            filteredData.forEach(item => {
                const div = document.createElement('div');
                div.textContent = item.entity_id + ' - ' + item.attributes.friendly_name;
                div.onclick = function() {
					// Cập nhật giá trị vào input
                    inputElement.value = item.entity_id;
					
                    // Cập nhật friendly_name vào thẻ input tương ứng
                    const friendlyNameInput = document.getElementById('friendly_name_' + newIndex);
                    if (friendlyNameInput) { 
                        friendlyNameInput.value = item.attributes.friendly_name; // Cập nhật giá trị friendly_name
                    }

					
					// Xóa gợi ý
                    suggestionsBox.innerHTML = '';
					// Ẩn gợi ý
                    suggestionsBox.style.display = 'none';
                };
                suggestionsBox.appendChild(div);
            });
			// Hiển thị gợi ý
            suggestionsBox.style.display = 'block'; 
        }
    }
}

// Hàm để gán sự kiện cho tất cả các ô input tìm kiếm
function initializeSearchInputs() {
    searchInputs = document.querySelectorAll('[id^="entityid_"]');
    searchInputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            // Gọi hàm tìm kiếm với ô input hiện tại và index
            searchAndSuggest(this, index);
        });
    });
}

// Gọi hàm để gán sự kiện
initializeSearchInputs();

// Ẩn gợi ý khi nhấn ra ngoài
document.addEventListener('click', function(event) {
    const suggestionsBox = document.getElementById('suggestions');
    const isClickInside = suggestionsBox.contains(event.target) || Array.from(searchInputs).some(input => input === event.target);
    if (!isClickInside) {
        suggestionsBox.style.display = 'none'; // Ẩn gợi ý nếu nhấn ra ngoài
    }
});
</script>
<?php
include 'html_js.php';
?>
<script src="assets/vendor/prism/prism.min.js"></script>
<script src="assets/vendor/prism/prism-json.min.js"></script>
</body>
</html>