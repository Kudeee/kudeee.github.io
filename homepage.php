<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/homepage.css" />
  <title>Home Page</title>
  <style>
    /* ── Shared scroll area ── */
    .scroll-area {
      max-height: 420px;
      overflow-y: auto;
      padding-right: 4px;
      scrollbar-width: thin;
      scrollbar-color: #ff6b35 #f0f0f0;
    }
    .scroll-area::-webkit-scrollbar { width: 6px; }
    .scroll-area::-webkit-scrollbar-track { background: #f0f0f0; border-radius: 3px; }
    .scroll-area::-webkit-scrollbar-thumb { background: #ff6b35; border-radius: 3px; }

    /* ── Booked Trainers section ── */
    .trainers-section {
      background: #fff;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
    }

    .trainer-booking-item {
      display: grid;
      grid-template-columns: 64px 1fr auto auto;
      gap: 16px;
      padding: 16px;
      background: #f9f9f9;
      border-radius: 12px;
      margin-bottom: 12px;
      align-items: center;
      transition: background 0.2s, box-shadow 0.2s;
      border: 2px solid transparent;
    }
    .trainer-booking-item:last-child { margin-bottom: 0; }
    .trainer-booking-item:hover {
      background: #fff3ee;
      border-color: rgba(255,107,53,0.2);
      box-shadow: 0 3px 10px rgba(255, 107, 53, 0.08);
    }

    .trainer-avatar {
      width: 64px;
      height: 64px;
      border-radius: 12px;
      object-fit: cover;
      background: linear-gradient(135deg, #ff6b35, #ff8c5a);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      font-weight: 900;
      color: #fff;
      flex-shrink: 0;
      overflow: hidden;
    }
    .trainer-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .trainer-booking-details { min-width: 0; }

    .trainer-booking-name {
      font-weight: 700;
      font-size: 1rem;
      margin-bottom: 3px;
      color: #1a1a1a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .trainer-booking-meta {
      font-size: 0.83rem;
      color: #666;
      line-height: 1.6;
    }

    .trainer-booking-badges {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      margin-top: 5px;
    }

    .tb-badge {
      font-size: 0.72rem;
      padding: 3px 8px;
      border-radius: 10px;
      font-weight: 600;
      display: inline-block;
    }
    .tb-badge-specialty { background: #fff3e0; color: #b35c00; }
    .tb-badge-focus     { background: #e3f2fd; color: #1255a0; }
    .tb-badge-recurring { background: #e8f5e9; color: #256029; }
    .tb-badge-today     { background: #ffebee; color: #b71c1c; }

    .trainer-booking-price {
      text-align: right;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
    }
    .trainer-price-value {
      font-size: 1.2rem;
      font-weight: 900;
      color: #ff6b35;
      white-space: nowrap;
    }
    .trainer-price-label {
      font-size: 0.75rem;
      color: #999;
    }

    .trainer-actions {
      display: flex;
      gap: 8px;
      flex-shrink: 0;
    }

    .tb-action-btn {
      border: none;
      border-radius: 8px;
      padding: 7px 13px;
      font-size: 0.78rem;
      font-weight: 700;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      transition: all 0.2s;
      white-space: nowrap;
    }
    .tb-action-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .tb-btn-recurring {
      background: #e8f5e9;
      color: #256029;
      border: 1.5px solid #a5d6a7;
    }
    .tb-btn-recurring:hover:not(:disabled) {
      background: #256029;
      color: #fff;
    }
    .tb-btn-unrecurring {
      background: #fff3e0;
      color: #b35c00;
      border: 1.5px solid #ffcc80;
    }
    .tb-btn-unrecurring:hover:not(:disabled) {
      background: #b35c00;
      color: #fff;
    }
    .tb-btn-cancel {
      background: #ffebee;
      color: #b71c1c;
      border: 1.5px solid #ef9a9a;
    }
    .tb-btn-cancel:hover:not(:disabled) {
      background: #b71c1c;
      color: #fff;
    }

    /* Confirm cancel mini-modal */
    .tb-cancel-confirm {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      backdrop-filter: blur(3px);
      z-index: 3000;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    .tb-cancel-confirm.open { display: flex; }
    .tb-cancel-box {
      background: #fff;
      border-radius: 16px;
      padding: 28px 28px 24px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
      animation: modalIn 0.22s ease;
    }
    .tb-cancel-box h3 {
      font-size: 1.15rem;
      font-weight: 900;
      text-transform: uppercase;
      margin: 0 0 8px;
      color: #1a1a1a;
    }
    .tb-cancel-box p {
      font-size: 0.9rem;
      color: #666;
      margin: 0 0 22px;
      line-height: 1.6;
    }
    .tb-cancel-box .warn {
      font-size: 0.82rem;
      color: #b71c1c;
      background: #ffebee;
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 20px;
    }
    .tb-cancel-actions {
      display: flex;
      gap: 10px;
    }
    .tb-cancel-actions button {
      flex: 1;
      height: 42px;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.88rem;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.2s;
    }
    .tb-cancel-actions .keep { background: #f0f0f0; color: #555; }
    .tb-cancel-actions .keep:hover { background: #e0e0e0; }
    .tb-cancel-actions .confirm-cancel { background: #b71c1c; color: #fff; }
    .tb-cancel-actions .confirm-cancel:hover { background: #8b1212; }

    .trainers-empty, .events-empty {
      text-align: center;
      padding: 40px 20px;
      color: #999;
    }
    .trainers-empty-icon, .events-empty-icon {
      font-size: 3rem;
      margin-bottom: 10px;
    }
    .trainers-empty p, .events-empty p { font-size: 0.95rem; }

    .trainers-loading, .events-loading {
      text-align: center;
      padding: 30px;
      color: #bbb;
      font-size: 0.95rem;
    }

    /* ── Events section ── */
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
    .events-scroll-area::-webkit-scrollbar { width: 6px; }
    .events-scroll-area::-webkit-scrollbar-track { background: #f0f0f0; border-radius: 3px; }
    .events-scroll-area::-webkit-scrollbar-thumb { background: #ff6b35; border-radius: 3px; }

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
    .event-item:last-child { margin-bottom: 0; }
    .event-item:hover { background: #fff3ee; box-shadow: 0 3px 10px rgba(255, 107, 53, 0.1); }

    .event-date {
      background: #ff6b35;
      color: #fff;
      padding: 15px;
      border-radius: 10px;
      text-align: center;
      min-width: 70px;
      flex-shrink: 0;
    }
    .event-day   { font-size: 2rem; font-weight: 900; line-height: 1; }
    .event-month { font-size: 0.8rem; text-transform: uppercase; margin-top: 2px; }

    .event-details { flex: 1; min-width: 0; }
    .event-title { font-weight: 700; font-size: 1rem; margin-bottom: 4px; }
    .event-meta  { color: #666; font-size: 0.85rem; line-height: 1.5; }

    .event-badge {
      font-size: 0.72rem; padding: 3px 8px; border-radius: 10px;
      font-weight: 600; display: inline-block; margin-top: 4px;
    }
    .badge-registered { background: #e8f5e9; color: #2e7d32; }
    .badge-members    { background: #fff3e0; color: #f57c00; }
    .badge-free       { background: #e3f2fd; color: #1565c0; }
    .badge-paid       { background: #f3e5f5; color: #6a1b9a; }

    .events-empty { text-align: center; padding: 40px 20px; color: #999; }
    .events-empty-icon { font-size: 3rem; margin-bottom: 10px; }
    .events-empty p { font-size: 0.95rem; }
    .events-loading { text-align: center; padding: 30px; color: #bbb; font-size: 0.95rem; }

    .event-item .btn { flex-shrink: 0; padding: 9px 18px; font-size: 0.82rem; }

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
    .booking-modal-overlay.open { display: flex; }

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
      from { opacity: 0; transform: translateY(-16px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .booking-modal-box h2 {
      font-size: 1.4rem; font-weight: 900; text-transform: uppercase;
      margin-bottom: 20px; color: #1a1a1a; text-align: left;
    }

    .modal-detail-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
      background: #f9f9f9; border-radius: 12px; padding: 16px; margin-bottom: 20px;
    }
    .modal-detail-item .label { font-size: 0.75rem; color: #999; text-transform: uppercase; font-weight: 600; margin-bottom: 3px; }
    .modal-detail-item .value { font-size: 0.95rem; font-weight: 700; color: #1a1a1a; }

    .modal-payment-title { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: #666; margin-bottom: 12px; letter-spacing: 0.4px; }

    .modal-payment-option {
      display: flex; align-items: center; gap: 10px; padding: 11px 14px;
      border: 2px solid #e5e5e5; border-radius: 10px; margin-bottom: 8px;
      cursor: pointer; transition: border-color 0.2s, background 0.2s;
      font-weight: 600; font-size: 0.9rem;
    }
    .modal-payment-option:hover { border-color: #ff6b35; }
    .modal-payment-option input[type="radio"] { accent-color: #ff6b35; width: 16px; height: 16px; flex-shrink: 0; }
    .modal-payment-option:has(input:checked) { border-color: #ff6b35; background: #fff7f2; }
    .modal-payment-option img { height: 22px; width: auto; }

    .modal-actions { display: flex; gap: 12px; margin-top: 22px; }
    .modal-actions button {
      flex: 1; height: 44px; border-radius: 10px; font-weight: 700;
      font-size: 0.9rem; text-transform: uppercase; cursor: pointer;
      border: none; transition: all 0.2s;
    }
    .modal-btn-cancel { background: #f0f0f0; color: #666; }
    .modal-btn-cancel:hover { background: #e0e0e0; }
    .modal-btn-confirm {
      background: linear-gradient(135deg, #ff6b35, #ff8c5a);
      color: #fff; box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
    }
    .modal-btn-confirm:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(255, 107, 53, 0.4); }
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
        <button class="btn btn-secondary" onclick="location.href='cancel-membership.php'">Cancel Membership</button>
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
</body>

</html>