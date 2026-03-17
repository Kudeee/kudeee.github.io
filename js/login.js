import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";
import { render } from "./renderer.js";

render('#pop-up', 'warning', renderPopUP);
window.closePopUp = closePopUp;

document.getElementById('loginForm').addEventListener("submit", handleLogin);

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

    const response = await fetch("api/auth/login.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    hideLoading();

    if (result.success) {
      // Trainers (staff role + is_trainer flag) → trainer dashboard
      if (result.is_trainer) {
        window.location.href = "trainer-dashboard.php";
        return;
      }

      // Regular admins and staff → admin panel
      const adminRoles = ['admin', 'super_admin', 'staff'];
      if (adminRoles.includes(result.role)) {
        window.location.href = "admin-panel.php";
        return;
      }

      // Members → homepage
      window.location.href = "homepage.php";

    } else {
      showPopUP(result.message || "Invalid email or password.");
    }
  } catch (error) {
    hideLoading();
    showPopUP("Login failed. Please try again.");
  }
}