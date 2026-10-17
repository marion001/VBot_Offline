<div class="card" id="lanBackupSection"><div class="card-body pt-3">
<h2 class="h5">Nhập, tải xuống, xem dữ liệu JSON, sao lưu và khôi phục</h2>
<p class="small text-muted">Quản lý danh sách loa và cách nhận cuộc gọi trong <code>resource/call_lan/Devices.json</code>. Không thay đổi Config.json hoặc khóa chung. JSON tối đa 40 KB, tối đa 50 loa. Trước khi nhập/khôi phục, hệ thống tự sao lưu dữ liệu hiện tại; giữ 10 bản trong <code>html/Backup_Upgrade/Backup_LAN_Calls</code>. Khởi động lại VBot sau khi thay đổi danh sách.</p>
<div class="d-flex flex-wrap gap-2 mb-3">
<button type="button" class="btn btn-outline-primary lan-backup-control" id="lanJsonView">Xem JSON đã lưu</button>
<button type="button" class="btn btn-primary lan-backup-control" id="lanJsonDownload">Tải xuống JSON đã lưu</button>
<button type="button" class="btn btn-success lan-backup-control" id="lanBackupCreate">Tạo bản sao lưu</button>
<button type="button" class="btn btn-outline-secondary lan-backup-control" id="lanBackupRefresh">Tải lại danh sách sao lưu</button>
</div>
<div id="lanBackupNotice" class="alert alert-info" role="status">Đang tải danh sách sao lưu…</div>
<label for="lanBackupSelect" class="form-label">Bản sao lưu trên thiết bị</label>
<div class="d-flex flex-wrap gap-2 mb-3">
<select id="lanBackupSelect" class="form-select lan-backup-control border-success" style="max-width:600px"><option value="">Chọn bản sao lưu…</option></select>
<button type="button" class="btn btn-outline-primary lan-backup-control" id="lanBackupView">Xem JSON</button>
<button type="button" class="btn btn-primary lan-backup-control" id="lanBackupDownload">Tải xuống</button>
<button type="button" class="btn btn-warning lan-backup-control" id="lanBackupRestore">Khôi phục bản này</button>
</div>
<hr><div class="row g-3">
<div class="col-md-8"><label for="lanImportFile" class="form-label">Tải lên tệp JSON để nhập / khôi phục</label><input id="lanImportFile" type="file" accept=".json,application/json" class="form-control lan-backup-control border-success"></div>
<div class="col-md-4"><label for="lanImportMode" class="form-label">Cách nhập</label><select id="lanImportMode" class="form-select lan-backup-control border-success"><option value="merge">Gộp theo ID (ID trùng được thay thế)</option><option value="replace">Thay thế toàn bộ / khôi phục</option></select></div>
<div class="col-12"><button type="button" class="btn btn-success lan-backup-control" id="lanImportUpload">Tải lên và nhập / khôi phục</button></div>
<div class="col-12"><label for="lanImportText" class="form-label">Hoặc dán nội dung JSON</label><textarea id="lanImportText" class="form-control font-monospace lan-backup-control border-success" rows="6" placeholder='[{"id":"id-loa","name":"Phòng ngủ","host":"192.168.14.132","auto_accept":false}]'></textarea></div>
<div class="col-12"><button type="button" class="btn btn-outline-success lan-backup-control" id="lanImportPaste">Nhập JSON đã dán</button></div>
</div></div></div>

<div class="modal fade" id="lanBackupJsonModal" tabindex="-1" aria-labelledby="lanBackupJsonTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="lanBackupJsonTitle">Xem dữ liệu JSON</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <pre id="lanBackupJson" class="bg-light border rounded p-3 mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere" tabindex="0"></pre>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
    </div>
  </div>
</div>
