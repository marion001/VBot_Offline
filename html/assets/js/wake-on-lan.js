(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  let data = {version:1,active:true,devices:[]}, revision = '', busy = false, dirty = false;
  function showSettings(show) {
    $('wol-settings').hidden = !show;

  }

  $('wol-close-settings').onclick = () => showSettings(false);
  function setBusy(value) {
    busy = value;
    $('wol-add').disabled = value;
    document.querySelectorAll('#wol-buttons button, #wol-backup-section button, #wol-backup-section input, #wol-backup-section select').forEach(el => el.disabled = value);
    document.querySelectorAll('#wol-settings input, #wol-settings textarea, #wol-settings button').forEach(el => el.disabled = value);
  }
  function notice(text, ok = true) {
    ['wol-notice','wol-modal-notice'].forEach(id => {
      const el = $(id); if (!el) return;
      el.textContent = text; el.className = 'alert mt-3 alert-' + (ok ? 'info' : 'danger');
    });
  }
  async function api(action, extra = {}) {
    const body = new URLSearchParams({action, csrf_token:window.vbotWolCsrf, ...extra});
    const response = await fetch('includes/php_ajax/Wake_On_LAN.php', {method:'POST',body,credentials:'same-origin'});
    const result = await response.json();
    if (!result.success) throw new Error(result.message || 'Thao tác thất bại');
    return result;
  }
  function field(label, value, onChange, type = 'text') {
    const wrap = document.createElement('label'); wrap.className = 'col-md-6 form-label';
    wrap.append(document.createTextNode(label));
    const input = document.createElement(type === 'textarea' ? 'textarea' : 'input');
    input.className = 'form-control'; input.value = value;
    if (type !== 'textarea') input.type = type;
    input.addEventListener('input', () => { dirty = true; onChange(input.value); });
    wrap.append(input); return wrap;
  }
  function render() {
    $('wol-devices').replaceChildren(); $('wol-buttons').replaceChildren();
    data.devices.forEach((device, index) => {
      const card = document.createElement('div'); card.className = 'card border mb-3';
      const body = document.createElement('div'); body.className = 'card-body pt-3';
      const row = document.createElement('div'); row.className = 'row g-3';
      row.append(field('Tên máy tính', device.name, v => device.name = v), field('MAC card Ethernet', device.mac, v => device.mac = v));
      row.append(field('Broadcast IPv4', device.broadcast, v => device.broadcast = v), field('Cổng UDP', device.port, v => device.port = Number(v), 'number'));
      row.append(field('Câu lệnh khác (mỗi dòng một câu, nói đúng cả câu)', device.aliases.join('\n'), v => device.aliases = v.split('\n').map(x => x.trim()).filter(Boolean), 'textarea'));
      const check = document.createElement('label'); check.className = 'col-md-6';
      const input = document.createElement('input'); input.type = 'checkbox'; input.checked = device.enabled;
      input.addEventListener('change', () => { device.enabled = input.checked; dirty = true; }); check.append(input, document.createTextNode(' Cho phép đánh thức máy này')); row.append(check);
      body.append(row); card.append(body); $('wol-devices').append(card);
      const wake = document.createElement('button'); wake.type = 'button'; wake.className = 'btn btn-primary'; wake.textContent = 'Bật ' + device.name;
      wake.disabled = !data.active || !device.enabled || !device.mac;
      wake.onclick = async () => {
        if (busy) return;
        if (dirty) { notice('Hãy lưu cấu hình trước khi gửi lệnh.', false); return; }
        setBusy(true); wake.disabled = true;
        try { const result = await api('wake', {id:device.id}); notice(result.message); }
        catch (e) { notice(e.message, false); }
        finally { setBusy(false); render(); }
      };
      const tr=document.createElement('tr');
      [device.name,device.mac,device.broadcast,String(device.port),device.aliases.join('; '),device.enabled?'Cho phép':'Tắt'].forEach(value=>{const td=document.createElement('td');td.textContent=value;tr.append(td);});
      const actions=document.createElement('td');
      const edit=document.createElement('button');edit.type='button';edit.className='btn btn-outline-primary btn-sm m-1';edit.textContent='Sửa';
      edit.onclick=()=>{if(busy)return;showSettings(true);Array.from($('wol-devices').children).forEach((node,i)=>node.hidden=i!==index);const input=card.querySelector('input');input.focus();card.scrollIntoView({behavior:'smooth',block:'center'});};
      const del=document.createElement('button');del.type='button';del.className='btn btn-outline-danger btn-sm m-1';del.textContent='Xóa';
      del.onclick=async()=>{if(busy || !confirm('Xóa máy tính '+device.name+'?'))return;if(dirty){notice('Hãy lưu hoặc tải lại các thay đổi trước khi xóa.',false);return;}const next=JSON.parse(JSON.stringify(data));next.devices.splice(index,1);await persist(next);};
      actions.append(wake,edit,del);tr.append(actions);$('wol-buttons').append(tr);
    });
    if (!data.devices.length) {const tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=7;td.textContent='Chưa có máy tính. Nhấn Thêm máy tính mới để cấu hình.';tr.append(td);$('wol-buttons').append(tr);}
  }
  async function reload() {
    if (busy) return;
    setBusy(true);
    try { const result = await api('list'); data = result.data; revision = result.revision; dirty = false; render(); notice('Đã tải cấu hình Wake-on-LAN'); }
    catch (e) { notice(e.message, false); }
    finally { setBusy(false); render(); }
  }
  $('wol-add').onclick = () => {
    if (busy) return;
    if (data.devices.length >= 50) { notice('Tối đa 50 máy tính', false); return; }
    data.devices.push({id:'pc_' + Date.now() + '_' + Math.random().toString(36).slice(2,8),name:'máy tính mới',mac:'',broadcast:'255.255.255.255',port:9,enabled:true,aliases:[]}); dirty = true; render();
    showSettings(true);
    Array.from($('wol-devices').children).forEach((node,i)=>node.hidden=i!==data.devices.length-1);
    const input = $('wol-devices').lastElementChild?.querySelector('input');
    if (input) { input.scrollIntoView({behavior:'smooth',block:'center'}); input.focus({preventScroll:true}); input.select(); }
  };
  $('wol-reload').onclick = () => { if (!dirty || window.confirm('Bỏ các thay đổi chưa lưu và tải lại?')) reload(); };
  async function persist(next) {
    if (busy) return;
    setBusy(true);
    try { const result=await api('save',{data:JSON.stringify(next),revision});data=result.data;revision=result.revision;dirty=false;render();showSettings(false);notice(result.message);await refreshBackups(); }
    catch(e){notice(e.message,false);}
    finally {setBusy(false);render();}
  }
  $('wol-save').onclick=()=>persist(data);
  async function refreshBackups(){
    const result=await api('backups');const select=$('wol-backup-select'),previous=select.value;select.replaceChildren();
    const empty=document.createElement('option');empty.value='';empty.textContent='Chọn bản sao lưu…';select.append(empty);
    result.backups.forEach(row=>{const option=document.createElement('option');option.value=row.name;option.textContent=row.name+' ('+Math.ceil(row.size/1024)+' KB)';select.append(option);});
    if(result.backups.some(row=>row.name===previous))select.value=previous;
  }
  async function backupTask(work){if(busy)return;setBusy(true);try{await work();}catch(e){notice(e.message,false);}finally{setBusy(false);render();}}
  function selected(){const name=$('wol-backup-select').value;if(!name)throw new Error('Hãy chọn bản sao lưu');return name;}
  async function readJson(name,download){
    const result=await api('read',{name});
    if(download){const url=URL.createObjectURL(new Blob([result.raw],{type:'application/json;charset=utf-8'}));const a=document.createElement('a');a.href=url;a.download=result.filename;a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);}
    else{$('wol-json-content').textContent=result.raw;$('wol-json-title').textContent='Xem JSON — '+result.filename;window.bootstrap.Modal.getOrCreateInstance($('wol-json-modal')).show();}
  }
  $('wol-json-view').onclick=()=>backupTask(()=>readJson('',false));
  $('wol-json-download').onclick=()=>backupTask(()=>readJson('',true));
  $('wol-backup-view').onclick=()=>backupTask(()=>readJson(selected(),false));
  $('wol-backup-download').onclick=()=>backupTask(()=>readJson(selected(),true));
  $('wol-backup-refresh').onclick=()=>backupTask(refreshBackups);
  $('wol-backup-create').onclick=()=>backupTask(async()=>{const r=await api('create',{revision});await refreshBackups();notice(r.message);});
  $('wol-backup-restore').onclick=()=>backupTask(async()=>{
    const name=selected();if(!confirm('Khôi phục '+name+'? Danh sách hiện tại sẽ được sao lưu; thay đổi chưa lưu sẽ bị bỏ.'))return;
    const r=await api('restore',{name,revision});data=r.data;revision=r.revision;dirty=false;showSettings(false);await refreshBackups();notice(r.message);
  });
  $('wol-import').onclick=async()=>{
    if(busy)return;const file=$('wol-import-file').files[0];
    if(!file || file.size>1048576){notice('Chọn tệp JSON tối đa 1 MB',false);return;}
    await backupTask(async()=>{
      const incoming=JSON.parse((await file.text()).replace(/^\uFEFF/,''));
      if(!confirm('Thay thế toàn bộ danh sách bằng JSON này? Dữ liệu hiện tại sẽ được sao lưu; thay đổi chưa lưu sẽ bị bỏ.'))return;
      const r=await api('save',{data:JSON.stringify(incoming),revision});data=r.data;revision=r.revision;dirty=false;showSettings(false);await refreshBackups();notice(r.message);
    });
  };
  reload().then(()=>backupTask(refreshBackups));
})();
