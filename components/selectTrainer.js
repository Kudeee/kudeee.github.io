/**
 * selectTrainer.js
 * Renders the trainer picker on the book-trainer-page.
 * Fetches live data from api/trainers/list.php (requires member auth).
 */

async function loadTrainerOptions() {
    const container = document.querySelector('.trainer-select-grid-js');
    if (!container) return;

    container.innerHTML = '<p style="text-align:center;color:#999;padding:40px;">Loading trainers...</p>';

    try {
        const res  = await fetch('api/trainers/list.php');
        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'Failed to load');

        const trainers = data.trainers;

        if (!trainers.length) {
            container.innerHTML = '<p style="text-align:center;color:#999;padding:40px;">No trainers available.</p>';
            return;
        }

        container.innerHTML = trainers.map(trainer => `
            <div class="trainer-option"
                 onclick="selectTrainer(
                     '${trainer.full_name.replace(/'/g, "\\'")}',
                     '${trainer.specialty.replace(/'/g, "\\'")}',
                     '${trainer.session_rate}'
                 )">
                <div class="trainer-photo">
                    <img src="${trainer.image_url || 'assests/trainers/default.png'}"
                         alt="${trainer.full_name}" loading="lazy" />
                </div>
                <div class="trainer-details">
                    <h3>${trainer.full_name}</h3>
                    <div class="trainer-specialty">${trainer.specialty}</div>
                    <div class="trainer-stats">
                        <span>⭐ ${Number(trainer.rating).toFixed(1)} Rating</span>
                        <span>🏋️ ${trainer.exp_years}+ Years</span>
                        <span>👥 ${trainer.client_count}+ Clients</span>
                    </div>
                </div>
                <div class="trainer-price">
                    <div class="price-value">₱${Number(trainer.session_rate).toLocaleString('en-PH')}</div>
                    <div class="price-label">/session</div>
                </div>
            </div>
        `).join('');

    } catch (err) {
        console.error('selectTrainer: failed to load trainers:', err);
        container.innerHTML = '<p style="text-align:center;color:#c00;padding:40px;">Could not load trainers. Please refresh the page.</p>';
    }
}

loadTrainerOptions();