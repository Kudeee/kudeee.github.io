import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

let bookingData = {
  trainer:   { id: null, name: null, specialty: null, baseRate: 0 },
  session:   { duration: null, durationMinutes: null, multiplier: 1 },
  focusArea: null,
  date:      null,
  dateValue: null,
  time:      null,
};

let calendarYear  = 0;
let calendarMonth = 0;

render('#pop-up', 'warning', renderPopUP);
window.closePopUp           = closePopUp;
window.nextStep             = nextStep;
window.prevStep             = prevStep;
window.selectDate           = selectDate;
window.selectTime           = selectTime;
window.selectSession        = selectSession;
window.selectTrainer        = selectTrainer;
window.prepareTrainerSubmit = prepareTrainerSubmit;
window.prevMonth            = prevMonth;
window.nextMonth            = nextMonth;

const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAY_ABBREVS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

function toISODate(y, m, d) {
  return `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
}
function formatDisplayDate(y, m, d) {
  return `${MONTH_NAMES[m].slice(0,3)} ${d}, ${y}`;
}

// ─── Calendar ─────────────────────────────────────────────────────────────────

function buildCalendar() {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (!calendarYear) { calendarYear = today.getFullYear(); calendarMonth = today.getMonth(); }

  const monthLabel = document.querySelector('.calendar-month');
  if (monthLabel) monthLabel.textContent = `${MONTH_NAMES[calendarMonth]} ${calendarYear}`;

  const grid = document.querySelector('.calendar-grid');
  if (!grid) return;
  grid.innerHTML = '';

  DAY_ABBREVS.forEach(d => {
    const hdr = document.createElement('div');
    hdr.className = 'calendar-day-header';
    hdr.textContent = d;
    hdr.style.cssText = 'font-size:0.75rem;font-weight:700;color:#aaa;text-align:center;padding:4px 0;';
    grid.appendChild(hdr);
  });

  const firstDay    = new Date(calendarYear, calendarMonth, 1).getDay();
  const daysInMonth = new Date(calendarYear, calendarMonth + 1, 0).getDate();

  for (let i = 0; i < firstDay; i++) {
    const blank = document.createElement('div');
    blank.className = 'calendar-day disabled';
    grid.appendChild(blank);
  }

  for (let d = 1; d <= daysInMonth; d++) {
    const cellDate = new Date(calendarYear, calendarMonth, d);
    cellDate.setHours(0,0,0,0);
    const isPast     = cellDate < today;
    const iso        = toISODate(calendarYear, calendarMonth, d);
    const isSelected = iso === bookingData.dateValue;

    const el = document.createElement('div');
    el.className   = 'calendar-day' + (isPast ? ' disabled' : '') + (isSelected ? ' selected' : '');
    el.textContent = d;
    el.dataset.iso = iso;

    if (!isPast) {
      el.addEventListener('click', () => selectDate(formatDisplayDate(calendarYear, calendarMonth, d), iso, el));
    }
    grid.appendChild(el);
  }
}

function prevMonth() {
  calendarMonth--;
  if (calendarMonth < 0) { calendarMonth = 11; calendarYear--; }
  buildCalendar();
}
function nextMonth() {
  calendarMonth++;
  if (calendarMonth > 11) { calendarMonth = 0; calendarYear++; }
  buildCalendar();
}

// ─── Step navigation ──────────────────────────────────────────────────────────

function nextStep(step) {
  const currentStep = document.querySelector(".step-content.active").id;

  if (currentStep === "step1" && !bookingData.trainer.name) { showPopUP("Please select a trainer before continuing"); return; }
  if (currentStep === "step2") {
    if (!bookingData.session.duration) { showPopUP("Please select a session duration before continuing"); return; }
    const focusArea = document.getElementById("focusArea").value;
    if (!focusArea) { showPopUP("Please select a focus area before continuing"); return; }
    bookingData.focusArea = focusArea;
  }
  if (currentStep === "step3" && (!bookingData.dateValue || !bookingData.time)) {
    showPopUP("Please select both a date and time before continuing"); return;
  }

  document.querySelectorAll(".step-content").forEach(c => c.classList.remove("active"));
  document.querySelectorAll(".step").forEach(s => s.classList.remove("active"));
  document.getElementById("step" + step).classList.add("active");
  document.getElementById("step" + step + "Indicator").classList.add("active");
  for (let i = 1; i < step; i++) document.getElementById("step" + i + "Indicator").classList.add("completed");

  if (step === 3) {
    buildCalendar();
    hookCalendarNav();
    if (bookingData.dateValue) loadAvailability(bookingData.dateValue);
    else renderTimeSlots([]);
  }

  document.querySelector(".booking-steps").scrollIntoView({ behavior: "smooth", block: "start" });
}

function prevStep(step) {
  document.querySelectorAll(".step-content").forEach(c => c.classList.remove("active"));
  document.querySelectorAll(".step").forEach(s => s.classList.remove("active", "completed"));
  document.getElementById("step" + step).classList.add("active");
  document.getElementById("step" + step + "Indicator").classList.add("active");
  for (let i = 1; i < step; i++) document.getElementById("step" + i + "Indicator").classList.add("completed");
  document.querySelector(".booking-steps").scrollIntoView({ behavior: "smooth", block: "start" });
}

function hookCalendarNav() {
  const nav = document.querySelector('.calendar-nav');
  if (!nav) return;
  const [btnPrev, btnNext] = nav.querySelectorAll('button');
  if (btnPrev) btnPrev.onclick = prevMonth;
  if (btnNext) btnNext.onclick = nextMonth;
}

// ─── Time slots — API now marks past slots as unavailable ─────────────────────

function renderTimeSlots(slots) {
  const container = document.querySelector('#step3 .time-slots');
  if (!container) return;

  if (!bookingData.dateValue) {
    container.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#aaa;padding:20px;">Select a date to see available times.</p>';
    return;
  }

  if (!slots.length) {
    container.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#aaa;padding:20px;">No slots data available.</p>';
    return;
  }

  container.innerHTML = slots.map(slot => {
    const isUnavailable = !slot.available;
    const isSelected    = slot.time === bookingData.time;
    const cls = ['time-slot', isUnavailable ? 'unavailable' : '', isSelected ? 'selected' : ''].filter(Boolean).join(' ');
    const subLabel = isUnavailable ? 'Unavailable' : 'Available';
    const title    = slot.past ? 'This time has already passed' : slot.booked ? 'Already booked' : '';

    if (isUnavailable) {
      return `<div class="${cls}" title="${title}">${slot.time}<div style="font-size:0.7rem;margin-top:3px;opacity:0.6;">${subLabel}</div></div>`;
    }
    return `<div class="${cls}" onclick="selectTime('${slot.time}')">${slot.time}<div style="font-size:0.7rem;margin-top:3px;opacity:0.6;">${subLabel}</div></div>`;
  }).join('');
}

// ─── Load availability — always fresh fetch, no cache ─────────────────────────

async function loadAvailability(date) {
  if (!bookingData.trainer.id && !bookingData.trainer.name) return;

  const slotsContainer = document.querySelector('#step3 .time-slots');
  if (slotsContainer) {
    slotsContainer.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#aaa;padding:20px;">Loading availability…</p>';
  }

  try {
    let trainerId = bookingData.trainer.id;

    if (!trainerId) {
      const res  = await fetch('api/trainers/list.php');
      const data = await res.json();
      const found = data.trainers?.find(t => t.full_name === bookingData.trainer.name);
      if (found) { trainerId = found.id; bookingData.trainer.id = found.id; }
    }

    if (!trainerId) { renderTimeSlots([]); return; }

    // cache-bust param so browser never serves a stale response
    const res      = await fetch(`api/user/trainers/availability.php?trainer_id=${trainerId}&date=${date}&_=${Date.now()}`);
    const data     = await res.json();

    if (!data.success) { renderTimeSlots([]); return; }

    renderTimeSlots(data.slots);

  } catch (err) {
    console.warn('Could not load availability:', err);
    renderTimeSlots([]);
  }
}

// ─── Selection handlers ───────────────────────────────────────────────────────

function selectTrainer(name, specialty, baseRate, id) {
  bookingData.trainer = { id: id || null, name, specialty, baseRate: parseFloat(baseRate) };
  document.getElementById("summaryTrainer").textContent   = name;
  document.getElementById("summarySpecialty").textContent = specialty;
  document.getElementById("baseRate").textContent         = "₱" + Number(baseRate).toLocaleString('en-PH');
  updateTotalPrice();
  document.querySelectorAll(".trainer-option").forEach(o => o.classList.remove("selected"));
  document.querySelectorAll(".trainer-option").forEach(o => {
    if (o.querySelector("h3")?.textContent.trim() === name) o.classList.add("selected");
  });
}

function selectSession(duration, minutes, multiplier) {
  bookingData.session = { duration, durationMinutes: parseInt(minutes), multiplier: parseFloat(multiplier) };
  document.getElementById("summaryDuration").textContent = duration;
  document.getElementById("multiplier").textContent      = "×" + multiplier;
  updateTotalPrice();
  document.querySelectorAll(".session-option").forEach(o => o.classList.remove("selected"));
  if (event && event.target) {
    const target = event.target.closest(".session-option");
    if (target) target.classList.add("selected");
  }
}

function selectDate(displayLabel, isoValue, el) {
  bookingData.date      = displayLabel;
  bookingData.dateValue = isoValue;
  bookingData.time      = null;
  document.getElementById("summaryDate").textContent = displayLabel;
  document.getElementById("summaryTime").textContent = '-';
  document.querySelectorAll(".calendar-day").forEach(d => d.classList.remove("selected"));
  if (el) el.classList.add("selected");
  else document.querySelectorAll(".calendar-day[data-iso]").forEach(d => {
    if (d.dataset.iso === isoValue) d.classList.add("selected");
  });
  loadAvailability(isoValue);
}

function selectTime(time) {
  const slot = event?.target?.closest(".time-slot");
  if (slot?.classList.contains("unavailable")) { showPopUP("This time slot is unavailable. Please select another time."); return; }
  bookingData.time = time;
  document.getElementById("summaryTime").textContent = time;
  document.querySelectorAll(".time-slot").forEach(s => s.classList.remove("selected"));
  if (slot) slot.classList.add("selected");
  else document.querySelectorAll(".time-slot").forEach(s => { if (s.textContent.trim().startsWith(time)) s.classList.add("selected"); });
}

function updateTotalPrice() {
  if (bookingData.trainer.baseRate && bookingData.session.multiplier) {
    const total = bookingData.trainer.baseRate * bookingData.session.multiplier;
    document.getElementById("totalPrice").textContent = "₱" + Number(total).toLocaleString('en-PH');
  }
}

async function preselectTrainerFromURL() {
  const params    = new URLSearchParams(window.location.search);
  const trainerId = parseInt(params.get('trainer_id'));
  if (!trainerId) return;
  try {
    const res  = await fetch('api/trainers/list.php');
    const data = await res.json();
    const t    = data.trainers?.find(tr => tr.id === trainerId);
    if (!t) return;
    setTimeout(() => selectTrainer(t.full_name, t.specialty, t.session_rate, t.id), 600);
  } catch (err) { console.warn('Could not pre-select trainer:', err); }
}

preselectTrainerFromURL();
function prepareTrainerSubmit() {}

// ─── Form submission ──────────────────────────────────────────────────────────

document.getElementById("trainerBookingForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  if (!bookingData.trainer.name)                   { showPopUP("Please go back and select a trainer."); return; }
  if (!bookingData.session.duration)               { showPopUP("Please go back and select a session duration."); return; }
  if (!bookingData.dateValue || !bookingData.time) { showPopUP("Please go back and select a date and time."); return; }

  const fitnessLevel = document.getElementById("fitness_level").value;
  if (!fitnessLevel) { showPopUP("Please select your current fitness level."); return; }

  const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
  if (!paymentMethod) { showPopUP("Please select a payment method."); return; }

  const total = Math.round(bookingData.trainer.baseRate * bookingData.session.multiplier);

  document.getElementById("hidden_trainer_name").value      = bookingData.trainer.name;
  document.getElementById("hidden_trainer_specialty").value = bookingData.trainer.specialty || '';
  document.getElementById("hidden_session_duration").value  = bookingData.session.duration;
  document.getElementById("hidden_session_minutes").value   = bookingData.session.durationMinutes;
  document.getElementById("hidden_multiplier_val").value    = bookingData.session.multiplier;
  document.getElementById("hidden_focus_area").value        = bookingData.focusArea || '';
  document.getElementById("hidden_booking_date").value      = bookingData.dateValue;
  document.getElementById("hidden_booking_time").value      = bookingData.time;
  document.getElementById("hidden_total_price").value       = total;

  let trainerIdInput = document.getElementById("hidden_trainer_id");
  if (!trainerIdInput) {
    trainerIdInput = document.createElement('input');
    trainerIdInput.type = 'hidden'; trainerIdInput.id = 'hidden_trainer_id'; trainerIdInput.name = 'trainer_id';
    this.appendChild(trainerIdInput);
  }
  trainerIdInput.value = bookingData.trainer.id || '';

  showLoading("Confirming your session...");
  try {
    const res    = await fetch("api/bookings/book-trainer.php", { method: "POST", body: new FormData(this) });
    const result = await res.json();
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