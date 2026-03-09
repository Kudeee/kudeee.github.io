/**
 * admin-js.js
 * Society Fit — Admin Panel JS
 * Backend-ready: all mutations use fetch POST to PHP endpoints.
 * Session guard: redirects to login if server returns 401/403.
 */

// ─── Session / Auth Guard ───────────────────────────────────────────────────

/**
 * Called on every admin page load.
 * PHP should return 401 if the session is not an admin.
 * 404 is ignored — it just means the PHP file hasn't been created yet (dev mode).
 */
async function checkAdminSession() {
  try {
    const res = await fetch('/api/admin/auth/check-session.php');
    if (res.status === 401 || res.status === 403) {
      window.location.href = '/login-page.html';
    }
    // 404 = PHP not built yet, stay on page silently
  } catch {
    // Network error — stay on page but warn
    console.warn('Session check failed — offline or PHP not set up yet.');
  }
}

checkAdminSession();

// ─── Content Container ───────────────────────────────────────────────────────

/**
 * Returns the main content element.
 * Tries common IDs used in admin panel shells.
 */
function getContentEl() {
  return (
    document.getElementById('main-content') ||
    document.getElementById('content') ||
    document.getElementById('admin-content') ||
    document.querySelector('.content') ||
    document.querySelector('.main-content') ||
    document.querySelector('main')
  );
}

// ─── Page Loader (SPA-style sidebar nav) ────────────────────────────────────

const pageMap = {
  dashboard:     'Admin-pages/dashboard.html',
  members:       'Admin-pages/members.html',
  classes:       'Admin-pages/classes.html',
  trainers:      'Admin-pages/trainers.html',
  subscriptions: 'Admin-pages/subscriptions.html',
  payments:      'Admin-pages/payments.html',
  events:        'Admin-pages/events.html',
  roles:         'Admin-pages/roles.html',
};

async function loadPage(pageName) {
  const path = pageMap[pageName];
  if (!path) return;

  const container = getContentEl();
  if (!container) {
    console.error('admin-js: No content container found. Add id="main-content" to your admin shell.');
    return;
  }

  try {
    const res = await fetch(path);
    if (!res.ok) throw new Error(`Failed to load ${path}`);
    const html = await res.text();
    container.innerHTML = html;

    // Highlight active nav link
    document.querySelectorAll('.sidebar a').forEach(link => {
      link.classList.toggle('active', link.dataset.page === pageName);
    });

    // Re-bind modal triggers after page inject
    bindModalTriggers();
    bindFormHandlers();
  } catch (err) {
    const el = getContentEl();
    if (el) el.innerHTML =
      `<div class="card"><p style="color:red;">Failed to load page. Please try again.</p></div>`;
    console.error(err);
  }
}

// ─── Init on DOM Ready ───────────────────────────────────────────────────────

function init() {
  // Attach nav click listeners
  document.querySelectorAll('.sidebar a[data-page]').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      loadPage(link.dataset.page);
    });
  });

  // Load dashboard by default
  loadPage('dashboard');
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

// ─── Modal Helpers ───────────────────────────────────────────────────────────

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) { modal.style.display = 'flex'; }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) { modal.style.display = 'none'; }
}

// Expose globally for onclick= attributes in injected HTML
window.closeModal = closeModal;

// Close modal when clicking the backdrop
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-backdrop') ||
      (e.target.style.background && e.target.style.background.includes('rgba'))) {
    // Only close if click is directly on the backdrop, not a child
    const modals = document.querySelectorAll('[id$="Modal"]');
    modals.forEach(m => {
      if (e.target === m) m.style.display = 'none';
    });
  }
});

function bindModalTriggers() {
  const triggers = {
    addMemberBtn:   'addMemberModal',
    addTrainerBtn:  'addTrainerModal',
    addUserBtn:     'addUserModal',
  };
  Object.entries(triggers).forEach(([btnId, modalId]) => {
    const btn = document.getElementById(btnId);
    if (btn) btn.addEventListener('click', () => openModal(modalId));
  });
}

// ─── Generic fetch POST helper ───────────────────────────────────────────────

async function postForm(endpoint, formData) {
  const res = await fetch(endpoint, { method: 'POST', body: formData });
  if (res.status === 401 || res.status === 403) {
    window.location.href = '/login-page.html';
    return null;
  }
  return res.json();
}

// ─── Form Handlers ───────────────────────────────────────────────────────────

function bindFormHandlers() {
  // --- Schedule Class ---
  const scheduleClassForm = document.getElementById('scheduleClassForm');
  if (scheduleClassForm) {
    scheduleClassForm.addEventListener('submit', async e => {
      e.preventDefault();
      const result = await postForm('/api/admin/classes/create.php', new FormData(scheduleClassForm));
      if (!result) return;
      if (result.success) {
        showAdminPopup('Class scheduled successfully!', 'success');
        scheduleClassForm.reset();
      } else {
        showAdminPopup(result.message || 'Failed to schedule class.', 'error');
      }
    });
  }

  // --- Add Member ---
  const addMemberForm = document.getElementById('addMemberForm');
  if (addMemberForm) {
    addMemberForm.addEventListener('submit', async e => {
      e.preventDefault();
      const result = await postForm('/api/admin/members/create.php', new FormData(addMemberForm));
      if (!result) return;
      if (result.success) {
        showAdminPopup('Member added successfully!', 'success');
        closeModal('addMemberModal');
        addMemberForm.reset();
      } else {
        showAdminPopup(result.message || 'Failed to add member.', 'error');
      }
    });
  }

  // --- Member Filter (GET form — let it submit naturally) ---
  // No JS override needed; form method="GET" will reload with query params.

  // --- Create Event ---
  const createEventForm = document.getElementById('createEventForm');
  if (createEventForm) {
    createEventForm.addEventListener('submit', async e => {
      e.preventDefault();
      const result = await postForm('/api/admin/events/create.php', new FormData(createEventForm));
      if (!result) return;
      if (result.success) {
        showAdminPopup('Event created successfully!', 'success');
        createEventForm.reset();
      } else {
        showAdminPopup(result.message || 'Failed to create event.', 'error');
      }
    });
  }

  // --- Add Trainer ---
  const addTrainerForm = document.getElementById('addTrainerForm');
  if (addTrainerForm) {
    addTrainerForm.addEventListener('submit', async e => {
      e.preventDefault();
      const result = await postForm('/api/admin/trainers/create.php', new FormData(addTrainerForm));
      if (!result) return;
      if (result.success) {
        showAdminPopup('Trainer added successfully!', 'success');
        closeModal('addTrainerModal');
        addTrainerForm.reset();
      } else {
        showAdminPopup(result.message || 'Failed to add trainer.', 'error');
      }
    });
  }

  // --- Create Subscription Plan ---
  const createPlanForm = document.getElementById('createPlanForm');
  if (createPlanForm) {
    createPlanForm.addEventListener('submit', async e => {
      e.preventDefault();
      const result = await postForm('/api/admin/subscriptions/create-plan.php', new FormData(createPlanForm));
      if (!result) return;
      if (result.success) {
        showAdminPopup('Plan saved successfully!', 'success');
        createPlanForm.reset();
      } else {
        showAdminPopup(result.message || 'Failed to save plan.', 'error');
      }
    });
  }

  // --- Refund Form ---
  const refundForm = document.getElementById('refundForm');
  if (refundForm) {
    refundForm.addEventListener('submit', async e => {
      e.preventDefault();
      const result = await postForm('/api/admin/payments/refund.php', new FormData(refundForm));
      if (!result) return;
      if (result.success) {
        showAdminPopup('Refund issued successfully!', 'success');
        closeModal('refundModal');
        refundForm.reset();
      } else {
        showAdminPopup(result.message || 'Failed to issue refund.', 'error');
      }
    });
  }

  // --- Add User (Roles) ---
  const addUserForm = document.getElementById('addUserForm');
  if (addUserForm) {
    addUserForm.addEventListener('submit', async e => {
      e.preventDefault();
      const result = await postForm('/api/admin/roles/create-user.php', new FormData(addUserForm));
      if (!result) return;
      if (result.success) {
        showAdminPopup('User created successfully!', 'success');
        closeModal('addUserModal');
        addUserForm.reset();
      } else {
        showAdminPopup(result.message || 'Failed to create user.', 'error');
      }
    });
  }

  // --- Edit User (Roles) ---
  const editUserForm = document.getElementById('editUserForm');
  if (editUserForm) {
    editUserForm.addEventListener('submit', async e => {
      e.preventDefault();
      const result = await postForm('/api/admin/roles/update-user.php', new FormData(editUserForm));
      if (!result) return;
      if (result.success) {
        showAdminPopup('User updated successfully!', 'success');
        closeModal('editUserModal');
      } else {
        showAdminPopup(result.message || 'Failed to update user.', 'error');
      }
    });
  }
}

// ─── Action Functions (called by inline onclick= in injected HTML) ────────────

// Classes
window.editClass = function(classId) {
  // TODO: fetch class data then populate an edit modal
  console.log('Edit class:', classId);
};
window.cancelClass = async function(classId) {
  if (!confirm('Cancel this class? Members will be notified.')) return;
  const fd = new FormData();
  fd.append('class_id', classId);
  fd.append('csrf_token', getCsrfToken());
  const result = await postForm('/api/admin/classes/cancel.php', fd);
  if (result?.success) showAdminPopup('Class cancelled.', 'success');
  else showAdminPopup(result?.message || 'Failed to cancel class.', 'error');
};

// Members
window.viewMember = function(memberId) {
  // TODO: open member detail modal / navigate to detail page
  console.log('View member:', memberId);
};
window.changePage = function(direction) {
  // TODO: fetch next/prev page from server with current filters
  console.log('Paginate:', direction);
};

// Events
window.editEvent = function(eventId) { console.log('Edit event:', eventId); };
window.cancelEvent = async function(eventId) {
  if (!confirm('Cancel this event?')) return;
  const fd = new FormData();
  fd.append('event_id', eventId);
  fd.append('csrf_token', getCsrfToken());
  const result = await postForm('/api/admin/events/cancel.php', fd);
  if (result?.success) showAdminPopup('Event cancelled.', 'success');
  else showAdminPopup(result?.message || 'Failed to cancel event.', 'error');
};

// Trainers
window.editTrainer = function(trainerId) { console.log('Edit trainer:', trainerId); };
window.viewTrainerSchedule = function(trainerId) { console.log('View schedule:', trainerId); };

// Subscriptions
window.editPlan = function(planId) { console.log('Edit plan:', planId); };
window.archivePlan = async function(planId) {
  if (!confirm('Archive this plan? It will be hidden from new signups.')) return;
  const fd = new FormData();
  fd.append('plan_id', planId);
  fd.append('csrf_token', getCsrfToken());
  const result = await postForm('/api/admin/subscriptions/archive-plan.php', fd);
  if (result?.success) showAdminPopup('Plan archived.', 'success');
  else showAdminPopup(result?.message || 'Failed to archive plan.', 'error');
};
window.manageSub = function(memberId) { console.log('Manage subscription for member:', memberId); };

// Payments
window.viewTransaction = function(txnId) { console.log('View transaction:', txnId); };
window.openRefundModal = function(txnId, amount) {
  document.getElementById('refund_transaction_id').value = txnId;
  document.getElementById('refund_amount').value = amount || '';
  openModal('refundModal');
};

// Roles
window.editUser = function(userId) {
  document.getElementById('edit_user_id').value = userId;
  openModal('editUserModal');
};

// Export buttons (trigger server-side CSV download)
document.addEventListener('click', e => {
  if (e.target.id === 'exportBtn' || e.target.id === 'exportPaymentsBtn' || e.target.id === 'exportTrainersBtn') {
    const map = {
      exportBtn:          '/api/admin/members/export.php',
      exportPaymentsBtn:  '/api/admin/payments/export.php',
      exportTrainersBtn:  '/api/admin/trainers/export.php',
    };
    window.location.href = map[e.target.id] || '#';
  }
});

// ─── CSRF Helper ─────────────────────────────────────────────────────────────

function getCsrfToken() {
  // Grab from any CSRF hidden input currently in the DOM
  const input = document.querySelector('input[name="csrf_token"]');
  return input ? input.value : '';
}

// ─── Admin Toast / Popup ─────────────────────────────────────────────────────

function showAdminPopup(message, type = 'success') {
  let toast = document.getElementById('adminToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'adminToast';
    toast.style.cssText = `
      position: fixed; bottom: 30px; right: 30px; z-index: 9999;
      padding: 15px 25px; border-radius: 10px; font-weight: 600;
      font-size: 0.95rem; box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      transition: opacity 0.3s;
    `;
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.style.background = type === 'success' ? '#2e7d32' : '#c62828';
  toast.style.color = '#fff';
  toast.style.opacity = '1';
  clearTimeout(toast._timeout);
  toast._timeout = setTimeout(() => { toast.style.opacity = '0'; }, 3500);
}

window.showAdminPopup = showAdminPopup;