<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin(['super_admin', 'admin']);

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$id     = sanitize_int($body['user_id'] ?? 0);
$role   = sanitize_string($body['role']   ?? '');
$status = sanitize_string($body['status'] ?? '');
$newpw  = $body['new_password'] ?? '';

if (!$id) error('User ID required.');

$set = []; $params = [];
if ($role)   { $set[] = "role=?";   $params[] = $role; }
if ($status) { $set[] = "status=?"; $params[] = $status; }
if ($newpw)  { $set[] = "password_hash=?"; $params[] = password_hash($newpw, PASSWORD_DEFAULT); }
if (!$set) error('Nothing to update.');

$params[] = $id;
db()->prepare("UPDATE admin_users SET " . implode(', ', $set) . " WHERE id=?")->execute($params);
success('User updated.');
