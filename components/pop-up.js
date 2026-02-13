const icon = ["../assests/icons/alert.svg", "../assests/icons/checked.svg", ];

export function renderPopUP(popUpContent = 'warning') {
  document.getElementById("pop-up").style.display = "none";
  

  if(popUpContent === 'warning'){
    return `
     <div id='pop-up' class="pop-up-container">
      <div class="pop-up-wrapper">
        <div class="pop-up">
          <div class="icon"><img src=${icon[0]} alt="" /></div>

          <div class="message">message example</div>

          <div class="pop-up-btn">
            <button class="popBtn popClose" onclick="closePopUp()">ok</button>
          </div>
        </div>
      </div>
    </div>
    `;
  }

  if(popUpContent === 'done'){
    return `
     <div id='pop-up' class="pop-up-container">
      <div class="pop-up-wrapper">
        <div class="pop-up">
          <div class="icon"><img src=${icon[1]} alt="" /></div>

          <div class="message">message example</div>

          <div class="pop-up-btn">
            <button class="popBtn popClose" onclick="closePopUp()">ok</button>
          </div>
        </div>
      </div>
    </div>
    `;
  }

  if(popUpContent === 'popUPOpt'){
    return `
     <div id='pop-up' class="pop-up-container">
      <div class="pop-up-wrapper">
        <div class="pop-up">
          <div class="icon"><img src=${icon[0]} alt="" /></div>

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
}

export function showPopUP(message) {
  const popUp = document.getElementById("pop-up");
  const msg = document.querySelector(".message");

  msg.textContent = message;
  popUp.style.display = "flex";
}

export function closePopUp() {
  const popUp = document.getElementById("pop-up");
  popUp.style.display = "none";
}

export function handleOk() {
  alert("the action is handled");

  closePopUp();
}
