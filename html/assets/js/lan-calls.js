(() => {
    'use strict';
    const options=window.lanCallOptions, byId=id=>document.getElementById(id);
    const escape=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let state={state:'idle',enabled:false,muted:false,peers:options.peers||[],message:'Đang đọc trạng thái…'},busy=false,errorMessage='',holdTimer=null;
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
            for(const [field,fallback] of [['control_port',5010],['audio_port',5011]]){
                const port=device.lan_calls?.[field]??device['lan_'+field];
                peer[field]=Number.isInteger(port) && port>=1024 && port<=65535?port:fallback;
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
            button.addEventListener('click',()=>{for(const field of tr.querySelectorAll('[data-peer]'))field.value=peer[field.dataset.peer];hideSuggestions();input.focus();hideSuggestions();});
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
        const tr=document.createElement('tr');
        tr.innerHTML=['id','name','host','control_port','audio_port'].map(key=>`<td><input class="form-control" data-peer="${key}" required value="${escape(peer[key]??({control_port:5010,audio_port:5011}[key]??''))}" ${key.includes('port')?'type="number" min="1024" max="65535"':'maxlength="100"'}></td>`).join('')+'<td><button type="button" class="btn btn-outline-danger">Xóa</button></td>';
        tr.querySelector('button').addEventListener('click',()=>tr.remove());byId('lanPeerRows').appendChild(tr);
        for(const input of tr.querySelectorAll('[data-peer="id"],[data-peer="name"],[data-peer="host"]')){
            input.autocomplete='off';input.setAttribute('aria-label',({id:'ID loa',name:'Tên loa',host:'IP loa'})[input.dataset.peer]);
            input.addEventListener('focus',()=>showSuggestions(input,tr));
            input.addEventListener('click',()=>showSuggestions(input,tr));
            input.addEventListener('input',()=>showSuggestions(input,tr,true));
            input.addEventListener('keydown',event=>{if(event.key==='Escape')hideSuggestions();if(event.key==='ArrowDown' && !suggestions.hidden){const first=suggestions.querySelector('button');if(first){event.preventDefault();first.focus();}}});
        }
    }
    options.peers.forEach(row);
    byId('lanAddPeer').addEventListener('click',()=>{if(byId('lanPeerRows').children.length<50)row();});
    byId('lanConfigForm').addEventListener('submit',()=>{
        byId('lan_peers').value=JSON.stringify([...byId('lanPeerRows').children].map(tr=>Object.fromEntries([...tr.querySelectorAll('input')].map(input=>[input.dataset.peer,input.dataset.peer.includes('port')?Number(input.value):input.value.trim()]))));
    });
    function render(next) {
        if(next.state!=='active' && holdTimer!==null){clearInterval(holdTimer);holdTimer=null;}
        state=next;byId('lanCallStatus').textContent=errorMessage || next.message+' — '+({idle:'Sẵn sàng',dialing:'Đang gọi',ringing:'Có cuộc gọi đến',connecting:'Đang kết nối âm thanh',active:'Đang trò chuyện'}[next.state]||next.state);
        const selected=byId('lanCallPeer').value;
        byId('lanCallPeer').innerHTML=(next.peers||[]).map(peer=>`<option value="${escape(peer.id)}">${escape(peer.name)}</option>`).join('');
        if((next.peers||[]).some(peer=>peer.id===selected))byId('lanCallPeer').value=selected;
        byId('lanDial').disabled=busy || !next.enabled || next.state!=='idle' || !next.peers?.length;
        byId('lanAccept').disabled=busy || next.state!=='ringing';byId('lanReject').disabled=busy || next.state!=='ringing';
        byId('lanEnd').disabled=busy || next.state==='idle';byId('lanMute').disabled=busy || next.state!=='active';
        byId('lanTalk').hidden=next.mode!=='push_to_talk';byId('lanTalk').disabled=next.state!=='active';
        byId('lanMute').hidden=next.mode==='push_to_talk';
        byId('lanMute').textContent=next.muted?'Bật microphone cuộc gọi':'Tắt microphone cuộc gọi';
    }
    async function request(payload) {
        if(options.active!==true)return;
        const headers={};if(options.apiKey)headers['VBot-API-Key']=options.apiKey;
        if(payload)headers['Content-Type']='application/json';
        const response=await fetch(options.api,{method:payload?'POST':'GET',credentials:'same-origin',headers,...(payload?{body:JSON.stringify(payload)}:{})});
        const result=await response.json();if(result.state)render(result.state);
        if(!response.ok || !result.success)throw Error(result.message||'Không kết nối được dịch vụ gọi LAN');
    }
    async function command(action,extra={}) {
        if(options.active!==true || busy)return;busy=true;errorMessage='';render(state);
        try{await request({action,...extra});}catch(error){errorMessage=error.message;}
        finally{busy=false;render(state);}
    }
    for(const [id,action] of [['lanDial','call'],['lanAccept','accept'],['lanReject','reject'],['lanEnd','end']])byId(id).addEventListener('click',()=>command(action,action==='call'?{peer:byId('lanCallPeer').value}:{}));
    byId('lanMute').addEventListener('click',()=>command('mute',{muted:!state.muted}));
    let talkChain=Promise.resolve();
    function talk(muted){if(options.active!==true)return;talkChain=talkChain.catch(()=>{}).then(()=>request({action:'mute',muted})).catch(error=>{byId('lanCallStatus').textContent=error.message;});}
    function release(){if(holdTimer!==null){clearInterval(holdTimer);holdTimer=null;}talk(true);}
    byId('lanTalk').addEventListener('pointerdown',event=>{if(options.active!==true || state.state!=='active')return;event.preventDefault();event.target.setPointerCapture(event.pointerId);talk(false);if(holdTimer===null)holdTimer=setInterval(()=>talk(false),750);});
    for(const type of ['pointerup','pointercancel','lostpointercapture'])byId('lanTalk').addEventListener(type,release);
    window.addEventListener('blur',()=>{if(state.mode==='push_to_talk' && state.state==='active')release();});
    let streamController=null,reconnectTimer=null,closed=false;
    async function connectEvents() {
        if(options.active!==true || closed || document.hidden || streamController!==null)return;
        if(reconnectTimer!==null){clearTimeout(reconnectTimer);reconnectTimer=null;}
        const controller=new AbortController();streamController=controller;
        try {
            const headers={Accept:'text/event-stream'};
            if(options.apiKey)headers['VBot-API-Key']=options.apiKey;
            const response=await fetch(options.api+'/events',{credentials:'same-origin',headers,signal:controller.signal});
            if(!response.ok || !response.body)throw Error('Không kết nối được SSE cuộc gọi');
            const reader=response.body.getReader(),decoder=new TextDecoder();let buffer='';
            try {
                while(!controller.signal.aborted) {
                    const {done,value}=await reader.read();if(done)break;
                    buffer+=decoder.decode(value,{stream:true});buffer=buffer.replace(/\r\n/g,'\n');
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
            if(!controller.signal.aborted && !closed)byId('lanCallStatus').textContent=error.message;
        } finally {
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
