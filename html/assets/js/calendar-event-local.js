(() => {
    'use strict';
    const action=document.getElementById('event_action'), group=document.getElementById('event_media_local_group');
    const select=document.getElementById('event_media_path'), search=document.getElementById('event_local_search');
    const refresh=document.getElementById('event_local_refresh'), status=document.getElementById('event_local_status');
    if (!action || !group || !select) return;
    let files=[], loaded=false, loading=false, selected=select.value || '';
    const escape=value=>String(value || '').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const normalize=value=>String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[đĐ]/g,'d').toLowerCase().trim();
    function renderOptions() {
        const words=normalize(search.value).split(/\s+/).filter(Boolean);
        const choices=files.filter(file=>words.every(word=>normalize(file.name).includes(word))).slice(0,250);
        if (selected && !choices.some(file=>file.path===selected)) {
            choices.unshift(files.find(file=>file.path===selected) || {path:selected,name:'Bài đã lưu (không có trong danh sách): '+selected});
        }
        select.innerHTML='<option value="">Chọn bài nhạc Local…</option>'+choices.map(file=>`<option value="${escape(file.path)}" ${file.path===selected?'selected':''}>${escape(file.name)}</option>`).join('');
        select.value=selected;
    }
    async function loadFiles() {
        if (loading) return;
        loading=true; refresh.disabled=true; status.textContent='Đang đọc thư mục nhạc Local…';
        try {
            const response=await fetch('includes/php_ajax/Voice_Routines_Local.php',{credentials:'same-origin',cache:'no-store'});
            const result=await response.json();
            if (!response.ok || !result.success || !Array.isArray(result.files)) throw new Error(result.message || 'Không đọc được danh sách nhạc');
            files=result.files;
            status.textContent=`Có ${files.length} bài nhạc. Tìm theo tên; hiển thị tối đa 250 kết quả mỗi lần.`;
        } catch (error) { status.textContent=error.message+(files.length?' Danh sách đang có được giữ nguyên.':''); }
        finally { loaded=true; loading=false; refresh.disabled=false; renderOptions(); }
    }
    function updateVisibility() {
        const visible=action.value==='media_local';
        group.classList.toggle('d-none',!visible);
        select.disabled=!visible; select.required=visible;
        if (visible && !loaded && !loading) loadFiles();
    }
    window.calendarLocalPicker={updateVisibility,setSelected(path){selected=path || '';search.value='';renderOptions();}};
    select.addEventListener('change',()=>{selected=select.value;});
    search.addEventListener('input',renderOptions);
    refresh.addEventListener('click',loadFiles);
    action.addEventListener('change',updateVisibility);
    updateVisibility();
})();
