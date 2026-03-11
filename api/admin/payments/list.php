<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 15));
$status   = sanitize_string($_GET['status']  ?? '');
$method   = sanitize_string($_GET['method']  ?? '');
$type     = sanitize_string($_GET['type']    ?? '');
$search   = sanitize_string($_GET['search']  ?? '');
$date_from = sanitize_string($_GET['date_from'] ?? date('Y-m-01'));
$date_to   = sanitize_string($_GET['date_to']   ?? date('Y-m-d'));

$where  = ["DATE(p.created_at) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

if ($status !== '') { $where[] = "p.status = ?"; $params[] = $status; }
if ($method !== '') { $where[] = "p.method = ?"; $params[] = $method; }
if ($type   !== '') { $where[] = "p.type = ?";   $params[] = $type; }
if ($search !== '') {
    $where[] = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR p.transaction_id LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);
$pdo = db();

$totStmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN p.status='completed' THEN p.amount ELSE 0 END),0) AS gross_revenue,
        COUNT(CASE WHEN p.status='completed' THEN 1 END)                           AS total_transactions,
        COUNT(CASE WHEN p.status='failed'    THEN 1 END)                           AS failed_count,
        COUNT(CASE WHEN p.status='pending'   THEN 1 END)                           AS pending_count
    FROM payments p
    JOIN members m ON m.id = p.member_id
    $where_sql
");
$totStmt->execute($params);
$totals = $totStmt->fetch();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments p JOIN members m ON m.id=p.member_id $where_sql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pag = get_pagination($total, $page, $per_page);

$stmt = $pdo->prepare("
    SELECT p.id,
           IFNULL(p.transaction_id, CONCAT('TXN-',LPAD(p.id,6,'0'))) AS transaction_id,
           CONCAT(m.first_name,' ',m.last_name) AS member_name,
           m.email AS member_email,
           p.type, p.amount, p.method, p.status, p.description, p.created_at
    FROM payments p
    JOIN members m ON m.id = p.member_id
    $where_sql
    ORDER BY p.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

success('Payments retrieved.', [
    'payments'   => $payments,
    'pagination' => $pag,
    'totals'     => $totals,
    'date_from'  => $date_from,
    'date_to'    => $date_to,
]);
