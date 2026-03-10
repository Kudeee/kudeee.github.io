<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$booking_type = sanitize_string($_POST['type']       ?? '');  // 'class' or 'trainer'
$booking_id   = sanitize_int($_POST['booking_id']    ?? 0);

if (!in_array($booking_type, ['class', 'trainer'])) {
    error('Invalid booking type. Use "class" or "trainer".');
}
if (!$booking_id) {
    error('booking_id is required.');
}

if ($booking_type === 'class') {
    // Get the booking
    $stmt = $pdo->prepare("
        SELECT cb.*, cs.scheduled_at
        FROM class_bookings cb
        LEFT JOIN class_schedules cs ON cs.id = cb.class_schedule_id
        WHERE cb.id = ? AND cb.member_id = ? AND cb.status = 'confirmed'
        LIMIT 1
    ");
    $stmt->execute([$booking_id, $member['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        error('Booking not found or already cancelled.');
    }

    // Enforce 2-hour cancellation rule
    if ($booking['scheduled_at']) {
        $classTime  = new DateTime($booking['scheduled_at']);
        $now        = new DateTime();
        $diff       = ($classTime->getTimestamp() - $now->getTimestamp()) / 3600; // hours
        if ($diff < 2) {
            error('Cancellations must be made at least 2 hours before the class starts.');
        }
    }

    // Cancel booking
    $pdo->prepare("UPDATE class_bookings SET status = 'cancelled' WHERE id = ?")
        ->execute([$booking_id]);

    // Decrement participant count
    if ($booking['class_schedule_id']) {
        $pdo->prepare("UPDATE class_schedules SET current_participants = GREATEST(current_participants - 1, 0) WHERE id = ?")
            ->execute([$booking['class_schedule_id']]);
    }

    success('Class booking cancelled successfully.');

} else {
    // Trainer booking
    $stmt = $pdo->prepare("
        SELECT * FROM trainer_bookings
        WHERE id = ? AND member_id = ? AND status = 'confirmed'
        LIMIT 1
    ");
    $stmt->execute([$booking_id, $member['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        error('Booking not found or already cancelled.');
    }

    // Enforce 24-hour cancellation rule
    $sessionDT  = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
    $now        = new DateTime();
    $diff       = ($sessionDT->getTimestamp() - $now->getTimestamp()) / 3600; // hours
    if ($diff < 24) {
        error('Trainer session cancellations must be made at least 24 hours in advance.');
    }

    $pdo->prepare("UPDATE trainer_bookings SET status = 'cancelled' WHERE id = ?")
        ->execute([$booking_id]);

    success('Trainer booking cancelled successfully.');
}