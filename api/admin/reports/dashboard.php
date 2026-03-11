<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../admin/config.php';

$admin = require_admin();
$pdo   = db();

// Total members
$stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE status != 'deleted'");
$totalMembers = (int)$stmt->fetchColumn();

// Active members (active subscription)
$stmt = $pdo->query("SELECT COUNT(DISTINCT member_id) FROM subscriptions WHERE status = 'active'");
$activeMembers = (int)$stmt->fetchColumn();

// New this month
$stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status!='deleted'");
$newThisMonth = (int)$stmt->fetchColumn();

// Monthly revenue (completed payments this month)
$stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
$monthlyRevenue = (float)$stmt->fetchColumn();

// Classes scheduled this month
$stmt = $pdo->query("SELECT COUNT(*) FROM class_schedules WHERE MONTH(scheduled_at)=MONTH(NOW()) AND YEAR(scheduled_at)=YEAR(NOW()) AND status='active'");
$classesThisMonth = (int)$stmt->fetchColumn();

// Active trainers
$stmt = $pdo->query("SELECT COUNT(*) FROM trainers WHERE status='active'");
$activeTrainers = (int)$stmt->fetchColumn();

// Recent activity from audit log
$stmt = $pdo->query("
    SELECT al.*, CONCAT(au.first_name,' ',au.last_name) AS admin_name
    FROM audit_log al
    LEFT JOIN admin_users au ON au.id = al.admin_id
    ORDER BY al.created_at DESC LIMIT 10
");
$recentActivity = $stmt->fetchAll();

// Plan distribution
$stmt = $pdo->query("SELECT plan, COUNT(*) as cnt FROM members WHERE status='active' GROUP BY plan");
$planDist = $stmt->fetchAll();

success('OK', [
    'members' => [
        'total'           => $totalMembers,
        'active'          => $activeMembers,
        'new_this_period' => $newThisMonth,
    ],
    'revenue' => [
        'net'      => $monthlyRevenue,
        'by_plan'  => $planDist,
    ],
    'classes' => [
        'scheduled' => $classesThisMonth,
    ],
    'top_trainers'    => array_fill(0, $activeTrainers, []),
    'recent_activity' => $recentActivity,
]);
