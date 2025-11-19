<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';
$URL_Address = dirname($Current_URL);
$parsedUrl = parse_url($Github_Repo_Vbot);
$pathParts = explode('/', trim($parsedUrl['path'], '/'));
$git_username = $pathParts[0];
$git_repository = $pathParts[1];
?>
<script src="assets/vendor/apexcharts/apexcharts.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/chart.js/chart.umd.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/echarts/echarts.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/quill/quill.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/tinymce/tinymce.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/php-email-form/validate.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/jquery/jquery-3.5.1.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/popper/popper.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/hls/hls.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/js/main.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/js/VBot.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script>
    //Xóa File theo path
    function deleteFile(filePath, langg = "No") {
        if (!confirm("Bạn có chắc chắn muốn xóa file: '" + filePath.substring(filePath.lastIndexOf('/') + 1) + "' này không?")) {
            return;
        }
        loading("show");
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'includes/php_ajax/Del_file_path.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            loading("hide");
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    showMessagePHP(response.message, 3);
                } else {
                    show_message("<center>" + response.message + "</center>");
                }
                //Tải lại dữ liệu hotword ở Config.json theo lang nếu có giá trị
                if (langg === "vi") {
                    loadConfigHotword("vi")
                } else if (langg === "eng") {
                    loadConfigHotword("eng")
                } else if (langg === "snowboy") {
                    loadConfigHotword("snowboy")
                } else if (langg === "wakeup_reply") {
                    loadWakeupReply();
                } else if (langg === "scan_Music_Local") {
                    list_audio_show_path('scan_Music_Local')
                } else if (langg === "Vbot_Backup_Program") {
                    if (document.getElementById("show_all_file_folder_Backup_Program")) {
                        show_all_file_in_directory('<?php echo $HTML_VBot_Offline . '/' . $Backup_Dir_Save_VBot; ?>', 'Tệp Sao Lưu Chương Trình Trên Hệ Thống', 'show_all_file_folder_Backup_Program');
                    } else if (document.getElementById("show_all_file_folder_Backup_web_interface")) {
                        show_all_file_in_directory('<?php echo $HTML_VBot_Offline . '/' . $Backup_Dir_Save_Web; ?>', 'Tệp Sao Lưu Giao Diện Trên Hệ Thống', 'show_all_file_folder_Backup_web_interface');
                    }
                } else if (langg === "scan_Audio_Startup") {
                    list_audio_show_path('scan_Audio_Startup')
                } else if (langg === "media_player_search") {
                    if (document.getElementById("local-tab")) {
                        //Nếu trong Dom có ID local-tab thì sẽ chạy
                        media_player_search();
                    } else if (document.getElementById("select_cache_media")) {
                        //Hoặc nếu có id "select_cache_media"trong DOM thì sẽ chạy
                        media_player_search("Local");
                    }
                }
            } else {
                show_message("<center>Có lỗi xảy ra khi xóa file.</center>");
            }
        };
        xhr.send('filePath=' + encodeURIComponent(filePath));
    }
    //Hàm tải xuống file theo đường dẫn
    function downloadFile(filePath) {
        var link = document.createElement('a');
        link.href = 'includes/php_ajax/Download_file_path.php?file=' + encodeURIComponent(filePath);
        link.target = '_blank';
        link.download = filePath.substring(filePath.lastIndexOf('/') + 1); // Lấy tên file từ đường dẫn
        link.style.display = 'none'; // Ẩn liên kết
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    //Hiển thị tất cả các file có trong thư mục show ra tên file, đường dẫn, thời gian tạo, kích thước tệp
    function show_all_file_in_directory(directory_path, source_backup, resultDiv_Id) {
        loading("show");
        var xhr = new XMLHttpRequest();
        var url = 'includes/php_ajax/Show_file_path.php?show_all_file&directory_path=' + directory_path;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    loading("hide");
                    var response = JSON.parse(xhr.responseText);
                    var resultDiv_show_all_File = document.getElementById(resultDiv_Id); // Sử dụng ID được truyền vào
                    if (!resultDiv_show_all_File) {
                        showMessagePHP('Không tìm thấy phần tử có id là: ' + resultDiv_Id + ' để hiển thị kết quả.');
                        return;
                    }
                    if (response.success) {
                        showMessagePHP(response.message);
                        //console.log(response);
                        // Tạo bảng để hiển thị thông tin tệp
                        var table = '<table class="table table-bordered border-primary">';
                        table += '<tr><th colspan="5" class="text-primary" style="text-align: center; vertical-align: middle;">' + source_backup + '</th></tr>';
                        table += '<tr><th style="text-align: center; vertical-align: middle;">STT</th><th style="text-align: center; vertical-align: middle;">Tên tệp</th><th style="text-align: center; vertical-align: middle;">Thời gian tạo</th><th style="text-align: center; vertical-align: middle;">Kích thước</th><th style="text-align: center; vertical-align: middle;">Hành động</th></tr>';
                        response.data.forEach(function(file, index) {
                            table += '<tr>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>'; // STT
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.name + '</td>'; // Tên tệp
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.created_at + '</td>'; // Thời gian tạo
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.size + '</td>'; // Kích thước
                            table += '<td style="text-align: center; vertical-align: middle;">';
                            table += '<form method="POST" action=""><button type="submit" onclick="return confirmRestore(\'Bạn có chắc chắn muốn khôi phục dữ liệu từ bản sao lưu trên hệ thống: ' + file.name + '\')" name="Restore_Backup" value="' + file.path + '" class="btn btn-primary" title="Khôi phục dữ liệu: ' + file.name + '"><i class="bi bi-arrow-counterclockwise" title="Khôi phục dữ liệu: ' + file.name + '"></i></button> </form> ';
                            table += ' <button type="button" class="btn btn-success" title="Xem cấu trúc bên trong tệp: ' + file.name + '" onclick="read_file_backup(\'' + file.path + '\')"><i class="bi bi-eye"></i></button> ';
                            table += ' <button type="button" class="btn btn-warning" title="Tải xuống file: ' + file.name + '" onclick="downloadFile(\'' + file.path + '\')"><i class="bi bi-download"></i></button> ';
                            table += ' <button type="button" class="btn btn-danger" onclick="deleteFile(\'' + file.path + '\', \'Vbot_Backup_Program\')"><i class="bi bi-trash"></i></button></td>';
                            table += '</tr>';
                        });
                        table += '</table>';
                        resultDiv_show_all_File.innerHTML = table;
                    } else {
                        show_message(response.message);
                    }
                } else {
                    loading("hide");
                    show_message('Có lỗi xảy ra: ' + xhr.status);
                }
            }
        };
        xhr.send();
    }

    //Google Cloud Hiển thị tất cả các file có trong thư mục show ra tên file, đường dẫn, thời gian tạo, kích thước tệp
    function gcloud_scan(folder_name, source_backup, resultDiv_Id) {
        loading("show");
        var xhr = new XMLHttpRequest();
        var url = 'includes/php_ajax/GCloud_Act.php?Scan&Folder_Name=' + folder_name;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    loading("hide");
                    var response = JSON.parse(xhr.responseText);
                    var resultDiv_show_all_File = document.getElementById(resultDiv_Id); // Sử dụng ID được truyền vào
                    if (!resultDiv_show_all_File) {
                        showMessagePHP('Không tìm thấy phần tử có id là: ' + resultDiv_Id + ' để hiển thị kết quả.');
                        return;
                    }
                    if (response.success) {
                        showMessagePHP(response.message, 3);
                        //console.log(response);
                        // Tạo bảng để hiển thị thông tin tệp
                        var table = '<table class="table table-bordered border-primary">';
                        table += '<tr><th colspan="5" class="text-primary"><center>' + source_backup + '</center></th></tr>';
                        table += '<tr><th><center>STT</center></th><th><center>Tên tệp</center></th><th><center>Thời gian tạo</center></th><th><center>Kích thước</center></th><th><center>Hành động</center></th></tr>';
                        response.data.forEach(function(file, index) {
                            table += '<tr>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>'; // STT
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.name + '</td>'; // Tên tệp
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.created_at + '</td>'; // Thời gian tạo
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.size + '</td>'; // Kích thước
                            table += '<td style="text-align: center; vertical-align: middle;"><form method="POST" action=""><button type="submit" onclick="return confirmRestore(\'Bạn có chắc chắn muốn khôi phục dữ liệu từ bản sao lưu trên Google Cloud Drive: ' + file.name + '\')" name="Restore_Backup" value="' + file.url_share + '" class="btn btn-success" title="Khôi phục dữ liệu: ' + file.name + '"><i class="bi bi-arrow-counterclockwise" title="Khôi phục dữ liệu: ' + file.name + '"></i></button> </form>';
                            table += '<a href="' + file.url_share + '" target="_bank" title="Mở  trong tab mới: ' + file.name + '"> <button type="button" class="btn btn-success" title="Mở trong tab mới: ' + file.name + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
                            table += ' <button type="button" class="btn btn-danger" onclick="deleteFile_gcloud(\'' + file.id + '\', \'' + file.name + '\', \'' + folder_name + '\', \'Tệp Sao Lưu Chương Trình Trên Google Cloud Drive\', \'' + resultDiv_Id + '\')"><i class="bi bi-trash"></i></button> </td>';
                            table += '</tr>';
                        });

                        table += '</table>';
                        resultDiv_show_all_File.innerHTML = table;
                    } else {
                        show_message(response.message);
                    }
                } else {
                    loading("hide");
                    show_message('Có lỗi xảy ra: ' + xhr.status);
                }
            }
        };
        xhr.send();
    }

    //Xóa tệp theo ID trên Google Cloud
    function deleteFile_gcloud(gcloud_id_file, gcloud_file_name, gcloud_folder_name, source_backup_name, div_resultDiv_Id) {
        if (!confirm("Bạn có chắc chắn muốn xóa file: '" + gcloud_file_name + "' Trên Google Cloud Drive không?")) {
            return;
        }
        loading("show");
        var xhr = new XMLHttpRequest();
        var url = 'includes/php_ajax/GCloud_Act.php?Delete&id_file=' + gcloud_id_file;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    loading("hide");
                    var response = JSON.parse(xhr.responseText);
                    //console.log(response);
                    if (response.success) {
                        showMessagePHP(response.message, 3);
                        //Nếu trong Dom có ID của biến div_resultDiv_Id thì sẽ chạy
                        if (document.getElementById(div_resultDiv_Id)) {
                            gcloud_scan(gcloud_folder_name, source_backup_name, div_resultDiv_Id);
                        }
                    } else {
                        show_message(response.message);
                    }
                } else {
                    loading("hide");
                    show_message('Có lỗi xảy ra: ' + xhr.status);
                }
            }
        };
        xhr.send();
    }

    //Đọc dữ liệu file theo path
    function read_loadFile(path) {
        var url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=' + encodeURIComponent(path);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    // Hiển thị thông báo
                    document.getElementById('message_LoadConfigJson').textContent = response.message_LoadConfigJson;
                    // Hiển thị dữ liệu
                    var codeElement = document.getElementById('code_config');
                    if (response.success) {
                        if (typeof response.data === 'object') {
                            codeElement.textContent = JSON.stringify(response.data, null, 2);
                            Prism.highlightElement(codeElement);
                        } else {
                            codeElement.textContent = response.data;
                            codeElement.className = 'language-txt';
                        }
                    } else {
                        show_message('Không có dữ liệu');
                    }
                } catch (e) {
                    show_message('Lỗi xử lý dữ liệu: ' + e);
                }
            } else {
                show_message('Lỗi tải dữ liệu: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            show_message("Lỗi kết nối");
        };
        xhr.send();
    }

    //Đọc dữ liệu cấu trúc bên trong file backup theo path
    function read_file_backup(path_backup_file) {
        loading('show');
        var url = 'includes/php_ajax/Show_file_path.php?read_file_backup&file=' + encodeURIComponent(path_backup_file);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var fileName = path_backup_file.split('/').pop();
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        loading('hide');
                        // Tạo bảng để hiển thị thông tin tệp
                        var table = '<table class="table table-bordered border-primary">';
                        table += '<tr><th colspan="3"  class="text-success"><center>Cấu Trúc Tệp: ' + fileName + '</center></th></tr>';
                        table += '<tr><th><center>STT</center></th><th><center>Tên tệp</center></th><th><center>Hành động</center></th></tr>';
                        // Duyệt qua danh sách các tệp trong response.data
                        response.data.forEach(function(file, index) {
                            table += '<tr>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>';
                            table += '<td style="vertical-align: middle;"><font color=blue>' + file + '</font></td>';
                            table += '<td style="text-align: center; vertical-align: middle;">';
                            // Hành động: Bạn có thể thêm các nút hoặc liên kết hành động tại đây
                            table += '<button type="button" class="btn btn-success" onclick="read_files_in_backup(\'' + path_backup_file + '\', \'' + file + '\')" title="Xem nội dung tệp tin: \'' + file + '\'"><i class="bi bi-eye"></i> Xem</button>';
                            table += '</td>';
                            table += '</tr>';
                        });
                        table += '</table>';
                        if (document.getElementById('show_all_file_folder_Backup_Program')) {
                            document.getElementById('show_all_file_folder_Backup_Program').innerHTML = table;
                        } else if (document.getElementById('show_all_file_folder_Backup_web_interface')) {
                            document.getElementById('show_all_file_folder_Backup_web_interface').innerHTML = table;
                        }
                    } else {
                        loading('hide');
                        show_message(response.message);
                    }
                } catch (e) {
                    loading('hide');
                    show_message('Lỗi xử lý dữ liệu: ' + e.message);
                }
            } else {
                loading('hide');
                show_message('Lỗi tải dữ liệu: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            show_message("Lỗi kết nối. Vui lòng thử lại sau.");
        };
        xhr.send();
    }

    //Đọc dữ liệu cấu trúc bên trong file backup theo path
    function read_files_in_backup(file_path, file_name) {
        loading('show');
        var url = 'includes/php_ajax/Show_file_path.php?read_files_in_backup&file_path=' + encodeURIComponent(file_path) + '&file_name=' + encodeURIComponent(file_name);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        loading('hide');
                        if (file_name.endsWith('.json')) {
                            document.getElementById('modal-body-content').textContent = JSON.stringify(response.data, null, 2);
                        } else {
                            var fileContent = response.data.replace(/\\r/g, '').replace(/\\n/g, '\n');
                            var modalContentElement = document.getElementById('modal-body-content');
                            modalContentElement.textContent = fileContent;
                            modalContentElement.className = 'language-yaml';
                            Prism.highlightElement(modalContentElement);
                        }
                        $('#responseModal_read_files_in_backup').modal('show');
                    } else {
                        loading('hide');
                        show_message(response.message);
                    }
                } catch (e) {
                    loading('hide');
                    show_message('Lỗi xử lý dữ liệu: ' + e.message);
                }
            } else {
                loading('hide');
                show_message('Lỗi tải dữ liệu: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            show_message("Lỗi kết nối. Vui lòng thử lại sau.");
        };
        xhr.send();
    }
    //tìm lại mật khẩu
    function forgotPassword() {
        loading("show");
        var email = document.getElementById("forgotPassword_email").value;
        if (/\s/.test(email)) {
            show_message('Email không được phép chứa khoảng trống hoặc dấu cách!');
            loading("hide");
            return false;
        }
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "Login.php?forgot_password&mail=" + encodeURIComponent(email), true);
        xhr.onreadystatechange = function() {
            loading("hide");
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    show_message("Thành công, mật khẩu của bạn là: <b>" + response.message + "</b>");
                } else {
                    show_message("Lỗi: " + response.message + "<hr/>- Bạn có thể thay đổi/tìm lại mật khẩu thủ công bằng cách truy cập giá trị <b>Config.json->contact_info->user_login->user_password</b>");
                }
            }
        };
        xhr.send();
    }

    //Gửi lệnh và thự thi, lệnh được encode dưới dạng base64 tránh ký tự không cho phép
    function VBot_Command(b64_encode) {
        if (!confirm("Bạn có chắc chắn muốn thực thi lệnh:\n$:> " + atob(b64_encode))) {
            return;
        }
        loading('show');
        var xhr = new XMLHttpRequest();
        xhr.open("GET", 'includes/php_ajax/Check_Connection.php?VBot_CMD&Command=' + encodeURIComponent(b64_encode));
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        loading('hide');
                        var result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            var formattedData = result.data.replace(/\n/g, "<br/>");
                            show_message("<font color=blue>" + result.message + "</font><br/><center><b>Dữ Liệu Trả Về</b></center><hr/><font color=green>" + formattedData + "</font>");
                        } else {
                            show_message("Yêu cầu không thành công: " + result.message);
                        }
                    } catch (e) {
                        loading('hide');
                        show_message("Lỗi khi phân tích phản hồi JSON: " + e);
                    }
                } else {
                    loading('hide');
                    show_message("Yêu cầu không thành công với mã trạng thái: " + xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            loading('hide');
            show_message("Lỗi khi gửi yêu cầu tới server");
        };
        xhr.send();
    }

    //Tải lên file âm thanh theo giấ trị được chỉ định key_path
    function upload_File(key_path) {
        loading("show");
        var fileInput = document.getElementById(key_path);
        // Lấy tất cả các file
        var files = fileInput.files;
        var formData = new FormData();
        if (files.length > 0) {
            for (var i = 0; i < files.length; i++) {
                formData.append('fileUpload[]', files[i]);
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'includes/php_ajax/Upload_file_path.php?' + encodeURIComponent(key_path));
            xhr.onload = function() {
                loading("hide");
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (document.getElementById("local-tab")) {
                            if (typeof media_player_search === 'function') {
                                media_player_search();
                            }
                        } else if (document.getElementById("select_cache_media")) {
                            //Hoặc nếu có id "select_cache_media"trong DOM thì sẽ chạy
                            //Tab index.php
                            media_player_search("Local");
                        } else if (document.getElementById("show_mp3_music_local")) {
                            //Tab Config.php
                            list_audio_show_path('scan_Music_Local')
                        }
                        show_message(response.messages.join('<br/>'));
                    } catch (e) {
                        show_message('Lỗi phân tích JSON: ' + e.message);
                    }
                } else {
                    show_message('Lỗi: ' + xhr.status);
                }
            };
            xhr.onerror = function() {
                show_message('Có lỗi xảy ra khi gửi yêu cầu');
            };
            xhr.send(formData);
        } else {
            loading("hide");
            show_message('Vui lòng chọn ít nhất một file');
        }
    }

    //Điều khiển volume theo bước
    function control_volume(action) {
        loading("show");
        var data = JSON.stringify({
            "type": 2,
            "data": "volume",
            "action": action
        });
        var xhr = new XMLHttpRequest();
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                loading("hide");
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            showMessagePHP(response.message, 5);
                        } else {
                            show_message("Error: " + response.message);
                        }
                    } catch (e) {
                        show_message("Lỗi phân tích phản hồi JSON: " + e.message);
                    }
                } else {
                    show_message("Không thể kết nối đến API, Vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa, API: http status" + xhr.status);
                }
            }
        });
        xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(data);
    }

    //Điều khiển media player
    function control_media(action) {
        loading("show");
        var data = JSON.stringify({
            "type": 1,
            "data": "media_control",
            "action": action
        });
        var xhr = new XMLHttpRequest();
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                loading("hide");
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showMessagePHP(response.message, 5);
                        } else {
                            show_message("Lỗi: " + response.message);
                        }
                    } catch (e) {
                        show_message("Lỗi phân tích phản hồi JSON: " + e.message);
                    }
                } else {
                    show_message("Lỗi kết nối, Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API: http status" + xhr.status);
                }
            }
        });
        xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(data);
    }

    // Hàm lấy Audio Link
    function getAudioLink_newspaper(url_media) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "includes/php_ajax/Media_Player_Search.php?Get_Link_NewsPaper&url=" + encodeURIComponent(url_media));
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var responseData = JSON.parse(xhr.responseText);
                        if (responseData.success) {
                            resolve(responseData.data.audio_link);
                        } else {
                            reject(responseData.message);
                        }
                    } catch (e) {
                        reject("Không thể phân tích dữ liệu JSON.");
                    }
                } else {
                    reject("Có lỗi khi lấy dữ liệu. Mã trạng thái HTTP: " + xhr.status);
                }
            };
            xhr.onerror = function() {
                reject("Có lỗi xảy ra trong quá trình yêu cầu.");
            };
            xhr.send();
        });
    }

    //Hàm để phát nhạc (Media Player)
    function send_Media_Play_API(url_media, name_media = "", url_cover = "<?php echo $URL_Address; ?>/assets/img/icon_audio_local.png", media_source = "N/A") {
        loading("show");
        //Kiểm tra nếu URL bắt đầu với các domain cần tìm
        if (url_media.startsWith("https://baomoi.com/") || url_media.startsWith("https://tienphong.vn/") || url_media.startsWith("https://vietnamnet.vn/") || url_media.includes("24h.com.vn")) {
            getAudioLink_newspaper(url_media)
                .then(function(audioLink) {
                    url_media = audioLink;
                    startMediaPlayer(url_media, name_media, url_cover, media_source);
                })
                .catch(function(error) {
                    showMessagePHP("Có lỗi: " + error);
                    loading("hide");
                });

        } else {
            startMediaPlayer(url_media, name_media, url_cover, media_source);
        }
    }

    //Hàm khởi tạo phát nhạc
    function startMediaPlayer(url_media, name_media, url_cover, media_source) {
        var data = JSON.stringify({
            "type": 1,
            "data": "media_control",
            "action": "play",
            "media_link": url_media,
            "media_cover": url_cover,
            "media_name": name_media,
            "media_player_source": media_source
        });
        var xhr = new XMLHttpRequest();
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === XMLHttpRequest.DONE) {
                loading("hide");
                if (this.status === 200) {
                    try {
                        var data = JSON.parse(this.responseText);
                        showMessagePHP(data.message, 7);
                    } catch (e) {
                        show_message("Lỗi phân tích JSON: " + e);
                    }
                } else {
                    show_message("Không thể kết nối đến API, Vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa, Lỗi HTTP: " + this.status, this.statusText);
                }
            }
        });
        xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(data);
    }

    //Get link Zingmp3
    function get_ZingMP3_Link(zing_id, zing_name, zing_cover, zing_artist) {
        loading("show");
        var xhr = new XMLHttpRequest();
        var url = 'includes/php_ajax/Media_Player_Search.php?ZingMP3_GetLink&Zing_ID=' + zing_id;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success == true) {
                        startMediaPlayer(data.url, zing_name + ' - ' + zing_artist, zing_cover, 'ZingMP3');
                    } else {
                        loading("hide");
                        show_message('Yêu cầu không thành công, Không lấy được link Player hoặc bạn có thể thử lại');
                    }
                } catch (e) {
                    loading("hide");
                    show_message('Lỗi phân tích cú pháp JSON:' + e);
                }
            } else if (xhr.readyState === 4) {
                loading("hide");
                show_message('Lỗi tìm nạp dữ liệu:' + xhr.status);
            }
        };
        xhr.send();
    }

    //Get link play Youtube
    function get_Youtube_Link(youtube_id, youtube_name = null, youtube_cover = null) {
        if (youtube_id === null || youtube_id === "N/A") {
            show_message("Lỗi, không lấy được ID hoặc ID của Video Youtube này không hợp lệ");
            return;
        }
        loading("show");
        var xhr = new XMLHttpRequest();
        var url = 'includes/php_ajax/Media_Player_Search.php?GetLink_Youtube&Youtube_ID=' + youtube_id;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success == true) {
                        if (youtube_name == null) {
                            youtube_name = data.data.title;
                        }
                        if (youtube_cover == null) {
                            youtube_cover = data.data.cover;
                        }
                        startMediaPlayer(data.data.dlink, youtube_name, youtube_cover, 'Youtube');
                    } else {
                        loading("hide");
                        show_message('Yêu cầu không thành công, Không lấy được link Player hoặc bạn có thể thử lại');
                    }
                } catch (e) {
                    loading("hide");
                    show_message('Lỗi phân tích cú pháp JSON: ' + e);
                }
            } else if (xhr.readyState === 4) {
                loading("hide");
                show_message('Lỗi tìm nạp dữ liệu:' + xhr.status);
            }
        };
        xhr.send();
    }

    //Thay đổi kiểu hiển thị Log đầu ra và xóa Log API
    function change_og_display_style(action, dataKey, actionValue = false) {
        if (actionValue) {
            var data = JSON.stringify({
                "type": 2,
                "data": "logs",
                "action": action,
                "value": dataKey
            });
            var xhr = new XMLHttpRequest();
            xhr.addEventListener("readystatechange", function() {
                if (this.readyState === 4) {
                    try {
                        if (this.status === 0) {
                            show_message('Lỗi: Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API, và Bot đã hoạt động chưa');
                            return;
                        } else if (this.status !== 200) {
                            show_message('Lỗi: Mã trạng thái HTTP ' + this.status);
                            return;
                        }
                        var response = JSON.parse(this.responseText);
                        if (response.success) {
                            showMessagePHP(response.message, 5);
                        } else {
                            show_message('Lỗi: ' + response.message);
                        }
                        const deleteLogCheckbox = document.getElementById('delete_log_api');
                        if (deleteLogCheckbox) {
                            deleteLogCheckbox.checked = false;
                        }
                    } catch (error) {
                        show_message('Đã xảy ra lỗi trong quá trình xử lý: ' + error.message);
                    }
                }
            });
            xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.send(data);
        }
    }

    //Hiển thị dữ liệu cache Zingmp3
    function cacheZingMP3() {
        var inputElement = document.getElementById("tim_kiem_bai_hat_all");
        if (inputElement) {
            inputElement.style.display = "";
        }
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'includes/php_ajax/Media_Player_Search.php?Cache_ZingMP3', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var zingDataDiv = document.getElementById('show_list_ZingMP3');
                // Nếu không tìm thấy phần tử 'show_list_ZingMP3'
                if (!zingDataDiv) {
                    // Sử dụng phần tử với ID 'tableContainer'
                    zingDataDiv = document.getElementById('tableContainer');
                }
                try {
                    zingDataDiv.innerHTML = '';
                    if (!document.getElementById("song_name_value")) {
                        zingDataDiv.innerHTML += '<div class="input-group mb-3">' +
                            '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát" title="Nhập tên bài hát cần tìm kiếm" value="">' +
                            '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
                            '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'ZingMP3\')"><i class="bi bi-search"></i></button>' +
                            '<button type="button" class="btn btn-primary border-success" onclick="cacheZingMP3()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
                        // Lắng nghe sự kiện thay đổi trong input tìm kiếm bài hát
                        setTimeout(function() {
                            if (document.getElementById('song_name_value')) {
                                document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);
                            }
                        }, 0);
                    }
                    var data = JSON.parse(xhr.responseText);
                    // Kiểm tra xem dữ liệu có phải là mảng và có phần tử không
                    if (Array.isArray(data) && data.length > 0) {
                        zingDataDiv.innerHTML += 'Xóa dữ liệu Cache: <button class="btn btn-danger" title="Xóa dữ liệu cache ZingMP3" onclick="cache_delete(\'ZingMP3\')"><i class="bi bi-trash"></i> Xóa</button><br/>';
                        // Duyệt qua dữ liệu và tạo danh sách các bài hát
                        data.forEach(function(cache_ZING) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + cache_ZING.thumb + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + cache_ZING.name + '</font></p>';
                            fileInfo += '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color=green>' + cache_ZING.artist + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (cache_ZING.duration || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success" title="Phát: ' + cache_ZING.name + '" onclick="get_ZingMP3_Link(\'' + cache_ZING.id + '\', \'' + cache_ZING.name + '\', \'' + cache_ZING.thumb + '\', \'' + cache_ZING.artist + '\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + cache_ZING.name + '" onclick="addToPlaylist(\'' + cache_ZING.name + '\', \'' + cache_ZING.thumb + '\', \'' + cache_ZING.id + '\', \'' + (cache_ZING.duration || 'N/A') + '\', null, \'ZingMP3\', \'' + cache_ZING.id + '\', null, \'' + cache_ZING.artist + '\')"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ' <button class="btn btn-warning" title="Tải Xuống: ' + cache_ZING.name + '" onclick="dowload_ZingMP3_ID(\'' + cache_ZING.id + '\', \'' + cache_ZING.name + '\')"><i class="bi bi-download"></i></button>';
                            fileInfo += ' <button class="btn btn-info" title="Tải Vào Thư Mục Local: ' + cache_ZING.name + '" onclick="download_zingMp3_to_local(\'' + cache_ZING.id + '\', \'' + cache_ZING.name + '\')"><i class="bi bi-save2"></i></button>';
                            fileInfo += '</div></div>';
                            zingDataDiv.innerHTML += fileInfo;
                            adjustContainerStyle_tableContainer();
                        });
                    } else {
                        zingDataDiv.innerHTML += '<center>Không có dữ liệu ZingMP3 từ bộ nhớ cache</center>';
                    }
                } catch (e) {
                    show_message('Lỗi phân tích cache ZingMP3 JSON: ' + e);
                }
            } else {
                show_message('Không thể tải dữ liệu cache ZingMP3. Trạng thái: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            show_message('Lỗi khi thực hiện yêu cầu cache ZingMP3');
        };
        xhr.send();
    }

    //Hiển thị dữ liệu cache NhacCuaTui
    function cacheNhacCuaTui() {
        var inputElement = document.getElementById("tim_kiem_bai_hat_all");
        if (inputElement) {
            inputElement.style.display = "";
        }
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'includes/php_ajax/Media_Player_Search.php?Cache_NhacCuaTui', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var nhaccuatuiDataDiv = document.getElementById('show_list_NhacCuaTui');
                if (!nhaccuatuiDataDiv) {
                    nhaccuatuiDataDiv = document.getElementById('tableContainer');
                }
                try {
                    nhaccuatuiDataDiv.innerHTML = '';
                    if (!document.getElementById("song_name_value")) {
                        nhaccuatuiDataDiv.innerHTML += '<div class="input-group mb-3">' +
                            '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát" title="Nhập tên bài hát cần tìm kiếm" value="">' +
                            '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
                            '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'NhacCuaTui\')"><i class="bi bi-search"></i></button>' +
                            '<button type="button" class="btn btn-primary border-success" onclick="cacheNhacCuaTui()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
                        //Lắng nghe sự kiện thay đổi trong input tìm kiếm bài hát
                        setTimeout(function() {
                            if (document.getElementById('song_name_value')) {
                                document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);
                            }
                        }, 0);
                    }
                    var data = JSON.parse(xhr.responseText);
                    //Kiểm tra xem dữ liệu có phải là mảng và có phần tử không
                    if (Array.isArray(data) && data.length > 0) {
                        nhaccuatuiDataDiv.innerHTML += 'Xóa dữ liệu Cache: <button class="btn btn-danger" title="Xóa dữ liệu cache NhacCuaTui" onclick="cache_delete(\'NhacCuaTui\')"><i class="bi bi-trash"></i> Xóa</button><br/>';
                        //Duyệt qua dữ liệu và tạo danh sách các bài hát
                        data.forEach(function(cache_nct) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + cache_nct.thumb + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + cache_nct.name + '</font></p>';
                            fileInfo += '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color=green>' + cache_nct.artist + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (cache_nct.duration || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success" title="Phát: ' + cache_nct.name + '" onclick="startMediaPlayer(\'' + cache_nct.url + '\', \'' + cache_nct.name + '\', \'' + cache_nct.thumb + '\', \'NhacCuaTui\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + cache_nct.name + '" onclick="addToPlaylist(\'' + cache_nct.name + '\', \'' + cache_nct.thumb + '\', \'' + cache_nct.url + '\', \'' + (cache_nct.duration || 'N/A') + '\', null, \'NhacCuaTui\', \'' + cache_nct.url + '\', null, \'' + cache_nct.artist + '\')"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ` <button class="btn btn-warning" title="Tải Xuống: ${cache_nct.name}" onclick="downloadFile('${cache_nct.url.substring(0, cache_nct.url.indexOf('.mp3') + 4)}')"><i class="bi bi-download"></i></button>`;
                            fileInfo += ' <button class="btn btn-info" title="Tải Vào Thư Mục Local: ' + cache_nct.name + '" onclick="download_Link_url_to_local(\'' + cache_nct.url + '\', \'' + cache_nct.name + '\')"><i class="bi bi-save2"></i></button>';
                            fileInfo += '</div></div>';
                            nhaccuatuiDataDiv.innerHTML += fileInfo;
                            adjustContainerStyle_tableContainer();
                        });
                    } else {
                        nhaccuatuiDataDiv.innerHTML += '<center>Không có dữ liệu NhacCuaTui từ bộ nhớ cache</center>';
                    }
                } catch (e) {
                    show_message('Lỗi phân tích cache NhacCuaTui JSON: ' + e);
                }
            } else {
                show_message('Không thể tải dữ liệu cache NhacCuaTui. Trạng thái: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            show_message('Lỗi khi thực hiện yêu cầu cache NhacCuaTui');
        };
        xhr.send();
    }

    //Hiển thị dữ liệu cache PodCast nếu có
    function cachePodCast() {
        var inputElement = document.getElementById("tim_kiem_bai_hat_all");
        if (inputElement) {
            inputElement.style.display = "";
        }
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'includes/php_ajax/Media_Player_Search.php?Cache_PodCast', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var fileListDiv = document.getElementById('show_list_PodCast');
                // Nếu không tìm thấy phần tử 'show_list_PodCast'
                if (!fileListDiv) {
                    // Sử dụng phần tử với ID 'tableContainer'
                    fileListDiv = document.getElementById('tableContainer');
                }
                try {
                    fileListDiv.innerHTML = '';
                    if (!document.getElementById("song_name_value")) {
                        fileListDiv.innerHTML += '<div class="input-group mb-3">' +
                            '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát" title="Nhập tên bài hát cần tìm kiếm" value="">' +
                            '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
                            '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'PodCast\')"><i class="bi bi-search"></i></button>' +
                            '<button type="button" class="btn btn-primary border-success" onclick="cachePodCast()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
                        setTimeout(function() {
                            if (document.getElementById('song_name_value')) {
                                document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);
                            }
                        }, 0);
                    }
                    var data = JSON.parse(xhr.responseText);
                    if (Array.isArray(data.data) && data.data.length > 0) {
                        fileListDiv.innerHTML += 'Xóa dữ liệu Cache: <button class="btn btn-danger" title="Xóa dữ liệu cache PodCast" onclick="cache_delete(\'PodCast\')"><i class="bi bi-trash"></i> Xóa</button><br/>';
                        data.data.forEach(function(podcast) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + podcast.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + podcast.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (podcast.duration || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thể Loại: <font color=green>' + (podcast.description || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success" title="Phát: ' + podcast.title + '" onclick="startMediaPlayer(\'' + podcast.audio + '\', \'' + podcast.title + '\', \'' + podcast.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + podcast.title + '" onclick="addToPlaylist(\'' + podcast.title + '\', \'' + podcast.cover + '\', \'' + podcast.audio + '\', \'' + (podcast.duration || 'N/A') + '\', \'' + (podcast.description || 'N/A') + '\', \'PodCast\', \'' + podcast.audio + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ' <button class="btn btn-warning" title="Tải Xuống: ' + podcast.title + '" onclick="download_AUDIO_URL(\'' + podcast.audio + '\', \'' + podcast.title + '\')"><i class="bi bi-download"></i></button>';
                            fileInfo += ' <button class="btn btn-danger" title="Tải Vào Thư Mục Local: ' + podcast.title + '" onclick="download_Link_url_to_local(\'' + podcast.audio + '\', \'' + podcast.title + '\')"><i class="bi bi-save2"></i></button>';
                            fileInfo += ' <a href="' + podcast.audio + '" target="_blank"><button class="btn btn-info" title="Mở trong tab mới: ' + podcast.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
                            fileInfo += '</div></div>';
                            fileListDiv.innerHTML += fileInfo;
                            adjustContainerStyle_tableContainer();
                        });
                    } else {
                        fileListDiv.innerHTML += '<center>Không có dữ liệu PodCast từ bộ nhớ cache</center>';
                    }
                } catch (e) {
                    show_message('Lỗi phân tích cache PodCast JSON: ' + e);
                }
            } else {
                show_message('Không thể tải dữ liệu cache PodCast. Trạng thái: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            show_message('Lỗi khi thực hiện yêu cầu cache PodCast');
        };
        xhr.send();
    }

    //Lấy và hiển thị dữ liệu cache báo, tin tức
    function cache_NewsPaper() {
        var inputElement = document.getElementById("tim_kiem_bai_hat_all");
        if (inputElement) {
            inputElement.style.display = "none";
        }
        loading('show');
        var xhr = new XMLHttpRequest();
        var url = "includes/php_ajax/Media_Player_Search.php?Cache_NewsPaper";
        xhr.open("GET", url, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                loading('hide');
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (Array.isArray(response.data) && response.data.length > 0) {
                        // Lấy phần tử DOM để hiển thị danh sách newspaper
                        var fileListDiv = document.getElementById('show_list_news_paper');
                        if (!fileListDiv) {
                            // Nếu không tồn tại, thay thế bằng phần tử với ID 'tableContainer' dành cho index.php
                            fileListDiv = document.getElementById('tableContainer');
                            document.getElementById('tableContainer').style.display = '';
                            document.getElementById('tableContainer').style.height = '400px';
                            document.getElementById('tableContainer').style.overflowY = 'auto';
                        }
                        fileListDiv.innerHTML = '';
                        fileListDiv.innerHTML += '<b>Phát tất cả:</b> <button class="btn btn-success" title="Phát toàn bộ" onclick="play_playlist_json_path(\'<?php echo $directory_path; ?>/includes/cache/<?php echo $Config['media_player']['news_paper']['newspaper_file_name']; ?>\')"><i class="bi bi-play-circle"></i></button>';
                        response.data.forEach(function(news_paper) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + news_paper.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tiêu Đề: <font color=green>' + news_paper.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Gian Tạo: <font color=green>' + (news_paper.publish_time || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (news_paper.duration || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Nguồn: <font color=green>' + (news_paper.source || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success" title="Phát: ' + news_paper.title + '" onclick="send_Media_Play_API(\'' + news_paper.audio + '\', \'' + news_paper.title + '\', \'' + news_paper.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                            //fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + news_paper.title + '" onclick="addToPlaylist(\'' + news_paper.title + '\', \'' + news_paper.cover + '\', \'' + news_paper.audio + '\', \'' + (news_paper.duration || 'N/A') + '\', \'' + (news_paper.description || 'N/A') + '\', \''+news_paper.source+'\', \'' + news_paper.audio + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ' <a href="' + news_paper.audio + '" target="_blank"><button class="btn btn-info" title="Mở trong tab mới: ' + news_paper.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
                            fileInfo += '</div></div>';
                            fileListDiv.innerHTML += fileInfo;
                        });
                    } else {
                        show_message('<center>Không có dữ liệu Báo, Tin Tức từ bộ nhớ cache</center>');
                    }
                } catch (e) {
                    show_message('Lỗi phân tích JSON: ' + e);
                }
            } else {
                loading('hide');
                show_message('Lỗi yêu cầu API: ' + xhr.status + ", " + xhr.statusText);
            }
        };
        xhr.onerror = function() {
            loading('hide');
            show_message('Lỗi kết nối tới server');
        };
        xhr.send();
    }

    //hiển thị dữ liệu cache Youtube
    function cacheYoutube() {
        var inputElement = document.getElementById("tim_kiem_bai_hat_all");
        if (inputElement) {
            inputElement.style.display = "";
        }
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'includes/php_ajax/Media_Player_Search.php?Cache_Youtube', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var fileListDiv = document.getElementById('show_list_Youtube');
                if (!fileListDiv) {
                    // Sử dụng phần tử với ID 'show_list_Youtube'
                    fileListDiv = document.getElementById('tableContainer');
                }
                try {
                    fileListDiv.innerHTML = '';
                    if (!document.getElementById("song_name_value")) {
                        fileListDiv.innerHTML += '<div class="input-group mb-3">' +
                            '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát hoặc nhập url/link Youtube" title="Nhập tên bài hát cần tìm kiếm hoặc nhập url/link Youtube" value="">' +
                            '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
                            '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'Youtube\')"><i class="bi bi-search"></i></button>' +
                            '<button type="button" class="btn btn-primary border-success" onclick="cacheYoutube()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
                        setTimeout(function() {
                            if (document.getElementById('song_name_value')) {
                                document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);
                            }
                        }, 0);
                    }
                    var data = JSON.parse(xhr.responseText);
                    if (Array.isArray(data.data) && data.data.length > 0) {
                        fileListDiv.innerHTML += 'Xóa dữ liệu Cache: <button class="btn btn-danger" title="Xóa dữ liệu cache Youtube" onclick="cache_delete(\'Youtube\')"><i class="bi bi-trash"></i> Xóa</button><br/>';
                        data.data.forEach(function(youtube) {
                            var description = youtube.description.length > 70 ? youtube.description.substring(0, 70) + '...' : youtube.description;
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + youtube.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + youtube.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Kênh: <font color=green>' + (youtube.channelTitle || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (youtube.duration || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Mô tả: <font color=green>' + (description || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success" title="Phát: ' + youtube.title + '" onclick="get_Youtube_Link(\'' + youtube.id + '\', \'' + youtube.title + '\', \'' + youtube.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + youtube.title + '" onclick="addToPlaylist(\'' + youtube.title + '\', \'' + youtube.cover + '\', \'https://www.youtube.com/watch?v=' + youtube.id + '\', \'' + (youtube.duration || 'N/A') + '\', \'' + (description || 'N/A') + '\', \'Youtube\', \'' + youtube.id + '\', \'' + (youtube.channelTitle || 'N/A') + '\', null)"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ' <a href="https://www.youtube.com/watch?v=' + youtube.id + '" target="_bank"><button class="btn btn-info" title="Mở trong tab mới: ' + youtube.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
                            fileInfo += '</div></div>';
                            // Thêm thông tin vào phần tử danh sách
                            fileListDiv.innerHTML += fileInfo;
                            adjustContainerStyle_tableContainer();
                        });
                    } else {
                        fileListDiv.innerHTML += '<center>Không có dữ liệu Youtube từ bộ nhớ cache</center>';
                    }
                } catch (e) {
                    show_message('Lỗi phân tích cache Youtube JSON: ' + e);
                }
            } else {
                show_message('Không thể tải dữ liệu cache Youtube. Trạng thái: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            show_message('Lỗi khi thực hiện yêu cầu cache Youtube');
        };
        xhr.send();
    }

    //Thêm bài hát vào danh sách phát playlist
    function addToPlaylist(title, cover, audio, duration, description, source, id, channelTitle, artist) {
        loading("show");
        var xhr = new XMLHttpRequest();
        var url = "includes/php_ajax/Media_Player_Search.php?playlist_ADD";
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    loading("hide");
                    //console.log(xhr.responseText);
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showMessagePHP("Đã thêm " + title + " vào PlayList thành công!");
                    } else {
                        show_message("Lỗi: " + response.message);
                    }
                } else {
                    loading("hide");
                    show_message("Lỗi kết nối với server.");
                }
            }
        };
        var params = "title=" + encodeURIComponent(title) +
            "&cover=" + encodeURIComponent(cover) +
            "&audio=" + encodeURIComponent(audio) +
            "&duration=" + encodeURIComponent(duration) +
            "&description=" + encodeURIComponent(description) +
            "&source=" + encodeURIComponent(source) +
            "&id=" + encodeURIComponent(id) +
            "&channelTitle=" + encodeURIComponent(channelTitle) +
            "&artist=" + encodeURIComponent(artist);
        xhr.send(params);
    }

    //Xóa toàn bộ playlist hoặc 1 số bài
    function deleteFromPlaylist(action, idsList) {
        if (action === "delete_all") {
            if (!confirm("Bạn có chắc chắn muốn xóa tất cả bài hát trong PlayList?")) {
                return;
            }
        }
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "includes/php_ajax/Media_Player_Search.php?playlist_DELETE", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        const data = 'action=' + encodeURIComponent(action) + '&ids_list=' + encodeURIComponent(idsList);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                // Nếu hàm loadPlayList() tồn tại, gọi hàm đó
                if (typeof loadPlayList === "function") {
                    loadPlayList();
                } else {
                    cachePlayList();
                }
            }
        };
        xhr.send(data);
    }

    //Tìm kiếm Media theo Nguồn phát được chọn
    function media_player_search(select_name = null) {
        loading("show");
        var searchInputElement = document.getElementById('song_name_value');
        let searchInput = null;
        if (searchInputElement) {
            searchInput = searchInputElement.value.trim();
        } else {
            searchInput = null;
        }
        if (!select_name) {
            const buttons = document.querySelectorAll('#select_source_media_music .nav-link');
            buttons.forEach(button => {
                if (button.classList.contains('active')) {
                    select_name = button.getAttribute('name');
                }
            });
        }
        if (!select_name) {
            loading("hide");
            show_message('Chọn nguồn nhạc không hợp lệ, vui lòng chọn nguồn khác.');
            return;
        }
        if (select_name === "PlayList") {
            loading("hide");
            show_message('Chưa cập nhật chức năng tìm kiếm bài hát ở PlayList');
            return;
        }
        if (searchInput === '' && (select_name !== 'Local' && select_name !== 'Radio')) {
            loading("hide");
            show_message('Cần nhập tên bài hát để tìm kiếm.');
            return;
        }
        var xhr = new XMLHttpRequest();
        var url;
        switch (select_name) {
            case 'Local':
                url = 'includes/php_ajax/Media_Player_Search.php?Local';
                break;
            case 'ZingMP3':
                url = 'includes/php_ajax/Media_Player_Search.php?ZingMP3_Search&SongName=' + searchInput;
                break;
            case 'NhacCuaTui':
                url = 'includes/php_ajax/Media_Player_Search.php?NhacCuaTui_Search&SongName=' + searchInput;
                break;
            case 'PodCast':
                url = 'includes/php_ajax/Media_Player_Search.php?podcast_Search&PodCastName=' + searchInput + '&Limit=1';
                break;
            case 'Youtube':
                url = 'includes/php_ajax/Media_Player_Search.php?Youtube_Search&Name=' + searchInput + '&Limit=20';
                break;
            case 'Radio':
                url = 'includes/php_ajax/Media_Player_Search.php?Radio';
                break;
            default:
                loading("hide");
                show_message('Chọn nguồn nhạc không hợp lệ, nguồn nhạc ' + select_name + ' không cho phép tìm kiếm');
                return;
        }
        xhr.open('GET', url, true);
        xhr.onload = function() {
            loading("hide");
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    switch (select_name) {
                        case 'Local':
                            processLocalData(data);
                            break;
                        case 'ZingMP3':
                            processZingMP3Data(data);
                            break;
                        case 'NhacCuaTui':
                            processNhacCuaTuiData(data);
                            break;
                        case 'PodCast':
                            processPodCastData(data);
                            break;
                        case 'Youtube':
                            processYoutubeData(data);
                            break;
                        case 'Radio':
                            processRadioData(data);
                            break;
                    }
                } catch (e) {
                    loading("hide");
                    show_message('Lỗi phân tích JSON: ' + e);
                }
            } else {
                loading("hide");
                show_message('Lỗi yêu cầu: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            loading("hide");
            show_message('Lỗi mạng');
        };
        xhr.send();
    }

    // Hàm xử lý dữ liệu Radio
    function processRadioData(data_media_Radio) {
        var fileListDiv = document.getElementById('show_list_Radio');
        if (!fileListDiv) {
            fileListDiv = document.getElementById('tableContainer');
        }
        fileListDiv.innerHTML = '';
        if (Array.isArray(data_media_Radio)) {
            data_media_Radio.forEach(function(radio) {
                var name = radio.name;
                var cover = "<?php echo $URL_Address; ?>/assets/img/radio_icon.png";
                var size = radio.size;
                var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                fileInfo += '<img src="' + cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên đài: <font color=green>' + radio.name + '</font></p>';
                fileInfo += '<button class="btn btn-success" title="Phát đài radio: ' + radio.name + '" onclick="startMediaPlayer(\'' + radio.full_path + '\', \'' + radio.name + '\', \'' + cover + '\', \'Radio\')"><i class="bi bi-play-circle"></i></button>';
                fileInfo += '</div></div>';
                fileListDiv.innerHTML += fileInfo;
                adjustContainerStyle_tableContainer();
            });
        } else {
            show_message('Dữ liệu trả về không hợp lệ.');
        }
    }

    //Lấy và hiển thị dữ liệu báo, tin tức
    function fetchData_NewsPaper(newspaper_link) {
        loading('show');
        var xhr = new XMLHttpRequest();
        var url = "includes/php_ajax/Media_Player_Search.php?newspaper&link=" + newspaper_link;
        xhr.open("GET", url, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                loading('hide');
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showMessagePHP(response.message, 3);
                        var fileListDiv = document.getElementById('show_list_news_paper');
                        if (!fileListDiv) {
                            fileListDiv = document.getElementById('tableContainer');
                            document.getElementById('tableContainer').style.display = '';
                            document.getElementById('tableContainer').style.height = '400px';
                            document.getElementById('tableContainer').style.overflowY = 'auto';
                        }
                        fileListDiv.innerHTML = '';
                        fileListDiv.innerHTML += '<b>Phát tất cả:</b> <button class="btn btn-success" title="Phát toàn bộ" onclick="play_playlist_json_path(\'<?php echo $directory_path; ?>/includes/cache/<?php echo $Config['media_player']['news_paper']['newspaper_file_name']; ?>\')"><i class="bi bi-play-circle"></i></button>';
                        response.data.forEach(function(news_paper) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + news_paper.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tiêu Đề: <font color=green>' + news_paper.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Gian Tạo: <font color=green>' + (news_paper.publish_time || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (news_paper.duration || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Nguồn: <font color=green>' + (news_paper.source || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success" title="Phát: ' + news_paper.title + '" onclick="send_Media_Play_API(\'' + news_paper.audio + '\', \'' + news_paper.title + '\', \'' + news_paper.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                            //fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + news_paper.title + '" onclick="addToPlaylist(\'' + news_paper.title + '\', \'' + news_paper.cover + '\', \'' + news_paper.audio + '\', \'' + (news_paper.duration || 'N/A') + '\', \'' + (news_paper.description || 'N/A') + '\', \''+news_paper.source+'\', \'' + news_paper.audio + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ' <a href="' + news_paper.audio + '" target="_blank"><button class="btn btn-info" title="Mở trong tab mới: ' + news_paper.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
                            fileInfo += '</div></div>';
                            fileListDiv.innerHTML += fileInfo;
                        });
                    } else {
                        show_message('Lỗi: ' + response.message);
                    }
                } catch (e) {
                    show_message('Lỗi phân tích JSON: ' + e);
                }
            } else {
                loading('hide');
                show_message('Lỗi yêu cầu API: ' + xhr.status + ", " + xhr.statusText);
            }
        };
        xhr.onerror = function() {
            loading('hide');
            show_message('Lỗi kết nối tới server');
        };
        xhr.send();
    }

    //Xóa dữ liệu cache bài hát theo nguồn nhạc
    function cache_delete(source_cache) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'includes/php_ajax/Media_Player_Search.php?cache_delete=' + source_cache, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        showMessagePHP(data.message, 5);
                        if (source_cache === "ZingMP3") {
                            cacheZingMP3();
                        } else if (source_cache === "Youtube") {
                            cacheYoutube();
                        } else if (source_cache === "PodCast") {
                            cachePodCast();
                        }
                    } else {
                        show_message(data.message);
                    }
                } catch (e) {
                    show_message('Lỗi phân tích JSON: ' + e.message);
                }
            } else {
                show_message('Không thể tải dữ liệu. Trạng thái: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            show_message('Lỗi khi thực hiện yêu cầu đến máy chủ.');
        };
        xhr.send();
    }

    //Xử lý Play, next, prev phaylist
    function playlist_media_control(action_control = null) {
        loading("show");
        let data;
        if (action_control === 'next') {
            data = JSON.stringify({
                "type": 1,
                "data": "media_control",
                "action": "play_list",
                "control": "next"
            });
        } else if (action_control === 'prev') {
            data = JSON.stringify({
                "type": 1,
                "data": "media_control",
                "action": "play_list",
                "control": "prev"
            });
        } else if (action_control === 'local') {
            data = JSON.stringify({
                "type": 1,
                "data": "media_control",
                "action": "play_list",
                "source_playlist": "local"
            });
        } else {
            data = JSON.stringify({
                "type": 1,
                "data": "media_control",
                "action": "play_list",
                "source_playlist": true
            });
        }
        const xhr = new XMLHttpRequest();
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                loading("hide");
                try {
                    const text = this.responseText.trim();
                    // Kiểm tra xem dữ liệu trả về có phải JSON không
                    if (text.startsWith("{") || text.startsWith("[")) {
                        const response = JSON.parse(text);
                        if (response.success) {
                            showMessagePHP(response.message, 3);
                        } else {
                            show_message("Lỗi xảy ra: " + JSON.stringify(response));
                        }
                    } else {
                        show_message("Không thể kết nối đến API, Vui lòng kiểm tra lại API (Bật/Tắt) và VBot đã được chạy hay chưa, Error: Dữ liệu phản hồi không phải JSON");
                    }
                } catch (error) {
                    show_message("Có lỗi xảy ra khi xử lý phản hồi: " + error);
                }
            }
        });
        xhr.onerror = function() {
            loading("hide");
            show_message("Yêu cầu thất bại, Chương trình VBot không được khởi chạy hoặc API VBot chưa được bật");
        };
        xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(data);
    }

    //xử lý Nghe thử các file âm thanh
    function playAudio(filePath) {
        loading("show");

        function getMimeType(extension) {
            const mimeTypes = {
                'mp3': 'audio/mpeg',
                'wav': 'audio/wav',
                'ogg': 'audio/ogg',
                'aac': 'audio/aac',
                'flac': 'audio/flac',
            };
            return mimeTypes[extension.toLowerCase()] || 'application/octet-stream';
        }
        const audioPlayer = document.getElementById('audioPlayer');
        if (filePath.startsWith('http')) {
            loading("hide");
            if (filePath.toLowerCase().includes("m3u8")) {
                playHLS(filePath);
            } else {
                audioPlayer.src = filePath;
                audioPlayer.load();
                audioPlayer.play().catch(function(error) {
                    show_message('Lỗi khi phát âm thanh: ' + error.message);
                });
            }
            return;
        }
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'includes/php_ajax/Show_file_path.php?audio_b64&path=' + encodeURIComponent(filePath), true);
        xhr.responseType = 'text';
        xhr.onload = function() {
            loading("hide");
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const base64Audio = response.data.base64Content;
                    const mimeType = getMimeType(response.data.fileExtension);
                    audioPlayer.src = 'data:' + mimeType + ';base64,' + base64Audio;
                    audioPlayer.load();
                    audioPlayer.play();
                } else {
                    show_message('Lỗi khi tìm nạp âm thanh: ' + response.error);
                }
            }
        };
        xhr.onerror = function() {
            loading("hide");
            show_message('Yêu cầu phát âm thanh không thành công');
        };
        xhr.send();
    }
</script>
<!-- Chatbot -->
<script>
    //Dữ liệu nguồn chatbot của thẻ select
    const select_Element_api_chatbox = document.getElementById('source_chatbot_api');
    //gửi yêu cầu POST Chatbox và xử lý phản hồi
    function sendRequest(message) {
        const selectedValue_api_chatbox = select_Element_api_chatbox.value;
        const selectedOption_api_chatbox = select_Element_api_chatbox.options[select_Element_api_chatbox.selectedIndex];
        const fullName_VBot_api_chatbox = selectedOption_api_chatbox.getAttribute('data-full_name_chatbot_api');
        console.log(fullName_VBot_api_chatbox);
        // Kiểm tra cả thẻ select và giá trị cùng lúc
        if (!select_Element_api_chatbox || !selectedValue_api_chatbox || selectedValue_api_chatbox.trim() === '') {
            selectedValue_api_chatbox = "<?php echo $URL_API_VBOT; ?>";
        }
        var data = JSON.stringify({
            "type": 3,
            "data": "main_processing",
            "action": "chatbot",
            "value": message
        });
        var xhr = new XMLHttpRequest();
        var chatbox = document.getElementById('chatbox');
        var typingIndicator = document.createElement('div');
        var timeout;
        typingIndicator.className = 'typing-indicator';
        typingIndicator.innerHTML = 'Đang xử lý...';
        chatbox.appendChild(typingIndicator);
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                clearTimeout(timeout);
                typingIndicator.remove();
                if (this.status === 200) {
                    var response = JSON.parse(this.responseText);
                    stopAllAudio();
                    var botMessageHTML = '';
                    if (response.success) {
                        var audioUrl = response.message;
                        var audioPattern = /^TTS_Audio.*\.(mp3|ogg|wav)$/i;
                        if (audioPattern.test(audioUrl)) {
                            var audioExtension = audioUrl.split('.').pop();
                            var fullAudioUrl = 'includes/php_ajax/Show_file_path.php?TTS_Audio=' + encodeURIComponent(audioUrl);
                            botMessageHTML =
                                '<div class="message bot-message">' +
                                '<div class="message-time">' + getCurrentTime() + ' [' + fullName_VBot_api_chatbox + ']</div>' +
                                '    <div class="audio-container">' +
                                '         <audio controls>' +
                                '            <source src="' + fullAudioUrl + '" type="audio/' + audioExtension + '">' +
                                '            Your browser does not support the audio element.' +
                                '        </audio>' +
                                '    </div>' +
                                '</div>';
                        } else {
                            botMessageHTML =
                                '<div class="message bot-message">' +
                                '<div class="message-time">' + getCurrentTime() + ' [' + fullName_VBot_api_chatbox + ']</div>' +
                                '    <div>' + response.message + '</div>' +
                                '</div>';
                        }
                        document.getElementById('chatbox').innerHTML += botMessageHTML;
                        saveMessage('bot', response.message, fullName_VBot_api_chatbox);
                        if (flag_mic_recording && isAutoClick_btn_send_msg) {
                            isAutoClick_btn_send_msg = false;
                            Recording_STT();
                        }
                    } else {
                        playSound_default('/assets/sound/default/dong.mp3');
                        var msg_error = "Có lỗi xảy ra. Vui lòng thử lại";
                        var errorMessageHTML =
                            '<div class="message bot-message">' +
                            '<div class="message-time">' + getCurrentTime() + ' [' + fullName_VBot_api_chatbox + ']</div>' +
                            '<div>' + msg_error + '</div>' +
                            '</div>';
                        document.getElementById('chatbox').innerHTML += errorMessageHTML;
                        saveMessage('bot', msg_error, fullName_VBot_api_chatbox);
                        if (flag_mic_recording && isAutoClick_btn_send_msg) {
                            isAutoClick_btn_send_msg = false;
                            Recording_STT();
                        }
                    }
                    setTimeout(scrollToBottom, 100);
                } else {
                    flag_mic_recording = false;
                    isAutoClick_btn_send_msg = false;
                    playSound_default('/assets/sound/default/dong.mp3');
                    var msg_error = "Có vẻ VBot đang không phản hồi, vui lòng thử lại.";
                    var failureMessageHTML =
                        '<div class="message bot-message">' +
                        '<div class="message-time">' + getCurrentTime() + ' [' + fullName_VBot_api_chatbox + ']</div>' +
                        '    <div>' + msg_error + '</div>' +
                        '</div>';
                    document.getElementById('chatbox').innerHTML += failureMessageHTML;
                    saveMessage('bot', msg_error, fullName_VBot_api_chatbox);
                }
            }
        });
        //Nếu là chatbot trên thiết bị hiện tại
        if (selectedValue_api_chatbox === '/vbot_api_external/' || selectedValue_api_chatbox === '<?php echo $URL_API_VBOT; ?>') {
            xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.send(data);
            timeout = setTimeout(function() {
                typingIndicator.innerHTML = 'Vui lòng chờ thêm...';
                timeout = setTimeout(function() {
                    var msg_error = "Có vẻ bot đang không phản hồi, vui lòng thử lại";
                    typingIndicator.innerHTML = msg_error;
                    saveMessage('bot', msg_error, fullName_VBot_api_chatbox);
                }, 13000);
            }, 7000);

        }
        //Nếu chatbot ánh xạ tới thiết bị khác
        else {
            const url = 'includes/php_ajax/Check_Connection.php?vbot_chatbox&ip_port=' + encodeURIComponent(selectedValue_api_chatbox) + '&text=' + encodeURIComponent(message);
            xhr.open("GET", url);
            xhr.send();
            timeout = setTimeout(function() {
                typingIndicator.innerHTML = 'Vui lòng chờ thêm...';
                timeout = setTimeout(function() {
                    var msg_error = "Có vẻ bot đang không phản hồi, vui lòng thử lại";
                    typingIndicator.innerHTML = msg_error;
                    saveMessage('bot', msg_error, fullName_VBot_api_chatbox);
                }, 13000);
            }, 7000);

        }
    }

    // Xử lý sự kiện khi nhấn nút gửi
    const sendButton = document.getElementById('send_button_chatbox');
    if (sendButton) {
        sendButton.addEventListener('click', function() {
            // Đặt lại cờ nếu không phải nhấn tự động
            if (!isAutoClick_btn_send_msg) {
                flag_mic_recording = false;
                isAutoClick_btn_send_msg = false;
            }
            const selectedOption_api_chatbox = select_Element_api_chatbox.options[select_Element_api_chatbox.selectedIndex];
            const fullName_VBot_api_chatbox = selectedOption_api_chatbox.getAttribute('data-full_name_chatbot_api');
            const userInput = document.getElementById('user_input_chatbox');
            const message = userInput?.value.trim();
            if (message) {
                const userMessageHTML =
                    '<div class="message user-message">' +
                    '<div class="message-time">' + getCurrentTime() + ' [' + fullName_VBot_api_chatbox + ']</div>' +
                    '    <div>' + message + '</div>' +
                    '</div>';
                const chatbox = document.getElementById('chatbox');
                if (chatbox) {
                    chatbox.innerHTML += userMessageHTML;
                }
                saveMessage('user', message, fullName_VBot_api_chatbox);
                sendRequest(message);
                if (userInput) userInput.value = '';
                setTimeout(scrollToBottom, 100);
            }
        });
    }

    //Xử lý sự kiện nhấn Enter
    const inputChatbox = document.getElementById('user_input_chatbox');
    const sendBtnChatbox = document.getElementById('send_button_chatbox');
    if (inputChatbox && sendBtnChatbox) {
        inputChatbox.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                flag_mic_recording = false;
                isAutoClick_btn_send_msg = false;
                sendBtnChatbox.click();
            }
        });
    }

    //thu âm từ Microphone
    function Recording_STT(stop_rec = 'vbot') {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            show_message('Trình duyệt của bạn không hỗ trợ nhận diện giọng nói');
            return;
        }
        const recognition = new SpeechRecognition();
        if (stop_rec === 'stop') {
            flag_mic_recording = false;
            isAutoClick_btn_send_msg = false;
            recognition.stop();
        }
        recognition.lang = 'vi-VN'; //ngôn ngữ tiếng việt
        recognition.interimResults = true; //Hiển thị kết quả tạm thời
        recognition.continuous = false; //tự động phát hiện kết thúc câu
        const inputField = document.getElementById('user_input_chatbox');
        const sendButton = document.getElementById('send_button_chatbox');
        const animationDiv = document.getElementById('Recording_STT_mic_animation');
        playSound_default('/assets/sound/default/ding.mp3');
        //Nếu 10 giây không có giọng nói thì tắt Mic
        let timeoutId = setTimeout(() => {
            recognition.stop();
        }, 10000);
        recognition.onresult = function(event) {
            flag_mic_recording = true;
            isAutoClick_btn_send_msg = false;
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            inputField.value = transcript;
            clearTimeout(timeoutId);
        };
        recognition.onerror = function(event) {
            animationDiv.style.display = 'none';
            if (event.error === 'no-speech' || event.error === 'aborted') {
                flag_mic_recording = false;
                isAutoClick_btn_send_msg = false;
                showMessagePHP('Không có giọng nói được truyền vào', 6);
            } else if (event.error === 'not-allowed') {
                flag_mic_recording = false;
                isAutoClick_btn_send_msg = false;
                show_message('Sử Dụng Microphone Thất bại, Kết nối WebUI VBot cần được truy cập bằng (Doamin, Tên Miền) và bảo mật bằng https, Điều này khiến trình duyệt chặn truy cập vào Microphone vì kết nối không phải là https: ' + event.error);
            } else {
                flag_mic_recording = false;
                isAutoClick_btn_send_msg = false;
                show_message('Lỗi nhận diện giọng nói: ' + event.error);
            }
            clearTimeout(timeoutId);
        };
        recognition.onend = function() {
            animationDiv.style.display = 'none';
            if (sendButton && flag_mic_recording) {
                isAutoClick_btn_send_msg = true;
                sendButton.click();
            } else {
                flag_mic_recording = false;
                isAutoClick_btn_send_msg = false;
                playSound_default('/assets/sound/default/dong.mp3');
                if (!sendButton) {
                    show_message('Lỗi, Không tìm thấy nút gửi tin nhắn: send_button_chatbox');
                }
            }
            clearTimeout(timeoutId);
        };
        animationDiv.style.display = 'flex';
        flag_mic_recording = true;
        isAutoClick_btn_send_msg = false;
        recognition.start();
    }

    //Hiển thị thiết bị chạy VBot vào thẻ select chatbot
    function fetchAndPopulateDevices_chatbot() {
        const selectElement = document.getElementById('source_chatbot_api');
        if (!selectElement) {
            return;
        }
        const url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $directory_path . "/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json"; ?>';
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (!data.success) {
                        return;
                    }
                    if (!data.data || !Array.isArray(data.data)) {
                        return;
                    }
                    const serverIp = '<?php echo $serverIp; ?>';
                    while (selectElement.options.length > 1) {
                        selectElement.remove(1);
                    }
                    data.data.forEach(device => {
                        if (device.ip_address !== serverIp) {
                            const option = document.createElement('option');
                            option.value = 'http://' + device.ip_address + ':' + device.port_api + '/';
                            option.text = device.user_name;
                            option.setAttribute('data-full_name_chatbot_api', device.user_name);
                            selectElement.appendChild(option);
                        }
                    });
                    check_Device_Status_VBot_Server('on');
                } catch (e) {
                    showMessagePHP('Lỗi: Không thể phân tích JSON - ' + e.message, 5);
                }
            } else {
                showMessagePHP('Lỗi lấy dữ liệu các Loa VBot trong cùng lớp mạng: HTTP status ' + xhr.status, 5);
            }
        };
        xhr.onerror = function() {
            showMessagePHP('Lỗi khi gửi yêu cầu lấy dữ liệu các loa chạy VBot trong cùng lớp mạng', 5);
        };
        xhr.send();
    }

    // Hàm gửi yêu cầu tới Command.php
    function command_php(command_line, reload_page = null) {
        // Kiểm tra nếu command_line không có giá trị
        if (!command_line) {
            showMessagePHP('Vui lòng nhập lệnh hợp lệ để thực thi.');
            return;
        }
        loading('show');
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'Command.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                loading('hide');
                if (xhr.status === 200) {
                    showMessagePHP("Thao tác thành công", 5);
                    if (reload_page === true) {
                        location.reload();
                    }
                } else {
                    show_message('Lỗi: Không thể xử lý yêu cầu. Mã trạng thái:', xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            loading('hide');
            show_message('Lỗi: Không thể kết nối tới máy chủ.');
        };
        xhr.send(command_line + '=1');
    }

    //Gửi yêu cầu phát nhạc playlist bằng thông tin tệp json
    function play_playlist_json_path(url_json_file) {
        loading('show');
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "<?php echo $URL_API_VBOT; ?>", true);
        xhr.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({
            "type": 1,
            "data": "media_control",
            "action": "play_list",
            "source_playlist": "json",
            "json_file": url_json_file
        });
        xhr.onerror = function() {
            loading('hide');
            show_message("Lỗi kết nối: Không thể thực hiện yêu cầu.");
        };
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                loading('hide');
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        showMessagePHP("Thông báo: " + response.message, 3);
                    } else {
                        show_message("Lỗi: " + response.message);
                    }
                } catch (error) {
                    show_message("Lỗi xử lý JSON hoặc phản hồi không hợp lệ: " + error);
                }
            }
        });
        xhr.send(data);
    }

    //Kiểm tra và hiển thị thông báo cập nhật WEB UI
    function ui_check_update() {
        const localFileUrl_ui = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $HTML_VBot_Offline; ?>/Version.json';
        const remoteFileUrl_ui = 'https://api.github.com/repos/<?php echo $git_username; ?>/<?php echo $git_repository; ?>/contents/html/Version.json?ref=main';

        function fetchRemoteData(url, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        const decodedContent = atob(response.content);
                        const jsonData = JSON.parse(decodedContent);
                        callback(jsonData);
                    } catch (error) {
                        showMessagePHP("Lỗi khi phân tích dữ liệu JSON từ file remote: " + error, 3);
                    }
                } else {
                    showMessagePHP('Lỗi khi tải dữ liệu từ file remote: ' + xhr.statusText, 3);
                    callback(null);
                }
            };
            xhr.onerror = function() {
                showMessagePHP("Lỗi khi thực hiện yêu cầu kiểm tra cập nhật Giao Diện", 3);
                callback(null);
            };
            xhr.send();
        }

        function fetchLocalData(url, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        callback(data.data);
                    } catch (error) {
                        showMessagePHP('Lỗi khi phân tích dữ liệu tệp Version.json của giao diện: ' + error, 3);
                        callback(null);
                    }
                } else {
                    showMessagePHP('Lỗi khi tải dữ liệu từ file local: ' + xhr.statusText, 3);
                    callback(null);
                }
            };
            xhr.onerror = function() {
                showMessagePHP("Lỗi khi thực hiện yêu cầu XMLHttpRequest.");
                callback(null);
            };
            xhr.send();
        }

        function checkForUpdate() {
            fetchLocalData(localFileUrl_ui, function(localData_ui) {
                fetchRemoteData(remoteFileUrl_ui, function(remoteData_ui) {
                    if (localData_ui && remoteData_ui) {
                        if (localData_ui.releaseDate && remoteData_ui.releaseDate) {
                            if (localData_ui.releaseDate !== remoteData_ui.releaseDate) {
                                var li = document.createElement("li");
                                li.classList.add("notification-item");
                                li.innerHTML = '<a href="#"><font color="green"><i class="bi bi-box-arrow-in-up"></i></font></a>' +
                                    '<div>' +
                                    '<h4><font color="green">Cập Nhật Web UI</font></h4>' +
                                    '<p class="text-primary">Có phiên bản Giao Diện VBot mới: ' + remoteData_ui.releaseDate + '</p>' +
                                    '<a href="_Dashboard.php"><p class="text-danger">Kiểm Tra</p></a>' +
                                    '</div>';
                                document.querySelector('#notification').appendChild(li);
                                var countElement = document.querySelector('#number_notification');
                                var countElement_1 = document.querySelector('#number_notification_1');
                                var currentCount = parseInt(countElement.innerText) || 0;
                                var currentCount_1 = parseInt(countElement_1.innerText.replace(/[^0-9]/g, '')) || 0;
                                countElement.innerText = currentCount + 1;
                                countElement_1.innerHTML = "Bạn có <b>" + (currentCount_1 + 1) + "</b> thông báo mới";
                            }
                        }
                    }
                });
            });
        }
        checkForUpdate();
    }

    //Kiểm tra và hiển thị thông báo cập nhật chương trình VBot
    function vbot_check_update() {
        const localFileUrl_ui = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $VBot_Offline; ?>Version.json';
        const remoteFileUrl_ui = 'https://api.github.com/repos/<?php echo $git_username; ?>/<?php echo $git_repository; ?>/contents/Version.json?ref=main';

        function fetchRemoteData(url, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        const decodedContent = atob(response.content);
                        const jsonData = JSON.parse(decodedContent);
                        callback(jsonData);
                    } catch (error) {
                        showMessagePHP("Lỗi khi phân tích dữ liệu JSON từ file remote:" + error, 3);
                    }
                } else {
                    showMessagePHP('Lỗi khi tải dữ liệu từ file remote:' + xhr.statusText, 3);
                    callback(null);
                }
            };
            xhr.onerror = function() {
                showMessagePHP("Lỗi khi thực hiện yêu cầu kiểm tra cập nhật Chương Trình VBot", 3);
                callback(null);
            };
            xhr.send();
        }

        function fetchLocalData(url, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        callback(data.data);
                    } catch (error) {
                        showMessagePHP('Lỗi khi phân tích dữ liệu JSON từ file local:' + error, 3);
                        callback(null);
                    }
                } else {
                    showMessagePHP('Lỗi khi tải dữ liệu từ file local:' + xhr.statusText, 3);
                    callback(null);
                }
            };
            xhr.onerror = function() {
                showMessagePHP("Lỗi khi thực hiện yêu cầu XMLHttpRequest.", 3);
                callback(null);
            };
            xhr.send();
        }

        function checkForUpdate() {
            fetchLocalData(localFileUrl_ui, function(localData_ui) {
                fetchRemoteData(remoteFileUrl_ui, function(remoteData_ui) {
                    if (localData_ui && remoteData_ui) {
                        if (localData_ui.releaseDate && remoteData_ui.releaseDate) {
                            if (localData_ui.releaseDate !== remoteData_ui.releaseDate) {
                                var li = document.createElement("li");
                                li.classList.add("notification-item");
                                li.innerHTML = '<a href="#"><font color="green"><i class="bi bi-box-arrow-in-up"></i></font></a>' +
                                    '<div>' +
                                    '<h4><font color="green">Cập Nhật VBot</font></h4>' +
                                    '<p class="text-primary">Có phiên bản chương trình VBot mới: ' + remoteData_ui.releaseDate + '</p>' +
                                    '<a href="_Program.php"><p class="text-danger">Kiểm Tra</p></a>' +
                                    '</div>';
                                document.querySelector('#notification').appendChild(li);
                                var countElement = document.querySelector('#number_notification');
                                var countElement_1 = document.querySelector('#number_notification_1');
                                var currentCount = parseInt(countElement.innerText) || 0;
                                // Lấy giá trị từ thẻ <font> và chuyển sang số
                                var currentCount_1 = parseInt(countElement_1.innerText.replace(/[^0-9]/g, '')) || 0;
                                countElement.innerText = currentCount + 1;
                                countElement_1.innerHTML = "Bạn có <b>" + (currentCount_1 + 1) + "</b> thông báo mới";
                            }
                        }
                    }
                });
            });
        }
        checkForUpdate();
    }

    //Quét các thiết bị sử dụng VBot trong cùng lớp mạng
    function scan_VBot_Device() {
        loading('show');
        showMessagePHP('Đang tìm kiếm các thiết bị chạy VBot trong cùng lớp mạng Lan', 12);
        const url = "includes/php_ajax/Scanner.php?VBot_Device_Scaner";
        const xhr = new XMLHttpRequest();
        xhr.open("GET", url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                loading('hide');
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const data = response.data;
                            if (Array.isArray(data) && data.length > 0) {
                                data.sort(function(a, b) {
                                    const aHasData = a.ip_address && a.port_api && a.host_name && a.user_name;
                                    const bHasData = b.ip_address && b.port_api && b.host_name && b.user_name;
                                    if (aHasData && !bHasData) {
                                        return -1;
                                    } else if (!aHasData && bHasData) {
                                        return 1;
                                    }
                                    return 0;
                                });
                                let tableHTML =
                                    '<table class="table table-bordered border-primary" cellspacing="0" cellpadding="5">' +
                                    '<thead>' +
                                    '<tr>' +
                                    '<th id="th_device_name" style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th>' +
                                    '<th id="th_ip_address" style="text-align: center; vertical-align: middle;">Địa Chỉ IP</th>' +
                                    '<th id="th_port_api" style="text-align: center; vertical-align: middle;">Port API</th>' +
                                    '<th id="th_host_name" style="text-align: center; vertical-align: middle;">HostName</th>' +
                                    '<th id="th_host_name" style="text-align: center; vertical-align: middle;">Hành Động</th>' +
                                    '</tr>' +
                                    '</thead>' +
                                    '<tbody>';
                                data.forEach((device, index) => {
                                    const rowId = 'device_row_' + index;
                                    tableHTML +=
                                        '<tr id="' + rowId + '">' +
                                        '<td id="' + rowId + '_name" style="text-align: center; vertical-align: middle;"><b><p class="text-success">' + (device.user_name || '') + '</p></b></td>' +
                                        '<td id="' + rowId + '_ip" style="text-align: center; vertical-align: middle;"><b><a class="text-danger" href="http://' + (device.ip_address || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.ip_address || '') + '</a></b></td>' +
                                        '<td id="' + rowId + '_port" style="text-align: center; vertical-align: middle;"><b><a class="text-success" href="http://' + (device.ip_address || '') + ':' + (device.port_api || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.port_api || '') + '</a></b></td>' +
                                        '<td id="' + rowId + '_host" style="text-align: center; vertical-align: middle;"><b>' + (device.host_name || '') + '</b></td>' +
                                        '<td id="' + rowId + '_action" style="text-align: center; vertical-align: middle;">' +
                                        '<button class="btn btn-danger" title="Xóa ' + (device.ip_address || '') + '" onclick="delete_IP_VBot_Server(\'' + (device.ip_address || '') + '\')"><i class="bi bi-trash"></i></button>' +
                                        ' <button class="btn btn-primary" title="WebUI ' + (device.ip_address || '') + '" onclick="showIframeModal(\'' + (device.ip_address || '') + '\', \'' + (device.user_name || '') + '\')"><i class="bi bi-gear-wide-connected"></i></button>' +
                                        '</td>' +
                                        '</tr>';
                                });
                                tableHTML +=
                                    '</tbody>' +
                                    '</table>';
                                document.getElementById("vbot_Scan_devices").innerHTML = tableHTML;
                                check_Device_Status_VBot_Server();
                                fetchAndPopulateDevices_chatbot();
                            } else {
                                document.getElementById("vbot_Scan_devices").innerHTML = "Không tìm thấy thiết bị nào.";
                            }
                        } else {
                            show_message("Đã xảy ra lỗi: " + response.messager);
                        }
                    } catch (error) {
                        document.getElementById("vbot_Scan_devices").innerHTML = "Đã xảy ra lỗi khi xử lý dữ liệu: " + xhr.responseText;
                    }
                } else {
                    document.getElementById("vbot_Scan_devices").innerHTML = "Không thể kết nối tới máy chủ: " + xhr.status;
                }
            }
        };
        xhr.send();
    }

    //Kiểm tra trực tuyến các thiết bị Vot server trong mạng lan
    function check_Device_Status_VBot_Server(chatbox_click = 'off') {
        const table = document.getElementById("vbot_Scan_devices").querySelector("table");
        if (!table) {
            return;
        }
        const rows = table.querySelectorAll("tbody tr");
        rows.forEach((row, index) => {
            const ipCell = row.querySelector('#device_row_' + index + '_ip a');
            const portCell = row.querySelector('#device_row_' + index + '_port a');
            if (!ipCell || !portCell) {
                return;
            }
            const ip = ipCell.textContent;
            const port = portCell.textContent;
            const xhr = new XMLHttpRequest();
            const url = 'includes/php_ajax/Check_Connection.php?check_status_vbot_server_in_lan=true&ip=' + encodeURIComponent(ip) + '&port=' + encodeURIComponent(port);
            xhr.open('GET', url, true);
            xhr.onload = function() {
                let isOnline = false;
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        isOnline = response.success === true;
                    } catch (error) {
                        isOnline = false;
                    }
                }
                const td = row.querySelector('#device_row_' + index + '_name');
                const deviceName = td.querySelector('p') ? td.querySelector('p').textContent : '';
                const statusDot = isOnline ?
                    '<span style="color: green; font-size: 30px;" title="Thiết bị đang trực tuyến">●</span>' :
                    '<span style="color: red; font-size: 30px;" title="Thiết bị đang ngoại tuyến">●</span>';
                if (td) {
                    td.innerHTML = '<b>' + statusDot + ' <p class="text-success">' + deviceName + '</p></b>';
                }
                if (isOnline) {
                    showMessagePHP('<font color="green">Thiết bị: ' + ip + ' đang <b>trực tuyến</b></font>', 7);
                } else {
                    showMessagePHP('<font color="red">Thiết bị: ' + ip + ' đang <b>ngoại tuyến</b></font>', 7);
                }
            };
            xhr.onerror = function() {
                const td = row.querySelector('#device_row_' + index + '_name');
                const deviceName = td.querySelector('p') ? td.querySelector('p').textContent : '';
                const statusDot = '<span style="color: red; font-size: 30px;" title="Thiết bị đang ngoại tuyến">●</span>';
                if (td) {
                    td.innerHTML = '<b>' + statusDot + ' <p class="text-success">' + deviceName + '</p></b>';
                }
                showMessagePHP('<font color="red">Thiết bị: ' + ip + ' đang <b>ngoại tuyến</b></font>', 7);
            };
            xhr.send();
        });
    }

    //Lấy dữ liệu Các thiết bị chạy Vbot trong mạng lan đã được Scan
    function get_vbotScanDevices() {
        loading('show');
        const url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $directory_path . "/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json"; ?>';
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const jsonData = JSON.parse(xhr.responseText);
                    if (jsonData.success && Array.isArray(jsonData.data) && jsonData.data.length > 0) {
                        let tableHTML =
                            '<p class="card-title">Dữ liệu được tìm kiếm trước đó:</p>' +
                            '<table class="table table-bordered border-primary" cellspacing="0" cellpadding="5">' +
                            '<thead>' +
                            '<tr>' +
                            '<th id="th_device_name" style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th>' +
                            '<th id="th_ip_address" style="text-align: center; vertical-align: middle;">Địa Chỉ IP</th>' +
                            '<th id="th_port_api" style="text-align: center; vertical-align: middle;">Port API</th>' +
                            '<th id="th_host_name" style="text-align: center; vertical-align: middle;">HostName</th>' +
                            '<th id="th_host_name" style="text-align: center; vertical-align: middle;">Hành Động</th>' +
                            '</tr>' +
                            '</thead>' +
                            '<tbody>';
                        jsonData.data.forEach((device, index) => {
                            const rowId = 'device_row_' + index;
                            tableHTML +=
                                '<tr id="' + rowId + '">' +
                                '<td id="' + rowId + '_name" style="text-align: center; vertical-align: middle;"><b><p class="text-success">' + (device.user_name || '') + '</p></b></td>' +
                                '<td id="' + rowId + '_ip" style="text-align: center; vertical-align: middle;"><b><a class="text-danger" href="http://' + (device.ip_address || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.ip_address || '') + '</a></b></td>' +
                                '<td id="' + rowId + '_port" style="text-align: center; vertical-align: middle;"><b><a class="text-success" href="http://' + (device.ip_address || '') + ':' + (device.port_api || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.port_api || '') + '</a></b></td>' +
                                '<td id="' + rowId + '_host" style="text-align: center; vertical-align: middle;"><b>' + (device.host_name || '') + '</b></td>' +
                                '<td id="' + rowId + '_action" style="text-align: center; vertical-align: middle;">' +
                                '<button class="btn btn-danger" title="Xóa ' + (device.ip_address || '') + '" onclick="delete_IP_VBot_Server(\'' + (device.ip_address || '') + '\')"><i class="bi bi-trash"></i></button>' +
                                ' <button class="btn btn-primary" title="WebUI ' + (device.ip_address || '') + '" onclick="showIframeModal(\'' + (device.ip_address || '') + '\', \'' + (device.user_name || '') + '\')"><i class="bi bi-gear-wide-connected"></i></button>' +
                                '</td>' +
                                '</tr>';
                        });
                        tableHTML +=
                            '</tbody>' +
                            '</table>';
                        document.getElementById("vbot_Scan_devices").innerHTML = tableHTML;
                        check_Device_Status_VBot_Server();
                        loading('hide');
                    } else {
                        loading('hide');
                        document.getElementById("vbot_Scan_devices").innerHTML = "<center><h5 class='text-danger'>Không có thiết bị nào, nhấn vào QUÉT THIẾT BỊ để tìm kiếm</h5></center>";
                    }
                } catch (error) {
                    loading('hide');
                    document.getElementById("vbot_Scan_devices").innerHTML = "<center><h5 class='text-danger'>Lỗi khi phân tích dữ liệu: " + error.message + "</h5></center>";
                }
            } else {
                loading('hide');
                document.getElementById("vbot_Scan_devices").innerHTML = "<center><h5 class='text-danger'>Lỗi khi tải dữ liệu: Mã trạng thái " + xhr.status + "</h5></center>";
            }
        };
        xhr.onerror = function() {
            loading('hide');
            document.getElementById("vbot_Scan_devices").innerHTML = "<center><h5 class='text-danger'>Lỗi mạng khi tải dữ liệu</h5></center>";
        };
        xhr.send();
    }

    //Xóa dữ liệu đã lưu Các thiết bị chạy VBot Server Trong Lan
    function clearAllDevices_vbotScanDevices() {
        const url = 'includes/php_ajax/Scanner.php?Clean_VBot_Device_Scaner';
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    showMessagePHP(response.message, 3);
                    if (response.success) {
                        get_vbotScanDevices();
                    }
                } catch (error) {
                    showMessagePHP('Lỗi khi phân tích dữ liệu: ' + error.message, 3);
                }
            } else {
                showMessagePHP('Lỗi khi xóa dữ liệu: Mã trạng thái ' + xhr.status, 3);
            }
        };
        xhr.onerror = function() {
            showMessagePHP('Lỗi mạng khi xóa dữ liệu', 3);
        };
        xhr.send();
    }

    //Thêm thiết bị chạy VBot Server thủ công trong mạng Lan
    function add_IP_VBot_Server() {
        loading('show');
        const input = document.getElementById('add_ip_vbot_server');
        if (!input) {
            loading('hide');
            show_message('Không tìm thấy thẻ input có id: add_ip_vbot_server');
            return;
        }
        const ip = input.value.trim();
        const validIP = /^(http:\/\/)?192\.168\.\d{1,3}\.\d{1,3}$/.test(ip);
        if (!validIP) {
            loading('hide');
            show_message('Địa chỉ IP không hợp lệ. Chỉ chấp nhận địa chỉ bắt đầu bằng 192.168 hoặc http://192.168');
            return;
        }
        const cleanIP = ip.replace(/^http:\/\//, '');
        const url = 'includes/php_ajax/Check_Connection.php?add_ip_vbot_server&ip=' + encodeURIComponent(cleanIP);
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        loading('hide');
                        showMessagePHP('Thêm thiết bị thành công: ' + response.device.ip_address, 5);
                        get_vbotScanDevices();
                    } else {
                        loading('hide');
                        showMessagePHP('Thêm thiết bị thất bại: ' + response.error, 5);
                    }
                } catch (e) {
                    loading('hide');
                    showMessagePHP('Thêm thiết bị thất bại: ' + e, 5);
                }
            } else {
                loading('hide');
                showMessagePHP('Không thể kết nối đến máy chủ. Mã lỗi: ' + xhr.status, 5);
            }
        };
        xhr.onerror = function() {
            loading('hide');
            showMessagePHP('Lỗi khi gửi yêu cầu', 5);
        };
        xhr.send();
    }

    //Xóa ip VBot Server đã scan được
    function delete_IP_VBot_Server(ip) {
        loading('show');
        if (!ip || !ip.startsWith('192.168')) {
            alert("Địa chỉ IP không hợp lệ. IP phải bắt đầu bằng '192.168'.");
            loading('hide');
            return;
        }
        const url = '/includes/php_ajax/Check_Connection.php?delete_ip_vbot_server&ip=' + encodeURIComponent(ip);
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    loading('hide');
                    showMessagePHP("<font color=green>Xóa thiết bị với IP " + ip + " thành công</font>", 7);
                    get_vbotScanDevices();
                } else {
                    loading('hide');
                    showMessagePHP("Lỗi xóa thiết bị: " + response.error);
                }
            } else {
                loading('hide');
                show_message("Lỗi kết nối: " + xhr.status);
            }
        };
        xhr.onerror = function() {
            loading('hide');
            showMessagePHP("Lỗi khi gửi yêu cầu", 5);
        };
        xhr.send();
    }

    //Bật, tắt, Command bluetooth
    function bluetooth_control(Action, Value) {
        loading('show');
        var data = JSON.stringify({
            "type": 4,
            "data": 'bluetooth',
            "action": Action,
            "value": Value
        });
        var xhr = new XMLHttpRequest();
        xhr.addEventListener("readystatechange", function() {
            if (this.readyState === 4) {
                try {
                    if (this.status === 0) {
                        loading('hide');
                        show_message('Lỗi: Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API, và Bot đã hoạt động chưa');
                        return;
                    } else if (this.status !== 200) {
                        loading('hide');
                        show_message('Lỗi: Mã trạng thái HTTP ' + this.status);
                        return;
                    }
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        loading('hide');
                        if (Action === 'power') {
                            showMessagePHP(response.message, 5);
                        } else if (Value === 'AT+PAIR') {
                            var data = JSON.parse(response.message);
                            var tableHTML = '<br/><table class="table table-bordered border-primary" cellspacing="0" cellpadding="5"><tr><th style="text-align: center; vertical-align: middle;">Địa Chỉ Mac</th><th style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th><th style="text-align: center; vertical-align: middle;">Hành Động</th></tr>';
                            var seenMacAddresses = [];
                            data.forEach(function(dataItem) {
                                var dataItemContent = dataItem.data;
                                if (dataItemContent && dataItemContent.includes("MacAdd") && dataItemContent.includes("Name")) {
                                    var macAddMatch = dataItemContent.match(/MacAdd:([0-9a-fA-F]+)/);
                                    var nameMatch = dataItemContent.match(/Name:([^,]+)/);
                                    if (macAddMatch && nameMatch) {
                                        var macAdd = macAddMatch[1];
                                        if (!seenMacAddresses.includes(macAdd)) {
                                            seenMacAddresses.push(macAdd);
                                            tableHTML += "<tr><td style='text-align: center; vertical-align: middle;'>" + macAdd + "</td><td style='text-align: center; vertical-align: middle;'>" + nameMatch[1] + "</td><td style='text-align: center; vertical-align: middle;'><button type='button' class='btn btn-success' onclick=\"bluetooth_control('connect', '" + macAdd + "|" + nameMatch[1] + "')\"><i class='bi bi-arrows-angle-contract'> Kết Nối</i></button></td></tr>";
                                        }
                                    }
                                }
                            });
                            tableHTML += "</table>";
                            document.getElementById("Bluetooth_Scan_devices").innerHTML = tableHTML;
                            localStorage.setItem('bluetoothDevices_Vbot', JSON.stringify(data));
                        } else if (Action === 'connect') {
                            var data = JSON.parse(response.message);
                            var jsonString = JSON.stringify(data, null, 2);
                            show_message('<b><pre class="text-success">' + jsonString + '</pre></b>');
                        } else {
                            var data = JSON.parse(response.message);
                            var jsonString = JSON.stringify(data, null, 2);
                            show_message('<b><pre class="text-success">' + jsonString + '</pre></b>');
                        }
                    } else {
                        loading('hide');
                        show_message('Lỗi: ' + response.message);
                    }
                } catch (error) {
                    loading('hide');
                    show_message('Đã xảy ra lỗi trong quá trình xử lý: ' + error.message);
                }
            }
        });
        xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(data);
    }

    //Tải xuống bài hát Zingmp3
    function dowload_ZingMP3_ID(zing_id, zing_name) {
        loading("show");
        var xhr = new XMLHttpRequest();
        var url = 'includes/php_ajax/Media_Player_Search.php?ZingMP3_GetLink&Zing_ID=' + zing_id;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success == true) {
                        fetch(data.url)
                            .then(response => {
                                loading("hide");
                                if (!response.ok) {
                                    show_message('Lỗi xảy ra, không thể tải file âm thanh từ ZingMP3');
                                }
                                return response.blob();
                            })
                            .then(blob => {
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = zing_name + '.mp3';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                window.URL.revokeObjectURL(url);
                                loading("hide");
                                showMessagePHP('Đã tải xuống file âm thanh từ ZingMP3: ' + zing_name + '.mp3', 5);
                            })
                            .catch(error => {
                                loading("hide");
                                show_message('Lỗi, Không thể tải file âm thanh từ Zingmp3:' + error);
                            });
                    } else {
                        loading("hide");
                        show_message('Yêu cầu không thành công, Không lấy được link để tải xuống hoặc bạn có thể thử lại');
                    }
                } catch (e) {
                    loading("hide");
                    show_message('Lỗi phân tích cú pháp JSON:' + e);
                }
            } else if (xhr.readyState === 4) {
                loading("hide");
                show_message('Lỗi tìm nạp dữ liệu:' + xhr.status);
            }
        };
        xhr.send();
    }

    // Tải Nhạc ZingMP3 Vào Thư Mục Local
    function download_zingMp3_to_local(IDzing, songName) {
        if (!IDzing || !songName) {
            show_message('Lỗi: ID Zing hoặc tên bài hát không được để trống.');
        }
        loading("show");
        var xhr = new XMLHttpRequest();
        var url_get_link = 'includes/php_ajax/Media_Player_Search.php?ZingMP3_GetLink&Zing_ID=' + encodeURIComponent(IDzing);
        xhr.open('GET', url_get_link, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success == true) {
                        var xhr2 = new XMLHttpRequest();
                        xhr2.open('POST', 'includes/php_ajax/Media_Player_Search.php', true);
                        xhr2.setRequestHeader('Content-Type', 'application/json');
                        xhr2.onreadystatechange = function() {
                            if (xhr2.readyState === 4) {
                                loading("hide");
                                if (xhr2.status === 200) {
                                    try {
                                        var data2 = JSON.parse(xhr2.responseText);
                                        if (data2.success) {
                                            showMessagePHP(data2.message, 5);
                                        } else {
                                            show_message('Lỗi: ' + data2.message);
                                        }
                                    } catch (e) {
                                        show_message('Lỗi phân tích JSON: ' + e.message);
                                    }
                                } else {
                                    show_message('Lỗi HTTP: ' + xhr2.status);
                                }
                            }
                        };
                        var postData = JSON.stringify({
                            zing_download_mp3_to_local: {
                                url: data.url,
                                name: songName
                            }
                        });
                        try {
                            xhr2.send(postData);
                        } catch (error) {
                            loading("hide");
                            show_message('Lỗi gửi yêu cầu: ' + error.message);
                        }
                    } else {
                        loading("hide");
                        show_message('Yêu cầu không thành công, Không lấy được link để tải xuống hoặc bạn có thể thử lại');
                    }
                } catch (e) {
                    loading("hide");
                    show_message('Lỗi phân tích cú pháp JSON: ' + e);
                }
            } else if (xhr.readyState === 4) {
                loading("hide");
                show_message('Lỗi tìm nạp dữ liệu: ' + xhr.status);
            }
        };
        xhr.send();
    }


    // Tải file MP3 từ URL vào thư mục local trên server
    function download_Link_url_to_local(url_audio, songName) {
        if (!url_audio || !songName) {
            show_message('Lỗi: URL âm thanh hoặc tên bài hát không được để trống.');
            return;
        }
        songName = songName.replace(/\.mp3$/i, '');
        loading("show");
        var xhr2 = new XMLHttpRequest();
        xhr2.open('POST', 'includes/php_ajax/Media_Player_Search.php', true);
        xhr2.setRequestHeader('Content-Type', 'application/json');
        xhr2.onreadystatechange = function() {
            if (xhr2.readyState === 4) {
                loading("hide");
                if (xhr2.status === 200) {
                    if (!xhr2.responseText) {
                        show_message('Lỗi: Phản hồi rỗng từ server');
                        return;
                    }
                    try {
                        var data2 = JSON.parse(xhr2.responseText);
                        if (data2.success) {
                            showMessagePHP(data2.message, 5);
                        } else {
                            show_message('Lỗi: ' + data2.message);
                        }
                    } catch (e) {
                        show_message('Lỗi phân tích JSON: ' + e.message);
                    }
                } else {
                    show_message('Lỗi HTTP: ' + xhr2.status);
                }
            }
        };
        var postData = JSON.stringify({
            zing_download_mp3_to_local: {
                url: url_audio,
                name: songName
            }
        });
        try {
            xhr2.send(postData);
        } catch (error) {
            loading("hide");
            show_message('Lỗi gửi yêu cầu: ' + error.message);
        }
    }
</script>
<script>
    // Kiểm tra điều kiện và thông báo cập nhật
    <?php if ($Config['backup_upgrade']['advanced_settings']['automatically_check_for_updates'] === true) { ?>
        window.onload = function() {
            // Nếu URL không chứa "Login.php", thực hiện các hàm sau
            if (!window.location.href.includes("Login.php")) {
                vbot_check_update();
                ui_check_update();
            }
        };
    <?php } ?>
</script>

<script>
    // Hàm khởi tạo log viewer (gọi API thật sự)
    function initLogViewer(checkboxId, outputId, apiUrl) {
        const checkboxEl = document.getElementById(checkboxId);
        const outputEl = document.getElementById(outputId);
        if (!checkboxEl || !outputEl) return;
        // Xoá interval cũ trước khi tạo mới
        if (window.logInterval) clearInterval(window.logInterval);

        function fetchLogs() {
            const xhr = new XMLHttpRequest();
            xhr.onerror = function() {
                outputEl.innerHTML = '<span style="color: red;">Không thể kết nối đến API, vui lòng kiểm tra lại</span>';
                outputEl.scrollTop = outputEl.scrollHeight;
            };
            xhr.ontimeout = function() {
                outputEl.innerHTML = '<span style="color: red;">Lỗi timeout. API phản hồi quá lâu.</span>';
                outputEl.scrollTop = outputEl.scrollHeight;
            };
            xhr.timeout = 10000;
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            if (response.data && response.data.length > 0) {
                                const logs = response.data
                                    .map(item => formatLogMessage(item.logs_message))
                                    .join('');
                                outputEl.innerHTML = logs;
                            } else {
                                outputEl.innerHTML = '<span style="color: orange;">Dữ Liệu Logs Chương Trình VBot Rỗng</span>';
                            }
                        } else {
                            outputEl.innerHTML = '<span style="color: red;">Lỗi: ' + response.message + '</span>';
                        }
                    } catch (e) {
                        outputEl.innerHTML = '<span style="color: green;">Nội dung trả về: </span><br>' +
                            '<pre>' + xhr.responseText + '</pre>';
                    }
                    outputEl.scrollTop = outputEl.scrollHeight;
                }
            };
            xhr.open("GET", apiUrl + "logs");
            xhr.send();
        }
        // bắt đầu interval
        window.logInterval = setInterval(fetchLogs, 1000);
    }

    // Màu cho log messages
    function formatLogMessage(message) {
        const logStyles = [{
                keyword: '[BOT] Đang thu âm',
                style: 'color: rgb(255, 105, 97);'
            },
            {
                keyword: '[BOT]',
                style: 'color: rgb(255, 214, 10);'
            },
            {
                keyword: '[HUMAN]',
                style: 'color: rgb(0, 255, 0);'
            },
            {
                keyword: 'Đang chờ được đánh thức.',
                style: 'color: rgb(0, 255, 0);'
            },
            {
                keyword: 'dữ liệu âm thanh',
                style: 'color: rgb(144, 238, 144);'
            },
            {
                keyword: 'Không có giọng nói được truyền vào',
                style: 'color: rgb(221, 160, 221);'
            },
            {
                keyword: 'Đã được đánh thức.',
                style: 'color: rgb(255, 182, 193);'
            },
            {
                keyword: 'Đang phát',
                style: 'color: rgb(255, 165, 0);'
            },
            {
                keyword: '[Custom skills',
                style: 'color: rgb(64, 224, 208);'
            },
            {
                keyword: 'ERROR',
                style: 'color: rgb(255, 69, 58);'
            },
            {
                keyword: 'WARNING',
                style: 'color: rgb(255, 140, 0);'
            },
            {
                keyword: 'SUCCESS',
                style: 'color: rgb(50, 205, 50);'
            },
        ];
        const style = logStyles.find(log => message.includes(log.keyword))?.style || 'color: white;';
        return '<div style="' + style + '">' + message + '</div>';
    }

    //Map checkbox -> div output
    const logCheckboxMap = {
        "fetchLogsCheckbox": "logsOutput",
        "fetchLogsCheckbox_Head": "logsOutput_Head"
    };

    //Gắn sự kiện cho cả 2 checkbox
    document.querySelectorAll("#fetchLogsCheckbox, #fetchLogsCheckbox_Head")
        .forEach(cb => {
            cb.addEventListener("change", function() {
                if (this.checked) {
                    //Tắt checkbox còn lại
                    document.querySelectorAll("#fetchLogsCheckbox, #fetchLogsCheckbox_Head")
                        .forEach(other => {
                            if (other !== this) {
                                other.checked = false;
                                document.getElementById(logCheckboxMap[other.id]).innerHTML = "";
                            }
                        });
                    //Bật log cho checkbox này
                    const outputId = logCheckboxMap[this.id];
                    initLogViewer(this.id, outputId, "<?php echo $URL_API_VBOT; ?>");
                } else {
                    //Nếu tắt thì clear interval và clear log
                    if (window.logInterval) clearInterval(window.logInterval);
                    document.getElementById(logCheckboxMap[this.id]).innerHTML = "";
                }
            });
        });
    //Khi click icon đóng thì bỏ tích checkbox Head và clear interval
    document.getElementById("Close_Logs_Head").addEventListener("click", function() {
        const cbHead = document.getElementById("fetchLogsCheckbox_Head");
        cbHead.checked = false;
        if (logInterval) clearInterval(logInterval);
    });

	//Tìm kiếm dữ liệu trong trang
	document.addEventListener('DOMContentLoaded', function() {
	  var searchResults = [];
	  function findParentH5(el) {
		var parent = el.parentElement;
		while(parent) {
		  var h5 = parent.querySelector('h5');
		  if(h5) return h5;
		  parent = parent.parentElement;
		}
		return null;
	  }
	  function findElementsWithText(keyword) {
		searchResults = [];
		var seenTexts = new Set();
		keyword = keyword.toLowerCase();
		var elements = document.querySelectorAll('label, button, font');
		elements.forEach(el => {
		  var textNodes = Array.from(el.childNodes).filter(n => n.nodeType === Node.TEXT_NODE);
		  var visibleText = textNodes.map(n => n.textContent)
									 .map(t => t.split('\n').filter(l => !l.trim().startsWith('//')).join(' '))
									 .join(' ')
									 .trim();
		  if (visibleText.toLowerCase().includes(keyword) && visibleText !== '') {
			if (!seenTexts.has(visibleText)) {
			  var parentH5 = findParentH5(el);
			  searchResults.push({el: el, parentH5: parentH5});
			  seenTexts.add(visibleText);
			}
		  }
		});
	  }
	  function updateDropdown() {
		var dropdown = document.getElementById('searchResults');
		dropdown.innerHTML = '';
		if (searchResults.length === 0) {
		  dropdown.classList.remove('show');
		  return;
		}
	searchResults.forEach((item, index) => {
	  var li = document.createElement('li');
	  li.classList.add('dropdown-item');
	  var parentH5Text = item.parentH5 ? item.parentH5.textContent.trim() : '';
	  var elText = item.el.textContent.trim().substring(0,100);
	  li.textContent = parentH5Text + ' -> ' + elText;
	  li.addEventListener('click', function() {
		var el = item.el;
		var parent = el.parentElement;
		while(parent) {
		  if(parent.classList.contains('collapse') && !parent.classList.contains('show')) {
			var instance = bootstrap.Collapse.getOrCreateInstance(parent);
			instance.show();
		  }
		  parent = parent.parentElement;
		}
		setTimeout(function(target){
			target.scrollIntoView({behavior:'smooth', block:'center'});
			
			target.style.backgroundColor = '#c8e6c9'; //xanh nhạt
			
			setTimeout(() => {
				target.style.backgroundColor = '';
			}, 1500);
		}, 300, el);
		dropdown.classList.remove('show'); 
	  });
	  dropdown.appendChild(li);
	});
		dropdown.classList.add('show');
	  }
	  var input = document.getElementById('searchInput');
	  input.addEventListener('input', function() {
		var keyword = this.value.trim();
		if(keyword) {
		  findElementsWithText(keyword);
		  updateDropdown();
		} else {
		  searchResults = [];
		  updateDropdown();
		}
	  });
	});
</script>