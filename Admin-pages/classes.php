<div class="header">
  <h1>Classes</h1>
</div>

<!-- Class Statistics -->
<div class="grid" style="margin-bottom: 20px;">
  <div class="card">
    <h3>Total Classes</h3>
    <p class="stat-value" id="classes-total">—</p>
    <p class="stat-status">Next 30 days</p>
  </div>
  <div class="card">
    <h3>Today's Classes</h3>
    <p class="stat-value" id="classes-today">—</p>
    <p class="stat-status">Scheduled</p>
  </div>
  <div class="card">
    <h3>Average Attendance</h3>
    <p class="stat-value" id="classes-avg-attend">—</p>
    <p class="stat-status">This period</p>
  </div>
  <div class="card">
    <h3>Popular Class</h3>
    <p class="stat-value">HIIT</p>
    <p class="stat-status">Most booked</p>
  </div>
</div>

<!-- Schedule New Class -->
<div class="card">
  <h3>Schedule New Class</h3>
  <form id="scheduleClassForm">
    <input type="hidden" name="csrf_token" value="" />
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
      <div class="form">
        <label for="class_type">Class Name</label>
        <select id="class_type" name="class_type" required>
          <option value="">Select Class Type</option>
          <option value="yoga_flow">Yoga Flow</option>
          <option value="hiit_training">HIIT Training</option>
          <option value="zumba">Zumba</option>
          <option value="crossfit">CrossFit</option>
          <option value="boxing">Boxing</option>
          <option value="pilates">Pilates</option>
          <option value="spin_class">Spin Class</option>
        </select>
      </div>
      <div class="form">
        <label for="trainer_id">Trainer</label>
        <select id="trainer_id" name="trainer_id" required>
          <option value="">Select Trainer</option>
          <option value="1">Ping Guerrero</option>
          <option value="2">Nadja Collins</option>
          <option value="3">Marcus Lee</option>
          <option value="4">Alyssa Moreno</option>
          <option value="5">Neil Aspag</option>
          <option value="6">Sofia Ramirez</option>
        </select>
      </div>
      <div class="form">
        <label for="class_datetime">Date &amp; Time</label>
        <input type="datetime-local" id="class_datetime" name="class_datetime" required />
      </div>
      <div class="form">
        <label for="duration_minutes">Duration (minutes)</label>
        <select id="duration_minutes" name="duration_minutes">
          <option value="30">30</option>
          <option value="45">45</option>
          <option value="50" selected>50</option>
          <option value="60">60</option>
          <option value="90">90</option>
        </select>
      </div>
      <div class="form">
        <label for="max_participants">Max Participants</label>
        <input type="number" id="max_participants" name="max_participants" value="20" min="5" max="50" required />
      </div>
      <div class="form">
        <label for="location">Studio/Location</label>
        <select id="location" name="location">
          <option value="studio_a">Studio A</option>
          <option value="studio_b">Studio B</option>
          <option value="main_gym">Main Gym</option>
          <option value="outdoor_area">Outdoor Area</option>
          <option value="boxing_ring">Boxing Ring</option>
        </select>
      </div>
    </div>
    <div class="form">
      <label for="class_description">Class Description</label>
      <textarea id="class_description" name="class_description" rows="3" placeholder="Brief description..."></textarea>
    </div>
    <div class="scheduleClass-btn">
      <button type="submit">Schedule Class</button>
    </div>
  </form>
</div>

<!-- Upcoming Classes -->
<div style="margin-top: 30px;">
  <h2 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; margin-bottom: 20px;">Upcoming Classes</h2>
  <div class="grid" id="upcoming-classes-grid">
    <div class="card"><p style="color:#999;">Loading upcoming classes...</p></div>
  </div>
</div>