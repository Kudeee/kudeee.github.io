<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/schedule-page.css" />
    <title>Class Schedule</title>
    <style>
      /* ── Week-grid overrides ─────────────────────────────────────────────── */
      .sg-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .sg-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
        font-size: 0.88rem;
      }

      .sg-table thead th {
        background: #1a1a1a;
        color: #fff;
        padding: 14px 10px;
        text-align: center;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
      }

      .sg-table thead th.sg-today {
        background: linear-gradient(135deg, #ff6b35, #ff8c5a);
      }

      .sg-time-col {
        width: 85px;
        min-width: 85px;
      }

      .sg-day-name { font-size: 0.78rem; opacity: 0.7; }
      .sg-day-num  { font-size: 1.3rem; font-weight: 900; }
      .sg-today-num {
        background: #ff6b35;
        color: #fff;
        width: 32px; height: 32px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
      }

      .sg-time-cell {
        background: #f9f9f9;
        color: #666;
        font-weight: 600;
        font-size: 0.78rem;
        text-align: center;
        padding: 10px 6px;
        white-space: nowrap;
        border-bottom: 1px solid #f0f0f0;
      }

      .sg-cell {
        background: #fff;
        border: 1px solid #f3f3f3;
        padding: 8px 6px;
        vertical-align: top;
        min-height: 60px;
        height: 60px;
      }

      .sg-cell.sg-has-class {
        background: linear-gradient(135deg, #ff6b35, #ff8c5a);
        color: #fff;
        cursor: pointer;
        transition: all 0.2s;
        border-radius: 0;
        padding: 8px;
      }

      .sg-cell.sg-has-class:hover {
        filter: brightness(1.08);
        transform: scale(1.04);
        z-index: 2;
        position: relative;
        box-shadow: 0 4px 14px rgba(255,107,53,0.35);
      }

      .sg-cell.sg-full {
        background: linear-gradient(135deg, #bdbdbd, #9e9e9e);
        cursor: not-allowed;
      }

      .sg-cell.sg-booked {
        background: linear-gradient(135deg, #43a047, #66bb6a);
        cursor: default;
      }

      .sg-class-name    { font-weight: 700; font-size: 0.82rem; line-height: 1.2; }
      .sg-class-trainer { font-size: 0.72rem; opacity: 0.85; margin-top: 2px; }
      .sg-class-spots   { font-size: 0.7rem; margin-top: 4px; font-weight: 700; color: #fff !important; }

      /* ── Week nav ───────────────────────────────────────────────────────── */
      .sg-week-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        margin-bottom: 16px;
        flex-wrap: wrap;
      }

      .sg-week-nav span {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a1a1a;
        min-width: 140px;
        text-align: center;
      }

      /* ── List view overrides ─────────────────────────────────────────────── */
      .class-card-booked {
        border-left: 4px solid #4caf50;
      }

      .class-specialty {
        font-size: 0.8rem;
        color: #ff6b35;
        font-weight: 600;
        margin-top: 4px;
      }

      .class-action {
        flex-shrink: 0;
      }

      .btn-booked {
        background: linear-gradient(135deg, #43a047, #66bb6a) !important;
        cursor: default !important;
      }

      .btn-full {
        background: #bdbdbd !important;
        cursor: not-allowed !important;
      }

      /* ── Loading / empty ─────────────────────────────────────────────────── */
      .sg-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 60px 20px;
        color: #999;
        font-size: 1rem;
      }

      .sg-spinner {
        width: 22px; height: 22px;
        border: 3px solid #e0e0e0;
        border-top-color: #ff6b35;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
      }

      @keyframes spin { to { transform: rotate(360deg); } }

      .sg-empty {
        text-align: center;
        padding: 60px 20px;
        color: #999;
      }

      /* ── Booking modal ───────────────────────────────────────────────────── */
      .booking-modal-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.55);
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
        box-shadow: 0 24px 64px rgba(0,0,0,0.25);
        animation: modalIn 0.25s ease;
      }

      @keyframes modalIn {
        from { opacity:0; transform: translateY(-16px) scale(0.97); }
        to   { opacity:1; transform: translateY(0) scale(1); }
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
        width: 16px; height: 16px;
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

      .modal-btn-cancel:hover { background: #e0e0e0; }

      .modal-btn-confirm {
        background: linear-gradient(135deg, #ff6b35, #ff8c5a);
        color: #fff;
        box-shadow: 0 4px 12px rgba(255,107,53,0.3);
      }

      .modal-btn-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(255,107,53,0.4);
      }

      /* ── Toast ───────────────────────────────────────────────────────────── */
      .schedule-toast {
        position: fixed;
        bottom: 28px; right: 28px;
        padding: 14px 22px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.9rem;
        color: #fff;
        box-shadow: 0 6px 24px rgba(0,0,0,0.18);
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.3s, transform 0.3s;
        z-index: 9999;
        pointer-events: none;
        max-width: 340px;
      }

      .schedule-toast.show { opacity: 1; transform: translateY(0); }
      .schedule-toast-success { background: #2e7d32; }
      .schedule-toast-error   { background: #c62828; }
      .schedule-toast-info    { background: #1565c0; }

      /* ── Responsive ──────────────────────────────────────────────────────── */
      @media (max-width: 1024px) {
        .sg-table { font-size: 0.8rem; }
      }

      @media (max-width: 768px) {
        .view-btn[data-view="grid"] { display: none; }
      }
    </style>
  </head>
  <body>
    <header class="header header-js"></header>

    <div class="container">
      <div class="page-header">
        <h1 class="page-title">Class Schedule</h1>
        <p class="page-subtitle">Browse and book your favourite classes</p>
      </div>

      <!-- Filters -->
      <div class="filter-section">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label" for="filterClass">Class Type</label>
            <select class="filter-select" id="filterClass">
              <option value="">All Classes</option>
              <option>HIIT</option>
              <option>Yoga</option>
              <option>Boxing</option>
              <option>CrossFit</option>
              <option>Spin</option>
              <option>Pilates</option>
              <option>Zumba</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="filterTrainer">Trainer</label>
            <select class="filter-select" id="filterTrainer">
              <option value="">All Trainers</option>
              <!-- Populated dynamically -->
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="filterTime">Time of Day</label>
            <select class="filter-select" id="filterTime">
              <option value="">All Times</option>
              <option value="morning">Morning (6AM–12PM)</option>
              <option value="afternoon">Afternoon (12PM–5PM)</option>
              <option value="evening">Evening (5PM–9PM)</option>
            </select>
          </div>
          <div class="view-toggle">
            <button class="view-btn active" data-view="grid" onclick="toggleView('grid')">📅 Week</button>
            <button class="view-btn"        data-view="list" onclick="toggleView('list')">📋 List</button>
          </div>
        </div>
      </div>

      <!-- Schedule content (injected by JS) -->
      <div id="scheduleContent">
        <div class="sg-loading"><div class="sg-spinner"></div> Loading schedule…</div>
      </div>

      <!-- Legend -->
      <div class="legend" id="scheduleLegend">
        <div class="legend-item">
          <div class="legend-color" style="background:linear-gradient(135deg,#ff6b35,#ff8c5a)"></div>
          <span>Available</span>
        </div>
        <div class="legend-item">
          <div class="legend-color" style="background:linear-gradient(135deg,#43a047,#66bb6a)"></div>
          <span>Already Booked</span>
        </div>
        <div class="legend-item">
          <div class="legend-color" style="background:linear-gradient(135deg,#bdbdbd,#9e9e9e)"></div>
          <span>Class Full</span>
        </div>
      </div>
    </div>

    <!-- ── Booking Modal ─────────────────────────────────────────────────────── -->
    <div class="booking-modal-overlay" id="bookingModal" onclick="if(event.target===this)closeBookingModal()">
      <div class="booking-modal-box">
        <h2 id="modalClassName">Book Class</h2>

        <div class="modal-detail-grid">
          <div class="modal-detail-item">
            <div class="label">Date</div>
            <div class="value" id="modalDate">—</div>
          </div>
          <div class="modal-detail-item">
            <div class="label">Time</div>
            <div class="value" id="modalTime">—</div>
          </div>
          <div class="modal-detail-item">
            <div class="label">Trainer</div>
            <div class="value" id="modalTrainer">—</div>
          </div>
          <div class="modal-detail-item">
            <div class="label">Fee</div>
            <div class="value">Free / Included</div>
          </div>
        </div>

        <!-- Hidden form fields -->
        <input type="hidden" id="modalScheduleId" />
        <input type="hidden" id="modalClassNameInput" />
        <input type="hidden" id="modalDateInput" />
        <input type="hidden" id="modalTimeInput" />

        <div class="modal-payment-title">Select Payment Method</div>

        <label class="modal-payment-option">
          <input type="radio" name="modal_payment_method" value="gcash" />
          <img src="assests/icons/GCash.svg" alt="GCash" /> GCash
        </label>
        <label class="modal-payment-option">
          <input type="radio" name="modal_payment_method" value="maya" />
          <img src="assests/icons/maya.svg" alt="Maya" /> Maya
        </label>
        <label class="modal-payment-option">
          <input type="radio" name="modal_payment_method" value="gotyme" />
          <img src="assests/icons/GoTyme.svg" alt="GoTyme" /> GoTyme
        </label>
        <label class="modal-payment-option">
          <input type="radio" name="modal_payment_method" value="card" />
          💳 Credit / Debit Card
        </label>

        <div class="modal-actions">
          <button class="modal-btn-cancel" onclick="closeBookingModal()">Cancel</button>
          <button class="modal-btn-confirm" onclick="submitBooking()">Confirm Booking</button>
        </div>
      </div>
    </div>

    <div id="loading"></div>

    <script src="components/loading.js"></script>
    <script src="js/header.js"></script>
    <script src="js/schedule-page.js"></script>
  </body>
</html>