// Import the subscription data
import { subscriptions } from '../data/subscription-data.js';

const successPaymentHTML = `
<div class="payment-successful">
        <img src="assests/icons/check.png" alt="">
        <p>Payment Successful!</p>
        <button class="back-btn button" >Back to Homepage</button>
</div>
`;

// Function to generate subscription card HTML (matching renderStaticCards structure)
function generateSubscriptionCard(planName) {
  const plan = subscriptions.find(sub => sub.plan === planName);
  
  if (!plan) {
    return '<div class="card"><h1>Plan not found</h1></div>';
  }
  
  return `
    <div class="sub-card" style="border: 3px solid #ff6b35; max-width: 400px; margin: 0 auto;">
      <div class="sub-header">
        <h3>${plan.plan}</h3>
      </div>
      
      <div class="sub-price">
        <span class="price">₱${plan.price}</span><span class="month">/month</span>
      </div>
      
      <ul class="sub-benefits">
        ${plan.benefits.map(b => `<li>✓ ${b}</li>`).join("")}
      </ul>
    </div>
  `;
}

// Generate renew HTML using renderStaticCards structure
function generateRenewHTML() {
  const premiumCard = generateSubscriptionCard("PREMIUM PLAN");
  
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

// Generate upgrade HTML using renderStaticCards structure
function generateUpgradeHTML() {
  const vipCard = generateSubscriptionCard("VIP PLAN");
  
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

// Function to generate plan change HTML dynamically using renderStaticCards structure
function generatePlanChangeHTML(planName, isUpgrade) {
  const actionText = isUpgrade ? "Upgrade" : "Downgrade";
  const buttonText = isUpgrade ? "UPGRADE" : "CHANGE PLAN";
  
  const planCard = generateSubscriptionCard(planName);
  
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
      location.href = "homepage.html";
    });
  }
}

// Parse URL parameters
const params = new URLSearchParams(window.location.search);
const type = params.get("type");
const planName = params.get("plan");
const price = params.get("price");

if (type === "change" && planName && price) {
  // Find the subscription plan
  const plan = subscriptions.find(sub => sub.plan === planName);
  
  if (plan) {
    // Determine if it's an upgrade or downgrade
    // Assuming current plan is Premium (899)
    const currentPrice = 899; // This should ideally come from user session
    const isUpgrade = parseInt(price) > currentPrice;
    
    const planChangeHTML = generatePlanChangeHTML(planName, isUpgrade);
    document.querySelector(".container").innerHTML = planChangeHTML;
    
    // Update page title
    const pageTitle = isUpgrade ? "Upgrade Membership" : "Change Membership";
    const pageTitleElement = document.querySelector(".page-title");
    if (pageTitleElement) {
      pageTitleElement.textContent = pageTitle;
    }
    document.title = pageTitle;
  }
} else if (type) {
  renderPayment(type);
}