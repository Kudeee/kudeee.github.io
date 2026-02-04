const paymentMethod = `
          <h3 id='head-title'>Payments Method</h3>

          <label class="payment-option">
            <input type="radio" name="payment-method" />
            <div class="payment-option-name">
              <img src="assests/icons/GCash.svg" alt="Gcash logo">
              Gcash
            </div>
          </label>

          <label class="payment-option">
            <input type="radio" name="payment-method" />
            <div class="payment-option-name">
              <img src="assests/icons/GoTyme.svg" alt="gotyme logo">
              GoTyme
            </div>
          </label>

          <label class="payment-option">
            <input type="radio" name="payment-method" />
            <div class="payment-option-name">
              <img src="assests/icons/maya.svg" alt="maya logo">
              Maya
            </div>
          </label>

          <label class="payment-option">
            <input type="radio" name="payment-method" />
            <div class="payment-option-name">
              <img src="assests/icons/visa.svg" alt="visa logo">
              <img src="assests/icons/mastercard.svg" alt="mastercard logo">
              Credit or debit card
            </div>

            <div class="card-payment-info">
              <label>Card Number</label>
              <input type="number" placeholder="0000 0000 0000 0000" />

              <div class="row">
                <div>
                  <label>Expiry Date</label>
                  <input type="text" placeholder="MM/YY" />
                </div>

                <div>
                  <label>Security Code</label>
                  <input type="number" placeholder="CVV" />
                </div>
              </div>
            </div>
          </label>
`;

document.querySelector(".payment-method-js").innerHTML = paymentMethod;

const currentPage = window.location.pathname.split("/").pop();

if (
  currentPage === "book-class-page.html" ||
  currentPage === "book-trainer-page.html"
) {
  document.getElementById("head-title").style.display = "none";
}

async function handlePayment(event) {
  showLoading("Processing Payment");

  try {
    await simulateLoading(9000);

    hideLoading();
  } catch (error) {
    hideLoading();

    alert("Something went wrong. Please try again.");
  }
}
