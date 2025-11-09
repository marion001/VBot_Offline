//Kiểm tra và thông báo lỗi nếu Submit có giá trị input trống
function validateFormVBot() {
    const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    let firstEmptyInput = null;
    let emptyFields = [];
    requiredInputs.forEach(input => {
        input.classList.remove('empty-field');
        if (!input.value.trim()) {
            input.classList.add('empty-field');
            if (!firstEmptyInput) {
                firstEmptyInput = input;
            }
            const accordionContent = input.closest('.accordion-collapse');
            if (accordionContent) {
                const accordionId = accordionContent.id;
                const accordion = new bootstrap.Collapse(accordionContent, {
                    show: true
                });
                const accordionHeader = document.querySelector('[data-bs-target="#' + accordionId + '"]');
                const sectionName = accordionHeader ? accordionHeader.textContent.trim() : '';
                let fieldName = input.getAttribute('placeholder') || 
                              input.getAttribute('name') ||
                              input.getAttribute('id') ||
                              'Trường dữ liệu';
                              
                if (sectionName) {
                    fieldName = '<b class="text-success">'+sectionName+'</b> <b class="text-primary">'+fieldName+'</b>';
                }
                emptyFields.push(fieldName);
            } else {
                let fieldName = input.getAttribute('placeholder') || 
                              input.getAttribute('name') ||
                              input.getAttribute('id') ||
                              'Trường dữ liệu';
                const cardHeader = input.closest('.card')?.querySelector('.card-header');
                if (cardHeader) {
                    fieldName = '<b class="text-success">'+cardHeader.textContent.trim()+' </b> <b class="text-primary">'+fieldName+'</b>';
                }
                emptyFields.push(fieldName);
            }
        }
    });
    if (firstEmptyInput) {
        const message = '<br/><center class="text-danger"><b>Vui lòng điền đầy đủ thông tin cho các trường giá trị</b></center><hr><b><center>Các Danh Mục Sau Còn Thiếu Tham Số</center></b><br/>- ' + emptyFields.join('<br>- ');
        show_message(message);
        const accordionContent = firstEmptyInput.closest('.accordion-collapse');
        if (accordionContent) {
            new bootstrap.Collapse(accordionContent, {
                show: true
            });
            setTimeout(() => {
                firstEmptyInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstEmptyInput.focus();
            }, 350);
        } else {
            firstEmptyInput.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            setTimeout(() => firstEmptyInput.focus(), 500);
        }
        return false;
    }
    return true;
}

//Ẩn hiển nguồn cài đặt hotword khi được chọn
function selectHotwordWakeup() {
    var selectedValue = document.getElementById("hotword_select_wakeup").value;
    document.getElementById("select_show_picovoice_porcupine").style.display = selectedValue === "porcupine" ? "block" : "none";
    document.getElementById("select_show_snowboy").style.display = selectedValue === "snowboy" ? "block" : "none";
}

// Hàm để cuộn lên đầu trang
function scrollToTop(event) {
    event.preventDefault();
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Hàm để cuộn xuống cuối trang
function scrollToBottom(event) {
    event.preventDefault();
    window.scrollTo({
        top: document.body.scrollHeight,
        behavior: 'smooth'
    });
}

//ẩn hiện Cấu hình STT: khi lựa chọn radio Lựa chọn STT (Speak To Text):
document.querySelectorAll('input[name="stt_select"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const div_select_stt_ggcloud_html = document.getElementById('select_stt_ggcloud_html');
        const div_select_stt_default_html = document.getElementById('select_stt_default_html');
        const div_select_stt_ggcloud_v2_html = document.getElementById('select_stt_ggcloud_v2_html');
        if (document.getElementById('stt_ggcloud').checked) {
            div_select_stt_ggcloud_html.style.display = 'block'; // Hiển thị div
        } else {
            div_select_stt_ggcloud_html.style.display = 'none'; // Ẩn div
        }
        if (document.getElementById('stt_default').checked) {
            div_select_stt_default_html.style.display = 'block';
        } else {
            div_select_stt_default_html.style.display = 'none';
        }
        if (document.getElementById('stt_ggcloud_v2').checked) {
            div_select_stt_ggcloud_v2_html.style.display = 'block';
        } else {
            div_select_stt_ggcloud_v2_html.style.display = 'none';
        }
    });
});

//ẩn hiện cấu hình tts khi được lựa chọn
document.querySelectorAll('input[name="tts_select"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const div_select_tts_ggcloud_html = document.getElementById('select_tts_ggcloud_html');
        const div_select_tts_default_html = document.getElementById('select_tts_default_html');
        const div_select_tts_edge_html = document.getElementById('select_tts_edge_html');
        const div_select_tts_ggcloud_key = document.getElementById('select_tts_ggcloud_key');
        const div_select_tts_zalo_html = document.getElementById('select_tts_zalo_html');
        const div_select_tts_viettel_html = document.getElementById('select_tts_viettel_html');
        if (document.getElementById('tts_ggcloud').checked) {
            load_list_GoogleVoices_tts('tts_ggcloud');
            div_select_tts_ggcloud_html.style.display = 'block'; // Hiển thị div
        } else {
            div_select_tts_ggcloud_html.style.display = 'none'; // Ẩn div
        }
        if (document.getElementById('tts_default').checked) {
            div_select_tts_default_html.style.display = 'block';
            document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_default_html.style.display = 'none';
        }
        if (document.getElementById('tts_zalo').checked) {
            getBacklistData('backlist->tts_zalo', 'tts_zalo_backlist_content');
            div_select_tts_zalo_html.style.display = 'block';
            document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_zalo_html.style.display = 'none';
        }
        if (document.getElementById('tts_viettel').checked) {
            getBacklistData('backlist->tts_viettel', 'tts_viettel_backlist_content');
            div_select_tts_viettel_html.style.display = 'block';
            document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_viettel_html.style.display = 'none';
        }
        if (document.getElementById('tts_edge').checked) {
            div_select_tts_edge_html.style.display = 'block';
            document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_edge_html.style.display = 'none';
        }
        if (document.getElementById('tts_ggcloud_key').checked) {
            load_list_GoogleVoices_tts('tts_ggcloud_key');
            div_select_tts_ggcloud_key.style.display = 'block';
            document.getElementById("tts_dev_customize_active").checked = false;
        } else {
            div_select_tts_ggcloud_key.style.display = 'none';
        }
        if (document.getElementById('tts_dev_customize').checked) {
            document.getElementById("tts_dev_customize_active").checked = true;
        }
    });
});

//Hiển thị list hotword Snowboy
function displayResults_Hotword_Snowboy(data) {
    const resultsDiv_snowboy = document.getElementById('results_body_hotword_snowboy');
    const resultsDiv_snowboy1 = document.getElementById('results_body_hotword_snowboy1');
    // Kiểm tra nếu không tìm thấy phần tử để tránh lỗi
    if (!resultsDiv_snowboy || !resultsDiv_snowboy1) {
        console.error('Không tìm thấy phần tử HTML cần thiết.');
        return;
    }
    // Xóa nội dung cũ
    resultsDiv_snowboy.innerHTML = '';
    resultsDiv_snowboy1.innerHTML = '';
    // Tạo biến selectHtml để tránh lỗi biến chưa được khai báo
    let selectHtml = '';
    if (Array.isArray(data.config) && data.config.length > 0) {
        let tableContent = '';
        data.config.forEach(function (item, index) {
            tableContent +=
                '<tr>' +
                '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                '<div class="form-switch">' +
                '<input class="form-check-input" type="checkbox" name="snowboy_active_' + index + '" ' + (item.active ? 'checked' : '') + '>' +
                '</div>' +
                '</td>' +
                '<td>' +
                '<input readonly class="form-control" type="text" name="snowboy_file_name_' + index + '" value="' + item.file_name + '">' +
                '</td>' +
                '<td>' +
                '<input class="form-control" type="number" name="snowboy_sensitive_' + index + '" value="' + item.sensitive + '" step="0.1" min="0.1" max="1.0">' +
                '</td>' +
                '<td style="text-align: center; vertical-align: middle;">' +
                '<center>' +
                '<button type="button" class="btn btn-danger" title="Xóa file: ' + item.file_name + '" onclick="deleteFile(\'' + data.path_hotword + item.file_name + '\', \'' + data.lang + '\')">' +
                '<i class="bi bi-trash"></i>' +
                '</button> ' +
                '<button type="button" class="btn btn-success" title="Tải xuống file: ' + item.file_name + '" onclick="downloadFile(\'' + data.path_hotword + item.file_name + '\')">' +
                '<i class="bi bi-download"></i>' +
                '</button>' +
                '</center>' +
                '</td>' +
                '</tr>';
        });
        // Thêm nút nút lưu vào thẻ <tr>
        tableContent +=
            '<tr>' +
            '<td colspan="5" style="text-align: center;">' +
            '<label for="upload_files_hotword_snowboy"><font color=blue>Tải lên file hotword <b>.umdl</b> hoặc <b>.pmdl</b></font></label>' +
            '<div class="input-group">' +
            '<input class="form-control" type="file" name="upload_files_hotword_snowboy[]" id="upload_files_hotword_snowboy" accept=".pmdl,.umdl" multiple>' +
            ' <button class="btn btn-primary"  type="button" onclick="uploadFilesHotwordSnowboy()">Tải Lên</button>' +
            '</div>' +
            '<br/><button type="submit" name="save_hotword_snowboy" class="btn btn-success rounded-pill" title="Lưu cài đặt hotword">Lưu Cài Đặt Hotword</button>' +
            '</td>' +
            '</tr>';
        resultsDiv_snowboy1.innerHTML =
            selectHtml +
            '<tr>' +
            '<th><center>STT</center></th>' +
            '<th><center>Kích Hoạt</center></th>' +
            '<th><center>File Hotword</center></th>' +
            '<th><center>Độ Nhạy</center></th>' +
            '<th><center>Hành Động</center></th>' +
            '</tr>';
        resultsDiv_snowboy.innerHTML = tableContent;
    } else {
        resultsDiv_snowboy.innerHTML = '<tr><td colspan="5"><center>Không có dữ liệu hotword nào.</center></td></tr>';
    }
}

//Hiển thị list hotword Picovoice/Procupine
function displayResults_Hotword_dataa(data) {
    const resultsDiv = document.getElementById('results_body_hotword');
    const resultsDiv1 = document.getElementById('results_body_hotword1');
    resultsDiv.innerHTML = '';
    resultsDiv1.innerHTML = '';
    //Hiển thị ngôn ngữ đang được truy vấn
    let reponse_lang = data.lang === "vi" ? "Tiếng Việt" : "Tiếng Anh";
    const langDiv = document.getElementById('language_hotwordd');
    langDiv.innerHTML = '<strong>- Ngôn ngữ: </strong> <font color="red" id="data_lang_shows" value=' + data.lang + '>' + reponse_lang + '</font><br/>- File Thư Viện Đang Dùng: <font color="red">' + data.config_lib_pv_to_lang + '</font>';
    const fileList = data.files_lib_pv;
    //Tạo thẻ <select> một lần với tất cả các tùy chọn
    var selectHtml = '<tr><td colspan="4"><div class="form-floating mb-3"><select required class="form-select" id="select_file_lib_pv" name="select_file_lib_pv">';
    selectHtml += '<option value="">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</option>';
    fileList.forEach(function (file) {
        var isSelected_lib = (file === data.config_lib_pv_to_lang) ? ' selected' : '';
        selectHtml += '<option value="' + file + '"' + isSelected_lib + '>' + file + '</option>';
    });
    selectHtml += '</select><div class="invalid-feedback">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</div> <label for="select_file_lib_pv">Chọn file thư viện Hotword: ' + reponse_lang + '</label></div></td>';
    selectHtml += '<td style="text-align: center; vertical-align: middle;"><center><button type="button" class="btn btn-danger" id="deleteFilePV" title="Xóa file: "><i class="bi bi-trash"></i></button>  <button type="button" class="btn btn-success" id="downloadFilePV" title="Tải xuống file: "><i class="bi bi-download"></i></button> </center></td></tr>';
    if (Array.isArray(data.config) && data.config.length > 0) {
        let i_up = 0;
        let tableContent = '';
        data.config.forEach((item, index) => {
            i_up++;
            tableContent +=
                '<tr>' +
                '<td style="text-align: center; vertical-align: middle;">' + i_up + '</td>' +
                '<td style="text-align: center; vertical-align: middle;"><div  class="form-switch"><input class="form-check-input" type="checkbox" name="active_' + index + '" ' + (item.active ? 'checked' : '') + '></div></td>' +
                '<td><input readonly class="form-control" type="text" name="file_name_' + index + '" value="' + item.file_name + '"></td>' +
                '<td><input class="form-control" type="number" name="sensitive_' + index + '" value="' + item.sensitive + '" step="0.1" min="0.1" max="1.0"></td>' +
                '<td style="text-align: center; vertical-align: middle;"><center><button type="button" class="btn btn-danger" title="Xóa file: ' + item.file_name + '" onclick="deleteFile(\'' + data.path_ppn + item.file_name + '\', \'' + data.lang + '\')"><i class="bi bi-trash"></i></button> ' +
                '  <button type="button" class="btn btn-success" title="Tải xuống file: ' + item.file_name + '"  onclick="downloadFile(\'' + data.path_ppn + item.file_name + '\')"><i class="bi bi-download"></i></button></center></td>' +
                '</tr>';
        });
        // Thêm nút tải lên và nút lưu vào thẻ <tr>
        tableContent +=
            '<tr>' +
            '<td colspan="5" style="text-align: center;">' +
            '<input type="hidden" name="lang_hotword_get" id="lang_hotword_get" value="' + data.lang + '">' +
            '<label for="upload_files_ppn_pv"><font color=blue>Tải lên file hotword .ppn hoặc file thư viện .pv cho <b>' + reponse_lang + '</b></font></label>' +
            '<div class="input-group">' +
            '<input class="form-control" type="file" name="upload_files_ppn_pv[]" id="upload_files_ppn_pv" accept=".ppn,.pv" multiple>' +
            ' <button class="btn btn-primary"  type="button" onclick="uploadFilesHotwordPPNandPV()">Tải Lên</button>' +
            '</div>' +
            '<br/><button type="submit" name="save_hotword_theo_lang" class="btn btn-success rounded-pill" title="Lưu cài đặt hotword">Lưu Cài Đặt Hotword</button>' +
            '</td>' +
            '</tr>';
        resultsDiv1.innerHTML +=
            selectHtml +
            '<tr><th><center>STT</center></th>' +
            '<th><center>Kích Hoạt</center></th>' +
            '<th><center>File Hotword</center></th>' +
            '<th><center>Độ Nhạy</center></th>' +
            '<th><center>Hành Động</center></th></tr>';
        resultsDiv.innerHTML = tableContent;
    }
    //Nếu không có dữ liệu hotword trong COnfig.json là rỗng: []
    else {
        let i_up = 0;
        let tableContent = '';
        var selectHtml = '<tr><td colspan="4"><div class="form-floating mb-3"><select required class="form-select" id="select_file_lib_pv" name="select_file_lib_pv">';
        selectHtml += '<option value="">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</option>';
        fileList.forEach(function (file) {
            var isSelected_lib = (file === data.config_lib_pv_to_lang) ? ' selected' : '';
            selectHtml += '<option value="' + file + '"' + isSelected_lib + '>' + file + '</option>';
        });
        selectHtml += '</select><div class="invalid-feedback">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</div> <label for="select_file_lib_pv">Chọn file thư viện Hotword: ' + reponse_lang + '</label></div></td>';
        selectHtml += '<td style="text-align: center; vertical-align: middle;"><center><button type="button" class="btn btn-danger" id="deleteFilePV" title="Xóa file: "><i class="bi bi-trash"></i></button>  <button type="button" class="btn btn-success" id="downloadFilePV" title="Tải xuống file: "><i class="bi bi-download"></i></button> </center></td></tr>';
        resultsDiv1.innerHTML = selectHtml;
        tableContent += '<tr><td colspan="5" class="text-danger"><center>Không có dữ liệu Hotword nào được cấu hình với ngôn ngữ ' + reponse_lang + '</center></td></tr>';
        tableContent +=
            '<tr>' +
            '<td colspan="5" style="text-align: center;">' +
            '<input type="hidden" name="lang_hotword_get" id="lang_hotword_get" value="' + data.lang + '">' +
            '<label for="upload_files"><font color=blue>Tải lên file hotword .ppn hoặc file thư viện .pv cho <b>' + reponse_lang + '</b></font></label>' +
            '<div class="input-group">' +
            '<input class="form-control" type="file" name="upload_files_ppn_pv[]" id="upload_files_ppn_pv" accept=".ppn,.pv" multiple>' +
            ' <button class="btn btn-primary"  type="button" onclick="uploadFilesHotwordPPNandPV()">Tải Lên</button>' +
            '</div>' +
            '<br/><button type="submit" name="save_hotword_theo_lang" class="btn btn-success rounded-pill" title="Lưu cài đặt hotword">Lưu Cài Đặt Hotword</button>' +
            '</td>' +
            '</tr>';
        resultsDiv.innerHTML = tableContent;
    }
    //Lấy giá trị mặc định của thẻ select khi tải trang
    var selectedValuee = document.getElementById('select_file_lib_pv').value;
    var deleteIconn = document.getElementById('deleteFilePV');
    var downloadIcon = document.getElementById('downloadFilePV');
    //Thiết lập onclick xóa file trong thẻ select với giá trị mặc định
    deleteIconn.onclick = function () {
        if (selectedValuee) {
            deleteFile(data.path_pv + selectedValuee);
            // Tải lại dữ liệu hotword ở Config.json
            loadConfigHotword(data.lang);
        } else {
            show_message('Cần chọn file thư viện .pv trước khi xóa');
        }
    };
    //Thiết lập onclick tải xuống file
    downloadIcon.onclick = function () {
        if (selectedValuee) {
            downloadFile(data.path_pv + selectedValuee);
        } else {
            show_message('Cần chọn file thư viện .pv trước khi tải xuống');
        }
    };
    //Lắng nghe sự kiện thay đổi trên thẻ select pv để xóa file
    document.getElementById('select_file_lib_pv').addEventListener('change', function () {
        var selectedValue = this.value;
        var deleteIcon = document.getElementById('deleteFilePV');
        var downloadIcon = document.getElementById('downloadFilePV');
        deleteIcon.onclick = function () {
            if (selectedValue) {
                deleteFile(data.path_pv + selectedValue);
                //Tải lại dữ liệu hotword ở Config.json
                loadConfigHotword(data.lang);
            } else {
                show_message('Cần chọn file thư viện .pv trước khi xóa');
            }
        };
        downloadIcon.onclick = function () {
            if (selectedValue) {
                downloadFile(data.path_pv + selectedValue);
            } else {
                show_message('Cần chọn file thư viện .pv trước khi tải xuống');
            }
        };
        //Cập nhật title của icon
        deleteIcon.title = 'Xóa file: ' + selectedValue;
        downloadIcon.title = 'Tải xuống file: ' + selectedValue;
    });
}

//Xóa thẻ input đài báo radio
function delete_Dai_bao_Radio(index_id, name_dai_radio) {
    if (name_dai_radio !== null) {
        if (!confirm('Bạn có chắc chắn muốn xóa đài "' + name_dai_radio + '" này không?')) {
            return;
        }
    }
    var row = document.getElementById('radio-row-' + index_id);
    if (row) {
        row.remove();
    }
}

//Xóa thẻ input báo, tin tức
function delete_NewsPaper(index_id, name_newspaper) {
    if (name_newspaper !== null) {
        if (!confirm('Bạn có chắc chắn muốn xóa kênh báo: "' + name_newspaper + '" này không?')) {
            return;
        }
    }
    var row = document.getElementById('newspaper-row-' + index_id);
    if (row) {
        row.remove();
    }
}

//Kiểm tra key dify
function test_key_difyAI() {
    loading('show');
    var dify_ai_key = document.getElementById('dify_ai_key').value;
    const url = 'https://api.dify.ai/v1/chat-messages';
    const headers = {
        'Authorization': 'Bearer ' + dify_ai_key,
        'Content-Type': 'application/json',
    };
    const data = {
        "inputs": {},
        "query": "Chào bạn",
        "response_mode": "blocking",
        "user": "VBot_Assistant_TestKey",
    };
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    Object.keys(headers).forEach(key => {
        xhr.setRequestHeader(key, headers[key]);
    });
    xhr.onload = function () {
        try {
            if (xhr.status === 200) {
                try {
                    const responseData = JSON.parse(xhr.responseText);
                    const answerDifyAI = responseData.answer || null;
                    if (answerDifyAI) {
                        loading('hide');
                        show_message("<font color='green'><center>Kiểm tra thành công</center></font><br/>-Dữ liệu trả về: " + answerDifyAI);
                    } else {
                        loading('hide');
                        show_message("Không có dữ liệu phản hồi từ Dify AI");
                    }
                } catch (e) {
                    loading('hide');
                    show_message("[Dify AI] Lỗi phân tích dữ liệu JSON: " + e);
                }
            } else {
                loading('hide');
                show_message("[Dify AI] Lỗi HTTP (" + xhr.status + "): " + xhr.statusText);
            }
        } catch (e) {
            loading('hide');
            show_message("[Dify AI] Lỗi xử lý phản hồi: " + e);
        }
    };
    xhr.onerror = function () {
        loading('hide');
        show_message("[Dify AI] Lỗi kết nối HTTP, vui lòng kiểm tra lại kết nối mạng.");
    };
    try {
        xhr.send(JSON.stringify(data));
    } catch (e) {
        loading('hide');
        show_message("[Dify AI] Lỗi gửi yêu cầu HTTP: " + e);
    }
}

//Test key ChatGPT
function test_key_ChatGPT(text) {
    loading("show");
    var apiKey = document.getElementById('chat_gpt_key').value;
    var url_API = document.getElementById('chat_gpt_url_api').value;
    //const url_API = "https://api.openai.com/v1/chat/completions";
    const xhr = new XMLHttpRequest();
    xhr.open("POST", url_API, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Authorization", 'Bearer ' + apiKey);
    const body = JSON.stringify({
        model: "gpt-3.5-turbo",
        messages: [{
            role: "system",
            content: "Bạn là một trợ lý thông minh"
        }, {
            role: "user",
            content: text
        }]
    });
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                loading("hide");
                const response = JSON.parse(xhr.responseText);
                const reply = response.choices[0].message.content;
                show_message('<center>Kiểm Tra API KEY Thành Công</center><br/>Phản hồi: <font color=green>' + reply + '</font>');
            } else {
                loading("hide");
                show_message('Lỗi xảy ra:<br/><font color=red>' + xhr.responseText + '</font>');
            }
        }
    };
    xhr.send(body);
}

//Tets, kiểm tra Key Gemini
function test_key_Gemini(text) {
    loading("show");
    var apiKey = document.getElementById('google_gemini_key').value;
    var gemini_models_name = document.getElementById('gemini_models_name').value;
    var apiVersion = document.getElementById('gemini_api_version').value;
    if (!apiKey || !gemini_models_name || !apiVersion) {
        loading("hide");
        show_message('<b class="text-danger">Thiếu tham số cấu hình để kiểm tra Gemini: API Key, Model name hoặc API version</b>');
        return;
    }
    var url = 'https://generativelanguage.googleapis.com/' + apiVersion + '/models/' + gemini_models_name + ':generateContent?key=' + apiKey;
    var payload = {
        contents: [{
            parts: [{
                text: text
            }]
        }]
    };
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            loading("hide");
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                var candidates = response.candidates;
                if (candidates.length > 0) {
                    var contentText = candidates[0].content.parts[0].text;
                    show_message('<center class="text-success"><b>Kiểm Tra API KEY Thành Công</b></center><br/>- API Key: <b>' + apiKey + '</b><br/>- Mô Hình: <b>' + gemini_models_name + '</b><br/>- Phiên Bản API: <b>' + apiVersion + '</b><hr/>Phản hồi: <font color=green>' + contentText + '</font>');
                }
            } else {
                var response = JSON.parse(xhr.responseText);
                if (response.error) {
                    show_message('Lỗi xảy ra: <font color=red>' + response.error.message + '</font>');
                } else {
                    show_message('Có Lỗi Xảy Ra: ' + xhr.status);
                }
            }
        }
    };
    xhr.send(JSON.stringify(payload));
}

//tách lấy tên file và đuôi từ đường dẫn path
function getFileNameFromPath(filePath) {
    // Tách đường dẫn bằng dấu '/'
    var parts = filePath.split('/');
    // Lấy phần cuối cùng của mảng, đó là tên tệp với phần mở rộng
    var fileNameWithExtension = parts.pop();
    return fileNameWithExtension;
}

//Chọn Mic để đẩy vào value của thẻ input Mic
function selectDevice_MIC(id) {
    var micInput = document.getElementById('mic_id');
    if (micInput) {
        micInput.value = id;
        showMessagePHP('Đã chọn Mic có id là: ' + id, 5);
    } else {
        show_message('Không tìm thấy thẻ input với id "mic_id".', 5);
    }
}

//Chọn Speaker để đẩy vào value của thẻ Tên thiết bị (alsamixer):
function selectDevice_Alsamixer(name) {
    var alsamixerInput = document.getElementById('alsamixer_name');
    if (alsamixerInput) {
        alsamixerInput.value = name;
        showMessagePHP('Đã chọn thiết bị trong alsamixer có tên là là: ' + name, 3);
    } else {
        show_message('Không tìm thấy thẻ input với id "alsamixer_name".', 3);
    }
}

//Play nghe thử âm thanh tts mẫu của google CLOUD
function play_tts_sample_gcloud(id_html) {
    loading('show');
    const selectElement = document.getElementById(id_html);
    if (selectElement) {
        if (selectElement.value) {
            playAudio('https://cloud.google.com/static/text-to-speech/docs/audio/' + selectElement.value + '.wav');
            loading('hide');
        } else {
            loading('hide');
            show_message('Cần chọn 1 giọng đọc để nghe thử');
        }
    } else {
        loading('hide');
        showMessagePHP('Không tìm thấy dữ liệu thẻ select với id:' + id_html, 3);
    }

}

//Cập nhật bảng mã màu vào thẻ input
//Thiết lập giá trị ban đầu cho các thẻ input khi tải trang
window.onload = function () {
    //Thiết lập màu cho thẻ đầu tiên
    setColorPickerValue('color_led_think', 'led_think');
    //Thiết lập màu cho thẻ thứ hai
    setColorPickerValue('color_led_mutex', 'led_mute');
};

//Hàm thiết lập màu cho các thẻ colorPicker dựa trên giá trị colorCodeInput
function setColorPickerValue(colorPickerId, colorCodeInputId) {
    const initialColor = document.getElementById(colorCodeInputId).value;
    document.getElementById(colorPickerId).value = '#' + initialColor;
}

//Hàm cập nhật mã màu vào thẻ input
function updateColorCode_input(colorPickerId, colorCodeInputId) {
    //Lấy mã màu từ input color và bỏ dấu '#' ở đầu
    const selectedColor = document.getElementById(colorPickerId).value.substring(1);
    //Cập nhật giá trị của thẻ input với mã màu đã chọn (không có '#')
    document.getElementById(colorCodeInputId).value = selectedColor;
}

//Kiểm tra đăng nhập
function check_info_login_olli() {
    loading('show');
    var rawUsername = document.getElementById("olli_assistant_username").value.trim();
    var password = document.getElementById("olli_assistant_password").value;
    var isEmail = /^[^@]+@[^@]+\.[^@]+$/.test(rawUsername);
    var username = rawUsername;
    if (!isEmail) {
        if (/^\+84\d{9}$/.test(username)) {
            //Đúng định dạng
        } else if (/^0\d{9}$/.test(username)) {
            username = '+84' + username.substring(1);
        } else if (/^\d{9}$/.test(username)) {
            username = '+84' + username;
        } else {
            show_message("Tài khoản đăng nhập không hợp lệ");
            loading('hide');
            return;
        }
    }
    var url = isEmail ? atob('aHR0cHM6Ly91c2Vycy5pdmlldC5jb20vdjEvYXV0aC9sb2dpbg==') : atob('aHR0cHM6Ly91c2Vycy5pdmlldC5jb20vdjEvYXV0aC9vdHAvbG9naW4=');
    var data = isEmail ? { email: username, password: password } : { phone_number: username, password: password };
    var xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Accept", "*/*");
    xhr.setRequestHeader("Accept-Language", "vi-VN;q=1.0, en-VN;q=0.9");
    xhr.setRequestHeader("Language-Code", "vi");
    xhr.setRequestHeader("Timezone", "Asia/Ho_Chi_Minh");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var responseData = JSON.parse(xhr.responseText);
                    if (responseData.code === 200) {
                        loading('hide');
                        show_message("<center class='text-success'>Xác thực Thành Công</center><br/>- " + responseData.data.tts_voice.sample_text);
                    } else {
                        loading('hide');
                        show_message("Xác thực thất bại: " + responseData + "<br/>Kiểm tra lại tài khoản hoặc mật khẩu");
                    }
                } catch (e) {
                    loading('hide');
                    show_message("Lỗi xử lý phản hồi json từ server:" + e);
                }
            } else {
                loading('hide');
                show_message("Lỗi kết nối: " + xhr.status + "<br/>Kiểm tra lại tài khoản hoặc mật khẩu");
            }
        }
    };
    xhr.send(JSON.stringify(data));
}

//Kiểm tra kết nối Socket Server Streaming
function connectWebSocketAndSendID() {
    const url = document.getElementById("url_server_streaming_audio_socket").value;
    if (!url) {
        console.error("Vui lòng nhập URL Server WebSocket!");
        return;
    }
    const ws = new WebSocket(url);
    ws.onopen = function () {
        show_message('Kết nối WebSocket thành công tới Server: ' + url);
        const sessionId = Math.random().toString(36).substring(2, 10);
        ws.send("VBot_Tester_Socket_Connect_" + sessionId);
        ws.close();
    };
    ws.onerror = function (error) {
        show_message("Lỗi kết nối tới Socket Server Streaming: " + error);
    };
    ws.onclose = function (event) {
        //console.log("Kết nối WebSocket đã đóng:", event.code, event.reason);
    };
}

function show_create_audio_WakeUP_Reply() {
    const resultDiv = document.getElementById("displayResults_create_audio_WakeUP_Reply");
    resultDiv.style.display = "block";
}

function handleAudioReplyType() {
    const type = document.getElementById("audio_reply_type").value;
    const contentDiv = document.getElementById("tts_audio_reply_options");
    if (type === "wakeup_reply_tts_gcloud") {
        contentDiv.innerHTML =
            '<div class="form-floating mb-3">' +
            '<input type="number" min="0.25" step="0.25" max="4.0" class="form-control border-success" name="create_tts_ggcloud_WakeUP_Reply_speed" id="create_tts_ggcloud_WakeUP_Reply_speed" value="1.0">' +
            '<label for="create_tts_ggcloud_WakeUP_Reply_speed" class="form-label">Tốc độ đọc:</label>' +
            '</div>' +
            '<div class="input-group mb-3">' +
            '<div class="form-floating">' +
            '<select name="create_tts_ggcloud_WakeUP_Reply_voice_name" id="tts_audio_reply_voice_name" class="form-select border-success">' +
            '<option>Đang tải danh sách...</option>' +
            '</select>' +
            '<label for="tts_audio_reply_voice_name">Giọng đọc:</label>' +
            '</div>' +
            '<button type="button" name="load_list_gcloud_tts" id="load_list_gcloud_tts" class="btn btn-primary" onclick="load_list_GoogleVoices_tts(\'tts_audio_reply\', \'ok\')" title="Tải danh sách giọng đọc TTS GCloud"><i class="bi bi-list-ul"></i></button>' +
            '<button type="button" class="btn btn-success" onclick="play_tts_sample_gcloud(\'tts_audio_reply_voice_name\')"><i class="bi bi-play-circle"></i></button>' +
            '</div>' +
            '<div class="form-floating mb-3">' +
            '<textarea type="text" class="form-control border-success" style="height: 100px;" name="tts_audio_reply_input_text" id="tts_audio_reply_input_text"></textarea>' +
            '<label for="tts_audio_reply_input_text" class="form-label">Nhập nội dung văn bản cần tạo file âm thanh</label>' +
            '</div>' +
            '<div class="input-group mb-3 border-success" id="showww_tts_audio_reply_output_path" style="display: none;"><span class="input-group-text border-danger">File Âm Thanh:</span>' +
            '<input type="text" id="tts_audio_reply_output_path" name="tts_audio_reply_output_path" class="form-control border-danger" readonly placeholder="Đường dẫn file âm thanh">' +
            '<button type="button" class="btn btn-primary border-danger" id="btn_play_audio_reply_out_p" onclick="playAudio(\'\')" title="Nghe thử file âm thanh vừa tạo"><i class="bi bi-megaphone"></i> Nghe Thử</button>' +
            '<button type="button" class="btn btn-warning border-danger" id="btn_download_audio_reply_out_p" title="Tải Xuống File Âm Thanh" onclick="downloadFile(\'\')"><i class="bi bi-download"></i></button>' +
            '</div>' +
            '<center>' +
            '<button type="button" class="btn btn-primary rounded-pill" onclick="createAudio_Wakeup_reply(\'tts_ggcloud\')" title="Tạo File âm thanh"> Tạo Âm Thanh</button> ' +
            ' <button type="button" class="btn btn-success rounded-pill" id="add_use_this_wakeup_reply_sound" onclick="use_this_wakeup_reply_sound(\'\')" title="Sử Dụng Âm Thanh Này"> Sử Dụng Âm Thanh Này</button> ' +
            '</center>';
        //Gọi hàm tải danh sách giọng đọc
        load_list_GoogleVoices_tts("tts_audio_reply", "ok");
    } else if (type === "wakeup_reply_tts_zalo") {
        contentDiv.innerHTML =
            '<div class="form-floating mb-3">' +
            '<input type="number" min="0.8" step="0.1" max="1.2" class="form-control border-success" name="create_tts_zalo_WakeUP_Reply_speed" id="create_tts_zalo_WakeUP_Reply_speed" value="1.0">' +
            '<label for="create_tts_zalo_WakeUP_Reply_speed" class="form-label">Tốc độ đọc:</label>' +
            '</div>' +
            '<div class="input-group mb-3">' +
            '<div class="form-floating">' +
            '<select name="create_tts_zalo_WakeUP_Reply_voice_name" id="create_tts_zalo_WakeUP_Reply_voice_name" class="form-select border-success">' +
            '<option value="1" selected>Nữ Miền Nam 1</option>' +
            '<option value="2">Nữ Miền Bắc 1</option>' +
            '<option value="3">Nam Miền Nam</option>' +
            '<option value="4">Nam Miền Bắc</option>' +
            '<option value="5">Nữ Miền Bắc 2</option>' +
            '<option value="6">Nữ Miền Nam 2</option>' +
            '</select>' +
            '<label for="create_tts_zalo_WakeUP_Reply_voice_name">Giọng đọc:</label>' +
            '</div>' +
            '</div>' +
            '<div class="form-floating mb-3">' +
            '<textarea type="text" class="form-control border-success" style="height: 100px;" name="tts_audio_reply_input_text" id="tts_audio_reply_input_text"></textarea>' +
            '<label for="tts_audio_reply_input_text" class="form-label">Nhập nội dung văn bản cần tạo file âm thanh</label>' +
            '</div>' +
            '<div class="input-group mb-3 border-success" id="showww_tts_audio_reply_output_path" style="display: none;"><span class="input-group-text border-danger">File Âm Thanh:</span>' +
            '<input type="text" id="tts_audio_reply_output_path" name="tts_audio_reply_output_path" class="form-control border-danger" readonly placeholder="Đường dẫn file âm thanh">' +
            '<button type="button" class="btn btn-primary border-danger" id="btn_play_audio_reply_out_p" onclick="playAudio(\'\')" title="Nghe thử file âm thanh vừa tạo"><i class="bi bi-megaphone"></i> Nghe Thử</button>' +
            '<button type="button" class="btn btn-warning border-danger" id="btn_download_audio_reply_out_p" title="Tải Xuống File Âm Thanh" onclick="downloadFile(\'\')"><i class="bi bi-download"></i></button>' +
            '</div>' +
            '<center>' +
            '<button type="button" class="btn btn-primary rounded-pill" onclick="createAudio_Wakeup_reply(\'tts_zalo\')" title="Tạo File âm thanh"> Tạo Âm Thanh</button> ' +
            ' <button type="button" class="btn btn-success rounded-pill" id="add_use_this_wakeup_reply_sound" onclick="use_this_wakeup_reply_sound(\'\')" title="Sử Dụng Âm Thanh Này"> Sử Dụng Âm Thanh Này</button> ' +
            '</center>';
    }
    else {
        contentDiv.innerHTML = "";
    }
}

//Xác Thực Liên Kết với Server XiaoZhi
async function xiaozhi_active_device_info() {
    loading("show");
    const id_otaUrl = document.getElementById("xiaozhi_ota_version_url");
    var otaUrl;
    if (!id_otaUrl) {
        show_message("Không tìm thấy phần tử có id 'xiaozhi_ota_version_url'");
    } else {
        otaUrl = id_otaUrl.value.trim();
        if (!otaUrl) {
            show_message("Link/URL OTA Server Gđang bị trống, không có dữ liệu");
        } else {
            if (!otaUrl.endsWith("/")) {
                otaUrl = otaUrl + "/";
            }
        }
    }
    const localUrl = "includes/php_ajax/Scanner.php?XiaoZhi_Active&action=get_device_info";
    try {
        showMessagePHP("Đang lấy thông tin thiết bị phần cứng...", 3);
        const response = await fetch(localUrl, { method: "GET" });
        if (!response.ok) throw new Error("HTTP Error " + response.status);
        const deviceInfo = await response.json();
        //console.log("deviceInfo:", JSON.stringify(deviceInfo, null, 2));
        showMessagePHP("Lấy thông tin thiết bị thành công", 3);
        if (!deviceInfo || Object.keys(deviceInfo).length === 0 || !deviceInfo.success) {
            loading("hide");
            show_message("<b class='text-danger'>Không lấy được thông tin phần cứng thiết bị</b>");
            return;
        }
        const headers = {
            "Device-Id": deviceInfo.mac_address,
            "Client-Id": deviceInfo.machine_id,
            "Content-Type": "application/json",
        };
        const payload = {
            application: {
                version: "VBot-" + deviceInfo.version_program,
                elf_sha256: deviceInfo.hmac_key,
            },
            board: {
                type: "demo",
                name: deviceInfo.device_model,
                ip: deviceInfo.ip_address,
                mac: deviceInfo.mac_address,
            },
        };
        showMessagePHP("Tiến hành gửi thông tin dữ liệu lên máy chủ...", 3);
        const otaResponse = await fetch(otaUrl, {
            method: "POST",
            headers: headers,
            body: JSON.stringify(payload),
        });
        const result = await otaResponse.json();
        //console.log("result:", JSON.stringify(result, null, 2));
        //Kiểm tra activation
        if (result?.activation?.challenge) {
            showMessagePHP("Đang tiến hành kích hoạt thiết bị này với máy chủ", 3);
            const challenge = result.activation.challenge;
            const active_number_code = result.activation.code;
            const hmacUrl = "includes/php_ajax/Scanner.php?XiaoZhi_Active&action=signature_hmac&challenge=" + encodeURIComponent(challenge);
            showMessagePHP("Gửi yêu cầu lấy HMAC...", 3);
            const hmacResponse = await fetch(hmacUrl);
            const hmacData = await hmacResponse.json();
            showMessagePHP("Đã ký Signature HMAC với thiết bị này thành công", 3);
            //Gửi activate và chờ nhập mã kích hoạt
            const activateUrl = otaUrl + "activate";
            const activatePayload = {
                Payload: {
                    algorithm: "hmac-sha256",
                    serial_number: deviceInfo.serial_number,
                    challenge: challenge,
                    hmac: hmacData.signature
                }
            };
            let activationCode = null;
            let waiting = true;
            showMessagePHP("Đang tiến hành gửi thông tin kích hoạt thiết bị này tới Máy Chủ, Vui Lòng Đợi Mã Kích Hoạt", 15);
            show_message("<b>Đang tiến hành gửi thông tin kích hoạt thiết bị này tới Máy Chủ, Vui Lòng Đợi Mã Kích Hoạt...</b>");
            while (waiting) {
                const activateResp = await fetch(activateUrl, {
                    method: "POST",
                    headers: headers,
                    body: JSON.stringify(activatePayload)
                });
                if (activateResp.status === 202) {
                    showMessagePHP("Đang chờ nhập mã kích hoạt: " + active_number_code, 5);
                    show_message("<b class='text-danger'>Mã Kích Hoạt Thiết Bị Của Bạn Là: <h5><center>" + active_number_code + "</center> Vui Lòng truy Cập: " + (result.activation.message.replace(/(\r?\n|\\n)(.*)/, " và nhập mã: $2")) + "</h5></b>-Vui Lòng Nhập Mã Kích Hoạt Này Trên Trang Chủ Của Server");
                    await new Promise(r => setTimeout(r, 3000));
                } else if (activateResp.status === 200) {
                    loading("hide");
                    const activateResult = await activateResp.json();
                    //console.log("activateResult:", JSON.stringify(activateResult, null, 2));
                    activationCode = activateResult.activation?.code || null;
                    waiting = false;
                    show_message("<b class='text-success'>Đã Kích Hoạt, Liên Kết Thiết Bị Này Thành Công</b>");
                    const logData = {
                        firmware_version: result.firmware.version,
                        activation_status: true,
                        client_id: deviceInfo.machine_id,
                        mac_address: deviceInfo.mac_address,
                        websocket_token: result.websocket.token,
                        websocket_url: result.websocket.url,
                        mqtt: result.mqtt,
                        activation_code: active_number_code,
                        device_id: activateResult.device_id,
                        serial_number: deviceInfo.serial_number,
                        hmac_signature: hmacData.signature
                    };
                    fetch("includes/php_ajax/Scanner.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: new URLSearchParams({
                            xiaozhi: "1",
                            action: "active_success_save_data",
                            json_data: JSON.stringify(logData)
                        }),
                    })
                        .then(res => res.json())
                        .then(data => {
                            loading("hide");
                            if (data.success) {
                                show_message("<b class='text-success'>" + (data.message || "Đã lưu dữ liệu kích hoạt thành công") + "</b>");
                            } else {
                                show_message("<b class='text-danger'>" + (data.message || "Không thể lưu dữ liệu kích hoạt") + "</b>");
                            }
                        })
                        .catch(err => {
                            loading("hide");
                            show_message('Kích hoạt thành công, Lỗi khi lưu dữ liệu kich hoạt vào Config.json:' + err);
                        });

                } else {
                    loading("hide");
                    const errText = await activateResp.text();
                    waiting = false;
                    show_message("Xảy ra lỗi khi kích hoạt, liên kết thiết bị: " + activateResp.status + " " + errText);
                }
            }
        } else {
            loading("hide");
            show_message("<b class='text-success'>Thiết bị đã được liên kết với máy chủ Server, Hãy tải lại trang này để làm mới dữ liệu cấu hình</b>");
            xiaozhi_activation_status_true();
        }
    } catch (error) {
        loading("hide");
        show_message("Lỗi khi gửi hoặc nhận dữ liệu: " + error);
    }
}

//Cấu hình, thành động thao tác với xiaozhi
function xiaozhi_action(action, confirmText, callback) {
    if (!confirm(confirmText)) return;
    loading('show');
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "includes/php_ajax/Scanner.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            loading('hide');
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        callback(true, response.message);
                    } else {
                        callback(false, response.message);
                    }
                } catch (e) {
                    callback(false, "Lỗi khi đọc phản hồi từ server: " + e.message);
                }
            } else {
                callback(false, "Lỗi kết nối server (HTTP " + xhr.status + ")");
            }
        }
    };
    xhr.send("xiaozhi=1&action=" + encodeURIComponent(action));
}

//Hủy liên kết và đặt lại cấu hình
function xiaozhi_unlink_reset_data() {
    xiaozhi_action(
        "unlink_reset_data",
        "-Bạn có chắc chắn muốn hủy liên kết và đặt lại dữ liệu cấu hình XiaoZhi này không?\n\n-Khi được đặt lại, bạn cần truy cập trang chủ Server để xóa thiết bị đã liên kết này",
        function (success, msg) {
            if (success) {
                if (confirm('-' + msg + "\n\n - Nhấn OK để áp dụng và tải lại trang này")) {
                    location.reload();
                }
            } else {
                show_message("Lỗi: " + msg);
            }
        }
    );
}

//Đặt lại activation_status = false
function xiaozhi_activation_status_false() {
    xiaozhi_action(
        "activation_status_false",
        "-Bạn có chắc chắn muốn chạy lại liên kết xác thực với máy chủ không?",
        function (success, msg) {
            show_message(msg);
        }
    );
}

//Đặt lại activation_status = true
function xiaozhi_activation_status_true() {
    xiaozhi_action(
        "activation_status_true",
        "-Bạn có chắc chắn muốn chạy lại liên kết xác thực với máy chủ không?",
        function (success, msg) {
            showMessagePHP(msg, 5);
        }
    );
}

//Lấy token zai_did tts_default
function get_token_tts_default_zai_did() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Check_Connection.php?get_token_tts_default_zai_did', true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const zaiDid = response.zai_did;
                    const expires = response.expires_zai_did;
                    document.getElementById('authentication_zai_did').value = zaiDid;
                    document.getElementById('expires_zai_did').value = expires;
                    showMessagePHP(response.message, 5);
                } else {
                    show_message(response.message);
                }
            } catch (e) {
                show_message(e);
            }
        }
    };
    xhr.send();
}

//Tạo file âm thanh tts gcloud
function createAudio_Wakeup_reply(source_tts) {
    loading('show');
    const text = document.getElementById("tts_audio_reply_input_text").value.trim();
    if (!text) {
        loading('hide');
        showMessagePHP("Vui lòng nhập nội dung để tạo âm thanh", 5);
        return;
    }
    let url_tts = "";
    if (source_tts === 'tts_ggcloud') {
        const speed = document.getElementById("create_tts_ggcloud_WakeUP_Reply_speed").value;
        const voiceName = document.getElementById("tts_audio_reply_voice_name").value;
        const encodedText = encodeURIComponent(text);
        const encodedVoice = encodeURIComponent(voiceName);
        url_tts = "includes/php_ajax/TTS_Audio_Create.php?create_tts_audio&source_tts=tts_ggcloud&language_code=vi-VN&speaking_rate=" + speed + "&voice_name=" + encodedVoice + "&text=" + encodedText;
    }
    else if (source_tts === 'tts_zalo') {
        const speakerId = document.getElementById("create_tts_zalo_WakeUP_Reply_voice_name").value;
        const speakerSpeed = document.getElementById("create_tts_zalo_WakeUP_Reply_speed").value;
        const encodedText = encodeURIComponent(text);
        url_tts = "includes/php_ajax/TTS_Audio_Create.php"
            + "?create_tts_audio"
            + "&source_tts=tts_zalo"
            + "&speaker_id=" + speakerId
            + "&speaker_speed=" + speakerSpeed
            + "&encode_type=1&text=" + encodedText;
    }
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url_tts, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        showMessagePHP(data.message || "Tạo file âm thanh thành công", 5);
                        const filePathInput = document.getElementById("tts_audio_reply_output_path");
                        const showww_reply_output_path = document.getElementById("showww_tts_audio_reply_output_path");
                        const playButton = document.getElementById("btn_play_audio_reply_out_p");
                        const downloadButton = document.getElementById("btn_download_audio_reply_out_p");
                        const adddButton = document.getElementById("add_use_this_wakeup_reply_sound");
                        if (filePathInput && showww_reply_output_path) {
                            loading('hide');
                            filePathInput.value = data.file_path || "";
                            showww_reply_output_path.style.display = "flex";
                            playButton.setAttribute("onclick", "playAudio('" + data.file_path + "')");
                            downloadButton.setAttribute("onclick", "downloadFile('" + data.file_path + "')");
                            adddButton.setAttribute("onclick", "use_this_wakeup_reply_sound('" + data.file_path + "')");
                        }
                    } else {
                        loading('hide');
                        showMessagePHP("Lỗi khi tạo file âm thanh: " + (data.message || "Không rõ lỗi"), 5);
                    }
                } catch (e) {
                    loading('hide');
                    showMessagePHP("Lỗi Phản Hồi Từ Server, Vui Lòng Thử Lại: " + e.message, 5);
                }
            } else {
                loading('hide');
                showMessagePHP("Không thể gửi yêu cầu tạo âm thanh. Mã lỗi: " + xhr.status, 5);
            }
        }
    };
    xhr.send();
}

//Lựa chọn file âm thanh dùng cho wakeup reply
function use_this_wakeup_reply_sound(filePath) {
    if (!filePath) {
        show_message('Không có dữ liệu file âm thanh');
        return;
    }
    loading('show');
    const encodedFilePath = encodeURIComponent(filePath);
    const url = 'includes/php_ajax/Hotword_pv_ppn.php?use_this_wakeup_reply_sound&file_path=' + encodedFilePath;
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        loadWakeupReply();
                        showMessagePHP(response.message || "Đã sử dụng file âm thanh làm wakeup reply!", 5);
                        loading('hide');
                    } else {
                        loading('hide');
                        show_message(response.message || "Thao tác thất bại!");
                    }
                } catch (err) {
                    loading('hide');
                    show_message("Lỗi xử lý phản hồi server!");
                }
            } else {
                loading('hide');
                show_message("Lỗi kết nối đến server!");
            }
        }
    };
    xhr.send();
}

//Cập nhật đường dẫn path web ui vào thẻ input html
function update_webui_link(path_web_ui_html) {
    const inputField = document.getElementById('webui_path');
    if (inputField) {
        inputField.value = path_web_ui_html
        showMessagePHP("Đã cập nhật đường dẫn path Web UI: " + path_web_ui_html)
    } else {
        show_message('Không tìm thấy input có id "webui_path".');
    }
}

//Hiển thị dữ liệu BackList.json
function getBacklistData(dataPath, textareaId) {
    var url = "includes/php_ajax/Show_file_path.php?data_backlist";
    var xhr = new XMLHttpRequest();
    xhr.addEventListener("readystatechange", function () {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        var data = response.data;
                        var pathParts = dataPath.split('->');
                        var currentData = data;
                        for (var i = 0; i < pathParts.length; i++) {
                            var part = pathParts[i].trim();
                            currentData = currentData[part];
                        }
                        var textarea = document.getElementById(textareaId);
                        if (textarea) {
                            textarea.value = JSON.stringify(currentData, null, 4);
                        } else {
                            show_message("Không tìm thấy thẻ textarea với ID: " + textareaId);
                        }
                        showMessagePHP("Dữ liệu đã được tải thành công.", 3);
                    } else {
                        show_message("Có lỗi xảy ra: " + response.message);
                    }
                } catch (e) {
                    show_message("Có lỗi xảy ra khi xử lý phản hồi từ máy chủ: " + e);
                }
            } else {
                show_message("Có lỗi xảy ra khi gửi yêu cầu. Mã lỗi: " + this.status);
            }
        }
    });
    xhr.onerror = function () {
        show_message("Lỗi mạng hoặc không thể kết nối với máy chủ.");
    };
    xhr.open("GET", url, true);
    xhr.send();
}

//Thay đổi giá trị value của BackList.json theo đường dẫn chỉ định 
function changeBacklistValue(path_json, value_type) {
    var url = "includes/php_ajax/Show_file_path.php";
    var params = "delete_data_backlist=1&path=" + path_json + "&value_type=" + value_type;
    var xhr = new XMLHttpRequest();
    xhr.addEventListener("readystatechange", function () {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        showMessagePHP(response.message, 3);
                        if (path_json === "backlist->tts_zalo->backlist_limit") {
                            getBacklistData('backlist->tts_zalo', 'tts_zalo_backlist_content');
                        } else if (path_json === "backlist->tts_viettel->backlist_limit") {
                            getBacklistData('backlist->tts_viettel', 'tts_viettel_backlist_content');
                        }
                    } else {
                        ;
                        show_message("Có lỗi xảy ra khi cập nhật backlist: " + response.message);
                    }
                } catch (e) {
                    show_message("Lỗi phân tích cú pháp JSON: " + e);
                }
            } else {
                show_message("Có lỗi xảy ra khi gửi yêu cầu. Mã lỗi: " + this.status);
            }
        }
    });
    xhr.onerror = function () {
        show_message("Lỗi mạng hoặc không thể kết nối với máy chủ.");
    };
    xhr.open("GET", url + "?" + params, true);
    xhr.send();
}

//Hàm gọi Test LED khi nhấn nút
function get_test_led() {
    let selectEl = document.getElementById("get_test_led_selected");
    if (!selectEl) {
        showMessagePHP('Không tìm thấy thẻ select có id: get_test_led_selected', 3);
        return;
    }
    let value = selectEl.value;
    if (value === "") {
        show_message("Vui Lòng Chọn Hiệu Ứng Để Kiểm Tra");
        return;
    }
    test_led(value);
}

//scan mic hoặc audio out
function scan_audio_devices(device_name) {
    loading("show");
    var xhr = new XMLHttpRequest();
    var url = 'includes/php_ajax/Scanner.php?' + device_name;
    xhr.open('GET', url, true);
    xhr.responseType = 'json';
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            loading("hide");
            var data = xhr.response;
            if (data && data.success) {
                if (device_name === "scan_mic") {
                    var container = document.getElementById('mic_scanner');
                    var tableHTML = '<table class="table table-bordered border-primary">';
                    tableHTML += '<thead><tr><th colspan="3" style="text-align: center; vertical-align: middle;"><font color=green>' + data.message + '</font></th></tr><tr><th style="text-align: center; vertical-align: middle;">ID Mic</th><th style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th><th style="text-align: center; vertical-align: middle;">Hành Động</th></tr></thead>';
                    tableHTML += '<tbody>';
                    data.devices.forEach(function (device) {
                        tableHTML += '<tr><td style="text-align: center; vertical-align: middle;">' + device.ID + '</td><td style="vertical-align: middle;">' + (device.Tên || '') + '</td><td style="text-align: center; vertical-align: middle;"><button type="button" class="btn btn-primary rounded-pill" onclick="selectDevice_MIC(' + device.ID + ')">Chọn</button></td></td></tr>';
                    });
                    tableHTML += '</tbody></table>';
                    if (container) {
                        showMessagePHP(data.message, 4);
                        container.innerHTML = tableHTML;
                    } else {
                        show_message('Không tìm thấy thẻ div với id: ' + container);
                    }
                }
                //Hiển thị thông tin khi scan_alsamixer 
                else if (device_name === "scan_alsamixer") {
                    var container = document.getElementById('alsamixer_scan');
                    var tableHTML = '<table class="table table-bordered border-primary">';
                    tableHTML += '<thead><tr><th colspan="6" style="text-align: center; vertical-align: middle;"><font color=green>' + data.message + '</font></th></tr><tr><th style="text-align: center; vertical-align: middle;">ID Speaker</th><th style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th><th style="text-align: center; vertical-align: middle;">Khả Năng</th><th style="text-align: center; vertical-align: middle;">Kênh Phát</th><th style="text-align: center; vertical-align: middle;">Thông Số</th><th style="text-align: center; vertical-align: middle;">Hành Động</th></tr></thead>';
                    tableHTML += '<tbody>';
                    data.devices.forEach(function (device) {
                        tableHTML += '<tr><td style="text-align: center; vertical-align: middle;">' + device.id + '</td><td style="vertical-align: middle;">' + (device.name || '') + '</td><td style="vertical-align: middle;">' + (device.capabilities || '') + '</td><td style="vertical-align: middle;">' + (device.playback_channels || '') + '</td><td style="vertical-align: middle;">' + (device.values.length > 0 ? device.values.map(value => (value.channel || '') + ' ' + (value.details || '')).join('<br>') : '') + '</td><td style="text-align: center; vertical-align: middle;"><button type="button" class="btn btn-primary rounded-pill" onclick="selectDevice_Alsamixer(\'' + device.name + '\')">Chọn</button></td></td></tr>';
                    });
                    tableHTML += '</tbody></table>';
                    if (container) {
                        showMessagePHP(data.message, 4);
                        container.innerHTML = tableHTML;
                    } else {
                        show_message('Không tìm thấy thẻ div với id: ' + container);
                    }
                }
            } else if (data) {
                show_message('Lỗi: ' + data.message);
            } else {
                show_message('Lỗi không xác định. Vui lòng thử lại sau.');
            }
        } else {
            loading("hide");
            show_message('Lỗi: ' + xhr.statusText);
        }
    };
    xhr.onerror = function () {
        loading("hide");
        show_message('Yêu cầu thất bại. Vui lòng kiểm tra kết nối mạng.');
    };
    xhr.send();
}

//Check key picovoice
function test_key_Picovoice() {
    loading("show");
    var token = document.getElementById('hotword_engine_key').value;
    var lang = document.getElementById('select_hotword_lang').value;
    var xhr = new XMLHttpRequest();
    var url = 'includes/php_ajax/Check_Connection.php?check_key_picovoice&key=' + token + '&lang=' + lang;
    xhr.open('GET', url);
    xhr.responseType = 'json';
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            loading("hide");
            var data = xhr.response;
            if (data.success) {
                show_message('<font color=green><center>' + data.message + '</center><br/>- Ngôn ngữ kiểm tra: <b>' + data.language_name + '</b><br/>- File Hotword kiểm tra ngẫu nhiên trong thư mục ' + data.lang + ': <b>' + data.hotword_random_test + '</b><br/>- File thư viện Procupine: <b>' + data.model_file_path + '</b></font>');
            } else {
                show_message('<font color=red><center>Lỗi</center> ' + data.message + '</font>');
            }
        } else {
            loading("hide");
            show_message('Lỗi: ' + xhr.statusText);
        }
    };
    xhr.onerror = function () {
        loading("hide");
        show_message('Yêu cầu thất bại');
    };
    xhr.send();
}

//Hiển thị các bài hát trong thư mục Local
function list_audio_show_path(id_path_music) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/Show_file_path.php?' + encodeURIComponent(id_path_music), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            //console.log(xhr.responseText)
            var data = JSON.parse(xhr.responseText);
            if (id_path_music === "scan_Music_Local") {
                var tableBody = document.getElementById('show_mp3_music_local').getElementsByTagName('tbody')[0];
                tableBody.innerHTML = '';
                var tableHead = document.querySelector('#show_mp3_music_local thead');
                tableHead.innerHTML =
                    '<tr>' +
                    '<th colspan="3"><center>Danh Sách Bài Hát Có Trong Thư Mục Music_Local</center></th>' +
                    '</tr>' +
                    '<tr>' +
                    '<th><center>STT</center></th>' +
                    '<th><center>Tên File</center></th>' +
                    '<th><center>Hành Động</center></th>' +
                    '</tr>';
                data.forEach(function (file, index) {
                    var fileName = getFileNameFromPath(file);
                    var rowContent =
                        '<tr>' +
                        '<td style="text-align: center; vertical-align: middle;"><center>' + (index + 1) + '</center></td>' +
                        '<td><input readonly class="form-control border-primary" type="text" name="file_name_music_local' + index + '" value="' + fileName + '"></td>' +
                        '<td style="text-align: center; vertical-align: middle;"><center>' +
                        '<button type="button" class="btn btn-danger" title="Xóa file: ' + fileName + '" onclick="deleteFile(\'' + file + '\', \'scan_Music_Local\')"><i class="bi bi-trash"></i></button>' +
                        ' <button type="button" class="btn btn-success" title="Tải Xuống file: ' + fileName + '" onclick="downloadFile(\'' + file + '\')"><i class="bi bi-download"></i></button>' +
                        '</center></td>' +
                        '</tr>';
                    tableBody.insertAdjacentHTML('beforeend', rowContent);
                });
            } else if (id_path_music === "scan_Audio_Startup") {
                var tableBody = document.getElementById('show_mp3_sound_welcome').getElementsByTagName('tbody')[0];
                tableBody.innerHTML = '';
                var tableHead = document.querySelector('#show_mp3_sound_welcome thead');
                tableHead.innerHTML =
                    '<tr>' +
                    '<th colspan="3"><center>Danh Sách Âm Thanh Có Trong Thư Mục Welcome</center></th>' +
                    '</tr>' +
                    '<tr>' +
                    '<th><center>STT</center></th>' +
                    '<th><center>Tên File</center></th>' +
                    '<th><center>Hành Động</center></th>' +
                    '</tr>';
                data.forEach(function (file, index) {
                    var fileName = getFileNameFromPath(file);
                    var rowContent =
                        '<tr>' +
                        '<td style="text-align: center; vertical-align: middle;"><center>' + (index + 1) + '</center></td>' +
                        '<td><input readonly class="form-control border-primary" type="text" name="file_name_music_local' + index + '" value="' + fileName + '"></td>' +
                        '<td style="text-align: center; vertical-align: middle;"><center>' +
                        '<button type="button" class="btn btn-danger" title="Xóa file: ' + fileName + '" onclick="deleteFile(\'' + file + '\', \'scan_Audio_Startup\')"><i class="bi bi-trash"></i></button>' +
                        ' <button type="button" class="btn btn-success" title="Tải Xuống file: ' + fileName + '" onclick="downloadFile(\'' + file + '\')"><i class="bi bi-download"></i></button>' +
                        '</center></td>' +
                        '</tr>';
                    tableBody.insertAdjacentHTML('beforeend', rowContent);
                });
            }
        }
    };
    xhr.send();
}

//Kiểm tra Kết Nối SSH
function checkSSHConnection(sshHost) {
    loading("show");
    var sshPort = document.getElementById('ssh_port').value;
    var sshUser = document.getElementById('ssh_username').value;
    var sshPass = document.getElementById('ssh_password').value;
    var xhr = new XMLHttpRequest();
    var url = 'includes/php_ajax/Check_Connection.php?check_ssh' +
        '&host=' + encodeURIComponent(sshHost) +
        '&port=' + encodeURIComponent(sshPort) +
        '&user=' + encodeURIComponent(sshUser) +
        '&pass=' + encodeURIComponent(sshPass);
    xhr.open('GET', url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            loading("hide");
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        show_message(response.message);
                    } else {
                        show_message("Lỗi: " + response.message);
                    }
                } catch (e) {
                    show_message("Lỗi phân tích cú pháp phản hồi: " + e.message);
                }
            } else {
                show_message("Lỗi kết nối: trạng thái HTTP " + xhr.status);
            }
        }
    };
    xhr.send();
}

//Kiểm tra kết nối Home Assistant
function CheckConnectionHomeAssistant(inputId) {
    loading("show");
    var url_hasss = document.getElementById(inputId).value;
    var token_hasss = document.getElementById('hass_long_token').value;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Check_Connection.php?check_hass&url_hass=' + encodeURIComponent(url_hasss) + '&token_hass=' + encodeURIComponent(token_hasss), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            loading("hide");
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    show_message('<center><font color=green><b>' + response.message + '</b></font></center><br/><b>- Tên Nhà:</b> ' + response.response.location_name +
                        '<br/><b>- Kinh độ:</b> ' + response.response.longitude + '<br/><b>- Vĩ độ:</b> ' + response.response.latitude + '<br/><b>- Múi giờ:</b> ' + response.response.time_zone +
                        '<br/><b>- Quốc gia:</b> ' + response.response.country + '<br/><b>- Ngôn ngữ:</b> ' + response.response.language +
                        '<br/><b>- Phiên bản Home Assistant:</b> ' + response.response.version + '<br/><b>- Trạng thái hoạt động:</b> ' + response.response.state +
                        '<br/><b>- URL nội bộ:</b> <a href="' + response.response.internal_url + '" target="_blank">' + response.response.internal_url + '</a><br/><b>- URL bên ngoài:</b> <a href="' + response.response.external_url + '" target="_blank">' + response.response.external_url + '</a>');
                } else {
                    show_message('<center><font color=red><b>Thất bại</b></font></center><br/>' + response.message)
                }
            } else {
                show_message('<center><font color=red><b>Thất bại</b></font></center><br/>' + xhr.statusText)
            }
        }
    };
    xhr.send();
}

//Tải lên file hotword Snowboy
function uploadFilesHotwordSnowboy() {
    const formData = new FormData();
    const files = document.getElementById('upload_files_hotword_snowboy').files;
    const lang = 'snowboy'
    if (files.length === 0) {
        show_message('Vui lòng chọn ít nhất một file để tải lên.');
        return;
    }
    for (let i = 0; i < files.length; i++) {
        formData.append('upload_files_hotword_snowboy[]', files[i]);
        //console.log(files);
    }
    formData.append('action_hotword_snowboy', 'upload_files_hotword_snowboy');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/Hotword_pv_ppn.php');
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                let messages = [];
                if (response.status === 'success') {
                    messages.push(response.messages + '<br/>');
                } else {
                    messages.push('Trạng thái phản hồi không mong đợi: ' + response.status);
                }
                //Tải lại dữ liệu hotword ở Config.json
                loadConfigHotword(lang)
                show_message(messages.join('<br/>'));
            } catch (e) {
                show_message('Không thể phân tích phản hồi json: ' + e.message);
            }
        } else {
            show_message("<center>Tải file lên thất bại</center>");
        }
    };
    xhr.send(formData);
}

//Tải lên file ppv và pv dùng cho Picovoice/Procupine
function uploadFilesHotwordPPNandPV() {
    const formData = new FormData();
    const files = document.getElementById('upload_files_ppn_pv').files;
    const lang = document.getElementById('lang_hotword_get').value;
    if (files.length === 0) {
        show_message('Vui lòng chọn ít nhất một file để tải lên.');
        return;
    }
    for (let i = 0; i < files.length; i++) {
        formData.append('upload_files_ppn_pv[]', files[i]);
    }
    formData.append('lang_hotword_get', lang);
    formData.append('action_ppn_pv', 'upload_files_ppn_pv');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/Hotword_pv_ppn.php');
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                let messages = [];
                if (response.status === 'success') {
                    messages.push(response.messages + '<br/>');
                } else {
                    messages.push('Trạng thái phản hồi không mong đợi: ' + response.status);
                }
                //Tải lại dữ liệu hotword ở Config.json
                loadConfigHotword(lang)
                show_message(messages.join('<br/>'));
            } catch (e) {
                show_message('Không thể phân tích phản hồi json: ' + e.message);
            }
        } else {
            show_message("<center>Tải file lên thất bại</center>");
        }
    };
    xhr.send(formData);
}

//Cập nhật các file trong eng và vi để Làm mới lại cấu hình Hotword trong Config.json, 
function reload_hotword_config(langg = "No") {
    if (!confirm("Bạn có chắc chắn muốn cập nhật mới dữ liệu")) {
        return;
    }
    var xhr = new XMLHttpRequest();
    if (langg === "No") {
        xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?reload_hotword_config');
        xhr.onload = function () {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    show_message("<center>" + response.message + "</center>");
                } else {
                    show_message("<center>" + response.message + "</center>");
                }
                var element_data_lang_shows = document.getElementById('data_lang_shows');
                if (element_data_lang_shows) {
                    var value_lang = element_data_lang_shows.getAttribute('value');
                    if (value_lang === "vi") {
                        loadConfigHotword("vi");
                    } else if (value_lang === "eng") {
                        loadConfigHotword("eng");
                    }
                }
            } else {
                show_message("<center>Có lỗi xảy ra khi ghi mới dữ liệu Hotword tiếng anh và tiếng việt</center>");
            }
        };
    } else if (langg === 'snowboy') {
        xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?reload_hotword_config_snowboy');
        xhr.onload = function () {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    show_message("<center>" + response.message + "</center>");
                } else {
                    show_message("<center>" + response.message + "</center>");
                }
                loadConfigHotword("snowboy");
            } else {
                show_message("<center>Có lỗi xảy ra khi ghi mới dữ liệu Hotword tiếng anh và tiếng việt</center>");
            }
        };
    } else if (langg === 'wakeup_reply') {
        xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?reload_wakeup_reply');
        xhr.onload = function () {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    show_message("<center>" + response.message + "</center>");
                } else {
                    show_message("<center>" + response.message + "</center>");
                }
                loadWakeupReply();
            } else {
                show_message("<center>Có lỗi xảy ra khi ghi mới dữ liệu Câu Phản Hồi Wakeup Reply</center>");
            }
        };
    }
    xhr.send();
}

//Tải Lên File âm thanh wakeup_reply
function uploadFilesWakeUP_Reply() {
    const input = document.getElementById('upload_files_wakeup_reply');
    const files = input.files;
    if (files.length === 0) {
        show_message('Vui lòng chọn ít nhất một file âm thanh .mp3 để tải lên.');
        return;
    }
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('upload_files_wakeup_reply[]', files[i]);
    }
    formData.append('wakeup_reply_upload', 'upload_files_wakeup_reply');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/Hotword_pv_ppn.php', true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                const res = JSON.parse(xhr.responseText);
                let messageHtml = "";
                if (Array.isArray(res.messages)) {
                    messageHtml = res.messages.join("\n");
                } else if (res.message) {
                    messageHtml = res.message;
                }
                show_message(messageHtml);
                input.value = '';
                loadWakeupReply();
            } catch (e) {
                show_message('Lỗi khi phân tích phản hồi từ máy chủ.');
            }
        } else {
            show_message('Lỗi khi gửi yêu cầu tải lên.');
        }
    };
    xhr.send(formData);
}

//Hiển thị list danh sách câu phản hồi
function loadWakeupReply(pathVBot_PY) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?get_wakeup_reply', true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    //showMessagePHP('Lấy Dữ Liệu Câu Phản Hồi Thành Công');
                    const data = response.config;
                    const container = document.getElementById('displayResults_wakeup_reply');
                    if (!data || !Array.isArray(data) || data.length === 0) {
                        container.innerHTML = '<div class="alert alert-warning text-center mt-3 text-danger">Dữ liệu Rỗng, Không có dữ liệu câu phản hồi nào</div>';
                        return;
                    }
                    let html =
                        '<br/><table class="table table-bordered border-primary">' +
                        '<thead>' +
                        '<tr>' +
                        '<th class="text-danger" style="text-align: center;" colspan="4">Cài Đặt Câu Phản Hồi</th>' +
                        '</tr>' +
                        '<tr>' +
                        '<th style="text-align: center;">STT</th>' +
                        '<th style="text-align: center;">Kích Hoạt</th>' +
                        '<th style="text-align: center;">Đường Dẫn File</th>' +
                        '<th style="text-align: center;">Hành Động</th>' +
                        '</tr>' +
                        '</thead>' +
                        '<tbody>';
                    data.forEach((item, index) => {
                        html +=
                            '<tr>' +
                            '<td style="text-align: center;">' + (index + 1) + '</td>' +
                            '<td style="text-align: center;"><div class="form-switch">' +
                            '<input class="form-check-input border-success" type="checkbox" name="save_wakeup_reply_active_' + index + '" ' + (item.active ? 'checked' : '') + '>' +
                            '</div></td>' +
                            '<td>' +
                            '<input readonly class="form-control border-danger" type="text" name="save_wakeup_reply_file_name_' + index + '" value="' + item.file_name + '">' +
                            '</td>' +
                            '<td style="text-align: center;">' +
                            '<button type="button" title="Nghe thử: ' + item.file_name + '" class="btn btn-primary" onclick="playAudio(\'' + pathVBot_PY + item.file_name + '\')"><i class="bi bi-play-circle"></i></button> ' +
                            ' <button type="button" class="btn btn-success" onclick="downloadFile(\'' + pathVBot_PY + item.file_name + '\')" title="Tải Xuống File: ' + item.file_name + '"><i class="bi bi-download"></i></button> ' +
                            ' <button type="button" class="btn btn-danger" title="Xóa file: ' + item.file_name + '" onclick="deleteFile(\'' + pathVBot_PY + item.file_name + '\', \'wakeup_reply\')"><i class="bi bi-trash"></i></button>' +
                            '</td>' +
                            '</tr>';
                    });
                    html +=
                        '<tr><td colspan="4"><center><button class="btn btn-success rounded-pill" type="submit" name="save_config_wakeup_reply" title="Lưu cài đặt câu phản hồi">Lưu Cài Đặt Câu Phản Hồi</button></center></td></tr>' +
                        '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    show_message('Không thành công: ' + response.message);
                }
            } catch (err) {
                show_message('Lỗi khi phân tích JSON: ' + err);
            }
        } else {
            show_message('Lỗi HTTP: ' + xhr.status);
        }
    };
    xhr.onerror = function () {
        show_message('Lỗi khi gửi yêu cầu.');
    };
    xhr.send();
}

//Hiển thị list hotword khi được scan
function loadConfigHotword(lang) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Hotword_pv_ppn.php?hotword&lang=' + lang, true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            if (lang === "vi" || lang === "eng") {
                displayResults_Hotword_dataa(data);
            } else if (lang === "snowboy") {
                displayResults_Hotword_Snowboy(data);
            }
        }
    };
    xhr.send();
}

//Đọc nội dung các file yaml MQTT
function read_YAML_file_path(fileName) {
    loading('show');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'includes/php_ajax/Show_file_path.php?yaml=' + fileName, true);
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            loading('hide');
            var codeElement = document.getElementById('code_config');
            codeElement.textContent = xhr.responseText.trim();
            codeElement.className = 'language-yaml';
            Prism.highlightElement(codeElement);
            showMessagePHP("Lấy Dữ Liệu: " + fileName + " thành công", 3)
            document.getElementById('name_file_showzz').textContent = "Tên File: " + fileName.split('/').pop();
            $('#myModal_Config').modal('show');
        } else {
            loading('hide');
            show_message('Thất bại, Mã lỗi:' + xhr.status)
        }
    };
    xhr.onerror = function () {
        loading('hide');
        show_message('Lỗi xảy ra, Truy vấn thất bại')
    };
    xhr.send();
}

//Kiểm tra kết nối MQTT
function checkMQTTConnection() {
    loading('show');
    var host = document.getElementById('mqtt_host').value;
    var port = document.getElementById('mqtt_port').value;
    var user = document.getElementById('mqtt_username').value;
    var pass = document.getElementById('mqtt_password').value;
    var url = 'includes/php_ajax/Check_Connection.php?check_mqtt&host=' + host + '&port=' + port + '&user=' + user + '&pass=' + pass;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            loading('hide');
            try {
                var response = JSON.parse(xhr.responseText);
                show_message(response.message)
            } catch (e) {
                show_message('Lỗi khi phân tích cú pháp JSON: ' + e)
            }
        } else {
            loading('hide');
            show_message('Yêu cầu thất bại. Mã lỗi: ' + xhr.status)
        }
    };
    xhr.onerror = function () {
        loading('hide');
        show_message('Yêu cầu bị lỗi.')
    };
    xhr.send();
}

//Hiển thị select khi nhấn icon lits đài radio
function showRadioSelect(index, path_file) {
  const select = document.getElementById('radio_select_' + index);
  if (select.options.length > 0) {
    select.style.display = 'block';
    select.focus();
    return;
  }
  fetch('includes/php_ajax/Show_file_path.php?read_file_path&file='+path_file+'/includes/other_data/lits_newspaper_radio.json')
    .then(response => response.json())
    .then(json => {
      if (!json.success || !json.data || !Array.isArray(json.data.radio)) {
		show_message("Không thể đọc danh sách Đài, Radio");
        return;
      }
      const radioOptions = json.data.radio;
      select.innerHTML = "";
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = '-- Chọn Đài, Radio --';
      select.appendChild(defaultOption);
      radioOptions.forEach(radio => {
        const option = document.createElement('option');
        option.value = radio.link;
        option.textContent = radio.name;
        option.dataset.name = radio.name;
        select.appendChild(option);
      });
      select.style.display = 'block';
      select.focus();
    })
    .catch(error => {
	  show_message("Lỗi khi tải danh sách Đài, Radio: "+error);
    });
}

//Khi chọn option, update gán giá trị vào input link và name của input đài radio
function updateRadioLinkName(index, select) {
  const inputLink = document.getElementById('radio_link_' + index);
  const inputName = document.getElementById('radio_name_' + index);
  const selectedOption = select.options[select.selectedIndex];
  if (!selectedOption) return;
  inputLink.value = selectedOption.value;
  inputName.value = selectedOption.dataset.name || '';
  select.style.display = 'none';
}

//Hiển thị select khi nhấn icon lits báo, tin tức
function showNewsPaperSelect(index, path_file) {
  const select = document.getElementById('newspaper_select_' + index);
  if (select.options.length > 0) {
    select.style.display = 'block';
    select.focus();
    return;
  }
  fetch('includes/php_ajax/Show_file_path.php?read_file_path&file='+path_file+'/includes/other_data/lits_newspaper_radio.json')
    .then(response => response.json())
    .then(json => {
      if (!json.success || !json.data || !Array.isArray(json.data.newspaper)) {
		show_message("Không thể đọc danh sách Báo, Tin Tức");
        return;
      }
      const newspaperOptions = json.data.newspaper;
      select.innerHTML = "";
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = '-- Chọn Nguồn Báo, Tin Tức --';
      select.appendChild(defaultOption);
      newspaperOptions.forEach(newspaper => {
        const option = document.createElement('option');
        option.value = newspaper.link;
        option.textContent = newspaper.name;
        option.dataset.name = newspaper.name;
        select.appendChild(option);
      });
      select.style.display = 'block';
      select.focus();
    })
    .catch(error => {
	  show_message("Lỗi khi tải danh sách Báo, Tin Tức: "+error);
    });
}

//Khi chọn option, update gán giá trị vào input link và name của input báo, tin tức
function updateNewsPaperLinkName(index, select) {
  const inputLink = document.getElementById('newspaper_link_' + index);
  const inputName = document.getElementById('newspaper_name_' + index);
  const selectedOption = select.options[select.selectedIndex];
  if (!selectedOption) return;
  inputLink.value = selectedOption.value;
  inputName.value = selectedOption.dataset.name || '';
  select.style.display = 'none';
}