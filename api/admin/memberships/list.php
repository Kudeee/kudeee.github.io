<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));
$status   = sanitize_string($_GET['status'] ?? '');
$search   = sanitize_string($_GET['search'] ?? '');

$where  = [];
$params = [];

if ($status !== '') {
    $where[]  = "ms.status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $where[]  = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$count_stmt = db()->prepare("
    SELECT COUNT(*) FROM memberships ms
    JOIN members m ON m.id = ms.member_id
    $where_sql
");
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();

$pag = get_pagination($total, $page, $per_page);

$stmt = db()->prepare("
    SELECT ms.id, ms.status, ms.start_date, ms.end_date,
           m.id AS member_id, m.first_name, m.last_name, m.email,
           mp.id AS plan_id, mp.name AS plan_name, mp.price
    FROM memberships ms
    JOIN members m ON m.id = ms.member_id
    JOIN membership_plans mp ON mp.id = ms.plan_id
    $where_sql
    ORDER BY ms.start_date DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$memberships = $stmt->fetchAll();

success('Memberships retrieved.', [
    'memberships' => $memberships,
    'pagination'  => $pag,
]);
