<div class="header">
  <h1>Subscriptions</h1>
</div>

<!-- Subscription Statistics -->
<div class="grid" style="margin-bottom: 20px;">
  <div class="card">
    <h3>Active Subscriptions</h3>
    <p class="stat-value" id="sub-active">—</p>
    <p class="stat-status">Currently active</p>
  </div>
  <div class="card">
    <h3>Monthly Revenue</h3>
    <p class="stat-value" id="sub-revenue">—</p>
    <p class="stat-status">Monthly billing only</p>
  </div>
  <div class="card">
    <h3>Expiring Soon</h3>
    <p class="stat-value" id="sub-expiring">—</p>
    <p class="stat-status">Within 7 days</p>
  </div>
  <div class="card">
    <h3>Most Popular Plan</h3>
    <p class="stat-value" id="sub-top-plan">—</p>
    <p class="stat-status">By active subscribers</p>
  </div>
</div>

<!-- Current Plans -->
<div style="margin-top: 30px;">
  <h2 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; margin-bottom: 20px;">Current Plans</h2>
  <div class="grid">
    <div class="card" style="border-top: 4px solid #9e9e9e;">
      <h3 style="color: #9e9e9e;">Basic Plan</h3>
      <p style="font-size: 1.5rem; font-weight: 900;">₱799 <span style="font-size: 0.9rem; font-weight: 400;">/mo</span></p>
      <p style="color: #666;">₱7,999 /year</p>
      <ul style="margin: 10px 0; padding-left: 20px; color: #555;">
        <li>Gym access (6AM–10PM)</li>
        <li>Locker room access</li>
        <li>2 group classes/week</li>
        <li>Basic equipment use</li>
      </ul>
      <p><strong>Subscribers:</strong> <span id="sub-plan-count-basic">—</span></p>
      <div style="display:flex;gap:10px;margin-top:15px;">
        <button style="flex:1;padding:8px;" onclick="editPlan(1)">Edit</button>
        <button style="flex:1;padding:8px;background:#e5e7eb;color:#333;" onclick="archivePlan(1)">Archive</button>
      </div>
    </div>
    <div class="card" style="border-top: 4px solid #ff6b35;">
      <h3 style="color: #ff6b35;">Premium Plan</h3>
      <p style="font-size: 1.5rem; font-weight: 900;">₱1,299 <span style="font-size: 0.9rem; font-weight: 400;">/mo</span></p>
      <p style="color: #666;">₱12,999 /year</p>
      <ul style="margin: 10px 0; padding-left: 20px; color: #555;">
        <li>24/7 gym access</li>
        <li>Unlimited group classes</li>
        <li>1 PT session/month</li>
        <li>Sauna &amp; spa access</li>
      </ul>
      <p><strong>Subscribers:</strong> <span id="sub-plan-count-premium">—</span></p>
      <div style="display:flex;gap:10px;margin-top:15px;">
        <button style="flex:1;padding:8px;" onclick="editPlan(2)">Edit</button>
        <button style="flex:1;padding:8px;background:#e5e7eb;color:#333;" onclick="archivePlan(2)">Archive</button>
      </div>
    </div>
    <div class="card" style="border-top: 4px solid #f9a825;">
      <h3 style="color: #f9a825;">VIP Plan</h3>
      <p style="font-size: 1.5rem; font-weight: 900;">₱2,499 <span style="font-size: 0.9rem; font-weight: 400;">/mo</span></p>
      <p style="color: #666;">₱24,999 /year</p>
      <ul style="margin: 10px 0; padding-left: 20px; color: #555;">
        <li>All Premium features</li>
        <li>4 PT sessions/month</li>
        <li>Priority class booking</li>
        <li>Guest passes (2/mo)</li>
      </ul>
      <p><strong>Subscribers:</strong> <span id="sub-plan-count-vip">—</span></p>
      <div style="display:flex;gap:10px;margin-top:15px;">
        <button style="flex:1;padding:8px;" onclick="editPlan(3)">Edit</button>
        <button style="flex:1;padding:8px;background:#e5e7eb;color:#333;" onclick="archivePlan(3)">Archive</button>
      </div>
    </div>
  </div>
</div>

<!-- Recent Subscriptions Table -->
<div style="margin-top: 30px;">
  <h2 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; margin-bottom: 20px;">Recent Subscriptions</h2>
  <table id="subscriptionsTable">
    <thead>
      <tr>
        <th>Member</th>
        <th>Plan</th>
        <th>Billing</th>
        <th>Start Date</th>
        <th>Expiry Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr><td colspan="7" style="text-align:center;color:#999;padding:30px;">Loading subscriptions...</td></tr>
    </tbody>
  </table>
</div>