<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/login-page.css" />
    <title>Reset Password — Society Fitness</title>
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

      .invalid-box {
        background: #fff3e0;
        border: 2px solid #ffe0b2;
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 22px;
        color: #e65100;
        font-size: 0.9rem;
        line-height: 1.7;
      }

      .info-text {
        color: #777;
        font-size: 0.88rem;
        line-height: 1.6;
        margin-bottom: 20px;
      }

      .password-wrap {
        position: relative;
      }
      .password-wrap input {
        padding-right: 44px !important;
        width: 100%;
        height: 50px;
        padding: 0 44px 0 12px;
        border-radius: 8px;
        border: 1px solid #ddd;
        margin-top: 7px;
        margin-bottom: 6px;
        outline: none;
        font-size: 16px;
        box-sizing: border-box;
      }
      .password-wrap input:focus { border-color: #ff6a2a; }

      .toggle-pw {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #aaa;
        font-size: 1.1rem;
        padding: 0;
        line-height: 1;
        margin: 0;
        box-shadow: none;
        width: auto;
        height: auto;
      }
      .toggle-pw:hover { color: #ff6a2a; transform: translateY(-50%); box-shadow: none; }

      .strength-bar {
        height: 4px;
        border-radius: 2px;
        background: #eee;
        margin-bottom: 12px;
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
        margin-top: -8px;
        margin-bottom: 12px;
        color: #aaa;
        display: block;
      }

      .req-list {
        list-style: none;
        padding: 0;
        margin: 0 0 18px;
        font-size: 0.82rem;
        color: #aaa;
        line-height: 2;
      }
      .req-list li::before { content: '○ '; }
      .req-list li.met { color: #2e7d32; }
      .req-list li.met::before { content: '✓ '; }
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

        <!-- ── STEP: Loading / Validating token ──────────────────── -->
        <div class="step active" id="step-loading">
          <p style="color:#999;font-size:0.95rem;">Validating your reset link…</p>
        </div>

        <!-- ── STEP: Invalid / Expired token ─────────────────────── -->
        <div class="step" id="step-invalid">
          <a href="login-page.php" class="back-link">← Back to Login</a>
          <h1>Link Expired</h1>
          <div class="invalid-box">
            ⚠ This password reset link is <strong>invalid or has expired</strong>.
            Reset links are only valid for 1 hour.
          </div>
          <p class="info-text">Please request a new reset link below.</p>
          <div class="form-btn">
            <button onclick="window.location.href='forgot-password.php'">Request New Link</button>
          </div>
        </div>

        <!-- ── STEP: Enter new password form ─────────────────────── -->
        <div class="step" id="step-form">
          <a href="login-page.php" class="back-link">← Back to Login</a>
          <h1>Reset Password</h1>
          <p class="info-text">
            Resetting password for <strong id="masked-email"></strong>.
            Choose a strong password you haven't used before.
          </p>

          <div class="error-box" id="reset-error"></div>

          <form id="resetForm">
            <input type="hidden" id="reset-token" name="token" />

            <label for="new-password">New Password</label>
            <div class="password-wrap">
              <input
                type="password"
                id="new-password"
                name="password"
                placeholder="At least 8 characters"
                autocomplete="new-password"
                required
              />
              <button type="button" class="toggle-pw" onclick="togglePw('new-password', this)">👁</button>
            </div>

            <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
            <span class="strength-label" id="strength-label">Enter a password</span>

            <ul class="req-list" id="req-list">
              <li id="req-len">At least 8 characters</li>
              <li id="req-upper">One uppercase letter</li>
              <li id="req-lower">One lowercase letter</li>
              <li id="req-num">One number</li>
            </ul>

            <label for="confirm-password">Confirm New Password</label>
            <div class="password-wrap">
              <input
                type="password"
                id="confirm-password"
                name="confirm_password"
                placeholder="Re-enter your password"
                autocomplete="new-password"
                required
              />
              <button type="button" class="toggle-pw" onclick="togglePw('confirm-password', this)">👁</button>
            </div>
            <span class="strength-label" id="match-label" style="margin-top:-6px;margin-bottom:14px;"></span>

            <div class="form-btn" style="margin-top:8px;">
              <button type="submit" id="resetBtn">Reset Password</button>
            </div>
          </form>
        </div>

        <!-- ── STEP: Success ──────────────────────────────────────── -->
        <div class="step" id="step-success">
          <h1>Password Updated! 🎉</h1>
          <div class="success-box">
            ✓ Your password has been reset successfully.
            You can now log in with your new password.
          </div>
          <div class="form-btn">
            <button onclick="window.location.href='login-page.php'">Go to Login</button>
          </div>
        </div>

      </div>

      <!-- Carousel panel -->
      <div class="image">
        <div class="carousel-track">
          <div class="slide">
            <img src="assests/images/ca2.jpg" alt="" />
            <div class="slide-text">
              <h2>New Start</h2>
              <p>Reset and get back to your fitness goals</p>
            </div>
          </div>
          <div class="slide">
            <img src="assests/images/car1.jpg" alt="" />
            <div class="slide-text">
              <h2>Stay Secure</h2>
              <p>Protect your account with a strong password</p>
            </div>
          </div>
          <div class="slide">
            <img src="assests/images/car3.jpg" alt="" />
            <div class="slide-text">
              <h2>Almost There</h2>
              <p>One more step to get back in action</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="loading"></div>
    <script src="components/loading.js"></script>
    <script src="js/carousel.js"></script>
    <script>
      // ── Helpers ──────────────────────────────────────────────────────
      function showStep(id) {
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
      }

      function showError(msg) {
        const el = document.getElementById('reset-error');
        el.textContent = msg;
        el.style.display = 'block';
      }
      function hideError() {
        document.getElementById('reset-error').style.display = 'none';
      }

      function togglePw(inputId, btn) {
        const inp = document.getElementById(inputId);
        const showing = inp.type === 'text';
        inp.type = showing ? 'password' : 'text';
        btn.textContent = showing ? '👁' : '🙈';
      }

      // ── Password strength ─────────────────────────────────────────────
      const pwInput = document.getElementById('new-password');
      const cfInput = document.getElementById('confirm-password');

      function checkStrength(pw) {
        let score = 0;
        if (pw.length >= 8)           score++;
        if (/[A-Z]/.test(pw))         score++;
        if (/[a-z]/.test(pw))         score++;
        if (/[0-9]/.test(pw))         score++;
        if (/[^A-Za-z0-9]/.test(pw))  score++;

        const fill   = document.getElementById('strength-fill');
        const label  = document.getElementById('strength-label');
        const widths = [0, 20, 40, 60, 80, 100];
        const colors = ['#eee','#f44336','#ff9800','#fdd835','#8bc34a','#4caf50'];
        const labels = ['','Weak','Fair','Moderate','Strong','Very Strong'];

        fill.style.width      = widths[score] + '%';
        fill.style.background = colors[score];
        label.textContent     = labels[score];
        label.style.color     = colors[score];

        // Requirements list
        setReq('req-len',   pw.length >= 8);
        setReq('req-upper', /[A-Z]/.test(pw));
        setReq('req-lower', /[a-z]/.test(pw));
        setReq('req-num',   /[0-9]/.test(pw));

        return score;
      }

      function setReq(id, met) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('met', met);
      }

      function checkMatch() {
        const ml = document.getElementById('match-label');
        if (!cfInput.value) { ml.textContent = ''; return; }
        if (pwInput.value === cfInput.value) {
          ml.textContent = '✓ Passwords match';
          ml.style.color = '#2e7d32';
        } else {
          ml.textContent = '✗ Passwords do not match';
          ml.style.color = '#c62828';
        }
      }

      pwInput.addEventListener('input', () => { checkStrength(pwInput.value); checkMatch(); });
      cfInput.addEventListener('input', checkMatch);

      // ── Get token from URL & validate ────────────────────────────────
      const urlParams = new URLSearchParams(window.location.search);
      const token     = urlParams.get('token') || '';

      async function validateToken() {
        if (!token) { showStep('step-invalid'); return; }

        try {
          const res  = await fetch('api/auth/validate-reset-token.php?token=' + encodeURIComponent(token));
          const data = await res.json();

          if (data.success) {
            document.getElementById('reset-token').value      = token;
            document.getElementById('masked-email').textContent = data.email || '';
            showStep('step-form');
          } else {
            showStep('step-invalid');
          }
        } catch (err) {
          showStep('step-invalid');
        }
      }

      validateToken();

      // ── Form submit ───────────────────────────────────────────────────
      document.getElementById('resetForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        hideError();

        const password        = pwInput.value;
        const confirmPassword = cfInput.value;

        if (password.length < 8) {
          showError('Password must be at least 8 characters.'); return;
        }
        if (password !== confirmPassword) {
          showError('Passwords do not match.'); return;
        }
        if (checkStrength(password) < 2) {
          showError('Please choose a stronger password.'); return;
        }

        const btn = document.getElementById('resetBtn');
        btn.disabled    = true;
        btn.textContent = 'Resetting…';

        try {
          const res  = await fetch('api/auth/reset-password.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ token, password, confirm_password: confirmPassword }),
          });
          const data = await res.json();

          btn.disabled    = false;
          btn.textContent = 'Reset Password';

          if (data.success) {
            showStep('step-success');
          } else {
            showError(data.message || 'Failed to reset password. Please try again.');
          }
        } catch (err) {
          btn.disabled    = false;
          btn.textContent = 'Reset Password';
          showError('Network error. Please try again.');
        }
      });
    </script>
  </body>
</html>