# Society Fitness — Developer Guidelines

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Architecture](#3-architecture)
4. [Directory Structure & Conventions](#4-directory-structure--conventions)
5. [Important Commands](#5-important-commands)
6. [Frontend Development Workflow](#6-frontend-development-workflow)
7. [Backend (PHP API) Workflow](#7-backend-php-api-workflow)
8. [Data Layer](#8-data-layer)
9. [Component System](#9-component-system)
10. [Admin Panel](#10-admin-panel)
11. [Styling Conventions](#11-styling-conventions)
12. [JavaScript Conventions](#12-javascript-conventions)
13. [Security Checklist](#13-security-checklist)
14. [Common Gotchas](#14-common-gotchas)
15. [API Reference](#15-api-reference)

---

## 1. Project Overview

**Society Fitness** is a gym management web application for a Filipino fitness gym. It serves two audiences:

- **Members** — can sign up, log in, book classes, book personal trainers, manage their membership plan, and view their payment history.
- **Admins / Staff** — have access to a full admin panel that manages members, subscriptions, classes, trainers, events, revenue, payments, and role-based access control.

The application is a traditional multi-page web app (MPA) with a single-page app (SPA) pattern only inside the admin panel. There is no frontend framework — everything is vanilla HTML, CSS, and JavaScript using ES Modules.

The currency used throughout is **Philippine Peso (₱)**. Phone numbers follow the PH format (`09XXXXXXXXX`).

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Markup | HTML5 |
| Styling | CSS3 — custom, no framework |
| Scripting | Vanilla JavaScript (ES Modules) |
| Backend | PHP 7.4+ (REST-style JSON API) |
| Database | MySQL (via PDO — not yet connected) |
| Server | Apache / Nginx / `php -S` (local dev) |
| No build tools | No npm, webpack, Vite, or transpiler |

**Key design decision:** The project intentionally avoids build tools and JS frameworks to keep the stack simple, portable, and easy to onboard onto a shared hosting environment (which is common in the PH market).

> **Current status:** All PHP endpoints are fully written with validation, business logic, and DB query stubs — but the database is not yet connected. Every endpoint returns a `503` stub error until the PDO connection is activated. The commented-out SQL blocks are ready for integration.

---

## 3. Architecture

### Member-Facing (MPA)

Each page is a standalone `.html` file. Pages share a common header injected by `js/header.js`. JavaScript files are loaded as ES Modules (`type="module"`) where imports are needed, or as regular scripts for global utilities like `loading.js`.

```
Browser → HTML page → loads CSS + JS modules → JS calls /api/*.php → PHP returns JSON
```

### Admin Panel (SPA inside a shell)

`admin-panel.html` is the shell. `js/admin-js.js` intercepts sidebar nav clicks and `fetch()`-loads the HTML fragment from `Admin-pages/` into the `#content` div. No page reload happens.

```
admin-panel.html → admin-js.js → fetch(Admin-pages/xxx.html) → inject into #content
                                → bindModalTriggers() + bindFormHandlers() after each inject
```

### PHP API

All backend routes live under `/api/`. Every endpoint:
- Accepts `POST` (mutations) or `GET` (reads/filters)
- Returns `{ "success": true/false, "message": "...", ...data }` as JSON
- Validates the CSRF token on every POST
- Returns HTTP 401/403 if the session is not authenticated or not authorized
- Currently returns `503` (stub) until the DB is connected — remove the `error(...)` stub line and uncomment the PDO block to activate

---

## 4. Directory Structure & Conventions

```
/
├── index.html                  ← Public landing page (entry point for visitors)
├── login-page.html             ← Member login
├── sign-up-page.html           ← Multi-step registration (5 pages)
├── homepage.html               ← Authenticated member dashboard
├── schedule-page.html          ← Weekly class schedule grid + list view
├── book-class-page.html        ← 4-step class booking wizard
├── book-trainer-page.html      ← 4-step personal trainer booking wizard
├── trainers-page.html          ← Full trainer directory
├── my-membership.html          ← Membership plan management (subscription cards)
├── payment.html                ← Renew / upgrade / change plan payment
├── payments-page.html          ← Member payment history
├── admin-panel.html            ← Admin SPA shell
│
├── Admin-pages/                ← HTML fragments (NOT standalone pages)
│   ├── dashboard.html
│   ├── members.html
│   ├── classes.html
│   ├── trainers.html
│   ├── subscriptions.html
│   ├── payments.html
│   ├── events.html
│   ├── roles.html
│   └── revenue.html            ← Currently hard-coded display data
│
├── api/                        ← PHP backend
│   ├── config.php              ← Shared session, CSRF, helpers, sanitizers
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── register.php
│   │   └── check-session.php
│   ├── admin/
│   │   ├── config.php          ← Admin-specific guards + pagination/date helpers
│   │   ├── bookings/           ← list.php, update.php
│   │   ├── classes/            ← create.php, list.php, update.php
│   │   ├── events/             ← create.php, list.php, update.php
│   │   ├── members/            ← create.php, list.php, update.php, view.php, delete.php
│   │   ├── payments/           ← list.php, refund.php
│   │   ├── reports/            ← dashboard.php, memberships.php, revenue.php
│   │   ├── settings/           ← admins.php, audit-log.php
│   │   └── trainers/           ← create.php, list.php, update.php, delete.php
│   ├── bookings/               ← book-class.php, book-trainer.php, cancel.php
│   ├── payments/               ← process.php
│   ├── user/
│   │   ├── events/             ← list.php, register.php
│   │   ├── membership/         ← info.php, pause.php
│   │   ├── payments/           ← history.php
│   │   ├── schedule/           ← list.php
│   │   └── trainers/           ← availability.php, list.php
│   └── contact/                ← inquiry.php
│
├── css/                        ← All stylesheets
│   ├── GENERAL-LAYOUT.css      ← Global * reset only — import this first
│   ├── general.css             ← Shared header/nav/container — import for user pages
│   ├── admin-css.css           ← Admin panel only
│   ├── payment-method.css      ← Payment option cards (shared)
│   └── [page-name].css         ← One stylesheet per HTML page
│
├── js/                         ← Page-level scripts and modules
│   ├── admin-js.js             ← Admin SPA controller (page loader, forms, actions)
│   ├── book-class-page.js      ← 4-step class booking wizard logic
│   ├── book-trainer-page.js    ← 4-step trainer booking wizard logic
│   ├── carousel.js             ← Login/sign-up image carousel (non-module)
│   ├── header.js               ← Shared nav header injection (non-module)
│   ├── homepage.js             ← Member dashboard interactions
│   ├── landing-page.js         ← Landing page interactions + contact form
│   ├── login.js                ← Login form handler
│   ├── payment-content.js      ← Dynamic payment page content (renew/upgrade/change)
│   ├── payment-methods.js      ← Payment method selector injection (non-module)
│   ├── renderer.js             ← Tiny render utility (module)
│   ├── schedule-page.js        ← Schedule grid/list toggle
│   ├── sign-up-page.js         ← Multi-step sign-up form + validation
│   ├── trainer-carousel.js     ← Landing page trainer carousel (non-module)
│   └── trainers-page.js        ← Trainer directory rendering
│
├── components/                 ← Reusable UI components
│   ├── css/
│   │   ├── loading-component.css
│   │   ├── pop-up.css
│   │   └── subscription-cards.css
│   ├── loading.js              ← Global loading overlay (non-module)
│   ├── meetOurTrainer.js       ← Landing page trainer cards
│   ├── pop-up.js               ← Modal pop-up (warning / done / confirm)
│   ├── selectTrainer.js        ← Trainer picker for booking wizard
│   └── subcriptionCards.js     ← Subscription plan cards (selection + management)
│
├── data/                       ← JS data modules
│   ├── Trainers.js             ← Static trainer data array
│   └── subscription-data.js    ← Subscription plan definitions
│
└── assests/                    ← [sic] Static files (do NOT rename)
    ├── images/
    ├── icons/                  ← SVG icons (Bootstrap Icons set)
    ├── logo/
    └── trainers/               ← Trainer portrait images
```

> **Note:** The folder is spelled `assests/` (with the typo) throughout the codebase. Do **not** rename it — all image paths across every HTML and JS file reference this spelling.

---

## 5. Important Commands

### Start a local dev server

```bash
# Option 1 — PHP built-in server (supports .php API routes)
php -S localhost:8000

# Option 2 — Python (frontend only, PHP routes won't work)
python3 -m http.server 8000

# Option 3 — VS Code Live Server extension
# Set defaultPreviewPath in .vscode/settings.json (already configured to admin-panel.html)
```

### Open the admin panel directly

```
http://localhost:8000/admin-panel.html
```

### Open the landing page

```
http://localhost:8000/index.html
# or just
http://localhost:8000/
```

### Activate a PHP endpoint (connect to DB)

Each endpoint has a commented-out PDO block and a stub line at the bottom:

```php
// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
```

To activate, update `api/config.php` credentials, then in each endpoint file:
1. Uncomment the `/* ... */` PDO block
2. Delete the `error(...)` stub line at the bottom

### Lint / format (no toolchain yet — manual conventions apply)

There is currently no automated linter or formatter configured. Follow the conventions in this document manually or set up Prettier with the config below:

```json
// .prettierrc (suggested)
{
  "singleQuote": true,
  "semi": true,
  "tabWidth": 2,
  "trailingComma": "es5"
}
```

---

## 6. Frontend Development Workflow

### Adding a new member-facing page

1. Create `your-page.html` in the project root.
2. Create `css/your-page.css`. Start with:
   ```css
   @import url("general.css"); /* gives you header, nav, container */
   ```
3. Create `js/your-page.js` as an ES Module if needed.
4. Import it in your HTML:
   ```html
   <script type="module" src="js/your-page.js"></script>
   ```
5. Add the shared header:
   ```html
   <header class="header header-js"></header>
   ...
   <script src="js/header.js"></script>
   ```
6. Add loading overlay and pop-up containers if the page has async actions:
   ```html
   <div id="loading"></div>
   <div id="pop-up"></div>
   <script src="components/loading.js"></script>
   ```

### Adding a new admin sub-page

1. Create `Admin-pages/your-page.html` as an **HTML fragment** (no `<!DOCTYPE>`, `<html>`, `<head>`, or `<body>` — just the inner content).
2. Register it in `js/admin-js.js` inside the `pageMap` object:
   ```js
   const pageMap = {
     ...
     yourpage: 'Admin-pages/your-page.html',
   };
   ```
3. Add a nav link in `admin-panel.html`:
   ```html
   <a data-page="yourpage">Your Page</a>
   ```
4. If the page has modals or forms, hook them up inside `bindModalTriggers()` and `bindFormHandlers()` in `admin-js.js`.

### Multi-step forms (booking wizard pattern)

Both `book-class-page.html` and `book-trainer-page.html` use the same 4-step pattern:

- All selection state is stored in a local `bookingData` object in the JS file.
- `nextStep(n)` / `prevStep(n)` show/hide `.step-content` divs and update the `.step-indicator` styles.
- On the final step, hidden `<input>` fields are populated just before form submission via `prepareBookingSubmit()` / `prepareTrainerSubmit()`.
- The booking summary sidebar (`#summaryClass`, `#summaryDate`, etc.) is updated live as the user makes selections.
- `selectDate(displayLabel, isoValue)` accepts both a human-readable label (shown in UI) and an ISO date string (sent to PHP).

### Sign-up multi-step form

`sign-up-page.html` has 5 pages (divs), navigated entirely in JS (`js/sign-up-page.js`):

| Page ID | Content |
|---------|---------|
| `#first-page` | Personal info (name, email, phone, zip) |
| `#second-page` | Password + confirm |
| `#second-last-page` | Membership plan selection (subscription cards) |
| `#last-page` | Payment method |
| `#sub-page` | Order summary, discount code, terms, submit |

Hidden inputs `#hidden_selected_plan`, `#hidden_billing_cycle`, and `#hidden_plan_price` are synced by JS when a plan is chosen so that the final form POST includes them.

### Payment page (`payment.html`)

`js/payment-content.js` reads URL params to determine what to render:

| URL param | Value | Behaviour |
|-----------|-------|-----------|
| `type` | `renew` | Shows Premium Plan renewal |
| `type` | `upgrade` | Shows VIP Plan upgrade |
| `type=change&plan=...&price=...&billing=...` | — | Shows plan change (upgrade or downgrade) |
| `type=billing-change&plan=...&price=...&billing=...` | — | Shows billing cycle switch |

---

## 7. Backend (PHP API) Workflow

### Shared bootstrap files

| File | Purpose |
|------|---------|
| `api/config.php` | Session start, CSRF generation, `success()`, `error()`, `require_member()`, `is_logged_in()`, `sanitize_*()`, `require_method()` |
| `api/admin/config.php` | Extends root config; adds `require_admin()`, `is_super_admin()`, `get_pagination()`, `get_date_range()` |

### Every endpoint must

1. **Include the right config** — admin endpoints use `api/admin/config.php`, member endpoints use `api/config.php`.

2. **Enforce HTTP method:**
   ```php
   require_method('POST'); // or 'GET'
   ```

3. **Validate CSRF on POST:**
   ```php
   require_csrf();
   ```

4. **Authenticate the caller:**
   ```php
   // Member endpoint:
   $member = require_member();

   // Admin endpoint (any admin role):
   $admin = require_admin();

   // Admin endpoint (restricted role):
   $admin = require_admin(['super_admin']);
   ```

5. **Return JSON:**
   ```php
   header('Content-Type: application/json');
   success('Message', ['key' => $value]);
   // or
   error('Human-readable error message.', 400);
   ```

### Standard JSON response shape

```json
{ "success": true, "message": "OK" }
{ "success": false, "message": "Human-readable error" }
{ "success": true, "message": "...", "data_key": { ... } }
```

### Auth flow

- `POST /api/auth/login.php` — validates credentials, creates session, returns `{ success, role }`
- JS redirects: `role === 'admin'` → `admin-panel.html`, otherwise → `homepage.html`
- `POST /api/auth/register.php` — creates member, logs them in, returns `{ success }`
- `POST /api/auth/logout.php` — destroys session, returns `{ success }`
- `GET /api/auth/check-session.php` — returns member session data or 401
- `GET /api/admin/auth/check-session.php` — returns 200 if valid admin session, 401 otherwise

### Plan prices (canonical reference)

These prices must stay in sync between PHP endpoints and `data/subscription-data.js`:

| Plan | Monthly | Yearly (16% off) |
|------|---------|-----------------|
| BASIC PLAN | ₱499 | ₱5,028 |
| PREMIUM PLAN | ₱899 | ₱9,067 |
| VIP PLAN | ₱1,500 | ₱15,120 |

Yearly formula: `monthlyPrice * 12 * 0.84`

### Admin roles

Defined in `api/admin/config.php`:

| Role | Permissions |
|------|------------|
| `staff` | Read + limited write (cannot issue refunds or delete) |
| `admin` | Full write access (cannot permanently delete members/trainers, cannot manage admin accounts) |
| `super_admin` | Unrestricted — permanent deletes, refunds, admin account management |

---

## 8. Data Layer

### `data/Trainers.js`

Exports a `trainers` array. Each trainer object has:

```js
{
  name, image, specialty, trainerBio, briefIntro,
  exp,          // years of experience
  clients,      // total clients
  BaseRate,     // ₱ per 30-minute session (base)
  rating,       // out of 5
  availability, // display string
  availCss,     // CSS class — "limited" or undefined (available)
  specialtyTag  // array of 4 tag strings
}
```

**Image paths** are relative to the consuming page (e.g., `../assests/trainers/` from `components/`).

### `data/subscription-data.js`

Exports a `subscriptions` array. Each plan:

```js
{
  plan,          // "BASIC PLAN" | "PREMIUM PLAN" | "VIP PLAN"
  monthlyPrice,  // number (₱)
  yearlyPrice,   // number (₱) — pre-calculated at 16% discount
  benefits       // string[]
}
```

The yearly price formula: `monthlyPrice * 12 * 0.84` (16% annual discount).

---

## 9. Component System

### Loading overlay — `components/loading.js`

A non-module script. Exposes global functions:

```js
showLoading("Processing...")  // shows overlay with message
hideLoading()                 // hides overlay
simulateLoading(ms)           // returns a Promise that resolves after ms
```

Include it **before** any module script that calls these functions:
```html
<script src="components/loading.js"></script>
<script type="module" src="js/your-page.js"></script>
```

### Pop-up modal — `components/pop-up.js`

Exports three variants via `renderPopUP(type)`:
- `'warning'` — single OK button with alert icon
- `'done'` — single OK button with check icon
- `'popUPOpt'` — No / Ok buttons (for confirm dialogs)

Usage pattern:
```js
import { renderPopUP, showPopUP, closePopUp } from "../components/pop-up.js";
import { render } from './renderer.js';

render('#pop-up', 'warning', renderPopUP);  // inject HTML once
window.closePopUp = closePopUp;             // expose for onclick= attributes

showPopUP("Your message here");             // show with a message
```

For confirm dialogs (`'popUPOpt'`), also expose `handleOk`:
```js
import { renderPopUP, showPopUP, handleOk, closePopUp } from "../components/pop-up.js";
window.handleOk = handleOk;
window.closePopUp = closePopUp;
```

### Subscription cards — `components/subcriptionCards.js`

Renders subscription plan cards in three modes, controlled by the selector you target:

| Selector | Function | Used on |
|----------|----------|---------|
| `.selection-cards` | `initSubscriptionSelection()` | Sign-up page (radio buttons) |
| `.card-container` | `initSubscriptionCards()` | My Membership page (manage) |

The toggle between monthly/yearly pricing is handled globally via `window.togglePricing(bool)`.

Radio buttons on the sign-up page use `name="membership_plan"` and carry `data-price` and `data-billing` attributes that JS reads to sync hidden form fields before submission.

### Payment methods — `js/payment-methods.js`

Non-module script. Auto-injects the payment option HTML into every `.payment-method-js` element on the page. Exposes `window.handlePayment(event)` for form `onsubmit`. Includes GCash, GoTyme, Maya, and Credit/Debit Card options. Card details (number, expiry, CVV) are only shown when the card radio is selected.

The heading `#head-title` ("Payment Method") is automatically hidden on `book-class-page.html` and `book-trainer-page.html` since those pages have their own section titles.

### Header — `js/header.js`

Non-module script. Injects the shared top nav into `.header-js`. Highlights the active nav link by comparing `window.location.pathname` to each link's `href`. Includes an accessible accordion-based user profile dropdown.

### Trainer components

| Component | Used on |
|-----------|---------|
| `components/meetOurTrainer.js` | Landing page trainer carousel cards |
| `components/selectTrainer.js` | Book Trainer wizard Step 1 (trainer picker) |
| `js/trainers-page.js` | Full trainer directory page |

All three consume `data/Trainers.js`. Image paths use `../assests/trainers/` prefix.

### Renderer — `js/renderer.js`

Tiny utility:
```js
render(containerSelector, prop, renderFn)
// Equivalent to: document.querySelector(selector).innerHTML = renderFn(prop)
```

---

## 10. Admin Panel

The admin panel is a **client-side SPA** inside `admin-panel.html`. There is no server-side routing — all navigation is done via `fetch()` in `admin-js.js`.

### How page loading works

```
Sidebar link click (data-page="members")
  → loadPage('members')
  → fetch('Admin-pages/members.html')
  → inject HTML into #content
  → bindModalTriggers()   ← re-binds Add Member, Add Trainer, Add User buttons
  → bindFormHandlers()    ← re-binds all form submit handlers
```

Because the admin HTML fragments are re-injected on every nav, **always re-bind event listeners inside `bindModalTriggers()` and `bindFormHandlers()`** rather than at the top level.

### Registered pages (pageMap)

| Key | Fragment |
|-----|---------|
| `dashboard` | `Admin-pages/dashboard.html` |
| `members` | `Admin-pages/members.html` |
| `classes` | `Admin-pages/classes.html` |
| `trainers` | `Admin-pages/trainers.html` |
| `subscriptions` | `Admin-pages/subscriptions.html` |
| `payments` | `Admin-pages/payments.html` |
| `events` | `Admin-pages/events.html` |
| `roles` | `Admin-pages/roles.html` |

`revenue` is listed in the sidebar nav in `admin-panel.html` but not yet in `pageMap` — add it when `Admin-pages/revenue.html` is ready for DB integration.

### Session guard

On every page load, `checkAdminSession()` hits `/api/admin/auth/check-session.php`. If the server returns 401 or 403, the user is redirected to `login-page.html`. A 404 is silently ignored (dev mode — PHP not yet built).

### Toast notifications

```js
showAdminPopup('Message here', 'success')  // green
showAdminPopup('Error message', 'error')   // red
```

The toast appears bottom-right and auto-hides after 3.5 seconds.

### CSRF in admin actions

```js
function getCsrfToken() {
  const input = document.querySelector('input[name="csrf_token"]');
  return input ? input.value : '';
}
```

Use `getCsrfToken()` when building `FormData` for inline action buttons (e.g., `cancelClass`, `cancelEvent`).

### Adding a new admin action button

1. Add the button in the relevant `Admin-pages/*.html` fragment with an `onclick="myAction(id)"` attribute.
2. Expose the function on `window` in `admin-js.js`:
   ```js
   window.myAction = function(id) { ... };
   ```
3. For mutations, use the `postForm()` helper:
   ```js
   const fd = new FormData();
   fd.append('item_id', id);
   fd.append('csrf_token', getCsrfToken());
   const result = await postForm('/api/admin/your-endpoint.php', fd);
   if (result?.success) showAdminPopup('Done!', 'success');
   ```

### Currently stubbed global window functions

These are defined in `admin-js.js` with `console.log` placeholders — implement when DB is connected:

- `window.editClass(classId)`
- `window.viewMember(memberId)`
- `window.changePage(direction)`
- `window.editEvent(eventId)`
- `window.editTrainer(trainerId)`
- `window.viewTrainerSchedule(trainerId)`
- `window.editPlan(planId)`
- `window.manageSub(memberId)`
- `window.viewTransaction(txnId)`
- `window.editUser(userId)`

---

## 11. Styling Conventions

### CSS import order

Every page stylesheet should begin with:
```css
@import url("GENERAL-LAYOUT.css");   /* global reset — always first */
@import url("general.css");          /* shared header/nav (user-facing pages only) */
@import url("payment-method.css");   /* only on pages with payment forms */
@import url("../components/css/pop-up.css");
@import url("../components/css/loading-component.css");
```

### Color palette

| Variable | Value | Usage |
|----------|-------|-------|
| Primary orange | `#ff6b35` | Buttons, accents, active states |
| Orange light | `#ff8c5a` | Button gradients, hover states |
| Dark background | `#1a1a1a` | Header, sidebar |
| Light background | `#f5f6fb` | Page background |
| Success green | `#2e7d32` | Active/completed badges, admin success toast |
| Warning orange | `#f57c00` | Pending badges |
| Error red | `#c62828` | Expired/failed/danger badges, admin error toast |
| Blue badge | `#2563eb` | Info badges |

### Design patterns

- **Cards:** `border-radius: 15px`, `box-shadow: 0 2px 10px rgba(0,0,0,0.05)`, hover lifts with `translateY(-5px)`.
- **Buttons:** Orange gradient `linear-gradient(135deg, #ff6b35, #ff8c5a)`, `box-shadow`, hover lifts with `translateY(-2px)`.
- **Status badges:** Inline `<span>` with colored background + matching text color, `border-radius: 12px`, `padding: 4px 12px`.
- **Stat values:** Class `.stat-value`, `font-size: 2.5rem`, `font-weight: 900`, colored `#ff6b35`.
- **Grid layout:** Use `display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px` for card grids.
- **Subscription cards:** `border: 3px solid #ff6b35; border-radius: 20px; padding: 32px`. Selection variant uses `border: 3px solid #e5e5e5` and turns orange on `:has(input:checked)`.

### Responsive breakpoints

```css
@media (max-width: 1024px) { /* Tablet */ }
@media (max-width: 768px)  { /* Mobile — hide header nav, stack grids */ }
```

---

## 12. JavaScript Conventions

### Module vs. non-module scripts

| Type | When to use | Examples |
|------|-------------|---------|
| `type="module"` | Any file that uses `import`/`export` | `book-class-page.js`, `homepage.js`, `pop-up.js` |
| Regular `<script>` | Global utilities that expose window functions | `loading.js`, `payment-methods.js`, `header.js`, `carousel.js`, `trainer-carousel.js` |

**Load order matters.** Global utilities (`loading.js`) must be loaded before module scripts that call `showLoading()`.

### Exposing functions globally from modules

ES Modules are scoped. To make a function callable from `onclick=` attributes in HTML:

```js
// In your module
window.myFunction = myFunction;
```

This is required for: `closePopUp`, `handleOk`, `nextStep`, `prevStep`, `selectClass`, `selectDate`, `selectTime`, `selectTrainer`, `selectSession`, `prepareBookingSubmit`, `prepareTrainerSubmit`, `togglePricing`, `getSelectedPlanPrice`, `handlePayment`, `showAdminPopup`, and all admin action functions.

### Async fetch pattern

All API calls follow this pattern:

```js
showLoading("Processing...");
try {
  const response = await fetch('/api/endpoint.php', {
    method: 'POST',
    body: formData,
  });
  const result = await response.json();
  hideLoading();
  if (result.success) {
    // handle success
  } else {
    showPopUP(result.message || 'Something went wrong.');
  }
} catch (error) {
  hideLoading();
  showPopUP('Connection error. Please try again.');
}
```

### Admin fetch pattern (`postForm` helper)

For admin mutations, use the existing helper in `admin-js.js`:

```js
const fd = new FormData();
fd.append('csrf_token', getCsrfToken());
fd.append('field', value);
const result = await postForm('/api/admin/endpoint.php', fd);
// postForm auto-redirects to login on 401/403
if (result?.success) showAdminPopup('Done!', 'success');
else showAdminPopup(result?.message || 'Failed.', 'error');
```

### Naming conventions

- **Files:** `kebab-case.js`, `kebab-case.css`, `kebab-case.html`
- **Functions:** `camelCase`
- **Constants / data keys:** `camelCase`
- **CSS classes:** `kebab-case`
- **IDs:** `camelCase` for JS targets (e.g., `addMemberModal`), `kebab-case` acceptable for pure CSS targets

---

## 13. Security Checklist

Before deploying any PHP endpoint:

- [ ] **CSRF token** — validated on every POST via `require_csrf()` (token stored in `$_SESSION['csrf_token']`)
- [ ] **Session check** — member endpoints call `require_member()`, admin endpoints call `require_admin()`
- [ ] **Role check** — use `require_admin(['super_admin'])` or `is_super_admin()` for restricted actions
- [ ] **Input sanitization** — use `sanitize_string()`, `sanitize_email()`, `sanitize_int()` from `config.php`; use `htmlspecialchars()` / `filter_input()` as needed
- [ ] **SQL prepared statements** — never concatenate user input into queries; all DB blocks use PDO with `?` or `:named` placeholders
- [ ] **File uploads** — validate MIME type and extension server-side (trainer photo upload)
- [ ] **Password hashing** — use `password_hash()` / `password_verify()` (never store plain text)
- [ ] **Unique email** — check for existing email before insert on members and admin_users
- [ ] **Self-action guard** — admin cannot suspend/delete their own account (`$_SESSION['member_id'] === $member_id` check in `members/delete.php`)
- [ ] **HTTPS** — enforce on production; set `session.cookie_secure = true`
- [ ] **Error responses** — never expose stack traces or DB errors to the client; use the `error()` helper

---

## 14. Common Gotchas

### `assests/` typo in asset paths

The assets folder is named `assests/` (misspelled). This is used consistently everywhere. Do **not** fix the spelling without doing a project-wide find-and-replace across all HTML and JS files.

### Admin page fragments must not include `<html>` tags

Files in `Admin-pages/` are injected via `innerHTML`. They must contain only the inner page content — no `<!DOCTYPE>`, `<html>`, `<head>`, `<body>`, or `<link>` tags. All styles are inherited from `admin-css.css` which is loaded in `admin-panel.html`.

### Event listeners must be re-bound after admin page injection

Every time a new admin page is loaded, the DOM is replaced. Listeners attached to old elements are lost. Always register new listeners inside `bindModalTriggers()` or `bindFormHandlers()` in `admin-js.js`, which are called after every page inject.

### `payment-methods.js` runs on load automatically

`payment-methods.js` is a non-module script that auto-injects payment HTML into `.payment-method-js` on `DOMContentLoaded`. It also has a `setTimeout` fallback for pages that inject the container dynamically. You don't need to call any function — just include the script and add the class to your container div.

### `loading.js` must load before module scripts

`showLoading()` and `hideLoading()` are global functions defined by `components/loading.js`. If a module script is loaded before `loading.js`, calls to `showLoading()` will throw `ReferenceError`. Always place `<script src="components/loading.js"></script>` before `<script type="module" ...>`.

### PHP CSRF token placeholder in static HTML

Several HTML files contain `<?php echo $_SESSION['csrf_token']; ?>` inside hidden inputs. These will render as literal text in a static server (Python's `http.server`, Live Server). They only work correctly when served by PHP. For frontend-only development this is harmless but visually incorrect.

### Image paths are relative to the consuming page

Trainer images in `data/Trainers.js` use paths like `../assests/trainers/nadjaCole.png`. This works from `components/` or `js/` subdirectory context. If you consume the data from the root, adjust the path prefix.

### Admin revenue numbers are display data only

The values in `Admin-pages/revenue.html` are currently hard-coded HTML. When connecting to the database, replace them with data fetched from `GET /api/admin/reports/revenue.php`.

### `specialtyTag` vs `specialtyTags` naming

In `data/Trainers.js` the field is `specialtyTag` (singular, array of 4). In the PHP API and DB schema it is `specialty_tags` (JSON array). Keep these consistent when connecting the trainer data source to the DB.

### `subcriptionCards.js` filename typo

The component file is spelled `subcriptionCards.js` (missing the 's' in 'subscription'). Do not rename it without updating all import paths and `<script>` tags that reference it.

### `selectTrainer.js` note on image paths

`components/selectTrainer.js` outputs trainer cards for the booking wizard. It references trainer image paths from `data/Trainers.js` which use `../assests/trainers/` — correct relative to `components/`. If the file is moved, update the paths.

---

## 15. API Reference

All endpoints return `{ "success": bool, "message": string, ...data }`. All POST endpoints require `csrf_token`. All admin endpoints require an active admin session.

> **Current state:** All endpoints are coded and validated but return `503` until the DB (`api/config.php` credentials) is connected. Uncomment the PDO block and remove the stub line to activate any endpoint.

### Auth (`/api/auth/`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/login.php` | — | Login; returns `{ role }` |
| POST | `/api/auth/register.php` | — | Register new member |
| POST | `/api/auth/logout.php` | Session | Destroy session |
| GET | `/api/auth/check-session.php` | Session | Returns session data or 401 |

### Member — Membership (`/api/user/membership/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user/membership/info.php` | Current plan + subscription details |
| POST | `/api/user/membership/pause.php` | Pause or resume subscription (`action=pause\|resume`) |

### Member — Bookings (`/api/bookings/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/bookings/book-class.php` | Book a group class |
| POST | `/api/bookings/book-trainer.php` | Book a personal training session |
| POST | `/api/bookings/cancel.php` | Cancel a class or trainer booking |

### Member — Payments (`/api/payments/`, `/api/user/payments/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/payments/process.php` | Process membership renewal/upgrade/change |
| GET | `/api/user/payments/history.php` | Member payment history (filterable) |

### Member — Schedule & Trainers (`/api/user/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user/schedule/list.php` | Weekly class schedule (filterable) |
| GET | `/api/user/trainers/list.php` | Public trainer directory |
| GET | `/api/user/trainers/availability.php` | Trainer available slots for a date |

### Member — Events (`/api/user/events/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user/events/list.php` | Upcoming public events |
| POST | `/api/user/events/register.php` | Register for an event |

### Admin — Members (`/api/admin/members/`)

| Method | Endpoint | Roles | Description |
|--------|----------|-------|-------------|
| GET | `/api/admin/members/list.php` | all | Paginated member list |
| GET | `/api/admin/members/view.php?id=` | all | Full member profile + history |
| POST | `/api/admin/members/create.php` | all | Create member (walk-in) |
| POST | `/api/admin/members/update.php` | all | Edit member profile/fields |
| POST | `/api/admin/members/delete.php` | all (delete: super_admin) | Suspend / unsuspend / delete |

### Admin — Classes (`/api/admin/classes/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/classes/list.php` | All class schedules (filterable) |
| POST | `/api/admin/classes/create.php` | Schedule a class (supports recurring) |
| POST | `/api/admin/classes/update.php` | Edit or cancel a class |

### Admin — Bookings (`/api/admin/bookings/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/bookings/list.php` | All class + trainer bookings (unified) |
| POST | `/api/admin/bookings/update.php` | Update booking status |

### Admin — Trainers (`/api/admin/trainers/`)

| Method | Endpoint | Roles | Description |
|--------|----------|-------|-------------|
| GET | `/api/admin/trainers/list.php` | all | All trainers (filterable) |
| POST | `/api/admin/trainers/create.php` | all | Add a trainer |
| POST | `/api/admin/trainers/update.php` | all | Edit trainer profile |
| POST | `/api/admin/trainers/delete.php` | all (delete: super_admin) | Deactivate / reactivate / delete |

### Admin — Events (`/api/admin/events/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/events/list.php` | All events (filterable) |
| POST | `/api/admin/events/create.php` | Create an event |
| POST | `/api/admin/events/update.php` | Edit or cancel an event |

### Admin — Payments (`/api/admin/payments/`)

| Method | Endpoint | Roles | Description |
|--------|----------|-------|-------------|
| GET | `/api/admin/payments/list.php` | all | All payment records + totals |
| POST | `/api/admin/payments/refund.php` | admin, super_admin | Issue full or partial refund |

### Admin — Reports (`/api/admin/reports/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/reports/dashboard.php` | KPIs for admin dashboard |
| GET | `/api/admin/reports/memberships.php` | Membership growth + churn report |
| GET | `/api/admin/reports/revenue.php` | Detailed revenue breakdown |

### Admin — Settings (`/api/admin/settings/`)

| Method | Endpoint | Roles | Description |
|--------|----------|-------|-------------|
| GET | `/api/admin/settings/admins.php` | super_admin | List admin accounts |
| POST | `/api/admin/settings/admins.php?action=create` | super_admin | Create admin/staff account |
| POST | `/api/admin/settings/admins.php?action=update` | super_admin | Update admin account |
| POST | `/api/admin/settings/admins.php?action=deactivate` | super_admin | Deactivate admin account |
| GET | `/api/admin/settings/audit-log.php` | admin, super_admin | Admin activity audit log |