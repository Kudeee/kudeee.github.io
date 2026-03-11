<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$period     = sanitize_string($_GET['period']     ?? 'month');  // day|week|month|year|custom
$date_from  = sanitize_string($_GET['date_from']  ?? '');
$date_to    = sanitize_string($_GET['date_to']    ?? '');
$group_by   = sanitize_string($_GET['group_by']   ?? 'day');    // day|week|month

// Build date range
$pdo = db();
switch ($period) {
    case 'day':
        $from = date('Y-m-d');
        $to   = date('Y-m-d');
        break;
    case 'week':
        $from = date('Y-m-d', strtotime('monday this week'));
        $to   = date('Y-m-d');
        break;
    case 'year':
        $from = date('Y-01-01');
        $to   = date('Y-m-d');
        break;
    case 'custom':
        $from = $date_from ?: date('Y-m-01');
        $to   = $date_to   ?: date('Y-m-d');
        break;
    default: // month
        $from = date('Y-m-01');
        $to   = date('Y-m-d');
}

// Totals
$totalsStmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS gross,
        SUM(CASE WHEN status = 'refunded'  THEN amount ELSE 0 END) AS refunded,
        COUNT(CASE WHEN status = 'completed' THEN 1 END)            AS transaction_count,
        COUNT(CASE WHEN status = 'pending'   THEN 1 END)            AS pending_count
    FROM payments
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$totalsStmt->execute([$from, $to]);
$totals = $totalsStmt->fetch();

$gross    = (float)($totals['gross']    ?? 0);
$refunded = (float)($totals['refunded'] ?? 0);
$net      = $gross - $refunded;

// Chart data — group by day/week/month
$date_format = match ($group_by) {
    'week'  => '%Y-%u',
    'month' => '%Y-%m',
    default => '%Y-%m-%d',
};

$chartStmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(created_at, '$date_format') AS period_label,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS revenue,
        COUNT(CASE WHEN status = 'completed' THEN 1 END)            AS transactions
    FROM payments
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY period_label
    ORDER BY period_label ASC
");
$chartStmt->execute([$from, $to]);
$chart_data = $chartStmt->fetchAll();

// Breakdown by plan
$planStmt = $pdo->prepare("
    SELECT m.plan,
           SUM(p.amount)  AS revenue,
           COUNT(p.id)    AS transactions
    FROM payments p
    JOIN members m ON m.id = p.member_id
    WHERE p.status = 'completed' AND DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY m.plan
    ORDER BY revenue DESC
");
$planStmt->execute([$from, $to]);
$by_plan = $planStmt->fetchAll();

// Last 6 months chart — JS reads monthly_chart[].label and monthly_chart[].total
$monthChartStmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS label,
           SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS total
    FROM payments
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC
");
$monthly_chart = $monthChartStmt->fetchAll();

// By type breakdown — JS reads by_type[].type, by_type[].amount, by_type[].count
$byTypeStmt = $pdo->prepare("
    SELECT IFNULL(p.type, 'subscription') AS type,
           SUM(p.amount) AS amount,
           COUNT(p.id)   AS count
    FROM payments p
    WHERE p.status = 'completed' AND DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY p.type
    ORDER BY amount DESC
");
$byTypeStmt->execute([$from, $to]);
$by_type = $byTypeStmt->fetchAll();
// Fallback: if type column doesn't exist, group by plan
if (empty($by_type)) {
    $byTypeStmt2 = $pdo->prepare("
        SELECT m.plan AS type, SUM(p.amount) AS amount, COUNT(p.id) AS count
        FROM payments p JOIN members m ON m.id = p.member_id
        WHERE p.status = 'completed' AND DATE(p.created_at) BETWEEN ? AND ?
        GROUP BY m.plan ORDER BY amount DESC
    ");
    $byTypeStmt2->execute([$from, $to]);
    $by_type = $byTypeStmt2->fetchAll();
}

// Static expenses (hardcoded as in revenue.php view)
$expenses = [
    'operating'  => 261000,
    'salaries'   => 180000,
    'marketing'  => 43268,
];
$total_expenses = array_sum($expenses);
$net_profit     = $gross - $total_expenses;

// Monthly revenue goal
$goal      = 750000;
$goal_pct  = $goal > 0 ? round(($gross / $goal) * 100) : 0;

success('Revenue report retrieved.', [
    'period'         => ['from' => $from, 'to' => $to],
    'summary'        => [
        'gross'             => $gross,
        'refunded'          => $refunded,
        'net'               => $net,
        'transaction_count' => (int)($totals['transaction_count'] ?? 0),
        'pending_count'     => (int)($totals['pending_count']     ?? 0),
    ],
    'monthly_chart'  => $monthly_chart,
    'by_type'        => $by_type,
    'by_plan'        => $by_plan,
    'expenses'       => $expenses,
    'total_expenses' => $total_expenses,
    'net_profit'     => $net_profit,
    'goals'          => [
        'target'       => $goal,
        'achieved'     => $gross,
        'achieved_pct' => $goal_pct,
    ],
]);