<?php
require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

// Get events the member has registered for (upcoming only)
$stmt = $pdo->prepare("
    SELECT e.id, e.name, e.type, e.event_date, e.event_time,
           e.location, e.fee, e.max_attendees, e.current_attendees,
           e.is_members_only, e.description, e.status,
           er.status AS registration_status,
           er.created_at AS registered_at,
           CONCAT(t.first_name, ' ', t.last_name) AS organizer_name,
           (e.max_attendees - e.current_attendees) AS spots_remaining
    FROM event_registrations er
    JOIN events e ON e.id = er.event_id
    LEFT JOIN trainers t ON t.id = e.organizer_id
    WHERE er.member_id = ?
      AND er.status = 'registered'
      AND e.status = 'active'
      AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC, e.event_time ASC
");
$stmt->execute([$member['id']]);
$events = $stmt->fetchAll();

success('OK', ['events' => $events]);