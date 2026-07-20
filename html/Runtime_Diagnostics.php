<?php
include 'Configuration.php';
if ($Config['contact_info']['user_login']['active']) {
  session_start();
  if (!isset($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200)) {
    session_unset(); session_destroy(); header('Location: Login.php'); exit;
  }
  $_SESSION['user_login']['login_time'] = time();
}
$diagnosticsUrl = rtrim($URL_API_VBOT, '/') . '/runtime/diagnostics';
?>
<!DOCTYPE html><html lang="vi">
<?php include 'html_head.php'; ?>
<body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main">
  <div class="pagetitle"><h1>Chẩn đoán Runtime</h1><nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
    <li class="breadcrumb-item active">Audio, LED, luồng và kết nối</li>
  </ol></nav></div>
  <section class="section dashboard">
    <div class="card"><div class="card-body pt-3"><div class="d-flex flex-wrap align-items-center gap-2">
      <button id="diag-refresh" class="btn btn-primary" type="button"><i class="bi bi-arrow-clockwise"></i> Làm mới</button>
      <div class="form-check form-switch ms-2"><input id="diag-auto" class="form-check-input" type="checkbox" checked>
        <label class="form-check-label" for="diag-auto">Tự cập nhật mỗi 5 giây</label></div>
      <span id="diag-loading" class="spinner-border spinner-border-sm text-primary d-none"></span>
      <span id="diag-updated" class="text-muted ms-auto">Chưa có dữ liệu</span>
    </div><div id="diag-error" class="alert alert-danger mt-3 d-none"></div></div></div>

    <div class="row">
      <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body pt-3"><h5>Audio</h5><div id="diag-audio">--</div></div></div></div>
      <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body pt-3"><h5>LED</h5><div id="diag-led">--</div></div></div></div>
      <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body pt-3"><h5>Kết nối</h5><div id="diag-connections">--</div></div></div></div>
      <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body pt-3"><h5>Watchdog</h5><div id="diag-watchdog">--</div></div></div></div>
    </div>

    <div class="card"><div class="card-body pt-3"><h5 class="card-title">Watchdog</h5><div class="table-responsive">
      <table class="table table-sm table-bordered align-middle"><thead><tr><th>Dịch vụ</th><th>Trạng thái</th><th>Kiểm tra</th><th>Phục hồi</th><th>Lỗi gần nhất</th></tr></thead>
      <tbody id="diag-watchdog-table"><tr><td colspan="5">Chưa có dữ liệu</td></tr></tbody></table>
    </div></div></div>

    <div class="row"><div class="col-lg-7"><div class="card"><div class="card-body pt-3"><h5 class="card-title">Các luồng quan trọng</h5><div class="table-responsive">
      <table class="table table-sm table-bordered align-middle"><thead><tr><th>Tên luồng</th><th>Alive</th><th>Daemon</th></tr></thead>
      <tbody id="diag-thread-table"><tr><td colspan="3">Chưa có dữ liệu</td></tr></tbody></table>
    </div></div></div></div><div class="col-lg-5"><div class="card"><div class="card-body pt-3"><h5 class="card-title">Hàng đợi</h5><div class="table-responsive">
      <table class="table table-sm table-bordered align-middle"><thead><tr><th>Hàng đợi</th><th>Đang chờ</th><th>Sức chứa</th></tr></thead>
      <tbody id="diag-queue-table"><tr><td colspan="3">Chưa có dữ liệu</td></tr></tbody></table>
    </div></div></div></div></div>

    <div class="card"><div class="card-body pt-3"><h5 class="card-title">Tất cả dữ liệu Runtime</h5>
      <div id="diag-all-sections" class="row g-3"><div class="col-12">Chưa có dữ liệu</div></div>
    </div></div>

    <div class="card"><div class="card-body pt-3"><h5 class="card-title">JSON đầy đủ</h5>
      <pre id="diag-json" class="border rounded p-3 mb-0" style="max-height:460px;overflow:auto;white-space:pre-wrap">--</pre>
    </div></div>
  </section>
</main>
<?php include 'html_footer.php'; ?>
<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<?php include 'html_js.php'; ?>
<script>
(() => {
  'use strict';
  const endpoint = <?php echo json_encode($diagnosticsUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
  const el = id => document.getElementById(id);
  const show = value => value === null || value === undefined || value === '' ? '--' : String(value);
  const yesNo = value => value ? 'Có' : 'Không';
  let busy = false, timer = null;

  function badge(ok, label) {
    const node = document.createElement('span');
    node.className = `badge ${ok ? 'bg-success' : 'bg-danger'}`;
    node.textContent = label;
    return node;
  }
  function lines(id, values) {
    const root = el(id); root.replaceChildren();
    values.forEach(([name, value]) => {
      const row = document.createElement('div'), strong = document.createElement('strong');
      strong.textContent = `${name}: `; row.append(strong, document.createTextNode(show(value))); root.appendChild(row);
    });
  }
  function table(id, rows, count) {
    const body = el(id); body.replaceChildren();
    if (!rows.length) { const cell = body.insertRow().insertCell(); cell.colSpan = count; cell.textContent = 'Không có dữ liệu'; return; }
    rows.forEach(values => { const row = body.insertRow(); values.forEach(value => {
      const cell = row.insertCell(); value instanceof Node ? cell.appendChild(value) : cell.textContent = show(value);
    }); });
  }
  function flattenRuntime(value, prefix = '', rows = []) {
    if (value === null || value === undefined || typeof value !== 'object') {
      rows.push([prefix || 'value', value]); return rows;
    }
    if (Array.isArray(value)) {
      if (!value.length) rows.push([prefix || 'value', '[]']);
      value.forEach((item, index) => flattenRuntime(item, `${prefix}[${index}]`, rows));
      return rows;
    }
    const entries = Object.entries(value);
    if (!entries.length) rows.push([prefix || 'value', '{}']);
    entries.forEach(([key, item]) => flattenRuntime(item, prefix ? `${prefix}.${key}` : key, rows));
    return rows;
  }
  function renderAllSections(data) {
    const root = el('diag-all-sections'); root.replaceChildren();
    Object.entries(data).forEach(([section, value]) => {
      const column = document.createElement('div'); column.className = 'col-xl-4 col-lg-6 col-12';
      const card = document.createElement('div'); card.className = 'border rounded h-100 p-3';
      const title = document.createElement('h6'); title.className = 'text-primary'; title.textContent = section;
      const tableWrap = document.createElement('div'); tableWrap.className = 'table-responsive';
      const runtimeTable = document.createElement('table'); runtimeTable.className = 'table table-sm table-striped mb-0';
      const body = document.createElement('tbody');
      flattenRuntime(value).forEach(([path, item]) => {
        const row = body.insertRow(), keyCell = row.insertCell(), valueCell = row.insertCell();
        keyCell.className = 'fw-semibold text-break'; keyCell.textContent = path;
        valueCell.className = 'text-break'; valueCell.textContent = show(item);
      });
      runtimeTable.appendChild(body); tableWrap.appendChild(runtimeTable); card.append(title, tableWrap); column.appendChild(card); root.appendChild(column);
    });
  }
  function render(data) {
    const audio = data.audio || {}, output = audio.output || {}, local = audio.local || {}, airplay = audio.airplay || {}, bluetooth = audio.bluetooth || {};
    const led = data.led || {}, connections = data.connections || {}, mqtt = connections.mqtt || {}, ws = connections.websocket || {};
    const watchdog = data.watchdog || {};
    lines('diag-audio', [['Nguồn', output.source || audio.source], ['Trạng thái', output.playback_state], ['Mic owner', audio.mic_capture_owner], ['Âm lượng', output.volume === undefined ? '--' : `${output.volume}%`], ['Mute', yesNo(output.muted)], ['Có tiếng', yesNo(output.audible)], ['Local', local.paused ? 'Tạm dừng' : yesNo(local.playing)], ['AirPlay', yesNo(airplay.playing)], ['Bluetooth', yesNo(bluetooth.playing)]]);
    lines('diag-led', [['Hiệu ứng', led.effect], ['Độ sáng', led.brightness], ['Mic bật', yesNo(led.mic_enabled)], ['LED bật', yesNo(led.enabled)]]);
    lines('diag-connections', [['MQTT', mqtt.connected ? 'Đã kết nối' : 'Mất kết nối'], ['WebSocket', ws.server_running ? 'Đang chạy' : 'Đã dừng'], ['WS clients', ws.connected_clients], ['SSE clients', connections.sse_clients]]);
    lines('diag-watchdog', [['Đang chạy', yesNo(watchdog.running)], ['Chu kỳ', `${show(watchdog.interval_seconds)} giây`], ['Cooldown', `${show(watchdog.restart_cooldown_seconds)} giây`]]);
    table('diag-watchdog-table', Object.entries(watchdog.targets || {}).map(([name, item]) => [name, badge(Boolean(item.healthy), item.status || 'unknown'), item.checks, item.restart_count, item.last_error]), 5);
    table('diag-thread-table', ((data.threads || {}).important || []).map(item => [item.name, badge(Boolean(item.alive), yesNo(item.alive)), yesNo(item.daemon)]), 3);
    table('diag-queue-table', Object.entries(data.queues || {}).map(([name, item]) => [name, item && item.size, item && item.capacity]), 3);
    renderAllSections(data);
    el('diag-json').textContent = JSON.stringify(data, null, 2);
    const date = new Date((Number(data.timestamp) || Date.now() / 1000) * 1000);
    el('diag-updated').textContent = `Cập nhật: ${date.toLocaleString('vi-VN')}`;
  }
  async function refresh() {
    if (busy || document.hidden) return; busy = true;
    el('diag-loading').classList.remove('d-none'); el('diag-error').classList.add('d-none');
    try {
      const headers = {'Accept': 'application/json'};
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 5000);
      let response;
      try {
        response = await fetch(endpoint, {headers, cache: 'no-store', signal: controller.signal});
      } finally {
        clearTimeout(timeout);
      }
      const contentType = (response.headers.get('content-type') || '').toLowerCase();
      const responseText = await response.text();
      if (!response.ok) {
        throw new Error(`VBot API không hoạt động hoặc không truy cập được (HTTP ${response.status})`);
      }
      if (!contentType.includes('application/json')) {
        throw new Error('VBot API không trả về JSON. Chương trình VBot có thể đang dừng');
      }
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (_) {
        throw new Error('Dữ liệu chẩn đoán từ VBot không đúng định dạng JSON');
      }
      if (!data || typeof data !== 'object') {
        throw new Error('VBot API trả về dữ liệu chẩn đoán rỗng');
      }
      if (data.success === false) {
        throw new Error(data.message || 'VBot API báo lỗi khi lấy dữ liệu chẩn đoán');
      }
      render(data);
    } catch (error) {
      let message = error.message || 'Không xác định';
      if (error.name === 'AbortError') message = 'VBot API không phản hồi sau 5 giây';
      else if (message === 'Failed to fetch' || message.includes('NetworkError')) {
        message = 'Không thể kết nối tới VBot API. Hãy kiểm tra chương trình VBot và cổng API';
      }
      el('diag-error').textContent = `Không lấy được dữ liệu chẩn đoán: ${message}`;
      el('diag-error').classList.remove('d-none');
      el('diag-updated').textContent = 'VBot không hoạt động hoặc API mất kết nối';
    } finally { busy = false; el('diag-loading').classList.add('d-none'); }
  }
  function schedule() { if (timer !== null) clearInterval(timer); timer = el('diag-auto').checked ? setInterval(refresh, 5000) : null; }
  el('diag-refresh').addEventListener('click', refresh); el('diag-auto').addEventListener('change', schedule);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
  window.addEventListener('beforeunload', () => { if (timer !== null) clearInterval(timer); });
  schedule(); refresh();
})();
</script>
</body></html>
