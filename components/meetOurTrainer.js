/**
 * meetOurTrainer.js
 * Renders the trainer cards inside the landing page carousel.
 * Fetches live data from api/trainers/list.php (public endpoint).
 */

async function loadLandingTrainers() {
    const grid = document.querySelector('.trainer-grid-js');
    if (!grid) return;

    try {
        const res  = await fetch('api/trainers/list.php');
        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'Failed');

        grid.innerHTML = data.trainers.map(trainer => `
            <div class="slide card trainer-card">
                <div class="trainer-image">
                    <img src="${trainer.image_url || 'assests/trainers/default.png'}"
                         alt="${trainer.full_name}" loading="lazy" />
                </div>
                <h3>${trainer.full_name}</h3>
                <p>${trainer.specialty}</p>
                <p>${trainer.bio ? trainer.bio.split('.')[0] + '.' : ''}</p>
            </div>
        `).join('');

        // Notify the carousel script that trainers are loaded
        window.dispatchEvent(new Event('trainersLoaded'));

    } catch (err) {
        console.warn('meetOurTrainer: could not load trainers from API.', err);
        // Fallback: leave grid empty or show a message
        grid.innerHTML = '<p style="text-align:center;color:#999;padding:20px;">Trainers unavailable.</p>';
    }
}

loadLandingTrainers();