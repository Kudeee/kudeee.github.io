import { renderPopUP, showPopUP, handleOk, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

render('#pop-up', "popUPOpt", renderPopUP);

window.handleOk    = handleOk;
window.closePopUp  = closePopUp;
window.switchTab   = switchTab;
window.registerEvent         = registerEvent;
window.closeEventModal       = closeEventModal;
window.submitEventRegistration = submitEventRegistration;
window.carouselStep          = carouselStep;
window.carouselGoTo          = carouselGoTo;

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
    }

    // Header user info
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

// ─── Carousel state ───────────────────────────────────────────────────────────
let carouselBookings = [];   // all upcoming bookings
let carouselIndex    = 0;    // active slide index

// ─── Build one slide's inner HTML ─────────────────────────────────────────────
function buildSlideHTML(booking) {
  return `
    <div class="next-action-content">
      <div class="next-action-label">
        ${booking.booking_type === 'trainer' ? 'Personal Training Session' : 'Your Next Class'}
      </div>
      <h2 class="next-action-title">${booking.class_name.toUpperCase()}</h2>
      <div class="class-info-grid">
        <div class="class-info-item">
          <div class="class-info-value">${booking.time_label}</div>
          <div class="class-info-label">Time</div>
        </div>
        <div class="class-info-item">
          <div class="class-info-value">${booking.date_label}</div>
          <div class="class-info-label">Date</div>
        </div>
        <div class="class-info-item">
          <div class="class-info-value">${booking.trainer_name || '—'}</div>
          <div class="class-info-label">Trainer</div>
        </div>
        <div class="class-info-item">
          <div class="class-info-value">${booking.duration_label}</div>
          <div class="class-info-label">Duration</div>
        </div>
      </div>
      <div class="action-buttons">
        <button class="btn btn-outline" data-booking-id="${booking.booking_id}" data-booking-type="${booking.booking_type}" onclick="cancelBookingById(${booking.booking_id},'${booking.booking_type}','${booking.class_name.replace(/'/g,"\\'")}')">Cancel Booking</button>
        <button class="btn btn-secondary" onclick="window.location.href='book-class-page.php'">Book Another</button>
      </div>
    </div>`;
}

// ─── Render empty-state slide ─────────────────────────────────────────────────
function renderEmptySlide() {
  const slider = document.getElementById('carouselSlider');
  if (!slider) return;
  slider.innerHTML = `
    <div class="next-action-slide next-action-content" style="text-align:center;padding:20px 0;">
      <div class="next-action-label">Your Next Class</div>
      <h2 class="next-action-title" style="font-size:2.2rem;opacity:0.55;letter-spacing:3px;">NO CLASS YET</h2>
      <p style="color:#888;margin:12px 0 30px;font-size:1rem;">You have no upcoming classes scheduled.</p>
      <div class="action-buttons" style="justify-content:center;">
        <button class="btn btn-outline" onclick="window.location.href='book-class-page.php'">Book a Class</button>
      </div>
    </div>`;
  // hide nav
  ['carouselPrev','carouselNext','carouselCount'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
  });
  const dots = document.getElementById('carouselDots');
  if (dots) dots.innerHTML = '';
}

// ─── Render full carousel ─────────────────────────────────────────────────────
function renderCarousel(bookings) {
  carouselBookings = bookings;
  carouselIndex    = 0;

  const slider = document.getElementById('carouselSlider');
  if (!slider) return;

  if (!bookings.length) { renderEmptySlide(); return; }

  // Build slides
  slider.innerHTML = bookings.map((b, i) => `
    <div class="next-action-slide" data-index="${i}">
      ${buildSlideHTML(b)}
    </div>`).join('');

  // Dots
  const dotsEl = document.getElementById('carouselDots');
  if (dotsEl) {
    if (bookings.length > 1) {
      dotsEl.innerHTML = bookings.map((_, i) =>
        `<button class="carousel-dot${i === 0 ? ' active' : ''}" onclick="carouselGoTo(${i})" aria-label="Booking ${i+1}"></button>`
      ).join('');
    } else {
      dotsEl.innerHTML = '';
    }
  }

  // Count badge
  const countEl = document.getElementById('carouselCount');
  if (countEl) {
    if (bookings.length > 1) {
      countEl.textContent  = `1 / ${bookings.length}`;
      countEl.style.display = '';
    } else {
      countEl.style.display = 'none';
    }
  }

  // Nav arrows
  const prevBtn = document.getElementById('carouselPrev');
  const nextBtn = document.getElementById('carouselNext');
  if (bookings.length > 1) {
    if (prevBtn) prevBtn.style.display = '';
    if (nextBtn) nextBtn.style.display = '';
  } else {
    if (prevBtn) prevBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'none';
  }

  updateCarouselUI();
}

// ─── Update slider transform + dot/button states ──────────────────────────────
function updateCarouselUI() {
  const slider  = document.getElementById('carouselSlider');
  if (slider) slider.style.transform = `translateX(-${carouselIndex * 100}%)`;

  // Dots
  document.querySelectorAll('.carousel-dot').forEach((d, i) =>
    d.classList.toggle('active', i === carouselIndex)
  );

  // Count badge
  const countEl = document.getElementById('carouselCount');
  if (countEl && carouselBookings.length > 1) {
    countEl.textContent = `${carouselIndex + 1} / ${carouselBookings.length}`;
  }

  // Disable arrows at boundaries
  const prevBtn = document.getElementById('carouselPrev');
  const nextBtn = document.getElementById('carouselNext');
  if (prevBtn) prevBtn.disabled = carouselIndex === 0;
  if (nextBtn) nextBtn.disabled = carouselIndex === carouselBookings.length - 1;
}

// ─── Carousel navigation ──────────────────────────────────────────────────────
function carouselStep(dir) {
  const newIdx = carouselIndex + dir;
  if (newIdx < 0 || newIdx >= carouselBookings.length) return;
  carouselIndex = newIdx;
  updateCarouselUI();
}

function carouselGoTo(idx) {
  if (idx < 0 || idx >= carouselBookings.length) return;
  carouselIndex = idx;
  updateCarouselUI();
}

// ─── Fetch ALL upcoming bookings (class + trainer) ────────────────────────────
async function loadAllUpcomingBookings() {
  try {
    // We call the same endpoint but need ALL bookings — use a new dedicated query
    // The existing endpoint only returns 1 (the soonest). We'll fetch both class
    // and trainer booking pages via the schedule list APIs.
    const [classRes, trainerRes] = await Promise.all([
      fetch('api/user/schedule/all-upcoming.php'),
      fetch('api/user/schedule/upcoming.php'),  // fallback if the above doesn't exist
    ]);

    // Try the all-upcoming endpoint first; if it 404s, fall back to single-booking
    let allBookings = [];

    if (classRes.ok) {
      const classData = await classRes.json();
      if (classData.success && Array.isArray(classData.bookings)) {
        allBookings = classData.bookings;
      }
    }

    // If we got nothing from the new endpoint, fall back to the single-booking endpoint
    if (!allBookings.length) {
      const singleData = await trainerRes.json();
      if (singleData.success && singleData.next_booking) {
        allBookings = [singleData.next_booking];
      }
    }

    renderCarousel(allBookings);

  } catch (err) {
    console.warn('Could not load upcoming bookings:', err);
    renderEmptySlide();
  }
}

// ─── Cancel a specific booking by ID (carousel-aware) ────────────────────────
window.cancelBookingById = function(bookingId, bookingType, className) {
  showPopUP(`Cancel your ${className} booking?`);

  window.handleOk = async function () {
    closePopUp();
    showLoading('Cancelling booking...');
    try {
      const fd = new FormData();
      fd.append('type',       bookingType);
      fd.append('booking_id', bookingId);
      const res    = await fetch('api/bookings/cancel.php', { method: 'POST', body: fd });
      const result = await res.json();
      hideLoading();
      if (result.success) {
        // Remove cancelled booking from the list and re-render
        carouselBookings = carouselBookings.filter(b => b.booking_id !== bookingId);
        if (carouselIndex >= carouselBookings.length) {
          carouselIndex = Math.max(0, carouselBookings.length - 1);
        }
        renderCarousel(carouselBookings);
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
};

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

// ─── Event registration modal ─────────────────────────────────────────────────

function registerEvent(eventId, name, dateStr, location, fee) {
  document.getElementById('eventModalId').value      = eventId;
  document.getElementById('eventModalName').textContent = name;
  document.getElementById('eventModalLocation').textContent = location || '—';

  const { day, month } = fmtEventDate(dateStr);
  document.getElementById('eventModalDate').textContent = `${month} ${day}`;

  const feeNum = parseFloat(fee) || 0;
  document.getElementById('eventModalFee').textContent = feeNum > 0
    ? `₱${feeNum.toLocaleString('en-PH')}`
    : 'Free';

  const paymentSection = document.getElementById('eventPaymentSection');
  if (paymentSection) {
    paymentSection.style.display = feeNum > 0 ? 'block' : 'none';
  }

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

// ─── Load My Events ───────────────────────────────────────────────────────────
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
          <div class="events-empty-icon">📅</div>
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

// ─── Load All Events ──────────────────────────────────────────────────────────
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
          <div class="events-empty-icon">🎉</div>
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

// ─── Tab switcher ─────────────────────────────────────────────────────────────
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
loadAllUpcomingBookings();
loadMyEvents();