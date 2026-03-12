<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/book-trainer-page.css" />
    <style>
      /* Calendar day headers row */
      .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 6px;
      }
      .calendar-day-header {
        font-size: 0.7rem;
        font-weight: 700;
        color: #aaa;
        text-align: center;
        padding: 4px 0;
      }
      .calendar-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        background: #fff;
        border: 2px solid transparent;
      }
      .calendar-day:hover:not(.disabled) {
        background: #fff3e0;
        border-color: #ff6b35;
      }
      .calendar-day.selected {
        background: #ff6b35 !important;
        color: #fff;
        border-color: #ff6b35;
      }
      .calendar-day.disabled {
        opacity: 0.3;
        cursor: not-allowed;
        background: #f5f5f5;
      }
      /* Time slots */
      #step3 .time-slots {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
      }
      .time-slot.unavailable {
        opacity: 0.4;
        cursor: not-allowed;
        background: #f5f5f5;
        border-color: #ddd;
        color: #999;
      }
    </style>
    <title>Book a Trainer</title>
  </head>
  <body>
    <header class="header header-js"></header>

    <div class="container">
      <div class="booking-layout">
        <div class="booking-steps">
          <div class="step-indicator">
            <div class="step active" id="step1Indicator">
              <div class="step-number">1</div>
              <div class="step-label">Choose Trainer</div>
            </div>
            <div class="step" id="step2Indicator">
              <div class="step-number">2</div>
              <div class="step-label">Session Type</div>
            </div>
            <div class="step" id="step3Indicator">
              <div class="step-number">3</div>
              <div class="step-label">Schedule</div>
            </div>
            <div class="step" id="step4Indicator">
              <div class="step-number">4</div>
              <div class="step-label">Details</div>
            </div>
          </div>

          <!-- Step 1: Choose Trainer -->
          <div id="step1" class="step-content active">
            <h2 class="section-title">Choose Your Trainer</h2>
            <div class="trainer-select-grid trainer-select-grid-js"></div>
            <div class="action-buttons">
              <button class="btn btn-primary" onclick="nextStep(2)">Next: Session Type</button>
            </div>
          </div>

          <!-- Step 2: Session Type -->
          <div id="step2" class="step-content">
            <h2 class="section-title">Choose Session Type</h2>
            <div class="session-type-grid">
              <div class="session-option" onclick="selectSession('30 Min', '30', '1')">
                <div class="session-duration">30</div>
                <div class="session-label">Minutes</div>
                <div class="session-price">Base Price</div>
              </div>
              <div class="session-option" onclick="selectSession('60 Min', '60', '1.5')">
                <div class="session-duration">60</div>
                <div class="session-label">Minutes</div>
                <div class="session-price">+50%</div>
              </div>
              <div class="session-option" onclick="selectSession('90 Min', '90', '2')">
                <div class="session-duration">90</div>
                <div class="session-label">Minutes</div>
                <div class="session-price">+100%</div>
              </div>
            </div>

            <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 15px; margin-top: 30px;">
              Focus Area
            </h3>
            <div class="form-group">
              <select class="form-select" id="focusArea" name="focus_area">
                <option value="">Select focus area...</option>
                <option value="weight_loss">Weight Loss</option>
                <option value="muscle_building">Muscle Building</option>
                <option value="strength_training">Strength Training</option>
                <option value="flexibility">Flexibility</option>
                <option value="endurance">Endurance</option>
                <option value="general_fitness">General Fitness</option>
              </select>
            </div>

            <div class="action-buttons">
              <button class="btn btn-secondary" onclick="prevStep(1)">Back</button>
              <button class="btn btn-primary" onclick="nextStep(3)">Next: Schedule</button>
            </div>
          </div>

          <!-- Step 3: Schedule -->
          <div id="step3" class="step-content">
            <h2 class="section-title">Select Date &amp; Time</h2>

            <div class="calendar">
              <div class="calendar-header">
                <div class="calendar-month">Loading…</div>
                <div class="calendar-nav">
                  <button type="button" onclick="prevMonth()">◀</button>
                  <button type="button" onclick="nextMonth()">▶</button>
                </div>
              </div>
              <!-- JS builds day-header row + day cells dynamically -->
              <div class="calendar-grid"></div>
            </div>

            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">
              Available Time Slots
            </h3>
            <div class="time-slots">
              <p style="grid-column:1/-1;text-align:center;color:#aaa;padding:20px;">
                Select a date to see available times.
              </p>
            </div>

            <div class="action-buttons">
              <button class="btn btn-secondary" onclick="prevStep(2)">Back</button>
              <button class="btn btn-primary" onclick="nextStep(4)">Next: Details</button>
            </div>
          </div>

          <!-- Step 4: Details -->
          <div id="step4" class="step-content">
            <h2 class="section-title">Session Details</h2>

            <form id="trainerBookingForm">
              <input type="hidden" name="trainer_name"         id="hidden_trainer_name"      value="" />
              <input type="hidden" name="trainer_specialty"    id="hidden_trainer_specialty" value="" />
              <input type="hidden" name="session_duration"     id="hidden_session_duration"  value="" />
              <input type="hidden" name="session_minutes"      id="hidden_session_minutes"   value="" />
              <input type="hidden" name="price_multiplier"     id="hidden_multiplier_val"    value="" />
              <input type="hidden" name="focus_area"           id="hidden_focus_area"        value="" />
              <input type="hidden" name="booking_date"         id="hidden_booking_date"      value="" />
              <input type="hidden" name="booking_time"         id="hidden_booking_time"      value="" />
              <input type="hidden" name="total_price"          id="hidden_total_price"       value="" />

              <div class="form-group">
                <label class="form-label" for="fitness_goals">Fitness Goals</label>
                <textarea
                  id="fitness_goals"
                  name="fitness_goals"
                  class="form-textarea"
                  placeholder="Describe your fitness goals and what you want to achieve..."
                ></textarea>
              </div>

              <div class="form-group">
                <label class="form-label" for="fitness_level">Current Fitness Level</label>
                <select class="form-select" id="fitness_level" name="fitness_level">
                  <option value="">Select your level...</option>
                  <option value="beginner">Beginner</option>
                  <option value="intermediate">Intermediate</option>
                  <option value="advanced">Advanced</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="medical_info">Medical Conditions / Injuries</label>
                <textarea
                  id="medical_info"
                  name="medical_info"
                  class="form-textarea"
                  placeholder="Any medical conditions, injuries, or limitations we should know about..."
                ></textarea>
              </div>

              <h2 class="section-title">Payment Method</h2>
              <div class="form-group payment-method-js"></div>

              <div class="checkbox-group">
                <input type="checkbox" id="recurring" name="recurring" value="1" />
                <label for="recurring">Make this a recurring session (weekly)</label>
              </div>

              <div class="action-buttons">
                <button class="btn btn-secondary" type="button" onclick="prevStep(3)">Back</button>
                <button class="btn btn-primary" type="submit" onclick="prepareTrainerSubmit()">
                  Confirm Booking
                </button>
              </div>
            </form>
          </div>

          <!-- Success Message -->
          <div id="successMessage" class="step-content">
            <div class="success-message">
              <div class="success-icon">
                <img src="assests/icons/check.png" alt="" />
              </div>
              <h2 class="success-title">Session Booked!</h2>
              <p class="success-text">
                Your training session has been confirmed. Your trainer will contact you 24 hours before the session.
              </p>
              <button
                class="btn btn-primary"
                onclick="location.href = 'homepage.php'"
                style="flex: none; padding: 15px 40px"
              >
                Back to Home
              </button>
            </div>
          </div>
        </div>

        <!-- Booking Summary Sidebar -->
        <div class="booking-summary">
          <h3 class="summary-title">Booking Summary</h3>
          <div class="summary-item">
            <div class="summary-label">Trainer</div>
            <div class="summary-value" id="summaryTrainer">-</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Specialty</div>
            <div class="summary-value" id="summarySpecialty">-</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Session Duration</div>
            <div class="summary-value" id="summaryDuration">-</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Date</div>
            <div class="summary-value" id="summaryDate">-</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Time</div>
            <div class="summary-value" id="summaryTime">-</div>
          </div>
          <div class="summary-price">
            <div class="price-breakdown">
              <div class="price-row">
                <span>Base Rate:</span>
                <span id="baseRate">₱0</span>
              </div>
              <div class="price-row">
                <span>Duration Multiplier:</span>
                <span id="multiplier">×1</span>
              </div>
            </div>
            <div style="border-top: 2px solid #e0e0e0; padding-top: 15px">
              <div style="font-size: 0.9rem; color: #666; margin-bottom: 10px">Total Price</div>
              <div class="total-price" id="totalPrice">₱0</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="loading"></div>
    <div id="pop-up"></div>

    <script type="module" src="js/header.js"></script>
    <script type="module" src="js/book-trainer-page.js"></script>
    <script type="module" src="components/selectTrainer.js"></script>
    <script type="module" src="js/payment-methods.js"></script>
    <script src="components/loading.js"></script>
  </body>
</html>