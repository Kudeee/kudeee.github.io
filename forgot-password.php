<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/login-page.css" />
    <title>Forgot Password — Society Fitness</title>
    <style>
      .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #ff6a2a;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 28px;
        transition: color 0.2s;
      }
      .back-link:hover { color: #1d1d4f; }

      .step { display: none; }
      .step.active { display: block; }

      .success-box {
        background: #e8f5e9;
        border: 2px solid #a5d6a7;
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 22px;
        color: #2e7d32;
        font-size: 0.92rem;
        line-height: 1.6;
      }

      .error-box {
        background: #ffebee;
        border: 2px solid #ffcdd2;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 18px;
        color: #c62828;
        font-size: 0.9rem;
        display: none;
      }

      .info-text {
        color: #777;
        font-size: 0.88rem;
        line-height: 1.6;
        margin-bottom: 22px;
      }

      .dev-box {
        background: #fff8e1;
        border: 2px dashed #ffd54f;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 18px;
        font-size: 0.82rem;
        color: #5d4037;
        display: none;
        word-break: break-all;
        line-height: 1.7;
      }

      .password-wrap {
        position: relative;
      }
      .password-wrap input {
        padding-right: 44px !important;
      }
      .toggle-pw {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #aaa;
        font-size: 1rem;
        padding: 0;
        line-height: 1;
      }
      .toggle-pw:hover { color: #ff6a2a; }

      .strength-bar {
        height: 4px;
        border-radius: 2px;
        background: #eee;
        margin-top: 6px;
        overflow: hidden;
      }
      .strength-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 0.3s, background 0.3s;
        width: 0%;
      }
      .strength-label {
        font-size: 0.78rem;
        margin-top: 4px;
        color: #aaa;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="login-container">
        <div class="logo">
          <a href="index.php">
            <img src="assests/logo/society-fit.png" alt="Society Fitness" />
          </a>
        </div>

        <!-- ── STEP 1: Enter Email ─────────────────────────────── -->
        <div class="step active" id="step-email">
          <a href="login-page.php" class="back-link">← Back to Login</a>
          <h1>Forgot Password?</h1>
          <p class="info-text">
            Enter the email address linked to your account and we'll send you a link to reset your password.
          </p>

          <div class="error-box" id="email-error"></div>
          <div class="dev-box"   id="email-dev"></div>

          <form id="forgotForm">
            <label for="reset-email">Email Address</label>
            <input
              type="email"
              id="reset-email"
              name="email"
              placeholder="Enter your email"
              autocomplete="email"
              required
            />

            <div class="form-btn" style="margin-top:8px;">
              <button type="submit" id="sendBtn">Send Reset Link</button>
            </div>
          </form>

          <div class="dont-have-acc" style="margin-top:16px;">
            Remember your password? <a href="login-page.php">Log in</a>
          </div>
        </div>

        <!-- ── STEP 2: Email Sent Confirmation ───────────────────── -->
        <div class="step" id="step-sent">
          <a href="login-page.php" class="back-link">← Back to Login</a>
          <h1>Check Your Email</h1>

          <div class="success-box">
            ✓ We've sent a password reset link to <strong id="sent-email-display"></strong>.
            Check your inbox (and spam folder) — the link expires in <strong>1 hour</strong>.
          </div>

          <p class="info-text">
            Didn't receive it?
          </p>
          <div class="form-btn">
            <button type="button" id="resendBtn">Resend Reset Link</button>
          </div>

          <div class="dont-have-acc" style="margin-top:16px;">
            <a href="login-page.php">Back to Login</a>
          </div>
        </div>

      </div>

      <!-- Carousel panel (same as login) -->
      <div class="image">
        <div class="carousel-track">
          <div class="slide">
            <img src="assests/images/ca2.jpg" alt="" />
            <div class="slide-text">
              <h2>No Worries</h2>
              <p>We'll get you back on track</p>
            </div>
          </div>
          <div class="slide">
            <img src="assests/images/car1.jpg" alt="" />
            <div class="slide-text">
              <h2>Stay Strong</h2>
              <p>Your fitness journey continues</p>
            </div>
          </div>
          <div class="slide">
            <img src="assests/images/car3.jpg" alt="" />
            <div class="slide-text">
              <h2>Almost There</h2>
              <p>Reset your password and keep going</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="loading"></div>
    <div id="pop-up"></div>

    <script src="components/loading.js"></script>
    <script src="js/carousel.js"></script>
    <script>
      let lastEmail = '';

      function showStep(id) {
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
      }

      function showError(elId, msg) {
        const el = document.getElementById(elId);
        el.textContent = msg;
        el.style.display = 'block';
      }
      function hideError(elId) {
        const el = document.getElementById(elId);
        el.style.display = 'none';
      }

      async function submitForgot(email) {
        hideError('email-error');
        const btn = document.getElementById('sendBtn');
        btn.disabled    = true;
        btn.textContent = 'Sending…';

        try {
          const res  = await fetch('api/auth/forgot-password.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email }),
          });
          const data = await res.json();

          btn.disabled    = false;
          btn.textContent = 'Send Reset Link';

          if (data.success) {
            lastEmail = email;
            document.getElementById('sent-email-display').textContent = email;

            // Dev mode: show the reset link directly
            if (data.dev_reset_url) {
              const devEl = document.getElementById('email-dev');
              devEl.innerHTML = `
                <strong>⚙ Dev Mode</strong> — email sending ${data.mail_sent ? 'succeeded' : 'failed (use link below)'}:<br>
                <a href="${data.dev_reset_url}" style="color:#ff6a2a;">${data.dev_reset_url}</a>
              `;
              devEl.style.display = 'block';
            }

            showStep('step-sent');
          } else {
            showError('email-error', data.message || 'Something went wrong. Please try again.');
          }
        } catch (err) {
          btn.disabled    = false;
          btn.textContent = 'Send Reset Link';
          showError('email-error', 'Network error. Please check your connection and try again.');
        }
      }

      document.getElementById('forgotForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('reset-email').value.trim();
        if (!email) { showError('email-error', 'Please enter your email address.'); return; }
        submitForgot(email);
      });

      document.getElementById('resendBtn').addEventListener('click', function() {
        if (lastEmail) {
          showStep('step-email');
          document.getElementById('reset-email').value = lastEmail;
          submitForgot(lastEmail);
        } else {
          showStep('step-email');
        }
      });
    </script>
  </body>
</html>