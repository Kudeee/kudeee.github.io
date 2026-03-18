<?php
require_once __DIR__ . '/../../../config.php';

$member = require_member();
$pdo    = db();

// Ensure is_recurring column exists
try {
    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS is_recurring TINYINT(1) NOT NULL DEFAULT 1");
} catch (\Throwable $e) {}

// Get member details
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, plan, billing_cycle, status, join_date FROM members WHERE id = ? LIMIT 1");
$stmt->execute([$member['id']]);
$memberRow = $stmt->fetch();

// Get active subscription (including is_recurring)
$stmt = $pdo->prepare("
    SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_remaining
    FROM subscriptions
    WHERE member_id = ? AND status IN ('active', 'paused')
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$member['id']]);
$subscription = $stmt->fetch();

// Normalize is_recurring: default to 1 if not set or column didn't exist yet
if ($subscription && !isset($subscription['is_recurring'])) {
    $subscription['is_recurring'] = 1;
}
if ($subscription) {
    $subscription['is_recurring'] = (int)($subscription['is_recurring'] ?? 1);
}

success('OK', [
    'member'       => $memberRow,
    'subscription' => $subscription,
]);