<?php
session_start();

// ─── Database connection ───────────────────────────────────────────────────
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $host   = 'localhost';
        $dbname = 'society_fitness';
        $user   = 'root';
        $pass   = '';
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
            exit;
        }
    }
    return $pdo;
}

// ─── Response helpers ──────────────────────────────────────────────────────
function json_response(array $data, int $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function success(string $message = 'OK', array $extra = []) {
    json_response(array_merge(['success' => true, 'message' => $message], $extra));
}

function error(string $message, int $status = 400) {
    json_response(['success' => false, 'message' => $message], $status);
}

// ─── Auth helpers ──────────────────────────────────────────────────────────
function is_logged_in(): bool {
    return isset($_SESSION['member_id']);
}

function require_member(): array {
    if (!is_logged_in()) {
        error('Unauthorized. Please log in.', 401);
    }
    return [
        'id'    => $_SESSION['member_id'],
        'email' => $_SESSION['member_email'] ?? '',
        'plan'  => $_SESSION['member_plan']  ?? '',
        'name'  => $_SESSION['member_name']  ?? '',
    ];
}

function require_method(string $method) {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        error('Method not allowed.', 405);
    }
}

// ─── Sanitizers ────────────────────────────────────────────────────────────
function sanitize_string(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function sanitize_email(string $val): string {
    return filter_var(trim($val), FILTER_SANITIZE_EMAIL);
}

function sanitize_int($val): int {
    return (int) $val;
}

// ─── CORS for local dev ────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');