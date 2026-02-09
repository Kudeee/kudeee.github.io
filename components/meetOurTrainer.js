import { trainers } from "../data/Trainers.js";

let trainerHTML = '';

trainers.forEach((trainer) => {
    trainerHTML += `
    <div class="slide card trainer-card">
          <div class="trainer-image">
            <img src="${trainer.image}" alt="trainer">
          </div>
          <h3>${trainer.name}</h3>
          <p>${trainer.specialty}</p>
          <p>${trainer.briefIntro}</p>
    </div>
    `;
});

document.querySelector('.trainer-grid-js').innerHTML = trainerHTML;