<?php
date_default_timezone_set('Asia/Tokyo');
// 商品引換ページ
require_once __DIR__ . '/db_access.php';
require_once __DIR__ . '/price.php';

// 権限チェック: ログイン済みかつ商品引換権限
if (!isset($_SESSION['access_id']) || empty($_SESSION['can_product_exchange'])) {
    http_response_code(403);
    include __DIR__ . '/header.php';
    echo '<div style="max-width:500px;margin:5em auto;background:#fff;padding:2em;border-radius:8px;box-shadow:0 2px 8px #ccc;font-size:1.2em;color:#c00;">商品引換権限がありません</div>';
    exit;
}

// --- Ajax: チケット情報取得・バリデーションAPI（引換用） ---
if (isset($_GET['api']) && $_GET['api'] === 'exchange_ticketinfo' && isset($_POST['ticketid'])) {
    if (!isset($_SESSION['access_id']) || empty($_SESSION['can_product_exchange'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => '権限がありません']);
        exit;
    }
    header('Content-Type: application/json');
    $ticketid = intval($_POST['ticketid']);
    $flavor = $_POST['flavor'] ?? null;
    $hr = $_POST['hr'] ?? null;
    $menu = $_POST['menu'] ?? null;
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT * FROM ticket WHERE ticketid = ? AND flavor = ? AND hr = ? AND menu = ?');
        $stmt->execute([$ticketid, $flavor, $hr, $menu]);
        $ticket = $stmt->fetch();
        if (!$ticket) {
            echo json_encode(['error' => '該当チケットが見つかりません。']);
            exit;
        } elseif ($ticket['ticketsalesid'] === null) {
            echo json_encode(['error' => 'このチケットは未販売です。']);
            exit;
        } elseif ($ticket['productexchangeid'] !== null) {
            echo json_encode(['error' => 'このチケットは既に引換済みです。']);
            exit;
        } else {
            $price = get_ticket_price($ticket['hr'], $ticket['menu'], $ticket['flavor']);
            echo json_encode([
                'ticketid' => $ticket['ticketid'],
                'hr' => $ticket['hr'],
                'menu' => $ticket['menu'],
                'flavor' => $ticket['flavor'],
                'price' => $price,
                'status' => '未引換'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'DBエラー: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
}

// --- Ajax: 商品引換処理API ---
if (isset($_GET['api']) && $_GET['api'] === 'exchange' && isset($_POST['ticketid'])) {
    if (!isset($_SESSION['access_id']) || empty($_SESSION['can_product_exchange'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => '権限がありません']);
        exit;
    }
    header('Content-Type: application/json');
    $ticketid = intval($_POST['ticketid']);
    $flavor = $_POST['flavor'] ?? null;
    $hr = $_POST['hr'] ?? null;
    $menu = $_POST['menu'] ?? null;
    $access_id = $_SESSION['access_id'] ?? null;
    try {
        $pdo = get_pdo();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM ticket WHERE ticketid = ? AND flavor = ? AND hr = ? AND menu = ? FOR UPDATE');
        $stmt->execute([$ticketid, $flavor, $hr, $menu]);
        $ticket = $stmt->fetch();
        if (!$ticket) {
            $pdo->rollBack();
            echo json_encode(['error' => '該当チケットが見つかりません。']);
            exit;
        } elseif ($ticket['ticketsalesid'] === null) {
            $pdo->rollBack();
            echo json_encode(['error' => 'このチケットは未販売です。']);
            exit;
        } elseif ($ticket['productexchangeid'] !== null) {
            $pdo->rollBack();
            echo json_encode(['error' => 'このチケットは既に引換済みです。']);
            exit;
        } else {
            $stmt = $pdo->prepare('UPDATE ticket SET productexchangeid = ?, productexchangetime = NOW() WHERE ticketid = ? AND flavor = ? AND hr = ? AND menu = ?');
            $stmt->execute([$access_id, $ticketid, $flavor, $hr, $menu]);
            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['error' => 'DBエラー: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
}

// --- Ajax: 商品引換処理API（バルク） ---
if (isset($_GET['api']) && $_GET['api'] === 'bulk_exchange' && isset($_POST['ticketids'])) {
    header('Content-Type: application/json');
    $ticketids = $_POST['ticketids'];
    $flavors = $_POST['flavors'] ?? [];
    $hrs = $_POST['hrs'] ?? [];
    $menus = $_POST['menus'] ?? [];
    $access_id = $_SESSION['access_id'] ?? null;
    $results = [];
    try {
        $pdo = get_pdo();
        $pdo->beginTransaction();
        $has_error = false;
        foreach ($ticketids as $i => $ticketid) {
            $ticketid = intval($ticketid);
            $flavor = $flavors[$i] ?? null;
            $hr = $hrs[$i] ?? null;
            $menu = $menus[$i] ?? null;
            $stmt = $pdo->prepare('SELECT * FROM ticket WHERE ticketid = ? AND flavor = ? AND hr = ? AND menu = ? FOR UPDATE');
            $stmt->execute([$ticketid, $flavor, $hr, $menu]);
            $ticket = $stmt->fetch();
            if (!$ticket) {
                $results[] = ['ticketid' => $ticketid, 'error' => '該当チケットが見つかりません。'];
                $has_error = true;
            } elseif ($ticket['ticketsalesid'] === null) {
                $results[] = ['ticketid' => $ticketid, 'error' => 'このチケットは未販売です。'];
                $has_error = true;
            } elseif ($ticket['productexchangeid'] !== null) {
                $results[] = ['ticketid' => $ticketid, 'error' => 'このチケットは既に引換済みです。'];
                $has_error = true;
            } else {
                $stmt = $pdo->prepare('UPDATE ticket SET productexchangeid = ?, productexchangetime = NOW() WHERE ticketid = ? AND flavor = ? AND hr = ? AND menu = ?');
                $stmt->execute([$access_id, $ticketid, $flavor, $hr, $menu]);
                $results[] = ['ticketid' => $ticketid, 'success' => '引換処理が完了しました。', 'access_id' => $access_id];
            }
        }
        if ($has_error) {
            $pdo->rollBack();
            foreach ($results as &$r) {
                if (isset($r['success'])) {
                    unset($r['success']);
                    $r['error'] = 'ロールバックされました（他にエラーがあったため）';
                }
            }
            unset($r);
        } else {
            $pdo->commit();
        }
        echo json_encode($results);
        exit;
    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        echo json_encode([['error' => 'DBエラー: ' . htmlspecialchars($e->getMessage())]]);
        exit;
    }
}


include __DIR__ . '/header.php';
?>
<style>
  body {
    background: linear-gradient(45deg,#43cea2,#185a9d);
    min-height: 100vh;
  }
  #video {
    width: 100%;
    height: 300px;
    background: #000;
    border-radius: 10px;
    object-fit: cover;
    aspect-ratio: 4/3;
  }
</style>
<main style="max-width:700px;margin:2em auto;padding:2em;border-radius:10px;box-shadow:0 2px 8px #ccc;">
  <h2>商品引換</h2>

  <div id="errorStackArea" style="position:fixed;left:0;right:0;bottom:0;z-index:9999;pointer-events:none;display:flex;flex-direction:column-reverse;align-items:center;gap:8px;padding:16px 0;"></div>

  <div style="margin-bottom:2em;">
    <!-- <h3>QRコード読み取り</h3> -->
    <div id="qr-container" style="background:#fafaff;border-radius:12px;padding:1em 1em 0.5em 1em;box-shadow:0 2px 8px #eee;">
      <video id="video" style="width:100%;height:300px;background:#000;border-radius:10px;"></video>
      <div style="display:flex;gap:10px;margin:1em 0;align-items:center;">
        <button id="startBtn">📷 スキャン開始</button>
        <button id="stopBtn" disabled>⏹️ 停止</button>
        <select id="cameraSelect" style="padding:0.3em 0.7em;border-radius:6px;border:1px solid #ccc;min-width:120px;"></select>
      </div>
      <div id="status" style="margin-bottom:0.5em;color:#555;font-size:0.95em;">カメラの準備ができました</div>
      <div id="result" style="background:#f8f8ff;border-radius:8px;padding:0.7em 1em;margin-bottom:1em;border:1px solid #eee;">
        <b>読み取り結果:</b> <span id="resultText">QRコードをカメラに向けてください</span>
      </div>
    </div>
  </div>

  <form id="exchangeForm" method="post" style="margin-bottom:2em;" onsubmit="return false;">
    <h3>追加済みチケット一覧</h3>
    <table id="exchangeListTable" border="1" cellpadding="8" style="width:100%;margin-bottom:1em;text-align:center;">
      <thead>
        <tr style="background:#f0f0f0;"><th>チケットID</th><th>クラス</th><th>メニュー</th><th>フレーバー</th><th>金額</th><th>削除</th></tr>
      </thead>
      <tbody></tbody>
    </table>
    <div style="margin-bottom:1em;text-align:right;">
      <b>合計金額:</b> <span id="totalPrice">0 円</span>
    </div>
    <button id="bulkExchangeBtn" type="button" style="width:100%;padding:1em;font-size:1.1em;background:linear-gradient(45deg,#43cea2,#185a9d);color:#fff;border:none;border-radius:8px;">確認画面を表示</button>
  </form>

  <div style="font-size:0.9em;color:#666;">※QRコードは <b>year,hr,menu,flavor,ticketid</b> の形式でコンマ区切りです。<br>※未販売・既に引換済みのチケットは引換できません。</div>
</main>
<!-- ZXingライブラリ -->
<script src="https://unpkg.com/@zxing/library@latest"></script>
<div id="confirmModal" style="display:none;position:fixed;z-index:2000;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.55);align-items:center;justify-content:center;">
  <div style="background:#fff;padding:2.5em 2em 2em 2em;border-radius:16px;box-shadow:0 4px 24px #333;max-width:420px;width:90vw;text-align:center;position:relative;">
    <h2 style="margin-top:0;">引換確認</h2>
    <div id="confirmListArea" style="max-height:220px;overflow-y:auto;margin-bottom:1em;"></div>
    <div style="font-size:1.1em;margin-bottom:1.5em;">合計 <span id="confirmTotal"></span> 円</div>
    <button id="confirmOkBtn" style="padding:0.7em 2.5em;margin-right:1em;background:#1976d2;color:#fff;border:none;border-radius:6px;font-size:1.1em;">了解</button>
    <button id="confirmCancelBtn" style="padding:0.7em 2.5em;background:#eee;color:#333;border:none;border-radius:6px;font-size:1.1em;">取り消し</button>
  </div>
</div>
<script>

let codeReader, devices = [], currentDeviceIndex = 0;
const video = document.getElementById('video');
const startBtn = document.getElementById('startBtn');
const stopBtn = document.getElementById('stopBtn');
const cameraSelect = document.getElementById('cameraSelect');
const status = document.getElementById('status');
const resultText = document.getElementById('resultText');
const errorStackArea = document.getElementById('errorStackArea');
const exchangeListTable = document.getElementById('exchangeListTable').getElementsByTagName('tbody')[0];
const totalPriceSpan = document.getElementById('totalPrice');
const bulkExchangeBtn = document.getElementById('bulkExchangeBtn');

let ticketList = [];

function showStatus(msg, type) {
  status.textContent = msg;
  status.style.color = type === 'error' ? '#c00' : (type === 'scanning' ? '#f57c00' : '#2e7d32');
}
function showError(msg) {
  const stack = errorStackArea;
  const div = document.createElement('div');
  div.textContent = msg;
  div.style.cssText = 'min-width:220px;max-width:90vw;padding:1em 2em;margin:0;border-radius:8px;background:#ffeaea;color:#c00;border:1px solid #fbb;box-shadow:0 2px 8px #ccc;font-size:1.1em;pointer-events:auto;opacity:0.97;transition:opacity 0.3s';
  stack.prepend(div);
  setTimeout(() => {
    div.style.opacity = '0';
    setTimeout(() => { if (div.parentNode) div.parentNode.removeChild(div); }, 400);
  }, 3000);
}
function updateExchangeTable() {
  exchangeListTable.innerHTML = '';
  let total = 0;
  ticketList.forEach((t, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${t.ticketid}</td><td>${t.hr}</td><td>${t.menu}</td><td>${t.flavor}</td><td>${t.price} 円</td><td><button data-idx="${idx}" style="color:#c00;">削除</button></td>`;
    exchangeListTable.appendChild(tr);
    total += Number(t.price);
  });
  totalPriceSpan.textContent = total + ' 円';
  // 削除ボタン
  exchangeListTable.querySelectorAll('button[data-idx]').forEach(btn => {
    btn.onclick = e => {
      ticketList.splice(Number(btn.dataset.idx), 1);
      updateExchangeTable();
    };
  });
}
function playBeep() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.value = 1200;
    gain.gain.value = 0.15;
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.08);
    osc.onended = () => ctx.close();
  } catch(e) {}
}
function playDoubleBeep() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc1 = ctx.createOscillator();
    const gain1 = ctx.createGain();
    osc1.type = 'sine';
    osc1.frequency.value = 1500;
    gain1.gain.value = 0.15;
    osc1.connect(gain1);
    gain1.connect(ctx.destination);
    osc1.start();
    osc1.stop(ctx.currentTime + 0.03);
    const osc2 = ctx.createOscillator();
    const gain2 = ctx.createGain();
    osc2.type = 'sine';
    osc2.frequency.value = 1500;
    gain2.gain.value = 0.15;
    osc2.connect(gain2);
    gain2.connect(ctx.destination);
    osc2.start(ctx.currentTime + 0.05);
    osc2.stop(ctx.currentTime + 0.08);
    osc2.onended = () => ctx.close();
  } catch(e) {}
}
function playDoubleBeepError() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc1 = ctx.createOscillator();
    const gain1 = ctx.createGain();
    osc1.type = 'sine';
    osc1.frequency.value = 800;
    gain1.gain.value = 0.15;
    osc1.connect(gain1);
    gain1.connect(ctx.destination);
    osc1.start();
    osc1.stop(ctx.currentTime + 0.03);
    const osc2 = ctx.createOscillator();
    const gain2 = ctx.createGain();
    osc2.type = 'sine';
    osc2.frequency.value = 800;
    gain2.gain.value = 0.15;
    osc2.connect(gain2);
    gain2.connect(ctx.destination);
    osc2.start(ctx.currentTime + 0.05);
    osc2.stop(ctx.currentTime + 0.08);
    osc2.onended = () => ctx.close();
  } catch(e) {}
}
async function addTicketFromQR(qrtext) {
  // year,hr,menu,flavor,ticketid 形式
  const parts = qrtext.split(',');
  if (parts.length !== 5) return showError('QRコード形式が不正です');
  const ticketid = parts[4];
  const hr = parts[1];
  const menu = parts[2];
  const flavor = parts[3];
  // 既に追加済みなら読み取り結果枠に表示
  if (ticketList.some(t => t.ticketid == ticketid && t.hr == hr && t.menu == menu && t.flavor == flavor)) {
    showError('このチケットは既に一覧に追加されています');
    playDoubleBeep();
    video.style.border = '2px solid red';
    setTimeout(() => {
      video.style.boxShadow = '';
      video.style.border = '';
      resultText.textContent = 'QRコードをカメラに向けてください';
    }, 1200);
    return;
  }
  // サーバーでバリデーション＆情報取得
  const form = new FormData();
  form.append('ticketid', ticketid);
  form.append('hr', hr);
  form.append('menu', menu);
  form.append('flavor', flavor);
  const res = await fetch('?api=exchange_ticketinfo', { method: 'POST', body: form });
  const data = await res.json();
  if (data.error) {
    playDoubleBeepError();
    showError(data.error);
    return;
  }
  playBeep();
  ticketList.push(data);
  updateExchangeTable();
}

// モーダル要素取得
const confirmModal = document.getElementById('confirmModal');
const confirmListArea = document.getElementById('confirmListArea');
const confirmTotal = document.getElementById('confirmTotal');
const confirmOkBtn = document.getElementById('confirmOkBtn');
const confirmCancelBtn = document.getElementById('confirmCancelBtn');

bulkExchangeBtn.onclick = function() {
  if (ticketList.length === 0) return showError('チケットが追加されていません');
  // モーダルに内容をセット
  let html = '<table style="width:100%;border-collapse:collapse;font-size:1em;">';
  html += '<tr style="background:#f0f0f0;"><th>チケットID</th><th>クラス</th><th>メニュー</th><th>フレーバー</th><th>金額</th></tr>';
  let total = 0;
  ticketList.forEach(t => {
    html += `<tr><td>${t.ticketid}</td><td>${t.hr}</td><td>${t.menu}</td><td>${t.flavor}</td><td>${t.price}円</td></tr>`;
    total += Number(t.price);
  });
  html += '</table>';
  confirmListArea.innerHTML = html;
  confirmTotal.textContent = total;
  confirmModal.style.display = 'flex';
};

function cancelHandler() {
  confirmModal.style.display = 'none';
}
confirmCancelBtn.onclick = cancelHandler;

confirmOkBtn.onclick = async function() {
  confirmOkBtn.disabled = true;
  // 実際の引換処理
  const form = new FormData();
  ticketList.forEach(t => {
    form.append('ticketids[]', t.ticketid);
    form.append('flavors[]', t.flavor);
    form.append('hrs[]', t.hr);
    form.append('menus[]', t.menu);
  });
  const res = await fetch('?api=bulk_exchange', { method: 'POST', body: form });
  const results = await res.json();
  let ok = 0, ng = 0, ngmsg = '';
  results.forEach(r => {
    if (r.success) ok++;
    if (r.error) { ng++; ngmsg += `ID:${r.ticketid} ${r.error}\n`; }
  });
  if (ok && ng === 0) {
    // 成功時はモーダル内容を上書き
    confirmListArea.innerHTML = `<div style='color:#1976d2;font-size:1.2em;padding:2em 0;'>${ok}件の引換処理が完了しました。</div>`;
    confirmTotal.textContent = '';
    confirmOkBtn.style.display = 'none';
    confirmCancelBtn.textContent = '閉じる';
    confirmCancelBtn.onclick = function() {
      confirmModal.style.display = 'none';
      confirmOkBtn.style.display = '';
      confirmCancelBtn.textContent = '取り消し';
      confirmCancelBtn.onclick = cancelHandler;
    };
  } else if (ng) {
    showError(ngmsg);
    confirmModal.style.display = 'none';
  }
  ticketList = [];
  updateExchangeTable();
  confirmOkBtn.disabled = false;
};
async function initCamera() {
  if (!window.ZXing) {
    showStatus('ZXingライブラリの読み込みに失敗しました', 'error');
    return;
  }
  codeReader = new ZXing.BrowserQRCodeReader();
  devices = await codeReader.listVideoInputDevices();
  cameraSelect.innerHTML = '';
  devices.forEach((dev, idx) => {
    const opt = document.createElement('option');
    opt.value = idx;
    opt.textContent = dev.label || `カメラ${idx+1}`;
    cameraSelect.appendChild(opt);
  });
  currentDeviceIndex = 0;
  cameraSelect.selectedIndex = 0;
  if (devices.length === 0) {
    showStatus('カメラが見つかりません', 'error');
    startBtn.disabled = true;
    cameraSelect.disabled = true;
    return;
  }
  cameraSelect.disabled = false;
  showStatus('カメラの準備ができました', 'ready');
}
setTimeout(() => { startBtn.click(); }, 100);
startBtn.addEventListener('click', async () => {
  if (!codeReader) await initCamera();
  startBtn.disabled = true;
  stopBtn.disabled = false;
  showStatus('スキャン中...', 'scanning');
  codeReader.decodeFromVideoDevice(devices[currentDeviceIndex]?.deviceId, video, async (result, error) => {
    if (result) {
      resultText.textContent = result.getText();
      await addTicketFromQR(result.getText());
      showStatus('QRコードを読み取りました', 'ready');
      setTimeout(() => { resultText.textContent = 'QRコードをカメラに向けてください'; }, 2000);
    }
    if (error && error.name !== 'NotFoundException') {
      showStatus('スキャン中...', 'scanning');
    }
  });
});
stopBtn.addEventListener('click', () => {
  if (codeReader) codeReader.reset();
  startBtn.disabled = false;
  stopBtn.disabled = true;
  showStatus('スキャンを停止しました', 'ready');
});

cameraSelect.addEventListener('change', () => {
  currentDeviceIndex = Number(cameraSelect.value);
  if (codeReader) {
    codeReader.reset();
    startBtn.disabled = true;
    stopBtn.disabled = false;
    showStatus('スキャン中...', 'scanning');
    codeReader.decodeFromVideoDevice(devices[currentDeviceIndex]?.deviceId, video, async (result, error) => {
      if (result) {
        resultText.textContent = result.getText();
        await addTicketFromQR(result.getText());
        showStatus('QRコードを読み取りました', 'ready');
        setTimeout(() => { resultText.textContent = 'QRコードをカメラに向けてください'; }, 2000);
      }
      if (error && error.name !== 'NotFoundException') {
        showStatus('スキャン中...', 'scanning');
      }
    });
  }
});
window.addEventListener('DOMContentLoaded', initCamera);
</script>
