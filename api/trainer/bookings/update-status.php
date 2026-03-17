<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
require_method('POST');
$trainer = require_trainer();
$tid     = $trainer['id'];

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$booking_id = sanitize_int($body['booking_id'] ?? 0);
$status     = sanitize_string($body['status']  ?? '');

if (!$booking_id) error('booking_id is required.');
if (!in_array($status, ['confirmed', 'cancelled', 'completed'])) error('Invalid status.');

$pdo = db();

// Ensure booking belongs to this trainer
$check = $pdo->prepare("SELECT id, status, member_id FROM trainer_bookings WHERE id = ? AND trainer_id = ? LIMIT 1");
$check->execute([$booking_id, $tid]);
$booking = $check->fetch();
if (!$booking) error('Booking not found.', 404);

// Update booking status
$pdo->prepare("UPDATE trainer_bookings SET status = ? WHERE id = ?")
    ->execute([$status, $booking_id]);

// ── Refund logic: when trainer cancels, refund the member's payment ───────────
if ($status === 'cancelled') {
    // Find the completed payment linked to this booking
    $payStmt = $pdo->prepare("
        SELECT id, amount, method
        FROM payments
        WHERE reference_id = ?
          AND type = 'trainer_session'
          AND status = 'completed'
        LIMIT 1
    ");
    $payStmt->execute([$booking_id]);
    $payment = $payStmt->fetch();

    if ($payment) {
        // Mark the original payment as refunded
        $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?")
            ->execute([$payment['id']]);

        // Insert a separate refund record so it shows in payment history
        $refund_txn = 'REF-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $pdo->prepare("
            INSERT INTO payments
                (member_id, type, amount, method, transaction_id, reference_id, status, description, created_at)
            VALUES
                (?, 'refund', ?, ?, ?, ?, 'completed', ?, NOW())
        ")->execute([
            $booking['member_id'],
            $payment['amount'],
            $payment['method'],
            $refund_txn,
            $booking_id,
            "Refund — trainer cancelled session (Booking #$booking_id)",
        ]);

        success('Booking cancelled and payment refunded.', [
            'booking_id'    => $booking_id,
            'status'        => $status,
            'refund_amount' => $payment['amount'],
            'refund_txn'    => $refund_txn,
        ]);
    }
}

// No payment found, or status wasn't cancelled — just return success
success('Booking status updated.', ['booking_id' => $booking_id, 'status' => $status]);