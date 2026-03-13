<?php
/**
 * api/bookings/toggle-recurring.php
 *
 * Toggles the `recurring` flag (0 ↔ 1) on a confirmed trainer booking
 * that belongs to the authenticated member.
 *
 * Method : POST
 * Body   : booking_id  (int, required)
 *          recurring   (int, 0 or 1 — the NEW desired value)
 */

require_once __DIR__ . '/../../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$booking_id = sanitize_int($_POST['booking_id'] ?? 0);
$recurring  = sanitize_int($_POST['recurring']  ?? 0);   // 0 = not recurring, 1 = recurring

if (!$booking_id) error('booking_id is required.');
if (!in_array($recurring, [0, 1])) error('recurring must be 0 or 1.');

// Verify ownership and that it is still confirmed
$stmt = $pdo->prepare("
    SELECT id, recurring, status
    FROM trainer_bookings
    WHERE id = ? AND member_id = ? AND status = 'confirmed'
    LIMIT 1
");
$stmt->execute([$booking_id, $member['id']]);
$booking = $stmt->fetch();

if (!$booking) error('Booking not found or not eligible for update.', 404);

// Apply update
$pdo->prepare("UPDATE trainer_bookings SET recurring = ? WHERE id = ?")
    ->execute([$recurring, $booking_id]);

$label = $recurring ? 'Session set to repeat weekly.' : 'Session is no longer recurring.';
success($label, ['booking_id' => $booking_id, 'recurring' => $recurring]);