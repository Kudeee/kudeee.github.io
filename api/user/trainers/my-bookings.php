<?php
/**
 * api/user/trainers/my-bookings.php
 *
 * Returns the authenticated member's confirmed upcoming (and recent past)
 * trainer bookings, joined with trainer details.
 *
 * Method: GET
 * Optional query params:
 *   status   — 'confirmed' | 'cancelled' | 'all'   (default: 'confirmed')
 *   upcoming — '1' to limit to future bookings only (default: '0' = show all)
 */

require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

$status   = sanitize_string($_GET['status']   ?? 'confirmed');
$upcoming = sanitize_int($_GET['upcoming']     ?? 0);

$where  = ['tb.member_id = ?'];
$params = [$member['id']];

if ($status !== 'all') {
    $where[]  = 'tb.status = ?';
    $params[] = $status;
}

if ($upcoming) {
    $where[] = "(tb.booking_date > CURDATE() OR (tb.booking_date = CURDATE() AND tb.booking_time >= CURTIME()))";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT
        tb.id                                           AS booking_id,
        tb.booking_date,
        tb.booking_time,
        tb.session_duration,
        tb.session_minutes,
        tb.total_price,
        tb.focus_area,
        tb.fitness_level,
        tb.recurring,
        tb.status,
        tb.created_at                                   AS booked_at,
        t.id                                            AS trainer_id,
        CONCAT(t.first_name, ' ', t.last_name)         AS trainer_name,
        t.specialty,
        t.image_url,
        t.rating,
        t.session_rate
    FROM trainer_bookings tb
    JOIN trainers t ON t.id = tb.trainer_id
    $where_sql
    ORDER BY tb.booking_date ASC, tb.booking_time ASC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Attach human-friendly labels
foreach ($bookings as &$b) {
    // Date label
    $date    = new DateTime($b['booking_date']);
    $today   = new DateTime('today');
    $tomorrow = new DateTime('tomorrow');

    if ($date == $today) {
        $b['date_label'] = 'Today';
    } elseif ($date == $tomorrow) {
        $b['date_label'] = 'Tomorrow';
    } else {
        $b['date_label'] = $date->format('M j, Y');
    }

    // Time label
    $b['time_label'] = date('g:i A', strtotime($b['booking_time']));

    // Focus area label
    $focusMap = [
        'weight_loss'      => 'Weight Loss',
        'muscle_building'  => 'Muscle Building',
        'strength_training'=> 'Strength Training',
        'flexibility'      => 'Flexibility',
        'endurance'        => 'Endurance',
        'general_fitness'  => 'General Fitness',
    ];
    $b['focus_label'] = $focusMap[$b['focus_area']] ?? ucfirst(str_replace('_', ' ', $b['focus_area'] ?? ''));

    $b['total_price'] = (float) $b['total_price'];
    $b['rating']      = (float) $b['rating'];
}

success('Trainer bookings retrieved.', ['bookings' => $bookings]);