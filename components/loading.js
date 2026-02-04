function renderLoading() {
  return `
        <div id="loadingOverlay" class="loading-overlay">
            <div class="spinner"></div>
            <p class="loading-text"></p>
        </div>
    `;
}

document.getElementById("loading").innerHTML = renderLoading();

const buttons = document.querySelectorAll("button, .btn");

function showLoading(message = "Processing") {
  const overlay = document.getElementById("loadingOverlay");
  const loadingText = document.querySelector(".loading-text");

  if (loadingText) {
    loadingText.textContent = message;
  }

  if (overlay) {
    overlay.classList.add("active");
  }

  disableAllButtons();
}

function hideLoading() {
  const overlay = document.getElementById("loadingOverlay");

  if (overlay) {
    overlay.classList.remove("active");
  }

  enableAllButtons();
}

function disableAllButtons() {
  buttons.forEach((button) => {
    button.disabled = true;
    button.style.opacity = "0.6";
    button.style.cursor = "not-allowed";
  });
}

function enableAllButtons() {
  buttons.forEach((button) => {
    button.disabled = false;
    button.style.opacity = "1";
    button.style.cursor = "pointer";
  });
}

function simulateLoading(duration = 2000) {
  return new Promise((resolve) => {
    setTimeout(resolve, duration);
  });
}
