<div class="header">
  <h1>Trainers</h1>
</div>

<!-- Trainer Statistics -->
<div class="grid" style="margin-bottom: 20px;">
  <div class="card">
    <h3>Total Trainers</h3>
    <p class="stat-value" id="trainers-total">—</p>
    <p class="stat-status">Active staff</p>
  </div>
  <div class="card">
    <h3>Sessions (Upcoming)</h3>
    <p class="stat-value" id="trainers-sessions">—</p>
    <p class="stat-status">Across all trainers</p>
  </div>
  <div class="card">
    <h3>Avg. Rating</h3>
    <p class="stat-value" id="trainers-avg-rating">—</p>
    <p class="stat-status">Out of 5</p>
  </div>
  <div class="card">
    <h3>Top Trainer</h3>
    <p class="stat-value" id="trainers-top">—</p>
    <p class="stat-status">Most sessions</p>
  </div>
</div>

<!-- Action Bar -->
<div class="option-bar">
  <button id="addTrainerBtn">Add Trainer</button>
  <button id="exportTrainersBtn">Export Data</button>
</div>

<!-- Add Trainer Modal -->
<div id="addTrainerModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;justify-content:center;align-items:center;">
  <div style="background:#fff;border-radius:15px;padding:30px;width:620px;max-width:95vw;max-height:90vh;overflow-y:auto;">
    <h3 style="margin-bottom:20px;">Add New Trainer</h3>
    <form id="addTrainerForm">
      <input type="hidden" name="csrf_token" value="" />
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
        <div class="form">
          <label for="trainer_first_name">First Name</label>
          <input type="text" id="trainer_first_name" name="first_name" placeholder="First name" required />
        </div>
        <div class="form">
          <label for="trainer_last_name">Last Name</label>
          <input type="text" id="trainer_last_name" name="last_name" placeholder="Last name" required />
        </div>
      </div>
      <div class="form">
        <label for="trainer_email">Email</label>
        <input type="email" id="trainer_email" name="email" placeholder="trainer@societyfit.com" required />
      </div>
      <div class="form">
        <label for="trainer_phone">Phone</label>
        <input type="tel" id="trainer_phone" name="phone" placeholder="09XX-XXX-XXXX" />
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
        <div class="form">
          <label for="trainer_specialty">Specialty</label>
          <select id="trainer_specialty" name="specialty" required>
            <option value="">Select Specialty</option>
            <option value="Yoga">Yoga</option>
            <option value="HIIT">HIIT</option>
            <option value="CrossFit">CrossFit</option>
            <option value="Boxing">Boxing</option>
            <option value="Pilates">Pilates</option>
            <option value="Zumba">Zumba</option>
            <option value="Strength &amp; Conditioning">Strength &amp; Conditioning</option>
            <option value="Nutrition Coaching">Nutrition Coaching</option>
          </select>
        </div>
        <div class="form">
          <label for="trainer_rate">Session Rate (₱/hr)</label>
          <input type="number" id="trainer_rate" name="session_rate" min="0" step="50" placeholder="e.g. 800" required />
        </div>
      </div>
      <div class="form">
        <label for="trainer_bio">Bio</label>
        <textarea id="trainer_bio" name="bio" rows="3" placeholder="Short trainer bio..."></textarea>
      </div>
      <div class="form">
        <label for="trainer_status">Status</label>
        <select id="trainer_status" name="status">
          <option value="active">Active</option>
          <option value="on_leave">On Leave</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;margin-top:15px;">
        <button type="submit" style="flex:1;">Add Trainer</button>
        <button type="button" style="flex:1;background:#e5e7eb;color:#333;" onclick="closeModal('addTrainerModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Trainers Grid -->
<div class="grid" style="margin-top: 20px;" id="trainers-grid">
  <div class="card"><p style="color:#999;">Loading trainers...</p></div>
</div>