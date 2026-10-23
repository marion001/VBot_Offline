(() => {
    'use strict';
    if(!document.getElementById('speaker-eq-controls'))return;
    const frequencyFields=['bass_db','low_mid_db','mid_db','high_mid_db','treble_db','air_db'];
    const fields=[...frequencyFields,'preamp_db','wet_percent'];
    const modes={flat:[0,0,0,0,0,0],bass:[4,2,0,-1,-1,0],vocal:[-2,-1,0,2,2,0],clear:[-2,-1,0,1,3,2],warm:[3,2,0,-1,-2,-1],rock:[4,2,-1,1,3,2],night:[-3,-2,0,2,1,-2]};
    const modeNames={flat:'Flat — Cân bằng',bass:'Bass Boost — Âm trầm',vocal:'Vocal — Giọng nói',clear:'Clarity — Chi tiết',warm:'Warm — Ấm áp',rock:'Rock — Sôi động',night:'Night — Nghe ban đêm'};
    let active=!!window.vbotEqualizerActive;
    const byId=id=>document.getElementById(id),get=name=>byId('speaker_eq_'+name);
    const status=byId('speaker-eq-status'),preset=byId('speaker-eq-preset'),nameInput=byId('speaker-eq-name');
    let state=window.vbotEqualizerState,timer=null,busy=false,revision=0;
    const queue=[];
    let externalRevision=null,latestExternal=null,localChangedAt=0,externalTimer=null;
    function receiveExternal(snapshot){
        if(!snapshot || !snapshot.state || !snapshot.revision || snapshot.revision===externalRevision)return;
        latestExternal=snapshot;
        // Let local slider writes and text edits finish; never echo SSE into a POST.
        if(externalTimer!==null){clearTimeout(externalTimer);externalTimer=null;}
        if(document.activeElement===nameInput)return;
        if(busy||timer!==null||queue.length||Date.now()-localChangedAt<1200){
            externalTimer=setTimeout(()=>{externalTimer=null;if(latestExternal)receiveExternal(latestExternal);},Math.max(150,1200-(Date.now()-localChangedAt)));
            return;
        }
        externalRevision=snapshot.revision;active=!!snapshot.active;render(snapshot.state);
        latestExternal=null;
    }
    window.addEventListener('vbot-equalizer-state',event=>receiveExternal(event.detail));
    nameInput.addEventListener('blur',()=>{if(latestExternal)receiveExternal(latestExternal);});
    const bands=()=>Object.fromEntries(fields.map(name=>[name,Number(get(name).value)]));
    function refreshView(){
        for(const field of fields){
            const range=get(field+'_range');
            const percent=Math.max(0,Math.min(100,(Number(range.value)-Number(range.min))/(Number(range.max)-Number(range.min))*100));
            range.style.setProperty('--eq-fill',percent+'%');
        }
        frequencyFields.forEach(field=>{
            const gain=Number(get(field).value||0);
            byId('speaker_eq_'+field+'_value').textContent=(gain>0?'+':'')+gain.toLocaleString('vi-VN',{maximumFractionDigits:1})+' dB';
        });
        drawGraph();
        const automatic=frequencyFields.reduce((sum,name)=>sum+Math.max(0,Number(get(name).value||0)),0);
        byId('speaker-eq-headroom').textContent='Bù gain tự động: '+(automatic?'-'+automatic.toLocaleString('vi-VN'):'0')+' dB';
        const label=byId('speaker-eq-active-label');
        label.textContent=active?'Đang bật':'Đang tắt';label.className='badge '+(active?'bg-success':'bg-secondary');
        const saved=state.presets.find(p=>p.id===preset.value);
        const dirty=!saved||fields.some(field=>Number(saved.bands[field]??(field==='wet_percent'?100:0))!==Number(get(field).value||0));
        byId('speaker-eq-draft').textContent=dirty?'Đã chỉnh · Chưa ghi vào mẫu':'Theo mẫu đã lưu';
        byId('speaker-eq-draft').className='badge '+(dirty?'bg-warning text-dark':'bg-secondary');
    }
    function drawGraph(){
        const canvas=byId('speaker-eq-graph');
        if(!canvas?.getContext)return;
        const rect=canvas.getBoundingClientRect();if(!rect.width)return;
        const ratio=window.devicePixelRatio||1,W=rect.width,H=190;
        canvas.width=Math.round(W*ratio);canvas.height=Math.round(H*ratio);
        const ctx=canvas.getContext('2d');if(!ctx)return;
        ctx.scale(ratio,ratio);ctx.clearRect(0,0,W,H);
        const pad={l:32,r:24,t:16,b:28},w=W-pad.l-pad.r,h=H-pad.t-pad.b;
        const freqs=[100,250,630,1600,4000,10000],labels=['100','250','630','1,6k','4k','10k'];
        const x=i=>pad.l+Math.log(freqs[i]/100)/Math.log(100)*w;
        const y=g=>pad.t+(12-Math.max(-12,Math.min(12,g)))/24*h;
        ctx.font='10px system-ui';ctx.textAlign='right';ctx.lineWidth=1;
        [-12,-6,0,6,12].forEach(g=>{
            ctx.strokeStyle=g===0?'#657d96':'#24364b';ctx.setLineDash(g===0?[]:[3,5]);
            ctx.beginPath();ctx.moveTo(pad.l,y(g));ctx.lineTo(W-pad.r,y(g));ctx.stroke();
            ctx.fillStyle='#8ca3ba';ctx.fillText((g>0?'+':'')+g,pad.l-6,y(g)+3);
        });
        ctx.setLineDash([]);ctx.textAlign='center';
        labels.forEach((label,i)=>ctx.fillText(label,x(i),H-8));
        // Illustrates band gains only: preamp, wet/dry and biquad response are not measured here.
        const gains=frequencyFields.map(f=>active?Number(get(f).value||0):0);
        ctx.beginPath();gains.forEach((g,i)=>{
            if(!i){ctx.moveTo(x(i),y(g));return;}
            const mid=(x(i-1)+x(i))/2;
            ctx.bezierCurveTo(mid,y(gains[i-1]),mid,y(g),x(i),y(g));
        });
        ctx.strokeStyle=active?'#63e1cd':'#77899e';ctx.lineWidth=3;ctx.lineJoin='round';
        ctx.shadowColor=active?'#4ddce1':'transparent';ctx.shadowBlur=10;ctx.stroke();ctx.shadowBlur=0;
        gains.forEach((g,i)=>{ctx.beginPath();ctx.arc(x(i),y(g),3.5,0,2*Math.PI);ctx.fillStyle=active?'#83ffe3':'#77899e';ctx.fill();});
    }
    function valid(){for(const name of fields)if(!get(name).checkValidity()){get(name).reportValidity();return false;}return true;}
    function render(value,updateName=true){
        const previousSelection=preset.value;
        state=value;
        preset.replaceChildren(...Object.keys(modes).map(id=>new Option(modeNames[id]+(state.default_preset==='_builtin_'+id?' ★ Mặc định':''),'builtin:'+id)),new Option('Custom — Tùy chỉnh','custom'),...state.presets.filter(p=>!p.id.startsWith('_builtin_')).map(p=>new Option(p.name+(p.id===state.default_preset?' ★ Mặc định':''),p.id)));
        const saved=state.presets.find(p=>p.id===state.selected_preset);
        const matchesSaved=saved&&fields.every(f=>Number(saved.bands[f]||0)===Number(state.current[f]||0));
        const mode=Object.keys(modes).find(id=>modes[id].every((v,i)=>v===Number(state.current[frequencyFields[i]]))&&Number(state.current.preamp_db)===-3&&Number(state.current.wet_percent??100)===100);
        preset.value=matchesSaved?state.selected_preset:(mode?'builtin:'+mode:'custom');
        if(matchesSaved && state.selected_preset.startsWith('_builtin_'))preset.value='builtin:'+state.selected_preset.slice(9);
        if(!updateName && !previousSelection.startsWith('_builtin_') && state.presets.some(p=>p.id===previousSelection))preset.value=previousSelection;
        for(const field of fields){get(field).value=state.current[field]??(field==='wet_percent'?100:0);get(field+'_range').value=get(field).value;}
        if(updateName)nameInput.value=state.presets.find(p=>p.id===preset.value)?.name||'';
        updateButtons();
        refreshView();
    }
    async function work(){
        if(busy)return;
        busy=true;
        while(queue.length){
            const task=queue.shift();status.textContent='';
            const controller=new AbortController();
            const deadline=setTimeout(()=>controller.abort(),10000);
            try {
                const response=await fetch('includes/php_ajax/Audio_Equalizer.php',{
                    method:task.data?'POST':'GET',credentials:'same-origin',signal:controller.signal,
                    headers:{'Content-Type':'application/json','X-CSRF-Token':window.VBOT_CSRF_TOKEN||''},
                    ...(task.data?{body:JSON.stringify(task.data)}:{})
                });
                const result=await response.json();
                if(!response.ok||!result.success)throw Error(result.message||'Không cập nhật được EQ');
                state=result.state;
                if(task.version===revision){
                    active=!!result.active;render(state,task.data?.action!=='apply');
                    status.textContent='';
                    if(task.data && typeof window.showMessagePHP==='function'){
                        const message=task.data.action==='apply'?'Đã tự động lưu EQ':result.message;
                        window.showMessagePHP(String(message).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])),3);
                    }
                }
            } catch(error){
                if(task.version===revision)status.textContent=error.name==='AbortError'?'Hết thời gian chờ. Nhấn Áp dụng ngay để thử lại.':error.message;
            } finally{clearTimeout(deadline);}
        }
        busy=false;
    }
    function submit(data,version){
        const task={data,version:version===undefined?++revision:version};
        if(data?.action==='apply' && queue.at(-1)?.data?.action==='apply')queue[queue.length-1]=task;
        else queue.push(task);
        return work();
    }
    function changed(){localChangedAt=Date.now();if(preset.value.startsWith('builtin:')){const id=preset.value.slice(8);if(!modes[id].every((v,i)=>v===Number(get(frequencyFields[i]).value))||Number(get('preamp_db').value)!==-3)preset.value='custom';}updateButtons();revision++;refreshView();clearTimeout(timer);timer=setTimeout(()=>{timer=null;if(valid())submit({action:'apply',bands:bands()},revision);},350);}
    function command(data){
        localChangedAt=Date.now();
        if(timer!==null){
            clearTimeout(timer);timer=null;
            if(['toggle','default','delete'].includes(data.action) && valid())submit({action:'apply',bands:bands()});
        }
        return submit(data);
    }
    for(const field of fields){
        const number=get(field),range=get(field+'_range');
        range.addEventListener('input',()=>{number.value=range.value;changed();});
        number.addEventListener('input',()=>{if(number.checkValidity()){range.value=number.value;changed();}});
    }
    function updateButtons(){
        const saved=state.presets.some(p=>p.id===preset.value);
        byId('speaker-eq-update').disabled=!saved;
        byId('speaker-eq-default').disabled=!saved&&!preset.value.startsWith('builtin:');
        byId('speaker-eq-delete').disabled=!saved||state.presets.length<=1;
    }
    preset.addEventListener('change',()=>{
        updateButtons();
        if(preset.value==='custom')return;
        if(!preset.value.startsWith('builtin:')){command({action:'select',id:preset.value});return;}
        const id=preset.value.slice(8);
        if(!modes[id])return;
        nameInput.value=modeNames[id];command({action:'select',id:preset.value});
    });
    nameInput.addEventListener('input',()=>{revision++;});
    byId('speaker-eq-flat').addEventListener('click',()=>{for(const field of fields){get(field).value=field==='wet_percent'?100:0;get(field+'_range').value=get(field).value;}changed();});
    byId('speaker-eq-apply').addEventListener('click',()=>{if(valid())command({action:'apply',bands:bands()});});
    function save(update){
        if(!valid())return;
        if(!nameInput.value.trim()){nameInput.focus();status.textContent='Nhập tên mẫu EQ trước khi lưu.';return;}
        if(update&&!state.presets.some(p=>p.id===preset.value))return;
        command({action:'save',id:update?preset.value:'',name:nameInput.value.trim(),bands:bands()});
    }
    byId('speaker-eq-save-new').addEventListener('click',()=>save(false));
    byId('speaker-eq-update').addEventListener('click',()=>save(true));
    byId('speaker-eq-default').addEventListener('click',()=>command({action:'default',id:preset.value}));
    byId('speaker-eq-delete').addEventListener('click',()=>{
        if(window.confirm('Xóa mẫu EQ đang chọn?'))command({action:'delete',id:preset.value});
    });
    for(const button of document.querySelectorAll('[data-eq-step]'))button.addEventListener('click',()=>{
        const number=get(button.dataset.eqStep);
        number.value=String(Math.max(-12,Math.min(12,Number(number.value)+Number(button.dataset.eqDelta))));
        get(button.dataset.eqStep+'_range').value=number.value;changed();
    });
    window.addEventListener?.('resize',drawGraph);
    if(window.ResizeObserver)new window.ResizeObserver(drawGraph).observe(byId('speaker-eq-graph'));
    render(state);submit(null,revision);
})();
