<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 15));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');
$plan     = sanitize_string($_GET['plan']   ?? '');

$where  = ['1=1']; $params = [];
if ($search !== '') {
    $where[] = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($status !== '') { $where[] = "s.status = ?"; $params[] = $status; }
if ($plan   !== '') { $where[] = "s.plan = ?";   $params[] = $plan; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();

$active        = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active'")->fetchColumn();
$expiring7     = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)")->fetchColumn();
$newThisMonth  = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$monthRevenue  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// Plan distribution
$planDist = $pdo->query("SELECT plan, COUNT(*) AS cnt FROM subscriptions WHERE status='active' GROUP BY plan ORDER BY cnt DESC")->fetchAll();
$topPlan  = !empty($planDist) ? $planDist[0]['plan'] : '—';

// Plan counts for the cards
$planCounts = [];
foreach ($planDist as $p) { $planCounts[$p['plan']] = $p['cnt']; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions s JOIN members m ON m.id=s.member_id $where_sql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pag = get_pagination($total, $page, $per_page);

$stmt = $pdo->prepare("
    SELECT s.id, s.plan, s.billing_cycle, s.price, s.status,
           s.start_date, s.expiry_date, s.paused_at, s.created_at,
           m.id AS member_id,
           CONCAT(m.first_name,' ',m.last_name) AS member_name,
           m.email AS member_email, m.phone AS member_phone
    FROM subscriptions s
    JOIN members m ON m.id = s.member_id
    $where_sql
    ORDER BY s.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$subscriptions = $stmt->fetchAll();

success('Subscriptions retrieved.', [
    'subscriptions' => $subscriptions,
    'pagination'    => $pag,
    'stats'         => [
        'active_count'      => $active,
        'expiring_soon'     => $expiring7,
        'new_this_month'    => $newThisMonth,
        'monthly_revenue'   => $monthRevenue,
        'top_plan'          => $topPlan,
        'plan_distribution' => $planDist,
        'plan_counts'       => $planCounts,
    ],
]);
