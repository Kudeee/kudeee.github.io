<div class="header">
  <h1>Dashboard</h1>
  <div class="header-right">
    <div class="user-box" id="dashAdminName">Admin</div>
  </div>
</div>

<div class="grid">
  <div class="card">
    <h3>Total Members</h3>
    <p class="stat-value" id="dash-total-members">—</p>
    <p class="stat-status">All registered</p>
  </div>
  <div class="card">
    <h3>Active Subscriptions</h3>
    <p class="stat-value" id="dash-active-subs">—</p>
    <p class="stat-status">Currently active</p>
  </div>
  <div class="card">
    <h3>Monthly Revenue</h3>
    <p class="stat-value" id="dash-monthly-revenue">—</p>
    <p class="stat-status">This month</p>
  </div>
  <div class="card">
    <h3>Classes This Month</h3>
    <p class="stat-value" id="dash-classes-today">—</p>
    <p class="stat-status">Scheduled sessions</p>
  </div>
</div>

<div class="grid">
  <div class="card">
    <h3>New Members</h3>
    <p class="stat-value" id="dash-new-members">—</p>
    <p class="stat-status">This month</p>
  </div>
  <div class="card">
    <h3>Active Trainers</h3>
    <p class="stat-value" id="dash-active-trainers">—</p>
    <p class="stat-status">All certified</p>
  </div>
  <div class="card">
    <h3>Expiring Soon</h3>
    <p class="stat-value" id="dash-expiring">—</p>
    <p class="stat-status">Within 7 days</p>
  </div>
  <div class="card">
    <h3>Retention Rate</h3>
    <p class="stat-value">89%</p>
    <p class="stat-status">↑ 2% from last month</p>
  </div>
</div>

<div class="two-col">
  <div class="card">
    <h3 style="margin-bottom:14px;">Recent Activity</h3>
    <div id="dash-recent-activity"><div class="loading"><div class="spinner"></div> Loading...</div></div>
  </div>
  <div class="card">
    <h3 style="margin-bottom:14px;">Quick Actions</h3>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <button onclick="loadPage('members')">➕ Add Member</button>
      <button onclick="loadPage('classes')">📅 Schedule Class</button>
      <button class="btn-secondary" onclick="loadPage('revenue')">📊 View Revenue</button>
      <button class="btn-secondary" onclick="loadPage('payments')">💳 View Payments</button>
    </div>
  </div>
</div>

<div style="margin-top:20px;">
  <p class="section-title">Membership Distribution</p>
  <div class="grid" id="dash-plan-dist"><div class="loading"><div class="spinner"></div></div></div>
</div>

<div class="card" style="margin-top:20px;">
  <h3 style="margin-bottom:16px;">Revenue (Last 6 Months)</h3>
  <div id="dash-monthly-chart" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;min-height:100px;">
    <div class="loading"><div class="spinner"></div></div>
  </div>
</div>
