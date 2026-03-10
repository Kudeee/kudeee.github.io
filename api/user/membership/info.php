<?php
/**
 * GET /api/user/membership/info.php
 *
 * Returns the authenticated member's current membership details.
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "member": { id, first_name, last_name, email, phone, join_date },
 *     "subscription": {
 *       plan, billing_cycle, price,
 *       start_date, expiry_date, status,
 *       days_remaining
 *     }
 *   }
 *
 * DB tables used (when connected):
 *   members       (id, first_name, last_name, email, phone, join_date)
 *   subscriptions (id, member_id, plan, billing_cycle, price, start_date,
 *                  expiry_date, status)
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$member = require_member();

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('
        SELECT id, first_name, last_name, email, phone, join_date
        FROM members
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->execute([$member['member_id']]);
    $member_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member_row) { error('Member not found.', 404); }

    $stmt = $pdo->prepare('
        SELECT plan, billing_cycle, price, start_date, expiry_date, status
        FROM subscriptions
        WHERE member_id = ? AND status = "active"
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$member['member_id']]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    $days_remaining = $sub
        ? max(0, (int) ceil((strtotime($sub['expiry_date']) - time()) / 86400))
        : 0;

    success('OK', [
        'member'       => $member_row,
        'subscription' => $sub ? array_merge($sub, ['days_remaining' => $days_remaining]) : null,
    ]);
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
