(() => {
  'use strict';
  const select = document.getElementById('media_source_select');
  if (!select) return;
  let last = null, signature = '';
  const validMac = value => typeof value === 'string' && /^(?:[0-9A-F]{2}:){5}[0-9A-F]{2}$/.test(value);
  window.renderVbotMediaInputs = (data, kind) => {
    last = [data, kind];
    if (select.disabled || document.activeElement === select) return;
    const bt = data.bluetooth || {}, media = data.media_player || {};
    const snapshot = data.bluetooth_output;
    const known = Array.isArray(snapshot?.devices) ? snapshot.devices : [];
    const incoming = Object.values(bt.bluetooth_devices || {}).filter(d => d.connected && !known.some(k => k.mac === d.mac && !k.audio_source));
    const rows = [...known.filter(d => d.audio_source && d.connected && d.paired), ...incoming];
    const devices = [...new Map(rows.map(d => ({...d, mac: String(d.mac || '').toUpperCase()})).filter(d => validMac(d.mac)).map(d => [d.mac, d])).values()];
    devices.sort((a, b) => String(a.name || a.mac).localeCompare(String(b.name || b.mac)));
    const enabled = bt.active && snapshot?.roles?.sink !== false;
    const next = JSON.stringify([devices.map(d => [d.mac, d.name]), Boolean(enabled), Boolean(media.multiroom_active), Boolean(media.airplay_active)]);
    if (next !== signature) {
      signature = next;
      select.replaceChildren();
      const add = (value, label, disabled = false) => {
        const option = document.createElement('option');
        option.value = value; option.textContent = label; option.disabled = disabled;
        select.append(option);
      };
      add('', 'Chưa có nguồn phát', true);
      add('local_media', 'Local / nhạc do VBot phát');
      const names = devices.map(d => d.name || d.mac);
      for (const device of devices) {
        const name = device.name || device.mac;
        add('bluetooth:' + device.mac, 'Bluetooth ' + name + (names.filter(n => n === name).length > 1 ? ' (' + device.mac + ')' : ''), !enabled);
      }
      if (!devices.length) add('bluetooth', 'Bluetooth — chưa có điện thoại kết nối', true);
      add('airplay', 'AirPlay', !media.airplay_active);
      add('multiroom', 'Multiroom Audio', !media.multiroom_active);
    }
    const preferred = media.selected_source;
    if (preferred === 'bluetooth' && media.playback_state !== 'playing') kind = preferred;
    let current = ['local_media', 'bluetooth', 'airplay', 'multiroom'].includes(kind) ? kind : '';
    if (current === 'bluetooth') {
      const pathMac = String(bt.device_path || '').split('dev_')[1]?.split('/')[0].replace(/_/g, ':').toUpperCase();
      const mac = String(bt.selected_input_mac || snapshot?.input_selected || pathMac || '').toUpperCase();
      current = devices.some(d => d.mac === mac) ? 'bluetooth:' + mac : '';
    }
    select.dataset.currentSource = current;
    select.value = current;
  };
  select.addEventListener('blur', () => { if (last) window.renderVbotMediaInputs(...last); });
})();
