<?php
/**
 * GET /api/admin/reports/revenue.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$date     = get_date_range();
$group_by = in_array($_GET['group_by'] ?? 'month', ['day','week','month']) ? ($_GET['group_by'] ?? 'month') : 'month';
$type     = sanitize_string($_GET['type'] ?? 'all');
$from     = $date['from'] . ' 00:00:00';
$to       = $date['to']   . ' 23:59:59';

try {
    $pdo = db();

    $type_filter = ($type !== 'all') ? 'AND p.type = ?' : '';
    $params_base = [$from, $to];
    if ($type !== 'all') $params_base[] = $type;

    // Summary
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN status='completed' AND amount>0 THEN amount ELSE 0 END), 0) AS gross,
            COALESCE(SUM(CASE WHEN status='refunded' THEN amount ELSE 0 END), 0) AS refunds,
            COUNT(CASE WHEN status='completed' AND amount>0 THEN 1 END) AS total_transactions
        FROM payments p WHERE p.created_at BETWEEN ? AND ? $type_filter
    ");
    $stmt->execute($params_base);
    $summary = $stmt->fetch();
    $summary['net'] = (float) $summary['gross'] - (float) $summary['refunds'];

    // Monthly comparison — last 6 months
    $monthly = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end   = date('Y-m-t', strtotime("-$i months"));
        $label       = date('M Y', strtotime("-$i months"));

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM payments
            WHERE created_at BETWEEN ? AND ? AND status='completed' AND amount>0
        ");
        $stmt->execute([$month_start . ' 00:00:00', $month_end . ' 23:59:59']);
        $monthly[] = [
            'label'  => $label,
            'period' => $month_start,
            'total'  => (float) $stmt->fetchColumn(),
        ];
    }

    // By plan
    $stmt = $pdo->prepare("
        SELECT m.plan, COALESCE(SUM(p.amount), 0) AS amount, COUNT(*) AS count
        FROM payments p JOIN members m ON m.id=p.member_id
        WHERE p.created_at BETWEEN ? AND ? AND p.status='completed' AND p.amount>0 AND p.type='subscription'
        GROUP BY m.plan
    ");
    $stmt->execute([$from, $to]);
    $by_plan = $stmt->fetchAll();

    // By method
    $stmt = $pdo->prepare("
        SELECT method, COALESCE(SUM(amount), 0) AS amount, COUNT(*) AS count
        FROM payments
        WHERE created_at BETWEEN ? AND ? AND status='completed' AND amount>0 $type_filter
        GROUP BY method
    ");
    $stmt->execute($params_base);
    $by_method = $stmt->fetchAll();

    // By type
    $stmt = $pdo->prepare("
        SELECT type, COALESCE(SUM(amount), 0) AS amount, COUNT(*) AS count
        FROM payments
        WHERE created_at BETWEEN ? AND ? AND status='completed' AND amount>0
        GROUP BY type
    ");
    $stmt->execute([$from, $to]);
    $by_type = $stmt->fetchAll();

    // Expenses (static for now — replace with expenses table when available)
    $expenses = [
        'operating'  => 289400,
        'salaries'   => 180000,
        'marketing'  => 43268,
    ];
    $total_expenses = array_sum($expenses);
    $net_profit = (float) $summary['gross'] - $total_expenses;

    // Goals
    $monthly_target = 750000;
    $achieved_pct   = $monthly_target > 0 ? round(((float) $summary['gross'] / $monthly_target) * 100) : 0;

    success('Revenue report retrieved.', [
        'period'         => $date,
        'summary'        => $summary,
        'monthly_chart'  => $monthly,
        'by_plan'        => $by_plan,
        'by_method'      => $by_method,
        'by_type'        => $by_type,
        'expenses'       => $expenses,
        'total_expenses' => $total_expenses,
        'net_profit'     => $net_profit,
        'goals'          => [
            'monthly_target'  => $monthly_target,
            'achieved_pct'    => $achieved_pct,
        ],
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}