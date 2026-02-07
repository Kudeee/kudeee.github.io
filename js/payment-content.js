const renewHTML = `
<h1 class="header-name">Renew Membership</h1>

      <div class="containers">
        <div class="card" id="mem-plan">
          <h1>Premium Plan</h1>
          <h2>₱ 899</h2>

          <p>• All Basic Features</p>
          <p>• Locker Access</p>
          <p>• Group Classes</p>
        </div>
      </div>

      <div class="containers">
        <form onsubmit="handlePayment(event)">
          <div class="payment-method-js"></div>
          <button class="payment-btn button" type="submit" onClick="renderPayment('successPayment')">RENEW</button>
        </form>
      </div>
    </div>
`;

const upgradeHTML = `
<h1 class="header-name">Upgrade Membership</h1>

      <div class="containers">
        <div class="card" id="mem-plan">
          <h1>VIP Plan</h1>
          <h2>₱ 1,500</h2>

          <p>• All Premium Features</p>
          <p>• Personal Trainer</p>
          <p>• Priority Booking</p>
        </div>
      </div>

      <div class="containers">
        <form onsubmit="handlePayment(event)">
          <div class="payment-method-js"></div>
          <button class="payment-btn button" type="submit" onClick="renderPayment('successPayment')">UPGRADE</button>
        </form>
      </div>
    </div>
`;

const successPaymentHTML = `
<div class="payment-successful">
        <img src="assests/icons/check.png" alt="">
        <p>Payment Successful!</p>
        <button class="back-btn button" >Back to Homepage</button>
</div>
`;

const backBtn = document.querySelector(".back-btn");

function renderPayment(paymentType) {
  if (paymentType === "renew") {
    document.querySelector(".container").innerHTML = renewHTML;
  }

  if (paymentType === "upgrade") {
    document.querySelector(".container").innerHTML = upgradeHTML;
  }

  if (paymentType === "successPayment") {
    document.querySelector(".container").innerHTML = successPaymentHTML;

    const backBtn = document.querySelector(".back-btn");

    backBtn.addEventListener("click", () => {
      location.href = "homepage.html";
    });
  }
}

const params = new URLSearchParams(window.location.search);
const type = params.get("type");

if (type) {
  renderPayment(type);
}
