<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
?>
<?php
if ($Config['contact_info']['user_login']['active']){
session_start();
if (!isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))) {
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
}
}
?>

<!DOCTYPE html>
<html lang="vi">
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
    
    #api_test_json_sample {
        white-space: pre-wrap;
        font-family: monospace;
        background-color: #141212;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        color: #00ff37;
    }
    
    #api_test_json_sample .keyword {
        color: #d6336c;
        font-weight: bold;
    }
    
    #api_test_json_sample .string {
        color: #21ffe0;
    }
    
    #api_test_json_sample .number {
        color: #fd7e14;
    }
    
    #api_test_json_sample .brace {
        color: #007bff;
    }
    
    #api_test_json_sample .comment {
        color: #6c757d;
    }
    
    #reponse_tets_code_api {
        white-space: pre-wrap;
        font-family: monospace;
        background-color: #000;
        color: #1e90ff;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-top: 10px;
    }
    
    #modal_dialog_show_TEST_API {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 20px auto;
        max-width: calc(100vw - 40px);
    }
    
    #modal_dialog_show_TEST_API .modal-content {
        max-height: calc(100vh - 40px);
        overflow-y: auto;
    }
    
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

    .api-list-toolbar {
        position: sticky;
        top: 64px;
        z-index: 900;
        padding: .75rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(13, 110, 253, .22);
        border-radius: .75rem;
        background: rgba(244, 248, 255, .96);
        box-shadow: 0 .25rem .75rem rgba(18, 38, 63, .08);
        backdrop-filter: blur(6px);
    }

    .api-list-search-hidden {
        display: none !important;
    }

    #api-list-empty {
        display: none;
    }

    @media (max-width: 767.98px) {
        .api-list-toolbar {
            top: 60px;
        }

        .list-container {
            max-height: 45vh;
            margin-bottom: 1rem;
            border-right: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: .75rem;
        }
    }
</style>
</head>
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
      <h1>Danh Sách API</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">Yêu cầu API</li>
&nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['api']['active'] ? '<p class="text-success" title="API đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="API không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
        </ol>
      </nav>
    </div>
	    <section class="section">
        <div class="api-list-toolbar" id="api-list-toolbar" aria-label="Tìm kiếm và lọc API">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-lg">
                    <div class="input-group">
                        <span class="input-group-text border-primary"><i class="bi bi-search text-primary"></i></span>
                        <input type="search" id="api-list-search" class="form-control border-primary"
                            placeholder="Tìm theo tên API, method hoặc URL..." autocomplete="off">
                        <button type="button" id="api-list-search-clear" class="btn btn-outline-secondary" title="Xóa nội dung tìm kiếm">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="col-6 col-lg-auto">
                    <select id="api-method-filter" class="form-select border-primary" aria-label="Lọc theo phương thức API">
                        <option value="">Tất cả Method</option>
                    </select>
                </div>
                <div class="col-6 col-lg-auto">
                    <select id="api-quick-navigation" class="form-select border-primary" aria-label="Mở nhanh API">
                        <option value="">Mở nhanh API...</option>
                    </select>
                </div>
            </div>
            <div id="api-list-empty" class="alert alert-info py-2 mt-2 mb-0" role="status">
                <i class="bi bi-info-circle"></i> Không tìm thấy API phù hợp.
            </div>
        </div>
        <div class="row">

<?php
$jsonFile = $VBot_Offline . 'resource/API_VBot_OFFLINE.postman_collection.json';
if (!file_exists($jsonFile)) {
echo '<div class="col-lg-12"><div class="alert alert-danger alert-dismissible fade show"role="alert">Tệp dữ liệu API không tồn tại: '.$jsonFile.'</div></div>';
} else {
    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    if (!$data || !isset($data['item'])) {
echo '<div class="col-lg-12"><div class="alert alert-danger alert-dismissible fade show"role="alert">Dữ liệu tệp API không hợp lệ hoặc lỗi cấu trúc</div></div>';
    } else {
        $info = $data['info'];
        $items = $data['item'];
		?>
        <!-- Hiển thị thông tin từ mục info -->
        <div class="card mb-4" id="api-info-section">
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
<div class="container-fluid mt-4" id="api-browser-section">
    <div class="row">
        <!-- Sidebar (Left Column) -->
        <div class="col-md-4 list-container">
		<p class="text-primary"><strong>Danh Sách API:</strong></p>
            <div class="list-group" id="apiList">
                <?php foreach ($items as $index => $item): ?>
                    <a class="list-group-item list-group-item-action" role="button" tabindex="0"
                       data-api-index="<?php echo $index; ?>"
                       data-api-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                       data-api-method="<?php echo htmlspecialchars(strtoupper($item['request']['method']), ENT_QUOTES, 'UTF-8'); ?>"
                       onclick="showDetails(<?php echo $index; ?>)">
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

    <!-- Modal hiển thị tệp Config.json -->
    <div class="modal fade" id="myModal_TETS_API" tabindex="-1" role="dialog" aria-labelledby="modalLabel_Config" aria-hidden="true">
        <div class="modal-dialog" id="modal_dialog_show_TEST_API" role="document">
            <div class="modal-content">
                <div class="modal-header">
				<b><font color=blue><div id="name_file_showzz"></div></font></b> 
                    <button type="button" class="close btn btn-danger" data-dismiss="modal_Config" aria-label="Close" onclick="$('#myModal_TETS_API').modal('hide');">
                        <i class="bi bi-x-circle-fill"></i> Đóng
                    </button>
                </div>
                <div class="modal-body">
                    <p id="message_TETS_API"></p>

<div class="row">
        <div class="col-lg-6">
            <div class="card-body">
<div id="api_test_json_sample" contenteditable="true" class="form-control"></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-body">
      <center><button id="run_api_code" class="btn btn-success rounded-pill" title="Chạy Kiểm Tra Đường API">Chạy Test API (Tester)</button></center>
	  <hr/>
	  <h5><strong><p class="text-primary">Dữ Liệu Phản Hồi API:</p></strong></h5>
	 <div id="reponse_tets_code_api">
	 - Chưa có dữ liệu phản hồi
	 </div>
</div>
        </div>
      </div>
                </div>
            </div>
        </div>
    </div>

  <!-- ======= Footer ======= -->
<?php
include 'html_footer.php';
?>
<!-- End Footer -->
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script>

// Hàm để thêm màu sắc cho chuỗi JSON
function apiListEscapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function highlightCode(code) {
    code = apiListEscapeHtml(code);
    code = code.replace(/(&quot;.*?&quot;)/g, "<span class='string'>$1</span>");
    code = code.replace(/\b(var|let|const|function|return)\b/g, "<span class='keyword'>$1</span>");
    code = code.replace(/\b(\d+)\b/g, "<span class='number'>$1</span>");
    code = code.replace(/{|}/g, "<span class='brace'>$&</span>");
    code = code.replace(/\/\/(.*)/g, "<span class='comment'>//$1</span>");
    return code;
}

let currentApiTestRequest = null;

// Chạy request đã chọn mà không thực thi mã JavaScript tùy ý.
document.getElementById("run_api_code").onclick = function() {
    if (!currentApiTestRequest) {
        document.getElementById('reponse_tets_code_api').textContent = 'Chưa chọn API để kiểm tra.';
        return;
    }
    loading('show');
    vbotFetchWithTimeout(currentApiTestRequest.url, {
        method: currentApiTestRequest.method,
        headers: {'Content-Type': 'application/json'},
        body: ['GET', 'HEAD'].includes(currentApiTestRequest.method) ? undefined : JSON.stringify(currentApiTestRequest.body)
    })
    .then(async response => {
        const responseText = await response.text();
        let responseData;
        try { responseData = JSON.parse(responseText); } catch (_) { responseData = responseText; }
        document.getElementById('reponse_tets_code_api').textContent =
            'HTTP ' + response.status + '\n' + (typeof responseData === 'string' ? responseData : JSON.stringify(responseData, null, 4));
    })
    .catch(error => {
        document.getElementById('reponse_tets_code_api').textContent = 'Lỗi gửi yêu cầu API: ' + error.message;
    })
    .finally(() => loading('hide'));
};

// onclick xem nội dung file json
function test_Code_API(json_body, method, url, name) {
    if (!method || !url || !name) {
		show_message("Thiếu dữ liệu đầu vào để  kiểm tra API");
        return;
    }
    let formattedJson;
    try {
        if (json_body === '' || json_body == null) {
            formattedJson = "{}";
        } else if (typeof json_body === 'string') {
            formattedJson = JSON.stringify(JSON.parse(json_body), null, 4);
        } else {
            formattedJson = JSON.stringify(json_body, null, 4);
        }
    } catch (e) {
		show_message("Phản hồi JSON không hợp lệ: " +e);
        formattedJson = "{}";
    }
    currentApiTestRequest = {
        body: typeof json_body === 'string' ? JSON.parse(formattedJson) : json_body,
        method: String(method).toUpperCase(),
        url: String(url)
    };
const apiCode = 
  "var API_VBot = '" + url + "';\n\n" +
  "var data = " +
  "JSON.stringify(" + (formattedJson ? formattedJson : "{}") + ");\n" +
  "\n" +
  "loading('show');\n" +
  "var xhr = new XMLHttpRequest();\n" +
  "xhr.open('" + method + "', API_VBot);\n" +
  "xhr.setRequestHeader('Content-Type', 'application/json');\n" +
  "xhr.addEventListener('readystatechange', function() {\n" +
  "  if (this.readyState === 4) {\n" +
  "    try {\n" +
  "      var responseData = JSON.parse(this.responseText);\n" +
  "      document.getElementById('reponse_tets_code_api').textContent = JSON.stringify(responseData, null, 4);\n" +
  "    } catch (e) {\n" +
  "      document.getElementById('reponse_tets_code_api').textContent = 'Phản hồi JSON không hợp lệ: ' + e.message;\n" +
  "    }\n" +
  "  }\n" +
  "loading('hide');\n" +
  "});\n\n" +
  "xhr.send(data);\n"+
  "\n";

    const content = "<center contenteditable='false'><strong>Chỉnh Sửa Nội Dung Bên Dưới Để Test API:</strong></center><hr/><pre id='code_send_api_test'>" + highlightCode(apiCode) + "</pre>";
    document.getElementById('api_test_json_sample').innerHTML = content;
    document.getElementById('name_file_showzz').textContent = "Tên API: "+name;
    $('#myModal_TETS_API').modal('show');
}
</script>

<script>
    const items = <?php echo json_encode(isset($items) && is_array($items) ? $items : [], JSON_HEX_TAG); ?>;

function normalizeApiListSearchText(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('vi')
        .trim();
}

function initializeApiListNavigation() {
    const searchInput = document.getElementById('api-list-search');
    const clearButton = document.getElementById('api-list-search-clear');
    const methodFilter = document.getElementById('api-method-filter');
    const quickNavigation = document.getElementById('api-quick-navigation');
    const emptyState = document.getElementById('api-list-empty');
    const apiList = document.getElementById('apiList');
    if (!searchInput || !clearButton || !methodFilter || !quickNavigation || !emptyState || !apiList) {
        return;
    }

    const links = Array.from(apiList.querySelectorAll('.list-group-item[data-api-index]'));
    const methods = new Set();
    links.forEach(function(link) {
        const index = Number(link.dataset.apiIndex);
        const item = items[index] || {};
        const method = String(link.dataset.apiMethod || '').toUpperCase();
        const rawUrl = item.request && item.request.url ? (item.request.url.raw || '') : '';
        link.dataset.apiSearch = normalizeApiListSearchText(link.dataset.apiName + ' ' + method + ' ' + rawUrl);
        if (method) {
            methods.add(method);
        }
        quickNavigation.appendChild(new Option(method + ' — ' + link.dataset.apiName, String(index)));
        link.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                showDetails(index);
            }
        });
    });

    Array.from(methods).sort().forEach(function(method) {
        methodFilter.appendChild(new Option(method, method));
    });

    const applyFilter = function() {
        const query = normalizeApiListSearchText(searchInput.value);
        const selectedMethod = methodFilter.value;
        let visibleCount = 0;
        links.forEach(function(link) {
            const matchesSearch = query === '' || link.dataset.apiSearch.includes(query);
            const matchesMethod = selectedMethod === '' || link.dataset.apiMethod === selectedMethod;
            const visible = matchesSearch && matchesMethod;
            link.classList.toggle('api-list-search-hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    };

    searchInput.addEventListener('input', applyFilter);
    methodFilter.addEventListener('change', applyFilter);
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        methodFilter.value = '';
        applyFilter();
        searchInput.focus();
    });
    quickNavigation.addEventListener('change', function() {
        if (quickNavigation.value !== '') {
            searchInput.value = '';
            methodFilter.value = '';
            applyFilter();
            showDetails(Number(quickNavigation.value));
            document.getElementById('detailsContainer')?.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
        quickNavigation.value = '';
    });
    applyFilter();
}

// Hàm hiển thị chi tiết API
function showDetails(index) {
    const listItems = document.querySelectorAll('#apiList .list-group-item[data-api-index]');

    listItems.forEach(item => item.classList.remove('active'));

    if (!items[index] || !listItems[index]) {
        return;
    }
    listItems[index].classList.add('active');

    const item = items[index];
    const method = item.request.method;
    const urlzz = item.request.url.raw; 
	const url = urlzz.replace(/(^https?:\/\/)[^\/]+/, '$1<?php echo $Domain . ":" . $Config["api"]["port"]; ?>');
    const body = item.request.body?.raw || '';
    currentApiTestRequest = null;
    document.getElementById('reponse_tets_code_api').textContent = '- Chưa có dữ liệu phản hồi';
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
       "<p><strong>Dữ Liệu Gửi (Body):</strong> <button type='button' id='testApiButton' class='btn btn-danger rounded-pill btn-sm'>Test API</button></p>" +
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

    document.getElementById('testApiButton').addEventListener('click', function() {
        test_Code_API(body, method, url, item.name);
    });
}
  
function updateCodeDisplay(index) {
    const codeSelector = document.getElementById('codeSelector' + index);
    const selectedCode = codeSelector.value;
    const item = items[index];
    const method = item.request.method;
    const urlzz = item.request.url.raw; 
	const url = urlzz.replace(/(^https?:\/\/)[^\/]+/, '$1<?php echo $Domain . ":" . $Config["api"]["port"]; ?>');
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
		"    CURLOPT_CONNECTTIMEOUT => 5,\n" +
		"    CURLOPT_TIMEOUT => 30,\n" +
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
    codeContainer.innerHTML = codeContent.replace(/"True"/g, 'True').replace(/"False"/g, 'False').replace(/"None"/g, 'None');
}

    // Hàm sao chép dữ liệu
    function copyToClipboard(elementId) {
        const content = document.getElementById(elementId);
        if (!content) {
			show_message('Không tìm thấy nội dung để sao chép!');
            return;
        }
        const text = content.innerText || content.textContent;
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

    document.addEventListener('DOMContentLoaded', initializeApiListNavigation);
</script>


<?php
include 'html_js.php';
?>

</body>
</html>
