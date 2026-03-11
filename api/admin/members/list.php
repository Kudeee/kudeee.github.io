<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');
$plan     = sanitize_string($_GET['plan']   ?? '');

$where  = ["m.status != 'deleted'"];
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
if ($plan !== '') {
    $where[]  = "m.plan = ?";
    $params[] = $plan;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Counts for stats
$pdo = db();
$totalStmt = $pdo->query("SELECT COUNT(*) FROM members WHERE status != 'deleted'");
$total_members = (int)$totalStmt->fetchColumn();

$activeStmt = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'active'");
$active_members = (int)$activeStmt->fetchColumn();

$newStmt = $pdo->query("SELECT COUNT(*) FROM members WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status != 'deleted'");
$new_this_month = (int)$newStmt->fetchColumn();

$expiringStmt = $pdo->query("
    SELECT COUNT(DISTINCT member_id) FROM subscriptions
    WHERE status = 'active'
      AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
$expiring_this_month = (int)$expiringStmt->fetchColumn();

// Paginated list
$count_sql = "SELECT COUNT(*) FROM members m $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$pag = get_pagination($total, $page, $per_page);

$sql = "
    SELECT m.id, m.first_name, m.last_name, m.email, m.phone,
           m.status, m.plan, m.billing_cycle, m.join_date, m.created_at,
           s.expiry_date,
           p.created_at AS last_payment_date
    FROM members m
    LEFT JOIN subscriptions s
        ON s.member_id = m.id AND s.status = 'active'
    LEFT JOIN payments p
        ON p.id = (
            SELECT id FROM payments
            WHERE member_id = m.id AND status = 'completed'
            ORDER BY created_at DESC LIMIT 1
        )
    $where_sql
    ORDER BY m.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

success('Members retrieved.', [
    'members'    => $members,
    'pagination' => $pag,
    'summary'    => [
        'total_members'      => $total_members,
        'active'             => $active_members,
        'new_this_month'     => $new_this_month,
        'expiring_this_month'=> $expiring_this_month,
    ],
]);