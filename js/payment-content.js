const params = new URLSearchParams(window.location.search);
const type = params.get("type");

if (type === "upgrade") {
  document.querySelector(".header-name").innerHTML = "Upgrade Membership";
  document.querySelector(".payment-btn").innerHTML = "UPGRADE";
  document.querySelector(".page-title").innerHTML = "Upgrade Membership";
  document.getElementById("mem-plan").style.display = "none";
}
