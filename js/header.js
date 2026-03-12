const headerHTML = `
        <div class="header-content">
        <div1 class="logo">
          <a href="index.php">
            <img src="assests/logo/society-fit.png" alt="society-fit logo" />
          </a>
        </div1>
        <ul class="header-nav">
          <li><a href="homepage.php">Home</a></li>
          <li><a href="schedule-page.php">Schedule</a></li>
          <li><a href="trainers-page.php">Trainers</a></li>
        </ul>
        <div class="user-profile">
          <div>
            <div style="font-weight: 600" id="header-member-name">Loading…</div>
            <div style="font-size: 0.85rem; color: #ff6b35" id="header-member-plan"></div>
          </div>
          <h3 class="accordion">
            <button type="button" aria-expanded="false" class="accordion-trigger" aria-controls="sect1" id="accordion1id" aria-label="User profile menu">
              <div class="user-avatar" id="header-member-avatar">?</div>
            </button>
          </h3>
          <div id="sect1" role="region" aria-labelledby="accordion1id" class="accordion-panel" hidden>
            <div class="sign-out"><a href="login-page.php">Sign Out</a></div>
          </div>
        </div>
      </div>
`;

document.querySelector('.header-js').innerHTML = headerHTML;

// Highlight the active nav link
const currentPage = window.location.pathname.split('/').pop();
document.querySelectorAll('.header-nav a').forEach(link => {
    if (link.getAttribute('href') === currentPage) {
        link.classList.add('active');
    }
});

// Fetch real member data from session
(async function loadHeaderMember() {
  try {
    const res  = await fetch('api/auth/check-session.php');
    const data = await res.json();

    if (!data.success || !data.member) return; // not logged in — leave as-is

    const m = data.member;
    const fullName = m.first_name + ' ' + m.last_name;
    const initials = (m.first_name[0] + m.last_name[0]).toUpperCase();

    const nameEl   = document.getElementById('header-member-name');
    const planEl   = document.getElementById('header-member-plan');
    const avatarEl = document.getElementById('header-member-avatar');

    if (nameEl)   nameEl.textContent   = fullName;
    if (planEl)   planEl.textContent   = m.plan || '';
    if (avatarEl) avatarEl.textContent = initials;

  } catch (err) {
    // silently fail — header still renders with placeholder
  }
})();

'use strict';

class Accordion {
  constructor(domNode) {
    this.rootEl = domNode;
    this.buttonEl = this.rootEl.querySelector('button[aria-expanded]');

    const controlsId = this.buttonEl.getAttribute('aria-controls');
    this.contentEl = document.getElementById(controlsId);

    this.open = this.buttonEl.getAttribute('aria-expanded') === 'true';

    this.buttonEl.addEventListener('click', this.onButtonClick.bind(this));
  }

  onButtonClick() {
    this.toggle(!this.open);
  }

  toggle(open) {
    if (open === this.open) return;
    this.open = open;
    this.buttonEl.setAttribute('aria-expanded', `${open}`);
    if (open) {
      this.contentEl.removeAttribute('hidden');
    } else {
      this.contentEl.setAttribute('hidden', '');
    }
  }
}

const accordions = document.querySelectorAll('.accordion');
accordions.forEach((accordionEl) => {
  new Accordion(accordionEl);
});