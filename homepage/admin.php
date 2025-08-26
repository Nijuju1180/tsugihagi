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
  <ul>
    <li><a href="edit_members.php">メンバー紹介の追加・編集</a></li>
    <li><a href="edit_shows.php">公演情報の管理</a></li>
    <li><a href="edit_support.php">支援・チケットページの管理</a></li>
  </ul>
  <a href="logout.php">ログアウト</a>
</body>
</html>