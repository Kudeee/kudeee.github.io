<?php
/**
 * config.php
 * Society Fitness — Shared configuration & helper functions.
 * Place this file at /api/config.php
 *
 * Database connection is intentionally NOT opened here yet.
 * Replace the constants below with your XAMPP credentials when ready.
 */

// ─── Database credentials (fill in when connecting) ──────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'society_fitness');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

// ─── App settings ─────────────────────────────────────────────────────────────
define('APP_NAME',    'Society Fitness');
define('SESSION_NAME','sf_session');

// ─── Session bootstrap ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Generate a CSRF token once per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Response helpers ─────────────────────────────────────────────────────────

/**
 * Send a JSON response and exit.
 *
 * @param bool   $success
 * @param string $message   Human-readable message
 * @param array  $data      Extra payload merged into root of response
 * @param int    $httpCode
 */
function json_response(bool $success, string $message = '', array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $data
    ));
    exit;
}

function success(string $message = 'OK', array $data = [], int $code = 200): void {
    json_response(true, $message, $data, $code);
}

function error(string $message = 'An error occurred.', int $code = 400): void {
    json_response(false, $message, [], $code);
}

// ─── CSRF validation ──────────────────────────────────────────────────────────

/**
 * Abort with 403 if the CSRF token in the POST body doesn't match the session token.
 */
function require_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        error('Invalid or missing CSRF token.', 403);
    }
}

// ─── Auth helpers ─────────────────────────────────────────────────────────────

/**
 * Abort with 401 if no authenticated member session exists.
 * Returns the current member's session data.
 */
function require_member(): array {
    if (empty($_SESSION['member_id'])) {
        error('You must be logged in to access this resource.', 401);
    }
    return [
        'member_id'   => (int) $_SESSION['member_id'],
        'name'        => $_SESSION['member_name']  ?? '',
        'email'       => $_SESSION['member_email'] ?? '',
        'plan'        => $_SESSION['member_plan']  ?? '',
        'role'        => $_SESSION['role']         ?? 'member',
    ];
}

/**
 * Returns true if a valid member session is active.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['member_id']);
}

// ─── Input sanitisation helpers ───────────────────────────────────────────────

function sanitize_string(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function sanitize_email(string $value): string|false {
    return filter_var(trim($value), FILTER_VALIDATE_EMAIL);
}

function sanitize_int(mixed $value): int|false {
    return filter_var($value, FILTER_VALIDATE_INT);
}

// ─── Method guard ─────────────────────────────────────────────────────────────

/**
 * Abort if the HTTP method is not one of the allowed methods.
 * Usage: require_method('POST');  or  require_method('GET', 'POST');
 */
function require_method(string ...$methods): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $methods));
        error('Method not allowed.', 405);
    }
}
