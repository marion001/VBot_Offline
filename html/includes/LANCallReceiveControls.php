<?php // Included inside the main configuration form; $lanSettings and $Config are available. ?>
<h6 class="mt-4 border-bottom pb-2">Nhận cuộc gọi</h6>
<input type="hidden" name="lan_accept_controls_present" value="1">
<?php
echo input_field('lan_auto_accept_delay','Chờ trước khi tự nhận (giây)',$lanSettings['auto_accept_delay']??3,'','number','1','0','30',
    'Mặc định 3 giây. Chỉ áp dụng cho loa được chọn tự động nhận trong danh sách Gọi điện LAN; các loa khác nhận thủ công.');
echo switch_field('lan_accept_button_active','Dùng nút vật lý nhận cuộc gọi',$lanSettings['accept_button_active']??true);
echo select_field('lan_accept_button','Nút nhận cuộc gọi',array_merge($lanButtonOptions,['encoder'=>'Nút nhấn Encoder']),vbotLANEscape($lanSettings['accept_button']??'wakeup'));
echo select_field('lan_accept_button_press','Thao tác nhận cuộc gọi',['short'=>'Nhấn nhả','hold'=>'Nhấn giữ','double'=>'Nhấn đúp'],$lanSettings['accept_button_press']??'short',[],
    'Thời gian giữ và khoảng nhấn đúp lấy từ cấu hình Button của nút đã chọn. Chỉ có tác dụng khi có cuộc gọi đến; WebUI vẫn nhận được cuộc gọi.');
?>
<h6 class="mt-4 border-bottom pb-2">Microphone trong cuộc gọi</h6>
<?php echo switch_field('lan_mic_button_mute','Nhấn nhả nút Mic để tắt/bật âm thanh gửi đi',$lanSettings['mic_button_mute']??true,
    'Chỉ đổi mic cuộc gọi, không đổi mic toàn hệ thống; khi tắt vẫn nghe đầu bên kia. Nếu Mic cũng dùng kết thúc, nhấn giữ để kết thúc, nhấn nhả để bật/tắt mic. Không chọn Mic làm nút nhấn giữ nói khi bật tùy chọn này. Với Encoder, thao tác Mic thông thường cũng chỉ bật/tắt mic cuộc gọi.'); ?>
