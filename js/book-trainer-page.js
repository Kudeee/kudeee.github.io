import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

let bookingData = {
    trainer: { name: null, specialty: null, baseRate: 0 },
    session: { duration: null, durationMinutes: null, multiplier: 1 },
    focusArea:  null,
    date:       null,
    dateValue:  null,
    time:       null,
};

render('#pop-up', 'warning', renderPopUP);
window.closePopUp           = closePopUp;
window.nextStep             = nextStep;
window.prevStep             = prevStep;
window.selectDate           = selectDate;
window.selectTime           = selectTime;
window.selectSession        = selectSession;
window.selectTrainer        = selectTrainer;
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
    document.getElementById("baseRate").textContent         = "₱" + Number(baseRate).toLocaleString('en-PH');
    updateTotalPrice();

    document.querySelectorAll(".trainer-option").forEach(o => o.classList.remove("selected"));
    document.querySelectorAll(".trainer-option").forEach(o => {
        if (o.querySelector("h3")?.textContent === name) {
            o.classList.add("selected");
        }
    });
}

function selectSession(duration, minutes, multiplier) {
    bookingData.session = { duration, durationMinutes: minutes, multiplier: parseFloat(multiplier) };

    document.getElementById("summaryDuration").textContent = duration;
    document.getElementById("multiplier").textContent      = "×" + multiplier;
    updateTotalPrice();

    document.querySelectorAll(".session-option").forEach(o => o.classList.remove("selected"));
    event.target.closest(".session-option").classList.add("selected");
}

function selectDate(displayLabel, isoValue) {
    if (event.target.closest(".calendar-day").classList.contains("disabled")) return;

    bookingData.date      = displayLabel;
    bookingData.dateValue = isoValue;
    document.getElementById("summaryDate").textContent = displayLabel;

    document.querySelectorAll(".calendar-day").forEach(d => d.classList.remove("selected"));
    event.target.closest(".calendar-day").classList.add("selected");

    loadAvailability(bookingData.dateValue);
}

function selectTime(time) {
    const slot = event.target.closest(".time-slot");
    if (slot.classList.contains("unavailable")) {
        showPopUP("This time slot is not available. Please select another time.");
        return;
    }

    bookingData.time = time;
    document.getElementById("summaryTime").textContent = time;

    document.querySelectorAll(".time-slot").forEach(s => s.classList.remove("selected"));
    slot.classList.add("selected");
}

function updateTotalPrice() {
    if (bookingData.trainer.baseRate && bookingData.session.multiplier) {
        const total = bookingData.trainer.baseRate * bookingData.session.multiplier;
        document.getElementById("totalPrice").textContent = "₱" + Number(total).toLocaleString('en-PH');
    }
}

// ─── Load real availability from API ─────────────────────────────────────────

async function loadAvailability(date) {
    if (!bookingData.trainer.name) return;

    try {
        const res   = await fetch('api/trainers/list.php');
        const data  = await res.json();
        const found = data.trainers?.find(t => t.full_name === bookingData.trainer.name);
        if (!found) return;

        const availRes  = await fetch(`api/user/trainers/availability.php?trainer_id=${found.id}&date=${date}`);
        const availData = await availRes.json();
        if (!availData.success) return;

        const slotsContainer = document.querySelector('#step3 .time-slots');
        if (!slotsContainer) return;

        slotsContainer.innerHTML = availData.slots.map(s => {
            const cls = s.available ? '' : 'unavailable';
            const clickAttr = s.available ? `onclick="selectTime('${s.time}')"` : '';
            return `<div class="time-slot ${cls}" ${clickAttr}>${s.time}</div>`;
        }).join('');

    } catch (err) {
        console.warn('Could not load availability:', err);
    }
}

// ─── Pre-select trainer from URL param ───────────────────────────────────────

async function preselectTrainerFromURL() {
    const params    = new URLSearchParams(window.location.search);
    const trainerId = parseInt(params.get('trainer_id'));
    if (!trainerId) return;

    try {
        const res  = await fetch('api/trainers/list.php');
        const data = await res.json();
        const t    = data.trainers?.find(tr => tr.id === trainerId);
        if (!t) return;

        setTimeout(() => {
            selectTrainer(t.full_name, t.specialty, t.session_rate);
        }, 500);
    } catch (err) {
        console.warn('Could not pre-select trainer:', err);
    }
}

preselectTrainerFromURL();

// ─── Kept for backward compat (onclick attr on button) ───────────────────────

function prepareTrainerSubmit() {
    // no-op — real logic handled by submit event listener below
}

// ─── AJAX form submission ─────────────────────────────────────────────────────

document.getElementById("trainerBookingForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    if (!bookingData.trainer.name) {
        showPopUP("Please go back and select a trainer.");
        return;
    }
    if (!bookingData.session.duration) {
        showPopUP("Please go back and select a session duration.");
        return;
    }
    if (!bookingData.dateValue || !bookingData.time) {
        showPopUP("Please go back and select a date and time.");
        return;
    }

    const fitnessLevel = document.getElementById("fitness_level").value;
    if (!fitnessLevel) {
        showPopUP("Please select your current fitness level.");
        return;
    }

    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethod) {
        showPopUP("Please select a payment method.");
        return;
    }

    // Populate hidden fields before grabbing FormData
    const total = (bookingData.trainer.baseRate * bookingData.session.multiplier).toFixed(0);

    document.getElementById("hidden_trainer_name").value      = bookingData.trainer.name;
    document.getElementById("hidden_trainer_specialty").value = bookingData.trainer.specialty;
    document.getElementById("hidden_session_duration").value  = bookingData.session.duration;
    document.getElementById("hidden_session_minutes").value   = bookingData.session.durationMinutes;
    document.getElementById("hidden_multiplier_val").value    = bookingData.session.multiplier;
    document.getElementById("hidden_focus_area").value        = bookingData.focusArea || "";
    document.getElementById("hidden_booking_date").value      = bookingData.dateValue;
    document.getElementById("hidden_booking_time").value      = bookingData.time;
    document.getElementById("hidden_total_price").value       = total;

    showLoading("Confirming your session...");

    try {
        const formData = new FormData(this);

        const res    = await fetch("api/bookings/book-trainer.php", { method: "POST", body: formData });
        const result = await res.json();

        hideLoading();

        if (result.success) {
            // Show success step
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