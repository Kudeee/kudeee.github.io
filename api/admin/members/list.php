<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 15));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');
$plan     = sanitize_string($_GET['plan']   ?? '');

$where  = ["m.status != 'deleted'"];
$params = [];

if ($search !== '') {
    $where[]  = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($status !== '') { $where[] = "m.status = ?"; $params[] = $status; }
if ($plan   !== '') { $where[] = "m.plan = ?";   $params[] = $plan; }

$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();

$totalMembers  = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status != 'deleted'")->fetchColumn();
$activeMembers = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'active'")->fetchColumn();
$newThisMonth  = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status != 'deleted'")->fetchColumn();
$expiringMonth = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

$count = (int)$pdo->prepare("SELECT COUNT(*) FROM members m $where_sql")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM members m $where_sql") : null;
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM members m $where_sql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pag = get_pagination($total, $page, $per_page);

$stmt = $pdo->prepare("
    SELECT m.id, m.first_name, m.last_name, m.email, m.phone,
           m.status, m.plan, m.billing_cycle, m.join_date, m.created_at,
           s.expiry_date,
           p.created_at AS last_payment_date, p.amount AS last_payment_amount
    FROM members m
    LEFT JOIN subscriptions s ON s.member_id = m.id AND s.status = 'active'
    LEFT JOIN payments p ON p.id = (
        SELECT id FROM payments WHERE member_id = m.id AND status = 'completed'
        ORDER BY created_at DESC LIMIT 1
    )
    $where_sql
    ORDER BY m.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$members = $stmt->fetchAll();

success('Members retrieved.', [
    'members'    => $members,
    'pagination' => $pag,
    'summary'    => [
        'total_members'       => $totalMembers,
        'active'              => $activeMembers,
        'new_this_month'      => $newThisMonth,
        'expiring_this_month' => $expiringMonth,
    ],
]);
