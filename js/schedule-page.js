/**
 * schedule-page.js
 * Dynamically loads class schedules from api/user/schedule/list.php
 * Supports week grid view and list view, with filters.
 */

// ── State ─────────────────────────────────────────────────────────────────────
let allClasses   = [];
let currentView  = 'grid';
let currentWeekOffset = 0;   // 0 = this week, 1 = next week, etc.

// ── Date helpers ──────────────────────────────────────────────────────────────
const DAY_NAMES  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const DAY_ABBR   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const MONTH_ABBR = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function getWeekRange(offset = 0) {
  const today = new Date();
  const dayOfWeek = today.getDay();            // 0=Sun
  const monday = new Date(today);
  monday.setDate(today.getDate() - dayOfWeek + 1 + offset * 7);
  monday.setHours(0, 0, 0, 0);
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);
  sunday.setHours(23, 59, 59, 999);
  return { monday, sunday };
}

function toYMD(date) {
  return date.toISOString().slice(0, 10);
}

function fmtTime(datetimeStr) {
  if (!datetimeStr) return '';
  const d = new Date(datetimeStr);
  return d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
}

function fmtDateLabel(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return `${DAY_NAMES[d.getDay()]}, ${MONTH_ABBR[d.getMonth()]} ${d.getDate()}`;
}

function spotsColor(spots) {
  if (spots <= 0)  return '#c62828';
  if (spots <= 3)  return '#f57c00';
  return '#2e7d32';
}

// ── API fetch ─────────────────────────────────────────────────────────────────
async function fetchClasses(dateFrom, dateTo, className = '', trainerId = '') {
  const params = new URLSearchParams({
    date_from: dateFrom,
    date_to:   dateTo,
  });
  if (className)  params.set('class_name', className);
  if (trainerId)  params.set('trainer_id', trainerId);

  const res  = await fetch('api/user/schedule/list.php?' + params);
  const data = await res.json();

  if (!data.success) {
    if (res.status === 401) { window.location.href = 'login-page.php'; return []; }
    return [];
  }
  return data.classes || [];
}

// ── Filters ───────────────────────────────────────────────────────────────────
function getFilters() {
  return {
    className:  document.getElementById('filterClass')?.value    || '',
    trainerId:  document.getElementById('filterTrainer')?.value  || '',
    timeOfDay:  document.getElementById('filterTime')?.value     || '',
  };
}

function matchesTimeFilter(scheduledAt, timeOfDay) {
  if (!timeOfDay) return true;
  const hour = new Date(scheduledAt).getHours();
  if (timeOfDay === 'morning')   return hour >= 6  && hour < 12;
  if (timeOfDay === 'afternoon') return hour >= 12 && hour < 17;
  if (timeOfDay === 'evening')   return hour >= 17 && hour < 21;
  return true;
}

// ── Grid View ─────────────────────────────────────────────────────────────────
function buildGridView(classes) {
  const { monday } = getWeekRange(currentWeekOffset);
  const days = Array.from({ length: 7 }, (_, i) => {
    const d = new Date(monday);
    d.setDate(monday.getDate() + i);
    return d;
  });

  const TIME_SLOTS = ['6:00 AM','7:00 AM','8:00 AM','9:00 AM','10:00 AM',
                      '11:00 AM','12:00 PM','1:00 PM','2:00 PM','3:00 PM',
                      '4:00 PM','5:00 PM','6:00 PM','7:00 PM','8:00 PM'];

  // Build lookup: dateStr -> hour -> [classes]
  const lookup = {};
  classes.forEach(cls => {
    const dt   = new Date(cls.scheduled_at);
    const date = toYMD(dt);
    const hour = dt.getHours();
    if (!lookup[date]) lookup[date] = {};
    if (!lookup[date][hour]) lookup[date][hour] = [];
    lookup[date][hour].push(cls);
  });

  // Headers
  let html = `
    <div class="schedule-grid-wrap">
      <div class="sg-week-nav">
        <button class="btn btn-sm" id="prevWeek">‹ Prev</button>
        <span id="weekLabel"></span>
        <button class="btn btn-sm" id="nextWeek">Next ›</button>
      </div>
      <div class="sg-scroll">
        <table class="sg-table">
          <thead>
            <tr>
              <th class="sg-time-col">Time</th>`;

  days.forEach(d => {
    const isToday = toYMD(d) === toYMD(new Date());
    html += `<th class="${isToday ? 'sg-today' : ''}">
      <div class="sg-day-name">${DAY_ABBR[d.getDay()]}</div>
      <div class="sg-day-num ${isToday ? 'sg-today-num' : ''}">${d.getDate()}</div>
    </th>`;
  });
  html += `</tr></thead><tbody>`;

  TIME_SLOTS.forEach(slot => {
    // Parse slot to hour integer
    const [h, ap] = slot.split(':');
    let hour = parseInt(h);
    if (ap && ap.includes('PM') && hour !== 12) hour += 12;
    if (ap && ap.includes('AM') && hour === 12) hour = 0;

    html += `<tr><td class="sg-time-cell">${slot}</td>`;
    days.forEach(d => {
      const dateStr = toYMD(d);
      const cellClasses = lookup[dateStr]?.[hour] || [];
      if (cellClasses.length) {
        const cls   = cellClasses[0]; // show first if multiple
        const spots = parseInt(cls.spots_remaining) ?? 0;
        const full  = spots <= 0;
        const booked = cls.already_booked == 1;
        html += `<td class="sg-cell sg-has-class ${full ? 'sg-full' : ''} ${booked ? 'sg-booked' : ''}"
                    onclick="${!full && !booked ? `openBookingModal(${cls.id}, '${cls.class_name.replace(/'/g,"\\'")}', '${toYMD(d)}', '${slot}', ${cls.trainer_id || 0}, '${(cls.trainer_name||'').replace(/'/g,"\\'")}')` : ''}">
          <div class="sg-class-name">${cls.class_name}</div>
          <div class="sg-class-trainer">${cls.trainer_name || ''}</div>
          <div class="sg-class-spots" style="color:${spotsColor(spots)}">
            ${booked ? '✓ Booked' : full ? 'Full' : spots + ' spots'}
          </div>
        </td>`;
      } else {
        html += `<td class="sg-cell"></td>`;
      }
    });
    html += `</tr>`;
  });

  html += `</tbody></table></div></div>`;
  return html;
}

// ── List View ─────────────────────────────────────────────────────────────────
function buildListView(classes) {
  if (!classes.length) {
    return `<div class="sg-empty">
      <div style="font-size:3rem;margin-bottom:12px;">📅</div>
      <p>No classes found for this week.</p>
      <p style="color:#999;font-size:0.9rem;margin-top:6px;">Try adjusting your filters or check another week.</p>
    </div>`;
  }

  // Group by date
  const grouped = {};
  classes.forEach(cls => {
    const date = toYMD(new Date(cls.scheduled_at));
    if (!grouped[date]) grouped[date] = [];
    grouped[date].push(cls);
  });

  let html = '';
  Object.keys(grouped).sort().forEach(date => {
    html += `<div class="day-section">
      <h2 class="day-header">${fmtDateLabel(date)}</h2>`;

    grouped[date].sort((a,b) => new Date(a.scheduled_at) - new Date(b.scheduled_at)).forEach(cls => {
      const spots  = parseInt(cls.spots_remaining) ?? 0;
      const full   = spots <= 0;
      const booked = cls.already_booked == 1;
      const time   = fmtTime(cls.scheduled_at);

      html += `
        <div class="class-card ${booked ? 'class-card-booked' : ''}">
          <div class="class-time">${time}</div>
          <div class="class-info">
            <h3>${cls.class_name}</h3>
            <div class="class-meta">
              ${cls.trainer_name ? `<span>👤 ${cls.trainer_name}</span>` : ''}
              ${cls.duration_minutes ? `<span>⏱️ ${cls.duration_minutes} min</span>` : ''}
              ${cls.location ? `<span>📍 ${cls.location}</span>` : ''}
              <span style="color:${spotsColor(spots)};font-weight:600;">
                ${booked ? '✓ Already Booked' : full ? 'Class Full' : spots + ' spots left'}
              </span>
            </div>
            ${cls.trainer_specialty ? `<div class="class-specialty">${cls.trainer_specialty}</div>` : ''}
          </div>
          <div class="class-action">
            ${booked
              ? `<button class="btn btn-booked" disabled>Booked ✓</button>`
              : full
              ? `<button class="btn btn-full" disabled>Full</button>`
              : `<button class="btn" onclick="openBookingModal(${cls.id}, '${cls.class_name.replace(/'/g,"\\'")}', '${toYMD(new Date(cls.scheduled_at))}', '${time}', ${cls.trainer_id || 0}, '${(cls.trainer_name||'').replace(/'/g,"\\'")}')">Book Now</button>`
            }
          </div>
        </div>`;
    });
    html += `</div>`;
  });
  return html;
}

// ── Booking Modal ─────────────────────────────────────────────────────────────
function openBookingModal(scheduleId, className, date, time, trainerId, trainerName) {
  const modal = document.getElementById('bookingModal');
  document.getElementById('modalClassName').textContent  = className;
  document.getElementById('modalDate').textContent       = fmtDateLabel(date);
  document.getElementById('modalTime').textContent       = time;
  document.getElementById('modalTrainer').textContent    = trainerName || '—';

  document.getElementById('modalScheduleId').value = scheduleId;
  document.getElementById('modalClassNameInput').value = className;
  document.getElementById('modalDateInput').value  = date;
  document.getElementById('modalTimeInput').value  = time;

  modal.classList.add('open');
}

function closeBookingModal() {
  document.getElementById('bookingModal').classList.remove('open');
}

async function submitBooking() {
  const method = document.querySelector('input[name="modal_payment_method"]:checked')?.value;
  if (!method) { alert('Please select a payment method.'); return; }

  const fd = new FormData();
  fd.append('class_schedule_id', document.getElementById('modalScheduleId').value);
  fd.append('class_name',        document.getElementById('modalClassNameInput').value);
  fd.append('booking_date',      document.getElementById('modalDateInput').value);
  fd.append('booking_time',      document.getElementById('modalTimeInput').value);
  fd.append('payment_method',    method);

  if (typeof showLoading === 'function') showLoading('Booking class...');

  try {
    const res    = await fetch('api/bookings/book-class.php', { method: 'POST', body: fd });
    const result = await res.json();
    if (typeof hideLoading === 'function') hideLoading();

    if (result.success) {
      closeBookingModal();
      showToast('Class booked successfully! 🎉', 'success');
      loadSchedule(); // refresh
    } else {
      showToast(result.message || 'Booking failed.', 'error');
    }
  } catch (err) {
    if (typeof hideLoading === 'function') hideLoading();
    showToast('Something went wrong. Please try again.', 'error');
  }
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(message, type = 'info') {
  let toast = document.getElementById('scheduleToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'scheduleToast';
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.className   = `schedule-toast schedule-toast-${type} show`;
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove('show'), 3500);
}

// ── Populate trainer filter ───────────────────────────────────────────────────
async function populateTrainerFilter() {
  try {
    const res  = await fetch('api/trainers/list.php');
    const data = await res.json();
    if (!data.success) return;
    const sel = document.getElementById('filterTrainer');
    if (!sel) return;
    data.trainers.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.id;
      opt.textContent = t.full_name;
      sel.appendChild(opt);
    });
  } catch (_) {}
}

// ── Main render ───────────────────────────────────────────────────────────────
async function loadSchedule() {
  const { monday, sunday } = getWeekRange(currentWeekOffset);
  const filters = getFilters();

  // Update week label
  const label = document.getElementById('weekLabel');
  if (label) {
    label.textContent = currentWeekOffset === 0
      ? 'This Week'
      : currentWeekOffset === 1 ? 'Next Week'
      : currentWeekOffset === -1 ? 'Last Week'
      : `${MONTH_ABBR[monday.getMonth()]} ${monday.getDate()} – ${MONTH_ABBR[sunday.getMonth()]} ${sunday.getDate()}`;
  }

  const container = document.getElementById('scheduleContent');
  if (!container) return;
  container.innerHTML = `<div class="sg-loading"><div class="sg-spinner"></div> Loading schedule…</div>`;

  try {
    allClasses = await fetchClasses(
      toYMD(monday), toYMD(sunday),
      filters.className, filters.trainerId
    );

    // Apply time-of-day filter client-side (not in API)
    const filtered = allClasses.filter(cls =>
      matchesTimeFilter(cls.scheduled_at, filters.timeOfDay)
    );

    if (currentView === 'grid') {
      container.innerHTML = buildGridView(filtered);
      // Attach week nav buttons
      document.getElementById('prevWeek')?.addEventListener('click', () => { currentWeekOffset--; loadSchedule(); });
      document.getElementById('nextWeek')?.addEventListener('click', () => { currentWeekOffset++; loadSchedule(); });
      updateWeekLabel();
    } else {
      container.innerHTML = buildListView(filtered);
    }

  } catch (err) {
    console.error('Schedule load error:', err);
    container.innerHTML = `<div class="sg-empty"><p>Could not load schedule. Please try again.</p></div>`;
  }
}

function updateWeekLabel() {
  const { monday, sunday } = getWeekRange(currentWeekOffset);
  const lbl = document.getElementById('weekLabel');
  if (!lbl) return;
  if (currentWeekOffset === 0) lbl.textContent = 'This Week';
  else if (currentWeekOffset === 1) lbl.textContent = 'Next Week';
  else if (currentWeekOffset === -1) lbl.textContent = 'Last Week';
  else lbl.textContent = `${MONTH_ABBR[monday.getMonth()]} ${monday.getDate()} – ${MONTH_ABBR[sunday.getMonth()]} ${sunday.getDate()}`;
}

// ── View toggle ───────────────────────────────────────────────────────────────
window.toggleView = function(view) {
  currentView = view;
  document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
  document.querySelector(`.view-btn[data-view="${view}"]`)?.classList.add('active');
  loadSchedule();
};

// ── Expose for onclick ────────────────────────────────────────────────────────
window.openBookingModal  = openBookingModal;
window.closeBookingModal = closeBookingModal;
window.submitBooking     = submitBooking;

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  populateTrainerFilter();
  loadSchedule();

  // Filter listeners
  ['filterClass','filterTrainer','filterTime'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', loadSchedule);
  });

  // Close modal on overlay click
  document.getElementById('bookingModal')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeBookingModal();
  });
});