<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include_once 'Configuration.php';
$URL_Address = dirname($Current_URL);
$parsedUrl = parse_url($Github_Repo_Vbot);
$pathParts = explode('/', trim($parsedUrl['path'], '/'));
$git_username = $pathParts[0];
$git_repository = $pathParts[1];
?>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<?php
$webui_page_name = basename($_SERVER['PHP_SELF'] ?? '');
$webui_datatable_pages = ['index.php', 'Log_TTS.php', 'Log_pycache.php'];
$webui_media_pages = ['index.php', 'Media_Player.php'];
$webui_backup_pages = ['Config.php', '_Program.php', '_Dashboard.php'];
$webui_password_pages = ['Login.php', 'Users_Profile.php'];
if (in_array($webui_page_name, $webui_datatable_pages, true)) {
    echo '<script src="assets/vendor/simple-datatables/simple-datatables.js?v='
        .htmlspecialchars((string)$Cache_UI_Ver, ENT_QUOTES, 'UTF-8')
        .'"></script>';
}
?>
<script src="assets/vendor/jquery/jquery-3.5.1.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/popper/popper.min.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/vendor/hls/hls.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/js/main.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<script src="assets/js/VBot.js?v=<?php echo $Cache_UI_Ver; ?>"></script>
<?php
if (in_array($webui_page_name, ['_Program.php', '_Dashboard.php'], true)) {
    echo '<script src="assets/js/webui-gcloud.js?v='
        .htmlspecialchars((string)$Cache_UI_Ver, ENT_QUOTES, 'UTF-8')
        .'"></script>';
}
?>
<script>
    //Xóa File theo path
	function deleteFile(filePath, langg = "No") {
		var fileName = filePath.substring(filePath.lastIndexOf('/') + 1);
		if (!confirm("Bạn có chắc chắn muốn xóa file: '" + fileName + "' này không?")) {
			return;
		}
		loading("show");
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'includes/php_ajax/Del_file_path.php', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
		xhr.onload = function() {
			loading("hide");
			try {
				if (xhr.status !== 200) {
					throw new Error("Lỗi HTTP: " + xhr.status);
				}
				var response = JSON.parse(xhr.responseText);
				if (response.status === 'success') {
					showMessagePHP(response.message, 3);
				} else {
					show_message("<center>" + response.message + "</center>");
				}
				//MAP xử lý langg
				var actions = {
					vi: function() { loadConfigHotword("vi"); },
					eng: function() { loadConfigHotword("eng"); },
					snowboy: function() { loadConfigHotword("snowboy"); },
					wakeup_reply: function() { loadWakeupReply(); },
					scan_Music_Local: function() { list_audio_show_path('scan_Music_Local'); },
					scan_Audio_Startup: function() { list_audio_show_path('scan_Audio_Startup'); },
					Vbot_Backup_Program: function() {
						if (document.getElementById("show_all_file_folder_Backup_Program")) {
							show_all_file_in_directory('<?php echo $HTML_VBot_Offline . '/' . $Backup_Dir_Save_VBot; ?>', 'Tệp Sao Lưu Chương Trình Trên Hệ Thống', 'show_all_file_folder_Backup_Program');
						} else if (document.getElementById("show_all_file_folder_Backup_web_interface")) {
							show_all_file_in_directory('<?php echo $HTML_VBot_Offline . '/' . $Backup_Dir_Save_Web; ?>', 'Tệp Sao Lưu Giao Diện Trên Hệ Thống', 'show_all_file_folder_Backup_web_interface');
						}
					},
					media_player_search: function() {
						if (document.getElementById("local-tab")) {
							media_player_search();
						} else if (document.getElementById("select_cache_media")) {
							media_player_search("Local");
						}
					}
				};
				if (actions[langg]) {
					actions[langg]();
				}
			} catch (error) {
				show_message("<center>Lỗi xử lý: " + error.message + "</center>");
			}
		};
		xhr.send('filePath=' + encodeURIComponent(filePath));
	}

    //Hàm tải xuống file theo đường dẫn
    function downloadFile(filePath) {
        var link = document.createElement('a');
        link.href = 'includes/php_ajax/Download_file_path.php?file=' + encodeURIComponent(filePath);
        link.target = '_blank';
        link.download = filePath.substring(filePath.lastIndexOf('/') + 1);
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

<?php if (in_array($webui_page_name, $webui_backup_pages, true)) { ?>
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
                    var resultDiv_show_all_File = document.getElementById(resultDiv_Id);
                    if (!resultDiv_show_all_File) {
                        showMessagePHP('Không tìm thấy phần tử có id là: ' + resultDiv_Id + ' để hiển thị kết quả.');
                        return;
                    }
                    if (response.success) {
                        showMessagePHP(response.message);
                        var table = '<table class="table table-bordered border-primary">';
                        table += '<tr><th colspan="5" class="text-primary" style="text-align: center; vertical-align: middle;">' + source_backup + '</th></tr>';
                        table += '<tr><th style="text-align: center; vertical-align: middle;">STT</th><th style="text-align: center; vertical-align: middle;">Tên tệp</th><th style="text-align: center; vertical-align: middle;">Thời gian tạo</th><th style="text-align: center; vertical-align: middle;">Kích thước</th><th style="text-align: center; vertical-align: middle;">Hành động</th></tr>';
                        response.data.forEach(function(file, index) {
                            table += '<tr>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.name + '</td>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.created_at + '</td>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + file.size + '</td>';
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

<?php } ?>
    //Đọc dữ liệu file theo path
    function read_loadFile(path) {
        var url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=' + encodeURIComponent(path);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    document.getElementById('message_LoadConfigJson').textContent = response.message_LoadConfigJson;
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

<?php if (in_array($webui_page_name, $webui_backup_pages, true)) { ?>
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
                        var table = '<table class="table table-bordered border-primary">';
                        table += '<tr><th colspan="3"  class="text-success"><center>Cấu Trúc Tệp: ' + fileName + '</center></th></tr>';
                        table += '<tr><th><center>STT</center></th><th><center>Tên tệp</center></th><th><center>Hành động</center></th></tr>';
                        response.data.forEach(function(file, index) {
                            table += '<tr>';
                            table += '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>';
                            table += '<td style="vertical-align: middle;"><font color=blue>' + file + '</font></td>';
                            table += '<td style="text-align: center; vertical-align: middle;">';
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

<?php } ?>
<?php if (in_array($webui_page_name, $webui_password_pages, true)) { ?>
    //tìm lại mật khẩu WebUI
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

<?php } ?>
<?php if ($webui_page_name === 'Lib_pip.php') { ?>
    //Gửi lệnh và thự thi, lệnh được encode dưới dạng base64
    function VBot_Command(b64_encode) {
        if (!confirm("Bạn có chắc chắn muốn thực thi lệnh:\n$:> " + atob(b64_encode))) {
            return;
        }
        loading('show');
        var xhr = new XMLHttpRequest();
        xhr.open("POST", 'includes/php_ajax/Check_Connection.php');
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
        xhr.setRequestHeader("X-CSRF-Token", window.VBOT_CSRF_TOKEN || "");
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
        xhr.send("VBot_CMD=1&Command=" + encodeURIComponent(b64_encode));
    }

<?php } ?>
<?php if (in_array($webui_page_name, ['Config.php', 'Media_Player.php'], true)) { ?>
    //Tải lên file âm thanh theo giấ trị được chỉ định key_path
    function upload_File(key_path) {
        loading("show");
        var fileInput = document.getElementById(key_path);
        var files = fileInput.files;
        var formData = new FormData();
        if (files.length > 0) {
            for (var i = 0; i < files.length; i++) {
                formData.append('fileUpload[]', files[i]);
            }
            var xhr = new XMLHttpRequest();
            formData.append(key_path, "1");
            xhr.open('POST', 'includes/php_ajax/Upload_file_path.php');
            xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
            xhr.onload = function() {
                loading("hide");
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (xhr.status === 200) {
                        if (document.getElementById("local-tab")) {
                            if (typeof media_player_search === 'function') {
                                media_player_search();
                            }
                        } else if (document.getElementById("select_cache_media")) {
                            media_player_search("Local");
                        } else if (document.getElementById("show_mp3_music_local")) {
                            list_audio_show_path('scan_Music_Local')
                        }
                        var uploadMessages = Array.isArray(response.messages)
                            ? response.messages.join('<br/>')
                            : (response.message || 'Phản hồi upload không hợp lệ');
                        show_message(uploadMessages);
                    } else {
                        show_message(response.message || response.error || ('Lỗi HTTP: ' + xhr.status));
                    }
                } catch (e) {
                    show_message('Lỗi phân tích JSON: ' + e.message);
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

<?php } ?>
<?php if (in_array($webui_page_name, $webui_media_pages, true)) { ?>
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

    //Hàm lấy Audio Link
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

<?php } ?>
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

<?php if (in_array($webui_page_name, $webui_media_pages, true)) { ?>
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
                if (!zingDataDiv) {
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
                        setTimeout(function() {
                            if (document.getElementById('song_name_value')) {
                                document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);
                            }
                        }, 0);
                    }
                    var data = JSON.parse(xhr.responseText);
                    if (Array.isArray(data.data) && data.data.length > 0) {
                        zingDataDiv.innerHTML += 'Dữ Liệu Cache ZingMP3: '+
						' <button type="button" id="play_Button" name="play_Button" title="Phát toàn bộ dữ liệu tìm kiếm được từ ZingMP3" class="btn btn-primary btn-sm" onclick="playlist_media_control(\'zingmp3\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button> ' +
						' <button class="btn btn-warning btn-sm" title="Tải Xuống Dữ Liệu ZingMP3" onclick="downloadFile(\'<?php echo $VBot_Offline.'html/includes/cache/ZingMP3.json' ; ?>\')"><i class="bi bi-download"></i> <i class="bi bi-filetype-json"></i></button> ' +
						'<button class="btn btn-danger btn-sm" title="Xóa dữ liệu cache ZingMP3" onclick="cache_delete(\'ZingMP3\')"><i class="bi bi-trash"></i></button> <br/>';
                        data.data.forEach(function(cache_ZING) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + cache_ZING.thumb + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên Bài Hát: <font color=green>' + cache_ZING.name + '</font></p>';
                            fileInfo += '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color=green>' + cache_ZING.artist + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (cache_ZING.duration || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success btn-sm" title="Phát: ' + cache_ZING.name + '" onclick="get_ZingMP3_Link(\'' + cache_ZING.id + '\', \'' + cache_ZING.name + '\', \'' + cache_ZING.thumb + '\', \'' + cache_ZING.artist + '\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <button class="btn btn-primary btn-sm" title="Thêm vào danh sách phát: ' + cache_ZING.name + '" onclick="addToPlaylist(\'' + cache_ZING.name + '\', \'' + cache_ZING.thumb + '\', \'' + cache_ZING.id + '\', \'' + (cache_ZING.duration || 'N/A') + '\', null, \'ZingMP3\', \'' + cache_ZING.id + '\', null, \'' + cache_ZING.artist + '\')"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ' <button class="btn btn-warning btn-sm" title="Tải Xuống: ' + cache_ZING.name + '" onclick="dowload_ZingMP3_ID(\'' + cache_ZING.id + '\', \'' + cache_ZING.name + '\')"><i class="bi bi-download"></i></button>';
                            fileInfo += ' <button class="btn btn-info btn-sm" title="Tải Vào Thư Mục Local: ' + cache_ZING.name + '" onclick="download_zingMp3_to_local(\'' + cache_ZING.id + '\', \'' + cache_ZING.name + '\')"><i class="bi bi-save2"></i></button>';
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
                        setTimeout(function() {
                            if (document.getElementById('song_name_value')) {
                                document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);
                            }
                        }, 0);
                    }
                    var data = JSON.parse(xhr.responseText);
                    if (Array.isArray(data.data) && data.data.length > 0) {
                        nhaccuatuiDataDiv.innerHTML += 'Dữ Liệu Cache NCT: ' +
						' <button type="button" id="play_Button" name="play_Button" title="Phát toàn bộ dữ liệu tìm kiếm được từ NhacCuaTui" class="btn btn-primary btn-sm" onclick="playlist_media_control(\'nhaccuatui\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button> ' +
						' <button class="btn btn-warning btn-sm" title="Tải Xuống Dữ Liệu NhacCuaTui" onclick="downloadFile(\'<?php echo $VBot_Offline.'html/includes/cache/NhacCuaTui.json' ; ?>\')"><i class="bi bi-download"></i> <i class="bi bi-filetype-json"></i></button> ' +
						'<button class="btn btn-danger btn-sm" title="Xóa dữ liệu cache NhacCuaTui" onclick="cache_delete(\'NhacCuaTui\')"><i class="bi bi-trash"></i></button> <br/>';
                        data.data.forEach(function(cache_nct) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + cache_nct.thumb + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên Bài Hát: <font color=green>' + cache_nct.name + '</font></p>';
                            fileInfo += '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color=green>' + cache_nct.artist + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (cache_nct.duration || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success btn-sm" title="Phát: ' + cache_nct.name + '" onclick="startMediaPlayer(\'' + cache_nct.url + '\', \'' + cache_nct.name + '\', \'' + cache_nct.thumb + '\', \'NhacCuaTui\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <button class="btn btn-primary btn-sm" title="Thêm vào danh sách phát: ' + cache_nct.name + '" onclick="addToPlaylist(\'' + cache_nct.name + '\', \'' + cache_nct.thumb + '\', \'' + cache_nct.url + '\', \'' + (cache_nct.duration || 'N/A') + '\', null, \'NhacCuaTui\', \'' + cache_nct.url + '\', null, \'' + cache_nct.artist + '\')"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ` <button class="btn btn-warning btn-sm" title="Tải Xuống: ${cache_nct.name}" onclick="downloadFile('${cache_nct.url.substring(0, cache_nct.url.indexOf('.mp3') + 4)}')"><i class="bi bi-download"></i></button>`;
                            fileInfo += ' <button class="btn btn-info btn-sm" title="Tải Vào Thư Mục Local: ' + cache_nct.name + '" onclick="download_Link_url_to_local(\'' + cache_nct.url + '\', \'' + cache_nct.name + '\')"><i class="bi bi-save2"></i></button>';
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
                if (!fileListDiv) {
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
                        fileListDiv.innerHTML += 'Dữ Liệu Cache PodCast: ' +
						' <button class="btn btn-warning btn-sm" title="Tải Xuống Dữ Liệu PodCast" onclick="downloadFile(\'<?php echo $VBot_Offline.'html/includes/cache/PodCast.json' ; ?>\')"><i class="bi bi-download"></i> <i class="bi bi-filetype-json"></i></button> ' +
						' <button class="btn btn-danger btn-sm" title="Xóa dữ liệu cache PodCast" onclick="cache_delete(\'PodCast\')"><i class="bi bi-trash"></i></button><br/>';
                        data.data.forEach(function(podcast) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + podcast.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên Bài Hát: <font color=green>' + podcast.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (podcast.duration || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thể Loại: <font color=green>' + (podcast.description || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success btn-sm" title="Phát: ' + podcast.title + '" onclick="startMediaPlayer(\'' + podcast.audio + '\', \'' + podcast.title + '\', \'' + podcast.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <button class="btn btn-primary btn-sm" title="Thêm vào danh sách phát: ' + podcast.title + '" onclick="addToPlaylist(\'' + podcast.title + '\', \'' + podcast.cover + '\', \'' + podcast.audio + '\', \'' + (podcast.duration || 'N/A') + '\', \'' + (podcast.description || 'N/A') + '\', \'PodCast\', \'' + podcast.audio + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ' <button class="btn btn-warning btn-sm" title="Tải Xuống: ' + podcast.title + '" onclick="download_AUDIO_URL(\'' + podcast.audio + '\', \'' + podcast.title + '\')"><i class="bi bi-download"></i></button>';
                            fileInfo += ' <button class="btn btn-danger btn-sm" title="Tải Vào Thư Mục Local: ' + podcast.title + '" onclick="download_Link_url_to_local(\'' + podcast.audio + '\', \'' + podcast.title + '\')"><i class="bi bi-save2"></i></button>';
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
                        var fileListDiv = document.getElementById('show_list_news_paper');
                        if (!fileListDiv) {
                            fileListDiv = document.getElementById('tableContainer');
                            document.getElementById('tableContainer').style.display = '';
                            document.getElementById('tableContainer').style.height = '400px';
                            document.getElementById('tableContainer').style.overflowY = 'auto';
                        }
                        fileListDiv.innerHTML = '';
                        fileListDiv.innerHTML += '<b>Dữ Liệu Cache: '+ (response.data[0].source || 'N/A') +' </b> ' +
						' <button class="btn btn-success btn-sm" title="Phát toàn bộ" onclick="play_playlist_json_path(\'<?php echo $directory_path; ?>/includes/cache/<?php echo $Config['media_player']['news_paper']['newspaper_file_name']; ?>\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button> '+
						' <button class="btn btn-warning btn-sm" title="Tải Xuống Dữ Liệu Báo, Tin Tức" onclick="downloadFile(\'<?php echo $VBot_Offline.'html/includes/cache/News_Paper.json' ; ?>\')"><i class="bi bi-download"></i> <i class="bi bi-filetype-json"></i></button> ';
                        response.data.forEach(function(news_paper) {
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + news_paper.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tiêu Đề: <font color=green>' + news_paper.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Gian Tạo: <font color=green>' + (news_paper.publish_time || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (news_paper.duration || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Nguồn: <font color=green>' + (news_paper.source || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success btn-sm" title="Phát: ' + news_paper.title + '" onclick="send_Media_Play_API(\'' + news_paper.audio + '\', \'' + news_paper.title + '\', \'' + news_paper.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <a href="' + news_paper.audio + '" target="_blank"><button class="btn btn-info btn-sm" title="Mở trong tab mới: ' + news_paper.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
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

    //hiển thị dữ liệu cache Link/URL thẻ input nhập link
    function cache_Link_URL() {
        var inputElement = document.getElementById("tim_kiem_bai_hat_all");
        if (inputElement) {
            inputElement.style.display = "";
        }
		var fileListDiv = document.getElementById('show_list_Link_URL');
		if (!fileListDiv) {
			fileListDiv = document.getElementById('tableContainer');
			document.getElementById('tableContainer').style.cssText = "height: auto; overflow-y: hidden;";
		}
		try {
			fileListDiv.innerHTML = '';
			if (!document.getElementById("song_name_value")) {
				fileListDiv.innerHTML += '<div class="input-group mb-3">' +
					'<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Cần nhập url/link nguồn âm thanh http, https" title="Nhập tên bài hát cần tìm kiếm hoặc nhập url/link Youtube" value="">' +
					'<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
					'<button id="actionButton_URL_Link" title="Phát Từ URL/Link" class="btn btn-success border-success" type="button" onclick="media_player_url(\'url_link\')"><i class="bi bi-play-circle" title="Phát bằng địa chỉ URL/Link"></i></button>';
			}
		}catch (e) {
			show_message('Lỗi: ' + e);
		}
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
                        fileListDiv.innerHTML += 'Dữ Liệu Cache Youtube: ' +
						' <button type="button" id="play_Button" name="play_Button" title="Phát toàn bộ dữ liệu tìm kiếm được từ Youtube" class="btn btn-primary btn-sm" onclick="playlist_media_control(\'youtube\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button> ' +
						' <button class="btn btn-warning btn-sm" title="Tải Xuống Dữ Liệu Youtube" onclick="downloadFile(\'<?php echo $VBot_Offline.'html/includes/cache/Youtube.json' ; ?>\')"><i class="bi bi-download"></i> <i class="bi bi-filetype-json"></i></button> ' +
						' <button class="btn btn-danger btn-sm" title="Xóa dữ liệu cache Youtube" onclick="cache_delete(\'Youtube\')"><i class="bi bi-trash"></i></button> <br/>';
                        data.data.forEach(function(youtube) {
                            var description = youtube.description.length > 70 ? youtube.description.substring(0, 70) + '...' : youtube.description;
                            var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                            fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                            fileInfo += '<img src="' + youtube.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                            fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên Bài Hát: <font color=green>' + youtube.title + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Kênh: <font color=green>' + (youtube.channelTitle || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (youtube.duration || 'N/A') + '</font></p>';
                            fileInfo += '<p style="margin: 0;">Mô tả: <font color="green">' + (description || 'N/A') + '</font></p>';
                            fileInfo += '<button class="btn btn-success btn-sm" title="Phát: ' + youtube.title + '" onclick="get_Youtube_Link(\'' + youtube.id + '\', \'' + youtube.title + '\', \'' + youtube.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                            fileInfo += ' <button class="btn btn-primary btn-sm" title="Thêm vào danh sách phát: ' + youtube.title + '" onclick="addToPlaylist(\'' + youtube.title + '\', \'' + youtube.cover + '\', \'https://www.youtube.com/watch?v=' + youtube.id + '\', \'' + (youtube.duration || 'N/A') + '\', \'' + (description || 'N/A') + '\', \'Youtube\', \'' + youtube.id + '\', \'' + (youtube.channelTitle || 'N/A') + '\', null)"><i class="bi bi-music-note-list"></i></button>';
                            fileInfo += ' <a href="https://www.youtube.com/watch?v=' + youtube.id + '" target="_bank"><button class="btn btn-info btn-sm" title="Mở trong tab mới: ' + youtube.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
                            fileInfo += '</div></div>';
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
        var url = "includes/php_ajax/Media_Player_Search.php";
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.setRequestHeader("X-CSRF-Token", window.VBOT_CSRF_TOKEN || "");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    loading("hide");
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
        var params = "playlist_ADD=1&title=" + encodeURIComponent(title) +
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
        xhr.open("POST", "includes/php_ajax/Media_Player_Search.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.setRequestHeader("X-CSRF-Token", window.VBOT_CSRF_TOKEN || "");
        const data = 'playlist_DELETE=1&action=' + encodeURIComponent(action) + '&ids_list=' + encodeURIComponent(idsList || "");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
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
					//console.log(data);
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
                        fileListDiv.innerHTML += '<b>Dữ Liệu Tìm Kiếm Báo: ' + (response.data[0].source || 'N/A') + ' </b> <button class="btn btn-success" title="Phát toàn bộ" onclick="play_playlist_json_path(\'<?php echo $directory_path; ?>/includes/cache/<?php echo $Config['media_player']['news_paper']['newspaper_file_name']; ?>\')"><i class="bi bi-music-note-list"></i> <i class="bi bi-play-fill"></i></button>';
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
        xhr.open('POST', 'includes/php_ajax/Media_Player_Search.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
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
        xhr.send('cache_delete=' + encodeURIComponent(source_cache));
    }

    //Xử lý Play, next, prev phaylist
	function playlist_media_control(action_control = null) {
		loading("show");
		let payload = {
			type: 1,
			data: "media_control",
			action: "play_list"
		};
		if (action_control === 'next' || action_control === 'prev') {
			payload.control = action_control;
		} else if (action_control === 'local') {
			payload.source_playlist = "local";
		}else if (action_control === 'youtube') {
			payload.source_playlist = "youtube";
		}else if (action_control === 'zingmp3') {
			payload.source_playlist = "zingmp3";
		}else if (action_control === 'nhaccuatui') {
			payload.source_playlist = "nhaccuatui";
		} else {
			payload.source_playlist = true;
		}
		const xhr = new XMLHttpRequest();
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				loading("hide");
				try {
					const text = xhr.responseText.trim();
					if (text.startsWith("{") || text.startsWith("[")) {
						const response = JSON.parse(text);
						showMessagePHP(response.message, response.success ? 3 : 5);
					} else {
						show_message("Không thể kết nối API: dữ liệu không phải JSON");
					}
				} catch (error) {
					show_message("Lỗi xử lý phản hồi: " + error);
				}
			}
		};
		xhr.onerror = function () {
			loading("hide");
			show_message("Yêu cầu thất bại: VBot chưa chạy hoặc API chưa bật");
		};
		xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
		xhr.setRequestHeader("Content-Type", "application/json");
		xhr.send(JSON.stringify(payload));
	}

<?php } ?>
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

<script>
    //Chatbox
    const select_Element_api_chatbox = document.getElementById('source_chatbot_api');
    function sendRequest(message) {
        const selectedValue_api_chatbox = select_Element_api_chatbox.value;
        const selectedOption_api_chatbox = select_Element_api_chatbox.options[select_Element_api_chatbox.selectedIndex];
        const fullName_VBot_api_chatbox = selectedOption_api_chatbox.getAttribute('data-full_name_chatbot_api');
        console.log(fullName_VBot_api_chatbox);
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
        recognition.lang = 'vi-VN';
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

//Hàm gửi yêu cầu tới Command.php
function command_php(command_line, reload_page = null) {
    const allowedCommands = [
        'chmod_vbot', 'owner_vbot', 'apache_restart', 'restart_alsa', 'reload_services',
        'serial_getty_ttyS0_start', 'serial_getty_ttyS0_stop',
        'serial_getty_ttyS0_enable', 'serial_getty_ttyS0_disable', 'reboot_os',
        'fix_asound_airplay', 'ifconfig_os', 'lscpu_os', 'hostnamectl_os',
        'kiem_tra_bo_nho', 'kiem_tra_dung_luong', 'logs_apache2',
        'list_systemctl_enabled', 'os_image_created', 'sudo_alsactl_store',
        'Stop_Service_Unnecessary_Processes', 'pip_show_all_lib',
        'pvporcupine_info', 'picovoice_info', 'alsamixer_soundcard_start',
        'alsamixer_soundcard_stop', 'alsamixer_soundcard_enable', 'alsamixer_soundcard_disable',
        'alsamixer_soundcard_status',
        'auto_start', 'auto_stop', 'auto_restart', 'auto_enable', 'auto_disable', 'auto_status',
        'restart_auto_wifi', 'enable_auto_wifi', 'logs_auto_wifi', 'status_auto_wifi',
        'start_btwifiset', 'stop_btwifiset', 'restart_btwifiset', 'enable_btwifiset', 'disabled_btwifiset',
        'logs_btwifiset', 'status_btwifiset', 'pass_crypto_btwifiset',
        'start_vbot_bluetooth_agent', 'stop_vbot_bluetooth_agent', 'restart_vbot_bluetooth_agent',
        'enable_vbot_bluetooth_agent', 'disabled_vbot_bluetooth_agent',
        'status_vbot_bluetooth_agent', 'logs_vbot_bluetooth_agent',
        'start_bluealsa', 'stop_bluealsa', 'restart_bluealsa', 'enable_bluealsa', 'disabled_bluealsa',
        'status_bluealsa', 'logs_bluealsa',
        'start_airplay', 'stop_airplay', 'restart_airplay', 'enable_airplay', 'disabled_airplay',
        'logs_airplay', 'status_airplay', 'version_airplay',
        'cloudflared_tunnel_start', 'cloudflared_tunnel_stop', 'cloudflared_tunnel_enable',
        'cloudflared_tunnel_disable', 'cloudflared_tunnel_status', 'cloudflared_tunnel_list',
        'save_asound_to_alsamixer', 'alsamixer_asound_to_alsamixer', 'update_btwifiset_py',
        'fix_airplay_services', 'install_bluetooth_agent_py', 'install_bthelper', 'install_bluealsa',
        'install_bluetooth_agent_service', 'install_bluetooth_config_main',
        'check_version_picovoice_porcupine', 'list_time_zones', 'check_time_zones', 'fix_time_zones',
        'config_auto', 'auto_wifi_manager_only', 'auto_wifi_manager_and_speaker_ip',
        'enabled_vbot_api_external', 'disable_vbot_api_external',
        'install_picovoice', 'install_porcupine', 'set_time_zones', 'submit_rename_airplay'
    ];
    if (allowedCommands.indexOf(command_line) === -1) {
        show_message('Thao tác không được hỗ trợ.');
        return;
    }
    if (command_line === 'reboot_os' && !confirm('Bạn có chắc chắn muốn khởi động lại hệ thống VBot?')) {
        return;
    }
    if (command_line === 'fix_asound_airplay'
        && !confirm('Bạn có chắc chắn muốn sao lưu và khôi phục file /etc/asound.conf?')) {
        return;
    }
    if (command_line === 'Stop_Service_Unnecessary_Processes'
        && !confirm('Tác vụ này sẽ tắt các service không cần thiết và khởi động lại VBot. Bạn có chắc chắn muốn tiếp tục?')) {
        return;
    }
    const fileChangeActions = [
        'alsamixer_asound_to_alsamixer', 'update_btwifiset_py', 'fix_airplay_services',
        'install_bluetooth_agent_py', 'install_bthelper', 'install_bluealsa',
        'install_bluetooth_agent_service', 'install_bluetooth_config_main', 'fix_time_zones',
        'config_auto', 'auto_wifi_manager_only', 'auto_wifi_manager_and_speaker_ip',
        'enabled_vbot_api_external', 'disable_vbot_api_external', 'install_picovoice', 'install_porcupine'
    ];
    if (fileChangeActions.indexOf(command_line) !== -1
        && !confirm('Thao tác này sẽ thay đổi file hoặc service hệ thống. Bạn có chắc chắn muốn tiếp tục?')) {
        return;
    }
    loading('show');
    const writeCommandLog = function(logText) {
        const logTextarea = document.getElementById('textarea_log_command');
        if (!logTextarea || typeof logText !== 'string' || logText.length === 0) {
            return;
        }
        logTextarea.value = logText;
        logTextarea.scrollTop = logTextarea.scrollHeight;
    };
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/php_ajax/Command_Ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
    xhr.onload = function() {
        loading('hide');
        let response;
        try {
            response = JSON.parse(xhr.responseText);
        } catch (error) {
            show_message('Máy chủ trả về dữ liệu không phải JSON (HTTP ' + xhr.status + ').');
            return;
        }
        if (xhr.status >= 200 && xhr.status < 300 && response.success === true) {
            writeCommandLog(response.command_log || response.data || '');
            if (false && response.display === 'modal') {
                const escapeHtml = function(value) {
                    return String(value).replace(/[&<>"']/g, function(character) {
                        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
                    });
                };
                show_message(
                    '<div class="text-start"><h6>' + escapeHtml(response.message || 'Kết quả') + '</h6>'
                    + '<pre class="mb-0" style="white-space:pre-wrap;max-height:65vh;overflow:auto">'
                    + escapeHtml(response.data || 'Không có dữ liệu.') + '</pre></div>'
                );
                return;
            }
            showMessagePHP(response.message || 'Thao tác thành công.', 5);
            if (reload_page === true) {
                window.setTimeout(function() {
                    location.reload();
                }, 500);
            }
            return;
        }
        show_message(response.message || ('Không thể xử lý yêu cầu (HTTP ' + xhr.status + ').'));
    };
    xhr.onerror = function() {
        loading('hide');
        show_message('Lỗi: Không thể kết nối tới máy chủ.');
    };
    let requestData = 'action=' + encodeURIComponent(command_line);
    if (command_line === 'install_picovoice') {
        const versionInput = document.querySelector('[name="versions_picovoice_install"]');
        requestData += '&version_picovoice=' + encodeURIComponent(versionInput ? versionInput.value : '');
    } else if (command_line === 'install_porcupine') {
        const versionInput = document.querySelector('[name="versions_porcupine_install"]');
        requestData += '&version_porcupine=' + encodeURIComponent(versionInput ? versionInput.value : '');
    } else if (command_line === 'set_time_zones') {
        const timezoneInput = document.querySelector('[name="show_lits_timezone"]');
        requestData += '&timezone=' + encodeURIComponent(timezoneInput ? timezoneInput.value : '');
    } else if (command_line === 'submit_rename_airplay') {
        const airplayNameInput = document.querySelector('[name="airplay_name_change"]');
        requestData += '&airplay_name=' + encodeURIComponent(airplayNameInput ? airplayNameInput.value : '');
    }
    if (command_line === 'reboot_os'
        || command_line === 'fix_asound_airplay'
        || command_line === 'Stop_Service_Unnecessary_Processes'
        || fileChangeActions.indexOf(command_line) !== -1) {
        requestData += '&confirmed=1';
    }
    xhr.send(requestData);
}

<?php if (in_array($webui_page_name, $webui_media_pages, true)) { ?>
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

    //Tải Nhạc ZingMP3 Vào Thư Mục Local
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

    //Tải file MP3 từ URL vào thư mục local trên server
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

	//Tải lên file json playlist
	function uploadFile_PlayList(tab) {
		var fileInput = document.getElementById("fileInput_PlayList");
		var file = fileInput.files[0];
		if (!file) {
			alert("Cần chọn file .json PlayList để tải lên!");
			return;
		}
		loading("show");
		var formData = new FormData();
		formData.append("json_file_playlist", "1");
		formData.append("select_json_file_playlist", file);
		var xhr = new XMLHttpRequest();
		xhr.open("POST", "includes/php_ajax/Upload_file_path.php", true);
		xhr.setRequestHeader('X-CSRF-Token', window.VBOT_CSRF_TOKEN || '');
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				try {
					var res = JSON.parse(xhr.responseText);
					if (res.success) {
						loading("hide");
						showMessagePHP(res.message, 5);
						if (tab === "index.php"){
							loadPlayList();
						}else if (tab === "Media_Player.php"){
							cachePlayList();
						}
					}
					else {
						loading("hide");
						show_message('Lỗi tải lên: ' + res.message);
					}
				} catch (e) {
					loading("hide");
					show_message('Tải lên thất bại, lỗi xảy ra: ' + xhr.responseText);
				}
			}
		};
		xhr.send(formData);
	}
<?php } ?>
</script>
<script>
    //Kiểm tra điều kiện và thông báo cập nhật
    <?php if ($Config['backup_upgrade']['advanced_settings']['automatically_check_for_updates'] === true) { ?>
        window.webuiUpdateConfig = {
            items: [
                {
                    label: 'WebUI',
                    title: 'Cập Nhật Web UI',
                    target: '_Dashboard.php',
                    localUrl: <?php echo json_encode(
                        'includes/php_ajax/Show_file_path.php?read_file_path&file='
                        .$HTML_VBot_Offline.'/Version.json',
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ); ?>,
                    remoteUrl: <?php echo json_encode(
                        'https://api.github.com/repos/'.$git_username.'/'.$git_repository
                        .'/contents/html/Version.json?ref=main',
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ); ?>
                },
                {
                    label: 'VBot',
                    title: 'Cập Nhật VBot',
                    target: '_Program.php',
                    localUrl: <?php echo json_encode(
                        'includes/php_ajax/Show_file_path.php?read_file_path&file='
                        .$VBot_Offline.'Version.json',
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ); ?>,
                    remoteUrl: <?php echo json_encode(
                        'https://api.github.com/repos/'.$git_username.'/'.$git_repository
                        .'/contents/Version.json?ref=main',
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ); ?>
                }
            ]
        };
        window.addEventListener('load', function() {
            if (!window.location.href.includes("Login.php")) {
                var script = document.createElement('script');
                script.src = 'assets/js/webui-updates.js?v=<?php echo rawurlencode((string)$Cache_UI_Ver); ?>';
                script.onerror = function() {
                    showMessagePHP('Không thể tải module kiểm tra cập nhật', 3);
                };
                document.head.appendChild(script);
            }
        });
    <?php } ?>
</script>

<script>
    window.webuiVbotClientConfig = {
        devicesFilePath: <?php
            echo json_encode(
                $directory_path.'/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json',
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        ?>
    };
    window.loadWebuiVbotClientModule = function() {
        if (window.webuiVbotClientModulePromise) {
            return window.webuiVbotClientModulePromise;
        }
        window.webuiVbotClientModulePromise = new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            script.src = 'assets/js/webui-vbot-client.js?v=<?php echo rawurlencode((string)$Cache_UI_Ver); ?>';
            script.onload = resolve;
            script.onerror = function() {
                window.webuiVbotClientModulePromise = null;
                show_message('Không thể tải module quản lý VBot Client');
                reject(new Error('Không thể tải webui-vbot-client.js'));
            };
            document.head.appendChild(script);
        });
        return window.webuiVbotClientModulePromise;
    };
    window.runWebuiVbotClientAction = function(actionName) {
        var actionArguments = Array.prototype.slice.call(arguments, 1);
        return window.loadWebuiVbotClientModule().then(function() {
            if (typeof window[actionName] !== 'function') {
                throw new Error('Không tìm thấy chức năng: ' + actionName);
            }
            return window[actionName].apply(window, actionArguments);
        }).catch(function(error) {
            show_message('Lỗi module VBot Client: ' + error.message);
        });
    };

    window.webuiLogConfig = {
        apiUrl: <?php echo json_encode($URL_API_VBOT, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    };
    window.loadWebuiLogModule = function() {
        if (window.webuiLogModulePromise) {
            return window.webuiLogModulePromise;
        }
        window.webuiLogModulePromise = new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            script.src = 'assets/js/webui-logs.js?v=<?php echo rawurlencode((string)$Cache_UI_Ver); ?>';
            script.onload = resolve;
            script.onerror = function() {
                window.webuiLogModulePromise = null;
                show_message('Không thể tải module xem logs');
                reject(new Error('Không thể tải webui-logs.js'));
            };
            document.head.appendChild(script);
        });
        return window.webuiLogModulePromise;
    };
    document.addEventListener('change', function(event) {
        if (
            event.target
            && (event.target.id === 'fetchLogsCheckbox' || event.target.id === 'fetchLogsCheckbox_Head')
            && typeof window.initLogViewer !== 'function'
        ) {
            window.loadWebuiLogModule();
        }
    }, true);

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
		var elements = document.querySelectorAll('label, button, font, a.list-group-item.list-group-item-action, td.lib_pip');
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
			target.style.backgroundColor = '#c8e6c9';
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
