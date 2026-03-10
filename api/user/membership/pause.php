<?php
require_once __DIR__ . '/../../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$action = sanitize_string($_POST['action'] ?? '');

if (!in_array($action, ['pause', 'resume'])) {
    error('Invalid action. Use "pause" or "resume".');
}

// Get active subscription
$stmt = $pdo->prepare("
    SELECT * FROM subscriptions
    WHERE member_id = ? AND status IN ('active', 'paused')
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$member['id']]);
$sub = $stmt->fetch();

if (!$sub) {
    error('No active subscription found.');
}

if ($action === 'pause') {
    if ($sub['status'] === 'paused') {
        error('Membership is already paused.');
    }

    // Check yearly pause limit (max 90 days)
    if (($sub['pause_count_days'] ?? 0) >= 90) {
        error('You have reached the maximum pause limit of 90 days per year.');
    }

    $stmt = $pdo->prepare("
        UPDATE subscriptions
        SET status = 'paused', paused_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$sub['id']]);

    success('Membership paused successfully.');

} elseif ($action === 'resume') {
    if ($sub['status'] !== 'paused') {
        error('Membership is not paused.');
    }

    // Calculate days paused
    $paused_at  = new DateTime($sub['paused_at']);
    $now        = new DateTime();
    $days_paused = (int)ceil(($now->getTimestamp() - $paused_at->getTimestamp()) / 86400);

    // Check yearly limit
    $total_days = ($sub['pause_count_days'] ?? 0) + $days_paused;
    if ($total_days > 90) {
        $days_paused = 90 - ($sub['pause_count_days'] ?? 0);
    }

    // Extend expiry date
    $new_expiry = date('Y-m-d', strtotime($sub['expiry_date'] . " +{$days_paused} days"));

    $stmt = $pdo->prepare("
        UPDATE subscriptions
        SET status = 'active',
            resumed_at = NOW(),
            pause_count_days = pause_count_days + ?,
            expiry_date = ?
        WHERE id = ?
    ");
    $stmt->execute([$days_paused, $new_expiry, $sub['id']]);

    success('Membership resumed. Expiry extended by ' . $days_paused . ' day(s).', [
        'new_expiry'  => $new_expiry,
        'days_added'  => $days_paused,
    ]);
}