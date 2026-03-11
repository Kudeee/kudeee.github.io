<div class="header"><h1>Subscriptions</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Active Subscriptions</h3><p class="stat-value" id="sub-active">—</p><p class="stat-status">Currently active</p></div>
  <div class="card"><h3>Monthly Revenue</h3><p class="stat-value" id="sub-revenue">—</p><p class="stat-status">From payments this month</p></div>
  <div class="card"><h3>Expiring Soon</h3><p class="stat-value" id="sub-expiring">—</p><p class="stat-status">Within 7 days</p></div>
  <div class="card"><h3>Most Popular Plan</h3><p class="stat-value" id="sub-top-plan" style="font-size:1.1rem;">—</p><p class="stat-status">By active subscribers</p></div>
</div>

<p class="section-title">Current Plans</p>
<div class="grid">
  <div class="card" style="border-top:4px solid #9e9e9e;">
    <h3 style="color:#9e9e9e;">Basic Plan</h3>
    <p style="font-size:1.5rem;font-weight:900;">₱499 <span style="font-size:0.85rem;font-weight:400;">/mo</span></p>
    <p style="color:#888;margin-top:2px;">₱5,389 /year</p>
    <ul style="margin:12px 0;padding-left:18px;color:#555;font-size:0.9rem;line-height:1.8;">
      <li>Gym access (6AM–10PM)</li>
      <li>Locker room access</li>
      <li>2 group classes/week</li>
    </ul>
    <p style="font-weight:700;">Subscribers: <span id="sub-plan-count-basic" style="color:var(--primary);">—</span></p>
  </div>
  <div class="card" style="border-top:4px solid var(--primary);">
    <h3 style="color:var(--primary);">Premium Plan</h3>
    <p style="font-size:1.5rem;font-weight:900;">₱899 <span style="font-size:0.85rem;font-weight:400;">/mo</span></p>
    <p style="color:#888;margin-top:2px;">₱9,709 /year</p>
    <ul style="margin:12px 0;padding-left:18px;color:#555;font-size:0.9rem;line-height:1.8;">
      <li>24/7 gym access</li>
      <li>Unlimited group classes</li>
      <li>1 PT session/month</li>
    </ul>
    <p style="font-weight:700;">Subscribers: <span id="sub-plan-count-premium" style="color:var(--primary);">—</span></p>
  </div>
  <div class="card" style="border-top:4px solid #f9a825;">
    <h3 style="color:#f9a825;">VIP Plan</h3>
    <p style="font-size:1.5rem;font-weight:900;">₱1,499 <span style="font-size:0.85rem;font-weight:400;">/mo</span></p>
    <p style="color:#888;margin-top:2px;">₱16,189 /year</p>
    <ul style="margin:12px 0;padding-left:18px;color:#555;font-size:0.9rem;line-height:1.8;">
      <li>All Premium features</li>
      <li>4 PT sessions/month</li>
      <li>Priority class booking</li>
    </ul>
    <p style="font-weight:700;">Subscribers: <span id="sub-plan-count-vip" style="color:var(--primary);">—</span></p>
  </div>
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
