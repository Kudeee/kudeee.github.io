import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

let bookingData = {
  trainer: { name: null, specialty: null, baseRate: 0 },
  session: { duration: null, durationMinutes: null, multiplier: 1 },
  focusArea:    null,
  date:         null,  // display label e.g. "Jan 20"
  dateValue:    null,  // ISO value e.g. "2026-01-20"
  time:         null,
};

render('#pop-up', 'warning', renderPopUP);
window.closePopUp        = closePopUp;
window.nextStep          = nextStep;
window.prevStep          = prevStep;
window.selectDate        = selectDate;
window.selectTime        = selectTime;
window.selectSession     = selectSession;
window.selectTrainer     = selectTrainer;
// Called by the form's submit button to sync hidden fields before POST
window.prepareTrainerSubmit = prepareTrainerSubmit;

// ─── Step navigation ──────────────────────────────────────────────────────────

function nextStep(step) {
  const currentStep = document.querySelector(".step-content.active").id;

  if (currentStep === "step1" && !bookingData.trainer.name) {
    showPopUP("Please select a trainer before continuing");
    return;
  }
  if (currentStep === "step2") {
    if (!bookingData.session.duration) {
      showPopUP("Please select a session duration before continuing");
      return;
    }
    const focusArea = document.getElementById("focusArea").value;
    if (!focusArea) {
      showPopUP("Please select a focus area before continuing");
      return;
    }
    bookingData.focusArea = focusArea;
  }
  if (currentStep === "step3" && (!bookingData.dateValue || !bookingData.time)) {
    showPopUP("Please select both date and time before continuing");
    return;
  }

  document.querySelectorAll(".step-content").forEach(c => c.classList.remove("active"));
  document.querySelectorAll(".step").forEach(s => s.classList.remove("active"));

  document.getElementById("step" + step).classList.add("active");
  document.getElementById("step" + step + "Indicator").classList.add("active");

  for (let i = 1; i < step; i++) {
    document.getElementById("step" + i + "Indicator").classList.add("completed");
  }

  document.querySelector(".booking-steps").scrollIntoView({ behavior: "smooth", block: "start" });
}

function prevStep(step) {
  nextStep(step);
}

// ─── Selection handlers ───────────────────────────────────────────────────────

function selectTrainer(name, specialty, baseRate) {
  bookingData.trainer = { name, specialty, baseRate: parseFloat(baseRate) };

  document.getElementById("summaryTrainer").textContent   = name;
  document.getElementById("summarySpecialty").textContent = specialty;
  document.getElementById("baseRate").textContent         = "₱" + baseRate;
  updateTotalPrice();

  document.querySelectorAll(".trainer-option").forEach(o => o.classList.remove("selected"));
  event.target.closest(".trainer-option").classList.add("selected");
}

function selectSession(duration, minutes, multiplier) {
  bookingData.session = { duration, durationMinutes: minutes, multiplier: parseFloat(multiplier) };

  document.getElementById("summaryDuration").textContent = duration;
  document.getElementById("multiplier").textContent      = "×" + multiplier;
  updateTotalPrice();

  document.querySelectorAll(".session-option").forEach(o => o.classList.remove("selected"));
  event.target.closest(".session-option").classList.add("selected");
}

// Accepts both the display label and the ISO date value
function selectDate(displayLabel, isoValue) {
  if (event.target.closest(".calendar-day").classList.contains("disabled")) return;

  bookingData.date      = displayLabel;
  bookingData.dateValue = isoValue;
  document.getElementById("summaryDate").textContent = displayLabel;

  document.querySelectorAll(".calendar-day").forEach(d => d.classList.remove("selected"));
  event.target.closest(".calendar-day").classList.add("selected");
}

function selectTime(time) {
  if (event.target.closest(".time-slot").classList.contains("unavailable")) {
    showPopUP("This time slot is not available. Please select another time.");
    return;
  }

  bookingData.time = time;
  document.getElementById("summaryTime").textContent = time;

  document.querySelectorAll(".time-slot").forEach(s => s.classList.remove("selected"));
  event.target.closest(".time-slot").classList.add("selected");
}

function updateTotalPrice() {
  if (bookingData.trainer.baseRate && bookingData.session.multiplier) {
    const total = bookingData.trainer.baseRate * bookingData.session.multiplier;
    document.getElementById("totalPrice").textContent = "₱" + total.toFixed(0);
  }
}

// ─── Pre-submit: populate hidden fields so PHP receives all data ──────────────

function prepareTrainerSubmit() {
  if (!bookingData.trainer.name) {
    showPopUP("Please go back and select a trainer.");
    event.preventDefault();
    return false;
  }
  if (!bookingData.session.duration) {
    showPopUP("Please go back and select a session duration.");
    event.preventDefault();
    return false;
  }
  if (!bookingData.dateValue || !bookingData.time) {
    showPopUP("Please go back and select a date and time.");
    event.preventDefault();
    return false;
  }

  const fitnessLevel = document.getElementById("fitness_level").value;
  if (!fitnessLevel) {
    showPopUP("Please select your current fitness level");
    event.preventDefault();
    return false;
  }

  const total = (bookingData.trainer.baseRate * bookingData.session.multiplier).toFixed(0);

  // Write all booking data into hidden form fields
  document.getElementById("hidden_trainer_name").value      = bookingData.trainer.name;
  document.getElementById("hidden_trainer_specialty").value = bookingData.trainer.specialty;
  document.getElementById("hidden_session_duration").value  = bookingData.session.duration;
  document.getElementById("hidden_session_minutes").value   = bookingData.session.durationMinutes;
  document.getElementById("hidden_multiplier_val").value    = bookingData.session.multiplier;
  document.getElementById("hidden_focus_area").value        = bookingData.focusArea || "";
  document.getElementById("hidden_booking_date").value      = bookingData.dateValue;
  document.getElementById("hidden_booking_time").value      = bookingData.time;
  document.getElementById("hidden_total_price").value       = total;

  return true; // allow form to submit
}