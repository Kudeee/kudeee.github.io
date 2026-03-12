import { renderPopUP, showPopUP, handleOk, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

render('#pop-up', "popUPOpt", renderPopUP);

window.handleOk   = handleOk;
window.closePopUp = closePopUp;

// ─── Plan upgrade map ─────────────────────────────────────────────────────────
const PLAN_UPGRADE = {
  'BASIC PLAN': {
    label: 'Upgrade to Premium',
    nextPlan: 'PREMIUM PLAN',
    paymentUrl: 'payment.php?type=change&plan=PREMIUM%20PLAN&billing=monthly',
  },
  'PREMIUM PLAN': {
    label: 'Upgrade to VIP',
    nextPlan: 'VIP PLAN',
    paymentUrl: 'payment.php?type=upgrade',
  },
};

// ─── Load member + subscription data ─────────────────────────────────────────

async function loadMemberData() {
  try {
    const res  = await fetch('api/user/membership/info.php');
    const data = await res.json();

    if (!data.success) {
      window.location.href = 'login-page.php';
      return;
    }

    const m   = data.member;
    const sub = data.subscription;

    // Greeting
    const welcomeEl = document.querySelector('.status-info h2');
    if (welcomeEl) welcomeEl.textContent = `Welcome Back, ${m.first_name}!`;

    // Plan badge
    const premBadge = document.querySelector('.badge-premium');
    if (premBadge) premBadge.textContent = m.plan;

    // Status details
    const items = document.querySelectorAll('.status-value');
    if (sub) {
      const expiry   = new Date(sub.expiry_date);
      const today    = new Date();
      const daysLeft = Math.max(0, Math.ceil((expiry - today) / 86400000));
      const options  = { month: 'short', day: 'numeric', year: 'numeric' };

      if (items[0]) items[0].textContent = expiry.toLocaleDateString('en-PH', options);
      if (items[1]) items[1].textContent = daysLeft + ' Days';
      if (items[2]) items[2].textContent = m.plan.replace(' PLAN', '');
    }

    // Update header user info
    try {
      const userNameEl = document.querySelector('.user-profile div div:first-child');
      const userPlanEl = document.querySelector('.user-profile div div:last-child');
      if (userNameEl) userNameEl.textContent = m.first_name + ' ' + m.last_name;
      if (userPlanEl) userPlanEl.textContent = m.plan;

      const avatarEl = document.querySelector('.user-avatar');
      if (avatarEl) {
        avatarEl.textContent = (m.first_name[0] + m.last_name[0]).toUpperCase();
      }
    } catch (headerErr) {
      console.warn('Could not update header:', headerErr);
    }

    updateUpgradeButton(m.plan);

  } catch (err) {
    console.warn('Could not load member data:', err);
  }
}

function updateUpgradeButton(currentPlan) {
  const upgradeBtn = document.getElementById('upgradeBtn');
  if (!upgradeBtn) return;

  const upgrade = PLAN_UPGRADE[currentPlan];
  if (!upgrade) {
    upgradeBtn.style.display = 'none';
    return;
  }

  upgradeBtn.textContent = upgrade.label;
  upgradeBtn.onclick = () => { location.href = upgrade.paymentUrl; };
}

// ─── Load next booking (dynamic next-action section) ─────────────────────────

// Stores the current booking info for cancel use
let currentBooking = null;

async function loadNextBooking() {
  const section       = document.querySelector('.next-action-section');
  const titleEl       = document.querySelector('.next-action-title');
  const timeEl        = document.querySelector('.class-info-grid .class-info-item:nth-child(1) .class-info-value');
  const dateEl        = document.querySelector('.class-info-grid .class-info-item:nth-child(2) .class-info-value');
  const trainerEl     = document.querySelector('.class-info-grid .class-info-item:nth-child(3) .class-info-value');
  const durationEl    = document.querySelector('.class-info-grid .class-info-item:nth-child(4) .class-info-value');
  const cancelBtn     = document.getElementById('CancelBooking');

  try {
    const res  = await fetch('api/user/bookings/upcoming.php');
    const data = await res.json();

    if (!data.success) return;

    const booking = data.next_booking;

    if (!booking) {
      // No upcoming booking — show empty state
      if (section) {
        section.innerHTML = `
          <div class="next-action-content" style="text-align:center;">
            <div class="next-action-label">Your Next Class</div>
            <h2 class="next-action-title" style="font-size:1.8rem;opacity:0.7;">No Upcoming Classes</h2>
            <p style="color:#999;margin:15px 0 25px;">You don't have any classes booked yet.</p>
            <div class="action-buttons" style="justify-content:center;">
              <button class="btn btn-secondary" onclick="window.location.href='book-class-page.php'">Book a Class</button>
            </div>
          </div>`;
      }
      return;
    }

    // Store for cancel button
    currentBooking = booking;

    // Populate the section
    if (titleEl)   titleEl.textContent   = booking.class_name.toUpperCase();
    if (timeEl)    timeEl.textContent    = booking.time_label;
    if (dateEl)    dateEl.textContent    = booking.date_label;
    if (trainerEl) trainerEl.textContent = booking.trainer_name || '—';
    if (durationEl) durationEl.textContent = booking.duration_label;

    // Show cancel button (it may have been hidden)
    if (cancelBtn) cancelBtn.style.display = '';

  } catch (err) {
    console.warn('Could not load next booking:', err);
  }
}

// ─── Cancel booking ───────────────────────────────────────────────────────────

document.querySelector("#CancelBooking").addEventListener("click", () => {
  if (!currentBooking) {
    showPopUP('No booking to cancel.');
    return;
  }
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
        currentBooking = null;
        // Reload the next booking section to reflect the cancellation
        await loadNextBooking();
        // Switch pop-up to info style for success message
        render('#pop-up', 'done', renderPopUP);
        window.closePopUp = closePopUp;
        showPopUP('Booking cancelled successfully.');
        // Re-register closePopUp since render replaced the DOM
        document.querySelector('.popClose')?.addEventListener('click', closePopUp);
      } else {
        render('#pop-up', 'warning', renderPopUP);
        window.closePopUp = closePopUp;
        showPopUP(result.message || 'Could not cancel booking.');
      }
    } catch (err) {
      hideLoading();
      render('#pop-up', 'warning', renderPopUP);
      window.closePopUp = closePopUp;
      showPopUP('Something went wrong. Please try again.');
    }
  };
});

// ─── Pause membership ─────────────────────────────────────────────────────────

document.querySelector("#pauseMem").addEventListener("click", () => {
  showPopUP('Are you sure you want to pause membership?');
  window.handleOk = async function () {
    closePopUp();
    showLoading('Pausing membership...');
    try {
      const fd = new FormData();
      fd.append('action', 'pause');
      const res    = await fetch('api/user/membership/pause.php', { method: 'POST', body: fd });
      const result = await res.json();
      hideLoading();
      if (result.success) {
        showPopUP('Membership paused successfully.');
      } else {
        showPopUP(result.message || 'Could not pause membership.');
      }
    } catch (err) {
      hideLoading();
      showPopUP('Something went wrong. Please try again.');
    }
  };
});

// ─── Load upcoming events ─────────────────────────────────────────────────────

async function loadUpcomingEvents() {
  try {
    const res  = await fetch('api/user/events/list.php');
    const data = await res.json();
    if (!data.success || !data.events?.length) return;

    const existing = document.querySelectorAll('.event-item');
    existing.forEach(e => e.remove());

    const eventsSection = document.querySelector('.events-section');

    data.events.slice(0, 3).forEach(ev => {
      const d      = new Date(ev.event_date);
      const day    = d.getDate();
      const month  = d.toLocaleDateString('en-PH', { month: 'short' }).toUpperCase();
      const btnHtml= ev.already_registered
        ? `<button class="btn" disabled style="opacity:0.6;">Registered</button>`
        : `<button class="btn btn-outline" onclick="registerEvent(${ev.id})">Register</button>`;
      const feeLabel = parseFloat(ev.fee) > 0 ? `₱${ev.fee}` : 'Free';

      const html = `
        <div class="event-item" data-event-id="${ev.id}">
          <div class="event-date">
            <div class="event-day">${day}</div>
            <div class="event-month">${month}</div>
          </div>
          <div class="event-details">
            <div class="event-title">${ev.name}</div>
            <div class="event-meta">${ev.location} • ${feeLabel} • ${ev.spots_remaining} spots left</div>
          </div>
          ${btnHtml}
        </div>`;
      eventsSection.insertAdjacentHTML('beforeend', html);
    });
  } catch (err) {
    console.warn('Could not load events:', err);
  }
}

window.registerEvent = async function (eventId) {
  const method = prompt('Enter payment method (gcash / maya / gotyme / card):');
  if (!method) return;

  showLoading('Registering...');
  try {
    const fd = new FormData();
    fd.append('event_id', eventId);
    fd.append('payment_method', method);

    const res    = await fetch('api/user/events/register.php', { method: 'POST', body: fd });
    const result = await res.json();
    hideLoading();

    if (result.success) {
      showPopUP('Successfully registered for the event!');
      loadUpcomingEvents();
    } else {
      showPopUP(result.message || 'Registration failed.');
    }
  } catch (err) {
    hideLoading();
    showPopUP('Something went wrong. Please try again.');
  }
};

// ─── Init ─────────────────────────────────────────────────────────────────────

loadMemberData();
loadNextBooking();
loadUpcomingEvents();