window.addEventListener("DOMContentLoaded", () => {
  const icon = document.getElementById("icon");
  let clickCount = 0;
  const threshold = 5; // 5��N���b�N�Ń��O�C���y�[�W�ւƂ�

  icon.addEventListener("click", () => {
    clickCount++;
    if (clickCount >= threshold) {
      window.location.href = "secret.html";
    }
  });
});
