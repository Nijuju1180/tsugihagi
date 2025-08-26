<?php
session_start();

// すでにログイン済みなら管理画面へ
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: admin.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';

    // 本番では password_hash / password_verify を使う
    $correctPassword = "tsugihagi2025"; 

    if ($pw === $correctPassword) {
        $_SESSION['logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "パスワードが間違っています";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>管理ログイン</title>
  <link rel="stylesheet" href="css/main.css">
</head>
<body>
  <div class="login-container">
    <h1>管理ログイン</h1>
    <form method="post" action="">
      <input type="password" name="password" placeholder="パスワードを入力" required>
      <button type="submit">ログイン</button>
    </form>
    <?php if ($error): ?>
      <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>
</body>
</html>