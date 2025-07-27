
<?php
date_default_timezone_set('Asia/Tokyo');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ログイン状態
$is_logged_in = isset($_SESSION['access_id']);
$access_id = $is_logged_in ? $_SESSION['access_id'] : null;

// 現在のページ名取得
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!-- 共通CSSの読み込み -->
<link rel="stylesheet" href="style.css">
<style>
  .nav-link {
    color: #fff;
    text-decoration: none;
    margin-right: 0.2em;
    border-radius: 6px;
    padding: 0.3em 0.3em;
    transition: background 0.2s, box-shadow 0.2s;
  }
  .nav-link:hover {
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }
  .nav-link.active {
    background: #fff;
    color: #333 !important;
    font-weight: bold;
    box-shadow: 0 2px 8px #2224, 0 0 0 2px #fff inset;
    pointer-events: none;
  }
</style>
<header style="background:#333;color:#fff;padding:1em 0;margin-bottom:2em;">
  <nav style="max-width:900px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;">
    <div>
      <a href="index.php" class="nav-link<?php if ($current_page === 'index.php') echo ' active'; ?>" style="font-weight:bold;">ホーム</a>
      <a href="ticketsales.php" class="nav-link<?php if ($current_page === 'ticketsales.php') echo ' active'; ?>">チケット販売</a>
      <a href="productexchange.php" class="nav-link<?php if ($current_page === 'productexchange.php') echo ' active'; ?>">商品引換</a>
      <a href="ticket_status.php" class="nav-link<?php if ($current_page === 'ticket_status.php') echo ' active'; ?>">チケット状態確認</a>
      <a href="view.php" class="nav-link<?php if ($current_page === 'view.php') echo ' active'; ?>">販売状況閲覧</a>
      <a href="admin.php" class="nav-link<?php if ($current_page === 'admin.php') echo ' active'; ?>">管理</a>
    </div>
    <div>
      <?php if ($is_logged_in): ?>
        <span style="margin-right:1em;">ログイン中: <b><?php echo htmlspecialchars($access_id); ?></b></span>
        <a href="logout.php" style="color:#fff;text-decoration:underline;">ログアウト</a>
      <?php else: ?>
        <a href="login.php" style="color:#fff;text-decoration:underline;">ログイン</a>
      <?php endif; ?>
    </div>
  </nav>
</header>
