<?php
require_once __DIR__ . '/../../config.php';

// Kill ALL caching so browsers always fetch fresh data
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

$member = require_member();
$pdo    = db();

$trainer_id = sanitize_int($_GET['trainer_id'] ?? 0);
$date       = sanitize_string($_GET['date']    ?? date('Y-m-d'));

if (!$trainer_id) {
    error('trainer_id is required.');
}

$all_slots = [
    '6:00 AM', '8:00 AM', '10:00 AM', '12:00 PM',
    '2:00 PM', '4:00 PM', '6:00 PM',  '8:00 PM',
];

$slot_to_24h = [
    '6:00 AM'  => '06:00',
    '8:00 AM'  => '08:00',
    '10:00 AM' => '10:00',
    '12:00 PM' => '12:00',
    '2:00 PM'  => '14:00',
    '4:00 PM'  => '16:00',
    '6:00 PM'  => '18:00',
    '8:00 PM'  => '20:00',
];

// Get booked slots for this trainer on this date
$stmt = $pdo->prepare("
    SELECT booking_time FROM trainer_bookings
    WHERE trainer_id = ? AND booking_date = ? AND status = 'confirmed'
");
$stmt->execute([$trainer_id, $date]);
$booked = array_column($stmt->fetchAll(), 'booking_time');

$now   = new DateTime(); 
$slots = [];

foreach ($all_slots as $slot) {
    $time24  = $slot_to_24h[$slot] ?? null;
    $isPast  = false;

    if ($time24) {
        $slotDT = new DateTime($date . ' ' . $time24 . ':00');
        $isPast  = ($slotDT <= $now);
    }

    $isBooked = in_array($slot, $booked);

    $slots[] = [
        'time'      => $slot,
        'available' => !$isPast && !$isBooked,
        'past'      => $isPast,
        'booked'    => $isBooked,
    ];
}

success('OK', ['slots' => $slots, 'booked' => $booked, 'server_time' => $now->format('Y-m-d H:i:s')]);