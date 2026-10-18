<?php
require_once __DIR__.'/EqualizerStorage.php';
$eqError='';
try {$eqState=vbotEQRead(dirname(__DIR__,2).'/resource/equalizer/settings.json',$Config['media_player']['equalizer']??[]);}
catch (Throwable $error) {$eqError=$error->getMessage();$eqState=vbotEQInitial();}
$eq=array_merge($eqState['current'],['active'=>!empty($Config['media_player']['equalizer']['active'])]);
$eqBands=[
    'bass_db'=>['100 Hz','Bass','Âm trầm','Chỉnh độ đầy của âm trầm; tránh tăng mạnh với loa nhỏ.'],
    'low_mid_db'=>['250 Hz','Low Mid','Độ ấm','Giảm nếu tiếng ù, tăng nhẹ để thêm độ ấm.'],
    'mid_db'=>['630 Hz','Mid','Độ sạch','Giảm nhẹ nếu tiếng đục hoặc bí.'],
    'high_mid_db'=>['1,6 kHz','Presence','Giọng hát','Chỉnh độ rõ giọng nói và giọng hát.'],
    'treble_db'=>['4 kHz','Treble','Chi tiết','Giảm nếu chói gắt, tăng nhẹ để rõ chi tiết.'],
    'air_db'=>['10 kHz','Air','Độ sáng','Chỉnh độ sáng và tiếng xì.']
];
?>
<style>
#speaker-eq-controls input[type="range"] { --eq-direction: to right; }
#speaker-eq-controls input[orient="vertical"] { --eq-direction: to top; }
#speaker-eq-controls input[type="range"]::-webkit-slider-runnable-track {
  background: linear-gradient(var(--eq-direction), var(--bs-primary, #0d6efd) var(--eq-fill, 50%), var(--bs-secondary-bg, #dee2e6) var(--eq-fill, 50%));
  border-radius: 999px;
  height: 10px;
}
#speaker-eq-controls input[type="range"]::-moz-range-track {
  background: linear-gradient(var(--eq-direction), var(--bs-primary, #0d6efd) var(--eq-fill, 50%), var(--bs-secondary-bg, #dee2e6) var(--eq-fill, 50%));
  border-radius: 999px;
  height: 10px;
}
#speaker-eq-controls input[type="range"]::-webkit-slider-thumb { width: 22px; height: 22px; margin-top: -6px; }
#speaker-eq-controls input[type="range"]::-moz-range-thumb { width: 22px; height: 22px; }
#speaker-eq-controls input[orient="vertical"]::-webkit-slider-runnable-track { width: 12px; height: 100%; }
#speaker-eq-controls input[orient="vertical"]::-moz-range-track { width: 12px; height: 100%; }
#speaker-eq-controls input[orient="vertical"]::-webkit-slider-thumb {
  width: 22px; height: 22px; margin-top: 0; margin-left: -5px;
}
#speaker-eq-controls input[orient="vertical"]::-moz-range-thumb {
  width: 22px; height: 22px; margin: 0;
}
</style>
<div id="speaker-eq-controls">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 pt-3 mb-3">
    <div>
      <h5 class="card-title p-0 mb-2"><i class="bi bi-sliders me-2"></i>EQ loa — Equalizer</h5>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="badge <?= $eq['active']?'bg-success':'bg-secondary' ?>" id="speaker-eq-active-label"><?= $eq['active']?'Đang bật':'Đang tắt' ?></span>
      <?= vbotEQHelpIcon('Bật hoặc tắt EQ tại Config.php, mục Equalizer / EQ loa, rồi lưu cấu hình. Áp dụng cho VLC, AirPlay, Bluetooth và Multiroom, kể cả khi VBot dừng. Mic và cuộc gọi LAN không qua EQ. Thay đổi tự lưu sau khi ngừng kéo khoảng 350 ms.') ?>
    </div>
  </div>
  <?php if ($eqError!==''): ?><div class="alert alert-danger"><?= htmlspecialchars($eqError,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>

  <div class="border rounded p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md">
        <label class="form-label small fw-bold" for="speaker-eq-preset">Chế độ âm thanh - EQ <?= vbotEQHelpIcon('Chọn mẫu để nghe ngay. Dấu sao là mẫu mặc định sau reboot. Kéo thanh chỉ đổi thông số đang nghe, không ghi đè mẫu đã lưu.') ?></label>
        <select class="form-select border-success" id="speaker-eq-preset">
          <?php foreach ($eqState['presets'] as $preset): ?><option value="<?= htmlspecialchars($preset['id'],ENT_QUOTES,'UTF-8') ?>" <?= $preset['id']===$eqState['selected_preset']?'selected':'' ?>><?= htmlspecialchars($preset['name'].($preset['id']===$eqState['default_preset']?' ★ Mặc định':''),ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto"><button type="button" class="btn btn-outline-success" id="speaker-eq-default" title="Dùng mẫu đã lưu này sau khi reboot"><i class="bi bi-star me-1"></i>Đặt mặc định</button></div>
      <div class="col-auto"><button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#speaker-eq-editor" aria-expanded="false" aria-controls="speaker-eq-editor"><i class="bi bi-folder2-open me-1"></i>Quản lý mẫu</button></div>
    </div>
    <div class="collapse" id="speaker-eq-editor">
      <div class="border-top pt-3 mt-3">
        <label class="form-label small fw-bold" for="speaker-eq-name">Tên mẫu <?= vbotEQHelpIcon('Nhập tên rồi lưu mẫu mới. Để sửa mẫu đang chọn, chỉnh thanh và tên rồi nhấn Cập nhật mẫu. Tối đa 50 mẫu, tên tối đa 80 ký tự.') ?></label>
        <input class="form-control mb-2" id="speaker-eq-name" maxlength="80" placeholder="Ví dụ: Nhạc nhẹ phòng khách">
        <div class="d-flex gap-2 flex-wrap">
          <button type="button" class="btn btn-success btn-sm" id="speaker-eq-save-new"><i class="bi bi-plus-circle me-1"></i>Lưu mẫu mới</button>
          <button type="button" class="btn btn-primary btn-sm" id="speaker-eq-update"><i class="bi bi-save me-1"></i>Cập nhật mẫu</button>
          <button type="button" class="btn btn-outline-danger btn-sm" id="speaker-eq-delete"><i class="bi bi-trash me-1"></i>Xóa mẫu</button>
        </div>
      </div>
    </div>
  </div>

  <div class="border rounded p-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
      <span class="small fw-bold">Biểu đồ EQ minh họa <?= vbotEQHelpIcon('Đường cong minh họa mức chỉnh của sáu dải, không phải đáp tuyến DSP đo thực tế. Preamp, Wet/Dry và bù gain không được thể hiện trên đồ thị này.') ?></span>
      <span class="badge bg-secondary" id="speaker-eq-draft">Theo mẫu đã lưu</span>
    </div>
    <div class="bg-dark border rounded p-2 mb-3">
      <canvas id="speaker-eq-graph" class="w-100 d-block" height="190" style="height: 190px;" role="img" aria-label="Biểu đồ EQ minh họa sáu dải tần số"></canvas>
    </div>
    <div class="d-flex flex-wrap justify-content-evenly gap-2 mt-3">
    <?php foreach ($eqBands as $field=>$description): $id='speaker_eq_'.$field;$value=htmlspecialchars((string)$eq[$field],ENT_QUOTES,'UTF-8'); ?>
      <div class="flex-shrink-0">
        <div class="border rounded h-100 px-2 py-3 text-center" style="width: 112px;">
          <label class="fw-bold d-block mb-1" for="<?= $id ?>"><?= $description[0] ?></label>
          <div class="small text-success fw-bold mb-1"><?= $description[1] ?> <?= vbotEQHelpIcon($description[3].' Từ -12 đến +12 dB, mặc định 0 dB.') ?></div>
          <div class="small text-muted mb-3"><?= $description[2] ?></div>
          <div class="d-flex justify-content-center position-relative py-3">
            <span class="position-absolute top-50 start-50 translate-middle w-50 pe-none opacity-50" style="border-top: 1px dashed currentColor;" aria-hidden="true"></span>
            <input type="range" orient="vertical" style="writing-mode: vertical-lr; direction: rtl; width: 28px; height: 180px;" class="form-range position-relative" id="<?= $id ?>_range" min="-12" max="12" step="0.5" value="<?= $value ?>" data-eq-range="<?= $field ?>" aria-label="<?= $description[0] ?> <?= $description[1] ?>">
          </div>
          <div class="text-center small mb-2"><output class="text-success fw-bold" id="<?= $id ?>_value" for="<?= $id ?>_range"><?= $value ?> dB</output></div>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-eq-step="<?= $field ?>" data-eq-delta="-0.5" aria-label="Giảm <?= $description[0] ?>"><i class="bi bi-dash"></i></button>
            <input type="number" id="<?= $id ?>" min="-12" max="12" step="0.5" value="<?= $value ?>" data-eq-number="<?= $field ?>" hidden>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-eq-step="<?= $field ?>" data-eq-delta="0.5" aria-label="Tăng <?= $description[0] ?>"><i class="bi bi-plus"></i></button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>

  <div class="border rounded p-3 mb-3">
    <div class="row g-3 align-items-center">
      <div class="col-12 col-md-4"><label class="form-label fw-bold mb-1" for="speaker_eq_preamp_db">Preamp <?= vbotEQHelpIcon('Giảm thêm từ -12 đến 0 dB sau khi trộn Wet/Dry. Nhánh EQ tự giảm gain khi tăng các dải để giữ khoảng dự phòng, không đổi alsamixer.') ?></label><div class="small text-muted" id="speaker-eq-headroom">Bù gain tự động: 0 dB</div></div>
      <div class="col"><input type="range" class="form-range" id="speaker_eq_preamp_db_range" min="-12" max="0" step="0.5" value="<?= (float)$eq['preamp_db'] ?>" aria-label="Preamp"></div>
      <div class="col-4 col-md-2"><div class="input-group input-group-sm"><input class="form-control text-center" type="number" id="speaker_eq_preamp_db" min="-12" max="0" step="0.5" value="<?= (float)$eq['preamp_db'] ?>" aria-label="Preamp dB" required><span class="input-group-text">dB</span></div></div>
    </div>
    <div class="border-top pt-3 mt-3">
    <label class="form-label fw-bold" for="speaker_eq_wet_percent">Mức áp dụng EQ — Wet/Dry <?= vbotEQHelpIcon('0% = âm gốc, 100% = EQ hoàn toàn (mặc định). Preamp áp dụng sau khi trộn.') ?></label>
    <div class="row align-items-center g-3"><div class="col"><input class="form-range" type="range" id="speaker_eq_wet_percent_range" min="0" max="100" step="1" value="<?= (float)$eq['wet_percent'] ?>"></div><div class="col-4 col-md-2"><div class="input-group input-group-sm"><input class="form-control text-center" type="number" id="speaker_eq_wet_percent" min="0" max="100" step="1" value="<?= (float)$eq['wet_percent'] ?>" required><span class="input-group-text">%</span></div></div></div>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
    <p class="small text-muted mb-0" id="speaker-eq-status" role="status" aria-live="polite"></p>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" id="speaker-eq-flat"><i class="bi bi-arrow-counterclockwise me-1"></i>Về phẳng</button>
      <button type="button" class="btn btn-success btn-sm" id="speaker-eq-apply"><i class="bi bi-check2 me-1"></i>Áp dụng ngay</button>
    </div>
  </div>
</div>
<script>window.vbotEqualizerActive=<?= $eq['active']?'true':'false' ?>;window.vbotEqualizerState=<?= json_encode($eqState,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script src="assets/js/audio-equalizer.js?v=16" defer></script>
