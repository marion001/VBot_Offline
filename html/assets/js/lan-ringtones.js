(() => {
    'use strict';
    const byId=id=>document.getElementById(id),options=window.lanRingtoneOptions;
    if(!options)return;
    const selects=[byId('lan_ringtone'),byId('lan_outgoing_ringtone')];
    for(const button of document.querySelectorAll('[data-lan-ringtone-preview]')){
        button.addEventListener('click',()=>{
            const name=byId(button.dataset.lanRingtonePreview).value;
            playAudio(name?options.folder+name:options.fallback);
        });
    }
    byId('lan_ringtone_upload_button').addEventListener('click',async function(){
        const input=byId('lan_ringtone_upload'),status=byId('lan_ringtone_upload_status'),file=input.files[0];
        if(!file){status.textContent='Hãy chọn file âm thanh để tải lên.';return;}
        if(file.size>20*1024*1024){status.textContent='File không được vượt quá 20 MB.';return;}
        this.disabled=true;status.textContent='Đang tải nhạc chuông…';
        try {
            const data=new FormData();data.append('ringtone',file);
            const response=await fetch('includes/php_ajax/LAN_Ringtone_Upload.php',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':window.VBOT_CSRF_TOKEN||''},body:data});
            const result=await response.json();if(!response.ok || !result.success)throw Error(result.message||'Tải nhạc chuông thất bại');
            for(const select of selects){
                const previous=select.value;
                select.replaceChildren(new Option('Âm báo mặc định của VBot',''));
                for(const name of result.files)select.add(new Option(name,name));
                select.value=select.id===byId('lan_ringtone_upload_target').value?result.name:previous;
            }
            input.value='';status.textContent=result.message;
        } catch(error){status.textContent=error.message;} finally{this.disabled=false;}
    });
})();
