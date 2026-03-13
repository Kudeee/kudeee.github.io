/**
 * cancel-membership.js
 * Handles the 3-step cancellation flow on cancel-membership.php.
 */

// ── State ────────────────────────────────────────────────────────────────────

let memberData = null;
let subscriptionData = null;

// ── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  loadMemberInfo();
  bindReasonRadios();
  bindCheckboxes();
});

// ── Load member & subscription data ──────────────────────────────────────────

async function loadMemberInfo() {
  try {
    const res  = await fetch('api/user/membership/info.php');
    const data = await res.json();

    if (!data.success) {
      window.location.href = 'login-page.php';
      return;
    }

    memberData       = data.member;
    subscriptionData = data.subscription;

    // Populate step-1 summary
    const fullName = memberData.first_name + ' ' + memberData.last_name;
    setText('sumName',    fullName);
    setText('sumPlan',    memberData.plan || '—');
    setText('sumBilling', capitalize(memberData.billing_cycle || 'monthly'));

    const expiryStr = subscriptionData?.expiry_date
      ? fmtDate(subscriptionData.expiry_date)
      : '—';
    setText('sumExpiry',     expiryStr);
    setText('confirmExpiry', expiryStr);
    setText('successExpiry', expiryStr);

  } catch (err) {
    console.warn('Could not load member info:', err);
  }
}

// ── Step navigation ───────────────────────────────────────────────────────────

window.goStep = function (stepNum) {
  // Validate before advancing
  if (stepNum === 3) {
    const selected = document.querySelector('input[name="cancel_reason"]:checked');
    if (!selected) {
      flashMessage('Please select a reason for cancelling.');
      return;
    }
    // If "other" selected, require text
    if (selected.value === 'other') {
      const txt = document.getElementById('otherReasonText')?.value?.trim();
      if (!txt) {
        flashMessage('Please describe your reason.');
        return;
      }
    }
  }

  // Hide all cards
  [1, 2, 3, 4].forEach(n => {
    const card = document.getElementById('step-' + n);
    if (card) card.classList.add('hidden');
  });

  // Show target card
  const target = document.getElementById('step-' + stepNum);
  if (target) target.classList.remove('hidden');

  // Update step dots
  updateStepDots(stepNum);

  window.scrollTo({ top: 0, behavior: 'smooth' });
};

function updateStepDots(activeStep) {
  const lines = document.querySelectorAll('.step-line');

  [1, 2, 3].forEach((n, idx) => {
    const dot = document.getElementById('step-dot-' + n);
    if (!dot) return;
    dot.classList.remove('active', 'done');
    if (n < activeStep)      dot.classList.add('done');
    else if (n === activeStep) dot.classList.add('active');

    if (lines[idx]) {
      lines[idx].classList.toggle('done', n < activeStep);
    }
  });
}

// ── Reason radio — show/hide "other" textarea ─────────────────────────────────

function bindReasonRadios() {
  document.querySelectorAll('input[name="cancel_reason"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const wrap = document.getElementById('otherReasonWrap');
      if (!wrap) return;
      if (radio.value === 'other') wrap.classList.remove('hidden');
      else wrap.classList.add('hidden');
    });
  });
}

// ── Checkboxes — enable final button when all ticked ─────────────────────────

function bindCheckboxes() {
  ['chk1', 'chk2', 'chk3'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', updateFinalBtn);
  });
}

function updateFinalBtn() {
  const allChecked = ['chk1', 'chk2', 'chk3'].every(id => {
    const el = document.getElementById(id);
    return el && el.checked;
  });
  const btn = document.getElementById('finalCancelBtn');
  if (btn) btn.disabled = !allChecked;
}

// ── Submit cancellation ───────────────────────────────────────────────────────

window.submitCancellation = async function () {
  const reason = document.querySelector('input[name="cancel_reason"]:checked')?.value || '';
  const note   = document.getElementById('otherReasonText')?.value?.trim() || '';

  const btn = document.getElementById('finalCancelBtn');
  if (btn) { btn.disabled = true; btn.textContent = 'Cancelling…'; }

  if (typeof showLoading === 'function') showLoading('Cancelling membership…');

  try {
    const fd = new FormData();
    fd.append('reason', reason);
    if (note) fd.append('note', note);

    const res    = await fetch('api/user/membership/cancel.php', { method: 'POST', body: fd });
    const result = await res.json();

    if (typeof hideLoading === 'function') hideLoading();

    if (result.success) {
      goStep(4);
    } else {
      if (btn) { btn.disabled = false; btn.textContent = 'Cancel My Membership'; }
      flashMessage(result.message || 'Could not cancel membership. Please try again.');
    }

  } catch (err) {
    if (typeof hideLoading === 'function') hideLoading();
    if (btn) { btn.disabled = false; btn.textContent = 'Cancel My Membership'; }
    flashMessage('Something went wrong. Please try again.');
    console.error(err);
  }
};

// ── Utilities ─────────────────────────────────────────────────────────────────

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

function capitalize(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function fmtDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  if (isNaN(d)) return str;
  return d.toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' });
}

function flashMessage(msg) {
  // Remove existing flash if present
  const old = document.getElementById('cancelFlash');
  if (old) old.remove();

  const el = document.createElement('div');
  el.id = 'cancelFlash';
  el.style.cssText = `
    position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
    background:#c62828;color:#fff;padding:13px 24px;border-radius:10px;
    font-weight:700;font-size:0.9rem;z-index:9999;
    box-shadow:0 6px 20px rgba(0,0,0,.2);
    animation:fadeIn 0.2s ease;
  `;
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3800);
}
