<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/schedule-page.css" />
    <title>Schedule</title>
  </head>
  <body>
    <header class="header header-js"></header>

    <div class="container">
      <div class="page-header">
        <h1 class="page-title">Class Schedule</h1>
        <p class="page-subtitle">Browse and book your favorite classes</p>
      </div>

      <div class="filter-section">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label">Class Type</label>
            <select class="filter-select">
              <option>All Classes</option>
              <option>HIIT</option>
              <option>Yoga</option>
              <option>Boxing</option>
              <option>CrossFit</option>
              <option>Spin</option>
              <option>Pilates</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">Trainer</label>
            <select class="filter-select">
              <option>All Trainers</option>
              <option>Emma Rodriguez</option>
              <option>Mike Chen</option>
              <option>Sarah Johnson</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">Time</label>
            <select class="filter-select">
              <option>All Times</option>
              <option>Morning (6AM-12PM)</option>
              <option>Afternoon (12PM-5PM)</option>
              <option>Evening (5PM-9PM)</option>
            </select>
          </div>
          <div class="view-toggle">
            <button class="view-btn active" onclick="toggleView('grid')">
              📅 Week
            </button>
            <button class="view-btn" onclick="toggleView('list')">
              📋 List
            </button>
          </div>
        </div>
      </div>

      <div id="gridView">
        <div class="schedule-grid">
          <div class="schedule-header">Time</div>
          <div class="schedule-header">Monday</div>
          <div class="schedule-header">Tuesday</div>
          <div class="schedule-header">Wednesday</div>
          <div class="schedule-header">Thursday</div>
          <div class="schedule-header">Friday</div>
          <div class="schedule-header">Saturday</div>
          <div class="schedule-header">Sunday</div>

          <div class="time-slot">6:00 AM</div>
          <div class="class-slot has-class" onclick="bookClass('Yoga')">
            <div class="class-name">Yoga Flow</div>
            <div class="class-trainer">Sarah J.</div>
            <div class="class-spots">5 spots</div>
          </div>
          <div class="class-slot"></div>
          <div class="class-slot has-class" onclick="bookClass('HIIT')">
            <div class="class-name">HIIT</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">3 spots</div>
          </div>
          <div class="class-slot"></div>
          <div class="class-slot has-class" onclick="bookClass('Yoga')">
            <div class="class-name">Yoga Flow</div>
            <div class="class-trainer">Sarah J.</div>
            <div class="class-spots">8 spots</div>
          </div>
          <div class="class-slot"></div>
          <div class="class-slot"></div>

          <div class="time-slot">9:00 AM</div>
          <div class="class-slot has-class" onclick="bookClass('CrossFit')">
            <div class="class-name">CrossFit</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">4 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Spin')">
            <div class="class-name">Spin Class</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">6 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Pilates')">
            <div class="class-name">Pilates</div>
            <div class="class-trainer">Sarah J.</div>
            <div class="class-spots">7 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('CrossFit')">
            <div class="class-name">CrossFit</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">2 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Yoga')">
            <div class="class-name">Yoga</div>
            <div class="class-trainer">Sarah J.</div>
            <div class="class-spots">10 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('HIIT')">
            <div class="class-name">HIIT</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">5 spots</div>
          </div>
          <div class="class-slot"></div>

          <div class="time-slot">12:00 PM</div>
          <div class="class-slot has-class" onclick="bookClass('Boxing')">
            <div class="class-name">Boxing</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">4 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Yoga')">
            <div class="class-name">Yoga</div>
            <div class="class-trainer">Sarah J.</div>
            <div class="class-spots">6 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('HIIT')">
            <div class="class-name">HIIT</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">3 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Boxing')">
            <div class="class-name">Boxing</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">7 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Spin')">
            <div class="class-name">Spin</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">8 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('CrossFit')">
            <div class="class-name">CrossFit</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">5 spots</div>
          </div>
          <div class="class-slot"></div>

          <div class="time-slot">5:00 PM</div>
          <div class="class-slot has-class" onclick="bookClass('HIIT')">
            <div class="class-name">HIIT</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">2 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Boxing')">
            <div class="class-name">Boxing</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">4 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Yoga')">
            <div class="class-name">Yoga</div>
            <div class="class-trainer">Sarah J.</div>
            <div class="class-spots">9 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('CrossFit')">
            <div class="class-name">CrossFit</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">3 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('HIIT')">
            <div class="class-name">HIIT</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">6 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Spin')">
            <div class="class-name">Spin</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">7 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Yoga')">
            <div class="class-name">Yoga</div>
            <div class="class-trainer">Sarah J.</div>
            <div class="class-spots">10 spots</div>
          </div>

          <div class="time-slot">7:00 PM</div>
          <div class="class-slot has-class" onclick="bookClass('CrossFit')">
            <div class="class-name">CrossFit</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">5 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Yoga')">
            <div class="class-name">Yoga</div>
            <div class="class-trainer">Sarah J.</div>
            <div class="class-spots">8 spots</div>
          </div>
          <div class="class-slot has-class" onclick="bookClass('Boxing')">
            <div class="class-name">Boxing</div>
            <div class="class-trainer">Mike C.</div>
            <div class="class-spots">4 spots</div>
          </div>
          <div class="class-slot"></div>
          <div class="class-slot has-class" onclick="bookClass('Spin')">
            <div class="class-name">Spin</div>
            <div class="class-trainer">Emma R.</div>
            <div class="class-spots">6 spots</div>
          </div>
          <div class="class-slot"></div>
          <div class="class-slot"></div>
        </div>

        <div class="legend">
          <div class="legend-item">
            <div
              class="legend-color"
              style="background: linear-gradient(135deg, #ff6b35, #ff8c5a)"
            ></div>
            <span>Class Available</span>
          </div>
          <div class="legend-item">
            <div
              class="legend-color"
              style="background: #fff; border: 2px solid #e0e0e0"
            ></div>
            <span>No Class</span>
          </div>
        </div>
      </div>

      <div id="listView" class="list-view">
        <div class="day-section">
          <h2 class="day-header">Monday, January 16</h2>
          <div class="class-card">
            <div class="class-time">6:00 AM</div>
            <div class="class-info">
              <h3>Yoga Flow</h3>
              <div class="class-meta">
                <span>👤 Sarah Johnson</span>
                <span>⏱️ 60 min</span>
                <span>📍 Studio A</span>
                <span style="color: #ff6b35; font-weight: 600"
                  >5 spots left</span
                >
              </div>
            </div>
            <button class="btn" onclick="bookClass('Yoga Flow')">
              Book Now
            </button>
          </div>
          <div class="class-card">
            <div class="class-time">9:00 AM</div>
            <div class="class-info">
              <h3>CrossFit</h3>
              <div class="class-meta">
                <span>👤 Mike Chen</span>
                <span>⏱️ 50 min</span>
                <span>📍 Main Gym</span>
                <span style="color: #ff6b35; font-weight: 600"
                  >4 spots left</span
                >
              </div>
            </div>
            <button class="btn" onclick="bookClass('CrossFit')">
              Book Now
            </button>
          </div>
          <div class="class-card">
            <div class="class-time">12:00 PM</div>
            <div class="class-info">
              <h3>Boxing Fundamentals</h3>
              <div class="class-meta">
                <span>👤 Mike Chen</span>
                <span>⏱️ 45 min</span>
                <span>📍 Boxing Ring</span>
                <span style="color: #ff6b35; font-weight: 600"
                  >4 spots left</span
                >
              </div>
            </div>
            <button class="btn" onclick="bookClass('Boxing')">Book Now</button>
          </div>
        </div>
      </div>
    </div>
  </body>

  <script src="js/schedule-page.js"></script>
  <script src="js/header.js"></script>
</html>
