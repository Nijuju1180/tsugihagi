<?php
// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');
// README.mdの内容を取得
$readme = file_exists(__DIR__ . '/README.md') ? file_get_contents(__DIR__ . '/README.md') : '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>文化祭チケット管理システム</title>
  <!-- OGP設定 -->
  <meta property="og:title" content="文化祭チケット管理システム">
  <meta property="og:type" content="website">
  <meta property="og:description" content="QRコードでチケット販売・引換・集計・管理をリアルタイムで行うWebシステム。ユーザー権限・集計グラフ・Ajax対応。">
  <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
  <meta property="og:image" content="https://bem130.com/tmp/sf2025/ticket/test1/ogp.png">
  <meta property="og:site_name" content="文化祭チケット管理システム">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="文化祭チケット管理システム">
  <meta name="twitter:description" content="QRコードでチケット販売・引換・集計・管理をリアルタイムで行うWebシステム。ユーザー権限・集計グラフ・Ajax対応。">
  <meta name="twitter:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/ogp.png">
  <?php include __DIR__ . '/header.php'; ?>
<style>
/* Markdownデザイン */
#readme-md {
  padding: 1.2em 1.5em;
  border-radius: 10px;
  box-shadow: 0 2px 8px #e0e0e0;
  max-width: 900px;
  margin: 1.2em auto;
  font-family: 'Segoe UI', 'Hiragino Sans', 'Meiryo', sans-serif;
  color: #222;
  line-height: 1.7;
}
#readme-md h1, #readme-md h2, #readme-md h3 {
  font-weight: bold;
  border-bottom: 1.5px solid #e0e0e0;
  padding-bottom: 0.2em;
  margin-top: 0.5em;
  margin-bottom: 0.5em;
}
#readme-md h1 { font-size: 2.1em; color: #4a69bb; }
#readme-md h2 { font-size: 1.5em; color: #5d6d7e; }
#readme-md h3 { font-size: 1.2em; color: #7b8fa1; }
#readme-md ul, #readme-md ol { margin-left: 2em; }
#readme-md li { margin-bottom: 0.4em; }
#readme-md code, #readme-md pre {
  background: #f4f4f4;
  color: #c7254e;
  border-radius: 4px;
  padding: 0.2em 0.4em;
  font-family: 'Fira Mono', 'Consolas', monospace;
}
#readme-md pre {
  padding: 1em;
  overflow-x: auto;
  margin: 1em 0;
}
#readme-md table {
  border-collapse: collapse;
  width: 100%;
  margin: 1.5em 0;
  background: #fafbfc;
}
#readme-md th, #readme-md td {
  border: 1px solid #d0d7de;
  padding: 0.6em 1em;
  text-align: left;
}
#readme-md th {
  background: #f0f4fa;
  color: #3b4a5a;
}
#readme-md blockquote {
  border-left: 4px solid #b2bec3;
  background: #f8f9fa;
  color: #636e72;
  margin: 1em 0;
  padding: 0.7em 1.2em;
  border-radius: 6px;
}
#readme-md a {
  color: #1976d2;
  text-decoration: underline;
}
</style>
<main id="readme-md"></main>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
// PHPからREADME.mdの内容をエスケープして渡す
const readmeText = <?php echo json_encode($readme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
document.getElementById('readme-md').innerHTML = marked.parse(readmeText);
</script>
