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
    radio.addEventListener('change', function() {
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
    radio.addEventListener('change', function() {
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
        data.config.forEach(function(item, index) {
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
    // Xóa nội dung hiện tại
    resultsDiv.innerHTML = '';
    resultsDiv1.innerHTML = '';
    // Hiển thị ngôn ngữ đang được truy vấn
    let reponse_lang = data.lang === "vi" ? "Tiếng Việt" : "Tiếng Anh";
    const langDiv = document.getElementById('language_hotwordd');
    langDiv.innerHTML = '<strong>- Ngôn ngữ: </strong> <font color="red" id="data_lang_shows" value=' + data.lang + '>' + reponse_lang + '</font><br/>- File Thư Viện Đang Dùng: <font color="red">' + data.config_lib_pv_to_lang + '</font>';
    const fileList = data.files_lib_pv;
    // Tạo thẻ <select> một lần với tất cả các tùy chọn
    var selectHtml = '<tr><td colspan="4"><div class="form-floating mb-3"><select required class="form-select" id="select_file_lib_pv" name="select_file_lib_pv">';
    selectHtml += '<option value="">Hãy chọn file thư viện .pv ' + reponse_lang + ' để cấu hình</option>';
    fileList.forEach(function(file) {
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
        fileList.forEach(function(file) {
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
    // Lấy giá trị mặc định của thẻ select khi tải trang
    var selectedValuee = document.getElementById('select_file_lib_pv').value;
    var deleteIconn = document.getElementById('deleteFilePV');
    var downloadIcon = document.getElementById('downloadFilePV');
    // Thiết lập onclick xóa file trong thẻ select với giá trị mặc định
    deleteIconn.onclick = function() {
        if (selectedValuee) {
            deleteFile(data.path_pv + selectedValuee);
            // Tải lại dữ liệu hotword ở Config.json
            loadConfigHotword(data.lang);
        } else {
            show_message('Cần chọn file thư viện .pv trước khi xóa');
        }
    };
    // Thiết lập onclick tải xuống file
    downloadIcon.onclick = function() {
        if (selectedValuee) {
            downloadFile(data.path_pv + selectedValuee);
        } else {
            show_message('Cần chọn file thư viện .pv trước khi tải xuống');
        }
    };
    // Lắng nghe sự kiện thay đổi trên thẻ select pv để xóa file
    document.getElementById('select_file_lib_pv').addEventListener('change', function() {
        var selectedValue = this.value;
        var deleteIcon = document.getElementById('deleteFilePV');
        var downloadIcon = document.getElementById('downloadFilePV');
        deleteIcon.onclick = function() {
            if (selectedValue) {
                deleteFile(data.path_pv + selectedValue);
                // Tải lại dữ liệu hotword ở Config.json
                loadConfigHotword(data.lang);
            } else {
                show_message('Cần chọn file thư viện .pv trước khi xóa');
            }
        };
        downloadIcon.onclick = function() {
            if (selectedValue) {
                downloadFile(data.path_pv + selectedValue);
            } else {
                show_message('Cần chọn file thư viện .pv trước khi tải xuống');
            }
        };
        // Cập nhật title của icon
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
    xhr.onload = function() {
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
    xhr.onerror = function() {
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
    xhr.onreadystatechange = function() {
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
    var url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' + apiKey;
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
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            loading("hide");
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                var candidates = response.candidates;
                if (candidates.length > 0) {
                    var contentText = candidates[0].content.parts[0].text;
                    show_message('<center>Kiểm Tra API KEY Thành Công</center><br/>Phản hồi: <font color=green>' + contentText + '</font>');
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
function play_tts_sample_gcloud() {
    loading('show');
    const selectElement = document.getElementById('tts_ggcloud_voice_name');
    if (selectElement) {
        if (selectElement.value) {
            playAudio('https://cloud.google.com/static/text-to-speech/docs/audio/' + selectElement.value + '.wav');
        } else {
            show_message('Cần chọn 1 giọng đọc để nghe thử');
        }
    } else {
        showMessagePHP('Không tìm thấy dữu liệu thẻ select với id=tts_ggcloud_voice_name', 3);
    }
    loading('hide');
}

//Cập nhật bảng mã màu vào thẻ input
// Thiết lập giá trị ban đầu cho các thẻ input khi tải trang
window.onload = function() {
    // Thiết lập màu cho thẻ đầu tiên
    setColorPickerValue('color_led_think', 'led_think');
    // Thiết lập màu cho thẻ thứ hai
    setColorPickerValue('color_led_mutex', 'led_mute');
};

// Hàm thiết lập màu cho các thẻ colorPicker dựa trên giá trị colorCodeInput
function setColorPickerValue(colorPickerId, colorCodeInputId) {
        const initialColor = document.getElementById(colorCodeInputId).value;
        document.getElementById(colorPickerId).value = '#' + initialColor;
    }

// Hàm cập nhật mã màu vào thẻ input
function updateColorCode_input(colorPickerId, colorCodeInputId) {
    // Lấy mã màu từ input color và bỏ dấu '#' ở đầu
    const selectedColor = document.getElementById(colorPickerId).value.substring(1);
    // Cập nhật giá trị của thẻ input với mã màu đã chọn (không có '#')
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
						show_message("<center class='text-success'>Xác thực Thành Công</center><br/>- " +responseData.data.tts_voice.sample_text);
                    } else {
						loading('hide');
                        show_message("Xác thực thất bại: " + responseData + "<br/>Kiểm tra lại tài khoản hoặc mật khẩu");
                    }
                } catch (e) {
					loading('hide');
                    show_message("Lỗi xử lý phản hồi json từ server:" +e);
                }
            } else {
				loading('hide');
				show_message("Lỗi kết nối: " +xhr.status+ "<br/>Kiểm tra lại tài khoản hoặc mật khẩu");
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
    ws.onopen = function() {
        show_message('Kết nối WebSocket thành công tới Server: ' + url);
        const sessionId = Math.random().toString(36).substring(2, 10);
        ws.send("VBot_Tester_Socket_Connect_" + sessionId);
        ws.close();
    };
    ws.onerror = function(error) {
        show_message("Lỗi kết nối tới Socket Server Streaming: " + error);
    };
    ws.onclose = function(event) {
        //console.log("Kết nối WebSocket đã đóng:", event.code, event.reason);
    };
}