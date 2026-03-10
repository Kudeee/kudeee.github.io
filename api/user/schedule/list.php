<?php
/**
 * GET /api/user/schedule/list.php
 * Returns the weekly class schedule, optionally filtered.
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$current_member_id = is_logged_in() ? (int)$_SESSION['member_id'] : null;

$class_type = sanitize_string($_GET['class_type'] ?? '');
$trainer    = sanitize_string($_GET['trainer']    ?? '');
$time_range = sanitize_string($_GET['time_range'] ?? '');
$date_from  = sanitize_string($_GET['date_from']  ?? date('Y-m-d'));
$date_to    = sanitize_string($_GET['date_to']    ?? date('Y-m-d', strtotime('+7 days')));

if (!strtotime($date_from)) { $date_from = date('Y-m-d'); }
if (!strtotime($date_to))   { $date_to   = date('Y-m-d', strtotime('+7 days')); }

$hour_min = 0; $hour_max = 23;
switch ($time_range) {
    case 'morning':   $hour_min = 5;  $hour_max = 11; break;
    case 'afternoon': $hour_min = 12; $hour_max = 16; break;
    case 'evening':   $hour_min = 17; $hour_max = 23; break;
}

try {
    $pdo = db();

    $conditions = [
        "cs.status = 'active'",
        "DATE(cs.scheduled_at) BETWEEN ? AND ?",
        "HOUR(cs.scheduled_at) BETWEEN ? AND ?",
    ];
    $params = [$date_from, $date_to, $hour_min, $hour_max];

    if ($class_type) {
        $conditions[] = 'cs.class_name = ?';
        $params[]     = $class_type;
    }
    if ($trainer) {
        $conditions[] = 't.name LIKE ?';
        $params[]     = '%' . $trainer . '%';
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $stmt = $pdo->prepare("
        SELECT
            cs.id,
            cs.class_name,
            t.name                                                 AS trainer_name,
            t.id                                                   AS trainer_id,
            cs.scheduled_at,
            cs.duration_minutes,
            cs.location,
            cs.max_participants,
            cs.current_participants,
            (cs.max_participants - cs.current_participants)        AS spots_left,
            (cs.current_participants >= cs.max_participants)       AS is_full
        FROM class_schedules cs
        JOIN trainers t ON t.id = cs.trainer_id
        $where
        ORDER BY cs.scheduled_at ASC
    ");
    $stmt->execute($params);
    $classes = $stmt->fetchAll();

    // Cast numeric booleans
    foreach ($classes as &$cls) {
        $cls['is_full']         = (bool)$cls['is_full'];
        $cls['is_booked_by_me'] = false;
    }
    unset($cls);

    // Mark which classes the current member already booked
    if ($current_member_id && count($classes)) {
        $ids          = array_column($classes, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $booked_stmt  = $pdo->prepare("
            SELECT class_schedule_id
            FROM class_bookings
            WHERE member_id = ? AND class_schedule_id IN ($placeholders) AND status = 'confirmed'
        ");
        $booked_stmt->execute(array_merge([$current_member_id], $ids));
        $booked_set = array_flip($booked_stmt->fetchAll(PDO::FETCH_COLUMN));

        foreach ($classes as &$cls) {
            $cls['is_booked_by_me'] = isset($booked_set[$cls['id']]);
        }
        unset($cls);
    }

    success('OK', ['classes' => $classes]);

} catch (PDOException $e) {
    error('A database error occurred. Please try again.', 500);
}