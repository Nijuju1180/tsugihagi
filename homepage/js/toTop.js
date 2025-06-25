window.addEventListener("load",()=>{

  const toTopBtn = document.createElement("button");
  toTopBtn.textContent = "↑";
  toTopBtn.title = "ページの先頭へ";
  toTopBtn.id = "toTopBtn";
  document.body.appendChild(toTopBtn);

  document.querySelectorAll("nav a").forEach(a => {
    console.log("aaa");
    console.log(a.href, location.href);
    if (a.href === location.href) a.classList.add("active");
  });

  window.addEventListener("scroll", () => {
    toTopBtn.style.display = document.documentElement.scrollTop > 300 ? "block" : "none";
  });

  toTopBtn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

})