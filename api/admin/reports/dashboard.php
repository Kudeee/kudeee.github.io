<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$pdo = db();

// Members
$totalMembers  = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status != 'deleted'")->fetchColumn();
$activeMembers = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'active'")->fetchColumn();
$newThisMonth  = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status != 'deleted'")->fetchColumn();

// Subscriptions
$activeSubs    = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
$expiringSoon  = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

// Revenue this month
$monthlyRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// Classes this month
$classesThisMonth = (int)$pdo->query("SELECT COUNT(*) FROM class_schedules WHERE MONTH(scheduled_at)=MONTH(NOW()) AND YEAR(scheduled_at)=YEAR(NOW()) AND status='active'")->fetchColumn();

// Active trainers
$activeTrainers = (int)$pdo->query("SELECT COUNT(*) FROM trainers WHERE status='active'")->fetchColumn();

// Plan distribution
$planDist = $pdo->query("SELECT plan, COUNT(*) AS cnt FROM members WHERE status='active' GROUP BY plan ORDER BY cnt DESC")->fetchAll();

// Recent activity from audit_log
$recentActivity = $pdo->query("
    SELECT al.action, al.target_type, al.created_at,
           CONCAT(au.first_name,' ',au.last_name) AS admin_name
    FROM audit_log al
    LEFT JOIN admin_users au ON au.id = al.admin_id
    ORDER BY al.created_at DESC LIMIT 8
")->fetchAll();

// Monthly revenue chart (last 6 months)
$monthlyChart = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') AS label,
           SUM(CASE WHEN status='completed' THEN amount ELSE 0 END) AS total
    FROM payments
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m')
    ORDER BY DATE_FORMAT(created_at,'%Y-%m') ASC
")->fetchAll();

success('OK', [
    'members'         => ['total' => $totalMembers, 'active' => $activeMembers, 'new_this_period' => $newThisMonth],
    'revenue'         => ['net' => $monthlyRevenue, 'by_plan' => $planDist, 'monthly_chart' => $monthlyChart],
    'classes'         => ['scheduled' => $classesThisMonth],
    'subscriptions'   => ['active' => $activeSubs, 'expiring_soon' => $expiringSoon],
    'top_trainers'    => $activeTrainers,
    'recent_activity' => $recentActivity,
    'plan_distribution' => $planDist,
]);
