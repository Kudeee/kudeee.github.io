<?php
require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

$trainer_id = sanitize_int($_GET['trainer_id'] ?? 0);
$date       = sanitize_string($_GET['date']    ?? date('Y-m-d'));

if (!$trainer_id) {
    error('trainer_id is required.');
}

// All possible time slots
$all_slots = [
    '6:00 AM', '8:00 AM', '10:00 AM', '12:00 PM',
    '2:00 PM', '4:00 PM', '6:00 PM',  '8:00 PM',
];

// Get booked slots for this trainer on this date
$stmt = $pdo->prepare("
    SELECT booking_time FROM trainer_bookings
    WHERE trainer_id = ? AND booking_date = ? AND status = 'confirmed'
");
$stmt->execute([$trainer_id, $date]);
$booked = array_column($stmt->fetchAll(), 'booking_time');

$slots = [];
foreach ($all_slots as $slot) {
    $slots[] = [
        'time'      => $slot,
        'available' => !in_array($slot, $booked),
    ];
}

success('OK', ['slots' => $slots, 'booked' => $booked]);