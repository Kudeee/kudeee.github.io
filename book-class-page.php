<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/book-class-page.css" />
    <style>
      /* Time slot sub-label (trainer name / spots) */
      .time-slot .time-spots {
        font-size: 0.78rem;
        margin-top: 3px;
        opacity: 0.8;
      }
      .time-slot.full {
        opacity: 0.45;
        cursor: not-allowed;
        background: #f5f5f5;
        border-color: #ddd;
      }
      .time-slot.full .time-text,
      .time-slot.full .time-spots {
        color: #999;
      }
    </style>
    <title>Book a Class</title>
  </head>
  <body>
    <header class="header header-js"></header>

    <div class="container">
      <div class="booking-layout">
        <div class="booking-steps">
          <div class="step-indicator">
            <div class="step active" id="step1Indicator">
              <div class="step-number">1</div>
              <div class="step-label">Choose Class</div>
            </div>
            <div class="step" id="step2Indicator">
              <div class="step-number">2</div>
              <div class="step-label">Select Date</div>
            </div>
            <div class="step" id="step3Indicator">
              <div class="step-number">3</div>
              <div class="step-label">Pick Time</div>
            </div>
            <div class="step" id="step4Indicator">
              <div class="step-number">4</div>
              <div class="step-label">Confirm</div>
            </div>
          </div>

          <!-- Step 1: Choose Class -->
          <div id="step1" class="step-content active">
            <h2 class="section-title">Choose Your Class</h2>
            <div class="class-grid">
              <div class="class-option" onclick="selectClass('HIIT Training')">
                <div class="class-name">HIIT Training</div>
                <div class="class-description">High-intensity interval training for maximum calorie burn</div>
              </div>
              <div class="class-option" onclick="selectClass('Yoga Flow')">
                <div class="class-name">Yoga Flow</div>
                <div class="class-description">Mind-body connection through flowing movements</div>
              </div>
              <div class="class-option" onclick="selectClass('CrossFit')">
                <div class="class-name">CrossFit</div>
                <div class="class-description">Functional fitness and strength training</div>
              </div>
              <div class="class-option" onclick="selectClass('Boxing')">
                <div class="class-name">Boxing</div>
                <div class="class-description">Combat training for fitness and confidence</div>
              </div>
              <div class="class-option" onclick="selectClass('Spin Class')">
                <div class="class-name">Spin Class</div>
                <div class="class-description">Indoor cycling for cardio endurance</div>
              </div>
              <div class="class-option" onclick="selectClass('Pilates')">
                <div class="class-name">Pilates</div>
                <div class="class-description">Core strength and body alignment</div>
              </div>
            </div>
            <div class="action-buttons">
              <button class="btn btn-primary" onclick="nextStep(2)">Next: Select Date</button>
            </div>
          </div>

          <!-- Step 2: Select Date — built dynamically by JS -->
          <div id="step2" class="step-content">
            <h2 class="section-title">Select Date</h2>
            <div class="date-selector">
              <!-- JS populates this with the next 7 days -->
              <div class="date-grid"></div>
            </div>
            <div class="action-buttons">
              <button class="btn btn-secondary" onclick="prevStep(1)">Back</button>
              <button class="btn btn-primary" onclick="nextStep(3)">Next: Pick Time</button>
            </div>
          </div>

          <!-- Step 3: Pick Time — populated dynamically after date is chosen -->
          <div id="step3" class="step-content">
            <h2 class="section-title">Pick Time Slot</h2>
            <div class="time-slots">
              <p style="grid-column:1/-1;text-align:center;color:#aaa;padding:20px;">
                Select a date first to see available times.
              </p>
            </div>
            <div class="action-buttons">
              <button class="btn btn-secondary" onclick="prevStep(2)">Back</button>
              <button class="btn btn-primary" onclick="nextStep(4)">Next: Confirm</button>
            </div>
          </div>

          <!-- Step 4: Confirm -->
          <div id="step4" class="step-content">
            <h2 class="section-title">Additional Information</h2>

            <form id="bookingForm">
              <input type="hidden" name="class_name"    id="hidden_class_name"   value="" />
              <input type="hidden" name="booking_date"  id="hidden_booking_date" value="" />
              <input type="hidden" name="booking_time"  id="hidden_booking_time" value="" />

              <div class="form-group">
                <label class="form-label" for="special_requirements">Special Requirements (Optional)</label>
                <textarea
                  id="special_requirements"
                  name="special_requirements"
                  class="form-textarea"
                  placeholder="Any injuries, limitations, or special requests..."
                ></textarea>
              </div>

              <div class="form-group">
                <label class="form-label" for="emergency_name">Emergency Contact</label>
                <input
                  type="text"
                  class="form-input"
                  id="emergency_name"
                  name="emergency_name"
                  placeholder="Name"
                />
                <input
                  type="tel"
                  id="emergency_phone"
                  name="emergency_phone"
                  class="form-input"
                  placeholder="Phone Number"
                  style="margin-top: 10px"
                />
              </div>

              <h2 class="section-title">Payment Method</h2>
              <div class="form-group payment-method-js"></div>

              <div class="action-buttons">
                <button class="btn btn-secondary" type="button" onclick="prevStep(3)">Back</button>
                <button class="btn btn-primary" type="submit" onclick="prepareBookingSubmit()">
                  Confirm Booking
                </button>
              </div>
            </form>
          </div>

          <!-- Success Message -->
          <div id="successMessage" class="step-content">
            <div class="success-message">
              <div class="success-icon"><img src="assests/icons/check.png" alt=""></div>
              <h2 class="success-title">Booking Confirmed!</h2>
              <p class="success-text">
                You're all set for your class. We've sent a confirmation email with all the details.
              </p>
              <button class="btn btn-primary" onclick="location.href = 'homepage.php'">
                Back to Home
              </button>
            </div>
          </div>
        </div>

        <!-- Booking Summary Sidebar -->
        <div class="booking-summary">
          <h3 class="summary-title">Booking Summary</h3>
          <div class="summary-item">
            <div class="summary-label">Class</div>
            <div class="summary-value" id="summaryClass">-</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Date</div>
            <div class="summary-value" id="summaryDate">-</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Time</div>
            <div class="summary-value" id="summaryTime">-</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Duration</div>
            <div class="summary-value">50 minutes</div>
          </div>
          <div class="summary-price">
            <div class="price-label">Total Price</div>
            <div class="price-value">FREE</div>
            <div style="font-size: 0.85rem; color: #666; margin-top: 5px">
              Included in Premium Plan
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="loading"></div>
    <div id="pop-up"></div>

    <script type="module" src="js/header.js"></script>
    <script type="module" src="js/book-class-page.js"></script>
    <script type="module" src="js/payment-methods.js"></script>
    <script src="components/loading.js"></script>
  </body>
</html>