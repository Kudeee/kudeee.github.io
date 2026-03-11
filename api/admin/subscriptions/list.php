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

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '') {
    $where[]  = "s.status = ?";
    $params[] = $status;
}
if ($plan !== '') {
    $where[]  = "s.plan = ?";
    $params[] = $plan;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();

// Summary stats
$activeStmt    = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
$active         = (int)$activeStmt->fetchColumn();

$expiredStmt   = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'expired'");
$expired        = (int)$expiredStmt->fetchColumn();

$expiringStmt  = $pdo->query("
    SELECT COUNT(*) FROM subscriptions
    WHERE status = 'active'
      AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
$expiring_soon  = (int)$expiringStmt->fetchColumn();

$newStmt       = $pdo->query("
    SELECT COUNT(*) FROM subscriptions
    WHERE MONTH(start_date)=MONTH(NOW()) AND YEAR(start_date)=YEAR(NOW())
");
$new_this_month = (int)$newStmt->fetchColumn();

// Count with filters
$count_sql = "SELECT COUNT(*) FROM subscriptions s JOIN members m ON m.id = s.member_id $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$filtered_total = (int)$stmt->fetchColumn();

$pag = get_pagination($filtered_total, $page, $per_page);

$sql = "
    SELECT s.id, s.plan, s.billing_cycle, s.status,
           s.start_date, s.expiry_date, s.amount, s.created_at,
           m.id AS member_id,
           CONCAT(m.first_name, ' ', m.last_name) AS member_name,
           m.email AS member_email,
           m.phone AS member_phone
    FROM subscriptions s
    JOIN members m ON m.id = s.member_id
    $where_sql
    ORDER BY s.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subscriptions = $stmt->fetchAll();

// Monthly revenue from active subscriptions
$monthRevStmt = $pdo->query("
    SELECT COALESCE(SUM(p.amount), 0)
    FROM payments p
    WHERE p.status = 'completed'
      AND MONTH(p.created_at) = MONTH(NOW())
      AND YEAR(p.created_at)  = YEAR(NOW())
");
$monthly_revenue = (float)$monthRevStmt->fetchColumn();

// Plan distribution — JS reads stats.plan_distribution[].plan and .cnt
$planDistStmt = $pdo->query("
    SELECT m.plan, COUNT(*) AS cnt
    FROM subscriptions s
    JOIN members m ON m.id = s.member_id
    WHERE s.status = 'active'
    GROUP BY m.plan
    ORDER BY cnt DESC
");
$plan_dist = $planDistStmt->fetchAll();

// Top plan
$top_plan = !empty($plan_dist) ? ($plan_dist[0]['plan'] ?? '—') : '—';

// Expiring within 7 days (matches UI label "Within 7 days")
$expiring7Stmt = $pdo->query("
    SELECT COUNT(*) FROM subscriptions
    WHERE status = 'active'
      AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$expiring_7days = (int)$expiring7Stmt->fetchColumn();

success('Subscriptions retrieved.', [
    'subscriptions' => $subscriptions,
    'pagination'    => $pag,
    // JS reads data.stats.* keys:
    'stats'         => [
        'active_count'      => $active,
        'expired'           => $expired,
        'expiring_soon'     => $expiring_7days,
        'new_this_month'    => $new_this_month,
        'monthly_revenue'   => $monthly_revenue,
        'top_plan'          => $top_plan,
        'plan_distribution' => $plan_dist,
    ],
]);