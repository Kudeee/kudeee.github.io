/**
 * trainer-dashboard.js  —  Society Fitness Trainer Portal
 * All API calls hit real PHP endpoints under api/trainer/
 */

'use strict';

// ─── Page map ─────────────────────────────────────────────────────────────────
const PAGE_RENDERERS = {
  dashboard:    renderDashboard,
  bookings:     renderBookings,
  availability: renderAvailability,
  members:      renderMembers,
  earnings:     renderEarnings,
  profile:      renderProfile,
};

let currentPage = 'dashboard';

// ─── Init ─────────────────────────────────────────────────────────────────────
(function init() {
  document.querySelectorAll('.sidebar .nav a[data-page]').forEach(a => {
    a.addEventListener('click', e => { e.preventDefault(); loadPage(a.dataset.page); });
  });
  loadPage('dashboard');
})();

// ─── Page Loader ──────────────────────────────────────────────────────────────
async function loadPage(pageName) {
  const renderer = PAGE_RENDERERS[pageName];
  if (!renderer) return;

  currentPage = pageName;
  document.querySelectorAll('.sidebar .nav a').forEach(a => {
    a.classList.toggle('active', a.dataset.page === pageName);
  });

  const main = document.getElementById('mainContent');
  main.innerHTML = '<div class="loading"><div class="spinner"></div> Loading…</div>';

  try {
    await renderer(main);
  } catch (err) {
    console.error('Page render error:', err);
    main.innerHTML = '<div class="card"><p style="color:red;">Failed to load page. Please try again.</p></div>';
  }
}

// ─── API helper ───────────────────────────────────────────────────────────────
async function apiFetch(url, options = {}) {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  if (res.status === 401) { window.location.href = 'login-page.php'; return null; }
  return await res.json();
}

// ═══════════════════════════════════════════════════════════════════════════════
// ─── DASHBOARD ────────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════
async function renderDashboard(container) {
  const data = await apiFetch('api/trainer/dashboard/stats.php');
  if (!data?.success) { container.innerHTML = '<div class="card"><p style="color:red;">Could not load dashboard data.</p></div>'; return; }

  const s     = data.stats;
  const today = new Date().toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

  container.innerHTML = `
    <div class="page-header">
      <h1>Dashboard</h1>
      <div class="header-right"><div class="date-badge">📅 ${today}</div></div>
    </div>

    <div class="grid">
      <div class="card">
        <h3>Sessions This Month</h3>
        <p class="stat-value">${s.sessions_this_month}</p>
        <p class="stat-status">Personal training sessions</p>
      </div>
      <div class="card">
        <h3>Upcoming Sessions</h3>
        <p class="stat-value">${s.upcoming_sessions}</p>
        <p class="stat-status">Confirmed bookings</p>
      </div>
      <div class="card">
        <h3>My Members</h3>
        <p class="stat-value">${s.total_members}</p>
        <p class="stat-status">Active clients</p>
      </div>
      <div class="card">
        <h3>Earnings (Month)</h3>
        <p class="stat-value">₱${numFormat(s.earnings_this_month)}</p>
        <p class="stat-status">This month</p>
      </div>
    </div>

    <div class="two-col">
      <div class="card">
        <h3 style="margin-bottom:16px;">Upcoming Sessions</h3>
        <div class="session-timeline">${renderSessionTimeline(data.upcoming_sessions || [])}</div>
        <div style="margin-top:16px;">
          <button class="btn-sm btn-secondary" onclick="loadPage('bookings')">View All →</button>
        </div>
      </div>
      <div class="card">
        <h3 style="margin-bottom:16px;">Quick Actions</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button onclick="loadPage('availability')">🕐 Set My Availability</button>
          <button onclick="loadPage('bookings')">📋 View All Bookings</button>
          <button class="btn-secondary" onclick="loadPage('earnings')">💰 View Earnings</button>
          <button class="btn-secondary" onclick="loadPage('profile')">👤 Edit Profile</button>
        </div>
        <div style="margin-top:24px;padding-top:18px;border-top:2px solid var(--border);">
          <h3 style="margin-bottom:12px;">Performance</h3>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-weight:600;font-size:0.88rem;">
            <span>Session Completion</span>
            <span style="color:var(--primary)">${s.completion_rate}%</span>
          </div>
          <div style="background:var(--border);height:8px;border-radius:4px;overflow:hidden;margin-bottom:14px;">
            <div style="width:${s.completion_rate}%;height:100%;background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:4px;"></div>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-weight:600;font-size:0.88rem;">
            <span>Avg Rating</span>
            <span style="color:var(--primary)">⭐ ${Number(s.avg_rating).toFixed(1)} / 5.0</span>
          </div>
          <div style="background:var(--border);height:8px;border-radius:4px;overflow:hidden;">
            <div style="width:${(s.avg_rating/5)*100}%;height:100%;background:linear-gradient(135deg,#f9a825,#ffca28);border-radius:4px;"></div>
          </div>
        </div>
      </div>
    </div>

    <p class="section-title" style="margin-top:8px;">Recent Booking Requests</p>
    <div id="dash-recent-bookings">${renderBookingRequestCards(data.recent_bookings || [])}</div>
  `;
}

// ═══════════════════════════════════════════════════════════════════════════════
// ─── BOOKINGS ─────────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════
let bookingsCurrentPage = 1;

async function renderBookings(container, page = 1) {
  bookingsCurrentPage = page;
  const params  = new URLSearchParams({ page, per_page: 20 });
  const statusF = document.getElementById('bookingStatusFilter')?.value || '';
  const dateF   = document.getElementById('bookingDateFilter')?.value   || '';
  if (statusF) params.set('status', statusF);
  if (dateF)   params.set('date', dateF);

  const data = await apiFetch('api/trainer/bookings/list.php?' + params);
  if (!data?.success) { container.innerHTML = '<div class="card"><p style="color:red;">Could not load bookings.</p></div>'; return; }

  const c  = data.counts || {};
  const pg = data.pagination || {};

  container.innerHTML = `
    <div class="page-header"><h1>My Bookings</h1></div>

    <div class="grid" style="margin-bottom:20px;">
      <div class="card"><h3>Confirmed</h3><p class="stat-value">${c.confirmed ?? 0}</p><p class="stat-status">Active bookings</p></div>
      <div class="card"><h3>Completed</h3><p class="stat-value">${c.completed ?? 0}</p><p class="stat-status">All time</p></div>
      <div class="card"><h3>Cancelled</h3><p class="stat-value">${c.cancelled ?? 0}</p><p class="stat-status">All time</p></div>
      <div class="card"><h3>Total</h3><p class="stat-value">${(c.confirmed??0)+(c.completed??0)+(c.cancelled??0)}</p><p class="stat-status">All bookings</p></div>
    </div>

    <div class="filter-bar">
      <h3>Filter Bookings</h3>
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="min-width:160px;">
          <label>Status</label>
          <select id="bookingStatusFilter" onchange="reloadBookings()">
            <option value="">All</option>
            <option value="confirmed"  ${statusF==='confirmed' ?'selected':''}>Confirmed</option>
            <option value="completed"  ${statusF==='completed' ?'selected':''}>Completed</option>
            <option value="cancelled"  ${statusF==='cancelled' ?'selected':''}>Cancelled</option>
          </select>
        </div>
        <div class="form-group" style="min-width:160px;">
          <label>Date</label>
          <input type="date" id="bookingDateFilter" value="${dateF}" onchange="reloadBookings()" />
        </div>
        <button class="btn-sm btn-secondary" onclick="clearBookingFilter()">Clear</button>
      </div>
    </div>

    <div id="bookingsListContainer">${renderBookingRequestCards(data.bookings || [])}</div>

    <div class="pagination">
      <button onclick="changeBookingsPage('prev')" class="btn-sm btn-secondary">← Prev</button>
      <span id="bookingsPageInfo">Page ${pg.page ?? 1} of ${pg.total_pages ?? 1}</span>
      <button onclick="changeBookingsPage('next')" class="btn-sm btn-secondary">Next →</button>
    </div>
  `;
}

window.reloadBookings = function() { loadPage('bookings'); };

window.clearBookingFilter = function() {
  loadPage('bookings');
};

window.changeBookingsPage = function(dir) {
  const el   = document.getElementById('bookingsPageInfo');
  const max  = parseInt(el?.textContent?.split('of')[1]?.trim() || '1');
  let newPage = bookingsCurrentPage + (dir === 'next' ? 1 : -1);
  newPage     = Math.max(1, Math.min(newPage, max));
  renderBookings(document.getElementById('mainContent'), newPage);
};

window.updateBookingStatus = async function(bookingId, newStatus) {
  const res = await apiFetch('api/trainer/bookings/update-status.php', {
    method: 'POST',
    body:   JSON.stringify({ booking_id: bookingId, status: newStatus }),
  });
  if (!res?.success) { toast(res?.message || 'Action failed.', 'error'); return; }
  toast(`Booking #${bookingId} marked as ${newStatus}.`);
  loadPage('bookings');
};

// ═══════════════════════════════════════════════════════════════════════════════
// ─── AVAILABILITY ─────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════
let availWeekOffset = 0;
let availSlotState  = {};  // "YYYY-MM-DD|HH:MM" → 'open' | 'blocked'
let availBooked     = {};  // "YYYY-MM-DD|HH:MM" → memberName
let availDateFrom   = '';
let availDateTo     = '';

const TIME_SLOTS = [
  '6:00 AM','7:00 AM','8:00 AM','9:00 AM','10:00 AM','11:00 AM',
  '12:00 PM','1:00 PM','2:00 PM','3:00 PM','4:00 PM','5:00 PM',
  '6:00 PM','7:00 PM','8:00 PM',
];

async function renderAvailability(container) {
  const data = await apiFetch(`api/trainer/availability/get.php?week_offset=${availWeekOffset}`);
  if (!data?.success) { container.innerHTML = '<div class="card"><p style="color:red;">Could not load availability.</p></div>'; return; }

  availSlotState = data.slots  || {};
  availBooked    = data.booked || {};
  availDateFrom  = data.date_from;
  availDateTo    = data.date_to;

  const { days } = getWeekDays(availWeekOffset);
  const weekLabel = getWeekLabel(availWeekOffset);

  container.innerHTML = `
    <div class="page-header"><h1>Availability</h1></div>

    <div class="card" style="margin-bottom:20px;">
      <h3 style="margin-bottom:10px;">How it works</h3>
      <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.7;">
        Click any time slot to toggle it <strong>open</strong> (members can book you) or <strong>blocked</strong>.
        Slots already booked by members are locked in orange. Click <strong>Save Availability</strong> when done.
      </p>
    </div>

    <div class="avail-legend">
      <div class="avail-legend-item">
        <div class="avail-legend-dot" style="background:var(--green-bg);border:2px solid #a5d6a7;"></div>Open
      </div>
      <div class="avail-legend-item">
        <div class="avail-legend-dot" style="background:#f5f5f5;border:2px solid #e0e0e0;"></div>Blocked
      </div>
      <div class="avail-legend-item">
        <div class="avail-legend-dot" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));"></div>Booked
      </div>
    </div>

    <div class="avail-week-nav">
      <button class="btn-sm btn-secondary" onclick="changeAvailWeek(-1)">◀ Prev Week</button>
      <span id="availWeekLabel">${weekLabel}</span>
      <button class="btn-sm btn-secondary" onclick="changeAvailWeek(1)">Next Week ▶</button>
    </div>

    <div id="availGrid" class="avail-week-grid">${buildAvailGrid(days)}</div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
      <button onclick="markAllOpen()">✓ Mark All Open</button>
      <button class="btn-secondary" onclick="markAllBlocked()">✕ Block All</button>
      <button class="btn-secondary" onclick="markWeekdaysOpen()">Mon–Fri Open</button>
    </div>

    <div class="option-bar">
      <button onclick="saveAvailability()" id="saveAvailBtn">💾 Save Availability</button>
      <button class="btn-secondary" onclick="reloadAvailability()">↺ Reload</button>
    </div>

    <p class="section-title" style="margin-top:8px;">Booked Sessions This Week</p>
    <div class="time-block-list">${renderBookedSlotsList(days)}</div>
  `;
}

function buildAvailGrid(days) {
  const today = toISODate(new Date());
  return days.map(d => {
    const iso     = toISODate(d);
    const isToday = iso === today;
    const dayName = d.toLocaleDateString('en-PH', { weekday: 'short' });

    let html = `<div class="avail-day-col">
      <div class="avail-day-header ${isToday ? 'is-today' : ''}">
        <div>${dayName}</div>
        <div style="font-size:1.1rem;font-weight:900;">${d.getDate()}</div>
      </div>`;

    TIME_SLOTS.forEach(slot => {
      const time24 = slot12to24(slot);
      const key    = `${iso}|${time24}`;
      const booked = availBooked[key];

      if (booked) {
        html += `<div class="avail-slot avail-slot-booked" title="Booked: ${booked}">
          <div style="font-size:0.68rem;">${slot.replace(' ','\n')}</div>
          <div style="font-size:0.6rem;margin-top:2px;opacity:0.9;">Booked</div>
        </div>`;
      } else {
        const state = availSlotState[key] || 'blocked';
        const cls   = state === 'open' ? 'avail-slot-open' : 'avail-slot-blocked';
        html += `<div class="avail-slot ${cls}" data-key="${key}" onclick="toggleSlot(this,'${key}')">
          <div style="font-size:0.68rem;">${slot.replace(' ','\n')}</div>
        </div>`;
      }
    });

    html += '</div>';
    return html;
  }).join('');
}

window.toggleSlot = function(el, key) {
  const next = (availSlotState[key] || 'blocked') === 'open' ? 'blocked' : 'open';
  availSlotState[key]  = next;
  el.className         = 'avail-slot ' + (next === 'open' ? 'avail-slot-open' : 'avail-slot-blocked');
};

window.changeAvailWeek = async function(dir) {
  availWeekOffset += dir;
  availSlotState  = {};
  availBooked     = {};
  await loadPage('availability');
};

window.reloadAvailability = async function() {
  availSlotState = {};
  availBooked    = {};
  await loadPage('availability');
};

window.markAllOpen = function() {
  const { days } = getWeekDays(availWeekOffset);
  days.forEach(d => TIME_SLOTS.forEach(s => {
    const key = `${toISODate(d)}|${slot12to24(s)}`;
    if (!availBooked[key]) availSlotState[key] = 'open';
  }));
  refreshAvailGrid();
};

window.markAllBlocked = function() {
  const { days } = getWeekDays(availWeekOffset);
  days.forEach(d => TIME_SLOTS.forEach(s => {
    const key = `${toISODate(d)}|${slot12to24(s)}`;
    if (!availBooked[key]) availSlotState[key] = 'blocked';
  }));
  refreshAvailGrid();
};

window.markWeekdaysOpen = function() {
  const { days } = getWeekDays(availWeekOffset);
  days.forEach(d => {
    if (d.getDay() === 0 || d.getDay() === 6) return;
    TIME_SLOTS.forEach(s => {
      const key = `${toISODate(d)}|${slot12to24(s)}`;
      if (!availBooked[key]) availSlotState[key] = 'open';
    });
  });
  refreshAvailGrid();
};

function refreshAvailGrid() {
  const { days } = getWeekDays(availWeekOffset);
  const g = document.getElementById('availGrid');
  if (g) g.innerHTML = buildAvailGrid(days);
}

window.saveAvailability = async function() {
  const btn = document.getElementById('saveAvailBtn');
  if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

  // Build payload from all non-booked slots
  const { days } = getWeekDays(availWeekOffset);
  const slots = [];
  days.forEach(d => {
    TIME_SLOTS.forEach(s => {
      const time24 = slot12to24(s);
      const key    = `${toISODate(d)}|${time24}`;
      if (!availBooked[key]) {
        slots.push({
          date:      toISODate(d),
          time:      time24,
          available: availSlotState[key] === 'open' ? 1 : 0,
        });
      }
    });
  });

  const res = await apiFetch('api/trainer/availability/save.php', {
    method: 'POST',
    body:   JSON.stringify({ slots }),
  });

  if (btn) { btn.disabled = false; btn.textContent = '💾 Save Availability'; }

  if (!res?.success) { toast(res?.message || 'Save failed.', 'error'); return; }
  toast(`Availability saved — ${res.saved} slots updated.`);
};

function renderBookedSlotsList(days) {
  const items = [];
  days.forEach(d => {
    TIME_SLOTS.forEach(s => {
      const key = `${toISODate(d)}|${slot12to24(s)}`;
      if (availBooked[key]) {
        items.push({
          date:   d.toLocaleDateString('en-PH', { weekday:'short', month:'short', day:'numeric' }),
          time:   s,
          member: availBooked[key],
        });
      }
    });
  });

  if (!items.length) {
    return '<p style="color:var(--text-muted);padding:16px;text-align:center;">No booked sessions this week.</p>';
  }

  return items.map(item => `
    <div class="time-block-item">
      <div class="time-block-dot tbd-booked"></div>
      <div class="time-block-info">
        ${item.date} — ${item.time}
        <div class="time-block-sub">Session with <strong>${esc(item.member)}</strong></div>
      </div>
      <div class="time-block-actions">
        <button class="btn-sm btn-secondary" onclick="loadPage('bookings')">View</button>
      </div>
    </div>`).join('');
}

// ═══════════════════════════════════════════════════════════════════════════════
// ─── MY MEMBERS ──────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════
async function renderMembers(container) {
  const data = await apiFetch('api/trainer/members/list.php');
  if (!data?.success) { container.innerHTML = '<div class="card"><p style="color:red;">Could not load members.</p></div>'; return; }

  const members = data.members || [];
  const s       = data.stats   || {};

  container.innerHTML = `
    <div class="page-header"><h1>My Members</h1></div>

    <div class="grid" style="margin-bottom:20px;">
      <div class="card"><h3>Total Members</h3><p class="stat-value">${s.total ?? members.length}</p><p class="stat-status">Active clients</p></div>
      <div class="card"><h3>Recurring</h3><p class="stat-value">${s.recurring ?? 0}</p><p class="stat-status">Weekly sessions</p></div>
      <div class="card"><h3>Avg Sessions</h3><p class="stat-value">${s.avg_sessions ?? 0}</p><p class="stat-status">Per member</p></div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Member</th><th>Plan</th><th>Focus Area</th>
            <th>Sessions</th><th>Last Session</th><th>Recurring</th>
          </tr>
        </thead>
        <tbody>
          ${!members.length
            ? '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px;">No members yet.</td></tr>'
            : members.map(m => `
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.85rem;flex-shrink:0;">
                      ${initials(m.member_name)}
                    </div>
                    <div>
                      <div style="font-weight:700;">${esc(m.member_name)}</div>
                      <div style="color:var(--text-muted);font-size:0.8rem;">${esc(m.email)}</div>
                    </div>
                  </div>
                </td>
                <td><span style="background:var(--blue-bg);color:var(--blue);padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700;">${esc(m.plan || '—')}</span></td>
                <td>${esc((m.focus_area||'').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()))}</td>
                <td style="font-weight:700;color:var(--primary);">${m.total_sessions}</td>
                <td style="font-size:0.88rem;">${fmtDate(m.last_session)}</td>
                <td>${m.recurring ? '<span class="badge badge-active">Weekly</span>' : '<span style="color:#bbb;">—</span>'}</td>
              </tr>`).join('')}
        </tbody>
      </table>
    </div>
  `;
}

// ═══════════════════════════════════════════════════════════════════════════════
// ─── EARNINGS ────────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════
async function renderEarnings(container) {
  const data = await apiFetch('api/trainer/earnings/summary.php?months=6');
  if (!data?.success) { container.innerHTML = '<div class="card"><p style="color:red;">Could not load earnings.</p></div>'; return; }

  const growth    = data.growth_pct ?? 0;
  const chartRows = data.monthly_chart   || [];
  const recent    = data.recent_payments || [];

  container.innerHTML = `
    <div class="page-header"><h1>Earnings</h1></div>

    <div class="grid" style="margin-bottom:24px;">
      <div class="card">
        <h3>This Month</h3>
        <p class="stat-value">₱${numFormat(data.this_month ?? 0)}</p>
        <p class="stat-status" style="color:${growth >= 0 ? 'var(--green)' : 'var(--red)'}">
          ${growth >= 0 ? '↑' : '↓'} ${Math.abs(growth)}% vs last month
        </p>
      </div>
      <div class="card">
        <h3>Sessions Completed</h3>
        <p class="stat-value">${data.sessions_month ?? 0}</p>
        <p class="stat-status">This month</p>
      </div>
      <div class="card">
        <h3>Avg / Session</h3>
        <p class="stat-value">₱${numFormat(data.avg_per_session ?? 0)}</p>
        <p class="stat-status">This month</p>
      </div>
      <div class="card">
        <h3>Last 6 Months</h3>
        <p class="stat-value">₱${numFormat(data.total_period ?? 0)}</p>
        <p class="stat-status">Total earned</p>
      </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
      <h3 style="margin-bottom:16px;">Earnings — Last 6 Months</h3>
      <div class="earnings-bar-wrap">
        ${renderEarningsChart(chartRows)}
      </div>
    </div>

    <div class="card">
      <h3 style="margin-bottom:16px;">Recent Payments Received</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Member</th><th>Focus</th><th>Duration</th><th>Amount</th></tr></thead>
          <tbody>
            ${!recent.length
              ? '<tr><td colspan="5" style="text-align:center;color:#999;padding:20px;">No payment records yet.</td></tr>'
              : recent.map(p => `
                <tr>
                  <td style="font-size:0.88rem;">${fmtDate(p.booking_date || p.created_at)}</td>
                  <td><strong>${esc(p.member_name)}</strong></td>
                  <td>${esc((p.focus_area||'').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()))}</td>
                  <td>${esc(p.session_duration || '—')}</td>
                  <td style="font-weight:700;color:var(--primary);">₱${numFormat(p.amount)}</td>
                </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function renderEarningsChart(months) {
  if (!months.length) return '<p style="color:var(--text-muted);text-align:center;padding:20px;">No earnings data yet.</p>';
  const maxVal = Math.max(...months.map(m => parseFloat(m.total)||0)) || 1;
  return months.map(m => {
    const h = Math.round((parseFloat(m.total) / maxVal) * 100);
    return `
      <div class="earnings-bar-col">
        <div class="earnings-bar-val">₱${numShort(m.total)}</div>
        <div class="earnings-bar" style="height:${h}px;"></div>
        <div class="earnings-bar-label">${m.label}</div>
      </div>`;
  }).join('');
}

// ═══════════════════════════════════════════════════════════════════════════════
// ─── PROFILE ─────────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════
async function renderProfile(container) {
  const data = await apiFetch('api/trainer/profile/get.php');
  if (!data?.success) { container.innerHTML = '<div class="card"><p style="color:red;">Could not load profile.</p></div>'; return; }

  const p = data.trainer;
  const initials2 = ((p.first_name?.[0] || '') + (p.last_name?.[0] || '')).toUpperCase();
  const tagsStr   = (p.specialty_tags || []).join(', ');

  container.innerHTML = `
    <div class="profile-header-card">
      <div class="profile-avatar-large">
        ${p.image_url
          ? `<img src="${esc(p.image_url)}" alt="${esc(p.first_name)}" />`
          : initials2}
      </div>
      <div class="profile-header-info">
        <div class="profile-header-name">${esc(p.first_name)} ${esc(p.last_name)}</div>
        <div class="profile-header-spec">${esc(p.specialty)}</div>
        <div class="profile-header-stats">
          <div><div class="ph-stat-value">⭐ ${Number(p.rating).toFixed(1)}</div><div class="ph-stat-label">Rating</div></div>
          <div><div class="ph-stat-value">${p.exp_years}+</div><div class="ph-stat-label">Years</div></div>
          <div><div class="ph-stat-value">${p.total_clients ?? p.client_count}</div><div class="ph-stat-label">Clients</div></div>
          <div><div class="ph-stat-value">₱${numFormat(p.session_rate)}</div><div class="ph-stat-label">/session</div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <h3 style="margin-bottom:20px;">Edit Profile</h3>
      <form id="profileForm">
        <div class="form-grid">
          <div class="form-group">
            <label>Specialty</label>
            <select name="specialty">
              ${['Yoga & Pilates Specialist','HIIT & CrossFit Coach','Strength & Conditioning Coach','Weight Loss & Nutrition Coach','Functional Training Specialist','Zumba & Dance Fitness Instructor','Bodybuilding Coach','Sports Performance Trainer','Rehabilitation & Mobility Coach','Beginner Fitness Coach']
                .map(s => `<option value="${s}" ${s === p.specialty ? 'selected' : ''}>${s}</option>`)
                .join('')}
            </select>
          </div>
          <div class="form-group">
            <label>Session Rate (₱/hr)</label>
            <input type="number" name="session_rate" value="${p.session_rate}" min="0" step="50" />
          </div>
          <div class="form-group">
            <label>Years of Experience</label>
            <input type="number" name="exp_years" value="${p.exp_years}" min="0" max="50" />
          </div>
          <div class="form-group">
            <label>Availability Status</label>
            <select name="availability">
              <option value="available" ${p.availability === 'available' ? 'selected' : ''}>Available</option>
              <option value="limited"   ${p.availability === 'limited'   ? 'selected' : ''}>Limited</option>
            </select>
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label>Specialty Tags <small style="color:#aaa;">(comma-separated)</small></label>
            <input type="text" name="specialty_tags" value="${esc(tagsStr)}" placeholder="HIIT, CrossFit, Strength" />
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label>Bio</label>
            <textarea name="bio" rows="4">${esc(p.bio || '')}</textarea>
          </div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" id="saveProfileBtn">💾 Save Changes</button>
          <button type="button" class="btn-secondary" onclick="this.closest('form').reset()">↺ Reset</button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:20px;">
      <h3 style="margin-bottom:20px;">Change Password</h3>
      <form id="passwordForm">
        <div class="form-grid">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="••••••••" autocomplete="current-password" />
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Min 8 characters" autocomplete="new-password" />
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter new password" autocomplete="new-password" />
          </div>
        </div>
        <div style="margin-top:16px;"><button type="submit">🔐 Update Password</button></div>
      </form>
    </div>
  `;

  document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveProfileBtn');
    btn.disabled = true; btn.textContent = 'Saving…';

    const fd   = new FormData(this);
    const body = Object.fromEntries(fd.entries());

    const res = await apiFetch('api/trainer/profile/update.php', {
      method: 'POST',
      body:   JSON.stringify(body),
    });

    btn.disabled = false; btn.textContent = '💾 Save Changes';
    if (!res?.success) { toast(res?.message || 'Save failed.', 'error'); return; }
    toast('Profile updated successfully!');
    // Refresh sidebar specialty
    const specEl = document.getElementById('sidebarSpec');
    if (specEl && body.specialty) specEl.textContent = body.specialty;
  });

  document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    if (fd.get('new_password') !== fd.get('confirm_password')) {
      toast('Passwords do not match.', 'error'); return;
    }

    const res = await apiFetch('api/trainer/auth/change-password.php', {
      method: 'POST',
      body:   JSON.stringify(Object.fromEntries(fd.entries())),
    });

    if (!res?.success) { toast(res?.message || 'Failed.', 'error'); return; }
    this.reset();
    toast('Password updated successfully!');
  });
}

// ═══════════════════════════════════════════════════════════════════════════════
// ─── SHARED RENDER HELPERS ───────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════

function renderSessionTimeline(sessions) {
  if (!sessions.length) {
    return '<p style="color:var(--text-muted);text-align:center;padding:20px;">No upcoming sessions.</p>';
  }
  return sessions.map(s => {
    const dotCls = s.status === 'completed' ? 'session-dot-completed'
                 : s.status === 'cancelled' ? 'session-dot-cancelled' : '';
    const icon   = s.status === 'completed' ? '✓' : s.status === 'cancelled' ? '✕' : '🏋️';
    return `
      <div class="session-item">
        <div class="session-dot ${dotCls}">${icon}</div>
        <div class="session-info">
          <div class="session-member">${esc(s.member_name)}</div>
          <div class="session-meta">
            ${fmtDate(s.booking_date)} · ${esc(s.booking_time)} · ${esc(s.session_duration)}
            <br>${badge(s.status)}
          </div>
        </div>
        <div class="session-price">₱${numFormat(s.total_price)}</div>
      </div>`;
  }).join('');
}

function renderBookingRequestCards(bookings) {
  if (!bookings.length) {
    return '<div class="card" style="text-align:center;color:var(--text-muted);padding:40px;">No bookings found.</div>';
  }

  return bookings.map(b => {
    const ini    = initials(b.member_name || '?');
    const tags   = [];
    if (b.focus_area)    tags.push(`<span class="req-tag req-tag-focus">${(b.focus_area||'').replace(/_/g,' ')}</span>`);
    if (b.fitness_level) tags.push(`<span class="req-tag req-tag-level">${b.fitness_level}</span>`);
    if (b.recurring)     tags.push(`<span class="req-tag req-tag-recurring">↻ Weekly</span>`);

    const today = toISODate(new Date());
    if (b.booking_date === today) tags.push(`<span class="req-tag req-tag-today">Today</span>`);

    const actions = b.status === 'confirmed'
      ? `<button class="btn-sm" onclick="updateBookingStatus(${b.id},'completed')">✓ Mark Done</button>
         <button class="btn-sm btn-danger" onclick="updateBookingStatus(${b.id},'cancelled')">Cancel</button>`
      : `<span style="color:var(--text-muted);font-size:0.82rem;">${ucFirst(b.status || '')}</span>`;

    return `
      <div class="booking-request-card">
        <div class="member-avatar">${ini}</div>
        <div class="booking-request-details">
          <div class="booking-member-name">${esc(b.member_name)}</div>
          <div class="booking-request-meta">
            📅 ${fmtDate(b.booking_date)} · ⏰ ${esc(b.booking_time)} · ⏱️ ${esc(b.session_duration)}
            ${b.member_plan ? `<br>📋 ${esc(b.member_plan)}` : ''}
            ${b.payment_method ? ` · 💳 ${ucFirst(b.payment_method)}` : ''}
          </div>
          <div class="booking-request-tags">${tags.join('')} ${badge(b.status)}</div>
        </div>
        <div class="booking-request-amount">
          <div class="booking-amount-label">Session Fee</div>
          <div class="booking-amount-value">₱${numFormat(b.total_price)}</div>
          <div class="booking-request-actions">${actions}</div>
        </div>
      </div>`;
  }).join('');
}

// ═══════════════════════════════════════════════════════════════════════════════
// ─── DATE / TIME UTILS ───────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════

function getWeekDays(offset = 0) {
  const today  = new Date(); today.setHours(0,0,0,0);
  const dow    = today.getDay();
  const monday = new Date(today);
  monday.setDate(today.getDate() - (dow === 0 ? 6 : dow - 1) + offset * 7);
  const days = Array.from({length:7}, (_, i) => {
    const d = new Date(monday); d.setDate(monday.getDate() + i); return d;
  });
  return { monday, days };
}

function getWeekLabel(offset) {
  if (offset === 0)  return 'This Week';
  if (offset === 1)  return 'Next Week';
  if (offset === -1) return 'Last Week';
  const { monday, days } = getWeekDays(offset);
  const sun = days[6];
  return `${monday.toLocaleDateString('en-PH',{month:'short',day:'numeric'})} – ${sun.toLocaleDateString('en-PH',{month:'short',day:'numeric'})}`;
}

function toISODate(date) {
  return date.toISOString().slice(0, 10);
}

function slot12to24(slot12) {
  const [time, period] = slot12.split(' ');
  let [h, m] = time.split(':').map(Number);
  if (period === 'PM' && h !== 12) h += 12;
  if (period === 'AM' && h === 12) h = 0;
  return `${String(h).padStart(2,'0')}:${String(m||0).padStart(2,'0')}`;
}

function fmtDate(str) {
  if (!str) return '—';
  const d = new Date(str + (str.includes('T') ? '' : 'T00:00:00'));
  return d.toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
}

// ═══════════════════════════════════════════════════════════════════════════════
// ─── GENERAL UTILS ───────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════════

function esc(str) {
  if (str == null) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function ucFirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

function numFormat(n) {
  return Number(n).toLocaleString('en-PH', { minimumFractionDigits:0, maximumFractionDigits:0 });
}

function numShort(n) {
  const v = parseFloat(n) || 0;
  if (v >= 1000) return (v/1000).toFixed(0) + 'K';
  return numFormat(v);
}

function initials(name) {
  return (name||'?').split(' ').map(n=>n[0]||'').join('').toUpperCase().slice(0,2);
}

function badge(status) {
  const map = {
    confirmed:'badge-confirmed', completed:'badge-completed',
    cancelled:'badge-cancelled', active:'badge-active', pending:'badge-pending',
  };
  return `<span class="badge ${map[(status||'').toLowerCase()]||'badge'}">${ucFirst(status||'—')}</span>`;
}

function toast(msg, type = 'success') {
  const el = document.getElementById('trainerToast');
  if (!el) return;
  el.textContent = msg;
  el.style.background = type === 'success' ? '#2e7d32' : type === 'error' ? '#c62828' : '#1565c0';
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 3500);
}

// ─── Logout ───────────────────────────────────────────────────────────────────
window.trainerLogout = async function() {
  await apiFetch('api/trainer/auth/logout.php', { method: 'POST' });
  window.location.href = 'login-page.php';
};
