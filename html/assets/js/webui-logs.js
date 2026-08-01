(function () {
    'use strict';

    var config = window.webuiLogConfig || {};
    var checkboxMap = {
        fetchLogsCheckbox: 'logsOutput',
        fetchLogsCheckbox_Head: 'logsOutput_Head'
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatLogMessage(message) {
        var safeMessage = escapeHtml(message);
        var logStyles = [
            {keyword: '[BOT] Đang thu âm', style: 'color: rgb(255, 105, 97);'},
            {keyword: '[BOT]', style: 'color: rgb(255, 214, 10);'},
            {keyword: '[HUMAN]', style: 'color: rgb(0, 255, 0);'},
            {keyword: 'Đang chờ được đánh thức.', style: 'color: rgb(0, 255, 0);'},
            {keyword: 'dữ liệu âm thanh', style: 'color: rgb(144, 238, 144);'},
            {keyword: 'Không có giọng nói được truyền vào', style: 'color: rgb(221, 160, 221);'},
            {keyword: 'Đã được đánh thức.', style: 'color: rgb(255, 182, 193);'},
            {keyword: 'Đang phát', style: 'color: rgb(255, 165, 0);'},
            {keyword: '[Custom skills', style: 'color: rgb(64, 224, 208);'},
            {keyword: 'ERROR', style: 'color: rgb(255, 69, 58);'},
            {keyword: 'WARNING', style: 'color: rgb(255, 140, 0);'},
            {keyword: 'SUCCESS', style: 'color: rgb(50, 205, 50);'}
        ];
        var style = 'color: white;';
        for (var i = 0; i < logStyles.length; i++) {
            if (String(message).indexOf(logStyles[i].keyword) !== -1) {
                style = logStyles[i].style;
                break;
            }
        }
        return '<div style="' + style + '">' + safeMessage + '</div>';
    }

    function stopLogViewer() {
        if (window.webuiLogInterval) {
            window.clearInterval(window.webuiLogInterval);
            window.webuiLogInterval = null;
        }
    }

    function initLogViewer(checkboxId, outputId) {
        var checkbox = document.getElementById(checkboxId);
        var output = document.getElementById(outputId);
        if (!checkbox || !output || !config.apiUrl) {
            return;
        }
        stopLogViewer();

        function fetchLogs() {
            var xhr = new XMLHttpRequest();
            xhr.timeout = 10000;
            xhr.onerror = function () {
                output.innerHTML = '<span style="color:red">Không thể kết nối đến API, vui lòng kiểm tra lại</span>';
            };
            xhr.ontimeout = function () {
                output.innerHTML = '<span style="color:red">API phản hồi quá lâu</span>';
            };
            xhr.onload = function () {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (!response.success) {
                        output.innerHTML = '<span style="color:red">Lỗi: '
                            + escapeHtml(response.message) + '</span>';
                    } else if (response.data && response.data.length) {
                        output.innerHTML = response.data.map(function (item) {
                            return formatLogMessage(item.logs_message);
                        }).join('');
                    } else {
                        output.innerHTML = '<span style="color:orange">Dữ liệu logs chương trình VBot rỗng</span>';
                    }
                } catch (error) {
                    output.innerHTML = '<span style="color:red">Phản hồi không hợp lệ: '
                        + escapeHtml(xhr.responseText) + '</span>';
                }
                output.scrollTop = output.scrollHeight;
            };
            xhr.open('GET', config.apiUrl + 'logs');
            xhr.send();
        }

        fetchLogs();
        window.webuiLogInterval = window.setInterval(fetchLogs, 1000);
    }

    function handleCheckbox(checkbox) {
        var outputId = checkboxMap[checkbox.id];
        var output = document.getElementById(outputId);
        if (!output) {
            return;
        }
        if (!checkbox.checked) {
            stopLogViewer();
            output.innerHTML = '';
            return;
        }
        Object.keys(checkboxMap).forEach(function (id) {
            if (id === checkbox.id) {
                return;
            }
            var otherCheckbox = document.getElementById(id);
            var otherOutput = document.getElementById(checkboxMap[id]);
            if (otherCheckbox) {
                otherCheckbox.checked = false;
            }
            if (otherOutput) {
                otherOutput.innerHTML = '';
            }
        });
        initLogViewer(checkbox.id, outputId);
    }

    Object.keys(checkboxMap).forEach(function (id) {
        var checkbox = document.getElementById(id);
        if (!checkbox || checkbox.dataset.webuiLogsBound === '1') {
            return;
        }
        checkbox.dataset.webuiLogsBound = '1';
        checkbox.addEventListener('change', function () {
            handleCheckbox(this);
        });
        if (checkbox.checked) {
            handleCheckbox(checkbox);
        }
    });

    var closeButton = document.getElementById('Close_Logs_Head');
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            var checkbox = document.getElementById('fetchLogsCheckbox_Head');
            if (checkbox) {
                checkbox.checked = false;
            }
            stopLogViewer();
        });
    }

    window.initLogViewer = initLogViewer;
    window.formatLogMessage = formatLogMessage;
})();
