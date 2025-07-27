
<?php
date_default_timezone_set('Asia/Tokyo');

// hr, menu, flavor から値段を返す関数
function get_ticket_price($hr, $menu, $flavor) {
    // 例: メニュー・フレーバー・クラスで価格を分岐
    // 必要に応じてロジックを編集してください
    if ($menu === 'yakisoba') return 300;
    if ($menu === 'takoyaki') return 350;
    if ($menu === 'crepe') return 400;
    if ($menu === 'curry') return 350;
    if ($menu === 'juice') return 150;
    return -100000; // デフォルト
}