<?php
include 'Configuration.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))) {
    header('Location: Login.php'); exit;
}
if (empty($_SESSION['vbot_csrf_token']) || !is_string($_SESSION['vbot_csrf_token']) || strlen($_SESSION['vbot_csrf_token'])<64) $_SESSION['vbot_csrf_token']=bin2hex(random_bytes(32));
$wolCsrf=$_SESSION['vbot_csrf_token'];
?>
<!DOCTYPE html><html lang="vi">
<?php include 'html_head.php'; session_write_close(); ?>
<body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main">
  <div class="pagetitle"><h1>Wake-on-LAN — Bật máy tính</h1></div>
  <section class="section"><div class="card"><div class="card-body pt-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <button id="wol-add" class="btn btn-primary" type="button">Thêm máy tính mới</button>
      <a href="Config.php#wakeOnLanConfiguration" class="small">Bật/tắt Wake-on-LAN trong cấu hình VBot</a>
    </div>
    <div id="wol-settings" class="border rounded p-3 mt-3" aria-labelledby="wol-settings-title" hidden>
      <h2 id="wol-settings-title" class="fs-5">Cấu hình máy tính</h2>
      <div id="wol-modal-notice" class="alert alert-info" role="status">Đang tải…</div>
      <p>Với tên máy tính <b>máy tính phòng làm việc</b>, nói “bật máy tính phòng làm việc”. Câu lệnh khác nhập mỗi dòng một câu hoàn chỉnh, ví dụ “đến giờ làm việc”; nói đúng câu đó để bật máy, không cần thêm “bật”. Lưu có hiệu lực ngay, không cần khởi động lại VBot.</p>
      <div id="wol-devices"></div>
      <div class="d-flex flex-wrap gap-2"><button id="wol-reload" class="btn btn-outline-secondary" type="button">Tải lại</button><button id="wol-save" class="btn btn-success" type="button">Lưu cấu hình</button><button id="wol-close-settings" class="btn btn-secondary" type="button">Thu gọn</button></div>
    </div>
    <div id="wol-notice" class="alert alert-info mt-3" role="status">Đang tải…</div>
    <div class="pt-3">
      <div id="wol-control"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Tên máy tính</th><th>MAC</th><th>Broadcast IPv4</th><th>UDP</th><th>Câu lệnh khác</th><th>Trạng thái</th><th>Hành động</th></tr></thead><tbody id="wol-buttons"></tbody></table></div>
        <p class="small text-muted mt-3">Nút chỉ gửi gói đánh thức, chưa xác nhận máy đã bật. Máy tính cần bật Wake-on-LAN trong BIOS/UEFI và card mạng; nên dùng Ethernet. VBot và máy tính cần có đường mạng cho phép gói broadcast.</p>
      </div>
    </div>
  </div></div>
  <div class="card"><div class="card-body pt-3" id="wol-backup-section">
    <h2 class="h5">Nhập, tải xuống, xem dữ liệu JSON, sao lưu và khôi phục</h2>
    <p class="small text-muted">Dữ liệu: resource/wake_on_lan/devices.json. Giữ 10 bản sao lưu trong html/Backup_Upgrade/Backup_Wake_On_LAN. Tự sao lưu trước khi lưu hoặc khôi phục. Không thay đổi bật/tắt Wake-on-LAN trong Config.json.</p>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button type="button" class="btn btn-outline-primary" id="wol-json-view">Xem JSON đã lưu</button>
      <button type="button" class="btn btn-primary" id="wol-json-download">Tải xuống JSON</button>
      <button type="button" class="btn btn-success" id="wol-backup-create">Tạo bản sao lưu</button>
      <button type="button" class="btn btn-outline-secondary" id="wol-backup-refresh">Tải lại danh sách</button>
    </div>
    <label for="wol-backup-select" class="form-label">Bản sao lưu trên thiết bị</label>
    <select id="wol-backup-select" class="form-select mb-3"></select>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button type="button" class="btn btn-outline-primary" id="wol-backup-view">Xem JSON</button>
      <button type="button" class="btn btn-primary" id="wol-backup-download">Tải xuống</button>
      <button type="button" class="btn btn-warning" id="wol-backup-restore">Khôi phục bản này</button>
    </div>
    <label for="wol-import-file" class="form-label">Tải lên JSON để thay thế / khôi phục (tối đa 1 MB)</label>
    <input type="file" id="wol-import-file" accept=".json,application/json" class="form-control mb-3">
    <button type="button" class="btn btn-success" id="wol-import">Tải lên và khôi phục</button>
  </div></div></section>
  <div class="modal fade" id="wol-json-modal" tabindex="-1" aria-labelledby="wol-json-title" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="wol-json-title">Xem JSON</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
    <div class="modal-body"><pre id="wol-json-content" style="white-space:pre-wrap;overflow-wrap:anywhere"></pre></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
  </div></div></div>
</main>
<?php include 'html_footer.php'; include 'html_js.php'; ?>
<script>window.vbotWolCsrf=<?= json_encode($wolCsrf, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script src="assets/js/wake-on-lan.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
</body></html>
