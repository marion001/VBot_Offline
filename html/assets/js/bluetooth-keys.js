(() => {
  'use strict';
  const el = id => document.getElementById(id);
  let busy = false, selected = null, buttons = [], actions = {}, devices = window.vbotBluetoothDevices || [], loaded = false, loadError = '', loadPending = false;
  const modal = () => bootstrap.Modal.getOrCreateInstance(el('bt-key-modal'));
  let modalMac = '';
  const status = (message, error = false, popup = true) => {
    el('bt-key-modal-status').textContent = message;
    el('bt-key-modal-status').className = `small mb-3 text-${error ? 'danger' : 'success'}`;
    if (popup) {
      const safe = document.createElement('span'); safe.textContent = message;
      if (typeof window.showMessagePHP === 'function') window.showMessagePHP(safe.innerHTML);
      if (error && typeof window.show_message === 'function') window.show_message(safe.innerHTML);
    }
  };
  async function request(action, values = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 45000);
    try {
      const response = await fetch('includes/php_ajax/Bluetooth_Source.php', {
        method: 'POST', credentials: 'same-origin', signal: controller.signal,
        body: new URLSearchParams({...values, action: `key_${action}`, csrf_token: window.vbotSourceCsrf})
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Không thực hiện được thao tác nút Bluetooth');
      return data;
    } finally { clearTimeout(timer); }
  }
  async function task(operation, showLoading = true) {
    if (busy || window.vbotBluetoothSourceBusy) return;
    busy = true;
    window.vbotBluetoothKeyBusy = true;
    const controls = [...document.querySelectorAll('#main button, #main input, #main select, #bt-key-modal button, #bt-key-modal input, #bt-key-modal select')];
    const states = controls.map(control => control.disabled);
    controls.forEach(control => { control.disabled = true; });
    if (showLoading && typeof window.loading === 'function') window.loading('show');
    try { await operation(); }
    catch (error) { status(error.name === 'AbortError' ? 'Hết thời gian chờ thao tác. Hãy thử lại.' : error.message, true); }
    finally {
      controls.forEach((control, index) => { if (control.isConnected) control.disabled = states[index]; });
      busy = false;
      window.vbotBluetoothKeyBusy = false;
      window.dispatchEvent(new CustomEvent('vbot-bluetooth-keys-idle'));
      el('bt-key-save').disabled = !selected;
      if (showLoading && typeof window.loading === 'function') window.loading('hide');
    }
  }
  function edit(binding) {
    modalMac = binding.mac;
    el('bt-key-modal-status').textContent = '';
    selected = {...binding};
    el('bt-key-name').value = binding.name || '';
    el('bt-key-code').value = binding.code || '';
    el('bt-key-enabled').checked = binding.enabled !== false;
    const device = devices.find(device => device.mac === binding.mac);
    el('bt-key-modal-device').textContent = device ? `${device.name} (${binding.mac})` : binding.mac;
    const select = el('bt-key-action'); select.replaceChildren();
    const options = {...actions};
    if (binding.action && !options[binding.action]) options[binding.action] = `Chức năng đã lưu (không còn khả dụng): ${binding.action}`;
    for (const [value, label] of Object.entries(options)) select.add(new Option(label, value));
    select.value = binding.action || 'none';
    el('bt-key-save').disabled = !binding.code;
    el('bt-key-relearn').disabled = !device?.connected;
    modal().show();
  }
  function render() {
    for (const host of document.querySelectorAll('.bt-device-keys')) {
      const mac = host.dataset.mac, device = devices.find(device => device.mac === mac);
      host.replaceChildren();
      const toolbar = document.createElement('div'); toolbar.className = 'd-flex flex-wrap align-items-center justify-content-between gap-2 mb-2';
      const title = document.createElement('strong'); title.textContent = 'Nút điều khiển đã học';
      const learnButton = document.createElement('button'); learnButton.type = 'button';
      learnButton.className = 'btn btn-primary btn-sm'; learnButton.textContent = 'Học nút';
      learnButton.disabled = !loaded || !device?.connected;
      learnButton.title = device?.connected ? 'Học nút từ thiết bị này trong 15 giây' : 'Kết nối Bluetooth của thiết bị trước khi học nút';
      learnButton.onclick = () => learn(mac);
      toolbar.appendChild(title); toolbar.appendChild(learnButton); host.appendChild(toolbar);
      if (!loaded) {
        const pending = document.createElement('span');
        pending.className = loadError ? 'small text-danger' : 'small text-muted';
        pending.textContent = loadError ? 'Không tải được danh sách nút. Nhấn Làm mới để thử lại.' : 'Đang tải danh sách nút…';
        host.appendChild(pending);
        continue;
      }
      const bindings = buttons.filter(binding => binding.mac === mac);
      if (!bindings.length) {
        const empty = document.createElement('span'); empty.className = 'small text-muted';
        empty.textContent = 'Thiết bị này chưa có nút đã học.'; host.appendChild(empty);
        continue;
      }
      const responsive = document.createElement('div'); responsive.className = 'table-responsive';
      const table = document.createElement('table'); table.className = `table table-sm table-bordered align-middle mb-0 ${host.dataset.tableClass || ''}`;
      table.style.setProperty('--bs-table-border-color', host.dataset.borderColor || '#dc3545');
      table.style.setProperty('border-color', host.dataset.borderColor || '#dc3545');
      const head = document.createElement('thead');
      const heading = document.createElement('tr');
      for (const label of ['Mã nút', 'Tên nút', 'Chức năng', 'Kích hoạt', 'Thao tác']) {
        const cell = document.createElement('th'); cell.textContent = label; heading.appendChild(cell);
      }
      head.appendChild(heading); table.appendChild(head);
      const body = document.createElement('tbody'); table.appendChild(body); responsive.appendChild(table); host.appendChild(responsive);
      for (const binding of bindings) {
      const row = document.createElement('tr');
      for (const value of [binding.code, binding.name, actions[binding.action] || binding.action]) {
        const cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell);
      }
      const active = document.createElement('td');
      active.textContent = binding.enabled === false ? 'Đang tắt' : 'Đang bật';
      active.className = binding.enabled === false ? 'text-danger' : 'text-success';
      row.appendChild(active);
      const cell = document.createElement('td');
      for (const [label, color, handler] of [
        ['Sửa', 'primary', () => { if (!busy && !window.vbotBluetoothSourceBusy) edit(binding); }],
        ['Xóa', 'danger', () => {
          if (confirm(`Xóa nút “${binding.name}”?`)) task(async () => {
            buttons = (await request('delete', binding)).buttons; render();
            if (selected?.mac === binding.mac && selected?.code === binding.code) {
              selected = null; modal().hide();
            }
            status('Đã xóa nút Bluetooth');
          });
        }]
      ]) {
        const button = document.createElement('button'); button.type = 'button';
        button.className = `btn btn-${color} btn-sm me-1`; button.textContent = label;
        button.onclick = handler; cell.appendChild(button);
      }
      row.appendChild(cell); body.appendChild(row);
      }
    }
  }
  window.addEventListener('vbot-bluetooth-devices', event => {
    devices = event.detail || [];
    render();
    if (!loaded && !loadPending) loadKeys();
  });
  function learn(mac) {
    if (!devices.some(device => device.mac === mac && device.connected)) return status('Hãy kết nối thiết bị Bluetooth trước', true);
    if (busy || window.vbotBluetoothSourceBusy) return;
    edit({mac, action: 'none'});
    selected = null;
    task(async () => {
      status('Hãy nhấn một nút trên thiết bị đã chọn trong 15 giây…');
      const result = await request('learn', {mac});
      edit(buttons.find(button => button.mac === mac && button.code === result.code) || {mac, code: result.code, action: 'none'});
      status('Đã nhận nút. Đặt tên, chọn chức năng và lưu trong 3 phút.');
    });
  }
  el('bt-key-relearn').onclick = () => {
    if (busy || window.vbotBluetoothSourceBusy) return;
    const mac = modalMac;
    if (!devices.some(device => device.mac === mac && device.connected)) return status('Hãy kết nối lại thiết bị trước khi học lại', true);
    const previous = selected ? {...selected} : null;
    const draft = {name: el('bt-key-name').value, action: el('bt-key-action').value, enabled: el('bt-key-enabled').checked};
    task(async () => {
      status('Hãy nhấn lại nút trên thiết bị trong 15 giây…');
      const result = await request('learn', {mac});
      const existing = buttons.find(button => button.mac === mac && button.code === result.code);
      if (existing && result.code !== previous?.code && !confirm(`Mã nút ${result.code} đã được gán cho “${existing.name}”. Thay thế khi lưu?`)) {
        status('Đã hủy thay đổi mã nút. Cấu hình cũ được giữ nguyên.');
        return;
      }
      const previousCode = previous?.previous_code || (buttons.some(button => button.mac === mac && button.code === previous?.code) ? previous.code : 0);
      edit({mac, code: result.code, previous_code: previousCode, ...draft});
      status('Đã học lại nút. Nhấn Lưu nút để áp dụng.');
    });
  };
  el('bt-key-form').onsubmit = event => {
    event.preventDefault();
    if (!selected) return;
    task(async () => {
      buttons = (await request('save', {...selected, name: el('bt-key-name').value, function: el('bt-key-action').value, enabled: el('bt-key-enabled').checked ? '1' : '0'})).buttons;
      render(); selected = null; modal().hide();
      status('Đã lưu nút Bluetooth');
    });
  };
  el('bt-key-modal').addEventListener('hidden.bs.modal', () => { selected = null; el('bt-key-save').disabled = true; });
  el('bt-key-modal').addEventListener('shown.bs.modal', () => { if (!busy) el('bt-key-name').focus(); });
  async function loadKeys() {
    if (loadPending) return;
    loadPending = true;
    try {
      const data = await request('list');
      buttons = data.buttons; actions = data.actions; loaded = true; loadError = ''; render();
    } catch (error) {
      loadError = error.message; render(); status(error.message, true);
    } finally { loadPending = false; }
  }
  loadKeys();
})();
