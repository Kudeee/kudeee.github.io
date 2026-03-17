<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
require_method('GET');
$trainer = require_trainer();
$tid     = $trainer['id'];
$pdo     = db();

$q = function($sql, $params = []) use ($pdo) {
    $s = $pdo->prepare($sql); $s->execute($params); return $s;
};

$sessionsMonth = (int)$q("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id=? AND status IN('confirmed','completed') AND MONTH(booking_date)=MONTH(CURDATE()) AND YEAR(booking_date)=YEAR(CURDATE())", [$tid])->fetchColumn();
$upcomingSess  = (int)$q("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id=? AND status='confirmed' AND booking_date>=CURDATE()", [$tid])->fetchColumn();
$totalMembers  = (int)$q("SELECT COUNT(DISTINCT member_id) FROM trainer_bookings WHERE trainer_id=? AND status!='cancelled'", [$tid])->fetchColumn();
$earningsMonth = (float)$q("SELECT COALESCE(SUM(total_price),0) FROM trainer_bookings WHERE trainer_id=? AND status IN('confirmed','completed') AND MONTH(booking_date)=MONTH(CURDATE()) AND YEAR(booking_date)=YEAR(CURDATE())", [$tid])->fetchColumn();
$avgRating     = (float)$q("SELECT COALESCE(rating,5.0) FROM trainers WHERE id=?", [$tid])->fetchColumn();

$totalB    = (int)$q("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id=?", [$tid])->fetchColumn();
$doneB     = (int)$q("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id=? AND status='completed'", [$tid])->fetchColumn();
$compRate  = $totalB > 0 ? round(($doneB / $totalB) * 100) : 100;

$upcomingRows = $q(
    "SELECT tb.id, tb.booking_date, tb.booking_time, tb.session_duration, tb.total_price, tb.focus_area, tb.status,
            CONCAT(m.first_name,' ',m.last_name) AS member_name
     FROM trainer_bookings tb JOIN members m ON m.id=tb.member_id
     WHERE tb.trainer_id=? AND tb.status='confirmed' AND tb.booking_date>=CURDATE()
     ORDER BY tb.booking_date ASC, tb.booking_time ASC LIMIT 5", [$tid]
)->fetchAll();

$recentRows = $q(
    "SELECT tb.id, tb.booking_date, tb.booking_time, tb.session_duration, tb.total_price,
            tb.focus_area, tb.fitness_level, tb.recurring, tb.status, tb.payment_method,
            CONCAT(m.first_name,' ',m.last_name) AS member_name, m.plan AS member_plan
     FROM trainer_bookings tb JOIN members m ON m.id=tb.member_id
     WHERE tb.trainer_id=?
     ORDER BY tb.created_at DESC LIMIT 5", [$tid]
)->fetchAll();

success('Dashboard stats.', [
    'stats' => [
        'sessions_this_month' => $sessionsMonth,
        'upcoming_sessions'   => $upcomingSess,
        'total_members'       => $totalMembers,
        'earnings_this_month' => $earningsMonth,
        'avg_rating'          => $avgRating,
        'completion_rate'     => $compRate,
    ],
    'upcoming_sessions' => $upcomingRows,
    'recent_bookings'   => $recentRows,
]);
