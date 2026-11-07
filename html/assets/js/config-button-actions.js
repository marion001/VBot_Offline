(function () {
  'use strict';

  function normalize(value) {
    return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
  }

  function start() {
    var selects = Array.prototype.slice.call(document.querySelectorAll('.vbot-config-action-select'));
    if (!selects.length) return;

    function refreshDeviceActions() {
      return fetch('includes/php_ajax/Config_Button_Actions.php', {method: 'GET', credentials: 'same-origin', cache: 'no-store'})
      .then(function (response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      })
      .then(function (result) {
        if (!result || result.success !== true || !result.actions || typeof result.actions !== 'object') return;
        if (window.VBotSearchableSelect) {
          selects.forEach(function (select) { window.VBotSearchableSelect.setOptions(select, result.actions); });
        }
      });
    }
    window.addEventListener('vbot:hass-cache-updated', function () {
      refreshDeviceActions().catch(function (error) { console.warn('Không cập nhật được danh sách thao tác thiết bị:', error); });
    });
    refreshDeviceActions().catch(function (error) {
      console.warn('Không tải được danh sách thao tác thiết bị:', error);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
}());
