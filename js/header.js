const header = `
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
            <div style="font-weight: 600">Ben Dover</div>
            <div style="font-size: 0.85rem; color: #ff6b35">Premium Member</div>
          </div>
          <h3 class="accordion">
            <button type="button" aria-expanded="false" class="accordion-trigger" aria-controls="sect1" id="accordion1id" aria-label="User profile menu">
              <div class="user-avatar">BD</div>
            </button>
          </h3>
          <div id="sect1" role="region" aria-labelledby="accordion1id" class="accordion-panel" hidden>
            <div class="sign-out"><a href="login-page.php">Sign Out</a></div>
          </div>
        </div>
      </div>
`;

document.querySelector('.header-js').innerHTML = header;

const currentPage = window.location.pathname.split('/').pop();

document.querySelectorAll('.header-nav a').forEach(link => {
    if (link.getAttribute('href') === currentPage) {
        link.classList.add('active');
    }
});

'use strict';

class Accordion {
  constructor(domNode) {
    this.rootEl = domNode;
    this.buttonEl = this.rootEl.querySelector('button[aria-expanded]');

    const controlsId = this.buttonEl.getAttribute('aria-controls');
    this.contentEl = document.getElementById(controlsId);

    this.open = this.buttonEl.getAttribute('aria-expanded') === 'true';

    // add event listeners
    this.buttonEl.addEventListener('click', this.onButtonClick.bind(this));
  }

  onButtonClick() {
    this.toggle(!this.open);
  }

  toggle(open) {
    // don't do anything if the open state doesn't change
    if (open === this.open) {
      return;
    }

    // update the internal state
    this.open = open;

    // handle DOM updates
    this.buttonEl.setAttribute('aria-expanded', `${open}`);
    if (open) {
      this.contentEl.removeAttribute('hidden');
    } else {
      this.contentEl.setAttribute('hidden', '');
    }
  }

  // Add public open and close methods for convenience
  open() {
    this.toggle(true);
  }

  close() {
    this.toggle(false);
  }
}

// init accordions
const accordions = document.querySelectorAll('.accordion');
accordions.forEach((accordionEl) => {
  new Accordion(accordionEl);
});