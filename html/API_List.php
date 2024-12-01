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
<head>
<style>
    .list-container {
        max-height: 100vh;
        overflow-y: auto;
        border-right: 1px solid #ddd;
        padding-right: 5px;
    }
    
    .list-group-item {
        cursor: pointer;
    }
    
    .list-group-item.active {
        background-color: #0d6efd;
        color: #fff;
        font-weight: bold;
    }
    
    .list-group-item:hover {
        background-color: #e9ecef;
        box-shadow: 0px 2px 8px rgb(255 0 0);
    }
    
    .pre-container {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 15px;
        position: relative;
        transition: background-color 0.3s, box-shadow 0.3s;
    }
    
    .pre-container:hover {
        background-color: #e9ecef;
        box-shadow: 0px 2px 8px rgb(18 110 21);
    }
    
    .pre-container pre {
        margin: 0;
        padding: 0;
        font-family: Consolas, 'Courier New', monospace;
        color: #000000;
        background-color: transparent;
        white-space: pre-wrap;
    }
    
    .copy-icon {
        position: absolute;
        top: 5px;
        right: 5px;
        cursor: pointer;
        font-size: 16px;
        color: #6c757d;
        opacity: 0.7;
        transition: opacity 0.3s, color 0.3s;
    }
    
    .copy-icon:hover {
        opacity: 1;
        color: #495057;
    }
    
    .details-container h5 {
        margin-bottom: 1rem;
    }
</style>
</head>
<?php
include 'html_head.php';
?>

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
      <h1>Danh Sách API</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">Yêu cầu API</li>
&nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['api']['active'] ? '<p class="text-success" title="API đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="API không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
        </ol>
      </nav>
    </div><!-- End Page Title -->
	    <section class="section">
        <div class="row">

<?php
// Đường dẫn đến file JSON
$jsonFile = $VBot_Offline . 'resource/API_VBot_OFFLINE.postman_collection.json';
// Kiểm tra file tồn tại
if (!file_exists($jsonFile)) {
echo '<div class="col-lg-12"><div class="alert alert-danger alert-dismissible fade show"role="alert">Tệp dữ liệu API không tồn tại: '.$jsonFile.'</div></div>';
} else {
    // Đọc file JSON
    $jsonData = file_get_contents($jsonFile);
    // Giải mã JSON
    $data = json_decode($jsonData, true);
    // Kiểm tra dữ liệu JSON có hợp lệ hay không
    if (!$data || !isset($data['item'])) {
echo '<div class="col-lg-12"><div class="alert alert-danger alert-dismissible fade show"role="alert">Dữ liệu tệp API không hợp lệ hoặc lỗi cấu trúc</div></div>';
    } else {
        $info = $data['info'];
        $items = $data['item'];
		?>

        <!-- Hiển thị thông tin từ mục info -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0 text-primary">Thông tin API</h4>
            </div>
            <div class="card-body">
                <p><strong>Tên: </strong> <?php echo htmlspecialchars($info['name']); ?></p>
                <p><strong>Đường dẫn: </strong> <?php echo htmlspecialchars($jsonFile); ?> <font color="blue"><i title="Tải Xuống" onclick="downloadFile('<?php echo htmlspecialchars($jsonFile); ?>')" class="bi bi-download"></i></font></p>
                <p><strong>ID Postman: </strong> <?php echo htmlspecialchars($info['_postman_id']); ?></p>
                <p><strong>Schema: </strong> <a href="<?php echo htmlspecialchars($info['schema']); ?>" target="_blank"><?php echo htmlspecialchars($info['schema']); ?></a></p>
                <p><strong>ID Người Xuất: </strong> <?php echo htmlspecialchars($info['_exporter_id']); ?></p>
            </div>
        </div>
        <!-- Hiển thị danh sách các yêu cầu API -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar (Left Column) -->
        <div class="col-md-4 list-container">
		<p class="text-primary"><strong>Danh Sách API</strong></p>
            <div class="list-group" id="apiList">
                <?php foreach ($items as $index => $item): ?>
                    <a class="list-group-item list-group-item-action" onclick="showDetails(<?php echo $index; ?>)">
                        <?php echo htmlspecialchars($item['name'])." <span class='badge bg-primary'> ".htmlspecialchars($item['request']['method'])."</span>"; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Content (Right Column) -->
        <div class="col-md-8 details-container">
            <div id="detailsContainer">
                <h5 class="text-center text-muted">Chọn một danh sách API để xem thông tin</h5>
            </div>
        </div>
    </div>
</div>

		<?php
		
		    }
}
?>
		</div>
		</section>
	
</main>


  <!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Template Main JS File -->
<script>
    const items = <?php echo json_encode($items, JSON_HEX_TAG); ?>;
// Hàm hiển thị chi tiết API
function showDetails(index) {
    // Lấy tất cả các mục trong danh sách
    const listItems = document.querySelectorAll('.list-group-item');

    // Loại bỏ trạng thái "active" khỏi tất cả các mục
    listItems.forEach(item => item.classList.remove('active'));

    // Thêm trạng thái "active" vào mục được nhấn
    listItems[index].classList.add('active');

    // Lấy thông tin chi tiết API từ danh sách items
    const item = items[index];
    const method = item.request.method;
    //const url = item.request.url.raw;
    const urlzz = item.request.url.raw; 
	const url = urlzz.replace(/(^https?:\/\/)[^\/]+/, '$1<?php echo $Domain . ":" . $Config["api"]["port"]; ?>');
    const body = item.request.body?.raw || '';
    
    // Tạo mã cURL cho API
    let curl = "curl -X " + method + " '" + url + "'" + (body ? " -H 'Content-Type: application/json' --data '" + body.replace(/'/g, "\\'") + "'" : "");

    // Cập nhật nội dung hiển thị chi tiết API
    document.getElementById('detailsContainer').innerHTML =
        "<h5><strong><p class='text-primary'>Tên API: " + item.name + "</p></strong></h5>" +
        "<p><strong>Phương Thức:</strong> <span class='badge bg-primary' id='method" + index + "'>" + method + "</span></p>" +
        "<p><strong>URL:</strong></p>" +
        "<div class='pre-container'>" +
            "<pre class='bg-light p-2 rounded' id='url" + index + "'><a href='" + url + "' target='_bank'>" + url + "</a></pre>" +
            "<span class='copy-icon' title='Sao chép dữ liệu' onclick=\"copyToClipboard('url" + index + "')\">📋</span>" +
        "</div>" +
        "<p><strong>Dữ Liệu Gửi (Body):</strong></p>" +
        "<div class='pre-container'>" +
            "<pre class='bg-light p-2 rounded' id='body" + index + "'>" + body + "</pre>" +
            "<span class='copy-icon' title='Sao chép dữ liệu' onclick=\"copyToClipboard('body" + index + "')\">📋</span>" +
        "</div>" +
        "<br/><hr/><p><strong>Code gửi yêu cầu tới API:</strong></p>" +
        "<select class='form-select border-success' id='codeSelector" + index + "' onchange='updateCodeDisplay(" + index + ")'>" +
            "<option value='curl'>cURL Command</option>" +
            "<option value='python'>Python (Requests)</option>" +
            "<option value='php'>PHP (cURL)</option>" +
            "<option value='xmlhttprequest'>JavaScript (XMLHttpRequest)</option>" +
            "<option value='javascript'>JavaScript (Fetch)</option>" +
            "<option value='jquery'>JavaScript (jQuery)</option>" +
            "<option value='axios'>JavaScript (Axios)</option>" +
        "</select>" +
        "<div class='pre-container'>" +
            "<pre class='bg-light p-2 rounded' id='code" + index + "'>" + curl + "</pre>" +
            "<span class='copy-icon' title='Sao chép dữ liệu' onclick=\"copyToClipboard('code" + index + "')\">📋</span>" +
        "</div>";
}
  
function updateCodeDisplay(index) {
    const codeSelector = document.getElementById('codeSelector' + index); // Chỉnh lại ID để lấy đúng phần tử của mỗi mục
    const selectedCode = codeSelector.value;
    const item = items[index];
    const method = item.request.method;
    const urlzz = item.request.url.raw; 
	const url = urlzz.replace(/(^https?:\/\/)[^\/]+/, '$1<?php echo $Domain . ":" . $Config["api"]["port"]; ?>');
    //const url = item.request.url.raw;
    const body = item.request.body?.raw || '';
    let curl = "curl -X " + method + " '" + url + "'" + (body ? " -H 'Content-Type: application/json' --data '" + body.replace(/'/g, "\\'") + "'" : "");

    // Python code
	const pythonCode = 
		"import requests\n" +
		"import json\n\n" +
		"url = \"" + url + "\"\n" +
		"payload = json.dumps(" + (body ? JSON.stringify(JSON.parse(body), (key, value) => {
			if (value === true) return 'True';
			if (value === false) return 'False';
			if (value === null) return 'None';
			return value;
		}, 4) : "{}") + ")\n" +
		"headers = {\n" +
		"    \"Content-Type\": \"application/json\"\n" +
		"}\n\n" +
		"response = requests.request(\"" + method + "\", url, headers=headers, data=payload)\n" +
		"print(response.text)\n";

    // JavaScript Fetch code
    const jsCode = 
        "fetch(\"" + url + "\", {\n" +
        "    method: \"" + method + "\",\n" +
        "    headers: {\n" +
        "        \"Content-Type\": \"application/json\"\n" +
        "    },\n" +
        "    body: " + (body ? JSON.stringify(JSON.parse(body), null, 4) : "{}") + "\n" +
        "})\n" +
        ".then(response => response.json())\n" +
        ".then(data => console.log(data))\n" +
        ".catch(error => console.error('Error:', error));\n";

    // JavaScript XMLHttpRequest code
	const xhrCode = 
		"var data = " + 
		"JSON.stringify(" + (body ? JSON.stringify(JSON.parse(body), null, 4) : "{}") + ");\n" + 
		"var xhr = new XMLHttpRequest();\n" +
		"xhr.addEventListener(\"readystatechange\", function() {\n" +
		"  if (this.readyState === 4) {\n" +
		"    console.log(this.responseText);\n" +
		"  }\n" +
		"});\n\n" +
		"xhr.open(\"" + method + "\", \"" + url + "\");\n" +
		"xhr.setRequestHeader(\"Content-Type\", \"application/json\");\n\n" +
		"xhr.send(data);\n";

    // JavaScript jQuery code
	const jqueryCode = 
		"$.ajax({\n" +
		"    url: \"" + url + "\",\n" +
		"    method: \"" + method + "\",\n" +
		"    contentType: \"application/json\",\n" +
		"    data: " + "JSON.stringify(" + (body ? JSON.stringify(JSON.parse(body), null, 4) : "{}") + "),\n" + 
		"    success: function(response) {\n" +
		"        console.log(response);\n" +
		"    },\n" +
		"    error: function(error) {\n" +
		"        console.error('Error:', error);\n" +
		"    }\n" +
		"});\n";

    // JavaScript Axios code
    const axiosCode = 
        "axios." + method.toLowerCase() + "(\"" + url + "\", {\n" +
        "    headers: {\n" +
        "        \"Content-Type\": \"application/json\"\n" +
        "    },\n" +
        "    data: " + "JSON.stringify(" + (body ? JSON.stringify(JSON.parse(body), null, 4) : "{}") + "),\n" + 
        "})\n" +
        ".then(response => {\n" +
        "    console.log(response.data);\n" +
        "})\n" +
        ".catch(error => {\n" +
        "    console.error('Error:', error);\n" +
        "});\n";

    // PHP cURL code
	const phpCode = 
		"<?php\n" +
		"$url = \"" + url + "\";\n" +
		"$curl = curl_init();\n\n" +
		"curl_setopt_array($curl, array(\n" +
		"    CURLOPT_URL => $url,\n" +
		"    CURLOPT_RETURNTRANSFER => true,\n" +
		"    CURLOPT_ENCODING => '',\n" +
		"    CURLOPT_MAXREDIRS => 10,\n" +
		"    CURLOPT_TIMEOUT => 0,\n" +
		"    CURLOPT_FOLLOWLOCATION => true,\n" +
		"    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,\n" +
		"    CURLOPT_CUSTOMREQUEST => \"" + method + "\",\n" + 
		"    CURLOPT_POSTFIELDS => '" + (body ? JSON.stringify(JSON.parse(body), null, 4) : '{}') + "',\n" +
		"    CURLOPT_HTTPHEADER => array(\n" +
		"        'Content-Type: application/json'\n" +
		"    ),\n" +
		"));\n\n" +
		"$response = curl_exec($curl);\n\n" +
		"curl_close($curl);\n" +
		"echo $response;\n" +
		"?>\n";

    // Hiển thị mã dựa trên lựa chọn của người dùng
    const codeContainer = document.getElementById('code' + index);
    let codeContent = '';

    switch (selectedCode) {
        case 'curl':
            codeContent = curl;
            break;
        case 'python':
            codeContent = pythonCode.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            break;
        case 'javascript':
            codeContent = jsCode.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            break;
        case 'xmlhttprequest':
            codeContent = xhrCode.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            break;
        case 'jquery':
            codeContent = jqueryCode.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            break;
        case 'axios':
            codeContent = axiosCode.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            break;
        case 'php':
            codeContent = phpCode.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            break;
        default:
            codeContent = curl;
    }
	// Cập nhật hiển thị giá trị  và replace đối với python True, False, None
    codeContainer.innerHTML = codeContent.replace(/"True"/g, 'True').replace(/"False"/g, 'False').replace(/"None"/g, 'None');
}

    // Hàm sao chép dữ liệu
    function copyToClipboard(elementId) {
        const content = document.getElementById(elementId);
        if (!content) {
			show_message('Không tìm thấy nội dung để sao chép!');
            return;
        }
        const text = content.innerText || content.textContent; // Lấy nội dung văn bản
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text.trim())
                .then(() => showMessagePHP('Đã sao chép vào clipboard!', 3))
                .catch(err => show_message('Sao chép thất bại: ' + err));
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = text.trim();
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showMessagePHP('Đã sao chép vào clipboard!', 3);
            } catch (err) {
                show_message('Sao chép thất bại: ' + err);
            }
            document.body.removeChild(textarea);
        }
    }
</script>


<?php
include 'html_js.php';
?>

</body>
</html>