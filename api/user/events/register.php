<?php
require_once __DIR__ . '/../../config.php';
require_method('POST');

$member   = require_member();
$pdo      = db();

$event_id       = sanitize_int($_POST['event_id']        ?? 0);
$payment_method = sanitize_string($_POST['payment_method'] ?? '');

if (!$event_id) {
    error('event_id is required.');
}

// Get event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    error('Event not found or is no longer active.');
}

// Check members-only
if ($event['is_members_only']) {
    // Already a logged-in member so fine
}

// Check capacity
if ($event['current_attendees'] >= $event['max_attendees']) {
    error('This event is fully booked.');
}

// Check duplicate registration
$stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND member_id = ? LIMIT 1");
$stmt->execute([$event_id, $member['id']]);
if ($stmt->fetch()) {
    error('You are already registered for this event.');
}

$fee = (float)$event['fee'];

// Require payment method if event has a fee
if ($fee > 0 && !$payment_method) {
    error('A payment method is required for paid events.');
}

// Insert registration
$stmt = $pdo->prepare("
    INSERT INTO event_registrations (event_id, member_id, payment_method, amount_paid, status)
    VALUES (?, ?, ?, ?, 'registered')
");
$stmt->execute([$event_id, $member['id'], $payment_method ?: null, $fee]);
$reg_id = (int)$pdo->lastInsertId();

// Increment attendee count
$pdo->prepare("UPDATE events SET current_attendees = current_attendees + 1 WHERE id = ?")
    ->execute([$event_id]);

// Record payment if paid
if ($fee > 0 && $payment_method) {
    $txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $stmt   = $pdo->prepare("
        INSERT INTO payments (member_id, type, amount, method, transaction_id, reference_id, status, description)
        VALUES (?, 'event', ?, ?, ?, ?, 'completed', ?)
    ");
    $stmt->execute([
        $member['id'], $fee, $payment_method, $txn_id,
        $reg_id, 'Event registration: ' . $event['name'],
    ]);
}

success('Successfully registered for event.', ['registration_id' => $reg_id]);