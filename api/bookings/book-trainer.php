<?php
/**
 * POST /api/bookings/book-trainer.php
 *
 * Books a personal training session for the authenticated member.
 *
 * Request (POST, form-data):
 *   csrf_token          string   required
 *   trainer_name        string   required
 *   trainer_specialty   string   required
 *   session_duration    string   required  e.g. "60 Min"
 *   session_minutes     int      required  30 | 60 | 90
 *   price_multiplier    float    required  1 | 1.5 | 2
 *   focus_area          string   required
 *   booking_date        string   required  ISO "YYYY-MM-DD"
 *   booking_time        string   required  e.g. "9:00 AM"
 *   total_price         float    required
 *   fitness_goals       string   optional
 *   fitness_level       string   required  ("beginner"|"intermediate"|"advanced")
 *   medical_info        string   optional
 *   recurring           string   optional  "1" = weekly recurring
 *   payment_method      string   required  ("gcash"|"maya"|"gotyme"|"card")
 *   card_number         string   optional
 *   card_expiry         string   optional
 *   card_cvv            string   optional
 *
 * Response 200:
 *   { "success": true, "message": "Session booked!", "booking_id": 42 }
 *
 * Response 400 / 401 / 409:
 *   { "success": false, "message": "..." }
 *
 * DB tables used (when connected):
 *   trainer_bookings  (id, member_id, trainer_id, session_duration, session_minutes,
 *                      price_multiplier, focus_area, booking_date, booking_time,
 *                      total_price, fitness_goals, fitness_level, medical_info,
 *                      recurring, payment_method, status, created_at)
 *   trainers          (id, name, specialty, session_rate, status)
 *   payments          (id, member_id, type, amount, method, reference_id, status, created_at)
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

$member = require_member();

// ─── Input ────────────────────────────────────────────────────────────────────

$trainer_name      = sanitize_string($_POST['trainer_name']       ?? '');
$trainer_specialty = sanitize_string($_POST['trainer_specialty']  ?? '');
$session_duration  = sanitize_string($_POST['session_duration']   ?? '');
$session_minutes   = (int)($_POST['session_minutes']              ?? 0);
$price_multiplier  = (float)($_POST['price_multiplier']           ?? 1);
$focus_area        = sanitize_string($_POST['focus_area']         ?? '');
$booking_date      = sanitize_string($_POST['booking_date']       ?? '');
$booking_time      = sanitize_string($_POST['booking_time']       ?? '');
$total_price       = (float)($_POST['total_price']                ?? 0);
$fitness_goals     = sanitize_string($_POST['fitness_goals']      ?? '');
$fitness_level     = sanitize_string($_POST['fitness_level']      ?? '');
$medical_info      = sanitize_string($_POST['medical_info']       ?? '');
$recurring         = ($_POST['recurring'] ?? '') === '1';
$payment_method    = sanitize_string($_POST['payment_method']     ?? '');

// ─── Validation ───────────────────────────────────────────────────────────────

if (!$trainer_name)     { error('Trainer name is required.'); }
if (!$session_duration) { error('Session duration is required.'); }
if (!in_array($session_minutes, [30, 60, 90], true)) { error('Invalid session duration.'); }
if (!$focus_area)       { error('Focus area is required.'); }
if (!$booking_date)     { error('Booking date is required.'); }
if (!$booking_time)     { error('Booking time is required.'); }
if ($total_price <= 0)  { error('Invalid session price.'); }

$date_ts = strtotime($booking_date);
if ($date_ts === false || $date_ts < strtotime('today')) {
    error('Please select a valid future date.');
}

$allowed_levels = ['beginner', 'intermediate', 'advanced'];
if (!in_array($fitness_level, $allowed_levels, true)) {
    error('Please select your fitness level.');
}

$allowed_methods = ['gcash', 'maya', 'gotyme', 'card'];
if (!in_array($payment_method, $allowed_methods, true)) {
    error('Please select a payment method.');
}

if ($payment_method === 'card') {
    $card_number = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
    $card_expiry = sanitize_string($_POST['card_expiry'] ?? '');
    $card_cvv    = sanitize_string($_POST['card_cvv']    ?? '');

    if (!preg_match('/^\d{15,16}$/', $card_number)) { error('Invalid card number.'); }
    if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) { error('Invalid expiry date.'); }
    if (!preg_match('/^\d{3,4}$/', $card_cvv)) { error('Invalid CVV.'); }
}

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Resolve trainer by name
    $stmt = $pdo->prepare('SELECT id, status FROM trainers WHERE name = ? LIMIT 1');
    $stmt->execute([$trainer_name]);
    $trainer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trainer) { error('Trainer not found.', 404); }
    if ($trainer['status'] !== 'active') { error('This trainer is not currently available.', 409); }

    // Check for overlapping booking
    $stmt = $pdo->prepare('
        SELECT id FROM trainer_bookings
        WHERE trainer_id = ?
          AND booking_date = ?
          AND booking_time = ?
          AND status != "cancelled"
        LIMIT 1
    ');
    $stmt->execute([$trainer['id'], $booking_date, $booking_time]);
    if ($stmt->fetch()) {
        error('This time slot is already taken. Please choose a different time.', 409);
    }

    $pdo->beginTransaction();

    // Insert booking
    $stmt = $pdo->prepare('
        INSERT INTO trainer_bookings
            (member_id, trainer_id, session_duration, session_minutes, price_multiplier,
             focus_area, booking_date, booking_time, total_price,
             fitness_goals, fitness_level, medical_info, recurring,
             payment_method, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "confirmed", NOW())
    ');
    $stmt->execute([
        $member['member_id'], $trainer['id'], $session_duration, $session_minutes,
        $price_multiplier, $focus_area, $booking_date, $booking_time, $total_price,
        $fitness_goals, $fitness_level, $medical_info, (int)$recurring, $payment_method
    ]);
    $booking_id = (int) $pdo->lastInsertId();

    // Record payment
    $pdo->prepare('
        INSERT INTO payments
            (member_id, type, amount, method, reference_id, status, created_at)
        VALUES (?, "trainer_session", ?, ?, ?, "completed", NOW())
    ')->execute([$member['member_id'], $total_price, $payment_method, $booking_id]);

    $pdo->commit();

    // TODO: notify trainer and send member confirmation email

    success('Session booked!', ['booking_id' => $booking_id]);
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
