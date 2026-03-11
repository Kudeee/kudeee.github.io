<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$pdo = db();
$search = sanitize_string($_GET['search'] ?? '');
$role   = sanitize_string($_GET['role']   ?? '');

$where  = ['1=1']; $params = [];
if ($search !== '') {
    $where[] = "(au.first_name LIKE ? OR au.last_name LIKE ? OR au.email LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($role !== '') { $where[] = "au.role = ?"; $params[] = $role; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$total  = (int)$pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
$active = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE status='active'")->fetchColumn();

$roleBreakdown = $pdo->query("SELECT role, COUNT(*) AS count FROM admin_users GROUP BY role")->fetchAll();
$role_map = [];
foreach ($roleBreakdown as $r) { $role_map[$r['role']] = (int)$r['count']; }

$trainerCount = (int)$pdo->query("SELECT COUNT(*) FROM trainers WHERE status='active'")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT au.id,
           CONCAT(au.first_name,' ',au.last_name) AS full_name,
           au.first_name, au.last_name,
           au.email, au.role, au.status, au.created_at
    FROM admin_users au
    $where_sql
    ORDER BY au.role ASC, au.first_name ASC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

success('Roles retrieved.', [
    'users'         => $users,
    'trainer_count' => $trainerCount,
    'stats'         => [
        'total'       => $total,
        'active'      => $active,
        'super_admin' => $role_map['super_admin'] ?? 0,
        'admin'       => $role_map['admin']       ?? 0,
        'staff'       => $role_map['staff']        ?? 0,
    ],
]);
