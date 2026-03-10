<?php
require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

// Get member details
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, plan, billing_cycle, status, join_date FROM members WHERE id = ? LIMIT 1");
$stmt->execute([$member['id']]);
$memberRow = $stmt->fetch();

// Get active subscription
$stmt = $pdo->prepare("
    SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_remaining
    FROM subscriptions
    WHERE member_id = ? AND status IN ('active', 'paused')
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$member['id']]);
$subscription = $stmt->fetch();

success('OK', [
    'member'       => $memberRow,
    'subscription' => $subscription,
]);