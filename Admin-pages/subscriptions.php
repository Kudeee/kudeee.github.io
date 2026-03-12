<div class="header"><h1>Subscriptions</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Active Subscriptions</h3><p class="stat-value" id="sub-active">—</p><p class="stat-status">Currently active</p></div>
  <div class="card"><h3>Monthly Revenue</h3><p class="stat-value" id="sub-revenue">—</p><p class="stat-status">From payments this month</p></div>
  <div class="card"><h3>Expiring Soon</h3><p class="stat-value" id="sub-expiring">—</p><p class="stat-status">Within 7 days</p></div>
  <div class="card"><h3>Most Popular Plan</h3><p class="stat-value" id="sub-top-plan" style="font-size:1.1rem;">—</p><p class="stat-status">By active subscribers</p></div>
</div>

<p class="section-title">Current Plans</p>
<div class="grid" id="sub-plans-grid">
  <div class="loading"><div class="spinner"></div> Loading plans…</div>
</div>

<p class="section-title" style="margin-top:24px;">Recent Subscriptions</p>
<div class="table-wrap">
  <table id="subscriptionsTable">
    <thead>
      <tr><th>Member</th><th>Plan</th><th>Billing</th><th>Start Date</th><th>Expiry</th><th>Price</th><th>Status</th></tr>
    </thead>
    <tbody><tr><td colspan="7" class="loading">Loading…</td></tr></tbody>
  </table>
</div>

<div class="pagination">
  <button onclick="changeSubPage('prev')">← Prev</button>
  <span id="subPageInfo">Page 1 of 1</span>
  <button onclick="changeSubPage('next')">Next →</button>
</div>