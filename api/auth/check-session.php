<?php
/**
 * GET /api/auth/check-session.php
 *
 * Returns the logged-in member's basic session data.
 * Used by JS to verify a live session without a full page reload.
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "member_id": 1,
 *     "name": "Ben Dover",
 *     "email": "ben@email.com",
 *     "plan": "PREMIUM PLAN",
 *     "role": "member"
 *   }
 *
 * Response 401:
 *   { "success": false, "message": "Not authenticated." }
 */

require_once __DIR__ . '/../config.php';
require_method('GET');

if (!is_logged_in()) {
    error('Not authenticated.', 401);
}

success('Authenticated.', [
    'member_id' => (int) $_SESSION['member_id'],
    'name'      => $_SESSION['member_name']  ?? '',
    'email'     => $_SESSION['member_email'] ?? '',
    'plan'      => $_SESSION['member_plan']  ?? '',
    'role'      => $_SESSION['role']         ?? 'member',
]);
