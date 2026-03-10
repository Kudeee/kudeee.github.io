<?php
/**
 * POST /api/admin/members/delete.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$member_id = sanitize_int($_POST['member_id'] ?? 0);
$action    = sanitize_string($_POST['action'] ?? '');
$reason    = sanitize_string($_POST['reason'] ?? '');

if (!$member_id || $member_id < 1) error('A valid member ID is required.');
if (!in_array($action, ['suspend', 'unsuspend', 'delete'], true)) error('Invalid action.');
if (in_array($action, ['suspend', 'delete'], true) && !$reason) error('A reason is required.');
if ($action === 'delete' && !is_super_admin()) error('Only super admins can permanently delete member accounts.', 403);
if ((int) $_SESSION['member_id'] === $member_id) error('You cannot perform this action on your own account.', 403);

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, status FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    if (!$member) error('Member not found.', 404);

    $pdo->beginTransaction();

    if ($action === 'suspend') {
        $pdo->prepare("UPDATE members SET status = 'suspended' WHERE id = ?")->execute([$member_id]);
        $pdo->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE member_id = ? AND status = 'active'")->execute([$member_id]);
        $msg = 'Member suspended.';
    } elseif ($action === 'unsuspend') {
        $pdo->prepare("UPDATE members SET status = 'active' WHERE id = ?")->execute([$member_id]);
        $msg = 'Member unsuspended.';
    } else {
        // Soft delete
        $pdo->prepare("UPDATE members SET status = 'deleted', email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP()) WHERE id = ?")->execute([$member_id]);
        $msg = 'Member account deleted.';
    }

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, ?, 'member', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $action . '_member', $member_id, json_encode(['reason' => $reason]), $_SERVER['REMOTE_ADDR'] ?? '']);

    $pdo->commit();
    success($msg);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Database error: ' . $e->getMessage(), 500);
}