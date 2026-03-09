/**
 * payment-methods.js
 * Injects the payment method selector into any .payment-method-js container.
 * All inputs have proper name attributes so they POST correctly to PHP.
 */

const paymentMethod = `
  <h3 id="head-title">Payment Method</h3>

  <!-- GCash -->
  <label class="payment-option">
    <input type="radio" name="payment_method" value="gcash" required />
    <div class="payment-option-name">
      <img src="assests/icons/GCash.svg" alt="Gcash logo" />
      GCash
    </div>
  </label>

  <!-- GoTyme -->
  <label class="payment-option">
    <input type="radio" name="payment_method" value="gotyme" />
    <div class="payment-option-name">
      <img src="assests/icons/GoTyme.svg" alt="GoTyme logo" />
      GoTyme
    </div>
  </label>

  <!-- Maya -->
  <label class="payment-option">
    <input type="radio" name="payment_method" value="maya" />
    <div class="payment-option-name">
      <img src="assests/icons/maya.svg" alt="Maya logo" />
      Maya
    </div>
  </label>

  <!-- Credit / Debit Card -->
  <label class="payment-option">
    <input type="radio" name="payment_method" value="card" />
    <div class="payment-option-name">
      <img src="assests/icons/visa.svg"       alt="Visa logo" />
      <img src="assests/icons/mastercard.svg" alt="Mastercard logo" />
      Credit or Debit Card
    </div>

    <div class="card-payment-info">
      <label for="card_number">Card Number</label>
      <input
        type="text"
        id="card_number"
        name="card_number"
        placeholder="0000 0000 0000 0000"
        maxlength="19"
        autocomplete="cc-number"
      />

      <div class="row">
        <div>
          <label for="card_expiry">Expiry Date</label>
          <input
            type="text"
            id="card_expiry"
            name="card_expiry"
            placeholder="MM/YY"
            maxlength="5"
            autocomplete="cc-exp"
          />
        </div>
        <div>
          <label for="card_cvv">Security Code</label>
          <input
            type="text"
            id="card_cvv"
            name="card_cvv"
            placeholder="CVV"
            maxlength="4"
            autocomplete="cc-csc"
          />
        </div>
      </div>
    </div>
  </label>
`;

function initializePaymentMethods() {
  const containers = document.querySelectorAll(".payment-method-js");

  containers.forEach((container) => {
    container.innerHTML = paymentMethod;
  });

  // Hide the "Payment Method" heading on booking pages (it has its own section title)
  const currentPage = window.location.pathname.split("/").pop();
  if (currentPage === "book-class-page.html" || currentPage === "book-trainer-page.html") {
    document.querySelectorAll("#head-title").forEach((el) => {
      el.style.display = "none";
    });
  }
}

// Run after DOM is ready (handles both inline and deferred loading)
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializePaymentMethods);
} else {
  initializePaymentMethods();
}

// Fallback for pages that inject the container dynamically
setTimeout(initializePaymentMethods, 100);

/**
 * handlePayment — AJAX payment submission.
 * Called from the sign-up / renew / upgrade forms via onsubmit.
 */
async function handlePayment(event) {
  event.preventDefault();

  const form     = event.target;
  const formData = new FormData(form);

  // Basic client-side check: a payment method must be selected
  const method = formData.get("payment_method");
  if (!method) {
    showPopUP("Please select a payment method.");
    return;
  }

  showLoading("Processing Payment...");

  try {
    const response = await fetch("/api/payments/process.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    hideLoading();

    if (result.success) {
      // Let the page handle the success state (redirect or show success UI)
      window.dispatchEvent(new CustomEvent("paymentSuccess", { detail: result }));
    } else {
      showPopUP(result.message || "Payment failed. Please try again.");
    }
  } catch (error) {
    hideLoading();
    showPopUP("Something went wrong. Please try again.");
  }
}

// Expose for inline onsubmit handlers
window.handlePayment = handlePayment;