<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/sign-up-page.css" />
    <title>Sign Up</title>
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
            <!-- Subscription card radios injected by subcriptionCards.js.
                 Each radio has: name="membership_plan" value="BASIC PLAN|PREMIUM PLAN|VIP PLAN"
                 and data-price / data-billing attributes. -->
            <div class="selection-cards"></div>

            <div class="form-btn">
              <button class="btn" id="second-last-prev-btn" type="button">Back</button>
              <button class="btn" id="second-last-next-btn" type="button">Next</button>
            </div>
          </div>

          <!-- ─── PAGE 4: Payment Method ────────────────────────── -->
          <div class="page" id="last-page" style="display: none">
            <!-- Payment fields injected by payment-methods.js.
                 Each field carries name="payment_method", name="card_number", etc. -->
            <div class="payment-method-js"></div>

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

            <label class="discount-label" for="discount_code">Apply Discount Code</label>
            <div class="discount-box">
              <input
                type="text"
                id="discount_code"
                name="discount_code"
                placeholder="Code"
              />
            </div>

            <!-- Hidden fields populated by JS when plan/billing are selected -->
            <input type="hidden" name="selected_plan"    id="hidden_selected_plan"    value="" />
            <input type="hidden" name="billing_cycle"    id="hidden_billing_cycle"    value="monthly" />
            <input type="hidden" name="plan_price"       id="hidden_plan_price"       value="" />

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
  </body>
</html>