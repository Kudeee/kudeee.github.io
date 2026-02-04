async function handleLogin(event) {
  event.preventDefault();

  const email = document.querySelector('input[type="email"]').value;
  const password = document.querySelector('input[type="password"]').value;

  showLoading("Logging in");

  try {
    await simulateLoading(2000);

    hideLoading();

    window.location.href = "../homepage.html";
  } catch (error) {
    hideLoading();
    alert("login failed");
  }
}
