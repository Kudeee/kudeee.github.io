<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/landing-page.css" />
    <title>Society Fitness</title>
  </head>
  <body>
    <header>
      <nav class="container">
        <div class="logo">
          <a href="index.php">
            <img src="assests/logo/society-fit.png" alt="society-fit logo" />
          </a>
        </div>
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>
        <ul class="nav-links" id="navLinks">
          <li><a href="homepage.php">Home</a></li>
          <li><a href="#profile" onclick="closeMenu()">Profile</a></li>
          <li><a href="#pricing" onclick="closeMenu()">Pricing</a></li>
          <li><a href="#classes" onclick="closeMenu()">Classes</a></li>
          <li><a href="#trainers" onclick="closeMenu()">Trainers</a></li>
          <li><a href="#events" onclick="closeMenu()">Events</a></li>
          <li><a href="#blog" onclick="closeMenu()">Blog</a></li>
          <li><a href="#contact" onclick="closeMenu()">Contact</a></li>
          <li><a href="#faq" onclick="closeMenu()">FAQ</a></li>
        </ul>
      </nav>
    </header>

    <section class="hero">
      <div class="container">
        <h1>Transform Your Life at Society Fitness</h1>
        <p>State of the art facilities, expert trainers, and a supportive community</p>
        <a href="#pricing" class="btn">Join Now</a>
        <a href="#schedule" class="btn btn-secondary">View Classes</a>
      </div>
    </section>

    <section id="profile">
      <div class="container">
        <h2>About Society Fitness</h2>
        <div class="profile-content">
          <div class="profile-image">
            <img src="assests/images/gym-interior.webp" alt="gym interior" />
          </div>
          <div>
            <h3>Your Fitness Partner</h3>
            <p>
              At FitLife, we believe fitness is a journey, not a destination.
              Our state-of-the-art facility features the latest equipment,
              diverse class offerings, and certified personal trainers dedicated
              to helping you achieve your goals.
            </p>
            <p>
              Whether you're a beginner taking your first steps toward a
              healthier lifestyle or an experienced athlete pushing your limits,
              FitLife provides the perfect environment for success.
            </p>
            <ul class="check-list">
              <li>24/7 access</li>
              <li>Professional Trainers</li>
              <li>Modern Equipment</li>
              <li>Group Classes</li>
              <li>Supportive Community</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section id="pricing">
      <div class="container">
        <h2>Membership Plans</h2>

        <div class="pricing-toggle">
          <span class="toggle-label active">Monthly</span>
          <label class="toggle-switch">
            <input
              type="checkbox"
              id="landingPricingToggle"
              onchange="toggleLandingPricing(this.checked)"
            />
            <span class="toggle-slider"></span>
          </label>
          <span class="toggle-label">Yearly</span>
          <span class="toggle-discount">Save 16%</span>
        </div>

        <div class="pricing-cards">
          <div class="pricing-card">
            <h3>Basic Plan</h3>
            <div class="price-display">
              <span class="monthly-price">₱499/month</span>
              <span class="yearly-price" style="display: none">₱5,028/year</span>
            </div>
            <div class="savings-info" style="display: none; color: #2e7d32; font-weight: 600; margin: 10px 0;">
              Save ₱960
            </div>
            <ul class="features">
              <li>Gym access</li>
              <li>Basic equipment</li>
              <li>Free Wifi</li>
              <li>Locker rental available</li>
            </ul>
            <div class="button-container">
              <a href="sign-up-page.php">Get Started</a>
            </div>
          </div>

          <div class="pricing-card">
            <h3>Premium Plan</h3>
            <div class="price-display">
              <span class="monthly-price">₱899/month</span>
              <span class="yearly-price" style="display: none">₱9,067/year</span>
            </div>
            <div class="savings-info" style="display: none; color: #2e7d32; font-weight: 600; margin: 10px 0;">
              Save ₱1,721
            </div>
            <ul class="features">
              <li>All Basic Features</li>
              <li>Locker Access</li>
              <li>Group Classes</li>
              <li>Nutritional guidance</li>
              <li>10% merchandise discount</li>
            </ul>
            <div class="button-container">
              <a href="sign-up-page.php">Get Started</a>
            </div>
          </div>

          <div class="pricing-card">
            <h3>VIP Plan</h3>
            <div class="price-display">
              <span class="monthly-price">₱1,500/month</span>
              <span class="yearly-price" style="display: none">₱15,120/year</span>
            </div>
            <div class="savings-info" style="display: none; color: #2e7d32; font-weight: 600; margin: 10px 0;">
              Save ₱2,880
            </div>
            <ul class="features">
              <li>All Premium Features</li>
              <li>Personal Trainer (2x/week)</li>
              <li>Priority Booking</li>
              <li>Free guest passes (2/month)</li>
              <li>Massage therapy (1x/month)</li>
              <li>20% merchandise discount</li>
            </ul>
            <div class="button-container">
              <a href="sign-up-page.php">Get Started</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="classes">
      <div class="container">
        <h2>Class Schedule</h2>
        <table class="sched-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>Monday</th>
              <th>Wednesday</th>
              <th>Friday</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>6:00 AM</td><td>Yoga</td><td>HIIT</td><td>Yoga</td></tr>
            <tr><td>9:00 AM</td><td>CrossFit</td><td>Pilates</td><td>Yoga</td></tr>
            <tr><td>12:00 PM</td><td>Boxing</td><td>Zumba</td><td>Boxing</td></tr>
            <tr><td>5:00 PM</td><td>CrossFit</td><td>HIIT</td><td>Yoga</td></tr>
            <tr><td>7:00 PM</td><td>Zumba</td><td>Boxing</td><td>CrossFit</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section id="trainers">
      <div class="container">
        <h2>Meet our Trainers</h2>
        <div class="carousel-container">
          <button class="carousel-nav prev" onclick="prevTrainer()">‹</button>
          <div class="carousel-wrapper">
            <div class="grid carousel-track trainer-grid-js"></div>
          </div>
          <button class="carousel-nav next" onclick="nextTrainer()">›</button>
        </div>
      </div>
    </section>

    <section id="events">
      <div class="container">
        <h2>Upcoming Events</h2>
        <div class="event-card">
          <div class="event-date">
            <div class="event-day">25</div>
            <div>JAN</div>
          </div>
          <div>
            <h3>New Year Fitness Challenge</h3>
            <p>Join us for our annual 30-day fitness challenge. Set goals, track progress, and win prizes!</p>
            <p>Time: 6:00 AM - 8:00 PM</p>
          </div>
        </div>
        <div class="event-card">
          <div class="event-date">
            <div class="event-day">25</div>
            <div>JAN</div>
          </div>
          <div>
            <h3>Nutrition Workshop</h3>
            <p>Learn meal prep strategies and nutrition fundamentals from our certified dietitian.</p>
            <p>Time: 2:00 PM - 4:00 PM</p>
          </div>
        </div>
      </div>
    </section>

    <section id="contact">
      <div class="container">
        <h2>Get in Touch</h2>

        <!-- Contact form POSTs to PHP handler -->
        <form class="contact-form" method="POST" action="/api/contact/inquiry.php">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />

          <div class="form-group">
            <label for="contact_name">Name</label>
            <input type="text" id="contact_name" name="name" required />
          </div>
          <div class="form-group">
            <label for="contact_email">Email</label>
            <input type="email" id="contact_email" name="email" required />
          </div>
          <div class="form-group">
            <label for="contact_phone">Phone</label>
            <input type="tel" id="contact_phone" name="phone" />
          </div>
          <div class="form-group">
            <label for="contact_interest">I'm Interested In</label>
            <select id="contact_interest" name="interest">
              <option value="membership">Membership</option>
              <option value="personal_training">Personal Training</option>
              <option value="group_classes">Group Classes</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="contact_message">Message</label>
            <textarea id="contact_message" name="message" rows="5"></textarea>
          </div>
          <button type="submit" class="btn">Send Message</button>
        </form>
      </div>
    </section>

    <section id="faq">
      <div class="container">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-item" onclick="toggleFAQ(this)">
          <div class="faq-question">
            <span>What are your operating hours?</span>
            <span>+</span>
          </div>
          <div class="faq-answer">
            FitLife is open 24/7 for all members. Our staffed hours are Monday–Friday 5am–10pm, and weekends 7am–8pm.
          </div>
        </div>
        <div class="faq-item" onclick="toggleFAQ(this)">
          <div class="faq-question">
            <span>Do you offer trial memberships?</span>
            <span>+</span>
          </div>
          <div class="faq-answer">
            Yes! We offer a free 7-day trial pass for first-time visitors. Contact us to schedule your visit.
          </div>
        </div>
        <div class="faq-item" onclick="toggleFAQ(this)">
          <div class="faq-question">
            <span>Can I freeze my membership?</span>
            <span>+</span>
          </div>
          <div class="faq-answer">
            Members can freeze their membership for up to 3 months per year for medical or travel reasons.
          </div>
        </div>
        <div class="faq-item" onclick="toggleFAQ(this)">
          <div class="faq-question">
            <span>What should I bring to my first visit?</span>
            <span>+</span>
          </div>
          <div class="faq-answer">
            Bring comfortable workout clothes, athletic shoes, a water bottle, and a towel. We provide lockers and showers.
          </div>
        </div>
      </div>
    </section>

    <footer>
      <div class="container">
        <p>&copy; 2026 Society Fitness Gym. All rights reserved.</p>
        <p>241 st. Kalsada Ave, syudad city | 09123456789101112 | info@societyfitnessgym.com</p>
      </div>
    </footer>

    <div id="loading"></div>
    <div id="pop-up"></div>

    <script type="module" src="js/landing-page.js"></script>
    <script type="module" src="components/meetOurTrainer.js"></script>
    <script src="js/trainer-carousel.js"></script>
    <script src="components/loading.js"></script>
    <script type="module" src="components/pop-up.js"></script>
  </body>
</html>