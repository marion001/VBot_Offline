(() => {
  'use strict';
  const el = id => document.getElementById(id);
  let busy = false;
  let transaction = {};
  async function api(action, data = {}) {
    const body = new URLSearchParams({action, uuid: el('wifi-profile-uuid').value, ...data});
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 45000);
    try {
      const response = await fetch('includes/php_ajax/Wifi_Act.php', {method: 'POST', body,
        signal: controller.signal, credentials: 'same-origin', headers: {'X-CSRF-Token': window.VBOT_CSRF_TOKEN || ''}});
      const result = await response.json();
      if (!result.success) throw new Error(result.message || 'Không thực hiện được thao tác Wi-Fi');
      return result;
    } finally { clearTimeout(timer); }
  }
  async function operation(fn) {
    if (busy) return;
    busy = true;
    try { await fn(); } catch (error) {
      show_message(error.name === 'AbortError' ? 'Hết thời gian chờ. Hãy mở lại địa chỉ IP của loa và kiểm tra áp dụng; không cần reboot.' : wifiEscapeHtml(error.message));
    } finally { busy = false; }
  }
  window.wifiLoadTransaction = async () => {
    if (!el('wifi-profile-uuid').value) return;
    try {
      const result = await api('transaction');
      transaction = result.transaction || {};
      newAddress(transaction.new_ip);
      el('wifi-apply').disabled = transaction.stage !== 'saved';
      el('wifi-confirm').disabled = transaction.stage !== 'awaiting_confirm';
      const labels = {saved: 'Đã lưu. Nhấn Áp dụng kết nối để dùng cấu hình mới.', scheduled: 'Chuẩn bị áp dụng. Mở lại trang sau vài giây.', applying: 'Đang áp dụng. Mở lại trang tại địa chỉ IP mới nếu địa chỉ thay đổi.', awaiting_confirm: 'Kết nối mới đang chờ xác nhận. Nhấn Xác nhận kết nối hoạt động trong 120 giây kể từ lúc áp dụng.', confirmed: 'Đã xác nhận cấu hình mới.', rolled_back: 'Đã khôi phục cấu hình cũ vì chưa xác nhận hoặc áp dụng lỗi.'};
      if (labels[transaction.stage]) el('wifi-apply-info').textContent = labels[transaction.stage];
    } catch (error) { el('wifi-apply-info').textContent = error.message; }
  };
  window.wifiPopulateConfig = data => {
    if (document.querySelector('#exampleModal_ipDong.show, #exampleModal_dns_only.show')) return;
    el('dia_chi_ip_tinh').value = data.IP && data.IP !== 'N/A' ? data.IP : '';
    el('ip_gateway_modem').value = data.Gateway && data.Gateway !== 'N/A' ? data.Gateway : '';
  };
  function newAddress(ip) {
    el('wifi-new-address').replaceChildren();
    if (!ip) return;
    const link = document.createElement('a'); const target = new URL(location.href);
    target.hostname = ip; target.pathname = target.pathname.replace(/[^/]*$/, 'Wifi.php'); target.hash = '';
    link.href = target.href; link.textContent = 'Địa chỉ dự kiến sau áp dụng: ' + target.href;
    el('wifi-new-address').append(link);
  }
  async function save(action, data) {
    await operation(async () => {
      const result = await api(action, data);
      for (const id of ['exampleModal_ipDong', 'exampleModal_dns_only']) bootstrap.Modal.getInstance(el(id))?.hide();
      newAddress(result.new_ip);
      await window.wifiLoadTransaction();
      show_message(wifiEscapeHtml(result.message));
    });
  }
  window.set_static_ip = () => save('set_static_ip', {ip: el('dia_chi_ip_tinh').value.trim(), gateway: el('ip_gateway_modem').value.trim(), dns1: el('set_dns_1').value.trim(), dns2: el('set_dns_2').value.trim()});
  window.set_dhcp_mode = () => save('use_dhcp_automatically', {dns_source: el('wifi-dhcp-dns').value});
  window.set_dns_only = () => save('set_dns_only', {dns1: el('set_dns_only_1').value.trim(), dns2: el('set_dns_only_2').value.trim()});
  window.resetDNS_DHCP = () => save('reset_dns_dhcp', {});
  window.reset_All_Wifi = () => operation(async () => {
    if (!confirm('Xóa tất cả hồ sơ Wi-Fi? PAN/Ethernet/VPN được giữ nguyên. Wi-Fi hiện tại có thể bị ngắt; loa chỉ phát AP nếu không còn đường mạng khác.')) return;
    const result = await api('reset_wifi', {Reset_Wifi: '1'});
    show_message(wifiEscapeHtml(result.message));
  });
  window.saveWifiPreferences = (uuid, auto, priority, button) => operation(async () => {
    button.disabled = true;
    try { const result = await api('preferences', {uuid, autoconnect: auto ? 'yes' : 'no', priority}); show_message(wifiEscapeHtml(result.message)); }
    finally { button.disabled = false; }
  });
  el('wifi-apply').addEventListener('click', () => operation(async () => {
    if (!confirm('Áp dụng cấu hình đã lưu? Kết nối WebUI có thể ngắt. Nếu không xác nhận trong 120 giây, loa sẽ khôi phục cấu hình cũ.')) return;
    const result = await api('apply');
    el('wifi-apply').disabled = true;
    el('wifi-apply-info').textContent = result.message;
  }));
  el('wifi-confirm').addEventListener('click', () => operation(async () => {
    const result = await api('confirm', {token: transaction.token || ''});
    show_message(wifiEscapeHtml(result.message)); await window.wifiLoadTransaction();
  }));
  el('wifi-transaction').addEventListener('click', window.wifiLoadTransaction);
  el('wifi-reload-info').addEventListener('click', () => getWifiNetworkInformation());
  el('wifi-probe').addEventListener('click', () => operation(async () => {
    el('wifi-probe').disabled = true; el('wifi-probe-result').textContent = 'Đang kiểm tra gateway, DNS và HTTPS qua Wi-Fi…';
    try {
      const result = await api('probe'); const p = result.probe;
      el('wifi-probe-result').textContent = `${result.message} Giao diện: ${p.interface}; gateway ${p.gateway}; DNS ${p.dns_ok ? p.dns_server : 'chưa đạt'}; HTTPS ${p.https_ok ? 'OK' : 'chưa đạt'}.`;
    } catch (error) { el('wifi-probe-result').textContent = error.message; }
    finally { el('wifi-probe').disabled = false; }
  }));

  // Real form inputs mask passwords and handle hidden/open networks separately.
  const container = document.createElement('div');
  container.innerHTML = '<div class="modal fade" id="wifi-connect-modal" tabindex="-1" aria-labelledby="wifi-connect-title"><div class="modal-dialog"><form class="modal-content" id="wifi-connect-form"><div class="modal-header"><h5 class="modal-title" id="wifi-connect-title">Kết nối Wi-Fi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body"><label class="form-label" for="wifi-connect-ssid">Tên mạng (SSID)</label><input class="form-control mb-3" id="wifi-connect-ssid" required><div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="wifi-connect-secure"><label class="form-check-label" for="wifi-connect-secure">Mạng có mật khẩu (WPA Personal)</label></div><label class="form-label" for="wifi-connect-password">Mật khẩu</label><input class="form-control" type="password" autocomplete="new-password" id="wifi-connect-password"><p class="small text-muted mt-2">Kết nối mạng mới có thể đổi IP và làm ngắt WebUI. Hãy mở địa chỉ mới hoặc tìm loa trên router nếu cần.</p><div id="wifi-connect-error" class="text-danger" role="status"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button type="submit" class="btn btn-primary" id="wifi-connect-submit">Kết nối</button></div></form></div></div>';
  document.body.append(container);
  let hidden = false;
  function passwordState() { el('wifi-connect-password').disabled = !el('wifi-connect-secure').checked; }
  el('wifi-connect-secure').addEventListener('change', passwordState);
  window.connectWifiNew = (ssid, security) => {
    hidden = ssid === 'Mạng ẩn';
    el('wifi-connect-ssid').value = hidden ? '' : ssid;
    el('wifi-connect-ssid').readOnly = !hidden;
    el('wifi-connect-secure').checked = !!security && security !== '--';
    el('wifi-connect-password').value = ''; el('wifi-connect-error').textContent = '';
    passwordState(); bootstrap.Modal.getOrCreateInstance(el('wifi-connect-modal')).show();
  };
  el('wifi-connect-form').addEventListener('submit', async event => {
    event.preventDefault(); if (busy) return;
    const ssid = el('wifi-connect-ssid').value;
    const password = el('wifi-connect-secure').checked ? el('wifi-connect-password').value : '';
    if (!ssid || new TextEncoder().encode(ssid).length > 32) { el('wifi-connect-error').textContent = 'SSID phải dài 1–32 byte.'; return; }
    if (el('wifi-connect-secure').checked && !(password.length >= 8 && password.length <= 63) && !/^[0-9a-f]{64}$/i.test(password)) { el('wifi-connect-error').textContent = 'Mật khẩu cần 8–63 ký tự hoặc PSK 64 ký tự hex.'; return; }
    busy = true; el('wifi-connect-submit').disabled = true;
    try {
      const result = await api('connect_and_save_wifi', {uuid: '', Connect_Wifi: '1', ssid, password, hidden: hidden ? '1' : ''});
      bootstrap.Modal.getInstance(el('wifi-connect-modal')).hide(); el('wifi-connect-password').value = '';
      show_message(wifiEscapeHtml(result.message)); getWifiNetworkInformation();
    } catch (error) { el('wifi-connect-error').textContent = error.name === 'AbortError' ? 'Hết thời gian chờ; kiểm tra IP mới của loa.' : error.message; }
    finally { busy = false; el('wifi-connect-submit').disabled = false; }
  });
  window.wifiLoadTransaction();
})();
