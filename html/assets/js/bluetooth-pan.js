(() => {
  'use strict';
  const el = id => document.getElementById(id);
  let busy = false;
  let savedEnabled = false;
  function updateSaveButton() {
    el('pan-save-enabled').disabled = busy || el('pan-enabled').disabled || el('pan-enabled').checked === savedEnabled;
  }
  function notice(text, error = false, notify = true) {
    el('pan-notice').className = `alert ${error ? 'alert-danger' : 'alert-info'} mt-3`;
    el('pan-notice').textContent = text;
    const display = error ? window.show_message : window.showMessagePHP;
    if (notify && typeof display === 'function') {
      const safe = document.createElement('span'); safe.textContent = text;
      display(safe.innerHTML);
    }
  }
  async function request(action, mac = '', enabled = false, mode = '') {
    const body = new URLSearchParams({action, mac, enabled: enabled ? '1' : '0', mode, csrf_token: window.vbotPanCsrf});
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 50000);
    try {
      const response = await fetch('includes/php_ajax/Bluetooth_PAN.php', {method: 'POST', body, signal: controller.signal, credentials: 'same-origin', headers: {'X-CSRF-Token': window.vbotPanCsrf}});
      const data = await response.json();
      if (!data.success) throw new Error(data.message || 'Không thực hiện được yêu cầu');
      return data;
    } finally { clearTimeout(timer); }
  }
  function cell(row, text) { const c = row.insertCell(); c.textContent = text; return c; }
  function render(data, preserveDraft = false) {
    const draft = el('pan-enabled').checked;
    const dirty = draft !== savedEnabled;
    savedEnabled = data.enabled;
    el('pan-enabled').checked = preserveDraft && dirty ? draft : data.enabled;
    el('pan-enabled').disabled = data.missing.length > 0;
    updateSaveButton();
    const probe = data.probe;
    el('pan-internet').className = `small ${probe ? probe.https_ok ? 'text-success' : 'text-warning' : 'text-muted'}`;
    el('pan-probe').disabled = !data.addresses.length;
    el('pan-internet').textContent = probe ? `${probe.message} (lần đo ${new Date(probe.at * 1000).toLocaleTimeString()}; gateway: ${probe.gateway_ok ? 'ping được' : 'không trả lời ping'}, DNS: ${probe.dns_ok ? 'OK — ' + probe.dns_server : 'chưa đạt'}, HTTPS: ${probe.https_ok ? 'OK' : 'chưa đạt'})` : data.addresses.length ? 'Đã có IP PAN; chưa xác nhận Internet.' : 'PAN chưa kết nối.';
    el('pan-alias').textContent = data.aliases.join(', ') || 'Chưa thấy adapter Bluetooth';
    const route = data.route.join(' ');
    el('pan-route').textContent = route.includes('dev bnep') ? 'Bluetooth PAN' : route.includes('dev wlan') ? 'Wi-Fi' : route || 'Chưa có đường mạng';
    el('pan-address').replaceChildren();
    for (const item of data.addresses) {
      const link = document.createElement('a');
      link.href = `${location.protocol}//${item.ip}${location.port ? ':' + location.port : ''}/`;
      link.textContent = `${item.interface}: ${item.ip}`;
      el('pan-address').append(link, document.createElement('br'));
    }
    if (!data.addresses.length) el('pan-address').textContent = 'Chưa kết nối PAN';
    const phones = new Map(data.phones.map(p => [p.mac, p]));
    for (const p of data.profiles) if (!phones.has(p.mac)) phones.set(p.mac, {mac: p.mac, name: p.name, nap: false});
    el('pan-devices').replaceChildren();
    for (const phone of phones.values()) {
      const profile = data.profiles.find(p => p.mac === phone.mac);
      const row = document.createElement('tr');
      cell(row, `${phone.name} (${phone.mac})`);
      const bluetooth = cell(row, phone.bluetooth_connected ? 'Đang kết nối' : 'Không kết nối');
      bluetooth.className = phone.bluetooth_connected ? 'text-success' : 'text-danger';
      const panStatus = cell(row, profile?.connected ? 'Đã kết nối' : phone.nap ? 'Chưa kết nối' : 'Chưa thấy hỗ trợ chia sẻ mạng');
      panStatus.className = profile?.connected ? 'text-success' : 'text-danger';
      const select = document.createElement('select'); select.className = 'form-select form-select-sm';
      const descriptions = {
        auto: 'VBot ưu tiên Wi-Fi. Mất Wi-Fi thì dùng PAN của điện thoại đang kết nối Bluetooth; có Wi-Fi trở lại thì ngắt PAN. AP cấu hình vẫn phát khi chưa có Wi-Fi.',
        on: 'Luôn tự kết nối mạng PAN khi điện thoại đang kết nối Bluetooth, kể cả khi có Wi-Fi.',
        off: 'Không tự kết nối mạng PAN khi Bluetooth đang kết nối. Vẫn có thể kết nối PAN thủ công.'
      };
      for (const [value, label] of [['auto','Auto VBot'],['on','Bật'],['off','Tắt']]) {
        const option = document.createElement('option'); option.value = value; option.textContent = label; option.title = descriptions[value]; select.append(option);
      }
      select.value = profile?.mode || phone.mode || 'auto'; select.disabled = !data.enabled || (!phone.nap && !profile);
      select.setAttribute('aria-label', `Tự kết nối ${phone.name}`); select.title = descriptions[select.value];
      select.addEventListener('change', () => change('mode', phone.mac, false, false, select.value));
      cell(row, '').append(select);
      const button = document.createElement('button'); button.type = 'button';
      button.className = `btn btn-sm ${profile?.connected ? 'btn-outline-danger' : 'btn-primary'}`;
      button.textContent = profile?.connected ? 'Ngắt PAN' : 'Kết nối PAN'; button.disabled = !data.enabled || (!phone.nap && !profile?.connected) || (!profile?.connected && select.value === 'auto' && data.wifi_connected === true);
      if (!profile?.connected && select.value === 'auto' && data.wifi_connected === true) button.title = 'Auto VBot đang ưu tiên Wi-Fi. Chọn Bật để sử dụng PAN cả khi có Wi-Fi.';
      button.addEventListener('click', () => change(profile?.connected ? 'disconnect' : 'connect', phone.mac));
      cell(row, '').append(button); el('pan-devices').append(row);
    }
    if (!phones.size) { const row = document.createElement('tr'); cell(row, 'Chưa có điện thoại đã ghép đôi.').colSpan = 5; el('pan-devices').append(row); }
  }
  async function change(action, mac = '', enabled = false, silent = false, mode = '') {
    if (busy) return;
    busy = true;
    el('pan-enabled').disabled = true;
    el('pan-save-enabled').disabled = true;
    el('pan-refresh').disabled = true;
    el('pan-probe').disabled = true;
    el('pan-devices').querySelectorAll('button,input,select').forEach(e => { e.disabled = true; });
    if (!silent) notice(action === 'probe' ? 'Đang kiểm tra gateway, DNS và HTTPS qua PAN…' : action === 'connect' ? 'Đang kết nối PAN, có thể mất khoảng 30 giây…' : 'Đang xử lý…', false, false);
    try {
      if (typeof window.loading === 'function') window.loading('show');
      const result = await request(action, mac, enabled, mode);
      render(action === 'status' ? result : await request('status'), silent);
      if (!silent) notice(result.message || 'Đã cập nhật trạng thái.', action === 'probe' && !result.probe?.https_ok);
    } catch (error) {
      notice(error.name === 'AbortError' ? 'Hết thời gian chờ. Nhấn Làm mới để kiểm tra kết quả.' : error.message, true);
      if (!silent) { try { render(await request('status')); } catch (_) { /* Keep the original error visible. */ } }
    } finally {
      busy = false;
      el('pan-refresh').disabled = false;
      updateSaveButton();
      if (typeof window.loading === 'function') window.loading('hide');
    }
  }
  el('pan-refresh').addEventListener('click', () => change('status'));
  el('pan-probe').addEventListener('click', () => change('probe'));
  el('pan-enabled').addEventListener('change', updateSaveButton);
  el('pan-save-enabled').addEventListener('click', () => change('enable', '', el('pan-enabled').checked));
  change('status');
})();
