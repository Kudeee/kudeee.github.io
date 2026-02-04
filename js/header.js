const header = `
        <div class="header-content">
            <div class="logo">
            <a href="index.html">
                <img src="assests/logo/society-fit.png" alt="society-fit logo">
            </a></div>
            <ul class="header-nav">
                <li><a href="homepage.html">Home</a></li>
                <li><a href="schedule-page.html">Schedule</a></li>
                <li><a href="trainers-page.html">Trainers</a></li>
            </ul>
            <div class="user-profile">
                <div>
                    <div style="font-weight: 600;">Ben Dover</div>
                    <div style="font-size: 0.85rem; color: #ff6b35;">Premium Member</div>
                </div>
                <div class="user-avatar">BD</div>
            </div>
        </div>
`;

document.querySelector('.header-js').innerHTML = header;

const currentPage = window.location.pathname.split('/').pop();

document.querySelectorAll('.header-nav a').forEach(link => {
    if (link.getAttribute('href') === currentPage) {
        link.classList.add('active');
    }
});
