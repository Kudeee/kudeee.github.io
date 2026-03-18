import { renderPopUP, showPopUP, handleOk, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

render('#pop-up', "popUPOpt", renderPopUP);

window.handleOk    = handleOk;
window.closePopUp  = closePopUp;
window.switchTab   = switchTab;
window.registerEvent           = registerEvent;
window.closeEventModal         = closeEventModal;
window.submitEventRegistration = submitEventRegistration;

// ─── Plan upgrade map ─────────────────────────────────────────────────────────
const PLAN_UPGRADE = {
  'BASIC PLAN': {
    label: 'Upgrade to Premium',
    paymentUrl: 'payment.php?type=change&plan=PREMIUM%20PLAN&billing=monthly',
  },
  'PREMIUM PLAN': {
    label: 'Upgrade to VIP',
    paymentUrl: 'payment.php?type=upgrade',
  },
};

// ─── Load member + subscription data ─────────────────────────────────────────
async function loadMemberData() {
  try {
    const res  = await fetch('api/user/membership/info.php');
    const data = await res.json();

    if (!data.success) { window.location.href = 'login-page.php'; return; }

    const m   = data.member;
    const sub = data.subscription;

    const welcomeEl = document.getElementById('welcomeHeading');
    if (welcomeEl) welcomeEl.textContent = `Welcome Back, ${m.first_name}!`;

    const planBadge = document.getElementById('planBadge');
    if (planBadge) planBadge.textContent = m.plan;

    const planNameEl = document.getElementById('planName');
    if (planNameEl) planNameEl.textContent = m.plan.replace(' PLAN', '');

    if (sub) {
      const expiry   = new Date(sub.expiry_date);
      const today    = new Date();
      const daysLeft = Math.max(0, Math.ceil((expiry - today) / 86400000));
      const options  = { month: 'short', day: 'numeric', year: 'numeric' };

      const nextBillingEl = document.getElementById('nextBilling');
      if (nextBillingEl) nextBillingEl.textContent = expiry.toLocaleDateString('en-PH', options);

      const daysEl = document.getElementById('daysRemaining');
      if (daysEl) daysEl.textContent = daysLeft + ' Days';

      const isRecurring = sub.is_recurring !== undefined ? sub.is_recurring : 1;
      if (typeof window.initAutoRenewToggle === 'function') {
        window.initAutoRenewToggle(isRecurring);
      }
    }

    try {
      const userNameEl = document.querySelector('.user-profile div div:first-child');
      const userPlanEl = document.querySelector('.user-profile div div:last-child');
      if (userNameEl) userNameEl.textContent = m.first_name + ' ' + m.last_name;
      if (userPlanEl) userPlanEl.textContent = m.plan;
      const avatarEl = document.querySelector('.user-avatar');
      if (avatarEl) avatarEl.textContent = (m.first_name[0] + m.last_name[0]).toUpperCase();
    } catch (_) {}

    updateUpgradeButton(m.plan);

  } catch (err) {
    console.warn('Could not load member data:', err);
  }
}

function updateUpgradeButton(currentPlan) {
  const upgradeBtn = document.getElementById('upgradeBtn');
  if (!upgradeBtn) return;
  const upgrade = PLAN_UPGRADE[currentPlan];
  if (!upgrade) { upgradeBtn.style.display = 'none'; return; }
  upgradeBtn.textContent = upgrade.label;
  upgradeBtn.onclick = () => { location.href = upgrade.paymentUrl; };
}

// ─── Booking Carousel ─────────────────────────────────────────────────────────
let allBookings    = [];
let carouselIndex  = 0;

async function loadAllUpcomingBookings() {
  try {
    const res  = await fetch('api/user/schedule/all-upcoming.php');
    const data = await res.json();

    if (!data.success) return;

    allBookings   = data.bookings || [];
    carouselIndex = 0;

    if (!allBookings.length) {
      renderEmptyNextClass();
    } else {
      renderCarouselSlide(carouselIndex);
    }

  } catch (err) {
    console.warn('Could not load upcoming bookings:', err);
  }
}

function renderCarouselSlide(index) {
  const booking = allBookings[index];
  if (!booking) return;

  const titleEl    = document.getElementById('nextClassName');
  const timeEl     = document.getElementById('nextClassTime');
  const dateEl     = document.getElementById('nextClassDate');
  const trainerEl  = document.getElementById('nextClassTrainer');
  const durationEl = document.getElementById('nextClassDuration');

  if (titleEl)    titleEl.textContent    = booking.class_name.toUpperCase();
  if (timeEl)     timeEl.textContent     = booking.time_label;
  if (dateEl)     dateEl.textContent     = booking.date_label;
  if (trainerEl)  trainerEl.textContent  = booking.trainer_name || '—';
  if (durationEl) durationEl.textContent = booking.duration_label;

  currentBooking = booking;
  updateCarouselCounter();
  updateCarouselButtons();
  animateSlide();
}

function animateSlide() {
  const content = document.querySelector('.next-action-content');
  if (!content) return;
  content.classList.remove('carousel-slide-in');
  void content.offsetWidth;
  content.classList.add('carousel-slide-in');
}

function updateCarouselCounter() {
  const counterEl = document.getElementById('carouselCounter');
  if (!counterEl) return;
  if (allBookings.length <= 1) { counterEl.style.display = 'none'; return; }
  counterEl.style.display = '';
  counterEl.textContent = `${carouselIndex + 1} / ${allBookings.length}`;
}

function updateCarouselButtons() {
  const prevBtn = document.getElementById('carouselPrev');
  const nextBtn = document.getElementById('carouselNext');
  if (!prevBtn || !nextBtn) return;
  const navEl = document.getElementById('carouselNav');
  if (navEl) navEl.style.display = allBookings.length > 1 ? 'flex' : 'none';
  prevBtn.disabled = carouselIndex <= 0;
  nextBtn.disabled = carouselIndex >= allBookings.length - 1;
}

window.carouselPrev = function () {
  if (carouselIndex > 0) { carouselIndex--; renderCarouselSlide(carouselIndex); }
};

window.carouselNext = function () {
  if (carouselIndex < allBookings.length - 1) { carouselIndex++; renderCarouselSlide(carouselIndex); }
};

function renderEmptyNextClass() {
  const section = document.querySelector('.next-action-section');
  if (section) {
    section.innerHTML = `
      <div class="next-action-content" style="text-align:center;padding:20px 0;">
        <div class="next-action-label">Your Next Class</div>
        <h2 class="next-action-title" style="font-size:2.2rem;opacity:0.55;letter-spacing:3px;">NO CLASS YET</h2>
        <p style="color:#888;margin:12px 0 30px;font-size:1rem;">You have no upcoming classes scheduled.</p>
        <div class="action-buttons" style="justify-content:center;">
          <button class="btn btn-outline" onclick="window.location.href='book-class-page.php'">Book a Class</button>
        </div>
      </div>`;
  }
}

let currentBooking = null;

async function loadNextBooking() {
  await loadAllUpcomingBookings();
}

// ─── Cancel booking ───────────────────────────────────────────────────────────
document.getElementById('CancelBooking').addEventListener('click', () => {
  if (!currentBooking) { showPopUP('No booking to cancel.'); return; }
  showPopUP(`Cancel your ${currentBooking.class_name} booking?`);

  window.handleOk = async function () {
    closePopUp();
    showLoading('Cancelling booking...');
    try {
      const fd = new FormData();
      fd.append('type',       currentBooking.booking_type);
      fd.append('booking_id', currentBooking.booking_id);
      const res    = await fetch('api/bookings/cancel.php', { method: 'POST', body: fd });
      const result = await res.json();
      hideLoading();
      if (result.success) {
        allBookings.splice(carouselIndex, 1);
        if (allBookings.length === 0) {
          renderEmptyNextClass();
        } else {
          carouselIndex = Math.min(carouselIndex, allBookings.length - 1);
          renderCarouselSlide(carouselIndex);
        }
        render('#pop-up', 'done', renderPopUP);
        window.closePopUp = closePopUp;
        showPopUP('Booking cancelled successfully.');
      } else {
        render('#pop-up', 'warning', renderPopUP);
        window.closePopUp = closePopUp;
        showPopUP(result.message || 'Could not cancel booking.');
      }
    } catch (err) {
      hideLoading();
      showPopUP('Something went wrong. Please try again.');
    }
  };
});

// ─── Booked Trainers ──────────────────────────────────────────────────────────
let pendingCancelId = null;

/**
 * ── buildTrainerBookingItem ────────────────────────────────────────────────────
 * Builds one trainer booking card HTML string.
 * Now includes a Reschedule button that opens the reschedule modal.
 */
function buildTrainerBookingItem(b) {
  const initials = b.trainer_name
    ? b.trainer_name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
    : '?';

  const avatarHtml = b.image_url
    ? `<div class="trainer-avatar"><img src="${b.image_url}" alt="${b.trainer_name}" onerror="this.parentElement.textContent='${initials}'"/></div>`
    : `<div class="trainer-avatar">${initials}</div>`;

  const price = Number(b.total_price).toLocaleString('en-PH');
  const isRecurring = parseInt(b.recurring) === 1;

  const badges = [];
  if (b.specialty)   badges.push(`<span class="tb-badge tb-badge-specialty">${b.specialty}</span>`);
  if (b.focus_label) badges.push(`<span class="tb-badge tb-badge-focus">${b.focus_label}</span>`);
  if (isRecurring)   badges.push(`<span class="tb-badge tb-badge-recurring" id="badge-rec-${b.booking_id}">↻ Weekly</span>`);
  if (b.date_label === 'Today') badges.push(`<span class="tb-badge tb-badge-today">Today</span>`);

  // ── Recurring toggle button ────────────────────────────────────────────────
  const recurringBtn = isRecurring
    ? `<button class="tb-action-btn tb-btn-unrecurring" id="rec-btn-${b.booking_id}"
         onclick="toggleRecurring(${b.booking_id}, 0)" title="Remove weekly repeat">
         ↻ Weekly On
       </button>`
    : `<button class="tb-action-btn tb-btn-recurring" id="rec-btn-${b.booking_id}"
         onclick="toggleRecurring(${b.booking_id}, 1)" title="Set as weekly repeat">
         ↻ Make Weekly
       </button>`;

  // ── Reschedule button ──────────────────────────────────────────────────────
  // Uses trainer_id from the API response; falls back gracefully if absent.
  const trainerId   = b.trainer_id || 0;
  const trainerName = (b.trainer_name || '').replace(/'/g, "\\'");
  const bookingDate = b.booking_date || '';
  const bookingTime = (b.booking_time || '').replace(/'/g, "\\'");

  const rescheduleBtn = `<button
    class="tb-action-btn tb-btn-reschedule"
    onclick="openRescheduleModal(${b.booking_id}, ${trainerId}, '${trainerName}', '${bookingDate}', '${bookingTime}')"
    title="Reschedule this session">
    ↺ Reschedule
  </button>`;

  // ── Cancel button ──────────────────────────────────────────────────────────
  const cancelBtn = `<button class="tb-action-btn tb-btn-cancel"
      onclick="openTrainerCancelModal(${b.booking_id}, '${b.trainer_name.replace(/'/g,"\\'")}', '${b.date_label}', '${b.time_label}')"
      title="Cancel this session">
      ✕ Cancel
    </button>`;

  return `
    <div class="trainer-booking-item" id="trainer-booking-${b.booking_id}">
      ${avatarHtml}
      <div class="trainer-booking-details">
        <div class="trainer-booking-name">${b.trainer_name}</div>
        <div class="trainer-booking-meta">
          ${b.date_label} &bull; ${b.time_label} &bull; ${b.session_duration || b.session_minutes + ' min'}
        </div>
        <div class="trainer-booking-badges" id="badges-${b.booking_id}">${badges.join('')}</div>
      </div>
      <div class="trainer-actions">
        ${recurringBtn}
        ${rescheduleBtn}
        ${cancelBtn}
      </div>
      <div class="trainer-booking-price">
        <div class="trainer-price-value">₱${price}</div>
        <div class="trainer-price-label">session fee</div>
      </div>
    </div>`;
}

async function toggleRecurring(bookingId, newValue) {
  const btn = document.getElementById(`rec-btn-${bookingId}`);
  if (btn) { btn.disabled = true; btn.textContent = '…'; }

  try {
    const fd = new FormData();
    fd.append('booking_id', bookingId);
    fd.append('recurring',  newValue);

    const res    = await fetch('api/bookings/toggle-recurring.php', { method: 'POST', body: fd });
    const result = await res.json();

    if (!result.success) {
      if (btn) { btn.disabled = false; btn.textContent = newValue ? '↻ Make Weekly' : '↻ Weekly On'; }
      showPopUP(result.message || 'Could not update recurring status.');
      return;
    }

    if (btn) {
      btn.disabled = false;
      if (newValue === 1) {
        btn.textContent = '↻ Weekly On';
        btn.className   = 'tb-action-btn tb-btn-unrecurring';
        btn.title       = 'Remove weekly repeat';
        btn.onclick     = () => toggleRecurring(bookingId, 0);
      } else {
        btn.textContent = '↻ Make Weekly';
        btn.className   = 'tb-action-btn tb-btn-recurring';
        btn.title       = 'Set as weekly repeat';
        btn.onclick     = () => toggleRecurring(bookingId, 1);
      }
    }

    const badgesEl = document.getElementById(`badges-${bookingId}`);
    if (badgesEl) {
      const existingBadge = document.getElementById(`badge-rec-${bookingId}`);
      if (newValue === 1 && !existingBadge) {
        const span = document.createElement('span');
        span.id        = `badge-rec-${bookingId}`;
        span.className = 'tb-badge tb-badge-recurring';
        span.textContent = '↻ Weekly';
        badgesEl.appendChild(span);
      } else if (newValue === 0 && existingBadge) {
        existingBadge.remove();
      }
    }

    const card = document.getElementById(`trainer-booking-${bookingId}`);
    if (card) {
      card.style.borderColor = newValue ? '#a5d6a7' : '#ffcc80';
      setTimeout(() => { card.style.borderColor = 'transparent'; }, 1200);
    }

  } catch (err) {
    console.warn('toggleRecurring error:', err);
    if (btn) { btn.disabled = false; btn.textContent = newValue ? '↻ Make Weekly' : '↻ Weekly On'; }
    showPopUP('Something went wrong. Please try again.');
  }
}

function openTrainerCancelModal(bookingId, trainerName, dateLabel, timeLabel) {
  pendingCancelId = bookingId;
  const desc = document.getElementById('trainerCancelDesc');
  if (desc) {
    desc.textContent = `Cancel your session with ${trainerName} on ${dateLabel} at ${timeLabel}?`;
  }
  document.getElementById('trainerCancelModal').classList.add('open');
}

function closeTrainerCancelModal() {
  pendingCancelId = null;
  document.getElementById('trainerCancelModal').classList.remove('open');
}

async function confirmTrainerCancel() {
  if (!pendingCancelId) return;
  const bookingId = pendingCancelId;
  closeTrainerCancelModal();

  const card = document.getElementById(`trainer-booking-${bookingId}`);
  if (card) { card.style.opacity = '0.45'; card.style.pointerEvents = 'none'; }

  showLoading('Cancelling session…');

  try {
    const fd = new FormData();
    fd.append('type',       'trainer');
    fd.append('booking_id', bookingId);

    const res    = await fetch('api/bookings/cancel.php', { method: 'POST', body: fd });
    const result = await res.json();
    hideLoading();

    if (result.success) {
      if (card) {
        card.style.transition = 'all 0.35s ease';
        card.style.maxHeight  = card.offsetHeight + 'px';
        card.style.overflow   = 'hidden';
        requestAnimationFrame(() => {
          card.style.maxHeight    = '0';
          card.style.padding      = '0';
          card.style.marginBottom = '0';
          card.style.opacity      = '0';
        });
        setTimeout(() => card.remove(), 370);
      }

      setTimeout(() => {
        const scroll = document.getElementById('trainerBookingsScroll');
        if (scroll && !scroll.querySelector('.trainer-booking-item')) {
          scroll.innerHTML = `
            <div class="trainers-empty">
              <div class="trainers-empty-icon"></div>
              <p>No upcoming trainer sessions.</p>
              <p style="margin-top:6px;">
                <a href="book-trainer-page.php" style="color:#ff6b35;font-weight:600;text-decoration:none;">
                  Browse trainers and book a session →
                </a>
              </p>
            </div>`;
        }
      }, 400);

    } else {
      if (card) { card.style.opacity = '1'; card.style.pointerEvents = ''; }
      render('#pop-up', 'warning', renderPopUP);
      window.closePopUp = closePopUp;
      showPopUP(result.message || 'Could not cancel this session.');
    }
  } catch (err) {
    hideLoading();
    if (card) { card.style.opacity = '1'; card.style.pointerEvents = ''; }
    showPopUP('Something went wrong. Please try again.');
  }
}

window.toggleRecurring         = toggleRecurring;
window.openTrainerCancelModal  = openTrainerCancelModal;
window.closeTrainerCancelModal = closeTrainerCancelModal;
window.confirmTrainerCancel    = confirmTrainerCancel;

async function loadTrainerBookings() {
  const container = document.getElementById('trainerBookingsScroll');
  if (!container) return;

  container.innerHTML = '<div class="trainers-loading">Loading your trainer sessions…</div>';

  try {
    const res  = await fetch('api/user/trainers/my-bookings.php?status=confirmed&upcoming=0');
    const data = await res.json();

    if (!data.success) {
      if (res.status === 401) { window.location.href = 'login-page.php'; return; }
      container.innerHTML = `<div class="trainers-empty"><div class="trainers-empty-icon">⚠️</div><p>Could not load trainer sessions.</p></div>`;
      return;
    }

    if (!data.bookings || !data.bookings.length) {
      container.innerHTML = `
        <div class="trainers-empty">
          <div class="trainers-empty-icon"></div>
          <p>You haven't booked any trainer sessions yet.</p>
          <p style="margin-top:6px;">
            <a href="book-trainer-page.php" style="color:#ff6b35;font-weight:600;text-decoration:none;">
              Browse trainers and book a session →
            </a>
          </p>
        </div>`;
      return;
    }

    container.innerHTML = data.bookings.map(buildTrainerBookingItem).join('');

  } catch (err) {
    console.warn('Could not load trainer bookings:', err);
    container.innerHTML = `<div class="trainers-empty"><div class="trainers-empty-icon">⚠️</div><p>Failed to load trainer sessions.</p></div>`;
  }
}

// ─── Refresh trainer bookings after a successful reschedule ──────────────────
window.addEventListener('trainerSessionRescheduled', function () {
  loadTrainerBookings();
});

// ─── Events helpers ───────────────────────────────────────────────────────────
const MONTH_ABBR = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];

function fmtEventDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return { day: d.getDate(), month: MONTH_ABBR[d.getMonth()] };
}

function fmtEventTime(timeStr) {
  if (!timeStr) return '';
  try {
    return new Date('1970-01-01T' + timeStr).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
  } catch { return timeStr; }
}

function buildEventItem(ev, showRegisterBtn) {
  const { day, month } = fmtEventDate(ev.event_date);
  const time  = fmtEventTime(ev.event_time);
  const fee   = parseFloat(ev.fee) > 0 ? `₱${Number(ev.fee).toLocaleString('en-PH')}` : 'Free';
  const spots = ev.spots_remaining ?? (ev.max_attendees - ev.current_attendees);

  const badges = [];
  if (ev.registration_status === 'registered') badges.push(`<span class="event-badge badge-registered">✓ Registered</span>`);
  if (ev.is_members_only)                      badges.push(`<span class="event-badge badge-members">Members Only</span>`);
  if (parseFloat(ev.fee) <= 0)                 badges.push(`<span class="event-badge badge-free">Free</span>`);
  else                                         badges.push(`<span class="event-badge badge-paid">${fee}</span>`);

  const actionBtn = showRegisterBtn
    ? (ev.already_registered || ev.registration_status === 'registered'
        ? `<button class="btn" disabled style="opacity:0.55;cursor:default;">Registered</button>`
        : `<button class="btn btn-outline" onclick="registerEvent(${ev.id}, '${(ev.name||'').replace(/'/g,"\\'")}', '${ev.event_date}', '${(ev.location||'').replace(/'/g,"\\'")}', ${parseFloat(ev.fee)||0})">Register</button>`)
    : '';

  return `
    <div class="event-item" data-event-id="${ev.id}">
      <div class="event-date">
        <div class="event-day">${day}</div>
        <div class="event-month">${month}</div>
      </div>
      <div class="event-details">
        <div class="event-title">${ev.name}</div>
        <div class="event-meta">
          ${ev.location}${time ? ' • ' + time : ''}${spots != null ? ' • ' + spots + ' spots left' : ''}
          ${ev.organizer_name ? ' • ' + ev.organizer_name : ''}
        </div>
        <div>${badges.join('')}</div>
      </div>
      ${actionBtn}
    </div>`;
}

function registerEvent(eventId, name, dateStr, location, fee) {
  document.getElementById('eventModalId').value           = eventId;
  document.getElementById('eventModalName').textContent   = name;
  document.getElementById('eventModalLocation').textContent = location || '—';

  const { day, month } = fmtEventDate(dateStr);
  document.getElementById('eventModalDate').textContent   = `${month} ${day}`;

  const feeNum = parseFloat(fee) || 0;
  document.getElementById('eventModalFee').textContent = feeNum > 0
    ? `₱${feeNum.toLocaleString('en-PH')}`
    : 'Free';

  const paymentSection = document.getElementById('eventPaymentSection');
  if (paymentSection) paymentSection.style.display = feeNum > 0 ? 'block' : 'none';

  document.querySelectorAll('input[name="event_payment_method"]').forEach(r => r.checked = false);
  document.getElementById('eventModal').classList.add('open');
}

function closeEventModal() {
  document.getElementById('eventModal').classList.remove('open');
}

async function submitEventRegistration() {
  const eventId = document.getElementById('eventModalId').value;
  const feeText = document.getElementById('eventModalFee').textContent;
  const isFree  = feeText === 'Free';

  let method = '';
  if (!isFree) {
    const selected = document.querySelector('input[name="event_payment_method"]:checked');
    if (!selected) {
      const section = document.getElementById('eventPaymentSection');
      if (section) {
        section.style.outline = '2px solid #ff6b35';
        setTimeout(() => section.style.outline = '', 1500);
      }
      return;
    }
    method = selected.value;
  }

  closeEventModal();
  showLoading('Registering…');

  try {
    const fd = new FormData();
    fd.append('event_id', eventId);
    if (method) fd.append('payment_method', method);

    const res    = await fetch('api/user/events/register.php', { method: 'POST', body: fd });
    const result = await res.json();
    hideLoading();

    if (result.success) {
      render('#pop-up', 'done', renderPopUP);
      window.closePopUp = closePopUp;
      showPopUP('Successfully registered for the event!');
      loadMyEvents();
      allEventsLoaded = false;
      loadAllEvents();
      allEventsLoaded = true;
    } else {
      render('#pop-up', 'warning', renderPopUP);
      window.closePopUp = closePopUp;
      showPopUP(result.message || 'Registration failed.');
    }
  } catch (err) {
    hideLoading();
    showPopUP('Something went wrong. Please try again.');
  }
}

async function loadMyEvents() {
  const container = document.getElementById('myEventsScroll');
  if (!container) return;
  container.innerHTML = '<div class="events-loading">Loading your events…</div>';

  try {
    const res  = await fetch('api/user/events/my-events.php');
    const data = await res.json();

    if (!data.success) {
      if (res.status === 401) { window.location.href = 'login-page.php'; return; }
      container.innerHTML = '<div class="events-empty"><div class="events-empty-icon">⚠️</div><p>Could not load events.</p></div>';
      return;
    }

    if (!data.events?.length) {
      container.innerHTML = `
        <div class="events-empty">
          <div class="events-empty-icon"></div>
          <p>You haven't registered for any upcoming events.</p>
          <p style="margin-top:6px;"><a href="#" onclick="switchTab('all');return false;" style="color:#ff6b35;font-weight:600;">Browse all events →</a></p>
        </div>`;
      return;
    }

    container.innerHTML = data.events.map(ev => buildEventItem(ev, false)).join('');

  } catch (err) {
    console.warn('Could not load my events:', err);
    container.innerHTML = '<div class="events-empty"><div class="events-empty-icon">⚠️</div><p>Failed to load events.</p></div>';
  }
}

async function loadAllEvents() {
  const container = document.getElementById('allEventsScroll');
  if (!container) return;
  container.innerHTML = '<div class="events-loading">Loading events…</div>';

  try {
    const res  = await fetch('api/user/events/list.php');
    const data = await res.json();

    if (!data.success) {
      if (res.status === 401) { window.location.href = 'login-page.php'; return; }
      container.innerHTML = '<div class="events-empty"><div class="events-empty-icon">⚠️</div><p>Could not load events.</p></div>';
      return;
    }

    if (!data.events?.length) {
      container.innerHTML = `
        <div class="events-empty">
          <div class="events-empty-icon"></div>
          <p>No upcoming events at the moment.</p>
          <p style="margin-top:6px;color:#bbb;font-size:0.85rem;">Check back soon!</p>
        </div>`;
      return;
    }

    container.innerHTML = data.events.map(ev => buildEventItem(ev, true)).join('');

  } catch (err) {
    console.warn('Could not load all events:', err);
    container.innerHTML = '<div class="events-empty"><div class="events-empty-icon">⚠️</div><p>Failed to load events.</p></div>';
  }
}

let allEventsLoaded = false;

function switchTab(tab) {
  const myPanel  = document.getElementById('panelMyEvents');
  const allPanel = document.getElementById('panelAllEvents');
  const myTab    = document.getElementById('tabMyEvents');
  const allTab   = document.getElementById('tabAllEvents');

  if (tab === 'my') {
    myPanel.style.display  = '';
    allPanel.style.display = 'none';
    myTab.classList.add('active');
    allTab.classList.remove('active');
  } else {
    myPanel.style.display  = 'none';
    allPanel.style.display = '';
    myTab.classList.remove('active');
    allTab.classList.add('active');
    if (!allEventsLoaded) { loadAllEvents(); allEventsLoaded = true; }
  }
}

// ─── Init ─────────────────────────────────────────────────────────────────────
loadMemberData();
loadNextBooking();
loadTrainerBookings();
loadMyEvents();