<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$token       = sanitize_string($body['token']            ?? $_POST['token']            ?? '');
$password    = $body['password']                         ?? $_POST['password']         ?? '';
$confirmPass = $body['confirm_password']                 ?? $_POST['confirm_password'] ?? '';

if (!$token)    error('Reset token is required.');
if (!$password) error('New password is required.');
if (strlen($password) < 8) error('Password must be at least 8 characters.');
if ($password !== $confirmPass) error('Passwords do not match.');

$pdo = db();

// Ensure table exists (in case forgot-password was never called)
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    user_type  VARCHAR(20)  NOT NULL DEFAULT 'member',
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   DEFAULT 0,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Look up the token
$stmt = $pdo->prepare("
    SELECT * FROM password_resets
    WHERE token = ? AND used = 0 AND created_at > NOW() - INTERVAL 1 HOUR
    LIMIT 1
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    error('This reset link is invalid or has expired. Please request a new one.');
}

$newHash = password_hash($password, PASSWORD_BCRYPT);
$email   = $reset['email'];

// Update the correct table based on user_type
if ($reset['user_type'] === 'admin') {
    $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE email = ?");
} else {
    $upd = $pdo->prepare("UPDATE members SET password_hash = ? WHERE email = ?");
}
$upd->execute([$newHash, $email]);

// Mark token as used
$pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")
    ->execute([$reset['id']]);

// Also invalidate any other unused tokens for this email
$pdo->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND id != ?")
    ->execute([$email, $reset['id']]);

success('Password reset successfully. You can now log in with your new password.');