# Society Fitness Gym — Web Application

A full-featured gym management web app for **Society Fitness**, covering everything from public-facing membership sign-up and class booking to a complete admin panel for staff and management.

---

## Quick Start

```bash
# Serve locally (requires a PHP-capable server)
php -S localhost:8000

# Or with XAMPP / WAMP — drop the project into htdocs/
# Then visit: http://localhost/society-fitness/
```

> **No build step required.** CSS, vanilla JS (ES Modules), and static HTML are served directly. PHP handles all backend API routes under `/api/`.

---

## Live Pages

| URL | Description |
|-----|-------------|
| `index.html` | Public landing page |
| `login-page.html` | Member login |
| `sign-up-page.html` | New member registration |
| `homepage.html` | Member dashboard |
| `schedule-page.html` | Weekly class schedule |
| `book-class-page.html` | Multi-step class booking |
| `book-trainer-page.html` | Multi-step trainer booking |
| `trainers-page.html` | Trainer directory |
| `my-membership.html` | Membership management |
| `payment.html` | Renew / upgrade plan |
| `payments-page.html` | Member payment history |
| `admin-panel.html` | Admin SPA (requires admin session) |

---

## Project Structure

```
society-fitness/
├── index.html                  # Landing page
├── homepage.html               # Member home
├── admin-panel.html            # Admin shell (SPA)
├── ...                         # Other HTML pages
│
├── Admin-pages/                # Admin page fragments (loaded via fetch)
│   ├── dashboard.html
│   ├── members.html
│   ├── classes.html
│   ├── trainers.html
│   ├── subscriptions.html
│   ├── payments.html
│   ├── events.html
│   ├── revenue.html
│   └── roles.html
│
├── css/                        # Stylesheets
│   ├── GENERAL-LAYOUT.css      # Global reset / base
│   ├── general.css             # Shared header, nav, container
│   ├── admin-css.css           # Admin panel styles
│   └── ...                     # Per-page stylesheets
│
├── js/                         # JavaScript modules
│   ├── admin-js.js             # Admin SPA controller
│   ├── header.js               # Shared header component
│   ├── renderer.js             # Generic render helper
│   ├── book-class-page.js      # Class booking logic
│   ├── book-trainer-page.js    # Trainer booking logic
│   ├── payment-methods.js      # Payment method injector
│   ├── payment-content.js      # Payment page content generator
│   ├── sign-up-page.js         # Multi-step sign-up
│   ├── login.js                # Login handler
│   ├── homepage.js             # Homepage interactions
│   ├── landing-page.js         # Landing page logic
│   ├── trainers-page.js        # Trainer grid renderer
│   ├── schedule-page.js        # Schedule view toggler
│   ├── carousel.js             # Login/sign-up carousel
│   └── trainer-carousel.js     # Landing page trainer carousel
│
├── components/                 # Reusable UI components
│   ├── loading.js              # Loading overlay
│   ├── pop-up.js               # Alert / confirm modals
│   ├── meetOurTrainer.js       # Landing trainer cards
│   ├── selectTrainer.js        # Book-trainer selector
│   ├── subcriptionCards.js     # Subscription card renderer
│   └── css/
│       ├── loading-component.css
│       ├── pop-up.css
│       └── subscription-cards.css
│
├── data/                       # Static data (JS modules)
│   ├── Trainers.js             # Trainer roster with rates/stats
│   └── subscription-data.js    # Plan names, prices, features
│
├── api/                        # PHP backend (to be implemented)
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── admin/
│   │   ├── auth/check-session.php
│   │   ├── members/
│   │   ├── classes/
│   │   ├── trainers/
│   │   ├── subscriptions/
│   │   ├── payments/
│   │   ├── events/
│   │   └── roles/
│   ├── bookings/
│   │   ├── book-class.php
│   │   └── book-trainer.php
│   ├── payments/process.php
│   └── contact/inquiry.php
│
└── assests/                    # Static assets
    ├── images/
    ├── icons/
    ├── logo/
    └── trainers/
```

---

## Tech Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES Modules)
- **Backend:** PHP (REST-style JSON API under `/api/`)
- **Styling:** Custom CSS with CSS Grid and Flexbox (no framework)
- **No build tools** — no npm, no bundler, no transpilation required

---

## Environment Requirements

- PHP 7.4+ (for API routes)
- Any web server: Apache, Nginx, or `php -S`
- Modern browser with ES Module support (Chrome, Firefox, Safari, Edge)

---

## Contributing

See [`GUIDELINES.md`](./GUIDELINES.md) for full development conventions, workflows, and architecture decisions.