<?php
$lanRingtones=vbotLANRingtones();
$lanRingtoneDefaults=['ringtone'=>'call_iphone.mp3','outgoing_ringtone'=>'Cho_Cuoc_Goi_1.mp3'];
foreach (['ringtone'=>'Nhạc chuông cuộc gọi đến','outgoing_ringtone'=>'Nhạc chuông khi chờ cuộc gọi'] as $field=>$label):
    $selected=$lanSettings[$field]??(in_array($lanRingtoneDefaults[$field],$lanRingtones,true)?$lanRingtoneDefaults[$field]:'');
?>
<div class="row mb-3"><label class="col-sm-3 col-form-label" for="lan_<?= $field ?>"><?= $label ?>:</label>
<div class="col-sm-9"><div class="input-group"><select class="form-select border-success lan-ringtone-select" id="lan_<?= $field ?>" name="lan_<?= $field ?>">
<option value="" <?= $selected===''?'selected':'' ?>>Âm báo mặc định của VBot</option>
<?php foreach ($lanRingtones as $name): ?><option value="<?= vbotLANEscape($name) ?>" <?= $selected===$name?'selected':'' ?>><?= vbotLANEscape($name) ?></option><?php endforeach; ?>
</select><button type="button" class="btn btn-outline-primary" data-lan-ringtone-preview="lan_<?= $field ?>" title="Nghe thử trên trình duyệt" aria-label="Nghe thử <?= $label ?>"><i class="bi bi-play-circle"></i></button><button type="button" class="btn btn-outline-danger" onclick="stopAudio()" title="D&#7915;ng nghe th&#7917;" aria-label="D&#7915;ng nghe th&#7917;"><i class="bi bi-stop-circle"></i></button></div></div></div>
<?php endforeach; ?>
<?php echo select_field('lan_ringtone_upload_target','Áp dụng file vừa tải cho',[
    'lan_ringtone'=>'Chọn làm chuông cuộc gọi đến','lan_outgoing_ringtone'=>'Chọn làm chuông chờ gọi đi'],
    'lan_ringtone'); ?>
<div class="row mb-3"><label class="col-sm-3 col-form-label" for="lan_ringtone_upload">Tải nhạc chuông lên (dùng chung cho hai danh sách):</label><div class="col-sm-9">
<div class="input-group"><input class="form-control" type="file" id="lan_ringtone_upload" accept=".mp3,.wav,.ogg,.flac,.m4a">
<button type="button" class="btn btn-success" id="lan_ringtone_upload_button"><i class="bi bi-upload"></i> Tải lên</button></div>
<div id="lan_ringtone_upload_status" class="form-text" role="status"></div>
<p class="form-text">Tối đa 20 MB/file, lưu tại resource/sound/call_ringtone/. File tải lên xuất hiện ở cả hai danh sách; lựa chọn bên trên chỉ quyết định mục được tự chọn sau khi tải. Chuông lặp lại nếu file ngắn hơn thời gian chờ và dừng khi nhận/từ chối/hủy/hết thời gian chờ. Nút Play chỉ phát trên trình duyệt. Lưu và khởi động lại VBot để áp dụng.</p></div></div>
<script>
window.lanRingtoneOptions=<?= json_encode(['folder'=>rtrim($VBot_Offline,'/\\').'/resource/sound/call_ringtone/','fallback'=>rtrim($VBot_Offline,'/\\').'/'.ltrim($Config['smart_config']['smart_wakeup']['sound']['default']['finish']??'resource/sound/default/dong.mp3','/\\')],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
</script>
