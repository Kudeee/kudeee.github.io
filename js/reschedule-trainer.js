/**
 * reschedule-trainer.js
 * 
 * Handles the trainer session reschedule modal on the homepage.
 * Fetches trainer availability and lets the member pick a new date/time.
 * 
 * Usage: call openRescheduleModal(bookingId, trainerId, trainerName, currentDate, currentTime)
 * from anywhere in the page.
 */

(function () {
  'use strict';

  // ── Constants ───────────────────────────────────────────────────────────────
  const MONTH_NAMES = ['January','February','March','April','May','June',
                       'July','August','September','October','November','December'];
  const DAY_ABBREVS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

  // ── State ───────────────────────────────────────────────────────────────────
  let _bookingId    = null;
  let _trainerId    = null;
  let _trainerName  = '';
  let _calYear      = 0;
  let _calMonth     = 0;
  let _selectedDate = null;  // ISO string
  let _selectedTime = null;  // e.g. "10:00 AM"
  let _slotsCache   = {};    // key: ISO date → array of slot objects

  // ── Inject modal HTML ────────────────────────────────────────────────────────
  function injectModal() {
    if (document.getElementById('rescheduleModal')) return;

    const overlay = document.createElement('div');
    overlay.id        = 'rescheduleModal';
    overlay.className = 'rs-overlay';
    overlay.innerHTML = `
      <div class="rs-box" role="dialog" aria-modal="true" aria-labelledby="rsTitle">

        <!-- Header -->
        <div class="rs-header">
          <div>
            <h2 class="rs-title" id="rsTitle">Reschedule Session</h2>
            <p class="rs-subtitle" id="rsSubtitle">Choose a new date and time</p>
          </div>
          <button class="rs-close" onclick="closeRescheduleModal()" aria-label="Close">✕</button>
        </div>

        <!-- Current booking info banner -->
        <div class="rs-current-info" id="rsCurrentInfo"></div>

        <!-- Step tabs -->
        <div class="rs-steps">
          <div class="rs-step rs-step--active" id="rsStep1Tab">
            <span class="rs-step-num">1</span> Pick a Date
          </div>
          <div class="rs-step-line"></div>
          <div class="rs-step" id="rsStep2Tab">
            <span class="rs-step-num">2</span> Pick a Time
          </div>
          <div class="rs-step-line"></div>
          <div class="rs-step" id="rsStep3Tab">
            <span class="rs-step-num">3</span> Confirm
          </div>
        </div>

        <!-- Step 1: Calendar -->
        <div class="rs-panel" id="rsPanel1">
          <div class="rs-cal-nav">
            <button class="rs-cal-arrow" id="rsPrevMonth" onclick="rsCalPrev()">&#8592;</button>
            <span class="rs-cal-month-label" id="rsCalLabel">Loading…</span>
            <button class="rs-cal-arrow" id="rsNextMonth" onclick="rsCalNext()">&#8594;</button>
          </div>
          <div class="rs-cal-grid" id="rsCalGrid"></div>
          <div class="rs-panel-actions">
            <button class="rs-btn-ghost" onclick="closeRescheduleModal()">Cancel</button>
            <button class="rs-btn-primary" id="rsToStep2Btn" onclick="rsGoToStep(2)" disabled>
              Next: Choose Time →
            </button>
          </div>
        </div>

        <!-- Step 2: Time slots -->
        <div class="rs-panel" id="rsPanel2" style="display:none;">
          <div class="rs-selected-date-label" id="rsSelectedDateLabel"></div>
          <div class="rs-slots-loading" id="rsSlotsLoading" style="display:none;">
            <div class="rs-spinner"></div> Checking trainer availability…
          </div>
          <div class="rs-slots-grid" id="rsSlotsGrid"></div>
          <div class="rs-panel-actions">
            <button class="rs-btn-ghost" onclick="rsGoToStep(1)">← Back</button>
            <button class="rs-btn-primary" id="rsToStep3Btn" onclick="rsGoToStep(3)" disabled>
              Next: Confirm →
            </button>
          </div>
        </div>

        <!-- Step 3: Confirm -->
        <div class="rs-panel" id="rsPanel3" style="display:none;">
          <div class="rs-confirm-card">
            <div class="rs-confirm-icon">📅</div>
            <h3 class="rs-confirm-heading">Confirm Reschedule</h3>

            <div class="rs-confirm-details" id="rsConfirmDetails"></div>

            <div class="rs-confirm-warning">
              <span>⚠</span>
              <span>Reschedules must be made <strong>at least 24 hours</strong> before your current session.
              The trainer will be notified of the change.</span>
            </div>
          </div>
          <div class="rs-panel-actions">
            <button class="rs-btn-ghost" onclick="rsGoToStep(2)">← Back</button>
            <button class="rs-btn-confirm" id="rsConfirmBtn" onclick="rsSubmit()">
              ✓ Confirm Reschedule
            </button>
          </div>
        </div>

        <!-- Success state -->
        <div class="rs-panel" id="rsPanelSuccess" style="display:none;">
          <div class="rs-success">
            <div class="rs-success-icon">✓</div>
            <h3>Session Rescheduled!</h3>
            <p id="rsSuccessMsg">Your session has been moved.</p>
            <button class="rs-btn-primary" onclick="closeRescheduleModal()">Done</button>
          </div>
        </div>

      </div>
    `;

    // Close on backdrop click
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeRescheduleModal();
    });

    document.body.appendChild(overlay);
    injectStyles();
  }

  // ── Styles ───────────────────────────────────────────────────────────────────
  function injectStyles() {
    if (document.getElementById('rs-styles')) return;
    const style = document.createElement('style');
    style.id = 'rs-styles';
    style.textContent = `
      /* ── Overlay ── */
      .rs-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.58);
        backdrop-filter: blur(5px);
        z-index: 4000;
        justify-content: center;
        align-items: center;
        padding: 16px;
        animation: rsFadeIn 0.18s ease;
      }
      .rs-overlay.rs-open { display: flex; }

      @keyframes rsFadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
      }

      /* ── Box ── */
      .rs-box {
        background: #ffffff;
        border-radius: 20px;
        width: 100%;
        max-width: 520px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 32px 80px rgba(0,0,0,0.28);
        animation: rsSlideUp 0.25s cubic-bezier(.34,1.56,.64,1);
        scrollbar-width: thin;
        scrollbar-color: #ff6b35 #f0f0f0;
      }

      .rs-box::-webkit-scrollbar { width: 5px; }
      .rs-box::-webkit-scrollbar-track { background: #f0f0f0; border-radius: 3px; }
      .rs-box::-webkit-scrollbar-thumb { background: #ff6b35; border-radius: 3px; }

      @keyframes rsSlideUp {
        from { opacity: 0; transform: translateY(24px) scale(0.96); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
      }

      /* ── Header ── */
      .rs-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 28px 28px 0;
        gap: 12px;
      }

      .rs-title {
        font-size: 1.4rem;
        font-weight: 900;
        color: #1a1a1a;
        text-transform: uppercase;
        letter-spacing: -0.3px;
        margin: 0 0 4px;
      }

      .rs-subtitle {
        font-size: 0.85rem;
        color: #888;
        margin: 0;
      }

      .rs-close {
        background: #f5f5f5;
        border: none;
        border-radius: 50%;
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
        cursor: pointer;
        color: #666;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s, color 0.15s;
        box-shadow: none;
        padding: 0;
        line-height: 1;
        text-transform: none;
        letter-spacing: 0;
        font-weight: 400;
      }

      .rs-close:hover {
        background: #ffebee;
        color: #c62828;
        transform: none;
        box-shadow: none;
      }

      /* ── Current info banner ── */
      .rs-current-info {
        margin: 16px 28px 0;
        background: #fff7f2;
        border: 1.5px solid #ffcc99;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 0.85rem;
        color: #c05000;
        line-height: 1.5;
      }

      .rs-current-info strong { color: #1a1a1a; }

      /* ── Steps indicator ── */
      .rs-steps {
        display: flex;
        align-items: center;
        padding: 20px 28px 0;
        gap: 0;
      }

      .rs-step {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 0.78rem;
        font-weight: 700;
        color: #bbb;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
        transition: color 0.2s;
      }

      .rs-step.rs-step--active { color: #ff6b35; }
      .rs-step.rs-step--done   { color: #2e7d32; }

      .rs-step-num {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #e5e5e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 900;
        color: #aaa;
        flex-shrink: 0;
        transition: background 0.2s, color 0.2s;
      }

      .rs-step--active .rs-step-num {
        background: #ff6b35;
        color: #fff;
      }

      .rs-step--done .rs-step-num {
        background: #2e7d32;
        color: #fff;
      }

      .rs-step-line {
        flex: 1;
        height: 2px;
        background: #e5e5e5;
        margin: 0 8px;
        border-radius: 1px;
      }

      /* ── Panel ── */
      .rs-panel {
        padding: 20px 28px 28px;
      }

      /* ── Calendar ── */
      .rs-cal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
      }

      .rs-cal-month-label {
        font-size: 1rem;
        font-weight: 800;
        color: #1a1a1a;
        text-transform: uppercase;
        letter-spacing: 0.3px;
      }

      .rs-cal-arrow {
        background: #f5f5f5;
        border: none;
        border-radius: 8px;
        width: 34px;
        height: 34px;
        font-size: 1rem;
        cursor: pointer;
        color: #555;
        transition: background 0.15s, color 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: none;
        padding: 0;
        line-height: 1;
        text-transform: none;
        font-weight: 400;
        letter-spacing: 0;
      }

      .rs-cal-arrow:hover { background: #fff3e0; color: #ff6b35; transform: none; box-shadow: none; }

      .rs-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
      }

      .rs-cal-day-hdr {
        text-align: center;
        font-size: 0.68rem;
        font-weight: 700;
        color: #aaa;
        text-transform: uppercase;
        padding: 4px 0 6px;
      }

      .rs-cal-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.88rem;
        border: 2px solid transparent;
        transition: all 0.15s;
        background: #fff;
        color: #1a1a1a;
      }

      .rs-cal-day:hover:not(.rs-cal-day--disabled) {
        background: #fff3e0;
        border-color: #ff6b35;
        color: #ff6b35;
      }

      .rs-cal-day--selected {
        background: #ff6b35 !important;
        border-color: #ff6b35 !important;
        color: #fff !important;
      }

      .rs-cal-day--today {
        border-color: #ffcc99;
        background: #fff7f2;
      }

      .rs-cal-day--disabled {
        opacity: 0.25;
        cursor: not-allowed;
        background: #f5f5f5;
        color: #bbb;
      }

      .rs-cal-day--blank {
        background: transparent;
        cursor: default;
        border-color: transparent;
      }

      /* ── Selected date label ── */
      .rs-selected-date-label {
        font-size: 0.92rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 14px;
        padding: 10px 14px;
        background: #f9f9f9;
        border-radius: 10px;
        border-left: 3px solid #ff6b35;
      }

      /* ── Slots ── */
      .rs-slots-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 30px;
        color: #999;
        font-size: 0.9rem;
      }

      .rs-spinner {
        width: 18px;
        height: 18px;
        border: 3px solid #e5e5e5;
        border-top-color: #ff6b35;
        border-radius: 50%;
        animation: rsSpin 0.65s linear infinite;
        flex-shrink: 0;
      }

      @keyframes rsSpin { to { transform: rotate(360deg); } }

      .rs-slots-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 6px;
      }

      .rs-slot {
        padding: 11px 4px;
        border: 2px solid #e5e5e5;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 700;
        color: #1a1a1a;
        background: #fff;
        transition: all 0.15s;
        line-height: 1.3;
      }

      .rs-slot:hover:not(.rs-slot--unavailable) {
        border-color: #ff6b35;
        background: #fff3e0;
        color: #ff6b35;
      }

      .rs-slot--selected {
        background: #ff6b35 !important;
        border-color: #ff6b35 !important;
        color: #fff !important;
      }

      .rs-slot--unavailable {
        opacity: 0.35;
        cursor: not-allowed;
        background: #f5f5f5;
        color: #bbb;
        border-color: #eee;
      }

      .rs-slot-sub {
        font-size: 0.66rem;
        font-weight: 500;
        margin-top: 3px;
        opacity: 0.75;
      }

      .rs-slots-empty {
        text-align: center;
        padding: 30px 10px;
        color: #999;
        font-size: 0.88rem;
      }

      /* ── Confirm card ── */
      .rs-confirm-card {
        background: #f9f9f9;
        border-radius: 14px;
        padding: 24px;
        text-align: center;
        margin-bottom: 6px;
      }

      .rs-confirm-icon {
        font-size: 2.8rem;
        margin-bottom: 12px;
        line-height: 1;
      }

      .rs-confirm-heading {
        font-size: 1.1rem;
        font-weight: 900;
        color: #1a1a1a;
        text-transform: uppercase;
        margin: 0 0 18px;
      }

      .rs-confirm-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        text-align: left;
        margin-bottom: 18px;
      }

      .rs-confirm-detail-item .rs-cd-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #aaa;
        letter-spacing: 0.4px;
        margin-bottom: 3px;
      }

      .rs-confirm-detail-item .rs-cd-value {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1a1a1a;
      }

      .rs-confirm-detail-item .rs-cd-value.rs-cd-new {
        color: #ff6b35;
      }

      .rs-confirm-warning {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        background: #fff7ed;
        border: 1.5px solid #fed7aa;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 0.8rem;
        color: #92400e;
        text-align: left;
        line-height: 1.5;
      }

      .rs-confirm-warning span:first-child { flex-shrink: 0; }

      /* ── Success ── */
      .rs-success {
        text-align: center;
        padding: 20px 0 10px;
      }

      .rs-success-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #16a34a, #22c55e);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #fff;
        margin: 0 auto 16px;
      }

      .rs-success h3 {
        font-size: 1.3rem;
        font-weight: 900;
        color: #1a1a1a;
        text-transform: uppercase;
        margin: 0 0 8px;
      }

      .rs-success p {
        color: #666;
        font-size: 0.9rem;
        margin: 0 0 24px;
        line-height: 1.6;
      }

      /* ── Panel actions ── */
      .rs-panel-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
      }

      /* ── Buttons ── */
      .rs-btn-primary {
        flex: 1;
        height: 44px;
        background: linear-gradient(135deg, #ff6b35, #ff8c5a);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(255,107,53,0.3);
        padding: 0 16px;
        font-family: inherit;
      }

      .rs-btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(255,107,53,0.4);
      }

      .rs-btn-primary:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
      }

      .rs-btn-ghost {
        flex: 0 0 auto;
        height: 44px;
        background: #f3f4f6;
        color: #555;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        transition: background 0.15s;
        padding: 0 18px;
        font-family: inherit;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        box-shadow: none;
      }

      .rs-btn-ghost:hover { background: #e5e7eb; transform: none; box-shadow: none; }

      .rs-btn-confirm {
        flex: 1;
        height: 44px;
        background: linear-gradient(135deg, #1565c0, #1976d2);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(21,101,192,0.3);
        padding: 0 16px;
        font-family: inherit;
      }

      .rs-btn-confirm:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(21,101,192,0.4);
      }

      .rs-btn-confirm:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
      }

      @media (max-width: 500px) {
        .rs-slots-grid { grid-template-columns: repeat(3, 1fr); }
        .rs-confirm-details { grid-template-columns: 1fr; }
        .rs-header { padding: 20px 18px 0; }
        .rs-panel  { padding: 16px 18px 22px; }
        .rs-steps  { padding: 16px 18px 0; }
        .rs-current-info { margin-left: 18px; margin-right: 18px; }
      }
    `;
    document.head.appendChild(style);
  }

  // ── Helpers ──────────────────────────────────────────────────────────────────
  function toISO(y, m, d) {
    return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
  }

  function fmtDateDisplay(isoStr) {
    const d = new Date(isoStr + 'T00:00:00');
    return d.toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
  }

  function setStepUI(step) {
    ['rsPanel1','rsPanel2','rsPanel3','rsPanelSuccess'].forEach((id, i) => {
      const el = document.getElementById(id);
      if (el) el.style.display = (i === step - 1) ? '' : 'none';
    });

    [1, 2, 3].forEach(n => {
      const tab = document.getElementById(`rsStep${n}Tab`);
      if (!tab) return;
      tab.classList.remove('rs-step--active', 'rs-step--done');
      if (n < step) tab.classList.add('rs-step--done');
      else if (n === step) tab.classList.add('rs-step--active');
    });
  }

  // ── Calendar ─────────────────────────────────────────────────────────────────
  function buildCalendar() {
    const labelEl = document.getElementById('rsCalLabel');
    const gridEl  = document.getElementById('rsCalGrid');
    if (!labelEl || !gridEl) return;

    labelEl.textContent = `${MONTH_NAMES[_calMonth]} ${_calYear}`;
    gridEl.innerHTML    = '';

    // Day headers
    DAY_ABBREVS.forEach(d => {
      const hdr = document.createElement('div');
      hdr.className   = 'rs-cal-day-hdr';
      hdr.textContent = d;
      gridEl.appendChild(hdr);
    });

    const today     = new Date(); today.setHours(0, 0, 0, 0);
    const firstDay  = new Date(_calYear, _calMonth, 1).getDay();
    const daysCount = new Date(_calYear, _calMonth + 1, 0).getDate();
    // Min bookable: tomorrow (same-day rescheduling is not useful, and API requires >24h)
    const minDate   = new Date(today); minDate.setDate(today.getDate() + 1);

    // Blank cells
    for (let i = 0; i < firstDay; i++) {
      const blank = document.createElement('div');
      blank.className = 'rs-cal-day rs-cal-day--blank';
      gridEl.appendChild(blank);
    }

    for (let d = 1; d <= daysCount; d++) {
      const cellDate = new Date(_calYear, _calMonth, d);
      cellDate.setHours(0, 0, 0, 0);
      const iso        = toISO(_calYear, _calMonth, d);
      const isPast     = cellDate < minDate;
      const isToday    = cellDate.getTime() === today.getTime();
      const isSelected = iso === _selectedDate;

      const el = document.createElement('div');
      el.className   = 'rs-cal-day'
        + (isPast ? ' rs-cal-day--disabled' : '')
        + (isToday ? ' rs-cal-day--today' : '')
        + (isSelected ? ' rs-cal-day--selected' : '');
      el.textContent = d;
      el.dataset.iso = iso;

      if (!isPast) {
        el.addEventListener('click', () => rsSelectDate(iso, el));
      }

      gridEl.appendChild(el);
    }
  }

  function rsSelectDate(iso, el) {
    _selectedDate = iso;
    _selectedTime = null;

    // Update calendar UI
    document.querySelectorAll('#rsCalGrid .rs-cal-day--selected').forEach(d => d.classList.remove('rs-cal-day--selected'));
    if (el) el.classList.add('rs-cal-day--selected');

    // Enable "Next" button
    const nextBtn = document.getElementById('rsToStep2Btn');
    if (nextBtn) nextBtn.disabled = false;

    // Pre-fetch slots in background
    fetchSlots(iso);
  }

  // ── Time slots ───────────────────────────────────────────────────────────────
  async function fetchSlots(isoDate) {
    if (_slotsCache[isoDate]) return _slotsCache[isoDate];

    const loadingEl = document.getElementById('rsSlotsLoading');
    const gridEl    = document.getElementById('rsSlotsGrid');
    if (loadingEl) loadingEl.style.display = 'flex';
    if (gridEl)    gridEl.innerHTML = '';

    try {
      const url  = `api/user/trainers/availability.php?trainer_id=${_trainerId}&date=${isoDate}&_=${Date.now()}`;
      const res  = await fetch(url);
      const data = await res.json();

      if (!data.success) return [];

      _slotsCache[isoDate] = data.slots || [];
      return _slotsCache[isoDate];
    } catch {
      return [];
    } finally {
      if (loadingEl) loadingEl.style.display = 'none';
    }
  }

  async function renderSlots(isoDate) {
    const loadingEl  = document.getElementById('rsSlotsLoading');
    const gridEl     = document.getElementById('rsSlotsGrid');
    const labelEl    = document.getElementById('rsSelectedDateLabel');
    const toStep3Btn = document.getElementById('rsToStep3Btn');

    if (labelEl)   labelEl.textContent = '📅 ' + fmtDateDisplay(isoDate);
    if (loadingEl) loadingEl.style.display = 'flex';
    if (gridEl)    gridEl.innerHTML = '';
    if (toStep3Btn) toStep3Btn.disabled = true;

    const slots = await fetchSlots(isoDate);

    if (loadingEl) loadingEl.style.display = 'none';

    if (!slots.length) {
      if (gridEl) gridEl.innerHTML = '<div class="rs-slots-empty">No time slots available for this date.</div>';
      return;
    }

    slots.forEach(slot => {
      const isUnavailable = !slot.available;
      const isSelected    = slot.time === _selectedTime;

      const el = document.createElement('div');
      el.className = 'rs-slot'
        + (isUnavailable ? ' rs-slot--unavailable' : '')
        + (isSelected ? ' rs-slot--selected' : '');

      el.innerHTML = `
        <div>${slot.time}</div>
        <div class="rs-slot-sub">${isUnavailable ? 'Unavailable' : 'Open'}</div>
      `;

      if (!isUnavailable) {
        el.addEventListener('click', () => rsSelectTime(slot.time, el));
      }

      if (gridEl) gridEl.appendChild(el);
    });
  }

  function rsSelectTime(time, el) {
    _selectedTime = time;

    document.querySelectorAll('#rsSlotsGrid .rs-slot--selected').forEach(s => s.classList.remove('rs-slot--selected'));
    if (el) el.classList.add('rs-slot--selected');

    const btn = document.getElementById('rsToStep3Btn');
    if (btn) btn.disabled = false;
  }

  // ── Step navigation ───────────────────────────────────────────────────────────
  window.rsGoToStep = async function (step) {
    if (step === 2) {
      if (!_selectedDate) return;
      setStepUI(2);
      await renderSlots(_selectedDate);
    } else if (step === 3) {
      if (!_selectedTime) return;
      setStepUI(3);
      buildConfirmPanel();
    } else {
      setStepUI(step);
    }

    if (step === 1) buildCalendar();
  };

  function buildConfirmPanel() {
    const el = document.getElementById('rsConfirmDetails');
    if (!el) return;

    el.innerHTML = `
      <div class="rs-confirm-detail-item">
        <div class="rs-cd-label">Trainer</div>
        <div class="rs-cd-value">${_trainerName}</div>
      </div>
      <div class="rs-confirm-detail-item">
        <div class="rs-cd-label">New Date</div>
        <div class="rs-cd-value rs-cd-new">${fmtDateDisplay(_selectedDate)}</div>
      </div>
      <div class="rs-confirm-detail-item">
        <div class="rs-cd-label">New Time</div>
        <div class="rs-cd-value rs-cd-new">${_selectedTime}</div>
      </div>
      <div class="rs-confirm-detail-item">
        <div class="rs-cd-label">Booking ID</div>
        <div class="rs-cd-value">#${_bookingId}</div>
      </div>
    `;
  }

  // ── Calendar navigation ───────────────────────────────────────────────────────
  window.rsCalPrev = function () {
    _calMonth--;
    if (_calMonth < 0) { _calMonth = 11; _calYear--; }
    buildCalendar();
  };

  window.rsCalNext = function () {
    _calMonth++;
    if (_calMonth > 11) { _calMonth = 0; _calYear++; }
    buildCalendar();
  };

  // ── Submit ────────────────────────────────────────────────────────────────────
  window.rsSubmit = async function () {
    const btn = document.getElementById('rsConfirmBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Rescheduling…'; }

    try {
      const fd = new FormData();
      fd.append('booking_id', _bookingId);
      fd.append('new_date',   _selectedDate);
      fd.append('new_time',   _selectedTime);

      const res    = await fetch('api/bookings/reschedule-trainer.php', { method: 'POST', body: fd });
      const result = await res.json();

      if (btn) { btn.disabled = false; btn.textContent = '✓ Confirm Reschedule'; }

      if (!result.success) {
        // Show error inline without closing modal
        const warning = document.querySelector('.rs-confirm-warning span:last-child');
        if (warning) {
          warning.innerHTML = `<span style="color:#c62828;font-weight:700;">⚠ ${result.message || 'Reschedule failed. Please try again.'}</span>`;
        }
        return;
      }

      // Show success panel
      setStepUI(4);
      const msgEl = document.getElementById('rsSuccessMsg');
      if (msgEl) {
        msgEl.textContent = `Your session with ${_trainerName} has been moved to ${result.new_datetime_label}.`;
      }

      // Notify homepage to refresh trainer bookings
      window.dispatchEvent(new CustomEvent('trainerSessionRescheduled', {
        detail: { bookingId: _bookingId, newDate: _selectedDate, newTime: _selectedTime }
      }));

    } catch (err) {
      if (btn) { btn.disabled = false; btn.textContent = '✓ Confirm Reschedule'; }
      console.error('Reschedule error:', err);
    }
  };

  // ── Public API ────────────────────────────────────────────────────────────────
  window.openRescheduleModal = function (bookingId, trainerId, trainerName, currentDate, currentTime) {
    injectModal();

    _bookingId    = bookingId;
    _trainerId    = trainerId;
    _trainerName  = trainerName;
    _selectedDate = null;
    _selectedTime = null;
    _slotsCache   = {};

    const now   = new Date();
    _calYear    = now.getFullYear();
    _calMonth   = now.getMonth();

    // Set subtitle
    const subtitleEl = document.getElementById('rsSubtitle');
    if (subtitleEl) subtitleEl.textContent = `Session with ${trainerName}`;

    // Show current booking info
    const infoEl = document.getElementById('rsCurrentInfo');
    if (infoEl && currentDate && currentTime) {
      const fmtDate = new Date(currentDate + 'T00:00:00')
        .toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' });
      infoEl.innerHTML = `<strong>Current session:</strong> ${fmtDate} at ${currentTime}`;
    }

    // Reset to step 1
    setStepUI(1);
    buildCalendar();

    const nextBtn = document.getElementById('rsToStep2Btn');
    if (nextBtn) nextBtn.disabled = true;

    // Show modal
    const overlay = document.getElementById('rescheduleModal');
    if (overlay) overlay.classList.add('rs-open');
  };

  window.closeRescheduleModal = function () {
    const overlay = document.getElementById('rescheduleModal');
    if (overlay) overlay.classList.remove('rs-open');
  };

})();