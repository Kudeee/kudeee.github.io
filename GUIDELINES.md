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

---

## 1. Project Overview

**Society Fitness** is a gym management web application for a Filipino fitness gym. It serves two audiences:

- **Members** — can sign up, log in, book classes, book personal trainers, manage their membership plan, and view their payment history.
- **Admins / Staff** — have access to a full admin panel that manages members, subscriptions, classes, trainers, events, revenue, payments, and role-based access control.

The application is a traditional multi-page web app (MPA) with a single-page app (SPA) pattern only inside the admin panel. There is no frontend framework — everything is vanilla HTML, CSS, and JavaScript using ES Modules.

The currency used throughout is **Philippine Peso (₱)**. Phone numbers follow the PH format (`09XX-XXX-XXXX`).

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Markup | HTML5 |
| Styling | CSS3 — custom, no framework |
| Scripting | Vanilla JavaScript (ES Modules) |
| Backend | PHP 7.4+ (REST-style JSON API) |
| Server | Apache / Nginx / `php -S` (local dev) |
| No build tools | No npm, webpack, Vite, or transpiler |

**Key design decision:** The project intentionally avoids build tools and JS frameworks to keep the stack simple, portable, and easy to onboard onto a shared hosting environment (which is common in the PH market).

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

---

## 4. Directory Structure & Conventions

```
/
├── index.html                  ← Public landing page (entry point for visitors)
├── login-page.html             ← Member login
├── sign-up-page.html           ← Multi-step registration
├── homepage.html               ← Authenticated member dashboard
├── schedule-page.html          ← Weekly class schedule grid + list view
├── book-class-page.html        ← 4-step class booking wizard
├── book-trainer-page.html      ← 4-step personal trainer booking wizard
├── trainers-page.html          ← Full trainer directory
├── my-membership.html          ← Membership plan management
├── payment.html                ← Renew / upgrade / change plan payment
├── payments-page.html          ← Member payment history
├── admin-panel.html            ← Admin SPA shell
│
├── Admin-pages/                ← HTML fragments (NOT standalone pages)
│   └── *.html                  ← Each file is injected into admin-panel.html
│
├── api/                        ← PHP backend
│   ├── auth/                   ← login.php, register.php
│   ├── admin/                  ← Admin-only endpoints (check session first!)
│   ├── bookings/               ← Class + trainer booking
│   ├── payments/               ← Payment processing
│   └── contact/                ← Contact form
│
├── css/                        ← All stylesheets
│   ├── GENERAL-LAYOUT.css      ← Global * reset only — import this first
│   ├── general.css             ← Shared header/nav/container — import for user pages
│   ├── admin-css.css           ← Admin panel only
│   ├── payment-method.css      ← Payment option cards (shared)
│   └── [page-name].css         ← One stylesheet per HTML page
│
├── js/                         ← Page-level scripts and modules
├── components/                 ← Reusable UI: loading, pop-up, subscription cards, etc.
│   └── css/                    ← Component-scoped stylesheets
├── data/                       ← JS data modules (trainers, subscriptions)
└── assests/                    ← [sic] Static files: images, icons, logos, trainer photos
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

---

## 7. Backend (PHP API) Workflow

### Every endpoint must

1. **Start a session** and verify CSRF on POST:
   ```php
   session_start();
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
           http_response_code(403);
           echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
           exit;
       }
   }
   ```

2. **Return JSON**:
   ```php
   header('Content-Type: application/json');
   echo json_encode(['success' => true, 'data' => $result]);
   ```

3. **Protect admin routes** by checking session role:
   ```php
   if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
       http_response_code(401);
       echo json_encode(['success' => false, 'message' => 'Unauthorized']);
       exit;
   }
   ```

### Standard JSON response shape

```json
{ "success": true }
{ "success": false, "message": "Human-readable error" }
{ "success": true, "data": { ... } }
```

### Auth flow

- `POST /api/auth/login.php` — validates credentials, creates session, returns `{ success, role }`
- JS redirects: `role === 'admin'` → `admin-panel.html`, otherwise → `homepage.html`
- `POST /api/auth/register.php` — creates member, logs them in, returns `{ success }`
- `GET /api/admin/auth/check-session.php` — returns 200 if valid admin session, 401 otherwise

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

### Subscription cards — `components/subcriptionCards.js`

Renders subscription plan cards in three modes, controlled by the selector you target:

| Selector | Function | Used on |
|----------|----------|---------|
| `.selection-cards` | `initSubscriptionSelection()` | Sign-up page (radio buttons) |
| `.card-container` | `initSubscriptionCards()` | My Membership page (manage) |

The toggle between monthly/yearly pricing is handled globally via `window.togglePricing(bool)`.

### Payment methods — `js/payment-methods.js`

Non-module script. Auto-injects the payment option HTML into every `.payment-method-js` element on the page. Exposes `window.handlePayment(event)` for form `onsubmit`.

### Header — `js/header.js`

Non-module script. Injects the shared top nav into `.header-js`. Highlights the active nav link by comparing `window.location.pathname` to each link's `href`.

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
Sidebar link click
  → loadPage('members')
  → fetch('Admin-pages/members.html')
  → inject HTML into #content
  → bindModalTriggers()   ← re-binds Add Member, Add Trainer, Add User buttons
  → bindFormHandlers()    ← re-binds all form submit handlers
```

Because the admin HTML fragments are re-injected on every nav, **always re-bind event listeners inside `bindModalTriggers()` and `bindFormHandlers()`** rather than at the top level.

### Session guard

On every page load, `checkAdminSession()` hits `/api/admin/auth/check-session.php`. If the server returns 401 or 403, the user is redirected to `login-page.html`. A 404 is silently ignored (dev mode — PHP not yet built).

### Toast notifications

```js
showAdminPopup('Message here', 'success')  // green
showAdminPopup('Error message', 'error')   // red
```

The toast appears bottom-right and auto-hides after 3.5 seconds.

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
| Dark background | `#1a1a1a` | Header, sidebar |
| Light background | `#f5f6fb` | Page background |
| Success green | `#2e7d32` | Active/completed badges |
| Warning orange | `#f57c00` | Pending badges |
| Error red | `#c62828` | Expired/failed/danger badges |

### Design patterns

- **Cards:** `border-radius: 15px`, `box-shadow: 0 2px 10px rgba(0,0,0,0.05)`, hover lifts with `translateY(-5px)`.
- **Buttons:** Orange gradient `linear-gradient(135deg, #ff6b35, #ff8c5a)`, `box-shadow`, hover lifts with `translateY(-2px)`.
- **Status badges:** Inline `<span>` with colored background + matching text color, `border-radius: 12px`, `padding: 4px 12px`.
- **Stat values:** Class `.stat-value`, `font-size: 2.5rem`, `font-weight: 900`, colored `#ff6b35`.
- **Grid layout:** Use `display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px` for card grids.

### Responsive breakpoints

```css
@media (max-width: 1024px) { /* Tablet */ }
@media (max-width: 768px)  { /* Mobile — hide header nav, stack grids */ }
```

---

## 12. JavaScript Conventions

### Module vs. non-module scripts

| Type | When to use |
|------|-------------|
| `type="module"` | Any file that uses `import`/`export` |
| Regular `<script>` | Global utilities: `loading.js`, `payment-methods.js`, `header.js`, `carousel.js` |

**Load order matters.** Global utilities (`loading.js`) must be loaded before module scripts that call `showLoading()`.

### Exposing functions globally from modules

ES Modules are scoped. To make a function callable from `onclick=` attributes in HTML:

```js
// In your module
window.myFunction = myFunction;
```

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

### Naming conventions

- **Files:** `kebab-case.js`, `kebab-case.css`, `kebab-case.html`
- **Functions:** `camelCase`
- **Constants / data keys:** `camelCase`
- **CSS classes:** `kebab-case`
- **IDs:** `camelCase` for JS targets (e.g., `addMemberModal`), `kebab-case` acceptable for pure CSS targets

---

## 13. Security Checklist

Before deploying any PHP endpoint:

- [ ] **CSRF token** — validated on every POST (token stored in `$_SESSION['csrf_token']`)
- [ ] **Session check** — admin endpoints must verify `$_SESSION['user_role']`
- [ ] **Input sanitization** — use `htmlspecialchars()`, `filter_input()`, or prepared statements
- [ ] **SQL prepared statements** — never concatenate user input into queries
- [ ] **File uploads** — validate MIME type and extension server-side (trainer photo upload)
- [ ] **Password hashing** — use `password_hash()` / `password_verify()` (never store plain text)
- [ ] **HTTPS** — enforce on production; set `session.cookie_secure = true`
- [ ] **Error responses** — never expose stack traces or DB errors to the client

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

The values in `Admin-pages/revenue.html` (₱721K monthly revenue, etc.) are currently hard-coded HTML. When connecting to the database, these need to be populated by a PHP endpoint rather than being static.