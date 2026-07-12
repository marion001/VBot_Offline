<?php
#Code By: Vũ Tuyển
#Designed by: BootstrapMade
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

include 'Configuration.php';

if ($Config['contact_info']['user_login']['active']) {
  session_start();
  if (
    !isset($_SESSION['user_login']) ||
    (isset($_SESSION['user_login']['login_time']) && (time() - $_SESSION['user_login']['login_time'] > 43200))
  ) {
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
  <!-- ======= Header ======= -->
  <?php
  include 'html_header_bar.php';
  ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.8.1/github-markdown.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github.min.css">

    <style>
        body {
            background: #f6f8fa;
            margin: 0;
            padding: 20px;
        }
        .markdown-body {
            margin: 0 auto;
            padding: 45px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        @media (max-width: 767px) {
            .markdown-body {
                padding: 15px;
            }
        }
    </style>
</head>
  <?php
  include 'html_sidebar.php';
  ?>
  <main id="main" class="main">

    <div class="pagetitle">
      <h1>README.md</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item" onclick="loading('show')"><a href="index.php">Trang chủ</a></li>
          <li class="breadcrumb-item active">README.md</li>
        </ol>
      </nav>
    </div>
    <section class="section">


<div id="markdown" class="markdown-body">
    Đang tải...
</div>

<script src="https://cdn.jsdelivr.net/npm/markdown-it/dist/markdown-it.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>

<?php
$markdown = "";
$file = $VBot_Offline.'README.md';
if (is_file($file)) {
    $markdown = file_get_contents($file);
}
?>

<script>
const md = window.markdownit({
    html: false,
    linkify: true,
    typographer: true,
    breaks: false,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return '<pre><code class="hljs">' +
                    hljs.highlight(str, { language: lang }).value +
                    '</code></pre>';
            } catch (e) {}
        }
        return '<pre><code class="hljs">' +
            md.utils.escapeHtml(str) +
            '</code></pre>';
    }
});

const markdown = <?= json_encode($markdown, JSON_UNESCAPED_UNICODE) ?>;

document.getElementById("markdown").innerHTML = md.render(markdown);
</script>

    </section>

  </main>


  <!-- ======= Footer ======= -->
  <?php
  include 'html_footer.php';
  ?>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <?php
  include 'html_js.php';
  ?>

</body>

</html>