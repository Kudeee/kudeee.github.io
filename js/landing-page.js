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
window.toggleLandingPricing = toggleLandingPricing;

// ─── Pricing ──────────────────────────────────────────────────────────────────

let landingIsYearly = false;
let landingPlans    = [];

async function fetchLandingPlans() {
  if (landingPlans.length) return landingPlans;
  try {
    const res  = await fetch('api/admin/plans/list.php');
    const data = await res.json();
    if (data.success && Array.isArray(data.plans)) {
      landingPlans = data.plans
        .filter(p => p.is_active)
        .map(p => ({
          plan:         p.plan,
          monthlyPrice: parseFloat(p.monthly_price),
          yearlyPrice:  parseFloat(p.yearly_price),
          color:        p.color || '#ff6b35',
          benefits:     Array.isArray(p.benefits) ? p.benefits : [],
        }));
    }
  } catch (e) {
    console.warn('landing-page: could not fetch plans, using fallback.', e);
  }

  // If API failed or returned no plans, use hardcoded fallback
  if (!landingPlans.length) {
    landingPlans = [
      {
        plan: 'BASIC PLAN', monthlyPrice: 499, yearlyPrice: 5028,
        color: '#9e9e9e',
        benefits: ['Gym access', 'Basic equipment', 'Free Wifi', 'Locker rental available'],
      },
      {
        plan: 'PREMIUM PLAN', monthlyPrice: 899, yearlyPrice: 9067,
        color: '#ff6b35',
        benefits: ['All Basic Features', 'Locker Access', 'Group Classes', 'Nutritional guidance', '10% merchandise discount'],
      },
      {
        plan: 'VIP PLAN', monthlyPrice: 1499, yearlyPrice: 15189,
        color: '#f9a825',
        benefits: ['All Premium Features', 'Personal Trainer (2x/week)', 'Priority Booking', 'Free guest passes (2/month)', 'Massage therapy (1x/month)', '20% merchandise discount'],
      },
    ];
  }

  return landingPlans;
}

function renderLandingPricingHTML() {
  const toggle = `
    <div class="pricing-toggle">
      <span class="toggle-label ${!landingIsYearly ? 'active' : ''}">Monthly</span>
      <label class="toggle-switch">
        <input type="checkbox" id="landingPricingToggle"
               ${landingIsYearly ? 'checked' : ''}
               onchange="window.toggleLandingPricing(this.checked)" />
        <span class="toggle-slider"></span>
      </label>
      <span class="toggle-label ${landingIsYearly ? 'active' : ''}">Yearly</span>
      <span class="toggle-discount">Save 16%</span>
    </div>
  `;

  const cards = landingPlans.map(p => {
    const price   = landingIsYearly ? p.yearlyPrice : p.monthlyPrice;
    const period  = landingIsYearly ? 'year' : 'month';
    const savings = Math.round(p.monthlyPrice * 12 - p.yearlyPrice);
    const savingsBadge = (landingIsYearly && savings > 0)
      ? `<div class="savings-info" style="color:#2e7d32;font-weight:600;margin:10px 0;">Save ₱${savings.toLocaleString('en-PH')}</div>`
      : '';

    return `
      <div class="pricing-card" style="border-top: 4px solid ${p.color};">
        <h3 style="color:${p.color};">${p.plan}</h3>
        <div class="price-display">
          <span class="price">₱${price.toLocaleString('en-PH')}</span><span class="month">/${period}</span>
        </div>
        ${savingsBadge}
        <ul class="features">
          ${p.benefits.map(b => `<li>${b}</li>`).join('')}
        </ul>
        <div class="button-container">
          <a href="sign-up-page.php">Get Started</a>
        </div>
      </div>
    `;
  }).join('');

  return `${toggle}<div class="pricing-cards">${cards}</div>`;
}

async function initLandingPricing() {
  await fetchLandingPlans();
  const container = document.getElementById('landing-pricing-container');
  if (container) container.innerHTML = renderLandingPricingHTML();
}

async function toggleLandingPricing(isYearly) {
  landingIsYearly = isYearly;
  const container = document.getElementById('landing-pricing-container');
  if (container) container.innerHTML = renderLandingPricingHTML();
}

// Initialise on load
initLandingPricing();

// ─── Contact form ─────────────────────────────────────────────────────────────

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

// ─── Nav ──────────────────────────────────────────────────────────────────────

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