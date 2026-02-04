let bookingData = {
  class: null,
  date: null,
  time: null,
  specialRequirements: null,
  emergencyContact: {
    name: null,
    phone: null,
  },
};

// Step navigation
function nextStep(step) {
  // Validate current step before proceeding
  const currentStep = document.querySelector(".step-content.active").id;

  if (currentStep === "step1" && !bookingData.class) {
    alert("Please select a class before continuing");
    return;
  }

  if (currentStep === "step2" && !bookingData.date) {
    alert("Please select a date before continuing");
    return;
  }

  if (currentStep === "step3" && !bookingData.time) {
    alert("Please select a time slot before continuing");
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

// Class selection
function selectClass(className) {
  bookingData.class = className;
  document.getElementById("summaryClass").textContent = className;

  // Remove selection from all classes
  document.querySelectorAll(".class-option").forEach((option) => {
    option.classList.remove("selected");
  });

  // Add selection to clicked class
  event.target.closest(".class-option").classList.add("selected");
}

// Date selection
function selectDate(date) {
  bookingData.date = date;
  document.getElementById("summaryDate").textContent = date;

  // Remove selection from all dates
  document.querySelectorAll(".date-option").forEach((option) => {
    option.classList.remove("selected");
  });

  // Add selection to clicked date
  event.target.closest(".date-option").classList.add("selected");
}

// Time selection
function selectTime(time) {
  // Check if the time slot is full
  if (event.target.closest(".time-slot").classList.contains("full")) {
    alert("This time slot is fully booked. Please select another time.");
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

// Confirm booking
async function confirmBooking() {
  // Get additional form data
  bookingData.specialRequirements = document.getElementById(
    "specialRequirements",
  ).value;
  bookingData.emergencyContact.name =
    document.getElementById("emergencyName").value;
  bookingData.emergencyContact.phone =
    document.getElementById("emergencyPhone").value;

  // Validate emergency contact (optional but recommended)
  if (
    !bookingData.emergencyContact.name ||
    !bookingData.emergencyContact.phone
  ) {
    const proceed = confirm(
      "You haven't filled in emergency contact information. Do you want to continue without it?",
    );
    if (!proceed) {
      return;
    }
  }

  showLoading("Booking Class");

  try {
    await simulateLoading(2000);

    hideLoading();

    // Hide step 4
    document.getElementById("step4").classList.remove("active");

    // Show success message
    document.getElementById("successMessage").classList.add("active");

    // Update all step indicators to completed
    document.querySelectorAll(".step").forEach((step) => {
      step.classList.add("completed");
    });

    // Log booking data (in a real app, this would be sent to a server)
    console.log("Booking confirmed:", bookingData);

    // Optional: Scroll to success message
    document
      .querySelector(".success-message")
      .scrollIntoView({ behavior: "smooth", block: "center" });
  } catch (error) {
     hideLoading();
    
    alert("Something went wrong. Please try again.");
  }
}
