(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    window.gcloud_scan = function (folderName, sourceBackup, resultDivId) {
        loading('show');
        var xhr = vbotCreateXhr(90000);
        xhr.open('POST', 'includes/php_ajax/GCloud_Act.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== XMLHttpRequest.DONE) {
                return;
            }
            loading('hide');
            try {
                var response = JSON.parse(xhr.responseText);
                if (xhr.status !== 200) {
                    show_message(response.message || ('Có lỗi xảy ra: ' + xhr.status));
                    return;
                }
                var resultElement = document.getElementById(resultDivId);
                if (!resultElement) {
                    showMessagePHP('Không tìm thấy phần tử có id là: ' + resultDivId + ' để hiển thị kết quả.');
                    return;
                }
                if (!response.success) {
                    show_message(response.message);
                    return;
                }
                showMessagePHP(response.message, 3);
                var table = '<table class="table table-bordered border-primary">';
                table += '<tr><th colspan="5" class="text-primary text-center">' + escapeHtml(sourceBackup) + '</th></tr>';
                table += '<tr><th class="text-center">STT</th><th class="text-center">Tên tệp</th>'
                    + '<th class="text-center">Thời gian tạo</th><th class="text-center">Kích thước</th>'
                    + '<th class="text-center">Hành động</th></tr>';
                response.data.forEach(function (file, index) {
                    var fileId = encodeURIComponent(file.id);
                    var fileName = escapeHtml(file.name);
                    var encodedFileName = encodeURIComponent(file.name);
                    var rawShareUrl = String(file.url_share);
                    var shareUrl = /^https?:\/\//i.test(rawShareUrl) ? escapeHtml(rawShareUrl) : '#';
                    table += '<tr><td class="text-center align-middle">' + (index + 1) + '</td>';
                    table += '<td class="text-center align-middle">' + fileName + '</td>';
                    table += '<td class="text-center align-middle">' + escapeHtml(file.created_at) + '</td>';
                    table += '<td class="text-center align-middle">' + escapeHtml(file.size) + '</td>';
                    table += '<td class="text-center align-middle">';
                    table += '<form method="POST" action="" class="d-inline">'
                        + '<button type="submit" name="Restore_Backup" value="' + shareUrl + '" '
                        + 'onclick="return confirmRestore(\'Bạn có chắc chắn muốn khôi phục dữ liệu từ Google Cloud Drive?\')" '
                        + 'class="btn btn-success"><i class="bi bi-arrow-counterclockwise"></i></button></form> ';
                    table += '<a href="' + shareUrl + '" target="_blank" rel="noopener" class="btn btn-success">'
                        + '<i class="bi bi-box-arrow-up-right"></i></a> ';
                    table += '<button type="button" class="btn btn-danger webui-gcloud-delete" '
                        + 'data-file-id="' + fileId + '" data-file-name="' + encodedFileName + '" '
                        + 'data-folder="' + encodeURIComponent(folderName) + '" '
                        + 'data-source="' + encodeURIComponent(sourceBackup) + '" '
                        + 'data-result-id="' + encodeURIComponent(resultDivId) + '">'
                        + '<i class="bi bi-trash"></i></button></td></tr>';
                });
                table += '</table>';
                resultElement.innerHTML = table;
            } catch (error) {
                show_message('Lỗi xử lý dữ liệu: ' + error.message);
            }
        };
        xhr.onerror = function () {
            loading('hide');
            show_message('Lỗi kết nối Google Cloud');
        };
        xhr.send('Scan=1&Folder_Name=' + encodeURIComponent(folderName));
    };

    window.deleteFile_gcloud = function (fileId, fileName, folderName, sourceName, resultDivId) {
        if (!window.confirm("Bạn có chắc chắn muốn xóa file: '" + fileName + "' trên Google Cloud Drive không?")) {
            return;
        }
        loading('show');
        var xhr = vbotCreateXhr(90000);
        xhr.open('POST', 'includes/php_ajax/GCloud_Act.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== XMLHttpRequest.DONE) {
                return;
            }
            loading('hide');
            try {
                var response = JSON.parse(xhr.responseText);
                if (xhr.status !== 200) {
                    show_message(response.message || ('Có lỗi xảy ra: ' + xhr.status));
                    return;
                }
                if (!response.success) {
                    show_message(response.message);
                    return;
                }
                showMessagePHP(response.message, 3);
                if (document.getElementById(resultDivId)) {
                    window.gcloud_scan(folderName, sourceName, resultDivId);
                }
            } catch (error) {
                show_message('Lỗi xử lý dữ liệu: ' + error.message);
            }
        };
        xhr.onerror = function () {
            loading('hide');
            show_message('Lỗi kết nối Google Cloud');
        };
        xhr.send('Delete=1&id_file=' + encodeURIComponent(fileId));
    };

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.webui-gcloud-delete');
        if (!button) {
            return;
        }
        window.deleteFile_gcloud(
            decodeURIComponent(button.dataset.fileId),
            decodeURIComponent(button.dataset.fileName),
            decodeURIComponent(button.dataset.folder),
            decodeURIComponent(button.dataset.source),
            decodeURIComponent(button.dataset.resultId)
        );
    });
})();
