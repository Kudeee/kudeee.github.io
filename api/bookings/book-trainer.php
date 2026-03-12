<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$trainer_id       = (int)($_POST['trainer_id']        ?? 0);
$trainer_name     = trim($_POST['trainer_name']        ?? '');
$booking_date     = trim($_POST['booking_date']        ?? '');
$booking_time     = trim($_POST['booking_time']        ?? '');
$session_duration = trim($_POST['session_duration']    ?? '60 Min');
$session_minutes  = (int)($_POST['session_minutes']    ?? 60);
$price_multiplier = (float)($_POST['price_multiplier'] ?? 2.0);
$focus_area       = trim($_POST['focus_area']          ?? '');
$fitness_goals    = trim($_POST['fitness_goals']       ?? '');
$fitness_level    = trim($_POST['fitness_level']       ?? 'beginner');
$medical_info     = trim($_POST['medical_info']        ?? '');
$recurring        = (int)($_POST['recurring']          ?? 0);
$payment_method   = trim($_POST['payment_method']      ?? '');
$total_price      = (float)($_POST['total_price']      ?? 0);

// Validate required fields
if (!$trainer_name || !$booking_date || !$booking_time) {
    error('Trainer name, date, and time are required.');
}
if (!$payment_method) {
    error('Please select a payment method.');
}

// Resolve trainer by ID or full name
$trainer = null;
if ($trainer_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM trainers WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$trainer_id]);
    $trainer = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM trainers
        WHERE CONCAT(first_name, ' ', last_name) = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$trainer_name]);
    $trainer = $stmt->fetch();
}

if (!$trainer) {
    error('Trainer not found or is currently unavailable.');
}
$trainer_id = (int)$trainer['id'];

// Check if time slot is already booked
$stmt = $pdo->prepare("
    SELECT id FROM trainer_bookings
    WHERE trainer_id = ? AND booking_date = ? AND booking_time = ? AND status = 'confirmed'
    LIMIT 1
");
$stmt->execute([$trainer_id, $booking_date, $booking_time]);
if ($stmt->fetch()) {
    error('This trainer is already booked at that time. Please choose a different slot.');
}

// Calculate price if not provided by client
if ($total_price <= 0) {
    // Map session_minutes to multiplier: 30min=1x, 60min=2x, 90min=3x
    if ($session_minutes <= 30) {
        $price_multiplier = 1.0;
        $session_duration = '30 Min';
    } elseif ($session_minutes <= 60) {
        $price_multiplier = 2.0;
        $session_duration = '60 Min';
    } else {
        $price_multiplier = 3.0;
        $session_duration = '90 Min';
    }
    $total_price = round((float)$trainer['session_rate'] * $price_multiplier, 2);
}

// Validate fitness_level ENUM
$valid_levels = ['beginner', 'intermediate', 'advanced'];
if (!in_array($fitness_level, $valid_levels)) {
    $fitness_level = 'beginner';
}

// Insert trainer booking
$stmt = $pdo->prepare("
    INSERT INTO trainer_bookings
        (member_id, trainer_id, session_duration, session_minutes, price_multiplier,
         focus_area, booking_date, booking_time, total_price,
         fitness_goals, fitness_level, medical_info, recurring, payment_method, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
");
$stmt->execute([
    $member['id'],
    $trainer_id,
    $session_duration,
    $session_minutes,
    $price_multiplier,
    $focus_area,
    $booking_date,
    $booking_time,
    $total_price,
    $fitness_goals ?: null,
    $fitness_level,
    $medical_info ?: null,
    $recurring ? 1 : 0,
    $payment_method,
]);
$booking_id = (int)$pdo->lastInsertId();

// Record payment
$txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
$trainer_full_name = $trainer['first_name'] . ' ' . $trainer['last_name'];
try {
    $pdo->prepare("
        INSERT INTO payments (member_id, type, amount, method, transaction_id, reference_id, status, description)
        VALUES (?, 'trainer_session', ?, ?, ?, ?, 'completed', ?)
    ")->execute([
        $member['id'],
        $total_price,
        $payment_method,
        $txn_id,
        $booking_id,
        "PT session — {$trainer_full_name} ({$session_duration}) on {$booking_date}",
    ]);
} catch (PDOException $e) {}

success('Trainer session booked successfully!', [
    'booking_id'     => $booking_id,
    'trainer_name'   => $trainer_full_name,
    'total_price'    => $total_price,
    'transaction_id' => $txn_id,
]);