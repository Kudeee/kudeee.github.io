import { subscriptions } from "../data/subscription-data.js";

let isYearly = false;

// ─── Toggle ───────────────────────────────────────────────────────────────────

export function togglePricing(yearly) {
  isYearly = yearly;
  initSubscriptionSelection(".selection-cards");

  const userCurrentPlan    = window.userCurrentPlan    || "PREMIUM PLAN";
  const userCurrentBilling = window.userCurrentBilling || "monthly";
  initSubscriptionCards(".card-container", userCurrentPlan, userCurrentBilling);

  updateSignUpReceipt();
}

function getPriceDisplay(sub) {
  if (isYearly) {
    return `<span class="price">₱${sub.yearlyPrice}</span><span class="month">/year</span>`;
  }
  return `<span class="price">₱${sub.monthlyPrice}</span><span class="month">/month</span>`;
}

function getSavingsBadge(sub) {
  if (isYearly) {
    const savings = sub.monthlyPrice * 12 - sub.yearlyPrice;
    return `<div class="savings-badge">Save ₱${savings}</div>`;
  }
  return "";
}

// ─── Pricing toggle HTML ──────────────────────────────────────────────────────

export function renderPricingToggle() {
  return `
    <div class="pricing-toggle">
      <span class="toggle-label ${!isYearly ? "active" : ""}">Monthly</span>
      <label class="toggle-switch">
        <input type="checkbox" id="pricingToggle" ${isYearly ? "checked" : ""}
               onchange="window.togglePricing(this.checked)" />
        <span class="toggle-slider"></span>
      </label>
      <span class="toggle-label ${isYearly ? "active" : ""}">Yearly</span>
      <span class="toggle-discount">Save 16%</span>
    </div>
  `;
}

// ─── Static cards (My Membership page) ───────────────────────────────────────

export function renderStaticCards() {
  return subscriptions
    .map(
      (sub) => `
      <div class="sub-card">
        <div class="sub-header">
          <h3>${sub.plan}</h3>
          <span class="badge">${sub.members || ""} ${sub.members ? "Members" : ""}</span>
          ${getSavingsBadge(sub)}
        </div>
        <div class="sub-price">${getPriceDisplay(sub)}</div>
        <ul class="sub-benefits">
          ${sub.benefits.map((b) => `<li>✓ ${b}</li>`).join("")}
        </ul>
      </div>`
    )
    .join("");
}

// ─── Subscription management cards (My Membership page) ──────────────────────

export function renderSubscriptionCards(currentPlan = null, currentBilling = "monthly") {
  let html = `
    <div class="pricing-wrapper">
      ${renderPricingToggle()}
      <div class="sub-container">
  `;

  subscriptions.forEach((subscription) => {
    const isCurrentPlan    = currentPlan === subscription.plan;
    const isCurrentBilling = (isYearly && currentBilling === "yearly") ||
                             (!isYearly && currentBilling === "monthly");

    let buttonClass, buttonText, buttonLink;

    if (isCurrentPlan && isCurrentBilling) {
      buttonClass = "current-plan";
      buttonText  = "Current Plan";
      buttonLink  = "";
    } else if (isCurrentPlan && !isCurrentBilling) {
      buttonClass = "change-plan";
      buttonText  = isYearly ? "Upgrade to Yearly" : "Switch to Monthly";
      const price      = isYearly ? subscription.yearlyPrice : subscription.monthlyPrice;
      const planParam  = encodeURIComponent(subscription.plan);
      const billingParam = isYearly ? "yearly" : "monthly";
      buttonLink = `href="payment.php?type=billing-change&plan=${planParam}&price=${price}&billing=${billingParam}"`;
    } else {
      const currentPrice = isYearly ? subscription.yearlyPrice : subscription.monthlyPrice;
      const isUpgrade    = currentPrice > getCurrentPlanPrice(currentPlan, currentBilling);
      buttonClass        = "change-plan";
      buttonText         = isUpgrade ? "Upgrade Plan" : "Downgrade Plan";
      const planParam    = encodeURIComponent(subscription.plan);
      const billingParam = isYearly ? "yearly" : "monthly";
      buttonLink = `href="payment.php?type=change&plan=${planParam}&price=${currentPrice}&billing=${billingParam}"`;
    }

    html += `
      <div class="sub-card">
        <div class="sub-header">
          <h3>${subscription.plan}</h3>
          ${getSavingsBadge(subscription)}
        </div>
        <div class="sub-price">${getPriceDisplay(subscription)}</div>
        <ul class="sub-benefits">
          ${subscription.benefits.map((b) => `<li>${b}</li>`).join("")}
        </ul>
        <div class="buttons">
          <a ${buttonLink} class="${buttonClass}">${buttonText}</a>
        </div>
      </div>
    `;
  });

  html += "</div></div>";
  return html;
}

// ─── Selection cards (Sign-up page) ──────────────────────────────────────────
// Uses name="membership_plan" so the selected value POSTs to PHP correctly.

export function renderSelectionCards() {
  let html = `
    <div class="pricing-wrapper">
      ${renderPricingToggle()}
      <div class="sub-container">
  `;

  subscriptions.forEach((sub) => {
    const id           = sub.plan.toLowerCase().replace(/\s+/g, "-");
    const currentPrice = isYearly ? sub.yearlyPrice : sub.monthlyPrice;
    const billing      = isYearly ? "yearly" : "monthly";

    html += `
      <label class="sub-card-select">
        <!-- name="membership_plan" ensures PHP $_POST['membership_plan'] is set -->
        <input
          type="radio"
          name="membership_plan"
          id="${id}"
          value="${sub.plan}"
          data-price="${currentPrice}"
          data-billing="${billing}"
        />

        <div class="sub-header">
          <h3>${sub.plan}</h3>
          ${getSavingsBadge(sub)}
        </div>

        <div class="sub-price">${getPriceDisplay(sub)}</div>

        <ul class="sub-benefits">
          ${sub.benefits.map((b) => `<li>✓ ${b}</li>`).join("")}
        </ul>
      </label>
    `;
  });

  html += "</div></div>";
  return html;
}

// ─── Init helpers ─────────────────────────────────────────────────────────────

export function initSubscriptionCards(containerSelector, currentPlan = null, currentBilling = "monthly") {
  const container = document.querySelector(containerSelector);
  if (container) container.innerHTML = renderSubscriptionCards(currentPlan, currentBilling);
}

export function initSubscriptionSelection(containerSelector) {
  const container = document.querySelector(containerSelector);
  if (container) container.innerHTML = renderSelectionCards();
}

function getCurrentPlanPrice(planName, billing = "monthly") {
  const plan = subscriptions.find((sub) => sub.plan === planName);
  if (!plan) return 0;
  return billing === "yearly" ? plan.yearlyPrice : plan.monthlyPrice;
}

function updateSignUpReceipt() {
  const receiptRow = document.querySelector(".reciept-row");
  const totalPrice = document.querySelector(".total-price");

  if (!receiptRow || !totalPrice) return;

  const selectedInput = document.querySelector('input[name="membership_plan"]:checked');
  if (!selectedInput) return;

  const planName = selectedInput.value;
  const plan     = subscriptions.find((sub) => sub.plan === planName);
  if (!plan) return;

  const price   = isYearly ? plan.yearlyPrice : plan.monthlyPrice;
  const period  = isYearly ? "yearly" : "monthly";

  receiptRow.querySelector(".price").textContent = `₱${price}`;
  receiptRow.querySelector("small").textContent  = `Billed ${period}`;
  totalPrice.textContent                         = `₱${price}`;
}

// ─── Global exposure ──────────────────────────────────────────────────────────

window.togglePricing = togglePricing;

window.getSelectedPlanPrice = function () {
  const selectedInput = document.querySelector('input[name="membership_plan"]:checked');
  if (!selectedInput) return null;
  return { price: selectedInput.dataset.price, billing: selectedInput.dataset.billing };
};

// ─── Initialise on load ───────────────────────────────────────────────────────

initSubscriptionSelection(".selection-cards");

window.userCurrentPlan    = "PREMIUM PLAN";
window.userCurrentBilling = "monthly";

initSubscriptionCards(".card-container", window.userCurrentPlan, window.userCurrentBilling);