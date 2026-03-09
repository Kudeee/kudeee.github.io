import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";
import { render } from "./renderer.js";

render('#pop-up', 'warning', renderPopUP);
window.closePopUp = closePopUp;

document.getElementById('submit').addEventListener("submit", handleLogin);

export async function handleLogin(event) {
  event.preventDefault();

  const email    = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;

  if (!email || !password) {
    showPopUP("Please enter your email and password.");
    return;
  }

  showLoading("Logging in...");

  try {
    const formData = new FormData(event.target);

    const response = await fetch("/api/auth/login.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    hideLoading();

    if (result.success) {
      // Redirect based on role returned by server
      if (result.role === "admin") {
        window.location.href = "admin-panel.html";
      } else {
        window.location.href = "homepage.html";
      }
    } else {
      showPopUP(result.message || "Invalid email or password.");
    }
  } catch (error) {
    hideLoading();
    showPopUP("Login failed. Please try again.");
  }
}