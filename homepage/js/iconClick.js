window.addEventListener("DOMContentLoaded", () => {
  const icon = document.getElementById("icon");
  let clickCount = 0;
  const threshold = 5; // 5回クリックでログインページへとぶ

  icon.addEventListener("click", () => {
    clickCount++;
    if (clickCount >= threshold) {
      window.location.href = "login.php";
    }
  });
});
