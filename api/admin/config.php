<?php
/**
 * admin/config.php
 * Society Fitness — Admin-side shared bootstrap.
 * Include this (instead of the root config.php) in every admin endpoint.
 *
 * It includes the root config first, then adds admin-specific guards.
 */

require_once __DIR__ . '/../config.php';

// ─── Admin role constants ─────────────────────────────────────────────────────
define('ADMIN_ROLES', ['admin', 'super_admin', 'staff']);

// ─── Admin auth guard ─────────────────────────────────────────────────────────

/**
 * Abort with 401/403 if the caller is not an authenticated admin.
 * Returns the current admin's session data.
 *
 * @param string[] $allowed_roles  Subset of ADMIN_ROLES; defaults to all.
 */
function require_admin(array $allowed_roles = ['admin', 'super_admin', 'staff']): array {
    if (empty($_SESSION['member_id'])) {
        error('Authentication required.', 401);
    }

    $role = $_SESSION['role'] ?? 'member';

    if (!in_array($role, ADMIN_ROLES, true)) {
        error('Access denied. Admin privileges required.', 403);
    }

    if (!in_array($role, $allowed_roles, true)) {
        error('You do not have permission to perform this action.', 403);
    }

    return [
        'admin_id' => (int) $_SESSION['member_id'],
        'name'     => $_SESSION['member_name']  ?? '',
        'email'    => $_SESSION['member_email'] ?? '',
        'role'     => $role,
    ];
}

/**
 * Returns true if the current session belongs to a super_admin.
 */
function is_super_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'super_admin';
}

// ─── Pagination helper ───────────────────────────────────────────────────────

/**
 * Extract page / per_page from GET params; returns [offset, per_page, page].
 *
 * @param int $default_per_page
 * @param int $max_per_page
 */
function get_pagination(int $default_per_page = 20, int $max_per_page = 100): array {
    $page     = max(1, (int) ($_GET['page']     ?? 1));
    $per_page = max(1, min($max_per_page, (int) ($_GET['per_page'] ?? $default_per_page)));
    $offset   = ($page - 1) * $per_page;
    return [$offset, $per_page, $page];
}

// ─── Date range helper ───────────────────────────────────────────────────────

/**
 * Parse date_from / date_to from GET params.
 * Falls back to current month if not supplied.
 * Returns ['from' => 'YYYY-MM-DD', 'to' => 'YYYY-MM-DD'].
 */
function get_date_range(): array {
    $from = $_GET['date_from'] ?? date('Y-m-01');
    $to   = $_GET['date_to']   ?? date('Y-m-t');

    // Basic format validation
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-t');

    return ['from' => $from, 'to' => $to];
}
