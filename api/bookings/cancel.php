<?php
/**
 * POST /api/bookings/cancel.php
 * Cancels a class or trainer booking for the authenticated member.
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

$member = require_member();

$booking_id   = sanitize_int($_POST['booking_id']    ?? 0);
$booking_type = sanitize_string($_POST['booking_type'] ?? '');

if (!$booking_id || $booking_id <= 0)                      { error('Invalid booking ID.'); }
if (!in_array($booking_type, ['class', 'trainer'], true))  { error('Invalid booking type.'); }

try {
    $pdo = db();

    if ($booking_type === 'class') {

        $stmt = $pdo->prepare('
            SELECT cb.id, cb.member_id, cb.status, cb.class_schedule_id,
                   cs.scheduled_at
            FROM class_bookings cb
            JOIN class_schedules cs ON cs.id = cb.class_schedule_id
            WHERE cb.id = ?
            LIMIT 1
        ');
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if (!$booking)                                  { error('Booking not found.', 404); }
        if ((int)$booking['member_id'] !== $member['member_id']) { error('Forbidden.', 403); }
        if ($booking['status'] === 'cancelled')         { error('This booking is already cancelled.'); }

        // 2-hour cancellation window
        if (strtotime($booking['scheduled_at']) - time() < 7200) {
            error('Classes cannot be cancelled within 2 hours of start time.');
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE class_bookings SET status = 'cancelled' WHERE id = ?")
            ->execute([$booking_id]);

        $pdo->prepare('
            UPDATE class_schedules
            SET current_participants = GREATEST(current_participants - 1, 0)
            WHERE id = ?
        ')->execute([$booking['class_schedule_id']]);

        $pdo->commit();

    } else {

        $stmt = $pdo->prepare('
            SELECT id, member_id, status, booking_date, booking_time
            FROM trainer_bookings
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if (!$booking)                                  { error('Booking not found.', 404); }
        if ((int)$booking['member_id'] !== $member['member_id']) { error('Forbidden.', 403); }
        if ($booking['status'] === 'cancelled')         { error('This booking is already cancelled.'); }

        // 24-hour cancellation window for trainer sessions
        $session_dt = strtotime($booking['booking_date'] . ' ' . $booking['booking_time']);
        if ($session_dt - time() < 86400) {
            error('Trainer sessions must be cancelled at least 24 hours in advance.');
        }

        $pdo->prepare("UPDATE trainer_bookings SET status = 'cancelled' WHERE id = ?")
            ->execute([$booking_id]);
    }

    success('Booking cancelled successfully.');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error('A database error occurred. Please try again.', 500);
}