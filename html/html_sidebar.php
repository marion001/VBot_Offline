<!--
Code By: Vũ Tuyển
Designed by: BootstrapMade
Facebook: https://www.facebook.com/TWFyaW9uMDAx
-->
<!-- Thông báo Mesage html_slidebar.php -->
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

<!-- Loading Mesage-->
    <div id="loadingOverlay" class="overlay_loading">
	<div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
<div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status">
  <span class="sr-only">________</span>
</div>
<div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
</div>
    </div>
<!--Kết thúc Loading Mesage-->

<!--Play nghe thử âm thanh html_sidebar.php-->
<audio id="audioPlayer" style="display: none;" controls></audio>
<video id="videoPlayer" style="display: none;" controls></video>

<!--Kết thúc nghe thử âm thanh html_sidebar.php-->
  <aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item" onclick="loading('show')">
        <a class="nav-link " href="index.php">
          <i class="bi bi-grid"></i>
          <span>Bảng điều Khiển</span>
        </a>
      </li><!-- End Dashboard Nav -->


      <li class="nav-item" onclick="loading('show')">
        <a class="nav-link collapsed" href="Media_Player.php">
          <i class="bi bi-music-note-list"></i><span>Media Player</span>
        </a>
      </li>


      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
         <i class="bi bi-journal-code"></i><span>Log, Cache</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="Log_TTS.php">
              <i class="bi bi-circle"></i><span>Log TTS</span>
            </a>
          </li>
          <li>
            <a href="Log_API.php">
              <i class="bi bi-circle"></i><span>Log Hệ Thống (API)</span>
            </a>
          </li>
          <li>
            <a href="Log_pycache.php">
              <i class="bi bi-circle"></i><span>__pycache__</span>
            </a>
          </li>
		  <!--
          <li>
            <a href="#">
              <i class="bi bi-circle"></i><span>Cache ZingMP3</span>
            </a>
          </li>
          <li>
            <a href="#">
              <i class="bi bi-circle"></i><span>Cache PodCast</span>
            </a>
          </li>
          <li>
            <a href="#">
              <i class="bi bi-circle"></i><span>Cache Youtube</span>
            </a>
          </li>
		  -->
        </ul>
      </li><!-- End Icons Nav -->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-arrow-repeat"></i><span>Sao Lưu, Cập nhật</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="charts-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="Upgrade_Program.php">
              <i class="bi bi-circle"></i><span>Chương Trình</span>
            </a>
          </li>
          <li>
            <a href="Upgrade_Dashboard.php">
              <i class="bi bi-circle"></i><span>Giao Diện</span>
            </a>
          </li>
        </ul>
      </li><!-- End Charts Nav -->
      <li class="nav-heading">Trang</li>

<!--
      <li class="nav-item">
        <a class="nav-link collapsed" href="Users_Profile.php">
          <i class="bi bi-person"></i>
          <span>Cá Nhân</span>
        </a>
      </li>
	  -->
	  
	  <!-- End Profile Page Nav -->


      <li class="nav-item" onclick="loading('show')">
        <a class="nav-link collapsed" href="Config.php">
          <i class="bi bi-gear-fill"></i>
          <span>Cấu hình Config</span>
        </a>
      </li>
	  
      <li class="nav-item" onclick="loading('show')">
        <a class="nav-link collapsed" href="Command.php">
          <i class="bi bi-terminal-fill"></i>
          <span>Command/Terminal</span>
        </a>
      </li>

      <li class="nav-item" onclick="loading('show')">
        <a class="nav-link collapsed" href="Wifi.php">
         <i class="bi bi-wifi"></i>
          <span>Wifi</span>
        </a>
      </li>

      <li class="nav-item" onclick="loading('show')">
        <a class="nav-link collapsed" href="System_Information.php">
         <i class="bi bi-info-circle-fill"></i>
          <span>Thông tin hệ thống</span>
        </a>
      </li>

    </ul>

  </aside>