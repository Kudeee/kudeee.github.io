/**
 * admin-js.js
 * Society Fit — Admin Panel JS
 * Fully connected to PHP/MySQL backend.
 */

// ─── Session / Auth Guard ───────────────────────────────────────────────────

async function checkAdminSession() {
  try {
    const res = await fetch('/api/admin/auth/check-session.php');
    if (res.status === 401 || res.status === 403) {
      window.location.href = '/login-page.php';
    }
  } catch {
    console.warn('Session check failed — offline or PHP not set up yet.');
  }
}

checkAdminSession();

// ─── Content Container ───────────────────────────────────────────────────────

function getContentEl() {
  return (
    document.getElementById('main-content') ||
    document.getElementById('content') ||
    document.getElementById('admin-content') ||
    document.querySelector('.content') ||
    document.querySelector('.main-content') ||
    document.querySelector('main')
  );
}

// ─── Page Map & Loader ────────────────────────────────────────────────────────

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
};

// Track current page for refresh
let currentPage = 'dashboard';

async function loadPage(pageName) {
  const path = pageMap[pageName];
  if (!path) return;

  currentPage = pageName;
  const container = getContentEl();
  if (!container) return;

  container.innerHTML = '<div style="padding:40px;text-align:center;color:#666;">Loading...</div>';

  try {
    const res = await fetch(path);
    if (!res.ok) throw new Error(`Failed to load ${path}`);
    const html = await res.text();
    container.innerHTML = html;

    // Active nav
    document.querySelectorAll('.sidebar a').forEach(link => {
      link.classList.toggle('active', link.dataset.page === pageName);
    });

    // Inject CSRF token into all hidden inputs in the new fragment
    document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
      input.value = _csrfToken;
    });

    bindModalTriggers();
    bindFormHandlers();

    // Fetch live data for this page
    await fetchPageData(pageName);
  } catch (err) {
    const el = getContentEl();
    if (el) el.innerHTML = `<div class="card"><p style="color:red;">Failed to load page. Please try again.</p></div>`;
    console.error(err);
  }
}

// ─── Page Data Fetchers ───────────────────────────────────────────────────────

async function fetchPageData(pageName) {
  try {
    switch (pageName) {
      case 'dashboard':     await loadDashboardData();     break;
      case 'members':       await loadMembersData();       break;
      case 'classes':       await loadClassesData();       break;
      case 'trainers':      await loadTrainersData();      break;
      case 'payments':      await loadPaymentsData();      break;
      case 'events':        await loadEventsData();        break;
      case 'revenue':       await loadRevenueData();       break;
      case 'subscriptions': await loadSubscriptionsData(); break;
      case 'roles':         await loadRolesData();         break;
    }
  } catch (err) {
    console.warn('fetchPageData error for', pageName, err);
  }
}

// ─── Dashboard ────────────────────────────────────────────────────────────────

async function loadDashboardData() {
  const res = await fetch('/api/admin/reports/dashboard.php');
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  const d = data;

  // Key metrics
  setTextById('dash-total-members',   d.members?.total        ?? '—');
  setTextById('dash-active-subs',     d.members?.active       ?? '—');
  setTextById('dash-monthly-revenue', formatPHP(d.revenue?.net ?? 0));
  setTextById('dash-classes-today',   d.classes?.scheduled    ?? '—');

  // Additional stats
  setTextById('dash-new-members',     d.members?.new_this_period ?? '—');
  setTextById('dash-active-trainers', d.top_trainers?.length  ?? '—');

  // Recent activity
  const actEl = document.getElementById('dash-recent-activity');
  if (actEl && d.recent_activity?.length) {
    actEl.innerHTML = d.recent_activity.slice(0, 6).map(a =>
      `<p>• ${formatActivityLog(a)}</p>`
    ).join('');
  }

  // Membership distribution
  if (d.revenue?.by_plan?.length) {
    const planMap = {};
    d.revenue.by_plan.forEach(p => { planMap[p.plan] = p.total; });
  }
}

function formatActivityLog(a) {
  const actionLabels = {
    member_created:   'New member registered',
    member_updated:   'Member updated',
    member_suspended: 'Member suspended',
    class_created:    'Class scheduled',
    class_cancelled:  'Class cancelled',
    trainer_added:    'Trainer added',
    event_created:    'Event created',
    refund_issued:    'Refund issued',
  };
  const label = actionLabels[a.action] || a.action.replace(/_/g, ' ');
  const time = new Date(a.created_at).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
  return `${label} — ${a.admin_name || 'System'} <span style="color:#999;font-size:0.85rem">${time}</span>`;
}

// ─── Members ──────────────────────────────────────────────────────────────────

let membersPage = 1;
let membersFilters = {};

async function loadMembersData(page = 1, filters = {}) {
  membersPage = page;
  membersFilters = filters;

  const params = new URLSearchParams({ page, per_page: 10, ...filters });
  const res = await fetch('/api/admin/members/list.php?' + params);
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  // Stats
  setTextById('members-total',   data.summary?.total_members  ?? '—');
  setTextById('members-active',  data.summary?.active         ?? '—');
  setTextById('members-expired', data.summary?.expiring_this_month ?? '—');
  setTextById('members-new',     data.summary?.new_this_month ?? '—');

  // Table
  const tbody = document.querySelector('#membersTable tbody');
  if (tbody) {
    if (!data.members?.length) {
      tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;color:#999;padding:30px;">No members found.</td></tr>`;
      return;
    }
    tbody.innerHTML = data.members.map(m => {
      const statusBadge = badgeHtml(m.status);
      const joinDate = formatDate(m.join_date || m.joined_at);
      const lastPay  = m.last_payment_date ? formatDate(m.last_payment_date) : '—';
      return `
        <tr>
          <td>#M${m.id}</td>
          <td>${escHtml(m.first_name + ' ' + m.last_name)}</td>
          <td>${escHtml(m.email)}</td>
          <td>${escHtml(m.phone || '—')}</td>
          <td>${statusBadge}</td>
          <td>${escHtml(m.plan || '—')}</td>
          <td>${joinDate}</td>
          <td>${lastPay}</td>
          <td><button style="padding:6px 12px;font-size:0.8rem;" onclick="viewMember(${m.id})">View</button></td>
        </tr>`;
    }).join('');
  }

  // Pagination
  const pg = data.pagination;
  setTextById('pageInfo', `Page ${pg.page} of ${pg.total_pages}`);
  window._membersPageTotal = pg.total_pages;
}

// ─── Classes ──────────────────────────────────────────────────────────────────

async function loadClassesData() {
  // Load upcoming classes (next 30 days)
  const from = new Date().toISOString().split('T')[0];
  const to   = new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0];
  const params = new URLSearchParams({ date_from: from, date_to: to, per_page: 20 });

  const res = await fetch('/api/admin/classes/list.php?' + params);
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  // Stats
  setTextById('classes-total',       data.stats?.scheduled   ?? data.pagination?.total ?? '—');
  setTextById('classes-today',       countToday(data.classes, 'scheduled_at'));
  setTextById('classes-avg-attend',  '—'); // Would need separate calc

  // Upcoming classes grid
  const grid = document.getElementById('upcoming-classes-grid');
  if (grid) {
    if (!data.classes?.length) {
      grid.innerHTML = `<div class="card"><p style="color:#999;">No upcoming classes found.</p></div>`;
      return;
    }
    grid.innerHTML = data.classes.map(c => {
      const fill     = c.current_participants;
      const max      = c.max_participants;
      const pct      = max > 0 ? Math.round((fill / max) * 100) : 0;
      const badgeCls = pct >= 100 ? '#e8f5e9;color:#2e7d32' : pct >= 75 ? '#fff3e0;color:#f57c00' : '#e3f2fd;color:#1565c0';
      const dt       = new Date(c.scheduled_at);
      const dateStr  = dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
      const timeStr  = dt.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
      return `
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px;">
            <h3 style="margin:0;color:#ff6b35;">${escHtml(c.class_name)}</h3>
            <span style="background:${badgeCls};padding:4px 12px;border-radius:12px;font-size:0.8rem;font-weight:600;">${fill}/${max}${pct >= 100 ? ' Full' : ''}</span>
          </div>
          <p><strong>Trainer:</strong> ${escHtml(c.trainer_name || '—')}</p>
          <p><strong>Date &amp; Time:</strong> ${dateStr} - ${timeStr}</p>
          <p><strong>Duration:</strong> ${c.duration_minutes} minutes</p>
          <p><strong>Location:</strong> ${escHtml(c.location || '—')}</p>
          <div style="margin-top:15px;display:flex;gap:10px;">
            <button style="flex:1;padding:8px;" onclick="editClass(${c.id})">Edit</button>
            <button style="flex:1;padding:8px;background:#e5e7eb;color:#333;" onclick="cancelClass(${c.id})">Cancel</button>
          </div>
        </div>`;
    }).join('');
  }
}

function countToday(items, dateField) {
  if (!items) return 0;
  const today = new Date().toISOString().split('T')[0];
  return items.filter(i => (i[dateField] || '').startsWith(today)).length;
}

// ─── Trainers ─────────────────────────────────────────────────────────────────

async function loadTrainersData() {
  const res = await fetch('/api/admin/trainers/list.php?per_page=50');
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  // Stats
  const trainers   = data.trainers || [];
  const activeOnes = trainers.filter(t => t.status === 'active');
  setTextById('trainers-total',    trainers.length);
  setTextById('trainers-sessions', trainers.reduce((s, t) => s + (parseInt(t.upcoming_sessions) || 0), 0));

  const avgRating = trainers.length
    ? (trainers.reduce((s, t) => s + parseFloat(t.rating || 0), 0) / trainers.length).toFixed(1)
    : '—';
  setTextById('trainers-avg-rating', avgRating);

  const top = [...trainers].sort((a, b) => (b.total_sessions || 0) - (a.total_sessions || 0))[0];
  setTextById('trainers-top', top ? top.first_name + ' ' + top.last_name.charAt(0) + '.' : '—');

  // Grid
  const grid = document.getElementById('trainers-grid');
  if (!grid) return;

  if (!trainers.length) {
    grid.innerHTML = `<div class="card"><p style="color:#999;">No trainers found.</p></div>`;
    return;
  }

  grid.innerHTML = trainers.map(t => {
    const name      = escHtml(t.first_name + ' ' + t.last_name);
    const imgSrc    = t.image_url || `../assests/images/trainer-${t.first_name?.toLowerCase()}.jpg`;
    const statusClr = t.status === 'active' ? '#2e7d32' : t.status === 'on_leave' ? '#f57c00' : '#c62828';
    const statusLbl = t.status === 'on_leave' ? 'On Leave' : t.status.charAt(0).toUpperCase() + t.status.slice(1);
    const tags      = (t.specialty_tags || []).slice(0, 2).join(' · ');
    return `
      <div class="card">
        <div style="display:flex;align-items:center;gap:15px;margin-bottom:15px;">
          <img src="${imgSrc}" alt="${name}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;background:#ddd;"
               onerror="this.style.background='#ddd';this.src=''"/>
          <div>
            <h3 style="margin:0;">${name}</h3>
            <p style="margin:0;color:#ff6b35;font-weight:600;">${escHtml(t.specialty || tags || '—')}</p>
          </div>
        </div>
        <p><strong>Sessions/mo:</strong> ${t.total_sessions ?? 0}</p>
        <p><strong>Rating:</strong> ⭐ ${parseFloat(t.rating || 0).toFixed(1)}</p>
        <p><strong>Rate:</strong> ₱${formatNum(t.session_rate)}/hr</p>
        <p><strong>Status:</strong> <span style="color:${statusClr};font-weight:600;">${statusLbl}</span></p>
        <div style="display:flex;gap:10px;margin-top:15px;">
          <button style="flex:1;padding:8px;" onclick="editTrainer(${t.id})">Edit</button>
          <button style="flex:1;padding:8px;" onclick="viewTrainerSchedule(${t.id})">Schedule</button>
        </div>
      </div>`;
  }).join('');
}

// ─── Payments ─────────────────────────────────────────────────────────────────

let paymentsPage = 1;

async function loadPaymentsData(page = 1) {
  paymentsPage = page;
  // Default to current month
  const from = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
  const to   = new Date().toISOString().split('T')[0];
  const params = new URLSearchParams({ page, per_page: 15, date_from: from, date_to: to });

  const res = await fetch('/api/admin/payments/list.php?' + params);
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  const t = data.totals || {};

  // Stats
  setTextById('pay-total-revenue',    formatPHP(t.gross_revenue  ?? 0));
  setTextById('pay-transactions',     t.total_transactions ?? 0);
  setTextById('pay-failed',           t.failed_count       ?? 0);
  setTextById('pay-pending-refunds',  t.pending_count      ?? 0);

  // Table
  const tbody = document.querySelector('#paymentsTable tbody');
  if (tbody) {
    if (!data.payments?.length) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#999;padding:30px;">No payment records found.</td></tr>`;
      return;
    }
    tbody.innerHTML = data.payments.map(p => {
      const statusBadge = badgeHtml(p.status);
      return `
        <tr>
          <td>#${escHtml(p.transaction_id || p.id.toString())}</td>
          <td>${escHtml(p.member_name)}</td>
          <td>${ucfirst(p.type?.replace('_', ' ') || '—')}</td>
          <td>₱${formatNum(p.amount)}</td>
          <td>${ucfirst(p.method || '—')}</td>
          <td>${formatDate(p.created_at)}</td>
          <td>${statusBadge}</td>
          <td><button style="padding:5px 10px;font-size:0.8rem;" onclick="viewTransaction('${escHtml(p.transaction_id || p.id.toString())}')">View</button></td>
        </tr>`;
    }).join('');
  }

  const pg = data.pagination;
  setTextById('pageInfo', `Page ${pg.page} of ${pg.total_pages}`);
  window._paymentsPageTotal = pg.total_pages;
}

// ─── Events ───────────────────────────────────────────────────────────────────

async function loadEventsData() {
  // Fetch next 60 days
  const from = new Date().toISOString().split('T')[0];
  const to   = new Date(Date.now() + 60 * 86400000).toISOString().split('T')[0];
  const params = new URLSearchParams({ date_from: from, date_to: to, per_page: 20 });

  const res = await fetch('/api/admin/events/list.php?' + params);
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  setTextById('events-upcoming',       data.stats?.upcoming            ?? data.pagination?.total ?? '—');
  setTextById('events-total-reg',      data.stats?.total_registrations ?? '—');
  setTextById('events-this-week',      countThisWeek(data.events, 'event_date'));

  // Most popular
  if (data.events?.length) {
    const popular = [...data.events].sort((a, b) => (b.current_attendees || 0) - (a.current_attendees || 0))[0];
    setTextById('events-popular', popular ? popular.name : '—');
  }

  // Grid
  const grid = document.getElementById('upcoming-events-grid');
  if (!grid) return;

  if (!data.events?.length) {
    grid.innerHTML = `<div class="card"><p style="color:#999;">No upcoming events found.</p></div>`;
    return;
  }

  grid.innerHTML = data.events.map(e => {
    const fill     = e.current_attendees || 0;
    const max      = e.max_attendees || 0;
    const pct      = max > 0 ? Math.round((fill / max) * 100) : 0;
    const badgeCls = pct >= 100 ? '#e8f5e9;color:#2e7d32' : pct >= 75 ? '#fff3e0;color:#f57c00' : '#e3f2fd;color:#1565c0';
    const dt       = new Date(e.event_date + 'T' + (e.event_time || '00:00'));
    const dateStr  = dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    const timeStr  = e.event_time ? new Date('1970-01-01T' + e.event_time).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '';
    const fee      = parseFloat(e.fee) > 0 ? `₱${formatNum(e.fee)}` : 'Free' + (e.is_members_only ? ' (Members)' : '');
    return `
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px;">
          <h3 style="margin:0;color:#ff6b35;">${escHtml(e.name)}</h3>
          <span style="background:${badgeCls};padding:4px 12px;border-radius:12px;font-size:0.8rem;font-weight:600;">${fill}/${max}</span>
        </div>
        <p><strong>Type:</strong> ${ucfirst(e.type?.replace('_', ' ') || '—')}</p>
        <p><strong>Date:</strong> ${dateStr}${timeStr ? ' — ' + timeStr : ''}</p>
        <p><strong>Location:</strong> ${escHtml(e.location)}</p>
        <p><strong>Fee:</strong> ${fee}</p>
        <div style="margin-top:15px;display:flex;gap:10px;">
          <button style="flex:1;padding:8px;" onclick="editEvent(${e.id})">Edit</button>
          <button style="flex:1;padding:8px;background:#e5e7eb;color:#333;" onclick="cancelEvent(${e.id})">Cancel</button>
        </div>
      </div>`;
  }).join('');
}

function countThisWeek(items, dateField) {
  if (!items) return 0;
  const now  = new Date();
  const sun  = new Date(now); sun.setDate(now.getDate() - now.getDay());
  const sat  = new Date(sun); sat.setDate(sun.getDate() + 6);
  return items.filter(i => {
    const d = new Date(i[dateField]);
    return d >= sun && d <= sat;
  }).length;
}

// ─── Revenue ──────────────────────────────────────────────────────────────────

async function loadRevenueData() {
  const from = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
  const to   = new Date().toISOString().split('T')[0];
  const params = new URLSearchParams({ date_from: from, date_to: to });

  const res = await fetch('/api/admin/reports/revenue.php?' + params);
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  const s = data.summary || {};

  // Key metrics
  setTextById('rev-monthly',      formatPHP(s.gross  ?? 0));
  setTextById('rev-net-profit',   formatPHP(data.net_profit ?? 0));

  // Monthly chart data
  const chart = data.monthly_chart || [];
  const chartContainer = document.getElementById('rev-monthly-chart');
  if (chartContainer && chart.length) {
    chartContainer.innerHTML = chart.map(m => `
      <div style="text-align:center;">
        <p style="font-weight:600;color:#666;">${m.label}</p>
        <p style="font-size:1.2rem;font-weight:900;color:#ff6b35;">₱${formatNumShort(m.total)}</p>
      </div>
    `).join('');
  }

  // By type breakdown
  const byType = data.by_type || [];
  const byTypeEl = document.getElementById('rev-by-type');
  if (byTypeEl && byType.length) {
    byTypeEl.innerHTML = byType.map(t => `
      <p style="color:#666;margin:5px 0;">${ucfirst(t.type?.replace('_', ' '))}: ₱${formatNum(t.amount)} (${t.count} txns)</p>
    `).join('');
  }

  // Expenses
  const exp = data.expenses || {};
  setTextById('rev-expenses-ops',      formatPHP(exp.operating   ?? 0));
  setTextById('rev-expenses-salaries', formatPHP(exp.salaries    ?? 0));
  setTextById('rev-expenses-marketing',formatPHP(exp.marketing   ?? 0));
  setTextById('rev-total-expenses',    formatPHP(data.total_expenses ?? 0));

  // Goals
  const goals = data.goals || {};
  const goalBar = document.getElementById('rev-goal-bar');
  const goalPct = document.getElementById('rev-goal-pct');
  if (goalBar)  goalBar.style.width  = Math.min(goals.achieved_pct ?? 0, 100) + '%';
  if (goalPct)  goalPct.textContent  = (goals.achieved_pct ?? 0) + '% achieved';
}

// ─── Subscriptions ────────────────────────────────────────────────────────────

async function loadSubscriptionsData() {
  const res = await fetch('/api/admin/subscriptions/list.php?per_page=10');
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  const s = data.stats || {};

  setTextById('sub-active',       s.active_count    ?? '—');
  setTextById('sub-revenue',      formatPHP(s.monthly_revenue ?? 0));
  setTextById('sub-expiring',     s.expiring_soon   ?? '—');
  setTextById('sub-top-plan',     s.top_plan        ?? '—');

  // Recent subscriptions table
  const tbody = document.querySelector('#subscriptionsTable tbody');
  if (tbody && data.subscriptions?.length) {
    tbody.innerHTML = data.subscriptions.map(sub => {
      const statusBadge = badgeHtml(sub.status);
      return `
        <tr>
          <td>${escHtml(sub.member_name)}</td>
          <td>${escHtml(sub.plan)}</td>
          <td>${ucfirst(sub.billing_cycle)}</td>
          <td>${formatDate(sub.start_date)}</td>
          <td>${formatDate(sub.expiry_date)}</td>
          <td>${statusBadge}</td>
          <td><button style="padding:5px 10px;font-size:0.8rem;" onclick="manageSub(${sub.member_id})">Manage</button></td>
        </tr>`;
    }).join('');
  }

  // Plan distribution cards
  const dist = s.plan_distribution || [];
  dist.forEach(p => {
    const planKey = p.plan?.toLowerCase().replace(' plan', '').replace(' ', '-');
    setTextById(`sub-plan-count-${planKey}`, p.cnt ?? 0);
  });
}

// ─── Roles ────────────────────────────────────────────────────────────────────

async function loadRolesData() {
  const res = await fetch('/api/admin/roles/list.php');
  if (!res.ok) return;
  const data = await res.json();
  if (!data.success) return;

  const s = data.stats || {};

  setTextById('roles-total-admins',  s.total        ?? '—');
  setTextById('roles-super-admins',  s.super_admin  ?? '—');
  setTextById('roles-staff',         s.staff        ?? '—');
  setTextById('roles-trainers',      data.trainer_count ?? '—');

  const tbody = document.querySelector('#usersTable tbody');
  if (!tbody || !data.users?.length) return;

  const roleBadges = {
    super_admin:  'background:#ffebee;color:#b71c1c',
    admin:        'background:#fff3e0;color:#e65100',
    staff:        'background:#f5f5f5;color:#757575',
    trainer:      'background:#e8f5e9;color:#2e7d32',
    receptionist: 'background:#e3f2fd;color:#1565c0',
  };

  tbody.innerHTML = data.users.map(u => {
    const roleStyle = roleBadges[u.role] || 'background:#e5e7eb;color:#333';
    const roleLabel = ucfirst(u.role?.replace('_', ' ') || '—');
    const statusClr = u.status === 'active' ? '#2e7d32' : '#c62828';
    const lastLogin = u.last_login_at ? formatDate(u.last_login_at) : 'Never';
    return `
      <tr>
        <td>${escHtml(u.first_name + ' ' + u.last_name)}</td>
        <td>${escHtml(u.email)}</td>
        <td><span style="${roleStyle};padding:4px 10px;border-radius:10px;font-weight:600;">${roleLabel}</span></td>
        <td>${lastLogin}</td>
        <td><span style="color:${statusClr};font-weight:600;">${ucfirst(u.status)}</span></td>
        <td><button style="padding:5px 10px;font-size:0.8rem;" onclick="editUser(${u.id})">Edit</button></td>
      </tr>`;
  }).join('');
}

// ─── CSRF Token Injection ─────────────────────────────────────────────────────

let _csrfToken = '';

async function fetchAndInjectCsrf() {
  try {
    const res = await fetch('/api/csrf-token.php');
    const data = await res.json();
    _csrfToken = data.csrf_token || '';
  } catch {
    // PHP not yet running, ignore
  }
  // Inject into all csrf_token inputs currently in DOM
  document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
    input.value = _csrfToken;
  });
}

// ─── Init ─────────────────────────────────────────────────────────────────────

function init() {
  document.querySelectorAll('.sidebar a[data-page]').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      loadPage(link.dataset.page);
    });
  });
  fetchAndInjectCsrf();
  loadPage('dashboard');
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

// ─── Modal Helpers ────────────────────────────────────────────────────────────

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = 'flex';
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = 'none';
}

window.closeModal = closeModal;

document.addEventListener('click', e => {
  const modals = document.querySelectorAll('[id$="Modal"]');
  modals.forEach(m => { if (e.target === m) m.style.display = 'none'; });
});

function bindModalTriggers() {
  const triggers = {
    addMemberBtn:  'addMemberModal',
    addTrainerBtn: 'addTrainerModal',
    addUserBtn:    'addUserModal',
  };
  Object.entries(triggers).forEach(([btnId, modalId]) => {
    const btn = document.getElementById(btnId);
    if (btn) btn.addEventListener('click', () => openModal(modalId));
  });
}

// ─── Generic POST helper ─────────────────────────────────────────────────────

async function postForm(endpoint, formData) {
  const res = await fetch(endpoint, { method: 'POST', body: formData });
  if (res.status === 401 || res.status === 403) {
    window.location.href = '/login-page.php';
    return null;
  }
  try { return await res.json(); } catch { return null; }
}

// ─── Form Handlers ────────────────────────────────────────────────────────────

function bindFormHandlers() {
  bindForm('scheduleClassForm', '/api/admin/classes/create.php', 'Class scheduled!', null, () => loadClassesData());
  bindForm('addMemberForm',     '/api/admin/members/create.php', 'Member added!', 'addMemberModal', () => loadMembersData());
  bindForm('createEventForm',   '/api/admin/events/create.php',  'Event created!', null, () => loadEventsData());
  bindForm('addTrainerForm',    '/api/admin/trainers/create.php', 'Trainer added!', 'addTrainerModal', () => loadTrainersData());
  bindForm('refundForm',        '/api/admin/payments/refund.php', 'Refund issued!', 'refundModal', () => loadPaymentsData());
  bindForm('addUserForm',       '/api/admin/roles/create-user.php', 'User created!', 'addUserModal', () => loadRolesData());
  bindForm('editUserForm',      '/api/admin/roles/update-user.php', 'User updated!', 'editUserModal', () => loadRolesData());
}

function bindForm(formId, endpoint, successMsg, modalId, onSuccess) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const result = await postForm(endpoint, new FormData(form));
    if (!result) return;
    if (result.success) {
      showAdminPopup(successMsg, 'success');
      if (modalId) closeModal(modalId);
      form.reset();
      if (onSuccess) onSuccess();
    } else {
      showAdminPopup(result.message || 'Action failed.', 'error');
    }
  });
}

// ─── Action Functions ─────────────────────────────────────────────────────────

window.editClass = async function(classId) {
  showAdminPopup('Edit class ID: ' + classId + ' (modal coming soon)', 'success');
};

window.cancelClass = async function(classId) {
  if (!confirm('Cancel this class? Members will be notified.')) return;
  const fd = new FormData();
  fd.append('class_id', classId);
  fd.append('csrf_token', getCsrfToken());
  const result = await postForm('/api/admin/classes/cancel.php', fd);
  if (result?.success) { showAdminPopup('Class cancelled.', 'success'); loadClassesData(); }
  else showAdminPopup(result?.message || 'Failed to cancel class.', 'error');
};

window.viewMember = async function(memberId) {
  const res = await fetch('/api/admin/members/view.php?id=' + memberId);
  const data = await res.json();
  if (!data.success) { showAdminPopup('Could not load member.', 'error'); return; }

  const m   = data.member;
  const sub = data.subscription;
  const payments = data.payments || [];

  // Build and show a simple info popup
  const info = [
    `<strong>${escHtml(m.first_name + ' ' + m.last_name)}</strong>`,
    `Email: ${escHtml(m.email)}`,
    `Phone: ${escHtml(m.phone || '—')}`,
    `Plan: ${escHtml(m.plan)} (${m.billing_cycle || '—'})`,
    `Status: ${ucfirst(m.status)}`,
    sub ? `Subscription expires: ${formatDate(sub.expiry_date)} (${sub.days_remaining} days left)` : 'No active subscription',
    `Recent payments: ${payments.length}`,
  ].join('<br>');

  showAdminPopup('Member #' + memberId + ' loaded — detail modal coming soon', 'success');
  console.log('Member data:', data);
};

window.changePage = function(direction) {
  if (currentPage === 'members') {
    const max = window._membersPageTotal || 1;
    if (direction === 'next' && membersPage < max) loadMembersData(membersPage + 1, membersFilters);
    if (direction === 'prev' && membersPage > 1)   loadMembersData(membersPage - 1, membersFilters);
  }
  if (currentPage === 'payments') {
    const max = window._paymentsPageTotal || 1;
    if (direction === 'next' && paymentsPage < max) loadPaymentsData(paymentsPage + 1);
    if (direction === 'prev' && paymentsPage > 1)   loadPaymentsData(paymentsPage - 1);
  }
};

window.editEvent   = function(id) { showAdminPopup('Edit event #' + id + ' (modal coming soon)', 'success'); };
window.cancelEvent = async function(eventId) {
  if (!confirm('Cancel this event? All registrations will be cancelled.')) return;
  const fd = new FormData();
  fd.append('event_id', eventId);
  fd.append('csrf_token', getCsrfToken());
  const result = await postForm('/api/admin/events/cancel.php', fd);
  if (result?.success) { showAdminPopup('Event cancelled.', 'success'); loadEventsData(); }
  else showAdminPopup(result?.message || 'Failed to cancel event.', 'error');
};

window.editTrainer         = function(id) { showAdminPopup('Edit trainer #' + id + ' (modal coming soon)', 'success'); };
window.viewTrainerSchedule = function(id) { showAdminPopup('Schedule for trainer #' + id + ' (coming soon)', 'success'); };

window.editPlan   = function(id)  { showAdminPopup('Edit plan #' + id + ' (modal coming soon)', 'success'); };
window.archivePlan= async function(planId) {
  if (!confirm('Archive this plan?')) return;
  showAdminPopup('Plan archive endpoint not yet connected.', 'error');
};
window.manageSub  = function(id)  { showAdminPopup('Manage subscription for member #' + id + ' (coming soon)', 'success'); };

window.viewTransaction = function(txnId) {
  showAdminPopup('Transaction #' + txnId + ' — detail modal coming soon', 'success');
};
window.openRefundModal = function(txnId, amount) {
  const txnInput = document.getElementById('refund_transaction_id');
  const amtInput = document.getElementById('refund_amount');
  if (txnInput) txnInput.value = txnId;
  if (amtInput) amtInput.value = amount || '';
  openModal('refundModal');
};

window.editUser = function(userId) {
  const input = document.getElementById('edit_user_id');
  if (input) input.value = userId;
  openModal('editUserModal');
};

// Export buttons
document.addEventListener('click', e => {
  const map = {
    exportBtn:         '/api/admin/members/export.php',
    exportPaymentsBtn: '/api/admin/payments/export.php',
    exportTrainersBtn: '/api/admin/trainers/export.php',
  };
  if (map[e.target.id]) window.location.href = map[e.target.id];
});

// ─── Member filter form ───────────────────────────────────────────────────────

document.addEventListener('submit', e => {
  if (e.target.id === 'memberFilterForm') {
    e.preventDefault();
    const fd = new FormData(e.target);
    const filters = {};
    if (fd.get('search_name'))  filters.search = fd.get('search_name');
    if (fd.get('status_filter')) filters.status = fd.get('status_filter');
    if (fd.get('plan_filter'))   filters.plan   = fd.get('plan_filter');
    loadMembersData(1, filters);
  }
  if (e.target.id === 'paymentFilterForm') {
    e.preventDefault();
    const fd = new FormData(e.target);
    paymentsPage = 1;
    const params = new URLSearchParams({ page: 1, per_page: 15 });
    if (fd.get('member'))    params.set('search',    fd.get('member'));
    if (fd.get('type'))      params.set('type',      fd.get('type'));
    if (fd.get('status'))    params.set('status',    fd.get('status'));
    if (fd.get('method'))    params.set('method',    fd.get('method'));
    if (fd.get('date_from')) params.set('date_from', fd.get('date_from'));
    if (fd.get('date_to'))   params.set('date_to',   fd.get('date_to'));
    fetch('/api/admin/payments/list.php?' + params)
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;
        const tbody = document.querySelector('#paymentsTable tbody');
        if (!tbody) return;
        if (!data.payments?.length) {
          tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#999;padding:30px;">No payment records found.</td></tr>`;
          return;
        }
        tbody.innerHTML = data.payments.map(p => `
          <tr>
            <td>#${escHtml(p.transaction_id || p.id.toString())}</td>
            <td>${escHtml(p.member_name)}</td>
            <td>${ucfirst(p.type?.replace('_', ' ') || '—')}</td>
            <td>₱${formatNum(p.amount)}</td>
            <td>${ucfirst(p.method || '—')}</td>
            <td>${formatDate(p.created_at)}</td>
            <td>${badgeHtml(p.status)}</td>
            <td><button style="padding:5px 10px;font-size:0.8rem;" onclick="viewTransaction('${escHtml(p.transaction_id || p.id.toString())}')">View</button></td>
          </tr>`).join('');
        setTextById('pageInfo', `Page ${data.pagination.page} of ${data.pagination.total_pages}`);
      });
  }
});

// ─── CSRF Helper ─────────────────────────────────────────────────────────────

function getCsrfToken() {
  // Use cached token or grab from any CSRF hidden input currently in the DOM
  if (_csrfToken) return _csrfToken;
  const input = document.querySelector('input[name="csrf_token"]');
  return input ? input.value : '';
}

// ─── Toast Popup ─────────────────────────────────────────────────────────────

function showAdminPopup(message, type = 'success') {
  let toast = document.getElementById('adminToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'adminToast';
    toast.style.cssText = `
      position:fixed;bottom:30px;right:30px;z-index:9999;
      padding:15px 25px;border-radius:10px;font-weight:600;
      font-size:0.95rem;box-shadow:0 4px 20px rgba(0,0,0,0.15);
      transition:opacity 0.3s;max-width:350px;word-wrap:break-word;
    `;
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.style.background = type === 'success' ? '#2e7d32' : '#c62828';
  toast.style.color = '#fff';
  toast.style.opacity = '1';
  clearTimeout(toast._timeout);
  toast._timeout = setTimeout(() => { toast.style.opacity = '0'; }, 3500);
}

window.showAdminPopup = showAdminPopup;

// ─── Utility Helpers ─────────────────────────────────────────────────────────

function setTextById(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

function escHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function formatPHP(amount) {
  return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function formatNum(num) {
  return Number(num).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function formatNumShort(num) {
  const n = Number(num);
  if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
  if (n >= 1000)    return (n / 1000).toFixed(0) + 'K';
  return formatNum(n);
}

function formatDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  if (isNaN(d)) return str;
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

function ucfirst(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function badgeHtml(status) {
  const map = {
    active:    'background:#e8f5e9;color:#2e7d32',
    confirmed: 'background:#e8f5e9;color:#2e7d32',
    completed: 'background:#e8f5e9;color:#2e7d32',
    expired:   'background:#ffebee;color:#c62828',
    failed:    'background:#ffebee;color:#c62828',
    cancelled: 'background:#ffebee;color:#c62828',
    suspended: 'background:#ffebee;color:#c62828',
    pending:   'background:#fff3e0;color:#f57c00',
    refunded:  'background:#e3f2fd;color:#1565c0',
    paused:    'background:#e3f2fd;color:#1565c0',
  };
  const style = map[status?.toLowerCase()] || 'background:#e5e7eb;color:#333';
  return `<span style="${style};padding:4px 10px;border-radius:10px;font-weight:600;">${ucfirst(status || '—')}</span>`;
}