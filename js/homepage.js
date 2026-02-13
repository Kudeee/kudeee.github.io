import {renderPopUP, showPopUP, handleOk, closePopUp } from "../components/pop-up.js";
import {render} from './renderer.js';

render('#pop-up', "popUPOpt", renderPopUP);

window.handleOk = handleOk;
window.closePopUp = closePopUp;

document.querySelector("#pauseMem").addEventListener("click",() => {
     showPopUP('Are you sure you want to pause membership?');
})
   

document.querySelector("#CancelBooking").addEventListener("click", () => {
    showPopUP('Are you sure you want to cancel?');
})