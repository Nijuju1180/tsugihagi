document.addEventListener("DOMContentLoaded", () => {
  const navToggle = document.getElementById("navToggleBtn");
  const navMenu = document.getElementById("navMenu");

  // メニュー開閉
  navToggle.addEventListener("click", (event) => {
    event.stopPropagation(); // 自身のクリックで閉じるのを防ぐ
    navMenu.classList.toggle("show");
  });

  // メニュー内のクリックでは閉じないようにする
  navMenu.addEventListener("click", (event) => {
    event.stopPropagation();
  });

  // メニュー外クリックで閉じる（モバイル表示時のみ）
  document.addEventListener("click", () => {
    const isMobile = window.innerWidth <= 768;
    if (isMobile && navMenu.classList.contains("show")) {
      navMenu.classList.remove("show");
    }
  });

  // Escキーでナビゲーションを閉じる
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && window.innerWidth <= 768) {
    navMenu.classList.remove("show");
  }
});

  // ウィンドウリサイズ時に閉じておく（安全対策）
  window.addEventListener("resize", () => {
    if (window.innerWidth > 768) {
      navMenu.classList.remove("show");
    }
  });
});
