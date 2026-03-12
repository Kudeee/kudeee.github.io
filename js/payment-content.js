/**
 * payment-content.js
 * Renders the correct payment UI based on URL params (type, plan, billing).
 * Uses live plan data from the API and re-injects payment methods after
 * each HTML injection so the payment-method-js containers are always filled.
 */

// ─── Plan data (fetched once from API, with hardcoded fallback) ───────────────

let plansCache = [];

async function fetchPlans() {
  if (plansCache.length) return plansCache;
  try {
    const res  = await fetch('api/admin/plans/list.php');
    const data = await res.json();
    if (data.success && Array.isArray(data.plans)) {
      plansCache = data.plans.map(p => ({
        plan:         p.plan,
        monthlyPrice: parseFloat(p.monthly_price),
        yearlyPrice:  parseFloat(p.yearly_price),
        color:        p.color || '#ff6b35',
        benefits:     Array.isArray(p.benefits) ? p.benefits : [],
      }));
    }
  } catch (e) {
    console.warn('payment-content: could not fetch plans, using fallback.', e);
  }

  if (!plansCache.length) {
    plansCache = [
      { plan: 'BASIC PLAN',   monthlyPrice: 499,  yearlyPrice: 5028,  color: '#9e9e9e', benefits: ['Gym access (6AM–10PM)', 'Locker room access', '2 group classes/week'] },
      { plan: 'PREMIUM PLAN', monthlyPrice: 899,  yearlyPrice: 9067,  color: '#ff6b35', benefits: ['24/7 gym access', 'Unlimited group classes', '1 PT session/month'] },
      { plan: 'VIP PLAN',     monthlyPrice: 1500, yearlyPrice: 15120, color: '#f9a825', benefits: ['All Premium features', '4 PT sessions/month', 'Priority class booking', '2 guest passes/month'] },
    ];
  }

  return plansCache;
}

// ─── Card HTML builder ────────────────────────────────────────────────────────

function buildPlanCard(plan, isYearly) {
  const price  = isYearly ? plan.yearlyPrice : plan.monthlyPrice;
  const period = isYearly ? '/year' : '/month';
  const savings = Math.round(plan.monthlyPrice * 12 - plan.yearlyPrice);
  const savingsBadge = (isYearly && savings > 0)
    ? `<div class="savings-badge" style="margin-bottom:8px;">Save ₱${savings.toLocaleString('en-PH')}</div>`
    : '';

  return `
    <div class="sub-card" style="border:3px solid ${plan.color};max-width:420px;margin:0 auto;">
      <div class="sub-header">
        <h3 style="color:${plan.color};">${plan.plan}</h3>
        ${savingsBadge}
      </div>
      <div class="sub-price">
        <span class="price">₱${price.toLocaleString('en-PH')}</span><span class="month">${period}</span>
      </div>
      <ul class="sub-benefits">
        ${plan.benefits.map(b => `<li>✓ ${b}</li>`).join('')}
      </ul>
    </div>`;
}

// ─── Page builders ────────────────────────────────────────────────────────────

function buildPaymentPage({ heading, buttonText, plan, isYearly, type, billing }) {
  return `
    <h1 class="header-name">${heading}</h1>
    <div class="containers">
      ${buildPlanCard(plan, isYearly)}
    </div>
    <div class="containers">
      <form id="paymentForm" style="width:100%;max-width:420px;">
        <input type="hidden" name="type"    value="${type}" />
        <input type="hidden" name="plan"    value="${plan.plan}" />
        <input type="hidden" name="billing" value="${billing}" />
        <div class="payment-method-js"></div>
        <button type="submit">${buttonText}</button>
      </form>
    </div>`;
}

const successHTML = `
  <div class="payment-successful">
    <img src="assests/icons/check.png" alt="Success" />
    <p>Payment Successful!</p>
    <button id="backBtn">Back to Homepage</button>
  </div>`;

// ─── Show success screen ──────────────────────────────────────────────────────

function showSuccess() {
  const container = document.querySelector('.container');
  container.innerHTML = successHTML;
  document.getElementById('backBtn').addEventListener('click', () => {
    location.href = 'homepage.php';
  });
}

// ─── Submit handler — POSTs to real API ──────────────────────────────────────

function bindPaymentForm() {
  const form = document.getElementById('paymentForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const method = form.querySelector('input[name="payment_method"]:checked')?.value;
    if (!method) {
      // showPopUP may not exist on this page — use alert as fallback
      if (typeof showPopUP === 'function') showPopUP('Please select a payment method.');
      else alert('Please select a payment method.');
      return;
    }

    if (typeof showLoading === 'function') showLoading('Processing Payment...');

    try {
      const formData = new FormData(form);

      const res    = await fetch('api/payments/process.php', { method: 'POST', body: formData });
      const result = await res.json();

      if (typeof hideLoading === 'function') hideLoading();

      if (result.success) {
        showSuccess();
      } else {
        const msg = result.message || 'Payment failed. Please try again.';
        if (typeof showPopUP === 'function') showPopUP(msg);
        else alert(msg);
      }
    } catch (err) {
      if (typeof hideLoading === 'function') hideLoading();
      const msg = 'Something went wrong. Please try again.';
      if (typeof showPopUP === 'function') showPopUP(msg);
      else alert(msg);
    }
  });
}

// ─── Re-inject payment method options ────────────────────────────────────────
// payment-methods.js runs before our HTML is injected, so we must call
// initializePaymentMethods() ourselves after setting innerHTML.

function injectPaymentMethods() {
  if (typeof initializePaymentMethods === 'function') {
    initializePaymentMethods();
  }
}

// ─── Main init ────────────────────────────────────────────────────────────────

async function init() {
  const params  = new URLSearchParams(window.location.search);
  const type    = params.get('type')    || '';
  const planParam = decodeURIComponent(params.get('plan') || '');
  const billing = params.get('billing') || 'monthly';
  const isYearly = billing === 'yearly';

  const plans = await fetchPlans();
  const container = document.querySelector('.container');
  if (!container) return;

  // ── Renew ──────────────────────────────────────────────────────────────────
  if (type === 'renew') {
    // Fetch member's current plan from session API
    let planName = 'PREMIUM PLAN';
    try {
      const res  = await fetch('api/user/membership/info.php');
      const data = await res.json();
      if (data.success) planName = data.member.plan;
    } catch (_) {}

    let renewIsYearly = false;
    try {
      const res2  = await fetch('api/user/membership/info.php');
      const data2 = await res2.json();
      if (data2.success) renewIsYearly = (data2.subscription?.billing_cycle === 'yearly');
    } catch (_) {}

    const plan = plans.find(p => p.plan === planName) || plans[1];
    container.innerHTML = buildPaymentPage({
      heading:    'Renew Membership',
      buttonText: 'RENEW',
      plan, isYearly: renewIsYearly,
      type: 'renew', billing: renewIsYearly ? 'yearly' : 'monthly',
    });
    document.title = 'Renew Membership';
    injectPaymentMethods();
    bindPaymentForm();
    return;
  }

  // ── Upgrade (straight to VIP) ──────────────────────────────────────────────
  if (type === 'upgrade') {
    const plan = plans.find(p => p.plan === 'VIP PLAN') || plans[2];
    container.innerHTML = buildPaymentPage({
      heading:    'Upgrade to VIP',
      buttonText: 'UPGRADE TO VIP',
      plan, isYearly: false,
      type: 'upgrade', billing: 'monthly',
    });
    document.title = 'Upgrade to VIP';
    injectPaymentMethods();
    bindPaymentForm();
    return;
  }

  // ── Change plan (upgrade/downgrade to a specific plan) ────────────────────
  if (type === 'change' && planParam) {
    const plan = plans.find(p => p.plan === planParam);
    if (!plan) {
      container.innerHTML = '<p style="text-align:center;padding:60px;color:#c00;">Plan not found.</p>';
      return;
    }

    // Determine heading
    const planOrder = ['BASIC PLAN', 'PREMIUM PLAN', 'VIP PLAN'];
    let heading = 'Change Membership Plan';
    let buttonText = 'CHANGE PLAN';
    try {
      const res  = await fetch('api/user/membership/info.php');
      const data = await res.json();
      if (data.success) {
        const currentIdx = planOrder.indexOf(data.member.plan);
        const targetIdx  = planOrder.indexOf(planParam);
        if (targetIdx > currentIdx)  { heading = 'Upgrade Membership';   buttonText = 'UPGRADE'; }
        if (targetIdx < currentIdx)  { heading = 'Downgrade Membership'; buttonText = 'CHANGE PLAN'; }
      }
    } catch (_) {}

    container.innerHTML = buildPaymentPage({
      heading, buttonText,
      plan, isYearly,
      type: 'change', billing,
    });
    document.title = heading;
    injectPaymentMethods();
    bindPaymentForm();
    return;
  }

  // ── Billing change (same plan, different cycle) ────────────────────────────
  if (type === 'billing-change' && planParam) {
    const plan = plans.find(p => p.plan === planParam);
    if (!plan) {
      container.innerHTML = '<p style="text-align:center;padding:60px;color:#c00;">Plan not found.</p>';
      return;
    }

    const heading    = isYearly ? 'Upgrade to Yearly Billing' : 'Switch to Monthly Billing';
    const buttonText = isYearly ? 'UPGRADE TO YEARLY' : 'SWITCH TO MONTHLY';

    container.innerHTML = buildPaymentPage({
      heading, buttonText,
      plan, isYearly,
      type: 'billing-change', billing,
    });
    document.title = heading;
    injectPaymentMethods();
    bindPaymentForm();
    return;
  }

  // ── Fallback: unknown/missing params ──────────────────────────────────────
  container.innerHTML = '<p style="text-align:center;padding:60px;color:#999;">No payment action specified.</p>';
}

// Run after DOM + payment-methods.js are both ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}