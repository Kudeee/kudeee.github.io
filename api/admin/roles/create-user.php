<?php
/**
 * POST /api/admin/roles/create-user.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin(['super_admin']);

$first_name    = sanitize_string($_POST['first_name']    ?? '');
$last_name     = sanitize_string($_POST['last_name']     ?? '');
$raw_email     = trim($_POST['email']                    ?? '');
$email         = sanitize_email($raw_email);
$role          = sanitize_string($_POST['role']          ?? '');
$temp_password = $_POST['temp_password']                 ?? '';

$valid_roles = ['super_admin', 'admin', 'staff', 'trainer', 'receptionist'];

if (!$first_name)                                     error('First name is required.');
if (!$last_name)                                      error('Last name is required.');
if (!$email)                                          error('A valid email is required.');
if (!in_array($role, $valid_roles, true))              error('Invalid role.');
if (strlen($temp_password) < 8)                       error('Password must be at least 8 characters.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) error('This email is already in use.', 409);

    $hash = password_hash($temp_password, PASSWORD_BCRYPT);
    $pdo->prepare("
        INSERT INTO admin_users (first_name, last_name, email, password_hash, role, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ")->execute([$first_name, $last_name, $email, $hash, $role]);
    $new_id = (int) $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'admin_user_created', 'admin_user', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $new_id, json_encode(['role' => $role, 'email' => $email]), $_SERVER['REMOTE_ADDR'] ?? '']);

    success('Admin user created.', ['user_id' => $new_id], 201);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}