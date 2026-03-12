let currentTrainerIndex = 0;
let trainersPerView = 3;
let totalTrainers = 0;

function updateTrainersPerView() {
    const width = window.innerWidth;
    if (width <= 768) {
        trainersPerView = 1;
    } else if (width <= 1024) {
        trainersPerView = 2;
    } else {
        trainersPerView = 3;
    }
}

function updateCarousel() {
    const track = document.querySelector('#trainers .carousel-track');
    const cards = document.querySelectorAll('#trainers .trainer-card');
    totalTrainers = cards.length;

    if (!track || totalTrainers === 0) return;

    updateTrainersPerView();

    const maxIndex = Math.max(0, totalTrainers - trainersPerView);
    currentTrainerIndex = Math.min(currentTrainerIndex, maxIndex);

    const cardWidth = cards[0].offsetWidth;
    const gap = 32; // 2rem gap
    const offset = -(currentTrainerIndex * (cardWidth + gap));

    track.style.transform = `translateX(${offset}px)`;

    const prevBtn = document.querySelector('.carousel-nav.prev');
    const nextBtn = document.querySelector('.carousel-nav.next');

    if (prevBtn && nextBtn) {
        prevBtn.disabled = currentTrainerIndex === 0;
        nextBtn.disabled = currentTrainerIndex >= maxIndex;
        prevBtn.style.opacity = currentTrainerIndex === 0 ? '0.5' : '1';
        nextBtn.style.opacity = currentTrainerIndex >= maxIndex ? '0.5' : '1';
        prevBtn.style.cursor = currentTrainerIndex === 0 ? 'not-allowed' : 'pointer';
        nextBtn.style.cursor = currentTrainerIndex >= maxIndex ? 'not-allowed' : 'pointer';
    }
}

function nextTrainer() {
    const maxIndex = Math.max(0, totalTrainers - trainersPerView);
    if (currentTrainerIndex < maxIndex) {
        currentTrainerIndex++;
        updateCarousel();
    }
}

function prevTrainer() {
    if (currentTrainerIndex > 0) {
        currentTrainerIndex--;
        updateCarousel();
    }
}

// Initialize once trainers are injected into the DOM (async fetch)
window.addEventListener('trainersLoaded', () => {
    setTimeout(updateCarousel, 100);
});

// Also support the old MutationObserver approach as fallback
const observer = new MutationObserver(() => {
    const trainerCards = document.querySelectorAll('#trainers .trainer-card');
    if (trainerCards.length > 0) {
        setTimeout(updateCarousel, 100);
        observer.disconnect();
    }
});

const trainerGrid = document.querySelector('.trainer-grid-js');
if (trainerGrid) {
    observer.observe(trainerGrid, { childList: true });
}

// Update on window resize
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateCarousel, 250);
});