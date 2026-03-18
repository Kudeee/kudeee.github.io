<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$booking_type = sanitize_string($_POST['type']       ?? '');  // 'class' or 'trainer'
$booking_id   = sanitize_int($_POST['booking_id']    ?? 0);

if (!in_array($booking_type, ['class', 'trainer'])) {
    error('Invalid booking type. Use "class" or "trainer".');
}
if (!$booking_id) {
    error('booking_id is required.');
}

if ($booking_type === 'class') {
    // Get the booking
    $stmt = $pdo->prepare("
        SELECT cb.*, cs.scheduled_at
        FROM class_bookings cb
        LEFT JOIN class_schedules cs ON cs.id = cb.class_schedule_id
        WHERE cb.id = ? AND cb.member_id = ? AND cb.status = 'confirmed'
        LIMIT 1
    ");
    $stmt->execute([$booking_id, $member['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        error('Booking not found or already cancelled.');
    }

    // Enforce 24-hour cancellation rule (changed from 2 hours)
    if ($booking['scheduled_at']) {
        $classTime  = new DateTime($booking['scheduled_at']);
        $now        = new DateTime();
        $diff       = ($classTime->getTimestamp() - $now->getTimestamp()) / 3600;
        if ($diff < 24) {
            error('Cancellations must be made at least 24 hours before the class starts.');
        }
    }

    // Cancel booking
    $pdo->prepare("UPDATE class_bookings SET status = 'cancelled' WHERE id = ?")
        ->execute([$booking_id]);

    // Decrement participant count
    if ($booking['class_schedule_id']) {
        $pdo->prepare("UPDATE class_schedules SET current_participants = GREATEST(current_participants - 1, 0) WHERE id = ?")
            ->execute([$booking['class_schedule_id']]);
    }

    // ── Refund logic: issue a refund record if a payment exists ──────────────
    $payStmt = $pdo->prepare("
        SELECT id, amount, method
        FROM payments
        WHERE reference_id = ?
          AND type = 'class_booking'
          AND status = 'completed'
        LIMIT 1
    ");
    $payStmt->execute([$booking_id]);
    $payment = $payStmt->fetch();

    if ($payment && (float)$payment['amount'] > 0) {
        // Mark the original payment as refunded
        $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?")
            ->execute([$payment['id']]);

        // Insert a refund record so it appears in the member's payment history
        $refund_txn = 'REF-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $pdo->prepare("
            INSERT INTO payments
                (member_id, type, amount, method, transaction_id, reference_id, status, description, created_at)
            VALUES
                (?, 'refund', ?, ?, ?, ?, 'completed', ?, NOW())
        ")->execute([
            $member['id'],
            $payment['amount'],
            $payment['method'],
            $refund_txn,
            $booking_id,
            "Refund — member cancelled class booking (Booking #$booking_id)",
        ]);

        success('Class booking cancelled and payment refunded.', [
            'refund_amount' => $payment['amount'],
            'refund_txn'    => $refund_txn,
        ]);
    }

    success('Class booking cancelled successfully.');

} else {
    // ── Trainer booking cancellation ──────────────────────────────────────────

    $stmt = $pdo->prepare("
        SELECT * FROM trainer_bookings
        WHERE id = ? AND member_id = ? AND status = 'confirmed'
        LIMIT 1
    ");
    $stmt->execute([$booking_id, $member['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        error('Booking not found or already cancelled.');
    }

    // Enforce 24-hour cancellation rule
    $sessionDT  = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
    $now        = new DateTime();
    $diff       = ($sessionDT->getTimestamp() - $now->getTimestamp()) / 3600;
    if ($diff < 24) {
        error('Trainer session cancellations must be made at least 24 hours in advance.');
    }

    // Cancel the booking
    $pdo->prepare("UPDATE trainer_bookings SET status = 'cancelled' WHERE id = ?")
        ->execute([$booking_id]);

    // ── Refund logic: issue a refund record for the member ────────────────────
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
            $member['id'],
            $payment['amount'],
            $payment['method'],
            $refund_txn,
            $booking_id,
            "Refund — member cancelled trainer session (Booking #$booking_id)",
        ]);

        success('Trainer booking cancelled and payment refunded.', [
            'refund_amount' => $payment['amount'],
            'refund_txn'    => $refund_txn,
        ]);
    }

    success('Trainer booking cancelled successfully.');
}