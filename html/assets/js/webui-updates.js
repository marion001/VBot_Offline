(function () {
    'use strict';

    var config = window.webuiUpdateConfig || {};

    function requestJson(url) {
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {'Accept': 'application/json'}
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    }

    function readLocalVersion(url) {
        return requestJson(url).then(function (response) {
            return response && response.data ? response.data : null;
        });
    }

    function readGithubVersion(url) {
        return requestJson(url).then(function (response) {
            if (!response || !response.content) {
                return null;
            }
            var decoded = window.atob(String(response.content).replace(/\s/g, ''));
            return JSON.parse(decoded);
        });
    }

    function addUpdateNotification(item, remoteVersion) {
        var notificationList = document.getElementById('notification');
        if (!notificationList) {
            return;
        }
        var listItem = document.createElement('li');
        listItem.className = 'notification-item';

        var iconLink = document.createElement('a');
        iconLink.href = item.target;
        iconLink.innerHTML = '<i class="bi bi-box-arrow-in-up text-success"></i>';

        var content = document.createElement('div');
        var heading = document.createElement('h4');
        heading.className = 'text-success';
        heading.textContent = item.title;
        var message = document.createElement('p');
        message.className = 'text-primary';
        message.textContent = 'Có phiên bản mới: ' + remoteVersion.releaseDate;
        var checkLink = document.createElement('a');
        checkLink.href = item.target;
        checkLink.className = 'text-danger';
        checkLink.textContent = 'Kiểm tra';

        content.appendChild(heading);
        content.appendChild(message);
        content.appendChild(checkLink);
        listItem.appendChild(iconLink);
        listItem.appendChild(content);
        notificationList.appendChild(listItem);

        var count = document.getElementById('number_notification');
        var countText = document.getElementById('number_notification_1');
        if (count) {
            count.textContent = String((parseInt(count.textContent, 10) || 0) + 1);
        }
        if (countText) {
            var current = parseInt(countText.textContent.replace(/[^0-9]/g, ''), 10) || 0;
            countText.innerHTML = 'Bạn có <b>' + (current + 1) + '</b> thông báo mới';
        }
    }

    function checkOne(item) {
        return Promise.all([
            readLocalVersion(item.localUrl),
            readGithubVersion(item.remoteUrl)
        ]).then(function (versions) {
            var localVersion = versions[0];
            var remoteVersion = versions[1];
            if (
                localVersion
                && remoteVersion
                && localVersion.releaseDate
                && remoteVersion.releaseDate
                && localVersion.releaseDate !== remoteVersion.releaseDate
            ) {
                addUpdateNotification(item, remoteVersion);
            }
        }).catch(function (error) {
            if (typeof showMessagePHP === 'function') {
                showMessagePHP('Không thể kiểm tra cập nhật ' + item.label + ': ' + error.message, 3);
            }
        });
    }

    function checkAllUpdates() {
        if (!Array.isArray(config.items)) {
            return Promise.resolve();
        }
        return Promise.all(config.items.map(checkOne));
    }

    window.checkAllWebuiUpdates = checkAllUpdates;
    checkAllUpdates();
})();
