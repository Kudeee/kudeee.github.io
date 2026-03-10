<div class="header">
  <h1>Payments</h1>
</div>

<!-- Payment Statistics -->
<div class="grid" style="margin-bottom: 20px;">
  <div class="card">
    <h3>Total Revenue (Month)</h3>
    <p class="stat-value" id="pay-total-revenue">—</p>
    <p class="stat-status">Gross this month</p>
  </div>
  <div class="card">
    <h3>Transactions (Month)</h3>
    <p class="stat-value" id="pay-transactions">—</p>
    <p class="stat-status">Completed</p>
  </div>
  <div class="card">
    <h3>Failed Payments</h3>
    <p class="stat-value" id="pay-failed">—</p>
    <p class="stat-status">Needs attention</p>
  </div>
  <div class="card">
    <h3>Pending</h3>
    <p class="stat-value" id="pay-pending-refunds">—</p>
    <p class="stat-status">Awaiting action</p>
  </div>
</div>

<!-- Filter / Search Payments -->
<div class="card" style="margin-bottom: 20px;">
  <h3>Filter Payments</h3>
  <form id="paymentFilterForm">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="filter_member">Member Name / ID</label>
        <input type="text" id="filter_member" name="member" placeholder="Search member..."
          style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;" />
      </div>
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="filter_type">Payment Type</label>
        <select id="filter_type" name="type" style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;">
          <option value="">All Types</option>
          <option value="subscription">Subscription</option>
          <option value="class_booking">Class Booking</option>
          <option value="trainer_session">Trainer Session</option>
          <option value="event">Event</option>
        </select>
      </div>
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="filter_status">Status</label>
        <select id="filter_status" name="status" style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;">
          <option value="">All</option>
          <option value="completed">Completed</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
          <option value="refunded">Refunded</option>
        </select>
      </div>
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="filter_method">Payment Method</label>
        <select id="filter_method" name="method" style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;">
          <option value="">All Methods</option>
          <option value="gcash">GCash</option>
          <option value="maya">Maya</option>
          <option value="gotyme">GoTyme</option>
          <option value="card">Credit / Debit Card</option>
        </select>
      </div>
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="filter_date_from">Date From</label>
        <input type="date" id="filter_date_from" name="date_from"
          style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;" />
      </div>
      <div>
        <label style="display:block;margin-bottom:5px;font-weight:600;" for="filter_date_to">Date To</label>
        <input type="date" id="filter_date_to" name="date_to"
          style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;" />
      </div>
    </div>
    <div style="margin-top:15px;display:flex;gap:10px;">
      <button type="submit" style="padding:10px 20px;">Apply Filter</button>
      <button type="button" style="padding:10px 20px;background:#e5e7eb;color:#333;" onclick="document.getElementById('paymentFilterForm').reset()">Clear</button>
    </div>
  </form>
</div>

<!-- Action Bar -->
<div class="option-bar">
  <button id="exportPaymentsBtn">Export CSV</button>
</div>

<!-- Payments Table -->
<table id="paymentsTable" style="margin-top: 20px;">
  <thead>
    <tr>
      <th>Transaction ID</th>
      <th>Member</th>
      <th>Type</th>
      <th>Amount</th>
      <th>Method</th>
      <th>Date</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <tr><td colspan="8" style="text-align:center;color:#999;padding:30px;">Loading payments...</td></tr>
  </tbody>
</table>

<!-- Pagination -->
<div style="display:flex;justify-content:center;align-items:center;gap:10px;margin-top:20px;">
  <button style="padding:8px 16px;" onclick="changePage('prev')">Previous</button>
  <span style="font-weight:600;" id="pageInfo">Page 1 of 1</span>
  <button style="padding:8px 16px;" onclick="changePage('next')">Next</button>
</div>

<!-- Issue Refund Modal -->
<div id="refundModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;justify-content:center;align-items:center;">
  <div style="background:#fff;border-radius:15px;padding:30px;width:450px;max-width:95vw;">
    <h3 style="margin-bottom:20px;">Issue Refund</h3>
    <form id="refundForm">
      <input type="hidden" name="csrf_token" value="" />
      <input type="hidden" id="refund_transaction_id" name="payment_id" value="" />
      <div class="form">
        <label for="refund_amount">Refund Amount (₱)</label>
        <input type="number" id="refund_amount" name="amount" min="1" step="0.01" />
      </div>
      <div class="form">
        <label for="refund_reason">Reason</label>
        <select id="refund_reason" name="reason" required>
          <option value="">Select reason</option>
          <option value="Class Cancelled">Class Cancelled</option>
          <option value="Duplicate Payment">Duplicate Payment</option>
          <option value="Member Request">Member Request</option>
          <option value="Billing Error">Billing Error</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;margin-top:15px;">
        <button type="submit" style="flex:1;background:#c62828;color:#fff;">Issue Refund</button>
        <button type="button" style="flex:1;background:#e5e7eb;color:#333;" onclick="closeModal('refundModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>