<?php
require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

$stmt = $pdo->prepare("
    SELECT e.*,
           CONCAT(t.first_name, ' ', t.last_name) AS organizer_name,
           (e.max_attendees - e.current_attendees) AS spots_remaining,
           CASE WHEN er.id IS NOT NULL THEN 1 ELSE 0 END AS already_registered
    FROM events e
    LEFT JOIN trainers t ON t.id = e.organizer_id
    LEFT JOIN event_registrations er ON er.event_id = e.id
        AND er.member_id = ? AND er.status = 'registered'
    WHERE e.status = 'active'
      AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC, e.event_time ASC
");
$stmt->execute([$member['id']]);
$events = $stmt->fetchAll();

success('OK', ['events' => $events]);