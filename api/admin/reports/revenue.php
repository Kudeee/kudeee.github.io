<?php
/**
 * GET /api/admin/reports/revenue.php
 *
 * Detailed revenue report with daily/monthly breakdowns.
 *
 * Query params:
 *   date_from    date     required
 *   date_to      date     required
 *   group_by     string   day | week | month  (default: day)
 *   type         string   membership | class | trainer | event | all  (default: all)
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "period": { "from": "...", "to": "..." },
 *     "summary": { gross, refunds, net, total_transactions },
 *     "chart_data": [ { period, gross, refunds, net, transactions } ],
 *     "by_plan":   [ { plan, amount, count } ],
 *     "by_method": [ { payment_method, amount, count } ],
 *     "by_type":   [ { type, amount, count } ]
 *   }
 *
 * DB tables used:
 *   payments, members
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$date     = get_date_range();
$group_by = in_array($_GET['group_by'] ?? 'day', ['day','week','month']) ? ($_GET['group_by'] ?? 'day') : 'day';
$type     = sanitize_string($_GET['type'] ?? 'all');

$valid_types = ['all','membership','class','trainer','event'];
if (!in_array($type, $valid_types, true)) error('Invalid type filter.');

$from = $date['from'] . ' 00:00:00';
$to   = $date['to']   . ' 23:59:59';

// ─── TODO: replace stub with real DB queries ──────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $type_filter = ($type !== 'all') ? 'AND p.type = :type' : '';
    $params_base = [':from' => $from, ':to' => $to];
    if ($type !== 'all') $params_base[':type'] = $type;

    // Summary
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN status='completed' AND amount>0 THEN amount ELSE 0 END) AS gross,
            SUM(CASE WHEN status='refunded'  OR  amount<0 THEN ABS(amount) ELSE 0 END) AS refunds,
            COUNT(CASE WHEN status='completed' AND amount>0 THEN 1 END) AS total_transactions
        FROM payments p
        WHERE p.created_at BETWEEN :from AND :to $type_filter
    ");
    $stmt->execute($params_base);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    $summary['net'] = (float)$summary['gross'] - (float)$summary['refunds'];

    // Chart data grouping
    $date_format = match($group_by) {
        'week'  => "DATE_FORMAT(p.created_at, '%Y-W%u')",
        'month' => "DATE_FORMAT(p.created_at, '%Y-%m')",
        default => "DATE(p.created_at)",
    };

    $stmt = $pdo->prepare("
        SELECT
            $date_format AS period,
            SUM(CASE WHEN status='completed' AND amount>0 THEN amount ELSE 0 END) AS gross,
            SUM(CASE WHEN status='refunded'  OR  amount<0 THEN ABS(amount) ELSE 0 END) AS refunds,
            SUM(CASE WHEN status='completed' AND amount>0 THEN amount ELSE 0 END)
            - SUM(CASE WHEN status='refunded' OR amount<0 THEN ABS(amount) ELSE 0 END) AS net,
            COUNT(CASE WHEN status='completed' AND amount>0 THEN 1 END) AS transactions
        FROM payments p
        WHERE p.created_at BETWEEN :from AND :to $type_filter
        GROUP BY period ORDER BY period ASC
    ");
    $stmt->execute($params_base);
    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // By plan
    $stmt = $pdo->prepare("
        SELECT m.plan, SUM(p.amount) AS amount, COUNT(*) AS count
        FROM payments p JOIN members m ON m.id=p.member_id
        WHERE p.created_at BETWEEN :from AND :to AND p.status='completed' AND p.amount>0 $type_filter
        GROUP BY m.plan
    ");
    $stmt->execute($params_base);
    $by_plan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // By method
    $stmt = $pdo->prepare("
        SELECT payment_method, SUM(amount) AS amount, COUNT(*) AS count
        FROM payments p
        WHERE p.created_at BETWEEN :from AND :to AND status='completed' AND amount>0 $type_filter
        GROUP BY payment_method
    ");
    $stmt->execute($params_base);
    $by_method = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // By type
    $stmt = $pdo->prepare("
        SELECT type, SUM(amount) AS amount, COUNT(*) AS count
        FROM payments p
        WHERE p.created_at BETWEEN :from AND :to AND status='completed' AND amount>0
        GROUP BY type
    ");
    $stmt->execute($params_base);
    $by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

    success('Revenue report retrieved.', compact(
        'date','summary','chart_data','by_plan','by_method','by_type'
    ));
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
