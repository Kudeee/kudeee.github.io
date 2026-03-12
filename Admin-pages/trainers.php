<div class="header"><h1>Trainers</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Total Trainers</h3><p class="stat-value" id="trainers-total">—</p><p class="stat-status">Active staff</p></div>
  <div class="card"><h3>Upcoming Sessions</h3><p class="stat-value" id="trainers-sessions">—</p><p class="stat-status">Across all trainers</p></div>
  <div class="card"><h3>Avg. Rating</h3><p class="stat-value" id="trainers-avg-rating">—</p><p class="stat-status">Out of 5.0</p></div>
  <div class="card"><h3>Top Trainer</h3><p class="stat-value" id="trainers-top" style="font-size:1.2rem;">—</p><p class="stat-status">Most sessions</p></div>
</div>

<div class="option-bar">
  <button id="addTrainerBtn">+ Add Trainer</button>
</div>

<!-- Delete Trainer Modal -->
<div class="modal-overlay" id="deleteTrainerModal">
  <div class="modal-box" style="max-width:420px;">
    <h3 style="color:#c62828;">Delete Trainer</h3>
    <p style="color:#555;margin-bottom:16px;">Select a trainer to deactivate. They will no longer appear as active staff.</p>
    <div class="form-group" style="margin-bottom:16px;">
      <label>Select Trainer</label>
      <select id="deleteTrainerSelect">
        <option value="">Loading trainers…</option>
      </select>
    </div>
    <div class="modal-actions">
      <button id="confirmDeleteTrainerBtn" class="btn-danger">Deactivate Trainer</button>
      <button type="button" class="btn-secondary" onclick="closeModal('deleteTrainerModal')">Cancel</button>
    </div>
  </div>
</div>

<div class="grid" id="trainers-grid">
  <div class="loading"><div class="spinner"></div> Loading…</div>
</div>

<!-- Add Trainer Modal -->
<div class="modal-overlay" id="addTrainerModal">
  <div class="modal-box">
    <h3>Add New Trainer</h3>
    <form id="addTrainerForm">
      <div class="form-grid">
        <div class="form-group"><label>First Name</label><input type="text" name="first_name" required placeholder="First name"/></div>
        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required placeholder="Last name"/></div>
        <div class="form-group">
          <label>Specialty</label>
          <select name="specialty" required>
            <option value="">Select Specialty</option>
            <option>Yoga</option><option>HIIT</option><option>CrossFit</option>
            <option>Boxing</option><option>Muay Thai</option><option>Pilates</option>
            <option>Zumba</option><option>Strength & Conditioning</option>
            <option>Bodybuilding</option><option>Nutrition Coaching</option>
          </select>
        </div>
        <div class="form-group"><label>Session Rate (₱/hr)</label><input type="number" name="session_rate" min="0" step="50" placeholder="e.g. 800"/></div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Bio</label>
          <textarea name="bio" placeholder="Short trainer bio…"></textarea>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit">Add Trainer</button>
        <button type="button" class="btn-secondary" onclick="closeModal('addTrainerModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>
