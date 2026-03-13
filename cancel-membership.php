<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/cancel-membership.css" />
    <title>Cancel Membership — Society Fitness</title>
  </head>
  <body>

    <header class="header">
      <a href="homepage.php">
        <img src="assests/logo/society-fit.png" alt="Society Fitness" />
      </a>
    </header>

    <div class="container">

      <!-- Step indicator -->
      <div class="steps">
        <div class="step active" id="step-dot-1">
          <div class="step-circle">1</div>
          <div class="step-label">Review</div>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step-dot-2">
          <div class="step-circle">2</div>
          <div class="step-label">Reason</div>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step-dot-3">
          <div class="step-circle">3</div>
          <div class="step-label">Confirm</div>
        </div>
      </div>

      <!-- ── STEP 1: Review membership ── -->
      <div class="card" id="step-1">
        <div class="warning-banner">
          <div class="warning-icon">⚠</div>
          <div>
            <div class="warning-title">You are about to cancel your membership</div>
            <div class="warning-sub">Your access will end at the current billing period.</div>
          </div>
        </div>

        <h2 class="card-title">Your Current Membership</h2>

        <div class="membership-summary" id="membershipSummary">
          <div class="summary-row">
            <span class="summary-label">Member</span>
            <span class="summary-value" id="sumName">Loading…</span>
          </div>
          <div class="summary-row">
            <span class="summary-label">Plan</span>
            <span class="summary-value" id="sumPlan">—</span>
          </div>
          <div class="summary-row">
            <span class="summary-label">Billing</span>
            <span class="summary-value" id="sumBilling">—</span>
          </div>
          <div class="summary-row">
            <span class="summary-label">Access Until</span>
            <span class="summary-value" id="sumExpiry">—</span>
          </div>
        </div>

        <div class="perks-lost">
          <div class="perks-title">What you'll lose access to:</div>
          <ul class="perks-list" id="perksList">
            <li>24/7 gym access</li>
            <li>Group fitness classes</li>
            <li>Personal training sessions</li>
            <li>Locker room &amp; facilities</li>
            <li>Member-only events &amp; discounts</li>
          </ul>
        </div>

        <div class="card-actions">
          <a href="homepage.php" class="btn btn-secondary">← Keep Membership</a>
          <button class="btn btn-danger" onclick="goStep(2)">Continue to Cancel →</button>
        </div>
      </div>

      <!-- ── STEP 2: Reason ── -->
      <div class="card hidden" id="step-2">
        <h2 class="card-title">Why are you leaving?</h2>
        <p class="card-sub">Your feedback helps us improve. Select a reason below.</p>

        <div class="reason-list">
          <label class="reason-option">
            <input type="radio" name="cancel_reason" value="too_expensive" />
            <div class="reason-body">
              <div class="reason-icon"></div>
              <div>
                <div class="reason-label">Too expensive</div>
                <div class="reason-desc">The cost doesn't fit my current budget.</div>
              </div>
            </div>
          </label>
          <label class="reason-option">
            <input type="radio" name="cancel_reason" value="not_using" />
            <div class="reason-body">
              <div class="reason-icon"></div>
              <div>
                <div class="reason-label">Not using it enough</div>
                <div class="reason-desc">I haven't been visiting as often as I planned.</div>
              </div>
            </div>
          </label>
          <label class="reason-option">
            <input type="radio" name="cancel_reason" value="moving" />
            <div class="reason-body">
              <div class="reason-icon"></div>
              <div>
                <div class="reason-label">Moving / relocating</div>
                <div class="reason-desc">I'm moving somewhere the gym isn't accessible.</div>
              </div>
            </div>
          </label>
          <label class="reason-option">
            <input type="radio" name="cancel_reason" value="health" />
            <div class="reason-body">
              <div class="reason-icon"></div>
              <div>
                <div class="reason-label">Health reasons</div>
                <div class="reason-desc">A medical condition is preventing me from going.</div>
              </div>
            </div>
          </label>
          <label class="reason-option">
            <input type="radio" name="cancel_reason" value="switching" />
            <div class="reason-body">
              <div class="reason-icon"></div>
              <div>
                <div class="reason-label">Switching to another gym</div>
                <div class="reason-desc">I found another facility that suits me better.</div>
              </div>
            </div>
          </label>
          <label class="reason-option">
            <input type="radio" name="cancel_reason" value="other" />
            <div class="reason-body">
              <div class="reason-icon"></div>
              <div>
                <div class="reason-label">Other</div>
                <div class="reason-desc">Something else (please specify below).</div>
              </div>
            </div>
          </label>
        </div>

        <div class="other-reason-wrap hidden" id="otherReasonWrap">
          <textarea
            id="otherReasonText"
            placeholder="Tell us more…"
            rows="3"
          ></textarea>
        </div>

        <div class="card-actions">
          <button class="btn btn-secondary" onclick="goStep(1)">← Back</button>
          <button class="btn btn-danger" onclick="goStep(3)">Next →</button>
        </div>
      </div>

      <!-- ── STEP 3: Final confirmation ── -->
      <div class="card hidden" id="step-3">
        <div class="confirm-icon">🚫</div>
        <h2 class="card-title" style="text-align:center;">Final Confirmation</h2>
        <p class="card-sub" style="text-align:center;">
          This action <strong>cannot be undone</strong>. Your membership will be cancelled
          and access will end at the close of your current billing period.
        </p>

        <div class="confirm-checklist">
          <label class="check-item">
            <input type="checkbox" id="chk1" />
            I understand my access will end on <strong id="confirmExpiry">—</strong>.
          </label>
          <label class="check-item">
            <input type="checkbox" id="chk2" />
            I understand this action cannot be reversed.
          </label>
          <label class="check-item">
            <input type="checkbox" id="chk3" />
            I have read and agree to the cancellation policy.
          </label>
        </div>

        <div class="card-actions" style="margin-top:28px;">
          <button class="btn btn-secondary" onclick="goStep(2)">← Back</button>
          <button class="btn btn-danger" id="finalCancelBtn" onclick="submitCancellation()" disabled>
            Cancel My Membership
          </button>
        </div>

        <p class="change-mind">
          Changed your mind? <a href="homepage.php">Return to homepage</a> or
          <a href="payment.php?type=renew">renew instead</a>.
        </p>
      </div>

      <!-- ── STEP 4: Success ── -->
      <div class="card hidden" id="step-4">
        <div class="success-icon">✓</div>
        <h2 class="card-title" style="text-align:center;">Membership Cancelled</h2>
        <p class="card-sub" style="text-align:center;">
          We're sorry to see you go. Your membership has been cancelled and you'll
          retain access until <strong id="successExpiry">—</strong>.
        </p>

        <div class="success-info">
          <p>A confirmation will be noted on your account. You are welcome to rejoin at any time.</p>
        </div>

        <div style="display:flex;justify-content:center;gap:14px;margin-top:30px;">
          <a href="homepage.php" class="btn btn-secondary">Go to Homepage</a>
          <a href="payment.php?type=change&plan=BASIC%20PLAN&billing=monthly" class="btn" style="background:#ff6b35;color:#fff;">Rejoin Now</a>
        </div>
      </div>

    </div><!-- /.container -->

    <div id="loading"></div>

    <script src="components/loading.js"></script>
    <script src="js/cancel-membership.js"></script>
  </body>
</html>
