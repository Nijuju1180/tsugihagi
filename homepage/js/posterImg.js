  const images = document.querySelectorAll(".poster-img");
  const lightbox = document.getElementById("lightbox");
  const lightboxImg = document.getElementById("lightboxImg");
  const closeBtn = document.querySelector(".lightbox .close");

  images.forEach(img => {
    img.addEventListener("click", () => {
      lightbox.style.display = "flex"; // フレックス表示に
      lightboxImg.src = img.src;       // クリックした画像を表示
    });
  });

  // 閉じるボタン
  closeBtn.addEventListener("click", () => {
    lightbox.style.display = "none";
  });

  // 背景クリックでも閉じる
  lightbox.addEventListener("click", (e) => {
    if (e.target === lightbox) {
      lightbox.style.display = "none";
    }
  });