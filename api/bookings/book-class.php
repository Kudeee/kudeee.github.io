<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$class_schedule_id    = (int)($_POST['class_schedule_id']       ?? 0);
$class_name           = trim($_POST['class_name']               ?? '');
$booking_date         = trim($_POST['booking_date']             ?? '');
$booking_time         = trim($_POST['booking_time']             ?? '');
$special_requirements = trim($_POST['special_requirements']     ?? '');
$emergency_name       = trim($_POST['emergency_name']           ?? '');
$emergency_phone      = trim($_POST['emergency_phone']          ?? '');
$payment_method       = trim($_POST['payment_method']           ?? '');

// Validate required fields
if (!$class_name || !$booking_date || !$booking_time) {
    error('Class name, date, and time are required.');
}
if (!$payment_method) {
    error('Please select a payment method.');
}

// Resolve a valid class_schedule_id — column is NOT NULL with FK constraint,
// so we MUST have a real row ID before inserting into class_bookings.
$schedule = null;

if ($class_schedule_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM class_schedules WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$class_schedule_id]);
    $schedule = $stmt->fetch();
    if (!$schedule) {
        error('The selected class schedule was not found or has been cancelled.');
    }
} else {
    // Match by class_name + date
    $stmt = $pdo->prepare("
        SELECT * FROM class_schedules
        WHERE class_name = ? AND DATE(scheduled_at) = ? AND status = 'active'
        ORDER BY scheduled_at ASC LIMIT 1
    ");
    $stmt->execute([$class_name, $booking_date]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        // Fallback: any upcoming schedule for this class type
        $stmt = $pdo->prepare("
            SELECT * FROM class_schedules
            WHERE class_name = ? AND scheduled_at >= NOW() AND status = 'active'
            ORDER BY scheduled_at ASC LIMIT 1
        ");
        $stmt->execute([$class_name]);
        $schedule = $stmt->fetch();
    }

    if (!$schedule) {
        error('No available schedule found for "' . $class_name . '". Please contact the gym or choose a different date.');
    }

    $class_schedule_id = (int)$schedule['id'];
}

// Check capacity
if ($schedule['current_participants'] >= $schedule['max_participants']) {
    error('Sorry, this class is fully booked.');
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

// Determine booking fee — fetch fresh from DB to be safe
$stmt = $pdo->prepare("SELECT plan FROM members WHERE id = ? LIMIT 1");
$stmt->execute([$member['id']]);
$member_row  = $stmt->fetch();
$member_plan = $member_row['plan'] ?? 'BASIC PLAN';
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
    $class_schedule_id,
    $booking_date,
    $booking_time,
    $class_name,
    $special_requirements ?: null,
    $emergency_name,
    $emergency_phone,
    $payment_method,
]);
$booking_id = (int)$pdo->lastInsertId();

// Increment participant count (non-fatal)
try {
    $pdo->prepare("UPDATE class_schedules SET current_participants = current_participants + 1 WHERE id = ?")
        ->execute([$class_schedule_id]);
} catch (PDOException $e) {}

// Record payment
$txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
try {
    $pdo->prepare("
        INSERT INTO payments (member_id, type, amount, method, transaction_id, reference_id, status, description)
        VALUES (?, 'class_booking', ?, ?, ?, ?, 'completed', ?)
    ")->execute([
        $member['id'],
        $booking_fee,
        $payment_method,
        $txn_id,
        $booking_id,
        "Class booking: {$class_name} on {$booking_date} at {$booking_time}",
    ]);
} catch (PDOException $e) {}

success('Class booked successfully!', [
    'booking_id'     => $booking_id,
    'booking_fee'    => $booking_fee,
    'transaction_id' => $txn_id,
]);