document.querySelectorAll("nav a").forEach(a => {
  if (a.href === location.href) a.classList.add("active");
});

const toTopBtn = document.getElementById("toTopBtn");

window.addEventListener("scroll", () => {
  toTopBtn.style.display = window.scrollY > 300 ? "block" : "none";
});

toTopBtn.addEventListener("click", () => {
  window.scrollTo({ top: 0, behavior: "smooth" });
});