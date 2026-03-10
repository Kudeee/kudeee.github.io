<?php
/**
 * POST /api/admin/bookings/update.php
 *
 * Updates the status of a class or trainer booking.
 *
 * Request (POST, form-data):
 *   csrf_token    string   required
 *   booking_id    int      required
 *   booking_type  string   required: class | trainer
 *   status        string   required: confirmed | cancelled | completed | no_show
 *   reason        string   required if status = cancelled
 *
 * Response 200:
 *   { "success": true, "message": "Booking updated." }
 *
 * DB tables used:
 *   class_bookings, class_schedules, trainer_bookings, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$booking_id   = sanitize_int($_POST['booking_id']   ?? 0);
$booking_type = sanitize_string($_POST['booking_type'] ?? '');
$status       = sanitize_string($_POST['status']    ?? '');
$reason       = sanitize_string($_POST['reason']    ?? '');

$valid_types   = ['class', 'trainer'];
$valid_statuses = ['confirmed', 'cancelled', 'completed', 'no_show'];

if (!$booking_id || $booking_id < 1)                        error('A valid booking ID is required.');
if (!in_array($booking_type, $valid_types, true))           error('Invalid booking type.');
if (!in_array($status, $valid_statuses, true))              error('Invalid status.');
if ($status === 'cancelled' && !$reason)                    error('A cancellation reason is required.');

// ─── TODO: replace stub with real DB update ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if ($booking_type === 'class') {
        $stmt = $pdo->prepare('SELECT * FROM class_bookings WHERE id = ? LIMIT 1');
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) error('Class booking not found.', 404);

        $pdo->prepare("UPDATE class_bookings SET status = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$status, $booking_id]);

        // Adjust participant count when cancelling
        if ($status === 'cancelled' && $booking['status'] === 'confirmed') {
            $pdo->prepare("UPDATE class_schedules SET current_participants = GREATEST(0, current_participants - 1) WHERE id = ?")
                ->execute([$booking['class_schedule_id']]);
            // TODO: issue refund
        }
    } else {
        $stmt = $pdo->prepare('SELECT * FROM trainer_bookings WHERE id = ? LIMIT 1');
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) error('Trainer booking not found.', 404);

        $pdo->prepare("UPDATE trainer_bookings SET status = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$status, $booking_id]);

        // TODO: issue refund if cancelled
    }

    $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, notes, created_at)
        VALUES (?, 'update_booking', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $booking_type . '_booking', $booking_id, $reason ?: null]);

    success('Booking updated successfully.');
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
