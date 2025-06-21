document.querySelectorAll("nav a").forEach(a => {
  if (a.href === location.href) a.classList.add("active");
});