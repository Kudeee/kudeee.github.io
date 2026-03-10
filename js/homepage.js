import { renderPopUP, showPopUP, handleOk, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

render('#pop-up', "popUPOpt", renderPopUP);

window.handleOk = handleOk;
window.closePopUp = closePopUp;

// ─── Load member + subscription data ─────────────────────────────────────────

async function loadMemberData() {
  try {
    const res  = await fetch('api/user/membership/info.php');
    const data = await res.json();

    if (!data.success) {
      // Not logged in — redirect to login
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
    const userNameEl  = document.querySelector('.user-profile div div:first-child');
    const userPlanEl  = document.querySelector('.user-profile div div:last-child');
    if (userNameEl) userNameEl.textContent = m.first_name + ' ' + m.last_name;
    if (userPlanEl) userPlanEl.textContent = m.plan;

    // Update avatar initials
    const avatarEl = document.querySelector('.user-avatar');
    if (avatarEl) {
      avatarEl.textContent = (m.first_name[0] + m.last_name[0]).toUpperCase();
    }

  } catch (err) {
    console.warn('Could not load member data:', err);
  }
}

// ─── Load upcoming events ─────────────────────────────────────────────────────

async function loadUpcomingEvents() {
  try {
    const res  = await fetch('api/user/events/list.php');
    const data = await res.json();
    if (!data.success || !data.events?.length) return;

    const container = document.querySelector('.events-section .events-tabs');
    if (!container) return;

    // Insert events after the tabs
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

window.registerEvent = async function(eventId) {
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

// ─── Pause membership ─────────────────────────────────────────────────────────

document.querySelector("#pauseMem").addEventListener("click", () => {
  showPopUP('Are you sure you want to pause membership?');
  window.handleOk = async function() {
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

// ─── Cancel booking ───────────────────────────────────────────────────────────

document.querySelector("#CancelBooking").addEventListener("click", () => {
  showPopUP('Are you sure you want to cancel?');
  window.handleOk = async function() {
    closePopUp();
    // Placeholder — booking_id would come from real data in a connected homepage
    showPopUP('Booking cancelled.');
  };
});

// ─── Init ─────────────────────────────────────────────────────────────────────

loadMemberData();
loadUpcomingEvents();