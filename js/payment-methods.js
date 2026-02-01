const paymentMethod = `
          <h3 id='head-title'>Payments Method</h3>

          <label class="payment-option">
            <input type="radio" name="payment-method" />
            <div class="payment-option-name">
              <img src="assests/icons/GCash.svg" alt="">
              Gcash
            </div>
          </label>

          <label class="payment-option">
            <input type="radio" name="payment-method" />
            <div class="payment-option-name">
              <img src="assests/icons/GoTyme.svg" alt="">
              GoTyme
            </div>
          </label>

          <label class="payment-option">
            <input type="radio" name="payment-method" />
            <div class="payment-option-name">
              <img src="assests/icons/maya.svg" alt="">
              Maya
            </div>
          </label>

          <label class="payment-option">
            <input type="radio" name="payment-method" />
            <div class="payment-option-name">
              <img src="assests/icons/visa.svg" alt="">
              <img src="assests/icons/mastercard.svg" alt="">
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
`

document.querySelector('.payment-method-js').innerHTML = paymentMethod;

if(currentPage === 'book-class-page.html' || currentPage === 'book-trainer-page.html'){
document.getElementById('head-title').style.display = 'none';
}
