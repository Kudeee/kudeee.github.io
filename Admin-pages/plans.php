<div class="header"><h1>Subscription Plans</h1></div>

<!-- Stats row -->
<div class="grid" style="margin-bottom:20px;" id="plans-stats-grid">
  <div class="card"><h3>Active Plans</h3><p class="stat-value" id="plans-active-count">—</p><p class="stat-status">Enabled plans</p></div>
  <div class="card"><h3>Basic Subscribers</h3><p class="stat-value" id="plans-basic-count">—</p><p class="stat-status">Active members</p></div>
  <div class="card"><h3>Premium Subscribers</h3><p class="stat-value" id="plans-premium-count">—</p><p class="stat-status">Active members</p></div>
  <div class="card"><h3>VIP Subscribers</h3><p class="stat-value" id="plans-vip-count">—</p><p class="stat-status">Active members</p></div>
</div>

<!-- Plan cards -->
<p class="section-title">Edit Plans</p>
<div id="plans-edit-grid" class="grid">
  <div class="loading"><div class="spinner"></div> Loading…</div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editPlanModal">
  <div class="modal-box" style="width:680px;">
    <h3>Edit Subscription Plan</h3>
    <form id="editPlanForm">
      <input type="hidden" id="ep_plan" name="plan"/>

      <div class="form-grid">
        <!-- Plan name (read-only) -->
        <div class="form-group" style="grid-column:1/-1">
          <label>Plan Name</label>
          <input type="text" id="ep_plan_label" disabled style="background:#f5f5f5;color:#666;"/>
        </div>

        <div class="form-group">
          <label>Monthly Price (₱)</label>
          <input type="number" id="ep_monthly" name="monthly_price" min="1" step="1" required/>
        </div>
        <div class="form-group">
          <label>Yearly Price (₱)</label>
          <input type="number" id="ep_yearly" name="yearly_price" min="1" step="1" required/>
          <small style="color:#888;">Tip: yearly = monthly × 12 × 0.84 for 16% savings</small>
        </div>

        <div class="form-group">
          <label>Accent Color</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" id="ep_color" name="color" style="width:50px;height:42px;padding:2px;border-radius:8px;border:2px solid var(--border);cursor:pointer;"/>
            <input type="text" id="ep_color_hex" placeholder="#ff6b35" style="flex:1;" oninput="syncColorHex(this.value)"/>
          </div>
        </div>
        <div class="form-group">
          <label>Plan Status</label>
          <select id="ep_active" name="is_active">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>

        <div class="form-group">
          <label>Max Group Classes / Week <small style="color:#888;">(-1 = unlimited)</small></label>
          <input type="number" id="ep_max_classes" name="max_classes" min="-1" step="1"/>
        </div>
        <div class="form-group">
          <label>PT Sessions / Month</label>
          <input type="number" id="ep_pt_sessions" name="pt_sessions" min="0" step="1"/>
        </div>
        <div class="form-group">
          <label>Free Guest Passes / Month</label>
          <input type="number" id="ep_guest_passes" name="guest_passes" min="0" step="1"/>
        </div>

        <!-- Benefits list -->
        <div class="form-group" style="grid-column:1/-1">
          <label>Benefits <small style="color:#888;font-weight:400;">(one per line)</small></label>
          <textarea id="ep_benefits" name="benefits_raw" style="min-height:130px;resize:vertical;" placeholder="e.g.&#10;24/7 gym access&#10;Unlimited group classes"></textarea>
        </div>

        <!-- Live preview -->
        <div class="form-group" style="grid-column:1/-1">
          <label>Live Preview</label>
          <div id="ep_preview" style="border:3px solid #e5e7eb;border-radius:14px;padding:20px;background:#fafafa;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <h3 id="prev_name" style="margin:0;font-size:1rem;font-weight:800;text-transform:uppercase;">—</h3>
              <span id="prev_status" style="padding:4px 12px;border-radius:12px;font-size:0.78rem;font-weight:700;"></span>
            </div>
            <p id="prev_price" style="font-size:2rem;font-weight:900;margin-bottom:12px;">—</p>
            <ul id="prev_benefits" style="padding-left:18px;color:#555;font-size:0.9rem;line-height:1.9;"></ul>
          </div>
        </div>
      </div>

      <div class="modal-actions">
        <button type="submit">Save Changes</button>
        <button type="button" class="btn-secondary" onclick="closeModal('editPlanModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>