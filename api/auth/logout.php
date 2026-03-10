<?php
/**
 * POST /api/auth/logout.php
 *
 * Destroys the current member session and redirects to the login page.
 *
 * Request (POST, form-data):
 *   csrf_token  string  required
 *
 * Response 200:
 *   { "success": true, "message": "Logged out successfully." }
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

// Destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

success('Logged out successfully.');
