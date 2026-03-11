/**
 * admin-js.js — Society Fitness Admin Panel
 * Fully wired to real DB schema (society_fitness)
 */

// ─── Session Check ───────────────────────────────────────────────────────────
async function checkAdminSession() {
  try {
    const res = await fetch('api/admin/auth/check-session.php');
    if (res.status === 401 || res.status === 403) {
      window.location.href = 'login-page.php';
    }
  } catch (e) {
    console.warn('Session check failed:', e);
  }
}
checkAdminSession();

// ─── Page Loader ─────────────────────────────────────────────────────────────
const pageMap = {
  dashboard:     'Admin-pages/dashboard.php',
  members:       'Admin-pages/members.php',
  classes:       'Admin-pages/classes.php',
  trainers:      'Admin-pages/trainers.php',
  subscriptions: 'Admin-pages/subscriptions.php',
  payments:      'Admin-pages/payments.php',
  events:        'Admin-pages/events.php',
  revenue:       'Admin-pages/revenue.php',
  roles:         'Admin-pages/roles.php',
  plans:         'Admin-pages/plans.php',   // ← NEW
};

let currentPage = 'dashboard';

async function loadPage(pageName) {
  const path = pageMap[pageName];
  if (!path) return;
  currentPage = pageName;

  const container = document.getElementById('content');
  if (!container) return;
  container.innerHTML = '<div class="loading"><div class="spinner"></div> Loading…</div>';

  try {
    const res = await fetch(path);
    if (!res.ok) throw new Error('Failed to load ' + path);
    container.innerHTML = await res.text();

    document.querySelectorAll('.sidebar .nav a').forEach(a => {
      a.classList.toggle('active', a.dataset.page === pageName);
    });

    bindModalTriggers();
    bindFormHandlers();
    await fetchPageData(pageName);
  } catch (err) {
    container.innerHTML = '<div class="card"><p style="color:red;">Failed to load page. Please try again.</p></div>';
    console.error(err);
  }
}

window.loadPage = loadPage;

async function fetchPageData(page) {
  try {
    switch (page) {
      case 'dashboard':     await loadDashboardData();     break;
      case 'members':       await loadMembersData();       break;
      case 'classes':       await loadClassesData();       break;
      case 'trainers':      await loadTrainersData();      break;
      case 'payments':      await loadPaymentsData();      break;
      case 'events':        await loadEventsData();        break;
      case 'revenue':       await loadRevenueData();       break;
      case 'subscriptions': await loadSubscriptionsData(); break;
      case 'roles':         await loadRolesData();         break;
      case 'plans':         await loadPlansData();         break;  // ← NEW
    }
  } catch (err) {
    console.warn('fetchPageData error for', page, err);
  }
}

// ─── DASHBOARD ────────────────────────────────────────────────────────────────
async function loadDashboardData() {
  const res  = await fetch('api/admin/reports/dashboard.php');
  const data = await res.json();
  if (!data.success) return;

  setText('dash-total-members',   data.members?.total          ?? '—');
  setText('dash-active-subs',     data.subscriptions?.active   ?? '—');
  setText('dash-monthly-revenue', phpFormat(data.revenue?.net  ?? 0));
  setText('dash-classes-today',   data.classes?.scheduled      ?? '—');
  setText('dash-new-members',     data.members?.new_this_period ?? '—');
  setText('dash-active-trainers', data.top_trainers            ?? '—');
  setText('dash-expiring',        data.subscriptions?.expiring_soon ?? '—');

  const nameEl = document.getElementById('dashAdminName');
  if (nameEl) nameEl.textContent = window.ADMIN_NAME || 'Admin';

  const actEl = document.getElementById('dash-recent-activity');
  if (actEl) {
    if (data.recent_activity?.length) {
      actEl.innerHTML = data.recent_activity.map(a => {
        const labels = {
          member_suspended: 'Member suspended',
          member_unsuspended: 'Member unsuspended',
          trainer_added: 'Trainer added',
          event_created: 'Event created',
          class_cancelled: 'Class cancelled',
          member_created: 'New member registered',
          refund_issued: 'Refund issued',
          plan_updated: 'Subscription plan updated',
        };
        const label = labels[a.action] || a.action.replace(/_/g, ' ');
        const time  = fmtDateTime(a.created_at);
        return `<div class="activity-item"><div class="activity-dot"></div><div><span>${esc(label)}</span><br><span class="activity-time">${esc(a.admin_name || 'System')} — ${time}</span></div></div>`;
      }).join('');
    } else {
      actEl.innerHTML = '<p style="color:#999;">No recent activity.</p>';
    }
  }

  const planGrid = document.getElementById('dash-plan-dist');
  if (planGrid && data.plan_distribution?.length) {
    planGrid.innerHTML = data.plan_distribution.map(p => `
      <div class="card">
        <h3>${esc(p.plan)}</h3>
        <p class="stat-value">${p.cnt}</p>
        <p class="stat-status">Active members</p>
      </div>`).join('');
  }

  const chartEl = document.getElementById('dash-monthly-chart');
  if (chartEl && data.revenue?.monthly_chart?.length) {
    const max = Math.max(...data.revenue.monthly_chart.map(m => parseFloat(m.total) || 0)) || 1;
    chartEl.innerHTML = data.revenue.monthly_chart.map(m => {
      const h = Math.round((parseFloat(m.total) / max) * 120);
      return `
        <div style="text-align:center;flex:1;min-width:70px;">
          <p style="font-size:0.8rem;font-weight:700;color:var(--primary);margin-bottom:6px;">₱${numShort(m.total)}</p>
          <div style="height:${h}px;background:linear-gradient(180deg,var(--primary),var(--primary-light));border-radius:6px 6px 0 0;min-height:4px;"></div>
          <p style="font-size:0.78rem;color:#888;margin-top:6px;">${esc(m.label)}</p>
        </div>`;
    }).join('');
  }
}

// ─── MEMBERS ─────────────────────────────────────────────────────────────────
let membersPage = 1;
let membersFilters = {};
window._membersPageTotal = 1;

async function loadMembersData(page = 1, filters = {}) {
  membersPage    = page;
  membersFilters = filters;

  const params = new URLSearchParams({ page, per_page: 15, ...filters });
  const res    = await fetch('api/admin/members/list.php?' + params);
  const data   = await res.json();
  if (!data.success) return;

  setText('members-total',   data.summary?.total_members        ?? '—');
  setText('members-active',  data.summary?.active               ?? '—');
  setText('members-expired', data.summary?.expiring_this_month  ?? '—');
  setText('members-new',     data.summary?.new_this_month       ?? '—');

  const tbody = document.querySelector('#membersTable tbody');
  if (!tbody) return;

  if (!data.members?.length) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:30px;color:#999;">No members found.</td></tr>';
    return;
  }

  tbody.innerHTML = data.members.map(m => `
    <tr>
      <td style="font-family:monospace;font-size:0.82rem;">#M${m.id}</td>
      <td><strong>${esc(m.first_name + ' ' + m.last_name)}</strong></td>
      <td style="color:#666;font-size:0.88rem;">${esc(m.email)}</td>
      <td>${esc(m.phone || '—')}</td>
      <td><span class="tag" style="background:#e3f2fd;color:#1565c0;">${esc(m.plan || '—')}</span></td>
      <td>${badge(m.status)}</td>
      <td>${fmtDate(m.join_date)}</td>
      <td style="font-size:0.88rem;">${m.expiry_date ? fmtDate(m.expiry_date) : '—'}</td>
      <td style="font-size:0.88rem;">${m.last_payment_date ? fmtDate(m.last_payment_date) : '—'}</td>
      <td>
        <button class="btn-sm" onclick="openEditMember(${m.id},'${esc(m.first_name)}','${esc(m.last_name)}','${esc(m.email)}','${esc(m.phone||'')}','${esc(m.status)}','${esc(m.plan||'')}')">Edit</button>
      </td>
    </tr>`).join('');

  const pg = data.pagination;
  window._membersPageTotal = pg.total_pages;
  setText('pageInfo', `Page ${pg.page} of ${pg.total_pages}`);
}

window.openEditMember = function(id, fn, ln, email, phone, status, plan) {
  document.getElementById('edit_member_id').value    = id;
  document.getElementById('edit_first_name').value   = fn;
  document.getElementById('edit_last_name').value    = ln;
  document.getElementById('edit_email').value        = email;
  document.getElementById('edit_phone').value        = phone;
  document.getElementById('edit_status').value       = status;
  document.getElementById('edit_plan').value         = plan;
  openModal('editMemberModal');
};

// ─── CLASSES ─────────────────────────────────────────────────────────────────
async function loadClassesData() {
  const res  = await fetch('api/admin/classes/list.php?per_page=40');
  const data = await res.json();
  if (!data.success) return;

  setText('classes-total',    data.stats?.scheduled ?? '—');
  setText('classes-today',    data.stats?.today     ?? '—');
  setText('classes-upcoming', data.stats?.upcoming  ?? '—');

  const trainerIds = [...new Set((data.classes || []).map(c => c.trainer_id).filter(Boolean))];
  setText('classes-trainers', trainerIds.length || '—');

  await populateTrainerSelect('classTrainerSelect');

  const grid = document.getElementById('upcoming-classes-grid');
  if (!grid) return;

  const upcoming = (data.classes || []).filter(c => new Date(c.scheduled_at) >= new Date() && c.status === 'active');

  if (!upcoming.length) {
    grid.innerHTML = '<div class="card"><p style="color:#999;">No upcoming classes.</p></div>';
    return;
  }

  grid.innerHTML = upcoming.map(c => {
    const fill = parseInt(c.current_participants) || 0;
    const max  = parseInt(c.max_participants)     || 20;
    const pct  = max > 0 ? Math.round((fill / max) * 100) : 0;
    const fillClr = pct >= 100 ? '#c62828' : pct >= 75 ? '#f57c00' : '#1565c0';
    return `
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
          <h3 style="margin:0;color:var(--primary);font-size:1rem;">${esc(c.class_name)}</h3>
          <span style="background:${fillClr}22;color:${fillClr};padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700;">${fill}/${max}</span>
        </div>
        <p style="font-size:0.88rem;color:#555;margin-bottom:5px;">👤 ${esc(c.trainer_name || '—')}</p>
        <p style="font-size:0.88rem;color:#555;margin-bottom:5px;">📅 ${fmtDateTime(c.scheduled_at)}</p>
        <p style="font-size:0.88rem;color:#555;margin-bottom:5px;">⏱ ${c.duration_minutes} min · 📍 ${esc(c.location || '—')}</p>
        <div style="margin-top:12px;height:6px;background:var(--border);border-radius:3px;">
          <div style="width:${Math.min(pct,100)}%;height:100%;background:${fillClr};border-radius:3px;transition:width 0.5s;"></div>
        </div>
        <div style="margin-top:12px;display:flex;gap:8px;">
          <button class="btn-sm btn-secondary" onclick="cancelClass(${c.id})">Cancel</button>
        </div>
      </div>`;
  }).join('');
}

window.cancelClass = async function(classId) {
  if (!confirm('Cancel this class?')) return;
  const res  = await apiFetch('api/admin/classes/cancel.php', { id: classId });
  if (res?.success) { toast('Class cancelled.'); loadClassesData(); }
  else toast(res?.message || 'Failed.', 'error');
};

// ─── TRAINERS ─────────────────────────────────────────────────────────────────
async function loadTrainersData() {
  const res  = await fetch('api/admin/trainers/list.php?per_page=50');
  const data = await res.json();
  if (!data.success) return;

  const trainers = data.trainers || [];
  setText('trainers-total',    data.summary?.total ?? trainers.length);
  setText('trainers-sessions', trainers.reduce((s, t) => s + (parseInt(t.upcoming_sessions)||0), 0));

  const avgR = trainers.length
    ? (trainers.reduce((s,t) => s + parseFloat(t.rating||0), 0) / trainers.length).toFixed(1)
    : '—';
  setText('trainers-avg-rating', avgR);

  const top = [...trainers].sort((a,b) => (b.total_sessions||0)-(a.total_sessions||0))[0];
  setText('trainers-top', top ? top.first_name + ' ' + top.last_name : '—');

  const grid = document.getElementById('trainers-grid');
  if (!grid) return;

  if (!trainers.length) {
    grid.innerHTML = '<div class="card"><p style="color:#999;">No trainers found.</p></div>';
    return;
  }

  grid.innerHTML = trainers.map(t => {
    const name     = esc(t.first_name + ' ' + t.last_name);
    const tags     = Array.isArray(t.specialty_tags) ? t.specialty_tags : [];
    const avail    = t.availability === 'available'
      ? '<span style="color:var(--green);font-weight:700;">Available</span>'
      : '<span style="color:var(--orange);font-weight:700;">Limited</span>';
    const initials = (t.first_name?.[0]||'?') + (t.last_name?.[0]||'');
    return `
      <div class="card">
        <div class="trainer-card-header">
          <div class="trainer-avatar" style="background:var(--primary);">${initials.toUpperCase()}</div>
          <div>
            <h3 style="margin:0;font-size:1rem;">${name}</h3>
            <p style="color:var(--primary);font-weight:600;font-size:0.88rem;">${esc(t.specialty || '—')}</p>
          </div>
        </div>
        <div style="font-size:0.88rem;color:#555;margin-bottom:10px;line-height:2;">
          <p>⭐ ${parseFloat(t.rating||0).toFixed(1)} · ${t.exp_years || 0} yrs exp · ${t.client_count || 0} clients</p>
          <p>💰 ₱${numFormat(t.session_rate)}/hr</p>
          <p>📅 ${t.upcoming_sessions || 0} upcoming · ${avail}</p>
        </div>
        ${tags.length ? '<div>' + tags.slice(0,3).map(tag => `<span class="tag">${esc(tag)}</span>`).join('') + '</div>' : ''}
        <div style="margin-top:12px;">
          <span class="${t.status === 'active' ? 'badge badge-active' : 'badge badge-expired'}">${ucFirst(t.status)}</span>
        </div>
      </div>`;
  }).join('');
}

// ─── PAYMENTS ─────────────────────────────────────────────────────────────────
let paymentsPage = 1;
window._paymentsPageTotal = 1;

async function loadPaymentsData(page = 1, extraParams = {}) {
  paymentsPage = page;
  const from  = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
  const to    = new Date().toISOString().split('T')[0];
  const params = new URLSearchParams({ page, per_page: 15, date_from: from, date_to: to, ...extraParams });

  const res  = await fetch('api/admin/payments/list.php?' + params);
  const data = await res.json();
  if (!data.success) return;

  const t = data.totals || {};
  setText('pay-total-revenue',   phpFormat(t.gross_revenue       ?? 0));
  setText('pay-transactions',    t.total_transactions            ?? 0);
  setText('pay-failed',          t.failed_count                  ?? 0);
  setText('pay-pending-refunds', t.pending_count                 ?? 0);

  const tbody = document.querySelector('#paymentsTable tbody');
  if (!tbody) return;

  if (!data.payments?.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:#999;">No payments found.</td></tr>';
    return;
  }

  tbody.innerHTML = data.payments.map(p => `
    <tr>
      <td style="font-family:monospace;font-size:0.82rem;">${esc(p.transaction_id)}</td>
      <td><strong>${esc(p.member_name)}</strong><br><span style="color:#888;font-size:0.8rem;">${esc(p.member_email)}</span></td>
      <td><span class="tag">${ucFirst((p.type||'').replace('_',' '))}</span></td>
      <td style="font-weight:700;color:var(--primary);">₱${numFormat(p.amount)}</td>
      <td>${ucFirst(p.method || '—')}</td>
      <td style="font-size:0.88rem;">${fmtDate(p.created_at)}</td>
      <td>${badge(p.status)}</td>
      <td><button class="btn-sm btn-secondary" onclick="viewPayment(${p.id},'${esc(p.member_name)}','${p.amount}','${p.status}')">View</button></td>
    </tr>`).join('');

  const pg = data.pagination;
  window._paymentsPageTotal = pg.total_pages;
  setText('pageInfo', `Page ${pg.page} of ${pg.total_pages}`);
}

window.viewPayment = function(id, member, amount, status) {
  toast(`Payment #${id} — ${member}: ₱${numFormat(amount)} (${status})`);
};

// ─── SUBSCRIPTIONS ────────────────────────────────────────────────────────────
let subPage = 1;
window._subPageTotal = 1;

async function loadSubscriptionsData(page = 1) {
  subPage = page;
  const params = new URLSearchParams({ page, per_page: 15 });
  const res    = await fetch('api/admin/subscriptions/list.php?' + params);
  const data   = await res.json();
  if (!data.success) return;

  const s = data.stats || {};
  setText('sub-active',   s.active_count    ?? '—');
  setText('sub-revenue',  phpFormat(s.monthly_revenue ?? 0));
  setText('sub-expiring', s.expiring_soon   ?? '—');
  setText('sub-top-plan', s.top_plan        ?? '—');

  const pc = s.plan_counts || {};
  setText('sub-plan-count-basic',   pc['BASIC PLAN']   ?? 0);
  setText('sub-plan-count-premium', pc['PREMIUM PLAN'] ?? 0);
  setText('sub-plan-count-vip',     pc['VIP PLAN']     ?? 0);

  const tbody = document.querySelector('#subscriptionsTable tbody');
  if (!tbody) return;

  if (!data.subscriptions?.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#999;">No subscriptions found.</td></tr>';
    return;
  }

  tbody.innerHTML = data.subscriptions.map(s => `
    <tr>
      <td><strong>${esc(s.member_name)}</strong><br><span style="color:#888;font-size:0.8rem;">${esc(s.member_email)}</span></td>
      <td><span class="tag" style="background:#e3f2fd;color:#1565c0;">${esc(s.plan)}</span></td>
      <td>${ucFirst(s.billing_cycle)}</td>
      <td>${fmtDate(s.start_date)}</td>
      <td>${fmtDate(s.expiry_date)}</td>
      <td style="font-weight:700;">₱${numFormat(s.price)}</td>
      <td>${badge(s.status)}</td>
    </tr>`).join('');

  const pg = data.pagination;
  window._subPageTotal = pg.total_pages;
  setText('subPageInfo', `Page ${pg.page} of ${pg.total_pages}`);
}

window.changeSubPage = function(dir) {
  const max = window._subPageTotal || 1;
  if (dir === 'next' && subPage < max) loadSubscriptionsData(subPage + 1);
  if (dir === 'prev' && subPage > 1)   loadSubscriptionsData(subPage - 1);
};

// ─── EVENTS ───────────────────────────────────────────────────────────────────
async function loadEventsData() {
  const res  = await fetch('api/admin/events/list.php?per_page=30');
  const data = await res.json();
  if (!data.success) return;

  setText('events-upcoming',  data.stats?.upcoming            ?? '—');
  setText('events-total-reg', data.stats?.total_registrations ?? '—');
  setText('events-this-week', data.stats?.this_week           ?? '—');
  setText('events-popular',   data.stats?.popular             ?? '—');

  await populateTrainerSelect('eventOrganizerSelect', true);

  const grid = document.getElementById('upcoming-events-grid');
  if (!grid) return;

  const events = (data.events || []).filter(e => e.status === 'active');
  if (!events.length) {
    grid.innerHTML = '<div class="card"><p style="color:#999;">No upcoming events.</p></div>';
    return;
  }

  grid.innerHTML = events.map(e => {
    const fill  = parseInt(e.current_attendees) || 0;
    const max   = parseInt(e.max_attendees) || 50;
    const fee   = parseFloat(e.fee) > 0 ? `₱${numFormat(e.fee)}` : 'Free';
    return `
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
          <h3 style="margin:0;color:var(--primary);font-size:1rem;">${esc(e.name)}</h3>
          <span style="background:var(--blue-bg);color:var(--blue);padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700;">${fill}/${max}</span>
        </div>
        <p style="font-size:0.88rem;color:#555;margin-bottom:4px;">🗂 ${ucFirst((e.type||'').replace('_',' '))}</p>
        <p style="font-size:0.88rem;color:#555;margin-bottom:4px;">📅 ${fmtDate(e.event_date)} ${e.event_time ? '— ' + fmtTime(e.event_time) : ''}</p>
        <p style="font-size:0.88rem;color:#555;margin-bottom:4px;">📍 ${esc(e.location)}</p>
        <p style="font-size:0.88rem;color:#555;margin-bottom:4px;">💰 ${fee}${e.is_members_only ? ' · Members only' : ''}</p>
        ${e.organizer_name ? `<p style="font-size:0.88rem;color:#555;">👤 ${esc(e.organizer_name)}</p>` : ''}
        <div style="margin-top:12px;display:flex;gap:8px;">
          <button class="btn-sm btn-secondary" onclick="cancelEvent(${e.id})">Cancel</button>
        </div>
      </div>`;
  }).join('');
}

window.cancelEvent = async function(eventId) {
  if (!confirm('Cancel this event?')) return;
  const res = await apiFetch('api/admin/events/cancel.php', { id: eventId });
  if (res?.success) { toast('Event cancelled.'); loadEventsData(); }
  else toast(res?.message || 'Failed.', 'error');
};

// ─── REVENUE ─────────────────────────────────────────────────────────────────
async function loadRevenueData() {
  const from = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
  const to   = new Date().toISOString().split('T')[0];
  const res  = await fetch(`api/admin/reports/revenue.php?date_from=${from}&date_to=${to}`);
  const data = await res.json();
  if (!data.success) return;

  const s = data.summary || {};
  setText('rev-monthly',       phpFormat(s.gross             ?? 0));
  setText('rev-transactions',  s.transaction_count           ?? 0);
  setText('rev-total-exp',     phpFormat(data.total_expenses ?? 0));
  setText('rev-net-profit',    phpFormat(data.net_profit     ?? 0));
  setText('rev-net-profit-2',  phpFormat(data.net_profit     ?? 0));
  setText('rev-expenses-ops',      phpFormat(data.expenses?.operating ?? 0));
  setText('rev-expenses-salaries', phpFormat(data.expenses?.salaries  ?? 0));
  setText('rev-expenses-marketing',phpFormat(data.expenses?.marketing ?? 0));
  setText('rev-total-expenses',    phpFormat(data.total_expenses      ?? 0));

  const byTypeEl = document.getElementById('rev-by-type');
  if (byTypeEl) {
    if (data.by_type?.length) {
      byTypeEl.innerHTML = data.by_type.map(t => `
        <div class="rev-type-row">
          <span>${ucFirst((t.type||'').replace('_',' '))}</span>
          <span class="rev-type-amount">₱${numFormat(t.amount)} <span style="color:#888;font-weight:400;font-size:0.82rem;">(${t.count} txns)</span></span>
        </div>`).join('');
    } else {
      byTypeEl.innerHTML = '<p style="color:#999;">No data for this period.</p>';
    }
  }

  const byPlanEl = document.getElementById('rev-by-plan');
  if (byPlanEl) {
    if (data.by_plan?.length) {
      byPlanEl.innerHTML = data.by_plan.map(p => `
        <div class="rev-type-row">
          <span>${esc(p.plan)}</span>
          <span class="rev-type-amount">₱${numFormat(p.revenue)}</span>
        </div>`).join('');
    } else {
      byPlanEl.innerHTML = '<p style="color:#999;">No plan breakdown available.</p>';
    }
  }

  const chartEl = document.getElementById('rev-monthly-chart');
  if (chartEl && data.monthly_chart?.length) {
    const maxV = Math.max(...data.monthly_chart.map(m => parseFloat(m.total)||0)) || 1;
    chartEl.innerHTML = data.monthly_chart.map(m => {
      const h = Math.round((parseFloat(m.total)/maxV)*120);
      return `
        <div style="text-align:center;flex:1;min-width:80px;">
          <p style="font-size:0.78rem;font-weight:700;color:var(--primary);margin-bottom:4px;">₱${numShort(m.total)}</p>
          <div style="height:${h}px;background:linear-gradient(180deg,var(--primary),var(--primary-light));border-radius:6px 6px 0 0;min-height:4px;"></div>
          <p style="font-size:0.75rem;color:#888;margin-top:4px;">${esc(m.label)}</p>
        </div>`;
    }).join('');
  }

  const goals   = data.goals || {};
  const goalBar = document.getElementById('rev-goal-bar');
  const goalPct = document.getElementById('rev-goal-pct');
  if (goalBar) goalBar.style.width = Math.min(goals.achieved_pct ?? 0, 100) + '%';
  if (goalPct) goalPct.textContent = (goals.achieved_pct ?? 0) + '% achieved';
}

// ─── ROLES ────────────────────────────────────────────────────────────────────
async function loadRolesData() {
  const res  = await fetch('api/admin/roles/list.php');
  const data = await res.json();
  if (!data.success) return;

  const s = data.stats || {};
  setText('roles-total-admins', s.total       ?? '—');
  setText('roles-super-admins', s.super_admin ?? '—');
  setText('roles-staff',        s.staff       ?? '—');
  setText('roles-trainers',     data.trainer_count ?? '—');

  const tbody = document.querySelector('#usersTable tbody');
  if (!tbody || !data.users?.length) return;

  const roleBadges = {
    super_admin: 'background:#ffebee;color:#b71c1c',
    admin:       'background:#fff3e0;color:#e65100',
    staff:       'background:#f5f5f5;color:#757575',
  };

  tbody.innerHTML = data.users.map(u => {
    const rStyle = roleBadges[u.role] || 'background:#e5e7eb;color:#333';
    return `
      <tr>
        <td><strong>${esc(u.full_name)}</strong></td>
        <td style="color:#666;font-size:0.88rem;">${esc(u.email)}</td>
        <td><span style="${rStyle};padding:4px 12px;border-radius:12px;font-size:0.8rem;font-weight:700;">${ucFirst((u.role||'').replace('_',' '))}</span></td>
        <td>${badge(u.status)}</td>
        <td style="font-size:0.88rem;">${fmtDate(u.created_at)}</td>
        <td>
          <button class="btn-sm" onclick="openEditUser(${u.id},'${esc(u.role)}','${esc(u.status)}')">Edit</button>
        </td>
      </tr>`;
  }).join('');
}

window.openEditUser = function(id, role, status) {
  document.getElementById('edit_user_id').value     = id;
  document.getElementById('edit_user_role').value   = role;
  document.getElementById('edit_user_status').value = status;
  openModal('editUserModal');
};

// ─── PLANS (Subscription Plan Editor) ────────────────────────────────────────
async function loadPlansData() {
  // Pull subscriber counts
  const subRes  = await fetch('api/admin/subscriptions/list.php?per_page=1');
  const subData = await subRes.json();
  const pc      = subData.stats?.plan_counts || {};

  setText('plans-basic-count',   pc['BASIC PLAN']   ?? '—');
  setText('plans-premium-count', pc['PREMIUM PLAN'] ?? '—');
  setText('plans-vip-count',     pc['VIP PLAN']     ?? '—');

  // Load plan configs
  const res  = await fetch('api/admin/plans/list.php');
  const data = await res.json();
  if (!data.success) return;

  const plans  = data.plans || [];
  const active = plans.filter(p => p.is_active).length;
  setText('plans-active-count', active);

  const grid = document.getElementById('plans-edit-grid');
  if (!grid) return;

  grid.innerHTML = plans.map(p => {
    const savingsAmt  = Math.round((p.monthly_price * 12) - p.yearly_price);
    const benefits    = Array.isArray(p.benefits) ? p.benefits : [];
    const statusBadge = p.is_active
      ? '<span class="badge badge-active">Active</span>'
      : '<span class="badge badge-expired">Inactive</span>';

    // Safely encode plan object for onclick
    const planJson = JSON.stringify(p).replace(/'/g, "\\'");

    return `
      <div class="card" style="border-top:4px solid ${esc(p.color)};">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
          <h3 style="color:${esc(p.color)};margin:0;">${esc(p.plan)}</h3>
          ${statusBadge}
        </div>

        <p style="font-size:1.6rem;font-weight:900;margin-bottom:2px;">
          ₱${numFormat(p.monthly_price)} <span style="font-size:0.85rem;font-weight:400;color:#888;">/mo</span>
        </p>
        <p style="color:#888;font-size:0.88rem;margin-bottom:14px;">
          ₱${numFormat(p.yearly_price)}/yr
          <span style="color:#2e7d32;font-weight:600;"> · Save ₱${numFormat(savingsAmt)}</span>
        </p>

        <ul style="padding-left:18px;color:#555;font-size:0.88rem;line-height:1.9;margin-bottom:16px;">
          ${benefits.map(b => `<li>${esc(b)}</li>`).join('')}
        </ul>

        <div style="font-size:0.82rem;color:#777;margin-bottom:14px;line-height:1.9;padding:10px;background:#f9f9f9;border-radius:8px;">
          <p>🏋️ Classes/wk: <strong>${p.max_classes === -1 ? 'Unlimited' : p.max_classes}</strong></p>
          <p>🧑‍💼 PT sessions/mo: <strong>${p.pt_sessions}</strong></p>
          <p>🎟️ Guest passes/mo: <strong>${p.guest_passes}</strong></p>
        </div>

        <button onclick='openEditPlan(${JSON.stringify(JSON.stringify(p))})'>
          ✏️ Edit Plan
        </button>
      </div>`;
  }).join('');
}

// Open the plan edit modal
window.openEditPlan = function(planJsonStr) {
  const p = JSON.parse(planJsonStr);

  document.getElementById('ep_plan').value         = p.plan;
  document.getElementById('ep_plan_label').value   = p.plan;
  document.getElementById('ep_monthly').value      = p.monthly_price;
  document.getElementById('ep_yearly').value       = p.yearly_price;
  document.getElementById('ep_color').value        = p.color || '#ff6b35';
  document.getElementById('ep_color_hex').value    = p.color || '#ff6b35';
  document.getElementById('ep_active').value       = p.is_active ? '1' : '0';
  document.getElementById('ep_max_classes').value  = p.max_classes ?? -1;
  document.getElementById('ep_pt_sessions').value  = p.pt_sessions ?? 0;
  document.getElementById('ep_guest_passes').value = p.guest_passes ?? 0;

  const benefits = Array.isArray(p.benefits) ? p.benefits : [];
  document.getElementById('ep_benefits').value = benefits.join('\n');

  updatePlanPreview();
  openModal('editPlanModal');

  // Wire live preview
  ['ep_monthly', 'ep_yearly', 'ep_active', 'ep_benefits'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.oninput = updatePlanPreview;
  });
  const colorPicker = document.getElementById('ep_color');
  const colorHex    = document.getElementById('ep_color_hex');
  if (colorPicker) colorPicker.addEventListener('input', function() {
    if (colorHex) colorHex.value = this.value;
    updatePlanPreview();
  });
  if (colorHex) colorHex.oninput = function() {
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
      if (colorPicker) colorPicker.value = this.value;
      updatePlanPreview();
    }
  };
};

// Live preview
window.updatePlanPreview = function() {
  const name    = document.getElementById('ep_plan_label')?.value  || '';
  const monthly = document.getElementById('ep_monthly')?.value     || '0';
  const yearly  = document.getElementById('ep_yearly')?.value      || '0';
  const color   = document.getElementById('ep_color')?.value       || '#ff6b35';
  const active  = document.getElementById('ep_active')?.value      === '1';
  const rawBen  = document.getElementById('ep_benefits')?.value    || '';
  const benefits = rawBen.split('\n').map(b => b.trim()).filter(Boolean);

  const prevName = document.getElementById('prev_name');
  if (prevName) { prevName.textContent = name; prevName.style.color = color; }

  const prevStatus = document.getElementById('prev_status');
  if (prevStatus) {
    prevStatus.textContent      = active ? 'Active' : 'Inactive';
    prevStatus.style.background = active ? '#e8f5e9' : '#ffebee';
    prevStatus.style.color      = active ? '#2e7d32' : '#c62828';
  }

  const prevPrice = document.getElementById('prev_price');
  if (prevPrice) {
    const savingsAmt = Math.round((parseFloat(monthly) * 12) - parseFloat(yearly));
    prevPrice.innerHTML = `₱${Number(monthly).toLocaleString('en-PH')}<span style="font-size:1rem;font-weight:400;color:#888;"> /mo</span>
      <span style="font-size:0.85rem;color:#2e7d32;font-weight:600;margin-left:8px;">Save ₱${Number(savingsAmt > 0 ? savingsAmt : 0).toLocaleString('en-PH')}/yr</span>`;
  }

  const prevBenefits = document.getElementById('prev_benefits');
  if (prevBenefits) {
    prevBenefits.innerHTML = benefits.map(b => `<li>${esc(b)}</li>`).join('') || '<li style="color:#aaa;">No benefits listed</li>';
  }

  const preview = document.getElementById('ep_preview');
  if (preview) preview.style.borderColor = color;
};

// ─── TRAINER SELECT HELPER ────────────────────────────────────────────────────
async function populateTrainerSelect(selectId, addEmpty = false) {
  const el = document.getElementById(selectId);
  if (!el) return;
  try {
    const res  = await fetch('api/admin/trainers/list.php?per_page=50&status=active');
    const data = await res.json();
    if (!data.success) return;
    el.innerHTML = (addEmpty ? '<option value="">Select Organizer</option>' : '<option value="">Select Trainer</option>') +
      (data.trainers || []).map(t =>
        `<option value="${t.id}">${esc(t.first_name + ' ' + t.last_name)} — ${esc(t.specialty)}</option>`
      ).join('');
  } catch (e) { console.warn('Could not load trainers:', e); }
}

// ─── PAGINATION ───────────────────────────────────────────────────────────────
window.changePage = function(dir) {
  if (currentPage === 'members') {
    const max = window._membersPageTotal || 1;
    if (dir === 'next' && membersPage < max) loadMembersData(membersPage + 1, membersFilters);
    if (dir === 'prev' && membersPage > 1)   loadMembersData(membersPage - 1, membersFilters);
  }
  if (currentPage === 'payments') {
    const max = window._paymentsPageTotal || 1;
    if (dir === 'next' && paymentsPage < max) loadPaymentsData(paymentsPage + 1);
    if (dir === 'prev' && paymentsPage > 1)   loadPaymentsData(paymentsPage - 1);
  }
};

// ─── MODAL HELPERS ────────────────────────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}
window.openModal  = openModal;
window.closeModal = closeModal;

document.addEventListener('click', e => {
  document.querySelectorAll('.modal-overlay.open').forEach(m => {
    if (e.target === m) m.classList.remove('open');
  });
});

function bindModalTriggers() {
  const map = {
    addMemberBtn:  'addMemberModal',
    addTrainerBtn: 'addTrainerModal',
    addUserBtn:    'addUserModal',
  };
  Object.entries(map).forEach(([btnId, modalId]) => {
    const btn = document.getElementById(btnId);
    if (btn) btn.onclick = () => openModal(modalId);
  });
}

// ─── FORM HANDLERS ────────────────────────────────────────────────────────────
function bindFormHandlers() {
  bindForm('addMemberForm',    'api/admin/members/create.php',    'Member added!',   'addMemberModal',  () => loadMembersData());
  bindForm('editMemberForm',   'api/admin/members/update.php',    'Member updated!', 'editMemberModal', () => loadMembersData());
  bindForm('scheduleClassForm','api/admin/classes/create.php',    'Class scheduled!', null,             () => loadClassesData());
  bindForm('addTrainerForm',   'api/admin/trainers/create.php',   'Trainer added!',  'addTrainerModal', () => loadTrainersData());
  bindForm('createEventForm',  'api/admin/events/create.php',     'Event created!',  null,              () => loadEventsData());
  bindForm('addUserForm',      'api/admin/roles/create-user.php', 'User created!',   'addUserModal',    () => loadRolesData());
  bindForm('editUserForm',     'api/admin/roles/update-user.php', 'User updated!',   'editUserModal',   () => loadRolesData());

  // Plans edit form (custom handler — uses JSON payload)
  bindPlanEditForm();

  // Member filter
  const mf = document.getElementById('memberFilterForm');
  if (mf) {
    mf.onsubmit = e => {
      e.preventDefault();
      const fd = new FormData(mf);
      const filters = {};
      if (fd.get('search_name'))   filters.search = fd.get('search_name');
      if (fd.get('status_filter')) filters.status = fd.get('status_filter');
      if (fd.get('plan_filter'))   filters.plan   = fd.get('plan_filter');
      loadMembersData(1, filters);
    };
  }

  // Payment filter
  const pf = document.getElementById('paymentFilterForm');
  if (pf) {
    pf.onsubmit = e => {
      e.preventDefault();
      const fd = new FormData(pf);
      const extra = {};
      if (fd.get('member'))    extra.search    = fd.get('member');
      if (fd.get('type'))      extra.type      = fd.get('type');
      if (fd.get('status'))    extra.status    = fd.get('status');
      if (fd.get('method'))    extra.method    = fd.get('method');
      if (fd.get('date_from')) extra.date_from = fd.get('date_from');
      if (fd.get('date_to'))   extra.date_to   = fd.get('date_to');
      loadPaymentsData(1, extra);
    };
  }
}

function bindPlanEditForm() {
  const form = document.getElementById('editPlanForm');
  if (!form) return;

  form.onsubmit = async function(e) {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

    const rawBenefits = document.getElementById('ep_benefits')?.value || '';
    const benefitsArr = rawBenefits.split('\n').map(b => b.trim()).filter(Boolean);

    const payload = {
      plan:          document.getElementById('ep_plan')?.value,
      monthly_price: parseFloat(document.getElementById('ep_monthly')?.value),
      yearly_price:  parseFloat(document.getElementById('ep_yearly')?.value),
      color:         document.getElementById('ep_color')?.value,
      is_active:     document.getElementById('ep_active')?.value === '1' ? 1 : 0,
      max_classes:   parseInt(document.getElementById('ep_max_classes')?.value),
      pt_sessions:   parseInt(document.getElementById('ep_pt_sessions')?.value),
      guest_passes:  parseInt(document.getElementById('ep_guest_passes')?.value),
      benefits:      benefitsArr,
    };

    const res = await apiFetch('api/admin/plans/update.php', payload);
    if (btn) { btn.disabled = false; btn.textContent = 'Save Changes'; }

    if (res?.success) {
      toast('Plan updated successfully!');
      closeModal('editPlanModal');
      loadPlansData();
    } else {
      toast(res?.message || 'Failed to update plan.', 'error');
    }
  };
}

function bindForm(formId, endpoint, successMsg, modalId, onSuccess) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.onsubmit = async e => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

    const body = Object.fromEntries(new FormData(form));
    const res  = await apiFetch(endpoint, body);
    if (btn)   { btn.disabled = false; btn.textContent = successMsg.includes('!') ? successMsg.split('!')[0] + '!' : 'Submit'; }

    if (res?.success) {
      toast(successMsg);
      if (modalId) closeModal(modalId);
      form.reset();
      if (onSuccess) onSuccess();
    } else {
      toast(res?.message || 'Action failed.', 'error');
    }
  };
}

// ─── LOGOUT ───────────────────────────────────────────────────────────────────
window.logout = async function() {
  await apiFetch('api/admin/auth/logout.php', {});
  window.location.href = 'login-page.php';
};

// ─── API HELPERS ──────────────────────────────────────────────────────────────
async function apiFetch(url, body) {
  try {
    const res = await fetch(url, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(body),
    });
    if (res.status === 401) { window.location.href = 'login-page.php'; return null; }
    return await res.json();
  } catch (err) {
    console.error(url, err);
    return null;
  }
}

// ─── INIT ─────────────────────────────────────────────────────────────────────
function init() {
  document.querySelectorAll('.sidebar .nav a[data-page]').forEach(a => {
    a.addEventListener('click', e => { e.preventDefault(); loadPage(a.dataset.page); });
  });
  loadPage('dashboard');
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

// ─── TOAST ────────────────────────────────────────────────────────────────────
function toast(msg, type = 'success') {
  const el = document.getElementById('adminToast');
  if (!el) return;
  el.textContent = msg;
  el.style.background = type === 'success' ? '#2e7d32' : '#c62828';
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 3500);
}
window.toast = toast;
window.showAdminPopup = toast;

// ─── UTILS ────────────────────────────────────────────────────────────────────
function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val ?? '—';
}

function esc(str) {
  if (str == null) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function phpFormat(n) {
  return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits:0, maximumFractionDigits:0 });
}
function numFormat(n) {
  return Number(n).toLocaleString('en-PH', { minimumFractionDigits:0, maximumFractionDigits:0 });
}
function numShort(n) {
  const v = parseFloat(n) || 0;
  if (v >= 1000000) return (v/1000000).toFixed(1) + 'M';
  if (v >= 1000)    return (v/1000).toFixed(0) + 'K';
  return numFormat(v);
}
function ucFirst(s) {
  if (!s) return '';
  return s.charAt(0).toUpperCase() + s.slice(1);
}
function fmtDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  if (isNaN(d)) return str;
  return d.toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
}
function fmtDateTime(str) {
  if (!str) return '—';
  const d = new Date(str);
  if (isNaN(d)) return str;
  return d.toLocaleDateString('en-PH',{month:'short',day:'numeric'}) + ' ' +
         d.toLocaleTimeString('en-PH',{hour:'numeric',minute:'2-digit'});
}
function fmtTime(str) {
  if (!str) return '';
  try {
    const d = new Date('1970-01-01T' + str);
    return d.toLocaleTimeString('en-PH', { hour:'numeric', minute:'2-digit' });
  } catch { return str; }
}

function badge(status) {
  const map = {
    active:'badge-active', completed:'badge-completed', confirmed:'badge-confirmed',
    expired:'badge-expired', failed:'badge-failed', cancelled:'badge-cancelled', deleted:'badge-deleted',
    pending:'badge-pending', paused:'badge-paused',
    refunded:'badge-refunded', suspended:'badge-suspended',
    inactive:'badge-expired',
  };
  const cls = map[(status||'').toLowerCase()] || 'badge';
  return `<span class="badge ${cls}">${ucFirst(status || '—')}</span>`;
}