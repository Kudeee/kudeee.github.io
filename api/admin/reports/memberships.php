<?php
/**
 * GET /api/admin/reports/memberships.php
 *
 * Membership growth, churn, and plan distribution report.
 *
 * Query params:
 *   date_from   date     default: start of current month
 *   date_to     date     default: end of current month
 *   group_by    string   day | week | month  (default: month)
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "period": { from, to },
 *     "totals": { total, active, expired, paused, suspended,
 *                 new_signups, cancellations, renewals },
 *     "plan_distribution": [ { plan, count, percentage } ],
 *     "billing_distribution": [ { billing_cycle, count } ],
 *     "chart_data": [ { period, new_signups, cancellations, net_growth } ],
 *     "expiring_soon": [ { id, first_name, last_name, email, plan, expiry_date, days_left } ]
 *   }
 *
 * DB tables used:
 *   members, subscriptions
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$date     = get_date_range();
$group_by = in_array($_GET['group_by'] ?? 'month', ['day','week','month']) ? ($_GET['group_by'] ?? 'month') : 'month';
$from     = $date['from'] . ' 00:00:00';
$to       = $date['to']   . ' 23:59:59';

// ─── TODO: replace stub with real DB queries ──────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Overall totals
    $stmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM members GROUP BY status");
    $totals = ['total'=>0,'active'=>0,'expired'=>0,'paused'=>0,'suspended'=>0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (isset($totals[$r['status']])) $totals[$r['status']] = (int)$r['cnt'];
        $totals['total'] += (int)$r['cnt'];
    }

    // New signups in period
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$from, $to]);
    $totals['new_signups'] = (int) $stmt->fetchColumn();

    // Cancellations (subscriptions cancelled in period)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE status='cancelled' AND updated_at BETWEEN ? AND ?");
    $stmt->execute([$from, $to]);
    $totals['cancellations'] = (int) $stmt->fetchColumn();

    // Renewals (subscriptions created in period excluding first-time)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM subscriptions s
        WHERE s.created_at BETWEEN ? AND ?
          AND (SELECT COUNT(*) FROM subscriptions s2 WHERE s2.member_id=s.member_id) > 1
    ");
    $stmt->execute([$from, $to]);
    $totals['renewals'] = (int) $stmt->fetchColumn();

    // Plan distribution
    $stmt = $pdo->query("
        SELECT plan, COUNT(*) AS count,
               ROUND(COUNT(*)*100.0 / (SELECT COUNT(*) FROM members), 1) AS percentage
        FROM members WHERE status != 'suspended'
        GROUP BY plan ORDER BY count DESC
    ");
    $plan_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Billing distribution
    $stmt = $pdo->query("
        SELECT billing_cycle, COUNT(*) AS count
        FROM subscriptions WHERE status='active'
        GROUP BY billing_cycle
    ");
    $billing_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chart data (new signups vs cancellations over time)
    $date_format = match($group_by) {
        'week'  => "DATE_FORMAT(created_at, '%Y-W%u')",
        'month' => "DATE_FORMAT(created_at, '%Y-%m')",
        default => "DATE(created_at)",
    };

    $stmt = $pdo->prepare("
        SELECT $date_format AS period, COUNT(*) AS new_signups
        FROM members WHERE created_at BETWEEN ? AND ?
        GROUP BY period ORDER BY period ASC
    ");
    $stmt->execute([$from, $to]);
    $signups_chart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $pdo->prepare("
        SELECT $date_format AS period, COUNT(*) AS cancellations
        FROM subscriptions WHERE status='cancelled' AND updated_at BETWEEN ? AND ?
        GROUP BY period ORDER BY period ASC
    ");
    $stmt->execute([$from, $to]);
    $cancel_chart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $all_periods = array_unique(array_merge(array_keys($signups_chart), array_keys($cancel_chart)));
    sort($all_periods);
    $chart_data = [];
    foreach ($all_periods as $p) {
        $s = (int)($signups_chart[$p] ?? 0);
        $c = (int)($cancel_chart[$p] ?? 0);
        $chart_data[] = ['period'=>$p,'new_signups'=>$s,'cancellations'=>$c,'net_growth'=>$s-$c];
    }

    // Expiring in next 7 days
    $stmt = $pdo->prepare("
        SELECT m.id, m.first_name, m.last_name, m.email, m.plan,
               s.expiry_date, DATEDIFF(s.expiry_date, CURDATE()) AS days_left
        FROM members m
        JOIN subscriptions s ON s.member_id=m.id AND s.status='active'
        WHERE s.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY s.expiry_date ASC
    ");
    $stmt->execute();
    $expiring_soon = $stmt->fetchAll(PDO::FETCH_ASSOC);

    success('Membership report retrieved.', compact(
        'date','totals','plan_distribution','billing_distribution','chart_data','expiring_soon'
    ));
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
