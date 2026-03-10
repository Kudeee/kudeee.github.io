<?php
/**
 * GET /api/admin/auth/check-session.php
 * Returns 200 if the caller has a valid admin session, 401 otherwise.
 * Called by admin-js.js on every page load as a session guard.
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$role = $_SESSION['role'] ?? '';
$admin_roles = ['staff', 'admin', 'super_admin'];

if (empty($_SESSION['member_id']) || !in_array($role, $admin_roles, true)) {
    error('Not authenticated.', 401);
}

success('Authenticated.', [
    'admin_id' => (int)$_SESSION['member_id'],
    'name'     => $_SESSION['member_name']  ?? '',
    'email'    => $_SESSION['member_email'] ?? '',
    'role'     => $role,
]);