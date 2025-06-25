window.addEventListener("load", () => {
  const toggleBtn = document.getElementById("navToggle");
  const nav = document.getElementById("mainNav");

  if (toggleBtn && nav) {
    toggleBtn.addEventListener("click", () => {
      nav.classList.toggle("show");
    });
  }
});
