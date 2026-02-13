import { showPopUP, renderPopUP, closePopUp } from "../components/pop-up.js";
import { render } from "./renderer.js";

render('#pop-up', 'warning', renderPopUP);
window.closePopUp = closePopUp;

const validationRules = {
  firstName: { 
    selector: '#first-page input[placeholder="First name"]',
    test: (val) => val.trim().length > 0,
    message: "Please enter your first name"
  },
  lastName: {
    selector: '#first-page input[placeholder="Last name"]',
    test: (val) => val.trim().length > 0,
    message: "Please enter your last name"
  },
  email: {
    selector: '#first-page input[type="email"]',
    test: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val),
    message: "Please enter a valid email address"
  },
  phone: {
    selector: '#first-page input[type="tel"]',
    test: (val) => /^(09|\+639)\d{9}$/.test(val.replace(/[-\s]/g, '')),
    message: "Please enter a valid phone number (e.g., 09091234567)"
  },
  password: {
    selector: '#second-page input[name="password"]',
    test: (val) => val.length >= 8 && /[A-Z]/.test(val) && /[a-z]/.test(val) && /[0-9]/.test(val),
    message: "Password must be 8+ characters with uppercase, lowercase, and numbers"
  },
  confirmPassword: {
    selector: '#second-page input[name="confirm_password"]',
    test: (val) => val === document.querySelector('#second-page input[name="password"]').value,
    message: "Passwords do not match"
  }
};

// Generic validator
function validate(fields) {
  for (let field of fields) {
    const rule = validationRules[field];
    const input = document.querySelector(rule.selector);
    
    if (!input || !rule.test(input.value)) {
      showPopUP(rule.message);
      input?.focus();
      return false;
    }
  }
  return true;
}

// Page-specific validations
const validateFirstPage = () => validate(['firstName', 'lastName', 'email', 'phone']);
const validateSecondPage = () => validate(['password', 'confirmPassword']);

const validateMembershipPlan = () => {
  if (!document.querySelector('input[name="membership-plan"]:checked')) {
    showPopUP("Please select a membership plan");
    return false;
  }
  return true;
};

const validateTerms = () => {
  if (!document.getElementById('terms')?.checked) {
    showPopUP("Please agree to the Terms and Conditions");
    return false;
  }
  return true;
};

// ============================================
// NAVIGATION (ULTRA-COMPACT)
// ============================================

document.addEventListener("DOMContentLoaded", () => {
  const pages = {
    first: document.getElementById("first-page"),
    second: document.getElementById("second-page"),
    third: document.getElementById("second-last-page"),
    fourth: document.getElementById("last-page"),
    sub: document.getElementById("sub-page")
  };

  const navigate = (from, to, validator) => {
    if (validator && !validator()) return;
    from.style.display = "none";
    to.style.display = "block";
  };

  // Forward navigation with validation
  document.getElementById("fnext-btn").onclick = () => 
    navigate(pages.first, pages.second, validateFirstPage);
  
  document.getElementById("next-btn").onclick = () => 
    navigate(pages.second, pages.third, validateSecondPage);
  
  document.getElementById("second-last-next-btn").onclick = () => 
    navigate(pages.third, pages.fourth, validateMembershipPlan);
  
  document.getElementById("last-next-btn").onclick = () => 
    navigate(pages.fourth, pages.sub);

  // Back navigation (no validation needed)
  document.getElementById("prev-btn").onclick = () => 
    navigate(pages.second, pages.first);
  
  document.getElementById("second-last-prev-btn").onclick = () => 
    navigate(pages.third, pages.second);
  
  document.getElementById("last-prev-btn").onclick = () => 
    navigate(pages.fourth, pages.third);
  
  document.getElementById("sub-prev-btn").onclick = () => 
    navigate(pages.sub, pages.fourth);
});

// ============================================
// FORM SUBMISSION
// ============================================

document.querySelector('#form').addEventListener("submit", handleSignUp)

export async function handleSignUp(event) {
  event.preventDefault();
  
  if (!validateTerms()) return;

  showLoading("Signing Up");
  
  try {
    await simulateLoading(2000);
    hideLoading();
    window.location.href = "../homepage.html";
  } catch (error) {
    hideLoading();
    showPopUP("Sign up failed. Please try again.");
  }
}