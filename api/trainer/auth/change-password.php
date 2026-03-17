<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
require_method('POST');
require_trainer();

$body            = json_decode(file_get_contents('php://input'), true) ?? [];
$current_password = $body['current_password'] ?? '';
$new_password     = $body['new_password']     ?? '';
$confirm_password = $body['confirm_password'] ?? '';

if (!$current_password || !$new_password) error('Current and new passwords are required.');
if (strlen($new_password) < 8)            error('New password must be at least 8 characters.');
if ($new_password !== $confirm_password)  error('Passwords do not match.');

$pdo = db();

// Get admin_user linked to this trainer
$trainer_id = $_SESSION['trainer_id'];
$trainer    = $pdo->prepare("SELECT first_name, last_name FROM trainers WHERE id = ?");
$trainer->execute([$trainer_id]);
$t = $trainer->fetch();
if (!$t) error('Trainer not found.', 404);

$stmt = $pdo->prepare("SELECT id, password_hash FROM admin_users WHERE first_name = ? AND last_name = ? LIMIT 1");
$stmt->execute([$t['first_name'], $t['last_name']]);
$user = $stmt->fetch();
if (!$user) error('No admin account linked to this trainer.', 404);

if (!password_verify($current_password, $user['password_hash'])) {
    error('Current password is incorrect.', 401);
}

$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")
    ->execute([$new_hash, $user['id']]);

success('Password updated successfully.');
