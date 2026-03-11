<?php
// Prevent double session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Database Configuration ───────────────────────────────────────────────────
if (!defined('DB_HOST'))    define('DB_HOST',    'localhost');
if (!defined('DB_NAME'))    define('DB_NAME',    'society_fitness');
if (!defined('DB_USER'))    define('DB_USER',    'root');    // ← change if needed
if (!defined('DB_PASS'))    define('DB_PASS',    '');        // ← change if needed
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ─── PDO Singleton ────────────────────────────────────────────────────────────
if (!function_exists('db')) {
    function db(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return $pdo;
    }
}

// ─── Response Helpers ─────────────────────────────────────────────────────────
if (!function_exists('json_response')) {
    function json_response(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('success')) {
    function success(string $message, array $data = []): void {
        json_response(array_merge(['success' => true, 'message' => $message], $data));
    }
}

if (!function_exists('error')) {
    function error(string $message, int $status = 400): void {
        json_response(['success' => false, 'message' => $message], $status);
    }
}

// ─── Input Helpers ────────────────────────────────────────────────────────────
if (!function_exists('require_method')) {
    function require_method(string $method): void {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            error('Method not allowed.', 405);
        }
    }
}

if (!function_exists('sanitize_string')) {
    function sanitize_string(string $val): string {
        return trim(strip_tags($val));
    }
}

if (!function_exists('sanitize_int')) {
    function sanitize_int($val): int {
        return (int) $val;
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email(string $val): string {
        return filter_var(trim($val), FILTER_SANITIZE_EMAIL) ?: '';
    }
}

// ─── Member Auth Helpers ──────────────────────────────────────────────────────
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return isset($_SESSION['member_id']);
    }
}

if (!function_exists('require_member')) {
    function require_member(): array {
        if (!isset($_SESSION['member_id'])) {
            error('Not authenticated. Please log in.', 401);
        }
        return [
            'id'    => $_SESSION['member_id'],
            'email' => $_SESSION['member_email'] ?? '',
            'plan'  => $_SESSION['member_plan']  ?? '',
            'name'  => $_SESSION['member_name']  ?? '',
        ];
    }
}

// ─── Admin Auth Helpers ───────────────────────────────────────────────────────
if (!function_exists('require_admin')) {
    function require_admin(array $roles = []): array {
        if (!isset($_SESSION['admin_id'])) {
            error('Unauthorized. Admin login required.', 401);
        }
        if (!empty($roles) && !in_array($_SESSION['admin_role'] ?? '', $roles)) {
            error('Forbidden. Insufficient permissions.', 403);
        }
        return [
            'id'   => $_SESSION['admin_id'],
            'role' => $_SESSION['admin_role'] ?? '',
            'name' => $_SESSION['admin_name'] ?? '',
        ];
    }
}

if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in(): bool {
        return isset($_SESSION['admin_id']);
    }
}

// ─── Pagination Helper ────────────────────────────────────────────────────────
if (!function_exists('get_pagination')) {
    function get_pagination(int $total, int $page, int $per_page): array {
        $total_pages = max(1, (int) ceil($total / $per_page));
        $page        = max(1, min($page, $total_pages));
        $offset      = ($page - 1) * $per_page;
        return [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => $total_pages,
            'offset'      => $offset,
        ];
    }
}