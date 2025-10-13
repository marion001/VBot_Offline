<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
include 'Configuration.php';
?>
<?php
if ($Config['contact_info']['user_login']['active']){
session_start();
if (!isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))) {
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
}
}
?>

<!DOCTYPE html>
<html lang="vi">
<?php
include 'html_head.php';
?>
<body>
<?php
include 'html_header_bar.php'; 
include 'html_sidebar.php';
?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Máy Chủ XiaoZhi MCP</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang Chủ</a></li>
          <li class="breadcrumb-item">XiaoZhi MCP Server</li>
        </ol>
      </nav>
    </div>
	    <section class="section">
        <div class="row">
		
		
		<center><h5 class="text-danger">Comback Soon</h5></center>
		
		</div>
		</section>
	
</main>

<?php
include 'html_footer.php';
?>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<?php
include 'html_js.php';
?>

</body>
</html>