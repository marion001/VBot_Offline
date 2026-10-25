<?php
include 'Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))) { header('Location: Login.php'); exit; }
if (empty($_SESSION['vbot_csrf_token']) || !is_string($_SESSION['vbot_csrf_token']) || strlen($_SESSION['vbot_csrf_token'])<64) $_SESSION['vbot_csrf_token']=bin2hex(random_bytes(32));
$sourceCsrf=$_SESSION['vbot_csrf_token'];
?>
<!DOCTYPE html><html lang="vi">
<?php include 'html_head.php'; session_write_close(); ?>
<body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main">
  <div class="pagetitle"><h1>Quản Lý Thiết Bị Bluetooth — A2DP Sink / Source</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li><li class="breadcrumb-item active">Quản Lý Thiết Bị Bluetooth</li></ol></nav></div>
  <section class="section">
    <div class="card"><div class="card-body pt-3">
      <h2 class="h5">Thiết lập nâng cao</h2>
      <div class="row align-items-center g-2 mb-3">
        <label class="col-sm-5 col-form-label" for="source-input-limit">Giới hạn Điện thoại → VBot <i class="bi bi-question-circle" onclick="show_message('Mặc định 2 điện thoại kết nối đồng thời; có thể chọn từ 1 đến 7. Giữ nhiều kết nối nhưng chỉ chọn một điện thoại phát nhạc vào VBot. Không giới hạn số thiết bị đã ghép đôi. Khả năng kết nối thực tế tùy phần cứng. Nếu đang có nhiều điện thoại hơn mức muốn đặt, hãy ngắt bớt trước khi lưu. Agent tự dùng mức 2 khi chưa có cấu hình hợp lệ.')"></i></label>
        <div class="col-sm-3"><input type="number" class="form-control border-success" id="source-input-limit" min="1" max="7" step="1" value="2" required></div>
        <div class="col-sm-4"><button type="button" class="btn btn-success" id="source-input-limit-save">Lưu giới hạn điện thoại</button></div>
      </div>
      <div class="row align-items-center g-2 mb-3">
        <label class="col-sm-5 col-form-label" for="source-connection-limit">Giới hạn VBot → loa / tai nghe <i class="bi bi-question-circle" onclick="show_message('Mặc định 5 loa/tai nghe Bluetooth kết nối đồng thời, chọn từ 1 đến 7. Không giới hạn số thiết bị đã lưu ghép đôi. Khả năng kết nối thực tế tùy phần cứng; vẫn chỉ chọn một đầu ra phát nhạc. Khi vượt giới hạn, dịch vụ tự ngắt thiết bị mới và ưu tiên giữ đầu ra đang chọn.')"></i></label>
        <div class="col-sm-3"><input type="number" class="form-control border-success" id="source-connection-limit" min="1" max="7" step="1" value="5" required></div>
        <div class="col-sm-4"><button type="button" class="btn btn-success" id="source-limit-save">Lưu giới hạn thiết bị</button></div>
      </div>

            <p>Cài dịch vụ phát ra loa Bluetooth và kiểm tra sửa Wi-Fi/Bluetooth theo phần cứng, có backup. Có thể ngắt nhạc Bluetooth khi khởi động lại dịch vụ</p>
            <button type="button" class="btn btn-primary" id="source-install"><i class="bi bi-tools"></i> Cài đặt, Bật A2DP Source</button>
            <a class="btn btn-link" href="FAQ.php#accordion_wifi_bluetooth_coexistence">Hướng dẫn sửa Wi-Fi/Bluetooth</a>
    </div></div>
    <div class="card"><div class="card-body pt-3">
      <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center"><h2 class="h5 mb-0">Quản lý âm thanh Bluetooth</h2><div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" id="source-refresh"><i class="bi bi-arrow-clockwise"></i> Làm mới</button><button class="btn btn-primary" id="source-scan"><i class="bi bi-search"></i> Quét thiết bị</button></div></div>
      <div class="alert alert-info mt-3" id="source-notice" role="status">Đang tải trạng thái…</div>
      <div class="border rounded p-3 mb-3">
        <h2 class="h5"><i class="bi bi-speaker"></i> Đầu ra đang chọn</h2>
        <p class="fw-bold text-success mb-2" id="source-current">—</p>
        <button type="button" class="btn btn-success" id="source-local"><i class="bi bi-house-door"></i> Phát về loa VBot</button>
      </div>
      <dl class="row mb-0"><dt class="col-sm-3">Tên Bluetooth VBot</dt><dd class="col-sm-9" id="source-alias">—</dd><dt class="col-sm-3">Loa Bluetooth đã chọn</dt><dd class="col-sm-9" id="source-selected">—</dd></dl>
      <p class="small text-muted mt-3 mb-0">Dùng chung tên Bluetooth, giữ chức năng nhận nhạc và Bluetooth PAN. Đầu ra áp dụng cho nhạc qua đường âm thanh chung, kể cả AirPlay/Bluetooth khi VBot dừng. Cuộc gọi và các âm thanh phát trực tiếp ngoài đường chung vẫn dùng loa VBot. Khi mất kết nối đầu ra sẽ quay về loa VBot. Bluetooth có độ trễ riêng; cần kiểm thử wake word với loa ngoài.</p>
    </div></div>
    <div class="card"><div class="card-body pt-3">
      <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0"><b>Điện thoại, loa và tai nghe — A2DP Sink / Source</b></h2>
        <button type="button" class="btn btn-primary" id="source-devices-scan"><i class="bi bi-search"></i> Quét thiết bị</button>
      </div>
      <p class="small text-muted">Sink: điện thoại gửi nhạc vào VBot. Source: VBot gửi nhạc ra loa/tai nghe. Nút Ngắt nhạc đầu vào chỉ ngắt âm thanh, giữ PAN nếu có. Trạng thái kết nối Bluetooth không đồng nghĩa đang phát nhạc. Quên thiết bị sẽ xóa ghép đôi của cả âm thanh và PAN.</p>
      <p class="small text-muted">Bật chế độ ghép đôi trên loa/tai nghe rồi nhấn Quét thiết bị. Ghép đôi → Kết nối và phát. Tự kết nối chỉ áp dụng cho một thiết bị đã chọn; nhấn Ngắt sẽ tắt tự kết nối để không nối lại ngay. Không tự quét hoặc tự tải lại trang.</p>
      <h3 class="h5 mt-4"><i class="bi bi-phone"></i> Điện thoại → VBot <small class="text-muted">Nhận nhạc</small></h3>
      <div class="table-responsive"><table class="table table-bordered align-middle border-danger" style="--bs-table-border-color:#dc3545"><thead class="table-light"><tr><th class="text-danger border-danger" style="border:2px solid #dc3545">Thiết bị / MAC</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Ghép đôi</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Bluetooth</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Âm thanh</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Thao tác</th></tr></thead><tbody id="source-input-devices"></tbody></table></div>
      <h3 class="h5 mt-4"><i class="bi bi-speaker"></i> VBot → Loa / tai nghe <small class="text-muted">Phát nhạc</small></h3>
      <div class="table-responsive"><table class="table table-bordered align-middle border-danger" style="--bs-table-border-color:#dc3545"><thead class="table-light"><tr><th class="text-danger border-danger" style="border:2px solid #dc3545">Thiết bị / MAC</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Ghép đôi</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Bluetooth</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Âm thanh / Đầu ra</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Tự kết nối</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Thao tác</th></tr></thead><tbody id="source-devices"></tbody></table></div>
      <h3 class="h5 mt-4">Thiết bị chưa rõ chức năng âm thanh</h3>
      <p class="small text-muted">Thiết bị mới tìm thấy qua quét nằm ở đây dù đã công bố chức năng âm thanh. Sau khi ghép đôi hoặc kết nối, thiết bị được đưa vào nhóm nhận/phát phù hợp; thiết bị hỗ trợ hai chiều có thể xuất hiện ở cả hai nhóm. Thiết bị đã ghép đôi nhưng chưa xác định chức năng vẫn ở đây. Các dòng của cùng một thiết bị dùng chung màu nền.</p>
      <div class="table-responsive"><table class="table table-striped table-bordered align-middle border-danger" style="--bs-table-border-color:#dc3545"><thead class="table-light"><tr><th class="text-danger border-danger" style="border:2px solid #dc3545">Thiết bị / MAC</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Ghép đôi</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Bluetooth</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Âm thanh</th><th class="text-danger border-danger" style="border:2px solid #dc3545">Thao tác</th></tr></thead><tbody id="source-other-devices"></tbody></table></div>
    </div></div>
  </section>
</main>
<div class="modal fade" id="bt-key-modal" tabindex="-1" aria-labelledby="bt-key-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form id="bt-key-form">
      <div class="modal-header"><h5 class="modal-title" id="bt-key-modal-title">Học / sửa nút Bluetooth</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
      <div class="modal-body">
        <p class="small text-muted" id="bt-key-modal-device"></p>
        <p class="small text-muted">Nhấn nút trên thiết bị trong 15 giây. Chỉ học được nút gửi về Pi. Trong lúc học, VBot tạm ngừng hành động nút đã gán; nút trên loa vẫn có thể có tác dụng riêng. Chọn kịch bản đã lưu để thực hiện nhiều bước. VBot cần chạy để thực hiện hành động.</p>
        <div id="bt-key-modal-status" class="small mb-3" role="status"></div>
        <div class="d-flex justify-content-between align-items-center mb-3"><label class="mb-0">Kích hoạt nút</label><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input border-success" id="bt-key-enabled" checked></div></div>
        <div class="mb-3"><label class="form-label" for="bt-key-name">Tên nút</label><input class="form-control border-success" id="bt-key-name" maxlength="80" required></div>
        <div class="mb-3"><label class="form-label" for="bt-key-action">Chức năng</label><select class="form-select border-success" id="bt-key-action"></select></div>
        <div class="mb-3"><label class="form-label" for="bt-key-code">Mã nút</label><input class="form-control border-danger" id="bt-key-code" readonly placeholder="Đang chờ nhận mã nút"></div>
      </div>
      <div class="modal-footer d-flex flex-wrap justify-content-center align-items-center gap-2"><button type="button" class="btn btn-primary me-auto" id="bt-key-relearn"><i class="bi bi-arrow-repeat"></i> Học lại</button> <button class="btn btn-success" id="bt-key-save" type="submit" disabled>Lưu nút</button> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>

    </form>
  </div></div>
</div>
<?php include 'html_footer.php'; include 'html_js.php'; ?>
<script>window.vbotSourceCsrf=<?= json_encode($sourceCsrf,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script src="assets/js/bluetooth-source.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/js/bluetooth-keys.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
</body></html>
