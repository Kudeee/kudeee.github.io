<?php
/**
 * api/bookings/reschedule-trainer.php
 *
 * Reschedules a confirmed trainer booking to a new date/time,
 * but only if the trainer is available at the requested slot.
 *
 * Method : POST
 * Body   : booking_id   (int, required)
 *          new_date     (string YYYY-MM-DD, required)
 *          new_time     (string e.g. "10:00 AM", required)
 */

require_once __DIR__ . '/../../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$booking_id = sanitize_int($_POST['booking_id'] ?? 0);
$new_date   = sanitize_string($_POST['new_date']   ?? '');
$new_time   = sanitize_string($_POST['new_time']   ?? '');

if (!$booking_id) error('booking_id is required.');
if (!$new_date)   error('new_date is required.');
if (!$new_time)   error('new_time is required.');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date)) {
    error('Invalid date format. Use YYYY-MM-DD.');
}

// Ensure requested slot is in the future
$requestedDT = new DateTime($new_date . ' ' . $new_time, new DateTimeZone('Asia/Manila'));
$now         = new DateTime('now',                        new DateTimeZone('Asia/Manila'));
if ($requestedDT <= $now) {
    error('The requested date and time must be in the future.');
}

// Get the booking — must belong to this member and be confirmed
$stmt = $pdo->prepare("
    SELECT tb.*, t.first_name, t.last_name, t.id AS trainer_id
    FROM trainer_bookings tb
    JOIN trainers t ON t.id = tb.trainer_id
    WHERE tb.id = ? AND tb.member_id = ? AND tb.status = 'confirmed'
    LIMIT 1
");
$stmt->execute([$booking_id, $member['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    error('Booking not found or cannot be rescheduled (must be confirmed and belong to you).');
}

// Prevent rescheduling a booking within 24 hours of its current time
$currentDT = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time'], new DateTimeZone('Asia/Manila'));
$hoursUntilCurrent = ($currentDT->getTimestamp() - $now->getTimestamp()) / 3600;
if ($hoursUntilCurrent < 24) {
    error('Bookings cannot be rescheduled within 24 hours of the scheduled session time.');
}

$trainer_id = (int)$booking['trainer_id'];

// Check trainer availability — first check trainer_availability table
$time24 = date('H:i', strtotime($new_time));

// Check if trainer has explicitly blocked this slot
$availStmt = $pdo->prepare("
    SELECT is_open FROM trainer_availability
    WHERE trainer_id = ? AND slot_date = ? AND slot_time = ?
    LIMIT 1
");
$availStmt->execute([$trainer_id, $new_date, $time24]);
$availRow = $availStmt->fetch();

// If the trainer has explicitly blocked this slot, deny
if ($availRow && (int)$availRow['is_open'] === 0) {
    error('The trainer is not available at the selected time. Please choose a different slot.');
}

// Check if trainer already has a confirmed booking at the new date/time
// (excluding the current booking being rescheduled)
$conflictStmt = $pdo->prepare("
    SELECT id FROM trainer_bookings
    WHERE trainer_id = ?
      AND booking_date = ?
      AND booking_time = ?
      AND status = 'confirmed'
      AND id != ?
    LIMIT 1
");
$conflictStmt->execute([$trainer_id, $new_date, $new_time, $booking_id]);
if ($conflictStmt->fetch()) {
    error('The trainer already has a booking at that time. Please choose a different slot.');
}

// Make sure the new slot isn't the same as the existing one
if ($booking['booking_date'] === $new_date && $booking['booking_time'] === $new_time) {
    error('The new time is the same as the current booking. Please choose a different slot.');
}

// All checks passed — perform the reschedule
$old_date = $booking['booking_date'];
$old_time = $booking['booking_time'];

$pdo->prepare("
    UPDATE trainer_bookings
    SET booking_date = ?,
        booking_time = ?,
        rescheduled_at = NOW(),
        rescheduled_from_date = COALESCE(rescheduled_from_date, ?),
        rescheduled_from_time = COALESCE(rescheduled_from_time, ?)
    WHERE id = ?
")->execute([$new_date, $new_time, $old_date, $old_time, $booking_id]);

// Format new date for human-readable response
$newDTFormatted = $requestedDT->format('M j, Y') . ' at ' . $requestedDT->format('g:i A');

success('Session rescheduled successfully.', [
    'booking_id'  => $booking_id,
    'new_date'    => $new_date,
    'new_time'    => $new_time,
    'new_datetime_label' => $newDTFormatted,
    'trainer_name' => $booking['first_name'] . ' ' . $booking['last_name'],
]);