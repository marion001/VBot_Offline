(() => {
    'use strict';
    const options = window.voiceRoutineOptions;
    const byId = id => document.getElementById(id);
    let data = {version: 1, routines: []}, revision = '', dirty = false, ready = false, busy = false, runningTest = false;
    let hassEntities = [], hassById = new Map(), hassLoading = false, hassMessage = 'Đang đọc dữ liệu Home Assistant đã lưu…';
    let localFiles = [], localLoaded = false, localLoading = false, localMessage = 'Chưa đọc danh sách nhạc Local.';
    const urlActions = ['play_url','play_youtube','play_zingmp3','play_nhaccuatui'];
    const types = {home_assistant: 'Home Assistant', action: 'Thao tác loa / phát playlist', volume: 'Đặt âm lượng', led_brightness: 'Đặt độ sáng LED ở loa', speak: 'Đọc thông báo', wait: 'Chờ'};
    const hassLabels = {turn_on:'Bật / kích hoạt', turn_off:'Tắt', turn_setup:'Đặt giá trị', turn_open:'Mở rèm', turn_close:'Đóng rèm', turn_stop:'Dừng rèm', turn_cool:'Làm lạnh', turn_auto:'Tự động', turn_heat:'Sưởi', turn_dry:'Hút ẩm', turn_fan_only:'Chỉ quạt'};
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const selectOptions = (values, selected) => Object.entries(values).map(([value, label]) => `<option value="${escape(value)}" ${value===selected?'selected':''}>${escape(label)}</option>`).join('');
    const notice = (message, ok=true) => { byId('routineNotice').className = `alert ${ok?'alert-info':'alert-danger'} mt-3 mb-0`; byId('routineNotice').textContent = message; };
    function setDirty() { dirty = true; notice('Có thay đổi chưa lưu. Lưu toàn bộ trước khi chạy thử.'); }
    async function post(action, extra={}) {
        const body = new URLSearchParams({action, csrf_token:window.VBOT_CSRF_TOKEN || '', ...extra});
        const response = await fetch(options.endpoint, {method:'POST', credentials:'same-origin', headers:{'X-CSRF-Token':window.VBOT_CSRF_TOKEN || ''}, body});
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Không hoàn tất yêu cầu');
        return result;
    }
    async function api(payload) {
        const headers = {};
        if (options.apiKey) headers['VBot-API-Key'] = options.apiKey;
        if (payload) headers['Content-Type'] = 'application/json';
        const response = await fetch(options.api, {method:payload?'POST':'GET', credentials:'same-origin', headers, ...(payload?{body:JSON.stringify(payload)}:{})});
        const result = await response.json();
        if (result.state) renderStatus(result.enabled===false?{...result.state,message:'Kịch bản giọng nói đang tắt theo cấu hình khi khởi động. Bật voice_routines.active và khởi động lại VBot để sử dụng.'}:result.state);
        if (!response.ok || !result.success) throw new Error(result.message || 'Không hoàn tất yêu cầu');
        return result;
    }
    function defaults(type) {
        return {home_assistant:{type,entity_id:'',action:'turn_off',value:null}, action:{type,action:'media_stop'},
            volume:{type,value:25}, led_brightness:{type,value:50}, speak:{type,text:''}, wait:{type,seconds:1}}[type];
    }
    const searchText = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[đĐ]/g,'d').toLowerCase().replace(/\s+/g,' ').trim();
    function updateHassUi() {
        document.querySelectorAll('.hass-cache-info').forEach(node => { node.textContent = hassMessage; });
        document.querySelectorAll('[data-command="fetch-hass"]').forEach(button => {
            button.disabled = hassLoading;
            button.textContent = hassLoading ? 'Đang tải…' : 'Tải dữ liệu Home Assistant';
        });
        document.querySelectorAll('[data-field="entity_id"]').forEach((input,index) => {
            input.id='routine-hass-entity-'+index;
            const picker=input.closest('.hass-picker');
            picker.querySelector('label').htmlFor=input.id;
            picker.querySelector('.hass-results').id='routine-hass-results-'+index;
            input.setAttribute('aria-controls','routine-hass-results-'+index);
            const label = input.closest('.hass-picker').querySelector('.hass-entity-name');
            const entity = hassById.get(input.value.trim());
            label.textContent = entity ? entity.name + ' — ' + entity.state : '';
            if (input.getAttribute('aria-expanded')==='true') showHassResults(input);
        });
    }
    async function loadHassCache(refresh=false) {
        if (hassLoading) return;
        hassLoading = true;
        hassMessage = refresh ? 'Đang tải toàn bộ thực thể từ Home Assistant…' : 'Đang đọc dữ liệu Home Assistant đã lưu…';
        updateHassUi();
        try {
            const settings = {credentials:'same-origin',cache:'no-store'};
            if (refresh) {
                settings.method='POST';
                settings.headers={'X-CSRF-Token':window.VBOT_CSRF_TOKEN || ''};
                settings.body=new URLSearchParams({action:'refresh',csrf_token:window.VBOT_CSRF_TOKEN || ''});
            }
            const response=await fetch(options.hassCache,settings);
            const result=await response.json();
            if (!response.ok || !result.success || !Array.isArray(result.data?.get_hass_all))
                throw new Error(result.message || 'Dữ liệu Home Assistant không hợp lệ');
            hassEntities=result.data.get_hass_all.map(entity => {
                const name=typeof entity.attributes?.friendly_name==='string'?entity.attributes.friendly_name:entity.entity_id;
                return {entity_id:entity.entity_id,name,state:entity.state,
                    supported:Object.hasOwn ? Object.hasOwn(options.hassActions,entity.entity_id.split('.')[0]) : Object.prototype.hasOwnProperty.call(options.hassActions,entity.entity_id.split('.')[0]),
                    search:searchText(name+' '+entity.entity_id)};
            }).sort((a,b)=>Number(b.supported)-Number(a.supported) || a.name.localeCompare(b.name,'vi'));
            hassById=new Map(hassEntities.map(entity=>[entity.entity_id,entity]));
            const time=result.data.updated_at?new Date(result.data.updated_at).toLocaleString('vi-VN'):'';
            hassMessage=hassEntities.length?`Đã có ${hassEntities.length} thực thể${time?' — cập nhật '+time:''}. Tìm theo tên hoặc Entity ID.`:'Chưa có thực thể đã lưu. Nhấn “Tải dữ liệu Home Assistant” hoặc nhập Entity ID trực tiếp.';
        } catch (error) {
            hassMessage=error.message+(hassEntities.length?' Danh sách đang có vẫn được giữ.':' Bạn vẫn có thể nhập Entity ID trực tiếp.');
        } finally { hassLoading=false; updateHassUi(); }
    }
    function showHassResults(input) {
        const picker=input.closest('.hass-picker'), results=picker.querySelector('.hass-results');
        const query=searchText(input.value), words=query.split(' ').filter(Boolean);
        const matches=hassEntities.filter(entity=>words.every(word=>entity.search.includes(word)))
            .sort((a,b)=>Number(searchText(b.entity_id)===query)-Number(searchText(a.entity_id)===query));
        results.innerHTML=matches.slice(0,40).map(entity=>`<button type="button" role="option" aria-selected="false" class="list-group-item list-group-item-action" data-command="select-hass" data-entity-id="${escape(entity.entity_id)}" ${entity.supported?'':'disabled'}>
            <strong>${escape(entity.name)}</strong><br><span class="small">${escape(entity.entity_id)} — ${escape(entity.state)}${entity.supported?'':' — loại thực thể chưa hỗ trợ trong kịch bản'}</span></button>`).join('')+
            (matches.length>40?'<div class="list-group-item small text-muted">Hiển thị 40 kết quả đầu. Nhập thêm tên hoặc Entity ID để thu hẹp.</div>':'')+
            (matches.length?'':'<div class="list-group-item small text-muted">Không có kết quả. Bạn vẫn có thể nhập Entity ID trực tiếp.</div>');
        results.classList.remove('d-none'); input.setAttribute('aria-expanded','true');
        const entity=hassById.get(input.value.trim());
        picker.querySelector('.hass-entity-name').textContent=entity?entity.name+' — '+entity.state:'';
    }
    function hideHassResults(picker) {
        picker.querySelector('.hass-results').classList.add('d-none');
        picker.querySelector('[data-field="entity_id"]').setAttribute('aria-expanded','false');
    }
    function syncHassControls(container,step) {
        const allowed=options.hassActions[step.entity_id.split('.')[0]];
        if (allowed && !allowed.includes(step.action)) { step.action=allowed[0]; step.value=null; }
        container.querySelector('.hass-controls').innerHTML=hassControlFields(step);
    }
    function chooseHass(button) {
        const container=button.closest('[data-step]');
        const routine=data.routines[Number(button.closest('[data-routine]').dataset.routine)];
        const step=routine.steps[Number(container.dataset.step)];
        step.entity_id=button.dataset.entityId;
        const input=container.querySelector('[data-field="entity_id"]');
        input.value=step.entity_id;
        syncHassControls(container,step); setDirty(); updateHassUi();
        input.focus(); hideHassResults(input.closest('.hass-picker'));
    }
    function hassControlFields(step) {
        const domain=String(step.entity_id || '').split('.')[0];
        const allowed=options.hassActions[domain] || ['turn_on','turn_off','turn_setup'];
        const actions=Object.fromEntries(allowed.map(key=>[key,hassLabels[key] || key]));
        if (!actions[step.action]) actions[step.action]='Thao tác đã lưu: '+step.action;
        return `<label class="form-label">Thao tác</label><select class="form-select mb-2" data-field="action">${selectOptions(actions,step.action)}</select>
            ${step.action==='turn_setup'?`<label class="form-label">${domain==='climate'?'Nhiệt độ (5–40 °C)':'Mức đặt (0–100 %)'}</label><input class="form-control" data-field="value" type="number" min="${domain==='climate'?5:0}" max="${domain==='climate'?40:100}" step="0.1" value="${escape(step.value)}">`:''}`;
    }
    function localTrackOptions(selected,query='') {
        const words=searchText(query).split(' ').filter(Boolean);
        const matches=localFiles.filter(file=>words.every(word=>searchText(file.name).includes(word)));
        const choices=matches.slice(0,250);
        const current=localFiles.find(file=>file.path===selected);
        if (selected && !choices.some(file=>file.path===selected)) choices.unshift(current || {path:selected,name:'Bài đã lưu (không có trong danh sách): '+selected});
        return '<option value="">Chọn bài nhạc Local…</option>'+choices.map(file=>`<option value="${escape(file.path)}" ${file.path===selected?'selected':''}>${escape(file.name)}</option>`).join('');
    }
    function updateLocalUi() {
        document.querySelectorAll('.routine-local-info').forEach(node=>{node.textContent=localMessage;});
        document.querySelectorAll('[data-command="refresh-local"]').forEach(button=>{button.disabled=localLoading;button.textContent=localLoading?'Đang đọc…':'Tải lại danh sách nhạc';});
        document.querySelectorAll('.routine-local-track').forEach(select=>{
            const container=select.closest('[data-step]');
            const routine=data.routines[Number(select.closest('[data-routine]').dataset.routine)];
            const step=routine.steps[Number(container.dataset.step)];
            select.innerHTML=localTrackOptions(step.path || '',container.querySelector('.routine-local-search').value);
        });
    }
    async function loadLocalFiles() {
        if (localLoading) return;
        localLoading=true; localMessage='Đang đọc thư mục nhạc Local…'; updateLocalUi();
        try {
            const response=await fetch(options.localMedia,{credentials:'same-origin',cache:'no-store'});
            const result=await response.json();
            if (!response.ok || !result.success || !Array.isArray(result.files)) throw new Error(result.message || 'Không đọc được danh sách nhạc Local');
            localFiles=result.files;
            localMessage=`Có ${localFiles.length} bài nhạc. Tìm theo tên; hiển thị tối đa 250 kết quả mỗi lần.`;
        } catch(error) { localMessage=error.message+(localFiles.length?' Danh sách đang có được giữ nguyên.':''); }
        finally { localLoaded=true; localLoading=false; updateLocalUi(); }
    }
    function actionMediaFields(step) {
        if (step.action==='play_local_file') return `<div class="mt-2"><label class="form-label">Bài nhạc Local</label>
            <div class="input-group mb-2"><input class="form-control routine-local-search" placeholder="Tìm theo tên bài nhạc" aria-label="Tìm bài nhạc Local">
            <button class="btn btn-outline-primary" type="button" data-command="refresh-local">Tải lại danh sách nhạc</button></div>
            <select class="form-select routine-local-track" data-field="path" aria-label="Bài nhạc Local">${localTrackOptions(step.path || '')}</select>
            <div class="form-text routine-local-info">${escape(localMessage)}</div>
            <label class="form-label mt-2">Tên hiển thị (không bắt buộc)</label><input class="form-control" data-field="title" maxlength="200" value="${escape(step.title || '')}"></div>`;
        if (urlActions.includes(step.action)) {
            return `<div class="mt-2"><label class="form-label">Link / URL phát media</label><input class="form-control" data-field="url" type="url" maxlength="8192" placeholder="URL trực tiếp, stream, YouTube, Zing MP3 hoặc NhacCuaTui" value="${escape(step.url || '')}">
                <label class="form-label mt-2">Tên hiển thị (không bắt buộc)</label><input class="form-control" data-field="title" maxlength="200" value="${escape(step.title || '')}">
                <div class="form-text">Hỗ trợ HTTP/HTTPS, link âm thanh trực tiếp, stream, YouTube, Zing MP3 và NhacCuaTui. VBot lấy link phát mới mỗi lần chạy. Bước hoàn tất khi media bắt đầu phát.</div></div>`;
        }
        return '';
    }
    function stepFields(step) {
        if (step.type==='wait') return `<label class="form-label">Chờ (giây)</label><input class="form-control" data-field="seconds" type="number" min="0" max="120" step="0.1" value="${escape(step.seconds)}">`;
        if (step.type==='volume') return `<label class="form-label">Âm lượng (%)</label><input class="form-control" data-field="value" type="number" min="0" max="100" value="${escape(step.value)}">`;
        if (step.type==='led_brightness') return `<label class="form-label">Độ sáng LED ở loa (%)</label><input class="form-control" data-field="value" type="number" min="0" max="100" value="${escape(step.value)}"><div class="form-text">0% tắt sáng, 100% sáng tối đa. Đèn LED phải được bật trong cấu hình loa.</div>`;
        if (step.type==='speak') return `<label class="form-label">Nội dung thông báo</label><textarea class="form-control" data-field="text" rows="2" maxlength="500">${escape(step.text)}</textarea>`;
        if (step.type==='action') {
            const actions = {...options.actions};
            for (const legacy of urlActions.filter(action=>action!=='play_url')) delete actions[legacy];
            const selected = urlActions.includes(step.action) ? 'play_url' : step.action;
            if (!actions[selected]) actions[selected] = 'Nguồn/thao tác đã lưu: '+selected;
            return `<label class="form-label">Thao tác</label><select class="form-select" data-field="action">${selectOptions(actions,selected)}</select>${actionMediaFields(step)}`;
        }
        return `<div class="hass-controls mb-2">${hassControlFields(step)}</div>
            <div class="hass-picker mb-2"><label class="form-label">Entity ID trong Home Assistant</label>
            <div class="input-group"><input class="form-control" data-field="entity_id" maxlength="150" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" placeholder="Tìm tên thiết bị hoặc nhập Entity ID" value="${escape(step.entity_id)}">
            <button type="button" class="btn btn-outline-primary" data-command="fetch-hass">Tải dữ liệu Home Assistant</button></div>
            <div class="hass-results list-group mt-1 d-none" role="listbox" style="max-height:320px;overflow-y:auto"></div>
            <div class="hass-entity-name form-text"></div><div class="hass-cache-info form-text"></div></div>
            <div class="form-text">Dùng đúng Entity ID và danh sách thiết bị được phép của Home Assistant. Đặt giá trị áp dụng cho đèn, quạt, rèm hoặc điều hòa.</div>`;
    }
    function conditionFields(routine) {
        const condition=routine.condition || {mode:'none'};
        const modes={none:'Không sử dụng điều kiện',time:'Chỉ chạy trong khung giờ',date_time:'Từ ngày đến ngày, trong khung giờ'};
        return `<div class="col-12"><label class="form-label">Điều kiện chạy kịch bản</label><select class="form-select" data-cfield="mode">${selectOptions(modes,condition.mode)}</select>
            ${condition.mode!=='none'?`<div class="row g-2 mt-1">
                ${condition.mode==='date_time'?`<div class="col-md-6"><label class="form-label">Từ ngày</label><input class="form-control" type="date" data-cfield="start_date" value="${escape(condition.start_date || '')}"></div><div class="col-md-6"><label class="form-label">Đến ngày</label><input class="form-control" type="date" data-cfield="end_date" value="${escape(condition.end_date || '')}"></div>`:''}
                <div class="col-md-6"><label class="form-label">Từ giờ (24 giờ)</label><input class="form-control" data-cfield="start_time" maxlength="5" placeholder="08:00" value="${escape(condition.start_time || '')}"></div>
                <div class="col-md-6"><label class="form-label">Đến giờ (24 giờ)</label><input class="form-control" data-cfield="end_time" maxlength="5" placeholder="22:00" value="${escape(condition.end_time || '')}"></div>
                <div class="col-12 form-text">Định dạng HH:MM, theo giờ hệ thống VBot. Hỗ trợ qua nửa đêm, ví dụ 22:00–06:00; giờ đầu/cuối được tính cả phút đó. Hai giờ phải khác nhau. Ngày bắt đầu/kết thúc được tính cả ngày đó; khung giờ áp dụng hằng ngày trong khoảng đã chọn.</div></div>`:''}</div>`;
    }
    const expandedRoutines = new Set();
    let routineQuery = '';
    function applyRoutineSearch() {
        let count=0;
        document.querySelectorAll('#routineCards [data-routine]').forEach(card=>{
            const routine=data.routines[Number(card.dataset.routine)];
            const matches=searchText(JSON.stringify(routine)).includes(searchText(routineQuery));
            card.hidden=!matches;if(matches)count++;
        });
        byId('routineSearchEmpty').hidden=!routineQuery || count>0;
    }
    function render() {
        byId('routineCards').innerHTML = data.routines.map((routine, index) => `<div class="card" data-routine="${index}"><div class="card-body p-0"><div class="alert alert-primary mb-0" role="alert">
            <div class="d-flex align-items-center gap-2"><h2 class="h5 flex-grow-1 mb-0"><button type="button" class="card-title accordion-button mb-0 ${expandedRoutines.has(routine.id)?'':'collapsed'}" data-command="toggle" aria-expanded="${expandedRoutines.has(routine.id)}" aria-controls="routineBody${index}"><span class="routine-heading">${escape(routine.name.trim() || 'Kịch bản '+(index+1))}</span><span class="routine-enabled-status badge ${routine.enabled?'bg-success':'bg-secondary'} ms-3" aria-live="polite">${routine.enabled?'Đang bật':'Đang tắt'}</span></button></h2><button type="button" class="btn btn-outline-danger btn-sm" data-command="delete">Xóa kịch bản</button></div>
            <div id="routineBody${index}" class="collapse ${expandedRoutines.has(routine.id)?'show':''} pt-3">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Tên kịch bản</label><input class="form-control" data-rfield="name" maxlength="100" value="${escape(routine.name)}"></div>
                <div class="col-md-6"><label class="form-label">Câu gọi (mỗi dòng một câu)</label><textarea class="form-control" data-rfield="triggers" maxlength="2010" rows="3">${escape(routine.triggers.join('\n'))}</textarea></div>
                <div class="col-12 d-flex flex-wrap gap-4"><div class="form-check form-switch"><input id="routineEnabled${index}" type="checkbox" role="switch" class="form-check-input border-success" data-rfield="enabled" ${routine.enabled?'checked':''}><label class="form-check-label" for="routineEnabled${index}">Kích hoạt</label></div>
                <div class="form-check form-switch"><input id="routineStopOnError${index}" type="checkbox" role="switch" class="form-check-input border-success" data-rfield="stop_on_error" ${routine.stop_on_error?'checked':''}><label class="form-check-label" for="routineStopOnError${index}">Dừng khi một bước lỗi</label></div></div>
                ${conditionFields(routine)}
            </div>
            <div class="mt-3">${routine.steps.map((step, number) => `<div class="border rounded p-3 mb-2" data-step="${number}">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2"><strong>Bước ${number+1}</strong>
                    <select class="form-select w-auto" data-field="type" aria-label="Loại bước">${selectOptions(types,step.type)}</select>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-command="up" ${number===0?'disabled':''} aria-label="Đưa bước lên">↑</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-command="down" ${number===routine.steps.length-1?'disabled':''} aria-label="Đưa bước xuống">↓</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-command="delete-step">Xóa bước</button></div>${stepFields(step)}
                </div>`).join('')}</div>
            <div class="d-flex gap-2 mt-3"><button type="button" class="btn btn-outline-primary" data-command="add-step">Thêm bước</button>
                <button type="button" class="btn btn-warning" data-command="run">Chạy thử bản đã lưu</button></div>
        </div></div></div></div>`).join('') || '<div class="alert alert-secondary">Chưa có kịch bản. Nhấn “Thêm kịch bản” để bắt đầu.</div>';
        applyRoutineSearch();
        updateHassUi();
        updateLocalUi();
        if (!localLoaded && !localLoading && data.routines.some(routine=>routine.steps.some(step=>step.type==='action' && step.action==='play_local_file'))) loadLocalFiles();
    }
    function readInput(event) {
        if (busy) return;
        const target = event.target, card = target.closest('[data-routine]');
        if (!card) return;
        const routine = data.routines[Number(card.dataset.routine)];
        if (target.dataset.cfield) {
            const key=target.dataset.cfield;
            if (key==='mode') {
                const previous=routine.condition || {};
                routine.condition=target.value==='none'?{mode:'none'}:{mode:target.value,start_time:previous.start_time || '08:00',end_time:previous.end_time || '22:00',
                    ...(target.value==='date_time'?{start_date:previous.start_date || '',end_date:previous.end_date || ''}:{})};
                render();
            } else { routine.condition=routine.condition || {mode:'none'};routine.condition[key]=target.value.trim(); }
            setDirty();return;
        }
        if (target.classList.contains('routine-local-search')) { updateLocalUi(); return; }
        if (target.dataset.rfield) {
            const key = target.dataset.rfield;
            routine[key] = target.type==='checkbox'?target.checked:key==='triggers'?target.value.split('\n').map(x=>x.trim().normalize('NFC')).filter(Boolean):target.value.normalize('NFC');
            if(key==='enabled') {
                const status=card.querySelector('.routine-enabled-status');
                if(status) {
                    status.textContent=routine.enabled?'Đang bật':'Đang tắt';
                    status.classList.toggle('bg-success',routine.enabled);
                    status.classList.toggle('bg-secondary',!routine.enabled);
                }
            }
            if(key==='name') {
                const heading=card.querySelector('.routine-heading');
                if(heading)heading.textContent=routine.name.trim() || 'Kịch bản '+(Number(card.dataset.routine)+1);
            }
        } else if (target.dataset.field) {
            const index = Number(target.closest('[data-step]').dataset.step), key = target.dataset.field;
            if (key==='type') { routine.steps[index] = defaults(target.value); render(); }
            else {
                const step = routine.steps[index];
                step[key] = target.type==='number'?(target.value===''?null:Number(target.value)):
                    ['path','url'].includes(key)?target.value.trim():target.value.trim().normalize('NFC');
                if (step.type==='home_assistant' && key==='entity_id') showHassResults(target);
                if (step.type==='home_assistant' && key==='action') {
                    step.value = step.action==='turn_setup'?(step.entity_id.startsWith('climate.')?25:50):null;
                    render();
                }
                if (step.type==='action' && key==='action') {
                    if (step.action==='play_local_file') { step.path=step.path || ''; step.title=step.title || ''; }
                    if (urlActions.includes(step.action)) { step.url=step.url || ''; step.title=step.title || ''; }
                    render();
                }
            }
        } else return;
        setDirty();
    }
    async function load() {
        if (busy) return;
        if (dirty && !confirm('Tải lại sẽ bỏ thay đổi chưa lưu. Bạn muốn tiếp tục?')) return;
        busy = true; byId('routineCards').inert=true; byId('routineAdd').disabled=true;
        try { const result = await post('list'); data = result.data; revision = result.revision; dirty=false; ready=true; render(); notice('Đã tải cấu hình. Lưu trước khi chạy thử.'); }
        catch (error) { notice(error.message,false); }
        finally { busy=false; byId('routineCards').inert=false; byId('routineAdd').disabled=false; }
    }
    async function save() {
        if (!ready || busy) return;
        busy=true; byId('routineCards').inert=true; byId('routineAdd').disabled=true;
        try { const result=await post('save',{revision,data:JSON.stringify(data)}); data=result.data; revision=result.revision; dirty=false; render(); notice(result.message); }
        catch (error) { notice(error.message,false); }
        finally { busy=false; byId('routineCards').inert=false; byId('routineAdd').disabled=false; }
    }
    function renderStatus(state) {
        byId('routineRuntime').innerHTML = `<p class="${state.status==='failed'?'text-danger':''}">${escape(state.message)}${state.running?` — bước ${escape(state.step)}/${escape(state.total)}`:''}</p>
            <ol>${(state.steps || []).map(step=>`<li class="${step.success?'text-success':'text-danger'}">Bước ${escape(step.step)}: ${escape(step.message)}</li>`).join('')}</ol>`;
    }
    async function refreshStatus() {
        try { await api(); }
        catch (error) { byId('routineRuntime').textContent='Không lấy được trạng thái. Kiểm tra VBot đang chạy và kết nối API. '+error.message; }
    }
    byId('routineCards').addEventListener('input',readInput);
    byId('routineCards').addEventListener('change',event => {
        const target=event.target;
        if (busy || target.dataset.field!=='entity_id') return;
        const routine=data.routines[Number(target.closest('[data-routine]').dataset.routine)];
        const step=routine.steps[Number(target.closest('[data-step]').dataset.step)];
        syncHassControls(target.closest('[data-step]'),step);
    });
    byId('routineCards').addEventListener('focusin',event => {
        if (event.target.dataset.field==='entity_id') showHassResults(event.target);
    });
    byId('routineCards').addEventListener('keydown',event => {
        const target=event.target, picker=target.closest('.hass-picker');
        if (!picker) return;
        const choices=Array.from(picker.querySelectorAll('[data-command="select-hass"]:not(:disabled)'));
        if (event.key==='Escape') { hideHassResults(picker); return; }
        if (target.dataset.field==='entity_id' && (event.key==='Enter' || event.key==='ArrowDown') && choices.length) {
            event.preventDefault();
            if (event.key==='Enter') chooseHass(choices[0]); else choices[0].focus();
        } else if (target.dataset.command==='select-hass' && ['ArrowDown','ArrowUp'].includes(event.key)) {
            event.preventDefault();
            const next=choices.indexOf(target)+(event.key==='ArrowDown'?1:-1);
            (choices[next] || picker.querySelector('[data-field="entity_id"]')).focus();
        }
    });
    document.addEventListener('click',event => {
        if (!event.target.closest('.hass-picker')) document.querySelectorAll('.hass-picker').forEach(hideHassResults);
    });
    byId('routineCards').addEventListener('click',async event => {
        const button=event.target.closest('[data-command]');
        if (!button || busy || !ready) return;
        const index=Number(button.closest('[data-routine]').dataset.routine), routine=data.routines[index], command=button.dataset.command;
        const container=button.closest('[data-step]'), step=container?Number(container.dataset.step):-1;
        if(command==='toggle') {
            const open=!expandedRoutines.has(routine.id);
            if(open)expandedRoutines.add(routine.id);else expandedRoutines.delete(routine.id);
            button.classList.toggle('collapsed',!open);button.setAttribute('aria-expanded',String(open));
            byId('routineBody'+index).classList.toggle('show',open);return;
        }
        if (command==='fetch-hass') { await loadHassCache(true); return; }
        if (command==='refresh-local') { await loadLocalFiles(); return; }
        if (command==='select-hass') { chooseHass(button); return; }
        if (command==='run') {
            if (dirty) return notice('Hãy lưu thay đổi trước khi chạy thử.',false);
            if (!routine.enabled) return notice('Hãy bật kịch bản và lưu trước khi chạy.',false);
            if (runningTest) return notice('Đang chờ kết quả chạy thử.',false);
            runningTest=true; notice('Đang chạy thử '+routine.name+'…');
            const poll=setInterval(refreshStatus,1500);
            try { const result=await api({action:'run',id:routine.id}); notice(result.message); }
            catch (error) { notice(error.message+'. Xem trạng thái trước khi chạy lại.',false); }
            finally { clearInterval(poll); runningTest=false; await refreshStatus(); }
            return;
        }
        if (command==='delete') { if (!confirm('Xóa kịch bản này khỏi bản đang sửa?')) return; data.routines.splice(index,1); }
        if (command==='add-step') { if (routine.steps.length>=30) return notice('Tối đa 30 bước.',false); routine.steps.push(defaults('action')); }
        if (command==='delete-step') { if (routine.steps.length===1) return notice('Kịch bản cần ít nhất một bước.',false); routine.steps.splice(step,1); }
        if (command==='up' && step>0) [routine.steps[step-1],routine.steps[step]]=[routine.steps[step],routine.steps[step-1]];
        if (command==='down' && step<routine.steps.length-1) [routine.steps[step+1],routine.steps[step]]=[routine.steps[step],routine.steps[step+1]];
        setDirty(); render();
    });
    byId('routineAdd').addEventListener('click',() => {
        if (!ready || busy) return;
        if (data.routines.length>=50) return notice('Tối đa 50 kịch bản.',false);
        data.routines.push({id:'routine_'+Date.now().toString(36)+'_'+Math.random().toString(36).slice(2,8),name:'',enabled:false,stop_on_error:true,triggers:[],steps:[defaults('action')]});
        routineQuery='';byId('routineSearch').value='';
        expandedRoutines.add(data.routines[data.routines.length-1].id);
        setDirty(); render();
        const input=byId('routineCards').querySelector(`[data-routine="${data.routines.length-1}"] [data-rfield="name"]`);
        if(input){input.focus({preventScroll:true});input.scrollIntoView({behavior:'smooth',block:'center'});}
    });
    byId('routineSearch').addEventListener('input',event=>{routineQuery=event.target.value;applyRoutineSearch();});
    byId('routineSearchClear').addEventListener('click',()=>{routineQuery='';byId('routineSearch').value='';applyRoutineSearch();byId('routineSearch').focus();});
    for(const [id,open] of [['routineExpandAll',true],['routineCollapseAll',false]])byId(id).addEventListener('click',()=>{
        if(busy)return;
        data.routines.forEach(routine=>{if(open)expandedRoutines.add(routine.id);else expandedRoutines.delete(routine.id);});render();
    });
    byId('routineSave').addEventListener('click',save);
    byId('routineReload').addEventListener('click',load);
    byId('routineStatusRefresh').addEventListener('click',refreshStatus);
    byId('routineCancel').addEventListener('click',async () => { try { const result=await api({action:'cancel'}); notice(result.message); } catch (error) { notice(error.message,false); } });
    byId('routineExport').addEventListener('click',() => {
        if (!ready) return;
        const url=URL.createObjectURL(new Blob([JSON.stringify(data,null,2)],{type:'application/json'}));
        const link=document.createElement('a'); link.href=url; link.download='voice_routines.json'; link.click(); setTimeout(()=>URL.revokeObjectURL(url),1000);
    });
    window.voiceRoutineEditor={
        hasUnsavedChanges:()=>dirty, isBusy:()=>busy, getRevision:()=>revision,
        setExternalBusy(value){busy=value;byId('routineCards').inert=value;byId('routineAdd').disabled=value;},
        applySaved(result){data=result.data;revision=result.revision;dirty=false;ready=true;render();notice(result.message);}
    };
    window.addEventListener('beforeunload',event => { if (dirty) { event.preventDefault(); event.returnValue=''; } });
    loadHassCache();
    load();
})();
