<?php
/**
 * POST /api/user/membership/pause.php
 * Pauses or resumes the authenticated member's active subscription.
 * Members may pause up to 90 days per calendar year.
 */

require_once __DIR__ . '/../../config.php';
require_method('POST');
require_csrf();

$member = require_member();

$action = sanitize_string($_POST['action'] ?? '');
if (!in_array($action, ['pause', 'resume'], true)) {
    error('Invalid action. Use "pause" or "resume".');
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('
        SELECT id, status, paused_at, pause_count_days, expiry_date
        FROM subscriptions
        WHERE member_id = ? AND status IN ("active", "paused")
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$member['member_id']]);
    $sub = $stmt->fetch();

    if (!$sub) { error('No active subscription found.', 404); }

    if ($action === 'pause') {
        if ($sub['status'] === 'paused') { error('Your membership is already paused.'); }

        if ((int)$sub['pause_count_days'] >= 90) {
            error('You have reached the maximum pause duration (90 days per year).');
        }

        $pdo->prepare('
            UPDATE subscriptions
            SET status = "paused", paused_at = NOW()
            WHERE id = ?
        ')->execute([$sub['id']]);

        success('Membership paused successfully.');

    } else {
        if ($sub['status'] !== 'paused') { error('Your membership is not currently paused.'); }

        $days_paused = (int)ceil((time() - strtotime($sub['paused_at'])) / 86400);

        $pdo->prepare('
            UPDATE subscriptions
            SET status            = "active",
                resumed_at        = NOW(),
                pause_count_days  = pause_count_days + ?,
                expiry_date       = DATE_ADD(expiry_date, INTERVAL ? DAY)
            WHERE id = ?
        ')->execute([$days_paused, $days_paused, $sub['id']]);

        success('Membership resumed. Your expiry date has been extended by ' . $days_paused . ' day(s).');
    }

} catch (PDOException $e) {
    error('A database error occurred. Please try again.', 500);
}