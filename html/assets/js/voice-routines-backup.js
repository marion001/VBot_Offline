(() => {
    'use strict';
    const byId=id=>document.getElementById(id), editor=window.voiceRoutineEditor;
    const section=byId('routineBackupSection');
    if (!section || !editor) return;
    let busy=false, revision='', editorRevisionAtRefresh='';
    const mutationRevision=()=>{const current=editor.getRevision();return current && current!==editorRevisionAtRefresh?current:revision || current;};
    const escape=value=>String(value || '').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    function notice(message,ok=true){const box=byId('routineBackupNotice');box.className='alert '+(ok?'alert-info':'alert-danger');box.textContent=message;}
    async function post(action,extra={}) {
        const body=new URLSearchParams({action,csrf_token:window.VBOT_CSRF_TOKEN || '',...extra});
        const response=await fetch('includes/php_ajax/Voice_Routines_Backup.php',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':window.VBOT_CSRF_TOKEN || ''},body});
        const result=await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Không hoàn tất thao tác sao lưu');
        return result;
    }
    function renderBackups(rows) {
        const select=byId('routineBackupSelect'), current=select.value;
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
        section.querySelectorAll('.routine-backup-control').forEach(control=>{control.disabled=true;});
        if (mutates) editor.setExternalBusy(true);
        try { await work(); } catch(error){notice(error.message,false);}
        finally {
            if (mutates) editor.setExternalBusy(false);
            busy=false;section.querySelectorAll('.routine-backup-control').forEach(control=>{control.disabled=false;});
        }
    }
    async function refresh() {
        const result=await post('list');revision=result.revision;editorRevisionAtRefresh=editor.getRevision();renderBackups(result.backups);
        notice(result.current_valid===false?'File kịch bản hiện tại không hợp lệ. Bạn có thể xem/tải xuống file và khôi phục bằng bản hợp lệ.':'Có '+result.backups.length+' bản sao lưu. Thao tác xem/tải xuống dùng dữ liệu đã lưu.');
    }
    function confirmReplace(label) {
        const unsaved=editor.hasUnsavedChanges()?' Các thay đổi chưa lưu trên trang sẽ bị bỏ.':'';
        return confirm(label+' Dữ liệu hiện tại được sao lưu trước khi thay đổi.'+unsaved);
    }
    async function read(name,saveFile) {
        const result=await post('read',{name});
        if (saveFile) download(result.raw,result.filename);
        else {byId('routineBackupJson').textContent=result.raw;byId('routineBackupJson').classList.remove('d-none');}
        notice((saveFile?'Đã tải xuống: ':'Đang xem: ')+result.filename);
    }
    function selectedBackup() {
        const name=byId('routineBackupSelect').value;
        if (!name) throw new Error('Hãy chọn một bản sao lưu');
        return name;
    }
    async function apply(action,extra) {
        const result=await post(action,{revision:mutationRevision(),...extra});
        revision=result.revision;editor.applySaved(result);editorRevisionAtRefresh=result.revision;renderBackups(result.backups);
        notice(result.message+' Bản trước thay đổi: '+result.before_backup);
        byId('routineBackupJson').classList.add('d-none');
    }
    async function importData(raw) {
        if (!raw || new Blob([raw]).size>1048576) throw new Error('JSON phải có nội dung và không vượt quá 1 MB');
        JSON.parse(raw.replace(/^\uFEFF/,''));
        const mode=byId('routineImportMode').value;
        if (!confirmReplace(mode==='replace'?'Thay thế toàn bộ kịch bản bằng JSON này?':'Gộp JSON theo ID, thay thế các ID trùng?')) return;
        await apply('import',{data:raw,mode});
    }
    byId('routineBackupRefresh').addEventListener('click',()=>operation(refresh));
    byId('routineJsonView').addEventListener('click',()=>operation(()=>read('',false)));
    byId('routineJsonDownload').addEventListener('click',()=>operation(()=>read('',true)));
    byId('routineBackupView').addEventListener('click',()=>operation(()=>read(selectedBackup(),false)));
    byId('routineBackupDownload').addEventListener('click',()=>operation(()=>read(selectedBackup(),true)));
    byId('routineBackupCreate').addEventListener('click',()=>operation(async()=>{
        const result=await post('create',{revision:mutationRevision()});revision=result.revision;editorRevisionAtRefresh=editor.getRevision();renderBackups(result.backups);notice(result.message);
    },true));
    byId('routineBackupRestore').addEventListener('click',()=>operation(async()=>{
        const name=selectedBackup();if (confirmReplace('Khôi phục toàn bộ kịch bản từ '+name+'?')) await apply('restore',{name});
    },true));
    byId('routineImportUpload').addEventListener('click',()=>operation(async()=>{
        const file=byId('routineImportFile').files[0];
        if (!file || file.size>1048576) throw new Error('Chọn một tệp JSON tối đa 1 MB');
        await importData(await file.text());
    },true));
    byId('routineImportPaste').addEventListener('click',()=>operation(()=>importData(byId('routineImportText').value),true));
    operation(refresh);
})();
