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
            var xhr = vbotCreateXhr();
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

    var errorLogRequest = null;
    function loadVbotErrorLog() {
        var output = document.getElementById('vbotErrorLogOutput');
        var status = document.getElementById('vbotErrorLogStatus');
        var reloadButton = document.getElementById('reloadVbotErrorLogButton');
        if (!output || !status || !config.errorLogUrl) {
            return;
        }
        if (errorLogRequest) {
            errorLogRequest.abort();
        }
        var xhr = vbotCreateXhr();
        errorLogRequest = xhr;
        xhr.timeout = 15000;
        status.textContent = 'Đang tải Vbot_error.log...';
        if (reloadButton) {
            reloadButton.disabled = true;
        }
        function finish() {
            if (errorLogRequest === xhr) {
                errorLogRequest = null;
            }
            if (reloadButton) {
                reloadButton.disabled = false;
            }
        }
        xhr.onload = function () {
            try {
                var response = JSON.parse(xhr.responseText);
                if (xhr.status < 200 || xhr.status >= 300 || !response.success) {
                    throw new Error(response.message || 'HTTP ' + xhr.status);
                }
                var content = typeof response.data === 'string' ? response.data : '';
                output.textContent = content || 'File Vbot_error.log hiện không có dữ liệu.';
                status.textContent = 'Cập nhật lúc ' + new Date().toLocaleTimeString('vi-VN');
                output.scrollTop = output.scrollHeight;
            } catch (error) {
                output.textContent = 'Không thể đọc Vbot_error.log: ' + error.message;
                status.textContent = 'Tải logs thất bại';
            }
            finish();
        };
        xhr.onerror = function () {
            output.textContent = 'Không thể kết nối tới WebUI để đọc Vbot_error.log.';
            status.textContent = 'Lỗi kết nối';
            finish();
        };
        xhr.ontimeout = function () {
            output.textContent = 'Quá thời gian chờ đọc Vbot_error.log.';
            status.textContent = 'Quá thời gian';
            finish();
        };
        xhr.open('GET', config.errorLogUrl, true);
        xhr.send();
    }

    function copyVbotErrorLog() {
        var output = document.getElementById('vbotErrorLogOutput');
        var content = output ? output.textContent : '';
        if (!content) {
            show_message('Không có dữ liệu logs để sao chép');
            return;
        }
        function copied() {
            showMessagePHP('Đã sao chép nội dung Vbot_error.log', 3);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(content).then(copied).catch(function(error) {
                show_message('Không thể sao chép logs: ' + error.message);
            });
            return;
        }
        var textarea = document.createElement('textarea');
        textarea.value = content;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            copied();
        } catch (error) {
            show_message('Không thể sao chép logs: ' + error.message);
        }
        document.body.removeChild(textarea);
    }

    function clearVbotErrorLog() {
        if (!config.fileActionUrl || !config.errorLogPath) {
            show_message('Thiếu cấu hình đường dẫn Vbot_error.log');
            return;
        }
        if (!window.confirm('Bạn có chắc chắn muốn xóa toàn bộ nội dung Vbot_error.log?')) {
            return;
        }
        var button = document.getElementById('clearVbotErrorLogButton');
        if (button) {
            button.disabled = true;
        }
        var xhr = vbotCreateXhr();
        xhr.timeout = 15000;
        xhr.open('POST', config.fileActionUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.setRequestHeader('X-CSRF-Token', config.csrfToken || '');
        xhr.onload = function () {
            if (button) {
                button.disabled = false;
            }
            try {
                var response = JSON.parse(xhr.responseText);
                if (xhr.status < 200 || xhr.status >= 300 || !response.success) {
                    throw new Error(response.message || 'HTTP ' + xhr.status);
                }
                showMessagePHP('Đã xóa nội dung Vbot_error.log', 3);
                loadVbotErrorLog();
            } catch (error) {
                show_message('Không thể xóa Vbot_error.log: ' + error.message);
            }
        };
        xhr.onerror = xhr.ontimeout = function () {
            if (button) {
                button.disabled = false;
            }
            show_message('Không thể kết nối để xóa Vbot_error.log');
        };
        xhr.send('empty_the_file=1&file_path=' + encodeURIComponent(config.errorLogPath));
    }

    function downloadVbotErrorLog() {
        if (!config.fileDownloadUrl || !config.errorLogPath) {
            show_message('Thiếu cấu hình tải xuống Vbot_error.log');
            return;
        }
        var link = document.createElement('a');
        link.href = config.fileDownloadUrl + '?file=' + encodeURIComponent(config.errorLogPath);
        link.download = 'Vbot_error.log';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
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
    window.loadVbotErrorLog = loadVbotErrorLog;
    window.copyVbotErrorLog = copyVbotErrorLog;
    window.clearVbotErrorLog = clearVbotErrorLog;
    window.downloadVbotErrorLog = downloadVbotErrorLog;
})();
