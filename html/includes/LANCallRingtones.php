<div class="border border-success rounded p-3 mb-3">
<h6 class="border-bottom pb-2">Nhạc chuông và tải lên</h6>
<?php
$lanRingtones=vbotLANRingtones();
$lanRingtoneDefaults=['ringtone'=>'call_iphone.mp3','outgoing_ringtone'=>'Cho_Cuoc_Goi_1.mp3'];
foreach (['ringtone'=>'Nhạc chuông cuộc gọi đến','outgoing_ringtone'=>'Nhạc chuông khi chờ cuộc gọi'] as $field=>$label):
    $selected=$lanSettings[$field]??(in_array($lanRingtoneDefaults[$field],$lanRingtones,true)?$lanRingtoneDefaults[$field]:'');
?>
<div class="row mb-3"><label class="col-sm-3 col-form-label" for="lan_<?= $field ?>"><?= $label ?> <?= vbotConfigHelpIcon($field==='ringtone'?'Âm thanh phát trên loa này khi có cuộc gọi đến. Chuông lặp lại trong thời gian chờ và dừng khi nhận, từ chối hoặc kết thúc cuộc gọi. Nút Play chỉ nghe thử trên trình duyệt.':'Âm thanh phát trên loa này trong lúc chờ loa đích bắt máy. File ngắn sẽ lặp lại đến khi người nhận bắt máy hoặc hết thời gian chờ. Nút Play chỉ nghe thử trên trình duyệt.') ?>:</label>
<div class="col-sm-9"><div class="input-group"><select class="form-select border-success lan-ringtone-select" id="lan_<?= $field ?>" name="lan_<?= $field ?>">
<option value="" <?= $selected===''?'selected':'' ?>>Âm báo mặc định của VBot</option>
<?php foreach ($lanRingtones as $name): ?><option value="<?= vbotLANEscape($name) ?>" <?= $selected===$name?'selected':'' ?>><?= vbotLANEscape($name) ?></option><?php endforeach; ?>
</select><button type="button" class="btn btn-outline-primary" data-lan-ringtone-preview="lan_<?= $field ?>" title="Nghe thử trên trình duyệt" aria-label="Nghe thử <?= $label ?>"><i class="bi bi-play-circle"></i></button><button type="button" class="btn btn-outline-danger" onclick="stopAudio()" title="D&#7915;ng nghe th&#7917;" aria-label="D&#7915;ng nghe th&#7917;"><i class="bi bi-stop-circle"></i></button></div></div></div>
<?php endforeach; ?>
<div class="row mb-3"><label class="col-sm-3 col-form-label">Tải nhạc chuông lên (dùng chung cho hai danh sách) <?= vbotConfigHelpIcon('Chọn file MP3, WAV, OGG, FLAC hoặc M4A, tối đa 20 MB, rồi nhấn Tải lên. File được lưu trong resource/sound/call_ringtone và xuất hiện ở cả hai danh sách. Lưu cấu hình sau khi chọn chuông.') ?>:</label><div class="col-sm-9">
<div class="input-group"><input class="form-control" type="file" id="lan_ringtone_upload" accept=".mp3,.wav,.ogg,.flac,.m4a">
<button type="button" class="btn btn-success" id="lan_ringtone_upload_button"><i class="bi bi-upload"></i> Tải lên</button></div>
<div id="lan_ringtone_upload_status" class="form-text" role="status"></div>
<p class="form-text">Tối đa 20 MB/file, lưu tại resource/sound/call_ringtone/. Sau khi tải lên, cả hai danh sách tự cập nhật file mới và giữ nguyên nhạc chuông đang chọn. Chọn nhạc chuông muốn dùng rồi lưu cấu hình. Chuông lặp lại nếu file ngắn hơn thời gian chờ và dừng khi nhận/từ chối/hủy/hết thời gian chờ. Nút Play chỉ phát trên trình duyệt. Lưu và khởi động lại VBot để áp dụng.</p></div></div>
</div>
<script>
window.lanRingtoneOptions=<?= json_encode(['folder'=>rtrim($VBot_Offline,'/\\').'/resource/sound/call_ringtone/','fallback'=>rtrim($VBot_Offline,'/\\').'/'.ltrim($Config['smart_config']['smart_wakeup']['sound']['default']['finish']??'resource/sound/default/dong.mp3','/\\')],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
</script>
