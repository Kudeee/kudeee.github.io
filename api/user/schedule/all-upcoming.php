<?php
/**
 * api/user/schedule/all-upcoming.php
 *
 * Returns ALL upcoming confirmed bookings (class + trainer) for the
 * authenticated member, formatted for the homepage carousel.
 */
require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

// ── Upcoming class bookings ───────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        cb.id                                       AS booking_id,
        'class'                                     AS booking_type,
        cb.class_name,
        cb.booking_date,
        cb.booking_time,
        cb.status,
        CONCAT(t.first_name, ' ', t.last_name)      AS trainer_name,
        cs.duration_minutes,
        cs.scheduled_at,
        cb.class_schedule_id
    FROM class_bookings cb
    LEFT JOIN class_schedules cs ON cs.id = cb.class_schedule_id
    LEFT JOIN trainers t         ON t.id  = cs.trainer_id
    WHERE cb.member_id = ?
      AND cb.status    = 'confirmed'
      AND (
            cb.booking_date > CURDATE()
         OR (cb.booking_date = CURDATE() AND cb.booking_time >= CURTIME())
      )
    ORDER BY cb.booking_date ASC, cb.booking_time ASC
");
$stmt->execute([$member['id']]);
$classBookings = $stmt->fetchAll();

// ── Upcoming trainer bookings ─────────────────────────────────────────────────
$stmt2 = $pdo->prepare("
    SELECT
        tb.id                                       AS booking_id,
        'trainer'                                   AS booking_type,
        CONCAT('Session w/ ', t.first_name)         AS class_name,
        tb.booking_date,
        tb.booking_time,
        tb.status,
        CONCAT(t.first_name, ' ', t.last_name)      AS trainer_name,
        tb.session_minutes                           AS duration_minutes,
        NULL                                         AS scheduled_at,
        NULL                                         AS class_schedule_id
    FROM trainer_bookings tb
    JOIN trainers t ON t.id = tb.trainer_id
    WHERE tb.member_id = ?
      AND tb.status    = 'confirmed'
      AND (
            tb.booking_date > CURDATE()
         OR (tb.booking_date = CURDATE() AND tb.booking_time >= CURTIME())
      )
    ORDER BY tb.booking_date ASC, tb.booking_time ASC
");
$stmt2->execute([$member['id']]);
$trainerBookings = $stmt2->fetchAll();

// ── Merge and sort by date + time ─────────────────────────────────────────────
$all = array_merge($classBookings, $trainerBookings);

usort($all, function ($a, $b) {
    $tsA = strtotime($a['booking_date'] . ' ' . $a['booking_time']);
    $tsB = strtotime($b['booking_date'] . ' ' . $b['booking_time']);
    return $tsA <=> $tsB;
});

// ── Format labels for each booking ────────────────────────────────────────────
foreach ($all as &$b) {
    $bookingDate = new DateTime($b['booking_date']);
    $today       = new DateTime('today');
    $tomorrow    = new DateTime('tomorrow');

    if ($bookingDate == $today) {
        $b['date_label'] = 'Today';
    } elseif ($bookingDate == $tomorrow) {
        $b['date_label'] = 'Tomorrow';
    } else {
        $b['date_label'] = $bookingDate->format('M j, Y');
    }

    $b['time_label']     = date('g:i A', strtotime($b['booking_time']));
    $b['duration_label'] = $b['duration_minutes'] ? $b['duration_minutes'] . ' min' : '50 min';
}
unset($b);

success('OK', [
    'bookings' => $all,
    'count'    => count($all),
]);