document.addEventListener("DOMContentLoaded", () => {
  const navToggle = document.getElementById("navToggleBtn"); // ハンバーガーボタン
  const navMenu = document.getElementById("navMenu");       // ナビメニュー

  // メニュー開閉
  navToggle.addEventListener("click", (event) => {
    event.stopPropagation(); // 自身のクリックで閉じるのを防ぐ

    // メニューの表示切替
    navMenu.classList.toggle("show");

    // ボタンを × に切替＆画面左上固定
    navToggle.classList.toggle("active");
  });

  // メニュー内クリックでは閉じない
  navMenu.addEventListener("click", (event) => {
    event.stopPropagation();
  });

  // メニュー外クリックで閉じる（モバイルのみ）
  document.addEventListener("click", () => {
    const isMobile = window.innerWidth <= 768;
    if (isMobile && navMenu.classList.contains("show")) {
      navMenu.classList.remove("show");
      navToggle.classList.remove("active"); // ボタンも元に戻す
    }
  });

  // Escキーで閉じる
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && window.innerWidth <= 768) {
      navMenu.classList.remove("show");
      navToggle.classList.remove("active"); // ボタンも元に戻す
    }
  });

  // ウィンドウリサイズ時に閉じる（安全対策）
  window.addEventListener("resize", () => {
    if (window.innerWidth > 768) {
      navMenu.classList.remove("show");
      navToggle.classList.remove("active"); // ボタンも元に戻す
    }
  });
});
