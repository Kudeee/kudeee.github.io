<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/homepage.css" />
  <title>Home Page</title>
</head>

<body>
  <header class="header header-js"></header>
  <div class="container">

    <!-- STATUS SNAPSHOT -->
    <div class="status-section">
      <div class="status-info">
        <h2 id="welcomeHeading">Welcome Back!</h2>
        <div class="status-badges">
          <span class="badge badge-active">✓ Active</span>
          <span class="badge badge-premium" id="planBadge">Loading…</span>
        </div>
        <div class="status-details">
          <div class="status-item">
            <div>
              <div class="status-label">Next Billing</div>
              <div class="status-value" id="nextBilling">—</div>
            </div>
          </div>
          <div class="status-item">
            <div>
              <div class="status-label">Days Remaining</div>
              <div class="status-value" id="daysRemaining">—</div>
            </div>
          </div>
          <div class="status-item">
            <div>
              <div class="status-label">Plan</div>
              <div class="status-value" id="planName">—</div>
            </div>
          </div>
          <div class="status-item">
            <div>
              <div class="status-label">Streak</div>
              <div class="status-value">7 Days</div>
            </div>
          </div>
        </div>

        <!-- ── Auto-Renew Toggle ── -->
        <div class="auto-renew-row" id="autoRenewRow" style="display:none;">
          <div class="auto-renew-info">
            <div class="ar-label">Auto-Renew</div>
            <div class="ar-value on" id="arValueText">Enabled — renews automatically</div>
            <div class="ar-saving" id="arSaving"></div>
          </div>
          <label class="ar-switch" id="arSwitchLabel" title="Toggle auto-renew">
            <input
              type="checkbox"
              id="autoRenewToggle"
              checked
              onchange="handleAutoRenewChange(this.checked)"
            />
            <span class="ar-slider"></span>
          </label>
        </div>
        <!-- ── /Auto-Renew Toggle ── -->

      </div>
      <div class="status-actions">
        <button class="btn" onclick="location.href='payment.php?type=renew'">Renew Now</button>
        <button class="btn btn-outline" id="upgradeBtn">Upgrade Plan</button>
        <button class="btn btn-secondary" onclick="location.href='cancel-membership.php'">Cancel Membership</button>
      </div>
    </div>

    <!-- NEXT CLASS (carousel) -->
    <div class="next-action-section">
      <div class="next-action-content">
        <div class="next-action-label">Your Next Class</div>
        <h2 class="next-action-title" id="nextClassName">Loading…</h2>
        <div class="class-info-grid">
          <div class="class-info-item">
            <div class="class-info-value" id="nextClassTime">—</div>
            <div class="class-info-label">Time</div>
          </div>
          <div class="class-info-item">
            <div class="class-info-value" id="nextClassDate">—</div>
            <div class="class-info-label">Date</div>
          </div>
          <div class="class-info-item">
            <div class="class-info-value" id="nextClassTrainer">—</div>
            <div class="class-info-label">Trainer</div>
          </div>
          <div class="class-info-item">
            <div class="class-info-value" id="nextClassDuration">—</div>
            <div class="class-info-label">Duration</div>
          </div>
        </div>
        <div class="action-buttons">
          <button class="btn btn-outline" id="CancelBooking">Cancel Booking</button>
          <button class="btn btn-secondary" onclick="window.location.href='book-class-page.php'">Book Another</button>
        </div>
      </div>

      <!-- Carousel navigation -->
      <div class="carousel-nav-strip" id="carouselNav" style="display:none;">
        <button class="carousel-arrow" id="carouselPrev" onclick="carouselPrev()" aria-label="Previous booking" disabled>
          &#8592;
        </button>
        <span class="carousel-counter" id="carouselCounter"></span>
        <button class="carousel-arrow" id="carouselNext" onclick="carouselNext()" aria-label="Next booking">
          &#8594;
        </button>
      </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="quick-actions-section">
      <h2 class="section-title">Quick Actions</h2>
      <div class="quick-actions-grid">
        <a href="book-class-page.php" class="quick-action-card">
          <div class="quick-action-icon"><img src="assests/icons/calendar2-plus-fill.svg" alt="" /></div>
          <div class="quick-action-label">Book a Class</div>
        </a>
        <a href="schedule-page.php" class="quick-action-card">
          <div class="quick-action-icon"><img src="assests/icons/calendar-week-fill.svg" alt="" /></div>
          <div class="quick-action-label">View Schedule</div>
        </a>
        <a href="book-trainer-page.php" class="quick-action-card">
          <div class="quick-action-icon"><img src="assests/icons/person-fill.svg" alt="" /></div>
          <div class="quick-action-label">Book Trainer</div>
        </a>
        <a href="my-membership.php" class="quick-action-card">
          <div class="quick-action-icon"><img src="assests/icons/credit-card-fill.svg" alt="" /></div>
          <div class="quick-action-label">My Membership</div>
        </a>
        <a href="payments-page.php" class="quick-action-card">
          <div class="quick-action-icon"><img src="assests/icons/cash.svg" alt="" /></div>
          <div class="quick-action-label">Payments</div>
        </a>
      </div>
    </div>

    <!-- ── BOOKED TRAINERS SECTION ── -->
    <div class="trainers-section">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2 class="section-title" style="margin:0;">My Trainer Sessions</h2>
        <a href="book-trainer-page.php"
           style="font-size:0.85rem;font-weight:700;color:#ff6b35;text-decoration:none;text-transform:uppercase;letter-spacing:0.3px;">
          + Book a Trainer
        </a>
      </div>
      <div class="scroll-area" id="trainerBookingsScroll">
        <div class="trainers-loading">Loading your trainer sessions…</div>
      </div>
    </div>

    <!-- ── EVENTS SECTION ── -->
    <div class="events-section">
      <h2 class="section-title">Events</h2>

      <div class="events-tabs">
        <button class="tab active" id="tabMyEvents" onclick="switchTab('my')">My Events</button>
        <button class="tab" id="tabAllEvents" onclick="switchTab('all')">All Events</button>
      </div>

      <!-- My Events panel -->
      <div id="panelMyEvents">
        <div class="events-scroll-area" id="myEventsScroll">
          <div class="events-loading">Loading your events…</div>
        </div>
      </div>

      <!-- All Events panel -->
      <div id="panelAllEvents" style="display:none;">
        <div class="events-scroll-area" id="allEventsScroll">
          <div class="events-loading">Loading events…</div>
        </div>
      </div>
    </div>

  </div><!-- /.container -->

  <!-- ── Trainer Cancel Confirmation Modal ── -->
  <div class="tb-cancel-confirm" id="trainerCancelModal" onclick="if(event.target===this)closeTrainerCancelModal()">
    <div class="tb-cancel-box">
      <h3>Cancel Session?</h3>
      <p id="trainerCancelDesc">Are you sure you want to cancel this trainer session?</p>
      <div class="warn">⚠ Cancellations must be made at least 24 hours before the session.</div>
      <div class="tb-cancel-actions">
        <button class="keep" onclick="closeTrainerCancelModal()">Keep It</button>
        <button class="confirm-cancel" onclick="confirmTrainerCancel()">Yes, Cancel</button>
      </div>
    </div>
  </div>

  <!-- ── Event Registration Modal ── -->
  <div class="booking-modal-overlay" id="eventModal" onclick="if(event.target===this)closeEventModal()">
    <div class="booking-modal-box">
      <h2 id="eventModalTitle">Register for Event</h2>

      <div class="modal-detail-grid">
        <div class="modal-detail-item">
          <div class="label">Event</div>
          <div class="value" id="eventModalName">—</div>
        </div>
        <div class="modal-detail-item">
          <div class="label">Date</div>
          <div class="value" id="eventModalDate">—</div>
        </div>
        <div class="modal-detail-item">
          <div class="label">Location</div>
          <div class="value" id="eventModalLocation">—</div>
        </div>
        <div class="modal-detail-item">
          <div class="label">Fee</div>
          <div class="value" id="eventModalFee">—</div>
        </div>
      </div>

      <input type="hidden" id="eventModalId" />

      <div id="eventPaymentSection">
        <div class="modal-payment-title">Select Payment Method</div>
        <label class="modal-payment-option">
          <input type="radio" name="event_payment_method" value="gcash" />
          <img src="assests/icons/GCash.svg" alt="GCash" /> GCash
        </label>
        <label class="modal-payment-option">
          <input type="radio" name="event_payment_method" value="maya" />
          <img src="assests/icons/maya.svg" alt="Maya" /> Maya
        </label>
        <label class="modal-payment-option">
          <input type="radio" name="event_payment_method" value="gotyme" />
          <img src="assests/icons/GoTyme.svg" alt="GoTyme" /> GoTyme
        </label>
        <label class="modal-payment-option">
          <input type="radio" name="event_payment_method" value="card" />
          💳 Credit / Debit Card
        </label>
      </div>

      <div class="modal-actions">
        <button class="modal-btn-cancel" onclick="closeEventModal()">Cancel</button>
        <button class="modal-btn-confirm" onclick="submitEventRegistration()">Confirm Registration</button>
      </div>
    </div>
  </div>

  <div id="loading"></div>
  <div id="pop-up"></div>

  <script src="js/header.js"></script>
  <script src="components/loading.js"></script>
  <script type="module" src="js/homepage.js"></script>

  <script>
    /**
     * Auto-renew toggle handler on the homepage.
     * Called when the toggle is changed. Debounced to avoid spamming the API.
     */
    let _arDebounce = null;

    async function handleAutoRenewChange(isOn) {
      const switchLabel = document.getElementById('arSwitchLabel');
      const valueText   = document.getElementById('arValueText');
      const saving      = document.getElementById('arSaving');

      // Immediate UI update
      if (valueText) {
        valueText.textContent = isOn
          ? 'Enabled — renews automatically'
          : 'Disabled — expires without renewal';
        valueText.className = 'ar-value ' + (isOn ? 'on' : 'off');
      }
      if (saving) saving.textContent = 'Saving…';
      if (switchLabel) switchLabel.classList.add('loading');

      clearTimeout(_arDebounce);
      _arDebounce = setTimeout(async () => {
        try {
          const fd = new FormData();
          fd.append('recurring', isOn ? 1 : 0);

          const res    = await fetch('api/user/membership/toggle-recurring.php', { method: 'POST', body: fd });
          const result = await res.json();

          if (saving) saving.textContent = result.success ? (isOn ? '✓ Auto-renew enabled' : '✓ Auto-renew disabled') : '⚠ Could not save';
          setTimeout(() => { if (saving) saving.textContent = ''; }, 2800);

        } catch (err) {
          if (saving) saving.textContent = '⚠ Network error';
          setTimeout(() => { if (saving) saving.textContent = ''; }, 2800);
        } finally {
          if (switchLabel) switchLabel.classList.remove('loading');
        }
      }, 500);
    }

    /**
     * Called from homepage.js after member data is loaded,
     * to initialise the toggle with the correct state.
     */
    window.initAutoRenewToggle = function(isRecurring) {
      const row    = document.getElementById('autoRenewRow');
      const toggle = document.getElementById('autoRenewToggle');
      const text   = document.getElementById('arValueText');

      if (!row || !toggle) return;

      row.style.display = 'flex';
      toggle.checked    = !!isRecurring;

      if (text) {
        text.textContent  = isRecurring
          ? 'Enabled — renews automatically'
          : 'Disabled — expires without renewal';
        text.className    = 'ar-value ' + (isRecurring ? 'on' : 'off');
      }
    };
  </script>
  <script src="js/reschedule-trainer.js"></script>
</body>

</html>