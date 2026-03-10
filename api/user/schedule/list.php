<?php
require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

// Filters
$class_name = sanitize_string($_GET['class_name'] ?? '');
$date_from  = sanitize_string($_GET['date_from']  ?? date('Y-m-d'));
$date_to    = sanitize_string($_GET['date_to']    ?? date('Y-m-d', strtotime('+7 days')));
$trainer_id = sanitize_int($_GET['trainer_id']    ?? 0);

$where  = ["cs.status = 'active'", "cs.scheduled_at >= ?", "cs.scheduled_at <= ?"];
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];

if ($class_name) {
    $where[]  = "cs.class_name LIKE ?";
    $params[] = "%$class_name%";
}
if ($trainer_id) {
    $where[]  = "cs.trainer_id = ?";
    $params[] = $trainer_id;
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT cs.*,
           CONCAT(t.first_name, ' ', t.last_name) AS trainer_name,
           t.specialty AS trainer_specialty,
           t.image_url AS trainer_image,
           (cs.max_participants - cs.current_participants) AS spots_remaining,
           CASE WHEN cb.id IS NOT NULL THEN 1 ELSE 0 END AS already_booked
    FROM class_schedules cs
    LEFT JOIN trainers t ON t.id = cs.trainer_id
    LEFT JOIN class_bookings cb ON cb.class_schedule_id = cs.id
        AND cb.member_id = ? AND cb.status = 'confirmed'
    WHERE $whereSQL
    ORDER BY cs.scheduled_at ASC
");
$stmt->execute(array_merge([$member['id']], $params));
$classes = $stmt->fetchAll();

success('OK', ['classes' => $classes]);