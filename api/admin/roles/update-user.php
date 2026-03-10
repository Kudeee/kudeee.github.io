<?php
/**
 * POST /api/admin/roles/update-user.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin(['super_admin', 'admin']);

$user_id      = sanitize_int($_POST['user_id']      ?? 0);
$role         = sanitize_string($_POST['role']       ?? '');
$status       = sanitize_string($_POST['status']     ?? '');
$new_password = $_POST['new_password']               ?? '';

$valid_roles   = ['super_admin', 'admin', 'staff', 'trainer', 'receptionist'];
$valid_statuses = ['active', 'inactive'];

if (!$user_id || $user_id < 1) error('A valid user ID is required.');
if ($role && !in_array($role, $valid_roles, true)) error('Invalid role.');
if ($status && !in_array($status, $valid_statuses, true)) error('Invalid status.');
if ($new_password && strlen($new_password) < 8) error('Password must be at least 8 characters.');

// Prevent non-super_admin from editing super_admin accounts
if (!is_super_admin() && $role === 'super_admin') {
    error('Only super admins can assign the super_admin role.', 403);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, role FROM admin_users WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $target = $stmt->fetch();
    if (!$target) error('User not found.', 404);

    // Prevent self-demotion or deactivation
    if ($user_id === $admin['admin_id'] && $status === 'inactive') {
        error('You cannot deactivate your own account.', 403);
    }

    $fields = [];
    $params = [];

    if ($role)         { $fields[] = 'role = ?';          $params[] = $role;     }
    if ($status)       { $fields[] = 'status = ?';        $params[] = $status;   }
    if ($new_password) {
        $fields[] = 'password_hash = ?';
        $params[] = password_hash($new_password, PASSWORD_BCRYPT);
    }

    if (empty($fields)) error('No fields to update.');

    $params[] = $user_id;
    $pdo->prepare('UPDATE admin_users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'admin_user_updated', 'admin_user', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $user_id, json_encode(['role' => $role ?: null, 'status' => $status ?: null]), $_SERVER['REMOTE_ADDR'] ?? '']);

    success('User updated successfully.');
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}