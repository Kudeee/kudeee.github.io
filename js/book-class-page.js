import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

let bookingData = {
  class: null,
  date: null,       // display label e.g. "Mon, Jan 20"
  dateValue: null,  // ISO value e.g. "2026-01-20"
  time: null,
};

render('#pop-up', 'warning', renderPopUP);
window.closePopUp = closePopUp;
window.nextStep = nextStep;
window.prevStep = prevStep;
window.selectClass = selectClass;
window.selectDate = selectDate;
window.selectTime = selectTime;
// Called by the form's submit button to sync hidden fields before POST
window.prepareBookingSubmit = prepareBookingSubmit;

// ─── Step navigation ──────────────────────────────────────────────────────────

function nextStep(step) {
  const currentStep = document.querySelector(".step-content.active").id;

  if (currentStep === "step1" && !bookingData.class) {
    showPopUP("Please select a class before continuing");
    return;
  }
  if (currentStep === "step2" && !bookingData.date) {
    showPopUP("Please select a date before continuing");
    return;
  }
  if (currentStep === "step3" && !bookingData.time) {
    showPopUP("Please select a time slot before continuing");
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

function selectClass(className) {
  bookingData.class = className;
  document.getElementById("summaryClass").textContent = className;

  document.querySelectorAll(".class-option").forEach(o => o.classList.remove("selected"));
  event.target.closest(".class-option").classList.add("selected");
}

// Now accepts both the display label and ISO date value
function selectDate(displayLabel, isoValue) {
  bookingData.date      = displayLabel;
  bookingData.dateValue = isoValue;
  document.getElementById("summaryDate").textContent = displayLabel;

  document.querySelectorAll(".date-option").forEach(o => o.classList.remove("selected"));
  event.target.closest(".date-option").classList.add("selected");
}

function selectTime(time) {
  if (event.target.closest(".time-slot").classList.contains("full")) {
    showPopUP("This time slot is fully booked. Please select another time.");
    return;
  }

  bookingData.time = time;
  document.getElementById("summaryTime").textContent = time;

  document.querySelectorAll(".time-slot").forEach(s => s.classList.remove("selected"));
  event.target.closest(".time-slot").classList.add("selected");
}

// ─── Pre-submit: populate hidden fields so PHP receives all data ──────────────

function prepareBookingSubmit() {
  // Validate required booking steps are complete
  if (!bookingData.class) {
    showPopUP("Please go back and select a class.");
    event.preventDefault();
    return false;
  }
  if (!bookingData.dateValue) {
    showPopUP("Please go back and select a date.");
    event.preventDefault();
    return false;
  }
  if (!bookingData.time) {
    showPopUP("Please go back and select a time slot.");
    event.preventDefault();
    return false;
  }

  // Write booking data into the hidden form fields
  document.getElementById("hidden_class_name").value   = bookingData.class;
  document.getElementById("hidden_booking_date").value = bookingData.dateValue;
  document.getElementById("hidden_booking_time").value = bookingData.time;

  return true; // allow form to submit
}