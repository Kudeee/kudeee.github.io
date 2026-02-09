const track = document.querySelector(".carousel-track");
let slides = Array.from(document.querySelectorAll(".slide"));

const slideWidth = 100;
const interval = 3000;
let index = 1;
let isTransitioning = false;

/* CLONE first & last slides */
const firstClone = slides[0].cloneNode(true);
const lastClone = slides[slides.length - 1].cloneNode(true);

firstClone.classList.add("clone");
lastClone.classList.add("clone");

track.appendChild(firstClone);
track.insertBefore(lastClone, slides[0]);

slides = Array.from(document.querySelectorAll(".slide"));

/* Start at first real slide */
track.style.transform = `translateX(-${slideWidth * index}%)`;
slides[index].classList.add("active");

function moveToSlide() {
  if (isTransitioning) return;
  isTransitioning = true;

  track.style.transition = "transform 0.6s ease-in-out";
  track.style.transform = `translateX(-${slideWidth * index}%)`;

  slides.forEach(slide => slide.classList.remove("active"));
  slides[index].classList.add("active");
}

function nextSlide() {
  index++;
  moveToSlide();
}

/* Handle infinite teleport */
track.addEventListener("transitionend", () => {
  if (slides[index].classList.contains("clone")) {
    track.style.transition = "none";

    if (index === slides.length - 1) {
      index = 1;
    } else if (index === 0) {
      index = slides.length - 2;
    }

    track.style.transform = `translateX(-${slideWidth * index}%)`;

    // Force reflow to apply transform without animation
    track.offsetHeight;

    track.style.transition = "transform 0.6s ease-in-out";
  }

  slides.forEach(slide => slide.classList.remove("active"));
  slides[index].classList.add("active");

  isTransitioning = false;
});

/* Auto play */
setInterval(nextSlide, interval);
