// payments-page.js — loads real payment history from the API

async function loadPayments() {
  const typeFilter   = document.getElementById('paymentTypeFilter')?.value   || 'all';
  const statusFilter = document.getElementById('statusFilter')?.value        || 'all';
  const rangeFilter  = document.getElementById('dateRangeFilter')?.value     || 'all';

  const params = new URLSearchParams();
  if (typeFilter   !== 'all') params.set('type',   typeFilter);
  if (statusFilter !== 'all') params.set('status', statusFilter);
  if (rangeFilter  !== 'all') params.set('range',  rangeFilter);

  try {
    const res  = await fetch('api/user/payments/history.php?' + params);
    const data = await res.json();

    if (!data.success) {
      if (res.status === 401) window.location.href = 'login-page.php';
      return;
    }

    // Update summary cards
    const s = data.summary;
    const summaryValues = document.querySelectorAll('.summary-value');
    if (summaryValues[0]) summaryValues[0].textContent = '₱' + Number(s.total_spent   || 0).toLocaleString('en-PH');
    if (summaryValues[1]) summaryValues[1].textContent = s.total_transactions || 0;
    if (summaryValues[2]) summaryValues[2].textContent = '₱' + Number(s.this_month   || 0).toLocaleString('en-PH');

    // Render payment items
    const list = document.querySelector('.payments-list');
    if (!list) return;

    if (!data.payments?.length) {
      list.innerHTML = '<p style="text-align:center;color:#999;padding:30px;">No payment records found.</p>';
      return;
    }

    const iconMap = {
      subscription:    { css: 'membership-icon', icon: 'credit-card-fill.svg' },
      class_booking:   { css: 'class-icon',      icon: 'calendar2-plus-fill.svg' },
      trainer_session: { css: 'trainer-icon',     icon: 'person-fill.svg' },
      event:           { css: 'membership-icon',  icon: 'calendar-week-fill.svg' },
      refund:          { css: 'class-icon',        icon: 'cash.svg' },
    };

    const statusMap = {
      completed: 'status-completed',
      pending:   'status-pending',
      refunded:  'status-refunded',
      failed:    'status-pending',
    };

    list.innerHTML = data.payments.map(p => {
      const icn    = iconMap[p.type] || { css: 'membership-icon', icon: 'credit-card-fill.svg' };
      const stsCls = statusMap[p.status] || 'status-pending';
      const stsLbl = p.status === 'completed' ? '✓ Completed'
                   : p.status === 'refunded'  ? '↩ Refunded'
                   : p.status === 'failed'    ? '✗ Failed'
                   : '⏳ Pending';
      const amt    = Number(p.amount).toLocaleString('en-PH');
      const date   = new Date(p.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
      const typeLabel = (p.type || '').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

      return `
        <div class="payment-item" data-type="${p.type}">
          <div class="payment-icon ${icn.css}">
            <img src="assests/icons/${icn.icon}" alt="" />
          </div>
          <div class="payment-details">
            <div class="payment-title">${p.description || typeLabel}</div>
            <div class="payment-meta">
              <span class="payment-date">${date}</span>
              <span class="payment-separator">•</span>
              <span class="payment-method">${p.method || '—'}</span>
              <span class="payment-separator">•</span>
              <span class="payment-id">${p.transaction_id || ''}</span>
            </div>
          </div>
          <div class="payment-status">
            <span class="status-badge ${stsCls}">${stsLbl}</span>
            <div class="payment-amount">₱${amt}</div>
          </div>
        </div>`;
    }).join('');

  } catch (err) {
    console.warn('Failed to load payments:', err);
  }
}

// Bind filter changes
['paymentTypeFilter', 'dateRangeFilter', 'statusFilter'].forEach(id => {
  document.getElementById(id)?.addEventListener('change', loadPayments);
});

// Initial load
loadPayments();