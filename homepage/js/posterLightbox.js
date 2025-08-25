document.addEventListener("DOMContentLoaded", () => {
  const images = document.querySelectorAll(".poster-img");
  const lightbox = document.getElementById("lightbox");
  const lightboxImg = document.getElementById("lightboxImg");
  const closeBtn = document.querySelector(".lightbox .close");

  // 画像クリックでライトボックスを開く
  images.forEach(img => {
    img.addEventListener("click", () => {
      lightbox.style.display = "flex";
      lightboxImg.src = img.src;
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
});