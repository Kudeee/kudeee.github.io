import { trainers } from "../data/Trainers.js";

let trainerOptionHTML = "";

trainers.forEach((trainer) => {
  trainerOptionHTML += `
    <div
      class="trainer-option"
        onclick="
            selectTrainer('${trainer.name}', '${trainer.specialty}', '${trainer.BaseRate}')
        "
        >
        <div class="trainer-photo"><img src="${trainer.image}" alt=""></div>
        <div class="trainer-details">
            <h3>${trainer.name}</h3>
            <div class="trainer-specialty">
            ${trainer.specialty}
            </div>
            <div class="trainer-stats">
            <span>⭐ ${trainer.rating} Rating</span>
            <span>🏋️ ${trainer.exp}+ Years</span>
            <span>👥 ${trainer.clients}+ Clients</span>
            </div>
        </div>
        <div class="trainer-price">
            <div class="price-value">₱${trainer.BaseRate}</div>
            <div class="price-label">/session</div>
        </div>
    </div>
 `;
});

document.querySelector('.trainer-select-grid-js').innerHTML = trainerOptionHTML;
