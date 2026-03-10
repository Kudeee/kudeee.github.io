<?php
/**
 * POST /api/bookings/book-class.php
 *
 * Books a group class for the authenticated member.
 *
 * Request (POST, form-data):
 *   csrf_token          string   required
 *   class_name          string   required  e.g. "HIIT Training"
 *   booking_date        string   required  ISO date "YYYY-MM-DD"
 *   booking_time        string   required  e.g. "9:00 AM"
 *   special_requirements string  optional
 *   emergency_name      string   optional
 *   emergency_phone     string   optional
 *   payment_method      string   required  ("gcash"|"maya"|"gotyme"|"card")
 *   card_number         string   optional  (if payment_method = card)
 *   card_expiry         string   optional
 *   card_cvv            string   optional
 *
 * Response 200:
 *   { "success": true, "message": "Class booked successfully!", "booking_id": 123 }
 *
 * Response 400 / 401 / 409:
 *   { "success": false, "message": "..." }
 *
 * DB tables used (when connected):
 *   class_bookings  (id, member_id, class_schedule_id, booking_date, booking_time,
 *                    class_name, special_requirements, emergency_name, emergency_phone,
 *                    payment_method, status, created_at)
 *   class_schedules (id, class_name, trainer_id, scheduled_at, duration_minutes,
 *                    max_participants, current_participants, location)
 *   payments        (id, member_id, type, amount, method, reference_id, status, created_at)
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

$member = require_member();

// ─── Input ────────────────────────────────────────────────────────────────────

$class_name     = sanitize_string($_POST['class_name']           ?? '');
$booking_date   = sanitize_string($_POST['booking_date']         ?? '');
$booking_time   = sanitize_string($_POST['booking_time']         ?? '');
$special_req    = sanitize_string($_POST['special_requirements'] ?? '');
$emerg_name     = sanitize_string($_POST['emergency_name']       ?? '');
$emerg_phone    = sanitize_string($_POST['emergency_phone']      ?? '');
$payment_method = sanitize_string($_POST['payment_method']       ?? '');

// ─── Validation ───────────────────────────────────────────────────────────────

if (!$class_name)   { error('Class name is required.'); }
if (!$booking_date) { error('Booking date is required.'); }
if (!$booking_time) { error('Booking time is required.'); }

// Validate date is not in the past
$date_ts = strtotime($booking_date);
if ($date_ts === false) { error('Invalid booking date format.'); }
if ($date_ts < strtotime('today')) { error('You cannot book a class in the past.'); }

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

    // Find the matching class schedule slot
    $stmt = $pdo->prepare('
        SELECT id, max_participants, current_participants, trainer_id
        FROM class_schedules
        WHERE class_name = ?
          AND DATE(scheduled_at) = ?
          AND TIME_FORMAT(scheduled_at, "%h:%i %p") = ?
        LIMIT 1
    ');
    $stmt->execute([$class_name, $booking_date, $booking_time]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        error('The selected class slot could not be found.', 404);
    }

    if ($schedule['current_participants'] >= $schedule['max_participants']) {
        error('This class is fully booked. Please choose another time.', 409);
    }

    // Check for duplicate booking by same member
    $stmt = $pdo->prepare('
        SELECT id FROM class_bookings
        WHERE member_id = ? AND class_schedule_id = ? AND status != "cancelled"
        LIMIT 1
    ');
    $stmt->execute([$member['member_id'], $schedule['id']]);
    if ($stmt->fetch()) {
        error('You have already booked this class.', 409);
    }

    $pdo->beginTransaction();

    // Determine booking fee (₱0 if class is included in plan)
    $booking_fee = 0; // Adjust per plan logic
    $payment_status = 'completed';

    // Insert booking
    $stmt = $pdo->prepare('
        INSERT INTO class_bookings
            (member_id, class_schedule_id, booking_date, booking_time,
             class_name, special_requirements, emergency_name, emergency_phone,
             payment_method, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "confirmed", NOW())
    ');
    $stmt->execute([
        $member['member_id'], $schedule['id'], $booking_date, $booking_time,
        $class_name, $special_req, $emerg_name, $emerg_phone, $payment_method
    ]);
    $booking_id = (int) $pdo->lastInsertId();

    // Increment class participant count
    $pdo->prepare('
        UPDATE class_schedules
        SET current_participants = current_participants + 1
        WHERE id = ?
    ')->execute([$schedule['id']]);

    // Record payment (even if ₱0 — for audit)
    $pdo->prepare('
        INSERT INTO payments
            (member_id, type, amount, method, reference_id, status, created_at)
        VALUES (?, "class_booking", ?, ?, ?, ?, NOW())
    ')->execute([$member['member_id'], $booking_fee, $payment_method, $booking_id, $payment_status]);

    $pdo->commit();

    // TODO: send confirmation email to member

    success('Class booked successfully!', ['booking_id' => $booking_id]);
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
