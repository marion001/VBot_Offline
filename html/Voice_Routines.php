<?php
include 'Configuration.php';
require_once __DIR__.'/includes/VoiceRoutines.php';
if (!empty($Config['contact_info']['user_login']['active'])) {
    if (session_status()!==PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_login'])) { header('Location: Login.php'); exit; }
}
?>
<!DOCTYPE html><html lang="vi"><?php include 'html_head.php'; ?><body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<style>
.routine-toolbar{position:sticky;top:64px;z-index:900;padding:.75rem;margin-bottom:1rem;border:1px solid rgba(13,110,253,.22);border-radius:.75rem;background:rgba(244,248,255,.96);box-shadow:0 .25rem .75rem rgba(18,38,63,.08);backdrop-filter:blur(6px)}
@media(max-width:991.98px){.routine-toolbar{top:60px}}
#routineCards [hidden]{display:none!important}
#routineCards [data-rfield="name"]{scroll-margin-top:200px}
#routineCards h2.h5>.accordion-button{font-size:inherit;font-weight:inherit;line-height:inherit}
#routineCards .card-title .routine-heading{font-size:1.25rem;font-weight:500;line-height:1.2;color:inherit}
#routineCards .card-title .routine-enabled-status{color:#fff;font-size:.75rem;font-weight:600;white-space:nowrap}
</style>
<main id="main" class="main">
  <div class="pagetitle"><h1>Kịch bản nhiều bước bằng giọng nói</h1></div>
  <section class="section">
    <div class="card"><div class="card-body pt-3">
      <p>Tạo một câu gọi để VBot thực hiện lần lượt nhiều bước. Ví dụ: “Tôi đi ngủ” → tắt đèn → dừng nhạc → giảm âm lượng → đọc lời chúc.</p>
      <p class="small">Kích hoạt khi VBot khởi động: <strong><?= ($Config['voice_routines']['active']??true)===true?'Bật':'Tắt' ?></strong>. Đổi mục <code>voice_routines.active</code> trong Config.json hoặc <a href="Config.php#voice-routines-setting">Cấu hình VBot</a>, sau đó khởi động lại VBot.</p>
      <p class="small text-muted">Ngưỡng khớp câu gọi: <?= number_format((float)($Config['voice_routines']['minimum_threshold']??0.90),2) ?> (đổi trong Config.php). So sánh toàn câu; có thể gọi “chạy kịch bản” + tên. Lưu kịch bản có hiệu lực ngay cho lần gọi tiếp theo; đổi ngưỡng cần khởi động lại VBot. Tối đa 50 kịch bản, 30 bước mỗi kịch bản; mỗi bước chờ tối đa 120 giây, tổng chờ tối đa 300 giây. Một lượt chạy tối đa 10 phút.</p>
      <div id="routineNotice" class="alert alert-info mt-3 mb-0" role="status">Đang tải cấu hình…</div>
    </div></div>
    <div class="routine-toolbar" role="toolbar" aria-label="Tìm kiếm và quản lý kịch bản">
      <div class="input-group mb-2"><span class="input-group-text border-primary"><i class="bi bi-search text-primary"></i></span><input id="routineSearch" type="search" class="form-control border-primary" placeholder="Tìm tên kịch bản, câu gọi, Entity ID hoặc nội dung bước..." aria-label="Tìm kịch bản" autocomplete="off"><button id="routineSearchClear" type="button" class="btn btn-outline-secondary" aria-label="Xóa tìm kiếm"><i class="bi bi-x-lg"></i></button></div>
      <div class="d-flex flex-wrap gap-2 justify-content-center">
        <button id="routineAdd" type="button" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Thêm kịch bản</button>
        <button id="routineSave" type="button" class="btn btn-success"><i class="bi bi-floppy"></i> Lưu toàn bộ</button>
        <button id="routineReload" type="button" class="btn btn-outline-secondary">Tải lại</button>
        <button id="routineExport" type="button" class="btn btn-outline-secondary">Xuất bản đang sửa</button>
        <button id="routineExpandAll" type="button" class="btn btn-outline-primary">Mở tất cả</button>
        <button id="routineCollapseAll" type="button" class="btn btn-outline-secondary">Thu gọn tất cả</button>
      </div>
      <div id="routineSearchEmpty" class="alert alert-info mt-2 mb-0" role="status" hidden>Không tìm thấy kịch bản phù hợp.</div>
    </div>
    <div id="routineCards"></div>
    <div class="card"><div class="card-body pt-3">
      <h2 class="h5">Trạng thái chạy kịch bản</h2>
      <p class="small text-muted">Chạy thử thực hiện các thao tác thật trên loa và thiết bị đã cấu hình. Hãy lưu trước khi chạy. Dừng kịch bản ngăn các bước tiếp theo; các bước đã thực hiện vẫn có hiệu lực, thao tác đang gửi có thể hoàn tất.</p>
      <div class="d-flex gap-2 mb-3">
        <button id="routineStatusRefresh" class="btn btn-outline-primary" type="button">Xem trạng thái</button>
        <button id="routineCancel" class="btn btn-warning" type="button">Dừng kịch bản đang chạy</button>
      </div>
      <div id="routineRuntime" role="status">Chưa lấy trạng thái.</div>
    </div></div>
    <div class="card" id="routineBackupSection"><div class="card-body pt-3">
      <h2 class="h5">Nhập, tải xuống, xem dữ liệu JSON, sao lưu và khôi phục</h2>
      <p class="small text-muted">Dữ liệu đã lưu gồm các kịch bản và bước thực hiện. Tệp JSON tối đa 1 MB. Trước khi nhập hoặc khôi phục, hệ thống tự sao lưu dữ liệu hiện tại; giữ tối đa 10 bản trong <code>resource/voice_routines_backups</code>.</p>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-outline-primary routine-backup-control" id="routineJsonView">Xem JSON đã lưu</button>
        <button type="button" class="btn btn-primary routine-backup-control" id="routineJsonDownload">Tải xuống JSON đã lưu</button>
        <button type="button" class="btn btn-success routine-backup-control" id="routineBackupCreate">Tạo bản sao lưu</button>
        <button type="button" class="btn btn-outline-secondary routine-backup-control" id="routineBackupRefresh">Tải lại danh sách sao lưu</button>
      </div>
      <div id="routineBackupNotice" class="alert alert-info" role="status">Đang tải danh sách sao lưu…</div>
      <label for="routineBackupSelect" class="form-label">Bản sao lưu trên thiết bị</label>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <select id="routineBackupSelect" class="form-select routine-backup-control" style="max-width:600px"><option value="">Chọn bản sao lưu…</option></select>
        <button type="button" class="btn btn-outline-primary routine-backup-control" id="routineBackupView">Xem JSON</button>
        <button type="button" class="btn btn-primary routine-backup-control" id="routineBackupDownload">Tải xuống</button>
        <button type="button" class="btn btn-warning routine-backup-control" id="routineBackupRestore">Khôi phục bản này</button>
      </div>
      <pre id="routineBackupJson" class="d-none bg-light border rounded p-3" style="max-height:480px;overflow:auto;white-space:pre-wrap;overflow-wrap:anywhere" tabindex="0"></pre>
      <hr>
      <div class="row g-3">
        <div class="col-md-8"><label for="routineImportFile" class="form-label">Tải lên tệp JSON để nhập / khôi phục</label><input id="routineImportFile" type="file" accept=".json,application/json" class="form-control routine-backup-control"></div>
        <div class="col-md-4"><label for="routineImportMode" class="form-label">Cách nhập</label><select id="routineImportMode" class="form-select routine-backup-control"><option value="merge">Gộp theo ID (ID trùng được thay thế)</option><option value="replace">Thay thế toàn bộ / khôi phục</option></select></div>
        <div class="col-12"><button type="button" class="btn btn-success routine-backup-control" id="routineImportUpload">Tải lên và nhập / khôi phục</button></div>
        <div class="col-12"><label for="routineImportText" class="form-label">Hoặc dán nội dung JSON</label><textarea id="routineImportText" class="form-control font-monospace routine-backup-control" rows="6" placeholder='{"version":1,"routines":[]}'></textarea></div>
        <div class="col-12"><button type="button" class="btn btn-outline-success routine-backup-control" id="routineImportPaste">Nhập JSON đã dán</button></div>
      </div>
    </div></div>
  </section>
</main>
<?php include 'html_footer.php'; include 'html_js.php'; ?>
<script>
window.voiceRoutineOptions=<?= json_encode([
    'endpoint'=>'includes/php_ajax/Voice_Routines_Ajax.php',
    'api'=>rtrim($URL_API_VBOT,'/').'/voice-routines',
    'apiKey'=>!empty($Config['api']['auth']['active'])?(string)($Config['api']['auth']['api_key']??''):'',
    'actions'=>vbotRoutineActions($Config),
    'hassActions'=>vbotRoutineHassActions(),
    'hassCache'=>'includes/php_ajax/Voice_Routines_Hass.php',
    'localMedia'=>'includes/php_ajax/Voice_Routines_Local.php',
],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
</script>
<script src="assets/js/voice-routines.js?v=13"></script>
<script src="assets/js/voice-routines-backup.js?v=1"></script>
</body></html>
