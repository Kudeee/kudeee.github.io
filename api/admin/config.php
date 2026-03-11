<?php
require_once __DIR__ . '/../../config.php';

// ─── Admin auth helpers ────────────────────────────────────────────────────
function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_id']);
}

function require_admin(array $roles = []): array {
    if (!is_admin_logged_in()) {
        error('Unauthorized. Admin login required.', 401);
    }
    if (!empty($roles) && !in_array($_SESSION['admin_role'] ?? '', $roles)) {
        error('Forbidden. Insufficient permissions.', 403);
    }
    return [
        'id'   => $_SESSION['admin_id'],
        'role' => $_SESSION['admin_role'] ?? '',
        'name' => $_SESSION['admin_name'] ?? '',
    ];
}

function is_super_admin(): bool {
    return ($_SESSION['admin_role'] ?? '') === 'super_admin';
}

// ─── Pagination helper ─────────────────────────────────────────────────────
function get_pagination(int $total, int $page, int $per_page): array {
    $total_pages = max(1, (int)ceil($total / $per_page));
    $page        = max(1, min($page, $total_pages));
    $offset      = ($page - 1) * $per_page;
    return [
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $per_page,
        'total_pages' => $total_pages,
        'offset'      => $offset,
    ];
}

// ─── Date range helper ─────────────────────────────────────────────────────
function get_date_range(): array {
    $from = sanitize_string($_GET['date_from'] ?? date('Y-m-01'));
    $to   = sanitize_string($_GET['date_to']   ?? date('Y-m-d'));
    return [$from, $to];
}
