(() => {
    'use strict';
    const byId=id=>document.getElementById(id), editor=window.lanPeerEditor;
    const section=byId('lanBackupSection');
    if (!section || !editor) return;
    let busy=false, revision='', editorRevisionAtRefresh='';
    const mutationRevision=()=>{const current=editor.getRevision();return current && current!==editorRevisionAtRefresh?current:revision || current;};
    const escape=value=>String(value || '').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    function notice(message,ok=true,popup=true){
        const box=byId('lanBackupNotice');box.className='alert '+(ok?'alert-info':'alert-danger');box.textContent=message;
        if(popup && typeof window.showMessagePHP==='function') window.showMessagePHP(escape(message));
        if(popup && !ok && typeof window.show_message==='function') window.show_message(escape(message));
    }
    async function post(action,extra={}) {
        const body=new URLSearchParams({action,csrf:window.lanCallOptions.backupCsrf || '',...extra});
        const response=await fetch('includes/php_ajax/LAN_Calls_Backup.php',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':window.VBOT_CSRF_TOKEN || ''},body});
        const result=await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Không hoàn tất thao tác sao lưu');
        return result;
    }
    function renderBackups(rows) {
        const select=byId('lanBackupSelect'), current=select.value;
        select.innerHTML='<option value="">Chọn bản sao lưu…</option>'+(rows || []).map(item=>`<option value="${escape(item.name)}">${escape(item.created_at)} — ${escape(item.name)} (${Math.ceil(item.size/1024)} KB)</option>`).join('');
        if ((rows || []).some(item=>item.name===current)) select.value=current;
    }
    function download(raw,filename) {
        const url=URL.createObjectURL(new Blob([raw],{type:'application/json;charset=utf-8'}));
        const link=document.createElement('a');link.href=url;link.download=filename;link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
    }
    async function operation(work,mutates=false) {
        if (busy || (mutates && editor.isBusy())) return;
        busy=true;
        const endLoading=typeof window.vbotBeginLoading==='function'?window.vbotBeginLoading():()=>{};
        section.querySelectorAll('.lan-backup-control').forEach(control=>{control.disabled=true;});
        if (mutates) editor.setExternalBusy(true);
        try { await work(); } catch(error){notice(error.message,false);}
        finally {
            if (mutates) editor.setExternalBusy(false);
            busy=false;section.querySelectorAll('.lan-backup-control').forEach(control=>{control.disabled=false;});
            endLoading();
        }
    }
    async function refresh() {
        const result=await post('list');revision=result.revision;editorRevisionAtRefresh=editor.getRevision();renderBackups(result.backups);
        notice(result.current_valid===false?'File danh sách loa hiện tại không hợp lệ. Nếu bảng vẫn hiển thị, hãy sửa các ô viền đỏ rồi lưu. Bạn cũng có thể xem/tải JSON hoặc khôi phục bằng bản hợp lệ.':'Có '+result.backups.length+' bản sao lưu. Thao tác xem/tải xuống dùng dữ liệu đã lưu.',result.current_valid!==false,result.current_valid===false);
    }
    function confirmReplace(label) {
        const unsaved=editor.hasUnsavedChanges()?' Các thay đổi chưa lưu trên trang sẽ bị bỏ.':'';
        return confirm(label+' Dữ liệu hiện tại được sao lưu trước khi thay đổi.'+unsaved);
    }
    async function read(name,saveFile) {
        const result=await post('read',{name});
        if (saveFile) download(result.raw,result.filename);
        else {
            byId('lanBackupJson').textContent=result.raw;
            byId('lanBackupJsonTitle').textContent='Xem JSON — '+result.filename;
            window.bootstrap.Modal.getOrCreateInstance(byId('lanBackupJsonModal')).show();
        }
        notice((saveFile?'Đã tải xuống: ':'Đang xem: ')+result.filename);
    }
    function selectedBackup() {
        const name=byId('lanBackupSelect').value;
        if (!name) throw new Error('Hãy chọn một bản sao lưu');
        return name;
    }
    async function apply(action,extra) {
        const result=await post(action,{revision:mutationRevision(),...extra});
        revision=result.revision;editor.applySaved(result);editorRevisionAtRefresh=result.revision;renderBackups(result.backups);
        notice(result.message+' Bản trước thay đổi: '+result.before_backup);
        const modal=window.bootstrap.Modal.getInstance(byId('lanBackupJsonModal'));
        if (modal) modal.hide();
        byId('lanBackupJson').textContent='';
    }
    async function importData(raw) {
        if (!raw || new Blob([raw]).size>40000) throw new Error('JSON phải có nội dung và không vượt quá 40 KB');
        JSON.parse(raw.replace(/^\uFEFF/,''));
        const mode=byId('lanImportMode').value;
        if (!confirmReplace(mode==='replace'?'Thay thế toàn bộ danh sách loa bằng JSON này?':'Gộp JSON theo ID, thay thế các ID trùng?')) return;
        await apply('import',{data:raw,mode});
    }
    byId('lanBackupRefresh').addEventListener('click',()=>operation(refresh));
    byId('lanJsonView').addEventListener('click',()=>operation(()=>read('',false)));
    byId('lanJsonDownload').addEventListener('click',()=>operation(()=>read('',true)));
    byId('lanBackupView').addEventListener('click',()=>operation(()=>read(selectedBackup(),false)));
    byId('lanBackupDownload').addEventListener('click',()=>operation(()=>read(selectedBackup(),true)));
    byId('lanBackupCreate').addEventListener('click',()=>operation(async()=>{
        const result=await post('create',{revision:mutationRevision()});revision=result.revision;editorRevisionAtRefresh=editor.getRevision();renderBackups(result.backups);notice(result.message);
    },true));
    byId('lanBackupRestore').addEventListener('click',()=>operation(async()=>{
        const name=selectedBackup();if (confirmReplace('Khôi phục toàn bộ danh sách loa từ '+name+'?')) await apply('restore',{name});
    },true));
    byId('lanImportUpload').addEventListener('click',()=>operation(async()=>{
        const file=byId('lanImportFile').files[0];
        if (!file || file.size>40000) throw new Error('Chọn một tệp JSON tối đa 40 KB');
        await importData(await file.text());
    },true));
    byId('lanImportPaste').addEventListener('click',()=>operation(()=>importData(byId('lanImportText').value),true));
    operation(refresh);
})();
