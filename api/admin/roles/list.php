<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$pdo = db();

// List all admin users with their roles
$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 50));
$search   = sanitize_string($_GET['search'] ?? '');
$role     = sanitize_string($_GET['role']   ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = "(au.username LIKE ? OR au.email LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
}
if ($role !== '') {
    $where[]  = "au.role = ?";
    $params[] = $role;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Summary stats
$totalStmt  = $pdo->query("SELECT COUNT(*) FROM admin_users");
$total       = (int)$totalStmt->fetchColumn();

$activeStmt = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE status = 'active'");
$active      = (int)$activeStmt->fetchColumn();

// Role breakdown
$roleBreakdownStmt = $pdo->query("
    SELECT role, COUNT(*) AS count
    FROM admin_users
    GROUP BY role
    ORDER BY count DESC
");
$role_breakdown = $roleBreakdownStmt->fetchAll();

// Count with filters
$count_sql = "SELECT COUNT(*) FROM admin_users au $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$filtered_total = (int)$stmt->fetchColumn();

$pag = get_pagination($filtered_total, $page, $per_page);

// Available roles (static definition — adjust to match your role enum)
$available_roles = [
    ['key' => 'super_admin', 'label' => 'Super Admin',  'description' => 'Full system access'],
    ['key' => 'admin',       'label' => 'Admin',        'description' => 'General admin access'],
    ['key' => 'manager',     'label' => 'Manager',      'description' => 'Manage members and classes'],
    ['key' => 'staff',       'label' => 'Staff',        'description' => 'Limited operational access'],
];

// Build stats.super_admin, stats.staff etc from role_breakdown
$role_map = [];
foreach ($role_breakdown as $r) {
    $role_map[$r['role']] = (int)$r['count'];
}

// Trainer count from trainers table
$trainerCountStmt = $pdo->query("SELECT COUNT(*) FROM trainers WHERE status = 'active'");
$trainer_count = (int)$trainerCountStmt->fetchColumn();

// JS reads: data.users (not data.admins), and last_login_at (not last_login)
$sql = "
    SELECT au.id,
           IFNULL(CONCAT(au.first_name, ' ', au.last_name), au.username) AS full_name,
           au.username,
           IFNULL(au.first_name, au.username) AS first_name,
           IFNULL(au.last_name, '')            AS last_name,
           au.email, au.role, au.status,
           au.last_login                        AS last_login_at,
           au.created_at
    FROM admin_users au
    $where_sql
    ORDER BY au.role ASC, au.username ASC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

success('Roles and admin users retrieved.', [
    'users'           => $users,          // JS reads data.users
    'admins'          => $users,          // keep for backwards compat
    'pagination'      => $pag,
    'available_roles' => $available_roles,
    'trainer_count'   => $trainer_count,  // JS reads data.trainer_count
    // JS reads data.stats.*
    'stats'           => [
        'total'       => $total,
        'active'      => $active,
        'super_admin' => $role_map['super_admin'] ?? 0,
        'admin'       => $role_map['admin']       ?? 0,
        'staff'       => $role_map['staff']        ?? 0,
        'trainer'     => $role_map['trainer']      ?? 0,
    ],
]);