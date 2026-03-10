<?php
/**
 * GET  /api/admin/settings/admins.php          — list admin accounts
 * POST /api/admin/settings/admins.php?action=create   — create admin account
 * POST /api/admin/settings/admins.php?action=update   — update admin account
 * POST /api/admin/settings/admins.php?action=deactivate — deactivate account
 *
 * Restricted to super_admin only.
 *
 * ── GET params (list) ────────────────────────────────────────────────────────
 *   (none required)
 *
 * ── POST params (create) ─────────────────────────────────────────────────────
 *   csrf_token   string  required
 *   first_name   string  required
 *   last_name    string  required
 *   email        email   required, unique
 *   password     string  required, min 8 chars
 *   role         string  required: admin | staff
 *
 * ── POST params (update) ─────────────────────────────────────────────────────
 *   csrf_token    string  required
 *   admin_user_id int     required
 *   first_name    string  optional
 *   last_name     string  optional
 *   email         email   optional
 *   role          string  optional: admin | staff | super_admin
 *   new_password  string  optional
 *
 * ── POST params (deactivate) ─────────────────────────────────────────────────
 *   csrf_token    string  required
 *   admin_user_id int     required
 *
 * DB tables used:
 *   admin_users, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
$admin = require_admin(['super_admin']); // strictly super_admin only

$action = sanitize_string($_GET['action'] ?? '');

// ─── GET: list admin accounts ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    /*
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $stmt = $pdo->query("
            SELECT id, first_name, last_name, email, role, status, last_login_at, created_at
            FROM admin_users ORDER BY role ASC, first_name ASC
        ");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        success('Admin accounts retrieved.', ['admins' => $admins]);
    */
    error('Database not connected yet. This endpoint is ready for integration.', 503);
}

// ─── POST: create / update / deactivate ───────────────────────────────────────
require_method('POST');
require_csrf();

if ($action === 'create') {
    $first_name  = sanitize_string($_POST['first_name']  ?? '');
    $last_name   = sanitize_string($_POST['last_name']   ?? '');
    $email       = sanitize_email(trim($_POST['email']   ?? ''));
    $password    = $_POST['password']                    ?? '';
    $role        = sanitize_string($_POST['role']        ?? '');

    if (!$first_name)                                   error('First name is required.');
    if (!$last_name)                                    error('Last name is required.');
    if (!$email)                                        error('A valid email is required.');
    if (strlen($password) < 8)                         error('Password must be at least 8 characters.');
    if (!in_array($role, ['admin','staff'], true))      error('Role must be admin or staff.');

    /*
        $pdo = new PDO(...);
        $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) error('This email is already in use.', 409);

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("
            INSERT INTO admin_users (first_name, last_name, email, password_hash, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ")->execute([$first_name, $last_name, $email, $hash, $role]);
        $new_id = (int) $pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, created_at)
            VALUES (?, 'create_admin', 'admin_user', ?, NOW())
        ")->execute([$admin['admin_id'], $new_id]);

        success('Admin account created.', ['admin_user_id' => $new_id], 201);
    */
    error('Database not connected yet. This endpoint is ready for integration.', 503);

} elseif ($action === 'update') {
    $target_id    = sanitize_int($_POST['admin_user_id'] ?? 0);
    $first_name   = sanitize_string($_POST['first_name']  ?? '');
    $last_name    = sanitize_string($_POST['last_name']   ?? '');
    $raw_email    = trim($_POST['email']                  ?? '');
    $email        = $raw_email ? sanitize_email($raw_email) : null;
    $role         = sanitize_string($_POST['role']        ?? '');
    $new_password = $_POST['new_password']                ?? '';

    if (!$target_id || $target_id < 1)                  error('A valid admin user ID is required.');
    if ($raw_email && !$email)                          error('Invalid email format.');
    if ($role && !in_array($role, ['admin','staff','super_admin'], true)) error('Invalid role.');
    if ($new_password && strlen($new_password) < 8)    error('New password must be at least 8 characters.');

    /*
        $pdo = new PDO(...);
        $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([$target_id]);
        if (!$stmt->fetch()) error('Admin user not found.', 404);

        $fields = []; $params = [];
        if ($first_name)   { $fields[]='first_name=?';   $params[]=$first_name;  }
        if ($last_name)    { $fields[]='last_name=?';    $params[]=$last_name;   }
        if ($email)        { $fields[]='email=?';        $params[]=$email;       }
        if ($role)         { $fields[]='role=?';         $params[]=$role;        }
        if ($new_password) { $fields[]='password_hash=?';$params[]=password_hash($new_password,PASSWORD_BCRYPT); }
        if (empty($fields)) error('No fields to update.');
        $fields[] = 'updated_at=NOW()'; $params[] = $target_id;
        $pdo->prepare('UPDATE admin_users SET '.implode(',',$fields).' WHERE id=?')->execute($params);
        success('Admin account updated.');
    */
    error('Database not connected yet. This endpoint is ready for integration.', 503);

} elseif ($action === 'deactivate') {
    $target_id = sanitize_int($_POST['admin_user_id'] ?? 0);
    if (!$target_id || $target_id < 1) error('A valid admin user ID is required.');
    if ($target_id === $admin['admin_id']) error('You cannot deactivate your own account.', 403);

    /*
        $pdo = new PDO(...);
        $pdo->prepare("UPDATE admin_users SET status='inactive', updated_at=NOW() WHERE id=?")->execute([$target_id]);
        $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, created_at) VALUES (?, 'deactivate_admin', 'admin_user', ?, NOW())")->execute([$admin['admin_id'], $target_id]);
        success('Admin account deactivated.');
    */
    error('Database not connected yet. This endpoint is ready for integration.', 503);

} else {
    error('Invalid action. Use: create, update, or deactivate.', 400);
}
