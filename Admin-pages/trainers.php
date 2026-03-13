<div class="header"><h1>Trainers</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Total Trainers</h3><p class="stat-value" id="trainers-total">—</p><p class="stat-status">Active staff</p></div>
  <div class="card"><h3>Upcoming Sessions</h3><p class="stat-value" id="trainers-sessions">—</p><p class="stat-status">Across all trainers</p></div>
  <div class="card"><h3>Avg. Rating</h3><p class="stat-value" id="trainers-avg-rating">—</p><p class="stat-status">Out of 5.0</p></div>
  <div class="card"><h3>Top Trainer</h3><p class="stat-value" id="trainers-top" style="font-size:1.2rem;">—</p><p class="stat-status">Most sessions</p></div>
</div>

<div class="option-bar">
  <button id="addTrainerBtn">+ Add Trainer</button>
  <button id="manageTrainerBtn" style="background:#fff3e0;color:#e65100;border:2px solid #ffe0b2;box-shadow:none;">⚙ Manage Trainer</button>
</div>

<!-- Manage Trainer Modal -->
<div class="modal-overlay" id="manageTrainerModal">
  <div class="modal-box" style="max-width:460px;">
    <h3 style="margin-bottom:6px;">Manage Trainer</h3>
    <p style="color:#888;font-size:0.88rem;margin-bottom:18px;">Select a trainer and choose an action to perform.</p>

    <div class="form-group" style="margin-bottom:18px;">
      <label>Select Trainer</label>
      <select id="manageTrainerSelect" style="width:100%;">
        <option value="">Loading trainers…</option>
      </select>
    </div>

    <!-- Action Radio Buttons -->
    <div style="margin-bottom:20px;">
      <label style="font-weight:700;font-size:0.82rem;color:#888;text-transform:uppercase;letter-spacing:0.4px;display:block;margin-bottom:12px;">Action</label>

      <label style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:2px solid var(--border);border-radius:10px;cursor:pointer;margin-bottom:8px;transition:border-color 0.2s;" id="action-label-activate">
        <input type="radio" name="trainerAction" value="activate" style="width:auto;padding:0;margin-top:2px;accent-color:var(--green);" />
        <div>
          <p style="font-weight:700;color:#2e7d32;margin-bottom:2px;">✓ Set Active</p>
          <p style="font-size:0.82rem;color:#888;">Reactivate an inactive trainer so they appear as active staff and can be assigned to classes.</p>
        </div>
      </label>

      <label style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:2px solid var(--border);border-radius:10px;cursor:pointer;margin-bottom:8px;transition:border-color 0.2s;" id="action-label-deactivate">
        <input type="radio" name="trainerAction" value="deactivate" style="width:auto;padding:0;margin-top:2px;accent-color:#f57c00;" checked />
        <div>
          <p style="font-weight:700;color:#e65100;margin-bottom:2px;">⏸ Deactivate</p>
          <p style="font-size:0.82rem;color:#888;">Hide from active staff. Their records and history are preserved and they can be reactivated later.</p>
        </div>
      </label>

      <label style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:2px solid var(--border);border-radius:10px;cursor:pointer;transition:border-color 0.2s;" id="action-label-delete">
        <input type="radio" name="trainerAction" value="delete" style="width:auto;padding:0;margin-top:2px;accent-color:#c62828;" />
        <div>
          <p style="font-weight:700;color:#c62828;margin-bottom:2px;">✕ Permanently Delete</p>
          <p style="font-size:0.82rem;color:#888;">Irreversibly removes the trainer. Class and event references will be unlinked. Requires Super Admin role.</p>
        </div>
      </label>
    </div>

    <!-- Warning box (shown only for delete) -->
    <div id="deleteWarningBox" style="display:none;background:#ffebee;border:2px solid #ffcdd2;border-radius:10px;padding:12px 14px;margin-bottom:16px;">
      <p style="color:#c62828;font-weight:700;font-size:0.88rem;">⚠ Warning: This action cannot be undone.</p>
      <p style="color:#b71c1c;font-size:0.82rem;margin-top:4px;">All class schedule and event associations for this trainer will be removed permanently.</p>
    </div>

    <div class="modal-actions">
      <button id="confirmManageTrainerBtn">Apply</button>
      <button type="button" class="btn-secondary" onclick="closeModal('manageTrainerModal')">Cancel</button>
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