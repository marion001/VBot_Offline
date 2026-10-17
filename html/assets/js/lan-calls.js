(() => {
    'use strict';
    const options=window.lanCallOptions, byId=id=>document.getElementById(id);
    const escape=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let state={state:'idle',enabled:false,muted:false,peers:[],message:'Đang đọc trạng thái…'},busy=false,errorMessage='',holdTimer=null;
    const suggestions=document.createElement('div');
    suggestions.className='list-group shadow';suggestions.hidden=true;
    Object.assign(suggestions.style,{position:'fixed',zIndex:'2000',maxHeight:'260px',overflowY:'auto'});
    document.body.appendChild(suggestions);
    const normalize=value=>String(value??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d').replace(/Đ/g,'D').toLowerCase();
    let suggestionInput=null;
    window.addEventListener('vbot:devices-scanned',event=>{
        const devices=event.detail?.devices;if(!Array.isArray(devices))return;
        const seen=new Set();
        options.discoveredPeers=devices.flatMap(device=>{
            if(!device || device.device_type!=='vbot_server')return [];
            const id=String(device.device_id||''),host=String(device.ip_address||'');
            if(!/^[A-Za-z0-9_-]{1,64}$/.test(id) || id===options.localId || seen.has(id))return [];
            if(!/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/.test(host) || !/^(\d{1,3}\.){3}\d{1,3}$/.test(host) || !host.split('.').every(part=>Number(part)<=255))return [];
            seen.add(id);const peer={id,name:String(device.user_name||'').trim()||id,host};
            for(const [field,fallback] of [['control_port',5010],['sip_port',5060]]){
                peer[field]=fallback;
            }
            return [peer];
        });
        byId('lanScanSummary').textContent=`Đã cập nhật ${options.discoveredPeers.length} loa VBot Python để gợi ý.`;
        if(suggestionInput && suggestionInput.isConnected)showSuggestions(suggestionInput,suggestionInput.closest('tr'),true);
    });
    function hideSuggestions(){suggestions.hidden=true;suggestionInput=null;}
    function showSuggestions(input,tr,filter=false){
        suggestionInput=input;suggestions.replaceChildren();
        const query=filter?normalize(input.value.trim()):'';
        const used=new Set([...byId('lanPeerRows').children].filter(other=>other!==tr).map(other=>other.querySelector('[data-peer="id"]').value.trim()));
        const matches=(options.discoveredPeers||[]).filter(peer=>!used.has(peer.id) && normalize(`${peer.name} ${peer.id} ${peer.host}`).includes(query));
        for(const peer of matches){
            const button=document.createElement('button');button.type='button';button.className='list-group-item list-group-item-action';
            button.textContent=`${peer.name} — ${peer.id} — ${peer.host}`;
            button.addEventListener('mousedown',event=>event.preventDefault());
            button.addEventListener('click',()=>{
                const changed=tr.querySelector('[data-peer="id"]').value.trim()!==peer.id;
                for(const field of tr.querySelectorAll('[data-peer]')){
                    if(field.dataset.peer==='auto_accept'){if(changed)field.value='false';}
                    else field.value=peer[field.dataset.peer];
                }
                validatePeerRows();
                hideSuggestions();input.focus();hideSuggestions();
            });
            suggestions.appendChild(button);
        }
        if(!matches.length){const empty=document.createElement('div');empty.className='list-group-item text-muted';empty.textContent='Không có loa phù hợp trong dữ liệu quét. Bạn có thể nhập thủ công.';suggestions.appendChild(empty);}
        const rect=input.getBoundingClientRect();
        Object.assign(suggestions.style,{top:`${rect.bottom}px`,left:`${rect.left}px`,width:`${Math.min(Math.max(rect.width,440),window.innerWidth-rect.left-12)}px`});
        suggestions.hidden=false;
    }
    document.addEventListener('mousedown',event=>{if(event.target!==suggestionInput && !suggestions.contains(event.target))hideSuggestions();});
    document.addEventListener('focusin',event=>{if(event.target!==suggestionInput && !suggestions.contains(event.target))hideSuggestions();});
    window.addEventListener('resize',hideSuggestions);window.addEventListener('scroll',hideSuggestions,true);
    function row(peer={}) {
        peer={...peer,control_port:5010,sip_port:5060};
        const tr=document.createElement('tr');
        tr.innerHTML=['id','name','host','control_port','sip_port'].map(key=>`<td><input class="form-control" data-peer="${key}" required value="${escape(peer[key]??'')}" ${key.includes('port')?'type="number" readonly':'maxlength="100"'}></td>`).join('')+
            `<td><select class="form-select" data-peer="auto_accept" aria-label="Nhận cuộc gọi từ loa này"><option value="false" ${peer.auto_accept===true?'':'selected'}>Nhấn nút để nhận</option><option value="true" ${peer.auto_accept===true?'selected':''}>Tự động nhận</option></select></td>`+
            '<td><button type="button" class="btn btn-outline-danger">Xóa</button></td>';
        if(peer.auto_accept!==undefined && typeof peer.auto_accept!=='boolean') {
            const select=tr.querySelector('[data-peer="auto_accept"]');
            const invalid=document.createElement('option');invalid.value='';invalid.textContent='Giá trị không hợp lệ — chọn lại';
            select.prepend(invalid);select.value='';
        }
        tr.querySelector('button').addEventListener('click',()=>{tr.remove();validatePeerRows();});byId('lanPeerRows').appendChild(tr);
        tr.addEventListener('input',()=>validatePeerRows());tr.addEventListener('change',()=>validatePeerRows());
        for(const input of tr.querySelectorAll('[data-peer="id"],[data-peer="name"],[data-peer="host"]')){
            input.autocomplete='off';input.setAttribute('aria-label',({id:'ID loa',name:'Tên loa',host:'IP loa'})[input.dataset.peer]);
            input.addEventListener('focus',()=>showSuggestions(input,tr));
            input.addEventListener('click',()=>showSuggestions(input,tr));
            input.addEventListener('input',()=>showSuggestions(input,tr,true));
            input.addEventListener('keydown',event=>{if(event.key==='Escape')hideSuggestions();if(event.key==='ArrowDown' && !suggestions.hidden){const first=suggestions.querySelector('button');if(first){event.preventDefault();first.focus();}}});
        }
    }
    options.peers.forEach(row);
    const readPeerRows=()=>[...byId('lanPeerRows').children].map(tr=>Object.fromEntries([...tr.querySelectorAll('input,select')].map(input=>[input.dataset.peer,input.dataset.peer==='auto_accept'?(input.value==='true'?true:input.value==='false'?false:null):input.dataset.peer.includes('port')?Number(input.value):input.value.trim()])));
    function validatePeerRows(initial={}) {
        const rows=[...byId('lanPeerRows').children],errors=rows.map((_,i)=>({...initial[i]})),ids=new Map(),hosts=new Map();
        rows.forEach((tr,i)=>{
            const value=key=>tr.querySelector(`[data-peer="${key}"]`).value.trim();
            const id=value('id'),host=value('host'),name=value('name');
            if(!/^[A-Za-z0-9_-]{1,64}$/.test(id))errors[i].id='ID cần 1–64 ký tự: chữ, số, _ hoặc -';
            else if(id===options.localId)errors[i].id='ID trùng với chính loa hiện tại; hãy nhập ID loa đích';
            else if(ids.has(id)){errors[i].id='ID trùng với dòng khác';errors[ids.get(id)].id='ID trùng với dòng khác';}else ids.set(id,i);
            if(!/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/.test(host) || !/^(\d{1,3}\.){3}\d{1,3}$/.test(host) || !host.split('.').every(part=>Number(part)<=255 && String(Number(part))===part))errors[i].host='Nhập IPv4 riêng trong mạng LAN';
            else if(hosts.has(host)){errors[i].host='IP trùng với dòng khác';errors[hosts.get(host)].host='IP trùng với dòng khác';}else hosts.set(host,i);
            if(!name || [...name].length>100)errors[i].name='Tên loa cần 1–100 ký tự';
            if(!['true','false'].includes(value('auto_accept')))errors[i].auto_accept='Hãy chọn lại cách nhận cuộc gọi';
        });
        let count=0,first=null;
        rows.forEach((tr,i)=>tr.querySelectorAll('[data-peer]').forEach(input=>{
            const message=errors[i][input.dataset.peer] || '';
            input.classList.toggle('border-danger',!!message);input.classList.toggle('is-invalid',!!message);
            input.setAttribute('aria-invalid',String(!!message));input.setCustomValidity(message);
            let feedback=input.parentElement.querySelector('.invalid-feedback');
            if(!feedback){feedback=document.createElement('div');feedback.className='invalid-feedback';input.parentElement.append(feedback);}
            feedback.textContent=message;
            if(message){count++;if(!first)first=input;}
        }));
        const notice=byId('lanPeerValidation');notice.hidden=!count;notice.textContent=count?`Có ${count} ô cần sửa. Kiểm tra các ô viền đỏ bên trên.`:'';
        return first;
    }
    validatePeerRows(options.peerErrors || {});
    let savedPeerRows=JSON.stringify(readPeerRows()),externalBusy=false,submitting=false;
    window.addEventListener('pageshow',()=>{submitting=false;});
    window.lanPeerEditor={
        getRevision:()=>byId('lanPeerRevision').value,
        hasUnsavedChanges:()=>JSON.stringify(readPeerRows())!==savedPeerRows,
        isBusy:()=>externalBusy || submitting,
        setExternalBusy:value=>{externalBusy=value;byId('lanConfigForm').querySelectorAll('input,select,button').forEach(control=>{control.disabled=value;});},
        applySaved:result=>{
            hideSuggestions();byId('lanPeerRows').replaceChildren();result.data.forEach(row);
            byId('lanPeerRevision').value=result.revision;
            byId('lan_peers').value=JSON.stringify(result.data);
            savedPeerRows=JSON.stringify(readPeerRows());
            validatePeerRows();
        }
    };
    byId('lanAddPeer').addEventListener('click',()=>{if(byId('lanPeerRows').children.length<50){row();validatePeerRows();}});
    byId('lanConfigForm').addEventListener('submit',event=>{
        if(externalBusy){event.preventDefault();return;}
        const invalid=validatePeerRows();
        if(invalid){event.preventDefault();invalid.focus();invalid.reportValidity();return;}
        byId('lan_peers').value=JSON.stringify(readPeerRows());submitting=true;
    });
    function render(next) {
        if(next.state!=='active' && holdTimer!==null){clearInterval(holdTimer);holdTimer=null;}
        state=next;byId('lanCallStatus').textContent=errorMessage || next.message+' — '+({idle:'Sẵn sàng',dialing:'Đang gọi',ringing:'Có cuộc gọi đến',connecting:'Đang kết nối âm thanh',active:'Đang trò chuyện'}[next.state]||next.state);
        if(next.browser_active && !errorMessage)byId('lanCallStatus').textContent='Loa đang có cuộc gọi từ trình duyệt';
        const selected=byId('lanCallPeer').value;
        byId('lanCallPeer').innerHTML=(next.peers||[]).map(peer=>`<option value="${escape(peer.id)}">${escape(peer.name)}</option>`).join('');
        if((next.peers||[]).some(peer=>peer.id===selected))byId('lanCallPeer').value=selected;
        byId('lanDial').disabled=busy || !next.enabled || next.browser_active || next.state!=='idle' || !next.peers?.length;
        byId('lanAccept').disabled=busy || next.state!=='ringing';byId('lanReject').disabled=busy || next.state!=='ringing';
        byId('lanEnd').disabled=busy || next.state==='idle';byId('lanMute').disabled=busy || next.state!=='active';
        byId('lanTalk').hidden=next.mode!=='push_to_talk';byId('lanTalk').disabled=next.state!=='active';
        byId('lanMute').hidden=next.mode==='push_to_talk';
        byId('lanMute').textContent=next.muted?'Bật microphone cuộc gọi':'Tắt microphone cuộc gọi';
    }
    async function request(payload) {
        if(options.active!==true)return;
        const controller=new AbortController();
        const timeout=setTimeout(()=>controller.abort(),15000);
        try {
        const headers={};if(options.apiKey)headers['VBot-API-Key']=options.apiKey;
        if(payload)headers['Content-Type']='application/json';
        const response=await fetch(options.api,{method:payload?'POST':'GET',credentials:'same-origin',headers,signal:controller.signal,...(payload?{body:JSON.stringify(payload)}:{})});
        const result=await response.json();if(result.state)render(result.state);
        if(!response.ok || !result.success)throw Error(result.message||'Không kết nối được dịch vụ gọi LAN');
        } catch(error) {
            if(controller.signal.aborted)throw Error('Dịch vụ gọi LAN phản hồi quá chậm; hãy kiểm tra trạng thái trước khi thử lại.');
            throw error;
        } finally {clearTimeout(timeout);}
    }
    async function command(action,extra={}) {
        if(options.active!==true || busy)return;busy=true;errorMessage='';render(state);
        try{await request({action,...extra});}catch(error){errorMessage=error.message;}
        finally{busy=false;render(state);}
    }
    for(const [id,action] of [['lanDial','call'],['lanAccept','accept'],['lanReject','reject'],['lanEnd','end']])byId(id).addEventListener('click',()=>command(action,action==='call'?{peer:byId('lanCallPeer').value}:{}));
    byId('lanMute').addEventListener('click',()=>command('mute',{muted:!state.muted}));
    let talkPending=null,talkRunning=false;
    async function talk(muted){
        if(options.active!==true || state.state!=='active')return;
        talkPending={muted,callId:state.call_id};
        if(talkRunning)return;
        talkRunning=true;
        try {
            while(talkPending){
                const next=talkPending;talkPending=null;
                if(state.state!=='active' || state.call_id!==next.callId)continue;
                try{await request({action:'mute',muted:next.muted});}
                catch(error){byId('lanCallStatus').textContent=error.message;}
            }
        } finally {talkRunning=false;}
    }
    function release(){if(holdTimer!==null){clearInterval(holdTimer);holdTimer=null;}talk(true);}
    byId('lanTalk').addEventListener('pointerdown',event=>{if(options.active!==true || state.state!=='active')return;event.preventDefault();event.target.setPointerCapture(event.pointerId);talk(false);if(holdTimer===null)holdTimer=setInterval(()=>talk(false),750);});
    for(const type of ['pointerup','pointercancel','lostpointercapture'])byId('lanTalk').addEventListener(type,release);
    window.addEventListener('blur',()=>{if(state.mode==='push_to_talk' && state.state==='active')release();});
    let streamController=null,reconnectTimer=null,closed=false;
    async function connectEvents() {
        if(options.active!==true || closed || document.hidden || streamController!==null)return;
        if(reconnectTimer!==null){clearTimeout(reconnectTimer);reconnectTimer=null;}
        const controller=new AbortController();streamController=controller;
        let idleTimer=null,idleTimedOut=false;
        const armIdleTimeout=()=>{
            if(idleTimer!==null)clearTimeout(idleTimer);
            idleTimer=setTimeout(()=>{idleTimedOut=true;controller.abort();},30000);
        };
        armIdleTimeout();
        try {
            const headers={Accept:'text/event-stream'};
            if(options.apiKey)headers['VBot-API-Key']=options.apiKey;
            const response=await fetch(options.api+'/events',{credentials:'same-origin',headers,signal:controller.signal});
            if(!response.ok || !response.body)throw Error('Không kết nối được SSE cuộc gọi');
            const reader=response.body.getReader(),decoder=new TextDecoder();let buffer='';
            try {
                while(!controller.signal.aborted) {
                    const {done,value}=await reader.read();if(done)break;
                    if(controller.signal.aborted || streamController!==controller)break;
                    armIdleTimeout();
                    buffer+=decoder.decode(value,{stream:true});buffer=buffer.replace(/\r\n/g,'\n');
                    if(buffer.length>131072)throw Error('Dữ liệu SSE cuộc gọi vượt giới hạn');
                    let boundary;
                    while((boundary=buffer.indexOf('\n\n'))!==-1) {
                        const event=buffer.slice(0,boundary);buffer=buffer.slice(boundary+2);
                        const lines=event.split('\n').filter(line=>line.startsWith('data:')).map(line=>line.slice(5).trimStart());
                        if(lines.length){const result=JSON.parse(lines.join('\n'));if(result.success && result.state){errorMessage='';render(result.state);}}
                    }
                }
            } finally {await reader.cancel().catch(()=>{});reader.releaseLock();}
            if(!controller.signal.aborted)throw Error('Kết nối trạng thái đã ngắt; đang kết nối lại…');
        } catch(error) {
            if((!controller.signal.aborted || idleTimedOut) && !closed && streamController===controller)
                byId('lanCallStatus').textContent=idleTimedOut?'SSE cuộc gọi không nhận dữ liệu; đang kết nối lại…':error.message;
        } finally {
            if(idleTimer!==null)clearTimeout(idleTimer);
            if(streamController===controller){streamController=null;if(!closed && !document.hidden)reconnectTimer=setTimeout(connectEvents,3000);}
        }
    }
    function disconnectEvents(){if(reconnectTimer!==null){clearTimeout(reconnectTimer);reconnectTimer=null;}if(streamController){const controller=streamController;streamController=null;controller.abort();}}
    document.addEventListener('visibilitychange',()=>{disconnectEvents();if(document.hidden && state.mode==='push_to_talk' && state.state==='active')release();if(!document.hidden)connectEvents();});
    window.addEventListener('pagehide',()=>{closed=true;disconnectEvents();if(holdTimer!==null){clearInterval(holdTimer);holdTimer=null;}});
    window.addEventListener('pageshow',()=>{if(closed){closed=false;connectEvents();}});
    render(state);
    // One initial snapshot makes the controls ready even when an SSE proxy buffers output.
    // Later state changes continue to arrive through SSE, without periodic GET requests.
    if(options.active===true){
        request().catch(error=>{errorMessage=error.message;render(state);}).finally(connectEvents);
    }else{
        render({...state,message:'Gọi LAN đã tắt. Bật tính năng trong Config.php và khởi động lại VBot để sử dụng.'});
        byId('lanCallStatus').textContent=state.message;
        byId('lanCallPeer').disabled=true;
    }
})();
