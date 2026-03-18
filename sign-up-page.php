<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/sign-up-page.css" />
    <title>Sign Up</title>
    <style>
      /* ── Recurring toggle card ─────────────────────────────────── */
      .recurring-toggle-card {
        margin: 20px 0 8px;
        border: 2px solid #e5e5e5;
        border-radius: 14px;
        padding: 18px 20px;
        transition: border-color 0.2s, background 0.2s;
        background: #fafafa;
      }

      .recurring-toggle-card.is-on {
        border-color: #ff6b35;
        background: #fff7f2;
      }

      .recurring-toggle-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
      }

      .recurring-toggle-label {
        flex: 1;
      }

      .recurring-toggle-label strong {
        display: block;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 3px;
      }

      .recurring-toggle-label span {
        font-size: 0.82rem;
        color: #777;
        line-height: 1.5;
      }

      /* Toggle switch */
      .rt-switch {
        position: relative;
        display: inline-block;
        width: 52px;
        height: 28px;
        flex-shrink: 0;
      }

      .rt-switch input {
        opacity: 0;
        width: 0;
        height: 0;
        margin: 0;
        padding: 0;
      }

      .rt-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: #d0d0d0;
        border-radius: 28px;
        transition: background 0.25s;
      }

      .rt-slider::before {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        left: 4px;
        top: 4px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.25s;
        box-shadow: 0 1px 4px rgba(0,0,0,0.18);
      }

      .rt-switch input:checked + .rt-slider {
        background: linear-gradient(135deg, #ff6b35, #ff8c5a);
      }

      .rt-switch input:checked + .rt-slider::before {
        transform: translateX(24px);
      }

      .recurring-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 10px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
        background: #e8f5e9;
        color: #2e7d32;
        transition: all 0.2s;
      }

      .recurring-badge.off {
        background: #f5f5f5;
        color: #999;
      }

      .recurring-badge-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="form-container">
        <header class="header">
          <a href="index.php">
            <img src="assests/logo/society-fit.png" alt="society-fit logo" />
          </a>
        </header>

        <h1>Sign Up</h1>

        <!-- Multi-step form — final submit POSTs to registration endpoint -->
        <form id="form" method="POST" action="/api/auth/register.php">

          <!-- ─── PAGE 1: Personal Info ─────────────────────────── -->
          <div class="page" id="first-page" style="display: block">
            <h3>Personal Info</h3>

            <div class="row">
              <div>
                <label for="first_name">First Name</label>
                <input
                  type="text"
                  id="first_name"
                  name="first_name"
                  placeholder="First name"
                  autocomplete="given-name"
                  required
                />
              </div>

              <div>
                <label for="last_name">Last Name</label>
                <input
                  type="text"
                  id="last_name"
                  name="last_name"
                  placeholder="Last name"
                  autocomplete="family-name"
                  required
                />
              </div>
            </div>

            <label for="email">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="youremail@example.com"
              autocomplete="email"
              required
            />

            <div class="row">
              <div>
                <label for="phone">Phone Number</label>
                <input
                  type="tel"
                  id="phone"
                  name="phone"
                  placeholder="0909xx-xxx-xxxx"
                  autocomplete="tel"
                  required
                />
              </div>

              <div>
                <label for="zip">Zip</label>
                <input
                  type="number"
                  id="zip"
                  name="zip"
                  placeholder="(Optional)"
                  autocomplete="postal-code"
                />
              </div>
            </div>

            <div class="form-btn">
              <button style="opacity: 0" disabled></button>
              <button class="btn" id="fnext-btn" type="button">Next</button>
            </div>

            <div class="already-have-account">
              Already have an account? <a href="login-page.php">Login Here</a>
            </div>
          </div>

          <!-- ─── PAGE 2: Password ──────────────────────────────── -->
          <div class="page" id="second-page" style="display: none">
            <h3>Create a Password</h3>

            <label for="password">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Use a Strong Password"
              autocomplete="new-password"
              required
            />

            <label for="confirm_password">Confirm Password</label>
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              placeholder="Confirm your password"
              autocomplete="new-password"
              required
            />

            <div class="form-btn">
              <button class="prev" id="prev-btn" type="button">Back</button>
              <button class="btn" id="next-btn" type="button">Next</button>
            </div>
          </div>

          <!-- ─── PAGE 3: Membership Plan ──────────────────────── -->
          <div class="page" id="second-last-page" style="display: none">
            <h3>Choose your Membership Plan</h3>
            <div class="selection-cards"></div>

            <div class="form-btn">
              <button class="btn" id="second-last-prev-btn" type="button">Back</button>
              <button class="btn" id="second-last-next-btn" type="button">Next</button>
            </div>
          </div>

          <!-- ─── PAGE 4: Payment Method ────────────────────────── -->
          <div class="page" id="last-page" style="display: none">
            <!-- Payment fields injected by payment-methods.js -->
            <div class="payment-method-js"></div>

            <!-- ── Recurring Subscription Toggle ──────────────────── -->
            <div class="recurring-toggle-card is-on" id="recurringCard">
              <div class="recurring-toggle-header">
                <div class="recurring-toggle-label">
                  <strong>Auto-Renew Subscription</strong>
                  <span>Automatically renew your membership each billing cycle so you never lose access.</span>
                </div>
                <label class="rt-switch" title="Toggle auto-renew">
                  <input
                    type="checkbox"
                    id="signup_recurring"
                    name="is_recurring"
                    value="1"
                    checked
                    onchange="updateRecurringUI(this.checked)"
                  />
                  <span class="rt-slider"></span>
                </label>
              </div>
              <div class="recurring-badge" id="recurringBadge">
                <span class="recurring-badge-dot"></span>
                Auto-renew ON — you can turn this off anytime from your account
              </div>
            </div>
            <!-- ── /Recurring Toggle ────────────────────────────── -->

            <div class="form-btn">
              <button class="btn" id="last-prev-btn" type="button">Back</button>
              <button class="btn" id="last-next-btn" type="button">Next</button>
            </div>
          </div>

          <!-- ─── PAGE 5: Order Summary & Submit ───────────────── -->
          <div class="page" id="sub-page" style="display: none">
            <h2>Society Fit Membership</h2>

            <div class="reciept-row">
              <span>Subscription <br /><small>Billed monthly</small></span>
              <span class="price">₱899</span>
            </div>

            <!-- Auto-renew status in receipt -->
            <div class="reciept-row" id="receiptRecurringRow">
              <span>Auto-Renew <br /><small>Billing preference</small></span>
              <span class="price" id="receiptRecurringVal" style="color:#2e7d32;font-size:0.88rem;">Enabled ✓</span>
            </div>

            <!-- Hidden fields populated by JS -->
            <input type="hidden" name="selected_plan"    id="hidden_selected_plan"    value="" />
            <input type="hidden" name="billing_cycle"    id="hidden_billing_cycle"    value="monthly" />
            <input type="hidden" name="plan_price"       id="hidden_plan_price"       value="" />
            <!-- is_recurring is already sent as a checkbox; this mirrors it for clarity -->
            <input type="hidden" name="signup_recurring_mirror" id="hidden_recurring" value="1" />

            <label class="discount-label" for="discount_code">Apply Discount Code</label>
            <div class="discount-box">
              <input
                type="text"
                id="discount_code"
                name="discount_code"
                placeholder="Code"
              />
            </div>

            <div class="total">
              <span>Total</span>
              <span class="total-price">₱899</span>
            </div>

            <div class="terms">
              <input type="checkbox" id="terms" name="agree_terms" value="1" required />
              <label for="terms">
                I agree to the <a href="#">Terms and Conditions</a> and the
                <a href="#">Automatic Renewal Terms</a> above
              </label>
            </div>

            <div class="form-btn">
              <button class="btn" id="sub-prev-btn" type="button">Back</button>
              <button class="subscribe-btn button" id="submit-btn" type="submit">
                SUBSCRIBE
              </button>
            </div>
          </div>
        </form>
      </div>

      <div class="image">
        <div class="carousel-track">
          <div class="slide">
            <img src="assests/images/car8.jpg" alt="" />
            <div class="slide-text">
              <h2>Start Your Fitness Journey</h2>
              <p>Join Society Fit and train smarter every day</p>
            </div>
          </div>
          <div class="slide">
            <img src="assests/images/car7.jpg" alt="" />
            <div class="slide-text">
              <h2>Become Stronger Today</h2>
              <p>Your transformation starts with one step</p>
            </div>
          </div>
          <div class="slide">
            <img src="assests/images/car11.jpg" alt="" />
            <div class="slide-text">
              <h2>Train. Transform. Repeat.</h2>
              <p>Sign up and unlock your full potential</p>
            </div>
          </div>
        </div>
      </div>
      <div id="loading"></div>
      <div id="pop-up"></div>
    </div>

    <script type="module" src="js/sign-up-page.js"></script>
    <script src="js/payment-methods.js"></script>
    <script src="components/loading.js"></script>
    <script src="js/carousel.js"></script>
    <script type="module" src="components/subcriptionCards.js"></script>

    <script>
      /**
       * updateRecurringUI — called when the toggle changes.
       * Updates the card style, badge text, receipt row, and hidden field.
       */
      function updateRecurringUI(isOn) {
        const card   = document.getElementById('recurringCard');
        const badge  = document.getElementById('recurringBadge');
        const mirror = document.getElementById('hidden_recurring');
        const recVal = document.getElementById('receiptRecurringVal');

        if (isOn) {
          card.classList.add('is-on');
          badge.classList.remove('off');
          badge.innerHTML = '<span class="recurring-badge-dot"></span> Auto-renew ON — you can turn this off anytime from your account';
          if (mirror) mirror.value = '1';
          if (recVal) { recVal.textContent = 'Enabled ✓'; recVal.style.color = '#2e7d32'; }
        } else {
          card.classList.remove('is-on');
          badge.classList.add('off');
          badge.innerHTML = '<span class="recurring-badge-dot"></span> Auto-renew OFF — you\'ll need to renew manually before expiry';
          if (mirror) mirror.value = '0';
          if (recVal) { recVal.textContent = 'Disabled'; recVal.style.color = '#999'; }
        }
      }

      // Sync recurring state when navigating to the receipt page
      document.getElementById('last-next-btn').addEventListener('click', function() {
        const isOn = document.getElementById('signup_recurring')?.checked ?? true;
        updateRecurringUI(isOn);
      });
    </script>
  </body>
</html>