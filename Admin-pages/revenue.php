<div class="header">
  <h1>Revenue</h1>
</div>

<!-- Key Revenue Metrics -->
<div class="grid">
  <div class="card">
    <h3>Monthly Revenue</h3>
    <p class="stat-value" id="rev-monthly">—</p>
    <p class="stat-status">Gross this month</p>
  </div>
  <div class="card">
    <h3>Quarterly Revenue</h3>
    <p class="stat-value">₱2.1M</p>
    <p class="stat-status">Q1 2026 performance</p>
  </div>
  <div class="card">
    <h3>Annual Revenue (Projected)</h3>
    <p class="stat-value">₱8.65M</p>
    <p class="stat-status">Based on current growth</p>
  </div>
  <div class="card">
    <h3>Net Profit (Month)</h3>
    <p class="stat-value" id="rev-net-profit">—</p>
    <p class="stat-status">After expenses</p>
  </div>
</div>

<!-- Revenue Breakdown -->
<div style="margin-top: 30px;">
  <h2 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; margin-bottom: 20px;">Revenue Breakdown by Type</h2>
  <div class="card">
    <div id="rev-by-type" style="margin-top:10px;">
      <p style="color:#999;">Loading breakdown...</p>
    </div>
  </div>
</div>

<!-- Monthly Comparison -->
<div class="card" style="margin-top: 30px;">
  <h3>Monthly Revenue Comparison (Last 6 Months)</h3>
  <div style="margin-top: 20px; padding: 30px; background: #f9f9f9; border-radius: 10px;">
    <div id="rev-monthly-chart" style="display:flex;justify-content:space-around;flex-wrap:wrap;gap:15px;">
      <p style="color:#999;">Loading chart data...</p>
    </div>
  </div>
</div>

<!-- Expense Overview -->
<div style="margin-top: 30px;">
  <h2 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; margin-bottom: 20px;">Expenses Overview</h2>
  <div class="grid">
    <div class="card">
      <h3>Operating Expenses</h3>
      <p class="stat-value" id="rev-expenses-ops">—</p>
      <p class="stat-status">Monthly</p>
      <div style="margin-top:15px;padding-top:15px;border-top:1px solid #e5e7eb;">
        <p style="color:#666;margin:5px 0;">Rent: ₱120,000</p>
        <p style="color:#666;margin:5px 0;">Utilities: ₱45,000</p>
        <p style="color:#666;margin:5px 0;">Maintenance: ₱35,000</p>
        <p style="color:#666;margin:5px 0;">Insurance: ₱61,000</p>
      </div>
    </div>
    <div class="card">
      <h3>Staff Salaries</h3>
      <p class="stat-value" id="rev-expenses-salaries">—</p>
      <p class="stat-status">Monthly</p>
      <div style="margin-top:15px;padding-top:15px;border-top:1px solid #e5e7eb;">
        <p style="color:#666;margin:5px 0;">Trainers (10): ₱120,000</p>
        <p style="color:#666;margin:5px 0;">Front desk (3): ₱35,000</p>
        <p style="color:#666;margin:5px 0;">Management (2): ₱25,000</p>
      </div>
    </div>
    <div class="card">
      <h3>Marketing &amp; Sales</h3>
      <p class="stat-value" id="rev-expenses-marketing">—</p>
      <p class="stat-status">Monthly</p>
      <div style="margin-top:15px;padding-top:15px;border-top:1px solid #e5e7eb;">
        <p style="color:#666;margin:5px 0;">Digital ads: ₱25,000</p>
        <p style="color:#666;margin:5px 0;">Social media: ₱10,000</p>
        <p style="color:#666;margin:5px 0;">Promotions: ₱8,268</p>
      </div>
    </div>
    <div class="card">
      <h3>Net Profit</h3>
      <p class="stat-value" id="rev-net-profit-2">—</p>
      <p class="stat-status" style="color:#4caf50;">This month</p>
      <div style="margin-top:15px;padding-top:15px;border-top:1px solid #e5e7eb;">
        <p style="color:#666;">Total Expenses: <span id="rev-total-expenses">—</span></p>
      </div>
    </div>
  </div>
</div>

<!-- Financial Goals -->
<div class="card" style="margin-top: 30px;">
  <h3>Financial Goals &amp; Targets</h3>
  <div style="margin-top: 20px;">
    <div style="margin-bottom: 20px;">
      <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        <span style="font-weight:600;">Monthly Revenue Target: ₱750,000</span>
        <span style="font-weight:600;color:#ff6b35;" id="rev-goal-pct">—</span>
      </div>
      <div style="background:#e5e7eb;height:12px;border-radius:6px;overflow:hidden;">
        <div id="rev-goal-bar" style="background:linear-gradient(135deg,#ff6b35,#ff8c5a);height:100%;width:0%;transition:width 0.8s ease;"></div>
      </div>
    </div>
    <div style="margin-bottom: 20px;">
      <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        <span style="font-weight:600;">Retention Target: 90%</span>
        <span style="font-weight:600;color:#4caf50;">89% achieved</span>
      </div>
      <div style="background:#e5e7eb;height:12px;border-radius:6px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#4caf50,#66bb6a);height:100%;width:89%;"></div>
      </div>
    </div>
  </div>
</div>