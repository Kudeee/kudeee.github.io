function renderPopUP() {
  return `
     <div id='pop-up' class="pop-up-container">
      <div class="pop-up-wrapper">
        <div class="pop-up">
          <div class="icon"><img src="./assests/icons/alert.svg" alt="" /></div>

          <div class="message">message example</div>

          <div class="pop-up-btn">
            <button class="popBtn popClose" onclick="closePopUp()">ok</button>
          </div>
        </div>
      </div>
    </div>
    `;
}

function renderOptionPopUP() {
  return `
     <div id='pop-up' class="pop-up-container">
      <div class="pop-up-wrapper">
        <div class="pop-up">
          <div class="icon"><img src="./assests/icons/alert.svg" alt="" /></div>

          <div class="message">message example</div>

          <div class="pop-up-btn">
            <button class="popBtn popNo btn-cancel" onclick="closePopUp()">No</button>
            <button class="popBtn popOk" onclick="handleOk()">Ok</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

if (document.getElementById("pop-up-render")) {
  document.getElementById("pop-up-render").innerHTML = renderPopUP();
  document.getElementById("pop-up").style.display = "none";
}

if (document.getElementById("pop-up-render-cancel")) {
  document.getElementById("pop-up-render-cancel").innerHTML =
    renderOptionPopUP();
    document.getElementById("pop-up").style.display = "none";
}

function showPopUP(message) {
  const popUp = document.getElementById("pop-up");
  const msg = document.querySelector(".message");

  msg.textContent = message;
  popUp.style.display = "flex";
}

function closePopUp() {
  const popUp = document.getElementById("pop-up");
  popUp.style.display = "none";
}

function handleOk() {
  alert("the action is handled");

  closePopUp();
}
