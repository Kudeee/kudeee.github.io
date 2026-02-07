import {trainers} from '../data/trainer.js';

let trainersHTML = '';

trainers.forEach((trainer) => {
    trainersHTML += `
        <div class="trainer-card">
          <div class="trainer-image">
            <img src="${trainer.image}" alt="">
          </div>
          <div class="trainer-info">
            <h2 class="trainer-name">${trainer.name}</h2>
            <div class="trainer-specialty">${trainer.specialty}</div>
            <p class="trainer-bio">
              ${trainer.trainerBio}
            </p>

            <div class="trainer-stats">
              <div class="stat-item">
                <div class="stat-value">${trainer.exp}+</div>
                <div class="stat-label">Years Exp</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">${trainer.clients}+</div>
                <div class="stat-label">Clients</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">${trainer.rating}</div>
                <div class="stat-label">Rating</div>
              </div>
            </div>

            <div class="trainer-specialties">
              <span class="specialty-tag">${trainer.specialtyTag[0]}</span>
              <span class="specialty-tag">${trainer.specialtyTag[1]}</span>
              <span class="specialty-tag">${trainer.specialtyTag[2]}</span>
              <span class="specialty-tag">${trainer.specialtyTag[3]}</span>
            </div>

            <div class="trainer-actions">
              <button class="btn" onclick="bookTrainer('${trainer.name}')">
                Book Session
              </button>
              <button
                class="btn btn-outline"
                onclick="viewProfile('${trainer.name}')"
              >
                View Profile
              </button>
            </div>

            <div class="availability ${trainer.availCss}">${trainer.availability}</div>
          </div>
        </div>
    `;
});

document.querySelector('.trainers-grid-js').innerHTML = trainersHTML;