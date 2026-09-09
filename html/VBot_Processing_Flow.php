<?php
include 'Configuration.php';
if ($Config['contact_info']['user_login']['active']) {
    session_start();
    if (!isset($_SESSION['user_login']) || (isset($_SESSION['user_login']['login_time']) && time() - $_SESSION['user_login']['login_time'] > 43200)) {
        session_unset();
        session_destroy();
        header('Location: Login.php');
        exit;
    }
    $_SESSION['user_login']['login_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="vi">
<?php include 'html_head.php'; ?>
<body>
<?php include 'html_header_bar.php'; include 'html_sidebar.php'; ?>
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Sơ Đồ Xử Lý VBot</h1>
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
      <li class="breadcrumb-item active">Kiến trúc và luồng xử lý hoàn chỉnh</li>
    </ol></nav>
  </div>
  <section class="section">
    <div class="card"><div class="card-body pt-3">
      <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <span class="text-muted">Tài liệu kỹ thuật tương tác, hoạt động offline.</span>
        <a class="btn btn-sm btn-outline-primary ms-auto" href="VBot_Processing_Flow.html" target="_blank">
          <i class="bi bi-box-arrow-up-right"></i> Mở toàn màn hình
        </a>
      </div>
      <iframe
        src="VBot_Processing_Flow.html"
        title="Sơ đồ xử lý hoàn chỉnh của VBot"
        loading="eager"
        style="display:block;width:100%;height:calc(100vh - 220px);min-height:720px;border:1px solid #d6dce5;border-radius:10px"
      ></iframe>
    </div></div>
  </section>
</main>
<?php include 'html_footer.php'; ?>
<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<?php include 'html_js.php'; ?>
</body>
</html>
