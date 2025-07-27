<?php
date_default_timezone_set('Asia/Tokyo');
// admin.php: 管理者専用ユーザー作成ページ
session_start();
require_once __DIR__ . '/db_access.php';

// 管理者チェック
if (!isset($_SESSION['access_id']) || $_SESSION['access_id'] !== 'bem130') {
    http_response_code(403);
    include __DIR__ . '/header.php';
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>権限エラー</title></head><body style="font-family:sans-serif;background:#f5f5f5;"><div style="margin:5em auto;width:350px;background:#fff;padding:2em;border-radius:8px;box-shadow:0 2px 8px #ccc;"><h2 style="color:red;">権限がありません</h2><p>このページは管理者のみアクセス可能です。</p></div></body></html>';
    exit;
}

$message = '';
// ユーザー削除処理（AJAX）
if (isset($_POST['delete_user']) && isset($_POST['access_id'])) {
    header('Content-Type: application/json');
    $access_id = $_POST['access_id'];
    if ($access_id === 'bem130') {
        echo json_encode(['error' => '管理者自身は削除できません']);
        exit;
    }
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('DELETE FROM accesspermission WHERE access_id = ?');
        $stmt->execute([$access_id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
// パスワード変更処理（AJAX）
if (isset($_POST['change_password']) && isset($_POST['access_id']) && isset($_POST['new_password'])) {
    header('Content-Type: application/json');
    $access_id = $_POST['access_id'];
    $new_password = $_POST['new_password'];
    if (!$new_password) {
        echo json_encode(['error' => '新しいパスワードを入力してください']);
        exit;
    }
    try {
        $pdo = get_pdo();
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE accesspermission SET password_hash = ? WHERE access_id = ?');
        $stmt->execute([$hash, $access_id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
// ユーザー一覧取得
$users = [];
try {
    $pdo = get_pdo();
    $users = $pdo->query('SELECT * FROM accesspermission ORDER BY access_id')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'ユーザー一覧取得失敗: ' . htmlspecialchars($e->getMessage());
}

// ユーザー作成処理（AJAX）
if (isset($_POST['create_user']) && isset($_POST['access_id']) && isset($_POST['password'])) {
    header('Content-Type: application/json');
    $access_id = trim($_POST['access_id']);
    $password = $_POST['password'];
    $can_product_exchange = isset($_POST['can_product_exchange']) ? 1 : 0;
    $can_ticket_sales = isset($_POST['can_ticket_sales']) ? 1 : 0;
    $can_view = isset($_POST['can_view']) ? 1 : 0;
    if ($access_id && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo = get_pdo();
            $stmt = $pdo->prepare('INSERT INTO accesspermission (access_id, password_hash, can_product_exchange, can_ticket_sales, can_view) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$access_id, $hash, $can_product_exchange, $can_ticket_sales, $can_view]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => '全ての必須項目を入力してください。']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザー作成 | チケット管理システム</title>
    <style>
        body {
            font-family: sans-serif;
            background: linear-gradient(45deg,#667eea,#764ba2);
            background-color: #667eea;
            min-height: 100vh;
        }
        main {
            max-width: 900px;
            margin: 2em auto;
            padding: 2em;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ccc;
            background: #fff;
        }
        .box {
            background: #fafaff;
            padding: 2em;
            margin: 2em 0;
            border-radius: 12px;
            box-shadow: 0 2px 8px #eee;
        }
        .box h2 { margin-top: 0; }
        .box input[type=text], .box input[type=password] { width: 100%; padding: 0.5em; margin: 0.5em 0; }
        .box label { display: block; margin: 0.5em 0; }
        .msg { color: green; }
        .err { color: red; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: center; }
        th { background: #f0f0f0; }
        .top-link { text-align: right; margin-bottom: 1em; }
        .top-link a { color: #1976d2; text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<main>
  <div class="top-link">
    <a href="ticket_qr_list.php">→ チケットQR一覧ページへ</a>
  </div>
  <div class="box">
      <h2>ユーザー作成</h2>
      <?php if ($message): ?>
          <div class="<?php echo strpos($message, '失敗') !== false ? 'err' : 'msg'; ?>"><?php echo $message; ?></div>
      <?php endif; ?>
      <form id="user-create-form">
          <label>アクセスID（半角英数字）<br>
              <input type="text" name="access_id" required pattern="[A-Za-z0-9_]+">
          </label>
          <label>パスワード<br>
              <input type="password" name="password" required>
          </label>
          <label><input type="checkbox" name="can_product_exchange"> 商品交換権限</label>
          <label><input type="checkbox" name="can_ticket_sales"> チケット販売権限</label>
          <label><input type="checkbox" name="can_view"> 閲覧権限</label>
          <button type="submit">作成</button>
      </form>
  </div>
  <div class="box">
      <h2>ユーザー管理</h2>
      <table id="user-table" border="1" style="width:100%;text-align:center;">
          <thead>
              <tr style="background:#f0f0f0;">
                  <th>ID</th><th>商品交換</th><th>チケット販売</th><th>閲覧</th><th>パスワード変更</th><th>削除</th>
              </tr>
          </thead>
          <tbody>
          <?php foreach ($users as $u): ?>
              <tr data-access-id="<?php echo htmlspecialchars($u['access_id']); ?>">
                  <td><?php echo htmlspecialchars($u['access_id']); ?></td>
                  <td><?php echo $u['can_product_exchange'] ? '✔' : ''; ?></td>
                  <td><?php echo $u['can_ticket_sales'] ? '✔' : ''; ?></td>
                  <td><?php echo $u['can_view'] ? '✔' : ''; ?></td>
                  <td>
                      <form class="pwchange-form" style="display:inline;">
                          <input type="password" name="new_password" placeholder="新PW" style="width:7em;">
                          <button type="submit">変更</button>
                      </form>
                  </td>
                  <td>
                      <?php if ($u['access_id'] !== 'bem130'): ?>
                      <button class="delete-btn" style="color:#c00;">削除</button>
                      <?php endif; ?>
                  </td>
              </tr>
          <?php endforeach; ?>
          </tbody>
      </table>
  </div>
</main>
<script>
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.onclick = function() {
        const tr = btn.closest('tr');
        const access_id = tr.dataset.accessId;
        if (!confirm(`ユーザー「${access_id}」を削除しますか？`)) return;
        fetch(location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ delete_user: 1, access_id })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                tr.remove();
            } else {
                alert(data.error || '削除失敗');
            }
        });
    };
});
document.querySelectorAll('.pwchange-form').forEach(form => {
    form.onsubmit = function(e) {
        e.preventDefault();
        const tr = form.closest('tr');
        const access_id = tr.dataset.accessId;
        const new_password = form.new_password.value;
        if (!new_password) return alert('新しいパスワードを入力してください');
        if (!confirm(`ユーザー「${access_id}」のパスワードを変更しますか？`)) return;
        fetch(location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ change_password: 1, access_id, new_password })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                alert('パスワードを変更しました');
                form.new_password.value = '';
            } else {
                alert(data.error || '変更失敗');
            }
        });
    };
});
// ユーザー作成フォームのAjax化
document.getElementById('user-create-form').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    fd.append('create_user', 1);
    fetch(location.pathname, {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(data => {
        if (data.success) {
            alert('ユーザーを作成しました。');
            location.reload();
        } else {
            alert(data.error || '作成失敗');
        }
    });
};
</script>
</body>
</html>
