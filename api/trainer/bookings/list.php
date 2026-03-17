<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
require_method('GET');
$trainer = require_trainer();
$tid     = $trainer['id'];
$pdo     = db();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));
$status   = sanitize_string($_GET['status']       ?? '');
$date     = sanitize_string($_GET['date']         ?? '');

$where  = ['tb.trainer_id = ?'];
$params = [$tid];
if ($status !== '') { $where[] = 'tb.status = ?';       $params[] = $status; }
if ($date   !== '') { $where[] = 'tb.booking_date = ?'; $params[] = $date; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$q = function($sql, $p=[]) use ($pdo){ $s=$pdo->prepare($sql); $s->execute($p); return $s; };

$c_confirmed = (int)$q("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id=? AND status='confirmed'", [$tid])->fetchColumn();
$c_completed = (int)$q("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id=? AND status='completed'", [$tid])->fetchColumn();
$c_cancelled = (int)$q("SELECT COUNT(*) FROM trainer_bookings WHERE trainer_id=? AND status='cancelled'", [$tid])->fetchColumn();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM trainer_bookings tb JOIN members m ON m.id=tb.member_id $where_sql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pag   = get_pagination($total, $page, $per_page);

$stmt = $pdo->prepare(
    "SELECT tb.id, tb.booking_date, tb.booking_time, tb.session_duration, tb.session_minutes,
            tb.total_price, tb.focus_area, tb.fitness_level, tb.fitness_goals,
            tb.recurring, tb.status, tb.payment_method, tb.created_at,
            CONCAT(m.first_name,' ',m.last_name) AS member_name,
            m.email AS member_email, m.plan AS member_plan
     FROM trainer_bookings tb
     JOIN members m ON m.id = tb.member_id
     $where_sql
     ORDER BY tb.booking_date DESC, tb.booking_time DESC
     LIMIT {$pag['per_page']} OFFSET {$pag['offset']}"
);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

success('Bookings retrieved.', [
    'bookings'   => $bookings,
    'pagination' => $pag,
    'counts'     => [
        'confirmed' => $c_confirmed,
        'completed' => $c_completed,
        'cancelled' => $c_cancelled,
        'pending'   => 0,
    ],
]);
