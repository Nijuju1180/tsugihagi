

<?php
date_default_timezone_set('Asia/Tokyo');
// チケットの発行枚数・販売枚数・交換済み割合を表示するページ（カラム仕様に対応）
require_once __DIR__ . '/db_access.php';
require_once __DIR__ . '/price.php';

// 権限チェック: ログイン済みかつ閲覧権限
if (!isset($_SESSION['access_id']) || empty($_SESSION['can_view'])) {
    http_response_code(403);
    include __DIR__ . '/header.php';
    echo '<div style="max-width:500px;margin:5em auto;background:#fff;padding:2em;border-radius:8px;box-shadow:0 2px 8px #ccc;font-size:1.2em;color:#c00;">閲覧権限がありません</div>';
    exit;
}

include __DIR__ . '/header.php';

try {
    $pdo = get_pdo();
    // 発行枚数
    $total = $pdo->query('SELECT COUNT(*) FROM ticket')->fetchColumn();
    // 販売枚数（ticketsalesidがNULLでなければ販売済み）
    $sold = $pdo->query('SELECT COUNT(*) FROM ticket WHERE ticketsalesid IS NOT NULL')->fetchColumn();
    // 交換済み枚数（productexchangeidがNULLでなければ交換済み）
    $exchanged = $pdo->query('SELECT COUNT(*) FROM ticket WHERE productexchangeid IS NOT NULL')->fetchColumn();
    // 交換済み割合
    $exchanged_rate = ($total > 0) ? round($exchanged / $total * 100, 1) : 0;

    // メニューごとの集計
    $stmt = $pdo->query('
        SELECT menu,
            COUNT(*) AS total,
            SUM(CASE WHEN ticketsalesid IS NOT NULL THEN 1 ELSE 0 END) AS sold,
            SUM(CASE WHEN productexchangeid IS NOT NULL THEN 1 ELSE 0 END) AS exchanged
        FROM ticket
        GROUP BY menu
        ORDER BY menu
    ');
    $menu_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // メニューごとの売上高計算
    $menu_sales = [];
    $sales_stmt = $pdo->query('SELECT menu, hr, flavor FROM ticket WHERE ticketsalesid IS NOT NULL');
    while ($row = $sales_stmt->fetch(PDO::FETCH_ASSOC)) {
        $menu = $row['menu'];
        if (!isset($menu_sales[$menu])) $menu_sales[$menu] = 0;
        $menu_sales[$menu] += get_ticket_price($row['hr'], $row['menu'], $row['flavor']);
    }

    // 売上高計算
    $sales_stmt = $pdo->query('SELECT hr, menu, flavor FROM ticket WHERE ticketsalesid IS NOT NULL');
    $sales_total = 0;
    while ($row = $sales_stmt->fetch(PDO::FETCH_ASSOC)) {
        $sales_total += get_ticket_price($row['hr'], $row['menu'], $row['flavor']);
    }
} catch (PDOException $e) {
    echo '<div style="color:red;">DB接続失敗: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}


    // --- 時系列集計（1時間ごと） ---
    // 販売
    $sales_time_stats = [];
    $stmt = $pdo->query("SELECT menu, ticketsalestime FROM ticket WHERE ticketsalesid IS NOT NULL");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$row['ticketsalestime']) continue;
        $dt = date('Y-m-d H:00:00', strtotime($row['ticketsalestime']));
        $menu = $row['menu'];
        if (!isset($sales_time_stats[$dt])) $sales_time_stats[$dt] = [];
        if (!isset($sales_time_stats[$dt][$menu])) $sales_time_stats[$dt][$menu] = 0;
        $sales_time_stats[$dt][$menu] += 1;
    }
    // 引換
    $exchange_time_stats = [];
    $stmt = $pdo->query("SELECT menu, productexchangetime FROM ticket WHERE productexchangeid IS NOT NULL");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$row['productexchangetime']) continue;
        $dt = date('Y-m-d H:00:00', strtotime($row['productexchangetime']));
        $menu = $row['menu'];
        if (!isset($exchange_time_stats[$dt])) $exchange_time_stats[$dt] = [];
        if (!isset($exchange_time_stats[$dt][$menu])) $exchange_time_stats[$dt][$menu] = 0;
        $exchange_time_stats[$dt][$menu] += 1;
    }
    // メニュー一覧
    $all_menus = array_unique(array_merge(
        array_column($menu_stats, 'menu'),
        array_keys(isset($menu_sales) ? $menu_sales : [])
    ));
    sort($all_menus);

?>
<main style="max-width:800px;margin:2em auto;padding:2em;border-radius:10px;box-shadow:0 2px 8px #ccc;">
  <h2>チケット集計</h2>
  <div style="font-size:0.9em;color:#888;margin:0.5em;">更新時刻: <?php echo date('Y-m-d H:i:s'); ?></div>
  <table border="1" cellpadding="8" style="width:100%;margin-bottom:1em;">
    <tr><th>発行枚数</th><td><?php echo $total; ?> 枚</td></tr>
    <tr><th>販売枚数</th><td><?php echo $sold; ?> 枚</td></tr>
    <tr><th>交換済み枚数</th><td><?php echo $exchanged; ?> 枚</td></tr>
    <tr><th>交換済み割合</th><td><?php echo $exchanged_rate; ?> %</td></tr>
    <tr><th>売上高</th><td><?php echo number_format($sales_total); ?> 円</td></tr>
  </table>
  <div style="font-size:0.9em;color:#666;">※「販売枚数」は ticketsalesid、「交換済み枚数」は productexchangeid がNULLでない行を集計しています。<br>※売上高はhr,menu,flavorごとに設定した価格で計算しています。</div>

  <h3 style="margin-top:2em;">メニューごとの集計</h3>
  <table border="1" cellpadding="8" style="width:100%;margin-bottom:2em;">
    <tr style="background:#f0f0f0;"><th>メニュー</th><th>発行枚数</th><th>販売枚数</th><th>交換済み枚数</th><th>売上高</th></tr>
    <?php foreach ($menu_stats as $row): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['menu']); ?></td>
        <td><?php echo $row['total']; ?></td>
        <td><?php echo $row['sold']; ?></td>
        <td><?php echo $row['exchanged']; ?></td>
        <td><?php echo isset($menu_sales[$row['menu']]) ? number_format($menu_sales[$row['menu']]) : 0; ?> 円</td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h3 style="margin-top:2em;">メニューごとの状況グラフ</h3>
  <canvas id="menuChart" width="700" height="350"></canvas>
  <h3 style="margin-top:2em;">時刻ごとの販売状況（メニュー別・積み上げ）</h3>
  <canvas id="salesTimeChart" width="900" height="350"></canvas>
  <h3 style="margin-top:2em;">時刻ごとの引換状況（メニュー別・積み上げ）</h3>
  <canvas id="exchangeTimeChart" width="900" height="350"></canvas>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // PHPからデータをJSへ
    const menuStats = <?php echo json_encode($menu_stats, JSON_UNESCAPED_UNICODE); ?>;
    const labels = menuStats.map(row => row.menu);
    const totalData = menuStats.map(row => Number(row.total));
    const soldData = menuStats.map(row => Number(row.sold));
    const exchangedData = menuStats.map(row => Number(row.exchanged));

    // メニュー色
    const menuColors = {
      '焼きそば': 'rgba(100,181,246,0.7)',
      'たこ焼き': 'rgba(255,138,128,0.7)',
      'クレープ': 'rgba(255,202,40,0.7)',
      'カレー': 'rgba(156,204,101,0.7)',
      'ジュース': 'rgba(149,117,205,0.7)'
    };

    // --- 販売・引換の時系列データ ---
    const salesTimeStats = <?php echo json_encode($sales_time_stats, JSON_UNESCAPED_UNICODE); ?>;
    const exchangeTimeStats = <?php echo json_encode($exchange_time_stats, JSON_UNESCAPED_UNICODE); ?>;
    const allMenus = <?php echo json_encode($all_menus, JSON_UNESCAPED_UNICODE); ?>;

    // 時系列ラベル（販売・引換で出現する全時刻をまとめてソート）
    const allTimes = Array.from(new Set([
      ...Object.keys(salesTimeStats),
      ...Object.keys(exchangeTimeStats)
    ])).sort();

    // 販売: メニューごとに時刻ごとの件数
    const salesDatasets = allMenus.map(menu => ({
      label: menu,
      data: allTimes.map(t => salesTimeStats[t]?.[menu] ?? 0),
      backgroundColor: menuColors[menu] || 'rgba(200,200,200,0.7)',
      stack: 'sales'
    }));
    // 引換: メニューごとに時刻ごとの件数
    const exchangeDatasets = allMenus.map(menu => ({
      label: menu,
      data: allTimes.map(t => exchangeTimeStats[t]?.[menu] ?? 0),
      backgroundColor: menuColors[menu] || 'rgba(200,200,200,0.7)',
      stack: 'exchange'
    }));

    // メニューごとの状況グラフ（従来）
    const ctx = document.getElementById('menuChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: '発行枚数',
            data: totalData,
            backgroundColor: 'rgba(100, 181, 246, 0.6)'
          },
          {
            label: '販売枚数',
            data: soldData,
            backgroundColor: 'rgba(255, 202, 40, 0.7)'
          },
          {
            label: '交換済み枚数',
            data: exchangedData,
            backgroundColor: 'rgba(76, 175, 80, 0.7)'
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'メニューごとのチケット状況' }
        },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: '枚数' } }
        }
      }
    });

    // 販売時系列グラフ
    const ctxSales = document.getElementById('salesTimeChart').getContext('2d');
    new Chart(ctxSales, {
      type: 'bar',
      data: {
        labels: allTimes,
        datasets: salesDatasets
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: '時刻ごとのチケット販売件数（メニュー別）' }
        },
        scales: {
          x: { stacked: true, title: { display: true, text: '時刻' } },
          y: { stacked: true, beginAtZero: true, title: { display: true, text: '販売件数' } }
        }
      }
    });

    // 引換時系列グラフ
    const ctxEx = document.getElementById('exchangeTimeChart').getContext('2d');
    new Chart(ctxEx, {
      type: 'bar',
      data: {
        labels: allTimes,
        datasets: exchangeDatasets
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: '時刻ごとの商品引換件数（メニュー別）' }
        },
        scales: {
          x: { stacked: true, title: { display: true, text: '時刻' } },
          y: { stacked: true, beginAtZero: true, title: { display: true, text: '引換件数' } }
        }
      }
    });
  </script>
</main>
