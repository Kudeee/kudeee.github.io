<?php
session_start();

// ─── Database Configuration ───────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'society_fitness');
define('DB_USER', 'root');       // Change to your DB user
define('DB_PASS', '');           // Change to your DB password
define('DB_CHARSET', 'utf8mb4');

// ─── PDO Singleton ────────────────────────────────────────────────────────────
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

// ─── Response Helpers ─────────────────────────────────────────────────────────
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function success(string $message, array $data = []): void {
    json_response(array_merge(['success' => true, 'message' => $message], $data));
}

function error(string $message, int $status = 400): void {
    json_response(['success' => false, 'message' => $message], $status);
}

// ─── Input Helpers ────────────────────────────────────────────────────────────
function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        error('Method not allowed.', 405);
    }
}

function sanitize_string(string $val): string {
    return trim(strip_tags($val));
}

function sanitize_int($val): int {
    return (int) $val;
}

function sanitize_email(string $val): string {
    return filter_var(trim($val), FILTER_SANITIZE_EMAIL) ?: '';
}

// ─── Admin Auth Helpers ───────────────────────────────────────────────────────
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

function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_id']);
}

// ─── Pagination Helper ────────────────────────────────────────────────────────
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
