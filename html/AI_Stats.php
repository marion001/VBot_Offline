<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

if ($Config['contact_info']['user_login']['active']) {
  session_start();
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

$aiEngineDir = rtrim((string)$VBot_Offline, '/\\') . '/resource/ai_engine';
$telemetryPath = $aiEngineDir . '/ai_telemetry.json';
$intentCachePath = $aiEngineDir . '/ai_intent_cache.json';
$aiMemoryPath = $aiEngineDir . '/ai_memory.json';
$voiceJournalPath = $aiEngineDir . '/voice_journal.json';

function vbot_read_json_safe($path) {
  if (!is_file($path)) {
    return [];
  }
  $raw = @file_get_contents($path);
  if ($raw === false || trim($raw) === '') {
    return [];
  }
  $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}

$telemetry = vbot_read_json_safe($telemetryPath);
$intentCache = vbot_read_json_safe($intentCachePath);
$aiMemory = vbot_read_json_safe($aiMemoryPath);
$voiceJournal = vbot_read_json_safe($voiceJournalPath);
$journalEntries = (isset($voiceJournal['entries']) && is_array($voiceJournal['entries'])) ? $voiceJournal['entries'] : [];
$journalCount = count($journalEntries);
$recentJournalEntries = array_reverse(array_slice($journalEntries, -8));

$todayDate = date('d/m/Y');
$emptyBucket = [
  'date' => $todayDate,
  'requests' => 0,
  'cache_hits' => 0,
  'ai_calls' => 0,
  'fallbacks' => 0,
  'errors' => 0,
  'providers' => [],
  'tools' => [],
];

$todayBucket = (isset($telemetry['today']) && is_array($telemetry['today']) && (($telemetry['today']['date'] ?? '') === $todayDate))
  ? array_merge($emptyBucket, $telemetry['today'])
  : $emptyBucket;
$totalBucket = (isset($telemetry['total']) && is_array($telemetry['total']))
  ? array_merge($emptyBucket, $telemetry['total'])
  : $emptyBucket;

$cacheEntriesCount = count($intentCache);
$memoriesCount = is_array($aiMemory['memories'] ?? null) ? count($aiMemory['memories']) : 0;
$shoppingCount = is_array($aiMemory['shopping_list'] ?? null) ? count($aiMemory['shopping_list']) : 0;
$aliasesCount = is_array($aiMemory['device_aliases'] ?? null) ? count($aiMemory['device_aliases']) : 0;
$updatedAt = (string)($telemetry['updated_at'] ?? 'Chưa có dữ liệu');

$statsPayloadJson = json_encode([
  'today' => $todayBucket,
  'total' => $totalBucket,
  'today_date' => $todayDate,
  'updated_at' => $updatedAt,
], JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">
<?php include 'html_head.php'; ?>

<body>
  <?php
  include 'html_header_bar.php';
  include 'html_sidebar.php';
  ?>
  <main id="main" class="main">
    <div class="pagetitle d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h1>Thống Kê Hiệu Năng AI</h1>
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Thống Kê AI</li>
          </ol>
        </nav>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <div class="btn-group" role="group">
          <button type="button" id="btn_scope_today" class="btn btn-sm btn-primary" onclick="renderAiStatsScope('today')">Hôm nay (<?php echo htmlspecialchars($todayDate); ?>)</button>
          <button type="button" id="btn_scope_total" class="btn btn-sm btn-outline-primary" onclick="renderAiStatsScope('total')">Tổng tích lũy</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise"></i> Làm mới trang</button>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="resetAiTelemetry()"><i class="bi bi-trash"></i> Reset Thống Kê</button>
      </div>
    </div>

    <section class="section dashboard mt-3">
      <div class="row">
        <div class="col-xxl-3 col-md-6">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">Tổng Lệnh AI <span id="kpi_scope_label">| Hôm nay</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-cpu"></i>
                </div>
                <div class="ps-3">
                  <h6 id="kpi_req_count">0</h6>
                  <span class="text-muted small">Gọi AI: <b id="kpi_ai_calls">0</b> | Fallback VBot: <b id="kpi_fallbacks">0</b> | Lỗi: <b id="kpi_errors" class="text-danger">0</b></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xxl-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">Tiết Kiệm Cache <span>| 0 Token</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-lightning-charge"></i>
                </div>
                <div class="ps-3">
                  <h6 id="kpi_cache_ratio">0%</h6>
                  <span id="kpi_cache_hits" class="text-success small fw-bold">0</span> <span class="text-muted small">lệnh khớp cục bộ</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xxl-3 col-md-6">
          <div class="card info-card customers-card">
            <div class="card-body">
              <h5 class="card-title">Token Tiêu Thụ <span>| Thực tế</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-bar-chart-line"></i>
                </div>
                <div class="ps-3">
                  <h6 id="kpi_total_tokens">0</h6>
                  <span class="text-muted small">Ghi nhận từ Groq / Gemini API</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xxl-3 col-md-6">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">Tốc Độ Trung Bình <span>| Phản hồi</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-speedometer2"></i>
                </div>
                <div class="ps-3">
                  <h6 id="kpi_avg_ms">0.0 ms</h6>
                  <span class="text-muted small">Cập nhật: <?php echo htmlspecialchars($updatedAt); ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Hiệu Năng Theo AI Provider <span id="tbl_scope_label">| Hôm nay</span></h5>
              <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Provider</th>
                      <th class="text-center">Thành công / Lỗi</th>
                      <th class="text-end">TB (ms)</th>
                      <th class="text-end">Nhanh / Chậm (ms)</th>
                      <th class="text-end">Prompt / Comp / Tổng Token</th>
                    </tr>
                  </thead>
                  <tbody id="ai_stats_providers_tbody"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 pb-2">
                <h5 class="card-title p-0 m-0">Kiểm Tra Hạn Mức &amp; Trạng Thái API Key Groq AI</h5>
                <button type="button" class="btn btn-sm btn-success rounded-pill" onclick="checkGroqQuotaOnStats()">
                  <i class="bi bi-shield-check"></i> Kiểm Tra Token &amp; Key
                </button>
              </div>
              <div id="ai_stats_groq_quota_box">
                <p class="text-muted small mb-0">Bấm <b>Kiểm Tra Token &amp; Key</b> để kiểm tra trực tiếp lượt gọi còn lại trong ngày, token còn lại trong phút và thời gian reset cho toàn bộ API Key Groq đang cấu hình.</p>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Top Công Cụ (Function Tools) Được Dùng Nhiều Nhất</h5>
              <div id="ai_stats_top_tools"></div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Kho Dữ Liệu AI <span>| resource/ai_engine/</span></h5>
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span><i class="bi bi-lightning-charge text-primary me-1"></i> Bộ Đệm Lệnh Cục Bộ (Intent Cache)</span>
                  <span class="badge bg-primary rounded-pill"><?php echo number_format($cacheEntriesCount); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span><i class="bi bi-journal-bookmark text-success me-1"></i> Ghi Nhớ Cá Nhân</span>
                  <span class="badge bg-success rounded-pill"><?php echo number_format($memoriesCount); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span><i class="bi bi-cart3 text-info me-1"></i> Danh Sách Mua Sắm</span>
                  <span class="badge bg-info rounded-pill"><?php echo number_format($shoppingCount); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span><i class="bi bi-tags text-warning me-1"></i> Tên Gọi Tắt Thiết Bị (Aliases)</span>
                  <span class="badge bg-warning text-dark rounded-pill"><?php echo number_format($aliasesCount); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span><i class="bi bi-mic-fill text-danger me-1"></i> Nhật Ký Giọng Nói (Voice Journal)</span>
                  <span class="badge bg-danger rounded-pill"><?php echo number_format($journalCount); ?></span>
                </li>
              </ul>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center pt-3 pb-2">
                <h5 class="card-title p-0 m-0">Nhật Ký Giọng Nói Gần Đây</h5>
                <?php if ($journalCount > 0): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="clearVoiceJournal()" title="Xóa sạch toàn bộ Nhật Ký Giọng Nói"><i class="bi bi-trash"></i> Xóa</button>
                <?php endif; ?>
              </div>
              <?php if (empty($recentJournalEntries)): ?>
                <p class="text-muted small mb-0">Chưa có ghi chú nào. Hãy thử nói: <i>"Ghi nhật ký hôm nay..."</i></p>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($recentJournalEntries as $jItem):
                    if (!is_array($jItem)) continue;
                    $jDt = trim((string)($jItem['date'] ?? '') . ' ' . (string)($jItem['time'] ?? ''));
                    $jTxt = (string)($jItem['text'] ?? '');
                  ?>
                    <div class="list-group-item px-0 py-2">
                      <div class="small text-muted"><i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($jDt); ?></div>
                      <div class="small"><?php echo htmlspecialchars($jTxt); ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
  <?php include 'html_footer.php'; ?>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <?php include 'html_js.php'; ?>
  <script>
    var VBOT_AI_STATS_DATA = <?php echo $statsPayloadJson ?: '{}'; ?>;
    var VBOT_PROVIDER_LABELS = {
      groq_ai: 'Groq AI',
      gemini_ai: 'Google Gemini AI',
      openai_compat: 'OpenAI-Compatible (Ollama / OpenRouter)',
      dev_ai_engine: 'Dev Custom AI',
      intent_cache: 'Local Intent Cache (0 token)'
    };

    function escapeHtmlStats(str) {
      return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function fmtNumStats(n) {
      return Number(n || 0).toLocaleString('en-US');
    }

    function renderAiStatsScope(scope) {
      scope = (scope === 'total') ? 'total' : 'today';
      var bucket = (VBOT_AI_STATS_DATA && VBOT_AI_STATS_DATA[scope]) ? VBOT_AI_STATS_DATA[scope] : {};

      var btnToday = document.getElementById('btn_scope_today');
      var btnTotal = document.getElementById('btn_scope_total');
      if (btnToday && btnTotal) {
        btnToday.className = 'btn btn-sm ' + (scope === 'today' ? 'btn-primary' : 'btn-outline-primary');
        btnTotal.className = 'btn btn-sm ' + (scope === 'total' ? 'btn-primary' : 'btn-outline-primary');
      }

      var scopeText = (scope === 'total') ? '| Tổng tích lũy' : '| Hôm nay';
      var kpiScope = document.getElementById('kpi_scope_label');
      var tblScope = document.getElementById('tbl_scope_label');
      if (kpiScope) kpiScope.textContent = (scope === 'total') ? '| Tổng' : '| Hôm nay';
      if (tblScope) tblScope.textContent = scopeText;

      var reqCount = Number(bucket.requests || 0);
      var cacheHits = Number(bucket.cache_hits || 0);
      var aiCalls = Number(bucket.ai_calls || 0);
      var fallbacks = Number(bucket.fallbacks || 0);
      var errors = Number(bucket.errors || 0);
      var cacheRatio = reqCount > 0 ? Math.round((cacheHits * 1000) / reqCount) / 10 : 0;

      var providers = (bucket.providers && typeof bucket.providers === 'object') ? bucket.providers : {};
      var totalTokens = 0;
      var sumMs = 0;
      var sumOps = 0;
      var provKeys = Object.keys(providers);
      provKeys.forEach(function (k) {
        var p = providers[k];
        if (!p || typeof p !== 'object') return;
        totalTokens += Number(p.total_tokens || 0);
        var ops = Number(p.ok || 0) + Number(p.err || 0);
        sumOps += ops;
        sumMs += Number(p.total_ms || 0);
      });
      var avgMs = sumOps > 0 ? (Math.round((sumMs * 10) / sumOps) / 10) : 0;

      document.getElementById('kpi_req_count').textContent = fmtNumStats(reqCount);
      document.getElementById('kpi_ai_calls').textContent = fmtNumStats(aiCalls);
      document.getElementById('kpi_fallbacks').textContent = fmtNumStats(fallbacks);
      document.getElementById('kpi_errors').textContent = fmtNumStats(errors);
      document.getElementById('kpi_cache_ratio').textContent = cacheRatio + '%';
      document.getElementById('kpi_cache_hits').textContent = fmtNumStats(cacheHits);
      document.getElementById('kpi_total_tokens').textContent = fmtNumStats(totalTokens);
      document.getElementById('kpi_avg_ms').textContent = avgMs.toFixed(1) + ' ms';

      var tbody = document.getElementById('ai_stats_providers_tbody');
      if (tbody) {
        if (!provKeys.length) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Chưa có dữ liệu thống kê AI. Hãy thử ra lệnh cho VBot ở chế độ AI!</td></tr>';
        } else {
          var rowsHtml = '';
          provKeys.forEach(function (pKey) {
            var p = providers[pKey];
            if (!p || typeof p !== 'object') return;
            var pOk = Number(p.ok || 0);
            var pErr = Number(p.err || 0);
            var pOps = Math.max(1, pOk + pErr);
            var pAvg = (Number(p.total_ms || 0) / pOps).toFixed(1);
            var pMin = (p.min_ms !== null && p.min_ms !== undefined) ? Number(p.min_ms).toFixed(1) : '0.0';
            var pMax = (p.max_ms !== null && p.max_ms !== undefined) ? Number(p.max_ms).toFixed(1) : '0.0';
            var pLabel = VBOT_PROVIDER_LABELS[pKey] || pKey;
            var labelClass = (pKey === 'intent_cache') ? 'text-success' : 'text-primary';
            var tokCell = (pKey === 'intent_cache')
              ? '<span class="badge bg-success-subtle text-success">0 token</span>'
              : (fmtNumStats(p.prompt_tokens || 0) + ' / ' + fmtNumStats(p.completion_tokens || 0) + ' / <b>' + fmtNumStats(p.total_tokens || 0) + '</b>');
            rowsHtml += '<tr>'
              + '<td><span class="fw-semibold ' + labelClass + '">' + escapeHtmlStats(pLabel) + '</span></td>'
              + '<td class="text-center"><span class="badge bg-success">' + pOk + '</span> <span class="badge bg-danger">' + pErr + '</span></td>'
              + '<td class="text-end fw-semibold">' + pAvg + '</td>'
              + '<td class="text-end text-muted small">' + pMin + ' / ' + pMax + '</td>'
              + '<td class="text-end small">' + tokCell + '</td>'
              + '</tr>';
          });
          tbody.innerHTML = rowsHtml;
        }
      }

      var toolsWrap = document.getElementById('ai_stats_top_tools');
      if (toolsWrap) {
        var toolsMap = (bucket.tools && typeof bucket.tools === 'object') ? bucket.tools : {};
        var entries = Object.keys(toolsMap).map(function (k) { return [k, Number(toolsMap[k] || 0)]; });
        entries.sort(function (a, b) { return b[1] - a[1]; });
        entries = entries.slice(0, 10);
        if (!entries.length) {
          toolsWrap.innerHTML = '<p class="text-muted mb-0">Chưa có công cụ nào được gọi.</p>';
        } else {
          var maxHits = Math.max(1, entries[0][1]);
          var tHtml = '';
          entries.forEach(function (pair) {
            var pct = Math.round((pair[1] * 1000) / maxHits) / 10;
            tHtml += '<div class="mb-2">'
              + '<div class="d-flex justify-content-between small mb-1">'
              + '<span class="fw-semibold"><code>' + escapeHtmlStats(pair[0]) + '</code></span>'
              + '<span><b>' + fmtNumStats(pair[1]) + '</b> lượt</span>'
              + '</div>'
              + '<div class="progress" style="height: 8px;"><div class="progress-bar bg-primary" role="progressbar" style="width: ' + pct + '%;"></div></div>'
              + '</div>';
          });
          toolsWrap.innerHTML = tHtml;
        }
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      renderAiStatsScope('today');
    });

    function checkGroqQuotaOnStats() {
      var resultBox = document.getElementById('ai_stats_groq_quota_box');
      if (typeof loading === 'function') loading('show');
      var xhr = (typeof vbotCreateXhr === 'function') ? vbotCreateXhr() : new XMLHttpRequest();
      xhr.open('POST', 'includes/php_ajax/Check_Connection.php', true);
      xhr.timeout = 30000;
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (typeof loading === 'function') loading('hide');
        if (xhr.status !== 200) {
          try {
            var errResp = JSON.parse(xhr.responseText);
            if (typeof show_message === 'function') show_message(errResp.message || ('Không thể kiểm tra hạn mức Groq, HTTP ' + xhr.status));
            else alert(errResp.message || ('HTTP ' + xhr.status));
          } catch (_) {
            alert('Không thể kiểm tra hạn mức Groq, HTTP ' + xhr.status);
          }
          return;
        }
        try {
          var response = JSON.parse(xhr.responseText);
          if (!response.success) {
            if (typeof show_message === 'function') show_message(response.message || 'Kiểm tra hạn mức Groq thất bại');
            else alert(response.message || 'Kiểm tra hạn mức Groq thất bại');
            return;
          }
          if (resultBox) {
            var html = '<div class="table-responsive mt-2"><table class="table table-sm table-bordered table-striped mb-0 bg-white">';
            html += '<thead class="table-dark"><tr><th>#</th><th>API Key</th><th>Trạng Thái</th><th>Lượt còn / Ngày</th><th>Token còn / Phút</th><th>Reset sau</th></tr></thead><tbody>';
            (response.results || []).forEach(function (row) {
              var badgeClass = row.ok ? 'bg-success' : (row.http_code === 429 ? 'bg-warning text-dark' : 'bg-danger');
              var reqText = (row.remaining_requests !== null && row.limit_requests !== null)
                ? (row.remaining_requests + ' / ' + row.limit_requests)
                : '-';
              var tokText = (row.remaining_tokens !== null && row.limit_tokens !== null)
                ? (row.remaining_tokens + ' / ' + row.limit_tokens)
                : '-';
              var resetText = row.reset_requests ? String(row.reset_requests) : '-';
              html += '<tr>'
                + '<td>' + row.index + '</td>'
                + '<td><code>' + escapeHtmlStats(row.key || '') + '</code></td>'
                + '<td><span class="badge ' + badgeClass + '">' + escapeHtmlStats(row.status || row.status_text || '') + '</span></td>'
                + '<td>' + reqText + '</td>'
                + '<td>' + tokText + '</td>'
                + '<td>' + escapeHtmlStats(resetText) + '</td>'
                + '</tr>';
            });
            html += '</tbody></table></div>';
            resultBox.innerHTML = html;
          }
          if (typeof showMessagePHP === 'function') showMessagePHP(response.message, 5);
        } catch (e) {
          alert('Lỗi đọc dữ liệu phản hồi: ' + e.message);
        }
      };
      xhr.onerror = function () {
        if (typeof loading === 'function') loading('hide');
        alert('Không thể kết nối để kiểm tra hạn mức Groq');
      };
      xhr.ontimeout = function () {
        if (typeof loading === 'function') loading('hide');
        alert('Yêu cầu kiểm tra hạn mức Groq đã quá thời gian 30 giây');
      };
      xhr.send('check_groq_quota=1&use_config_keys=1');
    }

    function resetAiTelemetry() {
      if (!confirm('Bạn có chắc chắn muốn đặt lại toàn bộ số liệu Thống Kê Hiệu Năng AI không?')) {
        return;
      }
      if (typeof loading === 'function') loading('show');
      var xhr = (typeof vbotCreateXhr === 'function') ? vbotCreateXhr() : new XMLHttpRequest();
      xhr.open('POST', 'includes/php_ajax/Check_Connection.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (typeof loading === 'function') loading('hide');
        try {
          var resp = JSON.parse(xhr.responseText);
          if (xhr.status === 200 && resp.success) {
            if (typeof showMessagePHP === 'function') showMessagePHP(resp.message, 4);
            setTimeout(function () { window.location.reload(); }, 600);
          } else {
            alert(resp.message || 'Không thể reset thống kê AI');
          }
        } catch (e) {
          alert('Lỗi khi reset thống kê AI (HTTP ' + xhr.status + ')');
        }
      };
      xhr.send('clear_ai_telemetry=1');
    }

    function clearVoiceJournal() {
      if (!confirm('Bạn có chắc chắn muốn xóa sạch toàn bộ Nhật Ký Giọng Nói không?')) {
        return;
      }
      if (typeof loading === 'function') loading('show');
      var xhr = (typeof vbotCreateXhr === 'function') ? vbotCreateXhr() : new XMLHttpRequest();
      xhr.open('POST', 'includes/php_ajax/Check_Connection.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (typeof loading === 'function') loading('hide');
        try {
          var resp = JSON.parse(xhr.responseText);
          if (xhr.status === 200 && resp.success) {
            if (typeof showMessagePHP === 'function') showMessagePHP(resp.message, 4);
            setTimeout(function () { window.location.reload(); }, 600);
          } else {
            alert(resp.message || 'Không thể xóa Nhật Ký Giọng Nói');
          }
        } catch (e) {
          alert('Lỗi khi xóa Nhật Ký Giọng Nói (HTTP ' + xhr.status + ')');
        }
      };
      xhr.send('clear_voice_journal=1');
    }
  </script>
</body>

</html>
