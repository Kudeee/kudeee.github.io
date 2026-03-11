<div class="header"><h1>Members</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Total Members</h3><p class="stat-value" id="members-total">—</p></div>
  <div class="card"><h3>Active Members</h3><p class="stat-value" id="members-active">—</p></div>
  <div class="card"><h3>Expiring This Month</h3><p class="stat-value" id="members-expired">—</p></div>
  <div class="card"><h3>New This Month</h3><p class="stat-value" id="members-new">—</p></div>
</div>

<div class="filter-bar">
  <h3>Search &amp; Filter</h3>
  <form id="memberFilterForm">
    <div class="form-grid">
      <div class="form-group">
        <label>Search by Name / Email</label>
        <input type="text" id="search_name" name="search_name" placeholder="Enter name or email…"/>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select id="status_filter" name="status_filter">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="suspended">Suspended</option>
        </select>
      </div>
      <div class="form-group">
        <label>Plan</label>
        <select id="plan_filter" name="plan_filter">
          <option value="">All Plans</option>
          <option value="BASIC PLAN">Basic</option>
          <option value="PREMIUM PLAN">Premium</option>
          <option value="VIP PLAN">VIP</option>
        </select>
      </div>
      <div class="form-group" style="justify-content:flex-end;padding-top:22px;">
        <button type="submit">Search</button>
      </div>
    </div>
  </form>
</div>

<div class="option-bar">
  <button id="addMemberBtn">+ Add Member</button>
</div>

<div class="table-wrap">
  <table id="membersTable">
    <thead>
      <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Phone</th>
        <th>Plan</th><th>Status</th><th>Join Date</th><th>Expiry</th><th>Last Payment</th><th>Actions</th>
      </tr>
    </thead>
    <tbody><tr><td colspan="10" class="loading">Loading…</td></tr></tbody>
  </table>
</div>

<div class="pagination">
  <button onclick="changePage('prev')">← Prev</button>
  <span id="pageInfo">Page 1 of 1</span>
  <button onclick="changePage('next')">Next →</button>
</div>

<!-- Add Member Modal -->
<div class="modal-overlay" id="addMemberModal">
  <div class="modal-box">
    <h3>Add New Member</h3>
    <form id="addMemberForm">
      <div class="form-grid">
        <div class="form-group"><label>First Name</label><input type="text" name="first_name" required placeholder="First name"/></div>
        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required placeholder="Last name"/></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="email@example.com"/></div>
        <div class="form-group"><label>Phone</label><input type="tel" name="phone" placeholder="09XX-XXX-XXXX"/></div>
        <div class="form-group">
          <label>Plan</label>
          <select name="plan" required>
            <option value="BASIC PLAN">Basic (₱499/mo)</option>
            <option value="PREMIUM PLAN">Premium (₱899/mo)</option>
            <option value="VIP PLAN">VIP (₱1,499/mo)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Billing Cycle</label>
          <select name="billing_cycle">
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly (10% off)</option>
          </select>
        </div>
        <div class="form-group"><label>Join Date</label><input type="date" name="join_date" required/></div>
      </div>
      <div class="modal-actions">
        <button type="submit">Add Member</button>
        <button type="button" class="btn-secondary" onclick="closeModal('addMemberModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Member Modal -->
<div class="modal-overlay" id="editMemberModal">
  <div class="modal-box">
    <h3>Edit Member</h3>
    <form id="editMemberForm">
      <input type="hidden" id="edit_member_id" name="id"/>
      <div class="form-grid">
        <div class="form-group"><label>First Name</label><input type="text" id="edit_first_name" name="first_name" required/></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="edit_last_name" name="last_name" required/></div>
        <div class="form-group"><label>Email</label><input type="email" id="edit_email" name="email" required/></div>
        <div class="form-group"><label>Phone</label><input type="tel" id="edit_phone" name="phone"/></div>
        <div class="form-group">
          <label>Status</label>
          <select id="edit_status" name="status">
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>
        <div class="form-group">
          <label>Plan</label>
          <select id="edit_plan" name="plan">
            <option value="BASIC PLAN">Basic Plan</option>
            <option value="PREMIUM PLAN">Premium Plan</option>
            <option value="VIP PLAN">VIP Plan</option>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit">Save Changes</button>
        <button type="button" class="btn-secondary" onclick="closeModal('editMemberModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>
