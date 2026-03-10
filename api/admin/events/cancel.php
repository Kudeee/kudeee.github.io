<?php
/**
 * POST /api/admin/events/cancel.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$event_id = sanitize_int($_POST['event_id'] ?? 0);
$reason   = sanitize_string($_POST['reason'] ?? 'Cancelled by admin');

if (!$event_id || $event_id < 1) error('A valid event ID is required.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, status FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    if (!$event) error('Event not found.', 404);
    if ($event['status'] === 'cancelled') error('Event is already cancelled.');

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?")->execute([$event_id]);

    $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE event_id = ? AND status = 'registered'");
    $stmt->execute([$event_id]);
    $registrations_cancelled = $stmt->rowCount();

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'event_cancelled', 'event', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $event_id, json_encode(['reason' => $reason, 'registrations_cancelled' => $registrations_cancelled]), $_SERVER['REMOTE_ADDR'] ?? '']);

    $pdo->commit();
    success('Event cancelled.', ['registrations_cancelled' => $registrations_cancelled]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Database error: ' . $e->getMessage(), 500);
}
