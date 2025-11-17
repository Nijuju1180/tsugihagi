<?php
session_start();

// 未ログインならログインページへ
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>管理画面</title>
</head>
<body>
  <h1>劇団つぎはぎ 管理画面</h1>
  <h2>アクセスカウンター</h2>
    <!--アクセスカウンター(外部サイトに依存)-->
  <table border="0" cellspacing="0" cellpadding="0"><tr><td align="center"><a href="http://www.rays-counter.com/"><img src="http://www.rays-counter.com/d1303_f6_004/691a96f8dc3f5/" alt="アクセスカウンター" border="0"></a></td></tr><tr><td align="center"><img src="http://www.rays-counter.com/images/counter_01.gif" border="0"><img src="http://www.rays-counter.com/images/counter_02.gif" border="0"><img src="http://www.rays-counter.com/images/counter_03.gif" border="0"><img src="http://www.rays-counter.com/images/counter_04.gif" border="0" ><img src="http://www.rays-counter.com/images/counter_05.gif" border="0"></td></tr></table>
    <!--ここまで-->

  <ul>
    <li><a href="edit_members.php">メンバー紹介の追加・編集</a></li>
    <li><a href="edit_shows.php">公演情報の管理</a></li>
    <li><a href="edit_support.php">支援・チケットページの管理</a></li>
  </ul>
  <a href="home.html">ログアウト</a>
</body>
</html>