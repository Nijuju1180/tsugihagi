<?php
// チケット一覧とQRコード表示ページ
require_once __DIR__ . '/db_access.php';

// チケット一覧取得
$pdo = get_pdo();
$tickets = $pdo->query('SELECT * FROM ticket ORDER BY ticketid')->fetchAll();

function make_qr_string($row) {
    // year,hr,menu,flavor,ticketid
    $year = date('Y');
    return $year . ',' . $row['hr'] . ',' . $row['menu'] . ',' . $row['flavor'] . ',' . $row['ticketid'];
}

// PHP: チケット一覧API
if (isset($_GET['api']) && $_GET['api'] === 'ticketlist') {
    header('Content-Type: application/json');
    $pdo = get_pdo();
    $tickets = $pdo->query('SELECT * FROM ticket ORDER BY ticketid')->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'tickets' => $tickets,
        'year' => date('Y'),
        'updated' => date('Y-m-d H:i:s'),
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>チケットQR一覧</title>
    <style>
        body { font-family: sans-serif; background: #f7f7f7; }
        main { max-width: 900px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 10px; box-shadow: 0 2px 8px #ccc; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 0.5em 0.7em; text-align: center; }
        th { background: #eee; }
        tr:hover { background: #f0f8ff; cursor: pointer; }
        .overlay { display: none; position: fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:1000; }
        .overlay-content { background:#fff; padding:2em; border-radius:10px; box-shadow:0 2px 8px #333; text-align:center; }
        .overlay img { margin: 1em 0; }
        .close-btn { margin-top:1em; padding:0.5em 2em; font-size:1em; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0/lib/qr-code-styling.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
    <script>
let qrCodeObj = null;

function showQR(qr, info) {
    document.getElementById('qr-info').textContent = info;
    document.getElementById('qr-overlay').style.display = 'flex';
    const qrContainer = document.getElementById('qr-img-container');
    qrContainer.innerHTML = '';
    qrCodeObj = new QRCodeStyling({
        width: 256,
        height: 256,
        type: "svg",
        data: qr,
        image: '', // ここにロゴ画像URLを指定可能
        dotsOptions: {
            color: "#222",
            type: "rounded"
        },
        backgroundOptions: {
            color: "#fff"
        },
        cornersSquareOptions: {
            color: "#1976d2",
            type: "extra-rounded"
        },
        cornersDotOptions: {
            color: "#1976d2",
            type: "dot"
        },
        qrOptions: {
            errorCorrectionLevel: 'L',
            version: 1
        }
    });
    qrCodeObj.append(qrContainer);
}
function closeQR() {
    document.getElementById('qr-overlay').style.display = 'none';
    const qrContainer = document.getElementById('qr-img-container');
    qrContainer.innerHTML = '';
    qrCodeObj = null;
}
    </script>
</head>
<body>
<main>
    <h2>チケット一覧（クリックでQR表示）</h2>
    <form id="dummy-generate-form" style="margin-bottom:2em;">
        <label>ダミーチケット生成（1～500）: <input type="number" id="dummy-count" name="count" value="100" min="1" max="50000"></label>
        <button type="submit">生成</button>
    </form>
    <div id="dummy-generate-result"></div>
    <script>
    document.getElementById('dummy-generate-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const count = document.getElementById('dummy-count').value;
        const resultDiv = document.getElementById('dummy-generate-result');
        resultDiv.textContent = '生成中...';
        try {
            const res = await fetch(location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ dummy_generate: true, count })
            });
            const data = await res.json();
            if (data.success) {
                resultDiv.style.color = 'green';
                resultDiv.textContent = data.message;
                setTimeout(() => location.reload(), 1000);
            } else {
                resultDiv.style.color = 'red';
                resultDiv.textContent = 'エラー: ' + data.message;
            }
        } catch (err) {
            resultDiv.style.color = 'red';
            resultDiv.textContent = '通信エラー';
        }
    });
    </script>
    <?php
    // ダミーチケット生成API処理
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
        && (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
    ) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['dummy_generate'])) {
            $count = isset($input['count']) ? max(1, min(50000, (int)$input['count'])) : 100;
            $inserted = 0;
            $error = '';
            try {
                $menus = ['yakisoba', 'takoyaki', 'crepe', 'curry', 'juice'];
                $flavors = ['A', 'B', 'C'];
                $hrs = range(1, 6);
                $pdo->beginTransaction();
                $pdo->exec('DELETE FROM ticket');
                $stmt = $pdo->prepare('INSERT INTO ticket (ticketid, flavor, hr, menu, ticketissuetime, ticketsalesid, ticketsalestime, productexchangeid, productexchangetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                for ($i = 0; $i < $count; $i++) {
                    $ticketid = $i + 1;
                    $flavor = $flavors[array_rand($flavors)];
                    $hr = $hrs[array_rand($hrs)];
                    $menu = $menus[array_rand($menus)];
                    $issue = date('Y-m-d H:i:s', strtotime('-'.rand(0,10).' days -'.rand(0, 86400).' seconds'));
                    $sold = rand(0,1);
                    $exchanged = $sold ? rand(0,1) : 0;
                    $salesid = $sold ? rand(1,10) : null;
                    $salestime = $sold ? date('Y-m-d H:i:s', strtotime($issue.' +'.rand(1, 86400).' seconds')) : null;
                    $exchangeid = $exchanged ? rand(1,10) : null;
                    $exchangetime = $exchanged ? date('Y-m-d H:i:s', strtotime($salestime.' +'.rand(1, 86400).' seconds')) : null;
                    $stmt->execute([$ticketid, $flavor, $hr, $menu, $issue, $salesid, $salestime, $exchangeid, $exchangetime]);
                    $inserted++;
                }
                $pdo->commit();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $inserted . '件のデータを追加しました。']);
                exit;
            } catch (Exception $e) {
                if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }
    }
    ?>
    <div style="margin-bottom:1em;">
        <span id="last-update-time">更新日時: -</span>
        <button id="refresh-btn" style="margin-left:1em;">テーブルを更新</button>
        <input type="text" id="table-search" placeholder="検索..." style="margin-left:2em;padding:0.3em 1em;">
    </div>
    <div id="ticket-table-area">
        <table id="ticket-table">
            <thead>
                <tr>
                    <th class="sort" data-sort="ticketid">ID</th>
                    <th class="sort" data-sort="flavor">種別</th>
                    <th class="sort" data-sort="hr">クラス</th>
                    <th class="sort" data-sort="menu">メニュー</th>
                    <th class="sort" data-sort="ticketissuetime">発行日時</th>
                    <th class="sort" data-sort="ticketsalesid">販売ID</th>
                    <th class="sort" data-sort="ticketsalestime">販売日時</th>
                    <th class="sort" data-sort="productexchangeid">引換ID</th>
                    <th class="sort" data-sort="productexchangetime">引換日時</th>
                </tr>
            </thead>
            <tbody id="ticket-table-body" class="list">
            <?php foreach ($tickets as $row): 
                $qr = make_qr_string($row);
                $info = "ticket: $qr";
            ?>
                <tr onclick="showQR('<?php echo htmlspecialchars($qr); ?>', '<?php echo htmlspecialchars($info); ?>')">
                    <td class="ticketid"><?php echo $row['ticketid']; ?></td>
                    <td class="flavor"><?php echo htmlspecialchars($row['flavor']); ?></td>
                    <td class="hr"><?php echo htmlspecialchars($row['hr']); ?></td>
                    <td class="menu"><?php echo htmlspecialchars($row['menu']); ?></td>
                    <td class="ticketissuetime"><?php echo htmlspecialchars($row['ticketissuetime']); ?></td>
                    <td class="ticketsalesid"><?php echo htmlspecialchars($row['ticketsalesid']); ?></td>
                    <td class="ticketsalestime"><?php echo htmlspecialchars($row['ticketsalestime']); ?></td>
                    <td class="productexchangeid"><?php echo htmlspecialchars($row['productexchangeid']); ?></td>
                    <td class="productexchangetime"><?php echo htmlspecialchars($row['productexchangetime']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    async function fetchTicketListAndUpdateTable() {
        const res = await fetch(location.pathname + '?api=ticketlist');
        const data = await res.json();
        const tbody = document.getElementById('ticket-table-body');
        tbody.innerHTML = '';
        data.tickets.forEach(row => {
            const tr = document.createElement('tr');
            const qr = `${data.year},${row.hr},${row.menu},${row.flavor},${row.ticketid}`;
            const info = `ticket: ${qr}`;
            tr.onclick = () => showQR(qr, info);
            tr.innerHTML = `
                <td class="ticketid">${row.ticketid}</td>
                <td class="flavor">${row.flavor}</td>
                <td class="hr">${row.hr}</td>
                <td class="menu">${row.menu}</td>
                <td class="ticketissuetime">${row.ticketissuetime}</td>
                <td class="ticketsalesid">${row.ticketsalesid ?? ''}</td>
                <td class="ticketsalestime">${row.ticketsalestime ?? ''}</td>
                <td class="productexchangeid">${row.productexchangeid ?? ''}</td>
                <td class="productexchangetime">${row.productexchangetime ?? ''}</td>
            `;
            tbody.appendChild(tr);
        });
        document.getElementById('last-update-time').textContent = '更新日時: ' + data.updated;
        if(window.ticketListObj) window.ticketListObj.reIndex();
    }
    document.getElementById('refresh-btn').onclick = fetchTicketListAndUpdateTable;
    // List.jsによるフィルタ・ソート
    window.ticketListObj = new List('ticket-table-area', {
        valueNames: [ 'ticketid', 'flavor', 'hr', 'menu', 'ticketissuetime', 'ticketsalesid', 'ticketsalestime', 'productexchangeid', 'productexchangetime' ],
        listClass: 'list',
        searchClass: 'search',
        sortClass: 'sort',
    });
    document.getElementById('table-search').addEventListener('input', function() {
        window.ticketListObj.search(this.value);
    });
    // 初期表示
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('last-update-time').textContent = '更新日時: ' + new Date().toLocaleString();
    });
    </script>
</main>
<div class="overlay" id="qr-overlay" onclick="closeQR()">
    <div class="overlay-content" onclick="event.stopPropagation()">
        <div id="qr-info" style="font-size:1.1em;margin-bottom:1em;"></div>
        <div id="qr-img-container"></div>
        <button class="close-btn" onclick="closeQR()">閉じる</button>
    </div>
</div>
</body>
</html>
