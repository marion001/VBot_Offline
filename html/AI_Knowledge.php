<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

if ($Config['contact_info']['user_login']['active']) {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
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

$pipelineCfg = (isset($Config['processing_pipeline']) && is_array($Config['processing_pipeline'])) ? $Config['processing_pipeline'] : [];
$pipelineMode = (string)($pipelineCfg['mode'] ?? 'vbot');
$aiKnowledgeActive = array_key_exists('ai_knowledge_active', $pipelineCfg) ? (bool)$pipelineCfg['ai_knowledge_active'] : true;
$aiKnowledgeVectorActive = array_key_exists('ai_knowledge_vector_active', $pipelineCfg) ? (bool)$pipelineCfg['ai_knowledge_vector_active'] : true;
$geminiKeysRaw = $Config['virtual_assistant']['google_gemini']['api_key'] ?? [];
$hasGeminiEmbeddingKey = false;
if (is_array($geminiKeysRaw)) {
  foreach ($geminiKeysRaw as $gk) {
    if (trim((string)$gk) !== '') {
      $hasGeminiEmbeddingKey = true;
      break;
    }
  }
} elseif (is_string($geminiKeysRaw) && trim($geminiKeysRaw) !== '') {
  $hasGeminiEmbeddingKey = true;
}
$knowledgeDirDisplay = rtrim((string)$VBot_Offline, '/\\') . '/resource/ai_engine/knowledge';
?>
<!DOCTYPE html>
<html lang="vi">
<?php include 'html_head.php'; ?>

<head>
  <style>
    .kb-dropzone {
      border: 2px dashed rgba(13, 110, 253, 0.35);
      border-radius: 12px;
      padding: 18px 14px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s ease, background-color 0.2s ease;
    }
    .kb-dropzone:hover,
    .kb-dropzone.dragover {
      border-color: #0d6efd;
      background-color: rgba(13, 110, 253, 0.06);
    }
    .kb-preview-pre {
      max-height: 460px;
      overflow: auto;
      white-space: pre-wrap;
      word-break: break-word;
      font-size: 13px;
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      padding: 12px;
      border-radius: 8px;
      background: rgba(15, 23, 42, 0.06);
    }
    .kb-tag-pill {
      font-size: 10.5px;
      font-weight: 500;
      padding: 1px 6px;
      border-radius: 999px;
      background: rgba(13, 110, 253, 0.1);
      color: #0d6efd;
      display: inline-block;
      margin-right: 3px;
      margin-top: 2px;
    }
  </style>
</head>

<body>
  <?php
  include 'html_header_bar.php';
  include 'html_sidebar.php';
  ?>

  <main id="main" class="main">
    <div class="pagetitle d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h1>Kho Tri Thức AI Cục Bộ (VBot Knowledge v2)</h1>
        <nav>
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Kho Tri Thức AI</li>
			&nbsp;| Trạng Thái Kích Hoạt: <?php echo $Config['processing_pipeline']['ai_knowledge_active'] ? '<p class="text-success" title="Đang được kích hoạt">&nbsp;Đang Bật</p>' : '<p class="text-danger" title="Không được kích hoạt">&nbsp;Đang Tắt</p>'; ?>
          </ol>
        </nav>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-info" onclick="kbOpenInspectModal('')" title="Xem dữ liệu bảng chỉ mục FTS5 (kb_chunks) và bảng vector ngữ nghĩa (kb_vectors)">
          <i class="bi bi-table"></i> Xem Dữ Liệu Bảng Chỉ Mục &amp; Vector
        </button>
        <button type="button" class="btn btn-sm btn-success" onclick="kbRefreshList()">
          <i class="bi bi-arrow-clockwise"></i> Làm mới trang
        </button>
      </div>
    </div>

    <section class="section dashboard mt-3">
      <?php
      $priorityVaList = (isset($Config['virtual_assistant']['prioritize_virtual_assistants']) && is_array($Config['virtual_assistant']['prioritize_virtual_assistants']))
        ? $Config['virtual_assistant']['prioritize_virtual_assistants'] : [];
      $hasAiInPriorityVa = (
        (in_array('groq_ai', $priorityVaList, true) && !empty($Config['virtual_assistant']['groq_ai']['active']))
        || (in_array('google_gemini', $priorityVaList, true) && !empty($Config['virtual_assistant']['google_gemini']['active']))
        || (in_array('openai_compat', $priorityVaList, true) && !empty($Config['virtual_assistant']['openai_compat']['active']))
        || in_array('dev_ai_engine', $priorityVaList, true)
      );
      if (!$aiKnowledgeActive || ($pipelineMode !== 'ai' && !$hasAiInPriorityVa)):
      ?>
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2" role="alert">
          <div>
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <?php if (!$aiKnowledgeActive): ?>
              Tính năng <b>Kho Tri Thức AI (RAG Cục Bộ)</b> hiện đang <b>TẮT</b>. Hãy bật tại tab <code>Cấu hình Config</code> rồi khởi động lại VBot để AI sử dụng các tài liệu dưới đây.
            <?php else: ?>
              Chế độ xử lý hiện tại đang là <b>VBot theo từ khóa</b> nhưng chưa bật <b>Groq AI</b> hoặc <b>Google Gemini</b> trong danh sách <b>Ưu tiên trợ lý ảo</b>. Hãy bật ít nhất 1 trợ lý AI (hoặc chuyển sang chế độ <b>Áp dụng AI</b>) trong tab <code>Cấu hình Config</code>.
            <?php endif; ?>
          </div>
          <a href="Config.php" class="btn btn-sm btn-warning fw-semibold" onclick="loading('show')">Đi tới Cấu hình Config</a>
        </div>
      <?php endif; ?>

      <div id="kbStatusAlert" class="alert py-2 d-none" role="alert"></div>

      <!-- KPI Cards -->
      <div class="row">
        <div class="col-md-4">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">Tài Liệu Đã Nạp <span>| Knowledge Catalog</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-journal-text"></i>
                </div>
                <div class="ps-3">
                  <h6 id="kbKpiCount">0</h6>
                  <span class="text-muted small">Dung lượng: <b id="kbKpiSize">0 B</b></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">Chỉ Mục Hybrid <span>| FTS5 + Vector</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-diagram-3"></i>
                </div>
                <div class="ps-3">
                  <h6><span id="kbKpiChunks">0</span> <small class="fs-6 text-muted">đoạn</small></h6>
                  <span class="text-muted small">Vector ngữ nghĩa: <b id="kbKpiVectors">0</b> đoạn (SQLite RRF)</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card info-card customers-card">
            <div class="card-body">
              <h5 class="card-title">Trạng Thái Kích Hoạt</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi <?php echo ($aiKnowledgeActive && $pipelineMode === 'ai') ? 'bi-check-circle text-success' : 'bi-pause-circle text-warning'; ?>"></i>
                </div>
                <div class="ps-3">
                  <h6><?php echo $aiKnowledgeActive ? '<span class="text-success">Đang BẬT</span>' : '<span class="text-danger">Đang TẮT</span>'; ?></h6>
                  <span class="text-muted small">
                    Hybrid Vector: <?php echo $aiKnowledgeVectorActive ? '<b class="text-info">BẬT</b>' : '<b class="text-secondary">TẮT (chỉ FTS5)</b>'; ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Workspace -->
      <div class="row g-3">
        <!-- Hàng 1: Danh Mục Tài Liệu (Catalog) - rộng hết cỡ -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 pb-2">
                <h5 class="card-title p-0 m-0">
                  <i class="bi bi-folder2-open text-warning me-1"></i> Danh Sách Tài Liệu (Catalog)
                </h5>
                <div class="d-flex flex-nowrap gap-2 align-items-center">
                  <select class="form-select border-success form-select-sm" id="kbCategoryFilter" style="width: 160px; flex-shrink: 0;" onchange="kbRenderTable()">
                    <option value="">Tất cả danh mục</option>
                  </select>
                  <div class="input-group input-group-sm" style="width: 210px; flex-shrink: 0;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-success" id="kbFilterInput" placeholder="Lọc tên, tag, tiêu đề..." oninput="kbRenderTable()">
                  </div>
                </div>
              </div>

              <div class="small text-muted mb-2">
                Thư mục lưu trữ tài liệu: <code><?php echo htmlspecialchars($knowledgeDirDisplay); ?></code>
              </div>

              <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Tài liệu / Danh mục &amp; Tags</th>
                      <th class="text-center">Loại File</th>
                      <th class="text-center">Chỉ mục</th>
                      <th class="text-end">Dung lượng</th>
                      <th class="text-end">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody id="kbFileTableBody">
                    <tr><td colspan="5" class="text-center text-muted py-4">Đang tải danh sách tài liệu...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Hàng 1b: Kiểm Thử Truy Vấn Tri Thức (Search Playground) -->
        <div class="col-12">
          <div class="card">
            <div class="card-body py-3">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h6 class="fw-bold text-info mb-0">
                  <i class="bi bi-search-heart me-1"></i> Kiểm Thử Truy Vấn Tri Thức (Hybrid FTS5 + Vector RRF + Neighbor Window)
                </h6>
                <span class="small text-muted">Gõ thử câu hỏi để kiểm tra Top đoạn khớp &amp; điểm xếp hạng trước khi hỏi qua loa</span>
              </div>
              <div class="d-flex flex-nowrap gap-2 align-items-center">
                <input type="text" class="form-control border-success form-control-sm" id="kbPlaygroundQuery" placeholder="vd: Quy trình cài đặt và cấu hình là gì?" onkeydown="if(event.key==='Enter'){event.preventDefault();kbRunSearchPlayground();}">
                <button type="button" class="btn btn-sm btn-info text-white fw-semibold" style="flex-shrink:0;" onclick="kbRunSearchPlayground()">
                  <i class="bi bi-search-heart me-1"></i> Tìm thử
                </button>
                <button type="button" class="btn btn-sm btn-secondary d-none" id="kbPlaygroundClearBtn" style="flex-shrink:0;" onclick="kbClearSearchPlayground()">
                  Xóa kết quả
                </button>
              </div>
              <div id="kbPlaygroundResults" class="mt-2 d-none"></div>
            </div>
          </div>
        </div>

        <!-- Hàng 2 (trái): Tải tệp lên + Nạp URL Website -->
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-cloud-arrow-up text-primary me-1"></i> Tải Lên Tệp Dữ Liệu Tri Thức</h5>
              <div class="kb-dropzone mb-2" id="kbDropzone" onclick="document.getElementById('kbUploadInput').click()">
                <i class="bi bi-file-earmark-arrow-up fs-2 text-primary"></i>
                <div class="fw-semibold mt-1">Bấm chọn tệp hoặc kéo thả vào đây</div>
                <div class="text-muted small">
                  Hỗ trợ: <code>PDF, DOCX, ODT, XLS, XLSX, PPT, PPTX, MD, TXT, RTF, CSV, JSON, XML, YAML, HTML, EPUB, PY, JS, C/C++/H, JPG, PNG, WEBP</code><br>
                  Giới hạn: Văn bản/Code <b>5MB</b> • DOCX/ODT/Excel/Ảnh <b>10MB</b> • PPT/PPTX <b>15MB</b> • PDF/EPUB <b>20MB</b>
                </div>
              </div>
              <input type="file" class="form-control border-success form-control-sm mb-2" id="kbUploadInput" accept=".txt,.md,.rtf,.json,.xml,.yaml,.yml,.csv,.html,.htm,.py,.js,.c,.cpp,.h,.docx,.odt,.xls,.xlsx,.ppt,.pptx,.pdf,.epub,.jpg,.jpeg,.png,.webp" multiple>
              <button type="button" class="btn btn-primary btn-sm w-100" id="kbUploadBtn" onclick="kbUploadSelectedFiles()">
                <i class="bi bi-upload me-1"></i> Tải Lên Kho Tri Thức
              </button>

              <hr class="my-3">
              <label for="kbImportUrlInput" class="form-label small fw-semibold mb-1">
                <i class="bi bi-globe2 text-info me-1"></i> Nạp nhanh từ đường dẫn Website (URL → Markdown):
              </label>
              <div class="d-flex flex-nowrap gap-2 align-items-center mb-2">
                <input type="url" class="form-control border-success form-control-sm" id="kbImportUrlInput" placeholder="https://example.com/huong-dan-esp32" onkeydown="if(event.key==='Enter'){event.preventDefault();kbImportUrl();}">
                <button type="button" class="btn btn-sm btn-info" style="flex-shrink:0;" onclick="kbImportUrl()">
                  <i class="bi bi-cloud-download me-1"></i> Nạp URL
                </button>
              </div>

              <div class="alert <?php echo $hasGeminiEmbeddingKey ? 'alert-info' : 'alert-warning'; ?> py-2 px-3 mt-2 mb-0 small">
                <i class="bi bi-info-circle-fill me-1"></i>
                <b>Lưu ý Vector (<code>kb_vectors</code>) &amp; Đọc Ảnh / PDF Scan:</b> Cần nhập <b>Google Gemini API Key</b> trong mục <a href="Config.php" class="fw-semibold text-decoration-underline" onclick="loading('show')">Cấu hình &rarr; Trợ Lý Ảo &rarr; Google Gemini</a> để hệ thống tạo bảng <code>kb_vectors</code> và OCR ảnh/PDF scan khi lập chỉ mục (kể cả khi đang chọn <b>Groq AI</b> để xử lý câu lệnh).
                <?php if ($hasGeminiEmbeddingKey): ?>
                  <span class="badge bg-success ms-1"><i class="bi bi-check-lg"></i> Đã có Gemini Key</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark ms-1"><i class="bi bi-exclamation-circle"></i> Chưa có Gemini Key (chỉ chạy FTS5)</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Hàng 2 (phải): Tạo / Sửa nhanh -->
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-pencil-square text-success me-1"></i> Tạo / Sửa Tài Liệu Nhanh</h5>
              </div>
              <div class="mt-3 mb-2">
                <label for="kbEditFilename" class="form-label small fw-semibold mb-1">
                  Tên tệp (mặc định <code>.txt</code>; chỉnh sửa trực tiếp: <code>.txt, .md, .json, .xml, .yaml, .csv, .html, .py, .js, .c, .cpp, .h</code>):
                </label>
                <input type="text" class="form-control border-success form-control-sm" id="kbEditFilename" placeholder="vd: so_tay_gia_dinh.txt">
              </div>
              <div class="mb-3">
                <label for="kbEditContent" class="form-label small fw-semibold mb-1">Nội dung tri thức:</label>
                <textarea class="form-control border-success form-control-sm" id="kbEditContent" rows="9" placeholder="Nhập hoặc dán thông tin cần AI ghi nhớ tra cứu (mật khẩu Wi-Fi, hướng dẫn thiết bị, bảng giá, lịch học, nội quy...)"></textarea>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm flex-grow-1" onclick="kbSaveNote()">
                  <i class="bi bi-save me-1"></i> Lưu Tài Liệu Tri Thức
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="kbResetEditor()"><i class="bi bi-eraser"></i> Xóa trắng</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Hàng 3: Mẹo sử dụng - rộng hết cỡ -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-lightbulb text-info me-1"></i> Cách khai thác tối đa VBot Knowledge v2</h5>
              <ul class="small text-muted mb-0 ps-3">
                <li>Gắn <b>Danh mục</b> và <b>Tags</b> bằng nút <i class="bi bi-tags text-warning"></i> — bạn có thể hỏi VBot: <i>"Tôi đã nạp những tài liệu nào về ESP32?"</i>, <i>"Thống kê kho tri thức"</i>, hoặc <i>"Đọc tóm tắt tài liệu ..."</i>.</li>
                <li>Tìm kiếm <b>Hybrid (FTS5 + Vector RRF)</b>: tìm chính xác mã lỗi/từ khóa bằng FTS5 BM25, đồng thời hiểu câu hỏi đồng nghĩa bằng Vector Embedding (Gemini) lưu ngay trong SQLite cục bộ.</li>
                <li>Ở từng dòng tài liệu, bấm nút <i class="bi bi-cpu text-dark"></i> để <b>lập chỉ mục FTS5 &amp; tạo Vector</b> riêng cho tệp đó, hoặc bấm nút <i class="bi bi-table text-secondary"></i> (hay bấm vào nhãn số đoạn/vector) để <b>xem trực tiếp bảng dữ liệu chỉ mục (<code>kb_chunks</code>) và bảng vector (<code>kb_vectors</code>)</b>.</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Hàng 4 (Dưới cùng): Sao Lưu & Khôi Phục Kho Tri Thức (.tar.gz) -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 pb-2">
                <h5 class="card-title p-0 m-0">
                  <i class="bi bi-archive text-primary me-1"></i> Sao Lưu &amp; Khôi Phục Kho Tri Thức (<code>.tar.gz</code>)
                  <span class="badge bg-secondary-subtle text-secondary border ms-1" id="kbBackupCountBadge">0 / 5 bản</span>
                </h5>
                <div class="d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-sm btn-primary" onclick="kbCreateBackup()">
                    <i class="bi bi-file-earmark-zip me-1"></i> Tạo Bản Sao Lưu (.tar.gz)
                  </button>
                  <button type="button" class="btn btn-sm btn-secondary" onclick="kbLoadBackups()">
                    <i class="bi bi-arrow-clockwise"></i> Làm mới danh sách
                  </button>
                </div>
              </div>

              <div class="small text-muted mb-3">
                Thư mục sao lưu: <code>html/Backup_Upgrade/Backup_AI_Knowledge</code> • Lưu tối đa <b>5 tệp <code>.tar.gz</code></b> (gồm toàn bộ tài liệu + danh mục + SQLite FTS5 &amp; bảng vector <code>kb_vectors</code>); khi tạo bản mới đầy 5 tệp hệ thống sẽ <b>tự động xóa tệp cũ nhất</b>.
              </div>

              <div class="table-responsive mb-3">
                <table class="table table-hover table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Tệp sao lưu (.tar.gz)</th>
                      <th class="text-end">Dung lượng</th>
                      <th class="text-center">Thời gian tạo</th>
                      <th class="text-end">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody id="kbBackupTableBody">
                    <tr><td colspan="4" class="text-center text-muted py-3">Đang tải danh sách bản sao lưu...</td></tr>
                  </tbody>
                </table>
              </div>

              <div class="border-top pt-3">
                <label for="kbRestoreTarInput" class="form-label small fw-semibold mb-1">
                  <i class="bi bi-cloud-upload text-warning me-1"></i> Khôi phục từ tệp sao lưu <code>.tar.gz</code> tải lên từ máy tính:
                </label>
                <div class="d-flex flex-nowrap gap-2 align-items-center">
                  <input type="file" class="form-control border-success form-control-sm" id="kbRestoreTarInput" accept=".tar.gz,.tgz,.gz">
                  <button type="button" class="btn btn-sm btn-success fw-semibold" style="flex-shrink:0;" onclick="kbUploadRestoreTar()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Tải Lên &amp; Khôi Phục
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Modal Xem Nhanh Nội Dung -->
  <div class="modal fade" id="kbPreviewModal" tabindex="-1" aria-labelledby="kbPreviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-primary" id="kbPreviewTitle">Xem nội dung tài liệu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <pre class="kb-preview-pre mb-0" id="kbPreviewBody"></pre>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-primary" id="kbPreviewEditBtn">
            <i class="bi bi-pencil me-1"></i> Chuyển sang chỉnh sửa
          </button>
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Sửa Metadata Danh Mục / Tags -->
  <div class="modal fade" id="kbMetaModal" tabindex="-1" aria-labelledby="kbMetaModalTitle" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-success" id="kbMetaModalTitle"><i class="bi bi-tags me-1"></i> Chỉnh Sửa Danh Mục &amp; Metadata</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="kbMetaFilename">
          <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">Tên tệp:</label>
            <div class="form-control form-control-sm bg-light text-success" id="kbMetaFilenameDisplay"></div>
          </div>
          <div class="mb-2">
            <label for="kbMetaTitle" class="form-label small fw-semibold mb-1">Tiêu đề hiển thị (Title):</label>
            <input type="text" class="form-control form-control-sm border-success" id="kbMetaTitle" placeholder="vd: Hướng dẫn cài đặt VBot">
          </div>
          <div class="mb-2">
            <label for="kbMetaCategory" class="form-label small fw-semibold mb-1">Danh mục (Category):</label>
            <input type="text" class="form-control border-success form-control-sm" id="kbMetaCategory" list="kbCategorySuggestions" placeholder="vd: Kỹ thuật, Gia đình, ESP32, Bảng giá...">
            <datalist id="kbCategorySuggestions">
              <option value="Tài liệu"></option>
              <option value="Kỹ thuật"></option>
              <option value="Gia đình"></option>
              <option value="Hướng dẫn"></option>
              <option value="Bảng giá"></option>
              <option value="Mã nguồn"></option>
            </datalist>
          </div>
          <div class="mb-2">
            <label for="kbMetaTags" class="form-label small fw-semibold mb-1">Từ khóa / Tags (ngăn cách bằng dấu phẩy):</label>
            <input type="text" class="form-control border-success form-control-sm" id="kbMetaTags" placeholder="vd: ESP32, Bluetooth, Wi-Fi, cấu hình">
          </div>
          <div class="mb-1">
            <label for="kbMetaSummary" class="form-label small fw-semibold mb-1">Mô tả ngắn / Tóm tắt (Summary):</label>
            <textarea class="form-control border-success form-control-sm" id="kbMetaSummary" rows="3" placeholder="Mô tả nhanh nội dung chính của tài liệu giúp AI lọc danh mục chính xác hơn..."></textarea>
          </div>
        </div>
        <div class="modal-footer py-2 d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-primary" id="kbAutoTagBtn" onclick="kbAutoTagCurrent()" title="Nhờ AI tự đọc nội dung tài liệu để sinh Tiêu đề, Danh mục, Tags &amp; Tóm tắt">
            <i class="bi bi-stars me-1"></i>Dùng AI tự phân loại &amp; tóm tắt
          </button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-warning fw-semibold" onclick="kbSaveMeta()">
              <i class="bi bi-check2-circle me-1"></i> Lưu Dữ Liệu
            </button>
            <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Xem Bảng Dữ Liệu Chỉ Mục (kb_chunks) & Bảng Vector (kb_vectors) -->
  <div class="modal fade" id="kbInspectModal" tabindex="-1" aria-labelledby="kbInspectModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header py-2">
          <div>
            <h5 class="modal-title fw-bold text-primary mb-0" id="kbInspectModalTitle">
              <i class="bi bi-table me-1"></i> Dữ Liệu Chỉ Mục FTS5 (<code>kb_chunks</code>) &amp; Bảng Vector (<code>kb_vectors</code>)
            </h5>
            <div class="small text-muted" id="kbInspectSubtitle">Cơ sở dữ liệu SQLite: <code>resource/ai_engine/knowledge_index.sqlite3</code></div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body pt-2">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
		  
<ul class="nav nav-pills kb-inspect-tabs mb-0" id="kbInspectTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="kbTabChunksBtn"
            data-bs-toggle="tab" data-bs-target="#kbTabChunksPane"
            type="button" role="tab">
      <i class="bi bi-list-columns-reverse"></i>
      <span class="kb-tab-label">
        <span class="kb-tab-title">Bảng Chỉ Mục</span>
        <small>kb_chunks</small>
      </span>
      <span class="badge rounded-pill bg-primary-subtle text-primary"
            id="kbInspectChunkCount">0</span>
    </button>
  </li>

  <li class="nav-item" role="presentation">
    <button class="nav-link" id="kbTabVectorsBtn"
            data-bs-toggle="tab" data-bs-target="#kbTabVectorsPane"
            type="button" role="tab">
      <i class="bi bi-lightning-charge-fill"></i>
      <span class="kb-tab-label">
        <span class="kb-tab-title">Bảng Vector</span>
        <small>kb_vectors</small>
      </span>
      <span class="badge rounded-pill bg-info-subtle text-info"
            id="kbInspectVectorCount">0</span>
    </button>
  </li>
</ul>
			
            <div class="d-flex flex-wrap align-items-center gap-2">
              <div class="input-group input-group-sm" style="width: 240px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="kbInspectSearchInput" placeholder="Tìm trong bảng chỉ mục..." oninput="kbRenderInspectTables()">
              </div>
              <button type="button" class="btn btn-sm btn-secondary d-none" id="kbInspectShowAllBtn" onclick="kbOpenInspectModal('')">
                <i class="bi bi-collection"></i> Xem tất cả tệp
              </button>
              <button type="button" class="btn btn-sm btn-dark d-none" id="kbInspectIndexFileBtn" onclick="kbIndexFromModal()">
                <i class="bi bi-cpu"></i> Lập chỉ mục lại tệp này
              </button>
            </div>
          </div>

          <div class="tab-content pt-1">
            <!-- Tab 1: kb_chunks -->
            <div class="tab-pane fade show active" id="kbTabChunksPane" role="tabpanel">
              <div class="table-responsive" style="max-height: 58vh;">
                <table class="table table-bordered table-hover table-sm align-middle mb-0">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th class="text-center">ID</th>
                      <th>Tệp nguồn</th>
                      <th class="text-center">Đoạn #</th>
                      <th class="text-center">Vector</th>
                      <th>Nội dung đoạn trích (<code>content</code>) &amp; Chuẩn hóa FTS5 (<code>content_norm</code>)</th>
                    </tr>
                  </thead>
                  <tbody id="kbInspectChunksBody">
                    <tr><td colspan="5" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab 2: kb_vectors -->
            <div class="tab-pane fade" id="kbTabVectorsPane" role="tabpanel">
              <div class="table-responsive" style="max-height: 58vh;">
                <table class="table table-bordered table-hover table-sm align-middle mb-0">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th class="text-center">Chunk ID</th>
                      <th>Tệp nguồn</th>
                      <th class="text-center">Model / Chiều</th>
                      <th class="text-center">Chuẩn L2 / Biên</th>
                      <th>Vector <code>float32</code> (10 chiều đầu) &amp; Đoạn văn bản</th>
                    </tr>
                  </thead>
                  <tbody id="kbInspectVectorsBody">
                    <tr><td colspan="5" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <?php include 'html_footer.php'; ?>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <?php include 'html_js.php'; ?>

  <script>
    (function () {
      var KB_API_URL = 'includes/php_ajax/AI_Knowledge.php';
      var kbFilesCache = [];
      var kbPreviewFilename = '';
      var kbLoadingCount = 0;

      // Hiển thị / ẩn icon loading toàn trang (đếm số yêu cầu đang chạy song song)
      function kbLoading(on) {
        if (on) {
          kbLoadingCount++;
          if (kbLoadingCount === 1 && typeof loading === 'function') loading('show');
        } else {
          kbLoadingCount = Math.max(0, kbLoadingCount - 1);
          if (kbLoadingCount === 0 && typeof loading === 'function') loading('hide');
        }
      }

      // fetch có kèm loading show/hide; trả về đối tượng có .json() giống Response
      function kbFetch(url, opts) {
        kbLoading(true);
        return window.fetch(url, opts).then(function (r) {
          return r.text().then(function (txt) {
            kbLoading(false);
            return {
              ok: r.ok,
              status: r.status,
              json: function () { return JSON.parse(txt); }
            };
          });
        }, function (err) {
          kbLoading(false);
          throw err;
        });
      }

      function kbEscapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function (c) {
          return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
      }

      function kbSetStatus(msg, type) {
        var box = document.getElementById('kbStatusAlert');
        if (!box) return;
        if (!msg) {
          box.className = 'alert py-2 d-none';
          box.textContent = '';
          return;
        }
        var cls = type === 'error' ? 'alert-danger' : (type === 'info' ? 'alert-info' : 'alert-success');
        box.className = 'alert py-2 ' + cls;
        box.textContent = msg;
      }

      window.kbResetEditor = function () {
        var fn = document.getElementById('kbEditFilename');
        var ct = document.getElementById('kbEditContent');
        if (fn) fn.value = '';
        if (ct) ct.value = '';
        kbSetStatus('', '');
      };

      function kbPopulateCategoryFilter(categories) {
        var sel = document.getElementById('kbCategoryFilter');
        if (!sel) return;
        var current = sel.value || '';
        var html = '<option value="">Tất cả danh mục</option>';
        (categories || []).forEach(function (cat) {
          var esc = kbEscapeHtml(cat);
          html += '<option value="' + esc + '">' + esc + '</option>';
        });
        sel.innerHTML = html;
        if (current) {
          sel.value = current;
        }
      }

      function kbBuildStatusHtml(item) {
        var chunks = Number(item.chunk_count);
        var vecs = Number(item.vector_count || 0);
        var st = String(item.status || 'pending');
        var safeFn = kbEscapeHtml(item.filename);
        var parts = [];
        if (st === 'indexed' && chunks > 0) {
          parts.push('<span class="badge bg-success-subtle text-success border border-success-subtle">' + chunks + ' đoạn</span>');
        } else if (st === 'empty' || chunks === 0) {
          parts.push('<span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="Không trích xuất được chữ (có thể là PDF scan ảnh)">0 đoạn</span>');
        } else {
          parts.push('<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" title="Bấm nút Lập chỉ mục ở cột Thao tác hoặc hỏi VBot">Chờ chỉ mục</span>');
        }
        if (vecs > 0) {
          parts.push('<span class="badge bg-info-subtle text-info border border-info-subtle mt-1" title="Đã tạo vector ngữ nghĩa Hybrid"><i class="bi bi-lightning-charge-fill"></i> ' + vecs + ' vec</span>');
        }
        return '<div class="d-flex flex-column align-items-center" style="cursor:pointer;" data-kb-inspect="' + safeFn + '" title="Bấm để xem bảng dữ liệu chỉ mục (kb_chunks) &amp; vector (kb_vectors) của tệp này">' + parts.join('') + '</div>';
      }

      window.kbRenderTable = function () {
        var tbody = document.getElementById('kbFileTableBody');
        var q = ((document.getElementById('kbFilterInput') || {}).value || '').trim().toLowerCase();
        var catFilter = ((document.getElementById('kbCategoryFilter') || {}).value || '').trim();
        if (!tbody) return;
        var filtered = kbFilesCache.filter(function (item) {
          if (catFilter && String(item.category || '') !== catFilter) {
            return false;
          }
          if (!q) return true;
          var hay = [
            item.filename || '',
            item.title || '',
            item.category || '',
            Array.isArray(item.tags) ? item.tags.join(' ') : '',
            item.summary || ''
          ].join(' ').toLowerCase();
          return hay.indexOf(q) !== -1;
        });
        if (!filtered.length) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">'
            + (kbFilesCache.length ? 'Không có tài liệu nào khớp bộ lọc.' : 'Kho tri thức đang trống. Hãy tải tệp lên hoặc tạo nhanh tài liệu bên trái.')
            + '</td></tr>';
          return;
        }
        tbody.innerHTML = filtered.map(function (item) {
          var safeFn = kbEscapeHtml(item.filename);
          var safeTitle = kbEscapeHtml(item.title || item.filename);
          var safeCat = kbEscapeHtml(item.category || 'Tài liệu');
          var tagsHtml = '';
          if (Array.isArray(item.tags) && item.tags.length) {
            tagsHtml = item.tags.slice(0, 5).map(function (t) {
              return '<span class="kb-tag-pill">#' + kbEscapeHtml(t) + '</span>';
            }).join('');
          }
          var extBadge = '<span class="badge bg-secondary text-uppercase">' + kbEscapeHtml(item.ext) + '</span>';
          var hitBadge = Number(item.hit_count || 0) > 0
            ? '<span class="badge bg-light text-primary border ms-1" title="Số lần AI đã tra cứu tài liệu này"><i class="bi bi-bar-chart-line"></i> ' + Number(item.hit_count) + ' lượt tra cứu</span>'
            : '';
          var dupBadge = item.duplicate_of
            ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" title="Nội dung SHA-256 trùng 100% với tệp ' + kbEscapeHtml(item.duplicate_of) + '"><i class="bi bi-files"></i> Trùng với: ' + kbEscapeHtml(item.duplicate_of) + '</span>'
            : '';
          var refreshUrlBtn = item.source_url
            ? '<button type="button" class="btn btn-sm btn-info py-0 px-2 me-1" data-kb-refresh-url="' + safeFn + '" title="Cập nhật lại nội dung mới nhất từ URL: ' + kbEscapeHtml(item.source_url) + '"><i class="bi bi-cloud-arrow-down"></i></button>'
            : '';
          var indexBtn = '<button type="button" class="btn btn-sm btn-dark py-0 px-2 me-1" data-kb-index="' + safeFn + '" title="Lập chỉ mục FTS5 &amp; tạo Vector (kb_vectors) cho tệp này"><i class="bi bi-cpu"></i></button>';
          var inspectBtn = '<button type="button" class="btn btn-sm btn-primary py-0 px-2 me-1" data-kb-inspect="' + safeFn + '" title="Xem bảng chỉ mục (kb_chunks) &amp; bảng vector (kb_vectors) của tệp này"><i class="bi bi-table"></i></button>';
          var metaBtn = '<button type="button" class="btn btn-sm btn-warning py-0 px-2 me-1" data-kb-meta="' + safeFn + '" title="Sửa danh mục, tags &amp; tóm tắt"><i class="bi bi-tags"></i></button>';
          var viewEditBtns = item.editable
            ? '<button type="button" class="btn btn-sm btn-info py-0 px-2 me-1" data-kb-view="' + safeFn + '" title="Xem nội dung"><i class="bi bi-eye"></i></button>'
              + '<button type="button" class="btn btn-sm btn-primary py-0 px-2 me-1" data-kb-edit="' + safeFn + '" title="Chỉnh sửa"><i class="bi bi-pencil"></i></button>'
            : '';
          var dlHref = KB_API_URL + '?action=download&file=' + encodeURIComponent(item.filename);
          var subtitle = safeTitle !== safeFn ? ('<div class="small text-muted">' + safeFn + '</div>') : '';
          return '<tr>'
            + '<td class="text-break">'
            +   '<div class="fw-semibold"><i class="bi bi-file-earmark-text text-primary me-1"></i>' + safeTitle + '</div>'
            +   subtitle
            +   '<div class="mt-1"><span class="badge bg-light text-dark border me-1">' + safeCat + '</span>' + hitBadge + dupBadge + tagsHtml + '</div>'
            + '</td>'
            + '<td class="text-center">' + extBadge + '</td>'
            + '<td class="text-center">' + kbBuildStatusHtml(item) + '</td>'
            + '<td class="text-end text-muted small">' + kbEscapeHtml(item.size_human) + '<div style="font-size:10.5px;">' + kbEscapeHtml(item.updated_at) + '</div></td>'
            + '<td class="text-end text-nowrap">'
            + refreshUrlBtn
            + indexBtn
            + inspectBtn
            + metaBtn
            + viewEditBtns
            + '<a href="' + dlHref + '" class="btn btn-sm btn-success py-0 px-2 me-1" title="Tải xuống"><i class="bi bi-download"></i></a>'
            + '<button type="button" class="btn btn-sm btn-danger py-0 px-2" data-kb-del="' + safeFn + '" title="Xóa"><i class="bi bi-trash"></i></button>'
            + '</td>'
            + '</tr>';
        }).join('');

        tbody.querySelectorAll('[data-kb-refresh-url]').forEach(function (btn) {
          btn.addEventListener('click', function () { kbRefreshUrlFile(btn.getAttribute('data-kb-refresh-url'), btn); });
        });
        tbody.querySelectorAll('[data-kb-index]').forEach(function (btn) {
          btn.addEventListener('click', function () { kbIndexSingleFile(btn.getAttribute('data-kb-index'), btn); });
        });
        tbody.querySelectorAll('[data-kb-inspect]').forEach(function (el) {
          el.addEventListener('click', function () { kbOpenInspectModal(el.getAttribute('data-kb-inspect')); });
        });
        tbody.querySelectorAll('[data-kb-meta]').forEach(function (btn) {
          btn.addEventListener('click', function () { kbOpenMetaModal(btn.getAttribute('data-kb-meta')); });
        });
        tbody.querySelectorAll('[data-kb-view]').forEach(function (btn) {
          btn.addEventListener('click', function () { kbOpenPreview(btn.getAttribute('data-kb-view')); });
        });
        tbody.querySelectorAll('[data-kb-edit]').forEach(function (btn) {
          btn.addEventListener('click', function () { kbLoadForEdit(btn.getAttribute('data-kb-edit')); });
        });
        tbody.querySelectorAll('[data-kb-del]').forEach(function (btn) {
          btn.addEventListener('click', function () { kbDeleteFile(btn.getAttribute('data-kb-del')); });
        });
      };

      window.kbRefreshList = function () {
        var tbody = document.getElementById('kbFileTableBody');
        if (tbody) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Đang tải danh sách tài liệu...</td></tr>';
        }
        kbFetch(KB_API_URL + '?action=list', { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data || !data.success) {
              kbFilesCache = [];
              if (tbody) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">' + kbEscapeHtml((data && data.message) || 'Không tải được danh sách.') + '</td></tr>';
              }
              return;
            }
            kbFilesCache = Array.isArray(data.files) ? data.files : [];
            var cnt = document.getElementById('kbKpiCount');
            var sz = document.getElementById('kbKpiSize');
            var ch = document.getElementById('kbKpiChunks');
            var vc = document.getElementById('kbKpiVectors');
            if (cnt) cnt.textContent = String(data.count || kbFilesCache.length);
            if (sz) sz.textContent = data.total_size_human || '0 B';
            if (ch) ch.textContent = String(data.total_chunks || 0);
            if (vc) vc.textContent = String(data.total_vectors || 0);
            kbPopulateCategoryFilter(data.categories || []);
            kbRenderTable();
          })
          .catch(function (err) {
            if (tbody) {
              tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Lỗi kết nối: ' + kbEscapeHtml(err && err.message) + '</td></tr>';
            }
          });
      };

      function kbOpenMetaModal(filename) {
        var item = null;
        for (var i = 0; i < kbFilesCache.length; i++) {
          if (kbFilesCache[i].filename === filename) {
            item = kbFilesCache[i];
            break;
          }
        }
        if (!item) return;
        document.getElementById('kbMetaFilename').value = item.filename;
        document.getElementById('kbMetaFilenameDisplay').textContent = item.filename;
        document.getElementById('kbMetaTitle').value = item.title || '';
        document.getElementById('kbMetaCategory').value = item.category || 'Tài liệu';
        document.getElementById('kbMetaTags').value = Array.isArray(item.tags) ? item.tags.join(', ') : '';
        document.getElementById('kbMetaSummary').value = item.summary || '';
        var modalEl = document.getElementById('kbMetaModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
          bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
      }

      window.kbSaveMeta = function () {
        var fn = (document.getElementById('kbMetaFilename').value || '').trim();
        if (!fn) return;
        var form = new URLSearchParams();
        form.set('action', 'update_meta');
        form.set('filename', fn);
        form.set('title', document.getElementById('kbMetaTitle').value || '');
        form.set('category', document.getElementById('kbMetaCategory').value || '');
        form.set('tags', document.getElementById('kbMetaTags').value || '');
        form.set('summary', document.getElementById('kbMetaSummary').value || '');
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var modalEl = document.getElementById('kbMetaModal');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
              bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã cập nhật metadata.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'Cập nhật metadata thất bại.', 'error');
            }
          })
          .catch(function () {
            kbSetStatus('Lỗi kết nối khi lưu metadata.', 'error');
          });
      };

      var kbInspectDataCache = { chunks: [], vectors: [], filename_filter: '' };

      // Hỏi xác nhận trước khi tạo lại chỉ mục / vector cho tệp đã có dữ liệu (tránh tốn quota Gemini)
      function kbConfirmReindexFile(filename) {
        var item = null;
        for (var i = 0; i < kbFilesCache.length; i++) {
          if (kbFilesCache[i].filename === filename) {
            item = kbFilesCache[i];
            break;
          }
        }
        if (!item) return true;
        var chunks = Number(item.chunk_count || 0);
        var vecs = Number(item.vector_count || 0);
        if (chunks <= 0 && vecs <= 0) return true;
        var msg = 'Tệp "' + filename + '" đã có dữ liệu chỉ mục sẵn:\n'
          + '- ' + Math.max(chunks, 0) + ' đoạn chỉ mục FTS5 (kb_chunks)\n'
          + '- ' + vecs + ' vector ngữ nghĩa (kb_vectors)\n\n'
          + 'Bạn có muốn tạo lại dữ liệu không?\n'
          + (vecs > 0 || chunks > 0
            ? 'Lưu ý: Việc tạo lại sẽ xóa dữ liệu cũ và gọi lại Gemini Embedding API để xử lý, tốn tài nguyên/hạn mức (quota) của Gemini.'
            : '');
        return confirm(msg);
      }

      window.kbIndexSingleFile = function (filename, btnEl) {
        if (!filename) return;
        if (!kbConfirmReindexFile(filename)) return;
        if (btnEl) btnEl.disabled = true;
        kbSetStatus('Đang lập chỉ mục FTS5 & tạo Vector (kb_vectors) cho tệp "' + filename + '"...', 'info');
        var form = new URLSearchParams();
        form.set('action', 'index_file');
        form.set('filename', filename);
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (btnEl) btnEl.disabled = false;
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã lập chỉ mục tệp thành công.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'Lập chỉ mục tệp thất bại.', 'error');
              kbRefreshList();
            }
          })
          .catch(function () {
            if (btnEl) btnEl.disabled = false;
            kbSetStatus('Lỗi kết nối khi gửi yêu cầu lập chỉ mục tệp.', 'error');
          });
      };

      window.kbIndexFromModal = function () {
        var fn = kbInspectDataCache.filename_filter || '';
        if (!fn) return;
        if (!kbConfirmReindexFile(fn)) return;
        var btn = document.getElementById('kbInspectIndexFileBtn');
        if (btn) btn.disabled = true;
        var form = new URLSearchParams();
        form.set('action', 'index_file');
        form.set('filename', fn);
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (btn) btn.disabled = false;
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã lập chỉ mục tệp.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
              kbOpenInspectModal(fn);
            } else {
              kbSetStatus((data && data.message) || 'Lập chỉ mục thất bại.', 'error');
            }
          })
          .catch(function () {
            if (btn) btn.disabled = false;
          });
      };

      window.kbOpenInspectModal = function (filename) {
        var fn = String(filename || '').trim();
        var searchEl = document.getElementById('kbInspectSearchInput');
        if (searchEl) searchEl.value = '';
        var titleEl = document.getElementById('kbInspectModalTitle');
        var showAllBtn = document.getElementById('kbInspectShowAllBtn');
        var idxFileBtn = document.getElementById('kbInspectIndexFileBtn');
        var chunksBody = document.getElementById('kbInspectChunksBody');
        var vectorsBody = document.getElementById('kbInspectVectorsBody');

        if (titleEl) {
          titleEl.innerHTML = '<i class="bi bi-table me-1"></i> ' + (fn
            ? ('Chỉ Mục &amp; Vector của tệp: <span class="text-dark">' + kbEscapeHtml(fn) + '</span>')
            : 'Toàn Bộ Bảng Chỉ Mục FTS5 (<code>kb_chunks</code>) &amp; Vector (<code>kb_vectors</code>)');
        }
        if (showAllBtn) showAllBtn.className = fn ? 'btn btn-sm btn-secondary' : 'btn btn-sm btn-secondary d-none';
        if (idxFileBtn) idxFileBtn.className = fn ? 'btn btn-sm btn-dark' : 'btn btn-sm btn-dark d-none';
        if (chunksBody) chunksBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Đang truy vấn SQLite kb_chunks...</td></tr>';
        if (vectorsBody) vectorsBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Đang truy vấn SQLite kb_vectors...</td></tr>';

        var modalEl = document.getElementById('kbInspectModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
          bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        var url = KB_API_URL + '?action=inspect' + (fn ? ('&file=' + encodeURIComponent(fn)) : '');
        kbFetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data || !data.success || !data.inspect) {
              var errMsg = kbEscapeHtml((data && data.message) || 'Không đọc được dữ liệu chỉ mục.');
              if (chunksBody) chunksBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">' + errMsg + '</td></tr>';
              if (vectorsBody) vectorsBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">' + errMsg + '</td></tr>';
              return;
            }
            kbInspectDataCache = {
              chunks: Array.isArray(data.inspect.chunks) ? data.inspect.chunks : [],
              vectors: Array.isArray(data.inspect.vectors) ? data.inspect.vectors : [],
              filename_filter: fn
            };
            kbRenderInspectTables();
          })
          .catch(function (err) {
            var errMsg = 'Lỗi kết nối: ' + kbEscapeHtml(err && err.message);
            if (chunksBody) chunksBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">' + errMsg + '</td></tr>';
            if (vectorsBody) vectorsBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">' + errMsg + '</td></tr>';
          });
      };

      window.kbRenderInspectTables = function () {
        var chunksBody = document.getElementById('kbInspectChunksBody');
        var vectorsBody = document.getElementById('kbInspectVectorsBody');
        var cCntEl = document.getElementById('kbInspectChunkCount');
        var vCntEl = document.getElementById('kbInspectVectorCount');
        var q = ((document.getElementById('kbInspectSearchInput') || {}).value || '').trim().toLowerCase();

        var filteredChunks = (kbInspectDataCache.chunks || []).filter(function (c) {
          if (!q) return true;
          var hay = ((c.filename || '') + ' ' + (c.content || '') + ' ' + (c.content_norm || '')).toLowerCase();
          return hay.indexOf(q) !== -1;
        });
        var filteredVectors = (kbInspectDataCache.vectors || []).filter(function (v) {
          if (!q) return true;
          var hay = ((v.filename || '') + ' ' + (v.model || '') + ' ' + (v.content_preview || '')).toLowerCase();
          return hay.indexOf(q) !== -1;
        });

        if (cCntEl) cCntEl.textContent = String(filteredChunks.length);
        if (vCntEl) vCntEl.textContent = String(filteredVectors.length);

        if (chunksBody) {
          if (!filteredChunks.length) {
            chunksBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Chưa có đoạn chỉ mục nào trong bảng <code>kb_chunks</code>. Hãy bấm nút <i class="bi bi-cpu"></i> Lập chỉ mục ở tệp tương ứng.</td></tr>';
          } else {
            chunksBody.innerHTML = filteredChunks.map(function (c) {
              var vecBadge = c.has_vector
                ? '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-check2"></i> Có</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Không</span>';
              return '<tr>'
                + '<td class="text-center fw-semibold text-muted">#' + Number(c.chunk_id) + '</td>'
                + '<td class="text-break small fw-semibold">' + kbEscapeHtml(c.filename) + '</td>'
                + '<td class="text-center"><span class="badge bg-light text-dark border">Đoạn ' + Number(c.chunk_index) + '</span><div class="text-muted" style="font-size:10.5px;">' + Number(c.char_len || 0) + ' ký tự</div></td>'
                + '<td class="text-center">' + vecBadge + '</td>'
                + '<td class="small">'
                +   '<div class="text-break" style="white-space:pre-wrap;">' + kbEscapeHtml(c.content) + '</div>'
                +   '<div class="text-muted mt-1" style="font-size:11px;font-family:monospace;"><b>FTS5 norm:</b> ' + kbEscapeHtml(c.content_norm) + '</div>'
                + '</td>'
                + '</tr>';
            }).join('');
          }
        }

        if (vectorsBody) {
          if (!filteredVectors.length) {
            vectorsBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Bảng <code>kb_vectors</code> chưa có dữ liệu vector cho bộ lọc này. (Cần bật Tìm kiếm ngữ nghĩa Hybrid Vector, cấu hình Gemini API Key và bấm <i class="bi bi-cpu"></i> Lập chỉ mục).</td></tr>';
          } else {
            vectorsBody.innerHTML = filteredVectors.map(function (v) {
              var sampleStr = Array.isArray(v.sample_values) ? ('[' + v.sample_values.join(', ') + ', ...]') : '[]';
              return '<tr>'
                + '<td class="text-center fw-semibold text-muted">#' + Number(v.chunk_id) + '</td>'
                + '<td class="text-break small"><div class="fw-semibold">' + kbEscapeHtml(v.filename) + '</div><span class="badge bg-light text-dark border">Đoạn ' + Number(v.chunk_index) + '</span></td>'
                + '<td class="text-center small"><div class="fw-semibold text-info">' + kbEscapeHtml(v.model) + '</div><span class="text-muted">' + Number(v.dim) + ' chiều (' + Number(v.byte_size) + ' B)</span></td>'
                + '<td class="text-center small"><div>L2: <b>' + Number(v.l2_norm) + '</b></div><div class="text-muted" style="font-size:11px;">[' + Number(v.min_val) + ' .. ' + Number(v.max_val) + ']</div></td>'
                + '<td class="small">'
                +   '<code class="d-block text-break mb-1">' + kbEscapeHtml(sampleStr) + '</code>'
                +   '<div class="text-muted text-break" style="font-size:11.5px;">' + kbEscapeHtml(v.content_preview) + '</div>'
                + '</td>'
                + '</tr>';
            }).join('');
          }
        }
      };

      function kbOpenPreview(filename) {
        kbFetch(KB_API_URL + '?action=read&file=' + encodeURIComponent(filename), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data || !data.success) {
              kbSetStatus((data && data.message) || 'Không đọc được tệp.', 'error');
              return;
            }
            kbPreviewFilename = data.filename || filename;
            var title = document.getElementById('kbPreviewTitle');
            var body = document.getElementById('kbPreviewBody');
            if (title) title.textContent = 'Nội dung: ' + kbPreviewFilename;
            if (body) body.textContent = data.content || '';
            var modalEl = document.getElementById('kbPreviewModal');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
              bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
          })
          .catch(function () {
            kbSetStatus('Không đọc được nội dung tài liệu.', 'error');
          });
      }

      function kbLoadForEdit(filename) {
        kbFetch(KB_API_URL + '?action=read&file=' + encodeURIComponent(filename), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data || !data.success) {
              kbSetStatus((data && data.message) || 'Không đọc được tệp.', 'error');
              return;
            }
            var fn = document.getElementById('kbEditFilename');
            var ct = document.getElementById('kbEditContent');
            if (fn) fn.value = data.filename || filename;
            if (ct) {
              ct.value = data.content || '';
              ct.focus();
            }
            kbSetStatus('Đang chỉnh sửa tài liệu "' + (data.filename || filename) + '". Sửa xong hãy bấm Lưu Tài Liệu Tri Thức.', 'info');
            if (fn) fn.scrollIntoView({ behavior: 'smooth', block: 'center' });
          })
          .catch(function () {
            kbSetStatus('Không đọc được nội dung tài liệu.', 'error');
          });
      }

      window.kbSaveNote = function () {
        var fn = ((document.getElementById('kbEditFilename') || {}).value || '').trim();
        var ct = (document.getElementById('kbEditContent') || {}).value || '';
        if (!fn) {
          kbSetStatus('Vui lòng nhập tên tài liệu (ví dụ: so_tay_gia_dinh.txt).', 'error');
          return;
        }
        if (!ct.trim()) {
          kbSetStatus('Nội dung tri thức không được để trống.', 'error');
          return;
        }
        var form = new URLSearchParams();
        form.set('action', 'save');
        form.set('filename', fn);
        form.set('content', ct);
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã lưu tài liệu thành công.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'Lưu tài liệu thất bại.', 'error');
            }
          })
          .catch(function () {
            kbSetStatus('Lỗi gửi yêu cầu lưu tài liệu.', 'error');
          });
      };

      function kbUploadSingleFile(file) {
        var fd = new FormData();
        fd.append('action', 'upload');
        fd.append('file', file);
        fd.append('csrf_token', window.VBOT_CSRF_TOKEN || '');
        return kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-CSRF-Token': window.VBOT_CSRF_TOKEN || '' },
          body: fd
        }).then(function (r) { return r.json(); });
      }

      window.kbUploadSelectedFiles = function (droppedFiles) {
        var input = document.getElementById('kbUploadInput');
        var list = droppedFiles || (input && input.files ? input.files : null);
        if (!list || !list.length) {
          kbSetStatus('Vui lòng chọn ít nhất 1 tệp tri thức trước khi tải lên.', 'error');
          return;
        }
        var files = Array.prototype.slice.call(list);
        kbSetStatus('Đang tải lên ' + files.length + ' tệp...', 'info');
        Promise.all(files.map(kbUploadSingleFile))
          .then(function (results) {
            var okCount = 0;
            var errs = [];
            results.forEach(function (res) {
              if (res && res.success) okCount++;
              else errs.push((res && res.message) || 'Lỗi không xác định');
            });
            if (input) input.value = '';
            if (errs.length) {
              kbSetStatus('Đã tải lên ' + okCount + '/' + files.length + ' tệp. Lỗi: ' + errs.join('; '), okCount ? 'info' : 'error');
            } else {
              var msg = 'Đã tải lên thành công ' + okCount + ' tài liệu tri thức! Bấm nút lập chỉ mục (biểu tượng CPU) ở dòng tài liệu hoặc hỏi trực tiếp VBot.';
              kbSetStatus(msg, 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(msg, 4);
            }
            kbRefreshList();
          })
          .catch(function () {
            kbSetStatus('Lỗi kết nối khi tải tệp lên máy chủ.', 'error');
          });
      };

      window.kbClearSearchPlayground = function () {
        var qEl = document.getElementById('kbPlaygroundQuery');
        var resEl = document.getElementById('kbPlaygroundResults');
        var clrBtn = document.getElementById('kbPlaygroundClearBtn');
        if (qEl) qEl.value = '';
        if (resEl) {
          resEl.innerHTML = '';
          resEl.className = 'mt-2 d-none';
        }
        if (clrBtn) clrBtn.className = 'btn btn-sm btn-secondary d-none';
      };

      window.kbRunSearchPlayground = function () {
        var q = ((document.getElementById('kbPlaygroundQuery') || {}).value || '').trim();
        var cat = ((document.getElementById('kbCategoryFilter') || {}).value || '').trim();
        var resEl = document.getElementById('kbPlaygroundResults');
        var clrBtn = document.getElementById('kbPlaygroundClearBtn');
        if (!q) {
          kbSetStatus('Vui lòng nhập câu hỏi cần kiểm thử tìm kiếm.', 'error');
          return;
        }
        var url = KB_API_URL + '?action=test_search&q=' + encodeURIComponent(q) + (cat ? ('&category=' + encodeURIComponent(cat)) : '');
        kbFetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!resEl) return;
            resEl.className = 'mt-2';
            if (clrBtn) clrBtn.className = 'btn btn-sm btn-secondary';
            if (!data || !data.success) {
              resEl.innerHTML = '<div class="alert alert-danger py-2 mb-0 small">' + kbEscapeHtml((data && data.message) || 'Kiểm thử thất bại.') + '</div>';
              return;
            }
            var hits = Array.isArray(data.hits) ? data.hits : [];
            if (!hits.length) {
              resEl.innerHTML = '<div class="alert alert-warning py-2 mb-0 small">Không tìm thấy đoạn tri thức nào khớp với câu hỏi này.</div>';
              return;
            }
            var rows = hits.map(function (h, idx) {
              var mtype = String(h.match_type || 'keyword');
              var badgeCls = mtype === 'hybrid' ? 'bg-success' : (mtype === 'semantic' ? 'bg-info text-dark' : 'bg-secondary');
              var nbBadge = h.has_neighbor ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">+Đoạn liền kề</span>' : '';
              return '<tr>'
                + '<td class="text-center fw-bold">#' + (idx + 1) + '</td>'
                + '<td class="small"><div class="fw-semibold text-break">' + kbEscapeHtml(h.source) + '</div>'
                + '<span class="badge ' + badgeCls + ' text-uppercase">' + kbEscapeHtml(mtype) + '</span>' + nbBadge
                + '<div class="text-muted mt-1" style="font-size:11px;">RRF: <b>' + Number(h.score || 0) + '</b> • Tin cậy: <b>' + Math.round(Number(h.confidence || 0) * 100) + '%</b></div></td>'
                + '<td class="small text-break" style="white-space:pre-wrap;">' + kbEscapeHtml(h.content) + '</td>'
                + '</tr>';
            }).join('');
            resEl.innerHTML = '<div class="table-responsive" style="max-height: 420px; overflow-y: auto;"><table class="table table-bordered table-sm align-middle mb-0">'
              + '<thead class="table-light sticky-top"><tr><th class="text-center">Top</th><th>Nguồn &amp; Kiểu khớp</th><th>Nội dung đoạn trích (gửi cho AI)</th></tr></thead>'
              + '<tbody>' + rows + '</tbody></table></div>';
          })
          .catch(function () {
            kbSetStatus('Lỗi kết nối khi chạy kiểm thử tìm kiếm.', 'error');
          });
      };

      window.kbImportUrl = function () {
        var inp = document.getElementById('kbImportUrlInput');
        var urlVal = ((inp || {}).value || '').trim();
        if (!urlVal) {
          kbSetStatus('Vui lòng nhập đường dẫn URL (http:// hoặc https://).', 'error');
          return;
        }
        var form = new URLSearchParams();
        form.set('action', 'import_url');
        form.set('url', urlVal);
        form.set('category', 'Web');
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbSetStatus('Đang tải nội dung trang web và lập chỉ mục...', 'info');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.success) {
              if (inp) inp.value = '';
              kbSetStatus(data.message || 'Đã nạp URL thành công.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'Nạp URL thất bại.', 'error');
            }
          })
          .catch(function () {
            kbSetStatus('Lỗi kết nối khi nạp URL.', 'error');
          });
      };

      window.kbAutoTagCurrent = function () {
        var fn = (document.getElementById('kbMetaFilename').value || '').trim();
        if (!fn) return;
        for (var i = 0; i < kbFilesCache.length; i++) {
          if (kbFilesCache[i].filename === fn && kbFilesCache[i].duplicate_of) {
            var dupName = kbFilesCache[i].duplicate_of;
            if (!confirm('Tài liệu "' + fn + '" đang trùng nội dung 100% với tài liệu "' + dupName + '" đã có trong kho.\n\nBạn nên xóa bớt 1 bản trùng để tránh tốn tài nguyên AI. Bạn vẫn muốn chạy AI tự phân loại & tóm tắt cho tệp này chứ?')) {
              return;
            }
            break;
          }
        }
        var btn = document.getElementById('kbAutoTagBtn');
        if (btn) btn.disabled = true;
        var form = new URLSearchParams();
        form.set('action', 'auto_tag');
        form.set('filename', fn);
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (btn) btn.disabled = false;
            if (data && data.success && data.entry) {
              document.getElementById('kbMetaTitle').value = data.entry.title || '';
              document.getElementById('kbMetaCategory').value = data.entry.category || 'Tài liệu';
              document.getElementById('kbMetaTags').value = Array.isArray(data.entry.tags) ? data.entry.tags.join(', ') : '';
              document.getElementById('kbMetaSummary').value = data.entry.summary || '';
              kbSetStatus(data.message || 'AI đã tự phân loại & tóm tắt thành công.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'AI tự phân loại thất bại.', 'error');
            }
          })
          .catch(function () {
            if (btn) btn.disabled = false;
            kbSetStatus('Lỗi kết nối khi gọi AI tự phân loại.', 'error');
          });
      };

      window.kbRefreshUrlFile = function (filename, btnEl) {
        if (!filename) return;
        if (!confirm('Tải lại nội dung mới nhất từ URL gốc cho "' + filename + '" và lập chỉ mục lại?')) return;
        if (btnEl) btnEl.disabled = true;
        kbSetStatus('Đang tải lại nội dung mới nhất từ URL cho "' + filename + '"...', 'info');
        var form = new URLSearchParams();
        form.set('action', 'refresh_url');
        form.set('filename', filename);
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (btnEl) btnEl.disabled = false;
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã cập nhật lại từ URL.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'Cập nhật lại từ URL thất bại.', 'error');
            }
          })
          .catch(function () {
            if (btnEl) btnEl.disabled = false;
            kbSetStatus('Lỗi kết nối khi cập nhật lại từ URL.', 'error');
          });
      };

      window.kbLoadBackups = function () {
        var tbody = document.getElementById('kbBackupTableBody');
        var badge = document.getElementById('kbBackupCountBadge');
        kbFetch(KB_API_URL + '?action=list_backups', { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!tbody) return;
            if (!data || !data.success) {
              tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">' + kbEscapeHtml((data && data.message) || 'Không tải được danh sách bản sao lưu.') + '</td></tr>';
              return;
            }
            var list = Array.isArray(data.backups) ? data.backups : [];
            var maxKeep = Number(data.max_keep || 5);
            if (badge) badge.textContent = list.length + ' / ' + maxKeep + ' bản';
            if (!list.length) {
              tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Chưa có bản sao lưu nào trong <code>html/Backup_Upgrade/Backup_AI_Knowledge</code>.</td></tr>';
              return;
            }
            tbody.innerHTML = list.map(function (b) {
              var safeFn = kbEscapeHtml(b.filename);
              var dlHref = KB_API_URL + '?action=download_backup&file=' + encodeURIComponent(b.filename);
              return '<tr>'
                + '<td class="fw-semibold text-break"><i class="bi bi-file-earmark-zip text-primary me-1"></i>' + safeFn + '</td>'
                + '<td class="text-end text-muted small">' + kbEscapeHtml(b.size_human) + '</td>'
                + '<td class="text-center text-muted small">' + kbEscapeHtml(b.created_at) + '</td>'
                + '<td class="text-end text-nowrap">'
                +   '<button type="button" class="btn btn-sm btn-warning py-0 px-2 me-1" data-kb-bk-restore="' + safeFn + '" title="Khôi phục Kho Tri Thức từ bản sao lưu này"><i class="bi bi-arrow-counterclockwise"></i> Khôi phục</button>'
                +   '<a href="' + dlHref + '" class="btn btn-sm btn-success py-0 px-2 me-1" title="Tải tệp sao lưu .tar.gz về máy"><i class="bi bi-download"></i></a>'
                +   '<button type="button" class="btn btn-sm btn-danger py-0 px-2" data-kb-bk-del="' + safeFn + '" title="Xóa bản sao lưu này"><i class="bi bi-trash"></i></button>'
                + '</td>'
                + '</tr>';
            }).join('');

            tbody.querySelectorAll('[data-kb-bk-restore]').forEach(function (btn) {
              btn.addEventListener('click', function () { kbRestoreServerBackup(btn.getAttribute('data-kb-bk-restore')); });
            });
            tbody.querySelectorAll('[data-kb-bk-del]').forEach(function (btn) {
              btn.addEventListener('click', function () { kbDeleteServerBackup(btn.getAttribute('data-kb-bk-del')); });
            });
          })
          .catch(function () {
            if (tbody) tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Lỗi kết nối khi tải danh sách bản sao lưu.</td></tr>';
          });
      };

      window.kbCreateBackup = function () {
        var form = new URLSearchParams();
        form.set('action', 'create_backup');
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbSetStatus('Đang đóng gói bản sao lưu Kho Tri Thức (.tar.gz)...', 'info');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã tạo bản sao lưu .tar.gz thành công.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbLoadBackups();
            } else {
              kbSetStatus((data && data.message) || 'Tạo bản sao lưu thất bại.', 'error');
            }
          })
          .catch(function () {
            kbSetStatus('Lỗi kết nối khi tạo bản sao lưu.', 'error');
          });
      };

      window.kbRestoreServerBackup = function (filename) {
        if (!filename) return;
        if (!confirm('Khôi phục Kho Tri Thức từ bản sao lưu "' + filename + '"?\nCác tệp trùng tên, danh mục và bảng chỉ mục/vector SQLite sẽ được khôi phục từ bản này.')) {
          return;
        }
        var form = new URLSearchParams();
        form.set('action', 'restore_backup');
        form.set('filename', filename);
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbSetStatus('Đang giải nén và khôi phục từ "' + filename + '"...', 'info');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã khôi phục Kho Tri Thức thành công!', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'Khôi phục thất bại.', 'error');
            }
          })
          .catch(function () {
            kbSetStatus('Lỗi kết nối khi khôi phục bản sao lưu.', 'error');
          });
      };

      window.kbUploadRestoreTar = function () {
        var inputEl = document.getElementById('kbRestoreTarInput');
        var file = inputEl && inputEl.files && inputEl.files[0];
        if (!file) {
          kbSetStatus('Vui lòng chọn tệp sao lưu .tar.gz từ máy tính trước khi bấm Khôi phục.', 'error');
          return;
        }
        if (!confirm('Tải lên và khôi phục Kho Tri Thức từ tệp "' + file.name + '"?\nCác tệp trùng tên và chỉ mục/vector SQLite sẽ được ghi đè từ bản sao lưu này.')) {
          return;
        }
        var fd = new FormData();
        fd.append('action', 'upload_restore_backup');
        fd.append('file', file);
        fd.append('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbSetStatus('Đang tải lên và khôi phục tệp "' + file.name + '"...', 'info');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-CSRF-Token': window.VBOT_CSRF_TOKEN || '' },
          body: fd
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (inputEl) inputEl.value = '';
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã khôi phục Kho Tri Thức thành công!', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'Khôi phục thất bại.', 'error');
            }
          })
          .catch(function () {
            if (inputEl) inputEl.value = '';
            kbSetStatus('Lỗi kết nối khi tải lên tệp sao lưu .tar.gz.', 'error');
          });
      };

      window.kbDeleteServerBackup = function (filename) {
        if (!filename) return;
        if (!confirm('Bạn có chắc muốn xóa bản sao lưu "' + filename + '" không?')) return;
        var form = new URLSearchParams();
        form.set('action', 'delete_backup');
        form.set('filename', filename);
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã xóa bản sao lưu.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbLoadBackups();
            } else {
              kbSetStatus((data && data.message) || 'Xóa bản sao lưu thất bại.', 'error');
            }
          })
          .catch(function () {
            kbSetStatus('Lỗi kết nối khi xóa bản sao lưu.', 'error');
          });
      };

      function kbDeleteFile(filename) {
        if (!confirm('Bạn có chắc chắn muốn xóa tài liệu tri thức "' + filename + '" không?')) {
          return;
        }
        var form = new URLSearchParams();
        form.set('action', 'delete');
        form.set('filename', filename);
        form.set('csrf_token', window.VBOT_CSRF_TOKEN || '');
        kbFetch(KB_API_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''
          },
          body: form.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.success) {
              kbSetStatus(data.message || 'Đã xóa tài liệu.', 'ok');
              if (typeof showMessagePHP === 'function') showMessagePHP(data.message, 4);
              kbRefreshList();
            } else {
              kbSetStatus((data && data.message) || 'Xóa tài liệu thất bại.', 'error');
            }
          })
          .catch(function () {
            kbSetStatus('Lỗi khi xóa tài liệu.', 'error');
          });
      }

      document.addEventListener('DOMContentLoaded', function () {
        kbRefreshList();
        kbLoadBackups();
        var editFromPreview = document.getElementById('kbPreviewEditBtn');
        if (editFromPreview) {
          editFromPreview.addEventListener('click', function () {
            var modalEl = document.getElementById('kbPreviewModal');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
              bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            if (kbPreviewFilename) kbLoadForEdit(kbPreviewFilename);
          });
        }
        var dz = document.getElementById('kbDropzone');
        if (dz) {
          ['dragenter', 'dragover'].forEach(function (evt) {
            dz.addEventListener(evt, function (e) {
              e.preventDefault();
              e.stopPropagation();
              dz.classList.add('dragover');
            });
          });
          ['dragleave', 'drop'].forEach(function (evt) {
            dz.addEventListener(evt, function (e) {
              e.preventDefault();
              e.stopPropagation();
              dz.classList.remove('dragover');
            });
          });
          dz.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
              kbUploadSelectedFiles(e.dataTransfer.files);
            }
          });
        }
      });
    })();
  </script>
</body>

</html>
