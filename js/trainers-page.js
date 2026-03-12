/**
 * trainers-page.js
 * Fetches trainers from the database via api/trainers/list.php
 * and renders the trainer cards dynamically.
 */

const grid = document.querySelector('.trainers-grid-js');

// ─── Specialty filter options derived from DB data ─────────────────────────

const specialtyFilterEl   = document.querySelector('.filter-select:nth-of-type(1)');
const availFilterEl       = document.querySelector('.filter-select:nth-of-type(2)');
const experienceFilterEl  = document.querySelector('.filter-select:nth-of-type(3)');

// ─── Render helpers ────────────────────────────────────────────────────────

function availabilityLabel(avail) {
    return avail === 'limited' ? 'Limited Availability' : 'Available This Week';
}

function buildCard(trainer) {
    const tags = (trainer.specialty_tags || []).slice(0, 4)
        .map(t => `<span class="specialty-tag">${t}</span>`)
        .join('');

    const imgSrc = trainer.image_url || 'assests/trainers/default.png';

    return `
        <div class="trainer-card">
            <div class="trainer-image">
                <img src="${imgSrc}" alt="${trainer.full_name}" loading="lazy" />
                <div class="trainer-badge">₱${Number(trainer.session_rate).toLocaleString('en-PH')}/session</div>
            </div>
            <div class="trainer-info">
                <h2 class="trainer-name">${trainer.full_name}</h2>
                <div class="trainer-specialty">${trainer.specialty}</div>
                <p class="trainer-bio">${trainer.bio || ''}</p>

                <div class="trainer-stats">
                    <div class="stat-item">
                        <div class="stat-value">${trainer.exp_years}+</div>
                        <div class="stat-label">Years Exp</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${trainer.client_count}+</div>
                        <div class="stat-label">Clients</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${Number(trainer.rating).toFixed(1)}</div>
                        <div class="stat-label">Rating</div>
                    </div>
                </div>

                <div class="trainer-specialties">${tags}</div>

                <div class="trainer-actions">
                    <button class="btn"
                        onclick="location.href='book-trainer-page.php?trainer_id=${trainer.id}'">
                        Book Session
                    </button>
                    <button class="btn btn-outline"
                        onclick="viewProfile('${trainer.full_name}')">
                        View Profile
                    </button>
                </div>

                <div class="availability ${trainer.availability === 'limited' ? 'limited' : ''}">
                    ${availabilityLabel(trainer.availability)}
                </div>
            </div>
        </div>
    `;
}

function renderTrainers(trainers) {
    if (!trainers.length) {
        grid.innerHTML = '<p style="text-align:center;color:#999;padding:40px;grid-column:1/-1;">No trainers found matching your filters.</p>';
        return;
    }
    grid.innerHTML = trainers.map(buildCard).join('');
}

// ─── Filter logic ──────────────────────────────────────────────────────────

let allTrainers = [];

function applyFilters() {
    const specialty  = specialtyFilterEl?.value   || '';
    const avail      = availFilterEl?.value        || '';
    const minExp     = parseInt(experienceFilterEl?.value || '0') || 0;

    let filtered = allTrainers;

    if (specialty && specialty !== 'All Specialties') {
        filtered = filtered.filter(t =>
            t.specialty.toLowerCase().includes(specialty.toLowerCase()) ||
            (t.specialty_tags || []).some(tag =>
                tag.toLowerCase().includes(specialty.toLowerCase())
            )
        );
    }
    if (avail && avail !== 'All Times') {
        const map = { 'Morning': 'available', 'Afternoon': 'available', 'Evening': 'available' };
        filtered = filtered.filter(t => t.availability === (map[avail] || avail));
    }
    if (minExp > 0) {
        filtered = filtered.filter(t => t.exp_years >= minExp);
    }

    renderTrainers(filtered);
}

// ─── Fetch from API ────────────────────────────────────────────────────────

async function loadTrainers() {
    grid.innerHTML = '<p style="text-align:center;color:#999;padding:40px;grid-column:1/-1;">Loading trainers...</p>';

    try {
        const res  = await fetch('api/trainers/list.php');
        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'Failed to load');

        allTrainers = data.trainers;
        renderTrainers(allTrainers);

        // Populate specialty filter dynamically from DB data
        if (specialtyFilterEl) {
            const specialties = [...new Set(allTrainers.map(t => t.specialty))].sort();
            specialties.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                specialtyFilterEl.appendChild(opt);
            });
        }

    } catch (err) {
        console.error('Failed to load trainers:', err);
        grid.innerHTML = '<p style="text-align:center;color:#c00;padding:40px;grid-column:1/-1;">Could not load trainers. Please try again.</p>';
    }
}

// ─── Bind filters ──────────────────────────────────────────────────────────

[specialtyFilterEl, availFilterEl, experienceFilterEl].forEach(el => {
    el?.addEventListener('change', applyFilters);
});

// ─── Global helpers (kept for inline onclick compatibility) ────────────────

window.bookTrainer = function(name) {
    location.href = 'book-trainer-page.php';
};

window.viewProfile = function(name) {
    alert(`Viewing profile for ${name}`);
};

// ─── Init ──────────────────────────────────────────────────────────────────

loadTrainers();