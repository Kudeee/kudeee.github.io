<?php
/**
 * api/user/membership/cancel.php
 *
 * Cancels the authenticated member's active subscription.
 * Sets the subscription status to 'cancelled' and marks
 * the member status as 'suspended' (access continues until expiry).
 *
 * Method: POST
 * Params (form-data):
 *   reason  — string (e.g. 'too_expensive', 'not_using', 'other', …)
 *   note    — string (optional free-text, used when reason = 'other')
 */

require_once __DIR__ . '/../../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$reason = sanitize_string($_POST['reason'] ?? '');
$note   = sanitize_string($_POST['note']   ?? '');

// ── Fetch the active subscription ────────────────────────────────────────────

$stmt = $pdo->prepare("
    SELECT id, plan, billing_cycle, expiry_date, status
    FROM subscriptions
    WHERE member_id = ?
      AND status IN ('active', 'paused')
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$member['id']]);
$sub = $stmt->fetch();

if (!$sub) {
    error('No active subscription found. Your membership may already be cancelled.');
}

// ── Cancel the subscription ───────────────────────────────────────────────────

$pdo->prepare("
    UPDATE subscriptions
    SET status = 'cancelled',
        cancelled_at = NOW(),
        cancel_reason = ?,
        cancel_note   = ?
    WHERE id = ?
")->execute([$reason, $note ?: null, $sub['id']]);

// ── Mark member status — keep access until expiry ────────────────────────────
// We set status to 'suspended' so the member can still log in and view
// their history, but staff know the account is scheduled to close.

$pdo->prepare("
    UPDATE members
    SET status = 'suspended'
    WHERE id = ?
")->execute([$member['id']]);

// ── Optional audit log entry (non-fatal) ────────────────────────────────────

try {
    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, created_at)
        VALUES (NULL, 'member_cancelled', 'subscription', ?, NOW())
    ")->execute([$sub['id']]);
} catch (\Throwable $e) {
    // audit_log table may not exist — safe to ignore
}

// ── Respond ──────────────────────────────────────────────────────────────────

success('Membership cancelled successfully.', [
    'expiry_date'    => $sub['expiry_date'],
    'plan'           => $sub['plan'],
    'cancel_reason'  => $reason,
]);
