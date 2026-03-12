<?php
require_once __DIR__ . '/../../config.php';

// Explicit session check with clear error
if (!isset($_SESSION['member_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
    exit;
}

$member_id = (int) $_SESSION['member_id'];
$pdo = db();

try {
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
    $stmt->execute([$member_id]);
    $events = $stmt->fetchAll();

    success('OK', ['events' => $events]);

} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage());
}