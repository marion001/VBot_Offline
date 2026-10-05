<?php
$isAnalysisPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if ($isAnalysisPost) {
    require_once __DIR__.'/includes/php_ajax/Api_Helpers.php';
    vbotApiInitialize(['POST']);
}
include __DIR__.'/Configuration.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) &&
    (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200))) {
    if ($isAnalysisPost) vbotApiJsonResponse(['success'=>false, 'message'=>'Bạn cần đăng nhập lại.'], 401);
    header('Location: Login.php'); exit;
}
if ($isAnalysisPost) {
    vbotApiVerifyCsrf();
    $refresh = ($_POST['action'] ?? '') === 'refresh_hass';
    $reset = ($_POST['action'] ?? '') === 'reset_dialogue';
    $text = $_POST['text'] ?? '';
    if (!$refresh && (!is_string($text) || (!$reset && trim($text) === '') || strlen($text) > 8192)) {
        vbotApiJsonResponse(['success'=>false, 'message'=>'Nhập câu lệnh từ 1 đến 2048 ký tự.'], 400);
    }
    $lookup = ($_POST['lookup_devices'] ?? '0') === '1';
    $execute = ($_POST['execute'] ?? '0') === '1';
    if ($execute && $lookup) vbotApiJsonResponse(['success'=>false, 'message'=>'Chỉ chọn một chế độ đọc thiết bị hoặc thực thi.'], 400);
    $analysisPayload = ['text'=>$text, 'lookup_devices'=>$lookup, 'execute'=>$execute];
    if (!$refresh) {
        $tabId = $_POST['tab_id'] ?? '';
        if (!is_string($tabId) || !preg_match('/^[a-f0-9]{32}$/D', $tabId)) {
            vbotApiJsonResponse(['success'=>false, 'message'=>'Phiên kiểm tra không hợp lệ; hãy tải lại trang.'], 400);
        }
        if (empty($_SESSION['command_test_seed'])) $_SESSION['command_test_seed'] = bin2hex(random_bytes(16));
        $analysisPayload['session_id'] = hash('sha256', $_SESSION['command_test_seed'].':'.$tabId);
        $analysisPayload['operation'] = $reset ? 'reset' : 'analyze';
    }
    // The endpoint is fixed to the local VBot; neither URL nor API key comes from the browser.
    $url = 'http://127.0.0.1:'.intval($Port_API).($refresh ? '/home-assistant/refresh' : '/commands/analyze');
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>$execute ? 120 : 45,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json', 'VBot-API-Key: '.$API_AUTH_KEY],
        CURLOPT_POSTFIELDS=>json_encode($refresh ? [] : $analysisPayload, JSON_UNESCAPED_UNICODE),
    ]);
    // Do not hold the PHP session lock while waiting for device lookup.
    session_write_close();
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status === 0) {
        error_log('Command analysis API: '.$error);
        vbotApiJsonResponse(['success'=>false, 'message'=>'Không kết nối được API VBot. Hãy kiểm tra VBot đang chạy và đã nạp mã mới.'], 503);
    }
    $result = json_decode($body, true);
    if (!is_array($result)) vbotApiJsonResponse(['success'=>false, 'message'=>'API VBot chưa hỗ trợ phân tích lệnh hoặc trả dữ liệu không hợp lệ.'], 502);
    vbotApiJsonResponse($result, $status);
}
?>
<!DOCTYPE html>
<html lang="vi">
<?php include 'html_head.php'; ?>
<body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main">
  <div class="pagetitle"><h1>Kiểm tra câu lệnh</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li><li class="breadcrumb-item active">Kiểm tra lệnh</li></ol></nav></div>
  <section class="section">
    <div class="card"><div class="card-body">
      <h5 class="card-title">Thử nhận diện lệnh</h5>
      <p>Nhập câu bạn muốn nói với VBot để xem keyword, hành động, đích điều khiển dự kiến và lý do bị từ chối. Kết quả phân tích không xác nhận thiết bị đã thực hiện lệnh.</p>
      <div id="mode-notice" class="alert alert-info">Chế độ thử không thực thi: không điều khiển thiết bị, khởi động lại hệ thống hoặc phát âm thanh.</div>
      <form id="command-test-form">
        <label for="command-text" class="form-label">Câu lệnh</label>
        <textarea id="command-text" name="text" class="form-control border-success" rows="3" maxlength="2048" required placeholder="Ví dụ: bật đèn phòng khách"></textarea>
        <div class="form-check my-3"><input class="form-check-input border-success" type="checkbox" id="lookup-devices"><label class="form-check-label" for="lookup-devices">Đọc danh sách thiết bị Home Assistant để tìm đích (không gửi lệnh điều khiển)</label></div>
        <div class="form-check my-3"><input class="form-check-input border-success" type="checkbox" id="execute-command"><label class="form-check-label" for="execute-command">Cho phép thực hiện luôn lệnh qua VBot</label></div>
        <button id="analyze-button" type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Phân tích lệnh</button>
      </form>
      <div id="analysis-error" class="alert alert-danger mt-3" role="alert" hidden></div>
    </div></div>
    <div class="card border border-success"><div class="card-body">
      <h5 class="card-title">Kiểm tra hội thoại nhiều lượt</h5>
      <p>Bật đọc thiết bị, nhập “bật đèn phòng khách”, sau đó nhập “đèn phòng khách 2” vào ô câu lệnh để thử lượt trả lời. Chế độ thử giữ hành động của câu trước và không gửi lệnh điều khiển.</p>
      <p>Ngữ cảnh chờ tối đa 30 giây, hủy sau hai câu trả lời không rõ. Hỗ trợ bật/tắt, nhiệt độ điều hòa, độ sáng đèn, tốc độ quạt và độ mở rèm. Thiết bị phải hỗ trợ chức năng tương ứng; phần trăm từ 0 đến 100. Với rèm: 0% đóng hoàn toàn, 100% mở hoàn toàn. Ví dụ: “mở rèm phòng khách 50%” rồi chọn “rèm phòng khách 2”; mức 50% được giữ từ câu đầu. “Dừng rèm” là lệnh riêng, hủy yêu cầu đặt vị trí đang chờ. Với đèn/quạt, 0% là tắt; quạt dạng switch chỉ hỗ trợ bật/tắt. Đổi chế độ hoặc bắt đầu phiên mới sẽ hủy ngữ cảnh cũ.</p>
      <div id="dialogue-summary" class="alert alert-info" aria-live="polite">Chưa có ngữ cảnh kiểm tra.</div>
      <div id="dialogue-details"></div>
      <button id="cancel-dialogue" type="button" class="btn btn-outline-danger">Hủy ngữ cảnh</button>
      <button id="new-dialogue" type="button" class="btn btn-outline-success">Bắt đầu phiên mới</button>
      <div id="dialogue-history" class="mt-3" aria-live="polite"></div>
    </div></div>
    <div id="analysis-results" aria-live="polite" hidden>
      <div class="card"><div class="card-body"><h5 class="card-title">Kết quả phân tích</h5><p id="analysis-summary"></p><div id="analysis-warnings"></div><div id="analysis-commands"></div>
        <details class="mt-3"><summary>Dữ liệu chi tiết</summary><pre id="analysis-json" class="bg-light p-3 mt-2" style="white-space:pre-wrap;overflow-wrap:anywhere"></pre></details>
        <button id="export-analysis" type="button" class="btn btn-outline-secondary mt-2">Tải kết quả JSON</button>
      </div></div>
    </div>
  </section>
</main>
<?php include 'html_footer.php'; include 'html_js.php'; ?>
<script>
(() => {
  const form = document.getElementById('command-test-form');
  const button = document.getElementById('analyze-button');
  const error = document.getElementById('analysis-error');
  const results = document.getElementById('analysis-results');
  const lookup = document.getElementById('lookup-devices');
  const execute = document.getElementById('execute-command');
  const cancelDialogue = document.getElementById('cancel-dialogue');
  const newDialogue = document.getElementById('new-dialogue');
  const createTabId = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), n => n.toString(16).padStart(2,'0')).join('');
  let tabId = createTabId();
  let dialogueDeadline = 0;
  let hasDialogue = false;
  let history = [];
  const dialogueStatuses = {awaiting_choice:'Đang chờ chọn thiết bị', unresolved:'Câu trả lời chưa rõ', selected:'Đã chọn đích — chưa thực thi', already_set:'Thiết bị đã ở trạng thái yêu cầu — không gửi lệnh', rejected:'Yêu cầu bị từ chối', completed:'Đã gửi lệnh thành công', failed:'Gửi lệnh thất bại', expired:'Ngữ cảnh hết hạn', cancelled:'Đã hủy ngữ cảnh', unavailable:'Không đọc được thiết bị', needs_lookup:'Cần bật đọc thiết bị', analyzed:'Đã phân tích'};
  setInterval(() => {
    const remaining = Math.max(0, Math.ceil((dialogueDeadline-performance.now())/1000));
    const timer = document.getElementById('dialogue-countdown');
    if (timer) timer.textContent = remaining ? `Còn ${remaining} giây để trả lời.` : 'Ngữ cảnh đã hết hạn; hãy nhập lại lệnh đầy đủ.';
  }, 1000);
  function renderDialogue(dialogue, input, resetHistory) {
    if (!dialogue) return;
    hasDialogue = dialogue.pending;
    if (resetHistory) history = [];
    const summary = document.getElementById('dialogue-summary');
    summary.replaceChildren();
    const status = document.createElement('div'); status.textContent = dialogueStatuses[dialogue.status] || dialogue.status; summary.append(status);
    dialogueDeadline = performance.now() + (dialogue.remaining_seconds || 0)*1000;
    if (dialogue.pending) {const timer = document.createElement('div'); timer.id='dialogue-countdown'; timer.textContent=`Còn ${Math.ceil(dialogue.remaining_seconds)} giây để trả lời.`; summary.append(timer);}
    const details = document.getElementById('dialogue-details'); details.replaceChildren();
    addField(details, 'Chế độ', dialogue.mode === 'execute' ? 'Thực thi thật' : 'Thử không thực thi');
    addField(details, 'Lệnh gốc', dialogue.original_text);
    if (dialogue.scheduled_at) addField(details, 'Thời gian hẹn đã lưu trong hội thoại', dialogue.scheduled_at);
    addField(details, 'Hành động đã lưu', dialogue.action);
    if (dialogue.value !== null && dialogue.value !== undefined) addField(details, 'Giá trị đã lưu từ câu đầu', dialogue.value);
    addField(details, 'Kết quả / lý do', dialogue.reason);
    addField(details, 'Số lần trả lời chưa rõ', dialogue.attempts);
    if (dialogue.target) addField(details, 'Thiết bị được chọn', dialogue.target);
    if (dialogue.target) {
      addField(details, 'Trạng thái trước điều khiển', dialogue.state ?? 'Không xác định');
      addField(details, 'Quyết định gửi lệnh', dialogue.would_execute ? dialogue.mode === 'execute' ? 'Đã xử lý yêu cầu gửi lệnh; xem kết quả thực thi và xác nhận trạng thái' : 'Cần gửi lệnh do trạng thái chưa khớp; chế độ thử không gửi lệnh' : dialogue.status === 'already_set' ? 'Không cần gửi: đã ở trạng thái yêu cầu' : 'Không gửi lệnh; xem lý do từ chối');
    }
    addTable(details, 'Thiết bị trong ngữ cảnh', dialogue.candidates);
    addTable(details, 'Thiết bị bị loại (blocked: bị chặn; removed: đã xóa)', dialogue.excluded_targets);
    addTable(details, 'Xác nhận trạng thái từ Home Assistant', dialogue.verification_results);
    if (dialogue.service) addField(details, 'Dịch vụ / dữ liệu', {service:dialogue.service, data:dialogue.service_data});
    if (!resetHistory) history.push({input, response:dialogue.reason, mode:dialogue.mode});
    history = history.slice(-20);
    const container = document.getElementById('dialogue-history'); container.replaceChildren();
    addTable(container, 'Lịch sử thử trên trang (tối đa 20 lượt)', history);
  }
  document.getElementById('export-analysis').addEventListener('click', () => {
    const blob = new Blob([document.getElementById('analysis-json').textContent], {type:'application/json;charset=utf-8'});
    const url = URL.createObjectURL(blob); const link = document.createElement('a');
    link.href = url; link.download = 'vbot-command-analysis.json'; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  });
  function updateMode(changed, other) {
    if (changed.checked) other.checked = false;
    const notice = document.getElementById('mode-notice');
    notice.className = execute.checked ? 'alert alert-warning' : 'alert alert-info';
    notice.textContent = execute.checked ? 'Chế độ thực thi: khi nhấn nút, VBot sẽ xử lý lệnh thật, bao gồm điều khiển thiết bị hoặc lệnh hệ thống nếu khớp.' : 'Chế độ thử: chỉ phân tích, không thực thi lệnh.';
    button.textContent = execute.checked ? 'Phân tích và thực thi' : 'Phân tích lệnh';
  }
  lookup.addEventListener('change', () => {updateMode(lookup, execute); if (hasDialogue) submitCommand('reset_dialogue');});
  execute.addEventListener('change', () => {updateMode(execute, lookup); if (hasDialogue) submitCommand('reset_dialogue');});
  const statuses = {matched:'Đã khớp — chưa thực thi', rejected:'Bị từ chối', needs_lookup:'Chưa đọc thiết bị', unmatched:'Chưa khớp lệnh cục bộ'};
  const stringify = value => typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value ?? '—');
  function addField(container, label, value) {
    const row = document.createElement('div'); row.className = 'row mb-2';
    const title = document.createElement('strong'); title.className = 'col-sm-3'; title.textContent = label;
    const content = document.createElement('div'); content.className = 'col-sm-9'; content.style.whiteSpace = 'pre-wrap'; content.style.overflowWrap = 'anywhere'; content.textContent = stringify(value);
    row.append(title, content); container.append(row);
  }
  function addTable(container, title, entries) {
    if (!entries?.length) return;
    const heading = document.createElement('h6'); heading.textContent = title; container.append(heading);
    const wrapper = document.createElement('div'); wrapper.className = 'table-responsive';
    const table = document.createElement('table'); table.className = 'table table-sm table-bordered';
    const keys = [...new Set(entries.flatMap(entry => Object.keys(entry)))];
    const header = document.createElement('tr');
    for (const key of keys) {const th = document.createElement('th'); th.textContent = key; header.append(th);}
    const head = document.createElement('thead'); head.append(header); table.append(head);
    const body = document.createElement('tbody');
    for (const entry of entries) {
      const row = document.createElement('tr');
      if (entry.selected) row.className = 'table-success';
      for (const key of keys) {const cell = document.createElement('td'); cell.textContent = stringify(entry[key]); row.append(cell);}
      body.append(row);
    }
    table.append(body); wrapper.append(table); container.append(wrapper);
  }
  form.addEventListener('submit', event => {event.preventDefault(); submitCommand();});
  cancelDialogue.addEventListener('click', () => submitCommand('reset_dialogue'));
  newDialogue.addEventListener('click', () => submitCommand('reset_dialogue', true));
  async function submitCommand(operation = 'analyze', startNew = false) {
    error.hidden = true; results.hidden = true; button.disabled = true; button.textContent = 'Đang phân tích…';
    cancelDialogue.disabled = true; newDialogue.disabled = true;
    const executionRequested = execute.checked;
    lookup.disabled = true; execute.disabled = true;
    const controller = new AbortController(); const timer = setTimeout(() => controller.abort(), executionRequested ? 125000 : 50000);
    try {
      const data = new FormData(); data.set('text', document.getElementById('command-text').value);
      data.set('action', operation); data.set('tab_id', tabId);
      data.set('lookup_devices', document.getElementById('lookup-devices').checked ? '1' : '0');
      data.set('execute', executionRequested ? '1' : '0');
      data.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
      const response = await fetch('Command_Test.php', {method:'POST', body:data, credentials:'same-origin', signal:controller.signal});
      const payload = await response.json();
      if (!response.ok || !payload.success || !payload.analysis) throw new Error(payload.message || 'Không phân tích được lệnh.');
      const report = payload.analysis;
      renderDialogue(report.dialogue, operation === 'reset_dialogue' ? 'Hủy ngữ cảnh' : data.get('text'), startNew);
      if (startNew) {tabId = createTabId(); document.getElementById('command-text').value = '';}
      document.getElementById('analysis-summary').textContent = `Ngôn ngữ: ${report.locale} · ${report.commands.length} lệnh · ${report.executed ? 'Đã gửi vào luồng xử lý thật' : 'Chưa thực thi'} · Phân tích: ${report.elapsed_ms ?? '—'} ms`;
      const warnings = document.getElementById('analysis-warnings'); warnings.replaceChildren();
      for (const warning of report.warnings || []) {const p = document.createElement('p'); p.className='text-warning'; p.textContent=warning; warnings.append(p);}
      const container = document.getElementById('analysis-commands'); container.replaceChildren();
      if (report.execution) {
        addField(container, 'Kết quả xử lý thật', report.execution.text);
        addField(container, 'Được xử lý (handled)', report.execution.handled);
        addField(container, 'Ghi chú', report.execution.note);
        addTable(container, 'Kiểm tra trạng thái trước/sau điều khiển', report.execution.verification_results);
      }
      addField(container, 'Cấu hình đang dùng', report.configuration);
      for (const command of report.commands) {
        const block = document.createElement('div'); block.className='border rounded p-3 mb-3';
        addField(block, 'Câu đã chuẩn hóa', command.text);
        addField(block, 'Kết luận phân tích trước thực thi', statuses[command.status] || command.status);
        for (const [label, key] of [['Keyword','keyword'],['Hành động','action'],['Nhánh xử lý','route'],['Thiết bị / đích','target'],['Lý do','reason']]) addField(block,label,command[key]);
        if (command.target && !Array.isArray(command.target)) {
          addField(block, 'Nguồn tên khớp', command.target.matched_via);
          addField(block, 'Tên / alias khớp', command.target.matched_name);
        }
        if (command.score !== undefined) addField(block,'Điểm khớp / ngưỡng', `${command.score} / ${command.threshold ?? '—'}`);
        if (command.service) addField(block,'Dịch vụ dự kiến',command.service);
        if (command.service_data) addField(block,'Dữ liệu dịch vụ',command.service_data);
        if (command.scheduled_at) addField(block,'Thời gian hẹn điều khiển',command.scheduled_at);
        if (command.query !== undefined) addField(block,'Tên dùng để tìm đích',command.query);
        if (command.entity_types) addField(block,'Loại thiết bị được tìm',command.entity_types);
        if (command.state !== undefined) addField(block,'Trạng thái hiện tại',command.state);
        if (command.would_execute !== undefined) addField(block,'Cần gửi lệnh khi thực thi',command.would_execute);
        if (command.area) addField(block, 'Area được chọn', command.area);
        if (command.multiple !== undefined) addField(block, 'Yêu cầu nhiều thiết bị', command.multiple);
        addTable(block, 'Dịch vụ dự kiến cho từng đích trong Area', command.services);
        if (command.supported_features !== undefined) addField(block,'Tính năng thiết bị (bitmask)',command.supported_features);
        addTable(block, 'Tất cả keyword nhận diện', command.keyword_matches);
        addTable(block, 'Xếp hạng đích (tối đa 10)', command.candidates);
        addTable(block, 'Các bước phân tích', command.trace);
        container.append(block);
      }
      document.getElementById('analysis-json').textContent = JSON.stringify(report,null,2); results.hidden=false;
    } catch (exception) {error.textContent = exception.message + (executionRequested ? ' Chưa xác định được kết quả thực thi. Hãy kiểm tra thiết bị hoặc log trước khi gửi lại; lệnh có thể đã được xử lý.' : ''); error.hidden=false;}
    finally {clearTimeout(timer); button.disabled=false; cancelDialogue.disabled=false; newDialogue.disabled=false; lookup.disabled=false; execute.disabled=false; updateMode(execute, lookup);}
  }
})();
</script>
</body></html>
