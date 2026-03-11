<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page      = max(1, sanitize_int($_GET['page']      ?? 1));
$per_page  = max(1, sanitize_int($_GET['per_page']  ?? 30));
$member_id = sanitize_int($_GET['member_id'] ?? 0);
[$date_from, $date_to] = get_date_range();

$where  = ["DATE(a.check_in) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

if ($member_id) {
    $where[]  = "a.member_id = ?";
    $params[] = $member_id;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$count_stmt = db()->prepare("SELECT COUNT(*) FROM attendance a $where_sql");
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();

$pag = get_pagination($total, $page, $per_page);

$stmt = db()->prepare("
    SELECT a.id, a.check_in, a.check_out, a.notes,
           m.id AS member_id, m.first_name, m.last_name
    FROM attendance a
    JOIN members m ON m.id = a.member_id
    $where_sql
    ORDER BY a.check_in DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$records = $stmt->fetchAll();

success('Attendance retrieved.', [
    'attendance' => $records,
    'pagination' => $pag,
    'date_from'  => $date_from,
    'date_to'    => $date_to,
]);
