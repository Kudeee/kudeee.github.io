<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$class_schedule_id    = sanitize_int($_POST['class_schedule_id']    ?? 0);
$class_name           = sanitize_string($_POST['class_name']        ?? '');
$booking_date         = sanitize_string($_POST['booking_date']      ?? '');
$booking_time         = sanitize_string($_POST['booking_time']      ?? '');
$special_requirements = sanitize_string($_POST['special_requirements'] ?? '');
$emergency_name       = sanitize_string($_POST['emergency_name']    ?? '');
$emergency_phone      = sanitize_string($_POST['emergency_phone']   ?? '');
$payment_method       = sanitize_string($_POST['payment_method']    ?? '');

if (!$class_name || !$booking_date || !$booking_time) {
    error('Class name, date, and time are required.');
}
if (!$payment_method) {
    error('Please select a payment method.');
}

// If a specific schedule ID was given, validate it
if ($class_schedule_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM class_schedules
        WHERE id = ? AND status = 'active' LIMIT 1
    ");
    $stmt->execute([$class_schedule_id]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        error('Class schedule not found or has been cancelled.');
    }

    // Check capacity
    if ($schedule['current_participants'] >= $schedule['max_participants']) {
        error('This class is fully booked.');
    }

    // Check duplicate booking
    $stmt = $pdo->prepare("
        SELECT id FROM class_bookings
        WHERE member_id = ? AND class_schedule_id = ? AND status = 'confirmed' LIMIT 1
    ");
    $stmt->execute([$member['id'], $class_schedule_id]);
    if ($stmt->fetch()) {
        error('You have already booked this class.');
    }
} else {
    // Loose booking — match by class_name + date + time
    $stmt = $pdo->prepare("
        SELECT * FROM class_schedules
        WHERE class_name = ?
          AND DATE(scheduled_at) = ?
          AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$class_name, $booking_date]);
    $schedule = $stmt->fetch();
    if ($schedule) {
        $class_schedule_id = $schedule['id'];
    }
}

// Determine booking fee based on plan
$member_plan = $_SESSION['member_plan'] ?? 'BASIC PLAN';
$booking_fee = ($member_plan === 'BASIC PLAN') ? 200.00 : 0.00;

// Insert booking
$stmt = $pdo->prepare("
    INSERT INTO class_bookings
        (member_id, class_schedule_id, booking_date, booking_time, class_name,
         special_requirements, emergency_name, emergency_phone, payment_method, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
");
$stmt->execute([
    $member['id'],
    $class_schedule_id ?: null,
    $booking_date,
    $booking_time,
    $class_name,
    $special_requirements,
    $emergency_name,
    $emergency_phone,
    $payment_method,
]);
$booking_id = (int)$pdo->lastInsertId();

// Update participant count if schedule exists
if ($class_schedule_id) {
    $pdo->prepare("UPDATE class_schedules SET current_participants = current_participants + 1 WHERE id = ?")
        ->execute([$class_schedule_id]);
}

// Record payment
$txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
$stmt   = $pdo->prepare("
    INSERT INTO payments (member_id, type, amount, method, transaction_id, reference_id, status, description)
    VALUES (?, 'class_booking', ?, ?, ?, ?, 'completed', ?)
");
$stmt->execute([
    $member['id'],
    $booking_fee,
    $payment_method,
    $txn_id,
    $booking_id,
    "Class booking: $class_name on $booking_date at $booking_time",
]);

success('Class booked successfully!', [
    'booking_id'  => $booking_id,
    'booking_fee' => $booking_fee,
    'transaction_id' => $txn_id,
]);