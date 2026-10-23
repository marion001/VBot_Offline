(() => {
  'use strict';
  let busy = false;
  let events = null, deferred = null, signature = '', lastLimit = null, lastInputLimit = null;
  const byId = id => document.getElementById(id);
  const notice = (message, error = false, notify = true) => {
    byId('source-notice').className = `alert alert-${error ? 'danger' : 'info'} mt-3`;
    byId('source-notice').textContent = message;
    const display = error ? window.show_message : window.showMessagePHP;
    if (notify && typeof display === 'function') {
      const safe = document.createElement('span'); safe.textContent = message;
      display(safe.innerHTML);
    }
  };
  function render(data, forceLimit = false) {
    if (!data || !Array.isArray(data.devices) || !data.settings) return;
    const next = JSON.stringify([data.devices.map(({rssi, ...device}) => device), data.settings, data.route?.device || '', data.adapter, data.installed, data.max_output_connections, data.max_input_connections, data.roles]);
    if (next === signature && !forceLimit) return;
    signature = next;
    window.vbotBluetoothDevices = data.devices;
    const limit = byId('source-connection-limit');
    if (forceLimit || lastLimit === null || Number(limit.value) === lastLimit) limit.value = data.max_output_connections ?? 5;
    lastLimit = data.max_output_connections ?? 5;
    const inputLimit = byId('source-input-limit');
    if (inputLimit && (forceLimit || lastInputLimit === null || Number(inputLimit.value) === lastInputLimit)) inputLimit.value = data.max_input_connections ?? 2;
    lastInputLimit = data.max_input_connections ?? 2;
    byId('source-alias').textContent = data.adapter || 'Không có bộ điều khiển Bluetooth';
    const selected = data.devices.find(device => device.mac === data.settings.selected);
    byId('source-current').textContent = data.route?.device ? `${data.route.name || 'Bluetooth'} (${data.route.device})` : 'Loa VBot';
    byId('source-selected').textContent = selected ? `${selected.name} (${selected.mac})` : data.settings.selected || 'Chưa chọn loa Bluetooth';
    const known = device => device.paired || device.connected;
    const sorted = [...data.devices].sort((a, b) => Number(b.connected)-Number(a.connected) || String(a.name).localeCompare(String(b.name), 'vi'));
    const palette = ['table-light', 'table-primary', 'table-info', 'table-secondary'];
    const colors = new Map([...new Set(data.devices.map(device => device.mac))].sort().map((mac, index) => [mac, palette[index % palette.length]]));
    const groups = [
      {id: 'source-input-devices', role: 'input', devices: sorted.filter(device => known(device) && device.audio_source)},
      {id: 'source-devices', role: 'output', devices: sorted.filter(device => known(device) && device.audio_sink)},
      {id: 'source-other-devices', role: 'other', devices: sorted.filter(device => !known(device) || (!device.audio_source && !device.audio_sink))}
    ];
    for (const group of groups) {
      const body = byId(group.id);
      body.replaceChildren();
      for (const device of group.devices) {
        const output = group.role === 'output';
        const connected = output ? device.output_connected : device.input_connected;
        const running = output ? device.output_running : device.input_running;
        const chosen = output && data.route?.device === device.mac;
        const deviceColor = data.route?.device === device.mac ? 'table-success' : colors.get(device.mac);
        const row = document.createElement('tr');
        row.className = deviceColor;
        const cell = (text, color = '') => {
          const value = document.createElement('td');
          value.textContent = text; value.className = color; value.style.whiteSpace = 'pre-line';
          row.append(value); return value;
        };
        cell(`${device.name}\n${device.mac}`);
        cell(device.paired ? 'Đã ghép đôi' : 'Chưa ghép đôi', device.paired ? 'text-primary' : 'text-danger');
        cell(device.connected ? 'Đã kết nối Bluetooth' : 'Không kết nối', device.connected ? 'text-success' : 'text-danger');
        const audio = cell(group.role === 'other' ? 'Chưa xác định' : connected ? running ? 'Luồng âm thanh đang chạy' : 'Đã kết nối âm thanh' : 'Chưa kết nối âm thanh', connected ? 'text-success' : group.role === 'other' ? 'text-secondary' : 'text-danger');
        if (chosen || (output && data.settings.selected === device.mac)) {
          const badge = document.createElement('span');
          badge.className = `badge d-block mt-1 ${chosen ? 'bg-success' : 'bg-warning text-dark'}`;
          badge.textContent = chosen ? 'Đang chọn làm đầu ra' : 'Đã chọn, chờ kết nối';
          audio.append(badge);
        }
        if (output) {
          const autoCell = cell('');
          const wrapper = document.createElement('div'); wrapper.className = 'form-check form-switch';
          const auto = document.createElement('input');
          auto.type = 'checkbox'; auto.className = 'form-check-input border-success'; auto.setAttribute('role', 'switch');
          auto.setAttribute('aria-label', `Tự kết nối ${device.name}`);
          auto.checked = data.settings.selected === device.mac && data.settings.autoconnect;
          auto.disabled = data.roles?.source === false || data.settings.selected !== device.mac;
          if (auto.disabled) wrapper.title = 'Chọn loa làm đầu ra trước để thiết lập tự kết nối.';
          auto.addEventListener('change', () => { const enabled = auto.checked; auto.checked = !enabled; request('autoconnect', device.mac, enabled); });
          wrapper.append(auto); autoCell.append(wrapper);
        }
        const actions = cell('');
        const buttons = document.createElement('div'); buttons.className = 'd-flex flex-wrap gap-1';
        const add = (label, action, danger = false, disabled = false) => {
          const button = document.createElement('button');
          button.type = 'button'; button.textContent = label;
          button.className = `btn btn-sm ${danger ? 'btn-outline-danger' : 'btn-primary'}`;
          button.disabled = disabled || (['connect','select'].includes(action) && data.roles?.source === false) || (action === 'sink_connect' && data.roles?.sink === false);
          button.addEventListener('click', () => request(action, device.mac));
          buttons.append(button);
        };
        if (!device.paired) {
          add('Ghép đôi', 'pair');
        } else if (output) {
          if (!connected) add('Kết nối và phát', 'connect');
          else {
            if (!chosen) add('Dùng loa này', 'select');
            add('Ngắt âm thanh', 'disconnect', true);
          }
        } else if (group.role === 'input') {
          if (connected) add('Ngắt nhạc đầu vào', 'sink_disconnect', true, data.settings.selected === device.mac);
          else add('Nhận nhạc vào VBot', 'sink_connect', false, data.settings.selected === device.mac);
        }
        if (device.paired) {
          const menu = document.createElement('div'); menu.className = 'dropdown';
          const toggle = document.createElement('button'); toggle.type = 'button';
          toggle.className = 'btn btn-sm btn-outline-secondary dropdown-toggle'; toggle.textContent = 'Thêm';
          toggle.setAttribute('data-bs-toggle', 'dropdown'); toggle.setAttribute('aria-expanded', 'false');
          const list = document.createElement('ul'); list.className = 'dropdown-menu';
          const item = document.createElement('li');
          const forget = document.createElement('button'); forget.type = 'button'; forget.className = 'dropdown-item text-danger';
          forget.textContent = 'Quên thiết bị';
          forget.addEventListener('click', () => {
            if (window.confirm(`Quên ${device.name}? Ghép đôi âm thanh và PAN của thiết bị này sẽ bị xóa.`)) request('forget', device.mac);
          });
          item.append(forget); list.append(item); menu.append(toggle, list); buttons.append(menu);
        }
        actions.append(buttons); body.append(row);
        if (!known(device)) continue;
        const keyRow = body.insertRow();
        keyRow.className = deviceColor;
        const keyCell = keyRow.insertCell(); keyCell.colSpan = output ? 6 : 5;
        const keyHost = document.createElement('div');
        keyHost.className = 'bt-device-keys border rounded p-2';
        keyHost.dataset.mac = device.mac;
        keyHost.dataset.tableClass = deviceColor;
        keyHost.textContent = 'Đang tải nút điều khiển của thiết bị…';
        keyCell.append(keyHost);
      }
      if (!group.devices.length) {
        const row = body.insertRow(); const cell = row.insertCell(); cell.colSpan = group.role === 'output' ? 6 : 5;
        cell.textContent = group.role === 'other' ? 'Không có thiết bị chưa xác định.' : 'Chưa có thiết bị trong nhóm. Bật chế độ ghép đôi rồi nhấn Quét thiết bị.';
      }
    }
    window.dispatchEvent(new CustomEvent('vbot-bluetooth-devices', {detail: data.devices}));
    if (!data.installed) notice('Chưa bật dịch vụ A2DP Source. Mở Thiết lập nâng cao để cài đặt.', false, false);
  }

  async function request(action, mac = '', enabled = false) {
    if (busy || window.vbotBluetoothKeyBusy) return;
    busy = true;
    window.vbotBluetoothSourceBusy = true;
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), action === 'install' ? 340000 : 50000);
    const controls = [...document.querySelectorAll('#main button, #main input')];
    const states = controls.map(control => control.disabled);
    let rebootReminder = false;
    controls.forEach(control => { control.disabled = true; });
    notice(action === 'scan' ? 'Đang quét loa/tai nghe Bluetooth trong 12 giây…' : 'Đang xử lý…', false, false);
    try {
      if (typeof window.loading === 'function') window.loading('show');
      const body = new URLSearchParams({action, mac, enabled: enabled ? '1' : '0', csrf_token: window.vbotSourceCsrf});
      if (action === 'connection_limit') body.set('max_connections', byId('source-connection-limit').value);
      if (action === 'input_connection_limit') body.set('max_connections', byId('source-input-limit').value);
      const response = await fetch('includes/php_ajax/Bluetooth_Source.php', {method: 'POST', credentials: 'same-origin', body, signal: controller.signal});
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Không hoàn tất thao tác Bluetooth');
      if (data.devices) render(data, action === 'connection_limit' || action === 'input_connection_limit');
      deferred = null;
      notice(data.message || (data.installed === false ? 'Chưa bật dịch vụ A2DP Source. Nhấn Cài đặt / Bật A2DP Source.' : 'Đã cập nhật trạng thái Bluetooth.'));
      if (action === 'install') {
        busy = false;
        window.vbotBluetoothSourceBusy = false;
        await request('status');
        rebootReminder = true;
      }
    } catch (error) {
      notice(error.name === 'AbortError' ? 'Hết thời gian chờ; nhấn Làm mới để kiểm tra trạng thái.' : error.message, true);
    } finally {
      clearTimeout(timeout);
      controls.forEach((control, index) => { if (control.isConnected) control.disabled = states[index]; });
      busy = false;
      window.vbotBluetoothSourceBusy = false;
      flushDeferred();
      if (typeof window.loading === 'function') window.loading('hide');
      if (rebootReminder && typeof window.show_message === 'function') {
        window.show_message('Đã hoàn tất cài đặt Bluetooth. Vui lòng khởi động lại loa VBot để các thay đổi cấu hình được áp dụng đầy đủ. Hệ thống không tự khởi động lại.');
      }
    }
  }
  byId('source-refresh').onclick = () => request('status');
  byId('source-limit-save').onclick = () => request('connection_limit');
  byId('source-input-limit-save').onclick = () => request('input_connection_limit');
  byId('source-scan').onclick = () => request('scan');
  byId('source-devices-scan').onclick = () => request('scan');
  byId('source-local').onclick = () => request('local');
  byId('source-install').onclick = () => {
    if (window.confirm('Cài đặt sẽ sao lưu và khởi động lại dịch vụ âm thanh Bluetooth; đồng thời kiểm tra sửa firmware Wi-Fi/Bluetooth theo phần cứng. Nếu firmware thay đổi, bạn cần reboot để áp dụng. Không tự ngắt Wi-Fi hoặc reboot. Tiếp tục?')) request('install');
  };
  function receive(data) {
    if (busy || window.vbotBluetoothKeyBusy) { deferred = data; return; }
    render(data);
  }
  function flushDeferred() {
    if (deferred && !busy && !window.vbotBluetoothKeyBusy) {
      const data = deferred; deferred = null; render(data);
    }
  }
  function startEvents() {
    if (events || typeof EventSource !== 'function') return;
    events = new EventSource('includes/php_ajax/Bluetooth_Source_Events.php');
    events.addEventListener('update', event => {
      try { receive(JSON.parse(event.data)); } catch (error) { console.warn('Bluetooth SSE:', error); }
    });
  }
  window.addEventListener('vbot-bluetooth-keys-idle', flushDeferred);
  window.addEventListener('pagehide', () => { if (events) events.close(); events = null; });
  window.addEventListener('pageshow', startEvents);
  startEvents();
  request('status');
})();
