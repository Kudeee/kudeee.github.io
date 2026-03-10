<?php
/**
 * POST /api/admin/classes/cancel.php
 * Convenience wrapper: sets class status to cancelled.
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$class_id = sanitize_int($_POST['class_id'] ?? 0);
$reason   = sanitize_string($_POST['reason'] ?? 'Cancelled by admin');

if (!$class_id || $class_id < 1) error('A valid class ID is required.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, status FROM class_schedules WHERE id = ? LIMIT 1');
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    if (!$class) error('Class not found.', 404);
    if ($class['status'] === 'cancelled') error('Class is already cancelled.');

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE class_schedules SET status = 'cancelled' WHERE id = ?")->execute([$class_id]);

    $stmt = $pdo->prepare("UPDATE class_bookings SET status = 'cancelled' WHERE class_schedule_id = ? AND status = 'confirmed'");
    $stmt->execute([$class_id]);
    $bookings_cancelled = $stmt->rowCount();

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'class_cancelled', 'class', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $class_id, json_encode(['reason' => $reason, 'bookings_cancelled' => $bookings_cancelled]), $_SERVER['REMOTE_ADDR'] ?? '']);

    $pdo->commit();
    success('Class cancelled.', ['bookings_cancelled' => $bookings_cancelled]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Database error: ' . $e->getMessage(), 500);
}