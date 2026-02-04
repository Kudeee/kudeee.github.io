let bookingData = {
  trainer: {
    name: null,
    specialty: null,
    baseRate: 0,
  },
  session: {
    duration: null,
    durationMinutes: null,
    multiplier: 1,
  },
  focusArea: null,
  date: null,
  time: null,
  fitnessGoals: null,
  fitnessLevel: null,
  medicalInfo: null,
  recurring: false,
};

// Step navigation
function nextStep(step) {
  // Validate current step before proceeding
  const currentStep = document.querySelector(".step-content.active").id;

  if (currentStep === "step1" && !bookingData.trainer.name) {
    alert("Please select a trainer before continuing");
    return;
  }

  if (currentStep === "step2") {
    if (!bookingData.session.duration) {
      alert("Please select a session duration before continuing");
      return;
    }
    const focusArea = document.getElementById("focusArea").value;
    if (!focusArea) {
      alert("Please select a focus area before continuing");
      return;
    }
    bookingData.focusArea = focusArea;
  }

  if (currentStep === "step3" && (!bookingData.date || !bookingData.time)) {
    alert("Please select both date and time before continuing");
    return;
  }

  // Hide all steps
  document.querySelectorAll(".step-content").forEach((content) => {
    content.classList.remove("active");
  });
  document.querySelectorAll(".step").forEach((stepEl) => {
    stepEl.classList.remove("active");
  });

  // Show current step
  document.getElementById("step" + step).classList.add("active");
  document.getElementById("step" + step + "Indicator").classList.add("active");

  // Mark previous steps as completed
  for (let i = 1; i < step; i++) {
    document
      .getElementById("step" + i + "Indicator")
      .classList.add("completed");
  }

  // Scroll to top of booking section
  document
    .querySelector(".booking-steps")
    .scrollIntoView({ behavior: "smooth", block: "start" });
}

function prevStep(step) {
  nextStep(step);
}

// Trainer selection
function selectTrainer(name, specialty, baseRate) {
  bookingData.trainer = {
    name: name,
    specialty: specialty,
    baseRate: parseFloat(baseRate),
  };

  document.getElementById("summaryTrainer").textContent = name;
  document.getElementById("summarySpecialty").textContent = specialty;
  document.getElementById("baseRate").textContent = "₱" + baseRate;

  // Update total price
  updateTotalPrice();

  // Remove selection from all trainers
  document.querySelectorAll(".trainer-option").forEach((option) => {
    option.classList.remove("selected");
  });

  // Add selection to clicked trainer
  event.target.closest(".trainer-option").classList.add("selected");
}

// Session selection
function selectSession(duration, minutes, multiplier) {
  bookingData.session = {
    duration: duration,
    durationMinutes: minutes,
    multiplier: parseFloat(multiplier),
  };

  document.getElementById("summaryDuration").textContent = duration;
  document.getElementById("multiplier").textContent = "×" + multiplier;

  // Update total price
  updateTotalPrice();

  // Remove selection from all sessions
  document.querySelectorAll(".session-option").forEach((option) => {
    option.classList.remove("selected");
  });

  // Add selection to clicked session
  event.target.closest(".session-option").classList.add("selected");
}

// Date selection
function selectDate(date) {
  // Check if the date is disabled
  if (event.target.closest(".calendar-day").classList.contains("disabled")) {
    return;
  }

  bookingData.date = date;
  document.getElementById("summaryDate").textContent = date;

  // Remove selection from all dates
  document.querySelectorAll(".calendar-day").forEach((day) => {
    day.classList.remove("selected");
  });

  // Add selection to clicked date
  event.target.closest(".calendar-day").classList.add("selected");
}

// Time selection
function selectTime(time) {
  // Check if the time slot is unavailable
  if (event.target.closest(".time-slot").classList.contains("unavailable")) {
    alert("This time slot is not available. Please select another time.");
    return;
  }

  bookingData.time = time;
  document.getElementById("summaryTime").textContent = time;

  // Remove selection from all times
  document.querySelectorAll(".time-slot").forEach((slot) => {
    slot.classList.remove("selected");
  });

  // Add selection to clicked time
  event.target.closest(".time-slot").classList.add("selected");
}

// Update total price calculation
function updateTotalPrice() {
  if (bookingData.trainer.baseRate && bookingData.session.multiplier) {
    const total = bookingData.trainer.baseRate * bookingData.session.multiplier;
    document.getElementById("totalPrice").textContent = "₱" + total.toFixed(0);
  }
}

// Confirm booking
async function confirmBooking() {
  // Get additional form data
  bookingData.fitnessGoals = document.getElementById("fitnessGoals").value;
  bookingData.fitnessLevel = document.getElementById("fitnessLevel").value;
  bookingData.medicalInfo = document.getElementById("medicalInfo").value;
  bookingData.recurring = document.getElementById("recurring").checked;

  // Validate fitness level
  if (!bookingData.fitnessLevel) {
    alert("Please select your current fitness level");
    return;
  }

  // Validate fitness goals (optional but recommended)
  if (!bookingData.fitnessGoals) {
    const proceed = confirm(
      "You haven't described your fitness goals. Do you want to continue without them?",
    );
    if (!proceed) {
      return;
    }
  }

  showLoading("Booking Trainer");

  try {

    await simulateLoading(2000);

    hideLoading();

    document.getElementById("step4").classList.remove("active");

    // Show success message
    document.getElementById("successMessage").classList.add("active");

    // Update all step indicators to completed
    document.querySelectorAll(".step").forEach((step) => {
      step.classList.add("completed");
    });

    // Log booking data (in a real app, this would be sent to a server)
    console.log("Training session booked:", bookingData);

    // Optional: Scroll to success message
    document
      .querySelector(".success-message")
      .scrollIntoView({ behavior: "smooth", block: "center" });
  } catch (error) {
    hideLoading();

    alert("Something went wrong. Please try again.");
  }
}
