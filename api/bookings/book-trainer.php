<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$trainer_name     = sanitize_string($_POST['trainer_name']     ?? '');
$trainer_specialty= sanitize_string($_POST['trainer_specialty'] ?? '');
$session_duration = sanitize_string($_POST['session_duration'] ?? '');
$session_minutes  = sanitize_int($_POST['session_minutes']     ?? 30);
$price_multiplier = (float)($_POST['price_multiplier']         ?? 1.0);
$focus_area       = sanitize_string($_POST['focus_area']       ?? '');
$booking_date     = sanitize_string($_POST['booking_date']     ?? '');
$booking_time     = sanitize_string($_POST['booking_time']     ?? '');
$total_price      = (float)($_POST['total_price']              ?? 0);
$fitness_goals    = sanitize_string($_POST['fitness_goals']    ?? '');
$fitness_level    = sanitize_string($_POST['fitness_level']    ?? 'beginner');
$medical_info     = sanitize_string($_POST['medical_info']     ?? '');
$recurring        = sanitize_int($_POST['recurring']           ?? 0);
$payment_method   = sanitize_string($_POST['payment_method']   ?? '');

if (!$trainer_name || !$booking_date || !$booking_time) {
    error('Trainer, date, and time are required.');
}
if (!$payment_method) {
    error('Please select a payment method.');
}

// Validate allowed fitness levels
$allowed_levels = ['beginner', 'intermediate', 'advanced'];
if (!in_array($fitness_level, $allowed_levels)) {
    $fitness_level = 'beginner';
}

// Find trainer by full name
$stmt = $pdo->prepare("
    SELECT * FROM trainers
    WHERE CONCAT(first_name, ' ', last_name) = ? AND status = 'active'
    LIMIT 1
");
$stmt->execute([$trainer_name]);
$trainer = $stmt->fetch();

if (!$trainer) {
    error('Trainer not found.');
}

// Check slot availability
$stmt = $pdo->prepare("
    SELECT id FROM trainer_bookings
    WHERE trainer_id = ? AND booking_date = ? AND booking_time = ? AND status = 'confirmed'
    LIMIT 1
");
$stmt->execute([$trainer['id'], $booking_date, $booking_time]);
if ($stmt->fetch()) {
    error('This time slot is already booked. Please choose another time.');
}

// Calculate total price if not supplied
if ($total_price <= 0) {
    $total_price = round($trainer['session_rate'] * $price_multiplier, 2);
}

// Insert booking
$stmt = $pdo->prepare("
    INSERT INTO trainer_bookings
        (member_id, trainer_id, session_duration, session_minutes, price_multiplier,
         focus_area, booking_date, booking_time, total_price, fitness_goals, fitness_level,
         medical_info, recurring, payment_method, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
");
$stmt->execute([
    $member['id'],
    $trainer['id'],
    $session_duration,
    $session_minutes,
    $price_multiplier,
    $focus_area,
    $booking_date,
    $booking_time,
    $total_price,
    $fitness_goals,
    $fitness_level,
    $medical_info,
    $recurring,
    $payment_method,
]);
$booking_id = (int)$pdo->lastInsertId();

// Record payment
$txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
$stmt   = $pdo->prepare("
    INSERT INTO payments (member_id, type, amount, method, transaction_id, reference_id, status, description)
    VALUES (?, 'trainer_session', ?, ?, ?, ?, 'completed', ?)
");
$stmt->execute([
    $member['id'],
    $total_price,
    $payment_method,
    $txn_id,
    $booking_id,
    "Trainer session: $trainer_name — $session_duration on $booking_date at $booking_time",
]);

success('Trainer session booked successfully!', [
    'booking_id'     => $booking_id,
    'total_price'    => $total_price,
    'transaction_id' => $txn_id,
]);