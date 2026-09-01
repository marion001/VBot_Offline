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
  <div class="pagetitle"><h1>Tailscale Funnel</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li><li class="breadcrumb-item active">Quản lý Tailscale Funnel</li></ol></nav></div>
  <section class="section">
    <div class="alert alert-info d-flex flex-wrap gap-2 align-items-start"><i class="bi bi-info-circle-fill mt-1"></i><div class="flex-grow-1"><strong>Funnel</strong> công khai WebUI ra Internet qua HTTPS. <strong>Serve</strong> chỉ chia sẻ trong tailnet. Trang này không thực hiện <code>tailscale down</code>, không logout và không gỡ Tailscale.</div><a class="btn btn-sm btn-outline-primary" href="FAQ.php#accordion_button_Tailscale_Funnel"><i class="bi bi-book"></i> Hướng dẫn cài đặt</a></div>
    <div class="alert alert-warning d-flex gap-2 align-items-start"><i class="bi bi-shield-exclamation mt-1"></i><div>Để truy cập WebUI từ Internet, cần bật <strong>Cho Phép Truy Cập Bên Ngoài Internet</strong> trong cấu hình Web Interface. Chỉ công khai dịch vụ bạn tin cậy.</div></div>
    <div class="alert alert-danger d-flex flex-wrap gap-2 align-items-start"><i class="bi bi-terminal-fill mt-1"></i><div class="flex-grow-1"><strong>Cần dùng SSH khi cấu hình hoặc xác thực:</strong> Khi cài đặt lần đầu, chạy <code>sudo tailscale up</code>, đăng nhập lại sau khi logout, liên kết thiết bị vào tailnet hoặc cấp quyền Funnel lần đầu, hãy đăng nhập SSH vào Raspberry Pi/VBot để chạy lệnh. Tailscale sẽ trả về URL dạng <code>https://login.tailscale.com/a/...</code> hoặc <code>https://login.tailscale.com/f/funnel?node=...</code>; cần mở URL đó trên trình duyệt và hoàn tất xác thực. Chỉ sử dụng các nút quản lý bên dưới sau khi thiết bị đã liên kết thành công.</div><a class="btn btn-sm btn-outline-danger" href="FAQ.php#accordion_button_Tailscale_Funnel"><i class="bi bi-book"></i> Xem các lệnh SSH</a></div>

    <div class="card"><div class="card-body pt-3">
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <strong>Tailscale:</strong>
        <span id="ts-status-loading" class="d-inline-flex align-items-center gap-2 text-primary"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Đang tải dữ liệu...</span></span>
        <span id="ts-status-badges" class="d-none flex-wrap gap-2 align-items-center"><span id="ts-install" class="badge bg-secondary">Chưa kiểm tra</span><span id="ts-daemon" class="badge bg-secondary">tailscaled: Chưa kiểm tra</span><span id="ts-login" class="badge bg-secondary">Đăng nhập: Chưa kiểm tra</span><span id="ts-funnel" class="badge bg-secondary">Funnel: Chưa kiểm tra</span><span id="ts-serve" class="badge bg-secondary">Serve: Chưa kiểm tra</span></span>
        <button id="ts-service-control" class="btn btn-sm btn-outline-danger ms-md-auto" type="button" disabled><i class="bi bi-stop-circle me-1"></i><span id="ts-service-control-text">Dừng Tailscale</span></button>
        <button id="ts-autostart" class="btn btn-sm btn-outline-warning" type="button" disabled><i class="bi bi-power me-1"></i><span id="ts-autostart-text">Tự khởi động service</span></button>
        <button id="ts-refresh" class="btn btn-sm btn-outline-primary" type="button" disabled><span id="ts-refresh-icon" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Kiểm tra hệ thống</button>
      </div>
      <div id="ts-message" class="alert mt-3 mb-0 d-none"></div>
    </div></div>

    <div class="card"><div class="card-body pt-3"><h5 class="card-title pt-0">Thông tin thiết bị và tài khoản</h5><div class="row g-2"><div class="col-md-4"><strong>Tài khoản:</strong> <span id="ts-account">—</span></div><div class="col-md-4"><strong>Tên hiển thị:</strong> <span id="ts-account-name">—</span></div><div class="col-md-4"><strong>Tự khởi động:</strong> <span id="ts-autostart-state">—</span></div><div class="col-md-4"><strong>DNS:</strong> <span id="ts-dns">—</span></div><div class="col-md-3"><strong>IP Tailscale:</strong> <span id="ts-ip">—</span></div><div class="col-md-5"><strong>URL Funnel:</strong> <a id="ts-public-url" href="#" target="_blank" rel="noopener noreferrer">—</a></div></div></div></div>

    <div class="row">
      <div class="col-12"><div class="card"><div class="card-body pt-3">
        <h5 class="card-title pt-0">Cấu hình Funnel cho WebUI</h5>
        <form id="ts-form" class="row g-3">
          <div class="col-md-8"><label for="ts-target" class="form-label">Dịch vụ nội bộ</label><input id="ts-target" type="url" class="form-control border-success" value="http://127.0.0.1:80" required><div class="form-text">Chỉ chấp nhận HTTP tại 127.0.0.1 hoặc localhost. Mặc định của hệ thống: <a href="http://127.0.0.1:80" target="_blank" rel="noopener noreferrer"><code>http://127.0.0.1:80</code></a>.</div></div>
          <div class="col-md-4"><label for="ts-port" class="form-label">Cổng HTTPS công khai</label><select id="ts-port" class="form-select border-primary"><option value="443" selected>443 (Mặc Định)</option><option value="8443">8443</option><option value="10000">10000</option></select></div>
          <div class="col-12 d-flex flex-wrap gap-2">
            <button id="ts-start" class="btn btn-success" type="submit"><i class="bi bi-globe2"></i> Bật / Áp dụng Funnel</button>
            <button id="ts-stop" class="btn btn-outline-danger" type="button"><i class="bi bi-stop-circle"></i> Chỉ dừng Funnel</button>
          </div>
        </form>
      </div></div></div>
    </div>

    <div class="card border-danger"><div class="card-body pt-3">
      <h5 class="card-title text-danger pt-0">Xóa cấu hình Funnel/Serve</h5>
      <p>Thao tác này chạy cả <code>tailscale funnel reset</code> và <code>tailscale serve reset</code>. Kết nối Tailscale và đăng nhập vẫn được giữ nguyên.</p>
      <label for="ts-confirm" class="form-label">Nhập <code>XOA TAT CAU HINH</code> để xác nhận</label>
      <input id="ts-confirm" class="form-control border-danger mb-3" autocomplete="off">
      <button id="ts-reset-all" class="btn btn-danger" type="button"><i class="bi bi-trash3"></i> Xóa toàn bộ Funnel/Serve</button>
    </div></div>

    <div class="card border-danger"><div class="card-body pt-3"><h5 class="card-title text-danger pt-0">Quản lý tài khoản và gỡ cài đặt</h5><div class="row g-4">
      <div class="col-lg-5"><h6 class="fw-bold">Đăng xuất thiết bị khỏi tailnet</h6><p class="small">Thiết bị sẽ rời tài khoản hiện tại. Phần mềm Tailscale vẫn được giữ lại.</p><label for="ts-logout-confirm" class="form-label">Nhập <code>DANG XUAT TAILSCALE</code></label><input id="ts-logout-confirm" class="form-control mb-2" autocomplete="off"><button id="ts-logout" class="btn btn-outline-danger" type="button"><i class="bi bi-box-arrow-right"></i> Đăng xuất Tailscale</button></div>
      <div class="col-lg-7 border-start"><h6 class="fw-bold text-danger">Gỡ Tailscale hoàn toàn</h6><p class="small">Reset Funnel/Serve, logout, gỡ gói Tailscale và keyring, xóa repository rồi chạy <code>apt update</code>.</p><div class="form-check mb-2"><input id="ts-remove-state" class="form-check-input border-danger" type="checkbox"><label class="form-check-label" for="ts-remove-state">Xóa cả dữ liệu trạng thái <code>/var/lib/tailscale</code></label></div><label for="ts-uninstall-confirm" class="form-label">Nhập <code>GO TAILSCALE</code></label><input id="ts-uninstall-confirm" class="form-control border-danger mb-2" autocomplete="off"><button id="ts-uninstall" class="btn btn-danger" type="button"><i class="bi bi-trash3-fill"></i> Gỡ Tailscale hoàn toàn</button></div>
    </div></div></div>

    <div class="card"><div class="card-body pt-3"><h5 class="card-title pt-0">Kết quả kiểm tra</h5><pre id="ts-output" class="border rounded bg-light p-3 mb-0" style="min-height:160px;max-height:520px;overflow:auto;white-space:pre-wrap">Chưa có dữ liệu.</pre></div></div>
  </section>
</main>
<?php include 'html_footer.php'; include 'html_js.php'; ?>
<script>
(()=>{
  const el=id=>document.getElementById(id), endpoint='includes/php_ajax/Tailscale_Funnel_Ajax.php';
  function notify(message,type='success'){const box=el('ts-message');box.className='alert mt-3 mb-0 alert-'+type;box.textContent=message;box.classList.remove('d-none');}
  async function request(action,values={},timeout=60000){const body=new URLSearchParams({action,...values});const response=await vbotFetchWithTimeout(endpoint,{method:'POST',credentials:'same-origin',body,headers:{'X-CSRF-Token':window.VBOT_CSRF_TOKEN||''}},timeout);let result;try{result=await response.json();}catch(error){throw new Error('Phản hồi máy chủ không phải JSON hợp lệ.');}if(!response.ok||!result.success)throw new Error(result.message||'Thao tác thất bại.');return result;}
  function badge(id,ok,onText,offText){const node=el(id);node.className='badge '+(ok?'bg-success':'bg-secondary');node.textContent=ok?onText:offText;}
  function setStatusLoading(checking){el('ts-status-loading').classList.toggle('d-none',!checking);el('ts-status-loading').classList.toggle('d-inline-flex',checking);el('ts-status-badges').classList.toggle('d-none',checking);el('ts-status-badges').classList.toggle('d-flex',!checking);el('ts-refresh').disabled=checking;el('ts-autostart').disabled=checking;el('ts-service-control').disabled=checking;const icon=el('ts-refresh-icon');icon.className=checking?'spinner-border spinner-border-sm me-1':'bi bi-arrow-clockwise me-1';}
  function render(runtime={}){badge('ts-install',runtime.installed,'Đã cài đặt','Chưa cài đặt');badge('ts-daemon',runtime.daemon_active,'tailscaled: active','tailscaled: inactive');badge('ts-login',runtime.logged_in,'Đăng nhập: Running','Đăng nhập: '+(runtime.backend_state||'Chưa đăng nhập'));badge('ts-funnel',runtime.funnel_active,'Funnel: Đang bật','Funnel: Đang tắt');badge('ts-serve',runtime.serve_active,'Serve: Có cấu hình','Serve: Không cấu hình');el('ts-account').textContent=runtime.account_login||'—';el('ts-account-name').textContent=runtime.account_display_name||'—';el('ts-autostart-state').textContent=runtime.daemon_enabled?'Đang bật':'Đang tắt';el('ts-autostart').dataset.enabled=runtime.daemon_enabled?'true':'false';el('ts-autostart-text').textContent=runtime.daemon_enabled?'Tắt tự khởi động service':'Bật tự khởi động service';el('ts-autostart').className=runtime.daemon_enabled?'btn btn-sm btn-outline-danger':'btn btn-sm btn-outline-success';el('ts-service-control').dataset.active=runtime.daemon_active?'true':'false';el('ts-service-control-text').textContent=runtime.daemon_active?'Dừng hẳn Tailscale':'Bật lại Tailscale';el('ts-service-control').className=runtime.daemon_active?'btn btn-sm btn-outline-danger ms-md-auto':'btn btn-sm btn-outline-success ms-md-auto';el('ts-dns').textContent=runtime.dns_name||'—';el('ts-ip').textContent=runtime.ipv4||'—';const link=el('ts-public-url');link.textContent=runtime.public_url||'—';link.href=runtime.public_url||'#';}
  async function refresh(show=true){setStatusLoading(true);try{if(show)loading('show');const result=await request('status',{},45000);el('ts-output').textContent=result.message;render(result.runtime||{});return true;}catch(error){el('ts-output').textContent=error.message;notify(error.message,'danger');return false;}finally{setStatusLoading(false);if(show)loading('hide');}}
  el('ts-refresh').addEventListener('click',()=>refresh(true));
  el('ts-autostart').addEventListener('click',async()=>{const currentlyEnabled=el('ts-autostart').dataset.enabled==='true',nextEnabled=!currentlyEnabled,label=nextEnabled?'bật':'tắt';if(!confirm('Bạn muốn '+label+' tailscaled tự khởi động cùng hệ thống? Kết nối hiện tại sẽ không bị dừng.'))return;try{loading('show');const result=await request('set_autostart',{enabled:String(nextEnabled)},35000);notify(result.message);await refresh(false);}catch(error){notify(error.message,'danger');}finally{loading('hide');}});
  el('ts-service-control').addEventListener('click',async()=>{const active=el('ts-service-control').dataset.active==='true',operation=active?'stop':'start';if(!confirm(active?'Dừng hẳn tailscaled nhưng vẫn giữ phần mềm và cấu hình?':'Bật tailscaled và cho phép tự khởi động cùng hệ thống?'))return;try{loading('show');const result=await request('service_control',{operation},40000);notify(result.message);await refresh(false);}catch(error){notify(error.message,'danger');}finally{loading('hide');}});
  el('ts-form').addEventListener('submit',async event=>{event.preventDefault();if(!event.currentTarget.reportValidity())return;if(!confirm('Công khai dịch vụ này ra Internet bằng Tailscale Funnel?'))return;try{loading('show');const result=await request('start_funnel',{target:el('ts-target').value,https_port:el('ts-port').value},70000);notify(result.message);await refresh(false);}catch(error){notify(error.message,'danger');}finally{loading('hide');}});
  el('ts-stop').addEventListener('click',async()=>{if(!confirm('Chỉ dừng Funnel tại cổng HTTPS đã chọn? Cấu hình Serve sẽ được giữ nguyên.'))return;try{loading('show');const result=await request('stop_funnel',{https_port:el('ts-port').value},45000);notify(result.message);await refresh(false);}catch(error){notify(error.message,'danger');}finally{loading('hide');}});
  el('ts-reset-all').addEventListener('click',async()=>{const confirmation=el('ts-confirm').value;if(confirmation!=='XOA TAT CAU HINH'){notify('Nội dung xác nhận không đúng.','danger');return;}if(!confirm('Xóa toàn bộ cấu hình Funnel và Serve? Thao tác này không thể hoàn tác.'))return;try{loading('show');const result=await request('reset_all',{confirmation},50000);notify(result.message);el('ts-confirm').value='';await refresh(false);}catch(error){notify(error.message,'danger');}finally{loading('hide');}});
  el('ts-logout').addEventListener('click',async()=>{const confirmation=el('ts-logout-confirm').value;if(confirmation!=='DANG XUAT TAILSCALE'){notify('Nội dung xác nhận đăng xuất không đúng.','danger');return;}if(!confirm('Đăng xuất thiết bị này khỏi tài khoản/tailnet Tailscale?'))return;try{loading('show');const result=await request('logout',{confirmation},45000);notify(result.message);el('ts-logout-confirm').value='';await refresh(false);}catch(error){notify(error.message,'danger');}finally{loading('hide');}});
  el('ts-uninstall').addEventListener('click',async()=>{const confirmation=el('ts-uninstall-confirm').value;if(confirmation!=='GO TAILSCALE'){notify('Nội dung xác nhận gỡ Tailscale không đúng.','danger');return;}if(!confirm('Gỡ hoàn toàn Tailscale và repository khỏi thiết bị?'))return;try{loading('show');const result=await request('uninstall',{confirmation,remove_state:String(el('ts-remove-state').checked)},120000);notify(result.message);el('ts-output').textContent=result.output||result.message;el('ts-uninstall-confirm').value='';await refresh(false);}catch(error){notify(error.message,'danger');}finally{loading('hide');}});
  refresh(false);
})();
</script>
</body></html>
