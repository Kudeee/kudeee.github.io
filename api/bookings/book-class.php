<?php
/**
 * POST /api/bookings/book-class.php
 * Books a group class for the authenticated member.
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

$member = require_member();

$class_name     = sanitize_string($_POST['class_name']           ?? '');
$booking_date   = sanitize_string($_POST['booking_date']         ?? '');
$booking_time   = sanitize_string($_POST['booking_time']         ?? '');
$special_req    = sanitize_string($_POST['special_requirements'] ?? '');
$emerg_name     = sanitize_string($_POST['emergency_name']       ?? '');
$emerg_phone    = sanitize_string($_POST['emergency_phone']      ?? '');
$payment_method = sanitize_string($_POST['payment_method']       ?? '');

if (!$class_name)   { error('Class name is required.'); }
if (!$booking_date) { error('Booking date is required.'); }
if (!$booking_time) { error('Booking time is required.'); }

$date_ts = strtotime($booking_date);
if ($date_ts === false)              { error('Invalid booking date format.'); }
if ($date_ts < strtotime('today'))   { error('You cannot book a class in the past.'); }

$allowed_methods = ['gcash', 'maya', 'gotyme', 'card'];
if (!in_array($payment_method, $allowed_methods, true)) { error('Please select a payment method.'); }

if ($payment_method === 'card') {
    $card_number = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
    $card_expiry = sanitize_string($_POST['card_expiry'] ?? '');
    $card_cvv    = sanitize_string($_POST['card_cvv']    ?? '');
    if (!preg_match('/^\d{15,16}$/', $card_number)) { error('Invalid card number.'); }
    if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) { error('Invalid expiry date.'); }
    if (!preg_match('/^\d{3,4}$/', $card_cvv)) { error('Invalid CVV.'); }
}

try {
    $pdo = db();

    // Find matching class schedule slot
    $stmt = $pdo->prepare("
        SELECT cs.id, cs.max_participants, cs.current_participants, cs.scheduled_at
        FROM class_schedules cs
        WHERE cs.class_name = ?
          AND DATE(cs.scheduled_at) = ?
          AND DATE_FORMAT(cs.scheduled_at, '%l:%i %p') = ?
          AND cs.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$class_name, $booking_date, $booking_time]);
    $schedule = $stmt->fetch();

    if (!$schedule) { error('The selected class slot could not be found.', 404); }

    if ($schedule['current_participants'] >= $schedule['max_participants']) {
        error('This class is fully booked. Please choose another time.', 409);
    }

    // Duplicate booking check
    $stmt = $pdo->prepare("
        SELECT id FROM class_bookings
        WHERE member_id = ? AND class_schedule_id = ? AND status != 'cancelled'
        LIMIT 1
    ");
    $stmt->execute([$member['member_id'], $schedule['id']]);
    if ($stmt->fetch()) { error('You have already booked this class.', 409); }

    // Booking fee: Basic plan pays ₱200, Premium/VIP is free
    $plan = strtoupper($member['plan'] ?? '');
    $booking_fee = ($plan === 'BASIC PLAN') ? 200 : 0;

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO class_bookings
            (member_id, class_schedule_id, booking_date, booking_time,
             class_name, special_requirements, emergency_name, emergency_phone,
             payment_method, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "confirmed", NOW())
    ');
    $stmt->execute([
        $member['member_id'], $schedule['id'], $booking_date, $booking_time,
        $class_name, $special_req, $emerg_name, $emerg_phone, $payment_method,
    ]);
    $booking_id = (int)$pdo->lastInsertId();

    $pdo->prepare('
        UPDATE class_schedules
        SET current_participants = current_participants + 1
        WHERE id = ?
    ')->execute([$schedule['id']]);

    $pdo->prepare('
        INSERT INTO payments
            (member_id, type, amount, method, reference_id, status, created_at)
        VALUES (?, "class_booking", ?, ?, ?, "completed", NOW())
    ')->execute([$member['member_id'], $booking_fee, $payment_method, $booking_id]);

    $pdo->commit();

    success('Class booked successfully!', ['booking_id' => $booking_id]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error('A database error occurred. Please try again.', 500);
}