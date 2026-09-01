<?php
include 'Configuration.php';
if (!empty($Config['contact_info']['user_login']['active'])) {
    session_start();
    if (!isset($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200)) {
        session_unset(); session_destroy(); header('Location: Login.php'); exit;
    }
    $_SESSION['user_login']['login_time'] = time();
}
?>
<!DOCTYPE html><html lang="vi">
<?php include 'html_head.php'; ?>
<body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main">
  <div class="pagetitle"><h1>Cloudflare Tunnel</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li><li class="breadcrumb-item active">Quản lý Cloudflare Tunnel</li></ol></nav></div>
  <section class="section">
    <div class="alert alert-info d-flex flex-wrap gap-2 align-items-start"><i class="bi bi-info-circle-fill"></i><div class="flex-grow-1"><strong>Quick Tunnel</strong> tạo địa chỉ <code>trycloudflare.com</code> tạm thời, không cần tài khoản. <strong>Domain riêng</strong> sử dụng Named Tunnel, credentials JSON và hostname đã định tuyến trên Cloudflare.</div><a class="btn btn-sm btn-outline-primary" href="FAQ.php#accordion_button_Cloudflare_Tunnel"><i class="bi bi-book"></i> Hướng dẫn cài đặt</a></div>
    <div class="alert alert-warning d-flex gap-2 align-items-start"><i class="bi bi-exclamation-triangle-fill mt-1"></i><div>Để sử dụng Cloudflare Tunnel truy cập WebUI từ Internet, cần bật: <strong>Cấu Hình Config → Cấu Hình Web Interface/Cloudflare Tunnel (Giao Diện) → Cho Phép Truy Cập Bên Ngoài Internet</strong>.</div></div>
    <div class="card"><div class="card-body pt-3"><div class="d-flex flex-wrap align-items-center gap-2"><strong>Cloudflared:</strong><span id="cf-install-status" class="badge bg-secondary">Chưa kiểm tra</span><span id="cf-service-status" class="badge bg-secondary">Service: Chưa kiểm tra</span><span id="cf-status-note" class="text-muted">Nhấn “Kiểm tra hệ thống” để lấy trạng thái.</span><a id="cf-install-guide" class="btn btn-sm btn-warning ms-md-auto d-none" href="FAQ.php#accordion_button_Cloudflare_Tunnel"><i class="bi bi-tools"></i> Xem hướng dẫn cài Cloudflared</a></div></div></div>
    <div class="card"><div class="card-body pt-3">
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <button id="cf-add" class="btn btn-primary" type="button"><i class="bi bi-plus-circle"></i> Thêm Tunnel</button>
        <button id="cf-refresh" class="btn btn-outline-primary" type="button"><i class="bi bi-arrow-clockwise"></i> Làm mới</button>
        <button id="cf-status" class="btn btn-outline-success" type="button"><i class="bi bi-activity"></i> Kiểm tra hệ thống</button>
        <button id="cf-apply" class="btn btn-success" type="button" disabled><i class="bi bi-check2-circle"></i> Áp dụng thay đổi</button>
        <button id="cf-stop" class="btn btn-outline-danger ms-md-auto" type="button"><i class="bi bi-stop-circle"></i> Dừng Tunnel</button>
      </div><div id="cf-message" class="alert mt-3 mb-0 d-none"></div>
    </div></div>
    <div class="card"><div class="card-body pt-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h5 class="card-title p-0 m-0">Danh sách hồ sơ</h5><input id="cf-search" type="search" class="form-control" style="max-width:320px" placeholder="Tìm tên, domain hoặc URL..."></div>
      <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Hồ sơ</th><th>Chế độ</th><th>Đích nội bộ</th><th>Domain</th><th>URL truy cập</th><th>API VBot</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead><tbody id="cf-list"><tr><td colspan="8" class="text-center text-muted py-4">Đang tải...</td></tr></tbody></table></div>
    </div></div>
    <div class="card"><div class="card-body pt-3"><h5 class="card-title">Kết quả kiểm tra</h5><pre id="cf-output" class="border rounded bg-light p-3 mb-0" style="min-height:100px;max-height:420px;overflow:auto;white-space:pre-wrap">Chưa có dữ liệu.</pre></div></div>
  </section>
</main>

<div class="modal fade" id="cf-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form id="cf-form" class="modal-content">
  <div class="modal-header"><h5 id="cf-modal-title" class="modal-title">Thêm Cloudflare Tunnel</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
  <div class="modal-body"><input id="cf-id" type="hidden"><div class="row g-3">
    <div class="col-md-7"><label for="cf-name" class="form-label">Tên hồ sơ</label><input id="cf-name" class="form-control border-primary" maxlength="80" required placeholder="Ví dụ: VBot WebUI"></div>
    <div class="col-md-5"><label for="cf-mode" class="form-label">Loại Tunnel</label><select id="cf-mode" class="form-select border-primary"><option value="quick">Quick Tunnel (Domain Ngẫu Nhiên của Cloudflare)</option><option value="domain">Domain riêng, Tên miền riêng</option></select></div>
    <div class="col-12"><label for="cf-url" class="form-label">URL dịch vụ nội bộ</label><input id="cf-url" type="url" class="form-control border-success" required value="http://127.0.0.1:80"><div class="form-text">Ví dụ WebUI mặc định hệ thống: http://127.0.0.1:80</div></div>
  </div><div id="cf-domain-fields" class="border rounded p-3 mt-3 d-none">
    <div class="alert alert-warning mb-3">
      <div class="d-flex gap-2 align-items-start"><i class="bi bi-exclamation-triangle-fill mt-1"></i><div class="flex-grow-1">
        <strong>Chuẩn bị Domain riêng trước khi lưu</strong>
        <p class="mb-2">Các thao tác dưới đây chỉ cần thực hiện một lần qua SSH để Cloudflare cấp chứng thực và credentials:</p>
        <ol class="mb-2 ps-3">
          <li><code>cloudflared tunnel login</code> — đăng nhập và chọn domain (Nếu đã đăng nhập trước đó đã tồn tại cert.pem thì bỏ qua lệnh này)</li>
          <li><code>cloudflared tunnel create TEN_TUNNEL</code> — tạo Named Tunnel.</li>
          <li><code>cloudflared tunnel route dns TEN_TUNNEL vbot.example.com</code> — gắn hostname vào tunnel.</li>
        </ol>
        <div class="small">Tìm file credentials bằng: <code>ls -1 /home/pi/.cloudflared/*.json</code>. Sau đó nhập tên/UUID tunnel, hostname và đường dẫn JSON vào các ô bên dưới.</div>
        <a class="btn btn-sm btn-outline-dark mt-2" href="FAQ.php#accordion_button_Cloudflare_Tunnel" target="_blank" rel="noopener noreferrer"><i class="bi bi-book"></i> Xem hướng dẫn chi tiết</a>
      </div></div>
    </div>
    <div class="row g-3">
    <div class="col-md-6"><label for="cf-hostname" class="form-label">Domain / Hostname</label><input id="cf-hostname" class="form-control border-success" placeholder="vbot.example.com"></div>
    <div class="col-md-6"><label for="cf-tunnel" class="form-label">Tên, UUID Tunnel</label><input id="cf-tunnel" class="form-control border-success" placeholder="c28b8888-9680-401e-a000-21b7f000000c"></div>
    <div class="col-12"><label for="cf-credentials" class="form-label">Credentials JSON</label><input id="cf-credentials" class="form-control border-success" placeholder="/home/pi/.cloudflared/UUID.json"><div class="form-text">File phải tồn tại trên Raspberry Pi; WebUI không đọc hoặc trả nội dung file này.</div></div>
    <div class="col-12"><div class="border border-info rounded p-3">
      <label for="cf-auth-type" class="form-label fw-semibold"><i class="bi bi-shield-lock"></i> Xác thực Cloudflare API</label><select id="cf-auth-type" class="form-select border-success"><option value="none">Chưa cấu hình</option><option value="global">Global API Key (khuyến nghị)</option><option value="token">API Token</option></select>
      <div class="form-text mt-2">Thông tin xác thực này chỉ được sử dụng khi bạn chọn xóa dữ liệu Tunnel/DNS đã tạo trên VBot và trên <a href="https://dash.cloudflare.com" target="_blank" rel="noopener noreferrer">dash.cloudflare.com</a>.</div>
      <div id="cf-token-fields" class="mt-3 d-none"><label for="cf-api-token" class="form-label">Cloudflare API Token</label><div class="input-group"><input id="cf-api-token" type="password" class="form-control" autocomplete="new-password" placeholder="Để trống để giữ token đang lưu"><button id="cf-show-api-token" class="btn btn-outline-secondary" type="button" title="Xem/ẩn API Token" aria-label="Xem hoặc ẩn API Token"><i class="bi bi-eye"></i></button></div><div id="cf-api-token-note" class="form-text">Token cần quyền <code>Zone / Zone / Read</code> và <code>Zone / DNS / Edit</code>.</div></div>
      <div id="cf-global-fields" class="mt-3 d-none"><div class="row g-3"><div class="col-md-6"><label for="cf-api-email" class="form-label">Email tài khoản Cloudflare</label><input id="cf-api-email" type="email" class="form-control" autocomplete="email"></div><div class="col-md-6"><label for="cf-global-api-key" class="form-label">Global API Key</label><div class="input-group"><input id="cf-global-api-key" type="password" class="form-control" autocomplete="new-password" placeholder="Để trống để giữ khóa đang lưu"><button id="cf-show-global-key" class="btn btn-outline-secondary" type="button" title="Xem/ẩn Global API Key" aria-label="Xem hoặc ẩn Global API Key"><i class="bi bi-eye"></i></button></div></div></div><div id="cf-global-key-note" class="form-text">Khuyến nghị vì thiết lập nhanh, không cần tạo quyền riêng. Lưu ý Global API Key có toàn quyền tài khoản Cloudflare.</div></div>
    </div></div>
  </div></div><div class="form-check form-switch mt-3"><label for="cf-auto" class="form-check-label">Tự khởi động cùng hệ thống khi kích hoạt</label><input id="cf-auto" class="form-check-input border-success" type="checkbox"></div></div>
  <div class="modal-footer"><button class="btn btn-danger" type="button" data-bs-dismiss="modal">Đóng</button><button id="cf-save" class="btn btn-success" type="submit"><i class="bi bi-save"></i> Lưu hồ sơ</button></div>
</form></div></div>

<div class="modal fade" id="cf-delete-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form id="cf-delete-form" class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Xóa Cloudflare Tunnel</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
  <div class="modal-body">
    <p>Bạn muốn xóa hồ sơ <strong id="cf-delete-name"></strong> theo cách nào?</p>
    <div class="form-check mb-2"><input id="cf-delete-local" class="form-check-input" type="radio" name="cf-delete-mode" value="local" checked><label class="form-check-label" for="cf-delete-local">Chỉ xóa hồ sơ VBot</label></div>
    <div class="form-check"><input id="cf-delete-all" class="form-check-input" type="radio" name="cf-delete-mode" value="all"><label class="form-check-label text-success" for="cf-delete-all">Xóa toàn bộ Tunnel + DNS + credentials (Xóa dữ liệu trên loa và trên máy chủ của Cloudflare)</label></div>
    <div id="cf-delete-danger" class="border border-danger rounded p-3 mt-3 d-none">
      <div class="alert alert-danger py-2">Thao tác này không thể hoàn tác. Không xóa <code>cert.pem</code> vì chứng thực này có thể dùng chung.</div>
      <label for="cf-delete-auth-type" class="form-label">Xác thực Cloudflare API</label><select id="cf-delete-auth-type" class="form-select"><option value="saved">Dùng thông tin đã lưu</option><option value="token">Nhập API Token</option><option value="global">Nhập Global API Key</option></select>
      <div id="cf-delete-token-fields" class="mt-3"><label for="cf-delete-token" class="form-label">Cloudflare API Token</label><input id="cf-delete-token" type="password" class="form-control" autocomplete="new-password"></div>
      <div id="cf-delete-global-fields" class="mt-3 d-none"><label for="cf-delete-email" class="form-label">Email tài khoản Cloudflare</label><input id="cf-delete-email" type="email" class="form-control" autocomplete="email"><label for="cf-delete-global-key" class="form-label mt-2">Global API Key</label><input id="cf-delete-global-key" type="password" class="form-control" autocomplete="new-password"></div><div id="cf-delete-token-note" class="form-text mt-2"></div>
      <label for="cf-delete-confirm" class="form-label mt-3">Nhập lại tên hồ sơ để xác nhận</label><input id="cf-delete-confirm" class="form-control" autocomplete="off">
    </div>
  </div>
  <div class="modal-footer"><button id="cf-delete-submit" type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Xóa</button> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
</form></div></div>

<?php include 'html_footer.php'; ?><a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a><?php include 'html_js.php'; ?>
<script>
(() => {
  'use strict';
  const endpoint = 'includes/php_ajax/Cloudflare_Tunnel_Ajax.php';
  const state = {profiles: [], active: '', pendingActive: '', selectionReady: false, dirty: false, publicUrl: '', serviceActive: false, installed: null, runtimeChecking: true};
  const el = id => document.getElementById(id);
  const modal = new bootstrap.Modal(el('cf-modal'));
  const deleteModal = new bootstrap.Modal(el('cf-delete-modal'));
  let deletingProfile = null;
  function notify(message, type = 'success') { const box = el('cf-message'); box.className = 'alert mt-3 mb-0 alert-' + type; box.textContent = message; }
  function renderRuntimeStatus() {
    const installed=el('cf-install-status'), service=el('cf-service-status'), note=el('cf-status-note'), guide=el('cf-install-guide');
    if(state.runtimeChecking){
      const loadingBadge=function(target,label){const spinner=document.createElement('span');spinner.className='spinner-border spinner-border-sm me-1';spinner.setAttribute('aria-hidden','true');target.className='badge bg-info text-dark';target.replaceChildren(spinner,document.createTextNode(label));};
      loadingBadge(installed,'Đang kiểm tra cài đặt...'); loadingBadge(service,'Đang kiểm tra service...'); note.textContent='Đang lấy trạng thái Cloudflare Tunnel và URL công khai...'; guide.classList.add('d-none'); return;
    }
    installed.className='badge '+(state.installed===null?'bg-secondary':state.installed?'bg-success':'bg-danger'); installed.textContent=state.installed===null?'Chưa kiểm tra':state.installed?'Đã cài đặt':'Chưa cài đặt';
    service.className='badge '+(state.serviceActive?'bg-success':'bg-secondary'); service.textContent=state.serviceActive?'Service: Đang chạy':'Service: Không chạy';
    note.textContent=state.installed===false?'Cần cài cloudflared trước khi sử dụng.':state.serviceActive?'Cloudflare Tunnel đang hoạt động.':'Cloudflared đã cài nhưng tunnel hiện không hoạt động.';
    guide.classList.toggle('d-none',state.installed!==false);
  }
  function actualActiveProfile() { return state.serviceActive ? state.active : ''; }
  function updateApplyButton() {
    const button=el('cf-apply'); button.disabled=!state.dirty; button.classList.toggle('btn-success',state.dirty); button.classList.toggle('btn-outline-secondary',!state.dirty);
  }
  function resetPendingSelection() { state.pendingActive=actualActiveProfile(); state.selectionReady=true; state.dirty=false; updateApplyButton(); }
  function selectProfile(id, enabled) { state.pendingActive=enabled?id:(state.pendingActive===id?'':state.pendingActive); state.dirty=state.pendingActive!==actualActiveProfile(); render(); }
  async function request(action, values = {}, timeout = 30000) {
    const body = new FormData(); body.append('action', action); Object.entries(values).forEach(([key, value]) => body.append(key, value));
    const response = await vbotFetchWithTimeout(endpoint, {method:'POST', credentials:'same-origin', body, headers:{'X-CSRF-Token':window.VBOT_CSRF_TOKEN || ''}}, timeout);
    const text = await response.text(); let result;
    try { result = JSON.parse(text); } catch (_) { throw new Error('Máy chủ trả về dữ liệu không hợp lệ (HTTP ' + response.status + ').'); }
    if (!response.ok || !result.success) throw new Error(result.message || ('HTTP ' + response.status)); return result;
  }
  function actionButton(icon, title, className, callback) { const button=document.createElement('button'); button.type='button'; button.className=className; button.title=title; const image=document.createElement('i'); image.className=icon; button.appendChild(image); button.addEventListener('click', callback); return button; }
  function publicUrl(profile) {
    if (profile.mode === 'domain' && profile.hostname) return 'https://' + profile.hostname;
    if (profile.mode === 'quick' && profile.id === state.active && state.serviceActive) return state.publicUrl;
    return '';
  }
  function appendUrlCell(row, url, emptyText, checking = false) {
    const cell = row.insertCell();
    if (!url && checking) { cell.className='text-muted text-nowrap'; const spinner=document.createElement('span'); spinner.className='spinner-border spinner-border-sm text-primary me-1'; spinner.setAttribute('role','status'); spinner.setAttribute('aria-hidden','true'); cell.append(spinner,document.createTextNode('Đang kiểm tra...')); return; }
    if (!url) { cell.className = 'text-muted'; cell.textContent = emptyText; return; }
    const link = document.createElement('a'); link.href = url; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.className = 'text-break'; link.textContent = url; cell.appendChild(link);
  }
  function render() {
    const body=el('cf-list'); body.replaceChildren(); const query=el('cf-search').value.trim().toLocaleLowerCase('vi');
    const profiles=state.profiles.filter(profile => JSON.stringify(profile).toLocaleLowerCase('vi').includes(query));
    if (!profiles.length) { const cell=body.insertRow().insertCell(); cell.colSpan=8; cell.className='text-center text-muted py-4'; cell.textContent=query?'Không tìm thấy hồ sơ phù hợp.':'Chưa có hồ sơ Cloudflare Tunnel.'; return; }
    profiles.forEach(profile => {
      const row=body.insertRow(); const name=row.insertCell(); name.className='fw-semibold'; name.textContent=profile.name;
      const mode=row.insertCell(), badge=document.createElement('span'); badge.className='badge '+(profile.mode==='quick'?'bg-info text-dark':'bg-primary'); badge.textContent=profile.mode==='quick'?'Quick Tunnel':'Domain'; mode.appendChild(badge);
      const local=row.insertCell(), code=document.createElement('code'); code.textContent=profile.local_url; local.appendChild(code); row.insertCell().textContent=profile.hostname || 'Tự động khi chạy';
      const profileUrl=publicUrl(profile), checking=profile.mode==='quick'&&state.runtimeChecking; appendUrlCell(row,profileUrl,profile.mode==='quick'?'Chưa lấy được URL':'Chưa có',checking); appendUrlCell(row,profileUrl ? profileUrl.replace(/\/$/,'')+'/vbot_api_external/' : '',profile.mode==='quick'?'Chưa lấy được URL':'Chưa có',checking);
      const status=row.insertCell(), switchWrap=document.createElement('div'), switchInput=document.createElement('input'), switchLabel=document.createElement('label');
      const isSelected=state.pendingActive===profile.id, isRunning=state.active===profile.id&&state.serviceActive; switchWrap.className='form-check form-switch mb-0'; switchInput.className='form-check-input border-success'; switchInput.type='checkbox'; switchInput.role='switch'; switchInput.id='cf-profile-switch-'+profile.id; switchInput.checked=isSelected; switchInput.title=isSelected?'Bỏ chọn hồ sơ':'Chọn hồ sơ để áp dụng'; switchLabel.className='form-check-label text-nowrap'; switchLabel.htmlFor=switchInput.id; switchLabel.textContent=isRunning&&isSelected?'Đang bật':isSelected?'Chờ áp dụng':isRunning?'Sẽ tắt':'Đang tắt';
      switchInput.addEventListener('change',()=>selectProfile(profile.id,switchInput.checked)); switchWrap.append(switchInput,switchLabel); status.appendChild(switchWrap);
      const actions=row.insertCell(); actions.className='text-end text-nowrap'; const group=document.createElement('div'); group.className='btn-group btn-group-sm';
      group.append(actionButton('bi bi-check2-circle','Kiểm tra','btn btn-outline-success',()=>check(profile.id)),actionButton('bi bi-pencil','Sửa','btn btn-outline-primary',()=>openForm(profile)),actionButton('bi bi-trash','Xóa hồ sơ','btn btn-outline-danger',()=>removeProfile(profile))); actions.appendChild(group);
    });
    updateApplyButton();
  }
  function domainFields(){const domain=el('cf-mode').value==='domain';el('cf-domain-fields').classList.toggle('d-none',!domain);['cf-hostname','cf-tunnel','cf-credentials'].forEach(id=>el(id).required=domain);}
  function updateAuthFields(){const type=el('cf-auth-type').value;el('cf-token-fields').classList.toggle('d-none',type!=='token');el('cf-global-fields').classList.toggle('d-none',type!=='global');el('cf-api-email').required=type==='global';}
  function openForm(profile=null){el('cf-form').reset();el('cf-id').value='';el('cf-url').value='http://127.0.0.1:80';el('cf-api-token').type='password';el('cf-show-api-token').querySelector('i').className='bi bi-eye';el('cf-global-api-key').type='password';el('cf-show-global-key').querySelector('i').className='bi bi-eye';el('cf-api-token-note').textContent='Token cần quyền Zone / Zone / Read và Zone / DNS / Edit.';el('cf-global-key-note').textContent='Global API Key có toàn quyền tài khoản; chỉ sử dụng khi không thể tạo API Token giới hạn quyền.';el('cf-modal-title').textContent=profile?'Sửa Cloudflare Tunnel':'Thêm Cloudflare Tunnel';if(profile){el('cf-id').value=profile.id;el('cf-name').value=profile.name;el('cf-mode').value=profile.mode;el('cf-url').value=profile.local_url;el('cf-hostname').value=profile.hostname||'';el('cf-tunnel').value=profile.tunnel||'';el('cf-credentials').value=profile.credentials_file||'';el('cf-auto').checked=Boolean(profile.auto_start);el('cf-auth-type').value=profile.auth_type||'none';el('cf-api-email').value=profile.api_email||'';if(profile.api_token_set)el('cf-api-token-note').textContent='Đã lưu API Token. Nhấn biểu tượng con mắt để xem token hoặc để trống để giữ nguyên.';if(profile.global_api_key_set)el('cf-global-key-note').textContent='Đã lưu Global API Key. Nhấn biểu tượng con mắt để xem khóa hoặc để trống để giữ nguyên.';}domainFields();updateAuthFields();modal.show();setTimeout(()=>el('cf-name').focus(),250);}
  async function toggleApiToken(){const input=el('cf-api-token'),icon=el('cf-show-api-token').querySelector('i');if(input.type==='text'){input.type='password';icon.className='bi bi-eye';return;}if(input.value===''){const id=el('cf-id').value,profile=state.profiles.find(item=>item.id===id);if(!profile||!profile.api_token_set){notify('Chưa có API được cấu hình.','warning');return;}try{const result=await request('reveal_api_token',{id});input.value=result.key||'';}catch(error){notify(error.message,'danger');return;}}input.type='text';icon.className='bi bi-eye-slash';}
  async function toggleGlobalKey(){const input=el('cf-global-api-key'),icon=el('cf-show-global-key').querySelector('i');if(input.type==='text'){input.type='password';icon.className='bi bi-eye';return;}if(input.value===''){const id=el('cf-id').value,profile=state.profiles.find(item=>item.id===id);if(!profile||!profile.global_api_key_set){notify('Hồ sơ chưa lưu Global API Key.','warning');return;}try{const result=await request('reveal_global_key',{id});input.value=result.key||'';}catch(error){notify(error.message,'danger');return;}}input.type='text';icon.className='bi bi-eye-slash';}
  el('cf-modal').addEventListener('shown.bs.modal',()=>{const profile=state.profiles.find(item=>item.id===el('cf-id').value);if(!profile||!profile.global_api_key_set)el('cf-global-key-note').textContent='Khuyến nghị vì thiết lập nhanh, không cần tạo quyền riêng. Lưu ý Global API Key có toàn quyền tài khoản Cloudflare.';});
  async function loadProfiles(){try{const result=await request('list');state.profiles=result.data.profiles||[];state.active=result.data.active_profile||'';if(!state.selectionReady){state.pendingActive=state.active;state.selectionReady=true;}else if(state.pendingActive&&!state.profiles.some(profile=>profile.id===state.pendingActive)){state.pendingActive=actualActiveProfile();state.dirty=false;}render();}catch(error){notify(error.message,'danger');}}
  async function check(id){state.runtimeChecking=true;renderRuntimeStatus();render();try{loading('show');const result=await request('check',{id},45000);el('cf-output').textContent=result.message;if(result.data){state.profiles=result.data.profiles||state.profiles;state.active=result.data.active_profile||state.active;}if(result.runtime){state.publicUrl=result.runtime.public_url||'';state.serviceActive=Boolean(result.runtime.active);}state.dirty=state.pendingActive!==actualActiveProfile();notify('Kiểm tra hồ sơ thành công.');}catch(error){el('cf-output').textContent=error.message;notify(error.message,'danger');}finally{state.runtimeChecking=false;renderRuntimeStatus();render();loading('hide');}}
  async function activate(id,ask=true){if(ask&&!confirm('Bật hồ sơ này sẽ thay thế cấu hình tunnel đang chạy và khởi động lại cloudflared. Tiếp tục?')){render();return false;}try{loading('show');const result=await request('activate',{id},100000);notify(result.message);await loadProfiles();await systemStatus(false);resetPendingSelection();render();return true;}catch(error){notify(error.message,'danger');await systemStatus(false);resetPendingSelection();render();return false;}finally{loading('hide');}}
  async function stopTunnel(profile=null,ask=true){const label=profile?' "'+profile.name+'"':'';if(ask&&!confirm('Tắt Cloudflare Tunnel'+label+'?')){render();return false;}try{loading('show');const result=await request('stop',{},45000);notify(result.message);state.serviceActive=false;state.active='';state.publicUrl='';await loadProfiles();resetPendingSelection();renderRuntimeStatus();render();return true;}catch(error){notify(error.message,'danger');await systemStatus(false);resetPendingSelection();render();return false;}finally{loading('hide');}}
  async function applySelection(){if(!state.dirty)return;if(!confirm(state.pendingActive?'Áp dụng hồ sơ đã chọn và khởi động lại Cloudflare Tunnel?':'Không có hồ sơ nào được chọn. Áp dụng để dừng Cloudflare Tunnel?'))return;if(state.pendingActive)await activate(state.pendingActive,false);else await stopTunnel(null,false);}
  function updateDeleteForm(){const removeAll=el('cf-delete-all').checked,needsAuth=removeAll&&deletingProfile&&deletingProfile.mode==='domain',saved=deletingProfile&&(deletingProfile.api_token_set||deletingProfile.global_api_key_set);el('cf-delete-danger').classList.toggle('d-none',!removeAll);el('cf-delete-auth-type').disabled=!needsAuth;el('cf-delete-auth-type').querySelector('option[value="saved"]').disabled=!saved;if(needsAuth&&!saved&&el('cf-delete-auth-type').value==='saved')el('cf-delete-auth-type').value='token';const auth=el('cf-delete-auth-type').value;el('cf-delete-token-fields').classList.toggle('d-none',!needsAuth||auth!=='token');el('cf-delete-global-fields').classList.toggle('d-none',!needsAuth||auth!=='global');el('cf-delete-token').required=needsAuth&&auth==='token';el('cf-delete-email').required=needsAuth&&auth==='global';el('cf-delete-global-key').required=needsAuth&&auth==='global';el('cf-delete-confirm').required=removeAll;el('cf-delete-token-note').textContent=!needsAuth?'Quick Tunnel không cần thông tin Cloudflare API.':auth==='saved'?'Sẽ dùng thông tin xác thực đã lưu trong hồ sơ.':auth==='token'?'Token cần quyền Zone / Zone / Read và Zone / DNS / Edit.':'Global API Key phải đi kèm email tài khoản Cloudflare.';}
  function removeProfile(profile){deletingProfile=profile;el('cf-delete-form').reset();el('cf-delete-name').textContent=profile.name;el('cf-delete-confirm').value='';el('cf-delete-auth-type').value=(profile.api_token_set||profile.global_api_key_set)?'saved':'token';updateDeleteForm();deleteModal.show();}
  async function systemStatus(showLoading=true,resetSelection=false){state.runtimeChecking=true;renderRuntimeStatus();render();try{if(showLoading)loading('show');const result=await request('status',{},45000);el('cf-output').textContent=result.message;if(result.data){state.profiles=result.data.profiles||state.profiles;state.active=result.data.active_profile||'';}state.publicUrl=result.runtime&&result.runtime.public_url?result.runtime.public_url:'';state.serviceActive=Boolean(result.runtime&&result.runtime.active);state.installed=result.runtime?Boolean(result.runtime.installed):null;if(resetSelection||!state.selectionReady)resetPendingSelection();else state.dirty=state.pendingActive!==actualActiveProfile();return true;}catch(error){el('cf-output').textContent=error.message;notify(error.message,'danger');return false;}finally{state.runtimeChecking=false;renderRuntimeStatus();render();if(showLoading)loading('hide');}}
  async function refreshAll(){loading('show');try{await loadProfiles();const ok=await systemStatus(false,true);if(ok)notify('Đã làm mới hồ sơ, trạng thái service và URL Cloudflare.','primary');}finally{loading('hide');}}
  el('cf-add').addEventListener('click',()=>openForm());el('cf-refresh').addEventListener('click',refreshAll);el('cf-status').addEventListener('click',()=>systemStatus(true,true));el('cf-apply').addEventListener('click',applySelection);el('cf-search').addEventListener('input',render);el('cf-mode').addEventListener('change',domainFields);el('cf-auth-type').addEventListener('change',updateAuthFields);el('cf-show-api-token').addEventListener('click',toggleApiToken);el('cf-show-global-key').addEventListener('click',toggleGlobalKey);el('cf-delete-auth-type').addEventListener('change',updateDeleteForm);document.querySelectorAll('input[name="cf-delete-mode"]').forEach(input=>input.addEventListener('change',updateDeleteForm));
  el('cf-stop').addEventListener('click',()=>stopTunnel());
  el('cf-form').addEventListener('submit',async event=>{event.preventDefault();if(!event.currentTarget.reportValidity())return;const profile={id:el('cf-id').value,name:el('cf-name').value,mode:el('cf-mode').value,local_url:el('cf-url').value,hostname:el('cf-hostname').value,tunnel:el('cf-tunnel').value,credentials_file:el('cf-credentials').value,auth_type:el('cf-auth-type').value,api_token:el('cf-api-token').value,api_email:el('cf-api-email').value,global_api_key:el('cf-global-api-key').value,auto_start:el('cf-auto').checked};const save=el('cf-save');save.disabled=true;try{const result=await request('save',{profile:JSON.stringify(profile)});modal.hide();notify(result.message);await loadProfiles();}catch(error){notify(error.message,'danger');}finally{save.disabled=false;}});
  el('cf-delete-form').addEventListener('submit',async event=>{event.preventDefault();if(!deletingProfile||!event.currentTarget.reportValidity())return;const removeAll=el('cf-delete-all').checked;if(removeAll&&el('cf-delete-confirm').value!==deletingProfile.name){notify('Tên xác nhận không khớp.','danger');return;}const button=el('cf-delete-submit');button.disabled=true;try{loading('show');const result=await request(removeAll?'delete_remote':'delete',{id:deletingProfile.id,auth_type:el('cf-delete-auth-type').value,api_token:el('cf-delete-token').value,api_email:el('cf-delete-email').value,global_api_key:el('cf-delete-global-key').value,confirm_name:el('cf-delete-confirm').value},90000);deleteModal.hide();notify(result.message);el('cf-output').textContent=result.output||result.message;deletingProfile=null;await loadProfiles();await systemStatus(false,true);}catch(error){notify(error.message,'danger');}finally{button.disabled=false;loading('hide');}});
  renderRuntimeStatus(); loadProfiles().then(()=>systemStatus(false,true));
})();
</script></body></html>
