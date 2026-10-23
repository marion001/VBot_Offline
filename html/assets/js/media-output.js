(() => {
  'use strict';
  const select = document.getElementById('media_output_select');
  const refresh = document.getElementById('media_output_refresh');
  if (!select || !refresh) return;
  let busy = false;
  let confirmed = '';
  let signature = '';
  let deferred = null;
  window.addEventListener('vbot-bluetooth-output', event => {
    if (busy) { deferred = event.detail; return; }
    render(event.detail);
    select.disabled = refresh.disabled = false;
  });
  function render(data) {
    if (!data || !Array.isArray(data.devices)) return;
    const next = JSON.stringify([data.devices.filter(device => data.roles?.source !== false && device.paired && device.audio_sink && device.output_connected).map(device => [device.mac, device.name]), data.route?.device || '']);
    if (signature === next && select.value === confirmed) return;
    signature = next;
    select.replaceChildren();
    const add = (value, label) => {
      const option = document.createElement('option');
      option.value = value; option.textContent = label; select.append(option);
    };
    add('', select.dataset.localLabel || 'Loa VBot');
    for (const device of data.devices || []) {
      if (data.roles?.source !== false && device.paired && device.audio_sink && device.output_connected) {
        add(device.mac, `${device.name} — Bluetooth`);
      }
    }
    const target = data.route?.device || '';
    select.value = [...select.options].some(option => option.value === target) ? target : '';
    confirmed = select.value;
  }
  async function request(action, mac = '', showLoading = true) {
    if (busy) return;
    busy = true; select.disabled = refresh.disabled = true;
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 50000);
    try {
      if (showLoading && typeof window.loading === 'function') window.loading('show');
      const body = new URLSearchParams({action, mac, enabled: '0', csrf_token: window.VBOT_CSRF_TOKEN || ''});
      const response = await fetch('includes/php_ajax/Bluetooth_Source.php', {
        method: 'POST', credentials: 'same-origin', body, signal: controller.signal
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Không chuyển được đầu ra âm thanh');
      render(data);
      // Older SSE packets received during this request must not undo its result.
      deferred = null;
    } catch (error) {
      select.value = confirmed;
      if (showLoading && typeof window.showMessagePHP === 'function') {
        const safe = document.createElement('span');
        safe.textContent = error.name === 'AbortError' ? 'Hết thời gian chờ đầu ra âm thanh' : error.message;
        window.showMessagePHP(safe.innerHTML);
      }
    } finally {
      clearTimeout(timer);
      select.disabled = refresh.disabled = false;
      busy = false;
      if (deferred) { render(deferred); deferred = null; }
      if (showLoading && typeof window.loading === 'function') window.loading('hide');
    }
  }
  select.addEventListener('change', () => request(select.value ? 'select' : 'local', select.value));
  refresh.addEventListener('click', () => request('status'));
  request('status', '', false);
})();
