import { render } from "./renderer.js";
import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";

render("#pop-up", "done", renderPopUP);

document
  .querySelector(".contact-form")
  .addEventListener("submit", handleInqSubmit);

window.toggleMenu = toggleMenu;
window.closeMenu = closeMenu;
window.toggleFAQ = toggleFAQ;
window.closePopUp = closePopUp;

async function handleInqSubmit(event) {
  event.preventDefault();

  showLoading("Sending...");

  try {
    await simulateLoading(2000);

    hideLoading();
    event.target.reset();
    showPopUP("Message has been sent.");
  } catch (error) {
    hideLoading();
    showPopUP("Please try again.");
  }
}

function toggleMenu() {
  const navLinks = document.getElementById("navLinks");
  navLinks.classList.toggle("active");
}

function closeMenu() {
  const navLinks = document.getElementById("navLinks");
  navLinks.classList.remove("active");
}

function toggleFAQ(element) {
  const answer = element.querySelector(".faq-answer");
  const symbol = element.querySelector(".faq-question span:last-child");

  answer.classList.toggle("active");
  symbol.textContent = answer.classList.contains("active") ? "−" : "+";
}

document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  });
});
