<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$date_from = sanitize_string($_GET['date_from'] ?? date('Y-m-01'));
$date_to   = sanitize_string($_GET['date_to']   ?? date('Y-m-d'));
$pdo = db();

// Totals
$totals = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN status='completed' THEN amount ELSE 0 END),0) AS gross,
        COALESCE(SUM(CASE WHEN status='refunded'  THEN amount ELSE 0 END),0) AS refunded,
        COUNT(CASE WHEN status='completed' THEN 1 END)                        AS transaction_count,
        COUNT(CASE WHEN status='pending'   THEN 1 END)                        AS pending_count
    FROM payments WHERE DATE(created_at) BETWEEN ? AND ?
");
$totals->execute([$date_from, $date_to]);
$t = $totals->fetch();

$gross    = (float)($t['gross']    ?? 0);
$refunded = (float)($t['refunded'] ?? 0);

// Monthly chart (last 6 months)
$monthlyChart = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') AS label,
           SUM(CASE WHEN status='completed' THEN amount ELSE 0 END) AS total
    FROM payments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m')
    ORDER BY DATE_FORMAT(created_at,'%Y-%m') ASC
")->fetchAll();

// By type
$byType = $pdo->prepare("
    SELECT type, SUM(amount) AS amount, COUNT(id) AS count
    FROM payments WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY type ORDER BY amount DESC
");
$byType->execute([$date_from, $date_to]);
$by_type = $byType->fetchAll();

// By plan
$byPlan = $pdo->prepare("
    SELECT m.plan, SUM(p.amount) AS revenue, COUNT(p.id) AS transactions
    FROM payments p JOIN members m ON m.id=p.member_id
    WHERE p.status='completed' AND DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY m.plan ORDER BY revenue DESC
");
$byPlan->execute([$date_from, $date_to]);
$by_plan = $byPlan->fetchAll();

// Static expenses
$expenses = ['operating' => 261000, 'salaries' => 180000, 'marketing' => 43268];
$total_expenses = array_sum($expenses);
$net_profit = $gross - $total_expenses;
$goal = 750000;
$goal_pct = $goal > 0 ? round(($gross / $goal) * 100) : 0;

success('Revenue report.', [
    'period'        => ['from' => $date_from, 'to' => $date_to],
    'summary'       => [
        'gross'             => $gross,
        'refunded'          => $refunded,
        'net'               => $gross - $refunded,
        'transaction_count' => (int)($t['transaction_count'] ?? 0),
        'pending_count'     => (int)($t['pending_count']     ?? 0),
    ],
    'monthly_chart' => $monthlyChart,
    'by_type'       => $by_type,
    'by_plan'       => $by_plan,
    'expenses'      => $expenses,
    'total_expenses'=> $total_expenses,
    'net_profit'    => $net_profit,
    'goals'         => ['target' => $goal, 'achieved' => $gross, 'achieved_pct' => $goal_pct],
]);
