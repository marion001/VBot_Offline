<?php
$VBot_Config_Read_Only=true;
include 'Configuration.php';
require_once __DIR__.'/includes/php_ajax/Api_Helpers.php';
require_once __DIR__.'/includes/LANCalls.php';
require_once __DIR__.'/includes/Config_Storage.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!empty($Config['contact_info']['user_login']['active']) && (empty($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time()-$_SESSION['user_login']['login_time']>43200))) { header('Location: Login.php');exit; }
if (empty($_SESSION['lan_calls_csrf'])) $_SESSION['lan_calls_csrf']=bin2hex(random_bytes(32));
$message='';$ok=false;
$settings=$Config['lan_calls']??[];
$localId=($settings['use_mdns_id']??true)?vbotStableDeviceId():($settings['id']??'');
$localId=$localId!==''?$localId:($settings['id']??'');
$peerFile=dirname(__DIR__).'/resource/call_lan/Devices.json';
$peerDevices=[];
try { $peerDevices=vbotLANReadPeers($peerFile,$settings['peers']??[]); }
catch (Throwable $error) { $message=$error->getMessage(); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        if (!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['lan_calls_csrf'],$_POST['csrf'])) throw new InvalidArgumentException('Phiên đã hết hạn; tải lại trang');
        vbotLANSavePeers($peerFile,$_POST,$localId,$peerDevices);
        $ok=true;$message='Đã lưu danh sách loa vào resource/call_lan/Devices.json. Khởi động lại VBot để áp dụng.';
    } catch (Throwable $error) { $message=$error->getMessage(); }
}
$settings=$Config['lan_calls']??[];
$localId=($settings['use_mdns_id']??true)?vbotStableDeviceId():($settings['id']??'');
$discoveredPeers=vbotLANDiscoveredPeers(__DIR__.'/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json',$localId!==''?$localId:($settings['id']??''));
function lanEscape($value) {return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html><html lang="vi"><?php include 'html_head.php'; ?><body>
<?php include 'html_header_bar.php';include 'html_sidebar.php'; ?>
<main id="main" class="main"><div class="pagetitle"><h1>Gọi điện giữa các loa trong LAN</h1></div>
<section class="section">
<?php if ($message!==''): ?><div class="alert <?= $ok?'alert-success':'alert-danger' ?>"><?= lanEscape($message) ?></div><?php endif; ?>
<div class="card"><div class="card-body pt-3"><h2 class="h5">Cuộc gọi trên loa này</h2>
<p id="lanCallStatus" role="status">Đang đọc trạng thái…</p><div class="d-flex flex-wrap gap-2 mb-3">
<select id="lanCallPeer" class="form-select w-auto" aria-label="Loa cần gọi"><?php foreach ($peerDevices as $peer): ?><option value="<?= lanEscape($peer['id']??'') ?>"><?= lanEscape($peer['name']??$peer['id']??'') ?></option><?php endforeach; ?></select>
<button type="button" id="lanDial" class="btn btn-primary" disabled>Gọi loa</button><button type="button" id="lanAccept" class="btn btn-success" disabled>Nhận cuộc gọi</button>
<button type="button" id="lanReject" class="btn btn-outline-danger">Từ chối</button><button type="button" id="lanEnd" class="btn btn-danger">Kết thúc</button>
<button type="button" id="lanMute" class="btn btn-outline-secondary">Tắt microphone cuộc gọi</button><button type="button" id="lanTalk" class="btn btn-warning">Nhấn giữ để nói</button></div>
<p class="small text-muted">Khi đang gọi, microphone dành riêng cho cuộc trò chuyện. Nhận/kết thúc bằng WebUI hoặc gán nút bấm “Gọi LAN: Nhận hoặc kết thúc cuộc gọi”. Khi chờ cuộc gọi, có thể nói “nhận cuộc gọi” hoặc “từ chối cuộc gọi”. Chế độ nhấn giữ để nói giúp hạn chế vọng; chế độ hai chiều đồng thời cần thử trên loa thực tế.</p>
</div></div>
<div class="card"><div class="card-body pt-3"><h2 class="h5">Danh sách loa kết nối</h2><p><a href="Config.php#lanCallConfiguration">Cấu hình bật/tắt gọi LAN, khóa chung và âm thanh</a></p>
<form method="post" id="lanConfigForm"><input type="hidden" name="csrf" value="<?= lanEscape($_SESSION['lan_calls_csrf']) ?>">
<h3 class="h6 mt-4">Danh sách loa được phép kết nối</h3><p class="small">Khai báo hai chiều: loa A có loa B trong danh sách và loa B cũng có loa A. ID, IP và cổng phải đúng với loa tương ứng. Nên giữ cố định IP trong router.</p>
<button type="button" id="tts_scan_devices_button" class="btn btn-success btn-sm mb-2" onclick="runWebuiVbotClientAction('scan_VBot_Device')" title="Quét lại các loa VBot và ESP32 Client trong mạng LAN"><i class="bi bi-radar"></i> Quét Thiết Bị</button>
<span id="lanScanSummary" class="small text-muted ms-2" role="status"></span>
<p class="form-text">Nhấn vào ô ID, tên hoặc IP để chọn loa từ dữ liệu quét mạng VBot_Devices_Network.json; có thể tìm theo tên, ID hoặc IP và nhập thủ công. Chọn gợi ý sẽ điền cả dòng. Nếu dữ liệu quét chưa có cổng gọi LAN, dùng TCP 5010 / UDP 5011; chỉnh lại khi loa đích dùng cổng khác.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Tên dùng để gọi</th><th>IP nội bộ</th><th>Cổng TCP</th><th>Cổng UDP</th><th></th></tr></thead><tbody id="lanPeerRows"></tbody></table></div>
<input type="hidden" id="lan_peers" name="lan_peers" value="<?= lanEscape(json_encode($peerDevices)) ?>"><button type="button" id="lanAddPeer" class="btn btn-outline-primary">Thêm loa</button>
<button type="submit" class="btn btn-success">Lưu danh sách loa</button>
<p class="form-text mt-3">Ví dụ tên loa đích “phòng khách”: nói “gọi loa phòng khách”. Trên mỗi loa, chọn microphone như cấu hình VBot hiện có và thiết bị phát ALSA phù hợp. Cuộc gọi dùng mạng LAN, không dùng STT/TTS để truyền nội dung trò chuyện. Nhạc nội bộ được tạm dừng khi bắt đầu gọi.</p>
</form></div></div></section></main>
<?php include 'html_footer.php';include 'html_js.php'; ?>
<script>window.lanCallOptions=<?= json_encode(['active'=>($settings['active']??false)===true,'api'=>rtrim($URL_API_VBOT,'/').'/lan-calls','apiKey'=>!empty($Config['api']['auth']['active'])?($Config['api']['auth']['api_key']??''):'','peers'=>$peerDevices,'discoveredPeers'=>$discoveredPeers,'localId'=>$localId!==''?$localId:($settings['id']??'')],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script src="assets/js/lan-calls.js?v=9"></script></body></html>
