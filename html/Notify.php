<?php
#Code By: Vũ Tuyển
#Kiểm tra chmod chạy bất đồng bộ sau khi WebUI tải xong để không chặn render trang.
?>
<a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Thông báo">
  <i class="bi bi-bell text-success"></i>
  <span id="number_notification" class="badge bg-primary badge-number"></span>
</a>
<ul id="notification" class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" style="max-height:400px; overflow-y:auto; width:auto; height:auto;">
  <li class="dropdown-header"><font id="number_notification_1" color="red">Bạn có <b>0</b> thông báo mới.</font></li>
</ul>
<script>
(function () {
  'use strict';

  function incrementNotificationCount() {
    const badge = document.getElementById('number_notification');
    const text = document.getElementById('number_notification_1');
    const next = (parseInt(badge?.textContent || '0', 10) || 0) + 1;
    if (badge) badge.textContent = String(next);
    if (text) text.innerHTML = 'Bạn có <b>' + next + '</b> thông báo mới.';
  }

  function renderChmodNotification(issues) {
    const notificationList = document.getElementById('notification');
    if (!notificationList || !Array.isArray(issues) || issues.length === 0
        || document.getElementById('chmod_permission_notification')) return;

    const divider = document.createElement('li');
    divider.dataset.chmodNotification = 'true';
    divider.innerHTML = '<hr class="dropdown-divider">';
    const item = document.createElement('li');
    item.id = 'chmod_permission_notification';
    item.className = 'notification-item align-items-start';
    item.style.padding = '10px';
    const icon = document.createElement('i');
    icon.className = 'bi bi-exclamation-circle text-warning';
    const content = document.createElement('div');
    content.className = 'overflow-hidden';
    content.style.width = '230px';
    content.style.maxWidth = '230px';
    const title = document.createElement('h4');
    title.className = 'text-danger';
    title.textContent = 'Cấp Quyền Chmod';
    const summaryText = document.createElement('p');
    summaryText.className = 'text-primary mb-1';
    summaryText.append('Có: ');
    const count = document.createElement('b');
    count.textContent = String(issues.length);
    summaryText.append(count, ' file, thư mục chưa có quyền 0777');
    const details = document.createElement('details');
    details.className = 'mb-2 small';
    const summary = document.createElement('summary');
    summary.className = 'text-info';
    summary.style.cursor = 'pointer';
    summary.textContent = 'Xem chi tiết quyền';
    const list = document.createElement('ul');
    list.className = 'small mt-2 ps-3 mb-2 overflow-auto';
    list.style.maxHeight = '120px';
    list.style.wordBreak = 'break-word';
    issues.forEach(function (issue) {
      const row = document.createElement('li');
      row.className = 'mb-1';
      const path = document.createElement('code');
      path.className = 'text-break';
      path.textContent = String(issue.path || '');
      const mode = document.createElement('span');
      mode.className = 'badge bg-danger ms-1';
      mode.textContent = String(issue.mode || 'không xác định');
      row.append(path, mode);
      list.appendChild(row);
    });
    details.append(summary, list);
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm btn-warning py-0 px-2';
    button.textContent = 'Cấp quyền 0777';
    button.addEventListener('click', function (event) {
      event.stopPropagation();
      if (typeof command_php === 'function') command_php('chmod_vbot', true);
    });
    content.append(title, summaryText, details, button);
    item.append(icon, content);
    notificationList.append(divider, item);
    incrementNotificationCount();
  }

  function scanChmodPermissionsInBackground() {
    if (window.vbotChmodPermissionScanStarted) return;
    window.vbotChmodPermissionScanStarted = true;
    const url = 'includes/php_ajax/Check_Connection.php?check_chmod_permissions=true&_=' + Date.now();
    const options = {method: 'GET', credentials: 'same-origin', headers: {'Accept': 'application/json'}};
    const request = typeof vbotFetchWithTimeout === 'function'
      ? vbotFetchWithTimeout(url, options, 120000)
      : fetch(url, options);
    request.then(function (response) {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.json();
    }).then(function (response) {
      if (response && response.success) renderChmodNotification(response.issues || []);
    }).catch(function (error) {
      console.warn('Không thể kiểm tra quyền chmod nền:', error);
    });
  }

  window.addEventListener('load', function () {
    window.setTimeout(scanChmodPermissionsInBackground, 1500);
  }, {once: true});
})();
</script>
