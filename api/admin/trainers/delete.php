<?php
/**
 * POST /api/admin/trainers/delete.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$trainer_id = sanitize_int($_POST['trainer_id'] ?? 0);
$action     = sanitize_string($_POST['action']  ?? '');
$reason     = sanitize_string($_POST['reason']  ?? '');

if (!$trainer_id || $trainer_id < 1) error('A valid trainer ID is required.');
if (!in_array($action, ['deactivate', 'reactivate', 'delete'], true)) error('Invalid action.');
if (in_array($action, ['deactivate', 'delete'], true) && !$reason) error('A reason is required.');
if ($action === 'delete' && !is_super_admin()) error('Only super admins can permanently delete trainers.', 403);

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM trainers WHERE id = ? LIMIT 1');
    $stmt->execute([$trainer_id]);
    if (!$stmt->fetch()) error('Trainer not found.', 404);

    if ($action === 'deactivate') {
        $pdo->prepare("UPDATE trainers SET status = 'inactive' WHERE id = ?")->execute([$trainer_id]);
        $msg = 'Trainer deactivated.';
    } elseif ($action === 'reactivate') {
        $pdo->prepare("UPDATE trainers SET status = 'active' WHERE id = ?")->execute([$trainer_id]);
        $msg = 'Trainer reactivated.';
    } else {
        // Check for upcoming confirmed bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id = ? AND booking_date >= CURDATE() AND status = 'confirmed'");
        $stmt->execute([$trainer_id]);
        if ((int) $stmt->fetchColumn() > 0) {
            error('Cannot delete trainer with upcoming confirmed bookings. Deactivate them instead.', 409);
        }
        $pdo->prepare("UPDATE trainers SET status = 'inactive' WHERE id = ?")->execute([$trainer_id]);
        $msg = 'Trainer removed.';
    }

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, ?, 'trainer', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $action . '_trainer', $trainer_id, json_encode(['reason' => $reason]), $_SERVER['REMOTE_ADDR'] ?? '']);

    success($msg);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}