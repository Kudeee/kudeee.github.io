<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin(['super_admin', 'admin']);

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$first    = sanitize_string($body['first_name']    ?? '');
$last     = sanitize_string($body['last_name']     ?? '');
$email    = sanitize_email($body['email']           ?? '');
$role     = sanitize_string($body['role']           ?? 'staff');
$password = $body['temp_password'] ?? 'changeme123';

if (!$first || !$last || !$email) error('First name, last name, and email are required.');

// Check duplicate email
$dup = db()->prepare("SELECT id FROM admin_users WHERE email=?");
$dup->execute([$email]);
if ($dup->fetch()) error('Email already in use.');

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare("INSERT INTO admin_users (first_name, last_name, email, password_hash, role, status, created_at) VALUES (?,?,?,?,?,'active',NOW())");
$stmt->execute([$first, $last, $email, $hash, $role]);
success('User created.', ['user_id' => db()->lastInsertId()]);
