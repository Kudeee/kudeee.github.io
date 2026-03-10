// Import the subscription data
import { subscriptions } from '../data/subscription-data.js';

const successPaymentHTML = `
<div class="payment-successful">
        <img src="assests/icons/check.png" alt="">
        <p>Payment Successful!</p>
        <button class="back-btn button" >Back to Homepage</button>
</div>
`;

// Function to generate subscription card HTML
function generateSubscriptionCard(planName, isYearly = false) {
  const plan = subscriptions.find(sub => sub.plan === planName);
  
  if (!plan) {
    return '<div class="card"><h1>Plan not found</h1></div>';
  }
  
  const price = isYearly ? plan.yearlyPrice : plan.monthlyPrice;
  const period = isYearly ? '/year' : '/month';
  const savings = isYearly ? `<div class="savings-badge">Save ₱${(plan.monthlyPrice * 12) - plan.yearlyPrice}</div>` : '';
  
  return `
    <div class="sub-card" style="border: 3px solid #ff6b35; max-width: 400px; margin: 0 auto;">
      <div class="sub-header">
        <h3>${plan.plan}</h3>
        ${savings}
      </div>
      
      <div class="sub-price">
        <span class="price">₱${price}</span><span class="month">${period}</span>
      </div>
      
      <ul class="sub-benefits">
        ${plan.benefits.map(b => `<li>✓ ${b}</li>`).join("")}
      </ul>
    </div>
  `;
}

// Generate renew HTML
function generateRenewHTML(isYearly = false) {
  const premiumCard = generateSubscriptionCard("PREMIUM PLAN", isYearly);
  
  return `
<h1 class="header-name">Renew Membership</h1>

      <div class="containers">
        ${premiumCard}
      </div>

      <div class="containers">
        <form onsubmit="handlePayment(event)">
          <div class="payment-method-js"></div>
          <button class="payment-btn button" type="submit" onClick="renderPayment('successPayment')">RENEW</button>
        </form>
      </div>
    </div>
`;
}

// Generate upgrade HTML
function generateUpgradeHTML(isYearly = false) {
  const vipCard = generateSubscriptionCard("VIP PLAN", isYearly);
  
  return `
<h1 class="header-name">Upgrade Membership</h1>

      <div class="containers">
        ${vipCard}
      </div>

      <div class="containers">
        <form onsubmit="handlePayment(event)">
          <div class="payment-method-js"></div>
          <button class="payment-btn button" type="submit" onClick="renderPayment('successPayment')">UPGRADE</button>
        </form>
      </div>
    </div>
`;
}

// Function to generate plan change HTML dynamically
function generatePlanChangeHTML(planName, isUpgrade, isYearly = false) {
  const actionText = isUpgrade ? "Upgrade" : "Downgrade";
  const buttonText = isUpgrade ? "UPGRADE" : "CHANGE PLAN";
  
  const planCard = generateSubscriptionCard(planName, isYearly);
  
  return `
<h1 class="header-name">${actionText} Membership</h1>

      <div class="containers">
        ${planCard}
      </div>

      <div class="containers">
        <form onsubmit="handlePayment(event)">
          <div class="payment-method-js"></div>
          <button class="payment-btn button" type="submit" onClick="renderPayment('successPayment')">${buttonText}</button>
        </form>
      </div>
    </div>
`;
}

// Function to generate billing change HTML
function generateBillingChangeHTML(planName, isYearly = false) {
  const actionText = isYearly ? "Upgrade to Yearly Billing" : "Switch to Monthly Billing";
  const buttonText = isYearly ? "UPGRADE TO YEARLY" : "SWITCH TO MONTHLY";
  
  const planCard = generateSubscriptionCard(planName, isYearly);
  
  return `
<h1 class="header-name">${actionText}</h1>

      <div class="containers">
        ${planCard}
      </div>

      <div class="containers">
        <form onsubmit="handlePayment(event)">
          <div class="payment-method-js"></div>
          <button class="payment-btn button" type="submit" onClick="renderPayment('successPayment')">${buttonText}</button>
        </form>
      </div>
    </div>
`;
}

function renderPayment(paymentType) {
  if (paymentType === "renew") {
    document.querySelector(".container").innerHTML = generateRenewHTML();
  }

  if (paymentType === "upgrade") {
    document.querySelector(".container").innerHTML = generateUpgradeHTML();
  }

  if (paymentType === "successPayment") {
    document.querySelector(".container").innerHTML = successPaymentHTML;

    const backBtn = document.querySelector(".back-btn");

    backBtn.addEventListener("click", () => {
      location.href = "homepage.php";
    });
  }
}

// Parse URL parameters
const params = new URLSearchParams(window.location.search);
const type = params.get("type");
const planName = params.get("plan");
const price = params.get("price");
const billing = params.get("billing");

const isYearly = billing === 'yearly';

if (type === "billing-change" && planName && price) {
  // User is changing billing cycle for same plan
  const plan = subscriptions.find(sub => sub.plan === planName);
  
  if (plan) {
    const billingChangeHTML = generateBillingChangeHTML(planName, isYearly);
    document.querySelector(".container").innerHTML = billingChangeHTML;
    
    // Update page title
    const pageTitle = isYearly ? "Upgrade to Yearly Billing" : "Switch to Monthly Billing";
    const pageTitleElement = document.querySelector(".page-title");
    if (pageTitleElement) {
      pageTitleElement.textContent = pageTitle;
    }
    document.title = pageTitle;
  }
} else if (type === "change" && planName && price) {
  // User is changing to a different plan
  const plan = subscriptions.find(sub => sub.plan === planName);
  
  if (plan) {
    // Determine if it's an upgrade or downgrade
    const currentPrice = isYearly ? 9067 : 899; // Premium plan price
    const isUpgrade = parseInt(price) > currentPrice;
    
    const planChangeHTML = generatePlanChangeHTML(planName, isUpgrade, isYearly);
    document.querySelector(".container").innerHTML = planChangeHTML;
    
    // Update page title
    const pageTitle = isUpgrade ? "Upgrade Membership" : "Change Membership";
    const pageTitleElement = document.querySelector(".page-title");
    if (pageTitleElement) {
      pageTitleElement.textContent = pageTitle;
    }
    document.title = pageTitle;
  }
} else if (type === "renew") {
  renderPayment(type);
} else if (type === "upgrade") {
  renderPayment(type);
}