<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/homepage.css" />
  <title>Home Page</title>
  <style>
    /* ── Events section overrides ── */
    .events-tabs {
      display: flex;
      gap: 20px;
      border-bottom: 2px solid #f5f5f5;
      margin-bottom: 25px;
    }

    .events-scroll-area {
      max-height: 420px;
      overflow-y: auto;
      padding-right: 4px;
      scrollbar-width: thin;
      scrollbar-color: #ff6b35 #f0f0f0;
    }

    .events-scroll-area::-webkit-scrollbar {
      width: 6px;
    }

    .events-scroll-area::-webkit-scrollbar-track {
      background: #f0f0f0;
      border-radius: 3px;
    }

    .events-scroll-area::-webkit-scrollbar-thumb {
      background: #ff6b35;
      border-radius: 3px;
    }

    .event-item {
      display: flex;
      gap: 20px;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 10px;
      margin-bottom: 12px;
      align-items: center;
      transition: all 0.2s;
    }

    .event-item:last-child {
      margin-bottom: 0;
    }

    .event-item:hover {
      background: #fff3ee;
      box-shadow: 0 3px 10px rgba(255, 107, 53, 0.1);
    }

    .event-date {
      background: #ff6b35;
      color: #fff;
      padding: 15px;
      border-radius: 10px;
      text-align: center;
      min-width: 70px;
      flex-shrink: 0;
    }

    .event-day {
      font-size: 2rem;
      font-weight: 900;
      line-height: 1;
    }

    .event-month {
      font-size: 0.8rem;
      text-transform: uppercase;
      margin-top: 2px;
    }

    .event-details {
      flex: 1;
      min-width: 0;
    }

    .event-title {
      font-weight: 700;
      font-size: 1rem;
      margin-bottom: 4px;
    }

    .event-meta {
      color: #666;
      font-size: 0.85rem;
      line-height: 1.5;
    }

    .event-badge {
      font-size: 0.72rem;
      padding: 3px 8px;
      border-radius: 10px;
      font-weight: 600;
      display: inline-block;
      margin-top: 4px;
    }

    .badge-registered {
      background: #e8f5e9;
      color: #2e7d32;
    }

    .badge-members {
      background: #fff3e0;
      color: #f57c00;
    }

    .badge-free {
      background: #e3f2fd;
      color: #1565c0;
    }

    .badge-paid {
      background: #f3e5f5;
      color: #6a1b9a;
    }

    .events-empty {
      text-align: center;
      padding: 40px 20px;
      color: #999;
    }

    .events-empty-icon {
      font-size: 3rem;
      margin-bottom: 10px;
    }

    .events-empty p {
      font-size: 0.95rem;
    }

    .events-loading {
      text-align: center;
      padding: 30px;
      color: #bbb;
      font-size: 0.95rem;
    }

    .event-item .btn {
      flex-shrink: 0;
      padding: 9px 18px;
      font-size: 0.82rem;
    }

    /* ── Event Registration Modal ── */
    .booking-modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(4px);
      z-index: 2000;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .booking-modal-overlay.open {
      display: flex;
    }

    .booking-modal-box {
      background: #fff;
      border-radius: 18px;
      padding: 32px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 24px 64px rgba(0, 0, 0, 0.25);
      animation: modalIn 0.25s ease;
    }

    @keyframes modalIn {
      from {
        opacity: 0;
        transform: translateY(-16px) scale(0.97);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .booking-modal-box h2 {
      font-size: 1.4rem;
      font-weight: 900;
      text-transform: uppercase;
      margin-bottom: 20px;
      color: #1a1a1a;
      text-align: left;
    }

    .modal-detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      background: #f9f9f9;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 20px;
    }

    .modal-detail-item .label {
      font-size: 0.75rem;
      color: #999;
      text-transform: uppercase;
      font-weight: 600;
      margin-bottom: 3px;
    }

    .modal-detail-item .value {
      font-size: 0.95rem;
      font-weight: 700;
      color: #1a1a1a;
    }

    .modal-payment-title {
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #666;
      margin-bottom: 12px;
      letter-spacing: 0.4px;
    }

    .modal-payment-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 14px;
      border: 2px solid #e5e5e5;
      border-radius: 10px;
      margin-bottom: 8px;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .modal-payment-option:hover {
      border-color: #ff6b35;
    }

    .modal-payment-option input[type="radio"] {
      accent-color: #ff6b35;
      width: 16px;
      height: 16px;
      flex-shrink: 0;
    }

    .modal-payment-option:has(input:checked) {
      border-color: #ff6b35;
      background: #fff7f2;
    }

    .modal-payment-option img {
      height: 22px;
      width: auto;
    }

    .modal-actions {
      display: flex;
      gap: 12px;
      margin-top: 22px;
    }

    .modal-actions button {
      flex: 1;
      height: 44px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.9rem;
      text-transform: uppercase;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
    }

    .modal-btn-cancel {
      background: #f0f0f0;
      color: #666;
    }

    .modal-btn-cancel:hover {
      background: #e0e0e0;
    }

    .modal-btn-confirm {
      background: linear-gradient(135deg, #ff6b35, #ff8c5a);
      color: #fff;
      box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
    }

    .modal-btn-confirm:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(255, 107, 53, 0.4);
    }
  </style>
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
      </div>
      <div class="status-actions">
        <button class="btn" onclick="location.href='payment.php?type=renew'">Renew Now</button>
        <button class="btn btn-outline" id="upgradeBtn">Upgrade Plan</button>
        <button class="btn btn-secondary" onclick="location.href='cancel-membership.php'">
          Cancel Membership
        </button>
      </div>
    </div>

    <!-- NEXT CLASS -->
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

  <!-- ── Event Registration Modal ─────────────────────────────────────────── -->
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

      <!-- Payment section (hidden for free events) -->
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
</body>

</html>