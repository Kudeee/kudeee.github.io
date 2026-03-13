<?php
require_once __DIR__ . '/../config.php';

// Accept both GET and POST
$token = sanitize_string($_GET['token'] ?? $_POST['token'] ?? '');
if (!$token) error('Token is required.');

$pdo = db();

// Ensure table exists first
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    user_type  VARCHAR(20)  NOT NULL DEFAULT 'member',
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   DEFAULT 0,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    // First check if token exists at all (ignore expiry/used for debug)
    $stmtDebug = $pdo->prepare("SELECT email, expires_at, used, NOW() as server_now FROM password_resets WHERE token = ? LIMIT 1");
    $stmtDebug->execute([$token]);
    $debugRow = $stmtDebug->fetch();

    // Now check with full conditions
    $stmt = $pdo->prepare("
        SELECT email, expires_at, user_type FROM password_resets
        WHERE token = ? AND used = 0 AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

} catch (\Exception $e) {
    error('Database error: ' . $e->getMessage());
}

if (!$reset) {
    // Return debug info so we can see why it failed
    error('This reset link is invalid or has expired.', 400);
}

$maskedEmail = preg_replace('/(?<=.{2}).(?=.*@)/', '*', $reset['email']);
success('Token is valid.', [
    'email'      => $maskedEmail,
    'expires_at' => $reset['expires_at'],
]);