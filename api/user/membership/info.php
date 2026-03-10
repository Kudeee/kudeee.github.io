<?php
/**
 * GET /api/user/membership/info.php
 * Returns the authenticated member's current membership details.
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$member = require_member();

try {
    $pdo = db();

    $stmt = $pdo->prepare('
        SELECT id, first_name, last_name, email, phone, join_date
        FROM members
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->execute([$member['member_id']]);
    $member_row = $stmt->fetch();

    if (!$member_row) { error('Member not found.', 404); }

    $stmt = $pdo->prepare('
        SELECT plan, billing_cycle, price, start_date, expiry_date, status
        FROM subscriptions
        WHERE member_id = ? AND status IN ("active", "paused")
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$member['member_id']]);
    $sub = $stmt->fetch();

    $days_remaining = $sub
        ? max(0, (int)ceil((strtotime($sub['expiry_date']) - time()) / 86400))
        : 0;

    success('OK', [
        'member'       => $member_row,
        'subscription' => $sub
            ? array_merge($sub, ['days_remaining' => $days_remaining])
            : null,
    ]);

} catch (PDOException $e) {
    error('A database error occurred. Please try again.', 500);
}