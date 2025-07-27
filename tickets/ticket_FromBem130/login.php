<?php
date_default_timezone_set('Asia/Tokyo');
// login.php: ログイン画面
session_start();
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン | チケット管理システム</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; }
        .login-box { background: #fff; padding: 2em; margin: 5em auto; width: 320px; border-radius: 8px; box-shadow: 0 2px 8px #ccc; }
        .login-box h2 { margin-top: 0; }
        .login-box input { width: 100%; padding: 0.5em; margin: 0.5em 0; }
        .error { color: red; }
    </style>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<div class="login-box">
    <h2>ログイン</h2>
    <?php if ($error): ?>
        <div class="error">ログインに失敗しました</div>
    <?php endif; ?>
    <form method="post" action="login_check.php">
        <label>アクセスID<br>
            <input type="text" name="access_id" required autofocus>
        </label><br>
        <label>パスワード<br>
            <input type="password" name="password" required>
        </label><br>
        <button type="submit">ログイン</button>
    </form>
</div>
</body>
</html>
