import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

let bookingData = {
  class:      null,
  date:       null,       // display label e.g. "Mon, Jan 20"
  dateValue:  null,       // ISO value e.g. "2026-01-20"
  time:       null,
  scheduleId: null,
};

// Cache: { "YYYY-MM-DD::ClassName": [ scheduleRow, ... ] }
let scheduleCache = {};

render('#pop-up', 'warning', renderPopUP);
window.closePopUp           = closePopUp;
window.nextStep             = nextStep;
window.prevStep             = prevStep;
window.selectClass          = selectClass;
window.selectDate           = selectDate;
window.selectTime           = selectTime;
window.prepareBookingSubmit = prepareBookingSubmit;

// ─── Helpers ──────────────────────────────────────────────────────────────────

const DAY_NAMES   = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTH_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function toISODate(d) {
  const y  = d.getFullYear();
  const mo = String(d.getMonth() + 1).padStart(2, '0');
  const da = String(d.getDate()).padStart(2, '0');
  return `${y}-${mo}-${da}`;
}

function formatDisplayDate(d) {
  return `${DAY_NAMES[d.getDay()]}, ${MONTH_NAMES[d.getMonth()]} ${d.getDate()}`;
}

// ─── Build the date grid (next 7 days) — lives inside step 2 ─────────────────

function buildDateGrid() {
  const grid = document.querySelector('#step2 .date-grid');
  if (!grid) return;

  grid.innerHTML = '';

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  for (let i = 0; i < 7; i++) {
    const d    = new Date(today);
    d.setDate(today.getDate() + i);

    const iso     = toISODate(d);
    const dayName = DAY_NAMES[d.getDay()];
    const dayNum  = d.getDate();
    const label   = i === 0 ? 'Today' : formatDisplayDate(d);

    const el = document.createElement('div');
    el.className     = 'date-option';
    el.dataset.iso   = iso;
    el.dataset.label = label;
    el.innerHTML     = `
      <div class="date-day">${dayName}</div>
      <div class="date-number">${dayNum}</div>
    `;
    el.addEventListener('click', () => selectDate(label, iso, el));
    grid.appendChild(el);
  }
}

// ─── Fetch class schedules for a given date & class name ─────────────────────

async function fetchSchedulesForDate(isoDate, className) {
  const cacheKey = `${isoDate}::${className || 'all'}`;
  if (scheduleCache[cacheKey]) return scheduleCache[cacheKey];

  try {
    const params = new URLSearchParams({ date_from: isoDate, date_to: isoDate, per_page: 50 });
    if (className) params.set('class_name', className);

    const res  = await fetch('api/user/schedule/list.php?' + params);
    const data = await res.json();
    scheduleCache[cacheKey] = data.success ? (data.classes || []) : [];
  } catch (e) {
    scheduleCache[cacheKey] = [];
  }

  return scheduleCache[cacheKey];
}

// ─── Render time slots for selected date / class (inside step 2) ─────────────

async function renderTimeSlots() {
  const container = document.querySelector('#step2 .time-slots');
  if (!container) return;

  if (!bookingData.dateValue) {
    container.innerHTML = '<p style="grid-column:1/-1;color:#aaa;text-align:center;padding:20px;">Select a date above to see available times.</p>';
    return;
  }

  container.innerHTML = '<p style="grid-column:1/-1;color:#aaa;text-align:center;padding:20px;">Loading available times…</p>';

  const schedules = await fetchSchedulesForDate(bookingData.dateValue, bookingData.class);

  if (!schedules.length) {
    container.innerHTML = `
      <div style="grid-column:1/-1;text-align:center;padding:30px;color:#999;">
        <div style="font-size:2rem;margin-bottom:10px;">😔</div>
        <div style="font-weight:600;">No classes scheduled for this date</div>
        <div style="font-size:0.9rem;margin-top:6px;">Try a different date or class type.</div>
      </div>`;
    return;
  }

  container.innerHTML = '';

  schedules.forEach(cls => {
    const scheduledAt = new Date(cls.scheduled_at);
    const timeStr     = scheduledAt.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true });
    const spotsLeft   = cls.max_participants - cls.current_participants;
    const isFull      = spotsLeft <= 0;
    const isBooked    = cls.already_booked == 1;

    const el = document.createElement('div');
    el.className = 'time-slot' + (isFull || isBooked ? ' full' : '');

    el.innerHTML = `
      <div class="time-text">${timeStr}</div>
      <div class="time-spots">${isBooked ? '✓ Booked' : isFull ? 'Full' : spotsLeft + ' spot' + (spotsLeft !== 1 ? 's' : '') + ' left'}</div>
      ${cls.trainer_name ? `<div style="font-size:0.75rem;color:${isFull || isBooked ? '#bbb' : '#888'};margin-top:4px;">${cls.trainer_name}</div>` : ''}
    `;

    if (!isFull && !isBooked) {
      el.addEventListener('click', () => {
        bookingData.scheduleId = cls.id;
        selectTime(timeStr, el);
      });
    } else if (isBooked) {
      el.title = 'You have already booked this class.';
    }

    container.appendChild(el);
  });
}

// ─── Step navigation ──────────────────────────────────────────────────────────
// Steps: 1 = Choose Class | 2 = Date & Time (combined) | 3 = Confirm

function nextStep(step) {
  const currentStep = document.querySelector(".step-content.active").id;

  if (currentStep === "step1" && !bookingData.class) {
    showPopUP("Please select a class before continuing.");
    return;
  }
  if (currentStep === "step2") {
    if (!bookingData.dateValue) {
      showPopUP("Please select a date before continuing.");
      return;
    }
    if (!bookingData.time) {
      showPopUP("Please select a time slot before continuing.");
      return;
    }
  }

  document.querySelectorAll(".step-content").forEach(c => c.classList.remove("active"));
  document.querySelectorAll(".step").forEach(s => s.classList.remove("active"));

  document.getElementById("step" + step).classList.add("active");
  document.getElementById("step" + step + "Indicator").classList.add("active");

  for (let i = 1; i < step; i++) {
    document.getElementById("step" + i + "Indicator").classList.add("completed");
  }

  // When entering step 2, ensure date grid is built & selections restored
  if (step === 2) {
    buildDateGrid();
    if (bookingData.dateValue) {
      document.querySelectorAll('#step2 .date-option').forEach(o => {
        if (o.dataset.iso === bookingData.dateValue) o.classList.add('selected');
      });
      renderTimeSlots();
    }
  }

  document.querySelector(".booking-steps").scrollIntoView({ behavior: "smooth", block: "start" });
}

function prevStep(step) {
  document.querySelectorAll(".step-content").forEach(c => c.classList.remove("active"));
  document.querySelectorAll(".step").forEach(s => s.classList.remove("active", "completed"));

  document.getElementById("step" + step).classList.add("active");
  document.getElementById("step" + step + "Indicator").classList.add("active");

  for (let i = 1; i < step; i++) {
    document.getElementById("step" + i + "Indicator").classList.add("completed");
  }

  document.querySelector(".booking-steps").scrollIntoView({ behavior: "smooth", block: "start" });
}

// ─── Selection handlers ───────────────────────────────────────────────────────

function selectClass(className) {
  bookingData.class      = className;
  bookingData.time       = null;
  bookingData.scheduleId = null;
  document.getElementById("summaryClass").textContent = className;

  document.querySelectorAll(".class-option").forEach(o => o.classList.remove("selected"));
  const target = (event && event.target) ? event.target.closest(".class-option") : null;
  if (target) target.classList.add("selected");

  scheduleCache = {}; // invalidate cache when class changes
}

function selectDate(displayLabel, isoValue, el) {
  bookingData.date       = displayLabel;
  bookingData.dateValue  = isoValue;
  bookingData.time       = null;
  bookingData.scheduleId = null;

  document.getElementById("summaryDate").textContent = displayLabel;
  document.getElementById("summaryTime").textContent = '-';

  document.querySelectorAll(".date-option").forEach(o => o.classList.remove("selected"));
  if (el) el.classList.add("selected");

  // Load time slots immediately below the date strip
  renderTimeSlots();
}

function selectTime(time, el) {
  bookingData.time = time;
  document.getElementById("summaryTime").textContent = time;

  document.querySelectorAll(".time-slot").forEach(s => s.classList.remove("selected"));
  if (el) {
    el.classList.add("selected");
  } else {
    document.querySelectorAll(".time-slot").forEach(s => {
      if (s.querySelector('.time-text')?.textContent === time) s.classList.add("selected");
    });
  }
}

// ─── AJAX form submission ─────────────────────────────────────────────────────

function prepareBookingSubmit() { /* kept for compat */ }

document.getElementById("bookingForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  if (!bookingData.class)     { showPopUP("Please go back and select a class.");     return; }
  if (!bookingData.dateValue) { showPopUP("Please go back and select a date.");      return; }
  if (!bookingData.time)      { showPopUP("Please go back and select a time slot."); return; }

  const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
  if (!paymentMethod) { showPopUP("Please select a payment method."); return; }

  document.getElementById("hidden_class_name").value   = bookingData.class;
  document.getElementById("hidden_booking_date").value = bookingData.dateValue;
  document.getElementById("hidden_booking_time").value = bookingData.time;

  let scheduleIdInput = document.getElementById("hidden_class_schedule_id");
  if (!scheduleIdInput) {
    scheduleIdInput      = document.createElement('input');
    scheduleIdInput.type = 'hidden';
    scheduleIdInput.id   = 'hidden_class_schedule_id';
    scheduleIdInput.name = 'class_schedule_id';
    this.appendChild(scheduleIdInput);
  }
  scheduleIdInput.value = bookingData.scheduleId || '';

  showLoading("Confirming your booking...");

  try {
    const formData = new FormData(this);
    const res      = await fetch("api/bookings/book-class.php", { method: "POST", body: formData });
    const result   = await res.json();

    hideLoading();

    if (result.success) {
      document.querySelectorAll(".step-content").forEach(c => c.classList.remove("active"));
      document.getElementById("successMessage").classList.add("active");
    } else {
      render('#pop-up', 'warning', renderPopUP);
      window.closePopUp = closePopUp;
      showPopUP(result.message || "Booking failed. Please try again.");
    }
  } catch (err) {
    hideLoading();
    render('#pop-up', 'warning', renderPopUP);
    window.closePopUp = closePopUp;
    showPopUP("Something went wrong. Please try again.");
  }
});

// ─── Init ─────────────────────────────────────────────────────────────────────

buildDateGrid();