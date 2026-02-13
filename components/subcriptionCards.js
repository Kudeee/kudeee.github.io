import { subscriptions } from "../data/subscription-data.js";

export function renderStaticCards() {
  let html = "";
  
  subscriptions.forEach(sub => {
    html += `
      <div class="sub-card">
        <div class="sub-header">
          <h3>${sub.plan}</h3>
          <span class="badge">${sub.members} Members</span>
        </div>
        
        <div class="sub-price">
          <span class="price">₱${sub.price}</span><span class="month">/month</span>
        </div>
        
        <ul class="sub-benefits">
          ${sub.benefits.map(b => `<li>✓ ${b}</li>`).join("")}
        </ul>
      </div>
    `;
  });
  
  return html;
}

export function renderSubscriptionCards(currentPlan = null) {
  let subscriptionHTML = "";

  subscriptions.forEach((subscription) => {
    const isCurrentPlan = currentPlan === subscription.plan;
    const buttonClass = isCurrentPlan ? "current-plan" : "change-plan";
    const buttonText = isCurrentPlan
      ? "Current Plan"
      : subscription.price < getCurrentPlanPrice(currentPlan)
      ? "Downgrade Plan"
      : "Upgrade Plan";
    
    // Construct the payment link with plan information
    const planParam = encodeURIComponent(subscription.plan);
    const priceParam = subscription.price;
    const buttonLink = isCurrentPlan 
      ? "" 
      : `href="payment.html?type=change&plan=${planParam}&price=${priceParam}"`;

    subscriptionHTML += `
      <div class="sub-card">
        <div class="sub-header">
          <h3>${subscription.plan}</h3>
        </div>
        <div class="sub-price">
          <span class="price">₱${subscription.price}</span><span class="month">/month</span>
        </div>
        <ul class="sub-benefits">
          ${subscription.benefits.map((benefit) => `<li>${benefit}</li>`).join("")}
        </ul>
        <div class="buttons">
        <a ${buttonLink} class="${buttonClass}">${buttonText}</a>
        </div>
      </div>
    `;
  });

  return subscriptionHTML;
}

export function renderSelectionCards() {
  let html = "";
  
  subscriptions.forEach(sub => {
    const id = sub.plan.toLowerCase().replace(/\s+/g, "-");
    
    html += `
      <label class="sub-card-select">
        <input type="radio" name="membership-plan" id="${id}" value="${sub.plan}">
        
        <div class="sub-header">
          <h3>${sub.plan}</h3>
        </div>
        
        <div class="sub-price">
          <span class="price">₱${sub.price}</span><span class="month">/month</span>
        </div>
        
        <ul class="sub-benefits">
          ${sub.benefits.map(b => `<li>✓ ${b}</li>`).join("")}
        </ul>
      </label>
    `;
  });
  
  return html;
}

export function initSubscriptionCards(containerSelector, currentPlan = null) {
  const container = document.querySelector(containerSelector);
  if (container) {
    container.innerHTML = renderSubscriptionCards(currentPlan);
  }
}

export function initSubscriptionSelection(containerSelector) {
  const container = document.querySelector(containerSelector);
  if (container) {
    container.innerHTML = renderSelectionCards();
  }
}

function getCurrentPlanPrice(planName) {
  const plan = subscriptions.find((sub) => sub.plan === planName);
  return plan ? plan.price : 0;
}

initSubscriptionSelection(".selection-cards");

const userCurrentPlan = "PREMIUM PLAN";
  
  initSubscriptionCards('.card-container', userCurrentPlan);