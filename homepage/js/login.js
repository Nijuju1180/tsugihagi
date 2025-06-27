document.getElementById("loginForm").addEventListener("submit", function(e) {
  e.preventDefault(); // フォーム送信を止める

  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;

  // 仮の認証（ここでDBやAPIと連携する）
  if(username === "tsugihagi" && password === "tsugihagi06") {
    document.getElementById("message").style.color = "green";
    document.getElementById("message").textContent = "ログイン成功！";
    window.location.href = "admin.html";
  } else {
    document.getElementById("message").style.color = "red";
    document.getElementById("message").textContent = "ユーザー名またはパスワードが間違っています";
  }
});
