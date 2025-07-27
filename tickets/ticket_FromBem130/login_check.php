
<?php
date_default_timezone_set('Asia/Tokyo');
// login_check.php: ログイン認証処理
session_start();
require_once __DIR__ . '/db_access.php';

// 入力値取得
$access_id = isset($_POST['access_id']) ? $_POST['access_id'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

try {
    $pdo = get_pdo();
    // accesspermissionテーブルから該当ユーザー取得
    $stmt = $pdo->prepare('SELECT * FROM accesspermission WHERE access_id = ?');
    $stmt->execute([$access_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // 認証成功
        $_SESSION['access_id'] = $user['access_id'];
        $_SESSION['can_product_exchange'] = $user['can_product_exchange'];
        $_SESSION['can_ticket_sales'] = $user['can_ticket_sales'];
        $_SESSION['can_view'] = $user['can_view'];
        header('Location: index.php');
        exit;
    } else {
        // 認証失敗
        header('Location: login.php?error=1');
        exit;
    }
} catch (PDOException $e) {
    echo 'DB接続失敗: ' . $e->getMessage();
    exit;
}
