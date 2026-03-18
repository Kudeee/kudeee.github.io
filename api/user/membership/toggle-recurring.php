<?php
/**
 * api/user/membership/toggle-recurring.php
 *
 * Toggles the `is_recurring` flag on the member's active subscription.
 * When recurring = 1, the subscription will auto-renew at expiry.
 * When recurring = 0, it will simply expire and the member is prompted to renew.
 *
 * Method: POST
 * Body (JSON or form-data):
 *   recurring  — int (0 or 1)
 */

require_once __DIR__ . '/../../../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

// Accept JSON or form POST
$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$recurring = isset($body['recurring']) ? (int)$body['recurring'] : (int)($_POST['recurring'] ?? 0);

if (!in_array($recurring, [0, 1])) {
    error('recurring must be 0 or 1.');
}

// Ensure column exists (run once, non-fatal)
try {
    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS is_recurring TINYINT(1) NOT NULL DEFAULT 1");
} catch (\Throwable $e) { /* column may already exist */ }

// Find active subscription
$stmt = $pdo->prepare("
    SELECT id FROM subscriptions
    WHERE member_id = ? AND status = 'active'
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$member['id']]);
$sub = $stmt->fetch();

if (!$sub) {
    error('No active subscription found.');
}

$pdo->prepare("UPDATE subscriptions SET is_recurring = ? WHERE id = ?")
    ->execute([$recurring, $sub['id']]);

// Also update member row if you store it there (optional convenience field)
try {
    $pdo->exec("ALTER TABLE members ADD COLUMN IF NOT EXISTS subscription_recurring TINYINT(1) NOT NULL DEFAULT 1");
    $pdo->prepare("UPDATE members SET subscription_recurring = ? WHERE id = ?")
        ->execute([$recurring, $member['id']]);
} catch (\Throwable $e) { /* column may already exist */ }

$label = $recurring
    ? 'Auto-renew enabled. Your subscription will renew automatically.'
    : 'Auto-renew disabled. Your subscription will expire without automatic renewal.';

success($label, ['recurring' => $recurring, 'subscription_id' => $sub['id']]);