<?php 
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
  #Facebook: https://www.facebook.com/TWFyaW9uMDAx
  include 'Configuration.php';
  $URL_Address = dirname($Current_URL);
  $parsedUrl = parse_url($Github_Repo_Vbot);
  $pathParts = explode('/', trim($parsedUrl['path'], '/'));
  $git_username = $pathParts[0];
  $git_repository = $pathParts[1];
  ?>
<!-- Vendor JS Files -->
<script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/chart.js/chart.umd.js"></script>
<script src="assets/vendor/echarts/echarts.min.js"></script>
<script src="assets/vendor/quill/quill.js"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>
<!-- Thông báo -->
<script src="assets/vendor/jquery/jquery-3.5.1.min.js"></script>
<script src="assets/vendor/popper/popper.min.js"></script>
<script src="assets/vendor/hls/hls.js"></script>
<!--END Thông báo -->
<script>
  const themeToggle = document.getElementById('themeToggle');

  function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('vbot_theme', theme);
    
    if (themeToggle) {
      if (theme === 'dark') {
        themeToggle.classList.remove('bi-moon-stars-fill');
        themeToggle.innerHTML = '<i class="bi bi-sun"></i> Chế độ sáng';
      } else {
        themeToggle.classList.remove('bi-sun');
        themeToggle.innerHTML = '<i class="bi bi-moon-stars-fill"></i> Chế độ tối';
      }
    }
  }

  function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
  }

  const savedTheme = localStorage.getItem('vbot_theme') || 'light';
  setTheme(savedTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
  }
</script>

<!-- Template Main JS File -->
<script src="assets/js/main.js"></script>
<script>
  //Chạy khi trang đã tải xong
  window.onload = setInterval(updateTime, 1000);
  function updateTime() {
      var d = new Date();
      var hour = d.getHours();
      var min = d.getMinutes();
      var sec = d.getSeconds();
	  if (document.getElementById("times")) {
		  document.getElementById('times').innerHTML = formatTime(hour) + ':' + formatTime(min) + ':' + formatTime(sec);
		}
  	//console.log(formatTime(hour) + ":" + formatTime(min) + ":" + formatTime(sec))
  }

  function formatTime(unit) {
      return unit < 10 ? "0" + unit : unit;
  }

  // Cập nhật ngày và thứ chỉ một lần khi trang tải
  function updateDate() {
      var d = new Date();
      var date = d.getDate();
      var month = d.getMonth();
      //var montharr = ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"];
      var montharr = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
      var year = d.getFullYear();
      var day = d.getDay();
      var dayarr = ["Chủ Nhật", "Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7"];
	  
	if (document.getElementById("days")) {
	  document.getElementById("days").innerHTML = dayarr[day];
	}

	if (document.getElementById("dates")) {
	  document.getElementById("dates").innerHTML = date + "/" + montharr[month] + "/" + year;
	}
  }
  //Cập nhật ngày tháng khi trang tải xong
  document.addEventListener('DOMContentLoaded', function() {
      // Lần cập nhật đầu tiên sau 1 giây
      updateDate();
      // Lần cập nhật thứ hai sau 3 giây (cách lần đầu tiên 2 giây)
      setTimeout(updateDate, 2000);
  });
  
  // Hàm để hiển thị hoặc ẩn overlay
  function loading(action) {
      const overlay = document.getElementById('loadingOverlay');
      if (action === 'show') {
          overlay.style.display = 'flex';
      } else if (action === 'hide') {
          overlay.style.display = 'none';
      }
  }
  loading("hide");
</script>
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
              }else if (langg === "snowboy") {
                  loadConfigHotword("snowboy")
              }else if (langg === "scan_Music_Local") {
                  list_audio_show_path('scan_Music_Local')
              } else if (langg === "Vbot_Backup_Program") {
  				if (document.getElementById("show_all_file_folder_Backup_Program")) {
                      show_all_file_in_directory('<?php echo $HTML_VBot_Offline . '/' . $Backup_Dir_Save_VBot; ?>', 'Tệp Sao Lưu Chương Trình Trên Hệ Thống', 'show_all_file_folder_Backup_Program');
                  }
  				else if (document.getElementById("show_all_file_folder_Backup_web_interface")) {
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
  					showMessagePHP('Không tìm thấy phần tử có id là: '+resultDiv_Id+' để hiển thị kết quả.');
                      return;
                  }
                  if (response.success) {
                      showMessagePHP(response.message);
                      //console.log(response);
                      // Tạo bảng để hiển thị thông tin tệp
                      var table = '<table class="table table-bordered border-primary">';
  					table += '<tr><th colspan="5" class="text-primary" style="text-align: center; vertical-align: middle;">'+source_backup+'</th></tr>';
                      table += '<tr><th style="text-align: center; vertical-align: middle;">STT</th><th style="text-align: center; vertical-align: middle;">Tên tệp</th><th style="text-align: center; vertical-align: middle;">Thời gian tạo</th><th style="text-align: center; vertical-align: middle;">Kích thước</th><th style="text-align: center; vertical-align: middle;">Hành động</th></tr>';
                      response.data.forEach(function(file, index) {
                          table += '<tr>';
                          table += '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>'; // STT
                          table += '<td style="text-align: center; vertical-align: middle;">' + file.name + '</td>'; // Tên tệp
                          table += '<td style="text-align: center; vertical-align: middle;">' + file.created_at + '</td>'; // Thời gian tạo
                          table += '<td style="text-align: center; vertical-align: middle;">' + file.size + '</td>'; // Kích thước
                          table += '<td style="text-align: center; vertical-align: middle;">';
  						table += '<form method="POST" action=""><button type="submit" onclick="return confirmRestore(\'Bạn có chắc chắn muốn khôi phục dữ liệu từ bản sao lưu trên hệ thống: ' + file.name +'\')" name="Restore_Backup" value="' + file.path + '" class="btn btn-primary" title="Khôi phục dữ liệu: ' + file.name + '"><i class="bi bi-arrow-counterclockwise" title="Khôi phục dữ liệu: ' + file.name + '"></i></button> </form> ';
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
      var url = 'includes/php_ajax/GCloud_Act.php?Scan&Folder_Name='+folder_name;
      xhr.open('GET', url, true);
      xhr.onreadystatechange = function() {
          if (xhr.readyState === XMLHttpRequest.DONE) {
              if (xhr.status === 200) {
  				loading("hide");
                  var response = JSON.parse(xhr.responseText);
                  var resultDiv_show_all_File = document.getElementById(resultDiv_Id); // Sử dụng ID được truyền vào
                  if (!resultDiv_show_all_File) {
  					showMessagePHP('Không tìm thấy phần tử có id là: '+resultDiv_Id+' để hiển thị kết quả.');
                      return;
                  }
                  if (response.success) {
                      showMessagePHP(response.message, 3);
                      //console.log(response);
                      // Tạo bảng để hiển thị thông tin tệp
                      var table = '<table class="table table-bordered border-primary">';
  					table += '<tr><th colspan="5" class="text-primary"><center>'+source_backup+'</center></th></tr>';
                      table += '<tr><th><center>STT</center></th><th><center>Tên tệp</center></th><th><center>Thời gian tạo</center></th><th><center>Kích thước</center></th><th><center>Hành động</center></th></tr>';
                      response.data.forEach(function(file, index) {
                          table += '<tr>';
                          table += '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>'; // STT
                          table += '<td style="text-align: center; vertical-align: middle;">' + file.name + '</td>'; // Tên tệp
                          table += '<td style="text-align: center; vertical-align: middle;">' + file.created_at + '</td>'; // Thời gian tạo
                          table += '<td style="text-align: center; vertical-align: middle;">' + file.size + '</td>'; // Kích thước
                          table += '<td style="text-align: center; vertical-align: middle;"><form method="POST" action=""><button type="submit" onclick="return confirmRestore(\'Bạn có chắc chắn muốn khôi phục dữ liệu từ bản sao lưu trên Google Cloud Drive: ' + file.name +'\')" name="Restore_Backup" value="' + file.url_share + '" class="btn btn-success" title="Khôi phục dữ liệu: ' + file.name + '"><i class="bi bi-arrow-counterclockwise" title="Khôi phục dữ liệu: ' + file.name + '"></i></button> </form>';
  						table += '<a href="'+file.url_share+'" target="_bank" title="Mở  trong tab mới: ' + file.name + '"> <button type="button" class="btn btn-success" title="Mở trong tab mới: ' + file.name + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
  						table += ' <button type="button" class="btn btn-danger" onclick="deleteFile_gcloud(\'' + file.id + '\', \''+file.name+'\', \''+folder_name+'\', \'Tệp Sao Lưu Chương Trình Trên Google Cloud Drive\', \''+resultDiv_Id+'\')"><i class="bi bi-trash"></i></button> </td>';
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
      var url = 'includes/php_ajax/GCloud_Act.php?Delete&id_file='+gcloud_id_file;
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
  
  //Hiển thị thông báo xác nhận với thông tin chi tiết về backup
  function confirmRestore(backup_file_name) {
      var result = confirm(backup_file_name);
      if (result) {
          loading('show');
      }
      return result;
  }
  
  //Coppy dữ liệu trong thẻ input
  function coppy_value(id_input) {
      var input = document.getElementById(id_input);
      input.select();
      try {
          document.execCommand("copy");
          showMessagePHP("Đã Sao Chép!", 3);
      } catch (err) {
          show_message("Lỗi khi sao chép nội dung. Vui lòng thử lại.");
      }
  }
  
  //Mở đường dẫn trong tab mới
  function openNewTab(url_link) {
      if (url_link) {
          window.open(url_link, '_blank');
      } else {
          show_message('Không có đường dẫn được cung cấp');
      }
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
  					table += '<tr><th colspan="3"  class="text-success"><center>Cấu Trúc Tệp: '+fileName+'</center></th></tr>';
                      table += '<tr><th><center>STT</center></th><th><center>Tên tệp</center></th><th><center>Hành động</center></th></tr>';
                      // Duyệt qua danh sách các tệp trong response.data
                      response.data.forEach(function(file, index) {
                          table += '<tr>';
                          table += '<td style="text-align: center; vertical-align: middle;">' + (index + 1) + '</td>';
                          table += '<td style="vertical-align: middle;"><font color=blue>' + file + '</font></td>';
                          table += '<td style="text-align: center; vertical-align: middle;">';
                          // Hành động: Bạn có thể thêm các nút hoặc liên kết hành động tại đây
                          table += '<button type="button" class="btn btn-success" onclick="read_files_in_backup(\''+path_backup_file+'\', \'' + file + '\')" title="Xem nội dung tệp tin: \''+file+'\'"><i class="bi bi-eye"></i> Xem</button>';
                          table += '</td>';
                          table += '</tr>';
                      });
                      table += '</table>';
  				if (document.getElementById('show_all_file_folder_Backup_Program')) {
                      document.getElementById('show_all_file_folder_Backup_Program').innerHTML = table;
                  }else if (document.getElementById('show_all_file_folder_Backup_web_interface')){
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
      var url = 'includes/php_ajax/Show_file_path.php?read_files_in_backup&file_path='+encodeURIComponent(file_path)+'&file_name='+ encodeURIComponent(file_name);
      var xhr = new XMLHttpRequest();
      xhr.open('GET', url);
      xhr.onload = function() {
          if (xhr.status === 200) {
              try {
                  var response = JSON.parse(xhr.responseText);
                  if (response.success) {
  					loading('hide');
                      // Kiểm tra nếu tệp là JSON
                      if (file_name.endsWith('.json')) {
  						// Hiển thị JSON với indent
                          document.getElementById('modal-body-content').textContent = JSON.stringify(response.data, null, 2);
                      } else {
                          // Làm sạch nội dung tệp khác
                          var fileContent = response.data
                              .replace(/\\r/g, '') // Xóa ký tự \r
                              .replace(/\\n/g, '\n'); // Thay thế \n bằng ký tự xuống dòng thực
  							// Cập nhật nội dung
  							//document.getElementById('modal-body-content').textContent = fileContent; 
  						    // Cập nhật nội dung cho modal
  							var modalContentElement = document.getElementById('modal-body-content');
  							modalContentElement.textContent = fileContent;
  							// Thêm class để Prism.js làm nổi bật cú pháp JSON
  							modalContentElement.className = 'language-yaml'; 
  							// Kích hoạt Prism.js để làm nổi bật cú pháp
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
      if (!confirm("Bạn có chắc chắn muốn thực thi lệnh:\n$:> "+atob(b64_encode))) {
          return;
      }
      loading('show');
      var xhr = new XMLHttpRequest();
      xhr.open("GET", 'includes/php_ajax/Check_Connection.php?VBot_CMD&Command=' + encodeURIComponent(b64_encode));
      xhr.onreadystatechange = function () {
          if (xhr.readyState === 4) {
              if (xhr.status === 200) {
                  try {
                      loading('hide');
                      var result = JSON.parse(xhr.responseText);
                      if (result.success) {
  						var formattedData = result.data.replace(/\n/g, "<br/>");
                          show_message("<font color=blue>"+result.message+"</font><br/><center><b>Dữ Liệu Trả Về</b></center><hr/><font color=green>" +formattedData+ "</font>");
                      } else {
                          show_message("Yêu cầu không thành công: " +result.message);
                      }
                  } catch (e) {
                      loading('hide');
                      show_message("Lỗi khi phân tích phản hồi JSON: " +e);
                  }
              } else {
                  loading('hide');
                  show_message("Yêu cầu không thành công với mã trạng thái: " +xhr.status);
              }
          }
      };
      xhr.onerror = function () {
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
          // Thêm tất cả các file vào FormData
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
  						//nếu có id local-tab trong DOM  thì sẽ chạy
                          if (typeof media_player_search === 'function') {
  							//Tab MediaPlayer.php
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
                  show_message("Lỗi kết nối, Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng, API: http status" + xhr.status);
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
              // Nếu thành công, trả về đường dẫn audio
              resolve(responseData.data.audio_link);
            } else {
              // Nếu thất bại, trả về lỗi
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
  
  // Hàm để phát nhạc (Media Player)
  function send_Media_Play_API(url_media, name_media = "", url_cover = "<?php echo $URL_Address; ?>/assets/img/icon_audio_local.png", media_source = "N/A") {
      loading("show");
      // Kiểm tra nếu URL bắt đầu với các domain cần tìm
      if (url_media.startsWith("https://baomoi.com/") || url_media.startsWith("https://tienphong.vn/") || url_media.startsWith("https://vietnamnet.vn/")) {
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
  
  // Hàm khởi tạo phát nhạc
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
                  show_message("Lỗi HTTP: " + this.status, this.statusText);
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
      var url = 'includes/php_ajax/Media_Player_Search.php?ZingMP3_GetLink&Zing_ID='+zing_id;
      xhr.open('GET', url, true);
      xhr.onreadystatechange = function() {
          if (xhr.readyState === 4 && xhr.status === 200) {
              try {
                  var data = JSON.parse(xhr.responseText);
                  //console.log(data);
                  if (data.success == true) {
  					startMediaPlayer(data.url, zing_name+' - '+zing_artist, zing_cover, 'ZingMP3');
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
  function get_Youtube_Link(youtube_id, youtube_name=null, youtube_cover=null) {
  	
  	if (youtube_id === null || youtube_id === "N/A") {
      // Trả về hoặc xử lý khi giá trị là null hoặc "N/A"
  	show_message("Lỗi, không lấy được ID hoặc ID của Video Youtube này không hợp lệ");
  	return;
  	}
  	loading("show");
      var xhr = new XMLHttpRequest();
      var url = 'includes/php_ajax/Media_Player_Search.php?GetLink_Youtube&Youtube_ID='+youtube_id;
      xhr.open('GET', url, true);
      xhr.onreadystatechange = function() {
          if (xhr.readyState === 4 && xhr.status === 200) {
              try {
                  var data = JSON.parse(xhr.responseText);
                  //console.log(data);
                  if (data.success == true) {
  					if (youtube_name == null) {
  						youtube_name = data.data.title;
  					}
  					startMediaPlayer(data.data.dlink, youtube_name, youtube_cover, 'Youtube');
                  } else {
  					loading("hide");
                      show_message('Yêu cầu không thành công, Không lấy được link Player hoặc bạn có thể thử lại');
                  }
              } catch (e) {
  				loading("hide");
  				console.log(xhr.responseText);
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
      // Chỉ thực hiện nếu radio button được checked
      if (actionValue) {
          //console.log("Giá trị đã chọn:", dataKey);
          // Tạo dữ liệu JSON cho yêu cầu POST
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
                      //document.getElementById('delete_log_api').checked = false;
                      const deleteLogCheckbox = document.getElementById('delete_log_api');
                      if (deleteLogCheckbox) {
                          // Nếu phần tử tồn tại, đặt giá trị checked thành false
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
  
  // Hàm kiểm tra và thay đổi nút tìm kiếm trong tab Media Player
  function checkInput_MediaPlayer() {
      const inputField = document.getElementById('song_name_value');
      const actionButton = document.getElementById('actionButton_Media');
      const inputValue = inputField.value.trim();
      if (inputValue.startsWith('http')) {
          // Thay đổi biểu tượng của nút thành biểu tượng Play URL
          actionButton.innerHTML = '<i class="bi bi-play-circle" title="Phát bằng địa chỉ URL"></i>';
          actionButton.setAttribute('onclick', 'media_player_url()');
      } else {
          // Đặt lại biểu tượng của nút thành biểu tượng Tìm Kiếm
          actionButton.innerHTML = '<i class="bi bi-search" title="Tìm kiếm"></i>';
          actionButton.setAttribute('onclick', 'media_player_search()');
          if (document.getElementById('select_cache_media')) {
              // Lấy giá trị của thẻ <select>
              const selectedValue = document.getElementById('select_cache_media').value;
              // Cập nhật onclick dựa trên giá trị của thẻ <select>
              if (selectedValue === 'Youtube') {
                  actionButton.setAttribute('onclick', 'media_player_search("Youtube")');
              } else if (selectedValue === 'ZingMP3') {
                  actionButton.setAttribute('onclick', 'media_player_search("ZingMP3")');
              } else if (selectedValue === 'PodCast') {
                  actionButton.setAttribute('onclick', 'media_player_search("PodCast")');
              } else {
                  actionButton.setAttribute('onclick', 'media_player_search()'); // Giá trị mặc định nếu không khớp
              }
  
          }
      }
  }
  		
  // Hàm trích xuất ID video từ URL YouTube
  function extractYouTubeId(url) {
      const youtubeRegex = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|v\/|.+\?v=)|youtu\.be\/)([^"&?\/\s]{11})/;
      const match = url.match(youtubeRegex);
      return match ? match[1] : null;
  }
  
  // Hàm xử lý phát URL http từ tab Media Player
  function media_player_url() {
      // Danh sách các đuôi âm thanh phổ biến
      const audioExtensions = ['.mp3', '.wav', '.ogg', '.flac', '.aac', '.m3u8'];
      const inputField = document.getElementById('song_name_value');
      const url = inputField.value.trim();
      // Chuyển URL về chữ thường
      link_url = url.toLowerCase();
  	// Kiểm tra xem URL có phải là một tệp âm thanh hoặc bắt đầu bằng https://rr2
  	const isAudio = audioExtensions.some(function(ext) {
  		return link_url.endsWith(ext);
  	}) || link_url.startsWith('http');
      //Xử lý URL Youtube
      if (link_url.startsWith('https://www.youtube.com') || link_url.startsWith('https://youtu.be')) {
          // Lấy ID từ URL YouTube
          const videoId = extractYouTubeId(url);
          if (videoId) {
              //alert('ID YouTube: ' + videoId);
              get_Youtube_Link(videoId, null, null);
          } else {
              show_message('URL YouTube không hợp lệ.');
          }
      }
  	
  
      //Xử lý URL Zingmp3
      /*
  	else if (url.startsWith('https://zingmp3.vn')) {
                  // Lấy ID từ URL Zing MP3
                  const zingId = extractZingMp3Id(url);
                  if (zingId) {
                      alert('ID Zing MP3: ' + zingId);
                      // Xử lý ID Zing MP3 ở đây
                      // window.open(url, '_blank');
                  } else {
                      show_message('URL Zing MP3 không hợp lệ.');
                  }
              }
  			*/
      //Nếu đường link, url có đuôi tệp cuối dùng
      else if (isAudio) {
          // Biến để lưu thông tin tệp
          var fileName = "";
          var fileExtension = "";
          // Kiểm tra đuôi tệp âm thanh
          for (var i = 0; i < audioExtensions.length; i++) {
              if (link_url.endsWith(audioExtensions[i])) {
                  fileExtension = audioExtensions[i];
                  // Lấy tên bài hát (tên tệp mà không có đuôi)
                  fileName = link_url.substring(link_url.lastIndexOf('/') + 1, link_url.lastIndexOf(fileExtension));
                  break; // Thoát khỏi vòng lặp khi đã tìm thấy đuôi
              }
          }
          // Kiểm tra và hiển thị thông tin
          if (fileExtension) {
              startMediaPlayer(url, fileName + '' + fileExtension, 'assets/img/icon_audio_local.png', 'Local');
          } else {
              startMediaPlayer(url, null, 'assets/img/icon_audio_local.png', 'Local');
          }
      } else {
          show_message('URL không hợp lệ hoặc không phải là nguồn nhạc được hỗ trợ.');
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
						  fileInfo += ' <button class="btn btn-warning" title="Tải Xuống: '+cache_ZING.name+'" onclick="dowload_ZingMP3_ID(\'' + cache_ZING.id + '\', \''+cache_ZING.name+'\')"><i class="bi bi-download"></i></button>';
						  fileInfo += ' <button class="btn btn-info" title="Tải Vào Thư Mục Local: '+cache_ZING.name+'" onclick="download_zingMp3_to_local(\'' + cache_ZING.id + '\', \''+cache_ZING.name+'\')"><i class="bi bi-save2"></i></button>';
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
                      // Lắng nghe sự kiện thay đổi trong input tìm kiếm bài hát
                      setTimeout(function() {
                          if (document.getElementById('song_name_value')) {
                              document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);
                          }
                      }, 0);
                  }
                  // Parse dữ liệu JSON
                  var data = JSON.parse(xhr.responseText);
                  // Kiểm tra xem dữ liệu có phải là mảng và có phần tử không
                  if (Array.isArray(data.data) && data.data.length > 0) {
                      fileListDiv.innerHTML += 'Xóa dữ liệu Cache: <button class="btn btn-danger" title="Xóa dữ liệu cache PodCast" onclick="cache_delete(\'PodCast\')"><i class="bi bi-trash"></i> Xóa</button><br/>';
                      // Xử lý và hiển thị từng podcast
                      data.data.forEach(function(podcast) {
                          var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                          fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                          fileInfo += '<img src="' + podcast.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                          fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + podcast.title + '</font></p>';
                          fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (podcast.duration || 'N/A') + '</font></p>';
                          fileInfo += '<p style="margin: 0;">Thể Loại: <font color=green>' + (podcast.description || 'N/A') + '</font></p>';
                          fileInfo += '<button class="btn btn-success" title="Phát: ' + podcast.title + '" onclick="startMediaPlayer(\'' + podcast.audio + '\', \'' + podcast.title + '\', \'' + podcast.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                          fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + podcast.title + '" onclick="addToPlaylist(\'' + podcast.title + '\', \'' + podcast.cover + '\', \'' + podcast.audio + '\', \'' + (podcast.duration || 'N/A') + '\', \'' + (podcast.description || 'N/A') + '\', \'PodCast\', \'' + podcast.audio + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
                          fileInfo += ' <button class="btn btn-warning" title="Tải Xuống: '+podcast.title+'" onclick="download_AUDIO_URL(\'' + podcast.audio + '\', \''+podcast.title+'\')"><i class="bi bi-download"></i></button>';
						  fileInfo += ' <button class="btn btn-danger" title="Tải Vào Thư Mục Local: '+podcast.title+'" onclick="download_Link_url_to_local(\'' + podcast.audio + '\', \''+podcast.title+'\')"><i class="bi bi-save2"></i></button>';
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
                      // Xử lý và hiển thị từng podcast
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
                          // Thêm thông tin vào phần tử danh sách
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
                      // Lắng nghe sự kiện thay đổi trong input tìm kiếm bài hát
                      setTimeout(function() {
                          if (document.getElementById('song_name_value')) {
                              document.getElementById('song_name_value').addEventListener('input', checkInput_MediaPlayer);
                          }
                      }, 0);
                  }
                  var data = JSON.parse(xhr.responseText);
                  // Kiểm tra xem dữ liệu có phải là mảng và có phần tử không
                  if (Array.isArray(data.data) && data.data.length > 0) {
                      fileListDiv.innerHTML += 'Xóa dữ liệu Cache: <button class="btn btn-danger" title="Xóa dữ liệu cache Youtube" onclick="cache_delete(\'Youtube\')"><i class="bi bi-trash"></i> Xóa</button><br/>';
                      // Xử lý và hiển thị từng dữ liệu
                      data.data.forEach(function(youtube) {
                          var description = youtube.description.length > 70 ? youtube.description.substring(0, 70) + '...' : youtube.description;
                          var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                          fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
                          fileInfo += '<img src="' + youtube.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
                          fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + youtube.title + '</font></p>';
                          fileInfo += '<p style="margin: 0;">Kênh: <font color=green>' + (youtube.channelTitle || 'N/A') + '</font></p>';
                          fileInfo += '<p style="margin: 0;">Mô tả: <font color=green>' + (description || 'N/A') + '</font></p>';
                          fileInfo += '<button class="btn btn-success" title="Phát: ' + youtube.title + '" onclick="get_Youtube_Link(\'' + youtube.id + '\', \'' + youtube.title + '\', \'' + youtube.cover + '\')"><i class="bi bi-play-circle"></i></button>';
                          fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + youtube.title + '" onclick="addToPlaylist(\'' + youtube.title + '\', \'' + youtube.cover + '\', \'https://www.youtube.com/watch?v=' + youtube.id + '\', null, \'' + (description || 'N/A') + '\', \'Youtube\', \'' + youtube.id + '\', \'' + (youtube.channelTitle || 'N/A') + '\', null)"><i class="bi bi-music-note-list"></i></button>';
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
                      showMessagePHP("Đã thêm " +title+ " vào PlayList thành công!");
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
      // Lấy phần tử theo ID
      var searchInputElement = document.getElementById('song_name_value');
      let searchInput = null;
      // Kiểm tra nếu phần tử tồn tại
      if (searchInputElement) {
          // Nếu phần tử tồn tại, lấy giá trị và loại bỏ khoảng trắng
          searchInput = searchInputElement.value.trim();
      } else {
          searchInput = null;
      }
      // Kiểm tra nếu select_name không có giá trị, thì tìm nút đang được chọn
      if (!select_name) {
          // Lấy tất cả các thẻ <button> trong danh sách
          const buttons = document.querySelectorAll('#select_source_media_music .nav-link');
          buttons.forEach(button => {
              if (button.classList.contains('active')) {
                  // Lấy giá trị của thuộc tính name của thẻ <button> đang được chọn
                  select_name = button.getAttribute('name');
              }
          });
      }
      // Kiểm tra xem select_name có giá trị hợp lệ không
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
      // Kiểm tra nếu trường nhập liệu rỗng và nguồn không phải là Local hoặc Radio
      if (searchInput === '' && (select_name !== 'Local' && select_name !== 'Radio')) {
          loading("hide");
          show_message('Cần nhập tên bài hát để tìm kiếm.');
          return;
      }
      var xhr = new XMLHttpRequest();
      // Cấu hình URL dựa trên giá trị select_name
      var url;
      switch (select_name) {
          case 'Local':
              url = 'includes/php_ajax/Media_Player_Search.php?Local';
              break;
          case 'ZingMP3':
              url = 'includes/php_ajax/Media_Player_Search.php?ZingMP3_Search&SongName=' + searchInput;
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
                  // Xử lý dữ liệu theo từng giá trị select_name
                  switch (select_name) {
                      case 'Local':
                          processLocalData(data);
                          break;
                      case 'ZingMP3':
                          processZingMP3Data(data);
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
      // Xử lý lỗi mạng
      xhr.onerror = function() {
          loading("hide");
          show_message('Lỗi mạng');
      };
      // Gửi yêu cầu
      xhr.send();
  }
  
  
  // Hàm xử lý dữ liệu ZingMP3
  function processZingMP3Data(data_media_ZingMP3) {
      var fileListDiv = document.getElementById('show_list_ZingMP3');
      if (!fileListDiv) {
          // Nếu không tồn tại, thay thế bằng phần tử với ID 'tableContainer' dành cho index.php
          fileListDiv = document.getElementById('tableContainer');
      }
      // Kiểm tra xem dữ liệu có rỗng không
      if (!data_media_ZingMP3 || !Array.isArray(data_media_ZingMP3.results) || data_media_ZingMP3.results.length === 0) {
          show_message('<p>Không có dữ liệu bài hát tương ứng với từ khóa trên ZingMP3</p>');
      } else {
          fileListDiv.innerHTML = '';
          // Kiểm tra và thêm phần tử input và button nếu chưa tồn tại
          if (!document.getElementById("song_name_value")) {
              fileListDiv.innerHTML += '<div class="input-group mb-3">' +
                  '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát" title="Nhập tên bài hát cần tìm kiếm" value="">' +
                  '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
                  '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'ZingMP3\')"><i class="bi bi-search"></i></button>' +
                  '<button type="button" class="btn btn-primary border-success" onclick="cacheZingMP3()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
          }
          data_media_ZingMP3.results.forEach(function(zing) {
              var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
              fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
              fileInfo += '<img src="' + zing.thumb + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
              fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + zing.name + '</font></p>';
              fileInfo += '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color=green>' + zing.artist + '</font></p>';
              fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (zing.duration || 'N/A') + '</font></p>';
              fileInfo += '<button class="btn btn-success" title="Phát: ' + zing.name + '" onclick="get_ZingMP3_Link(\'' + zing.id + '\', \'' + zing.name + '\', \'' + zing.thumb + '\', \'' + zing.artist + '\')"><i class="bi bi-play-circle"></i></button>';
              fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + zing.name + '" onclick="addToPlaylist(\'' + zing.name + '\', \'' + zing.thumb + '\', \'' + zing.id + '\', \'' + (zing.duration || 'N/A') + '\', null, \'ZingMP3\', \'' + zing.id + '\', null, \'' + zing.artist + '\')"><i class="bi bi-music-note-list"></i></button>';
              fileInfo += ' <button class="btn btn-warning" title="Tải Xuống: '+zing.name+'" onclick="dowload_ZingMP3_ID(\'' + zing.id + '\', \''+zing.name+'\')"><i class="bi bi-download"></i></button>';
			  fileInfo += ' <button class="btn btn-info" title="Tải Vào Thư Mục Local: '+zing.name+'" onclick="download_zingMp3_to_local(\'' + zing.id + '\', \''+zing.name+'\')"><i class="bi bi-save2"></i></button>';
			  fileInfo += '</div></div>';
              fileListDiv.innerHTML += fileInfo;
          });
      }
  }
  
  // Hàm xử lý dữ liệu hiển thị kết quả tìm kiếm Youtube
  function processYoutubeData(data_media_Youtube) {
      // Thay đổi cách hiển thị dữ liệu Youtube
      //console.log('Dữ liệu Youtube:', data);
      // Kiểm tra xem dữ liệu có hợp lệ không
      if (!data_media_Youtube || !Array.isArray(data_media_Youtube.data) || data_media_Youtube.data.length === 0) {
          show_message('<p>Không có dữ liệu bài hát từ Youtube</p>');
          return; // Kết thúc hàm nếu không có dữ liệu
      }
      // Lấy phần tử DOM để hiển thị danh sách youtube
      var fileListDiv = document.getElementById('show_list_Youtube');
      if (!fileListDiv) {
          // Nếu không tồn tại, thay thế bằng phần tử với ID 'tableContainer' dành cho index.php
          fileListDiv = document.getElementById('tableContainer');
          //fileListDiv.innerHTML = '';
      }
      // Xóa nội dung cũ
      fileListDiv.innerHTML = '';
      // Kiểm tra và thêm phần tử input và button nếu chưa tồn tại
      if (!document.getElementById("song_name_value")) {
          fileListDiv.innerHTML += '<div class="input-group mb-3">' +
              '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát hoặc nhập url/link Youtube" title="Nhập tên bài hát cần tìm kiếm hoặc nhập url/link Youtube" value="">' +
              '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
              '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'Youtube\')"><i class="bi bi-search"></i></button>' +
              '<button type="button" class="btn btn-primary border-success" onclick="cacheYoutube()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
      }
      // Xử lý và hiển thị từng youtube
      data_media_Youtube.data.forEach(function(youtube) {
          var description = youtube.description.length > 70 ? youtube.description.substring(0, 70) + '...' : youtube.description;
          var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
          fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
          fileInfo += '<img src="' + youtube.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
          fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + youtube.title + '</font></p>';
          fileInfo += '<p style="margin: 0;">Kênh: <font color=green>' + (youtube.channelTitle || 'N/A') + '</font></p>';
          fileInfo += '<p style="margin: 0;">Mô tả: <font color=green>' + (description || 'N/A') + '</font></p>';
          fileInfo += ' <button class="btn btn-success" title="Phát: ' + youtube.title + '" onclick="get_Youtube_Link(\'' + youtube.id + '\', \'' + youtube.title + '\', \'' + youtube.cover + '\')"><i class="bi bi-play-circle"></i></button>';
          fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + youtube.title + '" onclick="addToPlaylist(\'' + youtube.title + '\', \'' + youtube.cover + '\', \'https://www.youtube.com/watch?v=' + youtube.id + '\', null, \'' + (description || 'N/A') + '\', \'Youtube\', \'' + youtube.id + '\', \'' + (youtube.channelTitle || 'N/A') + '\', null)"><i class="bi bi-music-note-list"></i></button>';
          fileInfo += ' <a href="https://www.youtube.com/watch?v=' + youtube.id + '" target="_bank"><button class="btn btn-info" title="Mở trong tab mới: ' + youtube.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
          fileInfo += '</div></div>';
          // Thêm thông tin vào phần tử danh sách
          fileListDiv.innerHTML += fileInfo;
      });
  }

  function processPodCastData(data_media_PodCast) {
      //console.log(data);
      // Kiểm tra xem dữ liệu có hợp lệ không
      if (!data_media_PodCast || !Array.isArray(data_media_PodCast.data) || data_media_PodCast.data.length === 0) {
          show_message('<p>Không có dữ liệu bài hát từ PodCast</p>');
          return;
      }
      // Lấy phần tử DOM để hiển thị danh sách podcast
      var fileListDiv = document.getElementById('show_list_PodCast');
      if (!fileListDiv) {
          // Nếu không tồn tại, thay thế bằng phần tử với ID 'tableContainer' dành cho index.php
          fileListDiv = document.getElementById('tableContainer');
      }
      fileListDiv.innerHTML = '';
      // Kiểm tra và thêm phần tử input và button nếu chưa tồn tại
      if (!document.getElementById("song_name_value")) {
          fileListDiv.innerHTML += '<div class="input-group mb-3">' +
              '<input required class="form-control border-success" type="text" name="song_name" id="song_name_value" placeholder="Tìm kiếm bài hát" title="Nhập tên bài hát cần tìm kiếm" value="">' +
              '<div class="invalid-feedback">Cần nhập tên bài hát cần tìm kiếm</div>' +
              '<button id="actionButton_Media" title="Tìm kiếm" class="btn btn-success border-success" type="button" onclick="media_player_search(\'PodCast\')"><i class="bi bi-search"></i></button>' +
              '<button type="button" class="btn btn-primary border-success" onclick="cachePodCast()" title="Tải lại dữ liệu Cache"><i class="bi bi-arrow-repeat"></i></button></div>';
      }
      // Xử lý và hiển thị từng podcast
      data_media_PodCast.data.forEach(function(podcast) {
          var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
          fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
          fileInfo += '<img src="' + podcast.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
          fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: <font color=green>' + podcast.title + '</font></p>';
          fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (podcast.duration || 'N/A') + '</font></p>';
          fileInfo += '<p style="margin: 0;">Thể Loại: <font color=green>' + (podcast.description || 'N/A') + '</font></p>';
          fileInfo += '<button class="btn btn-success" title="Phát: ' + podcast.title + '" onclick="startMediaPlayer(\'' + podcast.audio + '\', \'' + podcast.title + '\', \'' + podcast.cover + '\')"><i class="bi bi-play-circle"></i></button>';
          fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + podcast.title + '" onclick="addToPlaylist(\'' + podcast.title + '\', \'' + podcast.cover + '\', \'' + podcast.audio + '\', \'' + (podcast.duration || 'N/A') + '\', \'' + (podcast.description || 'N/A') + '\', \'PodCast\', \'' + podcast.audio + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
          fileInfo += ' <button class="btn btn-warning" title="Tải Xuống: '+podcast.title+'" onclick="download_AUDIO_URL(\'' + podcast.audio + '\', \''+podcast.title+'\')"><i class="bi bi-download"></i></button>';
		  fileInfo += ' <button class="btn btn-danger" title="Tải Vào Thư Mục Local: '+podcast.title+'" onclick="download_Link_url_to_local(\'' + podcast.audio + '\', \''+podcast.title+'\')"><i class="bi bi-save2"></i></button>';
		  fileInfo += ' <a href="' + podcast.audio + '" target="_blank"><button class="btn btn-info" title="Mở trong tab mới: ' + podcast.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
          fileInfo += '</div></div>';
          // Thêm thông tin vào phần tử danh sách
          fileListDiv.innerHTML += fileInfo;
      });
  }

  // Hàm xử lý dữ liệu media Local
  function processLocalData(data_media_local) {
      var fileListDiv = document.getElementById('show_list_media_local');
      if (!fileListDiv) {
          // Nếu không tồn tại, thay thế bằng phần tử với ID 'tableContainer' dành cho index.php
          fileListDiv = document.getElementById('tableContainer');
      }
      //console.log(data)
      // Kiểm tra xem dữ liệu có rỗng không
      if (!data_media_local || data_media_local.length === 0) {
          show_message('<p>Không có dữ liệu bài hát Local</p>');
      } else {
          fileListDiv.innerHTML = '';
          if (!document.getElementById('upload_Music_Local')) {
              fileListDiv.innerHTML = '<form enctype="multipart/form-data" method="POST" action="">' +
                  '<div class="input-group">' +
                  '<input class="form-control border-success" type="file" id="upload_Music_Local" multiple="">' +
                  '<button class="btn btn-success border-success" type="button" onclick="upload_File(\'upload_Music_Local\')">Tải Lên</button>' +
                  '<button type="button" class="btn btn-primary border-success" onclick="media_player_search(\'Local\')" title="Tải lại dữ liệu bài hát trong thư mục Local"><i class="bi bi-arrow-repeat"></i></button></div></form>';
          }
          data_media_local.forEach(function(file) {
              var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
              fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
              fileInfo += '<img src="' + file.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
              fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tên bài hát: ' + file.name + '</p>';
              fileInfo += '<p style="margin: 0;">Kích thước: ' + file.size + ' MB</p>';
              fileInfo += '<button class="btn btn-success" title="Phát: ' + file.name + '" onclick="startMediaPlayer(\'' + file.full_path + '\', \'' + file.name + '\', \'' + file.cover + '\', \'Local\')"><i class="bi bi-play-circle"></i></button> ';
              fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + file.name + '" onclick="addToPlaylist(\'' + file.name + '\', \'' + file.cover + '\', \'' + file.full_path + '\', \'' + file.size + ' MB\', null, \'Local\', \'' + file.full_path + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
              fileInfo += ' <button class="btn btn-warning" title="Tải Xuống File: ' + file.name + '" onclick="downloadFile(\'' + file.full_path + '\')"><i class="bi bi-download"></i></button>';
              fileInfo += ' <button class="btn btn-danger" title="Xóa File: ' + file.name + '" onclick="deleteFile(\'' + file.full_path + '\', \'media_player_search\')"><i class="bi bi-trash"></i></button>';
              fileInfo += '</div></div>';
              fileListDiv.innerHTML += fileInfo;
              adjustContainerStyle_tableContainer();
          });
      }
  }
  
  // Hàm xử lý dữ liệu Radio
  function processRadioData(data_media_Radio) {
      var fileListDiv = document.getElementById('show_list_Radio');
      if (!fileListDiv) {
          fileListDiv = document.getElementById('tableContainer');
      }
      fileListDiv.innerHTML = '';
      // Kiểm tra xem dữ liệu có tồn tại và là một mảng không
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
              //fileInfo += '<p style="margin: 0; font-weight: bold;">Nghệ sĩ: <font color=green>' + radio.artist + '</font></p>';
              fileInfo += '</div></div>';
              fileListDiv.innerHTML += fileInfo;
              adjustContainerStyle_tableContainer();
          });
      } else {
          show_message('Dữ liệu trả về không hợp lệ.');
      }
  }
  
  //gán onclick để sử dụng truyền đối số vào hàm fetchData_NewsPaper lấy dữ liệu báo, tin tức
  function get_data_newspaper() {
      var selectedValue = document.getElementById("news_paper").value;
      // Kiểm tra nếu giá trị được chọn không rỗng
      if (selectedValue) {
          //console.log("Giá trị được chọn: " + selectedValue);
  		fetchData_NewsPaper(selectedValue);
      } else {
  		showMessagePHP("Cần chọn trang Báo, Tin Tức để thực hiện", 3);
      }
  }
  
  //Lấy và hiển thị dữ liệu báo, tin tức từ trung gian php
  function fetchData_NewsPaper(newspaper_link) {
  	loading('show');
      var xhr = new XMLHttpRequest();
      var url = "includes/php_ajax/Media_Player_Search.php?newspaper&link="+newspaper_link;
      xhr.open("GET", url, true);
      xhr.onload = function () {
          if (xhr.status === 200) {
  			loading('hide');
              try {
                  var response = JSON.parse(xhr.responseText);
                  if (response.success) {
  					showMessagePHP(response.message, 3);
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
      // Xử lý và hiển thị từng podcast
  	fileListDiv.innerHTML += '<b>Phát tất cả:</b> <button class="btn btn-success" title="Phát toàn bộ" onclick="play_playlist_json_path(\'<?php echo $directory_path; ?>/includes/cache/<?php echo $Config['media_player']['news_paper']['newspaper_file_name']; ?>\')"><i class="bi bi-play-circle"></i></button>';
      response.data.forEach(function(news_paper) {
          var fileInfo = '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
          fileInfo += '<div style="flex-shrink: 0; margin-right: 15px;">';
          fileInfo += '<img src="' + news_paper.cover + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"></div>';
          fileInfo += '<div><p style="margin: 0; font-weight: bold;">Tiêu Đề: <font color=green>' + news_paper.title + '</font></p>';
          fileInfo += '<p style="margin: 0;">Thời Gian Tạo: <font color=green>' + (news_paper.publish_time || 'N/A') + '</font></p>';
          fileInfo += '<p style="margin: 0;">Thời Lượng: <font color=green>' + (news_paper.duration || 'N/A') + '</font></p>';
          fileInfo += '<p style="margin: 0;">Nguồn: <font color=green>' + (news_paper.source || 'N/A') + '</font></p>';
  		//Báo Tin Tức
          fileInfo += '<button class="btn btn-success" title="Phát: ' + news_paper.title + '" onclick="send_Media_Play_API(\'' + news_paper.audio + '\', \'' + news_paper.title + '\', \'' + news_paper.cover + '\')"><i class="bi bi-play-circle"></i></button>';
          //fileInfo += ' <button class="btn btn-primary" title="Thêm vào danh sách phát: ' + news_paper.title + '" onclick="addToPlaylist(\'' + news_paper.title + '\', \'' + news_paper.cover + '\', \'' + news_paper.audio + '\', \'' + (news_paper.duration || 'N/A') + '\', \'' + (news_paper.description || 'N/A') + '\', \''+news_paper.source+'\', \'' + news_paper.audio + '\', null, null)"><i class="bi bi-music-note-list"></i></button>';
          fileInfo += ' <a href="' + news_paper.audio + '" target="_blank"><button class="btn btn-info" title="Mở trong tab mới: ' + news_paper.title + '"><i class="bi bi-box-arrow-up-right"></i></button></a>';
          fileInfo += '</div></div>';
          // Thêm thông tin vào phần tử danh sách
          fileListDiv.innerHTML += fileInfo;
      });
                  } else {
  					show_message('Lỗi: ' + response.message);
                  }
              } catch (e) {
  				show_message('Lỗi phân tích JSON: ' +e);
              }
          } else {
  			loading('hide');
  			show_message('Lỗi yêu cầu API: ' +xhr.status+ ", " +xhr.statusText);
          }
      };
      xhr.onerror = function () {
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
  					if (source_cache === "ZingMP3"){
  						cacheZingMP3();
  					}else if (source_cache === "Youtube"){
  						cacheYoutube();
  					}else if (source_cache === "PodCast"){
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
      //Xác định dữ liệu JSON tùy theo action_control
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
      } else {
          data = JSON.stringify({
              "type": 1,
              "data": "media_control",
              "action": "play_list",
              "source_playlist": true
          });
      }
      const xhr = new XMLHttpRequest();
      xhr.addEventListener("readystatechange", function () {
          if (this.readyState === 4) {
  			loading("hide");
              try {
                  const response = JSON.parse(this.responseText);
                  if (response.success) {
                      showMessagePHP(response.message, 3);
                  } else {
                      show_message("Lỗi xảy ra: " + JSON.stringify(response));
                  }
              } catch (error) {
                  show_message("Có lỗi xảy ra: " + error.message);
              }
          }
      });
      xhr.onerror = function () {
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
          // Kiểm tra nếu filePath là '.m3u8'
          if (filePath.endsWith('.m3u8')) {
              //Chạy playHLS nếu là m3u8
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

  //Play video hoặc m3u8 HLS
  function playHLS(url) {
      loading("show");
      const video = document.getElementById('videoPlayer');
      if (Hls.isSupported()) {
          const hls = new Hls();
          hls.loadSource(url);
          hls.attachMedia(video);
          hls.on(Hls.Events.MANIFEST_PARSED, function() {
              loading("hide");
              video.play();
          });
      } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
          video.src = url;
          video.addEventListener('loadedmetadata', function() {
              loading("hide");
              video.play();
          });
      }
  }
</script>
<!-- Chatbot -->
<script>
  // Hàm thay đổi class giữa modal-lg, modal-xl và modal-fullscreen và cập nhật icon dao diện chatbox
  function chatbot_toggleFullScreen() {
      var chatbotSizeSetting = document.getElementById('chatbot_size_setting');
      var chatbotIcon = document.getElementById('chatbot_fullscreen');
      // Kiểm tra và thay đổi class giữa modal-lg, modal-xl, và modal-fullscreen
      if (chatbotSizeSetting.classList.contains('modal-lg')) {
          chatbotSizeSetting.classList.remove('modal-lg');
          chatbotSizeSetting.classList.add('modal-xl');
      } else if (chatbotSizeSetting.classList.contains('modal-xl')) {
          chatbotSizeSetting.classList.remove('modal-xl');
          chatbotSizeSetting.classList.add('modal-fullscreen');
          // Thay đổi icon thành bi-fullscreen-exit khi ở chế độ fullscreen
          chatbotIcon.classList.remove('bi-arrows-fullscreen');
          chatbotIcon.classList.add('bi-fullscreen-exit');
      } else if (chatbotSizeSetting.classList.contains('modal-fullscreen')) {
          chatbotSizeSetting.classList.remove('modal-fullscreen');
          chatbotSizeSetting.classList.add('modal-lg');
          // Trở lại icon fullscreen khi không ở chế độ fullscreen
          chatbotIcon.classList.remove('bi-fullscreen-exit');
          chatbotIcon.classList.add('bi-arrows-fullscreen');
      }
  }

  // Hàm thay đổi class giữa modal-lg, modal-xl và modal-fullscreen và cập nhật icon dao diện chatbox
  function vbotScan_toggleFullScreen() {
      var chatbotSizeSetting = document.getElementById('vbotScan_size_setting');
      var chatbotIcon = document.getElementById('vbotScan_fullscreen');
      // Kiểm tra và thay đổi class giữa modal-lg, modal-xl, và modal-fullscreen
      if (chatbotSizeSetting.classList.contains('modal-lg')) {
          chatbotSizeSetting.classList.remove('modal-lg');
          chatbotSizeSetting.classList.add('modal-xl');
      } else if (chatbotSizeSetting.classList.contains('modal-xl')) {
          chatbotSizeSetting.classList.remove('modal-xl');
          chatbotSizeSetting.classList.add('modal-fullscreen');
          // Thay đổi icon thành bi-fullscreen-exit khi ở chế độ fullscreen
          chatbotIcon.classList.remove('bi-arrows-fullscreen');
          chatbotIcon.classList.add('bi-fullscreen-exit');
      } else if (chatbotSizeSetting.classList.contains('modal-fullscreen')) {
          chatbotSizeSetting.classList.remove('modal-fullscreen');
          chatbotSizeSetting.classList.add('modal-lg');
          // Trở lại icon fullscreen khi không ở chế độ fullscreen
          chatbotIcon.classList.remove('bi-fullscreen-exit');
          chatbotIcon.classList.add('bi-arrows-fullscreen');
      }
  }

  // Hàm thay đổi class giữa modal-lg, modal-xl và modal-fullscreen và cập nhật icon dao diện chatbox
  function vbotBluetooth_toggleFullScreen() {
      var chatbotSizeSetting = document.getElementById('vbotBluetooth_size_setting');
      var chatbotIcon = document.getElementById('vbotBluetooth_fullscreen');
  var modalContent = document.getElementById('BLT_modal-content_Style');
      // Kiểm tra và thay đổi class giữa modal-lg, modal-xl, và modal-fullscreen
      if (chatbotSizeSetting.classList.contains('modal-lg')) {
          chatbotSizeSetting.classList.remove('modal-lg');
          chatbotSizeSetting.classList.add('modal-xl');
  modalContent.style.height = '50%'; 
      } else if (chatbotSizeSetting.classList.contains('modal-xl')) {
          chatbotSizeSetting.classList.remove('modal-xl');
          chatbotSizeSetting.classList.add('modal-fullscreen');
  modalContent.style.height = '100%'; 
          // Thay đổi icon thành bi-fullscreen-exit khi ở chế độ fullscreen
          chatbotIcon.classList.remove('bi-arrows-fullscreen');
          chatbotIcon.classList.add('bi-fullscreen-exit');
      } else if (chatbotSizeSetting.classList.contains('modal-fullscreen')) {
          chatbotSizeSetting.classList.remove('modal-fullscreen');
          chatbotSizeSetting.classList.add('modal-lg');
          // Trở lại icon fullscreen khi không ở chế độ fullscreen
          chatbotIcon.classList.remove('bi-fullscreen-exit');
          chatbotIcon.classList.add('bi-arrows-fullscreen');
  modalContent.style.height = '50%'; 
      }
  }
  
  //  hàm cuộn xuống dưới cùng tin nhắn
  function scrollToBottom() {
      const chatbox = document.getElementById('chatbox');
      chatbox.scrollTop = chatbox.scrollHeight;
  }
  
  // Hàm lấy thời gian hiện tại dưới định dạng dd/mm/yyyy hh:mm:ss
  function getCurrentTime() {
      const now = new Date();
      const day = String(now.getDate()).padStart(2, '0');
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const year = now.getFullYear();
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      return hours + ':' + minutes + ':' + seconds + ' ' + day + '/' + month + '/' + year;
  }
  
  // Hàm lưu tin nhắn vào localStorage
  function saveMessage(type, text) {
      const messages = JSON.parse(localStorage.getItem('messages')) || [];
      messages.push({
          type: type,
          text: text,
          time: getCurrentTime()
      });
      localStorage.setItem('messages', JSON.stringify(messages));
  }
  
  // Hàm xóa tin nhắn khỏi localStorage và giao diện
  function deleteMessage(index) {
      const messages = JSON.parse(localStorage.getItem('messages')) || [];
      messages.splice(index, 1);
      localStorage.setItem('messages', JSON.stringify(messages));
      loadMessages();
  }
  
  // Hàm tải tin nhắn từ localStorage
function loadMessages() {
    const chatbox = document.getElementById('chatbox');
	// Nếu không có chatbox thì thoát luôn
    if (!chatbox) return;
    const messages = JSON.parse(localStorage.getItem('messages')) || [];
    chatbox.innerHTML = '';
    messages.forEach(function(message, index) {
        var messageHTML = '<div class="message ' + (message.type === 'user' ? 'user-message' : 'bot-message') + '">' +
            '<span class="delete_message_chatbox" data-index="' + index + '" title="Xóa tin nhắn">x</span>' +
            '<div class="message-time">' + message.time + '</div>';
        if (message.text && /^TTS_Audio.*\.(mp3|ogg|wav)$/i.test(message.text)) {
            var audioExtension = message.text.split('.').pop();
            var fullAudioUrl = 'includes/php_ajax/Show_file_path.php?TTS_Audio=' + encodeURIComponent(message.text);
            messageHTML +=
                '<div class="audio-container">' +
                '    <audio controls>' +
                '        <source src="' + fullAudioUrl + '" type="audio/' + audioExtension + '">' +
                '        Your browser does not support the audio element.' +
                '    </audio>' +
                '</div>';
        } else {
            messageHTML += '<div>' + message.text + '</div>';
        }
        messageHTML += '</div>';
        chatbox.innerHTML += messageHTML;
    });
    scrollToBottom();
    // Gán sự kiện xóa tin nhắn
    document.querySelectorAll('.delete_message_chatbox').forEach(function(button) {
        button.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'), 10);
            deleteMessage(index);
        });
    });
}

  // Hàm xóa tất cả tin nhắn từ localStorage và giao diện
  function clearMessages() {
  if (!confirm("Bạn có chắc chắn muốn xóa lịch sử chat ?")) {
      return;
  }
  localStorage.removeItem('messages');
  loadMessages();
  }
  
  // Hàm xóa tin nhắn khỏi localStorage và giao diện
  function deleteMessage(index) {
  const messages = JSON.parse(localStorage.getItem('messages')) || [];
  messages.splice(index, 1);
  localStorage.setItem('messages', JSON.stringify(messages));
  loadMessages();
  }
  
  // Hàm để dừng tất cả các phần tử audio đang phát
  function stopAllAudio() {
  //console.log("dừng audio");
  var audios = document.querySelectorAll('audio');
  audios.forEach(function(audio) {
      audio.pause();
      audio.currentTime = 0;
  });
  }

//gửi yêu cầu POST Chatbox và xử lý phản hồi
function sendRequest(message) {
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
                            '    <div class="message-time">' + getCurrentTime() + '</div>' +
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
                            '    <div class="message-time">' + getCurrentTime() + '</div>' +
                            '    <div>' + response.message + '</div>' +
                            '</div>';
                    }
                    document.getElementById('chatbox').innerHTML += botMessageHTML;
                    saveMessage('bot', response.message);
                    if (flag_mic_recording && isAutoClick_btn_send_msg) {
                        isAutoClick_btn_send_msg = false;
                        Recording_STT();
                    }
                } else {
                    playSound_default('/assets/sound/default/dong.mp3');
                    var msg_error = "Có lỗi xảy ra. Vui lòng thử lại";
                    var errorMessageHTML =
                        '<div class="message bot-message">' +
                        '<div class="message-time">' + getCurrentTime() + '</div>' +
                        '<div>' + msg_error + '</div>' +
                        '</div>';
                    document.getElementById('chatbox').innerHTML += errorMessageHTML;
                    saveMessage('bot', msg_error);
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
                    '    <div class="message-time">' + getCurrentTime() + '</div>' +
                    '    <div>' + msg_error + '</div>' +
                    '</div>';
                document.getElementById('chatbox').innerHTML += failureMessageHTML;
                saveMessage('bot', msg_error);
            }
        }
    });
    xhr.open("POST", "<?php echo $URL_API_VBOT; ?>");
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.send(data);
    timeout = setTimeout(function() {
        typingIndicator.innerHTML = 'Vui lòng chờ thêm...';
        timeout = setTimeout(function() {
            var msg_error = "Có vẻ bot đang không phản hồi, vui lòng thử lại";
            typingIndicator.innerHTML = msg_error;
            saveMessage('bot', msg_error);
        }, 13000);
    }, 7000);
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
        const userInput = document.getElementById('user_input_chatbox');
        const message = userInput?.value.trim();
        if (message) {
            const userMessageHTML =
                '<div class="message user-message">' +
                '    <div class="message-time">' + getCurrentTime() + '</div>' +
                '    <div>' + message + '</div>' +
                '</div>';
            const chatbox = document.getElementById('chatbox');
            if (chatbox) {
                chatbox.innerHTML += userMessageHTML;
            }
            saveMessage('user', message);
            sendRequest(message);
            if (userInput) userInput.value = '';
            setTimeout(scrollToBottom, 100);
        }
    });
}

// Xử lý sự kiện nhấn Enter
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
function Recording_STT() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        show_message('Trình duyệt của bạn không hỗ trợ nhận diện giọng nói');
        return;
    }
    const recognition = new SpeechRecognition();
    recognition.lang = 'vi-VN'; //ngôn ngữ tiếng việt
    recognition.interimResults = true; //Hiển thị kết quả tạm thời
    recognition.continuous = false;	//tự động phát hiện kết thúc câu
    const inputField = document.getElementById('user_input_chatbox');
    const sendButton = document.getElementById('send_button_chatbox');
    const animationDiv = document.getElementById('Recording_STT_mic_animation');
    playSound_default('/assets/sound/default/ding.mp3');
	//Nếu 10 giây không có giọng nói thì tắt Mic
    let timeoutId = setTimeout(() => {recognition.stop();}, 10000);
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
        }else if (event.error === 'not-allowed'){
            flag_mic_recording = false;
            isAutoClick_btn_send_msg = false;
            show_message('Sử Dụng Microphone Thất bại, Kết nối WebUI VBot cần được truy cập bằng (Doamin, Tên Miền) và bảo mật bằng https, Điều này khiến trình duyệt chặn truy cập vào Microphone vì kết nối không phải là https: ' + event.error);
		}
		else {
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
        }
		else {
            flag_mic_recording = false;
            isAutoClick_btn_send_msg = false;
            playSound_default('/assets/sound/default/dong.mp3');
			if (!sendButton){
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
  // Tải tin nhắn từ localStorage khi trang được tải
  loadMessages();
  //Khi chatbox được hiển thị hoàn toàn
  document.addEventListener('DOMContentLoaded', () => {
  const myModal = document.getElementById('modalDialogScrollable_chatbot');
  if (myModal) {
    myModal.addEventListener('shown.bs.modal', () => {
      scrollToBottom();
    });
  }
});

//Phát âm thanh báo hiệu thu âm hoặc kết thúc
function playSound_default(path_sound) {
    // playSound_default('/assets/sound/default/ding.mp3');
    const audio = new Audio(path_sound);
    audio.play();
    audio.onended = () => {
        //console.log("Âm thanh phát xong. Bắt đầu thu âm...");
        //startRecording();
    };
    audio.onerror = () => {
        showMessagePHP('Không thể phát âm thanh DING', 5)
    };
}

//Hàm thay đổi icon mic dựa trên giao thức URL
function updateMicIcon() {
    const micIcon = document.getElementById('mic_icon_rec');
    if (micIcon) {
        const isHttps = window.location.protocol === 'https:';
        micIcon.className = isHttps ? 'bi bi-mic-fill' : 'bi bi-mic-mute text-warning';
    }
}
updateMicIcon();
</script>
<script>
  //kiểm tra nếu có thẻ id là tableContainer sẽ thay đổi kích thước chiều dọc tùy thuộc vào nội dung
  function adjustContainerStyle_tableContainer() {
      var container = document.getElementById('tableContainer');
      if (container) {
          var contentHeight = container.scrollHeight;
          if (contentHeight > 400) {
              container.style.height = '400px';
              container.style.overflowY = 'auto';
          } else {
              container.style.height = 'auto';
              container.style.overflowY = 'hidden';
          }
      }
  }
</script>
<script>
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
  				if (reload_page === true){
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
  
  //Nếu nhấn vào nút Nguồn nhạc Local trên tab mediaplayer hoặc ở index
  function cacheLocal(){
      var inputElement = document.getElementById("tim_kiem_bai_hat_all");
      if (inputElement) {
          inputElement.style.display = "";
      } else {
          showMessagePHP('Không tìm thấy phần tử với id "tim_kiem_bai_hat_all"', 3);
      }
  }
  
  //Nếu nhấn vào nút Nguồn nhạc Local trên tab mediaplayer hoặc ở index
  function cacheRadio(){
      var inputElement = document.getElementById("tim_kiem_bai_hat_all");
      if (inputElement) {
          inputElement.style.display = "";
      } else {
          showMessagePHP('Không tìm thấy phần tử với id "tim_kiem_bai_hat_all"', 3);
      }
  }
  
  //Nếu nhấn vào nút Báo, Tin tức trên tab mediaplayer hoặc ở index
  function cacheNewsPaper() {
      var inputElement = document.getElementById("tim_kiem_bai_hat_all");
      if (inputElement) {
          inputElement.style.display = "none";
      } else {
  		showMessagePHP('Không tìm thấy phần tử với id "tim_kiem_bai_hat_all"', 3);
      }
  }
  
  //Kiểm tra và hiển thị thông báo cập nhật WEB UI
  function ui_check_update() {
      const localFileUrl_ui = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $HTML_VBot_Offline; ?>/Version.json';
      //const remoteFileUrl_ui = 'https://raw.githubusercontent.com/<?php echo $git_username; ?>/<?php echo $git_repository; ?>/refs/heads/main/html/Version.json';
      const remoteFileUrl_ui = 'https://api.github.com/repos/<?php echo $git_username; ?>/<?php echo $git_repository; ?>/contents/html/Version.json?ref=main';
      function fetchRemoteData(url, callback) {
          const xhr = new XMLHttpRequest();
          xhr.open('GET', url, true);
          xhr.onload = function() {
              if (xhr.status === 200) {
                  try {
                      //callback(JSON.parse(xhr.responseText));
                      const response = JSON.parse(xhr.responseText);
                      const decodedContent = atob(response.content);
                      const jsonData = JSON.parse(decodedContent);
                      callback(jsonData);
                  } catch (error) {
                      showMessagePHP("Lỗi khi phân tích dữ liệu JSON từ file remote: "+error, 3);
                  }
              } else {
                  showMessagePHP('Lỗi khi tải dữ liệu từ file remote: ' +xhr.statusText, 3);
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
  					showMessagePHP('Lỗi khi phân tích dữ liệu tệp Version.json của giao diện: ' +error, 3);
                      callback(null);
                  }
              } else {
  				showMessagePHP('Lỗi khi tải dữ liệu từ file local: ' +xhr.statusText, 3);
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
                      //callback(JSON.parse(xhr.responseText));
                      // Giải mã base64 nếu dữ liệu ở dạng base64
                      const response = JSON.parse(xhr.responseText);
                      const decodedContent = atob(response.content);
                      const jsonData = JSON.parse(decodedContent);
                      callback(jsonData);
                  } catch (error) {
                      showMessagePHP("Lỗi khi phân tích dữ liệu JSON từ file remote:" +error, 3);
                  }
              } else {
                  showMessagePHP('Lỗi khi tải dữ liệu từ file remote:' +xhr.statusText, 3);
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
                      showMessagePHP('Lỗi khi phân tích dữ liệu JSON từ file local:' +error, 3);
                      callback(null);
                  }
              } else {
                  showMessagePHP('Lỗi khi tải dữ liệu từ file local:' +xhr.statusText, 3);
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
      const url = "includes/php_ajax/Scanner.php?VBot_Device_Scaner";
      const xhr = new XMLHttpRequest();
      xhr.open("GET", url, true);
      xhr.onreadystatechange = function () {
          if (xhr.readyState === 4) {
              loading('hide');
              if (xhr.status === 200) {
                  try {
                      const response = JSON.parse(xhr.responseText);
                      //console.log("Response:", response);
  					if (response.success) {
  						const data = response.data;
  						if (Array.isArray(data) && data.length > 0) {
  							//Sắp xếp các thiết bị có đầy đủ dữ liệu lên đầu
  							data.sort(function(a, b) {
  								//Kiểm tra nếu tất cả các dữ liệu cần thiết tồn tại
  								const aHasData = a.ip_address && a.port_api && a.host_name && a.user_name;
  								const bHasData = b.ip_address && b.port_api && b.host_name && b.user_name;
  								if (aHasData && !bHasData) {
  									//a lên đầu, b xuống cuối
  									return -1;
  								} else if (!aHasData && bHasData) {
  									//b lên đầu, a xuống cuối
  									return 1;
  								}
  								//Giữ nguyên vị trí nếu đều đầy đủ hoặc đều thiếu
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
  							//Lặp qua danh sách đã sắp xếp và thêm từng thiết bị vào bảng
  							data.forEach((device, index) => {
								
								const rowId = 'device_row_' + index;
								
  								tableHTML += 
  									'<tr id="' + rowId + '">' +
										'<td id="' + rowId + '_name" style="text-align: center; vertical-align: middle;"><b><p class="text-success">' + (device.user_name || '') + '</p></b></td>' +
  										'<td id="' + rowId + '_ip" style="text-align: center; vertical-align: middle;"><b><a class="text-danger" href="http://' + (device.ip_address || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.ip_address || '') + '</a></b></td>' +
  										'<td id="' + rowId + '_port" style="text-align: center; vertical-align: middle;"><b><a class="text-success" href="http://' + (device.ip_address || '') + ':' + (device.port_api || '') + '" target="_blank" title="Mở Trong Tab Mới">' + (device.port_api || '') + '</a></b></td>' +
  										'<td id="' + rowId + '_host" style="text-align: center; vertical-align: middle;"><b>' + (device.host_name || '') + '</b></td>' +
  										'<td id="' + rowId + '_action" style="text-align: center; vertical-align: middle;"><button class="btn btn-danger" title="Xóa ' + (device.ip_address || '') + '" onclick="delete_IP_VBot_Server(\'' + (device.ip_address || '') + '\')"><i class="bi bi-trash"></i></button></td>' +
  									'</tr>';
  							});
  							tableHTML += 
  								'</tbody>' +
  							'</table>';
  							document.getElementById("vbot_Scan_devices").innerHTML = tableHTML;
							check_Device_Status_VBot_Server();
  						}
  						else {
  							document.getElementById("vbot_Scan_devices").innerHTML = "Không tìm thấy thiết bị nào.";
  						}
  					}
  					 else {
  						show_message("Đã xảy ra lỗi: " + response.messager);
                      }
                  } catch (error) {
                      document.getElementById("vbot_Scan_devices").innerHTML = "Đã xảy ra lỗi khi xử lý dữ liệu: "+xhr.responseText;
                  }
              } else {
                  document.getElementById("vbot_Scan_devices").innerHTML = "Không thể kết nối tới máy chủ: "+xhr.status;
              }
          }
      };
      xhr.send();
  }

//Kiểm tra trực tuyến các thiết bị Vot server trong mạng lan
function check_Device_Status_VBot_Server() {
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
            const statusDot = isOnline
                ? '<span style="color: green; font-size: 30px;" title="Thiết bị đang trực tuyến">●</span>'
                : '<span style="color: red; font-size: 30px;" title="Thiết bị đang ngoại tuyến">●</span>';
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
    const url = 'includes/php_ajax/Show_file_path.php?read_file_path&file=<?php echo $directory_path."/includes/other_data/VBot_Server_Data/VBot_Devices_Network.json"; ?>';
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
                                '<td id="' + rowId + '_action" style="text-align: center; vertical-align: middle;"><button class="btn btn-danger" title="Xóa ' + (device.ip_address || '') + '" onclick="delete_IP_VBot_Server(\'' + (device.ip_address || '') + '\')"><i class="bi bi-trash"></i></button></td>' +
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
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
					loading('hide');
					showMessagePHP('Thêm thiết bị thành công: '+response.device.ip_address, 5);
					get_vbotScanDevices();
                } else {
					loading('hide');
					showMessagePHP('Thêm thiết bị thất bại: '+response.error, 5);
                }
            } catch (e) {
				loading('hide');
				showMessagePHP('Thêm thiết bị thất bại: '+e, 5);
            }
        } else {
			loading('hide');
			showMessagePHP('Không thể kết nối đến máy chủ. Mã lỗi: ' + xhr.status, 5);
        }
    };
    xhr.onerror = function () {
		loading('hide');
		showMessagePHP('Lỗi khi gửi yêu cầu', 5);
    };
    xhr.send();
}

//Xóa ip  VBot Server đã scan được
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

  function bluetooth_command_at_input() {
      // Bluetooth command khi được nhập lệnh thủ công ở thẻ input
      var inputValue = document.getElementById('ble_Command_UI').value.trim();
      if (inputValue === "") {
          return;
      }
      if (!inputValue.startsWith("AT+")) {
          showMessagePHP("Lệnh Không Đúng", 3);
          return;
      }
      bluetooth_control('command', inputValue);
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
                          show_message('<b><pre class="text-success">'+jsonString+'</pre></b>');
                      } else {
  						var data = JSON.parse(response.message);
  						var jsonString = JSON.stringify(data, null, 2);
                          show_message('<b><pre class="text-success">'+jsonString+'</pre></b>');
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

  function loadBluetoothDevices() {
      // Lấy dữ liệu từ localStorage
      var storedData = localStorage.getItem('bluetoothDevices_Vbot');
      if (storedData) {
          var data = JSON.parse(storedData);
          var tableHTML = '<p class="card-title"> Dữ liệu được tìm kiếm trước đó: <i class="bi bi-trash text-danger" title="Xóa dữ liệu Tìm Kiếm" onclick="clearBluetoothDevices()"></i></p><table class="table table-bordered border-primary" cellspacing="0" cellpadding="5"><tr><th style="text-align: center; vertical-align: middle;">Địa Chỉ Mac</th><th style="text-align: center; vertical-align: middle;">Tên Thiết Bị</th><th style="text-align: center; vertical-align: middle;">Hành Động</th></tr>';
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
      } else {
          document.getElementById("Bluetooth_Scan_devices").innerHTML = "<center><h5 class='card-title text-danger'>Không có danh sách thiết bị Bluetooth nào được tìm thấy</h5></center>";
      }
  }

  function clearBluetoothDevices() {
  	//Bluetooth xóa dữ liệu được lưu trong localStorage
      localStorage.removeItem('bluetoothDevices_Vbot');
      document.getElementById("Bluetooth_Scan_devices").innerHTML = "<center><h5 class='card-title text-danger'>Không có danh sách thiết bị Bluetooth nào được tìm thấy</h5></center>";
  }

  //Tải xuống bài hát Zingmp3
  function dowload_ZingMP3_ID(zing_id, zing_name) {
  	loading("show");
      var xhr = new XMLHttpRequest();
      var url = 'includes/php_ajax/Media_Player_Search.php?ZingMP3_GetLink&Zing_ID='+zing_id;
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
							a.download = zing_name+'.mp3';
							document.body.appendChild(a);
							a.click();
							document.body.removeChild(a);
							window.URL.revokeObjectURL(url);
							loading("hide");
							showMessagePHP('Đã tải xuống file âm thanh từ ZingMP3: '+zing_name+'.mp3', 5);
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

//Tải xuống tệp âm thanh từ url/Link online
function download_AUDIO_URL(url, name_title) {
    if (!url || !name_title) {
        show_message('Lỗi: URL hoặc tên bài hát không được để trống.');
        return;
    }
    loading("show");
    fetch(url)
        .then(response => {
            loading("hide");
            if (!response.ok) {
                throw new Error('Lỗi xảy ra, không thể tải file âm thanh từ ZingMP3');
            }
            return response.blob();
        })
        .then(blob => {
            const blobUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = name_title + '.mp3';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(blobUrl);
            showMessagePHP('Đã tải xuống file âm thanh: ' + name_title + '.mp3', 5);
			loading("hide");
        })
        .catch(error => {
            loading("hide");
            show_message('Lỗi, Không thể tải xuống file âm thanh: ' + error.message);
        });
}

// Tải Nhạc ZingMP3 Vào Thư Mục Local
function download_zingMp3_to_local(IDzing, songName) {
    // Kiểm tra dữ liệu đầu vào
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
                    xhr2.onreadystatechange = function () {
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
    // Kiểm tra dữ liệu đầu vào
    if (!url_audio || !songName) {
        show_message('Lỗi: URL âm thanh hoặc tên bài hát không được để trống.');
        return;
    }
    // Loại bỏ đuôi .mp3 nếu đã có (để tránh trùng lặp trong PHP)
    songName = songName.replace(/\.mp3$/i, '');
    loading("show");
    var xhr2 = new XMLHttpRequest();
    xhr2.open('POST', 'includes/php_ajax/Media_Player_Search.php', true);
    xhr2.setRequestHeader('Content-Type', 'application/json');
    xhr2.onreadystatechange = function () {
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

  /*
  // Hàm thêm thông báo mới vào danh sách thông báo
  function addNotification(message) {
      // Tạo thẻ li mới
      var li = document.createElement("li");
      li.classList.add("notification-item");
  
      // Nội dung của thẻ li
      li.innerHTML = '<a href="#"><font color="green"><i class="bi bi-box-arrow-in-up"></i></font></a>' +
                     '<div>' +
                     '<h4><font color="green">Thông Báo Mới</font></h4>' +
                     '<p class="text-primary">' + message + '</p>' +
                     '<a href="#"><p class="text-danger">Kiểm Tra</p></a>' +
                     '</div>';
  
      // Thêm thẻ li vào trong ul
      document.querySelector('#notification').appendChild(li);
  
      // Cập nhật số lượng thông báo trong header
      var countElement = document.querySelector('#number_notification');
      var currentCount = parseInt(countElement.innerText);
      countElement.innerText = currentCount + 1; // Tăng số lượng thông báo
  }
  
  // Ví dụ về cách gọi hàm khi có thông báo mới
  setTimeout(function() {
      addNotification("Có bản cập nhật mới cho Web UI!");
  }, 2000);
  
  */
  
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