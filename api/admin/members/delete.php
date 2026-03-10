<?php
/**
 * POST /api/admin/members/delete.php
 *
 * Suspends or permanently deletes a member account.
 * Deletion is restricted to super_admin role; standard admins can only suspend.
 *
 * Request (POST, form-data):
 *   csrf_token  string   required
 *   member_id   int      required
 *   action      string   required: suspend | unsuspend | delete
 *   reason      string   required for suspend/delete — reason for the action
 *
 * Response 200:
 *   { "success": true, "message": "Member suspended." }
 *
 * DB tables used:
 *   members, subscriptions, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$member_id = sanitize_int($_POST['member_id'] ?? 0);
$action    = sanitize_string($_POST['action'] ?? '');
$reason    = sanitize_string($_POST['reason'] ?? '');

if (!$member_id || $member_id < 1)            error('A valid member ID is required.');
if (!in_array($action, ['suspend','unsuspend','delete'], true)) error('Invalid action.');
if (in_array($action, ['suspend','delete'], true) && !$reason) error('A reason is required.');

// Only super_admin can permanently delete
if ($action === 'delete' && !is_super_admin()) {
    error('Only super admins can permanently delete member accounts.', 403);
}

// Prevent admin from acting on themselves
if ((int) $_SESSION['member_id'] === $member_id) {
    error('You cannot perform this action on your own account.', 403);
}

// ─── TODO: replace stub with real DB operation ────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('SELECT id, status FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) error('Member not found.', 404);

    if ($action === 'suspend') {
        $pdo->prepare("UPDATE members SET status = 'suspended', updated_at = NOW() WHERE id = ?")
            ->execute([$member_id]);
        // Cancel active subscription
        $pdo->prepare("UPDATE subscriptions SET status = 'cancelled', updated_at = NOW()
                        WHERE member_id = ? AND status = 'active'")
            ->execute([$member_id]);
        $msg = 'Member suspended.';
    } elseif ($action === 'unsuspend') {
        $pdo->prepare("UPDATE members SET status = 'active', updated_at = NOW() WHERE id = ?")
            ->execute([$member_id]);
        $msg = 'Member unsuspended.';
    } else {
        // delete — cascade handled by FK constraints; soft-delete recommended
        $pdo->prepare("UPDATE members SET status = 'deleted', email = CONCAT(email, '_deleted_', NOW()), updated_at = NOW() WHERE id = ?")
            ->execute([$member_id]);
        $msg = 'Member account deleted.';
    }

    $pdo->prepare("
        INSERT INTO admin_logs
            (admin_id, action, target_type, target_id, notes, created_at)
        VALUES (?, ?, 'member', ?, ?, NOW())
    ")->execute([$admin['admin_id'], $action . '_member', $member_id, $reason]);

    success($msg);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
