<?php
// FIXED: returns data.totals.{gross_revenue, total_transactions, failed_count, pending_count}
// and data.payments[].{member_name, transaction_id, type, method} as JS expects
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page      = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page  = max(1, sanitize_int($_GET['per_page'] ?? 15));
$member_id = sanitize_int($_GET['member_id'] ?? 0);
$status    = sanitize_string($_GET['status']  ?? '');
$method    = sanitize_string($_GET['method']  ?? '');
$type      = sanitize_string($_GET['type']    ?? '');
$search    = sanitize_string($_GET['search']  ?? '');

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
if ($method !== '') {
    $where[]  = "p.method = ?";
    $params[] = $method;
}
if ($type !== '') {
    $where[]  = "IFNULL(p.type, 'subscription') = ?";
    $params[] = $type;
}
if ($search !== '') {
    $where[]  = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();

// Totals for the period — JS reads data.totals.*
$totStmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) AS gross_revenue,
        COUNT(CASE WHEN p.status = 'completed' THEN 1 END)                            AS total_transactions,
        COUNT(CASE WHEN p.status = 'failed'    THEN 1 END)                            AS failed_count,
        COUNT(CASE WHEN p.status = 'pending'   THEN 1 END)                            AS pending_count
    FROM payments p
    JOIN members m ON m.id = p.member_id
    $where_sql
");
$totStmt->execute($params);
$totals = $totStmt->fetch();

// Count for pagination
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM payments p JOIN members m ON m.id = p.member_id $where_sql
");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pag = get_pagination($total, $page, $per_page);

// JS reads: p.transaction_id, p.member_name, p.type, p.amount, p.method, p.created_at, p.status
$stmt = $pdo->prepare("
    SELECT p.id,
           IFNULL(p.reference_no, CONCAT('TXN-', LPAD(p.id, 6, '0'))) AS transaction_id,
           CONCAT(m.first_name, ' ', m.last_name)                       AS member_name,
           m.email                                                        AS member_email,
           IFNULL(p.type, 'subscription')                                AS type,
           p.amount,
           IFNULL(p.method, p.payment_method)                            AS method,
           p.status,
           p.notes,
           p.created_at
    FROM payments p
    JOIN members m ON m.id = p.member_id
    $where_sql
    ORDER BY p.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

success('Payments retrieved.', [
    'payments'      => $payments,
    'pagination'    => $pag,
    'totals'        => $totals,       // JS reads data.totals.*
    'total_revenue' => (float)($totals['gross_revenue'] ?? 0),
    'date_from'     => $date_from,
    'date_to'       => $date_to,
]);