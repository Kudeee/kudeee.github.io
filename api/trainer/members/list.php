<?php
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../trainer-config.php';
require_method('GET');
$trainer = require_trainer();
$tid     = $trainer['id'];
$pdo     = db();

$stmt = $pdo->prepare(
    "SELECT
         m.id,
         CONCAT(m.first_name,' ',m.last_name) AS member_name,
         m.email,
         m.plan,
         COUNT(tb.id)                             AS total_sessions,
         SUM(CASE WHEN tb.recurring=1 THEN 1 ELSE 0 END) AS recurring_count,
         MAX(CASE WHEN tb.status IN('confirmed','completed') THEN tb.booking_date END) AS last_session,
         MAX(tb.focus_area)                        AS focus_area
     FROM trainer_bookings tb
     JOIN members m ON m.id = tb.member_id
     WHERE tb.trainer_id = ? AND tb.status != 'cancelled'
     GROUP BY m.id
     ORDER BY total_sessions DESC, member_name ASC"
);
$stmt->execute([$tid]);
$members = $stmt->fetchAll();

// Add recurring flag (true if any active recurring booking)
foreach ($members as &$mem) {
    $mem['recurring']        = (bool)$mem['recurring_count'];
    $mem['total_sessions']   = (int)$mem['total_sessions'];
}

$total     = count($members);
$recurring = count(array_filter($members, fn($m) => $m['recurring']));
$avg_sess  = $total > 0 ? round(array_sum(array_column($members, 'total_sessions')) / $total) : 0;

success('Members retrieved.', [
    'members' => $members,
    'stats'   => [
        'total'     => $total,
        'recurring' => $recurring,
        'avg_sessions' => $avg_sess,
    ],
]);
