<?php
$lanButtonOptions['encoder']='Nút nhấn Encoder';
$lanDisabledButtons=[];
foreach ($lanButtonOptions as $name=>$label) {
    $enabled=!empty($Config['smart_config']['button_active']['active']) && ($name==='encoder'
        ? ($Config['smart_config']['button_active']['button_type']??'')==='encoder_rotary'
        : !empty($Config['smart_config']['button'][$name]['active']));
    if (!$enabled) $lanDisabledButtons[]=$name;
}
$endPress=!empty($lanSettings['mic_button_mute']??true) && ($lanSettings['end_button']??'mic')==='mic'?'hold':($lanSettings['end_button_press']??'short');
$buttonRows=[
 ['Nhận cuộc gọi','lan_accept_button_active',$lanSettings['accept_button_active']??true,
  'lan_accept_button',array_merge($lanButtonOptions,['encoder'=>'Nút nhấn Encoder']),$lanSettings['accept_button']??'wakeup',
  'lan_accept_button_press',['short'=>'Nhấn nhả','hold'=>'Nhấn giữ','double'=>'Nhấn đúp'],$lanSettings['accept_button_press']??'short',
  'Chỉ nhận khi có cuộc gọi đến. Thời gian giữ và khoảng nhấn đúp lấy từ cấu hình Button.'],
 ['Bật / Tắt mic gửi đi','lan_mic_button_mute',$lanSettings['mic_button_mute']??true,
  null,[],null,null,[],null,'Nhấn nhả nút Mic để tắt hoặc bật âm thanh gửi đi; vẫn nghe bên kia. Không đổi mic toàn hệ thống. Nếu Mic cũng dùng kết thúc cuộc gọi, nhấn giữ để kết thúc.'],
 ['Nhấn giữ để nói','lan_ptt_button_active',!empty($lanSettings['ptt_button_active']),
  'lan_ptt_button',[''=>'Chọn nút đã cấu hình']+$lanButtonOptions,$lanSettings['ptt_button']??'',
  null,[],null,'Chỉ hoạt động trong chế độ nhấn giữ để nói: giữ để truyền mic, thả để ngừng. Không chọn Mic nếu đang dùng nút Mic để tắt/bật mic. Nếu chọn Mic cho PTT, dùng WebUI để kết thúc cuộc gọi.'],
 ['Kết thúc cuộc gọi','lan_end_button_active',$lanSettings['end_button_active']??true,
  'lan_end_button',[''=>'Chọn nút đã cấu hình']+$lanButtonOptions,$lanSettings['end_button']??'mic',
  'lan_end_button_press',['short'=>'Nhấn nhả','hold'=>'Nhấn giữ','both'=>'Cả nhấn nhả và nhấn giữ'],$endPress,
  'Kết thúc khi đang kết nối hoặc trò chuyện. Nếu dùng Mic đồng thời bật/tắt mic, chỉ nhấn giữ để kết thúc. Nút kết thúc và nút nhấn giữ nói phải khác nhau khi dùng PTT.']
];
?>
<div class="border border-success rounded p-3 mb-3">
<input type="hidden" name="lan_accept_controls_present" value="1">
<input type="hidden" name="lan_ptt_present" value="1">
<input type="hidden" name="lan_end_button_present" value="1">
<div class="table-responsive">
<table id="lan-call-buttons-table" class="table table-bordered table-hover align-middle border-success">
<thead><tr class="table-success"><th colspan="4" class="p-0 border-success"><div class="bg-success text-white text-center p-3">Nút điều khiển cuộc gọi</div></th></tr><tr class="table-info"><th scope="col" class="p-0 border-success"><div class="bg-info text-dark text-center p-2">Chức năng</div></th><th scope="col" class="p-0 border-success"><div class="bg-info text-dark text-center p-2">Kích Hoạt</div></th><th scope="col" class="p-0 border-success"><div class="bg-info text-dark text-center p-2">Nút sử dụng</div></th><th scope="col" class="p-0 border-success"><div class="bg-info text-dark text-center p-2">Thao tác</div></th></tr></thead>
<tbody class="table-light">
<?php foreach ($buttonRows as $row): ?>
<tr><th scope="row" class="table-success"><?= $row[0] ?> <?= vbotConfigHelpIcon($row[9]) ?></th>
<td class="table-light"><?= switch_field($row[1],$row[0],$row[2],$row[9],'',null,true) ?></td>
<td class="table-light"><?php
if ($row[3]!==null) echo select_field($row[3],$row[0].' — nút sử dụng',$row[4],vbotLANEscape($row[5]),$lanDisabledButtons,$row[9],true);
else echo 'Mic '.vbotConfigHelpIcon('Dùng nút Mic đã cấu hình của hệ thống; không chọn nút khác tại đây.');
?></td>
<td class="table-light"><?php
if ($row[6]!==null) echo select_field($row[6],$row[0].' — thao tác',$row[7],$row[8],[],$row[9],true);
else echo ($row[1]==='lan_mic_button_mute'?'Nhấn nhả':'Giữ để nói, thả để ngừng').' '.vbotConfigHelpIcon($row[9]);
?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<p class="form-text">Chọn nút đã bật trong cấu hình Nút Nhấn. Ngoài cuộc gọi, các nút giữ chức năng thông thường. WebUI vẫn có thể nhận và kết thúc cuộc gọi.</p>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selects=['lan_accept_button','lan_ptt_button','lan_end_button'].map(id=>document.getElementById(id));
    const syncButtons=()=>{
        const active=document.querySelector('[name="button_active"]')?.checked===true;
        const type=document.querySelector('[name="button_type"]')?.value;
        for(const select of selects) for(const option of select.options){
            if(!option.value) continue;
            const control=document.getElementsByName('button['+option.value+'][active]')[0];
            const enabled=active && (option.value==='encoder'?type==='encoder_rotary':control?.checked===true);
            option.disabled=!enabled;
            if(!option.dataset.baseLabel) option.dataset.baseLabel=option.textContent.replace(/\s*\(đang tắt\)\s*$/,'');
            option.textContent=option.dataset.baseLabel+(enabled?'':' (đang tắt)');
        }
    };
    document.addEventListener('change',event=>{
        const name=event.target.name||'';
        if(name==='button_active'||name==='button_type'||/^button\[.*\]\[active\]$/.test(name)) syncButtons();
    });
    syncButtons();
});
</script>
