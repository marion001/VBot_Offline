<?php
$VBot_Csrf_Token = '';
if (session_status() === PHP_SESSION_ACTIVE) {
    if (
        !isset($_SESSION['vbot_csrf_token'])
        || !is_string($_SESSION['vbot_csrf_token'])
        || strlen($_SESSION['vbot_csrf_token']) < 64
    ) {
        $_SESSION['vbot_csrf_token'] = bin2hex(random_bytes(32));
    }
    $VBot_Csrf_Token = $_SESSION['vbot_csrf_token'];
}
?>
<!--
  #Code By: Vũ Tuyển
  #Designed by: BootstrapMade
  #GitHub VBot: https://github.com/marion001/VBot_Offline.git
  #Facebook Group: https://www.facebook.com/groups/1148385343358824
  #Facebook: https://www.facebook.com/TWFyaW9uMDAx
  #Email: VBot.Assistant@gmail.com
  -->

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <meta name="vbot-csrf-token" content="<?php echo htmlspecialchars($VBot_Csrf_Token, ENT_QUOTES, 'UTF-8'); ?>">
  <script>
    window.VBOT_CSRF_TOKEN = <?php echo json_encode($VBot_Csrf_Token); ?>;
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('form').forEach(function (form) {
        if ((form.method || 'get').toLowerCase() !== 'post') return;
        var input = form.querySelector('input[name="csrf_token"]');
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'csrf_token';
          form.appendChild(input);
        }
        input.value = window.VBOT_CSRF_TOKEN || '';
      });
    });
  </script>
  <script>
    (function () {
      'use strict';

      const loginUrl = new URL('Login.php', document.baseURI).href;
      let redirectingToLogin = false;

      function isLoginPageUrl(url) {
        try {
          return new URL(url, document.baseURI).pathname.toLowerCase().endsWith('/login.php');
        } catch (error) {
          return false;
        }
      }

      function isSameOriginRequest(url) {
        try {
          return new URL(url, document.baseURI).origin === window.location.origin;
        } catch (error) {
          return false;
        }
      }

      function responseIndicatesExpiredSession(status, responseUrl, body) {
        if (isLoginPageUrl(responseUrl)) return true;
        if (status === 401) return true;
        if (!body) return false;
        const text = typeof body === 'string' ? body : JSON.stringify(body);
        return /(?:phiên|phien).{0,30}(?:đăng nhập|dang nhap).{0,30}(?:hết hạn|het han)/i.test(text)
          || /(?:chỉ cho phép|chi cho phep).{0,50}(?:đăng nhập|dang nhap)/i.test(text)
          || /login\.php/i.test(text) && /(?:location|redirect|đăng nhập|dang nhap)/i.test(text);
      }

      function redirectToLogin() {
        if (redirectingToLogin || isLoginPageUrl(window.location.href)) return;
        redirectingToLogin = true;
        window.location.replace(loginUrl);
      }

      window.VBotWebUISession = {
        loginUrl: loginUrl,
        check: function (status, responseUrl, body, requestUrl) {
          if (!isSameOriginRequest(requestUrl || responseUrl || window.location.href)) return false;
          if (!responseIndicatesExpiredSession(status, responseUrl, body)) return false;
          redirectToLogin();
          return true;
        },
        redirectToLogin: redirectToLogin
      };

      if (typeof window.fetch === 'function') {
        const nativeFetch = window.fetch.bind(window);
        window.fetch = async function (input, init) {
          const requestUrl = typeof input === 'string' || input instanceof URL
            ? String(input)
            : input && input.url ? input.url : window.location.href;
          const response = await nativeFetch(input, init);
          if (!isSameOriginRequest(requestUrl)) return response;

          let body = '';
          const responseUrl = response.url || requestUrl;
          if (response.status === 401 || response.status === 403 || isLoginPageUrl(responseUrl)) {
            try { body = await response.clone().text(); } catch (error) {}
          } else {
            const contentType = response.headers.get('content-type') || '';
            if (/json|text|html/i.test(contentType)) {
              try { body = await response.clone().text(); } catch (error) {}
            }
          }
          window.VBotWebUISession.check(response.status, responseUrl, body, requestUrl);
          return response;
        };
      }

      if (typeof window.XMLHttpRequest === 'function') {
        const nativeOpen = XMLHttpRequest.prototype.open;
        const nativeSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function (method, url) {
          this.__vbotRequestUrl = url;
          return nativeOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function () {
          if (!this.__vbotSessionListener) {
            this.__vbotSessionListener = true;
            this.addEventListener('loadend', function () {
              const requestUrl = this.__vbotRequestUrl || this.responseURL;
              if (!isSameOriginRequest(requestUrl)) return;
              let body = '';
              try {
                body = this.responseType === 'json' ? this.response : this.responseText;
              } catch (error) {}
              window.VBotWebUISession.check(
                this.status,
                this.responseURL || requestUrl,
                body,
                requestUrl
              );
            });
          }
          return nativeSend.apply(this, arguments);
        };
      }
    })();
  </script>
  <!-- <title>VBot Assistant</title> -->
  <meta name="description" content="VBot Assistant - Loa Thông Minh VBot tiếng Việt, tích hợp trợ lý ảo giúp điều khiển nhà thông minh, phát nhạc, nhắc nhở và nhiều tiện ích khác. Trải nghiệm loa thông minh cho người Việt.">
  <meta name="keywords" content="VBot Assistant, Loa Thông Minh VBot, Loa Thông Minh Tiếng Việt, Loa Thông Minh Trợ Lý Ảo VBot, trợ lý ảo, loa thông minh Việt Nam, điều khiển giọng nói, nhà thông minh">
  <meta name="author" content="Vũ Tuyển">
  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <!-- Google Fonts
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&display=swap" rel="stylesheet">
    -->
  <link href="assets/css/fonts.css" rel="stylesheet">
  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <?php
  $webui_page_name = basename($_SERVER['PHP_SELF'] ?? '');
  $webui_datatable_pages = ['index.php', 'Log_TTS.php', 'Log_pycache.php'];
  if (in_array($webui_page_name, $webui_datatable_pages, true)) {
      echo '<link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">';
  }
  ?>
  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  <link href="assets/css/dark_mode.css" rel="stylesheet">
  <!-- =======================================================
    * Template Name: NiceAdmin
    * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
    * Updated: Apr 20 2024 with Bootstrap v5.3.3
    * Author: BootstrapMade.com
    * License: https://bootstrapmade.com/license/
    ======================================================== -->
  <style>
    .overlay_loading {
      display: none;
      /* Ẩn overlay theo mặc định */
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      /* Nền tối */
      z-index: 9999;
      justify-content: center;
      align-items: center;
      color: white;
      font-size: 1.5rem;
    }
  </style>
  <style>
    /* CSS hiển thời gian */
    #container_time {
      background: #f1f1f1;
      border: 2px solid #999;
      border-radius: 10px;
      padding: 2px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    #day-date-container_time {
      display: flex;
      justify-content: center;
    }

    /* Ẩn phần ngày tháng, thời gian khi trên giao diện mobile */
    @media (max-width: 768px) {
      #container_time {
        display: none;
      }

      #notification {
        max-width: 100%;
        width: auto;
      }

      #number_notification_1 {
        max-width: 100%;
        width: auto;
      }
    }
  </style>
  <!-- Css hiển thị thông báo cho code php -->
  <style>
    #toast {
      visibility: hidden;
      position: fixed;
      bottom: 10px;
      right: 55px;
      background: #333;
      color: #fff;
      padding: 15px;
      border-radius: 5px;
      z-index: 1059;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }

    #toast button {
      background: transparent;
      border: none;
      color: #fff;
      font-size: 20px;
      cursor: pointer;
      position: absolute;
      top: 5px;
      right: 5px;
    }

    #toastMessage {
      margin-right: 20px;
    }
  </style>
  <!-- Css hiển thị media player -->
  <style>
    #media-container {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    #media-cover {
      width: 150px;
      height: 150px;
      border-radius: 10px;
    }

    #progress-container {
      width: 100%;
    }

    #progress-bar {
      width: 100%;
      cursor: pointer;
    }

    #time-info {
      text-align: right;
      margin-top: 5px;
    }
  </style>
  <!--script Hiển thị thông báo Mesage php -->
  <script>
    // Hàm thêm thông báo mới vào danh sách thông báo
    function error_notify(message) {
      // Tạo thẻ li mới
      var li = document.createElement("li");
      li.classList.add("notification-item");
      var icon = document.createElement('i');
      icon.className = 'bi bi-exclamation-circle text-danger';
      var content = document.createElement('div');
      var title = document.createElement('h4');
      title.className = 'text-danger';
      title.textContent = 'Phát Hiện Lỗi';
      var detail = document.createElement('p');
      detail.className = 'text-primary';
      detail.textContent = message == null ? '' : String(message);
      content.appendChild(title);
      content.appendChild(detail);
      li.appendChild(icon);
      li.appendChild(content);
      // Thêm thẻ li vào trong ul
      document.querySelector('#notification').appendChild(li);
      // Cập nhật số lượng thông báo trong header
      var countElement = document.querySelector('#number_notification');
      var currentCount = parseInt(countElement.innerText);
      countElement.innerText = currentCount + 1; // Tăng số lượng thông báo
    }

    //biến cờ kích hoạt Mic để nói
    let flag_mic_recording = false;
    // Cờ để phân biệt nút nhấn gửi tin nhắn tự động hay người dùng nhấn
    let isAutoClick_btn_send_msg = false;

    function vbotSanitizeMessageHtml(message, allowedActions) {
      var template = document.createElement('template');
      template.innerHTML = message == null ? '' : String(message);
      var safeActions = new Set(Array.isArray(allowedActions) ? allowedActions : []);
      var allowedTags = new Set(['B', 'STRONG', 'I', 'EM', 'BR', 'HR', 'P', 'SPAN', 'FONT', 'SMALL', 'CODE', 'UL', 'OL', 'LI', 'A', 'CENTER', 'DIV', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'LABEL', 'TH', 'BUTTON']);
      Array.from(template.content.querySelectorAll('*')).forEach(function(node) {
        if (!allowedTags.has(node.tagName)) {
          node.replaceWith(document.createTextNode(node.textContent || ''));
          return;
        }
        if (node.tagName === 'BUTTON' && !safeActions.has(node.getAttribute('data-vbot-action'))) {
          node.replaceWith(document.createTextNode(node.textContent || ''));
          return;
        }
        Array.from(node.attributes).forEach(function(attribute) {
          var name = attribute.name.toLowerCase();
          var keepClass = name === 'class';
          var keepColor = name === 'color' && node.tagName === 'FONT';
          var keepHref = name === 'href' && node.tagName === 'A';
          var keepButtonType = name === 'type' && node.tagName === 'BUTTON' && attribute.value === 'button';
          var keepSafeAction = name === 'data-vbot-action' && node.tagName === 'BUTTON' && attribute.value === 'reboot-os';
          if (!keepClass && !keepColor && !keepHref && !keepButtonType && !keepSafeAction) node.removeAttribute(attribute.name);
        });
        if (node.tagName === 'A') {
          try {
            var url = new URL(node.getAttribute('href') || '', document.baseURI);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') node.removeAttribute('href');
          } catch (error) {
            node.removeAttribute('href');
          }
          node.setAttribute('target', '_blank');
          node.setAttribute('rel', 'noopener noreferrer');
        }
      });
      return template.innerHTML;
    }

    function showMessagePHP(message, timeout = 15) {
      var toastContainer = document.getElementById('toast');
      // Hiển thị lại toastContainer nếu bị ẩn trước đó
      toastContainer.style.visibility = 'visible';
      toastContainer.style.display = 'flex';
      var toastMessage = document.createElement('div');
      toastMessage.className = 'toast-message';
      var messageContent = document.createElement('span');
      messageContent.innerHTML = vbotSanitizeMessageHtml(message);
      var closeButton = document.createElement('button');
      closeButton.type = 'button';
      closeButton.className = 'text-danger';
      closeButton.textContent = '×';
      closeButton.addEventListener('click', function() { removeToast(toastMessage); });
      toastMessage.appendChild(messageContent);
      toastMessage.appendChild(document.createTextNode(' '));
      toastMessage.appendChild(closeButton);
      toastContainer.appendChild(toastMessage);
      setTimeout(function() {
        removeToast(toastMessage);
      }, timeout * 1000);
    }

    function showMessageText(message, timeout = 15) {
      var toastContainer = document.getElementById('toast');
      toastContainer.style.visibility = 'visible';
      toastContainer.style.display = 'flex';
      var toastMessage = document.createElement('div');
      toastMessage.className = 'toast-message';
      var text = document.createElement('span');
      text.textContent = String(message == null ? '' : message);
      var closeButton = document.createElement('button');
      closeButton.type = 'button';
      closeButton.className = 'text-danger';
      closeButton.textContent = '×';
      closeButton.addEventListener('click', function() { removeToast(toastMessage); });
      toastMessage.appendChild(text);
      toastMessage.appendChild(document.createTextNode(' '));
      toastMessage.appendChild(closeButton);
      toastContainer.appendChild(toastMessage);
      setTimeout(function() { removeToast(toastMessage); }, timeout * 1000);
    }

    function removeToast(toastElement) {
      toastElement.remove();
      var toastContainer = document.getElementById('toast');
      // Nếu không còn thông báo nào thì ẩn luôn #toast
      if (toastContainer.querySelectorAll('.toast-message').length === 0) {
        hideToast();
      }
    }

    function hideToast() {
      var toastContainer = document.getElementById('toast');
      // Xóa toàn bộ nội dung nhưng vẫn giữ nút đóng
      toastContainer.innerHTML = '<button onclick="hideToast()">×</button>';
      // Ẩn hoàn toàn
      toastContainer.style.visibility = 'hidden';
      toastContainer.style.display = 'none';
    }

    //Hiển thị và đóng thông báo Message
    function show_message(message, options) {
      var modalBody = document.querySelector('#notificationModal .modal-body');
      if (!modalBody) return;
      var allowReboot = Boolean(options && options.allowReboot === true);
      modalBody.innerHTML = vbotSanitizeMessageHtml(message, allowReboot ? ['reboot-os'] : []);
      modalBody.querySelectorAll('[data-vbot-action="reboot-os"]').forEach(function(button) {
        button.addEventListener('click', function() {
          if (typeof power_action_service === 'function') {
            power_action_service('reboot_os', 'Bạn có chắc chắn muốn khởi động lại toàn bộ hệ thống');
          }
        });
      });
      $('#notificationModal').modal('show');
    }

    function show_message_text(message) {
      document.querySelector('#notificationModal .modal-body').textContent = String(message == null ? '' : message);
      $('#notificationModal').modal('show');
    }

    function close_message() {
      $('#notificationModal').modal('hide');
    }

    //xử lý Nghe thử các file âm thanh
    function playAudio_upgrade(filePath) {
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
      // Kiểm tra nếu filePath bắt đầu bằng 'http'
      if (filePath.startsWith('http')) {

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
      const xhr = vbotCreateXhr();
      // Gửi yêu cầu GET tới server để lấy tệp âm thanh dưới dạng base64
      xhr.open('GET', 'includes/php_ajax/Show_file_path.php?audio_b64&path=' + encodeURIComponent(filePath), true);
      xhr.responseType = 'text';
      xhr.onload = function() {
        //loading("hide");
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
        show_message('Yêu cầu phát âm thanh không thành công');
      };

      xhr.send();
    }
  </script>
</head>
<div id="toast"><span id="toastMessage"></span><button onclick="hideToast()">×</button></div>

<!-- Thông báo Mesage html_head.php -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <button type="button" class="btn btn-danger" onclick="close_message()" title="Tắt thông báo"><i class="bi bi-x-circle-fill"></i></button>
      <div class="modal-body">
        <!-- Nội dung thông báo ở đây sẽ được cập nhật bởi JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger rounded-pill" onclick="close_message()">Đóng</button>
      </div>
    </div>
  </div>
</div>
<!--Kết Thúc Thông báo Mesage -->
