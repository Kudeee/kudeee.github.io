<?php
/**
 * POST /api/user/events/register.php
 * Registers the authenticated member for an event.
 */

require_once __DIR__ . '/../../config.php';
require_method('POST');
require_csrf();

$member = require_member();

$event_id       = sanitize_int($_POST['event_id']         ?? 0);
$payment_method = sanitize_string($_POST['payment_method'] ?? '');

if (!$event_id || $event_id <= 0) { error('Invalid event ID.'); }

try {
    $pdo = db();

    $stmt = $pdo->prepare('
        SELECT id, name, fee, max_attendees, current_attendees,
               is_members_only, status, event_date
        FROM events
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event)                          { error('Event not found.', 404); }
    if ($event['status'] !== 'active')    { error('This event is no longer accepting registrations.'); }
    if (strtotime($event['event_date']) < strtotime('today')) { error('This event has already passed.'); }
    if ($event['current_attendees'] >= $event['max_attendees']) {
        error('This event is fully booked.', 409);
    }

    // Duplicate check
    $stmt = $pdo->prepare("
        SELECT id FROM event_registrations
        WHERE event_id = ? AND member_id = ? AND status = 'registered'
        LIMIT 1
    ");
    $stmt->execute([$event_id, $member['member_id']]);
    if ($stmt->fetch()) { error('You are already registered for this event.', 409); }

    $fee = (float)$event['fee'];
    if ($fee > 0) {
        $allowed_methods = ['gcash', 'maya', 'gotyme', 'card'];
        if (!in_array($payment_method, $allowed_methods, true)) {
            error('Please select a payment method for this paid event.');
        }
        if ($payment_method === 'card') {
            $card_number = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
            $card_expiry = sanitize_string($_POST['card_expiry'] ?? '');
            $card_cvv    = sanitize_string($_POST['card_cvv']    ?? '');
            if (!preg_match('/^\d{15,16}$/', $card_number)) { error('Invalid card number.'); }
            if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) { error('Invalid expiry date.'); }
            if (!preg_match('/^\d{3,4}$/', $card_cvv)) { error('Invalid CVV.'); }
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO event_registrations
            (event_id, member_id, payment_method, amount_paid, status, registered_at)
        VALUES (?, ?, ?, ?, "registered", NOW())
    ');
    $stmt->execute([$event_id, $member['member_id'], $payment_method ?: null, $fee]);
    $registration_id = (int)$pdo->lastInsertId();

    $pdo->prepare('
        UPDATE events SET current_attendees = current_attendees + 1 WHERE id = ?
    ')->execute([$event_id]);

    if ($fee > 0) {
        $pdo->prepare('
            INSERT INTO payments
                (member_id, type, amount, method, reference_id, status, created_at)
            VALUES (?, "event", ?, ?, ?, "completed", NOW())
        ')->execute([$member['member_id'], $fee, $payment_method, $registration_id]);
    }

    $pdo->commit();

    success('Registered successfully!', ['registration_id' => $registration_id]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error('A database error occurred. Please try again.', 500);
}