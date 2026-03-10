<div class="header">
  <h1>Events</h1>
</div>

<!-- Event Statistics -->
<div class="grid" style="margin-bottom: 20px;">
  <div class="card">
    <h3>Upcoming Events</h3>
    <p class="stat-value" id="events-upcoming">—</p>
    <p class="stat-status">Next 60 days</p>
  </div>
  <div class="card">
    <h3>Total Registrations</h3>
    <p class="stat-value" id="events-total-reg">—</p>
    <p class="stat-status">This period</p>
  </div>
  <div class="card">
    <h3>Events This Week</h3>
    <p class="stat-value" id="events-this-week">—</p>
    <p class="stat-status">Scheduled</p>
  </div>
  <div class="card">
    <h3>Most Popular</h3>
    <p class="stat-value" id="events-popular">—</p>
    <p class="stat-status">Most registered</p>
  </div>
</div>

<!-- Create New Event -->
<div class="card">
  <h3>Create New Event</h3>
  <form id="createEventForm">
    <input type="hidden" name="csrf_token" value="" />
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
      <div class="form">
        <label for="event_name">Event Name</label>
        <input type="text" id="event_name" name="event_name" placeholder="Enter event name" required />
      </div>
      <div class="form">
        <label for="event_type">Event Type</label>
        <select id="event_type" name="event_type" required>
          <option value="">Select Type</option>
          <option value="fitness_challenge">Fitness Challenge</option>
          <option value="workshop">Workshop</option>
          <option value="competition">Competition</option>
          <option value="seminar">Seminar</option>
          <option value="open_house">Open House</option>
          <option value="special_class">Special Class</option>
        </select>
      </div>
      <div class="form">
        <label for="event_date">Date</label>
        <input type="date" id="event_date" name="event_date" required />
      </div>
      <div class="form">
        <label for="event_time">Time</label>
        <input type="time" id="event_time" name="event_time" required />
      </div>
      <div class="form">
        <label for="event_location">Location</label>
        <input type="text" id="event_location" name="event_location" placeholder="e.g. Main Gym / Studio A" required />
      </div>
      <div class="form">
        <label for="max_attendees">Max Attendees</label>
        <input type="number" id="max_attendees" name="max_attendees" value="50" min="1" max="500" required />
      </div>
      <div class="form">
        <label for="event_fee">Registration Fee (₱)</label>
        <input type="number" id="event_fee" name="event_fee" value="0" min="0" step="0.01" />
      </div>
      <div class="form">
        <label for="organizer_id">Organizer / Trainer</label>
        <select id="organizer_id" name="organizer_id">
          <option value="">Select Organizer</option>
          <option value="1">Ping Guerrero</option>
          <option value="2">Nadja Collins</option>
          <option value="3">Marcus Lee</option>
          <option value="4">Alyssa Moreno</option>
          <option value="5">Neil Aspag</option>
          <option value="6">Sofia Ramirez</option>
        </select>
      </div>
    </div>
    <div class="form">
      <label for="event_description">Event Description</label>
      <textarea id="event_description" name="event_description" rows="3" placeholder="Describe the event..."></textarea>
    </div>
    <div class="form">
      <label>
        <input type="checkbox" name="is_members_only" value="1" />
        Members Only Event
      </label>
    </div>
    <div class="scheduleClass-btn">
      <button type="submit">Create Event</button>
    </div>
  </form>
</div>

<!-- Upcoming Events List -->
<div style="margin-top: 30px;">
  <h2 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; margin-bottom: 20px;">Upcoming Events</h2>
  <div class="grid" id="upcoming-events-grid">
    <div class="card"><p style="color:#999;">Loading upcoming events...</p></div>
  </div>
</div>