<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
require_method('GET');
$trainer = require_trainer();
$tid     = $trainer['id'];
$pdo     = db();

$months = max(1, min(12, sanitize_int($_GET['months'] ?? 6)));

$q = function($sql, $p=[]) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s;
};

$thisMonth  = (float)$q("SELECT COALESCE(SUM(total_price),0) FROM trainer_bookings WHERE trainer_id=? AND status IN('confirmed','completed') AND MONTH(booking_date)=MONTH(CURDATE()) AND YEAR(booking_date)=YEAR(CURDATE())", [$tid])->fetchColumn();
$lastMonth  = (float)$q("SELECT COALESCE(SUM(total_price),0) FROM trainer_bookings WHERE trainer_id=? AND status IN('confirmed','completed') AND MONTH(booking_date)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(booking_date)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))", [$tid])->fetchColumn();
$sessMonth  = (int)$q("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id=? AND status IN('confirmed','completed') AND MONTH(booking_date)=MONTH(CURDATE()) AND YEAR(booking_date)=YEAR(CURDATE())", [$tid])->fetchColumn();

// Monthly chart
$chartRows = $q(
    "SELECT DATE_FORMAT(booking_date,'%b %Y') AS label,
            DATE_FORMAT(booking_date,'%Y-%m')  AS sort_key,
            COALESCE(SUM(total_price),0)        AS total
     FROM trainer_bookings
     WHERE trainer_id = ?
       AND status IN('confirmed','completed')
       AND booking_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
     GROUP BY DATE_FORMAT(booking_date,'%Y-%m')
     ORDER BY sort_key ASC",
    [$tid, $months]
)->fetchAll();

// Recent payments (join payments table)
$recentPayments = $q(
    "SELECT p.created_at, p.amount, p.method, p.transaction_id,
            tb.session_duration, tb.focus_area, tb.booking_date,
            CONCAT(m.first_name,' ',m.last_name) AS member_name
     FROM payments p
     JOIN trainer_bookings tb ON tb.id = p.reference_id
     JOIN members m ON m.id = p.member_id
     WHERE tb.trainer_id = ? AND p.type = 'trainer_session' AND p.status = 'completed'
     ORDER BY p.created_at DESC
     LIMIT 10",
    [$tid]
)->fetchAll();

$totalSixMonths = (float)$q(
    "SELECT COALESCE(SUM(total_price),0) FROM trainer_bookings WHERE trainer_id=? AND status IN('confirmed','completed') AND booking_date >= DATE_SUB(CURDATE(),INTERVAL ? MONTH)",
    [$tid, $months]
)->fetchColumn();

$growth = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;
$avgPerSession = $sessMonth > 0 ? round($thisMonth / $sessMonth) : 0;

success('Earnings summary.', [
    'this_month'       => $thisMonth,
    'last_month'       => $lastMonth,
    'growth_pct'       => $growth,
    'sessions_month'   => $sessMonth,
    'avg_per_session'  => $avgPerSession,
    'total_period'     => $totalSixMonths,
    'monthly_chart'    => $chartRows,
    'recent_payments'  => $recentPayments,
]);
