<div class="header">
  <h1>Members</h1>
</div>

<!-- Search and Filter -->
<div class="card" style="margin-bottom: 20px;">
  <h3>Search &amp; Filter Members</h3>
  <form id="memberFilterForm">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="search_name">Search by Name</label>
        <input type="text" id="search_name" name="search_name" placeholder="Enter member name..."
          style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;" />
      </div>
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="status_filter">Filter by Status</label>
        <select id="status_filter" name="status_filter" style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="suspended">Suspended</option>
        </select>
      </div>
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="plan_filter">Filter by Plan</label>
        <select id="plan_filter" name="plan_filter" style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;">
          <option value="">All Plans</option>
          <option value="BASIC PLAN">Basic</option>
          <option value="PREMIUM PLAN">Premium</option>
          <option value="VIP PLAN">VIP</option>
        </select>
      </div>
    </div>
    <div style="margin-top:15px;">
      <button type="submit" style="padding:10px 20px;">Search</button>
    </div>
  </form>
</div>

<!-- Action Buttons -->
<div class="option-bar">
  <button id="addMemberBtn">Add Member</button>
  <button id="exportBtn">Export Data</button>
</div>

<!-- Add Member Modal -->
<div id="addMemberModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;justify-content:center;align-items:center;">
  <div style="background:#fff;border-radius:15px;padding:30px;width:600px;max-width:95vw;max-height:90vh;overflow-y:auto;">
    <h3 style="margin-bottom:20px;">Add New Member</h3>
    <form id="addMemberForm">
      <input type="hidden" name="csrf_token" value="" />
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
        <div class="form">
          <label for="member_first_name">First Name</label>
          <input type="text" id="member_first_name" name="first_name" placeholder="First name" required />
        </div>
        <div class="form">
          <label for="member_last_name">Last Name</label>
          <input type="text" id="member_last_name" name="last_name" placeholder="Last name" required />
        </div>
      </div>
      <div class="form">
        <label for="member_email">Email</label>
        <input type="email" id="member_email" name="email" placeholder="member@email.com" required />
      </div>
      <div class="form">
        <label for="member_phone">Phone</label>
        <input type="tel" id="member_phone" name="phone" placeholder="09XX-XXX-XXXX" />
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
        <div class="form">
          <label for="member_plan">Membership Plan</label>
          <select id="member_plan" name="plan" required>
            <option value="">Select Plan</option>
            <option value="BASIC PLAN">Basic</option>
            <option value="PREMIUM PLAN">Premium</option>
            <option value="VIP PLAN">VIP</option>
          </select>
        </div>
        <div class="form">
          <label for="member_billing">Billing Cycle</label>
          <select id="member_billing" name="billing_cycle">
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>
      </div>
      <div class="form">
        <label for="member_join_date">Join Date</label>
        <input type="date" id="member_join_date" name="join_date" required />
      </div>
      <div style="display:flex;gap:10px;margin-top:15px;">
        <button type="submit" style="flex:1;">Add Member</button>
        <button type="button" style="flex:1;background:#e5e7eb;color:#333;" onclick="closeModal('addMemberModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Member Statistics -->
<div class="grid" style="margin-bottom:20px;">
  <div class="card">
    <h3>Total Members</h3>
    <p class="stat-value" id="members-total">—</p>
  </div>
  <div class="card">
    <h3>Active Members</h3>
    <p class="stat-value" id="members-active">—</p>
  </div>
  <div class="card">
    <h3>Expiring This Month</h3>
    <p class="stat-value" id="members-expired">—</p>
  </div>
  <div class="card">
    <h3>New This Month</h3>
    <p class="stat-value" id="members-new">—</p>
  </div>
</div>

<!-- Members Table -->
<table id="membersTable">
  <thead>
    <tr>
      <th>Member ID</th>
      <th>Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Status</th>
      <th>Plan</th>
      <th>Join Date</th>
      <th>Last Payment</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <tr><td colspan="9" style="text-align:center;color:#999;padding:30px;">Loading members...</td></tr>
  </tbody>
</table>

<!-- Pagination -->
<div style="display:flex;justify-content:center;align-items:center;gap:10px;margin-top:20px;">
  <button style="padding:8px 16px;" onclick="changePage('prev')">Previous</button>
  <span style="font-weight:600;" id="pageInfo">Page 1 of 1</span>
  <button style="padding:8px 16px;" onclick="changePage('next')">Next</button>
</div>