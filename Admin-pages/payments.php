<div class="header"><h1>Payments</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Revenue (Month)</h3><p class="stat-value" id="pay-total-revenue">—</p><p class="stat-status">Gross this month</p></div>
  <div class="card"><h3>Transactions</h3><p class="stat-value" id="pay-transactions">—</p><p class="stat-status">Completed this month</p></div>
  <div class="card"><h3>Failed Payments</h3><p class="stat-value" id="pay-failed">—</p><p class="stat-status">Needs attention</p></div>
  <div class="card"><h3>Pending</h3><p class="stat-value" id="pay-pending-refunds">—</p><p class="stat-status">Awaiting action</p></div>
</div>

<div class="filter-bar">
  <h3>Filter Payments</h3>
  <form id="paymentFilterForm">
    <div class="form-grid">
      <div class="form-group"><label>Member Name / Email</label><input type="text" name="member" placeholder="Search member…"/></div>
      <div class="form-group">
        <label>Type</label>
        <select name="type">
          <option value="">All Types</option>
          <option value="subscription">Subscription</option>
          <option value="class_booking">Class Booking</option>
          <option value="trainer_session">Trainer Session</option>
          <option value="event">Event</option>
          <option value="refund">Refund</option>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="">All</option>
          <option value="completed">Completed</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
          <option value="refunded">Refunded</option>
        </select>
      </div>
      <div class="form-group">
        <label>Method</label>
        <select name="method">
          <option value="">All Methods</option>
          <option value="gcash">GCash</option>
          <option value="maya">Maya</option>
          <option value="gotyme">GoTyme</option>
          <option value="card">Card</option>
        </select>
      </div>
      <div class="form-group"><label>Date From</label><input type="date" name="date_from"/></div>
      <div class="form-group"><label>Date To</label><input type="date" name="date_to"/></div>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px;">
      <button type="submit">Apply Filter</button>
      <button type="button" class="btn-secondary" onclick="document.getElementById('paymentFilterForm').reset();loadPaymentsData();">Clear</button>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table id="paymentsTable">
    <thead>
      <tr>
        <th>Transaction ID</th><th>Member</th><th>Type</th>
        <th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th>Actions</th>
      </tr>
    </thead>
    <tbody><tr><td colspan="8" class="loading">Loading…</td></tr></tbody>
  </table>
</div>

<div class="pagination">
  <button onclick="changePage('prev')">← Prev</button>
  <span id="pageInfo">Page 1 of 1</span>
  <button onclick="changePage('next')">Next →</button>
</div>
