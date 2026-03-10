import { showPopUP, renderPopUP, closePopUp } from "../components/pop-up.js";
import { render } from "./renderer.js";

render('#pop-up', 'warning', renderPopUP);
window.closePopUp = closePopUp;

// ─── Validation rules ─────────────────────────────────────────────────────────

const validationRules = {
  first_name: {
    selector: '#first-page input[name="first_name"]',
    test: (val) => val.trim().length > 0,
    message: "Please enter your first name",
  },
  last_name: {
    selector: '#first-page input[name="last_name"]',
    test: (val) => val.trim().length > 0,
    message: "Please enter your last name",
  },
  email: {
    selector: '#first-page input[name="email"]',
    test: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val),
    message: "Please enter a valid email address",
  },
  phone: {
    selector: '#first-page input[name="phone"]',
    test: (val) => /^(09|\+639)\d{9}$/.test(val.replace(/[-\s]/g, "")),
    message: "Please enter a valid phone number (e.g., 09091234567)",
  },
  password: {
    selector: '#second-page input[name="password"]',
    test: (val) =>
      val.length >= 8 &&
      /[A-Z]/.test(val) &&
      /[a-z]/.test(val) &&
      /[0-9]/.test(val),
    message: "Password must be 8+ characters with uppercase, lowercase, and numbers",
  },
  confirm_password: {
    selector: '#second-page input[name="confirm_password"]',
    test: (val) =>
      val === document.querySelector('#second-page input[name="password"]').value,
    message: "Passwords do not match",
  },
};

function validate(fields) {
  for (const field of fields) {
    const rule  = validationRules[field];
    const input = document.querySelector(rule.selector);
    if (!input || !rule.test(input.value)) {
      showPopUP(rule.message);
      input?.focus();
      return false;
    }
  }
  return true;
}

const validateFirstPage  = () => validate(["first_name", "last_name", "email", "phone"]);
const validateSecondPage = () => validate(["password", "confirm_password"]);

const validateMembershipPlan = () => {
  const selectedPlan = document.querySelector('input[name="membership_plan"]:checked');
  if (!selectedPlan) {
    showPopUP("Please select a membership plan");
    return false;
  }
  // Sync hidden fields for form POST
  document.getElementById("hidden_selected_plan").value = selectedPlan.value;
  document.getElementById("hidden_billing_cycle").value = selectedPlan.dataset.billing || "monthly";
  document.getElementById("hidden_plan_price").value    = selectedPlan.dataset.price    || "";
  updateReceipt(selectedPlan);
  return true;
};

const validateTerms = () => {
  if (!document.getElementById("terms")?.checked) {
    showPopUP("Please agree to the Terms and Conditions");
    return false;
  }
  return true;
};

function updateReceipt(selectedInput) {
  const price   = selectedInput.dataset.price;
  const billing = selectedInput.dataset.billing;

  const receiptRow = document.querySelector(".reciept-row");
  const totalPrice = document.querySelector(".total-price");

  if (receiptRow && totalPrice) {
    receiptRow.querySelector(".price").textContent      = `₱${price}`;
    receiptRow.querySelector("small").textContent       = `Billed ${billing}`;
    totalPrice.textContent                              = `₱${price}`;
  }

  // Keep hidden fields in sync as user browses
  document.getElementById("hidden_selected_plan").value = selectedInput.value;
  document.getElementById("hidden_billing_cycle").value = billing || "monthly";
  document.getElementById("hidden_plan_price").value    = price    || "";
}

// ─── Multi-step navigation ────────────────────────────────────────────────────

document.addEventListener("DOMContentLoaded", () => {
  const pages = {
    first:  document.getElementById("first-page"),
    second: document.getElementById("second-page"),
    third:  document.getElementById("second-last-page"),
    fourth: document.getElementById("last-page"),
    sub:    document.getElementById("sub-page"),
  };

  const navigate = (from, to, validator) => {
    if (validator && !validator()) return;
    from.style.display = "none";
    to.style.display   = "block";
  };

  document.getElementById("fnext-btn").onclick           = () => navigate(pages.first,  pages.second, validateFirstPage);
  document.getElementById("next-btn").onclick            = () => navigate(pages.second, pages.third,  validateSecondPage);
  document.getElementById("second-last-next-btn").onclick= () => navigate(pages.third,  pages.fourth, validateMembershipPlan);
  document.getElementById("last-next-btn").onclick       = () => navigate(pages.fourth, pages.sub);

  document.getElementById("prev-btn").onclick            = () => navigate(pages.second, pages.first);
  document.getElementById("second-last-prev-btn").onclick= () => navigate(pages.third,  pages.second);
  document.getElementById("last-prev-btn").onclick       = () => navigate(pages.fourth, pages.third);
  document.getElementById("sub-prev-btn").onclick        = () => navigate(pages.sub,    pages.fourth);
});

// ─── Final form submission ────────────────────────────────────────────────────

document.querySelector("#form").addEventListener("submit", handleSignUp);

export async function handleSignUp(event) {
  event.preventDefault();

  if (!validateTerms()) return;

  showLoading("Creating your account...");

  try {
    const formData = new FormData(event.target);

    const response = await fetch("/api/auth/register.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    hideLoading();

    if (result.success) {
      window.location.href = "homepage.php";
    } else {
      showPopUP(result.message || "Sign up failed. Please try again.");
    }
  } catch (error) {
    hideLoading();
    showPopUP("Sign up failed. Please try again.");
  }
}