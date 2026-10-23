(() => {
  'use strict';
  const modal = document.getElementById('bluetooth-pan-modal');
  if (!modal) return;
  let busy = false;
  const notice = document.getElementById('pan-setup-notice');
  const info = document.getElementById('pan-setup-info');
  async function request(action) {
    const token = document.querySelector('meta[name="vbot-csrf-token"]')?.content || '';
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), action === 'install' ? 345000 : 50000);
    try {
      const response = await fetch('includes/php_ajax/Bluetooth_PAN.php', {
        method: 'POST', credentials: 'same-origin', signal: controller.signal,
        headers: {'X-CSRF-Token': token}, body: new URLSearchParams({action, csrf_token: token})
      });
      const data = await response.json();
      if (!data.success) throw new Error(data.message || 'Không thực hiện được yêu cầu');
      return data;
    } finally { clearTimeout(timer); }
  }
  function render(data) {
    info.replaceChildren();
    for (const [label, value] of [
      ['Cài đặt', data.installed ? 'Đã hoàn tất' : 'Chưa hoàn tất cài lần đầu'],
      ['Thành phần còn thiếu', data.missing.join(', ') || 'Không thiếu'],
      ['NetworkManager', data.network_manager], ['Dịch vụ Bluetooth', data.bluetooth],
      ['BNEP', data.bnep_loaded ? 'Đã nạp' : 'Chưa nạp'],
      ['Dịch vụ tự phát AP', data.wifi_guard], ['Sử dụng mạng PAN', data.enabled ? 'Đang bật' : 'Đang tắt'],
      ['File chính sách', data.policy || 'Chưa tạo']
    ]) {
      const dt = document.createElement('dt'); dt.className = 'col-sm-5'; dt.textContent = label;
      const dd = document.createElement('dd'); dd.className = 'col-sm-7'; dd.textContent = value;
      info.append(dt, dd);
    }
  }
  async function execute(action) {
    if (busy) return;
    busy = true;
    modal.querySelectorAll('button').forEach(b => { b.disabled = true; });
    notice.className = 'alert alert-info';
    notice.textContent = action === 'install' ? 'Đang cài đặt, vui lòng chờ và giữ trang mở…' : 'Đang kiểm tra…';
    try {
      const result = await request(action);
      render(action === 'inspect' ? result : await request('inspect'));
      notice.textContent = result.message || 'Đã kiểm tra Bluetooth PAN.';
      notice.className = 'alert alert-success';
    } catch (error) {
      notice.textContent = error.name === 'AbortError' ? 'Hết thời gian chờ. Nhấn Kiểm tra lại để xem trạng thái.' : error.message;
      notice.className = 'alert alert-danger';
    } finally { busy = false; modal.querySelectorAll('button').forEach(b => { b.disabled = false; }); }
  }
  modal.addEventListener('shown.bs.modal', () => execute('inspect'));
  document.getElementById('pan-setup-check').addEventListener('click', () => execute('inspect'));
  document.getElementById('pan-setup-install').addEventListener('click', () => execute('install'));
  if (location.hash === '#bluetooth-pan-setup') {
    const button = document.getElementById('bluetooth-pan-setup');
    button?.click();
  }
})();
