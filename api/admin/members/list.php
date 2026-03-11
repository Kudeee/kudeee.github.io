<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '') {
    $where[]  = "m.status = ?";
    $params[] = $status;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$count_sql = "SELECT COUNT(*) FROM members m $where_sql";
$stmt = db()->prepare($count_sql);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$pag = get_pagination($total, $page, $per_page);

$sql = "
    SELECT m.id, m.first_name, m.last_name, m.email, m.phone, m.status,
           m.created_at,
           mp.name AS plan_name, mp.price AS plan_price
    FROM members m
    LEFT JOIN memberships ms ON ms.member_id = m.id AND ms.status = 'active'
    LEFT JOIN membership_plans mp ON mp.id = ms.plan_id
    $where_sql
    ORDER BY m.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

success('Members retrieved.', [
    'members'    => $members,
    'pagination' => $pag,
]);
