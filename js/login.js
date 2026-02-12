async function handleLogin(event) {
  event.preventDefault();

  const email = document.querySelector('input[type="email"]').value;
  const password = document.querySelector('input[type="password"]').value;

  if (!email || !password) {
    showPopUP("invalid email or password");
    return;
  }

  showLoading("Logging in");

  try {
    await simulateLoading(2000);

    hideLoading();

    if (email === "admin@admin" && password === "admin") {
      window.location.href = "../admin-panel.html";
    } else {
      window.location.href = "../homepage.html";
    }
  } catch (error) {
    hideLoading();
    showPopUP("login failed");
  }
}
