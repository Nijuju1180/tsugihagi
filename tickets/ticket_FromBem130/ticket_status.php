<?php
// チケット状態確認ページ
require_once __DIR__ . '/db_access.php';
require_once __DIR__ . '/price.php';

// --- Ajax: チケット情報取得API ---
if (isset($_GET['api']) && $_GET['api'] === 'ticketinfo' && isset($_POST['ticketid'])) {
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
        } else {
            $price = get_ticket_price($ticket['hr'], $ticket['menu'], $ticket['flavor']);
            // 状態判定
            $status = '販売前';
            if ($ticket['ticketsalesid']) {
                $status = '引換前';
            }
            if ($ticket['productexchangeid']) {
                $status = '引換済';
            }
            echo json_encode([
                'ticketid' => $ticket['ticketid'],
                'hr' => $ticket['hr'],
                'menu' => $ticket['menu'],
                'flavor' => $ticket['flavor'],
                'price' => $price,
                'ticketsalesid' => $ticket['ticketsalesid'],
                'ticketsalestime' => $ticket['ticketsalestime'],
                'productexchangeid' => $ticket['productexchangeid'],
                'productexchangetime' => $ticket['productexchangetime'],
                'status' => $status
            ]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'DBエラー: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
}

include __DIR__ . '/header.php';
?>
<main style="max-width:700px;margin:2em auto;padding:2em;border-radius:10px;box-shadow:0 2px 8px #ccc;">
  <h2>チケット状態確認</h2>
  <div id="errorStackArea" style="position:fixed;left:0;right:0;bottom:0;z-index:9999;pointer-events:none;display:flex;flex-direction:column-reverse;align-items:center;gap:8px;padding:16px 0;"></div>
  <div style="margin-bottom:2em;">
    <div id="qr-container" style="background:#fafaff;border-radius:12px;padding:1em 1em 0.5em 1em;box-shadow:0 2px 8px #eee;">
      <video id="video" style="width:100%;height:300px;background:#000;border-radius:10px;2px solid #00000000"></video>
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
  <div id="ticketInfoArea" style="display:none;background:#fff;border-radius:10px;padding:1.5em 1em 1em 1em;box-shadow:0 2px 8px #eee;margin-bottom:2em;">
    <div id="info_status_big" style="font-size:2.2em;font-weight:bold;text-align:center;margin-bottom:0.7em;"></div>
    <table style="width:100%;font-size:1.1em;text-align:center;margin-bottom:1.2em;">
      <thead>
        <tr>
          <th>チケットID</th>
          <th>クラス</th>
          <th>メニュー</th>
          <th>フレーバー</th>
          <th>金額</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td id="info_ticketid"></td>
          <td id="info_hr"></td>
          <td id="info_menu"></td>
          <td id="info_flavor"></td>
          <td id="info_price"></td>
        </tr>
      </tbody>
    </table>
    <table style="width:100%;font-size:1.1em;text-align:center;">
      <thead>
        <tr>
          <th>販売担当</th>
          <th>販売日時</th>
          <th>引換担当</th>
          <th>引換日時</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td id="info_salesid"></td>
          <td id="info_salestime"></td>
          <td id="info_exchangeid"></td>
          <td id="info_exchangetime"></td>
        </tr>
      </tbody>
    </table>
  </div>
  <div style="font-size:0.9em;color:#666;">※QRコードは <b>year,hr,menu,flavor,ticketid</b> の形式でコンマ区切りです。</div>
</main>
<script src="https://unpkg.com/@zxing/library@latest"></script>
<script>
let codeReader, devices = [], currentDeviceIndex = 0;
const video = document.getElementById('video');
const startBtn = document.getElementById('startBtn');
const stopBtn = document.getElementById('stopBtn');
const cameraSelect = document.getElementById('cameraSelect');
const status = document.getElementById('status');
const resultText = document.getElementById('resultText');

function showStatus(msg, type) {
  status.textContent = msg;
  status.style.color = type === 'error' ? '#c00' : (type === 'scanning' ? '#f57c00' : '#2e7d32');
}
function showError(msg) {
  const stack = document.getElementById('errorStackArea');
  const div = document.createElement('div');
  div.textContent = msg;
  div.style.cssText = 'min-width:220px;max-width:90vw;padding:1em 2em;margin:0;border-radius:8px;background:#ffeaea;color:#c00;border:1px solid #fbb;box-shadow:0 2px 8px #ccc;font-size:1.1em;pointer-events:auto;opacity:0.97;transition:opacity 0.3s';
  stack.prepend(div);
  setTimeout(() => {
    div.style.opacity = '0';
    setTimeout(() => { if (div.parentNode) div.parentNode.removeChild(div); }, 400);
  }, 3000);
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
async function fetchTicketInfoFromQR(qrtext) {
  // year,hr,menu,flavor,ticketid 形式
  const parts = qrtext.split(',');
  if (parts.length !== 5) return showError('QRコード形式が不正です');
  const ticketid = parts[4];
  const hr = parts[1];
  const menu = parts[2];
  const flavor = parts[3];
  const form = new FormData();
  form.append('ticketid', ticketid);
  form.append('hr', hr);
  form.append('menu', menu);
  form.append('flavor', flavor);
  const res = await fetch('?api=ticketinfo', { method: 'POST', body: form });
  const data = await res.json();
  if (data.error) {
    playDoubleBeepError();
    showError(data.error);
    document.getElementById('ticketInfoArea').style.display = 'none';
    return;
  } else {
    playBeep();
    document.getElementById('ticketInfoArea').style.display = '';
    document.getElementById('info_status_big').textContent = data.status;
    document.getElementById('info_ticketid').textContent = data.ticketid;
    document.getElementById('info_hr').textContent = data.hr;
    document.getElementById('info_menu').textContent = data.menu;
    document.getElementById('info_flavor').textContent = data.flavor;
    document.getElementById('info_price').textContent = data.price + ' 円';
    document.getElementById('info_salesid').textContent = data.ticketsalesid || '-';
    document.getElementById('info_salestime').textContent = data.ticketsalestime || '-';
    document.getElementById('info_exchangeid').textContent = data.productexchangeid || '-';
    document.getElementById('info_exchangetime').textContent = data.productexchangetime || '-';
  }
}
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
setTimeout(() => {
    startBtn.click();
}, 100);
startBtn.addEventListener('click', async () => {
  if (!codeReader) await initCamera();
  startBtn.disabled = true;
  stopBtn.disabled = false;
  showStatus('スキャン中...', 'scanning');
  codeReader.decodeFromVideoDevice(devices[currentDeviceIndex]?.deviceId, video, async (result, error) => {
    if (result) {
      resultText.textContent = result.getText();
      await fetchTicketInfoFromQR(result.getText());
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
        await fetchTicketInfoFromQR(result.getText());
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
