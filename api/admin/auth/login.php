<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = sanitize_email($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$email || !$password) {
    error('Email and password are required.');
}

$stmt = db()->prepare("SELECT id, name, email, password, role FROM admins WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    error('Invalid email or password.', 401);
}

$_SESSION['admin_id']   = $admin['id'];
$_SESSION['admin_role'] = $admin['role'];
$_SESSION['admin_name'] = $admin['name'];

success('Login successful.', [
    'admin' => [
        'id'   => $admin['id'],
        'name' => $admin['name'],
        'role' => $admin['role'],
    ]
]);
