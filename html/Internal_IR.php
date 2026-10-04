<?php include 'Configuration.php'; require_once __DIR__.'/includes/ActionRegistry.php'; if ($Config['contact_info']['user_login']['active']) { session_start(); if (empty($_SESSION['user_login'])) { header('Location: Login.php'); exit; } } ?>
<!DOCTYPE html><html lang="vi"><?php include 'html_head.php'; ?><body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main"><div class="pagetitle"><h1>IR, Remote GPIO Nội Bộ <i class="bi bi-question-circle-fill" onclick="show_message('Cần tích hợp với các Module thu phát IR, kết nối Module thông qua các chân GPIO')"></i></h1></div>
<section class="section"><div class="card"><div class="card-body pt-3">
  <div class="mb-3 d-flex flex-wrap justify-content-center gap-2">
    <button class="btn btn-warning" type="button" onclick="openLearnModal()"><i class="bi bi-broadcast"></i> Học lệnh</button>
    <a class="btn btn-outline-primary" href="FAQ.php#internal-ir-setup" target="_blank" rel="noopener noreferrer"><i class="bi bi-book"></i> Hướng dẫn</a>
  </div>
  <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Kích hoạt</th><th style="min-width:180px">Tên câu lệnh</th>
  <th style="min-width:200px">Câu phản hồi <i class="bi bi-question-circle-fill" onclick="show_message('Khi thực thi lệnh xong sẽ phát câu phản hồi tts tương ứng, để trống nếu không dùng câu phản hồi')"></i></th>
  <th style="min-width:220px">Thao tác khi nhận mã <i class="bi bi-question-circle-fill" onclick="show_message('Dùng chính nút đã học trên remote để điều khiển các chức năng của loa VBot thông qua mã lệnh đã học')"></i></th><th style="min-width:360px">Mã IR</th><th>Hành động</th></tr></thead><tbody id="irRows"></tbody></table></div>
<div class="d-flex justify-content-center mt-3">
    <button class="btn btn-success" onclick="saveAllIr()">
        <i class="bi bi-floppy"></i> Lưu toàn bộ cấu hình
    </button>
</div>
</div></div>
<div class="card"><div class="card-body pt-3">
  <h5 class="card-title">Sao lưu và khôi phục lệnh IR</h5>
  <p class="small text-muted">Bản sao lưu gồm tên lệnh, câu phản hồi, trạng thái kích hoạt, chức năng khi nhận và mã IR. Sao lưu lấy dữ liệu đã lưu; hãy lưu các thay đổi trong bảng trước khi tạo bản sao.</p>
  <div class="d-flex flex-wrap gap-2 mb-3">
    <button id="irBackupCreate" class="btn btn-primary" type="button" onclick="createIrBackup()"><i class="bi bi-save"></i> Tạo bản sao lưu</button>
    <button class="btn btn-outline-secondary" type="button" onclick="loadIrBackups()"><i class="bi bi-arrow-clockwise"></i> Tải lại danh sách</button>
  </div>
  <div id="irBackupStatus" class="alert alert-info" role="status">Đang tải danh sách sao lưu…</div>
  <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Bản sao lưu</th><th>Thời gian</th><th>Dung lượng</th><th>Thao tác</th></tr></thead><tbody id="irBackupRows"></tbody></table></div>
  <hr>
  <label for="irBackupFile" class="form-label">Khôi phục từ tệp trên máy tính (.json, tối đa 20 MB)</label>
  <div class="d-flex flex-wrap gap-2"><input id="irBackupFile" type="file" accept=".json,application/json" class="form-control" style="max-width:480px"><button class="btn btn-warning" type="button" onclick="uploadIrBackup()"><i class="bi bi-upload"></i> Tải lên và khôi phục</button></div>
  <p class="small text-muted mt-3">Khôi phục thay thế toàn bộ danh sách lệnh hiện tại. Hệ thống tự tạo bản sao trước khi khôi phục; không phát tín hiệu IR. Bản sao được lưu trong <code>html/Backup_Upgrade/Backup_Internal_IR</code>. Sau khi khôi phục, khởi động lại VBot để bảo đảm danh sách lệnh đang chạy được nạp lại.</p>
</div></div>
</section></main>
<div class="modal fade" id="irBackupJsonModal" tabindex="-1" aria-labelledby="irBackupJsonModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="irBackupJsonModalLabel">Nội dung JSON sao lưu IR</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
    <div class="modal-body">
      <p id="irBackupJsonName" class="text-break fw-bold"></p>
      <div id="irBackupJsonStatus" class="alert alert-info" role="status"></div>
      <pre id="irBackupJsonContent" class="bg-light border rounded p-3 mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere" tabindex="0"></pre>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
  </div></div>
</div>
<div class="modal fade" id="internalIrLearnModal" tabindex="-1" aria-labelledby="internalIrLearnModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="internalIrLearnModalLabel"><i class="bi bi-broadcast"></i> Học lệnh IR</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
    <div class="modal-body">
      <div id="irLearnStatus" class="alert alert-warning text-center"><i class="bi bi-info-circle"></i> Nhấn bắt đầu học và hướng remote vào mắt thu IR.</div>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label for="irName" class="form-label">Tên câu lệnh </label><input id="irName" class="form-control border-success" maxlength="100" placeholder="Tên câu lệnh, ví dụ: bật điều hòa"></div>
        <div class="col-md-6"><label for="irReply" class="form-label">Câu Phản hồi</label><input id="irReply" class="form-control border-success" maxlength="500" placeholder="Câu phản hồi (không bắt buộc)"></div>
        <div class="col-12"><label for="irNewAction" class="form-label">Chức năng khi nhận</label><select id="irNewAction" class="form-select border-success"></select></div>
      </div>
      <label for="irData" class="form-label">Mã IR đã học</label>
      <textarea id="irData" class="form-control font-monospace border-success" rows="7" readonly placeholder="Dữ liệu học được sẽ xuất hiện ở đây"></textarea>
    </div>
    <div class="modal-footer d-flex flex-column align-items-stretch">
<div class="d-flex flex-wrap gap-2 justify-content-center w-100">
    <button type="button" class="btn btn-warning text-nowrap" onclick="learnIr()"><i class="bi bi-broadcast"></i> Học lại</button>
    <button type="button" class="btn btn-primary text-nowrap" onclick="sendIr(currentData,true)"><i class="bi bi-send"></i> Thử phát</button>
    <button type="button" class="btn btn-success text-nowrap" onclick="saveIr()"><i class="bi bi-plus-circle"></i> Lưu Lệnh</button>
</div>
      <div class="d-flex justify-content-center w-100 mt-2">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div></div>
</div>
<?php include 'html_footer.php'; ?><a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a><?php include 'html_js.php'; ?>
<script>
let currentData=null, savedIr=[], irPlaylists=[], irRadios=[], irOperation=false;
const endpoint='includes/php_ajax/Internal_IR_Ajax.php';
const irSendApi=<?= json_encode(rtrim($URL_API_VBOT, '/').'/internal-ir/send', JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const irApiKey=<?= json_encode(!empty($Config['api']['auth']['active']) ? (string)($Config['api']['auth']['api_key'] ?? '') : '', JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
function irPost(values){const body=new URLSearchParams();Object.entries(values).forEach(([k,v])=>body.set(k,v));body.set('csrf_token',window.VBOT_CSRF_TOKEN||'');return fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-CSRF-Token':window.VBOT_CSRF_TOKEN||''},body:body.toString()}).then(r=>r.json()).catch(error=>({success:false,message:'Không thể kết nối hoặc máy chủ trả về dữ liệu không hợp lệ: '+error.message}));}
function notice(r){const message=r&&r.message?r.message:'Hoàn tất';if(r&&r.success){showMessagePHP(message);}else{show_message(message);}}
function learnNotice(r){const el=document.getElementById('irLearnStatus');const ok=r&&r.success;el.className='alert text-center '+(ok?'alert-success':'alert-danger');el.innerHTML=`<i class="bi ${ok?'bi-check-circle':'bi-exclamation-triangle'}"></i> ${escapeHtml(r&&r.message?r.message:'Có lỗi xảy ra')}`;}
function escapeHtml(v){const d=document.createElement('div');d.textContent=String(v??'');return d.innerHTML.replace(/"/g,'&quot;').replace(/'/g,'&#39;');}
const irActions=<?= json_encode(array_map(null, array_keys(vbotActionRegistryStatic()), array_values(vbotActionRegistryStatic())), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
function actionOptions(value){const selected=value||'none';const options=[...irActions,...irPlaylists.map(x=>[`playlist:${x.id}`,`Phát Playlist: ${x.name}`]),...irRadios.map(x=>[`radio:${x.id}`,`Phát Radio: ${x.name}`])];if((selected.startsWith('playlist:')||selected.startsWith('radio:'))&&!options.some(x=>x[0]===selected))options.push([selected,'Nguồn phát không còn tồn tại']);return options.map(([key,label])=>`<option value="${escapeHtml(key)}" ${key===selected?'selected':''}>${escapeHtml(label)}</option>`).join('');}
function renderRows(){document.getElementById('irRows').innerHTML=savedIr.map((x,i)=>`<tr data-index="${i}"><td class="text-center"><div class="form-switch d-inline-block"><input class="form-check-input ir-active border-success" type="checkbox" role="switch" ${x.active!==false?'checked':''}></div></td><td><input class="form-control border-success ir-name" maxlength="100" value="${escapeHtml(x.name||'')}"></td><td><input class="form-control border-success ir-reply" maxlength="500" value="${escapeHtml(x.reply||'')}"></td><td><select class="form-select border-success ir-action">${actionOptions(x.action)}</select></td><td><textarea class="form-control border-success font-monospace ir-code" rows="4">${escapeHtml(JSON.stringify(x.data))}</textarea></td><td class="text-nowrap"><button class="btn btn-sm btn-primary" onclick="sendRow(${i})"><i class="bi bi-send"></i> Gửi Lệnh</button> <button class="btn btn-sm btn-danger" onclick="deleteIr(${i})"><i class="bi bi-trash"></i> Xóa</button></td></tr>`).join('');}
async function loadIr(){const r=await irPost({list:1});if(!r.success)return notice(r);savedIr=r.data.commands||[];irPlaylists=Array.isArray(r.playlists)?r.playlists:[];irRadios=Array.isArray(r.radios)?r.radios:[];const c=r.config;const states=[];states.push(`Phát TX: ${c.tx_active?'bật':'tắt'} (GPIO${c.tx_gpio})`);states.push(`Thu RX: ${c.rx_active?'bật':'tắt'} (GPIO${c.rx_gpio})`);states.push(`Điều khiển nền: ${c.rx_control_active?'bật':'tắt'}`);notice({success:true,message:states.join(' — ')});renderRows();}
function openLearnModal(){currentData=null;document.getElementById('irData').value='';const action=document.getElementById('irNewAction');action.innerHTML=actionOptions('none');action.value='none';document.getElementById('irLearnStatus').className='alert alert-warning text-center';document.getElementById('irLearnStatus').innerHTML='<i class="bi bi-broadcast"></i> Hãy hướng remote vào mắt thu và nhấn nút...';$('#internalIrLearnModal').modal('show');setTimeout(learnIr,250);}
async function learnIr(){if(irOperation)return learnNotice({success:false,message:'Một thao tác IR khác đang chạy, vui lòng chờ.'});irOperation=true;const el=document.getElementById('irLearnStatus');el.className='alert alert-warning text-center';el.innerHTML='<span class="spinner-border spinner-border-sm me-2" role="status"></span> Hãy hướng remote vào mắt thu và nhấn nút...';try{const r=await irPost({learn:1});learnNotice(r);if(r.success){currentData=r.data;document.getElementById('irData').value=JSON.stringify(r.data);}}finally{irOperation=false;}}
async function sendIr(data,inModal=false){if(!data){const r={success:false,message:'Chưa có dữ liệu IR'};return inModal?learnNotice(r):notice(r);}if(irOperation){const r={success:false,message:'Một thao tác IR khác đang chạy, vui lòng chờ.'};return inModal?learnNotice(r):notice(r);}irOperation=true;try{const headers={'Content-Type':'application/json'};if(irApiKey)headers['VBot-API-Key']=irApiKey;let r;try{const response=await fetch(irSendApi,{method:'POST',headers,body:JSON.stringify({command:data})});const responseText=await response.text();let parsed=null;try{parsed=responseText?JSON.parse(responseText):null;}catch(_error){}if(!response.ok){r={success:false,message:(parsed&&parsed.message)||`VBot/API không hoạt động hoặc không phản hồi đúng (HTTP ${response.status})`};}else if(!parsed||typeof parsed!=='object'){r={success:false,message:'VBot/API không hoạt động hoặc trả về dữ liệu không hợp lệ'};}else{r=parsed;}}catch(error){r={success:false,message:'Không thể kết nối VBot/API. Hãy kiểm tra VBot đang chạy và API đã được bật.'};}if(inModal)learnNotice(r);else notice(r);}finally{irOperation=false;}}
function readRow(i){const row=document.querySelector(`#irRows tr[data-index="${i}"]`);if(!row)throw new Error('Không tìm thấy dòng '+i);let data;try{data=JSON.parse(row.querySelector('.ir-code').value);}catch(e){throw new Error(`Mã IR ở dòng ${i+1} không hợp lệ: ${e.message}`);}return {active:row.querySelector('.ir-active').checked,name:row.querySelector('.ir-name').value.trim(),reply:row.querySelector('.ir-reply').value.trim(),action:row.querySelector('.ir-action').value,data,created_at:savedIr[i]?.created_at||''};}
function sendRow(i){try{sendIr(readRow(i).data);}catch(e){notice({success:false,message:e.message});}}
async function saveIr(){if(!currentData)return learnNotice({success:false,message:'Hãy học lệnh trước'});const r=await irPost({save:1,name:document.getElementById('irName').value.trim(),reply:document.getElementById('irReply').value.trim(),action:document.getElementById('irNewAction').value||'none',data:JSON.stringify(currentData)});if(!r.success)return learnNotice(r);notice(r);currentData=null;document.getElementById('irData').value='';document.getElementById('irName').value='';document.getElementById('irReply').value='';document.getElementById('irNewAction').value='none';$('#internalIrLearnModal').modal('hide');loadIr();}
async function saveAllIr(){let commands=[];try{commands=savedIr.map((_,i)=>readRow(i));}catch(e){return notice({success:false,message:e.message});}const r=await irPost({bulk_save:1,commands:JSON.stringify(commands)});notice(r);if(r.success)loadIr();}
async function deleteIr(i){if(!confirm('Xóa lệnh IR này?'))return;const r=await irPost({delete:1,index:i});notice(r);if(r.success)loadIr();}
let irBackups=[], irBackupOperation=false;
function irBackupStatus(message, success=true){const el=document.getElementById('irBackupStatus');el.textContent=message;el.className='alert '+(success?'alert-info':'alert-danger');}
async function loadIrBackups(){
  const result=await irPost({backup_list:1});
  if(!result.success){irBackupStatus(result.message||'Không tải được danh sách sao lưu',false);return;}
  irBackups=Array.isArray(result.backups)?result.backups:[];
  const body=document.getElementById('irBackupRows');body.replaceChildren();
  irBackups.forEach((backup,index)=>{
    const row=document.createElement('tr');
    for(const text of [backup.name,backup.created_at,`${(backup.size/1024).toFixed(1)} KB`]){const cell=document.createElement('td');cell.textContent=text;row.append(cell);}
    const cell=document.createElement('td');cell.className='text-nowrap';
    for(const [label,style,handler] of [['Xem JSON','btn-outline-secondary',viewIrBackupJson],['Tải xuống','btn-outline-primary',downloadIrBackup],['Khôi phục','btn-warning',restoreIrBackup]]){
      const button=document.createElement('button');button.type='button';button.className=`btn btn-sm ${style} me-2`;button.textContent=label;button.onclick=()=>handler(index);cell.append(button);
    }
    row.append(cell);body.append(row);
  });
  irBackupStatus(irBackups.length?`Có ${irBackups.length} bản sao lưu.`:'Chưa có bản sao lưu. Nhấn “Tạo bản sao lưu” để lưu dữ liệu hiện tại.');
}
async function createIrBackup(){
  if(irBackupOperation)return;
  irBackupOperation=true;document.getElementById('irBackupCreate').disabled=true;
  try{const result=await irPost({backup_create:1});if(result.success){await loadIrBackups();irBackupStatus(`Đã sao lưu: ${result.name}`);}else irBackupStatus(result.message,false);}
  finally{irBackupOperation=false;document.getElementById('irBackupCreate').disabled=false;}
}
let irBackupJsonRequest=0;
async function viewIrBackupJson(index){
  const backup=irBackups[index];if(!backup)return;
  const request=++irBackupJsonRequest;
  const status=document.getElementById('irBackupJsonStatus');
  const content=document.getElementById('irBackupJsonContent');
  document.getElementById('irBackupJsonName').textContent=backup.name;
  content.textContent='';status.className='alert alert-info';status.textContent='Đang đọc bản sao lưu…';
  $('#irBackupJsonModal').modal('show');
  const result=await irPost({backup_download:1,name:backup.name});
  if(request!==irBackupJsonRequest)return;
  if(!result.success||typeof result.content!=='string'){
    status.className='alert alert-danger';status.textContent=result.message||'Không đọc được nội dung bản sao lưu.';return;
  }
  try{
    const data=JSON.parse(result.content);
    content.textContent=JSON.stringify(data,null,2);
    status.className='alert alert-success';status.textContent='Đang xem nội dung bản sao lưu. Không thay đổi dữ liệu và không phát lệnh IR.';
  }catch(error){
    content.textContent=result.content;
    status.className='alert alert-warning';status.textContent='Tệp chứa JSON không hợp lệ. Nội dung gốc được hiển thị để kiểm tra.';
  }
}
async function downloadIrBackup(index){
  const backup=irBackups[index];if(!backup)return;
  const result=await irPost({backup_download:1,name:backup.name});if(!result.success)return irBackupStatus(result.message,false);
  const url=URL.createObjectURL(new Blob([result.content],{type:'application/json;charset=utf-8'}));
  const link=document.createElement('a');link.href=url;link.download=result.name;document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);
}
async function restoreIrBackup(index){
  const backup=irBackups[index];if(!backup||irBackupOperation)return;
  if(irOperation)return irBackupStatus('Một thao tác IR đang chạy. Hãy chờ thao tác kết thúc.',false);
  if(!confirm(`Khôi phục ${backup.name}? Danh sách lệnh hiện tại và thay đổi chưa lưu sẽ được thay thế. Dữ liệu đã lưu sẽ được sao lưu trước khi khôi phục.`))return;
  irBackupOperation=true;irOperation=true;
  try{const result=await irPost({backup_restore:1,name:backup.name});if(result.success){await loadIr();await loadIrBackups();}irBackupStatus(result.message,result.success);}
  finally{irBackupOperation=false;irOperation=false;}
}
async function uploadIrBackup(){
  if(irBackupOperation)return;
  if(irOperation)return irBackupStatus('Một thao tác IR đang chạy. Hãy chờ thao tác kết thúc.',false);
  const input=document.getElementById('irBackupFile');const file=input.files[0];
  if(!file||!file.name.toLowerCase().endsWith('.json')||file.size<1||file.size>20971520)return irBackupStatus('Chọn tệp .json hợp lệ, tối đa 20 MB.',false);
  if(!confirm('Khôi phục từ tệp đã chọn? Danh sách lệnh hiện tại và thay đổi chưa lưu sẽ được thay thế. Dữ liệu đã lưu sẽ được sao lưu trước khi khôi phục.'))return;
  irBackupOperation=true;irOperation=true;
  try{
    const body=new FormData();body.set('backup_upload','1');body.set('backup_file',file);body.set('csrf_token',window.VBOT_CSRF_TOKEN||'');
    const response=await fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':window.VBOT_CSRF_TOKEN||''},body});
    const result=await response.json();if(result.success){input.value='';await loadIr();await loadIrBackups();}irBackupStatus(result.message,result.success);
  }catch(error){irBackupStatus('Không tải lên được tệp sao lưu: '+error.message,false);}
  finally{irBackupOperation=false;irOperation=false;}
}
loadIr();
loadIrBackups();
</script></body></html>
