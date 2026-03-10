<?php
/**
 * POST /api/user/membership/pause.php
 *
 * Pauses or resumes the authenticated member's active subscription.
 * Members may pause up to 3 months per calendar year.
 *
 * Request (POST, form-data):
 *   csrf_token  string   required
 *   action      string   required  "pause" | "resume"
 *
 * Response 200:
 *   { "success": true, "message": "Membership paused." }
 *
 * DB tables used (when connected):
 *   subscriptions  (id, member_id, status, paused_at, resumed_at, pause_count_days)
 *   members        (id, status)
 */

require_once __DIR__ . '/../../config.php';
require_method('POST');
require_csrf();

$member = require_member();

$action = sanitize_string($_POST['action'] ?? '');
if (!in_array($action, ['pause', 'resume'], true)) {
    error('Invalid action. Use "pause" or "resume".');
}

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('
        SELECT id, status, paused_at, pause_count_days
        FROM subscriptions
        WHERE member_id = ? AND status IN ("active", "paused")
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$member['member_id']]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub) { error('No active subscription found.', 404); }

    if ($action === 'pause') {
        if ($sub['status'] === 'paused') { error('Your membership is already paused.'); }

        // Enforce 90-day-per-year pause limit
        if ((int)$sub['pause_count_days'] >= 90) {
            error('You have reached the maximum pause duration (90 days per year).');
        }

        $pdo->prepare('
            UPDATE subscriptions
            SET status = "paused", paused_at = NOW()
            WHERE id = ?
        ')->execute([$sub['id']]);

        success('Membership paused successfully.');
    } else { // resume
        if ($sub['status'] !== 'paused') { error('Your membership is not currently paused.'); }

        // Calculate days paused and extend expiry accordingly
        $days_paused = (int) ceil((time() - strtotime($sub['paused_at'])) / 86400);

        $pdo->prepare('
            UPDATE subscriptions
            SET status          = "active",
                resumed_at      = NOW(),
                pause_count_days = pause_count_days + ?,
                expiry_date     = DATE_ADD(expiry_date, INTERVAL ? DAY)
            WHERE id = ?
        ')->execute([$days_paused, $days_paused, $sub['id']]);

        success('Membership resumed. Your expiry date has been extended by ' . $days_paused . ' day(s).');
    }
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
