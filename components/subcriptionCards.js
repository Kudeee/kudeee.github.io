/**
 * subcriptionCards.js
 * Fetches live plan data from api/admin/plans/list.php so that
 * any changes made in the Admin → Plans panel are immediately
 * reflected on the member-facing pages (sign-up, my-membership, payment).
 */

let isYearly     = false;
let plansCache   = [];   // populated once on first fetch

// ─── Data layer ───────────────────────────────────────────────────────────────

async function fetchPlans() {
  if (plansCache.length) return plansCache;
  try {
    const res  = await fetch('api/admin/plans/list.php');
    const data = await res.json();
    if (data.success && Array.isArray(data.plans)) {
      // Normalise to the shape the rest of the module expects
      plansCache = data.plans.map(p => ({
        plan:         p.plan,
        monthlyPrice: parseFloat(p.monthly_price),
        yearlyPrice:  parseFloat(p.yearly_price),
        color:        p.color        || '#ff6b35',
        benefits:     Array.isArray(p.benefits) ? p.benefits : [],
        maxClasses:   p.max_classes  ?? -1,
        ptSessions:   p.pt_sessions  ?? 0,
        guestPasses:  p.guest_passes ?? 0,
        isActive:     !!p.is_active,
      }));
    }
  } catch (e) {
    console.warn('subcriptionCards: could not fetch plans, falling back to defaults.', e);
    // Hardcoded fallback so pages still render if the API is unreachable
    plansCache = [
      {
        plan: 'BASIC PLAN', monthlyPrice: 499, yearlyPrice: 5028,
        color: '#9e9e9e', isActive: true,
        benefits: ['Gym access (6AM–10PM)', 'Locker room access', '2 group classes/week'],
        maxClasses: 2, ptSessions: 0, guestPasses: 0,
      },
      {
        plan: 'PREMIUM PLAN', monthlyPrice: 899, yearlyPrice: 9067,
        color: '#ff6b35', isActive: true,
        benefits: ['24/7 gym access', 'Unlimited group classes', '1 PT session/month'],
        maxClasses: -1, ptSessions: 1, guestPasses: 0,
      },
      {
        plan: 'VIP PLAN', monthlyPrice: 1499, yearlyPrice: 15189,
        color: '#f9a825', isActive: true,
        benefits: ['All Premium features', '4 PT sessions/month', 'Priority class booking'],
        maxClasses: -1, ptSessions: 4, guestPasses: 2,
      },
    ];
  }
  return plansCache;
}

// ─── Toggle ───────────────────────────────────────────────────────────────────

export async function togglePricing(yearly) {
  isYearly = yearly;

  const userCurrentPlan    = window.userCurrentPlan    || 'PREMIUM PLAN';
  const userCurrentBilling = window.userCurrentBilling || 'monthly';

  await initSubscriptionSelection('.selection-cards');
  await initSubscriptionCards('.card-container', userCurrentPlan, userCurrentBilling);

  updateSignUpReceipt();
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function getPriceDisplay(plan) {
  if (isYearly) {
    return `<span class="price">₱${plan.yearlyPrice.toLocaleString('en-PH')}</span><span class="month">/year</span>`;
  }
  return `<span class="price">₱${plan.monthlyPrice.toLocaleString('en-PH')}</span><span class="month">/month</span>`;
}

function getSavingsBadge(plan) {
  if (!isYearly) return '';
  const savings = Math.round(plan.monthlyPrice * 12 - plan.yearlyPrice);
  if (savings <= 0) return '';
  return `<div class="savings-badge">Save ₱${savings.toLocaleString('en-PH')}</div>`;
}

function getCurrentPlanPrice(planName, billing = 'monthly') {
  const plan = plansCache.find(p => p.plan === planName);
  if (!plan) return 0;
  return billing === 'yearly' ? plan.yearlyPrice : plan.monthlyPrice;
}

// ─── Pricing toggle HTML ──────────────────────────────────────────────────────

export function renderPricingToggle() {
  return `
    <div class="pricing-toggle">
      <span class="toggle-label ${!isYearly ? 'active' : ''}">Monthly</span>
      <label class="toggle-switch">
        <input type="checkbox" id="pricingToggle" ${isYearly ? 'checked' : ''}
               onchange="window.togglePricing(this.checked)" />
        <span class="toggle-slider"></span>
      </label>
      <span class="toggle-label ${isYearly ? 'active' : ''}">Yearly</span>
      <span class="toggle-discount">Save 16%</span>
    </div>
  `;
}

// ─── Static cards (read-only, no buttons) ────────────────────────────────────

export async function renderStaticCards() {
  const plans = await fetchPlans();
  return plans
    .filter(p => p.isActive)
    .map(p => `
      <div class="sub-card" style="border-top:4px solid ${p.color};">
        <div class="sub-header">
          <h3 style="color:${p.color};">${p.plan}</h3>
          ${getSavingsBadge(p)}
        </div>
        <div class="sub-price">${getPriceDisplay(p)}</div>
        <ul class="sub-benefits">
          ${p.benefits.map(b => `<li>✓ ${b}</li>`).join('')}
        </ul>
      </div>`)
    .join('');
}

// ─── Subscription management cards (My Membership page) ──────────────────────

export async function renderSubscriptionCards(currentPlan = null, currentBilling = 'monthly') {
  const plans = await fetchPlans();

  let html = `
    <div class="pricing-wrapper">
      ${renderPricingToggle()}
      <div class="sub-container">
  `;

  plans.filter(p => p.isActive).forEach(plan => {
    const isCurrentPlan    = currentPlan === plan.plan;
    const isCurrentBilling = (isYearly && currentBilling === 'yearly') ||
                             (!isYearly && currentBilling === 'monthly');

    let buttonClass, buttonText, buttonLink;

    if (isCurrentPlan && isCurrentBilling) {
      buttonClass = 'current-plan';
      buttonText  = 'Current Plan';
      buttonLink  = '';
    } else if (isCurrentPlan && !isCurrentBilling) {
      buttonClass = 'change-plan';
      buttonText  = isYearly ? 'Upgrade to Yearly' : 'Switch to Monthly';
      const price      = isYearly ? plan.yearlyPrice : plan.monthlyPrice;
      const planParam  = encodeURIComponent(plan.plan);
      const billingParam = isYearly ? 'yearly' : 'monthly';
      buttonLink = `href="payment.php?type=billing-change&plan=${planParam}&price=${price}&billing=${billingParam}"`;
    } else {
      const currentPrice = isYearly ? plan.yearlyPrice : plan.monthlyPrice;
      const isUpgrade    = currentPrice > getCurrentPlanPrice(currentPlan, currentBilling);
      buttonClass        = 'change-plan';
      buttonText         = isUpgrade ? 'Upgrade Plan' : 'Downgrade Plan';
      const planParam    = encodeURIComponent(plan.plan);
      const billingParam = isYearly ? 'yearly' : 'monthly';
      buttonLink = `href="payment.php?type=change&plan=${planParam}&price=${currentPrice}&billing=${billingParam}"`;
    }

    html += `
      <div class="sub-card" style="border-top:4px solid ${plan.color};">
        <div class="sub-header">
          <h3 style="color:${plan.color};">${plan.plan}</h3>
          ${getSavingsBadge(plan)}
        </div>
        <div class="sub-price">${getPriceDisplay(plan)}</div>
        <ul class="sub-benefits">
          ${plan.benefits.map(b => `<li>${b}</li>`).join('')}
        </ul>
        <div class="buttons">
          <a ${buttonLink} class="${buttonClass}">${buttonText}</a>
        </div>
      </div>
    `;
  });

  html += '</div></div>';
  return html;
}

// ─── Selection cards (Sign-up page) ──────────────────────────────────────────

export async function renderSelectionCards() {
  const plans = await fetchPlans();

  let html = `
    <div class="pricing-wrapper">
      ${renderPricingToggle()}
      <div class="sub-container">
  `;

  plans.filter(p => p.isActive).forEach(plan => {
    const id           = plan.plan.toLowerCase().replace(/\s+/g, '-');
    const currentPrice = isYearly ? plan.yearlyPrice : plan.monthlyPrice;
    const billing      = isYearly ? 'yearly' : 'monthly';

    html += `
      <label class="sub-card-select">
        <input
          type="radio"
          name="membership_plan"
          id="${id}"
          value="${plan.plan}"
          data-price="${currentPrice}"
          data-billing="${billing}"
        />
        <div class="sub-header">
          <h3 style="color:${plan.color};">${plan.plan}</h3>
          ${getSavingsBadge(plan)}
        </div>
        <div class="sub-price">${getPriceDisplay(plan)}</div>
        <ul class="sub-benefits">
          ${plan.benefits.map(b => `<li>✓ ${b}</li>`).join('')}
        </ul>
      </label>
    `;
  });

  html += '</div></div>';
  return html;
}

// ─── Init helpers ─────────────────────────────────────────────────────────────

export async function initSubscriptionCards(containerSelector, currentPlan = null, currentBilling = 'monthly') {
  const container = document.querySelector(containerSelector);
  if (!container) return;
  container.innerHTML = await renderSubscriptionCards(currentPlan, currentBilling);
}

export async function initSubscriptionSelection(containerSelector) {
  const container = document.querySelector(containerSelector);
  if (!container) return;
  container.innerHTML = await renderSelectionCards();
}

// ─── Receipt sync (sign-up page) ─────────────────────────────────────────────

function updateSignUpReceipt() {
  const receiptRow = document.querySelector('.reciept-row');
  const totalPrice = document.querySelector('.total-price');
  if (!receiptRow || !totalPrice) return;

  const selectedInput = document.querySelector('input[name="membership_plan"]:checked');
  if (!selectedInput) return;

  const planName = selectedInput.value;
  const plan     = plansCache.find(p => p.plan === planName);
  if (!plan) return;

  const price  = isYearly ? plan.yearlyPrice : plan.monthlyPrice;
  const period = isYearly ? 'yearly' : 'monthly';

  receiptRow.querySelector('.price').textContent = `₱${price.toLocaleString('en-PH')}`;
  receiptRow.querySelector('small').textContent  = `Billed ${period}`;
  totalPrice.textContent                         = `₱${price.toLocaleString('en-PH')}`;
}

// ─── Global exposure ──────────────────────────────────────────────────────────

window.togglePricing = togglePricing;

window.getSelectedPlanPrice = function () {
  const selectedInput = document.querySelector('input[name="membership_plan"]:checked');
  if (!selectedInput) return null;
  return { price: selectedInput.dataset.price, billing: selectedInput.dataset.billing };
};

// ─── Initialise on load ───────────────────────────────────────────────────────

(async () => {
  await fetchPlans();   // warm the cache

  await initSubscriptionSelection('.selection-cards');

  window.userCurrentPlan    = window.userCurrentPlan    || 'PREMIUM PLAN';
  window.userCurrentBilling = window.userCurrentBilling || 'monthly';

  await initSubscriptionCards('.card-container', window.userCurrentPlan, window.userCurrentBilling);
})();