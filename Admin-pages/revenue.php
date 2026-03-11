<div class="header"><h1>Revenue</h1></div>

<div class="grid">
  <div class="card"><h3>Monthly Revenue</h3><p class="stat-value" id="rev-monthly">—</p><p class="stat-status">Gross this month</p></div>
  <div class="card"><h3>Transactions</h3><p class="stat-value" id="rev-transactions">—</p><p class="stat-status">Completed payments</p></div>
  <div class="card"><h3>Total Expenses</h3><p class="stat-value" id="rev-total-exp">—</p><p class="stat-status">Monthly fixed costs</p></div>
  <div class="card"><h3>Net Profit</h3><p class="stat-value" id="rev-net-profit">—</p><p class="stat-status" style="color:var(--green)">This month</p></div>
</div>

<div class="two-col" style="margin-top:20px;">
  <div class="card">
    <h3 style="margin-bottom:14px;">Revenue by Type</h3>
    <div id="rev-by-type"><div class="loading"><div class="spinner"></div></div></div>
  </div>
  <div class="card">
    <h3 style="margin-bottom:14px;">Revenue by Plan</h3>
    <div id="rev-by-plan"><div class="loading"><div class="spinner"></div></div></div>
  </div>
</div>

<div class="card" style="margin-top:20px;">
  <h3 style="margin-bottom:16px;">Revenue (Last 6 Months)</h3>
  <div id="rev-monthly-chart" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;min-height:120px;">
    <div class="loading"><div class="spinner"></div></div>
  </div>
</div>

<p class="section-title" style="margin-top:24px;">Expenses Overview</p>
<div class="grid">
  <div class="card">
    <h3>Operating Expenses</h3>
    <p class="stat-value" id="rev-expenses-ops">—</p>
    <p class="stat-status">Monthly</p>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:0.88rem;color:#666;line-height:1.9;">
      <p>Rent: ₱120,000</p><p>Utilities: ₱45,000</p><p>Maintenance: ₱35,000</p><p>Insurance: ₱61,000</p>
    </div>
  </div>
  <div class="card">
    <h3>Staff Salaries</h3>
    <p class="stat-value" id="rev-expenses-salaries">—</p>
    <p class="stat-status">Monthly</p>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:0.88rem;color:#666;line-height:1.9;">
      <p>Trainers (10): ₱120,000</p><p>Front desk (3): ₱35,000</p><p>Management (2): ₱25,000</p>
    </div>
  </div>
  <div class="card">
    <h3>Marketing &amp; Sales</h3>
    <p class="stat-value" id="rev-expenses-marketing">—</p>
    <p class="stat-status">Monthly</p>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:0.88rem;color:#666;line-height:1.9;">
      <p>Digital ads: ₱25,000</p><p>Social media: ₱10,000</p><p>Promotions: ₱8,268</p>
    </div>
  </div>
  <div class="card">
    <h3>Net Profit Summary</h3>
    <p class="stat-value" id="rev-net-profit-2">—</p>
    <p class="stat-status" style="color:var(--green)">This month</p>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:0.88rem;color:#666;line-height:1.9;">
      <p>Total Expenses: <span id="rev-total-expenses">—</span></p>
    </div>
  </div>
</div>

<div class="card" style="margin-top:20px;">
  <h3 style="margin-bottom:16px;">Monthly Revenue Goal</h3>
  <div style="margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-weight:600;">
      <span>Target: ₱750,000</span>
      <span style="color:var(--primary);" id="rev-goal-pct">—</span>
    </div>
    <div class="progress-bar-track"><div class="progress-bar-fill" id="rev-goal-bar" style="width:0%"></div></div>
  </div>
  <div>
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-weight:600;">
      <span>Retention Target: 90%</span>
      <span style="color:var(--green)">89% achieved</span>
    </div>
    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:89%;background:linear-gradient(135deg,#4caf50,#66bb6a)"></div></div>
  </div>
</div>
