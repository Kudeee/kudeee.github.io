<div class="header"><h1>Classes</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Total Scheduled</h3><p class="stat-value" id="classes-total">—</p><p class="stat-status">All time</p></div>
  <div class="card"><h3>Today's Classes</h3><p class="stat-value" id="classes-today">—</p><p class="stat-status">Scheduled today</p></div>
  <div class="card"><h3>Upcoming</h3><p class="stat-value" id="classes-upcoming">—</p><p class="stat-status">From now on</p></div>
  <div class="card"><h3>Active Trainers</h3><p class="stat-value" id="classes-trainers">—</p><p class="stat-status">With classes</p></div>
</div>

<div class="card" style="margin-bottom:20px;">
  <h3 style="margin-bottom:16px;">Schedule New Class</h3>
  <form id="scheduleClassForm">
    <div class="form-grid">
      <div class="form-group">
        <label>Class Name</label>
        <select name="class_name" required>
          <option value="">Select Class</option>
          <option value="Yoga Flow">Yoga Flow</option>
          <option value="HIIT Blast">HIIT Blast</option>
          <option value="HIIT Circuit">HIIT Circuit</option>
          <option value="Zumba Party">Zumba Party</option>
          <option value="Zumba Saturday">Zumba Saturday</option>
          <option value="CrossFit WOD">CrossFit WOD</option>
          <option value="Kickboxing">Kickboxing</option>
          <option value="Pilates Core">Pilates Core</option>
          <option value="Strength Training">Strength Training</option>
          <option value="Bodybuilding Basics">Bodybuilding Basics</option>
          <option value="Muay Thai Cardio">Muay Thai Cardio</option>
          <option value="Morning Yoga">Morning Yoga</option>
          <option value="Mobility & Recovery">Mobility &amp; Recovery</option>
        </select>
      </div>
      <div class="form-group">
        <label>Trainer</label>
        <select name="trainer_id" id="classTrainerSelect" required>
          <option value="">Loading trainers…</option>
        </select>
      </div>
      <div class="form-group">
        <label>Date &amp; Time</label>
        <input type="datetime-local" name="class_datetime" required/>
      </div>
      <div class="form-group">
        <label>Duration (minutes)</label>
        <select name="duration_minutes">
          <option value="45">45 min</option>
          <option value="60" selected>60 min</option>
          <option value="75">75 min</option>
          <option value="90">90 min</option>
        </select>
      </div>
      <div class="form-group">
        <label>Max Participants</label>
        <input type="number" name="max_participants" value="20" min="5" max="50"/>
      </div>
      <div class="form-group">
        <label>Location</label>
        <select name="location">
          <option value="Main Studio">Main Studio</option>
          <option value="Studio B">Studio B</option>
          <option value="Weight Room">Weight Room</option>
          <option value="Functional Zone">Functional Zone</option>
          <option value="Outdoor Area">Outdoor Area</option>
        </select>
      </div>
    </div>
    <div class="form-submit-row"><button type="submit">Schedule Class</button></div>
  </form>
</div>

<p class="section-title">Upcoming Classes</p>
<div class="grid" id="upcoming-classes-grid">
  <div class="loading"><div class="spinner"></div> Loading…</div>
</div>
