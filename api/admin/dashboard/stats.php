<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$pdo = db();

// ── Member counts ───────────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT COUNT(*) FROM members");
$total_members = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'active'");
$active_members = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE DATE(created_at) = CURDATE()");
$new_today = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$new_this_month = (int) $stmt->fetchColumn();

// ── Active memberships ──────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT COUNT(*) FROM memberships WHERE status = 'active'");
$active_memberships = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM memberships WHERE status = 'active' AND end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$expiring_soon = (int) $stmt->fetchColumn();

// ── Attendance ──────────────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()");
$checkins_today = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE WEEK(check_in) = WEEK(NOW())");
$checkins_this_week = (int) $stmt->fetchColumn();

// ── Revenue ─────────────────────────────────────────────────────────────────
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) FROM payments
    WHERE status = 'completed' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
");
$revenue_this_month = (float) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
$revenue_today = (float) $stmt->fetchColumn();

// ── Revenue by month (last 6 months) ────────────────────────────────────────
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
           SUM(amount) AS revenue
    FROM payments
    WHERE status = 'completed'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$monthly_revenue = $stmt->fetchAll();

// ── Membership distribution by plan ─────────────────────────────────────────
$stmt = $pdo->query("
    SELECT mp.name AS plan_name, COUNT(ms.id) AS count
    FROM memberships ms
    JOIN membership_plans mp ON mp.id = ms.plan_id
    WHERE ms.status = 'active'
    GROUP BY mp.id, mp.name
    ORDER BY count DESC
");
$plan_distribution = $stmt->fetchAll();

// ── Recent members ───────────────────────────────────────────────────────────
$stmt = $pdo->query("
    SELECT id, first_name, last_name, email, created_at
    FROM members
    ORDER BY created_at DESC
    LIMIT 5
");
$recent_members = $stmt->fetchAll();

success('Dashboard stats retrieved.', [
    'stats' => [
        'members' => [
            'total'          => $total_members,
            'active'         => $active_members,
            'new_today'      => $new_today,
            'new_this_month' => $new_this_month,
        ],
        'memberships' => [
            'active'         => $active_memberships,
            'expiring_soon'  => $expiring_soon,
        ],
        'attendance' => [
            'today'          => $checkins_today,
            'this_week'      => $checkins_this_week,
        ],
        'revenue' => [
            'today'          => $revenue_today,
            'this_month'     => $revenue_this_month,
            'monthly_chart'  => $monthly_revenue,
        ],
        'plan_distribution' => $plan_distribution,
    ],
    'recent_members' => $recent_members,
]);
