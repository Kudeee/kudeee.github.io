<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page      = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page  = max(1, sanitize_int($_GET['per_page'] ?? 20));
$member_id = sanitize_int($_GET['member_id'] ?? 0);
$status    = sanitize_string($_GET['status']  ?? '');
[$date_from, $date_to] = get_date_range();

$where  = ["DATE(p.created_at) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

if ($member_id) {
    $where[]  = "p.member_id = ?";
    $params[] = $member_id;
}

if ($status !== '') {
    $where[]  = "p.status = ?";
    $params[] = $status;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$count_stmt = db()->prepare("SELECT COUNT(*) FROM payments p $where_sql");
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();

$pag = get_pagination($total, $page, $per_page);

$stmt = db()->prepare("
    SELECT p.id, p.amount, p.method, p.status, p.reference_no, p.created_at,
           m.id AS member_id, m.first_name, m.last_name, m.email,
           mp.name AS plan_name
    FROM payments p
    JOIN members m ON m.id = p.member_id
    LEFT JOIN membership_plans mp ON mp.id = p.plan_id
    $where_sql
    ORDER BY p.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Total revenue for the period
$rev_stmt = db()->prepare("
    SELECT SUM(amount) FROM payments p
    $where_sql AND p.status = 'completed'
");
$rev_params = $params;
$rev_params[] = 'completed'; // appended for the extra condition
// Rebuild to avoid duplicating params: just query separately
$rev_stmt2 = db()->prepare("
    SELECT SUM(p.amount) FROM payments p
    WHERE DATE(p.created_at) BETWEEN ? AND ? AND p.status = 'completed'
");
$rev_stmt2->execute([$date_from, $date_to]);
$total_revenue = (float) ($rev_stmt2->fetchColumn() ?? 0);

success('Payments retrieved.', [
    'payments'      => $payments,
    'pagination'    => $pag,
    'total_revenue' => $total_revenue,
    'date_from'     => $date_from,
    'date_to'       => $date_to,
]);
