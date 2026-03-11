<div class="header"><h1>Events</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Upcoming Events</h3><p class="stat-value" id="events-upcoming">—</p><p class="stat-status">From today onward</p></div>
  <div class="card"><h3>Total Registrations</h3><p class="stat-value" id="events-total-reg">—</p><p class="stat-status">All events</p></div>
  <div class="card"><h3>Events This Week</h3><p class="stat-value" id="events-this-week">—</p><p class="stat-status">Scheduled</p></div>
  <div class="card"><h3>Most Popular</h3><p class="stat-value" id="events-popular" style="font-size:1rem;">—</p><p class="stat-status">Most registered</p></div>
</div>

<div class="card" style="margin-bottom:20px;">
  <h3 style="margin-bottom:16px;">Create New Event</h3>
  <form id="createEventForm">
    <div class="form-grid">
      <div class="form-group"><label>Event Name</label><input type="text" name="event_name" required placeholder="Enter event name"/></div>
      <div class="form-group">
        <label>Event Type</label>
        <select name="event_type" required>
          <option value="">Select Type</option>
          <option value="fitness_challenge">Fitness Challenge</option>
          <option value="workshop">Workshop</option>
          <option value="competition">Competition</option>
          <option value="seminar">Seminar</option>
          <option value="open_house">Open House</option>
          <option value="special_class">Special Class</option>
        </select>
      </div>
      <div class="form-group"><label>Date</label><input type="date" name="event_date" required/></div>
      <div class="form-group"><label>Time</label><input type="time" name="event_time" required/></div>
      <div class="form-group"><label>Location</label><input type="text" name="event_location" required placeholder="e.g. Main Gym"/></div>
      <div class="form-group"><label>Max Attendees</label><input type="number" name="max_attendees" value="50" min="1" max="500"/></div>
      <div class="form-group"><label>Fee (₱)</label><input type="number" name="event_fee" value="0" min="0" step="0.01"/></div>
      <div class="form-group">
        <label>Organizer / Trainer</label>
        <select name="organizer_id" id="eventOrganizerSelect">
          <option value="">Select Organizer</option>
        </select>
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>Description</label>
        <textarea name="event_description" placeholder="Describe the event…"></textarea>
      </div>
      <div class="form-group">
        <label style="flex-direction:row;gap:8px;display:flex;align-items:center;cursor:pointer;">
          <input type="checkbox" name="is_members_only" value="1" style="width:auto;padding:0;"/> Members Only Event
        </label>
      </div>
    </div>
    <div class="form-submit-row"><button type="submit">Create Event</button></div>
  </form>
</div>

<p class="section-title">Upcoming Events</p>
<div class="grid" id="upcoming-events-grid">
  <div class="loading"><div class="spinner"></div> Loading…</div>
</div>
